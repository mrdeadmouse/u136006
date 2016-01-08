<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/search/index.php");
$APPLICATION->SetTitle(GetMessage("SEARCH_TITLE"));
?>

<?$APPLICATION->IncludeComponent(
	"bitrix:search.page",
	"icons",
	Array(
		"AJAX_MODE" => "N", 
		"RESTART" => "N", 
		"CHECK_DATES" => "N", 
		"USE_TITLE_RANK" => "N", 
		"arrWHERE" => Array("intranet", "iblock_news","iblock_library","blog"), 
		"arrFILTER" => Array(), 
		"SHOW_WHERE" => "Y", 
		"PAGE_RESULT_COUNT" => "50", 
		"CACHE_TYPE" => "A", 
		"CACHE_TIME" => "36000000", 
		"PAGER_TITLE" => GetMessage("SEARCH_RESULT"),
		"PAGER_SHOW_ALWAYS" => "N", 
		"PAGER_TEMPLATE" => "", 
		"SHOW_RATING" => "",
		"RATING_TYPE" => "",
		"PATH_TO_USER_PROFILE" => "#SITE_DIR#company/personal/user/#user_id#/",
		"AJAX_OPTION_SHADOW" => "Y", 
		"AJAX_OPTION_JUMP" => "N", 
		"AJAX_OPTION_STYLE" => "Y", 
		"AJAX_OPTION_HISTORY" => "N" 
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>