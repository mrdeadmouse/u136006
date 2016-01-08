<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$selectedEntityType = $arResult['SELECTED_ENTITY_TYPE'];


$entityData = &$arResult['ENTITY_DATA'];

$entityDatum = isset($entityData[$selectedEntityType]) ? $entityData[$selectedEntityType] : array();
$models = array();
if($selectedEntityType === CCrmOwnerType::CompanyName)
{
	foreach($entityDatum['ITEMS'] as &$item):
		$models[] = CCrmMobileHelper::PrepareCompanyData($item);
	endforeach;
	unset($item);
}
elseif($selectedEntityType === CCrmOwnerType::ContactName)
{
	foreach($entityDatum['ITEMS'] as &$item):
		$models[] = CCrmMobileHelper::PrepareContactData($item);
	endforeach;
	unset($item);
}
echo CUtil::PhpToJSObject(
	array(
		'DATA' => array(
			'MODELS' => $models,
			'NEXT_PAGE_URL' => $entityDatum['NAVIGATION']['NEXT_PAGE_URL']
		)
	)
);
die();
