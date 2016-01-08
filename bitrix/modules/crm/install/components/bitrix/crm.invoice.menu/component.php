<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_PAYMENT'] = CrmCheckPath('PATH_TO_INVOICE_PAYMENT', $arParams['PATH_TO_INVOICE_PAYMENT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&payment');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['TYPE'] = $arParams['TYPE'];

$arResult['BUTTONS'] = array();
$arFields = array();

$arParams['ELEMENT_ID'] = intval($arParams['ELEMENT_ID']);

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFields = CCrmInvoice::GetByID($arParams['ELEMENT_ID']);

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('INVOICE', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('INVOICE', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('INVOICE', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('INVOICE_ADD'),
			'TITLE' => GetMessage('INVOICE_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
				array(
					'invoice_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

//		if ($bImport)
//		{
//			$arResult['BUTTONS'][] = array(
//				'TEXT' => GetMessage('INVOICE_IMPORT'),
//				'TITLE' => GetMessage('INVOICE_IMPORT_TITLE'),
//				'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_IMPORT'], array()),
//				'ICON' => 'btn-import'
//			);
//		}

		if ($bExport)
		{
			$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('INVOICE_EXPORT_CSV_TITLE'),
				'TEXT' => GetMessage('INVOICE_EXPORT_CSV'),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
					array('type' => 'csv', 'ncc' => '1')
				),
				'ICON' => 'btn-export'
			);

			$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('INVOICE_EXPORT_EXCEL_TITLE'),
				'TEXT' => GetMessage('INVOICE_EXPORT_EXCEL'),
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
				'TEXT' => GetMessage('INVOICE_PAYMENT_HTML'),
				'TITLE' => GetMessage('INVOICE_PAYMENT_HTML_TITLE'),
				'ONCLICK' => "jsUtils.OpenWindow('".CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_INVOICE_PAYMENT'],
						array('invoice_id' => $arParams['ELEMENT_ID'])
					),
					array('PRINT' => 'Y', 'ncc' => '1'))."', 960, 600)"
			),
			array(
				'TEXT' => GetMessage('INVOICE_PAYMENT_HTML_BLANK'),
				'TITLE' => GetMessage('INVOICE_PAYMENT_HTML_BLANK_TITLE'),
				'ONCLICK' => "jsUtils.OpenWindow('".CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_INVOICE_PAYMENT'],
						array('invoice_id' => $arParams['ELEMENT_ID'])
					),
					array('PRINT' => 'Y', 'BLANK' => 'Y', 'ncc' => '1'))."', 960, 600)"
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
					'TEXT' => GetMessage('INVOICE_PAYMENT_PDF'),
					'TITLE' => GetMessage('INVOICE_PAYMENT_PDF_TITLE'),
					'ONCLICK' => "jsUtils.Redirect(null, '".CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_INVOICE_PAYMENT'],
							array('invoice_id' => $arParams['ELEMENT_ID'])
						),
						array('pdf' => 1, 'DOWNLOAD' => 'Y', 'ncc' => '1'))."')"
				),
				array(
					'TEXT' => GetMessage('INVOICE_PAYMENT_PDF_BLANK'),
					'TITLE' => GetMessage('INVOICE_PAYMENT_PDF_BLANK_TITLE'),
					'ONCLICK' => "jsUtils.Redirect(null, '".CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_INVOICE_PAYMENT'],
							array('invoice_id' => $arParams['ELEMENT_ID'])
						),
						array('pdf' => 1, 'DOWNLOAD' => 'Y', 'BLANK' => 'Y', 'ncc' => '1'))."')"
				)
			),
			'TYPE' => 'toolbar-split-left',
			'ICON' => 'btn-download'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('INVOICE_PAYMENT_EMAIL'),
			'TITLE' => GetMessage('INVOICE_PAYMENT_EMAIL_TITLE'),
			'LINK' => '#',
			'TYPE' => 'toolbar-left',
			'ICON' => 'btn-letter',
			'ONCLICK' => 'onCrmInvoiceSendEmailButtClick()'
		);
	}

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('INVOICE_EDIT'),
			'TITLE' => GetMessage('INVOICE_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
				array(
					'invoice_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_SHOW'),
		'TITLE' => GetMessage('INVOICE_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_SHOW'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('INVOICE_COPY'),
		'TITLE' => GetMessage('INVOICE_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
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
		'TEXT' => GetMessage('INVOICE_DELETE'),
		'TITLE' => GetMessage('INVOICE_DELETE_TITLE'),
		'LINK' => "javascript:invoice_delete('".GetMessage('INVOICE_DELETE_DLG_TITLE')."', '".
			GetMessage('INVOICE_DELETE_DLG_MESSAGE')."', '".GetMessage('INVOICE_DELETE_DLG_BTNTITLE').
			"', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
			array(
				'invoice_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

//	if ($bAdd)
//	{
//		$arResult['BUTTONS'][] = array(
//			'TEXT' => GetMessage('INVOICE_ADD'),
//			'TITLE' => GetMessage('INVOICE_ADD_TITLE'),
//			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
//				array(
//					'invoice_id' => 0
//				)
//			),
//			'ICON' => 'btn-new'
//		);
//	}

$this->IncludeComponentTemplate();
?>
