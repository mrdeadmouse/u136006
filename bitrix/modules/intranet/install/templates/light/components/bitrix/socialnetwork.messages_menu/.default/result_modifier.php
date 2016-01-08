<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$tmp = $GLOBALS["APPLICATION"]->GetPageProperty("BodyClass");
if ($tmp)
	$tmp .= " no-left-menu page-section-menu";
else
	$tmp = "no-left-menu page-section-menu";
$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", $tmp);

__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/result_modifier.php");

if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("NAME_TEMPLATE", $this->__component->__parent->arParams))
	$arParams["NAME_TEMPLATE"] = $this->__component->__parent->arParams["NAME_TEMPLATE"];

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$dbUser = CUser::GetByID($GLOBALS["USER"]->GetID());
$arResult["User"] = $dbUser->GetNext();

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_SETTINGS_EDIT", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_SETTINGS_EDIT"] = $this->__component->__parent->arResult["PATH_TO_USER_SETTINGS_EDIT"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_FEATURES", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_FEATURES"] = $this->__component->__parent->arResult["PATH_TO_USER_FEATURES"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_SUBSCRIBE", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_SUBSCRIBE"] = $this->__component->__parent->arResult["PATH_TO_USER_SUBSCRIBE"];

if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("SHOW_LOGIN", $this->__component->__parent->arParams))
	$arParams["SHOW_LOGIN"] = $this->__component->__parent->arParams["SHOW_LOGIN"];
$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;

$arResult["User"]["NAME_FORMATTED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["User"], $bUseLogin, false);

if (intval($arResult["User"]["PERSONAL_PHOTO"]) <= 0)
{
	switch ($arResult["User"]["PERSONAL_GENDER"])
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
	$arResult["User"]["PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
}

$arResult["User"]["PersonalPhotoFile"] = array("src" => "");

if (intval($arResult["User"]["PERSONAL_PHOTO"]) > 0)
{

	$imageFile = CFile::GetFileArray($arResult["User"]["PERSONAL_PHOTO"]);
	if ($imageFile !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$imageFile,
			array("width" => 42, "height" => 42),
			BX_RESIZE_IMAGE_EXACT,			
			true
		);
	}

	if($arFileTmp && array_key_exists("src", $arFileTmp))
		$arResult["User"]["PersonalPhotoFile"] = $arFileTmp;
}

$arResult["IS_ONLINE"] = CSocNetUser::IsOnLine($arResult["User"]["ID"]);
if (CModule::IncludeModule('intranet'))
{
	$arResult['IS_HONOURED'] = CIntranetUtils::IsUserHonoured($arResult["User"]["ID"]);
	$arResult['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arResult["User"]["ID"]);
}
if ($arResult["User"]['PERSONAL_BIRTHDAY'] <> '')
{
	$arBirthDate = ParseDateTime($arResult["User"]['PERSONAL_BIRTHDAY'], CSite::GetDateFormat('SHORT'));
	$arResult['IS_BIRTHDAY'] = (intval($arBirthDate['MM']) == date('n') && intval($arBirthDate['DD']) == date('j'));
}

$arResult["CurrentUserPerms"] = CSocNetUserPerms::InitUserPerms($GLOBALS["USER"]->GetID(), $GLOBALS["USER"]->GetID(), CSocNetUser::IsCurrentUserModuleAdmin());
if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
	$arResult["CurrentUserPerms"]["Operations"]["viewfriends"] = false;
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_PROFILE_EDIT", $this->__component->__parent->arResult))
	$arResult["Urls"]["Edit"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_PROFILE_EDIT"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_FRIENDS", $this->__component->__parent->arResult))
	$arResult["Urls"]["Friends"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_FRIENDS"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_GROUPS", $this->__component->__parent->arResult))
	$arResult["Urls"]["Groups"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_GROUPS"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER", $this->__component->__parent->arResult))
	$arResult["Urls"]["Main"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_BLOG", $this->__component->__parent->arResult))
	$arResult["Urls"]["Blog"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_BLOG"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_MICROBLOG", $this->__component->__parent->arResult))
	$arResult["Urls"]["Microblog"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_MICROBLOG"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_PHOTO", $this->__component->__parent->arResult))
	$arResult["Urls"]["Photo"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_PHOTO"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_FORUM", $this->__component->__parent->arResult))
	$arResult["Urls"]["Forum"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_FORUM"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_CALENDAR", $this->__component->__parent->arResult))
	$arResult["Urls"]["Calendar"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_CALENDAR"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_TASKS", $this->__component->__parent->arResult))
	$arResult["Urls"]["Tasks"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_TASKS"], array("user_id" => $GLOBALS["USER"]->GetID()));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_FILES", $this->__component->__parent->arResult))
	$arResult["Urls"]["Files"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_FILES"], array("user_id" => $GLOBALS["USER"]->GetID(), "path" => ""));
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_CONTENT_SEARCH", $this->__component->__parent->arResult))
	$arResult["Urls"]["content_search"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_USER_CONTENT_SEARCH"], array("user_id" => $GLOBALS["USER"]->GetID()));

$arResult["Urls"]["Settings"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_SETTINGS_EDIT"], array("user_id" => $GLOBALS["USER"]->GetID()));
$arResult["Urls"]["Features"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_FEATURES"], array("user_id" => $GLOBALS["USER"]->GetID()));
// $arResult["Urls"]["Subscribe"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_SUBSCRIBE"], array("user_id" => $GLOBALS["USER"]->GetID()));

$arResult["ActiveFeatures"] = CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID());
$arResult["CanView"]["blog"] = (array_key_exists("blog", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "blog", "view_post", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["microblog"] = (array_key_exists("microblog", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "blog", "view_post", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["photo"] = (array_key_exists("photo", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "photo", "view", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["forum"] = (array_key_exists("forum", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "forum", "view", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["calendar"] = (array_key_exists("calendar", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "calendar", "view", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["tasks"] = (array_key_exists("tasks", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "tasks", "view", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["files"] = (array_key_exists("files", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "files", "view", CSocNetUser::IsCurrentUserModuleAdmin()));
$arResult["CanView"]["content_search"] = (array_key_exists("search", $arResult["ActiveFeatures"]) && CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "search", "view", CSocNetUser::IsCurrentUserModuleAdmin()));

$arResult["Title"]["blog"] = ((array_key_exists("blog", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["blog"]) > 0) ? $arResult["ActiveFeatures"]["blog"] : GetMessage("SONET_UM_BLOG"));
$arResult["Title"]["microblog"] = ((array_key_exists("microblog", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["microblog"]) > 0) ? $arResult["ActiveFeatures"]["microblog"] : GetMessage("SONET_UM_MICROBLOG"));
$arResult["Title"]["photo"] = ((array_key_exists("photo", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["photo"]) > 0) ? $arResult["ActiveFeatures"]["photo"] : GetMessage("SONET_UM_PHOTO"));
$arResult["Title"]["forum"] = ((array_key_exists("forum", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["forum"]) > 0) ? $arResult["ActiveFeatures"]["forum"] : GetMessage("SONET_UM_FORUM"));
$arResult["Title"]["calendar"] = ((array_key_exists("calendar", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["calendar"]) > 0) ? $arResult["ActiveFeatures"]["calendar"] : GetMessage("SONET_UM_CALENDAR"));
$arResult["Title"]["tasks"] = ((array_key_exists("tasks", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["tasks"]) > 0) ? $arResult["ActiveFeatures"]["tasks"] : GetMessage("SONET_UM_TASKS"));
$arResult["Title"]["files"] = ((array_key_exists("files", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["files"]) > 0) ? $arResult["ActiveFeatures"]["files"] : GetMessage("SONET_UM_FILES"));
$arResult["Title"]["content_search"] = ((array_key_exists("search", $arResult["ActiveFeatures"]) && StrLen($arResult["ActiveFeatures"]["search"]) > 0) ? $arResult["ActiveFeatures"]["search"] : GetMessage("SONET_UM_SEARCH"));

$a = array_keys($arResult["Urls"]);
foreach ($a as $v)
	$arResult["Urls"][strtolower($v)] = $arResult["Urls"][$v];

$events = GetModuleEvents("socialnetwork", "OnFillSocNetMenu");
while ($arEvent = $events->Fetch())
	ExecuteModuleEventEx($arEvent, array(&$arResult));
?>