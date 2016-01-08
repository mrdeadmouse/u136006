<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk\Rest\Entity;
use Bitrix\Disk;

final class File extends BaseObject
{
	/**
	 * Returns field descriptions (type, possibility to usage in filter, in render).
	 * @return array
	 */
	protected function getFields()
	{
		$storage = new Entity\File;
		return $storage->getFields();
	}

	/**
	 * Returns file by id.
	 * @param int $id Id of file.
	 * @return Disk\File
	 * @throws RestException
	 */
	protected function getWorkObjectById($id)
	{
		return $this->getFileById($id);
	}

	/**
	 * Deletes file by id.
	 * @param int $id Id of file.
	 * @return bool
	 * @throws RestException
	 */
	protected function delete($id)
	{
		$file = $this->getFileById($id);
		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canDelete($securityContext))
		{
			throw new AccessException;
		}
		if(!$file->delete($this->userId))
		{
			$this->errorCollection->add($file->getErrors());
			return false;
		}

		return true;
	}

	/**
	 * Creates new version of file.
	 * @param        int   $id
	 * @param string|array $fileContent File content. General format in REST.
	 * @return Disk\Version|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function uploadVersion($id, $fileContent)
	{
		$file = $this->getFileById($id);
		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canUpdate($securityContext))
		{
			throw new AccessException;
		}
		$fileData = \CRestUtil::saveFile($fileContent);
		if(!$fileData)
		{
			throw new RestException('Could not save file.');
		}
		$newFile = $file->uploadVersion($fileData, $this->userId);
		if(!$newFile)
		{
			$this->errorCollection->add($file->getErrors());
			return null;
		}

		return $file;
	}
}