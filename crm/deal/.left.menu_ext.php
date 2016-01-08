<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/deal/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_ADD_DEAL"),
				'/crm/deal/edit/0/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_DEAL_LIST"),
				'/crm/deal/list/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_DEAL_IMPORT"),
				'/crm/deal/import/',
				Array(),
				Array(),
				''
			);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>