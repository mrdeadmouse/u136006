<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;
class TaskManager
{
	private static $items = array();

	/**
	* @return \CTaskItem
	*/
	public static function getTaskItem($taskID, $userID)
	{
		if(!Loader::includeModule('tasks'))
		{
			return null;
		}

		if($taskID <= 0 || $userID <= 0)
		{
			return null;
		}

		if(!isset(self::$items[$userID]))
		{
			self::$items[$userID] = array();
		}

		if(isset(self::$items[$userID]) && isset(self::$items[$userID][$taskID]))
		{
			return self::$items[$userID][$taskID];
		}

		if(!isset(self::$items[$userID]))
		{
			self::$items[$userID] = array();
		}

		return (self::$items[$userID][$taskID] = new \CTaskItem($taskID, $userID));
	}
	public static function checkUpdatePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			return $taskItem->isActionAllowed(\CTaskItem::ACTION_EDIT);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
	public static function checkDeletePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			return $taskItem->isActionAllowed(\CTaskItem::ACTION_REMOVE);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
	public static function checkCompletePermission($taskID, $userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_int($taskID))
		{
			$taskID = (int)$taskID;
		}

		$taskItem = self::getTaskItem($taskID, $userID);
		if($taskItem === null)
		{
			return false;
		}

		try
		{
			return $taskItem->isActionAllowed(\CTaskItem::ACTION_COMPLETE);
		}
		catch(\TasksException $e)
		{
			return false;
		}
	}
}