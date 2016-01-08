<?php
namespace Bitrix\Crm\VCard;
use Bitrix\Main;
class VCardElementAttribute
{
	protected $name = '';
	protected $value = '';
	protected $rawParams = '';
	protected $params = null;

	public function __construct($name, $value, array $params = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->params = $params;
	}

	/**
	* @return string
	*/
	public function __toString()
	{
		$params = $this->getParams();
		if(empty($params))
		{
			return '{ name: '.$this->name.', value: '.$this->value.' }';
		}

		$paramStrings = array();
		foreach($params as $k => $v)
		{
			$paramStrings[] = '{ name: '.$k.', values: [ '.implode(', ', $v).' ] }';
		}
		return '{ name: '.$this->name.', value: '.$this->value.', params: [ '.implode(', ', $paramStrings).' ] }';
	}

	/**
	* @return string
	*/
	public function getName()
	{
		return $this->name;
	}

	/**
	* @return string
	*/
	public function getValue()
	{
		return $this->value;
	}

	/**
	* @return array
	*/
	public function getParams()
	{
		if($this->params === null)
		{
			$this->params = array();
			if($this->rawParams !== '')
			{
				$params = explode(';', $this->rawParams);
				foreach($params as $param)
				{
					$pos = stripos($param, '=');
					if($pos === false)
					{
						continue;
					}

					$name = trim(preg_replace(array("/^\"/", "/\"$/"), '', substr($param, 0, $pos)));
					$this->params[$name] = preg_split("/\s*\,\s*/", substr($param, $pos + 1), -1, PREG_SPLIT_NO_EMPTY);
				}
			}
		}
		return $this->params;
	}

	/**
	* @return array
	*/
	public function getParamValues($name)
	{
		$ownParams = $this->getParams();
		return isset($ownParams[$name]) ? $ownParams[$name] : array();
	}

	/**
	* @return string
	*/
	public function getFirstParamValue($name, $default = null)
	{
		$ownParams = $this->getParams();
		return isset($ownParams[$name]) && !empty($ownParams[$name]) ? $ownParams[$name][0] : $default;
	}

	public function hasParams(array $params)
	{
		$ownParams = $this->getParams();
		if(empty($ownParams))
		{
			return empty($params);
		}

		/**
		* @var $v array
		*/
		foreach($params as $k => $v)
		{
			if(!is_array($v))
			{
				$v = array($v);
			}

			if(!isset($ownParams[$k]) || count(array_diff($v, $ownParams[$k])) > 1)
			{
				return false;
			}
		}

		return true;
	}

	public static function isValidAttributeString($str)
	{
		return preg_match("/[^\\\\]:/", $str) === 1;
	}

	/**
	* @return VCardElementAttribute|null
	*/
	public static function parseFromString($str)
	{
		if(preg_match("/[^\\\\]:/", $str, $match, PREG_OFFSET_CAPTURE) !== 1)
		{
			return null;
		}

		$pos = $match[0][1] + 1;
		$name = trim(substr($str, 0, $pos));
		$value = trim(substr($str, $pos + 1));
		$params = '';
		$pos = stripos($name, ';');

		if($pos !== false)
		{
			$params = trim(substr($name, $pos + 1));
			$name = trim(substr($name, 0, $pos));
		}

		$item = new VCardElementAttribute($name, $value);
		if($params !== '')
		{
			$item->rawParams = $params;
		}
		return $item;
	}
}