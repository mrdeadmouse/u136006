<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CTaskNotifications
{
	private static $arBuiltInTasksXmlIds = array(
		'6dfecf46063cd844ebeecf1873cff791',
		'148c0ccdbd25870eb632557e3327cb1c',
		'0cde03b1a29df438ba454327249a0750',
		'c5156d1b21fc626340295523a1074a8c',
		'c20c713f668b08f2804a3d007d724196',
		'52106f124d9f1b50d6315df50c696c93',
		'00000010000000000000000000000001',
		'00000010000000000000000000000002'
	);


	/**
	 * Sends notifications to IM.
	 *
	 * @param $fromUserID
	 * @param $arRecipientsIDs
	 * @param $message
	 * @param int $taskID
	 * @param null $message_email
	 * @param array $arEventData
	 * @return bool|null
	 */
	public static function SendMessage($fromUserID, $arRecipientsIDs, $message, 
		$taskID = 0, $message_email = null, $arEventData = array(),
		$taskAssignedTo = null
	)
	{
		if (!(IsModuleInstalled("im") && CModule::IncludeModule("im")))
			return false;

		$message_email = is_null($message_email)? $message: $message_email;

		if ( ! ($fromUserID && $arRecipientsIDs && $message) )
			return (false);

		CTaskAssert::assert(is_array($arEventData));

		$arEventData['fromUserID']      = &$fromUserID;
		$arEventData['arRecipientsIDs'] = &$arRecipientsIDs;
		$arEventData['message']         = &$message;
		$arEventData['message_email']   = &$message_email;

		foreach(GetModuleEvents('tasks', 'OnBeforeTaskNotificationSend', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($arEventData)) === false)
				return false;
		}

		$arSites = array();
		if (CModule::IncludeModule("extranet"))
		{
			$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));
			while($arSite = $dbSite->Fetch())
			{
				$type = ($arSite["ID"] == CExtranet::GetExtranetSiteID() ? "EXTRANET" : "INTRANET");

				if (
					($type === 'INTRANET')
					&& isset($arSites['INTRANET'])
					&& ($arSite['DEF'] !== 'Y')
				)
				{
					// Don't overwrite INTRANET site data by not default site
					continue;
				}

				$arSites[$type] = array(
					'SITE_ID' => $arSite['ID'],
					"DIR" => (strlen(trim($arSite["DIR"])) > 0 ? $arSite["DIR"] : "/"),
					"SERVER_NAME" => (strlen(trim($arSite["SERVER_NAME"])) > 0 ? $arSite["SERVER_NAME"] : COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]))
				);
			}
		}

		if (is_array($arRecipientsIDs) && count($arRecipientsIDs))
		{
			$arRecipientsIDs = array_unique($arRecipientsIDs);
			$rsUser = CUser::GetList(
				$by = 'ID',
				$order = 'ASC',
				array('ID' => implode('|', $arRecipientsIDs)),
				array('FIELDS' => array('ID'))
			);

			while ($arUser = $rsUser->Fetch())
			{
				$notifyEvent = 'manage';

				if ($taskAssignedTo !== null)
				{
					if ($arUser['ID'] == $taskAssignedTo)
						$notifyEvent = 'task_assigned';
				}

				$pathToTask = CTaskNotifications::GetNotificationPath($arUser, $taskID, true, $arSites);
				$arMessageFields = array(
					"TO_USER_ID" => $arUser['ID'],
					"FROM_USER_ID" => $fromUserID, 
					"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
					"NOTIFY_MODULE" => "tasks", 
					"NOTIFY_EVENT" => $notifyEvent, 
					"NOTIFY_MESSAGE" => str_replace("#PATH_TO_TASK#", $pathToTask, $message),
					"NOTIFY_MESSAGE_OUT" => strip_tags(str_replace("#PATH_TO_TASK#", $pathToTask, $message_email))
				);
				CIMNotify::Add($arMessageFields);
			}
		}

		return (null);
	}


	protected static function SendMessageToSocNet($arFields, $bSpawnedByAgent, $arChanges = null, $arTask = null)
	{
		global $USER, $DB;

		$effectiveUserId = false;

		if (($bSpawnedByAgent === true) || ($bSpawnedByAgent === 'Y'))
		{
			if (isset($arFields['CREATED_BY']) && ($arFields['CREATED_BY'] > 0))
				$effectiveUserId = (int) $arFields['CREATED_BY'];
			else
				$effectiveUserId = 1;
		}
		elseif (is_object($USER) && method_exists($USER, 'getId'))
			$effectiveUserId = (int) $USER->getId();

		if ( ! CModule::IncludeModule('socialnetwork') )
			return (null);

		static $arCheckedUsers = array();		// users that checked for their existing
		static $cachedSiteTimeFormat = -1;

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = $effectiveUserId;

		if ($cachedSiteTimeFormat === -1)
			$cachedSiteTimeFormat = CSite::GetDateFormat('FULL', SITE_ID);

		static $cachedAllSitesIds = -1;

		if ($cachedAllSitesIds === -1)
		{
			$cachedAllSitesIds = array();

			$dbSite = CSite::GetList(
				$by = 'sort', 
				$order = 'desc', 
				array('ACTIVE' => 'Y')
			);

			while ($arSite = $dbSite->Fetch())
				$cachedAllSitesIds[] = $arSite['ID'];
		}

		// Check that user exists
		if ( ! in_array( (int) $arFields["CREATED_BY"], $arCheckedUsers, true) )
		{
			$rsUser = CUser::GetList(
				$by = 'ID',
				$order = 'ASC',
				array('ID' => $arFields["CREATED_BY"]), 
				array('FIELDS' => array('ID'))
			);

			if ( ! ($arUser = $rsUser->Fetch()) )
				return (false);

			$arCheckedUsers[] = (int) $arFields["CREATED_BY"];
		}

		if (is_array($arChanges))
		{
			if (count($arChanges) == 0)
			{
				$rsSocNetLogItems = CSocNetLog::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => "tasks",
						"SOURCE_ID" => $arTask["ID"]
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID")
				);

				while ($arRes = $rsSocNetLogItems->Fetch())
				{
					$authorUserId = false;
					if (isset($arFields['CREATED_BY']))
						$authorUserId = (int) $arFields['CREATED_BY'];
					elseif (isset($arTask['CREATED_BY']))
						$authorUserId = (int) $arTask['CREATED_BY'];

					// Add author to list of users that view log about task in livefeed
					// But only when some other person change task
					// or if added FORUM_TOPIC_ID
					if (
						($authorUserId !== $effectiveUserId)
						|| (
							($arTask['FORUM_TOPIC_ID'] == 0)
							&& isset($arFields['FORUM_TOPIC_ID'])
							&& ($arFields['FORUM_TOPIC_ID'] > 0)
						)
					)
					{
						$authorGroupCode = 'U' . $authorUserId;

						$rsRights = CSocNetLogRights::GetList(
							array(),
							array(
								'LOG_ID'     => $arRes['ID'],
								'GROUP_CODE' => $authorGroupCode
							)
						);

						// If task's author hasn't rights yet, give them
						if ( ! ($arRights = $rsRights->fetch()) )
							CSocNetLogRights::Add($arRes["ID"], array($authorGroupCode));
					}
				}

				return (null);
			}
			elseif ((count($arChanges) == 1) && isset($arChanges['STATUS']))
				return (null);	// if only status changes - don't send message, because it will be send by SendStatusMessage()
		}

		if ($bSpawnedByAgent === 'Y')
			$bSpawnedByAgent = true;
		elseif ($bSpawnedByAgent === 'N')
			$bSpawnedByAgent = false;

		if ( ! is_bool($bSpawnedByAgent) )
			return (false);

		$taskId = false;
		if (is_array($arFields) && isset($arFields['ID']) && ($arFields['ID'] > 0))
			$taskId = $arFields['ID'];
		elseif (is_array($arTask) && isset($arTask['ID']) && ($arTask['ID'] > 0))
			$taskId = $arTask['ID'];

		// We will mark this to false, if we send update message and log item already exists
		$bSocNetAddNewItem = true;

		$logDate = $DB->CurrentTimeFunction();
		$curTimeTimestamp = time() + CTimeZone::GetOffset();
		$arSoFields = array(
			'EVENT_ID'  => 'tasks',
			'TITLE'     => $arFields['TITLE'],
			'MESSAGE'   => '',
			'MODULE_ID' => 'tasks'
		);

		// If changes and task data given => we are prepare "update" message,
		// or "add" message otherwise
		if (is_array($arChanges) && is_array($arTask))
		{	// Prepare "update" message here
			if (strlen($arFields["CHANGED_DATE"]) > 0)
			{
				$createdDateTimestamp = MakeTimeStamp(
					$arFields["CHANGED_DATE"], 
					$cachedSiteTimeFormat
				);

				if ($createdDateTimestamp > $curTimeTimestamp)
				{
					$logDate = $DB->CharToDateFunction(
						$arFields["CHANGED_DATE"], 
						"FULL", 
						SITE_ID
					);
				}
			}

			$arChangesFields = array_keys($arChanges);
			$arSoFields['TEXT_MESSAGE'] = str_replace(
				'#CHANGES#', 
				implode(
					', ', 
					CTaskNotifications::__Fields2Names($arChangesFields)
				),
				GetMessage('TASKS_SONET_TASK_CHANGED_MESSAGE')
			);

			// Determine, does item exists in sonet log
			$rsSocNetLogItems = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $arTask["ID"]
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID")
			);

			if (
				(($arFields["GROUP_ID"] === NULL) && $arTask['GROUP_ID'])	// If tasks has group and it not deleted
				|| ($arFields['GROUP_ID'])	// Or new group_id set
			)
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
				$arSoFields["ENTITY_ID"] = ($arFields["GROUP_ID"] ? $arFields["GROUP_ID"] : $arTask['GROUP_ID']);
			}
			else
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
				$arSoFields["ENTITY_ID"] = ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]);
			}

			$arSoFields['PARAMS'] = serialize(
				array(
					'TYPE'           => 'modify', 
					'CHANGED_FIELDS' => $arChangesFields,
					'CREATED_BY'     => ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]),
					'CHANGED_BY'     => ($occurAsUserId ? $occurAsUserId : $arFields['CHANGED_BY']),
					'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
				)
			);

			if ($rsSocNetLogItems->Fetch())
				$bSocNetAddNewItem = false;		// item already exists, update it, not create.
		}
		else	// Prepare "add" message here
		{
			if (strlen($arFields["CREATED_DATE"]) > 0)
			{
				$createdDateTimestamp = MakeTimeStamp(
					$arFields["CREATED_DATE"], 
					$cachedSiteTimeFormat
				);

				if ($createdDateTimestamp > $curTimeTimestamp)
				{
					$logDate = $DB->CharToDateFunction(
						$arFields["CREATED_DATE"], 
						"FULL", 
						SITE_ID
					);
				}
			}

			$arSoFields['TEXT_MESSAGE'] = GetMessage('TASKS_SONET_NEW_TASK_MESSAGE');

			if($arFields["GROUP_ID"])
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
				$arSoFields["ENTITY_ID"] = $arFields["GROUP_ID"];
			}
			else
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
				$arSoFields["ENTITY_ID"] = $arFields["CREATED_BY"];
			}

			$arParamsLog = array(
				'TYPE' => 'create',
				'CREATED_BY' => ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]),
				'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
			);

			if ($occurAsUserId)
				$arParamsLog["CREATED_BY"] = $occurAsUserId;

			$arSoFields['PARAMS'] = serialize($arParamsLog);
		}

		// rating entity id (ilike)
		$arSoFields["RATING_ENTITY_ID"] =  $taskId;
		$arSoFields["RATING_TYPE_ID"] = "TASK";

		// Do we need add new item to socnet?
		// We adds new item, if it is not exists.
		$logID = false;

		if (
			IsModuleInstalled("webdav")
			|| IsModuleInstalled("disk")
		)
		{
			$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("TASKS_TASK", "UF_TASK_WEBDAV_FILES", $taskId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$arSoFields["UF_SONET_LOG_DOC"] = $ufDocID;
			}
		}

		if ($bSocNetAddNewItem)
		{
			$arSoFields['=LOG_DATE']       = $logDate;
			$arSoFields['CALLBACK_FUNC']   = false;
			$arSoFields['SOURCE_ID']       = $taskId;
			$arSoFields['ENABLE_COMMENTS'] = 'Y';
			$arSoFields['URL']             = CTaskNotifications::GetNotificationPath(
				array('ID' => (int) $arFields["CREATED_BY"]),
				$taskId,
				false
			);
			$arSoFields['USER_ID']         = $arFields['CREATED_BY'];
			$arSoFields['TITLE_TEMPLATE']  = '#TITLE#';

			// Set all sites because any user from any site may be
			// added to task in future. For example, new auditor, etc.
			$arSoFields['SITE_ID'] = $cachedAllSitesIds;

			$logID = CSocNetLog::Add($arSoFields, false);

			if (intval($logID) > 0)
			{
				CSocNetLog::Update($logID, array("TMP_ID" => $logID));
				$arTaskParticipant = CTaskNotifications::GetRecipientsIDs(
					$arFields, 
					false		// don't exclude current user
				);

				// Exclude author
				$arLogCanViewedBy = array_diff($arTaskParticipant, array($arFields['CREATED_BY']));

				$arRights = CTaskNotifications::__UserIDs2Rights($arLogCanViewedBy);

				if (isset($arFields['GROUP_ID']))
				{
					$arRights = array_merge(
						$arRights,
						self::prepareRightsCodesForViewInGroupLiveFeed($logID, $arFields['GROUP_ID'])
					);
				}

				CSocNetLogRights::Add($logID, $arRights);
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
			}
		}
		else	// Update existing log item
		{
			$arSoFields['=LOG_DATE']   = $logDate;
			$arSoFields['=LOG_UPDATE'] = $logDate;

			// All tasks posts in live feed should be from director
			if (isset($arFields['CREATED_BY']))
				$arSoFields['USER_ID'] = $arFields['CREATED_BY'];
			elseif (isset($arTask['CREATED_BY']))
				$arSoFields['USER_ID'] = $arTask['CREATED_BY'];
			elseif ($occurAsUserId)
				$arSoFields['USER_ID'] = $occurAsUserId;
			else
				unset ($arSoFields['USER_ID']);

			$rsSocNetLogItems = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $arTask["ID"]
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID")
			);

			while ($arRes = $rsSocNetLogItems->Fetch())
			{
				CSocNetLog::Update($arRes["ID"], $arSoFields);

				$arTaskParticipant = CTaskNotifications::GetRecipientsIDs(
					$arFields,	// Only new tasks' participiants should view log event, fixed due to http://jabber.bx/view.php?id=34504
					false,		// don't exclude current user
					true		// exclude additional recipients (because there are previous members of task)
				);

				$bAuthorMustBeExcluded = false;

				$authorUserId = false;
				if (isset($arFields['CREATED_BY']))
					$authorUserId = (int) $arFields['CREATED_BY'];
				elseif (isset($arTask['CREATED_BY']))
					$authorUserId = (int) $arTask['CREATED_BY'];

				// Get current rights
				$rsRights = CSocNetLogRights::GetList(
					array(),
					array('LOG_ID' => $arRes['ID'])
				);

				$arCurrentRights = array();
				while ($arRights = $rsRights->fetch())
					$arCurrentRights[] = $arRights['GROUP_CODE'];

				// If author changes the task and author doesn't have
				// access to task yet, don't give access to him.
				if ($authorUserId === $effectiveUserId)
				{
					$authorGroupCode = 'U' . $authorUserId;

					// If task's author hasn't rights yet, still exclude him
					if ( ! in_array($authorGroupCode, $arCurrentRights, true) )
						$bAuthorMustBeExcluded = true;
				}

				if ($bAuthorMustBeExcluded)
					$arLogCanViewedBy = array_diff($arTaskParticipant, array($authorUserId));
				else
					$arLogCanViewedBy = $arTaskParticipant;

				$arNewRights = CTaskNotifications::__UserIDs2Rights($arLogCanViewedBy);

				$bGroupChanged = false;
				if (
					isset($arFields['GROUP_ID'], $arTask['GROUP_ID'])
					&& ($arFields['GROUP_ID'])
					&& ($arFields['GROUP_ID'] != $arTask['GROUP_ID'])
				)
				{
					$bGroupChanged = true;
				}

				// If rights really changed, update them
				if (
					count(array_diff($arCurrentRights, $arNewRights))
					|| count(array_diff($arNewRights, $arCurrentRights))
					|| $bGroupChanged
				)
				{
					if (isset($arFields['GROUP_ID']))
					{
						$arNewRights = array_merge(
							$arNewRights,
							self::prepareRightsCodesForViewInGroupLiveFeed($logID, $arFields['GROUP_ID'])
						);
					}
					elseif (isset($arTask['GROUP_ID']))
					{
						$arNewRights = array_merge(
							$arNewRights,
							self::prepareRightsCodesForViewInGroupLiveFeed($logID, $arTask['GROUP_ID'])
						);
					}

					CSocNetLogRights::DeleteByLogID($arRes["ID"], true);
					CSocNetLogRights::Add($arRes["ID"], $arNewRights);
				}
			}
		}

		return ($logID);
	}


	public static function SendAddMessage($arFields, $arParams = array())
	{
		global $USER;

		$isBbCodeDescription = true;
		if (isset($arFields['DESCRIPTION_IN_BBCODE']) && ($arFields['DESCRIPTION_IN_BBCODE'] === 'N'))
			$isBbCodeDescription = false;

		if (isset($arFields['XML_ID']) && strlen($arFields['XML_ID']))
		{
			// Don't send any messages when created built-in tasks
			if (in_array($arFields['XML_ID'], self::$arBuiltInTasksXmlIds, true))
				return;
		}

		$spawnedByAgent = false;

		if (is_array($arParams))
		{
			if (
				isset($arParams['SPAWNED_BY_AGENT'])
				&& (
					($arParams['SPAWNED_BY_AGENT'] === 'Y')
					|| ($arParams['SPAWNED_BY_AGENT'] === true)
				)
			)
			{
				$spawnedByAgent = true;
			}
		}

		$arUsers = CTaskNotifications::__GetUsers($arFields);

		$bExcludeLoggedUser = true;
		if ($spawnedByAgent)
			$bExcludeLoggedUser = false;

		$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arFields, $bExcludeLoggedUser);

		$effectiveUserId = false;

		if ($spawnedByAgent)
		{
			if (isset($arFields['CREATED_BY']) && ($arFields['CREATED_BY'] > 0))
				$effectiveUserId = (int) $arFields['CREATED_BY'];
			else
				$effectiveUserId = 1;
		}
		elseif (is_object($USER) && $USER->GetID())
			$effectiveUserId = (int) $USER->GetID();
		elseif (isset($arFields['CREATED_BY']) && ($arFields['CREATED_BY'] > 0))
			$effectiveUserId = (int) $arFields['CREATED_BY'];

		if (sizeof($arRecipientsIDs) && ($effectiveUserId !== false))
		{
			$arRecipientsIDs = array_unique($arRecipientsIDs);

			$strResponsible = CTaskNotifications::__Users2String($arFields["RESPONSIBLE_ID"], $arUsers, $arFields["NAME_TEMPLATE"]);

			$invariantDescription = GetMessage("TASKS_MESSAGE_RESPONSIBLE_ID").': [COLOR=#000]'.$strResponsible."[/COLOR]\r\n";

			$plainDescription = HTMLToTxt($arFields["DESCRIPTION"]);
			if (strlen($plainDescription))
				$invariantDescription .= GetMessage("TASKS_MESSAGE_DESCRIPTION").": [COLOR=#000]" . $plainDescription . "[/COLOR]\r\n";

			if ($strAccomplices = CTaskNotifications::__Users2String($arFields["ACCOMPLICES"], $arUsers, $arFields["NAME_TEMPLATE"]))
				$invariantDescription .= GetMessage("TASKS_MESSAGE_ACCOMPLICES").": [COLOR=#000]".$strAccomplices."[/COLOR]\r\n";

			if ($strAuditors = CTaskNotifications::__Users2String($arFields["AUDITORS"], $arUsers, $arFields["NAME_TEMPLATE"]))
				$invariantDescription .= GetMessage("TASKS_MESSAGE_AUDITORS").": [COLOR=#000]".$strAuditors."[/COLOR]\r\n";

			// There is can be different messages for users (caused by differnent users' timezones)
			$arVolatileDescriptions = array();

			// Is there correct deadline (it cause volatile part of message for different timezones)? 
			if (
				$arFields["DEADLINE"]
				&& (MakeTimeStamp($arFields['DEADLINE']) > 0)
			)
			{
				// Get unix timestamp for DEADLINE
				$utsDeadline = MakeTimeStamp($arFields['DEADLINE']) - CTasksTools::getTimeZoneOffset();

				// Collect recipients' timezones
				foreach ($arRecipientsIDs as $userId)
				{
					$tzOffset = (int) CTasksTools::getTimeZoneOffset($userId);

					if ( ! isset($arVolatileDescriptions[$tzOffset]) )
					{
						// Make bitrix timestamp for given user
						$bitrixTsDeadline = $utsDeadline + $tzOffset;

						$deadlineAsString = FormatDate(
							'^' . CDatabase::DateFormatToPHP(FORMAT_DATETIME),	// "^" means that don't format time, if it's 00:00
							$bitrixTsDeadline
						);

						$arVolatileDescriptions[$tzOffset] = array(
							'recipients'  => array(),
							'description' => GetMessage('TASKS_MESSAGE_DEADLINE')
								. ': [COLOR=#000]' . $deadlineAsString . "[/COLOR]\r\n"
						);
					}

					$arVolatileDescriptions[$tzOffset]['recipients'][] = $userId;
				}
			}

			// If there is no volatile part of descriptions, send to all recipients at once
			if (empty($arVolatileDescriptions))
			{
				$arVolatileDescriptions[] = array(
					'recipients'  => $arRecipientsIDs,
					'description' => ''
				);
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = $effectiveUserId;

			foreach ($arVolatileDescriptions as $arData)
			{
				$volatileDescription = $arData['description'];
				$description = $invariantDescription . $volatileDescription;

				$message_instant = str_replace(
					array(
						"#TASK_TITLE#",
						"#TASK_EXTRA#"
					), 
					array(
						self::formatTaskName(
							$arFields['ID'],
							$arFields['TITLE'],
							$arFields['GROUP_ID'],
							true		// use [URL=#PATH_TO_TASK#']
						),
						$description
					), 
					GetMessage("TASKS_NEW_TASK_MESSAGE")
				);

				if ($isBbCodeDescription)
				{
					$parser = new CTextParser();
					$htmlDescription = str_replace(
						"\t",
						' &nbsp; &nbsp;',
						$parser->convertText($description)
					);

				}
				else
					$htmlDescription = $description;

				$message_email = str_replace(
					array(
						"#TASK_TITLE#",
						"#TASK_EXTRA#"
					), 
					array(
						strip_tags(self::formatTaskName($arFields['ID'], $arFields['TITLE'], $arFields['GROUP_ID'])),
						$htmlDescription . "\r\n" . GetMessage('TASKS_MESSAGE_LINK') . ': #PATH_TO_TASK#'
					), 
					GetMessage("TASKS_NEW_TASK_MESSAGE")
				);

				CTaskNotifications::SendMessage(
					$occurAsUserId, 
					$arData['recipients'], 
					$message_instant, 
					$arFields["ID"], 
					$message_email,
					array(
						'ACTION'   => 'TASK_ADD',
						'arFields' => $arFields
					),
					$arFields['RESPONSIBLE_ID']	// $taskAssignedTo
				);
			}
		}

		// sonet log, not for CRM
		if (
			!isset($arFields["UF_CRM_TASK"])
			|| (
				is_array($arFields["UF_CRM_TASK"])
				&& (
					!isset($arFields["UF_CRM_TASK"][0])
					|| strlen($arFields["UF_CRM_TASK"][0]) <= 0
				)
			)
			|| (
				!is_array($arFields["UF_CRM_TASK"])
				&& strlen($arFields["UF_CRM_TASK"]) <= 0
			)
		)
		{
			self::SendMessageToSocNet($arFields, $spawnedByAgent);
		}
	}

	// this is for making notifications work when using "ilike"
	// see CRatings::AddRatingVote() and CIMEvent::OnAddRatingVote() for the context of usage
	public static function OnGetRatingContentOwner($params)
	{
		if(intval($params['ENTITY_ID']) && $params['ENTITY_TYPE_ID'] == 'TASK')
		{
			list($oTaskItems, $rsData) = CTaskItem::fetchList(CTasksTools::GetCommanderInChief(), array(), array('=ID' => $params['ENTITY_ID']), array(), array('ID', 'CREATED_BY'));
			unset($rsData);

			if($oTaskItems[0] instanceof CTaskItem)
			{
				$data = $oTaskItems[0]->getData(false);
				if(intval($data['CREATED_BY']))
					return intval($data['CREATED_BY']);
			}
		}

		return 0;
	}

	// this is for replacing the default message when user presses "ilike" button
	// see CIMEvent::GetMessageRatingVote() for the context of usage
	public static function OnGetMessageRatingVote(&$params, &$forEmail)
	{
		if($params['ENTITY_TYPE_ID'] == 'TASK' && !$forEmail)
		{
			$langMessage = GetMessage('TASKS_NOTIFICATIONS_I_'.($params['VALUE'] >= 0 ? '' : 'DIS').'LIKE_TASK');
			if((string) $langMessage != '')
			{
				$params['MESSAGE'] = str_replace(
					'#LINK#', 
					(string) $params['ENTITY_LINK'] != '' ? '<a href="'.$params['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$params["ENTITY_TITLE"].'</a>': '<i>'.$params["ENTITY_TITLE"].'</i>', $langMessage);
			}
		}
	}

	private static function formatTimeHHMM($in, $bDataInSeconds = false)
	{
		if ($in === NULL)
			return '';

		if ($bDataInSeconds)
			$minutes = (int) round($in / 60, 0);

		$hours = (int) ($minutes / 60);

		if ($minutes < 60)
		{
			$duration = $minutes . ' ' . CTasksTools::getMessagePlural(
					$minutes,
					'TASKS_TASK_DURATION_MINUTES'
				);
		}
		elseif ($minutesInResid = $minutes % 60)
		{
			$duration = $hours
				. ' '
				. CTasksTools::getMessagePlural(
					$hours,
					'TASKS_TASK_DURATION_HOURS'
				)
				. ' '
				. (int) $minutesInResid
				. ' '
				. CTasksTools::getMessagePlural(
					(int) $minutesInResid,
					'TASKS_TASK_DURATION_MINUTES'
				);
		}
		else
		{
			$duration = $hours . ' ' . CTasksTools::getMessagePlural(
					$hours,
					'TASKS_TASK_DURATION_HOURS'
				);
		}

		if ($bDataInSeconds && ($in < 3600))
		{
			if ($secondsInResid = $in % 60)
			{
				$duration .= ' ' . (int) $secondsInResid
					. ' '
					. CTasksTools::getMessagePlural(
						(int) $secondsInResid,
						'TASKS_TASK_DURATION_SECONDS'
					);
			}
		}

		return ($duration);
	}


	public static function SendUpdateMessage($arFields, $arTask, $bSpawnedByAgent = false)
	{
		global $USER;

		$isBbCodeDescription = true;
		if (isset($arFields['DESCRIPTION_IN_BBCODE']))
		{
			if ($arFields['DESCRIPTION_IN_BBCODE'] === 'N')
				$isBbCodeDescription = false;
		}
		elseif (isset($arTask['DESCRIPTION_IN_BBCODE']))
		{
			if ($arTask['DESCRIPTION_IN_BBCODE'] === 'N')
				$isBbCodeDescription = false;
		}

		$taskReassignedTo = null;

		if (
			isset($arFields['RESPONSIBLE_ID'])
			&& ($arFields['RESPONSIBLE_ID'] > 0)
			&& ($arFields['RESPONSIBLE_ID'] != $arTask['RESPONSIBLE_ID'])
		)
		{
			$taskReassignedTo = $arFields['RESPONSIBLE_ID'];
		}

		foreach (array('CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS', 'TITLE') as $field)
		{
			if ( ! isset($arFields[$field])
				&& isset($arTask[$field])
			)
			{
				$arFields[$field] = $arTask[$field];
			}
		}

		$arChanges = CTaskLog::GetChanges($arTask, $arFields);

		$arMerged = array(
			'ADDITIONAL_RECIPIENTS' => array()
		);

		// Pack prev users ids to ADDITIONAL_RECIPIENTS, to ensure, 
		// that they all will receive message
		{
			if (isset($arTask['CREATED_BY']))
				$arMerged['ADDITIONAL_RECIPIENTS'][] = $arTask['CREATED_BY'];

			if (isset($arTask['RESPONSIBLE_ID']))
				$arMerged['ADDITIONAL_RECIPIENTS'][] = $arTask['RESPONSIBLE_ID'];

			if (isset($arTask['ACCOMPLICES']) && is_array($arTask['ACCOMPLICES']))
				foreach ($arTask['ACCOMPLICES'] as $userId)
					$arMerged['ADDITIONAL_RECIPIENTS'][] = $userId;

			if (isset($arTask['AUDITORS']) && is_array($arTask['AUDITORS']))
				foreach ($arTask['AUDITORS'] as $userId)
					$arMerged['ADDITIONAL_RECIPIENTS'][] = $userId;
		}

		if (isset($arFields['ADDITIONAL_RECIPIENTS']))
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = array_merge (
				$arFields['ADDITIONAL_RECIPIENTS'],
				$arMerged['ADDITIONAL_RECIPIENTS']
				);
		}
		else
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = $arMerged['ADDITIONAL_RECIPIENTS'];
		}

		$arUsers = CTaskNotifications::__GetUsers($arFields);

		$arRecipientsIDs = array_unique(CTaskNotifications::GetRecipientsIDs($arFields));

		if (
			( ! empty($arRecipientsIDs) )
			&& ((is_object($USER) && $USER->GetID()) || $arFields["CREATED_BY"])
		)
		{
			$curUserTzOffset = (int) CTasksTools::getTimeZoneOffset();
			$arInvariantChangesStrs = array();
			$arVolatileDescriptions = array();
			$arRecipientsIDsByTimezone = array();
			$i = 0;
			foreach ($arChanges as $key => $value)
			{
				++$i;
				$actionMessage = GetMessage("TASKS_MESSAGE_".$key);
				if (strlen($actionMessage))
				{
					$tmpStr = $actionMessage.": [COLOR=#000]";
					switch ($key)
					{
						case 'TIME_ESTIMATE':
							$tmpStr .= self::formatTimeHHMM($value["FROM_VALUE"], true)
								. " -> "
								. self::formatTimeHHMM($value["TO_VALUE"], true);
						break;

						case "TITLE":
							$tmpStr .= $value["FROM_VALUE"]." -> ".$value["TO_VALUE"];
							break;

						case "RESPONSIBLE_ID":
							$tmpStr .= 
								CTaskNotifications::__Users2String($value["FROM_VALUE"], $arUsers, $arFields["NAME_TEMPLATE"])
								. ' -> '
								. CTaskNotifications::__Users2String($value["TO_VALUE"], $arUsers, $arFields["NAME_TEMPLATE"]);
							break;

						case "ACCOMPLICES":
						case "AUDITORS":
							$tmpStr .= 
								CTaskNotifications::__Users2String(explode(",", $value["FROM_VALUE"]), $arUsers, $arFields["NAME_TEMPLATE"])
								. ' -> '
								. CTaskNotifications::__Users2String(explode(",", $value["TO_VALUE"]), $arUsers, $arFields["NAME_TEMPLATE"])
								;
							break;

						case "DEADLINE":
						case "START_DATE_PLAN":
						case "END_DATE_PLAN":
							// CTasks::Log() returns bitrix timestamps for dates, so adjust them to correct unix timestamps.
							$utsFromValue = $value['FROM_VALUE'] - $curUserTzOffset;
							$utsToValue   = $value['TO_VALUE'] - $curUserTzOffset;

							// It will be replaced below to formatted string with correct dates for different timezones
							$placeholder = '###PLACEHOLDER###' . $i . '###';
							$tmpStr .= $placeholder;

							// Collect recipients' timezones
							foreach ($arRecipientsIDs as $userId)
							{
								$tzOffset = (int) CTasksTools::getTimeZoneOffset($userId);

								if ( ! isset($arVolatileDescriptions[$tzOffset]) )
									$arVolatileDescriptions[$tzOffset] = array();

								if ( ! isset($arVolatileDescriptions[$tzOffset][$placeholder]) )
								{
									// Make bitrix timestamps for given user
									$bitrixTsFromValue = $utsFromValue + $tzOffset;
									$bitrixTsToValue   = $utsToValue + $tzOffset;

									$description = '';

									if ($utsFromValue > 360000)		// is correct timestamp?
									{
										$fromValueAsString = FormatDate(
											'^' . CDatabase::DateFormatToPHP(FORMAT_DATETIME),	// "^" means that don't format time, if it's 00:00
											$bitrixTsFromValue
										);

										$description .= $fromValueAsString;
									}

									$description .= ' --> ';

									if ($utsToValue > 360000)		// is correct timestamp?
									{
										$toValueAsString = FormatDate(
											'^' . CDatabase::DateFormatToPHP(FORMAT_DATETIME),	// "^" means that don't format time, if it's 00:00
											$bitrixTsToValue
										);

										$description .= $toValueAsString;
									}

									$arVolatileDescriptions[$tzOffset][$placeholder] = $description;
								}

								$arRecipientsIDsByTimezone[$tzOffset][] = $userId;
							}
							break;

						case "DESCRIPTION":
							$tmpStr .= HTMLToTxt($arFields["DESCRIPTION"]);
							break;

						case "TAGS":
							$tmpStr .= ($value["FROM_VALUE"] ? str_replace(",", ", ", $value["FROM_VALUE"])." -> " : "").($value["TO_VALUE"] ? str_replace(",", ", ", $value["TO_VALUE"]) : GetMessage("TASKS_MESSAGE_NO_VALUE"));
							break;

						case "PRIORITY":
							$tmpStr .= GetMessage("TASKS_PRIORITY_".$value["FROM_VALUE"])." -> ".GetMessage("TASKS_PRIORITY_".$value["TO_VALUE"]);
							break;

						case "GROUP_ID":
							if ($value["FROM_VALUE"] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $value["FROM_VALUE"]))
							{
								$arGroupFrom = CSocNetGroup::GetByID($value["FROM_VALUE"]);
								{
									if ($arGroupFrom)
									{
										$tmpStr .= $arGroupFrom["NAME"]." -> ";
									}
								}
							}
							if ($value["TO_VALUE"] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $value["TO_VALUE"]))
							{
								$arGroupTo = CSocNetGroup::GetByID($value["TO_VALUE"]);
								{
									if ($arGroupTo)
									{
										$tmpStr .= $arGroupTo["NAME"];
									}
								}
							}
							else
							{
								$tmpStr .= GetMessage("TASKS_MESSAGE_NO_VALUE");
							}
							break;

						case "PARENT_ID":
							if ($value["FROM_VALUE"])
							{
								$rsTaskFrom = CTasks::GetList(array(), array("ID" => $value["FROM_VALUE"]), array('ID', 'TITLE'));
								{
									if ($arTaskFrom = $rsTaskFrom->GetNext())
									{
										$tmpStr .= $arTaskFrom["TITLE"]." -> ";
									}
								}
							}
							if ($value["TO_VALUE"])
							{
								$rsTaskTo = CTasks::GetList(array(), array("ID" => $value["TO_VALUE"]), array('ID', 'TITLE'));
								{
									if ($arTaskTo = $rsTaskTo->GetNext())
									{
										$tmpStr .= $arTaskTo["TITLE"];
									}
								}
							}
							else
							{
								$tmpStr .= GetMessage("TASKS_MESSAGE_NO_VALUE");
							}
							break;

						case "DEPENDS_ON":
							$arTasksFromStr = array();
							if ($value["FROM_VALUE"])
							{
								$rsTasksFrom = CTasks::GetList(array(), array("ID" => explode(",", $value["FROM_VALUE"])), array('ID', 'TITLE'));
								while ($arTaskFrom = $rsTasksFrom->GetNext())
								{
									$arTasksFromStr[] = $arTaskFrom["TITLE"];
								}
							}
							$arTasksToStr = array();
							if ($value["TO_VALUE"])
							{
								$rsTasksTo = CTasks::GetList(array(), array("ID" => explode(",", $value["TO_VALUE"])), array('ID', 'TITLE'));
								while ($arTaskTo = $rsTasksTo->GetNext())
								{
									$arTasksToStr[] = $arTaskTo["TITLE"];
								}
							}
							$tmpStr .= ($arTasksFromStr ? implode(", ", $arTasksFromStr)." -> " : "").($arTasksToStr ? implode(", ", $arTasksToStr) : GetMessage("TASKS_MESSAGE_NO_VALUE"));
							break;

						case "MARK":
							$tmpStr .= (!$value["FROM_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$value["FROM_VALUE"]))." -> ".(!$value["TO_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$value["TO_VALUE"]));
							break;

						case "ADD_IN_REPORT":
							$tmpStr .= ($value["FROM_VALUE"] == "Y" ? GetMessage("TASKS_MESSAGE_IN_REPORT_YES") : GetMessage("TASKS_MESSAGE_IN_REPORT_NO"))." -> ".($value["TO_VALUE"] == "Y" ? GetMessage("TASKS_MESSAGE_IN_REPORT_YES") : GetMessage("TASKS_MESSAGE_IN_REPORT_NO"));
							break;

						case "DELETED_FILES":
							$tmpStr .= $value["FROM_VALUE"];
							$tmpStr .= $value["TO_VALUE"];
							break;

						case "NEW_FILES":
							$tmpStr .= $value["TO_VALUE"];
							break;
					}
					$tmpStr .= "[/COLOR]";

					$arInvariantChangesStrs[] = $tmpStr;
				}
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = is_object($USER) && $USER->GetID() ? $USER->GetID() : $arFields["CREATED_BY"];

			$invariantDescription = null;

			if ( ! empty($arInvariantChangesStrs) )
				$invariantDescription = implode("\r\n", $arInvariantChangesStrs);

			if (
				($invariantDescription !== null)
				&& ( ! empty($arRecipientsIDs) )
			)
			{
				// If there is no volatile part of descriptions, send to all recipients at once
				if (empty($arVolatileDescriptions))
				{
					$arVolatileDescriptions['some_timezone'] = array();
					$arRecipientsIDsByTimezone['some_timezone']  = $arRecipientsIDs;
				}

				foreach ($arVolatileDescriptions as $tzOffset => $arVolatileDescriptionsData)
				{
					$strDescription = $invariantDescription;

					foreach ($arVolatileDescriptionsData as $placeholder => $strReplaceTo)
						$strDescription = str_replace($placeholder, $strReplaceTo, $strDescription);

					$message = str_replace(
						array(
							"#TASK_TITLE#",
							"#TASK_EXTRA#"
						), 
						array(
							self::formatTaskName(
								$arTask['ID'],
								$arTask['TITLE'],
								$arTask['GROUP_ID'],
								true		// use [URL=#PATH_TO_TASK#']
							),
							$strDescription
						), 
						GetMessage("TASKS_TASK_CHANGED_MESSAGE")
					);

					if ($isBbCodeDescription)
					{
						$parser = new CTextParser();
						$htmlDescription = str_replace(
							"\t",
							' &nbsp; &nbsp;',
							$parser->convertText($strDescription)
						);

					}
					else
						$htmlDescription = $strDescription;

					$message_email = str_replace(
						array(
							"#TASK_TITLE#",
							"#TASK_EXTRA#"
						), 
						array(
							self::formatTaskName(
								$arTask['ID'],
								$arTask['TITLE'],
								$arTask['GROUP_ID']
							),
							$htmlDescription."\r\n".GetMessage('TASKS_MESSAGE_LINK').': #PATH_TO_TASK#'
						), 
						GetMessage("TASKS_TASK_CHANGED_MESSAGE")
					);

					CTaskNotifications::SendMessage(
						$occurAsUserId,
						$arRecipientsIDsByTimezone[$tzOffset], 
						$message,
						$arTask["ID"],
						$message_email,
						array(
							'ACTION'    => 'TASK_UPDATE',
							'arFields'  => $arFields,
							'arChanges' => $arChanges
						),
						$taskReassignedTo
					);
				}
			}
		}

		// sonet log
		self::SendMessageToSocNet($arFields, $bSpawnedByAgent, $arChanges, $arTask);
	}


	function SendDeleteMessage($arFields)
	{
		global $USER;

		$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arFields);
		if (sizeof($arRecipientsIDs) && ((is_object($USER) && $USER->GetID()) || $arFields["CREATED_BY"]))
		{
			$message = str_replace(
				"#TASK_TITLE#",
				self::formatTaskName(
					$arFields['ID'],
					$arFields['TITLE'],
					$arFields['GROUP_ID']
				),
				GetMessage("TASKS_TASK_DELETED_MESSAGE")
			);

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = is_object($USER) && $USER->GetID() ? $USER->GetID() : $arFields["CREATED_BY"];

			CTaskNotifications::SendMessage($occurAsUserId, $arRecipientsIDs, 
				$message, 0, null,
				array(
					'ACTION'   => 'TASK_DELETE',
					'arFields' => $arFields
				)
			);
		}

		// sonet log
		if (CModule::IncludeModule("socialnetwork"))
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $arFields["ID"]
				),
				false,
				false,
				array("ID")
			);
			while ($arRes = $dbRes->Fetch())
				CSocNetLog::Delete($arRes["ID"]);
		}
	}


	function SendStatusMessage($arTask, $status, $arFields = array())
	{
		global $USER, $DB;

		$status = intval($status);
		if ($status > 0 && $status < 8)
		{
			$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs(array_merge($arTask, $arFields));
			if (sizeof($arRecipientsIDs) && ((is_object($USER) && $USER->GetID()) || $arTask["CREATED_BY"]))
			{
				// If task was redoed
				if (
					(
						($status == CTasks::STATE_NEW)
						|| ($status == CTasks::STATE_PENDING)
					)
					&& ($arTask['REAL_STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
				)
				{
					$message = str_replace(
						"#TASK_TITLE#",
						self::formatTaskName(
							$arTask['ID'], 
							$arTask['TITLE'], 
							$arTask['GROUP_ID'],
							true		// use [URL=#PATH_TO_TASK#']
						),
						GetMessage("TASKS_TASK_STATUS_MESSAGE_REDOED")
					);
					$message_email = str_replace(
						"#TASK_TITLE#",
						self::formatTaskName(
							$arTask['ID'], 
							$arTask['TITLE'], 
							$arTask['GROUP_ID']
						),
						GetMessage("TASKS_TASK_STATUS_MESSAGE_REDOED")."\r\n".GetMessage('TASKS_MESSAGE_LINK').': #PATH_TO_TASK#'
					);
				}
				else
				{
					$message = str_replace(
						"#TASK_TITLE#",
						self::formatTaskName(
							$arTask['ID'], 
							$arTask['TITLE'], 
							$arTask['GROUP_ID'],
							true		// use [URL=#PATH_TO_TASK#']
						),
						GetMessage("TASKS_TASK_STATUS_MESSAGE_".$status)
					);
					$message_email = str_replace(
						"#TASK_TITLE#",
						self::formatTaskName(
							$arTask['ID'], 
							$arTask['TITLE'], 
							$arTask['GROUP_ID']
						),
						GetMessage("TASKS_TASK_STATUS_MESSAGE_".$status)."\r\n".GetMessage('TASKS_MESSAGE_LINK').': #PATH_TO_TASK#'
					);

					if ($status == CTasks::STATE_DECLINED)
					{
						$message = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);
						$message_email = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message_email);
					}
				}

				$occurAsUserId = CTasksTools::getOccurAsUserId();
				if ( ! $occurAsUserId )
					$occurAsUserId = is_object($USER) && $USER->GetID() ? $USER->GetID() : $arTask["CREATED_BY"];

				CTaskNotifications::SendMessage($occurAsUserId, $arRecipientsIDs, 
					$message, $arTask["ID"], $message_email,
					array(
						'ACTION'   => 'TASK_STATUS_CHANGED_MESSAGE',
						'arTask'   => $arTask,
						'arFields' => $arFields
					)
				);
			}
		}

		// sonet log
		if (CModule::IncludeModule("socialnetwork"))
		{
			if ($status == CTasks::STATE_PENDING)
				$message = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . CTasks::STATE_NEW);
			else
				$message = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . $status);

			if ($status == CTasks::STATE_DECLINED)
				$message = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);

			$arSoFields = array(
				"TITLE" => $arTask["TITLE"],
				"=LOG_UPDATE" => (
					strlen($arTask["CHANGED_DATE"]) > 0?
						(MakeTimeStamp($arTask["CHANGED_DATE"], CSite::GetDateFormat("FULL", SITE_ID)) > time()+CTimeZone::GetOffset()?
							$DB->CharToDateFunction($arTask["CHANGED_DATE"], "FULL", SITE_ID) :
							$DB->CurrentTimeFunction()) :
						$DB->CurrentTimeFunction()
				),
				"MESSAGE" => "",
				"TEXT_MESSAGE" => $message,
				"PARAMS" => serialize(
					array(
						"TYPE" => "status",
						'CHANGED_BY' => $arFields['CHANGED_BY'],
						'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
					)
				)
			);

			$arSoFields['=LOG_DATE'] = $arSoFields['=LOG_UPDATE'];

			// All tasks posts in live feed should be from director
			if (isset($arFields['CREATED_BY']))
				$arSoFields["USER_ID"] = $arFields['CREATED_BY'];

			$loggedInUserId = false;
			if (is_object($USER) && method_exists($USER, 'getId'))
				$loggedInUserId = (int) $USER->getId();

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $arTask["ID"]
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID")
			);

			while ($arRes = $dbRes->Fetch())
			{
				CSocNetLog::Update($arRes['ID'], $arSoFields);

				$authorUserId = (int) $arTask['CREATED_BY'];

				// Add author to list of users that view log about task in livefeed
				// But only when some other person change task
				if ($authorUserId !== $loggedInUserId)
				{
					$authorGroupCode = 'U' . $authorUserId;

					$rsRights = CSocNetLogRights::GetList(
						array(),
						array(
							'LOG_ID'     => $arRes['ID'],
							'GROUP_CODE' => $authorGroupCode
						)
					);

					// If task's author hasn't rights yet, give them
					if ( ! ($arRights = $rsRights->fetch()) )
						CSocNetLogRights::Add($arRes["ID"], array($authorGroupCode));
				}
			}
		}
	}


	function GetRecipientsIDs($arFields, $bExcludeCurrent = true, $bExcludeAdditionalRecipients = false)
	{
		global $USER;

		if ($bExcludeAdditionalRecipients)
			$arFields['ADDITIONAL_RECIPIENTS'] = array();

		if ( ! isset($arFields['ADDITIONAL_RECIPIENTS']) )
			$arFields['ADDITIONAL_RECIPIENTS'] = array();

		$arRecipientsIDs = array_unique(
			array_filter(
				array_merge(
					array($arFields["CREATED_BY"], $arFields["RESPONSIBLE_ID"]), 
					(array) $arFields["ACCOMPLICES"], 
					(array) $arFields["AUDITORS"],
					(array) $arFields['ADDITIONAL_RECIPIENTS']
					)));

		if ($bExcludeCurrent && is_object($USER) && ($currentUserID = $USER->GetID()))
		{
			$currentUserPos = array_search($currentUserID, $arRecipientsIDs);
			if ($currentUserPos !== false)
			{
				unset($arRecipientsIDs[$currentUserPos]);
			}
		}

		return $arRecipientsIDs;
	}


	public static function GetNotificationPath($arUser, $taskID, $bUseServerName = true, $arSites = array())
	{
		$bExtranet = false;
		$siteID = false;
		$effectiveSiteId = (string) SITE_ID;
		$rsTask = CTasks::GetByID($taskID, false);
		if ($arTask = $rsTask->Fetch())
		{
			if (CModule::IncludeModule('extranet') 
				&& ( ! CTaskNotifications::__isIntranetUser($arUser["ID"]) )
			)
			{
				$bExtranet = true;
				$siteID = (string) CExtranet::GetExtranetSiteID();
			}

			if ($siteID)
				$effectiveSiteId = (string) $siteID;
			elseif (isset($arSites['INTRANET']['SITE_ID']))
				$effectiveSiteId = (string) $arSites['INTRANET']['SITE_ID'];

			if ( ! is_string($siteID) )
				$siteID = (string) SITE_ID;

			if ($arTask['GROUP_ID'] 
				&& CTasksTools::HasUserReadAccessToGroup(
					$arUser['ID'],
					$arTask['GROUP_ID']
				)
			)
			{
				$pathTemplate = str_replace(
					"#group_id#", 
					$arTask["GROUP_ID"], 
					CTasksTools::GetOptionPathTaskGroupEntry(
						$effectiveSiteId,
						"/workgroups/group/#group_id#/tasks/task/view/#task_id#/"
					)
				);
				$pathTemplate = str_replace(
					"#GROUP_ID#", 
					$arTask["GROUP_ID"], 
					$pathTemplate
				);
			}
			else
			{
				$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry(
					$siteID,
					"/company/personal/user/#user_id#/tasks/task/view/#task_id#/"					
				);
			}

			$server_name_tmp = false;
			if ($arTask["GROUP_ID"] && count($arSites) > 0)
				$server_name_tmp = $arSites[($bExtranet ? "EXTRANET" : "INTRANET")]["SERVER_NAME"];

			$strUrl = ($bUseServerName ? tasksServerName($server_name_tmp) : "")
				. CComponentEngine::MakePathFromTemplate(
					$pathTemplate, 
					array(
						'user_id' => $arUser['ID'], 
						'USER_ID' => $arUser['ID'], 
						'task_id' => $taskID, 
						'TASK_ID' => $taskID, 
						'action'  => 'view'
					)
				);

			return ($strUrl);
		}

		return false;
	}


	private function __isIntranetUser($userID)
	{
		return (CTasksTools::IsIntranetUser($userID));
	}


	private function __GetUsers($arFields)
	{
		static $arParams = array(
			'FIELDS' => array(
				'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'ID'
			)
		);

		$arUsersIDs = array_unique(
			array_filter(
				array_merge(
					array(
						$arFields["CREATED_BY"], 
						$arFields["RESPONSIBLE_ID"]
					), 
					(array) $arFields["ACCOMPLICES"], 
					(array) $arFields["AUDITORS"], 
					(array) $arFields['ADDITIONAL_RECIPIENTS']
				)
			)
		);

		$rsUsers = CUser::GetList(
			$by = 'id', 
			$order = 'asc', 
			array("ID" => implode("|", $arUsersIDs)),
			$arParams
		);

		$arUsers = array();

		while ($user = $rsUsers->Fetch())
			$arUsers[$user["ID"]] = $user;

		return $arUsers;
	}


	private function __Users2String($arUserIDs, $arUsers, $nameTemplate = "")
	{
		$arUsersStrs = array();
		if (!is_array($arUserIDs))
			$arUserIDs = array($arUserIDs);

		$arUserIDs = array_unique(array_filter($arUserIDs));
		foreach ($arUserIDs as $userID)
		{
			if ($user = $arUsers[$userID])
				$arUsersStrs[] = CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat(false) : $nameTemplate, $arUsers[$userID]);
		}

		return implode(", ", $arUsersStrs);
	}


	function __UserIDs2Rights($arUserIDs)
	{
		$arUserIDs = array_unique(array_filter($arUserIDs));
		$arRights = array();
		foreach($arUserIDs as $userID)
			$arRights[] = "U".$userID;

		return $arRights;
	}


	function __Fields2Names($arFields)
	{
		$arFields = array_unique(array_filter($arFields));
		$arNames = array();
		foreach($arFields as $field)
		{
			if ($field == "NEW_FILES" || $field == "DELETED_FILES")
				$field = "FILES";
			$arNames[] = GetMessage("TASKS_SONET_LOG_".$field);
		}

		return array_unique(array_filter($arNames));
	}


	public static function FormatTask4Log($arTask, $message = '', $message_24_1 = '', $message_24_2 = '', $changes_24 = '', $nameTemplate = '')
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.task.livefeed', 
			'', 
			array(
				'TASK' => $arTask,
				'MESSAGE' => $message,
				'MESSAGE_24_1' => $message_24_1,
				'MESSAGE_24_2' => $message_24_2,
				'CHANGES_24' => $changes_24,
				'NAME_TEMPLATE'	=> $nameTemplate
			), 
			null, 
			array('HIDE_ICONS' => 'Y')
		);
		$html = ob_get_clean();

		return $html;
	}


	public static function FormatTask4SocialNetwork($arFields, $arParams, $bMail = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$task_datetime = null;

		if ( ! CModule::IncludeModule('socialnetwork') )
			return (false);

		$APPLICATION->SetAdditionalCSS('/bitrix/js/tasks/css/tasks.css');

		if (isset($arFields['~PARAMS']) && $arFields['~PARAMS'])
			$arFields['PARAMS'] = unserialize($arFields['~PARAMS']);
		elseif (isset($arFields['PARAMS']) && $arFields['PARAMS'])
			$arFields['PARAMS'] = unserialize($arFields['PARAMS']);
		else
			$arFields['PARAMS'] = array();

		$arResult = array(
			'EVENT'           => $arFields,
			'CREATED_BY'      => CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail),
			'ENTITY'          => CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, $bMail),
			'EVENT_FORMATTED' => array(),
			'CACHED_CSS_PATH' => '/bitrix/js/tasks/css/tasks.css'
		);

		if (!$bMail)
			$arResult["AVATAR_SRC"] = CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		if (
			!$bMail
			&& $arParams["MOBILE"] != "Y"
			&& array_key_exists("URL", $arFields)
			&& strlen($arFields["URL"]) > 0
		)
			$taskHtmlTitle = '<a href="'.$arFields["URL"].'" onclick="if (taskIFramePopup.isLeftClick(event)) {taskIFramePopup.view('.$arFields["SOURCE_ID"].'); return false;}">'.$arFields["TITLE"].'</a>';
		else
			$taskHtmlTitle = $arFields["TITLE"];


		// Prepare event title (depends on action and gender of actor)
		{
			$actorUserId = null;
			$actorUserName = '';
			$actorMaleSuffix = '';
			$eventTitlePhraseSuffix = '_DEFAULT';

			if (isset($arParams['NAME_TEMPLATE']))
				$nameTemplate = $arParams['NAME_TEMPLATE'];
			else
				$nameTemplate = CSite::GetNameFormat();

			if (isset($arFields["PARAMS"], $arFields['PARAMS']['TYPE']))
			{
				if ($arFields["PARAMS"]["TYPE"] === "create")
				{
					$eventTitlePhraseSuffix = '_CREATE_24';
					if (isset($arFields["PARAMS"]["CREATED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CREATED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'modify')
				{
					$eventTitlePhraseSuffix = '_MODIFY_24';
					if (isset($arFields["PARAMS"]["CHANGED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CHANGED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'status')
				{
					$eventTitlePhraseSuffix = '_STATUS_24';
					if (isset($arFields["PARAMS"]["CHANGED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CHANGED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'comment')
				{
					$eventTitlePhraseSuffix = '';
				}
			}

			if ($actorUserId)
			{
				$rsUser = CUser::GetList(
					$by = 'id',
					$order = 'asc',
					array('ID_EQUAL_EXACT' => (int) $actorUserId),
					array(
						'FIELDS' => array(
							'ID',
							'NAME',
							'LAST_NAME',
							'SECOND_NAME',
							'LOGIN',
							'PERSONAL_GENDER'
						)
					)
				);

				if ($arUser = $rsUser->fetch())
				{
					if (isset($arUser['PERSONAL_GENDER']))
					{
						switch ($arUser['PERSONAL_GENDER'])
						{
							case "F":
							case "M":
								$actorMaleSuffix = '_' . $arUser['PERSONAL_GENDER'];
							break;
						}
					}

					$actorUserName = CUser::FormatName($nameTemplate, $arUser);
				}				
			}

			$eventTitleTemplate = GetMessage('TASKS_SONET_GL_EVENT_TITLE_TASK'
				. $eventTitlePhraseSuffix . $actorMaleSuffix);

			$eventTitle = str_replace(
				array('#USER_NAME#', '#TITLE#'),
				array($actorUserName, $taskHtmlTitle),
				$eventTitleTemplate
			);
			$eventTitleWoTaskName = str_replace(
				array('#USER_NAME#', '#TITLE#'),
				array($actorUserName, ''),
				$eventTitleTemplate
			);
		}

		$title_tmp = str_replace(
			"#TITLE#",
			$taskHtmlTitle,
			GetMessage("TASKS_SONET_GL_EVENT_TITLE_TASK")
		);

		if($arFields["PARAMS"] && $arFields["PARAMS"]["CREATED_BY"])
		{
			$suffix = (
				is_array($GLOBALS["arExtranetUserID"]) 
				&& in_array($arFields["PARAMS"]["CREATED_BY"], $GLOBALS["arExtranetUserID"]) ? GetMessage("TASKS_SONET_LOG_EXTRANET_SUFFIX") : "");

			$rsUser = CUser::GetList(
				$by = 'id',
				$order = 'asc',
				array('ID_EQUAL_EXACT' => (int) $arFields['PARAMS']['CREATED_BY']),
				array(
					'FIELDS' => array(
						'PERSONAL_GENDER',
						'ID',
						'NAME',
						'LAST_NAME',
						'SECOND_NAME',
						'LOGIN'
					)
				)
			);

			if ($arUser = $rsUser->Fetch())
			{
				$title_tmp .= " (" 
					. str_replace(
						"#USER_NAME#", 
						CUser::FormatName(CSite::GetNameFormat(false), $arUser) . $suffix,
						GetMessage("TASKS_SONET_GL_EVENT_TITLE_TASK_CREATED")
						)
					. ")";
			}
		}

		if ($bMail)
		{
			$title = str_replace(
				array("#TASK#", "#ENTITY#", "#CREATED_BY#"),
				array($title_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
				GetMessage(
					"SONET_GL_EVENT_TITLE_" .
					($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER") 
					. "_TASK_MAIL"
					)
			);
		}
		else
		{
			$title = $title_tmp;
			$title_24 = $eventTitle;
		}

		if (
			!$bMail 
			&& (
				in_array(
					$arFields["PARAMS"]["TYPE"],
					array("create", "status", 'modify', 'comment'),
					true
				)
			)
		)
		{
			if ( ! (
				isset($arFields['PARAMS']['CHANGED_FIELDS']) 
				&& is_array($arFields['PARAMS']['CHANGED_FIELDS']) 
			))
			{
				$arFields['PARAMS']['CHANGED_FIELDS'] = array();
			}

			$rsTask = CTasks::GetByID($arFields["SOURCE_ID"], false);
			if ($arTask = $rsTask->Fetch())
			{
				$task_datetime = $arTask["CHANGED_DATE"];
				if ($arFields["PARAMS"]["TYPE"] == "create")
				{
					if ($arParams["MOBILE"] == "Y")
					{
						$title_24     = GetMessage("TASKS_SONET_GL_TASKS2_NEW_TASK_MESSAGE");
						$message_24_1 = $taskHtmlTitle;
					}
					else
					{
						$message      = $message_24_1 = $eventTitle;
						$message_24_2 = $changes_24 = "";
					}
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "modify")
				{
					$arChangesFields = $arFields["PARAMS"]["CHANGED_FIELDS"];
					$changes_24 = implode(", ", CTaskNotifications::__Fields2Names($arChangesFields));

					if ($arParams["MOBILE"] == "Y")
					{
						$title_24     = GetMessage("TASKS_SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_1");
						$message_24_1 = $taskHtmlTitle;
					}
					else
					{
						$message = str_replace(
							"#CHANGES#", 
							implode(", ", CTaskNotifications::__Fields2Names($arChangesFields)), 
							GetMessage("TASKS_SONET_GL_TASKS2_TASK_CHANGED_MESSAGE")
						);
						$message_24_1 = $eventTitle;
						$message_24_2 = GetMessage("TASKS_SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_2");
					}				
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "status")
				{
					$message = GetMessage("TASKS_SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]);

					$message_24_1 = $eventTitle;

					if ($arTask["STATUS"] == CTasks::STATE_DECLINED)
					{
						$message      = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);
						$message_24_2 = GetMessage("TASKS_SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]."_24_2");
						$changes_24   = $arTask["DECLINE_REASON"];
					}
					else
						$message_24_2 = $changes_24 = $message;
				}
				elseif ($arFields['PARAMS']['TYPE'] === 'comment')
				{
					$message_24_1 = $eventTitle;
					$message_24_2 = $changes_24 = $message = '';
				}

				$prevRealStatus = false;

				if (isset($arFields['PARAMS']['PREV_REAL_STATUS']))
					$prevRealStatus = $arFields['PARAMS']['PREV_REAL_STATUS'];

				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					"bitrix:tasks.task.livefeed", 
					($arParams["MOBILE"] == "Y" ? 'mobile' : ''), 
					array(
						"MOBILE"        => ($arParams["MOBILE"] == "Y" ? "Y" : "N"),
						"TASK"          => $arTask,
						"MESSAGE"       => $message,
						"MESSAGE_24_1"  => $message_24_1,
						"MESSAGE_24_2"  => $message_24_2,
						"CHANGES_24"    => $changes_24,
						"NAME_TEMPLATE"	=> $arParams["NAME_TEMPLATE"],
						"PATH_TO_USER"	=> $arParams["PATH_TO_USER"],
						'TYPE'          => $arFields["PARAMS"]["TYPE"],
						'task_tmp'      => $taskHtmlTitle,
						'taskHtmlTitle' => $taskHtmlTitle,
						'PREV_REAL_STATUS' => $prevRealStatus
					), 
					null, 
					array("HIDE_ICONS" => "Y")
				);
				$arFields["MESSAGE"] = ob_get_contents();
				ob_end_clean();
			}
		}

		if ($arParams["MOBILE"] == "Y")
		{
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE"             => '',
				"TITLE_24"          => $eventTitleWoTaskName,
				"MESSAGE"           => htmlspecialcharsbx($arFields['MESSAGE']),
				"DESCRIPTION"       => $arFields['TITLE'],
				"DESCRIPTION_STYLE" => 'task'
			);
		}
		else 
		{
			$strMessage = $strShortMessage = '';

			if ($bMail)
			{
				$strMessage = $strShortMessage = str_replace(
					array('<nobr>', '</nobr>'), 
					array('', ''), 
					$arFields['TEXT_MESSAGE']
					);
			}
			else
			{
				$strMessage      = $arFields['MESSAGE'];
				$strShortMessage = $arFields['~MESSAGE'];
			}

			$arResult["EVENT_FORMATTED"] = array(
				"TITLE"            => $title,
				//"TITLE_24"         => $title_24,
				"MESSAGE"          => $strMessage,
				"SHORT_MESSAGE"    => $strShortMessage,
				"IS_MESSAGE_SHORT" => true,
				"STYLE"            => 'tasks-info'
			);
		}

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arFields);

			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		elseif ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
		{
			$arResult["EVENT_FORMATTED"]["DESTINATION"] = array(
				array(
					"STYLE" => "sonetgroups",
					"TITLE" => $arResult["ENTITY"]["FORMATTED"]["NAME"],
					"URL"   => $arResult["ENTITY"]["FORMATTED"]["URL"],
					"IS_EXTRANET" => (is_array($GLOBALS["arExtranetGroupID"]) && in_array($arFields["ENTITY_ID"], $GLOBALS["arExtranetGroupID"]))
				)
			);
		}

		if (
			( ! $bMail )
			&& (strlen($task_datetime) > 0)
		)
		{
			$arResult["EVENT_FORMATTED"]["LOG_DATE_FORMAT"] = $task_datetime;
		}

		return $arResult;
	}


	private static function prepareRightsCodesForViewInGroupLiveFeed($logID, $groupId)
	{
		$arRights = array();

		if ($groupId)
			$arRights = array('SG' . $groupId);

		return ($arRights);
	}


	private static function formatTaskName($taskId, $title, $groupId = 0, $bUrl = false)
	{
		$name = '[#' . $taskId . '] ';

		if ($bUrl)
			$name .= '[URL=#PATH_TO_TASK#]';

		$name .= $title;

		if ($bUrl)
			$name .= '[/URL]';

		if ($groupId && CModule::IncludeModule('socialnetwork'))
		{
			$arGroup = CSocNetGroup::GetByID($groupId);

			if (is_string($arGroup['NAME']) && ($arGroup['NAME'] !== ''))
				$name .= ' (' . GetMessage('TASKS_NOTIFICATIONS_IN_GROUP') . ' ' . $arGroup['NAME'] . ')';
		}

		return ($name);
	}
}
