<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


class CTaskIntranetTools
{
	/**
	 * if ($arAllowedDepartments === null) => all departments headed by user will be used
	 */
	public static function getImmediateEmployees($userId, $arAllowedDepartments = null)
	{
		if ( ! CModule::IncludeModule('intranet') )
			return (false);

		$arDepartmentHeads = array();

		$arQueueDepartmentsEmployees = array();	// IDs of departments where we need employees

		// Departments where given user is head
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$arManagedDepartments = CIntranetUtils::GetSubordinateDepartments($userId);

		if (is_array($arAllowedDepartments))
		{
			$arManagedDepartments = array_intersect(
				$arManagedDepartments,
				$arAllowedDepartments
			);
		}

		if (is_array($arManagedDepartments))
		{
			foreach ($arManagedDepartments as $departmentId)
			{
				$arQueueDepartmentsEmployees[] = $departmentId;

				$result = self::searchImmediateEmployeesInSubDepartments($departmentId);

				$arDepartmentHeads = array_merge(
					$arDepartmentHeads,
					$result['arDepartmentHeads']
				);

				$arQueueDepartmentsEmployees = array_merge(
					$arQueueDepartmentsEmployees,
					$result['arQueueDepartmentsEmployees']
				);
			}
		}

		$arEmployees = $arDepartmentHeads;

		if ( ! empty($arQueueDepartmentsEmployees) )
		{
			$arEmployees = array_merge(
				$arEmployees,
				self::getDepartmentsUsersIds($arQueueDepartmentsEmployees)
			);
		}

		if ( ! empty($arEmployees) )
		{
			$arEmployees = array_unique(array_filter($arEmployees));

			// Remove itself
			$curUserIndex = array_search($userId, $arEmployees);
			if ($curUserIndex !== false)
				unset($arEmployees[$curUserIndex]);
		}

		return ($arEmployees);
	}


	private static function searchImmediateEmployeesInSubDepartments($departmentId)
	{
		$arDepartmentHeads           = array();
		$arQueueDepartmentsEmployees = array();	// IDs of departments where we need employees

		$arSubDepartments = CIntranetUtils::getSubDepartments($departmentId);
		if (is_array($arSubDepartments))
		{
			foreach ($arSubDepartments as $subDepId)
			{
				$headUserId = CIntranetUtils::GetDepartmentManagerID($subDepId);

				if ($headUserId)
					$arDepartmentHeads[] = $headUserId;
				else
				{
					$arQueueDepartmentsEmployees[] = $subDepId;

					$result = self::searchImmediateEmployeesInSubDepartments($subDepId);

					$arDepartmentHeads = array_merge(
						$arDepartmentHeads,
						$result['arDepartmentHeads']
					);

					$arQueueDepartmentsEmployees = array_merge(
						$arQueueDepartmentsEmployees,
						$result['arQueueDepartmentsEmployees']
					);
				}
			}
		}

		return (array(
			'arDepartmentHeads'           => $arDepartmentHeads,
			'arQueueDepartmentsEmployees' => $arQueueDepartmentsEmployees
		));
	}


	private static function getDepartmentsUsersIds($arDepartmentsIds)
	{
		$arUsersIds = array();
		
		$dbRes = self::getDepartmentsUsers($arDepartmentsIds, $arFields = array('ID'));

		if ($dbRes !== false)
		{
			while ($arRes = $dbRes->fetch())
				$arUsersIds[] = $arRes['ID'];
		}

		return ($arUsersIds);
	}


	public static function getDepartmentsUsers($arDepartmentsIds, $arFields = array('ID'))
	{
		$dbRes = false;
		$arDepartmentsIds = array_unique(array_filter($arDepartmentsIds));

		if ( ! empty($arDepartmentsIds) )
		{
			$dbRes = CUser::GetList(
				$by = 'ID', 
				$order = 'ASC',
				array('ACTIVE' => 'Y', 'UF_DEPARTMENT' => $arDepartmentsIds),
				array(
					'SELECT' => array('UF_DEPARTMENT'),
					'FIELDS' => $arFields
				)
			);
		}

		return ($dbRes);
	}
}
