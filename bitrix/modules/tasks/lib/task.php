<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Tasks;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TaskTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks';
	}


	public static function getMap()
	{
		global $DB, $DBType, $USER;

		$userId = (int) $USER->getId();

		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION_TR' => array(
				'data_type' => 'string',
				'expression' => array(
					self::getDbTruncTextFunction($DBType, '%s'),
					'DESCRIPTION'
				)
			),
			'DESCRIPTION_IN_BBCODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'PRIORITY' => array(
				'data_type' => 'string'
			),
			'STATUS' => array(
				'data_type' => 'string'
			),
			'STATUS_PSEUDO' => array(
				'data_type' => 'string',
				'expression' => array(
					"CASE
					WHEN
						%s < ".$DB->currentTimeFunction()." AND %s != '4' AND %s != '5' AND (%s != '7' OR %s != ".$userId.")
					THEN
						'-1'
					ELSE
						%s
					END",
					'DEADLINE', 'STATUS', 'STATUS', 'STATUS', 'RESPONSIBLE_ID', 'STATUS'
				)
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer'
			),
			'RESPONSIBLE' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.RESPONSIBLE_ID' => 'ref.ID')
			),
			'DATE_START' => array(
				'data_type' => 'datetime'
			),
			'START_DATE_PLAN' => array(
				'data_type' => 'datetime'
			),
			'END_DATE_PLAN' => array(
				'data_type' => 'datetime'
			),
			'DURATION_PLAN' => array(
				'data_type' => 'integer'
			),
			'DURATION_TYPE' => array(
				'data_type' => 'string'
			),
			'DEADLINE' => array(
				'data_type' => 'datetime'
			),
			'CREATED_BY' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.CREATED_BY' => 'ref.ID'
				)
			),
			'CREATED_DATE' => array(
				'data_type' => 'datetime'
			),
			'CHANGED_BY' => array(
				'data_type' => 'integer'
			),
			'CHANGED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CHANGED_BY' => 'ref.ID')
			),
			'CHANGED_DATE' => array(
				'data_type' => 'datetime'
			),
			'STATUS_CHANGED_BY' => array(
				'data_type' => 'integer'
			),
			'STATUS_CHANGED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.STATUS_CHANGED_BY' => 'ref.ID')
			),
			'STATUS_CHANGED_DATE' => array(
				'data_type' => 'datetime'
			),
			'CLOSED_BY' => array(
				'data_type' => 'integer'
			),
			'CLOSED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CLOSED_BY' => 'ref.ID')
			),
			'CLOSED_DATE' => array(
				'data_type' => 'datetime'
			),
			'PARENT_ID' => array(
				'data_type' => 'integer'
			),
			'PARENT' => array(
				'data_type' => 'Task',
				'reference' => array('=this.PARENT_ID' => 'ref.ID')
			),
			'SITE_ID' => array(
				'data_type' => 'integer'
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID')
			),
			'ADD_IN_REPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'GROUP_ID' => array(
				'data_type' => 'integer'
			),
			'GROUP' => array(
				'data_type' => 'Bitrix\Socialnetwork\Workgroup',
				'reference' => array('=this.GROUP_ID' => 'ref.ID')
			),
			'MARK' => array(
				'data_type' => 'string'
			),
			'ALLOW_TIME_TRACKING' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'TIME_ESTIMATE' => array(
				'data_type' => 'integer'
			),
			'TIME_SPENT_IN_LOGS' => array(
				'data_type' => 'integer',
				'expression' => array(
					'(SELECT  SUM(SECONDS) FROM b_tasks_elapsed_time WHERE TASK_ID = %s)',
					'ID'
				)
			),
			'DURATION' => array(
				'data_type' => 'integer',
				'expression' => array(
					'ROUND((SELECT  SUM(SECONDS)/60 FROM b_tasks_elapsed_time WHERE TASK_ID = %s),0)',
					'ID'
				)
			),
			// DURATION_PLAN_MINUTES field - only for old user reports, which use it
			'DURATION_PLAN_MINUTES' => array(
				'data_type' => 'integer',
				'expression' => array(
					'%s * (CASE WHEN %s = \'days\' THEN 8 ELSE 1 END) * 60',
					'DURATION_PLAN', 'DURATION_TYPE'
				)
			),
			'DURATION_PLAN_HOURS' => array(
				'data_type' => 'integer',
				'expression' => array(
					'(%s * (CASE WHEN %s = \'days\' THEN 24 ELSE 1 END))',
					'DURATION_PLAN', 'DURATION_TYPE'
				)
			),
			'IS_OVERDUE' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IS NOT NULL AND (%s < %s OR (%s IS NULL AND %s < '.$DB->currentTimeFunction().')) THEN 1 ELSE 0 END',
					'DEADLINE', 'DEADLINE', 'CLOSED_DATE', 'CLOSED_DATE', 'DEADLINE'
				),
				'values' => array(0, 1)
			),
			'IS_OVERDUE_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_OVERDUE', 'ID'
				)
			),
			'IS_MARKED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IN(\'P\', \'N\') THEN 1 ELSE 0 END',
					'MARK'
				),
				'values' => array(0, 1)
			),
			'IS_MARKED_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_MARKED', 'ID'
				)
			),
			'IS_EFFECTIVE' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = \'P\' THEN 1 ELSE 0 END',
					'MARK'
				),
				'values' => array(0, 1)
			),
			'IS_EFFECTIVE_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_EFFECTIVE', 'ID'
				)
			),
			'IS_RUNNING' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IN (3,4) THEN 1 ELSE 0 END',
					'STATUS'
			),
				'values' => array(0, 1)
			),
			'ZOMBIE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			)
		);

		return $fieldsMap;
	}


	private static function getDbTruncTextFunction($dbtype, $param)
	{
		switch (ToLower($dbtype))
		{
			case 'mysql':
				$result = "SUBSTR(".$param.", 1, 1024)";
			break;

			case 'mssql':
				$result = "SUBSTRING(".$param.", 1, 1024)";
			break;

			case 'oracle':
				$result = "TO_CHAR(SUBSTR(".$param.", 1, 1024))";
			break;

			default:
				$result = $param;
			break;
		}

		return ($result);
	}
}
