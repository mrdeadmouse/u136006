<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();


if(!CModule::IncludeModule('calendar') || (!(isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized())))
	return;

$userId = $GLOBALS['USER']->GetID();
if (isset($_REQUEST['app_calendar_action']) && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();
	if ($_REQUEST['app_calendar_action'] == 'change_meeting_status' && $userId == $_REQUEST['user_id'])
	{
		CCalendarEvent::SetMeetingStatus(
			$userId,
			intVal($_REQUEST['event_id']),
			$_REQUEST['status'] == 'Y' ? 'Y' : 'N'
		);
	}
	die();
}

$eventId = intVal($arParams['EVENT_ID']);

$Event = CCalendarEvent::GetList(
	array(
		'arFilter' => array(
			"ID" => $eventId,
			"OWNER_ID" => $userId,
			"DELETED" => "N"
		),
		'parseRecursion' => false,
		'fetchAttendees' => true,
		'fetchMeetings' => true,
		'checkPermissions' => true,
		'setDefaultLimit' => false
	)
);
if ($Event && is_array($Event[0]))
{
	$Event = $Event[0];

	if ($Event['IS_MEETING'])
	{
		$arAttendees = array(
			'count' => 0,
			'Y' => array(), // Accepted
			'N' => array(), // Declined
			'Q' => array() // ?
		);

		if (!is_array($Event['~ATTENDEES']) || empty($Event['~ATTENDEES']))
			$Event['IS_MEETING'] = false;

		if ($Event['IS_MEETING'])
		{
			foreach($Event['~ATTENDEES'] as $attendee)
			{
				$attendee['DISPLAY_NAME'] = CCalendar::GetUserName($attendee);
				$arAttendees[$attendee['STATUS']][] = $attendee;
			}

			$arAttendees['count'] = count($Event['~ATTENDEES']);

			unset($Event['~ATTENDEES']);
			$arResult['ATTENDEES'] = $arAttendees;
		}
	}

	if ($Event['LOCATION'] !== '')
		$Event['LOCATION'] = CCalendar::GetTextLocation($Event["LOCATION"]);

	if ($Event['RRULE'] !== '')
	{
		$Event['RRULE'] = CCalendarEvent::ParseRRULE($Event['RRULE']);
		if (is_array($Event['RRULE']) && !isset($Event['RRULE']['UNTIL']))
			$Event['RRULE']['UNTIL'] = $Event['DT_TO_TS'];
		$Event['DT_TO_TS'] = $Event['DT_FROM_TS'] + intval($Event['DT_LENGTH']);
	}
}
else
{
	$Event = array(); // Event is not found
	$arResult['DELETED'] = "Y";
}

$arResult['EVENT'] = $Event;
$arResult['USER_ID'] = $userId;


$this->IncludeComponentTemplate();
?>