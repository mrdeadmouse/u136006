<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/media.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:iblock.tv", "round", Array(
	"IBLOCK_TYPE"	=>	"services",
	"IBLOCK_ID"	=>	"#VIDEO_IBLOCK_ID#",
	"PATH_TO_FILE"	=>	"#VIDEO_PATH_TO_FILE_ID#",
	"DURATION"	=>	"#VIDEO_DURATION_ID#",
	"SECTION_ID"	=>	"#VIDEO_SECTION_ID#",
	"ELEMENT_ID"	=>	"#VIDEO_ELEMENT_ID#",
	"WIDTH"	=>	"400",
	"HEIGHT"	=>	"300",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"36000000"
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>