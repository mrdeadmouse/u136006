<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Diag\SqlTrackerQuery;
use Bitrix\Main\SystemException;

final class Diag
{
	const SQL_SKIP        = 0x000;
	const SQL_COUNT       = 0x001;
	const SQL_PRINT_ALL   = 0x010;
	const SQL_DETECT_LIKE = 0x100;

	private $sqlBehavior = 0;
	private $enableTimeTracker = 0;
	private $enableErrorHandler = 0;
	private $showOnDisplay = 0;
	private $exclusiveUserId = null;
	/** @var  Diag */
	private static $instance;
	private $prevErrorReporting;
	private $levelReporting;
	private $stackSql = array();
	/** @var Connection connection */
	private $connection;

	private function __construct()
	{
		$this->sqlBehavior = self::SQL_SKIP;
		$this->levelReporting = E_ALL | E_STRICT;
		$this->connection = Application::getInstance()->getConnection();
	}

	/**
	 * Gets instance of Diag.
	 * @return Diag
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Collects debug info (sql queries, errors, etc).
	 * @param mixed $uniqueId Id of segment.
	 * @param null  $label Label for human.
	 * @return void
	 */
	public function collectDebugInfo($uniqueId, $label = null)
	{
		if($label === null)
		{
			$label = $uniqueId;
		}
		if($this->exclusiveUserId !== null && $this->getUser()->getId() != $this->exclusiveUserId)
		{
			return;
		}
		if($this->enableTimeTracker)
		{
			Debug::startTimeLabel($uniqueId);
		}
		if($this->enableErrorHandler)
		{
			$this->prevErrorReporting = error_reporting();
			error_reporting($this->levelReporting);
			set_error_handler(function ($code, $message, $file, $line)
			{
				if(strpos($file, '/disk/'))
				{
					$this->log(array(
						$code,
						$message,
						$file,
						$line
					));
				}
			}, $this->levelReporting);
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		//if($this->enableSqlTracker || $this->enableSqlCount)
		{
			if(empty($this->stackSql))
			{
				$this->connection->startTracker(true);
				array_push($this->stackSql, array($uniqueId, 0, array()));
			}
			else
			{
				list($prevLabel, $prevLabelCount, $prevSqlTrackerQueries) = array_pop($this->stackSql);
				list($countQueries, $sqlTrackerQueries) = $this->getDebugInfoSql();
				array_push($this->stackSql, array($prevLabel, $countQueries + $prevLabelCount, array_merge($prevSqlTrackerQueries, $sqlTrackerQueries)));

				$this->connection->startTracker(true);
				array_push($this->stackSql, array($uniqueId, 0, array()));
			}
		}
	}

	private function getDebugInfoSql()
	{
		$tracker = $this->connection->getTracker();
		$sqlTrackerQueries = $tracker->getQueries();

		return array(count($sqlTrackerQueries), $sqlTrackerQueries);
	}

	/**
	 * Logs debug info (sql queries, errors, etc).
	 * @param mixed $uniqueId Id of segment.
	 * @param null  $label Label for human.
	 * @throws SystemException
	 * @return void
	 */
	public function logDebugInfo($uniqueId, $label = null)
	{
		if($label === null)
		{
			$label = $uniqueId;
		}

		if($this->exclusiveUserId !== null && $this->getUser()->getId() != $this->exclusiveUserId)
		{
			return;
		}

		$debugData = array();
		if($this->enableTimeTracker)
		{
			Debug::endTimeLabel($uniqueId);
			$timeLabels = Debug::getTimeLabels();
			$debugData[] = "Time: {$timeLabels[$uniqueId]['time']}";
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		{
			list($prevLabel, $prevLabelCount, $prevSqlTrackerQueries) = array_pop($this->stackSql);

			list($countQueries, $sqlTrackerQueries) = $this->getDebugInfoSql();
			if($prevLabel === $uniqueId)
			{
				$countQueries += $prevLabelCount;
				$sqlTrackerQueries = array_merge($prevSqlTrackerQueries, $sqlTrackerQueries);
			}

			if($this->sqlBehavior & self::SQL_COUNT)
			{
				$debugData[] = 'Count sql: ' . $countQueries;
			}
		}
		if($this->sqlBehavior & (self::SQL_COUNT | self::SQL_DETECT_LIKE | self::SQL_PRINT_ALL))
		{
			/** @var SqlTrackerQuery[] $sqlTrackerQueries */
			foreach($sqlTrackerQueries as $query)
			{
				if($this->sqlBehavior & self::SQL_PRINT_ALL)
				{
					$debugData[] = array(
						$query->getTime(),
						$query->getSql(),
						$this->reformatBackTrace($query->getTrace())
					);
				}

				if(($this->sqlBehavior & self::SQL_DETECT_LIKE) && stripos($query->getSql(), 'upper') !== false)
				{
					$this->log(array(
						'Oh... LIKE UPPER... Delete! Destroy!',
						$this->reformatBackTrace($query->getTrace()),
					));
					throw new SystemException('Oh... LIKE UPPER... Delete! Destroy!');
				}
			}
			unset($query);
		}
		if($this->enableErrorHandler)
		{
			error_reporting($this->prevErrorReporting);
			restore_error_handler();
		}
		if($debugData)
		{
			array_unshift($debugData, "Label: {$label}");
			$this->log($debugData);
		}
	}

	/**
	 * Logs data in common log (@see AddMessage2Log).
	 * @param mixed $data Mixed data to log.
	 * @return void
	 */
	public function log($data)
	{
		$this->showOnDisplay && var_dump($data);
		AddMessage2Log(var_export($data, true), 'disk', 0);
	}

	private function reformatBackTrace(array $backtrace)
	{
		$functionStack = $filesStack = '';
		for($i = 1; $i < count($backtrace); $i++)
		{
			if(strlen($functionStack) > 0)
			{
				$functionStack .= " < ";
			}

			if(isset($backtrace[$i]["class"]))
			{
				$functionStack .= $backtrace[$i]["class"] . "::";
			}

			$functionStack .= $backtrace[$i]["function"];

			if(isset($backtrace[$i]["file"]))
			{
				$filesStack .= "\t" . $backtrace[$i]["file"] . ":" . $backtrace[$i]["line"] . "\n";
			}
		}

		return $functionStack . "\n" . $filesStack;
	}

	/**
	 * @return array|bool|\CAllUser|\CUser
	 */
	private function getUser()
	{
		global $USER;
		return $USER;
	}
} 