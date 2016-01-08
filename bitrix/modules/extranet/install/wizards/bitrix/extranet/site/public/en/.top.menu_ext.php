<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (SITE_TEMPLATE_ID !== "bitrix24")
	return;
GLOBAL $USER;
$USER_ID = $USER->GetID();
	
$arMenuB24 = Array(
	Array(
		"My Workspace", 
		SITE_DIR, 
		Array(), 
		Array("class" => "menu-favorites"), 
		"" 
	)
);
//extranet goups
if (CModule::IncludeModule("extranet") && CModule::IncludeModule("socialnetwork") && CBXFeatures::IsFeatureEnabled('Workgroups') && CBXFeatures::IsFeatureEnabled('Extranet'))
{
	if (defined("BX_COMP_MANAGED_CACHE"))
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$USER_ID);
	}

	$rsExtranetSite = CSite::GetByID(CExtranet::GetExtranetSiteID());
	if ($arExtranetSite = $rsExtranetSite->Fetch())
		$strExtranetSiteDir = $arExtranetSite["DIR"];
	else
		$strExtranetSiteDir = "/extranet/";

	$arGroupFilterMy = array(
		"USER_ID" => $USER_ID,
		"<=ROLE" => SONET_ROLES_USER,
		"GROUP_ACTIVE" => "Y",
		"!GROUP_CLOSED" => "Y",
		"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID()
	);

	$dbGroups = CSocNetUserToGroup::GetList(
		array(),
		$arGroupFilterMy,
		false,
		false
		//array('ID')
	);
	$arGroups = $dbGroups->GetNext();
}

$arPathPattern = array("\/contacts\/$", "\/docs\/$", "\/services\/$", "\/workgroups\/$", "\/community\/$", "\/crm\/$", "\/about\/$");
$arClasses = array("menu-employees", "menu-docs", "menu-services", "menu-groups-extranet",  "menu-community", "menu-crm", "menu-company");
$aNewMenuLinks = array();

foreach ($aMenuLinks as $key => $arItem)
{
	$bfound = false;
	$arNewItem = $arItem;
	$arNewItem[0] = strtoupper($arNewItem[0]);
	foreach ($arPathPattern as $key => $pattern)
	{
		$matches = "";
		preg_match("/".$pattern."/is", $arItem[1], $matches);
		if ($matches[0])
		{
			$arNewItem[3] = array("class" => $arClasses[$key]);
			break;
		}
	}

	if (
		!preg_match("/^".str_replace("/", "\/", $strExtranetSiteDir)."index.php$/i", $arItem[1])
		&& (
			!preg_match("/^".str_replace("/", "\/", $strExtranetSiteDir)."workgroups\/$/i", $arItem[1])
			|| $arGroups
		)
	)
		$aNewMenuLinks[] = $arNewItem;

}

$aMenuLinks = array_merge($arMenuB24, $aNewMenuLinks);
?>