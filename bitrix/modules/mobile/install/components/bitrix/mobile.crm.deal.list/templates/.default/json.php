<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$models = array();
foreach($arResult['ITEMS'] as &$item):
	$clientTitle = '';
	if($item['~CONTACT_ID'] > 0)
		$clientTitle = $item['~CONTACT_FORMATTED_NAME'];
	if($item['~COMPANY_ID'] > 0 && $item['~COMPANY_TITLE'] !== ''):
		if($clientTitle !== '')
			$clientTitle .= ', ';
		$clientTitle .= $item['~COMPANY_TITLE'];
	endif;

	$stageID = $item['~STAGE_ID'];
	$stageSort = CCrmDeal::GetStageSort($stageID);
	$finalStageSort = CCrmDeal::GetFinalStageSort();

	$models[] = array(
		'ID' => $item['~ID'],
		'TITLE' => $item['~TITLE'],
		'STAGE_ID' => $item['~STAGE_ID'],
		'STAGE_NAME' => $item['~STAGE_NAME'],
		'PROBABILITY' => $item['~PROBABILITY'],
		'OPPORTUNITY' => $item['~OPPORTUNITY'],
		'FORMATTED_OPPORTUNITY' => $item['~FORMATTED_OPPORTUNITY'],
		'CURRENCY_ID' => $item['~CURRENCY_ID'],
		'ASSIGNED_BY_ID' => $item['~ASSIGNED_BY_ID'],
		'ASSIGNED_BY_FORMATTED_NAME' => $item['~ASSIGNED_BY_FORMATTED_NAME'],
		'CONTACT_ID' => $item['~CONTACT_ID'],
		'CONTACT_FORMATTED_NAME' => $item['~CONTACT_FORMATTED_NAME'],
		'COMPANY_ID' => $item['~COMPANY_ID'],
		'COMPANY_TITLE' => $item['~COMPANY_TITLE'],
		'COMMENTS' => $item['COMMENTS'],
		'DATE_CREATE' => $item['~DATE_CREATE'],
		'DATE_MODIFY' => $item['~DATE_MODIFY'],
		'SHOW_URL' => $item['SHOW_URL'],
		'CONTACT_SHOW_URL' => $item['CONTACT_SHOW_URL'],
		'COMPANY_SHOW_URL' => $item['COMPANY_SHOW_URL'],
		'ASSIGNED_BY_SHOW_URL' => $item['ASSIGNED_BY_SHOW_URL'],
		'CLIENT_TITLE' => $clientTitle,
		'IS_FINISHED' => $stageSort >= $finalStageSort,
		'IS_SUCCESSED' => $stageSort === $finalStageSort
	);

endforeach;

echo CUtil::PhpToJSObject(
	array(
		'DATA' => array(
			'MODELS' => $models,
			'NEXT_PAGE_URL' => $arResult['NEXT_PAGE_URL'],
			'GRID_FILTER_ID' => $arResult['GRID_FILTER_ID'],
			'GRID_FILTER_NAME' => $arResult['GRID_FILTER_NAME']
		)
	)
);
die();
