<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/company/index.php");
$APPLICATION->SetTitle(GetMessage("COMPANY_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:intranet.search", ".default", Array(
	"STRUCTURE_PAGE"	=>	"structure.php",
	"PM_URL"	=>	"#SITE_DIR#company/personal/messages/chat/#USER_ID#/",
	"PATH_TO_CONPANY_DEPARTMENT" => "#SITE_DIR#company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"PATH_TO_VIDEO_CALL" => "#SITE_DIR#company/personal/video/#USER_ID#/",
	"STRUCTURE_FILTER"	=>	"structure",
	"FILTER_1C_USERS"	=>	"N",
	"USERS_PER_PAGE"	=>	"25",
	"FILTER_SECTION_CURONLY"	=>	"N",
	"SHOW_ERROR_ON_NULL"	=>	"Y",
	"NAV_TITLE"	=>	GetMessage("COMPANY_NAV_TITLE"),
	"SHOW_NAV_TOP"	=>	"N",
	"SHOW_NAV_BOTTOM"	=>	"Y",
	"SHOW_UNFILTERED_LIST"	=>	"Y",
	"AJAX_MODE" => "Y",
	"AJAX_OPTION_SHADOW" => "N",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "Y",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "604800",
	"FILTER_NAME"	=>	"company_search",
	"FILTER_DEPARTMENT_SINGLE"	=>	"Y",
	"FILTER_SESSION"	=>	"N",
	"DEFAULT_VIEW"	=>	"list",
	"LIST_VIEW"	=>	"list",
	"USER_PROPERTY_TABLE"	=>	array(
		0	=>	"PERSONAL_PHOTO",
		1	=>	"FULL_NAME",
		2	=>	"WORK_POSITION",
		3	=>	"WORK_PHONE",
		4	=>	"UF_DEPARTMENT",
		5 	=> 	"UF_PHONE_INNER",
		6	=> 	"UF_SKYPE",
	),

	"USER_PROPERTY_EXCEL" => array(
		0 => "FULL_NAME",
		1 => "EMAIL",
		2 => "PERSONAL_PHONE",
		3 => "PERSONAL_FAX",
		4 => "PERSONAL_MOBILE",
		5 => "WORK_POSITION",
		6 => "UF_DEPARTMENT",
		7 => "UF_PHONE_INNER",
		8 => "UF_SKYPE",
	),

	"USER_PROPERTY_LIST"	=>	array(
		0	=>	"EMAIL",
		1	=>	"PERSONAL_PHONE",
		2	=>	"PERSONAL_FAX",
		3	=>	"UF_DEPARTMENT",		
		4	=>	"UF_PHONE_INNER",
		5	=>	"UF_SKYPE",
		6 	=> 	"PERSONAL_MOBILE",
		7 	=> 	"PERSONAL_PHOTO",
	)
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
