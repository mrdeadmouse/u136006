<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.invoice.list',
	'',
	array(
		'SERVICE_URL' => SITE_DIR . 'mobile/ajax.php?mobile_action=crm_invoice_edit&siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
		'UID' => 'mobile_crm_invoice_list',
		'INVOICE_SHOW_URL_TEMPLATE' => '/mobile/crm/invoice/view.php?invoice_id=#invoice_id#',
		'INVOICE_EDIT_URL_TEMPLATE' => '/mobile/crm/invoice/edit.php?invoice_id=#invoice_id#',
		'USER_PROFILE_URL_TEMPLATE' => '/mobile/users/?user_id=#user_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
