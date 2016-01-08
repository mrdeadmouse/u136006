<?php
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true); 
define('NO_KEEP_STATISTIC', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('NO_LANG_FILES', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_PUBLIC_TOOLS', true);

global $APPLICATION;

$site_id = '';
if (isset($_REQUEST['site']) && is_string($_REQUEST['site']))
{
	$site_id = isset($_REQUEST['site'])? trim($_REQUEST['site']): '';
	$site_id = substr(preg_replace('/[^a-z0-9_]/i', '', $site_id), 0, 2);

	define('SITE_ID', $site_id);
}

define("SITE_TEMPLATE_ID", "mobile_app");

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CUtil::JSPostUnescape();

if ( ! (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized()) )
	exit(json_encode(array('status' => 'failed')));

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']): '';

$lng = isset($_REQUEST['lang'])? trim($_REQUEST['lang']): 'en';
$lng = substr(preg_replace('/[^a-z0-9_]/i', '', $lng), 0, 2);

if ( ! defined('LANGUAGE_ID') )
{
	$rsSite = CSite::GetByID($site_id);
	if ($arSite = $rsSite->Fetch())
		define('LANGUAGE_ID', $arSite['LANGUAGE_ID']);
	else
		define('LANGUAGE_ID', 'en');
}

$langFilename = dirname(__FILE__) . '/lang/' . $lng . '/ajax.php';
if (file_exists($langFilename))
	__IncludeLang($langFilename);

if (CModule::IncludeModule('compression'))
	CCompress::Disable2048Spaces();

// write and close session to prevent lock;
session_write_close();

class MBTasksCptDetailAjax
{
	public static function PrepareCommentsData(
		$arAvatarSize, 
		$pathSmile,
		$forumId,
		$taskId,
		$userId,
		$pathToUser,
		$nameTemplate,
		$lastViewedDate,
		$dateTimeFormat,
		$arAlreadyLoadedComments,
		$newComment,
		$defMessagesCount = 3
	)
	{
		global $APPLICATION;

		$bUtf = true;
		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
			$bUtf = false;

		$arComponentParams = array(
			//'CACHE_TYPE'                 => $arParams['CACHE_TYPE'],
			//'CACHE_TIME'                 => $arParams['CACHE_TIME'],
			'DEFAULT_MESSAGES_COUNT'     => $defMessagesCount,
			'PATH_TO_SMILE'              => $pathSmile,
			'FORUM_ID'                   => (int) $forumId,
			'TASK_ID'                    => (int) $taskId,
			'USER_ID'                    => (int) $userId,
			'SHOW_RATING'                => 'Y',
			'RATING_TYPE'                => 'like',
			'URL_TEMPLATES_PROFILE_VIEW' => $pathToUser,
			'NAME_TEMPLATE'              => $nameTemplate,
			'TASK_LAST_VIEWED_DATE'      => $lastViewedDate,
			'AVATAR_SIZE'                => $arAvatarSize,
			'DATE_TIME_FORMAT'           => $dateTimeFormat,
			'SHOW_TEMPLATE'              => 'N',
			'MESSAGES_PER_PAGE'          => 10 // need for links in "Big tasks", must be equal to tasks.topic.reviews params
		);

		if ($newComment !== false)
		{
			$arComponentParams['ACTION'] = 'ADD_COMMENT';
			$arComponentParams['ACTION:MESSAGE'] = $newComment;
		}

		$rc = $GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:mobile.tasks.topic.reviews',
			'',
			$arComponentParams,
			false,
			array("HIDE_ICONS" => "Y")
		);

		if (is_array($arAlreadyLoadedComments))
		{
			foreach ($arAlreadyLoadedComments as $msgId)
			{
				if (isset($rc['MESSAGES'][$msgId]))
					unset($rc['MESSAGES'][$msgId]);
			}
		}

		if ( ! $bUtf )
		{
			$arFieldsShouldBeConverted = array(
				'POST_MESSAGE_TEXT', 'AUTHOR_PHOTO',
				'META:FORMATTED_DATA' => array(
					'AUTHOR_NAME', 'AUTHOR_URL', 'DATETIME_SEXY'
				)
			);

			$arMessages = '';
			if (is_array($rc['MESSAGES']))
			{
				foreach ($rc['MESSAGES'] as $k => $mess)
				{
					foreach ($arFieldsShouldBeConverted as $kk => $filedName)
					{
						if ( ! is_array($filedName) )
						{
							$rc['MESSAGES'][$k][$filedName] = $APPLICATION->ConvertCharset(
								$rc['MESSAGES'][$k][$filedName], 
								SITE_CHARSET, 
								'utf-8'
							);
						}
						else
						{
							foreach ($filedName as $fieldName)
							{
								$rc['MESSAGES'][$k][$kk][$fieldName] = 
									$APPLICATION->ConvertCharset(
										$rc['MESSAGES'][$k][$kk][$fieldName], 
										SITE_CHARSET, 
										'utf-8'
								);
							}
						}
					}
				}
			}
		}

		return (
			array(
				'task_id'       => (int) $taskId,
				'arComments'    => $rc['MESSAGES'],
				'totalComments' => $rc['MESSAGES_COUNT'],
				'givenComments' => count($rc['MESSAGES'])
			)
		);
	}


	public static function SaveTask($tasksData)
	{
		$rc = false;

		if ( ! $GLOBALS['USER']->IsAuthorized() )
			return (false);

		$delegateToUser = false;
		if (isset($tasksData['META::DELEGATE_TO_USER']))
			$delegateToUser = (int) $tasksData['META::DELEGATE_TO_USER'];

		$bDelegate = false;
		if ($delegateToUser > 0)
			$bDelegate = true;

		$curUserId = (int) $GLOBALS['USER']->GetID();

		if ( ! CModule::IncludeModule('socialnetwork') )
			return (false);

		$arNewTaskFields = false;
		$bErrorOccuredOnTaskCreation = false;
		if (isset($tasksData['TASK_ID']) && check_bitrix_sessid())
		{
			$bCreateMode = true;
			if ($tasksData['TASK_ID'] > 0)
				$bCreateMode = false;	// We are in edit mode

			if ($bCreateMode && $bDelegate)
				throw new Exception('$bCreateMode && $bDelegate');

			if ( ( ! $bCreateMode ) && $bDelegate )
			{
				$arNewTaskFields = array();

				if (intval($delegateToUser) !== $curUserId)
				{
					$arNewTaskFields['RESPONSIBLE_ID'] = $delegateToUser;
					$arNewTaskFields['STATUS'] = CTasks::STATE_PENDING;

					$rsTask = CTasks::GetByID($tasksData['TASK_ID']);
					$arTask = $rsTask->Fetch();

					if ((!$arTask) || (!isset($arTask['ID'])))
						return (false);

					if (sizeof($arTask['AUDITORS'] > 0))
					{
						if ( ! in_array($curUserId, $arTask['AUDITORS']) )
						{
							$arNewTaskFields['AUDITORS'] = $arTask['AUDITORS'];
							$arNewTaskFields['AUDITORS'][] = $curUserId;
						}
					}
					else
						$arNewTaskFields['AUDITORS'] = array($curUserId);
				}
				else
					return (false);
			}
			else
			{
				$arNewTaskFields = array(
					'TITLE'          => $tasksData['TITLE'],
					'DESCRIPTION'    => $tasksData['DESCRIPTION'],
					'RESPONSIBLE_ID' => $tasksData['RESPONSIBLE_ID'],
					'PRIORITY'       => $tasksData['PRIORITY'],
					'DEADLINE'       => CAllDatabase::FormatDate(
						str_replace('T', ' ', $tasksData['DEADLINE']), 
						'YYYY-MM-DD HH:MI:SS', 
						FORMAT_DATETIME
					)
				);

				if (isset($tasksData['ACCOMPLICES']))
				{
					if ($tasksData['ACCOMPLICES'] == -1)
						$arNewTaskFields['ACCOMPLICES'] = array();
					else
						$arNewTaskFields['ACCOMPLICES'] = $tasksData['ACCOMPLICES'];
				}

				if (isset($tasksData['AUDITORS']))
				{
					if ($tasksData['AUDITORS'] == -1)
						$arNewTaskFields['AUDITORS'] = array();
					else
						$arNewTaskFields['AUDITORS'] = $tasksData['AUDITORS'];
				}

				$arNewTaskFields['GROUP_ID'] = 0;
				if (isset($tasksData['GROUP_ID']) && (intval($tasksData['GROUP_ID']) > 0))
				{
					if (CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
							SONET_ENTITY_GROUP,
							(int) $tasksData['GROUP_ID'], 
							'tasks', 
							'create_tasks'
						)
					)
					{
						$arNewTaskFields['GROUP_ID'] = (int) $tasksData['GROUP_ID'];
					}
					else
						unset($arNewTaskFields['GROUP_ID']);
				}

				if ($bCreateMode)
					$arNewTaskFields['CREATED_BY'] = $curUserId;
			}

			if (isset($tasksData['META::EVENT_GUID']))
				$arNewTaskFields['META::EVENT_GUID'] = $tasksData['META::EVENT_GUID'];

			if ($bCreateMode)
				$arNewTaskFields['ID'] = 0;
			else
				$arNewTaskFields['ID'] = (int) $tasksData['TASK_ID'];

			$oTask = new CTasks();

			if ( ! $bCreateMode )	// in edit existing task mode
			{
				$rc = $oTask->Update($arNewTaskFields['ID'], $arNewTaskFields);
			}
			else	// in create new task mode
			{
				$arNewTaskFields['MULTITASK']  = 'N';
				$arNewTaskFields['DESCRIPTION_IN_BBCODE'] = 'Y';

				// Only creator or priveleged user can set responsible person.
				$arNewTaskFields['RESPONSIBLE_ID'] = $curUserId;
				if (
					($arNewTaskFields['CREATED_BY'] === $curUserId)
					|| $GLOBALS['USER']->IsAdmin() 
					|| CTasksTools::IsPortalB24Admin()
				)
				{
					$arNewTaskFields['RESPONSIBLE_ID'] = (int) $tasksData['RESPONSIBLE_ID'];
				}

				$arNewTaskFields['SITE_ID'] = SITE_ID;

				$rc = $oTask->Add($arNewTaskFields);
				if ($rc > 0)
					$arNewTaskFields['ID'] = $rc;
				else
					$bErrorOccuredOnTaskCreation = true;
			}

			$rc = $arNewTaskFields['ID'];
		}

		if ($bErrorOccuredOnTaskCreation)
			return (false);

		return ($rc);
	}


	public static function PrepareBaseData($task_id, $arRes)
	{
		global $APPLICATION;

		$bUtf = true;
		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
			$bUtf = false;

		if ($bUtf)
		{
			$title        = (string) $arRes['TASK']['TITLE'];
			$description  = (string) $arRes['TASK']['DESCRIPTION'];
			$respName     = (string) $arRes['TASK']['META::RESPONSIBLE_FORMATTED_NAME'];
			$respWork     = (string) $arRes['TASK']['RESPONSIBLE_WORK_POSITION'];
			$origName     = (string) $arRes['TASK']['META::ORIGINATOR_FORMATTED_NAME'];
			$origWork     = (string) $arRes['TASK']['CREATED_BY_WORK_POSITION'];
			$statusName   = (string) $arRes['TASK']['META::STATUS_FORMATTED_NAME'];
			$groupName    = (string) $arRes['TASK']['META:GROUP_NAME'];
			$deadlineText = (string) $arRes['TASK']['META:DEADLINE_FORMATTED'];
			$photoOrig    = (string) $arRes['TASK']['META::ORIGINATOR_PHOTO_SRC'];
			$photoResp    = (string) $arRes['TASK']['META::RESPONSIBLE_PHOTO_SRC'];
		}
		else
		{
			$title        = $APPLICATION->ConvertCharset((string) $arRes['TASK']['TITLE'], SITE_CHARSET, 'utf-8');
			$description  = $APPLICATION->ConvertCharset((string) $arRes['TASK']['DESCRIPTION'], SITE_CHARSET, 'utf-8');
			$respName     = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META::RESPONSIBLE_FORMATTED_NAME'], SITE_CHARSET, 'utf-8');
			$respWork     = $APPLICATION->ConvertCharset((string) $arRes['TASK']['RESPONSIBLE_WORK_POSITION'], SITE_CHARSET, 'utf-8');
			$origName     = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META::ORIGINATOR_FORMATTED_NAME'], SITE_CHARSET, 'utf-8');
			$origWork     = $APPLICATION->ConvertCharset((string) $arRes['TASK']['CREATED_BY_WORK_POSITION'], SITE_CHARSET, 'utf-8');
			$statusName   = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META::STATUS_FORMATTED_NAME'], SITE_CHARSET, 'utf-8');
			$groupName    = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META:GROUP_NAME'], SITE_CHARSET, 'utf-8');
			$deadlineText = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META:DEADLINE_FORMATTED'], SITE_CHARSET, 'utf-8');
			$photoOrig    = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META::ORIGINATOR_PHOTO_SRC'], SITE_CHARSET, 'utf-8');
			$photoResp    = $APPLICATION->ConvertCharset((string) $arRes['TASK']['META::RESPONSIBLE_PHOTO_SRC'], SITE_CHARSET, 'utf-8');
		}

		$arTaskData['baseData'] = array(
			'task_id'                    => (int) $task_id,
			'title'                      => $title,
			'description'                => $description,
			'responsible_id'             => (int) $arRes['TASK']['RESPONSIBLE_ID'],
			'responsible_formatted_name' => $respName,
			'responsible_work_position'  => $respWork,
			'responsible_photo_src'      => $photoResp,
			'originator_id'              => (int) $arRes['TASK']['CREATED_BY'],
			'originator_formatted_name'  => $origName,
			'originator_work_position'   => $origWork,
			'originator_photo_src'       => $photoOrig,
			'priority'                   => (int) $arRes['TASK']['PRIORITY'],
			'status_id'                  => (int) $arRes['TASK']['STATUS'],
			'status_formatted_name'      => $statusName,
			'group_id'                   => $arRes['TASK']['GROUP_ID'],
			'group_name'                 => $groupName,
			'deadline'                   => (string) $arRes['TASK']['DEADLINE'],
			'deadline_formatted'         => $deadlineText,
			'comments_count'             => '1'
		);

		return ($arTaskData['baseData']);
	}


	public static function PrepareDetailData($task_id, $arRes)
	{
		global $APPLICATION;

		$bUtf = true;
		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
			$bUtf = false;

		$arTaskData['detailsData'] = array(
			'task_id'     => $task_id,
			'files'       => array(),
			'accomplices' => array(),
			'auditors'    => array(),
			'forum_files' => array(),
			'actions'     => $arRes['TASK']['META::ALLOWED_ACTIONS']
		);

		if (isset($arRes['TASK']['FILES']) && is_array($arRes['TASK']['FILES']))
		{
			foreach ($arRes['TASK']['FILES'] as $arFileData)
			{
				if ($bUtf)
				{
					$fileName = (string) $arFileData['ORIGINAL_NAME'];
					$fileSize = (string) $arFileData['META::SIZE_FORMATTED'];
				}
				else
				{
					$fileName = $APPLICATION->ConvertCharset(
						(string) $arFileData['ORIGINAL_NAME'], SITE_CHARSET, 'utf-8');
					$fileSize = $APPLICATION->ConvertCharset(
						(string) $arFileData['META::SIZE_FORMATTED'], SITE_CHARSET, 'utf-8');
				}

				$arTaskData['detailsData']['files'][] = array(
					'id'             => (int) $arFileData['ID'],
					'name'           => $fileName,
					'size_formatted' => $fileSize
				);
			}
		}

		if (isset($arRes['TASK']['FORUM_FILES']) && is_array($arRes['TASK']['FORUM_FILES']))
		{
			foreach ($arRes['TASK']['FORUM_FILES'] as $arFileData)
			{
				if ($bUtf)
				{
					$fileName = (string) $arFileData['ORIGINAL_NAME'];
					$fileSize = (string) $arFileData['META::SIZE_FORMATTED'];
				}
				else
				{
					$fileName = $APPLICATION->ConvertCharset(
						(string) $arFileData['ORIGINAL_NAME'], SITE_CHARSET, 'utf-8');
					$fileSize = $APPLICATION->ConvertCharset(
						(string) $arFileData['META::SIZE_FORMATTED'], SITE_CHARSET, 'utf-8');
				}

				$arTaskData['detailsData']['forum_files'][] = array(
					'id'             => (int) $arFileData['ID'],
					'name'           => $fileName,
					'size_formatted' => $fileSize
				);
			}
		}

		if (isset($arRes['TASK']['ACCOMPLICES']) && is_array($arRes['TASK']['ACCOMPLICES']))
		{
			foreach ($arRes['TASK']['ACCOMPLICES'] as $memberId)
			{
				if ($bUtf)
					$userName = (string) $arRes['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $memberId]['META:NAME_FORMATTED'];
				else
				{
					$userName = $APPLICATION->ConvertCharset(
						(string) $arRes['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $memberId]['META:NAME_FORMATTED'],
						SITE_CHARSET, 
						'utf-8'
					);
				}

				$arTaskData['detailsData']['accomplices'][] = array(
					'user_id'        => (int) $memberId,
					'name_formatted' => $userName
				);
			}
		}

		if (isset($arRes['TASK']['AUDITORS']) && is_array($arRes['TASK']['AUDITORS']))
		{
			foreach ($arRes['TASK']['AUDITORS'] as $memberId)
			{
				if ($bUtf)
					$userName = (string) $arRes['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $memberId]['META:NAME_FORMATTED'];
				else
				{
					$userName = $APPLICATION->ConvertCharset(
						(string) $arRes['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $memberId]['META:NAME_FORMATTED'],
						SITE_CHARSET, 
						'utf-8'
					);
				}

				$arTaskData['detailsData']['auditors'][] = array(
					'user_id'        => (int) $memberId,
					'name_formatted' => $userName
				);
			}
		}

		return($arTaskData['detailsData']);
	}


	public static function ChangeTaskStatus($action_name, $curUserId, $arTask, $task_id, $arParams)
	{
		$newStatus = null;
		switch ($action_name)
		{
			case 'close':
				if (($arTask['CREATED_BY'] == $curUserId) || ($arTask['TASK_CONTROL'] == 'N'))
					$newStatus = CTasks::STATE_COMPLETED;
				else
					$newStatus = CTasks::STATE_SUPPOSEDLY_COMPLETED;
			break;

			case 'start':
				$newStatus = CTasks::STATE_IN_PROGRESS;
			break;

			case 'accept':
				$newStatus = CTasks::STATE_PENDING;
			break;

			case 'renew':
				$newStatus = CTasks::STATE_NEW;
			break;

			case 'defer':
				$newStatus = CTasks::STATE_DEFERRED;
			break;

			case 'decline':
				$newStatus = CTasks::STATE_DECLINED;
			break;

			default:
				throw new Exception();
			break;
		}

		$arFields = array(
			'STATUS' => $newStatus
		);

		if ($arFields)
		{
			$oTask = new CTasks();
			$oTask->Update(
				$task_id,
				$arFields
			);
		}

		$arRes = $GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:mobile.tasks.detail', 
			'.default', 
			$arParams, 
			false
		);

		$arTaskData = array(
			'task_id' => $task_id
		);

		$arTaskData['detailsData'] = MBTasksCptDetailAjax::PrepareDetailData($task_id, $arRes);
		
		$status_formatted_name = (string) $arRes['TASK']['META::STATUS_FORMATTED_NAME'];
		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
		{
			$status_formatted_name = $GLOBALS['APPLICATION']->ConvertCharset(
				$status_formatted_name,
				SITE_CHARSET, 
				'utf-8'
			);
		}

		$arTaskData['baseData'] = array(
			'task_id' => $task_id,
			'status_id' => $arRes['TASK']['STATUS'],
			'status_formatted_name' => $status_formatted_name
		);

		$arResult = array(
			'bResultInJson' => false,
			'result'        => array(
				'rc'   => 'executed',
				'action_perfomed' => (string) $action_name,
				'data' => $arTaskData
			)
		);

		return ($arResult);
	}


	public static function RemoveTask($task_id)
	{
		$rc = CTasks::Delete($task_id);

		$executed = 'failed';
		if ($rc)
			$executed = 'executed';

		$arResult = array(
			'bResultInJson' => false,
			'result' => array(
				'rc'              => $executed,
				'action_perfomed' => 'remove',
				'data'            => array('task_id' => (int) $task_id)
			)
		);

		return ($arResult);
	}
}
	
$arResult = array(
	'bResultInJson' => false,
	'result'        => array()
);

if (
	( ! CModule::IncludeModule('tasks') )
	|| ( ! $GLOBALS["USER"]->IsAuthorized() )
	|| ( ! check_bitrix_sessid() )
)
{
	$arResult = array(
		'bResultInJson' => false,
		'result'        => array()
	);
}
elseif (
	isset($_POST['user_id'])
	&& ($_POST['action'] === 'save task')
	&& isset($_POST['tasksData'])
)
{
	$rc = MBTasksCptDetailAjax::SaveTask($_POST['tasksData']);

	$arResult = array(
		'bResultInJson' => false,
		'result' => array(
			'action_done' => 'save task',
			'rc' => $rc
		)
	);
}
elseif (
	isset($_POST['user_id'])
	&& isset($_POST['task_id'])
	&& isset($_POST['subject'])
	&& in_array(
		$_POST['subject'], 
		array(
			'BASE',
			'BASE_AND_DETAIL_AND_COMMENTS', 
			'DETAIL_AND_COMMENTS', 
			'DETAIL', 
			'COMMENTS'
		), 
		true
	)
	&& isset($_POST['DATE_TIME_FORMAT'])
	&& (
		($action === 'get_task_data')
		|| (
			($action === 'perfom_action')
			&& isset($_POST['action_name'])
			&& (
				(
					($_POST['action_name'] === 'edit')
					&& isset($_POST['taskData'])
				)
				||
				(
					($_POST['action_name'] === 'delegate')
					&& isset($_POST['taskData'])
				)
				||
				(
					($_POST['action_name'] === 'add_comment')
					&& isset($_POST['NEW_COMMENT_TEXT'])
				)
			)
		)
	)
)
{
//s oundex('Detail ajax: begin work;');
//s oundex($_POST['subject']);
	$userId = (int) $_POST['user_id'];
	$taskId = $task_id = (int) $_POST['task_id'];
	$action_name = 'view';
	$newComment = false;

	if (
		($action === 'perfom_action')
		&& (
			($_POST['action_name'] === 'edit')
			|| ($_POST['action_name'] === 'delegate')
		)
	)
	{
		$action_name = $_POST['action_name'];

		$rc = MBTasksCptDetailAjax::SaveTask($_POST['taskData']);

		if ($rc > 0)
			$taskId = (int) $rc;
	}
	elseif (
		($_POST['action_name'] === 'add_comment')
		&& isset($_POST['NEW_COMMENT_TEXT'])
	)
	{
		$action_name = 'add_comment';
		$newComment = (string) $_POST['NEW_COMMENT_TEXT'];
	}

	$bLoadBaseData     = false;
	$bLoadDetailData   = false;
	$bLoadCommentsData = false;

	if ($taskId > 0)
	{

		if ($_POST['subject'] === 'BASE')
			$bLoadBaseData = true;

		if ($_POST['subject'] === 'BASE_AND_DETAIL_AND_COMMENTS')
		{
			$bLoadBaseData     = true;
			$bLoadDetailData   = true;
			$bLoadCommentsData = true;
		}
		elseif ($_POST['subject'] === 'DETAIL_AND_COMMENTS')
		{
			$bLoadDetailData   = true;
			$bLoadCommentsData = true;
		}
		elseif ($_POST['subject'] === 'DETAIL')
			$bLoadDetailData = true;
		elseif ($_POST['subject'] === 'COMMENTS')
			$bLoadCommentsData = true;

		$arParams = array(
			'USER_ID'          => $userId,
			'TASK_ID'          => $taskId,
			'DATE_TIME_FORMAT' => $_POST['DATE_TIME_FORMAT'],
			'SHOW_TEMPLATE'    => 'N',
			'PATH_TO_USER_TASKS_EDIT' => '...'
		);

		$arRes = $APPLICATION->IncludeComponent(
			'bitrix:mobile.tasks.detail', 
			'.default', 
			$arParams, 
			false
		);

		$filterCheckResult = 'not checked';
		// We must check filter condition for this task?
		if (isset($_POST['CHECK_FILTER_ID']))
		{
			$filterPresetId = (int) $_POST['CHECK_FILTER_ID'];
			$oFilter = CTaskFilterCtrl::GetInstance($userId);
			$arFilter = $oFilter->GetFilterPresetConditionById($filterPresetId);

			if ($arFilter !== false)
			{
				$arFilter['ID'] = (int) $taskId;
				$rsTasks = CTasks::GetList(
					$arOrder = array(),
					$arFilter,
					$arSelect = array('ID')
				);

				if ($rsTasks->Fetch())
					$filterCheckResult = 'match filter';
				else
					$filterCheckResult = 'dismatch filter';
			}
		}

		$arTaskData = array(
			'task_id' => $task_id,
			'filter_check_result' => $filterCheckResult
		);

		if ($bLoadBaseData)
			$arTaskData['baseData'] = MBTasksCptDetailAjax::PrepareBaseData($task_id, $arRes);

		if ($bLoadDetailData)
			$arTaskData['detailsData'] = MBTasksCptDetailAjax::PrepareDetailData($task_id, $arRes);


		if ($bLoadCommentsData
			&& isset($_POST['PATH_TO_FORUM_SMILE'])
			&& isset($_POST['PATH_TEMPLATE_TO_USER_PROFILE'])
			&& isset($_POST['AVA_WIDTH'])
			&& isset($_POST['AVA_HEIGHT'])
		)
		{
			$arAlreadyLoadedComments = false;
			// Unset messages, that are already loaded in interface
			if (isset($_POST['comments_already_loaded'])
				&& (strlen($_POST['comments_already_loaded']) > 0)
			)
			{
				$arAlreadyLoadedComments = explode('|', $_POST['comments_already_loaded']);
			}
		
			$arAvatarSize = array(
				'width'  => (int) $_POST['AVA_WIDTH'], 
				'height' => (int) $_POST['AVA_HEIGHT']
			);

			if (
				isset($_POST['DEFAULT_MESSAGES_COUNT'])
				&& ($_POST['DEFAULT_MESSAGES_COUNT'] > 0)
			)
			{
				$defMessagesCount = (int) $_POST['DEFAULT_MESSAGES_COUNT'];
			}
			else
				$defMessagesCount = 3;

			$arTaskData['commentsData'] = MBTasksCptDetailAjax::PrepareCommentsData(
				$arAvatarSize, 
				$_POST['PATH_TO_FORUM_SMILE'], 
				$arRes['FORUM_ID'],
				$_POST['task_id'],
				$_POST['user_id'],
				$_POST['PATH_TEMPLATE_TO_USER_PROFILE'],
				$arRes['NAME_TEMPLATE'],
				$lastViewedDate,
				$_POST['DATE_TIME_FORMAT'],
				$arAlreadyLoadedComments,
				$newComment,
				$defMessagesCount
			);
		}

		if (($action_name === 'edit') || ($action_name === 'delegate'))
		{
			$arResult = array(
				'bResultInJson' => false,
				'result'        => array(
					'rc'   => 'executed',
					'action_perfomed' => $action_name,
					'data' => $arTaskData
				)
			);
		}
		else
		{
			$arResult = array(
				'bResultInJson' => false,
				'result'        => $arTaskData
			);
		}
	}
	else
	{
		if ($action_name === 'edit')
		{
			$arResult = array(
				'bResultInJson' => false,
				'result'        => array(
					'rc'   => 'failed',
					'action_perfomed' => $action_name,
					'data' => false
				)
			);
		}
		else
		{
			$arResult = array(
				'bResultInJson' => false,
				'result'        => false
			);
		}
	}
}
elseif (
	isset($_POST['user_id'])
	&& isset($_POST['task_id'])
	&& isset($_POST['action_name'])
	&& isset($_POST['DATE_TIME_FORMAT'])
	&& in_array(
		(string) $_POST['action_name'],
		array(
			'close',
			'start',
			'accept',
			'renew',
			'defer',
			'decline',
			'remove'
		),
		true
	)	
	&& ($action === 'perfom_action')
)
{
	$arParams = array(
		'USER_ID'                 => (int) $_POST['user_id'],
		'TASK_ID'                 => (int) $_POST['task_id'],
		'DATE_TIME_FORMAT'        => $_POST['DATE_TIME_FORMAT'],
		'SHOW_TEMPLATE'           => 'N',
		'PATH_TO_USER_TASKS_EDIT' => '...'
	);

	$arRes = $GLOBALS['APPLICATION']->IncludeComponent(
		'bitrix:mobile.tasks.detail', 
		'.default', 
		$arParams, 
		false
	);

	$arTask = $arRes['TASK'];
	$arAllowedActions = $arRes['TASK']['META::ALLOWED_ACTIONS'];

	// Check, that action is allowed
	$bActionAllowed = false;
	foreach ($arAllowedActions as $arAllowedActionData)
	{
		if ($_POST['action_name'] === $arAllowedActionData['system_name'])
		{
			$bActionAllowed = true;
			break;
		}
	}

	$curUserId = $GLOBALS['USER']->GetID();

	if ($bActionAllowed)
	{
		if ($_POST['action_name'] === 'remove')
			$arResult = MBTasksCptDetailAjax::RemoveTask((int) $_POST['task_id']);
		else
		{
			$arResult = MBTasksCptDetailAjax::ChangeTaskStatus(
				$_POST['action_name'], 
				$curUserId, 
				$arTask, 
				(int) $_POST['task_id'], 
				$arParams
			);
		}
	}
	else
	{
		$arResult = array(
			'bResultInJson' => false,
			'result' => array(
				'rc'              => 'action_not_allowed',
				'action_perfomed' => $_POST['action_name']
			)
		);
	}
}
else
{
	$arResult = array(
		'bResultInJson' => false,
		'result'        => array()
	);
}

$APPLICATION->RestartBuffer();

//header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
if ($arResult['bResultInJson'])
{
	echo $arResult['result'];
}
else
{
	echo json_encode($arResult['result']);
}

define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

exit();
