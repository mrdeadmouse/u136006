<?
define("NEED_AUTH",true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/board/my/index.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><?$APPLICATION->IncludeComponent("bitrix:iblock.element.add", ".default", Array(
	"NAV_ON_PAGE"	=>	"10",
	"USE_CAPTCHA"	=>	"N",
	"USER_MESSAGE_ADD"	=>	GetMessage("SERVICES_MESSAGE_ADD"),
	"USER_MESSAGE_EDIT"	=>	GetMessage("SERVICES_MESSAGE_EDIT"),
	"DEFAULT_INPUT_SIZE"	=>	"30",
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"#BOARD_IBLOCK_ID#",
	"PROPERTY_CODES"	=>	array(
		0	=>	"NAME",
		1	=>	"DATE_ACTIVE_TO",
		2	=>	"IBLOCK_SECTION",
		3	=>	"PREVIEW_TEXT",
		4	=>	"#E_MAIL_PROPERTY_ID#",
		5	=>	"#URL_PROPERTY_ID#",
		6	=>	"#PHONE_PROPERTY_ID#",
		7	=>	"",
	),
	"PROPERTY_CODES_REQUIRED"	=>	array(
		0	=>	"NAME",
		1	=>	"IBLOCK_SECTION",
		2	=>	"PREVIEW_TEXT",
		3	=>	"#PHONE_PROPERTY_ID#",
		4	=>	"",
	),
	"GROUPS"	=>	array(
		0	=>	"#WIZARD_EMPLOYEES_GROUP#",
	),
	"STATUS"	=>	array(
		0	=>	"2",
		1	=>	"3",
		2	=>	"1",
	),
	"STATUS_NEW" => "N",
	"ALLOW_EDIT"	=>	"Y",
	"ALLOW_DELETE"	=>	"Y",
	"ELEMENT_ASSOC"	=>	"PROPERTY_ID",
	"ELEMENT_ASSOC_PROPERTY"	=>	"#USER_ID_PROPERTY_ID#",
	"MAX_USER_ENTRIES"	=>	"20",
	"MAX_LEVELS"	=>	"1",
	"LEVEL_LAST"	=>	"Y",
	"MAX_FILE_SIZE"	=>	"0",
	"SEF_MODE"	=>	"N",
	"SEF_FOLDER"	=>	"#SITE_DIR#services/board/my/",
	"AJAX_MODE"	=>	"Y",
	"AJAX_OPTION_SHADOW"	=>	"Y",
	"AJAX_OPTION_JUMP"	=>	"N",
	"AJAX_OPTION_STYLE"	=>	"Y",
	"AJAX_OPTION_HISTORY"	=>	"N",
	"CUSTOM_TITLE_NAME"	=>	GetMessage("SERVICES_TITLE"),
	"CUSTOM_TITLE_TAGS"	=>	"",
	"CUSTOM_TITLE_DATE_ACTIVE_FROM"	=>	"",
	"CUSTOM_TITLE_DATE_ACTIVE_TO"	=>	GetMessage("SERVICES_DATE_ACTIVE_TO"),
	"CUSTOM_TITLE_IBLOCK_SECTION"	=>	GetMessage("SERVICES_CATEGORY"),
	"CUSTOM_TITLE_PREVIEW_TEXT"	=>	GetMessage("SERVICES_TEXT"),
	"CUSTOM_TITLE_PREVIEW_PICTURE"	=>	"",
	"CUSTOM_TITLE_DETAIL_TEXT"	=>	"",
	"CUSTOM_TITLE_DETAIL_PICTURE"	=>	""
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
