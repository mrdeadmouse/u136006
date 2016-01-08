<?php

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\User;
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Loader;
use Bitrix\Disk\Security\SecurityContext;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskFolderListComponent extends DiskComponent
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_FL_22001';
	const ERROR_COULD_NOT_FIND_SHARING = 'DISK_FL_22002';
	const ERROR_INVALID_DATA_TYPE      = 'DISK_FL_22003';

	const COUNT_ON_PAGE = 35;

	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->findFolder();

		return true;
	}

	protected function listActions()
	{
		return array(
			'withDeleted' => array(
				'method' => array('GET', 'POST'),
				'name' => 'withDeleted',
				'check_csrf_token' => false,
			),
		);
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if(isset($this->arParams['FOLDER']))
		{
			if(!$this->arParams['FOLDER'] instanceof Folder)
			{
				throw new \Bitrix\Main\ArgumentException('FOLDER not instance of \\Bitrix\\Disk\\Folder');
			}
			$this->arParams['FOLDER_ID'] = $this->arParams['FOLDER']->getId();
		}
		if(!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}

		if(empty($this->arParams['PATH_TO_GROUP']))
		{
			$this->arParams['PATH_TO_GROUP'] = '/';
		}

		if(!empty($_GET['viewMode']))
		{
			$this->storeViewMode($_GET['viewMode']);
		}

		return $this;
	}

	private function processGridActions($gridId)
	{
		$postAction = 'action_button_'.$gridId;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]) && check_bitrix_sessid())
		{
			$userId = $this->getUser()->getID();
			if($_POST[$postAction] == 'edit')
			{
				if(empty($_POST['FIELDS']))
				{
					return;
				}
				foreach($_POST['FIELDS'] as $id => $sourceData)
				{
					if(empty($sourceData['NAME']))
					{
						//inline edit for name only
						continue;
					}
					/** @var Folder|File $object */
					$object = BaseObject::loadById($id);
					if(!$object)
					{
						continue;
					}
					if(!$object->canRename($object->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}
					if(!$object->rename($sourceData['NAME']))
					{
						$this->errorCollection->add($object->getErrors());
					}
				}
				unset($id, $sourceData);
			}
			elseif($_POST[$postAction] == 'delete')
			{
				if(empty($_POST['ID']))
				{
					return;
				}
				foreach($_POST['ID'] as $targetId)
				{
					/** @var Folder|File $object */
					$object = BaseObject::loadById($targetId);
					if(!$object)
					{
						continue;
					}
					if(!$object->canMarkDeleted($object->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}
					$object->markDeleted($userId);
				}
			}
			elseif($_POST[$postAction] == 'move' && !empty($_POST['grid_group_action_target_object']))
			{
				/** @var Folder $targetFolder */
				$targetFolder = Folder::loadById((int)$_POST['grid_group_action_target_object'], array('STORAGE'));
				if(!$targetFolder)
				{
					return;
				}
				if(empty($_POST['ID']))
				{
					return;
				}
				$ids = array();
				foreach($_POST['ID'] as $targetId)
				{
					/** @var Folder|File $object */
					$object = BaseObject::loadById((int)$targetId);
					if(!$object)
					{
						continue;
					}
					$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
					if(!$object->canMove($securityContext, $targetFolder))
					{
						continue;
					}
					if($object->moveTo($targetFolder, $userId, true))
					{
						$ids[] = 'hl-' . $object->getId();
					}
				}
				$targetName = '';
				if($targetFolder->getId() != $targetFolder->getStorage()->getRootObjectId())
				{
					$targetName = $targetFolder->getName();
				}
				LocalRedirect(Driver::getInstance()->getUrlManager()->getPathInListing($targetFolder) . $targetName . '#' . implode(',', $ids));
			}
			elseif($_POST[$postAction] == 'copy' && !empty($_POST['grid_group_action_target_object']))
			{
				/** @var Folder $targetFolder */
				$targetFolder = Folder::loadById((int)$_POST['grid_group_action_target_object'], array('STORAGE'));
				if(!$targetFolder)
				{
					return;
				}
				if(empty($_POST['ID']))
				{
					return;
				}
				$ids = array();
				foreach($_POST['ID'] as $targetId)
				{
					/** @var Folder|File $object */
					$object = BaseObject::loadById((int)$targetId, array('STORAGE'));
					if(!$object)
					{
						continue;
					}
					if(!$object->canRead($object->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}

					if(!$targetFolder->canAdd($targetFolder->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}

					$newObject = $object->copyTo($targetFolder, $userId, true);
					if($newObject)
					{
						$ids[] = 'hl-' . $newObject->getId();
					}
				}
				$targetName = '';
				if($targetFolder->getId() != $targetFolder->getStorage()->getRootObjectId())
				{
					$targetName = $targetFolder->getName();
				}
				LocalRedirect(Driver::getInstance()->getUrlManager()->getPathInListing($targetFolder) . $targetName . '#' . implode(',', $ids));
			}
		}
	}

	protected function processActionDefault()
	{
		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$securityContext = $this->storage->getCurrentUserSecurityContext();

		$this->arParams['STATUS_BIZPROC'] = $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc");

		$gridId = 'folder_list_' . $this->storage->getId();

		$this->processGridActions($gridId);

		$errorsInGridActions = array();
		if($this->errorCollection->hasErrors())
		{
			foreach($this->getErrors() as $error)
			{
				/** @var Error $error */
				$errorsInGridActions[] = array(
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				);
			}
			$this->errorCollection->clear();
		}

		$proxyType = $this->storage->getProxyType();
		$isGroupStorage = $proxyType instanceof ProxyType\Group;
		$isConnectedGroupStorage = false;
		$groupSharingData = array();
		if($isGroupStorage)
		{
			$isConnectedGroupStorage = Sharing::isConnectedToUserStorage($this->getUser()->getId(), $this->storage->getRootObject(), $groupSharingData);
		}

		if($this->arParams['STATUS_BIZPROC'])
		{
			$documentData = array(
				'DISK' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
				),
				'WEBDAV' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
				),
			);
			$this->arParams['TEMPLATE_BIZPROC'] = $this->getTemplateBizProc($documentData);
		}

		$this->arResult = array(
			'ERRORS_IN_GRID_ACTIONS' => $errorsInGridActions,
			'GRID' => $this->getGridData($gridId),
			'IS_BITRIX24' => \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'),

			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),
			'STORAGE' => array(
				'NETWORK_DRIVE_LINK' => Driver::getInstance()->getUrlManager()->getHostUrl() . $proxyType->getBaseUrlFolderList(),
				'ID' => $this->storage->getId(),
				'NAME' => $proxyType->getEntityTitle(),
				'LINK' => $proxyType->getStorageBaseUrl(),
				'LINK_ON_HELP' => $proxyType->getStorageBaseUrl() . 'help',
				'ROOT_OBJECT_ID' => $this->storage->getRootObjectId(),
				'CAN_CHANGE_RIGHTS_ON_STORAGE' => $this->storage->canChangeRights($securityContext),
				'CAN_CHANGE_SETTINGS_ON_STORAGE' => $this->storage->canChangeSettings($securityContext),
				'CAN_CHANGE_SETTINGS_ON_BIZPROC' => $this->storage->canCreateWorkflow($securityContext),
				'CAN_CHANGE_SETTINGS_ON_BIZPROC_EXCEPT_USER' => $proxyType instanceof ProxyType\User ? false : true,
				'SHOW_BIZPROC' => $this->arParams['STATUS_BIZPROC'],
				'FOR_SOCNET_GROUP' => $isGroupStorage,
				'CONNECTED_SOCNET_GROUP' => $isConnectedGroupStorage,
				'CONNECTED_SOCNET_GROUP_OBJECT_ID' => isset($groupSharingData['LINK_OBJECT_ID'])? $groupSharingData['LINK_OBJECT_ID'] : null,
			),
			'BREADCRUMBS' => $this->getBreadcrumbs(),
			'BREADCRUMBS_ROOT' => array(
				'NAME' => $proxyType->getTitleForCurrentUser(),
				'LINK' => $proxyType->getBaseUrlFolderList(),
				'ID' => $this->storage->getRootObjectId(),
			),
			'URL_TO_DETACH_OBJECT' => '?action=detachConnectedObject',
		);

		if($this->arParams['STATUS_BIZPROC'])
		{
			$this->getAutoloadTemplateBizProc($documentData);
		}

		$this->includeComponentTemplate();
	}

	protected function findFolder()
	{
		$this->folder = \Bitrix\Disk\Folder::loadById($this->arParams['FOLDER_ID']);

		if(!$this->folder)
		{
			throw new \Bitrix\Main\SystemException("Invalid file.");
		}
		return $this;
	}

	private function storeViewMode($mode)
	{
		$mode = strtolower($mode);
		if($mode != 'grid' && $mode != 'tile')
		{
			$mode = 'grid';
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, 'grid', array('viewMode' => $mode));
	}

	private function getViewMode()
	{
		static $viewMode = null;
		if($viewMode !== null)
		{
			return $viewMode;
		}
		$userSettings = \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, 'grid', array('viewMode' => 'grid'));
		$viewMode = $userSettings['viewMode'];

		return $viewMode;
	}

	private function needPagination()
	{
		//temporary-permanent hack. In these folders we have many files.
		return
			$this->storage->getProxyType() instanceof ProxyType\Common &&
			in_array($this->folder->getXmlId(), array(
				'VI_CALLS',
				'CRM_EMAIL_ATTACHMENTS',
				'CRM_CALL_RECORDS',
				'CRM_REST',
			), true)
		;
	}

	private function getGridData($gridId, $showDeleted = false)
	{
		$grid = array(
			'ID' => $gridId,
		);

		$gridOptions = new CGridOptions($grid['ID']);
		$gridSort = $gridOptions->getSorting(array(
			'sort' => array('NAME' => 'ASC'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));
		$filter = array();

		$grid['SORT'] = $gridSort['sort'];
		$grid['SORT_VARS'] = $gridSort['vars'];
		$grid['MODE'] = $this->getViewMode();

		$possibleColumnForSorting = array(
			'UPDATE_TIME' => array('ALIAS' => 'UPDATE_TIME', 'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_UPDATE_TIME')),
			'NAME' => array('ALIAS' => 'NAME', 'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_NAME')),
			'FORMATTED_SIZE' => array('ALIAS' => 'SIZE', 'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_FORMATTED_SIZE')),
		);

		$byColumn = key($grid['SORT']);
		if(!isset($possibleColumnForSorting[$byColumn]) || (strtolower($grid['SORT'][$byColumn]) !== 'desc' && strtolower($grid['SORT'][$byColumn]) !== 'asc'))
		{
			$grid['SORT'] = array();
		}
		$order = $grid['SORT'];
		$byColumn = key($order);
		$sortingColumns = array(
			'TYPE' => array(SORT_NUMERIC, SORT_ASC),
			$possibleColumnForSorting[$byColumn]['ALIAS'] => strtolower($order[$byColumn]) === 'asc' ? SORT_ASC : SORT_DESC,
		);
		if($byColumn !== 'NAME')
		{
			$sortingColumns[$possibleColumnForSorting['NAME']['ALIAS']] = SORT_ASC;
		}


		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$proxyType = $this->storage->getProxyType();
		$isStorageCurrentUser = $proxyType instanceof ProxyType\User && $proxyType->getTitleForCurrentUser() != $proxyType->getTitle();

		$parameters = array(
			'with' => array(
				'CREATE_USER',
			),
			'filter' => array(
				'PARENT_ID' => $this->folder->getRealObjectId(),
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
		);
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));

		$needPagination = $this->needPagination();
		$pageNumber = (int)$this->request->getQuery('pageNumber');
		if($pageNumber <= 0)
		{
			$pageNumber = 1;
		}
		if($needPagination)
		{

			$parameters['order'] = array();
			foreach($sortingColumns as $columnName => $columnData)
			{
				if(is_array($columnData))
				{
					$parameters['order'][$columnName] = in_array(SORT_DESC, $columnData, true)? 'DESC' : 'ASC';
				}
				else
				{
					$parameters['order'][$columnName] = SORT_DESC === $columnData? 'DESC' : 'ASC';
				}
			}
			unset($columnName, $columnData);

			$parameters['limit'] = self::COUNT_ON_PAGE + 1; // +1 because we want to know about existence next page
			$parameters['offset'] = self::COUNT_ON_PAGE * ($pageNumber - 1);
		}

		$this->folder->preloadOperationsForChildren($securityContext);
		$sharedObjectIds = $this->getUserShareObjectIds();

		$isDesktopDiskInstall = \Bitrix\Disk\Desktop::isDesktopDiskInstall();

		$nowTime = time() + CTimeZone::getOffset();
		$fullFormatWithoutSec = preg_replace('/:s$/', '', CAllDatabase::dateFormatToPHP(CSite::GetDateFormat("FULL")));

		$urlManager = Driver::getInstance()->getUrlManager();
		$rows = array();
		$storageTitle = $proxyType->getTitle();
		$isEnabledShowExtendedRights = $this->storage->isEnabledShowExtendedRights();
		$result = $this->folder->getList($parameters);

		$countObjectsOnPage = 0;
		$needShowNextPagePagination = false;
		while($row = $result->fetch())
		{
			$countObjectsOnPage++;

			if($needPagination && $countObjectsOnPage > self::COUNT_ON_PAGE)
			{
				$needShowNextPagePagination = true;
				break;
			}

			$object = BaseObject::buildFromArray($row);
			/** @var File|Folder $object */
			$name = $object->getName();
			$objectId = $object->getId();
			$exportData = array(
				'TYPE' => $object->getType(),
				'NAME' => $name,
				'ID' => $objectId,
			);

			$relativePath = trim($this->arParams['RELATIVE_PATH'], '/');
			$detailPageFile = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
				'FILE_ID' => $objectId,
				'FILE_PATH' => ltrim($relativePath . '/' . $name, '/'),
			));
			$listingPage = rtrim(CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
				'PATH' => $relativePath,
			)), '/');

			$isFolder = $object instanceof Folder;
			$actions = $tileActions = $columns = array();
			if($object->canRead($securityContext))
			{
				$exportData['OPEN_URL'] = $urlManager->encodeUrn($isFolder? $listingPage . '/' . $name . '/' : $detailPageFile);
				$actions[] = array(
					"PSEUDO_NAME" => "open",
					"DEFAULT" => true,
					"ICONCLASS" => "show",
					"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_OPEN'),
					"ONCLICK" => "jsUtils.Redirect(arguments, '" . $exportData['OPEN_URL'] . "')",
				);

				if(!$object->canChangeRights($securityContext) && !$object->canShare($securityContext))
				{
					$actions[] = array(
						"PSEUDO_NAME" => "share",
						"ICONCLASS" => "share",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_2'),
						"ONCLICK" =>
							"BX.Disk.showSharingDetailWithoutEdit({
								ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}
				elseif($object->canChangeRights($securityContext))
				{
					$actions[] = array(
						"PSEUDO_NAME" => "share",
						"ICONCLASS" => "share",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_2'),
						"ONCLICK" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].showSharingDetailWithChangeRights({
								ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}
				elseif($object->canShare($securityContext))
				{
					$actions[] = array(
						"PSEUDO_NAME" => "share",
						"ICONCLASS" => "share",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_SHARING_DETAIL_2'),
						"ONCLICK" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].showSharingDetailWithSharing({
								ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}

				if($isEnabledShowExtendedRights && !$object->isLink() && $object->canChangeRights($securityContext))
				{
					$actions[] = array(
						"PSEUDO_NAME" => "rights",
						"ICONCLASS" => "rights",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_RIGHTS_SETTINGS'),
						"ONCLICK" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].showRightsOnObjectDetail({
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}

				if(!$isFolder)
				{
					$actions[] = array(
						"PSEUDO_NAME" => "download",
						"ICONCLASS" => "download",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_DOWNLOAD'),
						"ONCLICK" => "jsUtils.Redirect(arguments, '" . $urlManager->getUrlForDownloadFile($object) . "')",
					);
				}
				$actions[] = array(
					"PSEUDO_NAME" => "copy",
					"ICONCLASS" => "copy",
					"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY'),
					"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].openCopyModalWindow({
						id: {$this->storage->getRootObjectId()},
						name: '" . CUtil::JSEscape($storageTitle) . "'
					}, {
						id: {$objectId},
						name: '" . CUtil::JSEscape($name) . "'
					});",
				);

				if($object->canDelete($securityContext))
				{
					$actions[] = array(
						"PSEUDO_NAME" => "move",
						"ICONCLASS" => "move",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_MOVE'),
						"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].openMoveModalWindow({
							id: {$this->storage->getRootObjectId()},
							name: '" . CUtil::JSEscape($storageTitle) . "'
						}, {
							id: {$objectId},
							name: '" . CUtil::JSEscape($name) . "'
						});",
					);
				}

				if(
					!$isStorageCurrentUser &&
					(!isset($sharedObjectIds[$object->getRealObjectId()]) ||
					$sharedObjectIds[$object->getRealObjectId()]['TO_ENTITY'] != Sharing::CODE_USER . $this->getUser()->getId())
				)
				{
					$actions[] = array(
						"PSEUDO_NAME" => "connect",
						"ICONCLASS" => "connect",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_CONNECT'),
						"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].connectObjectToDisk({
							object: {
								id: {$objectId},
								name: '" . CUtil::JSEscape($name) . "',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							}
						});",
					);
				}

				if(!$isFolder)
				{
					$actions[] = array(
						"ICONCLASS" => "show",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_GET_EXT_LINK'),
						"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].getExternalLink({$objectId});",
					);
					$downloadLink = $urlManager->getUrlForShowFile($object, array(), true);
					$actions[] = array(
						"ICONCLASS" => "show",
						'PSEUDO_NAME' => 'internal_link',
						'PSEUDO_VALUE' => $downloadLink,
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_COPY_INTERNAL_LINK'),
						"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].getInternalLink('{$downloadLink}');",
					);
					$actions[] = array(
						"ICONCLASS" => "history",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_SHOW_HISTORY'),
						"ONCLICK" => "jsUtils.Redirect(arguments, '" . $exportData['OPEN_URL'] . "#tab-history')",
					);
				}
			}

			if($object->canRename($securityContext))
			{
				$actions[] = array(
					"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_RENAME'),
					"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].renameInline({$objectId})",
				);
			}

			if((!empty($sharedObjectIds[$objectId]) || $object->isLink()) && $object->canRead($securityContext))
			{
				$tileActions['SHARE_INFO'] = array(
					"ICONCLASS" => "show",
					"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_DETAIL_SHARE_INFO'),
					"ONCLICK" => "BX.Disk['FolderListClass_{$this->componentId}'].showShareInfoSmallView({
						object: {
							id: {$objectId},
							name: '{$name}',
							isFolder: " . ($isFolder? 'true' : 'false') . "
						 }
					})",
				);
			}

			$columnsBizProc = array(
				'BIZPROC' => ''
			);
			$bizprocIcon = array(
				'BIZPROC' => ''
			);
			if($this->arParams['STATUS_BIZPROC'] && !$isFolder)
			{
				list($actions, $columnsBizProc, $bizprocIcon) = $this->getBizProcData($object, $securityContext, $actions, $columnsBizProc, $bizprocIcon, $exportData);
			}

			if($object->canDelete($securityContext))
			{
				if($object->isLink())
				{
					$actions[] = array(
						"PSEUDO_NAME" => "detach",
						"ICONCLASS" => "detach",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_DETACH_BUTTON'),
						"ONCLICK" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDetach({
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 }
							})",
					);
				}
				elseif($object->getCode() !== Folder::CODE_FOR_UPLOADED_FILES)
				{
					$actions[] = array(
						"PSEUDO_NAME" => "delete",
						"ICONCLASS" => "delete",
						"TEXT" => Loc::getMessage('DISK_FOLDER_LIST_ACT_MARK_DELETED'),
						"ONCLICK" =>
							"BX.Disk['FolderListClass_{$this->componentId}'].openConfirmDelete({
								object: {
									id: {$objectId},
									name: '{$name}',
									isFolder: " . ($isFolder? 'true' : 'false') . "
								 },
								canDelete: {$object->canDelete($securityContext)}
							})",
					);
				}
			}
			$iconClass = Ui\Icon::getIconClassByObject($object, !empty($sharedObjectIds[$objectId]));
			$dataAttributesForViewer = '';
			if($isFolder)
			{
				$dataAttributesForViewer = Ui\Viewer::getAttributesByObject($object);
				if($grid['MODE'] === 'tile')
				{
					$exportData['VIEWER_ATTRS'] = $dataAttributesForViewer;
					$dataAttributesForViewer = '';
				}

				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
							<td style=\"width: 45px;\"><div data-object-id=\"{$objectId}\" class=\"draggable bx-file-icon-container-small {$iconClass}\"></div></td>
							<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\" {$dataAttributesForViewer}>{$nameSpecialChars}</a></td>
					</tr></table>
				";
			}
			else
			{
				$externalId = '';
				if($isDesktopDiskInstall && $isStorageCurrentUser)
				{
					$externalId = "st{$this->storage->getId()}|{$this->storage->getRootObjectId()}|f{$objectId}";
				}
				$dataAttributesForViewer = Ui\Viewer::getAttributesByObject($object, array(
					'canUpdate' => $object->canUpdate($securityContext),
					'relativePath' => $relativePath . '/' . $name,
					'externalId' => $externalId,
				));
				if($grid['MODE'] === 'tile')
				{
					$exportData['VIEWER_ATTRS'] = $dataAttributesForViewer;
					$dataAttributesForViewer = '';
				}

				$nameSpecialChars = htmlspecialcharsbx($name);
				$columnName = "
					<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$objectId}\" class=\"draggable bx-file-icon-container-small {$iconClass}\"></div></td>
						<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$objectId}\" href=\"{$exportData['OPEN_URL']}\" {$dataAttributesForViewer}>{$nameSpecialChars}</a></td>
						<td>{$bizprocIcon['BIZPROC']}</td>
					</tr></table>
				";
			}

			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $object->getCreatedBy()));

			$timestampCreate = $object->getCreateTime()->toUserTime()->getTimestamp();
			$timestampUpdate = $object->getUpdateTime()->toUserTime()->getTimestamp();
			$columns = array(
				'CREATE_TIME' => ($nowTime - $timestampCreate > 158400)? formatDate($fullFormatWithoutSec, $timestampCreate, $nowTime) : formatDate('x', $timestampCreate, $nowTime),
				'UPDATE_TIME' => ($nowTime - $timestampCreate > 158400)? formatDate($fullFormatWithoutSec, $timestampUpdate, $nowTime) : formatDate('x', $timestampUpdate, $nowTime),
				'NAME' => $columnName,
				'FORMATTED_SIZE' => $isFolder? '' : CFile::formatSize($object->getSize()),
				'CREATE_USER' => "
					<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($object->getCreateUser()->getFormattedName()) . "</a></div>
				",
			);

			if($this->arParams['STATUS_BIZPROC'])
			{
				$columns['BIZPROC'] = $columnsBizProc["BIZPROC"];
			}

			$exportData['ICON_CLASS'] = $iconClass;
			if($grid['MODE'] === 'tile')
			{
				$exportData['IS_IMAGE'] = $isFolder? false : \Bitrix\Disk\TypeFile::isImage($object);
				if($exportData['IS_IMAGE'])
				{
					$exportData['SRC_IMAGE'] = $urlManager->getUrlForShowFile($object, array('exact' => 'Y', 'width' => 64, 'height' => 64,));
				}
				$exportData['UPDATE_TIME'] = $columns['UPDATE_TIME'];
			}
			$exportData['IS_SHARED'] = !empty($sharedObjectIds[$objectId]);
			$exportData['IS_LINK'] = $object->isLink();
			$tildaExportData = array();
			foreach($exportData as $exportName => $exportValue)
			{
				$tildaExportData['~' . $exportName] = $exportValue;
			}
			unset($exportRow);
			$rows[] = array(
				'data' => array_merge($exportData, $tildaExportData),
				'columns' => $columns,
				'actions' => $actions,
				'tileActions' => $tileActions,
				//for sortByColumn
				'TYPE' => $exportData['TYPE'],
				'NAME' => $exportData['NAME'],
				'UPDATE_TIME' => $object->getUpdateTime()->getTimestamp(),
				'SIZE' => $isFolder? 0 : $object->getSize(),
			);
		}
		unset($object);

		if(!$needPagination)
		{
			Collection::sortByColumn($rows, $sortingColumns);
		}

		$grid['HEADERS'] = array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'sort' => isset($possibleColumnForSorting['ID']) ? 'ID' : false,
				'default' => false,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_NAME'),
				'sort' => isset($possibleColumnForSorting['NAME']) ? 'NAME' : false,
				'default' => true,
				'editable' => array(
					'size' => 45,
				),
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_CREATE_TIME'),
				'sort' => isset($possibleColumnForSorting['CREATE_TIME']) ? 'CREATE_TIME' : false,
				'default' => false,
			),
			array(
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_UPDATE_TIME'),
				'sort' => isset($possibleColumnForSorting['UPDATE_TIME']) ? 'UPDATE_TIME' : false,
				'default' => true,
			),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_CREATE_USER'),
				'sort' => isset($possibleColumnForSorting['CREATE_USER']) ? 'CREATE_USER' : false,
				'default' => false,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_FORMATTED_SIZE'),
				'sort' => isset($possibleColumnForSorting['FORMATTED_SIZE']) ? 'FORMATTED_SIZE' : false,
				'default' => true,
			),
		);

		if($this->arParams['STATUS_BIZPROC'])
		{
			$grid['HEADERS'][] = array(
				'id' => 'BIZPROC',
				'name' => Loc::getMessage('DISK_FOLDER_LIST_COLUMN_BIZPROC'),
				'default' => false,
			);
		}

		$grid['DATA_FOR_PAGINATION'] = array(
			'ENABLED' => $needPagination,
		);
		if($needPagination)
		{
			$grid['DATA_FOR_PAGINATION']['SHOW_NEXT_PAGE'] = $needShowNextPagePagination;
			$grid['DATA_FOR_PAGINATION']['CURRENT_PAGE'] = $pageNumber;
		}

		$grid['COLUMN_FOR_SORTING'] = $possibleColumnForSorting;
		$grid['ROWS'] = $rows;
		$grid['ROWS_COUNT'] = count($rows);
		$grid['FOOTER'] = array();
		if(isModuleInstalled('bitrix24'))
		{
			list($freeSpace, $diskSize) = $this->getDiskSpace();
			$freeSpace = CFile::formatSize($freeSpace);
			$diskSize = CFile::formatSize($diskSize);
			$grid['FOOTER'] = array(
				array(
					'custom_html' => '
						<td class="tar" style="width: 100%;">' . Loc::getMessage('DISK_FOLDER_LIST_B24_LABEL_DISK_SPACE', array(
							'#FREE_SPACE#' => '<span>' . $freeSpace,
							'#DISK_SIZE#' => $diskSize . '</span>',
						)) . '</span></td>
					',
				),
				array(
					'custom_html' => '
						<td class="tar"><a class="bx-disk-mp-link-addhdd" href="' . Loc::getMessage('DISK_FOLDER_LIST_B24_URL_DISK_SPACE') . '" target="_blank">+ <span>' . Loc::getMessage('DISK_FOLDER_LIST_B24_APPEND_DISK_SPACE') . '</span></a></td>
					',
				)
			);
		}

		return $grid;
	}

	private function getDiskSpace()
	{
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$freeSpace = CDiskQuota::GetDiskQuota();
		return array(
			$freeSpace === false? 0 : $freeSpace,
			(float)COption::GetOptionInt('main', 'disk_space', 0)*1024*1024,
		);
	}

	private function getUserShareObjectIds()
	{
		$sharedObjectIds = array();
		foreach(SharingTable::getList(array(
			'select' => array('REAL_OBJECT_ID', 'TO_ENTITY', 'FROM_ENTITY'),
			'filter' => array(
				array(
					'LOGIC' => 'OR',
					'=TO_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
					'=FROM_ENTITY' => Sharing::CODE_USER . $this->getUser()->getId(),
				),
				'!=STATUS' => SharingTable::STATUS_IS_DECLINED,
				'REAL_STORAGE_ID' => $this->folder->getStorageId(),
			),
		))->fetchAll() as $row)
		{
			$sharedObjectIds[$row['REAL_OBJECT_ID']] = $row;
		}
		unset($row);

		return $sharedObjectIds;
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		foreach($this->arParams['RELATIVE_ITEMS'] as $i => $item)
		{
			if(empty($item))
			{
				continue;
			}
			$crumbs[] = array(
				'ID' => $item['ID'],
				'NAME' => $item['NAME'],
				'LINK' => rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
					'PATH' => implode('/', (array_slice($parts, 0, $i + 1)))?: '',
				)), '/') . '/',
			);
		}
		unset($i, $item);

		return $crumbs;
	}

	protected function processActionWithDeleted()
	{
		$gridId = 'folder_list';
		$this->arResult = array(
			'GRID' => $this->getGridData($gridId, true),

			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),

			'BREADCRUMBS' => $this->getBreadcrumbs(),
		);

		$this->includeComponentTemplate();
	}

	protected function getTemplateBizProc($documentData)
	{
		$temporary = array();
		foreach($documentData as $nameModule => $data)
		{
			$res = CBPWorkflowTemplateLoader::getList(
				array(),
				array('DOCUMENT_TYPE' => $data['DOCUMENT_TYPE']),
				false,
				false,
				array("ID", "NAME")
			);
			while ($workflowTemplate = $res->getNext())
			{
				if($nameModule == 'DISK')
				{
					$old = '';
					$templateName = $workflowTemplate["NAME"];
				}
				else
				{
					$old = '&old=1';
					$templateName = $workflowTemplate["NAME"]." ".Loc::getMessage('DISK_FOLDER_LIST_ACT_BIZPROC_OLD_TEMPLATE');
				}
				$url = $this->arParams['PATH_TO_DISK_START_BIZPROC'];
				$url .= "?back_url=".urlencode($this->getApplication()->getCurPageParam());
				$url .= (strpos($url, "?") === false ? "?" : "&")."workflow_template_id=".$workflowTemplate["ID"].$old.'&'.bitrix_sessid_get();
				$temporary[$workflowTemplate["ID"]]['NAME'] = $templateName;
				$temporary[$workflowTemplate["ID"]]['URL'] = $url;
			}
		}
		return $temporary;
	}

	protected function getAutoloadTemplateBizProc($documentData)
	{
		$this->arResult['WORKFLOW_TEMPLATES'] = array();
		$this->arResult['BIZPROC_PARAMETERS'] = false;
		foreach($documentData as $nameModule => $data)
		{
			$workflowTemplateObject = CBPWorkflowTemplateLoader::getList(
				array(),
				array("DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"], "AUTO_EXECUTE" => CBPDocumentEventType::Create, "ACTIVE" => "Y"),
				false,
				false,
				array("ID", "NAME", "DESCRIPTION", "PARAMETERS")
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				if(!empty($workflowTemplate['PARAMETERS']))
				{
					$this->arResult['BIZPROC_PARAMETERS'] = true;
					$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['PARAMETERS'] = $workflowTemplate['PARAMETERS'];
				}
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['ID'] = $workflowTemplate['ID'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['NAME'] = $workflowTemplate['NAME'];
			}
		}
	}

	protected function getBizProcData(BaseObject $object, SecurityContext $securityContext, array $actions, array $columnsBizProc, array $bizprocIcon, array $exportData)
	{
		$documentData = array(
			'DISK'   => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($object->getId()),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
				'DOCUMENT_ID'   => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($object->getId()),
			),
		);

		$listBpTemplates = array();
		foreach ($this->arParams['TEMPLATE_BIZPROC'] as $idTemplate => $valueTemplate)
		{
			$url = CComponentEngine::MakePathFromTemplate($valueTemplate['URL'], array("ELEMENT_ID" => $object->getId()));
			$listBpTemplates[] = array(
				"ICONCLASS" => "",
				"TEXT"      => $valueTemplate['NAME'],
				"ONCLICK"   => "jsUtils.Redirect([], '".CUtil::JSEscape($url)."');"
			);
		}

		if ($object->canStartBizProc($securityContext) && !empty($listBpTemplates))
		{
			$actions[] = array(
				"ICONCLASS" => "bizproc_start",
				"TEXT"      => Loc::getMessage("DISK_FOLDER_LIST_ACT_START_BIZPROC"),
				"MENU"      => $listBpTemplates
			);
		}

		$webdavFileId = $object->getXmlId();
		if (!empty($webdavFileId))
		{
			if (Loader::includeModule("iblock"))
			{
				if ($this->storage->getProxyType() instanceof ProxyType\Group)
				{
					$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
					$entity = 'CIBlockDocumentWebdavSocnet';
				}
				else
				{
					$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
					$entity = 'CIBlockDocumentWebdav';
				}
				if (!empty($iblock))
				{
					$documentData['OLD_FILE'] = array(
						'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
						'DOCUMENT_ID'   => array('webdav', $entity, $iblock['ID']),
					);
				}
			}
		}

		foreach ($documentData as $nameModuleId => $data)
		{
			$temporary[$nameModuleId] = CBPDocument::getDocumentStates($data['DOCUMENT_TYPE'], $data['DOCUMENT_ID']);
		}
		if (isset($temporary['OLD_FILE']))
		{
			$documentStates = array_merge($temporary['DISK'], $temporary['WEBDAV'], $temporary['OLD_FILE']);
		}
		else
		{
			$documentStates = array_merge($temporary['DISK'], $temporary['WEBDAV']);
		}
		foreach ($documentStates as $key => $documentState)
		{
			if (empty($documentState['ID']))
			{
				unset($documentStates[$key]);
			}
		}
		$columnsBizProc['BIZPROC'] = "";
		$bizprocIcon['BIZPROC'] = "";
		if (!empty($documentStates))
		{
			if (count($documentStates) == 1)
			{
				$documentState = reset($documentStates);
				if ($documentState['WORKFLOW_STATUS'] > 0 || empty($documentState['WORKFLOW_STATUS']))
				{
					$tasksWorkflow = CBPDocument::getUserTasksForWorkflow($this->getUser()->GetID(), $documentState["ID"]);
					$columnsBizProc["BIZPROC"] =
						'<div class="bizproc-item-title">'.htmlspecialcharsbx($documentState["TEMPLATE_NAME"]).': '.
						'<span class="bizproc-item-title bizproc-state-title" style="">'.
						'<a href="'.$exportData["OPEN_URL"].'#tab-bp">'.
						(strlen($documentState["STATE_TITLE"]) > 0 ? htmlspecialcharsbx($documentState["STATE_TITLE"]) : htmlspecialcharsbx($documentState["STATE_NAME"])).
						'</a>'.
						'</span>'.
						'</div>';
					$columnsBizProc['BIZPROC'] = str_replace("'", "\"", $columnsBizProc['BIZPROC']);

					$bizprocIcon["BIZPROC"] = "<div class=\"element-bizproc-status bizproc-statuses ".
						(!(strlen($documentState["ID"]) <= 0 || strlen($documentState["WORKFLOW_STATUS"]) <= 0) ?
							'bizproc-status-'.(empty($tasksWorkflow) ? "inprogress" : "attention") : '').
						"\" onmouseover='BX.hint(this, \"".addslashes($columnsBizProc["BIZPROC"])."\")'></div>";

					if (!empty($tasksWorkflow))
					{
						$tmp = array();
						foreach ($tasksWorkflow as $val)
						{
							$url = CComponentEngine::makePathFromTemplate($this->arParams["PATH_TO_DISK_TASK"], array("ID" => $val["ID"]));
							$url .= "?back_url=".urlencode($this->getApplication()->getCurPageParam());
							$tmp[] = '<a href="'.$url.'">'.$val["NAME"].'</a>';
						}
						$columnsBizProc["BIZPROC"] .= '<div class="bizproc-tasks">'.implode(", ", $tmp).'</div>';

						return array($actions, $columnsBizProc, $bizprocIcon);
					}

					return array($actions, $columnsBizProc, $bizprocIcon);
				}

				return array($actions, $columnsBizProc, $bizprocIcon);
			}
			else
			{
				$tasks = array();
				$inprogress = false;
				foreach ($documentStates as $key => $documentState)
				{
					if ($documentState['WORKFLOW_STATUS'] > 0 || empty($documentState['WORKFLOW_STATUS']))
					{
						$tasksWorkflow = CBPDocument::getUserTasksForWorkflow($this->getUser()->GetID(), $documentState["ID"]);
						if (!$inprogress)
							$inprogress = (strlen($documentState['ID']) > 0 && strlen($documentState['WORKFLOW_STATUS']) > 0);
						if (!empty($tasksWorkflow))
						{
							foreach ($tasksWorkflow as $val)
							{
								$tasks[] = $val;
							}
						}
					}
				}

				$columnsBizProc["BIZPROC"] =
					'<span class="bizproc-item-title">'.
					Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC").': <a href="'.$exportData["OPEN_URL"].'#tab-bp" title="'.
					Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TITLE").'">'.count($documentStates).'</a></span>'.
					(!empty($tasks) ?
						'<br /><span class="bizproc-item-title">'.
						Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TASKS").': <a href="'.$this->arParams["PATH_TO_DISK_TASK_LIST"].'" title="'.
						Loc::getMessage("DISK_FOLDER_LIST_GRID_BIZPROC_TASKS_TITLE").'">'.count($tasks).'</a></span>' : '');
				$bizprocIcon["BIZPROC"] = "<div class=\"element-bizproc-status bizproc-statuses ".
					($inprogress ? ' bizproc-status-'.(empty($tasks) ? "inprogress" : "attention") : '').
					"\" onmouseover='BX.hint(this, \"".addslashes($columnsBizProc['BIZPROC'])."\")'></div>";

				return array($actions, $columnsBizProc, $bizprocIcon);
			}
		}

		return array($actions, $columnsBizProc, $bizprocIcon);
	}
}