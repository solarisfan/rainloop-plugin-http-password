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
	
	private $sTestMode = '';
	
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
			$ch = curl_init($this->sPermissionURL);
			curl_setopt( $ch, CURLOPT_POST, 1);
			$qry = 'user=' . urlencode($sEmailUser);
			$qry .= '&domain=' . urlencode($sEmailDomain);
			$qry .= $this->sTestMode;
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $qry);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $ch, CURLOPT_HEADER, 0);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$xml = curl_exec( $ch );
			curl_close($ch);
			if ($xml) {
				$token = simplexml_load_string($xml);
				if ($token->enabled) {
					if ($token->enabled == 'false') return false;
				}
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
		$ch = curl_init($this->sChangePassword1URL);
		curl_setopt( $ch, CURLOPT_POST, 1);
		$qry = 'user=' . urlencode($sEmailUser);
		$qry .= '&domain=' . urlencode($sEmailDomain);
		$qry .= '&pwd=' . urlencode($sPrevPassword);
		$qry .= $this->sTestMode;
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $qry);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$xml = curl_exec( $ch );
		curl_close($ch);
		if (!$xml) return false;
		$token = simplexml_load_string($xml);
		$ary = '';
		foreach($token as $k => $v) {
			$ary .= '&' . $k . '=' . urlencode($v);
		}
		$ch = curl_init($this->sChangePassword2URL);
		curl_setopt( $ch, CURLOPT_POST, 1);
		$qry = 'pwd=' . urlencode($sNewPassword);
		$qry .= $ary;
		$qry .= $this->sTestMode;
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $qry);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$xml = curl_exec( $ch );
		curl_close($ch);
		if (!$xml) return false;
		$token = simplexml_load_string($xml);
		if ($token->result == 'true') return true;
		return false;
	}
}

?>
