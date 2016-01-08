<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_LANG_FILES', true);
define('DisableEventsCheck', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('BX_PUBLIC_TOOLS', true);
define('PUBLIC_AJAX_MODE', true);

use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = $_REQUEST['site_id'];
	//Prevent LFI in prolog_before.php
	if($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('LANGUAGE_ID') )
{
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite ? $dbSite->Fetch() : null;
	define('LANGUAGE_ID', $arSite ? $arSite['LANGUAGE_ID'] : 'en');
}

//session_write_close();

if (!CModule::IncludeModule('crm'))
{
	die();
}

if (CModule::IncludeModule('compression'))
{
	CCompress::Disable2048Spaces();
}

global $APPLICATION, $DB;
$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

//$langID = isset($_REQUEST['lang_id'])? $_REQUEST['lang_id']: LANGUAGE_ID;
//__IncludeLang(dirname(__FILE__).'/lang/'.$langID.'/'.basename(__FILE__));

CUtil::JSPostUnescape();
$now = time() + CTimeZone::GetOffset();

if(!function_exists('__CrmMobileInvoiceEditEndResonse'))
{
	function __CrmMobileInvoiceEditEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$curUserPrems = CCrmPerms::GetCurrentUserPermissions();
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === 'SAVE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::InvoiceName)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$hasPermission = $ID > 0
		? CCrmAuthorizationHelper::CheckUpdatePermission(CCrmOwnerType::InvoiceName, $ID, $curUserPrems)
		: CCrmAuthorizationHelper::CheckCreatePermission(CCrmOwnerType::InvoiceName, $curUserPrems);

	if(!$hasPermission)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$topic = isset($data['ORDER_TOPIC']) ? trim($data['ORDER_TOPIC']) : '';
	if($topic === '')
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_TOPIC_IS_NOT_ASSIGNED')));
	}

	$companyID = max((isset($data['COMPANY_ID']) ? intval($data['COMPANY_ID']) : 0), 0);
	if($companyID > 0 && !CCrmCompany::CheckReadPermission($companyID, $curUserPrems))
	{
		$companyID = 0;
	}

	$contactID = max((isset($data['CONTACT_ID']) ? intval($data['CONTACT_ID']) : 0), 0);
	if($contactID > 0 && !CCrmContact::CheckReadPermission($contactID, $curUserPrems))
	{
		$contactID = 0;
	}

	if($companyID === 0 && $contactID === 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_CLIENT_IS_NOT_ASSIGNED')));
	}

	$dealID = max(isset($data['DEAL_ID']) ? intval($data['DEAL_ID']) : 0, 0);
	if($dealID > 0 && !CCrmDeal::CheckReadPermission($dealID, $curUserPrems))
	{
		$dealID = 0;
	}

	$personTypeID = isset($data['PERSON_TYPE_ID']) ? intval($data['PERSON_TYPE_ID']) : 0;
	$resolvedPersonTypeID = ($companyID > 0 || $contactID > 0) ? CCrmInvoice::ResolvePersonTypeID($companyID, $contactID) : 0;

	$paySystemID = isset($data['PAY_SYSTEM_ID']) ? intval($data['PAY_SYSTEM_ID']) : 0;
	$paySystems = CCrmPaySystem::GetPaySystemsListItems($resolvedPersonTypeID);
	$resolvedPaySystemID = 0;
	if($personTypeID === $resolvedPersonTypeID && $paySystemID > 0 && array_key_exists($paySystemID, $paySystems))
	{
		// Pay system does not changed
		$resolvedPaySystemID = $paySystemID;
	}
	elseif(!empty($paySystems))
	{
		// Take first pay system
		$resolvedPaySystemID = array_shift(array_keys($paySystems));
	}

	// CURRENCY_ID -->
	$currencyID = isset($data['CURRENCY_ID']) ? $data['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmInvoice::GetCurrencyID();
	}
	//<-- CURRENCY_ID

	// STATUS_ID -->
	$statusID = isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '';
	if($statusID === '')
	{
		$statusList = CCrmStatus::GetStatus('INVOICE_STATUS');
		if(!empty($statusList))
		{
			$statusID = array_shift(array_keys($statusList));
		}
	}

	if($statusID === '')
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_STATUS_IS_NOT_ASSIGNED')));
	}
	//<-- STATUS_ID

	// PRODUCT_ROWS -->
	$productRows = isset($data['PRODUCT_ROWS']) && is_array($data['PRODUCT_ROWS']) ? $data['PRODUCT_ROWS'] : array();
	if(!empty($productRows))
	{
		$productIDs = array();
		foreach($productRows as $productRowKey => &$productRow)
		{
			$productID = intval($productRow['PRODUCT_ID']);
			if($productID > 0)
			{
				$productIDs[] = $productID;
			}
			else
			{
				unset($productRow[$productRowKey]);
			}
		}
		unset($productRow);

		if(!empty($productIDs))
		{
			$products = array();
			$dbResult = CCrmProduct::GetList(array(), array('@ID' => $productIDs), array('ID','NAME'));
			if(is_object($dbResult))
			{
				while($product = $dbResult->Fetch())
				{
					$products[intval($product['ID'])] = $product['NAME'];
				}
			}

			foreach($productRows as $productRowKey => &$productRow)
			{
				$productID = intval($productRow['PRODUCT_ID']);
				if($productID > 0 && isset($products[$productID]) && $products[$productID] !== '')
				{
					$productRow['PRODUCT_NAME'] = $products[$productID];
					$productRow['CUSTOMIZED'] = 'Y';
				}
				else
				{
					unset($productRow[$productRowKey]);
				}
			}
			unset($productRow);
		}
	}

	if(empty($productRows))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_PRODUCT_ROWS_ARE_EMPTY')));
	}
	//<-- PRODUCT_ROWS

	// COMMENTS & USER_DESCRIPTION -->
	$sanitizer = new CBXSanitizer();
	$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_HIGH);

	$comments = isset($data['COMMENTS']) ? trim($data['COMMENTS']) : '';
	if($comments !== '')
	{
		$comments = $sanitizer->SanitizeHtml($comments);
	}

	$userDescription = isset($data['USER_DESCRIPTION']) ? trim($data['USER_DESCRIPTION']) : '';
	if($userDescription !== '')
	{
		$userDescription = $sanitizer->SanitizeHtml($userDescription);
	}
	//<-- COMMENTS & USER_DESCRIPTION

	$responsibleID = max((isset($data['RESPONSIBLE_ID']) ? intval($data['RESPONSIBLE_ID']) : 0), 0);
	if($responsibleID == 0)
	{
		$responsibleID = CCrmSecurityHelper::GetCurrentUserID();
	}

	$arFields = array(
		'ID' => $ID,
		'ORDER_TOPIC' => $topic,
		'STATUS_ID' => $statusID,
		'CURRENCY' => $currencyID,
		'PAY_SYSTEM_ID' => $resolvedPaySystemID,
		'PERSON_TYPE_ID' => $resolvedPersonTypeID,
		'RESPONSIBLE_ID' => $responsibleID,
		'UF_DEAL_ID' => $dealID,
		'UF_COMPANY_ID' => $companyID,
		'UF_CONTACT_ID' => $contactID,
		'COMMENTS' => $comments,
		'USER_DESCRIPTION' => $userDescription,
		'PRODUCT_ROWS' => $productRows
	);

	// DATE_INSERT & DATE_BILL & DATE_PAY_BEFORE -->
	$arFields['DATE_INSERT'] = ConvertTimeStamp($now, 'SHORT', SITE_ID);

	$ts = isset($data['DATE_BILL']) ? intval($data['DATE_BILL']) : 0;
	$arFields['DATE_BILL'] = ConvertTimeStamp(($ts > 0 ? $ts : $now), 'SHORT', SITE_ID);

	$ts = isset($data['DATE_PAY_BEFORE']) ? intval($data['DATE_PAY_BEFORE']) : 0;
	if($ts > 0)
	{
		$arFields['DATE_PAY_BEFORE'] = ConvertTimeStamp($ts, 'SHORT', SITE_ID);
	}
	//<-- DATE_INSERT & DATE_BILL & DATE_PAY_BEFORE

	$taxMode = CCrmTax::isVatMode() ? 'VAT' : (CCrmTax::isTaxMode() ? 'EXT' : '');
	if ($taxMode === 'EXT')
	{
		$arFields['PR_LOCATION'] = isset($data['LOCATION_ID']) ? intval($data['LOCATION_ID']) : 0;
	}

	$isSuccessfull = CCrmStatusInvoice::isStatusSuccess($statusID);
	$isFailed = !$isSuccessfull && CCrmStatusInvoice::isStatusFailed($statusID);

	if($isSuccessfull)
	{
		$timestamp = isset($data['PAYMENT_TIME_STAMP']) ? max(intval($data['PAYMENT_TIME_STAMP']), 0) : 0;
		$arFields['PAY_VOUCHER_DATE'] = $timestamp > 0 ? ConvertTimeStamp($timestamp, 'SHORT', SITE_ID) : null;

		$arFields['PAY_VOUCHER_NUM'] = isset($data['PAYMENT_DOC']) ? substr(trim($data['PAYMENT_DOC']), 0, 20) : '';
		$arFields['REASON_MARKED'] = isset($data['PAYMENT_COMMENT']) ? substr(trim($data['PAYMENT_COMMENT']), 0, 255) : '';
	}
	elseif($isFailed)
	{
		$timestamp = isset($data['CANCEL_TIME_STAMP']) ? max(intval($data['CANCEL_TIME_STAMP']), 0) : 0;
		$arFields['DATE_MARKED'] = $timestamp > 0 ? ConvertTimeStamp($timestamp, 'SHORT', SITE_ID) : null;

		$arFields['REASON_MARKED'] = isset($data['CANCEL_REASON']) ? substr(trim($data['CANCEL_REASON']), 0, 255) : '';
	}

	// INVOICE_PROPERTIES -->
	$properties = CCrmInvoice::GetProperties($ID, $resolvedPersonTypeID);
	if(!is_array($properties))
	{
		$properties = array();
	}

	//HACK: see CCrmInvoice::ParsePropertiesValuesFromPost
	if(isset($data['LOCATION_ID']))
	{
		$data['LOC_CITY'] = $data['LOCATION_ID'];
	}

	$propertyValues = CCrmInvoice::ParsePropertiesValuesFromPost($resolvedPersonTypeID, $data, $properties);

	if(isset($propertyValues['PROPS_VALUES']) && isset($propertyValues['PROPS_INDEXES']))
	{
		$arFields['INVOICE_PROPERTIES'] = $propertyValues['PROPS_VALUES'];
		/*foreach ($propertyValues['PROPS_INDEXES'] as $name => $index)
		{
			if (!isset($arFields[$name]))
			{
				$arFields[$name] = $propertyValues['PROPS_VALUES'][$index];
			}
		}*/
	}
	//<-- INVOICE_PROPERTIES

	$entity = new CCrmInvoice(false);
	if (!$entity->CheckFields($arFields, !$isNew ? $ID : false, $isSuccessfull, $isFailed))
	{
		if($entity->LAST_ERROR !== '')
		{
			$errorText = preg_replace('/<br\s*\/>/', "\n", $entity->LAST_ERROR);
			__CrmMobileInvoiceEditEndResonse(array('ERROR' => $errorText));
		}
		else
		{
			__CrmMobileInvoiceEditEndResonse(
				array('ERROR' => GetMessage('CRM_INVOICE_FIELD_CHECK_GENERAL_ERROR'))
			);
		}
	}

	//$DB->StartTransaction();

	$successed = false;
	if (!$isNew)
	{
		$successed = $entity->Update($ID, $arFields, array('UPDATE_SEARCH' => true));
	}
	else
	{
		$recalculate = false;
		$ID = $entity->Add($arFields, $recalculate, SITE_ID, array('UPDATE_SEARCH' => true));
		$successed = is_int($ID) && $ID > 0;
	}

	if($successed)
	{
		//$DB->Commit();

		$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

		CCrmMobileHelper::PrepareInvoiceItem(
			$currentItem,
			$formatParams,
			array('PAY_SYSTEMS' => $paySystems),
			array('ENABLE_MULTI_FIELDS' => true, 'ENABLE_PAYER_INFO' => true, 'ENABLE_LOCATION' => true)
		);

		__CrmMobileInvoiceEditEndResonse(
			array(
				'SAVED_ENTITY_ID' => $ID,
				'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareInvoiceData($currentItem)
			)
		);
	}
	else
	{
		//$DB->Rollback();

		$errorText = '';
		$exception = $APPLICATION->GetException();
		if ($exception)
		{
			$code = $exception->GetID();
			if($code !== '')
			{
				$errorText = GetMessage("CRM_INVOICE_{$code}");
			}
			if($errorText === '')
			{
				$errorText = preg_replace('/<br\s*\/>/', "\n", $exception->GetString());
			}

			$APPLICATION->ResetException();
		}

		__CrmMobileInvoiceEditEndResonse(
			array(
				'ERROR' => $errorText !== ''
					? $errorText
					: GetMessage('CRM_INVOICE_SAVING_GENERAL_ERROR')
			)
		);
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::InvoiceName)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::InvoiceName, $ID, $curUserPrems))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$entity = new CCrmInvoice(false);
	//$DB->StartTransaction();
	$successed = $entity->Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileInvoiceEditEndResonse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::DealName)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::InvoiceName, $ID, $curUserPrems))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
	$item = $dbRes ? $dbRes->GetNext() : null;
	if(!is_array($item))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_NOT_FOUND', array('#ID#' => $ID))));
	}
	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

	$resolvedPersonTypeID = ($companyID > 0 || $contactID > 0) ? CCrmInvoice::ResolvePersonTypeID($companyID, $contactID) : 0;
	$paySystems = CCrmPaySystem::GetPaySystemsListItems($resolvedPersonTypeID);

	CCrmMobileHelper::PrepareInvoiceItem(
		$item,
		$formatParams,
		array(),
		array('ENABLE_MULTI_FIELDS' => true, 'ENABLE_PAYER_INFO' => true, 'ENABLE_LOCATION' => true)
	);

	__CrmMobileInvoiceEditEndResonse(
		array(
			'SAVED_ENTITY_ID' => $ID,
			'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareInvoiceData($item)
		)
	);
}
elseif($action === 'SET_STATUS')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::InvoiceName)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmAuthorizationHelper::CheckUpdatePermission(CCrmOwnerType::InvoiceName, $ID, $curUserPrems))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$statusID = isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '';
	if($statusID === '')
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_STATUS_NOT_FOUND')));
	}

	$additionalData = isset($data['ADDITIONAL_DATA']) && is_array($data['ADDITIONAL_DATA'])
		? $data['ADDITIONAL_DATA'] : null;

	$isSuccess = CCrmStatusInvoice::isStatusSuccess($statusID);
	$isFail = CCrmStatusInvoice::isStatusFailed($statusID);

	$statusParams = array(
		'STATE_SUCCESS' => $isSuccess,
		'STATE_FAILED' => $isFail
	);

	if($isSuccess)
	{
		$timestamp = isset($additionalData['PAYMENT_TIME_STAMP']) ? max(intval($additionalData['PAYMENT_TIME_STAMP']), 0) : 0;
		$statusParams['PAY_VOUCHER_DATE'] = $timestamp > 0 ? ConvertTimeStamp($timestamp, 'SHORT', SITE_ID) : null;

		$statusParams['PAY_VOUCHER_NUM'] = isset($additionalData['PAYMENT_DOC']) ? substr(trim($additionalData['PAYMENT_DOC']), 0, 20) : '';
		$statusParams['REASON_MARKED'] = isset($additionalData['PAYMENT_COMMENT']) ? substr(trim($additionalData['PAYMENT_COMMENT']), 0, 255) : '';
	}
	elseif($isFail)
	{
		$timestamp = isset($additionalData['CANCEL_TIME_STAMP']) ? max(intval($additionalData['CANCEL_TIME_STAMP']), 0) : 0;
		$statusParams['DATE_MARKED'] = $timestamp > 0 ? ConvertTimeStamp($timestamp, 'SHORT', SITE_ID) : null;
		$statusParams['REASON_MARKED'] = isset($additionalData['CANCEL_REASON']) ? substr(trim($additionalData['CANCEL_REASON']), 0, 255) : '';
	}

	//$DB->StartTransaction();

	$CCrmInvoice = new CCrmInvoice(false);
	$successed = $CCrmInvoice->SetStatus($ID, $statusID, $statusParams);
	if($successed)
	{
		//$DB->Commit();

		$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID));
		$currentItem = $dbRes->GetNext();

		$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
		CCrmMobileHelper::PrepareInvoiceItem(
			$currentItem,
			$formatParams,
			array(),
			array('ENABLE_MULTI_FIELDS' => true, 'ENABLE_PAYER_INFO' => true)
		);

		__CrmMobileInvoiceEditEndResonse(
			array(
				'SAVED_ENTITY_ID' => $ID,
				'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareInvoiceData($currentItem)
			)
		);
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_SAVE_STATUS')));
	}
}
elseif($action === 'RECALCULATE')
{
	// Refresh invoice data according to client settings
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$data = isset($_REQUEST['SOURCE_DATA']) && is_array($_REQUEST['SOURCE_DATA']) ? $_REQUEST['SOURCE_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$enablePayerInfo = isset($data['ENABLE_PAYER_INFO']) ? (strtoupper($data['ENABLE_PAYER_INFO']) === "Y") : false;
	$enableProductRows = isset($data['ENABLE_PRODUCT_ROWS']) ? (strtoupper($data['ENABLE_PRODUCT_ROWS']) === "Y") : false;
	$productRows = $enableProductRows && isset($data['PRODUCT_ROWS']) && is_array($data['PRODUCT_ROWS']) ? $data['PRODUCT_ROWS'] : array();
	$productRowQty = count($productRows);
	//Save initial row's order
	for($i = 0; $i < $productRowQty; $i++)
	{
		$productRows[$i]['IDX'] = $i;
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;

	$companyID = isset($data['COMPANY_ID']) ? intval($data['COMPANY_ID']) : 0;
	if($companyID > 0 && !CCrmCompany::CheckReadPermission($companyID, $curUserPrems))
	{
		$companyID = 0;
	}

	$contactID = isset($data['CONTACT_ID']) ? intval($data['CONTACT_ID']) : 0;
	if($contactID > 0 && !CCrmContact::CheckReadPermission($contactID, $curUserPrems))
	{
		$contactID = 0;
	}

	$personTypeID = isset($data['PERSON_TYPE_ID']) ? intval($data['PERSON_TYPE_ID']) : 0;
	$resolvedPersonTypeID = ($companyID > 0 || $contactID > 0) ? CCrmInvoice::ResolvePersonTypeID($companyID, $contactID) : 0;

	$paySystemID = isset($data['PAY_SYSTEM_ID']) ? intval($data['PAY_SYSTEM_ID']) : 0;
	$paySystems = CCrmPaySystem::GetPaySystemsListItems($resolvedPersonTypeID);
	$resolvedPaySystemID = 0;
	if($personTypeID === $resolvedPersonTypeID && $paySystemID > 0 && array_key_exists($paySystemID, $paySystems))
	{
		// Pay system does not changed
		$resolvedPaySystemID = $paySystemID;
	}
	elseif(!empty($paySystems))
	{
		// Take first pay system
		$resolvedPaySystemID = array_shift(array_keys($paySystems));
	}

	$arFields = array(
		'ID' => $ID,
		'PRODUCT_ROWS' => $productRows,
		'PAY_SYSTEM_ID' => $resolvedPaySystemID,
		'PERSON_TYPE_ID' => $resolvedPersonTypeID,
	);

	$properties = CCrmInvoice::GetProperties($ID, $resolvedPersonTypeID);
	if(!is_array($properties))
	{
		$properties = array();
	}

	if(isset($data['LOCATION_ID']) && !isset($data['LOC_CITY']))
	{
		//HACK: see CCrmInvoice::ParsePropertiesValuesFromPost
		$data['LOC_CITY'] = $data['LOCATION_ID'];
	}

	$propertyValues = CCrmInvoice::ParsePropertiesValuesFromPost($resolvedPersonTypeID, $data, $properties);
	if(isset($propertyValues['PROPS_VALUES']) && isset($propertyValues['PROPS_INDEXES']))
	{
		$arFields['INVOICE_PROPERTIES'] = $propertyValues['PROPS_VALUES'];
		foreach ($propertyValues['PROPS_INDEXES'] as $name => $index)
		{
			if (!isset($arFields[$name]))
			{
				$arFields[$name] = $propertyValues['PROPS_VALUES'][$index];
			}
		}
	}

	$payerInfo = '';
	$payerRequisites = array();
	if ($enablePayerInfo)
	{
		CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $properties);
		$payerInfo = CCrmInvoice::__MakePayerInfoString($properties);
		if($resolvedPersonTypeID > 0)
		{
			$payerRequisites = CCrmMobileHelper::PrepareInvoiceClientRequisites($resolvedPersonTypeID, $properties);
		}
	}

	if(!$enableProductRows || empty($productRows))
	{
		$resultData = array(
			'PERSON_TYPE_ID' => $resolvedPersonTypeID,
			'PAY_SYSTEM_ID' => $resolvedPaySystemID,
			'PAY_SYSTEM_NAME' => $resolvedPaySystemID > 0 && isset($paySystems[$resolvedPaySystemID])
				? $paySystems[$resolvedPaySystemID] : ''
		);

		if($enablePayerInfo)
		{
			$resultData['PAYER_INFO'] = $payerInfo;
			$resultData['PAYER_INFO_FORMAT'] = $resolvedPersonTypeID > 0 ? CCrmMobileHelper::PrepareInvoiceClientInfoFormat($resolvedPersonTypeID) : '';
			$resultData['PAYER_REQUISITES'] = $payerRequisites;
		}
		__CrmMobileInvoiceEditEndResonse(array('RESULT_DATA' => $resultData));
	}

	$orderData = CCrmInvoice::QuickRecalculate($arFields);
	if(empty($orderData))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_RECALCULATE')));
	}

	$currencyID = isset($orderData['CURRENCY']) ? $orderData['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
	$sumBrutto = isset($orderData['PRICE']) ? $orderData['PRICE'] : 0.0;
	$taxSum = isset($orderData['TAX_VALUE']) ? $orderData['TAX_VALUE'] : 0.0;
	$sumNetto = $sumBrutto - $taxSum;

	$taxList = isset($orderData['TAX_LIST']) && is_array($orderData['TAX_LIST']) ? $orderData['TAX_LIST'] : array();
	$taxMode = isset($orderData['USE_VAT']) && $orderData['USE_VAT'] ? 'VAT' : (!empty($taxList) ? 'EXT' : '');

	$resultData = array(
		'PERSON_TYPE_ID' => $resolvedPersonTypeID,
		'PAY_SYSTEM_ID' => $resolvedPaySystemID,
		'PAY_SYSTEM_NAME' => $resolvedPaySystemID > 0 && isset($paySystems[$resolvedPaySystemID])
			? $paySystems[$resolvedPaySystemID] : '',
		'CURRENCY_ID' => $currencyID,
		'SUM_NETTO' => $sumNetto,
		'SUM_BRUTTO' => $sumBrutto,
		'TAX_SUM' => $taxSum,
		'FORMATTED_SUM_NETTO' => CCrmCurrency::MoneyToString($sumNetto, $currencyID),
		'FORMATTED_SUM_BRUTTO' => CCrmCurrency::MoneyToString($sumBrutto, $currencyID),
		'FORMATTED_TAX_SUM' => CCrmCurrency::MoneyToString($taxSum, $currencyID),
		'TAX_MODE' => $taxMode,
		'PRODUCT_ROWS' => array()
	);

	if($enablePayerInfo)
	{
		$resultData['PAYER_INFO'] = $payerInfo;
		$resultData['PAYER_INFO_FORMAT'] = $resolvedPersonTypeID > 0 ? CCrmMobileHelper::PrepareInvoiceClientInfoFormat($resolvedPersonTypeID) : '';
		$resultData['PAYER_REQUISITES'] = $payerRequisites;
	}

	$isVATMode = $taxMode === 'VAT';
	$VATName = $isVATMode && isset($taxList[0]) && isset($taxList[0]['NAME']) ? $taxList[0]['NAME'] : '';

	$cartItems = isset($orderData['BASKET_ITEMS']) && isset($orderData['BASKET_ITEMS']) ? $orderData['BASKET_ITEMS'] : array();
	// Recover initial row's order
	sortByColumn($cartItems, 'IDX');
	foreach($cartItems as &$cartItem)
	{
		$productID = isset($cartItem['PRODUCT_ID']) ? intval($cartItem['PRODUCT_ID']) : 0;
		if($productID <= 0)
		{
			continue;
		}

		$productName = isset($cartItem['NAME']) ? $cartItem['NAME'] : '';
		if($productName === '')
		{
			$dbProduct = CCrmProduct::GetList(array(), array('ID' => $productID), array('NAME'));
			$product = $dbProduct ? $dbProduct->Fetch() : null;
			$productName = is_array($product) && isset($product['NAME']) ? $product['NAME'] : '';
		}

		if($productName === '')
		{
			continue;
		}

		$price = isset($cartItem['PRICE']) ? $cartItem['PRICE'] : 0.0;
		$qty = isset($cartItem['QUANTITY']) ? $cartItem['QUANTITY'] : 0;
		$sum = $price * $qty;


		$row = array(
			'PRODUCT_ID' => $productID,
			'PRODUCT_NAME' => $productName,
			'CURRENCY_ID' => isset($cartItem['CURRENCY']) ? $cartItem['CURRENCY'] : '',
			'PRICE' => $price,
			'QUANTITY' => $qty,
			'SUM' => $sum,
			'FORMATTED_PRICE' => CCrmCurrency::MoneyToString($price, $currencyID),
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID)
		);

		if($isVATMode)
		{
			// Custom processing for VAT mode
			$rate = isset($cartItem['VAT_RATE']) ? round(doubleval($cartItem['VAT_RATE']) * 100, 2) : 0.0;
			if($rate > 0)
			{
				$row['TAX_INFOS'] = array(
					array('NAME' => $VATName, 'RATE'=> $rate, 'FORMATTED_RATE' => "{$rate}%")
				);
			}
		}

		$resultData['PRODUCT_ROWS'][] = &$row;
		unset($row);
	}
	unset($cartItem);

	$taxInfo = CCrmMobileHelper::PrepareInvoiceTaxInfo($taxList, false);
	$resultData['TAX_INFOS'] = $taxInfo['ITEMS'];
	__CrmMobileInvoiceEditEndResonse(array('RESULT_DATA' => $resultData));
}
elseif($action === 'PREPARE_PDF')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$data = isset($_REQUEST['SOURCE_DATA']) && is_array($_REQUEST['SOURCE_DATA']) ? $_REQUEST['SOURCE_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::InvoiceName, $ID, $curUserPrems))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	if (!CModule::IncludeModule('sale'))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_LOAD_SALE_MODULE')));
	}

	$dbOrder = CSaleOrder::GetList(
		array('DATE_UPDATE' => 'DESC'),
		array(
			'LID' => SITE_ID,
			'ID' => $ID
		)
	);
	$order = $dbOrder ? $dbOrder->Fetch() : null;
	if(!is_array($order))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_LOAD_SALE_ORDER')));
	}

	$order['SUM_PAID'] = isset($order['SUM_PAID']) ? doubleval($order['SUM_PAID']) : 0;
	$order['PRICE'] = (isset($order['PRICE']) ? doubleval($order['PRICE']) : 0) - $order['SUM_PAID'];

	$personTypeID = isset($order['PERSON_TYPE_ID']) ? intval($order['PERSON_TYPE_ID']) : 0;
	if($personTypeID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_FIND_SALE_ORDER_PERSON_TYPE')));
	}

	$paySystemID = isset($order['PAY_SYSTEM_ID']) ? intval($order['PAY_SYSTEM_ID']) : 0;
	if($paySystemID <= 0)
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_FIND_SALE_ORDER_PAY_SYSTEM')));
	}

	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			'PAY_SYSTEM_ID' => $paySystemID,
			'PERSON_TYPE_ID' => $personTypeID
		),
		false,
		false,
		array('ACTION_FILE', 'PARAMS', 'ENCODING')
	);

	$paySysAction = $dbPaySysAction ? $dbPaySysAction->Fetch() : null;
	$actionFile = is_array($paySysAction) && isset($paySysAction['ACTION_FILE']) ? $paySysAction['ACTION_FILE'] : '';
	if($actionFile === '')
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_FIND_PAY_SYSTEM_HANDLER')));
	}

	CSalePaySystemAction::InitParamArrays(
		$order,
		$ID,
		isset($paySysAction['PARAMS']) ? $paySysAction['PARAMS'] : ''
	);

	$actionPath = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'].$actionFile);
	$actionPathLength = strlen($actionPath);
	while (substr($actionPath, ($actionPathLength - 1), 1) === '/')
	{
		$actionPathLength -= 1;
		$actionPath = substr($actionPath, 0, $actionPathLength);
	}

	$actionFilePath = "{$actionPath}/payment.php";
	if (!(is_dir($actionPath) && file_exists($actionFilePath)))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_FIND_PAY_SYSTEM_HANDLER')));
	}
	//HACK: params for pdf handler
	$_REQUEST['pdf'] = 'Y';
	$_REQUEST['GET_CONTENT'] = 'Y';

	$content = include($actionFilePath);
	if($content === '')
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_NO_PDF_CONTENT')));
	}

	$accountNumber = isset($order['ACCOUNT_NUMBER']) ? $order['ACCOUNT_NUMBER'] : '';
	if($accountNumber !== '')
	{
		$accountNumber = preg_replace('/[^a-zA-Z0-9_-]/', '', $accountNumber);
	}

	if($accountNumber === '')
	{
		$accountNumber = strval($ID);
	}

	$fileID = CFile::SaveFile(
		array(
			'name' => "invoice_{$accountNumber}.pdf",
			'type' => 'file',
			'content' => $content,
			'MODULE_ID' => 'crm'
		),
		'crm'
	);

	if(!(is_int($fileID) && $fileID > 0))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_CREATE_FILE')));
	}

	$storageTypeID =  StorageType::getDefaultTypeID();
	$elementID = StorageManager::saveEmailAttachment(CFile::GetFileArray($fileID), $storageTypeID);
	if(!(is_int($elementID) && $elementID > 0))
	{
		__CrmMobileInvoiceEditEndResonse(array('ERROR' => GetMessage('CRM_INVOICE_COULD_NOT_CREATE_WEBDAV_ELEMENT')));
	}

	__CrmMobileInvoiceEditEndResonse(
		array('RESULT_DATA' => array('ELEMENT_INFO' => StorageManager::getFileInfo($elementID, $storageTypeID)))
	);
}
else
{
	__CrmMobileInvoiceEditEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}
