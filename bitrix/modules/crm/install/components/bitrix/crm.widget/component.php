<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arResult['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : 'crm_widget';
$arResult['HEIGHT'] = isset($arParams['HEIGHT']) ? (int)$arParams['HEIGHT'] : 360;
$arResult['LAYOUT'] = isset($arParams['LAYOUT']) ? $arParams['LAYOUT'] : '100';

$filter = new Bitrix\Crm\Widget\Filter(isset($arParams['FILTER']) ? $arParams['FILTER'] : array());
$settings = isset($arParams['SETTINGS']) ? $arParams['SETTINGS'] : array();
$results = array();
foreach($settings as $setting)
{
	$typeName = isset($setting['typeName']) ? $setting['typeName'] : '';
	if($typeName === 'funnel')
	{
		$widget = new Bitrix\Crm\Widget\DealFunnelWidget($setting, $filter);
		$setting['data'] = $widget->prepareData();
		$setting['valueField'] = 'TOTAL';
		$setting['titleField'] = 'NAME';
	}
	elseif($typeName === 'graph' || $typeName === 'bar')
	{
		$widget = new Bitrix\Crm\Widget\DealGraphWidget($setting, $filter);
		$setting['data'] = $widget->prepareData();
		$setting['dateFormat'] = 'YYYY-MM-DD';
	}
	elseif($typeName === 'number')
	{
		$widget = new Bitrix\Crm\Widget\DealNumericWidget($setting, $filter);
		$setting['data'] = $widget->prepareData();
	}
	elseif($typeName === 'rating')
	{
		$widget = new Bitrix\Crm\Widget\DealRatingWidget($setting, $filter);
		$setting['data'] = $widget->prepareData();
	}
	$results[] = $setting;
}
$arResult['SETTINGS'] = $results;
$this->IncludeComponentTemplate();
