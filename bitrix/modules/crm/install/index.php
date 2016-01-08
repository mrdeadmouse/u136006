<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen('/install/index.php'));
include(GetLangFileName($strPath2Lang.'/lang/', '/install/index.php'));

if (class_exists('crm')) return;
Class crm extends CModule
{
	var $MODULE_ID = 'crm';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = 'Y';
	var $errors = '';

	function crm()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen('/index.php'));
		include($path.'/version.php');

		if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion['VERSION'];
			$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		}
		else
		{
			$this->MODULE_VERSION = CRM_VERSION;
			$this->MODULE_VERSION_DATE = CRM_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage('CRM_INSTALL_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('CRM_INSTALL_DESCRIPTION');
	}

	function InstallUserFields()
	{
		global $DB, $APPLICATION, $USER;
		global $USER_FIELD_MANAGER;

		AddEventHandler("main", "OnUserTypeBuildList", array("CUserTypeCrm", "GetUserTypeDescription"));
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/classes/general/crm_usertypecrm.php");

		$strPath2Lang = str_replace("\\", "/", __FILE__);
		$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen('/install/index.php')).'/lang/';
		$arMess = self::__GetMessagesForAllLang($strPath2Lang,'/install/index.php',array('CRM_UF_NAME','CRM_UF_NAME_CAL','CRM_UF_NAME_LF_TYPE','CRM_UF_NAME_LF_ID'));


		// add CRM userfield for CTask
		$rsUserType = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_CRM_TASK',
			)
		);
		if (!$rsUserType->Fetch())
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = 'TASKS_TASK';
			$arFields['FIELD_NAME'] = 'UF_CRM_TASK';
			$arFields['USER_TYPE_ID'] = 'crm';
			$arFields['SETTINGS']['LEAD'] = 'Y';
			$arFields['SETTINGS']['CONTACT'] = 'Y';
			$arFields['SETTINGS']['COMPANY'] = 'Y';
			$arFields['SETTINGS']['DEAL'] = 'Y';
			$arFields['MULTIPLE'] = 'Y';

			if (!empty($arMess['CRM_UF_NAME']))
			{
				$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME'];
				$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME'];
				$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME'];
			}

			$CAllUserTypeEntity = new CUserTypeEntity();
			$intID = $CAllUserTypeEntity->Add($arFields);
			if (false == $intID)
			{
				if ($strEx = $APPLICATION->GetException())
				{
					$this->errors[] = $strEx->GetString();
				}
			}
		}

		// add CRM userfield for CTaskTemplates
		$rsUserType = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'TASKS_TASK_TEMPLATE',
				'FIELD_NAME' => 'UF_CRM_TASK',
			)
		);
		if (!$rsUserType->Fetch())
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = 'TASKS_TASK_TEMPLATE';
			$arFields['FIELD_NAME'] = 'UF_CRM_TASK';
			$arFields['USER_TYPE_ID'] = 'crm';
			$arFields['SETTINGS']['LEAD'] = 'Y';
			$arFields['SETTINGS']['CONTACT'] = 'Y';
			$arFields['SETTINGS']['COMPANY'] = 'Y';
			$arFields['SETTINGS']['DEAL'] = 'Y';
			$arFields['MULTIPLE'] = 'Y';

			if (!empty($arMess['CRM_UF_NAME']))
			{
				$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME'];
				$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME'];
				$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME'];
			}

			$CAllUserTypeEntity = new CUserTypeEntity();
			$intID = $CAllUserTypeEntity->Add($arFields);
			if (false == $intID)
			{
				if ($strEx = $APPLICATION->GetException())
				{
					$this->errors[] = $strEx->GetString();
				}
			}
		}

		$rsUserType = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'CALENDAR_EVENT',
				'FIELD_NAME' => 'UF_CRM_CAL_EVENT',
			)
		);
		if (!$rsUserType->Fetch())
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = 'CALENDAR_EVENT';
			$arFields['FIELD_NAME'] = 'UF_CRM_CAL_EVENT';
			$arFields['USER_TYPE_ID'] = 'crm';
			$arFields['SETTINGS']['LEAD'] = 'Y';
			$arFields['SETTINGS']['CONTACT'] = 'Y';
			$arFields['SETTINGS']['COMPANY'] = 'Y';
			$arFields['SETTINGS']['DEAL'] = 'Y';
			$arFields['MULTIPLE'] = 'Y';

			if (!empty($arMess['CRM_UF_NAME_CAL']))
			{
				$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
				$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
				$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME_CAL'];
			}

			$CAllUserTypeEntity = new CUserTypeEntity();
			$intID = $CAllUserTypeEntity->Add($arFields);
			if (false == $intID)
			{
				if ($strEx = $APPLICATION->GetException())
				{
					$this->errors[] = $strEx->GetString();
				}
			}
		}
/*
		$rsUserType = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'SONET_LOG',
				'FIELD_NAME' => 'UF_LF_CRM_TYPE',
			)
		);
		if (!$rsUserType->Fetch())
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = 'SONET_LOG';
			$arFields['FIELD_NAME'] = 'UF_LF_CRM_TYPE';
			$arFields['USER_TYPE_ID'] = 'integer';
			$arFields['SETTINGS']['SIZE'] = '2';
			$arFields['MULTIPLE'] = 'N';

			if (!empty($arMess['CRM_UF_NAME_LF_TYPE']))
			{
				$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME_LF_TYPE'];
				$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME_LF_TYPE'];
				$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME_LF_TYPE'];
			}

			$CAllUserTypeEntity = new CUserTypeEntity();
			$intID = $CAllUserTypeEntity->Add($arFields);
			if (false == $intID)
			{
				if ($strEx = $APPLICATION->GetException())
				{
					$this->errors[] = $strEx->GetString();
				}
			}
		}
		
		$rsUserType = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'SONET_LOG',
				'FIELD_NAME' => 'UF_LF_CRM_ID',
			)
		);
		if (!$rsUserType->Fetch())
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = 'SONET_LOG';
			$arFields['FIELD_NAME'] = 'UF_LF_CRM_ID';
			$arFields['USER_TYPE_ID'] = 'integer';
			$arFields['SETTINGS']['SIZE'] = '10';
			$arFields['MULTIPLE'] = 'N';

			if (!empty($arMess['CRM_UF_NAME_LF_ID']))
			{
				$arFields['EDIT_FORM_LABEL'] = $arMess['CRM_UF_NAME_LF_ID'];
				$arFields['LIST_COLUMN_LABEL'] = $arMess['CRM_UF_NAME_LF_ID'];
				$arFields['LIST_FILTER_LABEL'] = $arMess['CRM_UF_NAME_LF_ID'];
			}

			$CAllUserTypeEntity = new CUserTypeEntity();
			$intID = $CAllUserTypeEntity->Add($arFields);
			if (false == $intID)
			{
				if ($strEx = $APPLICATION->GetException())
				{
					$this->errors[] = $strEx->GetString();
				}
			}
		}
*/
	}

	function InstallDB()
	{
		global $DB, $APPLICATION, $USER;
		global $USER_FIELD_MANAGER;

		$this->errors = false;
		if (!$DB->Query("SELECT 'x' FROM b_crm_lead", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/db/'.strtolower($DB->type).'/install.sql');

			//COption::SetOptionString('crm', 'GROUP_DEFAULT_RIGHT', 'R');
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/include.php');
			// Create default status list

			$CCrmStatus = new CCrmStatus('STATUS');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_NEW'),
					'STATUS_ID' => 'NEW',
					'SORT' => 10,
					'SYSTEM' => 'Y',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_ASSIGNED'),
					'STATUS_ID' => 'ASSIGNED',
					'SORT' => 20,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_DETAILS'),
					'STATUS_ID' => 'DETAILS',
					'SORT' => 30,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_CANNOT_CONTACT'),
					'STATUS_ID' => 'CANNOT_CONTACT',
					'SORT' => 40,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_IN_PROCESS'),
					'STATUS_ID' => 'IN_PROCESS',
					'SORT' => 50,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_ON_HOLD'),
					'STATUS_ID' => 'ON_HOLD',
					'SORT' => 60,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_RESTORED'),
					'STATUS_ID' => 'RESTORED',
					'SORT' => 70,
					'SYSTEM' => 'N',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_CONVERTED'),
					'STATUS_ID' => 'CONVERTED',
					'SORT' => 80,
					'SYSTEM' => 'Y',
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_JUNK'),
					'STATUS_ID' => 'JUNK',
					'SORT' => 90,
					'SYSTEM' => 'Y',
				)
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default source list

			$CCrmStatus = new CCrmStatus('SOURCE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_SELF'),
					'STATUS_ID' => 'SELF',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_PARTNER'),
					'STATUS_ID' => 'PARTNER',
					'SORT' => 20,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CALL'),
					'STATUS_ID' => 'CALL',
					'SORT' => 30,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_WEB'),
					'STATUS_ID' => 'WEB',
					'SORT' => 40,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_EMAIL'),
					'STATUS_ID' => 'EMAIL',
					'SORT' => 50,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CONFERENCE'),
					'STATUS_ID' => 'CONFERENCE',
					'SORT' => 60,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_TRADE_SHOW'),
					'STATUS_ID' => 'TRADE_SHOW',
					'SORT' => 70,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_EMPLOYEE'),
					'STATUS_ID' => 'EMPLOYEE',
					'SORT' => 80,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_COMPANY'),
					'STATUS_ID' => 'COMPANY',
					'SORT' => 90,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_HR'),
					'STATUS_ID' => 'HR',
					'SORT' => 100,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_MAIL'),
					'STATUS_ID' => 'MAIL',
					'SORT' => 110,
				),
				Array(
					'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_OTHER'),
					'STATUS_ID' => 'OTHER',
					'SORT' => 120,
				)
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default contact type list

			$CCrmStatus = new CCrmStatus('CONTACT_TYPE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_CONTACT_TYPE_SHARE'),
					'STATUS_ID' => 'SHARE',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_CONTACT_TYPE_JOURNALIST'),
					'STATUS_ID' => 'JOURNALIST',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_CONTACT_TYPE_CLIENT'),
					'STATUS_ID' => 'CLIENT',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_CONTACT_TYPE_SUPPLIER'),
					'STATUS_ID' => 'SUPPLIER',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_CONTACT_TYPE_PARTNER'),
					'STATUS_ID' => 'PARTNER',
					'SORT' => 50,
					'SYSTEM' => 'N'
				)
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default contact type list

			$CCrmStatus = new CCrmStatus('COMPANY_TYPE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_CUSTOMER'),
					'STATUS_ID' => 'CUSTOMER',
					'SORT' => 10,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_PARTNER'),
					'STATUS_ID' => 'PARTNER',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_RESELLER'),
					'STATUS_ID' => 'RESELLER',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_COMPETITOR'),
					'STATUS_ID' => 'COMPETITOR',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_INVESTOR'),
					'STATUS_ID' => 'INVESTOR',
					'SORT' => 50,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_INTEGRATOR'),
					'STATUS_ID' => 'INTEGRATOR',
					'SORT' => 60,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_PROSPECT'),
					'STATUS_ID' => 'PROSPECT',
					'SORT' => 70,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_PRESS'),
					'STATUS_ID' => 'PRESS',
					'SORT' => 80,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_COMPANY_TYPE_OTHER'),
					'STATUS_ID' => 'OTHER',
					'SORT' => 90,
					'SYSTEM' => 'Y'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			$CCrmStatus = new CCrmStatus('EMPLOYEES');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_EMPLOYEES_1'),
					'STATUS_ID' => 'EMPLOYEES_1',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_EMPLOYEES_2'),
					'STATUS_ID' => 'EMPLOYEES_2',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_EMPLOYEES_3'),
					'STATUS_ID' => 'EMPLOYEES_3',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_EMPLOYEES_4'),
					'STATUS_ID' => 'EMPLOYEES_4',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default industry  list

			$CCrmStatus = new CCrmStatus('INDUSTRY');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_IT'),
					'STATUS_ID' => 'IT',
					'SORT' => 10,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_TELECOM'),
					'STATUS_ID' => 'TELECOM',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_MANUFACTURING'),
					'STATUS_ID' => 'MANUFACTURING',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_BANKING'),
					'STATUS_ID' => 'BANKING',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_CONSULTING'),
					'STATUS_ID' => 'CONSULTING',
					'SORT' => 50,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_FINANCE'),
					'STATUS_ID' => 'FINANCE',
					'SORT' => 60,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_GOVERNMENT'),
					'STATUS_ID' => 'GOVERNMENT',
					'SORT' => 70,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_DELIVERY'),
					'STATUS_ID' => 'DELIVERY',
					'SORT' => 80,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_ENTERTAINMENT'),
					'STATUS_ID' => 'ENTERTAINMENT',
					'SORT' => 90,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_NOTPROFIT'),
					'STATUS_ID' => 'NOTPROFIT',
					'SORT' => 100,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_INDUSTRY_OTHER'),
					'STATUS_ID' => 'OTHER',
					'SORT' => 110,
					'SYSTEM' => 'Y'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default deal type list

			$CCrmStatus = new CCrmStatus('DEAL_TYPE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_DEAL_TYPE_SALE'),
					'STATUS_ID' => 'SALE',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_TYPE_COMPLEX'),
					'STATUS_ID' => 'COMPLEX',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_TYPE_GOODS'),
					'STATUS_ID' => 'GOODS',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_TYPE_SERVICES'),
					'STATUS_ID' => 'SERVICES',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_TYPE_SERVICE'),
					'STATUS_ID' => 'SERVICE',
					'SORT' => 50,
					'SYSTEM' => 'N'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default deal stage list

			$CCrmStatus = new CCrmStatus('DEAL_STAGE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_NEW'),
					'STATUS_ID' => 'NEW',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_DETAILS'),
					'STATUS_ID' => 'DETAILS',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_PROPOSAL'),
					'STATUS_ID' => 'PROPOSAL',
					'SORT' => 40,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_NEGOTIATION'),
					'STATUS_ID' => 'NEGOTIATION',
					'SORT' => 50,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_WON'),
					'STATUS_ID' => 'WON',
					'SORT' => 70,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_ON_HOLD'),
					'STATUS_ID' => 'ON_HOLD',
					'SORT' => 75,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STAGE_LOSE'),
					'STATUS_ID' => 'LOSE',
					'SORT' => 80,
					'SYSTEM' => 'Y'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default deal state list

			$CCrmStatus = new CCrmStatus('DEAL_STATE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_DEAL_STATE_PLANNED'),
					'STATUS_ID' => 'PLANNED',
					'SORT' => 10,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STATE_PROCESS'),
					'STATUS_ID' => 'PROCESS',
					'SORT' => 20,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STATE_COMPLETE'),
					'STATUS_ID' => 'COMPLETE',
					'SORT' => 30,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_DEAL_STATE_CANCELED'),
					'STATUS_ID' => 'CANCELED',
					'SORT' => 40,
					'SYSTEM' => 'Y'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default event type

			$CCrmStatus = new CCrmStatus('EVENT_TYPE');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_EVENT_TYPE_INFO'),
					'STATUS_ID' => 'INFO',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_EVENT_TYPE_PHONE'),
					'STATUS_ID' => 'PHONE',
					'SORT' => 20,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_EVENT_TYPE_MESSAGE'),
					'STATUS_ID' => 'MESSAGE',
					'SORT' => 30,
					'SYSTEM' => 'Y'
				),
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			// Create default quote status list

			$CCrmStatus = new CCrmStatus('QUOTE_STATUS');
			$arAdd = Array(
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_DRAFT'),
					'STATUS_ID' => 'DRAFT',
					'SORT' => 10,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_SENT'),
					'STATUS_ID' => 'SENT',
					'SORT' => 20,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_RECEIVED'),
					'STATUS_ID' => 'RECEIVED',
					'SORT' => 30,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_APPROVED'),
					'STATUS_ID' => 'APPROVED',
					'SORT' => 40,
					'SYSTEM' => 'Y'
				),
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_UNANSWERED'),
					'STATUS_ID' => 'UNANSWERED',
					'SORT' => 50,
					'SYSTEM' => 'N'
				),
				Array(
					'NAME' => GetMessage('CRM_QUOTE_STATUS_DECLAINED'),
					'STATUS_ID' => 'DECLAINED',
					'SORT' => 60,
					'SYSTEM' => 'Y'
				)
			);
			foreach($arAdd as $ar)
				$CCrmStatus->Add($ar);

			$directionId = 0;
			$dbGroup = CGroup::GetList($by = "", $order = "", Array("STRING_ID" => "DIRECTION"));
			if($arGroup = $dbGroup -> Fetch())
				$directionId = $arGroup["ID"];

			$marketingId = 0;
			$dbGroup = CGroup::GetList($by = "", $order = "", Array("STRING_ID" => "MARKETING_AND_SALES"));
			if($arGroup = $dbGroup -> Fetch())
				$marketingId = $arGroup["ID"];

			$CCrmRole = new CCrmRole();
			$arRoles = array(
				'adm' => array(
					'NAME' => GetMessage('CRM_ROLE_ADMIN'),
					'RELATION' => array(
						'LEAD' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'DEAL' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'CONTACT' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'COMPANY' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'QUOTE' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'INVOICE' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'CONFIG' => array(
							'WRITE' => array('-' => 'X')
						)
					)
				),
				'dir' => array(
					'NAME' => GetMessage('CRM_ROLE_DIRECTOR'),
					'RELATION' => array(
						'LEAD' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'DEAL' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'CONTACT' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'COMPANY' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'QUOTE' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'INVOICE' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						)
					)
				),
				'chif' => array(
					'NAME' => GetMessage('CRM_ROLE_CHIF'),
					'RELATION' => array(
						'LEAD' => array(
							'READ' => array('-' => 'D'),
							'EXPORT' => array('-' => 'D'),
							'IMPORT' => array('-' => 'D'),
							'ADD' => array('-' => 'D'),
							'WRITE' => array('-' => 'D'),
							'DELETE' => array('-' => 'D')
						),
						'DEAL' => array(
							'READ' => array('-' => 'D'),
							'EXPORT' => array('-' => 'D'),
							'IMPORT' => array('-' => 'D'),
							'ADD' => array('-' => 'D'),
							'WRITE' => array('-' => 'D'),
							'DELETE' => array('-' => 'D')
						),
						'CONTACT' => array(
							'READ' => array('-' => 'D'),
							'EXPORT' => array('-' => 'D'),
							'IMPORT' => array('-' => 'D'),
							'ADD' => array('-' => 'D'),
							'WRITE' => array('-' => 'D'),
							'DELETE' => array('-' => 'D')
						),
						'COMPANY' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'QUOTE' => array(
							'READ' => array('-' => 'D'),
							'EXPORT' => array('-' => 'D'),
							'IMPORT' => array('-' => 'D'),
							'ADD' => array('-' => 'D'),
							'WRITE' => array('-' => 'D'),
							'DELETE' => array('-' => 'D')
						),
						'INVOICE' => array(
							'READ' => array('-' => 'D'),
							'EXPORT' => array('-' => 'D'),
							'IMPORT' => array('-' => 'D'),
							'ADD' => array('-' => 'D'),
							'WRITE' => array('-' => 'D'),
							'DELETE' => array('-' => 'D')
						)
					)
				),
				'man' => array(
					'NAME' => GetMessage('CRM_ROLE_MAN'),
					'RELATION' => array(
						'LEAD' => array(
							'READ' => array('-' => 'A'),
							'EXPORT' => array('-' => 'A'),
							'IMPORT' => array('-' => 'A'),
							'ADD' => array('-' => 'A'),
							'WRITE' => array('-' => 'A'),
							'DELETE' => array('-' => 'A')
						),
						'DEAL' => array(
							'READ' => array('-' => 'A'),
							'EXPORT' => array('-' => 'A'),
							'IMPORT' => array('-' => 'A'),
							'ADD' => array('-' => 'A'),
							'WRITE' => array('-' => 'A'),
							'DELETE' => array('-' => 'A')
						),
						'CONTACT' => array(
							'READ' => array('-' => 'A'),
							'EXPORT' => array('-' => 'A'),
							'IMPORT' => array('-' => 'A'),
							'ADD' => array('-' => 'A'),
							'WRITE' => array('-' => 'A'),
							'DELETE' => array('-' => 'A')
						),
						'COMPANY' => array(
							'READ' => array('-' => 'X'),
							'EXPORT' => array('-' => 'X'),
							'IMPORT' => array('-' => 'X'),
							'ADD' => array('-' => 'X'),
							'WRITE' => array('-' => 'X'),
							'DELETE' => array('-' => 'X')
						),
						'QUOTE' => array(
							'READ' => array('-' => 'A'),
							'EXPORT' => array('-' => 'A'),
							'IMPORT' => array('-' => 'A'),
							'ADD' => array('-' => 'A'),
							'WRITE' => array('-' => 'A'),
							'DELETE' => array('-' => 'A')
						),
						'INVOICE' => array(
							'READ' => array('-' => 'A'),
							'EXPORT' => array('-' => 'A'),
							'IMPORT' => array('-' => 'A'),
							'ADD' => array('-' => 'A'),
							'WRITE' => array('-' => 'A'),
							'DELETE' => array('-' => 'A')
						)
					)
				)
			);

			$iRoleID = $CCrmRole->Add($arRoles['adm']);
			$iRoleID = $CCrmRole->Add($arRoles['dir']);
			$arRel = array();
			if ($directionId > 0)
				$arRel['G'.$directionId] = array($iRoleID);
			$iRoleID = $CCrmRole->Add($arRoles['chif']);
			$iRoleID = $CCrmRole->Add($arRoles['man']);
			if ($marketingId > 0)
				$arRel['G'.$marketingId] = array($iRoleID);
			$CCrmRole->SetRelation($arRel);
		}

		$this->InstallUserFields();

		//FILL BUSINESS TYPES -->
		$dbType = strtoupper($DB->type);
		if($dbType === "ORACLE")
		{
			$dbResult = $DB->Query("SELECT COUNT(1) AS QTY FROM B_CRM_BIZ_TYPE");
		}
		elseif($dbType === "MSSQL")
		{
			$dbResult = $DB->Query("SELECT COUNT(*) AS QTY FROM B_CRM_BIZ_TYPE");
		}
		else
		{
			$dbResult = $DB->Query("SELECT COUNT(*) AS QTY FROM b_crm_biz_type");
		}

		$arResult = is_object($dbResult) ? $dbResult->Fetch() : null;
		$qty = (is_array($arResult) && isset($arResult["QTY"])) ? intval($arResult["QTY"]) : 0;
		if($qty === 0)
		{
			$allLangIDs = array();
			$sort = 'sort';
			$order = 'asc';
			$langEntity = new \CLanguage();
			$dbLangs = $langEntity->GetList($sort, $order);
			while($lang = $dbLangs->Fetch())
			{
				if(isset($lang['LID']))
				{
					$allLangIDs[] = $lang['LID'];
				}
			}

			foreach($allLangIDs as $langID)
			{
				$langFile = $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/crm/lang/'.$langID."/lib/businesstype.php";
				if(!file_exists($langFile))
				{
					continue;
				}

				//include($langFile);
				$messages = __IncludeLang($langFile, true);
				$s = isset($messages["CRM_BIZ_TYPE_DEFAULT"]) ? trim($messages["CRM_BIZ_TYPE_DEFAULT"]) : "";
				if($s === ' ' || $s === '-')
				{
					continue;
				}

				$bizTypeTableName = ($dbType === 'ORACLE' || $dbType === 'MSSQL') ? 'B_CRM_BIZ_TYPE' : 'b_crm_biz_type';
				foreach(explode('|', $s) as $slug)
				{
					$ary = explode(';', $slug);
					if(count($ary) < 2)
					{
						continue;
					}

					$code = $DB->ForSql($ary[0]);
					$name = $DB->ForSql($ary[1]);
					$lang = isset($ary[2]) ? $DB->ForSql($ary[2]) : '';

					$DB->Query("INSERT INTO {$bizTypeTableName}(CODE, NAME, LANG) VALUES('{$code}', '{$name}', '{$lang}')");
				}
			}
		}
		//<-- FILL BUSINESS TYPES

		RegisterModule('crm');

		RegisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterList');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrm', 'GetUserTypeDescription');
		RegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrmStatus', 'GetUserTypeDescription');
		RegisterModuleDependences('search', 'OnReindex', 'crm', 'CCrmSearch', 'OnSearchReindex');
		RegisterModuleDependences('search', 'OnSearchCheckPermissions', 'crm', 'CCrmSearch', 'OnSearchCheckPermissions');
		RegisterModuleDependences('report', 'OnReportAdd', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('report', 'OnReportUpdate', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('report', 'OnReportDelete', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		RegisterModuleDependences('iblock', 'OnIBlockDelete', 'crm', 'CAllCrmCatalog', 'OnIBlockDelete');
		RegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'crm', 'CCrmProduct', 'OnIBlockElementDelete');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmExternalSaleImport', 'OnFillSocNetLogEvents');

		RegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'crm', 'CAllCrmActivity', 'OnBeforeTaskAdd');
		RegisterModuleDependences('tasks', 'OnTaskAdd', 'crm', 'CAllCrmActivity', 'OnTaskAdd');
		RegisterModuleDependences('tasks', 'OnTaskUpdate', 'crm', 'CAllCrmActivity', 'OnTaskUpdate');
		RegisterModuleDependences('tasks', 'OnTaskDelete', 'crm', 'CAllCrmActivity', 'OnTaskDelete');

		RegisterModuleDependences('webdav', 'OnFileDelete', 'crm', 'CCrmWebDavHelper', 'OnWebDavFileDelete');

		RegisterModuleDependences('subscribe', 'BeforePostingSendMail', 'crm', 'CCrmEMail', 'BeforeSendMail');
		RegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'crm', 'CAllCrmActivity', 'OnCalendarEventEdit');
		RegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'crm', 'CAllCrmActivity', 'OnCalendarEventDelete');

		RegisterModuleDependences('crm', 'OnCrmStatusGetList', 'crm', 'CCrmStatusInvoice', 'getStatusList');
		RegisterModuleDependences('crm', 'OnGetEntityTypes', 'crm', 'CCrmStatusInvoice', 'onGetEntityTypes');
		RegisterModuleDependences('crm', 'OnBeforeCrmStatusCreate', 'crm', 'CCrmStatusInvoice', 'createCrmStatus');

		RegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmRestService', 'onRestServiceBuildDescription');
		RegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmInvoiceRestService', 'OnRestServiceBuildDescription');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'crm', 'CCrmLiveFeed', 'OnFillSocNetAllowedSubscribeEntityTypes');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogEvents');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogFields', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogFields');
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogFilter', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogFilter');
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogOrder', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogOrder');
		RegisterModuleDependences('socialnetwork', 'OnSocNetLogFormatDestination', 'crm', 'CCrmLiveFeed', 'OnSocNetLogFormatDestination');
		RegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogPerms', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogPerms');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogRightsAdd', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogRightsAdd');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogCommentCounterIncrement', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogCommentCounterIncrement');
		RegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogEntryCommentAdd', 'crm', 'CCrmLiveFeed', 'OnAfterSocNetLogEntryCommentAdd');
		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogEntryGetRights', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogEntryGetRights');
		RegisterModuleDependences("socialnetwork", "OnSendMentionGetEntityFields", "crm", "CCrmLiveFeed", "OnSendMentionGetEntityFields");
		RegisterModuleDependences('main', 'OnAddRatingVote', 'crm', 'CCrmLiveFeed', 'OnAddRatingVote');
		RegisterModuleDependences('forum', 'OnAfterCommentAdd', 'crm', 'CCrmLiveFeed', 'onAfterCommentAdd');

		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", '\Bitrix\Crm\Integration\Application', "OnApplicationsBuildList", 100, "modules/crm/lib/integration/application.php");
		RegisterModuleDependences('disk', 'onAfterDeleteFile', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'OnDiskFileDelete');

		if (is_array($this->errors))
		{
			$GLOBALS['errors'] = $this->errors;
			$APPLICATION->ThrowException(implode(' ', $this->errors));
			return false;
		}

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION, $stackCacheManager, $USER_FIELD_MANAGER;
		$this->errors = false;

		if (!array_key_exists('savedata', $arParams) || $arParams['savedata'] != 'Y')
		{
			// delete extra fields for all entities
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/include.php');
			$arEntityIds = CCrmFields::GetEntityTypes();
			foreach ($arEntityIds as $entityId => $ar)
			{
				$CCrmFields = new CCrmFields($USER_FIELD_MANAGER, $entityId);
				$arFields = $CCrmFields->GetFields();
				foreach ($arFields as $arField)
					$CCrmFields->DeleteField($arField['ID']);
			}

			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/db/'.strtolower($DB->type).'/uninstall.sql');

			if (CModule::IncludeModule('socialnetwork'))
			{
				$dbRes = CSocNetLog::GetList(
					array(),
					array("ENTITY_TYPE" => CCrmLiveFeedEntity::GetAll()),
					false,
					false,
					array("ID")
				);

				if ($dbRes)
				{
					while ($arRes = $dbRes->Fetch())
					{
						CSocNetLog::Delete($arRes["ID"]);
					}
				}
			}
		}

		$stackCacheManager->Clear('b_crm_status');
		$stackCacheManager->Clear('b_crm_perms');

		COption::RemoveOption('crm');

		UnRegisterModuleDependences('mail', 'OnGetFilterList', 'crm', 'CCrmEMail', 'OnGetFilterList');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrm', 'GetUserTypeDescription');
		UnRegisterModuleDependences('main', 'OnUserTypeBuildList', 'crm', 'CUserTypeCrmStatus', 'GetUserTypeDescription');
		UnRegisterModuleDependences('search', 'OnReindex', 'crm', 'CCrmSearch', 'OnSearchReindex');
		UnRegisterModuleDependences('search', 'OnSearchCheckPermissions', 'crm', 'CCrmSearch', 'OnSearchCheckPermissions');
		UnRegisterModuleDependences('report', 'OnReportAdd', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('report', 'OnReportUpdate', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('report', 'OnReportDelete', 'crm', 'CCrmReportHelper', 'clearMenuCache');
		UnRegisterModuleDependences('iblock', 'OnIBlockDelete', 'crm', 'CCrmCatalog', 'OnIBlockDelete');
		UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'crm', 'CAllCrmProduct', 'OnIBlockElementDelete');
		UnRegisterModuleDependences('iblock', 'OnAfterIBlockElementDelete', 'crm', 'CCrmProduct', 'OnIBlockElementDelete');

		UnRegisterModuleDependences("socialnetwork", "OnFillSocNetLogEvents", "crm", "CCrmExternalSaleImport", "OnFillSocNetLogEvents");

		UnRegisterModuleDependences('tasks', 'OnBeforeTaskAdd', 'crm', 'CAllCrmActivity', 'OnBeforeTaskAdd');
		UnRegisterModuleDependences('tasks', 'OnTaskAdd', 'crm', 'CAllCrmActivity', 'OnTaskAdd');
		UnRegisterModuleDependences('tasks', 'OnTaskUpdate', 'crm', 'CAllCrmActivity', 'OnTaskUpdate');
		UnRegisterModuleDependences('tasks', 'OnTaskDelete', 'crm', 'CAllCrmActivity', 'OnTaskDelete');

		UnRegisterModuleDependences('webdav', 'OnFileDelete', 'crm', 'CCrmWebDavHelper', 'OnWebDavFileDelete');

		UnRegisterModuleDependences('subscribe', 'BeforePostingSendMail', 'crm', 'CCrmEMail', 'BeforeSendMail');
		UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventEdit', 'crm', 'CAllCrmActivity', 'OnCalendarEventEdit');
		UnRegisterModuleDependences('calendar', 'OnAfterCalendarEventDelete', 'crm', 'CAllCrmActivity', 'OnCalendarEventDelete');

		UnRegisterModuleDependences('crm', 'OnCrmStatusGetList', 'crm', 'CCrmStatusInvoice', 'getStatusList');
		UnRegisterModuleDependences('crm', 'OnGetEntityTypes', 'crm', 'CCrmStatusInvoice', 'onGetEntityTypes');
		UnRegisterModuleDependences('crm', 'OnBeforeCrmStatusCreate', 'crm', 'CCrmStatusInvoice', 'createCrmStatus');

		UnRegisterModuleDependences('rest', 'onRestServiceBuildDescription', 'crm', 'CCrmRestService', 'onRestServiceBuildDescription');

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'crm', 'CCrmLiveFeed', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogEvents');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogFields', 'crm', 'CCrmLiveFeed', 'OnFillSocNetLogFields');
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogFilter', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogFilter');
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogOrder', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogOrder');
		UnRegisterModuleDependences('socialnetwork', 'OnSocNetLogFormatDestination', 'crm', 'CCrmLiveFeed', 'OnSocNetLogFormatDestination');
		UnRegisterModuleDependences('socialnetwork', 'OnBuildSocNetLogPerms', 'crm', 'CCrmLiveFeed', 'OnBuildSocNetLogPerms');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogRightsAdd', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogRightsAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogCommentCounterIncrement', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogCommentCounterIncrement');
		UnRegisterModuleDependences('socialnetwork', 'OnAfterSocNetLogEntryCommentAdd', 'crm', 'CCrmLiveFeed', 'OnAfterSocNetLogEntryCommentAdd');
		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetLogEntryGetRights', 'crm', 'CCrmLiveFeed', 'OnBeforeSocNetLogEntryGetRights');
		UnRegisterModuleDependences("socialnetwork", "OnSendMentionGetEntityFields", "crm", "CCrmLiveFeed", "OnSendMentionGetEntityFields");
		UnRegisterModuleDependences('main', 'OnAddRatingVote', 'crm', 'CCrmLiveFeed', 'OnAddRatingVote');

		UnRegisterModuleDependences('forum', 'OnAfterCommentAdd', 'crm', 'CCrmLiveFeed', 'onAfterCommentAdd');
		UnRegisterModuleDependences('disk', 'onAfterDeleteFile', 'crm', '\Bitrix\Crm\Integration\DiskManager', 'OnDiskFileDelete');

		if (CModule::IncludeModule('search'))
			CSearch::DeleteIndex('crm');

		UnRegisterModule('crm');

		if ($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode('<br />', $this->errors));
			return false;
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/components', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/gadgets', $_SERVER['DOCUMENT_ROOT'].'/bitrix/gadgets', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/js', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/admin', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/tools/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/tools', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/activities/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/activities', true, true);
		CopyDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/themes/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/themes', true, true);

		//[COPY CUSTOMIZED PAY SYSTEM ACTION FILES]-->
		$customPaySystemPath = IsModuleInstalled('sale') ? COption::GetOptionString('sale', 'path2user_ps_files', '') : '';
		if($customPaySystemPath === '')
		{
			$customPaySystemPath = BX_ROOT.'/php_interface/include/sale_payment/';
		}

		$sort = 'sort';
		$order = 'asc';
		$langEntity = new CLanguage();
		$dbLangs = $langEntity->GetList($sort, $order);
		while($lang = $dbLangs->Fetch())
		{
			$langSrcPaySystemPath = $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/install/integration/sale.paysystems/'.$lang['LID'].'/';
			if(!file_exists($langSrcPaySystemPath))
			{
				continue;
			}

			CopyDirFiles(
				$langSrcPaySystemPath,
				$_SERVER['DOCUMENT_ROOT'].$customPaySystemPath,
				true,
				true
			);
		}
		//<--[COPY CUSTOMIZED PAY SYSTEM ACTION FILES]BX_ROOT

		global $APPLICATION;

		$APPLICATION->SetFileAccessPermission('/bitrix/tools/ws_contacts_crm/', array('2' => 'R'));
		//HACK: bizproc crutch to enable user read only access to service files
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_activity_settings.php', array('2' => 'R'));
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_selector.php', array('2' => 'R'));
		$APPLICATION->SetFileAccessPermission('/bitrix/admin/crm_bizproc_wf_settings.php', array('2' => 'R'));
		return true;
	}

	function __CopyDir($target, $source, $bReWriteAdditionalFiles = false, $siteDir = '/', $lang)
	{
		CheckDirPath($target);
		$dh = opendir($source);
		while($file = readdir($dh))
		{
			if (is_file($source.$file))
			{
				if ($file == '.' || $file == '..')
					continue;

				if ($bReWriteAdditionalFiles || !file_exists($target.$file))
				{
					$fh = fopen($source.$file, 'rb');
					$php_source = fread($fh, filesize($source.$file));
					fclose($fh);
					if (preg_match_all('/GetMessage\("(.*?)"\)/', $php_source, $matches))
					{
						IncludeModuleLangFile($source.$file, $lang);
						foreach ($matches[0] as $i => $text)
						{
							$php_source = str_replace(
								$text,
								'"'.GetMessage($matches[1][$i]).'"',
								$php_source
							);
						}
					}

					$php_source = str_replace('#SITE_DIR#', $siteDir, $php_source);
					$fh = fopen($target.$file, 'wb');
					fwrite($fh, $php_source);
					fclose($fh);
				}
			}
		}
	}

	function __AddMenuItem($menuFile, $menuItem,  $siteID, $pos = -1)
	{
		if (CModule::IncludeModule('fileman'))
		{
			$arResult = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$menuFile);
			$arMenuItems = $arResult["aMenuLinks"];
			$menuTemplate = $arResult["sMenuTemplate"];

			$bFound = false;
			foreach($arMenuItems as $item)
				if($item[1] == $menuItem[1])
					$bFound = true;

			if(!$bFound)
			{
				if($pos<0 || $pos>=count($arMenuItems))
					$arMenuItems[] = $menuItem;
				else
				{
					for($i=count($arMenuItems); $i>$pos; $i--)
						$arMenuItems[$i] = $arMenuItems[$i-1];

					$arMenuItems[$pos] = $menuItem;
				}

				CFileMan::SaveMenu(Array($siteID, $menuFile), $arMenuItems, $menuTemplate);
			}
		}
	}

	function UnInstallFiles()
	{
		if($_ENV['COMPUTERNAME']!='BX')
		{
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/js', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js');
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/themes', $_SERVER['DOCUMENT_ROOT'].'/bitrix/themes');
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/gadgets', $_SERVER['DOCUMENT_ROOT'].'/bitrix/gadgets');
		}
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $step;

		$step = IntVal($step);
		if (!CBXFeatures::IsFeatureEditable('crm'))
		{
			$this->error = GetMessage('MAIN_FEATURE_ERROR_EDITABLE');
			$GLOBALS['errors'] = $this->error;
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/step3.php');
		}
		elseif($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/step1.php');
		}
		elseif($step == 2)
		{
			$this->InstallDB();
			$this->InstallFiles();
			CBXFeatures::SetFeatureEnabled('crm', true);
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/step3.php');
		}
/*		elseif ($step == 3)
		{
			$this->__CreateUserFields();
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/step3.php');
			die();
		}
		else
		{
			echo mydump($step);
			die();
		} */
	}

	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/unstep1.php');
		}
		elseif ($step == 2)
		{
			$this->UnInstallDB(array(
				'savedata' => $_REQUEST['savedata']
			));
			$this->UnInstallFiles();
			CBXFeatures::SetFeatureEnabled('crm', false);
			$GLOBALS['errors'] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage('CRM_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/unstep2.php');
		}
	}

	private function __GetMessagesForAllLang($strBefore,$strAfter,$MessID,$strDefMess = false,$arLangList = array())
	{
		$arResult = false;

		if (empty($MessID))
			return $arResult;
		if (!is_array($MessID))
			$MessID = array($MessID);

		if (empty($arLangList))
		{
			$rsLangs = CLanguage::GetList(($by="LID"), ($order="ASC"), array("ACTIVE" => "Y"));
			while ($arLang = $rsLangs->Fetch())
			{
				$arLangList[] = $arLang['LID'];
			}
		}
		foreach ($arLangList as &$strLID)
		{
			@include(GetLangFileName($strBefore, $strAfter, $strLID));
			foreach ($MessID as &$strMessID)
			{
				if (0 >= strlen($strMessID))
					continue;
				$arResult[$strMessID][$strLID] = (isset($MESS[$strMessID]) ? $MESS[$strMessID] : $strDefMess);
			}
		}
		return $arResult;
	}
}
?>
