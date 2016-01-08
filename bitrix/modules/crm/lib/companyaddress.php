<?php
namespace Bitrix\Crm;
use Bitrix\Main;

class CompanyAddress extends EntityAddress
{
	private static $fieldMaps = array();
	/**
	* @return \CCrmEntityListBuilder
	*/
	protected static function createEntityListBuilder()
	{
		return \CCrmCompany::CreateListBuilder();
	}

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		if(!isset(self::$fieldMaps[$typeID]))
		{
			if($typeID === EntityAddress::Registered)
			{
				self::$fieldMaps[$typeID] = array(
					'ADDRESS_1' => 'REG_ADDRESS',
					'ADDRESS_2' => 'REG_ADDRESS_2',
					'CITY' => 'REG_ADDRESS_CITY',
					'POSTAL_CODE' => 'REG_ADDRESS_POSTAL_CODE',
					'REGION' => 'REG_ADDRESS_REGION',
					'PROVINCE' => 'REG_ADDRESS_PROVINCE',
					'COUNTRY' => 'REG_ADDRESS_COUNTRY',
					'COUNTRY_CODE' => 'REG_ADDRESS_COUNTRY_CODE'
				);
			}
			else
			{
				self::$fieldMaps[$typeID] = array(
					'ADDRESS_1' => 'ADDRESS',
					'ADDRESS_2' => 'ADDRESS_2',
					'CITY' => 'ADDRESS_CITY',
					'POSTAL_CODE' => 'ADDRESS_POSTAL_CODE',
					'REGION' => 'ADDRESS_REGION',
					'PROVINCE' => 'ADDRESS_PROVINCE',
					'COUNTRY' => 'ADDRESS_COUNTRY',
					'COUNTRY_CODE' => 'ADDRESS_COUNTRY_CODE'
				);
			}
		}

		return self::$fieldMaps[$typeID];
	}
}