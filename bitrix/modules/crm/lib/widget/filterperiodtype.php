<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;

class FilterPeriodType
{
	const UNDEFINED = '';
	const YEAR = 'Y';
	const QUARTER = 'Q';
	const MONTH = 'M';
	const CURRENT_MONTH = 'M0';
	const CURRENT_QUARTER = 'Q0';
	const LAST_DAYS_90 = 'D90';
	const LAST_DAYS_60 = 'D60';
	const LAST_DAYS_30 = 'D30';
	const LAST_DAYS_7 = 'D7';

	private static $messagesLoaded = false;
	/**
	* @return boolean
	*/
	public static function isDefined($typeID)
	{
		return $typeID === self::YEAR
			|| $typeID === self::QUARTER
			|| $typeID === self::MONTH
			|| $typeID === self::CURRENT_MONTH
			|| $typeID === self::CURRENT_QUARTER
			|| $typeID === self::LAST_DAYS_90
			|| $typeID === self::LAST_DAYS_60
			|| $typeID === self::LAST_DAYS_30
			|| $typeID === self::LAST_DAYS_7;
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		self::includeModuleFile();
		return array(
			self::UNDEFINED  => GetMessage('CRM_FILTER_PERIOD_TYPE_UNDEFINED'),
			self::YEAR => GetMessage('CRM_FILTER_PERIOD_TYPE_YEAR'),
			self::QUARTER => GetMessage('CRM_FILTER_PERIOD_TYPE_QUARTER'),
			self::MONTH => GetMessage('CRM_FILTER_PERIOD_TYPE_MONTH'),
			self::CURRENT_MONTH => GetMessage('CRM_FILTER_PERIOD_TYPE_CURRENT_MONTH'),
			self::CURRENT_QUARTER => GetMessage('CRM_FILTER_PERIOD_TYPE_CURRENT_QUARTER'),
			self::LAST_DAYS_90 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_90'),
			self::LAST_DAYS_60 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_60'),
			self::LAST_DAYS_30 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_30'),
			self::LAST_DAYS_7 => GetMessage('CRM_FILTER_PERIOD_TYPE_LAST_DAYS_7')
		);
	}
	/**
	* @return string
	*/
	public static function getDescription($typeID)
	{
		$descriptions = self::getAllDescriptions();
		return isset($descriptions[$typeID]) ? $descriptions[$typeID] : '';
	}
	/**
	* @return void
	*/
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}