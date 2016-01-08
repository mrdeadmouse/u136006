<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$arResult = Array();

$arResult['SIP_ENABLE'] = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
$arResult['LIST_SIP_NUMBERS'] = Array();

$arResult['LINK_TO_BUY'] = '';
if (IsModuleInstalled('bitrix24'))
{
	if (LANGUAGE_ID != 'kz' )
	{
		$arResult['LINK_TO_BUY'] = '/settings/license_phone_sip.php';
	}
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=52&CHAPTER_ID=02564': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=55&LESSON_ID=6635');
}
else
{
	if (LANGUAGE_ID == 'ru')
	{
		$arResult['LINK_TO_BUY'] = 'http://www.1c-bitrix.ru/buy/intranet.php#tab-call-link';
	}
	else if (LANGUAGE_ID == 'ua')
	{
		//$arResult['LINK_TO_BUY'] = 'http://www.1c-bitrix.ua/buy/intranet.php#tab-call-link';
	}
	else if (LANGUAGE_ID == 'kz')
	{
	}
	else if (LANGUAGE_ID == 'de')
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.de/prices/calls.php';
	}
	else
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.com/prices/calls.php';
	}
	$arResult['LINK_TO_DOC'] = (in_array(LANGUAGE_ID, Array("ru", "kz", "ua", "by"))? 'https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=48&CHAPTER_ID=02699': 'https://www.bitrixsoft.com/support/training/course/index.php?COURSE_ID=26&LESSON_ID=6734');
}

$res = Bitrix\Voximplant\ConfigTable::getList(Array(
	'filter' => Array('=PORTAL_MODE' => CVoxImplantConfig::MODE_SIP)
));
while ($row = $res->fetch())
{
	if (strlen($row['PHONE_NAME']) <= 0)
	{
		$row['PHONE_NAME'] = substr($row['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_TITLE'): GetMessage('VI_CONFIG_SIP_OFFICE_TITLE');
		$row['PHONE_NAME'] = str_replace('#ID#', $row['ID'], $row['PHONE_NAME']);
	}
	$arResult['LIST_SIP_NUMBERS'][$row['ID']] = Array(
		'PHONE_NAME' => htmlspecialcharsbx($row['PHONE_NAME']),
	);
}

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>