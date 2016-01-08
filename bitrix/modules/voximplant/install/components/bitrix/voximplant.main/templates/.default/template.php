<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

use Bitrix\Voximplant as VI;

$ViAccount = new CVoxImplantAccount();
$amount = $ViAccount->GetAccountBalance(true);
$currency = $ViAccount->GetAccountCurrency();
$errorMessage = '';

if ($ViAccount->GetError()->error)
{
	$amount = '';
	$currency = '';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$errorMessage = GetMessage('VI_ERROR_LICENSE');
	}
	else
	{
		$errorMessage = GetMessage('VI_ERROR');
	}
}

function getBalance($amount)
{
	$amount = round(floatval($amount), 2);
	$amount = $amount.'';
	$str = '';
	$amountCount = strlen($amount);
	for ($i = 0; $i < $amountCount; $i++)
	{
		if ($amount[$i] == '.')
			$str .= '<span class="tel-num tel-num-point">.</span>';
		else
			$str .= '<span class="tel-num tel-num-'.$amount[$i].'">'.$amount[$i].'</span>';
	}

	return $str;
}

function getCurrency($currency)
{
	$currency = strtoupper($currency);
	return '<span class="tel-balance-sum-currency sum-currency-'.$currency.'"></span>';
}

?>

<div class="tel-title"></div>
<div class="tel-inner">
	<div class="tel-inner-left">
		<div class="tel-balance">
			<table class="tel-balance-table">
				<tr>
					<td class="tel-balance-left">
						<div class="tel-balance-title"><?=GetMessage('TELEPHONY_BALANCE')?></div>
						<div class="tel-balance-sum-wrap">
							<span class="tel-balance-box">
								<span class="tel-balance-box-inner">
									<?=getBalance($amount);?>
								</span>
								<span class="tel-balance-box-line"></span>
							</span>
							<?=getCurrency($currency);?>
						</div>
					</td>
					<td class="tel-balance-right">
						<div class="tel-balance-btn-wrap">
							<a href="?REFRESH" class="tel-balance-update-btn">
								<img class="tel-balance-update-loader" src="/bitrix/images/1.gif"/>
								<span class="tel-balance-update-btn-icon"></span>
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_REFRESH')?></span>
							</a>
						</div>
						<div class="tel-balance-btn-wrap">
							<?if ($arResult['LINK_TO_BUY']):?>
							<a href="<?=GetMessage('TELEPHONY_TARIFFS_LINK')?>" target="_blank" class="tel-balance-update-btn tel-balance-update-btn2">
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_TARIFFS')?></span>
							</a>
							<?endif;?>
						</div>
						<div class="tel-balance-btn-wrap">
							<?if ($arResult['LINK_TO_BUY']):?>
								<a href="<?=$arResult['LINK_TO_BUY']?>" class="tel-balance-blue-btn"><?=GetMessage('TELEPHONY_PAY')?></a>
							<?else:?>
								<span onclick="alert('<?=CUtil::JSEscape(GetMessage('TELEPHONY_PAY_DISABLE'))?>')" class="tel-balance-update-btn tel-balance-update-btn2">
									<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_PAY')?></span>
								</span>
							<?endif;?>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?$APPLICATION->IncludeComponent("bitrix:voximplant.regular_payments", "", array());?>

		<?$APPLICATION->IncludeComponent("bitrix:voximplant.sip_payments", "", array());?>
	</div>

<!-- statistic-->
<?
if (CModule::IncludeModule("currency"))
{
	$curPortalCurrency = "";

	$lastDay = ConvertTimeStamp(mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	$firstDay = ConvertTimeStamp(MakeTimeStamp("01.".date("m").".".date("Y"), "DD.MM.YYYY"));

	$parameters = array(
		'order' => array('CALL_START_DATE'=>'DESC'),
		'filter' => array(array(
			'LOGIC' => 'AND',
			'>CALL_START_DATE' => $firstDay,
			'<CALL_START_DATE' => $lastDay
		)),
		'select' => array('COST', 'COST_CURRENCY', 'CALL_DURATION'),
	);

	$costLastMonth = 0;
	$durationLastMonth = 0;
	$data = VI\StatisticTable::getList($parameters);
	while($arData = $data->fetch())
	{
		$arData["COST_CURRENCY"] = ($arData["COST_CURRENCY"] == "RUR" ? "RUB" : $arData["COST_CURRENCY"]);

		if (!$curPortalCurrency)
			$curPortalCurrency = $arData["COST_CURRENCY"];

		$costLastMonth += $arData["COST"];
		$durationLastMonth += $arData["CALL_DURATION"];
	}
	if ($durationLastMonth > 60)
	{
		$formatTimeMin = floor($durationLastMonth/60);
		$formatTimeSec = $durationLastMonth - $formatTimeMin*60;
		$durationLastMonth = $formatTimeMin." ".GetMessage("TELEPHONY_MIN");
		if ($formatTimeSec > 0)
			$durationLastMonth = $durationLastMonth." ".$formatTimeSec." ".GetMessage("TELEPHONY_SEC");
	}
	else
	{
		$durationLastMonth = $durationLastMonth." ".GetMessage("TELEPHONY_SEC");
	}
	$costLastMonth = $costLastMonth ? CCurrencyLang::CurrencyFormat($costLastMonth, $curPortalCurrency, true) : GetMessage("TELEPHONY_EMPTY");

	$monthlyStat = CVoxImplantMain::GetTelephonyStatistic();

	$monthCount = 0;
	?>
	<div class="tel-inner-right">
		<div class="tel-history-block">
			<div class="tel-history-title"><?=GetMessage('TELEPHONY_HISTORY_2')?></div>
			<div class="tel-history-block-info tel-history-block-info-current ">
				<strong><?=FormatDate("f", time());?> <?=date("Y")?></strong> &mdash; <?=$durationLastMonth?> <span class="tel-history-text-right"><?=$costLastMonth?></span>
			</div>
			<?if ($monthlyStat):
				foreach($monthlyStat as $year => $arYear)
				{
					if ($monthCount > 2)
						break;

					foreach($arYear as $month => $arMonth)
					{
						if ($monthCount > 2)
							break;

						$arMonth["COST_CURRENCY"] = ($arMonth["COST_CURRENCY"] == "RUR" ? "RUB" : $arMonth["COST_CURRENCY"]);

						if (!$curPortalCurrency)
							$curPortalCurrency = $arMonth["COST_CURRENCY"];

						$formatPrice = CCurrencyLang::CurrencyFormat($arMonth["COST"], $curPortalCurrency, true);

						if ($arMonth["CALL_DURATION"] > 60)
						{
							$formatTimeMin = floor($arMonth["CALL_DURATION"]/60);
							$formatTimeSec = $arMonth["CALL_DURATION"] - $formatTimeMin*60;
							$arMonth["CALL_DURATION"] = $formatTimeMin." ".GetMessage("TELEPHONY_MIN");
							if ($formatTimeSec > 0)
								$arMonth["CALL_DURATION"] = $arMonth["CALL_DURATION"]." ".$formatTimeSec." ".GetMessage("TELEPHONY_SEC");
						}
						else
						{
							$arMonth["CALL_DURATION"] = $arMonth["CALL_DURATION"]." ".GetMessage("TELEPHONY_SEC");
						}
					?>
						<div class="tel-history-block-info">
							<strong><?=GetMessage('TELEPHONY_MONTH_'.$month)?> <?=$year?></strong> &mdash; <?=$arMonth["CALL_DURATION"]?> <span class="tel-history-text-right"><?=$formatPrice?></span>
						</div>
					<?
						$monthCount++;
					}
				}
				?>
			<?endif?>

			<div class="tel-history-more">
				<a href="<?=CVoxImplantMain::GetPublicFolder()?>detail.php" class="tel-history-more-link"><?=GetMessage('TELEPHONY_DETAIL')?></a>
			</div>
		</div>
	</div>
<?
}
?>
<?if (!empty($errorMessage)):?>
	<script type="text/javascript">alert('<?=$errorMessage;?>');</script>
<?endif?>
</div>