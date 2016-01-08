<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\File;
use Bitrix\Disk\FileLink;
use Bitrix\Disk\Folder;
use Bitrix\Disk\FolderLink;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\Storage;
use Bitrix\Disk\User;
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Disk\BizProcDocument;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFolderListAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT          = 'DISK_FLAC_22001';
	const ERROR_COULD_NOT_COPY_OBJECT          = 'DISK_FLAC_22002';
	const ERROR_COULD_NOT_MOVE_OBJECT          = 'DISK_FLAC_22003';
	const ERROR_COULD_NOT_CREATE_FIND_EXT_LINK = 'DISK_FLAC_22004';

	protected function listActions()
	{
		return array(
			'createFolderWithSharing' => array(
				'method' => array('POST'),
			),
			'showCreateFolderWithSharingInCommon' => array(
				'method' => array('POST'),
			),
			'showRightsOnStorageDetail' => array(
				'method' => array('POST'),
			),
			'showRightsOnObjectDetail' => array(
				'method' => array('POST'),
			),
			'showSettingsOnBizproc' => array(
				'method' => array('POST'),
			),
			'saveSettingsOnBizproc' => array(
				'method' => array('POST'),
			),
			'saveRightsOnStorage' => array(
				'method' => array('POST'),
			),
			'saveRightsOnObject' => array(
				'method' => array('POST'),
			),
			'showRightsDetail' => array(
				'method' => array('POST'),
			),
			'showSharingDetail' => array(
				'method' => array('POST'),
			),
			'showSharingDetailChangeRights' => array(
				'method' => array('POST'),
			),
			'showSharingDetailAppendSharing' => array(
				'method' => array('POST'),
				'name' => 'showSharingDetailChangeRights'
			),
			'changeSharingAndRights' => array(
				'method' => array('POST'),
			),
			'appendSharing' => array(
				'method' => array('POST'),
			),
			'copyTo' => array(
				'method' => array('POST'),
			),
			'moveTo' => array(
				'method' => array('POST'),
			),
			'showSubFoldersToAdd' => array(
				'method' => array('POST'),
			),
			'showShareInfoSmallView' => array(
				'method' => array('POST'),
			),
			'connectToUserStorage' => array(
				'method' => array('POST'),
			),
			'disableExternalLink' => array(
				'method' => array('POST'),
			),
			'getExternalLink' => array(
				'method' => array('POST'),
			),
			'getDetailSettingsExternalLink' => array(
				'method' => array('POST'),
			),
			'saveSettingsExternalLink' => array(
				'method' => array('POST'),
			),
			'generateExternalLink' => array(
				'method' => array('POST'),
			),
			'markDelete' => array(
				'method' => array('POST'),
			),
			'delete' => array(
				'method' => array('POST'),
			),
			'detach' => array(
				'method' => array('POST'),
				'name' => 'markDelete',
			),
			'validateParameterAutoloadBizProc' => array(
				'method' => array('POST'),
			),
		);
	}

	/**
	 * Action only for Common docs.
	 * We don't check rights on rootObject for changeRights if user has "update" operation.
	 */
	protected function processActionShowCreateFolderWithSharingInCommon()
	{
		$this->checkRequiredPostParams(array('storageId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Common) || !$storage->getRootObject()->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));
		$destination = Ui\Destination::getRightsDestination($this->getUser()->getId(), array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
			'destination' => array(
				'items' => array(
					'users' => $destination['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['SONETGROUPS'],
					'department' => $destination['DEPARTMENT'],
					'departmentRelation' => $destination['DEPARTMENT_RELATION'],
				),
				'itemsLast' => array(
					'users' => $destination['LAST']['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['LAST']['SONETGROUPS'],
					'department' => $destination['LAST']['DEPARTMENT'],
				),
				'itemsSelected' => $destination['SELECTED'],
			),
		));
	}

	/**
	 * Action only for Common docs.
	 * We don't check rights on rootObject for changeRights if user has "update" operation.
	 */
	protected function processActionCreateFolderWithSharing()
	{
		//todo refactor actions. And move logic in rights manager if needed
		$this->checkRequiredPostParams(array('storageId', 'name', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		//todo refactor this code. with action ShowCreateFolderWithSharingInCommon
		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Common) || !$storage->getRootObject()->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$entityToNewShared = $this->request->getPost('entityToNewShared');
		$storageNewRights = $this->request->getPost('storageNewRights');
		if(!$storageNewRights || !is_array($storageNewRights))
		{
			$storageNewRights = array();
		}

		$newRights = array();
		foreach($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']) && !isset($storageNewRights[$rightOnObject['ACCESS_CODE']]))
			{
				unset($rightOnObject['ID']);
				$rightOnObject['NEGATIVE'] = 1;

				$newRights[] = $rightOnObject;
			}
		}
		unset($rightOnObject);

		if($newRights)
		{
			$newRights[] = array(
				'ACCESS_CODE' => 'IU' . $this->getUser()->getId(),
				'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
			);
		}

		$name = $this->request->getPost('name');
		$newFolder = $storage->addFolder(array('NAME' => Ui\Text::correctFolderName($name), 'CREATED_BY' => $this->getUser()->getId()), $newRights);
		if($newFolder === null)
		{
			$this->errorCollection->add($storage->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$newExtendedRightsReformat = array();
			foreach($entityToNewShared as $entityId => $right)
			{
				switch($right['right'])
				{
					case 'disk_access_read':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_READ;
						break;
					case 'disk_access_edit':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_EDIT;
						break;
					case 'disk_access_full':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_FULL;
						break;
				}
			}

			if($newExtendedRightsReformat)
			{
				Sharing::addToManyEntities(array(
					'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'REAL_OBJECT' => $newFolder,
					'CREATED_BY' => $this->getUser()->getId(),
					'CAN_FORWARD' => false,
				), $newExtendedRightsReformat, $this->errorCollection);
			}
			unset($right);

		}

		$this->sendJsonSuccessResponse(array(
			'folder' => array(
				'id' => $newFolder->getId(),
			),
		));
	}

	protected function processActionShowRightsOnStorageDetail()
	{
		$this->checkRequiredPostParams(array('storageId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$readOnlyAccessCodes = array();
		if($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\User && !User::isCurrentUserAdmin())
		{
			$readOnlyAccessCodes['IU' . $storage->getEntityId()] = true;
			$readOnlyAccessCodes['U' . $storage->getEntityId()] = true;
		}
		elseif($storage->getProxyType() instanceof Bitrix\Disk\ProxyType\Group)
		{
			$readOnlyAccessCodes['SG' . $storage->getEntityId() . '_A' ] = true;
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($storage->getRootObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				if(isset($readOnlyAccessCodes[$rightOnObject['ACCESS_CODE']]))
				{
					$rightOnObject['READ_ONLY'] = true;
				}
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'showExtendedRights' => $storage->isEnabledShowExtendedRights(),
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
		));
	}

	protected function processActionShowRightsOnObjectDetail()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getStorage();
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($object) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
			'tasks' => $rightsManager->getTasks(),
		));
	}

	protected function processActionShowSettingsOnBizproc()
	{
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'statusBizProc' => (bool)$storage->isEnabledBizProc(),
		));
	}

	protected function processActionSaveSettingsOnBizproc()
	{
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->canChangeSettings($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$status = (int)$this->request->getPost('activationBizproc')?
			$storage->enableBizProc() : $storage->disableBizProc();
		if(!$status)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_SAVE'))));
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function processActionSaveRightsOnStorage()
	{
		$this->checkRequiredPostParams(array('storageId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$storage = Storage::loadById((int)$this->request->getPost('storageId'), array('ROOT_OBJECT'));
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$showExtendedRights = (bool)$this->request->getPost('showExtendedRights');
		if($storage->isEnabledShowExtendedRights() != $showExtendedRights)
		{
			$showExtendedRights?
				$storage->enableShowExtendedRights() : $storage->disableShowExtendedRights();
		}

		if(!$this->request->getPost('isChangedRights'))
		{
			$this->sendJsonSuccessResponse();
		}

		$storageNewRights = $this->request->getPost('storageNewRights');
		if(!empty($storageNewRights) && is_array($storageNewRights))
		{
			$newRights = array();
			foreach($storageNewRights as $accessCode => $right)
			{
				if(!empty($right['right']['id']))
				{
					$newRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
					);
				}
			}
			unset($accessCode, $right);

			if(empty($newRights))
			{
				$this->sendJsonErrorResponse();
			}

			if($rightsManager->set($storage->getRootObject(), $newRights))
			{
				$this->sendJsonSuccessResponse();
			}
		}

		$this->sendJsonErrorResponse();
	}

	protected function processActionSaveRightsOnObject()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getStorage();
		if(!$storage)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$specificRights = $specificOnlyNegativeRights = $inheritedRights = array();
		foreach($rightsManager->getSpecificRights($object) as $right)
		{
			$specificRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']] = $right;
			if(!empty($right['NEGATIVE']))
			{
				$specificOnlyNegativeRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']] = $right;
			}
		}
		unset($right);

		foreach($rightsManager->getAllListNormalizeRights($object) as $right)
		{
			if(!isset($specificRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']]))
			{
				$inheritedRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']] = $right;
			}
		}
		unset($right);

		$newRights = $detachedRights = array();
		$objectNewRights = $this->request->getPost('objectNewRights');
		if(!empty($objectNewRights) && is_array($objectNewRights))
		{
			foreach($objectNewRights as $accessCode => $right)
			{
				if(empty($right['right']['id']))
				{
					continue;
				}

				$alreadySpecific = isset($specificRights[$accessCode . '-' . $right['right']['id']]);
				$alreadyInherited = isset($inheritedRights[$accessCode . '-' . $right['right']['id']]);
				if($alreadySpecific)
				{
					$newRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
					);
				}
				elseif(!$alreadySpecific && !$alreadyInherited)
				{
					$newRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
					);
				}
			}
			unset($accessCode, $right);
		}
		$detachedRightsFromPost = $this->request->getPost('detachedRights');
		if(!empty($detachedRightsFromPost) && is_array($detachedRightsFromPost))
		{
			$specificRights = array();
			foreach($newRights as $right)
			{
				$specificRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']] = $right;
			}
			unset($right);

			foreach($detachedRightsFromPost as $accessCode => $right)
			{
				if(empty($right['right']['id']))
				{
					continue;
				}
				$alreadySpecific = isset($specificRights[$accessCode . '-' . $right['right']['id']]);
				$alreadyInherited = isset($inheritedRights[$accessCode . '-' . $right['right']['id']]);
				if($alreadySpecific || $alreadyInherited)
				{
					$detachedRights[] = array(
						'ACCESS_CODE' => $accessCode,
						'TASK_ID' => $right['right']['id'],
						'NEGATIVE' => 1,
					);
				}
			}
			unset($accessCode, $right);
		}
		if($specificOnlyNegativeRights)
		{
			$inheritedRights = array();
			foreach($rightsManager->getParentsRights($object->getRealObjectId()) as $right)
			{
				$inheritedRights[$right['ACCESS_CODE'] . '-' . $right['TASK_ID']] = $right;
			}
			unset($right);


			foreach($specificOnlyNegativeRights as $key => $negativeRight)
			{
				if(!isset($inheritedRights[$negativeRight['ACCESS_CODE'] . '-' . $negativeRight['TASK_ID']]))
				{
					unset($specificOnlyNegativeRights[$key]);
				}
			}
		}
		unset($negativeRight);

		if($rightsManager->set($object, array_merge($newRights, $detachedRights, $specificOnlyNegativeRights)))
		{
			$this->sendJsonSuccessResponse();
		}

		$this->sendJsonErrorResponse();
	}

	protected function processActionShowRightsDetail()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$rightsByAccessCode = array();
		foreach($rightsManager->getAllListNormalizeRights($object->getRealObject()) as $rightOnObject)
		{
			if(empty($rightOnObject['NEGATIVE']))
			{
				$rightOnObject['TASK'] = $rightsManager->getTaskById($rightOnObject['TASK_ID']);
				$rightsByAccessCode[$rightOnObject['ACCESS_CODE']][] = $rightOnObject;
			}
		}
		$access  = new CAccess();
		$names = $access->getNames(array_keys($rightsByAccessCode));

		$this->sendJsonSuccessResponse(array(
			'rights' => $rightsByAccessCode,
			'accessCodeNames' => $names,
		));
	}

	protected function processActionShowSharingDetail()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => $rightsManager->getPseudoMaxTaskByObjectForUser($object, $user->getId()),
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		$this->sendJsonSuccessResponse(array(
			'members' => $entityList,
			'owner' => array(
				'name' => $object->getRealObject()->getStorage()->getProxyType()->getEntityTitle(),
				'avatar' => $object->getRealObject()->getStorage()->getProxyType()->getEntityImageSrc(21, 21)
			),
		));
	}

	protected function processActionShowSharingDetailChangeRights()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => $rightsManager->getPseudoMaxTaskByObjectForUser($object, $user->getId()),
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		$selected = array();
		foreach($entityList as $entity)
		{
			$selected[] = $entity['entityId'];
		}
		unset($entity);
		$destination = Ui\Destination::getSocNetDestination($this->getUser()->getId(), $selected);

		$rightsManager = Driver::getInstance()->getRightsManager();
		$canOnlyShare = $object->canShare($securityContext) && !$object->canChangeRights($securityContext);
		$maxTaskName = $rightsManager::TASK_FULL;
		if($canOnlyShare)
		{
			$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($object, $this->getUser()->getId());
		}
		$proxyType = $object->getRealObject()->getStorage()->getProxyType();
		$this->sendJsonSuccessResponse(array(
			'members' => $entityList,
			'owner' => array(
				'canOnlyShare' => $canOnlyShare,
				'maxTaskName' => $maxTaskName,
				'name' => $proxyType->getEntityTitle(),
				'avatar' => $proxyType->getEntityImageSrc(21, 21),
				'link' => $proxyType->getEntityUrl(),
			),
			'destination' => array(
				'items' => array(
					'users' => $destination['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['SONETGROUPS'],
					'department' => $destination['DEPARTMENT'],
					'departmentRelation' => $destination['DEPARTMENT_RELATION'],
				),
				'itemsLast' => array(
					'users' => $destination['LAST']['USERS'],
					'groups' => array(),
					'sonetgroups' => $destination['LAST']['SONETGROUPS'],
					'department' => $destination['LAST']['DEPARTMENT'],
				),
				'itemsSelected' => $destination['SELECTED'],
			),
		));
	}

	protected function processActionAppendSharing()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canShare($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$entityToNewShared = $this->request->getPost('entityToNewShared');
		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$extendedRights = $entityToNewShared;
			$newExtendedRightsReformat = array();
			foreach($extendedRights as $entityId => $right)
			{
				switch($right['right'])
				{
					case 'disk_access_read':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_READ;
						break;
					case 'disk_access_edit':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_EDIT;
						break;
					case 'disk_access_full':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_FULL;
						break;
				}
			}
			unset($right);
			//todo move this code to Object or Sharing model (reset sharing)
			$query = Sharing::getList(array(
				'select' => array('ID', 'TO_ENTITY', 'TASK_NAME'),
				'filter' => array(
					'REAL_OBJECT_ID' => $object->getRealObjectId(),
					'REAL_STORAGE_ID' => $object->getRealObject()->getStorageId(),
					'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
				),
			));
			while($sharingRow = $query->fetch())
			{
				if(isset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]))
				{
					unset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
				}
			}
			unset($sharingRow);

			$needToAdd = $newExtendedRightsReformat;
			if($needToAdd)
			{
				//todo! refactor this to compare set of operations. And move this code
				$rightsManager = Driver::getInstance()->getRightsManager();
				$maxTaskName = $rightsManager->getPseudoMaxTaskByObjectForUser($object, $this->getUser()->getId());
				if(!$maxTaskName)
				{
					//this user could not share object, he doesn't have access
					$this->sendJsonErrorResponse();
				}

				foreach($needToAdd as $entityId => $right)
				{
					//if upgrade - then skip
					if($rightsManager->pseudoCompareTaskName($right, $maxTaskName) > 0)
					{
						unset($needToAdd[$entityId]);
					}
				}
				unset($entityId, $right);

				if($needToAdd)
				{
					Sharing::addToManyEntities(array(
						'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
						'REAL_OBJECT' => $object,
						'CREATED_BY' => $this->getUser()->getId(),
						'CAN_FORWARD' => false,
					), $needToAdd, $this->errorCollection);
				}
			}
		}
		$this->sendJsonSuccessResponse();
	}

	protected function processActionChangeSharingAndRights()
	{
		$this->checkRequiredPostParams(array('objectId', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canChangeRights($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$entityToNewShared = $this->request->getPost('entityToNewShared');
		if(!empty($entityToNewShared) && is_array($entityToNewShared))
		{
			$extendedRights = $entityToNewShared;
			$newExtendedRightsReformat = array();
			foreach($extendedRights as $entityId => $right)
			{
				switch($right['right'])
				{
					case 'disk_access_read':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_READ;
						break;
					case 'disk_access_edit':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_EDIT;
						break;
					case 'disk_access_full':
						$newExtendedRightsReformat[$entityId] = \Bitrix\Disk\RightsManager::TASK_FULL;
						break;
				}
			}
			//todo move this code to Object or Sharing model (reset sharing)
			$query = Sharing::getList(array(
				'filter' => array(
					'REAL_OBJECT_ID' => $object->getRealObjectId(),
					'REAL_STORAGE_ID' => $object->getRealObject()->getStorageId(),
					'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
					'PARENT_ID' => null,
				),
			));
			$needToOverwrite = $needToDelete = $needToAdd = array();
			while($sharingRow = $query->fetch())
			{
				if(isset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]))
				{
					if($newExtendedRightsReformat[$sharingRow['TO_ENTITY']] != $sharingRow['TASK_NAME'])
					{
						$needToOverwrite[$sharingRow['TO_ENTITY']] = $sharingRow;
					}
					elseif($newExtendedRightsReformat[$sharingRow['TO_ENTITY']] == $sharingRow['TASK_NAME'])
					{
						unset($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
					}
				}
				else
				{
					$needToDelete[$sharingRow['TO_ENTITY']] = $sharingRow;
				}
			}
			unset($sharingRow);

			$needToAdd = array_diff_key($newExtendedRightsReformat, $needToOverwrite);
			if($needToAdd)
			{
				Sharing::addToManyEntities(array(
					'FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'REAL_OBJECT' => $object,
					'CREATED_BY' => $this->getUser()->getId(),
					'CAN_FORWARD' => false,
				), $needToAdd, $this->errorCollection);
			}
			if($needToOverwrite)
			{
				$rightsManager = Driver::getInstance()->getRightsManager();
				foreach($needToOverwrite as $sharingRow)
				{
					$rightsManager->deleteByDomain($object->getRealObject(), $rightsManager->getSharingDomain($sharingRow['ID']));
				}
				unset($sharingRow);

				$newRights = array();
				foreach($needToOverwrite as $sharingRow)
				{
					$sharingDomain = $rightsManager->getSharingDomain($sharingRow['ID']);
					$newRights[] = array(
						'ACCESS_CODE' => $sharingRow['TO_ENTITY'],
						'TASK_ID' => $rightsManager->getTaskIdByName($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]),
						'DOMAIN' => $sharingDomain,
					);
					//todo refactor. Move most logic to Sharing and SharingTable!
					if($sharingRow['TYPE'] == SharingTable::TYPE_TO_DEPARTMENT)
					{
						/** @var \Bitrix\Disk\Sharing $sharingModel */
						$sharingModel = Sharing::buildFromArray($sharingRow);
						$sharingModel->changeTaskName($newExtendedRightsReformat[$sharingRow['TO_ENTITY']]);
					}
					else
					{
						SharingTable::update($sharingRow['ID'], array(
							'TASK_NAME' => $newExtendedRightsReformat[$sharingRow['TO_ENTITY']],
						));
					}
				}
				unset($sharingRow);

				if($newRights)
				{
					$rightsManager->append($object->getRealObject(), $newRights);
				}
			}
			if($needToDelete)
			{
				$ids = array();
				foreach($needToDelete as $sharingRow)
				{
					$ids[] = $sharingRow['ID'];
				}
				unset($sharingRow);

				foreach(Sharing::getModelList(array('filter' => array('ID' => $ids))) as $sharing)
				{
					$sharing->delete($this->getUser()->getId());
				}
				unset($sharing);
			}

			unset($right);

			$this->sendJsonSuccessResponse();
		}
		else
		{
			//user delete all sharing
			$userId = $this->getUser()->getId();
			foreach($object->getRealObject()->getSharingsAsReal() as $sharing)
			{
				$sharing->delete($userId);
			}
			unset($sharing);

			$this->sendJsonSuccessResponse();
		}

	}

	protected function processActionCopyTo()
	{
		$this->checkRequiredPostParams(array('objectId', 'targetObjectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		/** @var \Bitrix\Disk\Folder $targetObject */
		$targetObject = Folder::loadById((int)$this->request->getPost('targetObjectId'), array('STORAGE'));
		if(!$targetObject)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $targetObject->getStorage()->getCurrentUserSecurityContext();
		if(!$targetObject->canAdd($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$newCopy = $object->copyTo($targetObject, $this->getUser()->getId(), true);
		if(!$newCopy)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_COPY_OBJECT'), self::ERROR_COULD_NOT_COPY_OBJECT)));
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'id' => $newCopy->getId(),
			'name' => $newCopy->getName(),
		));
	}

	protected function processActionMoveTo()
	{
		$this->checkRequiredPostParams(array('objectId', 'targetObjectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		/** @var \Bitrix\Disk\Folder $targetObject */
		$targetObject = Folder::loadById((int)$this->request->getPost('targetObjectId'), array('STORAGE'));
		if(!$targetObject)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		if(!$object->canMove($securityContext, $targetObject))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$object->moveTo($targetObject, $this->getUser()->getId(), true))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_MOVE_OBJECT'), self::ERROR_COULD_NOT_MOVE_OBJECT)));
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse(array(
			'id' => $object->getId(),
			'name' => $object->getName(),
		));
	}


	protected function processActionConnectToUserStorage()
	{
		$this->checkRequiredPostParams(array('objectId',));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var \Bitrix\Disk\File|\Bitrix\Disk\Folder $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$storage = $object->getStorage();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		if($storage->getRootObjectId() == $object->getId())
		{
			$sharingModel = Sharing::connectGroupToSelfUserStorage(
				$this->getUser()->getId(),
				$storage,
				$this->errorCollection
			);
			if($sharingModel)
			{
				$this->sendJsonSuccessResponse(array(
					'objectName' => $object->getName(),
					'manage' => array(
						'link' => array(
							'object' => array(
								'id' => $sharingModel->getLinkObjectId(),
							)
						),
					),
					'message' => Loc::getMessage('DISK_FOLDER_LIST_MESSAGE_CONNECT_GROUP_DISK'),
				));
			}
		}
		else
		{
			$sharingModel = Sharing::connectObjectToSelfUserStorage(
				$this->getUser()->getId(),
				$object,
				$this->errorCollection
			);
		}


		if($sharingModel === null)
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'objectName' => $object->getName(),
		));
	}

	private function getFileAndExternalLink()
	{
		$this->checkRequiredPostParams(array('objectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$extLinks = $file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array($file, array_pop($extLinks));
	}

	protected function processActionDisableExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();
		if(!$extLink || $extLink->delete())
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}

	protected function processActionGetExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();

		if(!$extLink)
		{
			$this->sendJsonSuccessResponse(array(
				'hash' => null,
				'link' => null,
			));
		}
		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	protected function processActionGetDetailSettingsExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();

		if(!$extLink)
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'object' => array(
				'name' => $file->getName(),
				'size' => \CFile::formatSize($file->getSize()),
				'date' => (string)$file->getUpdateTime(),
			),
			'linkData' => array(
				'hasPassword' => $extLink->hasPassword(),
				'hasDeathTime' => $extLink->hasDeathTime(),
				'deathTime' => $extLink->hasDeathTime()? (string)$extLink->getDeathTime() : null,
				'hash' => $extLink->getHash(),
				'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $extLink->getHash(),
					'action' => 'default',
				), true),
			),
		));
	}

	protected function processActionSaveSettingsExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();

		if(!$extLink)
		{
			$this->sendJsonErrorResponse();
		}

		if($this->request->getPost('deathTime'))
		{
			$extLink->changeDeathTime(DateTime::createFromTimestamp(time() + (int)$this->request->getPost('deathTime')));
		}
		if($this->request->getPost('password'))
		{
			$extLink->changePassword($this->request->getPost('password'));
		}

		$this->sendJsonSuccessResponse(array(
			'object' => array(
				'name' => $file->getName(),
				'size' => \CFile::formatSize($file->getSize()),
				'date' => (string)$file->getUpdateTime(),
			),
			'linkData' => array(
				'hasPassword' => $extLink->hasPassword(),
				'hasDeathTime' => $extLink->hasDeathTime(),
				'hash' => $extLink->getHash(),
				'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $extLink->getHash(),
					'action' => 'default',
				), true),
			),
		));
	}

	protected function processActionGenerateExternalLink()
	{
		/** @var File $file */
		list($file, $extLink) = $this->getFileAndExternalLink();
		if(!$extLink)
		{
			$extLink = $file->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extLink)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_CREATE_FIND_EXT_LINK'), self::ERROR_COULD_NOT_CREATE_FIND_EXT_LINK)));
			$this->errorCollection->add($file->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	protected function processActionShowSubFoldersToAdd()
	{
		$this->checkRequiredPostParams(array('objectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		//we should preload specific rights by object on current level, because we are filtering by canAdd. And we can use fakeSecurityContext by $folder->getChildren
		$securityContext->preloadOperationsForChildren($folder->getRealObjectId());

		$subFolders = array();
		foreach($folder->getChildren(Driver::getInstance()->getFakeSecurityContext(), array('select' => array('*', 'HAS_SUBFOLDERS'), 'filter' => array('TYPE' => ObjectTable::TYPE_FOLDER))) as $subFolder)
		{
			/** @var Folder $subFolder */
			if($subFolder->canAdd($securityContext))
			{
				$subFolders[] = array(
					'id' => $subFolder->getId(),
					'name' => $subFolder->getName(),
					'isLink' => $subFolder->isLink(),
					'hasSubFolders' => $subFolder->hasSubFolders(),
				);
			}
		}
		unset($subFolder);
		\Bitrix\Main\Type\Collection::sortByColumn($subFolders, 'name');


		$this->sendJsonSuccessResponse(array(
			'items' => $subFolders,
		));
	}

	protected function processActionShowShareInfoSmallView()
	{
		$this->checkRequiredPostParams(array('objectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File|Folder $object */
		$object = \Bitrix\Disk\BaseObject::loadById((int)$this->request->getPost('objectId'), array('REAL_OBJECT.STORAGE'));
		if(!$object || !$object->getRealObject() || !$object->getRealObject()->getStorage())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$storage = $object->getRealObject()->getStorage();

		$proxyType = $storage->getProxyType();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		//user has only read right. And he can't see on another sharing
		if(!$object->canShare($securityContext) && !$object->canChangeRights($securityContext))
		{
			/** @var User $user */
			$user = User::getById($this->getUser()->getId());
			$entityList = array(
				array(
					'entityId' => Sharing::CODE_USER . $this->getUser()->getId(),
					'name' => $user->getFormattedName(),
					'right' => \Bitrix\Disk\RightsManager::TASK_READ,
					'avatar' => $user->getAvatarSrc(),
					'type' => 'users',
				)
			);
		}
		else
		{
			$entityList = $object->getMembersOfSharing();
		}

		$this->sendJsonSuccessResponse(array(
			'owner' => array(
				'name' => $proxyType->getEntityTitle(),
				'url' => $proxyType->getEntityUrl(),
				'avatar' => $proxyType->getEntityImageSrc(21 ,21),
			),
			'members' => $entityList,
		));
	}

	protected function processActionMarkDelete()
	{
		$this->checkRequiredPostParams(array('objectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder|File $objectToMarkDeleted */
		$objectToMarkDeleted = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$objectToMarkDeleted)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		if(!$objectToMarkDeleted->canMarkDeleted($objectToMarkDeleted->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$objectToMarkDeleted->markDeleted($this->getUser()->getId()))
		{
			$this->errorCollection->add($objectToMarkDeleted->getErrors());
			$this->sendJsonErrorResponse();
		}
		$response = array();
		if($objectToMarkDeleted instanceof FolderLink)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_DETACH');
		}
		elseif($objectToMarkDeleted instanceof FileLink)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_DETACH');
		}
		elseif($objectToMarkDeleted instanceof Folder)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_MARK_DELETED_2');
		}
		elseif($objectToMarkDeleted instanceof File)
		{
			$response['message'] = Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_MARK_DELETED_2');
		}

		$this->sendJsonSuccessResponse($response);
	}

	protected function processActionDelete()
	{
		$this->checkRequiredPostParams(array('objectId'));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT)));
			$this->sendJsonErrorResponse();
		}

		if(!$object->canDelete($object->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if($object instanceof Folder)
		{
			if(!$object->deleteTree($this->getUser()->getId()))
			{
				$this->errorCollection->add($object->getErrors());
				$this->sendJsonErrorResponse();
			}
			$this->sendJsonSuccessResponse(array(
				'message' => Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FOLDER_DELETED'),
			));
		}

		if(!$object->delete($this->getUser()->getId()))
		{
			$this->errorCollection->add($object->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'message' => Loc::getMessage('DISK_FOLDER_ACTION_MESSAGE_FILE_DELETED'),
		));
	}

	protected function processActionValidateParameterAutoloadBizProc()
	{
		$validate = true;
		$search = 'bizproc';
		$data = $this->request->getPost('data');
		foreach($data['data'] as $key => $value)
		{
			$res = strpos($key, $search);
			if($res === 0)
			{
				foreach($this->request->getPost('required') as $checkString)
				{
					if(is_array($value))
					{
						foreach($value as $checkType => $validateValue)
						{
							if(is_array($validateValue))
							{
								foreach($validateValue as $checkTypeArray => $validateValueArray)
								{
									if($checkTypeArray == 'TYPE' && $validateValueArray == 'html')
									{
										if(!empty($validateValue['TEXT']) && $key == $checkString)
										{
											$validate = true;
											break;
										}
										elseif($key == $checkString)
										{
											$validate = false;
										}
									}
								}
								continue;
							}
							if($checkType == 'TYPE' && $validateValue == 'html')
							{
								if(empty($value['TEXT']) && $key == $checkString)
								{
									$validate = false;
								}
							}
							elseif(empty($validateValue) && $key == $checkString)
							{
								$validate = false;
							}
							elseif(!empty($validateValue) && $key == $checkString)
							{
								$validate = true;
								break;
							}
						}
					}
					else
					{
						if(empty($value) && $key == $checkString)
						{
							$validate = false;
						}
					}
				}
			}
		}
		if(!$validate)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LIST_ERROR_VALIDATE_BIZPROC'))));
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse();
	}
}
$controller = new DiskFolderListAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;