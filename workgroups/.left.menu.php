<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/workgroups/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("WORKGROUPS_MENU_GROUPS"),
		"/workgroups/index.php?filter_my=Y", 
		Array(), 
		Array(), 
		"" 
	)
);
?>