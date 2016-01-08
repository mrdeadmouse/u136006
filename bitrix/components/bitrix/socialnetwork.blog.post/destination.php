<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$user_id = IntVal($USER->GetID());

CJSCore::Init(array('socnetlogdest'));
// socialnetwork
$arResult["FEED_DESTINATION"]['LAST']['SONETGROUPS'] = CSocNetLogDestination::GetLastSocnetGroup();

$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
$cacheId = 'blog_post_form_dest_'.SITE_ID.'_'.$user_id;
$cacheDir = '/blog/form/dest/'.SITE_ID.'/'.$user_id;

$obCache = new CPHPCache;
if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
	$arResult["FEED_DESTINATION"]['SONETGROUPS'] = $obCache->GetVars();
else
{
	$obCache->StartDataCache();
	$arResult["FEED_DESTINATION"]['SONETGROUPS'] = CSocNetLogDestination::GetSocnetGroup(Array('features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"))));
	if(defined("BX_COMP_MANAGED_CACHE"))
	{
		$GLOBALS["CACHE_MANAGER"]->StartTagCache($cacheDir);
		foreach($arResult["FEED_DESTINATION"]['SONETGROUPS'] as $val)
		{
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_features_G_".$val["entityId"]);
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$val["entityId"]);
		}
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$user_id);
		$GLOBALS["CACHE_MANAGER"]->EndTagCache();
	}
	$obCache->EndDataCache($arResult["FEED_DESTINATION"]['SONETGROUPS']);
}

$arDestUser = Array();
$arResult["FEED_DESTINATION"]['SELECTED'] = Array();

$bAllowToAll = (COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y");
if ($bAllowToAll)
{
	$arToAllRights = unserialize(COption::GetOptionString("socialnetwork", "livefeed_toall_rights", 'a:1:{i:0;s:2:"AU";}'));
	if (!$arToAllRights)
		$arToAllRights = array("AU");
		
	$arUserGroupCode = array_merge(array("AU"), CAccess::GetUserCodesArray($GLOBALS["USER"]->GetID()));
	if (count(array_intersect($arToAllRights, $arUserGroupCode)) <= 0)
		$bAllowToAll = false;
}

if (
	CModule::IncludeModule('extranet') 
	&& !CExtranet::IsIntranetUser()
)
{
	if(!empty($arResult["FEED_DESTINATION"]['LAST']['SONETGROUPS']))
	{
		foreach ($arResult["FEED_DESTINATION"]['LAST']['SONETGROUPS'] as $val)
		{
			$arResult["FEED_DESTINATION"]['SELECTED'][$val] = "sonetgroups";
		}
	}
	else
	{
		foreach ($arResult["FEED_DESTINATION"]['SONETGROUPS'] as $k => $val)
		{
			$arResult["FEED_DESTINATION"]['SELECTED'][$k] = "sonetgroups";
		}
	}
}
elseif ($bAllowToAll)
{
	$arResult["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
}


// intranet structure
$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
$arResult["FEED_DESTINATION"]['DEPARTMENT'] = $arStructure['department'];
$arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
$arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

$arResult["FEED_DESTINATION"]['LAST']['DEPARTMENT'] = CSocNetLogDestination::GetLastDepartment();

// users
$arResult["FEED_DESTINATION"]['LAST']['USERS'] = CSocNetLogDestination::GetLastUser();

if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	$arResult["FEED_DESTINATION"]['EXTRANET_USER'] = 'Y';
	$arResult["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetExtranetUser();
}
else
{
	foreach ($arResult["FEED_DESTINATION"]['LAST']['USERS'] as $value)
		$arResult["dest_users"][] = str_replace('U', '', $value);

	$arResult["FEED_DESTINATION"]['EXTRANET_USER'] = 'N';
	$arResult["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $arResult["dest_users"]));
}

$arResult["FEED_DESTINATION"]["DENY_TOALL"] = !$bAllowToAll;
?>