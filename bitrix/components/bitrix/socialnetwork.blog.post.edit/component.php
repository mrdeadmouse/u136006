<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if (!CModule::IncludeModule("blog"))
{
	if ($arParams["IS_REST"] == "Y")
	{
		$APPLICATION->ThrowException(GetMessage("BLOG_MODULE_NOT_INSTALL"), "BLOG_MODULE_NOT_INSTALL");
	}
	else
	{
		ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	}

	return false;
}
$feature = "blog";
$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);
$arResult["bExtranetUser"] = (CModule::IncludeModule("extranet") && !CExtranet::IsIntranetUser());
$arResult["bExtranetSite"] = (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite());

$bCalendar = true;
if (!IsModuleInstalled('intranet')) // Disable calendar feature for non cp
{
	$bCalendar = false;
}

if ($bCalendar && $arResult["bExtranetUser"]) // Disable calendar feature for extranet users
{
	$bCalendar = false;
}

if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
{
	$bCalendar = false;
}

$arParams["B_CALENDAR"] = $bCalendar;

$arResult["bGroupMode"] = false;

if (CModule::IncludeModule("socialnetwork") && (IntVal($arParams["SOCNET_GROUP_ID"]) > 0 || IntVal($arParams["USER_ID"]) > 0))
{
	if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
		$arResult["bGroupMode"] = true;

	if($arResult["bGroupMode"])
	{
		if($arGroupSoNet = CSocNetGroup::GetByID($arParams["SOCNET_GROUP_ID"]))
		{
			if(!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], $feature))
			{
				if ($arParams["IS_REST"] == "Y")
				{
					$APPLICATION->ThrowException(GetMessage("BLOG_SONET_GROUP_MODULE_NOT_AVAIBLE"), "BLOG_FEATURE_NOT_AVAIBLE");
				}
				else
				{
					ShowError(GetMessage("BLOG_SONET_GROUP_MODULE_NOT_AVAIBLE"));
				}

				return false;
			}
		}
		else
		{
			if ($arParams["IS_REST"] == "Y")
				$APPLICATION->ThrowException(GetMessage("B_B_MES_NO_GROUP"), "SONET_GROUP_NOT_FOUND");

			return false;
		}
	}
	/*
	else
	{
		$rsUser = CUser::GetByID($arParams["USER_ID"]);
		if($arUser = $rsUser->Fetch())
		{
			if (!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $arParams["USER_ID"], $feature))
			{
				ShowError(GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE"));
				return;
			}
		}
		else
			return;
	}
	*/
}
$arParams["ID"] = IntVal($arParams["ID"]);
if (!is_array($arParams["GROUP_ID"]))
{
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
}

foreach ($arParams["GROUP_ID"] as $k=>$v)
{
	if (IntVal($v) <= 0)
	{
		unset($arParams["GROUP_ID"][$k]);
	}
}

if (empty($arParams["GROUP_ID"]))
{
	$tmpVal = COption::GetOptionString("socialnetwork", "sonet_blog_group", false, SITE_ID);
	if ($tmpVal)
	{
		$arTmpVal = unserialize($tmpVal);
		if (is_array($arTmpVal))
		{
			$arParams["GROUP_ID"] = $arTmpVal;
		}
		elseif(intval($tmpVal) > 0)
		{
			$arParams["GROUP_ID"] = array($arTmpVal);
		}
	}
}
else
{
	$tmpVal = COption::GetOptionString("socialnetwork", "sonet_blog_group", false, SITE_ID);
	if (!$tmpVal)
	{
		COption::SetOptionString("socialnetwork", "sonet_blog_group", serialize($arParams["GROUP_ID"]), false, SITE_ID);
	}
}

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";

$applicationCurPage = $APPLICATION->GetCurPage();
$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_BLOG"] = CHTTP::urlDeleteParams($arParams["PATH_TO_BLOG"], array("WFILES"));

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_DRAFT"] = trim($arParams["PATH_TO_DRAFT"]);
if(strlen($arParams["PATH_TO_DRAFT"])<=0)
	$arParams["PATH_TO_DRAFT"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=draft&".$arParams["BLOG_VAR"]."=#blog#");
$arParams["PATH_TO_GROUP_BLOG"] = trim($arParams["PATH_TO_GROUP_BLOG"]);
if(strlen($arParams["PATH_TO_GROUP_BLOG"])<=0)
	$arParams["PATH_TO_GROUP_BLOG"] = "/workgroups/group/#group_id#/blog/";
if(strlen($arParams["PATH_TO_GROUP_POST"])<=0)
	$arParams["PATH_TO_GROUP_POST"] = "/workgroups/group/#group_id#/blog/#post_id#/";
if(strlen($arParams["PATH_TO_GROUP_POST_EDIT"])<=0)
	$arParams["PATH_TO_GROUP_POST_EDIT"] = "/workgroups/group/#group_id#/blog/edit/#post_id#/";
if(strlen($arParams["PATH_TO_GROUP_DRAFT"])<=0)
	$arParams["PATH_TO_GROUP_DRAFT"] = "/workgroups/group/#group_id#/blog/draft/";
$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);

$arParams["USE_CUT"] = ($arParams["USE_CUT"] == "Y") ? "Y" : "N";

$arParams["EDITOR_RESIZABLE"] = $arParams["EDITOR_RESIZABLE"] !== "N";
$arParams["EDITOR_CODE_DEFAULT"] = $arParams["EDITOR_CODE_DEFAULT"] === "Y";
$arParams["EDITOR_DEFAULT_HEIGHT"] = intVal($arParams["EDITOR_DEFAULT_HEIGHT"]);
if(IntVal($arParams["EDITOR_DEFAULT_HEIGHT"]) <= 0)
	$arParams["EDITOR_DEFAULT_HEIGHT"] = '120px';

$user_id = $USER->GetID();
$arResult["UserID"] = $user_id;
$arResult["allowVideo"] = COption::GetOptionString("blog","allow_video", "Y");

$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";
$arParams["USE_GOOGLE_CODE"] = $arParams["USE_GOOGLE_CODE"] === "Y";
$arParams["IMAGE_MAX_WIDTH"] = 400;
$arParams["IMAGE_MAX_HEIGHT"] = 400;

$arParams["POST_PROPERTY_SOURCE"] = $arParams["POST_PROPERTY"] = (is_array($arParams["POST_PROPERTY"]) ? $arParams["POST_PROPERTY"] : array($arParams["POST_PROPERTY"]));
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_DOC";
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_IMPRTNT";

if(
	CModule::IncludeModule("webdav")
	|| CModule::IncludeModule("disk")
)
{
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_FILE";
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_F_EDIT";
}
if (IsModuleInstalled("vote"))
{
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_VOTE";
}

if(CModule::IncludeModule("lists") && COption::GetOptionString("lists", "turnProcessesOn") == "Y" &&
	!$arParams["SOCNET_GROUP_ID"] && !$arResult["bExtranetSite"] && IsModuleInstalled('intranet'))
	$arResult['BLOG_POST_LISTS'] = true;
else
	$arResult['BLOG_POST_LISTS'] = false;

$arFilterblg = Array(
		"ACTIVE" => "Y",
		"USE_SOCNET" => "Y",
		"GROUP_ID" => $arParams["GROUP_ID"],
		"GROUP_SITE_ID" => SITE_ID,
		"OWNER_ID" => $arParams["USER_ID"],
	);

$a = new CAccess;
$a->UpdateCodes();

$arResult["perms"] = BLOG_PERMS_DENY;
if($arResult["bGroupMode"])
{
	if (
		CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin())
		|| $APPLICATION->GetGroupRight("blog") >= "W"
	)
	{
		$arResult["perms"] = BLOG_PERMS_FULL;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "moderate_post"))
	{
		$arResult["perms"] = BLOG_PERMS_MODERATE;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post"))
	{
		$arResult["perms"] = BLOG_PERMS_WRITE;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "premoderate_post"))
	{
		$arResult["perms"] = BLOG_PERMS_PREMODERATE;
	}
}
elseif (
	$arParams["USER_ID"] == $user_id
	|| $APPLICATION->GetGroupRight("blog") >= "W"
	|| CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin())
)
{
	$arResult["perms"] = BLOG_PERMS_FULL;
}

$cacheTtl = 3153600;
$cacheId = 'blog_post_blog_'.md5(serialize($arFilterblg));
$cacheDir = '/blog/form/blog/';

$obCache = new CPHPCache;
if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
{
	$arBlog = $obCache->GetVars();
}
else
{
	$obCache->StartDataCache();

	$dbBl = CBlog::GetList(Array(), $arFilterblg);
	$arBlog = $dbBl ->Fetch();
	if (!$arBlog && IsModuleInstalled("intranet"))
	{
		$arIdeaBlogGroupID = array();
		if (IsModuleInstalled("idea"))
		{
			$rsSite = CSite::GetList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
			while($arSite = $rsSite->Fetch())
			{
				$val = COption::GetOptionInt("idea", "blog_group_id", false, $arSite["LID"]);
				if ($val)
				{
					$arIdeaBlogGroupID[] = $val;
				}
			}
		}

		if (empty($arIdeaBlogGroupID))
		{
			$arBlog = CBlog::GetByOwnerID($arParams["USER_ID"]);
		}
		else
		{
			$arBlogGroupID = array();
			$rsBlogGroup = CBlogGroup::GetList(array(), array(), false, false, array("ID"));
			while($arBlogGroup = $rsBlogGroup->Fetch())
			{
				if (!in_array($arBlogGroup["ID"], $arIdeaBlogGroupID))
				{
					$arBlogGroupID[] = $arBlogGroup["ID"];
				}
			}

			$arBlog = CBlog::GetByOwnerID($arParams["USER_ID"], $arBlogGroupID);
		}
	}

	$obCache->EndDataCache($arBlog);
}

$arResult["Blog"] = $arBlog;

if($arResult["bGroupMode"])
	$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
else
	$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));

$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", $arParams["ID"], LANGUAGE_ID);
$arResult["POST_PROPERTIES"] = array("SHOW" => "N", "DATA" => array());

$arParams["CACHE_TIME"] = defined("BX_COMP_MANAGED_CACHE") ? 3600*24*365 : 3600*24;
$arResult["PostToShow"]["GRATS"] = array();
$arResult["PostToShow"]["GRATS_DEF"] = false;

$cache = new CPHPCache;
$cache_id = "blog_post_grats_".SITE_ID;
$cache_path = "/blog/form/post/new";

if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
{
	$Vars = $cache->GetVars();
	$arResult["PostToShow"]["GRATS"] = $Vars["GRATS"];
	$arResult["PostToShow"]["GRATS_DEF"] = $Vars["GRATS_DEF"];
	$honour_iblock_id = $Vars["GRATS_IBLOCK_ID"];
}
else
{
	$cache->StartDataCache();
	if (
		(
			!empty($arParams["POST_PROPERTY"])
			|| IsModuleInstalled("intranet")
		)
		&& !$arResult["bExtranetSite"]
		&& CModule::IncludeModule("iblock")
	)
	{
		$rsIBlock = CIBlock::GetList(array(), array("CODE" => "honour", "TYPE" => "structure"));
		if ($arIBlock = $rsIBlock->Fetch())
		{
			$honour_iblock_id = $arIBlock["ID"];

			if (defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);

			$rsIBlockPropertyEnum = CIBlockPropertyEnum::GetList(
				array(
					"SORT" => "ASC",
					"XML_ID" => "ASC"
				),
				array(
					"CODE" => "GRATITUDE",
					"IBLOCK_ID" => $arIBlock["ID"]
				)
			);
			while($arIBlockPropertyEnum = $rsIBlockPropertyEnum->Fetch())
			{
				$arResult["PostToShow"]["GRATS"][] = $arIBlockPropertyEnum;
				if ($arIBlockPropertyEnum["DEF"] == "Y")
					$arResult["PostToShow"]["GRATS_DEF"] = $arIBlockPropertyEnum;
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->EndTagCache();
		}
	}
	$cache->EndDataCache(
		array(
			"GRATS" => $arResult["PostToShow"]["GRATS"],
			"GRATS_DEF" => $arResult["PostToShow"]["GRATS_DEF"],
			"GRATS_IBLOCK_ID" => $honour_iblock_id
		)
	);
}

if(
	$arParams["ID"] > 0
	&& $arPost = CBlogPost::GetByID($arParams["ID"])
)
{
	$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
	$arResult["Post"] = $arPost;
	if($arParams["SET_TITLE"]=="Y")
	{
		$APPLICATION->SetTitle(GetMessage("BLOG_POST_EDIT"));
	}

	if(
		$arParams["USER_ID"] == $user_id
		|| (
			$_POST["apply"]
			&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
		)
	)
	{
		$arResult["perms"] = BLOG_PERMS_FULL;
	}
	else
	{
		$arResult["perms"] = CBlogPost::GetSocNetPostPerms($arPost["ID"], true, false, $arPost["AUTHOR_ID"]);
	}

	// Get UF_GRATITUDE
	if (
		IsModuleInstalled("intranet")
		&& CModule::IncludeModule("iblock")
		&& is_array($arPostFields)
		&& array_key_exists("UF_GRATITUDE", $arPostFields)
		&& is_array($arPostFields["UF_GRATITUDE"])
		&& intval($arPostFields["UF_GRATITUDE"]["VALUE"]) > 0
	)
	{
		if ($honour_iblock_id > 0)
		{
			$arGrat = array(
				"ID" => false,
				"USERS" => array(),
				"USERS_FOR_JS" => array(),
				"TYPE" => false
			);
			$rsElementProperty = CIBlockElement::GetProperty(
				$honour_iblock_id,
				$arPostFields["UF_GRATITUDE"]["VALUE"]
			);
			while ($arElementProperty = $rsElementProperty->Fetch())
			{
				if (!$arGrat["ID"])
					$arGrat["ID"] = htmlspecialcharsbx($arPostFields["UF_GRATITUDE"]["VALUE"]);

				if ($arElementProperty["CODE"] == "USERS")
					$arGrat["USERS"][] = htmlspecialcharsbx($arElementProperty["VALUE"]);
				elseif ($arElementProperty["CODE"] == "GRATITUDE")
					$arGrat["TYPE"] = array(
						"VALUE_ENUM" => $arElementProperty["VALUE_ENUM"],
						"XML_ID" => $arElementProperty["VALUE_XML_ID"]
					);
			}
			if ($arGrat["ID"])
			{
				$dbUsers = CUser::GetList(
					($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')),
					($dummy=''),
					array(
						"ID" => implode("|", $arGrat["USERS"]),
						array(
							"FIELDS" => array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION")
						)
					)
				);

				while($arGratUser = $dbUsers->Fetch())
				{
					$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arGratUser));
					$arGrat["USERS_FOR_JS"]["U".$arGratUser["ID"]] = array(
						"id" => "U".$arGratUser["ID"],
						"entityId" => $arGratUser["ID"],
						"name" => $sName,
						"avatar" => "",
						"desc" => $arGratUser["WORK_POSITION"] ? $arGratUser["WORK_POSITION"] : ($arGratUser["PERSONAL_PROFESSION"] ? $arGratUser["PERSONAL_PROFESSION"] : "&nbsp;")
					);
				}

				$arResult["PostToShow"]["GRAT_CURRENT"] = $arGrat;
			}
		}
	}
}
else
{
	$arParams["ID"] = 0;
	if($arParams["SET_TITLE"]=="Y")
		$APPLICATION->SetTitle(GetMessage("BLOG_NEW_MESSAGE"));
}

if (IntVal($_GET["delete_blog_post_id"]) > 0 && $_GET["ajax_blog_post_delete"] == "Y")
{
	if (
		check_bitrix_sessid()
		|| $arParams["IS_REST"] == "Y"
	)
	{
		$delId = IntVal($_GET["delete_blog_post_id"]);
		if($arPost = CBlogPost::GetByID($delId))
		{
			$perms = ($arPost["AUTHOR_ID"] == $user_id ? BLOG_PERMS_FULL : CBlogPost::GetSocNetPostPerms($_GET["delete_blog_post_id"], true));

			if($perms >= BLOG_PERMS_FULL)
			{
				CBlogPost::DeleteLog($delId);
				BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
				BXClearCache(true, "/blog/socnet_post/".intval($delId / 100)."/".$delId."/");
				BXClearCache(true, "/blog/socnet_post/gen/".intval($delId / 100)."/".$delId."/");
				BXClearCache(true, CComponentEngine::MakeComponentPath("bitrix:socialnetwork.blog.blog"));

				if (!CBlogPost::Delete($delId))
				{
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR");
				}
				else
				{
					$arResult["OK_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_OK");
				}
			}
			else
			{
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS");
			}
		}
		else
		{
			$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR");
		}
	}
	else
	{
		$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG");
	}

	$arResult["delete_blog_post"] = "Y";
	$this->IncludeComponentTemplate();

	if (
		$arParams["IS_REST"] == "Y"
		&& strlen($arResult["ERROR_MESSAGE"]) > 0
	)
	{
		$APPLICATION->ThrowException($arResult["ERROR_MESSAGE"], "ERROR_IN_ACTION");
		return false;
	}

	return true;
}

/*if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']) && ($_REQUEST['mfi_mode'] == "upload"))
{
	CBlogImage::AddImageResizeHandler(array(
		'width' => $arParams["IMAGE_MAX_WIDTH"],
		'height' => $arParams["IMAGE_MAX_HEIGHT"]
	));
}*/

if (
	(
		$arParams["ID"] == 0
		&& $arResult["perms"] >= BLOG_PERMS_PREMODERATE
	)
	|| (
		$arParams["ID"] > 0
		&& $arResult["perms"] >= BLOG_PERMS_FULL
		&& $arPost["BLOG_ID"] == $arBlog["ID"]
	)
)
{
	$arP = Array();
	if (
		IntVal($arParams["ID"]) > 0
		&& $arPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY
	)
	{
		$arP = CBlogPost::GetSocnetPerms($arPost["ID"]);
	}

	if (
		IntVal($arParams["ID"]) > 0
		&& $arPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY
		&& empty($arP["U"])
		&& empty($arP["DR"])
	)
	{
		$arResult["OK_MESSAGE"] = GetMessage("BPE_HIDDEN_POSTED");
	}

	$bAllowToAll = (COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y");
	if ($bAllowToAll)
	{
		$arToAllRights = unserialize(COption::GetOptionString("socialnetwork", "livefeed_toall_rights", 'a:1:{i:0;s:2:"AU";}'));
		if (!$arToAllRights)
		{
			$arToAllRights = array("AU");
		}

		$arUserGroupCode = array_merge(array("AU"), CAccess::GetUserCodesArray($GLOBALS["USER"]->GetID()));
		if (count(array_intersect($arToAllRights, $arUserGroupCode)) <= 0)
		{
			$bAllowToAll = false;
		}
	}

	$bDefaultToAll = (
		$bAllowToAll
			? (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y")
			: false
	);

	if ($_POST["apply"] || $_POST["save"] || $_POST["do_upload"] || $_POST["draft"])
	{
		if(
			check_bitrix_sessid()
			|| $arParams["IS_REST"] == "Y"
		)
		{
			if ($_POST["decode"] == "Y")
			{
				CUtil::JSPostUnescape();
			}

			if (empty($arBlog))
			{
				if(!empty($arParams["GROUP_ID"]))
				{
					$arFields = array(
						"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
						"GROUP_ID" => (is_array($arParams["GROUP_ID"])) ? IntVal($arParams["GROUP_ID"][0]) : IntVal($arParams["GROUP_ID"]),
						"ACTIVE" => "Y",
						"ENABLE_COMMENTS" => "Y",
						"ENABLE_IMG_VERIF" => "Y",
						"EMAIL_NOTIFY" => "Y",
						"ENABLE_RSS" => "Y",
						"ALLOW_HTML" => "N",
						"ENABLE_TRACKBACK" => "N",
						"SEARCH_INDEX" => "Y",
						"USE_SOCNET" => "Y",
						"=DATE_CREATE" => $DB->CurrentTimeFunction(),
						"PERMS_POST" => Array(
							1 => "I",
							2 => "I" ),
						"PERMS_COMMENT" => Array(
							1 => "P",
							2 => "P" ),
					);

					$bRights = false;
					$rsUser = CUser::GetByID($arParams["USER_ID"]);
					$arUser = $rsUser->Fetch();
					if(strlen($arUser["NAME"]."".$arUser["LAST_NAME"]) <= 0)
					{
						$arFields["NAME"] = GetMessage("BLG_NAME")." ".$arUser["LOGIN"];
					}
					else
					{
						$arFields["NAME"] = GetMessage("BLG_NAME")." ".$arUser["NAME"]." ".$arUser["LAST_NAME"];
					}

					$arFields["URL"] = str_replace(" ", "_", $arUser["LOGIN"])."-blog-".SITE_ID;
					$arFields["OWNER_ID"] = $arParams["USER_ID"];

					$urlCheck = preg_replace("/[^a-zA-Z0-9_-]/is", "", $arFields["URL"]);
					if ($urlCheck != $arFields["URL"])
					{
						$arFields["URL"] = "u".$arParams["USER_ID"]."-blog-".SITE_ID;
					}

					if(CBlog::GetByUrl($arFields["URL"]))
					{
						$uind = 0;
						do
						{
							$uind++;
							$arFields["URL"] = $arFields["URL"].$uind;
						}
						while (CBlog::GetByUrl($arFields["URL"]));
					}

					$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_USER, $arFields["OWNER_ID"], "blog", "view_post");
					if ($featureOperationPerms == SONET_RELATIONS_TYPE_ALL)
					{
						$bRights = true;
					}

					$arFields["PATH"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arFields["URL"], "user_id" => $arFields["OWNER_ID"], "group_id" => $arFields["SOCNET_GROUP_ID"]));

					$blogID = CBlog::Add($arFields);
					BXClearCache(true, "/blog/form/blog/");
					if ($bRights)
					{
						CBlog::AddSocnetRead($blogID);
					}
					$arBlog = CBlog::GetByID($blogID, $arParams["GROUP_ID"]);
				}
			}
		}
		else
		{
			$arResult["ERROR_MESSAGE"] = GetMessage("BPE_SESS");
		}
	}

	if ($_GET["image_upload_frame"] == "Y" || $_GET["image_upload"] || $_POST["do_upload"] || $_GET["del_image_id"])
	{
		if (
			check_bitrix_sessid()
			|| $arParams["IS_REST"] == "Y"
		)
		{
			if(IntVal($_GET["del_image_id"]) > 0)
			{
				$del_image_id = IntVal($_GET["del_image_id"]);
				$aImg = CBlogImage::GetByID($del_image_id);
				if (
					$aImg["BLOG_ID"] == $arBlog["ID"]
					&& $aImg["POST_ID"] == IntVal($arParams["ID"])
				)
				{
					CBlogImage::Delete($del_image_id);
				}
				$APPLICATION->RestartBuffer();
				die();
			}
			else
			{
				$arResult["imageUploadFrame"] = "Y";
				$arResult["imageUpload"] = "Y";
				$APPLICATION->RestartBuffer();
				header("Pragma: no-cache");

				$arFields = Array();
				if ($_FILES["BLOG_UPLOAD_FILE"]["size"] > 0)
				{
					$arFields = array(
						"BLOG_ID"	=> $arBlog["ID"],
						"POST_ID"	=> $arParams["ID"],
						"USER_ID"	=> $arResult["UserID"],
						"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
						"TITLE"		=> $_POST["IMAGE_TITLE"],
						"IMAGE_SIZE"	=> $_FILES["BLOG_UPLOAD_FILE"]["size"]
					);
					$arImage=array_merge(
						$_FILES["BLOG_UPLOAD_FILE"],
						array(
							"MODULE_ID" => "blog",
							"del" => "Y"
						)
					);
					$arFields["FILE_ID"] = $arImage;
				}
				elseif ($_POST["do_upload"] && $_FILES["FILE_ID"]["size"] > 0)
				{
					$arFields = array(
						"BLOG_ID"	=> $arBlog["ID"],
						"POST_ID"	=> $arParams["ID"],
						"USER_ID"	=> $arResult["UserID"],
						"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
						"TITLE"		=> $_POST["IMAGE_TITLE"],
						"IMAGE_SIZE"	=> $_FILES["FILE_ID"]["size"],
						"URL" => $arBlog["URL"],
					);
					$arImage=array_merge(
						$_FILES["FILE_ID"],
						array(
							"MODULE_ID" => "blog",
							"del" => "Y"
						)
					);
					$arFields["FILE_ID"] = $arImage;
				}
				if(!empty($arFields))
				{
					if ($imgID = CBlogImage::Add($arFields))
					{
						$aImg = CBlogImage::GetByID($imgID);
						$aImg = CBlogTools::htmlspecialcharsExArray($aImg);

						$aImgNew = CFile::ResizeImageGet(
							$aImg["FILE_ID"],
							array("width" => 90, "height" => 90),
							BX_RESIZE_IMAGE_EXACT,
							true
						);
						$aImg["source"] = CFile::ResizeImageGet(
							$aImg["FILE_ID"],
							array("width" => $arParams["IMAGE_MAX_WIDTH"], "height" => $arParams["IMAGE_MAX_HEIGHT"]),
							BX_RESIZE_IMAGE_PROPORTIONAL,
							true
						);
						$aImg["params"] = CFile::_GetImgParams($aImg["FILE_ID"]);
						$aImg["fileName"] = substr($aImgNew["src"], strrpos($aImgNew["src"], "/")+1);
						$file = "<img src=\"".$aImgNew["src"]."\" width=\"".$aImgNew["width"]."\" height=\"".$aImgNew["height"]."\" id=\"".$aImg["ID"]."\" border=\"0\" style=\"cursor:pointer\" onclick=\"InsertBlogImage_LHEPostFormId_blogPostForm('".$aImg["ID"]."', '".$aImg["source"]['src']."', '".$aImgNew["source"]['width']."');\" title=\"".GetMessage("BLOG_P_INSERT")."\">";

						$file = str_replace("'","\'",$file);
						$file = str_replace("\r"," ",$file);
						$file = str_replace("\n"," ",$file);
						$arResult["ImageModified"] = $file;
						$arResult["Image"] = $aImg;
					}
					elseif ($ex = $APPLICATION->GetException())
					{
						$arResult["ERROR_MESSAGE"] = $ex->GetString();
					}
				}
			}
		}
	}
	else
	{
		// Save calendar event from Socialnetwork live feed form
		if ($_POST["save"] == "Y" && $_POST["changePostFormTab"] == "calendar" && (check_bitrix_sessid() || $arParams["IS_REST"] == "Y"))
		{
			if (isset($_POST['EVENT_PERM']))
			{
				$arAccessCodes = array();
				foreach($_POST["EVENT_PERM"] as $v => $k)
				{
					if(strlen($v) > 0 && is_array($k) && !empty($k))
					{
						foreach($k as $vv)
						{
							if(strlen($vv) > 0)
							{
								$arAccessCodes[] = $vv;
							}
						}
					}
				}
			}

			$arFields = array(
				"ID" => intVal($_POST['EVENT_ID']),
				"DT_FROM_TS" => $_POST['EVENT_FROM_TS'],
				"DT_TO_TS" => $_POST['EVENT_TO_TS'],
				"SKIP_TIME" => $_POST['EVENT_FULL_DAY'] == 'Y',
				'NAME' => trim($_POST['EVENT_NAME']),
				'DESCRIPTION' => trim($_POST['EVENT_DESCRIPTION']),
				'SECTION' => intVal($_POST['EVENT_SECTION']),
				'ACCESSIBILITY' => $_POST['EVENT_ACCESSIBILITY'],
				'IMPORTANCE' => $_POST['EVENT_IMPORTANCE'],
				'RRULE' => $_POST['EVENT_RRULE'],
				'LOCATION' => $_POST['EVENT_LOCATION'],
				"REMIND" => isset($_POST['EVENT_REMIND']) ? array(0 => array('count' => $_POST['EVENT_REMIND_COUNT'], 'type' => $_POST['EVENT_REMIND_TYPE'])) : null
			);

			// Userfields for event
			$arUFFields = array();
			foreach ($_POST as $field => $value)
			{
				if (substr($field, 0, 3) == "UF_")
				{
					$arUFFields[$field] = $value;
				}
			}

			CCalendarLiveFeed::EditCalendarEventEntry($arFields, $arUFFields, $arAccessCodes, array(
				'type' => 'user',
				'userId' => $arBlog["OWNER_ID"]
			));

			$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
			LocalRedirect($redirectUrl);
		}

		if($_POST["save"] == "Y" && $_POST["changePostFormTab"] == "lists" && (check_bitrix_sessid() || $arParams["IS_REST"] == "Y"))
		{
			$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
			LocalRedirect($redirectUrl);
		}

		if (
			(
				$_POST["apply"]
				|| $_POST["save"]
				|| $_POST["draft"]
			)
			&& empty($_POST["reset"])
		) // Save on button click
		{
			if (
				check_bitrix_sessid()
				|| $arParams["IS_REST"] == "Y"
			)
			{
				if(strlen($arResult["ERROR_MESSAGE"]) <= 0)
				{
					$GLOBALS["DB"]->StartTransaction();

					$CATEGORYtmp = Array();
					if(!empty($_POST["TAGS"]))
					{
						$dbCategory = CBlogCategory::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"]));
						while($arCategory = $dbCategory->Fetch())
						{
							$arCatBlog[ToLower($arCategory["NAME"])] = $arCategory["ID"];
						}
						$tags = explode (",", $_POST["TAGS"]);
						foreach($tags as $tg)
						{
							$tg = trim($tg);
							if(
								!in_array($arCatBlog[ToLower($tg)], $CATEGORYtmp)
								&& strlen($tg) > 0
							)
							{
								$CATEGORYtmp[] = (
									IntVal($arCatBlog[ToLower($tg)]) > 0
									? $arCatBlog[ToLower($tg)]
									: CBlogCategory::Add(array("BLOG_ID" => $arBlog["ID"], "NAME" => $tg))
								);
							}
						}
					}
					elseif (!empty($_POST["CATEGORY_ID"]))
					{
						foreach($_POST["CATEGORY_ID"] as $v)
						{
							if(substr($v, 0, 4) == "new_")
							{
								$CATEGORYtmp[] = CBlogCategory::Add(array("BLOG_ID"=>$arBlog["ID"],"NAME"=>substr($v, 4
)));
								$arSites = ($arResult["bGroupMode"] && $arGroupSites ? $arGroupSites : array(SITE_ID));
							}
							else
							{
								$CATEGORYtmp[] = $v;
							}
						}
					}
					else
					{
						$CATEGORY_ID = "";
					}

					$CATEGORY_ID = implode(",", $CATEGORYtmp);

					$DATE_PUBLISH = "";
					if (strlen($_POST["DATE_PUBLISH_DEF"]) > 0)
					{
						$DATE_PUBLISH = $_POST["DATE_PUBLISH_DEF"];
					}
					elseif (strlen($_POST["DATE_PUBLISH"]) <= 0)
					{
						$DATE_PUBLISH = ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL");
					}
					else
					{
						$DATE_PUBLISH = $_POST["DATE_PUBLISH"];
					}

					$PUBLISH_STATUS = (strlen($_POST["draft"]) > 0 ? BLOG_PUBLISH_STATUS_DRAFT : BLOG_PUBLISH_STATUS_PUBLISH);

					$arFields = array(
						"TITLE" => trim($_POST["POST_TITLE"]),
						"DETAIL_TEXT" => $_POST["POST_MESSAGE"],
						"DETAIL_TEXT_TYPE" => "text",
						"DATE_PUBLISH" => $DATE_PUBLISH,
						"PUBLISH_STATUS" => $PUBLISH_STATUS,
						"CATEGORY_ID" => $CATEGORY_ID,
						"PATH" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => "#post_id#", "user_id" => $arBlog["OWNER_ID"])),
						"URL" => $arBlog["URL"],
					);

					if($arParams["ALLOW_POST_CODE"] && strlen(trim($_POST["CODE"])) > 0)
					{
						$arFields["CODE"] = trim($_POST["CODE"]);
						$arPCFilter = array("BLOG_ID" => $arBlog["ID"], "CODE" => $arFields["CODE"]);
						if(IntVal($arParams["ID"]) > 0)
							$arPCFilter["!ID"] = $arParams["ID"];
						$db = CBlogPost::GetList(Array(), $arPCFilter, false, Array("nTopCount" => 1), Array("ID", "CODE", "BLOG_ID"));
						if($db->Fetch())
						{
							$uind = 0;
							do
							{
								$uind++;
								$arFields["CODE"] = $arFields["CODE"].$uind;
								$arPCFilter["CODE"]  = $arFields["CODE"];
								$db = CBlogPost::GetList(Array(), $arPCFilter, false, Array("nTopCount" => 1), Array("ID", "CODE", "BLOG_ID"));
							}
							while ($db->Fetch());
						}
					}
					$arFields["PERMS_POST"] = array();
					$arFields["PERMS_COMMENT"] = array();

					$arFields["MICRO"] = "N";
					if(strlen($arFields["TITLE"]) <= 0 || $_POST["show_title"] == "N")
					{
						$arFields["MICRO"] = "Y";
						$arFields["TITLE"] = trim(preg_replace(array("/\n+/is".BX_UTF_PCRE_MODIFIER, "/\s+/is".BX_UTF_PCRE_MODIFIER), " ", blogTextParser::killAllTags($arFields["DETAIL_TEXT"])));
						if(strlen($arFields["TITLE"]) <= 0)
							$arFields["TITLE"] = GetMessage("BLOG_EMPTY_TITLE_PLACEHOLDER");
					}

					$arFields["SOCNET_RIGHTS"] = Array();

					if(
						empty($_POST["SPERM"])
						&& $arParams["IS_REST"] == "Y"
						&& $bAllowToAll
					)
						$_POST["SPERM"] = array("UA" => array("UA"));

					if(!empty($_POST["SPERM"]))
					{
						$bOne = true;
						foreach($_POST["SPERM"] as $v => $k)
						{
							if(strlen($v) > 0 && is_array($k) && !empty($k))
							{
								foreach($k as $vv)
								{
									if(strlen($vv) > 0)
									{
										$arFields["SOCNET_RIGHTS"][] = $vv;
										if($v != "SG")
											$bOne = false;

									}
								}
							}
						}

						if($bOne && !empty($_POST["SPERM"]["SG"]))
						{
							$bOnesg = false;
							$bFirst = true;
							$oGrId = 0;
							foreach($_POST["SPERM"]["SG"] as $v)
							{
								if(strlen($v) > 0)
								{
									if($bFirst)
									{
										$bOnesg = true;
										$bFirst = false;
										$v = str_replace("SG", "", $v);
										$oGrId = IntVal($v);
									}
									else
									{
										$bOnesg = false;
									}
								}
							}
							if(
								$bOnesg
								&& !CSocNetUser::IsCurrentUserModuleAdmin()
								&& !CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $oGrId, "blog", "write_post")
								&& !CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $oGrId, "blog", "moderate_post")
								&& !CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $oGrId, "blog", "full_post")
							)
							{
								$arFields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
							}
						}
					}

					if (
						in_array("UA", $arFields["SOCNET_RIGHTS"])
						&& !$bAllowToAll
					)
						foreach ($arFields["SOCNET_RIGHTS"] as $key => $value)
							if ($value == "UA")
							{
								unset($arFields["SOCNET_RIGHTS"][$key]);
								break;
							}

					$bError = false;
					if ($arResult["bExtranetUser"])
					{
						if(empty($arFields["SOCNET_RIGHTS"]) || in_array("UA", $arFields["SOCNET_RIGHTS"]))
						{
							$bError = true;
							$arResult["ERROR_MESSAGE"] = GetMessage("BLOG_BPE_EXTRANET_ERROR");
						}
					}
					elseif (empty($arFields["SOCNET_RIGHTS"]))
					{
						$bError = true;
						$arResult["ERROR_MESSAGE"] = GetMessage("BLOG_BPE_DESTINATION_EMPTY");
					}

					if(!$bError)
					{
						$fieldName = 'UF_BLOG_POST_DOC';
						if (
							isset($GLOBALS[$fieldName])
							&& is_array($GLOBALS[$fieldName])
						)
						{
							$arOldFiles = array();
							if($arParams["ID"] > 0 && strlen($_POST["blog_upload_cid"]) <= 0)
							{
								$dbP = CBlogPost::GetList(array(), array("ID" => $arParams["ID"]), false, false, array("ID", $fieldName));
								if($arP = $dbP->Fetch())
								{
									$arOldFiles = $arP[$fieldName];
								}
							}
							$arAttachedFiles = array();
							foreach($GLOBALS[$fieldName] as $fileID)
							{
								$fileID = intval($fileID);

								if ($fileID <= 0)
								{
									continue;
								}
								elseif(
									(
										!is_array($_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]])
										|| !in_array($fileID, $_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]])
									)
									&& ( // mobile
										!is_array($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()])
										|| !in_array($fileID, $_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()])
									)
								)
								{
									if (
										empty($arOldFiles)
										|| !in_array($fileID, $arOldFiles)
									)
									{
										continue;
									}
								}

								$arFile = CFile::GetFileArray($fileID);
								if (CFile::CheckImageFile(CFile::MakeFileArray($fileID)) === null)
								{
									$arImgFields = array(
										"BLOG_ID" => $arBlog["ID"],
										"POST_ID" => 0,
										"USER_ID" => $arResult["UserID"],
										"=TIMESTAMP_X" => $DB->GetNowFunction(),
										"TITLE" => $arFile["FILE_NAME"],
										"IMAGE_SIZE" => $arFile["FILE_SIZE"],
										"FILE_ID" => $fileID,
										"URL" => $arBlog["URL"],
										"IMAGE_SIZE_CHECK" => "N",
									);
									$imgID = CBlogImage::Add($arImgFields);
									if (intval($imgID) <= 0)
									{
										$GLOBALS["APPLICATION"]->ThrowException("Error Adding file by CBlogImage::Add");
									}
									else
									{
										$arFields["DETAIL_TEXT"] = str_replace("[IMG ID=".$fileID."file", "[IMG ID=".$imgID."", $arFields["DETAIL_TEXT"]);
									}
								}
								else
								{
									$arAttachedFiles[] = $fileID;
								}
							}
							if (
								is_array($arPostFields)
								&& is_array($arPostFields[$fieldName])
								&& is_array($arPostFields[$fieldName]["VALUE"])
							)
							{
								$arAttachedFiles = array_unique(array_merge($arAttachedFiles, array_intersect($GLOBALS[$fieldName], $arPostFields[$fieldName]["VALUE"])));
							}
							$GLOBALS[$fieldName] = $arAttachedFiles;
						}

						CSocNetLogComponent::checkEmptyUFValue('UF_BLOG_POST_FILE');

						if (!empty($arParams["POST_PROPERTY"]))
						{
							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("BLOG_POST", $arFields);
						}

						preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/ies".BX_UTF_PCRE_MODIFIER, $_POST["POST_MESSAGE"], $arMention);

						$APPLICATION->ResetException();
						$bAdd = false;

						if (
							array_key_exists("GRAT", $_POST)
							&& is_array($_POST["GRAT"])
							&& array_key_exists("U", $_POST["GRAT"])
							&& is_array($_POST["GRAT"]["U"])
							&& array_key_exists("GRAT_TYPE", $_POST)
							&& array_key_exists("changePostFormTab", $_POST)
							&& $_POST["changePostFormTab"] == "grat"
						)
						{
							$bNeedAddGrat = true;
						}

						if (
							!empty($_POST["attachedFilesRaw"])
							&& is_array($_POST["attachedFilesRaw"])
						)
						{
							CSocNetLogComponent::saveRawFilesToUF(
								$_POST["attachedFilesRaw"],
								(
									IsModuleInstalled("webdav")
									|| IsModuleInstalled("disk")
										? "UF_BLOG_POST_FILE"
										: "UF_BLOG_POST_DOC"
								),
								$arFields
							);
						}

						if ($arParams["ID"] > 0)
						{
							if (
								array_key_exists("GRAT", $_POST)
								&& is_array($_POST["GRAT"])
								&& array_key_exists("U", $_POST["GRAT"])
								&& is_array($_POST["GRAT"]["U"])
								&& array_key_exists("GRAT_TYPE", $_POST)
							)
							{
								$bGratFromForm = true;
								if (
									is_array($arResult["PostToShow"]["GRAT_CURRENT"])
									&& count(array_diff($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"], $_POST["GRAT"]["U"])) == 0
									&& count(array_diff($_POST["GRAT"]["U"], $arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])) == 0
									&& ToLower($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]) == ToLower($_POST["GRAT_TYPE"])
								)
								{
									$bNeedAddGrat = false;
									$bGratSimilar = true;
								}
							}

							if (
								(
									$_POST["changePostFormTab"] != "grat"
									|| (
										$bGratFromForm 
										&& !$bGratSimilar
									)
								)
								&& (
									is_array($arResult["PostToShow"]["GRAT_CURRENT"])
									&& intval($arResult["PostToShow"]["GRAT_CURRENT"]["ID"]) > 0
									&& CModule::IncludeModule("iblock")
								)
							)
							{
								CIBlockElement::Delete($arResult["PostToShow"]["GRAT_CURRENT"]["ID"]);

								if ($_POST["changePostFormTab"] != "grat")
								{
									CBlogPost::Update($arParams["ID"], array(
										"DETAIL_TEXT_TYPE" => "text",
										"UF_GRATITUDE" => false
									));
								}
							}

							$arOldPost = CBlogPost::GetByID($arParams["ID"]);
							preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/ies".BX_UTF_PCRE_MODIFIER, $arOldPost["DETAIL_TEXT"], $arMentionOld);
							$socnetRightsOld = CBlogPost::GetSocnetPerms($arParams["ID"]);

							unset($arFields["DATE_PUBLISH"]);

							if($newID = CBlogPost::Update($arParams["ID"], $arFields))
							{
								BXClearCache(true, "/blog/socnet_post/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
								BXClearCache(true, "/blog/socnet_post/gen/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
								BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");

								$arFields["AUTHOR_ID"] = $arOldPost["AUTHOR_ID"];
								if (
									$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT
									&& $arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
								)
								{
									CBlogPost::DeleteLog($newID);
								}
								elseif (
									$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
									&& $arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
								)
								{
									$arParamsUpdateLog = Array(
										"allowVideo" => $arResult["allowVideo"],
										"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
									);
									CBlogPost::UpdateLog($newID, $arFields, $arBlog, $arParamsUpdateLog);
								}
							}
						}
						else
						{
							$arFields["=DATE_CREATE"] = $DB->GetNowFunction();
							$arFields["AUTHOR_ID"] = $arResult["UserID"];
							$arFields["BLOG_ID"] = $arBlog["ID"];

							$ar = (is_array($arFields["UF_BLOG_POST_FILE"]) ? array_values($arFields["UF_BLOG_POST_FILE"]) : array());
							$dbDuplPost = CBlogPost::GetList(
								array("ID" => "DESC"),
								array("BLOG_ID" => $arBlog["ID"]),
								false,
								array("nTopCount" => 1),
								array("ID", "BLOG_ID", "AUTHOR_ID", "DETAIL_TEXT", "TITLE")
							);
							if($arDuplPost = $dbDuplPost->Fetch())
							{
								if(
									empty($ar[0])
									&& $arDuplPost["BLOG_ID"] == $arFields["BLOG_ID"]
									&& IntVal($arDuplPost["AUTHOR_ID"]) == IntVal($arFields["AUTHOR_ID"])
									&& md5($arDuplPost["DETAIL_TEXT"]) == md5($arFields["DETAIL_TEXT"])
									&& md5($arDuplPost["TITLE"]) == md5($arFields["TITLE"])
								)
								{
									$bError = true;
									$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_DUPLICATE_POST");
								}
							}

							if(!$bError)
							{
								$newID = CBlogPost::Add($arFields);
								$socnetRightsOld = Array("U" => Array());

								$bAdd = true;
								$bNeedMail = false;
							}
						}

						if(IntVal($newID) > 0)
						{
							if (
								$bNeedAddGrat
								&& CModule::IncludeModule("iblock")
							)
							{
								$arGratFromPOST = false;
								$arUsersFromPOST = false;

								foreach ($arResult["PostToShow"]["GRATS"] as $arGrat)
								{
									if (ToLower($arGrat["XML_ID"]) == ToLower($_POST["GRAT_TYPE"]))
									{
										$arGratFromPOST = $arGrat;
										break;
									}
								}

								foreach($_POST["GRAT"]["U"] as $code)
									if (preg_match('/^U(\d+)$/', $code, $matches))
										$arUsersFromPOST[] = $matches[1];

								if (
									$arGratFromPOST
									&& $arUsersFromPOST
								)
								{
									$el = new CIBlockElement;
									$new_grat_element_id = $el->Add(
										array(
											"IBLOCK_ID" => $honour_iblock_id,
											"DATE_ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
											"NAME" => str_replace("#GRAT_NAME#", $arGratFromPOST["VALUE"], GetMessage("BLOG_GRAT_IBLOCKELEMENT_NAME"))
										),
										false,
										false
									);
									if ($new_grat_element_id > 0)
									{
										CIBlockElement::SetPropertyValuesEx(
											$new_grat_element_id,
											$honour_iblock_id,
											array(
												"USERS" => $arUsersFromPOST,
												"GRATITUDE" => array("VALUE" => $arGratFromPOST["ID"])
											)
										);
										CBlogPost::Update($newID, array(
											"DETAIL_TEXT_TYPE" => "text",
											"UF_GRATITUDE" => $new_grat_element_id
										));
									}
								}
							}

							CBlogPostCategory::DeleteByPostID($newID);
							foreach($CATEGORYtmp as $v)
								CBlogPostCategory::Add(Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $newID, "CATEGORY_ID"=>$v));

							$DB->Query("UPDATE b_blog_image SET POST_ID=".$newID." WHERE BLOG_ID=".$arBlog["ID"]." AND POST_ID=0", true);

							$bHasImg = false;
							$bHasTag = false;
							$bHasProps = false;
							$bHasOnlyAll = false;

							if(!empty($CATEGORYtmp))
								$bHasTag = true;

							$dbImg = CBlogImage::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $newID, "IS_COMMENT" => "N"), false, false, Array("ID"));
							if($dbImg->Fetch())
								$bHasImg = true;

							$arPostFieldsOLD = $arPostFields;
							$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", $newID, LANGUAGE_ID);
							if (
								($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"] != $arPostFieldsOLD["UF_BLOG_POST_IMPRTNT"]["VALUE"])
								||
								($arParams["ID"] > 0 &&
									($arResult["Post"]["~DETAIL_TEXT"] != $arFields["DETAIL_TEXT"] ||
										$arResult["Post"]["~TITLE"] != $arFields["TITLE"])))
							{
								BXClearCache(true, CComponentEngine::MakeComponentPath("bitrix:socialnetwork.blog.blog"));
							}
							foreach ($arPostFields as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]) > 0)
								{
									$bHasProps = true;
									break;
								}
							}

							if(!empty($arFields["SOCNET_RIGHTS"]) && count($arFields["SOCNET_RIGHTS"]) == 1 && in_array("UA", $arFields["SOCNET_RIGHTS"]))
								$bHasOnlyAll = true;

							$arFieldsHave = array(
								"HAS_IMAGES" => ($bHasImg ? "Y" : "N"),
								"HAS_TAGS" => ($bHasTag ? "Y" : "N"),
								"HAS_PROPS" => ($bHasProps ? "Y" : "N"),
								"HAS_SOCNET_ALL" => ($bHasOnlyAll ? "Y" : "N"),
							);
							CBlogPost::Update($newID, $arFieldsHave, false);
						}

						if(
							($bAdd && $newID && $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
							|| ($arOldPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH && $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
						)
						{
							$arFields["ID"] = $newID;
							$arParamsNotify = Array(
								"bSoNet" => true,
								"UserID" => $arResult["UserID"],
								"allowVideo" => $arResult["allowVideo"],
								//"bGroupMode" => $arResult["bGroupMode"],
								"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
								"PATH_TO_POST" => $arParams["PATH_TO_POST"],
								"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
								"user_id" => $arParams["USER_ID"],
								"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
								"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
								);
							CBlogPost::Notify($arFields, $arBlog, $arParamsNotify);

							if(COption::GetOptionString("blog","send_blog_ping", "N") == "Y")
							{
								if(strlen($serverName) <= 0)
								{
									$dbSite = CSite::GetByID(SITE_ID);
									$arSite = $dbSite -> Fetch();
									$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);
									if (strlen($serverName) <=0)
									{
										if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
											$serverName = SITE_SERVER_NAME;
										else
											$serverName = COption::GetOptionString("main", "server_name", "");
										if (strlen($serverName) <=0)
											$serverName = $_SERVER["SERVER_NAME"];
									}
								}

								$blogUrl = "http://".$serverName.CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_BLOG"]), array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"]));
								CBlog::SendPing($arBlog["NAME"], $blogUrl);
							}
						}
					}

					if ($newID > 0 && strlen($arResult["ERROR_MESSAGE"]) <= 0) // Record saved successfully
					{
						$GLOBALS["DB"]->Commit();

						if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
						{
							BXClearCache(true, "/".SITE_ID."/blog/last_messages_list/");

							$arFieldsIM = Array(
								"TYPE" => "POST",
								"TITLE" => $arFields["TITLE"],
								"URL" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $newID, "user_id" => $arBlog["OWNER_ID"])),
								"ID" => $newID,
								"FROM_USER_ID" => $arParams["USER_ID"],
								"TO_USER_ID" => array(),
								"TO_SOCNET_RIGHTS" => $arFields["SOCNET_RIGHTS"],
								"TO_SOCNET_RIGHTS_OLD" => array(
									"U" => $socnetRightsOld["U"],
									"SG" => $socnetRightsOld["SG"],
								)
							);
							if(!empty($arMentionOld))
								$arFieldsIM["MENTION_ID_OLD"] = $arMentionOld[1];
							if(!empty($arMention))
								$arFieldsIM["MENTION_ID"] = $arMention[1];

							CBlogPost::NotifyIm($arFieldsIM);
						}

						$arParams["ID"] = $newID;
						if(!empty($_POST["SPERM"]["SG"]))
						{
							foreach($_POST["SPERM"]["SG"] as $v)
							{
								$group_id_tmp = substr($v, 2);
								if(IntVal($group_id_tmp) > 0)
									CSocNetGroup::SetLastActivity(IntVal($group_id_tmp));
							}
						}

						if ($arParams["IS_REST"] != "Y")
						{
							if (strlen($_POST["apply"]) <= 0)
							{
								if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT || strlen($_POST["draft"]) > 0)
								{
									$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DRAFT"], array("user_id" => $arBlog["OWNER_ID"]));
								}
								elseif($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY)
								{
									$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$newID, "user_id" => $arBlog["OWNER_ID"]))."?moder=y";
								}
								else
								{
									$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
								}
							}
							else
							{
								$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$newID, "user_id" => $arBlog["OWNER_ID"]));
							}
							$as = new CAutoSave(); // It is necessary to clear autosave buffer
							$as->Reset();
							LocalRedirect($redirectUrl);
						}
						else
							return $newID;
					}
					else
					{
						$GLOBALS["DB"]->Rollback();

						if(strlen($arResult["ERROR_MESSAGE"]) <= 0)
						{
							if ($ex = $APPLICATION->GetException())
								$arResult["ERROR_MESSAGE"] = $ex->GetString();
							else
								$arResult["ERROR_MESSAGE"] = "Error saving data to database.<br />";
						}
					}
				}

				if (
					strlen($arResult["ERROR_MESSAGE"]) > 0
					&& $arParams["IS_REST"] == "Y"
				)
				{
					$APPLICATION->ThrowException($arResult["ERROR_MESSAGE"], "ERROR_IN_ACTION");
					return false;
				}
			}
			else
				$arResult["ERROR_MESSAGE"] = GetMessage("BPE_SESS");
		}
		elseif($_POST["reset"])
		{
			if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT)
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DRAFT"], array("user_id" => $arBlog["OWNER_ID"])));
			elseif($arResult["bGroupMode"])
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_BLOG"], array("group_id" => $arParams["SOCNET_GROUP_ID"])));
			else
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"])));
		}

		if (
			$arParams["ID"] > 0
			&& strlen($arResult["ERROR_MESSAGE"]) <= 0
		) // Edit post
		{
			$arResult["PostToShow"]["TITLE"] = ($arPost["MICRO"] != "Y" ? $arPost["TITLE"] : "");
			$arResult["PostToShow"]["DETAIL_TEXT"] = $arPost["DETAIL_TEXT"];
			$arResult["PostToShow"]["~DETAIL_TEXT"] = $arPost["~DETAIL_TEXT"];
			$arResult["PostToShow"]["DETAIL_TEXT_TYPE"] = $arPost["DETAIL_TEXT_TYPE"];
			$arResult["PostToShow"]["PUBLISH_STATUS"] = $arPost["PUBLISH_STATUS"];
			$arResult["PostToShow"]["ENABLE_TRACKBACK"] = $arPost["ENABLE_TRACKBACK"] == "Y";
			$arResult["PostToShow"]["ENABLE_COMMENTS"] = $arPost["ENABLE_COMMENTS"];
			$arResult["PostToShow"]["ATTACH_IMG"] = $arPost["ATTACH_IMG"];
			$arResult["PostToShow"]["DATE_PUBLISH"] = $arPost["DATE_PUBLISH"];
			$arResult["PostToShow"]["CATEGORY_ID"] = $arPost["CATEGORY_ID"];
			$arResult["PostToShow"]["FAVORITE_SORT"] = $arPost["FAVORITE_SORT"];
			$arResult["PostToShow"]["MICRO"] = $arPost["MICRO"];
			if ($arParams["ALLOW_POST_CODE"])
			{
				$arResult["PostToShow"]["CODE"] = $arPost["CODE"];
			}

			$arResult["PostToShow"]["SPERM"] = CBlogPost::GetSocnetPerms($arPost["ID"]);
			if (
				is_array($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
				&& in_array("US".$arPost["AUTHOR_ID"], $arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
			)
			{
				$arResult["PostToShow"]["SPERM"]["U"]["A"] = Array();
			}

			if (
				!is_array($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
				|| !in_array("U".$arPost["AUTHOR_ID"], $arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
			)
			{
				unset($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]]);
			}
		}
		else
		{
			$arResult["PostToShow"]["TITLE"] = htmlspecialcharsEx($_POST["POST_TITLE"]);
			$arResult["PostToShow"]["CATEGORY_ID"] = $_POST["CATEGORY_ID"];
			$arResult["PostToShow"]["CategoryText"] = htmlspecialcharsEx($_POST["TAGS"]);
			$arResult["PostToShow"]["DETAIL_TEXT"] = $_POST["POST_MESSAGE"];
			$arResult["PostToShow"]["~DETAIL_TEXT"] = $_POST["POST_MESSAGE"];
			$arResult["PostToShow"]["PUBLISH_STATUS"] = htmlspecialcharsEx($_POST["PUBLISH_STATUS"]);
			$arResult["PostToShow"]["ENABLE_COMMENTS"] = htmlspecialcharsEx($_POST["ENABLE_COMMENTS"]);
			$arResult["PostToShow"]["DATE_PUBLISH"] = $_POST["DATE_PUBLISH"] ? htmlspecialcharsEx($_POST["DATE_PUBLISH"]) : ConvertTimeStamp(time()+CTimeZone::GetOffset(),"FULL");

			if($arParams["ALLOW_POST_CODE"])
				$arResult["PostToShow"]["CODE"] = htmlspecialcharsEx($_POST["CODE"]);

			$arResult["PostToShow"]["SPERM"] = CBlogTools::htmlspecialcharsExArray($_POST["SPERM"]);
			if(empty($arResult["PostToShow"]["SPERM"]))
				$arResult["PostToShow"]["SPERM"] = array();
			if(empty($_POST["SPERM"]))
			{
				if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
					$arResult["PostToShow"]["SPERM"]["SG"][IntVal($arParams["SOCNET_GROUP_ID"])] = "";
				if(IntVal($arParams["SOCNET_USER_ID"]) > 0)
					$arResult["PostToShow"]["SPERM"]["U"][IntVal($arParams["SOCNET_USER_ID"])] = "";
			}
			else
			{
				foreach($_POST["SPERM"] as $k => $v)
				{
					foreach($v as $vv1)
					{
						if(strlen($vv1) > 0)
						{
							if($vv1 == "UA")
								$arResult["PostToShow"]["SPERM"]["U"][] = "A";
							else
								$arResult["PostToShow"]["SPERM"][$k][str_replace($k, "", $vv1)] = "";
						}
					}
				}
			}

			if (
				(
					array_key_exists("GRAT", $_POST)
					&& is_array($_POST["GRAT"])
					&& array_key_exists("U", $_POST["GRAT"])
				)
				|| array_key_exists("GRAT_TYPE", $_POST)
			)
			{
				if
				(
					array_key_exists("GRAT", $_POST)
					&& is_array($_POST["GRAT"])
					&& array_key_exists("U", $_POST["GRAT"])
					&& is_array($_POST["GRAT"]["U"])
					&& count($_POST["GRAT"]["U"]) > 0
				)
				{
					$arUsersFromPOST = array();

					foreach($_POST["GRAT"]["U"] as $code)
						if (preg_match('/^U(\d+)$/', $code, $matches))
							$arUsersFromPOST[] = $matches[1];

					if (count($arUsersFromPOST) > 0)
					{
						$dbUsers = CUser::GetList(
							($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')),
							($dummy=''),
							array(
								"ID" => implode("|", $arUsersFromPOST),
								array(
									"FIELDS" => array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION")
								)
							)
						);
						while($arGratUser = $dbUsers->Fetch())
						{
							$arResult["PostToShow"]["GRAT_CURRENT"]["USERS"][] = $arGratUser["ID"];

							$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arGratUser));
							$arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"]["U".$arGratUser["ID"]] = array(
								"id" => "U".$arGratUser["ID"],
								"entityId" => $arGratUser["ID"],
								"name" => $sName,
								"avatar" => "",
								"desc" => $arGratUser["WORK_POSITION"] ? $arGratUser["WORK_POSITION"] : ($arGratUser["PERSONAL_PROFESSION"] ? $arGratUser["PERSONAL_PROFESSION"] : "&nbsp;")
							);
						}
					}
				}

				if (
					array_key_exists("GRAT_TYPE", $_POST)
					&& strlen($_POST["GRAT_TYPE"]) > 0
					&& is_array($arResult["PostToShow"]["GRATS"])
				)
					foreach ($arResult["PostToShow"]["GRATS"] as $arGrat)
					{
						if ($arGrat["XML_ID"] == $_POST["GRAT_TYPE"])
						{
							$arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"] = $arGrat;
							break;
						}
					}
			}

			if($_REQUEST["moder"] == "y")
				$arResult["OK_MESSAGE"] = GetMessage("BPE_HIDDEN_POSTED");
		}

		$arResult["Smiles"] = CBlogSmile::GetSmilesList();
		$arResult["SmilesCount"] = count($arSmileTmp["Smiles"]);

		$arResult["Images"] = Array();
		if(!empty($arBlog) && ($arPost["ID"] > 0 || strlen($arResult["ERROR_MESSAGE"]) > 0))
		{
			$arFilter = array(
					"POST_ID" => $arParams["ID"],
					"BLOG_ID" => $arBlog["ID"],
					"IS_COMMENT" => "N",
				);
			if ($arParams["ID"]==0)
				$arFilter["USER_ID"] = $arResult["UserID"];

			$res = CBlogImage::GetList(array("ID"=>"ASC"), $arFilter);
			while($aImg = $res->Fetch())
			{
				$aImgNew = CFile::ResizeImageGet(
					$aImg["FILE_ID"],
					array("width" => 90, "height" => 90),
					BX_RESIZE_IMAGE_EXACT,
					true
				);
				$aImgNew["source"] = CFile::ResizeImageGet(
					$aImg["FILE_ID"],
					array("width" => $arParams["IMAGE_MAX_WIDTH"], "height" => $arParams["IMAGE_MAX_HEIGHT"]),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				);
				$aImgNew["ID"] = $aImg["ID"];
				$aImgNew["params"] = CFile::_GetImgParams($aImg["FILE_ID"]);
				$aImgNew["fileName"] = substr($aImgNew["src"], strrpos($aImgNew["src"], "/")+1);
				$aImgNew["fileShow"] = "<img src=\"".$aImgNew["src"]."\" width=\"".$aImgNew["width"]."\" height=\"".$aImgNew["height"]."\" border=\"0\" style=\"cursor:pointer\" onclick=\"InsertBlogImage_LHEPostFormId_blogPostForm('".$aImg["ID"]."', '".$aImgNew["source"]['src']."', '".$aImgNew["source"]['width']."');\" title=\"".GetMessage("BLOG_P_INSERT")."\">";
				$aImgNew["SRC"] = $aImgNew["source"]["src"];

				$aImgNew["FILE_NAME"] = $aImgNew["fileName"];
				$aImgNew["FILE_SIZE"] = $aImgNew["source"]["size"];
				$aImgNew["URL"] = $aImgNew["src"];
				$aImgNew["CONTENT_TYPE"] = "image/xyz";
				$aImgNew["THUMBNAIL"] = $aImgNew["src"];
				$aImgNew["DEL_URL"] = $APPLICATION->GetCurPageParam(
					"del_image_id=".$aImg["ID"]."&".bitrix_sessid_get(),
					Array("sessid", "image_upload_frame", "image_upload", "do_upload","del_image_id"));
				$arResult["Images"][] = $aImgNew;
			}
		}

		if(strpos($arResult["PostToShow"]["CATEGORY_ID"], ",")!==false)
			$arResult["PostToShow"]["CATEGORY_ID"] = explode(",", trim($arResult["PostToShow"]["CATEGORY_ID"]));

		$arResult["Category"] = Array();

		if(strlen($arResult["PostToShow"]["CategoryText"]) <= 0 && !empty($arResult["PostToShow"]["CATEGORY_ID"]))
		{
			$res = CBlogCategory::GetList(array("NAME"=>"ASC"),array("BLOG_ID"=>$arBlog["ID"]));
			while ($arCategory=$res->GetNext())
			{
				if (is_array($arResult["PostToShow"]["CATEGORY_ID"]))
				{
					if (in_array($arCategory["ID"], $arResult["PostToShow"]["CATEGORY_ID"]))
					{
						$arCategory["Selected"] = "Y";
					}
				}
				elseif (IntVal($arCategory["ID"]) == IntVal($arResult["PostToShow"]["CATEGORY_ID"]))
				{
					$arCategory["Selected"] = "Y";
				}

				if ($arCategory["Selected"] == "Y")
				{
					$arResult["PostToShow"]["CategoryText"] .= $arCategory["~NAME"].",";
				}

				$arResult["Category"][$arCategory["ID"]] = $arCategory;
			}
			$arResult["PostToShow"]["CategoryText"] = substr($arResult["PostToShow"]["CategoryText"], 0, strlen($arResult["PostToShow"]["CategoryText"])-1);
		}

		foreach ($arParams["POST_PROPERTY"] as $FIELD_NAME)
		{
			$arPostField = $arPostFields[$FIELD_NAME];
			if (!!$arPostField)
			{
				if (
					!empty($arResult["ERROR_MESSAGE"])
					&& !empty($_POST[$FIELD_NAME])
				)
				{
					$arPostField["VALUE"] = $_POST[$FIELD_NAME];
				}

				$arPostField["~EDIT_FORM_LABEL"] = ($arPostField["EDIT_FORM_LABEL"] !== "" ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"]);
				$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["~EDIT_FORM_LABEL"]);
				$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;
				$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
			}
		}

		if(
			isset($_REQUEST["WFILES"])
			&& !empty($_REQUEST["WFILES"])
			&& is_array($_REQUEST["WFILES"])
			&& !$_POST["save"]
		)
		{
			$isDiskProperty = (
				isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]['USER_TYPE_ID'])
				&& $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]['USER_TYPE_ID'] === 'disk_file'
			);

			foreach($_REQUEST["WFILES"] as $val)
			{
				$val = intval($val);
				if($val <= 0)
				{
					continue;
				}
				if($isDiskProperty)
				{
					//@see Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX
					$val = 'n' . $val;
				}
				$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"][] = $val;
			}
			if(!empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"]))
			{
				$arResult["needShow"] = true;
			}
		}

		$arResult["urlToDelImage"] = $APPLICATION->GetCurPageParam("del_image_id=#del_image_id#&".bitrix_sessid_get(), Array("sessid", "image_upload_frame", "image_upload", "do_upload","del_image_id"));

		$serverName = "";
		$dbSite = CSite::GetByID(SITE_ID);
		$arSite = $dbSite->Fetch();
		$serverName = $arSite["SERVER_NAME"];
		if (strLen($serverName) <=0)
		{
			$serverName = (
				defined("SITE_SERVER_NAME")
				&& strlen (SITE_SERVER_NAME) > 0
					? SITE_SERVER_NAME
					: COption::GetOptionString("main", "server_name", "www.bitrixsoft.com")
			);

			if (strLen($serverName) <= 0)
			{
				$serverName = $_SERVER["HTTP_HOST"];
			}
		}
		$serverName = "http://".$serverName;

		$arResult["PATH_TO_POST"] = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("blog" => $arBlog["URL"], "post_id" => "#post_id#", "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
		$arResult["PATH_TO_POST1"] = $serverName.substr($arResult["PATH_TO_POST"], 0, strpos($arResult["PATH_TO_POST"], "#post_id#"));
		$arResult["PATH_TO_POST2"] = substr($arResult["PATH_TO_POST"], strpos($arResult["PATH_TO_POST"], "#post_id#") + strlen("#post_id#"));
	}

	CJSCore::Init(array('socnetlogdest'));
	// socialnetwork
	$arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'] = CSocNetLogDestination::GetLastSocnetGroup();

	$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
	$cacheId = 'blog_post_form_dest_'.SITE_ID.'_'.$user_id;
	$cacheDir = '/blog/form/dest/'.SITE_ID.'/'.$user_id;

	$obCache = new CPHPCache;
	if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
	{
		$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] = $obCache->GetVars();
	}
	else
	{
		$obCache->StartDataCache();
		$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] = CSocNetLogDestination::GetSocnetGroup(Array('features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"))));
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->StartTagCache($cacheDir);
			foreach($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] as $val)
			{
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_features_G_".$val["entityId"]);
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$val["entityId"]);
			}
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$user_id);
			$GLOBALS["CACHE_MANAGER"]->EndTagCache();
		}
		$obCache->EndDataCache($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS']);
	}

	$arDestUser = Array();
	$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'] = Array();

	if (empty($arResult["PostToShow"]["SPERM"]))
	{
		if ($arResult["bExtranetUser"])
		{
			if(!empty($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS']))
			{
				foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'] as $val)
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'][$val] = "sonetgroups";
				}
			}
			else
			{
				foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] as $k => $val)
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'][$k] = "sonetgroups";
				}
			}

			if (empty($arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']))
			{
				$arResult["FATAL_MESSAGE"] .= GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE");
			}

		}
		elseif ($bDefaultToAll)
		{
			if (IsModuleInstalled("intranet"))
			{
				$siteDepartmentID = COption::GetOptionString("main", "wizard_departament", false, SITE_ID, true);
				if (intval($siteDepartmentID) > 0)
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['DR'.intval($siteDepartmentID)] = 'department';
				}
				else
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
				}
			}
			else
			{
				$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
			}
		}
	}
	else
	{
		foreach ($arResult["PostToShow"]["SPERM"] as $type => $ar)
		{
			if(is_array($ar))
			{
				foreach ($ar as $value => $ar2)
				{
					if (
						$type == 'U' 
						&& $value == 'A' 
						&& (
							$bDefaultToAll
							|| $arParams["ID"] > 0
						)
					)
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
					}
					elseif ($type == 'U')
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['U'.$value] = 'users';
						$arDestUser[] = $value;
					}
					elseif ($type == 'SG')
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['SG'.$value] = 'sonetgroups';
					}
					elseif ($type == 'DR')
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['DR'.$value] = 'department';
					}
				}
			}
		}
	}

	$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"] = array();
	$arHiddenGroups = array();
	if(!empty($arResult["PostToShow"]["FEED_DESTINATION"]["SELECTED"]))
	{
		foreach($arResult["PostToShow"]["FEED_DESTINATION"]["SELECTED"] as $gID => $value)
		{
			if($value == "sonetgroups" && empty($arResult["PostToShow"]["FEED_DESTINATION"]["SONETGROUPS"][$gID]))
			{
				$arHiddenGroups[] = substr($gID, 2);
			}
		}
	}

	if(!empty($arHiddenGroups))
	{
		$rsGroup = CSocNetGroup::GetList(
			array(),
			array("ID" => $arHiddenGroups),
			false,
			false,
			array("ID", "NAME")
		);
		while($arGroup = $rsGroup->Fetch())
		{
			$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"][$arGroup["ID"]] = array("ID" => $arGroup["ID"], "NAME" => $arGroup["NAME"]);
		}

		if (!CSocNetUser::IsCurrentUserModuleAdmin() && is_object($GLOBALS["USER"]))
		{
			$arGroupID = CSocNetLogTools::GetAvailableGroups(
				($arResult["bExtranetUser"] ? "Y" : "N"),
				($arResult["bExtranetSite"] ? "Y" : "N")
			);

			foreach($arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"] as $group_code => $arBlogSPerm)
			{
				if (!in_array($group_code, $arGroupID))
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"][$group_code]["NAME"] = GetMessage("B_B_HIDDEN_GROUP");
				}
			}
		}
	}

	// intranet structure
	$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
	$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT'] = $arStructure['department'];
	$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
	$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

	$arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['DEPARTMENT'] = CSocNetLogDestination::GetLastDepartment();

	// users
	$arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['USERS'] = CSocNetLogDestination::GetLastUser();

	if ($arResult["bExtranetUser"])
	{
		$arResult["PostToShow"]["FEED_DESTINATION"]['EXTRANET_USER'] = 'Y';
		$arResult["PostToShow"]["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetExtranetUser();
	}
	else
	{
		foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['USERS'] as $value)
			$arDestUser[] = str_replace('U', '', $value);

		$arResult["PostToShow"]["FEED_DESTINATION"]['EXTRANET_USER'] = 'N';
		$arResult["PostToShow"]["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $arDestUser));
	}

	$arResult["PostToShow"]["FEED_DESTINATION"]["DENY_TOALL"] = !$bAllowToAll;
}
else
{
	$arResult["FATAL_MESSAGE"] = GetMessage("BLOG_ERR_NO_RIGHTS");
}

if (
	(
		strlen($arResult["ERROR_MESSAGE"]) > 0
		|| strlen($arResult["FATAL_MESSAGE"]) > 0
	)
	&& $arParams["IS_REST"] == "Y"
)
{
	$APPLICATION->ThrowException((strlen($arResult["ERROR_MESSAGE"]) > 0 ? $arResult["ERROR_MESSAGE"] : $arResult["FATAL_MESSAGE"]), "BLOG_EDIT_COMPONENT_ERROR");
	return false;
}

CSocNetTools::InitGlobalExtranetArrays();

$this->IncludeComponentTemplate();
?>