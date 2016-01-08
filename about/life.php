<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/life.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:news", ".default", Array(
	"IBLOCK_TYPE"	=>	"news",
	"IBLOCK_ID"	=>	"1",
	"NEWS_COUNT"	=>	"10",
	"USE_SEARCH"	=>	"N",
	"USE_RSS"	=>	"N",
	"USE_RATING"	=>	"N",
	"USE_CATEGORIES"	=>	"N",
	"USE_REVIEW"	=>	"Y",
	"MESSAGES_PER_PAGE"	=>	"10",
	"USE_CAPTCHA"	=>	"Y",
	"PATH_TO_SMILE"	=>	"/bitrix/images/forum/smile/",
	"FORUM_ID"	=>	"1",
	"URL_TEMPLATES_READ"	=>	"",
	"SHOW_LINK_TO_FORUM"	=>	"N",
	"USE_FILTER"	=>	"N",
	"SORT_BY1"	=>	"ACTIVE_FROM",
	"SORT_ORDER1"	=>	"DESC",
	"SORT_BY2"	=>	"SORT",
	"SORT_ORDER2"	=>	"ASC",
	"CHECK_DATES"	=>	"Y",
	"SEF_MODE"	=>	"N",
	"SEF_FOLDER"	=>	"/about/",
	"AJAX_MODE"	=>	"N",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000",
	"CACHE_FILTER"	=>	"N",
	"DISPLAY_PANEL"	=>	"Y",
	"SET_TITLE"	=>	"Y",
	"INCLUDE_IBLOCK_INTO_CHAIN"	=>	"Y",
	"ADD_SECTIONS_CHAIN"	=>	"Y",
	"USE_PERMISSIONS"	=>	"N",
	"PREVIEW_TRUNCATE_LEN"	=>	"",
	"LIST_ACTIVE_DATE_FORMAT"	=>	(LANGUAGE_ID == "en") ? "F j, Y" : ((LANGUAGE_ID == "de") ? "j. F Y" : "d.m.Y"),
	"LIST_FIELD_CODE"	=>	array(
	),
	"LIST_PROPERTY_CODE"	=>	array(
	),
	"HIDE_LINK_WHEN_NO_DETAIL"	=>	"N",
	"DISPLAY_NAME"	=>	"N",
	"META_KEYWORDS"	=>	"-",
	"META_DESCRIPTION"	=>	"-",
	"DETAIL_ACTIVE_DATE_FORMAT"	=>	(LANGUAGE_ID == "en") ? "F j, Y" : ((LANGUAGE_ID == "de") ? "j. F Y" : "d.m.Y"),
	"DETAIL_FIELD_CODE"	=>	array(
	),
	"DETAIL_PROPERTY_CODE"	=>	array(
	),
	"DETAIL_DISPLAY_TOP_PAGER"	=>	"N",
	"DETAIL_DISPLAY_BOTTOM_PAGER"	=>	"Y",
	"DETAIL_PAGER_TITLE"	=>	GetMessage("ABOUT_INFO"),
	"DETAIL_PAGER_TEMPLATE"	=>	"",
	"DISPLAY_TOP_PAGER"	=>	"N",
	"DISPLAY_BOTTOM_PAGER"	=>	"Y",
	"PAGER_TITLE"	=>	GetMessage("ABOUT_PAGE_TITLE"),
	"PAGER_SHOW_ALWAYS"	=>	"N",
	"PAGER_TEMPLATE"	=>	"",
	"PAGER_DESC_NUMBERING"	=>	"Y",
	"PAGER_SHOW_ALL" => "N",
	"PAGER_DESC_NUMBERING_CACHE_TIME"	=>	"36000000",
	"DISPLAY_DATE"	=>	"Y",
	"DISPLAY_PICTURE"	=>	"Y",
	"DISPLAY_PREVIEW_TEXT"	=>	"Y",
	"VARIABLE_ALIASES"	=>	array(
		"SECTION_ID"	=>	"SECTION_ID",
		"ELEMENT_ID"	=>	"ID",
	)
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>