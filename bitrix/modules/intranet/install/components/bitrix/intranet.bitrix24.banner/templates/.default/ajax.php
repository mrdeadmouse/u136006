<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (check_bitrix_sessid() && isset($_GET["banner"]) && preg_match("/^[a-z0-9-_]+$/i", $_GET["banner"]))
{
	$moduleName = IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet";
	$optionName = "banners";
	$banners = CUserOptions::GetOption($moduleName, $optionName, array());
	if (is_array($banners))
	{
		$banners[$_GET["banner"]] = "Y";
		CUserOptions::SetOption($moduleName, $optionName, $banners);
	}

	\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
}

die("");