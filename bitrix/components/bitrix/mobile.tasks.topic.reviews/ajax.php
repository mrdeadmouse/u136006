<?php
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true); 
define('NO_KEEP_STATISTIC', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('NO_LANG_FILES', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_PUBLIC_TOOLS', true);

$site_id = '';
if (isset($_REQUEST['site']) && is_string($_REQUEST['site']))
{
	$site_id = isset($_REQUEST['site'])? trim($_REQUEST['site']): '';
	$site_id = substr(preg_replace('/[^a-z0-9_]/i', '', $site_id), 0, 2);

	define('SITE_ID', $site_id);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CUtil::JSPostUnescape();

if ( ! (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized()) )
	exit(json_encode(array('status' => 'failed')));

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']): '';

$lng = isset($_REQUEST['lang'])? trim($_REQUEST['lang']): '';
$lng = substr(preg_replace('/[^a-z0-9_]/i', '', $lng), 0, 2);

if ( ! defined('LANGUAGE_ID') )
{
	$rsSite = CSite::GetByID($site_id);
	if ($arSite = $rsSite->Fetch())
		define('LANGUAGE_ID', $arSite['LANGUAGE_ID']);
	else
		define('LANGUAGE_ID', 'en');
}

$langFilename = dirname(__FILE__)."/lang/".$lng."/ajax.php";
if (file_exists($langFilename))
	__IncludeLang($langFilename);

if (CModule::IncludeModule('compression'))
	CCompress::Disable2048Spaces();

// write and close session to prevent lock;
session_write_close();

$arResult = array();

if (
	( ! CModule::IncludeModule('tasks') )
	|| ( ! $GLOBALS["USER"]->IsAuthorized() )
	|| ( ! check_bitrix_sessid() )
)
{
	$arResult[0] = '*';
}
elseif (
	isset($_REQUEST['PATH_TO_SMILE'])
	&& isset($_REQUEST['FORUM_ID'])
	&& isset($_REQUEST['TASK_ID'])
	&& isset($_REQUEST['USER_ID'])
	&& isset($_REQUEST['URL_TEMPLATES_PROFILE_VIEW'])
	&& isset($_REQUEST['NAME_TEMPLATE'])
	&& isset($_REQUEST['TASK_LAST_VIEWED_DATE'])
	&& isset($_REQUEST['DATE_TIME_FORMAT'])
	&& isset($_REQUEST['avatar_width'])
	&& isset($_REQUEST['avatar_height'])
	&& (
		(
			($action === 'get_comments') 
			&& (isset($_POST['comments_already_loaded']))
		)
		|| (
			($action === 'add_comment')
			&& (isset($_POST['NEW_COMMENT_TEXT'])))
		)
)
{
	$arAvatarSize = array(
		'width'  => (int) $_REQUEST['avatar_width'], 
		'height' => (int) $_REQUEST['avatar_height']
	);

	$arResult['arComments'] = array();

	$arComponentParams = array(
		//'CACHE_TYPE'                 => $arParams['CACHE_TYPE'],
		//'CACHE_TIME'                 => $arParams['CACHE_TIME'],
		'DEFAULT_MESSAGES_COUNT'     => 500,
		'PATH_TO_SMILE'              => $_REQUEST['PATH_TO_SMILE'],
		'FORUM_ID'                   => (int) $_REQUEST['FORUM_ID'],
		'TASK_ID'                    => (int) $_REQUEST['TASK_ID'],
		'USER_ID'                    => (int) $_REQUEST['USER_ID'],
		'SHOW_RATING'                => 'Y',
		'RATING_TYPE'                => 'like',
		'URL_TEMPLATES_PROFILE_VIEW' => $_REQUEST['URL_TEMPLATES_PROFILE_VIEW'],
		'NAME_TEMPLATE'              => $_REQUEST['NAME_TEMPLATE'],
		'TASK_LAST_VIEWED_DATE'      => $_REQUEST['TASK_LAST_VIEWED_DATE'],
		'AVATAR_SIZE'                => $arAvatarSize,
		'DATE_TIME_FORMAT'           => $_REQUEST['DATE_TIME_FORMAT'],
		'SHOW_TEMPLATE'              => 'N',
		'MESSAGES_PER_PAGE'          => 10 // need for links in "Big tasks", must be equal to tasks.topic.reviews params
	);

	if ($action === 'add_comment')
	{
		$arComponentParams['ACTION'] = 'ADD_COMMENT';
		$arComponentParams['ACTION:MESSAGE'] = $_POST['NEW_COMMENT_TEXT'];
	}

	$rc = $APPLICATION->IncludeComponent(
		'bitrix:mobile.tasks.topic.reviews',
		'',
		$arComponentParams,
		false,
		Array("HIDE_ICONS" => "Y")
	);

	// Unset messages, that are already loaded in interface
	if (isset($_POST['comments_already_loaded'])
		&& (strlen($_POST['comments_already_loaded']) > 0)
	)
	{
		$arAlreadyLoadedComments = explode('|', $_POST['comments_already_loaded']);

		if (is_array($arAlreadyLoadedComments))
		{
			foreach ($arAlreadyLoadedComments as $msgId)
			{
				if (isset($rc['MESSAGES'][$msgId]))
					unset($rc['MESSAGES'][$msgId]);
			}
		}
	}

	$arResult['arComments'] = $rc['MESSAGES'];
}
else
{
	$arResult[0] = '*';
}

header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
echo CUtil::PhpToJSObject($arResult);

define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
