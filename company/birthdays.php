<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/company/birthdays.php");
$APPLICATION->SetTitle(GetMessage("COMPANY_TITLE"));
?>
<?$APPLICATION->IncludeComponent("bitrix:intranet.structure.birthday.nearest", ".default", Array(
	"STRUCTURE_PAGE"	=>	"structure.php",
	"PM_URL"	=>	"/company/personal/messages/chat/#USER_ID#/",
	"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"PATH_TO_VIDEO_CALL" => "/company/personal/video/#USER_ID#/",
	"STRUCTURE_FILTER"	=>	"structure",
	"NUM_USERS"	=>	"25",
	"DATE_FORMAT" => "d.m.Y",
	"DATE_FORMAT_NO_YEAR" => "d.m",
	"DATE_TIME_FORMAT" => "d.m.Y H:i:s",
	"AJAX_MODE"	=>	"N",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"SHOW_YEAR"	=>	"M",
	"USER_PROPERTY"	=>	array(
		0	=>	"PERSONAL_PHONE",
		1	=>	"UF_DEPARTMENT",
		2	=>	"UF_PHONE_INNER",
		3	=>	"UF_SKYPE",
		4	=>	"PERSONAL_PHOTO",
	),
	"SHOW_FILTER"	=>	"Y"
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>