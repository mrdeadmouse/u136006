<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Просмотр заявки");
?>
<?$APPLICATION->IncludeComponent("bitrix:form.result.view", "intranet", array(
	"RESULT_ID" => $_REQUEST["RESULT_ID"],
	"SEF_MODE" => "N",
	"SEF_FOLDER" => "/services/requests/",
	"SHOW_ADDITIONAL" => "Y",
	"SHOW_ANSWER_VALUE" => "N",
	"SHOW_STATUS" => "Y",
	"EDIT_URL" => "form_edit.php",
	"CHAIN_ITEM_TEXT" => "",
	"CHAIN_ITEM_LINK" => ""
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>