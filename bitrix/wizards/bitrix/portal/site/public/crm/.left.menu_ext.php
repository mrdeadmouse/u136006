<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS["USER"]->GetID());
	$arMenuCrm = Array();
	
	if (SITE_TEMPLATE_ID === "bitrix24")
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_DESKTOP"),
			"#SITE_DIR#crm/",
			Array(),
			Array(),
			""
		);
	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_STREAM"),
		"#SITE_DIR#crm/stream/",
		Array(),
		Array(),
		""
	);
	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_ACTIVITY"),
		"#SITE_DIR#crm/activity/",
		Array(),
		Array(),
		""
	);
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_CONTACT"),
			"#SITE_DIR#crm/contact/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_COMPANY"),
			"#SITE_DIR#crm/company/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_DEAL"),
			"#SITE_DIR#crm/deal/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_INVOICE"),
			"#SITE_DIR#crm/invoice/",
			Array(), 
			Array(), 
		"" 
		);
	}
	if (!$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_QUOTE"),
			"#SITE_DIR#crm/quote/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_LEAD"),
			"#SITE_DIR#crm/lead/",
			Array(),
			Array(),
			""
		);
	}

	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_PRODUCT"),
		"#SITE_DIR#crm/product/",
		Array(),
		Array(),
		""
	);

	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_HISTORY"),
			"#SITE_DIR#crm/events/", 
			Array(), 
			Array(), 
			"" 
		);
	}
	
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		if (IsModuleInstalled('report') || SITE_TEMPLATE_ID !== "bitrix24")
			$arMenuCrm[] = Array(
				GetMessage("MENU_CRM_REPORT"),
				CModule::IncludeModule('report') ? "#SITE_DIR#crm/reports/report/" : "#SITE_DIR#crm/reports/",
				Array(),
				Array(),
				""
			);
		
		if (SITE_TEMPLATE_ID === "bitrix24")
			$arMenuCrm[] = Array(
				GetMessage("MENU_CRM_FUNNEL"),
				"#SITE_DIR#crm/reports/",
				Array(),
				Array(),
				""
			);
	}
	if ($CrmPerms->IsAccessEnabled())
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_SETTINGS"),
			"#SITE_DIR#crm/configs/",
			Array(),
			Array(),
			""
		);
	}
	$aMenuLinks = array_merge($arMenuCrm, $aMenuLinks);
}

?>