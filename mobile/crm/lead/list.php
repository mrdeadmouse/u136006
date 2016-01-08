<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.lead.list',
	'',
	array(
		'UID' => 'mobile_crm_lead_list',
		'SERVICE_URL'=> SITE_DIR . 'mobile/ajax.php?mobile_action=crm_edit_list&siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
		'LEAD_SHOW_URL_TEMPLATE' => '/mobile/crm/lead/view.php?lead_id=#lead_id#',
		'LEAD_EDIT_URL_TEMPLATE' => '/mobile/crm/lead/edit.php?lead_id=#lead_id#',	
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
