<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Sharing;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskFileUploadAjaxController extends \Bitrix\Disk\Internals\Controller
{
	const ERROR_COULD_NOT_FIND_FOLDER = 'DISK_FUAC_22001';
	const ERROR_BAD_RIGHTS            = 'DISK_FUAC_22002';
	const ERROR_COULD_NOT_FIND_FILE   = 'DISK_FUAC_22003';
	const ERROR_COULD_UPLOAD_VERSION  = 'DISK_FUAC_22004';

	protected function listActions()
	{
		return array(
			'upload' => array(
				'method' => array('POST', 'GET'),
				'check_csrf_token' => false,
			),
		);
	}

	protected function processActionUpload()
	{
		static $uploader = null;
		if ($uploader === null)
		{
			$uploader = new \CFileUploader(array("events" => array("onFileIsUploaded" => array($this, "processActionHandleFile"))), "get");
		}
		$uploader->checkPost();
	}

	protected function processActionUploadFile($hash, &$file, &$package, &$upload, &$error)
	{
		$update = isset($_POST["REPLACE_FILE"]) && is_array($_POST["REPLACE_FILE"]) && in_array($file["id"], $_POST["REPLACE_FILE"]);
		/** @var Folder $folder */
		$folder = Folder::loadById((int)$_POST['targetFolderId'], array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FOLDER'), self::ERROR_COULD_NOT_FIND_FOLDER)));
			$error = implode(" ", $this->errorCollection->toArray());
			return true;
		}
		else if ($update)
		{
			/** @var File $fileModel */
			$fileModel = File::load(array('NAME' => $file['name'], 'PARENT_ID' => $folder->getRealObjectId()));
			if (!$fileModel)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			}
			else if(!$fileModel->canUpdate($fileModel->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
			}
			else if (!$fileModel->uploadVersion($file["files"]["default"], $this->getUser()->getId()))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_UPLOAD_VERSION'), self::ERROR_COULD_UPLOAD_VERSION)));
			}
		}
		elseif(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS'), self::ERROR_BAD_RIGHTS)));
		}
		else
		{
			$fileModel = $folder->uploadFile(
				$file["files"]["default"],
				array(
					'NAME' => $file['name'],
					'CREATED_BY' => $this->getUser()->getId(),
				)
			);
			if(!$fileModel)
			{
				$this->errorCollection->add($folder->getErrors());
			}
			if($fileModel && isset($_POST['checkBp']))
			{
				$workflowParameters = array();
				$search = 'bizproc';
				foreach($_POST as $idParameter => $valueParameter)
				{
					$res = strpos($idParameter, $search);
					if($res === 0)
					{
						$workflowParameters[$idParameter] = $valueParameter;
					}
				}
				$autoExecute = intval($_POST['autoExecute']);
				\Bitrix\Disk\BizProcDocument::startAutoBizProc($fileModel->getStorageId(), $fileModel->getId(), $autoExecute, $workflowParameters);
			}
		}

		$error = implode(" ", ($folder->getErrors() + $this->errorCollection->toArray()));
		if ($folder->getErrorByCode($folder::ERROR_NON_UNIQUE_NAME))
		{
			$file["isNotUnique"] = true;
		}

		return (empty($error));
	}

	protected function processActionUpdateFile($hash, &$fileData, &$package, &$upload, &$error)
	{
		$this->checkRequiredInputParams($_POST, array('targetFileId'));
		$file = false;
		if(!$this->errorCollection->hasErrors())
		{
			/** @var File $file */
			$file = File::loadById((int)$_POST['targetFileId'], array('STORAGE'));
			if($file && isset($_POST['checkBp']))
			{
				$workflowParameters = array();
				$search = 'bizproc';
				foreach($_POST as $idParameter => $valueParameter)
				{
					$res = strpos($idParameter, $search);
					if($res === 0)
					{
						$workflowParameters[$idParameter] = $valueParameter;
					}
				}
				$autoExecute = intval($_POST['autoExecute']);
				\Bitrix\Disk\BizProcDocument::startAutoBizProc($file->getStorageId(), $file->getId(), $autoExecute, $workflowParameters);
			}
			if(!$file)
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_NOT_FIND_FILE'), self::ERROR_COULD_NOT_FIND_FILE)));
			}
			else if (!$file->canUpdate($file->getStorage()->getCurrentUserSecurityContext()))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_BAD_RIGHTS2'), self::ERROR_BAD_RIGHTS)));
			}
			else if (!$file->uploadVersion($fileData["files"]["default"], $this->getUser()->getId()))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_UPLOAD_ERROR_COULD_UPLOAD_VERSION'), self::ERROR_COULD_UPLOAD_VERSION)));
			}
		}
		$error = implode(" ", ((is_object($file) ? $file->getErrors() : array()) + $this->errorCollection->toArray()));
		return (empty($error));
	}

	public function processActionHandleFile($hash, &$file, &$package, &$upload, &$error)
	{
		return (array_key_exists('targetFileId', $_POST) ?
			$this->processActionUpdateFile($hash, $file, $package, $upload, $error) :
			$this->processActionUploadFile($hash, $file, $package, $upload, $error)
		);
	}
}
$controller = new DiskFileUploadAjaxController();
$controller
	->setActionName( "upload" )
	->exec()
;