<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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
	$jsCoreInit[] = 'file_dialog';
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

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	($elementID > 0) ? 'CRM_QUOTE_SHOW_TITLE' : 'CRM_QUOTE_SHOW_NEW_TITLE',
	array(
		'#QUOTE_NUMBER#' => !empty($arResult['ELEMENT']['QUOTE_NUMBER']) ? $arResult['ELEMENT']['QUOTE_NUMBER'] : '-',
		'#BEGINDATE#' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : '-'
	)
);

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_product_rows'):
		$productFieldset['NAME'] = $field['name'];
		unset($arTabs[0]['fields'][$k]);
	endif;
	if($field['id'] === 'PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;
endforeach;
unset($field);

$formCustomHtml = '<input type="hidden" name="quote_id" value="'.$elementID.'"/>'.$arResult['FORM_CUSTOM_HTML'];
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'FIELD_SETS' => array($productFieldset),
		'USER_FIELD_ENTITY_ID' => CCrmQuote::$sUFEntityID,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'IS_NEW' => $elementID <= 0,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);

$prefixLower = strtolower($arResult['PREFIX']);
$companySpecifiedClientFields = array('CLIENT_CONTACT', 'CLIENT_TP_ID');
if (LANGUAGE_ID === 'ru')
	$companySpecifiedClientFields[] = 'CLIENT_TPA_ID';
$editorSettings = array(
	'formId' => $arResult['FORM_ID'],
	'productRowEditorId' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'url' => '/bitrix/components/bitrix/crm.quote.edit/ajax.php?'.bitrix_sessid_get(),
	'personType' => $arResult['PERSON_TYPE'],
	'contactId' => intval($arResult['ELEMENT']['CONTACT_ID']),
	'companyId' => intval($arResult['ELEMENT']['COMPANY_ID']),
	'personTypeIds' => $arResult['PERSON_TYPE_IDS'],
	'companySpecifiedClientFields' => $companySpecifiedClientFields,
	'languageId' => LANGUAGE_ID,
	'filesFieldSettings' => array(
		'containerId' => $arResult['FILES_FIELD_CONTAINER_ID'],
		'controlMode' => 'edit',
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
			'diskAttachFiles' => GetMessage('CRM_QUOTE_DISK_ATTACH_FILE'),
			'diskAttachedFiles' => GetMessage('CRM_QUOTE_DISK_ATTACHED_FILES'),
			'diskSelectFile' => GetMessage('CRM_QUOTE_DISK_SELECT_FILE'),
			'diskSelectFileLegend' => GetMessage('CRM_QUOTE_DISK_SELECT_FILE_LEGEND'),
			'diskUploadFile' => GetMessage('CRM_QUOTE_DISK_UPLOAD_FILE'),
			'diskUploadFileLegend' => GetMessage('CRM_QUOTE_DISK_UPLOAD_FILE_LEGEND')
		)
	)
);

CCrmQuote::PrepareStorageElementInfo($arResult['ELEMENT']);
if(isset($arResult['ELEMENT']['WEBDAV_ELEMENTS']))
{
	$editorSettings['filesFieldSettings']['webdavelements'] = $arResult['ELEMENT']['WEBDAV_ELEMENTS'];
}
elseif(isset($arResult['ELEMENT']['DISK_FILES']))
{
	$editorSettings['filesFieldSettings']['diskfiles'] = $arResult['ELEMENT']['DISK_FILES'];
}

?><script type="text/javascript">

	window.CrmProductRowSetLocation = function(){
		BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']);
	}

	BX.ready(function(){
		BX.CrmQuoteEditor.create(
			"<?=strtolower($arResult['FORM_ID'])?>",
			<?=CUtil::PhpToJSObject($editorSettings)?>
		);
	});
</script>