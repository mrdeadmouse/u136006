<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class DealSettings
{
	const VIEW_LIST = 1;
	const VIEW_WIDGET = 2;
	private static $messagesLoaded = false;
	private static $descriptions = null;

	public static function isCloseDateSyncEnabled()
	{
		return strtoupper(\COption::GetOptionString('crm', 'enable_close_date_sync', 'Y')) === 'Y';
	}

	public static function getViewDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions= array(
				self::VIEW_LIST => GetMessage('CRM_DEAL_SETTINGS_VIEW_LIST'),
				self::VIEW_WIDGET => GetMessage('CRM_DEAL_SETTINGS_VIEW_WIDGET')
			);
		}
		return self::$descriptions;
	}

	public static function prepareViewListItems()
	{
		return \CCrmEnumeration::PrepareListItems(self::GetViewDescriptions());
	}

	public static function setDefaultListViewID($viewID)
	{
		$viewID = (int)$viewID;
		if($viewID === self::VIEW_WIDGET)
		{
			\COption::RemoveOption('crm', 'deal_default_list_view');
		}
		else
		{
			\COption::SetOptionString('crm', 'deal_default_list_view', $viewID);
		}
	}

	public static function getDefaultListViewID()
	{
		return (int)\COption::GetOptionString('crm', 'deal_default_list_view', self::VIEW_WIDGET);
	}

	public static function enableCloseDateSync($enable)
	{
		$enable = (bool)$enable;
		if($enable)
		{
			\COption::RemoveOption('crm', 'enable_close_date_sync');
		}
		else
		{
			\COption::SetOptionString('crm', 'enable_close_date_sync', 'N');
		}
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