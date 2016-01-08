<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/learning/course.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><?$APPLICATION->IncludeComponent(
	"bitrix:learning.course",
	"",
	Array(
		"SEF_MODE" => "N", 
		"COURSE_ID" => $_REQUEST["COURSE_ID"], 
		"CHECK_PERMISSIONS" => "Y", 
		"PAGE_WINDOW" => "10", 
		"SHOW_TIME_LIMIT" => "Y", 
		"PAGE_NUMBER_VARIABLE" => "PAGE", 
		"TESTS_PER_PAGE" => "20", 
		"SET_TITLE" => "Y", 
		"CACHE_TYPE" => "A", 
		"CACHE_TIME" => "3600",
		"PATH_TO_USER_PROFILE" => "#SITE_DIR#company/personal/user/#user_id#/",
		"VARIABLE_ALIASES" => Array(
			"COURSE_ID" => "COURSE_ID",
			"INDEX" => "INDEX",
			"LESSON_ID" => "LESSON_ID",
			"CHAPTER_ID" => "CHAPTER_ID",
			"SELF_TEST_ID" => "SELF_TEST_ID",
			"TEST_ID" => "TEST_ID",
			"TYPE" => "TYPE",
			"TEST_LIST" => "TEST_LIST",
			"GRADEBOOK" => "GRADEBOOK",
			"FOR_TEST_ID" => "FOR_TEST_ID"
		)
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>