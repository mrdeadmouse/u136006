<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


IncludeModuleLangFile(__FILE__);

class CTasksNotifySchema
{
	public function __construct()
	{
	}


	public static function OnGetNotifySchema()
	{
		return array(
			'tasks' => array(
				'comment' => array(
					'NAME'      => GetMessage('TASKS_NS_COMMENT'),
				),
				'reminder' => array(
					'NAME'      => GetMessage('TASKS_NS_REMINDER'),
				),
				'manage' => array(
					'NAME'      => GetMessage('TASKS_NS_MANAGE'),
				),
				'task_assigned' => array(
					'NAME'      => GetMessage('TASKS_NS_TASK_ASSIGNED'),
				),
			),
		);
	}
}


class CTasksPullSchema
{
	public static function OnGetDependentModule()
	{
		return array(
			'MODULE_ID' => 'tasks',
			'USE'       => array('PUBLIC_SECTION')
		);
	}
}
