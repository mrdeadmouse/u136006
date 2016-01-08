<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.contact.edit',
	'',
	array(
		'UID' => 'mobile_crm_contact_edit',
		'SERVICE_URL_TEMPLATE' => '/mobile/ajax.php?mobile_action=crm_contact_edit&site_id=#SITE#&sessid=#SID#',
		'CONTACT_SHOW_URL_TEMPLATE' => '/mobile/crm/contact/view.php?contact_id=#contact_id#',
		'CONTACT_EDIT_URL_TEMPLATE' => '/mobile/crm/contact/edit.php?contact_id=#contact_id#',				
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#',
		'STATUS_SELECTOR_URL_TEMPLATE' => '/mobile/crm/status/list.php?mode=selector&type_id=#type_id#&context_id=#context_id#',
		'COMPANY_SELECTOR_URL_TEMPLATE' => '/mobile/crm/client/list.php?entityTypes[]=company&scope=contact_edit&context_id=#context_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
