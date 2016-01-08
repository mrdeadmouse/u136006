<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (isset($arParams['JUST_SHOW_BULK_TEMPLATE']) && ($arParams['JUST_SHOW_BULK_TEMPLATE'] === 'Y'))
{
	$arResult = array();
	$this->IncludeComponentTemplate();
	return $arResult;
}

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
	if (intval($arParams['TASK_ID']) > 0)
		$arParams['TASK_ID'] = (int) $arParams['TASK_ID'];
}
else
	$arParams['TASK_ID'] = 0;

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

if (is_string($arParams['PATH_TO_USER_TASKS_EDIT']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_EDIT'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_EDIT']
	);
}




$paramsCheck = (($arParams['SHOW_TEMPLATE'] === 'Y') || ($arParams['SHOW_TEMPLATE'] === 'N'))
	&& ($arParams['RENDER_FORMAT'] === 'HTML')
	&& is_int($arParams['USER_ID'])
	&& is_int($arParams['TASK_ID'])
	&& ($arParams['TASK_ID'] > 0)
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
	&& is_string($arParams['PATH_TO_TASKS_TASK'])
	&& is_string($arParams['PATH_TO_TASKS_EDIT']);

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

$rsTask = CTasks::GetByID($arParams['TASK_ID']);

if ( ! (is_object($rsTask) && ($arTask = $rsTask->Fetch())) )
{
	if (($arParams['SHOW_TEMPLATE'] === 'Y') && ($arParams['RENDER_FORMAT'] === 'HTML'))
	{
		ShowError(
			str_replace(
				'#TASK_ID#',
				(int) $arParams['TASK_ID'],
				GetMessage('MB_TASKS_TASK_DETAIL_TASK_NOT_ACCESSIBLE')
			)
		);
	}

	return (false);
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

$arTask['META::STATUS_FORMATTED_NAME'] = '';

switch ($arTask['REAL_STATUS'])
{
	case CTasks::STATE_NEW:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_NEW');
	break;

	case CTasks::STATE_PENDING:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_ACCEPTED');
	break;

	case CTasks::STATE_IN_PROGRESS:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_IN_PROGRESS');
	break;

	case CTasks::STATE_SUPPOSEDLY_COMPLETED:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_WAITING');
	break;

	case CTasks::STATE_COMPLETED:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_COMPLETED');
	break;

	case CTasks::STATE_DEFERRED:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_DELAYED');
	break;

	case CTasks::STATE_DECLINED:
		$arTask['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_DECLINED');
	break;

	default:
		$arTask['META::STATUS_FORMATTED_NAME'] = $arTask['REAL_STATUS'];
	break;
}

$arTask['META::STATUS_FORMATTED_NAME'] .= ' ' 
	. GetMessage('MB_TASKS_TASK_DETAIL_STATUS_DATE_PREPOSITION')
	. ' '
	. CTasksTools::FormatDatetimeBeauty(
		$arTask['STATUS_CHANGED_DATE'], 
		array(), 		// params
		$arParams['DATE_TIME_FORMAT']
	);

$arTask['META:DEADLINE_FORMATTED'] = '';
if (MakeTimeStamp($arTask['DEADLINE']) > 86400)
{
	$arTask['META:DEADLINE_FORMATTED'] = CTasksTools::FormatDatetimeBeauty(
		$arTask['DEADLINE'], 
		array(), 		// params
		$arParams['DATE_TIME_FORMAT']
	);
}

// HTML-format must be supported in future, because old tasks' data not converted from HTML to BB
if ($arTask['DESCRIPTION_IN_BBCODE'] === 'N')
{
	// HTML detected, sanitize if need
	$arTask['DESCRIPTION'] = CTasksTools::SanitizeHtmlDescriptionIfNeed($arTask['DESCRIPTION']);
}
else
{
	$parser = new CTextParser();
	$arTask['DESCRIPTION'] = $parser->convertText($arTask['DESCRIPTION']);
}

// collect files data
if ($arTask['FILES'])
{
	$rsFiles = CFile::GetList(
		array(), 
		array('@ID' => implode(',', $arTask['FILES']))
	);
	$arTask['FILES'] = array();

	while ($arFile = $rsFiles->GetNext())
	{
		$arFile['META::SIZE_FORMATTED'] = CFile::FormatSize($arFile['FILE_SIZE']);
		$arTask['FILES'][] = $arFile;
	}

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
	{
		$arFile['META::SIZE_FORMATTED'] = CFile::FormatSize($arFile['FILE_SIZE']);
		$arTask['FORUM_FILES'][] = $arFile;
	}

	unset ($rsFiles, $arFile);
}

// Task last viewed by given user date
$arResult['LAST_VIEWED_DATE'] = $arTask['CREATED_DATE'];
if ($arTask['VIEWED_DATE'])
	$arResult['LAST_VIEWED_DATE'] = $arTask['VIEWED_DATE'];

// Avatars and names for task members
$arTaskMembers = array((int) $arTask['CREATED_BY'], (int) $arTask['RESPONSIBLE_ID']);

if (isset($arTask['ACCOMPLICES']) && is_array($arTask['ACCOMPLICES']))
	$arTaskMembers = array_merge($arTaskMembers, $arTask['ACCOMPLICES']);

if (isset($arTask['AUDITORS']) && is_array($arTask['AUDITORS']))
	$arTaskMembers = array_merge($arTaskMembers, $arTask['AUDITORS']);

$arTaskMembers = array_unique(array_map('intval', $arTaskMembers));

$rsUser = CUser::GetList(
	$passByReference1 = 'id', 	// order by
	$passByReference2 = 'asc', 	// order direction
	$passByReference3 = array(		// filter
		'ID' => implode('|', $arTaskMembers)
	),
	array(
		'FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO',)
	)
);

$arUsersExtraData = array();
while ($arUser = $rsUser->Fetch())
{
	$arUsersExtraData[$arUser['ID']] = $arUser;

	$arUsersExtraData[$arUser['ID']]['META:NAME_FORMATTED'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'], 
		array(
			'NAME'        => $arUser['NAME'], 
			'LAST_NAME'   => $arUser['LAST_NAME'], 
			'SECOND_NAME' => $arUser['SECOND_NAME'], 
			'LOGIN'       => $arUser['LOGIN']
		),
		true,
		false	// don't use htmlspecialcharsbx
	);

	if (intval($arUser['PERSONAL_PHOTO']) > 0)
	{
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
}
unset($rsUser, $arUser);

$arTask['META::RESPONSIBLE_FORMATTED_NAME'] = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['META:NAME_FORMATTED'];
$arTask['META::ORIGINATOR_FORMATTED_NAME'] = $arUsersExtraData[$arTask['CREATED_BY']]['META:NAME_FORMATTED'];
$arTask['META::RESPONSIBLE_PHOTO_SRC'] = $arUsersExtraData[$arTask['RESPONSIBLE_ID']]['META:AVATAR_SRC'];
$arTask['META::ORIGINATOR_PHOTO_SRC'] = $arUsersExtraData[$arTask['CREATED_BY']]['META:AVATAR_SRC'];

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
	$arGroup = CSocNetGroup::GetByID($arTask['GROUP_ID']);
	$arTask['META:GROUP_NAME'] = $arGroup['NAME'];
}

$arTask['META:SOME_USERS_EXTRA_DATA'] = $arUsersExtraData;

$arTask['META::ALLOWED_ACTIONS'] = CTasks::GetAllowedActions($arTask);
$arResult['TASK'] = $arTask;

$arResult['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'];

CTasks::UpdateViewed($arTask['ID'], $arParams['USER_ID']);

if ($arParams['SHOW_TEMPLATE'] === 'Y')
	$this->IncludeComponentTemplate();

return $arResult;
