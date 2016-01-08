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
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}
if (!CModule::IncludeModule("socialnetwork"))
	return;

if (!isset($arParams["CHECK_PERMISSIONS_DEST"]) || strLen($arParams["CHECK_PERMISSIONS_DEST"]) <= 0)
	$arParams["CHECK_PERMISSIONS_DEST"] = "N";

$arResult["bFromList"] = ($arParams["FROM_LOG"] == "Y" || $arParams["TYPE"] == "DRAFT" || $arParams["TYPE"] == "MODERATION");
$arResult["bExtranetInstalled"] = CModule::IncludeModule("extranet");
$arResult["bExtranetSite"] = ($arResult["bExtranetInstalled"] && CExtranet::IsExtranetSite());
$arResult["bExtranetUser"] = ($arResult["bExtranetInstalled"] && !CExtranet::IsIntranetUser());

if ($arResult["bExtranetUser"])
{
	$arUserIdVisible = CExtranet::GetMyGroupsUsersSimple(SITE_ID);
}

if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(IntVal($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

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

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = "/company/personal/user/#user_id#/blog/#post_id#/";

$arParams["PATH_TO_POST_IMPORTANT"] = trim($arParams["PATH_TO_POST_IMPORTANT"]);
if(strlen($arParams["PATH_TO_POST_IMPORTANT"])<=0)
	$arParams["PATH_TO_POST_IMPORTANT"] = "/company/personal/user/#user_id#/blog/important/";

$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#"."&category=#category_id#");

$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = "/company/personal/user/#user_id#/blog/edit/#post_id#/";

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

if(strlen($arParams["PATH_TO_SEARCH_TAG"])<=0)
	$arParams["PATH_TO_SEARCH_TAG"] = SITE_DIR."search/?tags=#tag#";

$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

if (!isset($arParams["PATH_TO_CONPANY_DEPARTMENT"]) || strlen($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <= 0)
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
if (!isset($arParams["PATH_TO_MESSAGES_CHAT"]) || strlen($arParams["PATH_TO_MESSAGES_CHAT"]) <= 0)
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";
if (!isset($arParams["PATH_TO_VIDEO_CALL"]) || strlen($arParams["PATH_TO_VIDEO_CALL"]) <= 0)
	$arParams["PATH_TO_VIDEO_CALL"] = "/company/personal/video/#user_id#/";

$arParams["CACHE_TIME"] = 3600*24*365;

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";
$arParams['DATE_TIME_FORMAT_S'] = $arParams['DATE_TIME_FORMAT'];
$arParams["DATE_TIME_FORMAT"] = trim(!empty($arParams['DATE_TIME_FORMAT']) ? ($arParams['DATE_TIME_FORMAT'] == 'FULL' ? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) : $arParams['DATE_TIME_FORMAT']) : $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME));
// activation rating
CRatingsComponentsMain::GetShowRating($arParams);
$arParams["USE_CUT"] = $arParams["USE_CUT"] == "Y" ? "Y" : "N";

$arParams["IMAGE_MAX_WIDTH"] = IntVal($arParams["IMAGE_MAX_WIDTH"]);
$arParams["IMAGE_MAX_HEIGHT"] = IntVal($arParams["IMAGE_MAX_HEIGHT"]);
if(IntVal($arParams["IMAGE_MAX_WIDTH"]) <= 0)
	$arParams["IMAGE_MAX_WIDTH"] = COption::GetOptionString("blog", "image_max_width", 600);
if(IntVal($arParams["IMAGE_MAX_HEIGHT"]) <= 0)
	$arParams["IMAGE_MAX_HEIGHT"] = COption::GetOptionString("blog", "image_max_height", 600);

$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]) : 70);
$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]) : 1000);
$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"] = (IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) > 0 ? IntVal($arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"]) : 1000);

$arParams["AVATAR_SIZE_COMMON"] = (isset($arParams["AVATAR_SIZE_COMMON"]) && intval($arParams["AVATAR_SIZE_COMMON"]) > 0) ? intval($arParams["AVATAR_SIZE_COMMON"]) : 58;
$arParams["AVATAR_SIZE"] = (isset($arParams["AVATAR_SIZE"]) && intval($arParams["AVATAR_SIZE"]) > 0) ? intval($arParams["AVATAR_SIZE"]) : 50;
$arParams["AVATAR_SIZE_COMMENT"] = (isset($arParams["AVATAR_SIZE_COMMENT"]) && intval($arParams["AVATAR_SIZE_COMMENT"]) > 0) ? intval($arParams["AVATAR_SIZE_COMMENT"]) : 39;

$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";
$arParams["SMILES_COUNT"] = IntVal($arParams["SMILES_COUNT"]);

if(empty($arParams["POST_PROPERTY"]))
	$arParams["POST_PROPERTY"] = array();

$arParams["POST_PROPERTY_SOURCE"] = $arParams["POST_PROPERTY"];

$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_DOC";
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_IMPRTNT";
if(CModule::IncludeModule("webdav") || CModule::IncludeModule("disk"))
{
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_FILE";
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_D_FILE";
	// $arParams["POST_PROPERTY"][] = "UF_BLOG_POST_F_EDIT";
}
if(IsModuleInstalled("vote"))
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_VOTE";
if(IsModuleInstalled("intranet"))
	$arParams["POST_PROPERTY"][] = "UF_GRATITUDE";

if (!array_key_exists("GET_FOLLOW", $arParams) || strLen($arParams["GET_FOLLOW"]) <= 0)
	$arParams["GET_FOLLOW"] = "N";

if(defined("DisableSonetLogFollow") && DisableSonetLogFollow === true)
	$arParams["GET_FOLLOW"] = "N";

$user_id = IntVal($USER->GetID());
$arResult["USER_ID"] = $user_id;
$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();

if(!$arResult["bFromList"])
{
	$arParams["USE_CUT"] = "N";

	$arFilterblg = Array(
			"ACTIVE" => "Y",
			"USE_SOCNET" => "Y",
			"GROUP_ID" => $arParams["GROUP_ID"],
			"GROUP_SITE_ID" => SITE_ID,
			"OWNER_ID" => $arParams["USER_ID"],
		);

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
					$arIdeaBlogGroupID[] = COption::GetOptionInt("idea", "blog_group_id", false, $arSite["LID"]);
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

	if($GLOBALS["USER"]->IsAuthorized())
	{
		CSocNetTools::InitGlobalExtranetArrays();
		if (isset($GLOBALS["arExtranetGroupID"]))
		{
			$arResult["arExtranetGroupID"] = $GLOBALS["arExtranetGroupID"];
		}
	}
}

$arParams["ID"] = trim($arParams["ID"]);
if(preg_match("/^[1-9][0-9]*\$/", $arParams["ID"]))
{
	$arParams["ID"] = IntVal($arParams["ID"]);
}
else
{
	$arParams["ID"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["~ID"]));
	$arParams["ID"] = CBlogPost::GetID($arParams["ID"], $arBlog["ID"]);
}

if($arParams["ID"] == "" && !$arResult["bFromList"])
{
	ShowError(GetMessage("B_B_MES_NO_POST"));
	@define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	return;
}

$arPost = array();
$cacheTtl = 2592000;
$cacheId = 'blog_post_socnet_general_'.$arParams["ID"].'_'.LANGUAGE_ID;
if($arResult["TZ_OFFSET"] <> 0)
	$cacheId .= "_".$arResult["TZ_OFFSET"];
$cacheDir = '/blog/socnet_post/gen/'.intval($arParams["ID"] / 100).'/'.$arParams["ID"];

$obCache = new CPHPCache;
if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
{
	$arPost = $obCache->GetVars();
}
else
{
	$obCache->StartDataCache();

	$dbPost = CBlogPost::GetList(array(), array("ID" => $arParams["ID"]), false, false, array("ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_COMMENT_IMAGES"));
	$arPost = $dbPost->Fetch();

	$obCache->EndDataCache($arPost);
}

if(!empty($arPost) && ($arPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH && !in_array($arParams["TYPE"], array("DRAFT", "MODERATION"))))
	unset($arPost);

$a = new CAccess;
$a->UpdateCodes();

if(
	(!empty($arBlog) && $arBlog["ACTIVE"] == "Y")
	|| $arResult["bFromList"]
)
{
	if(!empty($arPost))
	{
		if (
			(
				(
					$arParams["GET_FOLLOW"] == "Y"
					&& (!array_key_exists("FOLLOW", $arParams) || strlen($arParams["FOLLOW"]) <= 0)
				)
				|| intval($arParams["LOG_ID"]) <= 0
			)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$rsLogSrc = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => array("blog_post", "blog_post_micro", "blog_post_important"),
					"SOURCE_ID" => $arParams["ID"]
				),
				false,
				false,
				($arParams["GET_FOLLOW"] == "Y" ? array("ID", "FOLLOW", "FAVORITES_USER_ID") : array("ID", "FAVORITES_USER_ID")),
				($arParams["GET_FOLLOW"] == "Y" ? array("USE_FOLLOW" => "Y") : array())
			);
			if ($arLogSrc = $rsLogSrc->Fetch())
			{
				$arParams["LOG_ID"] = $arLogSrc["ID"];
				$arParams["FAVORITES_USER_ID"] = $arLogSrc["FAVORITES_USER_ID"];
				if ($arParams["GET_FOLLOW"] == "Y")
				{
					$arParams["FOLLOW"] = $arLogSrc["FOLLOW"];
				}
			}
		}

		if (!$arResult["bFromList"])
		{
			CBlogPost::CounterInc($arPost["ID"]);
		}

		$arPost = CBlogTools::htmlspecialcharsExArray($arPost);
		if($arPost["AUTHOR_ID"] == $user_id)
		{
			$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_FULL;
		}
		elseif($arResult["bFromList"])
		{
			$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_READ;
			if (
				CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, (!isset($arParams["MOBILE"]) || $arParams["MOBILE"] != "Y"))
				|| $APPLICATION->GetGroupRight("blog") >= "W"
			)
			{
				$arPost["perms"] = $arResult["PostPerm"] = BLOG_PERMS_FULL;
			}
		}
		else
		{
			$arPost["perms"] = $arResult["PostPerm"] = CBlogPost::GetSocNetPostPerms($arPost["ID"], true, false, $arPost["AUTHOR_ID"]);
		}

		$arResult["Post"] = $arPost;
		$arResult["PostSrc"] = $arPost;
		$arResult["Blog"] = $arBlog;
		$arResult["PostSrc"]["PATH_TO_CONPANY_DEPARTMENT"] = $arParams["PATH_TO_CONPANY_DEPARTMENT"];
		$arResult["PostSrc"]["PATH_TO_GROUP"] = $arParams["PATH_TO_GROUP"];
		$arResult["PostSrc"]["bExtranetSite"] = $arResult["bExtranetSite"];

		$arResult["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id"=>CBlogPost::GetPostID($arResult["Post"]["ID"], $arResult["Post"]["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));

		if (is_set($arParams["PATH_TO_GROUP"]))
		{
			$strSiteWorkgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", SITE_DIR."workgroups/", SITE_ID);
			if (strlen($strSiteWorkgroupsPage) > 0)
			{
				if (strpos($arParams["PATH_TO_GROUP"], $strSiteWorkgroupsPage) === 0)
				{
					$arParams["PATH_TO_GROUP"] = "#GROUPS_PATH#".substr($arParams["PATH_TO_GROUP"], strlen($strSiteWorkgroupsPage), strlen($arParams["PATH_TO_GROUP"]) - strlen($strSiteWorkgroupsPage));
				}
			}
		}

		if ($_GET["delete"]=="Y" && !$arResult["bFromList"])
		{
			if (check_bitrix_sessid())
			{
				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					CBlogPost::DeleteLog($arParams["ID"]);

					if (CBlogPost::Delete($arParams["ID"]))
					{
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arBlog["SOCNET_GROUP_ID"]));
						if(strpos($url, "?") === false)
							$url .= "?";
						else
							$url .= "&";
						$url .= "del_id=".$arParams["ID"]."&success=Y";
						BXClearCache(true, "/blog/socnet_post/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
						BXClearCache(true, "/blog/socnet_post/gen/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");

						LocalRedirect($url);
					}
					else
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR").'<br />';
				}
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS").'<br />';
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG").'<br />';
		}
		if ($_GET["hide"]=="Y" && !$arResult["bFromList"])
		{
			if (check_bitrix_sessid())
			{
				if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
				{
					if(CBlogPost::Update($arParams["ID"], Array("PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_READY)))
					{
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						BXClearCache(true, "/blog/socnet_post/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
						BXClearCache(true, "/blog/socnet_post/gen/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
						CBlogPost::DeleteLog($arParams["ID"]);
						$url = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
						if(strpos($url, "?") === false)
							$url .= "?";
						else
							$url .= "&";
						$url .= "hide_id=".$arParams["ID"]."&success=Y";

						LocalRedirect($url);
					}
					else
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_ERROR").'<br />';
				}
				else
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_HIDE_NO_RIGHTS").'<br />';
			}
			else
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG").'<br />';
		}

		if($arResult["PostPerm"] > BLOG_PERMS_DENY)
		{
			/* share */
			if(
				$_SERVER["REQUEST_METHOD"] == "POST" 
				&& $_POST["act"] == "share" 
				&& check_bitrix_sessid() 
				&& $USER->IsAuthorized()
			)
			{
				$APPLICATION->RestartBuffer();

				$spermNew = $_POST["SPERM"];
				$spermOld = CBlogPost::GetSocNetPerms($arParams["ID"]);
				$perms2update = array();
				$arNewRights = array();

				foreach($spermOld as $type => $val)
				{
					foreach($val as $id => $values)
					{
						if($type != "U")
							$perms2update[] = $type.$id;
						else
						{
							if(in_array("US".$id, $values))
								$perms2update[] = "UA";
							else
								$perms2update[] = $type.$id;
						}
					}
				}
				foreach($spermNew as $type => $val)
				{
					foreach($val as $id => $values)
					{
						if(in_array($type, array("U", "SG", "DR")))
						{
							if(!in_array($values, $perms2update))
							{
								$perms2update[] = $values;
								$arNewRights[] = $values;
							}
						}
						elseif($type == "UA")
						{
							if(!in_array("UA", $perms2update))
							{
								$perms2update[] = "UA";
								$arNewRights[] = "UA";
							}
						}
					}
				}

				if(!empty($arNewRights))
				{
					if(CBlogPost::Update($arParams["ID"], array("SOCNET_RIGHTS" => $perms2update, "HAS_SOCNET_ALL" => "N")))
					{
						BXClearCache(true, "/blog/socnet_post/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
						BXClearCache(true, "/blog/socnet_post/gen/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");
						BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
						
						$arLogSitesNew = array();
						$arNewRightsName = array();
						$arUsers2Notify = array();
						$arSPERM = CBlogPost::GetSocnetPermsName($arResult["Post"]["ID"]);

						if ($arResult["bExtranetInstalled"])
						{
							$arIntranetUserID = CExtranet::GetIntranetUsers();
						}

						foreach($arSPERM as $type => $v)
						{
							foreach($v as $vv)
							{
								$name = "";
								$link = "";
								$id = "";
								if (
									$type == "SG" 
									&& in_array($type.$vv["ENTITY_ID"], $arNewRights)
								)
								{
									if($arSocNetGroup = CSocNetGroup::GetByID($vv["ENTITY_ID"]))
									{
										$name = $arSocNetGroup["NAME"];
										$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $vv["ENTITY_ID"]));

										$groupSiteID = false;

										$rsGroupSite = CSocNetGroup::GetSite($vv["ENTITY_ID"]);
										while ($arGroupSite = $rsGroupSite->Fetch())
										{
											$arLogSitesNew[] = $arGroupSite["LID"];
											if (
												!$groupSiteID
												&& (
													!$arResult["bExtranetInstalled"]
													|| $arGroupSite["LID"] != CExtranet::GetExtranetSiteID()
												)
											)
											{
												$groupSiteID = $arGroupSite["LID"];
											}
										}

										if ($groupSiteID)
										{
											$arTmp = CSocNetLogTools::ProcessPath(array("GROUP_URL" => $link), $user_id, $groupSiteID); // user_id is not important parameter
											$link = (strlen($arTmp["URLS"]["GROUP_URL"]) > 0 ? $arTmp["SERVER_NAME"].$arTmp["URLS"]["GROUP_URL"] : $link);
										}
									}
								}
								elseif ($type == "U")
								{
									if(in_array("US".$vv["ENTITY_ID"], $vv["ENTITY"]) && in_array("UA", $arNewRights))
									{
										$name = (IsModuleInstalled("intranet") ? GetMessage("B_B_SHARE_ALL") : GetMessage("B_B_SHARE_ALL_BUS"));

										if (
											!$arResult["bExtranetSite"] 
											&& defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL")
										)
										{
											$link = BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL;
										}
									}
									elseif(in_array($type.$vv["ENTITY_ID"], $arNewRights))
									{
										$arTmpUser = array(
											"NAME" => $vv["~U_NAME"],
											"LAST_NAME" => $vv["~U_LAST_NAME"],
											"SECOND_NAME" => $vv["~U_SECOND_NAME"],
											"LOGIN" => $vv["~U_LOGIN"],
											"NAME_LIST_FORMATTED" => "",
										);
										$name = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
										$id = $vv["ENTITY_ID"];
										$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $vv["ENTITY_ID"]));
										$arUsers2Notify[] = $vv["ENTITY_ID"];

										if (
											$arResult["bExtranetInstalled"]
											&& is_array($arIntranetUserID)
											&& !in_array($vv["ENTITY_ID"], $arIntranetUserID)
										)
										{
											$arLogSitesNew[] = CExtranet::GetExtranetSiteID();
										}
									}
								}
								elseif($type == "DR" && in_array($type.$vv["ENTITY_ID"], $arNewRights))
								{
									$name = $vv["EL_NAME"];
									$id = $vv["ENTITY_ID"];
									$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $vv["ENTITY_ID"]));
								}

								if(strlen($name) > 0)
								{
									if(strlen($link) > 0)
									{
										if($type == "U" && IntVal($id) > 0)
											$arNewRightsName[] = "[user=".$id."]".htmlspecialcharsback($name)."[/user]";
										else
											$arNewRightsName[] = "[url=".$link."]".htmlspecialcharsback($name)."[/url]";
									}
									else
										$arNewRightsName[] = htmlspecialcharsback($name);
								}
							}
						}

						$UserIP = CBlogUser::GetUserIP();
						$arComFields = Array(
							"POST_ID" => $arParams["ID"],
							"BLOG_ID" => $arPost["BLOG_ID"],
							"POST_TEXT" => (count($arNewRightsName) > 1 ? GetMessage("B_B_SHARE") : GetMessage("B_B_SHARE_1")).implode(", ", $arNewRightsName),
							"DATE_CREATE" => ConvertTimeStamp(time() + $arResult["TZ_OFFSET"], "FULL"),
							"AUTHOR_IP" => $UserIP[0],
							"AUTHOR_IP1" => $UserIP[1],
							"PARENT_ID" => false,
							"AUTHOR_ID" => $user_id,
							"SHARE_DEST" => implode(",", $arNewRights),
						);
						if($comId = CBlogComment::Add($arComFields))
						{
							BXClearCache(true, "/blog/comment/".intval($arParams["ID"] / 100)."/".$arParams["ID"]."/");

							if (
								CModule::IncludeModule("pull")
								&& CPullOptions::GetNginxStatus()
								&& ($arComment = CBlogComment::GetByID($comId))
							)
							{
								$arAuthor = CBlogUser::GetUserInfo(
									$arComment["AUTHOR_ID"], 
									$arParams["PATH_TO_USER"], 
									array(
										"AVATAR_SIZE" => (isset($arParams["AVATAR_SIZE_COMMON"]) 
											? $arParams["AVATAR_SIZE_COMMON"] 
											: $arParams["AVATAR_SIZE"]
										),
										"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"]
									)
								);

								$arPullFields = array(
									"ID" => $arComment["ID"],
									"ENTITY_XML_ID"	=> "BLOG_".$arComment["POST_ID"],
									"FULL_ID" => array(
											"BLOG_".$arComment["POST_ID"],
											$arComment["ID"]
									),
									"NEW" => "N",
									"APPROVED" => "Y",
									"POST_TIMESTAMP" => time() + $arResult["TZ_OFFSET"],
									"PANELS" => array
									(
										"EDIT" => "N",
										"MODERATE" => "N",
										"DELETE" => "N"
									),
									"URL" => array(
										"LINK" => $arResult['urlToPost'].(strpos($arResult['urlToPost'], "?") !== false ? "&" : "?")."commentId=".$arComment["ID"]."#com".$arComment["ID"]
									),
									"AUTHOR" => array(
										"ID" => $arComment["AUTHOR_ID"],
										"NAME" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arAuthor, ($arParams["SHOW_LOGIN"] != "N" ? true : false)),
										"URL" => $arAuthor["url"],
										"AVATAR" => $arAuthor["PERSONAL_PHOTO_resized"]["src"],
										"IS_EXTRANET" => (
											is_array($GLOBALS["arExtranetUserID"]) 
											&& in_array($arComment["AUTHOR_ID"], $GLOBALS["arExtranetUserID"]) 
										)
									),
									"ACTION" => "REPLY"
								);

								$p = new blogTextParser(false, "");
								$arPullFields["POST_MESSAGE_TEXT"] = $p->convert($arComment["POST_TEXT"], false, array(), array("HTML" => "N"), array("pathToUser" => $arParams["PATH_TO_USER"]));

								if (IsModuleInstalled("mobile"))
								{
									$p->bMobile = true;
									$arPullFields["POST_MESSAGE_TEXT_MOBILE"] = $p->convert($arComment["POST_TEXT"], false, array(), array("HTML" => "N"), array("pathToUser" => "/mobile/users/?user_id=#user_id#"));
								}

								$arPullFields["POST_TIME"] = FormatDateFromDB(
									$arComment["DATE_CREATE"],
									(
										strpos($arParams["DATE_TIME_FORMAT_S"], 'a') !== false
										|| (
											$arParams["DATE_TIME_FORMAT_S"] == 'FULL'
											&& IsAmPmMode()
										) !== false
											? (strpos(FORMAT_DATETIME, 'TT') !== false ? 'G:MI TT': 'G:MI T')
											: 'GG:MI'
									)
								);

								$arPullFields["POST_DATE"] = FormatDateFromDB($arComment["DATE_CREATE"], $arParams["DATE_TIME_FORMAT"], true);
								if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
								{
									$arPullFields["POST_DATE"] = ToLower($arPullFields["POST_DATE"]);
								}
								if (
									!empty($arParams['DATE_TIME_FORMAT_S']) 
									&& (
										$arParams['DATE_TIME_FORMAT_S'] == 'j F Y G:i' 
										|| $arParams['DATE_TIME_FORMAT_S'] == 'j F Y g:i a'
									)
								)
								{
									$arPullFields["POST_DATE"] = ltrim($arPullFields["POST_DATE"], '0');
									$curYear = date('Y');
									$arPullFields["POST_DATE"] = str_replace(
										array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), 
										'', 
										$arPullFields["POST_DATE"]
									);
								}

								if ($arParams["SHOW_RATING"] == "Y")
								{
									$arRating = CRatings::GetRatingVoteResult('BLOG_COMMENT', $arComment["ID"]);
								
									ob_start();
									$GLOBALS["APPLICATION"]->IncludeComponent(
										"bitrix:rating.vote", 
										$arParams["RATING_TYPE"],
										Array(
											"ENTITY_TYPE_ID" => "BLOG_COMMENT",
											"ENTITY_ID" => $arComment["ID"],
											"OWNER_ID" => $arComment["AUTHOR_ID"],
											"USER_VOTE" => $arRating["USER_VOTE"],
											"USER_HAS_VOTED" =>$arRating["USER_HAS_VOTED"],
											"TOTAL_VOTES" => $arRating["TOTAL_VOTES"],
											"TOTAL_POSITIVE_VOTES" => $arRating["TOTAL_POSITIVE_VOTES"],
											"TOTAL_NEGATIVE_VOTES" => $arRating["TOTAL_NEGATIVE_VOTES"],
											"TOTAL_VALUE" => $arRating["TOTAL_VALUE"],
											"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
									$arPullFields["BEFORE_ACTIONS"] = ob_get_clean();

									if (IsModuleInstalled("mobile"))
									{
										ob_start();
										$GLOBALS["APPLICATION"]->IncludeComponent(
											"bitrix:rating.vote",
											"mobile_comment_".$arParams["RATING_TYPE"],
											Array(
												"ENTITY_TYPE_ID" => "BLOG_COMMENT",
												"ENTITY_ID" => $arComment["ID"],
												"OWNER_ID" => $arComment["AUTHOR_ID"],
												"USER_VOTE" => $arRating["USER_VOTE"],
												"USER_HAS_VOTED" =>$arRating["USER_HAS_VOTED"],
												"TOTAL_VOTES" => $arRating["TOTAL_VOTES"],
												"TOTAL_POSITIVE_VOTES" => $arRating["TOTAL_POSITIVE_VOTES"],
												"TOTAL_NEGATIVE_VOTES" => $arRating["TOTAL_NEGATIVE_VOTES"],
												"TOTAL_VALUE" => $arRating["TOTAL_VALUE"],
												"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
											),
											null,
											array("HIDE_ICONS" => "Y")
										);
										$arPullFields["BEFORE_ACTIONS_MOBILE"] = ob_get_clean();
									}
								}

								CPullWatch::AddToStack(
									'UNICOMMENTSBLOG_'.$arComment["POST_ID"],
									Array(
										'module_id' => 'unicomments',
										'command' => 'comment',
										'params' => $arPullFields
									)
								);
							}

							if($arPost["AUTHOR_ID"] != $user_id)
							{
								$arFieldsIM = array(
									"TYPE" => "SHARE",
									"TITLE" => $arPost["TITLE"],
									"URL" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $arParams["ID"], "user_id" => $arPost["AUTHOR_ID"])),
									"ID" => $arParams["ID"],
									"FROM_USER_ID" => $user_id,
									"TO_USER_ID" => array($arPost["AUTHOR_ID"]),
								);
								CBlogPost::NotifyIm($arFieldsIM);
							}

							if(!empty($arUsers2Notify))
							{
								$arFieldsIM = array(
									"TYPE" => "SHARE2USERS",
									"TITLE" => $arPost["TITLE"],
									"URL" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $arParams["ID"], "user_id" => $arPost["AUTHOR_ID"])),
									"ID" => $arParams["ID"],
									"FROM_USER_ID" => $user_id,
									"TO_USER_ID" => $arUsers2Notify,
								);
								CBlogPost::NotifyIm($arFieldsIM);
							}
						}

						/* update socnet log rights*/
						$dbRes = CSocNetLog::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID" => array("blog_post", "blog_post_important"),
								"SOURCE_ID" => $arPost["ID"]
							),
							false,
							false,
							array("ID", "ENTITY_TYPE", "ENTITY_ID")
						);
						if ($arRes = $dbRes->Fetch())
						{
							$arLogSites = array();
							$rsLogSite = CSocNetLog::GetSite($arRes["ID"]);
							while ($arLogSite = $rsLogSite->Fetch())
							{
								$arLogSites[] = $arLogSite["LID"];
							}
							$arLogSitesNew = array_merge($arLogSitesNew, $arLogSites);

							$socnetPerms = CBlogPost::GetSocNetPermsCode($arPost["ID"]);
							if(!in_array("U".$arPost["AUTHOR_ID"], $socnetPerms))
								$socnetPerms[] = "U".$arPost["AUTHOR_ID"];
							$socnetPerms[] = "SA"; // socnet admin

							if (
								in_array("AU", $socnetPerms) 
								|| in_array("G2", $socnetPerms)
							)
							{
								$socnetPermsAdd = array();

								foreach($socnetPerms as $perm_tmp)
								{
									if (preg_match('/^SG(\d+)$/', $perm_tmp, $matches))
									{
										if (
											!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $socnetPerms)
											&& !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $socnetPerms)
											&& !in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $socnetPerms)
										)
											$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
									}
								}
								if (count($socnetPermsAdd) > 0)
									$socnetPerms = array_merge($socnetPerms, $socnetPermsAdd);
							}

							CSocNetLogRights::DeleteByLogID($arRes["ID"]);
							CSocNetLogRights::Add($arRes["ID"], $socnetPerms, true);

							if (count(array_diff($arLogSitesNew, $arLogSites)) > 0)
							{
								CSocNetLog::Update($arRes["ID"], array(
									"ENTITY_TYPE" => $arRes["ENTITY_TYPE"], // to use any real field
									"SITE_ID" => $arLogSitesNew,
									"=LOG_UPDATE" => $DB->CurrentTimeFunction(),
								));
							}
							else
							{
								CSocNetLog::Update($arRes["ID"], array(
									"=LOG_UPDATE" => $DB->CurrentTimeFunction(),
								));
							}

							/* subscribe share author */
							CSocNetLogFollow::Set(
								$user_id,
								"L".$arRes["ID"],
								"Y",
								ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL")
							);
						}

						/* update socnet groupd activity*/
						foreach($arNewRights as $v)
						{
							if(substr($v, 0, 2) == "SG")
							{
								$group_id_tmp = substr($v, 2);
								if(IntVal($group_id_tmp) > 0)
								{
									CSocNetGroup::SetLastActivity(IntVal($group_id_tmp));
								}
							}
						}
					}
				}

				die();
			}
			/* end share */
			if(!$arResult["bFromList"])
			{
				$strTitle = ($arPost["MICRO"] != "Y" ? $arPost["TITLE"] : blogTextParser::killAllTags($arPost["DETAIL_TEXT"]));

				if (IsModuleInstalled("intranet"))
				{
					$APPLICATION->SetPageProperty("title", $strTitle);
				}
				else
				{
					$APPLICATION->SetTitle($strTitle);
				}
			}

			if($arParams["SET_NAV_CHAIN"]=="Y")
				$APPLICATION->AddChainItem($arBlog["NAME"], CComponentEngine::MakePathFromTemplate(htmlspecialcharsback($arParams["PATH_TO_BLOG"]), array("blog" => $arBlog["URL"], "user_id" => $arPost["AUTHOR_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"])));

			$cache = new CPHPCache;

			$arCacheID = array();
			$arKeys = array(
				"MOBILE",
				"USE_CUT",
				"PATH_TO_SMILE",
				"ATTACHED_IMAGE_MAX_WIDTH_SMALL",
				"ATTACHED_IMAGE_MAX_HEIGHT_SMALL",
				"ATTACHED_IMAGE_MAX_WIDTH_FULL",
				"ATTACHED_IMAGE_MAX_HEIGHT_FULL",
				"POST_PROPERTY",
				"PATH_TO_USER",
				"PATH_TO_POST",
				"PATH_TO_GROUP",
				"PATH_TO_SEARCH_TAG",
				"IMAGE_MAX_WIDTH",
				"IMAGE_MAX_HEIGHT",
				"DATE_TIME_FORMAT",
				"DATE_TIME_FORMAT_S",
				"ALLOW_POST_CODE",
				"AVATAR_SIZE_COMMENT",
				"NAME_TEMPLATE",
				"SHOW_LOGIN"
			);
			foreach($arKeys as $param_key)
			{
				$arCacheID[$param_key] = (array_key_exists($param_key, $arParams) ? $arParams[$param_key] : false);
			}

			$cache_id = "blog_socnet_post_".md5(serialize($arCacheID))."_".LANGUAGE_ID."_".$arParams["DATE_TIME_FORMAT"];
			if ($arResult["TZ_OFFSET"] <> 0)
			{
				$cache_id .= "_".$arResult["TZ_OFFSET"];
			}

			if (
				!empty($arParams["MOBILE"])
				&& $arParams["MOBILE"] == "Y"
			)
			{
				$imageResizeWidth = CMobileHelper::getDeviceResizeWidth();
				if ($imageResizeWidth)
				{
					$cache_id .= "_".$imageResizeWidth;
				}
			}

			$cache_path = "/blog/socnet_post/".intval($arPost["ID"] / 100)."/".$arPost["ID"]."/";

			if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
			{
				$Vars = $cache->GetVars();
				$arResult["POST_PROPERTY"] = $Vars["POST_PROPERTY"];
				$arResult["Post"] = $Vars["Post"];
				$arResult["images"] = $Vars["images"];
				$arResult["Category"] = $Vars["Category"];
				$arResult["GRATITUDE"] = $Vars["GRATITUDE"];
				$arResult["POST_PROPERTIES"] = $Vars["POST_PROPERTIES"];
				$arResult["arUser"] = $Vars["arUser"];

				CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);
				$cache->Output();
			}
			else
			{
				if ($arParams["CACHE_TIME"] > 0)
				{
					$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($arPost["AUTHOR_ID"]));
					}
				}

				$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
				$p->bMobile = (isset($arParams["MOBILE"]) && $arParams["MOBILE"] == "Y");

				$arResult["POST_PROPERTIES"] = array("SHOW" => "N");

				$bHasImg = false;
				$bHasTag = false;
				$bHasProps = false;
				$bHasOnlyAll = false;

				if (!empty($arParams["POST_PROPERTY"]))
				{
					if($arPost["HAS_PROPS"] != "N")
					{
						$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", $arPost["ID"], LANGUAGE_ID);

						if (count($arParams["POST_PROPERTY"]) > 0)
						{
							foreach ($arPostFields as $FIELD_NAME => $arPostField)
							{
								if (!in_array($FIELD_NAME, $arParams["POST_PROPERTY"]))
									continue;
								elseif(
									$FIELD_NAME == "UF_GRATITUDE"
									&& array_key_exists("VALUE", $arPostField)
									&& intval($arPostField["VALUE"]) > 0
								)
								{
									$bHasProps = true;
									$gratValue = $arPostField["VALUE"];

									if (CModule::IncludeModule("iblock"))
									{
										if (
											!is_array($GLOBALS["CACHE_HONOUR"])
											|| !array_key_exists("honour_iblock_id", $GLOBALS["CACHE_HONOUR"])
											|| intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) <= 0
										)
										{
											$rsIBlock = CIBlock::GetList(array(), array("=CODE" => "honour", "=TYPE" => "structure"));
											if ($arIBlock = $rsIBlock->Fetch())
												$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"] = $arIBlock["ID"];
										}

										if (intval($GLOBALS["CACHE_HONOUR"]["honour_iblock_id"]) > 0)
										{
											$arGrat = array(
												"USERS" => array(),
												"USERS_FULL" => array(),
												"TYPE" => false
											);
											$rsElementProperty = CIBlockElement::GetProperty(
												$GLOBALS["CACHE_HONOUR"]["honour_iblock_id"],
												$gratValue
											);
											while ($arElementProperty = $rsElementProperty->GetNext())
											{
												if (
													$arElementProperty["CODE"] == "USERS"
													&& intval($arElementProperty["VALUE"]) > 0
												)
												{
													$arGrat["USERS"][] = $arElementProperty["VALUE"];
												}
												elseif ($arElementProperty["CODE"] == "GRATITUDE")
												{
													$arGrat["TYPE"] = array(
														"VALUE_ENUM" => $arElementProperty["VALUE_ENUM"],
														"XML_ID" => $arElementProperty["VALUE_XML_ID"]
													);
												}
											}

											if (count($arGrat["USERS"]) > 0)
											{
												if ($arParams["CACHE_TIME"] > 0 && defined("BX_COMP_MANAGED_CACHE"))
												{
													foreach($arGrat["USERS"] as $i => $grat_user_id)
													{
														$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($grat_user_id));
													}
												}

												$arGratUsers = array();

												$rsUser = CUser::GetList(
													($by = ""),
													($ord = ""),
													array(
														"ID" => implode("|", $arGrat["USERS"])
													),
													array(
														"FIELDS" => array(
															"ID",
															"PERSONAL_GENDER", "PERSONAL_PHOTO",
															"LOGIN", "NAME", "LAST_NAME", "SECOND_NAME", "EMAIL",
															"WORK_POSITION"
														)
													)
												);

												while ($arGratUser = $rsUser->Fetch())
												{
													$arGratUser["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arGratUser, array("AVATAR_SIZE" => (isset($arParams["AVATAR_SIZE_COMMON"]) ? $arParams["AVATAR_SIZE_COMMON"] : 58)), "");
													$arGratUser["AVATAR_SIZE"] = ($arParams["MOBILE"] == "Y" ? 58 : (count($arGrat["USERS"]) <= 4 ? 50 : 26));
													$arGratUser["URL"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arGratUser["ID"]));
													$arGratUsers[] = $arGratUser;
												}

												$arGrat["USERS_FULL"] = $arGratUsers;
											}
											if (count($arGrat["USERS_FULL"]) > 0)
											{
												$arResult["GRATITUDE"] = $arGrat;
											}
										}
									}
								}
								else
								{
									$arPostField["EDIT_FORM_LABEL"] = strLen($arPostField["EDIT_FORM_LABEL"]) > 0 ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"];
									$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["EDIT_FORM_LABEL"]);
									$arPostField["~EDIT_FORM_LABEL"] = $arPostField["EDIT_FORM_LABEL"];
									$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;

									if(!empty($arPostField["VALUE"]))
										$bHasProps = true;
								}
							}
						}
						if (!empty($arResult["POST_PROPERTIES"]["DATA"]))
							$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
					}
				}

				if($arPost["HAS_IMAGES"] != "N")
				{
					$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost['ID'], "IS_COMMENT" => "N"));
					while ($arImage = $res->Fetch())
					{
						$bHasImg = true;
						$arImages[$arImage['ID']] = $arImage['FILE_ID'];
						$arResult["images"][$arImage['ID']] = Array(
							"small" => "/bitrix/components/bitrix/blog/show_file.php?fid=".$arImage['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_SMALL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_SMALL"]."&type=square"
						);

						$arResult["images"][$arImage['ID']]["full"] = "/bitrix/components/bitrix/blog/show_file.php?fid=".$arImage['ID']."&width=".$arParams["ATTACHED_IMAGE_MAX_WIDTH_FULL"]."&height=".$arParams["ATTACHED_IMAGE_MAX_HEIGHT_FULL"];
					}
				}

				$arParserParams = Array(
					"imageWidth" => $arParams["IMAGE_MAX_WIDTH"],
					"imageHeight" => $arParams["IMAGE_MAX_HEIGHT"],
					"pathToUser" => $arParams["PATH_TO_USER"],
				);

				$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "USER" => "Y", "TAG" => "Y", "SHORT_ANCHOR" => "Y");
				if(COption::GetOptionString("blog","allow_video", "Y") != "Y")
				{
					$arAllow["VIDEO"] = "N";
				}

				if (is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]))
				{
					$p->arUserfields = array("UF_BLOG_POST_FILE" => array_merge($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"], array("TAG" => "DOCUMENT ID")));
				}
				$p->LAZYLOAD = (isset($arParams["LAZYLOAD"]) && $arParams["LAZYLOAD"] == "Y" ? "Y" : "N");
				$arResult["Post"]["textFormated"] = $p->convert($arPost["~DETAIL_TEXT"], ($arParams["USE_CUT"] == "Y" ? true : false), $arImages, $arAllow, $arParserParams);

				if (is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) &&
					is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"]))
					$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["SOURCE_VALUE"] = $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"];

				if ($arParams["USE_CUT"] == "Y" && preg_match("/(\[CUT\])/i",$arPost['~DETAIL_TEXT']))
					$arResult["Post"]["CUT"] = "Y";

				if(!empty($p->showedImages) && !empty($arResult["images"]))
				{
					foreach($p->showedImages as $val)
					{
						if(!empty($arResult["images"][$val]))
							unset($arResult["images"][$val]);
					}
				}
				$arResult["Post"]["DATE_PUBLISH_FORMATED"] = FormatDateFromDB($arResult["Post"]["DATE_PUBLISH"], $arParams["DATE_TIME_FORMAT"], true);
				$arResult["Post"]["DATE_PUBLISH_DATE"] = FormatDateFromDB($arResult["Post"]["DATE_PUBLISH"], FORMAT_DATE);
				if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
				{
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = ToLower($arResult["Post"]["DATE_PUBLISH_FORMATED"]);
					$arResult["Post"]["DATE_PUBLISH_DATE"] = ToLower($arResult["Post"]["DATE_PUBLISH_DATE"]);
				}
				// strip current year
				if (!empty($arParams['DATE_TIME_FORMAT_S']) && ($arParams['DATE_TIME_FORMAT_S'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT_S'] == 'j F Y g:i a'))
				{
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = ltrim($arResult["Post"]["DATE_PUBLISH_FORMATED"], '0');
					$arResult["Post"]["DATE_PUBLISH_DATE"] = ltrim($arResult["Post"]["DATE_PUBLISH_DATE"], '0');
					$curYear = date('Y');
					$arResult["Post"]["DATE_PUBLISH_FORMATED"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arResult["Post"]["DATE_PUBLISH_FORMATED"]);
					$arResult["Post"]["DATE_PUBLISH_DATE"] = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $arResult["Post"]["DATE_PUBLISH_DATE"]);
				}
				$arResult["Post"]["DATE_PUBLISH_TIME"] = FormatDateFromDB(
					$arResult["Post"]["DATE_PUBLISH"], 
					(
						strpos($arParams["DATE_TIME_FORMAT_S"], 'a') !== false 
						|| (
							$arParams["DATE_TIME_FORMAT_S"] == 'FULL' 
							&& IsAmPmMode()
						) !== false 
							? (strpos(FORMAT_DATETIME, 'TT')!==false ? 'G:MI TT': 'G:MI T')
							: 'GG:MI'
					)
				);
				if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
				{
					$arResult["Post"]["DATE_PUBLISH_TIME"] = ToLower($arResult["Post"]["DATE_PUBLISH_TIME"]);
				}
				$arResult["arUser"] = CBlogUser::GetUserInfo($arPost["AUTHOR_ID"], $arParams["PATH_TO_USER"], array("AVATAR_SIZE" => (isset($arParams["AVATAR_SIZE_COMMON"]) ? $arParams["AVATAR_SIZE_COMMON"] : $arParams["AVATAR_SIZE"]), "AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"]));
				$arResult["arUser"]["isExtranet"] = (intval($arPost["AUTHOR_ID"]) > 0 && is_array($GLOBALS["arExtranetUserID"]) && in_array($arPost["AUTHOR_ID"], $GLOBALS["arExtranetUserID"]));

				$arResult["Post"]["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("post_id"=> CBlogPost::GetPostID($arPost["ID"], $arPost["CODE"], $arParams["ALLOW_POST_CODE"]), "user_id" => $arPost["AUTHOR_ID"]));

				if(strlen($arPost["CATEGORY_ID"])>0)
				{
					$bHasTag = true;
					$arCategory = explode(",", $arPost["CATEGORY_ID"]);
					$dbCategory = CBlogCategory::GetList(Array(), Array("@ID" => $arCategory));
					while($arCatTmp = $dbCategory->Fetch())
					{
						$arCatTmp["~NAME"] = $arCatTmp["NAME"];
						$arCatTmp["NAME"] = htmlspecialcharsEx($arCatTmp["NAME"]);
						$arCatTmp["urlToCategory"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SEARCH_TAG"], array("tag" => urlencode($arCatTmp["NAME"])));
						$arResult["Category"][] = $arCatTmp;
					}
				}

				$bAll = false;
				$arResult["Post"]["SPERM"] = Array();
				if($arPost["HAS_SOCNET_ALL"] != "Y")
				{
					$arSPERM = CBlogPost::GetSocnetPermsName($arResult["Post"]["ID"]);
					foreach($arSPERM as $type => $v)
					{
						foreach($v as $vv)
						{
							$name = "";
							$link = "";
							$id = "";
							$isExtranet = false;

							if($type == "SG")
							{
								if($arSocNetGroup = CSocNetGroup::GetByID($vv["ENTITY_ID"]))
								{
									$name = $arSocNetGroup["NAME"];
									$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $vv["ENTITY_ID"]));

									$groupSiteID = false;
									$rsGroupSite = CSocNetGroup::GetSite($vv["ENTITY_ID"]);

									while($arGroupSite = $rsGroupSite->Fetch())
									{
										if (
											!$arResult["bExtranetInstalled"]
											|| $arGroupSite["LID"] != CExtranet::GetExtranetSiteID()
										)
										{
											$groupSiteID = $arGroupSite["LID"];
											break;
										}
									}

									if ($groupSiteID)
									{
										$arTmp = CSocNetLogTools::ProcessPath(array("GROUP_URL" => $link), $user_id, $groupSiteID); // user_id is not important parameter
										$link = (strlen($arTmp["URLS"]["GROUP_URL"]) > 0 ? $arTmp["SERVER_NAME"].$arTmp["URLS"]["GROUP_URL"] : $link);
									}

									$isExtranet = (is_array($GLOBALS["arExtranetGroupID"]) && in_array($vv["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]));
								}
							}
							elseif($type == "U")
							{
								if(in_array("US".$vv["ENTITY_ID"], $vv["ENTITY"]))
								{
									$name = "All";

									if (!$arResult["bExtranetSite"] && defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL"))
										$link = BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL;

									$bAll = true;
								}
								else
								{
									$arTmpUser = array(
										"NAME" => $vv["~U_NAME"],
										"LAST_NAME" => $vv["~U_LAST_NAME"],
										"SECOND_NAME" => $vv["~U_SECOND_NAME"],
										"LOGIN" => $vv["~U_LOGIN"],
										"NAME_LIST_FORMATTED" => "",
									);
									$name = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
									$id = $vv["ENTITY_ID"];
									$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $vv["ENTITY_ID"]));
									$isExtranet = (is_array($GLOBALS["arExtranetUserID"]) && in_array($vv["ENTITY_ID"], $GLOBALS["arExtranetUserID"]));
								}
							}
							elseif($type == "DR")
							{
								$name = $vv["EL_NAME"];
								$id = $vv["ENTITY_ID"];
								$link = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $vv["ENTITY_ID"]));
							}

							if(strlen($name) > 0)
							{
								$arResult["Post"]["SPERM"][$type][$vv["ENTITY_ID"]] = array(
									"NAME" => $name,
									"URL" => $link,
									"ID" => $id,
									"IS_EXTRANET" => ($isExtranet ? "Y" : "N")
								);
							}
						}
					}

					if(count($arResult["Post"]["SPERM"]) == 1 && count($arResult["Post"]["SPERM"]["U"]) == 1 && $bAll)
						$bHasOnlyAll = true;
				}
				else
				{
					$arResult["Post"]["SPERM"]["U"][1] = Array(
						"NAME" => "All", 
						"URL" => ((!$arResult["bExtranetSite"] && defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL")) ? BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL : ""), 
						"ID" => ""
					);
				}

				$arFieldsHave = array();
				if($arPost["HAS_IMAGES"] == "")
					$arFieldsHave["HAS_IMAGES"] = ($bHasImg ? "Y" : "N");
				if($arPost["HAS_TAGS"] == "")
					$arFieldsHave["HAS_TAGS"] = ($bHasTag ? "Y" : "N");
				if($arPost["HAS_PROPS"] == "")
					$arFieldsHave["HAS_PROPS"] = ($bHasProps ? "Y" : "N");
				if($arPost["HAS_SOCNET_ALL"] == "")
					$arFieldsHave["HAS_SOCNET_ALL"] = ($bHasOnlyAll ? "Y" : "N");

				if(!empty($arFieldsHave))
					CBlogPost::Update($arPost["ID"], $arFieldsHave);

				if($bAll || $arPost["HAS_SOCNET_ALL"] == "Y")
					$arResult["Post"]["HAVE_ALL_IN_ADR"] = "Y";

				if ($arParams["CACHE_TIME"] > 0)
				{
					$arCacheData = Array(
							"templateCachedData" => $this->GetTemplateCachedData(),
							"Post" => $arResult["Post"],
							"images" => $arResult["images"],
							"Category" => $arResult["Category"],
							"GRATITUDE" => $arResult["GRATITUDE"],
							"POST_PROPERTIES" => $arResult["POST_PROPERTIES"],
							"arUser" => $arResult["arUser"],
						);
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
					$cache->EndDataCache($arCacheData);
				}
			}

			$arResult["arUser"]["urlToPostImportant"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_IMPORTANT"], array("user_id"=> $arPost["AUTHOR_ID"]));			

			$arResult["CanComment"] = $GLOBALS["USER"]->IsAuthorized();
			$arResult["dest_users"] = array();
			foreach ($arResult["Post"]["SPERM"] as $key => $value) 
			{
				foreach($value as $kk => $vv)
				{
					$arResult["PostSrc"]["SPERM"][$key][] = $kk;
					if($key == "U")
						$arResult["dest_users"][] = $kk;
				}
			}
			$arResult["PostSrc"]["HAVE_ALL_IN_ADR"] = $arResult["Post"]["HAVE_ALL_IN_ADR"];

			if (
				$arParams["CHECK_PERMISSIONS_DEST"] == "N"
				&& !CSocNetUser::IsCurrentUserModuleAdmin()
				&& is_object($GLOBALS["USER"])
			)
			{
				$arResult["Post"]["SPERM_HIDDEN"] = 0;
				$arGroupID = CSocNetLogTools::GetAvailableGroups(
					($arResult["bExtranetUser"] ? "Y" : "N"),
					($arResult["bExtranetSite"] ? "Y" : "N")
				);

				if (
					!$arResult["bExtranetUser"]
					&& CModule::IncludeModule("extranet")
				)
				{
					$arAvailableExtranetUserID = CExtranet::GetMyGroupsUsersSimple(CExtranet::GetExtranetSiteID());
				}

				foreach($arResult["Post"]["SPERM"] as $group_code => $arBlogSPerm)
				{
					foreach($arBlogSPerm as $entity_id => $arBlogSPermDesc)
					{
						if (
							(
								$group_code == "SG"
								&& !in_array($entity_id, $arGroupID)
							)
							|| (
								$group_code == "DR"
								&& $arResult["bExtranetUser"]
							)
							|| (
								$group_code == "U"
								&& isset($arUserIdVisible)
								&& is_array($arUserIdVisible)
								&& !in_array($entity_id, $arUserIdVisible)
							)
							|| (
								$group_code == "U"
								&& isset($arBlogSPermDesc["IS_EXTRANET"])
								&& $arBlogSPermDesc["IS_EXTRANET"] == "Y"
								&& isset($arAvailableExtranetUserID)
								&& is_array($arAvailableExtranetUserID)
								&& !in_array($entity_id, $arAvailableExtranetUserID)
							)
						)
						{
							unset($arResult["Post"]["SPERM"][$group_code][$entity_id]);
							$arResult["Post"]["SPERM_HIDDEN"]++;
							$arResult["PostSrc"]["SPERM_HIDDEN"][] = $group_code.$entity_id;
						}
					}
				}
			}
			$arResult["PostSrc"]["SPERM_NAME"] = $arResult["Post"]["SPERM"];

			if($arResult["PostPerm"] > BLOG_PERMS_MODERATE || ($arResult["PostPerm"]>=BLOG_PERMS_WRITE && $arPost["AUTHOR_ID"] == $arResult["USER_ID"]))
			{
				$arResult["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$arPost["ID"], "user_id" => $arPost["AUTHOR_ID"]));
				if(in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
					$arResult["Post"]["urlToPost"] = $arResult["urlToEdit"];
			}

			if($arParams["FROM_LOG"] != "Y")
			{
				if($arResult["PostPerm"]>=BLOG_PERMS_MODERATE)
				{
					$arResult["urlToHide"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("hide=Y"."&".bitrix_sessid_get(), Array("sessid", "success", "hide", "delete")));
				}

				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					if(in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
					{
						$arResult["urlToDelete"] = $arResult["urlToEdit"];
						if(strpos($arResult["urlToDelete"], "?") === false)
							$arResult["urlToDelete"] .= "?";
						else
							$arResult["urlToDelete"] .= "&";
						$arResult["urlToDelete"] .= "delete_blog_post_id=#del_post_id#&ajax_blog_post_delete=Y"."&".bitrix_sessid_get();
					}
					else
						$arResult["urlToDelete"] = htmlspecialcharsex($APPLICATION->GetCurPageParam("delete=Y"."&".bitrix_sessid_get(), Array("sessid", "delete", "hide", "success")));
					$arResult["canDelete"] = "Y";
				}
			}
			else
			{
				if($arResult["PostPerm"] >= BLOG_PERMS_FULL)
				{
					$arResult["urlToDelete"] = $arResult["urlToEdit"];
					if(strpos($arResult["urlToDelete"], "?") === false)
						$arResult["urlToDelete"] .= "?";
					else
						$arResult["urlToDelete"] .= "&";
					$arResult["urlToDelete"] .= "delete_blog_post_id=#del_post_id#&ajax_blog_post_delete=Y"."&".bitrix_sessid_get();
					$arResult["canDelete"] = "Y";
				}
			}

			if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["Post"]))
			{
				if (
					array_key_exists("RATING_ENTITY_ID", $arParams)
					&& intval($arParams["RATING_ENTITY_ID"]) > 0
					&& array_key_exists("RATING_TOTAL_VALUE", $arParams)
					&& is_numeric($arParams["RATING_TOTAL_VALUE"])
					&& array_key_exists("RATING_TOTAL_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_VOTES"]) >= 0
					&& array_key_exists("RATING_TOTAL_POSITIVE_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_POSITIVE_VOTES"]) >= 0
					&& array_key_exists("RATING_TOTAL_NEGATIVE_VOTES", $arParams)
					&& intval($arParams["RATING_TOTAL_NEGATIVE_VOTES"]) >= 0
					&& array_key_exists("RATING_USER_VOTE_VALUE", $arParams)
					&& is_numeric($arParams["RATING_USER_VOTE_VALUE"])
				)
					$arResult['RATING'][$arResult["Post"]["ID"]] = array(
						"USER_VOTE" => $arParams["RATING_USER_VOTE_VALUE"],
						"USER_HAS_VOTED" => ($arParams["RATING_USER_VOTE_VALUE"] == 0 ? "N" : "Y"),
						"TOTAL_VOTES" => $arParams["RATING_TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arParams["RATING_TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arParams["RATING_TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arParams["RATING_TOTAL_VALUE"]
					);
				else
					$arResult['RATING'][$arResult["Post"]["ID"]] = CRatings::GetRatingVoteResult('BLOG_POST', $arResult["Post"]["ID"]);
			}

			if ($arParams["IS_UNREAD"])
				$arResult["Post"]["new"] = "Y";

			if ($arParams["IS_HIDDEN"])
				$arResult["Post"]["hidden"] = "Y";

			$arResult["Post"]["IS_IMPORTANT"] = false;
			if (
				is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]) 
				&& intval($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"]) > 0
			)
			{
				$arResult["Post"]["IS_IMPORTANT"] = true;
				unset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]);
				$arResult["Post"]["IMPORTANT"] = array();
				if ($GLOBALS["USER"]->IsAuthorized())
				{
					$arResult["Post"]["IMPORTANT"] = array(
						"COUNT" => 0,
						"IS_READ" => false,
						"USER" => array()
					);

					$cache = new CPHPCache;
					$cache_path = "/blog/socnet_post/".intval($arPost["ID"] / 100)."/".$arPost["ID"]."/";
					$cache_id = "blog_socnet_post_read_".$GLOBALS["USER"]->GetID();

					if ($cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
						$arResult["Post"]["IMPORTANT"] = $cache->GetVars();
					else
					{
						$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
						if (defined("BX_COMP_MANAGED_CACHE"))
						{
							$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("BLOG_POST_IMPRTNT".$arPost["ID"]);
						}
						$db_user = CUser::GetById($GLOBALS["USER"]->GetId());
						$arResult["Post"]["IMPORTANT"]["USER"] = $db_user->Fetch();

						$db_res = CBlogUserOptions::GetList(
							array(
								"ID" => "ASC"
							),
							array(
								"POST_ID" => $arResult["Post"]["ID"],
								"NAME" => "BLOG_POST_IMPRTNT",
								"VALUE" => "Y",
								"USER_ACTIVE" => "Y"
							),
							array(
								"bCount" => true
							)
						);
						if ($db_res && ($res = $db_res->Fetch()) && $res["CNT"] > 0)
						{
							$arResult["Post"]["IMPORTANT"]["COUNT"] = $res["CNT"];
							$arResult["Post"]["IMPORTANT"]["IS_READ"] = CBlogUserOptions::GetOption(
								$arPost["ID"],
								"BLOG_POST_IMPRTNT",
								"N",
								$GLOBALS["USER"]->GetId()
							);
						}

						if(defined("BX_COMP_MANAGED_CACHE"))
							$GLOBALS["CACHE_MANAGER"]->EndTagCache();
						$cache->EndDataCache($arResult["Post"]["IMPORTANT"]);
					}
				}
			}

			if (
				isset($arResult["GRATITUDE"])
				&& isset($arResult["GRATITUDE"]["USERS"])
				&& is_array($arResult["GRATITUDE"]["USERS"])
				&& !empty($arResult["GRATITUDE"]["USERS"])
				&& isset($arUserIdVisible)
				&& is_array($arUserIdVisible)
			)
			{
				foreach($arResult["GRATITUDE"]["USERS"] as $key => $userIdTmp)
				{
					if (!in_array($userIdTmp, $arUserIdVisible))
					{
						unset($arResult["GRATITUDE"]["USERS"][$key]);
					}
				}

				foreach($arResult["GRATITUDE"]["USERS_FULL"] as $key => $arUserTmp)
				{
					if (!in_array($arUserTmp["ID"], $arUserIdVisible))
					{
						unset($arResult["GRATITUDE"]["USERS_FULL"][$key]);
					}
				}
				
				if (empty($arResult["GRATITUDE"]["USERS_FULL"]))
				{
					unset($arResult["GRATITUDE"]);
				}
			}
		}
		else
		{
			$arResult["FATAL_MESSAGE"] .= GetMessage("B_B_MES_NO_RIGHTS")."<br />";
		}
	}
	elseif(!$arResult["bFromList"])
	{
		$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_POST");
		CHTTP::SetStatus("404 Not Found");
	}
}
else
{
	$arResult["FATAL_MESSAGE"] = GetMessage("B_B_MES_NO_BLOG");
	CHTTP::SetStatus("404 Not Found");
}

include_once('destination.php');

$this->IncludeComponentTemplate();

if ($arParams["RETURN_DATA"] == "Y")
{
	return array(
		"BLOG_DATA" => $arResult["Blog"],
		"POST_DATA" => $arResult["PostSrc"]
	);
}
?>