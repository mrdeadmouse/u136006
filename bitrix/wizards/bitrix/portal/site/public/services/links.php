<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/links.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?>

<p><?=GetMessage("SERVICES_INFO")?></p>

<?$APPLICATION->IncludeComponent("bitrix:catalog.section", "links", Array(
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"#LINKS_IBLOCK_ID#",
	"SECTION_ID"	=>	$_REQUEST["SECTION_ID"],
	"ELEMENT_SORT_FIELD"	=>	"sort",
	"ELEMENT_SORT_ORDER"	=>	"asc",
	"FILTER_NAME"	=>	"arrFilter",
	"INCLUDE_SUBSECTIONS"	=>	"Y",
	"PAGE_ELEMENT_COUNT"	=>	"30",
	"LINE_ELEMENT_COUNT"	=>	"1",
	"PROPERTY_CODE"	=>	array(
		0	=>	"URL",
		1	=>	"",
	),
	"SECTION_URL"	=>	"#SITE_DIR#services/links.php?SECTION_ID=#SECTION_ID#",
	"DETAIL_URL"	=>	"#SITE_DIR#services/links.php?SECTION_ID=#SECTION_ID#&ELEMENT_ID=#ELEMENT_ID#",
	"BASKET_URL"	=>	"",
	"ACTION_VARIABLE"	=>	"action",
	"PRODUCT_ID_VARIABLE"	=>	"id",
	"SECTION_ID_VARIABLE"	=>	"SECTION_ID",
	"AJAX_MODE"	=>	"Y",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"Y",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000",
	"META_KEYWORDS"	=>	"-",
	"META_DESCRIPTION"	=>	"-",
	"DISPLAY_PANEL"	=>	"Y",
	"DISPLAY_COMPARE"	=>	"N",
	"SET_TITLE"	=>	"N",
	"CACHE_FILTER"	=>	"N",
	"USE_PRICE_COUNT"	=>	"N",
	"SHOW_PRICE_COUNT"	=>	"1",
	"PRICE_VAT_INCLUDE"	=>	"N",
	"DISPLAY_TOP_PAGER"	=>	"N",
	"DISPLAY_BOTTOM_PAGER"	=>	"Y",
	"PAGER_TITLE"	=>	GetMessage("SERVICES_LINKS"),
	"PAGER_SHOW_ALWAYS"	=>	"N",
	"PAGER_TEMPLATE"	=>	"",
	"PAGER_DESC_NUMBERING"	=>	"N",
	"PAGER_DESC_NUMBERING_CACHE_TIME"	=>	"36000000"
	)
);?> 
 <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>