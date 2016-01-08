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

$dataItem = CCrmMobileHelper::PrepareContactData($entity);
$multiFieldTypeInfos = CCrmFieldMulti::GetEntityTypes();

$multiFieldTypeSettings = array();
foreach($multiFieldTypeInfos as $multiFieldTypeID => &$multiFieldTypeInfo)
{
	$multiFieldTypeSettings[$multiFieldTypeID] = array();
	foreach($multiFieldTypeInfo as $multiFieldValueTypeID => &$multiFieldValueTypeInfo)
	{
		$multiFieldTypeSettings[$multiFieldTypeID][$multiFieldValueTypeID] =
			isset($multiFieldValueTypeInfo['ABBR']) ? $multiFieldValueTypeInfo['ABBR'] : $multiFieldValueTypeID;
	}
	unset($multiFieldValueTypeInfo);
}
unset($multiFieldTypeInfo);

$formTitle = GetMessage("M_CRM_CONTACT_EDIT_{$mode}_TITLE");
if(!function_exists('__CrmMobileContactEditRenderMultiFields'))
{
	function __CrmMobileContactEditRenderMultiFields($typeName, &$fields, &$typeInfos, $prefix = '')
	{
		$typeName = strtoupper($typeName);
		$data = isset($fields[$typeName]) ? $fields[$typeName] : array();

		if($prefix !== '')
		{
			$prefix .= '_'.strtolower($typeName);
		}
		else
		{
			$prefix = strtolower($typeName);
		}

		$typeInfo = isset($typeInfos[$typeName]) ? $typeInfos[$typeName] : array();

		if(empty($data))
		{
			if(!isset($fields[$typeName]))
			{
				$fields[$typeName] = array();
			}

			$fields[$typeName]['n1'] = array('VALUE' => '', 'VALUE_TYPE' => '');
			$data = $fields[$typeName];
		}

		foreach($data as $key => &$datum)
		{
			$value = isset($datum['VALUE']) ? $datum['VALUE'] : '';

			echo '<input class="crm_input_text fll" id="', $prefix, '_', $key, '_value','" style="width: 70%;" type="text" value="', htmlspecialcharsbx($value), '" />';
			$valueTypeID = isset($datum['VALUE_TYPE']) ? $datum['VALUE_TYPE'] : '';
			echo '<select class="crm_input_select flr" id="', $prefix, '_', $key, '_value_type','">';
			foreach($typeInfo as $curValueTypeID => &$curValueType)
			{
				echo '<option value="', htmlspecialcharsbx($curValueTypeID), '"',
					($valueTypeID === $curValueTypeID ? 'selected="selected"' : ''),
					' >',
					(isset($curValueType['ABBR']) ? $curValueType['ABBR'] : $curValueTypeID),
					'</option>';
			}
			unset($curValueType);
			reset($typeInfo);
			echo '</select>';
		}
		unset($datum);

		echo '<div class="clb" style="margin-bottom: 10px;"></div>',
			'<a id="', $prefix, '_add_btn','" class="crm_people_cont_aqua_two">',
			'+&nbsp;', htmlspecialcharsbx(GetMessage("M_CRM_CONTACT_EDIT_BTN_ADD_{$typeName}")),
			'</a>';
	}
}

$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
?><div class="crm_toppanel">
	<div class="crm_filter">
		<span class="crm_cant_one_icon"></span>
		<?=htmlspecialcharsbx($formTitle)?>
	</div>
</div>
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;">
			<?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_SECTION_GENERAL'))?>
		</div>
		<hr/>
		<div class="crm_card">
			<div class="crm_card_image">
				<img id="<?=$prefix?>_photo" src="<?=$dataItem['IMAGE_ID'] > 0 ? htmlspecialcharsbx($dataItem['VIEW_IMAGE_URL']) : ''?>"/>
				<input type="hidden" id="<?=$prefix?>_photo_id" value="<?=htmlspecialcharsbx($dataItem['IMAGE_ID'])?>" />
			</div>
			<div class="crm_card_name tar">
				<input id="<?=$prefix?>_last_name" class="crm_input_text" type="text" value="<?=$entity['LAST_NAME']?>" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_LAST_NAME'))?>" />
				<div class="crm_input_desc"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_HINT_REQUIRED'))?></div>
				<input id="<?=$prefix?>_name" class="crm_input_text" type="text" value="<?=$entity['NAME']?>"  placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_NAME'))?>" />
				<div class="crm_input_desc"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_HINT_REQUIRED'))?></div>
			</div>
		</div>
		<hr/>
		<div class="crm_card">
			<div class="crm_card_name tar">
				<input id="<?=$prefix?>_second_name" class="crm_input_text flr" type="text" value="<?=$entity['SECOND_NAME']?>" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_SECOND_NAME'))?>" />
			</div>
			<div class="clearboth"></div>
		</div>
		<div class="clearboth"></div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_SECTION_PHONE'))?></div>
		<hr />
		<div class="crm_card" style="padding-bottom: 0;">
		<?__CrmMobileContactEditRenderMultiFields('PHONE', $entity['FM'], $multiFieldTypeInfos, $prefix);?>
		</div>
		<div class="clearboth"></div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_SECTION_EMAIL'))?></div>
		<hr />
		<div class="crm_card" style="padding-bottom: 0;">
		<?__CrmMobileContactEditRenderMultiFields('EMAIL', $entity['FM'], $multiFieldTypeInfos, $prefix);?>
		</div>
		<div class="clearboth"></div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=GetMessage('M_CRM_CONTACT_EDIT_SECTION_ADDRESS')?></div>
		<div class="clearboth"></div>
		<hr />
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['ADDRESS'])?></div>
		<div class="crm_card" style="padding-bottom: 0;">
			<textarea  id="<?=$prefix?>_address" class="crm_input_text"><?=$entity['ADDRESS']?></textarea>
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['ADDRESS_2'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_2" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_2']?>" />
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['CITY'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_city" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_CITY']?>" />
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['REGION'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_region" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_REGION']?>" />
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['PROVINCE'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_province" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_PROVINCE']?>" />
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['POSTAL_CODE'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_postal_code" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_POSTAL_CODE']?>" />
		</div>
		<div class="clearboth"></div>

		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx($addressLabels['COUNTRY'])?></div>
		<div class="crm_card">
			<input id="<?=$prefix?>_address_country" class="crm_input_text fll" type="text" value="<?=$entity['ADDRESS_COUNTRY']?>" />
		</div>
		<div class="clearboth"></div>
	</div>
	<?if($arResult['ENABLE_COMPANY']):?>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_COMPANY'))?>: <span id="<?=$prefix?>_company_name"><?=$entity['COMPANY_TITLE'] !== '' ? $entity['COMPANY_TITLE'] : htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_COMPANY_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_company_id" value="<?=$entity['COMPANY_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<?endif;?>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_TYPE'))?>: <span id="<?=$prefix?>_type_name"><?=$entity['TYPE_NAME'] !== '' ? $entity['TYPE_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_TYPE_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_type_id" value="<?=$entity['TYPE_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container aqua_style comments">
		<div class="crm_arrow">
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_RESPONSIBLE'))?>: <span id="<?=$prefix?>_assigned_by_name"><?=$entity['ASSIGNED_BY_FORMATTED_NAME'] !== '' ? $entity['ASSIGNED_BY_FORMATTED_NAME'] : htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_RESPONSIBLE_NOT_SPECIFIED'))?></span></div>
			<input type="hidden"  id="<?=$prefix?>_assigned_by_id" value="<?=$entity['ASSIGNED_BY_ID']?>" />
			<div class="clearboth"></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln" style="padding-bottom: 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_CONTACT_EDIT_FIELD_COMMENTS'))?></div>
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
					typeName: 'CONTACT',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'CONTACT_EDIT_URL_TEMPLATE' => $arParams['CONTACT_EDIT_URL_TEMPLATE'],
							'CONTACT_SHOW_URL_TEMPLATE' => $arParams['CONTACT_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmContactEditor.messages =
			{
				userSelectorOkButton: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_USER_SELECTOR_OK_BTN')?>',
				userSelectorCancelButton: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_USER_SELECTOR_CANCEL_BTN')?>',
				openPhotoAlbumMenuItem: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_OPEN_PHOTO_ALBUM_MENU_ITEM')?>',
				takePhotoMenuItem: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_TAKE_PHOTO_MENU_ITEM')?>'
			};

			var context = BX.CrmMobileContext.getCurrent();

			var editor = BX.CrmContactEditor.create(
				uid,
				{
					prefix: uid,
					containerID: uid,
					entityId: <?=CUtil::JSEscape($entityID)?>,
					title: '<?=CUtil::JSEscape($formTitle)?>',
					dispatcher: dispatcher,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					uploadUrl: '<?=CUtil::JSEscape($arResult['UPLOAD_URL'])?>',
					companySelectorUrl: '<?=CUtil::JSEscape($arResult['COMPANY_SELECTOR_URL'])?>',
					contactTypeSelectorUrl: '<?=CUtil::JSEscape($arResult['CONTACT_TYPE_SELECTOR_URL'])?>',
					contextId: '<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>',
					multiFields: <?=CUtil::PhpToJSObject($entity['FM'])?>,
					multiFieldInfos: <?=CUtil::PhpToJSObject($multiFieldTypeSettings)?>
				}
			);
			context.enableReloadOnPullDown(
				{
					pullText: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_PULL_TEXT')?>',
					downText: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_DOWN_TEXT')?>',
					loadText: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_LOAD_TEXT')?>'
				}
			);

			context.createButtons(
				{
					back:
					{
						type: 'right_text',
						style: 'custom',
						position: 'left',
						name: '<?=GetMessageJS('M_CRM_CONTACT_EDIT_CANCEL_BTN')?>',
						callback: context.createCloseHandler()
					},
					save:
					{
						type: 'right_text',
						style: 'custom',
						position: 'right',
						name: '<?=GetMessageJS("M_CRM_CONTACT_EDIT_{$mode}_BTN")?>',
						callback: editor.createSaveHandler()
					}
				}
			);
		}
	);
</script>
