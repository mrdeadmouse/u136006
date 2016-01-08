<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CallTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int optional
 * <li> SEARCH_ID string(255) mandatory
 * <li> CALL_ID string(255) mandatory
 * <li> CALLER_ID string(255) optional
 * <li> STATUS string(50) optional
 * <li> ACCESS_URL string(255) mandatory
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class CallTable extends Entity\DataManager
{
	const STATUS_CONNECTING = 'connecting';
	const STATUS_CONNECTED = 'connected';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_call';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('CALL_ENTITY_ID_FIELD'),
			),
			'CONFIG_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_CONFIG_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_USER_ID_FIELD'),
			),
			'TRANSFER_USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('CALL_ENTITY_TRANSFER_USER_ID_FIELD'),
			),
			'CALL_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCallId'),
				'title' => Loc::getMessage('CALL_ENTITY_CALL_ID_FIELD'),
			),
			'CALLER_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallerId'),
				'title' => Loc::getMessage('CALL_ENTITY_CALLER_ID_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateStatus'),
				'title' => Loc::getMessage('CALL_ENTITY_STATUS_FIELD'),
			),
			'CRM' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => '',
			),
			'CRM_LEAD' => array(
				'data_type' => 'integer',
				'title' => '',
			),
			'ACCESS_URL' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateAccessUrl'),
				'title' => Loc::getMessage('CALL_ENTITY_ACCESS_URL_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('CALL_ENTITY_DATE_CREATE_FIELD'),
			),
		);
	}
	public static function validateCallId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCallerId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateStatus()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateAccessUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}