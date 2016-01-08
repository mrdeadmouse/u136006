<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("form"))
	return;

require_once("functions.php");


$formID = false;
$rsForms = CForm::GetList($by="s_id", $order="desc", Array("SID" => "RESUME_" . WIZARD_SITE_ID, "arSITE" => array(WIZARD_SITE_ID)), $is_filtered);
while ($arForm = $rsForms->Fetch())
{
	$formID = $arForm["ID"];
}
if($formID == false && WIZARD_SITE_ID == "s1"){
	$rsForms = CForm::GetList($by="s_id", $order="desc", Array("SID" => "RESUME", "arSITE" => array(WIZARD_SITE_ID)), $is_filtered);
	while ($arForm = $rsForms->Fetch())
	{
		$formID = $arForm["ID"];
	}
}
if($formID == false){
	$arForm = array(
		"NAME" => GetMessage("RESUME_FORM_NAME"),
		"SID" => "RESUME_" . WIZARD_SITE_ID,
		"C_SORT" => 400,
		"BUTTON" => GetMessage("RESUME_ORDER_FORM_BUTTON"),
		"DESCRIPTION" => "",
		"DESCRIPTION_TYPE" => "text",

		"USE_CAPTCHA" => "N",
		"USE_RESTRICTIONS" => "N",

		"STAT_EVENT1" => "form",
		"STAT_EVENT2" => "resume_" . WIZARD_SITE_ID,
		"STAT_EVENT4" => "",

		"arSITE" => array(WIZARD_SITE_ID),
		"arMENU" => array(),
		"arGROUP" => Array(WIZARD_PERSONNEL_DEPARTMENT_GROUP => "30",WIZARD_PORTAL_ADMINISTRATION_GROUP => "30"),
	);

	$arFormFields = array(

		Array(
			"SID" => "FIO",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
					"FIELD_WIDTH" => 45,
				),
			),
			"arFILTER_USER" => array("text"),
		),

		Array(
			"SID" => "VACANCY",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
					"FIELD_WIDTH" => 45,
				),
			),
			"arFILTER_USER" => array("text"),
		),

		Array(
			"SID" => "PHONE",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
					"FIELD_WIDTH" => 30,
				),
			),
			"arFILTER_USER" => array("text"),
		),

		Array(
			"SID" => "EMAIL",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "text",
					"FIELD_WIDTH" => 30,
				),
			),
			"arFILTER_USER" => array("text"),
		),

		Array(
			"SID" => "EXPERIENCE",
			"REQUIRED" => "Y",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "textarea",
					"FIELD_WIDTH" => 40,
					"FIELD_HEIGHT" => 10,
				),
			),
		),

		Array(
			"SID" => "ATTACH_FILE",
			"REQUIRED" => "N",
			"arANSWER" => array(
				array(
					"MESSAGE" => " ",
					"VALUE" => "",
					"ACTIVE" => "Y",
					"FIELD_TYPE" => "file",
					"FIELD_WIDTH" => 40,
					"FIELD_HEIGHT" => 10,
				),
			),
		),


	);

	$formID = CreateForm($arForm, $arFormFields, "resume.php");
}

if ($formID)
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/about/resume.php", Array("RESUME_FORM_ID" => $formID));
?>
