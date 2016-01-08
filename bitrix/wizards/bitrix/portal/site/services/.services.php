<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arServices = Array(

	"search" => Array(
		"MODULE_ID" => "search",
		"NAME" => GetMessage("SERVICE_SEARCH"),
	),

	"files" => Array(
		"MODULE_ID" => "main",
		"NAME" => GetMessage("SERVICE_FILES"),
		"STAGES" => Array(
			"files.php",
			"bitrix.php",
		),
	),

	"main" => Array(
		"NAME" => GetMessage("SERVICE_MAIN_SETTINGS"),
		"STAGES" => Array(
			"site.php",
			"template.php", //Install template
			"theme.php", //Install theme
			"groups.php", //Create user groups
			"property.php", //Create user fields
			"options.php", //Install module options
			"rating.php",
		),
	),

	"forum" => Array(
		"NAME" => GetMessage("SERVICE_FORUM"),
	),

	"support" => Array(
		"NAME" => GetMessage("SERVICE_SUPPORT"),
	),

	"iblock" => Array(
		"NAME" => GetMessage("SERVICE_COMPANY_STRUCTURE"),
		"STAGES" => Array(
			"types.php", //IBlock types

			"our_life.php",
			"official_news.php",

			"absence.php",
			"honour.php",
			"departments.php",
			"state_history.php",

			"vacancy.php",
			"board.php",
			"faq.php",
			"video.php",
			"links.php",
			"clients.php",
			"master.php",

			"res.php",

			/*"group_files.php",
			"user_files.php",
			"directors_files.php",
			"sales_files.php",
			"shared_files.php",*/

			"photo_company.php",
			"user_photogallery.php",
			"group_photogallery.php",
			"idea.php",
		),
	),

	"users" => Array(
		"MODULE_ID" => "main",
		"NAME" => GetMessage("SERVICE_USERS"),
		"STAGES" => Array(
			"import.php", //Start user import
			"steps/import_step_2.php",
			"steps/import_step_3.php",
			"steps/import_step_4.php",
			"steps/import_step_5.php",
			"steps/import_step_6.php",
			"steps/import_step_7.php",
			"steps/import_step_8.php",
			"steps/import_step_9.php",
			"steps/import_step_10.php",
			"steps/import_end.php", //End user import
			"rating.php",
		),
	),

	"iblock_demo_data" => Array(
		"MODULE_ID" => "iblock",
		"NAME" => GetMessage("SERVICE_IBLOCK_DEMO_DATA"),
		"STAGES" => Array(
			"absence.php",
			"state_history.php",
			"honour.php",
		),
	),

	"advertising" => Array(
		"NAME" => GetMessage("SERVICE_ADVERTISING"),
	),

	"vote" => Array(
		"NAME" => GetMessage("SERVICE_VOTE"),
	),

	"learning" => Array(
		"NAME" => GetMessage("SERVICE_LEARNING"),
	),

	"form" => Array(
		"NAME" => GetMessage("SERVICE_FORM"),
		"STAGES" => Array(
			"settings.php",
			"service_it.php",
			"service_adm.php",
			"service_visitor.php",
			"service_card.php",
			"service_supplies.php",
			"service_courier.php",
			"service_consumables.php",
			"service_site.php",
			"service_driver.php",
			"service_hr.php",
			"resume.php",
		),
	),

	"subscribe" => Array(
		"NAME" => GetMessage("SERVICE_SUBSCRIBE"),
	),

	"blog" => Array(
		"NAME" => GetMessage("SERVICE_BLOG"),
		"STAGES" => Array(
			"index.php",
			"idea_blog.php",
		),
	),

	"intranet" => Array(
		"NAME" => GetMessage("SERVICE_INTRANET"),
		"STAGES" => Array(
			"index.php",
			"rating.php",
		)
	),

	"socialnetwork" => Array(
		"NAME" => GetMessage("SERVICE_SOCIALNETWORK"),
	),

	"users_head" => Array(
		"MODULE_ID" => "main",
		"NAME" => GetMessage("SERVICE_DEPARTMENTS_HEAD"),
		"STAGES" => Array(
			"head.php",
		),
	),
	"tasks" => Array(
		"NAME" => GetMessage("SERVICE_TASKS"),
	),
	
	"workflow" => Array(
		"NAME" => GetMessage("SERVICE_WORKFLOW"),
	),

	"fileman" => Array(
		"NAME" => GetMessage("SERVICE_FILEMAN"),
	),

	"medialibrary" => Array(
		"NAME" => GetMessage("SERVICE_MEDIALIBRARY"),
		"MODULE_ID" => Array("fileman"),
		"STAGES" => Array("index.php"),
		"DESCRIPTION" => GetMessage("SERVICE_MEDIALIBRARY_DESC")
	),

	"statistic" => Array(
		"NAME" => GetMessage("SERVICE_STATISTIC"),
	),

	"lists" => Array(
		"NAME" => GetMessage("SERVICE_LISTS"),
	),

	"video" => Array(
		"NAME" => GetMessage("SERVICE_VIDEO"),
	),

	"bizproc" => Array(
		"NAME" => GetMessage("SERVICE_BPV"),
	),
	"wiki" => Array(
		"NAME" => GetMessage("SERVICE_WIKI"),
	),	
	"crm" => Array(
		"NAME" => GetMessage("SERVICE_CRM"),
	),
 	"meeting" => Array(
		"NAME" => GetMessage("SERVICE_MEETING"),
	),
  	"timeman" => Array(
		"NAME" => GetMessage("SERVICE_TIMEMAN"),
	), 
	"xdimport" => Array(
		"NAME" => GetMessage("SERVICE_XDIMPORT"),
		"MODULE_ID" => Array("xdimport"),
		"STAGES" => Array("index.php"),
	),
	"calendar" => Array(
		"NAME" => GetMessage("SERVICE_CALENDAR"),
		"MODULE_ID" => Array("calendar")
	),
	"disk" => array(
		"NAME" => GetMessage("SERVICE_DISK"),
		"MODULE_ID" => Array("disk"),
		"STAGES" => Array("index.php"),
	),
);
?>