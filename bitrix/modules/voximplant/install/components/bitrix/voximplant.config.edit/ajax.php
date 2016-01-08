<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	die();
}

if (!CVoxImplantMain::CheckAccess())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	die();
}

if (check_bitrix_sessid())
{
	if ($_POST['VI_SIP_CHECK'])
	{
		$arSend['ERROR'] = '';

		$viSip = new CVoxImplantSip();
		$result = $viSip->GetSipRegistrations($_POST['REG_ID']);
		if ($result)
		{
			$arSend = Array(
				'REG_ID' => $result->reg_id,
				'REG_LAST_UPDATED' => $result->last_updated,
				'REG_ERROR_MESSAGE' => $result->error_message,
				'REG_CODE' => $result->status_code,
				'REG_STATUS' => $result->status_result,
				'ERROR' => '',
			);
		}
		else
		{
			$arSend['ERROR'] = $viSip->GetError()->msg;
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
	}
}
else
{
	echo CUtil::PhpToJsObject(Array(
		'BITRIX_SESSID' => bitrix_sessid(),
		'ERROR' => 'SESSION_ERROR'
	));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>