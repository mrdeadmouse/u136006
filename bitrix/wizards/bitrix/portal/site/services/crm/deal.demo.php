<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
	
$arDeals['1'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_1_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '03/14/2011': '14.03.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '03/01/2011': '01.03.2011',
    'OPPORTUNITY' => '123000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '32',
    'COMPANY_ID' => $arCompany['41']['ID'],
    'CONTACT_ID' => $arContacts['50']['ID'],
    'TYPE_ID' => 'SALE',
    'STAGE_ID' => 'ON_HOLD',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
	'PRODUCT_ROWS' => array(
		array('ORIGIN_ID' => 'CRM_DEMO_PRODUCT_BX_CMS')
	)
);


$arDeals['2'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_2_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '05/03/2011': '03.05.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '05/03/2011': '03.05.2011',
    'OPPORTUNITY' => '7000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '80',
    'COMPANY_ID' => $arCompany['38']['ID'],
    'CONTACT_ID' => $arContacts['45']['ID'],
    'TYPE_ID' => 'SERVICE',
    'STAGE_ID' => 'ON_HOLD',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);

$arDeals['3'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_3_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '03/23/2011': '23.03.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '03/23/2011': '23.03.2011',
    'OPPORTUNITY' => '17000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '80',
    'COMPANY_ID' => $arCompany['38']['ID'],
    'CONTACT_ID' => $arContacts['45']['ID'],
    'TYPE_ID' => 'SERVICE',
    'STAGE_ID' => 'LOSE',
    'CLOSED' => 'Y',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	


$arDeals['4'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_4_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '03/26/2011': '26.03.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '03/23/2011': '23.03.2011',
    'OPPORTUNITY' => '12000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '85',
    'COMPANY_ID' => '0',
    'CONTACT_ID' => '0',
    'TYPE_ID' => 'COMPLEX',
    'STAGE_ID' => 'NEW',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	


$arDeals['5'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_5_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '05/07/2011': '07.05.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '04/01/2011': '01.04.2011',
    'OPPORTUNITY' => '18500',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '65',
    'COMPANY_ID' => $arCompany['42']['ID'],
    'CONTACT_ID' => $arContacts['47']['ID'],
    'TYPE_ID' => 'GOODS',
    'STAGE_ID' => 'NEW',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	


$arDeals['7'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_7_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '05/26/2011': '26.05.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '04/23/2011': '23.04.2011',
    'OPPORTUNITY' => '10000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '80',
    'COMPANY_ID' => '0',
    'CONTACT_ID' => $arContacts['48']['ID'],
    'TYPE_ID' => 'SERVICES',
    'STAGE_ID' => 'DETAILS',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	


$arDeals['8'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_8_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '05/12/2011': '12.05.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '04/12/2011': '12.04.2011',
    'OPPORTUNITY' => '30000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '50',
    'COMPANY_ID' => $arCompany['39']['ID'],
    'CONTACT_ID' => $arContacts['51']['ID'],
    'TYPE_ID' => 'COMPLEX',
    'STAGE_ID' => 'PROPOSAL',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
	'LEAD_ID' => $arLeads['57']['ID'],
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
	'PRODUCT_ROWS' => array(
		array('ORIGIN_ID' => 'CRM_DEMO_PRODUCT_BX_CP')
	)
);	


$arDeals['9'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_9_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '04/26/2011': '26.04.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '04/03/2011': '03.04.2011',
    'OPPORTUNITY' => '25000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '56',
    'COMPANY_ID' => $arCompany['40']['ID'],
    'CONTACT_ID' => $arContacts['49']['ID'],
    'TYPE_ID' => 'COMPLEX',
    'STAGE_ID' => 'PROPOSAL',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
	'PRODUCT_ROWS' => array(
		array('ORIGIN_ID' => 'CRM_DEMO_PRODUCT_BX_CP')
	)
);	


$arDeals['10'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_10_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '05/03/2011': '03.05.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '03/26/2011': '26.03.2011',
    'OPPORTUNITY' => '10000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '90',
    'COMPANY_ID' => $arCompany['41']['ID'],
    'CONTACT_ID' => $arContacts['50']['ID'],
    'TYPE_ID' => 'SERVICE',
    'STAGE_ID' => 'NEGOTIATION',
    'EVENT_DATE' => LANGUAGE_ID == "en"? '04/22/2011': '22.04.2011',
    'EVENT_ID' => 'PHONE',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	


$arDeals['11'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_DEAL_11_TITLE"),
    'COMMENTS' => '',
    'CLOSEDATE' => LANGUAGE_ID == "en"? '03/26/2011': '26.03.2011',
    'BEGINDATE' => LANGUAGE_ID == "en"? '03/23/2011': '23.03.2011',
    'OPPORTUNITY' => '10000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'PROBABILITY' => '100',
    'COMPANY_ID' => $arCompany['38']['ID'],
    'CONTACT_ID' => $arContacts['45']['ID'],
    'TYPE_ID' => 'SERVICE',
    'STAGE_ID' => 'WON',
    'CLOSED' => 'Y',
    'EVENT_DATE' => '',
    'EVENT_ID' => '',
    'EVENT_DESCRIPTION' => '',
    'ASSIGNED_BY_ID' => '0',
);	

?>