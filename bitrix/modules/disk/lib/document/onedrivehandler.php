<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO;

Loc::loadMessages(__FILE__);

class OneDriveHandler extends DocumentHandler
{
	const API_URL_V1 = 'https://api.onedrive.com/v1.0';

	const SPECIFIC_FOLDER_CODE = SpecificFolder::CODE_FOR_IMPORT_ONEDRIVE;

	const ERROR_NOT_INSTALLED_SOCSERV     = 'DISK_ONEDRIVE_HANDLER_22002';
	const ERROR_BAD_JSON                  = 'DISK_ONEDRIVE_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE          = 'DISK_ONEDRIVE_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE        = 'DISK_ONEDRIVE_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA         = 'DISK_ONEDRIVE_HANDLER_22008';
	const ERROR_HTTP_FILE_INTERNAL        = 'DISK_ONEDRIVE_HANDLER_22009';
	const ERROR_COULD_NOT_VIEW_FILE       = 'DISK_ONEDRIVE_HANDLER_22013';
	const ERROR_SHARED_EDIT_LINK          = 'DISK_ONEDRIVE_HANDLER_22014';
	const ERROR_SHARED_EMBED_LINK         = 'DISK_ONEDRIVE_HANDLER_22015';
	const ERROR_COULD_NOT_FIND_EMBED_LINK = 'DISK_ONEDRIVE_HANDLER_22016';
	const ERROR_HTTP_LIST_FOLDER          = 'DISK_ONEDRIVE_HANDLER_22017';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'onedrive';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_ONE_DRIVE_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_ONE_DRIVE_HANDLER_NAME_STORAGE');
	}

	/**
	 * Execute this method for check potential possibility get access token.
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function checkAccessibleTokenService()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}
		$authManager = new \CSocServAuthManager();
		$socNetServices = $authManager->getActiveAuthServices(array());

		return !empty($socNetServices[\CSocServLiveIDOAuth::ID]);
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$socLiveIdOAuth = new \CSocServLiveIDOAuth($this->userId);

		if($mode === 'opener')
		{
			return $socLiveIdOAuth->getUrl(
				'opener',
				array(
					'wl.contacts_skydrive',
					'wl.skydrive_update',
					'wl.skydrive',
				),
				array('BACKURL' => '#external-auth-ok')
			);
		}

		return $socLiveIdOAuth->getUrl('modal', array(
			'wl.contacts_skydrive',
			'wl.skydrive_update',
			'wl.skydrive',
		));
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_ONEDRIVE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$socLiveIdOAuth = new \CSocServLiveIDOAuth($this->userId);
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$socLiveIdOAuth->getUrl('modal', array(
			'wl.contacts_skydrive',
			'wl.skydrive_update',
			'wl.skydrive',
		));
		$this->accessToken = $socLiveIdOAuth->getStorageToken();

		return $this;
	}


	/**
	 * Create new blank file in cloud service.
	 * It is not necessary set shared rights on file.
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createBlankFile(FileData $fileData)
	{
		return $this->createFileInternal($fileData);
	}

	/**
	 * @param FileData $fileData
	 * @return FileData
	 */
	public function createFile(FileData $fileData)
	{
		$fileData = $this->createFileInternal($fileData);
		if($fileData === null)
		{
			return null;
		}
		$link = $this->getSharedEditLink($fileData);
		if($link === null)
		{
			return null;
		}
		$fileData->setLinkInService($link);

		return $fileData;
	}

	protected function createFileInternal(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$fileName = $fileData->getName();
		$fileName = $this->convertToUtf8($fileName);

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$fileName = urlencode($fileName);
		$accessToken = urlencode($accessToken);
		if($http->query('PUT', "https://apis.live.net/v5.0/me/skydrive/files/{$fileName}?access_token={$accessToken}", IO\File::getFileContents(IO\Path::convertPhysicalToLogical($fileData->getSrc()))) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_FILE_INTERNAL)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		$fileData->setId($finalOutput['id']);
		$props = $this->getFileData($fileData);
		if($props === null)
		{
			return null;
		}
		$fileData->setLinkInService($props['link']);

		return $fileData;
	}

	/**
	 * Get shared edit link on file
	 * @param FileData $fileData
	 * @return string|null
	 */
	protected function getSharedEditLink(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$accessToken = urlencode($accessToken);
		if($http->get("https://apis.live.net/v5.0/{$fileData->getId()}/shared_edit_link?access_token={$accessToken}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_SHARED_EDIT_LINK)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		return $finalOutput['link'];
	}

	/**
	 * Object data from service
	 * @param FileData $fileData
	 * @return mixed|null
	 */
	protected function getFileData(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$accessToken = urlencode($accessToken);
		if($http->get("https://apis.live.net/v5.0/{$fileData->getId()}?access_token={$accessToken}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_GET_METADATA)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		return $finalOutput;
	}

	/**
	 * @param FileData $fileData
	 * @return FileData
	 */
	public function downloadFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$accessToken = urlencode($accessToken);
		if($http->download("https://apis.live.net/v5.0/{$fileData->getId()}/content?access_token={$accessToken}", $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DOWNLOAD_FILE)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		return $fileData;
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if($http->get(self::API_URL_V1 . "/drive/items/{$fileData->getId()}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_GET_METADATA)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$metaData = Json::decode($http->getResult());
		if($metaData === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		if(empty($metaData['file']))
		{
			$this->errorCollection->add(array(
				new Error('Could not get meta-data by folder', self::ERROR_HTTP_GET_METADATA)
			));
			return null;
		}

		return array(
			'id' => $metaData['id'],
			'name' => $metaData['name'],
			'size' => $metaData['size'],
			'mimeType' => $metaData['file']['mimeType'],
			'etag' => $metaData['eTag'],
		);
	}

	/**
	 * Download part of file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @param          $startRange
	 * @param          $chunkSize
	 * @return FileData|null
	 */
	public function downloadPartFile(FileData $fileData, $startRange, $chunkSize)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		@set_time_limit(0);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$http->setHeader('Authorization', "bearer {$accessToken}");

		$endRange = $startRange + $chunkSize - 1;
		$http->setHeader('Range', "bytes={$startRange}-{$endRange}");

		if($http->download(self::API_URL_V1 . "/drive/items/{$fileData->getId()}/content", $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DOWNLOAD_FILE)
			));
			return null;
		}

		return $fileData;
	}

	/**
	 * @param FileData $fileData
	 * @return bool
	 */
	public function deleteFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'redirect' => false,
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$accessToken = urlencode($accessToken);
		if($http->query('DELETE', "https://apis.live.net/v5.0/{$fileData->getId()}?access_token={$accessToken}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DELETE_FILE)
			));
			return false;
		}

		if(!$this->checkHttpResponse($http))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get data for showing preview file.
	 * Array must be contains keys: id, viewUrl, neededDelete, neededCheckView
	 * @param FileData $fileData
	 * @return array|null
	 */
	public function getDataForViewFile(FileData $fileData)
	{
		$fileData = $this->createFileInternal($fileData);
		if($fileData === null)
		{
			return null;
		}
		$link = $this->getSharedEmbedLink($fileData);
		if($link === null)
		{
			$this->errorCollection->add(array(
				new Error(Loc::getMessage('DISK_ONE_DRIVE_HANDLER_ERROR_COULD_NOT_VIEW_FILE'), self::ERROR_COULD_NOT_VIEW_FILE)
			));
			return null;
		}

		return array(
			'id' => $fileData->getId(),
			'viewUrl' => $link,
			'neededDelete' => true,
			'neededCheckView' => false,
		);
	}


	/**
	 * Lists folder contents
	 * @param $path
	 * @param $folderId
	 * @return mixed
	 */
	public function listFolder($path, $folderId)
	{
		if($path === '/')
		{
			$folderId = 'root';
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "bearer {$this->getAccessToken()}");

		if($http->get(self::API_URL_V1 . "/drive/items/{$folderId}?expand=children") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$items = Json::decode($http->getResult());
		if($items === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		if(!isset($items['children']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find items in response', self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		$reformatItems = array();
		foreach($items['children'] as $item)
		{
			$isFolder = isset($item['folder']);
			$dateTime = new \DateTime($item['lastModifiedDateTime']);
			$reformatItems[$item['id']] = array(
				'id' => $item['id'],
				'name' => $item['name'],
				'type' => $isFolder? 'folder' : 'file',

				'size' => $isFolder? '' : \CFile::formatSize($item['size']),
				'sizeInt' => $isFolder? '' : $item['size'],
				'modifyBy' => '',
				'modifyDate' => $dateTime->format('d.m.Y'),
				'modifyDateInt' => $dateTime->getTimestamp(),
				'provider' => static::getCode(),
			);
			if(!$isFolder)
			{
				$reformatItems[$item['id']]['storage'] = '';
				$reformatItems[$item['id']]['ext'] = getFileExtension($item['name']);
			}
		}
		unset($item);

		return $reformatItems;
	}

	protected function getSharedEmbedLink(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));

		$accessToken = urlencode($accessToken);
		if($http->get("https://apis.live.net/v5.0/{$fileData->getId()}/embed?access_token={$accessToken}") === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_SHARED_EMBED_LINK)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		$finalOutput = Json::decode($http->getResult());
		if($finalOutput === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		if(!preg_match('%src="(.*)"%iuU', $finalOutput['embed_html'], $m))
		{
			$this->errorCollection->add(array(
				new Error(Loc::getMessage('DISK_ONE_DRIVE_HANDLER_ERROR_COULD_NOT_FIND_EMBED_LINK'), self::ERROR_COULD_NOT_FIND_EMBED_LINK)
			));
			return null;
		}

		return $m[1];
	}
}