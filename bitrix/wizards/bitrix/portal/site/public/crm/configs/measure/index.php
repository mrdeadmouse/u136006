<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/measure/index.php");
global $APPLICATION;

$APPLICATION->SetTitle(GetMessage("CRM_TITLE"));
$APPLICATION->IncludeComponent(
	"bitrix:crm.config.measure",
	"",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "#SITE_DIR#crm/configs/measure/"
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
