<?php


namespace Bitrix\Disk;


use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

final class Version extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var BaseObject */
	protected $object;
	/** @var int */
	protected $fileId;
	/** @var int */
	protected $size;
	/** @var array */
	protected $file;
	/** @var string */
	protected $name;
	/** @var string */
	protected $extenstion;
	/** @var string */
	protected $miscData;
	/** @var array */
	protected $unserializeData;
	/** @var DateTime */
	protected $objectCreateTime;
	/** @var int */
	protected $objectCreatedBy;
	/** @var DateTime */
	protected $objectUpdateTime;
	/** @var int  */
	protected $objectUpdatedBy;
	/** @var int  */
	protected $globalContentVersion;


	/** @var DateTime */
	protected $createTime;
	/** @var int */
	protected $createdBy;
	/** @var  User */
	protected $createUser;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return VersionTable::className();
	}

	/**
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * @return int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * @return User
	 */
	public function getCreateUser()
	{
		if(isset($this->createUser) && $this->createdBy == $this->createUser->getId())
		{
			return $this->createUser;
		}
		$this->createUser = User::getModelForReferenceField($this->createdBy, $this->createUser);

		return $this->createUser;
	}

	/**
	 * @return int
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getFile()
	{
		if(!$this->fileId)
		{
			return null;
		}

		if(isset($this->file) && $this->fileId == $this->file['ID'])
		{
			return $this->file;
		}
		/** @noinspection PhpUndefinedClassInspection */
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$this->file = \CFile::getByID($this->fileId)->fetch();

		if(!$this->file)
		{
			return array();
		}

		return $this->file;
	}

	/**
	 * @return int
	 */
	public function getGlobalContentVersion()
	{
		return $this->globalContentVersion;
	}

	/**
	 * @return DateTime
	 */
	public function getObjectCreateTime()
	{
		return $this->objectCreateTime;
	}

	/**
	 * @return int
	 */
	public function getObjectCreatedBy()
	{
		return $this->objectCreatedBy;
	}

	/**
	 * @return DateTime
	 */
	public function getObjectUpdateTime()
	{
		return $this->objectUpdateTime;
	}

	/**
	 * @return int
	 */
	public function getObjectUpdatedBy()
	{
		return $this->objectUpdatedBy;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getMiscData()
	{
		return $this->miscData;
	}

	/**
	 * @return array
	 */
	public function getUnserializeMiscData()
	{
		if(isset($this->unserializeData))
		{
			return $this->unserializeData;
		}

		if(is_string($this->miscData))
		{
			$this->unserializeData = @unserialize($this->miscData);
			if($this->unserializeData === false)
			{
				return array();
			}
		}

		return $this->unserializeData;
	}

	public function getMiscDataByKey($key)
	{
		if(!isset($this->unserializeData))
		{
			$this->getUnserializeMiscData();
		}

		return isset($this->unserializeData[$key])? $this->unserializeData[$key] : null;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getExtension()
	{
		if($this->extenstion === null)
		{
			$this->extenstion = getFileExtension($this->getName());
		}
		return $this->extenstion;
	}

	/**
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return File|null
	 */
	public function getObject()
	{
		if(!$this->objectId)
		{
			return null;
		}

		if(isset($this->object) && $this->objectId == $this->object->getId())
		{
			return $this->object;
		}
		$this->object = File::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * Join data from another version.
	 * @param $data
	 * @return bool
	 * @internal
	 */
	public function joinData(array $data)
	{
		return $this->update(array_intersect_key($data, array(
			'CREATE_TIME' => true,

			'FILE_ID' => true,
			'SIZE' => true,

			'GLOBAL_CONTENT_VERSION' => true,

			'OBJECT_CREATED_BY' => true,
			'OBJECT_UPDATED_BY' => true,

			'OBJECT_CREATE_TIME'=> true,
			'OBJECT_UPDATE_TIME'=> true,
		)));
	}

	public function delete()
	{
		$success = parent::deleteInternal();
		if(!$success)
		{
			return false;
		}
		\CFile::delete($this->fileId);

		return true;
	}

	/**
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'FILE_ID' => 'fileId',
			'SIZE' => 'size',
			'NAME' => 'name',
			'MISC_DATA' => 'miscData',

			'OBJECT_CREATE_TIME' => 'objectCreateTime',
			'OBJECT_CREATED_BY' => 'objectCreatedBy',
			'OBJECT_UPDATE_TIME' => 'objectUpdateTime',
			'OBJECT_UPDATED_BY' => 'objectUpdatedBy',
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',

			'CREATE_TIME' => 'createTime',
			'CREATED_BY' => 'createdBy',
			'CREATE_USER' => 'createUser',
		);
	}

	/**
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array(
			'CREATE_USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
			'OBJECT' => File::className(),
		);
	}
}