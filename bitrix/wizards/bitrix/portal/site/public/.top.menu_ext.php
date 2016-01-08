<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (SITE_TEMPLATE_ID !== "bitrix24")
	return;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/.top.menu_ext.php");

GLOBAL $USER;
$USER_ID = $USER->GetID();

$arMenuB24 = Array(
	Array(
		GetMessage("TOP_MENU_FAVORITE"),
		SITE_DIR,
		Array(),
		Array("class" => "menu-favorites"),
		""
	),
);
if (CModule::IncludeModule("socialnetwork"))
	$arMenuB24[] = Array(
		GetMessage("TOP_MENU_GROUPS"),
		SITE_DIR."workgroups/",
		Array(),
		Array("class" => "menu-groups"),
		"CBXFeatures::IsFeatureEnabled('Workgroups')"
	);
//extranet groups
if (CModule::IncludeModule("extranet") && CBXFeatures::IsFeatureEnabled('Workgroups') && CBXFeatures::IsFeatureEnabled('Extranet') && CModule::IncludeModule("socialnetwork"))
{
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
	if ($arGroups = $dbGroups->GetNext())
	{
		$arMenuB24[] = Array(
			GetMessage("TOP_MENU_GROUPS_EXTRANET"),
			SITE_DIR."workgroups/extranet/",
			Array(),
			Array("class" => "menu-groups-extranet"),
			"CBXFeatures::IsFeatureEnabled('Workgroups')"
		);
	}
}

$arSkipPattern = array("\/workgroups\/$", "\/workgroups\/extranet\/$");
$arPathClassPattern = array(
	"\/company\/$" => "menu-employees",
	"\/docs\/$" => "menu-docs",
	"\/services\/$" => "menu-services",
	"\/crm\/$" => "menu-crm",
	"\/about\/$" => "menu-company"
);
foreach ($aMenuLinks as $arItem)
{
	$bFound = false;
	foreach($arSkipPattern as $skip)
	{
		preg_match("/".$skip."/is", $arItem[1], $matches);
		if ($matches[0])
		{
			$bFound = true;
			break;
		}
	}
	if ($bFound)
		continue;

	$arItem[0] = ToUpper($arItem[0]);
	foreach ($arPathClassPattern as $path => $class)
	{
		$matches = "";
		preg_match("/".$path."/is", $arItem[1], $matches);
		if ($matches[0])
		{
			$arItem[3] = array("class" => $class);
			break;
		}
	}
	$arMenuB24[] = $arItem;
}

$arMenuB24[] = Array(
	GetMessage("TOP_MENU_TELEPHONY"),
	SITE_DIR."services/telephony/",
	Array('/services/telephony/'),
	Array(),
	(!IsModuleInstalled("voximplant"))?"false":'$GLOBALS["USER"]->IsAdmin()'
);


$rsSite = CSite::GetList($by="sort", $order="asc", $arFilter=array("ACTIVE" => "Y"));
if (intval($rsSite->SelectedRowsCount())>1)
{
	$arMenuB24[] = Array(
		GetMessage("TOP_MENU_DEPARTMENTS"),
		SITE_DIR."departments/",
		Array(),
		Array("class" => "menu-departments"),
		""
	);
}

$aMenuLinks = $arMenuB24;
?>