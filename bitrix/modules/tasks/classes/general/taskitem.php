<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


interface CTaskItemInterface
{
	public function getData($returnEscapedData = true);
	public function getTags();
	public function getFiles();
	public function getDependsOn();
	public function getAllowedActions();
	public function startExecution();
	public function pauseExecution();
	public function defer();
	public function complete();
	public function delete();
	public function update($arNewTaskData);
	public function accept();
	public function delegate($newResponsibleId);
	public function decline($reason = '');
	public function renew();
	public function approve();
	public function disapprove();
	public function getId();		// returns tasks id
	public function getExecutiveUserId();	// returns user id used for rights check
	public function isActionAllowed($actionId);
	public function stopWatch();		// exclude itself from auditors
	public function startWatch();		// include itself to auditors
	public function isUserRole($roleId);

	/**
	 * Remove file attached to task
	 * 
	 * @param integer $fileId
	 * @throws TasksException
	 * @throws CTaskAssertException
	 */
	public function removeAttachedFile($fileId);

	/**
	 * @param integer $format one of constants: 
	 * CTaskItem::DESCR_FORMAT_RAW - give description of task "as is" (HTML or BB-code, depends on task)
	 * CTaskItem::DESCR_FORMAT_HTML - always return HTML (even if task in BB-code)
	 * CTaskItem::DESCR_FORMAT_PLAIN_TEXT - always return plain text (all HTML/BBCODE tags are stripped)
	 * can be omitted. Value by default is CTaskItem::DESCR_FORMAT_HTML.
	 * 
	 * @throws CTaskAssertException if invalid format value given
	 * 
	 * @return string description of the task (HTML will be sanitized accord to task module settings)
	 */
	public function getDescription($format = CTaskItem::DESCR_FORMAT_HTML);
}


final class CTaskItem implements CTaskItemInterface
{
	// Actions
	const ACTION_ACCEPT     = 0x01;
	const ACTION_DECLINE    = 0x02;
	const ACTION_COMPLETE   = 0x03;
	const ACTION_APPROVE    = 0x04;		// closes task
	const ACTION_DISAPPROVE = 0x05;		// perform ACTION_RENEW
	const ACTION_START      = 0x06;
	const ACTION_DELEGATE   = 0x07;
	const ACTION_REMOVE     = 0x08;
	const ACTION_EDIT       = 0x09;
	const ACTION_DEFER      = 0x0A;
	const ACTION_RENEW      = 0x0B;		// switch tasks to new or accepted state (depends on subordination)
	const ACTION_CREATE     = 0x0C;
	const ACTION_CHANGE_DEADLINE     = 0x0D;
	const ACTION_CHECKLIST_ADD_ITEMS = 0x0E;
	const ACTION_ELAPSED_TIME_ADD    = 0x0F;
	const ACTION_CHANGE_DIRECTOR     = 0x10;
	const ACTION_PAUSE               = 0x11;
	const ACTION_START_TIME_TRACKING = 0x12;

	// Roles implemented for managers of users too.
	// So, if some user is responsible in the task, than his manager has responsible role too.
	const ROLE_NOT_A_MEMBER = 0x01;		// not a member of the task
	const ROLE_DIRECTOR     = 0x02;
	const ROLE_RESPONSIBLE  = 0x04;
	const ROLE_ACCOMPLICE   = 0x08;
	const ROLE_AUDITOR      = 0x10;

	const DESCR_FORMAT_RAW        = 0x01;		// give description of task "as is" (HTML or BB-code, depends on task)
	const DESCR_FORMAT_HTML       = 0x02;		// always return HTML (even if task in BB-code)
	const DESCR_FORMAT_PLAIN_TEXT = 0x03;		// always return plain text (all HTML/BBCODE tags are stripped)

	private static $instances = array();

	private static $bSocialNetworkModuleIncluded = null;

	private $taskId = false;
	private $executiveUserId = false;	// User id under which rights will be checked

	// Lazy init:
	private $arTaskData = null;		// Task data

	// Very lazy init (not inited on arTaskData init, inited on demand):
	private $arTaskTags           = null;
	private $arTaskFiles          = null;
	private $arTaskDependsOn      = null;		// Ids of tasks where current tasks depends on
	private $arTaskUserRoles      = null;		// Roles in task of executive user
	private $arTaskAllowedActions = null;		// Allowed actions on task
	private $arTaskDataEscaped    = null;


	public function __construct($taskId, $executiveUserId)
	{
		CTaskAssert::assertLaxIntegers($taskId, $executiveUserId);
		CTaskAssert::assert( ($taskId > 0) && ($executiveUserId > 0) );

		$this->markCacheAsDirty();

		$this->taskId = (int) $taskId;
		$this->executiveUserId = (int) $executiveUserId;
	}


	/**
	 * @param $taskId
	 * @param $executiveUserId
	 * @return CTaskItem returns link to cached object or creates it.
	 */
	public static function getInstance($taskId, $executiveUserId)
	{
		return (self::getInstanceFromPool($taskId, $executiveUserId));
	}


	/**
	 * @param $taskId
	 * @param $executiveUserId
	 * @return CTaskItem returns link to cached object or creates it.
	 */
	public static function getInstanceFromPool($taskId, $executiveUserId)
	{
		CTaskAssert::assertLaxIntegers($taskId, $executiveUserId);
		CTaskAssert::assert( ($taskId > 0) && ($executiveUserId > 0) );

		$key = (int) $taskId . '|' . (int) $executiveUserId;

		// Cache instance in pool
		if ( ! isset(self::$instances[$key]) )
			self::$instances[$key] = new self($taskId, $executiveUserId);

		return (self::$instances[$key]);
	}


	private static function cacheInstanceInPool($taskId, $executiveUserId, $oTaskItemInstance)
	{
		CTaskAssert::assertLaxIntegers($taskId, $executiveUserId);
		CTaskAssert::assert( ($taskId > 0) && ($executiveUserId > 0) );

		$key = (int) $taskId . '|' . (int) $executiveUserId;

		// Cache instance in pool
		self::$instances[$key] = $oTaskItemInstance;
	}

	/**
	 * Create new task and return instance for it
	 *
	 * @param array $arNewTaskData New task fields.
	 * @param integer $executiveUserId Put 1 (admin) to skip rights check.
	 * @throws TasksException - on access denied, task not exists.
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error.
	 *
	 * @return object of class CTaskItem
	 */
	public static function add($arNewTaskData, $executiveUserId)
	{
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($executiveUserId > 0);

		// Use of BB code by default, HTML is deprecated, 
		// but supported for backward compatibility when tasks created
		// from template or as copy of old task with HTML-description.
		if (
			isset($arNewTaskData['DESCRIPTION_IN_BBCODE'])
			&& ($arNewTaskData['DESCRIPTION_IN_BBCODE'] === 'N')	// HTML mode requested
			&& isset($arNewTaskData['DESCRIPTION'])
			&& ($arNewTaskData['DESCRIPTION'] !== '')		// allow HTML mode if there is description
			&& (strpos($arNewTaskData['DESCRIPTION'], '<') !== false)	// with HTML tags
		)
		{
			$arNewTaskData['DESCRIPTION_IN_BBCODE'] = 'N';			// Set HTML mode
		}
		else
			$arNewTaskData['DESCRIPTION_IN_BBCODE'] = 'Y';

		if ( ! isset($arNewTaskData['CREATED_BY']) )
			$arNewTaskData['CREATED_BY'] = $executiveUserId;

		// Check some conditions for non-admins
		if (
			( ! CTasksTools::IsAdmin($executiveUserId) )
			&& ( ! CTasksTools::IsPortalB24Admin($executiveUserId) )
		)
		{
			if (
				($arNewTaskData['RESPONSIBLE_ID'] != $executiveUserId)
				&& ($arNewTaskData['CREATED_BY'] != $executiveUserId)
			)
			{
				throw new TasksException(
					serialize(array(array('text' => GetMessage('TASKS_TASK_CREATE_ACCESS_DENIED'), 'id' => 'ERROR_TASK_CREATE_ACCESS_DENIED'))),
					TasksException::TE_ACCESS_DENIED
				);
			}

			if (isset($arNewTaskData['GROUP_ID']) && ($arNewTaskData['GROUP_ID'] > 0))
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				if (
					! CSocNetFeaturesPerms::CanPerformOperation(
						$executiveUserId, SONET_ENTITY_GROUP, 
						$arNewTaskData['GROUP_ID'], 'tasks', 'create_tasks'
					)
				)
				{
					throw new TasksException(
						serialize(array(array('text' => GetMessage('TASKS_TASK_CREATE_ACCESS_DENIED'), 'id' => 'ERROR_TASK_CREATE_ACCESS_DENIED'))),
						TasksException::TE_ACCESS_DENIED
					);
				}
			}
		}

		if ( ! array_key_exists('GUID', $arNewTaskData) )
			$arNewTaskData['GUID'] = CTasksTools::genUuid();

		$arParams = array(
			'USER_ID'			   => $executiveUserId,
			'CHECK_RIGHTS_ON_FILES' => true
		);

		$o = new CTasks();
		/** @noinspection PhpDeprecationInspection */
		$rc = $o->Add($arNewTaskData, $arParams);
		if ( ! ($rc > 0) )
		{
			throw new TasksException(
				serialize($o->GetErrors()),
				TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
			);
		}

		return (new CTaskItem( (int) $rc, $executiveUserId));
	}

	/**
	 * Duplicate task and return an instance of the clone.
	 *
	 * @param mixed[] $overrideTaskData Task data needs to be overrided externally.
	 * @param mixed[] $parameters Various set of parameters.
	 * 
	 * 		<li> CLONE_CHILD_TASKS boolean 		clone subtasks or not
	 * 		<li> CLONE_CHECKLIST_ITEMS boolean 	clone check list items or not
	 * 		<li> CLONE_TAGS boolean 			clone tags or not
	 * 		<li> CLONE_REMINDERS boolean 		clone reminders or not
	 * 		<li> CLONE_TASK_DEPENDENCY boolean	clone previous tasks or not
	 * 		<li> CLONE_FILES boolean			clone files or not
	 * 
	 * @throws TasksException - on access denied, task not found.
	 * @throws CTaskAssertException.
	 * @throws Exception - on unexpected error.
	 *
	 * @return CTaskItem[]
	 */
	public function duplicate($overrideTaskData = array(), $parameters = array(
		'CLONE_CHILD_TASKS' => true,
		'CLONE_CHECKLIST_ITEMS' => true,
		'CLONE_TAGS' => true,
		'CLONE_REMINDERS' => true,
		'CLONE_TASK_DEPENDENCY' => true,
		'CLONE_FILES' => true
	))
	{
		if(!is_array($overrideTaskData))
			$overrideTaskData = array();

		if(!is_array($parameters))
			$parameters = array();
		if(!isset($parameters['CLONE_CHILD_TASKS']))
			$parameters['CLONE_CHILD_TASKS'] = true;
		if(!isset($parameters['CLONE_CHECKLIST_ITEMS']))
			$parameters['CLONE_CHECKLIST_ITEMS'] = true;
		if(!isset($parameters['CLONE_TAGS']))
			$parameters['CLONE_TAGS'] = true;
		if(!isset($parameters['CLONE_REMINDERS']))
			$parameters['CLONE_REMINDERS'] = true;
		if(!isset($parameters['CLONE_TASK_DEPENDENCY']))
			$parameters['CLONE_TASK_DEPENDENCY'] = true;
		if(!isset($parameters['CLONE_FILES']))
			$parameters['CLONE_FILES'] = true;

		$result = array();
		$data = $this->getData(false); // ensure we have access to the task
		if(is_array($data))
		{
			$data = array_merge($data, $overrideTaskData);

			// drop unwanted
			unset($data['ID']);
			unset($data['GUID']);

			// detach forum, if any
			unset($data['FORUM_TOPIC_ID']);
			unset($data['COMMENTS_COUNT']);

			// clean dates
			unset($data['CREATED_DATE']);
			unset($data['CHANGED_DATE']);
			unset($data['VIEWED_DATE']);
			unset($data['STATUS_CHANGED_DATE']);

			unset($data['CHANGED_BY']);

			$files = array();
			if(is_array($data['UF_TASK_WEBDAV_FILES']) && !empty($data['UF_TASK_WEBDAV_FILES']))
				$files = $data['UF_TASK_WEBDAV_FILES'];

			unset($data['UF_TASK_WEBDAV_FILES']);

			$clone = static::add($data, $this->getExecutiveUserId());
			$taskDupId = $clone->getId();

			if(intval($taskDupId))
			{
				$result[$clone->getId()] = $clone;

				if($parameters['CLONE_CHECKLIST_ITEMS'])
				{
					list($arChecklistItems, $arMetaData) = CTaskCheckListItem::fetchList($this, array('SORT_INDEX' => 'ASC'));
					unset($arMetaData);

					foreach ($arChecklistItems as $oChecklistItem)
					{
						$cliData = $oChecklistItem->getData();
						$cliCloneData = array(
							'TITLE' => 				$cliData['TITLE'],
							'IS_COMPLETE' => 		$cliData['IS_COMPLETE'],
							'SORT_INDEX' => 			$cliData['SORT_INDEX']
						);

						CTaskCheckListItem::add($clone, $cliCloneData);
					}
				}

				if($parameters['CLONE_TAGS'])
				{
					$tags = $this->getTags();
					if(is_array($tags))
					{
						foreach($tags as $tag)
						{
							if((string) $tag != '')
							{
								$oTag = new CTaskTags();
								$oTag->Add(array(
									'TASK_ID' => 	$taskDupId,
									'NAME' => 		$tag
								), $this->getExecutiveUserId());
							}
						}
					}
				}

				if($parameters['CLONE_REMINDERS'])
				{
					$res = CTaskReminders::GetList(false, array('TASK_ID' => $this->getId()));
					while($item = $res->fetch())
					{
						$item['TASK_ID'] = $taskDupId;
						$item['USER_ID'] = $this->getExecutiveUserId();

						$oReminder = new CTaskReminders();
						$oReminder->Add($item);
					}
				}

				if($parameters['CLONE_TASK_DEPENDENCY'])
				{
					$res = CTaskDependence::GetList(array(), array('TASK_ID' => $this->getId()));
					while($item = $res->fetch())
					{
						$depInstance = new CTaskDependence();
						if(is_array($item))
						{
							$depInstance->Add(array(
								'TASK_ID' => $taskDupId,
								'DEPENDS_ON_ID' => $item['DEPENDS_ON_ID']
							));
						}
					}
				}

				if($parameters['CLONE_FILES'] && !empty($files) && \Bitrix\Main\Loader::includeModule('disk'))
				{
					// find which files are new and which are old
					$old = array();
					$new = array();
					foreach($files as $fileId)
					{
						if((string) $fileId)
						{
							if(strpos($fileId, 'n') === 0)
								$new[] = $fileId;
							else
								$old[] = $fileId;
						}
					}

					if(!empty($old))
					{
						$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
						$old = $userFieldManager->cloneUfValuesFromAttachedObject($old, $this->getExecutiveUserId());

						if(is_array($old) && !empty($old))
						{
							$new = array_merge($new, $old);
						}
					}

					if(!empty($new))
						$clone->update(array('UF_TASK_WEBDAV_FILES' => $new));
				}

				if($parameters['CLONE_CHILD_TASKS'])
				{
					$clones = $this->duplicateChildTasks($clone);
					if(is_array($clones))
					{
						foreach($clones as $cId => $cInst)
							$result[$cId] = $cInst;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Duplicate subtasks of the current task.
	 *
	 * @param CTaskItem $cloneTaskInstance An instance of task clone that subtasks will be attached to.
	 * 
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 */
	public function duplicateChildTasks($cloneTaskInstance)
	{
		CTaskAssert::assert($cloneTaskInstance instanceof CTaskItemInterface);

		$duplicates = array();

		$data = $this->getData(false); // check rights here
		if($data)
		{
			// getting tree data and checking for dead loops
			$queue = array();
			$this->duplicateChildTasksLambda($this, $queue);

			$idMap = array();
			foreach($queue as $taskInstance)
			{
				$data = $taskInstance->getData();

				$cloneInstances = $taskInstance->duplicate(array(
					'PARENT_ID' => isset($idMap[$data['PARENT_ID']]) ? $idMap[$data['PARENT_ID']] : $cloneTaskInstance->getId()
				), array(
					'CLONE_CHILD_TASKS' => false
				));
				if(is_array($cloneInstances) && !empty($cloneInstances))
				{
					$cloneInstance = array_shift($cloneInstances);

					$idMap[$taskInstance->getId()] = $cloneInstance->getId();
					$duplicates[$taskInstance->getId()] = $cloneInstance;
				}
			}
		}

		return $duplicates;
	}

	protected function duplicateChildTasksLambda($parentTaskInstance, &$queue)
	{
		// have to walk task tree recursively, because no tree structure is currently provided
		list($items, $res) = static::fetchList($this->getExecutiveUserId(), array(), array('PARENT_ID' => $parentTaskInstance->getId()), array(), array('*', 'UF_*'));
		unset($res);
		foreach($items as $taskInstance)
		{
			if(isset($queue[$taskInstance->getId()]))
			{
				throw new TasksException(
					'An endless loop detected when attempting to duplicate subtasks (task '.intval($parentTaskInstance->getId()).' met twice)',
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
				);
			}

			$queue[$taskInstance->getId()] = $taskInstance;
			$this->duplicateChildTasksLambda($taskInstance, $queue);
		}
	}

	/**
	 * Create a task by a template.
	 *
	 * @param integer $templateId - Id of task template.
	 * @param integer $executiveUserId User id. Put 1 here to skip rights.
	 * @param mixed[] $overrideTaskData Task data needs to be overrided externally.
	 * @param mixed[] $parameters Various set of parameters.
	 * 
	 * 		<li> TEMPLATE_DATA mixed[] 			pre-cached data, if available we can get rid of additional queries
	 * 		<li> CREATE_CHILD_TASKS boolean 	if false, sub-tasks wont be created
	 * 		<li> CREATE_MULTITASK boolean		if false, discards template rule of "copying task to several responsibles"
	 * 		<li> BEFORE_ADD_CALLBACK callable 		callback called before each task added, allows to modify data passed to CTaskItem::add()
	 * 
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 */
	public static function addByTemplate($templateId, $executiveUserId, $overrideTaskData = array(), $parameters = array(
		'TEMPLATE_DATA' => array(), 
		'CREATE_CHILD_TASKS' => true, 
		'CREATE_MULTITASK' => true,

		'BEFORE_ADD_CALLBACK' => null,
		'SPAWNED_BY_AGENT' => false
	))
	{
		CTaskAssert::assertLaxIntegers($executiveUserId);
		CTaskAssert::assert($executiveUserId > 0);

		global $DB;

		$templateId = (int) $templateId;
		if ( ! $templateId )
			return array();	// template id not set

		if(!is_array($overrideTaskData))
			$overrideTaskData = array();

		if(!is_array($parameters))
			$parameters = array();
		if(!isset($parameters['CREATE_CHILD_TASKS']))
			$parameters['CREATE_CHILD_TASKS'] = true;
		if(!isset($parameters['CREATE_MULTITASK']))
			$parameters['CREATE_MULTITASK'] = true;
		if(!isset($parameters['BEFORE_ADD_CALLBACK']))
			$parameters['BEFORE_ADD_CALLBACK'] = null;
		if(!isset($parameters['SPAWNED_BY_AGENT']))
			$parameters['SPAWNED_BY_AGENT'] = false;

		// read template data

		if(is_array($parameters['TEMPLATE_DATA']) && !empty($parameters['TEMPLATE_DATA']))
			$arTemplate = $parameters['TEMPLATE_DATA'];
		else
		{
			$arFilter   = array('ID' => $templateId);
			$rsTemplate = CTaskTemplates::GetList(array(), $arFilter);
			$arTemplate = $rsTemplate->Fetch();

			if ( ! $arTemplate )
				return array();	// nothing to do
		}

		$arTemplate = array_merge($arTemplate, $overrideTaskData);

		if(!isset($arTemplate['CHECK_LIST']))
		{
			// get template checklist
			$arTemplate['CHECK_LIST'] = array();
			$res = \Bitrix\Tasks\Template\CheckListItemTable::getList(array(
				'filter' => array('TEMPLATE_ID' => $templateId), 
				'select' => array('IS_COMPLETE', 'SORT_INDEX', 'TITLE')
			));
			while($item = $res->fetch())
			{
				$arTemplate['CHECK_LIST'][] = $item;
			}
		}

		//////////////////////////////////////////////
		//////////////////////////////////////////////
		//////////////////////////////////////////////

		unset($arTemplate['STATUS']);

		$arFields = $arTemplate;

		$arFields['CREATED_DATE'] = date(
			$DB->DateFormatToPHP(CSite::GetDateFormat('FULL')), 
			time() + CTimeZone::GetOffset()
		);

		$arFields['ACCOMPLICES']  = unserialize($arFields['ACCOMPLICES']);
		$arFields['AUDITORS']     = unserialize($arFields['AUDITORS']);
		$arFields['TAGS']         = unserialize($arFields['TAGS']);
		$arFields['FILES']        = unserialize($arFields['FILES']);
		$arFields['DEPENDS_ON']   = unserialize($arFields['DEPENDS_ON']);
		$arFields['REPLICATE']    = 'N';
		$arFields['CHANGED_BY']   = $arFields['CREATED_BY'];
		$arFields['CHANGED_DATE'] = $arFields['CREATED_DATE'];

		if ( ! $arFields['ACCOMPLICES'] )
			$arFields['ACCOMPLICES'] = array();

		if ( ! $arFields['AUDITORS'] )
			$arFields['AUDITORS'] = array();

		unset($arFields['ID'], $arFields['REPLICATE'], $arFields['REPLICATE_PARAMS']);

		if ($arTemplate['DEADLINE_AFTER'])
		{
			$deadlineAfter = $arTemplate['DEADLINE_AFTER'] / (24 * 60 * 60);
			$deadline = strtotime(date('Y-m-d 00:00') . ' +' . $deadlineAfter . ' days');
			$arFields['DEADLINE'] = date(
				$DB->DateFormatToPHP(CSite::GetDateFormat('SHORT')), 
				$deadline
			);
		}

		$multitaskMode = false;
		if($parameters['CREATE_MULTITASK'])
		{
			$arFields['RESPONSIBLES'] = unserialize($arFields['RESPONSIBLES']);

			// copy task to multiple responsibles
			if ($arFields['MULTITASK'] == 'Y' && !empty($arFields['RESPONSIBLES']))
			{
				$arFields['RESPONSIBLE_ID'] = $arFields['CREATED_BY'];
				$multitaskMode = true;
			}
			else
			{
				$arFields['RESPONSIBLES'] = array();
			}
		}
		else
		{
			$arFields['MULTITASK'] = 'N';
			$arFields['RESPONSIBLES'] = array();
		}

		$arFields['FORKED_BY_TEMPLATE_ID'] = $templateId;

		// add main task to the create list
		$tasksToCreate = array(
			$arFields
		);

		// if MULTITASK where set to Y, create a duplicate task for each of RESPONSIBLES
		if (!empty($arFields['RESPONSIBLES']))
		{
			$arFields['MULTITASK'] = 'N';

			foreach ($arFields['RESPONSIBLES'] as $responsible)
			{
				$arFields['RESPONSIBLE_ID'] = $responsible;
				$tasksToCreate[] = $arFields;
			}
		}

		// get sub-templates
		$subTasksToCreate = array();
		if($parameters['CREATE_CHILD_TASKS'] !== false)
			$subTasksToCreate = static::getChildTemplateData($templateId);

		$created = array();

		// first, create ROOT tasks
		$multitaskTaskId = false;
		$i = 0;
		foreach($tasksToCreate as $arFields)
		{
			if($multitaskMode && $i > 0) // assign parent
			{
				if($multitaskTaskId)
				{
					// all following tasks will be subtasks of a base task in case of MULTITASK was turned on
					$arFields['PARENT_ID'] = $multitaskTaskId;
				}
				else
				{
					break; // no child tasks will be created, because parent task failed to be created
				}
			}

			$add = true;
			if(is_callable($parameters['BEFORE_ADD_CALLBACK']))
			{
				$result = call_user_func_array($parameters['BEFORE_ADD_CALLBACK'], array(&$arFields));
				if($result === false)
					$add = false;
			}

			if($add)
			{
				// temporary commented out, because there is currently no way to pass 
				// 'SPAWNED_BY_AGENT' => true 
				// parameter to CTaskTemplate::add() :

				//$taskInstance = static::add($arFields, $executiveUserId);
				//$taskId = $taskInstance->getId();

				$task = new CTasks();
				$taskId = $task->Add(
					$arFields,
					array(
						'SPAWNED_BY_AGENT' => !!$parameters['SPAWNED_BY_AGENT'],
						'USER_ID'          => $executiveUserId
					)
				);

				if(intval($taskId))
				{
					$taskInstance = static::getInstance($taskId, $executiveUserId);

					// the first task should be mom in case of multitasking
					if($multitaskMode && $i == 0)
						$multitaskTaskId = $taskId;

					// check list items for root task
					foreach($arTemplate['CHECK_LIST'] as $item)
						CTaskCheckListItem::add($taskInstance, $item);

					$created[$taskId] = $taskInstance;

					if(!empty($subTasksToCreate))
					{
						$createdSubtasks = $taskInstance->addChildTasksByTemplate($templateId, array(
							'CHILD_TEMPLATE_DATA' =>	$subTasksToCreate,

							// transfer some parameters
							'BEFORE_ADD_CALLBACK' =>	$parameters['BEFORE_ADD_CALLBACK'],
							'SPAWNED_BY_AGENT' =>		$parameters['SPAWNED_BY_AGENT'],
						));

						if(is_array($createdSubtasks) && !empty($createdSubtasks))
						{
							foreach($createdSubtasks as $ctId => $ctInst)
								$created[$ctId] = $ctInst;
						}
					}
				}
			}

			$i++;
		}

		return $created;
	}

	/**
	 * Create sub-task by sub-templates of a certain root template.
	 *
	 * @param integer $templateId Id of task template.
	 * @param integer $taskId Id of task sub-tasks will attach to.
	 * @param mixed[] $parameters Various set of parameters.
	 * 
	 * 		<li> CHILD_TEMPLATE_DATA mixed[] 		pre-cached data, if available we can get rid of additional queries
	 * 		<li> BEFORE_ADD_CALLBACK callable 		callback called before each task added, allows to modify data passed to CTaskItem::add()
	 * 
	 * @throws TasksException - on access denied, task not found
	 * @throws CTaskAssertException
	 * @throws Exception - on unexpected error
	 *
	 * @return CTaskItem[]
	 */
	public function addChildTasksByTemplate($templateId, $parameters = array(
		'CHILD_TEMPLATE_DATA' =>	array(),

		'BEFORE_ADD_CALLBACK' =>	null,
		'SPAWNED_BY_AGENT' =>		false
	))
	{
		$templateId = (int) $templateId;
		if ( ! $templateId )
			return array();	// template id not set

		$taskId = $this->getId();

		// ensure we have access to this task
		$data = $this->getData(false);

		if(is_array($data))
		{
			if(!is_array($parameters))
				$parameters = array();

			if(!isset($parameters['BEFORE_ADD_CALLBACK']))
				$parameters['BEFORE_ADD_CALLBACK'] = null;
			if(!isset($parameters['SPAWNED_BY_AGENT']))
				$parameters['SPAWNED_BY_AGENT'] = false;

			// CHILD_TEMPLATE_DATA is used to pass pre-cached data to a function to avoid unnecessary db quires
			if(!is_array($parameters['CHILD_TEMPLATE_DATA']) || empty($parameters['CHILD_TEMPLATE_DATA']))
				$parameters['CHILD_TEMPLATE_DATA'] = $this->getChildTemplateData($templateId);

			$created = array();

			if(!empty($parameters['CHILD_TEMPLATE_DATA']))
			{
				$templateId2TaskId = array($templateId => $taskId);
				$creationOrder = array();
				$walkQueue = array($templateId);
				$treeBundles = array();

				// restruct array to avioid recursion. we should NOT lay on ID values

				foreach($parameters['CHILD_TEMPLATE_DATA'] as $subTemplate)
					$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];

				while(!empty($walkQueue))
				{
					$topTemplate = array_shift($walkQueue);

					if(is_array($treeBundles[$topTemplate]))
					{
						foreach($treeBundles[$topTemplate] as $parent => $template)
						{
							$walkQueue[] = $template;
							$creationOrder[] = $template;
						}
					}
					unset($treeBundles[$topTemplate]);
				}

				foreach($creationOrder as $subTemplateId)
				{
					$data = $parameters['CHILD_TEMPLATE_DATA'][$subTemplateId];

					if(!intval($templateId2TaskId[$data['BASE_TEMPLATE_ID']])) // smth went wrong previously, skip this branch
						continue;

					$createdTasks = static::addByTemplate($subTemplateId, $this->getExecutiveUserId(), array('PARENT_ID' => $templateId2TaskId[$data['BASE_TEMPLATE_ID']]), array(
						'TEMPLATE_DATA' => $data,
						'CREATE_CHILD_TASKS' =>		false,
						'CREATE_MULTITASK' =>		false,

						'BEFORE_ADD_CALLBACK' =>	$parameters['BEFORE_ADD_CALLBACK'],
						'SPAWNED_BY_AGENT' =>		$parameters['SPAWNED_BY_AGENT'],
					));

					if(is_array($createdTasks))
					{
						foreach($createdTasks as $ctId => $ctInst)
							$created[$ctId] = $ctInst;

						$firstTask = array_shift($createdTasks);
						$templateId2TaskId[$subTemplateId] = $firstTask->getId(); // get only the first, because it is "main" task
					}
				}
			}

			return $created;
		}
		else
			return array();
	}

	protected function getChildTemplateData($templateId)
	{
		$templateId = (int) $templateId;
		if ( ! $templateId )
			return array();	// template id not set

		$subTasksToCreate = array();

		$res = CTaskTemplates::GetList(array('BASE_TEMPLATE_ID' => 'asc'), array('BASE_TEMPLATE_ID' => $templateId), false, array('INCLUDE_TEMPLATE_SUBTREE' => true), array('*', 'UF_*', 'BASE_TEMPLATE_ID'));
		while($item = $res->fetch())
		{
			if($item['ID'] == $templateId)
				continue;

			$subTasksToCreate[$item['ID']] = $item;
		}

		// get check lists
		$res = \Bitrix\Tasks\Template\CheckListItemTable::getListByTemplateDependency($templateId, array(
			'order' => array('SORT' => 'ASC'),
			'select' => array('ID', 'TEMPLATE_ID', 'IS_COMPLETE', 'SORT_INDEX', 'TITLE')
		));
		while($item = $res->fetch())
		{
			if(isset($subTasksToCreate[$item['TEMPLATE_ID']]))
			{
				$clId = $item['ID'];
				$tmpId = $item['TEMPLATE_ID'];
				unset($item['ID']);
				unset($item['TEMPLATE_ID']);
				$subTasksToCreate[$tmpId]['CHECK_LIST'][$clId] = $item;
			}
		}

		return $subTasksToCreate;
	}

	public function __wakeup()
	{
		$this->markCacheAsDirty();
	}


	public function __sleep()
	{
		$this->markCacheAsDirty();
		return (array('taskId', 'executiveUserId', 'arTaskData', 
			'arTaskAllowedActions', 'arTaskUserRoles', 'arTaskTags',
			'arTaskFiles', 'arTaskDependsOn'
		));
	}


	// prevent clone of object
	private function __clone(){}


	public function getId()
	{
		return ($this->taskId);
	}


	public function getExecutiveUserId()
	{
		return ($this->executiveUserId);
	}


	/**
	 * Synonym for getData();
	 * @deprecated
	 */
	public function getTaskData($returnEscapedData = true)
	{
		return ($this->getData($returnEscapedData));
	}

	public function checkCanRead()
	{
		/** @noinspection PhpDeprecationInspection */
		$arTask = CTasks::GetList(array(), array(
			'ID' => (int) $this->taskId,
			'CHECK_PERMISSIONS' => 'Y'
		), array("ID"), array(
			'USER_ID' => $this->executiveUserId
		))->fetch();

		return (is_array($arTask) && isset($arTask['ID']));
	}

	/**
	 * Get task data (read from DB on demand)
	 */
	public function getData($returnEscapedData = true)
	{
		// Preload data, if it isn't in cache
		if ($this->arTaskData === null)
		{
			$this->markCacheAsDirty();

			// Load task data
			$bCheckPermissions = true;
			$arParams = array(
				'USER_ID'        => $this->executiveUserId,
				'returnAsArray'  => true,
				'bSkipExtraData' => true
			);

			/** @noinspection PhpDeprecationInspection */
			$arTask = CTasks::getById($this->taskId, $bCheckPermissions, $arParams);

			if ( ! (is_array($arTask) && isset($arTask['ID'])) )
				throw new TasksException('Task not found or not accessible', TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE);

			$this->arTaskData = $arTask;
		}

		if ($returnEscapedData)
		{
			// Prepare escaped data on-demand
			if ($this->arTaskDataEscaped === null)
			{
				foreach ($this->arTaskData as $field => $value)
				{
					$this->arTaskDataEscaped['~' . $field] = $value;

					if ($field === 'DESCRIPTION')
						$this->arTaskDataEscaped[$field] = $this->getDescription();
					elseif (is_numeric($value) || ( ! is_string($value) ) )
						$this->arTaskDataEscaped[$field] = $value;
					else
						$this->arTaskDataEscaped[$field] = htmlspecialcharsex($value);
				}
			}

			return ($this->arTaskDataEscaped);
		}
		else
			return ($this->arTaskData);
	}


	public function getDescription($format = self::DESCR_FORMAT_HTML)
	{
		$rc = null;

		$format = intval($format);

		CTaskAssert::assert(in_array(
			$format,
			array(self::DESCR_FORMAT_RAW, self::DESCR_FORMAT_HTML, self::DESCR_FORMAT_PLAIN_TEXT),
			true
		));

		$arTask = $this->getData($bSpecialChars = false);

		$description = $arTask['DESCRIPTION'];

		if ($format === self::DESCR_FORMAT_RAW)
			return ($description);

		// Now, convert description to HTML
		if ($arTask['DESCRIPTION_IN_BBCODE'] === 'Y')
		{
			$parser = new CTextParser();
			$description = str_replace(
				"\t",
				' &nbsp; &nbsp;',
				$parser->convertText($description)
			);
		}
		else
			$description = CTasksTools::SanitizeHtmlDescriptionIfNeed($description);

		if ($format === self::DESCR_FORMAT_HTML)
			$rc = $description;
		elseif ($format === self::DESCR_FORMAT_PLAIN_TEXT)
		{
			$rc = strip_tags(
				str_replace(
					array('<br>', '<br/>', '<br />'),
					"\n",
					$description
				)
			);
		}
		else
		{
			CTaskAssert::log(
				'CTaskItem->getTaskDescription(): unexpected format: ' . $format,
				CTaskAssert::ELL_ERROR
			);

			CTaskAssert::assert(false);
		}

		return ($rc);
	}


	public function getTags()
	{
		if ($this->arTaskTags === null)
		{
			$rsTags = CTaskTags::GetList(
				array('NAME' => 'ASC'),
				array('TASK_ID' => $this->taskId)
			);

			$arTags = array();

			while ($arTag = $rsTags->fetch())
				$arTags[] = $arTag['NAME'];

			$this->arTaskTags = $arTags;
		}

		return ($this->arTaskTags);
	}


	public function getFiles()
	{
		if ($this->arTaskFiles === null)
		{
			$rsFiles = CTaskFiles::GetList(
				array(),
				array('TASK_ID' => $this->taskId)
			);

			$this->arTaskFiles = array();

			while ($arFile = $rsFiles->fetch())
				$this->arTaskFiles[] = $arFile['FILE_ID'];
		}

		return ($this->arTaskFiles);
	}


	/**
	 * Get id of tasks that current task depends on
	 */
	public function getDependsOn()
	{
		if ($this->arTaskDependsOn === null)
		{
			$rsDependsOn = CTaskDependence::GetList(
				array(),
				array('TASK_ID' => $this->taskId)
			);

			$arTaskDependsOn = array();

			while ($arDependsOn = $rsDependsOn->fetch())
				$arTaskDependsOn[] = $arDependsOn['DEPENDS_ON_ID'];

			$this->arTaskDependsOn = $arTaskDependsOn;
		}

		return ($this->arTaskDependsOn);
	}


	/**
	 * @deprecated
	 */
	public function getAllowedTaskActions()
	{
		return ($this->getAllowedActions());
	}


	/**
	 * @deprecated
	 */
	public function getAllowedTaskActionsAsStrings()
	{
		return ($this->getAllowedActions($bReturnAsStrings = true));
	}


	public function getAllowedActions($bReturnAsStrings = false)
	{
		if ($bReturnAsStrings)
		{
			return ($this->getAllowedActionsAsStrings());
		}

		// Lazy load and cache allowed actions list
		if ($this->arTaskAllowedActions === null)
		{
			$arTaskData = $this->getData($bSpecialChars = false);
			$bmUserRoles = $this->getUserRoles();
			$arBaseAllowedActions = self::getBaseAllowedActions();
			$arActualBaseAllowedActions = $arBaseAllowedActions[$arTaskData['REAL_STATUS']];

			$arAllowedActions = array();

			$mergesCount = 0;
			if(is_array($arActualBaseAllowedActions))
			{
				foreach ($arActualBaseAllowedActions as $userRole => $arActions)
				{
					if ($userRole & $bmUserRoles)
					{
						$arAllowedActions = array_merge($arAllowedActions, $arActions);
						++$mergesCount;
					}
				}
			}

			if ($mergesCount > 1)
				$arAllowedActions = array_unique($arAllowedActions);

			$isAdmin = CTasksTools::IsAdmin($this->executiveUserId)
				|| CTasksTools::IsPortalB24Admin($this->executiveUserId);

			if (self::$bSocialNetworkModuleIncluded === null)
				self::$bSocialNetworkModuleIncluded = CModule::IncludeModule('socialnetwork');

			// Admin always can edit and remove, also implement rights from task group
			if ( ! in_array(self::ACTION_REMOVE, $arAllowedActions, true) )
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				if (
					$isAdmin
					|| (
						($arTaskData['GROUP_ID'] > 0)
						&& self::$bSocialNetworkModuleIncluded
						&& CSocNetFeaturesPerms::CanPerformOperation(
						$this->executiveUserId, SONET_ENTITY_GROUP,
							$arTaskData['GROUP_ID'], 'tasks', 'delete_tasks'
						)
					)
				)
				{
					$arAllowedActions[] = self::ACTION_REMOVE;
				}
			}

			if ( ! in_array(self::ACTION_EDIT, $arAllowedActions, true) )
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				if (
					$isAdmin
					|| (
						($arTaskData['GROUP_ID'] > 0)
						&& self::$bSocialNetworkModuleIncluded
						&& CSocNetFeaturesPerms::CanPerformOperation(
							$this->executiveUserId, SONET_ENTITY_GROUP, 
							$arTaskData['GROUP_ID'], 'tasks', 'edit_tasks'
						)
					)
				)
				{
					$arAllowedActions[] = self::ACTION_EDIT;
				}
			}

			// Precache result of slow 'in_array' function
			$bCanEdit = in_array(self::ACTION_EDIT, $arAllowedActions, true);

			// User can change deadline, if ...
			if (
				$isAdmin
				// he can edit task
				|| $bCanEdit
				|| (
					// or this options is set to Y and ...
					($arTaskData['ALLOW_CHANGE_DEADLINE'] === 'Y')
					// current user is responsible or current user is manager of responsible
					&& (self::ROLE_RESPONSIBLE & $bmUserRoles)
				)
			)
			{
				$arAllowedActions[] = self::ACTION_CHANGE_DEADLINE;
			}

			// If user can edit task, he can also add elapsed time and checklist items
			if ($isAdmin || $bCanEdit)
			{
				$arAllowedActions[] = self::ACTION_ELAPSED_TIME_ADD;
				$arAllowedActions[] = self::ACTION_CHECKLIST_ADD_ITEMS;
			}

			// Director can change director, and user who can edit can
			if (
				$isAdmin
				|| $bCanEdit
				|| (self::ROLE_DIRECTOR & $bmUserRoles)
			)
			{
				$arAllowedActions[] = self::ACTION_CHANGE_DIRECTOR;
			}

			if ($arTaskData['ALLOW_TIME_TRACKING'] === 'Y')
			{
				// User can do time tracking, if he is participant in the task
				if (
					($this->executiveUserId == $arTaskData['RESPONSIBLE_ID'])
					|| ( ! empty($arTaskData['ACCOMPLICES']) && in_array($this->executiveUserId, $arTaskData['ACCOMPLICES']) )
				)
				{
					$arAllowedActions[] = self::ACTION_START_TIME_TRACKING;
				}
			}

			$this->arTaskAllowedActions = array_values(array_unique($arAllowedActions));
		}

		return ($this->arTaskAllowedActions);
	}


	private function getAllowedActionsAsStrings()
	{
		static $arStringsMap = array(
			self::ACTION_ACCEPT     => 'ACTION_ACCEPT',
			self::ACTION_DECLINE    => 'ACTION_DECLINE',
			self::ACTION_COMPLETE   => 'ACTION_COMPLETE',
			self::ACTION_APPROVE    => 'ACTION_APPROVE',
			self::ACTION_DISAPPROVE => 'ACTION_DISAPPROVE',
			self::ACTION_START      => 'ACTION_START',
			self::ACTION_PAUSE      => 'ACTION_PAUSE',
			self::ACTION_DELEGATE   => 'ACTION_DELEGATE',
			self::ACTION_REMOVE     => 'ACTION_REMOVE',
			self::ACTION_EDIT       => 'ACTION_EDIT',
			self::ACTION_DEFER      => 'ACTION_DEFER',
			self::ACTION_RENEW      => 'ACTION_RENEW',
			self::ACTION_CREATE     => 'ACTION_CREATE',
			self::ACTION_CHANGE_DEADLINE        => 'ACTION_CHANGE_DEADLINE',
			self::ACTION_CHECKLIST_ADD_ITEMS    => 'ACTION_CHECKLIST_ADD_ITEMS',
			self::ACTION_CHANGE_DIRECTOR        => 'ACTION_CHANGE_DIRECTOR',
			self::ACTION_ELAPSED_TIME_ADD       => 'ACTION_ELAPSED_TIME_ADD',
			self::ACTION_START_TIME_TRACKING    => 'ACTION_START_TIME_TRACKING'
		);

		$arAllowedActions = $this->getAllowedActions();

		$arResult = array();

		foreach ($arStringsMap as $actionCode => $actionString)
		{
			if (in_array($actionCode, $arAllowedActions, true))
				$arResult[$actionString] = true;	// action is allowed
			else
				$arResult[$actionString] = false;	// not allowed
		}

		return ($arResult);
	}


	public function isActionAllowed($actionId)
	{
		$bActionAllowed = false;

		if (in_array(intval($actionId), $this->getAllowedActions(), true))
			$bActionAllowed = true;

		return ($bActionAllowed);
	}


	/**
	 * Remove task
	 */
	public function delete()
	{
		$this->proceedAction(self::ACTION_REMOVE);
	}


	/**
	 * Delegate task to some responsible person (only subordinate users allowed)
	 * 
	 * @param integer $newResponsibleId user id of new responsible person
	 * @throws TasksException, including codes TE_TRYED_DELEGATE_TO_WRONG_PERSON,
	 * TE_ACTION_NOT_ALLOWED, TE_ACTION_FAILED_TO_BE_PROCESSED, 
	 * TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE
	 */
	public function delegate($newResponsibleId)
	{
		$this->proceedAction(
			self::ACTION_DELEGATE,
			array('RESPONSIBLE_ID' => $newResponsibleId)
		);
	}


	/**
	 * Decline task
	 * 
	 * @param string $reason reason by which task declined
	 * @throws TasksException, including codes TE_ACTION_NOT_ALLOWED,
	 * TE_ACTION_FAILED_TO_BE_PROCESSED, 
	 * TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE
	 * 
	 * @deprecated
	 */
	public function decline($reason = '')
	{
		$this->proceedAction(
			self::ACTION_DECLINE,
			array('DECLINE_REASON' => $reason)
		);
	}


	public function startExecution()
	{
		$this->proceedAction(self::ACTION_START);
	}


	public function pauseExecution()
	{
		$this->proceedAction(self::ACTION_PAUSE);
	}


	public function defer()
	{
		$this->proceedAction(self::ACTION_DEFER);
	}



	public function complete()
	{
		$this->proceedAction(self::ACTION_COMPLETE);
	}


	public function update($arNewTaskData)
	{
		$this->proceedAction(
			self::ACTION_EDIT,
			array('FIELDS' => $arNewTaskData)
		);
	}


	public function stopWatch()
	{
		// Force reload cache
		$this->markCacheAsDirty();
		$arTask = $this->getData($bEscaped = false);

		$key = array_search($this->executiveUserId, $arTask['AUDITORS']);

		// Am I auditor?
		if ($key !== false)
		{
			unset($arTask['AUDITORS'][$key]);
			$arFields = array('AUDITORS' => $arTask['AUDITORS']);
			$this->markCacheAsDirty();
			$o = new CTasks();
			$arParams = array(
				'USER_ID'               => $this->executiveUserId,
				'CHECK_RIGHTS_ON_FILES' => true
			);

			/** @noinspection PhpDeprecationInspection */
			if ($o->update($this->taskId, $arFields, $arParams) !== true)
			{
				throw new TasksException(
					serialize($o->GetErrors()),
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
				);
			}
		}
	}


	public function startWatch()
	{
		// Force reload cache
		$this->markCacheAsDirty();
		$arTask = $this->getData($bEscaped = false);

		// Am I auditor?
		if ( ! in_array($this->executiveUserId, $arTask['AUDITORS']))
		{
			$arTask['AUDITORS'][] = $this->executiveUserId;
			$arFields = array('AUDITORS' => $arTask['AUDITORS']);
			$this->markCacheAsDirty();
			$o = new CTasks();
			$arParams = array(
				'USER_ID'               => $this->executiveUserId,
				'CHECK_RIGHTS_ON_FILES' => true
			);

			/** @noinspection PhpDeprecationInspection */
			if ($o->update($this->taskId, $arFields, $arParams) !== true)
			{
				throw new TasksException(
					serialize($o->GetErrors()),
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
				);
			}
		}
	}


	/**
	 * @deprecated
	 */
	public function accept()
	{
		$this->proceedAction(self::ACTION_ACCEPT);
	}


	public function renew()
	{
		$this->proceedAction(self::ACTION_RENEW);
	}


	public function approve()
	{
		$this->proceedAction(self::ACTION_APPROVE);
	}


	public function disapprove()
	{
		$this->proceedAction(self::ACTION_DISAPPROVE);
	}


	/**
	 * @param integer $fileId
	 * @throws TasksException
	 * @throws CTaskAssertException
	 */
	public function removeAttachedFile($fileId)
	{
		CTaskAssert::assertLaxIntegers($fileId);
		CTaskAssert::assert($fileId > 0);

		if ( ! $this->isActionAllowed(self::ACTION_EDIT) )
		{
			CTaskAssert::log(
				'access denied while trying to remove file: fileId=' . $fileId 
				. ', taskId=' . $this->taskId . ', userId=' . $this->executiveUserId,
				CTaskAssert::ELL_WARNING
			);

			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);
		}

		if ( ! CTaskFiles::Delete($this->taskId, $fileId) )
		{
			throw new TasksException(
				'File #' . $fileId . ' not attached to task #' . $this->taskId,
				TasksException::TE_FILE_NOT_ATTACHED_TO_TASK
			);
		}
	}


	public function isUserRole($roleId)
	{
		$userRoles = $this->getUserRoles();

		return ($userRoles & $roleId);
	}


	/**
	 * @param $userId
	 * @param $arOrder
	 * @param $arFilter
	 * @param array $arParams
	 * @param array $arSelect
	 * @throws TasksException
	 * @return array $arReturn with elements
	 *        <ul>
	 *        <li>$arReturn[0] - array of items
	 *        <li>$arReturn[1] - CDBResult
	 *        </ul>
	 */
	public static function fetchList($userId, $arOrder, $arFilter, $arParams = array(), $arSelect = array())
	{
		$arItems = array();

		try
		{
			$arParamsOut = array(
				'USER_ID' => $userId,
				'bIgnoreErrors' => true		// don't die on SQL errors
			);

			if (isset($arParams['nPageTop']))
				$arParamsOut['nPageTop'] = $arParams['nPageTop'];
			elseif (isset($arParams['NAV_PARAMS']))
				$arParamsOut['NAV_PARAMS'] = $arParams['NAV_PARAMS'];

			$arFilter['CHECK_PERMISSIONS'] = 'Y';	// Always check permissions

			if ( ! empty($arSelect) )
			{
				$arSelect = array_merge(
					$arSelect,
					array('ID', 'STATUS', 'REAL_STATUS', 'RESPONSIBLE_ID', 'CREATED_BY', 'GROUP_ID')
				);
			}

			$arItemsData = array();
			$arTasksIDs  = array();
			$rsData = CTasks::getList($arOrder, $arFilter, $arSelect, $arParamsOut);

			if ( ! is_object($rsData) )
				throw new TasksException();

			while ($arData = $rsData->fetch())
			{
				$taskId       = (int) $arData['ID'];
				$arTasksIDs[] = $taskId;

				$arData['AUDITORS']    = array();
				$arData['ACCOMPLICES'] = array();
				$arItemsData[$taskId]  = $arData;
			}

			if(is_array($arTasksIDs) && !empty($arTasksIDs))
			{
				// fill ACCOMPLICES and AUDITORS
				$rsMembers = CTaskMembers::GetList(array(), array('TASK_ID' => $arTasksIDs));

				if ( ! is_object($rsMembers) )
					throw new TasksException();

				while ($arMember = $rsMembers->fetch())
				{
					$taskId = (int) $arMember['TASK_ID'];

					if (in_array($taskId, $arTasksIDs, true))
					{
						if ($arMember['TYPE'] === 'A')
							$arItemsData[$taskId]['ACCOMPLICES'][] = $arMember['USER_ID'];
						elseif ($arMember['TYPE'] === 'U')
							$arItemsData[$taskId]['AUDITORS'][] = $arMember['USER_ID'];
					}
				}

				// fill tags
				if (isset($arParams['LOAD_TAGS']) && $arParams['LOAD_TAGS'])
				{
					foreach ($arTasksIDs as $taskId)
						$arItemsData[$taskId]['TAGS'] = array();

					$rsTags = CTaskTags::getList(array(), array('TASK_ID' => $arTasksIDs));

					if ( ! is_object($rsTags) )
						throw new TasksException();

					while ($arTag = $rsTags->fetch())
					{
						$taskId = (int) $arTag['TASK_ID'];

						if (in_array($taskId, $arTasksIDs, true))
							$arItemsData[$taskId]['TAGS'][] = $arTag['NAME'];
					}
				}
			}
		}
		catch (Exception $e)
		{
			CTaskAssert::logError('[0xa819f6f1] probably SQL error at ' . $e->getFile() . ':' . $e->getLine());
			throw new TasksException(
				'',
				TasksException::TE_SQL_ERROR 
				| TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
			);
		}

		foreach ($arItemsData as $arItemData)
			$arItems[] = self::constructWithPreloadedData($userId, $arItemData);

		return (array($arItems, $rsData));
	}


	private static function constructWithPreloadedData($userId, $arTaskData)
	{
		$oItem = new self($arTaskData['ID'], $userId);

		if (isset($arTaskData['TAGS']))
		{
			$oItem->arTaskTags = $arTaskData['TAGS'];
			unset($arTaskData['TAGS']);
		}

		$oItem->arTaskDataEscaped = null;
		$oItem->arTaskData = $arTaskData;

		return ($oItem);
	}


	public static function runRestMethod($executiveUserId, $methodName, $args, $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		$rsData = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskitem', $methodName, $args);

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$argsParsed[] = $executiveUserId;
				/** @var CTaskItem $oTaskItem */
				$oTaskItem    = call_user_func_array(array('self', $methodName), $argsParsed);
				$taskId       = (int) $oTaskItem->getId();
				$returnValue  = $taskId;
				self::cacheInstanceInPool($taskId, $executiveUserId, $oTaskItem);
			}
			elseif ($methodName === 'getlist' || $methodName === 'list') // todo: temporal fix
			{
				array_unshift($argsParsed, $executiveUserId);

				// we need to fill default values up to $arParams (4th) argument
				while ( ! array_key_exists(3, $argsParsed) )
					$argsParsed[] = array();

				if ($navigation['iNumPage'] > 1)
				{
					$argsParsed[3]['NAV_PARAMS'] = array(
						'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
						'iNumPage'  => (int) $navigation['iNumPage']
					);
				}
				else if (isset($argsParsed[3]['NAV_PARAMS']))
				{
					if (isset($argsParsed[3]['NAV_PARAMS']['nPageTop']))
						$argsParsed[3]['NAV_PARAMS']['nPageTop'] = min(CTaskRestService::TASKS_LIMIT_TOP_COUNT, (int) $argsParsed[3]['NAV_PARAMS']['nPageTop']);

					if (isset($argsParsed[3]['NAV_PARAMS']['nPageSize']))
						$argsParsed[3]['NAV_PARAMS']['nPageSize'] = min(CTaskRestService::TASKS_LIMIT_PAGE_SIZE, (int) $argsParsed[3]['NAV_PARAMS']['nPageSize']);

					if (
						( ! isset($argsParsed[3]['NAV_PARAMS']['nPageTop']) )
						&& ( ! isset($argsParsed[3]['NAV_PARAMS']['nPageSize']) )
					)
					{
						$argsParsed[3]['NAV_PARAMS'] = array(
							'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
							'iNumPage'  => 1
						);
					}
				}
				else
				{
					$argsParsed[3]['NAV_PARAMS'] = array(
						'nPageSize' => CTaskRestService::TASKS_LIMIT_PAGE_SIZE,
						'iNumPage'  => 1
					);
				}

				/** @var CTaskItem[] $oTaskItems */
				/** @noinspection PhpUnusedLocalVariableInspection */
				list($oTaskItems, $rsData) = call_user_func_array(array('self', 'fetchList'), $argsParsed);

				$returnValue = array();

				foreach ($oTaskItems as $oTaskItem)
				{
					$arTaskData = $oTaskItem->getData(false);
					$arTaskData['ALLOWED_ACTIONS'] = $oTaskItem->getAllowedActionsAsStrings();

					if (isset($argsParsed[3]))
					{
						if (isset($argsParsed[3]['LOAD_TAGS']) && ($argsParsed[3]['LOAD_TAGS'] == 1))
							$arTaskData['TAGS'] = $oTaskItem->getTags();
					}

					$returnValue[] = $arTaskData;
				}
			}
			else
				$returnValue = call_user_func_array(array('self', $methodName), $argsParsed);
		}
		else
		{
			$taskId = array_shift($argsParsed);
			$oTask  = self::getInstanceFromPool($taskId, $executiveUserId);
			$returnValue = call_user_func_array(array($oTask, $methodName), $argsParsed);
		}

		return (array($returnValue, $rsData));
	}


	private function markCacheAsDirty()
	{
		$this->arTaskData           = null;
		$this->arTaskAllowedActions = null;
		$this->arTaskDataEscaped    = null;
		$this->arTaskUserRoles      = null;
		$this->arTaskFiles          = null;
		$this->arTaskTags           = null;
		$this->arTaskDependsOn      = null;
	}


	private function proceedAction($actionId, $arActionArguments = null)
	{
		$actionId = (int) $actionId;

		$arTaskData = $this->getData($bSpecialChars = false);
		$arNewFields = null;

		if ($actionId == self::ACTION_REMOVE)
		{
			if ( ! $this->isActionAllowed(self::ACTION_REMOVE) )
				throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED | TasksException::TE_ACCESS_DENIED);

			$this->markCacheAsDirty();
			/** @noinspection PhpDeprecationInspection */
			if (CTasks::Delete($this->taskId) !== true)
			{
				throw new TasksException(
					'', 
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
				);
			}

			return;
		}
		elseif ($actionId == self::ACTION_EDIT)
		{
			$arFields = $arActionArguments['FIELDS'];

			if (isset($arFields['ID']))
				unset($arFields['ID']);

			$arParams = array(
				'USER_ID'               => $this->executiveUserId,
				'CHECK_RIGHTS_ON_FILES' => true
			);

			$actionChangeDeadlineFields = array('DEADLINE', 'START_DATE_PLAN', 'END_DATE_PLAN');
			$arGivenFieldsNames = array_keys($arFields);

			if (
				array_key_exists('STATUS', $arFields)
				&& ! in_array(
					(int) $arFields['STATUS'],
					array(
						CTasks::STATE_NEW,
						CTasks::STATE_PENDING,
						CTasks::STATE_IN_PROGRESS,
						CTasks::STATE_SUPPOSEDLY_COMPLETED,
						CTasks::STATE_COMPLETED,
						CTasks::STATE_DEFERRED,
						CTasks::STATE_DECLINED
					),
					true	// forbid type casting
				)
			)
			{
				throw new TasksException('Invalid status given', TasksException::TE_WRONG_ARGUMENTS);
			}

			if (
				array_key_exists('PRIORITY', $arFields)
				&& ! in_array(
					(int) $arFields['PRIORITY'],
					array(
						CTasks::PRIORITY_LOW,
						CTasks::PRIORITY_AVERAGE,
						CTasks::PRIORITY_HIGH
					),
					true	// forbid type casting
				)
			)
			{
				throw new TasksException('Invalid priority given', TasksException::TE_WRONG_ARGUMENTS);
			}

			if (
				array_key_exists('CREATED_BY', $arFields)
				&& ( ! $this->isActionAllowed(self::ACTION_CHANGE_DIRECTOR) )
			)
			{
				throw new TasksException('Access denied for field CREATED_BY to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}

			if (
				// is there fields to be checked for ACTION_CHANGE_DEADLINE?
				array_diff($actionChangeDeadlineFields, $arGivenFieldsNames)
				&& ( ! $this->isActionAllowed(self::ACTION_CHANGE_DEADLINE) )
			)
			{
				throw new TasksException('Access denied for field CREATED_BY to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}

			// Get list of fields, except just checked above
			$arGeneralFields = array_diff(
				$arGivenFieldsNames,
				array_merge($actionChangeDeadlineFields, array('CREATED_BY'))
			);
			
			// Is there is something more for update?
			if ( ! empty($arGeneralFields) )
			{
				if ( ! $this->isActionAllowed(self::ACTION_EDIT) )
					throw new TasksException('Access denied for field CREATED_BY to be updated', TasksException::TE_ACTION_NOT_ALLOWED);
			}

			$this->markCacheAsDirty();
			$o = new CTasks();
			/** @noinspection PhpDeprecationInspection */
			if ($o->update($this->taskId, $arFields, $arParams) !== true)
			{
				$this->markCacheAsDirty();
				throw new TasksException(
					serialize($o->GetErrors()),
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
					| TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE
				);
			}
			$this->markCacheAsDirty();

			return;
		}

		switch ($actionId)
		{
			case self::ACTION_ACCEPT:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			case self::ACTION_DECLINE:
				$arNewFields['STATUS'] = CTasks::STATE_DECLINED;

				if (isset($arActionArguments['DECLINE_REASON']))
					$arNewFields['DECLINE_REASON'] = $arActionArguments['DECLINE_REASON'];
				else
					$arNewFields['DECLINE_REASON'] = '';
			break;

			case self::ACTION_COMPLETE:
				if (
					($arTaskData['TASK_CONTROL'] === 'N')
					|| ($arTaskData['CREATED_BY'] == $this->executiveUserId)
				)
				{
					$arNewFields['STATUS'] = CTasks::STATE_COMPLETED;
				}
				else
					$arNewFields['STATUS'] = CTasks::STATE_SUPPOSEDLY_COMPLETED;
			break;

			case self::ACTION_APPROVE:
				$arNewFields['STATUS'] = CTasks::STATE_COMPLETED;
			break;

			case self::ACTION_START:
				$arNewFields['STATUS'] = CTasks::STATE_IN_PROGRESS;
			break;

			case self::ACTION_PAUSE:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			case self::ACTION_DELEGATE:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;

				if ( ! isset($arActionArguments['RESPONSIBLE_ID']) )
					throw new TasksException('Expected $arActionArguments[\'RESPONSIBLE_ID\']', TasksException::TE_WRONG_ARGUMENTS);

				$arNewFields['RESPONSIBLE_ID'] = $arActionArguments['RESPONSIBLE_ID'];
				if (isset($arTaskData['AUDITORS']) && count($arTaskData['AUDITORS']))
				{
					if ( ! in_array($this->executiveUserId, $arTaskData['AUDITORS']) )
					{
						$arNewFields['AUDITORS'] = $arTaskData['AUDITORS'];
						$arNewFields['AUDITORS'][] = $this->executiveUserId;
					}
				}
				else
					$arNewFields['AUDITORS'] = array($this->executiveUserId);
			break;

			case self::ACTION_DEFER:
				$arNewFields['STATUS'] = CTasks::STATE_DEFERRED;
			break;

			case self::ACTION_DISAPPROVE:
			case self::ACTION_RENEW:
				$arNewFields['STATUS'] = CTasks::STATE_PENDING;
			break;

			default:
			break;
		}

		if ($arNewFields === null)
			throw new TasksException();

		// Don't update task, if nothing changed
		$bNeedUpdate = false;

		foreach ($arNewFields as $fieldName => $newValue)
		{
			$curValue = $arTaskData[$fieldName];

			// Convert task data arrays to strings, for comparing
			if (is_array($curValue))
			{
				sort($curValue);
				sort($newValue);
				$curValue = implode('|', $curValue);
				$newValue = implode('|', $newValue);
			}

			if ($curValue != $newValue)
			{
				$bNeedUpdate = true;
				break;
			}
		}

		if ($bNeedUpdate)
		{
			if ( ! $this->isActionAllowed($actionId) )
				throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED | TasksException::TE_ACCESS_DENIED);

			$arParams = array('USER_ID' => $this->executiveUserId);
			$this->markCacheAsDirty();
			$o = new CTasks();
			/** @noinspection PhpDeprecationInspection */
			if ($o->Update($this->taskId, $arNewFields, $arParams) !== true)
			{
				throw new TasksException(
					'', 
					TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED
				);
			}
		}
	}


	private static function getSubUsers($userId)
	{
		static $arSubUsersIdsCache = array();

		if ( ! isset($arSubUsersIdsCache[$userId]) )
		{
			$arSubUsersIds = array();
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$rsSubUsers = CIntranetUtils::GetSubordinateEmployees($userId, $bRecursive = true, $bSkipSelf = false, $onlyActive = 'Y');
			while ($ar = $rsSubUsers->fetch())
				$arSubUsersIds[] = (int) $ar['ID'];

			$arSubUsersIdsCache[$userId] = $arSubUsersIds;
		}

		return ($arSubUsersIdsCache[$userId]);
	}


	private function getUserRoles()
	{
		$arTask = $this->getData($bEscaped = false);
		$userId = $this->executiveUserId;

		// Is there precached data?
		if ($this->arTaskUserRoles === null)
		{
			$userRole = 0;

			if ($arTask['CREATED_BY'] == $userId)
				$userRole |= self::ROLE_DIRECTOR;
		
			if ($arTask['RESPONSIBLE_ID'] == $userId)
				$userRole |= self::ROLE_RESPONSIBLE;

			if ($arTask['ACCOMPLICES'] && in_array($userId, $arTask['ACCOMPLICES']))
				$userRole |= self::ROLE_ACCOMPLICE;

			if ($arTask['AUDITORS'] && in_array($userId, $arTask['AUDITORS']))
				$userRole |= self::ROLE_AUDITOR;

			// Now, process subordinated users
			$allRoles = self::ROLE_DIRECTOR | self::ROLE_RESPONSIBLE | self::ROLE_ACCOMPLICE | self::ROLE_AUDITOR;
			if ($userRole !== $allRoles)
			{
				$arSubUsersIds = self::getSubUsers($userId);

				if ( ! empty($arSubUsersIds) )
				{
					// Check only roles, that user doesn't have already
					if ( ! ($userRole & self::ROLE_DIRECTOR) )
					{
						if (in_array((int)$arTask['CREATED_BY'], $arSubUsersIds, true))
							$userRole |= self::ROLE_DIRECTOR;
					}

					if ( ! ($userRole & self::ROLE_RESPONSIBLE) )
					{
						if (in_array((int)$arTask['RESPONSIBLE_ID'], $arSubUsersIds, true))
							$userRole |= self::ROLE_RESPONSIBLE;
					}

					if ( ! ($userRole & self::ROLE_ACCOMPLICE) )
					{
						foreach ($arTask['ACCOMPLICES'] as $accompliceId)
						{
							if (in_array((int)$accompliceId, $arSubUsersIds, true))
							{
								$userRole |= self::ROLE_ACCOMPLICE;
								break;
							}
						}
					}

					if ( ! ($userRole & self::ROLE_AUDITOR) )
					{
						foreach ($arTask['AUDITORS'] as $auditorId)
						{
							if (in_array((int)$auditorId, $arSubUsersIds, true))
							{
								$userRole |= self::ROLE_AUDITOR;
								break;
							}
						}
					}
				}
			}

			// No role in task?
			if ($userRole === 0)
				$userRole = self::ROLE_NOT_A_MEMBER;

			$this->arTaskUserRoles = $userRole;
		}

		return ($this->arTaskUserRoles);
	}


	private static function getBaseAllowedActions()
	{
		static $arBaseActionsMap = null;

		// Init just once per hit
		if ($arBaseActionsMap === null)
		{
			$arBaseActionsMap = array(
				CTasks::STATE_NEW => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_COMPLETE
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_ACCEPT,
						self::ACTION_START,
						self::ACTION_DELEGATE
					),
					self::ROLE_ACCOMPLICE => array(
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_PENDING => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_COMPLETE
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_START,
						self::ACTION_DELEGATE,
						self::ACTION_DEFER,
						self::ACTION_COMPLETE
					),
					self::ROLE_ACCOMPLICE => array(
						self::ACTION_START,
						self::ACTION_DEFER,
						self::ACTION_COMPLETE
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_IN_PROGRESS => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_COMPLETE
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_PAUSE,
						self::ACTION_DELEGATE,
						self::ACTION_DEFER,
						self::ACTION_COMPLETE
					),
					self::ROLE_ACCOMPLICE => array(
						self::ACTION_PAUSE,
						self::ACTION_DEFER,
						self::ACTION_COMPLETE
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_SUPPOSEDLY_COMPLETED => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_APPROVE,
						self::ACTION_DISAPPROVE
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_RENEW
					),
					self::ROLE_ACCOMPLICE => array(
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_COMPLETED => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_RENEW
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_RENEW,
						self::ACTION_DELEGATE
					),
					self::ROLE_ACCOMPLICE => array(
						self::ACTION_START
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_DEFERRED => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_COMPLETE
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_START,
						self::ACTION_DELEGATE,
						self::ACTION_COMPLETE
					),
					self::ROLE_ACCOMPLICE => array(
						self::ACTION_START,
						self::ACTION_COMPLETE
					),
					self::ROLE_AUDITOR => array(
					)
				),
				CTasks::STATE_DECLINED => array(
					self::ROLE_DIRECTOR => array(
						self::ACTION_EDIT,
						self::ACTION_REMOVE,
						self::ACTION_COMPLETE,
						self::ACTION_RENEW
					),
					self::ROLE_RESPONSIBLE => array(
						self::ACTION_DELEGATE
					),
					self::ROLE_ACCOMPLICE => array(
					),
					self::ROLE_AUDITOR => array(
					)
				)
			);

			$arAnyStatusActionsMap = array(
				self::ROLE_DIRECTOR => array(
					self::ACTION_CHECKLIST_ADD_ITEMS,
					self::ACTION_ELAPSED_TIME_ADD,
					self::ACTION_CHANGE_DIRECTOR,
					self::ACTION_CHANGE_DEADLINE
				),
				self::ROLE_RESPONSIBLE => array(
					self::ACTION_CHECKLIST_ADD_ITEMS,
					self::ACTION_ELAPSED_TIME_ADD
				),
				self::ROLE_ACCOMPLICE => array(
					self::ACTION_CHECKLIST_ADD_ITEMS,
					self::ACTION_ELAPSED_TIME_ADD
				),
				self::ROLE_AUDITOR => array(
				)
			);

			foreach (array_keys($arBaseActionsMap) as $status)
			{
				$arBaseActionsMap[$status][self::ROLE_DIRECTOR] = array_merge(
					$arBaseActionsMap[$status][self::ROLE_DIRECTOR],
					$arAnyStatusActionsMap[self::ROLE_DIRECTOR]
				);
				$arBaseActionsMap[$status][self::ROLE_RESPONSIBLE] = array_merge(
					$arBaseActionsMap[$status][self::ROLE_RESPONSIBLE],
					$arAnyStatusActionsMap[self::ROLE_RESPONSIBLE]
				);
				$arBaseActionsMap[$status][self::ROLE_ACCOMPLICE] = array_merge(
					$arBaseActionsMap[$status][self::ROLE_ACCOMPLICE],
					$arAnyStatusActionsMap[self::ROLE_ACCOMPLICE]
				);
				$arBaseActionsMap[$status][self::ROLE_AUDITOR] = array_merge(
					$arBaseActionsMap[$status][self::ROLE_AUDITOR],
					$arAnyStatusActionsMap[self::ROLE_AUDITOR]
				);
			}

			foreach(GetModuleEvents('tasks', 'OnBaseAllowedActionsMapInit', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arBaseActionsMap));
		}

		return ($arBaseActionsMap);
	}


	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @access private
	 */
	public static function getManifest()
	{
		static $arWritableTaskDataKeys = null;
		static $arReadableTaskDataKeys = null;
		static $arFilterableTaskDataKeys = null;
		static $arDateKeys             = null;

		if ($arReadableTaskDataKeys === null)
		{
			$arCTasksManifest = CTasks::getManifest();
			$arWritableTaskDataKeys = $arCTasksManifest['REST: writable task data fields'];
			$arReadableTaskDataKeys = array_merge($arCTasksManifest['REST: readable task data fields']);
			$arFilterableTaskDataKeys = $arCTasksManifest['REST: filterable task data fields'];
			$arDateKeys = $arCTasksManifest['REST: date fields'];
		}

		$listMethodData = array(
			'staticMethod'         =>  true,
			'mandatoryParamsCount' =>  0,
			'params' => array(
				array(
					'description' => 'arOrder',
					'type'        => 'array'
				),
				array(
					'description' => 'arFilter',
					'type'        => 'array',
					'allowedKeys' =>  $arFilterableTaskDataKeys,
					'allowedKeyPrefixes' => array(
						'=', '!=', '%', '!%', '?', '><', 
						'!><', '>=', '>', '<', '<=', '!'
					)
				),
				array(
					'description' => 'arParams',
					'type'        => 'array',
					'allowedKeys' =>  array('NAV_PARAMS', 'LOAD_TAGS')
				)
			),
			'allowedKeysInReturnValue' => array_merge(
				$arReadableTaskDataKeys,
				array('ALLOWED_ACTIONS', 'TAGS')
			),
			'collectionInReturnValue'  => true
		);

		return(array(
			'Manifest version' => '1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class'    => 'item',
			'REST: writable task data fields'   =>  $arWritableTaskDataKeys,
			'REST: readable task data fields'   =>  $arReadableTaskDataKeys,
			'REST: filterable task data fields' =>  $arFilterableTaskDataKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'getlist' => $listMethodData, // temporal fix: implement method aliasing later
				'list' => $listMethodData, // temporal fix: implement method aliasing later
				'add' => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'arNewTaskData',
							'type'        => 'array',
							'allowedKeys' => $arWritableTaskDataKeys
						)
					)
				),
				'getexecutiveuserid' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getdata' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description'  => 'isReturnEscapedData',
							'type'         => 'boolean',
							'defaultValue' =>  false
						)
					),
					'allowedKeysInReturnValue' => $arReadableTaskDataKeys
				),
				'getdescription' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'format',
							'type'        => 'integer'
						)
					)
				),
				'getfiles' => array(
					'mandatoryParamsCount' => 1,
					'fileIdsReturnValue'   => true,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'gettags' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getdependson' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getallowedtaskactions' => array(
					'alias'                => 'getallowedactions',
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'getallowedtaskactionsasstrings' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						)
					)
				),
				'delete' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'delegate' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'userId',
							'type'        => 'integer'
						)
					)
				),
				'startexecution' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'pauseexecution' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'defer' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'complete' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'update' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arNewTaskData',
							'type'        => 'array',
							'allowedKeys' => $arWritableTaskDataKeys
						)
					)
				),
				'renew' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'approve' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				),
				'disapprove' => array(
					'mandatoryParamsCount' => 1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						)
					)
				)
			)
		));
	}
}
