<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_IMPORT'] = CrmCheckPath('PATH_TO_DEAL_IMPORT', $arParams['PATH_TO_DEAL_IMPORT'], $APPLICATION->GetCurPage().'?import');

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_deal_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$bExport = false;
	$bImport = false;

	$bRead   = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmDeal::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_ADD'),
			'TITLE' => GetMessage('DEAL_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_IMPORT'),
			'TITLE' => GetMessage('DEAL_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'csv', 'ncc' => '1')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_EXCEL'),
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

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Deal,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'deal_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.deal.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']))
{
	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_EDIT'),
			'TITLE' => GetMessage('DEAL_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_SHOW'),
		'TITLE' => GetMessage('DEAL_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_COPY'),
		'TITLE' => GetMessage('DEAL_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
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
		'TEXT' => GetMessage('DEAL_DELETE'),
		'TITLE' => GetMessage('DEAL_DELETE_TITLE'),
		'LINK' => "javascript:deal_delete('".GetMessage('DEAL_DELETE_DLG_TITLE')."', '".GetMessage('DEAL_DELETE_DLG_MESSAGE')."', '".GetMessage('DEAL_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_ADD'),
		'TITLE' => GetMessage('DEAL_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => 0
			)
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
