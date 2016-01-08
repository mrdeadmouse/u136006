<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');

// Looking for 'tab_1' (Only single tab is supported).
$mainTab = null;
foreach($arResult['TABS'] as $tab):
	if($tab['id'] !== 'tab_1')
		continue;
	$mainTab = $tab;
	break;
endforeach;

//Take first tab if tab_1 is not found
if(!$mainTab):
	$mainTab = reset($arResult['TABS']);
endif;

if(!($mainTab && isset($mainTab['fields']) && is_array($mainTab['fields'])))
	return;

$hasRequiredFields = false;

$arUserSearchFields = array();
$arSections = array();
$sectionIndex = -1;
foreach($mainTab['fields'] as &$field):
	if(!is_array($field))
		continue;

	$fieldID = isset($field['id']) ? $field['id'] : '';

	if($field['type'] === 'section'):

		$arSections[] = array(
			'SECTION_FIELD' => $field,
			'SECTION_ID' => $fieldID,
			'SECTION_NAME' => isset($field['name']) ? $field['name'] : $fieldID,
			'FIELDS' => array()
		);
		$sectionIndex++;
		continue;
	endif;

	if($sectionIndex < 0):
		$arSections[] = array(
			'SECTION_FIELD' => null,
			'SECTION_ID' => '',
			'SECTION_NAME' => '',
			'FIELDS' => array()
		);
		$sectionIndex = 0;
	endif;

	$arSections[$sectionIndex]['FIELDS'][] = $field;
endforeach;
unset($field);

if($arParams['SHOW_SETTINGS'] && $arResult['OPTIONS']['settings_disabled'])
{
	$arResult['TABS_META'] = array();
	foreach($arResult['TABS'] as $tabID => $tabData)
	{
		$arResult['TABS_META'][$tabID] = array('id'=>$tabID, 'name'=>$tabData['name'], 'title'=>$tabData['title']);
		foreach($tabData['fields'] as $field)
		{
			$fieldInfo = array('id'=>$field['id'], 'name'=>$field['name'], 'type'=>$field['type']);
			if(isset($field['required']))
			{
				$fieldInfo['required'] = $field['required'];
			}
			if(isset($field['persistent']))
			{
				$fieldInfo['persistent'] = $field['persistent'];
			}
			$arResult['TABS_META'][$tabID]['fields'][$field['id']] = &$fieldInfo;
			unset($fieldInfo);
		}
	}
}

$formIDLower = strtolower($arParams['FORM_ID']);
$containerID = 'container_'.$formIDLower;
$undoContainerID = 'undo_container_'.$formIDLower;
$quickPanelConfig = isset($arParams['~QUICK_PANEL']) ? $arParams['~QUICK_PANEL'] : null;
if(is_array($quickPanelConfig) && !empty($quickPanelConfig))
{
	$panelGuid = $formIDLower.'_qpv';
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.quickpanelview',
		'',
		array(
			'GUID' => $panelGuid,
			'FORM_ID' => $arParams['FORM_ID'],
			'ENTITY_TYPE_NAME' => $quickPanelConfig['ENTITY_TYPE_NAME'],
			'ENTITY_ID' => $quickPanelConfig['ENTITY_ID'],
			'ENTITY_FIELDS' => $quickPanelConfig['ENTITY_FIELDS'],
			'ENABLE_INSTANT_EDIT' => isset($quickPanelConfig['ENABLE_INSTANT_EDIT']) ? $quickPanelConfig['ENABLE_INSTANT_EDIT'] : false,
			'INSTANT_EDITOR_ID' => isset($quickPanelConfig['INSTANT_EDITOR_ID']) ? $quickPanelConfig['INSTANT_EDITOR_ID'] : '',
			'SERVICE_URL' => isset($quickPanelConfig['SERVICE_URL']) ? $quickPanelConfig['SERVICE_URL'] : '',
			'SHOW_SETTINGS' => 'Y'
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$mode = isset($arParams['MODE']) ? strtoupper($arParams['MODE']) : 'EDIT';
$isVisible = $mode !== 'VIEW' || !isset($arResult['OPTIONS']['show_in_view_mode']) || $arResult['OPTIONS']['show_in_view_mode'] === 'Y';
?><div id="<?=$undoContainerID?>"></div>
<div id="<?=$containerID?>" class="bx-interface-form bx-crm-edit-form"<?=!$isVisible ? ' style="display:none;"' : ''?>>
<script type="text/javascript">
	var bxForm_<?=$arParams['FORM_ID']?> = null;
</script><?
if($arParams['SHOW_FORM_TAG']):
?><form name="form_<?=$arParams['FORM_ID']?>" id="form_<?=$arParams["FORM_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
	<?=bitrix_sessid_post();?>
	<input type="hidden" id="<?=$arParams["FORM_ID"]?>_active_tab" name="<?=$arParams["FORM_ID"]?>_active_tab" value="<?=htmlspecialcharsbx($arResult["SELECTED_TAB"])?>"><?
endif;

$canCreateUserField = (
	CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
	&& (!isset($arParams['ENABLE_USER_FIELD_CREATION']) || $arParams['ENABLE_USER_FIELD_CREATION'] !== 'N')
);
$canCreateSection = (
	CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
	&& (!isset($arParams['ENABLE_SECTION_CREATION']) || $arParams['ENABLE_SECTION_CREATION'] !== 'N')
);
$title = isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '';
if(is_string($title) && $title !== ''):
?><div class="crm-title-block">
	<span class="ctm-title-text"><?=$title?></span>
	<span id="<?=$arParams['FORM_ID']?>_menu" class="crm-toolbar-btn crm-title-btn">
		<span class="crm-toolbar-btn-icon"></span>
	</span>
</div><?
endif;
$sectionWrapperID = $formIDLower.'_section_wrapper';
?><div id="<?=$sectionWrapperID?>" class="crm-offer-main-wrap"><?
foreach($arSections as &$arSection):
	$sectionNodePrefix = strtolower($arSection['SECTION_ID']);
	?><table id="<?=$sectionNodePrefix?>_contents" class="crm-offer-info-table<?=$mode === 'VIEW' ? ' crm-offer-main-info-text' : ''?>"><tbody><?

	$required = (isset($arSection['SECTION_FIELD']['required']) && $arSection['SECTION_FIELD']['required'] === true) ? true : false;
	?><tr id="<?=$arSection['SECTION_ID']?>">
		<td colspan="5">
			<div class="crm-offer-title">
				<span class="crm-offer-drg-btn"></span>
				<span class="crm-offer-title-text"><?=htmlspecialcharsbx($arSection['SECTION_NAME'])?></span>
				<span class="crm-offer-title-set-wrap"><?
					if($mode === 'EDIT'):
					?><span id="<?=$sectionNodePrefix?>_edit" class="crm-offer-title-edit"></span><?
					endif;
					?><span id="<?=$sectionNodePrefix?>_delete" class="crm-offer-title-del"></span>
				</span>
			</div>
		</td>
	</tr><?
	$fieldCount = 0;
	foreach($arSection['FIELDS'] as &$field):
		$visible = isset($field['visible']) ? (bool)$field['visible'] : true;
		$dragDropType = $field['type'] === 'lhe' ? 'lhe' : '';
		$containerClassName = $field['type'] === 'address' ? 'crm-offer-row crm-offer-info-address-row' : 'crm-offer-row';
		?><tr id="<?=strtolower($field["id"])?>_wrap"<?=$visible ? '' : 'style="display:none;"'?> class="<?=$containerClassName?>" data-dragdrop-context="field" data-dragdrop-id="<?=$field["id"]?>"<?=$dragDropType !== '' ? ' data-dragdrop-type="'.$dragDropType.'"' : ''?>>
			<td class="crm-offer-info-drg-btn"><span class="crm-offer-drg-btn"></span></td><?
		$required = isset($field['required']) && $field['required'] === true;
		$persistent = isset($field['persistent']) && $field['persistent'] === true;

		//default attributes
		if(!is_array($field['params']))
			$field['params'] = array();

		if($field['type'] == '' || $field['type'] == 'text')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '30';
		}
		elseif($field['type'] == 'textarea')
		{
			if($field['params']['cols'] == '')
				$field['params']['cols'] = '40';

			if($field['params']['rows'] == '')
				$field['params']['rows'] = '3';
		}
		elseif($field['type'] == 'date')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}
		elseif($field['type'] == 'date_short')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}

		$params = '';
		if(is_array($field['params']) && $field['type'] <> 'file')
			foreach($field['params'] as $p=>$v)
				$params .= ' '.$p.'="'.$v.'"';

		$val = isset($field['value']) ? $field['value'] : $arParams['~DATA'][$field['id']];

		if($field['type'] === 'vertical_container'):
			?><td class="crm-offer-info-right" colspan="4">
			<div class="crm-offer-editor-title">
				<div class="crm-offer-editor-title-contents-wapper">
					<?if($required):?><span class="required">*</span><?endif;?>
					<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
				</div>
			</div>
			<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?=$val?></div>
			<span class="crm-offer-edit-btn-wrap"><?
				if(!$required && !$persistent):
				?><span class="crm-offer-item-del"></span><?
				endif;
				?><span class="crm-offer-item-edit"></span>
			</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif($field['type'] === 'lhe'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$params['id'] = strtolower("{$arParams['FORM_ID']}_{$field['id']}");

			CModule::IncludeModule('fileman');
			$lhe = new CLightHTMLEditor();
			?><td class="crm-offer-info-right" colspan="4">
				<div class="crm-offer-editor-title">
					<div class="crm-offer-editor-title-contents-wapper">
						<?if($required):?><span class="required">*</span><?endif;?>
						<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
					</div>
				</div>
				<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?$lhe->Show($params);?></div>
				<span class="crm-offer-edit-btn-wrap"><?
					if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
					endif;
					?><span class="crm-offer-item-edit"></span>
				</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif($field['type'] === 'address'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) ? $params['DATA'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			?><td class="crm-offer-info-left" colspan="2">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
				<div class="crm-offer-info-data-wrap">
					<table class="crm-offer-info-table"><tbody>
					<tr>
						<td class="crm-offer-info-left"></td>
						<td class="crm-offer-info-right"></td>
					</tr><?
					$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
					foreach($addressData as $itemKey => $item):
						$itemValue = isset($item['VALUE']) ? $item['VALUE'] : '';
						$itemName = isset($item['NAME']) ? $item['NAME'] : $itemKey;
						$itemLocality = isset($item['LOCALITY']) ? $item['LOCALITY'] : null;
						?><tr>
							<td class="crm-offer-info-left">
								<span class="crm-offer-info-label-alignment"></span>
								<span class="crm-offer-info-label"><?=$addressLabels[$itemKey]?>:</span>
							</td>
							<td class="crm-offer-info-right">
								<div class="crm-offer-info-data-wrap"><?
									if(is_array($itemLocality)):
										$searchInputID = "{$arParams['FORM_ID']}_{$itemName}";
										$dataInputID = "{$arParams['FORM_ID']}_{$itemLocality['NAME']}";
										?><input class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$itemName?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" />
										<input type="hidden" id="<?=$dataInputID?>" name="<?=$itemLocality['NAME']?>" value="<?=htmlspecialcharsbx($itemLocality['VALUE'])?>"/>
										<script type="text/javascript">
											BX.ready(
												function()
												{
													BX.CrmLocalitySearchField.create(
														"<?=$searchInputID?>",
														{
															localityType: "<?=$itemLocality['TYPE']?>",
															serviceUrl: "<?=$addressServiceUrl?>",
															searchInputId: "<?=$searchInputID?>",
															dataInputId: "<?=$dataInputID?>"
														}
													);
												}
											);
										</script><?
									else:
										if(isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']):
											?><textarea class="bx-crm-edit-text-area" name="<?=htmlspecialcharsEx($itemName)?>"><?=$itemValue?></textarea><?
										else:
											?><input class="crm-offer-item-inp" name="<?=htmlspecialcharsEx($itemName)?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" /><?
										endif;
									endif;
								?></div>
							</td>
						</tr><?
					endforeach;
				?></tbody></table>
				</div>
			</td><!-- "crm-offer-info-left" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
			?></td>
			<td class="crm-offer-last-td"></td><?
		else:
			?><td class="crm-offer-info-left">
				<div class="crm-offer-info-label-wrap"><span class="crm-offer-info-label-alignment"></span><?if($required):?><span class="required">*</span><?endif;?><span class="crm-offer-info-label">
					<?if(!in_array($field['type'], array('checkbox', 'vertical_checkbox'))):?><?=htmlspecialcharsEx($field['name'])?>:<?endif;?>
				</span></div>
			</td><?
			?><td class="crm-offer-info-right"><div class="crm-offer-info-data-wrap"><?
			$advancedInfoHTML = '';
			switch($field['type']):
					case 'label':
						echo '<div id="'.$field["id"].'" class="crm-fld-block-readonly">', htmlspecialcharsEx($val), '</div>';
						break;
					case 'custom':
						$isUserField = strpos($field['id'], 'UF_') === 0;
						$wrap = isset($field['wrap']) && $field['wrap'] === true;
						if($isUserField):
							?><div class="bx-crm-edit-user-field"><?
						elseif($wrap):
							?><div class="bx-crm-edit-field"><?
						endif;

						echo $val;
						if($isUserField || $wrap):
							?></div><?
						endif;
						break;
					case 'checkbox':
					case 'vertical_checkbox':
						$chkBxId = strtolower($field['id']).'_chbx';
						?><input type="hidden" name="<?=$field['id']?>" value="N">
						<input class="crm-offer-checkbox" type="checkbox" id="<?=$chkBxId?>" name="<?=$field['id']?>" value="Y"<?=($val == 'Y'? ' checked':'')?><?=$params?>/>
						<label class="crm-offer-label" for="<?=$chkBxId?>"><?=htmlspecialcharsEx($field['name'])?></label><?
						break;
					case 'textarea':
						?><textarea class="bx-crm-edit-text-area" name="<?=$field["id"]?>"<?=$params?>><?=$val?></textarea><?
						break;
					case 'list':
						?><select class="crm-item-table-select" name="<?=$field["id"]?>"<?=$params?>><?
							if(is_array($field["items"])):
								if(!is_array($val))
									$val = array($val);
								foreach($field["items"] as $k=>$v):
									?><option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array($k, $val)? ' selected':'')?>><?=htmlspecialcharsEx($v)?></option><?
								endforeach;
							endif;
							?></select><?
						break;
					case 'file':
						?><div class="bx-crm-edit-file-field"><?
							$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
							foreach($arDefParams as $k=>$v)
								if(!array_key_exists($k, $field["params"]))
									$field["params"][$k] = $v;

							echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
							if($val <> '')
								echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);
							?></div><?
						break;
					case 'date':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=htmlspecialcharsbx($val)?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: true, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_short':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=htmlspecialcharsbx($val)?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: false, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_link':
						$dataID = "{$arParams['FORM_ID']}_{$field['id']}_DATA";
						$viewID = "{$arParams['FORM_ID']}_{$field['id']}_VIEW";
						?><span id="<?=htmlspecialcharsbx($viewID)?>" class="bx-crm-edit-datetime-link"><?=htmlspecialcharsEx($val !== '' ? $val : GetMessage('interface_form_set_datetime'))?></span>
						<input id="<?=htmlspecialcharsbx($dataID)?>" type="hidden" name="<?=htmlspecialcharsbx($field['id'])?>" value="<?=htmlspecialcharsbx($val)?>" <?=$params?>>
						<script type="text/javascript">BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::addslashes($dataID)?>'), BX('<?=CUtil::addslashes($viewID)?>'), { showTime: false }); });</script><?
						break;
					case 'intranet_user_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$rsUser = CUser::GetByID($val);
							if($arUser = $rsUser->Fetch()):
								$params['USER'] = $arUser;
							endif;
							?><input type="text" class="crm-offer-item-inp" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
						<input type="hidden" name="<?=htmlspecialcharsbx($params['INPUT_NAME'])?>" value="<?=htmlspecialcharsbx($val)?>"><?
							$arUserSearchFields[] = $params;
							$APPLICATION->IncludeComponent(
								'bitrix:intranet.user.selector.new',
								'',
								array(
									'MULTIPLE' => 'N',
									'NAME' => $params['NAME'],
									'INPUT_NAME' => $params['SEARCH_INPUT_NAME'],
									'POPUP' => 'Y',
									'SITE_ID' => SITE_ID,
									'NAME_TEMPLATE' => $params['NAME_TEMPLATE']
								),
								null,
								array('HIDE_ICONS' => 'Y')
							);
						endif;
						break;
					case 'crm_entity_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							$entityID = isset($params['INPUT_VALUE']) ? intval($params['INPUT_VALUE']) : 0;
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo($entityType, $entityID);
							$advancedInfoHTML = CCrmEntitySelectorHelper::PrepareEntityAdvancedInfoHTML($entityType, $entityInfo, array('CONTAINER_ID' => $containerID.'_descr'));
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper"><?
								if($entityID > 0):
									?><a href="<?=htmlspecialcharsbx($entityInfo['URL'])?>" target="_blank" class="bx-crm-entity-info-link"><?=htmlspecialcharsEx($entityInfo['TITLE'])?></a><span class="crm-element-item-delete"></span><?
								endif;
								?></div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=htmlspecialcharsbx($entityID)?>" /><?
							if($newDataInputName !== ''):
							?><input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="" /><?
							endif;
							?><div class="bx-crm-entity-buttons-wrapper">
							<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span><?
							if($newDataInputName !== ''):
							?> <span class="bx-crm-edit-crm-entity-add"><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?></span><?
							endif;
						?></div>
						</div><?
							$serviceUrl = '';
							$actionName = '';
							$dialogSettings = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel')
							);
							if($entityType === 'CONTACT')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$actionName = 'SAVE_CONTACT';

								$dialogSettings['title'] = GetMessage('interface_form_add_contact_dlg_title');
								$dialogSettings['lastNameTitle'] = GetMessage('interface_form_add_contact_fld_last_name');
								$dialogSettings['nameTitle'] = GetMessage('interface_form_add_contact_fld_name');
								$dialogSettings['secondNameTitle'] = GetMessage('interface_form_add_contact_fld_second_name');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_contact_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_contact_fld_phone');
								$dialogSettings['exportTitle'] = GetMessage('interface_form_add_contact_fld_export');
							}
							elseif($entityType === 'COMPANY')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$actionName = 'SAVE_COMPANY';

								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['companyTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['industryTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_conpany_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_company_fld_phone');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE'));
								$dialogSettings['industryItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'));
							}
							elseif($entityType === 'DEAL')
							{
								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['dealTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['dealPriceTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('DEAL_TYPE'));
							}
							elseif($entityType === 'QUOTE')
							{
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
							}
							?><script type="text/javascript">
							BX.ready(
									function()
									{
										var entitySelectorId = CRM.Set(
												BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
												'<?=CUtil::JSEscape($selectorID)?>',
												'',
											<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PreparePopupItems($entityType, false, isset($params['NAME_TEMPLATE']) ? $params['NAME_TEMPLATE'] : \Bitrix\Crm\Format\PersonNameFormatter::getFormat()))?>,
												false,
												false,
												['<?=CUtil::JSEscape(strtolower($entityType))?>'],
											<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
												true
										);

										BX.CrmEntityEditor.messages =
										{
											'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
											'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
											'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
											'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>'
										};

										BX.CrmEntityEditor.create(
												'<?=CUtil::JSEscape($editorID)?>',
												{
													'typeName': '<?=CUtil::JSEscape($entityType)?>',
													'containerId': '<?=CUtil::JSEscape($containerID)?>',
													'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
													'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
													'entitySelectorId': entitySelectorId,
													'serviceUrl': '<?= CUtil::JSEscape($serviceUrl) ?>',
													'actionName': '<?= CUtil::JSEscape($actionName) ?>',
													'dialog': <?=CUtil::PhpToJSObject($dialogSettings)?>
												}
										);
									}
							);
						</script><?
						endif;
						break;
					case 'crm_client_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params))
						{
							$entityID = $inputValue = isset($params['INPUT_VALUE']) ? $params['INPUT_VALUE'] : '';
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							switch (substr($entityID, 0, 2))
							{
								case 'C_':
									$valEntityType = 'contact';
									break;
								case 'CO':
									$valEntityType = 'company';
									break;
								default:
									$valEntityType = '';
							}
							$entityID = intval(substr($entityID, intval(strpos($entityID, '_')) + 1));
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$createEntitiesBlockID = "{$arParams['FORM_ID']}_CREATE_ENTITIES_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$addContactButtonID = "{$arParams['FORM_ID']}_ADD_CONTACT_BTN_{$field['id']}";
							$addCompanyButtonID = "{$arParams['FORM_ID']}_ADD_COMPANY_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo($valEntityType, $entityID);
							$advancedInfoHTML = CCrmEntitySelectorHelper::PrepareEntityAdvancedInfoHTML($valEntityType, $entityInfo, array('CONTAINER_ID' => $containerID.'_descr'));
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper">
								<? if($entityID > 0): ?>
									<a href="<?=htmlspecialcharsbx($entityInfo['URL'])?>" target="_blank" class="bx-crm-entity-info-link"><?=htmlspecialcharsEx($entityInfo['TITLE'])?></a><span class="crm-element-item-delete"></span>
								<? endif;?>
							</div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=htmlspecialcharsbx($inputValue)?>" />
							<? if($newDataInputName !== ''): ?>
								<input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="" />
							<? endif; ?>
							<!--<div class="bx-crm-entity-buttons-wrapper">-->
								<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span>
								<? if($newDataInputName !== ''): ?>
									<br>
									<span id="<?=htmlspecialcharsbx($createEntitiesBlockID)?>" class="bx-crm-edit-description"<?=($entityID>0)?' style="display: none;"':''?>>
									<span><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?> </span>
									<span id="<?=htmlspecialcharsbx($addCompanyButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_company'))?></span>
									<span><?= htmlspecialcharsbx(' '.GetMessage('interface_form_add_btn_or')).' '?></span>
									<span id="<?=htmlspecialcharsbx($addContactButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_contact'))?></span>
									</span>
								<? endif; ?>
							<!--</div>-->
							</div><?
							$dialogSettings['CONTACT'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_contact_dlg_title'),
								'lastNameTitle' => GetMessage('interface_form_add_contact_fld_last_name'),
								'nameTitle' => GetMessage('interface_form_add_contact_fld_name'),
								'secondNameTitle' => GetMessage('interface_form_add_contact_fld_second_name'),
								'emailTitle' => GetMessage('interface_form_add_contact_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_contact_fld_phone'),
								'exportTitle' => GetMessage('interface_form_add_contact_fld_export')
						);
							$dialogSettings['COMPANY'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_company_dlg_title'),
								'titleTitle' => GetMessage('interface_form_add_company_fld_title_name'),
								'companyTypeTitle' => GetMessage('interface_form_add_conpany_fld_company_type'),
								'industryTitle' => GetMessage('interface_form_add_company_fld_industry'),
								'emailTitle' => GetMessage('interface_form_add_conpany_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_company_fld_phone'),
								'companyTypeItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE')),
								'industryItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'))
							);
							?><script type="text/javascript">
							BX.ready(
								function()
								{
									var entitySelectorId = CRM.Set(
										BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
										'<?=CUtil::JSEscape($selectorID)?>',
										'',
										<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PreparePopupItems($entityType, true, isset($params['NAME_TEMPLATE']) ? $params['NAME_TEMPLATE'] : ''))?>,
										false,
										false,
										<?=CUtil::PhpToJsObject($entityType)?>,
										<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
										true
									);

									BX.CrmEntityEditor.messages =
									{
										'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
										'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
										'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
										'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>'
									};

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID.'_C')?>',
										{
											'typeName': 'CONTACT',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addContactButtonID)?>',
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'actionName': 'SAVE_CONTACT',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['CONTACT'])?>
										}
									);

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID).'_CO'?>',
										{
											'typeName': 'COMPANY',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addCompanyButtonID)?>',
											'buttonChangeIgnore': true,
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'actionName': 'SAVE_COMPANY',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['COMPANY'])?>
										}
									);
								}
							);
						</script><?
						}
						break;
					case 'crm_locality_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						$searchInputID = "{$arParams['FORM_ID']}_{$field['id']}";
						$dataInputID = "{$arParams['FORM_ID']}_{$params['DATA_INPUT_NAME']}";
						?><input type="text" class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$field["id"]?>"  name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>/>
						<input type="hidden" id="<?=$dataInputID?>" name="<?=$params['DATA_INPUT_NAME']?>" value="<?=htmlspecialcharsbx($params['DATA_VALUE'])?>"/>
						<script type="text/javascript">
							BX.ready(
								function()
								{
									BX.CrmLocalitySearchField.create(
										"<?=$searchInputID?>",
										{
											localityType: "<?=$params['LOCALITY_TYPE']?>",
											serviceUrl: "<?=$params['SERVICE_URL']?>",
											searchInputId: "<?=$searchInputID?>",
											dataInputId: "<?=$dataInputID?>"
										}
									);
								}
							);
						</script>
						<?
						break;
					default:
						?><input type="text" class="crm-offer-item-inp" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>><?
				endswitch;
			?></div><?
			if ($advancedInfoHTML !== '')
				echo $advancedInfoHTML;
			?></td><!-- "crm-offer-info-right" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
				?></td>
			<td class="crm-offer-last-td"></td><?
		endif;
		?></tr><?
		$fieldCount++;
	endforeach;
	unset($field);
	?><tr id="<?=$sectionNodePrefix?>_buttons" style="visibility: hidden;">
		<td class="crm-offer-info-drg-btn"></td>
		<td class="crm-offer-info-left"></td>
		<td class="crm-offer-info-right">
			<div class="crm-offer-item-link-wrap">
				<? if ($canCreateUserField): ?>
				<span id="<?=$sectionNodePrefix?>_add_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_field')?></span>
				<? endif; ?>
				<? if ($canCreateSection): ?>
				<span id="<?=$sectionNodePrefix?>_add_section" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_section')?></span>
				<? endif; ?>
				<span id="<?=$sectionNodePrefix?>_restore_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_restore_field')?></span>
			</div>
		</td>
		<td class="crm-offer-info-right-btn"></td>
		<td class="crm-offer-last-td"></td>
	</tr>
	</tbody></table><!-- "crm-offer-info-table" --><?
endforeach;
unset($arSection);

$arFieldSets = isset($arParams['FIELD_SETS']) ? $arParams['FIELD_SETS'] : array();
if(!empty($arFieldSets)):
	foreach($arFieldSets as &$arFieldSet):
		$html = isset($arFieldSet['HTML']) ? $arFieldSet['HTML'] : '';
		if($html === '')
			continue;
		?><div class="bx-crm-view-fieldset">
		<h2 class="bx-crm-view-fieldset-title"><? if (isset($arFieldSet['REQUIRED']) && $arFieldSet['REQUIRED'] === true): ?><span class="required">*</span><? endif; ?><?=isset($arFieldSet['NAME']) ? htmlspecialcharsbx($arFieldSet['NAME']) : ''?></h2>
			<div class="bx-crm-view-fieldset-content">
				<table class="bx-crm-view-fieldset-content-table">
					<tbody>
					<tr>
						<td class="bx-field-value"><?=$html?></td>
					</tr>
				</tbody>
			</table>
		</div>
		</div><?
	endforeach;
	unset($arFieldSet);
endif;
?></div><!-- "crm-offer-main-wrap" --><?

if(isset($arParams['~BUTTONS'])):
	if($arParams['~BUTTONS']['standard_buttons'] !== false):
		?><div class="webform-buttons ">
			<span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndView" id="<?=$arParams["FORM_ID"]?>_saveAndView" value="<?=htmlspecialcharsbx(GetMessage('interface_form_save_and_view'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_save_and_view_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		if(isset($arParams['IS_NEW']) && $arParams['IS_NEW'] === true):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndAdd" id="<?=$arParams["FORM_ID"]?>_saveAndAdd" value="<?=htmlspecialcharsbx(GetMessage('interface_form_save_and_add'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_save_and_add_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		else:
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="apply" id="<?=$arParams["FORM_ID"]?>_apply" value="<?=htmlspecialcharsbx(GetMessage('interface_form_apply'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_apply_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx(GetMessage('interface_form_cancel'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_cancel_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		?></div><?
	endif;
	if(isset($arParams['~BUTTONS']['custom_html'])):
		echo $arParams['~BUTTONS']['custom_html'];
	endif;
endif;

if($arParams['SHOW_FORM_TAG']):
	?></form><?
endif;

if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div style="display:none">

	<div id="form_settings_<?=$arParams["FORM_ID"]?>">
		<table width="100%">
			<tr class="section">
				<td colspan="2"><?echo GetMessage("interface_form_tabs")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<select style="min-width:150px;" name="tabs" size="10" ondblclick="this.form.tab_edit_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.OnSettingsChangeTab()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="tab_up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabEdit()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_del_btn" value="<?echo GetMessage("intarface_form_del")?>" title="<?echo GetMessage("intarface_form_del_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabDelete()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="section">
				<td colspan="2"><?echo GetMessage("intarface_form_fields")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_available")?></div>
								<select style="min-width:150px;" name="all_fields" multiple size="12" ondblclick="this.form.add_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="add_btn" value="&gt;" title="<?echo GetMessage("intarface_form_add_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="del_btn" value="&lt;" title="<?echo GetMessage("intarface_form_del_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsDelete()"></div>
							</td>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_on_tab")?></div>
								<select style="min-width:150px;" name="fields" multiple size="12" ondblclick="this.form.del_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_sect")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_field")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldEdit()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>

</div><?
endif; //$GLOBALS['USER']->IsAuthorized()

$variables = array(
	"mess"=>array(
		"collapseTabs"=>GetMessage("interface_form_close_all"),
		"expandTabs"=>GetMessage("interface_form_show_all"),
		"settingsTitle"=>GetMessage("intarface_form_settings"),
		"settingsSave"=>GetMessage("interface_form_save"),
		"tabSettingsTitle"=>GetMessage("intarface_form_tab"),
		"tabSettingsSave"=>"OK",
		"tabSettingsName"=>GetMessage("intarface_form_tab_name"),
		"tabSettingsCaption"=>GetMessage("intarface_form_tab_title"),
		"fieldSettingsTitle"=>GetMessage("intarface_form_field"),
		"fieldSettingsName"=>GetMessage("intarface_form_field_name"),
		"sectSettingsTitle"=>GetMessage("intarface_form_sect"),
		"sectSettingsName"=>GetMessage("intarface_form_sect_name"),
	),
	"ajax"=>array(
		"AJAX_ID"=>$arParams["AJAX_ID"],
		"AJAX_OPTION_SHADOW"=>($arParams["AJAX_OPTION_SHADOW"] == "Y"),
	),
	"settingWndSize"=>CUtil::GetPopupSize("InterfaceFormSettingWnd"),
	"tabSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormTabSettingWnd", array('width'=>400, 'height'=>200)),
	"fieldSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormFieldSettingWnd", array('width'=>400, 'height'=>150)),
	"component_path"=>$component->GetRelativePath(),
	"template_path"=>$this->GetFolder(),
	"sessid"=>bitrix_sessid(),
	"current_url"=>$APPLICATION->GetCurPageParam("", array("bxajaxid", "AJAX_CALL")),
	"GRID_ID"=>$arParams["THEME_GRID_ID"],
);

?><script type="text/javascript">
var formSettingsDialog<?=$arParams["FORM_ID"]?>;
bxForm_<?=$arParams["FORM_ID"]?> = new BxCrmInterfaceForm('<?=$arParams["FORM_ID"]?>', <?=CUtil::PhpToJsObject(array_keys($arResult["TABS"]))?>);
bxForm_<?=$arParams["FORM_ID"]?>.vars = <?=CUtil::PhpToJsObject($variables)?>;<?
if($arParams["SHOW_SETTINGS"] == true):
	?>bxForm_<?=$arParams["FORM_ID"]?>.oTabsMeta = <?=CUtil::PhpToJsObject($arResult["TABS_META"])?>;
bxForm_<?=$arParams["FORM_ID"]?>.oFields = <?=CUtil::PhpToJsObject($arResult["AVAILABLE_FIELDS"])?>;<?
endif;

if($arResult["OPTIONS"]["expand_tabs"] == "Y"):
	?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.ToggleTabs(true);});<?
endif;
?>bxForm_<?=$arParams["FORM_ID"]?>.Initialize();
bxForm_<?=$arParams["FORM_ID"]?>.EnableSigleSubmit(true);
</script><?

?></div><!-- bx-interface-form --><?
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmFormSectionSetting.messages =
			{
				deleteButton: "<?=CUtil::JSEscape(GetMessage('intarface_form_del'))?>",
				createTextFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_string_field_menu_item'))?>",
				createDoubleFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_double_field_menu_item'))?>",
				createBooleanFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_boolean_field_menu_item'))?>",
				createDatetimeFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_datetime_field_menu_item'))?>",
				createSectionMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_section_menu_item'))?>",
				sectionTitlePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_section_ttl_placeholder'))?>",
				sectionDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_title'))?>",
				sectionDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldSetting.messages =
			{
				saveButton: "<?=CUtil::JSEscape(GetMessage('interface_form_save'))?>",
				cancelButton: "<?=CUtil::JSEscape(GetMessage('interface_form_cancel'))?>",
				deleteButton: "<?=CUtil::JSEscape(GetMessage('interface_form_hide'))?>",
				fieldNamePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_field_name_placeholder'))?>",
				fieldDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_title'))?>",
				fieldDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldRenderer.messages =
			{
				addSectionButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_section'))?>",
				addFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_field'))?>",
				restoreFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_restore_field'))?>"
			};

			BX.CrmFormSettingManager.messages =
			{
				newFieldName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_field_name'))?>",
				newSectionName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_section_name'))?>",
				resetMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_reset_menu_item'))?>",
				saveForAllMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_save_for_all_menu_item'))?>",
				sectionHasRequiredFields: "<?=CUtil::JSEscape(GetMessage('interface_form_section_has_required_fields'))?>",
				saved: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_saved'))?>",
				undo: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_undo_change'))?>"
			};

			var isSettingsApplied = <?=$arResult['OPTIONS']['settings_disabled'] !== 'Y' ? 'true' : 'false'?>;

			BX.CrmEditFormManager.create(
				"<?=$formIDLower?>",
				{
					formId: "<?=$arParams['FORM_ID']?>",
					form: bxForm_<?=$arParams["FORM_ID"]?>,
					mode: <?=strtoupper($arParams["MODE"]) === 'VIEW' ? 'BX.CrmFormMode.view' : 'BX.CrmFormMode.edit'?>,
					sectionWrapperId: "<?=$sectionWrapperID?>",
					undoContainerId: "<?=$undoContainerID?>",
					tabId: "tab_1",
					metaData: window["bxForm_<?=$arParams['FORM_ID']?>"]["oTabsMeta"],
					hiddenMetaData: isSettingsApplied ? window["bxForm_<?=$arParams['FORM_ID']?>"]["oFields"] : [],
					isSettingsApplied: isSettingsApplied,
					canCreateUserField: <?=($canCreateUserField ? 'true' : 'false')?>,
					canCreateSection: <?=($canCreateSection ? 'true' : 'false')?>,
					canSaveSettingsForAll: <?=CCrmAuthorizationHelper::CanEditOtherSettings() ? 'true' : 'false'?>,
					userFieldEntityId: "<?=isset($arParams['USER_FIELD_ENTITY_ID']) ? $arParams['USER_FIELD_ENTITY_ID'] : ''?>",
					userFieldServiceUrl: "<?='/bitrix/components/bitrix/crm.config.fields.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()?>",
					serverTime: "<?=time() + CTimeZone::GetOffset()?>",
					enableQuickPanel: true,
					quickPanelConfig: <?=CUtil::PhpToJSObject($quickPanelConfig)?>
				}
			);
		}
	);
</script><?
if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;
