<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$CCrmInvoice = new CCrmInvoice();
if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}


global $APPLICATION;

$APPLICATION->RestartBuffer();

$ORDER_ID = intval($arParams["ORDER_ID"]);

$dbOrder = CSaleOrder::GetList(
	array("DATE_UPDATE" => "DESC"),
	array(
		"LID" => SITE_ID,
		"ID" => $ORDER_ID
	)
);

$arOrder = $dbOrder->GetNext();

if ($arOrder)
{
	if (strlen($arOrder["SUM_PAID"]) > 0)
		$arOrder["PRICE"] -= $arOrder["SUM_PAID"];

	$dbPaySysAction = CSalePaySystemAction::GetList(
		array(),
		array(
			"PAY_SYSTEM_ID" => $arOrder["PAY_SYSTEM_ID"],
			"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"]
		),
		false,
		false,
		array("ACTION_FILE", "PARAMS", "ENCODING")
	);

	if ($arPaySysAction = $dbPaySysAction->Fetch())
	{
		if (strlen($arPaySysAction["ACTION_FILE"]) > 0)
		{
			CSalePaySystemAction::InitParamArrays($arOrder, $ID, $arPaySysAction["PARAMS"]);

			// USER_ID hack (0050242)
			$arInvoice = array();
			$dbInvoice = CCrmInvoice::GetList(
				array('ID' => 'DESC'),
				array('ID' => $ORDER_ID, 'PERMISSION' => 'READ'),
				false,
				false,
				array('ID', 'UF_CONTACT_ID', 'UF_COMPANY_ID')
			);
			if (is_object($dbInvoice))
				$arInvoice = $dbInvoice->Fetch();
			unset($dbInvoice);
			if (is_array($arInvoice) && isset($arInvoice['UF_CONTACT_ID']) && isset($arInvoice['UF_COMPANY_ID']))
			{
				$companyId = intval($arInvoice['UF_COMPANY_ID']);
				$contactId = intval($arInvoice['UF_CONTACT_ID']);
				$clientId = '';
				if ($companyId > 0)
					$clientId = 'C'.$companyId;
				else
					$clientId = 'P'.$contactId;
				$GLOBALS['SALE_INPUT_PARAMS']['ORDER']['USER_ID'] = $clientId;
				unset($companyId, $contactId, $clientId);
			}
			unset($arInvoice);

			$pathToAction = $_SERVER["DOCUMENT_ROOT"].$arPaySysAction["ACTION_FILE"];

			$pathToAction = str_replace("\\", "/", $pathToAction);
			while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/")
				$pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);

			if (file_exists($pathToAction))
			{
				if (is_dir($pathToAction))
				{
					if (file_exists($pathToAction."/payment.php"))
						include($pathToAction."/payment.php");
				}
				else
				{
					include($pathToAction);
				}
			}

			if (strlen($arPaySysAction["ENCODING"]) > 0)
			{
				define("BX_SALE_ENCODING", $arPaySysAction["ENCODING"]);
				AddEventHandler("main", "OnEndBufferContent", "ChangeEncoding");
				function ChangeEncoding($content)
				{
					global $APPLICATION;
					header("Content-Type: text/html; charset=".BX_SALE_ENCODING);
					$content = $APPLICATION->ConvertCharset($content, SITE_CHARSET, BX_SALE_ENCODING);
					$content = str_replace("charset=".SITE_CHARSET, "charset=".BX_SALE_ENCODING, $content);
				}
			}
		}
	}
}

$r = $APPLICATION->EndBufferContentMan();
echo $r;
if (defined("HTML_PAGES_FILE") && !defined("ERROR_404"))
	CHTMLPagesCache::writeFile(HTML_PAGES_FILE, $r);
die();
