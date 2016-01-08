<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\Collection;
use CFile;

Loc::loadMessages(__FILE__);

class Folder extends BaseObject
{
	const ERROR_COULD_NOT_DELETE_WITH_CODE = 'DISK_FOLDER_22001';
	const ERROR_COULD_NOT_SAVE_FILE        = 'DISK_FOLDER_22002';

	const CODE_FOR_CREATED_FILES  = 'FOR_CREATED_FILES';
	const CODE_FOR_SAVED_FILES    = 'FOR_SAVED_FILES';
	const CODE_FOR_UPLOADED_FILES = 'FOR_UPLOADED_FILES';

	/** @var bool */
	protected $hasSubFolders;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return FolderTable::className();
	}

	/**
	 * Tells if the folder has sub-folders.
	 * Property {hasSubFolders} fills by using field HAS_SUBFOLDERS in select.
	 * @return boolean
	 */
	public function hasSubFolders()
	{
		return (bool)$this->hasSubFolders;
	}

	/**
	 * Checks rights to add object to current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canAdd(SecurityContext $securityContext)
	{
		return $securityContext->canAdd($this->id);
	}

	/**
	 * Pre-loads all operations for children.
	 * @internal
	 * @param SecurityContext $securityContext Security context.
	 */
	public function preloadOperationsForChildren(SecurityContext $securityContext)
	{
		$securityContext->preloadOperationsForChildren($this->id);
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
		$folder = parent::add($data, $errorCollection);
		if($folder)
		{
			Driver::getInstance()->sendChangeStatusToSubscribers($folder);
		}

		return $folder;
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
		$filter['TYPE'] = ObjectTable::TYPE_FOLDER;

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
	 * Uploads new file to folder.
	 * @param array $fileArray Structure like $_FILES.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function uploadFile(array $fileArray, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$this->checkRequiredInputParams($data, array(
			'NAME', 'CREATED_BY'
		));

		if(!isset($fileArray['MODULE_ID']))
		{
			$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;
		}

		if(empty($fileArray['type']))
		{
			$fileArray['type'] = '';
		}
		$fileArray['type'] = TypeFile::normalizeMimeType($fileArray['type'], $data['NAME']);

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$fileId = CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_MODEL_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE)));
			return null;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$fileArray = CFile::getFileArray($fileId);

		$data['NAME'] = Ui\Text::correctFilename($data['NAME']);
		$fileModel = $this->addFile(array(
			'NAME' => $data['NAME'],
			'FILE_ID' => $fileId,
			'CONTENT_PROVIDER' => isset($data['CONTENT_PROVIDER'])? $data['CONTENT_PROVIDER'] : null,
			'SIZE' => !isset($data['SIZE'])? $fileArray['FILE_SIZE'] : $data['SIZE'],
			'CREATED_BY' => $data['CREATED_BY'],
		), $rights, $generateUniqueName);

		if(!$fileModel)
		{
			CFile::delete($fileId);
			return null;
		}
		return $fileModel;
	}

	/**
	 * Creates blank file (size 0 byte) in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, MIME_TYPE).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @return File|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addBlankFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$this->checkRequiredInputParams($data, array(
			'NAME', 'CREATED_BY', 'MIME_TYPE'
		));

		return $this->uploadFile(array(
			'name' => $data['NAME'],
			'content' => '',
			'type' => $data['MIME_TYPE'],
		), array(
			'NAME' => $data['NAME'],
			'SIZE' => isset($data['SIZE'])? $data['SIZE'] : null, //for im. We should show future!
			'CREATED_BY' => $data['CREATED_BY'],
		), $rights, $generateUniqueName);
	}

	/**
	 * Adds file in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|File
	 */
	public function addFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$this->checkRequiredInputParams($data, array(
			'NAME'
		));

		if($generateUniqueName)
		{
			$data['NAME'] = $this->generateUniqueName($data['NAME'], $this->id);
		}

		if(!$this->isUniqueName($data['NAME'], $this->id))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return null;
		}

		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;
		$data['PARENT'] = $this;

		/** @var File $fileModel */
		$fileModel = File::add($data, $this->errorCollection);
		if(!$fileModel)
		{
			return null;
		}

		Driver::getInstance()->getRightsManager()->setAsNewLeaf($fileModel, $rights);

		$this->notifySonetGroup($fileModel);

		return $fileModel;
	}

	/**
	 * Adds link on file in folder.
	 * @param File  $sourceFile Source file.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function addFileLink(File $sourceFile, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$data = $this->prepareDataForAddLink($sourceFile, $data, $generateUniqueName);
		if(!$data)
		{
			return null;
		}
		$data['SIZE'] = $sourceFile->getSize();

		/** @var FileLink $fileModel */
		$fileLinkModel = FileLink::add($data, $this->errorCollection);
		if(!$fileLinkModel)
		{
			return null;
		}

		$driver = Driver::getInstance();
		$driver->getRightsManager()->setAsNewLeaf($fileLinkModel, $rights);
		$driver->getIndexManager()->indexFile($fileLinkModel);

		$this->notifySonetGroup($fileLinkModel);

		return $fileLinkModel;
	}

	private function notifySonetGroup(File $fileModel)
	{
		//todo create NotifyManager, which provides notify (not only group)
		if(!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$storage = $fileModel->getStorage();
		if(!$storage->getProxyType() instanceof Group)
		{
			return;
		}

		$groupId = (int)$storage->getEntityId();
		if($groupId <= 0)
		{
			return;
		}

		$fileUrl = Driver::getInstance()->getUrlManager()->getPathFileDetail($fileModel);
		$fileCreatedBy = $fileModel->getCreatedBy();
		$fileName = $fileModel->getName();

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		\CSocNetSubscription::notifyGroup(array(
			'LOG_ID' => false,
			'GROUP_ID' => array($groupId),
			'NOTIFY_MESSAGE' => '',
			'FROM_USER_ID' => $fileCreatedBy,
			'URL' => $fileUrl,
			'MESSAGE' => Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE', array(
				'#TITLE#' => '<a href="#URL#" class="bx-notifier-item-action">'.$fileName.'</a>',
			)),
			'MESSAGE_OUT' => Loc::getMessage('DISK_FOLDER_MODEL_IM_NEW_FILE', array(
				'#TITLE#' => $fileName
			)).' (#URL#)',
			'EXCLUDE_USERS' => array($fileCreatedBy)
		));
	}

	/**
	 * Adds sub-folder in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|Folder
	 */
	public function addSubFolder(array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$this->checkRequiredInputParams($data, array(
			'NAME'
		));

		if($generateUniqueName)
		{
			$data['NAME'] = $this->generateUniqueName($data['NAME'], $this->id);
		}

		if(!$this->isUniqueName($data['NAME'], $this->id))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return null;
		}

		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;

		$folderModel = Folder::add($data, $this->errorCollection);
		if(!$folderModel)
		{
			return null;
		}
		Driver::getInstance()->getRightsManager()->setAsNewLeaf($folderModel, $rights);

		return $folderModel;
	}

	/**
	 * Adds link on folder in folder.
	 * @param Folder $sourceFolder Original folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return FolderLink|null
	 */
	public function addSubFolderLink(Folder $sourceFolder, array $data, array $rights = array(), $generateUniqueName = false)
	{
		$this->errorCollection->clear();

		$data = $this->prepareDataForAddLink($sourceFolder, $data, $generateUniqueName);
		if(!$data)
		{
			return null;
		}

		$fileLinkModel = FolderLink::add($data, $this->errorCollection);
		if(!$fileLinkModel)
		{
			return null;
		}
		Driver::getInstance()->getRightsManager()->setAsNewLeaf($fileLinkModel, $rights);

		return $fileLinkModel;
	}

	private function prepareDataForAddLink(BaseObject $object, array $data, $generateUniqueName = false)
	{
		if(empty($data['NAME']))
		{
			$data['NAME'] = $object->getName();
		}

		$this->checkRequiredInputParams($data, array(
			'NAME'
		));

		if($generateUniqueName)
		{
			$data['NAME'] = $this->generateUniqueName($data['NAME'], $this->id);
		}

		if(!$this->isUniqueName($data['NAME'], $this->id))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return null;
		}

		$data['PARENT_ID'] = $this->id;
		$data['STORAGE_ID'] = $this->storageId;
		$data['REAL_OBJECT_ID'] = $object->getRealObject()->getId();

		return $data;
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

		if($this->getId() == $targetFolder->getId())
		{
			return $this;
		}

		$newRoot = $this->copyToInternal($targetFolder, $updatedBy, $generateUniqueName);
		if(!$newRoot)
		{
			return null;
		}
		$mapParentToNewParent = array(
			$this->getId() => $newRoot,
		);

		$fakeSecurityContext = Driver::getInstance()->getFakeSecurityContext();
		foreach ($this->getDescendants($fakeSecurityContext) as $item)
		{
			if(!isset($mapParentToNewParent[$item->getParentId()]))
			{
				return null;
			}

			/** @var Folder $newParentFolder */
			$newParentFolder = $mapParentToNewParent[$item->getParentId()];
			if($item instanceof File)
			{
				/** @var \Bitrix\Disk\File $item */
				$item->copyTo($newParentFolder, $updatedBy, $generateUniqueName);
			}
			elseif ($item instanceof Folder)
			{
				/** @var \Bitrix\Disk\Folder $item */
				$newFolder = $item->copyToInternal($newParentFolder, $updatedBy, $generateUniqueName);
				if(!$newFolder)
				{
					continue;
				}
				$mapParentToNewParent[$item->getId()] = $newFolder;
			}
		}

		return $newRoot;
	}

	protected function copyToInternal(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$newFolder = $targetFolder->addSubFolder(array(
			'NAME' => $this->getName(),
			'CREATED_BY' => $updatedBy,
		), array(), $generateUniqueName);

		if(!$newFolder)
		{
			$this->errorCollection->add($targetFolder->getErrors());
			return null;
		}
		return $newFolder;
	}

	/**
	 * Gets all descendants objects by the folder.
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @param int             $orderDepthLevel Order for depth level (default asc).
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @return BaseObject[]
	 */
	public function getDescendants(SecurityContext $securityContext, array $parameters = array(), $orderDepthLevel = SORT_ASC)
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
		elseif(!array_key_exists('DELETED_TYPE', $parameters['filter']) && !array_key_exists('!DELETED_TYPE', $parameters['filter']) && !array_key_exists('!=DELETED_TYPE', $parameters['filter']))
		{
			$parameters['filter']['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}
		$parameters['select']['DEPTH_LEVEL'] = 'PATH_CHILD.DEPTH_LEVEL';
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$data = FolderTable::getDescendants($this->id, static::prepareGetListParameters($parameters))->fetchAll();
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
	 * Gets direct children (files, folders).
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @return BaseObject[]
	 */
	public function getChildren(SecurityContext $securityContext, array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		if(!empty($parameters['filter']['MIXED_SHOW_DELETED']))
		{
			unset($parameters['filter']['DELETED_TYPE'], $parameters['filter']['MIXED_SHOW_DELETED']);
		}
		elseif(!array_key_exists('DELETED_TYPE', $parameters['filter']) && !array_key_exists('!DELETED_TYPE', $parameters['filter']) && !array_key_exists('!=DELETED_TYPE', $parameters['filter']))
		{
			$parameters['filter']['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$modelData = array();
		$query = FolderTable::getChildren($this->id, static::prepareGetListParameters($parameters));
		while($item = $query->fetch())
		{
			$modelData[] = BaseObject::buildFromArray($item);
		}

		return $modelData;
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		$this->errorCollection->clear();

		foreach($this->getDescendants(
			Storage::getFakeSecurityContext(),
			array('filter' => array('MIXED_SHOW_DELETED' => true,)),
			SORT_DESC
		) as $object)
		{
			/** @var Folder|File */
			if($object instanceof Folder)
			{
				$object->markDeletedNonRecursiveInternal($deletedBy, ObjectTable::DELETED_TYPE_CHILD);
			}
			elseif($object instanceof File)
			{
				$object->markDeletedInternal($deletedBy, ObjectTable::DELETED_TYPE_CHILD);
			}
		}

		return $this->markDeletedNonRecursiveInternal($deletedBy);
	}

	protected function markDeletedNonRecursiveInternal($deletedBy, $deletedType = ObjectTable::DELETED_TYPE_ROOT)
	{
		$success = parent::markDeletedInternal($deletedBy, $deletedType);
		if($success)
		{
			DeletedLog::addFolder($this, $deletedBy, $this->errorCollection);
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
		$this->errorCollection->clear();

		$fakeContext = Storage::getFakeSecurityContext();
		foreach($this->getDescendants(
			$fakeContext,
			array('filter' => array('!DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,)),
			SORT_DESC
		) as $object)
		{
			/** @var Folder|File */
			if($object instanceof Folder)
			{
				$object->restoreNonRecursive($restoredBy);
			}
			elseif($object instanceof File)
			{
				$object->restoreInternal($restoredBy);
			}
		}
		$needRecalculate = $this->deletedType == ObjectTable::DELETED_TYPE_CHILD;
		$statusRestoreNonRecursive = $this->restoreInternal($restoredBy);
		if($statusRestoreNonRecursive && $needRecalculate)
		{
			$this->recalculateDeletedTypeAfterRestore($restoredBy);
		}
		if($statusRestoreNonRecursive)
		{
			Driver::getInstance()->sendChangeStatusToSubscribers($this);
		}

		return $statusRestoreNonRecursive;
	}

	protected function restoreNonRecursive($restoredBy)
	{
		return parent::restoreInternal($restoredBy);
	}

	/**
	 * Deletes folder and all descendants objects.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return bool
	 */
	public function deleteTree($deletedBy)
	{
		$this->errorCollection->clear();

		foreach($this->getDescendants(Storage::getFakeSecurityContext(), array('filter' => array('MIXED_SHOW_DELETED' => true)), SORT_DESC) as $object)
		{
			/** @var Folder|File */
			if($object instanceof Folder)
			{
				$object->deleteNonRecursive($deletedBy);
			}
			elseif($object instanceof File)
			{
				$object->delete($deletedBy);
			}
		}
		unset($object);

		return $this->deleteNonRecursive($deletedBy);
	}

	protected function deleteNonRecursive($deletedBy)
	{
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

		SimpleRightTable::deleteBatch(array('OBJECT_ID' => $this->id));

		$success = RightTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		DeletedLog::addFolder($this, $deletedBy, $this->errorCollection);

		$resultDelete = FolderTable::delete($this->id);
		if(!$resultDelete->isSuccess())
		{
			return false;
		}

		if(!$this->isLink())
		{
			//todo potential - very hard operation.
			foreach(Folder::getModelList(array('filter' => array('REAL_OBJECT_ID' => $this->id, '!=REAL_OBJECT_ID' => $this->id))) as $link)
			{
				$link->deleteTree($deletedBy);
			}
			unset($link);
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteFolder", array($this->getId(), $deletedBy));
		$event->send();

		return true;
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
			'HAS_SUBFOLDERS' => 'hasSubFolders',
		));

		return $shelve;
	}
}

/**
 * Class SpecificFolder
 * @package Bitrix\Disk
 * @internal
 */
final class SpecificFolder
{
	const CODE_FOR_CREATED_FILES  = 'FOR_CREATED_FILES';
	const CODE_FOR_SAVED_FILES    = 'FOR_SAVED_FILES';
	const CODE_FOR_UPLOADED_FILES = 'FOR_UPLOADED_FILES';

	const CODE_FOR_IMPORT_DROPBOX  = 'FOR_DROPBOX_FILES';
	const CODE_FOR_IMPORT_ONEDRIVE = 'FOR_ONEDRIVE_FILES';
	const CODE_FOR_IMPORT_GDRIVE   = 'FOR_GDRIVE_FILES';
	const CODE_FOR_IMPORT_BOX      = 'FOR_BOX_FILES';
	const CODE_FOR_IMPORT_YANDEX   = 'FOR_YANDEXDISK_FILES';

	/**
	 * Gets name for specific folder by code. If code is invalid, then return null.
	 * @param string $code Code of specific folder.
	 * @return null|string
	 */
	public static function getName($code)
	{
		$codes = static::getCodes();
		if(!isset($codes[$code]))
		{
			return null;
		}
		return Loc::getMessage("DISK_FOLDER_SPECIFIC_{$code}_NAME");
	}

	/**
	 * Gets specific folder in storage by code. If folder does not exist, creates it.
	 * @param Storage $storage Target storage.
	 * @param string $code Code of specific folder.
	 * @return Folder|null|static
	 */
	public static function getFolder(Storage $storage, $code)
	{
		$folder = Folder::load(array(
			'=CODE' => $code,
			'STORAGE_ID' => $storage->getId(),
		));
		if($folder)
		{
			return $folder;
		}

		return static::createFolder($storage, $code);
	}

	protected static function createFolder(Storage $storage, $code)
	{
		$name = static::getName($code);
		if(!$name)
		{
			return null;
		}

		if($storage->getProxyType() instanceof ProxyType\User)
		{
			$createdBy = $storage->getEntityId();
		}
		else
		{
			$createdBy = SystemUser::SYSTEM_USER_ID;
		}

		if(static::shouldBeUnderUploadedFolder($code))
		{
			$folderForUploadedFiles = $storage->getFolderForUploadedFiles();
			if(!$folderForUploadedFiles)
			{
				return null;
			}
			return $folderForUploadedFiles->addSubFolder(array(
				'NAME' => $name,
				'CODE' => $code,
				'CREATED_BY' => $createdBy
			), array(), true);
		}

		return $storage->addFolder(array(
			'NAME' => $name,
			'CODE' => $code,
			'CREATED_BY' => $createdBy
		), array(), true);
	}

	protected static function shouldBeUnderUploadedFolder($code)
	{
		return
			static::CODE_FOR_IMPORT_DROPBOX === $code ||
			static::CODE_FOR_IMPORT_ONEDRIVE === $code ||
			static::CODE_FOR_IMPORT_YANDEX === $code ||
			static::CODE_FOR_IMPORT_BOX === $code ||
			static::CODE_FOR_IMPORT_GDRIVE === $code;
	}

	protected static function getCodes()
	{
		static $codes = null;
		if($codes !== null)
		{
			return $codes;
		}
		$refClass = new \ReflectionClass(__CLASS__);
		foreach($refClass->getConstants() as $name => $value)
		{
			if(substr($name, 0, 4) === 'CODE')
			{
				$codes[$value] = $value;
			}
		}
		unset($name, $value);

        return $codes;
	}
}