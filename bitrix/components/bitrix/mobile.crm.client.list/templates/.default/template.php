<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$contactViewButtonID = "{$UID}_contact_view_btn";
$companyViewButtonID = "{$UID}_company_view_btn";
$prefix = htmlspecialcharsbx($UID);


$selectedEntityType = $arResult['SELECTED_ENTITY_TYPE'];
$entityData = &$arResult['ENTITY_DATA'];
$isInSigleEntityMode = count($entityData) === 1;

foreach($entityData as $typeName => &$data):
	$searchContainerID = $data['SEARCH_CONTAINER_ID'] = $data['UID'].'_search';
?><div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search"<?=$typeName !== $selectedEntityType ? ' style="display:none;"' : ''?>>
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_CLIENT_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_CLIENT_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div><?
endforeach;
unset($data);
?><div style="padding: 20px 20px 10px; border-bottom: 1px solid #b3c6d2;"><?
	if(!$isInSigleEntityMode):
	?><div class="crm_top_nav col2">
		<ul>
			<li id="<?=htmlspecialcharsbx($contactViewButtonID)?>"<?=$selectedEntityType === CCrmOwnerType::ContactName ? ' class="current"' : ''?>><a href=""><?=htmlspecialcharsbx(GetMessage('M_CRM_CLIENT_LIST_ENTITY_TYPE_CONTACT'))?></a></li>
			<li id="<?=htmlspecialcharsbx($companyViewButtonID)?>"<?=$selectedEntityType === CCrmOwnerType::CompanyName ? ' class="current"' : ''?>><a href=""><?=htmlspecialcharsbx(GetMessage('M_CRM_CLIENT_LIST_ENTITY_TYPE_COMPANY'))?></a></li>
		</ul>
		<div class="clb"></div>
	</div><?
	endif;
?></div><?
foreach($entityData as $typeName => &$data):
	$wrapperID = $data['WRAPPER_ID'] = $data['UID'];
	$data['DISPATCHER_DATA'] = array();
	?><div id="<?=htmlspecialcharsbx($wrapperID)?>"><ul class="crm_list_tel_list"<?=$typeName !== $selectedEntityType ? ' style="display:none;"' : ''?>><?
	foreach($data['ITEMS'] as &$item):
		?><li class="crm_list_tel">
			<input type="hidden" class="crm_entity_info" value="<?=$item['ID']?>" />
			<div class="crm_contactlist_tel_info crm_arrow"><?
			if($typeName === CCrmOwnerType::ContactName):
				$data['BUTTON_ID'] = $contactViewButtonID;
				$dataItem = $data['DISPATCHER_DATA'][] = CCrmMobileHelper::PrepareContactData($item);
				?><img src="<?=htmlspecialcharsbx($dataItem['LIST_IMAGE_URL'])?>" alt=""/>
				<strong><?=$item['FORMATTED_NAME']?></strong>
				<span><?=htmlspecialcharsbx($dataItem['LEGEND'])?></span><?
			elseif($typeName === CCrmOwnerType::CompanyName):
				$data['BUTTON_ID'] = $companyViewButtonID;
				$dataItem = $data['DISPATCHER_DATA'][] = CCrmMobileHelper::PrepareCompanyData($item);
				?><a href="#" onclick="return BX.eventReturnFalse();" class="crm_company_img" style="margin-left: 10px;">
					<span class="p0"><img class="fln p0" src="<?=htmlspecialcharsbx($dataItem['LIST_IMAGE_URL'])?>" alt=""></span>
				</a>
				<strong style="line-height: 41px;"><?=$item['TITLE']?></strong><?
			endif;?>
			</div>
			<div class="clb"></div>
		</li><?
	endforeach;
	unset($item);
	if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
		?><li class="crm_list_tel crm_list_tel_wait"></li><?
	endif;
?></ul></div><?
endforeach;
unset($data);
?><script type="text/javascript">
	BX.ready(
		function()
		{
			//alert('init');
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_CLIENT_LIST_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_CLIENT_LIST_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_CLIENT_LIST_LOAD_TEXT')?>'
				}
			);

			BX.CrmClientListItemButtonPanel.messages =
			{
				acceptButtonTitle: '<?=GetMessageJS('M_CRM_CLIENT_LIST_ACCEPT_BUTTON_TITLE')?>',
				cancelButtonTitle: '<?=GetMessageJS('M_CRM_CLIENT_LIST_CANCEL_BUTTON_TITLE')?>'
			};

			BX.CrmClientContactListItemView.messages =
			{
				companyButtonTitle: '<?=GetMessageJS('M_CRM_CLIENT_LIST_COMPANY_BUTTON_TITLE')?>'
			};

			BX.CrmClientCompanyListItemView.messages =
			{
				contactButtonTitle: '<?=GetMessageJS('M_CRM_CLIENT_LIST_CONTACT_BUTTON_TITLE')?>'
			};

			BX.CrmInnerClientListView.messages =
			{
				noContacts: '<?=GetMessageJS('M_CRM_CLIENT_LIST_NO_CONTACTS')?>',
				noCompanies: '<?=GetMessageJS('M_CRM_CLIENT_LIST_NO_COMPANIES')?>'
			};

			BX.CrmClientCreatorManager.messages =
			{
				"CONTACT": "<?=GetMessageJS('M_CRM_CLIENT_LIST_ADD_CONTACT')?>",
				"COMPANY": "<?=GetMessageJS('M_CRM_CLIENT_LIST_ADD_COMPANY')?>"
			};

			function createView(uid, contextId, typeName, dispatcherData, wrapperId, searchContainerId, nextPageUrl, searchPageUrl, reloadUrl, enableQuickSelect, buttonId)
			{
				var listManager = BX.CrmClientListManager.getCurrent();

				var dispatcher = BX.CrmEntityDispatcher.create(
					uid,
					{
						typeName: typeName,
						data: dispatcherData
					}
				);
				listManager.registerDispatcher(dispatcher);

				if(searchPageUrl !== "")
				{
					listManager.registerSearchSettings({ typeName: typeName, url: searchPageUrl });
				}

				var view = BX.CrmClientListView.create(
					uid,
					{
						entityType: typeName,
						dispatcher: dispatcher,
						wrapperId: wrapperId,
						contextId: contextId,
						searchContainerId: searchContainerId,
						nextPageUrl: nextPageUrl,
						searchPageUrl: searchPageUrl,
						reloadUrl: reloadUrl,
						enableQuickSelect: enableQuickSelect
					}
				);

				var button = BX(buttonId);
				if(button)
				{
					listManager.registerView(view);
					listManager.registerButton(button, view.getId())
				}
			}<?

			$enableCreation = $arResult['ENABLE_CREATION'];
			$typeMap = array();
			foreach($entityData as $typeName => &$data):
				if($enableCreation):
					$typeMap[$typeName] = array(
						'createUrl'=> $data['CREATE_URL'],
						'viewId' => $data['UID']
					);
				endif;

			?>
			createView(
					'<?=CUtil::JSEscape($data['UID'])?>',
					'<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					'<?=CUtil::JSEscape($typeName)?>',
					<?=CUtil::PhpToJSObject($data['DISPATCHER_DATA'])?>,
					'<?=CUtil::JSEscape($data['WRAPPER_ID'])?>',
					'<?=CUtil::JSEscape($data['SEARCH_CONTAINER_ID'])?>',
					'<?=CUtil::JSEscape($data['NAVIGATION']['NEXT_PAGE_URL'])?>',
					'<?=CUtil::JSEscape($data['NAVIGATION']['SEARCH_PAGE_URL'])?>',
					'<?=CUtil::JSEscape($data['RELOAD_URL'])?>',
					<?=$isInSigleEntityMode ? "true" : "false"?>,
					'<?=CUtil::JSEscape($data['BUTTON_ID'])?>'
				);<?
			endforeach;
			unset($data);

			if(!empty($typeMap)):
			?> BX.CrmClientCreatorManager.create(
				'<?=CUtil::JSEscape($UID)?>',
				{
					typeMap: <?=CUtil::PhpToJSObject($typeMap)?>
				}
			);
			<?endif;
		?>}
	);
</script>
