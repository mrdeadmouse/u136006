<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Ui\Avatar;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

abstract class BaseObject extends Internals\Model
{
	const ERROR_NON_UNIQUE_NAME = 'DISK_OBJ_22000';

	/** @var string */
	protected $name;
	/** @var string */
	protected $label;
	/** @var string */
	protected $code;
	/** @var string */
	protected $xmlId;
	/** @var int */
	protected $storageId;
	/** @var  Storage */
	protected $storage;
	/** @var int */
	protected $type;
	/** @var int */
	protected $realObjectId;
	/** @var BaseObject */
	protected $realObject;
	/** @var int */
	protected $parentId;
	/** @var Folder */
	protected $parent;
	/** @var Document\CloudImport\Entry */
	protected $lastCloudImport;
	/** @var string */
	protected $contentProvider;
	/** @var int */
	protected $deletedType;

	/** @var DateTime */
	protected $createTime;
	/** @var DateTime */
	protected $updateTime;
	/** @var DateTime */
	protected $deleteTime;

	/** @var int */
	protected $createdBy;
	/** @var  User */
	protected $createUser;
	/** @var int */
	protected $updatedBy;
	/** @var  User */
	protected $updateUser;
	/** @var int */
	protected $deletedBy;
	/** @var  User */
	protected $deleteUser;

	/**
	 * Returns the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return ObjectTable::className();
	}

	/**
	 * Checks rights to change rights on current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canChangeRights(SecurityContext $securityContext)
	{
		return $securityContext->canChangeRights($this->id);
	}

	/**
	 * Checks rights to delete current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canDelete(SecurityContext $securityContext)
	{
		return $securityContext->canDelete($this->id);
	}

	/**
	 * Checks rights to mark deleted current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canMarkDeleted(SecurityContext $securityContext)
	{
		return $securityContext->canMarkDeleted($this->id);
	}

	/**
	 * Checks rights to move current object in new destination object.
	 * @param SecurityContext $securityContext Security context.
	 * @param BaseObject $targetObject New destination object.
	 * @return bool
	 */
	public function canMove(SecurityContext $securityContext, BaseObject $targetObject)
	{
		return $securityContext->canMove($this->id, $targetObject->getId());
	}

	/**
	 * Checks rights to read current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canRead(SecurityContext $securityContext)
	{
		return $securityContext->canRead($this->id);
	}

	/**
	 * Checks rights to rename current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canRename(SecurityContext $securityContext)
	{
		return $securityContext->canRename($this->id);
	}

	/**
	 * Checks rights to restore current object from trash can.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canRestore(SecurityContext $securityContext)
	{
		return $securityContext->canRestore($this->id);
	}

	/**
	 * Checks rights to share current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canShare(SecurityContext $securityContext)
	{
		return $securityContext->canShare($this->id);
	}

	/**
	 * Checks rights to update (content) current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canUpdate(SecurityContext $securityContext)
	{
		return $securityContext->canUpdate($this->id);
	}

	/**
	 * Checks rights to update current object by cloud import.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canUpdateByCloudImport(SecurityContext $securityContext)
	{
		return
				$this->getContentProvider() &&
				$this->getCreatedBy() == $securityContext->getUserId() &&
				$securityContext->canUpdate($this->id)
		;
	}

	/**
	 * Returns time of create object.
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns id of user, who created object.
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Returns user model, who created object.
	 * @return User
	 */
	public function getCreateUser()
	{
		if(isset($this->createUser) && $this->createdBy == $this->createUser->getId())
		{
			return $this->createUser;
		}
		$this->createUser = User::getModelForReferenceField($this->createdBy, $this->createUser);

		return $this->createUser;
	}

	/**
	 * Returns time of delete object.
	 * @return DateTime
	 */
	public function getDeleteTime()
	{
		return $this->deleteTime;
	}

	/**
	 * Returns id of user, who deleted object.
	 * @return int
	 */
	public function getDeletedBy()
	{
		return $this->deletedBy;
	}

	/**
	 * Returns deleted type (@see ObjectTable).
	 * @return int
	 */
	public function getDeletedType()
	{
		return $this->deletedType;
	}

	/**
	 * Returns user model, who deleted object.
	 * @return User
	 */
	public function getDeleteUser()
	{
		if(isset($this->deleteUser) && $this->deletedBy == $this->deleteUser->getId())
		{
			return $this->deleteUser;
		}
		$this->deleteUser = User::getModelForReferenceField($this->deletedBy, $this->deleteUser);

		return $this->deleteUser;
	}

	/**
	 * Returns real object of object.
	 *
	 * For example if object is link (@see FolderLink, @see FileLink), then method returns original object.
	 * @return BaseObject|Folder|File
	 */
	public function getRealObject()
	{
		if(!$this->isLink())
		{
			return $this;
		}

		if(isset($this->realObject) && $this->realObjectId === $this->realObject->getId())
		{
			return $this->realObject;
		}
		$this->realObject = BaseObject::loadById($this->realObjectId);

		return $this->realObject;
	}

	/**
	 * Returns id of real object of object.
	 *
	 * For example if object is link (@see FolderLink, @see FileLink), then method returns id of original object.
	 * @return int
	 */
	public function getRealObjectId()
	{
		return $this->isLink()? $this->realObjectId : $this->id;
	}

	/**
	 * Returns true if object is moved in trash can.
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->deletedType != ObjectTable::DELETED_TYPE_NONE;
	}

	/**
	 * Returns name of object.
	 * Be careful: the method returns name without possible trash can suffix.
	 * @return string
	 */
	public function getName()
	{
		if($this->label === null)
		{
			$this->label = $this->getNameWithoutTrashCanSuffix();
		}
		return $this->label;
	}

	/**
	 * Returns original name of object.
	 * May contains trash can suffix.
	 * @return string
	 */
	public function getOriginalName()
	{
		return $this->name;
	}

	protected function getNameWithTrashCanSuffix()
	{
		return Ui\Text::appendTrashCanSuffix($this->name);
	}

	protected function getNameWithoutTrashCanSuffix()
	{
		return Ui\Text::cleanTrashCanSuffix($this->name);
	}

	/**
	 * Returns code of object.
	 * Code is used for working with object by symbolic name.
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns xml id of object.
	 * @return string
	 */
	public function getXmlId()
	{
		return $this->xmlId;
	}

	/**
	 * Returns id of parent object.
	 * If object is root, then method returns null.
	 * @return int
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * Returns content provider of object.
	 * Content provider determines service which provided object (for ex. Dropbox).
	 * @return string
	 */
	public function getContentProvider()
	{
		return $this->contentProvider;
	}

	/**
	 * Returns id of storage.
	 * @return int
	 */
	public function getStorageId()
	{
		return $this->storageId;
	}

	/**
	 * Returns storage model.
	 * @return array|Storage|null
	 */
	public function getStorage()
	{
		if(!$this->storageId)
		{
			return null;
		}

		if(isset($this->storage) && $this->storageId == $this->storage->getId())
		{
			return $this->storage;
		}
		$this->storage = Storage::loadById($this->storageId, array('ROOT_OBJECT'));

		if(!$this->storage)
		{
			return array();
		}

		return $this->storage;
	}

	/**
	 * Returns type (folder or file).
	 * @see ObjectTable::TYPE_FOLDER, ObjectTable::TYPE_FILE.
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Returns time of update object.
	 * @return DateTime
	 */
	public function getUpdateTime()
	{
		return $this->updateTime;
	}

	/**
	 * Returns id of user, who updated object.
	 * @return int
	 */
	public function getUpdatedBy()
	{
		return $this->updatedBy;
	}

	/**
	 * Returns user model, who created object.
	 * @return User
	 */
	public function getUpdateUser()
	{
		if(isset($this->updateUser) && $this->updatedBy == $this->updateUser->getId())
		{
			return $this->updateUser;
		}
		$this->updateUser = User::getModelForReferenceField($this->updatedBy, $this->updateUser);

		return $this->updateUser;
	}

	/**
	 * Tells if object is a link on another object.
	 * @return bool
	 */
	public function isLink()
	{
		return isset($this->realObjectId) && $this->realObjectId != $this->id;
	}

	/**
	 * Returns last cloud import entry of object.
	 * @see Document\CloudImport\ImportManager, @see Document\CloudImport\Entry.
	 * @return Document\CloudImport\Entry|
	 */
	public function getLastCloudImportEntry()
	{
		if($this->lastCloudImport === null)
		{
			$lastCloudImport = Document\CloudImport\Entry::getModelList(array(
				'filter' => array(
					'OBJECT_ID' => $this->getRealObjectId(),
				),
				'order' => array(
					'ID' => 'DESC',
				),
			    'limit' => 1,
			));
			if(!$lastCloudImport)
			{
				return null;
			}
			$this->lastCloudImport = array_pop($lastCloudImport);
		}

		return $this->lastCloudImport;
	}

	/**
	 * Renames object.
	 * @param string $newName New name.
	 * @param bool $generateUniqueName Generates unique name for object in directory.
	 * @return bool
	 * @internal
	 */
	public function renameInternal($newName, $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		if(!$newName)
		{
			$this->errorCollection->addOne(new Error('Empty name.'));
			return false;
		}

		if($this->name == $newName)
		{
			return true;
		}
		if($generateUniqueName)
		{
			$newName = $this->generateUniqueName($newName, $this->getParentId());
		}

		if(!$this->isUniqueName($newName, $this->parentId))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_OBJECT_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return false;
		}

		$oldName = $this->name;
		$success = $this->update(array('NAME' => $newName, 'UPDATE_TIME' => new DateTime()));
		if(!$success)
		{
			return false;
		}
		$this->label = null;


		Driver::getInstance()->sendChangeStatusToSubscribers($this);

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterRenameObject", array($this, $oldName, $newName));
		$event->send();

		return true;
	}

	/**
	 * Renames object.
	 * @param string $newName New name.
	 * @return bool
	 */
	public function rename($newName)
	{
		return $this->renameInternal($newName, false);
	}

	/**
	 * Changes xml id on current element.
	 * @param string $newXmlId New xml id.
	 * @return bool
	 */
	public function changeXmlId($newXmlId)
	{
		return $this->update(array('XML_ID' => $newXmlId));
	}

	/**
	 * Changes code on current element.
	 * @param string $newCode New code.
	 * @return bool
	 */
	public function changeCode($newCode)
	{
		return $this->update(array('CODE' => $newCode));
	}

	/**
	 * Copies object to target folder.
	 * @param Folder $targetFolder Target folder.
	 * @param int    $updatedBy Id of user.
	 * @param bool   $generateUniqueName Generates unique name for object in directory.
	 * @return BaseObject|null
	 */
	abstract public function copyTo(Folder $targetFolder, $updatedBy, $generateUniqueName = false);

	/**
	 * Moves object to another folder.
	 * Support cross-storage move (mark deleted + create new)
	 * @param Folder $folder             Destination folder.
	 * @param int    $movedBy            User id of user, which move file.
	 * @param bool   $generateUniqueName Generates unique name for object if in destination directory.
	 * @return BaseObject|null
	 */
	public function moveTo(Folder $folder, $movedBy, $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		if($this->getId() == $folder->getId())
		{
			return $this;
		}

		$realStorageIdSource = $this->getStorageId();
		$realStorageIdTarget = $folder->getRealObject()->getStorageId();

		$realFolderId = $folder->getRealObject()->getId();
		if($this->getParentId() == $realFolderId)
		{
			return $this;
		}

		$possibleNewName = $this->name;
		if($generateUniqueName)
		{
			$possibleNewName = $this->generateUniqueName($this->name, $realFolderId);
		}
		$needToRename = $possibleNewName != $this->name;

		if(!$this->isUniqueName($possibleNewName, $realFolderId))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_OBJECT_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return null;
		}
		$this->name = $possibleNewName;

		if($needToRename)
		{
			$successUpdate = $this->update(array(
				'NAME' => $possibleNewName
			));
			if(!$successUpdate)
			{
				return null;
			}
		}

		//simple move
		if($realStorageIdSource == $realStorageIdTarget)
		{
			$object = $this->moveInSameStorage($folder, $movedBy);
		}
		else
		{
			$object = $this->moveInAnotherStorage($folder, $movedBy);
		}
		if($object !== null)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterMoveObject", array($this));
			$event->send();
		}

		return $object;
	}

	/**
	 * @param Folder $folder
	 * @param  int   $movedBy
	 * @return $this|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function moveInSameStorage(Folder $folder, $movedBy)
	{
		$subscribersBeforeMove = Driver::getInstance()->collectSubscribers($this);

		$realFolderId = $folder->getRealObject()->getId();
		/** @var ObjectTable $tableClassName */
		$tableClassName = $this->getTableClassName();

		$moveResult = $tableClassName::move($this->id, $realFolderId);
		if(!$moveResult->isSuccess())
		{
			$this->errorCollection->addFromResult($moveResult);
			return null;
		}
		$this->setAttributesFromResult($moveResult);

		Driver::getInstance()->getRightsManager()->setAfterMove($this);

		$subscribersAfterMove = Driver::getInstance()->collectSubscribers($this);
		DeletedLog::addAfterMove(
			$this,
			array_unique(array_diff($subscribersBeforeMove, $subscribersAfterMove)),
			$movedBy,
			$this->errorCollection
		);
		//notify new subscribers (in DeletedLog we notify subscribers only missed access)
		if($this instanceof Folder)
		{
			Driver::getInstance()->cleanCacheTreeBitrixDisk(array_keys($subscribersAfterMove));
		}
		Driver::getInstance()->sendChangeStatus($subscribersAfterMove);

		$success = $this->update(array('UPDATE_TIME' => new DateTime()));
		if(!$success)
		{
			return null;
		}

		return $this;
	}

	/**
	 * Simple logic: create copy in another storage and move in trash can.
	 * If we have problem - stop.
	 * Return new object.
	 * @param Folder $targetFolder
	 * @param int    $movedBy
	 * @return null|BaseObject
	 */
	protected function moveInAnotherStorage(Folder $targetFolder, $movedBy)
	{
		$newObject = $this->copyTo($targetFolder, $movedBy);
		if(!$newObject)
		{
			return null;
		}
		if($newObject->getErrors())
		{
			$this->errorCollection->add($newObject->getErrors());
			return $newObject;
		}
		$this->markDeleted($movedBy);

		return $newObject;
	}

	/**
	 * Builds model from array.
	 * @param array $attributes Model attributes.
	 * @param array &$aliases Aliases.
	 * @internal
	 * @return static
	 */
	public static function buildFromArray(array $attributes, array &$aliases = null)
	{
		/** @var BaseObject $className */
		$className = static::getClassNameModel($attributes);
		/** @var BaseObject $model */
		$model = new $className;

		return $model->setAttributes($attributes, $aliases);
	}

	/**
	 * Builds model from \Bitrix\Main\Entity\Result.
	 * @param Result $result Query result.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function buildFromResult(Result $result)
	{
		$data = $result->getData();
		if($result instanceof AddResult)
		{
			$data['ID'] = $result->getId();
		}
		$className = static::getClassNameModel($data);
		/** @var BaseObject $model */
		$model = new $className;
		return $model->setAttributesFromResult($result);
	}

	protected static function getClassNameModel(array $row)
	{
		if(!isset($row['ID']))
		{
			throw new ArgumentTypeException('Invalid ID');
		}
		if(!isset($row['TYPE']))
		{
			throw new ArgumentTypeException('Invalid TYPE');
		}

		if(empty($row['REAL_OBJECT_ID']) || $row['REAL_OBJECT_ID'] == $row['ID'])
		{
			if($row['TYPE'] == ObjectTable::TYPE_FILE)
			{
				return File::className();
			}
			return Folder::className();
		}
		if($row['TYPE'] == ObjectTable::TYPE_FILE)
		{
			return FileLink::className();
		}
		return FolderLink::className();
	}

	/**
	 * Returns once model by specific filter.
	 * @param array $filter Filter.
	 * @param array $with List of eager loading.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function load(array $filter, array $with = array())
	{
		$objectData = static::getList(array(
			'with' => $with,
			'filter'=> $filter,
			'limit' => 1,
		))->fetch();

		if(empty($objectData))
		{
			return null;
		}
		/** @var BaseObject $className */
		$className = static::getClassNameModel($objectData);

		return $className::buildFromArray($objectData);
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	abstract public function markDeleted($deletedBy);

	/**
	 * Restores object from trash can.
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	abstract public function restore($restoredBy);

	/**
	 * @param int $deletedBy
	 * @param int $deletedType
	 * @throws \Bitrix\Main\ArgumentException
	 * @return bool
	 */
	protected function markDeletedInternal($deletedBy, $deletedType = ObjectTable::DELETED_TYPE_ROOT)
	{
		$this->errorCollection->clear();

		$status = $this->update(array(
			'CODE' => null,
			'NAME' => $deletedType == ObjectTable::DELETED_TYPE_ROOT? $this->getNameWithTrashCanSuffix() : $this->name,
			'DELETED_TYPE' => $deletedType,
			'DELETE_TIME' => new DateTime(),
			'DELETED_BY' => $deletedBy,
		));
		if($status)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterMarkDeletedObject", array($this, $deletedBy, $deletedType));
			$event->send();
		}
		return $status;
	}

	/**
	 * Restores object from trash can.
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 * @internal
	 */
	public function restoreInternal($restoredBy)
	{
		if(!$this->isUniqueName($this->getNameWithoutTrashCanSuffix(), $this->parentId, $this->id))
		{
			$this->name = $this->generateUniqueName($this->getNameWithoutTrashCanSuffix(), $this->parentId);
		}

		/** @var ObjectTable $tableClassName */
		$tableClassName = $this->getTableClassName();

		$status = $this->update(array(
			'NAME' => $this->getNameWithoutTrashCanSuffix(),
			'DELETED_TYPE' => $tableClassName::DELETED_TYPE_NONE,
			'UPDATE_TIME' => new DateTime(),
			'UPDATED_BY' => $restoredBy,
		));
		if($status)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterRestoreObject", array($this, $restoredBy));
			$event->send();
		}

		return $status;
	}

	protected function recalculateDeletedTypeAfterRestore($restoredBy)
	{
		$fakeContext = Storage::getFakeSecurityContext();
		foreach ($this->getParents($fakeContext, array('filter' => array('MIXED_SHOW_DELETED' => true)), SORT_ASC) as $parent)
		{
			if(!$parent instanceof Folder || !$parent->isDeleted())
			{
				continue;
			}
			foreach ($parent->getChildren($fakeContext, array('filter' => array('!DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,))) as $childPotentialRoot)
			{
				if($childPotentialRoot instanceof Folder && $childPotentialRoot->getId() != $this->getId())
				{
					$childPotentialRoot->markDeletedNonRecursiveInternal($childPotentialRoot->getDeletedBy());
				}
				elseif($childPotentialRoot instanceof File)
				{
					$childPotentialRoot->markDeletedInternal($childPotentialRoot->getDeletedBy());
				}
			}
			unset($childPotentialRoot);

			$parent->restoreNonRecursive($restoredBy);
		}
		unset($parent);

		return;
	}

	/**
	 * Returns list parents of object.
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @param int             $orderDepthLevel Order for depth level (default asc).
	 * @return array|Folder[]|File[]|FileLink[]|FolderLink[]
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function getParents(SecurityContext $securityContext, array $parameters = array(), $orderDepthLevel = SORT_ASC)
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!isset($parameters['select']))
		{
			$parameters['select'] = array('*');
		}

		if(!empty($parameters['filter']['MIXED_SHOW_DELETED']))
		{
			unset($parameters['filter']['DELETED_TYPE'], $parameters['filter']['MIXED_SHOW_DELETED']);
		}
		elseif(!array_key_exists('DELETED_TYPE', $parameters['filter']) && !array_key_exists('!DELETED_TYPE', $parameters['filter']))
		{
			$parameters['filter']['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}
		$parameters['select']['DEPTH_LEVEL'] = 'PATH_PARENT.DEPTH_LEVEL';
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		/** @var ObjectTable $tableClassName */
		$tableClassName = $this->getTableClassName();
		$data = $tableClassName::getAncestors($this->id, static::prepareGetListParameters($parameters))->fetchAll();
		Collection::sortByColumn($data, array('DEPTH_LEVEL' => $orderDepthLevel));

		$modelData = array();
		foreach($data as $item)
		{
			$modelData[] = BaseObject::buildFromArray($item);
		}
		unset($item);

		return $modelData;
	}

	/**
	 * Returns parent model.
	 * If object does not have parent, then returns null.
	 * @return Folder|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getParent()
	{
		if(!$this->parentId)
		{
			return null;
		}

		if(isset($this->parent) && $this->parentId === $this->parent->getId())
		{
			return $this->parent;
		}
		//todo - BaseObject - knows about Folder ^( Nu i pust'
		$this->parent = Folder::loadById($this->getParentId());

		return $this->parent;
	}

	/**
	 * Tells if name is not unique in object.
	 * @param string $name Name.
	 * @param int $underObjectId Id of parent object.
	 * @param null $excludeId Id which will be excluded from query.
	 * @param null &$opponentId Opponent object which has same name.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function isUniqueName($name, $underObjectId, $excludeId = null, &$opponentId = null)
	{
		$opponent = ObjectTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'!ID' => $excludeId,
				'PARENT_ID' => $underObjectId,
				'=NAME' => $name,
			),
			'limit' => 1,
		))->fetch();
		if(!$opponent)
		{
			return true;
		}
		$opponentId = $opponent['ID'];

		return false;
	}

	/**
	 * Appends (1), (2), etc. if name is non unique in target dir
	 * @param string $potentialName Potential name.
	 * @param int $underObjectId Id of parent object.
	 * @return string
	 */
	protected static function generateUniqueName($potentialName, $underObjectId)
	{
		$count = 0;
		$newName = $mainPartName = $potentialName;
		while(!static::isUniqueName($newName, $underObjectId))
		{
			$count++;
			$withoutDot = strstr($mainPartName, '.', true);
			if(strlen($withoutDot) > 0)
			{
				$newName = $withoutDot . " ({$count})" . strstr($mainPartName, '.');
			}
			else
			{
				$newName = $mainPartName . " ({$count})";
			}
		}

		return $newName;
	}

	protected function updateLinksAttributes(array $attr)
	{
		/** @var ObjectTable $tableClassName */
		$tableClassName = $this->getTableClassName();
		//todo don't update object with REAL_OBJECT_ID == ID. Exlucde form update. It is not necessary.
		$tableClassName::updateAttributesByFilter($attr, array('REAL_OBJECT_ID' => $this->id));
	}

	/**
	 * Returns all sharings where the object is as source (real_object_id).
	 * @return Sharing[]
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getSharingsAsReal()
	{
		/** @var Sharing[] $sharings */
		$sharings = Sharing::getModelList(array(
			'with' => array('LINK_OBJECT'),
			'filter' => array(
				'REAL_OBJECT_ID' => $this->id,
				'REAL_STORAGE_ID' => $this->storageId,
				'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
			)
		));
		foreach($sharings as $sharing)
		{
			$sharing->setAttributes(array('REAL_OBJECT' => $this));
		}
		unset($sharing);

		return $sharings;
	}

	/**
	 * Returns all sharing where the object is as link (link_object_id).
	 * @return Sharing[]
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getSharingsAsLink()
	{
		/** @var Sharing[] $sharings */
		$sharings = Sharing::getModelList(array(
			'filter' => array(
				'LINK_OBJECT_ID' => $this->id,
				'LINK_STORAGE_ID' => $this->storageId,
			)
		));
		foreach($sharings as $sharing)
		{
			$sharing->setAttributes(array('LINK_OBJECT' => $this));
		}
		unset($sharing);

		return $sharings;
	}

	/**
	 * Returns list users who have sharing on this object.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getMembersOfSharing()
	{
		$sharings = $this->getRealObject()->getSharingsAsReal();
		$members = array();
		$membersToSharing = array();
		foreach($sharings as $sharing)
		{
			if($sharing->isToDepartmentChild())
			{
				continue;
			}
			list($type, $id) = Sharing::parseEntityValue($sharing->getToEntity());
			$members[$type][] = $id;
			$membersToSharing[$type . '|' . $id] = $sharing;
		}
		unset($sharing);

		$enabledSocialnetwork = Loader::includeModule('socialnetwork');

		$entityList = array();
		foreach(SharingTable::getListOfTypeValues() as $type)
		{
			if(empty($members[$type]))
			{
				continue;
			}
			if($type == SharingTable::TYPE_TO_USER)
			{
				$query = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'PERSONAL_PHOTO', 'NAME', 'LOGIN', 'LAST_NAME', 'SECOND_NAME'),
					'filter' => array('ID' => array_values($members[$type])),
				));
				while($userRow = $query->fetch())
				{
					/** @var Sharing $sharing */
					$sharing = $membersToSharing[$type . '|' . $userRow['ID']];
					$entityList[] = array(
						'entityId' => Sharing::CODE_USER . $userRow['ID'],
						'name' => \CUser::formatName('#NAME# #LAST_NAME#', array(
							"NAME" => $userRow['NAME'],
							"LAST_NAME" => $userRow['LAST_NAME'],
							"SECOND_NAME" => $userRow['SECOND_NAME'],
							"LOGIN" => $userRow['LOGIN'],
						), false),
						'right' => $sharing->getTaskName(),
						'avatar' => Avatar::getPerson($userRow['PERSONAL_PHOTO']),
						'type' => 'users',
					);
				}
			}
			elseif($type == SharingTable::TYPE_TO_GROUP && $enabledSocialnetwork)
			{
				$query = \CSocNetGroup::getList(array(), array('ID' => array_values($members[$type])), false, false, array(
						'ID',
						'IMAGE_ID',
						'NAME'
					));
				while($query && $groupRow = $query->fetch())
				{
					/** @var Sharing $sharing */
					$sharing = $membersToSharing[$type . '|' . $groupRow['ID']];
					$entityList[] = array(
						'entityId' => Sharing::CODE_SOCNET_GROUP . $groupRow['ID'],
						'name' => $groupRow['NAME'],
						'right' => $sharing->getTaskName(),
						'avatar' => Avatar::getGroup($groupRow['IMAGE_ID']),
						'type' => 'groups',
					);
				}
			}
			elseif($type == SharingTable::TYPE_TO_DEPARTMENT && $enabledSocialnetwork)
			{
				// intranet structure
				$structure = \CSocNetLogDestination::getStucture();
				foreach(array_values($members[$type]) as $departmentId)
				{
					if(empty($structure['department']['DR' . $departmentId]))
					{
						continue;
					}
					/** @var Sharing $sharing */
					$sharing = $membersToSharing[$type . '|' . $departmentId];
					$entityList[] = array(
						'entityId' => Sharing::CODE_DEPARTMENT . $departmentId,
						'name' => $structure['department']['DR' . $departmentId]['name'],
						'right' => $sharing->getTaskName(),
						'avatar' => Avatar::getDefaultGroup(),
						'type' => 'department',
					);
				}
				unset($departmentId);
			}
		}
		unset($type);

		return $entityList;
	}

	/**
	 * Returns the list of pair for mapping.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'NAME' => 'name',
			'CODE' => 'code',
			'XML_ID' => 'xmlId',
			'STORAGE_ID' => 'storageId',
			'STORAGE' => 'storage',
			'TYPE' => 'type',
			'REAL_OBJECT_ID' => 'realObjectId',
			'REAL_OBJECT' => 'realObject',
			'PARENT_ID' => 'parentId',
			'PARENT' => 'parent',
			'CONTENT_PROVIDER' => 'contentProvider',
			'DELETED_TYPE' => 'deletedType',
			'CREATE_TIME' => 'createTime',
			'UPDATE_TIME' => 'updateTime',
			'DELETE_TIME' => 'deleteTime',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
			'UPDATED_BY' => 'updatedBy',
			'UPDATE_USER' => 'updateUser',
			'DELETED_BY' => 'deletedBy',
			'DELETE_USER' => 'deleteUser',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$userClassName = User::className();
		$fields = User::getFieldsForSelect();

		return array(
			'CREATE_USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
			'UPDATE_USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
			'DELETE_USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
			'REAL_OBJECT' => BaseObject::className(),
			'STORAGE' => Storage::className(),
		);
	}
}