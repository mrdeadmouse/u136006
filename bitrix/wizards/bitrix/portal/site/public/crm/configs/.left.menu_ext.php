<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	GLOBAL $USER;
	$USER_ID = $USER->GetID();
	$CrmPerms = new CCrmPerms($USER_ID);
	$aMenuLinksExt = array();

	if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	{
		$aMenuLinksExt = array(
			Array(
				GetMessage("CRM_MENU_STATUS"),
				"#SITE_DIR#crm/configs/status/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_CURRENCY"),
				"#SITE_DIR#crm/configs/currency/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_TAX"),
				"#SITE_DIR#crm/configs/tax/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_LOCATIONS"),
				"#SITE_DIR#crm/configs/locations/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PS"),
				"#SITE_DIR#crm/configs/ps/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PERMS"),
				"#SITE_DIR#crm/configs/perms/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_BP"),
				"#SITE_DIR#crm/configs/bp/",
				Array(),
				Array(),
				"CModule::IncludeModule('bizproc') && CModule::IncludeModule('bizprocdesigner')"
			),
			Array(
				GetMessage("CRM_MENU_FIELDS"),
				"#SITE_DIR#crm/configs/fields/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PRODUCT_PROPS"),
				"#SITE_DIR#crm/configs/productprops/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_CONFIG"),
				"#SITE_DIR#crm/configs/config/",
				Array(),
				Array(),
				"CModule::IncludeModule('subscribe')"
			),
			Array(
				GetMessage("CRM_MENU_SENDSAVE"),
				"#SITE_DIR#crm/configs/sendsave/",
				Array(),
				Array(),
				"CModule::IncludeModule('mail')"
			),
			Array(
				GetMessage("CRM_MENU_SALE"),
				"#SITE_DIR#crm/configs/external_sale/",
				Array(),
				Array(),
				""
			),
			Array(
			   GetMessage("CRM_MENU_MEASURE"),
			   "#SITE_DIR#crm/configs/measure/",
			   Array(),
			   Array(),
			   ""
			)
		);
		if (LANGUAGE_ID == "ru")
		{
			$aMenuLinksExt[] = Array(
				GetMessage("CRM_MENU_EXCH1C"),
				"#SITE_DIR#crm/configs/exch1c/",
				Array(),
				Array(),
				""
			);
			$aMenuLinksExt[] = Array(
				GetMessage("CRM_MENU_INFO"),
				"#SITE_DIR#crm/configs/info/",
				Array(),
				Array(),
				""
			);
		}
	}
	if ($CrmPerms->IsAccessEnabled())
	{
		$aMenuLinksExt[] = Array(
			GetMessage("CRM_MENU_MAILTEMPLATE"),
			"#SITE_DIR#crm/configs/mailtemplate/",
			Array(),
			Array(),
			""
		);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>