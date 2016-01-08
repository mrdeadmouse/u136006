<?
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->AddHeadScript('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js');

$ajaxUrl = $this->__component->GetPath() . '/ajax.php?' .
	http_build_query(
		array(
			'lang'                => LANGUAGE_ID,
			'SITE_ID'             => $arParams["SITE_ID"],
			'GROUP_SITE_ID'       => $GLOBALS["GROUP_SITE_ID"],
			'IS_EXTRANET'         => ($bExtranet ? "Y" : "N"),
			'SHOW_INACTIVE_USERS' => $arParams['SHOW_INACTIVE_USERS'],
			'SHOW_EXTRANET_USERS' => $arParams['SHOW_EXTRANET_USERS'],
			'EX_GROUP'            => !empty($arParams["EX_GROUP"]) ? $arParams["EX_GROUP"] : '',
			'nt'                  => $arParams["NAME_TEMPLATE"],
			'sl'                  => $arParams["SHOW_LOGIN"],
			'SHOW_USERS'          => ($arParams['SHOW_STRUCTURE_ONLY'] == 'Y' ? 'N' : 'Y'),
		)
	);
?>
<script type="text/javascript">
	BX.message({
		INTRANET_EMP_HEAD : '<?=GetMessageJS('INTRANET_EMP_HEAD')?>',
		INTRANET_EMP_WAIT : '<?=GetMessageJS('INTRANET_EMP_WAIT')?>'
	});
	IntranetUsers.lastUsers = <?=CUtil::PhpToJSObject($arResult["LAST_USERS_IDS"]); ?>;

	var O_<?=$arResult["NAME"]; ?> = new IntranetUsers("<?=$arResult["NAME"]; ?>", <?=($arParams["MULTIPLE"] == "Y" ? "true" : "false"); ?>, <?=($arParams["SUBORDINATE_ONLY"] == "Y" ? "true" : "false"); ?>);
	O_<?=$arResult["NAME"]; ?>.ajaxUrl = '<?=$ajaxUrl; ?>';
	O_<?=$arResult["NAME"]; ?>.arFixed = <?=CUtil::PhpToJSObject($arResult['FIXED_USERS']); ?>;

	<?php foreach($arResult["CURRENT_USERS"] as $user):?>
		O_<?php echo $arResult["NAME"]?>.arSelected[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["~NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["~WORK_POSITION"])?>", photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
		IntranetUsers.arEmployeesData[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["~NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["~WORK_POSITION"])?>", photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
	<?php endforeach?>

	<?php foreach($arResult["LAST_USERS"] as $user):?>
		IntranetUsers.arEmployeesData[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["~NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["~WORK_POSITION"])?>", photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
	<?php endforeach?>

	BX.ready(function() {
		<?php if (strlen($arParams["FORM_NAME"]) > 0 && strlen($arParams["INPUT_NAME"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = document.forms["<?php echo CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
		<?php elseif(strlen($arParams["INPUT_NAME"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>");
		<?php else:?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo $arResult["NAME"]?>_user_input");
		<?php endif?>

		<?php if (strlen($arParams["ON_CHANGE"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.onChange = <?php echo CUtil::JSEscape($arParams["ON_CHANGE"])?>;
			O_<?php echo $arResult["NAME"]?>.onChange(O_<?php echo $arResult["NAME"]?>.arSelected);
		<?php endif?>

		<?php if (strlen($arParams["ON_SELECT"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.onSelect= <?php echo CUtil::JSEscape($arParams["ON_SELECT"])?>;
		<?php elseif (strlen($arParams["ON_SECTION_SELECT"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.onSectionSelect= <?php echo CUtil::JSEscape($arParams["ON_SECTION_SELECT"])?>;
		<?php endif?>

		BX.bind(O_<?php echo $arResult["NAME"]?>.searchInput, "keyup", BX.proxy(O_<?php echo $arResult["NAME"]?>.search, O_<?php echo $arResult["NAME"]?>));
		BX.bind(O_<?php echo $arResult["NAME"]?>.searchInput, "focus", BX.proxy(O_<?php echo $arResult["NAME"]?>._onFocus, O_<?php echo $arResult["NAME"]?>));
	});
</script>

<div id="<?php echo $arParams["NAME"]?>_selector_content" class="finder-box<?php if ($arParams["MULTIPLE"] == "Y"):?> finder-box-multiple<?php endif?>"<?php echo $arParams["POPUP"] == "Y" ? " style=\"display: none;\"" : ""?>>
	<table class="finder-box-layout" cellspacing="0">
		<tr>
			<td class="finder-box-left-column">
				<?php if (!isset($arParams["INPUT_NAME"]) || strlen($arParams["INPUT_NAME"]) == 0):?>
				<div class="finder-box-search"><input name="<?php echo $arResult["NAME"]?>_user_input" autocomplete="off" id="<?php echo $arResult["NAME"]?>_user_input" class="finder-box-search-textbox" /></div>
				<?php endif?>
				<?php if($arParams["DISPLAY_TABS"] == 'Y'): ?>
					<div class="finder-box-tabs">
						<span class="finder-box-tab finder-box-tab-selected" id="<?php echo $arResult["NAME"]?>_tab_last" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('last');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_LAST_SELECTED")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php if($arParams["DISPLAY_TAB_STRUCTURE"] == 'Y'): ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_structure" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('structure');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_TAB_USER_STRUCTURE")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php endif; ?>
						<?php if($arParams["DISPLAY_TAB_GROUP"] == 'Y'): ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_groups" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('groups');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage('INTRANET_TAB_USER_GROUPS'); ?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php endif; ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_search" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('search'), O_<?php echo $arResult["NAME"]?>.searchInput.focus();">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_USER_SEARCH")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
					</div>

					<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>
				<?php endif?>
				<div class="finder-box-tabs-content">
					<?php if($arParams["SHOW_STRUCTURE_ONLY"] != 'Y'): ?>
						<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?php echo $arResult["NAME"]?>_last">
							<table class="finder-box-tab-columns" cellspacing="0">
								<tr>
									<td>
										<?php $i = 0;?>
										<?php foreach($arResult["LAST_USERS"] as $key=>$user):?>
											<?php $anchor_id = RandString(16); ?>
											<div class="finder-box-item<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " finder-box-item-selected" : "")?>" id="<?php echo $arResult["NAME"]?>_last_employee_<?php echo $user["ID"]?>" onclick="O_<?php echo $arResult["NAME"]?>.select(event)">
												<?php if ($arParams["MULTIPLE"] == "Y"):?>
													<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php else:?>
													<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php endif?>
												<div class="finder-box-item-text" id="anchor_created_<?php echo $anchor_id; ?>"><?php echo $user["NAME"]?></div>
												<div class="finder-box-item-icon"></div>
											</div>
											<?php if ($i == ceil(sizeof($arResult["LAST_USERS"]) / 2) - 1):?>
											</td><td>
											<?php endif?>

											<script>
											BX.tooltip(<?php echo $user["ID"]; ?>, "anchor_created_<?php echo $anchor_id; ?>", "", 'intrantet-user-selector-tooltip');
											</script>


											<?php $i++;?>
										<?php endforeach?>
										<?php foreach($arResult["CURRENT_USERS"] as $key=>$user):?>
											<?php if (!in_array($user, $arResult["LAST_USERS"])):?>
												<?php if ($arParams["MULTIPLE"] == "Y"):?>
													<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php else:?>
													<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php endif?>
											<?php endif?>
										<?php endforeach?>
									</td>
								</tr>
							</table>
						</div>
					<?php endif?>
					<?php if($arParams["DISPLAY_TAB_STRUCTURE"] == 'Y'): ?>
					<div class="finder-box-tab-content<?=($arParams["SHOW_STRUCTURE_ONLY"] == 'Y' ? ' finder-box-tab-content-selected' : '')?>" id="<?php echo $arResult["NAME"]?>_structure">
						<div class="company-structure">
							<?php if (sizeof($arResult["STRUCTURE"]) > 0) CIntranetUserSelectorHelper::drawEmployeeStructure($arResult["STRUCTURE"], $arResult["SECTIONS"], 0, $arResult["NAME"], ($arParams["SHOW_STRUCTURE_ONLY"] == "Y"));?>
						</div>
					</div>
					<?php endif; ?>
					<?php if($arParams["DISPLAY_TAB_GROUP"] == 'Y'): ?>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_groups">
						<?php
							CIntranetUserSelectorHelper::drawGroup($arResult["GROUPS"], $arResult["NAME"]);
						?>
					</div>
					<?php endif; ?>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_search"></div>
				</div>
			</td>
			<?php if ($arParams["MULTIPLE"] == "Y"):?>
			<td class="finder-box-right-column" id="<?=$arResult["NAME"]; ?>_selected_users">
				<div class="finder-box-selected-title"><?=GetMessage("INTRANET_EMP_CURRENT_COUNT"); ?> (<span id="<?=$arResult["NAME"]; ?>_current_count"><?=sizeof($arResult["CURRENT_USERS"]); ?></span>)</div>
				<div class="finder-box-selected-items">
					<? foreach($arResult["CURRENT_USERS"] as $user) { ?>
						<div
							class="finder-box-selected-item"
							id="<?=$arResult["NAME"]; ?>_employee_selected_<?=$user["ID"]; ?>"><div
								class="finder-box-selected-item-icon"
								id="user-selector-unselect-<?=$user["ID"]; ?>"
								onclick="O_<?=$arResult["NAME"]; ?>.unselect(<?=$user["ID"]; ?>, this);"
								<? if (in_array($user['ID'], $arResult['FIXED_USERS'])) { ?>style="visibility: hidden; "<? } ?>></div><span
									class="finder-box-selected-item-text"><?=$user["NAME"]; ?></span></div>
					<? } ?>
				</div>
			</td>
			<?php endif?>
		</tr>
	</table>
</div>