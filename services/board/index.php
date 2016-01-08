<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/board/index.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?>
<h4><a href="/services/board/my/?edit=Y"><?=GetMessage("SERVICES_ADD")?></a> | <a href="/services/board/my/"><?=GetMessage("SERVICES_MY")?></a></h4>

<?$APPLICATION->IncludeComponent("bitrix:catalog.section", "board", Array(
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"8",
	"SECTION_ID"	=>	$_REQUEST["SECTION_ID"],
	"ELEMENT_SORT_FIELD"	=>	"sort",
	"ELEMENT_SORT_ORDER"	=>	"asc",
	"FILTER_NAME"	=>	"arrFilter",
	"INCLUDE_SUBSECTIONS"	=>	"Y",
	"PAGE_ELEMENT_COUNT"	=>	"30",
	"LINE_ELEMENT_COUNT"	=>	"1",
	"PROPERTY_CODE"	=>	array(
		0	=>	"E_MAIL",
		1	=>	"URL",
		2	=>	"PHONE",
		3	=>	"USER_ID",
		4	=>	"",
	),
	"SECTION_URL"	=>	"index.php?SECTION_ID=#SECTION_ID#",
	"DETAIL_URL"	=>	"detail.php?SECTION_ID=#SECTION_ID#&ELEMENT_ID=#ELEMENT_ID#",
	"BASKET_URL"	=>	"",
	"ACTION_VARIABLE"	=>	"action",
	"PRODUCT_ID_VARIABLE"	=>	"id",
	"SECTION_ID_VARIABLE"	=>	"SECTION_ID",
	"AJAX_MODE"	=>	"Y",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"86400",
	"PAGER_DESC_NUMBERING"	=> "Y",
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
	"PAGER_TITLE"	=>	GetMessage("SERVICES_BOARD"),
	"PAGER_SHOW_ALWAYS"	=>	"N",
	"PAGER_TEMPLATE"	=>	"",
	"PAGER_DESC_NUMBERING"	=>	"N",
	"PAGER_DESC_NUMBERING_CACHE_TIME"	=>	"36000"
	)
);?>
<br />
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
