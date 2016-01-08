<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
	
$arEvents['1'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'LEAD',
	'ENTITY_ID'    => $arLeads['58']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_1_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_LEAD_58_PHONE_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['2'] = Array(
	'ENTITY_FIELD' => 'STATUS_ID',
	'ENTITY_TYPE'  => 'LEAD',
	'ENTITY_ID'    => $arLeads['58']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_2_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_EVENT_2_EVENT_TEXT_1"),
);	
$arEvents['3'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'LEAD',
	'ENTITY_ID'    => $arLeads['56']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_3_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_LEAD_56_PHONE_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['4'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['40']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_4_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_40_EMAIL_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['5'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['40']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_5_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_40_PHONE_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['6'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['40']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_6_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_40_WEB_WORK"),
	'EVENT_TEXT_2' => '',
);	

$arEvents['8'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['42']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_8_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_42_EMAIL_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['9'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['42']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_9_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_42_WEB_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['10'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['39']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_10_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_39_EMAIL_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['11'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['39']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_11_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_39_WEB_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['12'] = Array(
	'ENTITY_FIELD' => 'EMPLOYEES',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['39']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_12_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_EVENT_12_EVENT_TEXT_1"),
	'EVENT_TEXT_2' => '50-250',
);	
$arEvents['13'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['41']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_13_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_41_EMAIL_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['14'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['41']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_14_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_41_WEB_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['15'] = Array(
	'ENTITY_FIELD' => 'EMPLOYEES',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['41']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_15_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_EVENT_15_EVENT_TEXT_1"),
	'EVENT_TEXT_2' => '50-250',
);	
$arEvents['16'] = Array(
	'ENTITY_FIELD' => '',
	'ENTITY_TYPE'  => 'COMPANY',
	'ENTITY_ID'    => $arCompany['38']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_16_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_COMPANY_38_PHONE_WORK"),
	'EVENT_TEXT_2' => '',
);	
$arEvents['17'] = Array(
	'ENTITY_FIELD' => 'OPPORTUNITY',
	'ENTITY_TYPE'  => 'DEAL',
	'ENTITY_ID'    => $arDeals['5']['ID'],
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_17_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_EVENT_17_EVENT_TEXT_1"),
	'EVENT_TEXT_2' => GetMessage("CRM_DEMO_EVENT_17_EVENT_TEXT_2"),
);	
$arEvents['18'] = Array(
	'ENTITY_FIELD' => 'STATUS_ID',
	'ENTITY_TYPE'  => 'LEAD',
	'ENTITY_ID'    => $arLeads['57']['ID'],
	'EVENT_ID'	   => '',
	'EVENT_TYPE'   => '1',
	'EVENT_NAME' => GetMessage("CRM_DEMO_EVENT_18_EVENT_NAME"),
	'EVENT_TEXT_1' => GetMessage("CRM_DEMO_EVENT_18_EVENT_TEXT_1"),
	'EVENT_TEXT_2' => GetMessage("CRM_DEMO_EVENT_18_EVENT_TEXT_2"),
);	

?>