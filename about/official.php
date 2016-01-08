<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/official.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:news.detail", "official", Array(
	"IBLOCK_TYPE"	=>	"news",
	"IBLOCK_ID"	=>	"2",
	"ELEMENT_ID"	=>	$_REQUEST["ID"],
	"CHECK_DATES"	=>	"Y",
	"FIELD_CODE"	=>	array(
	),
	"PROPERTY_CODE"	=>	array(
		0	=>	"DOC_TYPE",
	),
	"IBLOCK_URL"	=>	"index.php",
	"AJAX_MODE"	=>	"N",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000",
	"META_KEYWORDS"	=>	"-",
	"META_DESCRIPTION"	=>	"-",
	"DISPLAY_PANEL"	=>	"N",
	"SET_TITLE"	=>	"Y",
	"INCLUDE_IBLOCK_INTO_CHAIN"	=>	"Y",
	"ADD_SECTIONS_CHAIN"	=>	"Y",
	"ACTIVE_DATE_FORMAT"	=>	(LANGUAGE_ID == "en") ? "F j, Y" : ((LANGUAGE_ID == "de") ? "j. F Y" : "d.m.Y"),
	"USE_PERMISSIONS"	=>	"N",
	"DISPLAY_TOP_PAGER"	=>	"N",
	"DISPLAY_BOTTOM_PAGER"	=>	"Y",
	"PAGER_TITLE"	=>	GetMessage("ABOUT_INFO"),
	"PAGER_TEMPLATE"	=>	"",
	"DISPLAY_DATE"	=>	"Y",
	"DISPLAY_NAME"	=>	"Y",
	"DISPLAY_PICTURE"	=>	"Y",
	"DISPLAY_PREVIEW_TEXT"	=>	"N"
	)
);?></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>