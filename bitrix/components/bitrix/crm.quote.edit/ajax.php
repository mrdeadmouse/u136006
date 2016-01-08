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
 * 'RECALCULATE'
 */
global $DB, $APPLICATION;

$a = CCrmSecurityHelper::IsAuthorized();
$b = check_bitrix_sessid();
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
	if($userID > 0 && $entityTypeName === CCrmOwnerType::QuoteName && $entityID > 0 && CCrmQuote::CheckReadPermission($entityID))
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Quote,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && strtoupper($_POST['ENABLE']) === 'Y' ;

		if($isEnabled !== $enable)
		{
			if($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Quote, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Quote, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
		}
	}
}
else if($action === 'RECALCULATE')
{
	try
	{
		$resultOptions = array();
		$options = isset($_POST['OPTIONS']) ? $_POST['OPTIONS'] : array();
		if (!is_array($options))
			throw new Exception('MISSING OPTIONS!');

		if (isset($options['CLIENT_FIELDS']) && is_array($options['CLIENT_FIELDS']))
		{
			$options = $options['CLIENT_FIELDS'];
			if (isset($options['CONTACT_ID']) && isset($options['COMPANY_ID']))
			{
				$fields = array(
					'CONTACT_ID' => intval($options['CONTACT_ID']),
					'COMPANY_ID' => intval($options['COMPANY_ID'])
				);
				foreach (CCrmQuote::GetClientFields() as $k)
					$fields[$k] = '';
				unset($k);
				CCrmQuote::RewriteClientFields($fields, false);
				$resultOptions['CLIENT_FIELDS'] = $fields;
			}
			else
				throw new Exception('INVALID CLIENT_FIELDS OPTIONS!');
		}
		else
			throw new Exception('INVALID OPTIONS!');

		echo CUtil::PhpToJSObject($resultOptions);
	}
	catch (Exception $e)
	{
		echo CUtil::PhpToJSObject(array('ERROR' => $e->getMessage()));
	}
}
elseif($action == 'GET_WEBDAV_ELEMENT_INFO')
{
	$elementID = isset($_POST['ELEMENT_ID']) ? intval($_POST['ELEMENT_ID']) : 0;

	if($elementID <= 0)
	{
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid data')
		);
		die();
	}

	echo CUtil::PhpToJSObject(
		array(
			'DATA' => array(
				'ELEMENT_ID' => $elementID,
				'INFO' => \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$elementID,
					\Bitrix\Crm\Integration\StorageType::WebDav
				)
			)
		)
	);
	die();
}
