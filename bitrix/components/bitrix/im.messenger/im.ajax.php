<?
if (!defined('IM_AJAX_INIT'))
{
	define("IM_AJAX_INIT", true);
	define("PUBLIC_AJAX_MODE", true);
	define("NO_KEEP_STATISTIC", "Y");
	define("NO_AGENT_STATISTIC","Y");
	define("NOT_CHECK_PERMISSIONS", true);
	if (!isset($_POST['IM_UPDATE_STATE']) || !isset($_POST['IM_UPDATE_STATE_LIGHT']))
	{
		define("DisableEventsCheck", true);
		define("NO_AGENT_CHECK", true);
	}
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

// NOTICE
// Before execute next code, execute file /module/im/ajax_hit.php
// for skip onProlog events

if (!CModule::IncludeModule("im"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'IM_MODULE_NOT_INSTALLED'));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	die();
}

if(!$USER->IsAuthorized())
{
	$USER->LoginByCookies();
}

if (intval($USER->GetID()) <= 0)
{
	// TODO need change AUTHORIZE ERROR callbacks
	//header("HTTP/1.0 401 Not Authorized");
	//header("Content-Type: application/x-javascript");
	//header("BX-Authorize: ".bitrix_sessid());
	
	echo CUtil::PhpToJsObject(Array(
		'ERROR' => 'AUTHORIZE_ERROR',
		'BITRIX_SESSID' => bitrix_sessid()
	));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
	die();
}

if (check_bitrix_sessid())
{
	if ($_POST['IM_AVATAR_UPDATE'] == 'Y')
	{
		$CFileUploader = new CFileUploader(array(
			"allowUpload" => "I",
			"events" => array("onFileIsUploaded" => array("CIMDisk", "UploadAvatar"))
		));
		if (!$CFileUploader->checkPost())
		{
			echo CUtil::PhpToJsObject(Array(
				'ERROR' => 'UPLOAD_ERROR'
			));
		}
	}
	else if ($_POST['IM_FILE_UPLOAD'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);
		$CFileUploader = new CFileUploader(array(
			"allowUpload" => "A",
			"events" => array(
				"onFileIsUploaded" => array("CIMDisk", "UploadFile")
			)
		));
		if (!$CFileUploader->checkPost())
		{
			echo CUtil::PhpToJsObject(Array(
				'ERROR' => 'UPLOAD_ERROR'
			));
		}
	}
	else if ($_POST['IM_FILE_REGISTER'] == 'Y')
	{
		$errorMessage = '';
		CUtil::decodeURIComponent($_POST);
		$_POST['FILES'] = CUtil::JsObjectToPhp($_POST['FILES']);

		$result = CIMDisk::UploadFileRegister($_POST['CHAT_ID'], $_POST['FILES']);
		if (!$result)
		{
			$errorMessage = 'ERROR';
		}

		echo CUtil::PhpToJsObject(Array(
			'FILE_ID' => $result['FILE_ID'],
			'CHAT_ID' => $_POST['CHAT_ID'],
			'RECIPIENT_ID' => $_POST['RECIPIENT_ID'],
			'MESSAGE_ID' => $result['MESSAGE_ID'],
			'MESSAGE_TMP_ID' => $_POST['MESSAGE_TMP_ID'],
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_FILE_UNREGISTER'] == 'Y')
	{
		$_POST['FILES'] = CUtil::JsObjectToPhp($_POST['FILES']);
		$_POST['MESSAGES'] = CUtil::JsObjectToPhp($_POST['MESSAGES']);

		$result = CIMDisk::UploadFileUnRegister($_POST['CHAT_ID'], $_POST['FILES'], $_POST['MESSAGES']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $result? 'ERROR': ''
		));
	}
	else if ($_POST['IM_FILE_SAVE_TO_DISK'] == 'Y')
	{
		$result = CIMDisk::SaveToLocalDisk($_POST['FILE_ID']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $_POST['CHAT_ID'],
			'FILE_ID' => $_POST['FILE_ID'],
			'NEW_FILE_ID' => $result? $result->getId(): 0,
			'ERROR' => $result? 'ERROR': ''
		));
	}
	else if ($_POST['IM_FILE_UPLOAD_FROM_DISK'] == 'Y')
	{
		$errorMessage = '';
		$_POST['FILES'] = CUtil::JsObjectToPhp($_POST['FILES']);
		$result = CIMDisk::UploadFileFromDisk($_POST['CHAT_ID'], $_POST['FILES']);
		if (!$result)
		{
			$errorMessage = 'ERROR';
		}

		echo CUtil::PhpToJsObject(Array(
			'FILES' => $result['FILES'],
			'CHAT_ID' => $_POST['CHAT_ID'],
			'RECIPIENT_ID' => $_POST['RECIPIENT_ID'],
			'MESSAGE_ID' => $result['MESSAGE_ID'],
			'MESSAGE_TMP_ID' => $_POST['MESSAGE_TMP_ID'],
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_HISTORY_FILES_LOAD'] == 'Y')
	{
		$chatId = intval($_POST['CHAT_ID']);
		$historyPage = intval($_POST['PAGE_ID']);
		$historyPage = $historyPage>0? $historyPage: 1;

		$arFiles = CIMDisk::GetHistoryFiles($chatId, $historyPage);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $chatId,
			'FILES' => $arFiles,
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_FILES_SEARCH'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$chatId = intval($_POST['CHAT_ID']);
		$arFiles = CIMDisk::GetHistoryFilesByName($chatId, $_POST['SEARCH']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $chatId,
			'FILES' => $arFiles,
			'ERROR' => ''
		));
	}
	elseif ($_POST['IM_UPDATE_STATE'] == 'Y')
	{
		if (isset($_POST['DESKTOP']) && $_POST['DESKTOP'] == 'Y')
			CIMMessenger::SetDesktopStatusOnline();

		CIMContactList::SetOnline();

		$arResult["REVISION"] = IM_REVISION;
		$arResult["MOBILE_REVISION"] = IM_MOBILE_REVISION;
		$arResult["DISK_REVISION"] = COption::GetOptionString("disk", "disk_revision_api", -1);

		$arResult['SERVER_TIME'] = time();

		if (isset($_POST['FN']))
		{
			$_POST['FN'] = CUtil::JsObjectToPhp($_POST['FN']);
			if (is_array($_POST['FN']))
			{
				foreach ($_POST['FN'] as $key => $value)
					$_SESSION['IM_FLASHED_NOTIFY'][$key] = $key;
			}
		}

		if (isset($_POST['FM']))
		{
			$_POST['FM'] = CUtil::JsObjectToPhp($_POST['FM']);
			if (is_array($_POST['FM']))
			{
				foreach ($_POST['FM'] as $userId => $data)
					foreach ($data as $key => $value)
						$_SESSION['IM_FLASHED_MESSAGE'][$key] = $key;
			}
		}

		$bOpenMessenger = isset($_POST['OPEN_MESSENGER']) && intval($_POST['OPEN_MESSENGER']) == 1? true: false;

		// Online
		$arOnline = CIMStatus::GetList();

		// Counters
		$arResult["COUNTERS"] = CUserCounter::GetValues($USER->GetID(), $_POST['SITE_ID']);

		if (CIMMail::IsExternalMailAvailable())
		{
			$arResult["MAIL_COUNTER"] = intval($arResult["COUNTERS"]["mail_unseen"]);
		}
		else if (CModule::IncludeModule("dav"))
		{
			// Exchange
			$ar = CDavExchangeMail::GetTicker($GLOBALS["USER"]);
			if ($ar !== null)
				$arResult["MAIL_COUNTER"] = intval($ar["numberOfUnreadMessages"]);
		}

		$arSend = Array(
			'REVISION' => $arResult["REVISION"],
			'MOBILE_REVISION' => $arResult["MOBILE_REVISION"],
			'DISK_REVISION' => $arResult["DISK_REVISION"],
			'USER_ID' => $USER->GetId(),
			'ONLINE' => !empty($arOnline)? $arOnline['users']: array(),
			'COUNTERS' => $arResult["COUNTERS"],
			'MAIL_COUNTER' => $arResult["MAIL_COUNTER"],
			'SERVER_TIME' => time(),
			'ERROR' => ""
		);

		if (CModule::IncludeModule('pull'))
		{
			$arPullConfig = CPullChannel::GetConfig($USER->GetId(), ($_POST['CACHE'] == 'Y'), false, ($_POST['MOBILE'] == 'Y'));
			if (is_array($arPullConfig))
			{
				$arSend['PULL_CONFIG'] = $arPullConfig;
			}
		}
		if ($bOpenMessenger && $_POST['TAB'] != 0)
			CIMMessenger::SetCurrentTab($_POST['TAB']);

		$CIMMessage = new CIMMessage();
		$arMessage = $CIMMessage->GetUnreadMessage(Array(
			'USE_TIME_ZONE' => 'N',
			'ORDER' => 'ASC'
		));
		if ($arMessage['result'])
		{
			CIMMessage::GetFlashMessage($arMessage['unreadMessage']);

			$arSend['MESSAGE'] = $arMessage['message'];
			$arSend['UNREAD_MESSAGE'] = CIMMessenger::CheckXmppStatusOnline()? array(): $arMessage['unreadMessage'];
			$arSend['USERS_MESSAGE'] = $arMessage['usersMessage'];
			$arSend['FILES'] = $arMessage['files'];
			$arSend['USERS'] = $arMessage['users'];
			$arSend['USER_IN_GROUP'] = $arMessage['userInGroup'];
			$arSend['WO_USER_IN_GROUP'] = $arMessage['woUserInGroup'];
			$arSend['ERROR'] = '';
		}

		$CIMChat = new CIMChat();
		$arMessage = $CIMChat->GetUnreadMessage(Array(
			'USE_TIME_ZONE' => 'N',
			'ORDER' => 'ASC'
		));
		if ($arMessage['result'])
		{
			CIMMessage::GetFlashMessage($arMessage['unreadMessage']);

			foreach ($arMessage['message'] as $id => $ar)
			{
				$ar['recipientId'] = 'chat'.$ar['recipientId'];
				$arSend['MESSAGE'][$id] = $ar;
			}

			foreach ($arMessage['usersMessage'] as $chatId => $ar)
				$arSend['USERS_MESSAGE']['chat'.$chatId] = $ar;

			if (!CIMMessenger::CheckXmppStatusOnline())
			{
				foreach ($arMessage['unreadMessage'] as $chatId => $ar)
					$arSend['UNREAD_MESSAGE']['chat'.$chatId] = $ar;
			}

			foreach ($arMessage['files'] as $key => $value)
				$arSend['FILES'][$key] = $value;

			foreach ($arMessage['users'] as $key => $value)
				$arSend['USERS'][$key] = $value;

			foreach ($arMessage['userInGroup'] as $key => $value)
				$arSend['USER_IN_GROUP'][$key] = $value;

			foreach ($arMessage['woUserInGroup'] as $key => $value)
				$arSend['WO_USER_IN_GROUP'][$key] = $value;

			$arSend['CHAT'] = $arMessage['chat'];
			$arSend['USER_BLOCK_CHAT'] = $arMessage['userChatBlockStatus'];
			$arSend['USER_IN_CHAT'] = $arMessage['userInChat'];

			$arSend['ERROR'] = '';
		}

		// Notify
		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetUnreadNotify(Array('USE_TIME_ZONE' => 'N'));
		if ($arNotify['result'])
		{
			$arSend['NOTIFY'] = $arNotify['notify'];
			$arSend['UNREAD_NOTIFY'] = $arNotify['unreadNotify'];
			$arSend['FLASH_NOTIFY'] = CIMNotify::GetFlashNotify($arNotify['unreadNotify']);
			$arSend['ERROR'] = '';
		}
		$arSend['XMPP_STATUS'] = CIMMessenger::CheckXmppStatusOnline()? 'Y':'N';
		$arSend['DESKTOP_STATUS'] = CIMMessenger::CheckDesktopStatusOnline()? 'Y':'N';

		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['IM_UPDATE_STATE_LIGHT'] == 'Y')
	{
		$errorMessage = "";

		CIMContactList::SetOnline();

		$arResult["REVISION"] = IM_REVISION;
		$arResult["MOBILE_REVISION"] = IM_MOBILE_REVISION;
		$arResult["DISK_REVISION"] = COption::GetOptionString("disk", "disk_revision_api", -1);

		$arResult['SERVER_TIME'] = time();
		if (isset($_POST['NOTIFY']))
		{
			$CIMNotify = new CIMNotify();
			$arNotify = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));

			$arResult['COUNTER_NOTIFICATIONS'] = count($arNotify['notify']);
			$arResult['NOTIFY_LAST_ID'] = $arNotify['maxNotify'];
		}
		if (isset($_POST['MESSAGE']))
		{
			$CIMMessage = new CIMMessage();
			$arMessage = $CIMMessage->GetUnreadMessage(Array(
				'SPEED_CHECK' => 'N',
				'LOAD_DEPARTMENT' => 'N',
				'ORDER' => 'ASC',
				'FILE_LOAD' => 'N',
				'GROUP_BY_CHAT' => 'Y',
			));
			$arResult['COUNTER_MESSAGES'] = count($arMessage['message']);

			$arUnread = Array();
			foreach ($arMessage['message'] as $data)
			{
				$arUnread[$data['senderId']]['MESSAGE'] = $data;
				$arUnread[$data['senderId']]['USER'] = $arMessage['users'][$data['senderId']];
			}

			$CIMChat = new CIMChat();
			$arMessage = $CIMChat->GetUnreadMessage(Array(
				'SPEED_CHECK' => 'N',
				'LOAD_DEPARTMENT' => 'N',
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
				'FILE_LOAD' => 'N',
				'USER_LOAD' => 'N',
			));

			$arResult['COUNTER_MESSAGES'] += count($arMessage['message']);

			foreach ($arMessage['message'] as $data)
			{
				$arUnread['chat'.$data['recipientId']]['MESSAGE'] = $data;
				$arUnread['chat'.$data['recipientId']]['CHAT'] = $arMessage['chat'][$data['recipientId']];
			}

			uasort($arUnread, create_function('$a, $b', 'if($a["MESSAGE"]["date"] < $b["MESSAGE"]["date"] ) return 1; elseif($a["MESSAGE"]["date"]  > $b["MESSAGE"]["date"] ) return -1; else return 0;'));
			$arResult['COUNTER_UNREAD_MESSAGES'] = $arUnread;
		}

		$arOnline = CIMStatus::GetList();
		$arResult['ONLINE'] = !empty($arOnline)? $arOnline['users']: Array();

		if (CModule::IncludeModule('pull'))
		{
			$arChannel = CPullChannel::Get($USER->GetId());
			if (is_array($arChannel))
			{
				$nginxStatus = CPullOptions::GetNginxStatus();
				$webSocketStatus = CPullOptions::GetWebSocketStatus();

				$arChannels = Array($arChannel['CHANNEL_ID']);
				if ($nginxStatus)
				{
					$arChannelShared = CPullChannel::GetShared();
					$arChannels[] = $arChannelShared['CHANNEL_ID'];
					if ($arChannel['CHANNEL_DT'] > $arChannelShared['CHANNEL_DT'])
						$arChannel['CHANNEL_DT'] = $arChannelShared['CHANNEL_DT'];
				}

				if ($_POST['MOBILE'] == 'Y')
					$pullPath = ($nginxStatus? (CMain::IsHTTPS()? CPullOptions::GetListenSecureUrl($arChannels, true): CPullOptions::GetListenUrl($arChannels, true)): '/bitrix/components/bitrix/pull.request/ajax.php?UPDATE_STATE');
				else
					$pullPath = ($nginxStatus? (CMain::IsHTTPS()? CPullOptions::GetListenSecureUrl($arChannels): CPullOptions::GetListenUrl($arChannels)): '/bitrix/components/bitrix/pull.request/ajax.php?UPDATE_STATE');

				$arResult['PULL_CONFIG'] = Array(
					'CHANNEL_ID' => implode('/', $arChannels),
					'CHANNEL_DT' => $arChannel['CHANNEL_DT'],
					'PATH' => $pullPath,
					'PATH_WS' => ($nginxStatus && $webSocketStatus? (CMain::IsHTTPS()? CPullOptions::GetWebSocketSecureUrl($arChannels): CPullOptions::GetWebSocketUrl($arChannels)): ''),
					'METHOD' => ($nginxStatus? 'LONG': 'PULL'),
					'ERROR' => '',
				);
			}
		}
		// Counters
		$arResult["COUNTERS"] = CUserCounter::GetValues($USER->GetID(), $_POST['SITE_ID']);

		$arResult["ERROR"] = $errorMessage;
		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_NOTIFY_LOAD'] == 'Y')
	{
		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetUnreadNotify(Array('SPEED_CHECK' => 'N', 'USE_TIME_ZONE' => 'N'));
		if ($arNotify['result'])
		{
			$arSend['NOTIFY'] = $arNotify['notify'];
			$arSend['UNREAD_NOTIFY'] = $arNotify['unreadNotify'];
			$arSend['FLASH_NOTIFY'] = CIMNotify::GetFlashNotify($arNotify['unreadNotify']);
			$arSend['ERROR'] = '';

			if ($arNotify['maxNotify'] > 0)
				$CIMNotify->MarkNotifyRead($arNotify['maxNotify'], true);
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['IM_NOTIFY_HISTORY_LOAD_MORE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetNotifyList(Array('PAGE' => $_POST['PAGE'], 'USE_TIME_ZONE' => 'N'));

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY' => $arNotify,
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SEND_MESSAGE'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$tmpID = $_POST['ID'];

		if ($_POST['CHAT'] == 'Y')
		{
			$ar = Array(
				"FROM_USER_ID" => intval($USER->GetID()),
				"TO_CHAT_ID" => intval(substr($_POST['RECIPIENT_ID'], 4)),
				"MESSAGE" 	 => $_POST['MESSAGE'],
				"MESSAGE_TYPE" => IM_MESSAGE_GROUP
			);
		}
		else
		{
			$ar = Array(
				"FROM_USER_ID" => intval($USER->GetID()),
				"TO_USER_ID" => intval($_POST['RECIPIENT_ID']),
				"MESSAGE" 	 => $_POST['MESSAGE'],
			);
		}

		$errorMessage = "";
		if(!($insertID = CIMMessage::Add($ar)))
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
			if (StrLen($errorMessage) == 0)
				$errorMessage = GetMessage('IM_UNKNOWN_ERROR');
		}
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => (isset($_POST['MOBILE'])?"N": "Y"), "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		$userTzOffset = isset($_POST['USER_TZ_OFFSET'])? intval($_POST['USER_TZ_OFFSET']): CTimeZone::GetOffset();
		$arResult = Array(
			'TMP_ID' => $tmpID,
			'ID' => $insertID,
			'SEND_DATE' => time()+$userTzOffset,
			'SEND_MESSAGE' => $CCTP->convertText(htmlspecialcharsbx($ar['MESSAGE'])),
			'SENDER_ID' => intval($USER->GetID()),
			'RECIPIENT_ID' => $_POST['CHAT'] == 'Y'? htmlspecialcharsbx($_POST['RECIPIENT_ID']): intval($_POST['RECIPIENT_ID']),
			'ERROR' => $errorMessage
		);
		if (isset($_POST['MOBILE']))
		{
			$arFormat = Array(
				"today" => "today, ".GetMessage('IM_MESSAGE_FORMAT_TIME'),
				"" => GetMessage('IM_MESSAGE_FORMAT_DATE')
			);
			$arResult['SEND_DATE_FORMAT'] = FormatDate($arFormat, time()+$userTzOffset);
		}
		echo CUtil::PhpToJsObject($arResult);

		CIMContactList::SetOnline();
		CIMMessenger::SetCurrentTab($_POST['TAB']);
	}
	else if ($_POST['IM_EDIT_MESSAGE'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		if(!CIMMessenger::Update($_POST['ID'], $_POST['MESSAGE']))
		{
			$arResult = Array(
				'ERROR' => 'CANT_EDIT_MESSAGE'
			);
		}
		else
		{
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

			$userTzOffset = isset($_POST['USER_TZ_OFFSET'])? intval($_POST['USER_TZ_OFFSET']): CTimeZone::GetOffset();

			$arResult = Array(
				'ID' => $insertID,
				'MESSAGE' => $CCTP->convertText(htmlspecialcharsbx($_POST['MESSAGE'])),
				'DATE' => time()+$userTzOffset,
				'ERROR' => ''
			);
		}

		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_DELETE_MESSAGE'] == 'Y')
	{
		$errorMessage = '';
		if(!CIMMessenger::Delete($_POST['ID']))
		{
			$errorMessage = 'CANT_EDIT_MESSAGE';
		}

		$arResult = Array(
			'ERROR' => $errorMessage
		);
		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_LIKE_MESSAGE'] == 'Y')
	{
		$errorMessage = '';
		$result = CIMMessenger::Like($_POST['ID'], $_POST['ACTION']);
		if ($result === false)
			$errorMessage = 'WITHOUT_CHANGES';

		$arResult = Array(
			'LIKE' => $result,
			'ERROR' => $errorMessage
		);
		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_READ_MESSAGE'] == 'Y')
	{
		$errorMessage = "";

		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$CIMChat = new CIMChat();
			$CIMChat->SetReadMessage(intval(substr($_POST['USER_ID'],4)), (isset($_POST['LAST_ID']) && intval($_POST['LAST_ID'])>0 ? $_POST['LAST_ID']: null));
		}
		else
		{
			$CIMMessage = new CIMMessage();
			$CIMMessage->SetReadMessage($_POST['USER_ID'], (isset($_POST['LAST_ID']) && intval($_POST['LAST_ID'])>0 ? $_POST['LAST_ID']: null));
		}
		CIMMessenger::SetCurrentTab($_POST['TAB']);

		CIMContactList::SetOnline();

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_LOAD_LAST_MESSAGE'] == 'Y')
	{
		$error = '';
		$arMessage = Array();
		if ($_POST['CHAT'] == 'Y')
		{
			$chatId = intval(substr($_POST['USER_ID'], 4));

			$CIMChat = new CIMChat();
			$arMessage = $CIMChat->GetLastMessage($chatId, false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
			if ($_POST['USER_LOAD'] == 'Y' && empty($arMessage['chat']))
			{
				$arMessage = Array();
				$error = 'ACCESS_DENIED';
			}
			else if (isset($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
				if (isset($_POST['READ']) && $_POST['READ'] == 'Y')
					$CIMChat->SetReadMessage($chatId);
			}
		}
		else
		{
			$chatId = 0;
			if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
			{
				$CIMMessage = new CIMMessage();
				$arMessage = $CIMMessage->GetLastMessage(intval($_POST['USER_ID']), false, ($_POST['USER_LOAD'] == 'Y'? true: false), false);
				if (isset($_POST['READ']) && $_POST['READ'] == 'Y')
					$CIMMessage->SetReadMessage(intval($_POST['USER_ID']));

				if ($_POST['USER_LOAD'] == 'Y' && count($arMessage['users']) <= 1)
				{
					$arMessage = Array();
					$error = 'ACCESS_DENIED';
				}
				else
				{
					$chatId = $arMessage['chatId'];
					if ($chatId <= 0)
					{
						$chatId = CIMMessage::GetChatId($USER->GetId(), $_POST['USER_ID']);
					}
				}
			}
			else
			{
				$arMessage = Array();
				$error = 'ACCESS_DENIED';
			}

		}
		if ($error == '')
			CIMMessenger::SetCurrentTab($_POST['TAB']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $chatId,
			'USER_ID' => $_POST['CHAT'] == 'Y'? htmlspecialcharsbx($_POST['USER_ID']): intval($_POST['USER_ID']),
			'MESSAGE' => isset($arMessage['message'])? $arMessage['message']: Array(),
			'USERS_MESSAGE' => isset($arMessage['usersMessage'])? $arMessage['usersMessage']: Array(),
			'USERS' => isset($arMessage['users'])? $arMessage['users']: Array(),
			'USER_IN_GROUP' => isset($arMessage['userInGroup'])? $arMessage['userInGroup']: Array(),
			'WO_USER_IN_GROUP' => isset($arMessage['woUserInGroup'])? $arMessage['woUserInGroup']: Array(),
			'CHAT' => isset($arMessage['chat'])? $arMessage['chat']: Array(),
			'USER_BLOCK_CHAT' => isset($arMessage['userChatBlockStatus'])? $arMessage['userChatBlockStatus']: Array(),
			'USER_IN_CHAT' => isset($arMessage['userInChat'])? $arMessage['userInChat']: Array(),
			'USER_LOAD' => $_POST['USER_LOAD'] == 'Y'? 'Y': 'N',
			'READED_LIST' => isset($arMessage['readedList'])? $arMessage['readedList']: Array(),
			'PHONES' => isset($arMessage['phones'])? $arMessage['phones']: Array(),
			'FILES' => isset($arMessage['files'])? $arMessage['files']: Array(),
			'ERROR' => $error
		));
	}
	else if ($_POST['IM_USER_DATA_LOAD'] == 'Y')
	{
		$error = '';
		$arMessage = Array();
		$chatId = 0;
		if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
		{
			$ar = CIMContactList::GetUserData(array(
					'ID' => Array($_POST['USER_ID'], $USER->GetID()),
					'DEPARTMENT' => 'Y',
					'USE_CACHE' => 'N',
					'PHONES' => IsModuleInstalled('voximplant')? 'Y': 'N'
				)
			);
			$arMessage['users'] = $ar['users'];
			$arMessage['userInGroup']  = $ar['userInGroup'];
			$arMessage['woUserInGroup']  = $ar['woUserInGroup'];
			$arMessage['phones']  = $ar['phones'];
			$chatId = CIMMessage::GetChatId($USER->GetId(), $_POST['USER_ID']);
		}
		else
		{
			$error = 'ACCESS_DENIED';
		}
		if ($error == '')
			CIMMessenger::SetCurrentTab($_POST['TAB']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $chatId,
			'USER_ID' => intval($_POST['USER_ID']),
			'USERS' => isset($arMessage['users'])? $arMessage['users']: Array(),
			'USER_IN_GROUP' => isset($arMessage['userInGroup'])? $arMessage['userInGroup']: Array(),
			'WO_USER_IN_GROUP' => isset($arMessage['woUserInGroup'])? $arMessage['woUserInGroup']: Array(),
			'PHONES' => isset($arMessage['phones'])? $arMessage['phones']: Array(),
			'ERROR' => $error
		));
	}
	else if ($_POST['IM_HISTORY_LOAD'] == 'Y')
	{
		$arMessage = Array();
		$chatId = 0;
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$chatId = intval(substr($_POST['USER_ID'], 4));

			$CIMChat = new CIMChat();
			$arMessage = $CIMChat->GetLastMessage($chatId, false, ($_POST['USER_LOAD'] == 'Y'? true: false), false, false);
			if (isset($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
			}
			$dialogId = 'chat'.$chatId;
		}
		else
		{
			$dialogId = intval($_POST['USER_ID']);
			if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $dialogId)))
			{
				$CIMMessage = new CIMMessage();
				$arMessage = $CIMMessage->GetLastMessage($dialogId, false, ($_POST['USER_LOAD'] == 'Y'? true: false), false, false);

				$chatId = $arMessage['chatId'];
				if ($chatId <= 0)
				{
					$chatId = CIMMessage::GetChatId($USER->GetId(), $dialogId);
				}
			}
		}
		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $chatId,
			'USER_ID' => $dialogId,
			'MESSAGE' => isset($arMessage['message'])? $arMessage['message']: Array(),
			'USERS_MESSAGE' => isset($arMessage['message'])? $arMessage['usersMessage']: Array(),
			'USERS' => isset($arMessage['users'])? $arMessage['users']: Array(),
			'USER_IN_GROUP' => isset($arMessage['userInGroup'])? $arMessage['userInGroup']: Array(),
			'WO_USER_IN_GROUP' => isset($arMessage['woUserInGroup'])? $arMessage['woUserInGroup']: Array(),
			'CHAT' => isset($arMessage['chat'])? $arMessage['chat']: Array(),
			'USER_BLOCK_CHAT' => isset($arMessage['userChatBlockStatus'])? $arMessage['userChatBlockStatus']: Array(),
			'USER_IN_CHAT' => isset($arMessage['userInChat'])? $arMessage['userInChat']: Array(),
			'FILES' => isset($arMessage['files'])? $arMessage['files']: Array(),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_LOAD_MORE'] == 'Y')
	{
		$arMessage = Array();
		if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
		{
			$CIMHistory = new CIMHistory(false, Array(

			));
			if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			{
				$chatId = substr($_POST['USER_ID'],4);
				$arMessage = $CIMHistory->GetMoreChatMessage(intval($_POST['PAGE_ID']), $chatId, false);
				if (!empty($arMessage['message']))
				{
					foreach ($arMessage['message'] as $id => $ar)
						$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

					$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
					unset($arMessage['usersMessage'][$chatId]);
				}
			}
			else
			{
				$arMessage = $CIMHistory->GetMoreMessage(intval($_POST['PAGE_ID']), intval($_POST['USER_ID']), false, false);
			}
		}

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => isset($arMessage['chatId'])? $arMessage['chatId']: 0,
			'MESSAGE' => isset($arMessage['message'])? $arMessage['message']: Array(),
			'USERS_MESSAGE' => isset($arMessage['usersMessage'])? $arMessage['usersMessage']: Array(),
			'FILES' => isset($arMessage['files'])? $arMessage['files']: Array(),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_REMOVE_ALL'] == 'Y')
	{
		$errorMessage = "";

		$CIMHistory = new CIMHistory();
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			$CIMHistory->HideAllChatMessage(substr($_POST['USER_ID'],4));
		else
			$CIMHistory->RemoveAllMessage($_POST['USER_ID']);

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_HISTORY_REMOVE_MESSAGE'] == 'Y')
	{
		$errorMessage = "";

		$CIMHistory = new CIMHistory();
		$CIMHistory->RemoveMessage($_POST['MESSAGE_ID']);

		echo CUtil::PhpToJsObject(Array(
			'MESSAGE_ID' => intval($_POST['MESSAGE_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_HISTORY_SEARCH'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$CIMHistory = new CIMHistory();
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$chatId = substr($_POST['USER_ID'],4);
			$arMessage = $CIMHistory->SearchChatMessage($_POST['SEARCH'], $chatId, false);
			if (!empty($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
			}
		}
		else
		{
			$arMessage = $CIMHistory->SearchMessage($_POST['SEARCH'], intval($_POST['USER_ID']), false, false);
		}

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $arMessage['chatId'],
			'MESSAGE' => $arMessage['message'],
			'FILES' => $arMessage['files'],
			'USERS_MESSAGE' => $arMessage['usersMessage'],
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_HISTORY_DATE_SEARCH'] == 'Y')
	{
		$CIMHistory = new CIMHistory();
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
		{
			$chatId = substr($_POST['USER_ID'],4);
			$arMessage = $CIMHistory->SearchDateChatMessage($_POST['DATE'], $chatId, false);
			if (!empty($arMessage['message']))
			{
				foreach ($arMessage['message'] as $id => $ar)
					$arMessage['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

				$arMessage['usersMessage']['chat'.$chatId] = $arMessage['usersMessage'][$chatId];
				unset($arMessage['usersMessage'][$chatId]);
			}
		}
		else
		{
			$arMessage = $CIMHistory->SearchDateMessage($_POST['DATE'], intval($_POST['USER_ID']), false, false);
		}

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => $arMessage['chatId'],
			'MESSAGE' => $arMessage['message'],
			'FILES' => $arMessage['files'],
			'USERS_MESSAGE' => $arMessage['usersMessage'],
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CONTACT_LIST_SEARCH'] == 'Y' && !IsModuleInstalled('intranet'))
	{
		CUtil::decodeURIComponent($_POST);

		$CIMContactList = new CIMContactList();
		$arContactList = $CIMContactList->SearchUsers($_POST['SEARCH'], false);

		echo CUtil::PhpToJsObject(Array(
			'USERS' => $arContactList['users'],
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CONTACT_LIST'] == 'Y')
	{
		if (isset($_POST['DESKTOP']) && $_POST['DESKTOP'] == 'Y')
			CIMMessenger::SetDesktopStatusOnline();

		$CIMContactList = new CIMContactList();
		$arContactList = $CIMContactList->GetList();

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $USER->GetId(),
			'USERS' => $arContactList['users'],
			'GROUPS' => $arContactList['groups'],
			'CHATS' => $arContactList['chats'],
			'USER_IN_GROUP' => $arContactList['userInGroup'],
			'WO_GROUPS' => $arContactList['woGroups'],
			'WO_USER_IN_GROUP' => $arContactList['woUserInGroup'],
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_RECENT_LIST'] == 'Y')
	{
		$ar = CIMContactList::GetRecentList(Array(
			'USE_TIME_ZONE' => 'N',
			'USE_SMILES' => 'N'
		));
		$arRecent = Array();
		$arUsers = Array();
		$arChat = Array();
		foreach ($ar as $userId => $value)
		{
			if ($value['TYPE'] == IM_MESSAGE_GROUP)
			{
				$arChat[$value['CHAT']['id']] = $value['CHAT'];
				$value['MESSAGE']['userId'] = $userId;
				$value['MESSAGE']['recipientId'] = $userId;
			}
			else
			{
				$value['MESSAGE']['userId'] = $userId;
				$value['MESSAGE']['recipientId'] = $userId;
				$arUsers[$value['USER']['id']] = $value['USER'];
			}
			$arRecent[] = $value['MESSAGE'];
		}

		$arSmile = CIMMessenger::PrepareSmiles();
		$arResult['SMILE'] = $arSmile['SMILE'];
		$arResult['SMILE_SET'] = $arSmile['SMILE_SET'];

		$arResult['NOTIFY_BLOCKED'] = CIMSettings::GetSimpleNotifyBlocked();

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => $USER->GetId(),
			'RECENT' => $arRecent,
			'USERS' => $arUsers,
			'CHAT' => $arChat,
			'NOTIFY_BLOCKED' => $arResult['NOTIFY_BLOCKED'],
			'SMILE' => !empty($arSmile['SMILE'])? $arSmile['SMILE']: false,
			'SMILE_SET' => !empty($arSmile['SMILE_SET'])? $arSmile['SMILE_SET']: false,
			'ERROR' => ''
		));

	}
	else if ($_POST['IM_NOTIFY_VIEWED'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->MarkNotifyRead($_POST['MAX_ID'], true);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_VIEW'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->MarkNotifyRead($_POST['ID']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_CONFIRM'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->Confirm($_POST['NOTIFY_ID'], $_POST['NOTIFY_VALUE']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'NOTIFY_VALUE' => $_POST['NOTIFY_VALUE'],
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_BLOCK_TYPE'] == 'Y')
	{
		$errorMessage = "";

		$arSettings = Array(
			'site|'.$_POST['BLOCK_TYPE'] => $_POST['BLOCK_RESULT'] == 'Y'? false: true,
			'xmpp|'.$_POST['BLOCK_TYPE'] => $_POST['BLOCK_RESULT'] == 'Y'? false: true,
			'email|'.$_POST['BLOCK_TYPE'] => $_POST['BLOCK_RESULT'] == 'Y'? false: true,
		);
		CIMSettings::SetSetting(CIMSettings::NOTIFY, $arSettings);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_REMOVE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		$CIMNotify->DeleteWithCheck($_POST['NOTIFY_ID']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_NOTIFY_GROUP_REMOVE'] == 'Y')
	{
		$errorMessage = "";

		$CIMNotify = new CIMNotify();
		if ($arNotify = $CIMNotify->GetNotify($_POST['NOTIFY_ID']))
			CIMNotify::DeleteByTag($arNotify['NOTIFY_TAG']);

		echo CUtil::PhpToJsObject(Array(
			'NOTIFY_ID' => intval($_POST['NOTIFY_ID']),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_RECENT_HIDE'] == 'Y')
	{
		if (substr($_POST['USER_ID'], 0, 4) == 'chat')
			CIMContactList::DeleteRecent(substr($_POST['USER_ID'], 4), true);
		else
			CIMContactList::DeleteRecent($_POST['USER_ID']);

		echo CUtil::PhpToJsObject(Array(
			'USER_ID' => htmlspecialcharsbx($_POST['USER_ID']),
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CHAT_ADD'] == 'Y')
	{
		$_POST['USERS'] = CUtil::JsObjectToPhp($_POST['USERS']);

		$errorMessage = "";
		$chatId = 0;
		if (!is_array($_POST['USERS']))
		{
			$errorMessage = 'Unknown error';
		}
		else
		{
			$CIMChat = new CIMChat();
			$chatId = $CIMChat->Add(Array('USERS' => $_POST['USERS']));
			if (!$chatId)
			{
				if ($e = $GLOBALS["APPLICATION"]->GetException())
					$errorMessage = $e->GetString();
			}
		}

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($chatId),
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_CHAT_EXTEND'] == 'Y')
	{
		$_POST['USERS'] = CUtil::JsObjectToPhp($_POST['USERS']);

		$errorMessage = "";
		$CIMChat = new CIMChat();
		$result = $CIMChat->AddUser($_POST['CHAT_ID'], $_POST['USERS']);
		if (!$result)
		{
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
		}
		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_CHAT_LEAVE'] == 'Y')
	{
		$CIMChat = new CIMChat();
		$result = $CIMChat->DeleteUser($_POST['CHAT_ID'], intval($_POST['USER_ID']) > 0? intval($_POST['USER_ID']): $USER->GetID());
		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($_POST['CHAT_ID']),
			'USER_ID' => intval($_POST['USER_ID']),
			'ERROR' => $result? '': 'ACCESS_ERROR'
		));
	}
	else if ($_POST['IM_CHAT_MUTE'] == 'Y')
	{
		$CIMChat = new CIMChat();
		$result = $CIMChat->MuteNotify($_POST['CHAT_ID'], $_POST['MUTE'] == 'Y');
		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($_POST['CHAT_ID']),
			'ERROR' => $result? '': 'ACCESS_ERROR'
		));
	}
	else if ($_POST['IM_CHAT_RENAME'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$CIMChat = new CIMChat();
		$CIMChat->Rename($_POST['CHAT_ID'], $_POST['CHAT_TITLE']);

		echo CUtil::PhpToJsObject(Array(
			'CHAT_ID' => intval($_POST['CHAT_ID']),
			'CHAT_TITLE' => $_POST['CHAT_TITLE'],
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_CHAT_DATA_LOAD'] == 'Y')
	{
		CUtil::decodeURIComponent($_POST);

		$arChat = CIMChat::GetChatData(array(
			'ID' => $_POST['CHAT_ID'],
			'USE_CACHE' => 'Y',
			'USER_ID' => $USER->GetId()
		));

		echo CUtil::PhpToJsObject(Array(
			'CHAT' => $arChat['chat'],
			'CHAT_ID' => $_POST['CHAT_ID'],
			'USER_IN_CHAT' => $arChat['userInChat'],
			'USER_BLOCK_CHAT' => $arChat['userChatBlockStatus'],
			'ERROR' => ''
		));
	}
	else if ($_POST['IM_GET_EXTERNAL_DATA'] == 'Y')
	{
		$error = '';
		$arResult = Array(
			'TS' => $_POST['TS'],
			'TYPE' => $_POST['TYPE'],
			'ERROR' => ''
		);
		$arMessage = Array();

		if ($_POST['TYPE'] == 'user')
		{
			$arResult['USER_ID'] = intval($_POST['USER_ID']);
			if (CIMContactList::AllowToSend(Array('TO_USER_ID' => $_POST['USER_ID'])))
			{
				$ar = CIMContactList::GetUserData(array(
					'ID' => Array($_POST['USER_ID']),
					'DEPARTMENT' => 'Y',
					'USE_CACHE' => 'N',
					'PHONES' => IsModuleInstalled('voximplant')? 'Y': 'N'
				));
				$arResult['USERS'] = isset($ar['users'])? $ar['users']: Array();
				$arResult['USER_IN_GROUP'] = isset($ar['userInGroup'])? $ar['userInGroup']: Array();
				$arResult['WO_USER_IN_GROUP'] = isset($ar['woUserInGroup'])? $ar['woUserInGroup']: Array();
				$arResult['PHONES'] = isset($ar['phones'])? $ar['phones']: Array();
			}
			else
			{
				$arResult['ERROR'] = 'ACCESS_DENIED';
			}
		}
		else if ($_POST['TYPE'] == 'phoneCallHistory')
		{
			if (CModule::IncludeModule('voximplant'))
			{
				$arResult['HISTORY_ID'] = intval($_POST['HISTORY_ID']);
				$history = CVoxImplantHistory::GetForPopup($arResult['HISTORY_ID']);
				if ($history && $history['PORTAL_USER_ID'] == $USER->GetId())
				{
					if (strlen($history['CALL_RECORD_HREF']) > 0)
					{
						ob_start();
						$APPLICATION->IncludeComponent(
							"bitrix:player",
							"",
							Array(
								"PLAYER_TYPE" => "flv",
								"CHECK_FILE" => "N",
								"USE_PLAYLIST" => "N",
								"PATH" => $history["CALL_RECORD_HREF"],
								"WIDTH" => 233,
								"HEIGHT" => 24,
								"PREVIEW" => false,
								"LOGO" => false,
								"FULLSCREEN" => "N",
								"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
								"SKIN" => "",
								"CONTROLBAR" => "bottom",
								"WMODE" => "transparent",
								"WMODE_WMV" => "windowless",
								"HIDE_MENU" => "N",
								"SHOW_CONTROLS" => "N",
								"SHOW_STOP" => "Y",
								"SHOW_DIGITS" => "Y",
								"CONTROLS_BGCOLOR" => "FFFFFF",
								"CONTROLS_COLOR" => "000000",
								"CONTROLS_OVER_COLOR" => "000000",
								"SCREEN_COLOR" => "000000",
								//"FILE_DURATION" => "30",
								"AUTOSTART" => "N",
								"REPEAT" => "N",
								"VOLUME" => "90",
								"DISPLAY_CLICK" => "play",
								"MUTE" => "N",
								"HIGH_QUALITY" => "N",
								"ADVANCED_MODE_SETTINGS" => "Y",
								"BUFFER_LENGTH" => "10",
								"DOWNLOAD_LINK" => false,
								"DOWNLOAD_LINK_TARGET" => "_self",
								"ALLOW_SWF" => "N",
								"ADDITIONAL_PARAMS" => array(
									'LOGO' => false,
									'NUM' => false,
									'HEIGHT_CORRECT' => false,
								),
								"PLAYER_ID" => "bitrix_vi_record_".$arResult['HISTORY_ID']
							),
							false,
							Array("HIDE_ICONS" => "Y")
						);
						$history['CALL_RECORD_HTML'] = ob_get_contents();
						ob_end_clean();
						unset($history['CALL_RECORD_HREF']);

					}
					foreach ($history as $key => $value)
					{
						$arResult[$key] = $value;
					}
				}
				else
				{
					$arResult['ERROR'] = 'ACCESS_DENIED';
				}
			}
			else
			{
				$arResult['ERROR'] = 'ACCESS_DENIED';
			}
		}

		echo CUtil::PhpToJsObject($arResult);
	}
	else if ($_POST['IM_CALL'] == 'Y')
	{
		$chatId = intval($_POST['CHAT_ID']);
		$userId = intval($USER->GetId());

		$errorMessage = "";
		if ($_POST['COMMAND'] == 'invite')
		{
			if ($_POST['CHAT'] != 'Y')
				$chatId = CIMMessage::GetChatId($userId, intval($_POST['CHAT_ID']));

			$arCallData = CIMCall::Invite(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'RECIPIENT_ID' => $_POST['CHAT'] != 'Y'? intval($_POST['CHAT_ID']): 0,
				'VIDEO' => $_POST['VIDEO'],
				'MOBILE' => $_POST['MOBILE'],
			));
			if (!$arCallData)
			{
				if ($e = $GLOBALS["APPLICATION"]->GetException())
					$errorMessage = $e->GetString();

				echo CUtil::PhpToJsObject(Array(
					'ERROR' => $errorMessage
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'CHAT_ID' => $arCallData['CHAT_ID'],
					'USERS' => $arCallData['USER_DATA']['USERS'],
					'USERS_CONNECT' => isset($arCallData['USERS_CONNECT'])? $arCallData['USERS_CONNECT']: array(),
					'HR_PHOTO' => $arCallData['USER_DATA']['HR_PHOTO'],
					'CALL_VIDEO' => $arCallData['STATUS_TYPE'] == IM_CALL_VIDEO,
					'CALL_TO_GROUP' => $arCallData['CALL_TO_GROUP'],
					'CALL_ENABLED' => $arCallData['STATUS_TYPE'] != IM_CALL_NONE,
					'ERROR' => $errorMessage
				));
			}
		}
		else if ($_POST['COMMAND'] == 'wait')
		{
			CIMCall::Wait(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
			));
		}
		else if ($_POST['COMMAND'] == 'reconnect')
		{
			CIMCall::Command($chatId, $_POST['RECIPIENT_ID'], 'reconnect', Array());
		}
		else if ($_POST['COMMAND'] == 'answer')
		{
			CIMCall::Answer(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'CALL_TO_GROUP' => $_POST['CALL_TO_GROUP'] == 'Y',
				'MOBILE' => $_POST['MOBILE'],
			));
		}
		else if ($_POST['COMMAND'] == 'start')
		{
			CIMCall::Start(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'RECIPIENT_ID' => intval($_POST['RECIPIENT_ID']),
				'CALL_TO_GROUP' => $_POST['CALL_TO_GROUP'] == 'Y',
			));
		}
		else if (in_array($_POST['COMMAND'], Array(IM_CALL_END_DECLINE, IM_CALL_END_TIMEOUT, IM_CALL_END_BUSY, IM_CALL_END_OFFLINE, IM_CALL_END_ACCESS)))
		{
			$arParams = Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'RECIPIENT_ID' => intval($_POST['RECIPIENT_ID']),
				'REASON' => $_POST['COMMAND'],
			);
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			if (isset($_POST['VIDEO']))
				$arParams['VIDEO'] = $_POST['VIDEO'];
			if (isset($_POST['PARAMS']['ACTIVE']))
				$arParams['ACTIVE'] = $_POST['PARAMS']['ACTIVE'];
			if (isset($_POST['PARAMS']['INITIATOR']))
				$arParams['INITIATOR'] = $_POST['PARAMS']['INITIATOR'];

			CIMCall::End($arParams);
		}
		else if ($_POST['COMMAND'] == 'signaling')
		{
			CIMCall::Command($chatId, $_POST['RECIPIENT_ID'], 'signaling', Array('peer' => $_POST['PEER']));
		}
		else if ($_POST['COMMAND'] == 'invite_user')
		{
			$arCallData = CIMCall::AddUser(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'USERS' => CUtil::JsObjectToPhp($_POST['USERS']),
			));
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();

			if (strlen($errorMessage) <= 0)
			{
				echo CUtil::PhpToJsObject(Array(
					'CHAT_ID' => $arCallData['CHAT_ID'],
					'USERS' => $arCallData['USER_DATA']['USERS'],
					'HR_PHOTO' => $arCallData['USER_DATA']['HR_PHOTO'],
					'ERROR' => $errorMessage
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'CHAT_ID' => $arCallData['CHAT_ID'],
					'ERROR' => $e->GetString()
				));
			}
		}
		else
		{
			CIMCall::Signaling(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $userId,
				'COMMAND' => $_POST['COMMAND'],
			));
		}
		if ($_POST['COMMAND'] != 'invite' && $_POST['COMMAND'] != 'invite_user')
		{
			echo CUtil::PhpToJsObject(Array(
				'CHAT_ID' => $chatId,
				'ERROR' => $errorMessage
			));
		}
	}
	else if ($_POST['IM_SHARING'] == 'Y' && intval($_POST['USER_ID']) > 0)
	{
		if (!CModule::IncludeModule("pull"))
			return false;

		if ($_POST['COMMAND'] == 'signaling')
		{
			CPullStack::AddByUser(intval($_POST['USER_ID']), Array(
				'module_id' => 'im',
				'command' => 'screenSharing',
				'params' => Array(
					'senderId' => $USER->GetID(),
					'command' => 'signaling',
					'peer' => $_POST['PEER'],
				),
			));
		}
		else
		{
			CPullStack::AddByUser(intval($_POST['USER_ID']), Array(
				'module_id' => 'im',
				'command' => 'screenSharing',
				'params' => Array(
					'senderId' => $USER->GetID(),
					'command' => $_POST['COMMAND']
				),
			));
		}
	}
	else if ($_POST['IM_PHONE'] == 'Y' && CModule::IncludeModule('voximplant'))
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/ajax_hit.php");
	}
	else if ($_POST['IM_IDLE'] == 'Y')
	{
		$errorMessage = "";
		CIMStatus::SetIdle($USER->GetId(), $_POST['IDLE'] == 'Y');

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_START_WRITING'] == 'Y')
	{
		$errorMessage = "";
		CIMMessenger::StartWriting($_POST['DIALOG_ID']);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_DESKTOP_LOGOUT'] == 'Y')
	{
		$errorMessage = "";

		CIMMessenger::SetDesktopStatusOffline();
		CIMContactList::SetOffline();

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SETTING_SAVE'] == 'Y')
	{
		$errorMessage = "";

		$arSettings = CUtil::JsObjectToPhp($_POST['SETTINGS']);

		CIMSettings::SetSetting(CIMSettings::SETTINGS, $arSettings);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SETTINGS_SAVE'] == 'Y')
	{
		$errorMessage = "";

		$arSettings = CUtil::JsObjectToPhp($_POST['SETTINGS']);

		$arOldSettings = CUserOptions::GetOption('IM', CIMSettings::SETTINGS, Array());
		if ($arOldSettings['notifyScheme'] == 'expert' && $arSettings['notifyScheme'] == 'simple')
		{
			$arNotifyValues = CIMSettings::GetSimpleNotifyBlocked();
			$arSettings['notify'] = Array();
			foreach ($arNotifyValues as $settingName => $value)
			{
				$arSettings['notify'][CIMSettings::CLIENT_SITE.'|'.$settingName] = false;
				$arSettings['notify'][CIMSettings::CLIENT_XMPP.'|'.$settingName] = false;
				$arSettings['notify'][CIMSettings::CLIENT_MAIL.'|'.$settingName] = false;
			}
		}

		if (array_key_exists('notify', $arSettings))
		{
			CIMSettings::Set(CIMSettings::NOTIFY, $arSettings['notify']);
			unset($arSettings['notify']);
		}
		CIMSettings::Set(CIMSettings::SETTINGS, $arSettings);

		echo CUtil::PhpToJsObject(Array(
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SETTINGS_NOTIFY_LOAD'] == 'Y')
	{
		$errorMessage = "";

		$arSettings = CIMSettings::Get();
		$arNotifyNames = CIMSettings::GetNotifyNames();

		echo CUtil::PhpToJsObject(Array(
			'NAMES' => $arNotifyNames,
			'VALUES' => $arSettings['notify'],
			'ERROR' => $errorMessage
		));
	}
	else if ($_POST['IM_SETTINGS_SIMPLE_NOTIFY_LOAD'] == 'Y')
	{
		$errorMessage = "";

		$arNotifyNames = CIMSettings::GetNotifyNames();
		$arNotifyValues = CIMSettings::GetSimpleNotifyBlocked(true);

		echo CUtil::PhpToJsObject(Array(
			'NAMES' => $arNotifyNames,
			'VALUES' => $arNotifyValues,
			'ERROR' => $errorMessage
		));
	}
	else
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
	}
}
else
{
	echo CUtil::PhpToJsObject(Array(
		'ERROR' => 'SESSION_ERROR',
		'BITRIX_SESSID' => bitrix_sessid(),
	));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>