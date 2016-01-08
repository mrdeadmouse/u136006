<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;

final class FolderTable extends ObjectTable
{
	public static function getMap()
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$map = parent::getMap();
		$map['TYPE']['validation'] = array(__CLASS__, 'validateType');
		$map[] = new ExpressionField('HAS_SUBFOLDERS',
			'CASE WHEN EXISTS(
			SELECT \'x\' FROM b_disk_object_path p
				INNER JOIN b_disk_object o ON o.ID=p.OBJECT_ID AND o.TYPE=2
			WHERE p.PARENT_ID = %1$s AND p.DEPTH_LEVEL = 1) THEN 1 ELSE 0 END',
		array('REAL_OBJECT_ID',), array('data_type' => 'boolean',));


		return $map;
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		if($result instanceof DeleteResult)
		{
			if(!ObjectPathTable::isLeaf($primary))
			{
				$result->addError(new EntityError(Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_DELETE_NODE")));
			}
		}

		parent::checkFields($result, $primary, $data);
	}

	public static function add(array $data)
	{
		$data['TYPE'] = static::TYPE_FOLDER;

		return parent::add($data);
	}

	public static function validateTypeLogic($value)
	{
		return $value == static::TYPE_FOLDER;
	}

	public static function validateType()
	{
		return array(
			array(__CLASS__, 'validateTypeLogic')
		);
	}

}
