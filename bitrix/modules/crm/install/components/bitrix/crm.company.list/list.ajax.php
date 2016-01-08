<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmCompanyListEndResonse'))
{
	function __CrmCompanyListEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	__CrmCompanyListEndResonse(array('ERROR' => 'Could not include crm module.'));
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmCompanyListEndResonse(array('ERROR' => 'Access denied.'));
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';

if ($_REQUEST['MODE'] == 'SEARCH')
{
	if($userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	{
		return;
	}

	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$search = trim($_REQUEST['VALUE']);

	$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
	$arFilter = array();
	if (is_numeric($search))
		$arFilter['ID'] = (int) $search;
	else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$arFilter['%TITLE'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}

	$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');

	foreach($arCompanyTypeList as $key => $value)
	if (strpos($value, $search) !== false)
		$arFilter['COMPANY_TYPE'][] = $key;

	foreach($arCompanyIndustryList as $key => $value)
	if (strpos($value, $search) !== false)
		$arFilter['INDUSTRY'][] = $key;


	$arSelect = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO');
	$arOrder = array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC');
	$arData = array();
	$obRes = CCrmCompany::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	$i = 0;
	$companyIndex = array();
	while ($arRes = $obRes->Fetch())
	{
		$logoID = intval($arRes['LOGO']);
		if ($logoID > 0 && !isset($arFiles[$logoID]))
		{
			$arFiles[$logoID] = CFile::ResizeImageGet($logoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
		}

		$arDesc = Array();
		if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
			$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
		if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
			$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];
		$arData[$i] =
			array(
				'id' => $multi? 'CO_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $arRes['ID']
					)
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => implode(', ', $arDesc),
				'image' => isset($arFiles[$logoID]['src']) ? $arFiles[$logoID]['src'] : '',
				'type' => 'company'
			)
		;
		$companyIndex[$arRes['ID']] = &$arData[$i];
		$i++;
	}

	// advanced info - phone number, e-mail
	$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => array_keys($companyIndex)));
	while($arRes = $obRes->Fetch())
	{
		if (isset($companyIndex[$arRes['ELEMENT_ID']])
			&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
		{
			$item = &$companyIndex[$arRes['ELEMENT_ID']];
			if (!is_array($item['advancedInfo']))
				$item['advancedInfo'] = array();
			if (!is_array($item['advancedInfo']['multiFields']))
				$item['advancedInfo']['multiFields'] = array();
			$item['advancedInfo']['multiFields'][] = array(
				'ID' => $arRes['ID'],
				'TYPE_ID' => $arRes['TYPE_ID'],
				'VALUE_TYPE' => $arRes['VALUE_TYPE'],
				'VALUE' => $arRes['VALUE']
			);
			unset($item);
		}
	}
	unset($companyIndex);

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
elseif ($action === 'REBUILD_DUPLICATE_INDEX')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyListEndResonse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyListEndResonse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmCompany::CheckUpdatePermission(0))
	{
		__CrmCompanyListEndResonse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmCompanyListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData) : array();

	if(empty($progressData) && intval(\Bitrix\Crm\BusinessTypeTable::getCount()) === 0)
	{
		//Try to fill BusinessTypeTable on first iteration
		\Bitrix\Crm\BusinessTypeTable::installDefault();
	}

	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmCompany::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = intval($fields['ID']);
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmCompany::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmCompanyListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS');
		__CrmCompanyListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>