<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!empty($arResult['ERROR_MESSAGE']))
{
	ShowError($arResult['ERROR_MESSAGE']);
}

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$jsCoreInit = array('date', 'popup', 'ajax');
if($arResult['ENABLE_DISK'])
{
	$jsCoreInit[] = 'uploader';
}
CJSCore::Init($jsCoreInit);

if($arResult['ENABLE_DISK'])
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/disk_uploader.js');
	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/disk.uf.file/templates/.default/style.css');
}
if($arResult['ENABLE_WEBDAV'])
{
	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
	$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/webdav.user.field/templates/.default/style.css');
	$APPLICATION->SetAdditionalCSS('/bitrix/js/webdav/css/file_dialog.css');

	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/core/core_dd.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/file_upload_agent.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/webdav/file_dialog.js');
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/webdav_uploader.js');
}

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_QUOTE_SHOW_TITLE',
	array(
		'#QUOTE_NUMBER#' => !empty($arResult['ELEMENT']['QUOTE_NUMBER']) ? $arResult['ELEMENT']['QUOTE_NUMBER'] : '-',
		'#BEGINDATE#' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : '-'
	)
);

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
/*---bizproc---$bizprocDispatcherID = strtolower($arResult['FORM_ID']).'_bp_disp';*/

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_QUOTE_SHOW_TAB_1'),
	'title' => GetMessage('CRM_QUOTE_SHOW_TAB_1_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_1'],
	'display' => false
);
$arTabs[] = array(
	'id' => 'tab_details',
	'name' => GetMessage('CRM_TAB_DETAILS'),
	'title' => GetMessage('CRM_TAB_DETAILS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_details'],
	'display' => false
);

$arTabs[] = array(
	'id' => $arResult['PRODUCT_ROW_TAB_ID'],
	'name' => GetMessage('CRM_TAB_PRODUCT_ROWS'),
	'title' => GetMessage('CRM_TAB_PRODUCT_ROWS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']]
);
if (!empty($arResult['FIELDS']['tab_invoice']))
{
	//$invoiceCount = intval($arResult['INVOICE_COUNT']);
	$arTabs[] = array(
		'id' => 'tab_invoice',
		//'name' => GetMessage('CRM_TAB_8')." ($invoiceCount)",
		'name' => GetMessage('CRM_TAB_8'),
		'title' => GetMessage('CRM_TAB_8_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_invoice']
	);
}
if(!empty($arResult['FIELDS']['tab_event']))
{
	//$eventCount = intval($arResult[EVENT_COUNT]);
	$arTabs[] = array(
		'id' => 'tab_event',
		//'name' => GetMessage('CRM_TAB_HISTORY')." ($eventCount)",
		'name' => GetMessage('CRM_TAB_HISTORY'),
		'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_event']
	);
}

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TACTILE_FORM_ID' => 'CRM_QUOTE_EDIT_V12',
		'QUICK_PANEL' => array(
			'ENTITY_TYPE_NAME' => CCrmOwnerType::QuoteName,
			'ENTITY_ID' => $arResult['ELEMENT_ID'],
			'ENTITY_FIELDS' => $element,
			'ENABLE_INSTANT_EDIT' => $arResult['ENABLE_INSTANT_EDIT'],
			'INSTANT_EDITOR_ID' => $instantEditorID,
			'SERVICE_URL' => SITE_DIR.'bitrix/components/bitrix/crm.quote.show/ajax.php?'.bitrix_sessid_get()
		),
		'TABS' => $arTabs,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');

$arResult['PREFIX'] = isset($arResult['PREFIX']) ? strval($arResult['PREFIX']) : 'crm_quote_show';
$activityEditorID = $arResult['PREFIX'].'_send_email';
$activityEditorSettings = array(
	'CONTAINER_ID' => '',
	'EDITOR_ID' => $activityEditorID,
	'PREFIX' => $arResult['PREFIX'],
	'ENABLE_UI' => false,
	'ENABLE_TOOLBAR' => false,
	'ENABLE_EMAIL_ADD' => true
);

$leadID = isset($arResult['ELEMENT']['LEAD_ID']) ? intval($arResult['ELEMENT']['LEAD_ID']) : 0;
if($leadID > 0)
{
	$activityEditorSettings['OWNER_TYPE'] = CCrmOwnerType::LeadName;
	$activityEditorSettings['OWNER_ID'] = $leadID;
}

$dealID = isset($arResult['ELEMENT']['DEAL_ID']) ? intval($arResult['ELEMENT']['DEAL_ID']) : 0;
if($dealID > 0)
{
	$activityEditorSettings['OWNER_TYPE'] = CCrmOwnerType::DealName;
	$activityEditorSettings['OWNER_ID'] = $dealID;
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	$activityEditorSettings,
	$component,
	array('HIDE_ICONS' => 'Y')
);


$prefixLower = strtolower($arResult['PREFIX']);
$scriptSettings = array(
	'formId' => $arResult['FORM_ID'],
	// instant editor settings ->
	'enableInstantEdit' => (bool)$arResult['ENABLE_INSTANT_EDIT'],
	'instantEditorId' => $instantEditorID,
	'summaryContainerId' => $summaryContainerID,
	'productRowsTabId' => $arResult['PRODUCT_ROW_TAB_ID'],
	'ownerType' => CCrmQuote::OWNER_TYPE,
	'ownerId' => $arResult['ELEMENT_ID'],
	'url' => '/bitrix/components/bitrix/crm.quote.show/ajax.php?'.bitrix_sessid_get(),
	'callToFormat' => CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix),
	'messages' => array(
		'editButtonTitle' => GetMessage('CRM_EDIT_BTN_TTL'),
		'lockButtonTitle' => GetMessage('CRM_LOCK_BTN_TTL')
	),
	// <- instant editor settings
	'filesFieldSettings' => array(
		'containerId' => $arResult['FILES_FIELD_CONTAINER_ID'],
		'controlMode' => 'view',
		'webDavSelectUrl' => $arResult['WEBDAV_SELECT_URL'],
		'webDavUploadUrl' => $arResult['WEBDAV_UPLOAD_URL'],
		'webDavShowUrl' => $arResult['WEBDAV_SHOW_URL'],
		'files' => $arResult['ELEMENT']['STORAGE_ELEMENT_IDS'],
		'uploadContainerID' => $prefixLower.'_upload_container',
		'uploadControlID' => $prefixLower.'_uploader',
		'uploadInputID' => $prefixLower.'_saved_file',
		'storageTypeId' => $arResult['ELEMENT']['STORAGE_TYPE_ID'],
		'defaultStorageTypeId' => CCrmQuote::GetDefaultStorageTypeID(),
		'serviceUrl' => '/bitrix/components/bitrix/crm.quote.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'messages' => array(
			'webdavFileLoading' => GetMessage('CRM_QUOTE_WEBDAV_FILE_LOADING'),
			'webdavFileAlreadyExists' => GetMessage('CRM_QUOTE_WEBDAV_FILE_ALREADY_EXISTS'),
			'webdavFileAccessDenied' => GetMessage('CRM_QUOTE_WEBDAV_FILE_ACCESS_DENIED'),
			'webdavAttachFile' => GetMessage('CRM_QUOTE_WEBDAV_ATTACH_FILE'),
			'webdavTitle' => GetMessage('CRM_QUOTE_WEBDAV_TITLE'),
			'webdavDragFile' => GetMessage('CRM_QUOTE_WEBDAV_DRAG_FILE'),
			'webdavSelectFile' => GetMessage('CRM_QUOTE_WEBDAV_SELECT_FILE'),
			'webdavSelectFromLib' => GetMessage('CRM_QUOTE_WEBDAV_SELECT_FROM_LIB'),
			'webdavLoadFiles' => GetMessage('CRM_QUOTE_WEBDAV_LOAD_FILES'),
			'diskAttachedFiles' => GetMessage('CRM_QUOTE_DISK_ATTACHED_FILES')
		)
	)
);
CCrmQuote::PrepareStorageElementInfo($arResult['ELEMENT']);
if(isset($arResult['ELEMENT']['WEBDAV_ELEMENTS']))
{
	$scriptSettings['filesFieldSettings']['webdavelements'] = $arResult['ELEMENT']['WEBDAV_ELEMENTS'];
}
elseif(isset($arResult['ELEMENT']['DISK_FILES']))
{
	$scriptSettings['filesFieldSettings']['diskfiles'] = $arResult['ELEMENT']['DISK_FILES'];
}
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmQuoteShowInitScript(<?= CUtil::PhpToJSObject($scriptSettings) ?>);

			BX.CrmQuotePrintDialog.messages =
			{
				title: "<?=CUtil::JSEscape(GetMessage("CRM_QUOTE_PRINT_DLG_TTL"))?>",
				printButton: "<?=CUtil::JSEscape(GetMessage("CRM_QUOTE_PRINT_BTN_TTL"))?>",
				cancelButton: "<?=CUtil::JSEscape(GetMessage("CRM_QUOTE_CANCEL_BTN_TTL"))?>",
				templateField: "<?=CUtil::JSEscape(GetMessage("CRM_QUOTE_PRINT_TEMPLATE_FIELD"))?>"
			};

			BX.CrmQuoteViewForm.messages =
			{
				noPrintTemplatesError: "<?=GetMessage("CRM_QUOTE_NO_PRINT_TEMPLATES_ERROR")?>",
				noPrintUrlError: "<?=GetMessage("CRM_QUOTE_NO_PRINT_URL_ERROR")?>"
			};

			BX.CrmQuoteViewForm.create("<?=CUtil::JSEscape($arResult['FORM_ID'])?>",
				{
					entityId: "<?=CUtil::JSEscape($arResult['ELEMENT_ID'])?>",
					activityEditorId: "<?=CUtil::JSEscape($activityEditorID)?>",
					printTemplates: <?=CUtil::PhpToJSObject($arResult['PRINT_TEMPLATES'])?>,
					printUrl: "<?=CUtil::JSEscape($arResult['PRINT_URL'])?>",
					downloadPdfUrl: "<?=CUtil::JSEscape($arResult['DOWNLOAD_PDF_URL'])?>",
					createPdfFileUrl: "<?=CUtil::JSEscape($arResult['CREATE_PDF_FILE_URL'])?>",
					emailTitle: "<?=CUtil::JSEscape($arResult['EMAIL_TITLE'])?>",
					emailCommunications: <?=CUtil::PhpToJSObject($arResult['EMAIL_COMMUNICATIONS'])?>
				}
			);
		}
	);
</script>
