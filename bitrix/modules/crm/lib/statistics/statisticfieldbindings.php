<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;

class StatisticFieldBindings
{
	private $typeName = '';
	private $bindings = null;

	public function __construct($typeName)
	{
		if(!is_string($typeName))
		{
			throw new Main\ArgumentTypeException('typeName', 'string');
		}

		if($typeName === '')
		{
			throw new Main\ArgumentNullException('typeName');
		}

		$this->typeName = strtolower($typeName);
	}
	/**
	* @return void
	*/
	protected function load()
	{
		if($this->bindings !== null)
		{
			return;
		}

		$s = Main\Config\Option::get('crm', $this->typeName);
		if(is_string($s) && $s !== '')
		{
			$ary = unserialize($s);
			if(is_array($ary))
			{
				$this->bindings = &$ary;
				unset($ary);
			}
		}
		if(!is_array($this->bindings))
		{
			$this->bindings = array();
		}
	}
	/**
	* @return void
	*/
	public function save()
	{
		if($this->bindings === null)
		{
			return;
		}

		if(!empty($this->bindings))
		{
			Main\Config\Option::set('crm', $this->typeName, serialize($this->bindings));
		}
		else
		{
			Main\Config\Option::delete('crm', array('name' => $this->typeName));
		}
	}
	/**
	* @return void
	*/
	public function clear()
	{
		$this->bindings = array();
	}
	/**
	* @return array
	*/
	public function getAll()
	{
		$this->load();
		return $this->bindings;
	}
	/**
	* @return string
	*/
	public function get($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		if($name === '')
		{
			throw new Main\ArgumentNullException('name');
		}

		$this->load();
		return isset($this->bindings[$name]) ? $this->bindings[$name] : '';
	}
	/**
	* @return boolean
	*/
	public function set($name, $value)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		if($name === '')
		{
			throw new Main\ArgumentNullException('name');
		}

		if(!is_string($value))
		{
			throw new Main\ArgumentTypeException('value', 'string');
		}

		if($value === '')
		{
			throw new Main\ArgumentNullException('value');
		}

		$this->load();


		if(isset($this->bindings[$name]) && $this->bindings[$name] === $value)
		{
			return false;
		}

		$this->bindings[$name] = $value;
		return true;
	}
	/**
	* @return boolean
	*/
	public function remove($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		if($name === '')
		{
			throw new Main\ArgumentNullException('name');
		}

		if(!isset($this->bindings[$name]))
		{
			return false;
		}

		unset($this->bindings[$name]);
		return true;
	}
}