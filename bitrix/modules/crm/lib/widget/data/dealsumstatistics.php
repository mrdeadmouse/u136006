<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;

class DealSumStatistics extends DealDataSource
{
	const TYPE_NAME = 'DEAL_SUM_STATS';
	const GROUP_BY_USER = 'USER';
	const GROUP_BY_DATE = 'DATE';

	private static $messagesLoaded = false;
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

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);

		$isFinalSemantics = PhaseSemantics::isFinal($semanticID);
		$hasInvoices = $filter->getExtraParam('hasInvoices', null);
		$isLost = $filter->getExtraParam('isLost', null);

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}
		$enableGroupKey = isset($params['enableGroupKey']) ? (bool)$params['enableGroupKey'] : false;

		/** @var array $select */
		$select = isset($params['select']) && is_array($params['select']) ? $params['select'] : array();
		$name = '';
		$aggregate = '';
		if(!empty($select))
		{
			$selectItem = $select[0];
			if(isset($selectItem['name']))
			{
				$name = $selectItem['name'];
			}
			if(isset($selectItem['aggregate']))
			{
				$aggregate = strtoupper($selectItem['aggregate']);
			}
		}

		if($name === '')
		{
			$name = 'SUM_TOTAL';
		}

		if($aggregate !== '' && !in_array($aggregate, array('SUM', 'COUNT', 'MAX', 'MIN')))
		{
			$aggregate = '';
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

		$query = new Query(DealSumStatisticsTable::getEntity());

		$nameAlias = $name;
		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "COUNT(*)"));
			}
			else
			{
				$nameAlias = "{$nameAlias}_R";
				$query->registerRuntimeField('', new ExpressionField($nameAlias, "{$aggregate}(%s)", $name));
			}
		}

		$query->addSelect($nameAlias);
		$query->setTableAliasPostfix('_s2');

		$subQuery = new Query(DealSumStatisticsTable::getEntity());
		$subQuery->setTableAliasPostfix('_s1');
		$subQuery->addSelect('OWNER_ID');

		$subQuery->addFilter('>=END_DATE', $periodStartDate);
		$subQuery->addFilter('<=START_DATE', $periodEndDate);
		//$subQuery->addFilter('<=CREATED_DATE', $periodEndDate);

		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$subQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
		}

		if(is_bool($isLost))
		{
			$subQuery->addFilter('=IS_LOST', $isLost);
		}

		if(is_bool($hasInvoices))
		{
			$invoiceSubQuery = new Query(DealInvoiceStatisticsTable::getEntity());
			$invoiceSubQuery->addSelect('OWNER_ID');
			$invoiceSubQuery->addGroup('OWNER_ID');

			$invoiceSubQuery->addFilter('>=END_DATE', $periodStartDate);
			$invoiceSubQuery->addFilter('<=START_DATE', $periodEndDate);

			if($semanticID !== PhaseSemantics::UNDEFINED)
			{
				$invoiceSubQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
			}

			if($isFinalSemantics)
			{
				$invoiceSubQuery->addFilter('<=END_DATE', $periodEndDate);
			}
			else
			{
				$invoiceSubQuery->addFilter('<=CREATED_DATE', $periodEndDate);
			}

			$subQuery->addFilter(
				$hasInvoices ? '@OWNER_ID' : '!@OWNER_ID',
				new SqlExpression($invoiceSubQuery->getQuery())
			);
		}

		if($this->enablePermissionCheck && is_string($permissionSql) && $permissionSql !== '')
		{
			$subQuery->addFilter('@OWNER_ID', new SqlExpression($permissionSql));
		}

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$subQuery->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$subQuery->addGroup('OWNER_ID');

		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(%s)', 'CREATED_DATE'));

		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		$sort = isset($params['sort']) && is_array($params['sort']) && !empty($params['sort']) ? $params['sort'] : null;
		if($sort)
		{
			foreach($sort as $sortItem)
			{
				if(isset($sortItem['name']))
				{
					$query->addOrder($sortItem['name'], isset($sortItem['order']) ? $sortItem['order'] : 'asc');
				}
			}
		}

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			else //if($group === self::GROUP_BY_DATE)
			{
				$query->addSelect('CREATED_DATE');
				$query->addGroup('CREATED_DATE');
				if(!$sort)
				{
					$query->addOrder('CREATED_DATE', 'ASC');
				}
			}
		}

		$dbResult = $query->exec();
		$result = array();
		$useAlias = $nameAlias !== $name;
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$ary['DATE'] = $ary['CREATED_DATE']->format('Y-m-d');
				unset($ary['CREATED_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}

				if($enableGroupKey)
				{
					$result[$ary['DATE']] = $ary;
				}
				else
				{
					$result[] = $ary;
				}
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$rawResult = array();
			$userIDs = array();
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}

				$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($userID > 0 && !isset($userNames[$userID]))
				{
					$userIDs[] = $userID;
				}
				$rawResult[] = $ary;
			}
			$userNames = self::prepareUserNames($userIDs);
			if($enableGroupKey)
			{
				foreach($rawResult as $item)
				{
					$userID = $item['RESPONSIBLE_ID'];
					$item['USER_ID'] = $userID;
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					unset($item['RESPONSIBLE_ID']);

					$result[$userID] = $item;
				}
			}
			else
			{
				foreach($rawResult as $item)
				{
					$userID = $item['RESPONSIBLE_ID'];
					$item['USER_ID'] = $userID;
					$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
					unset($item['RESPONSIBLE_ID']);

					$result[] = $item;
				}
			}
		}
		else
		{
			while($ary = $dbResult->fetch())
			{
				if($useAlias && isset($ary[$nameAlias]))
				{
					$ary[$name] = $ary[$nameAlias];
					unset($ary[$nameAlias]);
				}
							
				$result[] = $ary;
			}
		}
		return $result;
	}
	/**
	 * @return array Array of arrays
	 */
	public static function getPresets()
	{
		self::includeModuleFile();
		return array(
			array(
				'title' => GetMessage('CRM_DEAL_SUM_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
				'context' => DataContext::ENTITY
			),
			array(
				'title' => GetMessage('CRM_DEAL_SUM_STAT_PRESET_OVERALL_SUM'),
				'name' => self::TYPE_NAME.'::OVERALL_SUM',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
				'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
				'context' => DataContext::FUND
			)
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('>=END_DATE', $periodStartDate);
		$query->addFilter('<=START_DATE', $periodEndDate);

		$responsibleIDs = $filter->getResponsibleIDs();
		if(!empty($responsibleIDs))
		{
			$query->addFilter('@RESPONSIBLE_ID', $responsibleIDs);
		}

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$query->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
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
}