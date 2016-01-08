<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;

class CVoxImplantIncoming
{
	const RULE_WAIT = 'wait';
	const RULE_TALK = 'talk';
	const RULE_HUNGUP = 'hungup';
	const RULE_PSTN = 'pstn';
	const RULE_PSTN_SPECIFIC = 'pstn_specific';
	const RULE_USER = 'user';
	const RULE_VOICEMAIL = 'voicemail';
	const RULE_QUEUE = 'queue';

	const TYPE_CONNECT_DIRECT = 'direct';
	const TYPE_CONNECT_CRM = 'crm';
	const TYPE_CONNECT_QUEUE = 'queue';

	public static function GetConfigBySearchId($searchId)
	{
		return CVoxImplantConfig::GetConfigBySearchId($searchId);
	}

	public static function Init($params)
	{
		// TODO check $params
		$result = Array('COMMAND' => CVoxImplantIncoming::RULE_QUEUE);
		$firstUserId = 0;
		$excludeUsers = Array();


		$config = self::GetConfigBySearchId($params['SEARCH_ID']);
		if (!$config)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}

		if ($config['DIRECT_CODE'] == 'Y' && isset($params['DIRECT_CODE']) && intval($params['DIRECT_CODE']) > 0)
		{
			$res = CVoxImplantUser::GetList(Array(
				'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
				'filter' => Array('=UF_PHONE_INNER' => intval($params['DIRECT_CODE'])),
			));
			$userData = $res->fetch();
			
			if ($userData && $userData['ACTIVE'] == 'Y')
			{
				if ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = self::TYPE_CONNECT_DIRECT;
					$result['USER_ID'] = $userData['ID'];
					$result['USER_HAVE_PHONE'] = $userData['UF_VI_PHONE'] == 'Y'? 'Y': 'N';
				}
				else
				{
					if ($config['DIRECT_CODE_RULE'] == CVoxImplantIncoming::RULE_VOICEMAIL)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						$result['USER_ID'] = $userData['ID'];

						return $result;
					}
					else if ($config['DIRECT_CODE_RULE'] == CVoxImplantIncoming::RULE_PSTN)
					{
						$userPhone = CVoxImplantPhone::GetUserPhone($userData['ID']);
						if ($userPhone)
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
							$result['PHONE_NUMBER'] = $userPhone;
							$result['USER_ID'] = $userData['ID'];
						}
						else
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
							$result['USER_ID'] = $userData['ID'];
						}
						return $result;
					}
					else
					{
						$firstUserId = $userData['ID'];
					}
				}
			}
		}

		$crmData = Array();
		$crmCreate = CVoxImplantConfig::CRM_CREATE_NONE;
		if ($config['CRM'] == 'Y')
		{
			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID']);

			if ($config['CRM_FORWARD'] == "Y" && $result['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE && isset($crmData['RESPONSIBILITY']['ID']) && $crmData['RESPONSIBILITY']['ID'] > 0)
			{
				$skipByTimeman = false;
				if ($config['TIMEMAN'] == "Y")
				{
					$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($crmData['RESPONSIBILITY']['ID']);
					if ($skipByTimeman)
					{
						$excludeUsers[] = $crmData['RESPONSIBILITY']['ID'];
					}
				}
				if (!$skipByTimeman)
				{
					$res = CVoxImplantUser::GetList(Array(
						'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
						'filter' => Array('=ID' => intval($crmData['RESPONSIBILITY']['ID'])),
					));
					$userData = $res->fetch();
					if ($userData && $userData['ACTIVE'] == 'Y')
					{
						if ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y')
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
							$result['TYPE_CONNECT'] = self::TYPE_CONNECT_CRM;
							$result['USER_ID'] = $userData['ID'];
							$result['USER_HAVE_PHONE'] = $userData['UF_VI_PHONE'] == 'Y'? 'Y': 'N';
						}
						else
						{
							if ($config['CRM_RULE'] == CVoxImplantIncoming::RULE_VOICEMAIL)
							{
								$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
								$result['USER_ID'] = $userData['ID'];

								return $result;
							}
							else if ($config['CRM_RULE'] == CVoxImplantIncoming::RULE_PSTN)
							{
								$userPhone = CVoxImplantPhone::GetUserPhone($userData['ID']);
								if ($userPhone)
								{
									$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
									$result['PHONE_NUMBER'] = $userPhone;
									$result['USER_ID'] = $userData['ID'];
								}
								else
								{
									$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
									$result['USER_ID'] = $userData['ID'];
								}
								return $result;
							}
							else
							{
								if ($firstUserId <= 0)
									$firstUserId = $userData['ID'];
							}
						}
					}
				}
			}
			else if ($crmData['FOUND'] == 'N' && $config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$crmCreate = CVoxImplantConfig::CRM_CREATE_LEAD;
			}
			$crmData = Array();
		}

		if ($result['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
			$result = self::GetNextInQueue(Array(
				'SEARCH_ID' => $params['SEARCH_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => 0,
				'LAST_TYPE_CONENCT' => self::TYPE_CONNECT_QUEUE,
				'LAST_ANSWER_USER_ID' => 0,
				'EXCLUDE_USERS' => $excludeUsers,

				'FIRST_IN_QUEUE' => 'Y',
				'CONFIG' => $config,
			));
		}

		if ($result['USER_ID'] > 0)
		{
			if ($crmCreate == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$leadId = CVoxImplantCrmHelper::AddLead(Array(
					'USER_ID' => $result['USER_ID'],
					'PHONE_NUMBER' => $params['CALLER_ID'],
					'SEARCH_ID' => $params['SEARCH_ID'],
					'INCOMING' => true,
				));
				if ($leadId)
				{
					$res = VI\CallTable::getList(Array(
						'select' => Array('ID'),
						'filter' => Array('=CALL_ID' => $params['CALL_ID']),
					));
					if ($call = $res->fetch())
					{
						VI\CallTable::update($call['ID'], Array(
							'CRM_LEAD' => $leadId,
						));
					}
				}
			}

			if ($config['CRM'] == 'Y')
			{
				CVoxImplantCrmHelper::AddCall(Array(
					'CALL_ID' => $params['CALL_ID'],
					'PHONE_NUMBER' => $params['CALLER_ID'],
					'INCOMING' => CVoxImplantMain::CALL_INCOMING,
					'USER_ID' => $result['USER_ID'],
					'DATE_CREATE' => new FieldType\DateTime()
				));
			}
		}

		if ($result['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
			if ($result['USER_ID'] > 0)
			{
				$res = VI\CallTable::getList(Array(
					'select' => Array('ID'),
					'filter' => Array('=CALL_ID' => $params['CALL_ID']),
				));
				if ($call = $res->fetch())
				{
					VI\CallTable::update($call['ID'], Array(
						'USER_ID' => $result['USER_ID'],
					));
				}

				if ($config['CRM'] == 'Y' && empty($crmData))
					$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID'], $result['USER_ID']);

				$pullResult = self::SendPullEvent(Array(
					'COMMAND' => 'invite',
					'USER_ID' => $result['USER_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'PHONE_NAME' => $config['PHONE_TITLE'],
					'CRM' => $crmData,
				));
			}
			else
			{
				$pullResult = false;
			}

			if (!$pullResult)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}

		if ($firstUserId > 0)
			$result['FIRST_USER_ID'] = $firstUserId;

		return $result;
	}

	public static function GetNextAction($params)
	{
		// TODO check $params
		$config = self::GetConfigBySearchId($params['SEARCH_ID']);
		if (!$config)
		{
			$result['COMMAND'] =  CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}
		else
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_QUEUE;
		}

		$rule = self::TYPE_CONNECT_QUEUE;
		if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_DIRECT)
		{
			$rule = $config['DIRECT_CODE_RULE'];
		}
		else if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_CRM)
		{
			$rule = $config['CRM_RULE'];
		}

		if ($rule == CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
			$result['USER_ID'] = $params['LAST_USER_ID'];
		}
		else if ($rule == CVoxImplantIncoming::RULE_PSTN)
		{
			$userPhone = CVoxImplantPhone::GetUserPhone($params['LAST_USER_ID']);
			if ($userPhone)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
				$result['PHONE_NUMBER'] = $userPhone;
				$result['USER_ID'] = $params['LAST_USER_ID'];
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
				$result['USER_ID'] = $params['LAST_USER_ID'];
			}
		}

		if ($result['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
			$result = self::GetNextInQueue(Array(
				'SEARCH_ID' => $params['SEARCH_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONENCT' => self::TYPE_CONNECT_QUEUE,
				'LAST_ANSWER_USER_ID' => 0,
				'EXCLUDE_USERS' => Array(),
				'CONFIG' => $config,
			));
		}
		else if (isset($params['LAST_USER_ID']) && $params['LAST_USER_ID'] > 0)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($params['LAST_USER_ID']),
				'CALL_ID' => $params['CALL_ID'],
			));
		}

		return $result;

	}
	public static function GetNextInQueue($params)
	{
		CVoxImplantHistory::WriteToLog($params, '!!!GetNextInQueue');
		$fistInQueue = isset($params['FIRST_IN_QUEUE']) && $params['FIRST_IN_QUEUE'] == 'Y';

		// TODO check $params
		$result = Array('COMMAND' => CVoxImplantIncoming::RULE_HUNGUP);

		if (!$fistInQueue)
		{
			$res = VI\CallTable::getList(Array(
				'select' => Array('ID', 'STATUS'),
				'filter' => Array('=CALL_ID' => $params['CALL_ID']),
			));
			$call = $res->fetch();
			if ($call['STATUS'] == VI\CallTable::STATUS_CONNECTED)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_TALK;
				return $result;
			}
		}

		if (isset($params['CONFIG']))
		{
			$config = $params['CONFIG'];
		}
		else
		{
			$config = self::GetConfigBySearchId($params['SEARCH_ID']);
			if (!$config)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
				return $result;
			}
			$params['CONFIG'] = $config;
		}

		if (isset($params['LAST_USER_ID']) && $params['LAST_USER_ID'] > 0)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($params['LAST_USER_ID']),
				'CALL_ID' => $params['CALL_ID'],
			));
		}

		$filter = Array('=CONFIG_ID' => $config['ID']);
		if (isset($params['EXCLUDE_USERS']))
		{
			$excludeUsers = $params['EXCLUDE_USERS'];
			if (!empty($excludeUsers))
				$filter['!=USER_ID'] = $excludeUsers;
		}
		$res = CVoxImplantUser::QueuedGetList(Array(
			'select' => Array('ID', 'USER_ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE' => 'USER.UF_VI_PHONE', 'ACTIVE' => 'USER.ACTIVE'),
			'filter' => $filter,
			'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
		));

		$findActiveUser = false;
		while($queueUser = $res->fetch())
		{
			if ($queueUser['IS_ONLINE_CUSTOM'] != 'Y' && $queueUser['UF_VI_PHONE'] != 'Y' || $queueUser['ACTIVE'] != 'Y')
			{
				continue;
			}

			$findActiveUser = true;
			if ($config['TIMEMAN'] == "Y" && !CVoxImplantUser::GetActiveStatusByTimeman($queueUser['USER_ID']))
			{
				$params['EXCLUDE_USERS'][] = $queueUser['USER_ID'];
				$params['LAST_USER_ID'] = 0;
				$result = self::GetNextInQueue($params);
			}
			else
			{
				VI\QueueTable::update($queueUser['ID'], Array('LAST_ACTIVITY_DATE' => new FieldType\DateTime()));

				$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
				$result['TYPE_CONNECT'] = self::TYPE_CONNECT_QUEUE;
				$result['USER_ID'] = $queueUser['USER_ID'];
				$result['USER_HAVE_PHONE'] = $queueUser['UF_VI_PHONE'] == 'Y'? 'Y': 'N';

				if (!$fistInQueue)
				{
					$crmData = Array();
					if ($config['CRM'] == 'Y')
					{
						$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID'], $queueUser['USER_ID']);
					}
					self::SendPullEvent(Array(
						'COMMAND' => 'invite',
						'USER_ID' => $queueUser['USER_ID'],
						'CALL_ID' => $params['CALL_ID'],
						'CALLER_ID' => $params['CALLER_ID'],
						'PHONE_NAME' => $config['PHONE_TITLE'],
						'CRM' => $crmData,
					));
				}
			}
			break;
		}


		if(!$findActiveUser)
		{
			$userId = intval($params['LAST_ANSWER_USER_ID']) > 0? intval($params['LAST_ANSWER_USER_ID']): intval($params['LAST_USER_ID']);
			if ($userId <= 0)
			{
				$res = VI\QueueTable::getList(Array(
					'select' => Array('ID', 'USER_ID', 'ACTIVE' => 'USER.ACTIVE'),
					'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
					'filter' => Array('=CONFIG_ID' => $config['ID']),
					'limit' => 1
				));
				$queueUser = $res->fetch();
				if ($queueUser && $queueUser['ACTIVE'])
				{
					VI\QueueTable::update($queueUser['ID'], Array('LAST_ACTIVITY_DATE' => new FieldType\DateTime()));
					$userId = $queueUser['USER_ID'];
				}
			}

			if ($config['NO_ANSWER_RULE'] != CVoxImplantIncoming::RULE_HUNGUP && (isset($params['LAST_ANSWER_USER_ID']) || isset($params['LAST_USER_ID'])))
			{

				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
				$result['USER_ID'] = $userId;

				if ($config['NO_ANSWER_RULE'] == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
				{
					if (strlen($config['FORWARD_NUMBER']) <= 0)
					{
						$config['NO_ANSWER_RULE'] == CVoxImplantIncoming::RULE_PSTN;
					}
					else
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
						$result['PHONE_NUMBER'] = NormalizePhone($config['FORWARD_NUMBER'], 1);
						$result['USER_ID'] = $userId;
					}
				}
				if ($config['NO_ANSWER_RULE'] == CVoxImplantIncoming::RULE_PSTN)
				{
					$userPhone = CVoxImplantPhone::GetUserPhone($userId);
					if ($userPhone)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
						$result['PHONE_NUMBER'] = $userPhone;
						$result['USER_ID'] = $userId;
					}
				}
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}
		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check $params
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		if ($params['COMMAND'] == 'invite')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callerId" => $params['CALLER_ID'],
				"phoneNumber" => $params['PHONE_NAME'],
				"chatId" => 0,
				"chat" => array(),
				"CRM" => $params['CRM'],
			);
		}
		else if ($params['COMMAND'] == 'update_crm')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"CRM" => $params['CRM'],
			);
		}
		else if ($params['COMMAND'] == 'timeout' || $params['COMMAND'] == 'answer_self')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
			);
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

	public static function SendCommand($params)
	{
		// TODO check $params
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $params['CALL_ID']),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		global $USER;

		$answer['COMMAND'] = $params['COMMAND'];
		$answer['OPERATOR_ID'] = $USER->GetId();
		if ($params['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_PSTN)
		{
			$answer['PHONE_NUMBER'] = '';
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_USER)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else
		{
			$answer['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
		}

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($answer));

		return true;
	}

	public static function Answer($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		global $USER;

		$ViMain = new CVoxImplantMain($USER->GetId());
		$result = $ViMain->GetDialogInfo($_POST['NUMBER']);

		if ($result)
		{
			echo CUtil::PhpToJsObject(Array(
				'DIALOG_ID' => $result['DIALOG_ID'],
				'HR_PHOTO' => $result['HR_PHOTO'],
				'ERROR' => ''
			));
		}
		else
		{
			echo CUtil::PhpToJsObject(Array(
				'CODE' => $ViMain->GetError()->code,
				'ERROR' => $ViMain->GetError()->msg
			));
		}
	}

	public static function RegisterCall($config, $params)
	{
		Bitrix\Voximplant\CallTable::add(Array(
			'CONFIG_ID' => $config['ID'],
			'CALL_ID' => $params['CALL_ID'],
			'USER_ID' => 0,
			'CALLER_ID' => $params['CALLER_ID'],
			'STATUS' => Bitrix\Voximplant\CallTable::STATUS_CONNECTING,
			'CRM' => $config['CRM'],
			'ACCESS_URL' => $params['ACCESS_URL'],
			'DATE_CREATE' => new Bitrix\Main\Type\DateTime(),
		));

		if ($config['WORKTIME_SKIP_CALL'] == 'Y')
		{
			$config['WORKTIME_USER_ID'] = 0;
			if ($config['CRM'] == 'Y')
			{
				$existsLead = false;
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID']);
				if (isset($crmData['RESPONSIBILITY']['ID']) && $crmData['RESPONSIBILITY']['ID'] > 0)
				{
					$config['WORKTIME_USER_ID'] = $crmData['RESPONSIBILITY']['ID'];
					$existsLead = true;
				}
				else
				{
					$res = VI\QueueTable::getList(Array(
						'select' => Array('ID', 'USER_ID'),
						'filter' => Array('=CONFIG_ID' => $config['ID']),
						'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
						'limit' => 1
					));
					$queueUser = $res->fetch();
					if ($queueUser)
					{
						VI\QueueTable::update($queueUser['ID'], Array('LAST_ACTIVITY_DATE' => new FieldType\DateTime()));
						$config['WORKTIME_USER_ID'] = $queueUser["USER_ID"];
					}

					if ($config['WORKTIME_USER_ID'] > 0 && $crmData['FOUND'] == 'N' && $config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
					{
						$id = CVoxImplantCrmHelper::AddLead(Array(
							'USER_ID' => $config['WORKTIME_USER_ID'],
							'PHONE_NUMBER' => $params['CALLER_ID'],
							'SEARCH_ID' => $params['SEARCH_ID'],
							'INCOMING' => true,
						));
						if ($id)
						{
							$existsLead = true;
						}
					}
				}

				if ($existsLead)
				{
					CVoxImplantCrmHelper::AddCall(Array(
						'CALL_ID' => $params['CALL_ID'],
						'PHONE_NUMBER' => $params['CALLER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'USER_ID' => $config['WORKTIME_USER_ID'],
						'DATE_CREATE' => new FieldType\DateTime()
					));
					CVoxImplantCrmHelper::UpdateCall(Array(
						'CALL_ID' => $params['CALL_ID'],
						'PHONE_NUMBER' => $params['CALLER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'USER_ID' => $config['WORKTIME_USER_ID'],
						'DESCRIPTION' => GetMessage("WORKTIME_CALL")
					));
				}
			}
			else
			{
				$res = VI\QueueTable::getList(Array(
					'select' => Array('ID', 'USER_ID'),
					'filter' => Array('=CONFIG_ID' => $config['ID']),
					'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
					'limit' => 1
				));
				$queueUser = $res->fetch();
				if ($queueUser)
				{
					$config['WORKTIME_USER_ID'] = $queueUser["USER_ID"];
				}
			}
		}

		return $config;
	}

	public static function IsNumberInBlackList($number)
	{
		$dbBlacklist = VI\BlacklistTable::getList(
			array(
				"filter" => array("PHONE_NUMBER" => $number)
			)
		);
		if ($dbBlacklist->fetch())
		{
			return true;
		}

		return false;
	}

	public static function CheckNumberForBlackList($number)
	{
		$blackListTime = Bitrix\Main\Config\Option::get("voximplant", "blacklist_time", 5);
		$blackListCount = Bitrix\Main\Config\Option::get("voximplant", "blacklist_count", 5);

		$minTime = new Bitrix\Main\Type\DateTime();
		$minTime->add('-'.$blackListTime.' minutes');

		$dbData = VI\StatisticTable::getList(array(
			'filter' => array(
				"PHONE_NUMBER" => $number,
				'>CALL_START_DATE' => $minTime,
			),
			'select' => array('ID')
		));

		$callsCount = 0;
		while($dbData->fetch())
		{
			$callsCount++;
			if ($callsCount >= $blackListCount)
			{
				$number = substr($number, 0, 20);
				VI\BlacklistTable::add(array(
					"PHONE_NUMBER" => $number
				));

				$messageUserId = Bitrix\Main\Config\Option::get("voximplant", "blacklist_user_id", "");
				CVoxImplantHistory::SendMessageToChat(
					$messageUserId,
					$number,
					CVoxImplantMain::CALL_INCOMING,
					GetMessage("BLACKLIST_NUMBER")
				);

				return true;
			}
		}

		return false;
	}
}
?>
