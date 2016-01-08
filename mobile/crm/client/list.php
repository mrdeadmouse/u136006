<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.client.list',
	'',
	array(
		'UID' => 'mobile_crm_client_list_#SCOPE#',
		'CONTACT_EDIT_URL_TEMPLATE' => '/mobile/crm/contact/edit.php?contact_id=#contact_id#&context_id=#context_id#&enable_company=N',
		'COMPANY_EDIT_URL_TEMPLATE' => '/mobile/crm/company/edit.php?company_id=#company_id#&context_id=#context_id#&enable_contact=N'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
