<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_EVENT_ADD_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_EVENT_ADD_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CrmEventAddActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
	),
);
?>
