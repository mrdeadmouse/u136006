<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

/**
 * @var $arParams array
 * @var $arResult array
 * @var $this CBitrixComponent
 * @var $APPLICATION CMain
 */
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
$arParams["ID"] = intval($arParams["ID"] > 0 ? $arParams["ID"] : $_REQUEST["ID"]);
/********************************************************************
				/Input params
********************************************************************/
$arResult = array(
	"ITEM" => Bitrix\Voximplant\ConfigTable::getById($arParams["ID"])->fetch(),
	"QUEUE" => array(),
	"SIP_CONFIG" => array(),
	"~QUEUE" => array()
);
if (!!$arResult["ITEM"])
{
	if (!empty($arResult["ITEM"]["WORKTIME_DAYOFF"]))
	{
		$arResult["ITEM"]["WORKTIME_DAYOFF"] = explode(",", $arResult["ITEM"]["WORKTIME_DAYOFF"]);
	}

	$db_res = Bitrix\Voximplant\QueueTable::getList(Array(
		'filter' => Array('=CONFIG_ID' => $arResult["ITEM"]["ID"]),
	));
	while ($res = $db_res->fetch())
	{
		$arResult["QUEUE"][$res["USER_ID"]] = $res;
		$arResult["~QUEUE"][$res["ID"]] = $res["USER_ID"];
	}

	if ($arResult["ITEM"]["PORTAL_MODE"] == CVoxImplantConfig::MODE_SIP)
	{
		$viSip = new CVoxImplantSip();
		$arResult["SIP_CONFIG"] = $viSip->Get($arParams["ID"]);
		$arResult["SIP_CONFIG"]['PHONE_NAME'] = $arResult['ITEM']['PHONE_NAME'];
	}
}

if (empty($arResult["ITEM"]))
	return;

if ($_REQUEST["action"] == "save" && check_bitrix_sessid())
{
	$post = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList()->toArray();

	$skipSaving = false;
	$arFieldsSip = Array();

	if (isset($post['SIP']))
	{
		$viSip = new CVoxImplantSip();
		$result = $viSip->Update($arParams["ID"], Array(
			'TYPE' => $arResult["SIP_CONFIG"]["TYPE"],
			'PHONE_NAME' => $post['SIP']['PHONE_NAME'],
			'SERVER' => $post['SIP']['SERVER'],
			'LOGIN' => $post['SIP']['LOGIN'],
			'PASSWORD' => $post['SIP']['PASSWORD'],
			'NEED_UPDATE' => $post['SIP']['NEED_UPDATE'],
		));

		$skipSaving = !$result;

		$arFieldsSip = Array(
			'PHONE_NAME' => $post['SIP']['PHONE_NAME'],
			'SERVER' => $post['SIP']['SERVER'],
			'LOGIN' => $post['SIP']['LOGIN'],
			'PASSWORD' => $post['SIP']['PASSWORD'],
		);
	}

	if (IsModuleInstalled('timeman'))
	{
		$post["TIMEMAN"] = isset($post["TIMEMAN"])? 'Y': 'N';
	}
	else
	{
		$post["TIMEMAN"] = 'N';
	}

	if ($post["NO_ANSWER_RULE"] == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
	{
		if (strlen($post["FORWARD_NUMBER"]) <= 0)
		{
			$post["NO_ANSWER_RULE"] = CVoxImplantIncoming::RULE_PSTN;
		}
		else
		{
			$post["FORWARD_NUMBER"] = substr($post["FORWARD_NUMBER"], 0, 20);
		}
	}
	else
	{
		$post["FORWARD_NUMBER"] = '';
	}

	$workTimeDayOff = "";
	if (isset($post["WORKTIME_DAYOFF"]) && is_array($post["WORKTIME_DAYOFF"]))
	{
		$arAvailableValues = array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
		foreach($post["WORKTIME_DAYOFF"] as $key => $value)
		{
			if (!in_array($value, $arAvailableValues))
				unset($post["WORKTIME_DAYOFF"][$key]);
		}
		if (!empty($post["WORKTIME_DAYOFF"]))
			$workTimeDayOff = implode(",", $post["WORKTIME_DAYOFF"]);
	}

	$workTimeFrom = "";
	$workTimeTo = "";
	if (!empty($post["WORKTIME_FROM"]) && !empty($post["WORKTIME_TO"]))
	{
		preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $post["WORKTIME_FROM"], $matchesFrom);
		preg_match("/^\d{1,2}(\.\d{1,2})?$/i", $post["WORKTIME_TO"], $matchesTo);

		if (isset($matchesFrom[0]) && isset($matchesTo[0]))
		{
			$workTimeFrom = $post['WORKTIME_FROM'];
			$workTimeTo = $post['WORKTIME_TO'];

			if($workTimeFrom > 23.30)
				$workTimeFrom= 23.30;
			if ($workTimeTo <= $workTimeFrom)
				$workTimeTo = $workTimeFrom < 23.30 ? $workTimeFrom + 1 : 23.59;
		}
	}

	$workTimeHolidays = "";
	if (!empty($post["WORKTIME_HOLIDAYS"]))
	{
		preg_match("/^(\d{1,2}\.\d{1,2},?)+$/i", $post["WORKTIME_HOLIDAYS"], $matches);

		if (isset($matches[0]))
		{
			$workTimeHolidays = $post["WORKTIME_HOLIDAYS"];
		}
	}

	if ($post["WORKTIME_DAYOFF_RULE"] == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
	{
		if (strlen($post["WORKTIME_DAYOFF_NUMBER"]) <= 0)
		{
			$post["WORKTIME_DAYOFF_RULE"] = CVoxImplantIncoming::RULE_HUNGUP;
		}
		else
		{
			$post["WORKTIME_DAYOFF_NUMBER"] = substr($post["WORKTIME_DAYOFF_NUMBER"], 0, 20);
		}
	}
	else
	{
		$post["WORKTIME_DAYOFF_NUMBER"] = '';
	}

	$arFields = Array(
		"DIRECT_CODE" => $post["DIRECT_CODE"],
		"DIRECT_CODE_RULE" => $post["DIRECT_CODE_RULE"],
		"CRM" => $post["CRM"],
		"CRM_RULE" => $post["CRM_RULE"],
		"CRM_CREATE" => $post["CRM_CREATE"],
		"CRM_FORWARD" => $post["CRM_FORWARD"],
		"QUEUE_TIME" => $post["QUEUE_TIME"],
		"TIMEMAN" => $post["TIMEMAN"],
		"NO_ANSWER_RULE" => $post["NO_ANSWER_RULE"],
		"FORWARD_NUMBER" => $post["FORWARD_NUMBER"],
		"RECORDING" => $post["RECORDING"],
		"MELODY_LANG" => $post["MELODY_LANG"],
		"MELODY_WELCOME" => $post["MELODY_WELCOME"],
		"MELODY_WELCOME_ENABLE" => $post["MELODY_WELCOME_ENABLE"],
		"MELODY_WAIT" => $post["MELODY_WAIT"],
		"MELODY_HOLD" => $post["MELODY_HOLD"],
		"MELODY_VOICEMAIL" => $post["MELODY_VOICEMAIL"],
		"WORKTIME_ENABLE" => isset($post["WORKTIME_ENABLE"]) ? "Y" : "N",
		"WORKTIME_FROM" => $workTimeFrom,
		"WORKTIME_TO" => $workTimeTo,
		"WORKTIME_HOLIDAYS" => $workTimeHolidays,
		"WORKTIME_DAYOFF" => $workTimeDayOff,
		"WORKTIME_TIMEZONE" => $post["WORKTIME_TIMEZONE"],
		"WORKTIME_DAYOFF_RULE" => $post["WORKTIME_DAYOFF_RULE"],
		"WORKTIME_DAYOFF_NUMBER" => $post["WORKTIME_DAYOFF_NUMBER"],
		"WORKTIME_DAYOFF_MELODY" => $post["WORKTIME_DAYOFF_MELODY"],
	);

	$post["QUEUE"] = (is_array($post["QUEUE"]) ? $post["QUEUE"] : array());
	$post["QUEUE"]["U"] = (is_array($post["QUEUE"]["U"]) ? $post["QUEUE"]["U"] : array());
	$queue = array();
	if (is_array($post["QUEUE"]) && is_array($post["QUEUE"]["U"]))
	{
		foreach($post["QUEUE"] as $type => $k)
		{
			$queue[$type] = str_replace($type, "", $k);
		}
	}

	if ($skipSaving)
	{
		$error = $viSip->GetError()->msg;
	}
	else
	{
		if (($res = Bitrix\Voximplant\ConfigTable::update($arParams["ID"], $arFields)) && $res->isSuccess())
		{
			// TODO We should work with other socialnetwork entities
			$toDrop = array_diff($arResult["~QUEUE"], $queue["U"]);
			$toAdd = array_diff($queue["U"], array_keys($arResult["QUEUE"]));
			foreach ($toDrop as $primary => $id)
				Bitrix\Voximplant\QueueTable::delete($primary);
			foreach ($toAdd as $k)
				Bitrix\Voximplant\QueueTable::add(array(
					"CONFIG_ID" => $arParams["ID"],
					"USER_ID" => $k,
					"STATUS" => "OFFLINE"
				));
			LocalRedirect(CVoxImplantMain::GetPublicFolder().'lines.php?MODE='.$arResult["ITEM"]["PORTAL_MODE"]);
		}
		$error = $res->getErrorMessages();
	}

	$arResult = array(
		"ERROR" => $error,
		"ITEM" => array_merge($arResult["ITEM"], $arFields),
		"QUEUE" => array_flip($queue["U"]),
		"SIP_CONFIG" => array_merge($arResult["SIP_CONFIG"], $arFieldsSip)
	);
}
foreach (array("MELODY_WELCOME", "MELODY_WAIT", "MELODY_HOLD", "MELODY_VOICEMAIL", "WORKTIME_DAYOFF_MELODY") as $id)
{
	if ($arResult["ITEM"][$id] > 0)
	{
		$res = CFile::GetFileArray($arResult["ITEM"][$id]);
		if ($res)
		{
			$arResult["ITEM"]["~".$id] = $res;
		}
		else
		{
			$arResult["ITEM"][$id] = 0;
		}
	}
}
$arResult["ITEM"]["MELODY_LANG"] = (empty($arResult["ITEM"]["MELODY_LANG"]) ? strtoupper(LANGUAGE_ID) : $arResult["ITEM"]["MELODY_LANG"]);
$arResult["ITEM"]["MELODY_LANG"] = (in_array($arResult["ITEM"]["MELODY_LANG"], array("RU", "EN", "DE")) ? $arResult["ITEM"]["MELODY_LANG"] : "EN");
$arResult["DEFAULT_MELODIES"] = CVoxImplantConfig::GetDefaultMelodies(false);

if (IsModuleInstalled('bitrix24'))
{
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=52&CHAPTER_ID=02564': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=55&LESSON_ID=6635');
}
else
{
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&CHAPTER_ID=02699': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=6734');
}

//for work time block
$arResult["TIME_ZONE_ENABLED"] = CTimeZone::Enabled();
$arResult["TIME_ZONE_LIST"] = CTimeZone::GetZones();

if (empty($arResult["ITEM"]["WORKTIME_TIMEZONE"]))
{
	if (LANGUAGE_ID == "ru")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Moscow";
	elseif (LANGUAGE_ID == "de")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Berlin";
	elseif (LANGUAGE_ID == "ua")
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "Europe/Kiev";
	else
		$arResult["ITEM"]["WORKTIME_TIMEZONE"] = "America/New_York";
}

$arResult["WEEK_DAYS"] = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');

$arResult["WORKTIME_LIST_FROM"] = array();
$arResult["WORKTIME_LIST_TO"] = array();
if (CModule::IncludeModule("calendar"))
{
	$arResult["WORKTIME_LIST_FROM"][strval(0)] = CCalendar::FormatTime(0, 0);
	for ($i = 0; $i < 24; $i++)
	{
		if ($i !== 0)
		{
			$arResult["WORKTIME_LIST_FROM"][strval($i)] = CCalendar::FormatTime($i, 0);
			$arResult["WORKTIME_LIST_TO"][strval($i)] = CCalendar::FormatTime($i, 0);
		}
		$arResult["WORKTIME_LIST_FROM"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
		$arResult["WORKTIME_LIST_TO"][strval($i).'.30'] = CCalendar::FormatTime($i, 30);
	}
	$arResult["WORKTIME_LIST_TO"][strval('23.59')] = CCalendar::FormatTime(23, 59);
}

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;
?>