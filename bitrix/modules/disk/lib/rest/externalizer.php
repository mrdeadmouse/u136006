<?php


namespace Bitrix\Disk\Rest;


use Bitrix\Disk;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;

final class Externalizer
{
	/** @var Disk\UrlManager */
	protected $urlManager;
	/** @var string */
	protected $host;
	/** @var \CRestServer */
	private $restServer;
	/** @var Service\Base */
	private $service;

	/**
	 * Constructor of Externalizer.
	 * @param Service\Base $service Service which provides methods for REST.
	 * @param \CRestServer $restServer REST server object.
	 */
	public function __construct(Service\Base $service, \CRestServer $restServer)
	{
		$this->urlManager = Disk\Driver::getInstance()->getUrlManager();
		$this->host = $this->urlManager->getHostUrl();
		$this->restServer = $restServer;
		$this->service = $service;
	}

	/**
	 * Returns data to show in response by REST. If $data contains objects we run converting.
	 * @param mixed $data Data to show in response by REST.
	 * @return array Result.
	 * @throws RestException
	 */
	public function getExternalData($data)
	{
		if(!is_array($data))
		{
			return $this->toArray($data);
		}
		foreach($data as $key => $item)
		{
			$data[$key] = $this->getExternalData($data[$key]);
		}
		unset($item);

		return $data;
	}

	private function convertDateTimeFields($data)
	{
		if($data instanceof DateTime)
		{
			return \CRestUtil::convertDateTime($data);
		}
		if(!is_array($data))
		{
			return $data;
		}
		foreach($data as $key => $item)
		{
			$data[$key] = $this->convertDateTimeFields($data[$key]);
		}
		unset($item);

		return $data;
	}

	private function toArrayFromModel(Disk\Internals\Model $model)
	{
		$entity = null;
		if($model instanceof Disk\Storage)
		{
			$entity = new Entity\Storage;
		}
		elseif($model instanceof Disk\File)
		{
			$entity = new Entity\Folder;
		}
		elseif($model instanceof Disk\Folder)
		{
			$entity = new Entity\File;
		}
		else
		{
			throw new RestException('Unknown object ' . get_class($model));
		}

		$toArray = array_intersect_key($model->toArray(), $entity->getFieldsForShow());
		foreach($entity->getFieldsForMap() as $fieldName => $modifiers)
		{
			if(!isset($toArray[$fieldName]))
			{
				continue;
			}
			$toArray[$fieldName] = call_user_func_array($modifiers['OUT'], array($toArray[$fieldName]));
		}
		unset($fieldName, $modifiers);

		if($model instanceof Disk\File)
		{
			$toArray['DOWNLOAD_URL'] = $this->host . $this->urlManager->getUrlForDownloadFile($model) . '&auth=' . $this->restServer->getAuth();
			if($model->getStorage()->getProxyType() instanceof Disk\ProxyType\RestApp)
			{
				$toArray['DETAIL_URL'] = null;
			}
			else
			{
				$toArray['DETAIL_URL'] = $this->host . $this->urlManager->getPathFileDetail($model);
			}
		}
		elseif($model instanceof Disk\Folder)
		{
			if($model->getStorage()->getProxyType() instanceof Disk\ProxyType\RestApp)
			{
				$toArray['DETAIL_URL'] = null;
			}
			else
			{
				$toArray['DETAIL_URL'] = $this->host . $this->urlManager->getPathInListing($model) . $model->getName();
			}
		}

		return $toArray;
	}

	private function toArray($item)
	{
		if(!is_object($item))
		{
			return $item;
		}
		if($item instanceof Disk\Internals\Model)
		{
			return $this->toArrayFromModel($item);
		}

		throw new RestException('Unknown object ' . get_class($item));
	}

}