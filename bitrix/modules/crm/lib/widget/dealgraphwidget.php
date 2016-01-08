<?php
namespace Bitrix\Crm\Widget;
use Bitrix\Main;
use Bitrix\Crm\Widget\Data\DataSourceFactory;

class DealGraphWidget extends Widget
{
	/** @var array[DealWidgetConfig] */
	private $configs = null;
	/** @var string */
	private $groupField = '';
	/** @var boolean */
	private $enableDataCombining = false;
	private $skipZeros = true;

	public function __construct(array $settings, Filter $filter, $userID = 0, $enablePermissionCheck = true)
	{
		parent::__construct($settings, $filter, $userID, $enablePermissionCheck);

		$this->configs = array();
		$configs = $this->getSettingArray('configs', array());
		foreach($configs as $config)
		{
			$this->configs[] = new DealWidgetConfig($config);
		}

		if(isset($settings['group']) && is_string($settings['group']) && $settings['group'] !== '')
		{
			$this->setGroupField($settings['group']);
		}

		if(isset($settings['combineData']))
		{
			$this->enableDataCombining((bool)$settings['combineData']);
		}

		if(isset($settings['skipZeros']))
		{
			$this->skipZeros((bool)$settings['skipZeros']);
		}
	}
	/** @return string */
	public function getGroupField()
	{
		return $this->groupField;
	}
	public function setGroupField($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		$this->groupField = $name;
	}
	/** @return boolean */
	public function isDataCombiningEnabled()
	{
		return $this->enableDataCombining;
	}
	public function enableDataCombining($enable)
	{
		if(!is_bool($enable))
		{
			throw new Main\ArgumentTypeException('enable', 'boolean');
		}

		$this->enableDataCombining = $enable;
	}
	/** @return boolean */
	public function isSkipZeros()
	{
		return $this->skipZeros;
	}
	/**
	* @return void
	*/
	public function skipZeros($skipZeros)
	{
		if(!is_bool($skipZeros))
		{
			throw new Main\ArgumentTypeException('enable', 'boolean');
		}

		$this->skipZeros = $skipZeros;
	}
	/**
	* @return array
	*/
	public function prepareData()
	{
		$result = array();
		foreach($this->configs as $config)
		{
			/** @var DealWidgetConfig $config */
			$name = $config->getName();
			if($name === '')
			{
				$name = strval(count($result) + 1);
			}

			$title = $config->getTitle();
			if($title === '')
			{
				$title = $name;
			}

			$this->filter->setExtras($config->getFilterParams());
			$source = DataSourceFactory::create(
				$config->getDataSourceSettings(),
				$this->userID,
				$this->enablePermissionCheck
			);

			$selectField = $config->getSelectField();
			$aggregate = $config->getAggregate();
			$groupField = $config->getGroupField();
			if($groupField === '')
			{
				$groupField = $this->groupField;
			}

			$values = $source->getList(
				array(
					'filter' => $this->filter,
					'select' => array(array('name' => $selectField, 'aggregate' => $aggregate)),
					'group' => $groupField
				)
			);

			$item = array(
				'name' => $name,
				'title' => $title,
				'values' => $values,
				'selectField' => $selectField,
				'groupField' => $groupField
			);

			$displayParams = $config->getDisplayParams();
			if(!empty($displayParams))
			{
				$item['display'] = $displayParams;
			}
			$result[] = $item;
		}

		if(!(count($result) > 1 && $this->enableDataCombining && $this->groupField !== ''))
		{
			return $result;
		}

		$merge = array();
		$groupField = $this->groupField;
		$graphs = array();
		foreach($result as $item)
		{
			$name = $item['name'];
			$title = $item['title'];
			$selectField = $item['selectField'];
			$valueKey = strtoupper($name).'_'.$selectField;

			$graph = array(
				'name' => $name,
				'title' => $title,
				'selectField' => $valueKey
			);

			if(isset($item['display']))
			{
				$graph['display'] = $item['display'];
			}

			$graphs[] = $graph;
			$values = $item['values'];
			$addZeroValues = $this->skipZeros;
			foreach($values as $value)
			{
				$key = isset($value[$groupField]) ? $value[$groupField] : '';
				if($key === '')
				{
					continue;
				}

				if(!isset($merge[$key]))
				{
					$merge[$key] = array($groupField => $key);
				}

				if(isset($value[$selectField]) && ($addZeroValues || $value[$selectField] != 0))
				{
					$merge[$key][$valueKey] = $value[$selectField];
				}
			}
		}

		ksort($merge, SORT_STRING);

		return array(
			'items' => array(
				array(
					'graphs' => $graphs,
					'groupField' => $groupField,
					'values' => array_values($merge)
				)
			),
			'dateFormat' => 'YYYY-MM-DD'
		);
	}
	/**
	* @return DealWidgetConfig|null
	*/
	protected function findConfigByName($name)
	{
		if($name === '')
		{
			return null;
		}

		$qty = count($this->configs);
		for($i = 0; $i < $qty; $i++)
		{
			/** @var DealWidgetConfig $config */
			$config = $this->configs[$i];
			if($config->getName() === $name)
			{
				return $config;
			}
		}
		return null;
	}
	/**
	* @return array
	*/
	public function initializeDemoData(array $data)
	{
		if(!(isset($data['items']) && is_array($data['items'])))
		{
			return $data;
		}

		foreach($data['items'] as &$item)
		{

			if(!(isset($item['graphs']) && is_array($item['graphs'])))
			{
				continue;
			}

			foreach($item['graphs'] as &$graph)
			{
				$config = $this->findConfigByName(isset($graph['name']) ? $graph['name'] : '');
				if($config)
				{
					$graph['title'] = $config->getTitle();
				}
			}
			unset($graph);
		}
		unset($item);
		return $data;
	}
}