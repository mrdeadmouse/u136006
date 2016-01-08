<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arResult["arForm"]["SID"] == "SERVICE_ORDER")
{
	$current_value = intval($_REQUEST['form_type']);
	$question_sid = 'REQUEST_TYPE';

	if ($GLOBALS['USER']->IsAdmin())
	{
		foreach ($arResult['arDropDown'][$question_sid]['reference'] as $key => $MESSAGE)
		{
			$arResult['arDropDown'][$question_sid]['reference'][$key] = 
				'['.$arResult['arDropDown'][$question_sid]['reference_id'][$key].'] '.$MESSAGE;
		}
	}
	
	$arResult['QUESTIONS'][$question_sid]['HTML_CODE'] = CForm::GetDropDownField(
		$question_sid,
		$arResult["arDropDown"][$question_sid],
		$current_value
	);

}

if ($arResult["arForm"]["SID"] == "TROUBLESHOOTING")
{
	$current_value = intval($_REQUEST['form_type']);
	$question_sid = 'REQUEST_TYPE';

	if ($GLOBALS['USER']->IsAdmin())
	{
		foreach ($arResult['arDropDown'][$question_sid]['reference'] as $key => $MESSAGE)
		{
			$arResult['arDropDown'][$question_sid]['reference'][$key] = 
				'['.$arResult['arDropDown'][$question_sid]['reference_id'][$key].'] '.$MESSAGE;
		}
	}
	
	$arResult['QUESTIONS'][$question_sid]['HTML_CODE'] = CForm::GetDropDownField(
		$question_sid,
		$arResult["arDropDown"][$question_sid],
		$current_value
	);

}

if ($arResult["arForm"]["SID"] == "DIRECTION")
{
	$current_value = intval($_REQUEST['form_type']);
	$question_sid = 'REQUEST_TYPE';

	if ($GLOBALS['USER']->IsAdmin())
	{
		foreach ($arResult['arDropDown'][$question_sid]['reference'] as $key => $MESSAGE)
		{
			$arResult['arDropDown'][$question_sid]['reference'][$key] = 
				'['.$arResult['arDropDown'][$question_sid]['reference_id'][$key].'] '.$MESSAGE;
		}
	}
	
	$arResult['QUESTIONS'][$question_sid]['HTML_CODE'] = CForm::GetDropDownField(
		$question_sid,
		$arResult["arDropDown"][$question_sid],
		$current_value
	);

}

?>