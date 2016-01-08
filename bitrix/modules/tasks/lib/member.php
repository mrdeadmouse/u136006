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

class MemberTable extends Entity\DataManager
{
	public static function getMap()
	{
		$fieldsMap = array(
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'TASK' => array(
				'data_type' => 'Task',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
			'TASK_FOLLOWED' => array(
				'data_type' => 'Task',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', 'U')
				)
			),
			'TASK_COWORKED' => array(
				'data_type' => 'Task',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', 'A')
				)
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TYPE' => array(
				'data_type' => 'string',
				'primary' => true
			)
		);

		return $fieldsMap;
	}
}
