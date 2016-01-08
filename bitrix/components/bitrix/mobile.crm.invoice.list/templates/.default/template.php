<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$contextID = $arResult['CONTEXT_ID'];
$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$dispatcherData = array();

$showSearchPanel = $arResult['SHOW_SEARCH_PANEL'];
$enableSearch = $arResult['ENABLE_SEARCH'];

$searchTitle = !$enableSearch
	? GetMessage('M_CRM_INVOICE_LIST_FILTER_NONE')
	: ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_INVOICE_LIST_FILTER_CUSTOM'));

$filterPresets = $arResult['FILTER_PRESETS'];
$currentFilterPresetID = isset($arResult['GRID_FILTER_ID']) ? $arResult['GRID_FILTER_ID'] : '';

echo CCrmViewHelper::RenderInvoiceStatusSettings();
?><div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>

<div class="crm_toppanel">
	<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
		<span class="crm_filter_icon"></span>
		<?=htmlspecialcharsbx($searchTitle)?>
		<span class="crm_arrow_bottom"></span>
	</div>
</div>

<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<ul class="crm_dealings_list"><?
		$numberTemplate = GetMessage('M_CRM_INVOICE_LIST_NUMBER');
		foreach($arResult['ITEMS'] as &$item):
			$dispatcherDataItem = CCrmMobileHelper::PrepareInvoiceData($item);
			?><li class="crm_dealings_list_item" data-entity-id="<?=$item['ID']?>">
				<div class="crm_dealings_title">
					<span class="crm_numorder"><?=htmlspecialcharsbx(str_replace('#NUM#', $item['ACCOUNT_NUMBER'], $numberTemplate))?> </span>
					<?=$item['ORDER_TOPIC']?>
					<span> - <?=$item['FORMATTED_PRICE']?></span>
				</div>
				<div class="crm_dealings_company">
					<span>
						<span style="color: #6f7272;"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_LIST_CLIENT_CAPTION'))?> </span>
						<?=$item['CONTACT_FULL_NAME'] !== '' ? $item['CONTACT_FULL_NAME'] : $item['COMPANY_TITLE']?>
					</span><?
					if($item['DEAL_TITLE'] !== ''):
					?><span>
						<span style="color:#6f7272;"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_LIST_DEAL_CAPTION'))?> </span>
						<?=$item['DEAL_TITLE']?>
					</span><?
					endif;
					CCrmMobileHelper::RenderProgressBar(
						array(
							'LAYOUT' => 'small',
							'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
							'ENTITY_ID' => $item['~ID'],
							'CURRENT_ID' => $item['~STATUS_ID']
						)
					);
				?></div>
				<div class="clb"></div>
			</li><?
			$dispatcherData[] = $dispatcherDataItem;
			unset($dispatcherDataItem);
		endforeach;
		unset($item);
		if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
			?><li class="crm_dealings_list_item crm_dealings_list_item_wait"></li><?
		endif;
	?></ul>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_INVOICE_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_INVOICE_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_INVOICE_LIST_LOAD_TEXT')?>'
				}
			);

			var uid = '<?=CUtil::JSEscape($UID)?>';
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: 'INVOICE',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
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
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_INVOICE_LIST_FILTER_NONE'))?>',
					fields: {}
				});

			BX.CrmInvoiceListView.messages =
			{
				customFilter: '<?=CUtil::JSEscape(GetMessage('M_CRM_INVOICE_LIST_FILTER_CUSTOM'))?>',
				numberTemplate: '<?=CUtil::JSEscape(GetMessage('M_CRM_INVOICE_LIST_NUMBER'))?>',
				clientCaption: '<?=CUtil::JSEscape(GetMessage('M_CRM_INVOICE_LIST_CLIENT_CAPTION'))?>',
				dealCaption: '<?=CUtil::JSEscape(GetMessage('M_CRM_INVOICE_LIST_DEAL_CAPTION'))?>'
			};

			var view = BX.CrmInvoiceListView.create(
				uid,
				{
					contextId: '<?=CUtil::JSEscape($contextID)?>',
					dispatcher: dispatcher,
					wrapperId: uid,
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					filterContainerId: '<?=CUtil::JSEscape($filterContainerID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					editUrl: '<?=CUtil::JSEscape($arResult['CREATE_URL'])?>',
					reloadUrl: '<?=CUtil::JSEscape($arResult['RELOAD_URL'])?>',
					filterPresets: filterPresets,
					enablePresetButtons: true,
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>
				}
			);
			//view.initializeFromExternalData();
		}
	);
</script>


