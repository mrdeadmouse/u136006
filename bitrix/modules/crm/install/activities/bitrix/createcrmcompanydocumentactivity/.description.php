<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_DESC'),
	'TYPE' => 'activity',
	'CLASS' => 'CreateCrmCompanyDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
	),
	'RETURN' => array(
		'CompanyId' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_CREATE_COMPANY_ID'),
			'TYPE' => 'int',
		),
	),
);
?>
