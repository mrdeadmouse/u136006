<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_LANG_FILES', true);
define('DisableEventsCheck', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('BX_PUBLIC_TOOLS', true);
define('PUBLIC_AJAX_MODE', true);

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = $_REQUEST['site_id'];
	//Prevent LFI in prolog_before.php
	if($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('LANGUAGE_ID') )
{
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite ? $dbSite->Fetch() : null;
	define('LANGUAGE_ID', $arSite ? $arSite['LANGUAGE_ID'] : 'en');
}

//session_write_close();

if (!CModule::IncludeModule('crm'))
{
	die();
}

if (CModule::IncludeModule('compression'))
{
	CCompress::Disable2048Spaces();
}

global $APPLICATION, $DB;
$curUser = CCrmSecurityHelper::GetCurrentUser();

if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

//$langID = isset($_REQUEST['lang_id'])? $_REQUEST['lang_id']: LANGUAGE_ID;
//__IncludeLang(dirname(__FILE__).'/lang/'.$langID.'/'.basename(__FILE__));

CUtil::JSPostUnescape();

if(!function_exists('__CrmMobileProductListEndResonse'))
{
	function __CrmMobileProductListEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$curUserPrems = CCrmPerms::GetCurrentUserPermissions();
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === 'GET_SECTIONS')
{
	if (!$curUserPrems->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => 'Access denied.'));
	}

	$catalogID = isset($_REQUEST['CATALOG_ID']) ? intval($_REQUEST['CATALOG_ID']) : 0;

	if($catalogID <= 0 || !CCrmCatalog::Exists($catalogID))
	{
		__CrmMobileCompanyEditEndResonse(array('ERROR' => 'Catalog ID is not found.'));
	}

	$filter = array(
		'IBLOCK_ID' => $catalogID,
		'GLOBAL_ACTIVE' => 'Y',
		'CHECK_PERMISSIONS' => 'N'
	);

	CModule::IncludeModule('iblock');
	$sectionID = isset($_REQUEST['SECTION_ID']) ? intval($_REQUEST['SECTION_ID']) : 0;
	$parentSectionID = 0;
	$sectionName = '';
	if($sectionID > 0)
	{
		$dbSections = CIBlockSection::GetList(array(), array('ID' => $sectionID), false, array('ID', 'NAME', 'IBLOCK_SECTION_ID'));
		$section = $dbSections->Fetch();
		if($section)
		{
			$sectionName = $section['NAME'];
			$parentSectionID = intval($section['IBLOCK_SECTION_ID']);
		}
	}

	$filter['SECTION_ID'] = $sectionID;

	$dbSections = CIBlockSection::GetList(
		array('left_margin' => 'asc'),
		$filter,
		false,
		array('ID', 'NAME'),
		false
	);

	$result = array();
	while($section = $dbSections->Fetch())
	{
		$result[] = &$section;
		unset($section);
	}

	__CrmMobileProductListEndResonse(
		array(
			'MODELS' => $result,
			'CATALOG_ID' => $catalogID,
			'SECTION_ID' => $sectionID,
			'PARENT_SECTION_ID' => $parentSectionID,
			'SECTION_NAME' => $sectionName
		)
	);
}
else
{
	__CrmMobileProductListEndResonse(array('ERROR' => 'Action is not supported in current context.'));
}




