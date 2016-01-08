<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class Folder extends BaseObject
{
	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\Folder;
		return $storage->getFields();
	}

	/**
	 * Returns folder by id.
	 * @param int $id Id of folder.
	 * @return Disk\Folder
	 */
	protected function getWorkObjectById($id)
	{
		return $this->getFolderById($id);
	}

	/**
	 * Returns direct children of storage.
	 * @param      int $id     Id of folder.
	 * @param array    $filter Filter.
	 * @param array    $order  Order.
	 * @return Disk\BaseObject[]
	 * @throws RestException
	 */
	protected function getChildren($id, array $filter = array(), array $order = array())
	{
		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();

		$internalizer = new Disk\Rest\Internalizer(new Entity\BaseObject, $this);
		$parameters = array_merge(array(
			'filter' => $internalizer->cleanFilter($filter),
			'order' => $order
		), Disk\Rest\RestManager::getNavData($this->start));

		$children = $folder->getChildren($securityContext, $parameters);
		if($children === null)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $children;
	}

	/**
	 * Creates sub-folder in folder.
	 * @param      int $id     Id of storage.
	 * @param array    $data   Data for creating new folder.
	 * @param array    $rights Specific rights on folder. If empty, then use parents rights.
	 * @return Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function addSubFolder($id, array $data, array $rights = array())
	{
		if(!$this->checkRequiredInputParams($data, array('NAME')))
		{
			return null;
		}

		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		$subFolder = $folder->addSubFolder(array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$subFolder)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $subFolder;
	}

	/**
	 * Deletes folder and sub-items.
	 * @param int $id Id of folder.
	 * @return bool
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function deleteTree($id)
	{
		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canDelete($securityContext))
		{
			throw new AccessException;
		}
		if(!$folder->getParentId())
		{
			throw new RestException('Could not delete root folder.');
		}
		if(!$folder->deleteTree($this->userId))
		{
			$this->errorCollection->add($folder->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Creates new file in folder.
	 * @param       int    $id          Id of folder.
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

		$folder = $this->getFolderById($id);
		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$file = $folder->uploadFile($fileData, array(
			'NAME' => $data['NAME'],
			'CREATED_BY' => $this->userId
		), $rights);
		if(!$file)
		{
			$this->errorCollection->add($folder->getErrors());
			return null;
		}

		return $file;
	}
}