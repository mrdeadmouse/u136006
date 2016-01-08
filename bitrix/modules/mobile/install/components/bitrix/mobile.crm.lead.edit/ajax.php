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

if(!function_exists('__CrmMobileLeadEditEndResonse'))
{
	function __CrmMobileLeadEditEndResonse($result)
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
	if($typeName !== CCrmOwnerType::LeadName)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$hasPermission = $isNew ? CCrmLead::CheckCreatePermission() : CCrmLead::CheckUpdatePermission($ID);
	if(!$hasPermission)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$currentItem = null;
	if(!$isNew)
	{
		$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		if(!is_array($currentItem))
		{
			__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND', array('#ID#' => $ID))));
		}
	}

	$title = isset($data['TITLE']) ? $data['TITLE'] : '';
	if($title === '')
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_LEAD_TITLE_NOT_ASSIGNED')));
	}

	$opportunity = isset($data['OPPORTUNITY']) ? $data['OPPORTUNITY'] : '';
	if($opportunity === '')
	{
		$opportunity = 0.0;
	}

	$comments = isset($data['COMMENTS']) ? $data['COMMENTS'] : '';
	if($comments !== '')
	{
		$sanitizer = new CBXSanitizer();
		$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_HIGH);
		$comments = $sanitizer->SanitizeHtml($comments);
	}

	$fields = array(
		'TITLE' => $title,
		'NAME' => isset($data['NAME']) ? $data['NAME'] : '',
		'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
		'LAST_NAME' => isset($data['LAST_NAME']) ? $data['LAST_NAME'] : '',
		'COMPANY_TITLE' => isset($data['COMPANY_TITLE']) ? $data['COMPANY_TITLE'] : '',
		'OPPORTUNITY' => $opportunity,
		'STATUS_ID' => isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '',
		'ADDRESS' => isset($data['ADDRESS']) ? $data['ADDRESS'] : '',
		'ADDRESS_2' => isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '',
		'ADDRESS_CITY' => isset($data['ADDRESS_CITY']) ? $data['ADDRESS_CITY'] : '',
		'ADDRESS_REGION' => isset($data['ADDRESS_REGION']) ? $data['ADDRESS_REGION'] : '',
		'ADDRESS_PROVINCE' => isset($data['ADDRESS_PROVINCE']) ? $data['ADDRESS_PROVINCE'] : '',
		'ADDRESS_POSTAL_CODE' => isset($data['ADDRESS_POSTAL_CODE']) ? $data['ADDRESS_POSTAL_CODE'] : '',
		'ADDRESS_COUNTRY' => isset($data['ADDRESS_COUNTRY']) ? $data['ADDRESS_COUNTRY'] : '',
		'SOURCE_ID' => isset($data['SOURCE_ID']) ? $data['SOURCE_ID'] : '',
		'COMMENTS' => $comments
	);

	$currencyID = isset($data['CURRENCY_ID']) ? $data['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}
	$fields['CURRENCY_ID'] = $currencyID;

	$productDataFieldName = 'PRODUCT_ROWS';
	$processProductRows = isset($data['PROCESS_PRODUCT_ROWS']) ? ($data['PROCESS_PRODUCT_ROWS'] === 'Y') : false;

	$productRows = array();
	if($processProductRows)
	{
		$productRows = isset($data[$productDataFieldName]) ? $data[$productDataFieldName] : array();

		if(!empty($productRows))
		{
			foreach($productRows as &$productRow)
			{
				$productRow['CUSTOMIZED'] = 'Y';
			}
			unset($productRow);

			$params = array(
				'CONTACT_ID' => 0,
				'COMPANY_ID' => 0,
				'CURRENCY_ID' => $fields['CURRENCY_ID']
			);
			$result = CCrmProductRow::CalculateTotalInfo('L', 0, false, $params, $productRows);
			$fields['OPPORTUNITY'] = isset($result['OPPORTUNITY']) ? $result['OPPORTUNITY'] : 0.0;
		}
	}

	$assignedByID = isset($data['ASSIGNED_BY_ID']) ? intval($data['ASSIGNED_BY_ID']) : 0;
	if($assignedByID <= 0)
	{
		$assignedByID = intval($curUser->GetID());
	}
	$fields['ASSIGNED_BY_ID'] = $assignedByID;

	if(isset($data['FM']) && is_array($data['FM']) && !empty($data['FM']))
	{
		$fields['FM'] = $data['FM'];
	}

	$entity = new CCrmLead(false);
	if(!$entity->CheckFields($fields, !$isNew ? $ID : false, array('DISABLE_USER_FIELD_CHECK' => true)))
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $entity->LAST_ERROR))));
	}
	else
	{
		//$DB->StartTransaction();
		$successed = false;
		if($isNew)
		{
			$ID = $entity->Add($fields, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
			$successed = $ID !== false;
		}
		else
		{
			$successed = $entity->Update($ID, $fields, true, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
		}

		if($successed && $processProductRows && (!$isNew || !empty($productRows)))
		{
			$successed = CCrmLead::SaveProductRows($ID, $productRows, false, true, false);
			if(!$successed)
			{
				$fields['RESULT_MESSAGE'] = 'Could not save product rows.';
			}
		}

		if($successed)
		{
			//$DB->Commit();

			$errors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$ID,
				$isNew ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit,
				$errors
			);

			$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->GetNext();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareLeadItem($currentItem, $formatParams);

			__CrmMobileLeadEditEndResonse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareLeadData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileLeadEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $fields['RESULT_MESSAGE']))));
		}
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::LeadName)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmLead::CheckDeletePermission($ID))
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$entity = new CCrmLead(false);
	//$DB->StartTransaction();
	$successed = $entity->Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileLeadEditEndResonse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_LEAD_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::LeadName)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmLead::CheckReadPermission($ID))
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
	$item = $dbRes ? $dbRes->GetNext() : null;
	if(!is_array($item))
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND', array('#ID#' => $ID))));
	}

	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareLeadItem($item, $formatParams);

	__CrmMobileLeadEditEndResonse(
		array('ENTITY' => CCrmMobileHelper::PrepareLeadData($item))
	);
}
elseif($action === 'SET_STATUS')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::LeadName)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}


	if(!CCrmLead::CheckUpdatePermission($ID, $curUserPrems))
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$statusID = isset($data['STATUS_ID']) ? $data['STATUS_ID'] : '';
	if($statusID === '')
	{
		__CrmMobileLeadEditEndResonse(array('ERROR' => GetMessage('CRM_LEAD_STATUS_NOT_FOUND')));
	}

	//$DB->StartTransaction();

	$fields = array('STATUS_ID' => $statusID);
	$entity = new CCrmLead(false);
	$successed = $entity->Update($ID, $fields, true, true, array());
	if($successed)
	{
		//$DB->Commit();

		$errors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$ID,
			CCrmBizProcEventType::Edit,
			$errors
		);

		$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

		CCrmMobileHelper::PrepareLeadItem($currentItem, $formatParams);

		__CrmMobileLeadEditEndResonse(
			array(
				'SAVED_ENTITY_ID' => $ID,
				'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareLeadData($currentItem)
			)
		);
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileLeadEditEndResonse(array('ERROR' => $fields['RESULT_MESSAGE']));
	}
}
elseif($action === 'CONVERT_MONEY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$srcCurrencyID = isset($_REQUEST['SRC_CURRENCY_ID']) ? $_REQUEST['SRC_CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$dstCurrencyID = isset($_REQUEST['DST_CURRENCY_ID']) ? $_REQUEST['DST_CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$srcSum = isset($_REQUEST['SUM']) ? doubleval($_REQUEST['SUM']) : 0.0;
	$dstSum = CCrmCurrency::ConvertMoney(
		$srcSum,
		$srcCurrencyID,
		$dstCurrencyID
	);

	__CrmMobileLeadEditEndResonse(
		array(
			'SUM' => $dstSum,
			'CURRENCY_ID' => $dstCurrencyID,
			'CURRENCY_NAME' => CCrmCurrency::GetCurrencyName($dstCurrencyID),
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($dstSum, $dstCurrencyID)
		)
	);
}
else
{
	__CrmMobileLeadEditEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}




