<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmContactListEndResonse'))
{
	function __CrmContactListEndResonse($result)
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
	__CrmContactListEndResonse(array('ERROR' => 'Could not include crm module.'));
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmContactListEndResonse(array('ERROR' => 'Access denied.'));
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($_REQUEST['MODE'] == 'SEARCH')
{
	if($userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
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
		$arFilter['%FULL_NAME'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%FULL_NAME'] = trim($search);
		$arFilter['%COMPANY_TITLE'] = trim($search);
		$arFilter['LOGIC'] = 'OR';
	}
	$arContactTypeList = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
	$arSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID');
	$arOrder = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
	$arData = array();
	$obRes = CCrmContact::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	$i = 0;
	$contactIndex = array();
	$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');
	while ($arRes = $obRes->Fetch())
	{
		$photoID = intval($arRes['PHOTO']);
		if ($photoID > 0 && !isset($arFiles[$photoID]))
		{
			$arFiles[$photoID] = CFile::ResizeImageGet($photoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
		}

		// advanced info
		$advancedInfo = array();
		if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
		{
			$advancedInfo['contactType'] = array(
				'id' => $arRes['TYPE_ID'],
				'name' => $contactTypes[$arRes['TYPE_ID']]
			);
		}

		$arData[$i] =
			array(
				'id' => $multi? 'C_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $arRes['ID']
					)
				),
				'title' => CUser::FormatName(
					\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
					array(
						'LOGIN' => '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
					),
					false,
					false
				),
				'desc' => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
				'image' => isset($arFiles[$photoID]['src']) ? $arFiles[$photoID]['src'] : '',
				'type' => 'contact'
			)
		;
		if (!empty($advancedInfo))
			$arData[$i]['advancedInfo'] = $advancedInfo;
		unset($advancedInfo);
		$contactIndex[$arRes['ID']] = &$arData[$i];
		$i++;
	}

	// advanced info - phone number, e-mail
	$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => array_keys($contactIndex)));
	while($arRes = $obRes->Fetch())
	{
		if (isset($contactIndex[$arRes['ELEMENT_ID']])
			&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
		{
			$item = &$contactIndex[$arRes['ELEMENT_ID']];
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
	unset($contactIndex);

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
		__CrmContactListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactListEndResonse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactListEndResonse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmContact::CheckUpdatePermission(0))
	{
		__CrmContactListEndResonse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmContactListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmContact::GetListEx(
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
		CCrmContact::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmContactListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS');
		__CrmContactListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>