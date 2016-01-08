<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/board/detail.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><?$APPLICATION->IncludeComponent("bitrix:catalog.element", "board", Array(
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"8",
	"ELEMENT_ID"	=>	$_REQUEST["ELEMENT_ID"],
	"SECTION_ID"	=>	$_REQUEST["SECTION_ID"],
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
	"CACHE_TYPE"	=>	"N",
	"CACHE_TIME"	=>	"36000000",
	"META_KEYWORDS"	=>	"-",
	"META_DESCRIPTION"	=>	"-",
	"DISPLAY_PANEL"	=>	"N",
	"SET_TITLE"	=>	"Y",
	"ADD_SECTIONS_CHAIN"	=>	"Y",
	"USE_PRICE_COUNT"	=>	"N",
	"SHOW_PRICE_COUNT"	=>	"1",
	"PRICE_VAT_INCLUDE"	=>	"N",
	"PRICE_VAT_SHOW_VALUE"	=>	"N",
	"LINK_IBLOCK_TYPE"	=>	"",
	"LINK_IBLOCK_ID"	=>	"",
	"LINK_PROPERTY_SID"	=>	"",
	"LINK_ELEMENTS_URL"	=>	"link.php?PARENT_ELEMENT_ID=#ELEMENT_ID#"
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
