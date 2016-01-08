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

if(!function_exists('__CrmMobileDealEditEndResonse'))
{
	function __CrmMobileDealEditEndResonse($result)
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
	if($typeName !== CCrmOwnerType::DealName)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$hasPermission = $isNew ? CCrmDeal::CheckCreatePermission() : CCrmDeal::CheckUpdatePermission($ID);
	if(!$hasPermission)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$currentItem = null;
	if(!$isNew)
	{
		$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		if(!is_array($currentItem))
		{
			__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND', array('#ID#' => $ID))));
		}
	}

	$title = isset($data['TITLE']) ? $data['TITLE'] : '';
	if($title === '')
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_TITLE_NOT_ASSIGNED')));
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
		'OPPORTUNITY' => $opportunity,
		'PROBABILITY' => isset($data['PROBABILITY']) ? $data['PROBABILITY'] : 0,
		'STAGE_ID' => isset($data['STAGE_ID']) ? $data['STAGE_ID'] : '',
		'TYPE_ID' => isset($data['TYPE_ID']) ? $data['TYPE_ID'] : '',
		'COMPANY_ID' => isset($data['COMPANY_ID']) ? $data['COMPANY_ID'] : 0,
		'CONTACT_ID' => isset($data['CONTACT_ID']) ? $data['CONTACT_ID'] : 0,
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

			$result = CCrmProductRow::CalculateTotalInfo('D', 0, false, $fields, $productRows);
			$fields['OPPORTUNITY'] = isset($result['OPPORTUNITY']) ? $result['OPPORTUNITY'] : 0.0;
			$fields['TAX_VALUE'] = isset($result['TAX_VALUE']) ? $result['TAX_VALUE'] : 0.0;
		}
	}

	$assignedByID = isset($data['ASSIGNED_BY_ID']) ? intval($data['ASSIGNED_BY_ID']) : 0;
	if($assignedByID <= 0)
	{
		$assignedByID = intval($curUser->GetID());
	}
	$fields['ASSIGNED_BY_ID'] = $assignedByID;

	$entity = new CCrmDeal(false);
	if(!$entity->CheckFields($fields, !$isNew ? $ID : false, array('DISABLE_USER_FIELD_CHECK' => true)))
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $entity->LAST_ERROR))));
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
			$successed = CCrmDeal::SaveProductRows($ID, $productRows, false, true, false);
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
				CCrmOwnerType::Deal,
				$ID,
				$isNew ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit,
				$errors
			);

			$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->GetNext();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareDealItem($currentItem, $formatParams);

			__CrmMobileDealEditEndResonse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareDealData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileDealEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $fields['RESULT_MESSAGE']))));
		}
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::DealName)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmDeal::CheckDeletePermission($ID))
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$entity = new CCrmDeal(false);
	//$DB->StartTransaction();
	$successed = $entity->Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileDealEditEndResonse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::DealName)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmDeal::CheckReadPermission($ID))
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
	$item = $dbRes ? $dbRes->GetNext() : null;
	if(!is_array($item))
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND', array('#ID#' => $ID))));
	}

	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareDealItem($item, $formatParams);

	__CrmMobileDealEditEndResonse(
		array('ENTITY' => CCrmMobileHelper::PrepareDealData($item))
	);
}
elseif($action === 'SET_STAGE')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== CCrmOwnerType::DealName)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}


	if(!CCrmDeal::CheckUpdatePermission($ID, $curUserPrems))
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$stageID = isset($data['STAGE_ID']) ? $data['STAGE_ID'] : '';
	if($stageID === '')
	{
		__CrmMobileDealEditEndResonse(array('ERROR' => GetMessage('CRM_DEAL_STAGE_NOT_FOUND')));
	}

	//$DB->StartTransaction();

	$fields = array('STAGE_ID' => $stageID);
	$entity = new CCrmDeal(false);
	$successed = $entity->Update($ID, $fields, true, true, array());
	if($successed)
	{
		//$DB->Commit();

		$errors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Deal,
			$ID,
			CCrmBizProcEventType::Edit,
			$errors
		);

		$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

		CCrmMobileHelper::PrepareDealItem($currentItem, $formatParams);

		__CrmMobileDealEditEndResonse(
			array(
				'SAVED_ENTITY_ID' => $ID,
				'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareDealData($currentItem)
			)
		);
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileDealEditEndResonse(array('ERROR' => $fields['RESULT_MESSAGE']));
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

	__CrmMobileDealEditEndResonse(
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
	__CrmMobileDealEditEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}




