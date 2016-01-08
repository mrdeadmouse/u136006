<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.invoice.view',
	'',
	array(
		'UID' => 'mobile_crm_invoice_view',
		'SERVICE_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_invoice_edit&site_id=#SITE#&sessid=#SID#',
		'INVOICE_SHOW_URL_TEMPLATE' => '/mobile/crm/invoice/view.php?invoice_id=#invoice_id#',		
		'INVOICE_EDIT_URL_TEMPLATE' => '/mobile/crm/invoice/edit.php?invoice_id=#invoice_id#',			
		'ACTIVITY_LIST_URL_TEMPLATE' => '/mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#',
		'ACTIVITY_EDIT_URL_TEMPLATE' => '/mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#',
		'COMMUNICATION_LIST_URL_TEMPLATE' => '/mobile/crm/comm/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#&type_id=#type_id#',		
		'EVENT_LIST_URL_TEMPLATE' => '/mobile/crm/invoice_event/list.php?entity_id=#entity_id#',
		'PRODUCT_ROW_LIST_URL_TEMPLATE' => '/mobile/crm/product_row/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#',
		'COMPANY_SHOW_URL_TEMPLATE' => '/mobile/crm/company/view.php?company_id=#company_id#',
		'CONTACT_SHOW_URL_TEMPLATE' => '/mobile/crm/contact/view.php?contact_id=#contact_id#',	
		'INVOICE_STATUS_SELECTOR_URL_TEMPLATE' => '/mobile/crm/progress_bar/list.php?mode=selector&entity_type=invoice&context_id=#context_id#',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
