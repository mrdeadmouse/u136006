<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;

class DealSumStatisticEntry
{
	private static $bindings = null;
	/**
	* @return array
	*/
	public static function getAll($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addOrder('CREATED_DATE', 'ASC');

		$dbResult = $query->exec();
		$results = array();

		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}
	/**
	* @return array
	*/
	public static function getLatest($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$subQuery = new Query(DealSumStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => new SqlExpression($ownerID), '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
	/**
	* @return boolean
	*/
	public static function isRegistered($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('CREATED_DATE');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}
	/**
	* @return void
	*/
	public static function register($ownerID, array $entityFields = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STAGE_ID', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE', 'CURRENCY_ID', 'OPPORTUNITY', 'UF_*')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return;
			}
		}

		$bindings = self::getBindings();

		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		$semanticID = \CCrmDeal::GetSemanticID($stageID);
		$isLost = PhaseSemantics::isLost($semanticID);
		$isFinalized = PhaseSemantics::isFinal($semanticID);
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		/** @var Date $startDate */
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null)
		{
			$startDate = new Date();
		}

		/** @var Date $endDate */
		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null)
		{
			$endDate = new Date('9999-12-31', 'Y-m-d');
		}

		$date = $isFinalized ? $endDate : $startDate;
		$day = (int)$date->format('d');
		$month = (int)$date->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$date->format('Y');

		$binding = $bindings->get('SUM_TOTAL');
		if($binding === '')
		{
			$binding = 'OPPORTUNITY';
		}
		$total = isset($entityFields[$binding]) ? (double)$entityFields[$binding] : 0.0;
		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		$accData = \CCrmAccountingHelper::PrepareAccountingData(
			array(
				'CURRENCY_ID' => $currencyID,
				'SUM' => $total,
				'EXCH_RATE' => isset($entityFields['EXCH_RATE']) ? $entityFields['EXCH_RATE'] : null
			)
		);
		if(is_array($accData))
		{
			$currencyID = $accData['ACCOUNT_CURRENCY_ID'];
			$total = (double)$accData['ACCOUNT_SUM'];
		}

		$sumSlots = array();
		$sumSlotFields = DealSumStatisticsTable::getSumSlotFieldNames();
		foreach($sumSlotFields as $fieldName)
		{
			$binding = $bindings->get($fieldName);
			$sumSlots[$fieldName] = $binding !== '' && isset($entityFields[$binding])
				? (double)$entityFields[$binding] : 0.0;
		}

		$entities = self::getAll($ownerID);
		$final = null;
		$process = null;
		foreach($entities as $entity)
		{
			if(PhaseSemantics::isFinal($entity['STAGE_SEMANTIC_ID']))
			{
				$final = $entity;
			}
			else
			{
				$process = $entity;
			}

			if($final && $process)
			{
				break;
			}
		}

		$latest = self::getLatest($ownerID);
		if(is_array($latest))
		{
			if($startDate->getTimestamp() === $latest['START_DATE']->getTimestamp()
				&& $endDate->getTimestamp() === $latest['END_DATE']->getTimestamp()
				&& $responsibleID === (int)$latest['RESPONSIBLE_ID']
				&& $stageID === $latest['STAGE_ID']
				&& $currencyID === $latest['CURRENCY_ID']
				&& $total === (double)$latest['SUM_TOTAL']
				&& $sumSlots['UF_SUM_1'] === (double)$latest['UF_SUM_1']
				&& $sumSlots['UF_SUM_2'] === (double)$latest['UF_SUM_2']
				&& $sumSlots['UF_SUM_3'] === (double)$latest['UF_SUM_3']
				&& $sumSlots['UF_SUM_4'] === (double)$latest['UF_SUM_4']
				&& $sumSlots['UF_SUM_5'] === (double)$latest['UF_SUM_5'])
			{
				return;
			}

			if($startDate->getTimestamp() !== $latest['START_DATE']->getTimestamp()
				|| $endDate->getTimestamp() !== $latest['END_DATE']->getTimestamp()
				|| $responsibleID !== (int)$latest['RESPONSIBLE_ID'])
			{
				if(!$isFinalized)
				{
					DealSumStatisticsTable::deleteByFilter(
						array(
							'OWNER_ID' => $ownerID,
							'SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
						)
					);

					DealSumStatisticsTable::synchronize(
						$ownerID,
						array(
							'CREATED_DATE' => $startDate,
							'START_DATE' => $startDate,
							'END_DATE' => $endDate,
							'RESPONSIBLE_ID' => $responsibleID
						),
						PhaseSemantics::getProcessSemantis()
					);
				}
				else
				{
					if($startDate->getTimestamp() === $endDate->getTimestamp())
					{
						DealSumStatisticsTable::deleteByFilter(
							array(
								'OWNER_ID' => $ownerID,
								'SEMANTIC_ID' => PhaseSemantics::getProcessSemantis()
							)
						);

						DealSumStatisticsTable::synchronize(
							$ownerID,
							array(
								'CREATED_DATE' => $endDate,
								'START_DATE' => $startDate,
								'END_DATE' => $endDate,
								'RESPONSIBLE_ID' => $responsibleID
							),
							PhaseSemantics::getFinalSemantis()
						);
					}
					else
					{
						//Diphasic update of "final" semantics (first update with forged date: "1970-01-01") for avoid possible primary key conflict with "process" semantics
						DealSumStatisticsTable::synchronize(
							$ownerID,
							array(
								'CREATED_DATE' => new Date('1970-01-01', 'Y-m-d'),
								'START_DATE' => $startDate,
								'END_DATE' => $endDate,
								'RESPONSIBLE_ID' => $responsibleID
							),
							PhaseSemantics::getFinalSemantis()
						);

						DealSumStatisticsTable::synchronize(
							$ownerID,
							array(
								'CREATED_DATE' => $startDate,
								'START_DATE' => $startDate,
								'END_DATE' => $endDate,
								'RESPONSIBLE_ID' => $responsibleID
							),
							PhaseSemantics::getProcessSemantis()
						);

						DealSumStatisticsTable::synchronize(
							$ownerID,
							array(
								'CREATED_DATE' => $endDate,
								'START_DATE' => $startDate,
								'END_DATE' => $endDate,
								'RESPONSIBLE_ID' => $responsibleID
							),
							PhaseSemantics::getFinalSemantis()
						);
					}
				}
			}
		}

		$data = array_merge(
			array(
				'OWNER_ID' => $ownerID,
				'CREATED_DATE' => $date,
				'START_DATE' => $startDate,
				'END_DATE' => $endDate,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'PERIOD_DAY' => $day,
				'RESPONSIBLE_ID' => $responsibleID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost ? 'Y' : 'N',
				'CURRENCY_ID' => $currencyID,
				'SUM_TOTAL' => $total
			),
			$sumSlots
		);
		DealSumStatisticsTable::upsert($data);
	}
	/**
	* @return void
	*/
	public static function unregister($ownerID)
	{
		DealSumStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	* @return string|null
	*/
	public static function parseDateString($str)
	{
		if($str === '')
		{
			return null;
		}

		try
		{
			$date = new Date($str, Date::convertFormatToPhp(FORMAT_DATE));
		}
		catch(Main\ObjectException $e)
		{
			try
			{
				$date = new DateTime($str, Date::convertFormatToPhp(FORMAT_DATETIME));
				$date->setTime(0, 0, 0);
			}
			catch(Main\ObjectException $e)
			{
				return null;
			}
		}
		return $date;
	}
	/**
	* @return StatisticFieldBindings
	*/
	protected static function getBindings()
	{
		if(self::$bindings == null)
		{
			self::$bindings = new StatisticFieldBindings('deal_stat_sum_bind');
		}
		return self::$bindings;
	}
}