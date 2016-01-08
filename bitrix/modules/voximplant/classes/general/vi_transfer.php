<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantTransfer
{
	public static function Invite($callId, $transferUserId)
	{
		$transferUserId = intval($transferUserId);
		if ($transferUserId <= 0)
			return false;

		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'CALLER_ID', 'CRM', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		if ($call['TRANSFER_USER_ID'] > 0)
		{
			self::Cancel($callId, $call);
		}

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => $transferUserId));

		$crmData = Array();
		if ($call['CRM'] == 'Y')
			$crmData = CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID'], $transferUserId);

		self::SendPullEvent(Array(
			'COMMAND' => 'inviteTransfer',
			'USER_ID' => $transferUserId,
			'CALL_ID' => $call['CALL_ID'],
			'CALLER_ID' => $call['CALLER_ID'],
			'CRM' => $crmData,
		));

		return true;
	}

	public static function Cancel($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		self::SendPullEvent(Array(
			'COMMAND' => 'cancelTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Wait($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		self::SendPullEvent(Array(
			'COMMAND' => 'waitTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Answer($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		self::SendPullEvent(Array(
			'COMMAND' => 'waitTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Ready($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CALLER_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		$answer['COMMAND'] = 'transfer';
		$answer['OPERATOR_ID'] = $call['USER_ID'];
		$answer['TRANSFER_USER_ID'] = $call['TRANSFER_USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($answer));

		VI\CallTable::update($call['ID'], Array('USER_ID' => $call['TRANSFER_USER_ID'], 'TRANSFER_USER_ID' => 0));

		CVoxImplantHistory::TransferMessage($call['USER_ID'], $call['TRANSFER_USER_ID'], $call['CALLER_ID']);

		self::SendPullEvent(Array(
			'COMMAND' => 'answerTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Decline($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID','CALL_ID', 'USER_ID', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		self::SendPullEvent(Array(
			'COMMAND' => 'declineTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Timeout($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'TRANSFER_USER_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function SendPullEvent($params)
	{
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		if (empty($params['COMMAND']))
			return false;

		$config = Array();
		if ($params['COMMAND'] == 'inviteTransfer')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callerId" => $params['CALLER_ID'],
				"phoneNumber" => $params['PHONE_NAME'],
				"chatId" => 0,
				"chat" => array(),
				"application" => $params['APPLICATION'],
				"CRM" => $params['CRM'],
			);
		}
		else
		{
			$config["callId"] = $params['CALL_ID'];
		}

		CPullStack::AddByUser($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config
			)
		);

		return true;
	}
}
?>