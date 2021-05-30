<?php

class HttpChangePasswordPlugin extends \RainLoop\Plugins\AbstractPlugin
{
	public function Init()
	{
		$this->addHook('main.fabrica', 'MainFabrica');
	}

	public function MainFabrica($sName, &$oProvider)
	{
		switch ($sName)
		{
			case 'change-password':
				$sUrl1 = (string) $this->Config()->Get('plugin', 'AccountValidationURL', '');
				$sUrl2 = (string) $this->Config()->Get('plugin', 'ChangePassword1URL', '');
				$sUrl3 = (string) $this->Config()->Get('plugin', 'ChangePassword2URL', '');
				include_once __DIR__.'/HttpChangePasswordDriver.php';

				$oProvider = new HttpChangePasswordDriver();
				$oProvider
					->SetConfig($sUrl1, $sUrl2, $sUrl3)
					->SetAllowedEmails(\strtolower(\trim($this->Config()->Get('plugin', 'allowed_emails', ''))))
					->SetLogger($this->Manager()->Actions()->Logger())
				;

				break;
		}
	}
	
	public function configMapping()
	{
		return array(
			\RainLoop\Plugins\Property::NewInstance('AccountValidationURL')->SetLabel('URL for allowing password change')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('Return if password change is allowed for the given email')
				->SetDefaultValue('https://127.0.0.1/'),
			\RainLoop\Plugins\Property::NewInstance('ChangePassword1URL')->SetLabel('URL for changing password first pass')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('New and old passwords are pass as parameters for setting new password')
				->SetDefaultValue('https://127.0.0.1/'),
			\RainLoop\Plugins\Property::NewInstance('ChangePassword2URL')->SetLabel('URL for changing password second pass')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('New and old passwords are pass as parameters for setting new password')
				->SetDefaultValue('https://127.0.0.1/'),
			\RainLoop\Plugins\Property::NewInstance('allowed_emails')->SetLabel('Allowed emails')
				->SetType(\RainLoop\Enumerations\PluginPropertyType::STRING_TEXT)
				->SetDescription('Allowed emails, space as delimiter, wildcard supported. Example: user1@domain1.net user2@domain1.net *@domain2.net')
				->SetDefaultValue('*')
		);
	}
}
?>
