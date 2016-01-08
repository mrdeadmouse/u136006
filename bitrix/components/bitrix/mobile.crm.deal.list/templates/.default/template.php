<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

/*if (CModule::IncludeModule('pull'))
{
	CPullWatch::Add($arResult['USER_ID'], $arResult['PULL_TAG']);
}*/

$mode = $arResult['MODE'];
$isInSelectorMode = $mode === 'SELECTOR';
$contextID = $arResult['CONTEXT_ID'];
$rubric = $arResult['RUBRIC'];
$rubricPresets = isset($rubric['FILTER_PRESETS']) ? $rubric['FILTER_PRESETS'] : null;
$rubricPresetQty = $rubricPresets ? count($rubricPresets) : 0;
$showSearchPanel = $arResult['SHOW_SEARCH_PANEL'];
$enableSearch = $arResult['ENABLE_SEARCH'];

$searchTitle = !$enableSearch
	? GetMessage('M_CRM_DEAL_LIST_FILTER_NONE')
	: ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_DEAL_LIST_FILTER_CUSTOM'));

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$dispatcherData = array();

$filterPresets = $arResult['FILTER_PRESETS'];
$currentFilterPresetID = isset($arResult['GRID_FILTER_ID']) ? $arResult['GRID_FILTER_ID'] : '';

echo CCrmViewHelper::RenderDealStageSettings();
?>

<?if($showSearchPanel):?>
	<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
		<div class="crm_input_container">
			<span class="crm_lupe"></span>
			<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_LIST_SEARCH_PLACEHOLDER'))?>" />
		</div>
		<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_LIST_SEARCH_BUTTON'))?></a>
		<span class="crm_clear"></span>
	</div>
<?endif;?>
<?if(!$rubric['ENABLED']):?>
<div class="crm_toppanel">
	<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
		<span class="crm_filter_icon"></span>
		<?=htmlspecialcharsbx($searchTitle)?>
		<span class="crm_arrow_bottom"></span>
	</div>
</div>
<?endif;?>

<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<?if($rubric['ENABLED']):?>
	<div class="crm_head_title tal m0" style="padding: 10px 5px 20px;"><?=htmlspecialcharsbx($rubric['TITLE'])?><span style="font-size: 13px;color: #87949b;"> <?=GetMessage('M_CRM_DEAL_LIST_RUBRIC_LEGEND')?></span></div>
	<?if($rubricPresetQty > 0):?>
		<div class="crm_top_nav col<?=$rubricPresetQty?>">
			<ul>
				<?foreach($rubricPresets as $presetKey):
					$presetName = '';
					$isCurrent = false;
					if($presetKey === 'clear_filter'):
						$presetName = GetMessage('M_CRM_DEAL_LIST_RUBRIC_FILTER_NONE');
						$isCurrent = $currentFilterPresetID === '';
					elseif(isset($filterPresets[$presetKey])):
						$presetName = $filterPresets[$presetKey]['name'];
						$isCurrent = $currentFilterPresetID === $presetKey;
					endif;
					if($presetName === '')
						continue;
					?>

					<li class="crm-filter-preset-button-container<?=$isCurrent ? ' current' : ''?>">
						<a class="crm-filter-preset-button" href="#"><?=htmlspecialcharsbx($presetName)?></a>
						<input type="hidden" class="crm-filter-preset-data" value="<?=htmlspecialcharsbx($presetKey)?>"/>
					</li>
				<?endforeach;?>
			</ul>
			<div class="clb"></div>
		</div>
	<?endif;?>
<?endif;?>
<ul class="crm_dealings_list"><?
foreach($arResult['ITEMS'] as &$item):
	$dispatcherDataItem = array(
		'ID' => $item['~ID'],
		'TITLE' => $item['~TITLE'],
		'STAGE_ID' => $item['~STAGE_ID'],
		'PROBABILITY' => $item['~PROBABILITY'],
		'OPPORTUNITY' => $item['~OPPORTUNITY'],
		'FORMATTED_OPPORTUNITY' => $item['FORMATTED_OPPORTUNITY'],
		'CURRENCY_ID' => $item['~CURRENCY_ID'],
		'ASSIGNED_BY_ID' => $item['~ASSIGNED_BY_ID'],
		'ASSIGNED_BY_FORMATTED_NAME' => $item['~ASSIGNED_BY_FORMATTED_NAME'],
		'CONTACT_ID' => $item['~CONTACT_ID'],
		'CONTACT_FORMATTED_NAME' => $item['~CONTACT_FORMATTED_NAME'],
		'COMPANY_ID' => $item['~COMPANY_ID'],
		'COMPANY_TITLE' => $item['~COMPANY_TITLE'],
		'COMMENTS' => $item['COMMENTS'],
		'DATE_CREATE' => $item['~DATE_CREATE'],
		'DATE_MODIFY' => $item['~DATE_MODIFY'],
		'SHOW_URL' => $item['SHOW_URL'],
		'CONTACT_SHOW_URL' => $item['CONTACT_SHOW_URL'],
		'COMPANY_SHOW_URL' => $item['COMPANY_SHOW_URL'],
		'ASSIGNED_BY_SHOW_URL' => $item['ASSIGNED_BY_SHOW_URL']
	);

	$clientTitle = '';
	if($item['~CONTACT_ID'] > 0)
		$clientTitle = $item['CONTACT_FORMATTED_NAME'];
	if($item['~COMPANY_ID'] > 0 && $item['COMPANY_TITLE'] !== ''):
		if($clientTitle !== '')
			$clientTitle .= ', ';
		$clientTitle .= $item['COMPANY_TITLE'];
	endif;

	$dispatcherDataItem['CLIENT_TITLE'] = $clientTitle;

	$stageID = $item['~STAGE_ID'];
	$stageSort = CCrmDeal::GetStageSort($stageID);
	$finalStageSort = CCrmDeal::GetFinalStageSort();

	$dispatcherDataItem['IS_FINISHED'] = $stageSort >= $finalStageSort;
	$dispatcherDataItem['IS_SUCCESSED'] = $stageSort === $finalStageSort;

	//$stageClassName = $dispatcherDataItem['IS_FINISHED']
	//	? ($dispatcherDataItem['IS_SUCCESSED'] ? 'green' : 'red') : 'blue';

	$dispatcherData[] = &$dispatcherDataItem;
	unset($dispatcherDataItem);

	?><li class="crm_dealings_list_item<?=$isInSelectorMode ? ' crm_arrow' : ''?>">
		<input type="hidden" class="crm_entity_info" value="<?=$item['ID']?>" />
		<div class="crm_dealings_title"><?=$item['TITLE']?>
		<?if(isset($item['~OPPORTUNITY']) && floatval($item['~OPPORTUNITY']) > 0):?>
		<span> - <?=$item['FORMATTED_OPPORTUNITY']?></span>
		<?endif?>
		</div>
		<div class="crm_dealings_company">
			<span><?=$clientTitle?></span><?
			if(!$isInSelectorMode)
				CCrmMobileHelper::RenderProgressBar(
					array(
						'LAYOUT' => 'small',
						'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
						'ENTITY_ID' => $item['~ID'],
						//'PREFIX' => strtolower($UID).'_',
						'CURRENT_ID' => $item['~STAGE_ID']
					)
				);
		?></div>
		<div class="clb"></div>
	</li><?
endforeach;
unset($item);
if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
	?><li class="crm_dealings_list_item crm_dealings_list_item_wait"></li><?
endif;
?></ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_DEAL_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_DEAL_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_DEAL_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'DEAL',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					pullTag: '<?=CUtil::JSEscape($arResult['PULL_TAG'])?>',
					updateEventName: '<?=CUtil::JSEscape($arResult['PULL_UPDATE_CMD'])?>',
					deleteEventName: '<?=CUtil::JSEscape($arResult['PULL_DELETE_CMD'])?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'SHOW_URL_TEMPLATE' => $arParams['DEAL_SHOW_URL_TEMPLATE'],
							'CONTACT_SHOW_URL_TEMPLATE' => $arParams['CONTACT_SHOW_URL_TEMPLATE'],
							'COMPANY_SHOW_URL_TEMPLATE' => $arParams['COMPANY_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			var filterPresets = [];
			<?foreach($filterPresets as $key => &$preset):
			?>filterPresets.push(
				{
					id: '<?=CUtil::JSEscape($key)?>',
					name: '<?=CUtil::JSEscape($preset['name'])?>',
					fields: <?=CUtil::PhpToJSObject($preset['fields'])?>
				});<?
			echo "\n";
			endforeach;
			unset($preset);
			echo "\n";
			?>filterPresets.push(
				{
					id: 'clear_filter',
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_DEAL_LIST_FILTER_NONE'))?>',
					fields: {}
				});
			BX.CrmDealListView.messages =
			{
				customFilter: '<?=CUtil::JSEscape(GetMessage('M_CRM_DEAL_LIST_FILTER_CUSTOM'))?>'
			};

			var view = BX.CrmDealListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					mode: '<?=CUtil::JSEscape($mode)?>',
					contextId: '<?=CUtil::JSEscape($contextID)?>',
					dispatcher: dispatcher,
					wrapperId: '<?=CUtil::JSEscape($UID)?>',
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					filterContainerId: '<?=CUtil::JSEscape($filterContainerID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					editUrl: '<?=CUtil::JSEscape($arResult['CREATE_URL'])?>',
					reloadUrl: '<?=CUtil::JSEscape($arResult['RELOAD_URL'])?>',
					filterPresets: filterPresets,
					enablePresetButtons: <?=$rubricPresetQty > 0 ? 'true' : 'false'?>,
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>
				}
			);
			view.initializeFromExternalData();
		}
	);
</script>
