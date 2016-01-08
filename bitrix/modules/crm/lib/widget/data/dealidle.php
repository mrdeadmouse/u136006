<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\Statistics\Entity\DealActivityStatisticsTable;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;

class DealIdle extends DealDataSource
{
	const TYPE_NAME = 'DEAL_IDLE';
	const GROUP_BY_DATE = 'DATE';
	private static $messagesLoaded = false;
	/**
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
		}

		if($name === '')
		{
			$name = 'COUNT';
		}

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}
		$enableGroupByDate = $group !== '';

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
		$query->addSelect($name);
		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=START_DATE', $periodStartDate);
		$query->addFilter('<=START_DATE', $periodEndDate);

		if($enableGroupByDate)
		{
			$query->addSelect('START_DATE', 'DATE');
			$query->addGroup('START_DATE');
			$query->addOrder('START_DATE', 'ASC');
		}


		$query->registerRuntimeField('',
			new ExpressionField(
				'E1',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::MODIFICATION, '%s', '_h')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E1', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E2',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::FINALIZATION, '%s', '_h')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E2', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E3',
				'(CASE WHEN NOT EXISTS('.self::prepareActivityQuery($periodStartDate, $periodEndDate, '%s', '_a')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E3', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E4',
				'(CASE WHEN NOT EXISTS('.self::prepareInvoiceQuery($periodStartDate, $periodEndDate, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E4', 1);

		$results = array();
		$dbResult = $query->exec();
		while($ary = $dbResult->fetch())
		{
			if($enableGroupByDate)
			{
				/** @var Date $date */
				$date =  $ary['DATE'];
				$ary['DATE'] = $date->format('Y-m-d');
			}
			$results[] = $ary;
		}
		return $results;
	}

	/**
	 * @return Query
	 */
	public static function prepareHistoryQuery($startDate, $endDate, $typeID, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealStageHistoryTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}

	/**
	 * @return Query
	 */
	protected static function prepareActivityQuery($startDate, $endDate, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealActivityStatisticsTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=DEADLINE_DATE', $startDate);
		$query->addFilter('<=DEADLINE_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}

	/**
	 * @return Query
	 */
	protected static function prepareInvoiceQuery($startDate, $endDate, $ownerFieldReference = '%s', $postfix = '')
	{
		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		if($postfix !== '')
		{
			$query->setTableAliasPostfix($postfix);
		}
		$query->addFilter('=IS_LOST', false);
		$query->addFilter('>=CREATED_DATE', $startDate);
		$query->addFilter('<=CREATED_DATE', $endDate);
		$query->addFilter('=OWNER_ID', new SqlExpression($ownerFieldReference));
		if(!(Main\Application::getConnection() instanceof Main\DB\OracleConnection))
		{
			$query->setLimit(1);
		}

		return $query;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'title' => GetMessage('CRM_DEAL_IDLE_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT'),
				'context' => DataContext::ENTITY
			)
		);
	}

	/**
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}

	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('=TYPE_ID', HistoryEntryType::CREATION);
		$query->addFilter('>=START_DATE', $periodStartDate);
		$query->addFilter('<=START_DATE', $periodEndDate);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E1',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::MODIFICATION, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E1', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E2',
				'(CASE WHEN NOT EXISTS('.self::prepareHistoryQuery($periodStartDate, $periodEndDate, HistoryEntryType::FINALIZATION, '%s', '_i')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E2', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E3',
				'(CASE WHEN NOT EXISTS('.self::prepareActivityQuery($periodStartDate, $periodEndDate, '%s')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E3', 1);

		$query->registerRuntimeField('',
			new ExpressionField(
				'E4',
				'(CASE WHEN NOT EXISTS('.self::prepareInvoiceQuery($periodStartDate, $periodEndDate, '%s')->getQuery().') THEN 1 ELSE 0 END)',
				'OWNER_ID'
			)
		);
		$query->addFilter('=E4', 1);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		return array(
			'__JOINS' => array(
					array(
						'TYPE' => 'INNER',
						'SQL' => 'INNER JOIN('.$query->getQuery().') DS ON DS.OWNER_ID = L.ID'
					)
				)
		);
	}
}