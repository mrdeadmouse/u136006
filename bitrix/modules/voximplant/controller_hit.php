<?php
if(!CModule::IncludeModule("voximplant"))
	return false;

CVoxImplantHistory::WriteToLog($_POST, 'PORTAL HIT');

$params = $_POST;
$hash = $params["BX_HASH"];
unset($params["BX_HASH"]);

// VOXIMPLANT CLOUD HITS
if(isset($_GET['b24_direct']) && CVoxImplantHttp::CheckDirectRequest($params))
{
	if (isset($params['PHONE_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']) && $params["COMMAND"] != "OutgoingRegister")
	{
		$params['PHONE_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
	}

	if($params["COMMAND"] == "OutgoingRegister")
	{
		if (isset($params['CALLER_ID']) && isset($params['ACCOUNT_SEARCH_ID']))
		{
			$params['CALLER_ID'] = $params['ACCOUNT_SEARCH_ID'];
		}

		$result = CVoxImplantOutgoing::Init(Array(
			'CONFIG_ID' => $params['CONFIG_ID'],
			'USER_ID' => $params['USER_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'CALLER_ID' => $params['CALLER_ID'],
			'ACCESS_URL' => $params['ACCESS_URL'],
			'CRM' => $params['CRM'],
		));

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_ID_TMP' => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
				'CALL_TYPE' => 1,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		echo 'OK';
	}
	else if($params["COMMAND"] == "IncomingInvite")
	{
		$result = CVoxImplantIncoming::Init(Array(
			'SEARCH_ID' => $params['PHONE_NUMBER'],
			'CALL_ID' => $params['CALL_ID'],
			'CALLER_ID' => $params['CALLER_ID'],
			'DIRECT_CODE' => $params['DIRECT_CODE'],
			'ACCESS_URL' => $params['ACCESS_URL'],
		));
		CVoxImplantHistory::WriteToLog($result, 'PORTAL ANSWER');

		echo json_encode($result);
	}
	else if($params["COMMAND"] == "StartCall")
	{
		CVoxImplantMain::CallStart($params['CALL_ID'], $params['USER_ID'], $params['CALL_DEVICE'], $params['EXTERNAL'] == 'Y');

		echo json_encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "HangupCall")
	{
		$res = Bitrix\Voximplant\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $params['CALL_ID']),
		));
		$userTimeout = Array();
		if ($call = $res->fetch())
		{
			$res = Bitrix\Voximplant\QueueTable::getList(Array(
				'filter' => Array('=CONFIG_ID' => $call['CONFIG_ID']),
			));
			while ($queue = $res->fetch())
			{
				if ($call['TRANSFER_USER_ID'] == $queue['USER_ID'])
					continue;

				$userTimeout[$queue['USER_ID']] = true;;
				CVoxImplantIncoming::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $queue['USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
				));
			}
			if ($call['TRANSFER_USER_ID'] > 0 && !isset($userTimeout[$call['TRANSFER_USER_ID']]))
			{
				CVoxImplantTransfer::SendPullEvent(Array(
					'COMMAND' => 'timeoutTransfer',
					'USER_ID' => $call['TRANSFER_USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
				));
			}
			if ($call['USER_ID'] > 0 && !isset($userTimeout[$call['USER_ID']]))
			{
				CVoxImplantTransfer::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $call['USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
				));
			}
		}
		else
		{
			CVoxImplantIncoming::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => $params['USER_ID'],
				'CALL_ID' => $params['CALL_ID'],
			));
		}

		CVoxImplantHistory::WriteToLog($call, 'PORTAL HANGUP');

		echo json_encode($result);
	}
	else if($params["COMMAND"] == "GetNextInQueue")
	{
		$excludeUsers = Array();
		if (isset($params['EXCLUDE_USERS']))
			$excludeUsers = explode('|', $params['EXCLUDE_USERS']);

		if (in_array($params['LAST_TYPE_CONNECT'], Array(CVoxImplantIncoming::TYPE_CONNECT_DIRECT, CVoxImplantIncoming::TYPE_CONNECT_CRM)))
		{
			$result = CVoxImplantIncoming::GetNextAction(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LAST_ANSWER_USER_ID' => $params['LAST_ANSWER_USER_ID'],
				'EXCLUDE_USERS' => $excludeUsers,
			));
		}
		else
		{
			$result = CVoxImplantIncoming::GetNextInQueue(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LAST_ANSWER_USER_ID' => $params['LAST_ANSWER_USER_ID'],
				'EXCLUDE_USERS' => $excludeUsers,
			));
		}
		CVoxImplantHistory::WriteToLog($result, 'PORTAL ANSWER');

		echo json_encode($result);
	}
} // CONTROLLER 1C HITS
else if(
	$params['BX_TYPE'] == 'B24' && CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME)) === $hash ||
	$params['BX_TYPE'] == 'CP' && CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params))) === $hash
)
{
	if ($params["BX_COMMAND"] != "add_history" && isset($params['PHONE_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']))
	{
		$params['PHONE_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
	}

	if($params["BX_COMMAND"] == "add_history")
	{
		CVoxImplantHistory::WriteToLog($params, 'PORTAL ADD HISTORY');

		if (isset($params['PORTAL_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']))
		{
			$params['PORTAL_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
		}

		CVoxImplantHistory::Add($params);

		$ViAccount = new CVoxImplantAccount();
		$ViAccount->SetAccountBalance($params["balance"]);

		echo "200 OK";
	}
	elseif($params["COMMAND"] == "IncomingGetConfig")
	{
		$result = CVoxImplantIncoming::GetConfigBySearchId($params['PHONE_NUMBER']);
		CVoxImplantHistory::WriteToLog($result, 'PORTAL GET INCOMING CONFIG');

		if ($result['ID'])
		{
			$result = CVoxImplantIncoming::RegisterCall($result, $params);
		}

		$isNumberInBlacklist = CVoxImplantIncoming::IsNumberInBlackList($params["CALLER_ID"]);
		$isBlacklistAutoEnable = Bitrix\Main\Config\Option::get("voximplant", "blacklist_auto", "N") == "Y";

		if ($result["WORKTIME_SKIP_CALL"] == "Y" && !$isNumberInBlacklist && $isBlacklistAutoEnable)
		{
			$isNumberInBlacklist = CVoxImplantIncoming::CheckNumberForBlackList($params["CALLER_ID"]);
		}

		if ($isNumberInBlacklist)
		{
			$result["NUMBER_IN_BLACKLIST"] = "Y";
		}

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_TYPE' => 2,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		echo json_encode($result);
	}
	elseif($params["COMMAND"] == "OutgoingGetConfig")
	{
		$result = CVoxImplantOutgoing::GetConfigByUserId($params['USER_ID']);
		CVoxImplantHistory::WriteToLog($result, 'PORTAL GET OUTGOING CONFIG');

		echo json_encode($result);
	}
	elseif($params["COMMAND"] == "UnlinkExpirePhoneNumber")
	{
		$result = CVoxImplantConfig::DeleteConfigBySearchId($params['PHONE_NUMBER']);
		CVoxImplantHistory::WriteToLog($result, 'CONTROLLER UNLINK EXPIRE PHONE NUMBER');

		echo json_encode($result);
	}
	else if($params["COMMAND"] == "ExternalHungup")
	{
		$res = Bitrix\Voximplant\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $params['CALL_ID_TMP']),
		));
		if ($call = $res->fetch())
		{
			Bitrix\Voximplant\CallTable::delete($call['ID']);

			CVoxImplantOutgoing::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => $call['USER_ID'],
				'CALL_ID' => $call['CALL_ID'],
				'FAILED_CODE' => intval($params['CALL_FAILED_CODE']),
			));
			CVoxImplantHistory::WriteToLog($call, 'EXTERNAL CALL HANGUP');
		}
	}
}
else
{
	echo "You don't have access to this page.";
}