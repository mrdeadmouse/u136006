<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Мои заявки");
?><?$APPLICATION->IncludeComponent(
	"bitrix:form.result.list.my",
	".default",
	Array(
		"FORMS" => Array("", ""), 
		"NUM_RESULTS" => "10", 
		"LIST_URL" => "form_list.php?WEB_FORM_ID=#FORM_ID#", 
		"VIEW_URL" => "form_view.php?WEB_FORM_ID=#FORM_ID#&RESULT_ID=#RESULT_ID#", 
		"EDIT_URL" => "form_edit.php?WEB_FORM_ID=#FORM_ID#&RESULT_ID=#RESULT_ID#" 
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>