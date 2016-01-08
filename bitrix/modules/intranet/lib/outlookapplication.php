<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OutlookApplication extends \Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/bitrix/tools/ws_calendar/",
		"/bitrix/tools/ws_calendar_extranet/",
		"/bitrix/tools/ws_contacts/",
		"/bitrix/tools/ws_contacts_crm/",
		"/bitrix/tools/ws_contacts_extranet/",
		"/bitrix/tools/ws_contacts_extranet_emp/",
		"/bitrix/tools/ws_tasks/",
		"/bitrix/tools/ws_tasks_extranet/",
	);

	public static function OnApplicationsBuildList()
	{
		return array(
			"ID" => "ws_outlook",
			"NAME" => Loc::getMessage("WS_OUTLOOK_APP_TITLE"),
			"DESCRIPTION" => Loc::getMessage("WS_OUTLOOK_APP_DESC"),
			"SORT" => 1000,
			"CLASS" => '\Bitrix\Intranet\OutlookApplication',
			"OPTIONS_CAPTION" => Loc::getMessage('WS_OUTLOOK_APP_OPTIONS_CAPTION'),
			"OPTIONS" => array(
				Loc::getMessage("WS_OUTLOOK_APP_TITLE_OPTION"),
			)
		);
	}
}
