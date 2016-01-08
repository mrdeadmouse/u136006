<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DownloadController extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_VERSION   = 'DISK_DC_22003';
	const ERROR_COULD_NOT_FIND_FILE      = 'DISK_DC_22004';
	const ERROR_BAD_RIGHTS               = 'DISK_DC_22005';
	const ERROR_COULD_NOT_FIND_REAL_FILE = 'DISK_DC_22006';

	protected $fileId;
	protected $versionId;
	/** @var File */
	protected $file;
	/** @var Version */
	protected $version;

	protected function listActions()
	{
		return array(
			'showFile' => array(
				'method' => array('GET'),
				'redirect_on_auth' => true,
				'close_session' => true,
			),
			'downloadFile',
			'downloadVersion',
			'copyToMe' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => true,
			),
		);
	}

	protected function prepareParams()
	{
		if(!$this->checkRequiredGetParams(array('fileId')))
		{
			return false;
		}
		$this->fileId = (int)$this->request->getQuery('fileId');
		if($this->request->getQuery('versionId'))
		{
			$this->versionId = (int)$this->request->getQuery('versionId');
		}

		return true;
	}

	protected function processBeforeAction($actionName)
	{
		$this->file = File::loadById($this->fileId, array('STORAGE'));
		if(!$this->file)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			$this->sendJsonErrorResponse();
		}
		if($this->file instanceof FileLink && !$this->file->getRealObject())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_REAL_FILE)));
			$this->sendJsonErrorResponse();
		}
		$this->checkPermissions();

		return true;
	}

	protected function checkPermissions()
	{
		$securityContext = $this->file->getStorage()->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));

			if(Desktop::getDiskVersion())
			{
				$this->sendJsonErrorResponse();
			}
			//general for user we show simple message
			$this->sendResponse(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_BAD_RIGHTS'));
		}
	}

	protected function processActionDownloadFile()
	{
		$fileData = $this->file->getFile();
		\CFile::viewByUser($fileData, array('force_download' => true, 'cache_time' => 0, 'attachment_name' => $this->file->getName()));
	}

	protected function processActionShowFile()
	{
		$fileName = $this->file->getName();
		$fileData = $this->file->getFile();

		if(!$fileData)
		{
			$this->end();
		}

		$isImage = TypeFile::isImage($fileData["ORIGINAL_NAME"]);
		$cacheTime = $isImage? 86400 : 0;

		$width = $this->request->getQuery('width');
		$height = $this->request->getQuery('height');
		if ($isImage && ($width > 0 || $height > 0))
		{
			$signature = $this->request->getQuery('signature');
			if(!$signature)
			{
				$this->sendJsonInvalidSignResponse('Empty signature');
			}
			if(!ParameterSigner::validateImageSignature($signature, $this->file->getId(), $width, $height))
			{
				$this->sendJsonInvalidSignResponse('Invalid signature');
			}

			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$tmpFile = \CFile::resizeImageGet($fileData, array("width" => $width, "height" => $height), ($this->request->getQuery('exact') == "Y" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, false, true);
			$fileData["FILE_SIZE"] = $tmpFile["size"];
			$fileData["SRC"] = $tmpFile["src"];
		}

		\CFile::viewByUser($fileData, array('force_download' => false, 'cache_time' => $cacheTime, 'attachment_name' => $fileName));
	}

	protected function processActionDownloadVersion()
	{
		$this->version = $this->file->getVersion($this->versionId);
		if(!$this->version)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_DOWNLOAD_CONTROLLER_ERROR_COULD_NOT_FIND_VERSION'), self::ERROR_COULD_NOT_FIND_VERSION)));
			$this->sendJsonErrorResponse();
		}

		$fileData = $this->version->getFile();
		\CFile::viewByUser($fileData, array("force_download" => false, 'cache_time' => 0, 'attachment_name' => $this->file->getName()));
	}

	protected function processActionCopyToMe()
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->getUser()->getId());
		if(!$userStorage)
		{
			$this->errorCollection->add(array(new Error("Could not find storage for current user")));
			$this->sendJsonErrorResponse();
		}
		$folder = $userStorage->getFolderForSavedFiles($this->getUser()->getId());
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error("Could not find folder for created files")));
			$this->sendJsonErrorResponse();
		}
		$newFile = $this->file->copyTo($folder, $this->getUser()->getId(), true);

		if(!$newFile)
		{
			$this->errorCollection->add(array(new Error("Could not copy file to storage for current user")));
			$this->sendJsonErrorResponse();
		}

		$crumbs = array();
		foreach($newFile->getParents(Driver::getInstance()->getFakeSecurityContext()) as $parent)
		{
			if($parent->getId() == $userStorage->getRootObjectId())
			{
				continue;
			}
			$crumbs[] = $parent->getName();
		}
		unset($parent);

		$viewUrl = Driver::getInstance()->getUrlManager()->encodeUrn($userStorage->getProxyType()->getStorageBaseUrl() . 'path/' . implode('/', $crumbs));

		$this->sendJsonSuccessResponse(array(
			'newId' => $newFile->getId(),
			'viewUrl' => $viewUrl . '#hl-' . $newFile->getId(),
		));
	}
}