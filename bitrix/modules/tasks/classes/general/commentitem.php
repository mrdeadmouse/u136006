<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


final class CTaskCommentItem extends CTaskSubItemAbstract
{
	const ACTION_COMMENT_ADD    = 0x01;
	const ACTION_COMMENT_MODIFY = 0x02;
	const ACTION_COMMENT_REMOVE = 0x03;

	/**
	 * @param CTaskItemInterface $oTaskItem
	 * @param array $arFields with mandatory elements POST_MESSAGE
	 * @throws TasksException
	 * @return CTaskElapsedItem
	 */
	public static function add(CTaskItemInterface $oTaskItem, $arFields)
	{
		CTaskAssert::assert(
			is_array($arFields) && !empty($arFields)
		);

		// if you reached this point (e.g. created an instance of a task class, you have an access to the task and therefore can comment it)
		//if ( ! $this->isActionAllowed(CTaskItem::ACTION_COMMENT_ADD) )
		//	throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		$obComment = new CTaskComments();
		$messageId = $obComment->add($oTaskItem->getId(), $oTaskItem->getExecutiveUserId(), $arFields['POST_MESSAGE']);

		if ($messageId === false)
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);

		return (new self($oTaskItem, (int) $messageId));
	}


	public function delete()
	{
		if ( ! $this->isActionAllowed(self::ACTION_COMMENT_REMOVE) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		$taskData = $this->oTaskItem->getData();

		if(intval($taskData['FORUM_TOPIC_ID']))
		{
			/** @noinspection PhpDeprecationInspection */
			$rc = CTaskComments::Remove($this->taskId, $this->itemId, $this->executiveUserId, array('FORUM_TOPIC_ID' => $taskData['FORUM_TOPIC_ID']));
		}

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
	}


	public function update($arFields)
	{
		if ( ! $this->isActionAllowed(self::ACTION_COMMENT_MODIFY) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		// Nothing to do?
		if (empty($arFields))
			return;

		$o  = new CTaskComments();
		$rc = $o->update($this->taskId, $this->itemId, $this->executiveUserId, $arFields);

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);

		return;
	}


	public function isActionAllowed($actionId)
	{
		$isActionAllowed = false;
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int) $actionId;

		if($actionId === self::ACTION_COMMENT_ADD)
			return true; // you can view the task (if you reached this point, you obviously can)
		elseif (($actionId === self::ACTION_COMMENT_MODIFY) || ($actionId === self::ACTION_COMMENT_REMOVE))
		{
			$taskData = $this->oTaskItem->getData();
			if(!intval($taskData['FORUM_TOPIC_ID'])) // task even doesnt have a forum
				$isActionAllowed = false;
			else
			{
				if($actionId === self::ACTION_COMMENT_REMOVE)
				{
					$isActionAllowed = CTaskComments::CanRemoveComment(
						$this->oTaskItem->getId(),
						$this->itemId,
						$this->executiveUserId,
						array(
							'FORUM_TOPIC_ID' => $taskData['FORUM_TOPIC_ID']
						)
					);
				}
				elseif($actionId === self::ACTION_COMMENT_MODIFY)
				{
					$isActionAllowed = CTaskComments::CanUpdateComment(
						$this->oTaskItem->getId(),
						$this->itemId,
						$this->executiveUserId,
						array(
							'FORUM_TOPIC_ID' => $taskData['FORUM_TOPIC_ID']
						)
					);
				}

			}
		}

		return ($isActionAllowed);
	}

	final protected function fetchListFromDb($taskData, $arOrder = array(), $arFilter = array())
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		$arItemsData = array();
		$rsData = null;

		if($topicId = intval($taskData['FORUM_TOPIC_ID']))
		{
			CTaskAssert::assert(CModule::IncludeModule('forum'));

			if(!is_array($arFilter))
				$arFilter = array();

			$arFilter['TOPIC_ID'] = $topicId;

			$rsData = CForumMessage::GetList($arOrder, $arFilter/*, false, 0, array("SELECT" => array("UF_FORUM_MESSAGE_DOC"))*/);

			if ( ! is_object($rsData) )
				throw new Exception();

			while ($arData = $rsData->fetch())
			{
				if($arData['POST_MESSAGE'] == 'TASK_'.$taskData['ID']) // typically the first one is a non-interesting system message, so skip it
					continue;

				$arItemsData[] = $arData;
			}
		}

		return (array($arItemsData, $rsData));
	}

	final protected function fetchDataFromDb($taskId, $itemId)
	{
		CTaskAssert::assertLaxIntegers($taskId, $itemId);
		CTaskAssert::assert(CModule::IncludeModule('forum'));

		/** @noinspection PhpDeprecationInspection */
		$rsData = CForumMessage::GetList(
				array(),
			array('ID' => (int) $itemId)
		);

		if (is_object($rsData) && ($arData = $rsData->fetch()))
			return ($arData);
		else
			throw new Exception();
	}

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
		$argsParsed = CTaskRestService::_parseRestParams('ctaskcommentitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$taskId    = $argsParsed[0];
				$arFields  = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);	// taskId in $argsParsed[0]
				$oItem     = self::add($oTaskItem, $arFields);

				$returnValue = $oItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = $argsParsed[1];
				$filter = $argsParsed[2];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				list($oCommentItems, $rsData) = self::fetchList($oTaskItem, $order, $filter);

				$returnValue = array();

				foreach ($oCommentItems as $oCommentItem)
					$returnValue[] = $oCommentItem->getData(false);
			}
			else
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$obComment  = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
				$returnValue = $obComment->getData();
			else
				$returnValue = call_user_func_array(array($obComment, $methodName), $argsParsed);
		}

		return (array($returnValue, null));
	}


	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @access private
	 */
	public static function getManifest()
	{
		$arWritableKeys = array('POST_MESSAGE');
		$arDateKeys = array('POST_DATE');
		$arSortableKeys = array('ID', 'AUTHOR_ID', 'AUTHOR_NAME', 'AUTHOR_EMAIL', /*'EDITOR_ID',*/ 'POST_DATE');
		$arReadableKeys = array_merge(
			array('POST_MESSAGE_HTML'),
			$arSortableKeys,
			$arDateKeys,
			$arWritableKeys
		);
		$arFiltrableKeys = array('ID', 'AUTHOR_ID', 'AUTHOR_NAME', 'POST_DATE');

		return(array(
			'Manifest version' => '1.1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'commentitem',
			'REST: writable commentitem data fields'   =>  $arWritableKeys,
			'REST: readable commentitem data fields'   =>  $arReadableKeys,
			'REST: sortable commentitem data fields'   =>  $arSortableKeys,
			'REST: filterable commentitem data fields' =>  $arFiltrableKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'getlist' => array(
					'staticMethod'         =>  true,
					'mandatoryParamsCount' =>  1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' => $arFiltrableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							)
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'collectionInReturnValue'  => true
				),
				'get' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					),
					'allowedKeysInReturnValue' => $arReadableKeys
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
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'update' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'delete' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						)
					)
				)
			)
		));
	}
}
