<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\EditSessionTable;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use CFile;
use Bitrix\Disk\BizProcDocument;

Loc::loadMessages(__FILE__);

class File extends BaseObject
{
	const ERROR_COULD_NOT_SAVE_FILE           = 'DISK_FILE_22002';
	const ERROR_COULD_NOT_COPY_FILE           = 'DISK_FILE_22003';
	const ERROR_COULD_NOT_RESTORE_FROM_OBJECT = 'DISK_FILE_22004';

	const SECONDS_TO_JOIN_VERSION = 300;

	/** @var int */
	protected $typeFile;
	/** @var int */
	protected $globalContentVersion;
	/** @var int */
	protected $fileId;
	/** @var int */
	protected $prevFileId;
	/** @var array */
	protected $file;
	/** @var int */
	protected $size;
	/** @var string */
	protected $externalHash;
	/** @var string */
	protected $extenstion;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return FileTable::className();
	}

	/**
	 * Checks rights to start bizprocess on current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canStartBizProc(SecurityContext $securityContext)
	{
		return $securityContext->canStartBizProc($this->id);
	}

	/**
	 * Adds row to entity table, fills error collection and builds model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		$parent = null;
		if(isset($data['PARENT']) && $data['PARENT'] instanceof Folder)
		{
			$parent = $data['PARENT'];
			unset($data['PARENT']);
		}
		$file = parent::add($data, $errorCollection);
		if($file)
		{
			if($parent !== null)
			{
				$file->setAttributes(array('PARENT' => $parent));
			}
			$versionData = array(
				'ID' => $file->getFileId(),
				'FILE_SIZE' => $file->getSize(),
			);
			if(!empty($data['UPDATE_TIME']))
			{
				$versionData['UPDATE_TIME'] = $data['UPDATE_TIME'];
			}
			$file->addVersion($versionData, $file->getCreatedBy());
		}

		return $file;
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
		$filter['TYPE'] = ObjectTable::TYPE_FILE;

		return parent::load($filter, $with);
	}

	protected static function getClassNameModel(array $row)
	{
		$classNameModel = parent::getClassNameModel($row);
		if(
			$classNameModel === static::className() ||
			is_subclass_of($classNameModel, static::className()) ||
			in_array(static::className(), class_parents($classNameModel)) //5.3.9
		)
		{
			return $classNameModel;
		}

		throw new ObjectException('Could not to get non subclass of ' . static::className());
	}

	/**
	 * Returns extension.
	 * @return string
	 */
	public function getExtension()
	{
		if($this->extenstion === null)
		{
			$this->extenstion = getFileExtension($this->getName());
		}
		return $this->extenstion;
	}

	/**
	 * Returns external hash.
	 * @return string
	 */
	public function getExternalHash()
	{
		return $this->externalHash;
	}

	/**
	 * Returns global content version.
	 *
	 * Version always increments after creating new version.
	 * @return int
	 */
	public function getGlobalContentVersion()
	{
		return $this->globalContentVersion;
	}

	/**
	 * Returns id of file (table {b_file}).
	 * @return int
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * Returns file (@see CFile::getById());
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getFile()
	{
		if(!$this->fileId)
		{
			return null;
		}

		if(isset($this->file) && $this->fileId == $this->file['ID'])
		{
			return $this->file;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$this->file = \CFile::getByID($this->fileId)->fetch();

		if(!$this->file)
		{
			return array();
		}

		return $this->file;
	}

	/**
	 * Returns size in bytes.
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns type of file.
	 * @see TypeFile class for details.
	 * @return int
	 */
	public function getTypeFile()
	{
		return $this->typeFile;
	}

	/**
	 * Renames object.
	 * @param string $newName New name.
	 * @return bool
	 */
	public function rename($newName)
	{
		$result = parent::rename($newName);
		if($result)
		{
			$this->extenstion = null;
			Driver::getInstance()->getIndexManager()->changeName($this);
		}
		return $result;
	}

	/**
	 * Copies object to target folder.
	 * @param Folder $targetFolder Target folder.
	 * @param int    $updatedBy Id of user.
	 * @param bool   $generateUniqueName Generates unique name for object in directory.
	 * @return BaseObject|null
	 */
	public function copyTo(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$forkFileId = \CFile::copyFile($this->getFileId(), true);
		if(!$forkFileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_COPY_FILE'), self::ERROR_COULD_NOT_COPY_FILE)));
			return null;
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$fileArray = \CFile::getFileArray($forkFileId);
		$fileModel = $targetFolder->addFile(array(
			'NAME' => $this->getName(),
			'FILE_ID' => $forkFileId,
			'SIZE' => $fileArray['FILE_SIZE'],
			'CREATED_BY' => $updatedBy
		), array(), $generateUniqueName);
		if(!$fileModel)
		{
			$this->errorCollection->add($targetFolder->getErrors());
			return null;
		}

		return $fileModel;
	}

	/**
	 * Adds external link.
	 * @param array $data Data to create new external link (@see ExternalLink).
	 * @return bool|ExternalLink
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addExternalLink(array $data)
	{
		$this->errorCollection->clear();

		$data['OBJECT_ID'] = $this->id;

		return ExternalLink::add($data, $this->errorCollection);
	}

	/**
	 * Returns external links by file.
	 * @param array $parameters Parameters.
	 * @return static[]
	 */
	public function getExternalLinks(array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		$parameters['filter']['OBJECT_ID'] = $this->id;

		if(!isset($parameters['order']))
		{
			$parameters['order'] = array(
				'CREATE_TIME' => 'DESC',
			);
		}

		return ExternalLink::getModelList($parameters);
	}

	/**
	 * Increases global content version.
	 * @return bool
	 */
	public function increaseGlobalContentVersion()
	{
		//todo inc in DB by expression
		$success = $this->update(array(
			'GLOBAL_CONTENT_VERSION' => (int)$this->getGlobalContentVersion() + 1,
		));

		if(!$success)
		{
			return false;
		}

		$this->updateLinksAttributes(array(
			'GLOBAL_CONTENT_VERSION' => $this->getGlobalContentVersion(),
		));

		return $success;
	}

	/**
	 * Updates file content.
	 *
	 * Runs index file, updates all FileLinks, sends notify to subscribers.
	 *
	 * @param array $file Structure like $_FILES.
	 * @param int $updatedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function updateContent(array $file, $updatedBy)
	{
		$this->errorCollection->clear();

		$this->checkRequiredInputParams($file, array(
			'ID', 'FILE_SIZE'
		));

		$this->prevFileId = $this->fileId;
		//todo inc in DB by expression
		$success = $this->update(array(
			'GLOBAL_CONTENT_VERSION' => (int)$this->getGlobalContentVersion() + 1,
			'FILE_ID' => $file['ID'],
			'SIZE' => $file['FILE_SIZE'],
			'UPDATE_TIME' => empty($file['UPDATE_TIME'])? new DateTime() : $file['UPDATE_TIME'],
			'UPDATED_BY' => $updatedBy,
		));

		if(!$success)
		{
			$this->prevFileId = null;
			return false;
		}

		$this->updateLinksAttributes(array(
			'GLOBAL_CONTENT_VERSION' => $this->getGlobalContentVersion(),
			'SIZE' => $file['FILE_SIZE'],
			'UPDATE_TIME' => $this->getUpdateTime(),
			'UPDATED_BY' => $updatedBy,
		));

		$driver = Driver::getInstance();
		$driver->getIndexManager()->indexFile($this);

		//todo little hack...We don't synchronize file in folder with uploaded files. And we have not to send notify by pull
		if($this->parent === null || $this->parent && $this->parent->getCode() !== Folder::CODE_FOR_UPLOADED_FILES)
		{
			$driver->sendChangeStatusToSubscribers($this);

			$updatedBy = $this->getUpdatedBy();
			if($updatedBy)
			{
				$driver->sendEvent($updatedBy, 'live', array(
					'objectId' => $this->getId(),
					'action' => 'commit',
					'contentVersion' => (int)$this->getGlobalContentVersion(),
					'size' => (int)$this->getSize(),
					'formatSize' => (string)CFile::formatSize($this->getSize()),
				));
			}
		}

		return true;
	}

	/**
	 * Adds new version to file.
	 *
	 * The method may joins version with last version.
	 *
	 * @param array $file Structure like $_FILES.
	 * @param int $createdBy Id of user.
	 * @param bool $disableJoin If set false the method attempts to join version with last version (@see \Bitrix\Disk\File::SECONDS_TO_JOIN_VERSION).
	 * @return Version|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addVersion(array $file, $createdBy, $disableJoin = false)
	{
		$this->errorCollection->clear();

		$now = new DateTime;
		$needToJoin = false;
		if(!$disableJoin && $this->updateTime && $this->updatedBy == $createdBy)
		{
			$updateTimestamp = $this->updateTime->getTimestamp();
			if($now->getTimestamp() - $updateTimestamp < self::SECONDS_TO_JOIN_VERSION)
			{
				$needToJoin = true;
			}
		}

		if(!$this->updateContent($file, $createdBy))
		{
			return null;
		}

		if($needToJoin)
		{
			$lastVersion = $this->getLastVersion();
			if($lastVersion)
			{
				if(!$lastVersion->joinData(array_merge(array('CREATE_TIME' => $now), $this->getHistoricalData())))
				{
					$this->errorCollection->add($lastVersion->getErrors());
					return null;
				}
				if($this->prevFileId && $this->prevFileId != $this->fileId)
				{
					CFile::delete($this->prevFileId);
				}
				return $lastVersion;
			}
		}

		$versionModel = Version::add(array_merge(array(
			'OBJECT_ID' => $this->id,
			'FILE_ID' => $this->fileId,
			'NAME' => $this->name,
			'CREATED_BY' => $createdBy,
		), $this->getHistoricalData()), $this->errorCollection);

		if(!$versionModel)
		{
			return null;
		}

		$valueVersionUf = FileUserType::NEW_FILE_PREFIX . $versionModel->getId();
		/** @var User $createUser */
		$createUser = User::loadById($createdBy);
		if(!$createUser)
		{
			//skip
			return $versionModel;
		}
		$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_VERSION_IN_COMMENT_M');
		if($createUser->getPersonalGender() == 'F')
		{
			$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_VERSION_IN_COMMENT_F');
		}
		foreach($this->getAttachedObjects() as $attache)
		{
			AttachedObject::storeDataByObjectId($this->getId(), array(
				'IS_EDITABLE' => $attache->isEditable(),
				'ALLOW_EDIT' => $attache->getAllowEdit(),
			));

			$attache->getConnector()->addComment($createdBy, array(
				'text' => $text,
				'versionId' => $valueVersionUf,
			));

			AttachedObject::storeDataByObjectId($this->getId(), null);
		}
		unset($attache);

		return $versionModel;
	}

	/**
	 * Uploads new version to file.
	 * @see \Bitrix\Disk\File::addVersion().
	 * @param array $fileArray Structure like $_FILES.
	 * @param int $createdBy Id of user.
	 * @return Version|null
	 */
	public function uploadVersion(array $fileArray, $createdBy)
	{
		$this->errorCollection->clear();

		if(!isset($fileArray['MODULE_ID']))
		{
			$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;
		}

		if(empty($fileArray['type']))
		{
			$fileArray['type'] = '';
		}
		$fileArray['type'] = TypeFile::normalizeMimeType($fileArray['type'], $this->name);

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$fileId = CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE)));
			return null;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$fileArray = CFile::getFileArray($fileId);
		$version = $this->addVersion($fileArray, $createdBy);
		if(!$version)
		{
			CFile::delete($fileId);
		}

		return $version;
	}

	/**
	 * Returns version of file by version id.
	 * @param int $versionId Id of version.
	 * @return static
	 */
	public function getVersion($versionId)
	{
		return Version::load(array(
			'ID' => $versionId,
			'OBJECT_ID' => $this->id,
		));
	}

	/**
	 * Gets last version of the file.
	 * @return Version|null
	 */
	public function getLastVersion()
	{
		$versions = $this->getVersions(array('limit' => 1));
		return array_shift($versions)?: null;
	}

	/**
	 * Returns all versions by file.
	 * @param array $parameters Parameters.
	 * @return Version[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public function getVersions(array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		$parameters['filter']['OBJECT_ID'] = $this->id;

		if(!isset($parameters['order']))
		{
			$parameters['order'] = array(
				'CREATE_TIME' => 'DESC',
			);
		}

		$versions = Version::getModelList($parameters);
		foreach($versions as $version)
		{
			$version->setAttributes(array('OBJECT' => $this));
		}
		unset($version);

		return $versions;
	}

	/**
	 * Restores file from the version.
	 *
	 * The method is similar with (@see Bitrix\Disk\File::addVersion()).
	 *
	 * @param Version $version Version which need to restore.
	 * @param int $createdBy Id of user.
	 * @return bool
	 */
	public function restoreFromVersion(Version $version, $createdBy)
	{
		$this->errorCollection->clear();

		if($version->getObjectId() != $this->id)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_RESTORE_FROM_ANOTHER_OBJECT'), self::ERROR_COULD_NOT_RESTORE_FROM_OBJECT)));
			return false;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$forkFileId = \CFile::copyFile($version->getFileId(), true);

		if(!$forkFileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_COPY_FILE'), self::ERROR_COULD_NOT_COPY_FILE)));
			return false;
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		if($this->addVersion(\CFile::getFileArray($forkFileId), $createdBy, true) === null)
		{
			return false;
		}

		return true;
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		return $this->markDeletedInternal($deletedBy);
	}

	/**
	 * Internal method for deleting file as child of folder.
	 * @param int $deletedBy Id of user.
	 * @param int $deletedType Type of delete (@see ObjectTable::DELETED_TYPE_ROOT, ObjectTable::DELETED_TYPE_CHILD)
	 * @return bool
	 * @internal
	 */
	public function markDeletedInternal($deletedBy, $deletedType = ObjectTable::DELETED_TYPE_ROOT)
	{
		$success = parent::markDeletedInternal($deletedBy, $deletedType);
		if($success)
		{
			DeletedLog::addFile($this, $deletedBy, $this->errorCollection);
		}
		return $success;
	}

	/**
	 * Restores object from trash can.
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function restore($restoredBy)
	{
		$needRecalculate = $this->deletedType == ObjectTable::DELETED_TYPE_CHILD;
		$status = parent::restoreInternal($restoredBy);
		if($status && $needRecalculate)
		{
			$this->recalculateDeletedTypeAfterRestore($restoredBy);
		}
		if($status)
		{
			Driver::getInstance()->sendChangeStatusToSubscribers($this);
		}

		return $status;
	}

	/**
	 * Deletes file and all connected data and entities (@see Sharing, @see Rights, etc).
	 * @param int $deletedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function delete($deletedBy)
	{
		$this->errorCollection->clear();

		$success = EditSessionTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		$success = ExternalLinkTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		foreach($this->getSharingsAsReal() as $sharing)
		{
			$sharing->delete($deletedBy);
		}

		//with status unreplied, declined (not approved)
		$success = SharingTable::deleteByFilter(array(
			'REAL_OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		foreach($this->getAttachedObjects() as $attached)
		{
			$attached->delete();
		}
		unset($attached);

		BizProcDocument::deleteWorkflowsFile($this->id);

		SimpleRightTable::deleteBatch(array('OBJECT_ID' => $this->id));

		$success = RightTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		$success = VersionTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		DeletedLog::addFile($this, $deletedBy, $this->errorCollection);

		\CFile::delete($this->fileId);
		$deleteResult = FileTable::delete($this->id);
		if(!$deleteResult->isSuccess())
		{
			return false;
		}
		Driver::getInstance()->getIndexManager()->dropIndex($this);

		if(!$this->isLink())
		{
			//todo potential - very hard operation.
			foreach(File::getModelList(array('filter' => array('REAL_OBJECT_ID' => $this->id, '!=REAL_OBJECT_ID' => $this->id))) as $link)
			{
				$link->delete($deletedBy);
			}
			unset($link);
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteFile", array($this->getId(), $deletedBy));
		$event->send();

		return true;
	}

	protected function getHistoricalData()
	{
		return array(
			'FILE_ID' => $this->fileId,
			'SIZE' => $this->size,

			'GLOBAL_CONTENT_VERSION' => $this->globalContentVersion,

			'OBJECT_CREATED_BY' => $this->createdBy,
			'OBJECT_UPDATED_BY' => $this->updatedBy,

			'OBJECT_CREATE_TIME'=> $this->createTime,
			'OBJECT_UPDATE_TIME'=> $this->updateTime,
		);
	}

	/**
	 * Returns all attached objects by the file.
	 * @param array $parameters Parameters.
	 * @return AttachedObject[]
	 */
	public function getAttachedObjects(array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		$parameters['filter']['OBJECT_ID'] = $this->id;
		$parameters['filter']['=VERSION_ID'] = null;

		return AttachedObject::getModelList($parameters);
	}

	protected function restoreFromHistoricalData(array $data)
	{}


	protected function updateLinksAttributes(array $attr)
	{
		$possibleToUpdate = array(
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',
			'TYPE_FILE' => 'typeFile',
			'SIZE' => 'size',
			'EXTERNAL_HASH' => 'externalHash',
			'UPDATE_TIME' => 'updateTime',
			'UPDATED_BY' => 'updatedBy',
			'UPDATE_USER' => 'updateUser',
		);
		$attr = array_intersect_key($attr, $possibleToUpdate);
		if($attr)
		{
			parent::updateLinksAttributes($attr);
		}
	}

	/**
	 * Returns the list of pair for mapping.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		static $shelve = null;
		if($shelve !== null)
		{
			return $shelve;
		}

		$shelve = array_merge(parent::getMapAttributes(), array(
			'ID' => 'id',
			'NAME' => 'name',
			'CODE' => 'code',
			'STORAGE_ID' => 'storageId',
			'STORAGE' => 'storage',
			'TYPE' => 'type',
			'REAL_OBJECT_ID' => 'realObjectId',
			'REAL_OBJECT' => 'realObject',
			'PARENT_ID' => 'parentId',
			'DELETED_TYPE' => 'deletedType',
			'TYPE_FILE' => 'typeFile',
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',
			'FILE_ID' => 'fileId',
			'SIZE' => 'size',
			'EXTERNAL_HASH' => 'externalHash',
			'CREATE_TIME' => 'createTime',
			'UPDATE_TIME' => 'updateTime',
			'DELETE_TIME' => 'deleteTime',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
			'UPDATED_BY' => 'updatedBy',
			'UPDATE_USER' => 'updateUser',
			'DELETED_BY' => 'deletedBy',
			'DELETE_USER' => 'deleteUser',
		));

		return $shelve;
	}
}