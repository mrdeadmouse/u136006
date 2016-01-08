<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;
use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\Widget\Data\DealStageHistory;
use Bitrix\Crm\PhaseSemantics;

class DealFunnelWidget extends Widget
{
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
		$totals = array();
		$stages = \CCrmStatus::GetStatusList('DEAL_STAGE');
		foreach($stages as $k => $v)
		{
			$semanticID = \CCrmDeal::GetSemanticID($k);
			if($semanticID === PhaseSemantics::FAILURE)
			{
				continue;
			}
			$totals[$k] = array('ID' => $k, 'NAME' => $v, 'TOTAL' => 0);
		}

		$source = new DealStageHistory(array(), $this->userID, $this->enablePermissionCheck);

		//CREATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::CREATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);
		//MODIFICATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::MODIFICATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);

		//FINALIZATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::FINALIZATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);

		$items = array();
		foreach($totals as $total)
		{
			if($total['TOTAL'] > 0)
			{
				$items[] = $total;
			}
		}
		return array('items' => $items, 'valueField' => 'TOTAL', 'titleField' => 'NAME');
	}
	/**
	* @return boolean
	*/
	protected function prepareTotals(array $values, array &$totals)
	{
		foreach($values as $value)
		{
			$stageID = isset($value['STAGE_ID']) ? $value['STAGE_ID'] : '';

			$qty = isset($value['QTY']) ? (int)$value['QTY'] : 0;
			if(isset($totals[$stageID]))
			{
				$totals[$stageID]['TOTAL'] += $qty;
			}
		}
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

		$stages = \CCrmStatus::GetStatusList('DEAL_STAGE');
		foreach($data['items'] as &$item)
		{
			$stageID = isset($item['ID']) ? $item['ID'] : '';
			if($stageID !== '' && isset($stages[$stageID]))
			{
				$item['NAME'] = $stages[$stageID];
			}
		}
		unset($item);
		return $data;
	}
}