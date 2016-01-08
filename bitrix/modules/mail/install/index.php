<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class mail extends CModule
{
	var $MODULE_ID = "mail";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function mail()
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
			$this->MODULE_VERSION = MAIL_VERSION;
			$this->MODULE_VERSION_DATE = MAIL_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("MAIL_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("MAIL_MODULE_DESC");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		// Database tables creation
		if(!$DB->Query("SELECT 'x' FROM b_mail_mailbox WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			RegisterModule("mail");

			if (CModule::IncludeModule("mail"))
			{
				$result = Bitrix\Mail\MailServicesTable::getList(array('filter' => array('ACTIVE' => 'Y')));
				if ($result->fetch() === false)
				{
					$mailServices = array(
						'gmail' => array(
							'SERVER' => 'imap.gmail.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'https://mail.google.com/',
						),
						'icloud' => array(
							'SERVER' => 'imap.mail.me.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'https://www.icloud.com/#mail',
						),
						'outlook.com' => array(
							'SERVER' => 'imap-mail.outlook.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'https://www.outlook.com/owa',
						),
						'office365' => array(
							'SERVER' => 'outlook.office365.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'http://mail.office365.com/',
						),
						'yahoo' => array(
							'SERVER' => 'imap.mail.yahoo.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'http://mail.yahoo.com/',
						),
						'aol' => array(
							'SERVER' => 'imap.aol.com', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'http://mail.aol.com/',
						),
						'yandex' => array(
							'SERVER' => 'imap.yandex.ru', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'https://mail.yandex.ru/',
						),
						'mail.ru' => array(
							'SERVER' => 'imap.mail.ru', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'http://e.mail.ru/',
						),
						'ukr.net' => array(
							'SERVER' => 'imap.ukr.net', 'PORT' => 993, 'ENCRYPTION' => 'Y', 'LINK' => 'http://freemail.ukr.net/',
						),
						'exchange' => array(),
						'other' => array(),
					);

					$mailServicesByLang = array(
						'ru' => array(
							100  => 'gmail',
							200  => 'outlook.com',
							300  => 'icloud',
							400  => 'office365',
							500  => 'exchange',
							600  => 'yahoo',
							700  => 'aol',
							800  => 'yandex',
							900  => 'mail.ru',
							1000 => 'ukr.net',
							1100 => 'other'
						),
						'ua' => array(
							100  => 'gmail',
							200  => 'outlook.com',
							300  => 'icloud',
							400  => 'office365',
							500  => 'exchange',
							600  => 'yahoo',
							700  => 'aol',
							800  => 'yandex',
							900  => 'mail.ru',
							1000 => 'ukr.net',
							1100 => 'other'
						),
						'en' => array(
							100 => 'gmail',
							200 => 'outlook.com',
							300 => 'icloud',
							400 => 'office365',
							500 => 'exchange',
							600 => 'yahoo',
							700 => 'aol',
							800 => 'other'
						),
						'de' => array(
							100 => 'gmail',
							200 => 'outlook.com',
							300 => 'icloud',
							400 => 'office365',
							500 => 'exchange',
							600 => 'yahoo',
							700 => 'aol',
							800 => 'other'
						)
					);

					$result = \Bitrix\Main\SiteTable::getList();
					while (($site = $result->fetch()) !== false)
					{
						if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite($site['LID']))
							continue;

						$mailServicesList = isset($mailServicesByLang[$site['LANGUAGE_ID']])
							? $mailServicesByLang[$site['LANGUAGE_ID']]
							: $mailServicesByLang['en'];
						foreach ($mailServicesList as $serviceSort => $serviceName)
						{
							$serviceSettings = $mailServices[$serviceName];

							$serviceSettings['SITE_ID']      = $site['LID'];
							$serviceSettings['ACTIVE']       = 'Y';
							$serviceSettings['SERVICE_TYPE'] = 'imap';
							$serviceSettings['NAME']         = $serviceName;
							$serviceSettings['SORT']         = $serviceSort;

							Bitrix\Mail\MailServicesTable::add($serviceSettings);
						}
					}
				}
			}

			RegisterModuleDependences('rest', 'OnRestServiceBuildDescription', 'mail', 'CMailRestService', 'OnRestServiceBuildDescription');

			RegisterModuleDependences('main', 'OnAfterUserUpdate', 'mail', 'CMail', 'onUserUpdate');
			RegisterModuleDependences('main', 'OnAfterUserDelete', 'mail', 'CMail', 'onUserDelete');

			CAgent::AddAgent("CMailbox::CleanUp();", "mail", "N", 60*60*24);

			return true;
		}
	}

	public function installBitrix24MailService()
	{
		if (CModule::IncludeModule("mail"))
		{
			$result = \Bitrix\Main\SiteTable::getList();
			while (($site = $result->fetch()) !== false)
			{
				if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite($site['LID']))
					continue;

				\Bitrix\Mail\MailServicesTable::add(array(
					'SITE_ID'      => $site['LID'],
					'ACTIVE'       => 'Y',
					'NAME'         => 'bitrix24',
					'SERVICE_TYPE' => 'controller'
				));
			}
		}
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		//delete agents
		CAgent::RemoveModuleAgents("mail");

		UnRegisterModule("mail");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
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
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/images", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/mail", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/tools/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools");
		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		DeleteDirFilesEx("/bitrix/themes/.default/icons/mail/");//icons
		DeleteDirFilesEx("/bitrix/images/mail/");//images
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION, $step;

		if(!CBXFeatures::IsFeatureEditable("SMTP"))
		{
			$APPLICATION->ThrowException(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
		}
		else
		{
			$this->InstallFiles();
			$this->InstallDB();
			CBXFeatures::SetFeatureEnabled("SMTP", true);
		}
		$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/step1.php");
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $step;
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/unstep1.php");
		}
		elseif($step == 2)
		{
			$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
			));
			$this->UnInstallFiles();
			CBXFeatures::SetFeatureEnabled("SMTP", false);
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("MAIL_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mail/install/unstep2.php");
		}
	}
}
?>