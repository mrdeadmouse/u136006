<?php

namespace Bitrix\Disk\Bitrix24Disk\Legacy;

use Bitrix\Disk\Bitrix24Disk\TmpFile;

abstract class AbstractStorage
{
	/** @var array|string|null */
	protected $storageId = null;
	/** @var array|null */
	protected $storageExtra = null;

	/**
	 * @return string
	 */
	abstract public function getStorageClassName();

	/**
	 * @return array|null|string
	 */
	public function getStorageId()
	{
		return $this->storageId;
	}

	/**
	 * @param $storageId
	 * @return $this
	 */
	public function setStorageId($storageId)
	{
		$this->storageId = $storageId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStringStorageId()
	{
		//todo may in $this->setStorageId() use array_filter...
		//remove null values from storageId
		return implode('|', array_filter($this->getStorageId()));
	}

	/**
	 * @param array|null $storageExtra
	 * @return $this
	 */
	public function setStorageExtra($storageExtra)
	{
		$this->storageExtra = $storageExtra;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getStorageExtra()
	{
		return $this->storageExtra;
	}

	abstract public function parseStorageExtra(array $source);

	abstract public function parseElementExtra(array $source);

	/**
	 * @param array $element
	 * @return string
	 */
	abstract public function generateId(array $element);

	/**
	 * @param int $version
	 * @return array
	 */
	abstract public function getSnapshot($version = 0);

	/**
	 * @param       $id
	 * @param array $extra
	 * @param bool  $skipCheckId
	 * @return array|boolean
	 */
	abstract public function getFile($id, array $extra, $skipCheckId = false);

	/**
	 * @param       $id
	 * @param array $extra
	 * @param bool  $skipCheckId
	 * @return array|boolean
	 */
	abstract public function getDirectory($id, array $extra, $skipCheckId = false);

	/**
	 * @param $file
	 * @return boolean
	 */
	abstract public function sendFile($file);

	/**
	 * @param $name
	 * @param $parentDirectoryId
	 * @return array|boolean
	 */
	abstract public function addDirectory($name, $parentDirectoryId);

	/**
	 * @param $name
	 * @param $targetDirectoryId
	 * @param $newParentDirectoryId
	 * @internal param $parentDirectoryId
	 * @return array|bool
	 */
	abstract public function moveDirectory($name, $targetDirectoryId, $newParentDirectoryId);
	abstract public function renameDirectory($name, $targetDirectoryId, $parentDirectoryId);

	/**
	 * @param $name
	 * @param $targetElementId
	 * @param $newParentDirectoryId
	 * @internal param $parentDirectoryId
	 * @return array|bool
	 */
	abstract public function moveFile($name, $targetElementId, $newParentDirectoryId);
	abstract public function renameFile($name, $targetElementId, $parentDirectoryId);

	/**
	 * @param                $name
	 * @param                $targetDirectoryId
	 * @param TmpFile $tmpFile
	 * @return array|boolean
	 */
	abstract public function addFile($name, $targetDirectoryId, TmpFile $tmpFile);

	/**
	 * @param                $name
	 * @param                $targetElementId
	 * @param TmpFile $tmpFile
	 * @return array|boolean
	 */
	abstract public function updateFile($name, $targetElementId, TmpFile $tmpFile);
	abstract public function deleteFile($file);
	abstract public function deleteDirectory($directory);
	abstract public function getVersionDelete($element);
	abstract public function isUnique($name, $targetDirectoryId, &$opponentId = null);
	abstract public function isCorrectName($name, &$msg);

	abstract public function getPublicLink(array $file);

	/**
	 * @param $a
	 * @param $b
	 * @return int (1, -1, 0)
	 */
	public function compareVersion($a , $b)
	{
		$a = str_pad($a, strlen($b), '0', STR_PAD_LEFT);
		$b = str_pad($b, strlen($a), '0', STR_PAD_LEFT);

		return strcmp($a, $b);
	}
}