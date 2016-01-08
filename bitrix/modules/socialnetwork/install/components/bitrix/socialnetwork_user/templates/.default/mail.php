<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (IsModuleInstalled("intranet"))
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.mail.setup',
		'',
		array()
	);
}
?>