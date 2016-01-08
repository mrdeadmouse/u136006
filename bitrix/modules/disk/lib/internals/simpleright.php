<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class SimpleRightTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> OBJECT_ID int mandatory
 * <li> ACCESS_CODE string(50) optional
 * </ul>
 *
 * @package Bitrix\Disk
 * @internal
 **/

final class SimpleRightTable extends DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_disk_simple_right';
	}

	/**
	 * Returns entity map definition
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'OBJECT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'ACCESS_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAccessCode'),
			),
		);
	}

	/**
	 * Validates access code field.
	 * @return array
	 */
	public static function validateAccessCode()
	{
		return array(
			new Entity\Validator\Length(1, 50),
		);
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 */
	public static function deleteBatch(array $filter)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		if(!empty($filter['OBJECT_ID']))
		{
			$objectId = (int)$filter['OBJECT_ID'];
			$connection->queryExecute("DELETE FROM {$tableName} WHERE OBJECT_ID = {$objectId}");
		}
	}

	/**
	 * Fills descendants simple rights by simple rights of object.
	 * @internal
	 * @param int $objectId Id of object.
	 */
	public static function fillDescendants($objectId)
	{
		$tableName = static::getTableName();
		$pathTableName = ObjectPathTable::getTableName();
		$connection = Application::getConnection();

		$objectId = (int)$objectId;
		$connection->queryExecute("
			INSERT INTO {$tableName} (OBJECT_ID, ACCESS_CODE)
			SELECT path.OBJECT_ID, sright.ACCESS_CODE FROM {$pathTableName} path
				INNER JOIN {$tableName} sright ON sright.OBJECT_ID = path.PARENT_ID
			WHERE path.PARENT_ID = {$objectId}
		");
	}
}
