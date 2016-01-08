<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/company/style.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>
<?=GetMessage("ABOUT_INFO1")?>

<?=GetMessage("ABOUT_INFO2", array("#SITE#" => "#SITE_DIR#"))?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>