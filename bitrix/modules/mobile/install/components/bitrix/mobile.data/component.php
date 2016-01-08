<?if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $APPLICATION CAllMain
 * @var $USER CAllUser
 */
Bitrix\Main\Loader::includeModule("mobileapp");
Bitrix\Main\Loader::includeModule("mobile");
Bitrix\MobileApp\Mobile::Init();

include(dirname(__FILE__) . "/functions.php");

defineApiVersion();
$isSessidValid = true;
if(array_key_exists("sessid", $_REQUEST) && strlen($_REQUEST["sessid"]) > 0)
{
	$isSessidValid = check_bitrix_sessid();
}

if ($_REQUEST["mobile_action"])//Executing some action
{
	$APPLICATION->RestartBuffer();
	$action = $_REQUEST["mobile_action"];
	$actionList = new Bitrix\Mobile\Action();
	$actionList->executeAction($action, $arParams);

	require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
	die();
}

elseif ($_REQUEST["captcha_sid"])//getting captcha image
{
	$APPLICATION->RestartBuffer();
	$actionList = new Bitrix\Mobile\Action();
	$actionList->executeAction("get_captcha", $arParams);
	die();
}
elseif ($_REQUEST["manifest_id"])//getting content of appcache manifest
{
	include($_SERVER["DOCUMENT_ROOT"] .\Bitrix\Main\Data\AppCacheManifest::MANIFEST_CHECK_FILE);
	die();
}
elseif(!$USER->IsAuthorized() || !$isSessidValid)
{
	$APPLICATION->RestartBuffer();
	header("HTTP/1.0 401 Not Authorized");
	if(Bitrix\MobileApp\Mobile::getInstance()->getInstance() != "android")
	{
		header("Content-Type: application/x-javascript");
		header("BX-Authorize: ".bitrix_sessid());
	}

	echo json_encode(Array("status" => "failed", "bitrix_sessid"=>bitrix_sessid()));
	die();
}

$this->IncludeComponentTemplate();
?>
