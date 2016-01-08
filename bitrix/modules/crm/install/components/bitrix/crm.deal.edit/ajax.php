<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'ENABLE_SONET_SUBSCRIPTION'
 */
global $DB, $APPLICATION;

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));
CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	echo CUtil::PhpToJSObject(
		array('ERROR' => 'INVALID DATA!')
	);
	die();
}

if($action === 'ENABLE_SONET_SUBSCRIPTION')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$entityTypeName = isset($_POST['ENTITY_TYPE']) ? strtoupper($_POST['ENTITY_TYPE']) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	if($userID > 0 && $entityTypeName === CCrmOwnerType::DealName && $entityID > 0 && CCrmDeal::CheckReadPermission($entityID))
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Deal,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && strtoupper($_POST['ENABLE']) === 'Y' ;

		if($isEnabled !== $enable)
		{
			if($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Deal, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Deal, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
		}
	}
}
