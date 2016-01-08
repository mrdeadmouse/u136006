<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc') && !CModule::IncludeModule('bizproc'))
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmDeal = new CCrmDeal();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('DEAL');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;

if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}
$bConvert = isset($arParams['CONVERT']) && $arParams['CONVERT'];

$isPermitted = $bEdit
	? CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions)
	: CCrmDeal::CheckCreatePermission($userPermissions);

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('DEAL', array($arParams['ELEMENT_ID']))
	: array();

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

$bTaxMode = CCrmTax::isTaxMode();
$arResult['TAX_MODE'] = $bTaxMode ? 'Y' : 'N';

$arFields = null;
if ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmDeal::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}

	if ($bCopy)
	{
		if(isset($arFields['LEAD_ID']))
		{
			unset($arFields['LEAD_ID']);
		}

		if(isset($arFields['~LEAD_ID']))
		{
			unset($arFields['~LEAD_ID']);
		}

		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'DEAL', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
	}

	if(is_array($arFields))
	{
		//HACK: MSSQL returns '.00' for zero value
		if(isset($arFields['~OPPORTUNITY']))
		{
			$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = floatval($arFields['~OPPORTUNITY']);
		}

		if(isset($arFields['~OPPORTUNITY_ACCOUNT']))
		{
			$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = floatval($arFields['~OPPORTUNITY_ACCOUNT']);
		}
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);

	$beginDate = time() + CTimeZone::GetOffset();
	$time = localtime($beginDate, true);
	$beginDate -= $time['tm_sec'];

	$arFields['BEGINDATE'] = ConvertTimeStamp($beginDate, 'FULL', SITE_ID);
	$arFields['CLOSEDATE'] = ConvertTimeStamp($beginDate + 7 * 86400, 'FULL', SITE_ID);

	$extVals =  isset($arParams['~VALUES']) && is_array($arParams['~VALUES']) ? $arParams['~VALUES'] : array();
	if (count($extVals) > 0)
	{
		if(isset($extVals['PRODUCT_ROWS']) && is_array($extVals['PRODUCT_ROWS']))
		{
			$arResult['PRODUCT_ROWS'] = $extVals['PRODUCT_ROWS'];
			unset($extVals['PRODUCT_ROWS']);
		}

		$arFields = array_merge($arFields, $extVals);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmDeal::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $extVals;
	}

	if (isset($_GET['contact_id']))
	{
		$arFields['CONTACT_ID'] = intval($_GET['contact_id']);
	}
	if (isset($_GET['company_id']))
	{
		$arFields['COMPANY_ID'] = intval($_GET['company_id']);
	}
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		CUtil::decodeURIComponent($arFields['~TITLE']);
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}
}

$isExternal = $bEdit && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$arResult['ELEMENT'] = is_array($arFields) ? $arFields : null;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_DEAL_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12';

$productDataFieldName = 'DEAL_PRODUCT_DATA';

if($bConvert)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		if (intval($_POST["SYNC_ORDER_ID"]) > 0)
		{
			$_POST['apply'] = $_REQUEST['apply'] = "Y";
			if (!isset($_POST['TITLE']) || empty($_POST['TITLE']))
			{
				$_REQUEST['TITLE'] = $_POST['TITLE'] = GetMessage('CRM_DEFAULT_TITLE');
			}
		}

		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
		{
			$arSrcElement = ($bEdit || $bCopy) ? $arResult['ELEMENT'] : array();
			$arFields = array();

			$title = isset($_POST['TITLE']) ? trim($_POST['TITLE']) : '';
			if($title !== '')
			{
				$arFields['TITLE'] = $title;
			}
			elseif(!$bEdit)
			{
				$arFields['TITLE'] = GetMessage('CRM_DEAL_EDIT_DEFAULT_TITLE');
			}
			elseif(isset($arSrcElement['~TITLE']))
			{
				$arFields['TITLE'] = $arSrcElement['~TITLE'];
			}

			if(isset($_POST['COMMENTS']))
			{
				$comments = isset($_POST['COMMENTS']) ? trim($_POST['COMMENTS']) : '';
				if($comments !== '' && strpos($comments, '<') !== false)
				{
					$sanitizer = new CBXSanitizer();
					$sanitizer->ApplyDoubleEncode(false);
					$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
					//Crutch for for Chrome line break behaviour in HTML editor.
					$sanitizer->AddTags(array('div' => array()));
					$comments = $sanitizer->SanitizeHtml($comments);
				}
				$arFields['COMMENTS'] = $comments;
			}

			if(isset($_POST['PROBABILITY']))
			{
				$arFields['PROBABILITY'] = (int)$_POST['PROBABILITY'];
			}
			elseif(isset($arSrcElement['PROBABILITY']))
			{
				$arFields['PROBABILITY'] = (int)$arSrcElement['PROBABILITY'];
			}

			if(isset($_POST['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = trim($_POST['TYPE_ID']);
			}
			elseif(isset($arSrcElement['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = $arSrcElement['TYPE_ID'];
			}

			if(isset($_POST['STAGE_ID']))
			{
				$arFields['STAGE_ID'] = trim($_POST['STAGE_ID']);
			}
			elseif(isset($arSrcElement['STAGE_ID']))
			{
				$arFields['STAGE_ID'] = $arSrcElement['STAGE_ID'];
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
			}
			elseif(isset($arSrcElement['OPENED']))
			{
				$arFields['OPENED'] = $arSrcElement['OPENED'];
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}
			elseif(isset($arSrcElement['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = $arSrcElement['ASSIGNED_BY_ID'];
			}

			if ($bTaxMode)
			{
				$arFields['LOCATION_ID'] = $_POST['LOC_CITY'];
			}

			if(isset($_POST['BEGINDATE']))
			{
				$arFields['BEGINDATE'] = trim($_POST['BEGINDATE']);
			}
			elseif(isset($arSrcElement['BEGINDATE']))
			{
				$arFields['BEGINDATE'] = $arSrcElement['BEGINDATE'];
			}

			if(isset($_POST['CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = trim($_POST['CLOSEDATE']);
			}
			elseif(isset($arSrcElement['CLOSEDATE']))
			{
				$arFields['CLOSEDATE'] = $arSrcElement['CLOSEDATE'];
			}

			if(isset($_POST['CLOSED']))
			{
				$arFields['CLOSED'] = $_POST['CLOSED'] == 'Y' ? 'Y' : 'N';
			}
			elseif(isset($arSrcElement['CLOSED']))
			{
				$arFields['CLOSED'] = $arSrcElement['CLOSED'];
			}

			if(isset($_POST['OPPORTUNITY']))
			{
				$arFields['OPPORTUNITY'] = trim($_POST['OPPORTUNITY']);
			}
			elseif(isset($arSrcElement['OPPORTUNITY']))
			{
				$arFields['OPPORTUNITY'] = $arSrcElement['OPPORTUNITY'];
			}

			if(isset($_POST['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $_POST['CURRENCY_ID'];
			}
			elseif(isset($arSrcElement['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = $arSrcElement['CURRENCY_ID'];
			}

			$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
			if(!($currencyID !== '' && CCrmCurrency::IsExists($currencyID)))
			{
				$currencyID = $arFields['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			}
			$arFields['EXCH_RATE'] = CCrmCurrency::GetExchangeRate($currencyID);

			if(isset($_POST['CONTACT_ID']))
			{
				$contactID = intval($_POST['CONTACT_ID']);
				if($contactID <= 0)
				{
					$arFields['CONTACT_ID'] = 0;
				}
				elseif(CCrmContact::CheckReadPermission($contactID))
				{
					$arFields['CONTACT_ID'] = $contactID;
				}
				elseif(isset($arSrcElement['CONTACT_ID']))
				{
					$arFields['CONTACT_ID'] = $arSrcElement['CONTACT_ID'];
				}
			}
			elseif(isset($arSrcElement['CONTACT_ID']))
			{
				$arFields['CONTACT_ID'] = $arSrcElement['CONTACT_ID'];
			}

			if(isset($_POST['COMPANY_ID']))
			{
				$companyID = intval($_POST['COMPANY_ID']);
				if($companyID <= 0)
				{
					$arFields['COMPANY_ID'] = 0;
				}
				elseif(CCrmCompany::CheckReadPermission($companyID))
				{
					$arFields['COMPANY_ID'] = $companyID;
				}
				elseif(isset($arSrcElement['COMPANY_ID']))
				{
					$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
				}
			}
			elseif(isset($arSrcElement['COMPANY_ID']))
			{
				$arFields['COMPANY_ID'] = $arSrcElement['COMPANY_ID'];
			}


			$processProductRows = array_key_exists($productDataFieldName, $_POST);
			$arProd = array();
			if($processProductRows)
			{
				$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
				$arProd = $arResult['PRODUCT_ROWS'] = strlen($prodJson) > 0 ? CUtil::JsObjectToPhp($prodJson) : array();

				if(count($arProd) > 0)
				{
					// SYNC OPPORTUNITY WITH PRODUCT ROW SUM TOTAL
					$result = CCrmProductRow::CalculateTotalInfo('D', 0, false, $arFields, $arProd);
					$arFields['OPPORTUNITY'] = isset($result['OPPORTUNITY']) ? $result['OPPORTUNITY'] : 1.0;
					$arFields['TAX_VALUE'] = isset($result['TAX_VALUE']) ? $result['TAX_VALUE'] : 0.0;
				}
			}

			// Product row settings
			$productRowSettings = array();
			$productRowSettingsFieldName = $productDataFieldName.'_SETTINGS';
			if(array_key_exists($productRowSettingsFieldName, $_POST))
			{
				$settingsJson = isset($_POST[$productRowSettingsFieldName]) ? strval($_POST[$productRowSettingsFieldName]) : '';
				$arSettings = strlen($settingsJson) > 0 ? CUtil::JsObjectToPhp($settingsJson) : array();
				if(is_array($arSettings))
				{
					$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
					$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
				}
			}
			unset($productRowSettingsFieldName, $settingsJson, $arSettings);

			if (!$bEdit)
			{
				$originatorId = intval($_POST["EXTERNAL_SALE_ID"]);
				$originId = intval($_POST["SYNC_ORDER_ID"]);
			}
			else
			{
				$originatorId = intval($arResult['ELEMENT']["ORIGINATOR_ID"]);
				$originId = intval($arResult['ELEMENT']["ORIGIN_ID"]);
			}

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmDeal::$sUFEntityID, $arFields);
			$arResult['ERROR_MESSAGE'] = '';

			if (!$CCrmDeal->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmDeal->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmDeal->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? array($arResult['ELEMENT']['ID'] => $arEntityAttr[$arResult['ELEMENT']['ID']]) : null)) === false)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmDeal->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true));
				}
				else
				{
					if ($originatorId > 0 && $originId > 0)
					{
						$arFields['ORIGINATOR_ID'] = $originatorId;
						$arFields['ORIGIN_ID'] = $originId;
					}

					$ID = $CCrmDeal->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					$bSuccess = $ID !== false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if ($bSuccess)
				{
					// Save settings
					if(is_array($productRowSettings) && count($productRowSettings) > 0)
					{
						$arSettings = CCrmProductRow::LoadSettings('D', $arResult['ELEMENT']['ID']);
						foreach ($productRowSettings as $k => $v)
							$arSettings[$k] = $v;
						CCrmProductRow::SaveSettings('D', $arResult['ELEMENT']['ID'], $arSettings);
					}
					unset($arSettings);
				}

				if($bSuccess
					&& !$isExternal // Product rows of external deal are read only
					&& $processProductRows
					&& ($bEdit || !empty($arProd)))
				{
					// Suppress owner synchronization
					$bSuccess = $CCrmDeal::SaveProductRows($arResult['ELEMENT']['ID'], $arProd, true, true, false);
					if(!$bSuccess)
					{
						$arResult['ERROR_MESSAGE'] = GetMessage('PRODUCT_ROWS_SAVING_ERROR');
					}
				}

				if($bSuccess)
				{
					if($arFields['CONTACT_ID'] > 0
						&& $arFields['COMPANY_ID'] > 0
						&& isset($_POST['NEW_CONTACT_ID'])
						&& $arFields['CONTACT_ID'] == $_POST['NEW_CONTACT_ID'])
					{
						$CrmContact = new CCrmContact();
						$arContactFields = array(
							'COMPANY_ID' => $arFields['COMPANY_ID']
						);

						$bSuccess = $CrmContact->Update(
							$arFields['CONTACT_ID'],
							$arContactFields,
							false,
							true,
							array('DISABLE_USER_FIELD_CHECK' => true)
						);

						if(!$bSuccess)
						{
							$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
						}
					}
				}

				if($bSuccess)
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
					$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
				}
			}

			if (intval($_POST['SYNC_ORDER_ID']) > 0)
			{
				$imp = new CCrmExternalSaleImport($originatorId);
				if ($imp->IsInitialized())
				{
					$r = $imp->GetOrderData($originId, true);
					if ($r == CCrmExternalSaleImport::SyncStatusError)
					{
						$arErrors = $imp->GetErrors();
						foreach ($arErrors as $err)
						{
							$arResult['ERROR_MESSAGE'] .= $err[1].'<br />';
						}
					}
				}
			}

			if (empty($arResult['ERROR_MESSAGE']))
			{
				if (!$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
					$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
			}

			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					CCrmDeal::GetFields()
				);
			}
			else
			{
				if (intval($_POST['SYNC_ORDER_ID']) > 0)
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_DEAL_SHOW'],
							array('deal_id' => $ID)
						)
					);
				}

				if (isset($_POST['apply']))
				{
					if (CCrmDeal::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_DEAL_EDIT'],
								array('deal_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_DEAL_EDIT'],
							array('deal_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmDeal::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_DEAL_SHOW'],
								array('deal_id' => $ID)
							)
						);
					}
				}

				// save
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
			}
		}
	}
	elseif (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
			if ($bDeleteError)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmDeal->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST']));
			else
				ShowError($arResult['ERROR_MESSAGE']);
			return;
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}

$arResult['BACK_URL'] = $arParams['PATH_TO_DEAL_LIST'];
$arResult['STAGE_LIST'] = array();
$arResult['~STAGE_LIST'] = CCrmStatus::GetStatusList('DEAL_STAGE');
foreach ($arResult['~STAGE_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($userPermissions->GetPermType('DEAL', $bEdit ? 'WRITE' : 'ADD', array('STAGE_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STAGE_LIST'][$sStatusId] = $sStatusTitle;
}
$arResult['STATE_LIST'] = CCrmStatus::GetStatusList('DEAL_STATE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('DEAL_TYPE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['EVENT_LIST'] = CCrmStatus::GetStatusList('EVENT_TYPE');
$arResult['EDIT'] = $bEdit;

$arResult['FIELDS'] = array();

$APPLICATION->AddHeadScript($this->GetPath().'/sale.js');

if (!$bEdit)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array("NAME" => "ASC", "SERVER" => "ASC"),
		array("ACTIVE" => "Y")
	);
	$arSalesList = array();
	while ($arSale = $dbSalesList->GetNext())
		$arSalesList[$arSale["ID"]] = ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);

	$salesListCount = count($arSalesList);
	if ($salesListCount > 0)
	{
		$strCreateOrderHtml  = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>'.
			'<input type="hidden" name="SYNC_ORDER_ID" id="ID_SYNC_ORDER_ID" value="" />'.
			'<input type="hidden" name="SYNC_ORDER_FORM_NAME" id="ID_SYNC_ORDER_FORM_NAME" value="form_'.htmlspecialcharsbx($arResult['FORM_ID']).'" />';
		$strCreateOrderHtml .= '<script type="text/javascript">'.
			'function DoChangeExternalSaleId(val)'.
			'{'.
			'	var frm = document.forms[document.getElementById("ID_SYNC_ORDER_FORM_NAME").value];'.
			'	if (frm)'.
			'	{'.
			'		var l = frm.getElementsByTagName(\'*\');'.
			'		for (var i in l)'.
			'		{'.
			'			var el = l[i];'.
			'			if (el && el.type && (el.getAttribute("sale_order_marker") != null || el.type == "submit"))'.
			'				el.disabled = val;'.
			'		}'.
			'	}'.
			'	var contactSelectorId = "'.CUtil::JSEscape($arResult['FORM_ID']).'_CONTACT_ID";'.
			'	var companySelectorId = "'.CUtil::JSEscape($arResult['FORM_ID']).'_COMPANY_ID";'.
			'	if(typeof(BX.CrmEntityEditor.items[contactSelectorId]) !== "undefined")'.
			'		BX.CrmEntityEditor.items[contactSelectorId].setReadOnly(val);'.
			'	if(typeof(BX.CrmEntityEditor.items[companySelectorId]) !== "undefined")'.
			'		BX.CrmEntityEditor.items[companySelectorId].setReadOnly(val);'.
			'	var b = document.getElementById("ID_EXTERNAL_SALE_CREATE_BTN1");'.
			'	if (b)'.
			'		b.style.display = (val ? "" : "none");'.
			'	BX.CrmProductEditor.getDefault().setReadOnly(val);'.
			'}'.
			'</script>';
		$strCreateOrderHtml .= '<input type="checkbox" name="DO_USE_EXTERNAL_SALE" id="ID_DO_USE_EXTERNAL_SALE" value="Y" onclick="DoChangeExternalSaleId(this.checked)">';

		$strCreateOrderHtmlSelect = '';
		$strCreateOrderHtmlAction = '';

		if ($salesListCount == 1)
		{
			$arKeys = array_keys($arSalesList);
			$strCreateOrderHtmlSelect .= '<input type="hidden" name="EXTERNAL_SALE_ID" id="ID_EXTERNAL_SALE_ID" value="'.$arKeys[0].'" />';
			$strCreateOrderHtmlAction .= "document.getElementById('ID_EXTERNAL_SALE_ID').value";
		}
		elseif ($salesListCount > 1)
		{
			$strCreateOrderHtmlSelect .= '<select name="EXTERNAL_SALE_ID" id="ID_EXTERNAL_SALE_ID">';
			foreach ($arSalesList as $key => $val)
				$strCreateOrderHtmlSelect .= '<option value="'.$key.'">'.$val.'</option>';
			$strCreateOrderHtmlSelect .= '</select> ';
			$strCreateOrderHtmlAction .= "document.getElementById('ID_EXTERNAL_SALE_ID').options[document.getElementById('ID_EXTERNAL_SALE_ID').selectedIndex].value";
		}

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'SALE_ORDER',
			'name' => GetMessage('CRM_FIELD_SALE_ORDER'),
			'type' => 'custom',
			'value' => $strCreateOrderHtml,
			'persistent' => true
		);
	}
}
else
{
	if (intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0)
	{
		$strEditOrderHtml = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>'.
			'<input type="hidden" name="SYNC_ORDER_ID" id="ID_SYNC_ORDER_ID" value="" />'.
			'<input type="hidden" name="SYNC_ORDER_FORM_NAME" id="ID_SYNC_ORDER_FORM_NAME" value="form_'.htmlspecialcharsbx($arResult['FORM_ID']).'" />';

		$dbSalesList = CCrmExternalSale::GetList(
			array("NAME" => "ASC", "SERVER" => "ASC"),
			array("ID" => $arResult['ELEMENT']['ORIGINATOR_ID'])
		);
		if ($arSale = $dbSalesList->GetNext())
			$strEditOrderHtml .= ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'SALE_ORDER',
			'name' => GetMessage('CRM_FIELD_SALE_ORDER1'),
			'type' => 'custom',
			'value' => $strEditOrderHtml,
			'persistent' => true
		);
	}
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_deal_info',
	'name' => GetMessage('CRM_SECTION_DEAL_INFO'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'items' => $arResult['STAGE_LIST'],
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['STAGE_ID']) ? $arResult['ELEMENT']['STAGE_ID'] : '')
);

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

$currencyFld = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID')
);
if(!$isExternal)
{
	$currencyFld['type'] = 'list';
	$currencyFld['params'] = array('sale_order_marker' => 'Y');
	$currencyFld['items'] = $arResult['CURRENCY_LIST'];
	$currencyFld['value'] = $currencyID;
}
else
{
	$currencyFld['type'] = 'label';
	$currencyFld['params'] = array('size' => 50);
	$currencyFld['value'] = isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID;
}
$arResult['FIELDS']['tab_1'][] = &$currencyFld;

$opportunityFld = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21, 'sale_order_marker' => 'Y'),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);
if(!$isExternal)
{
	$opportunityFld['type'] = 'text';
}
else
{
	$opportunityFld['type'] = 'label';
}
$arResult['FIELDS']['tab_1'][] = &$opportunityFld;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'params' => array('size' => 3, 'maxlength' => '3'),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['PROBABILITY']) ? (string)(double)$arResult['ELEMENT']['PROBABILITY'] : ''
);
$arResult['RESPONSIBLE_SELECTOR_PARAMS'] = array(
	'NAME' => 'crm_deal_edit_resonsible',
	'INPUT_NAME' => 'ASSIGNED_BY_ID',
	'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => $arResult['RESPONSIBLE_SELECTOR_PARAMS'],
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

//Fix for issue #36848
$beginDate = isset($arResult['ELEMENT']['BEGINDATE']) ? $arResult['ELEMENT']['BEGINDATE'] : '';
$closeDate = isset($arResult['ELEMENT']['CLOSEDATE']) ? $arResult['ELEMENT']['CLOSEDATE'] : $beginDate;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'date_link',
	'value' => $beginDate !== '' ? ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID) : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_CLOSEDATE2'),
	'type' => 'date_link',
	'value' => $closeDate !== '' ? ConvertTimeStamp(MakeTimeStamp($closeDate), 'SHORT', SITE_ID) : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '')
);

/* Field 'CLOSED' was removed from user editable fields
 * $arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_FIELD_CLOSED'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'checkbox',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? $arResult['ELEMENT']['CLOSED'] : 'N')
);*/

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED']) ? $arResult['ELEMENT']['OPENED'] : true,
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);
if (CCrmContact::CheckReadPermission())
{
	if(!$isExternal)
	{
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'CONTACT_ID',
			'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
			'type' => 'crm_entity_selector',
			'componentParams' => array(
				'ENTITY_TYPE' => 'CONTACT',
				'INPUT_NAME' => 'CONTACT_ID',
				'NEW_INPUT_NAME' => CCrmContact::CheckCreatePermission() ? 'NEW_CONTACT_ID' : '',
				'INPUT_VALUE' => isset($arResult['ELEMENT']['CONTACT_ID']) ? $arResult['ELEMENT']['CONTACT_ID'] : '',
				'FORM_NAME' => $arResult['FORM_ID'],
				'MULTIPLE' => 'N',
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)
		);
	}
	elseif(isset($arResult['ELEMENT']['CONTACT_ID']))
	{
		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
			array('contact_id' => $arResult['ELEMENT']['CONTACT_ID'])
		);

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'CONTACT_ID',
			'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
			'params' => array('size' => 50),
			'type' => 'custom',
			'value' => isset($arResult['ELEMENT']['CONTACT_FULL_NAME']) ?
				'<a href="'.htmlspecialcharsbx($contactShowUrl).'" id="balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['ID'].'">'.$arResult['ELEMENT']['CONTACT_FULL_NAME'].'</a>'.
					'<script type="text/javascript">BX.tooltip("CONTACT_'.$arResult['ELEMENT']['~CONTACT_ID'].'", "balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['ID'].'", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>'
				: ''
		);
	}
}
if (CCrmCompany::CheckReadPermission())
{
	if(!$isExternal)
	{
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'COMPANY_ID',
			'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
			'type' => 'crm_entity_selector',
			'componentParams' => array(
				'ENTITY_TYPE' => 'COMPANY',
				'INPUT_NAME' => 'COMPANY_ID',
				'NEW_INPUT_NAME' => CCrmCompany::CheckCreatePermission() ? 'NEW_COMPANY_ID' : '',
				'INPUT_VALUE' => isset($arResult['ELEMENT']['COMPANY_ID']) ? $arResult['ELEMENT']['COMPANY_ID'] : '',
				'FORM_NAME' => $arResult['FORM_ID'],
				'MULTIPLE' => 'N',
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)
		);
	}
	elseif(isset($arResult['ELEMENT']['COMPANY_ID']))
	{
		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
			array('company_id' => $arResult['ELEMENT']['COMPANY_ID'])
		);

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'COMPANY_ID',
			'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
			'params' => array('size' => 50),
			'type' => 'custom',
			'value' => isset($arResult['ELEMENT']['COMPANY_TITLE']) ?
				'<a href="'.htmlspecialcharsbx($companyShowUrl).'" id="balloon_'.$arResult['GRID_ID'].'_'.$arResult['ELEMENT']['ID'].'">'.$arResult['ELEMENT']['COMPANY_TITLE'].'</a>'.
					'<script type="text/javascript">BX.tooltip("COMPANY_'.$arResult['ELEMENT']['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_'.$arResult['ELEMENT']['ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
				: ''
		);
	}
}
if ($bTaxMode)
{
	// CLIENT LOCATION
	$sLocationHtml = '';
	ob_start();

	$locValue = isset($arResult['ELEMENT']['LOCATION_ID']) ? $arResult['ELEMENT']['LOCATION_ID'] : '';
	CSaleLocation::proxySaleAjaxLocationsComponent(
		array(
			'AJAX_CALL' => 'N',
			'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
			'REGION_INPUT_NAME' => 'LOC_REGION',
			'CITY_INPUT_NAME' => 'LOC_CITY',
			'CITY_OUT_LOCATION' => 'Y',
			'LOCATION_VALUE' => $locValue,
			'ORDER_PROPS_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
			'ONCITYCHANGE' => 'BX.onCustomEvent(\'CrmProductRowSetLocation\', [\'LOC_CITY\']);',
			'SHOW_QUICK_CHOOSE' => 'N'/*,
			'SIZE1' => $arProperties['SIZE1']*/
		),
		array(
			"CODE" => $locValue,
			"ID" => "",
			"PROVIDE_LINK_BY" => "code",
			"JS_CALLBACK" => 'CrmProductRowSetLocation'
		),
		'popup'
	);
	$sLocationHtml = ob_get_contents();
	ob_end_clean();
	$locationField = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_DEAL_FIELD_LOCATION_ID'),
		'type' => 'custom',
		'value' =>  $sLocationHtml.
			'<div>
				<span class="bx-crm-edit-content-block-element-name">&nbsp;</span>'.
			'<span class="bx-crm-edit-content-location-description">'.
			GetMessage('CRM_DEAL_FIELD_LOCATION_ID_DESCRIPTION').
			'</span>'.
			'</div>',
		'required' => true
	);
	$arResult['FIELDS']['tab_1'][] = $locationField;
	$arResult['FORM_FIELDS_TO_ADD']['LOCATION_ID'] = $locationField;
	unset($locationField);
}
ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '180',
	'content' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

// PRODUCT_ROWS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '';

if ($bEdit)
{
	if (intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0)
	{
		$sProductsHtml .= '<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_EDIT").'</span>'.
			'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_VIEW").'</span>'.
			'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_PRINT").'</span><br/><br/>';
	}
}
else
{
	if ($salesListCount > 0)
		$sProductsHtml .= '<div id="ID_EXTERNAL_SALE_CREATE_BTN1" style="display:none;">'.$strCreateOrderHtmlSelect.'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$strCreateOrderHtmlAction.', \'CREATE\')">'.GetMessage("CRM_EXT_SALE_CD_CREATE1").'</span></div>';
}

// Determine person type
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
$personTypeId = 0;
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if (intval($arResult['ELEMENT']['COMPANY_ID']) > 0)
		$personTypeId = $arPersonTypes['COMPANY'];
	elseif (intval($arResult['ELEMENT']['CONTACT_ID']) > 0)
		$personTypeId = $arPersonTypes['CONTACT'];
}

$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'deal_'.strval($arParams['ELEMENT_ID']) : 'new_deal').'_product_editor';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'D',
	'PERMISSION_TYPE' => $isExternal ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => $isExternal ? 'N' : 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => ($bTaxMode && isset($arResult['ELEMENT']['LOCATION_ID'])) ? $arResult['ELEMENT']['LOCATION_ID'] : '',
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
);
if (isset($arParams['ENABLE_DISCOUNT']))
	$componentSettings['ENABLE_DISCOUNT'] = ($arParams['ENABLE_DISCOUNT'] === 'Y') ? 'Y' : 'N';
if (isset($arParams['ENABLE_TAX']))
	$componentSettings['ENABLE_TAX'] = ($arParams['ENABLE_TAX'] === 'Y') ? 'Y' : 'N';
if (is_array($productRowSettings) && count($productRowSettings) > 0)
{
	if (isset($productRowSettings['ENABLE_DISCOUNT']))
		$componentSettings['ENABLE_DISCOUNT'] = $productRowSettings['ENABLE_DISCOUNT'] ? 'Y' : 'N';
	if (isset($productRowSettings['ENABLE_TAX']))
		$componentSettings['ENABLE_TAX'] = $productRowSettings['ENABLE_TAX'] ? 'Y' : 'N';
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
	'',
	$componentSettings,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sProductsHtml .= ob_get_contents();
ob_end_clean();
unset($componentSettings);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$icnt = count($arResult['FIELDS']['tab_1']);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bConvert ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm,
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (count($arResult['FIELDS']['tab_1']) == $icnt)
	unset($arResult['FIELDS']['tab_1'][$icnt - 1]);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'DEAL');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
			$bEdit ? array('crm', 'CCrmDocumentDeal', 'DEAL_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentDeal', $bEdit ? 'DEAL_'.$arResult['ELEMENT']['ID'] : 'DEAL_0'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowId' => $arDocumentState['ID'] > 0 ? $arDocumentState['ID'] : $arDocumentState['TEMPLATE_ID'],
				'CreatedBy' => $arResult['ELEMENT']['ASSIGNED_BY'],
				'UserIsAdmin' => $USER->IsAdmin()
			)
		);

		if (!$canViewWorkflow)
			continue;

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'section_bp_name_'.$bizProcIndex,
			'name' => $arDocumentState['TEMPLATE_NAME'],
			'type' => 'section'
		);
		if ($arDocumentState['TEMPLATE_DESCRIPTION'] != '')
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_DESC_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_TEMPLATE_DESC'),
				'type' => 'label',
				'colspan' => true,
				'value' => $arDocumentState['TEMPLATE_DESCRIPTION']
			);
		}
		if (!empty($arDocumentState['STATE_MODIFIED']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_MODIFIED_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_MODIFIED'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_MODIFIED']
			);
		}
		if (!empty($arDocumentState['STATE_NAME']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_NAME_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_NAME'),
				'type' => 'label',
				'value' => strlen($arDocumentState['STATE_TITLE']) > 0 ? $arDocumentState['STATE_TITLE'] : $arDocumentState['STATE_NAME']
			);
		}
		if (strlen($arDocumentState['ID']) <= 0)
		{
			ob_start();
			CBPDocument::StartWorkflowParametersShow(
				$arDocumentState['TEMPLATE_ID'],
				$arDocumentState['TEMPLATE_PARAMETERS'],
				'form_'.$arResult['FORM_ID'],
				$bVarsFromForm
			);
			$sVal = ob_get_contents();
			ob_end_clean();

			if($sVal !== '')
			{
				$arResult['FIELDS']['tab_1'][] = array(
					'id' => 'BP_PARAMETERS',
					'name' => GetMessage('CRM_FIELD_BP_PARAMETERS'),
					'colspan' => true,
					'type' => 'custom',
					'value' => "<table>$sVal</table>"
				);
			}
		}

		$_arEvents = CBPDocument::GetAllowableEvents($USER->GetID(), $CCrmBizProc->arCurrentUserGroups, $arDocumentState);
		if (count($_arEvents) > 0)
		{
			$arEvent = array('' => GetMessage('CRM_FIELD_BP_EMPTY_EVENT'));
			foreach ($_arEvents as $_arEvent)
				$arEvent[$_arEvent['NAME']] = $_arEvent['TITLE'];

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_EVENTS_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_EVENTS'),
				'params' => array(),
				'items' => $arEvent,
				'type' => 'list',
				'value' => (isset($_REQUEST['bizproc_event_'.$bizProcIndex]) ? $_REQUEST['bizproc_event_'.$bizProcIndex] : '')
			);

			$arResult['FORM_CUSTOM_HTML'] = '
					<input type="hidden" name="bizproc_id_'.$bizProcIndex.'" value="'.$arDocumentState["ID"].'">
					<input type="hidden" name="bizproc_template_id_'.$bizProcIndex.'" value="'.$arDocumentState["TEMPLATE_ID"].'">
			';
		}

	}

	if ($bizProcIndex > 0)
		$arResult['BIZPROC'] = true;
}

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
?>
