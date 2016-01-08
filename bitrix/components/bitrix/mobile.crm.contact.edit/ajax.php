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

if(!function_exists('__CrmMobileContactEditEndResonse'))
{
	function __CrmMobileContactEditEndResonse($result)
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
	if($typeName !== 'CONTACT')
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$hasPermission = $isNew ? CCrmContact::CheckCreatePermission() : CCrmContact::CheckUpdatePermission($ID);
	if(!$hasPermission)
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$currentItem = null;
	if(!$isNew)
	{
		$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		if(!is_array($currentItem))
		{
			__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_CONTACT_NOT_FOUND', array('#ID#' => $ID))));
		}
	}

	$name = isset($data['NAME']) ? $data['NAME'] : '';
	$lastName = isset($data['LAST_NAME']) ? $data['LAST_NAME'] : '';

	if($name === '' && $lastName === '')
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_CONTACT_NAME_NOT_ASSIGNED')));
	}

	$comments = isset($data['COMMENTS']) ? $data['COMMENTS'] : '';
	if($comments !== '')
	{
		$sanitizer = new CBXSanitizer();
		$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_HIGH);
		$comments = $sanitizer->SanitizeHtml($comments);
	}

	$fields = array(
		'NAME' => $name,
		'LAST_NAME' => $lastName,
		'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
		'ADDRESS' => isset($data['ADDRESS']) ? $data['ADDRESS'] : '',
		'ADDRESS_2' => isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '',
		'ADDRESS_CITY' => isset($data['ADDRESS_CITY']) ? $data['ADDRESS_CITY'] : '',
		'ADDRESS_REGION' => isset($data['ADDRESS_REGION']) ? $data['ADDRESS_REGION'] : '',
		'ADDRESS_PROVINCE' => isset($data['ADDRESS_PROVINCE']) ? $data['ADDRESS_PROVINCE'] : '',
		'ADDRESS_POSTAL_CODE' => isset($data['ADDRESS_POSTAL_CODE']) ? $data['ADDRESS_POSTAL_CODE'] : '',
		'ADDRESS_COUNTRY' => isset($data['ADDRESS_COUNTRY']) ? $data['ADDRESS_COUNTRY'] : '',
		'TYPE_ID' => isset($data['TYPE_ID']) ? $data['TYPE_ID'] : '',
		'COMMENTS' => $comments
	);

	$companyID = isset($data['COMPANY_ID']) ? intval($data['COMPANY_ID']) : 0;
	if($companyID > 0)
	{
		$fields['COMPANY_ID'] = $companyID;
	}

	$assignedByID = isset($data['ASSIGNED_BY_ID']) ? intval($data['ASSIGNED_BY_ID']) : 0;
	if($assignedByID <= 0)
	{
		$assignedByID = intval($curUser->GetID());
	}

	$fields['ASSIGNED_BY_ID'] = $assignedByID;

	$photoID = isset($data['PHOTO']) ? intval($data['PHOTO']) : 0;
	if($photoID > 0)
	{
		$allowedFileIDs = CCrmMobileHelper::GetUploadedFileIDs(CCrmOwnerType::Contact, $ID);
		if(!$isNew)
		{
			$currentPhotoID = isset($currentItem['PHOTO']) ? intval($currentItem['PHOTO']) : 0;
			if($currentPhotoID > 0)
			{
				$allowedFileIDs[] = $currentPhotoID;
			}
		}

		if(!in_array($photoID, $allowedFileIDs, true))
		{
			$photoID = 0;
		}
	}

	if($photoID > 0)
	{
		$fields['PHOTO'] = $photoID;
	}

	if(isset($data['FM']) && is_array($data['FM']) && !empty($data['FM']))
	{
		$fields['FM'] = $data['FM'];
	}

	$entity = new CCrmContact(false);
	if(!$entity->CheckFields($fields, !$isNew ? $ID : false, array('DISABLE_USER_FIELD_CHECK' => true)))
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $entity->LAST_ERROR))));
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

		if($successed)
		{
			//$DB->Commit();
			$errors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Contact,
				$ID,
				$isNew ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit,
				$errors
			);

			$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->GetNext();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareContactItem($currentItem, $formatParams);

			__CrmMobileContactEditEndResonse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareContactData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileContactEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $fields['RESULT_MESSAGE']))));
		}
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'CONTACT')
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmContact::CheckDeletePermission($ID))
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$entity = new CCrmContact(false);
	//$DB->StartTransaction();
	$successed = $entity->Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileContactEditEndResonse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_CONTACT_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'CONTACT')
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmContact::CheckReadPermission($ID))
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
	$item = $dbRes ? $dbRes->GetNext() : null;
	if(!is_array($item))
	{
		__CrmMobileContactEditEndResonse(array('ERROR' => GetMessage('CRM_CONTACT_NOT_FOUND', array('#ID#' => $ID))));
	}

	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareContactItem($item, $formatParams);

	__CrmMobileContactEditEndResonse(
		array('ENTITY' => CCrmMobileHelper::PrepareContactData($item))
	);
}
else
{
	__CrmMobileContactEditEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}




