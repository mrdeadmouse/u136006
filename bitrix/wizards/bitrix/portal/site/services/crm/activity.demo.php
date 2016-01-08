<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
CModule::IncludeModule("crm");

$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["45"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['1'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Call,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_1_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000000 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_1_DESCR"),
		"DIRECTION" => CCrmActivityDirection::Outgoing,
		"OWNER_ID" => $arDeals["2"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Deal,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'OWNER_ID' => $arDeals["2"]['ID']
			),
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'OWNER_ID' => $arContacts["45"]['ID']
			)
		),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["45"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);

$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["46"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['2'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Meeting,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_2_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000000 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_2_DESCR"),
		'LOCATION' => GetMessage("CRM_DEMO_ACTIVITY_2_LOC"),
		"OWNER_ID" => $arContacts["46"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Contact,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(array(
			'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
			'OWNER_ID' => $arContacts["46"]['ID']
		)),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["46"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);


$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["47"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['3'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Call,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_3_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000007 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_3_DESCR"),
		"DIRECTION" => CCrmActivityDirection::Outgoing,
		"OWNER_ID" => $arContacts["47"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Contact,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(array(
			'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
			'OWNER_ID' => $arContacts["47"]['ID']
		)),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["47"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);


$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["48"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['4'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Meeting,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_4_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000008 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_4_DESCR"),
		'LOCATION' => GetMessage("CRM_DEMO_ACTIVITY_4_LOC"),
		"OWNER_ID" => $arContacts["48"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Contact,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(array(
			'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
			'OWNER_ID' => $arContacts["48"]['ID']
		)),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["48"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);


$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["45"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['5'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Meeting,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_5_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000009 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_5_DESCR"),
		'LOCATION' => GetMessage("CRM_DEMO_ACTIVITY_5_LOC"),
		"OWNER_ID" => $arDeals["2"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Deal,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'OWNER_ID' => $arDeals["2"]['ID']
			),
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'OWNER_ID' => $arContacts["45"]['ID']
			)
		),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["45"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);

$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["49"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['6'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Call,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_6_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000000 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_6_DESCR"),
		"DIRECTION" => CCrmActivityDirection::Outgoing,
		"OWNER_ID" => $arDeals["8"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Deal,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'OWNER_ID' => $arDeals["8"]['ID']
			),
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'OWNER_ID' => $arContacts["49"]['ID']
			)
		),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["49"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);

$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["47"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['7'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Meeting,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_7_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000009 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_7_DESCR"),
		'LOCATION' => GetMessage("CRM_DEMO_ACTIVITY_7_LOC"),
		"OWNER_ID" => $arDeals["5"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Deal,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'OWNER_ID' => $arDeals["5"]['ID']
			),
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'OWNER_ID' => $arContacts["47"]['ID']
			)
		),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["47"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);

$dbResFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => "CONTACT", 'ELEMENT_ID' => $arContacts["45"]['ID'], 'TYPE_ID' =>  "PHONE")
);
while($ar = $dbResFields->Fetch())
{
	$commValue =  $ar['VALUE'];
	$commID = $ar['ID'];
}
$arActivities['8'] = Array(
	"FIELDS" => array(
		'TYPE_ID' =>  CCrmActivityType::Call,
		'SUBJECT' => GetMessage("CRM_DEMO_ACTIVITY_8_SUBJ"),
		'START_TIME' =>  ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID),
		'END_TIME' => ConvertTimeStamp(time()+ 32000000 + CTimeZone::GetOffset(), 'FULL', $siteID),
		'COMPLETED' => 'N',
		'PRIORITY' => CCrmActivityPriority::Medium,
		'DESCRIPTION' => GetMessage("CRM_DEMO_ACTIVITY_8_DESCR"),
		"DIRECTION" => CCrmActivityDirection::Outgoing,
		"OWNER_ID" => $arDeals["3"]['ID'],
		"OWNER_TYPE_ID" => CCrmOwnerType::Deal,
		"RESPONSIBLE_ID" => "1",
		"BINDINGS" => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'OWNER_ID' => $arDeals["3"]['ID']
			),
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'OWNER_ID' => $arContacts["45"]['ID']
			)
		),
	),
	"COMMUNICATIONS" => array(
		'ID' => $commID,
		'TYPE' => "PHONE",
		'VALUE' => $commValue,
		'ENTITY_ID' => $arContacts["45"]['ID'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
	)
);
?>