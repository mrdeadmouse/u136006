<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;

final class DocumentController extends Document\DocumentController
{
	const ERROR_COULD_NOT_FIND_ATTACHED_OBJECT = 'DISK_UFDOCC_22001';

	/** @var AttachedObject */
	protected $attachedModel;
	/** @var int */
	protected $attachedId;

	protected function prepareParams()
	{
		if($this->isActionWithExistsFile())
		{
			if(!$this->checkRequiredGetParams(array('attachedId')))
			{
				return false;
			}
			$this->attachedId = (int)$this->request->getQuery('attachedId');
		}

		return true;
	}

	protected function initializeData()
	{
		$this->attachedModel = AttachedObject::loadById($this->attachedId, array('OBJECT.STORAGE', 'VERSION'));
		if(!$this->attachedModel)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_DOCUMENT_CONTROLLER_ERROR_COULD_NOT_FIND_ATTACHED_OBJECT'), self::ERROR_COULD_NOT_FIND_ATTACHED_OBJECT)));
			$this->sendJsonErrorResponse();
		}
		$this->fileId = $this->attachedModel->getObjectId();
		$this->file = $this->attachedModel->getFile();
		if($this->attachedModel->getVersionId())
		{
			$this->versionId = $this->attachedModel->getVersionId();
			$this->version = $this->attachedModel->getVersion();
		}
	}

	protected function initializeFile($fileId)
	{
		if($fileId == $this->attachedModel->getObjectId())
		{
			$this->file = $this->attachedModel->getFile();
		}
		else
		{
			parent::initializeFile($fileId);
		}
	}

	protected function checkReadPermissions()
	{
		if(!$this->attachedModel->canRead($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_DOCUMENT_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

	protected function checkUpdatePermissions()
	{
		if(!$this->attachedModel->canUpdate($this->getUser()->getId()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_UF_DOCUMENT_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			$this->sendJsonErrorResponse();
		}
	}

}