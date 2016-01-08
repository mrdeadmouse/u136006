<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("1C integration");
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.config.exch1c",
	".default",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/configs/exch1c/",
		"PATH_TO_CONFIGS_INDEX" => "/crm/configs/"
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>