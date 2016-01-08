<?php
namespace Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Entity;

class AddressTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_addr';
	}
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ADDRESS_1' => array('data_type' => 'string'),
			'ADDRESS_2' => array('data_type' => 'string'),
			'CITY' => array('data_type' => 'string'),
			'POSTAL_CODE' => array('data_type' => 'string'),
			'REGION' => array('data_type' => 'string'),
			'PROVINCE' => array('data_type' => 'string'),
			'COUNTRY' => array('data_type' => 'string'),
			'COUNTRY_CODE' => array('data_type' => 'string'),
		);
	}
	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityTypeID = isset($data['ENTITY_TYPE_ID']) ? (int)$data['ENTITY_TYPE_ID'] : 0;
		$entityID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		$typeID = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : 0;

		$address1 = isset($data['ADDRESS_1']) && $data['ADDRESS_1'] !== '' ? "'{$sqlHelper->forSql($data['ADDRESS_1'], 255)}'" : 'NULL';
		$address2 = isset($data['ADDRESS_2']) && $data['ADDRESS_2'] !== '' ? "'{$sqlHelper->forSql($data['ADDRESS_2'], 255)}'" : 'NULL';
		$city = isset($data['CITY']) && $data['CITY'] !== '' ? "'{$sqlHelper->forSql($data['CITY'], 128)}'" : 'NULL';
		$postalCode = isset($data['POSTAL_CODE']) && $data['POSTAL_CODE'] !== '' ? "'{$sqlHelper->forSql($data['POSTAL_CODE'], 16)}'" : 'NULL';
		$region = isset($data['REGION']) && $data['REGION'] !== '' ? "'{$sqlHelper->forSql($data['REGION'], 128)}'" : 'NULL';
		$province = isset($data['PROVINCE']) && $data['PROVINCE'] !== '' ? "'{$sqlHelper->forSql($data['PROVINCE'], 128)}'" : 'NULL';
		$country = isset($data['COUNTRY']) && $data['COUNTRY'] !== '' ? "'{$sqlHelper->forSql($data['COUNTRY'], 128)}'" : 'NULL';
		$countryCode = isset($data['COUNTRY_CODE']) && $data['COUNTRY_CODE'] !== '' ? "'{$sqlHelper->forSql($data['COUNTRY_CODE'], 100)}'" : 'NULL';

		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$connection->queryExecute(
				"INSERT INTO b_crm_addr(ENTITY_TYPE_ID, ENTITY_ID, TYPE_ID, ADDRESS_1, ADDRESS_2, CITY, POSTAL_CODE, REGION, PROVINCE, COUNTRY, COUNTRY_CODE)
					VALUES({$entityTypeID}, {$entityID}, {$typeID}, {$address1}, {$address2}, {$city}, {$postalCode}, {$region}, {$province}, {$country}, {$countryCode})
					ON DUPLICATE KEY UPDATE ADDRESS_1 = {$address1}, ADDRESS_2 = {$address2}, CITY = {$city}, POSTAL_CODE = {$postalCode}, REGION = {$region}, PROVINCE = {$province}, COUNTRY = {$country}, COUNTRY_CODE = {$countryCode}"
			);
		}

		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$dbResult = $connection->query(
				"SELECT 'X' FROM b_crm_addr WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}"
			);

			if(is_array($dbResult->fetch()))
			{
				$connection->queryExecute(
					"UPDATE b_crm_addr SET ADDRESS_1 = {$address1}, ADDRESS_2 = {$address2}, CITY = {$city}, POSTAL_CODE = {$postalCode}, REGION = {$region}, PROVINCE = {$province}, COUNTRY = {$country}, COUNTRY_CODE = {$countryCode}
						WHERE ENTITY_TYPE_ID = {$entityTypeID} AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}"
				);
			}
			else
			{
				$connection->queryExecute(
					"INSERT INTO b_crm_addr(ENTITY_TYPE_ID, ENTITY_ID, TYPE_ID, ADDRESS_1, ADDRESS_2, CITY, POSTAL_CODE, REGION, PROVINCE, COUNTRY, COUNTRY_CODE)
						VALUES({$entityTypeID}, {$entityID}, {$typeID}, {$address1}, {$address2}, {$city}, {$postalCode}, {$region}, {$province}, {$country}, {$countryCode})"
				);
			}
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$connection->queryExecute("MERGE INTO b_crm_addr USING (SELECT {$entityTypeID} ENTITY_TYPE_ID, {$entityID} ENTITY_ID, {$typeID} TYPE_ID FROM dual)
				source ON
				(
					source.ENTITY_TYPE_ID = b_crm_addr.ENTITY_TYPE_ID
					AND source.ENTITY_ID = b_crm_addr.ENTITY_ID
					AND source.TYPE_ID = b_crm_addr.TYPE_ID
				)
				WHEN MATCHED THEN
					UPDATE SET b_crm_addr.ADDRESS_1 = {$address1},
						b_crm_addr.ADDRESS_2 = {$address2},
						b_crm_addr.CITY = {$city},
						b_crm_addr.POSTAL_CODE = {$postalCode},
						b_crm_addr.REGION = {$region},
						b_crm_addr.PROVINCE = {$province},
						b_crm_addr.COUNTRY = {$country},
						b_crm_addr.COUNTRY_CODE = {$countryCode}
				WHEN NOT MATCHED THEN
					INSERT (ENTITY_TYPE_ID, ENTITY_ID, TYPE_ID, ADDRESS_1, ADDRESS_2, CITY, POSTAL_CODE, REGION, PROVINCE, COUNTRY, COUNTRY_CODE)
					VALUES({$entityTypeID}, {$entityID}, {$typeID}, {$address1}, {$address2}, {$city}, {$postalCode}, {$region}, {$province}, {$country}, {$countryCode})"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}