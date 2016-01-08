<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule('advertising'))
	return;

$dbResult = CAdvContract::GetByID(1);
if (!$dbResult->Fetch())
	return;

//Types
$arTypes = Array(
	Array(
		"SID" => "100x100_ONE",
		"ACTIVE" => "Y",
		"SORT" => 1,
		"NAME" => GetMessage("PORTAL_ADV_100_100_ONE"),
		"DESCRIPTION" => ""
	),
	Array(
		"SID" => "100x100_TWO",
		"ACTIVE" => "Y",
		"SORT" => 2,
		"NAME" => GetMessage("PORTAL_ADV_100_100_TWO"),
		"DESCRIPTION" => ""
	),

	Array(
		"SID" => "468x60_TOP",
		"ACTIVE" => "Y",
		"SORT" => 3,
		"NAME" => GetMessage("PORTAL_ADV_468_60_TOP"),
		"DESCRIPTION" => ""
	),
	Array(
		"SID" => "468x60_BOTTOM",
		"ACTIVE" => "Y",
		"SORT" => 4,
		"NAME" => GetMessage("PORTAL_ADV_468_60_BOTTOM"),
		"DESCRIPTION" => ""
	),
	Array(
		"SID"=>"INFO",
		"ACTIVE"=>"Y",
		"NAME"=>GetMessage("PORTAL_ADV_INFO"),
		"SORT"=>"5",
		"DESCRIPTION"=>""
	)
);

foreach ($arTypes as $arFields)
{
	$dbResult = CAdvType::GetByID($arTypes["SID"], $CHECK_RIGHTS="N");
	if ($dbResult && $dbResult->Fetch())
		continue;

	CAdvType::Set($arFields, "", $CHECK_RIGHTS="N");
}

//Matrix
$arWeekday = Array(
	"SUNDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"MONDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"TUESDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"WEDNESDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"THURSDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"FRIDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23),
	"SATURDAY" => Array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23)
);

$pathToBanner = WIZARD_SERVICE_ABSOLUTE_PATH."/banners/".LANGUAGE_ID;

$arBanners = Array(
	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "468x60_BOTTOM",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_468_60_BOTTOM_NAME"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "banner_468x60.gif",
			"type" => "image/gif",
			"tmp_name" => $pathToBanner."/banner_468x60.gif",
			"error" => "0",
			"size" => @filesize($pathToBanner."/banner_468x60.gif"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_468_60_BOTTOM_NAME"),
		"URL" => "/company/novice.php",
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "banner_468x60.gif",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "100x100_ONE",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_100_100_ONE_NAME"),
		"ACTIVE" => "Y",
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "banner_100x100.gif",
			"type" => "image/gif",
			"tmp_name" => $pathToBanner."/banner_100x100.gif",
			"error" => "0",
			"size" => @filesize($pathToBanner."/banner_100x100.gif"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_100_100_ONE_NAME"),
		"URL" => "/company/novice.php",
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "banner_100x100.gif",
	),


	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "INFO",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_100_100_ONE_NAME"),
		"ACTIVE" => "Y",
		"FIX_SHOW" => "Y",
		"FIX_CLICK" => "N",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "new.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $_SERVER["DOCUMENT_ROOT"]."/images/".LANGUAGE_ID."/company/about/new.jpg",
			"error" => "0",
			"size" => @filesize($_SERVER["DOCUMENT_ROOT"]."/images/".LANGUAGE_ID."/company/about/new.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_100_100_ONE_NAME"),
		"URL" => "/company/novice.php",
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "new.jpg",
		"SHOWS_FOR_VISITOR" => 10
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "INFO",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_dashboard"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "dashboard.gif",
			"type" => "image/gif",
			"tmp_name" => $pathToBanner."/dashboard.gif",
			"error" => "0",
			"size" => @filesize($pathToBanner."/dashboard.gif"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_dashboard"),
		"URL" => "/desktop.php",
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "dashboard.gif",
	),


	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "468x60_BOTTOM",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_01_absence"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "01_absence_02.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/01_absence_02.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/01_absence_02.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_01_absence"),
		"URL" => GetMessage("PORTAL_ADV_01_absence_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "01_absence_02.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "100x100_ONE",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_01_absence"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "01_absence_01.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/01_absence_01.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/01_absence_01.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_01_absence"),
		"URL" => GetMessage("PORTAL_ADV_01_absence_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "01_absence_01.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "468x60_BOTTOM",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_03_outlook"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "02_outlook_02.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/02_outlook_02.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/02_outlook_02.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_03_outlook"),
		"URL" => GetMessage("PORTAL_ADV_03_outlook_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "02_outlook_02.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "100x100_ONE",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_03_outlook"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "02_outlook_02.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/02_outlook_02.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/02_outlook_02.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_03_outlook"),
		"URL" => GetMessage("PORTAL_ADV_03_outlook_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "02_outlook_02.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "468x60_BOTTOM",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_05_xmpp"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "Y",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "03_xmpp_02.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/03_xmpp_02.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/03_xmpp_02.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_05_xmpp"),
		"URL" => GetMessage("PORTAL_ADV_05_xmpp_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "03_xmpp_02.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "100x100_ONE",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_05_xmpp"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "03_xmpp_01.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/03_xmpp_01.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/03_xmpp_01.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_05_xmpp"),
		"URL" => GetMessage("PORTAL_ADV_05_xmpp_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "03_xmpp_01.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "468x60_BOTTOM",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_05_xmpp"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "03_xmpp_02_01.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/03_xmpp_02_01.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/03_xmpp_02_01.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_05_xmpp"),
		"URL" => GetMessage("PORTAL_ADV_05_xmpp_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "03_xmpp_02_01.jpg",
	),

	Array(
		"CONTRACT_ID" => 1,
		"TYPE_SID" => "100x100_ONE",
		"STATUS_SID"		=> "PUBLISHED",
		"NAME" => GetMessage("PORTAL_ADV_05_xmpp"),
		"ACTIVE" => "Y",
		"arrSITE" => Array(WIZARD_SITE_ID),
		"WEIGHT"=> 100,
		"FIX_SHOW" => "N",
		"FIX_CLICK" => "N",
		"AD_TYPE" => "image",
		"arrIMAGE_ID" => Array(
			"name" => "03_xmpp_02_02.jpg",
			"type" => "image/jpeg",
			"tmp_name" => $pathToBanner."/03_xmpp_02_02.jpg",
			"error" => "0",
			"size" => @filesize($pathToBanner."/03_xmpp_02_02.jpg"),
			"MODULE_ID" => "advertising"
		),
		"IMAGE_ALT" => GetMessage("PORTAL_ADV_05_xmpp"),
		"URL" => GetMessage("PORTAL_ADV_05_xmpp_url"),
		"URL_TARGET" => "_blank",
		"STAT_EVENT_1" => "banner",
		"STAT_EVENT_2" => "click",
		"arrWEEKDAY" => $arWeekday,
		"COMMENTS" => "03_xmpp_02_02.jpg",
	),
);

foreach ($arBanners as $arFields)
{
	$dbResult = CAdvBanner::GetList($by, $order, Array("COMMENTS" => $arFields["COMMENTS"], "COMMENTS_EXACT_MATCH" => "Y"), $is_filtered, "N");
	if ($dbResult && $dbResult->Fetch())
		continue;

	CAdvBanner::Set($arFields, "", $CHECK_RIGHTS="N");
}

if (!WIZARD_IS_RERUN)
{
	$APPLICATION->SetGroupRight("advertising", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
}
?>
