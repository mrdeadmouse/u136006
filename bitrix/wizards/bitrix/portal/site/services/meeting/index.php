<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("meeting")||!CModule::IncludeModule("iblock"))
	return;

$arXMLID = array(
	"meeting_rooms_".WIZARD_SITE_ID => 'RESERVE_MEETING_IBLOCK_ID',
	"video-meeting_".WIZARD_SITE_ID => 'RESERVE_VMEETING_IBLOCK_ID',
);

$rsIBlock = CIBlock::GetList(
	array(), array("XML_ID" => array_keys($arXMLID), "TYPE" => "events")
);

$arReplace = array();
while ($arIBlock = $rsIBlock->Fetch())
{
	$arReplace[$arXMLID[$arIBlock['XML_ID']]] = $arIBlock['ID'];
}

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/meeting/index.php", $arReplace);
CUrlRewriter::Add(array(
	'CONDITION' => '#^'.WIZARD_SITE_DIR.'services/meeting/#',
	'RULE' => '',
	'ID' => 'bitrix:meetings',
	'PATH' => WIZARD_SITE_DIR.'/services/meeting/index.php'
));

if (!WIZARD_IS_RERUN)
{
	$arFields = array(
		'TITLE' => GetMessage('MEETING_TITLE'),
		'DATE_START' => ConvertTimeStamp(time() + 86400),
		'DURATION' => 3600,
		'DESCRIPTION' => GetMessage('MEETING_DESCRIPTION'),
		'PLACE' => GetMessage('MEETING_PLACE'),
		'USERS' => array(
			1 => CMeeting::ROLE_OWNER,
			477 => CMeeting::ROLE_KEEPER,
			11 => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
			rand(12,476) => CMeeting::ROLE_MEMBER,
		)
	);

	$MEETING_ID = CMeeting::Add($arFields);

	$arResponsible = array(1 => 1, 11, $arFields['USERS'][rand(3,9)]);
	for ($i = 1; $i <= 3; $i++)
	{
		CMeetingItem::Add(array(
			'MEETING_ID' => $MEETING_ID,
			'TITLE' => GetMessage('MEETING_ITEM_TITLE_'.$i),
			'SORT' => 100*$i,
			'RESPONSIBLE' => $arResponsible[$i]
		));
	}
/*
	$iblockCode = "calendar_employees";
	$iblockType = "events";

	$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		CMeeting::AddEvent($MEETING_ID, $arFields, array("CALENDAR_IBLOCK_ID" => $arIBlock['ID']));
	}
*/
}
?>