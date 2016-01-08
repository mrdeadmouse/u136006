<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(isset($_REQUEST['back_url']))
{
	$backUrl = urldecode($_REQUEST["back_url"]);
}
else
{
	$backUrl = $arResult["FOLDER"];
	$backUrl .= CComponentEngine::MakePathFromTemplate(
		$arResult["URL_TEMPLATES"]["list_element_edit"],
		array(
			"list_id" => $arResult["VARIABLES"]["list_id"],
			"section_id" => 0,
			"element_id" => $arResult["VARIABLES"]["element_id"]
		)
	);
}
if(!preg_match('#^(?:/|\?|https?://)(?:\w|$)#D', $backUrl))
	$backUrl = '#';

$arButtons = array(
	array(
		"TEXT"=>GetMessage("CT_BL_LIST_GO_BACK"),
		"TITLE"=>GetMessage("CT_BL_LIST_GO_BACK"),
		"LINK"=>$backUrl,
		"ICON"=>"btn-list",
	),
);
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS" => $arButtons
	),
	$component
);

if($arParams["IBLOCK_TYPE_ID"] == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
{
	$moduleId = "lists";
	$entity = "BizprocDocument";
}
else
{
	$moduleId = "iblock";
	$entity = "CIBlockDocument";
}
$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.start", ".default", array(
	"MODULE_ID" => $moduleId,
	"ENTITY" => $entity,
	"DOCUMENT_TYPE" => "iblock_".$arResult["VARIABLES"]["list_id"],
	"DOCUMENT_ID" => $arResult["VARIABLES"]["element_id"],
	),
	$component
);
?>