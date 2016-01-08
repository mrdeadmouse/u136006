<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("calendar") || WIZARD_IS_RERUN)
	return;

COption::SetOptionString("intranet", "calendar_2", "Y");

// company calendar calendar type
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'company_calendar',
		'NAME' => GetMessage('CAL_TYPE_COMPANY_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_view')
		)
	)
));

// Sections
$sectId0 = CCalendar::SaveSection(
	array(
		'arFields' => Array(
			'CAL_TYPE' => 'company_calendar',
			'ID' => 0,
			'NAME' => GetMessage("CAL_COMPANY_SECT_0"),
			'DESCRIPTION' => GetMessage("CAL_COMPANY_SECT_DESC_0"),
			'COLOR' => '#855CC5',
			'TEXT_COLOR' => '',
			'OWNER_ID' => '',
			'EXPORT' => array(
				'ALLOW' => true,
				'SET' => '3_9'
			),
			'ACCESS' => array(),
			'IS_EXCHANGE' => false
		)
	)
);

$sectId1 = CCalendar::SaveSection(
	array(
		'arFields' => Array(
			'CAL_TYPE' => 'company_calendar',
			'ID' => 0,
			'NAME' => GetMessage("CAL_COMPANY_SECT_1"),
			'DESCRIPTION' => GetMessage("CAL_COMPANY_SECT_DESC_1"),
			'COLOR' => '#7DDEC2',
			'TEXT_COLOR' => '',
			'OWNER_ID' => '',
			'EXPORT' => array(
				'ALLOW' => true,
				'SET' => '3_9'
			),
			'ACCESS' => array(),
			'IS_EXCHANGE' => false
		)
	)
);

$sectId2 = CCalendar::SaveSection(
	array(
		'arFields' => Array(
			'CAL_TYPE' => 'company_calendar',
			'ID' => 0,
			'NAME' => GetMessage("CAL_COMPANY_SECT_2"),
			'DESCRIPTION' => GetMessage("CAL_COMPANY_SECT_DESC_2"),
			'COLOR' => '#F6EA68',
			'TEXT_COLOR' => '',
			'OWNER_ID' => '',
			'EXPORT' => array(
				'ALLOW' => true,
				'SET' => '3_9'
			),
			'ACCESS' => array(),
			'IS_EXCHANGE' => false
		)
	)
);

// Events for company_calendar
CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 0,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_0"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_0"),
		'DT_FROM' => GetTime(mktime(9, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(16, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'RRULE' => array(
			'FREQ' => 'WEEKLY',
			'INTERVAL' => 1,
			'UNTIL' => GetTime(mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1), "SHORT"),
			'BYDAY' => 'TU'
		),
		'SECTIONS' => $sectId0
	),
	'userId' => 1
));

CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 0,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_1"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_1"),
		'DT_FROM' => GetTime(mktime(0, 0, 0, date("m"), date("d") + 2, date("Y")) , "SHORT"),
		'DT_TO' => GetTime(mktime(0, 0, 0, date("m"), date("d") + 2, date("Y")) , "SHORT"),
		'RRULE' => array(
			'FREQ' => 'MONTHLY',
			'INTERVAL' => 1,
			'UNTIL' => GetTime(mktime(0, 0, 0, date("m"), date("d"), date("Y") + 1), "SHORT"),
		),
		'SECTIONS' => $sectId0
	),
	'userId' => 1
));

CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 0,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_2"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_2"),
		'DT_FROM' => GetTime(mktime(10, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(12, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'RRULE' => array(
			'FREQ' => 'WEEKLY',
			'INTERVAL' => 1,
			'BYDAY' => 'MO'
		),
		'SECTIONS' => $sectId1
	),
	'userId' => 1
));

CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 0,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_3"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_3"),
		'DT_FROM' => GetTime(mktime(9, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(18, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'RRULE' => array(
			'FREQ' => 'WEEKLY',
			'INTERVAL' => 1,
			'BYDAY' => 'TU'
		),
		'SECTIONS' => $sectId2
	),
	'userId' => 1
));

CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 0,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_4"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_4"),
		'DT_FROM' => GetTime(mktime(12, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(17, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'RRULE' => array(
			'FREQ' => 'WEEKLY',
			'INTERVAL' => 1,
			'BYDAY' => 'FR'
		),
		'SECTIONS' => $sectId2
	),
	'userId' => 1
));

CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'company_calendar',
		'OWNER_ID' => 1,
		'NAME' => GetMessage("CAL_COMP_EVENT_NAME_5"),
		'DESCRIPTION' => GetMessage("CAL_COMP_EVENT_DESC_5"),
		'DT_FROM' => GetTime(mktime(16, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(21, 0, 0, date("m"), date("d"), date("Y")) , "FULL"),
		'RRULE' => array(
			'FREQ' => 'WEEKLY',
			'INTERVAL' => 1,
			'BYDAY' => 'SA'
		),
		'SECTIONS' => $sectId1
	),
	'userId' => 1
));


// User's calendar type
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'user',
		'NAME' => GetMessage('CAL_TYPE_USER_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

// Group's calendar type
CCalendarType::Edit(array(
	'NEW' => true,
	'arFields' => array(
		'XML_ID' => 'group',
		'NAME' => GetMessage('CAL_TYPE_GROUP_NAME'),
		'DESCRIPTION' => '',
		'ACCESS' => array(
			'G2' => CCalendar::GetAccessTasksByName('calendar_type', 'calendar_type_edit')
		)
	)
));

$id = CCalendar::SaveEvent(array(
	'arFields' => array(
		'CAL_TYPE' => 'user',
		'OWNER_ID' => 1,
		'NAME' => GetMessage("W_IB_CALENDAR_EMP_ABS"),
		'DT_FROM' => GetTime(mktime(12, 0, 0, date("m"), date("d") + 1, date("Y")) , "FULL"),
		'DT_TO' => GetTime(mktime(14, 0, 0, date("m"), date("d") + 1, date("Y")) , "FULL"),
		'DESCRIPTION' => ''
	),
	'userId' => 1,
	'autoDetectSection' => true,
	'autoCreateSection' => true
));
?>