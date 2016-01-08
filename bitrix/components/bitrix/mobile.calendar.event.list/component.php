<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
header("Content-Type: application/x-javascript");

if(!CModule::IncludeModule('calendar')
	||
	(!(isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized()))
	||
	$GLOBALS['USER']->GetId() != $arParams['USER_ID']
)
	return array('status' => 'failed');

// We check it in the mobile menu
CUserOptions::SetOption("mobile", "calendar_first_visit", "N");

// Limits
if (strlen($arParams["INIT_DATE"]) > 0 && strpos($arParams["INIT_DATE"], '.') !== false)
	$ts = CCalendar::Timestamp($arParams["INIT_DATE"]);
else
	$ts = time();

$arParams["FUTURE_MONTH_COUNT"] = 2;
$userOffset = CCalendar::GetOffset($arParams['USER_ID']);
$fromLimit = CCalendar::Date($ts + $userOffset, false);
$ts = CCalendar::Timestamp($fromLimit);
$delta = 5184000;// 86400 * 30 * 2 ~ two month;
$toLimit = CCalendar::Date($ts + $delta, false);

$arEvents = CCalendar::GetNearestEventsList(array(
	'bCurUserList' => true,
	'userId' => $arParams['USER_ID'],
	'fromLimit' => $fromLimit,
	'toLimit' => $toLimit,
));

if ($arEvents == 'access_denied')
{
	$arResult['ACCESS_DENIED'] = true;
}
elseif ($arEvents == 'inactive_feature')
{
	$arResult['INACTIVE_FEATURE'] = true;
}
elseif (is_array($arEvents))
{
	$arSections = array(
		array("ID" => 'today', "NAME" => CMobile::PrepareStrToJson(GetMessage('EVENTS_GROUP_TODAY'))),
		array("ID" => 'tomorrow', "NAME" => CMobile::PrepareStrToJson(GetMessage('EVENTS_GROUP_TOMORROW'))),
		array("ID" => 'later', "NAME" => CMobile::PrepareStrToJson(GetMessage('EVENTS_GROUP_LATE')))
	);

	$url = '/mobile/calendar/view_event.php';
	$arResult['EVENTS'] = array();

	$oneDay = 86400;

	$todayStartTs = CCalendar::Timestamp(CCalendar::Date(time() + $userOffset, false));
	$todayEndTs = $todayStartTs + $oneDay - 1;

	$tomorrowStartTs = $todayStartTs + $oneDay;
	$tomorrowEndTs = $tomorrowStartTs + $oneDay - 1;

	$today = CCalendar::Date(time(), false);
	$tomorrow = CCalendar::Date(time() + 86400, false);

	$bToday = false;
	$bTomorrow = false;
	$bLater = false;
	$use_sections = "YES";

	$iconEvent = '/bitrix/templates/mobile_app/images/calendar/event.png';
	$iconMeeting = '/bitrix/templates/mobile_app/images/calendar/meeting.png';
	$iconInviting = '/bitrix/templates/mobile_app/images/calendar/meeting-q.png';

	$dateFormat = GetMessage('MB_CAL_EVENT_DATE_FORMAT');
	$timeFormat = IsAmPmMode() ? GetMessage('MB_CAL_EVENT_TIME_FORMAT_AMPM') : GetMessage('MB_CAL_EVENT_TIME_FORMAT');

	$count = count($arEvents);
	for ($i = 0; $i < $count; $i++)
	{
		$event = $arEvents[$i];
		$event['DT_FROM_TS'] = CCalendar::_fixTimestamp($event['DT_FROM_TS']);
		$event['DT_TO_TS'] = CCalendar::_fixTimestamp($event['DT_TO_TS']);

		$item = array(
			"ID" => $event['ID'],
			"NAME" => CMobile::PrepareStrToJson($event['NAME']),
			"URL" => $url."?event_id=".$event['ID']
		);

		if ($event['IS_MEETING'] && $event['USER_MEETING']['STATUS'] == 'N')
			continue;

		if ($event['IS_MEETING'])
			$item["IMAGE"] = $event['USER_MEETING']['STATUS'] == 'Q' ? $iconInviting : $iconMeeting;
		else
			$item["IMAGE"] = $iconEvent;

		$bOneDay = $event['DT_LENGTH'] == 86400; // One full day

		$bDuringOneDay = !$bOneDay && (FormatDate('d.m.Y', $event['DT_FROM_TS']) === FormatDate('d.m.Y', $event['DT_TO_TS']));

		$fromToMess = '';
		// It's event for today
		if ($event['DT_FROM_TS'] <= $todayEndTs && $event['DT_TO_TS'] >= $todayStartTs)
		{
			if ($bOneDay)
				$fromToMess = FormatDate("today", 0).', '.GetMessage('MB_CAL_EVENT_ALL_DAY');
			elseif ($bDuringOneDay)
				$fromToMess = FormatDate("today", 0).', '.GetMessage('MB_CAL_EVENT_TIME_FROM_TO_TIME', Array(
					'#TIME_FROM#' => FormatDate($timeFormat, $event['DT_FROM_TS']),
					'#TIME_TO#' => FormatDate($timeFormat, $event['DT_TO_TS'])
				));

			$item['SECTION_ID'] = 'today';
			$bToday = true;
		}
		// Tomorrow
		elseif($event['DT_FROM_TS'] <= $tomorrowEndTs && $event['DT_TO_TS'] >= $tomorrowStartTs)
		{
			if ($bOneDay)
				$fromToMess = FormatDate("tomorrow", 0).', '.GetMessage('MB_CAL_EVENT_ALL_DAY');
			elseif ($bDuringOneDay)
				$fromToMess = FormatDate("tomorrow", 0).', '.GetMessage('MB_CAL_EVENT_TIME_FROM_TO_TIME', Array(
					'#TIME_FROM#' => FormatDate($timeFormat, $event['DT_FROM_TS']),
					'#TIME_TO#' => FormatDate($timeFormat, $event['DT_TO_TS'])
				));

			$item['SECTION_ID'] = 'tomorrow';
			$bTomorrow = true;
		}
		// Later
		else
		{
			if ($bOneDay)
				$fromToMess = FormatDate($dateFormat, $event['DT_FROM_TS']).', '.GetMessage('MB_CAL_EVENT_ALL_DAY');
			elseif ($bDuringOneDay)
				$fromToMess = FormatDate($dateFormat, $event['DT_FROM_TS']).', '.GetMessage('MB_CAL_EVENT_TIME_FROM_TO_TIME', Array(
					'#TIME_FROM#' => FormatDate($timeFormat, $event['DT_FROM_TS']),
					'#TIME_TO#' => FormatDate($timeFormat, $event['DT_TO_TS'])
				));

			$item['SECTION_ID'] = 'later';
			$bLater = true;
		}

		if ($fromToMess === '')
		{
			$fromToMess = GetMessage('MB_CAL_EVENT_DATE_FROM_TO', Array(
				'#DATE_FROM#' => FormatDate($dateFormat.' '.$timeFormat, $event['DT_FROM_TS']),
				'#DATE_TO#' => FormatDate($dateFormat.' '.$timeFormat, $event['DT_TO_TS'])
			));
		}

		$item['TAGS'] = CMobile::PrepareStrToJson($fromToMess);

		$arResult['EVENTS'][] = $item;
	}

	// Footer under the table
	$strFooter = $count > 0 ? GetMessage('MB_CAL_EVENTS_COUNT', array("#COUNT#" => $count)) : GetMessage('MB_CAL_NO_EVENTS');

	// Kill unused sections
	if (!$bToday && !$bTomorrow)
	{
		$use_sections = "NO";
		$arSections = array();
	}
	else
	{
		$arSections_ = array();
		foreach($arSections as $ind => $sect)
		{
			if ($sect['ID'] == 'today' && $bToday)
				$arSections_[] = $sect;
			if ($sect['ID'] == 'tomorrow' && $bTomorrow)
				$arSections_[] = $sect;
			if ($sect['ID'] == 'later' && $bLater)
				$arSections_[] = $sect;
		}
		$arSections = $arSections_;
	}

	$res = array(
		"TABLE_SETTINGS" => array(
			"footer" => CMobile::PrepareStrToJson($strFooter),
			"use_sections" => $use_sections
		)
	);

	if ($use_sections != "NO")
	{
		$res["data"] = array("events" => $arResult['EVENTS']);
		$res["sections"] = array("events" => $arSections);
	}
	else
	{
		$res["data"] = $arResult['EVENTS'];
	}

	return $res;
}
?>