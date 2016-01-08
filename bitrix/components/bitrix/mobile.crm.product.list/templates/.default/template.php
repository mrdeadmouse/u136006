<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$showSearchPanel = $arResult['SHOW_SEARCH_PANEL'];
$searchContainerID = $UID.'_search';

$productWrapperID = $UID.'_product';
$products = $arResult['PRODUCTS'];

$productSectionWrapperID = $UID.'_section';
$sections = $arResult['SECTIONS'];

$activeTab = $arResult['ACTIVE_TAB'];
$listMode =  $arResult['LIST_MODE'];
$activeSection = $arResult['ACTIVE_SECTION'];
$activeSectionContainerID = $UID.'_active_section';
$showSectionButtonID = $UID.'_show_section';

$viewSelectorContainerID = $UID.'_view_selector';
$productViewSelectorID = $UID.'_product_view';
$productSectionViewSelectorID = $UID.'_product_section_view';
$productLegendContainerID = $UID.'_product_view_legend';

$productDispatcherData = array();
$productSectionDispatcherData = array();
?>
<?if($showSearchPanel):?>
<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>
<?endif;?>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div id="<?=htmlspecialcharsbx($viewSelectorContainerID)?>" class="crm_top_nav col2">
		<ul>
			<li<?=$activeTab === 'PRODUCT' ? ' class="current"' : ''?>><a id="<?=htmlspecialcharsbx($productViewSelectorID)?>" href="#"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_TAB_PRODUCT'))?></a></li>
			<li<?=$activeTab === 'SECTION' ? ' class="current"' : ''?>><a id="<?=htmlspecialcharsbx($productSectionViewSelectorID)?>" href="#"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_TAB_SECTION'))?></a></li>
		</ul>
		<div class="clb"></div>
	</div>

	<div id="<?=htmlspecialcharsbx($productWrapperID)?>" style="display:<?=$activeTab === 'PRODUCT' ? '' : 'none';?>">
	<?if($listMode === 'SELECTOR'):?>
		<div id="<?=htmlspecialcharsbx($productLegendContainerID)?>" class="tac" style="margin-bottom: 10px; color: #32506b; text-shadow:0 1px 0 #fff; font-size: 16px;"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_SELECTOR_LEGEND'))?></div>
	<?endif;?>
		<ul class="crm_product_list crm_itemcategory">
		<?$productItemClass = $listMode === 'SELECTOR' ? "crm_itemcategory_item crm_arrow" : "crm_itemcategory_item";
		foreach($products as &$product):
			$productDispatcherData[] = CCrmMobileHelper::PrepareProductData($product);?>
			<li class="<?=$productItemClass?>">
				<input type="hidden" class="crm_entity_info" value="<?=$product['ID']?>" />
				<div class="crm_itemcategory_title"><?=$product['NAME']?><span> <?=$product['FORMATTED_PRICE']?></span></div>
			<?if($product['SECTION_NAME'] !== ''):?>
				<div class="crm_category_desc"><span><?=$product['SECTION_NAME']?></span></div>
			<?endif;?>
				<div class="clb"></div>
			</li>
		<?endforeach;?>
		<?unset($product);?>
		<?if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):?>
			<li class="crm_itemcategory_item crm_itemcategory_item_wait"></li>
		<?endif;?>
		</ul>
	</div>
	<div id="<?=htmlspecialcharsbx($productSectionWrapperID)?>">
		<div id="<?=htmlspecialcharsbx($activeSectionContainerID)?>" class="tac" style="margin-bottom: 10px; color: #32506b; text-shadow:0 1px 0 #fff; font-size: 16px;display:<?=$activeTab === 'SECTION' ? '' : 'none';?>;">
		<?=$activeSection ? $activeSection['NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_ROOT_SECTION_LEGEND'))?>
		</div>
		<div id="<?=htmlspecialcharsbx($showSectionButtonID)?>" class="crm_block_container aqua_style comments" style="display:<?=$activeTab === 'SECTION' ? '' : 'none';?>">
			<div class="crm_arrow">
				<div class="crm_block_title" style="color:#000"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_LIST_SHOW_SECTION'))?></div>
				<div class="clearboth"></div>
			</div>
		</div>
		<ul class="crm_product_section_list crm_itemcategory" style="display:<?=$activeTab === 'SECTION' ? '' : 'none';?>">
			<?foreach($sections as &$section):
				$productSectionDispatcherData[] = CCrmMobileHelper::PrepareProductSectionData($section);?>
				<li class="crm_itemcategory_item crm_arrow">
					<input type="hidden" class="crm_entity_info" value="<?=$section['ID']?>" />
					<div class="crm_category_title"><?=htmlspecialcharsbx($section['NAME'])?></div>
				</li>
			<?endforeach;?>
			<?unset($section);?>
		</ul>
		<ul class="crm_product_list crm_itemcategory" style="display:none;">
			<li class="crm_itemcategory_item crm_itemcategory_item_wait"></li>
		</ul>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_PRODUCT_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_PRODUCT_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_PRODUCT_LIST_LOAD_TEXT')?>'
				}
			);

			var productDispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID.'_product')?>",
				{
					typeName: 'PRODUCT',
					data: <?=CUtil::PhpToJSObject($productDispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: {}
				}
			);

			var mainProductList = BX.CrmProductListView.create(
				"<?=CUtil::JSEscape($UID.'_main')?>",
				{
					dispatcher: productDispatcher,
					wrapperId: '<?=CUtil::JSEscape($productWrapperID)?>',
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					listMode: '<?=CUtil::JSEscape($arResult['LIST_MODE'])?>'
				}
			);
			mainProductList.initializeFromExternalData();

			var sectionDispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID.'_section')?>",
				{
					typeName: 'PRODUCT_SECTION',
					data: <?=CUtil::PhpToJSObject($productSectionDispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: {}
				}
			);

			var sectionProductList = BX.CrmProductListView.create(
				"<?=CUtil::JSEscape($UID.'_section_products')?>",
				{
					dispatcher: productDispatcher,
					wrapperId: '<?=CUtil::JSEscape($productSectionWrapperID)?>',
					listMode: '<?=CUtil::JSEscape($arResult['LIST_MODE'])?>'
				}
			);
			sectionProductList.initializeFromExternalData();

			BX.CrmProductSectionListView.messages =
			{
				rootSectionLegend: '<?=GetMessageJS('M_CRM_PRODUCT_LIST_ROOT_SECTION_LEGEND')?>',
				buttonUpCaption: '<?=GetMessageJS('M_CRM_PRODUCT_LIST_BUTTON_UP')?>'
			};

			var sectionList = BX.CrmProductSectionListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: sectionDispatcher,
					wrapperId: '<?=CUtil::JSEscape($productSectionWrapperID)?>',
					activeSectionContainerId: '<?=CUtil::JSEscape($activeSectionContainerID)?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					catalogId: '<?=CUtil::JSEscape($arResult['CATALOG_ID'])?>',
					sectionId: '<?=CUtil::JSEscape($arResult['SECTION_ID'])?>',
					sectionProductUrlTemplate: '<?=CUtil::JSEscape($arResult['PRODUCT_SECTION_URL_TEMPLATE'])?>'
				}
			);

			BX.CrmProductListTurnView.messages =
			{
				buttonUpCaption: '<?=GetMessageJS('M_CRM_PRODUCT_LIST_BUTTON_UP')?>'
			};

			var turnView = BX.CrmProductListTurnView.create(
				{
					sectionView: sectionList,
					productView: sectionProductList,
					showSectionButtonId: '<?=CUtil::JSEscape($showSectionButtonID)?>',
					isVisible: false
				}
			);

			var productViewElems =
				[
					{
						object: BX("<?=CUtil::JSEscape($searchContainerID)?>")
					},
					{
						type: "view",
						object: mainProductList
					}
				];

			var productLegendContainer = BX("<?=CUtil::JSEscape($productLegendContainerID)?>");
			if(BX.type.isDomNode(productLegendContainer))
			{
				productViewElems.push({ object: productLegendContainer });
			}

			BX.CrmProductListViewSwitch.create(
				{
					itemData:
						[
							{
								button: BX("<?=CUtil::JSEscape($productViewSelectorID)?>"),
								isVisible: true,
								elements: productViewElems
							},
							{
								button: BX("<?=CUtil::JSEscape($productSectionViewSelectorID)?>"),
								isVisible: false,
								elements:
									[
										{
											object: BX("<?=CUtil::JSEscape($activeSectionContainerID)?>")
										},
										{
											type: "view",
											object: turnView
										}
									]
							}
						]
				}
			);
		}
	);
</script>
