<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$platform = "android";
if(CModule::IncludeModule("mobileapp"))
{
	CMobile::Init();
	$platform = CMobile::$platform;
}
else
{
	die();
}

\Bitrix\Main\Data\AppCacheManifest::getInstance()->setManifestCheckFile(SITE_DIR."mobile/");

define("MOBILE_MODULE_VERSION", "155102");
$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");

$APPLICATION->IncludeComponent("bitrix:mobile.data","",Array(
	"START_PAGE" => SITE_DIR."mobile/index.php?version=".$moduleVersion,
	"MENU_PAGE" => SITE_DIR."mobile/left.php?version=".$moduleVersion,
	"CHAT_PAGE" => SITE_DIR."mobile/im/right.php?version=".$moduleVersion
),false, Array("HIDE_ICONS" => "Y"));
?><!DOCTYPE html>
<html<?=$APPLICATION->ShowProperty("manifest");?> class="<?=$platform;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=<?=SITE_CHARSET?>"/>
	<meta name="format-detection" content="telephone=no">
	<link href="<?=CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH.(defined('MOBILE_TEMPLATE_CSS')? MOBILE_TEMPLATE_CSS: "/template_styles.css"))?>" type="text/css" rel="stylesheet" />
	<?$APPLICATION->AddBufferContent(array(&$APPLICATION, "GetHeadStrings"), 'BEFORE_CSS');?>
	<?$APPLICATION->ShowHeadStrings(true);?>
	<?$APPLICATION->ShowHeadStrings();?>
	<?$APPLICATION->ShowHeadScripts();?>

	<?CJSCore::Init('ajax');?>
	<title><?$APPLICATION->ShowTitle()?></title>
</head>
<body class="<?=$APPLICATION->ShowProperty("BodyClass");?>"><?
?><script>
	BX.message({
		MobileSiteDir: '<?=CUtil::JSEscape(htmlspecialcharsbx(SITE_DIR))?>'
	});
</script><?
?>