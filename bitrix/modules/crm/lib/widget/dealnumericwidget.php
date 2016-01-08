<?php
namespace Bitrix\Crm\Widget;
use Bitrix\Crm\Widget\Data\DataSource;
use Bitrix\Crm\Widget\Data\DataSourceFactory;
use Bitrix\Crm\Widget\Data\ExpressionDataSource;

class DealNumericWidget extends Widget
{
	/** @var array[DealWidgetConfig] */
	private $configs = null;
	public function __construct(array $settings, Filter $filter, $userID = 0, $enablePermissionCheck = true)
	{
		parent::__construct($settings, $filter, $userID, $enablePermissionCheck);

		$this->configs = array();
		$configs = $this->getSettingArray('configs', array());
		foreach($configs as $config)
		{
			$this->configs[] = new DealWidgetConfig($config);
		}
	}
	/**
	* @return array
	*/
	public function prepareData()
	{
		$items = array();
		$expressions = array();
		$qty = count($this->configs);
		for($i = 0; $i < $qty; $i++)
		{
			/** @var DealWidgetConfig $config */
			$config = $this->configs[$i];

			$name = $config->getName();
			if($name === '')
			{
				$name = strval($i + 1);
			}

			$title = $config->getTitle();
			if($title === '')
			{
				$title = $name;
			}

			$items[$name] = array('name' => $name, 'title' => $title, 'value' => 0);

			$source = DataSourceFactory::create(
				$config->getDataSourceSettings(),
				$this->userID,
				$this->enablePermissionCheck
			);


			$params = array('name' => $name, 'config' => $config, 'source' => $source);
			//Skip expressions. They will be processed at the end of this function.
			if($source instanceof ExpressionDataSource)
			{
				$expressions[] = $params;
				continue;
			}
			$this->prepareItem($params, $items);
		}

		foreach($expressions as $params)
		{
			$this->prepareItem($params, $items);
		}

		return array('items' => array_values($items));
	}
	/**
	* @return void
	*/
	protected function prepareItem(array $params, array &$result)
	{
		/** @var string $name */
		$name = $params['name'];
		/** @var DealWidgetConfig $config */
		$config = $params['config'];
		/** @var DataSource $source */
		$source = $params['source'];

		$selectField = $config->getSelectField();
		if($selectField === '')
		{
			$selectField = $name;
		}

		$this->filter->setExtras($config->getFilterParams());
		$value = (double)$source->getFirstValue(
			array(
				'filter' => $this->filter,
				'select' => array(array('name' => $selectField, 'aggregate' => $config->getAggregate())),
				'result' => $result
			),
			$selectField,
			0.0
		);

		$format = $config->getFomatParams();
		if(isset($format['enableDecimals']) && $format['enableDecimals'] == 'N')
		{
			$value = round($value, 0);
		}

		$result[$name]['value'] = $value;
		if(isset($format['isCurrency']) && $format['isCurrency'] === 'Y')
		{
			$result[$name]['html'] = \CCrmCurrency::MoneyToString($value, \CCrmCurrency::GetAccountCurrencyID());
		}

		$detailsPageUrl = $source->getDetailsPageUrl(array('filter' => $this->filter));
		if($detailsPageUrl !== '')
		{
			$result[$name]['url'] = $detailsPageUrl;
		}

		$displayParams = $config->getDisplayParams();
		if(!empty($displayParams))
		{
			$result[$name]['display'] = $displayParams;
		}
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
			$config = $this->findConfigByName(isset($item['name']) ? $item['name'] : '');
			if(!$config)
			{
				continue;
			}

			$item['title'] = $config->getTitle();
			$value = isset($item['value']) ? (double)$item['value'] : 0.0;
			$format = $config->getFomatParams();
			if(isset($format['enableDecimals']) && $format['enableDecimals'] == 'N')
			{
				$value = round($value, 0);
			}
			$item['value'] = $value;
			if(isset($format['isCurrency']) && $format['isCurrency'] === 'Y')
			{
				$item['html'] = \CCrmCurrency::MoneyToString($value, \CCrmCurrency::GetAccountCurrencyID());
			}
		}
		unset($item);
		return $data;
	}
}