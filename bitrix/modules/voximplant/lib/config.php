<?php
namespace Bitrix\Voximplant;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ConfigTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SEARCH_ID string(255) optional
 * <li> PHONE_NAME string(255) optional
 * <li> CRM bool optional default 'Y'
 * <li> CRM_RULE string(50) optional
 * <li> CRM_CREATE string(50) optional
 * <li> QUEUE_TIME int optional
 * <li> DIRECT_CODE bool optional default 'N'
 * <li> DIRECT_CODE_RULE string(50) optional
 * <li> RECORDING bool optional default 'Y'
 * <li> RECORDING_TIME int optional
 * <li> VOICEMAIL bool optional default 'Y'
 * <li> NO_ANSWER_RULE string(50) optional
 * <li> MELODY_LANG string(2) optional
 * <li> MELODY_WELCOME int optional
 * <li> MELODY_WELCOME_ENABLE bool optional default 'Y'
 * <li> MELODY_VOICEMAIL int optional
 * <li> MELODY_WAIT int optional
 * <li> MELODY_HOLD int optional
 * </ul>
 *
 * @package Bitrix\Voximplant
 **/

class ConfigTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_voximplant_config';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_ID_FIELD'),
			),
			'PORTAL_MODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePortalMode'),
				'title' => 'PORTAL_MODE',
				'default_value' => 'RENT',
			),
			'SEARCH_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSearchId'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_SEARCH_ID_FIELD'),
			),
			'PHONE_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePhoneName'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_PHONE_NAME_FIELD'),
			),
			'CRM' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_FIELD'),
				'default_value' => 'Y',
			),
			'CRM_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRuleField'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_RULE_FIELD'),
				'default_value' => 'queue',
			),
			'CRM_CREATE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRuleField'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_CREATE_FIELD'),
				'default_value' => 'lead',
			),
			'CRM_FORWARD' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_CRM_FORWARD_FIELD'),
				'default_value' => 'Y',
			),
			'QUEUE_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_QUEUE_TIME_FIELD'),
				'default_value' => '3',
			),
			'DIRECT_CODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DIRECT_CODE_FIELD'),
				'default_value' => 'Y',
			),
			'DIRECT_CODE_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRuleField'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DIRECT_CODE_RULE_FIELD'),
				'default_value' => 'voicemail',
			),
			'RECORDING' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_RECORDING_FIELD'),
				'default_value' => 'N',
			),
			'RECORDING_TIME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_RECORDING_TIME_FIELD'),
			),
			'NO_ANSWER_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateRuleField'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_NO_ANSWER_RULE_FIELD'),
				'default_value' => 'voicemail',
			),
			'FORWARD_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateForwardNumber'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_FORWARD_NUMBER_FIELD'),
			),
			'TIMEMAN' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_TIMEMAN_FIELD'),
				'default_value' => 'N',
			),
			'VOICEMAIL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_VOICEMAIL_FIELD'),
				'default_value' => 'Y',
			),
			'MELODY_LANG' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMelodyLang'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_LANG_FIELD'),
				'default_value' => 'EN',
			),
			'MELODY_WELCOME' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WELCOME_FIELD'),
				'default_value' => '0',
			),
			'MELODY_WELCOME_ENABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WELCOME_ENABLE_FIELD'),
				'default_value' => 'Y',
			),
			'MELODY_WAIT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_WAIT_FIELD'),
				'default_value' => '0',
			),
			'MELODY_HOLD' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_HOLD_FIELD'),
				'default_value' => '0',
			),
			'DATE_DELETE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_DATE_DELETE_FIELD'),
			),
			'TO_DELETE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_TO_DELETE_FIELD'),
				'default_value' => 'N',
			),
			'MELODY_VOICEMAIL' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_MELODY_VOICEMAIL_FIELD'),
				'default_value' => '0',
			),
			'WORKTIME_ENABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_ENABLE_FIELD'),
				'default_value' => 'N',
			),
			'WORKTIME_FROM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeFrom'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_FROM_FIELD'),
				'default_value' => '9',
			),
			'WORKTIME_TO' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeTo'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_TO_FIELD'),
				'default_value' => '18.30',
			),
			'WORKTIME_TIMEZONE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeTimezone'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_TIMEZONE_FIELD'),
			),
			'WORKTIME_HOLIDAYS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeHolidays'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_HOLIDAYS_FIELD'),
			),
			'WORKTIME_DAYOFF' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeDayoff'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_FIELD'),
			),
			'WORKTIME_DAYOFF_RULE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeDayoffRule'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_RULE_FIELD'),
				'default_value' => 'voicemail',
			),
			'WORKTIME_DAYOFF_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateWorktimeDayoffNumber'),
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_NUMBER_FIELD'),
			),
			'WORKTIME_DAYOFF_MELODY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('INCOMING_CONFIG_ENTITY_WORKTIME_DAYOFF_MELODY_FIELD'),
				'default_value' => '0',
			)
		);
	}

	public static function validateSearchId()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateRuleField()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateForwardNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
	public static function validatePortalMode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validatePhoneName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateMelodyLang()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}
	public static function validateWorktimeFrom()
	{
		return array(
			new Entity\Validator\Length(null, 5),
		);
	}
	public static function validateWorktimeTo()
	{
		return array(
			new Entity\Validator\Length(null, 5),
		);
	}
	public static function validateWorktimeTimezone()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateWorktimeHolidays()
	{
		return array(
			new Entity\Validator\Length(null, 2000),
		);
	}
	public static function validateWorktimeDayoff()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
	public static function validateWorktimeDayoffRule()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateWorktimeDayoffNumber()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}
}