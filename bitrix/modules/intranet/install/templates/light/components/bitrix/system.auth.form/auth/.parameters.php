<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

	$arTemplateParameters["PATH_TO_MYPORTAL"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_MYPORTAL"),
		"DEFAULT" => SITE_DIR."desktop.php",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

if(CModule::IncludeModule("socialnetwork")) 
{

	$arTemplateParameters["PATH_TO_SONET_PROFILE"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_PROFILE"),
		"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

	$arTemplateParameters["PATH_TO_BIZPROC"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_BIZPROC"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "",
		"COLS" => 25,
		"PARENT" => "URL_TEMPLATES",
	);

	$arTemplateParameters["PATH_TO_SONET_MESSAGES"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_MESSAGES"),
		"DEFAULT" => SITE_DIR."company/personal/messages/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);
	$arTemplateParameters["PATH_TO_SONET_MESSAGE_FORM"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_MESSAGE_FORM"),
		"DEFAULT" => SITE_DIR."company/personal/messages/form/#user_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);	
	$arTemplateParameters["PATH_TO_SONET_MESSAGE_FORM_MESS"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_MESSAGE_FORM_MESS"),
		"DEFAULT" => SITE_DIR."company/personal/messages/form/#user_id#/#message_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);
	$arTemplateParameters["PATH_TO_SONET_MESSAGES_CHAT"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_MESSAGES_CHAT"),
		"DEFAULT" => SITE_DIR."company/personal/messages/chat/#user_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);	
	$arTemplateParameters["PATH_TO_SONET_GROUPS"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_GROUPS"),
		"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/groups/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

		$arTemplateParameters["PATH_TO_SONET_LOG"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_LOG"),
		"DEFAULT" => SITE_DIR."company/personal/log/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

	$arTemplateParameters["PATH_TO_SONET_GROUP"] = array(
		"NAME" => GetMessage("SAF_TP_PATH_TO_SONET_GROUP"),
		"DEFAULT" => SITE_DIR."workgroups/group/#group_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

	if(CModule::IncludeModule("blog")) 
	{
		$arTemplateParameters["PATH_TO_BLOG"] = array(
			"NAME" => GetMessage("SAF_TP_PATH_TO_BLOG"),
			"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/blog/",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"COLS" => 25,
		);
	}
	
	if(CModule::IncludeModule("photogallery")) 
	{
		$arTemplateParameters["PATH_TO_PHOTO"] = array(
			"NAME" => GetMessage("SAF_TP_PATH_TO_PHOTO"),
			"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/photo/",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"COLS" => 25,
		);
	}	
	
	if(CModule::IncludeModule("intranet")) 
	{
		$arTemplateParameters["PATH_TO_CALENDAR"] = array(
			"NAME" => GetMessage("SAF_TP_PATH_TO_CALENDAR"),
			"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/calendar/",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"COLS" => 25,
		);
		$arTemplateParameters["PATH_TO_TASKS"] = array(
			"NAME" => GetMessage("SAF_TP_PATH_TO_TASKS"),
			"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/tasks/",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"COLS" => 25,
		);
	}
	if(CModule::IncludeModule("webdav")) 
	{
		$arTemplateParameters["PATH_TO_FILES"] = array(
			"NAME" => GetMessage("SAF_TP_PATH_TO_FILES"),
			"DEFAULT" => SITE_DIR."company/personal/user/#user_id#/files/lib/",
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"COLS" => 25,
		);
	}
	
}
?>