<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * UPDATE - update contact field
 * GET_USER_INFO
 */
global $APPLICATION;
$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if(!function_exists('__CrmCompanyShowEndJsonResonse'))
{
	function __CrmCompanyShowEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}
if(!function_exists('__CrmCompanyShowEndHtmlResonse'))
{
	function __CrmCompanyShowEndHtmlResonse()
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if($mode === '')
{
	__CrmCompanyShowEndJsonResonse(array('ERROR'=>'MODE IS NOT DEFINED!'));
}

if($mode === 'GET_USER_INFO')
{
	$result = array();
	if(!CCrmInstantEditorHelper::PrepareUserInfo(isset($_POST['USER_ID']) ? intval($_POST['USER_ID']) : 0, $result))
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		__CrmCompanyShowEndJsonResonse(array('USER_INFO' => $result));
	}
}
if($mode === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmCompanyShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#')
		)
	);
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmCompany::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmCompanyShowEndHtmlResonse();
	}

	$name = isset($_POST['NAME']) ? $_POST['NAME'] : '';

	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.user.selector.new', '.default',
		array(
			'MULTIPLE' => 'N',
			'NAME' => $name,
			'POPUP' => 'Y',
			'SITE_ID' => SITE_ID
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	__CrmCompanyShowEndHtmlResonse();
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmCompany::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmCompanyShowEndHtmlResonse();
	}

	$lheEditorID = isset($_POST['EDITOR_ID']) ? $_POST['EDITOR_ID'] : '';
	$lheEditorName = isset($_POST['EDITOR_NAME']) ? $_POST['EDITOR_NAME'] : '';

	CModule::IncludeModule('fileman');
	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);

	$emailEditor = new CLightHTMLEditor();
	$emailEditor->Show(
		array(
			'id' => $lheEditorID,
			'height' => '250',
			'BBCode' => false,
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => false,
			'bResizable' => false,
			'autoResizeOffset' => 20,
			'jsObjName' => $lheEditorName,
			'bInitByJS' => false,
			'bSaveOnBlur' => false,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'Strike',
				'BackColor', 'ForeColor',
				'CreateLink', 'DeleteLink',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
			)
		)
	);
	__CrmCompanyShowEndHtmlResonse();
}
if($mode === 'GET_ENTITY_SIP_INFO')
{
	$entityType = isset($_POST['ENITY_TYPE']) ? $_POST['ENITY_TYPE'] : '';
	$m = null;
	if($entityType === '' || preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) !== 1)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ENITY TYPE IS NOT DEFINED!'));
		die();
	}

	$entityTypeName = isset($m[1]) ? strtoupper($m[1]) : '';
	if($entityTypeName !== CCrmOwnerType::CompanyName)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ENITY TYPE IS NOT DEFINED IS NOT SUPPORTED IN CURRENT CONTEXT!'));
		die();
	}

	$entityID = isset($_POST['ENITY_ID']) ? intval($_POST['ENITY_ID']) : 0;
	if($entityID <= 0)
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'ENITY ID IS INVALID OR NOT DEFINED!'));
	}

	$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('TITLE', 'LOGO'));
	$arRes = $dbRes ? $dbRes->Fetch() : null;
	if(!$arRes)
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'ENITY IS NOT FOUND!'));
	}
	else
	{
		if(!isset($arRes['LOGO']))
		{
			$imageUrl = '';
		}
		else
		{
			$fileInfo = CFile::ResizeImageGet(
				$arRes['LOGO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);

			$imageUrl = isset($fileInfo['src']) ? $fileInfo['src'] : '';
		}

		__CrmCompanyShowEndJsonResonse(
			array('DATA' =>
				array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'IMAGE_URL' => $imageUrl,
					'SHOW_URL' => CCrmOwnerType::GetShowUrl(CCrmOwnerType::Company, $entityID, false),
				)
			)
		);
	}
}

$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
if($type !== 'CO')
{
	__CrmCompanyShowEndJsonResonse(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
}

if (!CCrmCompany::CheckUpdatePermission(0))
{
	__CrmCompanyShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
}

if($mode === 'UPDATE')
{
	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
	}

	if (!CCrmCompany::CheckUpdatePermission($ID))
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$fieldNames = array();
	$hasUserFields = false;
	if(isset($_POST['FIELD_NAME']))
	{
		if(is_array($_POST['FIELD_NAME']))
		{
			$fieldNames = $_POST['FIELD_NAME'];
			foreach($fieldNames as $fieldName)
			{
				if(strncmp($fieldName, 'UF_', 3) === 0)
				{
					$hasUserFields = true;
					break;
				}
			}
		}
		else
		{
			$fieldNames[] = $_POST['FIELD_NAME'];
			if(!$hasUserFields)
			{
				$hasUserFields = strncmp($_POST['FIELD_NAME'], 'UF_', 3) === 0;
			}
		}
	}

	if(count($fieldNames) == 0)
	{
		__CrmCompanyShowEndJsonResonse(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
	}

	$fieldValues = array();
	if(isset($_POST['FIELD_VALUE']))
	{
		if(is_array($_POST['FIELD_VALUE']))
		{
			$fieldValues = $_POST['FIELD_VALUE'];
		}
		else
		{
			$fieldValues[] = $_POST['FIELD_VALUE'];
		}
	}

	$dbResult = CCrmCompany::GetListEx(
		array(),
		array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('*', 'UF_*')
	);
	$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Company, $arFields, $fieldNames, $fieldValues);
		$CCrmCompany = new CCrmCompany();
		$disableUserFieldCheck = !$hasUserFields
			&& isset($_POST['DISABLE_USER_FIELD_CHECK'])
			&& strtoupper($_POST['DISABLE_USER_FIELD_CHECK']) === 'Y';
		if($CCrmCompany->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => $disableUserFieldCheck)))
		{
			$arErrors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Company,
				$ID,
				CCrmBizProcEventType::Edit,
				$arErrors
			);

			$result = array();
			$count = count($fieldNames);
			for($i = 0; $i < $count; $i++)
			{
				$fieldName = $fieldNames[$i];
				if(strpos($fieldName, 'FM.') === 0)
				{
					//Filed name like 'FM.PHONE.WORK.1279'
					$fieldParams = explode('.', $fieldName);
					if(count($fieldParams) >= 3)
					{
						$result[$fieldName] = array(
							'VIEW_HTML' =>
								CCrmViewHelper::PrepareMultiFieldHtml(
									$fieldParams[1],
									array(
										'VALUE_TYPE_ID' => $fieldParams[2],
										'VALUE' => isset($fieldValues[$i]) ? $fieldValues[$i] : ''
									)
								)
						);
					}
				}
			}

			if(!empty($result))
			{
				__CrmCompanyShowEndJsonResonse(array('DATA' => $result));
			}
		}
	}
}
die();
?>
