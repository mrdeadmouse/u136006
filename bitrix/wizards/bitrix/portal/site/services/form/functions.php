<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

function CreateForm($arForm, $arFormFields, $langFile)
{
	if(!CModule::IncludeModule("form"))
		return false;

	// set defaults
	$arFieldDefaults = array(
		"ACTIVE" => "Y",
		"C_SORT" => 0,
		"ADDITIONAL" => "N",
		"TITLE_TYPE" => "text",
		"IN_RESULTS_TABLE" => "Y",
		"IN_EXCEL_TABLE" => "Y",
	);

	foreach ($arFormFields as $key => $arField)
	{
		if ($arFormFields['ADDITIONAL'] == 'Y' && !is_set($arFormFields['C_SORT']))
			$arFormFields['C_SORT'] = 5000;
			
		$arFieldDefaults["C_SORT"] += 100;
		$arFormFields[$key] = array_merge($arFieldDefaults, $arField);
		$arFormFields[$key]["TITLE"] = GetMessage($arField["SID"]."_QUESTION");
	}

	$rsLanguage = CLanguage::GetList($by, $order, array());
	while($arLanguage = $rsLanguage->Fetch())
	{
		WizardServices::IncludeServiceLang($langFile, $arLanguage["LID"]);
		$arForm["arMENU"][$arLanguage["LID"]] = GetMessage(substr($arForm["SID"], 0, -3)."_MENU_NAME");
	}

	$dbForm = CForm::GetBySID($arForm["SID"]);
	if ($dbForm->Fetch())
		return;

	WizardServices::IncludeServiceLang("status.php");

	if ($formID = CForm::Set($arForm, false, "N"))
	{
		// setup form fields
		foreach ($arFormFields as $key => $arField)
		{
			$arField["FORM_ID"] = $formID;
			$fieldID = CFormField::Set($arField, false, "N");
		}
		
		if ($arTemplates = CForm::SetMailTemplate($formID))
		{
			CForm::Set(array('SID' => $arForm['SID'], 'arMAIL_TEMPLATE' => $arTemplates), $formID, 'N');
		}

		$arStatuses = Array(
			Array(
				"FORM_ID" => $formID,
				"TITLE" => GetMessage("STATUS_NEW"),
				"C_SORT" => 100,
				"ACTIVE" => "Y",
				"DEFAULT_VALUE" => "Y",
				"CSS" => "statusgray",
				"arPERMISSION_VIEW" => array(0),
				"arPERMISSION_MOVE" => array(0),
				"arPERMISSION_EDIT" => array(0),
				"arPERMISSION_DELETE" => array(0),
			),

			Array(
				"FORM_ID" => $formID,
				"TITLE" => GetMessage("STATUS_RECEIVED"),
				"C_SORT" => 200,
				"ACTIVE" => "Y",
				"DEFAULT_VALUE" => "N",
				"CSS" => "statusblue",
				"arPERMISSION_VIEW" => array(0),
			),

			Array(
				"FORM_ID" => $formID,
				"TITLE" => GetMessage("STATUS_DONE"),
				"C_SORT" => 300,
				"ACTIVE" => "Y",
				"DEFAULT_VALUE" => "N",
				"CSS" => "statusgreen",
				"arPERMISSION_VIEW" => array(0),
			),

			Array(
				"FORM_ID" => $formID,
				"TITLE" => GetMessage("STATUS_REFUSE"),
				"C_SORT" => 400,
				"ACTIVE" => "Y",
				"DEFAULT_VALUE" => "N",
				"CSS" => "statusred",
				"arPERMISSION_VIEW" => array(0),
			),
		);

		foreach ($arStatuses as $key => $arStatus)
		{
			if ($STATUS_ID = CFormStatus::Set($arStatus, false, "N"))
			{
				if ($arStatus['DEFAULT_VALUE'] == 'N' && ($arTemplates = CFormStatus::SetMailTemplate($formID, $STATUS_ID)))
				{
					foreach ($arTemplates as $TEMPLATE_ID)
					{
						if (null == $em) $em = new CEventMessage();
						$em->Update($TEMPLATE_ID, array('MESSAGE' => GetMessage('STATUS_MESSAGE')));
					}
					
					CFormStatus::Set(array('FORM_ID' => $formID, 'arMAIL_TEMPLATE' => $arTemplates), $STATUS_ID, 'N');
				}
			}
		}
	}

	return $formID;

}
?>