<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Storage;
use Bitrix\Disk\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use COption;

abstract class DiskComponent extends BaseComponent
{
	/** @var Storage */
	protected $storage;

	protected function checkRequiredModules()
	{
		if (!Loader::includeModule("disk"))
		{
			throw new SystemException('Install module "disk"');
		}

		return $this;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if(!empty($this->arParams['STORAGE']))
		{
			if(!($this->arParams['STORAGE'] instanceof Storage))
			{
				throw new ArgumentException('STORAGE must be instance of \Bitrix\Disk\Storage');
			}
		}
		else
		{
			if(empty($this->arParams['STORAGE_MODULE_ID']))
			{
				throw new ArgumentException('STORAGE_MODULE_ID required');
			}
			if(empty($this->arParams['STORAGE_ENTITY_TYPE']))
			{
				throw new ArgumentException('STORAGE_ENTITY_TYPE required');
			}
			if(!isset($this->arParams['STORAGE_ENTITY_ID']))
			{
				throw new ArgumentException('STORAGE_ENTITY_ID required');
			}
		}

		if(empty($this->arParams['PATH_TO_USER']))
		{
			$siteId = SITE_ID;
			$currentUser = User::buildFromArray($this->getUser()->getById($this->getUser()->getId())->fetch());
			$default = '/company/personal/user/#user_id#/';
			if($currentUser->isExtranetUser())
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$siteId = \CExtranet::getExtranetSiteID();
				$default = '/extranet/contacts/personal/user/#user_id#/';
			}

			$this->arParams['PATH_TO_USER'] = strtolower(COption::getOptionString('intranet', 'path_user', $default, $siteId));
		}


		return $this;
	}

	protected function initializeStorage()
	{
		if(isset($this->arParams['STORAGE']))
		{
			$this->storage = $this->arParams['STORAGE'];

			return $this;
		}
		else
		{
			$this->storage = Storage::load(array(
				'MODULE_ID' => $this->arParams['STORAGE_MODULE_ID'],
				'ENTITY_TYPE' => $this->arParams['STORAGE_ENTITY_TYPE'],
				'ENTITY_ID' => $this->arParams['STORAGE_ENTITY_ID'],
			));
		}
		return $this;
	}

	/**
	 * Common operations before run action.
	 */
	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->initializeStorage();

		$bodyClass = $this->getApplication()->getPageProperty("BodyClass");
		$bodyClass = $bodyClass . " page-one-column";
		$this
			->getApplication()
			->setPageProperty("BodyClass", $bodyClass)
		;

		return true;
	}
}