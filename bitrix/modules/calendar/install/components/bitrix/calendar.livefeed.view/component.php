<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!isset($arParams["CALENDAR_TYPE"]))
	$arParams["CALENDAR_TYPE"] = 'user';

if(!CModule::IncludeModule("calendar") || !class_exists("CCalendar"))
	return ShowError(GetMessage("EC_CALENDAR_MODULE_NOT_INSTALLED"));
$arParams['EVENT_ID'] = intval($arParams['EVENT_ID']);
$arResult['ID'] = 'livefeed'.$arParams['EVENT_ID'];
$arResult['EVENT'] = false;
$Events = CCalendarEvent::GetList(
	array(
		'arFilter' => array(
			"ID" => $arParams['EVENT_ID'],
			"DELETED" => "N"
		),
		'parseRecursion' => false,
		'fetchAttendees' => true,
		'checkPermissions' => true,
		'setDefaultLimit' => false
	)
);
if ($Events && is_array($Events[0]))
	$arResult['EVENT'] =  $Events[0];

if ($arResult['EVENT']['LOCATION'] !== '')
	$arResult['EVENT']['LOCATION'] = CCalendar::GetTextLocation($arResult['EVENT']["LOCATION"]);

global $USER_FIELD_MANAGER;
$UF = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $arParams['EVENT_ID'], LANGUAGE_ID);

$arResult['UF_CRM_CAL_EVENT'] = $UF['UF_CRM_CAL_EVENT'];
if (empty($arResult['UF_CRM_CAL_EVENT']['VALUE']))
	$arResult['UF_CRM_CAL_EVENT'] = false;

$arResult['UF_WEBDAV_CAL_EVENT'] = $UF['UF_WEBDAV_CAL_EVENT'];
if (empty($arResult['UF_WEBDAV_CAL_EVENT']['VALUE']))
	$arResult['UF_WEBDAV_CAL_EVENT'] = false;

$arParams['ATTENDEES_SHOWN_COUNT'] = 4;
$arParams['ATTENDEES_SHOWN_COUNT_MAX'] = 8;
$arParams['AVATAR_SIZE'] = 30;

if (!isset($arParams['EVENT_TEMPLATE_URL']))
{
	$editUrl = CCalendar::GetPath('user', '#USER_ID#');
	$arParams['EVENT_TEMPLATE_URL'] = $editUrl.((strpos($editUrl, "?") === false) ? '?' : '&').'EVENT_ID=#EVENT_ID#';
}

$arResult['EVENT']['FROM_WEEK_DAY'] = FormatDate('D', $arResult['EVENT']['DT_FROM_TS']);
$arResult['EVENT']['FROM_MONTH_DAY'] = FormatDate('j', $arResult['EVENT']['DT_FROM_TS']);

if ($arResult['EVENT']['IS_MEETING'])
{
	$arResult['ATTENDEES_INDEX'] = array();
	$arResult['EVENT']['ACCEPTED_ATTENDEES'] = array();
	$arResult['EVENT']['DECLINED_ATTENDEES'] = array();
	foreach ($arResult['EVENT']['~ATTENDEES'] as $i => $att)
	{
		$arResult['ATTENDEES_INDEX'][$att["USER_ID"]] = array(
			"STATUS" => $att['STATUS']
		);

		if ($att['STATUS'] != "Q")
		{
			$att['AVATAR_SRC'] = CCalendar::GetUserAvatar($att);
			$att['URL'] = CCalendar::GetUserUrl($att["USER_ID"], $arParams["PATH_TO_USER"]);
		}

		if ($att['STATUS'] == "Y")
			$arResult['EVENT']['ACCEPTED_ATTENDEES'][] = $att;
		elseif($att['STATUS'] == "N")
			$arResult['EVENT']['DECLINED_ATTENDEES'][] = $att;
	}
}

if ($arParams['MOBILE'] == 'Y')
{
	$arParams['ACTION_URL'] = SITE_DIR.'mobile/index.php?mobile_action=calendar_livefeed';
}
else
{
	$arParams['ACTION_URL'] = $this->getPath().'/action.php';
}

ob_start();
$this->IncludeComponentTemplate();
$html_message = ob_get_contents();
ob_end_clean();

$footStr1 = '<!--#BX_FEED_EVENT_FOOTER_MESSAGE#-->';
$footStr2 = '<!--#BX_FEED_EVENT_FOOTER_MESSAGE_END#-->';
$pos1 = strpos($html_message, $footStr1);
$pos2 = strpos($html_message, $footStr2);

if ($footStr1 !== false)
	$message = substr($html_message, 0, $pos1);
else
	$message = $html_message;
$footer_message = substr($html_message, $pos1 + strlen($footStr1), $pos2 - $pos1 - strlen($footStr1));

return array(
	'MESSAGE' => htmlspecialcharsex($message),
	'FOOTER_MESSAGE' => $footer_message,
	'CACHED_JS_PATH' => $this->getTemplate()->GetFolder().'/script.js' // used for attach js inside cached Live feed
);
?>