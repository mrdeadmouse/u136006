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
class CTaskListState
{
	// Bitmask constants for view modes / roles
	// There are reserved bits, right to left:
	//  1 -  8 - submode flags (OR)
	//  9 - 12 - mode (XOR)
	// 13 - 16 - role (XOR)
	// 17 - 20 - section (XOR)

	// list view mode
	const VIEW_MODE_LIST                 = 0x0000100;
	const VIEW_MODE_GANTT                = 0x0000200;
	const VIEW_SUBMODE_WITH_GROUPS       = 0x0000001;
	const VIEW_SUBMODE_WITH_SUBTASKS     = 0x0000002;
	
	// roles
	const VIEW_ROLE_RESPONSIBLE          = 0x0001000;
	const VIEW_ROLE_ACCOMPLICE           = 0x0002000;
	const VIEW_ROLE_AUDITOR              = 0x0003000;
	const VIEW_ROLE_ORIGINATOR           = 0x0004000;

	// section
	const VIEW_SECTION_ROLES             = 0x0010000;
	const VIEW_SECTION_ADVANCED_FILTER   = 0x0020000;	// no role, only advanced filter used

	// category
	const VIEW_TASK_CATEGORY_NEW         = 0x0100000;	// not viewed yet by user
	const VIEW_TASK_CATEGORY_IN_PROGRESS = 0x0300000;	// All except completed/deferred
	const VIEW_TASK_CATEGORY_COMPLETED   = 0x0400000;	// CTasks::STATE_COMPLETED
	const VIEW_TASK_CATEGORY_DEFERRED    = 0x0500000;	// CTasks::STATE_DEFERRED
	const VIEW_TASK_CATEGORY_EXPIRED     = 0x0600000;	// CTasks::METASTATE_EXPIRED
	const VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES = 0x0900000;	// <= 24h to deadline
	const VIEW_TASK_CATEGORY_ATTENTION   = 0x0700000;	// depends on role
	const VIEW_TASK_CATEGORY_WAIT_CTRL   = 0x0800000;	// CTasks::STATE_SUPPOSEDLY_COMPLETED
	const VIEW_TASK_CATEGORY_WO_DEADLINE = 0x0A00000;	// tasks without DEADLINE
	const VIEW_TASK_CATEGORY_ALL         = 0x0B00000;


	// Identifications for CUserOptions
	// Warning: this is private constants, don't use it!!!
	const listCategoryName      = 'tasks:ctasklistsctrl';
	const listViewModeParamName = 'list_state';

	// Private constants
	const TOC_SECTION_SELECTED = 'T-1';
	const TOC_ROLE_SELECTED    = 'T-2';
	const TOC_VIEW_SELECTED    = 'T-3';
	const TOC_SUBMODES         = 'T-4';
	const TOC_SELECTED         = 'T-5';
	const TOC_TASK_CATEGORY_SELECTED = 'T-6';

	private $state = null;
	private $paramName = null;
	private $loggedInUserId = null;
	private $userId = null;
	private static $instancesOfSelf = array();


	/**
	 * Get instance of multiton
	 *
	 * @param integer $userId
	 * @return CTaskListState
	 */
	public static function getInstance($userId)
	{
		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);

		$key = (string) ((int) $userId);

		if ( ! array_key_exists($key, self::$instancesOfSelf) )
			self::$instancesOfSelf[$key] = new self($userId);

		return (self::$instancesOfSelf[$key]);
	}


	/**
	 * prevent creating through "new"
	 *
	 * @param $userId
	 */
	private function __construct($userId)
	{
		global $USER;

		CTaskAssert::assertLaxIntegers($userId);
		CTaskAssert::assert($userId > 0);

		$this->userId = $userId;

		if (
			isset($USER)
			&& is_object($USER)
			&& $USER->IsAuthorized()
		)
		{
			$this->loggedInUserId = (int) $USER->getId();
			$this->paramName = self::listViewModeParamName . '_by_user_' . $this->loggedInUserId;
		}
		else
			$this->paramName = self::listViewModeParamName;

		$rc = CUserOptions::GetOption(
			self::listCategoryName, 
			$this->paramName, 
			serialize(false),
			$this->userId
		);

		$this->state = array(
			self::TOC_SECTION_SELECTED       => self::VIEW_SECTION_ROLES,
			self::TOC_ROLE_SELECTED          => self::VIEW_ROLE_RESPONSIBLE,
			self::TOC_VIEW_SELECTED          => self::VIEW_MODE_LIST,
			self::TOC_TASK_CATEGORY_SELECTED => array(
				self::VIEW_ROLE_RESPONSIBLE => self::VIEW_TASK_CATEGORY_IN_PROGRESS,
				self::VIEW_ROLE_ORIGINATOR  => self::VIEW_TASK_CATEGORY_IN_PROGRESS,
				self::VIEW_ROLE_ACCOMPLICE  => self::VIEW_TASK_CATEGORY_IN_PROGRESS,
				self::VIEW_ROLE_AUDITOR     => self::VIEW_TASK_CATEGORY_IN_PROGRESS
			),
			self::TOC_SUBMODES               => array(
				self::VIEW_SUBMODE_WITH_GROUPS => array(
					self::TOC_SELECTED => 'Y'
				),
				self::VIEW_SUBMODE_WITH_SUBTASKS => array(
					self::TOC_SELECTED => 'Y'
				)
			)
		);

		$state = unserialize($rc);
		if (is_array($state))
			$this->state = array_merge($this->state, $state);
	}


	/**
	 * @access private
	 */
	public function getRawState()
	{
		$curState = CUserOptions::GetOption(
			self::listCategoryName, 
			$this->paramName, 
			serialize(false),
			$this->userId
		);

		return ($curState);
	}


	/**
	 * @access private
	 */
	public function setRawState($newState)
	{
		$curState = $this->getRawState();

		if ($newState !== $curState)
		{
			CUserOptions::SetOption(
				self::listCategoryName, 
				$this->paramName, 
				(string) $newState,
				$bCommon = false,
				$this->userId
			);

			$this->state = unserialize($newState);
		}
	}


	public function saveState()
	{
		$newState = (string) serialize($this->state);

		$this->setRawState($newState);
	}


	public function setSection($sectionId)
	{
		$sectionId = (int) $sectionId;

		if ( ! in_array($sectionId, self::getKnownSections(), true) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$this->state[self::TOC_SECTION_SELECTED] = $sectionId;
	}


	public function getSection()
	{
		return ($this->state[self::TOC_SECTION_SELECTED]);
	}


	public function setUserRole($roleId)
	{
		$roleId = (int) $roleId;

		if ( ! in_array($roleId, self::getKnownRoles(), true) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$this->state[self::TOC_ROLE_SELECTED] = $roleId;
	}


	public function getUserRole()
	{
		return ($this->state[self::TOC_ROLE_SELECTED]);
	}


	public function setViewMode($viewId)
	{
		$viewId = (int) $viewId;

		if ( ! in_array($viewId, $this->getAllowedViewModes(), true) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$this->state[self::TOC_VIEW_SELECTED] = $viewId;
	}


	public function getViewMode()
	{
		return ($this->state[self::TOC_VIEW_SELECTED]);
	}


	public function switchOnSubmode($submodeId)
	{
		$this->switchSubmode($submodeId, $switchOn = true);
	}


	public function switchOffSubmode($submodeId)
	{
		$this->switchSubmode($submodeId, $switchOn = false);
	}


	public function isSubmode($submodeId)
	{
		if ($this->state[self::TOC_SUBMODES][$submodeId][self::TOC_SELECTED] === 'Y')
			return (true);
		else
			return (false);
	}


	public function setTaskCategory($categoryId)
	{
		$categoryId = (int) $categoryId;

		if ( ! in_array($categoryId, $this->getAllowedTaskCategories(), true) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$roleId = $this->getUserRole();

		$this->state[self::TOC_TASK_CATEGORY_SELECTED][$roleId] = $categoryId;
	}


	public function getTaskCategory()
	{
		$roleId = $this->getUserRole();
		return ($this->state[self::TOC_TASK_CATEGORY_SELECTED][$roleId]);
	}


	public function getState()
	{
		$arRoles = self::getKnownRoles();
		$arViews = $this->getAllowedViewModes();
		$arSubmodes = self::getKnownSubmodes();
		$arTaskCategories = $this->getAllowedTaskCategories();

		$selectedSectionId = $this->getSection();
		$selectedRoleId = $this->getUserRole();
		$selectedViewId = $this->getViewMode();
		$taskCategoryId = $this->getTaskCategory();

		$arViewState = array(
			'SECTION_SELECTED' => array(
				'ID'       => $selectedSectionId,
				'CODENAME' => self::resolveConstantCodename($selectedSectionId)
			),
			'ROLES'         => array(),
			'ROLE_SELECTED' => array(
				'ID'       => $selectedRoleId,
				'CODENAME' => self::resolveConstantCodename($selectedRoleId)
			),
			'VIEWS'         => array(),
			'VIEW_SELECTED' => array(
				'ID'       => $selectedViewId,
				'CODENAME' => self::resolveConstantCodename($selectedViewId)
			),
			'TASK_CATEGORIES' => array(),
			'TASK_CATEGORY_SELECTED' => array(
				'ID'       => $taskCategoryId,
				'CODENAME' => self::resolveConstantCodename($taskCategoryId)
			),
			'SUBMODES'      => array()
		);

		foreach ($arRoles as $roleId)
		{
			$codeName = self::resolveConstantCodename($roleId);
			$arViewState['ROLES'][$codeName] = array(
				'ID'       => $roleId,
				'SELECTED' => (($selectedRoleId === $roleId) ? 'Y' : 'N'),
				'TITLE'    => self::resolveRoleName($roleId),
				'TITLE_ALT' => self::resolveRoleName($roleId, $bAltName = true)
			);
		}

		foreach ($arViews as $viewId)
		{
			$codeName = self::resolveConstantCodename($viewId);
			$arViewState['VIEWS'][$codeName] = array(
				'ID'       => $viewId,
				'SELECTED' => (($selectedViewId === $viewId) ? 'Y' : 'N'),
				'TITLE'    => self::resolveViewName($viewId)
			);
		}

		foreach ($arTaskCategories as $categoryId)
		{
			$codeName = self::resolveConstantCodename($categoryId);
			$arViewState['TASK_CATEGORIES'][$codeName] = array(
				'ID'       => $categoryId,
				'SELECTED' => (($taskCategoryId === $categoryId) ? 'Y' : 'N'),
				'TITLE'    => self::resolveTaskCategoryName($categoryId)
			);
		}

		foreach ($arSubmodes as $submodeId)
		{
			$codeName = self::resolveConstantCodename($submodeId);
			$isSubmodeSelected = $this->isSubmode($submodeId);

			$arViewState['SUBMODES'][$codeName] = array(
				'ID'       => $submodeId,
				'SELECTED' => ($isSubmodeSelected ? 'Y' : 'N'),
				'TITLE'    => self::resolveSubmodeName($submodeId)
			);
		}

		return ($arViewState);
	}


	private function switchSubmode($submodeId, $switchOn = false)
	{
		$submodeId = (int) $submodeId;

		if ( ! in_array($submodeId, self::getKnownSubmodes(), true) )
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);

		$this->state[self::TOC_SUBMODES][$submodeId][self::TOC_SELECTED] = ($switchOn ? 'Y' : 'N');
	}


	private static function replaceTocKeysToStrings($input)
	{
		$return = array();

		foreach ($input as $key => $value)
		{
			$newkey = null;

			switch ($key)
			{
				case self::TOC_SELECTED:
					$newkey = 'SELECTED';
				break;

				case self::TOC_SUBMODES:
					$newkey = 'SUBMODES';
				break;

				case self::TOC_VIEW_SELECTED:
					$newkey = 'VIEW_SELECTED';
				break;

				case self::TOC_ROLE_SELECTED:
					$newkey = 'ROLE_SELECTED';
				break;

				case self::TOC_SECTION_SELECTED:
					$newkey = 'SECTION_SELECTED';
				break;

				case self::TOC_TASK_CATEGORY_SELECTED:
					$newkey = 'TASK_CATEGORY_SELECTED';
				break;

				default:
					$newkey = $key;
				break;
			}

			if (is_array($value))
				$value = self::replaceTocKeysToStrings($value); 

			$return[$newkey] = $value;
		}

		return $return;
	}



	private function getAllowedTaskCategories()
	{
		switch ($this->getUserRole())
		{
			case self::VIEW_ROLE_RESPONSIBLE:
				$arCategories = array(
					self::VIEW_TASK_CATEGORY_ALL,
					self::VIEW_TASK_CATEGORY_IN_PROGRESS,
					self::VIEW_TASK_CATEGORY_DEFERRED,
					self::VIEW_TASK_CATEGORY_COMPLETED,
					self::VIEW_TASK_CATEGORY_ATTENTION,
					self::VIEW_TASK_CATEGORY_WO_DEADLINE,
					self::VIEW_TASK_CATEGORY_NEW,
					self::VIEW_TASK_CATEGORY_EXPIRED,
					self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
				);
			break;

			case self::VIEW_ROLE_ACCOMPLICE:
				$arCategories = array(
					self::VIEW_TASK_CATEGORY_ALL,
					self::VIEW_TASK_CATEGORY_IN_PROGRESS,
					self::VIEW_TASK_CATEGORY_DEFERRED,
					self::VIEW_TASK_CATEGORY_COMPLETED,
					self::VIEW_TASK_CATEGORY_ATTENTION,
					self::VIEW_TASK_CATEGORY_NEW,
					self::VIEW_TASK_CATEGORY_EXPIRED,
					self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
				);
			break;

			case self::VIEW_ROLE_AUDITOR:
				$arCategories = array(
					self::VIEW_TASK_CATEGORY_ALL,
					self::VIEW_TASK_CATEGORY_IN_PROGRESS,
					self::VIEW_TASK_CATEGORY_DEFERRED,
					self::VIEW_TASK_CATEGORY_COMPLETED,
					self::VIEW_TASK_CATEGORY_NEW,
					self::VIEW_TASK_CATEGORY_EXPIRED,
					self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
				);
			break;

			case self::VIEW_ROLE_ORIGINATOR:
				$arCategories = array(
					self::VIEW_TASK_CATEGORY_ALL,
					self::VIEW_TASK_CATEGORY_IN_PROGRESS,
					self::VIEW_TASK_CATEGORY_DEFERRED,
					self::VIEW_TASK_CATEGORY_COMPLETED,
					self::VIEW_TASK_CATEGORY_WO_DEADLINE,
					self::VIEW_TASK_CATEGORY_WAIT_CTRL,
					self::VIEW_TASK_CATEGORY_EXPIRED,
					self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
				);
			break;

			default:
				throw new TasksException(TasksException::TE_WRONG_ARGUMENTS);
			break;
		}

		return ($arCategories);
	}


	private static function getKnownSubmodes()
	{
		return (array(
			self::VIEW_SUBMODE_WITH_GROUPS,
			self::VIEW_SUBMODE_WITH_SUBTASKS
		));
	}


	private function getAllowedViewModes()
	{
		return (array(
			self::VIEW_MODE_LIST,
			self::VIEW_MODE_GANTT
		));
	}


	private static function getKnownRoles()
	{
		return (array(
			self::VIEW_ROLE_RESPONSIBLE,
			self::VIEW_ROLE_ACCOMPLICE,
			self::VIEW_ROLE_ORIGINATOR,
			self::VIEW_ROLE_AUDITOR
		));
	}


	private static function getKnownSections()
	{
		return (array(
			self::VIEW_SECTION_ROLES,
			self::VIEW_SECTION_ADVANCED_FILTER
		));
	}


	private static function resolveConstantCodename($constant)
	{
		static $arMap = array(
			self::VIEW_SECTION_ROLES             => 'VIEW_SECTION_ROLES',
			self::VIEW_SECTION_ADVANCED_FILTER   => 'VIEW_SECTION_ADVANCED_FILTER',
			self::VIEW_ROLE_RESPONSIBLE          => 'VIEW_ROLE_RESPONSIBLE',
			self::VIEW_ROLE_ACCOMPLICE           => 'VIEW_ROLE_ACCOMPLICE',
			self::VIEW_ROLE_ORIGINATOR           => 'VIEW_ROLE_ORIGINATOR',
			self::VIEW_ROLE_AUDITOR              => 'VIEW_ROLE_AUDITOR',
			self::VIEW_MODE_LIST                 => 'VIEW_MODE_LIST',
			self::VIEW_MODE_GANTT                => 'VIEW_MODE_GANTT',
			self::VIEW_SUBMODE_WITH_GROUPS       => 'VIEW_SUBMODE_WITH_GROUPS',
			self::VIEW_SUBMODE_WITH_SUBTASKS     => 'VIEW_SUBMODE_WITH_SUBTASKS',
			self::VIEW_TASK_CATEGORY_ALL         => 'VIEW_TASK_CATEGORY_ALL',
			self::VIEW_TASK_CATEGORY_NEW         => 'VIEW_TASK_CATEGORY_NEW',
			self::VIEW_TASK_CATEGORY_IN_PROGRESS => 'VIEW_TASK_CATEGORY_IN_PROGRESS',
			self::VIEW_TASK_CATEGORY_COMPLETED   => 'VIEW_TASK_CATEGORY_COMPLETED',
			self::VIEW_TASK_CATEGORY_DEFERRED    => 'VIEW_TASK_CATEGORY_DEFERRED',
			self::VIEW_TASK_CATEGORY_EXPIRED     => 'VIEW_TASK_CATEGORY_EXPIRED',
			self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES => 'VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES',
			self::VIEW_TASK_CATEGORY_ATTENTION   => 'VIEW_TASK_CATEGORY_ATTENTION',
			self::VIEW_TASK_CATEGORY_WAIT_CTRL   => 'VIEW_TASK_CATEGORY_WAIT_CTRL',
			self::VIEW_TASK_CATEGORY_WO_DEADLINE => 'VIEW_TASK_CATEGORY_WO_DEADLINE'
		);

		if ( ! isset($arMap[$constant]) )
		{
			CTaskAssert::logError('[0xbe638df3] ');
			throw new TasksException('', TasksException::TE_WRONG_ARGUMENTS);
		}

		return ($arMap[$constant]);
	}


	private static function resolveSubmodeName($submodeId)
	{
		static $arMap = null;

		if ($arMap === null)
		{
			$arMap = array(
				self::VIEW_SUBMODE_WITH_GROUPS   => GetMessage('TASKS_LIST_CTRL_SUBMODE_WITH_GROUPS_V2'),
				self::VIEW_SUBMODE_WITH_SUBTASKS => GetMessage('TASKS_LIST_CTRL_SUBMODE_WITH_SUBTASKS_V2')
			);
		}

		if (isset($arMap[$submodeId]))
			return ($arMap[$submodeId]);
		else
		{
			CTaskAssert::logError('[0xe758ff49] ');
			return ('???');
		}
	}


	private static function resolveRoleName($roleId, $alternate = false)
	{
		static $arMap = null;

		if ($arMap === null)
		{
			$arMap = array(
				self::VIEW_ROLE_RESPONSIBLE => array(
					'DEFAULT' => GetMessage('TASKS_LIST_CTRL_ROLE_RESPONSIBLE'),
					'ALT'     => GetMessage('TASKS_LIST_CTRL_ROLE_RESPONSIBLE_ALT')
				),
				self::VIEW_ROLE_ACCOMPLICE => array(
					'DEFAULT' => GetMessage('TASKS_LIST_CTRL_ROLE_ACCOMPLICE'),
					'ALT'     => GetMessage('TASKS_LIST_CTRL_ROLE_ACCOMPLICE_ALT')
				),
				self::VIEW_ROLE_ORIGINATOR => array(
					'DEFAULT' => GetMessage('TASKS_LIST_CTRL_ROLE_ORIGINATOR'),
					'ALT'     => GetMessage('TASKS_LIST_CTRL_ROLE_ORIGINATOR_ALT')
				),
				self::VIEW_ROLE_AUDITOR => array(
					'DEFAULT' => GetMessage('TASKS_LIST_CTRL_ROLE_AUDITOR'),
					'ALT'     => GetMessage('TASKS_LIST_CTRL_ROLE_AUDITOR_ALT')
				)
			);
		}

		$use = ($alternate ? 'ALT' : 'DEFAULT');

		if (isset($arMap[$roleId][$use]))
			return ($arMap[$roleId][$use]);
		else
		{
			CTaskAssert::logError('[0xaa58b61e] role_id = ' . $roleId);
			return ('???');
		}
	}


	private static function resolveViewName($viewId)
	{
		static $arMap = null;

		if ($arMap === null)
		{
			$arMap = array(
				self::VIEW_MODE_LIST  => GetMessage('TASKS_LIST_CTRL_MODE_LIST'),
				self::VIEW_MODE_GANTT => GetMessage('TASKS_LIST_CTRL_MODE_GANTT')
			);
		}

		if (isset($arMap[$viewId]))
			return ($arMap[$viewId]);
		else
		{
			CTaskAssert::logError('[0x456cbacc] ');
			return ('???');
		}
	}


	private static function resolveTaskCategoryName($categoryId)
	{
		static $arMap = null;

		$categoryId = (int) $categoryId;

		if ($arMap === null)
		{
			$arMap = array(
				self::VIEW_TASK_CATEGORY_ALL         => GetMessage('TASKS_LIST_CTRL_CATEGORY_ALL'),
				self::VIEW_TASK_CATEGORY_NEW         => GetMessage('TASKS_LIST_CTRL_CATEGORY_NEW'),
				self::VIEW_TASK_CATEGORY_IN_PROGRESS => GetMessage('TASKS_LIST_CTRL_CATEGORY_IN_PROGRESS'),
				self::VIEW_TASK_CATEGORY_COMPLETED   => GetMessage('TASKS_LIST_CTRL_CATEGORY_COMPLETED'),
				self::VIEW_TASK_CATEGORY_DEFERRED    => GetMessage('TASKS_LIST_CTRL_CATEGORY_DEFERRED'),
				self::VIEW_TASK_CATEGORY_EXPIRED     => GetMessage('TASKS_LIST_CTRL_CATEGORY_EXPIRED'),
				self::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES => GetMessage('TASKS_LIST_CTRL_CATEGORY_EXPIRED_CANDIDATES'),
				self::VIEW_TASK_CATEGORY_ATTENTION   => GetMessage('TASKS_LIST_CTRL_CATEGORY_ATTENTION'),
				self::VIEW_TASK_CATEGORY_WAIT_CTRL   => GetMessage('TASKS_LIST_CTRL_CATEGORY_WAIT_CTRL'),
				self::VIEW_TASK_CATEGORY_WO_DEADLINE => GetMessage('TASKS_LIST_CTRL_CATEGORY_WO_DEADLINE')
			);
		}

		if (isset($arMap[$categoryId]))
			return ($arMap[$categoryId]);
		else
		{
			CTaskAssert::logError('[0xa1bd9ec0] ');
			return ('???');
		}
	}


	// prevent clone of object
	public function __clone()
	{
		throw new Exception('clone is not allowed');
	}


	// prevent wakeup
	public function __wakeup()
	{
		throw new Exception('wakeup is not allowed');
	}
}
