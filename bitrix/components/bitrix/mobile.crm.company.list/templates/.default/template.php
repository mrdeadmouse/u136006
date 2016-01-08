<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

/*if (CModule::IncludeModule('pull'))
{
	CPullWatch::Add($arResult['USER_ID'], $arResult['PULL_TAG']);
}*/

$enableSearch = $arResult['ENABLE_SEARCH'];
$searchTitle = !$enableSearch
	? GetMessage('M_CRM_COMPANY_LIST_FILTER_NONE')
	: ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_COMPANY_LIST_FILTER_CUSTOM'));

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$dispatcherData = array();

?>
<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_COMPANY_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_COMPANY_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>
<div class="crm_toppanel">
	<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
		<span class="crm_filter_icon"></span>
		<?=htmlspecialcharsbx($searchTitle)?>
		<span class="crm_arrow_bottom"></span>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper"><ul class="crm_company_list"><?
foreach($arResult['ITEMS'] as &$item):
	$dispatcherDataItem = CCrmMobileHelper::PrepareCompanyData($item);
	$titleHtml = isset($item['TITLE']) ? $item['TITLE'] : '';
	?><li class="crm_company_list_item crm_arrow" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($item['SHOW_URL'])?>' });">
		<input type="hidden" class="crm_entity_info" value="<?=$item['ID']?>" />
		<a class="crm_company_img">
			<span>
				<?if($dispatcherDataItem['LIST_IMAGE_URL'] !== ''):?>
				<img src="<?=htmlspecialcharsbx($dispatcherDataItem['LIST_IMAGE_URL'])?>" />
				<?endif;?>
			</span>
		</a>
		<a class="crm_company_title"><?=$titleHtml?></a>
		<div class="crm_company_company">
			<?if($item['COMPANY_TYPE_NAME'] !== ''):?>
			<?=htmlspecialcharsbx(GetMessage("M_CRM_COMPANY_LIST_TYPE"))?>: <span><?=$item['COMPANY_TYPE_NAME']?></span>
			<?endif;?>
			<?if($item['INDUSTRY_NAME'] !== ''):?>
				<?if($item['COMPANY_TYPE_NAME'] !== ''):?>
					<br/>
				<?endif;?>
				<?=htmlspecialcharsbx(GetMessage("M_CRM_COMPANY_LIST_INDUSTRY"))?>: <span><?=$item['INDUSTRY_NAME']?></span>
			<?endif;?>
		</div>
		<div class="clb"></div>
	</li><?

	$dispatcherData[] = $dispatcherDataItem;
	unset($dispatcherDataItem);
endforeach;
unset($item);
if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
	?><li class="crm_company_list_item crm_company_list_item_wait"></li><?
endif;

?></ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_COMPANY_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_COMPANY_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_COMPANY_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'COMPANY',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					/*
					pullTag: '<?//=CUtil::JSEscape($arResult['PULL_TAG'])?>',
					updateEventName: '<?//=CUtil::JSEscape($arResult['PULL_UPDATE_CMD'])?>',
					deleteEventName: '<?//=CUtil::JSEscape($arResult['PULL_DELETE_CMD'])?>',
					*/
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'COMPANY_SHOW_URL_TEMPLATE' => $arParams['COMPANY_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			var filterPresets = [];
			<?foreach($arResult['FILTER_PRESETS'] as $key => &$preset):
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
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_COMPANY_LIST_FILTER_NONE'))?>',
					fields: {}
				});
			BX.CrmCompanyListView.messages =
			{
				customFilter: '<?=CUtil::JSEscape(GetMessage('M_CRM_COMPANY_LIST_FILTER_CUSTOM'))?>',
				typeTitle: '<?= GetMessage('M_CRM_COMPANY_LIST_TYPE')?>',
				industryTitle: '<?= GetMessage('M_CRM_COMPANY_LIST_INDUSTRY')?>'
			};

			BX.CrmCompanyListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					wrapperId: '<?=CUtil::JSEscape($UID)?>',
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					filterContainerId: '<?=CUtil::JSEscape($filterContainerID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					editUrl: '<?=CUtil::JSEscape($arResult['CREATE_URL'])?>',
					reloadUrl: '<?=CUtil::JSEscape($arResult['RELOAD_URL'])?>',
					filterPresets: filterPresets,
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>
				}
			);
		}
	);
</script>
