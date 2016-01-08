<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Интеграция с \"1С:Предприятие\"");
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