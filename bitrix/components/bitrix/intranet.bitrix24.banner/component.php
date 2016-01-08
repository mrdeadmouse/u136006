<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//Standard banners
$arBanners = array();

//en-de new year banner for free license
/*if (
	IsModuleInstalled("bitrix24")
	&& MakeTimeStamp(date("d.m.Y"), "DD.MM.YYYY") <= MakeTimeStamp("31.12.2014", "DD.MM.YYYY")
)
{
	if (
		LANGUAGE_ID == "de"
		|| LANGUAGE_ID == "en" && !CBitrix24::IsLicensePaid()
	)
		$arBanners[] = "newyear";

	if (
		LANGUAGE_ID == "ru"
		|| LANGUAGE_ID == "ua"
	)
		$arBanners[] = "runewyear";
}*/

if (
	IsModuleInstalled("bitrix24")
	&& COption::GetOptionString("voximplant", "notice_old_config_office_pbx") == 'Y'
	&& CBitrix24::IsPortalAdmin($USER->GetID())
)
{
	$arBanners[] = "sip";
}

if (CUserOptions::GetOption("im", "DesktopLastActivityDate", -1) == -1)
{
	$arBanners[] = "messenger";
}

$arBanners[] = "mobile";

if (COption::GetOptionString("bitrix24", "network", "N") == "Y")
{
	$arBanners[] = "network";
}

/*if (IsModuleInstalled("bitrix24"))
{
	$arBanners[] = "marketplace";
}*/

$arBanners[] = "webinar";

if (IsModuleInstalled("mail") && LANGUAGE_ID == "ru")
{
	$arBanners[] = "mail";
}

//Trial banner
if (IsModuleInstalled("bitrix24") && $USER->CanDoOperation("bitrix24_config"))
{
	$licenseName = COption::GetOptionString("main", "~controller_group_name");
	$position = strpos($licenseName, "_");
	if ($position !== false)
	{
		$licenseName = substr($licenseName, $position + 1);
	}

	$startDate = intval(COption::GetOptionInt("bitrix24", "DEMO_START"));
	if (in_array($licenseName, array("team", "project")) && $startDate <= 0)
	{
		$arBanners[] = "trial";
	}
	elseif ($licenseName == "demo" && $startDate > 0)
	{
		$days = (time()-$startDate) / (3600*24);
		if ($days >= 20 && $days < 30)
		{
			$arBanners[] = "trial-expired";
		}
	}
}

//for en/de license banner
if (IsModuleInstalled("bitrix24") && in_array(LANGUAGE_ID, array("en", "de")))
{
	$arBanners[] = "prices";
}

$moduleName = IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet";
$arResult["MODULE_NAME"] = $moduleName;

$arResult["BANNERS"] = array();
$arOptions = CUserOptions::GetOption($moduleName, "banners", array());
$maxBanners = 2;

foreach ($arBanners as $bannerId)
{
	if (!is_array($arOptions) || !array_key_exists($bannerId, $arOptions))
	{
		$arResult["BANNERS"][] = $bannerId;
		if (--$maxBanners <= 0)
			break;
	}
}

if (count($arResult["BANNERS"]) >= 1)
{
	$this->IncludeComponentTemplate();
}