<?php
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\User;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Storage;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Type\Collection;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskAggregatorAjaxController extends \Bitrix\Disk\Internals\Controller
{
	protected function listActions()
	{
		return array(
			'getListStorage' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionGetListStorage()
	{
		$this->checkRequiredPostParams(array('proxyType', ));
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$proxyTypePost = $this->request->getPost('proxyType');
		$diskSecurityContext = $this->getSecurityContextByUser($this->getUser());

		$siteId = null;
		$siteDir = null;
		if($this->request->getPost('siteId'))
		{
			$siteId = $this->request->getPost('siteId');
		}
		if($this->request->getPost('siteDir'))
		{
			$siteDir = rtrim($this->request->getPost('siteDir'), '/');
		}

		$result = array();
		$filterReadableList = array();
		$checkSiteId = false;

		if($proxyTypePost == 'user')
		{
			$result['TITLE'] = Loc::getMessage('DISK_AGGREGATOR_USER_TITLE');
			$filterReadableList = array('STORAGE.ENTITY_TYPE' => ProxyType\User::className());
		}
		elseif($proxyTypePost == 'group')
		{
			$checkSiteId = true;
			$result['TITLE'] = Loc::getMessage('DISK_AGGREGATOR_GROUP_TITLE');
			$filterReadableList = array('STORAGE.ENTITY_TYPE' => ProxyType\Group::className());
		}

		foreach(Storage::getReadableList($diskSecurityContext, array('filter' => $filterReadableList)) as $storage)
		{
			if($checkSiteId)
			{
				$groupObject = CSocNetGroup::getList(
					array(),
					array('ID' => $storage->getEntityId()),
					false,
					false,
					array('SITE_ID')
				);
				$group = $groupObject->fetch();
				if(!empty($group) && $group['SITE_ID'] != $siteId)
				{
					continue;
				}
			}
			$proxyType = $storage->getProxyType();
			$result['DATA'][] = array(
				"TITLE" => $proxyType->getEntityTitle(),
				"URL" => $siteDir.$proxyType->getBaseUrlFolderList(),
				"ICON" => $proxyType->getEntityImageSrc(64,64),
			);
		}
		if(!empty($result['DATA']))
		{
			Collection::sortByColumn($result['DATA'], array('TITLE' => SORT_ASC));
			$this->sendJsonSuccessResponse(array(
				'listStorage' => $result['DATA'],
				'title' => $result['TITLE'],
			));
		}
		else
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_AGGREGATOR_ERROR_COULD_NOT_FIND_DATA'))));
			$this->sendJsonErrorResponse();
		}
	}

	protected function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new DiskSecurityContext($user);
		if(Loader::includeModule('socialnetwork'))
		{
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
}
$controller = new DiskAggregatorAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;