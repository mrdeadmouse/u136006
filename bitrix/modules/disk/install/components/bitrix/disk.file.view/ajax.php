<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\ExternalLink;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Version;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFileViewAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT          = 'DISK_FLAC_22001';
	const ERROR_COULD_NOT_CREATE_FIND_EXT_LINK = 'DISK_FLAC_22004';
	const ERROR_COULD_NOT_FIND_VERSION         = 'DISK_FLAC_22005';

	protected function listActions()
	{
		return array(
			'disableExternalLink' => array(
				'method' => array('POST'),
			),
			'getExternalLink' => array(
				'method' => array('POST'),
			),
			'generateExternalLink' => array(
				'method' => array('POST'),
			),
			'restoreFromVersion' => array(
				'method' => array('POST'),
			),
			'deleteVersion' => array(
				'method' => array('POST'),
			),
			'deleteBizProc' => array(
				'method' => array('POST'),
			),
			'stopBizProc' => array(
				'method' => array('POST'),
			),
		);
	}

	private function getFileAndExternalLink()
	{
		if(!$this->checkRequiredPostParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canRead($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}
		$extLinks = $file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'=IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array($file, array_pop($extLinks));
	}

	protected function processActionDisableExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();
		if(!$extLink || $extLink->delete())
		{
			$this->sendJsonSuccessResponse();
		}
		$this->sendJsonErrorResponse();
	}

	protected function processActionGetExternalLink()
	{
		/** @var File $file */
		/** @var ExternalLink $extLink */
		list($file, $extLink) = $this->getFileAndExternalLink();

		if(!$extLink)
		{
			$this->sendJsonSuccessResponse(array(
				'hash' => null,
				'link' => null,
			));
		}
		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	protected function processActionGenerateExternalLink()
	{
		/** @var File $file */
		list($file, $extLink) = $this->getFileAndExternalLink();
		if(!$extLink)
		{
			$extLink = $file->addExternalLink(array(
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extLink)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_CREATE_FIND_EXT_LINK'), self::ERROR_COULD_NOT_CREATE_FIND_EXT_LINK));
			$this->errorCollection->add($file->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array(
			'hash' => $extLink->getHash(),
			'link' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
				'hash' => $extLink->getHash(),
				'action' => 'default',
			), true),
		));
	}

	public function processActionRestoreFromVersion()
	{
		if(!$this->checkRequiredPostParams(array('versionId', 'objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		/** @var Version $version */
		$version = $file->getVersion($this->request->getPost('versionId'));
		if(!$version)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canRestore($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$file->restoreFromVersion($version, $this->getUser()->getId()))
		{
			$this->errorCollection->add($version->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	public function processActionDeleteVersion()
	{
		if(!$this->checkRequiredPostParams(array('versionId', 'objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getPost('objectId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		/** @var Version $version */
		$version = $file->getVersion($this->request->getPost('versionId'));
		if(!$version)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION));
			$this->sendJsonErrorResponse();
		}

		$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
		if(!$file->canDelete($securityContext) || !$file->canRestore($securityContext))
		{
			$this->sendJsonAccessDeniedResponse();
		}

		if(!$version->delete())
		{
			$this->errorCollection->add($version->getErrors());
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse();
	}

	public function processActionDeleteBizProc()
	{
		$this->checkRequiredPostParams(array('fileId', 'idBizProc'));
		if (!Loader::includeModule("bizproc"))
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_BIZPROC_LOAD')));
		}
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$fileId = (int)$this->request->getPost('fileId');
		/** @var File $file */
		$file = File::loadById((int)$fileId);
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$documentData = array(
			'DISK' => array(
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($fileId),
			),
			'WEBDAV' => array(
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($fileId),
			),
		);

		$webdavFileId = $file->getXmlId();
		if(!empty($webdavFileId))
		{
			$documentData['OLD_FILE_COMMON'] = array(
				'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdav', $webdavFileId),
			);
			$documentData['OLD_FILE_GROUP'] = array(
				'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdavSocnet', $webdavFileId),
			);
		}

		$workflowId = $this->request->getPost('idBizProc');
		foreach($documentData as $nameModule => $data)
		{
			$availabilityProcess = CBPDocument::GetDocumentState($data['DOCUMENT_ID'], $workflowId);
			if(!empty($availabilityProcess))
			{
				if(CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::CreateWorkflow,
					$this->getUser()->getId(),
					$data['DOCUMENT_ID'])
				)
				{
					CBPTrackingService::deleteByWorkflow($workflowId);
					CBPTaskService::deleteByWorkflow($workflowId);
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CBPStateService::deleteWorkflow($workflowId);
				}
			}
		}
		$this->sendJsonSuccessResponse();
	}

	public function processActionStopBizProc()
	{
		$this->checkRequiredPostParams(array('fileId', 'idBizProc'));
		if (!Loader::includeModule("bizproc"))
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_BIZPROC_LOAD')));
		}
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$fileId = (int)$this->request->getPost('fileId');
		/** @var File $file */
		$file = File::loadById($fileId);
		if(!$file)
		{
			$this->errorCollection->addOne(new Error(Loc::getMessage('DISK_FILE_VIEW_ERROR_COULD_NOT_FIND_OBJECT'), self::ERROR_COULD_NOT_FIND_OBJECT));
			$this->sendJsonErrorResponse();
		}

		$documentData = array(
			'DISK' => array(
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($fileId),
			),
			'WEBDAV' => array(
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($fileId),
			),
		);

		$webdavFileId = $file->getXmlId();
		if(!empty($webdavFileId))
		{
			$documentData['OLD_FILE_COMMON'] = array(
				'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdav', $webdavFileId),
			);
			$documentData['OLD_FILE_GROUP'] = array(
				'DOCUMENT_ID' => array('webdav', 'CIBlockDocumentWebdavSocnet', $webdavFileId),
			);
		}
		$workflowId = $this->request->getPost('idBizProc');
		$error = array();
		foreach($documentData as $nameModule => $data)
		{
			$availabilityProcess = CBPDocument::GetDocumentState($data['DOCUMENT_ID'], $workflowId);
			if(!empty($availabilityProcess))
			{
				if(CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$this->getUser()->getId(),
					$data['DOCUMENT_ID'])
				)
				{
					CBPDocument::TerminateWorkflow(
						$workflowId,
						$data['DOCUMENT_ID'],
						$error
					);
				}
			}
		}
		if($error)
		{
			$this->errorCollection->addOne(new Error(array_shift($error)));
			$this->sendJsonErrorResponse();
		}
		$this->sendJsonSuccessResponse();
	}
}
$controller = new DiskFileViewAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;