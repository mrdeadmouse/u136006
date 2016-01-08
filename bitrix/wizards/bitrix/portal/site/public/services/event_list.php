<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/event_list.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));?>
<?
$APPLICATION->IncludeComponent("bitrix:event_list", ".default", array(
	"USER_PATH" => "#SITE_ID#company/personal/user/#user_id#/",
	"PAGE_NUM" => "10",
	"FILTER" => array(
		0 => "#OUR_LIFE_IBLOCK_ID#",
		1 => "#OFFICIAL_NEWS_IBLOCK_ID#",
		2 => "USERS",
		3 => "PAGE_EDIT",
		4 => "MENU_EDIT",
	)
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>