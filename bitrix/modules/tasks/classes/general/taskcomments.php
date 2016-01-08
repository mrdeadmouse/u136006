<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

class CTaskComments
{
	/**
	 * Create new comment for task
	 * 
	 * @param integer $taskId
	 * @param integer $commentAuthorId - ID of user who is comment's author
	 * @param string $commentText - text in BB code
	 * @param additional fields to be passed to CForumMessage::Add() through ForumAddMessage()
	 * 
	 * @throws TasksException, CTaskAssertException
	 * 
	 * @return integer $messageId
	 */
	public static function add($taskId, $commentAuthorId, $commentText, $arFields = array())
	{
		CTaskAssert::assertLaxIntegers($taskId, $commentAuthorId);
		CTaskAssert::assert(is_string($commentText));

		if ( ! CModule::includeModule('forum') )
		{
			throw new TasksException(
				'forum module can not be loaded',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		IncludeModuleLangFile(__FILE__);

		$forumId = CTasksTools::GetForumIdForIntranet();
		$oTask = CTaskItem::getInstance($taskId, $commentAuthorId);
		$arTask = $oTask->getData();

		$outForumTopicId = $outStrUrl = null;
		$arErrorCodes = array();

		$messageId = self::__deprecated_Add(
			$commentText,
			$forumTopicId      = $arTask['FORUM_TOPIC_ID'],
			$forumId,
			$nameTemplate      = CSite::GetNameFormat(false),
			$arTask            = $arTask,
			$permissions       = 'Y',
			$commentId         = 0,
			$givenUserId       = $commentAuthorId,
			$imageWidth        = 300,
			$imageHeight       = 300,
			$arSmiles          = array(),
			$arForum           = CForumNew::GetByID($forumId),
			$messagesPerPage   = 10,
			$arUserGroupArray  = CUser::GetUserGroup($commentAuthorId),
			$backPage          = null,
			$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD"),
			$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT"),
			$strMsgNewTask     = GetMessage("TASKS_COMMENT_SONET_NEW_TASK_MESSAGE"),
			$componentName     = null,
			$outForumTopicId,
			$arErrorCodes,
			$outStrUrl,
			$arFields
		);

		if ( ! ($messageId >= 1) )
		{
			throw new TasksException(
				serialize($arErrorCodes),
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			);
		}

		return ( (int) $messageId );
	}

	/**
	 * Create new comment for task
	 * 
	 * @param integer $taskId
	 * @param integet $commentId
	 * @param integer $commentEditorId - ID of user who is comment's editor
	 * @param string[] $arFields - fields to be updated, including text in BB code
	 * 
	 * @throws TasksException, CTaskAssertException
	 * 
	 * @return boolean
	 */
	public static function update($taskId, $commentId, $commentEditorId, $arFields)
	{
		CTaskAssert::assertLaxIntegers($taskId, $commentId, $commentEditorId);
		CTaskAssert::assert(is_array($arFields) && !empty($arFields));

		if ( ! CModule::includeModule('forum') )
		{
			throw new TasksException(
				'forum module can not be loaded',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		IncludeModuleLangFile(__FILE__);

		$forumId = CTasksTools::GetForumIdForIntranet();
		$oTask = CTaskItem::getInstance($taskId, $commentEditorId);
		$arTask = $oTask->getData();

		$outForumTopicId = $outStrUrl = null;
		$arErrorCodes = array();

		$arFields = array_merge(array(
			'EDITOR_ID' => $commentEditorId
		), $arFields);

		$messageId = self::__deprecated_Add(
			$arFields['POST_MESSAGE'],
			$forumTopicId      = $arTask['FORUM_TOPIC_ID'],
			$forumId,
			$nameTemplate      = CSite::GetNameFormat(false),
			$arTask            = $arTask,
			$permissions       = 'Y',
			$commentId         = $commentId,
			$givenUserId       = $commentEditorId,
			$imageWidth        = 300,
			$imageHeight       = 300,
			$arSmiles          = array(),
			$arForum           = CForumNew::GetByID($forumId),
			$messagesPerPage   = 10,
			$arUserGroupArray  = CUser::GetUserGroup($commentEditorId),
			$backPage          = null,
			$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD"),
			$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT"),
			$strMsgNewTask     = GetMessage("TASKS_COMMENT_SONET_NEW_TASK_MESSAGE"),
			$componentName     = null,
			$outForumTopicId,
			$arErrorCodes,
			$outStrUrl,
			$arFields
		);

		if ( ! ($messageId >= 1) )
		{
			throw new TasksException(
				serialize($arErrorCodes),
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			);
		}

		return ( true );
	}

	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 * 
	 * @access private
	 */
	public static function onCommentTopicAdd($entityType, $entityId, $arPost, &$arTopic)
	{
		// 'TK' is our entity type
		if ($entityType !== 'TK')
			return;

		if ( ! (
			CTaskAssert::isLaxIntegers($entityId)
			&& ((int) $entityId >= 1)
		))
		{
			CTaskAssert::logError('[0xb6324222] Expected integer $entityId >= 1');
			return;
		}

		$taskId = (int) $entityId;

		$task = CTasks::GetList(array(), array('ID' => $taskId), array('TITLE', 'DESCRIPTION', 'CREATED_BY'))->Fetch();
		if ($task)
		{
			$arTopic["TITLE"] = $task["TITLE"];
			$arTopic["MESSAGE"] = $task["DESCRIPTION"];
			$arTopic["AUTHOR_ID"] = $task["CREATED_BY"];
		}
	}

	public static function onAfterCommentTopicAdd($entityType, $entityId, $topicId)
	{
		// 'TK' is our entity type
		if ($entityType !== 'TK')
			return;

		if ( ! (
			CTaskAssert::isLaxIntegers($entityId)
			&& ((int) $entityId >= 1)
		))
		{
			CTaskAssert::logError('[0xb6324222] Expected integer $entityId >= 1');
			return;
		}

		$taskId = (int) $entityId;

		if ($entityType === 'TK')
		{
			$oTask = new CTasks();
			$oTask->Update($entityId, array('FORUM_TOPIC_ID' => $topicId));
		}
	}


	public static function fireOnAfterCommentAddEvent($commentId, $taskId, $commentText, $arFilesIds)
	{
		$arFields = array(
			'TASK_ID'      => $taskId,
			'COMMENT_TEXT' => $commentText,
			'FILES'        => $arFilesIds
		);

		self::addFilesRights($taskId, $arFilesIds);

		foreach(GetModuleEvents('tasks', 'OnAfterCommentAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($commentId, &$arFields));
	}


	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 * 
	 * @access private
	 */
	public static function onAfterCommentAdd($entityType, $entityId, $arData)
	{
		global $USER;

		$MESSAGE_TYPE = 'NEW';

		$arFilesIds = array();

		// 'TK' is our entity type
		if ($entityType !== 'TK')
			return;

		if ( ! (
			CTaskAssert::isLaxIntegers($entityId)
			&& ((int) $entityId >= 1)
		))
		{
			CTaskAssert::logWarning('[0xc4b31fa6] Expected integer $entityId >= 1');
			return;
		}

		$loggedInUserId = 1;
		if (is_object($USER) && method_exists($USER, 'getId'))
			$loggedInUserId = (int) $USER->getId();

		$taskId = (int) $entityId;

		$topicId    = $arData['TOPIC_ID'];
		$messageId  = $arData['MESSAGE_ID'];
		$strMessage = $arData['PARAMS']['POST_MESSAGE'];

		if (
			isset($arData['PARAMS']['UF_FORUM_MESSAGE_DOC']) 
			&& !empty($arData['PARAMS']['UF_FORUM_MESSAGE_DOC']) 
		)
		{
			$arFilesIds = $arData['PARAMS']['UF_FORUM_MESSAGE_DOC'];
		}

		$parser = new CTextParser();

		$messageAuthorId = null;
		$messageEditDate = null;
		$messagePostDate = null;

		if (
			array_key_exists('AUTHOR_ID', $arData['PARAMS'])
			&& array_key_exists('EDIT_DATE', $arData['PARAMS'])
			&& array_key_exists('POST_DATE', $arData['PARAMS'])
		)
		{
			$messageAuthorId = $arData['PARAMS']['AUTHOR_ID'];
			$messageEditDate = $arData['PARAMS']['EDIT_DATE'];
			$messageEditDate = $arData['PARAMS']['POST_DATE'];
		}
		else
		{
			$arMessage = CForumMessage::GetByID($messageId);

			$messageAuthorId = $arMessage['AUTHOR_ID'];
			$messageEditDate = $arMessage['EDIT_DATE'];
			$messageEditDate = $arMessage['POST_DATE'];
		}

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = ($messageAuthorId ? $messageAuthorId : 1);

		$oTask = CTaskItem::getInstance($taskId, 1);
		$arTask = $oTask->getData();

		$arRecipientsIDs = self::getTaskMembersByTaskId($taskId, $excludeUser = $occurAsUserId);

		// Instant Messages
		if (IsModuleInstalled("im") && CModule::IncludeModule("im") && sizeof($arRecipientsIDs))
		{
			// There are different links for extranet users
			$isExtranetEnabled = false;
			if (CModule::IncludeModule("extranet"))
				$isExtranetEnabled = true;

			if ($isExtranetEnabled)
			{
				$arSites = array();
				$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));

				while($arSite = $dbSite->Fetch())
				{
					if (strlen(trim($arSite["DIR"])) > 0)
						$arSites[$arSite['ID']]['DIR'] = $arSite['DIR'];
					else
						$arSites[$arSite['ID']]['DIR'] = '/';

					if (strlen(trim($arSite["SERVER_NAME"])) > 0)
						$arSites[$arSite['ID']]['SERVER_NAME'] = $arSite["SERVER_NAME"];
					else
						$arSites[$arSite['ID']]['SERVER_NAME'] = COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]);

					$arSites[$arSite['ID']]['urlPrefix'] = $arSites[$arSite['ID']]['SERVER_NAME'] . $arSites[$arSite['ID']]['DIR'];

					// remove last '/'
					if (
						(strlen($arSites[$arSite['ID']]['urlPrefix']) > 0)
						&& (substr($arSites[$arSite['ID']]['urlPrefix'], -1) === '/')
					)
					{
						$arSites[$arSite['ID']]['urlPrefix'] = substr($arSites[$arSite['ID']]['urlPrefix'], 0, -1);
					}
				}

				$extranet_site_id = CExtranet::GetExtranetSiteID();
				$intranet_site_id = CSite::GetDefSite();

				$arIntranetUsers = CExtranet::GetIntranetUsers();
			}
			else
			{
				if ($arTask["GROUP_ID"])
					$pathTemplateWoExtranet = str_replace("#group_id#", $arTask["GROUP_ID"], COption::GetOptionString("tasks", "paths_task_group_entry", "/workgroups/group/#group_id#/tasks/task/view/#task_id#/", $arFields["SITE_ID"]));
				else
					$pathTemplateWoExtranet = COption::GetOptionString("tasks", "paths_task_user_entry", "/company/personal/user/#user_id#/tasks/task/view/#task_id#/", $arFields["SITE_ID"]);
			}

			IncludeModuleLangFile(__FILE__);

			$rsUser = CUser::GetList(
				$by = 'id',
				$order = 'asc',
				array('ID_EQUAL_EXACT' => (int) $occurAsUserId),
				array('FIELDS' => array('PERSONAL_GENDER'))
			);

			$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD");
			$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT");

			if ($arUser = $rsUser->fetch())
			{
				switch ($arUser['PERSONAL_GENDER'])
				{
					case "F":
					case "M":
						$strMsgAddComment  = GetMessage("TASKS_COMMENT_MESSAGE_ADD" . '_' . $arUser['PERSONAL_GENDER']);
						$strMsgEditComment = GetMessage("TASKS_COMMENT_MESSAGE_EDIT" . '_' . $arUser['PERSONAL_GENDER']);
					break;

					default:
					break;
				}
			}

			foreach ($arRecipientsIDs as $userID)
			{
				$urlPrefixForUser = tasksServerName();

				if ($isExtranetEnabled)
				{
					if ( ! in_array($userID, $arIntranetUsers) 
						&& $extranet_site_id
					)
					{
						$userSiteId = $extranet_site_id;
					}
					else
						$userSiteId = $intranet_site_id;

					if (isset($arSites[$userSiteId]['SERVER_NAME']))
					{
						$urlPrefixForUser = tasksServerName(
							$arSites[$userSiteId]['SERVER_NAME']
						);
					}

					if ($arTask["GROUP_ID"])
					{
						$pathTemplate = str_replace(
							'#group_id#', 
							$arTask['GROUP_ID'], 
							CTasksTools::GetOptionPathTaskGroupEntry($userSiteId, '')
						);
					}
					else
						$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry($userSiteId, '');
				}
				else
					$pathTemplate = $pathTemplateWoExtranet;

				$NOTIFY_MESSAGE_TITLE_TEMPLATE = '';
				$messageUrl = '';
				if (strlen($pathTemplate) > 0)
				{
					$groupId = 0;
					
					if (isset($arTask['GROUP_ID']))
						$groupId = (int) $arTask['GROUP_ID'];

					$messageUrl = $urlPrefixForUser 
						. CComponentEngine::MakePathFromTemplate(
							$pathTemplate, 
							array(
								"user_id"  => $userID, 
								"task_id"  => $taskId, 
								"action"   => "view",
								"USER_ID"  => $userID, 
								"TASK_ID"  => $taskId, 
								"ACTION"   => "view",
								'GROUP_ID' => $groupId,
								'group_id' => $groupId
								)
							);

					$messageUrl .= ( strpos($messageUrl, "?") === false ? "?" : "&")."MID=".$messageId;

					$NOTIFY_MESSAGE_TITLE_TEMPLATE = '[URL=' . $messageUrl . "#message" . $messageId.']' 
						. $arTask["TITLE"] . '[/URL]';
				}
				else
					$NOTIFY_MESSAGE_TITLE_TEMPLATE = $arTask["TITLE"];

				$MESSAGE_SITE = preg_replace(
					array(
						'|\[\/USER\]|', 
						'|\[USER=\d+\]|',
						'|\[DISK\sFILE\sID=[n]*\d+\]|',
						'|\[DOCUMENT\sID=\d+\]|'
					), 
					'', 
					$strMessage
				);
				$MESSAGE_EMAIL = $MESSAGE_SITE;	// full message to email

				if (strlen($MESSAGE_SITE) >= 100)
				{
					$dot = '...';
					$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 99);

					if (substr($MESSAGE_SITE, -1) === '[')
						$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 98);

					if (
						(($lastLinkPosition = strrpos($MESSAGE_SITE, '[u')) !== false)
						|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'http://')) !== false)
						|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'https://')) !== false)
						|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftp://')) !== false)
						|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftps://')) !== false)
					)
					{
						if (strpos($MESSAGE_SITE, ' ', $lastLinkPosition) === false)
							$MESSAGE_SITE = substr($MESSAGE_SITE, 0, $lastLinkPosition);
					}

					$MESSAGE_SITE .= $dot;
				}

				$arMessageFields = array(
					"TO_USER_ID" => $userID,
					"FROM_USER_ID" => $occurAsUserId, 
					"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
					"NOTIFY_MODULE" => "tasks", 
					"NOTIFY_EVENT" => "comment", 
					"NOTIFY_MESSAGE" => str_replace(
						array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
						array($NOTIFY_MESSAGE_TITLE_TEMPLATE, '[COLOR=#000000]'.$MESSAGE_SITE.'[/COLOR]'), 
						($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
					),
					"NOTIFY_MESSAGE_OUT" => str_replace(
						array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
						array($arTask["TITLE"], $MESSAGE_EMAIL.' #BR# '.$messageUrl."#message".$messageId.' '), 
						($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
					)
				);

				CIMNotify::Add($arMessageFields);
			}
		}

		$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
		$strURL = ForumAddPageParams(
			$strURL,
			array(
				"MID" => $messageId, 
				"result" => "reply"
			), 
			false, 
			false
		);

		// sonet log
		if (CModule::IncludeModule("socialnetwork"))
		{
			$bCrmTask = (
				isset($arTask["UF_CRM_TASK"])
				&& (
					(
						is_array($arTask["UF_CRM_TASK"])
						&& (
							isset($arTask["UF_CRM_TASK"][0])
							&& strlen($arTask["UF_CRM_TASK"][0]) > 0
						)
					)
					||
					(
						!is_array($arTask["UF_CRM_TASK"])
						&& strlen($arTask["UF_CRM_TASK"]) > 0
					)
				)
			);

			if (!$bCrmTask)
			{
				$dbRes = CSocNetLog::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => "tasks",
						"SOURCE_ID" => $taskId
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
				);
				if ($arRes = $dbRes->Fetch())
				{
					$log_id = $arRes["ID"];
					$entity_type = $arRes["ENTITY_TYPE"];
					$entity_id = $arRes["ENTITY_ID"];
				}
				else
				{
					$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
					$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

					$rsUser = CUser::GetByID($arTask["CREATED_BY"]);
					if ($arUser = $rsUser->Fetch())
					{
						$arSoFields = array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "tasks",
							"LOG_DATE" => $arTask["CREATED_DATE"],
							"TITLE_TEMPLATE" => "#TITLE#",
							"TITLE" => $arTask["TITLE"],
							"MESSAGE" => "",
							"TEXT_MESSAGE" => $strMsgNewTask,
							"MODULE_ID" => "tasks",
							"CALLBACK_FUNC" => false,
							"SOURCE_ID" => $taskId,
							"ENABLE_COMMENTS" => "Y",
							"USER_ID" => $arTask["CREATED_BY"],
							"URL" => CTaskNotifications::GetNotificationPath($arUser, $taskId),
							"PARAMS" => serialize(array("TYPE" => "create"))
						);
						$log_id = CSocNetLog::Add($arSoFields, false);
						if (intval($log_id) > 0)
						{
							CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
							$arRights = CTaskNotifications::__UserIDs2Rights(self::getTaskMembersByTaskId($taskId));
							if($arTask["GROUP_ID"])
								$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
							CSocNetLogRights::Add($log_id, $arRights);
						}
					}
				}
			}

			if (intval($log_id) > 0)
			{
				$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

				CSocNetLog::Update(
					$log_id,
					array(
						'PARAMS' => serialize(array('TYPE' => 'comment'))
					)
				);

				$arFieldsForSocnet = array(
					"ENTITY_TYPE" => $entity_type,
					"ENTITY_ID" => $entity_id,
					"EVENT_ID" => "tasks_comment",
					"MESSAGE" => $sText,
					"TEXT_MESSAGE" => $parser->convert4mail($sText),
					"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
					"MODULE_ID" => "tasks",
					"SOURCE_ID" => $messageId,
					"LOG_ID" => $log_id,
					"RATING_TYPE_ID" => "FORUM_POST",
					"RATING_ENTITY_ID" => $messageId
				);

				if ($MESSAGE_TYPE == "EDIT")
				{
					$dbRes = CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> array("tasks_comment"),
							"SOURCE_ID" => $messageId
						),
						false,
						false,
						array("ID")
					);
					while ($arRes = $dbRes->Fetch())
						CSocNetLogComments::Update($arRes["ID"], $arFieldsForSocnet);
				}
				else
				{
					$arFieldsForSocnet["USER_ID"] = $occurAsUserId;
					$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS['DB']->CurrentTimeFunction();

					$ufFileID = array();
					$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageId));
					while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
						$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

					if (count($ufFileID) > 0)
						$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

					$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageId, LANGUAGE_ID);
					if ($ufDocID)
						$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;							

					$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
					CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
				}
			}
		}

		// Tasks log
		$arLogFields = array(
			"TASK_ID" => $taskId,
			"USER_ID" => $occurAsUserId,
			"CREATED_DATE" => ($messageEditDate ? ConvertTimeStamp(MakeTimeStamp($messageEditDate, CSite::GetDateFormat()), "FULL") : $messagePostDate),
			"FIELD" => "COMMENT",
			"TO_VALUE" => $messageId
		);

		$log = new CTaskLog();
		$log->Add($arLogFields);

		CTaskComments::fireOnAfterCommentAddEvent($messageId, $taskId, $strMessage, $arFilesIds);
	}


	/**
	 * This is not a part of public API.
	 * This function is for internal use only.
	 *
	 * @access private
	 */
	public static function onAfterCommentUpdate($entityType, $taskID, $arData)
	{
		// 'TK' is our entity type
		if ($entityType !== 'TK')
			return;
			
		if (empty($arData["MESSAGE_ID"]))
			return;

		if (CModule::IncludeModule("socialnetwork"))
		{
			$parser = new CTextParser();
			$parser->allow = array("HTML" => 'Y',"ANCHOR" => 'Y',"BIU" => 'Y',"IMG" => "Y","VIDEO" => "Y","LIST" => 'N',"QUOTE" => 'Y',"CODE" => 'Y',"FONT" => 'Y',"SMILES" => "N","UPLOAD" => 'N',"NL2BR" => 'N',"TABLE" => "Y");

			$oTask = CTaskItem::getInstance($taskID, 1);
			$arTask = $oTask->getData();

			$bCrmTask = (
				isset($arTask["UF_CRM_TASK"])
				&& (
					(
						is_array($arTask["UF_CRM_TASK"])
						&& (
							isset($arTask["UF_CRM_TASK"][0])
							&& strlen($arTask["UF_CRM_TASK"][0]) > 0
						)
					)
					||
					(
						!is_array($arTask["UF_CRM_TASK"])
						&& strlen($arTask["UF_CRM_TASK"]) > 0
					)
				)
			);

			switch ($arData["ACTION"])
			{
				case "DEL":
				case "HIDE":
					$dbLogComment = CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
							"SOURCE_ID" => intval($arData["MESSAGE_ID"])
						),
						false,
						false,
						array("ID")
					);
					while ($arLogComment = $dbLogComment->Fetch())
						CSocNetLogComments::Delete($arLogComment["ID"]);
					break;
				case "SHOW":
					$dbLogComment = CSocNetLogComments::GetList(
						array("ID" => "DESC"),
						array(
							"EVENT_ID"	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
							"SOURCE_ID" => intval($arData["MESSAGE_ID"])
						),
						false,
						false,
						array("ID")
					);
					$arLogComment = $dbLogComment->Fetch();
					if (!$arLogComment)
					{
						$arMessage = CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
						if ($arMessage)
						{
							$arFilter = false;
							if (!$bCrmTask)
							{
								$arFilter = array(
									"EVENT_ID" => "tasks",
									"SOURCE_ID" => $taskID
								);
							}
							elseif (CModule::IncludeModule("crm"))
							{
								$dbCrmActivity = CCrmActivity::GetList(
									array(), 
									array(
										'TYPE_ID' => CCrmActivityType::Task,
										'ASSOCIATED_ENTITY_ID' => $taskID,
										'CHECK_PERMISSIONS' => 'N'
									), 
									false, 
									false, 
									array('ID')
								);

								if ($arCrmActivity = $dbCrmActivity->Fetch())
								{
									$arFilter = array(
										"EVENT_ID" => "crm_activity_add",
										"ENTITY_ID" => $arCrmActivity["ID"]
									);
								}
							}

							if ($arFilter)
							{
								$dbLog = CSocNetLog::GetList(
									array("ID" => "DESC"),
									$arFilter,
									false,
									false,
									array("ID", "ENTITY_TYPE", "ENTITY_ID")
								);
								if ($arLog = $dbLog->Fetch())
								{
									$log_id = $arLog["ID"];
									$entity_type = $arLog["ENTITY_TYPE"];
									$entity_id = $arLog["ENTITY_ID"];
								}
								else
								{
									$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
									$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

									$rsUser = CUser::GetByID($arTask["CREATED_BY"]);
									if ($arUser = $rsUser->Fetch())
									{
										$arSoFields = array(
											"ENTITY_TYPE" => $entity_type,
											"ENTITY_ID" => $entity_id,
											"EVENT_ID" => "tasks",
											"LOG_DATE" => $arTask["CREATED_DATE"],
											"TITLE_TEMPLATE" => "#TITLE#",
											"TITLE" => $arTask["TITLE"],
											"MESSAGE" => "",
											"TEXT_MESSAGE" => $strMsgNewTask,
											"MODULE_ID" => "tasks",
											"CALLBACK_FUNC" => false,
											"SOURCE_ID" => $taskID,
											"ENABLE_COMMENTS" => "Y",
											"USER_ID" => $arTask["CREATED_BY"],
											"URL" => CTaskNotifications::GetNotificationPath($arUser, $taskID),
											"PARAMS" => serialize(array("TYPE" => "create"))
										);
										$log_id = CSocNetLog::Add($arSoFields, false);
										if (intval($log_id) > 0)
										{
											$arRights = CTaskNotifications::__UserIDs2Rights(self::getTaskMembersByTaskId($taskID));
											if($arTask["GROUP_ID"])
												$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
											CSocNetLogRights::Add($log_id, $arRights);
										}
									}
								}
							}

							if ($log_id > 0)
							{
								$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
								$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
								$strURL = ForumAddPageParams(
									$strURL,
									array(
										"MID" => intval($arData["MESSAGE_ID"]), 
										"result" => "reply"
									), 
									false, 
									false
								);

								$arFieldsForSocnet = array(
									"ENTITY_TYPE" => $entity_type,
									"ENTITY_ID" => $entity_id,
									"EVENT_ID" => ($bCrmTask ? 'crm_activity_add_comment' : 'tasks_comment'),
									"MESSAGE" => $sText,
									"TEXT_MESSAGE" => $parser->convert4mail($sText),
									"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
									"MODULE_ID" => "tasks",
									"SOURCE_ID" => intval($arData["MESSAGE_ID"]),
									"LOG_ID" => $log_id,
									"RATING_TYPE_ID" => "FORUM_POST",
									"RATING_ENTITY_ID" => intval($arData["MESSAGE_ID"])
								);

								$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];
								$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS["DB"]->CurrentTimeFunction();

								$ufFileID = array();
								$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
								while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
									$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

								if (count($ufFileID) > 0)
									$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

								$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
								if ($ufDocID)
									$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;							

								$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
								CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
							}
						}
					}
					break;
				case "EDIT":
					$arMessage = CForumMessage::GetByID(intval($arData["MESSAGE_ID"]));
					if ($arMessage)
					{
						$dbLogComment = CSocNetLogComments::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID" => ($bCrmTask ? 'crm_activity_add_comment' : 'tasks_comment'),
								"SOURCE_ID" => intval($arData["MESSAGE_ID"])
							),
							false,
							false,
							array("ID")
						);
						$arLogComment = $dbLogComment->Fetch();
						if ($arLogComment)
						{
							$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);
							$arFieldsForSocnet = array(
								"MESSAGE" => $sText,
								"TEXT_MESSAGE" => $parser->convert4mail($sText),
							);

							$ufFileID = array();
							$arFilesIds = array();

							$taskId = null;
							if (
								isset($arData['PARAMS']['PARAM2']) 
								&& !empty($arData['PARAMS']['PARAM2']) 
							)
							{
								$taskId = (int) $arData['PARAMS']['PARAM2'];
							}

							$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => intval($arData["MESSAGE_ID"])));
							while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							{
								$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
								$arFilesIds[] = $arAddedMessageFiles["FILE_ID"];
							}

							if (count($ufFileID) > 0)
								$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

							$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", intval($arData["MESSAGE_ID"]), LANGUAGE_ID);
							if ($ufDocID)
							{
								$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;

								if (is_array($ufDocID))
									$arFilesIds = array_merge($arFilesIds, $ufDocID);
							}

							if ($taskId && ! empty($arFilesIds))
							{
								self::addFilesRights($taskId, $arFilesIds);
							}

							CSocNetLogComments::Update($arLogComment["ID"], $arFieldsForSocnet);
						}
					}
					break;
				default:
			}
		}
	}


	public static function Remove($taskId, $commentId, $userId, $arParams)
	{
		global $DB;

		if (self::CanRemoveComment($taskId, $commentId, $userId, $arParams) !== true)
			throw new TasksException('', TasksException::TE_ACCESS_DENIED);

		$strErrorMessage = $strOKMessage = '';
		$result = ForumDeleteMessage($commentId, $strErrorMessage, $strOKMessage, array('PERMISSION' => 'Y'));

		if($result)
		{
			if (CModule::IncludeModule("socialnetwork"))
			{
				$oTask = CTaskItem::getInstance($taskId, 1);
				$arTask = $oTask->getData();

				$bCrmTask = (
					isset($arTask["UF_CRM_TASK"])
					&& (
						(
							is_array($arTask["UF_CRM_TASK"])
							&& (
								isset($arTask["UF_CRM_TASK"][0])
								&& strlen($arTask["UF_CRM_TASK"][0]) > 0
							)
						)
						||
						(
							!is_array($arTask["UF_CRM_TASK"])
							&& strlen($arTask["UF_CRM_TASK"]) > 0
						)
					)
				);

				$dbRes = CSocNetLogComments::GetList(
					array(),
					array(
						'EVENT_ID'	=> ($bCrmTask ? array('crm_activity_add_comment') : array('tasks_comment')),
						'SOURCE_ID' => $commentId
					),
					false,
					false,
					array('ID')
				);

				if ($arRes = $dbRes->Fetch())
				{
					CSocNetLogComments::Delete($arRes['ID']);
				}
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = ($userId ? $userId : 1);

			// Tasks log
			$arLogFields = array(
				'TASK_ID'       =>  $taskId,
				'USER_ID'       =>  $occurAsUserId,
				'~CREATED_DATE' =>  $DB->CurrentTimeFunction(),
				'FIELD'         => 'COMMENT_REMOVE'
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);

		}

		return $result;
	}


	public static function CanUpdateComment($taskId, $commentId, $userId, $arParams)
	{
		$bCommentsCanBeUpdated = COption::GetOptionString('tasks', 'task_comment_allow_edit'); // there could be trouble

		if ( ! $bCommentsCanBeUpdated || !CModule::IncludeModule('forum'))
			return (false);

		return self::CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams);
	}


	public static function CanRemoveComment($taskId, $commentId, $userId, $arParams)
	{
		$bCommentsCanBeRemoved = COption::GetOptionString('tasks', 'task_comment_allow_remove'); // there could be trouble

		if ( ! $bCommentsCanBeRemoved || !CModule::IncludeModule('forum'))
			return (false);

		return self::CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams);
	}


	private static function CheckUpdateRemoveCandidate($taskId, $commentId, $userId, $arParams)
	{
		$filter = array('TOPIC_ID' => $arParams['FORUM_TOPIC_ID']);

		// have no idea in which case the following parameters will be used:
		if(isset($arParams['FORUM_ID']))
			$filter['FORUM_ID'] = $arParams['FORUM_ID'];
		if(isset($arParams['APPROVED']))
			$filter['APPROVED'] = $arParams['APPROVED'];

		$res = CForumMessage::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			0,
			array('bShowAll' => true)
		);

		// Take last message
		$comment = false;
		$lastComment = false;
		$cnt = 0;
		while ($ar = $res->fetch())
		{
			if($ar['ID'] == $commentId)
				$comment = $ar;

			$lastComment = $ar;
			$cnt++;
		}

		if ( $cnt == 0 ) // no comments in the topic
			return (false);

		if ( empty($comment) ) // comment not found
			return (false);

		if (
			CTasksTools::isAdmin($userId)
			|| CTasksTools::IsPortalB24Admin($userId)
		)
		{
			return (true);
		}
		elseif ($userId == $lastComment['AUTHOR_ID'])
		{
			if ($commentId != $lastComment['ID'])	// it's not the last comment
				return (false);
			else
				return (true);
		}
		else
			return (false);
	}


	public static function onAfterTaskAdd($taskId, $arFields)
	{
		if ( ! isset($arFields['UF_TASK_WEBDAV_FILES']) )
			return;

		$arFilesIds = array_filter($arFields['UF_TASK_WEBDAV_FILES']);

		if (empty($arFilesIds))
			return;

		self::addFilesRights($taskId, $arFilesIds);
	}


	public static function onAfterTaskUpdate($taskId, $arTask, $arFields)
	{
		// List of files to be updated
		if (isset($arFields['UF_TASK_WEBDAV_FILES']))
			$arFilesIds = array_filter($arFields['UF_TASK_WEBDAV_FILES']);
		else
			$arFilesIds = array();

		$arAddedMembers = array_diff(
			self::getTaskMembersByFields($arFields),
			self::getTaskMembersByFields($arTask)
		);

		// If added new members to task - rights for ALL files must be updated
		if ( ! empty($arAddedMembers) )
		{
			// Get all files of task
			if (is_array($arTask['UF_TASK_WEBDAV_FILES']))
				$arFilesIds = array_merge($arFilesIds, $arTask['UF_TASK_WEBDAV_FILES']);

			// Get all files from all comments
			$arFilesIds = array_merge($arFilesIds, self::getCommentsFiles($arTask['FORUM_TOPIC_ID']));
		}

		// Nothing to do?
		if (empty($arFilesIds))
			return;

		self::addFilesRights($taskId, $arFilesIds);
	}


	private static function getCommentsFiles($forumTopicId)
	{
		$arFilesIds = array();

		if (
			CModule::IncludeModule('forum')
			&& ($forumId = CTasksTools::GetForumIdForIntranet())
			&& ($forumId >= 1)
		)
		{
			$rc = CForumMessage::GetListEx(
				array(),
				array('FORUM_ID' => $forumId, 'TOPIC_ID' => $forumTopicId)
			);

			$arMessagesIds = array();
			while ($arMsg = $rc->fetch())
				$arMessagesIds[] = (int) $arMsg['ID'];

			foreach ($arMessagesIds as $msgId)
			{
				$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("FORUM_MESSAGE", $msgId, LANGUAGE_ID, 1);

				if (isset($arUF['UF_FORUM_MESSAGE_DOC'], $arUF['UF_FORUM_MESSAGE_DOC']['VALUE']))
				{
					if (is_array($arUF['UF_FORUM_MESSAGE_DOC']['VALUE']))
						$arFilesIds = array_merge($arFilesIds, $arUF['UF_FORUM_MESSAGE_DOC']['VALUE']);
				}				
			}
		}

		$arFilesIds = array_unique(array_map('intval', $arFilesIds));

		return ($arFilesIds);
		/*
		if (CModule::IncludeModuel("forum"))
		{
			$arFilter = (is_array($arFilter) ? $arFilter : array($arFilter));
			$arFilter[">UF_FORUM_MESSAGE_DOC"] = 0;
			$db_res = CForumMessage::GetList(array("ID" => "ASC"), $arFilter, false, 0, array("SELECT" => array("UF_FORUM_MESSAGE_DOC")));
			$arDocs = array();
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do {
					if (!empty($res["UF_FORUM_MESSAGE_DOC"]) && is_array($res["UF_FORUM_MESSAGE_DOC"]))
						$arDocs = array_merge($arDocs, $res["UF_FORUM_MESSAGE_DOC"]);
				} while ($res = $db_res->Fetch());
			}
		}
		*/
	}


	/**
	 * Add rights for reading files by given users.
	 */
	private static function addFilesRights($taskId, $arFilesIds)
	{
		$arFilesIds = array_unique(array_filter($arFilesIds));

		// Nothing to do?
		if (empty($arFilesIds))
			return;

		if(!CModule::IncludeModule('webdav'))
			return;

		$arRightsTasks = CWebDavIblock::GetTasks();	// tasks-operations

		$oTask  = new CTaskItem((int)$taskId, 1);
		$arTask = $oTask->getData(false);

		$arTaskMembers = array_unique(array_merge(
			array($arTask['CREATED_BY'], $arTask['RESPONSIBLE_ID']),
			$arTask['AUDITORS'],
			$arTask['ACCOMPLICES']
		));

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(
			array(),
			array('ID' => $arFilesIds, 'SHOW_NEW' => 'Y'),
			false,
			false,
			array('ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW')
		);

		if ($dbWDFile)
		{
			$i = 0;
			$arRightsForTaskMembers = array();
			foreach ($arTaskMembers as $userId)
			{
				// For intranet users and their managers
				$arRightsForTaskMembers['n' . $i++] = array(
					'GROUP_CODE' => 'IU' . $userId,
					'TASK_ID'    => $arRightsTasks['R']		// rights for reading
				);

				// For extranet users
				$arRightsForTaskMembers['n' . $i++] = array(
					'GROUP_CODE' => 'U' . $userId,
					'TASK_ID'    => $arRightsTasks['R']		// rights for reading
				);
			}
			$iNext = $i;

			while ($arWDFile = $dbWDFile->Fetch())
			{
				if ( ! $arWDFile['IBLOCK_ID'] )
					continue;

				$fileId = $arWDFile['ID'];

				if (CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
				{
					$ibRights = new CIBlockElementRights($arWDFile['IBLOCK_ID'], $fileId);
					$arCurRightsRaw = $ibRights->getRights();

					// Preserve existing rights
					$i = $iNext;
					$arRights = $arRightsForTaskMembers;
					foreach ($arCurRightsRaw as $arRightsData)
					{
						$arRights['n' . $i++] = array(
							'GROUP_CODE' => $arRightsData['GROUP_CODE'],
							'TASK_ID'    => $arRightsData['TASK_ID']
						);
					}

					$ibRights->setRights($arRights);
				}
			}
		}
	}


	private static function getTaskMembersByTaskId($taskId, $excludeUser = 0)
	{
		$oTask = CTaskItem::getInstance((int)$taskId, 1);
		$arTask = $oTask->getData(false);

		$arUsersIds = CTaskNotifications::getRecipientsIDs($arTask, $bExcludeLoggedUser = false);

		$excludeUser = (int) $excludeUser;

		if ($excludeUser >= 1)
		{
			$currentUserPos = array_search($excludeUser, $arUsersIds);
			if ($currentUserPos !== false)
				unset($arUsersIds[$currentUserPos]);
		}
		else if ($excludeUser < 0)
			CTaskAssert::logWarning('[0x3c2a31fe] invalid user id (' . $excludeUser . ')');

		return ($arUsersIds);
	}


	private static function getTaskMembersByFields($arFields)
	{
		$arMembers = array();

		if (isset($arFields['CREATED_BY']))
			$arMembers[] = $arFields['CREATED_BY'];

		if (isset($arFields['RESPONSIBLE_ID']))
			$arMembers[] = $arFields['RESPONSIBLE_ID'];

		if (isset($arFields['AUDITORS']))
		{
			if ( ! is_array($arFields['AUDITORS']) )
				$arFields['AUDITORS'] = array($arFields['AUDITORS']);

			$arMembers = array_merge($arMembers, $arFields['AUDITORS']);
		}

		if (isset($arFields['ACCOMPLICES']))
		{
			if ( ! is_array($arFields['ACCOMPLICES']) )
				$arFields['ACCOMPLICES'] = array($arFields['ACCOMPLICES']);

			$arMembers = array_merge($arMembers, $arFields['ACCOMPLICES']);
		}

		$arMembers = array_unique(array_map('intval', $arMembers));

		return ($arMembers);
	}


	/**
	 * WARNING! This method is transitional and can be changed without 
	 * any notifications! Don't use it.
	 * 
	 * @deprecated
	 */
	public static function __deprecated_Add(
		$commentText,
		$forumTopicId,
		$forumId,
		$nameTemplate,
		$arTask,
		$permissions,
		$commentId,
		$givenUserId,
		$imageWidth,
		$imageHeight,
		$arSmiles,
		$arForum,
		$messagesPerPage,
		$arUserGroupArray,
		$backPage,
		$strMsgAddComment,
		$strMsgEditComment,
		$strMsgNewTask,
		$componentName,
		&$outForumTopicId,
		&$arErrorCodes,
		&$outStrUrl,
		$arFieldsAdditional
	)
	{
		global $DB;

		if (is_array($arTask))
		{
			if ( ! array_key_exists('~TITLE', $arTask) )
			{
				$arTmpTask = $arTask;

				foreach ($arTmpTask as $key => $value)
				{
					if (substr($key, 0, 1) !== '~')
						$arTask['~' . $key] = $arTmpTask[$key];
				}
			}
		}

		$MID = 0;
		$TID = 0;

		if (($forumTopicId > 0) && (CForumTopic::GetByID($forumTopicId) === false))
			$forumTopicId = false;

		if ($forumTopicId <= 0)
		{
			$arUserStart = array(
				"ID" => intVal($arTask["CREATED_BY"]),
				"NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"]
			);

			if ($arUserStart["ID"] > 0)
			{
				$res = array();
				$db_res = CForumUser::GetListEx(
					array(),
					array("USER_ID" => $arTask["CREATED_BY"])
				);

				if ($db_res && $res = $db_res->Fetch())
				{
					$res["FORUM_USER_ID"] = intVal($res["ID"]);
					$res["ID"] = $res["USER_ID"];
				}
				else
				{
					$db_res = CUser::GetByID($arTask["CREATED_BY"]);
					if ($db_res && $res = $db_res->Fetch())
					{
						$res["SHOW_NAME"] = COption::GetOptionString("forum", "USER_SHOW_NAME", "Y");
						$res["USER_PROFILE"] = "N";
					}
				}

				if (!empty($res))
				{
					$arUserStart = $res;
					$sName = ($res["SHOW_NAME"] == "Y" ? trim(CUser::FormatName($nameTemplate, $res)) : "");
					$arUserStart["NAME"] = (empty($sName) ? trim($res["LOGIN"]) : $sName);
				}
			}

			$arUserStart["NAME"] = (empty($arUserStart["NAME"]) ? $GLOBALS["FORUM_STATUS_NAME"]["guest"] : $arUserStart["NAME"]);
			$DB->StartTransaction();

			$arFields = Array(
				"TITLE" => $arTask["~TITLE"],
				"FORUM_ID" => $forumId,
				"USER_START_ID" => $arUserStart["ID"],
				"USER_START_NAME" => $arUserStart["NAME"],
				"LAST_POSTER_NAME" => $arUserStart["NAME"],
				"APPROVED" => "Y",
				"PERMISSION_EXTERNAL" => $permissions,
				"PERMISSION" => $permissions,
				"NAME_TEMPLATE" => $nameTemplate,
				'XML_ID' => 'TASK_' . $arTask['ID']
			);

			$TID = CForumTopic::Add($arFields);

			if (intVal($TID) <= 0)
				$arErrorCodes[] = array('code' => 'topic is not created');
			else
			{
				$arFields = array(
					"FORUM_TOPIC_ID" => $TID
				);

				$task = new CTasks();
				$task->Update($arTask["ID"], $arFields);
			}

			if (!empty($arErrorCodes))
			{
				$DB->Rollback();
				return false;
			}
			else
			{
				$DB->Commit();
			}
		}

		$arFieldsG = array(
			"POST_MESSAGE" => $commentText,
			"AUTHOR_NAME"  => '',
			"AUTHOR_EMAIL" => $GLOBALS['USER']->GetEmail(),
			"USE_SMILES" => NULL,
			"PARAM2" => $arTask['ID'],
			"TITLE"               => $arTask["~TITLE"],
			"PERMISSION_EXTERNAL" => $permissions,
			"PERMISSION"          => $permissions,
		);

		// UF_* forwarding
		if(is_array($arFieldsAdditional))
		{
			foreach($arFieldsAdditional as $field => $value)
			{
				if(strlen($field) && substr($field, 0, 3) == 'UF_')
				{
					$arFieldsG[$field] = $value;
					$GLOBALS[$field] = $value; // strange behaviour required for ForumMessageAdd() to handle UF_* properly
				}
			}
		}

		if (!empty($_FILES["REVIEW_ATTACH_IMG"]))
		{
			$arFieldsG["ATTACH_IMG"] = $_FILES["REVIEW_ATTACH_IMG"];
		}
		else
		{
			$arFiles = array();
			if (!empty($_REQUEST["FILES"]))
			{
				foreach ($_REQUEST["FILES"] as $key)
				{
					$arFiles[$key] = array("FILE_ID" => $key);
					if (!in_array($key, $_REQUEST["FILES_TO_UPLOAD"]))
					{
						$arFiles[$key]["del"] = "Y";
					}
				}
			}
			if (!empty($_FILES))
			{
				$res = array();
				foreach ($_FILES as $key => $val)
				{
					if (substr($key, 0, strLen("FILE_NEW")) == "FILE_NEW" && !empty($val["name"]))
					{
						$arFiles[] = $_FILES[$key];
					}
				}
			}
			if (!empty($arFiles))
			{
				$arFieldsG["FILES"] = $arFiles;
			}
		}
		$TOPIC_ID = ($forumTopicId > 0 ? $forumTopicId : $TID);

		$MESSAGE_ID = 0;
		$MESSAGE_TYPE = $TOPIC_ID > 0 ? "REPLY" : "NEW";
		if (COption::GetOptionString("tasks", "task_comment_allow_edit") && $MESSAGE_ID = intval($commentId))
		{
			$MESSAGE_TYPE = "EDIT";
		}

		$strErrorMessage = '';
		$strOKMessage = '';
		$MID = ForumAddMessage($MESSAGE_TYPE, $forumId, $TOPIC_ID, $MESSAGE_ID, 
			$arFieldsG, $strErrorMessage, $strOKMessage, false, 
			$_POST["captcha_word"], 0, $_POST["captcha_code"], $nameTemplate);

		if ($MID <= 0 || !empty($strErrorMessage))
		{
			$arErrorCodes[] = array(
				'code'  => 'message is not added 2',
				'title' => (empty($strErrorMessage) ? NULL : $strErrorMessage)
			);
		}
		else
		{
			$arMessage = CForumMessage::GetByID($MID);

			if ($forumTopicId <= 0)
			{
				$forumTopicId = $TID = intVal($arMessage["TOPIC_ID"]);
			}

			$outForumTopicId = intVal($forumTopicId);

			if ($componentName !== null)
				ForumClearComponentCache($componentName);

			// NOTIFICATION
			$arTask["ACCOMPLICES"] = $arTask["AUDITORS"] = array();
			$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $arTask["ID"]));
			while ($arMember = $rsMembers->Fetch())
			{
				if ($arMember["TYPE"] == "A")
				{
					$arTask["ACCOMPLICES"][] = $arMember["USER_ID"];
				}
				elseif ($arMember["TYPE"] == "U")
				{
					$arTask["AUDITORS"][] = $arMember["USER_ID"];
				}
			}
			$arEmailUserIDs = array($arTask["RESPONSIBLE_ID"], $arTask["CREATED_BY"]);
			$arEmailUserIDs = array_unique(array_merge($arEmailUserIDs, $arTask["ACCOMPLICES"], $arTask["AUDITORS"]));
			$currentUserPos = array_search($givenUserId, $arEmailUserIDs);
			if ($currentUserPos !== false)
			{
				unset($arEmailUserIDs[$currentUserPos]);
			}

			$parser = new CTextParser();
			$parser->imageWidth = $imageWidth;
			$parser->imageHeight = $imageHeight;
			$parser->smiles = $arSmiles;
			$parser->allow = array(
				"HTML" => $arForum["ALLOW_HTML"],
				"ANCHOR" => $arForum["ALLOW_ANCHOR"],
				"BIU" => $arForum["ALLOW_BIU"],
				"IMG" => "N",
				"VIDEO" => "N",
				"LIST" => $arForum["ALLOW_LIST"],
				"QUOTE" => $arForum["ALLOW_QUOTE"],
				"CODE" => $arForum["ALLOW_CODE"],
				"FONT" => $arForum["ALLOW_FONT"],
				"SMILES" => "N",
				"UPLOAD" => $arForum["ALLOW_UPLOAD"],
				"NL2BR" => $arForum["ALLOW_NL2BR"],
				"TABLE" => "Y"
			);

			$arAllow = NULL;
			$MESSAGE = HTMLToTxt($parser->convertText($commentText, $arAllow));

			// remove [ url] for socialnetwork log
			$MESSAGE = preg_replace("/(\s\[\s(http:\/\/|https:\/\/|ftp:\/\/))(.*?)(\s\])/is", "", $MESSAGE);

			$parser->allow = array("HTML" => 'Y',"ANCHOR" => 'Y',"BIU" => 'Y',"IMG" => "Y","VIDEO" => "Y","LIST" => 'N',"QUOTE" => 'Y',"CODE" => 'Y',"FONT" => 'Y',"SMILES" => "N","UPLOAD" => 'N',"NL2BR" => 'N',"TABLE" => "Y");
			$message_notify = $parser->convertText($commentText);

			$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arTask);

			// Instant Messages
			if (IsModuleInstalled("im") && CModule::IncludeModule("im") && sizeof($arRecipientsIDs))
			{
				$pageNumber = CForumMessage::GetMessagePage(
					$MID, 
					$messagesPerPage, 
					$arUserGroupArray
				);

				// There are different links for extranet users
				$isExtranetEnabled = false;
				if (CModule::IncludeModule("extranet"))
					$isExtranetEnabled = true;

				if ($isExtranetEnabled)
				{
					$arSites = array();
					$dbSite = CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));

					while($arSite = $dbSite->Fetch())
					{
						if (strlen(trim($arSite["DIR"])) > 0)
							$arSites[$arSite['ID']]['DIR'] = $arSite['DIR'];
						else
							$arSites[$arSite['ID']]['DIR'] = '/';

						if (strlen(trim($arSite["SERVER_NAME"])) > 0)
							$arSites[$arSite['ID']]['SERVER_NAME'] = $arSite["SERVER_NAME"];
						else
							$arSites[$arSite['ID']]['SERVER_NAME'] = COption::GetOptionString("main", "server_name", $_SERVER["HTTP_HOST"]);

						$arSites[$arSite['ID']]['urlPrefix'] = $arSites[$arSite['ID']]['SERVER_NAME'] . $arSites[$arSite['ID']]['DIR'];

						// remove last '/'
						if (
							(strlen($arSites[$arSite['ID']]['urlPrefix']) > 0)
							&& (substr($arSites[$arSite['ID']]['urlPrefix'], -1) === '/')
						)
						{
							$arSites[$arSite['ID']]['urlPrefix'] = substr($arSites[$arSite['ID']]['urlPrefix'], 0, -1);
						}
					}

					$extranet_site_id = CExtranet::GetExtranetSiteID();
					$intranet_site_id = CSite::GetDefSite();

					$arIntranetUsers = CExtranet::GetIntranetUsers();
				}
				else
				{
					if ($arTask["GROUP_ID"])
						$pathTemplateWoExtranet = str_replace("#group_id#", $arTask["GROUP_ID"], COption::GetOptionString("tasks", "paths_task_group_entry", "/workgroups/group/#group_id#/tasks/task/view/#task_id#/", $arFields["SITE_ID"]));
					else
						$pathTemplateWoExtranet = COption::GetOptionString("tasks", "paths_task_user_entry", "/company/personal/user/#user_id#/tasks/task/view/#task_id#/", $arFields["SITE_ID"]);
				}

				foreach ($arRecipientsIDs as $userID)
				{
					$urlPrefixForUser = tasksServerName();

					if ($isExtranetEnabled)
					{
						if ( ! in_array($userID, $arIntranetUsers) 
							&& $extranet_site_id
						)
						{
							$userSiteId = $extranet_site_id;
						}
						else
							$userSiteId = $intranet_site_id;

						if (isset($arSites[$userSiteId]['SERVER_NAME']))
						{
							$urlPrefixForUser = tasksServerName(
								$arSites[$userSiteId]['SERVER_NAME']
							);
						}

						if ($arTask["GROUP_ID"])
						{
							$pathTemplate = str_replace(
								'#group_id#', 
								$arTask['GROUP_ID'], 
								CTasksTools::GetOptionPathTaskGroupEntry($userSiteId, '')
								);
						}
						else
							$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry($userSiteId, '');
					}
					else
						$pathTemplate = $pathTemplateWoExtranet;

					$NOTIFY_MESSAGE_TITLE_TEMPLATE = '';
					$messageUrl = '';
					if (strlen($pathTemplate) > 0)
					{
						$groupId = 0;
						
						if (isset($arTask['GROUP_ID']))
							$groupId = (int) $arTask['GROUP_ID'];

						$messageUrl = $urlPrefixForUser 
							. CComponentEngine::MakePathFromTemplate(
								$pathTemplate, 
								array(
									"user_id"  => $userID, 
									"task_id"  => $arTask["ID"], 
									"action"   => "view",
									"USER_ID"  => $userID, 
									"TASK_ID"  => $arTask["ID"], 
									"ACTION"   => "view",
									'GROUP_ID' => $groupId,
									'group_id' => $groupId
									)
								);

						if ($pageNumber > 1)
							$messageUrl .= ( strpos($messageUrl, "?") === false ? "?" : "&")."MID=".$MID;

						$NOTIFY_MESSAGE_TITLE_TEMPLATE = '[URL=' . $messageUrl . "#message" . $MID.']' 
							. $arTask["~TITLE"] . '[/URL]';
					}
					else
						$NOTIFY_MESSAGE_TITLE_TEMPLATE = $arTask["~TITLE"];

					$MESSAGE_SITE = trim(
						htmlspecialcharsbx(
							strip_tags(
								str_replace(
									array("\r\n","\n","\r"), 
									' ', 
									htmlspecialcharsback($message_notify)
								)
							)
						)
					);

					$MESSAGE_EMAIL = $MESSAGE_SITE;	// full message to email

					if (strlen($MESSAGE_SITE) >= 100)
					{
						$dot = '...';
						$MESSAGE_SITE = substr($MESSAGE_SITE, 0, 99);

						if (
							(($lastLinkPosition = strrpos($MESSAGE_SITE, 'http://')) !== false)
							|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'https://')) !== false)
							|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftp://')) !== false)
							|| (($lastLinkPosition = strrpos($MESSAGE_SITE, 'ftps://')) !== false)
						)
						{
							if (strpos($MESSAGE_SITE, ' ', $lastLinkPosition) === false)
								$MESSAGE_SITE = substr($MESSAGE_SITE, 0, $lastLinkPosition);
						}

						$MESSAGE_SITE .= $dot;
					}

					$arMessageFields = array(
						"TO_USER_ID" => $userID,
						"FROM_USER_ID" => $givenUserId, 
						"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
						"NOTIFY_MODULE" => "tasks", 
						"NOTIFY_EVENT" => "comment", 
						"NOTIFY_MESSAGE" => str_replace(
							array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
							array($NOTIFY_MESSAGE_TITLE_TEMPLATE, '[COLOR=#000000]'.$MESSAGE_SITE.'[/COLOR]'), 
							($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
						),
						"NOTIFY_MESSAGE_OUT" => str_replace(
							array("#TASK_TITLE#", "#TASK_COMMENT_TEXT#"), 
							array($arTask["~TITLE"], $MESSAGE_EMAIL.' #BR# '.$messageUrl."#message".$MID.' '), 
							($MESSAGE_TYPE != "EDIT" ? $strMsgAddComment : $strMsgEditComment)
						),
					);

					CIMNotify::Add($arMessageFields);
				}
			}

			$strURL = (!empty($backPage) ? $backPage : $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result")));
			$strURL = ForumAddPageParams(
				$strURL,
				array(
					"MID" => $MID, 
					"result" => ($arForum["MODERATION"] != "Y" 
						|| CForumNew::CanUserModerateForum($forumId, $arUserGroupArray) ? "reply" : "not_approved"
					)
				), 
				false, 
				false
			);
			$outStrUrl = $strURL;

			// sonet log
			if (CModule::IncludeModule("socialnetwork"))
			{
				$dbRes = CSocNetLog::GetList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => "tasks",
						"SOURCE_ID" => $arTask["ID"]
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
				);
				if ($arRes = $dbRes->Fetch())
				{
					$log_id = $arRes["TMP_ID"];
					$entity_type = $arRes["ENTITY_TYPE"];
					$entity_id = $arRes["ENTITY_ID"];
				}
				else
				{
					$entity_type = ($arTask["GROUP_ID"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER);
					$entity_id = ($arTask["GROUP_ID"] ? $arTask["GROUP_ID"] : $arTask["CREATED_BY"]);

					$rsUser = CUser::GetByID($arTask["CREATED_BY"]);
					if ($arUser = $rsUser->Fetch())
					{
						$arSoFields = Array(
							"ENTITY_TYPE" => $entity_type,
							"ENTITY_ID" => $entity_id,
							"EVENT_ID" => "tasks",
							"LOG_DATE" => $arTask["CREATED_DATE"],
							"TITLE_TEMPLATE" => "#TITLE#",
							"TITLE" => htmlspecialcharsBack($arTask["~TITLE"]),
							"MESSAGE" => "",
							"TEXT_MESSAGE" => $strMsgNewTask,
							"MODULE_ID" => "tasks",
							"CALLBACK_FUNC" => false,
							"SOURCE_ID" => $arTask["ID"],
							"ENABLE_COMMENTS" => "Y",
							"USER_ID" => $arTask["CREATED_BY"],
							"URL" => CTaskNotifications::GetNotificationPath($arUser, $arTask["ID"]),
							"PARAMS" => serialize(array("TYPE" => "create"))
						);
						$log_id = CSocNetLog::Add($arSoFields, false);
						if (intval($log_id) > 0)
						{
							CSocNetLog::Update($log_id, array("TMP_ID" => $log_id));
							$arRights = CTaskNotifications::__UserIDs2Rights(CTaskNotifications::GetRecipientsIDs($arTask, false));
							if($arTask["GROUP_ID"])
								$arRights[] = "S".SONET_ENTITY_GROUP.$arTask["GROUP_ID"];
							CSocNetLogRights::Add($log_id, $arRights);
						}
					}
				}

				if (intval($log_id) > 0)
				{
					$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

					CSocNetLog::Update(
						$log_id,
						array(
							'PARAMS' => serialize(array('TYPE' => 'comment'))
						)
					);

					$arFieldsForSocnet = array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => "tasks_comment",
						"MESSAGE" => $sText,
						"TEXT_MESSAGE" => $parser->convert4mail($sText),
						"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
						"MODULE_ID" => "tasks",
						"SOURCE_ID" => $MID,
						"LOG_ID" => $log_id,
						"RATING_TYPE_ID" => "FORUM_POST",
						"RATING_ENTITY_ID" => $MID
					);

					if ($MESSAGE_TYPE == "EDIT")
					{
						$dbRes = CSocNetLogComments::GetList(
							array("ID" => "DESC"),
							array(
								"EVENT_ID"	=> array("tasks_comment"),
								"SOURCE_ID" => $MID
							),
							false,
							false,
							array("ID")
						);
						while ($arRes = $dbRes->Fetch())
						{
							CSocNetLogComments::Update($arRes["ID"], $arFieldsForSocnet);
						}
					}
					else
					{
						$arFieldsForSocnet['USER_ID']   = $givenUserId;
						$arFieldsForSocnet['=LOG_DATE'] = $GLOBALS['DB']->CurrentTimeFunction();

						$ufFileID = array();
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $MID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

						if (count($ufFileID) > 0)
							$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $MID, LANGUAGE_ID);
						if ($ufDocID)
							$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;
							
						$ufDocVer = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_VER", $MID, LANGUAGE_ID);
						if ($ufDocVer)
							$arFieldsForSocnet["UF_SONET_COM_VER"] = $ufDocVer;

						$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
						CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
					}
				}
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = ($arMessage["AUTHOR_ID"] ? $arMessage["AUTHOR_ID"] : 1);

			// Tasks log
			$arLogFields = array(
				"TASK_ID" => $arTask["ID"],
				"USER_ID" => $occurAsUserId,
				"CREATED_DATE" => ($arMessage["EDIT_DATE"] ? ConvertTimeStamp(MakeTimeStamp($arMessage["EDIT_DATE"], CSite::GetDateFormat()), "FULL") : $arMessage["POST_DATE"]),
				"FIELD" => "COMMENT",
				"TO_VALUE" => $MID
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}

		return ($MID);	// Message id
	}

	/**
	 * @deprecated
	 */
	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */ $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskcomments', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$occurAsUserId = CTasksTools::getOccurAsUserId();
				if ( ! $occurAsUserId )
					$occurAsUserId = $executiveUserId;

				$taskId          = $argsParsed[0];
				$commentText     = $argsParsed[1];
				$commentAuthorId = $occurAsUserId;
				$returnValue     = self::add($taskId, $commentAuthorId, $commentText);
			}
		}
		else
		{
			$taskId = array_shift($argsParsed);
			$oTask  = self::getInstanceFromPool($taskId, $executiveUserId);
			$returnValue = call_user_func_array(array($oTask, $methodName), $argsParsed);
		}

		return (array($returnValue, null));
	}


	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @deprecated
	 * @access private
	 */
	public static function getManifest()
	{
		return(array(
			'Manifest version' => '1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'comment',
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'add' => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'commentText',
							'type'        => 'string'
						)
					)
				)
			)
		));
	}
}
