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
 * 'UPDATE' - update quote field
 */
global $APPLICATION;
$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if(!function_exists('__CrmQuoteShowEndJsonResonse'))
{
	function __CrmQuoteShowEndJsonResonse($result)
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
if(!function_exists('__CrmQuoteShowEndHtmlResonse'))
{
	function __CrmQuoteShowEndHtmlResonse()
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
if(!isset($mode[0]))
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'MODE IS NOT DEFINED!'));
	die();
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if($mode === 'SAVE_PDF')
{
	$quoteID = isset($_POST['QUOTE_ID']) ? intval($_POST['QUOTE_ID']) : 0;
	if($quoteID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'QUOTE_ID IS NOT FOUND!'));
		die();
	}

	if(!CCrmQuote::CheckReadPermission($quoteID, $userPermissions))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION DENIED!'));
		die();
	}

	if (!CModule::IncludeModule('sale'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'MODULE SALE NOT INCLUDED!'));
		die();
	}

	$paySystemID = isset($_POST['PAY_SYSTEM_ID']) ? intval($_POST['PAY_SYSTEM_ID']) : 0;
	if($paySystemID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PAY_SYSTEM_ID ID NOT FOUND!'));
		die();
	}

	$dbResult = CCrmQuote::GetList(array(), array('ID' => $quoteID, 'CHECK_PERMISSIONS' => 'N'), false, false, array('*', 'UF_*'));
	$quoteFields = is_object($dbResult) ? $dbResult->Fetch() : null;
	if(!is_array($quoteFields))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'QUOTE IS NOT FOUND!'));
		die();
	}
	$paymentData = CCrmQuote::PrepareSalePaymentData($quoteFields);
	if(!is_array($paymentData))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT PREPARE PAYMENT DATA!'));
		die();
	}

	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			'PAY_SYSTEM_ID' => $paySystemID,
			'PERSON_TYPE_ID' => $quoteFields['PERSON_TYPE_ID']
		),
		false,
		false,
		array('ACTION_FILE', 'PARAMS', 'ENCODING')
	);

	$paySysActionFields = $dbPaySysAction->Fetch();
	if(!is_array($paySysActionFields))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT FIND PAYMENT SYSTEM ACTION!'));
		die();
	}

	$actionFilePath = isset($paySysActionFields['ACTION_FILE']) ? $paySysActionFields['ACTION_FILE'] : "";
	if(!is_string($actionFilePath) || $actionFilePath === '')
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!'));
		die();
	}

	$actionFilePath = $_SERVER["DOCUMENT_ROOT"].$actionFilePath;
	$actionFilePath = str_replace("\\", "/", $actionFilePath);
	while (substr($actionFilePath, strlen($actionFilePath) - 1, 1) == "/")
		$actionFilePath = substr($actionFilePath, 0, strlen($actionFilePath) - 1);

	if(!file_exists($actionFilePath))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!'));
		die();
	}
	elseif(is_dir($actionFilePath))
	{
		$actionFilePath = $actionFilePath.'/payment.php';
		if(!file_exists($actionFilePath))
		{
			echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!'));
			die();
		}
	}

	CSalePaySystemAction::InitParamArrays(
		$paymentData['ORDER'],
		0,
		$paySysActionFields['PARAMS'],
		array(
			"PROPERTIES" => $paymentData['PROPERTIES'],
			"BASKET_ITEMS" => $paymentData['CART_ITEMS'],
			"TAX_LIST" => $paymentData["TAX_LIST"]
		)
	);
	$pdfContent = include($actionFilePath);

	$fileName = "quote_{$quoteID}.pdf";
	$fileData = array(
		'name' => $fileName,
		'type' => 'file',
		'content' => $pdfContent,
		'MODULE_ID' => 'crm'
	);

	$fileID = CFile::SaveFile($fileData, 'crm');
	if($fileID > 0)
	{
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
					echo CUtil::PhpToJSObject(array('webdavelement' => $fileInfo));
				}
				elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
				{
					echo CUtil::PhpToJSObject(array('diskfile' => $fileInfo));
				}
			}
		}
		else
		{
			echo CUtil::PhpToJSObject(
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
	}
	die();
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
		echo CUtil::PhpToJSObject(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		echo CUtil::PhpToJSObject(array('USER_INFO' => $result));
	}
	die();
}
if($mode === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmQuoteShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
if($mode === 'UPDATE')
{
	$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
	if($type !== CCrmQuote::OWNER_TYPE)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
		die();
	}

	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
		die();
	}

	if(!CCrmQuote::CheckUpdatePermission($ID, $userPermissions))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION DENIED!'));
		die();
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
		echo CUtil::PhpToJSObject(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
		die();
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

	$dbResult = CCrmQuote::GetList(
		array(),
		array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('*', 'UF_*')
	);
	$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;

	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Quote, $arFields, $fieldNames, $fieldValues);
		$CCrmQuote = new CCrmQuote();
		if($CCrmQuote->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true)))
		{
			/*---bizproc---$arErrors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Quote,
				$ID,
				CCrmBizProcEventType::Edit,
				$arErrors
			);*/
		}
	}
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmQuote::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmQuoteShowEndHtmlResonse();
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
	__CrmQuoteShowEndHtmlResonse();
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmQuote::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmQuoteShowEndHtmlResonse();
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
	__CrmQuoteShowEndHtmlResonse();
}
if($mode === 'GET_ENTITY_SIP_INFO')
{
	$entityType = isset($_POST['ENITY_TYPE']) ? $_POST['ENITY_TYPE'] : '';
	$m = null;
	if($entityType === '' || preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) !== 1)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED!'));
	}

	$entityTypeName = isset($m[1]) ? strtoupper($m[1]) : '';
	if($entityTypeName !== CCrmOwnerType::QuoteName)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED IS NOT SUPPORTED IN CURRENT CONTEXT!'));
	}

	$entityID = isset($_POST['ENITY_ID']) ? intval($_POST['ENITY_ID']) : 0;
	if($entityID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY ID IS INVALID OR NOT DEFINED!'));
	}

	$dbRes = CCrmQuote::GetList(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('TITLE'));
	$arRes = $dbRes ? $dbRes->Fetch() : null;
	if(!$arRes)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY IS NOT FOUND!'));
	}
	else
	{
		__CrmQuoteShowEndJsonResonse(
			array('DATA' =>
				array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'IMAGE_URL' => '',
					'SHOW_URL' => CCrmOwnerType::GetShowUrl(CCrmOwnerType::Quote, $entityID, false),
				)
			)
		);
	}
}
die();
?>
