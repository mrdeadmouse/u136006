<?php
namespace Bitrix\Crm\Widget;
use Bitrix\Main;
use Bitrix\Crm\Widget\Data\DataSourceFactory;
use Bitrix\Crm\MessageHelper;

class DealRatingWidget extends Widget
{
	/** @var array[DealWidgetConfig] */
	private $configs = null;
	/** @var string */
	private $groupField = '';
	private $nomineeID = 0;

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

		if(isset($settings['nominee']))
		{
			$this->nomineeID = (int)$settings['nominee'];
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
	/**
	* @return array
	*/
	protected function preparePosition(array $data, $index, $idField, $legendField, DealWidgetConfig $config)
	{
		$item = $data[$index];
		$position = $index + 1;
		$ID = isset($item[$idField]) ? (int)$item[$idField] : 0;

		$legend = isset($item[$legendField]) ? $item[$legendField] : '';
		$legendType = 'text';
		$format = $config->getFomatParams();
		if(isset($format['enableDecimals']) && $format['enableDecimals'] == 'N')
		{
			$legend = round($legend, 0);
		}

		if(isset($format['isCurrency']) && $format['isCurrency'] == 'Y')
		{
			$legend = \CCrmCurrency::MoneyToString($legend, \CCrmCurrency::GetAccountCurrencyID());
			$legendType = 'html';
		}

		return array('id' => $ID, 'value' => $position, 'legend' => $legend, 'legendType' => $legendType);
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
	public function prepareData()
	{
		$items = array();
		foreach($this->configs as $config)
		{
			/** @var DealWidgetConfig $config */
			$name = $config->getName();
			if($name === '')
			{
				$name = strval(count($items) + 1);
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
			$groupField = $this->groupField !== '' ? $this->groupField : $config->getGroupField();
			$values = $source->getList(
				array(
					'filter' => $this->filter,
					'select' => array(array('name' => $selectField, 'aggregate' => $aggregate)),
					'group' => $groupField,
					'sort' => array(array('name' => $selectField, 'order' => 'desc'))
				)
			);

			$positions = array();
			$key = "{$groupField}_ID";
			$qty = count($values);
			if($this->nomineeID > 0)
			{
				for($i = 0; $i < $qty; $i++)
				{
					$value = $values[$i];
					$curID = isset($value[$key]) ? (int)$value[$key] : 0;
					if($curID !== $this->nomineeID)
					{
						continue;
					}

					if($i > 0)
					{
						$positions[] = $this->preparePosition($values, $i - 1, $key, $selectField, $config);
						$positions[] = $this->preparePosition($values, $i, $key, $selectField, $config);
						if($qty > ($i + 1))
						{
							$positions[] = $this->preparePosition($values, $i + 1, $key, $selectField, $config);
						}
					}
					else
					{
						$positions[] = $this->preparePosition($values, 0, $key, $selectField, $config);
						if($qty > 1)
						{
							$positions[] = $this->preparePosition($values, 1, $key, $selectField, $config);
							if($qty > 2)
							{
								$positions[] = $this->preparePosition($values, 2, $key, $selectField, $config);
							}
						}
					}
					break;
				}
			}

			if($this->nomineeID <= 0 || empty($positions))
			{
				$qty = min($qty, 3);
				for($i = 0; $i < $qty; $i++)
				{
					$positions[] = $this->preparePosition($values, $i, $key, $selectField, $config);
				}
			}

			$items[] = array(
				'name' => $name,
				'title' => $title,
				'nomineeId' => $this->nomineeID,
				'positions' => $positions
			);
		}

		return array('items' => $items);
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
			$config = $this->findConfigByName(isset($item['name']) ? $item['name'] : '');
			if(!$config)
			{
				continue;
			}

			$item['title'] = $config->getTitle();
			if(!(isset($item['positions']) && is_array($item['positions'])))
			{
				continue;
			}

			$format = $config->getFomatParams();
			foreach($item['positions'] as &$position)
			{
				$legend = isset($position['legend']) ? (double)$position['legend'] : 0.0;
				$legendType = 'text';
				if(isset($format['enableDecimals']) && $format['enableDecimals'] == 'N')
				{
					$legend = round($legend, 0);
				}
				if(isset($format['isCurrency']) && $format['isCurrency'] == 'Y')
				{
					$legend = \CCrmCurrency::MoneyToString($legend, \CCrmCurrency::GetAccountCurrencyID());
					$legendType = 'html';
				}
				$position['legend'] = $legend;
				$position['legendType'] = $legendType;
			}
			unset($position);
		}
		unset($item);
		return $data;
	}
}