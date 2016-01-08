<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("form"))
	return;

require_once("functions.php");

$formID = false;
$rsForms = CForm::GetList($by="s_id", $order="desc", Array("SID" => "HR_REQUEST_" . WIZARD_SITE_ID, "arSITE" => array(WIZARD_SITE_ID)), $is_filtered);
while ($arForm = $rsForms->Fetch())
{
	$formID = $arForm["ID"]; 
}
if($formID == false && WIZARD_SITE_ID == "s1"){
	$rsForms = CForm::GetList($by="s_id", $order="desc", Array("SID" => "HR_REQUEST", "arSITE" => array(WIZARD_SITE_ID)), $is_filtered);
	while ($arForm = $rsForms->Fetch())
	{
		$formID = $arForm["ID"]; 
	}
}
if($formID == false){
	$arForm = array(
		"NAME" => GetMessage("SERVICE_HR_FORM_NAME"),
		"SID" => "HR_REQUEST_" . WIZARD_SITE_ID,
		"C_SORT" => 200,
		"BUTTON" => GetMessage("SERVICE_HR_FORM_BUTTON"),
		"DESCRIPTION" => "",
		"DESCRIPTION_TYPE" => "text",
		
		"USE_CAPTCHA" => "N",
		"USE_RESTRICTIONS" => "N",
		
		"STAT_EVENT1" => "form",
		"STAT_EVENT2" => "hr_request",
		"STAT_EVENT4" => "",
		
		"arSITE" => array(WIZARD_SITE_ID),
		"arMENU" => array(),
		"arGROUP" => Array(WIZARD_EMPLOYEES_GROUP => "15", WIZARD_PERSONNEL_DEPARTMENT_GROUP => "30",WIZARD_PORTAL_ADMINISTRATION_GROUP => "30"),
	);
	
	$arFormFields = array(
	
		Array(
			"SID" => "DEPARTMENT",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
				),
			),
			"arFILTER_USER" => array("text"),
		),
	
		Array(
			"SID" => "POSITION",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
				),
			),
			"arFILTER_USER" => array("text"),
		),
		
		Array(
			"SID" => "DATE_ACTUAL",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "date",
				),
			),
			"arFILTER_USER" => array("date"),
		),
		Array(
			"SID" => "REQUIREMENTS",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "textarea",
					"FIELD_WIDTH" => 40,
					"FIELD_HEIGHT" => 4,
				),
			),
		),
		Array(
			"SID" => "FUNCTIONS",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "textarea",
					"FIELD_WIDTH" => 40,
					"FIELD_HEIGHT" => 4,
				),
			),
		),
		Array(
			"SID" => "SALARY",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
					"FIELD_WIDTH" => 3,
				),
			),
		),
		
		Array(
			"SID" => "COMMENT",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "textarea",
					"FIELD_WIDTH" => 40,
					"FIELD_HEIGHT" => 4,
				),
			),
		),
	
		Array(
			"SID" => "ADMIN_NOTE",
			"ADDITIONAL" => 'Y',
			"REQUIRED" => "N",
			"FIELD_TYPE" => "text", 
		),
	);
	
	$formID = CreateForm($arForm, $arFormFields, "service_hr.php");
}
?>