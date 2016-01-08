<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PS_LIST'] = CrmCheckPath('PATH_TO_PS_LIST', $arParams['PATH_TO_PS_LIST'], '');
$arParams['PATH_TO_PS_ADD'] = CrmCheckPath('PATH_TO_PS_ADD', $arParams['PATH_TO_PS_ADD'], '?add');
$arParams['PATH_TO_PS_EDIT'] = CrmCheckPath('PATH_TO_PS_EDIT', $arParams['PATH_TO_PS_EDIT'], '?ps_id=#ps_id#&edit');

$arResult['GRID_ID'] = 'CRM_PS_LIST_GRID';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['EDIT_FORM_ID'] = 'CRM_PS_EDIT_FORM';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'ACTIVE', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'PERSON_TYPE_NAME', 'name' => GetMessage('CRM_COLUMN_PERSON_TYPE_NAME'), 'sort' => false, 'default' => true, 'editable' => false),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	$IDs = isset($_POST['ID']) ? $_POST['ID'] : array();
	if($arResult['CAN_DELETE'] && $action === 'delete' && !empty($IDs))
	{
		foreach($IDs as $psID)
		{
			if(!CSalePaySystem::Delete($psID))
			{
				$errorMsg = '';

				if ($ex = $APPLICATION->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_PS_DELETION_GENERAL_ERROR')."<br>";

				ShowError($errorMsg);
			}
		}

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}
	elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		foreach($_POST['FIELDS'] as $ID => $arField)
		{
			$arFields = array();

			if(isset($arField['NAME']))
				$arFields['NAME'] = trim($arField['NAME']);

			if(isset($arField['ACTIVE']))
				$arFields['ACTIVE'] = trim($arField['ACTIVE']);

			if(isset($arField['SORT']))
				$arFields['SORT'] = (strlen($arField['SORT']) > 0) ? $arField['SORT'] : 100;

			if (count($arFields) > 0)
			{
				if(!CSalePaySystem::Update($ID, $arFields))
					ShowError(GetMessage('CRM_PS_UPDATE_GENERAL_ERROR'));
			}
		}
	}

	if(!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPage());
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && check_bitrix_sessid() && isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
	{
		$psID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if($psID > 0)
		{
			if(!CSalePaySystem::Delete($psID))
			{
				$errorMsg = '';

				if ($ex = $APPLICATION->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_PS_DELETION_GENERAL_ERROR')."<br>";

				ShowError($errorMsg);
			}
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_product' : '');
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$arSort = array();

$by = isset($_GET['by']) ? trim($_GET['by']) : 'ID';
$sort = isset($_GET['order']) ? trim($_GET['order']) : 'asc';

if(isset($_GET['by']) && isset($_GET['order']))
	$arSort = array($by => $sort);

$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('ID' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$paySystems = array();
$arCrmPtIDs = CCrmPaySystem::getPersonTypeIDs();
$dbPaySystems = CSalePaySystem::GetList($arSort, array( "PERSON_TYPE_ID" => $arCrmPtIDs ));

while($arPaySys = $dbPaySystems->Fetch())
{
	$tmpPS = array();
	$tmpPS['ID'] = $tmpPS['~ID'] = $arPaySys['ID'];
	$tmpPS['NAME'] = htmlspecialcharsbx($arPaySys['NAME']);
	$tmpPS['~NAME'] = $arPaySys['NAME'];
	$tmpPS['ACTIVE'] = $tmpPS['~ACTIVE'] = $arPaySys['ACTIVE'];

/**/
	$ptName = '';
	$dbPSAction = CSalePaySystemAction::GetList(
								array(),
								array(
									"PAY_SYSTEM_ID" => $tmpPS['ID'],
									"PERSON_TYPE_ID" => $arCrmPtIDs),
								false,
								false,
								array()
	);

	if ($arPSAction = $dbPSAction->Fetch())
	{
		$ptID = $arPSAction['PERSON_TYPE_ID'];
		$ptName = ($arCrmPtIDs['COMPANY'] == intval($ptID) ?  GetMessage('CRM_COMPANY_PT') : GetMessage('CRM_CONTACT_PT'));
	}

	$tmpPS['PERSON_TYPE_NAME'] = $tmpPS['~PERSON_TYPE_NAME'] = $ptName;



/**/
	$tmpPS['SORT'] = $tmpPS['~SORT'] = $arPaySys['SORT'];

	$tmpPS['PATH_TO_PS_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PS_EDIT'],
			array('ps_id' => $arPaySys['ID'])
		);

	$tmpPS['PATH_TO_PS_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PS_LIST'],
				array('ps_id' => $arPaySys['ID'])
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arPaySys['ID'], 'sessid' => bitrix_sessid())
		);


	$paySystems[] = $tmpPS;
}

$arResult['PAY_SYSTEMS'] = array();
$rowCount = $arResult['ROWS_COUNT'] = count($paySystems);
for($i = 0; $i < $rowCount; $i++)
{
	$tmpPS = $paySystems[$i];
	$arResult['PAY_SYSTEMS'][$tmpPS['ID']] = $tmpPS;
}

$this->IncludeComponentTemplate();
?>