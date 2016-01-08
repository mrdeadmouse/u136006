<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\DB\OracleConnection;

abstract class DataManager extends \Bitrix\Main\Entity\DataManager
{
	const MAX_LENGTH_BATCH_MYSQL_QUERY = 2048;

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return bool
	 */
	public static function deleteByFilter(array $filter)
	{
		if (!$filter)
		{
			throw new ArgumentNullException('filter');
		}

		$result = static::getList(array(
			'select' => array('ID'),
			'filter' => $filter,
		));
		while($row = $result->fetch())
		{
			if(!empty($row['ID']))
			{
				$resultDelete = static::delete($row['ID']);
				if(!$resultDelete->isSuccess())
				{
					return false;
				}
			}
		}
		//todo? Return new DbResult with lists of deleted object?
		return true;
	}

	/**
	 * Adds rows to table.
	 * @param array $items Items.
	 * @internal
	 */
	protected static function insertBatch(array $items)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$query = $prefix = '';
		if($connection instanceof MysqlCommonConnection)
		{
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);

				$query .= ($query? ', ' : ' ') . '(' . $values . ')';
				if(strlen($query) > self::MAX_LENGTH_BATCH_MYSQL_QUERY)
				{
					$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
					$query = '';
				}
			}
			unset($item);

			if($query && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) VALUES {$query}");
			}
		}
		elseif($connection instanceof MssqlConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values}";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
		elseif($connection instanceof OracleConnection)
		{
			$valueData = array();
			foreach ($items as $item)
			{
				list($prefix, $values) = $sqlHelper->prepareInsert($tableName, $item);
				$valueData[] = "SELECT {$values} FROM dual";
			}
			unset($item);

			$valuesSql = implode(' UNION ALL ', $valueData);
			if($valuesSql && $prefix)
			{
				$connection->queryExecute("INSERT INTO {$tableName} ({$prefix}) $valuesSql");
			}
		}
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @internal
	 */
	protected static function deleteBatch(array $filter)
	{
	}

	/**
	 * Updates rows by filter (simple format).
	 * Filter support only column = value. Only =.
	 * @param array $fields Fields.
	 * @param array $filter Filter.
	 */
	protected static function updateBatch(array $fields, array $filter)
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($tableName, $fields);

		$whereItems = array();
		foreach ($filter as $k => $v)
		{
			$whereItems[] = $sqlHelper->prepareAssignment($tableName, $k, $v);
		}
		$where = implode(' AND ', $whereItems);

		$sql = 'UPDATE ' . $tableName . ' SET ' . $update[0] . ' WHERE ' . $where;
		$connection->queryExecute($sql, $update[1]);
	}
}