<?php
namespace Bitrix\Tasks;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TemplateTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TASK_ID int optional
 * <li> TITLE string(255) optional
 * <li> DESCRIPTION string optional
 * <li> DESCRIPTION_IN_BBCODE bool optional default 'N'
 * <li> PRIORITY string(1) mandatory default 1
 * <li> STATUS string(1) mandatory default 1
 * <li> RESPONSIBLE_ID int optional
 * <li> DEADLINE_AFTER int optional
 * <li> REPLICATE bool optional default 'N'
 * <li> REPLICATE_PARAMS string optional
 * <li> CREATED_BY int optional
 * <li> XML_ID string(50) optional
 * <li> ALLOW_CHANGE_DEADLINE bool optional default 'N'
 * <li> ALLOW_TIME_TRACKING bool optional default 'N'
 * <li> TASK_CONTROL bool optional default 'N'
 * <li> ADD_IN_REPORT bool optional default 'N'
 * <li> GROUP_ID int optional
 * <li> PARENT_ID int optional
 * <li> MULTITASK bool optional default 'N'
 * <li> SITE_ID string(2) mandatory
 * <li> ACCOMPLICES string optional
 * <li> AUDITORS string optional
 * <li> RESPONSIBLES string optional
 * <li> FILES string optional
 * <li> TAGS string optional
 * <li> DEPENDS_ON string optional
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

class TemplateTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TEMPLATE_ENTITY_ID_FIELD'),
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_TASK_ID_FIELD'),
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_TITLE_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_DESCRIPTION_FIELD'),
			),
			'DESCRIPTION_IN_BBCODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_DESCRIPTION_IN_BBCODE_FIELD'),
			),
			'PRIORITY' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validatePriority'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_PRIORITY_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateStatus'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_STATUS_FIELD'),
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_RESPONSIBLE_ID_FIELD'),
			),
			'DEADLINE_AFTER' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_DEADLINE_AFTER_FIELD'),
			),
			'REPLICATE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_REPLICATE_FIELD'),
			),
			'REPLICATE_PARAMS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_REPLICATE_PARAMS_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_CREATED_BY_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_XML_ID_FIELD'),
			),
			'ALLOW_CHANGE_DEADLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_ALLOW_CHANGE_DEADLINE_FIELD'),
			),
			'ALLOW_TIME_TRACKING' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_ALLOW_TIME_TRACKING_FIELD'),
			),
			'TASK_CONTROL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_TASK_CONTROL_FIELD'),
			),
			'ADD_IN_REPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_ADD_IN_REPORT_FIELD'),
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_GROUP_ID_FIELD'),
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_PARENT_ID_FIELD'),
			),
			'MULTITASK' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_MULTITASK_FIELD'),
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('TEMPLATE_ENTITY_SITE_ID_FIELD'),
			),
			'ACCOMPLICES' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_ACCOMPLICES_FIELD'),
			),
			'AUDITORS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_AUDITORS_FIELD'),
			),
			'RESPONSIBLES' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_RESPONSIBLES_FIELD'),
			),
			'FILES' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_FILES_FIELD'),
			),
			'TAGS' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_TAGS_FIELD'),
			),
			'DEPENDS_ON' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TEMPLATE_ENTITY_DEPENDS_ON_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 */
	public static function validateTitle()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PRIORITY field.
	 *
	 * @return array
	 */
	public static function validatePriority()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for STATUS field.
	 *
	 * @return array
	 */
	public static function validateStatus()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for SITE_ID field.
	 *
	 * @return array
	 */
	public static function validateSiteId()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
}