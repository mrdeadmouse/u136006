<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

$CIMNotify = new CIMNotify(false, Array(
	'hide_link' => false
));
$arResult = $CIMNotify->GetNotifyList();

$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "ml-notify");
$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>