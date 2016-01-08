<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantCrmHelper
{
	public static function GetCrmEntity($phone, $userId = 0)
	{
		if (strlen($phone) <= 0 || intval($userId) <= 0 || !CModule::IncludeModule('crm'))
		{
			return false;
		}

		$arResult = false;

		$crm = CCrmSipHelper::findByPhoneNumber($phone, array('USER_ID'=> $userId));
		if ($crm)
		{
			if (isset($crm['CONTACT']))
			{
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Contact;
				$arResult['ENTITY_ID'] = $crm['CONTACT'][0]['ID'];
			}
			else if (isset($crm['LEAD']))
			{
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Lead;
				$arResult['ENTITY_ID'] = $crm['LEAD'][0]['ID'];
			}
			else if (isset($crm['COMPANY']))
			{
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Company;
				$arResult['ENTITY_ID'] = $crm['COMPANY'][0]['ID'];
			}

			$arResult['BINDINGS'] = Array();
			if (isset($crm['CONTACT']) || isset($crm['COMPANY']))
			{
				if (isset($crm['CONTACT'][0]))
				{
					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $crm['CONTACT'][0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Contact
					);
				}
				if (isset($crm['COMPANY'][0]))
				{
					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $crm['COMPANY'][0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Company
					);
				}

				$deals = self::findDealsByPhone($phone);
				if ($deals)
				{
					$arResult['DEALS'] = $deals;

					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $deals[0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Deal
					);
				}
			}
			else if (isset($crm['LEAD'][0]))
			{

				$arResult['BINDINGS'][] = array(
					'OWNER_ID' => $crm['LEAD'][0]['ID'],
					'OWNER_TYPE_ID' => CCrmOwnerType::Lead
				);
			}
		}

		return $arResult;
	}

	public static function GetDataForPopup($callId, $phone, $userId = 0)
	{
		if (strlen($phone) <= 0 || !CModule::IncludeModule('crm'))
		{
			return false;
		}

		if ($userId > 0)
		{
			$findParams = array('USER_ID'=> $userId);
		}
		else
		{
			$findParams = array('ENABLE_EXTENDED_MODE'=> false);
		}

		$crm = CCrmSipHelper::findByPhoneNumber((string)$phone, $findParams);
		if ($crm)
		{
			$dealStatuses = CCrmStatus::GetStatus('DEAL_STAGE');

			$entity = '';
			$entityData = Array();
			$entities = Array();

			$arResult = Array(
				'FOUND' => 'Y',
				'CONTACT' => Array(),
				'COMPANY' => Array(),
				'ACTIVITIES' => Array(),
				'DEALS' => Array(),
				'RESPONSIBILITY' => Array()
			);

			if (isset($crm['CONTACT']))
			{
				$entity = 'CONTACT';
				$entityData = $crm[$entity][0];

				$arResult['CONTACT'] = Array(
					'NAME' => $entityData['FORMATTED_NAME'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);
				if (intval($entityData['PHOTO']) > 0)
				{
					$arPhoto = CFile::ResizeImageGet(
						$entityData['PHOTO'],
						array('width' => 37, 'height' => 37),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$arResult['CONTACT']['PHOTO'] = $arPhoto['src'];
				}

				$arResult['COMPANY'] = $entityData['COMPANY_TITLE'];
			}
			else if (isset($crm['LEAD']))
			{
				$entity = 'LEAD';
				$entityData = $crm[$entity][0];

				$arResult['CONTACT'] = Array(
					'NAME' => !empty($entityData['FORMATTED_NAME'])? $entityData['FORMATTED_NAME']: $entityData['TITLE'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);

				$arResult['COMPANY'] = $entityData['COMPANY_TITLE'];
			}
			else if (isset($crm['COMPANY']))
			{
				$entity = 'COMPANY';
				$entityData = $crm[$entity][0];

				$arResult['COMPANY'] = $entityData['TITLE'];
			}

			if ($entityData['ASSIGNED_BY_ID'] > 0)
			{
				$ar = Bitrix\Main\UserTable::getById($entityData['ASSIGNED_BY_ID']);
				if ($user = $ar->fetch())
				{
					$arPhoto = CFile::ResizeImageGet(
						$user['PERSONAL_PHOTO'],
						array('width' => 37, 'height' => 37),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);

					$arResult['RESPONSIBILITY'] = Array(
						'ID' => $user['ID'],
						'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $user, true, false),
						'PHOTO' => $arPhoto? $arPhoto['src']: '',
						'POST' => $user['WORK_POSITION'],
					);
				}
			}

			if (isset($entityData['SHOW_URL']))
			{
				$arResult['SHOW_URL'] = $entityData['SHOW_URL'];
			}
			if (isset($entityData['ACTIVITY_LIST_URL']))
			{
				$arResult['ACTIVITY_URL'] = $entityData['ACTIVITY_LIST_URL'];
			}
			if (isset($entityData['INVOICE_LIST_URL']))
			{
				$arResult['INVOICE_URL'] = $entityData['INVOICE_LIST_URL'];
			}
			if (isset($entityData['DEAL_LIST_URL']))
			{
				$arResult['DEAL_URL'] = $entityData['DEAL_LIST_URL'];
			}

			$activityId = CCrmActivity::GetIDByOrigin('VI_'.$callId);
			if ($activityId)
			{
				$arResult['CURRENT_CALL_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Activity, $activityId);
				if($arResult['CURRENT_CALL_URL'] !== '')
				{
					$arResult['CURRENT_CALL_URL'] = CCrmUrlUtil::AddUrlParams($arResult['CURRENT_CALL_URL'], array("disable_storage_edit" => 'Y'));
				}
			}

			if (isset($crm['CONTACT']) && isset($crm['COMPANY']))
			{
				$entities = Array('CONTACT', 'COMPANY', 'LEAD');
			}
			else if (isset($crm['CONTACT']) && isset($crm['LEAD']) && !isset($crm['COMPANY']))
			{
				$entities = Array('CONTACT', 'LEAD');
			}
			else if (isset($crm['LEAD']) && isset($crm['COMPANY']) && !isset($crm['CONTACT']))
			{
				$entities = Array('LEAD', 'COMPANY');
			}
			else
			{
				$entities = Array($entity);
			}
			foreach ($entities as $entity)
			{
				if (isset($crm[$entity][0]['ACTIVITIES']))
				{
					foreach ($crm[$entity][0]['ACTIVITIES'] as $activity)
					{
						if ($activity['ID'] == $activityId)
							continue;

						$overdue = 'N';
						if (strlen($activity['DEADLINE']) > 0 && MakeTimeStamp($activity['DEADLINE']) < time())
						{
							$overdue = 'Y';
						}

						$arResult['ACTIVITIES'][] = Array(
							'TITLE' => $activity['SUBJECT'],
							'DATE' => strlen($activity['DEADLINE']) > 0? $activity['DEADLINE']: $activity['END_TIME'],
							'OVERDUE' => $overdue,
							'URL' => $activity['SHOW_URL'],
						);
					}
				}

				if (isset($crm[$entity][0]['DEALS']))
				{
					foreach ($crm[$entity][0]['DEALS'] as $deal)
					{
						$arResult['DEALS'][] = Array(
							'TITLE' => $deal['TITLE'],
							'STAGE' => $dealStatuses[$deal['STAGE_ID']]['NAME'],
							'URL' => $deal['SHOW_URL'],
						);
					}
				}
			}
		}
		else
		{
			$arResult = Array('FOUND' => 'N');

			$userPermissions = CCrmPerms::GetUserPermissions($userId);
			if (CCrmLead::CheckCreatePermission($userPermissions))
			{
				$arResult['LEAD_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Lead, 0);
				if($arResult['LEAD_URL'] !== '')
				{
					$arResult['LEAD_URL'] = CCrmUrlUtil::AddUrlParams($arResult['LEAD_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
			if (CCrmContact::CheckCreatePermission($userPermissions))
			{
				$arResult['CONTACT_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0);
				if($arResult['CONTACT_URL'] !== '')
				{
					$arResult['CONTACT_URL'] = CCrmUrlUtil::AddUrlParams($arResult['CONTACT_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
		}
		return $arResult;
	}

	public static function AddCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		CVoxImplantHistory::WriteToLog($params, 'CRM ADD CALL');
		$crmEntity = self::GetCrmEntity($params['PHONE_NUMBER'], $params['USER_ID']);
		if (!$crmEntity)
		{
			return false;
		}

		$direction = isset($params['INCOMING']) && intval($params['INCOMING']) === CVoxImplantMain::CALL_INCOMING
			? CCrmActivityDirection::Incoming
			: CCrmActivityDirection::Outgoing;

		$arFields = array(
			'TYPE_ID' =>  CCrmActivityType::Call,
			'SUBJECT' => GetMessage('VI_CRM_TITLE'),
			'START_TIME' => $params['DATE_CREATE'],
			'COMPLETED' => 'N',
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => '',
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => '',
			'DIRECTION' => $direction,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'BINDINGS' => array(),
			'SETTINGS' => array(),
			'AUTHOR_ID' => $params['USER_ID']
		);

		$arFields['RESPONSIBLE_ID'] = $params['USER_ID'];
		$arFields['ORIGIN_ID'] = 'VI_'.$params['CALL_ID'];

		if (isset($crmEntity['BINDINGS']))
		{
			$arFields['BINDINGS'] = $crmEntity['BINDINGS'];
		}
		else
		{
			$arFields['BINDINGS'][] = array(
				'OWNER_ID' => $crmEntity['ENTITY_ID'],
				'OWNER_TYPE_ID' => $crmEntity['ENTITY_TYPE']
			);
		}

		$arComms = array(
			array(
				'ID' => 0,
				'TYPE' => 'PHONE',
				'VALUE' => $params['PHONE_NUMBER'],
				'ENTITY_ID' => $crmEntity['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $crmEntity['ENTITY_TYPE']
			)
		);

		$ID = CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
		if($ID > 0)
		{
			CCrmActivity::SaveCommunications($ID, $arComms, $arFields, true, false);
		}

		//CCrmActivity::SaveBindings($ID, $arFields['BINDINGS'])

		return true;
	}

	public static function UpdateCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}
		CVoxImplantHistory::WriteToLog($params, 'CRM UPDATE TO CALL');

		$activity = CCrmActivity::GetByOriginID('VI_'.$params['CALL_ID'], false);
		if ($activity)
		{
			$params = CVoxImplantHistory::PrepereData($params);
			if (isset($params['DESCRIPTION']) && strlen($params['DESCRIPTION']) > 0)
			{
				$description = $params['DESCRIPTION'];
			}
			else
			{
				if($params['CALL_DURATION'] > 0)
				{
					$description = GetMessage('VI_CRM_CALL_DURATION', array('#DURATION#' => $params['CALL_DURATION_TEXT']));
				}
				else
				{
					$description = GetMessage('VI_CRM_CALL_STATUS').' '.$params['CALL_FAILED_REASON'];
				}
			}

			if ($params['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
			{
				$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
				$portalNumber = isset($portalNumbers[$params['PORTAL_NUMBER']])? $portalNumbers[$params['PORTAL_NUMBER']]: '';
				if ($portalNumber)
				{
					$description = $description."\n".GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
				}
			}

			$arFields = array(
				'DESCRIPTION' => (strlen($activity['DESCRIPTION'])>0? $activity['DESCRIPTION']."\n":'').$description,
				'COMPLETED' => 'Y'
			);
			CCrmActivity::Update($activity['ID'], $arFields, false, true, Array('REGISTER_SONET_EVENT' => true));
		}

		return true;
	}

	public static function AttachRecordToCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		CVoxImplantHistory::WriteToLog($params, 'CRM ATTACH RECORD TO CALL');
		if ($params['CALL_WEBDAV_ID'] > 0)
		{
			$activityId = CCrmActivity::GetIDByOrigin('VI_'.$params['CALL_ID']);
			if ($activityId)
			{
				$arFields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
				$arFields['STORAGE_ELEMENT_IDS'] = array($params['CALL_WEBDAV_ID']);
				CCrmActivity::Update($activityId, $arFields, false);
			}
		}
		
		return true;
	}

	public static function RegisterEntity($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$callId = $params['ORIGIN_ID'];
		$callerId = '';
		if (substr($callId, 0, 3) == 'VI_')
			$callId = substr($callId, 3);


		$res = VI\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $callId),
		));
		if ($call = $res->fetch())
		{
			$callerId = $call['CALLER_ID'];

			CVoxImplantCrmHelper::AddCall(Array(
				'CALL_ID' => $call['CALL_ID'],
				'PHONE_NUMBER' => $call['CALLER_ID'],
				'INCOMING' => $call['INCOMING'],
				'USER_ID' => $call['USER_ID'],
				'DATE_CREATE' => $call['DATE_CREATE']
			));

			if ($call['USER_ID'] > 0)
			{
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($callId, $call['CALLER_ID'], $call['USER_ID']);

				$pullResult = CVoxImplantIncoming::SendPullEvent(Array(
					'COMMAND' => 'update_crm',
					'USER_ID' => $call['USER_ID'],
					'CALL_ID' => $callId,
					'CALLER_ID' => $callerId,
					'CRM' => $crmData,
				));
			}

			CVoxImplantHistory::WriteToLog(Array($callId, $call), 'CRM ATTACH INIT CALL');
		}
		else
		{
			$res = VI\StatisticTable::getList(Array(
				'filter' => Array('=CALL_ID' => $callId),
			));
			if ($history = $res->fetch())
			{
				$history['USER_ID'] = $history['PORTAL_USER_ID'];
				$history['DATE_CREATE'] = $history['CALL_START_DATE'];

				CVoxImplantCrmHelper::AddCall(Array(
					'CALL_ID' => $history['CALL_ID'],
					'PHONE_NUMBER' => $history['PHONE_NUMBER'],
					'INCOMING' => $history['INCOMING'] == CVoxImplantMain::CALL_OUTGOING? CVoxImplantMain::CALL_OUTGOING: CVoxImplantMain::CALL_INCOMING,
					'USER_ID' => $history['USER_ID'],
					'DATE_CREATE' => $history['DATE_CREATE']
				));

				CVoxImplantCrmHelper::UpdateCall($history);

				CVoxImplantCrmHelper::AttachRecordToCall(Array(
					'CALL_ID' => $history['CALL_ID'],
					'CALL_WEBDAV_ID' => $history['CALL_WEBDAV_ID'],
					'CALL_RECORD_ID' => $history['CALL_RECORD_ID'],
				));

				CVoxImplantHistory::WriteToLog(Array($callId), 'CRM ATTACH FULL CALL');
			}
		}

		return true;
	}

	public static function AddLead($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		if (strlen($params['PHONE_NUMBER']) <= 0 || intval($params['USER_ID']) <= 0)
		{
			return false;
		}

		$dateNow = new Bitrix\Main\Type\DateTime();
		$title = GetMessage($params['INCOMING']? 'VI_CRM_CALL_INCOMING': 'VI_CRM_CALL_OUTGOING');

		$arFields = array(
			'TITLE' => $title.' '.$dateNow->format('H:i d.m.Y'),
			'OPENED' => 'Y',
			'PHONE_WORK' => $params['PHONE_NUMBER'],
		);

		$statuses = CCrmStatus::GetStatusList("SOURCE");
		if (isset($statuses['CALL']))
		{
			$arFields['SOURCE_ID'] = 'CALL';
		}

		$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
		$portalNumber = isset($portalNumbers[$params['SEARCH_ID']])? $portalNumbers[$params['SEARCH_ID']]: '';
		if ($portalNumber)
		{
			$arFields['SOURCE_DESCRIPTION'] = GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
		}

		$arFields['FM'] = CCrmFieldMulti::PrepareFields($arFields);

		$CCrmLead = new CCrmLead(false);
		$ID = $CCrmLead->Add($arFields, true, Array(
			'CURRENT_USER' => $params['USER_ID'],
			'DISABLE_USER_FIELD_CHECK' => true
		));

		$arErrors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$ID,
			CCrmBizProcEventType::Create,
			$arErrors
		);

		CVoxImplantHistory::WriteToLog($arFields, 'LEAD CREATED');
		return $ID;
	}

	public static function UpdateLead($id, $params)
	{
		if (!isset($params['ASSIGNED_BY_ID']))
			return false;

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$update = Array('ASSIGNED_BY_ID' => $params['ASSIGNED_BY_ID']);

		$CCrmLead = new CCrmLead(false);
		$CCrmLead->Update($id, $update);

		return true;
	}

	public static function findDealsByPhone($phone)
	{
		if (strlen($phone) <= 0)
		{
			return false;
		}

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$deals = array();

		$entityTypeIDs = array(CCrmOwnerType::Contact, CCrmOwnerType::Company);
		foreach($entityTypeIDs as $entityTypeID)
		{
			$results = CCrmDeal::FindByCommunication($entityTypeID, 'PHONE', $phone, false, array('ID', 'TITLE', 'STAGE_ID', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'DATE_MODIFY'));
			foreach($results as $fields)
			{
				if(Bitrix\Crm\PhaseSemantics::isFinal($fields['STAGE_ID']))
				{
					continue;
				}

				$entityID = (int)($entityTypeID === CCrmOwnerType::Company ? $fields['COMPANY_ID'] : $fields['CONTACT_ID']);
				if($entityID <= 0)
				{
					continue;
				}

				$deals[$fields['ID']] = $fields;
			}
		}

		sortByColumn($deals, array('DATE_MODIFY' => array(SORT_DESC)));

		return $deals;
	}
}
