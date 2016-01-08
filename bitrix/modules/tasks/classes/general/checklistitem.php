<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 * 
 * @global $USER_FIELD_MANAGER CUserTypeManager
 * @global $APPLICATION CMain
 */

/*

Usage example:

CModule::IncludeModule('tasks');

try
{
	$oTaskItem = new CTaskItem(75, 1);
	
	$oListItem = CTaskCheckListItem::add($oTaskItem, array('TITLE' => 'test'));
	//var _dump($oListItem->getId());
	
	//var _dump($oListItem->isComplete());
	$oListItem->complete();
	//var _dump($oListItem->isComplete());
	$oListItem->renew();
	//var _dump($oListItem->isComplete());
	$oListItem->delete();
	//var _dump($oListItem->isComplete());
}
catch (Exception $e)
{
	echo 'Got exception: ' . $e->getCode() . '; ' . $e->getFile() . ':' . $e->getLine();
}

Expected output:
int(15)
string(4) "test"
string(2) "75"
bool(false)
bool(true)
bool(false) 
Got exception: 8; /var/www/sites/RAM/cpb24.bxram.bsr/html/bitrix/modules/tasks/classes/general/checklistitem.php:282
*/

final class CTaskCheckListItem extends CTaskSubItemAbstract
{
	const ACTION_ADD    = 0x01;
	const ACTION_MODIFY = 0x02;
	const ACTION_REMOVE = 0x03;
	const ACTION_TOGGLE = 0x04;


	/**
	 * @param CTaskItemInterface $oTaskItem (of class CTaskItem,checklist item will be added to this task)
	 * @param array $arFields with mandatory element TITLE (string).
	 * 
	 * @throws TasksException with code TasksException::TE_WRONG_ARGUMENTS
	 * 
	 * @return CTaskCheckListItem
	 */
	public static function add(CTaskItemInterface $oTaskItem, $arFields)
	{
		global $DB;

		if ( ! self::checkFieldsForAdd($arFields) )
			throw new TasksException('Bad arguments for '.__CLASS__.'::add()', TasksException::TE_WRONG_ARGUMENTS);

		$arFields = self::normalizeFieldsDataForAdd($arFields);

		CTaskAssert::assert(
			isset($arFields['TITLE'])
			&& is_string($arFields['TITLE'])
			&& ($arFields['TITLE'] !== '')
			&& ($oTaskItem instanceof CTaskItemInterface)
		);

		if ( ! $oTaskItem->isActionAllowed(CTaskItem::ACTION_CHECKLIST_ADD_ITEMS) )
			throw new TasksException(__CLASS__.'::add() is not allowed to call', TasksException::TE_ACTION_NOT_ALLOWED);

		$taskId          = (int) $oTaskItem->getId();
		$executiveUserId = (int) $oTaskItem->getExecutiveUserId();
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime     = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()+CTimeZone::GetOffset());

		$arFieldsToDb = array(
			'TITLE'       =>  $arFields['TITLE'],
			'TASK_ID'     =>  $taskId,
			'CREATED_BY'  =>  $executiveUserId,
			'IS_COMPLETE' => 'N'
		);

		if (isset($arFields['SORT_INDEX']))
			$arFieldsToDb['SORT_INDEX'] = (int) $arFields['SORT_INDEX'];
		else
		{
			$rc = $DB->Query(
				"SELECT MAX(SORT_INDEX) AS MAX_SORT_INDEX
				FROM b_tasks_checklist_items 
				WHERE TASK_ID = " . (int) $taskId,
				$bIgnoreErrors = true
			);

			if ( ! $rc )
				throw new TasksException('SQL error', TasksException::TE_SQL_ERROR);

			if (($arSortIndex = $rc->fetch()) && isset($arSortIndex['MAX_SORT_INDEX']))
				$arFieldsToDb['SORT_INDEX'] = (int) $arSortIndex['MAX_SORT_INDEX'] + 1;
			else
				$arFieldsToDb['SORT_INDEX'] = 0;
		}

		if (isset($arFields['IS_COMPLETE']))
			$arFieldsToDb['IS_COMPLETE'] = $arFields['IS_COMPLETE'];

		$id = $DB->add('b_tasks_checklist_items', $arFieldsToDb, $arCLOBFields = array(), 'tasks', $ignore_errors = true);

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = $executiveUserId;

		// changes log
		$arLogFields = array(
			'TASK_ID'      =>  $taskId,
			'USER_ID'      =>  $occurAsUserId,
			'CREATED_DATE' =>  $curDatetime,
			'FIELD'        => 'CHECKLIST_ITEM_CREATE',
			'FROM_VALUE'   => '',
			'TO_VALUE'     =>  $arFields['TITLE']
		);

		if ( ! ($id > 0) )
		{
			CTaskAssert::logError('[0xbb7986ff] ');

			throw new TasksException(
				'Action failed',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		$log = new CTaskLog();
		$log->Add($arLogFields);

		if ($arFieldsToDb['IS_COMPLETE'] === 'Y')
		{
			// changes log
			$arLogFields = array(
				'TASK_ID'      => (int) $taskId,
				'USER_ID'      => $occurAsUserId,
				'CREATED_DATE' => $curDatetime,
				'FIELD'        => 'CHECKLIST_ITEM_CHECK',
				'FROM_VALUE'   => $arFields['TITLE'],
				'TO_VALUE'     => $arFields['TITLE']
			);

			$log->Add($arLogFields);
		}

		return (new self($oTaskItem, (int) $id));
	}


	public function isActionAllowed($actionId)
	{
		$isActionAllowed = false;
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int) $actionId;

		$isAdmin = CTasksTools::IsAdmin($this->executiveUserId)
			|| CTasksTools::IsPortalB24Admin($this->executiveUserId);

		if ($actionId === self::ACTION_ADD)
			$isActionAllowed = $this->oTaskItem->isActionAllowed(CTaskItem::ACTION_CHECKLIST_ADD_ITEMS);
		elseif (
			in_array(
				(int) $actionId,
				array(self::ACTION_MODIFY, self::ACTION_REMOVE, self::ACTION_TOGGLE),
				true
			)
		)
		{
			$arItemData = $this->getData($bEscape = false);

			if ($isAdmin || ($arItemData['CREATED_BY'] == $this->executiveUserId))
				$isActionAllowed = true;
			elseif ($actionId == self::ACTION_TOGGLE)
			{
				// This can do director, responsible and accomplices
				if (
					$this->oTaskItem->isUserRole(
						CTaskItem::ROLE_DIRECTOR
						| CTaskItem::ROLE_RESPONSIBLE
						| CTaskItem::ROLE_ACCOMPLICE
					)
				)
				{
					$isActionAllowed = true;
				}
			}
			elseif (($actionId == self::ACTION_MODIFY) || ($actionId == self::ACTION_REMOVE))
			{
				// This can do director or user who can edit task
				if (
					$this->oTaskItem->isUserRole(CTaskItem::ROLE_DIRECTOR)
					|| $this->oTaskItem->isActionAllowed(CTaskItem::ACTION_EDIT)
				)
				{
					$isActionAllowed = true;
				}
			}
		}

		return ($isActionAllowed);
	}


	final protected function fetchListFromDb($taskData, $arOrder = array('SORT_INDEX' => 'asc', 'ID' => 'asc'))
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);
		
		if(!isset($arOrder))
			$arOrder = array('SORT_INDEX' => 'asc', 'ID' => 'asc');

		global $DB;

		if(is_array($arOrder) && !empty($arOrder))
		{
			if ( ! self::checkFieldsForSort($arOrder) )
				throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

			$sqlOrder = array();
			foreach($arOrder as $fld => $way)
				$sqlOrder[] = $fld.' '.$way;
			$sqlOrder = 'ORDER BY '.implode(', ', $sqlOrder);
		}
		else
			$sqlOrder = '';

		$rc = $DB->Query(
			"SELECT ID, CREATED_BY, TASK_ID, TITLE, IS_COMPLETE, SORT_INDEX, " . $DB->DateToCharFunction("TOGGLED_DATE", "FULL") . " AS TOGGLED_DATE , TOGGLED_BY
				FROM b_tasks_checklist_items 
				WHERE TASK_ID = " . (int) $taskData['ID'] . ' '.
				$sqlOrder,
			$bIgnoreErrors = true
		);

		if ( ! $rc )
			throw new \Bitrix\Main\SystemException();

		$arItemsData = array();
		while ($arItemData = $rc->fetch())
			$arItemsData[] = $arItemData;
		
		return (array($arItemsData, $rc));
	}


	public function getTitle()
	{
		$arItemData = $this->getData();

		return ($arItemData['TITLE']);
	}


	public function getTaskId()
	{
		$arItemData = $this->getData();

		return ($arItemData['TASK_ID']);
	}


	/**
	 * @return bool true if complete, false otherwise
	 */
	public function isComplete()
	{
		$arItemData = $this->getData();
		$isComplete = ($arItemData['IS_COMPLETE'] === 'Y');

		return ($isComplete);
	}


	public function complete()
	{
		$this->update(array('IS_COMPLETE' => 'Y'));
	}


	public function renew()
	{
		$this->update(array('IS_COMPLETE' => 'N'));
	}


	public function update($arFields)
	{
		global $DB;

		if ( ! self::checkFieldsForUpdate($arFields) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$arFields = self::normalizeFieldsDataForUpdate($arFields);

		CTaskAssert::assert(is_array($arFields));

		// Nothing to do?
		if (empty($arFields))
			return;

		if ( ! $this->isActionAllowed(self::ACTION_MODIFY) )
		{
			if ((count($arFields) == 1) && array_key_exists('IS_COMPLETE', $arFields))
			{
				; // It's all OK, this field can be edited
			}
			else
				throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()+CTimeZone::GetOffset());

		$arCurrentData = $this->getData();
		$curTitle = $arCurrentData['~TITLE'];
		$curIsComplete = $arCurrentData['IS_COMPLETE'];

		if (isset($arFields['IS_COMPLETE']))
			$newIsComplete = $arFields['IS_COMPLETE'];
		else
			$newIsComplete = $curIsComplete;

		if (isset($arFields['TITLE']))
			$newTitle = $arFields['TITLE'];
		else
			$newTitle = $curTitle;

		if (isset($arFields['IS_COMPLETE']))
		{
			$arFields['TOGGLED_BY']   = $this->executiveUserId;
			$arFields['TOGGLED_DATE'] = $curDatetime;
		}

		$strUpdate = $DB->PrepareUpdate('b_tasks_checklist_items', $arFields, 'tasks');
		$strSql = "UPDATE b_tasks_checklist_items SET " . $strUpdate . " WHERE ID=" . $this->itemId;
		$result = $DB->QueryBind($strSql, $arBinds = array(), $ignore_errors = true);

		// Reset cache
		$this->resetCache();

		if ( ! $result )
		{
			throw new TasksException(
				'',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		if ($curTitle !== $newTitle)
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = (int) $this->executiveUserId;

			// changes log
			$arLogFields = array(
				'TASK_ID'      =>  (int) $this->taskId,
				'USER_ID'      =>  $occurAsUserId,
				'CREATED_DATE' =>  $curDatetime,
				'FIELD'        => 'CHECKLIST_ITEM_RENAME',
				'FROM_VALUE'   =>  $curTitle,
				'TO_VALUE'     =>  $newTitle
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}

		if ($curIsComplete !== $newIsComplete)
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = (int) $this->executiveUserId;

			// changes log
			$arLogFields = array(
				'TASK_ID'      => (int) $this->taskId,
				'USER_ID'      => $occurAsUserId,
				'CREATED_DATE' => $curDatetime,
				'FIELD'        => (($newIsComplete === 'Y') ? 'CHECKLIST_ITEM_CHECK' : 'CHECKLIST_ITEM_UNCHECK'),
				'FROM_VALUE'   => $curTitle,
				'TO_VALUE'     => $newTitle
			);

			$log = new CTaskLog();
			$log->Add($arLogFields);
		}
	}


	public function delete()
	{
		global $DB;

		$taskId = (int) $this->oTaskItem->getId();
		$executiveUserId = (int) $this->oTaskItem->getExecutiveUserId();
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$curDatetime     = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time()+CTimeZone::GetOffset());

		if ( ! $this->isActionAllowed(self::ACTION_REMOVE) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		$arCurrentData = $this->getData();

		$rc = $DB->Query(
			"DELETE FROM b_tasks_checklist_items 
			WHERE ID = " . $this->itemId . " AND TASK_ID = " . $taskId,
			$bIgnoreErrors = true
		);

		// Reset cache
		$this->resetCache();

		if ($rc === false)
		{
			throw new TasksException(
				'',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = $executiveUserId;

		// changes log
		$arLogFields = array(
			'TASK_ID'      =>  $taskId,
			'USER_ID'      =>  $occurAsUserId,
			'CREATED_DATE' =>  $curDatetime,
			'FIELD'        => 'CHECKLIST_ITEM_REMOVE',
			'FROM_VALUE'   =>  $arCurrentData['~TITLE'],
			'TO_VALUE'     => ''
		);

		$log = new CTaskLog();
		$log->Add($arLogFields);
	}


	/**
	 * Reorder item in checklist to position after some given item.
	 */
	public function moveAfterItem($itemId)
	{
		$this->moveItem($this->getId(), $itemId);
	}


	private function moveItem($selectedItemId, $insertAfterItemId)
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT ID, SORT_INDEX
			FROM b_tasks_checklist_items 
			WHERE TASK_ID = " . (int) $this->taskId . "
			ORDER BY SORT_INDEX ASC, ID ASC
			",
			$bIgnoreErrors = true
		);

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_SQL_ERROR);

		$arItems = array($selectedItemId => 0);	// by default to first position
		$prevItemId = 0;
		$sortIndex = 1;
		while($arItem = $rc->fetch())
		{
			if ($insertAfterItemId == $prevItemId)
				$arItems[$selectedItemId] = $sortIndex++;

			if ($arItem['ID'] != $selectedItemId)
				$arItems[$arItem['ID']] = $sortIndex++;

			$prevItemId = $arItem['ID'];
		}

		if ($insertAfterItemId == $prevItemId)
			$arItems[$selectedItemId] = $sortIndex;

		if ( ! empty($arItems) )
		{
			$sqlUpdate = "UPDATE b_tasks_checklist_items
				SET SORT_INDEX = CASE ID\n";
			
			foreach ($arItems as $id => $sortIndex)
				$sqlUpdate .= "WHEN $id THEN $sortIndex\n";

			$sqlUpdate .= "END\n"
				. "WHERE ID IN (" . implode(', ', array_keys($arItems)) . ")";

			$DB->Query($sqlUpdate);
		}
	}


	/**
	 * Removes all checklist's items for given task.
	 * WARNING: This function doesn't check rights!
	 * 
	 * @param integer $taskId
	 * @throws TasksException
	 */
	public static function deleteByTaskId($taskId)
	{
		global $DB;

		CTaskAssert::assert(
			CTaskAssert::isLaxIntegers($taskId)
			&& ($taskId > 0)
		);

		$rc = $DB->Query("DELETE FROM b_tasks_checklist_items WHERE TASK_ID = $taskId", true);
		if ($rc === false)
		{
			throw new TasksException(
				'',
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}
	}


	final protected function fetchDataFromDb($taskId, $itemId)
	{
		global $DB;

		$rc = $DB->Query(
			"SELECT ID, CREATED_BY, TASK_ID, TITLE, IS_COMPLETE, SORT_INDEX
				FROM b_tasks_checklist_items 
				WHERE ID = " . (int) $itemId . " AND TASK_ID = " . (int) $taskId,
			$bIgnoreErrors = true
		);

		if ($rc && ($arItemData = $rc->fetch()))
			return ($arItemData);
		else
			throw new \Bitrix\Main\SystemException();
	}


	private static function normalizeFieldsDataForAdd($arFields)
	{
		if (array_key_exists('IS_COMPLETE', $arFields))
		{
			if ($arFields['IS_COMPLETE'] === true || $arFields['IS_COMPLETE'] === 'Y' || intval($arFields['IS_COMPLETE']) > 0)
				$arFields['IS_COMPLETE'] = 'Y';
			else
				$arFields['IS_COMPLETE'] = 'N';
		}

		if (isset($arFields['SORT_INDEX']))
			$arFields['SORT_INDEX'] = (int) $arFields['SORT_INDEX'];

		return ($arFields);
	}


	private static function normalizeFieldsDataForUpdate($arFields)
	{
		if (isset($arFields['IS_COMPLETE']))
		{
			if ($arFields['IS_COMPLETE'] === true || $arFields['IS_COMPLETE'] === 'Y' || intval($arFields['IS_COMPLETE']) > 0)
				$arFields['IS_COMPLETE'] = 'Y';
			else
				$arFields['IS_COMPLETE'] = 'N';
		}

		if (isset($arFields['SORT_INDEX']))
			$arFields['SORT_INDEX'] = (int) $arFields['SORT_INDEX'];

		return ($arFields);
	}

	public static function checkFieldsForSort($arOrder)
	{
		global $APPLICATION;

		$bErrorsFound = false;
		$arErrorsMsgs = array();

		$allowedSortFields = array(
			'SORT_INDEX', 'ID', 'TITLE', 'IS_COMPLETE', 'CREATED_BY', 'TASK_ID', 'TOGGLED_BY', 'TOGGLED_DATE'
		);
		foreach($arOrder as $fld => $way)
		{
			if(!in_array($fld, $allowedSortFields))
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_UNKNOWN_FIELD'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_UNKNOWN_FIELD'
				);
			}

			$way = ToLower($way);
			if($way != 'desc' && $way != 'asc')
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_SORT_DIRECTION'
				);
			}
		}

		if ($bErrorsFound)
		{
			$e = new CAdminException($arErrorsMsgs);
			$APPLICATION->ThrowException($e);
		}

		$isAllRight = ! $bErrorsFound;

		return ($isAllRight);
	}

	public static function checkFieldsForAdd($arFields)
	{
		return (self::checkFields($arFields, $checkForAdd = true));
	}


	public static function checkFieldsForUpdate($arFields)
	{
		return (self::checkFields($arFields, $checkForAdd = false));
	}


	private static function checkFields($arFields, $checkForAdd)
	{
		global $APPLICATION;

		$bErrorsFound = false;
		$arErrorsMsgs = array();

		if ($checkForAdd)
		{
			// TITLE must be set during add
			if ( ! array_key_exists('TITLE', $arFields) )
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_BAD_TITLE'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_TITLE'
				);
			}
		}

		$allowedFields = array('SORT_INDEX', 'TITLE', 'IS_COMPLETE');
		foreach (array_keys($arFields) as $fieldName)
		{
			if(!in_array($fieldName, $allowedFields))
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_UNKNOWN_FIELD'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_UNKNOWN_FIELD'
				);
			}
		}

		// TITLE must be an non-empty string
		if (array_key_exists('TITLE', $arFields))
		{
			if ( ! (
				is_string($arFields['TITLE'])
				&& ($arFields['TITLE'] !== '')
			))
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_BAD_TITLE'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_TITLE'
				);
			}
		}

		// IS_COMLETE can be 'Y' / 'N' / true / false
		if (array_key_exists('IS_COMLETE', $arFields))
		{
			if ( ! (
				($arFields['IS_COMLETE'] === 'Y')
				|| ($arFields['IS_COMLETE'] === 'N')
				|| ($arFields['IS_COMLETE'] === true)
				|| ($arFields['IS_COMLETE'] === false)
			))
			{
				$bErrorsFound = true;
				$arErrorsMsgs[] = array(
					'text' =>  GetMessage('TASKS_CHECKLISTITEM_BAD_COMPLETE_FLAG'),
					'id'   => 'ERROR_TASKS_CHECKLISTITEM_BAD_COMPLETE_FLAG'
				);
			}
		}

		if ($bErrorsFound)
		{
			$e = new CAdminException($arErrorsMsgs);
			$APPLICATION->ThrowException($e);
		}

		$isAllRight = ! $bErrorsFound;

		return ($isAllRight);
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
		$argsParsed = CTaskRestService::_parseRestParams('ctaskchecklistitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$taskId    = $argsParsed[0];
				$arFields  = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				$oItem     = self::add($oTaskItem, $arFields);

				$returnValue = $oItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				list($oCheckListItems, $rsData) = self::fetchList($oTaskItem, $order);

				$returnValue = array();

				foreach ($oCheckListItems as $oCheckListItem)
					$returnValue[] = $oCheckListItem->getData(false);
			}
			else
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$obElapsed  = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
				$returnValue = $obElapsed->getData();
			else
				$returnValue = call_user_func_array(array($obElapsed, $methodName), $argsParsed);
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
		$arWritableKeys = array('TITLE', 'SORT_INDEX', 'IS_COMPLETE');
		$arSortableKeys = array_merge(array('ID', 'CREATED_BY', 'TOGGLED_BY', 'TOGGLED_DATE'), $arWritableKeys);
		$arDateKeys = array('TOGGLED_DATE');
		$arReadableKeys = array_merge(
			array('TASK_ID'),
			$arDateKeys,
			$arSortableKeys,
			$arWritableKeys
		);

		return(array(
			'Manifest version' => '1.0',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'checklistitem',
			'REST: writable checklistitem data fields'   =>  $arWritableKeys,
			'REST: readable checklistitem data fields'   =>  $arReadableKeys,
			'REST: sortable checklistitem data fields'   =>  $arSortableKeys,
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
				'complete' => array(
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
				'renew' => array(
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
				'moveafteritem' => array(
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
							'description' => 'afterItemId',
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
