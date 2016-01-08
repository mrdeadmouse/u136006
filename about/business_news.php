<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/business_news.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?><?$APPLICATION->IncludeComponent("bitrix:desktop", ".default", array(
	"ID" => "business_news",
	"CAN_EDIT" => "Y",
	"COLUMNS" => "1",
	"COLUMN_WIDTH_0" => "100%",
	"GADGETS" => array(
		0 => "RSSREADER",
	),
	"G_RSSREADER_CACHE_TIME" => "3600",
	"G_RSSREADER_SHOW_URL" => "Y",
	"G_RSSREADER_PREDEFINED_RSS" => "",
	"GU_RSSREADER_CNT" => "25",
	"GU_RSSREADER_RSS_URL" => (LANGUAGE_ID == "en") ? "http://rss.cnn.com/rss/edition_business.rss"	: ((LANGUAGE_ID == "de") ? "http://www.tagesschau.de/xml/rss2/" : "http://news.yandex.ru/business.rss")
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>