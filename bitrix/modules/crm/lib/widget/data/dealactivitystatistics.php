<?php
namespace Bitrix\Crm\Widget\Data;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\DealActivityStatisticsTable;

class DealActivityStatistics extends DealDataSource
{
	const TYPE_NAME = 'DEAL_ACTIVITY_STATS';
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

		$group = isset($params['group']) ? strtoupper($params['group']) : '';
		if($group !== '' && $group !== self::GROUP_BY_USER && $group !== self::GROUP_BY_DATE)
		{
			$group = '';
		}

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
			$name = 'CALL_QTY';
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

		$query = new Query(DealActivityStatisticsTable::getEntity());
		if($name === 'TOTAL')
		{
			$query->addSelect('CALL_QTY');
			$query->addSelect('MEETING_QTY');
			$query->addSelect('EMAIL_QTY');
		}
		else
		{
			$query->addSelect($name);
		}

		if($aggregate !== '')
		{
			if($aggregate === 'COUNT')
			{
				$query->registerRuntimeField('', new ExpressionField($name, "COUNT(*)"));
			}
			else
			{
				if($name === 'TOTAL')
				{
					$query->registerRuntimeField('', new ExpressionField('CALL_QTY', "{$aggregate}(CALL_QTY)"));
					$query->registerRuntimeField('', new ExpressionField('MEETING_QTY', "{$aggregate}(MEETING_QTY)"));
					$query->registerRuntimeField('', new ExpressionField('EMAIL_QTY', "{$aggregate}(EMAIL_QTY)"));
				}
				else
				{
					$query->registerRuntimeField('', new ExpressionField($name, "{$aggregate}({$name})"));
				}
			}
		}

		$recentOnly = (bool)$filter->getExtraParam('recentOnly', false);
		if($recentOnly)
		{
			$query->setTableAliasPostfix('_s2');

			$subQuery = new Query(DealActivityStatisticsTable::getEntity());
			$subQuery->setTableAliasPostfix('_s1');
			$subQuery->addSelect('OWNER_ID');

			$subQuery->addFilter('>=DEADLINE_DATE', $periodStartDate);
			$subQuery->addFilter('<=DEADLINE_DATE', $periodEndDate);

			if($semanticID !== PhaseSemantics::UNDEFINED)
			{
				$subQuery->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
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
			$subQuery->registerRuntimeField('', new ExpressionField('MAX_DEADLINE_DATE', 'MAX(DEADLINE_DATE)'));
			$subQuery->addFilter('<=DEADLINE_DATE', $periodEndDate);

			$query->registerRuntimeField('',
				new ReferenceField('M',
					Base::getInstanceByQuery($subQuery),
					array('=this.OWNER_ID' => 'ref.OWNER_ID', '=this.DEADLINE_DATE' => 'ref.MAX_DEADLINE_DATE'),
					array('join_type' => 'INNER')
				)
			);
		}
		else
		{
			$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
			$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

			if($semanticID !== PhaseSemantics::UNDEFINED)
			{
				$query->addFilter('=STAGE_SEMANTIC_ID', $semanticID);
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
		}

		if($group !== '')
		{
			if($group === self::GROUP_BY_USER)
			{
				$query->addSelect('RESPONSIBLE_ID');
				$query->addGroup('RESPONSIBLE_ID');
			}
			else //if($groupBy === self::GROUP_BY_DATE)
			{
				$query->addSelect('DEADLINE_DATE');
				$query->addGroup('DEADLINE_DATE');
				$query->addOrder('DEADLINE_DATE', 'ASC');
			}
		}

		$dbResult = $query->exec();
		//Trace('sql', $query->getLastQuery(), 1);
		$result = array();
		if($group === self::GROUP_BY_DATE)
		{
			while($ary = $dbResult->fetch())
			{
				$ary['DATE'] = $ary['DEADLINE_DATE']->format('Y-m-d');
				unset($ary['DEADLINE_DATE']);

				if($ary['DATE'] === '9999-12-31')
				{
					//Skip empty dates
					continue;
				}


				if($name === 'TOTAL')
				{
					$ary['TOTAL'] = $ary['CALL_QTY'] + $ary['MEETING_QTY'] + $ary['EMAIL_QTY'];
				}

				$result[] = $ary;
			}
		}
		elseif($group === self::GROUP_BY_USER)
		{
			$userIDs = array();
			while($ary = $dbResult->fetch())
			{
				$userID = $ary['RESPONSIBLE_ID'] = (int)$ary['RESPONSIBLE_ID'];
				if($userID > 0 && !isset($userNames[$userID]))
				{
					$userIDs[] = $userID;
				}

				if($name === 'TOTAL')
				{
					$ary['TOTAL'] = $ary['CALL_QTY'] + $ary['MEETING_QTY'] + $ary['EMAIL_QTY'];
				}

				$result[] = $ary;
			}
			$userNames = self::prepareUserNames($userIDs);
			foreach($result as &$item)
			{
				$userID = $item['RESPONSIBLE_ID'];
				$item['USER_ID'] = $userID;
				$item['USER'] = isset($userNames[$userID]) ? $userNames[$userID] : "[{$userID}]";
				unset($item['RESPONSIBLE_ID']);
			}
			unset($item);
		}
		else
		{
			while($ary = $dbResult->fetch())
			{
				if($name === 'TOTAL')
				{
					$ary['TOTAL'] = $ary['CALL_QTY'] + $ary['MEETING_QTY'] + $ary['EMAIL_QTY'];
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
				'title' => GetMessage('CRM_DEAL_ACT_STAT_PRESET_CALL_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::CALL_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'title' => GetMessage('CRM_DEAL_ACT_STAT_PRESET_MEETING_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::MEETING_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'MEETING_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'title' => GetMessage('CRM_DEAL_ACT_STAT_PRESET_EMAIL_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::EMAIL_OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'EMAIL_QTY', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
			array(
				'title' => GetMessage('CRM_DEAL_ACT_STAT_PRESET_OVERALL_COUNT'),
				'name' => self::TYPE_NAME.'::OVERALL_COUNT',
				'source' => self::TYPE_NAME,
				'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM'),
				'context' => DataContext::ENTITY
			),
		);
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		$filter = self::internalizeFilter($filterParams);
		$query = new Query(DealActivityStatisticsTable::getEntity());
		$query->addSelect('OWNER_ID');
		$query->addGroup('OWNER_ID');

		$period = $filter->getPeriod();
		$periodStartDate = $period['START'];
		$periodEndDate = $period['END'];

		$query->addFilter('>=DEADLINE_DATE', $periodStartDate);
		$query->addFilter('<=DEADLINE_DATE', $periodEndDate);

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