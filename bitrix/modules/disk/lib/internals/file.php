<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\TypeFile;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class FileTable extends ObjectTable
{
	public static function getMap()
	{
		$map = parent::getMap();
		$map['TYPE']['validation'] = array(__CLASS__, 'validateType');
		$map['TYPE_FILE']['required'] = true;
		$map['PARENT_ID']['required'] = true;
		$map['NAME']['required'] = true;

		return $map;
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(1, 255),
		);
	}

	public static function add(array $data)
	{
		if(!isset($data['TYPE_FILE']))
		{
			$data['TYPE_FILE'] = TypeFile::getByFilename($data['NAME']);
		}

		$data['TYPE'] = static::TYPE_FILE;

		return parent::add($data);
	}

	public static function validateTypeLogic($value)
	{
		return $value == static::TYPE_FILE;
	}

	public static function validateType()
	{
		return array(
			array(__CLASS__, 'validateTypeLogic')
		);
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		if($result instanceof Entity\AddResult)
		{
			if(
				isset($data['REAL_OBJECT_ID']) &&
				isset($data['FILE_ID']) &&
				!empty($data['REAL_OBJECT_ID']) &&
				!empty($data['FILE_ID']) &&
				$data['REAL_OBJECT_ID'] != $data['ID']
			)
			{
				$field = static::getEntity()->getField('FILE_ID');
				$result->addError(new FieldError(
					$field,
					Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_LINK_FILE_ID", array("#FIELD#" => $field->getTitle()))
				));
			}

			if(
				isset($data['FILE_ID']) &&
				empty($data['REAL_OBJECT_ID']) &&
				empty($data['FILE_ID'])
			)
			{
				$field = static::getEntity()->getField('FILE_ID');
				$result->addError(new FieldError(
					$field,
					Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_REQUIRED_FILE_ID", array("#FIELD#" => $field->getTitle()))
				));
			}

		}

		parent::checkFields($result, $primary, $data);
	}
}
