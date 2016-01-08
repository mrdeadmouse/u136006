<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (defined('IM_COMPONENT_INIT'))
	return;
else
	define("IM_COMPONENT_INIT", true);

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

CModule::IncludeModule('voximplant');
CModule::IncludeModule('disk');

$arParams["DESKTOP"] = isset($arParams['DESKTOP']) && $arParams['DESKTOP'] == 'Y'? 'Y': 'N';

$arResult = Array();

// Counters
$arResult["COUNTERS"] = CUserCounter::GetValues($USER->GetID(), SITE_ID);

CIMContactList::SetOnline(null, $arParams['DESKTOP'] == 'N');

$arSettings = CIMSettings::Get();
$arResult['SETTINGS'] = $arSettings['settings'];

if ($arParams['DESKTOP'] == 'Y')
{
	$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "im-desktop");
	CIMMessenger::SetDesktopStatusOnline();
	CIMMessenger::SetDesktopVersion(empty($_GET['BXD_API_VERSION'])? 0 : $_GET['BXD_API_VERSION']);
}

if ($arResult['SETTINGS']['bxdNotify'] && CIMMessenger::CheckInstallDesktop())
{
	CIMSettings::SetSetting(CIMSettings::SETTINGS, Array('bxdNotify' => false));
	$arResult['SETTINGS']['bxdNotify'] = false;
}

$arParams["INIT"] = 'Y';
$arParams["DESKTOP_LINK_OPEN"] = 'N';

// Exchange
$arResult["PATH_TO_USER_MAIL"] = "";
$arResult["MAIL_COUNTER"] = 0;
if ($arParams["INIT"] == 'Y')
{
	if (CIMMail::IsExternalMailAvailable())
	{
		$arResult["PATH_TO_USER_MAIL"] = $arParams['PATH_TO_SONET_EXTMAIL'];
		$arResult["MAIL_COUNTER"] = intval($arResult["COUNTERS"]["mail_unseen"]);
	}
	else if (CModule::IncludeModule("dav"))
	{
		$ar = CDavExchangeMail::GetTicker($GLOBALS["USER"]);
		if ($ar !== null)
		{
			$arResult["PATH_TO_USER_MAIL"] = $ar["exchangeMailboxPath"];
			$arResult["MAIL_COUNTER"] = intval($ar["numberOfUnreadMessages"]);
		}
	}
}

// Message & Notify
if ($arParams["INIT"] == 'Y')
{
	$arRecent = Array();
	$arResult['CHAT'] = Array(
		'chat' => Array(),
		'userInChat' => Array(),
	);

	if ($arParams['DESKTOP'] == 'Y')
	{
		$CIMContactList = new CIMContactList();
		$arResult['CONTACT_LIST'] = $CIMContactList->GetList();

		foreach ($arResult['CONTACT_LIST']['chats'] as $key => $value)
		{
			$value['fake'] = true;
			$arResult['CHAT']['chat'][$key] = $value;
		}

		$arRecent = CIMContactList::GetRecentList(Array(
			'LOAD_LAST_MESSAGE' => 'Y',
			'USE_TIME_ZONE' => 'N',
			'USE_SMILES' => 'N'
		));
		$arResult['RECENT'] = Array();

		$arSmile = CIMMessenger::PrepareSmiles();
		$arResult['PATH_TO_IM'] = '/desktop_app/im.ajax.php';
		$arResult['PATH_TO_CALL'] = '/desktop_app/call.ajax.php';
		$arResult['PATH_TO_FILE'] = '/desktop_app/file.ajax.php';

		$arResult['SMILE'] = $arSmile['SMILE'];
		$arResult['SMILE_SET'] = $arSmile['SMILE_SET'];
		$arResult['SETTINGS_NOTIFY_BLOCKED'] = CIMSettings::GetSimpleNotifyBlocked();
	}
	else
	{
		$arResult['RECENT'] = false;
		$arResult['CONTACT_LIST'] = Array(
			'users' => Array(),
			'groups' => Array(),
			'userInGroup' => Array(),
			'woGroups' => Array(),
			'woUserInGroup' => Array()
		);
		$arResult['SMILE'] = false;
		$arResult['SMILE_SET'] = false;
		$arResult['SETTINGS_NOTIFY_BLOCKED'] = Array();
	}

	$CIMNotify = new CIMNotify();
	$arResult['NOTIFY'] = $CIMNotify->GetUnreadNotify(Array('GET_ONLY_FLASH' => 'Y', 'USE_TIME_ZONE' => 'N'));
	$arResult['NOTIFY']['flashNotify'] = CIMNotify::GetFlashNotify($arResult['NOTIFY']['unreadNotify']);
	$arResult["NOTIFY_COUNTER"] = $arResult['NOTIFY']['countNotify']; // legacy

	$CIMMessage = new CIMMessage();
	$arResult['MESSAGE'] = $CIMMessage->GetUnreadMessage(Array('USE_TIME_ZONE' => 'N', 'ORDER' => 'ASC'));
	$arResult["MESSAGE_COUNTER"] = $arResult['MESSAGE']['countMessage']; // legacy

	$CIMChat = new CIMChat();
	$arChatMessage = $CIMChat->GetUnreadMessage(Array('USE_TIME_ZONE' => 'N', 'ORDER' => 'ASC'));
	if ($arChatMessage['result'])
	{
		foreach ($arChatMessage['message'] as $id => $ar)
		{
			$ar['recipientId'] = 'chat'.$ar['recipientId'];
			$arResult['MESSAGE']['message'][$id] = $ar;
		}

		foreach ($arChatMessage['usersMessage'] as $chatId => $ar)
			$arResult['MESSAGE']['usersMessage']['chat'.$chatId] = $ar;

		foreach ($arChatMessage['unreadMessage'] as $chatId => $ar)
			$arResult['MESSAGE']['unreadMessage']['chat'.$chatId] = $ar;

		foreach ($arChatMessage['users'] as $key => $value)
			$arResult['MESSAGE']['users'][$key] = $value;

		foreach ($arChatMessage['userInGroup'] as $key => $value)
			$arResult['MESSAGE']['userInGroup'][$key] = $value;

		foreach ($arChatMessage['files'] as $key => $value)
			$arResult['MESSAGE']['files'][$key] = $value;

		foreach ($arChatMessage['woUserInGroup'] as $key => $value)
			$arResult['MESSAGE']['woUserInGroup'][$key] = $value;

		if ($arParams['DESKTOP'] == 'Y')
		{
			foreach ($arChatMessage['chat'] as $key => $value)
				$arResult['CHAT']['chat'][$key] = $value;
		}
		else
		{
			foreach ($arChatMessage['chat'] as $key => $value)
			{
				$value['fake'] = true;
				$arResult['CHAT']['chat'][$key] = $value;
			}
		}

		foreach ($arChatMessage['userInChat'] as $key => $value)
			$arResult['CHAT']['userInChat'][$key] = $value;

		foreach ($arChatMessage['userChatBlockStatus'] as $key => $value)
			$arResult['CHAT']['userChatBlockStatus'][$key] = $value;
	}
	$arResult['MESSAGE']['flashMessage'] = CIMMessage::GetFlashMessage($arResult['MESSAGE']['unreadMessage']);
	$arResult["MESSAGE_COUNTER"] = $arResult['MESSAGE']['countMessage']+$arChatMessage['countMessage']; // legacy
	foreach ($arRecent as $userId => $value)
	{
		if ($value['TYPE'] == IM_MESSAGE_GROUP)
		{
			if (!isset($arResult['CHAT']['chat'][$value['CHAT']['id']]))
			{
				$value['CHAT']['fake'] = true;
				$arResult['CHAT']['chat'][$value['CHAT']['id']] = $value['CHAT'];
			}
			$value['MESSAGE']['userId'] = $userId;
			$value['MESSAGE']['recipientId'] = $userId;
		}
		else
		{
			if ($arParams['DESKTOP'] == 'N')
				$arResult['CONTACT_LIST']['users'][$value['USER']['id']] = $value['USER'];
			$value['MESSAGE']['userId'] = $userId;
			$value['MESSAGE']['recipientId'] = $userId;
		}
		$arResult['RECENT'][] = $value['MESSAGE'];
	}

	// Merge message users with contact list
	if (isset($arResult['MESSAGE']['users']) && !empty($arResult['MESSAGE']['users']))
	{
		foreach ($arResult['MESSAGE']['users'] as $arUser)
			$arResult['CONTACT_LIST']['users'][$arUser['id']] = $arUser;

		if (isset($arResult['MESSAGE']['userInGroup']))
		{
			foreach ($arResult['MESSAGE']['userInGroup'] as $arUserInGroup)
			{
				if (isset($arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users']))
					$arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users'] = array_unique(array_merge($arResult['CONTACT_LIST']['userInGroup'][$arUserInGroup['id']]['users'], $arUserInGroup['users']));
				else
				{
					if (isset($arResult['CONTACT_LIST']['userInGroup']['other']['users']))
						$arResult['CONTACT_LIST']['userInGroup']['other']['users'] = array_unique(array_merge($arResult['CONTACT_LIST']['userInGroup']['other']['users'], $arUserInGroup['users']));
					else
					{
						$arUserInGroup['id'] = 'other';
						$arResult['CONTACT_LIST']['userInGroup']['other'] = $arUserInGroup;
					}
				}
			}
		}
		if (isset($arResult['MESSAGE']['woUserInGroup']))
		{
			foreach ($arResult['MESSAGE']['woUserInGroup'] as $arWoUserInGroup)
			{
				if (isset($arResult['CONTACT_LIST']['woUserInGroup'][$arWoUserInGroup['id']]['users']))
					$arResult['CONTACT_LIST']['woUserInGroup'][$arWoUserInGroup['id']]['users'] = array_merge($arResult['CONTACT_LIST']['woUserInGroup'][$arWoUserInGroup['id']]['users'], $arWoUserInGroup['users']);
				else
				{
					if (isset($arResult['CONTACT_LIST']['woUserInGroup']['other']['users']))
						$arResult['CONTACT_LIST']['woUserInGroup']['other']['users'] = array_merge($arResult['CONTACT_LIST']['woUserInGroup']['other']['users'], $arWoUserInGroup['users']);
					else
					{
						$arWoUserInGroup['id'] = 'other';
						$arResult['CONTACT_LIST']['woUserInGroup']['other'] = $arWoUserInGroup;
					}
				}
			}
		}
	}
	if (!isset($arResult['CONTACT_LIST']['users'][$USER->GetID()]))
	{
		$arUsers = CIMContactList::GetUserData(array(
			'ID' => $USER->GetID(),
			'DEPARTMENT' => 'N',
			'USE_CACHE' => 'Y',
			'SHOW_ONLINE' => 'N'
		));
		$arResult['CONTACT_LIST']['users'][$USER->GetID()] = $arUsers['users'][$USER->GetID()];
	}
	$arResult['CURRENT_TAB'] = CIMMessenger::GetCurrentTab();
}
else
{
	$arResult['SETTINGS_NOTIFY_BLOCKED'] = CIMSettings::GetSimpleNotifyBlocked();
}
$arResult['DESKTOP'] = $arParams['DESKTOP'] == 'Y'? 'true': 'false';
$arResult["INIT"] = $arParams['INIT'];
$arResult['DESKTOP_LINK_OPEN'] = $arParams['DESKTOP_LINK_OPEN'] == 'Y'? 'true': 'false';
$arResult['PATH_TO_USER_PROFILE_TEMPLATE'] = CIMContactList::GetUserPath();
$arResult['PATH_TO_USER_PROFILE'] = CIMContactList::GetUserPath($USER->GetId());

$arResult['TURN_SERVER'] = COption::GetOptionString('im', 'turn_server');
$arResult['TURN_SERVER_FIREFOX'] = COption::GetOptionString('im', 'turn_server_firefox');
$arResult['TURN_SERVER_LOGIN'] = COption::GetOptionString('im', 'turn_server_login');
$arResult['TURN_SERVER_PASSWORD'] = COption::GetOptionString('im', 'turn_server_password');

CIMMessenger::InitCounters($USER->GetID());

$jsInit = array('im');
if (defined('LANGUAGE_ID') && LANGUAGE_ID == 'ru')
{
	$jsInit[] = 'translit';
}
CJSCore::Init($jsInit);

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>