<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Ui\Avatar;
use Bitrix\Main\Loader;
use Bitrix\Disk\User as UserModel;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RestApp extends Base
{
	/**
	 * Potential opportunity to attach object to external entity
	 * @return bool
	 */
	public function canAttachToExternalEntity()
	{
		return false;
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
		return '/';
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		return '/';
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
}