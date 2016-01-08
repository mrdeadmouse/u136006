<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\User;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class Storage extends Base
{
	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\Storage;
		return $storage->getFields();
	}

	/**
	 * Returns Storage by id.
	 * @param int $id Id of storage.
	 * @return Disk\Storage
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function get($id)
	{
		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canRead($securityContext))
		{
			throw new AccessException;
		}

		return $storage;
	}

	/**
	 * Returns storage for current application.
	 * @return Disk\Storage|null
	 * @throws RestException
	 */
	protected function getForApp()
	{
		$driver  = Disk\Driver::getInstance();
		$appData = \CBitrix24App::getList(array(), array('APP_ID' => $this->restServer->getAppId()))->fetch();
		if(!$appData || empty($appData['ID']) || empty($appData['CODE']))
		{
			throw new RestException('Could not find application by app_id.', RestException::ERROR_NOT_FOUND);
		}

		$storage = $driver->getStorageByRestApp($appData['ID']);
		if(!$storage)
		{
			$storage = $driver->addRestAppStorage(array(
				'ENTITY_ID' => $appData['ID'],
				'NAME' => $appData['CODE'],
			));
			if(!$storage)
			{
				$this->errorCollection->add($driver->getErrors());
				return null;
			}
		}

		return $storage;
	}

	/**
	 * Returns list of storages.
	 * @param array $filter Filter.
	 * @param array $order  Order.
	 * @return Disk\Storage[]|null
	 */
	protected function getList(array $filter = array(), array $order = array())
	{
		$securityContext = $this->getSecurityContextByUser($this->userId);

		$internalizer = new Disk\Rest\Internalizer(new Entity\Storage, $this);
		$parameters = array_merge(
			array(
				'with' => array('ROOT_OBJECT'),
				'filter' => array_merge(array(
					'=ROOT_OBJECT.PARENT_ID' => null,
					'=MODULE_ID' => Disk\Driver::INTERNAL_MODULE_ID,
					'=RIGHTS_CHECK' => true,
				), $internalizer->cleanFilter($filter)),
				'runtime' => array(
					new ExpressionField('RIGHTS_CHECK', 'CASE WHEN ' . $securityContext->getSqlExpressionForList('%1$s', '%2$s') . ' THEN 1 ELSE 0 END', array(
							'ROOT_OBJECT.ID',
							'ROOT_OBJECT.CREATED_BY'
						), array('data_type' => 'boolean',))
				),
				'order' => $order
			),
			Disk\Rest\RestManager::getNavData($this->start)
		);

		$parameters = Disk\Driver::getInstance()
			->getRightsManager()
			->addRightsCheck(
				$securityContext,
				$parameters,
				array( 'ROOT_OBJECT.ID', 'ROOT_OBJECT.CREATED_BY')
			)
		;

		$storages = Disk\Storage::getModelList($parameters);
		if($storages === null)
		{
			$this->errorCollection->addOne(new Error('Could not load list of storages.'));
			return null;
		}
		foreach($storages as $key => $storage)
		{
			if(
				!$storage->getProxyType() instanceof Disk\ProxyType\Common &&
				!$storage->getProxyType() instanceof Disk\ProxyType\Group &&
				!$storage->getProxyType() instanceof Disk\ProxyType\User
			)
			{
				unset($storages[$key]);
			}
		}
		unset($storage);

		return $storages;
	}

	private function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new Disk\Security\DiskSecurityContext($user);
		if(Loader::includeModule('socialnetwork'))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new Disk\Security\FakeSecurityContext($user);
			}
		}
		if(User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new Disk\Security\FakeSecurityContext($user);
		}
		return $diskSecurityContext;
	}

	/**
	 * Gets type of storages.
	 * @return array
	 */
	protected function getTypes()
	{
		return array('user', 'common', 'group');
	}

	/**
	 * Renames storage.
	 * @param int    $id      Id of storage.
	 * @param string $newName New name for storage.
	 * @return Disk\Storage|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function rename($id, $newName)
	{
		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canRename($securityContext))
		{
			throw new AccessException;
		}
		if(!$storage->getProxyType() instanceof Disk\ProxyType\RestApp)
		{
			throw new RestException('Access denied (invalid type of storage)');
		}
		if(!$storage->rename($newName))
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $storage;
	}

	/**
	 * Creates folder in root of storage.
	 * @param      int $id     Id of storage.
	 * @param array    $data   Data for creating new folder.
	 * @param array    $rights Specific rights on folder. If empty, then use parents rights.
	 * @return Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function addFolder($id, array $data, array $rights = array())
	{
		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canAdd($securityContext))
		{
			throw new AccessException;
		}
		$folder = $storage->addFolder(array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$folder)
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $folder;
	}

	/**
	 * Returns direct children of storage.
	 * @param    int $id     Id of storage.
	 * @param array  $filter Filter.
	 * @param array  $order  Order.
	 * @return Disk\BaseObject[]
	 * @throws RestException
	 */
	protected function getChildren($id, array $filter = array(), array $order = array())
	{
		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();

		$internalizer = new Disk\Rest\Internalizer(new Entity\BaseObject, $this);
		$parameters = array_merge(array(
			'filter' => $internalizer->cleanFilter($filter),
			'order' => $order
		), Disk\Rest\RestManager::getNavData($this->start));

		$children = $storage->getChildren($securityContext, $parameters);
		if($children === null)
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $children;
	}

	/**
	 * Creates new file in root of storage.
	 * @param       int    $id          Id of storage.
	 * @param string|array $fileContent File content. General format in REST.
	 * @param array        $data        Data for new file.
	 * @param array        $rights      Specific rights on file. If empty, then use parents rights.
	 * @return Disk\File|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function uploadFile($id, $fileContent, array $data, array $rights = array())
	{
		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$storage = $this->getStorageById($id);
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$storage->getRootObject()->canAdd($securityContext))
		{
			throw new AccessException;
		}
		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$file = $storage->uploadFile($fileData, array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$file)
		{
			$this->errorCollection->add($storage->getErrors());
			return null;
		}

		return $file;
	}

	/**
	 * @param $id
	 * @return Disk\Storage
	 * @throws RestException
	 */
	private function getStorageById($id)
	{
		$storage = Disk\Storage::getById($id);
		if(!$storage)
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $storage;
	}
}