<?php
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui\Icon;
use Bitrix\Main\Security\Random;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	return false;
}
class CDiskExternalLinkComponent extends DiskComponent
{
	/** @var \Bitrix\Disk\ExternalLink */
	protected $externalLink;
	/** @var string */
	protected $hash;

	protected function processBeforeAction($actionName)
	{
		$this->findLink();

		return true;
	}

	protected function listActions()
	{
		return array(
			'download',
			'showFile',
			'showPreview',
		);
	}

	protected function runProcessingExceptionComponent(Exception $e)
	{
		$this->includeComponentTemplate('error');
	}

	protected function prepareParams()
	{
		if(!isset($_GET['hash']))
		{
			throw new \Bitrix\Main\ArgumentException('Empty hash');
		}

		if(!\Bitrix\Disk\ExternalLink::isValidValueForField('HASH', $_GET['hash'], $this->errorCollection))
		{
			throw new \Bitrix\Main\ArgumentException('Hash contains invalid character');
		}
		$this->hash = $_GET['hash'];

		return $this;
	}

	private function storeDownloadToken(File $file, $token)
	{
		$_SESSION['DISK_PUBLIC_VERIFICATION'][$file->getId()] = $token;
	}

	private function checkDownloadToken(File $file, $token)
	{
		return $_SESSION['DISK_PUBLIC_VERIFICATION'][$file->getId()] === $token;
	}

	protected function processActionDefault()
	{
		$validPassword = true;
		if($this->externalLink->hasPassword())
		{
			$validPassword = $this->checkPassword();
		}

		$file = $this->externalLink->getFile();

		$downloadToken = Random::getString(12);
		$this->storeDownloadToken($file, $downloadToken);
		$this->arResult = array(
			'FILE' => array(
				'ID' => $file->getId(),
				'IS_IMAGE' => TypeFile::isImage($file->getName()),
				'ICON_CLASS' => Icon::getIconClassByObject($file),
				'UPDATE_TIME' => $file->getUpdateTime(),
				'NAME' => $file->getName(),
				'SIZE' => $file->getSize(),
				'DOWNLOAD_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'download',
					'token' => $downloadToken,
				)),
				'SHOW_PREVIEW_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'showPreview',
					'token' => $downloadToken,
				)),
				'SHOW_FILE_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'showFile',
					'token' => $downloadToken,
				)),
				'VIEW_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'default',
				), true),
				'VIEW_FULL_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(array(
					'hash' => $this->externalLink->getHash(),
					'action' => 'default',
				)),
			),
			'PROTECTED_BY_PASSWORD' => $this->externalLink->hasPassword(),
			'VALID_PASSWORD' => $validPassword,
		);

		$this->includeComponentTemplate();
	}

	protected function checkPassword()
	{
		$password = null;
		if(isset($_POST['PASSWORD']))
		{
			$password = $_POST['PASSWORD'];
		}
		elseif(isset($_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"]) && strlen($_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"]) > 0)
		{
			$password = $_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"];
		}

		if($password === null)
		{
			return null;
		}

		if($this->externalLink->checkPassword($password))
		{
			if(!isset($_SESSION["DISK_DATA"]))
			{
				$_SESSION["DISK_DATA"] = array();
			}
			$_SESSION["DISK_DATA"]["EXT_LINK_PASSWORD"] = $password;

			return true;
		}

		return false;
	}

	protected function processActionDownload($showFile = false, $runResize = false)
	{
		if($this->externalLink->hasPassword() && !$this->checkPassword())
		{
			$this->showAccessDenied();
			return false;
		}
		$file = $this->externalLink->getFile();

		if(!$file)
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		if(!$this->externalLink->isAutomatic() && !$this->checkDownloadToken($file, $this->request->getQuery('token')))
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		$this->externalLink->incrementDownloadCount();
		if($this->externalLink->isSpecificVersion())
		{
			$version = $file->getVersion($this->externalLink->getVersionId());
			if(!$version)
			{
				$this->includeComponentTemplate('error');
				return false;
			}
			$fileData = $version->getFile();
		}
		else
		{
			$fileData = $file->getFile();
		}

		if(!$fileData)
		{
			$this->includeComponentTemplate('error');
			return false;
		}

		if($runResize && TypeFile::isImage($fileData['ORIGINAL_NAME']))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$tmpFile = \CFile::resizeImageGet($fileData, array("width" => 255, "height" => 255), BX_RESIZE_IMAGE_EXACT, true, false, true);
			$fileData["FILE_SIZE"] = $tmpFile["size"];
			$fileData["SRC"] = $tmpFile["src"];
		}

		CFile::viewByUser($fileData, array('force_download' => !$showFile, 'attachment_name' => $file->getName()));
	}

	protected function processActionShowFile()
	{
		$this->processActionDownload(true);
	}

	protected function processActionShowPreview()
	{
		$this->processActionDownload(true, true);
	}

	protected function findLink()
	{
		$this->externalLink = \Bitrix\Disk\ExternalLink::load(array('=HASH' => $this->hash), array('FILE'));

		if(!$this->externalLink || $this->externalLink->isExpired())
		{
			throw new \Bitrix\Main\SystemException('Invalid external link');
		}

		return $this;
	}
}