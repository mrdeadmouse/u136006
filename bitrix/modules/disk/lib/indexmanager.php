<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use CFile;
use CSearch;

final class IndexManager
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	/**
	 * Constructor IndexManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Runs index by file.
	 * @param File $file Target file.
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function indexFile(File $file)
	{
		if(!Loader::includeModule('search'))
		{
			return;
		}
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if(!Configuration::allowIndexFiles())
		{
			return;
		}
		$detailUrl = Driver::getInstance()->getUrlManager()->getPathFileDetail($file);
		$storage = $file->getStorage();
		$searchData = array(
			'LAST_MODIFIED' => $file->getUpdateTime()?: $file->getCreateTime(),
			'TITLE' => $file->getName(),
			'PARAM1' => $file->getStorageId(),
			'PARAM2' => $file->getParentId(),
			'SITE_ID' => $storage->getSiteId()?: SITE_ID,
			'URL' => $detailUrl,
			'PERMISSIONS' => $this->getSimpleRights($file),
			//CSearch::killTags
			'BODY' => $this->getFileContent($file),
		);
		if($storage->getProxyType() instanceof Group)
		{
			$searchData['PARAMS'] = array(
				'socnet_group' => $storage->getEntityId(),
				'entity' => 'socnet_group',
			);
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::index(Driver::INTERNAL_MODULE_ID, $this->getItemId($file), $searchData, true);
	}

	/**
	 * Changes index after rename.
	 * @param File $file Target file.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function changeName(File $file)
	{
		if(!Loader::includeModule('search'))
		{
			return;
		}
		//here we place configuration by Module (Options). Example, we can deactivate index for big files in Disk.
		if(!Configuration::allowIndexFiles())
		{
			return;
		}
		$detailUrl = Driver::getInstance()->getUrlManager()->getPathFileDetail($file);
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::changeIndex(Driver::INTERNAL_MODULE_ID, array(
			'TITLE' => $file->getName(),
			'URL' => $detailUrl,
		), $this->getItemId($file));
	}

	/**
	 * Delete information from Search by concrete file.
	 * @param File $file Target file.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function dropIndex(File $file)
	{
		if(!Loader::includeModule('search'))
		{
			return;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CSearch::deleteIndex(Driver::INTERNAL_MODULE_ID, $this->getItemId($file));
	}

	/**
	 * Recalculate rights in Search if it needs.
	 * @param BaseObject $object Target object (can be folder or file).
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function recalculateRights(BaseObject $object)
	{
		if(!Loader::includeModule('search'))
		{
			return;
		}
		if($object instanceof File)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			CSearch::changePermission(Driver::INTERNAL_MODULE_ID, $this->getSimpleRights($object), $this->getItemId($object));
		}
		elseif($object instanceof Folder)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			CSearch::changePermission(Driver::INTERNAL_MODULE_ID, $this->getSimpleRights($object), false, false, $object->getParentId());
		}
	}

	/**
	 * Event listener which return url for resource by fields.
	 * @param array $fields Fields from search module.
	 * @return string
	 */
	public static function onSearchGetUrl($fields)
	{
		if(!is_array($fields))
		{
			return '';
		}
		if($fields["MODULE_ID"] !== "disk" || substr($fields["URL"], 0, 1) !== "=")
		{
			return $fields["URL"];
		}

		parse_str(ltrim($fields["URL"], "="), $data);
		if(empty($data['ID']))
		{
			return '';
		}
		$file = File::loadById($data['ID']);
		if(!$file)
		{
			return '';
		}
		$pathFileDetail = Driver::getInstance()->getUrlManager()->getPathFileDetail($file);
		\CSearch::update($fields['ID'], array('URL' => $pathFileDetail));

		return $pathFileDetail;
	}


	/**
	 * Search re-index handler.
	 * @param array  $nextStepData Array with data about step.
	 * @param null   $searchObject Search object.
	 * @param string $method Method.
	 * @return array|bool
	 */
	public static function onSearchReindex($nextStepData = array(), $searchObject = null, $method = "")
	{
		$result = array();
		$filter = array(
			'TYPE' => ObjectTable::TYPE_FILE,
		);

		if(isset($nextStepData['MODULE']) && ($nextStepData['MODULE'] === 'disk') && !empty($nextStepData['ID']))
		{
			$filter['>ID'] = self::getObjectIdFromItemId($nextStepData['ID']);
		}
		else
		{
			$filter['>ID'] = 0;
		}

		static $self = null;
		if($self === null)
		{
			$self = Driver::getInstance()->getIndexManager();
		}

		$query = File::getList(array('filter' => $filter, 'order' => array('ID' => 'ASC')));
		while($fileData = $query->fetch())
		{
			/** @var File $file */
			$file = File::buildFromArray($fileData);

			$detailUrl = Driver::getInstance()->getUrlManager()->getPathFileDetail($file);
			$searchData = array(
				'ID' => self::getItemId($file),
				'LAST_MODIFIED' => $file->getUpdateTime() ?: $file->getCreateTime(),
				'TITLE' => $file->getName(),
				'PARAM1' => $file->getStorageId(),
				'PARAM2' => $file->getParentId(),
				'SITE_ID' => $file->getStorage()->getSiteId()?: SITE_ID,
				'URL' => $detailUrl,
				'PERMISSIONS' => $self->getSimpleRights($file),
				//CSearch::killTags
				'BODY' => $self->getFileContent($file),
			);

			if($searchObject)
			{
				$indexResult = call_user_func(array($searchObject, $method), $searchData);
				if(!$indexResult)
				{
					return $searchData["ID"];
				}
			}
			else
			{
				$result[] = $searchData;
			}
		}

		if($searchObject)
		{
			return false;
		}

		return $result;
	}

	private function getFileContent(File $file)
	{
		static $maxFileSize = null;
		if(!isset($maxFileSize))
		{
			$maxFileSize = Option::get("search", "max_file_size", 0) * 1024;
		}

		$searchData = '';
		$searchData .= strip_tags($file->getName()) . "\r\n";


		if($maxFileSize > 0 && $file->getSize() > $maxFileSize)
		{
			return '';
		}

		$searchDataFile = array();
		$fileArray = CFile::makeFileArray($file->getFileId());
		if($fileArray && $fileArray['tmp_name'])
		{
			$fileAbsPath = \CBXVirtualIo::getInstance()->getLogicalName($fileArray['tmp_name']);
			foreach(GetModuleEvents('search', 'OnSearchGetFileContent', true) as $event)
			{
				if($searchDataFile = executeModuleEventEx($event, array($fileAbsPath, getFileExtension($fileArray['name']))))
				{
					break;
				}
			}

			return is_array($searchDataFile)? $searchData  . "\r\n" . $searchDataFile['CONTENT'] : $searchData;
		}

		return $searchData;
	}

	private function getSimpleRights(BaseObject $object)
	{
		$query = SimpleRightTable::getList(array(
			'select' => array('ACCESS_CODE'),
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
			)
		));
		$permissions = array();
		while($row = $query->fetch())
		{
			$permissions[] = $row['ACCESS_CODE'];
		}

		return $permissions;
	}

	/**
	 * Getting id for module search.
	 * @param BaseObject $object
	 * @return string
	 */
	private static function getItemId(BaseObject $object)
	{
		if($object instanceof File)
		{
			return 'FILE_' . $object->getId();
		}
		return 'FOLDER_' . $object->getId();
	}

	private static function getObjectIdFromItemId($itemId)
	{
		if(substr($itemId, 0, 5) === 'FILE_')
		{
			return substr($itemId, 5);
		}
		return substr($itemId, 7);
	}
} 