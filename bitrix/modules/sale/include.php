<?
use Bitrix\Main\Loader;
define("SALE_DEBUG", false); // Debug

global $DBType;

IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_FIELD_TYPES"] = array(
	"TEXT" => GetMessage("SALE_TYPE_TEXT"),
	"CHECKBOX" => GetMessage("SALE_TYPE_CHECKBOX"),
	"SELECT" => GetMessage("SALE_TYPE_SELECT"),
	"MULTISELECT" => GetMessage("SALE_TYPE_MULTISELECT"),
	"TEXTAREA" => GetMessage("SALE_TYPE_TEXTAREA"),
	"LOCATION" => GetMessage("SALE_TYPE_LOCATION"),
	"RADIO" => GetMessage("SALE_TYPE_RADIO"),
	"FILE" => GetMessage("SALE_TYPE_FILE")
);

if (!Loader::includeModule('currency'))
	return false;

// Number of processed recurring records at one time
define("SALE_PROC_REC_NUM", 3);
// Number of recurring payment attempts
define("SALE_PROC_REC_ATTEMPTS", 3);
// Time between recurring payment attempts (in seconds)
define("SALE_PROC_REC_TIME", 43200);

define("SALE_PROC_REC_FREQUENCY", 7200);
// Owner ID base name used by CSale<etnity_name>ReportHelper clases for managing the reports.
define("SALE_REPORT_OWNER_ID", 'sale');
//cache orders flag for real-time exhange with 1C
define("CACHED_b_sale_order", 3600*24);

global $SALE_TIME_PERIOD_TYPES;
$SALE_TIME_PERIOD_TYPES = array(
	"H" => GetMessage("I_PERIOD_HOUR"),
	"D" => GetMessage("I_PERIOD_DAY"),
	"W" => GetMessage("I_PERIOD_WEEK"),
	"M" => GetMessage("I_PERIOD_MONTH"),
	"Q" => GetMessage("I_PERIOD_QUART"),
	"S" => GetMessage("I_PERIOD_SEMIYEAR"),
	"Y" => GetMessage("I_PERIOD_YEAR")
);

define("SALE_VALUE_PRECISION", 2);
define("SALE_WEIGHT_PRECISION", 3);

define('BX_SALE_MENU_CATALOG_CLEAR', 'Y');

$GLOBALS["AVAILABLE_ORDER_FIELDS"] = array(
	"ID" => array("COLUMN_NAME" => "ID", "NAME" => GetMessage("SI_ORDER_ID"), "SELECT" => "ID,DATE_INSERT", "CUSTOM" => "Y", "SORT" => "ID"),
	"LID" => array("COLUMN_NAME" => GetMessage("SI_SITE"), "NAME" => GetMessage("SI_SITE"), "SELECT" => "LID", "CUSTOM" => "N", "SORT" => "LID"),
	"PERSON_TYPE" => array("COLUMN_NAME" => GetMessage("SI_PAYER_TYPE"), "NAME" => GetMessage("SI_PAYER_TYPE"), "SELECT" => "PERSON_TYPE_ID", "CUSTOM" => "Y", "SORT" => "PERSON_TYPE_ID"),
	"PAYED" => array("COLUMN_NAME" => GetMessage("SI_PAID"), "NAME" => GetMessage("SI_PAID_ORDER"), "SELECT" => "PAYED,DATE_PAYED,EMP_PAYED_ID", "CUSTOM" => "Y", "SORT" => "PAYED"),
	"PAY_VOUCHER_NUM" => array("COLUMN_NAME" => GetMessage("SI_NO_PP"), "NAME" => GetMessage("SI_NO_PP_DOC"), "SELECT" => "PAY_VOUCHER_NUM", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_NUM"),
	"PAY_VOUCHER_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP"), "NAME" => GetMessage("SI_DATE_PP_DOC"), "SELECT" => "PAY_VOUCHER_DATE", "CUSTOM" => "N", "SORT" => "PAY_VOUCHER_DATE"),
	"DELIVERY_DOC_NUM" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_NUM"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_NUM"), "SELECT" => "DELIVERY_DOC_NUM", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_NUM"),
	"DELIVERY_DOC_DATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_PP_DELIVERY_DOC_DATE"), "NAME" => GetMessage("SI_DATE_PP_DOC_DELIVERY_DOC_DATE"), "SELECT" => "DELIVERY_DOC_DATE", "CUSTOM" => "N", "SORT" => "DELIVERY_DOC_DATE"),
	"CANCELED" => array("COLUMN_NAME" => GetMessage("SI_CANCELED"), "NAME" => GetMessage("SI_CANCELED_ORD"), "SELECT" => "CANCELED,DATE_CANCELED,EMP_CANCELED_ID", "CUSTOM" => "Y", "SORT" => "CANCELED"),
	"STATUS" => array("COLUMN_NAME" => GetMessage("SI_STATUS"), "NAME" => GetMessage("SI_STATUS_ORD"), "SELECT" => "STATUS_ID,DATE_STATUS,EMP_STATUS_ID", "CUSTOM" => "Y", "SORT" => "STATUS_ID"),
	"PRICE_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY"), "NAME" => GetMessage("SI_DELIVERY"), "SELECT" => "PRICE_DELIVERY,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE_DELIVERY"),
	"ALLOW_DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_ALLOW_DELIVERY"), "NAME" => GetMessage("SI_ALLOW_DELIVERY1"), "SELECT" => "ALLOW_DELIVERY,DATE_ALLOW_DELIVERY,EMP_ALLOW_DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "ALLOW_DELIVERY"),
	"PRICE" => array("COLUMN_NAME" => GetMessage("SI_SUM"), "NAME" => GetMessage("SI_SUM_ORD"), "SELECT" => "PRICE,CURRENCY", "CUSTOM" => "Y", "SORT" => "PRICE"),
	"SUM_PAID" => array("COLUMN_NAME" => GetMessage("SI_SUM_PAID"), "NAME" => GetMessage("SI_SUM_PAID1"), "SELECT" => "SUM_PAID,CURRENCY", "CUSTOM" => "Y", "SORT" => "SUM_PAID"),
	"USER" => array("COLUMN_NAME" => GetMessage("SI_BUYER"), "NAME" => GetMessage("SI_BUYER"), "SELECT" => "USER_ID", "CUSTOM" => "Y", "SORT" => "USER_ID"),
	"PAY_SYSTEM" => array("COLUMN_NAME" => GetMessage("SI_PAY_SYS"), "NAME" => GetMessage("SI_PAY_SYS"), "SELECT" => "PAY_SYSTEM_ID", "CUSTOM" => "Y", "SORT" => "PAY_SYSTEM_ID"),
	"DELIVERY" => array("COLUMN_NAME" => GetMessage("SI_DELIVERY_SYS"), "NAME" => GetMessage("SI_DELIVERY_SYS"), "SELECT" => "DELIVERY_ID", "CUSTOM" => "Y", "SORT" => "DELIVERY_ID"),
	"DATE_UPDATE" => array("COLUMN_NAME" => GetMessage("SI_DATE_UPDATE"), "NAME" => GetMessage("SI_DATE_UPDATE"), "SELECT" => "DATE_UPDATE", "CUSTOM" => "N", "SORT" => "DATE_UPDATE"),
	"PS_STATUS" => array("COLUMN_NAME" => GetMessage("SI_PAYMENT_PS"), "NAME" => GetMessage("SI_PS_STATUS"), "SELECT" => "PS_STATUS,PS_RESPONSE_DATE", "CUSTOM" => "N", "SORT" => "PS_STATUS"),
	"PS_SUM" => array("COLUMN_NAME" => GetMessage("SI_PS_SUM"), "NAME" => GetMessage("SI_PS_SUM1"), "SELECT" => "PS_SUM,PS_CURRENCY", "CUSTOM" => "Y", "SORT" => "PS_SUM"),
	"TAX_VALUE" => array("COLUMN_NAME" => GetMessage("SI_TAX"), "NAME" => GetMessage("SI_TAX_SUM"), "SELECT" => "TAX_VALUE,CURRENCY", "CUSTOM" => "Y", "SORT" => "TAX_VALUE"),
	"BASKET" => array("COLUMN_NAME" => GetMessage("SI_ITEMS"), "NAME" => GetMessage("SI_ITEMS_ORD"), "SELECT" => "", "CUSTOM" => "Y", "SORT" => "")
);

CModule::AddAutoloadClasses(
	"sale",
	array(
		"CSaleDelivery" => $DBType."/delivery.php",
		"CSaleDeliveryHandler" => $DBType."/delivery_handler.php",
		"CSaleDeliveryHelper" => "general/delivery_helper.php",
		"CSaleDelivery2PaySystem" => "general/delivery_2_pay_system.php",
		"CSaleLocation" => $DBType."/location.php",
		"CSaleLocationGroup" => $DBType."/location_group.php",

		"CSaleBasket" => $DBType."/basket.php",
		"CSaleBasketHelper" => "general/basket_helper.php",
		"CSaleUser" => $DBType."/basket.php",

		"CSaleOrder" => $DBType."/order.php",
		"CSaleOrderProps" => $DBType."/order_props.php",
		"CSaleOrderPropsGroup" => $DBType."/order_props_group.php",
		"CSaleOrderPropsValue" => $DBType."/order_props_values.php",
		"CSaleOrderPropsVariant" => $DBType."/order_props_variant.php",
		"CSaleOrderUserProps" => $DBType."/order_user_props.php",
		"CSaleOrderUserPropsValue" => $DBType."/order_user_props_value.php",
		"CSaleOrderTax" => $DBType."/order_tax.php",
		"CSaleOrderHelper" => "general/order_helper.php",

		"CSalePaySystem" => $DBType."/pay_system.php",
		"CSalePaySystemAction" => $DBType."/pay_system_action.php",
		"CSalePaySystemsHelper" => "general/pay_system_helper.php",
		"CSalePaySystemTarif" => "general/pay_system_tarif.php",

		"CSaleTax" => $DBType."/tax.php",
		"CSaleTaxRate" => $DBType."/tax_rate.php",

		"CSalePersonType" => $DBType."/person_type.php",
		"CSaleDiscount" => $DBType."/discount.php",
		"CSaleUserAccount" => $DBType."/user.php",
		"CSaleUserTransact" => $DBType."/user_transact.php",
		"CSaleUserCards" => $DBType."/user_cards.php",
		"CSaleRecurring" => $DBType."/recurring.php",
		"CSaleStatus" => $DBType."/status.php",

		"CSaleLang" => $DBType."/settings.php",
		"CSaleGroupAccessToSite" => $DBType."/settings.php",
		"CSaleGroupAccessToFlag" => $DBType."/settings.php",

		"CSaleAuxiliary" => $DBType."/auxiliary.php",

		"CSaleAffiliate" => $DBType."/affiliate.php",
		"CSaleAffiliatePlan" => $DBType."/affiliate_plan.php",
		"CSaleAffiliatePlanSection" => $DBType."/affiliate_plan_section.php",
		"CSaleAffiliateTier" => $DBType."/affiliate_tier.php",
		"CSaleAffiliateTransact" => $DBType."/affiliate_transact.php",
		"CSaleExport" => $DBType."/export.php",
		"CSaleOrderLoader" => "general/order_loader.php",

		"CSaleMeasure" => "general/measurement.php",
		"CSaleProduct" => $DBType."/product.php",

		"CSaleViewedProduct" => $DBType."/product.php",

		"CSaleHelper" => "general/helper.php",
		"CSaleMobileOrderUtils" => "general/mobile_order.php",
		"CSaleMobileOrderPull" => "general/mobile_order.php",
		"CSaleMobileOrderPush" => "general/mobile_order.php",
		"CSaleMobileOrderFilter" => "general/mobile_order.php",

		"CBaseSaleReportHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleOrderHelper" => "general/sale_report_helper.php",
		"CSaleReportUserHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleFuserHelper" => "general/sale_report_helper.php",

		"IBXSaleProductProvider" => "general/product_provider.php",
		"CSaleStoreBarcode" => $DBType."/store_barcode.php",

		"CSaleOrderChange" => $DBType."/order_change.php",
		"CSaleOrderChangeFormat" => "general/order_change.php",

		"Bitrix\\Sale\\OrderTable" => "lib/order.php",
		"Bitrix\\Sale\\BasketTable" => "lib/basket.php",
		"Bitrix\\Sale\\FuserTable" => "lib/fuser.php",
		"Bitrix\\Sale\\StatusLangTable" => "lib/statuslang.php",
		"Bitrix\\Sale\\PaySystemTable" => "lib/paysystem.php",
		"Bitrix\\Sale\\DeliveryTable" => "lib/delivery.php",
		"Bitrix\\Sale\\DeliveryHandlerTable" => "lib/deliveryhandler.php",
		"Bitrix\\Sale\\PersonTypeTable" => "lib/persontype.php",
		"\\Bitrix\\Sale\\OrderTable" => "lib/order.php",
		"\\Bitrix\\Sale\\BasketTable" => "lib/basket.php",
		"\\Bitrix\\Sale\\FuserTable" => "lib/fuser.php",
		"\\Bitrix\\Sale\\StatusLangTable" => "lib/statuslang.php",
		"\\Bitrix\\Sale\\PaySystemTable" => "lib/paysystem.php",
		"\\Bitrix\\Sale\\DeliveryTable" => "lib/delivery.php",
		"\\Bitrix\\Sale\\DeliveryHandlerTable" => "lib/deliveryhandler.php",
		"\\Bitrix\\Sale\\PersonTypeTable" => "lib/persontype.php",
		"CSaleReportSaleGoodsHelper" => "general/sale_report_helper.php",
		"CSaleReportSaleProductHelper" => "general/sale_report_helper.php",
		"Bitrix\\Sale\\ProductTable" => "lib/product.php",
		"Bitrix\\Sale\\GoodsSectionTable" => "lib/goodssection.php",
		"Bitrix\\Sale\\SectionTable" => "lib/section.php",
		"Bitrix\\Sale\\StoreProductTable" => "lib/storeproduct.php",
		"\\Bitrix\\Sale\\ProductTable" => "lib/product.php",
		"\\Bitrix\\Sale\\GoodsSectionTable" => "lib/goodssection.php",
		"\\Bitrix\\Sale\\SectionTable" => "lib/section.php",
		"\\Bitrix\\Sale\\StoreProductTable" => "lib/storeproduct.php",
		"\\Bitrix\\Sale\\SalesZone" => "lib/saleszone.php",
		"Bitrix\\Sale\\Delivery\\OrderDeliveryTable" => "lib/delivery/orderdelivery.php",

		"Bitrix\\Sale\\SenderEventHandler" => "lib/senderconnector.php",
		"Bitrix\\Sale\\SenderConnectorBuyer" => "lib/senderconnector.php",

		"Bitrix\\Sale\\Product2ProductTable" => "lib/product2product.php",
		"Bitrix\\Sale\\OrderProcessingTable" => "lib/orderprocessing.php",

		"Bitrix\\Sale\\Tax\\RateTable" => "lib/tax/rate.php",

		////////////////////////////
		// new location 2.0
		////////////////////////////

		// data entities
		"Bitrix\\Sale\\Location\\LocationTable" => "lib/location/location.php",
		"Bitrix\\Sale\\Location\\Tree" => "lib/location/tree.php",
		"Bitrix\\Sale\\Location\\TypeTable" => "lib/location/type.php",
		"Bitrix\\Sale\\Location\\GroupTable" => "lib/location/group.php",
		"Bitrix\\Sale\\Location\\ExternalTable" => "lib/location/external.php",
		"Bitrix\\Sale\\Location\\ExternalServiceTable" => "lib/location/externalservice.php",

		// search
		"Bitrix\\Sale\\Location\\Search\\Finder" => "lib/location/search/finder.php",
		"Bitrix\\Sale\\Location\\Search\\WordTable" => "lib/location/search/word.php",
		"Bitrix\\Sale\\Location\\Search\\ChainTable" => "lib/location/search/chain.php",
		"Bitrix\\Sale\\Location\\Search\\SiteLinkTable" => "lib/location/search/sitelink.php",

		// lang entities
		"Bitrix\\Sale\\Location\\Name\\NameEntity" => "lib/location/name/nameentity.php",
		"Bitrix\\Sale\\Location\\Name\\LocationTable" => "lib/location/name/location.php",
		"Bitrix\\Sale\\Location\\Name\\TypeTable" => "lib/location/name/type.php",
		"Bitrix\\Sale\\Location\\Name\\GroupTable" => "lib/location/name/group.php",

		// connector from locations to other entities
		"Bitrix\\Sale\\Location\\Connector" => "lib/location/connector.php",

		// link entities
		"Bitrix\\Sale\\Location\\GroupLocationTable" => "lib/location/grouplocation.php",
		"Bitrix\\Sale\\Location\\SiteLocationTable" => "lib/location/sitelocation.php",
		"Bitrix\\Sale\\Location\\DefaultSiteTable" => "lib/location/defaultsite.php",

		// db util
		"Bitrix\\Sale\\Location\\DB\\CommonHelper" => "lib/location/db/commonhelper.php",
		"Bitrix\\Sale\\Location\\DB\\Helper" => "lib/location/db/".ToLower($DBType)."/helper.php",
		"Bitrix\\Sale\\Location\\DB\\BlockInserter" => "lib/location/db/blockinserter.php",

		// db util
		"Bitrix\\Sale\\Location\\DB\\CommonHelper" => "lib/location/db/commonhelper.php",
		"Bitrix\\Sale\\Location\\DB\\Helper" => "lib/location/db/".ToLower($DBType)."/helper.php",
		"Bitrix\\Sale\\Location\\DB\\BlockInserter" => "lib/location/db/blockinserter.php",

		// admin logic
		"Bitrix\\Sale\\Location\\Admin\\Helper" => "lib/location/admin/helper.php",
		"Bitrix\\Sale\\Location\\Admin\\NameHelper" => "lib/location/admin/namehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\LocationHelper" => "lib/location/admin/locationhelper.php",
		"Bitrix\\Sale\\Location\\Admin\\TypeHelper" => "lib/location/admin/typehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\GroupHelper" => "lib/location/admin/grouphelper.php",
		"Bitrix\\Sale\\Location\\Admin\\DefaultSiteHelper" => "lib/location/admin/defaultsitehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\SiteLocationHelper" => "lib/location/admin/sitelocationhelper.php",
		"Bitrix\\Sale\\Location\\Admin\\ExternalServiceHelper" => "lib/location/admin/externalservicehelper.php",
		"Bitrix\\Sale\\Location\\Admin\\SearchHelper" => "lib/location/admin/searchhelper.php",

		// util
		"Bitrix\\Sale\\Location\\Util\\Process" => "lib/location/util/process.php",
		"Bitrix\\Sale\\Location\\Util\\CSVReader" => "lib/location/util/csvreader.php",
		"Bitrix\\Sale\\Location\\Util\\Assert" => "lib/location/util/assert.php",

		// processes for step-by-step actions
		"Bitrix\\Sale\\Location\\Import\\ImportProcess" => "lib/location/import/importprocess.php",
		"Bitrix\\Sale\\Location\\Search\\ReindexProcess" => "lib/location/search/reindexprocess.php",

		// old
		"CSaleProxyAdminResult" => "general/proxyadminresult.php", // for admin
		"CSaleProxyResult" => "general/proxyresult.php", // for public

		// other
		"Bitrix\\Sale\\Location\\Migration\\CUpdaterLocationPro" => "lib/location/migration/migrate.php", // class of migrations

		////////////////////////////
		// linked entities
		////////////////////////////

		"Bitrix\\Sale\\Delivery\\DeliveryTable" => "lib/delivery/delivery.php", // attack of clones???
		"Bitrix\\Sale\\Delivery\\DeliveryLocationTable" => "lib/delivery/deliverylocation.php",
		"Bitrix\\Sale\\Tax\\RateLocationTable" => "lib/tax/ratelocation.php",

		////////////////////////////

		"CSaleBasketFilter" => "general/sale_cond.php",
		"CSaleCondCtrlGroup" => "general/sale_cond.php",
		"CSaleCondCtrlBasketGroup" => "general/sale_cond.php",
		"CSaleCondCtrlBasketFields" => "general/sale_cond.php",
		"CSaleCondCtrlBasketProps" => "general/sale_cond.php",
		"CSaleCondCtrlOrderFields" => "general/sale_cond.php",
		"CSaleCondCtrlCommon" => "general/sale_cond.php",
		"CSaleCondTree" => "general/sale_cond.php",
		"CSaleDiscountActionApply" => "general/sale_act.php",
		"CSaleActionCtrlGroup" => "general/sale_act.php",
		"CSaleActionCtrlDelivery" => "general/sale_act.php",
		"CSaleActionCtrlBasketGroup" => "general/sale_act.php",
		"CSaleActionCtrlSubGroup" => "general/sale_act.php",
		"CSaleActionCondCtrlBasketFields" => "general/sale_act.php",
		"CSaleActionTree" => "general/sale_act.php",
		"CSaleDiscountConvert" => "general/discount_convert.php",

		"CSalePdf" => "general/pdf.php",
		"CSaleYMHandler" => "general/ym_handler.php",
		"Bitrix\\Sale\\TradingPlatformTable" => "lib/tradingplatform.php",
		"CSaleYMLocation" => "general/ym_location.php",
		'\Bitrix\Sale\Internals\DiscountTable' => 'lib/internals/discount.php',
		'\Bitrix\Sale\Internals\DiscountCouponTable' => 'lib/internals/discountcoupon.php',
		'\Bitrix\Sale\Internals\DiscountEntitiesTable' => 'lib/internals/discountentities.php',
		'\Bitrix\Sale\Internals\DiscountGroupTable' => 'lib/internals/discountgroup.php',
		'\Bitrix\Sale\Internals\DiscountModuleTable' => 'lib/internals/discountmodule.php',
		'\Bitrix\Sale\DiscountCouponsManager' => 'lib/discountcoupon.php'
	)
);

function GetBasketListSimple($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	if ($fUserID > 0)
		return CSaleBasket::GetList(
			array("NAME" => "ASC"),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => "NULL")
		);
	else
		return False;
}

function GetBasketList($bSkipFUserInit = true)
{
	$fUserID = (int)CSaleBasket::GetBasketUserID($bSkipFUserInit);
	$arRes = array();
	if ($fUserID > 0)
	{
		$basketID = array();
		$db_res = CSaleBasket::GetList(
			array(),
			array("FUSER_ID" => $fUserID, "LID" => SITE_ID, "ORDER_ID" => false),
			false,
			false,
			array('ID', 'CALLBACK_FUNC', 'PRODUCT_PROVIDER_CLASS', 'MODULE', 'PRODUCT_ID', 'QUANTITY', 'NOTES')
		);
		while ($res = $db_res->Fetch())
		{
			$res['CALLBACK_FUNC'] = (string)$res['CALLBACK_FUNC'];
			$res['PRODUCT_PROVIDER_CLASS'] = (string)$res['PRODUCT_PROVIDER_CLASS'];
			if ($res['CALLBACK_FUNC'] != '' || $res['PRODUCT_PROVIDER_CLASS'] != '')
				CSaleBasket::UpdatePrice($res["ID"], $res["CALLBACK_FUNC"], $res["MODULE"], $res["PRODUCT_ID"], $res["QUANTITY"], 'N', $res["PRODUCT_PROVIDER_CLASS"], $res['NOTES']);
			$basketID[] = $res['ID'];
		}
		unset($res, $db_res);
		if (!empty($basketID))
		{
			$basketIterator = CSaleBasket::GetList(
				array('NAME' => 'ASC'),
				array('ID' => $basketID)
			);
			while ($basket = $basketIterator->GetNext())
				$arRes[] = $basket;
			unset($basket, $basketIterator);
		}
		unset($basketID);
	}
	return $arRes;
}

function SaleFormatCurrency($fSum, $strCurrency, $OnlyValue = false)
{
	return CCurrencyLang::CurrencyFormat($fSum, $strCurrency, !($OnlyValue === true));
}

function AutoPayOrder($ORDER_ID)
{
	$ORDER_ID = (int)$ORDER_ID;
	if ($ORDER_ID <= 0)
		return false;

	$arOrder = CSaleOrder::GetByID($ORDER_ID);
	if (!$arOrder)
		return false;
	if ($arOrder["PS_STATUS"] != "Y")
		return false;
	if ($arOrder["PAYED"] != "N")
		return false;

	if ($arOrder["CURRENCY"] == $arOrder["PS_CURRENCY"]
		&& DoubleVal($arOrder["PRICE"]) == DoubleVal($arOrder["PS_SUM"]))
	{
		if (CSaleOrder::PayOrder($arOrder["ID"], "Y", true, false))
			return true;
	}

	return false;
}

function CurrencyModuleUnInstallSale()
{
	$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SALE_INCLUDE_CURRENCY"), "SALE_DEPENDES_CURRENCY");
	return false;
}

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php"))
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/ru/include.php");

function PayUserAccountDeliveryOrderCallback($productID, $userID, $bPaid, $orderID, $quantity = 1)
{
	global $DB;

	$productID = IntVal($productID);
	$userID = IntVal($userID);
	$bPaid = ($bPaid ? True : False);
	$orderID = IntVal($orderID);

	if ($userID <= 0)
		return False;

	if ($orderID <= 0)
		return False;

	if (!($arOrder = CSaleOrder::GetByID($orderID)))
		return False;

	$baseLangCurrency = CSaleLang::GetLangCurrency($arOrder["LID"]);
	$arAmount = unserialize(COption::GetOptionString("sale", "pay_amount", 'a:4:{i:1;a:2:{s:6:"AMOUNT";s:2:"10";s:8:"CURRENCY";s:3:"EUR";}i:2;a:2:{s:6:"AMOUNT";s:2:"20";s:8:"CURRENCY";s:3:"EUR";}i:3;a:2:{s:6:"AMOUNT";s:2:"30";s:8:"CURRENCY";s:3:"EUR";}i:4;a:2:{s:6:"AMOUNT";s:2:"40";s:8:"CURRENCY";s:3:"EUR";}}'));
	if (!array_key_exists($productID, $arAmount))
		return False;

	$currentPrice = $arAmount[$productID]["AMOUNT"] * $quantity;
	$currentCurrency = $arAmount[$productID]["CURRENCY"];
	if ($arAmount[$productID]["CURRENCY"] != $baseLangCurrency)
	{
		$currentPrice = CCurrencyRates::ConvertCurrency($arAmount[$productID]["AMOUNT"], $arAmount[$productID]["CURRENCY"], $baseLangCurrency) * $quantity;
		$currentCurrency = $baseLangCurrency;
	}

	if (!CSaleUserAccount::UpdateAccount($userID, ($bPaid ? $currentPrice : -$currentPrice), $currentCurrency, "MANUAL", $orderID, "Payment to user account"))
		return False;

	return True;
}

/*
* Formats user name. Used everywhere in 'sale' module
*
*/
function GetFormatedUserName($USER_ID, $bEnableId = true)
{
	$result = "";
	$USER_ID = IntVal($USER_ID);

	if($USER_ID > 0)
	{
		if (!isset($LOCAL_PAYED_USER_CACHE[$USER_ID]) || !is_array($LOCAL_PAYED_USER_CACHE[$USER_ID]))
		{
			$dbUser = CUser::GetByID($USER_ID);
			if ($arUser = $dbUser->Fetch())
			{
				$LOCAL_PAYED_USER_CACHE[$USER_ID] = CUser::FormatName(
						CSite::GetNameFormat(false),
						array(
							"NAME" => $arUser["NAME"],
							"LAST_NAME" => $arUser["LAST_NAME"],
							"SECOND_NAME" => $arUser["SECOND_NAME"],
							"LOGIN" => $arUser["LOGIN"]
						),
						true, true
					);
			}
		}

		if ($bEnableId)
			$result .= '[<a href="/bitrix/admin/user_edit.php?ID='.$USER_ID.'&lang='.LANGUAGE_ID.'">'.$USER_ID.'</a>] ';

		if (CBXFeatures::IsFeatureEnabled('SaleAccounts'))
			$result .= '<a href="/bitrix/admin/sale_buyers_profile.php?USER_ID='.$USER_ID.'&lang='.LANGUAGE_ID.'">';
		else
			$result .= '<a href="/bitrix/admin/user_edit.php?ID='.$USER_ID.'&lang='.LANGUAGE_ID.'">';

		$result .= $LOCAL_PAYED_USER_CACHE[$USER_ID];
		$result .= '</a>';
	}

	return $result;
}

/*
 * Updates basket item arrays with information about measures from catalog
 * Basically adds MEASURE_TEXT field with the measure name to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return array|bool
 */
function getMeasures($arBasketItems)
{
	if (Loader::includeModule('catalog'))
	{
		$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);
		$arElementId = array();
		$basketLinks = array();
		foreach ($arBasketItems as $keyBasket => $arItem)
		{
			$productID = (int)$arItem["PRODUCT_ID"];
			if (!isset($basketLinks[$productID]))
				$basketLinks[$productID] = array();
			$basketLinks[$productID][] = $keyBasket;
			$arElementId[] = $productID;
			$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $arDefaultMeasure['~SYMBOL_RUS'];
			$arBasketItems[$keyBasket]['MEASURE'] = 0;
		}
		unset($productID, $keyBasket, $arItem);

		if (!empty($arElementId))
		{
			$arBasket2Measure = array();
			$dbres = CCatalogProduct::GetList(
				array(),
				array("ID" => $arElementId),
				false,
				false,
				array("ID", "MEASURE")
			);
			while ($arRes = $dbres->Fetch())
			{
				$arRes['ID'] = (int)$arRes['ID'];
				$arRes['MEASURE'] = (int)$arRes['MEASURE'];
				if (!isset($arBasket2Measure[$arRes['MEASURE']]))
					$arBasket2Measure[$arRes['MEASURE']] = array();
				$arBasket2Measure[$arRes['MEASURE']][] = $arRes['ID'];
			}
			unset($arRes, $dbres);
			if (!empty($arBasket2Measure))
			{
				$dbMeasure = CCatalogMeasure::GetList(
					array(),
					array("ID" => array_keys($arBasket2Measure)),
					false,
					false,
					array('ID', 'SYMBOL_RUS')
				);
				while ($arMeasure = $dbMeasure->Fetch())
				{
					$arMeasure['ID'] = (int)$arMeasure['ID'];
					if (isset($arBasket2Measure[$arMeasure['ID']]) && !empty($arBasket2Measure[$arMeasure['ID']]))
					{
						foreach ($arBasket2Measure[$arMeasure['ID']] as &$productID)
						{
							if (isset($basketLinks[$productID]) && !empty($basketLinks[$productID]))
							{
								foreach ($basketLinks[$productID] as &$keyBasket)
								{
									$arBasketItems[$keyBasket]['MEASURE_TEXT'] = $arMeasure['SYMBOL_RUS'];
									$arBasketItems[$keyBasket]['MEASURE'] = $arMeasure['ID'];
								}
								unset($keyBasket);
							}
						}
						unset($productID);
					}
				}
			}
		}
	}
	return $arBasketItems;
}

/*
 * Updates basket items' arrays with information about ratio from catalog
 * Basically adds MEASURE_RATIO field with the ratio coefficient to each basket item array
 *
 * @param array $arBasketItems - array of basket items' arrays
 * @return mixed
 */
function getRatio($arBasketItems)
{
	if (Loader::includeModule('catalog'))
	{
		$map = array();
		$arElementId = array();
		foreach ($arBasketItems as $key => $arItem)
		{
			$arElementId[$arItem["PRODUCT_ID"]] = $arItem["PRODUCT_ID"];
			if (!isset($map[$arItem["PRODUCT_ID"]]))
				$map[$arItem["PRODUCT_ID"]] = array();
			$map[$arItem["PRODUCT_ID"]][] = $key;
		}

		if (!empty($arElementId))
		{
			$dbRatio = CCatalogMeasureRatio::getList(array(), array('PRODUCT_ID' => $arElementId), false, false, array('PRODUCT_ID', 'RATIO'));
			while ($arRatio = $dbRatio->Fetch())
			{
				if (empty($map[$arRatio["PRODUCT_ID"]]))
					continue;
				foreach ($map[$arRatio["PRODUCT_ID"]] as $key)
				{
					$arBasketItems[$key]["MEASURE_RATIO"] = $arRatio["RATIO"];
				}
			}
		}
		unset($arElementId, $map);
	}
	return $arBasketItems;
}

/*
 * Creates an array of iblock properties for the elements with certain IDs
 *
 * @param array $arElementId - array of element id
 * @param array $arSelect - properties to select
 * @return array - array of properties' values in the form of array("ELEMENT_ID" => array of props)
 */
function getProductProps($arElementId, $arSelect)
{
	if (!Loader::includeModule("iblock"))
		return array();

	if (empty($arElementId))
		return array();

	$arSelect = array_filter($arSelect, 'checkProductPropCode');

	$arProductData = array();
	$arElementData = array();
	$res = CIBlockElement::GetList(
		array(),
		array("=ID" => array_unique($arElementId)),
		false,
		false,
		array("ID", "IBLOCK_ID")
	);
	while ($arElement = $res->Fetch())
		$arElementData[$arElement["IBLOCK_ID"]][] = $arElement["ID"]; // two getlists are used to support 1 and 2 type of iblock properties

	foreach ($arElementData as $iblockId => $arElemId) // todo: possible performance bottleneck
	{
		$res = CIBlockElement::GetList(
			array(),
			array("IBLOCK_ID" => $iblockId, "=ID" => $arElemId),
			false,
			false,
			$arSelect
		);
		while ($arElement = $res->GetNext())
		{
			$id = $arElement["ID"];
			foreach ($arElement as $key => $value)
			{
				if (!isset($arProductData[$id]))
					$arProductData[$id] = array();

				if (isset($arProductData[$id][$key])
					&& !is_array($arProductData[$id][$key])
					&& !in_array($value, explode(", ", $arProductData[$id][$key]))
				) // if we have multiple property value
				{
					$arProductData[$id][$key] .= ", ".$value;
				}
				elseif (empty($arProductData[$id][$key]))
				{
					$arProductData[$id][$key] = $value;
				}
			}
		}
	}

	return $arProductData;
}

function checkProductPropCode($selectItem)
{
	return ($selectItem !== null && $selectItem !== '' && $selectItem !== 'PROPERTY_');
}

function updateBasketOffersProps($oldProps, $newProps)
{
	if (!is_array($oldProps) || !is_array($newProps))
		return false;

	$result = array();
	if (empty($newProps))
		return $oldProps;
	if (empty($oldProps))
		return $newProps;
	foreach ($oldProps as &$oldValue)
	{
		$found = false;
		$key = false;
		$propId = (isset($oldValue['CODE']) ? (string)$oldValue['CODE'] : '').':'.$oldValue['NAME'];
		foreach ($newProps as $newKey => $newValue)
		{
			$newId = (isset($newValue['CODE']) ? (string)$newValue['CODE'] : '').':'.$newValue['NAME'];
			if ($newId == $propId)
			{
				$key = $newKey;
				$found = true;
				break;
			}
		}
		if ($found)
		{
			$oldValue['VALUE'] = $newProps[$key]['VALUE'];
			unset($newProps[$key]);
		}
		$result[] = $oldValue;
	}
	unset($oldValue);
	if (!empty($newProps))
	{
		foreach ($newProps as &$newValue)
		{
			$result[] = $newValue;
		}
		unset($newValue);
	}
	return $result;
}