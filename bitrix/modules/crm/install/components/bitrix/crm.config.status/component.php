<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if (!CCrmQuote::LocalComponentCausedUpdater())
	return;

$arResult['ACTIVE_TAB'] = 'status_tab_STATUS';

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() &&
	isset($_POST['ACTION']) && $_POST['ACTION'] == 'save')
{
	$arAdd = array();
	$arUpdate = array();
	$arDelete = array();

	foreach($_POST['LIST'] as $entityId => $arFields)
	{
		$iPrevSort = 0;
		$CCrmStatus = false;

		/* Here we can define our own class to process own status type (if we need).
		 * It may be inherited from CCrmStatus.
		 * For example see CCrmStatusInvoice.
		 */
		$events = GetModuleEvents("crm", "OnBeforeCrmStatusCreate");

		while($arEvent = $events->Fetch())
		{
			$CCrmStatus = ExecuteModuleEventEx($arEvent, array($entityId));

			if($CCrmStatus)
				break;
		}

		if(!$CCrmStatus)
			$CCrmStatus = new CCrmStatus($entityId);

		foreach($arFields as $id => $arField)
		{
			$arField['SORT'] = (int)$arField['SORT'];
			if ($arField['SORT'] <= $iPrevSort)
				$arField['SORT'] = $iPrevSort + 10;
			$iPrevSort = $arField['SORT'];

			if (substr($id, 0, 1) == 'n')
			{
				if (trim($arField['VALUE']) == "")
					continue;

				$arAdd['NAME'] = trim($arField['VALUE']);
				$arAdd['SORT'] = $arField['SORT'];
				$CCrmStatus->Add($arAdd);
			}
			else
			{
				if (!isset($arField['VALUE']) || trim($arField['VALUE']) == "")
				{
					$arCurrentData = $CCrmStatus->GetStatusById($id);
					if ($arCurrentData['SYSTEM'] == 'N')
						$CCrmStatus->Delete($id);
					else
					{
						$arUpdate['NAME'] = trim($arCurrentData['NAME_INIT']);
						$CCrmStatus->Update($id, $arUpdate);
					}
				}
				else
				{
					$arCurrentData = $CCrmStatus->GetStatusById($id);
					if (trim($arField['VALUE']) != $arCurrentData['NAME'] || intval($arField['SORT']) != $arCurrentData['SORT'])
					{
						$arUpdate['NAME'] = trim($arField['VALUE']);
						$arUpdate['SORT'] = $arField['SORT'];
						$CCrmStatus->Update($id, $arUpdate);
					}
				}
			}
		}
	}
	$arResult['ACTIVE_TAB'] = $_POST['ACTIVE_TAB'];
}

$ar = CCrmStatus::GetEntityTypes();
foreach($ar as $entityId => $arEntityType)
{
	$arResult['HEADERS'][$entityId] = $arEntityType['NAME'];
	$arResult['ROWS'][$entityId] = Array();
}

$res = CCrmStatus::GetList(array('SORT' => 'ASC'));
while($ar = $res->Fetch())
{
	$arResult['ROWS'][$ar['ENTITY_ID']][$ar['ID']] = $ar;
}
/*
 * We can add here some elements to status list of own type(s)
 * TODO: transfer to GetList
 */

$events = GetModuleEvents("crm", "OnCrmStatusGetList");

while($arEvent = $events->Fetch())
{
	$arStatuses = ExecuteModuleEventEx($arEvent);
	foreach ($arStatuses as $key => $arStatus)
		$arResult['ROWS'][$arStatus['ENTITY_ID']][$arStatus['ID']] = $arStatus;
}
/**/

$arResult['NEED_FOR_FIX_STATUSES'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_FIX_STATUSES', 'N') === 'Y')
{
	$arResult['NEED_FOR_FIX_STATUSES'] = true;
}

CUtil::InitJSCore();
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();
$APPLICATION->AddChainItem(GetMessage('CRM_FIELDS_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);

?>