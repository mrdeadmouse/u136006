<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/company/leaders.php");
$APPLICATION->SetTitle(GetMessage("COMPANY_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:intranet.structure.honour", ".default", Array(
	"STRUCTURE_PAGE"	=>	"#SITE_DIR#company/structure.php",
	"PM_URL"	=>	"#SITE_DIR#company/personal/messages/chat/#USER_ID#/",
	"PATH_TO_VIDEO_CALL" => "#SITE_DIR#company/personal/video/#USER_ID#/",
	"PATH_TO_CONPANY_DEPARTMENT" => "#SITE_DIR#company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"STRUCTURE_FILTER"	=>	"structure",
	"NUM_USERS"	=>	"25",
	"USER_PROPERTY"	=>	array(
		0	=>	"PERSONAL_PHONE",
		1	=>	"UF_DEPARTMENT",
		2	=>	"UF_PHONE_INNER",
		3	=>	"UF_SKYPE",
		4	=>	"PERSONAL_PHOTO",
	)
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>