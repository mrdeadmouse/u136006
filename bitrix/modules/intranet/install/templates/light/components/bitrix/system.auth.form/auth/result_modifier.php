<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
	return;

global $CACHE_MANAGER;

$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

$arParams["PATH_TO_MYPORTAL"] = (isset($arParams["PATH_TO_MYPORTAL"]) ? $arParams["PATH_TO_MYPORTAL"] : SITE_DIR."desktop.php");
$arParams["PATH_TO_SONET_PROFILE"] = (isset($arParams["PATH_TO_SONET_PROFILE"]) ? $arParams["PATH_TO_SONET_PROFILE"] : SITE_DIR."company/personal/user/#user_id#/");
$arParams["PATH_TO_SONET_GROUP"] = (isset($arParams["PATH_TO_SONET_GROUP"]) ? $arParams["PATH_TO_SONET_GROUP"] : SITE_DIR."workgroups/group/#group_id#/");
$arParams["PATH_TO_SONET_MESSAGES"] = (isset($arParams["PATH_TO_SONET_MESSAGES"]) ? $arParams["PATH_TO_SONET_MESSAGES"] : SITE_DIR."company/personal/messages/");
$arParams["PATH_TO_SONET_MESSAGE_FORM"] = (isset($arParams["PATH_TO_SONET_MESSAGE_FORM"]) ? $arParams["PATH_TO_SONET_MESSAGE_FORM"] : SITE_DIR."company/personal/messages/form/#user_id#/");
$arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"] = (isset($arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"]) ? $arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"] : SITE_DIR."company/personal/messages/form/#user_id#/#message_id#/");
$arParams["PATH_TO_SONET_MESSAGES_CHAT"] = (isset($arParams["PATH_TO_SONET_MESSAGES_CHAT"]) ? $arParams["PATH_TO_SONET_MESSAGES_CHAT"] : SITE_DIR."company/personal/messages/chat/#user_id#/");
$arParams["PATH_TO_BIZPROC"] = (isset($arParams["PATH_TO_BIZPROC"]) ? $arParams["PATH_TO_BIZPROC"] : SITE_DIR."company/personal/bizproc/");
$arParams["PATH_TO_CALENDAR"] = (isset($arParams["PATH_TO_CALENDAR"]) ? $arParams["PATH_TO_CALENDAR"] : SITE_DIR."company/personal/user/#user_id#/calendar/");
$arParams["PATH_TO_TASKS"] = (isset($arParams["PATH_TO_TASKS"]) ? $arParams["PATH_TO_TASKS"] : SITE_DIR."company/personal/user/#user_id#/tasks/");
$arParams["PATH_TO_PHOTO"] = (isset($arParams["PATH_TO_PHOTO"]) ? $arParams["PATH_TO_PHOTO"] : SITE_DIR."company/personal/user/#user_id#/photo/");
$arParams["PATH_TO_BLOG"] = (isset($arParams["PATH_TO_BLOG"]) ? $arParams["PATH_TO_BLOG"] : SITE_DIR."company/personal/user/#user_id#/blog/");
$arParams["PATH_TO_MICROBLOG"] = (isset($arParams["PATH_TO_MICROBLOG"]) ? $arParams["PATH_TO_MICROBLOG"] : SITE_DIR."company/personal/user/#user_id#/microblog/");

$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
$diskPath = ($diskEnabled == "Y") ? SITE_DIR."company/personal/user/#user_id#/disk/path/" : SITE_DIR."company/personal/user/#user_id#/files/lib/";
$arParams["PATH_TO_FILES"] = (isset($arParams["PATH_TO_FILES"]) ? $arParams["PATH_TO_FILES"] : $diskPath);

$arParams["PATH_TO_SONET_GROUPS"] = (isset($arParams["PATH_TO_SONET_GROUPS"]) ? $arParams["PATH_TO_SONET_GROUPS"] : SITE_DIR."company/personal/user/#user_id#/groups/");
$arParams["PATH_TO_SONET_LOG"] = (isset($arParams["PATH_TO_SONET_LOG"]) ? $arParams["PATH_TO_SONET_LOG"] : SITE_DIR."company/personal/log/");
$arParams["THUMBNAIL_SIZE"] = (isset($arParams["THUMBNAIL_SIZE"]) ? intval($arParams["THUMBNAIL_SIZE"]) : 32);

if (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite())
{
	$arParams["PATH_TO_MYPORTAL"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_MYPORTAL"]);
	$arParams["PATH_TO_SONET_PROFILE"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_PROFILE"]);
	$arParams["PATH_TO_SONET_GROUP"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_GROUP"]);
	$arParams["PATH_TO_SONET_MESSAGES"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_MESSAGES"]);
	$arParams["PATH_TO_SONET_MESSAGE_FORM"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_MESSAGE_FORM"]);
	$arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"]);
	$arParams["PATH_TO_SONET_MESSAGES_CHAT"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_MESSAGES_CHAT"]);
	$arParams["PATH_TO_BIZPROC"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_BIZPROC"]);
	$arParams["PATH_TO_CALENDAR"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_CALENDAR"]);
	$arParams["PATH_TO_TASKS"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_TASKS"]);
	$arParams["PATH_TO_PHOTO"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_PHOTO"]);
	$arParams["PATH_TO_BLOG"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_BLOG"]);
	$arParams["PATH_TO_MICROBLOG"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_MICROBLOG"]);
	$arParams["PATH_TO_FILES"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_FILES"]);
	$arParams["PATH_TO_SONET_GROUPS"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_GROUPS"]);
	$arParams["PATH_TO_SONET_LOG"] = str_replace("/company/", "/contacts/", $arParams["PATH_TO_SONET_LOG"]);
	$arParams["THUMBNAIL_SIZE"] = str_replace("/company/", "/contacts/", $arParams["THUMBNAIL_SIZE"]);
}

$arResult["urlToMyPortal"] = "";
$arResult["urlToOwnProfile"] = "";
$arResult["urlToOwnMessages"] = "";
$arResult["urlToOwnGroups"] = "";
$arResult["urlToOwnLog"] = "";
$arResult["urlToOwnBlog"] = "";
$arResult["urlToOwnMicroBlog"] = "";
$arResult["urlToOwnPhoto"] = "";
$arResult["urlToOwnCalendar"] = "";
$arResult["urlToOwnTasks"] = "";
$arResult["urlToOwnFiles"] = "";
$arResult["urlToOwnBizProc"] = "";

if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
	$arResult["urlToMyPortal"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MYPORTAL"], array("user_id" => $GLOBALS["USER"]->GetID()));

$arUserActiveFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID());

if ($GLOBALS["USER"]->IsAuthorized())
{
	$arResult["urlToOwnProfile"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SONET_PROFILE"], array("user_id" => $GLOBALS["USER"]->GetID()));
	
	if (CBXFeatures::IsFeatureEnabled("WebMessenger"))
		$arResult["urlToOwnMessages"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SONET_MESSAGES"], array("user_id" => $GLOBALS["USER"]->GetID()));

	if (CBXFeatures::IsFeatureEnabled("Workgroups"))
		$arResult["urlToOwnGroups"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SONET_GROUPS"], array("user_id" => $GLOBALS["USER"]->GetID()));
	
	$arResult["urlToOwnLog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SONET_LOG"], array("user_id" => $GLOBALS["USER"]->GetID()));
}

if (
	CModule::IncludeModule("blog") 
	&& $GLOBALS["USER"]->IsAuthorized() 
	&& array_key_exists("blog", $arSocNetFeaturesSettings)
	&& array_key_exists("allowed", $arSocNetFeaturesSettings["blog"])
	&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["blog"]["allowed"])
	&& in_array("blog", $arUserActiveFeatures)	
)
	$arResult["urlToOwnBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $GLOBALS["USER"]->GetID()));

if (
	CModule::IncludeModule("blog") 
	&& $GLOBALS["USER"]->IsAuthorized() 
	&& array_key_exists("microblog", $arSocNetFeaturesSettings)
	&& array_key_exists("allowed", $arSocNetFeaturesSettings["microblog"])
	&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["microblog"]["allowed"])
	&& in_array("microblog", $arUserActiveFeatures)	
)
	$arResult["urlToOwnMicroBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MICROBLOG"], array("user_id" => $GLOBALS["USER"]->GetID()));

if (
	CModule::IncludeModule("photogallery") 
	&& $GLOBALS["USER"]->IsAuthorized() 
	&& array_key_exists("photo", $arSocNetFeaturesSettings)	
	&& array_key_exists("allowed", $arSocNetFeaturesSettings["photo"])
	&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["photo"]["allowed"])
	&& in_array("photo", $arUserActiveFeatures)	
)
	$arResult["urlToOwnPhoto"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PHOTO"], array("user_id" => $GLOBALS["USER"]->GetID()));

if (CModule::IncludeModule("intranet") && $GLOBALS["USER"]->IsAuthorized())
{
	if (
		array_key_exists("calendar", $arSocNetFeaturesSettings)	
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["calendar"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["calendar"]["allowed"])
		&& in_array("calendar", $arUserActiveFeatures)
	)
		$arResult["urlToOwnCalendar"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_CALENDAR"], array("user_id" => $GLOBALS["USER"]->GetID()));
		
	if (
		array_key_exists("tasks", $arSocNetFeaturesSettings)
		&& array_key_exists("allowed", $arSocNetFeaturesSettings["tasks"])
		&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["tasks"]["allowed"])
		&& in_array("tasks", $arUserActiveFeatures)
	)
		$arResult["urlToOwnTasks"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array("user_id" => $GLOBALS["USER"]->GetID()));
}

if (
	(
		$diskEnabled && CModule::IncludeModule("disk")
		|| !$diskEnabled && CModule::IncludeModule("webdav")
	)
	&& $GLOBALS["USER"]->IsAuthorized()
	&& array_key_exists("files", $arSocNetFeaturesSettings)	
	&& array_key_exists("allowed", $arSocNetFeaturesSettings["files"])
	&& in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["files"]["allowed"])
	&& in_array("files", $arUserActiveFeatures)
)
	$arResult["urlToOwnFiles"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_FILES"], array("user_id" => $GLOBALS["USER"]->GetID()));

if (CModule::IncludeModule("bizproc") && $GLOBALS["USER"]->IsAuthorized())
	$arResult["urlToOwnBizProc"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BIZPROC"], array("user_id" => $GLOBALS["USER"]->GetID()));

if ($GLOBALS["USER"]->IsAuthorized())
{
	$cacheTtl = 604800;
	$cacheID = 'bx_cp_user_auth_info_'.$GLOBALS["USER"]->GetID();
	$cacheDir = '/cp_user_ainfo/';
	$obUserCache = new CPHPCache;

	if($obUserCache->InitCache($cacheTtl, $cacheID, $cacheDir))
	{
		$cacheData = $obUserCache->GetVars();
		$arResult['USER_NAME_FORMATTED'] = $cacheData['USER_NAME_FORMATTED'];
		$arResult['USER_PERSONAL_PHOTO_SRC'] = $cacheData['USER_PERSONAL_PHOTO_SRC'];
		unset($cacheData);
	}
	else
	{
		$CACHE_MANAGER->StartTagCache($cacheDir);

		$dbUser = CUser::GetByID($GLOBALS["USER"]->GetID());
		$arUser = $dbUser->Fetch();

	$arResult["USER_NAME_FORMATTED"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true);

		$iSize = $arParams["THUMBNAIL_SIZE"];
		$imageFile = false;
		$imageImg = false;

		$bThumbnailFound = false;

		if (intval($arUser["PERSONAL_PHOTO"]) <= 0 && IsModuleInstalled("socialnetwork"))
		{
			switch ($arUser["PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
			}
			$arUser["PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
		}

		if (intval($arUser["PERSONAL_PHOTO"]) > 0)
		{
			$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $arParams["THUMBNAIL_SIZE"], "height" => $arParams["THUMBNAIL_SIZE"]),
					BX_RESIZE_IMAGE_EXACT,
					false
				);
				$arResult["USER_PERSONAL_PHOTO_SRC"] = $arFileTmp["src"];
			}
		}
		$CACHE_MANAGER->RegisterTag("USER_CARD_".intval($arUser['ID'] / TAGGED_user_card_size));
		$CACHE_MANAGER->EndTagCache();

		if($obUserCache->StartDataCache())
		{
			$obUserCache->EndDataCache(array(
					'USER_NAME_FORMATTED' => $arResult['USER_NAME_FORMATTED'],
					'USER_PERSONAL_PHOTO_SRC' => $arResult['USER_PERSONAL_PHOTO_SRC']
				)
			);
		}
	}

	$arResult["SHOW_BIZPROC"] = false;
	if(CModule::IncludeModule("bizproc") && IsModuleInstalled("socialnetwork"))
	{
		$arResult["SHOW_BIZPROC"] = true;
		$arFilter = array("USER_ID" => $GLOBALS["USER"]->GetID());
		if (class_exists('CBPTaskUserStatus'))
		{
			$arFilter['USER_STATUS'] = CBPTaskUserStatus::Waiting;
		}

		$dbResultList = CBPTaskService::GetList(
			array(),
			$arFilter,
			array("USER_ID"),
			false,
			array("COUNT" => "ID")
		);
								
		$arResult["BZP_CNT"] = 0;
		if ($arResultList = $dbResultList->Fetch())
			$arResult["BZP_CNT"] = intval($arResultList["CNT"]);
	}

	// live updates counter
	$arCounters = CUserCounter::GetValues($GLOBALS["USER"]->GetID(), SITE_ID);
	$arResult["LOG_ITEMS_TOTAL"] = (isset($arCounters["**"]) ? intval($arCounters["**"]) : 0);

	// external mailbox messages counter
	$arResult["urlToExternalMailbox"] = "";
	$arResult["EXTERNAL_MAIL_CNT"] = 0;

	// exchange messages counter
	$arResult["urlToExchangeBox"] = "";
	$arResult["EXCHANGE_CNT"] = 0;

	if (CModule::IncludeModule("intranet") && CIntranetUtils::IsExternalMailAvailable())
	{
		$arResult["urlToExternalMailbox"] = $arParams['PATH_TO_SONET_EXTMAIL'];
		$arResult["urlToMailboxSetup"] = CHTTP::urlAddParams($arParams['PATH_TO_SONET_EXTMAIL'], array('page' => 'home'));
		$arResult["EXTERNAL_MAIL_CNT"] = intval($arCounters["mail_unseen"]);
	}
	else if (CModule::IncludeModule("dav"))
	{
		$ar = CDavExchangeMail::GetTicker($GLOBALS["USER"]);
		if ($ar !== null)
		{
			$arResult["urlToExchangeBox"] = $ar["exchangeMailboxPath"];
			$arResult["EXCHANGE_CNT"] = $ar["numberOfUnreadMessages"];
		}
	}
}
?>