<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
//$arParams['PATH_TO_QUOTE_IMPORT'] = CrmCheckPath('PATH_TO_QUOTE_IMPORT', $arParams['PATH_TO_QUOTE_IMPORT'], $APPLICATION->GetCurPage().'?import');

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_quote_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'EXPORT');
	//$bImport = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$bExport = false;
	//$bImport = false;

	$bRead   = CCrmQuote::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmQuote::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmQuote::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmQuote::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_ADD'),
			'TITLE' => GetMessage('QUOTE_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
				array(
					'quote_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	/*if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_IMPORT'),
			'TITLE' => GetMessage('QUOTE_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}*/

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('QUOTE_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('QUOTE_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'csv', 'ncc' => '1')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('QUOTE_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('QUOTE_EXPORT_EXCEL'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'excel', 'ncc' => '1')
			),
			'ICON' => 'btn-export'
		);
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'LINKS' => array(
			array(
				'DEFAULT' => true,
				'TEXT' => GetMessage('QUOTE_PAYMENT_HTML'),
				'TITLE' => GetMessage('QUOTE_PAYMENT_HTML_TITLE'),
				'ONCLICK' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: false }])"
			),
			array(
				'TEXT' => GetMessage('QUOTE_PAYMENT_HTML_BLANK'),
				'TITLE' => GetMessage('QUOTE_PAYMENT_HTML_BLANK_TITLE'),
				'ONCLICK' => "BX.onCustomEvent(window, 'CrmQuotePrint', [this, { blank: true }])"
			)
		),
		'TYPE' => 'toolbar-split-left',
		'ICON' => 'btn-print'
	);

	if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
	{
		$arResult['BUTTONS'][] = array(
			'LINKS' => array(
				array(
					'DEFAULT' => true,
					'TEXT' => GetMessage('QUOTE_PAYMENT_PDF'),
					'TITLE' => GetMessage('QUOTE_PAYMENT_PDF_TITLE'),
					'ONCLICK' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: false }])"
				),
				array(
					'TEXT' => GetMessage('QUOTE_PAYMENT_PDF_BLANK'),
					'TITLE' => GetMessage('QUOTE_PAYMENT_PDF_BLANK_TITLE'),
					'ONCLICK' => "BX.onCustomEvent(window, 'CrmQuoteDownloadPdf', [this, { blank: true }])"
				)
			),
			'TYPE' => 'toolbar-split-left',
			'ICON' => 'btn-download'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_PAYMENT_EMAIL'),
			'TITLE' => GetMessage('QUOTE_PAYMENT_EMAIL_TITLE'),
			'LINK' => '#',
			'TYPE' => 'toolbar-left',
			'ICON' => 'btn-letter',
			'ONCLICK' => "BX.onCustomEvent(window, 'CrmQuoteSendByEmail', [this])"
		);
	}

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('QUOTE_EDIT'),
			'TITLE' => GetMessage('QUOTE_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
				array(
					'quote_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_SHOW'),
		'TITLE' => GetMessage('QUOTE_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_COPY'),
		'TITLE' => GetMessage('QUOTE_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_DELETE'),
		'TITLE' => GetMessage('QUOTE_DELETE_TITLE'),
		'LINK' => "javascript:quote_delete('".GetMessage('QUOTE_DELETE_DLG_TITLE')."', '".GetMessage('QUOTE_DELETE_DLG_MESSAGE')."', '".GetMessage('QUOTE_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('QUOTE_ADD'),
		'TITLE' => GetMessage('QUOTE_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
			array(
				'quote_id' => 0
			)
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
