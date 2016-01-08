<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.deal.edit',
	'',
	array(
		'UID' => 'mobile_crm_deal_edit',
		'SERVICE_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_deal_edit&site_id=#SITE#&sessid=#SID#',
		'PRODUCT_ROW_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_product_row_edit&site_id=#SITE#&sessid=#SID#',
		'DEAL_SHOW_URL_TEMPLATE' => '/mobile/crm/deal/view.php?deal_id=#deal_id#',
		'DEAL_EDIT_URL_TEMPLATE' => '/mobile/crm/deal/edit.php?deal_id=#deal_id#',				
		'PRODUCT_ROW_EDIT_URL_TEMPLATE' => '/mobile/crm/product_row/edit.php?context_id=#context_id#',				
		'CLIENT_SELECTOR_URL_TEMPLATE' => '/mobile/crm/client/list.php?entityTypes[]=contact&entityTypes[]=company&scope=deal_edit&context_id=#context_id#',
		'CURRENCY_SELECTOR_URL_TEMPLATE' => '/mobile/crm/currency/list.php?mode=selector&context_id=#context_id#',
		'PRODUCT_SELECTOR_URL_TEMPLATE' => '/mobile/crm/product/list.php?list_mode=selector&currency_id=#currency_id#',
		'STATUS_SELECTOR_URL_TEMPLATE' => '/mobile/crm/status/list.php?mode=selector&type_id=#type_id#&context_id=#context_id#',
		'DEAL_STAGE_SELECTOR_URL_TEMPLATE' => '/mobile/crm/progress_bar/list.php?mode=selector&entity_type=deal&context_id=#context_id#',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
