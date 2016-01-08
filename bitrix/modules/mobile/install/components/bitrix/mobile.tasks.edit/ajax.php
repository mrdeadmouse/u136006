<?php
/*
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true); 
define('NO_KEEP_STATISTIC', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('NO_LANG_FILES', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_PUBLIC_TOOLS', true);
*/

global $APPLICATION;

$site_id = '';
if (isset($_REQUEST['site']) && is_string($_REQUEST['site']))
{
	$site_id = isset($_REQUEST['site'])? trim($_REQUEST['site']): '';
	$site_id = substr(preg_replace('/[^a-z0-9_]/i', '', $site_id), 0, 2);

	define('SITE_ID', $site_id);
}

//require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog.php');

require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//soundex($GLOBALS['USER']->GetID());

CUtil::JSPostUnescape();

if ( ! (
	isset($GLOBALS['USER']) 
	&& is_object($GLOBALS['USER']) 
	&& $GLOBALS['USER']->IsAuthorized()
))
{
	exit(json_encode(array('status' => 'failed')));
}

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

$arResult = array(
	'bResultInJson' => false,
	'result'        => array()
);

if (
	( ! CModule::IncludeModule('tasks') )
	|| ( ! $GLOBALS["USER"]->IsAuthorized() )
	//|| ( ! check_bitrix_sessid() )
)
{
	$arResult = array(
		'bResultInJson' => false,
		'result'        => array()
	);
}
elseif (
	isset($_REQUEST['USER_ID'])
	&& ($action === 'get_group_list_where_user_is_member')
	&& (CModule::IncludeModule('socialnetwork'))
)
{
	$userId = $GLOBALS['USER']->GetID();

	function addDataToTable($source = array(), $data = array(), $data_name = "", $dataID = false)
	{
		global $APPLICATION;

		if ($dataID === false)
			$dataID = 'data' . rand(1, 100000);

		$source['data'][$dataID] = $data;

		if (ToUpper(SITE_CHARSET) !== 'UTF-8')
			$data_name = $APPLICATION->ConvertCharset($data_name, SITE_CHARSET, 'utf-8');

		$source['names'][$dataID] = (string) $data_name;

		return ($source);
	}

	//cache data
	$cache = new CPHPCache();
	$cache_time = 3600*24*365;
	$cache_path = '/tasks_mobile_cache/' . $action;

	$cache_id = 'tasks|' . $action . '|' . (int) $userId . time();
	if ($cache->InitCache($cache_time, $cache_id, $cache_path))
	{
		$cachedData = $cache->GetVars();
		$data       = $cachedData["DATA"];
		$dataId     = $cachedData["dataId"];
	}
	else
	{
		$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U" . $userId);
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");

		$data = array();
		$arFilter = array(
			'ID'     => array(),
			'ACTIVE' => 'Y'
		);

		$dbUserGroups = CSocNetUserToGroup::GetList(
			array('GROUP_NAME' => 'ASC'),
			array(
				'USER_ID'       => $userId,
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
				$arFilter['ID'][] = $arUserGroups['GROUP_ID'];
		}

		if (count($arFilter['ID']) > 0)
		{
			$dbSocnetGroup = CSocNetGroup::GetList(
				array('NAME' => 'ASC'),
				$arFilter,
				false,
				false,
				array('ID', 'NAME', 'IMAGE_ID')
			);

			$woGroupName = GetMessage('MB_TASKS_TASK_EDIT_WO_GROUP');
			if (ToUpper(SITE_CHARSET) !== 'UTF-8')
				$woGroupName = $APPLICATION->ConvertCharsetArray($woGroupName, SITE_CHARSET, 'utf-8');

			$data[] = array(
				'ID' => 0,
				'NAME' => (string) $woGroupName,
				'OUTSECTION' => 1
			);

			while ($arSocnetGroup = $dbSocnetGroup->Fetch())
			{
				if (intval($arSocnetGroup['IMAGE_ID']) > 0)
				{
					$arImage = CFile::ResizeImageGet(
						$arSocnetGroup['IMAGE_ID'],
						array(
							'width'  => 80, 
							'height' => 80
						),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$img_src = (string) $arImage['src'];
				}
				else
					$img_src = false;

				$tmpData = array(
					'ID'    => (string) $arSocnetGroup['ID'],
					'NAME'  => (string) $arSocnetGroup['NAME'],
					'IMAGE' => $img_src,
					//'URL' => $userData['ID']
				);

				if (ToUpper(SITE_CHARSET) !== 'UTF-8')
					$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, 'utf-8');

				$data[] = $tmpData;
			}
		}

		$GLOBALS["CACHE_MANAGER"]->EndTagCache();

		$dataId = "b_groups";

		if ($cache->StartDataCache())
			$cache->EndDataCache(
				array(
					"DATA" => $data,
					"dataId" => $dataId
				)
			);
	}

	$tableTitle = GetMessage('MB_TASKS_TASK_EDIT_GROUPS_TABLE_TITLE');

	$tableData = null;

	if (count($data) > 0)
		$tableData = addDataToTable($tableData, $data, $tableTitle, $dataId);

	$arResult = array(
		'bResultInJson' => false,
		'result'        => $tableData
	);
}
else
{
	$arResult = array(
		'bResultInJson' => false,
		'result'        => array()
	);
}

$APPLICATION->RestartBuffer();

header('Content-Type: application/x-javascript');
if ($arResult['bResultInJson'])
	echo $arResult['result'];
else
{
	echo json_encode($arResult['result']);
}

define('PUBLIC_AJAX_MODE', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

exit();
