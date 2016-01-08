<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\CloudImport;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\Storage;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Ui\Text;
use Bitrix\Disk\User;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use CFile;

Loc::loadMessages(__FILE__);

class Controller extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_USER_STORAGE       = 'DISK_UF_CON_22001';
	const ERROR_COULD_NOT_FIND_FOLDER             = 'DISK_UF_CON_22002';
	const ERROR_COULD_NOT_FIND_CLOUD_IMPORT       = 'DISK_UF_CON_22003';
	const ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE = 'DISK_UF_CON_22004';

	protected function listActions()
	{
		return array(
			'openDialog',
			'selectFile' => 'openDialog',
			'renameFile' => array(
				'method' => array('POST'),
			),
			'loadItems' => array(
				'method' => array('POST'),
			),
			'downloadFile' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'download' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'show' => array(
				'method' => array('GET'),
				'close_session' => true,
			),
			'copyToMe' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => true,
			),
			'uploadFile' => array(
				'method' => array('POST', 'GET')
			),
			'deleteFile' => array(
				'method' => array('POST'),
			),
			'startUpload' => array(
				'method' => array('POST'),
			),
			'reloadAttachedObject' => array(
				'method' => array('POST'),
			),
			'uploadChunk' => array(
				'method' => array('POST'),
				'close_session' => true,
			),
			'saveAsNewFile' => array(
				'method' => array('POST'),
			),
			'updateAttachedObject' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);

		\CFile::enableTrackingResizeImage();

		return true;
	}

	protected function getUserGroupWithStorage()
	{
		if(!\CBXFeatures::isFeatureEnabled("Workgroups"))
		{
			return array();
		}
		if(!Loader::includeModule('socialnetwork'))
		{
			return array();
		}

		$userId = $this->getUser()->getId();
		$currentPossibleUserGroups = $currentUserGroups = array();

		$diskSecurityContext = new DiskSecurityContext($this->getUser()->getId());
		$storages = Storage::getReadableList(
			$diskSecurityContext,
			array('filter' => array('STORAGE.ENTITY_TYPE' => ProxyType\Group::className()))
		);
		foreach($storages as $storage)
		{
			$currentPossibleUserGroups[$storage->getEntityId()] = $storage;
		}
		unset($storage);

		$query = \CSocNetUserToGroup::getList(
			array('GROUP_NAME' => 'ASC'),
			array('USER_ID' => $userId, 'GROUP_ID' => array_keys($currentPossibleUserGroups)),
			false,
			false,
			array('GROUP_ID', 'GROUP_NAME', 'GROUP_ACTIVE', 'GROUP_CLOSED', 'ROLE')
		);
		while($row = $query->getNext())
		{
			if (
				($row['GROUP_ACTIVE'] == 'Y') &&
				($row['GROUP_CLOSED'] == 'N') &&
				($row['ROLE'] != SONET_ROLES_BAN) &&
				($row['ROLE'] != SONET_ROLES_REQUEST) &&
				isset($currentPossibleUserGroups[$row['GROUP_ID']]))
			{
				$currentUserGroups[$row['GROUP_ID']] = array(
					'STORAGE' => $currentPossibleUserGroups[$row['GROUP_ID']],
					'NAME' => $row['GROUP_NAME'],
				);
			}
		}

		return $currentUserGroups;
	}

	protected function getCommonStorages()
	{
		return Storage::getReadableList($this->getSecurityContextByUser($this->getUser()), array('filter' => array(
			'STORAGE.ENTITY_TYPE' => ProxyType\Common::className(),
			'STORAGE.SITE_ID' => SITE_ID,
		)));
	}

	private function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new DiskSecurityContext($user);
		if(Loader::includeModule('socialnetwork'))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new FakeSecurityContext($user);
			}
		}
		if(User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new FakeSecurityContext($user);
		}
		return $diskSecurityContext;
	}

	protected function processActionReloadAttachedObject()
	{
		$this->checkRequiredPostParams(array('attachedId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getPost('attachedId'), array('OBJECT'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not update this file")));
			$this->sendJsonErrorResponse();
		}

		$file = $attachedModel->getFile();
		if(!$file)
		{
			$this->sendJsonErrorResponse();
		}
		if(!$file->canUpdateByCloudImport($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$importManager = CloudImport\ImportManager::buildByAttachedObject($attachedModel);
		if(!$importManager)
		{
			return null;
		}
		$documentHandler = $importManager->getDocumentHandler();
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$lastCloudImport = $attachedModel
			->getFile()
			->getLastCloudImportEntry()
		;
		if(!$importManager->hasNewVersion($lastCloudImport))
		{
			$this->sendJsonSuccessResponse(array(
				'hasNewVersion' => false,
			));
		}

		$cloudImportEntry = $importManager->forkImport($lastCloudImport);
		if(!$cloudImportEntry)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'hasNewVersion' => true,
			'cloudImport' => array(
				'id' => $cloudImportEntry->getId(),
			),
		));
	}

	protected function processActionStartUpload()
	{
		$this->checkRequiredPostParams(array('fileId', 'service'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($this->request->getPost('service'));
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		$cloudImportEntry = $importManager->startImport($this->request->getPost('fileId'));
		if(!$cloudImportEntry)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'cloudImport' => array(
				'id' => $cloudImportEntry->getId(),
			),
		));
	}

	protected function processActionUploadChunk()
	{
		$this->checkRequiredPostParams(array('cloudImportId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		if(!$importManager->uploadChunk($cloudImport))
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'step' => $cloudImport->isDownloaded()? 'finish' : 'download',
			'contentSize' => (int)$cloudImport->getContentSize(),
			'downloadedContentSize' => (int)$cloudImport->getDownloadedContentSize(),
		));
	}

	protected function processActionSaveAsNewFile()
	{
		$this->checkRequiredPostParams(array('cloudImportId',));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		), array('TMP_FILE'));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}

		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		$folder = $storage->getSpecificFolderByCode($documentHandler::SPECIFIC_FOLDER_CODE);

		$importManager = new CloudImport\ImportManager($documentHandler);
		$file = $importManager->saveFile($cloudImport, $folder);
		if(!$file)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'file' => array(
				'id' => $file->getId(),
				'ufId' => FileUserType::NEW_FILE_PREFIX . $file->getId(),
				'name' => $file->getName(),
				'size' => $file->getSize(),
				'sizeFormatted' => \CFile::formatSize($file->getSize()),
				'folder' => $file->getParent()->getName(),
				'storage' => $file->getParent()->getName(),
				'previewUrl' => TypeFile::isImage($file)?
					Driver::getInstance()->getUrlManager()->getUrlForShowFile($file, array("width" => 100, "height" => 100)) : ''
			),
		));
	}

	protected function processActionUpdateAttachedObject()
	{
		$this->checkRequiredPostParams(array('cloudImportId', 'attachedId', ));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$cloudImport = CloudImport\Entry::load(array(
			'ID' => $this->request->getPost('cloudImportId'),
			'USER_ID' => $this->getUser()->getId(),
		), array('TMP_FILE'));
		if(!$cloudImport)
		{
			$this->errorCollection->addOne(new Error('Could not find cloud import', self::ERROR_COULD_NOT_FIND_CLOUD_IMPORT));
			$this->sendJsonErrorResponse();
		}

		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($cloudImport->getService());
		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());
			$this->sendJsonErrorResponse();
		}
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
			$this->errorCollection->add($documentHandler->getErrors());
			$this->sendJsonErrorResponse();
		}

		if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
		{
			$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
		}


		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getPost('attachedId'), array('OBJECT'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not update this file")));
			$this->sendJsonErrorResponse();
		}

		$importManager = new CloudImport\ImportManager($documentHandler);
		$version = $importManager->uploadVersion($cloudImport);
		if(!$version)
		{
			$this->errorCollection->add($importManager->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	protected function listStorages()
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error('Could not find storage for current user')));
			return null;
		}

		$list = array(
			$userStorage->getId() => array(
				'id' => $userStorage->getId(),
				'name' => $userStorage->getProxyType()->getTitleForCurrentUser(),
				'type' => 'user',
				'link' => Driver::getInstance()->getUrlManager()->getUrlUfController('loadItems'),
			),
		);

		foreach($this->getUserGroupWithStorage() as $group)
		{
			if(empty($group['STORAGE']))
			{
				continue;
			}
			/** @var Storage $storage */
			$storage = $group['STORAGE'];
			$list[$storage->getId()] = array(
				'id' => $storage->getId(),
				'name' => $group['NAME'],
				'type' => 'group',
				'link' => Driver::getInstance()->getUrlManager()->getUrlUfController('loadItems'),
			);
		}
		unset($group, $storage);

		foreach($this->getCommonStorages() as $common)
		{
			$list[$common->getId()] = array(
				'id' => $common->getId(),
				'name' => $common->getName(),
				'type' => 'common',
				'link' => Driver::getInstance()->getUrlManager()->getUrlUfController('loadItems'),
			);
		}
		unset($common);

		return $list;
	}

	protected function listCloudStorages()
	{
		$list = array();
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		foreach($documentHandlersManager->getListHandlersForImport() as $handler)
		{
			$list[$handler->getCode()] = array(
				'id' => $handler->getCode(),
				'name' => $handler->getStorageName(),
				'type' => 'cloud',
				'link' => Driver::getInstance()->getUrlManager()->getUrlUfController(
					'loadItems',
					array(
						'cloudImport' => 1,
						'service' => $handler->getCode(),
					)
				),
			);
		}
		unset($handler);

		return $list;
	}

	protected function processActionOpenDialog()
	{
		$selectedService = '';
		if($this->request->getQuery('cloudImport'))
		{
			$list = $this->listCloudStorages();
			$types = array(
				'cloud' => array(
					'id' => 'cloud',
					'order' => 4,
				),
			);
			$selectedService = $this->request->getQuery('service');
		}
		else
		{
			$list = $this->listStorages();
			if(!$list)
			{
				$this->sendJsonErrorResponse();
			}
			$types = array(
				'user' => array(
					'id' => 'user',
					'order' => 1,
				),
				'common' => array(
					'id' => 'common',
					'name' => Loc::getMessage('DISK_UF_CONTROLLER_SHARED_DOCUMENTS'),
					'order' => 2,
				),
				'group' => array(
					'id' => 'group',
					'name' => Loc::getMessage('DISK_UF_CONTROLLER_MY_GROUPS'),
					'order' => 3,
				),
			);
		}

		$dialogName = $this->request->getQuery('dialogName');
		if (strlen($dialogName) <= 0)
		{
			$dialogName = 'DiskFileDialog';
		}

		$js = "
			<script>
				BX.DiskFileDialog.init({
					'currentTabId': '" . \CUtil::jSEscape($selectedService) . "',
					'name' : '".\CUtil::jSEscape($dialogName)."',

					'bindPopup' : { 'node' : null, 'offsetTop' : 0, 'offsetLeft': 0},

					'localize' : {
						'title' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_SELECT_DOCUMENT_TITLE')) . "',
						'saveButton' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_SELECT_DOCUMENT')) . "',
						'cancelButton' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_CANCEL')) . "'
					},

					'callback' : {
						'saveButton' : function(tab, path, selected) {},
						'cancelButton' : function(tab, path, selected) {}
					},

					'type' : " . \CUtil::phpToJSObject($types) . ",
					'typeItems' : " . \CUtil::phpToJSObject($list) . ",
					'items' : {},

					'itemsDisabled' : {},
					'itemsSelected' : {},
					'itemsSelectEnabled' : {'onlyFiles' : true}, // all, onlyFiles, folder, archive, image, file, video, txt, word, excel, ppt
					'itemsSelectMulti' : true,

					'gridColumn' : {
						'name' : {'id' : 'name', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_NAME')) . "', 'sort' : 'name', 'style': 'width: 310px', 'order': 1},
						'size' : {'id' : 'size', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_FILE_SIZE')) . "', 'sort' : 'sizeInt', 'style': 'width: 79px', 'order': 2},
						'modifyBy' : {'id' : 'modifyBy', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_MODIFIED_BY')) . "', 'sort' : 'modifyBy', 'style': 'width: 122px', 'order': 3},
						'modifyDate' : {'id' : 'modifyDate', 'name' : '" . \CUtil::jSEscape(Loc::getMessage('DISK_UF_CONTROLLER_TITLE_TIMESTAMP')) . "', 'sort' : 'modifyDateInt', 'style': 'width: 90px', 'order': 4}
					},
					'gridOrder' : {'column': 'modifyDateInt', 'order':'desc'}
				});
			</script>
				";

		$this->sendResponse($js);
	}

	protected function processActionLoadItems()
	{
		$this->checkRequiredPostParams(array(
			'FORM_TAB_TYPE', 'FORM_TAB_ID', 'FORM_PATH',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$dialogName = $this->request->getPost('FORM_NAME') ?: 'DiskFileDialog';
		$typeStorage = strtolower($this->request->getPost('FORM_TAB_TYPE'));
		if(!in_array($typeStorage, array('user', 'common', 'group', 'cloud'), true))
		{
			$this->errorCollection->add(array(new Error("Invalid storage type {$typeStorage}")));
			$this->sendJsonErrorResponse();
		}
		$storageId = (int)$this->request->getPost('FORM_TAB_ID');
		$path = $this->request->getPost('FORM_PATH');

		$storage = null;
		if($typeStorage === 'user')
		{
			$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		}
		elseif($typeStorage === 'group')
		{
			$storage = Storage::loadById($storageId);
			if(!$storage->getProxyType() instanceof ProxyType\Group)
			{
				$this->errorCollection->add(array(new Error("Invalid storage type {$typeStorage}. Is not a group.")));
				$this->sendJsonErrorResponse();
			}
		}
		elseif($typeStorage === 'common')
		{
			$storage = Storage::loadById($storageId);
			if(!$storage->getProxyType() instanceof ProxyType\Common)
			{
				$this->errorCollection->add(array(new Error("Invalid storage type {$typeStorage}. Is not a common storage.")));
				$this->sendJsonErrorResponse();
			}
		}
		elseif($typeStorage === 'cloud')
		{
			$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
			$documentHandler = $documentHandlersManager->getHandlerByCode($this->request->getQuery('service'));
			if(!$documentHandler)
			{
				$this->errorCollection->add($documentHandlersManager->getErrors());
				$this->sendJsonErrorResponse();
			}
			if(!$documentHandler->checkAccessibleTokenService())
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE', array('#NAME#' => $documentHandler->getName())), self::ERROR_COULD_NOT_WORK_WITH_TOKEN_SERVICE)));
				$this->errorCollection->add($documentHandler->getErrors());
				$this->sendJsonErrorResponse();
			}

			if(!$documentHandler->queryAccessToken()->hasAccessToken() || $documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
			}

			$itemsCloud = $this->listItemsCloud($documentHandler, $path);
			if($itemsCloud === null && $documentHandler->isRequiredAuthorization())
			{
				$this->sendNeedAuth($documentHandler->getUrlForAuthorizeInTokenService('opener'));
			}
			$this->sendJsonSuccessResponse(array(
				'FORM_NAME' => $dialogName,
				'FORM_ITEMS' => $itemsCloud,
				'FORM_ITEMS_DISABLED' => array(),
				'FORM_PATH' => $path,
				'FORM_IBLOCK_ID' => 0,
			));
		}

		if(!$storage)
		{
			$this->errorCollection->add(array(new Error('Could not find storage for current user')));
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'FORM_NAME' => $dialogName,
			'FORM_ITEMS' => $this->listItems($storage, $path),
			'FORM_ITEMS_DISABLED' => array(),
			'FORM_PATH' => $path,
			'FORM_IBLOCK_ID' => 0,
		));

	}

	/**
	 * @param $storage
	 * @param $path
	 * @return array
	 */
	protected function listItems(Storage $storage, $path = '/')
	{
		$currentFolderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, $path);
		/** @var Folder $folder */
		$folder = Folder::loadById($currentFolderId);
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error('Could not find folder by path')));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $storage->getCurrentUserSecurityContext();
		$urlManager = Driver::getInstance()->getUrlManager();
		$urlForLoadItems = $urlManager->getUrlUfController('loadItems');

		$response = array();
		foreach($folder->getChildren($securityContext, array('with' => array('UPDATE_USER'))) as $item)
		{
			/** @var File|Folder $item */
			$isFolder = $item instanceof Folder;
			$id = $item->getId();
			$res = array(
				'id' => $item->getId(),
				'type' => $isFolder ? 'folder' : 'file',
				'link' => $urlForLoadItems,
				'name' => $item->getName(),
				'path' => ($path === '/'? '/' : $path . '/') . $item->getName(),
				'size' => $isFolder ? '' : \CFile::formatSize($item->getSize()),
				'sizeInt' => $isFolder ? '' : $item->getSize(),
				'modifyBy' => $item->getUpdateUser()->getFormattedName(),
				'modifyDate' => $item->getUpdateTime()->format('d.m.Y'),
				'modifyDateInt' => $item->getUpdateTime()->getTimestamp(),
			);
			if (!$isFolder)
			{
				$extension = $item->getExtension();
				$id = FileUserType::NEW_FILE_PREFIX.$item->getId();
				$res = array_merge(
					$res,
					array(
						'id' => $id,
						'ext' => $extension,
						'storage' => $folder->getName()
					)
				);
				if (TypeFile::isImage($item))
				{
					$res['previewUrl'] = $urlManager->getUrlForShowFile($item);
				}
			}
			$response[$id] = $res;
		}
		unset($item);

		return $response;
	}

	protected function listItemsCloud(DocumentHandler $documentHandler, $path = '/')
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		$urlForLoadItems = $urlManager->getUrlUfController('loadItems');

		$items = $documentHandler->listFolder($path, $this->request->getQuery('folderId'));
		if($items === null)
		{
			$this->errorCollection->add($documentHandler->getErrors());
			return null;
		}
		$response = array();
		foreach($items as $item)
		{
			$item['link'] = $urlManager->getUrlUfController(
				'loadItems',
				array(
					'folderId' => $item['id'],
					'service' => $documentHandler->getCode(),
				)
			);
			$response[$item['id']] = $item;

		}
		unset($item);

		return $response;
	}

	protected function processActionDownload($showFile = false)
	{
		$this->checkRequiredGetParams(array(
			'attachedId',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		/** @var AttachedObject $attachedModel */
		$attachedModel = AttachedObject::loadById((int)$this->request->getQuery('attachedId'), array('OBJECT', 'VERSION'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
			$this->sendJsonErrorResponse();
		}

		$file = $attachedModel->getFile();
		$fileName = $file->getName();
		$fileData = $file->getFile();

		$version = $attachedModel->getVersion();
		if($version)
		{
			$fileName = $version->getName();
			$fileData = $version->getFile();
		}

		if(!$fileData)
		{
			$this->end();
		}

		$isImage = TypeFile::isImage($fileData["ORIGINAL_NAME"]);
		$cacheTime = $isImage? 86400 : 0;

		$width = $this->request->getQuery('width');
		$height = $this->request->getQuery('height');
		if ($isImage && ($width > 0 || $height > 0))
		{
			$signature = $this->request->getQuery('signature');
			if(!$signature)
			{
				$this->sendJsonInvalidSignResponse('Empty signature');
			}
			if(!ParameterSigner::validateImageSignature($signature, $attachedModel->getId(), $width, $height))
			{
				$this->sendJsonInvalidSignResponse('Invalid signature');
			}

			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$tmpFile = \CFile::resizeImageGet($fileData, array("width" => $width, "height" => $height), ($this->request->getQuery('exact') == "Y" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, false, true);
			$fileData["FILE_SIZE"] = $tmpFile["size"];
			$fileData["SRC"] = $tmpFile["src"];
		}
		if($isImage && $showFile)
		{
			header("X-Bitrix-Context-Image: {$this->getContextForImage($attachedModel)}");
		}

		\CFile::viewByUser($fileData, array('force_download' => !$showFile, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	protected function processActionShow()
	{
		$this->processActionDownload(true);
	}

	protected function processActionCopyToMe()
	{
		$this->checkRequiredGetParams(array(
			'attachedId',
		));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$attachedModel = AttachedObject::loadById((int)$this->request->getQuery('attachedId'), array('OBJECT', 'VERSION'));
		if(!$attachedModel)
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}

		if(!$attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
			$this->sendJsonErrorResponse();
		}

		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error("Could not find storage for current user")));
			$this->sendJsonErrorResponse();
		}
		$folder = $userStorage->getFolderForSavedFiles($this->getUser()->getId());
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not find folder for created files")));
			$this->sendJsonErrorResponse();
		}
		$file = $attachedModel->getObject();
		$newFile = $file->copyTo($folder, $this->getUser()->getId(), true);

		if(!$newFile)
		{
			$this->errorCollection->add(array(new Error("Could not copy file to storage for current user")));
			$this->sendJsonErrorResponse();
		}

		$crumbs = array();
		foreach($newFile->getParents(Driver::getInstance()->getFakeSecurityContext()) as $parent)
		{
			if($parent->getId() == $userStorage->getRootObjectId())
			{
				continue;
			}
			$crumbs[] = $parent->getName();
		}
		unset($parent);

		$viewUrl = Driver::getInstance()->getUrlManager()->encodeUrn($userStorage->getProxyType()->getStorageBaseUrl() . 'path/' . implode('/', $crumbs));

		$this->sendJsonSuccessResponse(array(
			'newId' => $newFile->getId(),
			'viewUrl' => $viewUrl . '#hl-' . $newFile->getId(),
		));
	}

	function processActionHandleFile($hash, &$file, &$package, &$upload, &$error)
	{
		$errorCollection = new ErrorCollection();
		$storage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$storage)
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_USER_STORAGE'), self::ERROR_COULD_NOT_FIND_USER_STORAGE)));
			$error = implode(" ", $errorCollection->toArray());
			return false;
		}
		$folder = $storage->getFolderForUploadedFiles($this->getUser()->getId());
		if(!$folder)
		{
			$errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_CONTROLLER_ERROR_COULD_NOT_FIND_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER)));
			$error = implode(" ", $errorCollection->toArray());
			return false;
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		if($folder->canAdd($storage->getCurrentUserSecurityContext()))
		{
			$fileModel = $folder->uploadFile(
				$file["files"]["default"],
				array(
					'NAME' => $file['name'],
					'CREATED_BY' => $this->getUser()->getId(),
			), array(), true);

			if($fileModel)
			{
				$name = $fileModel->getName();
				$id = FileUserType::NEW_FILE_PREFIX.$fileModel->getId();

				$file = array_merge(
					$file,
					array(
						'id' => $id,
						'originalId' => $fileModel->getId(),
						'name' => $name,
						'label' => getFileNameWithoutExtension($name),
						'ext' => $fileModel->getExtension(),
						'size' => \CFile::formatSize($fileModel->getSize()),
						'sizeInt' => $fileModel->getSize(),
						'storage' => $storage->getProxyType()->getTitleForCurrentUser() . ' / ' . $folder->getName(),
						'deleteUrl' => $urlManager->getUrlUfController('deleteFile', array('attachedId' => $id)),
						'canChangeName' => true,
					),
					(TypeFile::isImage($name) ? array(
						'previewUrl' => $urlManager->getUrlForShowFile($fileModel, array("width" => 100, "height" => 100))
					) : array())
				);
			}
			else
			{
				$error = (is_array($folder->getErrors()) ? implode(" ", $folder->getErrors()) : 'The file has not been saved');
			}
		}
		return (empty($error));
	}

	protected function processActionUploadFile()
	{
		static $uploader = null;
		if ($uploader === null)
			$uploader = new \CFileUploader(array("events" => array("onFileIsUploaded" => array($this, "processActionHandleFile"))), "get");
		$uploader->checkPost();
	}

	protected function processActionDownloadFile()
	{
		$this->checkRequiredGetParams(array('attachedId'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$fileModel = null;

		list($type, $realValue) = FileUserType::detectType($this->request->getQuery('attachedId'));

		if ($type == FileUserType::TYPE_NEW_OBJECT)
		{
			/** @var File $fileModel */
			$fileModel = File::loadById((int)$realValue, array('STORAGE'));
			if(!$fileModel)
			{
				$this->errorCollection->add(array(new Error("Could not find file")));
				$this->sendJsonErrorResponse();
			}
			if(!$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
				$this->sendJsonErrorResponse();
			}

			$fileName = $fileModel->getName();
			$fileData = $fileModel->getFile();

			if(!$fileData)
			{
				$this->end();
			}

			$cacheTime = 0;

			$width = $this->request->getQuery('width');
			$height = $this->request->getQuery('height');
			if (TypeFile::isImage($fileData["ORIGINAL_NAME"]) && ($width > 0 || $height > 0))
			{
				$signature = $this->request->getQuery('signature');
				if(!$signature)
				{
					$this->sendJsonInvalidSignResponse('Empty signature');
				}
				if(!ParameterSigner::validateImageSignature($signature, $fileModel->getId(), $width, $height))
				{
					$this->sendJsonInvalidSignResponse('Invalid signature');
				}

				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$tmpFile = \CFile::resizeImageGet($fileData, array("width" => $width, "height" => $height), ($this->request->getQuery('exact') == "Y" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, false, true);
				$fileData["FILE_SIZE"] = $tmpFile["size"];
				$fileData["SRC"] = $tmpFile["src"];
				$cacheTime = 86400;
			}
			\CFile::viewByUser($fileData, array("force_download" => false, "cache_time" => $cacheTime, 'attachment_name' => $fileName));
		}
		else
		{
			$this->errorCollection->add(array(new Error('Could not find attached object')));
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionDeleteFile()
	{
		$this->checkRequiredGetParams(array('attachedId'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		list($type, $realValue) = FileUserType::detectType($this->request->getQuery('attachedId'));

		if ($type == FileUserType::TYPE_NEW_OBJECT)
		{
			$model = File::loadById((int)$realValue, array('STORAGE'));
			if(!$model)
			{
				$this->errorCollection->add(array(new Error("Could not find file")));
				$this->sendJsonErrorResponse();
			}
			if(!$model->canDelete($model->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
				$this->sendJsonErrorResponse();
			}

			if(!$model->delete($this->getUser()->getId()))
			{
				$this->errorCollection->add($model->getErrors());
				$this->sendJsonErrorResponse();
			}

			$this->sendJsonSuccessResponse(array(
				'id' => $this->request->getQuery('attachedId'),
			));
		}
		else
		{
			$this->errorCollection->add(array(new Error('Could not delete attached object')));
			$this->sendJsonErrorResponse();
		}
	}

	protected function processActionRenameFile()
	{
		$this->checkRequiredPostParams(array('newName', 'attachedId'));

		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		list($type, $realValue) = FileUserType::detectType($this->request->getPost('attachedId'));

		if ($type == FileUserType::TYPE_NEW_OBJECT)
		{
			/** @var File $model */
			$model = File::loadById((int)$realValue, array('STORAGE'));
			if(!$model)
			{
				$this->errorCollection->add(array(new Error("Could not find file")));
				$this->sendJsonErrorResponse();
			}
			if(!$model->canRename($model->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error("Bad permission. Could not read this file")));
				$this->sendJsonErrorResponse();
			}
			$newName = Text::correctFilename(($this->request->getPost('newName')));
			if(!$model->renameInternal($newName, true))
			{
				$this->errorCollection->add($model->getErrors());
				$this->sendJsonErrorResponse();
			}
			Driver::getInstance()->getIndexManager()->indexFile($model);

			$this->sendJsonSuccessResponse(array(
				'id' => $this->request->getPost('attachedId'),
				'name' => $model->getName(),
			));
		}
		else
		{
			$this->sendJsonErrorResponse();
		}
	}

	/**
	 * @param AttachedObject $attachedModel
	 * @return string
	 */
	private function getContextForImage(AttachedObject $attachedModel)
	{
		$context = 'unknown';
		if($_GET["width"] == 1024 && $_GET["height"] == 1024)
		{
			$context = 'viewer resize';
		}
		elseif($_GET["width"] == 69 && $_GET["height"] == 69)
		{
			$context = 'list resize';
		}
		elseif(isset($_GET['x']))
		{
			$context = 'inline resize';
		}
		$connector = $attachedModel->getConnector();
		if($connector instanceof BlogPostConnector)
		{
			$context .= " (blog {$attachedModel->getEntityId()})";

			return $context;
		}
		elseif($connector instanceof CalendarEventConnector)
		{
			$context .= " (calendar {$attachedModel->getEntityId()})";

			return $context;
		}
		elseif($connector instanceof ForumMessageConnector)
		{
			$context .= " (forum {$attachedModel->getEntityId()})";

			return $context;
		}
		elseif($connector instanceof SonetCommentConnector)
		{
			$context .= " (sonetcomm {$attachedModel->getEntityId()})";

			return $context;
		}
		elseif($connector instanceof SonetLogConnector)
		{
			$context .= " (sonetlog {$attachedModel->getEntityId()})";

			return $context;
		}
		elseif($connector instanceof TaskConnector)
		{
			$context .= " (task {$attachedModel->getEntityId()})";

			return $context;
		}
		else
		{
			$context .= " (stub {$attachedModel->getEntityId()})";

			return $context;
		}
	}

	protected function sendNeedAuth($authUrl)
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_NEED_AUTH,
			'authUrl' => $authUrl,
			'isBitrix24' => ModuleManager::isModuleInstalled('bitrix24'),
		));
	}
}