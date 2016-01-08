<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

CUtil::InitJSCore(array('ajax', 'date'));

$UID = $arResult['UID'];
$mode = $arResult['MODE'];
$prefix = htmlspecialcharsbx($UID);
$entityID = $arResult['ENTITY_ID'];
$entity = $arResult['ENTITY'];

$dataItem = CCrmMobileHelper::PrepareInvoiceData($entity);
$formTitle =GetMessage("M_CRM_INVOICE_EDIT_{$mode}_TITLE");

echo CCrmViewHelper::RenderInvoiceStatusSettings();
?><div class="crm_toppanel">
	<div class="crm_filter">
		<span class="crm_raport_icon"></span>
		<?=htmlspecialcharsbx($formTitle)?>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;">
			<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_SECTION_GENERAL'))?>
		</div>
		<hr/>
		<div class="crm_card tar" style="padding:3px 14px;">
			<input id="<?=$prefix?>_order_topic" class="crm_input_text" type="text" value="<?=$entity['ORDER_TOPIC']?>"  placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_FIELD_TOPIC'))?>" />
			<div class="crm_input_desc" style="padding-bottom:0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_FIELD_HINT_REQUIRED'))?></div>
		</div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info crm_arrow">
			<input type="hidden"  id="<?=$prefix?>_status_id" value="<?=$entity['STATUS_ID']?>" />
			<span id="<?=$prefix?>_status_name" class="fll" style="color:#687178;font-size: 14px;"><?=$entity['STATUS_TEXT']?></span>
			<div class="clb"></div><?
			CCrmMobileHelper::RenderProgressBar(
				array(
					'LAYOUT' => 'big',
					'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
					'ENTITY_ID' => $entity['~ID'],
					'WRAPPER_ID' => $UID.'_status_container',
					'CURRENT_ID' => $entity['~STATUS_ID']
				)
			);
		?></div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info crm_arrow">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_DATE_BILL'))?>: <span id="<?=$prefix?>_date_bill_text"><?=$entity['DATE_BILL'] !== '' ? $entity['DATE_BILL'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span>
			</div>
			<input type="hidden" id="<?=$prefix?>_date_bill" value="<?=htmlspecialcharsbx($entity['DATE_BILL_STAMP'])?>"/>
			<div class="clearboth"></div>
		</div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info crm_arrow">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_DATE_PAY_BEFORE'))?>: <span id="<?=$prefix?>_date_pay_before_text"><?=$entity['DATE_PAY_BEFORE'] !== '' ? $entity['DATE_PAY_BEFORE'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span>
			</div>
			<input type="hidden" id="<?=$prefix?>_date_pay_before" value="<?=htmlspecialcharsbx($entity['DATE_PAY_BEFORE_STAMP'])?>"/>
			<div class="clearboth"></div>
		</div>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info" style="margin-bottom: 10px;">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_CURRENCY'))?>: <span id="<?=$prefix?>_currency_name"><?=$entity['CURRENCY_NAME']?></span>
			</div>
			<input type="hidden" id="<?=$prefix?>_currency_id" value="<?=$entity['CURRENCY']?>"/>
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_DEAL'))?>: <span id="<?=$prefix?>_deal_title"><?=$entity['DEAL_TITLE'] !== '' ? $entity['DEAL_TITLE'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_deal_id" value="<?=$entity['DEAL_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow"><?
			$clientCaption = '';
			$clientLegend = '';
			if($entity['~CONTACT_ID'] > 0):
				$clientCaption = $entity['CONTACT_FULL_NAME'];
				if(isset($entity['COMPANY_TITLE'])):
					$clientLegend = $entity['COMPANY_TITLE'];
				endif;
			elseif($entity['~COMPANY_ID'] > 0):
				$clientCaption = $entity['COMPANY_TITLE'];
			else:
				$clientCaption = htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'));
			endif;
			?><div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_CLIENT'))?>: <span id="<?=$prefix?>_client_caption"><?=$clientCaption?></span><br/><span id="<?=$prefix?>_client_legend" style="font-size: 12px; color: #616e79; margin-left: 60px;<?= $clientLegend === '' ? ' display:none;' : ''?>"><?=$clientLegend?></span></div>
			<input type="hidden" id="<?=$prefix?>_contact_id" value="<?=$entity['CONTACT_ID']?>" />
			<input type="hidden" id="<?=$prefix?>_company_id" value="<?=$entity['COMPANY_ID']?>" />
			<input type="hidden" id="<?=$prefix?>_person_type_id" value="<?=$arResult['PERSON_TYPE_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_meeting_info<?=($arResult['PERSON_TYPE_ID'] > 0 && !empty($arResult['PAYER_REQUISITES'])) ? ' crm_arrow' : ''?>">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_PAYER_INFO'))?>: <span id="<?=$prefix?>_payer_info"><?=$entity['PAYER_INFO'] !== '' ? $entity['PAYER_INFO'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span>
			</div>
			<div class="clearboth"></div>
		</div>
		<?if($arResult['ENABLE_LOCATION']):?>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info crm_arrow" style="margin-bottom: 10px;">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_LOCATION'))?>: <span id="<?=$prefix?>_location_name"><?=$entity['LOCATION_NAME'] !== '' ? $entity['LOCATION_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span>
			</div>
			<input type="hidden" id="<?=$prefix?>_location_id" value="<?=$entity['~LOCATION_ID']?>" />
			<div class="clearboth"></div>
		</div>
		<?endif;?>
		<hr style="margin: 15px 0;"/>
		<div class="crm_meeting_info<?=$arResult['PERSON_TYPE_ID'] > 0 ? ' crm_arrow' : ''?>" style="margin-bottom: 10px;">
			<div class="crm_block_title">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_PAY_SYSTEM'))?>: <span id="<?=$prefix?>_pay_system_name"><?=$entity['PAY_SYSTEM_NAME'] !== '' ? $entity['PAY_SYSTEM_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span>
			</div>
			<input type="hidden" id="<?=$prefix?>_pay_system_id" value="<?=$entity['~PAY_SYSTEM_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_FIELD_RESPONSIBLE'))?>: <span id="<?=$prefix?>_responsible_name"><?=$entity['RESPONSIBLE_FORMATTED_NAME'] !== '' ? $entity['RESPONSIBLE_FORMATTED_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_responsible_id" value="<?=$entity['RESPONSIBLE_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_MANAGER_COMMENTS'))?></div>
		<hr>
		<div class="crm_card" style="padding-bottom: 0;">
			<textarea id="<?=$prefix?>_comments" class="crm_input_text"><?=$entity['~COMMENTS']?></textarea>
		</div>
		<div class="clearboth"></div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_EDIT_USER_COMMENTS'))?></div>
		<hr>
		<div class="crm_card" style="padding-bottom: 0;">
			<textarea id="<?=$prefix?>_user_description" class="crm_input_text"><?=$entity['~USER_DESCRIPTION']?></textarea>
		</div>
		<div class="clearboth"></div>
	</div>
<!--<input type="button"  id="save" value="SAVE" />--><div id="traceContainer"></div>
</div>

<script type="text/javascript">
	BX.trace = function(text){BX("traceContainer").appendChild(BX.create('DIV', { text: text }));};
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_LOAD_TEXT')?>'
				}
			);

			var uid = '<?=CUtil::JSEscape($UID)?>';
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: 'INVOICE',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'INVOICE_EDIT_URL_TEMPLATE' => $arParams['INVOICE_EDIT_URL_TEMPLATE'],
							'INVOICE_SHOW_URL_TEMPLATE' => $arParams['INVOICE_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmInvoiceEditor.messages =
			{
				userSelectorOkButton: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_USER_SELECTOR_OK_BTN')?>',
				userSelectorCancelButton: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_USER_SELECTOR_CANCEL_BTN')?>',
				contactNotSpecified: "<?=GetMessageJS('M_CRM_INVOICE_EDIT_NOT_SPECIFIED')?>",
				sumTotal: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_SUM_TOTAL")?>",
				sumBrutto: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_SUM_BRUTTO")?>",
				notSpecified: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_NOT_SPECIFIED")?>"
			};

			var editor = BX.CrmInvoiceEditor.create(
				uid,
				{
					prefix: uid,
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					containerId: uid,
					entityId: <?=CUtil::JSEscape($entityID)?>,
					title: "<?=CUtil::JSEscape($formTitle)?>",
					dispatcher: dispatcher,
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
					clientSelectorUrl: "<?=CUtil::JSEscape($arResult['CLIENT_SELECTOR_URL'])?>",
					invoiceStatusSelectorUrl: "<?=CUtil::JSEscape($arResult['INVOICE_STATUS_SELECTOR_URL'])?>",
					paySystemSelectorUrl: "<?=CUtil::JSEscape($arResult['PAY_SYSTEM_SELECTOR_URL'])?>",
					locationSelectorUrl: "<?=CUtil::JSEscape($arResult['LOCATION_SELECTOR_URL'])?>",
					dealSelectorUrl: "<?=CUtil::JSEscape($arResult['DEAL_SELECTOR_URL'])?>",
					payerInfoFormat: "<?=$arResult['PAYER_INFO_FORMAT']?>",
					payerRequisites: <?=CUtil::PhpToJSObject($arResult['PAYER_REQUISITES'])?>,
					requisiteEditUrl: "<?=CUtil::JSEscape($arResult['REQUISITE_EDIT_URL'])?>",
					productRows: <?=CUtil::PhpToJSObject($arResult['PRODUCT_ROWS'])?>,
					taxInfos: <?=CUtil::PhpToJSObject($arResult['TAX_INFOS'])?>,
					formattedSumBrutto: "<?=CUtil::JSEscape($arResult['FORMATTED_SUM_BRUTTO'])?>",
					formattedSumNetto: "<?=CUtil::JSEscape($arResult['FORMATTED_SUM_NETTO'])?>"
				}
			);

			BX.CrmProductRowList.messages =
			{
				addItemButton: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_ADD_PRODUCT")?>",
				sumTotal: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_SUM_TOTAL")?>"
			};

			var productRowList = BX.CrmProductRowList.create(
				uid,
				{
					ownerId: <?=CUtil::JSEscape($entityID)?>,
					ownerType: 'I',
					containerId: uid,
					enableTotalInfoRefresh: false,
					sumTotal: '<?=CUtil::JSEscape($entity['~PRICE'])?>',
					formattedSumTotal: '<?=CUtil::JSEscape($entity['~FORMATTED_PRICE'])?>',
					currencyId: '<?=CUtil::JSEscape($entity['~CURRENCY'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					itemInfoHtmlTemplate: "<?=GetMessageJS("M_CRM_INVOICE_EDIT_PRODUCT_ROW_TEMPLATE")?>",
					productSelectorUrlTemplate: '<?=CUtil::JSEscape($arResult['PRODUCT_SELECTOR_URL_TEMPLATE'])?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['PRODUCT_ROW_SERVICE_URL'])?>',
					editUrl: '<?=CUtil::JSEscape($arResult['PRODUCT_ROW_EDIT_URL'])?>'
				}
			);
			editor.setProductRowList(productRowList, true);
			productRowList.layout();

			context.createButtons(
				{
					back:
					{
						type: 'right_text',
						style: 'custom',
						position: 'left',
						name: '<?=GetMessageJS('M_CRM_INVOICE_EDIT_CANCEL_BTN')?>',
						callback: context.createCloseHandler()
					},
					save:
					{
						type: 'right_text',
						style: 'custom',
						position: 'right',
						name: '<?=GetMessageJS("M_CRM_INVOICE_EDIT_{$mode}_BTN")?>',
						callback: editor.createSaveHandler()
					}
				}
			);
		}
	);
</script>
