<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

/**
 * This is not a part of public API.
 * For internal use only.
 * 
 * @access private
 */
class CTaskCountersProcessor
{
	private 
		$currentUnixTimeStamp = null,
		$curBitrixTimeStamp = null,
		$expiredCandidatesBitrixTimeStamp = null,
		$currentDateTime = null,
		$expiredCandidatesDateTime = null;

	const COUNTER_TASKS_TOTAL = 'tasks_total';

	// Subtotals counters for roles
	const COUNTER_TASKS_MY         = 'tasks_my';
	const COUNTER_TASKS_ACCOMPLICE = 'tasks_acc';
	const COUNTER_TASKS_AUDITOR    = 'tasks_au';
	const COUNTER_TASKS_ORIGINATOR = 'tasks_orig';

	// Not viewed tasks counters
	const COUNTER_TASKS_MY_NEW         = 'tasks_my_new';
	const COUNTER_TASKS_ACCOMPLICE_NEW = 'tasks_acc_new';

	// Expired tasks counters
	const COUNTER_TASKS_MY_EXPIRED         = 'tasks_my_expired';
	const COUNTER_TASKS_ACCOMPLICE_EXPIRED = 'tasks_acc_expired';
	const COUNTER_TASKS_AUDITOR_EXPIRED    = 'tasks_au_expired';
	const COUNTER_TASKS_ORIGINATOR_EXPIRED = 'tasks_orig_expired';

	// Tasks to be expired soon counters
	const COUNTER_TASKS_MY_EXPIRED_CANDIDATES         = 'tasks_my_expired_cand';
	const COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES = 'tasks_acc_expired_cand';
	//const COUNTER_TASKS_AUDITOR_EXPIRED_CANDIDATES    = 'tasks_au_expired_cand';
	//const COUNTER_TASKS_ORIGINATOR_EXPIRED_CANDIDATES = 'tasks_orig_expired_cand';

	// Tasks without DEADLINE counters
	const COUNTER_TASKS_MY_WO_DEADLINE         = 'tasks_my_wo_dl';
	const COUNTER_TASKS_ORIGINATOR_WO_DEADLINE = 'tasks_orig_wo_dl';

	// Counters of tasks in status CTasks::STATE_SUPPOSEDLY_COMPLETED
	const COUNTER_TASKS_ORIGINATOR_WAIT_CTRL = 'tasks_orig_wctrl';


	// private consts:
	const DEADLINE_NOT_COUNTED                  = 0;
	const DEADLINE_COUNTED_AS_EXPIRED           = 1;
	const DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE = 2;

	private static $instanceOfSelf = null;


	/**
	 * @return array of known counters IDs
	 */
	public static function enumCountersIds()
	{
		return (array(
			self::COUNTER_TASKS_TOTAL,
			self::COUNTER_TASKS_MY,
			self::COUNTER_TASKS_ACCOMPLICE,
			self::COUNTER_TASKS_AUDITOR,
			self::COUNTER_TASKS_ORIGINATOR,
			self::COUNTER_TASKS_MY_NEW,
			self::COUNTER_TASKS_ACCOMPLICE_NEW,
			self::COUNTER_TASKS_MY_EXPIRED,
			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
			self::COUNTER_TASKS_AUDITOR_EXPIRED,
			self::COUNTER_TASKS_ORIGINATOR_EXPIRED,
			self::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
			self::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
			self::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
			self::COUNTER_TASKS_MY_WO_DEADLINE,
			self::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE
		));
	}


	/**
	 * Get instance of multiton tasks' list controller
	 */
	public static function getInstance()
	{
		if (self::$instanceOfSelf === null)
			self::$instanceOfSelf = new self();

		return (self::$instanceOfSelf);
	}


	/**
	 * prevent creating through "new"
	 */
	private function __construct()
	{
		if ($this->currentUnixTimeStamp === null)
		{
			$this->currentUnixTimeStamp = CTasksPerHitOption::getHitTimestamp();
			/** @noinspection PhpDeprecationInspection */
			$this->curBitrixTimeStamp = $this->currentUnixTimeStamp + CTasksTools::getTimeZoneOffset();
			$this->expiredCandidatesBitrixTimeStamp = $this->curBitrixTimeStamp + 86400;	// Calc current time + 24hours (86400 seconds)
			$this->currentDateTime = ConvertTimeStamp($this->curBitrixTimeStamp, 'FULL');
			$this->expiredCandidatesDateTime = ConvertTimeStamp($this->expiredCandidatesBitrixTimeStamp, 'FULL');
		}
	}

	public static function ensureAgentExists()
	{
		if(CTaskCountersProcessorInstaller::checkProcessIsNotActive())
		{
			$agent = CAgent::GetList(array(), array(
				'MODULE_ID' => 'tasks',
				'NAME' => 'CTaskCountersProcessor::agent();'
			))->fetch();

			if(!is_array($agent) || !isset($agent['ID']))
			{
				CAgent::AddAgent(
					'CTaskCountersProcessor::agent();',
					'tasks',
					'N', 
					900
				);	// every 15 minutes
			}
		}
	}

	public static function agent()
	{
		if(intval(self::getAdminId()))
		{
			CTaskCountersProcessorHomeostasis::onExpirityRecountAgent(); // here we start sub-agent for expired counters recalculation

			// this must be ambigious, kz countExpiredAndExpiredSoonTasks is called already inside CTaskCountersProcessorInstaller,
			// which initialized inside CTaskCountersProcessorHomeostasis::onExpirityRecountAgent
			$executionTimeLimit = 0.2;		// time limit in seconds
			self::countExpiredAndExpiredSoonTasks($executionTimeLimit);
		}

		return "CTaskCountersProcessor::agent();";
	}


	public static function countExpiredAndExpiredSoonTasks($executionTimeLimit = 0.5)
	{
		$unixTs = time();

		/** @noinspection PhpDeprecationInspection */
		$bts = $unixTs + CTasksTools::getTimeZoneOffset();
		$expiredEdgeDateTime = ConvertTimeStamp($bts, 'FULL');
		// Calc current time + 24hours (86400 seconds)
		$expiredSoonEdgeDateTime = ConvertTimeStamp($bts + 86400, 'FULL');

		// Two different filters for expired tasks and to be expired tasks
		// This is cause to DB->CurrentTimeFunction() != current time in general case
		// TODO: this should be optimized to one getList() request
		$arFilterExpiredSoon = array(	// the task is to be expired soon, but not counted
			'::LOGIC' => 'AND',
			'!REAL_STATUS' => array(
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_COMPLETED,
				CTasks::STATE_DECLINED
			),
			'>=DEADLINE'        => $expiredEdgeDateTime,
			'<DEADLINE'         => $expiredSoonEdgeDateTime,
			'!=DEADLINE_COUNTED' => self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE
		);

		$arFilterExpired = array(	// the task was expired, but not counted
			'::LOGIC' => 'AND',
			'!REAL_STATUS' => array(
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_COMPLETED,
				CTasks::STATE_DECLINED
			),
			'<DEADLINE'         => $expiredEdgeDateTime,
			'!=DEADLINE_COUNTED' => self::DEADLINE_COUNTED_AS_EXPIRED
		);

		$arSelect = array(
			'ID', 'CREATED_BY', 'RESPONSIBLE_ID', 
			'REAL_STATUS', 'DEADLINE', 'DEADLINE_COUNTED'
		);

		$arParams = array(
			'nPageTop'      => 50,							// selects only 50 tasks
			'bIgnoreErrors' => true,						// don't die on SQL errors
			'USER_ID'       => self::getAdminId()			// act as admin
		);

		$itemsTotalProcessed = $itemsLastProcessed = 0;

		try
		{
			$timeLimit = microtime(true) + $executionTimeLimit;
			$minIterations = 2;		// do not less than 2 iterations (in case if there was processed items)
			$iterationsDone = 0;

			do
			{
				$itemsLastProcessed = self::iterateAgentWork($arFilterExpiredSoon, $arFilterExpired, $arSelect, $arParams);
				$itemsTotalProcessed += $itemsLastProcessed;

				$iterationsDone++;

				if ($itemsLastProcessed == 0)
					break;

			} while (($iterationsDone < $minIterations) || (microtime(true) <= $timeLimit));
		}
		catch (Exception $e)
		{
			CTaskAssert::logError('[0x87d58c6c] ');
		}

		// flush counters
		if ($itemsTotalProcessed != 0)
			CTaskCountersQueue::execute();


		return ($itemsTotalProcessed);
	}


	private static function iterateAgentWork($arFilterExpiredSoon, $arFilterExpired, $arSelect, $arParams)
	{
		global $DB;

		$arTasks = array();
		$arExpiredTaskIds = array();
		$arExpiredSoonTaskIds = array();

		$rs = CTasks::getList(array(), $arFilterExpired, $arSelect, $arParams);
		while ($arTask = $rs->fetch())
		{
			$taskId = (int) $arTask['ID'];
			$arExpiredTaskIds[] = $taskId;
			$arTasks[$taskId] = array(
				'CREATED_BY'       => $arTask['CREATED_BY'],
				'RESPONSIBLE_ID'   => $arTask['RESPONSIBLE_ID'],
				'REAL_STATUS'      => $arTask['REAL_STATUS'],
				'DEADLINE'         => $arTask['DEADLINE'],
				'ACCOMPLICES'      => array(),	// will be inited later
				'AUDITORS'         => array(),	// will be inited later
				'DEADLINE_COUNTED' => $arTask['DEADLINE_COUNTED']
			);
		}

		$rs = CTasks::getList(array(), $arFilterExpiredSoon, $arSelect, $arParams);
		while ($arTask = $rs->fetch())
		{
			$taskId = (int) $arTask['ID'];
			$arExpiredSoonTaskIds[] = $taskId;
			$arTasks[$taskId] = array(
				'CREATED_BY'       => $arTask['CREATED_BY'],
				'RESPONSIBLE_ID'   => $arTask['RESPONSIBLE_ID'],
				'REAL_STATUS'      => $arTask['REAL_STATUS'],
				'DEADLINE'         => $arTask['DEADLINE'],
				'ACCOMPLICES'      => array(),	// will be inited later
				'AUDITORS'         => array(),	// will be inited later
				'DEADLINE_COUNTED' => $arTask['DEADLINE_COUNTED']
			);
		}

		$arAllTaskIds = array_merge($arExpiredTaskIds, $arExpiredSoonTaskIds);

		// mark, that this tasks are counted
		if (count($arExpiredTaskIds))
		{
			$strSql = "UPDATE b_tasks 
				SET DEADLINE_COUNTED = " . self::DEADLINE_COUNTED_AS_EXPIRED . " 
				WHERE ID IN(" . implode(',' , $arExpiredTaskIds) . ")";

			$rc = $DB->query($strSql, $bIgnoreDbErrors = true);

			if ( ! $rc )
				throw new Exception();
		}

		if (count($arExpiredSoonTaskIds))
		{
			$strSql = "UPDATE b_tasks 
				SET DEADLINE_COUNTED = " . self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE . " 
				WHERE ID IN(" . implode(',' , $arExpiredSoonTaskIds) . ")";

			$rc = $DB->query($strSql, $bIgnoreDbErrors = true);

			if ( ! $rc )
				throw new Exception();
		}

		// get ACCOMPLICES and AUDITORS
		$allTasksCount = count($arAllTaskIds);
		if ($allTasksCount)
		{
			$rsMembers = CTaskMembers::GetList(array(), array('TASK_ID' => $arAllTaskIds));

			if ( ! is_object($rsMembers) )
				throw new Exception();

			while ($arMember = $rsMembers->fetch())
			{
				$taskId = (int) $arMember['TASK_ID'];

				if ($arMember['TYPE'] === 'A')
					$arTasks[$taskId]['ACCOMPLICES'][] = (int) $arMember['USER_ID'];
				elseif ($arMember['TYPE'] === 'U')
					$arTasks[$taskId]['AUDITORS'][] = (int) $arMember['USER_ID'];
			}
		}

		// process counters
		$objSelf = self::getInstance();
		foreach ($arTasks as $arTask)
		{
			$objSelf->actualizeExpirityCounters(
				$arTask['DEADLINE'],
				$arTask['DEADLINE_COUNTED'],
				$arTask['REAL_STATUS'],
				$arTask['RESPONSIBLE_ID'],
				$arTask['CREATED_BY'],
				$arTask['AUDITORS'],
				$arTask['ACCOMPLICES']
			);
		}

		return ($allTasksCount);
	}


	/**
	 * Process DEADLINE counters
	 */
	public function onBeforeTaskAdd(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $effectiveUserId)
	{
		if ( ! isset($arFields['DEADLINE']) )
			return;

		if ($this->isDeadlineExpired($arFields['DEADLINE']))
			$isExpired = true;
		elseif ($this->isDeadlineExpiredSoon($arFields['DEADLINE']))
			$isExpired = false;		// will be expired soon
		else
			return;		// nothing to do with deadline

		// Task can be expired / almost expired only if not closed yet
		if (isset($arFields['STATUS']))
		{
			if (self::isCompletedStatus($arFields['STATUS']))
				return;
		}

		if ($isExpired)
		{
			$arFields['DEADLINE_COUNTED'] = self::DEADLINE_COUNTED_AS_EXPIRED;
			$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED;
			$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED;
			$auditorCounterId     = CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED;
			$originatorCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED;
		}
		else
		{
			// Not expired, but will be expired soon
			$arFields['DEADLINE_COUNTED'] = self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE;
			$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES;
			$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES;
			$auditorCounterId     = null;		// don't count expired soon tasks for auditors
			$originatorCounterId  = null;		// don't count expired soon tasks for originators
		}

		if ($originatorCounterId !== null)
		{
			// Expirity counters not counted for originators in tasks,
			// where originator == responsible
			if ($arFields['CREATED_BY'] != $arFields['RESPONSIBLE_ID'])
			{
				CTaskCountersQueue::push(
					$originatorCounterId,
					CTaskCountersQueue::OP_INCREMENT,
					array($arFields['CREATED_BY'])
				);
			}
		}

		CTaskCountersQueue::push(
			$responsibleCounterId,
			CTaskCountersQueue::OP_INCREMENT,
			array($arFields['RESPONSIBLE_ID'])
		);

		if (isset($arFields['ACCOMPLICES']) && is_array($arFields['ACCOMPLICES']))
		{
			CTaskCountersQueue::push(
				$accompliceCounterId,
				CTaskCountersQueue::OP_INCREMENT,
				$arFields['ACCOMPLICES']
			);
		}

		if ($auditorCounterId !== null)
		{
			if (isset($arFields['AUDITORS']) && is_array($arFields['AUDITORS']))
			{
				CTaskCountersQueue::push(
					$auditorCounterId,
					CTaskCountersQueue::OP_INCREMENT,
					$arFields['AUDITORS']
				);
			}
		}

		// CTaskCountersQueue::execute() will be called in onAfterTaskAdd
	}


	public static function onAfterTaskAdd($arFields, $effectiveUserId)
	{
		$originatorId  = (int) $arFields['CREATED_BY'];
		$responsibleId = (int) $arFields['RESPONSIBLE_ID'];

		$taskStatus = CTasks::STATE_PENDING;		// by default

		if (isset($arFields['STATUS']))
			$taskStatus = $arFields['STATUS'];

		// task is in statuses where it can be "new",
		// so do work
		if (self::isNewStatus($taskStatus))
		{
			if (
				($responsibleId > 0)
				// if $responsibleId == $effectiveUserId => this task is already just viewed by user
				&& ($responsibleId != $effectiveUserId)
			)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
					CTaskCountersQueue::OP_INCREMENT,
					array($responsibleId)
				);
			}

			if (isset($arFields['ACCOMPLICES']) && is_array($arFields['ACCOMPLICES']))
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
					CTaskCountersQueue::OP_INCREMENT,
					array_diff($arFields['ACCOMPLICES'], array($effectiveUserId)) // if $accompliceId == $effectiveUserId => this task is already just viewed by user
				);
			}
		}

		if (
			($taskStatus == CTasks::STATE_SUPPOSEDLY_COMPLETED)
			&& ($originatorId != $responsibleId)
		)
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
				CTaskCountersQueue::OP_INCREMENT,
				array((int) $arFields['CREATED_BY'])
			);
		}

		if (
			self::isDeadlineAbsentsInFields($arFields)
			&& ( ! self::isCompletedStatus($taskStatus) )
			&& ($responsibleId != $originatorId)	// counts only for tasks that from self to self
		)
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
				CTaskCountersQueue::OP_INCREMENT,
				array($originatorId)
			);

			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
				CTaskCountersQueue::OP_INCREMENT,
				array($responsibleId)
			);
		}


		// Execute counters queue in any case because it's expected by onBeforeTaskAdd
		CTaskCountersQueue::execute();
	}


	public static function onBeforeTaskDelete($taskId, $arFields)
	{
		$taskId = (int) $taskId;

		// Decrement "DEADLINE" counters
		if (
			($arFields['DEADLINE_COUNTED'] == self::DEADLINE_COUNTED_AS_EXPIRED)
			|| ($arFields['DEADLINE_COUNTED'] == self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE)
		)
		{
			if ($arFields['DEADLINE_COUNTED'] == self::DEADLINE_COUNTED_AS_EXPIRED)
			{
				$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED;
				$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED;
				$auditorCounterId     = CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED;
				$originatorCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED;
			}
			else
			{
				$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES;
				$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES;
				$auditorCounterId     = null;	// don't count expired soon tasks for auditors
				$originatorCounterId  = null;	// don't count expired soon tasks for originator
			}

			if ($arFields['RESPONSIBLE_ID'] > 0)
			{
				CTaskCountersQueue::push(
					$responsibleCounterId,
					CTaskCountersQueue::OP_DECREMENT,
					array($arFields['RESPONSIBLE_ID'])
				);
			}

			if ($originatorCounterId !== null)
			{
				if ($arFields['CREATED_BY'] != $arFields['RESPONSIBLE_ID'])
				{
					CTaskCountersQueue::push(
						$originatorCounterId,
						CTaskCountersQueue::OP_DECREMENT,
						array($arFields['CREATED_BY'])
					);
				}
			}

			if (isset($arFields['ACCOMPLICES']) && is_array($arFields['ACCOMPLICES']))
			{
				CTaskCountersQueue::push(
					$accompliceCounterId,
					CTaskCountersQueue::OP_DECREMENT,
					$arFields['ACCOMPLICES']
				);
			}

			if ($auditorCounterId !== null)
			{
				if (isset($arFields['AUDITORS']) && is_array($arFields['AUDITORS']))
				{
					CTaskCountersQueue::push(
						$auditorCounterId,
						CTaskCountersQueue::OP_DECREMENT,
						$arFields['AUDITORS']
					);
				}
			}
		}

		// Decrement COUNTER_TASKS_ORIGINATOR_WAIT_CTRL counter
		if (
			($arFields['REAL_STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
			&& ($arFields['CREATED_BY'] != $arFields['RESPONSIBLE_ID'])
		)
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
				CTaskCountersQueue::OP_DECREMENT,
				array((int) $arFields['CREATED_BY'])
			);
		}

		// if task not in statuses CTasks::STATE_PENDING or CTasks::STATE_NEW,
		// than counters of "new tasks" must to have been decremented already (on status change)
		// or not incremented at all (on task creation).
		if (self::isNewStatus($arFields['REAL_STATUS']))
		{
			// This task is not 'new' elsemore, 
			// so decrement counters for users with incremented counters,
			// i.e. users who didn't watch tasks yet.
			self::decrementNewTasksCounters($arFields['RESPONSIBLE_ID'], $arFields['ACCOMPLICES'], $taskId);
		}

		// If task without DEADLINE and was counted, decrement counters
		if (
			self::isDeadlineAbsentsInFields($arFields)
			&& ($arFields['CREATED_BY'] != $arFields['RESPONSIBLE_ID'])	// counts only for tasks that from self to self
			&& ( ! self::isCompletedStatus($arFields['REAL_STATUS']) )
		)
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
				CTaskCountersQueue::OP_DECREMENT,
				array((int) $arFields['CREATED_BY'])
			);

			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
				CTaskCountersQueue::OP_DECREMENT,
				array((int) $arFields['RESPONSIBLE_ID'])
			);
		}

		CTaskCountersQueue::execute();
	}


	/**
	 * This function will decrements user counters of new tasks when user view task
	 */
	public static function onBeforeTaskViewedFirstTime($taskId, $userId, $onTaskAdd)
	{
		$taskId = (int) $taskId;
		$userId = (int) $userId;

		// there is self::onAfterTaskAdd() function process case when task added
		if ($onTaskAdd)
			return;

		$adminId = self::getAdminId();
		if(!intval($adminId))
			return;

		// if task not in statuses CTasks::STATE_PENDING or CTasks::STATE_NEW,
		// than counters of "new tasks" must to have been decremented already (on status change)
		// or not incremented at all (on task creation).
		$oTaskItem = CTaskItem::getInstance($taskId, $adminId);
		$arTask = $oTaskItem->getData($escaped = false);
		if ( ! self::isNewStatus($arTask['REAL_STATUS']) )
			return;

		// The task wasn't viewed by user, but will be viewed just now
		// So, we must decrement count of unviewed tasks for this user
		$oTaskItem = CTaskItem::getInstance($taskId, $adminId);
		$arTask = $oTaskItem->getData(false);

		if ($userId == $arTask['RESPONSIBLE_ID'])
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
				CTaskCountersQueue::OP_DECREMENT,
				array($userId)
			);
		}

		if (isset($arTask['ACCOMPLICES']) && is_array($arTask['ACCOMPLICES']))
		{
			if (in_array($userId, $arTask['ACCOMPLICES']))
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
					CTaskCountersQueue::OP_DECREMENT,
					array($userId)
				);
			}
		}

		CTaskCountersQueue::execute();
	}


	public function onBeforeTaskUpdate($taskId, $arTask, &$arFields)
	{
		list ($arAccomplices, $arAuditors) = self::getTaskAccomplicesAndAuditors($taskId);

		// Actualize expirity counters of the task, due to the system time change
		$arFields['DEADLINE_COUNTED'] = $this->actualizeExpirityCounters(
			$arTask['DEADLINE'],
			$arTask['DEADLINE_COUNTED'],
			$arTask['REAL_STATUS'],
			$arTask['RESPONSIBLE_ID'],
			$arTask['CREATED_BY'],
			$arAuditors,
			$arAccomplices
		);

		// Actualize expirity counters of the task, due to task status change
		$status = $arTask['REAL_STATUS'];
		if (isset($arFields['STATUS']) && ($arFields['STATUS'] != $arTask['REAL_STATUS']))
		{
			$status = $arFields['STATUS'];

			$arFields['DEADLINE_COUNTED'] = $this->actualizeExpirityCounters(
				$arTask['DEADLINE'],
				$arFields['DEADLINE_COUNTED'],
				$arFields['STATUS'],
				$arTask['RESPONSIBLE_ID'],
				$arTask['CREATED_BY'],
				$arAuditors,
				$arAccomplices
			);
		}

		// Actualize expirity counters of the task, due to deadline change
		if (isset($arFields['DEADLINE']) && ($arFields['DEADLINE'] != $arTask['DEADLINE']))
		{
			$arFields['DEADLINE_COUNTED'] = $this->actualizeExpirityCounters(
				$arFields['DEADLINE'],
				$arFields['DEADLINE_COUNTED'],
				$status,
				$arTask['RESPONSIBLE_ID'],
				$arTask['CREATED_BY'],
				$arAuditors,
				$arAccomplices
			);
		}

		// Recount expirity counters due to the members change
		if ($arFields['DEADLINE_COUNTED'] != self::DEADLINE_NOT_COUNTED)
		{
			if ($arFields['DEADLINE_COUNTED'] == self::DEADLINE_COUNTED_AS_EXPIRED)
			{
				$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED;
				$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED;
				$auditorCounterId     = CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED;
				$originatorCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED;
			}
			else
			{
				$responsibleCounterId = CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES;
				$accompliceCounterId  = CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES;
				$auditorCounterId     = null;		// don't count expired soon tasks for auditors
				$originatorCounterId  = null;		// don't count expired soon tasks for auditors
			}

			// Responsible changed?
			$actualResponsibleId = $arTask['RESPONSIBLE_ID'];
			if (
				isset($arFields['RESPONSIBLE_ID'])
				&& ($arFields['RESPONSIBLE_ID'] != $arTask['RESPONSIBLE_ID'])
			)
			{
				CTaskCountersQueue::push(
					$responsibleCounterId,
					CTaskCountersQueue::OP_DECREMENT,
					array($arTask['RESPONSIBLE_ID'])		// prev. responsible
				);

				CTaskCountersQueue::push(
					$responsibleCounterId,
					CTaskCountersQueue::OP_INCREMENT,
					array($arFields['RESPONSIBLE_ID'])		// new responsible
				);

				if ($originatorCounterId !== null)
				{
					if (
						// If prevOriginator == prevResponsible and now it's not so
						// Than we must increment originator 'expirity' counter
						// (we don't count expirity for origintators in tasks, where responsible == originator)
						($arTask['CREATED_BY'] == $arTask['RESPONSIBLE_ID'])
						&& ($arTask['CREATED_BY'] != $arFields['RESPONSIBLE_ID'])
					)
					{
						CTaskCountersQueue::push(
							$originatorCounterId,
							CTaskCountersQueue::OP_INCREMENT,
							array($arTask['CREATED_BY'])		// prev originator
						);
					}
					elseif (
						// If prevOriginator != prevResponsible and now it's not so
						// Than we must decrement originator 'expirity' counter
						// (we don't count expirity for origintators in tasks, where responsible == originator)
						($arTask['CREATED_BY'] != $arTask['RESPONSIBLE_ID'])
						&& ($arTask['CREATED_BY'] == $arFields['RESPONSIBLE_ID'])
					)
					{
						CTaskCountersQueue::push(
							$originatorCounterId,
							CTaskCountersQueue::OP_DECREMENT,
							array($arTask['CREATED_BY'])		// prev originator
						);
					}
				}

				$actualResponsibleId = $arFields['RESPONSIBLE_ID'];
			}

			if ($originatorCounterId !== null)
			{
				// Originator changed?
				if (
					isset($arFields['CREATED_BY'])
					&& ($arFields['CREATED_BY'] != $arTask['CREATED_BY'])
				)
				{
					// prevOriginator != actualResponsible => it means, 
					// expirity counter was counted for prevOriginator, so decrement it
					if ($arTask['CREATED_BY'] != $actualResponsibleId)
					{
						CTaskCountersQueue::push(
							$originatorCounterId,
							CTaskCountersQueue::OP_DECREMENT,
							array($arTask['CREATED_BY'])		// prev originator
						);
					}

					// newOriginator != actualResponsible => it means, 
					// expirity counter must be counted for newOriginator, so increment it
					if ($arFields['CREATED_BY'] != $actualResponsibleId)
					{
						CTaskCountersQueue::push(
							$originatorCounterId,
							CTaskCountersQueue::OP_INCREMENT,
							array($arTask['CREATED_BY'])		// prev originator
						);
					}
				}
			}

			if (isset($arFields['ACCOMPLICES']))
			{
				$arAddedAccomplices   = array_diff($arFields['ACCOMPLICES'], $arTask['ACCOMPLICES']);
				$arRemovedAccomplices = array_diff($arTask['ACCOMPLICES'], $arFields['ACCOMPLICES']);

				CTaskCountersQueue::push(
					$accompliceCounterId,
					CTaskCountersQueue::OP_DECREMENT,
					$arRemovedAccomplices
				);

				CTaskCountersQueue::push(
					$accompliceCounterId,
					CTaskCountersQueue::OP_INCREMENT,
					$arAddedAccomplices
				);
			}

			if ($auditorCounterId !== null)
			{
				if (isset($arFields['AUDITORS']))
				{
					$arAddedAuditors   = array_diff($arFields['AUDITORS'], $arTask['AUDITORS']);
					$arRemovedAuditors = array_diff($arTask['AUDITORS'], $arFields['AUDITORS']);

					CTaskCountersQueue::push(
						$auditorCounterId,
						CTaskCountersQueue::OP_DECREMENT,
						$arRemovedAuditors
					);

					CTaskCountersQueue::push(
						$auditorCounterId,
						CTaskCountersQueue::OP_INCREMENT,
						$arAddedAuditors
					);
				}
			}
		}

		// CTaskCountersQueue::execute() will be called in onAfterTaskUpdate
	}


	public static function onAfterTaskUpdate($taskId, $arPrevFields, $arNewFields)
	{
		// Deadline was changed?
		if (
			isset($arNewFields['DEADLINE'])
			&& ($arNewFields['DEADLINE'] != $arPrevFields['DEADLINE'])
		)
		{
			$deadlineAfter = $arNewFields['DEADLINE'];
		}
		else
			$deadlineAfter = $arPrevFields['DEADLINE'];

		// Status was changed?
		if (
			isset($arNewFields['STATUS'])
			&& ($arNewFields['STATUS'] != $arPrevFields['REAL_STATUS'])
		)
		{
			self::onAfterStatusChanged(
				$taskId,
				$arPrevFields['REAL_STATUS'], $arNewFields['STATUS'], 
				$arPrevFields['DEADLINE'],
				$arPrevFields['RESPONSIBLE_ID'], $arPrevFields['ACCOMPLICES'],
				$arPrevFields['CREATED_BY']
			);

			$statusAfter = $arNewFields['STATUS'];
		}
		else
			$statusAfter = $arPrevFields['REAL_STATUS'];

		if (isset($arNewFields['CREATED_BY']))
			$originatorAfter = $arNewFields['CREATED_BY'];
		else
			$originatorAfter = $arPrevFields['CREATED_BY'];

		if (isset($arNewFields['RESPONSIBLE_ID']))
			$responsibleAfter = $arNewFields['RESPONSIBLE_ID'];
		else
			$responsibleAfter = $arPrevFields['RESPONSIBLE_ID'];

		if (isset($arNewFields['ACCOMPLICES']))
			$arAccomplicesAfter = $arNewFields['ACCOMPLICES'];
		else
			$arAccomplicesAfter = $arPrevFields['ACCOMPLICES'];

		// Count absents deadline (only for tasks that not from self to self)
		if (
			($arPrevFields['CREATED_BY'] != $arPrevFields['RESPONSIBLE_ID'])
			&& ( ! self::isCompletedStatus($statusAfter) )
		)
		{
			$deadlineWasAbsent = self::isDeadlineAbsentsInFields($arPrevFields);

			if ( ! array_key_exists('DEADLINE', $arNewFields) )
				$deadlineIsAbsents = $deadlineWasAbsent;	// Deadline absents if it was absent, and vice versa
			else
				$deadlineIsAbsents = self::isDeadlineAbsentsInFields($arNewFields);

			// Deadline was added?
			if ($deadlineWasAbsent && !$deadlineIsAbsents)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
					CTaskCountersQueue::OP_DECREMENT,
					array((int) $arPrevFields['CREATED_BY'])
				);

				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
					CTaskCountersQueue::OP_DECREMENT,
					array((int) $arPrevFields['RESPONSIBLE_ID'])
				);
			}
			// Deadline was removed?
			elseif (!$deadlineWasAbsent && $deadlineIsAbsents)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
					CTaskCountersQueue::OP_INCREMENT,
					array((int) $arPrevFields['CREATED_BY'])
				);

				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
					CTaskCountersQueue::OP_INCREMENT,
					array((int) $arPrevFields['RESPONSIBLE_ID'])
				);
			}
		}

		self::onAfterMembersChanged(
			$taskId, $statusAfter, $deadlineAfter,
			$arPrevFields['CREATED_BY'], $originatorAfter,
			$arPrevFields['RESPONSIBLE_ID'], $responsibleAfter,
			$arPrevFields['ACCOMPLICES'], $arAccomplicesAfter
		);

		// Execute counters queue in any case because it's expected by onBeforeTaskUpdate
		CTaskCountersQueue::execute();
	}


	/**
	 * Returns current datetime string
	 */
	public function getNowDateTime()
	{
		return ($this->currentDateTime);
	}


	/**
	 * Returns datetime string, before which tasks is counted as "expired soon"
	 */
	public function getExpiredSoonEdgeDateTime()
	{
		return ($this->expiredCandidatesDateTime);
	}


	// --------------------- Private functions are below -----------------------


	private static function onAfterStatusChanged($taskId, $prevStatus, $newStatus, $deadline, $responsibleId, $arAccomplices, $originatorId)
	{
		$prevStatus = (int) $prevStatus;
		$newStatus  = (int) $newStatus;

		if (self::isNewStatus($prevStatus) && ( ! self::isNewStatus($newStatus) ) )
		{
			// This task is not 'new' elsemore, 
			// so decrement counters for users with incremented counters,
			// i.e. users who didn't watch tasks yet.
			self::decrementNewTasksCounters($responsibleId, $arAccomplices, $taskId);
		}
		elseif ( ( ! self::isNewStatus($prevStatus) ) && self::isNewStatus($newStatus) )
		{
			// This task become "new" for users, who didn't watch this task yet

			// Get users, who viewed this task
			try
			{
				$arUsersViewed = CTasks::getUsersViewedTask($taskId);
			}
			catch (TasksException $e)
			{
				$arUsersViewed = array();
			}

			// Responsible didn't watch the task yet?
			// So his counter of "new tasks" must be incremented
			$responsibleId = (int) $responsibleId;
			if ( ! in_array($responsibleId, $arUsersViewed, true) )
				CTaskCountersQueue::push(CTaskCountersProcessor::COUNTER_TASKS_MY_NEW, CTaskCountersQueue::OP_INCREMENT, array($responsibleId));

			// Accomplice didn't watch the task yet?
			// So his counter of "new tasks" must be incremented
			$arAccomplices = array_unique($arAccomplices);

			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
				CTaskCountersQueue::OP_INCREMENT,
				array_diff($arAccomplices, $arUsersViewed)		// accomplices who not watch the task
			);
		}

		if ($prevStatus !== $newStatus)
		{
			if ($responsibleId != $originatorId)
			{
				// Status is not STATE_SUPPOSEDLY_COMPLETED elsemore
				if ($prevStatus == CTasks::STATE_SUPPOSEDLY_COMPLETED)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_DECREMENT,
						array((int) $originatorId)
					);
				}
				// Status became to STATE_SUPPOSEDLY_COMPLETED
				elseif ($newStatus == CTasks::STATE_SUPPOSEDLY_COMPLETED)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_INCREMENT,
						array((int) $originatorId)
					);
				}
			}

			// Count tasks that without DEADLINE
			if (
				self::isDeadlineAbsentsInFields(array('DEADLINE' => $deadline))
				&& ($responsibleId != $originatorId)
			)
			{
				$statusWasComlete   = self::isCompletedStatus($prevStatus);
				$statusIsComleteNow = self::isCompletedStatus($newStatus);

				// If status was complete and is not complete now => ++counter
				if ($statusWasComlete && !$statusIsComleteNow)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
						CTaskCountersQueue::OP_INCREMENT,
						array($originatorId)
					);

					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
						CTaskCountersQueue::OP_INCREMENT,
						array($responsibleId)
					);
				}
				// If status was NOT complete and is complete now => --counter
				elseif (!$statusWasComlete && $statusIsComleteNow)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
						CTaskCountersQueue::OP_DECREMENT,
						array($originatorId)
					);

					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
						CTaskCountersQueue::OP_DECREMENT,
						array($responsibleId)
					);
				}
			}
		}
	}


	private static function onAfterMembersChanged(
		$taskId, $currentStatus, $currentDeadline,
		$prevOriginatorId, $newOriginatorId, 
		$prevResponsibleId, $newResponsibleId, 
		$arPrevAccomplices, $arNewAccomplices
	)
	{
		$currentStatus      = (int) $currentStatus;
		$prevOriginatorId   = (int) $prevOriginatorId;
		$newOriginatorId    = (int) $newOriginatorId;
		$prevResponsibleId  = (int) $prevResponsibleId;
		$newResponsibleId   = (int) $newResponsibleId;
		$arNewAccomplices   = array_unique($arNewAccomplices);
		$arPrevAccomplices  = array_unique($arPrevAccomplices);

		$isOriginatorChanged  = null;
		$isResponsibleChanged = null;
		$isAccomplicesChanged = null;

		if ($newOriginatorId !== $prevOriginatorId)
			$isOriginatorChanged = true;

		if ($newResponsibleId !== $prevResponsibleId)
			$isResponsibleChanged = true;

		$arAddedAccomplices   = array_diff($arNewAccomplices, $arPrevAccomplices);
		$arRemovedAccomplices = array_diff($arPrevAccomplices, $arNewAccomplices);

		if (!empty($arAddedAccomplices) || !empty($arRemovedAccomplices))
			$isAccomplicesChanged = true;

		// count tasks without DEADLINE setted
		if (
			self::isDeadlineAbsentsInFields(array('DEADLINE' => $currentDeadline))
			&& ( ! self::isCompletedStatus($currentStatus) )
		)
		{
			if ($isOriginatorChanged || $isResponsibleChanged)
			{
				// Decompose in two stages:
				// 1. Changing of originator
				// 2. Changing of responsible

				// 1. Changing of originator
				if ($isOriginatorChanged)
				{
					// Was tasks counted for previous originator & responsible?
					// It was counted only if prevOriginator != responsible
					if ($prevOriginatorId != $prevResponsibleId)
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
							CTaskCountersQueue::OP_DECREMENT,
							array($prevOriginatorId)
						);

						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
							CTaskCountersQueue::OP_DECREMENT,
							array($prevResponsibleId)
						);
					}

					// Must we count task for new originator & responsible?
					// We must count only if newOriginator != responsible
					if ($newOriginatorId != $prevResponsibleId)
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
							CTaskCountersQueue::OP_INCREMENT,
							array($newOriginatorId)
						);

						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
							CTaskCountersQueue::OP_INCREMENT,
							array($prevResponsibleId)
						);
					}
				}

				// 2. Changing of responsible
				if ($isResponsibleChanged)
				{
					// Was tasks counted for previous responsible & originator?
					// It was counted only if prevResponsible != originator
					if ($prevResponsibleId != $newOriginatorId)
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
							CTaskCountersQueue::OP_DECREMENT,
							array($newOriginatorId)
						);

						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
							CTaskCountersQueue::OP_DECREMENT,
							array($prevResponsibleId)
						);
					}

					// Must we count task for new responsible & originator?
					// We must count only if newResponsible != originator
					if ($newResponsibleId != $newOriginatorId)
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE,
							CTaskCountersQueue::OP_INCREMENT,
							array($newOriginatorId)
						);

						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE,
							CTaskCountersQueue::OP_INCREMENT,
							array($newResponsibleId)
						);
					}
				}
			}
		}

		// count tasks that wait control
		if (
			($currentStatus == CTasks::STATE_SUPPOSEDLY_COMPLETED)
			&& ($isOriginatorChanged || $isResponsibleChanged)
		)
		{
			if ($isOriginatorChanged)
			{
				if ($prevOriginatorId != $prevResponsibleId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_DECREMENT,
						array($prevOriginatorId)
					);
				}

				if ($newOriginatorId != $prevResponsibleId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_INCREMENT,
						array($newOriginatorId)
					);
				}
			}

			if ($isResponsibleChanged)
			{
				if ($prevResponsibleId != $newOriginatorId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_DECREMENT,
						array($newOriginatorId)
					);
				}

				if ($newResponsibleId != $newOriginatorId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL,
						CTaskCountersQueue::OP_INCREMENT,
						array($newOriginatorId)
					);
				}
			}
		}

		// count not viewed tasks
		if (
			($isResponsibleChanged || $isAccomplicesChanged)
			&& self::isNewStatus($currentStatus)
		)
		{
			// Get users, who viewed this task
			try
			{
				$arUsersViewed = CTasks::getUsersViewedTask($taskId);
			}
			catch (TasksException $e)
			{
				$arUsersViewed = array();
			}

			if ($isResponsibleChanged)
			{
				// Previous responsible not watch the task?
				// So his counter of "new tasks" must be decremented
				if ( ! in_array($prevResponsibleId, $arUsersViewed, true) )
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
						CTaskCountersQueue::OP_DECREMENT,
						array($prevResponsibleId)
					);
				}

				// New responsible didn't watch the task yet?
				// So his counter of "new tasks" must be incremented
				if ( ! in_array($newResponsibleId, $arUsersViewed, true) )
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
						CTaskCountersQueue::OP_INCREMENT,
						array($newResponsibleId)
					);
				}
			}

			if ($isAccomplicesChanged)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
					CTaskCountersQueue::OP_DECREMENT,
					array_diff($arRemovedAccomplices, $arUsersViewed)	// removed accomplices who didn't watched the task
				);

				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
					CTaskCountersQueue::OP_INCREMENT,
					array_diff($arAddedAccomplices, $arUsersViewed)	// added accomplices who not watch the task
				);
			}
		}
	}


	/**
	 * Decrements counters for users, which not view task (not regitered in b_tasks_viewed)
	 */
	private static function decrementNewTasksCounters($responsibleId, $arAccomplices, $taskId)
	{
		// Get users, who viewed this task
		try
		{
			$arUsersViewed = CTasks::getUsersViewedTask($taskId);
		}
		catch (TasksException $e)
		{
			$arUsersViewed = array();
		}

		// Responsible didn't watch tasks yet?
		// So his counter of "new tasks" is incremented and we must decrement it
		$responsibleId = (int) $responsibleId;
		if ( ! in_array($responsibleId, $arUsersViewed, true) )
		{
			CTaskCountersQueue::push(
				CTaskCountersProcessor::COUNTER_TASKS_MY_NEW,
				CTaskCountersQueue::OP_DECREMENT,
				array($responsibleId)
			);
		}

		CTaskCountersQueue::push(
			CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW,
			CTaskCountersQueue::OP_DECREMENT,
			array_diff($arAccomplices, $arUsersViewed)	// users who didn't watch the task
		);
	}


	/**
	 * Actualize expirity counters on time change, depends on current task status
	 */
	private function actualizeExpirityCounters($deadline, $deadlineCounted, $status, $responsibleId, $originatorId, $arAuditors, $arAccomplices)
	{
		if (
			// The tasks becomes to be expired soon, but not counted yet
			($this->isDeadlineExpiredSoon($deadline) && ! $this->isCompletedStatus($status))
			&& ($deadlineCounted != self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE)
		)
		{
			// Counted as expired? Decrements this counters
			if ($deadlineCounted == self::DEADLINE_COUNTED_AS_EXPIRED)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED,
					CTaskCountersQueue::OP_DECREMENT,
					array($responsibleId)
				);

				if ($originatorId != $responsibleId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED,
						CTaskCountersQueue::OP_DECREMENT,
						array($originatorId)
					);
				}

				if (is_array($arAccomplices))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
						CTaskCountersQueue::OP_DECREMENT,
						$arAccomplices
					);
				}

				if (is_array($arAuditors))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED,
						CTaskCountersQueue::OP_DECREMENT,
						$arAuditors
					);
				}
			}

			if ($this->isCompletedStatus($status))
			{
				$deadlineCounted = self::DEADLINE_NOT_COUNTED;
			}
			else
			{
				// Count as expired soon
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
					CTaskCountersQueue::OP_INCREMENT,
					array($responsibleId)
				);

				// don't count expired soon tasks for originator
				// if ($originatorId != $responsibleId)
				// {
				// 	CTaskCountersQueue::push(
				// 		CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED_CANDIDATES,
				// 		CTaskCountersQueue::OP_INCREMENT,
				// 		array($originatorId)
				// 	);
				// }

				if (is_array($arAccomplices))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
						CTaskCountersQueue::OP_INCREMENT,
						$arAccomplices
					);
				}

				// don't count expired soon tasks for auditors
				// if (is_array($arAuditors))
				// {
				// 	CTaskCountersQueue::push(
				// 		CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED_CANDIDATES,
				// 		CTaskCountersQueue::OP_INCREMENT,
				// 		$arAuditors
				// 	);
				// }

				$deadlineCounted = self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE;
			}
		}
		elseif (
			// The tasks becomes is expired, but not counted yet
			($this->isDeadlineExpired($deadline) && ! $this->isCompletedStatus($status))
			&& ($deadlineCounted != self::DEADLINE_COUNTED_AS_EXPIRED)
		)
		{
			// Counted as expired soon? Decrements this counters
			if ($deadlineCounted == self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE)
			{
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
					CTaskCountersQueue::OP_DECREMENT,
					array($responsibleId)
				);

				// don't count expired soon tasks for originator
				// if ($originatorId != $responsibleId)
				// {
				// 	CTaskCountersQueue::push(
				// 		CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED_CANDIDATES,
				// 		CTaskCountersQueue::OP_DECREMENT,
				// 		array($originatorId)
				// 	);
				// }

				if (is_array($arAccomplices))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
						CTaskCountersQueue::OP_DECREMENT,
						$arAccomplices
					);
				}

				// don't count expired soon tasks for auditors
				// if (is_array($arAuditors))
				// {
				// 	CTaskCountersQueue::push(
				// 		CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED_CANDIDATES,
				// 		CTaskCountersQueue::OP_DECREMENT,
				// 		$arAuditors
				// 	);
				// }
			}

			if ($this->isCompletedStatus($status))
			{
				$deadlineCounted = self::DEADLINE_NOT_COUNTED;
			}
			else
			{
				// Count as expired
				CTaskCountersQueue::push(
					CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED,
					CTaskCountersQueue::OP_INCREMENT,
					array($responsibleId)
				);

				if ($originatorId != $responsibleId)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED,
						CTaskCountersQueue::OP_INCREMENT,
						array($originatorId)
					);
				}

				if (is_array($arAccomplices))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
						CTaskCountersQueue::OP_INCREMENT,
						$arAccomplices
					);
				}

				if (is_array($arAuditors))
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED,
						CTaskCountersQueue::OP_INCREMENT,
						$arAuditors
					);
				}

				$deadlineCounted = self::DEADLINE_COUNTED_AS_EXPIRED;
			}
		}
		else
		{
			if (
				( ! $this->isCompletedStatus($status) )	// expired task is not complete task by definition
				&& (
					$this->isDeadlineExpired($deadline)
					|| $this->isDeadlineExpiredSoon($deadline)
				)
			)
			{
				$expiredOrExpiredCandidate = true;
			}
			else
				$expiredOrExpiredCandidate = false;

			// Task become not expired and not to be expired soon, but it was counted
			if (
				! $expiredOrExpiredCandidate
				&& ($deadlineCounted != self::DEADLINE_NOT_COUNTED)	// task expirity counted?
			)
			{
				// Counted as expired? Decrements this counters
				if ($deadlineCounted == self::DEADLINE_COUNTED_AS_EXPIRED)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED,
						CTaskCountersQueue::OP_DECREMENT,
						array($responsibleId)
					);

					if ($originatorId != $responsibleId)
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED,
							CTaskCountersQueue::OP_DECREMENT,
							array($originatorId)
						);
					}

					if (is_array($arAccomplices))
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED,
							CTaskCountersQueue::OP_DECREMENT,
							$arAccomplices
						);
					}

					if (is_array($arAuditors))
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED,
							CTaskCountersQueue::OP_DECREMENT,
							$arAuditors
						);
					}
				}
				// Counted as expired soon? Decrements this counters
				elseif ($deadlineCounted == self::DEADLINE_COUNTED_AS_EXPIRED_CANDIDATE)
				{
					CTaskCountersQueue::push(
						CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES,
						CTaskCountersQueue::OP_DECREMENT,
						array($responsibleId)
					);

					// don't count expired soon tasks for originator
					// if ($originatorId != $responsibleId)
					// {
					// 	CTaskCountersQueue::push(
					// 		CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED_CANDIDATES,
					// 		CTaskCountersQueue::OP_DECREMENT,
					// 		array($originatorId)
					// 	);
					// }

					if (is_array($arAccomplices))
					{
						CTaskCountersQueue::push(
							CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES,
							CTaskCountersQueue::OP_DECREMENT,
							$arAccomplices
						);
					}

					// don't count expired soon tasks for auditors
					// if (is_array($arAuditors))
					// {
					// 	CTaskCountersQueue::push(
					// 		CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED_CANDIDATES,
					// 		CTaskCountersQueue::OP_DECREMENT,
					// 		$arAccomplices
					// 	);
					// }
				}

				$deadlineCounted = self::DEADLINE_NOT_COUNTED;
			}
		}

		return ($deadlineCounted);
	}


	private static function getTaskAccomplicesAndAuditors($taskId)
	{
		$arAccomplices = $arAuditors = array();

		$rsMembers = CTaskMembers::GetList(array(), array("TASK_ID" => $taskId));
		while ($arMember = $rsMembers->fetch())
		{
			if ($arMember["TYPE"] == "A")
				$arAccomplices[] = (int) $arMember["USER_ID"];
			elseif ($arMember["TYPE"] == "U")
				$arAuditors[] = (int) $arMember["USER_ID"];
		}

		return (array($arAccomplices, $arAuditors));
	}


	private static function isCompletedStatus($status)
	{
		return(in_array(
			(int) $status,
			array(
				CTasks::STATE_DECLINED,
				CTasks::STATE_SUPPOSEDLY_COMPLETED,
				CTasks::STATE_COMPLETED
			),
			true
		));
	}


	private static function isNewStatus($status)
	{
		return(in_array(
			(int) $status,
			array(
				CTasks::STATE_NEW,
				CTasks::STATE_PENDING
			),
			true
		));
	}


	private function isDeadlineExpired($deadline)
	{
		$ts = MakeTimeStamp($deadline);

		// No deadline at all
		if ($ts < 100000)
			return (false);		// task not expired

		// Not to be expired soon? So, nothing to do.
		if ($ts < $this->curBitrixTimeStamp)
			return (true);
		else
			return (false);
	}


	private static function isDeadlineAbsentsInFields($arFields)
	{
		if (
			array_key_exists('DEADLINE', $arFields)
			&& (MakeTimeStamp($arFields['DEADLINE']) >= 100000)
		)
		{
			return (false);
		}
		else
			return (true);
	}


	private function isDeadlineExpiredSoon($deadline)
	{
		$ts = MakeTimeStamp($deadline);

		// No deadline at all
		if ($ts < 100000)
			return (false);		// task not to be expired soon

		// Not to be expired soon? So, nothing to do.
		if (($ts >= $this->curBitrixTimeStamp) && ($ts < $this->expiredCandidatesBitrixTimeStamp))
			return (true);
		else
			return (false);
	}

	private function getAdminId()
	{
		static $adminId;

		if($adminId === null)
		{
			$adminId = CTasksTools::GetCommanderInChief();
			if(!intval($adminId))
			{
				CAdminNotify::Add(
					array(
						"MESSAGE" => GetMessage('TASKS_COUNTERS_PROCESSOR_ADMIN_IS_NOT_AN_ADMIN'),
						"TAG" => "TASKS_SYSTEM_NO_ADMIN",
						"MODULE_ID" => "TASKS",
						"ENABLE_CLOSE" => "Y"
					)
				);
			}
		}

		return $adminId;
	}
}


/**
 * This is not a part of public API.
 * For internal use only.
 * 
 * @access private
 */
class CTaskCountersQueue
{
	private static $queue = array();

	const OP_INCREMENT = 1;
	const OP_DECREMENT = 2;


	public static function push($counterId, $operation, $arUsers, $delta = 1)
	{
		self::$queue[] = array(
			'counterId' => $counterId,
			'operation' => $operation,
			'arUsers'   => $arUsers,
			'delta'     => $delta
		);
	}


	public static function execute()
	{
		// In case of excepted execution flow queue must be empty 
		// when calling code gets control
		$queue = self::$queue;
		self::$queue = array();

		$lengthBeforeOprimization = count($queue);
		$queue = self::optimizeQueue($queue);
		$lengthAfterOprimization = count($queue);

		// $s = 'Execute queue, length: ' . $lengthAfterOprimization
		//	. ' (removed elements during optimization: '
		//	. ($lengthBeforeOprimization - $lengthAfterOprimization)
		//	. ')';
		//soundex($s);

		$arUsersAffected = array();

		foreach ($queue as $request)
		{
			$arUsersAffected = array_merge($arUsersAffected, $request['arUsers']);

			self::processUsersCounters(
				$request['counterId'],
				$request['operation'],
				$request['arUsers'],
				$request['delta']
			);
		}

		$arUsersAffected = array_unique($arUsersAffected);
		foreach ($arUsersAffected as $userId)
			self::refreshTotalCounter($userId);
	}


	private static function optimizeQueue($queue)
	{
		$optimizedQueue = array();

		foreach ($queue as $element)
		{
			// skip operations without destination users
			if (empty($element['arUsers']))
				continue;

			if ($element['operation'] === self::OP_DECREMENT)
			{
				$element['operation'] = self::OP_INCREMENT;
				$element['delta']     = -1 * $element['delta'];
			}

			$key = $element['counterId'] . "'/" . implode('-', $element['arUsers']);

			if (array_key_exists($key, $optimizedQueue))
				$optimizedQueue[$key]['delta'] += $element['delta'];
			else
				$optimizedQueue[$key] = $element;
		}

		return ($optimizedQueue);
	}


	private static function refreshTotalCounter($userId)
	{
		$arValues = CUserCounter::GetValues($userId, $site_id = '**');

		$total = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL]))
			$total = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_TOTAL];

		$my = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY]))
			$my = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY];

		$accomplice = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE]))
			$accomplice = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE];

		$auditor = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_AUDITOR]))
			$auditor = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_AUDITOR];

		$originator = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR]))
			$originator = (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR];

		// Responsible's real counters:
		$realMy = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_NEW]))
			$realMy += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_NEW];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED]))
			$realMy += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE]))
			$realMy += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_WO_DEADLINE];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES]))
			$realMy += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_MY_EXPIRED_CANDIDATES];

		// Accomplices' real counters:
		$realAccomplice = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW]))
			$realAccomplice += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_NEW];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED]))
			$realAccomplice += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES]))
			$realAccomplice += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE_EXPIRED_CANDIDATES];

		// Originator's real counters:
		$realOriginator = 0;

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE]))
			$realOriginator += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WO_DEADLINE];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED]))
			$realOriginator += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_EXPIRED];

		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL]))
			$realOriginator += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR_WAIT_CTRL];

		// Auditors' real counters:
		$realAuditor = 0;
		if (isset($arValues[CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED]))
			$realAuditor += (int) $arValues[CTaskCountersProcessor::COUNTER_TASKS_AUDITOR_EXPIRED];

		// Total:
		$realTotal = $realMy + $realAccomplice + $realOriginator + $realAuditor;

		if ($realTotal != $total)
		{
			//$s = '[USER_ID= ' . $userId . '] update master counter to: ' . $realTotal;
			//soundex($s);

			CUserCounter::Increment(
				$userId,
				CTaskCountersProcessor::COUNTER_TASKS_TOTAL,
				$site_id = '**',
				$sendPull = true,
				$realTotal - $total
			);
		}

		if ($realMy != $my)
		{
			CUserCounter::Increment(
				$userId,
				CTaskCountersProcessor::COUNTER_TASKS_MY,
				$site_id = '**',
				$sendPull = true,
				$realMy - $my
			);
		}

		if ($realOriginator != $originator)
		{
			CUserCounter::Increment(
				$userId,
				CTaskCountersProcessor::COUNTER_TASKS_ORIGINATOR,
				$site_id = '**',
				$sendPull = true,
				$realOriginator - $originator
			);
		}

		if ($realAccomplice != $accomplice)
		{
			CUserCounter::Increment(
				$userId,
				CTaskCountersProcessor::COUNTER_TASKS_ACCOMPLICE,
				$site_id = '**',
				$sendPull = true,
				$realAccomplice - $accomplice
			);
		}

		if ($realAuditor != $auditor)
		{
			CUserCounter::Increment(
				$userId,
				CTaskCountersProcessor::COUNTER_TASKS_AUDITOR,
				$site_id = '**',
				$sendPull = true,
				$realAuditor - $auditor
			);
		}
	}


	private static function processUsersCounters($counterId, $operation, $arUsers, $delta)
	{
		if ( ! is_array($arUsers))
		{
			CTaskAssert::logError('[0x9418f293] ');
			return;
		}

		$arUsers = array_unique($arUsers);

		if ($operation === self::OP_DECREMENT)
		{
			foreach ($arUsers as $userId)
			{
				$userId = (int) $userId;

				if ($userId < 1)
				{
					CTaskAssert::logError('[0x8886cc18] ');
					continue;
				}

				//$x = '[USER_ID= ' . $userId . '] ' . $counterId . '--';
				//soundex($x);

				CUserCounter::Decrement(
					$userId,
					$counterId,
					'**',		// $site_id
					false,		// $sendPull
					$delta
				);
			}
		}
		elseif ($operation === self::OP_INCREMENT)
		{
			foreach ($arUsers as $userId)
			{
				$userId = (int) $userId;

				if ($userId < 1)
				{
					CTaskAssert::logError('[0x01f722e7] ');
					continue;
				}

				//$x = '[USER_ID= ' . $userId . '] ' . $counterId . '+=' . $delta;
				//soundex($x);

				CUserCounter::Increment(
					(int) $userId,
					$counterId,
					'**',		// $site_id
					false,		// $sendPull
					$delta
				);
			}
		}
		else
		{
			CTaskAssert::logError('[0x0a5999d4] Invalid operation: ' . $operation);
		}
	}
}
