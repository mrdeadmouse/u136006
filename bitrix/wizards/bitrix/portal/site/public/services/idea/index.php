<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/idea/index.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?>
<?$APPLICATION->IncludeComponent("bitrix:idea", ".default", array(
	"MESSAGE_COUNT" => "10",
	"COMMENTS_COUNT" => "10",
	"DATE_TIME_FORMAT" => "#DATE_TIME_FORMAT#",
	"NAV_TEMPLATE" => "",
	"SMILES_COUNT" => "2",
	"IMAGE_MAX_WIDTH" => "770",
	"IMAGE_MAX_HEIGHT" => "770",
	"EDITOR_RESIZABLE" => "Y",
	"EDITOR_DEFAULT_HEIGHT" => "300",
	"EDITOR_CODE_DEFAULT" => "N",
	"COMMENT_EDITOR_RESIZABLE" => "Y",
	"COMMENT_EDITOR_DEFAULT_HEIGHT" => "200",
	"COMMENT_EDITOR_CODE_DEFAULT" => "N",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/services/idea/",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "3600",
	"CACHE_TIME_LONG" => "604800",
	"PATH_TO_SMILE" => "/bitrix/images/blog/smile/",
	"SET_TITLE" => "Y",
	"SET_NAV_CHAIN" => "Y",
	"BLOG_PROPERTY" => array(
	),
	"BLOG_PROPERTY_LIST" => array(
	),
	"POST_PROPERTY" => array(
	),
	"POST_PROPERTY_LIST" => array(
	),
	"USE_ASC_PAGING" => "N",
	"SHOW_RATING" => "Y",
	"COMMENT_ALLOW_VIDEO" => "N",
	"SHOW_SPAM" => "N",
	"NO_URL_IN_COMMENTS" => "",
	"ALLOW_POST_CODE" => "Y",
	"USE_GOOGLE_CODE" => "Y",
	"BLOG_URL" => "#IDEA_BLOG_CODE#",
	"NAME_TEMPLATE" => "",
	"SHOW_LOGIN" => "Y",
	"USE_SHARE" => "N",
	"IBLOCK_CATOGORIES" => "#IDEA_IBLOCK_CATEGORY#",
	"POST_BIND_USER" => array(
		0 => "1",
	),
	"POST_BIND_STATUS_DEFAULT" => "#IDEA_BIND_STATUS_DEFAULT#",
	"SEF_URL_TEMPLATES" => array(
		"index" => "",
		"status_0" => "status/#status_code#/",
		"category_1" => "category/#category_1#/",
		"category_1_status" => "category/#category_1#/status/#status_code#/",
		"category_2" => "category/#category_1#/#category_2#/",
		"category_2_status" => "category/#category_1#/#category_2#/status/#status_code#/",
		"user_ideas" => "user_idea/#user_id#/",
		"user_ideas_status" => "user_idea/#user_id#/status/#status_code#/",
		"user" => "user/#user_id#/",
		"post_edit" => "edit/#post_id#/",
		"post" => "#post_id#/",
		"post_rss" => "#blog#/rss/#type#/#post_id#/",
		"rss" => "#blog#/rss/#type#",
		"rss_all" => "rss/#type#/#group_id#/",
	)
	),
	false
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>