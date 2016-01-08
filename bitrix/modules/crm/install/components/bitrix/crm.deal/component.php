<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;
if(!CCrmQuote::LocalComponentCausedUpdater())
	return;

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'funnel' => 'funnel/',
	'import' => 'import/',
	'widget' => 'widget/',
	'edit' => 'edit/#deal_id#/',
	'show' => 'show/#deal_id#/'
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('deal_id');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = 'index';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		if(strlen($arParams['PATH_TO_DEAL_'.strToUpper($url)]) <= 0)
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['SEF_FOLDER'].$value;
		else
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['PATH_TO_'.strToUpper($url)];
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['deal_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['card']))
		$componentPage = 'card';
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['import']))
		$componentPage = 'import';
	else if (isset($_REQUEST['widget']))
		$componentPage = 'widget';

	$arResult['PATH_TO_DEAL_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_DEAL_FUNNEL'] = $APPLICATION->GetCurPage().'&funnel';
	$arResult['PATH_TO_DEAL_SHOW'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&show";
	$arResult['PATH_TO_DEAL_EDIT'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&edit";
	$arResult['PATH_TO_DEAL_IMPORT'] = $APPLICATION->GetCurPage()."?import";
	$arResult['PATH_TO_DEAL_WIDGET'] = $APPLICATION->GetCurPage()."?widget";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
	),
	$arResult
);

$arResult['NAVIGATION_CONTEXT_ID'] = 'DEAL';
$this->IncludeComponentTemplate($componentPage);
?>