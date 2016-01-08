<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/docs/.left.menu.php");

$aMenuLinks = Array(
    Array(
		GetMessage("DOCS_MENU_ALL_DOCS"),
		"/docs/index.php", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("DOCS_MENU_SHARED"),
		"/docs/shared/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("DOCS_MENU_SALE"),
		"/docs/sale/", 
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("DOCS_MENU_MANAGE"),
		"/docs/manage/", 
		Array(), 
		Array(), 
		"" 
	)
);
?>
