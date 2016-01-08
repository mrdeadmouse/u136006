<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Ui\Avatar;
use Bitrix\Disk\User as UserModel;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Common extends Base
{
	protected $unserializedMiscData;

	public function __construct($entityId, Storage $storage, $entityMiscData = null)
	{
		parent::__construct($entityId, $storage, $entityMiscData);
		if(!empty($this->entityMiscData) && is_string($this->entityMiscData))
		{
			$this->unserializedMiscData = unserialize($this->entityMiscData);
		}
	}

	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return true;
	}

	/**
	 * Gets security context (access provider) for user.
	 * Attention! File/Folder can use anywhere and SecurityContext have to check rights anywhere (any module).
	 * @param mixed $user User which use for check rights.
	 * @return SecurityContext
	 */
	public function getSecurityContextByUser($user)
	{
		if($this->isCurrentUser($user))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(Loader::includeModule('socialnetwork') && \CSocnetUser::isCurrentUserModuleAdmin())
			{
				return new FakeSecurityContext($user);
			}
			if(UserModel::isCurrentUserAdmin())
			{
				return new FakeSecurityContext($user);
			}
		}

		return new DiskSecurityContext($user);
	}

	/**
	 * Gets url which use for building url to listing folders, trashcan, etc.
	 * @return string
	 */
	public function getStorageBaseUrl()
	{
		if(!empty($this->unserializedMiscData['BASE_URL']))
		{
			return '/' . ltrim(\CComponentEngine::makePathFromTemplate($this->unserializedMiscData['BASE_URL']), '/');
		}
		return '/common/' . $this->entityId . '/files/';
	}

	/**
	 * Get image (avatar) of entity.
	 * Can be shown with entityTitle in different lists.
	 * @param int $width Image width.
	 * @param int $height Image height.
	 * @return string
	 */
	public function getEntityImageSrc($width, $height)
	{
		return Avatar::getDefaultGroup();
	}

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		$entityId = $this->storage->getEntityId();
		if($entityId == 'shared_files_s1' || $entityId == 'shared_files' || $entityId == 'shared')
		{
			return Loc::getMessage('DISK_PROXY_TYPE_COMMON_TITLE_S1');
		}
		return parent::getTitle();
	}
}