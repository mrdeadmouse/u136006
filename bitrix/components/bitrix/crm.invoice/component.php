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

global $APPLICATION;

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'edit' => 'edit/#invoice_id#/',
	'show' => 'show/#invoice_id#/',
	'payment' => 'payment/#invoice_id#/'
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('invoice_id');

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
		if(strlen($arParams['PATH_TO_INVOICE_'.strToUpper($url)]) <= 0)
			$arResult['PATH_TO_INVOICE_'.strToUpper($url)] = $arParams['SEF_FOLDER'].$value;
		else
			$arResult['PATH_TO_INVOICE_'.strToUpper($url)] = $arParams['PATH_TO_'.strToUpper($url)];
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['invoice_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['payment']))
		$componentPage = 'payment';

	$curPage = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_INVOICE_LIST'] = $curPage;
	$arResult['PATH_TO_INVOICE_SHOW'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&show";
	$arResult['PATH_TO_INVOICE_EDIT'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&edit";
	$arResult['PATH_TO_INVOICE_PAYMENT'] = $curPage."?$arVariableAliases[invoice_id]=#invoice_id#&payment";
	$arResult['PATH_TO_PRODUCT_EDIT'] = $curPage."?$arVariableAliases[product_id]=#product_id#&edit";
	$arResult['PATH_TO_PRODUCT_SHOW'] = $curPage."?$arVariableAliases[product_id]=#product_id#&show";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
		'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
	),
	$arResult
);

$this->IncludeComponentTemplate($componentPage);
?>