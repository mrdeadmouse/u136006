<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Форма заявки");
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:form.result.new",
	"",
	Array(
		"SEF_MODE" => "N", 
		"WEB_FORM_ID" => $_REQUEST["WEB_FORM_ID"], 
		"LIST_URL" => "", 
		"EDIT_URL" => "", 
		"SUCCESS_URL" => "my.php", 
		"CHAIN_ITEM_TEXT" => "", 
		"CHAIN_ITEM_LINK" => "", 
		"IGNORE_CUSTOM_TEMPLATE" => "N", 
		"USE_EXTENDED_ERRORS" => "Y", 
		"CACHE_TYPE" => "A", 
		"CACHE_TIME" => "3600", 
		"VARIABLE_ALIASES" => Array(
			"WEB_FORM_ID" => "WEB_FORM_ID",
			"RESULT_ID" => "RESULT_ID"
		)
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>