<?
IncludeModuleLangFile(__FILE__);

use \Bitrix\Im;

class CIMDisk
{
	const MODULE_ID = 'im';

	const PATH_TYPE_SHOW = 'show';
	const PATH_TYPE_PREVIEW = 'preview';
	const PATH_TYPE_DOWNLOAD = 'download';

	public static function GetStorage()
	{
		if (!self::Enabled())
			return false;

		$storageModel = false;
		if ($storageId = self::GetStorageId())
		{
			$storageModel = Bitrix\Disk\Storage::loadById($storageId);
			if (!$storageModel || $storageModel->getModuleId() != self::MODULE_ID)
			{
				$storageModel = false;
			}
		}

		if (!$storageModel)
		{
			$data['NAME'] = GetMessage('IM_DISK_STORAGE_TITLE');
			$data['USE_INTERNAL_RIGHTS'] = 1;
			$data['MODULE_ID'] = self::MODULE_ID;
			$data['ENTITY_TYPE'] = Bitrix\Im\Disk\ProxyType\Im::className();
			$data['ENTITY_ID'] = self::MODULE_ID;

			$driver = Bitrix\Disk\Driver::getInstance();

			$rightsManager = $driver->getRightsManager();
			$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

			$storageModel = $driver->addStorageIfNotExist($data, array(
				array(
					'ACCESS_CODE' => 'AU',
					'TASK_ID' => $fullAccessTaskId,
				),
			));
			if ($storageModel)
			{
				self::SetStorageId($storageModel->getId());
			}
			else
			{
				$storageModel = false;
			}
		}

		return $storageModel;
	}

	public static function UploadFileRegister($chatId, $files)
	{
		if (intval($chatId) <= 0)
			return false;

		$chatRelation = CIMChat::GetRelationById($chatId);
		if (!$chatRelation[self::GetUserId()])
			return false;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
			return false;

		$result['FILE_ID'] = Array();
		$messageFileId = Array();
		foreach ($files as $fileId => $fileData)
		{
			if (!$fileData['mimeType'])
			{
				$fileData['mimeType'] = "binary";
			}
			if (!$fileData['name'])
			{
				continue;
			}
			$newFile = $folderModel->addBlankFile(Array(
				'NAME' => $fileData['name'],
				'SIZE' => $fileData['size'],
				'CREATED_BY' => self::GetUserId(),
				'MIME_TYPE' => $fileData['mimeType'],
			), Array(), true);
			if ($newFile)
			{
				$result['FILE_ID'][$fileId]['TMP_ID'] = $fileId;
				$result['FILE_ID'][$fileId]['FILE_ID'] = $newFile->getId();
				$result['FILE_ID'][$fileId]['FILE_NAME'] = $newFile->getName();

				$messageFileId[] = $newFile->getId();
			}
			else
			{
				$result['FILE_ID'][$fileId]['TMP_ID'] = $fileId;
				$result['FILE_ID'][$fileId]['FILE_ID'] = 0;
			}
		}
		if (empty($messageFileId))
		{
			return false;
		}

		$result['MESSAGE_ID'] = 0;
		$arChat = CIMChat::GetChatData(Array('ID' => $chatId));
		$ar = Array(
			"TO_CHAT_ID" => $chatId,
			"FROM_USER_ID" => self::GetUserId(),
			"MESSAGE_TYPE" => $arChat['chat'][$chatId]['type'] == IM_MESSAGE_PRIVATE? IM_MESSAGE_PRIVATE: IM_MESSAGE_GROUP,
			"PARAMS" => Array(
				'FILE_ID' => $messageFileId
			)
		);
		$messageId = CIMMessage::Add($ar);
		if ($messageId)
		{
			$result['MESSAGE_ID'] = $messageId;
		}

		return $result;
	}

	public static function UploadFile($hash, &$file, &$package, &$upload, &$error)
	{
		$post = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList()->toArray();
		$post['PARAMS'] = CUtil::JsObjectToPhp($post['REG_PARAMS']);

		$chatId = intval($post['REG_CHAT_ID']);
		if (intval($chatId) <= 0)
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}

		$chatRelation = CIMChat::GetRelationById($chatId);
		if (!$chatRelation[self::GetUserId()])
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}

		$fileId = $post['PARAMS'][$file["id"]];
		if (!$fileId)
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}

		if (!$file["files"]["default"])
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}

		/** @var $fileModel \Bitrix\Disk\File */
		$fileModel = \Bitrix\Disk\File::getById($fileId);
		if (!$fileModel || $fileModel->getParentId() != $folderModel->getId())
		{
			$error = GetMessage('IM_DISK_ERR_UPLOAD');
			return false;
		}
		$resultUpdate = $fileModel->uploadVersion($file["files"]["default"], self::GetUserId());
		if (!$resultUpdate)
		{
			$errors = $fileModel->getErrors();
			$message = '';
			foreach ($errors as $errorCode)
			{
				$message = $message.' '.$errorCode->getMessage();
			}
			$message = trim($message);
			if (strlen($message) > 0)
			{
				$error = $message;
			}
			return false;
		}

		$messageId = intval($post['REG_MESSAGE_ID']);

		$file['fileId'] = $fileId;
		$file['fileTmpId'] = $file["id"];
		$file['fileMessageId'] = $messageId;
		$file['fileChatId'] = $chatId;
		$file['fileParams'] = self::GetFileParams($chatId, $fileModel);

		if (CModule::IncludeModule('pull'))
		{
			foreach ($chatRelation as $relation)
			{
				CPullStack::AddByUser($relation['USER_ID'], Array(
					'module_id' => 'im',
					'command' => 'fileUpload',
					'params' => Array(
						'fileChatId' => $file['fileChatId'],
						'fileId' => $file['fileId'],
						'fileTmpId' => $file["id"],
						'fileMessageId' => $file["fileMessageId"],
						'fileParams' => $file['fileParams'],
					)
				));
			}
		}

		$arFiles[$fileId] = $file['fileParams'];
		$file['fileMessageOut'] = CIMMessenger::GetFormatFilesMessageOut($arFiles);

		CIMMessage::UpdateMessageOut($messageId, $file['fileMessageOut']);

		foreach(GetModuleEvents("im", "OnAfterFileUpload", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(Array(
				'CHAT_ID' => $file['fileChatId'],
				'FILE_ID' => $file['fileId'],
				'MESSAGE_ID' => $file['fileMessageId'],
				'MESSAGE_OUT' => $file['fileMessageOut'],
				'FILE' => $file['fileParams'],
			)));
		}

		return true;
	}

	public static function UploadFileUnRegister($chatId, $files, $messages)
	{
		if (intval($chatId) <= 0)
			return false;

		$chatRelation = CIMChat::GetRelationById($chatId);
		if (!$chatRelation[self::GetUserId()])
			return false;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
			return false;

		$result['CHAT_ID'] = $chatId;
		$result['FILE_ID'] = Array();
		$result['MESSAGE_ID'] = Array();
		foreach ($files as $fileTmpId => $fileId)
		{
			$fileModel = \Bitrix\Disk\File::getById($fileId);
			if (
				!$fileModel || $fileModel->getParentId() != $folderModel->getId()
				|| $fileModel->getCreatedBy() != self::GetUserId())
			{
				continue;
			}
			$fileModel->delete(self::GetUserId());
			$result['FILE_ID'][$fileTmpId] = $fileId;
		}
		foreach ($messages as $fileTmpId => $messageId)
		{
			if (!isset($result['FILE_ID'][$fileTmpId]))
				continue;

			$CIMMessage = new CIMMessage();
			$arMessage = $CIMMessage->GetMessage($messageId);
			if ($arMessage['AUTHOR_ID'] != self::GetUserId())
			{
				continue;
			}
			CIMMessage::Delete($messageId);
			$result['MESSAGE_ID'][$fileTmpId] = $messageId;
		}
		if (empty($result['FILE_ID']) && empty($result['MESSAGE_ID']))
			return false;

		if (CModule::IncludeModule('pull'))
		{
			foreach ($chatRelation as $relation)
			{
				CPullStack::AddByUser($relation['USER_ID'], Array(
					'module_id' => 'im',
					'command' => 'fileUnRegister',
					'params' => Array(
						'chatId' => $result['CHAT_ID'],
						'files' => $result['FILE_ID'],
						'messages' => $result['MESSAGE_ID'],
					)
				));
			}
		}

		return $result;
	}

	public static function UploadFileFromDisk($chatId, $files)
	{
		if (intval($chatId) <= 0)
			return false;

		$chatRelation = CIMChat::GetRelationById($chatId);
		if (!$chatRelation[self::GetUserId()])
			return false;

		$result['FILES'] = Array();
		$messageFileId = Array();
		foreach ($files as $fileId)
		{
			$newFile = self::SaveFromLocalDisk($chatId, substr($fileId, 4));
			if ($newFile)
			{
				$result['FILES'][$fileId] = self::GetFileParams($chatId, $newFile);
				$messageFileId[] = $newFile->getId();
			}
			else
			{
				$result['FILES'][$fileId]['id'] = 0;
			}
		}
		if (empty($messageFileId))
		{
			return false;
		}

		$result['MESSAGE_ID'] = 0;
		$arChat = CIMChat::GetChatData(Array('ID' => $chatId));
		$ar = Array(
			"TO_CHAT_ID" => $chatId,
			"FROM_USER_ID" => self::GetUserId(),
			"MESSAGE_TYPE" => $arChat['chat'][$chatId]['type'] == IM_MESSAGE_PRIVATE? IM_MESSAGE_PRIVATE: IM_MESSAGE_GROUP,
			"PARAMS" => Array(
				'FILE_ID' => $messageFileId
			)
		);
		$messageId = CIMMessage::Add($ar);
		if ($messageId)
		{
			$result['MESSAGE_ID'] = $messageId;
		}

		return $result;
	}


	public static function SaveToLocalDisk($fileId)
	{
		if (!self::Enabled())
			return false;

		if (intval($fileId) <= 0)
			return false;

		$fileModel = \Bitrix\Disk\File::getById($fileId, array('STORAGE'));
		if (!$fileModel)
			return false;

		$storageModel = $fileModel->getStorage();

		if(!$fileModel->canRead($storageModel->getCurrentUserSecurityContext()))
			return false;

		$folderModel = self::GetLocalDiskMolel();
		if (!$folderModel)
			return false;

		$newFileModel = $fileModel->copyTo($folderModel, self::GetUserId(), true);

		return $newFileModel;
	}

	public static function SaveFromLocalDisk($chatId, $fileId)
	{
		if (!self::Enabled())
			return false;

		if (intval($fileId) <= 0)
			return false;

		if (intval($chatId) <= 0)
			return false;

		$fileModel = \Bitrix\Disk\File::getById($fileId, array('STORAGE'));
		if (!$fileModel)
			return false;

		$storageModel = $fileModel->getStorage();

		if(!$fileModel->canRead($storageModel->getCurrentUserSecurityContext()))
			return false;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
			return false;

		$newFileModel = $fileModel->copyTo($folderModel, self::GetUserId(), true);
		if (!$newFileModel)
			return false;

		$newFileModel->increaseGlobalContentVersion();

		return $newFileModel;
	}

	public static function UploadAvatar($hash, &$file, &$package, &$upload, &$error)
	{
		$post = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList()->toArray();

		$chatId = intval($post['CHAT_ID']);
		if ($chatId <= 0)
			return false;

		$chat = Im\ChatTable::getById($chatId)->fetch();
		if (!$chat)
			return false;

		$relationError = true;
		$chatRelation = CIMChat::GetRelationById($chatId);
		foreach ($chatRelation as $relation)
		{
			if ($relation['USER_ID'] == self::GetUserId())
			{
				$relationError = false;
				break;
			}
		}
		if ($relationError)
		{
			$error = GetMessage('IM_DISK_ERR_AVATAR_1');
			return false;
		}

		$fileId = CFile::saveFile($file["files"]["default"], self::MODULE_ID);
		if ($fileId > 0)
		{
			if ($chat['AVATAR'] > 0)
			{
				CFile::DeLete($chat['AVATAR']);
			}
			Im\ChatTable::update($chatId, Array('AVATAR' => $fileId));

			$file['chatId'] = $chatId;
			$file['chatAvatar'] = CIMChat::GetAvatarImage($fileId);

			if ($chat["ENTITY_TYPE"] != 'CALL')
			{
				CIMChat::AddSystemMessage(Array(
					'CHAT_ID' => $chatId,
					'USER_ID' => self::GetUserId(),
					'MESSAGE_CODE' => 'IM_DISK_AVATAR_CHANGE_'
				));
			}

			if (CModule::IncludeModule('pull'))
			{
				foreach ($chatRelation as $relation)
				{
					CPullStack::AddByUser($relation['USER_ID'], Array(
						'module_id' => 'im',
						'command' => 'chatAvatar',
						'params' => Array(
							'chatId' => $chatId,
							'chatAvatar' => $file['chatAvatar'],
						),
					));
				}
			}
		}
		else
		{
			return false;
		}

		return true;
	}

	public static function GetHistoryFiles($chatId, $historyPage = 1)
	{
		$fileArray = Array();
		if (!self::Enabled())
			return $fileArray;

		if (intval($chatId) <= 0)
			return $fileArray;

		$offset = intval($historyPage)-1;
		if ($offset < 0)
			return $fileArray;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
		{
			return $fileArray;
		}

		$filter = Array(
			'PARENT_ID' => $folderModel->getId(),
			'STORAGE_ID' => $folderModel->getStorageId()
		);

		$relation = CIMChat::GetRelationById($chatId, self::GetUserId());
		if (!$relation)
			return $fileArray;

		if ($relation['LAST_FILE_ID'] > 0)
		{
			$filter['>ID'] = $relation['LAST_FILE_ID'];
		}

		$fileCollection = \Bitrix\Disk\File::getModelList(Array(
			'filter' => $filter,
			'with' => Array('CREATE_USER'),
			'limit' => 15,
			'offset' => $offset*15,
			'order' => Array('UPDATE_TIME' => 'DESC')
		));

		foreach ($fileCollection as $fileModel)
		{
			$fileArray[$fileModel->getId()] = self::GetFileParams($chatId, $fileModel);
		}

		return $fileArray;
	}

	public static function GetHistoryFilesByName($chatId, $name)
	{
		$fileArray = Array();
		if (!self::Enabled())
			return $fileArray;

		if (intval($chatId) <= 0)
			return $fileArray;

		$name = trim($name);
		if (strlen($name) <= 0)
			return $fileArray;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
		{
			return $fileArray;
		}

		$filter = Array(
			'PARENT_ID' => $folderModel->getId(),
			'STORAGE_ID' => $folderModel->getStorageId(),
			'%=NAME' => str_replace("%", '', $name)."%",
		);

		$relation = CIMChat::GetRelationById($chatId, self::GetUserId());
		if (!$relation)
			return $fileArray;

		if ($relation['LAST_FILE_ID'] > 0)
		{
			$filter['>ID'] = $relation['LAST_FILE_ID'];
		}

		$fileCollection = \Bitrix\Disk\File::getModelList(Array(
			'filter' => $filter,
			'with' => Array('CREATE_USER'),
			'limit' => 100,
			'order' => Array('UPDATE_TIME' => 'DESC')
		));

		foreach ($fileCollection as $fileModel)
		{
			$fileArray[$fileModel->getId()] = self::GetFileParams($chatId, $fileModel);
		}

		return $fileArray;
	}

	public static function GetMaxFileId($chatId)
	{
		$maxId = 0;
		if (!self::Enabled())
			return $maxId;

		if (intval($chatId) <= 0)
			return $maxId;

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
			return $maxId;

		$result = \Bitrix\Disk\Internals\ObjectTable::getList(array(
			'select' => array('MAX_ID'),
			'filter' => array(
				'PARENT_ID' => $folderModel->getId(),
				'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FILE
			),
			'runtime' => array(
				'MAX_ID' => array(
					'data_type' => 'integer',
					'expression' => array('MAX(ID)')
				)
			)
		));
		if ($data = $result->fetch())
			$maxId = $data['MAX_ID'];

		return intval($maxId);
	}

	public static function GetFiles($chatId, $fileId = false)
	{
		$fileArray = Array();
		if (!self::Enabled())
			return $fileArray;

		if (intval($chatId) <= 0)
			return $fileArray;

		if ($fileId === false)
		{
			if (!is_array($fileId))
			{
				$fileId = Array($fileId);
			}
			foreach ($fileId as $key => $value)
			{
				$fileId[$key] = intval($value);
			}
		}
		if (empty($fileId))
		{
			return $fileArray;
		}

		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
		{
			return $fileArray;
		}
		$filter = Array(
			'PARENT_ID' => $folderModel->getId(),
			'STORAGE_ID' => $folderModel->getStorageId()
		);
		if ($fileId)
		{
			$filter['ID'] = array_values($fileId);
		}

		$fileCollection = \Bitrix\Disk\File::getModelList(Array(
			'filter' => $filter,
			'with' => Array('CREATE_USER')
		));

		foreach ($fileCollection as $fileModel)
		{
			$fileArray[$fileModel->getId()] = self::GetFileParams($chatId, $fileModel);
		}

		return $fileArray;
	}

	public static function GetFileParams($chatId, $fileModel)
	{
		if (!self::Enabled())
			return false;

		if ($fileModel instanceof \Bitrix\Disk\File)
		{
		}
		else if (intval($fileModel) > 0)
		{
			$fileModel = \Bitrix\Disk\File::getById($fileModel);
		}
		else
		{
			return false;
		}

		$fileData = Array(
			'id' => $fileModel->getId(),
			'chatId' => intval($chatId),
			'date' => $fileModel->getCreateTime()->getTimestamp(),
			'type' => \Bitrix\Disk\TypeFile::isImage($fileModel->getName())? 'image': 'file',
			'preview' => '',
			'name' => $fileModel->getName(),
			'size' => $fileModel->getSize(),
			'status' => $fileModel->getGlobalContentVersion() > 1? 'done': 'upload',
			'progress' => $fileModel->getGlobalContentVersion() > 1? 100: -1,
			'authorId' => $fileModel->getCreatedBy(),
			'authorName' => CUser::FormatName(CSite::GetNameFormat(false), $fileModel->getCreateUser(), true, false),
			'urlPreview' => self::GetPublicPath(self::PATH_TYPE_PREVIEW, $fileModel),
			'urlShow' => self::GetPublicPath(self::PATH_TYPE_SHOW, $fileModel),
			'urlDownload' => self::GetPublicPath(self::PATH_TYPE_DOWNLOAD, $fileModel),
		);

		return $fileData;
	}

	public static function Enabled()
	{
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetNginxStatus())
			return false;

		if (!CModule::IncludeModule('disk'))
			return false;

		if (!Bitrix\Disk\Driver::isSuccessfullyConverted())
			return false;

		return true;
	}

	public static function GetFolderModel($chatId)
	{
		if (!self::Enabled())
			return false;

		$folderModel = false;

		$result = Im\ChatTable::getById($chatId);
		if (!$chat = $result->fetch())
			return false;

		$folderId = intval($chat['DISK_FOLDER_ID']);
		if ($folderId > 0)
		{
			$folderModel = \Bitrix\Disk\Folder::getById($folderId);
			if (!$folderModel || $folderModel->getStorageId() != self::GetStorageId())
			{
				$folderId = 0;
			}
		}

		if (!$folderId)
		{
			$driver = \Bitrix\Disk\Driver::getInstance();
			$storageModel = self::GetStorage();
			if (!$storageModel)
			{
				return false;
			}

			$rightsManager = $driver->getRightsManager();
			$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

			$accessCodes = array();
			$accessCodes[] = Array(
				'ACCESS_CODE' => 'AU',
				'TASK_ID' => $fullAccessTaskId,
				'NEGATIVE' => 1
			);

			$chatRelation = CIMChat::GetRelationById($chatId);
			foreach ($chatRelation as $relation)
			{
				$accessCodes[] = Array(
					'ACCESS_CODE' => 'U'.$relation['USER_ID'],
					'TASK_ID' => $fullAccessTaskId
				);
			}

			$folderModel = $storageModel->addFolder(array('NAME' => 'chat'.$chatId, 'CREATED_BY' => self::GetUserId()), $accessCodes);
			if ($folderModel)
				Im\ChatTable::update($chatId, Array('DISK_FOLDER_ID' => $folderModel->getId()));
		}

		return $folderModel;
	}

	public static function ChangeFolderMembers($chatId, $userId, $append = true)
	{
		$folderModel = self::GetFolderModel($chatId);
		if (!$folderModel)
			return false;

		if (!is_array($userId))
			$userIds = Array($userId);
		else
			$userIds = $userId;

		$driver = Bitrix\Disk\Driver::getInstance();
		$rightsManager = $driver->getRightsManager();
		if ($append)
		{
			$fullAccessTaskId = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

			$accessCodes = Array();
			foreach ($userIds as $userId)
			{
				$userId = intval($userId);
				if ($userId <= 0)
					continue;

				$accessCodes[] = array(
					'ACCESS_CODE' => 'U'.$userId,
					'TASK_ID' => $fullAccessTaskId,
				);
			}
			if (count($accessCodes) <= 0)
				return false;

			$result = $rightsManager->append($folderModel, $accessCodes);
		}
		else
		{
			$accessCodes = Array();
			foreach ($userIds as $userId)
			{
				$userId = intval($userId);
				if ($userId <= 0)
					continue;

				$accessCodes[] = 'U'.$userId;
			}
			$result = $rightsManager->revokeByAccessCodes($folderModel, $accessCodes);
		}

		return $result;
	}

	public static function GetLocalDiskMolel()
	{
		if (!self::Enabled())
			return false;

		$storageModel = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId(self::GetUserId());
		if (!$storageModel)
		{
			return false;
		}

		$folderModel = \Bitrix\Disk\Folder::load(array(
			'STORAGE_ID' => $storageModel->getId(),
			'PARENT_ID' => $storageModel->getRootObjectId(),
			'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
			'CODE' => 'IM_SAVED',
		));
		if (!$folderModel)
		{
			$folderName = GetMessage(IsModuleInstalled('intranet')? 'IM_DISK_LOCAL_FOLDER_B24_TITLE': 'IM_DISK_LOCAL_FOLDER_TITLE');
			$folderModel = $storageModel->addFolder(array(
				'NAME' => $folderName,
				'CREATED_BY' => self::GetUserId(),
				'CODE' => 'IM_SAVED',
			));
			if (!$folderModel)
			{
				if ($storageModel->getErrorByCode(\Bitrix\Disk\Folder::ERROR_NON_UNIQUE_NAME))
				{
					$folderModel = \Bitrix\Disk\Folder::load(array(
						'STORAGE_ID' => $storageModel->getId(),
						'PARENT_ID' => $storageModel->getRootObjectId(),
						'TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
						'NAME' => $folderName,
					));
					$folderModel->changeCode('IM_SAVED');
				}
				else
				{
					$folderModel = $storageModel->addFolder(array(
						'NAME' => $folderName,
						'CREATED_BY' => self::GetUserId(),
						'CODE' => 'IM_SAVED',
					), Array(), true);
				}
			}
		}
		return $folderModel;
	}

	public static function GetStorageId()
	{
		return COption::GetOptionInt('im', 'disk_storage_id', 0);
	}

	public static function SetStorageId($id)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$oldId = self::GetStorageId();
		if ($oldId > 0 && $oldId != $id)
		{
			global $DB;
			$DB->Query("UPDATE b_im_chat SET DISK_FOLDER_ID = 0");
			$DB->Query("DELETE FROM b_im_message_param WHERE PARAM_NAME = 'FILE_ID'");
		}

		COption::SetOptionInt('im', 'disk_storage_id', $id);

		return true;
	}

	public static function GetPublicPath($type, \Bitrix\Disk\File $fileModel)
	{
		if (!in_array($type, Array(self::PATH_TYPE_DOWNLOAD, self::PATH_TYPE_SHOW, self::PATH_TYPE_PREVIEW)))
			return '';

		if ($fileModel->getGlobalContentVersion() <= 1)
			return '';

		$isShow = in_array($type, Array(self::PATH_TYPE_SHOW, self::PATH_TYPE_PREVIEW)) && \Bitrix\Disk\TypeFile::isImage($fileModel->getName());
		$isPreview = $isShow && in_array($type, Array(self::PATH_TYPE_PREVIEW));

		if ($type == self::PATH_TYPE_PREVIEW && !$isPreview)
			return '';

		$url = Array(
			'default' => '/bitrix/components/bitrix/im.messenger/'.($isShow? 'show.file.php?': 'download.file.php?')
		);

		$url['desktop'] = '/desktop_app/'.($isShow? 'show.file.php?': 'download.file.php?');
		if (IsModuleInstalled('mobile'))
		{
			$url['mobile'] = '/mobile/ajax.php?mobile_action=im_files&fileType='.($isShow? 'show&': 'download&');
		}

		foreach ($url as $key => $value)
		{
			$url[$key] = $value.'fileId='.$fileModel->getId().($isPreview? '&preview=Y': '').($isShow || $key == 'mobile'? '&fileName='.urlencode($fileModel->getName()): '');
		}

		return $url;
	}

	public static function RemoveTmpFileAgent()
	{
		$storageModel = self::GetStorage();
		if (!$storageModel)
		{
			return "CIMDisk::RemoveTmpFileAgent();";
		}
		$date = new Bitrix\Main\Type\DateTime();
		$date->add('YESTERDAY');

		$fileModels = \Bitrix\Disk\File::getModelList(Array(
			'filter' => Array(
				'GLOBAL_CONTENT_VERSION' => 1,
				'STORAGE_ID' => $storageModel->getId(),
				'<CREATE_TIME' => $date
			),
			'limit' => 200
		));
		foreach ($fileModels as $fileModel)
		{
			$fileModel->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID);
		}

		return "CIMDisk::RemoveTmpFileAgent();";
	}
	
	public static function GetUserId()
	{
		global $USER;
		return $USER->GetId();
	}
}
?>