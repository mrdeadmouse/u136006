<?php
namespace Bitrix\Tasks\Template;

use Bitrix\Main\Entity;

use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\DB;

//use Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class DependencyTable
 * 
 * Fields:
 * <ul>
 * <li> TEMPLATE_ID int mandatory
 * <li> PARENT_TEMPLATE_ID int mandatory
 * </ul>
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @package Bitrix\Tasks
 * @access private
 **/

class DependencyTable extends DB\Tree
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template_dep';
	}

	public static function getIDColumnName()
	{
		return 'TEMPLATE_ID';
	}

	public static function getPARENTIDColumnName()
	{
		return 'PARENT_TEMPLATE_ID';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array_merge(array(
			'TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				//'title' => Loc::getMessage('TEMPLATE_DEP_ENTITY_TEMPLATE_ID_FIELD'),
			),
			'PARENT_TEMPLATE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				//'title' => Loc::getMessage('TEMPLATE_DEP_ENTITY_PARENT_TEMPLATE_ID_FIELD'),
			),

			// template data
			'TEMPLATE' => array(
				'data_type' => '\Bitrix\Tasks\Template',
				'reference' => array(
					'=this.TEMPLATE_ID' => 'ref.ID'
				),
				'join_type' => 'inner'
			),

			// parent template data
			'PARENT_TEMPLATE' => array(
				'data_type' => '\Bitrix\Tasks\Template',
				'reference' => array(
					'=this.PARENT_TEMPLATE_ID' => 'ref.ID'
				),
				'join_type' => 'inner'
			),
		), parent::getMap('\Bitrix\Tasks\Template\Dependency'));
	}
}