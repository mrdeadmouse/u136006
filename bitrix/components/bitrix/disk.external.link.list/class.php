<?php

use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\User;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskExternalLinkListComponent extends DiskComponent
{
	/** @var \Bitrix\Disk\Folder */
	protected $folder;
	/** @var  array */
	protected $breadcrumbs;

	private function processGridActions($gridId)
	{
		$postAction = 'action_button_'.$gridId;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]) && check_bitrix_sessid())
		{
			if($_POST[$postAction] == 'delete')
			{
				if(empty($_POST['ID']))
				{
					return;
				}
				foreach($_POST['ID'] as $targetId)
				{
					/** @var ExternalLink $externalLink */
					$externalLink = ExternalLink::loadById($targetId, array('FILE.STORAGE'));
					if(!$externalLink)
					{
						continue;
					}
					//todo perf we can use getModelList and filter by SimpleRights with ID in (...). Also at once we make so quickly
					if(!$externalLink->getFile()->canRead($externalLink->getFile()->getStorage()->getCurrentUserSecurityContext()))
					{
						continue;
					}
					$externalLink->delete();
				}
			}
		}
	}

	protected function processActionDefault()
	{
		$gridId = 'external_link_list';

		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$this->processGridActions($gridId);

		$proxyType = $this->storage->getProxyType();
		$this->arResult = array(
			'GRID' => $this->getGridData($gridId),
			'STORAGE' => array(
				'NAME' => $proxyType->getTitleForCurrentUser(),
				'LINK' => $proxyType->getBaseUrlFolderList(),
				'ID' => $this->storage->getRootObjectId(),
			),
		);

		$this->includeComponentTemplate();
	}

	private function getGridData($gridId)
	{
		$grid = array(
			'ID' => $gridId,
		);

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$parameters = array(
			'with' => array('FILE', 'CREATE_USER'),
			'filter' => array(
				'IS_EXPIRED' => false,
				'OBJECT.STORAGE_ID' => $this->storage->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
			),
		);
		$parameters = Driver::getInstance()->getRightsManager()->addRightsCheck($securityContext, $parameters, array('OBJECT_ID', 'OBJECT.CREATED_BY'));
		$items = ExternalLink::getModelList($parameters);

		Collection::sortByColumn($items, array(
			'CREATE_TIME' => array(SORT_NUMERIC, SORT_ASC),
		));

		$urlManager = Driver::getInstance()->getUrlManager();
		$rows = array();
		foreach ($items as $externalLink)
		{
			/** @var ExternalLink $externalLink */
			$exportData = $externalLink->toArray();

			$nameSpecialChars = htmlspecialcharsbx($externalLink->getFile()->getName());
			$createDateText = htmlspecialcharsbx((string)$externalLink->getCreateTime());
			$columnName = "
				<table class=\"bx-disk-object-name\"><tr>
						<td style=\"width: 45px;\"><div data-object-id=\"{$externalLink->getId()}\" class=\"draggable bx-file-icon-container-small bx-disk-file-icon\"></div></td>
						<td><a class=\"bx-disk-folder-title\" id=\"disk_obj_{$externalLink->getId()}\" href=\"\" data-bx-dateModify=\"{$createDateText}\">{$nameSpecialChars}</a></td>
				</tr></table>
			";

			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $externalLink->getCreatedBy()));
			$rows[] = array(
				'data' => $exportData,
				'columns' => array(
					'CREATE_TIME' => formatDate('x', $externalLink->getCreateTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
					'UPDATE_TIME' => formatDate('x', $externalLink->getCreateTime()->getTimestamp(), (time() + CTimeZone::getOffset())),
					'NAME' => $columnName,
					'FORMATTED_SIZE' => CFile::formatSize($externalLink->getFile()->getSize()),
					'CREATE_USER' => "
						<div class=\"bx-disk-user-link\"><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($externalLink->getCreateUser()->getFormattedName()) . "</a></div>
					",
				),
				'actions' => array(
					array(
						"PSEUDO_NAME" => "download",
						"DEFAULT" => true,
						"ICONCLASS" => "download",
						"TEXT" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DOWNLOAD'),
						"ONCLICK" => "jsUtils.Redirect(arguments, '" . $urlManager->getUrlForDownloadFile($externalLink->getFile()) . "')",
					),
					array(
						"PSEUDO_NAME" => "disable_external_link",
						"ICONCLASS" => "disable_external_link",
						"TEXT" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DISABLE_EXTERNAL_LINK'),
						"SHORT_TEXT" => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_ACT_DISABLE_EXTERNAL_LINK_SHORT'),
						"ONCLICK" => "BX.Disk['ExternalLinkListClass_{$this->getComponentId()}'].disableExternalLink({$externalLink->getId()}, {$externalLink->getObjectId()})",
					),
				),
			);
		}
		unset($externalLink);

		$grid['MODE'] = 'list';
		$grid['HEADERS'] = array(
			array(
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'show_checkbox' => true,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_NAME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_CREATE_TIME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_CREATE_USER'),
				'default' => false,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_COLUMN_FORMATTED_SIZE'),
				'default' => true,
			),
		);
		$grid['ROWS'] = $rows;
		$grid['ROWS_COUNT'] = count($rows);
		$grid['FOOTER'] = array();

		return $grid;
	}
}