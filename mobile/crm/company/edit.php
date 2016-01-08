<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.company.edit',
	'',
	array(
		'UID' => 'mobile_crm_company_edit',
		'SERVICE_URL_TEMPLATE' => '/mobile/ajax.php?mobile_action=crm_company_edit&site_id=#SITE#&sessid=#SID#',
		'COMPANY_SHOW_URL_TEMPLATE' => '/mobile/crm/company/view.php?company_id=#company_id#',
		'COMPANY_EDIT_URL_TEMPLATE' => '/mobile/crm/company/edit.php?company_id=#company_id#',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#',
		'STATUS_SELECTOR_URL_TEMPLATE' => '/mobile/crm/status/list.php?mode=selector&type_id=#type_id#&context_id=#context_id#',
		'CONTACT_SELECTOR_URL_TEMPLATE' => '/mobile/crm/client/list.php?entityTypes[]=contact&scope=company_edit&context_id=#context_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
