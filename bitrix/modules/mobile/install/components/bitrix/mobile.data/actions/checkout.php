<?if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var $APPLICATION Cmain
 * @var $USER CUser
 */
global $APPLICATION, $USER;
use Bitrix\Main;
use Bitrix\Main\Authentication\ApplicationPasswordTable;


if ($_SERVER["REQUEST_METHOD"] == "OPTIONS")
{
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Max-Age: 60');
	header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');
	die('');
}

if (!IsModuleInstalled('bitrix24'))
{
	header('Access-Control-Allow-Origin: *');
}

$data = array(
	"status" => "failed",
	"bitrix_sessid" => bitrix_sessid(),
);

$userData = CHTTP::ParseAuthRequest();
$APPLICATION->RestartBuffer();

$login = $userData["basic"]["username"];
$isAlreadyAuthorized = $USER->IsAuthorized();

if (!$isAlreadyAuthorized)
{
	if($isAlreadyAuthorized)
	{
		$USER->Logout();
	}

	if (IsModuleInstalled('bitrix24'))
	{
		header('Access-Control-Allow-Origin: *');
	}

	if($login)
	{
		if(CModule::IncludeModule('bitrix24') && ($captchaInfo = CBitrix24::getStoredCaptcha()))
		{
			$data["captchaCode"] = $captchaInfo["captchaCode"];
			$data["captchaURL"] = $captchaInfo["captchaURL"];
		}
		elseif($APPLICATION->NeedCAPTHAForLogin($login))
		{
			$data["captchaCode"] = $APPLICATION->CaptchaGetCode();
		}

		if (CModule::IncludeModule("security") && \Bitrix\Security\Mfa\Otp::isOtpRequired())
		{
			$data["needOtp"] = true;
		}
	}

	CHTTP::SetStatus("401 Unauthorized");
}
else
{
	$bExtranetInstalled = (
		CModule::IncludeModule("extranet")
		&& CExtranet::GetExtranetSiteID()
	);

	$arSelectParams = array(
		"FIELDS" => array("PERSONAL_PHOTO")
	);

	if ($bExtranetInstalled)
	{
		$arSelectParams["SELECT"] = array("UF_DEPARTMENT");
	}

	$dbuser = CUser::GetList(
		($by = array("last_name" => "asc", "name" => "asc")),
		($order = false),
		Array("ID" => $USER->GetID()),
		$arSelectParams
	);
	$curUser = $dbuser->Fetch();
	$img_src = "";
	if (intval($curUser["PERSONAL_PHOTO"]) > 0)
	{
		$arImage = CFile::ResizeImageGet(
			$curUser["PERSONAL_PHOTO"],
			array("width" => 64, "height" => 64),
			BX_RESIZE_IMAGE_EXACT,
			false
		);
		if (
			$arImage
			&& strlen($arImage["src"]) > 0
		)
		{
			$img_src = $arImage["src"];
		}
	}

	$bExtranetUser = ($bExtranetInstalled && intval($curUser["UF_DEPARTMENT"][0]) <= 0);

	if (
		!$bExtranetUser
		|| CMobile::getApiVersion() >= 9
		|| intval($_GET["api_version"]) >= 9
	)
	{
		$data = array(
			"status" => "success",
			"page_mark" => "<!--bitrix_mobile_app-->",
			"sessid_md5" => bitrix_sessid(),
			"target" => md5($USER->GetID() . CMain::GetServerUniqID()),
			"photoUrl" => $img_src,
			"useModernStyle"=>true,
			"appmap" => Array(
				"main" => Array("url" => $params["START_PAGE"]? $params["START_PAGE"]:"", "bx24ModernStyle"=>true),
				"menu" => Array("url" => $params["MENU_PAGE"]?$params["MENU_PAGE"]:""),
			)
		);
		if (\Bitrix\MobileApp\Mobile::getInstance()->getApiVersion() >= 10 && strlen($params["CHAT_PAGE"])>0)
		{
			$data["appmap"]["right"] = Array("url" => $params["CHAT_PAGE"]);
		}

		if ($bExtranetUser)
		{
			$rsSites = CSite::GetByID(CExtranet::GetExtranetSiteID());
			if (
				($arExtranetSite = $rsSites->Fetch())
				&& ($arExtranetSite["ACTIVE"] != "N")
			)
			{
				$data["whiteList"] = array($arExtranetSite["DIR"] . "mobile/");
				$data["appmap"] = array(
					"main" => Array("url" => $arExtranetSite["DIR"] . "mobile/index.php", "bx24ModernStyle"=>true),
					"menu" => Array("url" => $arExtranetSite["DIR"] . "mobile/left.php"),
					"right" => Array("url" => $arExtranetSite["DIR"] . "mobile/im/right.php")
				);
				if (\Bitrix\MobileApp\Mobile::getInstance()->getApiVersion() >= 10)
				{
					$data["appmap"]["right"] = Array("url" => $arExtranetSite["DIR"] . "mobile/im/right.php");
				}
			}
		}

		if (toUpper(SITE_CHARSET) != "UTF-8")
		{
			$data = $APPLICATION->ConvertCharsetArray($data, SITE_CHARSET, "utf-8");
		}
	}
	$needAppPass = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_PASS");
	$appUUID = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_UUID");
	$deviceName = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_DEVICE_NAME");

	if ($needAppPass == 'mobile' && $USER->GetParam("APPLICATION_ID") === null)
	{

		if(strlen($appUUID) > 0)
		{
			$result = ApplicationPasswordTable::getList(Array(
				'select' => Array('ID'),
				'filter' => Array(
					'USER_ID' => $USER->GetID(),
					'CODE' => $appUUID
				)
			));
			if ($row = $result->fetch())
			{
				ApplicationPasswordTable::delete($row['ID']);
			}
		}

		$password = ApplicationPasswordTable::generatePassword();

		$res = ApplicationPasswordTable::add(array(
			'USER_ID' => $USER->GetID(),
			'APPLICATION_ID' => 'mobile',
			'PASSWORD' => $password,
			'CODE'=> $appUUID,
			'DATE_CREATE' => new Main\Type\DateTime(),
			'COMMENT' => GetMessage("MD_GENERATE_BY_MOBILE").(strlen($deviceName)>0 ? " (".$deviceName.")" : ""),
			'SYSCOMMENT' =>GetMessage("MD_MOBILE_APPLICATION")
		));

		if ($res->isSuccess())
		{
			$data["appPassword"] = $password;
		}
	}
}


return $data;
?>