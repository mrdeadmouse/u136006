<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.product.list',
	'',
	array(
		'SERVICE_URL_TEMPLATE'=> '/mobile/ajax.php?mobile_action=crm_product_list&site_id=#SITE#&sessid=#SID#',
		'UID' => 'mobile_crm_product_list'
	)
);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
