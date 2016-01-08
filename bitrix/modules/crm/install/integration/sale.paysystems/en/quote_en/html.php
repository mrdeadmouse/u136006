<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?><?
$ORDER_ID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]);
if (!is_array($arOrder))
	$arOrder = CSaleOrder::GetByID($ORDER_ID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Quote</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
<style>
	table { border-collapse: collapse; }
	table.acc td { border: 1pt solid #000000; padding: 0pt 3pt; line-height: 21pt; }
	table.it td { border: 1pt solid #000000; padding: 0pt 3pt; }
	table.sign td { font-weight: bold; vertical-align: bottom; }
	table.header td { padding: 0pt; vertical-align: top; }
</style>
</head>

<?

if ($_REQUEST['BLANK'] == 'Y')
	$blank = true;

$pageWidth  = 595.28;
$pageHeight = 841.89;

$background = '#ffffff';
if (CSalePaySystemAction::GetParamValue('BACKGROUND', false))
{
	$path = CSalePaySystemAction::GetParamValue('BACKGROUND', false);
	if (intval($path) > 0)
	{
		if ($arFile = CFile::GetFileArray($path))
			$path = $arFile['SRC'];
	}

	$backgroundStyle = CSalePaySystemAction::GetParamValue('BACKGROUND_STYLE', false);
	if (!in_array($backgroundStyle, array('none', 'tile', 'stretch')))
		$backgroundStyle = 'none';

	if ($path)
	{
		switch ($backgroundStyle)
		{
			case 'none':
				$background = "url('" . $path . "') 0 0 no-repeat";
				break;
			case 'tile':
				$background = "url('" . $path . "') 0 0 repeat";
				break;
			case 'stretch':
				$background = sprintf(
					"url('%s') 0 0 repeat-y; background-size: %.02fpt %.02fpt",
					$path, $pageWidth, $pageHeight
				);
				break;
		}
	}
}

$margin = array(
	'top' => intval(CSalePaySystemAction::GetParamValue('MARGIN_TOP', false) ?: 15) * 72/25.4,
	'right' => intval(CSalePaySystemAction::GetParamValue('MARGIN_RIGHT', false) ?: 15) * 72/25.4,
	'bottom' => intval(CSalePaySystemAction::GetParamValue('MARGIN_BOTTOM', false) ?: 15) * 72/25.4,
	'left' => intval(CSalePaySystemAction::GetParamValue('MARGIN_LEFT', false) ?: 20) * 72/25.4
);
$width = $pageWidth - $margin['left'] - $margin['right'];
?>
<body style="margin: 0pt; padding: 0pt;"<? if ($_REQUEST['PRINT'] == 'Y') { ?> onload="setTimeout(window.print, 0);"<? } ?>>
<div style="margin: 0pt; padding: <?=join('pt ', $margin); ?>pt; width: <?=$width; ?>pt; background: <?=$background; ?>">
<div style="margin: 0pt; padding: 0pt;">
	<?
	$pathToLogo = CSalePaySystemAction::GetParamValue("PATH_TO_LOGO", false);
	if ($pathToLogo) {
		$imgParams = CFile::_GetImgParams($pathToLogo);
		$imgWidth = $imgParams['WIDTH'] * 96 / (intval(CSalePaySystemAction::GetParamValue('LOGO_DPI', false)) ?: 96);
		?><img src="<?=$imgParams['SRC']; ?>" width="<?=$imgWidth; ?>" /><?
	}
	unset($pathToLogo);
	?>
</div>
<br>
<div style="margin: 0pt; padding: 0pt;">
	<? if (CSalePaySystemAction::GetParamValue("SELLER_NAME", false)) { ?>
	<b><?=CSalePaySystemAction::GetParamValue("SELLER_NAME", false); ?></b><br>
	<?}?>
	<? if (CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false)) { ?>
	<?=CSalePaySystemAction::GetParamValue("SELLER_ADDRESS", false); ?><br>
	<? } ?>
	<? if (CSalePaySystemAction::GetParamValue("SELLER_PHONE", false)) { ?>
	<?=sprintf("Phone: %s", CSalePaySystemAction::GetParamValue("SELLER_PHONE", false)); ?><br>
	<? } ?>
	<? if (CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false)) { ?>
	<?=sprintf("E-mail: %s", CSalePaySystemAction::GetParamValue("SELLER_EMAIL", false)); ?>
	<? } ?>
</div>
<br>
<br>
<div style="margin: 0pt; padding: 0pt;">
	<? if (CSalePaySystemAction::GetParamValue("BUYER_NAME", false)) { ?>
		<b><?=CSalePaySystemAction::GetParamValue("BUYER_NAME", false); ?></b>
		<br>
		<? if (CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false)) { ?>
		<b><?=CSalePaySystemAction::GetParamValue("BUYER_ADDRESS", false); ?></b>
		<? } ?>
	<? } ?>
</div>
<br>
<br>
<table width="100%" style="font-weight: bold">
	<tr>
		<td>
			<span style="font-size: 1.5em; font-weight: bold; text-align: center;">
				<?=sprintf('Quote # %s',
					htmlspecialcharsbx($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ACCOUNT_NUMBER"])); ?>
			</span>
		</td>
		<td align="right">
			<?=sprintf('Issue Date: %s',
					CSalePaySystemAction::GetParamValue("DATE_INSERT", false)); ?>
			</td>
	</tr>
</table>
<br>
<br>

<?if (CSalePaySystemAction::GetParamValue("COMMENT1", false)
	|| CSalePaySystemAction::GetParamValue("COMMENT2", false)) { ?>
	<? if (CSalePaySystemAction::GetParamValue("COMMENT1", false)) { ?>
	<?=nl2br(HTMLToTxt(preg_replace(
		array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
		htmlspecialcharsback(CSalePaySystemAction::GetParamValue("COMMENT1", false))
	), '', array(), 0)); ?>
	<br>
	<br>
	<? } ?>
	<? if (CSalePaySystemAction::GetParamValue("COMMENT2", false)) { ?>
	<?=nl2br(HTMLToTxt(preg_replace(
		array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
		htmlspecialcharsback(CSalePaySystemAction::GetParamValue("COMMENT2", false))
	), '', array(), 0)); ?>
	<br>
	<br>
	<? } ?>
<? } ?>

<br>
<br>

<?
$vat = 0;
$bShowDiscount = false;
$arBasketItems = CSalePaySystemAction::GetParamValue("BASKET_ITEMS", false);
if(!is_array($arBasketItems))
	$arBasketItems = array();

if (!empty($arBasketItems))
{
	$arBasketItems = getMeasures($arBasketItems);

	$arCells = array();
	$arProps = array();

	$n = 0;
	$sum = 0.00;
	$vats = array();

	if(is_array($arBasketItems))
	{
		foreach($arBasketItems as &$arBasket)
		{
			// @TODO: replace with real vatless price
			$arBasket["VATLESS_PRICE"] = roundEx($arBasket["PRICE"] / (1 + $arBasket["VAT_RATE"]), SALE_VALUE_PRECISION);

			$productName = $arBasket["NAME"];
			if ($productName == "OrderDelivery")
				$productName = "Shipping";
			else if ($productName == "OrderDiscount")
				$productName = "Discount";

			// discount
			$discountValue = '0%';
			$discountSum = 0.0;
			$discountIsSet = false;
			if (is_array($arBasket['CRM_PR_FIELDS']))
			{
				if (isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'])
					&& isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_RATE'])
					&& isset($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']))
				{
					if ($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
					{
						$discountValue = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_RATE']), 2).'%';
						$discountSum = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']), 2);
						$discountIsSet = true;
					}
					else if ($arBasket['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
					{
						$discountSum = round(doubleval($arBasket['CRM_PR_FIELDS']['DISCOUNT_SUM']), 2);
						$discountValue = SaleFormatCurrency($discountSum, $arBasket["CURRENCY"], false);
						$discountIsSet = true;
					}
				}
			}
			if ($discountIsSet && $discountSum > 0)
				$bShowDiscount = true;
			unset($discountIsSet);

			if ($bShowDiscount
				&& isset($arBasket['CRM_PR_FIELDS']['TAX_INCLUDED'])
				&& isset($arBasket['CRM_PR_FIELDS']['PRICE_NETTO'])
				&& isset($arBasket['CRM_PR_FIELDS']['PRICE_BRUTTO']))
			{
				if ($arBasket['CRM_PR_FIELDS']['TAX_INCLUDED'] === 'Y')
					$unitPrice = $arBasket['CRM_PR_FIELDS']["PRICE_BRUTTO"];
				else
					$unitPrice = $arBasket['CRM_PR_FIELDS']["PRICE_NETTO"];
			}
			else
			{
				$unitPrice = $arBasket["VATLESS_PRICE"];
			}
			$arCells[++$n] = array(
				1 => $n,
				htmlspecialcharsbx($productName),
				roundEx($arBasket["QUANTITY"], SALE_VALUE_PRECISION),
				$arBasket["MEASURE_NAME"] ? htmlspecialcharsbx($arBasket["MEASURE_NAME"]) : 'pcs',
				SaleFormatCurrency($unitPrice, $arBasket["CURRENCY"], false),
				$discountValue,
				roundEx($arBasket["VAT_RATE"]*100, SALE_VALUE_PRECISION) . "%",
				SaleFormatCurrency(
					$arBasket["VATLESS_PRICE"] * $arBasket["QUANTITY"],
					$arBasket["CURRENCY"],
					false
				)
			);

			if(isset($arBasket["PROPS"]) && is_array($arBasket["PROPS"]))
			{
				$arProps[$n] = array();
				foreach ($arBasket["PROPS"] as $vv)
					$arProps[$n][] = htmlspecialcharsbx(sprintf("%s: %s", $vv["NAME"], $vv["VALUE"]));
			}

			$sum += doubleval($arBasket["VATLESS_PRICE"] * $arBasket["QUANTITY"]);
			$vat = max($vat, $arBasket["VAT_RATE"]);
			$vatKey = strval($arBasket["VAT_RATE"]);
			if ($arBasket["VAT_RATE"] > 0)
			{
				if (!isset($vats[$vatKey]))
					$vats[$vatKey] = 0;
				$vats[$vatKey] += ($arBasket["PRICE"] - $arBasket["VATLESS_PRICE"]) * $arBasket["QUANTITY"];
			}
		}
		unset($arBasket);
	}

	if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"]) > 0)
	{
		$arDelivery_tmp = CSaleDelivery::GetByID($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DELIVERY_ID"]);

		$sDeliveryItem = "Shipping";
		if (strlen($arDelivery_tmp["NAME"]) > 0)
			$sDeliveryItem .= sprintf(" (%s)", $arDelivery_tmp["NAME"]);
		$arCells[++$n] = array(
			1 => $n,
			htmlspecialcharsbx($sDeliveryItem),
			1,
			'',
			SaleFormatCurrency(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"] / (1 + $vat),
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
				false
			),
			'',
			roundEx($vat*100, SALE_VALUE_PRECISION) . "%",
			SaleFormatCurrency(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"] / (1 + $vat),
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
				false
			)
		);

		$sum += roundEx(
			doubleval($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"] / (1 + $vat)),
			SALE_VALUE_PRECISION
		);

		if ($vat > 0)
			$vats[$vat] += roundEx(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE_DELIVERY"] * $vat / (1 + $vat),
				SALE_VALUE_PRECISION
			);
	}

	$items = $n;

	if ($sum < $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"])
	{
		$arCells[++$n] = array(
			1 => null,
			null,
			null,
			null,
			null,
			null,
			"Subtotal:",
			SaleFormatCurrency($sum, $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"], false)
		);
	}

	if (!empty($vats))
	{
		// @TODO: remove on real vatless price implemented
		$delta = intval(roundEx(
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] - $sum - array_sum($vats),
			SALE_VALUE_PRECISION
		) * pow(10, SALE_VALUE_PRECISION));
		if ($delta)
		{
			$vatRates = array_keys($vats);
			rsort($vatRates);

			while (abs($delta) > 0)
			{
				foreach ($vatRates as $vatRate)
				{
					$vats[$vatRate] += abs($delta)/$delta / pow(10, SALE_VALUE_PRECISION);
					$delta -= abs($delta)/$delta;

					if ($delta == 0)
						break 2;
				}
			}
		}

		foreach ($vats as $vatRate => $vatSum)
		{
			$arCells[++$n] = array(
				1 => null,
				null,
				null,
				null,
				null,
				null,
				sprintf(
					"Tax (%s%%):",
					roundEx($vatRate * 100, SALE_VALUE_PRECISION)
				),
				SaleFormatCurrency(
					$vatSum,
					$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
					false
				)
			);
		}
	}
	else
	{
		$arTaxList = CSalePaySystemAction::GetParamValue("TAX_LIST", false);
		if(!is_array($arTaxList))
		{
			$dbTaxList = CSaleOrderTax::GetList(
				array("APPLY_ORDER" => "ASC"),
				array("ORDER_ID" => $ORDER_ID)
			);

			$arTaxList = array();
			while ($arTaxInfo = $dbTaxList->Fetch())
			{
				$arTaxList[] = $arTaxInfo;
			}
		}

		if(!empty($arTaxList))
		{
			foreach($arTaxList as &$arTaxInfo)
			{
				$arCells[++$n] = array(
					1 => null,
					null,
					null,
					null,
					null,
					null,
					htmlspecialcharsbx(sprintf(
						"%s%s%s:",
						($arTaxInfo["IS_IN_PRICE"] == "Y") ? "Included " : "",
						$arTaxInfo["TAX_NAME"],
						sprintf(' (%s%%)', roundEx($arTaxInfo["VALUE"],SALE_VALUE_PRECISION))
					)),
					SaleFormatCurrency(
						$arTaxInfo["VALUE_MONEY"],
						$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
						false
					)
				);
			}
			unset($arTaxInfo);
		}
	}
	if (DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"]) > 0)
	{
		$arCells[++$n] = array(
			1 => null,
			null,
			null,
			null,
			null,
			null,
			"Discount:",
			SaleFormatCurrency(
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DISCOUNT_VALUE"],
				$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
				false
			)
		);
	}

	$arCells[++$n] = array(
		1 => null,
		null,
		null,
		null,
		null,
		null,
		"Total:",
		SaleFormatCurrency(
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"],
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"],
			false
		)
	);
}

?>
<table class="it" width="100%">
	<tr>
		<td><nobr>#</nobr></td>
		<td><nobr>Item / Description</nobr></td>
		<td><nobr>Qty</nobr></td>
		<td><nobr>Units</nobr></td>
		<td><nobr>Unit Price</nobr></td>
		<? if ($bShowDiscount) { ?>
			<td><nobr>Discount</nobr></td>
		<? } ?>
		<? if (isset($vat) && $vat > 0) { ?>
		<td><nobr>Tax Rate</nobr></td>
		<? } ?>
		<td><nobr>Total</nobr></td>
	</tr>
<?

$rowsCnt = count($arCells);
for ($n = 1; $n <= $rowsCnt; $n++)
{
	$accumulated = 0;

?>
	<tr valign="top">
		<? if (!is_null($arCells[$n][1])) { ?>
		<td align="center"><?=$arCells[$n][1]; ?></td>
		<? } else {
			$accumulated++;
		} ?>
		<? if (!is_null($arCells[$n][2])) { ?>
		<td align="left"
			style="word-break: break-all; <? if ($accumulated) {?>border-width: 0pt 1pt 0pt 0pt; <? } ?>"
			<? if ($accumulated) { ?>colspan="<?=($accumulated+1); ?>"<? $accumulated = 0; } ?>>
			<?=$arCells[$n][2]; ?>
			<? if (isset($arProps[$n]) && is_array($arProps[$n])) { ?>
			<? foreach ($arProps[$n] as $property) { ?>
			<br>
			<small><?=$property; ?></small>
			<? } ?>
			<? } ?>
		</td>
		<? } else {
			$accumulated++;
		} ?>
		<? for ($i = 3; $i <= 8; $i++) { ?>
			<? if (!is_null($arCells[$n][$i])) { ?>
				<? if (($i !== 6 || $bShowDiscount) && ($i != 7 || $vat > 0) || is_null($arCells[$n][2])) { ?>
				<td align="right"
					<? if ($accumulated) { ?>
					style="border-width: 0pt 1pt 0pt 0pt"
					colspan="<?= ($accumulated + ($vat > 0) - !$bShowDiscount) ?>"
					<? $accumulated = 0; } ?>>
					<nobr><?=$arCells[$n][$i]; ?></nobr>
				</td>
				<? }
			} else {
				$accumulated++;
			}
		} ?>
	</tr>
<?

}

?>
</table>
<br>
<? if (CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false)) { ?>
<div><?=sprintf('Due Date: %s', ConvertDateTime(CSalePaySystemAction::GetParamValue("DATE_PAY_BEFORE", false), FORMAT_DATE))?></div>
<br>
<? } ?>

<?$userFields = array();
for($i = 1; $i <= 5; $i++)
{
	$fildValue = CSalePaySystemAction::GetParamValue("USER_FIELD_{$i}", false);
	if($fildValue)
	{
		$userFields[] = $fildValue;
	}
}?>
<?if (!empty($userFields)) { ?>
	<?foreach($userFields as &$userField){?>
		<?=nl2br(HTMLToTxt(preg_replace(
				array('#</div>\s*<div[^>]*>#i', '#</?div>#i'), array('<br>', '<br>'),
				htmlspecialcharsback($userField)
			), '', array(), 0));?>
		<br>
		<br>
	<?}
	unset($userField);?>
<?}?>

<? if (!$blank) { ?>
<div style="position: relative; "><?=CFile::ShowImage(
	CSalePaySystemAction::GetParamValue("PATH_TO_STAMP", false),
	160, 160,
	'style="position: absolute; left: 40pt; "'
); ?></div>
<? } ?>

<div style="position: relative">
	<table class="sign">
		<? if (CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false)) { ?>
		<tr>
			<td style="width: 150pt; "><?=CSalePaySystemAction::GetParamValue("SELLER_DIR_POS", false); ?></td>
			<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
				<? if (!$blank) { ?>
				<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_DIR_SIGN", false), 200, 50); ?>
				<? } ?>
			</td>
			<td>
				<? if (CSalePaySystemAction::GetParamValue("SELLER_DIR", false)) { ?>
				(<?=CSalePaySystemAction::GetParamValue("SELLER_DIR", false); ?>)
				<? } ?>
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<? } ?>
		<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false)) { ?>
		<tr>
			<td style="width: 150pt; "><?=CSalePaySystemAction::GetParamValue("SELLER_ACC_POS", false); ?></td>
			<td style="width: 160pt; border: 1pt solid #000000; border-width: 0pt 0pt 1pt 0pt; text-align: center; ">
				<? if (!$blank) { ?>
				<?=CFile::ShowImage(CSalePaySystemAction::GetParamValue("SELLER_ACC_SIGN", false), 200, 50); ?>
				<? } ?>
			</td>
			<td>
				<? if (CSalePaySystemAction::GetParamValue("SELLER_ACC", false)) { ?>
				(<?=CSalePaySystemAction::GetParamValue("SELLER_ACC", false); ?>)
				<? } ?>
			</td>
		</tr>
		<? } ?>
	</table>
</div>

<br>
<br>
<br>

</div>

</body>
</html>