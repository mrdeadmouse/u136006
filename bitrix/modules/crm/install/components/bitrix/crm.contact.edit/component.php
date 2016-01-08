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
$CCrmContact = new CCrmContact();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;

$isEditMode = false;
$isCopyMode = false;
$varsFromForm = false;
if ($arParams['ELEMENT_ID'] > 0)
	$isEditMode = true;
if (!empty($_REQUEST['copy']))
{
	$isCopyMode = true;
	$isEditMode = false;
}
$isConverting = isset($arParams['CONVERT']) && $arParams['CONVERT'];

$isPermitted = $isEditMode
	? CCrmContact::CheckUpdatePermission($arParams['ELEMENT_ID'], $userPermissions)
	: CCrmContact::CheckCreatePermission($userPermissions);

if(!$isPermitted)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arEntityAttr = $arParams['ELEMENT_ID'] > 0
	? $userPermissions->GetEntityAttr('CONTACT', array($arParams['ELEMENT_ID']))
	: array();

$isInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$isInternal = true;
$arResult['INTERNAL'] = $isInternal;

if ($isEditMode || $isCopyMode)
{
	$obFields = CCrmContact::GetListEx(
		array(),
		array('=ID' => $arParams['ELEMENT_ID'], 'CHECK_PERMISSIONS'=> 'N')
	);
	$arFields = is_object($obFields) ? $obFields->GetNext() : false;
	if ($arFields === false)
	{
		$isEditMode = false;
		$isCopyMode = false;
	}

	if ($isCopyMode)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['PHOTO']);
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
			CCrmContact::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['company_id']))
	{
		$arFields['COMPANY_ID'] = intval($_GET['company_id']);
	}
	if (isset($_GET['name']))
	{
		$arFields['~NAME'] = $_GET['name'];
		CUtil::decodeURIComponent($arFields['~NAME']);
		$arFields['NAME'] = htmlspecialcharsbx($arFields['~NAME']);
	}
	if (isset($_GET['second_name']))
	{
		$arFields['~SECOND_NAME'] = $_GET['second_name'];
		CUtil::decodeURIComponent($arFields['~SECOND_NAME']);
		$arFields['SECOND_NAME'] = htmlspecialcharsbx($arFields['~SECOND_NAME']);
	}
	if (isset($_GET['last_name']))
	{
		$arFields['~LAST_NAME'] = $_GET['last_name'];
		CUtil::decodeURIComponent($arFields['~LAST_NAME']);
		$arFields['LAST_NAME'] = htmlspecialcharsbx($arFields['~LAST_NAME']);
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

$CCrmBizProc = new CCrmBizProc('CONTACT');

if($isConverting)
{
	$varsFromForm = true;
}
else
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$varsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
		{
			$arFields = array(
				'NAME' => trim($_POST['NAME']),
				'LAST_NAME' => trim($_POST['LAST_NAME']),
				'SECOND_NAME' => trim($_POST['SECOND_NAME'])
			);

			if(isset($_POST['POST']))
			{
				$arFields['POST'] = trim($_POST['POST']);
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

			if(isset($_POST['SOURCE_DESCRIPTION']))
			{
				$arFields['SOURCE_DESCRIPTION'] = trim($_POST['SOURCE_DESCRIPTION']);
			}

			if(isset($_POST['SOURCE_ID']))
			{
				$arFields['SOURCE_ID'] = trim($_POST['SOURCE_ID']);
			}

			if(isset($_POST['TYPE_ID']))
			{
				$arFields['TYPE_ID'] = trim($_POST['TYPE_ID']);
			}

			if(isset($_POST['COMPANY_ID']))
			{
				$companyID = intval($_POST['COMPANY_ID']);
				if($companyID <= 0 || !CCrmCompany::CheckReadPermission($companyID))
				{
					$companyID = 0;
				}
				$arFields['COMPANY_ID'] = $companyID;
			}

			if(isset($_POST['COMMENTS']))
			{
				$comments = trim($_POST['COMMENTS']);
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

			if(isset($_FILES['PHOTO']))
			{
				$arFields['PHOTO'] = $_FILES['PHOTO'];
			}

			if(isset($_POST['PHOTO_del']))
			{
				$arFields['PHOTO_del'] = $_POST['PHOTO_del'];
			}

			if(isset($_POST['EXPORT']))
			{
				$arFields['EXPORT'] = isset($_POST['EXPORT']) && $_POST['EXPORT'] == 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['EXPORT'] = 'N';
			}

			if(isset($_POST['OPENED']))
			{
				$arFields['OPENED'] = isset($_POST['OPENED']) && $_POST['OPENED'] == 'Y' ? 'Y' : 'N';
			}
			elseif(!$isEditMode)
			{
				$arFields['OPENED'] = 'N';
			}

			if(isset($_POST['ASSIGNED_BY_ID']))
			{
				$arFields['ASSIGNED_BY_ID'] = intval(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']);
			}

			if(isset($_POST['CONFM']))
			{
				$arFields['FM'] = $_POST['CONFM'];
			}

			if(isset($_POST['BIRTHDATE']))
			{
				$arFields['BIRTHDATE'] = $_POST['BIRTHDATE'];
			}

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmContact::$sUFEntityID, $arFields);

			$originID = isset($_REQUEST['origin_id']) ? $_REQUEST['origin_id'] : '';
			if($originID !== '')
			{
				$arFields['ORIGIN_ID'] = $originID;
			}

			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmContact->CheckFields($arFields, $isEditMode ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmContact->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmContact->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($isEditMode ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $isEditMode ? array($arResult['ELEMENT']['ID'] => $arEntityAttr[$arResult['ELEMENT']['ID']]) : null) === false))
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$success = false;
				if ($isEditMode)
				{
					$success = $CCrmContact->Update($arResult['ELEMENT']['ID'], $arFields, true, true, array('REGISTER_SONET_EVENT' => true));
				}
				else
				{
					$ID = $CCrmContact->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
					$success = $ID !== false;
					if($success)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if($success)
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
					CCrmContact::GetFields()
				);
			}
			else
			{
				if (!isset($_POST['COMPANY_ID']) && isset($_POST['COMPANY_NAME']))
				{
					if (CCrmCompany::CheckCreatePermission())
					{
						$arFields = array(
							'TITLE' => trim($_POST['COMPANY_NAME']),
							'CONTACT_ID' => array($ID),
						);
						$CCrmCompany = new CCrmCompany();
						$companyId = $CCrmCompany->Add($arFields);
						$CCrmContact->UpdateCompanyId($ID, $companyId);
					}
				}

				if (isset($_POST['apply']))
				{
					if (CCrmContact::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_EDIT'],
								array('contact_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					$redirectUrl = CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_CONTACT_EDIT'],
							array('contact_id' => 0)
					);
					if($companyID > 0)
					{
						$redirectUrl = CCrmUrlUtil::AddUrlParams($redirectUrl, array('company_id' => $companyID));
					}
					LocalRedirect($redirectUrl);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmContact::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_SHOW'],
								array('contact_id' => $ID)
							)
						);
					}
				}

				//save
				LocalRedirect(
					isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
						? $_REQUEST['backurl']
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array())
				);
			}
		}
	}
	else if (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($isEditMode)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!CCrmContact::CheckDeletePermission($arParams['ELEMENT_ID'], $userPermissions))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
			if ($bDeleteError)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmContact->Delete($arResult['ELEMENT']['ID'], array('PROCESS_BIZPROC' => false)))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST']));
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

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_CONTACT_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_CONTACT_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_CONTACT_LIST'];
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('CONTACT_TYPE');
$arResult['EDIT'] = $isEditMode;
$arResult['IS_COPY'] = $isCopyMode;
$arResult['DUPLICATE_CONTROL'] = array();
$enableDupControl = $arResult['DUPLICATE_CONTROL']['ENABLED'] =
	!$isEditMode && \Bitrix\Crm\Integrity\DuplicateControl::isControlEnabledFor(CCrmOwnerType::Contact);

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);

$lastNameID = $arResult['FORM_ID'].'_LAST_NAME';
$lastNameCaptionID = $arResult['FORM_ID'].'_LAST_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'] = $lastNameID;
	$arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'] = $lastNameCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LAST_NAME',
	'name' => GetMessage('CRM_FIELD_LAST_NAME'),
	'nameWrapper' => $lastNameCaptionID,
	'params' => array('id' => $lastNameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
	'required' => true
);

$nameID = $arResult['FORM_ID'].'_NAME';
$nameCaptionID = $arResult['FORM_ID'].'_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['NAME_ID'] = $nameID;
	$arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'] = $nameCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'nameWrapper' => $nameCaptionID,
	'params' => array('id' => $nameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
	'required' => true
);

$secondNameID = $arResult['FORM_ID'].'_SECOND_NAME';
$secondNameCaptionID = $arResult['FORM_ID'].'_SECOND_NAME_CAP';
if($enableDupControl)
{
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'] = $secondNameID;
	$arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'] = $secondNameCaptionID;
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECOND_NAME',
	'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
	'nameWrapper' => $secondNameCaptionID,
	'params' => array('id'=> $secondNameID, 'size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHOTO',
	'name' => GetMessage('CRM_FIELD_PHOTO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['PHOTO']) ? $arResult['ELEMENT']['PHOTO'] : '',
);

$birthDate = isset($arResult['ELEMENT']['BIRTHDATE']) ? $arResult['ELEMENT']['BIRTHDATE'] : '';
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BIRTHDATE',
	'name' => GetMessage('CRM_CONTACT_EDIT_FIELD_BIRTHDATE'),
	'type' => 'date_short',
	'value' => $birthDate !== '' ? ConvertTimeStamp(MakeTimeStamp($birthDate), 'SHORT', SITE_ID) : ''
);

$emailEditorID = uniqid('CONFM_EMAIL_');
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'EDITOR_ID' => $emailEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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

$phoneEditorID = uniqid('CONFM_PHONE_');
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'EDITOR_ID' => $phoneEditorID,
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
if (CCrmCompany::CheckReadPermission())
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
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'address',
	'componentParams' => array(
		'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
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
	'id' => 'EXPORT',
	'name' => GetMessage('CRM_FIELD_EXPORT'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['EXPORT']) ? $arResult['ELEMENT']['EXPORT'] : 'Y'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_contact_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => isset($arResult['ELEMENT']['~SOURCE_ID']) ? $arResult['ELEMENT']['~SOURCE_ID'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bConvert ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $varsFromForm,
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'CONTACT');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
			$bEdit ? array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentContact', $bEdit ? 'CONTACT_'.$arResult['ELEMENT']['ID'] : 'CONTACT_0'),
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
				$varsFromForm
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

if ($isCopyMode)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');

?>
