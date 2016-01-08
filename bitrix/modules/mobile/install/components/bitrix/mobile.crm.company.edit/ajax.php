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

if(!function_exists('__CrmMobileCompanyEditEndResonse'))
{
	function __CrmMobileCompanyEditEndResonse($result)
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
	if($typeName !== 'COMPANY')
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$hasPermission = $isNew ? CCrmCompany::CheckCreatePermission() : CCrmCompany::CheckUpdatePermission($ID);
	if(!$hasPermission)
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$currentItem = null;
	if(!$isNew)
	{
		$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->GetNext();
		if(!is_array($currentItem))
		{
			__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_COMPANY_NOT_FOUND', array('#ID#' => $ID))));
		}
	}

	$title = isset($data['TITLE']) ? $data['TITLE'] : '';
	if($title === '')
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_COMPANY_TITLE_NOT_ASSIGNED')));
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
		'COMPANY_TYPE' => isset($data['COMPANY_TYPE']) ? $data['COMPANY_TYPE'] : '',
		'INDUSTRY' => isset($data['INDUSTRY']) ? $data['INDUSTRY'] : '',
		'REVENUE' => isset($data['REVENUE']) ? $data['REVENUE'] : '',
		'ADDRESS' => isset($data['ADDRESS']) ? $data['ADDRESS'] : '',
		'COMMENTS' => $comments

	);

	$assignedByID = isset($data['ASSIGNED_BY_ID']) ? intval($data['ASSIGNED_BY_ID']) : 0;
	if($assignedByID <= 0)
	{
		$assignedByID = intval($curUser->GetID());
	}

	$fields['ASSIGNED_BY_ID'] = $assignedByID;

	$logoID = isset($data['LOGO']) ? intval($data['LOGO']) : 0;
	if($logoID > 0)
	{
		$allowedFileIDs = CCrmMobileHelper::GetUploadedFileIDs(CCrmOwnerType::Company, $ID);
		if(!$isNew)
		{
			$currentLogoID = isset($currentItem['LOGO']) ? intval($currentItem['LOGO']) : 0;
			if($currentPhotoID > 0)
			{
				$allowedFileIDs[] = $currentLogoID;
			}
		}

		if(!in_array($logoID, $allowedFileIDs, true))
		{
			$logoID = 0;
		}
	}

	if($logoID > 0)
	{
		$fields['LOGO'] = $logoID;
	}

	if(isset($data['FM']) && is_array($data['FM']) && !empty($data['FM']))
	{
		$fields['FM'] = $data['FM'];
	}

	$entity = new CCrmCompany(false);
	if(!$entity->CheckFields($fields, !$isNew ? $ID : false, array('DISABLE_USER_FIELD_CHECK' => true)))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $entity->LAST_ERROR))));
	}
	else
	{
		//$DB->StartTransaction();
		$successed = false;
		if($isNew)
		{
			$ID = $entity->Add($fields, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
			$successed = $ID !== false;

			if($successed)
			{
				$contactID = isset($data['CONTACT_ID']) ? intval($data['CONTACT_ID']) : 0;
				if($contactID > 0 && CCrmContact::Exists($contactID))
				{
					$dbContacts = CCrmContact::GetListEx(array(), array('=ID' => $contactID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('COMPANY_ID'));
					$contact = $dbContacts ? $dbContacts->Fetch() : null;
					if(is_array($contact))
					{
						$contactCompanyID = isset($contact['COMPANY_ID']) ? intval($contact['COMPANY_ID']) : 0;
						if($contactCompanyID !== $ID)
						{
							$contactFields = array('COMPANY_ID' => $ID);
							$contactEntity = new CCrmContact(false);
							$contactEntity->Update($contactID, $contactFields);
						}
					}
				}
			}
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
				CCrmOwnerType::Company,
				$ID,
				$isNew ? CCrmBizProcEventType::Create : CCrmBizProcEventType::Edit,
				$errors
			);

			$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->GetNext();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareCompanyItem($currentItem, $formatParams);
			__CrmMobileCompanyEditEndResonse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareCompanyData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileCompanyEditEndResonse(array('ERROR' => strip_tags(preg_replace("/<br[^>]*>/", "\n", $fields['RESULT_MESSAGE']))));
		}
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'COMPANY')
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmCompany::CheckDeletePermission($ID))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$entity = new CCrmCompany(false);
	//$DB->StartTransaction();
	$successed = $entity->Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileCompanyEditEndResonse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_COMPANY_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'COMPANY')
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	if(!CCrmCompany::CheckReadPermission($ID))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
	$item = $dbRes ? $dbRes->GetNext() : null;
	if(!is_array($item))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => GetMessage('CRM_COMPANY_NOT_FOUND', array('#ID#' => $ID))));
	}

	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareCompanyItem($item, $formatParams);

	__CrmMobileCompanyEditEndResonse(
		array('ENTITY' => CCrmMobileHelper::PrepareCompanyData($item))
	);
}
else
{
	__CrmMobileCompanyEditEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}




