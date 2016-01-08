<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (!CBXFeatures::IsFeatureEnabled("Workgroups"))
{
	ShowError(GetMessage("SONET_WORKGROUPS_FEATURE_DISABLED"));
	return;
}

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",

	"group_reindex" => "group_reindex.php",
	"group_content_search" => "group/#group_id#/search/",
	"group_request_user" => "group/#group_id#/user/#user_id#/request/",
	"group_create" => "create/",

	"search" => "search.php",
	"group" => "group/#group_id#/",
	"group_edit" => "group/#group_id#/edit/",
	"group_requests" => "group/#group_id#/requests/",
	"group_requests_out" => "group/#group_id#/requests_out/",
	"group_mods" => "group/#group_id#/moderators/",
	"group_users" => "group/#group_id#/users/",
	"group_ban" => "group/#group_id#/ban/",
	"group_delete" => "group/#group_id#/delete/",
	"group_features" => "group/#group_id#/features/",
	"group_subscribe" => "group/#group_id#/subscribe/",
	"group_list" => "group/",
	"group_search" => "group/search/",
	"group_search_subject" => "group/search/#subject_id#/",
	"user_leave_group" => "group/#group_id#/user_leave/",
	"user_request_group" => "group/#group_id#/user_request/",
	"group_request_search" => "group/#group_id#/user_search/",
	"message_to_group" => "group/#group_id#/chat/",

	"group_photo" => "group/#group_id#/photo/",
	"group_photo_gallery" => "group/#group_id#/photo/gallery/",
	"group_photo_gallery_edit" => "group/#group_id#/photo/gallery/action/#action#/",
	"group_photo_galleries" => "group/#group_id#/photo/galleries/",
	"group_photo_section" => "group/#group_id#/photo/album/#section_id#/",
	"group_photo_section_edit" => "group/#group_id#/photo/album/#section_id#/action/#action#/",
	"group_photo_section_edit_icon" => "group/#group_id#/photo/album/#section_id#/icon/action/#action#/",
	"group_photo_element_upload" => "group/#group_id#/photo/photo/#section_id#/action/upload/",
	"group_photo_element" => "group/#group_id#/photo/photo/#section_id#/#element_id#/",
	"group_photo_element_edit" => "group/#group_id#/photo/photo/#section_id#/#element_id#/action/#action#/",
	"group_photo_element_slide_show" => "group/#group_id#/photo/photo/#section_id#/#element_id#/slide_show/",
	"group_photofull_gallery" => "group/#group_id#/photo/gallery/#user_alias#/",
	"group_photofull_gallery_edit" => "group/#group_id#/photo/gallery/#user_alias#/action/#action#/",
	"group_photofull_section" => "group/#group_id#/photo/album/#user_alias#/#section_id#/",
	"group_photofull_section_edit" => "group/#group_id#/photo/album/#user_alias#/#section_id#/action/#action#/",
	"group_photofull_section_edit_icon" => "group/#group_id#/photo/album/#user_alias#/#section_id#/icon/action/#action#/",
	"group_photofull_element_upload" => "group/#group_id#/photo/photo/#user_alias#/#section_id#/action/upload/",
	"group_photofull_element" => "group/#group_id#/photo/photo/#user_alias#/#section_id#/#element_id#/",
	"group_photofull_element_edit" => "group/#group_id#/photo/photo/#user_alias#/#section_id#/#element_id#/action/#action#/",
	"group_photofull_element_slide_show" => "group/#group_id#/photo/photo/#user_alias#/#section_id#/#element_id#/slide_show/",

	"group_calendar" => "group/#group_id#/calendar/",

	"group_files" => "group/#group_id#/files/lib/#path#/",
	"group_files_short" => "folder/view/#section_id#/#element_id#/#element_name#",
	"group_files_section_edit" => "group/#group_id#/files/folder/edit/#section_id#/#action#/",
	"group_files_element" => "group/#group_id#/files/element/view/#element_id#/",
	"group_files_element_comment" => "group/#group_id#/files/element/comment/#topic_id#/#message_id#/",
	"group_files_element_edit" => "group/#group_id#/files/element/edit/#element_id#/#action#/",
	"group_files_element_file" => "",
	"group_files_element_history" => "group/#group_id#/files/element/history/#element_id#/",
	"group_files_element_history_get" => "group/#group_id#/files/element/historyget/#element_id#/#element_name#",
	"group_files_element_version" => "group/#group_id#/files/element/version/#action#/#element_id#/",
	"group_files_element_versions" => "group/#group_id#/files/element/versions/#element_id#/",
	"group_files_element_upload" => "group/#group_id#/files/element/upload/#section_id#/",
	"group_files_help" => "group/#group_id#/files/help/",
	"group_files_connector" => "group/#group_id#/files/connector/",
	"group_files_webdav_bizproc_history" => "group/#group_id#/files/bizproc/history/#element_id#/",
	"group_files_webdav_bizproc_history_get" => "group/#group_id#/files/bizproc/historyget/#element_id#/#id#/#element_name#",
	"group_files_webdav_bizproc_log" => "group/#group_id#/files/bizproc/log/#element_id#/#id#/",
	"group_files_webdav_bizproc_view" => "group/#group_id#/files/bizproc/bizproc/#element_id#/",
	"group_files_webdav_bizproc_workflow_admin" => "group/#group_id#/files/bizproc/admin/",
	"group_files_webdav_bizproc_workflow_edit" => "group/#group_id#/files/bizproc/edit/#id#/",
	"group_files_webdav_start_bizproc" => "group/#group_id#/files/bizproc/start/#element_id#/",
	"group_files_webdav_task_list" => "group/#group_id#/files/bizproc/task/list/",
	"group_files_webdav_task" => "group/#group_id#/files/bizproc/task/read/#id#/",

	"group_blog" => "group/#group_id#/blog/",
	"group_blog_post_edit" => "group/#group_id#/blog/edit/#post_id#/",
	"group_blog_rss" => "group/#group_id#/blog/rss/#type#/",
	"group_blog_post_rss" => "group/#group_id#/blog/rss/#type#/#post_id#/",
	"group_blog_draft" => "group/#group_id#/blog/draft/",
	"group_blog_moderation" => "group/#group_id#/blog/moderation/",
	"group_blog_post" => "group/#group_id#/blog/#post_id#/",

	"group_forum" => "group/#group_id#/forum/",
	"group_forum_topic" => "group/#group_id#/forum/#topic_id#/",
	"group_forum_topic_edit" => "group/#group_id#/forum/edit/#topic_id#/",
	"group_forum_message" => "group/#group_id#/forum/message/#topic_id#/#message_id#/",
	"group_forum_message_edit" => "group/#group_id#/forum/message/#action#/#topic_id#/#message_id#/",

	"group_tasks" => "group/#group_id#/tasks/",
	"group_tasks_task" => "group/#group_id#/tasks/task/#action#/#task_id#/",
	"group_tasks_view" => "group/#group_id#/tasks/view/#action#/#view_id#/",
	"group_tasks_report" => "group/#group_id#/tasks/report/",
	"group_tasks_report_construct" => "group/#group_id#/tasks/report/construct/#report_id#/#action#/",
	"group_tasks_report_view" => "group/#group_id#/tasks/report/view/#report_id#/",

	"group_log" => "group/#group_id#/log/",
	"group_log_rss" => "group/#group_id#/log/rss/?bx_hit_hash=#sign#&events=#events#",
	"group_log_rss_mask" => "group/#group_id#/log/rss/",
);

$diskEnabled = (
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
	&& CModule::includeModule('disk')
);

$bExtranetEnabled = IsModuleInstalled('extranet');
if ($bExtranetEnabled)
{
	$extranetSiteId = COption::GetOptionString("extranet", "extranet_site");
	if (strlen($extranetSiteId) <= 0)
	{
		$bExtranetEnabled = false;
	}
}

if (
	$bExtranetEnabled
	&& $arParams["SEF_MODE"] == "Y"
	&& SITE_ID == $extranetSiteId
	&& !CSocNetUser::IsCurrentUserModuleAdmin()
	&& CModule::IncludeModule('intranet')
)
{
	if ($USER->IsAuthorized())
	{
		$rsCurrentUser = CUser::GetById($USER->GetId());
		if (
			($arCurrentUser = $rsCurrentUser->Fetch())
			&& !empty($arCurrentUser["UF_DEPARTMENT"])
			&& is_array($arCurrentUser["UF_DEPARTMENT"])
			&& intval($arCurrentUser["UF_DEPARTMENT"][0]) > 0
		)
		{
			$arRedirectSite = CSocNetLogComponent::GetSiteByDepartmentId($arCurrentUser["UF_DEPARTMENT"]);
			if (!$arRedirectSite)
			{
				$arRedirectSite = array(
					"LID" => SITE_ID,
					"SERVER_NAME" => SITE_SERVER_NAME
				);
			}
		}
	}
}

if($diskEnabled)
{
	$arDefaultUrlTemplates404["group_disk"] = "group/#group_id#/disk/path/#PATH#";
	$arDefaultUrlTemplates404["group_disk_file"] = "group/#group_id#/disk/file/#FILE_PATH#";
	$arDefaultUrlTemplates404["group_trashcan_list"] = "group/#group_id#/disk/trashcan/#TRASH_PATH#";
	$arDefaultUrlTemplates404["group_trashcan_file_view"] = "group/#group_id#/disk/trash/file/#TRASH_FILE_PATH#";
	$arDefaultUrlTemplates404["group_external_link_list"] = "group/#group_id#/disk/external";
	$arDefaultUrlTemplates404["group_disk_help"] = "group/#group_id#/disk/help";
	$arDefaultUrlTemplates404["group_disk_bizproc_workflow_admin"] = "group/#group_id#/disk/bp/";
	$arDefaultUrlTemplates404["group_disk_bizproc_workflow_edit"] = "group/#group_id#/disk/bp_edit/#ID#/";
	$arDefaultUrlTemplates404["group_disk_start_bizproc"] = "group/#group_id#/disk/bp_start/#ELEMENT_ID#/";
	$arDefaultUrlTemplates404["group_disk_task"] = "group/#group_id#/disk/bp_task/#ID#/";
	$arDefaultUrlTemplates404["group_disk_task_list"] = "group/#group_id#/disk/bp_task_list/";
}

$arDefaultUrlTemplatesN404 = array(

	"index" => "",

	"group_reindex" => "page=group_reindex",
	"group_content_search" => "page=group_content_search&group_id=#group_id#",
	"group_create" => "page=group_create&user_id=#group_id#",

	"group" => "page=group&group_id=#group_id#",
	"group_edit" => "page=group_edit&group_id=#group_id#",
	"group_requests" => "page=group_requests&group_id=#group_id#",
	"group_requests_out" => "page=group_requests_out&group_id=#group_id#",
	"group_mods" => "page=group_mods&group_id=#group_id#",
	"group_users" => "page=group_users&group_id=#group_id#",
	"group_ban" => "page=group_ban&group_id=#group_id#",
	"group_delete" => "page=group_delete&group_id=#group_id#",
	"group_features" => "page=group_features&group_id=#group_id#",
	"group_subscribe" => "page=group_subscribe&group_id=#group_id#",
	"group_list" => "page=group_list",
	"group_search" => "page=group_search",
	"group_search_subject" => "page=group_search_subject&subject_id=#subject_id#",
	"user_leave_group" => "page=user_leave_group&group_id=#group_id#",
	"group_request_user" => "page=group_request_user&group_id=#group_id#&user_id=#user_id#",
	"user_request_group" => "page=user_request_group&group_id=#group_id#",
	"group_request_search" => "page=group_request_search&group_id=#group_id#",

	"group_photo" => "page=group_photo&group_id=#group_id#",
	"group_photo_gallery" => "page=group_photo_gallery&group_id=#group_id#",
	"group_photo_gallery_edit" => "page=group_photo_gallery&group_id=#group_id#&action=#action#",
	"group_photo_galleries" => "page=group_photo_galleries&group_id=#group_id#",
	"group_photo_section" => "page=group_photo_section&group_id=#group_id#&section_id=#section_id#",
	"group_photo_section_edit" => "page=group_photo_section_edit&group_id=#group_id#&section_id=#section_id#&action=#action#",
	"group_photo_section_edit_icon" => "page=group_photo_section_edit_icon&group_id=#group_id#&section_id=#section_id#&action=#action#",
	"group_photo_element_upload" => "page=group_photo_element_upload&group_id=#group_id#&section_id=#section_id#",
	"group_photo_element" => "page=group_photo_element&group_id=#group_id#&section_id=#section_id#&element_id=#element_id#",
	"group_photo_element_edit" => "page=group_photo_element_edit&group_id=#group_id#&section_id=#section_id#&element_id=#element_id#&action=#action#",
	"group_photo_element_slide_show" => "page=group_photo_element_slide_show&group_id=#group_id#&section_id=#section_id#&element_id=#element_id#",
	"group_photofull_gallery" => "page=group_photofull_gallery&group_id=#group_id#&user_alias=#user_alias#",
	"group_photofull_gallery_edit" => "page=group_photofull_gallery_edit&group_id=#group_id#&user_alias=#user_alias#&action=#action#",
	"group_photofull_section" => "page=group_photofull_section&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#",
	"group_photofull_section_edit" => "page=group_photofull_section_edit&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#&action=#action#",
	"group_photofull_section_edit_icon" => "page=group_photofull_section_edit_icon&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#&action=#action#",
	"group_photofull_element_upload" => "page=group_photofull_element_upload&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#",
	"group_photofull_element" => "page=group_photofull_element&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#&element_id=#element_id#",
	"group_photofull_element_edit" => "page=group_photofull_element_edit&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#&element_id=#element_id#&action=#action#",
	"group_photofull_element_slide_show" => "page=group_photofull_element_slide_show&group_id=#group_id#&user_alias=#user_alias#&section_id=#section_id#&element_id=#element_id#",

	"group_calendar" => "page=group_calendar&group_id=#group_id#",

	"message_to_group" => "page=message_to_group&group_id=#group_id#",

	"group_files" => "page=group_files&group_id=#group_id#&path=#path#",
	"group_files_short" => "page=group_files_short&group_id=#group_id#&section_id=#section_id#&element_id=#element_id#&element_name=#element_name#",
	"group_files_section_edit" => "page=group_files_section_edit&group_id=#group_id#&section_id=#section_id#&action=#action#",
	"group_files_element" => "page=group_files_element&group_id=#group_id#&element_id=#element_id#",
	"group_files_element_comment" => "page=group_files_element_comment&group_id=#group_id#&topic_id=#topic_id#&message_id=#message_id#",
	"group_files_element_edit" => "page=group_files_element_edit&group_id=#group_id#&element_id=#element_id#&action=#action#",
	"group_files_element_file" => "",
	"group_files_element_history" => "page=group_files_element_history&element_id=#element_id#",
	"group_files_element_history_get" => "page=group_files_element_history_get&element_id=#element_id#&element_name=#element_name#",
	"group_files_element_version" => "page=group_files_element_version&group_id=#group_id#&element_id=#element_id#&action=#action#",
	"group_files_element_versions" => "page=group_files_element_versions&group_id=#group_id#&element_id=#element_id#",
	"group_files_element_upload" => "page=group_files_element_upload&group_id=#group_id#&section_id=#section_id#",
	"group_files_help" => "page=group_files_help&group_id=#group_id#",
	"group_files_connector" => "page=group_files_connector&group_id=#group_id#",
	"group_files_webdav_bizproc_history" => "page=group_files_webdav_bizproc_history&group_id=#group_id#&element_id=#element_id#",
	"group_files_webdav_bizproc_history_get" => "page=group_files_webdav_bizproc_history_get&group_id=#group_id#&element_id=#element_id#&element_name=#element_name#",
	"group_files_webdav_bizproc_log" => "page=group_files_webdav_bizproc_log&group_id=#group_id#&element_id=#element_id#&id=#id#",
	"group_files_webdav_bizproc_view" => "page=group_files_webdav_bizproc_view&group_id=#group_id#&element_id=#element_id#",
	"group_files_webdav_bizproc_workflow_admin" => "page=group_files_webdav_bizproc_workflow_admin&group_id=#group_id#",
	"group_files_webdav_bizproc_workflow_edit" => "page=group_files_webdav_bizproc_workflow_edit&group_id=#group_id#&id=#id#",
	"group_files_webdav_start_bizproc" => "page=group_files_webdav_start_bizproc&group_id=#group_id#&element_id=#element_id#",
	"group_files_webdav_task_list" => "page=group_files_webdav_task_list&group_id=#group_id#",
	"group_files_webdav_task" => "page=group_files_webdav_task&group_id=#group_id#&id=#id#",

	"group_blog" => "page=group_blog&group_id=#group_id#",
	"group_blog_post_edit" => "page=group_blog_post_edit&group_id=#group_id#&post_id=#post_id#",
	"group_blog_rss" => "page=group_blog_rss&group_id=#group_id#&type=#type#",
	"group_blog_post_rss" => "page=group_blog_post_rss&group_id=#group_id#&type=#type#&post_id=#post_id#",
	"group_blog_draft" => "page=group_blog_draft&group_id=#group_id#",
	"group_blog_moderation" => "page=group_blog_moderation&group_id=#group_id#",
	"group_blog_post" => "page=group_blog_post&group_id=#group_id#&post_id=#post_id#",

	"group_forum" => "page=group_forum&group_id=#group_id#",
	"group_forum_topic" => "page=group_forum_topic&group_id=#group_id#&topic_id=#topic_id#",
	"group_forum_topic_edit" => "page=group_forum_topic_edit&group_id=#group_id#&topic_id=#topic_id#",
	"group_forum_message" => "page=group_forum_message&group_id=#group_id#&topic_id=#topic_id#&message_id=#message_id#",
	"group_forum_message_edit" => "page=group_forum_message_edit&group_id=#group_id#&topic_id=#topic_id#&message_id=#message_id#&action=#action#",

	"group_tasks" => "page=group_tasks&group_id=#group_id#",
	"group_tasks_task" => "page=group_tasks_task&group_id=#group_id#&action=#action#&task_id=#task_id#",
	"group_tasks_view" => "page=group_tasks_view&group_id=#group_id#&action=#action#&view_id=#view_id#",
	"group_tasks_report" => "page=group_tasks_report&group_id=#group_id#",
	"group_tasks_report_construct" => "page=group_tasks_report_construct&group_id=#group_id#&action=#action#&report_id=#report_id#",
	"group_tasks_report_view" => "page=group_tasks_report_view&group_id=#group_id#&report_id=#report_id#",

	"group_log" => "page=group_log&group_id=#group_id#",
	"group_log_rss" => "page=group_log_rss&group_id=#group_id#&bx_hit_hash=#sign#&events=#events#",
//	"group_log_rss_mask" => "page=group_log_rss&group_id=#group_id#"
);

$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();
$componentPage = "";
$arComponentVariables = array("user_id", "group_id", "page", "message_id", "subject_id", "path", "section_id", "element_id", "action", "post_id", "category", "topic_id", "task_id", "view_id", "type", "report_id");

if (
	$_REQUEST["auth"]=="Y" 
	&& $USER->IsAuthorized()
)
{
	LocalRedirect($APPLICATION->GetCurPageParam("", array("login", "logout", "register", "forgot_password", "change_password", "backurl", "auth")));
}

if (!array_key_exists("SET_NAV_CHAIN", $arParams))
{
	$arParams["SET_NAV_CHAIN"] = $arParams["SET_NAVCHAIN"];
}
$arParams["SET_NAV_CHAIN"] = ($arParams["SET_NAV_CHAIN"] == "N" ? "N" : "Y");
$arParams["HIDE_OWNER_IN_TITLE"] = ($arParams["HIDE_OWNER_IN_TITLE"] == "Y" ? "Y" : "N");

if (!array_key_exists("PATH_TO_USER_CALENDAR", $arParams))
{
	$arParams["PATH_TO_USER_CALENDAR"] = SITE_DIR."company/personal/user/#user_id#/calendar/";
}

if (!array_key_exists("PATH_TO_USER_LOG", $arParams))
{
	$arParams["PATH_TO_USER_LOG"] = $arParams["~PATH_TO_USER_LOG"] = SITE_DIR."company/personal/log/";
}

if (!array_key_exists("PATH_TO_USER_LOG_ENTRY", $arParams))
{
	$arParams["PATH_TO_USER_LOG_ENTRY"] = $arParams["~PATH_TO_USER_LOG_ENTRY"] = COption::GetOptionString("socialnetwork", "log_entry_page", false, SITE_ID);
	if (empty($arParams["PATH_TO_USER_LOG_ENTRY"]))
	{
		$arParams["PATH_TO_USER_LOG_ENTRY"] = $arParams["~PATH_TO_USER_LOG_ENTRY"] = SITE_DIR."company/personal/log/#log_id#/";
	}
}
	
if (!is_array($arParams["VARIABLE_ALIASES"]))
{
	$arParams["VARIABLE_ALIASES"] = array();
}

if ($arParams["USE_KEYWORDS"] != "N") $arParams["USE_KEYWORDS"] = "Y";
// for bitrix:main.user.link
if (IsModuleInstalled('intranet'))
{
	$arTooltipFieldsDefault	= serialize(array(
		"EMAIL",
		"PERSONAL_MOBILE",
		"WORK_PHONE",
		"PERSONAL_ICQ",
		"PERSONAL_PHOTO",
		"PERSONAL_CITY",
		"WORK_COMPANY",
		"WORK_POSITION",
	));
	$arTooltipPropertiesDefault = serialize(array(
		"UF_DEPARTMENT",
		"UF_PHONE_INNER",
	));
}
else
{
	$arTooltipFieldsDefault = serialize(array(
		"PERSONAL_ICQ",
		"PERSONAL_BIRTHDAY",
		"PERSONAL_PHOTO",
		"PERSONAL_CITY",
		"WORK_COMPANY",
		"WORK_POSITION"
	));
	$arTooltipPropertiesDefault = serialize(array());
}

if (!array_key_exists("SHOW_FIELDS_TOOLTIP", $arParams))
{
	$arParams["SHOW_FIELDS_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_fields", $arTooltipFieldsDefault));
}

if (!array_key_exists("USER_PROPERTY_TOOLTIP", $arParams))
{
	$arParams["USER_PROPERTY_TOOLTIP"] = unserialize(COption::GetOptionString("socialnetwork", "tooltip_properties", $arTooltipPropertiesDefault));
}

if (
	IsModuleInstalled('intranet')
	&& !array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams)
)
{
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
}

if (
	IsModuleInstalled("search")
	&& !array_key_exists("PATH_TO_SEARCH_TAG", $arParams)
)
{
	$arParams["PATH_TO_SEARCH_TAG"] = SITE_DIR."search/?tags=#tag#";
}

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
{
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
}

$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

$arParams['CAN_OWNER_EDIT_DESKTOP'] = (
	IsModuleInstalled("intranet")
		? ($arParams['CAN_OWNER_EDIT_DESKTOP'] != "Y" ? "N" : "Y")
		: ($arParams['CAN_OWNER_EDIT_DESKTOP'] != "N" ? "Y" : "N")
);

if (intval(trim($arParams["SEARCH_PAGE_RESULT_COUNT"])) <= 0)
	$arParams["SEARCH_PAGE_RESULT_COUNT"] = "10";
if (strlen(trim($arParams["SEARCH_DEFAULT_SORT"])) <= 0)
	$arParams["SEARCH_DEFAULT_SORT"] = "rank";
if (strlen(trim($arParams["SEARCH_TAGS_PAGE_ELEMENTS"])) <= 0)
	$arParams["SEARCH_TAGS_PAGE_ELEMENTS"] = 100;
if (intval(trim($arParams["SEARCH_TAGS_PERIOD"])) <= 0)
	$arParams["SEARCH_TAGS_PERIOD"] = "";
if (intval(trim($arParams["SEARCH_TAGS_FONT_MAX"])) <= 0)
	$arParams["SEARCH_TAGS_FONT_MAX"] = "50";
if (intval(trim($arParams["SEARCH_TAGS_FONT_MIN"])) <= 0)
	$arParams["SEARCH_TAGS_FONT_MIN"] = "10";
if (strlen(trim($arParams["SEARCH_TAGS_COLOR_NEW"])) <= 0)
	$arParams["SEARCH_TAGS_COLOR_NEW"] = "3E74E6";
if (strlen(trim($arParams["SEARCH_TAGS_COLOR_OLD"])) <= 0)
	$arParams["SEARCH_TAGS_COLOR_OLD"] = "C0C0C0";

if (IsModuleInstalled("blog"))
{
	if (!array_key_exists("BLOG_ALLOW_POST_CODE", $arParams))
		$arParams["BLOG_ALLOW_POST_CODE"] = "Y";
}

$arParams["USE_MAIN_MENU"] = (isset($arParams["USE_MAIN_MENU"]) && $arParams["USE_MAIN_MENU"] == "Y" ? $arParams["USE_MAIN_MENU"] : false);

if ($arParams["USE_MAIN_MENU"] == "Y" && !array_key_exists("MAIN_MENU_TYPE", $arParams))
	$arParams["MAIN_MENU_TYPE"] = "left";

$arParams["LOG_SUBSCRIBE_ONLY"] = (isset($arParams["LOG_SUBSCRIBE_ONLY"]) && $arParams["LOG_SUBSCRIBE_ONLY"] == "Y" ? "Y" : "N");

$arParams["LOG_RSS_TTL"] = (isset($arParams["LOG_RSS_TTL"]) && intval($arParams["LOG_RSS_TTL"]) > 0 ? $arParams["LOG_RSS_TTL"] : "60");

$arParams["GROUP_USE_BAN"] = $arParams["GROUP_USE_BAN"] != "N" ? "Y" : "N";

$arParams["ALLOW_RATING_SORT"] = $arParams["ALLOW_RATING_SORT"] != "Y" ? "N" : "Y";

// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
if (IsModuleInstalled("search"))
{
	if (!array_key_exists("SEARCH_FILTER_NAME", $arParams))
		$arParams["SEARCH_FILTER_NAME"] = "sonet_search_filter";
	if (!array_key_exists("SEARCH_FILTER_DATE_NAME", $arParams))
		$arParams["SEARCH_FILTER_DATE_NAME"] = "sonet_search_filter_date";
}

$arCustomPagesPath = array();

if ($arParams["SEF_MODE"] == "Y")
{
	$arVariables = array();

	$events = GetModuleEvents("socialnetwork", "OnParseSocNetComponentPath");
	while ($arEvent = $events->Fetch())
		ExecuteModuleEventEx($arEvent, array(&$arDefaultUrlTemplates404, &$arCustomPagesPath, $arParams));

	$engine = new CComponentEngine($this);
	if($diskEnabled)
	{
		$engine->addGreedyPart("#PATH#");
		$engine->addGreedyPart("#FILE_PATH#");
		$engine->addGreedyPart("#TRASH_PATH#");
		$engine->addGreedyPart("#TRASH_FILE_PATH#");
		$engine->setResolveCallback(array(\Bitrix\Disk\Driver::getInstance()->getUrlManager(), "resolveSocNetPathComponentEngine"));
	}

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);

	/* This code is needed to use short paths in WebDAV */
	$arUrlTemplates["group_files_short"] = str_replace("#path#", $arDefaultUrlTemplates404["group_files_short"], $arUrlTemplates["group_files"]);
	/* / This code is needed to use short paths in WebDAV */

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = $engine->guessComponentPath(
		$arParams["SEF_FOLDER"],
		$arUrlTemplates,
		$arVariables
	);

//	$componentPage = CComponentEngine::ParseComponentPath($arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		//if (strlen($componentPage) <= 0)
		$componentPage = "index";
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
		$arResult["PATH_TO_".strToUpper($url)] = $arParams["SEF_FOLDER"].$value;

	if ($_REQUEST["auth"] == "Y")
		$componentPage = "auth";

	if(!isset($arParams["PATH_TO_MESSAGES_CHAT"]))
		$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
	if(!isset($arParams["PATH_TO_MESSAGE_FORM_MESS"]))
		$arParams["PATH_TO_MESSAGE_FORM_MESS"] = "/company/personal/messages/form/#user_id#/#message_id#/";

	if(!isset($arParams["PATH_TO_USER_TASKS_TEMPLATES"]))
	{
		$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = "/company/personal/user/#user_id#/tasks/templates/";
	}

	if(!isset($arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"]))
	{
		$arParams["PATH_TO_USER_TEMPLATES_TEMPLATE"] = "/company/personal/user/#user_id#/tasks/templates/template/#action#/#template_id#/";
	}

	if(!isset($arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"]))
		$arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"] = "/company/personal/user/#user_id#/blog/important/";

	if (IsModuleInstalled("video"))
		if(!isset($arParams["PATH_TO_VIDEO_CALL"]))
			$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#user_id#/";

	$tmpVal = COption::GetOptionString("socialnetwork", "workgroups_page", false, SITE_ID);
	if (
		$arParams["SEF_FOLDER"] 
		&& (
			!$tmpVal
			|| substr($tmpVal, 0, strlen($arParams["SEF_FOLDER"])) !== $arParams["SEF_FOLDER"]
		)
	)
		COption::SetOptionString("socialnetwork", "workgroups_page", $arParams["SEF_FOLDER"], false, SITE_ID);

	$tmpVal = COption::GetOptionString("socialnetwork", "workgroups_list_page", false, SITE_ID);
	if (
		$arParams["SEF_FOLDER"] 
		&& (
			!$tmpVal
			|| substr($tmpVal, 0, strlen($arParams["SEF_FOLDER"])) !== $arParams["SEF_FOLDER"]
		)
	)
	{
		COption::SetOptionString("socialnetwork", "workgroups_list_page", $arResult["PATH_TO_GROUP_SEARCH"], false, SITE_ID);
	}

	$tmpVal = COption::GetOptionString("socialnetwork", "subject_path_template", false, SITE_ID);
	if (
		$arParams["SEF_FOLDER"]
		&& (
			!$tmpVal
			|| substr($tmpVal, 0, strlen($arParams["SEF_FOLDER"])) !== $arParams["SEF_FOLDER"]
		)
	)
	{
		COption::SetOptionString("socialnetwork", "subject_path_template", $arResult["PATH_TO_GROUP_SEARCH_SUBJECT"], false, SITE_ID);
	}
}
else
{
	if(is_array($arParams["VARIABLE_ALIASES"]))
		foreach ($arParams["VARIABLE_ALIASES"] as $key => $val)
			$arParams["VARIABLE_ALIASES"][$key] = (!empty($val) ? $val : $key);

	$events = GetModuleEvents("socialnetwork", "OnParseSocNetComponentPath");
	while ($arEvent = $events->Fetch())
		ExecuteModuleEventEx($arEvent, array(&$arDefaultUrlTemplatesN404, &$arCustomPagesPath, $arParams));

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);

	$events = GetModuleEvents("socialnetwork", "OnInitSocNetComponentVariables");
	while ($arEvent = $events->Fetch())
		ExecuteModuleEventEx($arEvent, array(&$arVariableAliases, &$arCustomPagesPath));

	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);
	if (!empty($arDefaultUrlTemplatesN404) && !empty($arParams["VARIABLE_ALIASES"]))
	{
		foreach ($arDefaultUrlTemplatesN404 as $url => $value)
		{
			$pattern = array();
			$replace = array();
			foreach ($arParams["VARIABLE_ALIASES"] as $key => $res)
			{
				if ($key != $res && !empty($res))
				{
					$pattern[] = preg_quote("/(^|([&?]+))".$key."\=/is");
					$replace[] = "$1".$res."=";
				}
			}
			if (!empty($pattern))
			{
				$value = preg_replace($pattern, $replace, $value);
				$arDefaultUrlTemplatesN404[$url] = $value;
			}
		}
	}
	foreach ($arDefaultUrlTemplatesN404 as $url => $value)
	{
		$arParamsKill = array("page", "path",
				"section_id", "element_id", "action", "user_id", "group_id", "action", "use_light_view", "AJAX_CALL", "MUL_MODE",
				"edit_section", "sessid", "post_id", "category", "topic_id", "result", "MESSAGE_TYPE", "q", "how", "tags", "where");
		$arParamsKill = array_merge($arParamsKill, $arParams["VARIABLE_ALIASES"], array_values($arVariableAliases));
		$arResult["PATH_TO_".strToUpper($url)] = $GLOBALS["APPLICATION"]->GetCurPageParam($value, $arParamsKill);
	}

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplatesN404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplatesN404)))
	{
		//if (strlen($componentPage) <= 0)
		$componentPage = "index";
	}
	if ($_REQUEST["auth"] == "Y")
		$componentPage = "auth";

	$arResult["PATH_TO_GROUP_LOG_RSS_MASK"] = $arResult["~PATH_TO_GROUP_LOG_RSS_MASK"] = $APPLICATION->GetCurPage(true)."?page=group_log_rss&group_id=".$arVariables["group_id"];
}

if (strlen($arParams["PATH_TO_USER_BLOG_POST"]) <= 0)
{
	$arParams["PATH_TO_USER_BLOG_POST"] = COption::GetOptionString("socialnetwork", "userblogpost_page", false, SITE_ID);
}

if ($arRedirectSite)
{
	if ($arParams["SEF_MODE"] == "Y")
	{
		$url =
			(
				strlen(trim($arRedirectSite["SERVER_NAME"])) > 0
				&& $arRedirectSite["SERVER_NAME"] != SITE_SERVER_NAME
					? (CMain::IsHTTPS() ? "https" : "http")."://".$arRedirectSite["SERVER_NAME"]
					: ''
			).
			COption::GetOptionString("socialnetwork", "workgroups_page", false, $arRedirectSite["LID"]).
			CComponentEngine::MakePathFromTemplate(
				$arDefaultUrlTemplates404[$componentPage],
				$arVariables
			);

		LocalRedirect($url);
	}
}

$arResult = array_merge(
	array(
		"SEF_MODE" => $arParams["SEF_MODE"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"VARIABLES" => $arVariables,
		"ALIASES" => $arParams["SEF_MODE"] == "Y"? array(): $arVariableAliases,
		"SET_TITLE" => $arParams["SET_TITLE"],
		"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_TIME_LONG" => $arParams["CACHE_TIME_LONG"],
		"SET_NAV_CHAIN" => $arParams["SET_NAV_CHAIN"],
		"SET_NAVCHAIN" => $arParams["SET_NAV_CHAIN"],
		"ITEM_DETAIL_COUNT" => $arParams["ITEM_DETAIL_COUNT"],
		"ITEM_MAIN_COUNT" => $arParams["ITEM_MAIN_COUNT"],
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"USER_PROPERTY_MAIN" => $arParams["USER_PROPERTY_MAIN"],
		"USER_PROPERTY_CONTACT" => $arParams["USER_PROPERTY_CONTACT"],
		"USER_PROPERTY_PERSONAL" => $arParams["USER_PROPERTY_PERSONAL"],
		"GROUP_PROPERTY" => $arParams["GROUP_PROPERTY"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	$arResult
);

$arParams["PATH_TO_SEARCH_EXTERNAL"] = Trim($arParams["PATH_TO_SEARCH_EXTERNAL"]);
if (StrLen($arParams["PATH_TO_SEARCH_EXTERNAL"]) > 0)
	$arResult["PATH_TO_SEARCH"] = $arParams["PATH_TO_SEARCH_EXTERNAL"];

$arParams["PATH_TO_USER_CALENDAR"] = trim($arParams["PATH_TO_USER_CALENDAR"]);
if (strlen($arParams["PATH_TO_USER_CALENDAR"]) > 0)
	$arResult["PATH_TO_USER_CALENDAR"] = $arParams["PATH_TO_USER_CALENDAR"];

if (!empty($arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"]))
	$arResult["PATH_TO_USER_BLOG_POST_IMPORTANT"] = $arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"];

$arParams["ERROR_MESSAGE"] = "";
$arParams["NOTE_MESSAGE"] = "";
/********************************************************************
				WebDav
********************************************************************/
//detect VARIABLES
if (strPos($componentPage, "user_files") === false && strPos($componentPage, "group_files") === false)
{
	$sCurrUrl = strToLower(str_replace("//", "/", "/".$APPLICATION->GetCurPage()."/"));
	$arBaseUrl = array(
		"user" => $arParams["FILES_USER_BASE_URL"],
		"group" => $arParams["FILES_GROUP_BASE_URL"]);

	if ($arParams["SEF_MODE"] == "Y" )
	{
		$arBaseUrl = array(
			"user" => $arResult["PATH_TO_USER_FILES"],
			"group" => $arResult["PATH_TO_GROUP_FILES"]);
	}
	foreach ($arBaseUrl as $key => $res)
	{
		if (strPos($res, "#path#") !== false)
			$res = subStr($res, 0, strPos($res, "#path#"));
		$res = strToLower(str_replace("//", "/", "/".$res."/"));
		$pos = strPos($res, "#".$key."_id#");
		if ($pos !== false && subStr($res, 0, $pos) == subStr($sCurrUrl, 0, $pos))
		{
			$v1 = subStr($res, $pos + strLen("#".$key."_id#"));
			$v2 = subStr($sCurrUrl, $pos);
			$v3 = subStr($v2, strPos($v2, subStr($v1, 0, 1)), strLen($v1));
			if ($v1 == $v3)
			{
				$componentPage = $key."_files";
				$arResult["VARIABLES"]["#".$key."_id#"] = intVal(subStr($v2, 0, strPos($v2, subStr($v1, 0, 1))));
				$arResult["VARIABLES"][$key."_id"] = intVal(subStr($v2, 0, strPos($v2, subStr($v1, 0, 1))));
			}
		}
	}
}
/********************************************************************
				/WebDav
********************************************************************/
/********************************************************************
				Search Index
********************************************************************/
if(check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] == "PUT")
{
	global $bxSocNetSearch;
	if(!is_object($bxSocNetSearch))
	{
		if (CModule::IncludeModule('tasks'))
		{
			$tasksForumId = intval(CTasksTools::getForumIdForIntranet());
		}

		$arSocNetSearchParams = array(
			"PATH_TO_GROUP" => $arResult["PATH_TO_GROUP"],

			"BLOG_GROUP_ID" => $arParams["BLOG_GROUP_ID"],
			"PATH_TO_GROUP_BLOG" => $arResult["PATH_TO_GROUP_BLOG"],
			"PATH_TO_GROUP_BLOG_POST" => $arResult["PATH_TO_GROUP_BLOG_POST"],
			"PATH_TO_GROUP_BLOG_COMMENT" => $arResult["PATH_TO_GROUP_BLOG_POST"]."?commentId=#comment_id###comment_id#",
			"PATH_TO_USER_BLOG" => "",
			"PATH_TO_USER_BLOG_POST" => "",
			"PATH_TO_USER_BLOG_COMMENT" => "",

			"FORUM_ID" => $arParams["FORUM_ID"],
			"PATH_TO_GROUP_FORUM_MESSAGE" => $arResult["PATH_TO_GROUP_FORUM_MESSAGE"],
			"PATH_TO_USER_FORUM_MESSAGE" => "",

			"PHOTO_GROUP_IBLOCK_ID" => $arParams["PHOTO_GROUP_IBLOCK_ID"],
			"PATH_TO_GROUP_PHOTO_ELEMENT" => $arResult["PATH_TO_GROUP_PHOTO_ELEMENT"],
			"PHOTO_USER_IBLOCK_ID" => false,
			"PATH_TO_USER_PHOTO_ELEMENT" => "",
			"PHOTO_FORUM_ID" => $arParams["PHOTO_FORUM_ID"],

			"CALENDAR_GROUP_IBLOCK_ID" => $arParams["CALENDAR_GROUP_IBLOCK_ID"],
			"PATH_TO_GROUP_CALENDAR_ELEMENT" => $arResult["PATH_TO_GROUP_CALENDAR"]."?EVENT_ID=#element_id#",

			"TASK_IBLOCK_ID" => $arParams["TASK_IBLOCK_ID"],
			"PATH_TO_GROUP_TASK_ELEMENT" => $arResult["PATH_TO_GROUP_TASKS_TASK"],
			"PATH_TO_USER_TASK_ELEMENT" => "",
			"TASK_FORUM_ID" => ($tasksForumId > 0 ? $tasksForumId : $arParams["TASK_FORUM_ID"]),

			"FILES_PROPERTY_CODE" => $arParams["NAME_FILE_PROPERTY"],
			"FILES_FORUM_ID" => $arParams["FILES_FORUM_ID"],
			"FILES_GROUP_IBLOCK_ID" => $arParams["FILES_GROUP_IBLOCK_ID"],
			"PATH_TO_GROUP_FILES_ELEMENT" => $arResult["PATH_TO_GROUP_FILES_ELEMENT"],
			"PATH_TO_GROUP_FILES" => $arResult["PATH_TO_GROUP_FILES"],
			"FILES_USER_IBLOCK_ID" => false,
			"PATH_TO_USER_FILES_ELEMENT" => "",
			"PATH_TO_USER_FILES" => "",
		);

		if (isset($arResult["PATH_TO_GROUP_WIKI_POST_COMMENT"]))
		{
			$arSocNetSearchParams["PATH_TO_GROUP_WIKI_POST_COMMENT"] = $arResult["PATH_TO_GROUP_WIKI_POST_COMMENT"];
		}

		$bxSocNetSearch = new CSocNetSearch(
			$arResult["VARIABLES"]["user_id"], 
			$arResult["VARIABLES"]["group_id"],
			$arSocNetSearchParams
		);

		AddEventHandler("search", "BeforeIndex", Array($bxSocNetSearch, "BeforeIndex"));
		AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array($bxSocNetSearch, "IBlockElementUpdate"));
		AddEventHandler("iblock", "OnAfterIBlockElementAdd", Array($bxSocNetSearch, "IBlockElementUpdate"));
		AddEventHandler("iblock", "OnAfterIBlockElementDelete", Array($bxSocNetSearch, "IBlockElementDelete"));
		AddEventHandler("iblock", "OnAfterIBlockSectionUpdate", Array($bxSocNetSearch, "IBlockSectionUpdate"));
		AddEventHandler("iblock", "OnAfterIBlockSectionAdd", Array($bxSocNetSearch, "IBlockSectionUpdate"));
		AddEventHandler("iblock", "OnAfterIBlockSectionDelete", Array($bxSocNetSearch, "IBlockSectionDelete"));
	}
}
/********************************************************************
				Bizproc
********************************************************************/
if (IsModuleInstalled("bizproc"))
{
	$arDefaultUrlTemplates404["bizproc_task_list"] = $arResult["PATH_TO_BIZPROC_TASK_LIST"] = (empty($arParams["PATH_TO_BIZPROC_TASK_LIST"]) ?
		"/company/personal/user/#user_id#/bizproc/" :
		$arParams["PATH_TO_BIZPROC_TASK_LIST"]);

	$arResult["PATH_TO_BIZPROC_TASK"] = (
		empty($arParams["PATH_TO_BIZPROC_TASK"])
		? (
			empty($arParams['PATH_TO_USER'])
			? "/company/personal/user/#user_id#/"
			: $arParams['PATH_TO_USER']
		) . "bizproc/#id#/"
		: $arParams["PATH_TO_BIZPROC_TASK"]
	);
	$arDefaultUrlTemplates404["bizproc_task"] = $arResult["PATH_TO_BIZPROC_TASK"] = str_replace("#task_id#", "#id#", $arResult["PATH_TO_BIZPROC_TASK"]);
}
/********************************************************************
				Bizproc
********************************************************************/
/********************************************************************
				Disk
********************************************************************/
if(strpos($componentPage, 'group_disk') !== false)
{
	if(!CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $arResult["VARIABLES"]["group_id"], "files"))
	{
		ShowError(GetMessage("SONET_FILES_IS_NOT_ACTIVE"));
		return 0;
	}
}
/********************************************************************
				Disk
********************************************************************/
/********************************************************************
				WebDav
********************************************************************/
if (
	strPos($componentPage, "group_files") !== false 
	|| strPos($componentPage, "group_blog") !== false
	|| strPos($componentPage, "group_log") !== false
	|| $componentPage == "group"
)
{
	if (intval($arResult["VARIABLES"]["group_id"]) > 0)
	{
		$cache_time = 31536000;
		$arEvent = array();

		$cache = new CPHPCache;
		$cache_id = "files_iblock_id";
		$cache_path = "/sonet/group_comp/".$arResult["VARIABLES"]["group_id"]."/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arParams["FILES_GROUP_IBLOCK_ID"] = $arCacheVars["FILES_GROUP_IBLOCK_ID"];
		}
		elseif (CModule::IncludeModule("iblock"))
		{
			if (is_object($cache))
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			$arFilesIBlockID = array();
			$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "CHECK_PERMISSIONS"=>"N", "CODE"=>"group_files%"));
			while($arIBlock = $rsIBlock->Fetch())
				$arFilesIBlockID[] = $arIBlock["ID"];

			if (count($arFilesIBlockID) > 0)
			{
				$rsFilesSection = CIBlockSection::GetList(
					array("timestamp_x"=>"desc"),
					array(
						"IBLOCK_ID" => $arFilesIBlockID,
						"SOCNET_GROUP_ID" => $arResult["VARIABLES"]["group_id"]
					)
				);
				if ($arFilesSection = $rsFilesSection->Fetch())
					$arParams["FILES_GROUP_IBLOCK_ID"] = $arFilesSection["IBLOCK_ID"];
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"FILES_GROUP_IBLOCK_ID" => $arParams["FILES_GROUP_IBLOCK_ID"]
				);
				$cache->EndDataCache($arCacheData);
			}
		}
	}

	if (
		intval($arResult["VARIABLES"]["group_id"]) > 0
		&& intval($arResult["VARIABLES"]["element_id"]) > 0
		&& intval($arParams["FILES_FORUM_ID"]) > 0
		&& intval($arParams["FILES_GROUP_IBLOCK_ID"]) > 0
	)
	{
		$cache_time = 31536000;
		$arEvent = array();

		$cache = new CPHPCache;

		$arCacheID = array();
		$arKeys = array(
			"FILES_GROUP_IBLOCK_ID",
		);
		foreach($arKeys as $param_key)
		{
			if (array_key_exists($param_key, $arParams))
				$arCacheID[$param_key] = $arParams[$param_key];
			else
				$arCacheID[$param_key] = false;
		}
		$cache_id = "files_forum_id_".md5(serialize($arCacheID));
		$cache_path = "/sonet/group_comp/".$arResult["VARIABLES"]["group_id"]."/".$arResult["VARIABLES"]["element_id"];

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arParams["FILES_FORUM_ID"] = $arCacheVars["FILES_FORUM_ID"];
		}
		elseif (
			CModule::IncludeModule("forum")
			&& CModule::IncludeModule("iblock")
		)
		{
			if (is_object($cache))
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			$rsIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					"IBLOCK_ID" => $arParams["FILES_GROUP_IBLOCK_ID"],
					"ID" => $arResult["VARIABLES"]["element_id"]
				),
				false,
				false,
				array("IBLOCK_ID", "PROPERTY_FORUM_TOPIC_ID")
			);

			if (
				($arIBlockElement = $rsIBlockElement->Fetch())
				&& array_key_exists("PROPERTY_FORUM_TOPIC_ID_VALUE", $arIBlockElement)
				&& intval($arIBlockElement["PROPERTY_FORUM_TOPIC_ID_VALUE"] > 0)
			)
			{
				$arForumTopic = CForumTopic::GetByID($arIBlockElement["PROPERTY_FORUM_TOPIC_ID_VALUE"]);
				$arParams["FILES_FORUM_ID"] = $arForumTopic["FORUM_ID"];
			}
			
			if (is_object($cache))
			{
				$arCacheData = Array(
					"FILES_FORUM_ID" => $arParams["FILES_FORUM_ID"]
				);
				$cache->EndDataCache($arCacheData);
			}
		}
	}
}

$path2 = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/include/webdav_2.php");
if (file_exists($path2))
	include_once($path2);

if (strPos($componentPage, "user_files") !== false || strPos($componentPage, "group_files") !== false)
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/include/webdav.php");
	if (!file_exists($path))
	{
		$arParams["ERROR_MESSAGE"] = "WebDAV file is not exist.";
		$res = 0;
	}
	else
		$res = include_once($path);

	$arParams["FATAL_ERROR"] = ($res <= 0 ? "Y" : "N");

	if ($arParams["FATAL_ERROR"] === "Y")
	{
		if (strlen($arParams["NOTE_MESSAGE"]) > 0)
			ShowNote($arParams["NOTE_MESSAGE"]);
		if (strlen($arParams["ERROR_MESSAGE"]) > 0)
			ShowError($arParams["ERROR_MESSAGE"]);
		return 0;
	}

}
/********************************************************************
				/WebDav
********************************************************************/
/********************************************************************
				Photogalley
********************************************************************/
elseif (strPos($componentPage, "user_photo")!== false || strPos($componentPage, "group_photo")!== false)
{
	if (strPos($componentPage, "user_photofull") !== false || strPos($componentPage, "group_photofull") !== false)
		$componentPage = str_replace("_photofull", "_photo", $componentPage);

	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/include/photogallery.php");
	if (!file_exists($path))
	{
		$arParams["ERROR_MESSAGE"] = "Photogallery file is not exist.";
		$res = 0;
	}
	else
		$res = include_once($path);

	$arParams["FATAL_ERROR"] = ($res <= 0 ? "Y" : "N");

	if (strPos($componentPage, "group_photo") !== false && CModule::IncludeModule('iblock'))
	{
		$arPhotoIBlockID = array();
		$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "CODE"=>"group_photogallery%"));
		while($arIBlock = $rsIBlock->Fetch())
			$arPhotoIBlockID[] = $arIBlock["ID"];

		if (count($arPhotoIBlockID) > 0)
		{
			$rsPhotoSection = CIBlockSection::GetList(
				array("timestamp_x"=>"desc"),
				array(
					"IBLOCK_ID" => $arPhotoIBlockID,
					"SOCNET_GROUP_ID" => $arResult["VARIABLES"]["group_id"]
				)
			);
			if ($arPhotoSection = $rsPhotoSection->Fetch())
			{
				$arParams["PHOTO_GROUP_IBLOCK_ID"] = $arPhotoSection["IBLOCK_ID"];

				if (
					intval($_GET["ELEMENT_ID"]) > 0
					&& intval($arParams["PHOTO"]["ALL"]["FORUM_ID"]) > 0
					&& $arParams["PHOTO"]["ALL"]["COMMENTS_TYPE"] == "FORUM"
					&& CModule::IncludeModule("forum")
				)
				{
					$rsIBlockElement = CIBlockElement::GetList(
						array(),
						array(
							"IBLOCK_ID" => $arParams["PHOTO_GROUP_IBLOCK_ID"],
							"ID" => $_GET["ELEMENT_ID"]
						),
						false,
						false,
						array("IBLOCK_ID", "PROPERTY_FORUM_TOPIC_ID")
					);

					if (
						($arIBlockElement = $rsIBlockElement->Fetch())
						&& array_key_exists("PROPERTY_FORUM_TOPIC_ID_VALUE", $arIBlockElement)
					)
					{
						$arForumTopic = CForumTopic::GetByID($arIBlockElement["PROPERTY_FORUM_TOPIC_ID_VALUE"]);
						$arParams["PHOTO"]["ALL"]["FORUM_ID"] = $arForumTopic["FORUM_ID"];
					}
				}
			}
		}
	}
}
/********************************************************************
				/Photogalley
********************************************************************/
/********************************************************************
				Calendar
********************************************************************/
elseif (strPos($componentPage, "group_calendar") !== false && CModule::IncludeModule("iblock"))
{
	$arCalendarIBlockID = array();
	$rsIBlock = CIBlock::GetList(array(), array("ACTIVE" => "Y", "CODE"=>"calendar_group%"));
	while($arIBlock = $rsIBlock->Fetch())
		$arCalendarIBlockID[] = $arIBlock["ID"];

	if (count($arCalendarIBlockID) > 0)
	{
		$rsCalendarSection = CIBlockSection::GetList(
			array("timestamp_x"=>"desc"),
			array(
				"IBLOCK_ID" => $arCalendarIBlockID,
				"SOCNET_GROUP_ID" => $arResult["VARIABLES"]["group_id"]
			)
		);
		if ($arCalendarSection = $rsCalendarSection->Fetch())
			$arParams["CALENDAR_GROUP_IBLOCK_ID"] = $arCalendarSection["IBLOCK_ID"];
	}

}
/********************************************************************
				/Calendar
********************************************************************/
/********************************************************************
				Forum
********************************************************************/
elseif (strPos($componentPage, "user_forum")!== false || strPos($componentPage, "group_forum")!== false ||
	$componentPage == "user" || $componentPage == "group" || $componentPage == "index")
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/include/forum.php");
	if (!file_exists($path))
	{
		$arParams["ERROR_MESSAGE"] = "Forum file is not exist.";
		$res = 0;
	}
	else
		$res = include_once($path);

	$arParams["FATAL_ERROR"] = ($res <= 0 ? "Y" : "N");
}
/********************************************************************
				/Forum
********************************************************************/
/********************************************************************
				Content Search
********************************************************************/
elseif (strPos($componentPage, "user_content_search")!== false || strPos($componentPage, "group_content_search")!== false)
{
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/include/search.php");
	if (!file_exists($path))
	{
		$arParams["ERROR_MESSAGE"] = "Content search file is not exist.";
		$res = 0;
	}
	else
	{
		$res = include_once($path);
	}
	$arParams["FATAL_ERROR"] = ($res <= 0 ? "Y" : "N");
}
/********************************************************************
				/Content search
********************************************************************/
CUtil::InitJSCore(array("window", "ajax"));

$this->IncludeComponentTemplate($componentPage, array_key_exists($componentPage, $arCustomPagesPath) ? $arCustomPagesPath[$componentPage] : "");

//top panel button to reindex
if($GLOBALS['USER']->IsAdmin())
{
	$GLOBALS['APPLICATION']->AddPanelButton(array(
		"HREF"=> $arResult["PATH_TO_GROUP_REINDEX"],
		"ICON"=>"bx-panel-reindex-icon",
		"ALT"=>GetMessage('SONET_PANEL_REINDEX_TITLE'),
		"TEXT"=>GetMessage('SONET_PANEL_REINDEX'),
		"MAIN_SORT"=>"1000",
		"SORT"=>100
	));
}
?>
