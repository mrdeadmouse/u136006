<?php
define('CRM_MODULE_CALENDAR_ID', 'calendar');

// Permissions -->
define('BX_CRM_PERM_NONE', '');
define('BX_CRM_PERM_SELF', 'A');
define('BX_CRM_PERM_DEPARTMENT', 'D');
define('BX_CRM_PERM_SUBDEPARTMENT', 'F');
define('BX_CRM_PERM_OPEN', 'O');
define('BX_CRM_PERM_ALL', 'X');
define('BX_CRM_PERM_CONFIG', 'C');
// <-- Permissions

// Sonet entity types -->
define('SONET_CRM_LEAD_ENTITY', 'CRMLEAD');
define('SONET_CRM_CONTACT_ENTITY', 'CRMCONTACT');
define('SONET_CRM_COMPANY_ENTITY', 'CRMCOMPANY');
define('SONET_CRM_DEAL_ENTITY', 'CRMDEAL');
define('SONET_CRM_ACTIVITY_ENTITY', 'CRMACTIVITY');
define('SONET_CRM_INVOICE_ENTITY', 'CRMINVOICE');
//<-- Sonet entity types

global $APPLICATION, $DBType, $DB;

IncludeModuleLangFile(__FILE__);

require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/classes/general/crm_usertypecrmstatus.php');
require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/classes/general/crm_usertypecrm.php');

CModule::AddAutoloadClasses(
	'crm',
	array(
		'CAllCrmLead' => 'classes/general/crm_lead.php',
		'CCrmLead' => 'classes/'.$DBType.'/crm_lead.php',
		'CCrmLeadWS' => 'classes/general/ws_lead.php',
		'CCRMLeadRest' => 'classes/general/rest_lead.php',
		'CAllCrmDeal' => 'classes/general/crm_deal.php',
		'CCrmDeal' => 'classes/'.$DBType.'/crm_deal.php',
		'CAllCrmCompany' => 'classes/general/crm_company.php',
		'CCrmCompany' => 'classes/'.$DBType.'/crm_company.php',
		'CAllCrmContact' => 'classes/general/crm_contact.php',
		'CCrmContact' => 'classes/'.$DBType.'/crm_contact.php',
		'CCrmContactWS' => 'classes/general/ws_contact.php',
		'CCrmPerms' => 'classes/general/crm_perms.php',
		'CCrmRole' => 'classes/general/crm_role.php',
		'CCrmFields' => 'classes/general/crm_fields.php',
		'CCrmUserType' => 'classes/general/crm_usertype.php',
		'CCrmGridOptions' => 'classes/general/crm_grids.php',
		'CCrmStatus' => 'classes/general/crm_status.php',
		'CCrmFieldMulti' => 'classes/general/crm_field_multi.php',
		'CCrmEvent' => 'classes/general/crm_event.php',
		'CCrmEMail' => 'classes/general/crm_email.php',
		'CCrmVCard' => 'classes/general/crm_vcard.php',
		'CCrmActivityTask' => 'classes/general/crm_activity_task.php',
		'CCrmActivityCalendar' => 'classes/general/crm_activity_calendar.php',
		'CUserTypeCrm' => 'classes/general/crm_usertypecrm.php',
		'CUserTypeCrmStatus' => 'classes/general/crm_usertypecrmstatus.php',
		'CCrmSearch' => 'classes/general/crm_search.php',
		'CCrmBizProc' => 'classes/general/crm_bizproc.php',
		'CCrmDocument' => 'classes/general/crm_document.php',
		'CCrmDocumentLead' => 'classes/general/crm_document_lead.php',
		'CCrmDocumentContact' => 'classes/general/crm_document_contact.php',
		'CCrmDocumentCompany' => 'classes/general/crm_document_company.php',
		'CCrmDocumentDeal' => 'classes/general/crm_document_deal.php',
		'CCrmReportHelper' => 'classes/general/crm_report_helper.php',
		'Bitrix\Crm\StatusTable' => 'lib/status.php',
		'Bitrix\Crm\EventTable' => 'lib/event.php',
		'Bitrix\Crm\EventRelationsTable' => 'lib/event.php',
		'Bitrix\Crm\DealTable' => 'lib/deal.php',
		'Bitrix\Crm\LeadTable' => 'lib/lead.php',
		'Bitrix\Crm\ContactTable' => 'lib/contact.php',
		'Bitrix\Crm\CompanyTable' => 'lib/company.php',
		'\Bitrix\Crm\StatusTable' => 'lib/status.php',
		'\Bitrix\Crm\EventTable' => 'lib/event.php',
		'\Bitrix\Crm\EventRelationsTable' => 'lib/event.php',
		'\Bitrix\Crm\DealTable' => 'lib/deal.php',
		'\Bitrix\Crm\LeadTable' => 'lib/lead.php',
		'\Bitrix\Crm\ContactTable' => 'lib/contact.php',
		'\Bitrix\Crm\CompanyTable' => 'lib/company.php',
		'CCrmExternalSale' => 'classes/general/crm_external_sale.php',
		'CCrmExternalSaleProxy' => 'classes/general/crm_external_sale_proxy.php',
		'CCrmExternalSaleImport' => 'classes/general/crm_external_sale_import.php',
		'CCrmUtils' => 'classes/general/crm_utils.php',
		'CCrmEntityHelper' => 'classes/general/entity_helper.php',
		'CAllCrmCatalog' => 'classes/general/crm_catalog.php',
		'CCrmCatalog' => 'classes/'.$DBType.'/crm_catalog.php',
		'CCrmCurrency' => 'classes/general/crm_currency.php',
		'CCrmCurrencyHelper' => 'classes/general/crm_currency_helper.php',
		'CCrmProductResult' => 'classes/general/crm_product_result.php',
		'CCrmProduct' => 'classes/general/crm_product.php',
		'CCrmProductHelper' => 'classes/general/crm_product_helper.php',
		'CAllCrmProductRow' => 'classes/general/crm_product_row.php',
		'CCrmProductRow' => 'classes/'.$DBType.'/crm_product_row.php',
		'CAllCrmInvoice' => 'classes/general/crm_invoice.php',
		'CCrmInvoice' => 'classes/'.$DBType.'/crm_invoice.php',
		'CAllCrmQuote' => 'classes/general/crm_quote.php',
		'CCrmQuote' => 'classes/'.$DBType.'/crm_quote.php',
		'CCrmOwnerType' => 'classes/general/crm_owner_type.php',
		'CCrmOwnerTypeAbbr' => 'classes/general/crm_owner_type.php',
		'Bitrix\Crm\ProductTable' => 'lib/product.php',
		'Bitrix\Crm\ProductRowTable' => 'lib/productrow.php',
		'Bitrix\Crm\IBlockElementProxyTable' => 'lib/iblockelementproxy.php',
		'Bitrix\Crm\IBlockElementGrcProxyTable' => 'lib/iblockelementproxy.php',
		'\Bitrix\Crm\ProductTable' => 'lib/product.php',
		'\Bitrix\Crm\ProductRowTable' => 'lib/productrow.php',
		'\Bitrix\Crm\IBlockElementProxyTable' => 'lib/iblockelementproxy.php',
		'\Bitrix\Crm\IBlockElementGrcProxyTable' => 'lib/iblockelementproxy.php',
		'CCrmAccountingHelper' => 'classes/general/crm_accounting_helper.php',
		'Bitrix\Crm\ExternalSaleTable' => 'lib/externalsale.php',
		'\Bitrix\Crm\ExternalSaleTable' => 'lib/externalsale.php',
		'CCrmExternalSaleHelper' => 'classes/general/crm_external_sale_helper.php',
		'CCrmEntityListBuilder' => 'classes/general/crm_entity_list_builder.php',
		'CCrmComponentHelper' => 'classes/general/crm_component_helper.php',
		'CCrmInstantEditorHelper' => 'classes/general/crm_component_helper.php',
		'CAllCrmActivity' => 'classes/general/crm_activity.php',
		'CCrmActivity' => 'classes/'.$DBType.'/crm_activity.php',
		'CCrmActivityType' => 'classes/general/crm_activity.php',
		'CCrmActivityStatus' => 'classes/general/crm_activity.php',
		'CCrmActivityPriority' => 'classes/general/crm_activity.php',
		'CCrmActivityNotifyType' => 'classes/general/crm_activity.php',
		'CCrmActivityStorageType' => 'classes/general/crm_activity.php',
		'CCrmContentType' => 'classes/general/crm_activity.php',
		'CCrmEnumeration' => 'classes/general/crm_enumeration.php',
		'CCrmEntitySelectorHelper' => 'classes/general/crm_entity_selector_helper.php',
		'CCrmBizProcHelper' => 'classes/general/crm_bizproc_helper.php',
		'CCrmBizProcEventType' => 'classes/general/crm_bizproc_helper.php',
		'CCrmUrlUtil' => 'classes/general/crm_url_util.php',
		'CCrmAuthorizationHelper' => 'classes/general/crm_authorization_helper.php',
		'CCrmWebDavHelper' => 'classes/general/crm_webdav_helper.php',
		'CCrmActivityDirection' => 'classes/general/crm_activity.php',
		'CCrmViewHelper' => 'classes/general/crm_view_helper.php',
		'CCrmSecurityHelper' => 'classes/general/crm_security_helper.php',
		'CCrmMailHelper' => 'classes/general/crm_mail_helper.php',
		'CCrmNotifier' => 'classes/general/crm_notifier.php',
		'CCrmNotifierSchemeType' => 'classes/general/crm_notifier.php',
		'CCrmActivityConverter' => 'classes/general/crm_activity_converter.php',
		'CCrmDateTimeHelper' => 'classes/general/datetime_helper.php',
		'CCrmEMailCodeAllocation' => 'classes/general/crm_email.php',
		'CCrmActivityCalendarSettings' => 'classes/general/crm_activity.php',
		'CCrmActivityCalendarSettings' => 'classes/general/crm_activity.php',
		'CCrmProductReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmReportManager' => 'classes/general/crm_report_helper.php',
		'CCrmCallToUrl' => 'classes/general/crm_url_util.php',
		'CCrmUrlTemplate' => 'classes/general/crm_url_util.php',
		'CCrmFileProxy' => 'classes/general/file_proxy.php',
		'CAllCrmMailTemplate' => 'classes/general/mail_template.php',
		'CCrmMailTemplate' => 'classes/'.$DBType.'/mail_template.php',
		'CCrmMailTemplateScope' =>  'classes/general/mail_template.php',
		'CCrmTemplateAdapter' =>  'classes/general/template_adapter.php',
		'CCrmTemplateMapper' =>  'classes/general/template_mapper.php',
		'CCrmTemplateManager' =>  'classes/general/template_manager.php',
		'CCrmGridContext' => 'classes/general/crm_grids.php',
		'CCrmUserCounter' => 'classes/general/user_counter.php',
		'CCrmUserCounterSettings' => 'classes/general/user_counter.php',
		'CCrmMobileHelper' => 'classes/general/mobile_helper.php',
		'CCrmStatusInvoice' => 'classes/general/crm_status_invoice.php',
		'CCrmTax' => 'classes/general/crm_tax.php',
		'CCrmVat' => 'classes/general/crm_vat.php',
		'CCrmLocations' => 'classes/general/crm_locations.php',
		'CCrmPaySystem' => 'classes/general/crm_pay_system.php',
		'CCrmRestService' => 'classes/general/restservice.php',
		'CCrmFieldInfo' => 'classes/general/field_info.php',
		'CCrmFieldInfoAttr' => 'classes/general/field_info.php',
		'CCrmActivityEmailSender' => 'classes/general/crm_activity.php',
		'CCrmProductSection' => 'classes/general/crm_product_section.php',
		'CCrmProductSectionDbResult' => 'classes/general/crm_product_section.php',
		'CCrmActivityDbResult' => 'classes/general/crm_activity.php',
		'CCrmInvoiceRestService' => 'classes/general/restservice_invoice.php',
		'CCrmInvoiceEvent' => 'classes/general/crm_invoice_event.php',
		'CCrmInvoiceEventFormat' => 'classes/general/crm_invoice_event.php',
		'CCrmLeadReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmInvoiceReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmActivityReportHelper' => 'classes/general/crm_report_helper.php',
		'CCrmLiveFeed' => 'classes/general/livefeed.php',
		'CCrmLiveFeedMessageRestProxy' => 'classes/general/restservice.php',
		'CCrmLiveFeedEntity' => 'classes/general/livefeed.php',
		'CCrmLiveFeedEvent' => 'classes/general/livefeed.php',
		'CCrmLiveFeedFilter' => 'classes/general/livefeed.php',
		'CCrmLiveFeedComponent' => 'classes/general/livefeed.php',
		'CAllCrmSonetRelation' => 'classes/general/sonet_relation.php',
		'CCrmSonetRelationType' => 'classes/general/sonet_relation.php',
		'CCrmSonetRelation' => 'classes/'.$DBType.'/sonet_relation.php',
		'CAllCrmSonetSubscription' => 'classes/general/sonet_subscription.php',
		'CCrmSonetSubscriptionType' => 'classes/general/sonet_subscription.php',
		'CCrmSonetSubscription' => 'classes/'.$DBType.'/sonet_subscription.php',
		'CCrmSipHelper' => 'classes/general/sip_helper.php',
		'CCrmSaleHelper' => 'classes/general/sale_helper.php',
		'CCrmProductFile' => 'classes/general/crm_product_file.php',
		'CCrmProductFileControl' => 'classes/general/crm_product_file.php',
		'CCrmProductPropsHelper' => 'classes/general/crm_productprops_helper.php',
		'CCrmProductSectionHelper' => 'classes/general/crm_product_section_helper.php'
	)
);

CModule::AddAutoloadClasses(
	'',
	array(
		'CAdminCalendar' => BX_ROOT.'/modules/main/interface/admin_lib.php' // for stupid function of module bizproc: CBPDocument::StartWorkflowParametersShow
	)
);

//Disable data initialization under agent context
if(CCrmSecurityHelper::GetCurrentUserID() > 0)
{
	// Convert LEAD & DEAL PRODUCT  -->
	if (COption::GetOptionString('crm', '~crm_11_0_6_convertion', 'N') !== 'Y')
	{
		$baseCurrencyID = "USD";
		$rsLang = CLanguage::GetByID("ru");
		if($arLang = $rsLang->Fetch())
			$baseCurrencyID = "RUB";
		else
		{
			$rsLang = CLanguage::GetByID("de");
			if($arLang = $rsLang->Fetch())
				$baseCurrencyID = "EUR";
		}

		$arProducts = CCrmStatus::GetStatusList('PRODUCT', true);
		foreach($arProducts as $prodCode => $prodName)
		{
			CCrmProduct::Add(
				array(
					'NAME' => $prodName,
					'ACTIVE' => 'Y',
					'CURRENCY_ID' => $baseCurrencyID,
					'PRICE' => 1,
					'ORIGIN_ID' => 'CRM_PROD_'.$prodCode,
					'ORIGINATOR_ID' => 'CRM_PRODUCT_REFERENCE'
				)
			);
		}

		$rsDeals = CCrmDeal::GetList(array('ID' => 'ASC'), array("CHECK_PERMISSIONS" => "N"), array('ID', 'PRODUCT_ID', 'OPPORTUNITY', 'CURRENCY_ID'));
		while($arDeal = $rsDeals->Fetch())
		{
			$ID = isset($arDeal['ID']) ? intval($arDeal['ID']) : 0;
			if($ID <= 0)
			{
				continue;
			}

			$productID = isset($arDeal['PRODUCT_ID']) ? $arDeal['PRODUCT_ID'] : '';
			if(isset($productID[0]))
			{
				$arProductRows = CCrmDeal::LoadProductRows($ID);
				if(count($arProductRows) > 0)
				{
					// Already converted
					continue;
				}

				$arProduct = CCrmProduct::GetByOriginID('CRM_PROD_'.$productID);
				if(!is_array($arProduct))
				{
					continue;
				}

				$productID = isset($arProduct['ID']) ? $arProduct['ID'] : 0;
				if($productID <= 0)
				{
					continue;
				}

				$arProductRows = array(
					array(
						'PRODUCT_ID' => $productID,
						'PRICE' => isset($arDeal['OPPORTUNITY']) ? doubleval($arDeal['OPPORTUNITY']) : 0.0,
						'QUANTITY' => 1
					)
				);

				CCrmDeal::SaveProductRows($ID, $arProductRows);
			}
		}

		$rsLeads = CCrmLead::GetList(array('ID' => 'ASC'), array("CHECK_PERMISSIONS" => "N"), array('ID', 'PRODUCT_ID', 'OPPORTUNITY', 'CURRENCY_ID'));
		while($arLead = $rsLeads->Fetch())
		{
			$ID = isset($arLead['ID']) ? intval($arLead['ID']) : 0;
			if($ID <= 0)
			{
				continue;
			}

			$productID = isset($arLead['PRODUCT_ID']) ? $arLead['PRODUCT_ID'] : '';
			if(isset($productID[0]))
			{
				$arProductRows = CCrmLead::LoadProductRows($ID);
				if(count($arProductRows) > 0)
				{
					// already converted
					continue;
				}

				$arProduct = CCrmProduct::GetByOriginID('CRM_PROD_'.$productID);
				if(!is_array($arProduct))
				{
					continue;
				}

				$productID = isset($arProduct['ID']) ? $arProduct['ID'] : 0;
				if($productID <= 0)
				{
					continue;
				}

				$arProductRows = array(
					array(
						'PRODUCT_ID' => $productID,
						'PRICE' => isset($arLead['OPPORTUNITY']) ? doubleval($arLead['OPPORTUNITY']) : 0.0,
						'QUANTITY' => 1
					)
				);

				CCrmLead::SaveProductRows($ID, $arProductRows);
			}
		}
		COption::SetOptionString('crm', '~crm_11_0_6_convertion', 'Y');
	}
	// <-- Convert LEAD CURRENCY and PRODUCT
	// Convert DEAL EVENTS -->
	if (COption::GetOptionString('crm', '~CRM_DEAL_EVENT_CONVERT_11_5_7', 'N') !== 'Y')
	{
		$dbDeals = CCrmDeal::GetListEx(
			array(),
			array(
				'@EVENT_ID' => array('PHONE', 'INFO')
			),
			false,
			false,
			array()
		);

		while($arDeal = $dbDeals->Fetch())
		{
			CCrmActivity::CreateFromDealEvent($arDeal);
		}

		COption::SetOptionString('crm', '~CRM_DEAL_EVENT_CONVERT_11_5_7', 'Y');
	}
	// <-- Convert DEAL EVENTS
	// SETUP DEFAULT RESPONSIBLE FOR COMPANIES-->
	if (COption::GetOptionString('crm', '~CRM_COMPANY_RESPONSIBLE_11_5_7', 'N') !== 'Y')
	{
		try
		{
			if(CCrmCompany::SetDefaultResponsible(true))
			{
				COption::SetOptionString('crm', '~CRM_COMPANY_RESPONSIBLE_11_5_7', 'Y');
			}
		}
		catch(Exception $e)
		{
		}
	}

	// FIX FOR CALENDAR EVENT BINBINGS-->
	if (COption::GetOptionString('crm', '~CRM_CAL_EVENT_BINDING_12_0_4', 'N') !== 'Y')
	{
		try
		{
			if($DB->TableExists('b_crm_act'))
			{
				CCrmActivity::RefreshCalendarBindings();
				COption::SetOptionString('crm', '~CRM_CAL_EVENT_BINDING_12_0_4', 'Y');
			}
		}
		catch(Exception $e)
		{
		}
	}
	//<-- FIX FOR CALENDAR EVENT BINBINGS
}
