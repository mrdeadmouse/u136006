<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

abstract class CTasksMobileTasksListNsAbstract
{
	const listModeCategoryName = 'tasks:mobile:mobile.tasks.list';
	const listModeParamName    = 'tasks.list.groupModeEnabled';


	public static function Init($userId, $groupListModeSwitcher)
	{
		static::GetUserId ($userId);	// Set user id

		// Change group list mode, if need
		if ($groupListModeSwitcher === 'Y')
			static::EnableGroupMode();
		elseif ($groupListModeSwitcher === 'N')
			static::DisableGroupMode();
	}


	public static function GetUserId ($userId = null)
	{
		static $uid = false;

		if (is_int($userId) && ($userId > 0))
			$uid = $userId;

		return ($uid);
	}


	/**
	 * @return boolean true - if group list mode, false - if plain list mode
	 */
	public static function IsGroupListMode()
	{
		// Get User Id
		$uid = static::GetUserId();

		if ($uid < 1)
		{
			if (isset($_REQUEST['GROUP_LIST_MODE']))
			{
				if ($_REQUEST['GROUP_LIST_MODE'] === 'Y')
					return (true);
				else
					return (false);
			}
			else
				return (true);
		}

		$rc = CUserOptions::GetOption(
			static::listModeCategoryName, 
			static::listModeParamName, 
			'N',
			$uid
		);

		if ($rc === 'Y')
			return (true);
		else
			return (false);
	}


	public static function EnableGroupMode()
	{
		static::SwitchGroupMode('Y');
	}


	public static function DisableGroupMode()
	{
		static::SwitchGroupMode('N');
	}


	private static function SwitchGroupMode ($yn)
	{
		// Get User Id
		$uid = static::GetUserId(false);

		if ($uid === false)
			return;

		if ($yn === 'Y')
			$value = 'Y';
		else
			$value = 'N';

		CUserOptions::SetOption(
			static::listModeCategoryName, 
			static::listModeParamName, 
			$value, 
			$bCommon=false, 
			$uid
		);
	}
}
