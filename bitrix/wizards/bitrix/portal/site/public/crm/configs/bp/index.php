<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/bp/index.php");
CModule::IncludeModule('bizproc');


$APPLICATION->SetTitle(GetMessage("CRM_TITLE"));
?>  <?
$APPLICATION->IncludeComponent(
	"bitrix:crm.config.bp",
	"",
	Array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "#SITE_DIR#crm/configs/bp/",
		"SEF_URL_TEMPLATES" => Array(
			"ENTITY_LIST_URL" => "",
			"FIELDS_LIST_URL" => "#entity_id#/",
			"FIELD_EDIT_URL" => "#entity_id#/edit/#bp_id#/"
		),
		"VARIABLE_ALIASES" => Array(
			"ENTITY_LIST_URL" => Array(),
			"FIELDS_LIST_URL" => Array(),
			"FIELD_EDIT_URL" => Array(),
		)
	)
);

?> <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>