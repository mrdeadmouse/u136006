<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class GoogleHandler extends DocumentHandler
{
	const API_URL_V2 = 'https://www.googleapis.com/drive/v2';

	const SPECIFIC_FOLDER_CODE = SpecificFolder::CODE_FOR_IMPORT_GDRIVE;

	const ERROR_NOT_INSTALLED_SOCSERV        = 'DISK_GOOGLE_HANDLER_22002';
	const ERROR_UNSUPPORTED_FILE_FORMAT      = 'DISK_GOOGLE_HANDLER_22003';
	const ERROR_HTTP_CREATE_BLANK            = 'DISK_GOOGLE_HANDLER_22004';
	const ERROR_BAD_JSON                     = 'DISK_GOOGLE_HANDLER_22005';
	const ERROR_HTTP_DELETE_FILE             = 'DISK_GOOGLE_HANDLER_22006';
	const ERROR_HTTP_DOWNLOAD_FILE           = 'DISK_GOOGLE_HANDLER_22007';
	const ERROR_HTTP_GET_METADATA            = 'DISK_GOOGLE_HANDLER_22008';
	const ERROR_HTTP_GET_LOCATION_FOR_UPLOAD = 'DISK_GOOGLE_HANDLER_22009';
	const ERROR_HTTP_INSERT_PERMISSION       = 'DISK_GOOGLE_HANDLER_22010';
	const ERROR_HTTP_RESUMABLE_UPLOAD        = 'DISK_GOOGLE_HANDLER_22012';
	const ERROR_COULD_NOT_VIEW_FILE          = 'DISK_GOOGLE_HANDLER_22013';
	const ERROR_COULD_NOT_FIND_ID            = 'DISK_GOOGLE_HANDLER_22014';
	const ERROR_HTTP_LIST_FOLDER             = 'DISK_GOOGLE_HANDLER_22015';

	/**
	 * @inheritdoc
	 */
	public static function getCode()
	{
		return 'gdrive';
	}

	/**
	 * @inheritdoc
	 */
	public static function getName()
	{
		return Loc::getMessage('DISK_GOOGLE_HANDLER_NAME');
	}

	/**
	 * Public name storage of documents. May show in user interface.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getStorageName()
	{
		return Loc::getMessage('DISK_GOOGLE_HANDLER_NAME_STORAGE');
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
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}
		$authManager = new \CSocServAuthManager();
		$socNetServices = $authManager->getActiveAuthServices(array());

		return !empty($socNetServices[\CSocServGoogleOAuth::ID]);
	}


	/**
	 * Return link for authorize user in external service.
	 * @param string $mode
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getUrlForAuthorizeInTokenService($mode = 'modal')
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$socGoogleOAuth = new \CSocServGoogleOAuth($this->userId);
		if($mode === 'opener')
		{
			return $socGoogleOAuth->getUrl(
				'opener',
				'https://www.googleapis.com/auth/drive',
				array('BACKURL' => '#external-auth-ok')
			);
		}

		return $socGoogleOAuth->getUrl('modal', 'https://www.googleapis.com/auth/drive');
	}

	/**
	 * Request and store access token (self::accessToken) for self::userId
	 * @return $this
	 */
	public function queryAccessToken()
	{
		if(!Loader::includeModule('socialservices'))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_NOT_INSTALLED_SOCSERV'), self::ERROR_NOT_INSTALLED_SOCSERV)));
			return false;
		}

		$socGoogleOAuth = new \CSocServGoogleOAuth($this->userId);
		//this bug. SocServ fill entityOAuth in method getUrl.....
		$googleOAuthUrl = $socGoogleOAuth->getUrl('modal', 'https://www.googleapis.com/auth/drive');
		$this->accessToken = $socGoogleOAuth->getStorageToken();

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
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$googleMimeType = $this->getInternalMimeTypeByExtension(getFileExtension($fileData->getName()));
		$fileName = getFileNameWithoutExtension($fileData->getName());
		$fileName = $this->convertToUtf8($fileName);

		if(!$googleMimeType)
		{
			$this->errorCollection->add(array(
				new Error("Unsupported file format with name {$fileData->getName()}", self::ERROR_UNSUPPORTED_FILE_FORMAT)
			));
			return null;
		}

		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$postFields = "{\"title\":\"{$fileName}\",\"mimeType\":\"{$googleMimeType}\"}";
		if($http->post(self::API_URL_V2 . '/files', $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_CREATE_BLANK)
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

		if(empty($finalOutput['id']) || empty($finalOutput['alternateLink']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find id or alternateLink in response from Google.', self::ERROR_COULD_NOT_FIND_ID)
			));
			return null;
		}

		$fileData->setLinkInService($finalOutput['alternateLink']);
		$fileData->setId($finalOutput['id']);

		//last signed user must delete file from google drive
		$this->insertPermission($fileData);


		return $fileData;
	}

	/**
	 * Create file in cloud service by upload from us server.
	 * Necessary set shared rights on file for common work.
	 *
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function createFile(FileData $fileData)
	{
		$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
		if(!$newFile)
		{
			//retry upload, but not convert content
			if($lastStatus == '500')
			{
				$fileData->setNeedConvert(false);
				$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
			}
		}
		if(!$newFile)
		{
			return null;
		}
		//last signed user must delete file from google drive
		$this->insertPermission($newFile);

		return $newFile;
	}

	/**
	 * @param FileData $fileData
	 * @param string $lastStatus
	 * @param array $fileMetadata
	 * @return FileData|null
	 */
	protected function createByResumableUpload(FileData $fileData, &$lastStatus, &$fileMetadata)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'src', 'mimeType',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		if(!$fileData->getSize())
		{
			$fileData->setSize(filesize($fileData->getSrc()));
		}
		$chunkSize = 40 * 256 * 1024; // Chunk size restriction: All chunks must be a multiple of 256 KB (256 x 1024 bytes) in size except for the final chunk that completes the upload
		$locationForUpload = $this->getLocationForResumableUpload($fileData);
		if(!$locationForUpload)
		{
			return null;
		}

		$lastResponseCode = false;
		$fileMetadata = null;
		$lastRange = false;
		$transactionCounter = 0;
		$doExponentialBackoff = false;
		$exponentialBackoffCounter = 0;
		$response = array();
		while ($lastResponseCode === false || $lastResponseCode == '308')
		{
			$transactionCounter++;

			if ($doExponentialBackoff)
			{
				$sleepFor = pow(2, $exponentialBackoffCounter);
				sleep($sleepFor);
				usleep(rand(0, 1000));
				$exponentialBackoffCounter++;
				if ($exponentialBackoffCounter > 5)
				{
					$lastStatus = $response['code'];
					$this->errorCollection->add(array(
						new Error("Could not upload part (Exponential back off) ({$lastStatus})", self::ERROR_HTTP_RESUMABLE_UPLOAD)
					));

					return null;
				}
			}

			// determining what range is next
			$rangeStart = 0;
			$rangeEnd   = min($chunkSize, $fileData->getSize ()- 1);
			if ($lastRange !== false)
			{
				$lastRange  = explode('-', $lastRange);
				$rangeStart = (int)$lastRange[1] + 1;
				$rangeEnd   = min($rangeStart + $chunkSize, $fileData->getSize ()- 1);
			}

			$http = new HttpClient(array(
				'socketTimeout' => 10,
				'streamTimeout' => 30,
				'version' => HttpClient::HTTP_1_1,
			));
			$http->setHeader('Authorization', "Bearer {$accessToken}");
			$http->setHeader('Content-Length', (string)($rangeEnd - $rangeStart + 1));
			$http->setHeader('Content-Type', $fileData->getMimeType());
			$http->setHeader('Content-Range', "bytes {$rangeStart}-{$rangeEnd}/{$fileData->getSize()}");

			$toSendContent = file_get_contents($fileData->getSrc(), false, null, $rangeStart, ($rangeEnd - $rangeStart + 1));
			if($http->query('PUT', $locationForUpload, $toSendContent))
			{
				$response['code'] = $http->getStatus();
				$response['headers']['range'] = $http->getHeaders()->get('Range');
			}

			$doExponentialBackoff = false;
			if (isset($response['code']))
			{
				// checking for expired credentials
				if ($response['code'] == "401")
				{ // todo: make sure that we also got an invalid credential response
					//$access_token       = get_access_token(true);
					$lastResponseCode = false;
				}
				else if ($response['code'] == "308")
				{
					$lastResponseCode = $response['code'];
					$lastRange = $response['headers']['range'];
					// todo: verify x-range-md5 header to be sure
					$exponentialBackoffCounter = 0;
				}
				else if ($response['code'] == "503")
				{ // Google's letting us know we should retry
					$doExponentialBackoff = true;
					$lastResponseCode     = false;
				}
				else
				{
					if ($response['code'] == "200")
					{ // we are done!
						$lastResponseCode = $response['code'];
					}
					else
					{
						$lastStatus = $response['code'];
						$this->errorCollection->add(array(
							new Error("Could not upload part ({$lastStatus})", self::ERROR_HTTP_RESUMABLE_UPLOAD)
						));

						return null;
					}
				}
			}
			else
			{
				$doExponentialBackoff = true;
				$lastResponseCode     = false;
			}
		}

		if ($lastResponseCode != "200")
		{
			$lastStatus = $response['code'];
			$this->errorCollection->add(array(
				new Error("Could not upload final part ({$lastStatus})", self::ERROR_HTTP_RESUMABLE_UPLOAD)
			));

			return null;
		}

		$fileMetadata = null;
		if(isset($http))
		{
			$fileMetadata = Json::decode($http->getResult());
		}
		if($fileMetadata === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		$fileData->setLinkInService($fileMetadata['alternateLink']);
		$fileData->setId($fileMetadata['id']);

		return $fileData;
	}

	protected  function insertPermission(FileData $fileData)
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
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$postFields = "{\"role\":\"writer\", \"type\":\"anyone\", \"withLink\":true, \"value\": null}";
		if($http->post(self::API_URL_V2 . "/files/{$fileData->getId()}/permissions", $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_INSERT_PERMISSION)
			));
			return false;
		}

		if(!$this->checkHttpResponse($http))
		{
			return false;
		}

		return true;
	}

	protected function getLocationForResumableUpload(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'name', 'mimeType', 'size',
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
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");
		$http->setHeader('X-Upload-Content-Type', $fileData->getMimeType());
		$http->setHeader('X-Upload-Content-Length', $fileData->getSize());

		$postFields = "{\"title\":\"{$fileName}\"}";
		if($http->post('https://www.googleapis.com/upload/drive/v2/files?uploadType=resumable&convert=' . ($fileData->isNeedConvert()? 'true':'false'), $postFields) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_GET_LOCATION_FOR_UPLOAD)
			));
			return null;
		}

		if(!$this->checkHttpResponse($http))
		{
			return null;
		}

		return $http->getHeaders()->get('Location');
	}

	/**
	 * Download file from cloud service by FileData::id, put contents in FileData::src
	 * @param FileData $fileData
	 * @return FileData|null
	 */
	public function downloadFile(FileData $fileData)
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id', 'mimeType', 'src',
		)))
		{
			return null;
		}

		$accessToken = $this->getAccessToken();

		$fileMetaData = $this->getFileMetadataInternal($fileData);
		if($fileMetaData === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}
		$link = $this->getDownloadUrl($fileData, $fileMetaData);
		if(!$link)
		{
			$this->errorCollection->add(array(
				new Error('Could not get link for download', self::ERROR_BAD_JSON)
			));
			return null;
		}

		@set_time_limit(0);
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->download($link, $fileData->getSrc()) === false)
		{
			$errorString = implode('; ', array_keys($http->getError()));
			$this->errorCollection->add(array(
				new Error($errorString, self::ERROR_HTTP_DOWNLOAD_FILE)
			));
			return null;
		}
		//$file['title'] = BaseComponent::convertFromUtf8($file['title']);
		$this->recoverExtensionInName($fileMetaData['title'], $fileData->getMimeType());
		$fileData->setName($fileMetaData['title']);

		return $fileData;
	}

	protected function getDownloadUrl(FileData $fileData, $fileMetaData = array())
	{
		if(!$this->checkRequiredInputParams($fileData->toArray(), array(
			'id',
		)))
		{
			return null;
		}
		if(!$fileMetaData)
		{
			$fileMetaData = $this->getFileMetadataInternal($fileData);
		}
		if(!$fileMetaData)
		{
			return null;
		}
		if(!empty($fileMetaData['downloadUrl']))
		{
			return self::API_URL_V2 . "/files/{$fileData->getId()}?alt=media";
		}
		if(empty($fileMetaData['exportLinks']))
		{
			return null;
		}
		$links = $fileMetaData['exportLinks'];

		$mimeType = TypeFile::getMimeTypeByFilename($fileMetaData['title']);
		if($mimeType && isset($links[$mimeType]))
		{
			return $links[$mimeType];
		}

		if(isset($links['application/vnd.openxmlformats-officedocument.wordprocessingml.document']))
		{
			return $links['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		}
		if(isset($links['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']))
		{
			return $links['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
		}
		if(isset($links['image/png']))
		{
			return $links['image/png'];
		}
		if(isset($links['application/vnd.openxmlformats-officedocument.presentationml.presentation']))
		{
			return $links['application/vnd.openxmlformats-officedocument.presentationml.presentation'];
		}
		return null;
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
			'id', 'mimeType', 'src',
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
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		$endRange = $startRange + $chunkSize - 1;
		$http->setHeader('Range', "bytes={$startRange}-{$endRange}");

		if($http->download($this->getDownloadUrl($fileData), $fileData->getSrc()) === false)
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
	 * Delete file from cloud service by FileData::id
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
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->query('DELETE', self::API_URL_V2 . '/files/' . $fileData->getId()) === false)
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
		$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
		if(!$newFile)
		{
			//retry upload, but not convert content
			if($lastStatus == '500')
			{
				$fileData->setNeedConvert(false);
				$newFile = $this->createByResumableUpload($fileData, $lastStatus, $metadata);
			}
		}
		if($newFile === null)
		{
			$this->errorCollection->add(array(
				new Error(Loc::getMessage('DISK_GOOGLE_HANDLER_ERROR_COULD_NOT_VIEW_FILE'), self::ERROR_COULD_NOT_VIEW_FILE)
			));
			return null;
		}
		return array(
			'id' => $newFile->getId(),
			'viewUrl' => $metadata['embedLink'],
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
		$http->setHeader('Authorization', "Bearer {$this->getAccessToken()}");

		if($http->get(self::API_URL_V2 . "/files?q='{$folderId}'+in+parents") === false)
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
		if(!isset($items['items']))
		{
			$this->errorCollection->add(array(
				new Error('Could not find items in response', self::ERROR_HTTP_LIST_FOLDER)
			));
			return null;
		}

		$reformatItems = array();
		foreach($items['items'] as $item)
		{
			$isFolder = $item['mimeType'] === 'application/vnd.google-apps.folder';
			$dateTime = new \DateTime($item['modifiedDate']);
			$reformatItems[$item['id']] = array(
				'id' => $item['id'],
				'name' => $item['title'],
				'type' => $isFolder? 'folder' : 'file',

				'size' => $isFolder? '' : \CFile::formatSize($item['fileSize']),
				'sizeInt' => $isFolder? '' : $item['fileSize'],
				'modifyBy' => '',
				'modifyDate' => $dateTime->format('d.m.Y'),
				'modifyDateInt' => $dateTime->getTimestamp(),
				'provider' => static::getCode(),
			);

			if(!$isFolder && empty($item['fileSize']))
			{
				//Google.Drive doesn't show size of google documents. We should export docs
				$reformatItems[$item['id']]['size'] = $reformatItems[$item['id']]['sizeInt'] = '';
			}

			if(!$isFolder)
			{
				$reformatItems[$item['id']]['ext'] = getFileExtension($item['title']);
			}
		}
		unset($item);

		return $reformatItems;
	}

	protected function checkHttpResponse(HttpClient $http)
	{
		$status = (int)$http->getStatus();
		if($status === 401)
		{
			$this->errorCollection->add(array(
				new Error('Invalid credentials (401)', self::ERROR_CODE_INVALID_CREDENTIALS)
			));
		}
		elseif($status === 403)
		{
			$headers = $http->getHeaders();
			$response = $http->getResult();
			$errorMessage = '';
			if($response && is_string($response))
			{
				$jsonResponse = Json::decode($response);
				if(isset($jsonResponse['error']['message']))
				{
					$errorMessage = $jsonResponse['error']['message'];
				}
				unset($jsonResponse, $response);
			}

			$headerAuthenticate = $headers->get('WWW-Authenticate');
			if(is_string($headerAuthenticate) && strpos($headerAuthenticate, 'insufficient') !== false)
			{
				$this->errorCollection->add(array(
					new Error('Insufficient scope (403)', self::ERROR_CODE_INSUFFICIENT_SCOPE)
				));
				return false;
			}
			elseif(strpos($errorMessage, 'The authenticated user has not installed the app with client') !== false)
			{
				$this->errorCollection->add(array(
					new Error('The authenticated user has not installed the app (403)', self::ERROR_CODE_NOT_INSTALLED_APP)
				));
			}
			elseif(strpos($errorMessage, 'The authenticated user has not granted the app') !== false)
			{
				$this->errorCollection->add(array(
					new Error('The authenticated user has not granted the app (403)', self::ERROR_CODE_NOT_GRANTED_APP)
				));
			}
			elseif(strpos($errorMessage, 'Invalid accessLevel') !== false)
			{
				$this->errorCollection->add(array(
					new Error('Invalid accessLevel (403)', self::ERROR_CODE_INVALID_ACCESS_LEVEL)
				));
			}
			elseif(strpos($errorMessage, 'is not properly configured as a Google Drive app') !== false)
			{
				$this->errorCollection->add(array(
					new Error('The app does not exist or is not properly configured as a Google Drive app (403)', self::ERROR_CODE_APP_NOT_CONFIGURED)
				));
			}
			elseif(strpos($errorMessage, 'is blacklisted') !== false)
			{
				$this->errorCollection->add(array(
					new Error('The app is blacklisted as a Google Drive app. (403)', self::ERROR_CODE_APP_IN_BLACKLIST)
				));
			}
			elseif($errorMessage)
			{
				$this->errorCollection->add(array(
					new Error($errorMessage, self::ERROR_CODE_UNKNOWN)
				));
			}
		}

		if($this->errorCollection->hasErrors())
		{
			return false;
		}

		return parent::checkHttpResponse($http);
	}

	/**
	 * Gets a file's metadata by ID
	 * @param FileData $fileData
	 * @return array|null
	 */
	private function getFileMetadataInternal(FileData $fileData)
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
		$http->setHeader('Content-Type', 'application/json; charset=UTF-8');
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->get(self::API_URL_V2 . '/files/' . $fileData->getId()) === false)
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

		$file = Json::decode($http->getResult());
		if($file === null)
		{
			$this->errorCollection->add(array(
				new Error('Could not decode response as json', self::ERROR_BAD_JSON)
			));
			return null;
		}

		return $file;
	}

	private function getFileSizeInternal($downloadUrl)
	{
		$accessToken = $this->getAccessToken();
		$http = new HttpClient(array(
			'socketTimeout' => 10,
			'streamTimeout' => 30,
			'version' => HttpClient::HTTP_1_1,
		));
		$http->setHeader('Authorization', "Bearer {$accessToken}");

		if($http->query('HEAD', $downloadUrl) === false)
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

		return $http->getHeaders()->get('Content-Length');
	}

	/**
	 * Gets a file's metadata by ID.
	 *
	 * @param FileData $fileData
	 * @return array|null Describes file (id, title, size, mimeType)
	 */
	public function getFileMetadata(FileData $fileData)
	{
		$metaData = $this->getFileMetadataInternal($fileData);
		if(!$metaData)
		{
			return null;
		}
		if(empty($metaData['fileSize']))
		{
			$link = $this->getDownloadUrl($fileData, $metaData);
			if(!$link)
			{
				return null;
			}
			$metaData['fileSize'] = $this->getFileSizeInternal($link);
		}

		return array(
			'id' => $metaData['id'],
			'name' => $metaData['title'],
			'size' => $metaData['fileSize'],
			'mimeType' => $metaData['mimeType'],
			'etag' => $metaData['etag'],
		);
	}

	private function getInternalMimeTypeByExtension($ext)
	{
		$ext = trim($ext, '.');
		$googleMimeTypes = array(
			'docx' => 'application/vnd.google-apps.document',
			'xlsx' => 'application/vnd.google-apps.spreadsheet',
			'pptx' => 'application/vnd.google-apps.presentation',
		);

		return isset($googleMimeTypes[$ext])? $googleMimeTypes[$ext] : null;
	}
}