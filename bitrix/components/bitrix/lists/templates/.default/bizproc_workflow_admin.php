<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$dir = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/")));
__IncludeLang($dir."lang/".LANGUAGE_ID."/bizproc_workflow_admin.php");
$APPLICATION->IncludeComponent("bitrix:lists.element.navchain", ".default", array(
	"IBLOCK_TYPE_ID" => $arParams["IBLOCK_TYPE_ID"],
	"IBLOCK_ID" => $arResult["VARIABLES"]["list_id"],
	"LISTS_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["lists"],
	"LIST_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["list"],
	"ADD_NAVCHAIN_SECTIONS" => "N",
	"ADD_NAVCHAIN_ELEMENT" => "N",
	"CACHE_TYPE" => $arParams["CACHE_TYPE"],
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	),
	$component,
	array("HIDE_ICONS" => "Y")
);
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=>array(
			array(
				"TEXT" => GetMessage("CT_BL_STATE_BIZPROC"),
				"TITLE" => GetMessage("CT_BL_STATE_BIZPROC_TITLE"),
				"LINK" => CHTTP::urlAddParams(str_replace(
						array("#list_id#", "#ID#"),
						array($arResult["VARIABLES"]["list_id"], 0),
						$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_edit"]
				), array("init" => "statemachine")),
				"ICON" => "btn-new",
			),
			array(
				"TEXT" => GetMessage("CT_BL_SEQ_BIZPROC"),
				"TITLE" => GetMessage("CT_BL_SEQ_BIZPROC_TITLE"),
				"LINK" => str_replace(
						array("#list_id#", "#ID#"),
						array($arResult["VARIABLES"]["list_id"], 0),
						$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_edit"]
				),
				"ICON" => "btn-new",
			),
			array(
				"SEPARATOR" => "Y",
			),
			array(
				"TEXT" => htmlspecialcharsbx(CIBlock::GetArrayByID($arResult["VARIABLES"]["list_id"], "ELEMENTS_NAME")),
				"TITLE" => GetMessage("CT_BL_ELEMENTS_TITLE"),
				"LINK" => CHTTP::urlAddParams(str_replace(
					array("#list_id#", "#section_id#"),
					array($arResult["VARIABLES"]["list_id"], 0),
					$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["list"]
				), array("list_section_id" => ""))
			),
		),
	),
	$component, array("HIDE_ICONS" => "Y")
);
if($arParams["IBLOCK_TYPE_ID"] == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
{
	$moduleId = "lists";
	$entity = "BizprocDocument";
	$createDefaultTemplate = 'N';
}
else
{
	$moduleId = "iblock";
	$entity = "CIBlockDocument";
	$createDefaultTemplate = 'Y';
}
$APPLICATION->IncludeComponent("bitrix:bizproc.workflow.list", ".default", Array(
	"MODULE_ID" => $moduleId,
	"ENTITY" => $entity,
	"DOCUMENT_ID" => "iblock_".$arResult["VARIABLES"]["list_id"],
	"CREATE_DEFAULT_TEMPLATE" => $createDefaultTemplate,
	"EDIT_URL" => str_replace(
				array("#list_id#"),
				array($arResult["VARIABLES"]["list_id"]),
				$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_edit"]
			),
	"SET_TITLE" => "Y",
	"EDIT_VARS_URL" => str_replace(
				array("#list_id#"),
				array($arResult["VARIABLES"]["list_id"]),
				$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_vars"]
			),
	"EDIT_CONSTANTS_URL" => str_replace(
		array("#list_id#"),
		array($arResult["VARIABLES"]["list_id"]),
		$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_constants"]
	),
	"TARGET_MODULE_ID" => "lists",
	),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>