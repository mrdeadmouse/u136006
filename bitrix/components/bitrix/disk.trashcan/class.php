<?php

use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskTrashCanComponent extends DiskComponent
{
	const ERROR_COULD_NOT_FIND_OBJECT = 'DISK_TC_22001';

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

	protected function prepareParams()
	{
		parent::prepareParams();

		if(!isset($this->arParams['FOLDER_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID required');
		}
		$this->arParams['FOLDER_ID'] = (int)$this->arParams['FOLDER_ID'];
		if($this->arParams['FOLDER_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FOLDER_ID < 0');
		}

		return $this;
	}

	private function processGridActions($gridId)
	{
		$postAction = 'action_button_'.$gridId;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]) && check_bitrix_sessid())
		{
			$userId = $this->getUser()->getID();
			if($_POST[$postAction] == 'restore')
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
					if(!$object->canRestore($object->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}
					$object->restore($userId);
				}
			}
			elseif($_POST[$postAction] == 'delete' || $_POST[$postAction] == 'destroy')
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
					if(!$object->canDelete($object->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}
					if($object instanceof Folder)
					{
						$object->deleteTree($userId);
					}
					else
					{
						$object->delete($userId);
					}
				}
			}
		}
	}

	protected function processActionDefault()
	{
		if(
			!$this->folder->canRead($this->storage->getCurrentUserSecurityContext()) ||
			!$this->folder->canRestore($this->storage->getCurrentUserSecurityContext())
		)
		{
			$this->showAccessDenied();
			return false;
		}
		$gridId = 'folder_list';

		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$this->processGridActions($gridId);
		$this->arResult = array(
			'GRID' => $this->getGridData($gridId),

			'FOLDER' => array(
				'ID' => $this->folder->getId(),
				'NAME' => $this->folder->getName(),
				'IS_DELETED' => $this->folder->isDeleted(),
				'CREATE_TIME' => $this->folder->getCreateTime(),
				'UPDATE_TIME' => $this->folder->getUpdateTime(),
			),
			'BREADCRUMBS' => $this->getBreadcrumbs(),
			'BREADCRUMBS_ROOT' => array(
				'NAME' => Loc::getMessage('DISK_TRASHCAN_NAME'),
				'LINK' => CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
					'TRASH_PATH' => '',
				)),
				'ID' => $this->storage->getRootObjectId(),
			),
		);

		$this->includeComponentTemplate();
	}

	protected function findFolder()
	{
		$this->folder = \Bitrix\Disk\Folder::loadById($this->arParams['FOLDER_ID']);

		if(!$this->folder)
		{
			throw new \Bitrix\Main\SystemException("Invalid folder.");
		}
		return $this;
	}

	private function getGridData($gridId)
	{
		$grid = array(
			'ID' => $gridId,
		);

		$filter = array();
		$securityContext = $this->storage->getCurrentUserSecurityContext();
		//shown trash can root
		if($this->arParams['RELATIVE_PATH'] == '/')
		{
			$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_ROOT;
			$items = $this->folder->getDescendants($securityContext, array(
				'with' => array(
					'CREATE_USER',
					'UPDATE_USER',
					'DELETE_USER',
				),
				'filter' => $filter,
			));
		}
		else
		{
			$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_CHILD;
			$items = $this->folder->getChildren($securityContext, array(
				'with' => array(
					'CREATE_USER',
					'UPDATE_USER',
					'DELETE_USER',
				),
				'filter' => $filter,
			));
		}

		if(count($items))
		{
			$this->folder->preloadOperationsForChildren($securityContext);
		}

		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$rows = array();
		foreach ($items as $object)
		{
			/** @var File|Folder $object */
			$exportData = $object->toArray();

			$relativePath = trim($this->arParams['RELATIVE_PATH'], '/');
			$detailPageFile = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_FILE_VIEW'], array(
				'FILE_ID' => $object->getId(),
				'TRASH_FILE_PATH' => ltrim($relativePath . '/' . $object->getOriginalName(), '/'),
			));
			$listingPage = rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
				'TRASH_PATH' => $relativePath,
			)), '/');

			$isFolder = $object instanceof Folder;
			$actions = array();

			$exportData['OPEN_URL'] = $urlManager->encodeUrn($isFolder? $listingPage . '/' . $object->getOriginalName() : $detailPageFile);
			$actions[] = array(
				"PSEUDO_NAME" => "open",
				"ICONCLASS" => "show",
				"TEXT" => Loc::getMessage('DISK_TRASHCAN_ACT_OPEN'),
				"ONCLICK" => "jsUtils.Redirect(arguments, '" . $exportData['OPEN_URL'] . "')",
			);

			if(!$isFolder)
			{
				$actions[] = array(
					"PSEUDO_NAME" => "download",
					"ICONCLASS" => "download",
					"TEXT" => Loc::getMessage('DISK_TRASHCAN_ACT_DOWNLOAD'),
					"ONCLICK" => "jsUtils.Redirect(arguments, '" . $urlManager->getUrlForDownloadFile($object) . "')",
				);
			}

			if($object->isDeleted() && $object->canRestore($securityContext))
			{
				$actions[] = array(
					"ICONCLASS" => "restore",
					"PSEUDO_NAME" => "restore",
					"TEXT" => Loc::getMessage('DISK_TRASHCAN_ACT_RESTORE'),
					"ONCLICK" =>
						"BX.Disk['TrashCanClass_{$this->getComponentId()}'].openConfirmRestore({
							object: {
								id: {$object->getId()},
								name: '{$object->getName()}',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							 }
						})",
				);
			}
			if($object->canDelete($securityContext))
			{
				$actions[] = array(
					"ICONCLASS" => "destroy",
					"PSEUDO_NAME" => "destroy",
					"TEXT" => Loc::getMessage('DISK_TRASHCAN_ACT_DESTROY'),
					"ONCLICK" =>
						"BX.Disk['TrashCanClass_{$this->getComponentId()}'].openConfirmDelete({
							object: {
								id: {$object->getId()},
								name: '{$object->getName()}',
								isFolder: " . ($isFolder? 'true' : 'false') . "
							 }
						})",
				);
			}

			if($isFolder)
			{
				$uri = $urlManager->encodeUrn($listingPage . '/' . $object->getOriginalName());
			}
			else
			{
				$uri = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->encodeUrn($detailPageFile);
			}
			$iconClass = \Bitrix\Disk\Ui\Icon::getIconClassByObject($object);
			$name = htmlspecialcharsbx($object->getName());

			$updateDateTime = $object->getUpdateTime();
			$columnName = "
				<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$object->getId()}\" class=\"bx-file-icon-container-small {$iconClass}\"></div></td>
						<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$object->getId()}\" href=\"{$uri}\" data-bx-dateModify=\"" . htmlspecialcharsbx($updateDateTime) . "\">{$name}</a></td>
				</tr></table>
			";

			$deletedTime = $object->getDeleteTime();
			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $object->getCreatedBy()));
			$deletedByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $object->getDeletedBy()));

			$columns = array(
				'CREATE_TIME' => formatDate('x', $object->getCreateTime()->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'UPDATE_TIME' => formatDate('x', $updateDateTime->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'DELETE_TIME' => formatDate('x', $deletedTime->toUserTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
				'NAME' => $columnName,
				'FORMATTED_SIZE' => $isFolder? '' : CFile::formatSize($object->getSize()),
				'CREATE_USER' => "
					<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($object->getCreateUser()->getFormattedName()) . "</a></div>
				",
				'DELETE_USER' => "
					<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$deletedByLink}\" id=\"\">" . htmlspecialcharsbx($object->getDeleteUser()->getFormattedName()) . "</a></div>
				",
			);

			$exportData['ICON_CLASS'] = $iconClass;
			$exportData['IS_SHARED'] = false;
			$exportData['IS_LINK'] = false;
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
				//for sortByColumn
				'DELETE_TIME' => $deletedTime->getTimestamp(),
			);
		}
		unset($object);

		Collection::sortByColumn($rows, array('DELETE_TIME' => SORT_DESC));

		$grid['HEADERS'] = array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_NAME'),
				'default' => true,
			),
			array(
				'id' => 'DELETE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_TIME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_CREATE_TIME'),
				'default' => false,
			),
			array(
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_UPDATE_TIME'),
				'default' => false,
			),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_CREATE_USER'),
				'default' => false,
			),
			array(
				'id' => 'DELETE_USER',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_DELETE_USER'),
				'default' => false,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_TRASHCAN_COLUMN_FORMATTED_SIZE'),
				'default' => true,
			),
		);

		$grid['ROWS'] = $rows;
		$grid['ROWS_COUNT'] = count($rows);
		$grid['FOOTER'] = array();

		return $grid;
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
				'NAME' => Ui\Text::cleanTrashCanSuffix($item['NAME']),
				'LINK' => rtrim(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_TRASHCAN_LIST'], array(
					'TRASH_PATH' => implode('/', (array_slice($parts, 0, $i + 1)))?: '',
				)), '/') . '/',
			);
		}
		unset($i, $part);

		return $crumbs;
	}
}