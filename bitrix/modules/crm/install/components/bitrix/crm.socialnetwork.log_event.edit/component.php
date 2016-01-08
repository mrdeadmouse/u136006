<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CModule::IncludeModule('socialnetwork'))
{
	ShowError(GetMessage('SONET_MODULE_NOT_INSTALLED'));
	return;
}

$arResult['POST_FORM_URI'] = isset($arParams['POST_FORM_URI']) ? $arParams['POST_FORM_URI'] : '';

$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
if(CCrmOwnerType::IsDefined($entityTypeID))
{
	$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
}
else
{
	$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);

}
$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;

$arResult['ENABLE_LIVE_FEED_EXTENDED_MODE'] = true;
$arResult['LIVE_FEED_ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
$arResult['ENTITY_TYPE_NAME'] = $entityTypeName;
$arResult['ENTITY_TYPE_ID'] = $entityTypeID;
$arResult['ENTITY_ID'] = $entityID;

$userID = CCrmSecurityHelper::GetCurrentUserID();
$arResult['USER_ID'] = $userID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if($entityTypeName !== '' && !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
$arResult['PERMISSIONS'] = $userPerms;

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'crm_sl_event_edit';
}
$arResult['UID'] =$arParams['UID'] = $uid;
$arResult['MESSAGE_TITLE_FIELD_NAME'] = $arParams['UID'] = $uid;
$cacheManager = $GLOBALS['CACHE_MANAGER'];

$allowLiveFeedToAll = $defaultLiveFeedToAll = false;

$arParams['USE_CUT'] = isset($arParams['USE_CUT']) && $arParams['USE_CUT'] === 'Y' ? 'Y' : 'N';


global $USER_FIELD_MANAGER;
$sonetFields = $USER_FIELD_MANAGER->GetUserFields('SONET_LOG', 0);
$arResult['WEB_DAV_FILE_FIELD_NAME'] = 'UF_SONET_LOG_DOC';
$arResult['WEB_DAV_FILE_FIELD'] = isset($sonetFields['UF_SONET_LOG_DOC']) ? $sonetFields['UF_SONET_LOG_DOC'] : null;

$arResult['EVENT'] = array('MESSAGE'=> '', 'TITLE'=> '');
$arResult['ENTITY_DATA'] = array();
$arResult['ENABLE_TITLE'] = false;
$arResult['FEED_DESTINATION'] = array(
	'LAST' => array(
		'CONTACTS' => array(),
		'COMPANIES' => array(),
		'LEADS' => array(),
		'DEALS' => array()
	),
	'SELECTED' => array()
);

$arResult['ERROR_MESSAGES'] = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if(isset($_POST['save']) && $_POST['save'] === 'Y')
	{
		$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($_POST, $entityTypeID, $entityID, $arResult);

		if(
			!is_array($res) 
			&& intval($res) > 0
		)
		{
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPage());
		}
		else
		{
			foreach($res as $error)
			{
				$arResult['ERROR_MESSAGES'][] = $error;
			}
		}
	}
}

//$arResult['FEED_DESTINATION']['LAST']['SONETGROUPS'] = CSocNetLogDestination::GetLastSocnetGroup();
// ALLOWED SONET GROUPS -->
$isManagedCacheEnabled = defined('BX_COMP_MANAGED_CACHE');
$cacheTtl = $isManagedCacheEnabled ? 3153600 : 3600 * 4;
$eventDestCacheID = 'crm_sl_event_dest_'.SITE_ID.'_'.$userID;
$eventDestCacheDir = '/crm/sl_event/dest/'.SITE_ID.'/'.$userID;
$cache = new CPHPCache;
if($cache->InitCache($arParams['CACHE_TIME'], $eventDestCacheID, $eventDestCacheDir))
{
	$arResult['FEED_DESTINATION']['SONETGROUPS'] = $cache->GetVars();
}
else
{
	$cache->StartDataCache();
	$arResult['FEED_DESTINATION']['SONETGROUPS'] = CSocNetLogDestination::GetSocnetGroup();
	if($isManagedCacheEnabled)
	{
		$cacheManager->StartTagCache($eventDestCacheDir);
		foreach($arResult['FEED_DESTINATION']['SONETGROUPS'] as $val)
		{
			$cacheManager->RegisterTag("sonet_features_G_{$val['entityId']}");
			$cacheManager->RegisterTag("sonet_group_{$val['entityId']}");
		}
		$cacheManager->RegisterTag("sonet_user2group_U{$userID}");
		$cacheManager->EndTagCache();
	}
	$cache->EndDataCache($arResult['FEED_DESTINATION']['SONETGROUPS']);
}
// <-- ALLOWED SONET GROUPS

if(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	foreach($arResult['FEED_DESTINATION']['SONETGROUPS'] as $k => $val)
	{
		$arResult['FEED_DESTINATION']['SELECTED'][$k] = 'sonetgroups';
	}

	if(empty($arResult['FEED_DESTINATION']['SELECTED']))
	{
		ShowError(GetMessage('CRM_SL_EVENT_NOT_AVAIBLE'));
		return;
	}
}
elseif($defaultLiveFeedToAll)
{
	$arResult['FEED_DESTINATION']['SELECTED']['UA'] = 'groups';
}

$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'] = array();
$hiddenGroups = array();
if(!empty($arResult['FEED_DESTINATION']['SELECTED']))
{
	foreach($arResult['FEED_DESTINATION']['SELECTED'] as $groupID => $value)
	{
		if($value === 'sonetgroups' && empty($arResult['FEED_DESTINATION']['SONETGROUPS'][$groupID]))
		{
			$hiddenGroups[] = substr($groupID, 2);
		}
	}
}

if(!empty($hiddenGroups))
{
	$rsGroup = CSocNetGroup::GetList(
		array(),
		array('ID' => $hiddenGroups),
		false,
		false,
		array('ID', 'NAME')
	);
	while($arGroup = $rsGroup->Fetch())
	{
		$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'][$arGroup['ID']] = array('ID' => $arGroup['ID'], 'NAME' => $arGroup['NAME']);
	}

	if(!CSocNetUser::IsCurrentUserModuleAdmin() && $userID > 0)
	{
		$arGroupID = array();
		if(!empty($GLOBALS['SONET_GROUPS_ID_AVAILABLE']))
			$arGroupID = $GLOBALS['SONET_GROUPS_ID_AVAILABLE'];
		else
		{
			// get tagged cached available groups and intersect
			$cache = new CPHPCache;
			$groupCacheID = $userID;
			$groupCacheDir = "/sonet/groups_available/{$userID}/";

			if($cache->InitCache($arParams['CACHE_TIME'], $groupCacheID, $groupCacheDir))
			{
				$arCacheVars = $cache->GetVars();
				$arGroupID = $arCacheVars['arGroupID'];
			}
			else
			{
				$cache->StartDataCache($arParams['CACHE_TIME'], $groupCacheID, $groupCacheDir);
				if($isManagedCacheEnabled)
				{
					$cacheManager->StartTagCache($groupCacheDir);
					$cacheManager->RegisterTag("sonet_user2group_U{$userID}");
					$cacheManager->RegisterTag('sonet_group');
				}

				$rsGroup = CSocNetGroup::GetList(
					array(),
					array('CHECK_PERMISSIONS' => $userID),
					false,
					false,
					array('ID')
				);
				while($arGroup = $rsGroup->Fetch())
					$arGroupID[] = $arGroup['ID'];

				$arCacheData = array(
					'arGroupID' => $arGroupID
				);
				if($isManagedCacheEnabled)
					$cacheManager->EndTagCache();
				$cache->EndDataCache($arCacheData);
			}

			$GLOBALS['SONET_GROUPS_ID_AVAILABLE'] = $arGroupID;
		}

		foreach($arResult['FEED_DESTINATION']['HIDDEN_GROUPS'] as $group_code => $arBlogSPerm)
		{
			if(!in_array($group_code, $arGroupID))
			{
				$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'][$group_code]['NAME'] = GetMessage('CRM_SL_EVENT_EDIT_HIDDEN_GROUP');
			}
		}
	}
}

// intranet structure
$arStructure = CSocNetLogDestination::GetStucture(array('LAZY_LOAD' => true));
$arResult['FEED_DESTINATION']['DEPARTMENT'] = $arStructure['department'];
$arResult['FEED_DESTINATION']['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
$arResult['FEED_DESTINATION']['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

//$arResult['FEED_DESTINATION']['LAST']['DEPARTMENT'] = CSocNetLogDestination::GetLastDepartment();

// users
//$arResult['FEED_DESTINATION']['LAST']['USERS'] = CSocNetLogDestination::GetLastUser();

if(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	$arResult['FEED_DESTINATION']['EXTRANET_USER'] = 'Y';
	$arResult['FEED_DESTINATION']['USERS'] = CSocNetLogDestination::GetExtranetUser();
}
else
{
//	$destUsers = array();
//	foreach($arResult['FEED_DESTINATION']['LAST']['USERS'] as $value)
//		$destUsers[] = str_replace('U', '', $value);
//
	$arResult['FEED_DESTINATION']['EXTRANET_USER'] = 'N';
//	$arResult['FEED_DESTINATION']['USERS'] = CSocNetLogDestination::GetUsers(array('id' => $destUsers));
}


$crmLogDestination = CUserOptions::GetOption('crm', 'log_destination');
$crmLogDestinationItems = isset($crmLogDestination['items']) ? explode(',', $crmLogDestination['items']) : false;
if (!empty($crmLogDestinationItems))
{
	$lastContactIds = array();
	$lastCompanyIds = array();
	$lastLeadIds = array();
	$lastDealIds = array();

	foreach ($crmLogDestinationItems as $crmItemId)
	{
		if (preg_match('/^CRMCONTACT(\d+)$/i', $crmItemId, $matches))
			$lastContactIds[] = $matches[1];
		else if (preg_match('/^CRMCOMPANY(\d+)$/i', $crmItemId, $matches))
			$lastCompanyIds[] = $matches[1];
		else if (preg_match('/^CRMLEAD(\d+)$/i', $crmItemId, $matches))
			$lastLeadIds[] = $matches[1];
		else if (preg_match('/^CRMDEAL(\d+)$/i', $crmItemId, $matches))
			$lastDealIds[] = $matches[1];
	}

	$dbLastContacts = $lastContactIds ? CCrmContact::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastContactIds),
		$arGroupBy = false,
		$arNavStartParams = array(),
		$arSelectFields = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
	) : false;
	$dbLastCompanies = $lastCompanyIds ? CCrmCompany::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastCompanyIds),
		$arGroupBy = false,
		$arNavStartParams = array(),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
	) : false;
	$dbLastLeads = $lastLeadIds ? CCrmLead::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastLeadIds),
		$arGroupBy = false,
		$arNavStartParams = array(),
		$arSelectFields = array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
	) : false;
	$dbLastDeals = $lastDealIds ? CCrmDeal::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastDealIds),
		$arGroupBy = false,
		$arNavStartParams = array(),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
	) : false;
}
else
{
	$dbLastContacts = CCrmContact::GetListEx(
		$arOrder = array('ID' => 'DESC'),
		$arFilter = array(),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'DATE_CREATE')
	);
	$dbLastCompanies = CCrmCompany::GetListEx(
		$arOrder = array('ID' => 'DESC'),
		$arFilter = array(),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'DATE_CREATE')
	);
	$dbLastLeads = CCrmLead::GetListEx(
		$arOrder = array('ID' => 'DESC'),
		$arFilter = array(),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE')
	);
	$dbLastDeals = CCrmDeal::GetListEx(
		$arOrder = array('ID' => 'DESC'),
		$arFilter = array(),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'DATE_CREATE')
	);
}

function prepareCrmEntity($type, $data)
{
	static $siteNameFormat, $arCompanyTypeList, $arCompanyIndustryList;

	if (is_null($siteNameFormat))
	{
		$siteNameFormat = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
		$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
	}

	switch ($type)
	{
		case 'contact':
			$result = array(
				'id'         => 'CRMCONTACT'.$data['ID'],
				'entityType' => 'contacts',
				'entityId'   => $data['ID'],
				'name'       => htmlspecialcharsbx(CUser::FormatName(
					$siteNameFormat,
					array(
						'LOGIN'       => '',
						'NAME'        => $data['NAME'],
						'SECOND_NAME' => $data['SECOND_NAME'],
						'LAST_NAME'   => $data['LAST_NAME']
					),
					false, false
				)),
				'desc' => htmlspecialcharsbx($data['COMPANY_TITLE'])
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			if (!empty($data['PHOTO']) && intval($data['PHOTO']) > 0)
			{
				$arImg = CFile::ResizeImageGet($data['PHOTO'], array('width' => 30, 'height' => 30), BX_RESIZE_IMAGE_EXACT);
				$result['avatar'] = $arImg['src'];
			}
			break;
		case 'company':
			$arDesc = Array();
			if (isset($arCompanyTypeList[$data['COMPANY_TYPE']]))
				$arDesc[] = $arCompanyTypeList[$data['COMPANY_TYPE']];
			if (isset($arCompanyIndustryList[$data['INDUSTRY']]))
				$arDesc[] = $arCompanyIndustryList[$data['INDUSTRY']];

			$result = array(
				'id'         => 'CRMCOMPANY'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'companies',
				'name'       => htmlspecialcharsbx(str_replace(array(';', ','), ' ', $data['TITLE'])),
				'desc'       => htmlspecialcharsbx(implode(', ', $arDesc))
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);

			if (!empty($data['LOGO']) && intval($data['LOGO']) > 0)
			{
				$arImg = CFile::ResizeImageGet($data['LOGO'], array('width' => 30, 'height' => 30), BX_RESIZE_IMAGE_EXACT);
				$result['avatar'] = $arImg['src'];
			}
			break;
		case 'lead':
			$result = array(
				'id'         => 'CRMLEAD'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'leads',
				'name'       => htmlspecialcharsbx($data['TITLE']),
				'desc'       => htmlspecialcharsbx(CUser::FormatName(
					$siteNameFormat,
					array(
						'LOGIN'       => '',
						'NAME'        => $data['NAME'],
						'SECOND_NAME' => $data['SECOND_NAME'],
						'LAST_NAME'   => $data['LAST_NAME']
					),
					false, false
				))
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			break;
		case 'deal':
			$arDesc = array();
			if ($data['COMPANY_TITLE'] != '')
				$arDesc[] = $data['COMPANY_TITLE'];
			$arDesc[] = CUser::FormatName(
				$siteNameFormat,
				array(
					'LOGIN'       => '',
					'NAME'        => $data['CONTACT_NAME'],
					'SECOND_NAME' => $data['CONTACT_SECOND_NAME'],
					'LAST_NAME'   => $data['CONTACT_LAST_NAME']
				),
				false, false
			);

			$result = array(
				'id'         => 'CRMDEAL'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'deals',
				'name'       => htmlspecialcharsbx($data['TITLE']),
				'desc'       => htmlspecialcharsbx(implode(', ', $arDesc))
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			break;
	}

	return $result;
}

$arLastContacts = array();
$arLastCompanies = array();
$arLastLeads = array();
$arLastDeals = array();

while ($dbLastContacts && ($arContact = $dbLastContacts->fetch()))
	$arLastContacts['CRMCONTACT'.$arContact['ID']] = prepareCrmEntity('contact', $arContact);
while ($dbLastCompanies && ($arCompany = $dbLastCompanies->fetch()))
	$arLastCompanies['CRMCOMPANY'.$arCompany['ID']] = prepareCrmEntity('company', $arCompany);
while ($dbLastLeads && ($arLead = $dbLastLeads->fetch()))
	$arLastLeads['CRMLEAD'.$arLead['ID']] = prepareCrmEntity('lead', $arLead);
while ($dbLastDeals && ($arDeal = $dbLastDeals->fetch()))
	$arLastDeals['CRMDEAL'.$arDeal['ID']] = prepareCrmEntity('deal', $arDeal);

if (!empty($crmLogDestinationItems))
{
	$arResult['FEED_DESTINATION']['CONTACTS']  = $arLastContacts;
	$arResult['FEED_DESTINATION']['COMPANIES'] = $arLastCompanies;
	$arResult['FEED_DESTINATION']['LEADS']     = $arLastLeads;
	$arResult['FEED_DESTINATION']['DEALS']     = $arLastDeals;

	$arResult['FEED_DESTINATION']['LAST']['CONTACTS']  = empty($arLastContacts) ? array() : array_combine(array_keys($arLastContacts), array_keys($arLastContacts));
	$arResult['FEED_DESTINATION']['LAST']['COMPANIES'] = empty($arLastCompanies) ? array() : array_combine(array_keys($arLastCompanies), array_keys($arLastCompanies));
	$arResult['FEED_DESTINATION']['LAST']['LEADS']     = empty($arLastLeads) ? array() : array_combine(array_keys($arLastLeads), array_keys($arLastLeads));
	$arResult['FEED_DESTINATION']['LAST']['DEALS']     = empty($arLastDeals) ? array() : array_combine(array_keys($arLastDeals), array_keys($arLastDeals));

	$arResult['FEED_DESTINATION']['LAST']['CRM'] = $crmLogDestinationItems;
}
else
{
	$lastElements = array_merge($arLastContacts, $arLastCompanies, $arLastLeads, $arLastDeals);
	usort($lastElements, create_function('$a,$b', 'return $a["date"] == $b["date"] ? 0 : $a["date"] < $b["date"];'));

	$arResult['FEED_DESTINATION']['LAST']['CRM'] = array();
	for ($i = 0; $i < 20; $i++)
	{
		if (!isset($lastElements[$i]))
			break;

		$el = $lastElements[$i];

		unset($el['date']);
		$arResult['FEED_DESTINATION'][strtoupper($el['entityType'])][$el['id']] = $el;

		$arResult['FEED_DESTINATION']['LAST'][strtoupper($el['entityType'])][$el['id']] = $el['id'];
		$arResult['FEED_DESTINATION']['LAST']['CRM'][] = $el['id'];
	}
	CUserOptions::SetOption('crm', 'log_destination', array('items' => join(',', $arResult['FEED_DESTINATION']['LAST']['CRM'])));
}

$types = array('CONTACT' => 'contacts', 'COMPANY' => 'companies', 'LEAD' => 'leads', 'DEAL' => 'deals');
if(!empty($arResult['ENTITY_DATA']))
{
	foreach($arResult['ENTITY_DATA'] as &$entityData)
	{
		$entityTypeName = CCrmOwnerType::ResolveName(CCrmLiveFeedEntity::ResolveEntityTypeID($entityData['ENTITY_TYPE']));
		$entityID = $entityData['ENTITY_ID'];
		$arResult['FEED_DESTINATION']['SELECTED']['CRM'.$entityTypeName.$entityID] = $types[$entityTypeName];
	}
	unset($entityData);
}

if ($entityTypeName && $entityID)
{
	if (isset($types[$entityTypeName]))
	{
		if(empty($arResult['ENTITY_DATA']))
		{
			$arResult['FEED_DESTINATION']['SELECTED']['CRM'.$entityTypeName.$entityID] = $types[$entityTypeName];
		}

		if (!isset($arResult['FEED_DESTINATION'][strtoupper($types[$entityTypeName])]['CRM'.$entityTypeName.$entityID]))
		{
			switch ($entityTypeName)
			{
				case 'CONTACT':
					$dbEntity = CCrmContact::GetListEx(
						array(), array('ID' => $entityID), false, array(),
						array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
					);
					break;
				case 'COMPANY':
					$dbEntity = CCrmCompany::GetListEx(
						array(), array('ID' => $entityID), false, array(),
						array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
					);
					break;
				case 'LEAD':
					$dbEntity = CCrmLead::GetListEx(
						array(), array('ID' => $entityID), false, array(),
						array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
					);
					break;
				case 'DEAL':
					$dbEntity = CCrmDeal::GetListEx(
						array(), array('ID' => $entityID), false, array(),
						array('ID', 'TITLE', 'STAGE_ID')
					);
					break;
				default:
					$dbEntity = null;
			}

			if ($dbEntity && ($arEntity = $dbEntity->fetch()))
				$arResult['FEED_DESTINATION'][strtoupper($types[$entityTypeName])]['CRM'.$entityTypeName.$arEntity['ID']] = prepareCrmEntity(strtolower($entityTypeName), $arEntity);
		}
	}
}

$arResult['FEED_DESTINATION']['DENY_TOALL'] = !$allowLiveFeedToAll;

$this->IncludeComponentTemplate();

