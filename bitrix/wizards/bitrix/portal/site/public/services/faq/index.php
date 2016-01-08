<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/faq/index.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:support.faq", ".default", Array(
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"#FAQ_IBLOCK_ID#",
	"SECTION"	=>	"-",
	"EXPAND_LIST"	=>	"N",
	"SEF_MODE"	=>	"Y",
	"SEF_FOLDER"	=>	"#SITE_DIR#services/faq/",
	"AJAX_MODE"	=>	"Y",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000",
	"SHOW_RATING" => "",
	"RATING_TYPE" => "",
	"PATH_TO_USER" => "#SITE_DIR#company/personal/user/#user_id#/",
	"SEF_URL_TEMPLATES"	=>	array(
		"faq"	=>	"",
		"section"	=>	"#SECTION_ID#/",
		"detail"	=>	"#SECTION_ID#/#ELEMENT_ID#",
	)
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>