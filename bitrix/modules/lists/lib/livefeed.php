<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CListsLiveFeed
{
	public static function setMessageLiveFeed($users, $elementId, $workflowId, $flagCompleteProcess)
	{
		$elementId = intval($elementId);
		$elementObject = CIBlockElement::getList(
			array(),
			array('ID' => $elementId),
			false,
			false,
			array('ID', 'CREATED_BY', 'IBLOCK_NAME', 'NAME', 'IBLOCK_ID', 'LANG_DIR')
		);
		$element = $elementObject->fetch();

		if(!CLists::getLiveFeed($element["IBLOCK_ID"]))
			return false;

		$element['NAME'] = preg_replace_callback(
			'#^[^\[\]]+?\[(\d+)\]#i',
			function ($matches)
			{
				$userId = $matches[1];
				$db = CUser::GetByID($userId);
				if ($ar = $db->GetNext())
				{
					$ix = randString(5);
					return '<a class="feed-post-user-name" id="bp_'.$userId.'_'.$ix.'" href="#" onClick="return false;"
						bx-post-author-id="'.$userId.'">'.CUser::FormatName(CSite::GetNameFormat(false), $ar, false, false).'</a>
						<script type="text/javascript">if (BX.tooltip) BX.tooltip(\''.$userId.'\', "bp_'.$userId.'_'.$ix.'", "");</script>';
				}
				return $matches[0];
			},
			$element['NAME']
		);

		$path = rtrim($element['LANG_DIR'], '/');
		$urlElement = $path.COption::GetOptionString('lists', 'livefeed_url').'?livefeed=y&list_id='.$element["IBLOCK_ID"].'&element_id='.$elementId;
		$createdBy = $element['CREATED_BY'];
		if(!Loader::includeModule('socialnetwork') || $createdBy <= 0)
			return false;

		$sourceId = CBPStateService::getWorkflowIntegerId($workflowId);
		$logId = 0;
		$userObject = CUser::getByID($createdBy);
		$siteId = array();
		$siteObject = CSite::getList($by="sort", $order="desc", array("ACTIVE" => "Y"));
		while ($site = $siteObject->fetch())
			$siteId[] = $site['LID'];

		if ($userObject->fetch())
		{
			global $DB;
			$soFields = Array(
				'ENTITY_TYPE' => SONET_LISTS_NEW_POST_ENTITY,
				'EVENT_ID' => 'lists_new_element',
				'ENTITY_ID' => 1,
				'=LOG_UPDATE' => $DB->currentTimeFunction(),
				'SOURCE_ID' => $sourceId,
				'USER_ID' => $createdBy,
				'MODULE_ID' => 'lists',
				'TITLE_TEMPLATE' => $urlElement,
				'TITLE' => $element['IBLOCK_NAME'],
				'MESSAGE' => $workflowId,
				'CALLBACK_FUNC' => false,
				'SITE_ID' => $siteId,
				'ENABLE_COMMENTS' => 'Y',
				'RATING_TYPE_ID' => 'LISTS_NEW_ELEMENT',
				'RATING_ENTITY_ID' => $sourceId,
				'URL' => '#SITE_DIR#'.COption::GetOptionString('socialnetwork', 'user_page', false, SITE_ID).'log/'
			);

			$logObject = CSocNetLog::getList(array(), array(
				'ENTITY_TYPE' => $soFields['ENTITY_TYPE'],
				'ENTITY_ID' => $soFields['ENTITY_ID'],
				'EVENT_ID' => $soFields['EVENT_ID'],
				'SOURCE_ID' => $soFields['SOURCE_ID'],
			));

			$iblockPicture = CIBlock::getArrayByID($element['IBLOCK_ID'], 'PICTURE');
			$imageFile = CFile::getFileArray($iblockPicture);
			if(empty($imageFile['SRC']))
				$imageFile['SRC'] = '/bitrix/images/lists/default.png';

			$soFields['TEXT_MESSAGE'] = '
				<span class="bp-title-desc">
					<span class="bp-title-desc-icon">
						<img src="'.$imageFile['SRC'].'" width="36" height="30" border="0" />
					</span>
					'.$element['NAME'].'
				</span>
			';

			if($log = $logObject->fetch())
			{
				if (intval($log['ID']) > 0)
				{
					$rightsOld = self::getRights($users, $log['ID'], $createdBy, 'post');
					$usersRight = self::getUserIdForRight($rightsOld);
					if(empty($users))
					{
						CSocNetLog::update($log['ID'], $soFields);
					}
					else
					{
						/* Recipients tasks bp */
						CSocNetLog::update($log['ID'], $soFields);
						CSocNetLogRights::deleteByLogID($log['ID']);
						$rightsCounter = self::getRights($users, $log['ID'], $createdBy, 'counter');
						CSocNetLogRights::add($log['ID'], $rightsCounter, false, false);
						CSocNetLog::counterIncrement($log['ID'], $soFields['EVENT_ID'], false, 'L', false);
						CSocNetLogRights::deleteByLogID($log['ID']);
						CSocNetLogRights::add($log['ID'], $rightsOld, false, false);
						self::setSocnetFollow($users, $log['ID'], 'Y');
						self::setSocnetFollow($users, $log['ID'], 'N');
					}

					/* Completion of the process for the author */
					if ($flagCompleteProcess)
					{
						$users[] = $createdBy;
						CSocNetLogRights::deleteByLogID($log['ID']);
						$rightsCounter = self::getRights($users, $log['ID'], $createdBy, 'counter');
						CSocNetLogRights::add($log['ID'], $rightsCounter, false, false);
						CSocNetLog::counterIncrement($log['ID'], $soFields['EVENT_ID'], false, 'L', false);
						CSocNetLogRights::deleteByLogID($log['ID']);
						CSocNetLogRights::add($log['ID'], $rightsOld, false, false);
						self::setSocnetFollow($users, $log['ID'], 'Y');
						self::setSocnetFollow($usersRight, $log['ID'], 'N');
					}
				}
			}
			else
			{
				$soFields['=LOG_DATE'] = $DB->currentTimeFunction();
				$logId = CSocNetLog::add($soFields, false);
				if (intval($logId) > 0)
				{
					$rights = self::getRights($users, $logId, $createdBy, 'post');
					CSocNetLogRights::add($logId, $rights, false, false);
					$usersRight = self::getUserIdForRight($rights);
					self::setSocnetFollow($usersRight, $logId, 'N');
				}
				CSocNetLog::counterIncrement($logId, $soFields['EVENT_ID'], false, 'L', false);
			}
		}
		return $logId;
	}

	public static function onFillSocNetAllowedSubscribeEntityTypes(&$arSocNetEntityTypes)
	{
		$arSocNetEntityTypes[] = SONET_LISTS_NEW_POST_ENTITY;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_LISTS_NEW_POST_ENTITY] = array(
			'TITLE_LIST' => '',
			'TITLE_ENTITY' => ''
		);
	}

	public static function onFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents['lists_new_element'] = array(
			'ENTITIES' => array(
				SONET_LISTS_NEW_POST_ENTITY => array(),
			),
			'FORUM_COMMENT_ENTITY' => 'WF',
			'CLASS_FORMAT' => 'CListsLiveFeed',
			'METHOD_FORMAT' => 'formatListsElement',
			'HAS_CB' => 'Y',
			'FULL_SET' => array('lists_new_element', 'lists_new_element_comment'),
			'COMMENT_EVENT' => array(
				'MODULE_ID' => 'lists_new_element',
				'EVENT_ID' => 'lists_new_element_comment',
				'OPERATION' => 'view',
				'OPERATION_ADD' => 'log_rights',
				'ADD_CALLBACK' => array('CListsLiveFeed', 'addCommentLists'),
				'UPDATE_CALLBACK' => array('CSocNetLogTools', 'UpdateComment_Forum'),
				'DELETE_CALLBACK' => array('CSocNetLogTools', 'DeleteComment_Forum'),
				'CLASS_FORMAT' => 'CSocNetLogTools',
				'METHOD_FORMAT' => 'FormatComment_Forum'
			)
		);
	}

	public static function formatListsElement($fields, $params, $mail = false)
	{
		$element = array(
			'EVENT' => $fields,
			'CREATED_BY' => array(),
			'ENTITY' => array(),
			'EVENT_FORMATTED' => array(),
		);

		$userObject = CUser::getByID($fields['ENTITY_ID']);
		$user = $userObject->fetch();
		if ($user)
		{
			if(!$mail)
			{
				global $APPLICATION;
				$rights = array();
				$rightsQuery = CSocNetLogRights::getList(array(), array('LOG_ID' => $fields['ID']));
				while ($right = $rightsQuery->fetch())
				{
					$rights[] = $right['GROUP_CODE'];
				}

				if(defined('BX_COMP_MANAGED_CACHE'))
					$GLOBALS['CACHE_MANAGER']->registerTag('LISTS_ELEMENT_LIVE_FEED');

				$componentResult = $APPLICATION->includeComponent(
					'bitrix:bizproc.workflow.livefeed',
					'',
					Array(
						'WORKFLOW_ID' => $fields['MESSAGE'],
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);

				$siteDir = rtrim(SITE_DIR, '/');
				$url = CSocNetLogTools::formatEvent_GetURL($fields, true);
				$url = str_replace('#SITE_DIR#', $siteDir, $url);
				$url .= ''.$fields['ID'].'/';

				$element = array(
					'EVENT' => $fields,
					'EVENT_FORMATTED' => array(
						'TITLE_24' => '<a href="'.$fields['TITLE_TEMPLATE'].'" class="bx-lists-live-feed-title-link">'.$fields['TITLE'].'</a>',
						'MESSAGE' => $fields['TEXT_MESSAGE'].$componentResult['MESSAGE'],
						'IS_IMPORTANT' => false,
						'STYLE' => 'new-employee',
						'AVATAR_STYLE' => 'avatar-info',
						'DESTINATION' => CSocNetLogTools::formatDestinationFromRights($rights, array_merge($params, array('CREATED_BY' => $fields['USER_ID']))),
						'URL' => $url
					),
					'CREATED_BY' => CSocNetLogTools::formatEvent_GetCreatedBy($fields, $params, $mail),
					'AVATAR_SRC' => CSocNetLog::formatEvent_CreateAvatar($fields, $params),
					'CACHED_JS_PATH' => $componentResult['CACHED_JS_PATH'],
					'CACHED_CSS_PATH' => $componentResult['CACHED_CSS_PATH']
				);
				if ($params['MOBILE'] == 'Y')
				{
					$element['EVENT_FORMATTED']['TITLE_24'] = Loc::getMessage('LISTS_LF_MOBILE_DESTINATION');
					$element['EVENT_FORMATTED']['TITLE_24_2'] = $fields['TITLE'];
				}

				if (CModule::IncludeModule('bizproc'))
				{
					$workflowId = \CBPStateService::getWorkflowByIntegerId($element['EVENT']['SOURCE_ID']);
				}

				if ($workflowId)
				{
					$element['EVENT']['SOURCE_ID'] = $workflowId;
				}
			}
			return $element;
		}
	}

	public static function addCommentLists($fields)
	{
		if (!CModule::IncludeModule('forum') || !CModule::IncludeModule('bizproc'))
			return false;

		$ufFileId = array();
		$ufDocId = array();
		$fieldsMessage = array();
		$messageId = array();
		$error = array();
		$note = array();

		$sonetLogQuery = CSocNetLog::GetList(
			array(),
			array('ID' => $fields['LOG_ID']),
			false,
			false,
			array('ID', 'SOURCE_ID', 'SITE_ID', 'MESSAGE', 'USER_ID')
		);
		if($sonetLog = $sonetLogQuery->fetch())
		{
			self::setSocnetFollow(array($sonetLog['USER_ID']), $sonetLog['ID'], 'Y');
			$forumId = CBPHelper::getForumId();
			if($forumId)
			{
				$topicQuery = CForumTopic::GetList(array(), array('FORUM_ID' => $forumId, 'XML_ID' => 'WF_'.$sonetLog['MESSAGE']));
				if ($topicQuery && ($topic = $topicQuery->fetch()))
					$topicId = $topic['ID'];
				else
					$topicId = 0;

				$fieldsMessage = array(
					'POST_MESSAGE' => $fields['TEXT_MESSAGE'],
					'USE_SMILES' => 'Y',
					'PERMISSION_EXTERNAL' => 'Q',
					'PERMISSION' => 'Y',
					'APPROVED' => 'Y'
				);

				if ($topicId === 0)
				{
					$fieldsMessage['TITLE'] = 'WF_'.$sonetLog['MESSAGE'];
					$fieldsMessage['TOPIC_XML_ID'] = 'WF_'.$sonetLog['MESSAGE'];
				}

				$tmp = false;
				$GLOBALS['USER_FIELD_MANAGER']->editFormAddFields('SONET_COMMENT', $tmp);
				if (is_array($tmp))
				{
					if (array_key_exists('UF_SONET_COM_DOC', $tmp))
						$GLOBALS['UF_FORUM_MESSAGE_DOC'] = $tmp['UF_SONET_COM_DOC'];
					elseif (array_key_exists('UF_SONET_COM_FILE', $tmp))
					{
						$fieldsMessage['FILES'] = array();
						foreach($tmp['UF_SONET_COM_FILE'] as $fileId)
							$fieldsMessage['FILES'][] = array('FILE_ID' => $fileId);
					}
				}

				$messageId = ForumAddMessage(($topicId > 0 ? "REPLY" : "NEW"), $forumId, $topicId, 0, $fieldsMessage, $error, $note);

				if ($messageId > 0)
				{
					$addedMessageFilesQuery = CForumFiles::getList(array('ID' => 'ASC'), array('MESSAGE_ID' => $messageId));
					while ($addedMessageFiles = $addedMessageFilesQuery->fetch())
						$ufFileId[] = $addedMessageFiles['FILE_ID'];

					$ufDocId = $GLOBALS['USER_FIELD_MANAGER']->getUserFieldValue('FORUM_MESSAGE', 'UF_FORUM_MESSAGE_DOC', $messageId, LANGUAGE_ID);
				}
			}
		}
		if (!$messageId)
			$error = Loc::getMessage('LISTS_LF_ADD_COMMENT_SOURCE_ERROR');

		return array(
			'SOURCE_ID' => $messageId,
			'MESSAGE' => ($fieldsMessage ? $fieldsMessage['POST_MESSAGE'] : false),
			'RATING_TYPE_ID' => 'FORUM_POST',
			'RATING_ENTITY_ID' => $messageId,
			'ERROR' => $error,
			'NOTES' => $note,
			'UF' => array(
				'FILE' => $ufFileId,
				'DOC' => $ufDocId
			)
		);
	}

	protected static function getRights($users, $logId, $createdBy, $method)
	{
		$rights = array();
		$rights[] = 'SA'; //socnet admin

		if(!empty($users))
		{
			if($method == 'post')
				$users[] = $createdBy;

			foreach($users as $userId)
			{
				$rights[] = 'U'.$userId;
			}
		}

		if($method == 'post')
		{
			$rightsQuery = CSocNetLogRights::getList(array(), array('LOG_ID' => $logId));
			while($currentRights = $rightsQuery->fetch())
			{
				$rights[] = $currentRights['GROUP_CODE'];
			}
			$rights = array_unique($rights);
		}

		return $rights;
	}

	protected static function getUserIdForRight($rights)
	{
		$users = array();
		foreach($rights as $user)
		{
			if($user != 'SA')
			{
				$users[] = substr($user, 1);
			}
		}
		return $users;
	}

	protected static function setSocnetFollow($users, $logId, $type)
	{
		if($type == 'Y')
		{
			foreach($users as $userId)
			{
				CSocNetLogFollow::delete($userId, 'L'.$logId, false);
				CSocNetLogFollow::set($userId, 'L'.$logId, $type, ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID));

				//$logFollowObject = CSocNetLogFollow::getList(array('USER_ID' => $userId, 'REF_ID' => $logId), array('BY_WF'));
				//$logFollow = $logFollowObject->fetch();
				//if(!empty($logFollow) && $logFollow['BY_WF'] == 'Y')
				//{
				//	CSocNetLogFollow::delete($userId, 'L'.$logId, false);
				//	CSocNetLogFollow::set($userId, 'L'.$logId, $type, ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID), true);
				//}
				//elseif(empty($logFollow))
				//{
				//	CSocNetLogFollow::delete($userId, 'L'.$logId, false);
				//	CSocNetLogFollow::set($userId, 'L'.$logId, $type, ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID), true);
				//}
			}
		}
		else
		{
			foreach($users as $userId)
			{
				CSocNetLogFollow::set($userId, 'L'.$logId, $type, false, SITE_ID);

				//$logFollowObject = CSocNetLogFollow::getList(array('USER_ID' => $userId, 'REF_ID' => $logId), array('BY_WF'));
				//$logFollow = $logFollowObject->fetch();
				//if(!empty($logFollow) && $logFollow['BY_WF'] == 'Y')
				//{
				//	CSocNetLogFollow::set($userId, 'L'.$logId, $type, false, SITE_ID, true);
				//}
				//elseif(empty($logFollow))
				//{
				//	CSocNetLogFollow::set($userId, 'L'.$logId, $type, false, SITE_ID, true);
				//}
			}
		}

	}

	protected static function getSiteName()
	{
		return COption::getOptionString('main', 'site_name', '');
	}

	function BeforeIndexSocNet($bxSocNetSearch, $arFields)
	{
		static $bizprocForumId = false;

		if (!$bizprocForumId)
		{
			$bizprocForumId = intval(COption::GetOptionString('bizproc', 'forum_id'));
		}

		if(
			$arFields['ENTITY_TYPE_ID'] == 'FORUM_POST'
			&& intval($arFields['PARAM1']) == $bizprocForumId
			&& !empty($arFields['PARAM2'])
			&& !empty($bxSocNetSearch->_params["PATH_TO_WORKFLOW"])
			&& CModule::IncludeModule("forum")
		)
		{
			$arTopic = CForumTopic::GetByID($arFields['PARAM2']);

			if (
				!empty($arTopic)
				&& is_array($arTopic)
				&& !empty($arTopic["XML_ID"])
			)
			{
				if (preg_match('/^WF_([0-9a-f\.]+)/', $arTopic["XML_ID"], $match))
				{
					$workflowId = $match[1];
					$arState = CBPStateService::GetStateDocumentId($workflowId);

					if (
						$arState[0] == 'lists'
						&& $arState[1] == 'BizprocDocument'
						&& CModule::IncludeModule('iblock')
						&& (intval($arState[2]) > 0)
					)
					{
						$rsIBlockElement = CIBlockElement::GetList(
							array(),
							array(
								"ID" => intval($arState[2])
							),
							false,
							false,
							array("ID", "IBLOCK_ID")
						);

						if ($arIBlockElement = $rsIBlockElement->Fetch())
						{
							$listId = $arIBlockElement["IBLOCK_ID"];

							$arFields["URL"] = $bxSocNetSearch->Url(
								str_replace(
									array("#list_id#", "#workflow_id#"),
									array($listId, urlencode($workflowId)),
									$bxSocNetSearch->_params["PATH_TO_WORKFLOW"]
								),
								array(
									"MID" => $arFields["ENTITY_ID"]
								),
								"message".$arFields["ENTITY_ID"]
							);

							if (
								!empty($arFields["LID"])
								&& is_array($arFields["LID"])
							)
							{
								foreach ($arFields["LID"] as $siteId => $url)
								{
									$arFields["LID"][$siteId] = $arFields["URL"];
								}
							}
						}
					}
				}
			}
		}

		return $arFields;
	}

	/* Called from LiveFeed */
	public static function OnAfterSonetLogEntryAddComment($comment)
	{
		return;
		if ($comment["EVENT_ID"] != "lists_new_element_comment")
		{
			return;
		}

		$logQuery = CSocNetLog::getList(
			array(),
			array(
				"ID" => $comment["LOG_ID"],
				"EVENT_ID" => "lists_new_element"
			),
			false,
			false,
			array("SOURCE_ID", "URL", "TITLE", "USER_ID")
		);

		if (($log = $logQuery->fetch()) && (intval($log["SOURCE_ID"]) > 0))
		{
			CListsLiveFeed::notifyComment(
				array(
					"LOG_ID" => $comment["LOG_ID"],
					"TO_USER_ID" => $log["USER_ID"],
					"FROM_USER_ID" => $comment["USER_ID"],
					"URL" => $log["URL"],
					"TITLE" => $log["TITLE"]
				)
			);
		}
	}

	/* Called from popup */
	public static function OnForumCommentIMNotify($entityType, $entityId, $comment)
	{
		return;
		if ($entityType != "WF")
			return;

		$logQuery = CSocNetLog::getList(
			array(),
			array(
				"ID" => $comment["LOG_ID"],
				"EVENT_ID" => "lists_new_element"
			),
			false,
			false,
			array("ID", "SOURCE_ID", "URL", "TITLE", "USER_ID")
		);

		if (($log = $logQuery->fetch()) && (intval($log["SOURCE_ID"]) > 0))
		{
			CListsLiveFeed::notifyComment(
				array(
					"LOG_ID" => $log["ID"],
					"TO_USER_ID" => $log["USER_ID"],
					"FROM_USER_ID" => $comment["USER_ID"],
					"URL" => $log["URL"],
					"TITLE" => $log["TITLE"]
				)
			);
		}
	}

	public static function NotifyComment($comment)
	{
		return;
		if (!Loader::includeModule("im"))
			return;
		if($comment["TO_USER_ID"] == $comment["FROM_USER_ID"])
			return;

		$siteDir = rtrim(SITE_DIR, '/');
		$url = str_replace('#SITE_DIR#', $siteDir, $comment["URL"]);
		$url .= ''.$comment['LOG_ID'].'/';

		$messageAddComment = Loc::getMessage("LISTS_LF_COMMENT_MESSAGE_ADD",
			array("#PROCESS#" => '<a href="'.$url.'" class="bx-notifier-item-action">'.$comment["TITLE"].'</a>'));
		$userQuery = CUser::getList(
			$by = "id",
			$order = "asc",
			array("ID_EQUAL_EXACT" => intval($comment["USER_ID"])),
			array("FIELDS" => array("PERSONAL_GENDER"))
		);
		if ($user = $userQuery->fetch())
		{
			switch ($user["PERSONAL_GENDER"])
			{
				case "F":
				case "M":
				$messageAddComment = Loc::getMessage("LISTS_LF_COMMENT_MESSAGE_ADD" . '_' . $user["PERSONAL_GENDER"],
					array("#PROCESS#" => '<a href="'.$url.'" class="bx-notifier-item-action">'.$comment["TITLE"].'</a>'));
					break;
				default:
					break;
			}
		}

		$messageFields = array(
			"TO_USER_ID" => $comment["TO_USER_ID"],
			"FROM_USER_ID" => $comment["FROM_USER_ID"],
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "lists",
			"NOTIFY_EVENT" => "event_lists_comment_add",
			"NOTIFY_MESSAGE" => $messageAddComment
		);

		CIMNotify::Add($messageFields);
	}
}