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
	if ($_POST['VI_CONNECT'] == 'Y')
	{
		$ViAccount = new CVoxImplantAccount();
		$accountBalance = $ViAccount->GetAccountBalance(true);
		if ($accountBalance > 0)
		{
			$arSend['ERROR'] = '';
			$result = CVoxImplantPhone::AddCallerID($_POST['NUMBER']);
			if ($result)
			{
				$arSend['NUMBER'] = $result['NUMBER'];
				$arSend['VERIFIED'] = $result['VERIFIED'];
				$arSend['VERIFIED_UNTIL'] = ConvertTimeStamp(MakeTimeStamp($result['VERIFIED_UNTIL'])+CTimeZone::GetOffset()+date("Z"), 'FULL');
			}
			else
			{
				$arSend['ERROR'] = 'CONNECT_ERROR';
			}
		}
		else
		{
			$arSend['ERROR'] = 'MONEY_LOW';
		}

		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_VERIFY'] == 'Y')
	{
		$result = CVoxImplantPhone::VerifyCallerID(CVoxImplantPhone::GetLinkNumber());
		if (!$result)
		{
			$arSend['ERROR'] = 'CONNECT_ERROR';
		}
		else if ($result == 200)
		{
			$arSend['ERROR'] = '';
		}
		else
		{
			$arSend['ERROR'] = $result;
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_ACTIVATE'] == 'Y')
	{
		$arSend['ERROR'] = '';
		$result = CVoxImplantPhone::ActivateCallerID(CVoxImplantPhone::GetLinkNumber(), $_POST['CODE']);
		if ($result)
		{
			$arSend['NUMBER'] = $result['NUMBER'];
			$arSend['VERIFIED'] = $result['VERIFIED'];
			$arSend['VERIFIED_UNTIL'] = ConvertTimeStamp(MakeTimeStamp($result['VERIFIED_UNTIL'])+CTimeZone::GetOffset()+date("Z"), 'FULL');
		}
		else
		{
			$arSend['ERROR'] = 'CONNECT_ERROR';
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_REMOVE'] == 'Y')
	{
		$result = CVoxImplantPhone::DelCallerID(CVoxImplantPhone::GetLinkNumber());
		$arSend['ERROR'] = $result? '': 'CONNECT_ERROR';
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