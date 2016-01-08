<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$mode = $arResult['MODE'];
$prefix = htmlspecialcharsbx($UID);
$entityID = $arResult['ENTITY_ID'];
$entity = $arResult['ENTITY'];

$dataItem = CCrmMobileHelper::PrepareDealData($entity);
$formTitle =GetMessage("M_CRM_DEAL_EDIT_{$mode}_TITLE");

echo CCrmViewHelper::RenderDealStageSettings();

?><div class="crm_toppanel">
	<div class="crm_filter">
		<span class="crm_deals_icon"></span>
		<?=htmlspecialcharsbx($formTitle)?>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;">
			<?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_SECTION_GENERAL'))?>
		</div>
		<hr/>
		<div class="crm_card tar" style="padding:3px 14px;">
			<input id="<?=$prefix?>_title" class="crm_input_text" type="text" value="<?=$entity['TITLE']?>"  placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_TITLE'))?>" />
			<div class="crm_input_desc" style="padding-bottom:0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_HINT_REQUIRED'))?></div>
		</div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info crm_arrow">
			<input type="hidden"  id="<?=$prefix?>_stage_id" value="<?=$entity['STAGE_ID']?>" />
			<span id="<?=$prefix?>_stage_name" class="fll" style="color:#687178;font-size: 14px;"><?=$entity['STAGE_NAME']?></span>
			<div class="clb"></div><?
			CCrmMobileHelper::RenderProgressBar(
				array(
					'LAYOUT' => 'big',
					'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
					'ENTITY_ID' => $entity['~ID'],
					'WRAPPER_ID' => $UID.'_stage_container',
					'CURRENT_ID' => $entity['~STAGE_ID']
				)
			);
		?></div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info">
			<div class="crm_order_status">
				<span class="fll p0 fwn" style="color:#687178;font-size: 14px;"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_PROBABILITY'))?>:</span>
				<span class="fll"><input id="<?=$prefix?>_probability" class="crm_input_text fwb dib tac posr" style="margin-top: -8px; width: 40px;" type="text" value="<?=$entity['PROBABILITY']?>"/></span>
				<div class="clb"></div>
			</div>
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_CURRENCY'))?>: <span id="<?=$prefix?>_currency_name"><?=$entity['CURRENCY_NAME']?></span></div>
			<input type="hidden"  id="<?=$prefix?>_currency_id" value="<?=$entity['CURRENCY_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_OPPORTUNITY'))?></div>
		<hr>
		<div class="crm_card" style="padding-bottom: 0;">
			<input id="<?=$prefix?>_opportunity" class="crm_input_text" type="text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_OPPORTUNITY'))?>" value="<?=$entity['OPPORTUNITY'] != 0 ? $entity['OPPORTUNITY'] : ''?>" />
		</div>
		<div class="clearboth"></div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow"><?
			$clientCaption = '';
			$clientLegend = '';
			if($entity['~CONTACT_ID'] > 0):
				$clientCaption = $entity['CONTACT_FORMATTED_NAME'];
				if(isset($entity['COMPANY_TITLE'])):
					$clientLegend = $entity['COMPANY_TITLE'];
				endif;
			elseif($entity['~COMPANY_ID'] > 0):
				$clientCaption = $entity['COMPANY_TITLE'];
			else:
				$clientCaption = htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_CLIENT_NOT_SPECIFIED'));
			endif;
			?><div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_CLIENT'))?>: <span id="<?=$prefix?>_client_caption"><?=$clientCaption?></span><br/><span id="<?=$prefix?>_client_legend" style="font-size: 12px; color: #616e79; margin-left: 60px;<?= $clientLegend === '' ? ' display:none;' : ''?>"><?=$clientLegend?></span></div>
			<input type="hidden"  id="<?=$prefix?>_contact_id" value="<?=$entity['CONTACT_ID']?>" />
			<input type="hidden"  id="<?=$prefix?>_company_id" value="<?=$entity['COMPANY_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_TYPE'))?>: <span id="<?=$prefix?>_type_name"><?=$entity['TYPE_NAME'] !== '' ? $entity['TYPE_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_TYPE_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_type_id" value="<?=$entity['TYPE_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_RESPONSIBLE'))?>: <span id="<?=$prefix?>_assigned_by_name"><?=$entity['ASSIGNED_BY_FORMATTED_NAME'] !== '' ? $entity['ASSIGNED_BY_FORMATTED_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_RESPONSIBLE_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_assigned_by_id" value="<?=$entity['ASSIGNED_BY_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_DEAL_EDIT_FIELD_COMMENTS'))?></div>
		<hr>
		<div class="crm_card" style="padding-bottom: 0;">
			<textarea id="<?=$prefix?>_comments" class="crm_input_text"><?=$entity['~COMMENTS']?></textarea>
		</div>
		<div class="clearboth"></div>
	</div>
<!--<input type="button"  id="save" value="SAVE" />-->
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var uid = '<?=CUtil::JSEscape($UID)?>';

			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: 'DEAL',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'DEAL_EDIT_URL_TEMPLATE' => $arParams['DEAL_EDIT_URL_TEMPLATE'],
							'DEAL_SHOW_URL_TEMPLATE' => $arParams['DEAL_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmDealEditor.messages =
			{
				userSelectorOkButton: '<?=GetMessageJS('M_CRM_DEAL_EDIT_USER_SELECTOR_OK_BTN')?>',
				userSelectorCancelButton: '<?=GetMessageJS('M_CRM_DEAL_EDIT_USER_SELECTOR_CANCEL_BTN')?>',
				contactNotSpecified: "<?=GetMessageJS('M_CRM_DEAL_EDIT_FIELD_CLIENT_NOT_SPECIFIED')?>"
			};

			var editor = BX.CrmDealEditor.create(
				uid,
				{
					prefix: uid,
					containerId: uid,
					entityId: <?=CUtil::JSEscape($entityID)?>,
					title: '<?=CUtil::JSEscape($formTitle)?>',
					dispatcher: dispatcher,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					statusSelectorUrl: '<?=CUtil::JSEscape($arResult['STATUS_SELECTOR_URL'])?>',
					typeSelectorUrl: '<?=CUtil::JSEscape($arResult['TYPE_SELECTOR_URL'])?>',
					currencySelectorUrl: '<?=CUtil::JSEscape($arResult['CURRENCY_SELECTOR_URL'])?>',
					clientSelectorUrl: '<?=CUtil::JSEscape($arResult['CLIENT_SELECTOR_URL'])?>',
					dealStageSelectorUrl: '<?=CUtil::JSEscape($arResult['DEAL_STAGE_SELECTOR_URL'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>'
				}
			);

			BX.CrmProductRowList.messages =
			{
				addItemButton: "<?=GetMessageJS("M_CRM_DEAL_EDIT_ADD_PRODUCT")?>",
				sumTotal: "<?=GetMessageJS("M_CRM_DEAL_EDIT_SUM_TOTAL")?>"
			};

			var productRowList = BX.CrmProductRowList.create(
				uid,
				{
					data: <?=CUtil::PhpToJSObject($arResult['PRODUCT_ROWS'])?>,
					ownerId: <?=CUtil::JSEscape($entityID)?>,
					ownerType: 'D',
					containerId: uid,
					sumTotal: '<?=CUtil::JSEscape($entity['~OPPORTUNITY'])?>',
					formattedSumTotal: '<?=CUtil::JSEscape($entity['~FORMATTED_OPPORTUNITY'])?>',
					currencyId: '<?=CUtil::JSEscape($entity['~CURRENCY_ID'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					itemInfoHtmlTemplate: "<?=GetMessageJS("M_CRM_DEAL_EDIT_PRODUCT_ROW_TEMPLATE")?>",
					productSelectorUrlTemplate: '<?=CUtil::JSEscape($arResult['PRODUCT_SELECTOR_URL_TEMPLATE'])?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['PRODUCT_ROW_SERVICE_URL'])?>',
					editUrl: '<?=CUtil::JSEscape($arResult['PRODUCT_ROW_EDIT_URL'])?>'
				}
			);
			productRowList.layout();
			editor.setProductRowList(productRowList, true);

			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_DEAL_EDIT_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_DEAL_EDIT_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_DEAL_EDIT_LOAD_TEXT')?>'
				}
			);

			context.createButtons(
				{
					back:
					{
						type: 'right_text',
						style: 'custom',
						position: 'left',
						name: '<?=GetMessageJS('M_CRM_DEAL_EDIT_CANCEL_BTN')?>',
						callback: context.createCloseHandler()
					},
					save:
					{
						type: 'right_text',
						style: 'custom',
						position: 'right',
						name: '<?=GetMessageJS("M_CRM_DEAL_EDIT_{$mode}_BTN")?>',
						callback: editor.createSaveHandler()
					}
				}
			);
		}
	);
</script>
