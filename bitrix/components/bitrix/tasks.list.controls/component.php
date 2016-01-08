<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$arDefaultValues = array(
	'GROUP_ID'               => 0,
	'SHOW_TASK_LIST_MODES'   => 'Y',
	'SHOW_HELP_ICON'         => 'Y',
	'SHOW_SEARCH_FIELD'      => 'Y',
	'SHOW_TEMPLATES_TOOLBAR' => 'Y',
	'SHOW_QUICK_TASK_ADD'    => 'Y',
	'SHOW_ADD_TASK_BUTTON'   => 'Y',
	'SHOW_SECTIONS_BAR'      => 'N',
	'SHOW_FILTER_BAR'        => 'N',
	'SHOW_COUNTERS_BAR'      => 'N',
	'SHOW_SECTION_MANAGE'    => 'A',
	'MARK_ACTIVE_ROLE'       => 'N',
	'MARK_SECTION_MANAGE'    => 'N',
	'MARK_SECTION_PROJECTS'  => 'N',
	'MARK_SECTION_REPORTS'   => 'N',
	'SECTION_URL_PREFIX'     => '',
	'PATH_TO_DEPARTMENTS'    => null,
	'PATH_TO_REPORTS'        => null
);

if ( ! isset($arParams['NAME_TEMPLATE']) )
	$arParams['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

$loggedInUserId = (int) $USER->getId();

$isAccessToCounters = ($arParams['USER_ID'] == $loggedInUserId)
	|| $USER->isAdmin()
	|| CTasksTools::IsPortalB24Admin()
	|| CTasks::IsSubordinate($arParams['USER_ID'], $loggedInUserId);

if ($arParams["GROUP_ID"] > 0)
	$arParams['SHOW_SECTION_COUNTERS'] = 'N';

if ( ! $isAccessToCounters )
	$arParams['SHOW_SECTION_COUNTERS'] = 'N';

// Set default values for omitted parameters
foreach ($arDefaultValues as $paramName => $paramDefaultValue)
{
	if ( ! array_key_exists($paramName, $arParams) )
		$arParams[$paramName] = $paramDefaultValue;
}

if ( ! $arParams['PATH_TO_REPORTS'] )
	$arParams['PATH_TO_REPORTS'] = $arParams['SECTION_URL_PREFIX'] . 'report/';

if ( ! $arParams['PATH_TO_DEPARTMENTS'] )
	$arParams['PATH_TO_DEPARTMENTS'] = $arParams['SECTION_URL_PREFIX'] . 'departments/';

if (
	isset($arParams['SHOW_SECTION_PROJECTS']) 
	&& ($arParams['SHOW_SECTION_PROJECTS'] === 'Y')
	&& isset($arParams['PATH_TO_PROJECTS']) 
	&& ( ! empty($arParams['PATH_TO_PROJECTS']) )
	&& ($arParams['USER_ID'] == $loggedInUserId)
)
{
	$arResult['SHOW_SECTION_PROJECTS'] = 'Y';
}
else
	$arResult['SHOW_SECTION_PROJECTS'] = 'N';

$arResult['SHOW_SECTION_MANAGE'] = $arParams['SHOW_SECTION_MANAGE'];

$arResult['F_SEARCH'] = null;

if (strlen($fTitle = tasksGetFilter("F_TITLE")) > 0)
	$arResult['F_SEARCH'] = $fTitle;
elseif (intval($fID = tasksGetFilter("F_META::ID_OR_NAME")) > 0)
	$arResult['F_SEARCH'] = $fID;

if (
	($arParams['SHOW_SECTIONS_BAR'] === 'Y')
	|| ($arParams['SHOW_FILTER_BAR'] === 'Y')
	|| ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
)
{
	// Show this section ONLY if given user is head of department
	// and logged in user is admin or given user or manager of given user
	if ($arParams['SHOW_SECTION_MANAGE'] === 'A')
	{
		$arResult['SHOW_SECTION_MANAGE'] = 'N';

		if ($isAccessToCounters)
		{
			if (CModule::Includemodule('intranet'))
				$arDepartments = CIntranetUtils::GetSubordinateDepartments($arParams['USER_ID'], $bRecursive = false);

			if ( ! empty($arDepartments) )
				$arResult['SHOW_SECTION_MANAGE'] = 'Y';
		}
	}

	if (
		($arResult['SHOW_SECTION_MANAGE'] === 'Y')
		&& ($arParams['GROUP_ID'] > 0)
	)
	{
		$arResult['SHOW_SECTION_MANAGE'] = 'N';
	}

	if ($arResult['SHOW_SECTION_MANAGE'] === 'Y')
	{
		$arResult['SECTION_MANAGE_COUNTER'] = 0;

		if ($arEmployees = CTaskIntranetTools::getImmediateEmployees($arParams['USER_ID']))
		{
			foreach ($arEmployees as $employeeId)
			{
				$employeeId = (int) $employeeId;

				$arResult['SECTION_MANAGE_COUNTER'] += CTaskListCtrl::getUserRoleCounterForUser(
						$employeeId,
						CTaskListState::VIEW_ROLE_RESPONSIBLE
					) 
					+ CTaskListCtrl::getUserRoleCounterForUser(
						$employeeId,
						CTaskListState::VIEW_ROLE_ACCOMPLICE
					)
					+ CTaskListCtrl::getUserRoleCounterForUser(
						$employeeId,
						CTaskListState::VIEW_ROLE_ORIGINATOR
					)
					+ CTaskListCtrl::getUserRoleCounterForUser(
					$employeeId,
					CTaskListState::VIEW_ROLE_AUDITOR
				);
			}
		}
	}

	// get states description
	$oListState = CTaskListState::getInstance($loggedInUserId);
	$arResult['VIEW_STATE'] = $oListState->getState();
	$arResult['VIEW_STATE_RAW'] = $oListState->getRawState();

	$oListCtrl = CTaskListCtrl::getInstance($arParams['USER_ID']);
	$oListCtrl->useState($oListState);

	if ($arParams["GROUP_ID"] > 0)
		$oListCtrl->setFilterByGroupId( (int) $arParams["GROUP_ID"] );
	else
		$oListCtrl->setFilterByGroupId(null);

	$selectedRoleId = $arResult['VIEW_STATE']['ROLE_SELECTED']['ID'];
	$selectedRoleName = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];

	$arResult['F_CREATED_BY'] = $arResult['F_RESPONSIBLE_ID'] = null;
	if ($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ROLES')
	{
		/*
		if (
			($selectedRoleName === 'VIEW_ROLE_RESPONSIBLE')
			|| ($selectedRoleName === 'VIEW_ROLE_ORIGINATOR')
		)
		{
		*/
		if (isset($_GET['F_RESPONSIBLE_ID']))
			$arResult['F_RESPONSIBLE_ID'] = $_GET['F_RESPONSIBLE_ID'];

		if (isset($_GET['F_CREATED_BY']))
			$arResult['F_CREATED_BY'] = $_GET['F_CREATED_BY'];

		if ($arResult['F_CREATED_BY'] || $arResult['F_RESPONSIBLE_ID'])
		{
			$arResult['~USER_NAMES'] = array();

			$rsUsers = CUser::GetList(
				$by = 'id', 
				$order = 'asc', 
				array("ID" => implode('|', array_filter(array($arResult['F_CREATED_BY'], $arResult['F_RESPONSIBLE_ID'])))),
				array(
					'FIELDS' => array(
						'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'ID'
					)
				)
			);

			while ($arUser = $rsUsers->fetch())
			{
				$arResult['~USER_NAMES'][$arUser['ID']] = CUser::FormatName(
					$arParams['NAME_TEMPLATE'], 
					array(
						'NAME'        => $arUser['NAME'],
						'LAST_NAME'   => $arUser['LAST_NAME'],
						'SECOND_NAME' => $arUser['SECOND_NAME'],
						'LOGIN'       => $arUser['LOGIN']
					),
					$bUseLogin = true,
					$bHtmlSpecialChars = false
				);
			}
		}
		//}
	}

	// Links for mode switching
	$arResult['VIEW_HREFS'] = array(
		'ROLES'           => array(),
		'VIEWS'           => array(),
		'TASK_CATEGORIES' => array(),
		'SUBMODES'        => array()
	);

	foreach ($arResult['VIEW_STATE']['ROLES'] as $roleCodeName => $roleData)
		$arResult['VIEW_HREFS']['ROLES'][$roleCodeName] = '?F_CANCEL=Y&F_STATE=sR' . base_convert($roleData['ID'], 10, 32);

	foreach ($arResult['VIEW_STATE']['VIEWS'] as $viewCodeName => $viewData)
		$arResult['VIEW_HREFS']['VIEWS'][$viewCodeName] = '?F_CANCEL=Y&F_STATE=sV' . base_convert($viewData['ID'], 10, 32);

	$curUserFilterSwitch = '';
	if ($arResult['F_CREATED_BY'])
		$curUserFilterSwitch .= '&F_CREATED_BY=' . (int) $arResult['F_CREATED_BY'];
	if ($arResult['F_RESPONSIBLE_ID'])
		$curUserFilterSwitch .= '&F_RESPONSIBLE_ID=' . (int) $arResult['F_RESPONSIBLE_ID'];

	$curRoleSwitch = '&F_STATE[]=sR' . base_convert($arResult['VIEW_STATE']['ROLE_SELECTED']['ID'], 10, 32);
	foreach ($arResult['VIEW_STATE']['TASK_CATEGORIES'] as $categoryCodeName => $categoryData)
		$arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName] = '?F_CANCEL=Y' . $curRoleSwitch . $curUserFilterSwitch . '&F_STATE[]=sC' . base_convert($categoryData['ID'], 10, 32);

	foreach ($arResult['VIEW_STATE']['SUBMODES'] as $submodeCodeName => $submodeData)
	{
		if ($submodeData['SELECTED'] === 'Y')
			$cmd = 'd';		// disable
		else
			$cmd = 'e';		// enable

		$arResult['VIEW_HREFS']['SUBMODES'][$submodeCodeName] = '?F_CANCEL=Y&F_STATE=' . $cmd . 'S' . base_convert($submodeData['ID'], 10, 32);
	}

	if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
	{
		$arResult['VIEW_COUNTERS'] = array(
			'TOTAL' => array(
				'COUNTER' => $oListCtrl->getMainCounter()
			),
			'ROLES' => array(
				'VIEW_ROLE_RESPONSIBLE' => array(
					'TOTAL' => array(
						'COUNTER' => $oListCtrl->getUserRoleCounter(CTaskListState::VIEW_ROLE_RESPONSIBLE)
					)
				),
				'VIEW_ROLE_ACCOMPLICE' => array(
					'TOTAL' => array(
						'COUNTER' => $oListCtrl->getUserRoleCounter(CTaskListState::VIEW_ROLE_ACCOMPLICE)
					)
				),
				'VIEW_ROLE_ORIGINATOR' => array(
					'TOTAL' => array(
						'COUNTER' => $oListCtrl->getUserRoleCounter(CTaskListState::VIEW_ROLE_ORIGINATOR)
					)
				),
				'VIEW_ROLE_AUDITOR' => array(
					'TOTAL' => array(
						'COUNTER' => $oListCtrl->getUserRoleCounter(CTaskListState::VIEW_ROLE_AUDITOR)
					)
				)
			)
		);

		// set extended counter info
		switch($selectedRoleName)
		{
			case 'VIEW_ROLE_RESPONSIBLE':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
					)
				);

			case 'VIEW_ROLE_ACCOMPLICE':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_NEW'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_NEW
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
					)
				);

			case 'VIEW_ROLE_AUDITOR':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
					)
				);
			break;

			case 'VIEW_ROLE_ORIGINATOR':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WAIT_CTRL'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
					)
				);
			break;
		}

		####################################################
		### counter break detection 
		####################################################
		/*
		The purpose of this section is to handle a situation when we cannot perform on-the-fly re-checking of some additional counters appeared on the page.
		Normally, we check and recount just one counter per page (request) using CTaskCountersProcessorHomeostasis::onTaskGetList when calling CTask::GetList. 
		But in some cases we have more then one counter on the page, so we unable to check them since CTaskListCtrl::resolveCounterIdByRoleAndCategory wont return ther ids.
		*/

		if(CTaskCountersProcessorInstaller::isInstallComplete()/*counter recalc is not in progress*/ && isset($arResult['VIEW_STATE']))
		{
			$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];
			
			if(CTaskListState::VIEW_SECTION_ROLES === $arResult['VIEW_STATE']['SECTION_SELECTED']['ID']) // only in "roles" section
			{
				$role = $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename];

				$countersSumValues = 
					(isset($role['VIEW_TASK_CATEGORY_NEW']['COUNTER']) ? 					intval($role['VIEW_TASK_CATEGORY_NEW']['COUNTER']) : 0) +
					(isset($role['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']) ? 				intval($role['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']) : 0) + 
					(isset($role['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']) ? 	intval($role['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']) : 0) + 
					(isset($role['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']) ? 				intval($role['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']) : 0) + 
					(isset($role['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']) ? 			intval($role['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']) : 0);

				if($role['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER'] < 0 
					||
					$role['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER'] < 0
					||
					$countersSumValues !== $role['TOTAL']['COUNTER'])
				{
					CTaskCountersProcessorHomeostasis::pendCountersRecalculation();
				}
			}
		}

		####################################################
		### counter break detection END
		####################################################

		// Set plural forms
		$arResult['VIEW_COUNTERS']['TOTAL']['PLURAL'] = CTasksTools::getPluralForm($arResult['VIEW_COUNTERS']['TOTAL']['COUNTER']);
		foreach ($arResult['VIEW_COUNTERS']['ROLES'] as $roleId => $arData)
		{
			foreach ($arData as $counterId => $arCounter)
			{
				$arResult['VIEW_COUNTERS']['ROLES'][$roleId][$counterId]['PLURAL'] = CTasksTools::getPluralForm($arCounter['COUNTER']);
			}
		}
	}

	$arResult['VIEW_SECTION_ADVANCED_FILTER_HREF'] = '?F_CANCEL=Y&F_SECTION=ADVANCED';

	$arResult['MARK_SECTION_ALL'] = 'N';

	if (
		($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER')
		&& ($arParams['MARK_SECTION_PROJECTS'] === 'N')
		&& ($arParams['MARK_SECTION_MANAGE'] === 'N')
		&& ($arParams['MARK_SECTION_REPORTS'] === 'N')
	)
	{
		$arResult['MARK_SECTION_ALL'] = 'Y';
	}
}

// arResult better formatting

$arResult['SELECTED_SECTION_NAME'] = $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'];

if (isset($arResult['VIEW_COUNTERS'], $arResult['VIEW_STATE']))
{
	if ($arResult['VIEW_STATE']['SECTION_SELECTED']['ID'] === CTaskListState::VIEW_SECTION_ROLES) // work only in "roles" state
	{
		$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];

		$arResult['SELECTED_ROLE_NAME']          = $selectedRoleCodename;
		$arResult['SELECTED_TASK_CATEGORY_NAME'] = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];

		$arResult['SELECTED_ROLE_COUNTER'] = array(
			'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['TOTAL']['COUNTER']
		);

		$arResult['SELECTED_ROLE_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['SELECTED_ROLE_COUNTER']['VALUE']);

		$arResult['TASKS_NEW_COUNTER'] = 				null;
		$arResult['TASKS_EXPIRED_COUNTER']= 			null;
		$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER'] = null;
		$arResult['TASKS_WAIT_CTRL_COUNTER'] = 			null;
		$arResult['TASKS_WAIT_CTRL_COUNTER'] = 			null;

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_NEW']['COUNTER']))
		{
			$arResult['TASKS_NEW_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_NEW']['COUNTER']
			);

			$arResult['TASKS_NEW_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['TASKS_NEW_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']))
		{
			$arResult['TASKS_EXPIRED_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']
			);

			$arResult['TASKS_EXPIRED_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['TASKS_EXPIRED_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']))
		{
			$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']
			);

			$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']))
		{
			$arResult['TASKS_WAIT_CTRL_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']
			);

			$arResult['TASKS_WAIT_CTRL_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']))
		{
			$arResult['TASKS_WO_DEADLINE_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']
			);

			$arResult['TASKS_WO_DEADLINE_COUNTER']['PLURAL'] = CTasksTools::getPluralForm($arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']);
		}
	}
}

$arResult['SEARCH_STRING'] = null;
$arResult['ADV_FILTER'] = array('F_ADVANCED' => 'N');
if (
	isset($arParams['ADV_FILTER']['F_ADVANCED'])
	&& ($arParams['ADV_FILTER']['F_ADVANCED'] === 'Y')
)
{
	$arResult['ADV_FILTER'] = $arParams['ADV_FILTER'];
	if (isset($arParams['ADV_FILTER']['F_META::ID_OR_NAME']))
		$arResult['SEARCH_STRING'] = $arParams['ADV_FILTER']['F_META::ID_OR_NAME'];
	elseif (isset($arParams['ADV_FILTER']['F_TITLE']))
		$arResult['SEARCH_STRING'] = $arParams['ADV_FILTER']['F_TITLE'];
}

// from\for switch
$queryList = $request->getQueryList();
$arResult['FROM_FOR_SWITCH'] = isset($queryList['SW_FF']) && $queryList['SW_FF'] == 'FROM' ? 'FROM' : 'FOR';

$this->IncludeComponentTemplate();
