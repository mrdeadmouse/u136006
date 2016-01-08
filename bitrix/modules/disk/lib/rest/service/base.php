<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Rest\RestException;

abstract class Base implements IErrorable
{
	const ERROR_REQUIRED_PARAMETER = 'DISK_BASE_SERVICE_22001';

	/** @var ErrorCollection */
	protected $errorCollection;
	/** @var string */
	protected $methodName;
	/** @var array */
	protected $methodParams;
	/** @var array */
	protected $params;
	/** @var string */
	protected $start;
	/** @var \CRestServer */
	protected $restServer;
	/** @var int */
	protected $userId;

	/**
	 * Base constructor.
	 * @param   string      $methodName Method name which invokes REST.
	 * @param array         $params     Input params.
	 * @param        string $start      Start position for listing items.
	 * @param \CRestServer  $restServer
	 */
	public function __construct($methodName, array $params, $start, \CRestServer $restServer)
	{
		$this->methodName = $methodName;
		$this->params = $params;
		$this->start = $start;
		$this->restServer = $restServer;
		$this->errorCollection = new ErrorCollection;

		$this->init();
	}

	/**
	 * Initialize service.
	 */
	protected function init()
	{
		global $USER;
		$this->userId = $USER->getId();
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	private function bindParams($requestParams)
	{
		$args = $methodParams= array();

		$method = new \ReflectionMethod($this, $this->methodName);
		foreach($method->getParameters() as $param)
		{
			$name = $param->getName();
			if(isset($requestParams[$name]) || array_key_exists($name, $requestParams))
			{
				if($param->isArray())
				{
					$args[] = $methodParams[$name] = (array)$requestParams[$name];
				}
				else
				{
					$args[] = $methodParams[$name] = $requestParams[$name];
				}
				unset($requestParams[$name]);
			}
			elseif($param->isDefaultValueAvailable())
			{
				$args[] = $methodParams[$name] = $param->getDefaultValue();
			}
			else
			{
				throw new RestException("Invalid value of parameter { {$name} }.", RestException::ERROR_ARGUMENT);
			}
		}
		unset($param);

		$this->methodParams = $methodParams;

		return $args;
	}

	/**
	 * Executes method.
	 * @return mixed
	 * @throws RestException
	 */
	public function processMethodRequest()
	{
		return call_user_func_array(array($this, $this->methodName), $this->bindParams($this->params));
	}

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && strlen($inputParams[$item]))))
			{
				$this->errorCollection->add(array(new Error("Error: required parameter {$item}", self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}