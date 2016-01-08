<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/index.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:news.list", "official", Array(
	"IBLOCK_TYPE"	=>	"news",
	"IBLOCK_ID"	=>	"#OFFICIAL_NEWS_IBLOCK_ID#",
	"NEWS_COUNT"	=>	"10",
	"SORT_BY1"	=>	"ACTIVE_FROM",
	"SORT_ORDER1"	=>	"DESC",
	"SORT_BY2"	=>	"SORT",
	"SORT_ORDER2"	=>	"ASC",
	"FILTER_NAME"	=>	"",
	"FIELD_CODE"	=>	array(
	),
	"PROPERTY_CODE"	=>	array(
		0	=>	"DOC_TYPE",
	),
	"DETAIL_URL"	=>	"official.php?ID=#ELEMENT_ID#",
	"AJAX_MODE"	=>	"N",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000",
	"CACHE_FILTER"	=>	"N",
	"PREVIEW_TRUNCATE_LEN"	=>	"",
	"ACTIVE_DATE_FORMAT"	=>	(LANGUAGE_ID == "en") ? "F j, Y" : ((LANGUAGE_ID == "de") ? "j. F Y" : "d.m.Y"),
	"DISPLAY_PANEL"	=>	"Y",
	"SET_TITLE"	=>	"N",
	"INCLUDE_IBLOCK_INTO_CHAIN"	=>	"Y",
	"ADD_SECTIONS_CHAIN"	=>	"Y",
	"HIDE_LINK_WHEN_NO_DETAIL"	=>	"Y",
	"PARENT_SECTION"	=>	"",
	"DISPLAY_TOP_PAGER"	=>	"N",
	"DISPLAY_BOTTOM_PAGER"	=>	"Y",
	"PAGER_TITLE"	=>	GetMessage("ABOUT_PAGE_TITLE"),
	"PAGER_SHOW_ALWAYS"	=>	"N",
	"PAGER_TEMPLATE"	=>	"",
	"PAGER_DESC_NUMBERING"	=>	"Y",
	"PAGER_SHOW_ALL" => "N",
	"PAGER_DESC_NUMBERING_CACHE_TIME"	=>	"36000000",
	"DISPLAY_DATE"	=>	"Y",
	"DISPLAY_NAME"	=>	"Y",
	"DISPLAY_PICTURE"	=>	"N",
	"DISPLAY_PREVIEW_TEXT"	=>	"Y"
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>