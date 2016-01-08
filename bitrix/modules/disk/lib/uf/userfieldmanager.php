<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\BaseObject;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\SystemException;

final class UserFieldManager implements IErrorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	protected $additionalConnectorList = null;
	/** @var AttachedObject[]  */
	protected $loadedAttachedObjects = array();

	/**
	 * Constructor of UserFiedManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	/**
	 * Gets values of user fields for file or folder.
	 * @param BaseObject $object Target object.
	 * @return array
	 */
	public function getFieldsForObject(BaseObject $object)
	{
		/** @var \CAllUserTypeManager */
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->getUserFields($this->getUfEntityName($object), $object->getId(), LANGUAGE_ID);
	}

	/**
	 * Gets data which describes specific connector by entity type.
	 * @param string $entityType Entity type (ex. sonet_comment).
	 * @return array|null Array with two elements: connector class and module.
	 */
	public function getConnectorDataByEntityType($entityType)
	{
		$entityType = strtolower($entityType);
		switch($entityType)
		{
			case 'blog_comment':
				return array(BlogPostCommentConnector::className(), 'blog');
			case 'blog_post':
				return array(BlogPostConnector::className(), 'blog');
			case 'calendar_event':
				return array(CalendarEventConnector::className(), 'calendar');
			case 'forum_message':
				return array(ForumMessageConnector::className(), 'forum');
			case 'tasks_task':
				return array(TaskConnector::className(), 'tasks');
			case 'sonet_log':
				return array(SonetLogConnector::className(), 'socialnetwork');
			case 'sonet_comment':
				return array(SonetCommentConnector::className(), 'socialnetwork');
		}
		$data = $this->getAdditionalConnector($entityType);

		return $data === null?
			array(StubConnector::className(), Driver::INTERNAL_MODULE_ID) :
			$data
		;
	}

	/**
	 * Gets input name for hidden input in disk.uf.file by entity type.
	 * This name will use in process saving user type (disk_file).
	 * @param string $entityType Entity type (ex. sonet_comment).
	 * @return string
	 */
	public function getInputNameForAllowEditByEntityType($entityType)
	{
		return $entityType . '_DISK_ATTACHED_OBJECT_ALLOW_EDIT';
	}

	/**
	 * Checks attitude attached object to entity. It is important in right checking in components disk.uf.file, disk.uf.version.
	 * @param AttachedObject $attachedObject Attached object.
	 * @param string $entityType Entity type (ex. sonet_comment).
	 * @param int $entityId Id of entity.
	 * @return bool
	 */
	public function belongsToEntity(AttachedObject $attachedObject, $entityType, $entityId)
	{
		list($connectorClass, $moduleId) = $this->getConnectorDataByEntityType($entityType);

		return
			$attachedObject->getEntityId()   == $entityId &&
			$attachedObject->getModuleId()   == $moduleId &&
			$attachedObject->getEntityType() == $connectorClass
		;
	}

	private function getAdditionalConnector($entityType)
	{
		if($this->additionalConnectorList === null)
		{
			$this->buildAdditionalConnectorList();
		}

		return isset($this->additionalConnectorList[$entityType])? $this->additionalConnectorList[$entityType] : null;
	}

	private function buildAdditionalConnectorList()
	{
		$this->additionalConnectorList = array();

		$event = new Event(Driver::INTERNAL_MODULE_ID, 'onBuildAdditionalConnectorList');
		$event->send();
		if($event->getResults())
		{
			foreach($event->getResults() as $evenResult)
			{
				if($evenResult->getResultType() != EventResult::SUCCESS)
				{
					continue;
				}

				$result = $evenResult->getParameters();
				if(!is_array($result))
				{
					throw new SystemException('Wrong event result by building AdditionalConnectorList. Must be array.');
				}
				if(empty($result['ENTITY_TYPE']))
				{
					throw new SystemException('Wrong event result by building AdditionalConnectorList. Could not find ENTITY_TYPE.');
				}
				if(empty($result['MODULE_ID']))
				{
					throw new SystemException('Wrong event result by building AdditionalConnectorList. Could not find MODULE_ID.');
				}
				if(empty($result['CLASS']))
				{
					throw new SystemException('Wrong event result by building AdditionalConnectorList. Could not find CLASS.');
				}
				if(is_string($result['CLASS']) && class_exists($result['CLASS']))
				{
					$this->additionalConnectorList[strtolower($result['ENTITY_TYPE'])] = array($result['CLASS'], $result['MODULE_ID']);
				}
				else
				{
					throw new SystemException('Wrong event result by building AdditionalConnectorList. Could not find class by CLASS.');
				}
			}
		}
	}

	/**
	 * Shows component disk.uf.file (edit mode).
	 * @param array &$params Component parameters.
	 * @param array &$result Component results.
	 * @param null $component Component.
	 * @return void
	 */
	public function showEdit(&$params, &$result, $component = null)
	{
		if(!Configuration::isSuccessfullyConverted())
		{
			return;
		}

		global $APPLICATION;
		$APPLICATION->includeComponent('bitrix:disk.uf.file', 
		($params["MOBILE"] == "Y" ? 'mobile' : ''),
		array(
			'EDIT' => 'Y',
			'PARAMS' => $params,
			'RESULT' => $result,
		), $component, array("HIDE_ICONS" => "Y"));
	}

	/**
	 * Shows component disk.uf.file (show mode).
	 * @param array &$params Component parameters.
	 * @param array &$result Component results.
	 * @param null $component Component.
	 * @return void
	 */
	public function showView(&$params, &$result, $component = null)
	{
		global $APPLICATION;
		$APPLICATION->includeComponent('bitrix:disk.uf.file', 
		($params["MOBILE"] == "Y" ? 'mobile' : ''),
		array(
			'PARAMS' => $params,
			'RESULT' => $result,
		), $component, array("HIDE_ICONS" => "Y"));
	}

	/**
	 * Shows component disk.uf.version.
	 * @param array &$params Component parameters.
	 * @param array &$result Component results.
	 * @param null $component Component.
	 * @return void
	 */
	public function showViewVersion(&$params, &$result, $component = null)
	{
		global $APPLICATION;
		$APPLICATION->includeComponent('bitrix:disk.uf.version', 
		($params["MOBILE"] == "Y" ? 'mobile' : ''),
		array(
			'PARAMS' => $params,
			'RESULT' => $result,
		), $component, array("HIDE_ICONS" => "Y"));
	}

	/**
	 * @param BaseObject $object
	 * @return string
	 */
	private function getUfEntityName(BaseObject $object)
	{
		if($object instanceof File)
		{
			return 'DISK_FILE_' . $object->getStorageId();
		}
		return 'DISK_FOLDER_' . $object->getStorageId();
	}

	/**
	 * Preload AttachedObjects.
	 * @param array $ids List of attached objects id.
	 * @return void
	 */
	public function loadBatchAttachedObject(array $ids)
	{
		foreach($ids as $i => &$id)
		{
			if(isset($this->loadedAttachedObjects[$id]))
			{
				unset($ids[$i]);
			}
			if(!is_numeric($id))
			{
				unset($ids[$i]);
			}
			$id = (int)$id;
		}
		unset($id);

		if(empty($ids))
		{
			return;
		}

		/** @var \Bitrix\Disk\AttachedObject $attachedObject */
		foreach(AttachedObject::getModelList(array("filter" => array("ID" => $ids), 'with' => array('OBJECT'))) as $attachedObject)
		{
			$this->loadedAttachedObjects[$attachedObject->getId()] = $attachedObject;
		}
		unset($attachedObject);
	}

	/**
	 * Preload AttachedObjects in blog posts.
	 * @param array $blogPostIds List of blog post id.
	 * @return void
	 */
	public function loadBatchAttachedObjectInBlogPost(array $blogPostIds)
	{
		if(empty($blogPostIds))
		{
			return;
		}

		list($connectorClass, $moduleId) = $this->getConnectorDataByEntityType('BLOG_POST');
		/** @var \Bitrix\Disk\AttachedObject $attachedObject */

		foreach(AttachedObject::getModelList(array("filter" => array(
			'=ENTITY_TYPE' => $connectorClass,
			'ENTITY_ID' => $blogPostIds,
			'=MODULE_ID' => $moduleId,
		), 'with' => array('OBJECT'))) as $attachedObject)
		{
			$this->loadedAttachedObjects[$attachedObject->getId()] = $attachedObject;
		}
		unset($attachedObject);
	}

	/**
	 * Checks by id of attached object status of loading data in memory (optimization).
	 * @param int $id Id of attached object.
	 * @return bool
	 */
	public function isLoadedAttachedObject($id)
	{
		return !empty($this->loadedAttachedObjects[$id]);
	}

	/**
	 * Gets attached object by id (optimization).
	 * @param int $id Id of attached object.
	 * @return AttachedObject|null
	 */
	public function getAttachedObjectById($id)
	{
		if(!isset($this->loadedAttachedObjects[$id]))
		{
			$this->loadedAttachedObjects[$id] = AttachedObject::loadById($id, array('OBJECT'));
		}
		return $this->loadedAttachedObjects[$id];
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Clones uf values from entity and creates new files (copies from attach) to save in new entity.
	 * @param array $attachedIds List of attached objects id.
	 * @param int $userId Id of user.
	 * @internal
	 * @return array
	 */
	public function cloneUfValuesFromAttachedObject(array $attachedIds, $userId)
	{
		$this->errorCollection->clear();

		$userId = (int)$userId;
		if($userId <= 0)
		{
			$this->errorCollection->addOne(new Error('Invalid $userId'));
			return null;
		}
		$userStorage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$userStorage)
		{
			$this->errorCollection->addOne(new Error("Could not find storage for user {$userId}"));
			$this->errorCollection->add(Driver::getInstance()->getErrors());
			return null;
		}
		$folder = $userStorage->getFolderForUploadedFiles();
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error("Could not create/find folder for upload"));
			$this->errorCollection->add($userStorage->getErrors());
			return null;
		}

		$newValues = array();
		foreach($attachedIds as $id)
		{
			list($type, $realValue) = FileUserType::detectType($id);
			if(FileUserType::TYPE_ALREADY_ATTACHED != $type)
			{
				continue;
			}
			$attachedObject = AttachedObject::loadById($realValue, array('OBJECT'));
			if(!$attachedObject)
			{
				continue;
			}
			if(!$attachedObject->canRead($userId))
			{
				continue;
			}
			$file = $attachedObject->getFile();
			if(!$file)
			{
				continue;
			}
			$newFile = $file->copyTo($folder, $userId, true);
			if(!$newFile)
			{
				$this->errorCollection->add($file->getErrors());
				continue;
			}
			$newValues[] = FileUserType::NEW_FILE_PREFIX . $newFile->getId();
		}

		return $newValues;
	}
}