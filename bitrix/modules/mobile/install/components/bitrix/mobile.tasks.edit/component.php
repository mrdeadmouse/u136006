<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult = array();

$environmentCheck = isset($GLOBALS['APPLICATION']) 
	&& is_object($GLOBALS['APPLICATION'])
	&& isset($GLOBALS['USER']) 
	&& is_object($GLOBALS['USER'])
	&& isset($arParams)
	&& is_array($arParams)
	&& CModule::IncludeModule('tasks')
	&& CModule::IncludeModule('socialnetwork')
	&& CModule::IncludeModule('forum');

if ( ! $environmentCheck )
	return (false);

unset ($environmentCheck);

if ( ! isset($arParams['AVATAR_SIZE']) )
	$arParams['AVATAR_SIZE'] = array('width' => 58, 'height' => 58);

if ( ! isset($arParams['DATE_TIME_FORMAT']) || empty($arParams['DATE_TIME_FORMAT']))
	$arParams['DATE_TIME_FORMAT'] = $DB->DateFormatToPHP(CSite::GetDateFormat('FULL'));

$arParams['DATE_TIME_FORMAT'] = trim($arParams['DATE_TIME_FORMAT']);

if ( ! isset($arParams['SHOW_TEMPLATE']) )
	$arParams['SHOW_TEMPLATE'] = 'Y';	// show template by default

if ( ! isset($arParams['RENDER_FORMAT']) )
	$arParams['RENDER_FORMAT'] = 'HTML';	// render as HTML by default

if (isset($arParams['NAME_TEMPLATE']) && (strlen($arParams['NAME_TEMPLATE']) > 0))
{
	$arParams['NAME_TEMPLATE'] = str_replace(
		array('#NOBR#','#/NOBR#'), 
		'', 
		$arParams['NAME_TEMPLATE']
	);
}
else
	$arParams['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

if (isset($arParams['USER_ID']))
{
	if (intval($arParams['USER_ID']) > 0)
		$arParams['USER_ID'] = (int) $arParams['USER_ID'];
}
else
	$arParams['USER_ID'] = (int) $GLOBALS['USER']->GetID();

if (isset($arParams['TASK_ID']))
{
	if (intval($arParams['TASK_ID']) >= 0)
		$arParams['TASK_ID'] = (int) $arParams['TASK_ID'];
}
else
	$arParams['TASK_ID'] = false;

// user paths
if ( ! isset($arParams['PATH_TO_USER_TASKS']) )
	$arParams['PATH_TO_USER_TASKS'] = '';

$arParams['PATH_TO_USER_TASKS'] = trim($arParams['PATH_TO_USER_TASKS']);	// Path to tasks list
if (strlen($arParams['PATH_TO_USER_TASKS']) <= 0)
	$arParams['PATH_TO_USER_TASKS'] = COption::GetOptionString('tasks', 'paths_task_user', null, SITE_ID);

if ( ! isset($arParams['PATH_TO_USER_TASKS_TASK']) )
	$arParams['PATH_TO_USER_TASKS_TASK'] = '';

$arParams['PATH_TO_USER_TASKS_TASK'] = trim($arParams['PATH_TO_USER_TASKS_TASK']);		// Path to tasks view
if (strlen($arParams['PATH_TO_USER_TASKS_TASK']) <= 0)
	$arParams['PATH_TO_USER_TASKS_TASK'] = COption::GetOptionString('tasks', 'paths_task_user_action', null, SITE_ID);

if ( ! isset($arParams['PATH_TEMPLATE_TO_USER_PROFILE']) )
	$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = '';

$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = trim($arParams['PATH_TEMPLATE_TO_USER_PROFILE']);

if (is_string($arParams['PATH_TO_USER_TASKS']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_TASK']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_TASK'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_TASK']
	);
}

$paramsCheck = (($arParams['SHOW_TEMPLATE'] === 'Y') || ($arParams['SHOW_TEMPLATE'] === 'N'))
	&& ($arParams['RENDER_FORMAT'] === 'HTML')
	&& is_int($arParams['USER_ID'])
	&& is_int($arParams['TASK_ID'])
	&& ($arParams['TASK_ID'] >= 0)
	&& is_string($arParams['NAME_TEMPLATE'])
	&& (strlen($arParams['NAME_TEMPLATE']) > 0)
	&& is_array($arParams['AVATAR_SIZE'])
	&& (count($arParams['AVATAR_SIZE']) === 2)
	&& isset($arParams['AVATAR_SIZE']['width'])
	&& isset($arParams['AVATAR_SIZE']['height'])
	&& is_string($arParams['DATE_TIME_FORMAT'])
	&& strlen($arParams['DATE_TIME_FORMAT'])
	&& is_string($arParams['PATH_TEMPLATE_TO_USER_PROFILE'])
	&& is_string($arParams['PATH_TO_USER_TASKS'])
	&& is_string($arParams['PATH_TO_USER_TASKS_TASK'])
	&& is_string($arParams['PATH_TO_TASKS'])
	&& is_string($arParams['PATH_TO_TASKS_TASK']);

if ( ! $paramsCheck )
	return (false);

unset ($paramsCheck);

if ( ! CBXFeatures::IsFeatureEnabled('Tasks') )
{
	if (($arParams['SHOW_TEMPLATE'] === 'Y') && ($arParams['RENDER_FORMAT'] === 'HTML'))
		ShowError(GetMessage('TASKS_MODULE_NOT_AVAILABLE_IN_THIS_EDITION'));

	return (false);
}

$rsUser = CUser::GetByID($arParams['USER_ID']);

if ( ! (is_object($rsUser) && ($arUser = $rsUser->GetNext())) )
	return (false);

$arResult['USER'] = $arUser;
unset ($rsUser, $arUser);

$arNewTaskFields = false;
$bErrorOccuredOnTaskCreation = false;
if (isset($_POST['TASK_ID']) && check_bitrix_sessid())
{
	$bCreateMode = true;
	if ($_POST['TASK_ID'] > 0)
		$bCreateMode = false;	// We are in edit mode

	$arNewTaskFields = array(
		'TITLE'          => $_POST['TITLE'],
		'DESCRIPTION'    => $_POST['DESCRIPTION'],
		'RESPONSIBLE_ID' => $_POST['RESPONSIBLE_ID'],
		'PRIORITY'       => $_POST['PRIORITY'],
		'DEADLINE'       => CAllDatabase::FormatDate(
			str_replace('T', ' ', $_POST['DEADLINE']), 
			'YYYY-MM-DD HH:MI:SS', 
			FORMAT_DATETIME
		)
	);

	if ($bCreateMode)
		$arNewTaskFields['ID'] = 0;
	else
		$arNewTaskFields['ID'] = (int) $_POST['TASK_ID'];

	$arNewTaskFields['GROUP_ID'] = 0;
	if (isset($_POST['GROUP_ID']) && (intval($_POST['GROUP_ID']) > 0))
	{
		if (CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				(int) $_POST['GROUP_ID'], 
				'tasks', 
				'create_tasks'
			)
		)
		{
			$arNewTaskFields['GROUP_ID'] = (int) $_POST['GROUP_ID'];
		}
		else
			unset($arNewTaskFields['GROUP_ID']);
	}

	$oTask = new CTasks();

	if ( ! $bCreateMode )	// in edit existing task mode
	{
		// Only priveleged users can change or set any ORIGINATOR
		$arNewTaskFields['CREATED_BY'] = (int) $GLOBALS['USER']->GetID();
		if ($USER->IsAdmin() || CTasksTools::IsPortalB24Admin())
			$arNewTaskFields['CREATED_BY'] = (int) $_POST['CREATED_BY'];

		$rc = $oTask->Update($arNewTaskFields['ID'], $arNewTaskFields);
	}
	else	// in create new task mode
	{
		$arNewTaskFields['MULTITASK']  = 'N';
		$arNewTaskFields['CREATED_BY'] = (int) $GLOBALS['USER']->GetID();
		$arNewTaskFields['DESCRIPTION_IN_BBCODE'] = 'Y';

		// Only creator or priveleged user can set responsible person.
		$arNewTaskFields['RESPONSIBLE_ID'] = (int) $GLOBALS['USER']->GetID();
		if (
			($arNewTaskFields['CREATED_BY'] === $arParams['USER_ID'])
			|| $USER->IsAdmin() 
			|| CTasksTools::IsPortalB24Admin()
		)
		{
			$arNewTaskFields['RESPONSIBLE_ID'] = (int) $_POST['RESPONSIBLE_ID'];
		}

		$arNewTaskFields['SITE_ID'] = SITE_ID;

		$rc = $oTask->Add($arNewTaskFields);
		if ($rc > 0)
			$arNewTaskFields['ID'] = $rc;
		else
			$bErrorOccuredOnTaskCreation = true;
	}

	unset($oTask);

	// Redirect to view details of this task
	if ($arNewTaskFields['ID'] > 0)
	{
		LocalRedirect(
			str_replace(
				array('#task_id#', '#TASK_ID#'), 
				$arNewTaskFields['ID'], 
				$arParams['PATH_TO_TASKS_TASK']
			)
		);
	}
	exit();
}

// Edit existing task?
if ($arParams['TASK_ID'] > 0)
{
	$rsTask = CTasks::GetByID($arParams['TASK_ID']);

	if ( ! (is_object($rsTask) && ($arTask = $rsTask->Fetch())) )
	{
		if (($arParams['SHOW_TEMPLATE'] === 'Y') && ($arParams['RENDER_FORMAT'] === 'HTML'))
		{
			ShowError(
				str_replace(
					'#TASK_ID#',
					(int) $arParams['TASK_ID'],
					GetMessage('MB_TASKS_TASK_EDIT_TASK_NOT_ACCESSIBLE')
				)
			);
		}

		return (false);
	}

	unset ($rsTask);
}
else
{
	// Creating new task
	if ($bErrorOccuredOnTaskCreation && ($arNewTaskFields !== false))
	{
		// Use data, that already exists
		$arTask = $arNewTaskFields;
	}
	else
	{
		// Use new data
		$arTask = array(
			'ID'             => 0,
			'GROUP_ID'       => 0,
			'TITLE'          => '',
			'DESCRIPTION'    => '',
			'DESCRIPTION_IN_BBCODE' => 'Y',
			'CREATED_BY'     => (int) $GLOBALS['USER']->GetID(),
			'RESPONSIBLE_ID' => $arParams['USER_ID'],
			'PRIORITY'       => CTasks::PRIORITY_AVERAGE,
			'DEADLINE'       => ''
		);

		if (isset($_GET['offerGroupId']))
		{
			// Get allowed groups for task
			$arAllowedTaskGroups = array();

			$dbUserGroups = CSocNetUserToGroup::GetList(
				array('GROUP_NAME' => 'ASC'),
				array(
					'USER_ID'       => (int) $arTask['CREATED_BY'],
					'<=ROLE'        => SONET_ROLES_USER,
					'GROUP_ACTIVE'  => 'Y'
				),
				false,
				false,
				array('GROUP_ID')
			);

			if ($dbUserGroups)
			{
				while ($arUserGroups = $dbUserGroups->GetNext())
					$arAllowedTaskGroups[] = (int) $arUserGroups['GROUP_ID'];
			}

			$offerGroupId = (int) $_GET['offerGroupId'];

			if (in_array($offerGroupId, $arAllowedTaskGroups, true))
				$arTask['GROUP_ID'] = (int) $_GET['offerGroupId'];
		}
	}
}

// Avatars&names for originator, responsible and current user
$rsUser = CUser::GetList(
	$passByReference1 = 'id', 	// order by
	$passByReference2 = 'asc', 	// order direction
	$passByReference3 = array(		// filter
		'ID' => implode(
			'|', 
			array_unique(
				array(
					(int) $arParams['USER_ID'], 
					(int) $arTask['CREATED_BY'], 
					(int) $arTask['RESPONSIBLE_ID']
				)
			)
		)
	)
);

$arUsersExtraData = array();
while ($arUser = $rsUser->Fetch())
{
	$arUsersExtraData[$arUser['ID']] = $arUser;

	if ( ! (intval($arUser['PERSONAL_PHOTO']) > 0) )
		continue;

	$imageFile = CFile::GetFileArray($arUser['PERSONAL_PHOTO']);
	if ($imageFile !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$imageFile, 
			array(
				"width"  => $arParams['AVATAR_SIZE']['width'], 
				"height" => $arParams['AVATAR_SIZE']['height']
			), 
			BX_RESIZE_IMAGE_EXACT, 
			false
		);

		$arUsersExtraData[$arUser['ID']]['META:AVATAR_SRC'] = $arFileTmp['src'];
	}
}
unset($rsUser, $arUser);

// There is no names in $arTask, if new task requested
if ($arParams['TASK_ID'] === 0)
{
	$arTask['RESPONSIBLE_NAME']        = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['NAME'];
	$arTask['RESPONSIBLE_LAST_NAME']   = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['LAST_NAME'];
	$arTask['RESPONSIBLE_SECOND_NAME'] = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['SECOND_NAME'];
	$arTask['RESPONSIBLE_LOGIN']       = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['LOGIN'];
}

$arResult['FORUM_ID'] = false;

if ($arTask['FORUM_TOPIC_ID'])
{
	$arTopic = CForumTopic::GetByID($arTask['FORUM_TOPIC_ID']);
	if ($arTopic)
		$arResult['FORUM_ID'] = (int) $arTopic['FORUM_ID'];
	
	unset($arTopic);
}

if ($arResult['FORUM_ID'] === false)
{
	try
	{
		$arResult['FORUM_ID'] = CTasksTools::GetForumIdForIntranet();
	}
	catch (Exception $e)
	{
		; // nothing to do here
	}
}

if ($arResult['FORUM_ID'] > 0)
	__checkForum($arResult['FORUM_ID']);	

// collect files data
if ($arTask['FILES'])
{
	$rsFiles = CFile::GetList(
		array(), 
		array('@ID' => implode(',', $arTask['FILES']))
	);
	$arTask['FILES'] = array();

	while ($arFile = $rsFiles->GetNext())
		$arTask['FILES'][] = $arFile;

	unset ($rsFiles, $arFile);
}

// collect comments files
$arTask['FORUM_FILES'] = array();
if ($arTask['FORUM_TOPIC_ID'])
{
	$rsFiles = CForumFiles::GetList(
		array('ID' => 'ASC'), 
		array('TOPIC_ID' => $arTask['FORUM_TOPIC_ID'])
	);

	while ($arFile = $rsFiles->GetNext())
		$arTask['FORUM_FILES'][] = $arFile;

	unset ($rsFiles, $arFile);
}

// Task last viewed by given user date
$arResult['LAST_VIEWED_DATE'] = $arTask['CREATED_DATE'];
if ($arTask['VIEWED_DATE'])
	$arResult['LAST_VIEWED_DATE'] = $arTask['VIEWED_DATE'];

// Format deadline
$arTask['META:FORMATTED_DATA']['DATETIME_SEXY'] = null;
if (MakeTimeStamp($arTask['DEADLINE']) > 86400)
{
	$arTask['META:FORMATTED_DATA']['DATETIME_SEXY'] = CTasksTools::FormatDatetimeBeauty(
		$arTask['DEADLINE'], 
		array(), 		// params
		$arParams['DATE_TIME_FORMAT']
	);
}

// Get group name
$arTask['META:GROUP_NAME'] = null;
if ($arTask['GROUP_ID'] > 0)
{
	$arGroup = htmlspecialcharsback(CSocNetGroup::GetByID($arTask['GROUP_ID']));
	$arTask['META:GROUP_NAME'] = $arGroup['NAME'];
}

$arTask['META:SOME_USERS_EXTRA_DATA'] = $arUsersExtraData;
$arResult['TASK'] = $arTask;

// Don't update last viewed date, if creating new task
if ($arTask['ID'] != 0)
	CTasks::UpdateViewed($arTask['ID'], $arParams['USER_ID']);

if ($arParams['SHOW_TEMPLATE'] === 'Y')
	$this->IncludeComponentTemplate();

return $arResult;
