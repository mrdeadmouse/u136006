<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

if(!function_exists('__CrmMobileLeadViewRenderMultiFields'))
{
	function __CrmMobileLeadViewRenderMultiFields($type, &$fields, &$typeInfos)
	{
		$data = isset($fields[$type]) ? $fields[$type] : array();
		if(empty($data))
		{
			return '';
		}

		$result = '';

		$typeInfo = isset($typeInfos[$type]) ? $typeInfos[$type] : array();
		foreach($data as $datum)
		{
			$value = isset($datum['VALUE']) ? $datum['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$type = isset($datum['VALUE_TYPE']) ? $datum['VALUE_TYPE'] : '';
			$legend = '';
			if(isset($typeInfo[$type]))
			{
				$legend = isset($typeInfo[$type]['ABBR']) ? $typeInfo[$type]['ABBR'] : '';
				if($legend === '' && isset($typeInfo[$type]['SHORT']))
				{
					$legend = $typeInfo[$type]['SHORT'];
				}
			}

			if($result !== '')
			{
				$result .= '<br/>';
			}

			$result .= htmlspecialcharsbx($value).' '.htmlspecialcharsbx($legend);
		}

		return $result;
	}
}

$UID = $arResult['UID'];
$prefix = htmlspecialcharsbx($UID);
$entity = $arResult['ENTITY'];

$dataItem = CCrmMobileHelper::PrepareLeadData($entity);
$typeInfos = CCrmFieldMulti::GetEntityTypes();

$legendHtml = '';
$companyTitle = isset($entity['COMPANY_TITLE']) ? $entity['COMPANY_TITLE'] : '';
$post = isset($entity['POST']) ? $entity['POST'] : '';
if($companyTitle !== '' && $post !== '')
{
	$legendHtml = "{$companyTitle}, {$post}";
}
elseif($companyTitle !== '')
{
	$legendHtml = $companyTitle;
}
elseif($post !== '')
{
	$legendHtml = $post;
}

//COMMENTS already encoded by LHE
$comment = isset($entity['~COMMENTS']) ? $entity['~COMMENTS'] : '';
/*$comment = '';
$commentCut = '';
$hasComment = CCrmMobileHelper::PrepareCut(
	isset($entity['~COMMENTS']) ? $entity['~COMMENTS'] : '',
	$comment,
	$commentCut
);
*/
echo CCrmViewHelper::RenderLeadStatusSettings();

?><div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<span class="crm_head_title_number"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_ID', array('#ID#' => $entity['~ID'])))?></span>
	<div class="crm_head_title"><?=$entity['TITLE']?> - <?=$entity['FORMATTED_OPPORTUNITY']?></div>
	<div class="clb"></div>
	<div class="crm_block_container">
		<div class="crm_tac lisb order">
			<div style="padding: 10px;">
				<div class="crm_order_status<?= $arResult['PERMISSIONS']['EDIT'] ? ' crm_arrow' : ''?>">
					<input type="hidden" id="<?=$prefix?>_status_id" value="<?=$entity['STATUS_ID']?>" />
					<span id="<?=$prefix?>_status_name" class="fll" style="color:#687178"><?=$entity['STATUS_NAME']?></span>
					<div class="clb"></div>
					<?CCrmMobileHelper::RenderProgressBar(
						array(
							'LAYOUT' => 'big',
							'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
							'ENTITY_ID' => $entity['~ID'],
							'WRAPPER_ID' => $UID.'_status_container',
							'CURRENT_ID' => $entity['~STATUS_ID']
						)
					);?>
				</div>
			</div>
		</div>
		<div class="clb"></div>
	</div>
	<div class="crm_block_container">
		<div class="crm_card">
			<div class="crm_card_image">
				<img src="<?=htmlspecialcharsbx(CCrmMobileHelper::GetLeadViewImageStub())?>" />
			</div>
			<div class="crm_card_name">
				<?=$entity['FORMATTED_NAME']?>
			</div>
			<div class="crm_card_description">
				<?=$legendHtml?>
			</div>
			<div class="clb"></div>
		</div>
		<div class="crm_tac lisb"><?
				$callto = isset($arResult['CALLTO']) ? $arResult['CALLTO'] : null;
				$enableCallto = $callto && ($callto['URL'] !== '' || $callto['SCRIPT'] !== '');
				//$mailto = isset($arResult['MAILTO']) ? $arResult['MAILTO'] : null;
				//$enableMailto = $mailto && ($mailto['URL'] !== '' || $mailto['SCRIPT'] !== '');
				?><a class="crm accept-button<?=!$enableCallto ? ' disable' : ''?>" href="<?=$callto['URL'] !== '' ? htmlspecialcharsbx($callto['URL']) : '#'?>"<?=$callto['SCRIPT'] !== '' ? ' onclick="'.htmlspecialcharsbx($callto['SCRIPT']).'"' : (!$enableCallto ? ' onclick="return false;"' : '')?>><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_ACTION_CALL_TO'))?></a><?
				?><a id="<?=$prefix?>_add_email_btn" class="crm_buttons email" href="#"><span></span></a><?
				?><a class="crm_buttons sms disabled" href="#" onclick="return false;"><span></span></a><?
				?><a id="<?=$prefix?>_add_call_btn" class="crm_buttons phone" href="#"><span></span></a><?
				?><a class="crm_buttons ceck disabled" href="#" onclick="return false;"><span></span></a><?
				?><a id="<?=$prefix?>_add_meeting_btn" class="crm_buttons cont" href="#"><span></span></a><?
		?></div>
	</div>
	<div class="crm_block_container dealings" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['ACTIVITY_LIST_URL'])?>' });">
		<div class="crm_block-aqua-container">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_ACTIVITY_LIST'))?></div>
			<div class="crm_dealings_count"><?=$entity['ACTITITY_QUANTITY']?></div>
			<div class="clb"></div>
		</div>
	</div>
	<div class="crm_block_container work" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['PRODUCT_ROWS_URL'])?>', cache: false });">
		<div class="crm_block-aqua-container">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_PRODUCT_ROWS'))?></div>
			<div class="crm_work_count"><?=$entity['PRODUCT_ROWS_QUANTITY']?></div>
			<div class="clb"></div>
		</div>
	</div>
	<div class="crm_block_container comments" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['EVENT_LIST_URL'])?>' });">
		<div class="crm_block-aqua-container">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_EVENT_LIST'))?></div>
			<!--<div class="crm_dealings_count"></div>-->
			<div class="clb"></div>
		</div>
	</div><?
	$phoneHtml = __CrmMobileLeadViewRenderMultiFields('PHONE', $entity['FM'], $typeInfos);
	$emailHtml = __CrmMobileLeadViewRenderMultiFields('EMAIL', $entity['FM'], $typeInfos);
	$webHtml = __CrmMobileLeadViewRenderMultiFields('WEB', $entity['FM'], $typeInfos);
	$imHtml = __CrmMobileLeadViewRenderMultiFields('IM', $entity['FM'], $typeInfos);
	$enableAddCall = $phoneHtml !== '';
	$enableAddEmail = $emailHtml !== '';
	if($enableAddCall || $enableAddEmail || $webHtml !== '' || $imHtml !== '' || $entity['FULL_ADDRESS'] !== ''):
		?><div class="crm_block_container">
			<div class="crm_contact_info">
				<table><tbody>
					<?if($phoneHtml !== ''):?>
						<tr>
							<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_PHONE'))?>:</td>
							<td><?=$phoneHtml?></td>
						</tr>
					<?endif;?>
					<?if($emailHtml !== ''):?>
						<tr>
							<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_EMAIL'))?>:</td>
							<td><?=$emailHtml?></td>
						</tr>
					<?endif;?>
					<?if($webHtml !== ''):?>
						<tr>
							<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_WEB'))?>:</td>
							<td><?=$webHtml?></td>
						</tr>
					<?endif;?>
					<?if($imHtml !== ''):?>
						<tr>
							<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_IM'))?>:</td>
							<td><?=$imHtml?></td>
						</tr>
					<?endif;?>
					<?if($entity['ADDRESS'] !== ''):?>
						<tr>
							<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_ADDRESS'))?>:</td>
							<td><?=$entity['FULL_ADDRESS']?></td>
						</tr>
					<?endif;?>
				</tbody></table>
			</div>
		</div><?
	endif;
	?><div class="crm_block_container">
		<div class="crm_contact_info">
			<table><tbody>
				<tr>
					<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_SOURCE'))?>:</td>
					<td><?=$entity['SOURCE_NAME']?></td>
				</tr>
			</tbody></table>
			<hr/>
			<table>
				<tbody>
					<tr>
						<td class="crm_vat"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_RESPONSIBLE'))?>:</td>
						<?if($entity['ASSIGNED_BY_SHOW_URL'] !== ''):?>
							<td class="crm_arrow" onclick="BX.CrmMobileContext.redirect({ url: '<?=CUtil::JSEscape($entity['ASSIGNED_BY_SHOW_URL'])?>' });">
								<span class="crm_user_link"> <?=$entity['ASSIGNED_BY_FORMATTED_NAME']?></span>
							</td>
						<?else:?>
							<td>
								<span class="crm_user_link"> <?=$entity['ASSIGNED_BY_FORMATTED_NAME']?></span>
							</td>
						<?endif;?>
					</tr>
				</tbody>
			</table>
			<?if($comment !== ''):?>
			<hr/>
			<div class="crm_block_content">
				<div class="crm_block_content_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_LEAD_VIEW_COMMENT'))?>:</div>
				<div class="crm_block_comment"><?=$comment?></div>
			</div>
			<?endif;?>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var uid = '<?=CUtil::JSEscape($UID)?>';
			var dispatcher = BX.CrmEntityDispatcher.create(
				uid,
				{
					typeName: 'LEAD',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'LEAD_EDIT_URL_TEMPLATE' => $arParams['LEAD_EDIT_URL_TEMPLATE'],
							'LEAD_SHOW_URL_TEMPLATE' => $arParams['LEAD_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmLeadView.messages =
			{
				menuEdit: '<?=GetMessageJS('M_CRM_LEAD_VIEW_EDIT')?>',
				menuDelete: '<?=GetMessageJS('M_CRM_LEAD_VIEW_DELETE')?>',
				deletionTitle: '<?=GetMessageJS('M_CRM_LEAD_VIEW_DELETION_TITLE')?>',
				deletionConfirmation: '<?=GetMessageJS('M_CRM_LEAD_VIEW_DELETION_CONFIRMATION')?>'
			};

			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_LEAD_VIEW_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_LEAD_VIEW_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_LEAD_VIEW_LOAD_TEXT')?>'
				}
			);

			var entityId = <?=$arResult['ENTITY_ID']?>;
			var view = BX.CrmLeadView.create(
				entityId,
				{
					prefix: uid,
					entityId: entityId,
					dispatcher: dispatcher,
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					editUrl: '<?=CUtil::JSEscape($entity['EDIT_URL'])?>',
					callEditUrl: '<?=CUtil::JSEscape($entity['CALL_EDIT_URL'])?>',
					meetingEditUrl: '<?=CUtil::JSEscape($entity['MEETING_EDIT_URL'])?>',
					emailEditUrl: '<?=CUtil::JSEscape($entity['EMAIL_EDIT_URL'])?>',
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					leadStatusSelectorUrl: '<?=CUtil::JSEscape($arResult['LEAD_STATUS_SELECTOR_URL'])?>',
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>,
					enableAddCall: <?= $enableAddCall ? 'true' : 'false'?>,
					enableAddEmail: <?= $enableAddEmail ? 'true' : 'false'?>
				}
			);
		}
	);
</script>

