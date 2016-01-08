<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("voximplant")) return;

Class voximplant extends CModule
{
	var $MODULE_ID = "voximplant";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function voximplant()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = VI_VERSION;
			$this->MODULE_VERSION_DATE = VI_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("VI_MODULE_NAME_2");
		$this->MODULE_DESCRIPTION = GetMessage("VI_MODULE_DESCRIPTION_2");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(GetMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step1.php");

		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB(Array(
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				));
				$this->InstallFiles();
				$this->InstallUserFields();

				$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
				CBitrixComponent::clearComponentCache("bitrix:menu");
			}
			$APPLICATION->IncludeAdminFile(GetMessage("VI_INSTALL_TITLE_2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/step2.php");
		}
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function CheckModules()
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = GetMessage('VI_CHECK_PULL');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = GetMessage('VI_CHECK_IM');
		}

		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php");
		if (version_compare("14.9.2", SM_VERSION) == 1)
		{
			$this->errors[] = GetMessage('VI_CHECK_MAIN');
		}

		if (IsModuleInstalled('intranet'))
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/install/version.php");
			if (version_compare("14.5.6", $arModuleVersion["VERSION"]) == 1)
			{
				$this->errors[] = GetMessage('VI_CHECK_INTRANET');
			}
		}
		else
		{
			$this->errors[] = GetMessage('VI_CHECK_INTRANET_INSTALL');
		}

		if(is_array($this->errors) && !empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			return true;
		}
	}

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;
		if (strlen($params['PUBLIC_URL']) > 0 && strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = GetMessage('VI_CHECK_PUBLIC_PATH');
		}
		if(!$this->errors && !$DB->Query("SELECT 'x' FROM b_voximplant_phone", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/".strtolower($DB->type)."/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		COption::SetOptionString("voximplant", "portal_url", $params['PUBLIC_URL']);

		RegisterModule("voximplant");

		RegisterModuleDependences('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		RegisterModuleDependences('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		RegisterModuleDependences('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		RegisterModuleDependences('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');
		RegisterModuleDependences("perfmon", "OnGetTableSchema", "voximplant", "CVoxImplantTableSchema", "OnGetTableSchema");

		RegisterModuleDependences("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		RegisterModuleDependences("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");

		RegisterModuleDependences("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');

		CAgent::AddAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant", "N", 300);
		CAgent::AddAgent("CVoxImplantMain::CountTelephonyStatisticAgent();", "voximplant", "N", 86400);

		return true;
	}

	function InstallFiles()
	{
		if($_ENV['COMPUTERNAME']!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		}
		return true;
	}

	function InstallUserFields()
	{
		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PASSWORD';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: user password';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_BACKPHONE';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_BACKPHONE'] = 'VoxImplant: user backphone';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_BACKPHONE'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_BACKPHONE'];
				}
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: phone';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}

		$arFields = array();
		$arFields['ENTITY_ID'] = 'USER';
		$arFields['FIELD_NAME'] = 'UF_VI_PHONE_PASSWORD';

		$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => $arFields['ENTITY_ID'], 'FIELD_NAME' => $arFields['FIELD_NAME']));
		if (!$res->Fetch())
		{
			$rs = CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $arFields["ENTITY_ID"],
				"FIELD_NAME" => $arFields["FIELD_NAME"],
			));
			if(!$rs->Fetch())
			{
				$arMess['VI_UF_NAME_PASSWORD'] = 'VoxImplant: phone password';

				$arFields['USER_TYPE_ID'] = 'string';
				$arFields['EDIT_IN_LIST'] = 'N';
				$arFields['SHOW_IN_LIST'] = 'N';
				$arFields['MULTIPLE'] = 'N';

				$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = $arMess['VI_UF_NAME_PASSWORD'];
				if (LANGUAGE_ID != 'en')
				{
					$arFields['EDIT_FORM_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_COLUMN_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
					$arFields['LIST_FILTER_LABEL']['en'] = $arMess['VI_UF_NAME_PASSWORD'];
				}

				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Add($arFields);
			}
		}
	}

	function UnInstallEvents()
	{
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("VI_UNINSTALL_TITLE_2"), $DOCUMENT_ROOT."/bitrix/modules/voximplant/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallUserFields(array("savedata" => $_REQUEST["savedata"]));
			$this->UnInstallFiles();

			$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
			CBitrixComponent::clearComponentCache("bitrix:menu");

			$APPLICATION->IncludeAdminFile(GetMessage("VI_UNINSTALL_TITLE_2"), $DOCUMENT_ROOT."/bitrix/modules/voximplant/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/voximplant/install/db/".strtolower($DB->type)."/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		UnRegisterModuleDependences('main', 'OnBeforeUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserAdd');
		UnRegisterModuleDependences('main', 'OnAfterUserAdd', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		UnRegisterModuleDependences('main', 'OnBeforeUserUpdate', 'voximplant', 'CVoxImplantEvent', 'OnBeforeUserUpdate');
		UnRegisterModuleDependences('main', 'OnUserDelete', 'voximplant', 'CVoxImplantEvent', 'OnUserDelete');

		UnRegisterModuleDependences("crm", "OnAfterExternalCrmLeadAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");
		UnRegisterModuleDependences("crm", "OnAfterExternalCrmContactAdd", "voximplant", "CVoxImplantCrmHelper", "RegisterEntity");

		UnRegisterModuleDependences("pull", "OnGetDependentModule", "voximplant", "CVoxImplantEvent", "PullOnGetDependentModule");
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'voximplant', 'CVoxImplantRestService', 'OnRestServiceBuildDescription');

		CAgent::RemoveAgent("CVoxImplantPhone::SynchronizeUserPhones();", "voximplant");
		CAgent::RemoveAgent("CVoxImplantEvent::CountTelephonyStatisticAgent();", "voximplant");

		UnRegisterModule("voximplant");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		return true;
	}

	function UnInstallUserFields($arParams = Array())
	{
		if (!$arParams['savedata'])
		{
			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_BACKPHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}

			$res = CUserTypeEntity::GetList(Array(), Array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_VI_PHONE_PASSWORD'));
			$arFieldData = $res->Fetch();
			if (isset($arFieldData['ID']))
			{
				$CUserTypeEntity = new CUserTypeEntity();
				$CUserTypeEntity->Delete($arFieldData['ID']);
			}
		}

		return true;
	}
}
?>