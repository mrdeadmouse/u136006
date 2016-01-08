<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/learning/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("SERVICES_MENU_COURSES"),
		"index.php",
		Array(), 
		Array(), 
		"" 
	),
	Array(
		GetMessage("SERVICES_MENU_MY_COURSES"),
		"mycourses.php",
		Array(), 
		Array(), 
		"\$GLOBALS['USER']->IsAuthorized()" 
	),
	Array(
		GetMessage("SERVICES_MENU_GRADEBOOK"),
		"gradebook.php",
		Array(), 
		Array(), 
		"\$GLOBALS['USER']->IsAuthorized()" 
	),
	Array(
		GetMessage("SERVICES_MENU_PROFILE"),
		"profile.php",
		Array(), 
		Array(), 
		"\$GLOBALS['USER']->IsAuthorized()"  
	),
);
?>