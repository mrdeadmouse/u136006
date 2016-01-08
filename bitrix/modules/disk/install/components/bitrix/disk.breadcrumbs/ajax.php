<?php
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskBreadcrumbsAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_FOLDER = 'DISK_BAC_22001';

	protected function listActions()
	{
		return array(
			'showSubFolders' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionShowSubFolders()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}
		$showOnlyDeleted = (bool)$this->request->getPost('showOnlyDeleted');
		$isRoot = (bool)$this->request->getPost('isRoot');

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_BREADCRUMBS_ERROR_COULD_NOT_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER)));
			$this->sendJsonErrorResponse();
		}
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();

		$subFolders = array();
		$filter = array(
			'TYPE' => ObjectTable::TYPE_FOLDER,
		);
		if($showOnlyDeleted)
		{
			$filter['!=DELETED_TYPE'] = ObjectTable::DELETED_TYPE_NONE;
		}

		if($showOnlyDeleted && $isRoot)
		{
			$filter['DELETED_TYPE'] = ObjectTable::DELETED_TYPE_ROOT;
			$children = $folder->getDescendants($securityContext, array(
				'filter' => $filter,
			));
		}
		else
		{
			$children = $folder->getChildren($securityContext, array('filter' => $filter));
		}

		foreach($children as $subFolder)
		{
			/** @var Folder $subFolder */
			$subFolders[] = array(
				'id' => $subFolder->getId(),
				'name' => $subFolder->getName(),
				'isLink' => $subFolder->isLink(),
			);
		}
		unset($subFolder);
		\Bitrix\Main\Type\Collection::sortByColumn($subFolders, 'name');


		$this->sendJsonSuccessResponse(array(
			'items' => $subFolders,
		));
	}
}
$controller = new DiskBreadcrumbsAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;