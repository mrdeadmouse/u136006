<?php
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\Ui;
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskUfFileComponent extends BaseComponent
{
	protected $editMode = false;

	protected function prepareParams()
	{
		if($this->arParams['EDIT'] === 'Y')
		{
			$this->editMode = true;
		}
		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'FILES' => $this->loadFilesData(),
			'UID' => $this->getComponentId(),
		);
		$driver = \Bitrix\Disk\Driver::getInstance();

		$this->arResult['CLOUD_DOCUMENT'] = array();

		static $documentHandlerName = null;
		static $documentHandlerCode = null;
		static $isLocal = null;

		if($documentHandlerName === null)
		{
			if(Configuration::canCreateFileByCloud())
			{
				$documentServiceCode = \Bitrix\Disk\UserConfiguration::getDocumentServiceCode();
				if(!$documentServiceCode)
				{
					$documentServiceCode = 'l';
				}
				if(LocalDocumentController::isLocalService($documentServiceCode))
				{
					$documentHandlerName = LocalDocumentController::getName();
					$documentHandlerCode = LocalDocumentController::getCode();
					$isLocal = true;
				}
				else
				{
					$defaultDocumentHandler = \Bitrix\Disk\Driver::getInstance()
						->getDocumentHandlersManager()
						->getDefaultServiceForCurrentUser()
					;
					if($defaultDocumentHandler)
					{
						$documentHandlerName = $defaultDocumentHandler->getName();
						$documentHandlerCode = $defaultDocumentHandler->getCode();
						$isLocal = false;
					}
				}
			}
		}
		if($documentHandlerCode)
		{
			$this->arResult['CLOUD_DOCUMENT'] = array(
				'DEFAULT_SERVICE' => $documentHandlerCode,
				'DEFAULT_SERVICE_LABEL' => $documentHandlerName,
				'IS_LOCAL' => $isLocal,
			);
			$this->arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_NAME'] = $documentHandlerName;
			$this->arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_CODE'] = $documentHandlerCode;
		}
		else
		{
			$documentHandlerCode = 'l';
		}

		if($this->editMode)
		{
			$this->arResult['controlName'] = $this->arParams['PARAMS']['arUserField']['FIELD_NAME'];
			$this->arResult['SHARE_EDIT_ON_OBJECT_UF'] = Configuration::isEnabledDefaultEditInUf();

			$urlManager = $driver->getUrlManager();
			$this->arResult['CREATE_BLANK_URL'] = $urlManager->getUrlToStartCreateUfFileByService('docx', $documentHandlerCode);
			$this->arResult['RENAME_FILE_URL'] = $urlManager->getUrlDocumentController('rename', array('document_action' => 'rename'));
			$this->arResult['UPLOAD_FILE_URL'] = $urlManager->getUrlToUploadUfFile();

			//now we show checkbox only if it's create post, etc.
			$this->arResult['DISK_ATTACHED_OBJECT_ALLOW_EDIT'] = empty($this->arResult['FILES']);
			$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
			$this->arResult['INPUT_NAME_OBJECT_ALLOW_EDIT'] = $userFieldManager->getInputNameForAllowEditByEntityType($this->arParams['PARAMS']['arUserField']['ENTITY_ID']);
		}

		foreach (GetModuleEvents("main", $this->arParams['PARAMS']['arUserField']["USER_TYPE_ID"], true) as $arEvent)
		{
			if (!ExecuteModuleEventEx($arEvent, array($this->arResult, $this->arParams)))
				return;
		}
		if(is_array($this->arParams['PARAMS']))
		{
			$this->arParams = array_merge($this->arParams, $this->arParams['PARAMS']);
		}

		$this->includeComponentTemplate($this->editMode ? 'edit' : 'show'.($this->arParams['INLINE'] == 'Y' ? '_inline' : ''));
	}

	private function loadFilesData()
	{
		if(empty($this->arParams['PARAMS']['arUserField']))
		{
			return array();
		}
		$userId = $this->getUser()->getId();
		$values = $this->arParams['PARAMS']['arUserField']['VALUE'];
		if(!is_array($this->arParams['PARAMS']['arUserField']['VALUE']))
		{
			$values = array($values);
		}
		$files = array();
		$driver = \Bitrix\Disk\Driver::getInstance();
		$urlManager = $driver->getUrlManager();
		$userFieldManager = $driver->getUserFieldManager();

		$userFieldManager->loadBatchAttachedObject($values);
		foreach($values as $id)
		{
			$attachedModel = null;
			list($type, $realValue) = FileUserType::detectType($id);
			if ($realValue <= 0)
			{
				continue;
			}
			elseif ($type == FileUserType::TYPE_NEW_OBJECT)
			{
				/** @var File $fileModel */
				$fileModel = File::loadById($realValue);
				if(!$fileModel || !$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
				{
					continue;
				}
			}
			else
			{
				/** @var \Bitrix\Disk\AttachedObject $attachedModel */
				$attachedModel = $userFieldManager->getAttachedObjectById($realValue);
				if(!$attachedModel)
				{
					continue;
				}
				if(!$this->editMode)
				{
					$attachedModel->setOperableEntity(array(
						'ENTITY_ID' => $this->arParams['PARAMS']['arUserField']['ENTITY_ID'],
						'ENTITY_VALUE_ID' => $this->arParams['PARAMS']['arUserField']['ENTITY_VALUE_ID'],
					));
				}
				/** @var File $fileModel */
				$fileModel = $attachedModel->getFile();
			}

			$name = $fileModel->getName();
			$data = array(
				'ID' => $id,
				'NAME' => $name,
				'CONVERT_EXTENSION' => DocumentHandler::isNeedConvertExtension($fileModel->getExtension()),
				'EDITABLE' => DocumentHandler::isEditable($fileModel->getExtension()),
				'CAN_UPDATE' => ($attachedModel ? $attachedModel->canUpdate($userId) : $fileModel->canUpdate($fileModel->getStorage()->getCurrentUserSecurityContext())),

				'FROM_EXTERNAL_SYSTEM' => $fileModel->getContentProvider() && $fileModel->getCreatedBy() == $userId,

				'EXTENSION' => $fileModel->getExtension(),
				'SIZE' => \CFile::formatSize($fileModel->getSize()),
				'XML_ID' => $fileModel->getXmlId(),
				'FILE_ID' => $fileModel->getId(),

				'VIEW_URL' => $urlManager->getUrlToShowAttachedFileByService($id, 'gvdrive'),
				'EDIT_URL' => $urlManager->getUrlToStartEditUfFileByService($id, 'gdrive'),
				'DOWNLOAD_URL' => $urlManager->getUrlUfController('download', array('attachedId' => $id)),
				'COPY_TO_ME_URL' => $urlManager->getUrlUfController('copyToMe', array('attachedId' => $id)),

				'DELETE_URL' => ""
			);
			if(\Bitrix\Disk\TypeFile::isImage($fileModel))
			{
				$this->arParams['PARAMS']['THUMB_SIZE'] = ($this->arParams['PARAMS']['THUMB_SIZE'] > 0 ? $this->arParams['PARAMS']['THUMB_SIZE'] : 100);
				$data["PREVIEW_URL"] = ($attachedModel === null ? $urlManager->getUrlForShowFile($fileModel) : $urlManager->getUrlUfController('show', array('attachedId' => $id)));
				$data["IMAGE"] = $fileModel->getFile();
			}


			if($this->editMode)
			{
				$data['STORAGE'] = $fileModel->getStorage()->getProxyType()->getTitleForCurrentUser() . ' / ' . $fileModel->getParent()->getName();
			}
			else
			{
				$data['ATTRIBUTES_FOR_VIEWER'] = Ui\Viewer::getAttributesByAttachedObject($attachedModel, array(
					'canUpdate' => $data['CAN_UPDATE'],
					'canFakeUpdate' => true,
					'showStorage' => false,
					'externalId' => false,
					'relativePath' => false,
				));
			}
			$files[] = $data;
		}
		unset($id);
		return $files;
	}
}