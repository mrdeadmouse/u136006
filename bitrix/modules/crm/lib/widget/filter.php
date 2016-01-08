<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Filter
{
	const MIN_YEAR = 1000;
	const MAX_YEAR = 9999;

	/** @var int */
	private $periodTypeID = '';
	/** @var int */
	private $year = 0;
	/** @var int */
	private $quarter = 0;
	/** @var int */
	private $month = 0;
	/** @var array[int] */
	private $responsibleIDs = null;
	/** @var array  */
	private $extras = null;

	public function __construct(array $params)
	{
		$periodTypeID = isset($params['periodType']) ? $params['periodType'] : FilterPeriodType::UNDEFINED;

		$this->setPeriodTypeID($periodTypeID);
		$this->setYear(isset($params['year']) ? (int)$params['year'] : 1970);
		$this->setQuarter(isset($params['quarter']) ? (int)$params['quarter'] : 1);
		$this->setMonth(isset($params['month']) ? (int)$params['month'] : 1);
		$this->setResponsibleIDs(isset($params['responsibleIDs']) && is_array($params['responsibleIDs'])
			? $params['responsibleIDs'] : array());
		$this->setExtras(isset($params['extras']) && is_array($params['extras'])
			? $params['extras'] : array());
	}
	/**
	* @return boolean
	*/
	public function isEmpty()
	{
		return $this->periodTypeID === FilterPeriodType::UNDEFINED;
	}
	/**
	* @return string
	*/
	public function getPeriodTypeID()
	{
		return $this->periodTypeID;
	}
	public function setPeriodTypeID($periodTypeID)
	{
		if($periodTypeID !== FilterPeriodType::UNDEFINED && !FilterPeriodType::isDefined($periodTypeID))
		{
			throw new Main\ArgumentException("Period type '{$periodTypeID}' is unknown in current context.", 'periodTypeID');
		}

		$this->periodTypeID = $periodTypeID;
	}
	/**
	* @return int
	*/
	public function getYear()
	{
		return $this->year;
	}
	/**
	* @return void
	*/
	public function setYear($year)
	{
		if(!is_int($year))
		{
			$year = (int)$year;
		}

		if($year < self::MIN_YEAR || $year > self::MAX_YEAR)
		{
			throw new Main\ArgumentOutOfRangeException('year', self::MIN_YEAR, self::MAX_YEAR);
		}

		$this->year = $year;
	}
	/**
	* @return int
	*/
	public function getQuarter()
	{
		return $this->quarter;
	}
	/**
	* @return void
	*/
	public function setQuarter($quarter)
	{
		if(!is_int($quarter))
		{
			$quarter = (int)$quarter;
		}

		if($quarter < 1 || $quarter > 4)
		{
			throw new Main\ArgumentOutOfRangeException('quarter', 1, 4);
		}

		$this->quarter = $quarter;
	}
	/**
	* @return int
	*/
	public function getMonth()
	{
		return $this->month;
	}
	/**
	* @return void
	*/
	public function setMonth($month)
	{
		if(!is_int($month))
		{
			$month = (int)$month;
		}

		if($month < 1 || $month > 12)
		{
			throw new Main\ArgumentOutOfRangeException('month', 1, 12);
		}

		$this->month = $month;
	}
	/**
	* @return array[int]
	*/
	public function getResponsibleIDs()
	{
		return $this->responsibleIDs;
	}
	/**
	* @return void
	*/
	public function setResponsibleIDs(array $responsibleIDs)
	{
		$this->responsibleIDs = $responsibleIDs;
	}
	/**
	* @return array
	*/
	public function getPeriod()
	{
		if($this->isEmpty())
		{
			throw new Main\InvalidOperationException('Could not prepare period. Filter is empty.');
		}

		$result = array();
		if($this->periodTypeID === FilterPeriodType::YEAR)
		{
			$year = $this->year;
			$result['START'] = new Date("{$year}-1-1", 'Y-m-d');
			$result['END'] = new Date("{$year}-12-31", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::QUARTER)
		{
			$year = $this->year;
			$quarter = $this->quarter;
			$lastMonth = 3 * $quarter;
			$firstMonth = $lastMonth - 2;

			$d = new \DateTime("{$year}-{$lastMonth}-01");
			$lastDay = $d->format('t');

			$result['START'] = new Date("{$year}-{$firstMonth}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$lastMonth}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::MONTH)
		{
			$year = $this->year;
			$month = $this->month;

			$d = new \DateTime("{$year}-{$month}-01");
			$lastDay = $d->format('t');
			$result['START'] = new Date("{$year}-{$month}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$month}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::CURRENT_MONTH)
		{
			$d = new \DateTime();
			$year = $d->format('Y');
			$month = $d->format('n');
			$lastDay = $d->format('t');

			$leftBoundary = new \DateTime();
			$leftBoundary->setDate($year, $month, 1);
			$leftBoundary->setTime(0, 0, 0);

			$rightBoundary = new \DateTime();
			$rightBoundary->setDate($year, $month, $lastDay);
			$rightBoundary->setTime(0, 0, 0);

			$result['START'] = Date::createFromPhp($leftBoundary);
			$result['END'] = Date::createFromPhp($rightBoundary);
		}
		elseif($this->periodTypeID === FilterPeriodType::CURRENT_QUARTER)
		{
			$d = new \DateTime();
			$year = $d->format('Y');
			$month = $d->format('n');
			$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));

			$lastMonth = 3 * $quarter;
			$firstMonth = $lastMonth - 2;

			$d = new \DateTime("{$year}-{$lastMonth}-01");
			$lastDay = $d->format('t');

			$result['START'] = new Date("{$year}-{$firstMonth}-01", 'Y-m-d');
			$result['END'] = new Date("{$year}-{$lastMonth}-{$lastDay}", 'Y-m-d');
		}
		elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_90
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_60
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_30
			|| $this->periodTypeID === FilterPeriodType::LAST_DAYS_7)
		{
			$rightBoundary = new \DateTime();
			$rightBoundary->setTime(0, 0, 0);

			$leftBoundary = new \DateTime();
			$leftBoundary->setTime(0, 0, 0);

			$intervalLength = 7;
			if($this->periodTypeID === FilterPeriodType::LAST_DAYS_90)
			{
				$intervalLength = 90;
			}
			elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_60)
			{
				$intervalLength = 60;
			}
			elseif($this->periodTypeID === FilterPeriodType::LAST_DAYS_30)
			{
				$intervalLength = 30;
			}

			$interval = new \DateInterval("P{$intervalLength}D");
			$interval->invert = 1;
			$leftBoundary->add($interval);

			$result['START'] = Date::createFromPhp($leftBoundary);
			$result['END'] = Date::createFromPhp($rightBoundary);
		}
		return $result;
	}
	/**
	* @return array
	*/
	public function getExtras()
	{
		return $this->extras;
	}
	/**
	* @return void
	*/
	public function setExtras(array $extras)
	{
		$this->extras = $extras;
	}
	/**
	* @return mixed
	*/
	public function getExtraParam($name, $defaultValue)
	{
		return isset($this->extras[$name]) ? $this->extras[$name] : $defaultValue;
	}
	/**
	* @return void
	*/
	public function setExtraParam($name, $value)
	{
		$this->extras[$name] = $value;
	}
	/**
	* @return array
	*/
	public function getParams()
	{
		$result = array('periodType' => $this->periodTypeID);

		if($this->periodTypeID === FilterPeriodType::YEAR
			|| $this->periodTypeID === FilterPeriodType::QUARTER
			|| $this->periodTypeID === FilterPeriodType::MONTH)
		{
			$result['year'] = $this->year;
		}

		if($this->periodTypeID === FilterPeriodType::QUARTER)
		{
			$result['quarter'] = $this->quarter;
		}
		elseif($this->periodTypeID === FilterPeriodType::MONTH)
		{
			$result['month'] = $this->month;
		}

		if(is_array($this->responsibleIDs))
		{
			$result['responsibleIDs'] = $this->responsibleIDs;
		}

		if(is_array($this->extras))
		{
			$result['extras'] = $this->extras;
		}

		return $result;
	}
	/**
	* @return array
	*/
	public static function externalizeParams(array $params)
	{
		$periodTypeID = isset($params['periodType']) ? $params['periodType'] : FilterPeriodType::UNDEFINED;
		$year = isset($params['year']) ? $params['year'] : 0;
		$quarter = isset($params['quarter']) ? $params['quarter'] : 0;
		$month = isset($params['month']) ? $params['month'] : 0;

		$periodParts = array($periodTypeID);
		if($year > 0
			&& ($periodTypeID === FilterPeriodType::YEAR
				|| $periodTypeID === FilterPeriodType::QUARTER
				|| $periodTypeID === FilterPeriodType::MONTH))
		{
			$periodParts[] = $year;
		}
		if($quarter > 0 && $periodTypeID === FilterPeriodType::QUARTER)
		{
			$periodParts[] = $quarter;
		}
		if($month > 0 && $periodTypeID === FilterPeriodType::MONTH)
		{
			$periodParts[] = $month;
		}

		$result = array('PERIOD' => implode('-', $periodParts));

		$responsibleIDs = isset($params['responsibleIDs']) ? $params['responsibleIDs'] : null;
		if(is_array($responsibleIDs))
		{
			$result['RESPONSIBLE_ID'] = $responsibleIDs;
		}

		return $result;
	}
	/**
	* @return array
	*/
	public static function internalizeParams(array $params)
	{
		$period = isset($params['PERIOD']) ? $params['PERIOD'] : '';

		$result = array('periodType' => FilterPeriodType::UNDEFINED);
		$periodParts = explode('-', $period);
		$periodPartCount = count($periodParts);
		if($periodPartCount > 0)
		{
			$result['periodType'] = $periodParts[0];
		}
		if($periodPartCount > 1
			&& ($result['periodType'] === FilterPeriodType::YEAR
				|| $result['periodType'] === FilterPeriodType::QUARTER
				|| $result['periodType'] === FilterPeriodType::MONTH))
		{
			$result['year'] = (int)$periodParts[1];
		}
		if($periodPartCount > 2 && $result['periodType'] === FilterPeriodType::QUARTER)
		{
			$result['quarter'] = (int)$periodParts[2];
		}
		if($periodPartCount > 2 && $result['periodType'] === FilterPeriodType::MONTH)
		{
			$result['month'] = (int)$periodParts[2];
		}

		if(isset($params['RESPONSIBLE_ID']))
		{
			$responsibleIDs = array();
			if(is_array($params['RESPONSIBLE_ID']))
			{
				foreach($params['RESPONSIBLE_ID'] as $userID)
				{
					if($userID > 0)
					{
						$responsibleIDs[] = (int)$userID;
					}
				}
			}
			elseif($params['RESPONSIBLE_ID'] > 0)
			{
				$responsibleIDs[] = (int)$params['RESPONSIBLE_ID'];
			}

			if(!empty($responsibleIDs))
			{
				$result['responsibleIDs'] = $responsibleIDs;
			}
		}

		return $result;
	}
}