<?
define("IM_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/component.php');

class CVNSetupAjax
{
	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!CModule::IncludeModule('voximplant'))
			$error = 'Module voximplant is not installed.';
		else if (!is_object($USER) || !$USER->IsAuthorized())
			$error = GetMessage('ACCESS_DENIED');
		else if (!CVoxImplantMain::CheckAccess())
			$error = GetMessage('ACCESS_DENIED');
		else if (!check_bitrix_sessid())
			$error = GetMessage('ACCESS_DENIED');
		else if ($_REQUEST["act"] == "edit")
			$result = self::executeEditPhones($error);
		else if ($_REQUEST["act"] == "getInfo")
			$result = self::executeGetInfo($error);
		else if ($_REQUEST["act"] == "option")
			$result = self::executeSaveOption($error);

		self::returnJson(array_merge(array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		), $result));
	}

	private static function executeSaveOption(&$error)
	{
		$error = !CVoxImplantConfig::SetPortalNumber($_REQUEST["portalNumber"]);
		return array();
	}

	private static function executeGetInfo(&$error)
	{
		$userId = intval($_REQUEST['USER_ID']);

		$viUser = new CVoximplantUser();
		$userInfo = $viUser->GetUserInfo($userId, true);
		if (!is_array($userInfo))
		{
			$error = $viUser->GetError()->msg;
		}
		unset($userInfo['user_password']);
		return $userInfo;
	}

	private static function executeEditPhones(&$error)
	{
		global $USER_FIELD_MANAGER;

		$userId = intval($_REQUEST['USER_ID']);
		$obUser = new CUser;
		$arFields = array(
			"UF_VI_BACKPHONE" => $_REQUEST["UF_VI_BACKPHONE"],
			"UF_PHONE_INNER" => $_REQUEST["UF_PHONE_INNER"],
		);

		$viUser = new CVoximplantUser();

		if (isset($_REQUEST["UF_VI_PHONE"]))
		{
			if ($_REQUEST["UF_VI_PHONE"] == 'N')
			{
				$viUser->UpdateUserPassword($_REQUEST['USER_ID'], CVoxImplantUser::MODE_PHONE);
				unset($_REQUEST["UF_VI_PHONE_PASSWORD"]);
			}
			$viUser->SetPhoneActive($_REQUEST['USER_ID'], $_REQUEST["UF_VI_PHONE"] == "Y" ? true : false);
		}

		if (isset($_REQUEST["UF_VI_PHONE_PASSWORD"]) && trim($_REQUEST["UF_VI_PHONE_PASSWORD"]) )
		{
			$pass = $viUser->UpdateUserPassword($_REQUEST['USER_ID'], CVoxImplantUser::MODE_PHONE, $_REQUEST["UF_VI_PHONE_PASSWORD"]);
			if (!$pass)
			{
				$error = $viUser->GetError()->msg;
			}
		}

		$USER_FIELD_MANAGER->EditFormAddFields("USER", $arFields);

		if(!$obUser->Update($userId, $arFields, true))
			$error = $obUser->LAST_ERROR;

		$arUser = CUser::GetList(($by="ID"), ($order="ASC"), array('ID' => $userId),
			array(
				'FIELDS' => array('ID', 'LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', "UF_PHONE_INNER", "UF_VI_BACKPHONE", "UF_VI_PHONE", "UF_VI_PHONE_PASSWORD"),
				'SELECT' => array("UF_PHONE_INNER", "UF_VI_BACKPHONE", "UF_VI_PHONE", "UF_VI_PHONE_PASSWORD")
			))->fetch();

		return array(
			'UF_VI_BACKPHONE' => $arUser['UF_VI_BACKPHONE'],
			'UF_PHONE_INNER' => $arUser["UF_PHONE_INNER"],
			'UF_VI_PHONE' => $arUser["UF_VI_PHONE"],
			'UF_VI_PHONE_PASSWORD' => $arUser["UF_VI_PHONE_PASSWORD"]
		);
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();

		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		echo json_encode($data);
		die;
	}
}

CVNSetupAjax::execute();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>