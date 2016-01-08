<?php
namespace Bitrix\Crm;
use Bitrix\Main;
use \Bitrix\Sale;

class EntityAddress
{
	const Undefined = 0;
	const Primary = 1;
	const Secondary = 2;
	const Third = 3;
	const Home = 4;
	const Work = 5;
	const Registered = 6;
	const Custom = 7;

	const First = 1;
	const Last = 7;

	private static $messagesLoaded = false;

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	private static $labels = array();
	private static $shortLabels = array();
	private static $fullAddressLabels = array();

	private static function checkCountryCaption($code, $caption)
	{
		$fields = self::getCountryByCode($code);
		return $fields !== null && isset($fields['CAPTION']) && $fields['CAPTION'] === $caption;
	}

	/**
	* @return \CCrmEntityListBuilder
	*/
	protected static function createEntityListBuilder()
	{
		throw new Main\NotImplementedException('Method createEntityListBuilder must be overridden');
	}

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		throw new Main\NotImplementedException('Method getEntityTypeID must be overridden');
	}

	/**
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		throw new Main\NotImplementedException('Method getFieldMap must be overridden');
	}

	public static function mapEntityFields(array $fields, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? $options['TYPE_ID'] : EntityAddress::Undefined;
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}
		$skipEmpty = isset($options['SKIP_EMPTY']) ? $options['SKIP_EMPTY'] : false;

		$result = array();
		$map = static::getFieldMap($typeID);
		foreach($map as $k => $v)
		{
			$fieldValue = isset($fields[$v]) ? $fields[$v] : '';
			if($fieldValue !== '' || !$skipEmpty)
			{
				$result[$k] = $fieldValue;
			}
		}
		return $result;
	}

	public static function register($entityTypeID, $entityID, $typeID, array $data)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$country = isset($data['COUNTRY']) ? $data['COUNTRY'] : '';
		$countryCode = isset($data['COUNTRY_CODE']) ? $data['COUNTRY_CODE'] : '';
		if($countryCode !== '' && ($country === '' || !self::checkCountryCaption($countryCode, $country)))
		{
			$countryCode = '';
		}

		AddressTable::upsert(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'TYPE_ID' => $typeID,
				'ADDRESS_1' => isset($data['ADDRESS_1']) ? $data['ADDRESS_1'] : '',
				'ADDRESS_2' => isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '',
				'CITY' => isset($data['CITY']) ? $data['CITY'] : '',
				'POSTAL_CODE' => isset($data['POSTAL_CODE']) ? $data['POSTAL_CODE'] : '',
				'REGION' => isset($data['REGION']) ? $data['REGION'] : '',
				'PROVINCE' => isset($data['PROVINCE']) ? $data['PROVINCE'] : '',
				'COUNTRY' => $country,
				'COUNTRY_CODE' => $countryCode
			)
		);
	}
	public static function unregister($entityTypeID, $entityID, $typeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		AddressTable::delete(array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID, 'TYPE_ID' => $typeID));
	}

	public static function getFullAddressLabel($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!isset(self::$fullAddressLabels[$typeID]))
		{
			self::includeModuleFile();

			if($typeID === self::Registered)
			{
				self::$fullAddressLabels[self::Registered] = GetMessage('CRM_ENTITY_FULL_REG_ADDRESS');
			}
			else
			{
				self::$fullAddressLabels[$typeID] = GetMessage('CRM_ENTITY_FULL_ADDRESS');
			}
		}
		return self::$fullAddressLabels[$typeID];
	}
	public static function getLabels($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!isset(self::$labels[$typeID]))
		{
			self::includeModuleFile();

			if($typeID === self::Registered)
			{
				self::$labels[self::Registered] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_REG_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_REG_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_REG_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_REG_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_REG_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_REG_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_REG_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_REG_ADDRESS_COUNTRY')
				);
			}
			else
			{
				self::$labels[$typeID] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_ADDRESS_COUNTRY')
				);
			}
		}
		return self::$labels[$typeID];
	}
	public static function getLabel($fieldName, $typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$labels = self::getLabels($typeID);
		return isset($labels[$fieldName]) ? $labels[$fieldName] : $fieldName;
	}
	public static function getShortLabels($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!isset(self::$shortLabels[$typeID]))
		{
			self::includeModuleFile();

			if($typeID === self::Registered)
			{
				self::$shortLabels[self::Registered] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_COUNTRY')
				);
			}
			else
			{
				self::$shortLabels[$typeID] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_COUNTRY')
				);
			}
		}
		return self::$shortLabels[$typeID];
	}

	public static function getCountryByCode($code)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$dbResult = Sale\Location\LocationTable::getList(
			array(
				'filter' => array(
					'=TYPE.CODE' => 'COUNTRY',
					'=NAME.LANGUAGE_ID' => LANGUAGE_ID,
					'=CODE' => $code
				),
				'select' => array('CODE', 'CAPTION' => 'NAME.NAME')
			)
		);

		$fields = $dbResult->fetch();
		return is_array($fields)  ? $fields : null;
	}
	public static function getCountries(array $filter = null)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return array();
		}

		$listFilter = array(
			'=TYPE.CODE' => 'COUNTRY',
			'=NAME.LANGUAGE_ID' => LANGUAGE_ID
		);

		if(is_array($filter) && !empty($filter))
		{
			$caption = isset($filter['CAPTION']) ? $filter['CAPTION'] : '';
			if($caption !== '')
			{
				$listFilter['%NAME.NAME'] = $caption;
			}
		}

		$dbResult = Sale\Location\LocationTable::getList(
			array(
				'filter' => $listFilter,
				'select' => array('CODE', 'CAPTION' => 'NAME.NAME')
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$result[] = $fields;
		}

		return $result;
	}

	public static function resolveEntityFieldName($fieldName, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? $options['TYPE_ID'] : EntityAddress::Undefined;
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}

		$map = static::getFieldMap($typeID);
		return isset($map[$fieldName]) ? $map[$fieldName] : $fieldName;
	}

	public static function prepareChangeEvents(array $original, array $modified, $typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$original = static::mapEntityFields($original, array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => false));
		$modified = static::mapEntityFields($modified, array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => false));

		$events = array();

		self::prepareFieldChangeEvent('ADDRESS_1', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('ADDRESS_2', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('CITY', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('POSTAL_CODE', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('REGION', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('PROVINCE', $events,  $original, $modified, $typeID);
		self::prepareFieldChangeEvent('COUNTRY', $events,  $original, $modified, $typeID);

		return $events;
	}

	public static function getEntityList($typeID, array $sort, array $filter, $navParams = false)
	{
		$typeID = (int)$typeID;
		$sort = static::mapEntityFields($sort, array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => true));

		$entityTypeID = static::getEntityTypeID();
		$lb = static::createEntityListBuilder();


		$fields = $lb->GetFields();
		$entityAlias = $lb->GetTableAlias();
		$join = 'INNER JOIN b_crm_addr ADDR_S ON '.$entityAlias.'.ID = ADDR_S.ENTITY_ID AND ADDR_S.TYPE_ID = '.$typeID.' AND ADDR_S.ENTITY_TYPE_ID = '.$entityTypeID;

		$listSort = array();
		foreach($sort as $fieldName => $order)
		{
			$fieldKey = "ADDR_S_{$fieldName}";
			$fields[$fieldKey] = array('FIELD' => 'ADDR_S.'.$fieldName, 'TYPE' => 'string', 'FROM'=> $join);
			$listSort[$fieldKey] = $order;
		}
		$fields['ADDR_ENTITY_ID'] = array('FIELD' => 'ADDR_S.ENTITY_ID', 'TYPE' => 'string', 'FROM'=> $join);
		$listSort['ADDR_ENTITY_ID'] = array_shift(array_slice($listSort, 0, 1));
		$lb->SetFields($fields);

		$options = array(
			'PERMISSION_SQL_TYPE' => 'FROM',
			'PERMISSION_SQL_UNION' => 'DISTINCT'
		);

		return $lb->Prepare($listSort, $filter, false, $navParams, array('ID'), $options);
	}

	protected static function prepareFieldChangeEvent($fieldName, array &$events, array $original, array $modified, $typeID = 0)
	{
		$originalValue = isset($original[$fieldName]) ? $original[$fieldName] : '';
		$modifiedValue = isset($modified[$fieldName]) ? $modified[$fieldName] : '';

		if($originalValue === $modifiedValue)
		{
			return false;
		}

		$events[] = array(
			'ENTITY_FIELD' => static::resolveEntityFieldName($fieldName),
			'EVENT_NAME' => self::getLabel($fieldName, $typeID),
			'EVENT_TEXT_1' => $originalValue !== '' ? $originalValue : GetMessage('CRM_ENTITY_ADDRESS_CHANGE_EVENT_EMPTY'),
			'EVENT_TEXT_2' => $modifiedValue !== '' ? $modifiedValue : GetMessage('CRM_ENTITY_ADDRESS_CHANGE_EVENT_EMPTY'),
		);
		return true;
	}


	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}