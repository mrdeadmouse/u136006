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

class ElapsedTimeTable extends Entity\DataManager
{
	/**
	 * @return array
	 */
	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CREATED_DATE' => array(
				'data_type' => 'date'
			),
			'USER_ID' => array(
				'data_type' => 'integer'
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK_ID' => array(
				'data_type' => 'integer'
			),
			'TASK' => array(
				'data_type' => 'Task',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
			'MINUTES' => array(
				'data_type' => 'integer'
			),
			'SECONDS' => array(
				'data_type' => 'integer'
			),
			'SOURCE' => array(
				'data_type' => 'integer'
			),
			'COMMENT_TEXT' => array(
				'data_type' => 'string'
			)
		);

		return $fieldsMap;
	}
}
