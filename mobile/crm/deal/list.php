<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.deal.list',
	'',
	array(
		'UID' => 'mobile_crm_deal_list',
		'SERVICE_URL' => SITE_DIR . 'mobile/ajax.php?mobile_action=crm_deal_list&siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
		'DEAL_SHOW_URL_TEMPLATE' => '/mobile/crm/deal/view.php?deal_id=#deal_id#',
		'DEAL_EDIT_URL_TEMPLATE' => '/mobile/crm/deal/edit.php?deal_id=#deal_id#&company_id=#company_id#&contact_id=#contact_id#',
		'COMPANY_SHOW_URL_TEMPLATE' => '/mobile/crm/company/view.php?company_id=#company_id#',
		'CONTACT_SHOW_URL_TEMPLATE' => '/mobile/crm/contact/view.php?contact_id=#contact_id#',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
