<?
global $MESS;

IncludeModuleLangFile(__FILE__);

Class tasks extends CModule
{
	var $MODULE_ID = "tasks";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $errors;

	function tasks()
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
			$this->MODULE_VERSION = TASKS_VERSION;
			$this->MODULE_VERSION_DATE = TASKS_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("TASKS_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TASKS_MODULE_DESC");
	}


	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_tasks WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/db/".strtolower($DB->type)."/install.sql");
		}

		$errors = self::InstallUserFields();
		if ( ! empty($errors) )
		{
			if ( ! is_array($this->errors) )
				$this->errors = array();

			$this->errors = array_merge($this->errors, $errors);
		}

		$APPLICATION->ResetException();
		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		RegisterModule("tasks");
		RegisterModuleDependences("search", "OnReindex", "tasks", "CTasks", "OnSearchReindex", 200);
		RegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		RegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		RegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		RegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");
		RegisterModuleDependences(
			'search',
			'BeforeIndex',
			'tasks',
			'CTasksTools',
			'FixForumCommentURL',
			200
		);
		RegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerInit');
		RegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerAction');
		RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks',
			'CTaskRestService', 'OnRestServiceBuildDescription');
		RegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks',
			'CTaskComments', 'onCommentTopicAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks',
			'CTaskComments', 'onAfterCommentTopicAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks',
			'CTaskComments', 'onAfterCommentAdd');
		RegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks',
			'CTaskComments', 'onAfterCommentUpdate');

		RegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onForumUninstall');
		RegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onWebdavUninstall');
		RegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onIntranetUninstall');

		RegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks', 
			'CTaskPlannerMaintance', 'OnAfterTMDayStart');

		RegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks',
			'CTaskCountersNotifier', 'onAfterTimeManagerDayStart');

		RegisterModuleDependences(
			'timeman',
			'OnAfterTMEntryUpdate',
			'tasks',
			'CTaskTimerManager',
			'onAfterTMEntryUpdate'
		);

		RegisterModuleDependences(
			'tasks',
			'OnBeforeTaskUpdate',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskUpdate'
		);

		RegisterModuleDependences(
			'tasks',
			'OnBeforeTaskDelete',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskDelete'
		);

		RegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupDelete', 'tasks', 'CTasks', 'onBeforeSocNetGroupDelete');

		RegisterModuleDependences("main", "OnAfterRegisterModule", "main", "tasks", "InstallUserFields", 100, "/modules/tasks/install/index.php"); // check webdav UF

		RegisterModuleDependences("main", "OnBeforeUserTypeAdd", "tasks", "CTasksRarelyTools", "onBeforeUserTypeAdd");
		RegisterModuleDependences("main", "OnBeforeUserTypeUpdate", "tasks", "CTasksRarelyTools", "onBeforeUserTypeUpdate");
		RegisterModuleDependences("main", "OnBeforeUserTypeDelete", "tasks", "CTasksRarelyTools", "onBeforeUserTypeDelete");

		// im "ilike"
		RegisterModuleDependences("main", "OnGetRatingContentOwner", "tasks", "CTaskNotifications", "OnGetRatingContentOwner");
		RegisterModuleDependences("im", "OnGetMessageRatingVote", "tasks", "CTaskNotifications", "OnGetMessageRatingVote");

		CAgent::AddAgent('CTaskReminders::SendAgent();','tasks', 'N', 10800);	// every 3 hours

		CAgent::AddAgent(
			'CTaskCountersProcessorInstaller::setup();',
			'tasks',
			'N', 
			16777216	// we need only one start of this agent, after its remove itself
		);

		// If sanitize_level not set, set up it
		if (COption::GetOptionString('tasks', 'sanitize_level', 'not installed yet ))') === 'not installed yet ))')
			COption::SetOptionString('tasks', 'sanitize_level', CBXSanitizer::SECURE_LEVEL_LOW);

		// turn on comment editing by default
		COption::SetOptionString('tasks', 'task_comment_allow_edit', 'Y', '', '');
		COption::SetOptionString('tasks', 'task_comment_allow_remove', 'Y', '', '');

		return true;
	}


	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;

		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$rsUserType = CUserTypeEntity::getList(
				array(),
				array(
					'ENTITY_ID'  => 'TASKS_TASK',
					'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
				)
			);

			if ($arUserType = $rsUserType->fetch())
			{
				$obUserField = new CUserTypeEntity;
				$obUserField->Delete($arUserType['ID']);
			}

			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		//delete agents
		CAgent::RemoveModuleAgents("tasks");

		if (CModule::IncludeModule("search"))
			CSearch::DeleteIndex("tasks");

		UnRegisterModule("tasks");
		UnRegisterModuleDependences("search", "OnReindex", "tasks", "CTasks", "OnSearchReindex");
		UnRegisterModuleDependences("main", "OnUserDelete", "tasks", "CTasks", "OnUserDelete");
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "tasks", "CTasksNotifySchema", "OnGetNotifySchema");
		UnRegisterModuleDependences('main', 'OnBeforeUserDelete', 'tasks', 'CTasks', 'OnBeforeUserDelete');
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "tasks", "CTasksPullSchema", "OnGetDependentModule");
		UnRegisterModuleDependences(
			'search',
			'BeforeIndex',
			'tasks',
			'CTasksTools',
			'FixForumCommentURL'
		);
		UnRegisterModuleDependences('intranet', 'OnPlannerInit', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerInit');
		UnRegisterModuleDependences('intranet', 'OnPlannerAction', 'tasks',
			'CTaskPlannerMaintance', 'OnPlannerAction');
		UnRegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'tasks',
			'CTaskRestService', 'OnRestServiceBuildDescription');
		UnRegisterModuleDependences('forum', 'OnCommentTopicAdd', 'tasks',
			'CTaskComments', 'onCommentTopicAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentTopicAdd', 'tasks',
			'CTaskComments', 'onAfterCommentTopicAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentAdd', 'tasks',
			'CTaskComments', 'onAfterCommentAdd');
		UnRegisterModuleDependences('forum', 'OnAfterCommentUpdate', 'tasks',
			'CTaskComments', 'onAfterCommentUpdate');

		UnRegisterModuleDependences('forum', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onForumUninstall');
		UnRegisterModuleDependences('webdav', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onWebdavUninstall');
		UnRegisterModuleDependences('intranet', 'OnModuleUnInstall', 'tasks', 
			'CTasksRarelyTools', 'onIntranetUninstall');

		UnRegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks', 
			'CTaskPlannerMaintance', 'OnAfterTMDayStart');

		UnRegisterModuleDependences('timeman', 'OnAfterTMDayStart', 'tasks',
			'CTaskCountersNotifier', 'onAfterTimeManagerDayStart');

		UnRegisterModuleDependences(
			'timeman',
			'OnAfterTMEntryUpdate',
			'tasks',
			'CTaskTimerManager',
			'onAfterTMEntryUpdate'
		);

		UnRegisterModuleDependences(
			'tasks',
			'OnBeforeTaskUpdate',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskUpdate'
		);

		UnRegisterModuleDependences(
			'tasks',
			'OnBeforeTaskDelete',
			'tasks',
			'CTaskTimerManager',
			'onBeforeTaskDelete'
		);

		UnRegisterModuleDependences('socialnetwork', 'OnBeforeSocNetGroupDelete', 'tasks', 'CTasks', 'onBeforeSocNetGroupDelete');
		UnRegisterModuleDependences("main", "OnAfterRegisterModule", "main", "tasks", "InstallUserFields", "/modules/tasks/install/index.php"); // check webdav UF
		UnRegisterModuleDependences("main", "OnBeforeUserTypeAdd", "tasks", "CTasksRarelyTools", "onBeforeUserTypeAdd");
		UnRegisterModuleDependences("main", "OnBeforeUserTypeUpdate", "tasks", "CTasksRarelyTools", "onBeforeUserTypeUpdate");
		UnRegisterModuleDependences("main", "OnBeforeUserTypeDelete", "tasks", "CTasksRarelyTools", "onBeforeUserTypeDelete");

		// im "ilike"
		UnRegisterModuleDependences("main", "OnGetRatingContentOwner", "tasks", "CTaskNotifications", "OnGetRatingContentOwner");
		UnRegisterModuleDependences("im", "OnGetMessageRatingVote", "tasks", "CTaskNotifications", "OnGetMessageRatingVote");

		if (
			(
				!array_key_exists("savedata", $arParams) 
				|| $arParams["savedata"] != "Y"
			)			
			&& IsModuleInstalled('socialnetwork')
			&& CModule::IncludeModule('socialnetwork')

		)
		{
			$dbRes = CSocNetLog::GetList(
				array(),
				array("EVENT_ID" => "tasks"),
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

		// Remove tasks from IM
		if (IsModuleInstalled('im') && CModule::IncludeModule('im'))
		{
			if (method_exists('CIMNotify', 'DeleteByModule'))
				CIMNotify::DeleteByModule('tasks');
		}			

		// remove comment edit flags
		COption::RemoveOption('tasks', 'task_comment_allow_edit','');
		COption::RemoveOption('tasks', 'task_comment_allow_remove', '');

		return true;
	}


	public static function InstallUserFields($moduleId = 'all')
	{
		global $APPLICATION;

		$errors = array();

		if($moduleId === 'all' || $moduleId === 'disk')
		{
			$errors = self::installDiskUserFields();
		}

		if (
			(($moduleId === 'all') || ($moduleId === 'webdav'))
			&& IsModuleInstalled("webdav")
		)
		{
			$rsUserType = CUserTypeEntity::GetList(
				array(),
				array(
					'ENTITY_ID'  => 'TASKS_TASK',
					'FIELD_NAME' => 'UF_TASK_WEBDAV_FILES',
				)
			);

			if ($rsUserType && ( ! $rsUserType->fetch() ) )
			{
				$CAllUserTypeEntity = new CUserTypeEntity();
				$intID = $CAllUserTypeEntity->add(array(
					'ENTITY_ID'     => 'TASKS_TASK',
					'FIELD_NAME'    => 'UF_TASK_WEBDAV_FILES',
					'USER_TYPE_ID'  => 'webdav_element',
					'XML_ID'        => 'TASK_WEBDAV_FILES',
					'MULTIPLE'      => 'Y',
					'MANDATORY'     =>  null,
					'SHOW_FILTER'   => 'N',
					'SHOW_IN_LIST'  =>  null,
					'EDIT_IN_LIST'  =>  null,
					'IS_SEARCHABLE' =>  null,
					'SETTINGS'      =>  array(
						'IBLOCK_TYPE_ID'        => '0',
						'IBLOCK_ID'             => '',
						'UF_TO_SAVE_ALLOW_EDIT' => ''
					),
					'EDIT_FORM_LABEL' => array(
						'en' => 'Load files',
						'ru' => 'Load files',
						'de' => 'Load files'
					)
				));

				if (
					($intID === false)
					&& ($strEx = $APPLICATION->GetException())
				)
				{
					$errors[] = $strEx->GetString();
				}
			}
		}

		return ($errors);
	}

	public static function installDiskUserFields()
	{
		global $APPLICATION;
		$errors = null;

		if(!IsModuleInstalled('disk'))
		{
			return $errors;
		}

		$uf = new CUserTypeEntity;
		$rsData = CUserTypeEntity::getList(array("ID" => "ASC"), array("ENTITY_ID" => 'TASKS_TASK', "FIELD_NAME" => 'UF_TASK_WEBDAV_FILES'));
		if (!($rsData && ($arRes = $rsData->Fetch())))
		{
			$intID = $uf->add(array(
				'ENTITY_ID'     => 'TASKS_TASK',
				'FIELD_NAME'    => 'UF_TASK_WEBDAV_FILES',
				'USER_TYPE_ID'  => 'disk_file',
				'XML_ID'        => 'TASK_WEBDAV_FILES',
				'MULTIPLE'      => 'Y',
				'MANDATORY'     =>  null,
				'SHOW_FILTER'   => 'N',
				'SHOW_IN_LIST'  =>  null,
				'EDIT_IN_LIST'  =>  null,
				'IS_SEARCHABLE' =>  'Y',
				'EDIT_FORM_LABEL' => array(
					'en' => 'Load files',
					'ru' => 'Load files',
					'de' => 'Load files'
				)
			), false);

			if (false == $intID && ($strEx = $APPLICATION->getException()))
			{
				$errors[] = $strEx->getString();
			}
		}

		return $errors;
	}

	function InstallEvents()
	{

		global $DB;
		$sIn = "'TASK_REMINDER'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events/set_events.php");
		}
		return true;
	}


	function UnInstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events/del_events.php");
		return true;
	}


	function InstallFiles($arParams = array())
	{
		global $DB;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/admin", 
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", 
				false
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/activities",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/activities",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/public/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true,
				true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/public/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
				true,
				true
			);
		}

		return true;
	}


	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");

		DeleteDirFilesEx("/bitrix/js/tasks/");//scripts
		return true;
	}


	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION;

		if (!CBXFeatures::IsFeatureEditable('Tasks'))
		{
			$this->errors = array(GetMessage('MAIN_FEATURE_ERROR_EDITABLE'));
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(
				GetMessage('TASKS_INSTALL_TITLE'),
				$_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php');
		}
		elseif (!IsModuleInstalled("tasks"))
		{
			$this->InstallFiles();
			$this->InstallDB();
			$this->InstallEvents();

			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/step1.php");
		}
	}


	function DoUninstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$GLOBALS["CACHE_MANAGER"]->CleanAll();
			$this->UnInstallDB(array(
				"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			$this->UnInstallEvents();
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TASKS_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/unstep2.php");
		}
	}
}
