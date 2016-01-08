<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

global $USER_FIELD_MANAGER, $DB, $USER;
$CCrmCompany = new CCrmCompany();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$CCrmBizProc = new CCrmBizProc('COMPANY');
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
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
	? CCrmCompany::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions)
	: CCrmCompany::CheckCreatePermission($userPermissions);

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('COMPANY', array($arParams['ELEMENT_ID']))
	: array();

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;


if ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmCompany::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}

	if ($bCopy)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['LOGO']);
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);

	if (isset($arParams['~VALUES']) && is_array($arParams['~VALUES']))
	{
		$arFields = array_merge($arFields, $arParams['~VALUES']);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmCompany::GetFields()
		);

		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['contact_id']))
	{
		$arResult['CONTACT_ID'] = array(intval($_GET['contact_id']));
	}
	if (isset($_GET['title']))
	{
		$arFields['~TITLE'] = $_GET['title'];
		CUtil::decodeURIComponent($arFields['~TITLE']);
		$arFields['TITLE'] = htmlspecialcharsbx($arFields['~TITLE']);
	}
	if (isset($_GET['address']))
	{
		$arFields['~ADDRESS'] = $_GET['address'];
		CUtil::decodeURIComponent($arFields['~ADDRESS']);
		$arFields['ADDRESS'] = htmlspecialcharsbx($arFields['~ADDRESS']);
	}
	if (isset($_GET['address_2']))
	{
		$arFields['~ADDRESS_2'] = $_GET['address_2'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_2']);
		$arFields['ADDRESS_2'] = htmlspecialcharsbx($arFields['~ADDRESS_2']);
	}
	if (isset($_GET['address_city']))
	{
		$arFields['~ADDRESS_CITY'] = $_GET['address_city'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_CITY']);
		$arFields['ADDRESS_CITY'] = htmlspecialcharsbx($arFields['~ADDRESS_CITY']);
	}
	if (isset($_GET['address_postal_code']))
	{
		$arFields['~ADDRESS_POSTAL_CODE'] = $_GET['address_postal_code'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_POSTAL_CODE']);
		$arFields['ADDRESS_POSTAL_CODE'] = htmlspecialcharsbx($arFields['~ADDRESS_POSTAL_CODE']);
	}
	if (isset($_GET['address_region']))
	{
		$arFields['~ADDRESS_REGION'] = $_GET['address_region'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_REGION']);
		$arFields['ADDRESS_REGION'] = htmlspecialcharsbx($arFields['~ADDRESS_REGION']);
	}
	if (isset($_GET['address_province']))
	{
		$arFields['~ADDRESS_PROVINCE'] = $_GET['address_province'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_PROVINCE']);
		$arFields['ADDRESS_PROVINCE'] = htmlspecialcharsbx($arFields['~ADDRESS_PROVINCE']);
	}
	if (isset($_GET['address_country']))
	{
		$arFields['~ADDRESS_COUNTRY'] = $_GET['address_country'];
		CUtil::decodeURIComponent($arFields['~ADDRESS_COUNTRY']);
		$arFields['ADDRESS_COUNTRY'] = htmlspecialcharsbx($arFields['~ADDRESS_COUNTRY']);
	}
	if (isset($_GET['email']) || isset($_GET['phone']) || isset($_GET['tel']))
	{
		if(isset($_GET['email']))
		{
			$email = $_GET['email'];
			CUtil::decodeURIComponent($email);
			trim($email);
		}
		else
		{
			$email = '';
		}

		if(isset($_GET['phone']) || isset($_GET['tel']))
		{
			$phone = isset($_GET['phone']) ? $_GET['phone'] : $_GET['tel'];
			CUtil::decodeURIComponent($phone);
			trim($phone);
		}
		else
		{
			$phone = '';
		}

		$arFields['FM'] = array();
		if($email !== '')
		{
			$arFields['FM']['EMAIL'] = array(
				'n0' => array('VALUE' => $email, 'VALUE_TYPE' => 'WORK')
			);
		}
		if($phone !== '')
		{
			$arFields['FM']['PHONE'] = array(
				'n0' => array('VALUE' => $phone, 'VALUE_TYPE' => 'WORK'));
		}
	}
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

if($bConvert)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
		{
			$arFields = array('TITLE' => trim($_POST['TITLE']));

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

			if(isset($_POST['ADDRESS']))
			{
				$arFields['ADDRESS'] = trim($_POST['ADDRESS']);
			}

			if(isset($_POST['ADDRESS_2']))
			{
				$arFields['ADDRESS_2'] = trim($_POST['ADDRESS_2']);
			}

			if(isset($_POST['ADDRESS_CITY']))
			{
				$arFields['ADDRESS_CITY'] = trim($_POST['ADDRESS_CITY']);
			}

			if(isset($_POST['ADDRESS_POSTAL_CODE']))
			{
				$arFields['ADDRESS_POSTAL_CODE'] = trim($_POST['ADDRESS_POSTAL_CODE']);
			}

			if(isset($_POST['ADDRESS_REGION']))
			{
				$arFields['ADDRESS_REGION'] = trim($_POST['ADDRESS_REGION']);
			}

			if(isset($_POST['ADDRESS_PROVINCE']))
			{
				$arFields['ADDRESS_PROVINCE'] = trim($_POST['ADDRESS_PROVINCE']);
			}

			if(isset($_POST['ADDRESS_COUNTRY']))
			{
				$arFields['ADDRESS_COUNTRY'] = trim($_POST['ADDRESS_COUNTRY']);
			}

			if(isset($_POST['ADDRESS_COUNTRY_CODE']))
			{
				$arFields['ADDRESS_COUNTRY_CODE'] = trim($_POST['ADDRESS_COUNTRY_CODE']);
			}

			if(isset($_POST['REG_ADDRESS']))
			{
				$arFields['REG_ADDRESS'] = trim($_POST['REG_ADDRESS']);
			}

			if(isset($_POST['REG_ADDRESS_2']))
			{
				$arFields['REG_ADDRESS_2'] = trim($_POST['REG_ADDRESS_2']);
			}

			if(isset($_POST['REG_ADDRESS_CITY']))
			{
				$arFields['REG_ADDRESS_CITY'] = trim($_POST['REG_ADDRESS_CITY']);
			}

			if(isset($_POST['REG_ADDRESS_POSTAL_CODE']))
			{
				$arFields['REG_ADDRESS_POSTAL_CODE'] = trim($_POST['REG_ADDRESS_POSTAL_CODE']);
			}

			if(isset($_POST['REG_ADDRESS_REGION']))
			{
				$arFields['REG_ADDRESS_REGION'] = trim($_POST['REG_ADDRESS_REGION']);
			}

			if(isset($_POST['REG_ADDRESS_PROVINCE']))
			{
				$arFields['REG_ADDRESS_PROVINCE'] = trim($_POST['REG_ADDRESS_PROVINCE']);
			}

			if(isset($_POST['REG_ADDRESS_COUNTRY']))
			{
				$arFields['REG_ADDRESS_COUNTRY'] = trim($_POST['REG_ADDRESS_COUNTRY']);
			}

			if(isset($_POST['REG_ADDRESS_COUNTRY_CODE']))
			{
				$arFields['REG_ADDRESS_COUNTRY_CODE'] = trim($_POST['REG_ADDRESS_COUNTRY_CODE']);
			}

			if(isset($_POST['BANKING_DETAILS']))
			{
				$arFields['BANKING_DETAILS'] = trim($_POST['BANKING_DETAILS']);
			}

			if(isset($_POST['COMPANY_TYPE']))
			{
				$arFields['COMPANY_TYPE'] = trim($_POST['COMPANY_TYPE']);
			}

			if(isset($_POST['INDUSTRY']))
			{
				$arFields['INDUSTRY'] = trim($_POST['INDUSTRY']);
			}

			if(isset($_POST['REVENUE']))
			{
				$arFields['REVENUE'] = trim($_POST['REVENUE']);
			}

			if(isset($_POST['CURRENCY_ID']))
			{
				$arFields['CURRENCY_ID'] = trim($_POST['CURRENCY_ID']);
			}

			if(isset($_POST['EMPLOYEES']))
			{
				$arFields['EMPLOYEES'] = trim($_POST['EMPLOYEES']);
			}

			if(isset($_FILES['LOGO']))
			{
				$arFields['LOGO'] = $_FILES['LOGO'];
			}

			if(isset($_POST['LOGO_del']))
			{
				$arFields['LOGO_del'] = $_POST['LOGO_del'];
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = strtoupper($_POST['OPENED']) === 'Y' ? 'Y' : 'N';
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}

			if(isset($_POST['COMFM']))
			{
				$arFields['FM'] = $_POST['COMFM'];
			}

			if(isset($_POST['CONTACT_ID']))
			{
				$contactIDs = is_array($_POST['CONTACT_ID']) ? $_POST['CONTACT_ID'] : array();
				foreach($contactIDs as $k => $v)
				{
					if(!CCrmContact::CheckReadPermission($v))
					{
						unset($contactIDs[$k]);
					}
				}
				$arFields['CONTACT_ID'] = $contactIDs;
			}

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmCompany::$sUFEntityID, $arFields);

			$originID = isset($_REQUEST['origin_id']) ? $_REQUEST['origin_id'] : '';
			if($originID !== '')
			{
				$arFields['ORIGIN_ID'] = $originID;
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmCompany->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmCompany->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmCompany->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? array($arResult['ELEMENT']['ID'] => $arEntityAttr[$arResult['ELEMENT']['ID']]) : null)) === false)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
				$DB->StartTransaction();
				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmCompany->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true));
				}
				else
				{
					$ID = $CCrmCompany->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					$bSuccess = $ID !== false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
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

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
			{
				$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
			}

			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					CCrmCompany::GetFields()
				);
			}
			else
			{
				if (isset($_POST['apply']))
				{
					if (CCrmCompany::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_EDIT'],
								array('company_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_COMPANY_EDIT'],
							array('company_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmCompany::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_SHOW'],
								array('company_id' => $ID)
							)
						);
					}
				}

				//save
				LocalRedirect(
					isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
						? $_REQUEST['backurl']
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array())
				);
			}
		}
	}
	else if (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!CCrmCompany::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
			if ($bDeleteError)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmCompany->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST']));
			else
			{
				ShowError($arResult['ERROR_MESSAGE']);
				return;
			}
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_COMPANY_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_COMPANY_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_COMPANY_LIST'];
$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');
$arResult['EDIT'] = $bEdit;
$arResult['IS_COPY'] = $bCopy;
$arResult['DUPLICATE_CONTROL'] = array();
$enableDupControl = $arResult['DUPLICATE_CONTROL']['ENABLED'] =
	!$bEdit && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Company);

// Fix for #26945. Suppress binding of contacts to new compnany. Contacts will be binded to source company.
if(!$bCopy)
{
	$dbRes = CCrmContact::GetContactByCompanyId($arResult['ELEMENT']['ID']);
	if(!isset($arResult['CONTACT_ID']))
	{
		$arResult['CONTACT_ID'] = array();
	}
	while($arContact = $dbRes->Fetch())
	{
		$arResult['CONTACT_ID'][] = $arContact['ID'];
	}
}

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_company_info',
	'name' => GetMessage('CRM_SECTION_COMPANY_INFO2'),
	'type' => 'section'
);

$titleID = $arResult['FORM_ID'].'_TITLE';
$titleCaptionID = $arResult['FORM_ID'].'_TITLE_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['TITLE_ID'] = $titleID;
	$arResult['DUPLICATE_CONTROL']['TITLE_CAPTION_ID'] = $titleCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'nameWrapper' => $titleCaptionID,
	'params' => array('id'=> $titleID, 'size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_company_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['~ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['~ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LOGO',
	'name' => GetMessage('CRM_FIELD_LOGO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['LOGO']) ? $arResult['ELEMENT']['LOGO'] : '',
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'type' => 'list',
	'items' => $arResult['COMPANY_TYPE_LIST'],
	'value' => isset($arResult['ELEMENT']['COMPANY_TYPE']) ? $arResult['ELEMENT']['COMPANY_TYPE'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INDUSTRY',
	'name' => GetMessage('CRM_FIELD_INDUSTRY'),
	'type' => 'list',
	'items' => $arResult['INDUSTRY_LIST'],
	'value' => isset($arResult['ELEMENT']['INDUSTRY']) ? $arResult['ELEMENT']['INDUSTRY'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_EMPLOYEES'),
	'type' => 'list',
	'items' => $arResult['EMPLOYEES_LIST'],
	'value' => isset($arResult['ELEMENT']['EMPLOYEES']) ? $arResult['ELEMENT']['EMPLOYEES'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REVENUE',
	'name' => GetMessage('CRM_FIELD_REVENUE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['REVENUE']) ? $arResult['ELEMENT']['REVENUE'] : '',
	'type' => 'text',
	'required' => false
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => 'list',
	'value' => isset($arResult['ELEMENT']['CURRENCY_ID']) ? $arResult['ELEMENT']['CURRENCY_ID'] : ''
);
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
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section'
);

$emailEditorID = uniqid('COMFM_EMAIL_');
$emailEditorCaptionID =$emailEditorID.'_CAPTION';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'] = $emailEditorID;
	$arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'] = $emailEditorCaptionID;
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'EDITOR_ID' => $emailEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMAIL',
	'name' => GetMessage('CRM_FIELD_EMAIL'),
	'nameWrapper' => $emailEditorCaptionID,
	'type' => 'custom',
	'value' => $sVal
);

$phoneEditorID = uniqid('COMFM_PHONE_');
$phoneEditorCaptionID =$phoneEditorID.'_CAPTION';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_ID'] = $phoneEditorID;
	$arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_CAPTION_ID'] = $phoneEditorCaptionID;
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'EDITOR_ID' => $phoneEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHONE',
	'name' => GetMessage('CRM_FIELD_PHONE'),
	'nameWrapper' => $phoneEditorCaptionID,
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'WEB',
	'name' => GetMessage('CRM_FIELD_WEB'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IM',
	'name' => GetMessage('CRM_FIELD_MESSENGER'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'address',
	'componentParams' => array(
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'DATA' => array(
			'ADDRESS' => array('NAME' => 'ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS']) ? $arResult['ELEMENT']['~ADDRESS'] : ''),
			'ADDRESS_2' => array('NAME' => 'ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_2']) ? $arResult['ELEMENT']['~ADDRESS_2'] : ''),
			'CITY' => array('NAME' => 'ADDRESS_CITY','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_CITY']) ? $arResult['ELEMENT']['~ADDRESS_CITY'] : ''),
			'REGION' => array('NAME' => 'ADDRESS_REGION','VALUE' => isset($arResult['ELEMENT']['~ADDRESS_REGION']) ? $arResult['ELEMENT']['~ADDRESS_REGION'] : ''),
			'PROVINCE' => array('NAME' => 'ADDRESS_PROVINCE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_PROVINCE']) ? $arResult['ELEMENT']['~ADDRESS_PROVINCE'] : ''),
			'POSTAL_CODE' => array('NAME' => 'ADDRESS_POSTAL_CODE', 'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_POSTAL_CODE']) ? $arResult['ELEMENT']['~ADDRESS_POSTAL_CODE'] : ''),
			'COUNTRY' => array(
				'NAME' => 'ADDRESS_COUNTRY',
				'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY'] : '',
				'LOCALITY' => array(
					'TYPE' => 'COUNTRY',
					'NAME' => 'ADDRESS_COUNTRY_CODE',
					'VALUE' => isset($arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE']) ? $arResult['ELEMENT']['~ADDRESS_COUNTRY_CODE'] : ''
				)
			)
		)
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS_LEGAL',
	'name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
	'type' => 'address',
	'componentParams' => array(
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'DATA' => array(
			'ADDRESS' => array('NAME' => 'REG_ADDRESS', 'IS_MULTILINE' => true, 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS']) ? $arResult['ELEMENT']['~REG_ADDRESS'] : ''),
			'ADDRESS_2' => array('NAME' => 'REG_ADDRESS_2', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_2']) ? $arResult['ELEMENT']['~REG_ADDRESS_2'] : ''),
			'CITY' => array('NAME' => 'REG_ADDRESS_CITY','VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_CITY']) ? $arResult['ELEMENT']['~REG_ADDRESS_CITY'] : ''),
			'REGION' => array('NAME' => 'REG_ADDRESS_REGION','VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_REGION']) ? $arResult['ELEMENT']['~REG_ADDRESS_REGION'] : ''),
			'PROVINCE' => array('NAME' => 'REG_ADDRESS_PROVINCE', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_PROVINCE']) ? $arResult['ELEMENT']['~REG_ADDRESS_PROVINCE'] : ''),
			'POSTAL_CODE' => array('NAME' => 'REG_ADDRESS_POSTAL_CODE', 'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_POSTAL_CODE']) ? $arResult['ELEMENT']['~REG_ADDRESS_POSTAL_CODE'] : ''),
			'COUNTRY' => array(
				'NAME' => 'REG_ADDRESS_COUNTRY',
				'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_COUNTRY']) ? $arResult['ELEMENT']['~REG_ADDRESS_COUNTRY'] : '',
				'LOCALITY' => array(
					'TYPE' => 'COUNTRY',
					'NAME' => 'REG_ADDRESS_COUNTRY_CODE',
					'VALUE' => isset($arResult['ELEMENT']['~REG_ADDRESS_COUNTRY_CODE']) ? $arResult['ELEMENT']['~REG_ADDRESS_COUNTRY_CODE'] : ''
				)
			)
		)
	)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['BANKING_DETAILS']) ? $arResult['ELEMENT']['BANKING_DETAILS'] : ''
);

// Contacts selector
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contacts',
	'name' => GetMessage('CRM_SECTION_CONTACTS'),
	'type' => 'section'
);
if (CCrmContact::CheckReadPermission(0, $userPermissions))
{
	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'CONTACT',
			'INPUT_NAME' => 'CONTACT_ID',
			'INPUT_VALUE' => isset($arResult['CONTACT_ID']) ? $arResult['CONTACT_ID'] : '',
			'FORM_NAME' => $arResult['FORM_ID'],
			'MULTIPLE' => 'Y'
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
		'type' => 'custom',
		'wrap' => true,
		'value' => $sVal
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bConvert ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm,
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'COMPANY');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			$bEdit ? array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentCompany', $bEdit ? 'COMPANY_'.$arResult['ELEMENT']['ID'] : 'COMPANY_0'),
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
				'id' => 'BP_EVENTS',
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

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

?>
