<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/res_c.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?>
<p><?$APPLICATION->IncludeComponent(
	"bitrix:intranet.event_calendar",
	".default",
	Array(
		"IBLOCK_TYPE" => "events", 
		"IBLOCK_ID" => "#CALENDAR_RES_IBLOCK_ID#", 
		"INIT_DATE" => GetMessage("SERVICES_INIT_DATE"),
		"WEEK_HOLIDAYS" => array(0=>"5",1=>"6",), 
		"YEAR_HOLIDAYS" => (LANGUAGE_ID == "en") ? "1.01, 25.12" : ((LANGUAGE_ID == "de") ? "1.01, 25.12" : "1.01,7.01,23.02,8.03"),
		"LOAD_MODE" => "ajax", 
		"EVENT_LIST_MODE" => "N", 
		"USERS_IBLOCK_ID" => "#CALENDAR_USERS_IBLOCK_ID#", 
		"PATH_TO_USER" => "#SITE_DIR#company/personal/user/#user_id#/", 
		"PATH_TO_USER_CALENDAR" => "#SITE_DIR#company/personal/user/#user_id#/calendar/",
		"WORK_TIME_START" => "9", 
		"WORK_TIME_END" => "19", 
		"ALLOW_SUPERPOSE" => "N", 
		"RESERVE_MEETING_READONLY_MODE" => "Y",
		"REINVITE_PARAMS_LIST" => array(
			0 => "from",
			1 => "to",
			2 => "location",
		),
		"ALLOW_RES_MEETING" => "N",
		"ALLOW_VIDEO_MEETING" => "N",	
		"CACHE_TYPE" => "A", 
		"CACHE_TIME" => "3600" 
	)
);?></p>

<p><?=GetMessage("SERVICES_INFO")?></p>

<p><a href="#SITE_DIR#services/index.php"><?=GetMessage("SERVICES_LINK")?></a><br /></p>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>