<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("NOT_SHOW_NAV_CHAIN", "Y");
$APPLICATION->SetPageProperty("title", htmlspecialcharsbx(COption::GetOptionString("main", "site_name", "Extranet")));

$pathToUser = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", false, SITE_ID);
$pathToUser = ($pathToUser ? $pathToUser : SITE_DIR."contacts/personal/user/#user_id#/");

$pathToLogEntry = COption::GetOptionString("socialnetwork", "log_entry_page", false, SITE_ID);
$pathToLogEntry = ($pathToLogEntry ? $pathToLogEntry : SITE_DIR."contacts/personal/log/#log_id#/");

$pathToMessagesChat = COption::GetOptionString("main", "TOOLTIP_PATH_TO_MESSAGES_CHAT", false, SITE_ID);
$pathToMessagesChat = ($pathToMessagesChat ? $pathToMessagesChat : SITE_DIR."contacts/personal/messages/chat/#user_id#/");

$pathToVideoCall = COption::GetOptionString("main", "TOOLTIP_PATH_TO_VIDEO_CALL", false, SITE_ID);
$pathToVideoCall = ($pathToVideoCall ? $pathToVideoCall : SITE_DIR."contacts/personal/video/#user_id#/");

$pathToUserBlogPost = COption::GetOptionString("socialnetwork", "userblogpost_page", false, SITE_ID);
$pathToUserBlogPost = ($pathToUserBlogPost ? $pathToUserBlogPost : SITE_DIR."contacts/personal/user/#user_id#/blog/#post_id#/");

$pathToSmile = COption::GetOptionString("socialnetwork", "smile_page", false, SITE_ID);
$pathToSmile = ($pathToSmile ? $pathToSmile : "/bitrix/images/socialnetwork/smile/");

$folderUsers = COption::GetOptionString("socialnetwork", "user_page", false, SITE_ID);
$folderUsers = ($folderUsers ? $folderUsers : SITE_DIR."contacts/personal/");

$folderWorkgroups = COption::GetOptionString("socialnetwork", "workgroups_page", false, SITE_ID);
$folderWorkgroups = ($folderWorkgroups ? $folderWorkgroups : SITE_DIR."workgroups/");
?>
<?
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.log.ex", 
	"", 
	Array(
		"PATH_TO_LOG_ENTRY" => $pathToLogEntry,
		"PATH_TO_USER" => $pathToUser,
		"PATH_TO_MESSAGES_CHAT" => $pathToMessagesChat,
		"PATH_TO_VIDEO_CALL" => $pathToVideoCall,
		"PATH_TO_GROUP" => $folderWorkgroups."group/#group_id#/",
		"PATH_TO_SMILE" => $pathToSmile,
		"PATH_TO_USER_MICROBLOG" => $folderUsers."user/#user_id#/blog/",
		"PATH_TO_GROUP_MICROBLOG" => $folderWorkgroups."group/#group_id#/blog/",
		"PATH_TO_USER_BLOG_POST" => $folderUsers."user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_MICROBLOG_POST" => $folderUsers."user/#user_id#/blog/#post_id#/",
		"PATH_TO_USER_BLOG_POST_EDIT" => $folderUsers."user/#user_id#/blog/edit/#post_id#/",
		"PATH_TO_USER_BLOG_POST_IMPORTANT" => $folderUsers."user/#user_id#/blog/important/",
		"PATH_TO_GROUP_BLOG_POST" => $folderWorkgroups."group/#group_id#/blog/#post_id#/",
		"PATH_TO_GROUP_MICROBLOG_POST" => $folderWorkgroups."group/#group_id#/blog/#post_id#/",
		"PATH_TO_USER_PHOTO" => $folderUsers."user/#user_id#/photo/",
		"PATH_TO_GROUP_PHOTO" => $folderWorkgroups."group/#group_id#/photo/",
		"PATH_TO_USER_PHOTO_SECTION" => $folderUsers."user/#user_id#/photo/album/#section_id#/",
		"PATH_TO_GROUP_PHOTO_SECTION" => $folderWorkgroups."group/#group_id#/photo/album/#section_id#/",
		"PATH_TO_USER_PHOTO_ELEMENT" => $folderUsers."user/#user_id#/photo/photo/#section_id#/#element_id#/",		
		"PATH_TO_GROUP_PHOTO_ELEMENT" => $folderWorkgroups."group/#group_id#/photo/#section_id#/#element_id#/",
		"PATH_TO_SEARCH_TAG" => SITE_DIR."search/?tags=#tag#",
		"SET_NAV_CHAIN" => "Y",
		"SET_TITLE" => "Y",
		"ITEMS_COUNT" => "32",
		"NAME_TEMPLATE" => CSite::GetNameFormat(),
		"SHOW_LOGIN" => "Y",
		"DATE_TIME_FORMAT" => "F j, Y h:i a",
		"SHOW_YEAR" => "M",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"SHOW_EVENT_ID_FILTER" => "Y",
		"SHOW_SETTINGS_LINK" => "Y",
		"SET_LOG_CACHE" => "Y",
		"USE_COMMENTS" => "Y",
		"BLOG_ALLOW_POST_CODE" => "Y",
		"BLOG_GROUP_ID" => "#BLOG_GROUP_ID#",
		"PHOTO_USER_IBLOCK_TYPE" => "photos",
		"PHOTO_USER_IBLOCK_ID" => "#PHOTO_USER_IBLOCK_ID#",
		"PHOTO_USE_COMMENTS" => "Y",
		"PHOTO_COMMENTS_TYPE" => "FORUM",
		"PHOTO_FORUM_ID" => "#PHOTOGALLERY_FORUM_ID#",
		"PHOTO_USE_CAPTCHA" => "N",
		"FORUM_ID" => "#FORUM_ID#",
		"PAGER_DESC_NUMBERING" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_SHADOW" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CONTAINER_ID" => "log_external_container",
		"SHOW_RATING" => "",
		"RATING_TYPE" => "",
		"NEW_TEMPLATE" => "Y",
		"AVATAR_SIZE" => 50,
		"AVATAR_SIZE_COMMENT" => 39,
		"AUTH" => "Y",
	)
);
?>
<?
if(CModule::IncludeModule('calendar')):
	$APPLICATION->IncludeComponent("bitrix:calendar.events.list", "widget", array(
		"CALENDAR_TYPE" => "user",
		"B_CUR_USER_LIST" => "Y",
		"INIT_DATE" => "",
		"FUTURE_MONTH_COUNT" => "1",
		"DETAIL_URL" => "#SITE_DIR#contacts/personal/user/#user_id#/calendar/",
		"EVENTS_COUNT" => "10",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "3600"
		),
		false
	);
endif;?>


<?
if(CModule::IncludeModule('tasks')):
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.filter.v2",
		"widget",
		array(
			"VIEW_TYPE" => 0,
			"COMMON_FILTER" => array("ONLY_ROOT_TASKS" => "Y"),
			"USER_ID" => $USER->GetID(),
			"ROLE_FILTER_SUFFIX" => "",
			"PATH_TO_TASKS" => "#SITE_DIR#contacts/personal/user/".$USER->GetID()."/tasks/",
			"CHECK_TASK_IN" => "R"
		),
		null,
		array("HIDE_ICONS" => "N")
	);
endif;?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>