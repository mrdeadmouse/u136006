<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/wiki.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><?$APPLICATION->IncludeComponent("bitrix:wiki", ".default", array(
	"IBLOCK_TYPE" => "wiki",
	"IBLOCK_ID" => "28",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"ELEMENT_NAME" => $_REQUEST["title"],
	"SHOW_RATING" => "",
	"RATING_TYPE" => "",
	"PATH_TO_USER" => "/company/personal/user/#user_id#/",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/services/wiki/",
	"NAV_ITEM" => GetMessage("SERVICES_TITLE"),
	"SET_TITLE" => "Y",
	"SET_STATUS_404" => "N",
	"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
	"ADD_SECTIONS_CHAIN" => "N",
	"USE_REVIEW" => "Y",
	"MESSAGES_PER_PAGE" => "10",
	"USE_CAPTCHA" => "Y",
	"PATH_TO_SMILE" => "/bitrix/images/forum/smile/",
	"FORUM_ID" => "9",
	"URL_TEMPLATES_READ" => "",
	"SHOW_LINK_TO_FORUM" => "N",
	"POST_FIRST_MESSAGE" => "Y",
	"SEF_URL_TEMPLATES" => array(
		"index" => "",
		"post" => "#wiki_name#/",
		"post_edit" => "#wiki_name#/edit/",
		"categories" => "categories/",
		"discussion" => "#wiki_name#/discussion/",
		"history" => "#wiki_name#/history/",
		"history_diff" => "#wiki_name#/history/diff/",
		"search" => "search/",
	)
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>