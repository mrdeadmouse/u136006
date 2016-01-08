<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

$arResult = $arParams["arResult"];
$arParams = $arParams["arParams"];

$arResult["PostFormUrl"] = isset($arParams["POST_FORM_URI"]) ? $arParams["POST_FORM_URI"] : '';
$arResult["ActionUrl"] = isset($arParams["ACTION_URI"]) ? $arParams["ACTION_URI"] : '';
if($arResult["ActionUrl"] === "")
{
	$arResult["AjaxURL"] = $GLOBALS["APPLICATION"]->GetCurPageParam("SONET_FILTER_MODE=AJAX", array("SONET_FILTER_MODE"));
}
else
{
	//For custom schemes
	$ajaxUrlParams = array("SONET_FILTER_MODE" => "AJAX");
	if(isset($_REQUEST["flt_created_by_id"]))
	{
		$ajaxUrlParams["flt_created_by_id"] = $_REQUEST["flt_created_by_id"];
	}
	if(isset($_REQUEST["flt_comments"]))
	{
		$ajaxUrlParams["flt_comments"] = $_REQUEST["flt_comments"];
	}
	if(isset($_REQUEST["flt_date_datesel"]))
	{
		$ajaxUrlParams["flt_date_datesel"] = $_REQUEST["flt_date_datesel"];
	}
	if(isset($_REQUEST["flt_date_days"]))
	{
		$ajaxUrlParams["flt_date_days"] = $_REQUEST["flt_date_days"];
	}
	if(isset($_REQUEST["flt_date_from"]))
	{
		$ajaxUrlParams["flt_date_from"] = $_REQUEST["flt_date_from"];
	}
	if(isset($_REQUEST["flt_date_to"]))
	{
		$ajaxUrlParams["flt_date_to"] = $_REQUEST["flt_date_to"];
	}

	$arResult["AjaxURL"] = CHTTP::urlAddParams(
		CHTTP::urlDeleteParams(
			$arResult["ActionUrl"],
			array("SONET_FILTER_MODE", "flt_created_by_id", "flt_comments", "flt-date-datesel", "flt_date_days", "flt_date_from", "flt_date_to")
		),
		$ajaxUrlParams
	);
}

$arResult["MODE"] = (isset($_REQUEST["SONET_FILTER_MODE"]) && $_REQUEST["SONET_FILTER_MODE"] == "AJAX" ? "AJAX" : false);

if (
	(
		$GLOBALS["USER"]->IsAuthorized() 
		|| $arParams["AUTH"] == "Y" 
		|| $arParams["SUBSCRIBE_ONLY"] != "Y"
	)
)
	$arResult["DATE_FILTER"] = array(
		"" => GetMessage("SONET_C30_DATE_FILTER_NO_NO_NO_1"),
		"today" => GetMessage("SONET_C30_DATE_FILTER_TODAY"),
		"yesterday" => GetMessage("SONET_C30_DATE_FILTER_YESTERDAY"),
		"week" => GetMessage("SONET_C30_DATE_FILTER_WEEK"),
		"week_ago" => GetMessage("SONET_C30_DATE_FILTER_WEEK_AGO"),
		"month" => GetMessage("SONET_C30_DATE_FILTER_MONTH"),
		"month_ago" => GetMessage("SONET_C30_DATE_FILTER_MONTH_AGO"),
		"days" => GetMessage("SONET_C30_DATE_FILTER_LAST"),
		"exact" => GetMessage("SONET_C30_DATE_FILTER_EXACT"),
		"after" => GetMessage("SONET_C30_DATE_FILTER_LATER"),
		"before" => GetMessage("SONET_C30_DATE_FILTER_EARLIER"),
		"interval" => GetMessage("SONET_C30_DATE_FILTER_INTERVAL"),
	);

$arResult["FOLLOW_TYPE"] = "";

if ($GLOBALS["USER"]->IsAuthorized())
{
	$arParams["SHOW_SMART_FILTER_MYGROUPS"] = $arParams["USE_SMART_FILTER"];

	if (array_key_exists("set_follow_type", $_GET))
	{
		CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "**", $_GET["set_follow_type"] == "Y" ? "Y" : "N", false);
		if ($_GET["set_follow_type"] != "Y")
			$_SESSION["SL_SHOW_FOLLOW_HINT"] = "Y";
		LocalRedirect("");
	}
	elseif (
		$arParams["USE_SMART_FILTER"] == "Y"
		&& array_key_exists("set_smart_filter_mygroups", $_GET)
	)
	{
		CSocNetLogSmartFilter::Set($GLOBALS["USER"]->GetID(), ($_GET["set_smart_filter_mygroups"] == "Y" ? "Y" : "N"));
		CSocNetLogPages::DeleteEx($GLOBALS["USER"]->GetID(), SITE_ID);
		LocalRedirect("");
	}

	$arResult["FOLLOW_TYPE"] = CSocNetLogFollow::GetDefaultValue($GLOBALS["USER"]->GetID());
}

$arResult["flt_created_by_string"] = "";

if (strlen($_REQUEST["flt_created_by_string"]) > 0)
	$arResult["flt_created_by_string"] = $_REQUEST["flt_created_by_string"];
else
{
	if (is_array($_REQUEST["flt_created_by_id"]) && intval($_REQUEST["flt_created_by_id"][0]) > 0)
		$user_id_tmp = $_REQUEST["flt_created_by_id"][0];
	elseif(intval($_REQUEST["flt_created_by_id"]) > 0)
		$user_id_tmp = $_REQUEST["flt_created_by_id"];

	if (intval($user_id_tmp) > 0)
	{
		$rsUser = CUser::GetByID($user_id_tmp);
		if ($arUser = $rsUser->GetNext())
		{
			$arResult["flt_created_by_string"] = CUser::FormatName($arParams["NAME_TEMPLATE"]." <#EMAIL#> [#ID#]", $arUser, ($arParams["SHOW_LOGIN"] != "N"), false);
		}
	}
}

if (!is_array($arResult["PresetFiltersTop"]))
	$arResult["PresetFiltersTop"] = array();
if (!is_array($arResult["PresetFilters"]))
	$arResult["PresetFilters"] = array();

$arResult["PageParamsToClear"] = array("set_follow_type");
$arResult["ALL_ITEM_TITLE"] = false;
$db_events = GetModuleEvents("socialnetwork", "OnBeforeSonetLogFilterFill");
while ($arEvent = $db_events->Fetch())
{
	ExecuteModuleEventEx($arEvent, array(&$arResult["PageParamsToClear"], &$arResult["PresetFiltersTop"], &$arResult["PresetFilters"], &$arResult["ALL_ITEM_TITLE"]));
}

if (!function_exists("__SL_PF_sort"))
{
	function __SL_PF_sort($a, $b)
	{
		if ($a["SORT"] == $b["SORT"])
			return 0;
		return ($a["SORT"] < $b["SORT"]) ? -1 : 1;
	}
}

usort($arResult["PresetFilters"], "__SL_PF_sort");
$arResult["PresetFilters"] = CSocNetLogComponent::ConvertPresetToFilters($arResult["PresetFilters"], $arParams);

if ($_REQUEST["preset_filter_top_id"] == "clearall")
{
	$preset_filter_top_id = false;
}
elseif(array_key_exists("preset_filter_top_id", $_REQUEST) && strlen($_REQUEST["preset_filter_top_id"]) > 0)
{
	$preset_filter_top_id = $_REQUEST["preset_filter_top_id"];
}

if (
	strlen($preset_filter_top_id) > 0
	&& array_key_exists($preset_filter_top_id, $arResult["PresetFiltersTop"])
	&& is_array($arResult["PresetFiltersTop"][$preset_filter_top_id])
)
{
	$arResult["PresetFilterTopActive"] = $preset_filter_top_id;
}
else
{
	$arResult["PresetFilterTopActive"] = false;
}

if ($_REQUEST["preset_filter_id"] == "clearall")
{
	$preset_filter_id = false;
}
elseif(array_key_exists("preset_filter_id", $_REQUEST) && strlen($_REQUEST["preset_filter_id"]) > 0)
{
	$preset_filter_id = $_REQUEST["preset_filter_id"];
}

if (
	strlen($preset_filter_id) > 0
	&& array_key_exists($preset_filter_id, $arResult["PresetFilters"])
	&& is_array($arResult["PresetFilters"][$preset_filter_id])
	&& array_key_exists("FILTER", $arResult["PresetFilters"][$preset_filter_id])
	&& is_array($arResult["PresetFilters"][$preset_filter_id]["FILTER"])
)
{
	$arResult["PresetFilterActive"] = $preset_filter_id;
}
else
{
	$arResult["PresetFilterActive"] = false;
}

$this->IncludeComponentTemplate();
?>