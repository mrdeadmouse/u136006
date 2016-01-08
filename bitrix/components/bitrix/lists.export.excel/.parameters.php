<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if (!CModule::IncludeModule("lists"))
	return;

$strSelectedType = $arCurrentValues["IBLOCK_TYPE_ID"];

$arTypes = array();
$rsTypes = CLists::GetIBlockTypes();
while ($ar = $rsTypes->Fetch())
{
	$arTypes[$ar["IBLOCK_TYPE_ID"]] = "[".$ar["IBLOCK_TYPE_ID"]."] ".$ar["NAME"];
	if (!$strSelectedType)
		$strSelectedType = $ar["IBLOCK_TYPE_ID"];
}

$arIBlocks = array();
$rsIBlocks = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $strSelectedType, "ACTIVE" => "Y"));
while ($ar = $rsIBlocks->Fetch())
{
	$arIBlocks[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(),
	"PARAMETERS" => array(
		"IBLOCK_TYPE_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CP_BLL_IBLOCK_TYPE_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arTypes,
			"DEFAULT" => "lists",
		),
		"IBLOCK_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CP_BLL_IBLOCK_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => '={$_REQUEST["list_id"]}',
		),
		"SECTION_ID" => Array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CP_BLL_SECTION_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["section_id"]}',
		),
		"LIST_URL" => CListsParameters::GetPathTemplateParam(
			"SECTIONS",
			"LIST_URL",
			GetMessage("CP_BLL_LIST_URL"),
			"lists.list.php?list_id=#list_id#&section_id=#section_id#",
			"URL_TEMPLATES"
		),
		"LIST_FILE_URL" => CListsParameters::GetPathTemplateParam(
			"FILE",
			"LIST_FILE_URL",
			GetMessage("CP_BLL_LIST_FILE_URL"),
			"lists.file.php?list_id=#list_id#&section_id=#section_id#&element_id=#element_id#&field_id=#field_id#&file_id=#file_id#",
			"URL_TEMPLATES"
		),
		"CACHE_TIME" => Array("DEFAULT" => 3600),
	),
);