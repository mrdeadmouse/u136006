<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

//$enableDemo = $arResult['ENABLE_DEMO'] = isset($_REQUEST['demo']) && strtoupper($_REQUEST['demo']) === 'Y';

$arResult['PATH_TO_WIDGET'] = isset($arParams['PATH_TO_WIDGET']) ? $arParams['PATH_TO_WIDGET'] : $APPLICATION->GetCurPage();
$arResult['PATH_TO_LIST'] = isset($arParams['PATH_TO_LIST']) ? $arParams['PATH_TO_LIST'] : $APPLICATION->GetCurPage();

$arResult['GUID'] = $arParams['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : 'crm_widget_panel';
$arResult['ENTITY_TYPE'] = $arParams['ENTITY_TYPE'] = isset($arParams['ENTITY_TYPE']) ? strtoupper($arParams['ENTITY_TYPE']) : '';
$arResult['LAYOUT'] = $arParams['LAYOUT'] =isset($arParams['LAYOUT']) ? $arParams['LAYOUT'] : '50/50';
$arResult['NAVIGATION_CONTEXT_ID'] = $arParams['NAVIGATION_CONTEXT_ID'] =isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$options = CUserOptions::GetOption('crm.widget_panel', $arResult['GUID'], array());

$enableDemo = $arResult['ENABLE_DEMO'] = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';
$arParams['ROWS'] = isset($arParams['ROWS']) ? $arParams['ROWS'] : array();
if(!$enableDemo)
{
	if(isset($options['rows']))
	{
		$arParams['ROWS'] = $options['rows'];
	}
}

$arParams['FILTER'] = isset($arParams['FILTER']) ? $arParams['FILTER'] : array();
$arResult['FILTER'] = array(
	array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('CRM_FILTER_FIELD_RESPONSIBLE'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
	array('id' => 'PERIOD', 'name' => GetMessage('CRM_FILTER_FIELD_PERIOD'), 'default' => true, 'enable_settings' => true, 'type' => 'period')
);

$gridOptions = new CGridOptions($arResult['GUID']);
$arResult['FILTER_FIELDS'] = $gridOptions->GetFilter($arResult['FILTER']);
$arResult['WIDGET_FILTER'] = Bitrix\Crm\Widget\Filter::internalizeParams($arResult['FILTER_FIELDS']);

$gridSettings = $gridOptions->GetOptions();
$visibleRows = isset($gridSettings['filter_rows']) ? explode(',', $gridSettings['filter_rows']) : array();

$arResult['FILTER_ROWS'] = array(
	'RESPONSIBLE_ID' => true,
	'PERIOD' => true
);

$arResult['FILTER_PRESETS'] = array(
	'filter_current_month' => array(
		'name' => Bitrix\Crm\Widget\FilterPeriodType::getDescription(Bitrix\Crm\Widget\FilterPeriodType::CURRENT_MONTH),
		'fields' => array('PERIOD' => Bitrix\Crm\Widget\FilterPeriodType::CURRENT_MONTH)
	),
	'filter_current_quarter' => array(
		'name' => Bitrix\Crm\Widget\FilterPeriodType::getDescription(Bitrix\Crm\Widget\FilterPeriodType::CURRENT_QUARTER),
		'fields' => array('PERIOD' => Bitrix\Crm\Widget\FilterPeriodType::CURRENT_QUARTER)
	)
);

if(!empty($visibleRows))
{
	foreach(array_keys($arResult['FILTER_ROWS']) as $k)
	{
		$arResult['FILTER_ROWS'][$k] = in_array($k, $visibleRows);
	}
}
$arResult['OPTIONS']['filters'] = array_merge($arResult['FILTER_PRESETS'], $gridSettings['filters']);

$commonFilter = new Bitrix\Crm\Widget\Filter($arResult['WIDGET_FILTER']);
if($commonFilter->isEmpty())
{
	$commonFilter->setPeriodTypeID(Bitrix\Crm\Widget\FilterPeriodType::LAST_DAYS_30);
	$arResult['WIDGET_FILTER'] = $commonFilter->getParams();
}

$demoRows = null;
if($enableDemo)
{
	$demoRows = (include $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/crm.widget_panel/data.php");
}

$arResult['ROWS'] = array();
$rowQty = count($arParams['ROWS']);
for($i = 0; $i < $rowQty; $i++)
{
	if(!isset($arParams['ROWS'][$i]))
	{
		continue;
	}

	$rowConfig = $arParams['ROWS'][$i];
	$row = array('cells' => array());
	if(isset($rowConfig['height']))
	{
		$row['height'] = $rowConfig['height'];
	}

	$cellConfigs = isset($rowConfig['cells']) ? $rowConfig['cells'] : array();
	$cellQty = count($cellConfigs);
	for($j = 0; $j < $cellQty; $j++)
	{
		$cell = array('controls' => array(), 'data' => array());
		$cellConfig = isset($cellConfigs[$j]) ? $cellConfigs[$j] : array();
		$controls = isset($cellConfig['controls']) ? $cellConfig['controls'] : array();
		$controlQty = count($controls);
		for($k = 0; $k < $controlQty; $k++)
		{
			$control = $controls[$k];
			$cell['controls'][] = $control;

			if(isset($control['filter']) && is_array($control['filter']))
			{
				$filter = new Bitrix\Crm\Widget\Filter($control['filter']);
				if($filter->isEmpty())
				{
					$filter = $commonFilter;
				}
			}
			else
			{
				$filter = $commonFilter;
			}

			$widget = Bitrix\Crm\Widget\WidgetFactory::create($control, $filter);
			if(!$enableDemo)
			{
				$cell['data'][] = $widget->prepareData();
			}
			else
			{
				$cell['data'][] = $widget->initializeDemoData(
					isset($demoRows[$i]['cells'][$j]['data']) ? $demoRows[$i]['cells'][$j]['data'] : array()
				);
			}
		}
		$row['cells'][] = $cell;
	}
	$arResult['ROWS'][] = $row;
}

$arResult['NEED_FOR_REBUILD_DEAL_STATISTICS'] = false;
if(!$enableDemo
	&& CCrmPerms::IsAdmin()
	&& $arResult['ENTITY_TYPE'] === CCrmOwnerType::DealName
	&& COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_STATISTICS', 'N') === 'Y')
{
	$arResult['NEED_FOR_REBUILD_DEAL_STATISTICS'] = true;
}

$this->IncludeComponentTemplate();
