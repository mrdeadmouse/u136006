<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\History\HistoryEntryType;


class DealStageHistory extends DealDataSource
{
	const TYPE_NAME = 'DEAL_STAGE_HISTORY';
	const GROUP_BY_STAGE = 'STAGE';
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/** @return array */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$permissionSql = '';
		if($this->enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return array();
			}
		}

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('STAGE_ID');
		$query->addSelect('QTY');
		$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(DISTINCT OWNER_ID)'));

		$typeID = $filter->getExtraParam('typeID', HistoryEntryType::UNDEFINED);
		if($typeID !== HistoryEntryType::UNDEFINED)
		{
			$query->addFilter('=TYPE_ID', $typeID);
			if($typeID === HistoryEntryType::CREATION)
			{
				$query->addFilter('>=START_DATE', $periodStartDate);
				$query->addFilter('<=START_DATE', $periodEndDate);
			}
			elseif($typeID === HistoryEntryType::MODIFICATION)
			{
				$query->addFilter('>=CREATED_TIME', $periodStartDate);
				$query->addFilter('<=CREATED_TIME', $periodEndDate);
			}
			elseif($typeID === HistoryEntryType::FINALIZATION)
			{
				$query->addFilter('>=END_DATE', $periodStartDate);
				$query->addFilter('<=END_DATE', $periodEndDate);
			}
		}

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$query->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$query->addGroup('STAGE_ID');
		$dbResult = $query->exec();
		//Trace('sql', Query::getLastQuery(), 1);
		$result = array();
		while($ary = $dbResult->fetch())
		{
			$result[] = $ary;
		}
		return $result;
	}
}