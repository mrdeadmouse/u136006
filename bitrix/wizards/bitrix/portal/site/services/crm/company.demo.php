<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arCompany['40'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_COMPANY_40_TITLE"),
    'ADDRESS' => '',
    'ADDRESS_LEGAL' => '',
    'BANKING_DETAILS' => '',
    'COMMENTS' => '',
    'COMPANY_TYPE' => 'CUSTOMER',
	'INDUSTRY' => 'MANUFACTURING',
    'REVENUE' => '10000000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'EMPLOYEES' => 'EMPLOYEES_3',
    'LOGO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/cl2/02.gif')+Array('MODULE_ID'=>'crm'),
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_40_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'PHONE' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_40_PHONE_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_40_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    ),
	'CONTACT_ID' => Array($arContacts['49']['ID'])
);

$arCompany['39'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_COMPANY_39_TITLE"),
    'ADDRESS' => '',
    'ADDRESS_LEGAL' => '',
    'BANKING_DETAILS' => '',
    'COMMENTS' => '',
    'COMPANY_TYPE' => 'PROSPECT',
	'INDUSTRY' => 'IT',
    'REVENUE' => '0',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'EMPLOYEES' => 'EMPLOYEES_2',
	'LEAD_ID' => $arLeads['57']['ID'],
    'LOGO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/cl5/05.gif')+Array('MODULE_ID'=>'crm'),
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_39_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_39_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    ),
	'CONTACT_ID' => Array($arContacts['46']['ID'], $arContacts['51']['ID'])
);

$arCompany['42'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_COMPANY_42_TITLE"),
    'ADDRESS' => '',
    'ADDRESS_LEGAL' => '',
    'BANKING_DETAILS' => '',
    'COMMENTS' => '',
    'COMPANY_TYPE' => 'PARTNER',
	'INDUSTRY' => 'IT',
    'REVENUE' => '300000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'EMPLOYEES' => 'EMPLOYEES_1',
    'LOGO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/cl4/04.gif')+Array('MODULE_ID'=>'crm'),
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_42_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_42_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    ),
	'CONTACT_ID' => Array($arContacts['47']['ID'])
);

$arCompany['41'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_COMPANY_41_TITLE"),
    'ADDRESS' => '',
    'ADDRESS_LEGAL' => '',
    'BANKING_DETAILS' => '',
    'COMMENTS' => '',
    'COMPANY_TYPE' => 'PARTNER',
	'INDUSTRY' => 'DELIVERY',
    'REVENUE' => '0',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'EMPLOYEES' => 'EMPLOYEES_2',
    'LOGO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/cl1/01.gif')+Array('MODULE_ID'=>'crm'),
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_41_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_41_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    ),
	'CONTACT_ID' => Array($arContacts['50']['ID'])
);

$arCompany['38'] = Array(
    'TITLE' => GetMessage("CRM_DEMO_COMPANY_38_TITLE"),
    'ADDRESS' => '',
    'ADDRESS_LEGAL' => '',
    'BANKING_DETAILS' => '',
    'COMMENTS' => '',
    'COMPANY_TYPE' => 'CUSTOMER',
	'INDUSTRY' => 'IT',
    'REVENUE' => '120000',
    'CURRENCY_ID' => GetMessage("CRM_DEMO_CURRENCY_ID"),
    'EMPLOYEES' => 'EMPLOYEES_1',
    'LOGO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/cl3/03.gif')+Array('MODULE_ID'=>'crm'),
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_38_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_38_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            ),
			'n2' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_38_WEB_FACEBOOK"),
                'VALUE_TYPE' => 'FACEBOOK',
            ),
			'n3' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_38_WEB_TWITTER"),
                'VALUE_TYPE' => 'TWITTER',
            )
        ),
		'PHONE' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_38_PHONE_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    ),
	'CONTACT_ID' => Array($arContacts['45']['ID'])
);


?>