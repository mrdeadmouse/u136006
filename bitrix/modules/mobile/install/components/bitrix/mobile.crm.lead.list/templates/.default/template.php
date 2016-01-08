<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH.'/crm_mobile.js').'"></script>',true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

/*
if (CModule::IncludeModule('pull'))
{
	CPullWatch::Add($arResult['USER_ID'], $arResult['PULL_TAG']);
}
*/

$enableSearch = $arResult['ENABLE_SEARCH'];
$searchTitle = !$enableSearch
	? GetMessage('M_CRM_LEAD_LIST_FILTER_NONE')
	: ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_LEAD_LIST_FILTER_CUSTOM'));

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$dispatcherData = array();

echo CCrmViewHelper::RenderLeadStatusSettings();
?>
<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>
<div class="crm_toppanel">
	<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
		<span class="crm_filter_icon"></span>
		<?=htmlspecialcharsbx($searchTitle)?>
		<span class="crm_arrow_bottom"></span>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper"><ul class="crm_dealings_list"><?
foreach($arResult['ITEMS'] as &$item):
	$dispatcherData[] = CCrmMobileHelper::PrepareLeadData($item);

	$legendHtml = '';
	$name = isset($item['FORMATTED_NAME']) ? $item['FORMATTED_NAME'] : '';
	$companyTitle = isset($item['COMPANY_TITLE']) ? $item['COMPANY_TITLE'] : '';
	if($name !== '' && $companyTitle !== '')
	{
		$legendHtml = '<strong class="fwn">'.$name.', </strong><strong class="fwn" style="color:#7d7d7d;">'.$companyTitle.'</strong>';
	}
	elseif($name !== '')
	{
		$legendHtml = '<strong class="fwn">'.$name.'</strong>';
	}
	elseif($companyTitle !== '')
	{
		$legendHtml = '<strong class="fwn" style="color:#7d7d7d;">'.$companyTitle.'</strong>';
	}
	?><li class="crm_dealings_list_item" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($item['SHOW_URL'])?>' });">
		<input type="hidden" class="crm_entity_info" value="<?=$item['ID']?>" />
		<div class="crm_dealings_title"><?=$item['TITLE']?>
		<?if(isset($item['~OPPORTUNITY']) && floatval($item['~OPPORTUNITY']) > 0):?>
		<span> - <?=$item['FORMATTED_OPPORTUNITY']?></span>
		<?endif?>
		</div>
		<div class="crm_dealings_company">
			<span><?=$legendHtml?></span>
			<?CCrmMobileHelper::RenderProgressBar(
				array(
					'LAYOUT' => 'small',
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $item['~ID'],
					//'PREFIX' => strtolower($UID).'_',
					'CURRENT_ID' => $item['~STATUS_ID']
				)
			);?>
		</div>
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
					pullText: '<?= GetMessage('M_CRM_LEAD_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_LEAD_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_LEAD_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'LEAD',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					/*
					pullTag: '<?//=CUtil::JSEscape($arResult['PULL_TAG'])?>',
					updateEventName: '<?//=CUtil::JSEscape($arResult['PULL_UPDATE_CMD'])?>',
					deleteEventName: '<?//=CUtil::JSEscape($arResult['PULL_DELETE_CMD'])?>',
					*/
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'SHOW_URL_TEMPLATE' => $arParams['LEAD_SHOW_URL_TEMPLATE'],
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
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_LEAD_LIST_FILTER_NONE'))?>',
					fields: {}
				});
			BX.CrmLeadListView.messages =
			{
				customFilter: '<?=CUtil::JSEscape(GetMessage('M_CRM_LEAD_LIST_FILTER_CUSTOM'))?>'
			};

			BX.CrmLeadListView.create(
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
