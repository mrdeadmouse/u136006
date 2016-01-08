<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('subscribe'))
{
	ShowError(GetMessage('SUBSCRIBE_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_SM_CONFIG'] = CrmCheckPath('PATH_TO_SM_CONFIG', $arParams['PATH_TO_SM_CONFIG'], $APPLICATION->GetCurPage());
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;

CUtil::InitJSCore();
$bVarsFromForm = false;
$sMailFrom = COption::GetOptionString('crm', 'email_from');

if (empty($sMailFrom))
{
	$sMailFrom = COption::GetOptionString('crm', 'mail', '');
}

//Disable fake address generation for Bitrix24
if (empty($sMailFrom) && !IsModuleInstalled('bitrix24'))
{
	$sHost = $_SERVER['HTTP_HOST'];
	if (strpos($sHost, ':') !== false)
		$sHost = substr($sHost, 0, strpos($sHost, ':'));

	$sMailFrom = 'crm@'.$sHost;
}

$dupControl = \Bitrix\Crm\Integrity\DuplicateControl::getCurrent();

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		$sError = '';

		/*Account number template settings*/
		$APPLICATION->ResetException();
		include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/components/bitrix/crm.config.invoice.number/post_proc.php");
		if ($ex = $APPLICATION->GetException())
			$sError = $ex->GetString();

		$APPLICATION->ResetException();
		include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/components/bitrix/crm.config.number/post_proc.php");
		if ($ex = $APPLICATION->GetException())
			$sError = $ex->GetString();

		$APPLICATION->ResetException();

		if (strlen($sError) > 0)
			ShowError($sError.'<br>');
		else
		{
			CCrmActivityCalendarSettings::SetValue(
				CCrmActivityCalendarSettings::DisplayCompletedCalls,
				isset($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']) && strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']) !== 'N'
			);

			CCrmActivityCalendarSettings::SetValue(
				CCrmActivityCalendarSettings::DisplayCompletedMeetings,
				isset($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']) && strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']) !== 'N'
			);

			CCrmUserCounterSettings::SetValue(
				CCrmUserCounterSettings::ReckonActivitylessItems,
				isset($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) && strtoupper($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) !== 'N'
			);

			CCrmEMailCodeAllocation::SetCurrent(
				isset($_POST['SERVICE_CODE_ALLOCATION'])
					? intval($_POST['SERVICE_CODE_ALLOCATION'])
					: CCrmEMailCodeAllocation::Body
			);
			
			if(Bitrix\Crm\Integration\Bitrix24Email::isEnabled()
				&& Bitrix\Crm\Integration\Bitrix24Email::allowDisableSignature())
			{
				Bitrix\Crm\Integration\Bitrix24Email::enableSignature(
					isset($_POST['ENABLE_B24_EMAIL_SIGNATURE']) && strtoupper($_POST['ENABLE_B24_EMAIL_SIGNATURE']) !== 'N'
				);
			}

			$isCallSettingsChanged = false;

			$oldCalltoFormat = CCrmCallToUrl::GetFormat(0);
			$newCalltoFormat = isset($_POST['CALLTO_FORMAT']) ? intval($_POST['CALLTO_FORMAT']) : CCrmCallToUrl::Slashless;
			if ($oldCalltoFormat != $newCalltoFormat)
			{
				CCrmCallToUrl::SetFormat($newCalltoFormat);
				$isCallSettingsChanged = true;
			}

			$oldCalltoSettings = $newCalltoSettings = CCrmCallToUrl::GetCustomSettings();
			if($newCalltoFormat === CCrmCallToUrl::Custom)
			{
				$newCalltoSettings['URL_TEMPLATE'] = isset($_POST['CALLTO_URL_TEMPLATE']) ? $_POST['CALLTO_URL_TEMPLATE'] : '';
				$newCalltoSettings['CLICK_HANDLER'] = isset($_POST['CALLTO_CLICK_HANDLER']) ? $_POST['CALLTO_CLICK_HANDLER'] : '';
			}
			$newCalltoSettings['NORMALIZE_NUMBER'] = isset($_POST['CALLTO_NORMALIZE_NUMBER']) && strtoupper($_POST['CALLTO_NORMALIZE_NUMBER']) === 'N' ? 'N' : 'Y';

			if (
				$oldCalltoSettings['URL_TEMPLATE'] != $newCalltoSettings['URL_TEMPLATE']
				|| $oldCalltoSettings['CLICK_HANDLER'] != $newCalltoSettings['CLICK_HANDLER']
				|| $oldCalltoSettings['NORMALIZE_NUMBER'] != $newCalltoSettings['NORMALIZE_NUMBER']
			)
			{
				CCrmCallToUrl::SetCustomSettings($newCalltoSettings);
				$isCallSettingsChanged = true;
			}

			if (defined('BX_COMP_MANAGED_CACHE') && $isCallSettingsChanged)
			{
				$GLOBALS['CACHE_MANAGER']->ClearByTag('CRM_CALLTO_SETTINGS');
			}

			$entityAddressFormatID = isset($_POST['ENTITY_ADDRESS_FORMAT_ID'])
				? (int)$_POST['ENTITY_ADDRESS_FORMAT_ID'] : \Bitrix\Crm\Format\EntityAddressFormatter::Dflt;
			\Bitrix\Crm\Format\EntityAddressFormatter::setFormatID($entityAddressFormatID);

			$personFormatID = isset($_POST['PERSON_NAME_FORMAT_ID'])
				? (int)$_POST['PERSON_NAME_FORMAT_ID'] : \Bitrix\Crm\Format\PersonNameFormatter::Dflt;
			\Bitrix\Crm\Format\PersonNameFormatter::setFormatID($personFormatID);

			$dupControl->enabledFor(
				CCrmOwnerType::Lead,
				isset($_POST['ENABLE_LEAD_DUP_CONTROL']) && strtoupper($_POST['ENABLE_LEAD_DUP_CONTROL']) === 'Y'
			);
			$dupControl->enabledFor(
				CCrmOwnerType::Contact,
				isset($_POST['ENABLE_CONTACT_DUP_CONTROL']) && strtoupper($_POST['ENABLE_CONTACT_DUP_CONTROL']) === 'Y'
			);
			$dupControl->enabledFor(
				CCrmOwnerType::Company,
				isset($_POST['ENABLE_COMPANY_DUP_CONTROL']) && strtoupper($_POST['ENABLE_COMPANY_DUP_CONTROL']) === 'Y'
			);
			$dupControl->save();

			CCrmStatus::EnableDepricatedTypes(
				isset($_POST['ENABLE_DEPRECATED_STATUSES']) && strtoupper($_POST['ENABLE_DEPRECATED_STATUSES']) === 'Y'
			);

			\Bitrix\Crm\Settings\DealSettings::enableCloseDateSync(
				isset($_POST['REFRESH_DEAL_CLOSEDATE']) && strtoupper($_POST['REFRESH_DEAL_CLOSEDATE']) === 'Y'
			);

			if($_POST['DEAL_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\DealSettings::setDefaultListViewID($_POST['DEAL_DEFAULT_LIST_VIEW']);
			}

			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_SM_CONFIG'],	array()
				)
			);
		}
	}
}

$arResult['FORM_ID'] = 'CRM_SM_CONFIG';
$arResult['BACK_URL'] = $arParams['PATH_TO_SM_CONFIG'];

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_deal_config'][] = array(
	'id' => 'DEAL_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_DEAL_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\DealSettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\DealSettings::getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_deal_config'][] = array(
	'id' => 'REFRESH_DEAL_CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_REFRESH_DEAL_CLOSEDATE'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\DealSettings::isCloseDateSyncEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_CALLS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_CALLS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedCalls, true),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_MEETINGS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_MEETINGS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedMeetings, true),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS',
	'name' => GetMessage('CRM_FIELD_RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS'),
	'type' => 'checkbox',
	'value' => CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true),
	'required' => false
);

$arResult['FIELDS']['tab_outgoing_email'][] = array(
	'id' => 'SERVICE_CODE_ALLOCATION',
	'name' => GetMessage('CRM_FIELD_SERVICE_CODE_ALLOCATION'),
	'items' => CCrmEMailCodeAllocation::GetAllDescriptions(),
	'type' => 'list',
	'value' => CCrmEMailCodeAllocation::GetCurrent(),
	'required' => false
);


if(Bitrix\Crm\Integration\Bitrix24Email::isEnabled())
{
	if(Bitrix\Crm\Integration\Bitrix24Email::allowDisableSignature())
	{
		$arResult['FIELDS']['tab_outgoing_email'][] = array(
			'id' => 'ENABLE_B24_EMAIL_SIGNATURE',
			'name' => GetMessage('CRM_FIELD_ENABLE_B24_EMAIL_SIGNATURE'),
			'type' => 'checkbox',
			'value' => Bitrix\Crm\Integration\Bitrix24Email::isSignatureEnabled(),
			'required' => false
		);
	}
	else
	{
		$arResult['FIELDS']['tab_outgoing_email'][] = array(
			'id' => 'ENABLE_B24_EMAIL_SIGNATURE',
			'name' => GetMessage('CRM_FIELD_ENABLE_B24_EMAIL_SIGNATURE'),
			'type' => 'label',
			'value' =>  Bitrix\Crm\Integration\Bitrix24Email::getSignatureExplanation(),
			'required' => false
		);
	}
}

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'PERSON_NAME_FORMAT_ID',
	'name' => GetMessage('CRM_FIELD_PERSON_NAME_FORMAT'),
	'type' => 'list',
	'items' => \Bitrix\Crm\Format\PersonNameFormatter::getAllDescriptions(),
	'value' => \Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_FORMAT',
	'name' => GetMessage('CRM_FIELD_CALLTO_FORMAT'),
	'type' => 'list',
	'items' => CCrmCallToUrl::GetAllDescriptions(),
	'value' => CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix),
	'required' => false
);

$calltoSettings = CCrmCallToUrl::GetCustomSettings();
$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_URL_TEMPLATE',
	'name' => GetMessage('CRM_FIELD_CALLTO_URL_TEMPLATE'),
	'type' => 'text',
	'value' => isset($calltoSettings['URL_TEMPLATE']) ? $calltoSettings['URL_TEMPLATE'] : 'callto:[phone]',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_CLICK_HANDLER',
	'name' => GetMessage('CRM_FIELD_CALLTO_CLICK_HANDLER'),
	'type' => 'textarea',
	'value' => isset($calltoSettings['CLICK_HANDLER']) ? $calltoSettings['CLICK_HANDLER'] : '',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_NORMALIZE_NUMBER',
	'name' => GetMessage('CRM_FIELD_CALLTO_NORMALIZE_NUMBER'),
	'type' => 'checkbox',
	'value' => isset($calltoSettings['NORMALIZE_NUMBER']) ? $calltoSettings['NORMALIZE_NUMBER'] === 'Y' : true,
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'section_address_format',
	'name' => GetMessage('CRM_SECTION_ADDRESS_FORMAT'),
	'type' => 'section'
);

$curAddrFormatID = \Bitrix\Crm\Format\EntityAddressFormatter::getFormatID();
$addrFormatDescrs = \Bitrix\Crm\Format\EntityAddressFormatter::getAllDescriptions();
$arResult['ADDR_FORMAT_INFOS'] = \Bitrix\Crm\Format\EntityAddressFormatter::getAllExamples();
$arResult['ADDR_FORMAT_CONTROL_PREFIX'] = 'addr_format_';

$addrFormatControls = array();
foreach($addrFormatDescrs as $addrFormatID => $addrFormatDescr)
{
	$isChecked = $addrFormatID === $curAddrFormatID;
	$addrFormatControlID = $arResult['ADDR_FORMAT_CONTROL_PREFIX'].$addrFormatID;
	$addrFormatControls[] = '<input type="radio" class="crm-dup-control-type-radio" id="'.$addrFormatControlID.'" name="ENTITY_ADDRESS_FORMAT_ID" value="'.$addrFormatID.'"'.($isChecked ? ' checked="checked"' : '').'/><label class="crm-dup-control-type-label" for="'.$addrFormatControlID.'">'.htmlspecialcharsbx($addrFormatDescr).'</label>';
}
$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'ENTITY_ADDRESS_FORMAT',
	'type' => 'custom',
	'value' =>
		'<div class="crm-dup-control-type-radio-title">'.GetMessage('CRM_FIELD_ENTITY_ADDRESS_FORMAT').':</div>'.
		'<div class="crm-dup-control-type-radio-wrap">'.
		implode('', $addrFormatControls).
		'</div>',
	'colspan' => true
);

$arResult['ADDR_FORMAT_DESCR_ID'] = 'addr_format_descr';
$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'ENTITY_ADDRESS_FORMAT_DESCR',
	'type' => 'custom',
	'value' => '<div class="crm-dup-control-type-info" id="'.$arResult['ADDR_FORMAT_DESCR_ID'].'">'.$arResult['ADDR_FORMAT_INFOS'][$curAddrFormatID].'</div>',
	'colspan' => true
);

ob_start();

$APPLICATION->IncludeComponent(
	'bitrix:crm.config.invoice.number',
	'',
	array(),
	''
);

$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_inv_nums'][] = array(
	'id' => 'INVOICE_NUMBERS_FORMAT',
	'name' => GetMessage('CRM_INVOICE_NUMBERS_FORMAT'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal,
	'required' => false
);

ob_start();
$APPLICATION->IncludeComponent(
	'bitrix:crm.config.number',
	'',
	array('ENTITY_NAME' => CCrmOwnerType::QuoteName)
);
$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_quote_nums'][] = array(
	'id' => 'QUOTE_NUMBERS_FORMAT',
	'name' => GetMessage('CRM_QUOTE_NUMBERS_FORMAT'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal,
	'required' => false
);

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_LEAD_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_LEAD_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Lead),
	'required' => false
);

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_CONTACT_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_CONTACT_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Contact),
	'required' => false
);

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_COMPANY_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_COMPANY_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Company),
	'required' => false
);

$arResult['FIELDS']['tab_status_config'][] = array(
	'id' => 'ENABLE_DEPRECATED_STATUSES',
	'name' => GetMessage('CRM_FIELD_ENABLE_DEPRECATED_STATUSES'),
	'type' => 'checkbox',
	'value' => CCrmStatus::IsDepricatedTypesEnabled(),
	'required' => false
);

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_SM_LIST'), $arParams['PATH_TO_SM_CONFIG']);
?>