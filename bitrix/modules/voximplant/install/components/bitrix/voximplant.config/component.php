<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$mode = '';
if (in_array($_REQUEST['MODE'], Array(CVoxImplantConfig::MODE_LINK, CVoxImplantConfig::MODE_RENT, CVoxImplantConfig::MODE_SIP)))
{
	if (isset($_POST['MODE']))
	{
		$mode = $_POST['MODE'];
	}
	else if (isset($_GET['MODE']))
	{
		$mode = $_GET['MODE'];
	}
}

$arResult = Array(
	'MODE_LINK' => CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_LINK),
	'MODE_RENT' => CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_RENT),
	'MODE_SIP' => CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP),
	'MODE_ACTIVE' => $mode,
);

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>