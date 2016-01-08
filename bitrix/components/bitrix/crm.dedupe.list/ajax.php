<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'GET_DUPLICATE_ENTITIES' - get duplicates of specicied types (ENTITY_TYPE_NAME, INDEX_TYPE_NAME) by specified matches (INDEX_MATCHES) in duplicate index
 * 'GET_DUPLICATE_ENTITY_MULTI_FIELDS' - get multifields of specified entity (ENTITY_TYPE_NAME, ENTITY_ID)
 * 'REBUILD_DEDUPE_INDEX' - rebuild duplicate index of specified types (ENTITY_TYPE_NAME, INDEX_TYPE_NAMES)
 * 'MERGE_ENTITIES' - merge entities of specified type (ENTITY_TYPE_NAME)
 */

use Bitrix\Crm\Integrity;
use Bitrix\Crm\Merger;
global $APPLICATION;

if(!function_exists('__CrmDedupeListEndResonse'))
{
	function __CrmDedupeListEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}
if(!function_exists('__CrmDedupeListErrorText'))
{
	function __CrmDedupeListErrorText(Merger\EntityMergerException $e)
	{
		__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

		$entityTypeID = $e->getEntityTypeID();
		$entityID = $e->getEntityID();
		$code = $e->getCode();

		if($code === Merger\EntityMergerException::GENERAL)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_GENERAL_ERROR');
		}
		elseif($code === Merger\EntityMergerException::NOT_FOUND)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_NOT_FOUND', array('#ID#' => $entityID));
		}

		$title = CCrmOwnerType::GetCaption($entityTypeID, $entityID, false);
		if(strlen($title) > 20)
		{
			$title = substr($title, 0, 17).'...';
		}

		if($code === Merger\EntityMergerException::READ_DENIED)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_READ_DENIED',
				array('#TITLE#' => $title, '#ID#' => $entityID));
		}
		elseif($code === Merger\EntityMergerException::UPDATE_DENIED)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_UPDATE_DENIED',
				array('#TITLE#' => $title, '#ID#' => $entityID));
		}
		elseif($code === Merger\EntityMergerException::DELETE_DENIED)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_DELETE_DENIED',
				array('#TITLE#' => $title, '#ID#' => $entityID));
		}
		elseif($code === Merger\EntityMergerException::UPDATE_FAILED)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_UPDATE_FAILED',
				array('#TITLE#' => $title, '#ID#' => $entityID));
		}
		elseif($code === Merger\EntityMergerException::DELETE_FAILED)
		{
			return GetMessage('CRM_DEDUPE_LIST_MERGE_ERROR_DELETE_FAILED',
				array('#TITLE#' => $title, '#ID#' => $entityID));
		}

		return $e->getMessage();
	}
}

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmDedupeListEndResonse(array('ERROR' => 'Invalid request.'));
}
CUtil::JSPostUnescape();
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';

if (!CModule::IncludeModule('crm'))
{
	__CrmDedupeListEndResonse(array('ERROR' => 'Could not load CRM module.'));
}

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized() || !check_bitrix_sessid())
{
	__CrmDedupeListEndResonse(array('ERROR' => 'Access denied.'));
}
$currentUserID = (int)$currentUser->GetID();
$currentUserPermissions = CCrmPerms::GetUserPermissions($currentUserID);

if ($action === 'GET_DUPLICATE_ENTITIES')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$entityTypeID = isset($_POST['ENTITY_TYPE_NAME'])
		? CCrmOwnerType::ResolveID($_POST['ENTITY_TYPE_NAME']) : CCrmOwnerType::Undefined;
	$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

	if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, 0))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Access denied.'));
	}

	$rootEntityID = isset($_POST['ROOT_ENTITY_ID']) ?  (int)$_POST['ROOT_ENTITY_ID'] : 0;
	$typeID = isset($_POST['INDEX_TYPE_NAME']) ?  Integrity\DuplicateIndexType::resolveID($_POST['INDEX_TYPE_NAME']) : Integrity\DuplicateIndexType::UNDEFINED;
	$layoutName = isset($_POST['LAYOUT_NAME']) ?  $_POST['LAYOUT_NAME'] : '';
	$layoutID = CCrmOwnerType::ResolveID($layoutName);

	$matches = isset($_POST['INDEX_MATCHES']) && is_array($_POST['INDEX_MATCHES']) ? $_POST['INDEX_MATCHES'] : array();
	if(empty($matches))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Matches is not defined.'));
	}

	$enableRanking = isset($_POST['ENABLE_RANKING']) && strtoupper($_POST['ENABLE_RANKING']) === 'Y';
	$enablePermissionCheck = !CCrmPerms::IsAdmin($currentUserID);
	$entityInfos = array();

	$criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);

	$list = new Integrity\DuplicateList($typeID, $entityTypeID, $currentUserID, $enablePermissionCheck);
	if($list->isJunk($rootEntityID))
	{
		$result = array(
			'INDEX_TYPE_NAME' => Integrity\DuplicateIndexType::resolveName($typeID),
			'ENTITY_TYPE_NAME' => $entityTypeName,
			'ENTITY_INFOS' => array(),
			'TEXT_TOTALS' => GetMessage("CRM_DEDUPE_LIST_JUNK")
		);
		__CrmDedupeListEndResonse($result);
	}

	$dup = $criterion->createDuplicate($entityTypeID, $rootEntityID, $currentUserID, $enablePermissionCheck, $enableRanking, 50);
	if($dup)
	{
		$entities = $dup->getEntitiesByType($entityTypeID);
		foreach($entities as $entity)
		{
			$entityID = $entity->getEntityID();
			$info = array('ID' => $entityID);
			$entityCriterion = $entity->getCriterion();
			if($entityCriterion !== null)
			{
				$info['INDEX_MATCHES'] = $entityCriterion->getMatches();
			}
			if(!$enablePermissionCheck)
			{
				$info['CAN_UPDATE'] = $info['CAN_DELETE'] = true;
			}
			else
			{
				$info['CAN_UPDATE'] = \CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $currentUserPermissions);
				$info['CAN_DELETE'] = \CCrmAuthorizationHelper::CheckDeletePermission($entityTypeName, $entityID, $currentUserPermissions);
			}
			$entityInfos[$entityID] = &$info;
			unset($info);
		}

		if(empty($entityInfos))
		{
			$result = array(
				'INDEX_TYPE_NAME' => Integrity\DuplicateIndexType::resolveName($typeID),
				'ENTITY_TYPE_NAME' => $entityTypeName,
				'ENTITY_INFOS' => array(),
				'TEXT_TOTALS' => GetMessage("CRM_DEDUPE_LIST_JUNK")
			);
			__CrmDedupeListEndResonse($result);
		}

		$entityInfoOptions = array(
			'ENABLE_EDIT_URL' => false,
			'ENABLE_RESPONSIBLE' => true,
			'ENABLE_RESPONSIBLE_PHOTO' => false
		);
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			$entityInfoOptions[$layoutID === CCrmOwnerType::Company ? 'TREAT_AS_COMPANY' : 'TREAT_AS_CONTACT'] = true;
		}

		\CCrmOwnerType::PrepareEntityInfoBatch($entityTypeID, $entityInfos, $enablePermissionCheck, $entityInfoOptions);
		\CCrmFieldMulti::PrepareEntityInfoBatch('PHONE', $entityTypeName, $entityInfos, array('ENABLE_NORMALIZATION' => true));
		\CCrmFieldMulti::PrepareEntityInfoBatch('EMAIL', $entityTypeName, $entityInfos);

		foreach($entityInfos as &$entityInfo)
		{
			if(isset($entityInfo['IMAGE_FILE_ID']))
			{
				if($entityInfo['IMAGE_FILE_ID'] > 0)
				{
					$imageInfo = CFile::ResizeImageGet(
						$entityInfo['IMAGE_FILE_ID'],
						array('width' => 34, 'height' => 50),
						BX_RESIZE_IMAGE_EXACT
					);
					$entityInfo['IMAGE_URL'] = $imageInfo['src'];
				}
				unset($entityInfo['IMAGE_FILE_ID']);
			}
		}
		unset($entityInfo);
	}

	$totalsText = $criterion->getTextTotals(count($entityInfos), 50);
	$result = array(
		'INDEX_TYPE_NAME' => Integrity\DuplicateIndexType::resolveName($typeID),
		'ENTITY_TYPE_NAME' => $entityTypeName,
		'ENTITY_INFOS' => array_values($entityInfos),
		'TEXT_TOTALS' => $totalsText
	);
	__CrmDedupeListEndResonse($result);
}
elseif ($action === 'GET_DUPLICATE_ENTITY_MULTI_FIELDS')
{
	$entityTypeID = isset($_POST['ENTITY_TYPE_NAME'])
		? CCrmOwnerType::ResolveID($_POST['ENTITY_TYPE_NAME']) : CCrmOwnerType::Undefined;
	$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;

	if(!CCrmPerms::IsAdmin($currentUserID) &&
		!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Access denied.'));
	}

	$result = Integrity\DuplicateCommunicationCriterion::getRegisteredCodes(
		$entityTypeID,
		$entityID,
		false,
		$currentUserID,
		50
	);

	__CrmDedupeListEndResonse(array('MULTI_FIELDS' => $result));
}
elseif($action === 'REBUILD_DEDUPE_INDEX')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$contextID = isset($params['CONTEXT_ID']) ? $params['CONTEXT_ID'] : '';
	if($contextID === '')
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Context ID is not defined.'));
	}

	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if(!CCrmOwnerType::IsDefined($entityTypeID))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Entity type name is not defined or invalid.'));
	}

	$typeNames = isset($params['INDEX_TYPE_NAMES']) && is_array($params['INDEX_TYPE_NAMES']) ? $params['INDEX_TYPE_NAMES'] : array();
	$typeIDs = array();
	foreach($typeNames as $typeName)
	{
		$typeID = Integrity\DuplicateIndexType::resolveID($typeName);
		if($typeID !== Integrity\DuplicateIndexType::UNDEFINED)
		{
			$typeIDs[] = $typeID;
		}
	}
	if(empty($typeIDs))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Inndex type names are not defined or invalid.'));
	}

	$enablePermissionCheck = !CCrmPerms::IsAdmin($currentUserID);

	$progressData = CUserOptions::GetOption('crm', '~dedupe_index_rebuild_progress', array(), $currentUserID);
	if(!empty($progressData)
		&& (!isset($progressData['CONTEXT_ID']) || $progressData['CONTEXT_ID'] !== $contextID))
	{
		$progressData = array();
	}

	$isStart = empty($progressData);
	if($isStart)
	{
		$progressData['CONTEXT_ID'] = $contextID;

		$effectiveTypeIDs = $progressData['TYPE_IDS'] = $typeIDs;
		$currentTypeIndex = $progressData['CURRENT_TYPE_INDEX'] = 0;
		$processedItemQty = $progressData['PROCESSED_ITEMS'] = 0;
	}
	else
	{
		$effectiveTypeIDs = isset($progressData['TYPE_IDS'])
			? $progressData['TYPE_IDS'] : null;
		if(!is_array($effectiveTypeIDs) || empty($effectiveTypeIDs))
		{
			$effectiveTypeIDs = $typeIDs;
		}
		$currentTypeIndex = isset($progressData['CURRENT_TYPE_INDEX'])
			? (int)$progressData['CURRENT_TYPE_INDEX'] : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)$progressData['PROCESSED_ITEMS'] : 0;
	}

	$effectiveTypeQty = count($effectiveTypeIDs);
	if($currentTypeIndex >= $effectiveTypeQty)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Invalid current type index.'));
	}

	$builder = Integrity\DuplicateManager::createIndexBuilder(
		$effectiveTypeIDs[$currentTypeIndex],
		$entityTypeID,
		$currentUserID,
		$enablePermissionCheck
	);

	$buildData = isset($progressData['BUILD_DATA']) ? $progressData['BUILD_DATA'] : array();

	$offset = isset($buildData['OFFSET']) ? (int)$buildData['OFFSET'] : 0;
	if($offset === 0)
	{
		$builder->remove();
	}

	$limit = isset($buildData['LIMIT']) ? (int)$buildData['LIMIT'] : 0;
	if($limit === 0)
	{
		$buildData['LIMIT'] = 10;
	}

	$isInProgress = $builder->build($buildData);
	if($isInProgress)
	{
		$processedItemQty += isset($buildData['EFFECTIVE_ITEM_COUNT']) ? (int)$buildData['EFFECTIVE_ITEM_COUNT'] : 0;
		$isFinal = false;

		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['BUILD_DATA'] = $buildData;
	}
	else
	{
		$isFinal = $currentTypeIndex === ($effectiveTypeQty - 1);
		if(!$isFinal)
		{
			$progressData['CURRENT_TYPE_INDEX'] = ++$currentTypeIndex;
			unset($progressData['BUILD_DATA']);
		}
	}

	if(!$isFinal)
	{
		CUserOptions::SetOption('crm', '~dedupe_index_rebuild_progress', $progressData, false, $currentUserID);
		__CrmDedupeListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'SUMMARY' => GetMessage(
					'CRM_DEDUPE_LIST_REBUILD_INDEX_PROGRESS_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
	else
	{
		CUserOptions::DeleteOption('crm', '~dedupe_index_rebuild_progress', false, $currentUserID);
		__CrmDedupeListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'SUMMARY' => GetMessage(
					'CRM_DEDUPE_LIST_REBUILD_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif($action === 'GET_MERGE_COLLISIONS')
{
	$entityTypeName = isset($_POST['ENTITY_TYPE_NAME']) ? strtoupper($_POST['ENTITY_TYPE_NAME']) : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead
		&& $entityTypeID !== CCrmOwnerType::Contact
		&& $entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmDedupeListEndResonse(array('ERROR' => "Entity type '{$entityTypeName}' is not supported in current context."));
	}

	$seedEntityID = isset($_POST['SEED_ENTITY_ID']) ? (int)$_POST['SEED_ENTITY_ID'] : 0;
	if($seedEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Seed entity ID is not is not specified.'));
	}

	$targEntityID = isset($_POST['TARG_ENTITY_ID']) ? (int)$_POST['TARG_ENTITY_ID'] : 0;
	if($targEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Target entity ID is not is not specified.'));
	}

	$result = array(
		'ENTITY_TYPE_NAME' => $entityTypeName,
		'SEED_ENTITY_ID' => $seedEntityID,
		'TARG_ENTITY_ID' => $targEntityID,
		'COLLISION_TYPES' => array()
	);

	$merger = Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);
	try
	{
		$collisions = $merger->getMergeCollisions($seedEntityID, $targEntityID);
		foreach($collisions as $collision)
		{
			/* @var Merger\EntityMergeCollision $collision*/
			$result['COLLISION_TYPES'][] = $collision->getTypeName();
		}
	}
	catch(Merger\EntityMergerException $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => __CrmDedupeListErrorText($e)));
	}
	catch(Exception $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => $e->getMessage()));
	}

	__CrmDedupeListEndResonse($result);
}
elseif($action === 'MERGE')
{
	$entityTypeName = isset($_POST['ENTITY_TYPE_NAME']) ? strtoupper($_POST['ENTITY_TYPE_NAME']) : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead
		&& $entityTypeID !== CCrmOwnerType::Contact
		&& $entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmDedupeListEndResonse(array('ERROR' => "Entity type '{$entityTypeName}' is not supported in current context."));
	}

	$seedEntityID = isset($_POST['SEED_ENTITY_ID']) ? (int)$_POST['SEED_ENTITY_ID'] : 0;
	if($seedEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Seed entity ID is not is not specified.'));
	}

	$targEntityID = isset($_POST['TARG_ENTITY_ID']) ? (int)$_POST['TARG_ENTITY_ID'] : 0;
	if($targEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Target entity ID is not is not specified.'));
	}

	$typeID = isset($_POST['INDEX_TYPE_NAME']) ?  Integrity\DuplicateIndexType::resolveID($_POST['INDEX_TYPE_NAME']) : Integrity\DuplicateIndexType::UNDEFINED;
	$matches = isset($_POST['INDEX_MATCHES']) && is_array($_POST['INDEX_MATCHES']) ? $_POST['INDEX_MATCHES'] : array();
	$criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);

	$enablePermissionCheck = !CCrmPerms::IsAdmin($currentUserID);
	$merger = Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);
	try
	{
		$merger->merge($seedEntityID, $targEntityID, $criterion);
	}
	catch(Merger\EntityMergerException $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => __CrmDedupeListErrorText($e)));
	}
	catch(Exception $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => $e->getMessage()));
	}

	$totalsText = $criterion->getTextTotals(
		$criterion->getActualCount($entityTypeID, $targEntityID, $currentUserID, $enablePermissionCheck, 51),
		50
	);

	__CrmDedupeListEndResonse(
		array(
			'SEED_ENTITY_ID' => $seedEntityID,
			'TARG_ENTITY_ID' => $targEntityID,
			'TEXT_TOTALS' => $totalsText,
		)
	);
}
elseif($action === 'REGISTER_MISMATCH')
{
	$entityTypeName = isset($_POST['ENTITY_TYPE_NAME']) ? strtoupper($_POST['ENTITY_TYPE_NAME']) : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead
		&& $entityTypeID !== CCrmOwnerType::Contact
		&& $entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmDedupeListEndResonse(array('ERROR' => "Entity type '{$entityTypeName}' is not supported in current context."));
	}

	$leftEntityID = isset($_POST['LEFT_ENTITY_ID']) ? (int)$_POST['LEFT_ENTITY_ID'] : 0;
	if($leftEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Left entity ID is not is not specified.'));
	}

	$rightEntityID = isset($_POST['RIGHT_ENTITY_ID']) ? (int)$_POST['RIGHT_ENTITY_ID'] : 0;
	if($rightEntityID <= 0)
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Right entity ID is not is not specified.'));
	}

	$typeID = isset($_POST['INDEX_TYPE_NAME']) ?  Integrity\DuplicateIndexType::resolveID($_POST['INDEX_TYPE_NAME']) : Integrity\DuplicateIndexType::UNDEFINED;
	if(!Integrity\DuplicateIndexType::isDefined($typeID))
	{
		__CrmDedupeListEndResonse(array('ERROR' => 'Index type ID is not specified or invalid.'));
	}

	$enablePermissionCheck = !CCrmPerms::IsAdmin($currentUserID);
	$merger = Merger\EntityMerger::create($entityTypeID, $currentUserID, $enablePermissionCheck);

	$leftEntityMatches = isset($_POST['LEFT_ENTITY_INDEX_MATCHES']) && is_array($_POST['LEFT_ENTITY_INDEX_MATCHES']) ? $_POST['LEFT_ENTITY_INDEX_MATCHES'] : array();
	$leftEntityCriterion = Integrity\DuplicateManager::createCriterion($typeID, $leftEntityMatches);

	$rightEntityMatches = isset($_POST['RIGHT_ENTITY_INDEX_MATCHES']) && is_array($_POST['RIGHT_ENTITY_INDEX_MATCHES']) ? $_POST['RIGHT_ENTITY_INDEX_MATCHES'] : array();
	if(empty($rightEntityMatches))
	{
		$rightEntityMatches = $leftEntityMatches;
	}
	$rightEntityCriterion = Integrity\DuplicateManager::createCriterion($typeID, $rightEntityMatches);
	try
	{
		$merger->registerCriterionMismatch($rightEntityCriterion, $leftEntityID, $rightEntityID);
		$builder = Integrity\DuplicateManager::createIndexBuilder($typeID, $entityTypeID, $currentUserID, $enablePermissionCheck);
		$builder->processMismatchRegistration($leftEntityCriterion, $leftEntityID);

	}
	catch(Merger\EntityMergerException $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => __CrmDedupeListErrorText($e)));
	}
	catch(Exception $e)
	{
		__CrmDedupeListEndResonse(array('ERROR' => $e->getMessage()));
	}

	$totalsText = $leftEntityCriterion->getTextTotals(
		$leftEntityCriterion->getActualCount($entityTypeID, $leftEntityID, $currentUserID, $enablePermissionCheck, 51),
		50
	);

	__CrmDedupeListEndResonse(
		array(
			'LEFT_ENTITY_ID' => $leftEntityID,
			'RIGHT_ENTITY_ID' => $rightEntityID,
			'TEXT_TOTALS' => $totalsText,
		)
	);
}


