<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SEARCH_ID string(255) optional
 * <li> CONFIG_ID int mandatory
 * <li> USER_ID int mandatory
 * <li> STATUS string(50) optional
 * <li> LAST_ACTIVITY_DATE datetime optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class QueueTable extends Entity\DataManager
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_voximplant_queue';
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
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_ID_FIELD'),
			),
			'SEARCH_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSearchId'),
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_SEARCH_ID_FIELD'),
			),
			'CONFIG_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_CONFIG_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_USER_ID_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateStatus'),
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_STATUS_FIELD'),
			),
			'LAST_ACTIVITY_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('INCOMING_QUEUE_ENTITY_LAST_ACTIVITY_DATE_FIELD'),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
		);
	}
	/**
	 * Returns validators for SEARCH_ID field.
	 *
	 * @return array
	 */
	public static function validateSearchId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
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
			new Entity\Validator\Length(null, 50),
		);
	}
}