<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$arResult['NUMBERS'] = CVoxImplantPhone::GetRentNumbers();

$arResult['PAID_BEFORE'] = Array(
	'TS' => 0,
	'DATE' => '',
	'PRICE' => 0,
	'NOTICE' => false,
);

foreach ($arResult['NUMBERS'] as $value)
{
	if ($arResult['PAID_BEFORE']['TS'] > $value['PAID_BEFORE_TS'] || $arResult['PAID_BEFORE']['TS'] == 0)
	{
		$arResult['PAID_BEFORE']['TS'] = $value['PAID_BEFORE_TS'];
		$arResult['PAID_BEFORE']['DATE'] = $value['PAID_BEFORE'];
		$arResult['PAID_BEFORE']['PRICE'] = $value['PRICE'];
	}
	else if ($arResult['PAID_BEFORE']['TS'] == $value['PAID_BEFORE_TS'])
	{
		$arResult['PAID_BEFORE']['PRICE'] += $value['PRICE'];
	}
}

$ViAccount = new CVoxImplantAccount();
$arResult['BALANCE_CURRENCY'] = $ViAccount->GetAccountCurrency();

if ($arResult['PAID_BEFORE']['TS'] > 0)
{
	$data = new Bitrix\Main\Type\DateTime();
	if ($arResult['PAID_BEFORE']['TS'] <= $data->getTimestamp()+604800) // 1 week
	{
		$arResult['BALANCE'] = $ViAccount->GetAccountBalance(true);
		if ($arResult['BALANCE'] < $arResult['PAID_BEFORE']['PRICE'])
		{
			$arResult['PAID_BEFORE']['NOTICE'] = 'Y';
		}
	}
}

if (count($arResult['NUMBERS']) > 0 && !(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>