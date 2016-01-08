<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock") || WIZARD_INSTALL_DEMO_DATA === false)
	return;

$rsIBlock = CIBlock::GetList(array(), array("CODE" => "state_history", "TYPE" => "structure", "SITE_ID" => WIZARD_SITE_ID));
if (!$arIBlock = $rsIBlock->Fetch())
	return;

$state_history_IBLOCK_ID = $arIBlock["ID"];

$state_ACCEPTED = 0;
$dbEnum = CIBlockPropertyEnum::GetList(
	array("DEF"=>"DESC", "SORT"=>"ASC"), 
	array('IBLOCK_ID' => $state_history_IBLOCK_ID, 'CODE' => 'STATE', 'EXTERNAL_ID' => 'ACCEPTED')
);
if ($arEnum = $dbEnum->Fetch())
{
	$state_ACCEPTED = $arEnum['ID'];
}

if(strtolower($GLOBALS['DB']->type) == "mssql")
	$GLOBALS['DB']->Query('SET NOCOUNT ON', true);

$dbrUsers = CUser::GetList($o, $b, Array(), array('SELECT' => array('UF_*')));
while($arUser = $dbrUsers->Fetch())
{
	if(!$arUser["UF_DEPARTMENT"] || !$arUser["WORK_POSITION"])
		continue;

	$arElementFields = Array(
	    "ACTIVE" => "Y",
	    "IBLOCK_ID" => $state_history_IBLOCK_ID,
	    "ACTIVE_FROM" => ($arUser["UF_STATE_FIRST"]?$arUser["UF_STATE_FIRST"]:ConvertTimeStamp(mktime()-(2500 - $arUser["ID"]*5)*60*60*24)),
	    "NAME" => GetMessage("W_IB_STATE_HIST1")." - ".$arUser["LAST_NAME"]." ".$arUser["NAME"],
	    "PREVIEW_TEXT" => GetMessage("W_IB_STATE_HIST1"),
	    "PREVIEW_TEXT_TYPE" => "text",
	    "PROPERTY_VALUES" => Array(
	            "POST" => $arUser["WORK_POSITION"],
	            "USER" => $arUser["ID"],
	            "USER_ACTIVE" => "Y",
	            "DEPARTMENT" => $arUser["UF_DEPARTMENT"][0],
				"STATE" => $state_ACCEPTED
	        )
		);

	$el = new CIBlockElement();
	$el->Add($arElementFields);
}

?>
