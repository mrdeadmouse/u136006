<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.invoice.edit',
	'',
	array(
		'UID' => 'mobile_crm_invoice_edit',
		'SERVICE_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_invoice_edit&site_id=#SITE#&sessid=#SID#',
		'PRODUCT_ROW_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_product_row_edit&site_id=#SITE#&sessid=#SID#',
		'INVOICE_SHOW_URL_TEMPLATE' => '/mobile/crm/invoice/view.php?invoice_id=#invoice_id#',
		'INVOICE_EDIT_URL_TEMPLATE' => '/mobile/crm/invoice/edit.php?invoice_id=#invoice_id#',				
		'REQUISITE_EDIT_URL_TEMPLATE' => '/mobile/crm/requisite/edit.php?context_id=#context_id#?person_type_id=#person_type_id#',				
		'PRODUCT_ROW_EDIT_URL_TEMPLATE' => '/mobile/crm/product_row/edit.php?context_id=#context_id#',				
		'PRODUCT_SELECTOR_URL_TEMPLATE' => '/mobile/crm/product/list.php?list_mode=selector&currency_id=#currency_id#',
		'CLIENT_SELECTOR_URL_TEMPLATE' => '/mobile/crm/client/list.php?entityTypes[]=contact&entityTypes[]=company&scope=deal_edit&context_id=#context_id#',
		'INVOICE_STATUS_SELECTOR_URL_TEMPLATE' => '/mobile/crm/progress_bar/list.php?mode=selector&entity_type=invoice&context_id=#context_id#',
		'PAY_SYSTEM_SELECTOR_URL_TEMPLATE' => '/mobile/crm/pay_system/list.php?mode=selector&person_type_id=#person_type_id#&context_id=#context_id#',
		'LOCATION_SELECTOR_URL_TEMPLATE' => '/mobile/crm/location/list.php?mode=selector&context_id=#context_id#',
		'DEAL_SELECTOR_URL_TEMPLATE' => '/mobile/crm/deal/list.php?mode=selector',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
