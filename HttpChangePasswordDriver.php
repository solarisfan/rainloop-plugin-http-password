<?php

class HttpChangePasswordDriver implements \RainLoop\Providers\ChangePassword\ChangePasswordInterface
{
	/**
	 * @var string
	 */
	private $sAllowedEmails = '';
	
	/**
	 * @var \MailSo\Log\Logger
	 */
	private $oLogger = null;
	
	/**
	 * @var string
	 */
	private $sPermissionURL = '';
	
	/**
	 * @var string
	 */
	private $sChangePassword1URL = '';
	
	/**
	 * @var string
	 */
	private $sChangePassword2URL = '';
		
	/**
	 * @var
	/**
	 * @param string $sRegisterUserURL
	 * @param int $sChangePasswordURL
	 *
	 * @return \HttpChangePasswordDriver
	 */
	public function SetConfig($sPermissionURL, $sChangePassword1URL, $sChangePassword2URL)
	{
		$this->sPermissionURL = $sPermissionURL;
		$this->sChangePassword1URL = $sChangePassword1URL;
		$this->sChangePassword2URL = $sChangePassword2URL;

		return $this;
	}

	/**
	 * @param string $sAllowedEmails
	 *
	 * @return \HttpChangePasswordDriver
	 */
	public function SetAllowedEmails($sAllowedEmails)
	{
		$this->sAllowedEmails = $sAllowedEmails;
		return $this;
	}

	/**
	 * @param \MailSo\Log\Logger $oLogger
	 *
	 * @return \HttpChangePasswordDriver
	 */
	public function SetLogger($oLogger)
	{
		if ($oLogger instanceof \MailSo\Log\Logger)
		{
			$this->oLogger = $oLogger;
		}

		return $this;
	}

	/**
	 * @param $sUri
	 * @param $sUser
	 * @param $sDomain
	 * @param $sOldpassword
	 * @param $sNewpassword
	 * @param $sTrailer
	 * 
	 * @return URI with substituted username, domain, old password and new password
	 */
	private function RewriteURI($sUri, $sUser, $sDomain, $sOldpassword=false, 
								$sNewpassword=false) {
		$qry=false;
		$tok = strtok($sUri, '&');
		while ($tok !== false) {
			if ($qry) $qry .= '&';
			$tmp = str_replace('%USER%', urlencode($sUser), $tok, $cnt);
			if ($cnt < 1) $tmp = str_replace('%DOMAIN%', urlencode($sDomain), $tok, $cnt);
			if (($sOldpassword) && ($cnt < 1)) $tmp = str_replace('%OLDPASSWORD%', urlencode($sOldpassword), $tok, $cnt);
			if (($sNewpassword) && ($cnt < 1)) $tmp = str_replace('%NEWPASSWORD%', urlencode($sNewpassword), $tok, $cnt);
			$qry .= $tmp;
			$tok = strtok('&');
		}
		return $qry;
	}
	
	/**
	 * @param $sURL
	 * @param $sEmailUser
	 * @param $sEmailDomain
	 * @param $sOldpassword
	 * @param $sNewpassword
	 * @param $sTrailer
	 * 
	 * @return XML content from the URL and supplied parameters
	 */
	private function IssueURL($sUrl, $sEmailUser, $sEmailDomain, 
								$sOldpassword=false, $sNewpassword=false,
								$sTrailer='') {
		$i= strpos($sUrl, '?');
		$sHttp = substr($sUrl, 0, $i);
		$sUri='';
		if ($i) $sUri = substr($sUrl, $i+1);
		$qry = $this->RewriteURI($sUri, $sEmailUser, 
								$sEmailDomain, 
								$sOldpassword, $sNewpassword);
		if ($sTrailer) $qry .= $sTrailer;
		$ch = curl_init($sHttp);
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $qry);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$xml = curl_exec( $ch );
		$errorCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($errorCode !== 200) {
			$xml = false;
		}
		curl_close($ch);
		if ((!$xml) && ($this->oLogger)) 
		{
			$this->oLogger->Write('URL did not return any content: ' . $sUrl, 
									\MailSo\Log\Enumerations\Type::ERROR);
		}
	
		return $xml;
	}
	
	/**
	 * @param \RainLoop\Account $oAccount
	 *
	 * @return bool
	 */
	public function PasswordChangePossibility($oAccount)
	{
		if ($this->oLogger)
		{
			$this->oLogger->Write('Password change possible '.$oAccount->Email());
		}
		$sEmail = $oAccount->Email();
		$sEmailUser = \MailSo\Base\Utils::GetAccountNameFromEmail($sEmail);
		$sEmailDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
		if ($this->sPermissionURL) {
			$xml = $this->IssueURL($this->sPermissionURL, $sEmailUser, $sEmailDomain);
			if ($xml) {
				$token = simplexml_load_string($xml);
				if ($token->enabled) {
					if ($token->enabled == 'false') return false;
				}
			} else {
				return false;
			}
		}
		return $oAccount && $oAccount->Email() &&
			\RainLoop\Plugins\Helper::ValidateWildcardValues($oAccount->Email(), $this->sAllowedEmails);
	}
	
	/**
	 * @param \RainLoop\Account $oAccount
	 * @param string $sPrevPassword
	 * @param string $sNewPassword
	 *
	 * @return bool
	 */
	public function ChangePassword(\RainLoop\Account $oAccount, $sPrevPassword, $sNewPassword)
	{
		if ($this->oLogger)
		{
			$this->oLogger->Write('Try to change password for '.$oAccount->Email());
		}
		$sEmail = $oAccount->Email();
		$sEmailUser = \MailSo\Base\Utils::GetAccountNameFromEmail($sEmail);
		$sEmailDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
		$sTrailer = '';
		if ($this->sChangePassword1URL) {
			$xml = $this->IssueURL($this->sChangePassword1URL, $sEmailUser, $sEmailDomain, $sPrevPassword);
			if (!$xml) return false;
			$token = simplexml_load_string($xml);
			$ary = '';
			foreach($token as $k => $v) {
				$sTrailer .= '&' . $k . '=' . urlencode($v);
			}
		}
		if ($this->sChangePassword2URL) {
			$xml = $this->IssueURL($this->sChangePassword2URL, 
						$sEmailUser, $sEmailDomain, false, 
						$sNewPassword, $sTrailer);
			if (!$xml) return false;
			$token = simplexml_load_string($xml);
			if ($token->result == 'true') return true;
		}
		return false;
	}
}

?>
