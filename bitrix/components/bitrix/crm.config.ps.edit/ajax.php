<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arReturn = array();

if (!CModule::IncludeModule('crm'))
	$arReturn['ERROR'][] = GetMessage('CRM_PS_MODULE_NOT_INSTALLED');

if(!isset($arReturn['ERROR']))
{
	$CrmPerms = new CCrmPerms($USER->GetID());
	$bCrmWritePerm = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

	if($USER->IsAuthorized() && check_bitrix_sessid() && $bCrmWritePerm)
	{
		$ID = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
		$personTypeId = isset($_REQUEST['person_type']) ? $_REQUEST['person_type'] : 0;
		$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

		switch ($action)
		{
				case 'get_fields':

					$arReturn['FIELDS'] = CCrmPaySystem::getPSCorrespondence($ID);
					$arReturn['FIELDS_LIST'] =  implode(',', array_keys($arReturn['FIELDS']));

				break;
		}
	}
	else
	{
		$arReturn['ERROR'][] = GetMessage('CRM_PS_ACCESS_DENIED');
	}
}

$arReturn = $APPLICATION->ConvertCharsetArray($arReturn, SITE_CHARSET, 'utf-8');
echo json_encode($arReturn);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>