<?php
namespace Bitrix\Dav;

use Bitrix\Main\UrlRewriter;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Application extends \Bitrix\Main\Authentication\Application
{
	/**
	 * Application passwords event handler.
	 * @return array
	 */
	public static function onApplicationsBuildList()
	{
		return array(
			array(
				"ID" => "caldav",
				"NAME" => Loc::getMessage("dav_app_calendar"),
				"DESCRIPTION" => Loc::getMessage("dav_app_calendar_desc"),
				"SORT" => 100,
				"OPTIONS" => array(Loc::getMessage("dav_app_calendar_phone")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
			array(
				"ID" => "carddav",
				"NAME" => Loc::getMessage("dav_app_card"),
				"DESCRIPTION" => Loc::getMessage("dav_app_card_desc"),
				"SORT" => 200,
				"OPTIONS" => array(Loc::getMessage("dav_app_calendar_phone")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
			array(
				"ID" => "webdav",
				"NAME" => Loc::getMessage("dav_app_doc"),
				"DESCRIPTION" => Loc::getMessage("dav_app_doc_desc"),
				"SORT" => 300,
				"OPTIONS" => array(Loc::getMessage("dav_app_doc_office")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
		);
	}

	public function __construct()
	{
		$this->validUrls = array("/bitrix/groupdav.php", "/index.php", "/.well-known");

		$site = \Bitrix\Main\Application::getInstance()->getContext()->getSite();
		$urls = UrlRewriter::getList($site);
		foreach ($urls as $url)
		{
			if (in_array($url['ID'], array('bitrix:socialnetwork_user', 'bitrix:socialnetwork_group', 'bitrix:disk.common')))
				$this->validUrls[] = $url['PATH'];
		}
	}

	public function checkScope()
	{
		if (parent::checkScope())
		{
			if (static::checkDavHeaders())
				return true;
		}

		return false;
	}

	public static function checkDavHeaders()
	{
		$davHeaders = array("DAV", "IF", "DEPTH", "OVERWRITE", "DESTINATION", "LOCK_TOKEN", "TIMEOUT", "STATUS_URI");
		foreach ($davHeaders as $header)
		{
			if (array_key_exists("HTTP_".$header, $_SERVER))
				return true;
		}

		$davMethods = array("OPTIONS", "PUT", "PROPFIND", "REPORT", "PROPPATCH", "MKCOL", "COPY", "MOVE", "LOCK", "UNLOCK", "DELETE", "COPY", "MOVE");
		foreach ($davMethods as $method)
		{
			if ($_SERVER["REQUEST_METHOD"] == $method)
				return true;
		}

		if (strpos($_SERVER['HTTP_USER_AGENT'], "Microsoft Office") !== false &&
			strpos($_SERVER['HTTP_USER_AGENT'], "Outlook") === false
				||
			strpos($_SERVER['HTTP_USER_AGENT'], "MiniRedir") !== false
		)
		{
			return true;
		}

		return false;
	}
}