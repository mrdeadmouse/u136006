<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/company/mission.php");

$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>
<?=GetMessage("ABOUT_INFO", array("#SITE#" => "/"))?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>