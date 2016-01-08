<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class StatisticTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ACCOUNT_ID int mandatory
 * <li> APPLICATION_ID int mandatory
 * <li> APPLICATION_NAME string(80) mandatory
 * <li> PORTAL_USER_ID int mandatory
 * <li> PORTAL_NUMBER string(20)
 * <li> PHONE_NUMBER string(20) mandatory
 * <li> INCOMING string(50) mandatory
 * <li> CALL_ID string(255) optional
 * <li> CALL_LOG string(255) optional
 * <li> CALL_DIRECTION string(255) optional
 * <li> CALL_DURATION int mandatory
 * <li> CALL_START_DATE datetime mandatory
 * <li> CALL_STATUS int optional
 * <li> CALL_RECORD_ID int optional
 * <li> CALL_WEBDAV_ID int optional
 * <li> COST double optional default 0.0000
 * <li> COST_CURRENCY string(50) optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class StatisticTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_statistic';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('STATISTIC_ENTITY_ID_FIELD'),
			),
			'ACCOUNT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('STATISTIC_ENTITY_ACCOUNT_ID_FIELD'),
			),
			'APPLICATION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('STATISTIC_ENTITY_APPLICATION_ID_FIELD'),
			),
			'APPLICATION_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateApplicationName'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_APPLICATION_NAME_FIELD'),
			),
			'PORTAL_USER_ID' => array(
				'data_type' => 'integer',
				'required' => false,
				'title' => Loc::getMessage('STATISTIC_ENTITY_PORTAL_USER_ID_FIELD'),
			),
			'PORTAL_NUMBER' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validatePhoneNumber'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_PORTAL_NUMBER_FIELD'),
			),
			'PHONE_NUMBER' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validatePhoneNumber'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_PHONE_NUMBER_FIELD'),
			),
			'INCOMING' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateIncoming'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_INCOMING_FIELD'),
			),
			'CALL_ID' => array(
				'data_type' => 'string',
				'required' => false,
				'validation' => array(__CLASS__, 'validateCallIdDirection'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_ID_FIELD'),
			),
			'CALL_LOG' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallLog'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_LOG_FIELD'),
			),
			'CALL_DIRECTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallDirection'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_DIRECTION_FIELD'),
			),
			'CALL_DURATION' => array(
				'data_type' => 'integer',
				'required' => false,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_DURATION_FIELD'),
			),
			'CALL_START_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_START_DATE_FIELD'),
			),
			'CALL_STATUS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_STATUS_FIELD'),
			),
			'CALL_RECORD_ID' => array(
				'data_type' => 'integer',
				'required' => false,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_RECORD_ID_FIELD'),
			),
			'CALL_WEBDAV_ID' => array(
				'data_type' => 'integer',
				'required' => false,
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_WEBDAV_ID_FIELD'),
			),
			'COST' => array(
				'data_type' => 'float',
				'title' => Loc::getMessage('STATISTIC_ENTITY_COST_FIELD'),
			),
			'COST_CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCostCurrency'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_COST_CURRENCY_FIELD'),
			),
			'CALL_FAILED_CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallFailed'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_FAILED_CODE_FIELD'),
			),
			'CALL_FAILED_REASON' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCallFailed'),
				'title' => Loc::getMessage('STATISTIC_ENTITY_CALL_FAILED_REASON_FIELD'),
			),
		);
	}
	public static function validateApplicationName()
	{
		return array(
			new Entity\Validator\Length(null, 80),
		);
	}
	public static function validateCallIdDirection()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validatePhoneNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
	public static function validateIncoming()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateCallLog()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCallDirection()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCallFailed()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCostCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
}?>