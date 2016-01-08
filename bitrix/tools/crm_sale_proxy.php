<?
define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule('crm'))
	die('CRM module is not installed');

$CCrmDeal = new CCrmDeal();
if ($CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
{
	die("Permission denied");
}

$externalSaleId = 0;

/*
if (isset($_SERVER["REQUEST_URI"]) && strlen($_SERVER["REQUEST_URI"]) > 0)
	$path = substr($_SERVER["REQUEST_URI"], strlen("/bitrix/tools/crm_sale_proxy.php"));
else
	$path = $_SERVER["PATH_INFO"].((isset($_SERVER["QUERY_STRING"]) && strlen($_SERVER["QUERY_STRING"]) > 0) ? "?".$_SERVER["QUERY_STRING"] : "");
*/
$path = $_SERVER["QUERY_STRING"];
$path = preg_replace("/%0D|%0A|\r|\n/i", "", $path);

if (isset($_REQUEST["__BX_CRM_QUERY_STRING_PREFIX"]))
{
	$prefix = $_REQUEST["__BX_CRM_QUERY_STRING_PREFIX"];
	$prefix = preg_replace("/%0D|%0A|\r|\n/i", "", $prefix);
	if (substr($prefix, 0, strlen("/bitrix/tools/crm_sale_proxy.php?")) == "/bitrix/tools/crm_sale_proxy.php?")
		$prefix = substr($prefix, strlen("/bitrix/tools/crm_sale_proxy.php?"));
	if (substr($path, 0, strlen($prefix)) != $prefix)
		$path = $prefix.$path;
}

$path = ltrim($path, "/");
if (($pos = strpos($path, "/")) !== false)
{
	$externalSaleId = intval(substr($path, 0, $pos));
	$path = substr($path, $pos);
}

$proxy = new CCrmExternalSaleProxy($externalSaleId);
if (!$proxy->IsInitialized())
	die("External site is not found");

$arPath = parse_url($path);
$arAvailableUrls = array(
	"/bitrix/admin/sale_order_edit.php",
	"/bitrix/components/bitrix/sale.ajax.delivery.calculator/templates/input/ajax.php",
	"/bitrix/admin/sale_order_new.php",
	"/bitrix/admin/sale_order_detail.php",
	"/bitrix/admin/sale_order_print.php",
	"/bitrix/admin/sale_print.php",
	"/bitrix/admin/sale_product_search.php",
	"/bitrix/admin/user_search.php",
	"/bitrix/admin/cat_product_search_dialog.php",

	"/bitrix/components/bitrix/sale.ajax.locations/templates/.default/ajax.php",
	"/bitrix/components/bitrix/sale.ajax.locations/templates/.default/proceed.js",

	"/bitrix/js/main/json/json2.min.js",
	"/bitrix/js/main/json/json2.js",

	"/bitrix/components/bitrix/catalog.product.search/templates/.default/style.css",
	"/bitrix/components/bitrix/catalog.product.search/templates/.default/script.js",

	"/bitrix/components/bitrix/sale.location.selector.search/get.php",
	"/bitrix/components/bitrix/sale.location.selector.steps/get.php",
	"/bitrix/components/bitrix/sale.location.selector.system/get.php",
	"/bitrix/components/bitrix/sale.location.import/get.php",

	"/bitrix/components/bitrix/sale.location.selector.search/templates/.default/script.js",
	"/bitrix/components/bitrix/sale.location.selector.steps/templates/.default/script.js",
	"/bitrix/components/bitrix/sale.location.selector.steps/templates/.default/style.css",

	"/bitrix/components/bitrix/sale.location.import/templates/.default/script.js",
	"/bitrix/components/bitrix/sale.location.import/templates/admin/script.js",
	"/bitrix/components/bitrix/sale.ajax.locations/templates/.default/style.css",

	"/bitrix/js/sale/core_iterator.js",
	"/bitrix/js/sale/core_ui_autocomplete.js",
	"/bitrix/js/sale/core_ui_chainedselectors.js",
	"/bitrix/js/sale/core_ui_combobox.js",
	"/bitrix/js/sale/core_ui_dynamiclist.js",
	"/bitrix/js/sale/core_ui_etc.js",
	"/bitrix/js/sale/core_ui_fileasyncloader.js",
	"/bitrix/js/sale/core_ui_itemtree.js",
	"/bitrix/js/sale/core_ui_pager.js",
	"/bitrix/js/sale/core_ui_widget.js"
);

if (!in_array($arPath["path"], $arAvailableUrls))
	die("Page is not found");

$path = $arPath["path"]."?".$arPath["query"];

$request = array(
	"METHOD" => $_SERVER["REQUEST_METHOD"],
	"PATH" => $path,
	"HEADERS" => array(),
	"BODY" => array()
);
$request["PATH"] = str_replace("CRM_MANAGER_USER_ID", "CMUI", $request["PATH"]);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	foreach ($_POST as $key => $val)
		$request["BODY"][$key] = $val;
	$request["BODY"]["CRM_MANAGER_USER_ID"] = intval($USER->GetID());
	$request["BODY"]["bxpublic"] = "Y";
	$request["BODY"]["nocdn"] = "Y";
	$request["BODY"]["externalcontext"] = "crm";

	if (isset($request["BODY"]["AlreadyUTF8Request"]) && ($request["BODY"]["AlreadyUTF8Request"] == "Y"))
	{
		$request["UTF"] = true;
		unset($request["BODY"]["AlreadyUTF8Request"]);
	}
}
else
{
	$request["PATH"] .= ((strpos($request["PATH"], "?") !== false) ? "&" : "?")."CRM_MANAGER_USER_ID=".intval($USER->GetID())."&bxpublic=Y&nocdn=Y&externalcontext=crm";
}

$response = $proxy->Send($request);
if ($response == null)
	die("Communication error");

$body = $response["BODY"];

if (isset($response["CONTENT"]["ENCODING"]) && (in_array($response["CONTENT"]["TYPE"], array("text/xml", "application/xml", "text/html"))))
{
	$utf8Encoding = (strtoupper($response["CONTENT"]["ENCODING"]) == "UTF-8");
	if (!$utf8Encoding && defined("BX_UTF"))
		$body = CharsetConverter::ConvertCharset($body, $response["CONTENT"]["ENCODING"], "UTF-8");
	elseif ($utf8Encoding && !defined("BX_UTF"))
		$body = CharsetConverter::ConvertCharset($body, "UTF-8", SITE_CHARSET);
}

$body = preg_replace(
	"#(\"|')(/bitrix/([a-z0-9_.-]+/)*?([a-z0-9_.-]+?\.(gif|png)))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(\"|')(/bitrix/([a-z0-9_.-]+/)*?(sale\.css))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(\"|')(/upload/([%a-z0-9_.-]+/)*?([%a-z0-9_.-]+?\.([a-z0-9]+)))#i",
	"$1".($proxy->GetUrl())."$2",
	$body
);

$body = preg_replace(
	"#(<a\s[^>]*?)(href\s*=\s*(\"|'))(/bitrix/([a-z0-9_.-]+/)*([a-z0-9_.-]+\.php)(?<!sale_order_edit\.php|ajax\.php|sale_order_new\.php|sale_order_detail\.php|sale_order_print\.php|sale_print\.php|sale_product_search\.php|user_search\.php))#i",
	"$1target=\"_blank\" $2".($proxy->GetUrl())."$4",
	$body
);

$body = preg_replace(
	"#(\"|')(/bitrix/([a-z0-9_.-]+/)*([a-z0-9_.-]+\.(?!css|js)))#i",
	"$1/bitrix/tools/crm_sale_proxy.php?".$externalSaleId."$2",
	$body
);

$body = preg_replace(
	"#(<a\s[^>]*?)(href\s*=\s*(\"|'))(?!/bitrix/)(/([a-z0-9_.-]+/)*([a-z0-9_.-]+)/?)(\"|')#i",
	"$1target=\"_blank\" $2".($proxy->GetUrl())."$4$5",
	$body
);

if (strpos($arPath["path"], '.css') !== false)
{
	header('Content-Type: text/css');
}
elseif (strpos($arPath["path"], '.js') !== false)
{
	header('Content-Type: application/x-javascript');
}

echo $body;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");