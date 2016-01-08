<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Bitrix24Manager
{
	private static $isLicensePaid = null;
	private static $isPaidAccount = null;
	private static $settings = null;

	protected static function loadSettings()
	{
		if(self::$settings !== null)
		{
			return;
		}

		$s = Main\Config\Option::get('crm', 'b24_settings', '', '');
		self::$settings = $s !== '' ? unserialize($s) : array();

		$isLicensed = self::isPaidLicense();
		if(!isset(self::$settings['enableRestBizProc']))
		{
			self::$settings['enableRestBizProc'] = $isLicensed;
		}
		else if(!$isLicensed)
		{
			self::$settings['enableRestBizProc'] = false;
		}
	}
	protected static function saveSettings()
	{
		Main\Config\Option::set('crm', 'b24_settings', serialize(self::$settings), '');
	}

	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
	public static function isPaidAccount()
	{
		if(self::$isPaidAccount !== null)
		{
			return self::$isPaidAccount;
		}

		if(\COption::GetOptionString('voximplant', 'account_payed', 'N') === 'Y')
		{
			return (self::$isPaidAccount = true);
		}

		return (self::$isPaidAccount = self::isPaidLicense());
	}
	public static function isPaidLicense()
	{
		if(self::$isLicensePaid !== null)
		{
			return self::$isLicensePaid;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsLicensePaid'))
		{
			return (self::$isLicensePaid = false);
		}


		return (self::$isLicensePaid = \CBitrix24::IsLicensePaid());
	}
	public static function isRestBizProcEnabled()
	{
		self::loadSettings();
		return self::$settings['enableRestBizProc'];
	}
}