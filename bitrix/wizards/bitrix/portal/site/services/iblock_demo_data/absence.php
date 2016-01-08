<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock") || WIZARD_INSTALL_DEMO_DATA === false)
	return;

$rsIBlock = CIBlock::GetList(array(), array("CODE" => "absence", "TYPE" => "structure", "SITE_ID" => WIZARD_SITE_ID));
if (!$arIBlock = $rsIBlock->Fetch())
	return;

$absence_IBLOCK_ID = $arIBlock["ID"];

$arAbsenceTypes = array();
$dbTypeRes = CIBlockPropertyEnum::GetList(array("SORT"=>"ASC", "VALUE"=>"ASC"), array('IBLOCK_ID' => $absence_IBLOCK_ID, 'PROPERTY_ID' => 'ABSENCE_TYPE'));
while ($arTypeValue = $dbTypeRes->GetNext())
{
	$arAbsenceTypes[$arTypeValue['XML_ID']] = $arTypeValue['ID'];
}

$arAbsenceUsers = Array(12, 43, 84, 95, 118, 138, 166, 188, 210, 240, 276, 305, 379, 403);
foreach($arAbsenceUsers as $absentUserId)
{
	$rsUser = CUser::GetByID($absentUserId);
	if (!$rsUser->Fetch())
		continue;

	$date_from = mktime() - (($absentUserId - 200)/10)*60*60*24;
	$date_to = $date_from + 30*60*60*24;

	$arAbsenceUser = Array(
		    "ACTIVE" => "Y",
		    "IBLOCK_ID" => $absence_IBLOCK_ID,
		    "ACTIVE_FROM" => ConvertTimeStamp($date_from),
		    "ACTIVE_TO" => ConvertTimeStamp($date_to),
		    "NAME" => GetMessage('W_IB_ABSENCE_1_NAME'),
		    "PREVIEW_TEXT" => GetMessage('W_IB_ABSENCE_1_PREV1').date("Y", $date_from).GetMessage('W_IB_ABSENCE_1_PREV2'),
		    "PREVIEW_TEXT_TYPE" => "text",
		    "PROPERTY_VALUES" => Array(
		            "USER" => $absentUserId,
		            "STATE" => GetMessage('W_IB_ABSENCE_1_STATE'),
		            "FINISH_STATE" => GetMessage('W_IB_ABSENCE_1_FINISH'),
					"ABSENCE_TYPE" => $arAbsenceTypes['VACATION'],
		        )
		);

	$el = new CIBlockElement();
	$el->Add($arAbsenceUser);
}

// командировка
$arAbsenceUsers = Array(11, 122, 210, 213, 250, 272, 389, 399);
foreach($arAbsenceUsers as $absentUserId)
{
	$rsUser = CUser::GetByID($absentUserId);
	if (!$rsUser->Fetch())
		continue;

	$date_from = mktime() - (($absentUserId - 200)/10)*60*60*24;
	$date_to = $date_from + ($absentUserId%3)*60*60*24;

	$arAbsenceUser = Array(
		    "ACTIVE" => "Y",
		    "IBLOCK_ID" => $absence_IBLOCK_ID,
		    "ACTIVE_FROM" => ConvertTimeStamp($date_from),
		    "ACTIVE_TO" => ConvertTimeStamp($date_to),
		    "NAME" => GetMessage('W_IB_ABSENCE_2_NAME'),
		    "PREVIEW_TEXT" => GetMessage('W_IB_ABSENCE_2_PREV'),
		    "PREVIEW_TEXT_TYPE" => "text",
		    "PROPERTY_VALUES" => Array(
		            "USER" => $absentUserId,
		            "STATE" => GetMessage('W_IB_ABSENCE_2_NAME'),
		            "FINISH_STATE" => GetMessage('W_IB_ABSENCE_1_FINISH'),
					"ABSENCE_TYPE" => $arAbsenceTypes['ASSIGNMENT'],
		        )
		);

	$el = new CIBlockElement();
	$el->Add($arAbsenceUser);
}

// больничный
$arAbsenceUsers = Array(4, 144, 233, 372, 400);
foreach($arAbsenceUsers as $absentUserId)
{
	$rsUser = CUser::GetByID($absentUserId);
	if (!$rsUser->Fetch())
		continue;

	$date_from = mktime() - (($absentUserId - 200)/10)*60*60*24;
	$date_to = $date_from + ($absentUserId%3+3)*60*60*24;

	$arAbsenceUser = Array(
		    "ACTIVE" => "Y",
		    "IBLOCK_ID" => $absence_IBLOCK_ID,
		    "ACTIVE_FROM" => ConvertTimeStamp($date_from),
		    "ACTIVE_TO" => ConvertTimeStamp($date_to),
		    "NAME" => GetMessage('W_IB_ABSENCE_3_NAME'),
		    "PREVIEW_TEXT" => GetMessage('W_IB_ABSENCE_3_PREV'),
		    "PREVIEW_TEXT_TYPE" => "text",
		    "PROPERTY_VALUES" => Array(
		            "USER" => $absentUserId,
		            "STATE" => GetMessage('W_IB_ABSENCE_3_STATE'),
		            "FINISH_STATE" => GetMessage('W_IB_ABSENCE_1_FINISH'),
					"ABSENCE_TYPE" => $arAbsenceTypes["LEAVESICK"],
		        )
		);

	$el = new CIBlockElement();
	$el->Add($arAbsenceUser);
}

?>