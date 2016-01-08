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

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!function_exists('__CrmMobileFileEndResonse'))
{
	function __CrmMobileFileEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo json_encode($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

		die();
	}
}

if (CModule::IncludeModule('compression'))
{
	CCompress::Disable2048Spaces();
}

CUtil::JSPostUnescape();

if(!CModule::IncludeModule('crm'))
{
	__CrmMobileFileEndResonse(array('error' => 'Could not include "crm" module.'));
}

if(!(check_bitrix_sessid() && CCrmSecurityHelper::IsAuthorized()))
{
	__CrmMobileFileEndResonse(array('error' => 'Access denied.'));
}

global $APPLICATION;
if($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($_FILES) && !empty($_FILES))
{
	$ID = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$hasPermission = $ID > 0 ? CCrmCompany::CheckUpdatePermission($ID) : CCrmCompany::CheckCreatePermission();
	if(!$hasPermission)
	{
		__CrmMobileFileEndResonse(array('error' => 'Access denied.'));
	}
	else
	{
		$result = array();
		if(!CCrmMobileHelper::TryUploadFile(
			$result,
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Company,
				'OWNER_ID' => $ID,
				'SCOPE' => 'I',
				'MAX_FILE_SIZE' => 5242880)))
		{
			__CrmMobileFileEndResonse(array('error' => $result['ERROR_MESSAGE']));
		}
		else
		{
			$fileID = $result['FILE_ID'];
			$fileInfo = CFile::ResizeImageGet(
				$fileID,
				array('width' => 55, 'height' => 55),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			__CrmMobileFileEndResonse(
				array(
					'fileId' => $fileID,
					'showUrl' => is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : ''
				)
			);
		}
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
