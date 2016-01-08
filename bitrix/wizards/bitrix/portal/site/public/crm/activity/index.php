<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/activity/index.php");
$APPLICATION->SetTitle(GetMessage("CRM_TITLE"));
if (CModule::IncludeModule("crm"))
{
?>
<?$APPLICATION->IncludeComponent("bitrix:crm.activity.list",
	"grid",
	array(
		'PERMISSION_TYPE' => 'WRITE',
		'ENABLE_TOOLBAR' => true,
		'ENABLE_NAVIGATION' => true,
		'DISPLAY_REFERENCE' => true,
		'DISPLAY_CLIENT' => true,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'PREFIX' => 'MY_ACTIVITIES',		
	),
	false
);?>
<?
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>