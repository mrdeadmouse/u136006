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
 * 'UPDATE' - update invoice field
 * 'GET_USER_SELECTOR' - prepare user selector
 */
global $APPLICATION;
$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if(!function_exists('__CrmInvoiceShowEndJsonResonse'))
{
	function __CrmInvoiceShowEndJsonResonse($result)
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
if(!function_exists('__CrmInvoiceShowEndHtmlResonse'))
{
	function __CrmInvoiceShowEndHtmlResonse()
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
	__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'MODE IS NOT DEFINED!'));
}

if($mode === 'SAVE_PDF')
{
	if (!CModule::IncludeModule('sale'))
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'MODULE SALE NOT INCLUDED!'));
	}

	if(isset($_POST['INVOICE_ID']))
	{
		$invoice_id = $_POST['INVOICE_ID'];
	}
	else
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'INVOICE_ID NOT DEFINED!'));
	}

	$CCrmInvoice = new CCrmInvoice();
	if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ') || !CCrmInvoice::CheckReadPermission($invoice_id))
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}


	$pdfContent = '';

	$dbOrder = CSaleOrder::GetList(
		array("ID"=>"DESC"),
		array("ID" => $invoice_id)
	);

	$arOrder = $dbOrder->GetNext();
	if(!$arOrder)
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'COULD NOT FIND ORDER!'));
	}

	if (strlen($arOrder["SUM_PAID"]) > 0)
		$arOrder["PRICE"] -= $arOrder["SUM_PAID"];

	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			"PAY_SYSTEM_ID" => $arOrder["PAY_SYSTEM_ID"],
			"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"]
		),
		false,
		false,
		array("ACTION_FILE", "PARAMS", "ENCODING")
	);

	if ($arPaySysAction = $dbPaySysAction->Fetch())
	{
		if (strlen($arPaySysAction["ACTION_FILE"]) > 0)
		{
			CSalePaySystemAction::InitParamArrays($arOrder, $ID, $arPaySysAction["PARAMS"]);

			$pathToAction = $_SERVER["DOCUMENT_ROOT"].$arPaySysAction["ACTION_FILE"];

			$pathToAction = str_replace("\\", "/", $pathToAction);
			while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/")
				$pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);

			if (is_dir($pathToAction) && file_exists($pathToAction."/payment.php") )
			{
				$pdfContent = include($pathToAction."/payment.php");
			}
			else
			{
				__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'PDF MAKER NOT FOUNDED!'));
			}
		}
	}

	$invNum = isset($_REQUEST['INVOICE_NUM']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST['INVOICE_NUM']) : '';
	$fileName = 'invoice_'.(strlen($invNum) > 0 ? $invNum : strval($invoice_id)).'.pdf';

	$fileData = array(
		'name' => $fileName,
		'type' => 'file',
		'content' => $pdfContent,
		'MODULE_ID' => 'crm'
	);

	$fileID = CFile::SaveFile($fileData, 'crm');
	if($fileID <= 0)
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR' => 'COULD NOT SAVE FILE!'));
	}

	$fileArray = CFile::GetFileArray($fileID);
	$storageTypeID = \Bitrix\Crm\Integration\StorageType::getDefaultTypeID();
	if($storageTypeID !== \Bitrix\Crm\Integration\StorageType::File)
	{
		$storageFileID = \Bitrix\Crm\Integration\StorageManager::saveEmailAttachment($fileArray, $storageTypeID);
		$fileInfo = $storageFileID > 0 ? \Bitrix\Crm\Integration\StorageManager::getFileInfo($storageFileID, $storageTypeID) : null;
		if(is_array($fileInfo))
		{
			if($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav)
			{
				__CrmInvoiceShowEndJsonResonse(array('webdavelement' => $fileInfo));
			}
			elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
			{
				__CrmInvoiceShowEndJsonResonse(array('diskfile' => $fileInfo));
			}
		}
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE FILE INFO!'));
	}

	__CrmInvoiceShowEndJsonResonse(
		array('file' =>
			array(
				"fileName" => $fileArray['FILE_NAME'],
				"fileID" => $fileID,
				"fileSize" => CFile::FormatSize($fileArray['FILE_SIZE']),
				"src" => $fileArray['SRC']
			)
		)
	);
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmInvoice::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmInvoiceShowEndHtmlResonse();
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
	__CrmInvoiceShowEndHtmlResonse();
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmInvoice::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmInvoiceShowEndHtmlResonse();
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
	__CrmInvoiceShowEndHtmlResonse();
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
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		__CrmInvoiceShowEndJsonResonse(array('USER_INFO' => $result));
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

	__CrmInvoiceShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
if($type !== 'I')
{
	__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
}

if($mode === 'UPDATE')
{
	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
	}

	$CCrmInvoice = new CCrmInvoice();
	if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE') || !CCrmInvoice::CheckUpdatePermission($ID))
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}


	$fieldNames = array();
	if(isset($_POST['FIELD_NAME']))
	{
		if(is_array($_POST['FIELD_NAME']))
		{
			$fieldNames = $_POST['FIELD_NAME'];
		}
		else
		{
			$fieldNames[] = $_POST['FIELD_NAME'];
		}
	}

	if(count($fieldNames) == 0)
	{
		__CrmInvoiceShowEndJsonResonse(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
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

	$arFields = CCrmInvoice::GetByID($ID);
	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Invoice, $arFields, $fieldNames, $fieldValues);

		// check fields
		if (!$CCrmInvoice->CheckFieldsUpdate($arFields, $ID))
		{
			$errMsg = '';
			if (!empty($CCrmInvoice->LAST_ERROR))
				$errMsg .= $CCrmInvoice->LAST_ERROR;
			else
				$errMsg .= GetMessage('UNKNOWN_ERROR');

			__CrmInvoiceShowEndJsonResonse(array('ERROR'=>$errMsg));
		}

		$CCrmInvoice->Update($ID, $arFields, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
	}
}
die();
?>