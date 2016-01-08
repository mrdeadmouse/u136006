<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

/*if (CModule::IncludeModule('pull'))
{
	CPullWatch::Add($arResult['USER_ID'], $arResult['PULL_TAG']);
}*/

$rubric = $arResult['RUBRIC'];
$showSearchPanel = $arResult['SHOW_SEARCH_PANEL'];
$enableSearch = $arResult['ENABLE_SEARCH'];
$searchTitle = !$enableSearch
	? GetMessage('M_CRM_CONTACT_LIST_FILTER_NONE')
	: ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_CONTACT_LIST_FILTER_CUSTOM'));

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$dispatcherData = array();
?>

<?if($showSearchPanel):?>
	<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
		<div class="crm_input_container">
			<span class="crm_lupe"></span>
			<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_LIST_SEARCH_PLACEHOLDER'))?>" />
		</div>
		<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_LIST_SEARCH_BUTTON'))?></a>
		<span class="crm_clear"></span>
	</div>
<?endif;?>

<?if($rubric['ENABLED']):?>
	<div class="crm_contacts_title"><?=htmlspecialcharsbx($rubric['TITLE'])?><span style="font-size: 13px;color: #87949b;"> <?=GetMessage('M_CRM_CONTACT_LIST_RUBRIC_LEGEND')?></span></div>
<?else:?>
	<div class="crm_toppanel">
		<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
			<span class="crm_filter_icon"></span>
			<?=htmlspecialcharsbx($searchTitle)?>
			<span class="crm_arrow_bottom"></span>
		</div>
	</div>
<?endif;?>

<div id="<?=htmlspecialcharsbx($UID)?>"><ul class="crm_contact_list"><?
$currentClassifier = null;
foreach($arResult['ITEMS'] as &$item):
	$dispatcherDataItem = CCrmMobileHelper::PrepareContactData($item);

	$classifier = $item['CLASSIFIER'];
	if($currentClassifier === null || $currentClassifier !== $classifier):
		if($currentClassifier !== null):
			?></ul></li><?
		endif;

		$currentClassifier = $classifier;

		?><li class="crm_contact_list_separator">
			<input type="hidden" class="crm_entity_classifier" value="<?=$classifier?>" />
			<span><?=$classifier?></span>
			<ul class="crm_contact_list_people_list"><?
	endif;

	?><li class="crm_contact_list_people" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($item['SHOW_URL'])?>' });">
		<input type="hidden" class="crm_entity_info" value="<?=$item['ID']?>" />
		<div class="crm_contactlist_info">
			<img src="<?=htmlspecialcharsbx($dispatcherDataItem['LIST_IMAGE_URL'])?>" />
			<strong><?=$item['FORMATTED_NAME']?></strong>
			<span><?=htmlspecialcharsbx($dispatcherDataItem['LEGEND'])?></span>
		</div>
	</li><?

	$dispatcherData[] = $dispatcherDataItem;
	unset($dispatcherDataItem);
endforeach;
unset($item);

if($currentClassifier !== null):
	?></ul></li><?
endif;

if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
	?><li class="crm_contact_list_people crm_contact_list_people_wait"></li><?
endif;
?></ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_CONTACT_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_CONTACT_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_CONTACT_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'CONTACT',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					/*
					pullTag: '<?//=CUtil::JSEscape($arResult['PULL_TAG'])?>',
					updateEventName: '<?//=CUtil::JSEscape($arResult['PULL_UPDATE_CMD'])?>',
					deleteEventName: '<?//=CUtil::JSEscape($arResult['PULL_DELETE_CMD'])?>',
					*/
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'CONTACT_SHOW_URL_TEMPLATE' => $arParams['CONTACT_SHOW_URL_TEMPLATE'],
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
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_CONTACT_LIST_FILTER_NONE'))?>',
					fields: {}
				});
			BX.CrmContactListView.messages =
			{
				customFilter: '<?=CUtil::JSEscape(GetMessage('M_CRM_CONTACT_LIST_FILTER_CUSTOM'))?>',
				menuAdd: '<?=GetMessageJS('M_CRM_CONTACT_LIST_ADD_ITEM')?>'
			};

			BX.CrmContactListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'CONTACT',
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


