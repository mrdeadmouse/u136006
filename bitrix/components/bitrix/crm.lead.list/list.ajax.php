<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmLeadListEndResonse'))
{
	function __CrmLeadListEndResonse($result)
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
	__CrmLeadListEndResonse(array('ERROR' => 'Could not include crm module.'));
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmLeadListEndResonse(array('ERROR' => 'Access denied.'));
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	if($userPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
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
		$arFilter['%FULL_NAME'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%TITLE'] = trim($search);
		$arFilter['%FULL_NAME'] = trim($search);
		$arFilter['LOGIC'] = 'OR';
	}

	$arSelect = array('ID', 'TITLE', 'FULL_NAME', 'STATUS_ID');
	$arOrder = array('TITLE' => 'ASC');
	$arData = array();
	$obRes = CCrmLead::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	$i = 0;
	$leadIndex = array();
	while ($arRes = $obRes->Fetch())
	{
		$arData[$i] =
			array(
				'id' => $multi? 'L_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
					array(
						'lead_id' => $arRes['ID']
					)
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => $arRes['FULL_NAME'],
				'type' => 'lead'
			)
		;
		$leadIndex[$arRes['ID']] = &$arData[$i];
		$i++;
	}

	// advanced info - phone number, e-mail
	$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => array_keys($leadIndex)));
	while($arRes = $obRes->Fetch())
	{
		if (isset($leadIndex[$arRes['ELEMENT_ID']])
			&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
		{
			$item = &$leadIndex[$arRes['ELEMENT_ID']];
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
	unset($leadIndex);

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
elseif ($action === 'SAVE_PROGRESS')
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);
	if($statusID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid data!')
		);
		die();
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Access denied!')
		);
		die();
	}

	$arFields = CCrmLead::GetByID($ID, false);

	if(!is_array($arFields))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Not found!')
		);
		die();
	}

	if(isset($arFields['CREATED_BY_ID']))
	{
		unset($arFields['CREATED_BY_ID']);
	}

	if(isset($arFields['DATE_CREATE']))
	{
		unset($arFields['DATE_CREATE']);
	}

	if(isset($arFields['MODIFY_BY_ID']))
	{
		unset($arFields['MODIFY_BY_ID']);
	}

	if(isset($arFields['DATE_MODIFY']))
	{
		unset($arFields['DATE_MODIFY']);
	}

	$arFields['STATUS_ID'] = $statusID;
	$CCrmLead = new CCrmLead(false);
	if($CCrmLead->Update($ID, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true)))
	{
		$arErrors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$ID,
			CCrmBizProcEventType::Edit,
			$arErrors
		);
	}

	__CrmLeadListEndResonse(array('TYPE' => $targetTypeName, 'ID' => $ID, 'VALUE' => $statusID));
}
elseif ($action === 'REBUILD_DUPLICATE_INDEX')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmLeadListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadListEndResonse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadListEndResonse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmLead::CheckUpdatePermission(0))
	{
		__CrmLeadListEndResonse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmLeadListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS',  '');
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
		$totalItemQty = CCrmLead::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmLead::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 100),
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
		CCrmLead::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmLeadListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS');
		__CrmLeadListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>
