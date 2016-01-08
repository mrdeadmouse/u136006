<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/invoice/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_ADD_INVOICE"),
				'/crm/invoice/edit/0/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_INVOICE_LIST"),
				'/crm/invoice/list/',
				Array(),
				Array(),
				''
			);
	}
	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>