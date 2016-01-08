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
 * 'UPDATE' - update deal field
 */
global $APPLICATION;
$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if(!function_exists('__CrmDealShowEndJsonResonse'))
{
	function __CrmDealShowEndJsonResonse($result)
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
if(!function_exists('__CrmDealShowEndHtmlResonse'))
{
	function __CrmDealShowEndHtmlResonse()
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
	__CrmDealShowEndJsonResonse(array('ERROR'=>'MODE IS NOT DEFINED!'));
}

if($mode === 'GET_USER_INFO')
{
	$result = array();

	$userProfileUrlTemplate = isset($_POST['USER_PROFILE_URL_TEMPLATE']) ? $_POST['USER_PROFILE_URL_TEMPLATE'] : '';
	if(!CCrmInstantEditorHelper::PrepareUserInfo(
		isset($_POST['USER_ID']) ? intval($_POST['USER_ID']) : 0,
		$result,
		array('USER_PROFILE_URL_TEMPLATE' => $userProfileUrlTemplate)))
	{
		__CrmDealShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		__CrmDealShowEndJsonResonse(array('USER_INFO' => $result));
	}
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmDeal::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmDealShowEndHtmlResonse();
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
	__CrmDealShowEndHtmlResonse();
}
if($mode === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmDealShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmDeal::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmDealShowEndHtmlResonse();
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
	__CrmDealShowEndHtmlResonse();
}

$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
if($type !== 'D')
{
	__CrmDealShowEndJsonResonse(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
}

$CCrmDeal = new CCrmDeal();
if ($CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE'))
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION DENIED!'));
	die();
}

if($mode === 'UPDATE')
{
	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		__CrmDealShowEndJsonResonse(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
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
		__CrmDealShowEndJsonResonse(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
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

	$dbResult = CCrmDeal::GetListEx(
		array(),
		array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('*', 'UF_*')
	);
	$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Deal, $arFields, $fieldNames, $fieldValues);
		$disableUserFieldCheck = !$hasUserFields
			&& isset($_POST['DISABLE_USER_FIELD_CHECK'])
			&& strtoupper($_POST['DISABLE_USER_FIELD_CHECK']) === 'Y';
		if($CCrmDeal->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => $disableUserFieldCheck)))
		{
			$arErrors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Deal,
				$ID,
				CCrmBizProcEventType::Edit,
				$arErrors
			);
		}
	}
}
die();
?>
