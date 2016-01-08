<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/** CMain $APPLICATION */
global $APPLICATION;
define('CRM_REPORT_UPDATE_14_5_2_MESSAGE', 'Y');
$APPLICATION->ShowViewContent('REPORT_UPDATE_14_5_2_MESSAGE');
?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_DEAL'))?></h3>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmReportHelper'
	),
	false
);?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_PRODUCT'))?></h3>
<?$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmProductReportHelper'
	),
	false
);?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_LEAD'))?></h3>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmLeadReportHelper'
	),
	false
);?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_INVOICE'))?></h3>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmInvoiceReportHelper'
	),
	false
);?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_ACTIVITY'))?></h3>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmActivityReportHelper'
	),
	false
);?>