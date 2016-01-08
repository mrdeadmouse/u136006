<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Version;
use Bitrix\Main\IO;

class FileData implements IErrorable
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $mimeType;
	/** @var string */
	protected $src;
	/** @var int */
	protected $size;
	/** @var bool */
	protected $needConvert = true;
	/** @var string */
	protected $linkInService;
	/** @var File|null */
	protected $file;
	/** @var Version|null */
	protected $version;

	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getLinkInService()
	{
		return $this->linkInService;
	}

	/**
	 * @param string $linkInService
	 */
	public function setLinkInService($linkInService)
	{
		$this->linkInService = $linkInService;
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * @param string $mimeType
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return boolean
	 */
	public function isNeedConvert()
	{
		return $this->needConvert;
	}

	/**
	 * @param boolean $needConvert
	 */
	public function setNeedConvert($needConvert)
	{
		$this->needConvert = $needConvert;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @param int $size
	 */
	public function setSize($size)
	{
		$this->size = $size;
	}

	/**
	 * @param bool $getFromFileIfPossible
	 * @return string|null
	 */
	public function getSrc($getFromFileIfPossible = true)
	{
		if(!$this->src && $getFromFileIfPossible && $this->file instanceof File)
		{
			$fileArray = \CFile::makeFileArray($this->file->getFileId());
			if(!is_array($fileArray))
			{
				return null;
			}
			$this->src = $fileArray['tmp_name'];
		}
		return $this->src;
	}

	/**
	 * @param string $src
	 */
	public function setSrc($src)
	{
		$this->src = $src;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return File|null
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param File|null $file
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * @return Version|null
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param Version|null $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function toArray()
	{
		return array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'mimeType' => $this->getMimeType(),
			'src' => $this->getSrc(true),
			'size' => $this->getSize(),
			'needConvert' => $this->isNeedConvert(),
			'linkInService' => $this->getLinkInService(),
			'file' => $this->getFile(),
		);
	}
}
