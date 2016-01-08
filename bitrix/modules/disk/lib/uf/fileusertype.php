<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Ui;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class FileUserType
{
	const ERROR_COULD_NOT_FIND_ATTACHED_OBJECT = 'DISK_FUT_22002';

	const TYPE_NEW_OBJECT       = 2;
	const TYPE_ALREADY_ATTACHED = 3;
	const NEW_FILE_PREFIX = 'n';
	/** @var File[]  */
	protected static $loadedFiles = array();

	public static function getUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "disk_file",
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => Loc::getMessage('DISK_FILE_USER_TYPE_NAME'),
			"BASE_TYPE" => "int",
			"TAG" => array(
				"DISK FILE ID",
				"DOCUMENT ID"
			)
		);
	}

	public static function getDBColumnType($userField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "int(11)";
			case "oracle":
				return "number(18)";
			case "mssql":
				return "int";
		}
	}

	public static function prepareSettings($userField)
	{
		$iblockID = intval($userField["SETTINGS"]["IBLOCK_ID"]);
		$sectionID = intval($userField["SETTINGS"]["SECTION_ID"]);

		return array(
			"IBLOCK_ID" => $iblockID,
			"SECTION_ID" => $sectionID,
			"UF_TO_SAVE_ALLOW_EDIT" => $userField["SETTINGS"]["UF_TO_SAVE_ALLOW_EDIT"],
		);
	}

	public static function getSettingsHTML($userField = false, $htmlControl, $varsFromForm)
	{
		return "&nbsp;";
	}

	public static function getEditFormHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getFilterHTML($userField, $htmlControl)
	{
		return '&nbsp;';
	}

	public static function getAdminListViewHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTMLMulty($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function onSearchIndex($userField)
	{
		$values = $userField['VALUE'];
		if(!is_array($values))
		{
			$values = array($userField['VALUE']);
		}

		$searchData = array();
		$fileIdsForLoad = array();
		$attachedIdsForLoad = array();
		foreach($values as $value)
		{
			list($type, $realValue) = self::detectType($value);
			if($type == self::TYPE_NEW_OBJECT)
			{
				if(self::isLoadedFile($realValue))
				{
					$searchData[] = self::getFileById($realValue)->getName();
				}
				else
				{
					$fileIdsForLoad[] = $realValue;
				}
			}
			else
			{
				$attachedIdsForLoad[] = $realValue;
			}
		}
		unset($value);

		if($attachedIdsForLoad)
		{
			$query = AttachedObject::getList(array(
				'select' => array(
					'NAME' => 'OBJECT.NAME',
				),
				'filter' => array(
					'ID' => $attachedIdsForLoad
				),
			));
			while($attachedItem = $query->fetch())
			{
				$searchData[] = Ui\Text::cleanTrashCanSuffix($attachedItem['NAME']);
			}
			unset($attachedItem, $attachedIdsForLoad);
		}
		if($fileIdsForLoad)
		{
			$query = File::getList(array(
				'select' => array(
					'NAME',
				),
				'filter' => array(
					'ID' => $fileIdsForLoad
				),
			));
			while($fileItem = $query->fetch())
			{
				$searchData[] = Ui\Text::cleanTrashCanSuffix($fileItem['NAME']);
			}
			unset($fileItem, $fileIdsForLoad);
		}

		return implode("\r\n", $searchData);
	}

	public static function onBeforeSaveAll($userField, $values, $userId = false)
	{
		if(!is_array($values))
		{
			$values = array();
		}

		if($values)
		{
			static $alreadyRunDetach = array();
			if(!isset($alreadyRunDetach[$userField['FIELD_NAME'] . '|' . $userField['ENTITY_VALUE_ID']]))
			{
				$alreadyRunDetach[$userField['FIELD_NAME'] . '|' . $userField['ENTITY_VALUE_ID']] = true;

				if($userField['VALUE'])
				{
					$alreadyExistsValues = $userField['VALUE'];
					if(!is_array($alreadyExistsValues))
					{
						$alreadyExistsValues = array($userField['VALUE']);
					}
					$needToDetach = array_diff($alreadyExistsValues, $values);
					AttachedObject::detachByFilter(array('ID' => $needToDetach));
				}
			}
		}

		$valuesToInsert = array();
		foreach($values as $value)
		{
			if(!empty($value))
			{
				$valuesToInsert[] = (int)self::onBeforeSave($userField, $value, $userId);
			}
		}
		unset($value);

		return $valuesToInsert;
	}

	public static function onBeforeSave($userField, $value, $userId = false)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();

		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userField['ENTITY_ID']);
		list($type, $realValue) = self::detectType($value);

		if(empty($value))
		{
			$alreadyExistsValues = $userField['VALUE'];
			if(!is_array($alreadyExistsValues))
			{
				$alreadyExistsValues = array($userField['VALUE']);
			}
			AttachedObject::detachByFilter(array('ID' => $alreadyExistsValues));
			return $value;
		}

		if($type == self::TYPE_NEW_OBJECT)
		{
			$errorCollection = new ErrorCollection();
			$fileModel = self::getFileById($realValue);
			if(!$fileModel)
			{
				return '';
			}

			if($userId === false)
			{
				$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
			}

			$canUpdate = $fileModel->canUpdate($securityContext);
			$attachedModel = AttachedObject::add(array(
				'MODULE_ID' => $moduleId,
				'OBJECT_ID' => $fileModel->getId(),
				'ENTITY_ID' => $userField['VALUE_ID'],
				'ENTITY_TYPE' => $connectorClass,
				'IS_EDITABLE' => (int)$canUpdate,
				//$_POST - hack. We know.
				'ALLOW_EDIT' => (int) ($canUpdate && (int)Application::getInstance()->getContext()->getRequest()->getPost($userFieldManager->getInputNameForAllowEditByEntityType($userField['ENTITY_ID']))),
				'CREATED_BY' => $userId === false? self::getActivityUserId() : $userId,
			), $errorCollection);
			if(!$attachedModel || $errorCollection->hasErrors())
			{
				$errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_ATTACHED_OBJECT'), self::ERROR_COULD_NOT_FIND_ATTACHED_OBJECT)));
				return '';
			}

			return $attachedModel->getId();
		}
		else
		{
			return $realValue;
		}
	}

	public static function onDelete($userField, $value)
	{
		list($type, $realValue) = self::detectType($value);
		if($type != self::TYPE_ALREADY_ATTACHED)
		{
			return;
		}

		$attachedModel = AttachedObject::loadById($realValue);
		if(!$attachedModel)
		{
			return;
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		if(!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']))
		{
			return;
		}

		AttachedObject::detachByFilter(array('ID' => $realValue));
	}

	public static function getPublicViewHTML($userField, $id, $params = "", $settings = array(), $matches)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$res = (is_array($matches) && is_string($matches[0]) ? $matches[0] : '');
		list($type, $realValue) = self::detectType($id);

		if($type == self::TYPE_NEW_OBJECT || (is_array($matches) && $matches[1] == "DOCUMENT ID"))
		{
			$userFieldManager->loadBatchAttachedObject($userField["VALUE"]);

			$originalId = $id;
			$id = false;
			foreach ($userField["VALUE"] as $attachedObjectId)
			{
				if(!$userFieldManager->isLoadedAttachedObject($attachedObjectId))
				{
					continue;
				}

				$fileObject = $userFieldManager->getAttachedObjectById($attachedObjectId)->getFile();
				if(!$fileObject)
				{
					continue;
				}
				if($type == self::TYPE_NEW_OBJECT && $fileObject->getId() == $realValue)
				{
					$id = $attachedObjectId;
					break;
				}
				elseif($matches[1] == "DOCUMENT ID" && $fileObject->getXmlId() == $originalId)
				{
					$id = $attachedObjectId;
					break;
				}
			}
		}

		if ($id > 0)
		{
			$userField["VALUE"] = array_intersect($userField["VALUE"], array($id));
			$maxSize = array();
			if (is_array($settings) && !empty($settings) && array_key_exists("imageWidth", $settings) && array_key_exists("imageHeight", $settings))
				$maxSize = array("width" => $settings["imageWidth"], "height" => $settings["imageHeight"]);
			$size = array();
			if ($params != '' && is_string($params) && preg_match_all("/(width|height)=(\d+)/is", $params, $matches))
				$size = array_combine($matches[1], $matches[2]);
			ob_start();
			
			$newParams = array(
				"arUserField" => $userField, 
				"INLINE" => "Y", 
				"LAZYLOAD" => (isset($settings["LAZYLOAD"]) && $settings["LAZYLOAD"] == "Y" ? "Y" : "N"),
				"MAX_SIZE" => $maxSize, 
				"SIZE" => array($id => $size)
			);
			if (
				(defined("BX_MOBILE_LOG") && BX_MOBILE_LOG == true)
				|| (is_array($settings) && $settings["bMobile"] === true)
			)
			{
				$newParams["MOBILE"] = "Y";
			}
			$newResult = array("VALUE" => array($id));
			$userFieldManager->showView(
				$newParams,
				$newResult,
				null
			);
			$res = ob_get_clean();
		}
		return $res;
	}


	/**
	 * Detect: this is already exists attachedObject or new object
	 * @param $value
	 * @return array
	 */
	public static function detectType($value)
	{
		if(is_string($value) && $value[0] == self::NEW_FILE_PREFIX)
		{
			return array(self::TYPE_NEW_OBJECT, substr($value, 1));
		}
		return array(self::TYPE_ALREADY_ATTACHED, (int)$value);
	}

	/**
	 * @param      $userField
	 * @param      $value
	 * @param bool $userId False means current user id.
	 * @return array
	 */
	public static function checkFields($userField, $value, $userId = false)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$errors = array();

		list($type, $realValue) = self::detectType($value);

		if($type == self::TYPE_ALREADY_ATTACHED)
		{
			$attachedModel = $userFieldManager->getAttachedObjectById($realValue);
			if(!$attachedModel)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}
			list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userField['ENTITY_ID']);

			if(
				!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']) &&
				!(
					is_subclass_of($connectorClass, 'Bitrix\Disk\Uf\ISupportForeignConnector') ||
					in_array('Bitrix\Disk\Uf\ISupportForeignConnector', class_implements($connectorClass)) //5.3.9
				)
			)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}
		}
		else
		{
			if($realValue <= 0)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_INVALID_VALUE'),
				);

				return $errors;
			}

			$fileModel = self::getFileById($realValue);
			if(!$fileModel)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}

			if($userId === false)
			{
				$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
			}
			if(!$fileModel->canRead($securityContext))
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_FILE_USER_TYPE_ERROR_BAD_RIGHTS'),
				);

				return $errors;
			}
		}

		return $errors;
	}

	/**
	 * @param $id
	 * @return File|null
	 */
	protected static function getFileById($id)
	{
		if(!isset(self::$loadedFiles[$id]))
		{
			self::$loadedFiles[$id] = File::loadById($id, array('STORAGE'));
		}
		return self::$loadedFiles[$id];
	}

	protected static function isLoadedFile($id)
	{
		return isset(self::$loadedFiles[$id]);
	}

	private static function getActivityUserId()
	{
		global $USER;
		if($USER && is_object($USER))
		{
			$userId = $USER->getId();
			if(is_numeric($userId) && ((int)$userId > 0))
			{
				return $userId;
			}
		}

		return SystemUser::SYSTEM_USER_ID;
	}
}