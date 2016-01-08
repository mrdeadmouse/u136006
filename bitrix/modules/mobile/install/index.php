<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class mobile extends CModule
{
	var $MODULE_ID = "mobile";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function mobile()
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

		$this->MODULE_NAME = GetMessage('APP_MODULE_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('APP_MODULE_DESCRIPTION');
	}

	function InstallDB()
	{
		RegisterModule("mobile");
		RegisterModuleDependences("pull", "OnGetDependentModule", "mobile", "CMobileEvent", "PullOnGetDependentModule");
		RegisterModuleDependences("main", "OnApplicationsBuildList", "main", 'MobileApplication', "OnApplicationsBuildList", 100, "modules/mobile/classes/general/mobile_event.php");
		return true;
	}

	function UnInstallDB($arParams = array())
	{
		UnRegisterModuleDependences("pull", "OnGetDependentModule", "mobile", "CMobileEvent", "PullOnGetDependentModule");
		UnRegisterModuleDependences("main", "OnApplicationsBuildList", "main", 'MobileApplication', "OnApplicationsBuildList", 100, "modules/mobile/classes/general/mobile_event.php");
		UnRegisterModule("mobile");
		return true;
	}

	function InstallFiles()
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/public/mobile/", $_SERVER["DOCUMENT_ROOT"]."/mobile/", True, True);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/install/templates/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/", True, True);
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/install/components/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
			true, true
		);
		$default_site_id = CSite::GetDefSite();
		if ($default_site_id)
		{
			$arAppTempalate = Array(
				"SORT" => 1,
				"CONDITION" => "CSite::InDir('/mobile/')",
				"TEMPLATE" => "mobile_app"
			);

			$arFields = Array("TEMPLATE"=>Array());
			$dbTemplates = CSite::GetTemplateList($default_site_id);
			$mobileAppFound = false;
			while($template = $dbTemplates->Fetch())
			{
				if ($template["TEMPLATE"] == "mobile_app")
				{
					$mobileAppFound = true;
					$template = $arAppTempalate;
				}
				$arFields["TEMPLATE"][] = array(
					"TEMPLATE" => $template['TEMPLATE'],
					"SORT" => $template['SORT'],
					"CONDITION" => $template['CONDITION']
				);
			}
			if (!$mobileAppFound)
				$arFields["TEMPLATE"][] = $arAppTempalate;

			$obSite = new CSite;
			$arFields["LID"] = $default_site_id;
			$obSite->Update($default_site_id, $arFields);
		}

		CUrlRewriter::ReindexFile("/mobile/webdav/index.php");
		CUrlRewriter::ReindexFile("/mobile/disk/index.php");

		CUrlRewriter::Add(
			array(
				"CONDITION" => "#^/mobile/disk/(?<hash>[0-9]+)/download#",
				"RULE" => "download=1&objectId=\$1",
				"ID" => "bitrix:mobile.disk.file.detail",
				"PATH" => "/mobile/disk/index.php",
			)
		);

		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFilesEx('/mobile');
			DeleteDirFilesEx('/bitrix/templates/mobile_app/');
		}
		return true;
	}

	function InstallPull()
	{
		if (!IsModuleInstalled('pull') && file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/pull/install/index.php"))
		{
			include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/pull/install/index.php");
			$pull = new pull();
			$pull->InstallFiles();
			$pull->InstallDB();
		}
	}

    function InstallMobileApp()
    {
        if (!IsModuleInstalled('mobileapp') && file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/mobileapp/install/index.php"))
        {
            include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/mobileapp/install/index.php");
            $pull = new mobileapp();
            $pull->InstallFiles();
            $pull->InstallDB();
        }
    }

	function DoInstall()
	{
		global $USER, $DB, $APPLICATION;
		if(!$USER->IsAdmin())
			return;

		$this->InstallDB();
		$this->InstallFiles();
        $this->InstallMobileApp();
		$this->InstallPull();

		$APPLICATION->IncludeAdminFile(GetMessage("APP_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/install/step.php");
	}

	function DoUninstall()
	{
		global $USER, $DB, $APPLICATION, $step;
		if($USER->IsAdmin())
		{
			$step = IntVal($step);
			if($step < 2)
			{

				$APPLICATION->IncludeAdminFile(GetMessage("APP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB();
				$this->UnInstallFiles();
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("APP_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mobile/install/unstep.php");
			}
		}
	}
}
?>