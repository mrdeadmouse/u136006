<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Main\Loader;

class SocialnetworkHandlers
{
	private static $lastGroupIdAddedOnHit;
	private static $lastGroupOwnerIdAddedOnHit;

	public static function onAfterUserAdd($fields)
	{
		if(!Loader::includeModule('socialnetwork') || empty($fields['ID']))
		{
			return;
		}
		Driver::getInstance()->addUserStorage($fields['ID']);
	}

	public static function onAfterUserUpdate($fields)
	{
		if(!Loader::includeModule('socialnetwork') || empty($fields['ID']))
		{
			return;
		}

		if(!empty($fields['NAME']) || !empty($fields['LAST_NAME']) || !empty($fields['SECOND_NAME']))
		{
			$user = User::loadById($fields['ID']);
			if(!$user || $user->isEmptyName())
			{
				return;
			}
			$userStorage = Driver::getInstance()->getStorageByUserId($user->getId());
			if(!$userStorage)
			{
				return;
			}
			$userStorage->rename($user->getFormattedName());
		}
	}

	public static function onUserDelete($userId)
	{
		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			return true;
		}

		try
		{
			$storage->delete(self::getActivityUserId());
		}
		catch(\Exception $e)
		{
			global $APPLICATION;
			if(is_object($APPLICATION))
			{
				$APPLICATION->throwException($e->getMessage());
			}
			return false;
		}

		return true;
	}

	public static function onSocNetGroupAdd($id, $fields)
	{
		self::$lastGroupIdAddedOnHit = $id;
		self::$lastGroupOwnerIdAddedOnHit = !empty($fields['OWNER_ID'])? $fields['OWNER_ID'] : false;
	}

	public static function onSocNetGroupDelete($groupId)
	{
		$storage = Driver::getInstance()->getStorageByGroupId($groupId);
		if(!$storage)
		{
			return;
		}
		$storage->delete(self::getActivityUserId());
	}

	public static function onSocNetUserToGroupDelete($id, $fields)
	{
		if(
			isset($fields['ROLE']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			$userId = $fields['USER_ID'];
			$groupId = $fields['GROUP_ID'];

			if(empty($userId) || empty($groupId))
			{
				return;
			}

			$storage = Driver::getInstance()->getStorageByGroupId($groupId);
			if(!$storage)
			{
				return;
			}
			/** @var Sharing $sharing */
			$sharing = Sharing::load(array(
				'=TO_ENTITY' => Sharing::CODE_USER . $userId,
				'REAL_OBJECT_ID' => $storage->getRootObjectId(),
				'REAL_STORAGE_ID' => $storage->getId(),
			));
			if(!$sharing)
			{
				return;
			}
			$sharing->delete(self::getActivityUserId());
		}
	}

	public static function onSocNetUserToGroupUpdate($id, $fields)
	{
		if(
			isset($fields['ROLE']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)
		)
		{
			if(!(isset($fields['USER_ID'])))
			{
				$query = \CSocNetUserToGroup::getList(array(), array('ID' => $id), false, false, array('USER_ID', 'GROUP_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$userId = $row['USER_ID'];
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(!empty($userId) && !empty($groupId) && \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$rootObject = $storage->getRootObject();
				if(!$rootObject->canRead($storage->getSecurityContext($userId)))
				{
					return;
				}

				$errorCollection = new ErrorCollection();
				Sharing::connectToUserStorage($userId, array(
					'CREATED_BY' => $userId,
					'REAL_OBJECT' => $storage->getRootObject(),
				), $errorCollection);
			}
		}
	}

	public static function onSocNetUserToGroupAdd($id, $fields)
	{
		if(
			isset($fields['ROLE']) && isset($fields['USER_ID']) &&
			(
				$fields['ROLE'] == SONET_ROLES_USER ||
				$fields['ROLE'] == SONET_ROLES_MODERATOR ||
				$fields['ROLE'] == SONET_ROLES_OWNER
			)

		)
		{
			if(!(isset($fields['GROUP_ID'])))
			{
				$query = \CSocNetUserToGroup::getList(array(), array('ID' => $id), false, false, array('GROUP_ID', 'INITIATED_BY_USER_ID'));
				if($query)
				{
					$row = $query->fetch();
					if($row)
					{
						$groupId = $row['GROUP_ID'];
					}
				}
			}
			else
			{
				$groupId = $fields['GROUP_ID'];
			}

			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			if(!empty($groupId) && \CSocNetFeatures::isActiveFeature(SONET_ENTITY_GROUP, $groupId, 'files'))
			{
				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$rootObject = $storage->getRootObject();
				if(!$rootObject->canRead($storage->getSecurityContext($fields['USER_ID'])))
				{
					return;
				}

				$errorCollection = new ErrorCollection();
				Sharing::connectToUserStorage($fields['USER_ID'], array(
					'CREATED_BY' => empty($fields['INITIATED_BY_USER_ID'])? $fields['USER_ID'] : $fields['INITIATED_BY_USER_ID'],
					'REAL_OBJECT' => $storage->getRootObject(),
				), $errorCollection);
			}
		}
	}

	public static function onSocNetFeaturesAdd($id, $fields)
	{
		static $addGroupFilesFeatures = false;

		if(!$addGroupFilesFeatures && isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'Y')
		{
			if($fields
				&& isset($fields['FEATURE'])
				&& $fields['FEATURE'] == 'files'
				&& $fields['ENTITY_TYPE'] == 'G'
				&& $fields['ENTITY_ID']
			)
			{
				$addGroupFilesFeatures = true;
				$groupId = $fields['ENTITY_ID'];

				$storage = Driver::getInstance()->addGroupStorage($groupId);
				if($storage && self::$lastGroupIdAddedOnHit == $groupId && self::$lastGroupOwnerIdAddedOnHit)
				{
					$rootObject = $storage->getRootObject();
					if(!$rootObject->canRead($storage->getSecurityContext(self::$lastGroupOwnerIdAddedOnHit)))
					{
						return;
					}

					$errorCollection = new ErrorCollection();
					Sharing::connectToUserStorage(self::$lastGroupOwnerIdAddedOnHit, array(
						'CREATED_BY' => self::$lastGroupOwnerIdAddedOnHit,
						'REAL_OBJECT' => $storage->getRootObject(),
					), $errorCollection);
				}
			}
		}
	}

	public static function onSocNetFeaturesUpdate($id, $fields)
	{
		static $updateGroupFilesFeatures = false;

		if(!$updateGroupFilesFeatures && isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'N')
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$features = \CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$updateGroupFilesFeatures = true;
				$groupId = $features['ENTITY_ID'];

				if(empty($groupId))
				{
					return;
				}

				$storage = Driver::getInstance()->getStorageByGroupId($groupId);
				if(!$storage)
				{
					return;
				}

				$userId = self::getActivityUserId();
				foreach(Sharing::getModelList(array('filter' => array(
						'REAL_OBJECT_ID' => (int)$storage->getRootObjectId(),
						'REAL_STORAGE_ID' => (int)$storage->getId(),
				))) as $sharing)
				{
					$sharing->delete($userId);
				}
				unset($sharing);
			}
		}
		elseif(isset($fields['ACTIVE']) && $fields['ACTIVE'] == 'Y')
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$features = \CSocNetFeatures::getById($id);
			if($features
				&& isset($features['FEATURE'])
				&& $features['FEATURE'] == 'files'
				&& $features['ENTITY_TYPE'] == 'G'
				&& $features['ENTITY_ID']
			)
			{
				$groupId = $features['ENTITY_ID'];
				if(!empty($groupId))
				{
					Driver::getInstance()->addGroupStorage($groupId);
				}
			}
		}
	}

	public static function onAfterFetchDiskUfEntity(array $entities)
	{
		foreach($entities as $name => $ids)
		{
			if($name === 'BLOG_POST')
			{
				if(is_array($ids))
				{
					Driver::getInstance()->getUserFieldManager()->loadBatchAttachedObjectInBlogPost($ids);
				}
			}
		}
		unset($name);
	}

	private static function getActivityUserId()
	{
		global $USER;
		if($USER && is_object($USER))
		{
			$userId = $USER->getId();
			if(is_numeric($userId) && ((int)$userId > 0))
			{
				return $userId;
			}
		}

		return SystemUser::SYSTEM_USER_ID;
	}
}