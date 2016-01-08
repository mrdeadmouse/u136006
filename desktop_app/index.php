<?
require($_SERVER["DOCUMENT_ROOT"]."/desktop_app/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
if (intval($USER->GetID()) <= 0)
{
	?>
<script type="text/javascript">
	if (typeof(BXDesktopSystem) != 'undefined')
		BXDesktopSystem.Login({});
	else
		location.href = '/';
</script><?
	return true;
}
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/im/install/public/desktop_app/index.php");

if (!CModule::IncludeModule('im'))
	return;

CJSCore::Init(array('im_desktop'));
?>
<script type="text/javascript">
	if (typeof(BXDesktopSystem) != 'undefined')
		BX.desktop.init();
	else
		location.href = '/';
</script>
<?
$APPLICATION->IncludeComponent("bitrix:im.messenger", "", Array("DESKTOP" => "Y"), false, Array("HIDE_ICONS" => "Y"));

$diskEnabled = false;
if(IsModuleInstalled('disk'))
{
	$diskEnabled =
		\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) &&
		CModule::includeModule('disk');
	if($diskEnabled && \Bitrix\Disk\Configuration::REVISION_API >= 5)
	{
		$APPLICATION->IncludeComponent('bitrix:disk.bitrix24disk', '', array('AJAX_PATH' => '/desktop_app/disk.ajax.new.php'), false, Array("HIDE_ICONS" => "Y"));
	}
	else
	{
		$diskEnabled = false;
	}
}
if(!$diskEnabled && IsModuleInstalled('webdav'))
{
	$APPLICATION->IncludeComponent('bitrix:webdav.disk', '', array('AJAX_PATH' => '/desktop_app/disk.ajax.php'), false, Array("HIDE_ICONS" => "Y"));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
