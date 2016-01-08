<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_EVENT_LIST'] = CrmCheckPath('PATH_TO_EVENT_LIST', $arParams['PATH_TO_EVENT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

$arResult['EVENT_ENTITY_LINK'] = isset($arParams['EVENT_ENTITY_LINK']) && $arParams['EVENT_ENTITY_LINK'] == 'Y'? 'Y': 'N';
$arResult['EVENT_HINT_MESSAGE'] = isset($arParams['EVENT_HINT_MESSAGE']) && $arParams['EVENT_HINT_MESSAGE'] == 'N'? 'N': 'Y';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arResult['INTERNAL'] = isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y';
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL']? 'N': 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['PATH_TO_EVENT_DELETE'] =  CHTTP::urlAddParams($arParams['PATH_TO_EVENT_LIST'], array('sessid' => bitrix_sessid()));

if(isset($arParams['ENABLE_CONTROL_PANEL']))
{
	$arResult['ENABLE_CONTROL_PANEL'] = (bool)$arParams['ENABLE_CONTROL_PANEL'];
}
else
{
	$arResult['ENABLE_CONTROL_PANEL'] = !(isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y');
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$bInternal = false;
if ($arParams['INTERNAL'] == 'Y' || $arParams['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
$arResult['INTERNAL_EDIT'] = false;
if ($arParams['INTERNAL_EDIT'] == 'Y')
	$arResult['INTERNAL_EDIT'] = true;
$arResult['GADGET'] =  isset($arParams['GADGET']) && $arParams['GADGET'] == 'Y'? 'Y': 'N';

$entityType = isset($arParams['ENTITY_TYPE']) ? $arParams['ENTITY_TYPE'] : '';
$entityTypeID = CCrmOwnerType::ResolveID($entityType);

$arFilter = array();
if ($entityType !== '')
{
	$arFilter['ENTITY_TYPE'] = $arResult['ENTITY_TYPE'] = $entityType;
}

if (isset($arParams['ENTITY_ID']))
{
	if (is_array($arParams['ENTITY_ID']))
	{
		array_walk($arParams['ENTITY_ID'], create_function('&$v',  '$v = (int)$v;'));
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = $arParams['ENTITY_ID'];
	}
	elseif ($arParams['ENTITY_ID'] > 0)
	{
		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];
	}
}
else
{
	$ownerTypeID = isset($arParams['OWNER_TYPE']) ? CCrmOwnerType::ResolveID($arParams['OWNER_TYPE']) : CCrmOwnerType::Undefined;
	$ownerID = isset($arParams['OWNER_ID']) ? (int)$arParams['OWNER_ID'] : 0;
	if($ownerID > 0 && $ownerTypeID === CCrmOwnerType::Company && $entityTypeID === CCrmOwnerType::Contact)
	{
		$dbRes = CCrmContact::GetList(array(), array('COMPANY_ID' => $ownerID), array('ID'));
		$arContactID = array();
		while($arRow = $dbRes->Fetch())
		{
			$arContactID[] = (int)$arRow['ID'];
		}

		if(empty($arContactID))
		{
			return 0;
		}

		$arFilter['ENTITY_ID'] = $arResult['ENTITY_ID'] = $arContactID;
	}
}

if(isset($arParams['EVENT_COUNT']))
	$arResult['EVENT_COUNT'] = intval($arParams['EVENT_COUNT']) > 0? intval($arParams['EVENT_COUNT']): 50;
else
	$arResult['EVENT_COUNT'] = 50;

$arResult['PREFIX'] = isset($arParams['PREFIX']) ? strval($arParams['PREFIX']) : '';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['VIEW_ID'] = isset($arParams['VIEW_ID']) ? $arParams['VIEW_ID'] : '';

$filterFieldPrefix = $bInternal ? "{$arResult['TAB_ID']}_{$arResult['VIEW_ID']}" : '';
if($bInternal)
{
	$filterFieldPrefix = strtoupper($arResult['TAB_ID']).'_'.strtoupper($arResult['VIEW_ID']).'_';
}

$arResult['FILTER_FIELD_PREFIX'] = $filterFieldPrefix;

$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

if(strlen($arResult['VIEW_ID']))
	$arResult['GRID_ID'] = $arResult['INTERNAL'] ? 'CRM_INTERNAL_EVENT_LIST_'.$arResult['TAB_ID'].'_'.$arResult['VIEW_ID']: 'CRM_EVENT_LIST';
else
	$arResult['GRID_ID'] = $arResult['INTERNAL'] ? 'CRM_INTERNAL_EVENT_LIST_'.$arResult['TAB_ID'] : 'CRM_EVENT_LIST';


if(check_bitrix_sessid())
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_button_'.$arResult['GRID_ID']]))
	{
		if ($_POST['action_button_'.$arResult['GRID_ID']] == 'delete' && isset($_POST['ID']) && is_array($_POST['ID']))
		{
			$CCrmEvent =  new CCrmEvent;
			foreach($_POST['ID'] as $ID)
				$CCrmEvent->Delete($ID);
			unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_event');
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['action_'.$arResult['GRID_ID']]))
	{
		if ($_REQUEST['action_'.$arResult['GRID_ID']] == 'delete' && isset($_REQUEST['ID']) && $_REQUEST['ID'] > 0)
		{
			$CCrmEvent =  new CCrmEvent;
			$CCrmEvent->Delete((int)$_REQUEST['ID']);
			unset($_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_event');
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$arResult['GRID_ID']]))
	{
		if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
		{
			$CCrmEvent =  new CCrmEvent;
			$CCrmEvent->Delete((int)$_GET['ID']);
			unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if (!$arResult['IS_AJAX_CALL'])
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab='.$arResult['TAB_ID'] : '');
	}
}

$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array('EVENT_DESC');
$arResult['FILTER_PRESETS'] = array();

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('EVENT_DESC');

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => 'ID', 'default' => false),
	);

	$enabledEntityTypeNames = array();
	$currentUserPerms = CCrmPerms::GetCurrentUserPermissions();
	if (!$currentUserPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
	{
		$enabledEntityTypeNames[] = 'LEAD';
	}
	if (!$currentUserPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
	{
		$enabledEntityTypeNames[] = 'CONTACT';
	}
	if (!$currentUserPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	{
		$enabledEntityTypeNames[] = 'COMPANY';
	}
	if (!$currentUserPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
	{
		$enabledEntityTypeNames[] = 'DEAL';
	}
	if (!$currentUserPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
	{
		$enabledEntityTypeNames[] = 'QUOTE';
	}

	if(!empty($enabledEntityTypeNames))
	{
		ob_start();
		$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
			'',
			array(
				'ENTITY_TYPE' => $enabledEntityTypeNames,
				'INPUT_NAME' => 'ENTITY',
				'INPUT_VALUE' => isset($_REQUEST['ENTITY']) ? $_REQUEST['ENTITY'] : '',
				'FORM_NAME' => $arResult['GRID_ID'],
				'MULTIPLE' => 'N',
				'FILTER' => true,
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();

		$arResult['FILTER'][] =
			array('id' => 'ENTITY', 'name' => GetMessage('CRM_COLUMN_ENTITY'), 'default' => true, 'type' => 'custom', 'value' => $sVal);
	}

	$arEntityType = Array(
		'' => '',
		'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
		'CONTACT' => GetMessage('CRM_ENTITY_TYPE_CONTACT'),
		'COMPANY' => GetMessage('CRM_ENTITY_TYPE_COMPANY'),
		'DEAL' => GetMessage('CRM_ENTITY_TYPE_DEAL'),
		'QUOTE' => GetMessage('CRM_ENTITY_TYPE_QUOTE')
	);

	$arResult['FILTER'] = array_merge(
		$arResult['FILTER'],
		array(
			array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'default' => true, 'type' => 'list', 'items' => $arEntityType),
			array('id' => 'EVENT_TYPE', 'name' => GetMessage('CRM_COLUMN_EVENT_TYPE'), 'default' => true, 'type' => 'list', 'items' => array('' => '', '0' => GetMessage('CRM_EVENT_TYPE_USER'), '1' => GetMessage('CRM_EVENT_TYPE_CHANGE'), '2' => GetMessage('CRM_EVENT_TYPE_SNS'))),
			array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),
			array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC')),
			array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY_ID'), 'default' => true, 'enable_settings' => false, 'type' => 'user'),
			array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date')
		)
	);

	$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CREATE_TODAY'), 'fields' => array('DATE_CREATE_datesel' => 'today')),
		'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CREATE_YESTERDAY'), 'fields' => array('DATE_CREATE_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CREATE_MY'), 'fields' => array( 'CREATED_BY_ID' => $currentUserID, 'CREATED_BY_ID_name' => $currentUserName))
	);
}
elseif(isset($arParams['SHOW_INTERNAL_FILTER']) && strtoupper(strval($arParams['SHOW_INTERNAL_FILTER'])) === 'Y')
{
	$arResult['FILTER'] = array(
		array('id' => "{$filterFieldPrefix}ID", 'name' => 'ID', 'default' => false),
		array('id' => "{$filterFieldPrefix}EVENT_TYPE", 'name' => GetMessage('CRM_COLUMN_EVENT_TYPE'), 'default' => true, 'type' => 'list', 'items' => array('' => '', '0' => GetMessage('CRM_EVENT_TYPE_USER'), '1' => GetMessage('CRM_EVENT_TYPE_CHANGE'), '2' => GetMessage('CRM_EVENT_TYPE_SNS'))),
		array('id' => "{$filterFieldPrefix}EVENT_ID", 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),
		array('id' => "{$filterFieldPrefix}EVENT_DESC", 'name' => GetMessage('CRM_COLUMN_EVENT_DESC')),
		array('id' => "{$filterFieldPrefix}CREATED_BY_ID",  'name' => GetMessage('CRM_COLUMN_CREATED_BY_ID'), 'default' => true, 'enable_settings' => false, 'type' => 'user'),
		array('id' => "{$filterFieldPrefix}DATE_CREATE", 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date'),
	);
}

$arResult['HEADERS'] = array();
$arResult['HEADERS'][] = array('id' => 'ID', 'name' => 'ID', 'sort' => 'id', 'default' => false, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => false, 'default' => true, 'editable' => false, 'width'=>'140px');
if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
{
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'sort' => false, 'default' => true, 'editable' => false);
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TITLE'), 'sort' => false, 'default' => true, 'editable' => false);
}
$arResult['HEADERS'][] = array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => false, 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_NAME', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'sort' => false, 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC'), 'sort' => false, 'default' => true, 'editable' => false);

$arNavParams = array(
	'nPageSize' => $arResult['EVENT_COUNT']
);

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);

if (($arResult['TAB_ID'] === '' || $arResult['TAB_ID'] === $activeTabID)
	&& isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $arFilterField)
	{
		$filterFieldID = $arFilterField['id'];
		if ($arFilterField['type'] == 'user')
		{
			$urlParams[] = $filterFieldID.'_name';
		}
		if ($arFilterField['type'] == 'date')
		{
			$urlParams[] = $filterFieldID.'_datesel';
			$urlParams[] = $filterFieldID.'_days';
			$urlParams[] = $filterFieldID.'_from';
			$urlParams[] = $filterFieldID.'_to';
		}

		$urlParams[] = $filterFieldID;
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	if($arResult['TAB_ID'] !== '')
	{
		$urlParams[] = $tabParamName;
		LocalRedirect($APPLICATION->GetCurPageParam(
			urlencode($tabParamName).'='.urlencode($arResult['TAB_ID']),
			$urlParams));
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPageParam('',$urlParams));
	}
}

$arGridFilter = $CGridOptions->GetFilter($arResult['FILTER']);

$prefixLength = strlen($filterFieldPrefix);

if($prefixLength == 0)
{
	$arFilter = array_merge($arFilter, $arGridFilter);
}
else
{
	foreach($arGridFilter as $key=>&$value)
	{
		$arFilter[substr($key, $prefixLength)] = $value;
	}
}
unset($value);

foreach ($arFilter as $k => $v)
{
	$arMatch = array();
	if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		$arFilter['>='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if ($arMatch[1] == 'DATE_CREATE' && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			$v .=  ' 23:59:59';

		$arFilter['<='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
	else if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			//Bugfix #42761 replace logic field name
			$arFilter['?'.($k === 'EVENT_DESC' ? 'EVENT_TEXT_1' : $k)] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k == 'CREATED_BY_ID')
	{
		// For suppress comparison by LIKE
		$arFilter['=CREATED_BY_ID'] = $v;
		unset($arFilter['CREATED_BY_ID']);
	}
}

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('event_rel_id' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;
$arSelect = $CGridOptions->GetVisibleColumns();
// HACK: ignore entity related fields if entity info is not displayed
if ($arResult['EVENT_ENTITY_LINK'] !== 'Y')
{
	$key = array_search('ENTITY_TYPE', $arSelect, true);
	if($key !== false)
	{
		unset($arSelect[$key]);
	}

	$key = array_search('ENTITY_TITLE', $arSelect, true);
	if($key !== false)
	{
		unset($arSelect[$key]);
	}
}

$CGridOptions->SetVisibleColumns($arSelect);

$nTopCount = false;
if ($arResult['GADGET'] == 'Y')
{
	$nTopCount = $arResult['EVENT_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

$arEntityList = Array();
$arResult['EVENT'] = Array();

$obRes = CCrmEvent::GetListEx($arResult['SORT'], $arFilter, false, $arNavParams, array(), array());

$arResult['DB_LIST'] = $obRes;
$arResult['ROWS_COUNT'] = $obRes->NavRecordCount;
// Prepare raw filter ('=CREATED_BY' => 'CREATED_BY')
$arResult['DB_FILTER'] = array();
foreach($arFilter as $filterKey => &$filterItem)
{
	$info = CSqlUtil::GetFilterOperation($filterKey);
	$arResult['DB_FILTER'][$info['FIELD']] = $filterItem;
}
unset($filterItem);

while ($arEvent = $obRes->Fetch())
{
	$arEvent['~FILES'] = $arEvent['FILES'];
	$arEvent['~EVENT_NAME'] = $arEvent['EVENT_NAME'];
	if (!empty($arEvent['CREATED_BY_ID']))
		$arEvent['~CREATED_BY_FULL_NAME'] = CUser::FormatName(
			$arParams["NAME_TEMPLATE"],
			array(
				'LOGIN' => $arEvent['CREATED_BY_LOGIN'],
				'NAME' => $arEvent['CREATED_BY_NAME'],
				'LAST_NAME' => $arEvent['CREATED_BY_LAST_NAME'],
				'SECOND_NAME' => $arEvent['CREATED_BY_SECOND_NAME']
			),
			true, false
		);
	$arEvent['DATE_CREATE'] = $arEvent['DATE_CREATE'];
	$arEvent['CREATED_BY_FULL_NAME'] = htmlspecialcharsbx($arEvent['~CREATED_BY_FULL_NAME']);
	$arEvent['CREATED_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], array('user_id' => $arEvent['CREATED_BY_ID']));
	$arEvent['EVENT_NAME'] = htmlspecialcharsbx($arEvent['~EVENT_NAME']);

	$arEvent['EVENT_TEXT_1'] = strip_tags($arEvent['EVENT_TEXT_1'], '<br>');
	$arEvent['EVENT_TEXT_2'] = strip_tags($arEvent['EVENT_TEXT_2'], '<br>');

	if (strlen($arEvent['EVENT_TEXT_1'])>255 && strlen($arEvent['EVENT_TEXT_2'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'"><a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none"><b>'.GetMessage('CRM_EVENT_DESC_BEFORE').'</b>:<br>'.($arEvent['EVENT_TEXT_1']).'<br><br><b>'.GetMessage('CRM_EVENT_DESC_AFTER').'</b>:<br>'.($arEvent['EVENT_TEXT_2']).'</div>';
	}
	elseif (strlen($arEvent['EVENT_TEXT_1'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'">'.substr(($arEvent['EVENT_TEXT_1']), 0, 252).'... <a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none">'.($arEvent['EVENT_TEXT_1']).'</div>';
	}
	else if (strlen($arEvent['EVENT_TEXT_2'])>255)
	{
		$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'">'.substr(($arEvent['EVENT_TEXT_2']), 0, 252).'... <a href="#more" onclick="crm_event_desc('.$arEvent['ID'].'); return false;">'.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>';
		$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none">'.($arEvent['EVENT_TEXT_2']).'</div>';
	}
	else if (strlen($arEvent['EVENT_TEXT_1'])>0 && strlen($arEvent['EVENT_TEXT_2'])>0)
		$arEvent['EVENT_DESC'] = ($arEvent['EVENT_TEXT_1']).' <span>&rarr;</span> '.($arEvent['EVENT_TEXT_2']);
	else
		$arEvent['EVENT_DESC'] = !empty($arEvent['EVENT_TEXT_1'])? ($arEvent['EVENT_TEXT_1']): '';
	$arEvent['EVENT_DESC'] = nl2br($arEvent['EVENT_DESC']);

	$arEvent['FILES'] = $arEvent['~FILES'] = $arEvent['FILES'] !== '' ? unserialize($arEvent['FILES']) : array();
	if (!empty($arEvent['FILES']))
	{
		$i=1;
		$arFiles = array();
		$arFilter = array(
			'@ID' => implode(',', $arEvent['FILES'])
		);
		$rsFile = CFile::GetList(array(), $arFilter);
		while($arFile = $rsFile->Fetch())
		{
			$arFiles[$i++] = array(
				'NAME' => $arFile['ORIGINAL_NAME'],
				'PATH' => CComponentEngine::MakePathFromTemplate(
					'/bitrix/components/bitrix/crm.event.view/show_file.php?eventId=#event_id#&fileId=#file_id#',
					array('event_id' => $arEvent['ID'], 'file_id' => $arFile['ID'])
				),
				'SIZE' => CFile::FormatSize($arFile['FILE_SIZE'], 1)
			);
		}
		$arEvent['FILES'] = $arFiles;
	}
	$arEntityList[$arEvent['ENTITY_TYPE']][$arEvent['ENTITY_ID']] = $arEvent['ENTITY_ID'];

	$arResult['EVENT'][] = $arEvent;
}

if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
{
	if (isset($arEntityList['LEAD']) && !empty($arEntityList['LEAD']))
	{
		$dbRes = CCrmLead::GetList(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $arEntityList['LEAD']));
		while ($arRes = $dbRes->Fetch())
		{
			$arEntityList['LEAD'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arEntityList['CONTACT']) && !empty($arEntityList['CONTACT']))
	{
		$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $arEntityList['CONTACT']));
		while ($arRes = $dbRes->Fetch())
		{
			$arEntityList['CONTACT'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['LAST_NAME'].' '.$arRes['NAME'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arEntityList['COMPANY']) && !empty($arEntityList['COMPANY']))
	{
		$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC'), array('ID' => $arEntityList['COMPANY']));
		while ($arRes = $dbRes->Fetch())
		{
			$arEntityList['COMPANY'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arEntityList['DEAL']) && !empty($arEntityList['DEAL']))
	{
		$dbRes = CCrmDeal::GetList(Array('TITLE'=>'ASC'), array('ID' => $arEntityList['DEAL']));
		while ($arRes = $dbRes->Fetch())
		{
			$arEntityList['DEAL'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arEntityList['QUOTE']) && !empty($arEntityList['QUOTE']))
	{
		$dbRes = CCrmQuote::GetList(Array('TITLE'=>'ASC'), array('ID' => $arEntityList['QUOTE']));
		while ($arRes = $dbRes->Fetch())
		{
			$arEntityList['QUOTE'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'], array('quote_id' => $arRes['ID']))
			);
		}
	}

	foreach($arResult['EVENT'] as $key => $ar)
	{
		$arResult['EVENT'][$key]['ENTITY_TITLE'] = htmlspecialcharsbx($arEntityList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_TITLE']);
		$arResult['EVENT'][$key]['ENTITY_LINK'] = $arEntityList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_LINK'];
	}
}

$this->IncludeComponentTemplate();

return $obRes->SelectedRowsCount();

?>
