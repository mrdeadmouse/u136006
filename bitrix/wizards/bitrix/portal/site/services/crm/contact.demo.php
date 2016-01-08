<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arContacts['46'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_46_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_46_LAST_NAME"),
    'SECOND_NAME' => '',
    'POST' => GetMessage("CRM_DEMO_CONTACT_46_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'SHARE',
    'SOURCE_ID' => 'SELF',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/86b/72b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
		'PHONE' => Array(
            'n1' => Array(
                'VALUE' => '213 11 64',
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'IM' => Array(
            'n1' => Array(
                'VALUE' => 'vm@msn.com',
                'VALUE_TYPE' => 'MSN',
            )
        )
    )
);

$arContacts['45'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_45_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_45_LAST_NAME"),
    'SECOND_NAME' => GetMessage("CRM_DEMO_CONTACT_45_SECOND_NAME"),
    'POST' => GetMessage("CRM_DEMO_CONTACT_45_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'SUPPLIER',
    'SOURCE_ID' => 'TRADE_SHOW',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/fc0/69b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_45_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'PHONE' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_45_PHONE_MOBILE"),
                'VALUE_TYPE' => 'MOBILE',
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
		'IM' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_45_IM_SKYPE"),
                'VALUE_TYPE' => 'SKYPE',
            )
        )
    )
);		


$arContacts['47'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_47_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_47_LAST_NAME"),
    'SECOND_NAME' => GetMessage("CRM_DEMO_CONTACT_47_SECOND_NAME"),
    'POST' => GetMessage("CRM_DEMO_CONTACT_47_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'SHARE',
    'SOURCE_ID' => 'SELF',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/a30/67b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_47_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    )
);		


$arContacts['48'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_48_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_48_LAST_NAME"),
    'SECOND_NAME' => '',
    'POST' => GetMessage("CRM_DEMO_CONTACT_48_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'CLIENT',
    'SOURCE_ID' => 'CALL',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/bab/36b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
        'PHONE' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_48_PHONE_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    )
);		

$arContacts['51'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_51_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_51_LAST_NAME"),
    'SECOND_NAME' => '',
    'POST' => GetMessage("CRM_DEMO_CONTACT_51_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'SUPPLIER',
    'SOURCE_ID' => 'PARTNER',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
	'LEAD_ID' => $arLeads['57']['ID'],
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/8f8/200491503-003.gif')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
);	

$arContacts['50'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_50_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_50_LAST_NAME"),
    'SECOND_NAME' => '',
    'POST' => GetMessage("CRM_DEMO_CONTACT_50_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'PARTNER',
    'SOURCE_ID' => 'SELF',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/ddc/13b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_50_EMAIL_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
		'IM' => Array(
            'n1' => Array(
                'VALUE' => '202707',
                'VALUE_TYPE' => 'ICQ',
            )
        ),
		'WEB' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_COMPANY_41_WEB_WORK"),
                'VALUE_TYPE' => 'WORK',
            )
        ),
    )
);	
$arContacts['49'] = Array(
    'TITLE' => '',
    'NAME' => GetMessage("CRM_DEMO_CONTACT_49_NAME"),
    'LAST_NAME' => GetMessage("CRM_DEMO_CONTACT_49_LAST_NAME"),
    'SECOND_NAME' => '',
    'POST' => GetMessage("CRM_DEMO_CONTACT_49_POST"),
    'ADDRESS' => '',
    'COMMENTS' => '',
    'SOURCE_DESCRIPTION' => '',
	'TYPE_ID' => 'SUPPLIER',
    'SOURCE_ID' => 'WEB',
    'COMPANY_ID' => '0',
    'ASSIGNED_BY_ID' => '0',
    'PHOTO' => CFile::MakeFileArray(WIZARD_SITE_PATH.'/upload/crm/c87/21b.jpg')+Array('MODULE_ID'=>'crm'),
    'EXPORT' => 'Y',
    'FM' => Array(
        'EMAIL' => Array(
            'n1' => Array(
                'VALUE' => GetMessage("CRM_DEMO_CONTACT_49_EMAIL_WORK"),
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
    )
);	


?>