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
	if ($_POST['VI_GET_COUNTRY'] == 'Y')
	{
		$arSend['ERROR'] = '';
		$result = CVoxImplantPhone::GetPhoneCategories();
		if (!empty($result))
		{
			$arSend['RESULT'] = $result;
		}
		else
		{
			$arSend['ERROR'] = 'ERROR';
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_GET_STATE'] == 'Y')
	{
		$arSend['ERROR'] = '';
		$result = CVoxImplantPhone::GetPhoneCountryStates($_POST['COUNTRY_CODE'], $_POST['COUNTRY_CATEGORY']);
		if ($result !== false)
		{
			$arSend['RESULT'] = $result;
		}
		else
		{
			$arSend['ERROR'] = 'ERROR';
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_GET_REGION'] == 'Y')
	{
		$arSend['ERROR'] = '';
		$result = CVoxImplantPhone::GetPhoneRegions($_POST['COUNTRY_CODE'], $_POST['COUNTRY_STATE'], $_POST['COUNTRY_CATEGORY']);
		if ($result !== false)
		{
			$arSend['RESULT'] = $result;
		}
		else
		{
			$arSend['ERROR'] = 'ERROR';
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_GET_PHONE_NUMBERS'] == 'Y')
	{
		$arSend['ERROR'] = '';
		$result = CVoxImplantPhone::GetPhoneNumbers($_POST['COUNTRY_CODE'], $_POST['COUNTRY_REGION'], $_POST['COUNTRY_CATEGORY']);
		if ($result !== false)
		{
			$arSend['RESULT'] = $result;
		}
		else
		{
			$arSend['ERROR'] = 'ERROR';
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_RENT_NUMBER'] == 'Y')
	{
		$arSend['ERROR'] = '';

		$ViAccount = new CVoxImplantAccount();
		$accountBalance = $ViAccount->GetAccountBalance(true);

		$orm = Bitrix\Voximplant\ConfigTable::getList(Array(
			'filter'=>Array(
				'=SEARCH_ID' => $_POST['CURRENT_NUMBER']
			)
		));
		if ($orm->fetch())
		{
			$arSend['ERROR'] = 'ATTACHED';
		}
		else if (floatval($_POST['PRE_MONEY_CHECK']) <= $accountBalance)
		{
			$result = CVoxImplantPhone::AttachPhoneNumber($_POST['COUNTRY_CODE'], $_POST['REGION_ID'], $_POST['CURRENT_NUMBER'], $_POST['COUNTRY_STATE'], $_POST['COUNTRY_CATEGORY']);
			if (!empty($result))
			{
				$arSend['RESULT'] = $result[0];
			}
			else
			{
				$arSend['ERROR'] = 'ERROR';
			}
		}
		else
		{
			$arSend['ERROR'] = 'NO_MONEY';
		}

		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_UNLINK_NUMBER'] == 'Y')
	{
		$arSend['ERROR'] = '';

		$_POST['NUMBER_ID'] = intval($_POST['NUMBER_ID']);
		if ($_POST['NUMBER_ID'] > 0)
		{
			$result = Bitrix\Voximplant\ConfigTable::getById($_POST['NUMBER_ID']);
			if ($row = $result->fetch())
			{
				$result = CVoxImplantPhone::EnqueueDeactivatePhoneNumber($row['SEARCH_ID']);
				if (!$result)
				{
					$arSend['ERROR'] = 'ERROR';
				}
			}
		}

		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_CANCEL_UNLINK_NUMBER'] == 'Y')
	{
		$arSend['ERROR'] = '';

		$_POST['NUMBER_ID'] = intval($_POST['NUMBER_ID']);
		if ($_POST['NUMBER_ID'] > 0)
		{
			$result = Bitrix\Voximplant\ConfigTable::getById($_POST['NUMBER_ID']);
			if ($row = $result->fetch())
			{
				$result = CVoxImplantPhone::CancelDeactivatePhoneNumber($row['SEARCH_ID']);
				if (!$result)
				{
					$arSend['ERROR'] = 'ERROR';
				}
			}
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