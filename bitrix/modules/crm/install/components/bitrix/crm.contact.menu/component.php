<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_CONTACT_IMPORT'] = CrmCheckPath('PATH_TO_CONTACT_IMPORT', $arParams['PATH_TO_CONTACT_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_DEDUPE'] = CrmCheckPath('PATH_TO_CONTACT_DEDUPE', $arParams['PATH_TO_CONTACT_DEDUPE'], $APPLICATION->GetCurPage());

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_contact_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();


if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;

	$bDedupe = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE')
		&& !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE');
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmContact::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmContact::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmContact::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmContact::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_ADD'),
			'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
				array('contact_id' => 0)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_VCARD'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_VCARD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_IMPORTVCARD'], array()),
			'ICON' => 'btn-import'
		);

		$importUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_IMPORT'], array());

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_GMAIL'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_GMAIL_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'gmail')),
			'ICON' => 'btn-import'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_OUTLOOK'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_OUTLOOK_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'outlook')),
			'ICON' => 'btn-import'
		);

		if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CRM_CONTACT_IMPORT_YANDEX'),
				'TITLE' => GetMessage('CRM_CONTACT_IMPORT_YANDEX_TITLE'),
				'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'yandex')),
				'ICON' => 'btn-import'
			);
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_YAHOO'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_YAHOO_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'yahoo')),
			'ICON' => 'btn-import'
		);

		if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CRM_CONTACT_IMPORT_MAILRU'),
				'TITLE' => GetMessage('CRM_CONTACT_IMPORT_MAILRU_TITLE'),
				'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'mailru')),
				'ICON' => 'btn-import'
			);
		}

		/*
		* LIVEMAIL is temporary disabled due to implementation error
		* $arResult['BUTTONS'][] = array(
		*  'TEXT' => GetMessage('CRM_CONTACT_IMPORT_LIVEMAIL'),
		*  'TITLE' => GetMessage('CRM_CONTACT_IMPORT_LIVEMAIL_TITLE'),
		*  'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'livemail')),
		*  'ICON' => 'btn-import'
		);*/

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_CUSTOM'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_CUSTOM_TITLE'),
			'LINK' => $importUrl,
			'ICON' => 'btn-import',
			'MENU' => array(array('TITLE' => 'X', 'TEXT' => 'X', 'ONCLICK' => 'alert();'))
		);
	}

	if ($bExport)
	{
		if($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}

		$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_CSV_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_CSV'),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
					array('type' => 'csv', 'ncc' => '1')
				),
				'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_EXCEL_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_EXCEL'),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
					array('type' => 'excel', 'ncc' => '1')
				),
				'ICON' => 'btn-export'
		);

		if (IsModuleInstalled('webservice') && CModule::IncludeModule('webservice'))
		{
			$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/crm/outlook.js');

			$rsSites = CSite::GetByID(SITE_ID);
			$arSite = $rsSites->Fetch();
			if (strlen($arSite['SITE_NAME']) > 0)
				$sPrefix = $arSite['SITE_NAME'];
			else
				$sPrefix = COption::GetOptionString('main', 'site_name', GetMessage('CRM_OUTLOOK_PREFIX_CONTACTS'));

			$GUID = CCrmContactWS::makeGUID(md5($_SERVER['SERVER_NAME'].'|'.$type));
			$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK'),
				'ONCLICK' => "jsOutlookUtils.Sync('contacts', '/bitrix/tools/ws_contacts_crm/', '".$APPLICATION->GetCurPage()."', '".CUtil::JSEscape($sPrefix)."', '".CUtil::JSEscape(GetMessage('CRM_OUTLOOK_TITLE_CONTACTS'))."', '$GUID')",
				'ICON' => 'btn-export'
			);
		}
	}

	if ($bDedupe)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CONTACT_DEDUPE'),
			'TITLE' => GetMessage('CONTACT_DEDUPE_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_DEDUPE'], array()),
			'ICON' => 'btn-dedupe'
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
		CCrmOwnerType::Contact,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'contact_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.contact.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_CONTACT_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_CONTACT_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_CONTACT_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_CONTACT_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if (($arParams['TYPE'] == 'show') && $bWrite && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_EDIT'),
		'TITLE' => GetMessage('CRM_CONTACT_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_SHOW'),
		'TITLE' => GetMessage('CRM_CONTACT_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_COPY'),
		'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
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

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CONTACT_EDIT'],
			array('contact_id' => 0)
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

if ($arParams['TYPE'] == 'show' && !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
{
	$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arParams['ELEMENT_ID'],  'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'COMPANY_ID'));
	$arFields = $dbRes->Fetch();

	$arResult['BUTTONS'][]= array(
		'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
		'LINK' => CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
			array('contact_id' =>$arParams['ELEMENT_ID'], 'company_id' => $arFields['COMPANY_ID'])
		),
		'ICONCLASS' => 'btn-add-deal'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
		'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
		'LINK' => "javascript:contact_delete('".GetMessage('CRM_CONTACT_DELETE_DLG_TITLE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();

?>
