<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;

abstract class Connector
{
	protected $entityId;
	/** @var \Bitrix\Disk\AttachedObject  */
	protected $attachedObject;

	protected static $pathToUser  = '/company/personal/user/#user_id#/';
	protected static $pathToGroup = '/workgroups/group/#group_id#/';

	public function __construct($entityId, AttachedObject $attachedObject)
	{
		$this->entityId = $entityId;
		$this->attachedObject = $attachedObject;
	}

	public static function className()
	{
		return get_called_class();
	}

	public function getDataToShow()
	{
		return array();
	}

	/**
	 * $data contains 'text', 'version'
	 * @param       $authorId
	 * @param array $data
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead($userId)
	{
		return false;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canUpdate($userId)
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function canConfidenceReadInOperableEntity()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function canConfidenceUpdateInOperableEntity()
	{
		return false;
	}

	public function getPathToUser()
	{
		return $this::$pathToUser;
	}

	public function getPathToGroup()
	{
		return $this::$pathToGroup;
	}

	public static function setPathToUser($path)
	{
		if(!empty($path))
		{
			static::$pathToUser = $path;
		}
	}

	public static function setPathToGroup($path)
	{
		if(!empty($path))
		{
			static::$pathToGroup = $path;
		}
	}

	/**
	 * @return Application|\Bitrix\Main\HttpApplication|\CAllMain|\CMain
	 */
	protected function getApplication()
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	/**
	 * @return array|bool|\CAllUser|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}
}