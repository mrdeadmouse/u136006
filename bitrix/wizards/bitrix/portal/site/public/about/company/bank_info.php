<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/company/bank_info.php");

$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>
<?=GetMessage("ABOUT_INFO")?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>