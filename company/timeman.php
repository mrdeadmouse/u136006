<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 
$APPLICATION->SetPageProperty("HIDE_SIDEBAR", "Y");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/company/timeman.php");

$APPLICATION->SetTitle(GetMessage("COMPANY_TITLE"));
?> <?
$APPLICATION->IncludeComponent("bitrix:timeman.report", ".default", array());
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>