<?php

namespace Bitrix\Disk\ProxyType;

use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\User as UserModel;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class User extends Base
{
	/** @var \Bitrix\Disk\User */
	private $user;

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
		$userPage = \COption::getOptionString("socialnetwork", "user_page", false, SITE_ID);
		if(!$userPage)
		{
			$userPage = SITE_DIR . 'company/personal/';
		}
		return $userPage . 'user/' .  $this->entityId . '/disk/';
	}

	/**
	 * Get url to view entity of storage (ex. user profile, group profile, etc)
	 * By default: folder list
	 * @return string
	 */
	public function getEntityUrl()
	{
		$userPage = \COption::getOptionString("socialnetwork", "user_page", false, SITE_ID);
		if(!$userPage)
		{
			$userPage = SITE_DIR . 'company/personal/';
		}
		return $userPage . 'user/' .  $this->entityId . '/';
	}

	/**
	 * Get name of entity (ex. user last name + first name, group name, etc)
	 * By default: get title
	 * @return string
	 */
	public function getEntityTitle()
	{
		$user = $this->getUser();
		return isset($user)? $user->getFormattedName() : parent::getEntityTitle();
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
		return $this->getUser()->getAvatarSrc($width, $height);
	}

	/**
	 * Return name of storage.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('DISK_PROXY_TYPE_USER_TITLE');
	}

	/**
	 * Return name of storage.
	 * May be concrete by current user context.
	 * Should not use in notification, email to another person.
	 * @return string
	 */
	public function getTitleForCurrentUser()
	{
		global $USER;
		if(is_object($USER) && $USER->getId() == $this->entityId)
		{
			return Loc::getMessage('DISK_PROXY_TYPE_USER_TITLE_CURRENT_USER');
		}

		return parent::getTitle();
	}

	/**
	 * @return \Bitrix\Disk\User|null
	 */
	private function getUser()
	{
		if($this->user !== null)
		{
			return $this->user;
		}
		$this->user = UserModel::loadById($this->entityId);
		if(!$this->user)
		{
			$this->user = SystemUser::create();
		}

		return $this->user;
	}
}