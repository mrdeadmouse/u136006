<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/gallery/index.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:photogallery", ".default", Array(
	"USE_LIGHT_VIEW"	=>	"Y",
	"IBLOCK_TYPE"	=>	"photos",
	"IBLOCK_ID"	=>	"15",
	"SEF_MODE"	=>	"Y",
	"SEF_FOLDER"	=>	"/about/gallery/",
	"PATH_TO_USER" => "/company/personal/user/#user_id#/",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"3600000",
	"USE_RATING"	=>	"Y",
	"DISPLAY_AS_RATING" => "rating_main",
	"SHOW_TAGS"	=>	"Y",
	"USE_COMMENTS"	=>	"N",
	"SHOW_LINK_ON_MAIN_PAGE"	=>	array(
		0	=>	"id",
		1	=>	"shows",
		2	=>	"rating",
		3	=>	"comments",
	),
	"SHOW_ON_MAIN_PAGE"	=>	"none",
	"SHOW_ON_MAIN_PAGE_POSITION"	=>	"left",
	"SHOW_ON_MAIN_PAGE_TYPE"	=>	"none",
	"SHOW_ON_MAIN_PAGE_COUNT"	=>	"",
	"SHOW_PHOTO_ON_DETAIL_LIST"	=>	"none",
	"SHOW_PHOTO_ON_DETAIL_LIST_COUNT"	=>	"500",
	"PAGE_NAVIGATION_TEMPLATE"	=>	"",
	"ORIGINAL_SIZE" => "1280",
	"UPLOADER_TYPE" => "form",
	"WATERMARK_COLORS"	=>	array(
		0	=>	"FF0000",
		1	=>	"FFFF00",
		2	=>	"FFFFFF",
		3	=>	"000000",
		4	=>	"",
	),
	"TEMPLATE_LIST"	=>	".default",
	"CELL_COUNT"	=>	"0",
	"SEF_URL_TEMPLATES"	=>	array(
		"sections_top"	=>	"index.php",
		"section"	=>	"#SECTION_ID#/",
		"section_edit"	=>	"#SECTION_ID#/action/#ACTION#/",
		"section_edit_icon"	=>	"#SECTION_ID#/icon/action/#ACTION#/",
		"upload"	=>	"#SECTION_ID#/action/upload/",
		"detail"	=>	"#SECTION_ID#/#ELEMENT_ID#/",
		"detail_slide_show"	=>	"#SECTION_ID#/#ELEMENT_ID#/slide_show/",
		"detail_list"	=>	"#SECTION_ID#/#ELEMENT_ID#/list/",
		"detail_edit"	=>	"#SECTION_ID#/#ELEMENT_ID#/action/#ACTION#/",
	)
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>