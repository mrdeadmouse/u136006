<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$UID = $arResult['UID'];
$prefix = htmlspecialcharsbx($UID);
$entity = $arResult['ENTITY'];

$clientTitle = '';
$clientLegend = '';
$clientImageInfo = null;
$clientCallTo = null;
$emailEditUrl = '';
$showCompany = true;
$clientEmails = array();
$clientEmailComm = null;

if($entity['~CONTACT_ID'] > 0)
{
	$clientTitle = $entity['~CONTACT_FULL_NAME'];
	$clientLegend = $entity['~COMPANY_TITLE'];
	if($entity['~CONTACT_POST'] !== '')
	{
		if($clientLegend !== '')
		{
			$clientLegend .= ', ';
			$clientLegend .= $entity['~CONTACT_POST'];
		}
	}

	$clientImageID = $entity['~CONTACT_PHOTO'];
	$clientImageInfo = $clientImageID > 0
		? CFile::ResizeImageGet($clientImageID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT)
		: array('src' => CCrmMobileHelper::GetContactViewImageStub());

	if(isset($arResult['CONTACT_CALLTO']))
	{
		$clientCallTo = $arResult['CONTACT_CALLTO'];
	}

	if(isset($entity['CONTACT_EMAIL_EDIT_URL']))
	{
		$emailEditUrl = $entity['CONTACT_EMAIL_EDIT_URL'];
	}

	$emailData = isset($entity['CONTACT_FM']) && isset($entity['CONTACT_FM']['EMAIL']) ? $entity['CONTACT_FM']['EMAIL'] : array();
	if(!empty($emailData))
	{
		$clientEmailComm = array(
			'TYPE' => 'EMAIL',
			'VALUE' => $emailData[0]['VALUE'],
			'TITLE' => $clientTitle,
			'ENTITY_ID' => $entity['~CONTACT_ID'],
			'ENTITY_TYPE' => CCrmOwnerType::ContactName
		);
	}
}
elseif($entity['~COMPANY_ID'] > 0)
{
	$clientTitle = $entity['~COMPANY_TITLE'];
	$clientImageID = $entity['~COMPANY_LOGO'];
	$clientImageInfo = $clientImageID > 0
		? CFile::ResizeImageGet($clientImageID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT)
		: array('src' => CCrmMobileHelper::GetCompanyViewImageStub());

	$showCompany = false;

	if(isset($arResult['COMPANY_CALLTO']))
	{
		$clientCallTo = $arResult['COMPANY_CALLTO'];
	}

	if(isset($entity['COMPANY_EMAIL_EDIT_URL']))
	{
		$emailEditUrl = $entity['COMPANY_EMAIL_EDIT_URL'];
	}

	$emailData = isset($entity['COMPANY_FM']) && isset($entity['COMPANY_FM']['EMAIL']) ? $entity['COMPANY_FM']['EMAIL'] : null;
	if(!empty($emailData))
	{
		$clientEmailComm = array(
			'TYPE' => 'EMAIL',
			'VALUE' => $emailData[0]['VALUE'],
			'TITLE' => $clientTitle,
			'ENTITY_ID' => $entity['~COMPANY_ID'],
			'ENTITY_TYPE' => CCrmOwnerType::CompanyName
		);
	}
}
$enableMailto = $clientEmailComm !== null;
$dataItem = CCrmMobileHelper::PrepareInvoiceData($entity);

echo CCrmViewHelper::RenderInvoiceStatusSettings();
?><div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<span class="crm_head_title_number"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_ID', array('#ID#' => $entity['~ID'])))?></span>
	<div class="crm_head_title"><?=$entity['ORDER_TOPIC']?> - <?=$entity['FORMATTED_PRICE']?></div>
	<div class="clb"></div>
	<div class="crm_block_container">
		<div class="crm_tac lisb order">
			<div style="padding:0 10px 10px;">
				<div class="crm_order_status<?= $arResult['PERMISSIONS']['EDIT'] ? ' crm_arrow' : ''?>">
					<input type="hidden"  id="<?=$prefix?>_status_id" value="<?=$entity['STATUS_ID']?>" />
					<span id="<?=$prefix?>_status_name" class="fll" style="color:#687178"><?=$entity['STATUS_TEXT']?></span>
					<div class="clb"></div>
					<?CCrmMobileHelper::RenderProgressBar(
						array(
							'LAYOUT' => 'big',
							'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
							'ENTITY_ID' => $entity['~ID'],
							'WRAPPER_ID' => $UID.'_status_container',
							'CURRENT_ID' => $entity['~STATUS_ID']
						)
					);?>
				</div><?
				if($dataItem['IS_SUCCESSED'] || $dataItem['IS_FINISHED']):
				?><hr/><?
				endif;
				if($dataItem['IS_SUCCESSED']):
					if($dataItem['PAYMENT_DATE'] !== ''):
					?><div class="crm_order_status">
						<span class="fll fwn"><?=htmlspecialcharsbx(GetMessage('M_CRM_IVOICE_VIEW_PAYMENT_DATE'))?>:</span>
						<span class="fll fwb"><?=htmlspecialcharsbx($dataItem['PAYMENT_DATE'])?></span>
						<div class="clb"></div>
					</div><?
					endif;
					if($dataItem['PAYMENT_DOC'] !== ''):
					?><div class="crm_order_status">
						<span class="fll fwn"><?=htmlspecialcharsbx(GetMessage('M_CRM_IVOICE_VIEW_PAYMENT_DOC'))?>:</span>
						<span class="fll fwb"><?=htmlspecialcharsbx($dataItem['PAYMENT_DOC'])?></span>
						<div class="clb"></div>
					</div><?
					endif;
				elseif($dataItem['IS_FINISHED'] && $dataItem['CANCEL_DATE'] !== ''):
				?><div class="crm_order_status">
					<span class="fll fwn"><?=htmlspecialcharsbx(GetMessage('M_CRM_IVOICE_VIEW_CANCEL_DATE'))?>:</span>
					<span class="fll fwb"><?=htmlspecialcharsbx($dataItem['CANCEL_DATE'])?></span>
					<div class="clb"></div>
				</div><?
				endif;
				?>
			</div>
		</div>
		<div class="clb"></div><?
		if($dataItem['IS_SUCCESSED'] && $dataItem['PAYMENT_COMMENT'] !== ''):?>
		<div class="crm_contact_info">
			<div class="crm_block_content">
				<div class="fwn" style="font-size: 14px;"><?=htmlspecialcharsbx(GetMessage('M_CRM_IVOICE_VIEW_PAYMENT_COMMENT'))?>:</div>
				<div style="font-size: 14px;"><?=htmlspecialcharsbx($dataItem['PAYMENT_COMMENT'])?></div>
			</div>
		</div><?
		elseif($dataItem['IS_FINISHED'] && $dataItem['CANCEL_REASON'] !== ''):?>
		<div class="crm_contact_info">
			<div class="crm_block_content">
				<div class="fwn" style="font-size: 14px;"><?=htmlspecialcharsbx(GetMessage('M_CRM_IVOICE_VIEW_CANCEL_REASON'))?>:</div>
				<div style="font-size: 14px;"><?=htmlspecialcharsbx($dataItem['CANCEL_REASON'])?></div>
			</div>
		</div><?
		endif;
	?></div><?
	if($clientTitle !== ''):
	?><div class="crm_block_container">
		<div class="crm_card">
			<div class="crm_card_image">
				<img src="<?=$clientImageInfo && isset($clientImageInfo['src']) ? htmlspecialcharsbx($clientImageInfo['src']) : ''?>"/>
			</div>
			<div class="crm_card_name"><?=htmlspecialcharsbx($clientTitle)?></div>
			<div class="crm_card_description"><?=htmlspecialcharsbx($clientLegend)?></div>
			<div class="clb"></div>
		</div>
		<div class="crm_tac lisb"><?
				$enableCallto = $clientCallTo && ($clientCallTo['URL'] !== '' || $clientCallTo['SCRIPT'] !== '');
				?><a class="crm accept-button<?=!$enableCallto ? ' disabled' : ''?>" href="<?=$clientCallTo['URL'] !== '' ? htmlspecialcharsbx($clientCallTo['URL']) : '#'?>"<?=$clientCallTo['SCRIPT'] !== '' ? ' onclick="'.htmlspecialcharsbx($clientCallTo['SCRIPT']).'"' : (!$enableCallto ? ' onclick="return false;"' : '')?>><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_ACTION_CALL_TO_CLIENT'))?></a><?
				?><a id="<?=$prefix?>_add_email_btn" class="crm_buttons invoice<?=!$enableMailto ? ' disabled' : ''?>" href="#"><span></span></a><?
		?></div>
	</div><?
	endif;
	if($showCompany && $entity['COMPANY_SHOW_URL'] !== ''):
	?><div class="crm_block_container company" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['COMPANY_SHOW_URL'])?>' });">
		<div class="crm_block-aqua-container">
			<div class="crm_block_title fln crm_arrow"><?=htmlspecialcharsbx($entity['~COMPANY_TITLE'] != '' ? $entity['~COMPANY_TITLE'] : GetMessage('M_CRM_DEAL_VIEW_NO_TITLE'))?></div>
			<div class="clb"></div>
		</div>
	</div><?
	endif;
	?>
	<div class="crm_block_container work" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['PRODUCT_ROWS_URL'])?>', cache: false });">
		<div class="crm_block-aqua-container">
			<div class="crm_arrow">
				<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_PRODUCT_ROWS'))?></div>
				<div class="clb"></div>
			</div>
		</div>
	</div>
	<div class="crm_block_container comments" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['EVENT_LIST_URL'])?>' });">
		<div class="crm_block-aqua-container">
			<div class="crm_arrow">
				<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_EVENT_LIST'))?></div>
				<!--<div class="crm_dealings_count"></div>-->
				<div class="clb"></div>
			</div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_contact_info">
			<table>
				<tbody>
					<?if($entity['DATE_BILL'] !== ''):?>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_DATE_BILL'))?>:</td>
						<td><span> <?=$entity['DATE_BILL']?></span></td>
					</tr>
					<?endif;?>
					<?if($entity['DATE_PAY_BEFORE'] !== ''):?>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_DATE_PAY_BEFORE'))?>:</td>
						<td><span> <?=$entity['DATE_PAY_BEFORE']?></span></td>
					</tr>
					<?endif;?>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_PAYER_INFO'))?>:</td>
						<td><span> <?=$entity['PAYER_INFO']?></span></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_contact_info">
			<table>
				<tbody>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_RESPONSIBLE'))?>:</td>
						<?if($entity['RESPONSIBLE_SHOW_URL'] !== ''):?>
							<td class="crm_arrow" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['RESPONSIBLE_SHOW_URL'])?>' });">
								<span class="crm_user_link"> <?=$entity['RESPONSIBLE_FORMATTED_NAME']?></span>
							</td>
						<?else:?>
							<td><span class="crm_user_link"> <?=$entity['RESPONSIBLE_FORMATTED_NAME']?></span></td>
						<?endif;?>
					</tr>
				</tbody>
			</table>
			<?if($entity['~COMMENTS'] !== ''):?>
			<hr/>
			<div class="crm_block_content">
				<div class="crm_block_content_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_MANAGER_COMMENTS'))?>:</div>
				<div class="crm_block_comment"><?=$entity['~COMMENTS']?></div>
			</div>
			<?endif;?>
			<?if($entity['~USER_DESCRIPTION'] !== ''):?>
			<hr/>
			<div class="crm_block_content">
				<div class="crm_block_content_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_INVOICE_VIEW_USER_COMMENTS'))?>:</div>
				<div class="crm_block_comment"><?=$entity['~USER_DESCRIPTION']?></div>
			</div>
			<?endif;?>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_INVOICE_VIEW_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_INVOICE_VIEW_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_INVOICE_VIEW_LOAD_TEXT')?>'
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

			BX.CrmInvoiceView.messages =
			{
				menuEdit: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_EDIT')?>',
				menuDelete: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_DELETE')?>',
				deletionTitle: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_DELETION_TITLE')?>',
				deletionConfirmation: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_DELETION_CONFIRMATION')?>',
				sendEmailTitle: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_SEND_EMAIL')?>',
				clientEmailNotFound: '<?=GetMessageJS('M_CRM_INVOICE_VIEW_CLIENT_EMAIL_NOT_FOUND')?>'
			};

			var entityId = <?=$arResult['ENTITY_ID']?>;
			var view = BX.CrmInvoiceView.create(
				entityId,
				{
					prefix: uid,
					entityId: entityId,
					dispatcher: dispatcher,
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					editUrl: '<?=CUtil::JSEscape($entity['EDIT_URL'])?>',
					callEditUrl: '<?=CUtil::JSEscape($callEditUrl)?>',
					emailEditUrl: '<?=CUtil::JSEscape($emailEditUrl)?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					invoiceStatusSelectorUrl: '<?=CUtil::JSEscape($arResult['INVOICE_STATUS_SELECTOR_URL'])?>',
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>,
					clientEmailComm: <?=CUtil::PhpToJSObject($clientEmailComm)?>,
					emailSubject: '<?=CUtil::JSEscape($arResult['EMAIL_SUBJECT'])?>'
				}
			);
		}
	);
</script>
