<?
$langs = CLanguage::GetList(($b=""), ($o=""));
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events.php", $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASK_REMINDER",
		"NAME" => GetMessage("TASK_REMINDER_NAME"),
		"DESCRIPTION" => GetMessage("TASK_REMINDER_DESC"),
	));

	$arSites = array();
	$sites = CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASK_REMINDER",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("TASK_REMINDER_SUBJECT"),
			"MESSAGE" => GetMessage("TASK_REMINDER_MESSAGE"),
			"BODY_TYPE" => "text",
		));
	}
}
?>
