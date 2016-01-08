<?php

namespace Bitrix\Disk;


use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class Sharing extends Internals\Model
{
	const ERROR_EMPTY_USER_ID               = 'DISK_SHARING_22002';
	const ERROR_EMPTY_REAL_OBJECT           = 'DISK_SHARING_22003';
	const ERROR_COULD_NOT_FIND_USER_STORAGE = 'DISK_SHARING_22004';
	const ERROR_COULD_NOT_FIND_STORAGE      = 'DISK_SHARING_22005';
	const ERROR_COULD_NOT_CREATE_LINK       = 'DISK_SHARING_22006';

	const CODE_USER         = 'U';
	const CODE_GROUP        = 'G';
	const CODE_SOCNET_GROUP = 'SG';
	const CODE_DEPARTMENT   = 'DR';

	/** @var int */
	protected $parentId;
	/** @var Sharing */
	protected $parent;
	/** @var array */
	protected $children;
	/** @var int */
	protected $createdBy;
	/** @var User */
	protected $createUser;
	/** @var string */
	protected $toEntity;
	/** @var string */
	protected $fromEntity;
	/** @var int */
	protected $linkObjectId;
	/** @var int */
	protected $linkStorageId;
	/** @var BaseObject */
	protected $linkObject;
	/** @var Storage */
	protected $linkStorage;
	/** @var int */
	protected $realObjectId;
	/** @var int */
	protected $realStorageId;
	/** @var BaseObject */
	protected $realObject;
	/** @var Storage */
	protected $realStorage;
	/** @var string */
	protected $description;
	/** @var bool */
	protected $canForward;
	/** @var int */
	protected $type;
	/** @var int */
	protected $status;
	/** @var string */
	protected $taskName;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return SharingTable::className();
	}

	/**
	 * @param array           $data
	 * @param ErrorCollection $errorCollection
	 * @return null|Sharing
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		$successSharing = static::addToManyEntities($data, array($data['TO_ENTITY'] => $data['TASK_NAME']), $errorCollection);

		if($successSharing === null || !isset($successSharing[$data['TO_ENTITY']]))
		{
			return null;
		}

		return $successSharing[$data['TO_ENTITY']];
	}

	public static function addToManyEntities(array $data, array $entitiesToTask, ErrorCollection $errorCollection)
	{
		self::checkRequiredInputParams($data, array('FROM_ENTITY', 'CREATED_BY'));

		if(isset($data['REAL_OBJECT']) && $data['REAL_OBJECT'] instanceof BaseObject)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$data['REAL_OBJECT_ID'] = $data['REAL_OBJECT']->getId();
		}
		elseif(isset($data['REAL_OBJECT_ID']))
		{
			$data['REAL_OBJECT'] = BaseObject::loadById($data['REAL_OBJECT_ID']);
		}
		else
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_SHARING_MODEL_ERROR_EMPTY_REAL_OBJECT'), self::ERROR_EMPTY_REAL_OBJECT)));
			return null;
		}
		/** @var \Bitrix\Disk\BaseObject $objectToSharing */
		$objectToSharing = $data['REAL_OBJECT'];
		//resolve to last real object. In table we write only real (not link) id.
		$objectToSharing = $objectToSharing->getRealObject();
		$data['REAL_OBJECT_ID'] = $objectToSharing->getId();
		$data['REAL_STORAGE_ID'] = $objectToSharing->getStorageId();

		$dataToInsert = $data;
		unset($dataToInsert['REAL_OBJECT']);

		//we don't have to connect object, which already exists in same storage
		$ownerUserId = null;
		if($objectToSharing->getStorage()->getProxyType() instanceof ProxyType\User)
		{
			$ownerUserId = $objectToSharing->getStorage()->getEntityId();
		}

		$rightManager = Driver::getInstance()->getRightsManager();
		/** @var Sharing[] $successSharingByEntity */
		$successSharingByEntity = array();
		foreach ($entitiesToTask as $entity => $taskName)
		{
			list($type, ) = self::parseEntityValue($entity);
			if(!$type)
			{
				continue;
			}
			$dataToInsert['TO_ENTITY'] = $entity;
			$dataToInsert['TYPE'] = $type;
			$dataToInsert['TASK_NAME'] = $taskName;
			if($type == SharingTable::TYPE_TO_DEPARTMENT)
			{
				$dataToInsert['STATUS'] = SharingTable::STATUS_IS_APPROVED;
			}

			$sharingModel = parent::add($dataToInsert, $errorCollection);
			if(!$sharingModel)
			{
				continue;
			}

			$successSharingByEntity[$entity] = $sharingModel->setAttributes(array('REAL_OBJECT' => $objectToSharing));
			if($type == SharingTable::TYPE_TO_DEPARTMENT && Loader::includeModule('socialnetwork'))
			{
				unset($dataToInsert['STATUS']);
				//todo expand access code DR to list of users. And for each user of list create Sharing
				$dataToInsertChild = $dataToInsert;
				$dataToInsertChild['PARENT_ID'] = $sharingModel->getId();
				$dataToInsertChild['TYPE'] = SharingTable::TYPE_TO_USER;

				foreach(\CSocNetLogDestination::getDestinationUsers(array($entity)) as $userId)
				{
					if($ownerUserId == $userId)
					{
						continue;
					}

					$dataToInsertChild['TO_ENTITY'] = self::CODE_USER . $userId;

					$sharingModel = parent::add($dataToInsertChild, $errorCollection);
					if(!$sharingModel)
					{
						continue;
					}
					//above we can already added sharing to this entity (user) and we should not overwrite that. DR-sharing has lower priority than another.
					if(!isset($successSharingByEntity[$dataToInsertChild['TO_ENTITY']]))
					{
						$successSharingByEntity[$dataToInsertChild['TO_ENTITY']] = $sharingModel->setAttributes(array('REAL_OBJECT' => $objectToSharing,));
					}
				}
				unset($userId);
			}
			elseif($type == SharingTable::TYPE_TO_GROUP)
			{
				//if sharing to socnet group, we should approve invite at once, because it's not personal invite.
				$sharingModel->approve();
			}

		}
		unset($entity, $dataToInsert);

		$forwardTaskId = !empty($data['CAN_FORWARD'])? $rightManager->getTaskIdByName($rightManager::OP_SHARING) : null;

		$newRights = array();
		foreach ($successSharingByEntity as $entity => $sharingModel)
		{
			if($sharingModel->isToDepartmentChild())
			{
				continue;
			}
			$sharingDomain = $rightManager->getSharingDomain($sharingModel->getId());
			/** @var \Bitrix\Disk\Sharing $sharingModel */
			$newRights[] = array(
				'ACCESS_CODE' => $entity,
				'TASK_ID' => $rightManager->getTaskIdByName($sharingModel->getTaskName()),
				'DOMAIN' => $sharingDomain,
			);
			if($forwardTaskId)
			{
				$newRights[] = array(
					'ACCESS_CODE' => $entity,
					'TASK_ID' => $forwardTaskId,
					'DOMAIN' => $sharingDomain,
				);
			}
		}
		unset($entity);
		$rightManager->append($objectToSharing, $newRights);

		self::processConnectAndNotify($successSharingByEntity, $objectToSharing);

		return $successSharingByEntity;
	}

	/**
	 * Parse entity and return null if could not parse or array(TYPE, entity id)
	 * Ex. SG444 = array('SG', 444)
	 * @param $entity
	 * @return array|null
	 */
	public static function parseEntityValue($entity)
	{
		preg_match("%(" . self::CODE_USER . "|" . self::CODE_SOCNET_GROUP . "|" . self::CODE_DEPARTMENT . "){1,2}([0-9]+)%u", $entity, $m);
		list(, $code, $id) = $m;
		if($code === null || $id === null)
		{
			return null;
		}
		switch($code)
		{
			case self::CODE_USER:
				return array(SharingTable::TYPE_TO_USER, $id);
			case self::CODE_SOCNET_GROUP:
				return array(SharingTable::TYPE_TO_GROUP, $id);
			case self::CODE_DEPARTMENT:
				return array(SharingTable::TYPE_TO_DEPARTMENT, $id);
		}
		return null;
	}


	public static function connectGroupToSelfUserStorage($userId, Storage $storage, ErrorCollection $errorCollection)
	{
		return self::connectToUserStorage($userId, array(
			'SELF_CONNECT' => true,
			'CREATED_BY' => $userId,
			'LINK_NAME' => Ui\Text::correctFolderName($storage->getProxyType()->getEntityTitle()),
			'REAL_OBJECT' => $storage->getRootObject(),
		), $errorCollection);
	}

	public static function connectObjectToSelfUserStorage($userId, BaseObject $object, ErrorCollection $errorCollection)
	{
		return self::connectToUserStorage($userId, array(
			'SELF_CONNECT' => true,
			'CREATED_BY' => $userId,
			'REAL_OBJECT' => $object,
		), $errorCollection);
	}

	/**
	 * Connect object $data['REAL_OBJECT'] (or $data['REAL_OBJECT_ID']) to user storage
	 * $data['SELF_CONNECT'] - don't ask about connecting object,
	 * @param                 $userId
	 * @param array           $data
	 * @param ErrorCollection $errorCollection
	 * @return Sharing|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function connectToUserStorage($userId, array $data, ErrorCollection $errorCollection)
	{
		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			$storage = Driver::getInstance()->addUserStorage($userId);
		}
		if(!$storage)
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_SHARING_MODEL_ERROR_COULD_NOT_FIND_USER_STORAGE', array('#USER_ID#' => $userId)), self::ERROR_COULD_NOT_FIND_USER_STORAGE)));
			return null;
		}

		$selfConnect = !empty($data['SELF_CONNECT']);
		$linkName = !empty($data['LINK_NAME'])? $data['LINK_NAME'] : null;
		unset($data['SELF_CONNECT'], $data['LINK_NAME']);

		$data['TYPE'] = SharingTable::TYPE_TO_USER;
		$data['FROM_ENTITY'] = self::CODE_USER . (int)$userId;
		$data['TO_ENTITY'] = self::CODE_USER . (int)$userId;
		$data['TASK_NAME'] = RightsManager::TASK_READ;

		if(isset($data['REAL_OBJECT']) && $data['REAL_OBJECT'] instanceof BaseObject)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$data['REAL_OBJECT_ID'] = $data['REAL_OBJECT']->getId();
		}
		elseif(isset($data['REAL_OBJECT_ID']))
		{
			$data['REAL_OBJECT'] = BaseObject::loadById($data['REAL_OBJECT_ID']);
		}
		else
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_SHARING_MODEL_ERROR_EMPTY_REAL_OBJECT'), self::ERROR_EMPTY_REAL_OBJECT)));
			return null;
		}
		/** @var \Bitrix\Disk\BaseObject $objectToSharing */
		$objectToSharing = $data['REAL_OBJECT'];
		//resolve to last real object. In table we write only real (not link) id.
		$objectToSharing = $objectToSharing->getRealObject();
		$data['REAL_OBJECT_ID'] = $objectToSharing->getId();
		$data['REAL_STORAGE_ID'] = $objectToSharing->getStorageId();

		$dataToInsert = $data;
		unset($dataToInsert['REAL_OBJECT']);

		$sharingModel = parent::add($dataToInsert, $errorCollection);
		if(!$sharingModel)
		{
			return null;
		}
		$sharingModel->setAttributes(array('REAL_OBJECT' => $objectToSharing));

		if(!$selfConnect)
		{
			self::processConnectAndNotify(array($sharingModel), $objectToSharing);
		}
		else
		{
			if(!$sharingModel->approve(array('LINK_NAME' => $linkName)))
			{
				$errorCollection->add($sharingModel->getErrors());
				$sharingModel->delete($userId);
				return null;
			}
		}

		return $sharingModel;
	}

	/**
	 * Check is connected or not BaseObject (File|Folder) to user storage.
	 * It means row in SharingTable.
	 * @param               $userId
	 * @param BaseObject|BaseObject $object
	 * @param array         $returnData Special for optimization we get fields and return by link.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function isConnectedToUserStorage($userId, BaseObject $object, array &$returnData = array())
	{
		$userId = (int)$userId;
		$returnData = SharingTable::getList(array(
			'select' => array('REAL_OBJECT_ID', 'LINK_OBJECT_ID'),
			'filter' => array(
				'REAL_OBJECT_ID' => $object->getRealObjectId(),
				'=TO_ENTITY' => self::CODE_USER . $userId,
				'=STATUS' => SharingTable::STATUS_IS_APPROVED,
			),
			'limit' => 1,
		))->fetch();

		return (bool)$returnData;
	}

	/**
	 * Autoconnect, notify.
	 * @param Sharing[]                 $successSharingByEntity
	 * @param File|Folder|BaseObject $objectToSharing
	 */
	protected static function processConnectAndNotify(array $successSharingByEntity, BaseObject $objectToSharing)
	{
		$isFolder = $objectToSharing instanceof Folder;
		if(Configuration::canAutoconnectSharedObjects())
		{
			$urlManager = Driver::getInstance()->getUrlManager();
			foreach($successSharingByEntity as $entity => $sharingModel)
			{
				/** @var \Bitrix\Disk\Sharing $sharingModel */
				if(!$sharingModel->approve())
				{
					unset($successSharingByEntity[$entity]);
				}
				else
				{
					if(!$sharingModel->isToUser())
					{
						continue;
					}
					$pathInListing = $urlManager->getPathInListing($sharingModel->getLinkObject()) . "#hl-{$sharingModel->getLinkObjectId()}";
					$uriToDisconnect = $pathInListing . "!detach";
					$message = Loc::getMessage($isFolder ? 'DISK_SHARING_MODEL_AUTOCONNECT_NOTIFY' : 'DISK_SHARING_MODEL_AUTOCONNECT_NOTIFY_FILE', array(
						'#NAME#' => '<a href="' . $pathInListing . '">' . $objectToSharing->getName() . '</a>',
						'#DESCRIPTION#' => $sharingModel->getDescription(),
						'#DISCONNECT_LINK#' => '<a href="' . $uriToDisconnect . '">' . Loc::getMessage('DISK_SHARING_MODEL_TEXT_DISCONNECT_LINK') . '</a>',
					));
					list($subTag, $tag) = $sharingModel->getNotifyTags();
					Driver::getInstance()->sendNotify(substr($sharingModel->getToEntity(), 1), array(
						'FROM_USER_ID' => $sharingModel->getCreatedBy(),
						'NOTIFY_EVENT' => 'sharing',
						'NOTIFY_TAG' => $tag,
						'NOTIFY_SUB_TAG' => $subTag,
						'NOTIFY_MESSAGE' => $message,
						'NOTIFY_MESSAGE_OUT' => strip_tags($message),
					));
				}
			}
		}
		else
		{
			$buttons = array(
				array(
					'TITLE' => Loc::getMessage($isFolder ? 'DISK_SHARING_MODEL_APPROVE_Y' : 'DISK_SHARING_MODEL_APPROVE_Y_FILE'),
					'VALUE' => 'Y',
					'TYPE' => 'accept'
				),
				array(
					'TITLE' => Loc::getMessage('DISK_SHARING_MODEL_APPROVE_N_2_DECLINE'),
					'VALUE' => 'N',
					'TYPE' => 'cancel'
				)
			);
			$message = Loc::getMessage($isFolder ? 'DISK_SHARING_MODEL_TEXT_APPROVE_CONFIRM' : 'DISK_SHARING_MODEL_TEXT_APPROVE_CONFIRM_FILE', array(
				'#NAME#' => $objectToSharing->getName(),
			));

			foreach($successSharingByEntity as $entity => $sharingModel)
			{
				if(!$sharingModel->isToUser())
				{
					continue;
				}
				list($subTag, $tag) = $sharingModel->getNotifyTags();
				Driver::getInstance()->sendNotify(substr($sharingModel->getToEntity(), 1), array(
					'NOTIFY_BUTTONS' => $buttons,
					'NOTIFY_TYPE' => 'IM_NOTIFY_CONFIRM',
					'FROM_USER_ID' => $sharingModel->getCreatedBy(),
					'NOTIFY_EVENT' => 'sharing',
					'NOTIFY_TAG' => $tag,
					'NOTIFY_SUB_TAG' => $subTag,
					'NOTIFY_MESSAGE' => $message,
					'NOTIFY_MESSAGE_OUT' => strip_tags($message),
				));
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function isCanForward()
	{
		return $this->canForward;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return boolean
	 */
	public function isApproved()
	{
		return $this->status == SharingTable::STATUS_IS_APPROVED;
	}

	/**
	 * @return boolean
	 */
	public function isDeclined()
	{
		return $this->status == SharingTable::STATUS_IS_DECLINED;
	}

	/**
	 * @return boolean
	 */
	public function isUnreplied()
	{
		return $this->status == SharingTable::STATUS_IS_UNREPLIED;
	}

	/**
	 * @return boolean
	 */
	public function isToUser()
	{
		return $this->type == SharingTable::TYPE_TO_USER;
	}

	/**
	 * @return boolean
	 */
	public function isToGroup()
	{
		return $this->type == SharingTable::TYPE_TO_GROUP;
	}

	/**
	 * @return boolean
	 */
	public function isToDepartment()
	{
		return $this->type == SharingTable::TYPE_TO_DEPARTMENT;
	}

	/**
	 * Parent sharing to department.
	 * @return boolean
	 */
	public function isToDepartmentParent()
	{
		return !$this->parentId && $this->isToDepartment();
	}

	/**
	 * Child sharing to department. Born by parent sharing
	 * @return boolean
	 */
	public function isToDepartmentChild()
	{
		//todo Now I dont know what child sharing on user of department will be with type TYPE_TO_USER, or with TO_DEPARTMENT
		return $this->parentId && $this->isToUser();
	}

	/**
	 * @return string
	 */
	public function getTaskName()
	{
		return $this->taskName;
	}

	/**
	 * @return int
	 */
	public function getLinkObjectId()
	{
		return $this->linkObjectId;
	}

	/**
	 * @return int
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * @return Sharing|null
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
		$this->parent = Sharing::loadById($this->parentId);

		return $this->parent;
	}

	/**
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
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
	 * @return string
	 */
	public function getToEntity()
	{
		return $this->toEntity;
	}

	/**
	 * @return int
	 */
	public function getLinkStorageId()
	{
		return $this->linkStorageId;
	}

	/**
	 * @return Storage|null
	 */
	public function getLinkStorage()
	{
		if(!$this->linkStorageId)
		{
			return null;
		}

		if(isset($this->linkStorage) && $this->linkStorageId === $this->linkStorage->getId())
		{
			return $this->linkStorage;
		}
		$this->linkStorage = Storage::loadById($this->linkStorageId);

		return $this->linkStorage;
	}

	/**
	 * @return int
	 */
	public function getRealStorageId()
	{
		return $this->realStorageId;
	}

	/**
	 * @return Storage|null
	 */
	public function getRealStorage()
	{
		if(!$this->realStorageId)
		{
			return null;
		}

		if(isset($this->realStorage) && $this->realStorageId === $this->realStorage->getId())
		{
			return $this->realStorage;
		}
		$this->realStorage = Storage::loadById($this->realStorageId);

		return $this->realStorage;
	}

	/**
	 * @return null|\Bitrix\Disk\BaseObject
	 */
	public function getLinkObject()
	{
		if(!$this->linkObjectId)
		{
			return null;
		}

		if(isset($this->linkObject) && $this->linkObjectId === $this->linkObject->getId())
		{
			return $this->linkObject;
		}
		$this->linkObject = BaseObject::loadById($this->linkObjectId);

		return $this->linkObject;
	}

	/**
	 * @return BaseObject
	 */
	public function getRealObject()
	{
		if(!$this->realObjectId)
		{
			return null;
		}

		if(isset($this->realObject) && $this->realObjectId === $this->realObject->getId())
		{
			return $this->realObject;
		}
		$this->realObject = BaseObject::loadById($this->realObjectId);

		return $this->realObject;
	}

	/**
	 * @return int
	 */
	public function getRealObjectId()
	{
		return $this->realObjectId;
	}

	protected function getTargetStorageByToEntity()
	{
		if(!$this->toEntity)
		{
			return null;
		}
		switch($this->type)
		{
			case SharingTable::TYPE_TO_USER:
				if(substr($this->toEntity, 0, 1) !== self::CODE_USER)
				{
					return null;
				}
				$userId = (int)substr($this->toEntity, 1);
				$storage = Driver::getInstance()->getStorageByUserId($userId);
				if(!$storage)
				{
					$storage = Driver::getInstance()->addUserStorage($userId);
				}
				return $storage;
			case SharingTable::TYPE_TO_GROUP:
				if(substr($this->toEntity, 0, 2) !== self::CODE_SOCNET_GROUP)
				{
					return null;
				}
				return Driver::getInstance()->getStorageByGroupId((int)substr($this->toEntity, 2));
		}
		return null;
	}

	public function changeTaskName($newTaskName)
	{
		if($this->taskName == $newTaskName)
		{
			return true;
		}

		$success = $this->update(array(
			'TASK_NAME' => $newTaskName,
		));
		if(!$success)
		{
			return false;
		}
		if($this->isToDepartmentParent())
		{
			SharingTable::updateBatch(array('TASK_NAME' => $newTaskName,), array('PARENT_ID' => $this->id));
			if($this->isLoadedChildren())
			{
				foreach($this->getChildren() as $child)
				{
					$child->setAttributes(array('TASK_NAME' => $newTaskName,));
				}
				unset($child);
			}
		}

		return true;
	}

	/**
	 * Attach link after approve sharing.
	 * @param array $data
	 * @return bool
	 */
	public function approve(array $data = array())
	{
		$this->errorCollection->clear();

		if($this->isApproved())
		{
			return true;
		}

		if($this->isDeclined())
		{
			return false;
		}

		if($this->isToDepartmentParent())
		{
			return true;
		}

		$targetStorage = $this->getTargetStorageByToEntity();
		if(!$targetStorage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_SHARING_MODEL_ERROR_COULD_NOT_FIND_STORAGE'), self::ERROR_COULD_NOT_FIND_STORAGE)));
			return false;
		}

		$realObject = $this->getRealObject();
		$linkModel = null;
		if($this->isToUser() || $this->isToGroup() || $this->isToDepartmentChild())
		{
			$linkName = empty($data['LINK_NAME'])? null : $data['LINK_NAME'];
			if($realObject instanceof Folder)
			{
				$linkModel = $targetStorage->addFolderLink($realObject, array('NAME' => $linkName, 'CREATED_BY' => $this->createdBy), array(), true);
			}
			elseif($realObject instanceof File)
			{
				$linkModel = $targetStorage->addFileLink($realObject, array('NAME' => $linkName, 'CREATED_BY' => $this->createdBy), array(), true);
			}
		}

		if(!$linkModel)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_SHARING_MODEL_ERROR_COULD_NOT_CREATE_LINK'), self::ERROR_COULD_NOT_CREATE_LINK)));
			$this->errorCollection->add($targetStorage->getErrors());
			return false;
		}

		$success = $this->update(array(
			'LINK_OBJECT_ID' => $linkModel->getId(),
			'LINK_STORAGE_ID' => $linkModel->getStorageId(),
			'STATUS' => SharingTable::STATUS_IS_APPROVED,
		));
		if(!$success)
		{
			return false;
		}
		$this->linkObject = $linkModel;

		return true;
	}

	/**
	 * @param      $declinedBy
	 * @param bool $withDeletingObject
	 * @return bool
	 */
	public function decline($declinedBy, $withDeletingObject = true)
	{
		$this->errorCollection->clear();

		if($this->isDeclined())
		{
			return true;
		}

		if(
			$withDeletingObject &&
			($this->isToUser() || $this->isToGroup() || $this->isToDepartmentChild())
		)
		{
			$linkModel = $this->getLinkObject();
			if($linkModel instanceof FolderLink)
			{
				$linkModel->deleteTree($declinedBy);
			}
			elseif($linkModel instanceof FileLink)
			{
				$linkModel->deleteWithoutSharing($declinedBy);
			}
		}

		$success = $this->update(array(
			'LINK_OBJECT_ID' => null,
			'LINK_STORAGE_ID' => null,
			'STATUS' => SharingTable::STATUS_IS_DECLINED,
		));
		if(!$success)
		{
			return false;
		}

		foreach($this->getChildren() as $childSharing)
		{
			$childSharing->decline($declinedBy, $withDeletingObject);
		}
		unset($childSharing);

		if(!$this->getRealObject())
		{
			return true;
		}

		if($this->isToUser() || $this->isToGroup() || $this->isToDepartmentParent())
		{
			$rightsManager = Driver::getInstance()->getRightsManager();
			$rightsManager->deleteByDomain($this->getRealObject(), $rightsManager->getSharingDomain($this->id));
		}

		if($this->isToUser() && !$this->isToDepartmentChild() && self::CODE_USER . $declinedBy == $this->toEntity && $this->fromEntity != $this->toEntity)
		{
			$isFolder = $this->getRealObject() instanceof Folder;
			$message = Loc::getMessage($isFolder? 'DISK_SHARING_MODEL_TEXT_SELF_DISCONNECT' : 'DISK_SHARING_MODEL_TEXT_SELF_DISCONNECT_FILE', array(
				'#NAME#' => $this->getRealObject()->getName(),
				'#USERNAME#' => User::loadById($declinedBy)->getFormattedName(),
			));
			list($subTag, $tag) = $this->getNotifyTags();
			Driver::getInstance()->sendNotify($this->createdBy, array(
				'FROM_USER_ID' => $declinedBy,
				'NOTIFY_EVENT' => 'sharing',
				'NOTIFY_TAG' => $tag,
	//			'NOTIFY_SUB_TAG' => $subTag,
				'NOTIFY_MESSAGE' => $message,
				'NOTIFY_MESSAGE_OUT' => strip_tags($message),
			));
		}

		return true;
	}

	/**
	 * @param      $disprovedBy
	 * @param bool $withDeletingObject
	 * @return bool
	 */
	public function disprove($disprovedBy, $withDeletingObject = true)
	{
		return $this->decline($disprovedBy, $withDeletingObject);
	}

	/**
	 * Delete sharing. If it is not declined|unreplied, then at first run decline and after run really deleting.
	 * @param      $deletedBy
	 * @param bool $withDeletingObject
	 * @return bool
	 */
	public function delete($deletedBy, $withDeletingObject = true)
	{
		$this->errorCollection->clear();

		if($this->isDeclined())
		{
			return $this->deleteInternal();
		}
		elseif($this->isApproved() || $this->isUnreplied())
		{
			if(!$this->decline($deletedBy, $withDeletingObject))
			{
				return false;
			}

			return $this->delete($deletedBy, $withDeletingObject);
		}

		return false;
	}

	protected function deleteInternal()
	{
		foreach($this->getChildren() as $childSharing)
		{
			$childSharing->deleteInternal();
		}
		unset($childSharing);

		return parent::deleteInternal();
	}

	public function isLoadedChildren()
	{
		return $this->children !== null;
	}

	/**
	 * @return Sharing[]
	 */
	public function getChildren()
	{
		if(!$this->isToDepartmentParent())
		{
			return array();
		}
		if(isset($this->children))
		{
			return $this->children;
		}

		$this->children = $this->getModelList(array('filter' => array(
			'PARENT_ID' => $this->getId(),
			'REAL_OBJECT_ID' => $this->getRealObjectId(),
			'REAL_STORAGE_ID' => $this->getRealStorageId(),
			'TYPE' => SharingTable::TYPE_TO_USER,
		)));

		return $this->children;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'PARENT_ID' => 'parentId',
			'PARENT' => 'parent',

			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',

			'FROM_ENTITY' => 'fromEntity',
			'TO_ENTITY' => 'toEntity',

			'LINK_OBJECT_ID' => 'linkObjectId',
			'LINK_STORAGE_ID' => 'linkStorageId',

			'LINK_OBJECT' => 'linkObject',
			'LINK_STORAGE' => 'linkStorage',

			'REAL_OBJECT_ID' => 'realObjectId',
			'REAL_STORAGE_ID' => 'realStorageId',

			'REAL_OBJECT' => 'realObject',
			'REAL_STORAGE' => 'realStorage',

			'DESCRIPTION' => 'description',
			'CAN_FORWARD' => 'canForward',
			'TYPE' => 'type',
			'STATUS' => 'status',
			'TASK_NAME' => 'taskName',
		);
	}

	/**
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$storageClassName = Storage::className();
		$objectClassName = BaseObject::className();

		return array(
			'PARENT' => static::className(),
			'LINK_OBJECT' => $objectClassName,
			'REAL_OBJECT' => $objectClassName,
			'LINK_STORAGE' => $storageClassName,
			'REAL_STORAGE' => $storageClassName,
			'CREATE_USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
		);
	}

	public static function onBeforeConfirmNotify($module, $tag, $value, $notify)
	{
		global $USER;
		$userId = $USER->getId();
		if ( !($module == Driver::INTERNAL_MODULE_ID && $userId) )
		{
			return;
		}
		$sharingModel = static::loadByNotifyTag($tag);

		if(!$sharingModel)
		{
			return;
		}

		if(!$sharingModel->isToUser())
		{
			return;
		}

		if($sharingModel->getToEntity() != self::CODE_USER . $userId)
		{
			return;
		}

		list(, $tag) = $sharingModel->getNotifyTags();
		\CIMNotify::deleteByTag($tag);

		if($value === 'N')
		{
			$sharingModel->decline($userId);
			return;
		}
		$sharingModel->approve();

		return;
	}

	public function getNotifyTags()
	{
		return array(
			Driver::INTERNAL_MODULE_ID . "|SHARING|{$this->realObjectId}",
			Driver::INTERNAL_MODULE_ID . "|SHARING|{$this->realObjectId}|{$this->toEntity}",
		);
	}

	/**
	 * @param $tag
	 * @return null|static
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 * @internal
	 */
	public static function loadByNotifyTag($tag)
	{
		$tagData = explode('|', $tag);
		if( !(
			$tagData[0] == Driver::INTERNAL_MODULE_ID &&
			$tagData[1] == 'SHARING' &&
			isset($tagData[2]) && is_numeric($tagData[2]) &&
			isset($tagData[3])
		))
		{
			return null;
		}

		$model = static::load(array(
			'REAL_OBJECT_ID' => (int)$tagData[2],
			'=TO_ENTITY' => $tagData[3],
		));

		if(!$model)
		{
			return null;
		}

		return $model;
	}
}