<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	CJSCore::Init(array('socnetlogdest', 'popup', 'fx'));
	$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/components/bitrix/main.post.form/templates/.default/style.css");
	$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/style.css");

	?><div id="sonet_group_create_error_block" class="sonet-group-create-popup-error-block" style="display: <?=(strlen($arResult["ErrorMessage"]) > 0 ? "block" : "none")?>;">
		<div class="sonet-group-create-popup-corners-top"><div class="sonet-group-create-popup-left-corner"></div><div class="sonet-group-create-popup-right-corner"></div></div>
		<div id="sonet_group_create_error_block_content" class="sonet-group-create-popup-content"><?=$arResult["ErrorMessage"]?></div>
		<div class="sonet-group-create-popup-corners-bottom"><div class="sonet-group-create-popup-left-corner"></div><div class="sonet-group-create-popup-right-corner"></div></div>
	</div><?

	if ($arResult["ShowForm"] == "Input")
	{
		?><script type="text/javascript">
		top.BXExtranetMailList = [];

		BX.message({
			SONET_GCE_T_DESCR : '<?=CUtil::JSEscape(GetMessage("SONET_GCE_T_DESCR"))?>',
			SONET_GROUP_TITLE_EDIT : '<?=CUtil::JSEscape(GetMessage("SONET_GCE_T_TITLE_EDIT"))?>',
			SONET_GCE_T_DEST_EXTRANET_SELECTOR_INVITE : '<?=GetMessageJS("SONET_GCE_T_DEST_EXTRANET_SELECTOR_INVITE")?>',
			SONET_GCE_T_DEST_EXTRANET_SELECTOR_ADD : '<?=GetMessageJS("SONET_GCE_T_DEST_EXTRANET_SELECTOR_ADD")?>',
			SONET_GCE_T_DEST_LINK_1 : '<?=GetMessageJS(IsModuleInstalled("intranet") ? "SONET_GCE_T_ADD_EMPLOYEE" : "SONET_GCE_T_ADD_USER")?>',
			SONET_GCE_T_DEST_LINK_2 : '<?=GetMessageJS('SONET_GCE_T_DEST_LINK_2')?>'
			<?
			if (array_key_exists("POST", $arResult) && array_key_exists("NAME", $arResult["POST"]) && strlen($arResult["POST"]["NAME"]) > 0)
			{
				?>
				, SONET_GROUP_TITLE : '<?=CUtil::JSEscape($arResult["POST"]["NAME"])?>'
				<?
			}
			?>
		});

		<?
		if (
			$arResult["IS_IFRAME"] 
			&& $arResult["CALLBACK"] == "REFRESH"
		)
		{
			$APPLICATION->RestartBuffer();
			?>
			<script type="text/javascript">
			top.BX.onCustomEvent('onSonetIframeCallbackRefresh');
			</script>
			<?
			die();
		}
		elseif (
			$arResult["IS_IFRAME"]
			&& $arResult["CALLBACK"] == "GROUP"
		)
		{
			$APPLICATION->RestartBuffer();
			?>
			<script type="text/javascript">
			top.BX.onCustomEvent('onSonetIframeCallbackGroup', [<?=intval($_GET["GROUP_ID"])?>]);
			</script>
			<?
			die();
		}
		elseif (
			$arResult["IS_IFRAME"]
			&& $arResult["CALLBACK"] == "EDIT"
		)
		{
// this situation is impossible now but this code may be needed in the future
			?>
			(function() {
				var iframePopup = window.top.BX.SonetIFramePopup;
				if (iframePopup)
				{
					BX.adjust(iframePopup.title, {text: BX.message("SONET_GROUP_TITLE_EDIT").replace('#GROUP_NAME#', BX.message("SONET_GROUP_TITLE"))});
				}
			})();
			<?
		}
		?>

		BX.ready(
			function()
			{
				if (typeof (BX.BXGCE) != 'undefined')
				{
					BX.BXGCE.arUserSelector = [];
				}
				BXGCESwitchTabs();
				BXGCESwitchFeatures();
				BX.bind(BX("sonet_group_create_popup_form_button_submit"), "click", BXGCESubmitForm);
				BX.bind(BX("sonet_group_create_popup_form_email_input"), "keydown", BXGCEEmailKeyDown);

				if (BX("USERS_employee_section_extranet"))
				{
					BX("USERS_employee_section_extranet").style.display = "<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? "inline-block" : "none")?>";
				}
			}
		);
		</script>
		<?
		if (is_array($arResult["ErrorFields"]) && count($arResult["ErrorFields"]) > 0)
		{
			$bHasUserFieldError = false;
			foreach ($arResult["GROUP_PROPERTIES"] as $FIELD_NAME => $arUserField)
			{
				if (in_array($FIELD_NAME, $arResult["ErrorFields"]))
				{
					$bHasUserFieldError = true;
					break;
				}
			}

			if (
				(
					in_array("GROUP_INITIATE_PERMS", $arResult["ErrorFields"])
					|| in_array("GROUP_SPAM_PERMS", $arResult["ErrorFields"])
					|| $bHasUserFieldError
				)
				&& !in_array("GROUP_SUBJECT_ID", $arResult["ErrorFields"])
				&& !in_array("GROUP_NAME", $arResult["ErrorFields"])
			)
				$active_tab = "additional";
		}
		?>
		<form method="post" name="sonet_group_create_popup_form" id="sonet_group_create_popup_form" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
			<div id="sonet_group_create_popup" class="sonet-group-create-popup"><?
				?><div class="sonet-group-create-popup-tabs-block">
					<span class="sonet-group-create-popup-tabs-wrap"><?
						if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "edit")
						{
							?><span class="sonet-group-create-popup-tab<?=($active_tab == "additional" ? "" : " sonet-group-create-popup-tab-active")?>">
								<span class="sonet-group-create-popup-tab-left"></span><span class="sonet-group-create-popup-tab-text"><?=GetMessage("SONET_GCE_TAB_1")?></span><span class="sonet-group-create-popup-tab-right"></span>
							</span><?
						}

						if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "edit")
						{
							?><span class="sonet-group-create-popup-tab">
								<span class="sonet-group-create-popup-tab-left"></span><span class="sonet-group-create-popup-tab-text"><?=GetMessage("SONET_GCE_TAB_2")?></span><span class="sonet-group-create-popup-tab-right"></span>
							</span><?
						}

						if (!array_key_exists("TAB", $arResult))
						{
							?><span class="sonet-group-create-popup-tab">
								<span class="sonet-group-create-popup-tab-left"></span><span class="sonet-group-create-popup-tab-text"><?=GetMessage("SONET_GCE_TAB_3")?></span><span class="sonet-group-create-popup-tab-right"></span>
							</span><?
						}

						if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "edit")
						{
							?><span class="sonet-group-create-popup-tab<?=($active_tab == "additional" ? " sonet-group-create-popup-tab-active" : "")?>">
								<span class="sonet-group-create-popup-tab-left"></span><span class="sonet-group-create-popup-tab-text"><?=GetMessage("SONET_GCE_TAB_4")?></span><span class="sonet-group-create-popup-tab-right"></span>
							</span><?
						}

					?></span>
					<div class="sonet-group-create-tabs-block-line"></div>
				</div>

				<div id="sonet_group_create_tabs_content" class="sonet-group-create-tabs-content<?=($arResult["bExtranetInstalled"] ? " sonet-group-create-tabs-content-extranet" : "")?><?=($arResult["TAB"] == "invite" ? " sonet-group-create-tabs-content-invite" : "")?>"><?

					if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "edit")
					{
						?><div id="sonet_group_create_tabs_description" style="<?=($active_tab == "additional" ? "display: none;" : "")?>"><?

							$strSubmitButtonTitle = ($arParams["GROUP_ID"] > 0 ? GetMessage("SONET_GCE_T_DO_EDIT") : GetMessage("SONET_GCE_T_DO_CREATE"));
							?>
							<div>
								<div class="sonet-group-create-popup-main-fields">
									<div class="sonet-group-create-popup-field-corners-top">
										<div class="sonet-group-create-popup-form-left-corner"></div>
										<div class="sonet-group-create-popup-form-right-corner"></div>
									</div>
									<div class="sonet-group-create-popup-field-content">
										<div class="sonet-group-create-tabs-text-input-wrap <?=(in_array("GROUP_NAME", $arResult["ErrorFields"]) ? "sonet-group-create-tabs-text-error" : "")?>" value="<?=(strlen($arResult["POST"]["NAME"]) > 0 ? $arResult["POST"]["NAME"] : GetMessage("SONET_GCE_T_NAME"));?>">
											<input type="text" name="GROUP_NAME" class="sonet-group-create-tabs-text-input<?=(strlen($arResult["POST"]["NAME"]) > 0 ? " sonet-group-create-tabs-text-input-active" : "");?><?=(in_array("GROUP_NAME", $arResult["ErrorFields"]) ? " sonet-group-create-tabs-text-error" : "")?>" value="<?=(strlen($arResult["POST"]["NAME"]) > 0 ? $arResult["POST"]["NAME"] : GetMessage("SONET_GCE_T_NAME"));?>" onblur="if(this.value == ''){ BX.removeClass(this, 'sonet-group-create-tabs-text-input-active'); this.value = this.value.replace(new RegExp(/^$/), '<?=GetMessage("SONET_GCE_T_NAME")?>')}" onfocus="BX.addClass(this, 'sonet-group-create-tabs-text-input-active'); this.value = this.value.replace('<?=GetMessage("SONET_GCE_T_NAME")?>', '')" />
										</div>
									</div>
								</div>
								<div class="sonet-group-create-popup-additional-fields">
									<div class="sonet-group-create-popup-field-content">
										<div class="sonet-group-create-tabs-textarea-wrap">
											<textarea 
												class="<?=(strlen($arResult["POST"]["DESCRIPTION"]) > 0 ? "sonet-group-create-tabs-textarea-active" : "");?>" 
												name="GROUP_DESCRIPTION" 
												id="GROUP_DESCRIPTION" 
												onblur="if(this.value == ''){BX.removeClass(this, 'sonet-group-create-tabs-textarea-active'); this.value = this.value.replace(new RegExp(/^$/), '<?=GetMessage("SONET_GCE_T_DESCR")?>')}" 
												onfocus="BX.addClass(this, 'sonet-group-create-tabs-textarea-active'); this.value = this.value.replace('<?=GetMessage("SONET_GCE_T_DESCR")?>', '')"
											><?=(strlen($arResult["POST"]["DESCRIPTION"]) > 0 ? $arResult["POST"]["DESCRIPTION"] : GetMessage("SONET_GCE_T_DESCR"));?></textarea>
										</div>
										<div style="margin-top: 10px;" class="<?=(in_array("GROUP_IMAGE_ID", $arResult["ErrorFields"]) ? "sonet-group-create-popup-field-upload-error" : "")?>"><?
										
											$APPLICATION->IncludeComponent('bitrix:main.file.input', '', array(
												'INPUT_NAME' => 'GROUP_IMAGE_ID',
												'INPUT_NAME_UNSAVED' => 'GROUP_IMAGE_ID_UNSAVED',
												'CONTROL_ID' => 'GROUP_IMAGE_ID',
												'INPUT_VALUE' => $arResult["POST"]["IMAGE_ID"],
												'MULTIPLE' => 'N',
												'ALLOW_UPLOAD' => 'I',
												'INPUT_CAPTION' => GetMessage("SONET_GCE_T_UPLOAD_IMAGE")
											));

											?><input type="hidden" id="sonet_group_create_group_image_cid" name="GROUP_IMAGE_CID" value="" />
											<script>
											function onFileUploaderChangeHandler(files) {
												if (files && files.length > 0)
												{
													BX('sonet_group_create_popup_image').src = files[0].fileURL;
													BX.show(BX('sonet_group_create_tabs_image_block'));
													BX("sonet_group_create_tabs_image_block").style.visibility = "visible";
													BX("sonet_group_create_group_image_cid").value = window.FILE_INPUT_GROUP_IMAGE_ID.CID;
												}
												else
												{
													BX.hide(BX('sonet_group_create_tabs_image_block'));
													BX("sonet_group_create_tabs_image_block").style.visibility = "hidden";
												}
											}
											BX.addCustomEvent(window.FILE_INPUT_GROUP_IMAGE_ID, 'onFileUploaderChange', onFileUploaderChangeHandler);
											</script>
										</div><?

										$bIsSepNeeded = false;

										?><div class="sonet-group-create-tabs-filter-wrap"><?
											if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite() || intval($arResult["GROUP_ID"]) > 0):

												?><div><?

													if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()):
														$bIsSepNeeded = true;
														if ($arResult["bExtranetInstalled"]):
															?><div id="GROUP_VISIBLE_block" class="<?=($arResult["POST"]["VISIBLE"] == "Y" ? "sonet-group-create-popup-checkbox-active" : "")?>" style="<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? " display: none;" : "")?>"><input type="checkbox" onclick="BXSwitchNotVisible(this.checked)" class="sonet-group-create-popup-checkbox" id="GROUP_VISIBLE" value="Y" name="GROUP_VISIBLE"<?= ($arResult["POST"]["VISIBLE"] == "Y") ? " checked" : ""?>> <label for="GROUP_VISIBLE"><?= GetMessage("SONET_GCE_T_PARAMS_VIS") ?></label></div><?
														else:
															?><div id="GROUP_VISIBLE_block" class="<?=($arResult["POST"]["VISIBLE"] == "Y" ? "sonet-group-create-popup-checkbox-active" : "")?>"><input type="checkbox" onclick="BXSwitchNotVisible(this.checked)" class="sonet-group-create-popup-checkbox" id="GROUP_VISIBLE" value="Y" name="GROUP_VISIBLE"<?= ($arResult["POST"]["VISIBLE"] == "Y") ? " checked" : ""?>> <label for="GROUP_VISIBLE"><?= GetMessage("SONET_GCE_T_PARAMS_VIS") ?></label></div><?
														endif;
													else:
														?><input type="hidden" value="N" name="GROUP_VISIBLE"><?
													endif;

													if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()):
														$bIsSepNeeded = true;
														if ($arResult["bExtranetInstalled"]):
															?><div id="GROUP_OPENED_block" class="<?=($arResult["POST"]["OPENED"] == "Y" ? "sonet-group-create-popup-checkbox-active" : "")?>" style="<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? " display: none;" : "")?>"><input type="checkbox" onclick="BX.toggleClass(this.parentNode, 'sonet-group-create-popup-checkbox-active')" class="sonet-group-create-popup-checkbox" id="GROUP_OPENED" value="Y" name="GROUP_OPENED"<?= ($arResult["POST"]["OPENED"] == "Y") ? " checked" : ""?> <?= ($arResult["POST"]["VISIBLE"] == "Y") ? "" : " disabled"?>> <label for="GROUP_OPENED"><?= GetMessage("SONET_GCE_T_PARAMS_OPEN") ?></label></div><?
														else:
															?><div class="<?=($arResult["POST"]["OPENED"] == "Y" ? "sonet-group-create-popup-checkbox-active" : "")?>"><input type="checkbox"  onclick="BX.toggleClass(this.parentNode, 'sonet-group-create-popup-checkbox-active')"  class="sonet-group-create-popup-checkbox<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? " sonet-group-create-popup-checkbox-active" : "")?>" id="GROUP_OPENED" value="Y" name="GROUP_OPENED"<?= ($arResult["POST"]["OPENED"] == "Y") ? " checked" : ""?> <?= ($arResult["POST"]["VISIBLE"] == "Y") ? "" : " disabled"?>> <label for="GROUP_OPENED"><?= GetMessage("SONET_GCE_T_PARAMS_OPEN") ?></label></div><?
														endif;
													else:
														?><input type="hidden" value="N" name="GROUP_OPENED"><?
													endif;

													if (intval($arParams["GROUP_ID"]) > 0):
														$bIsSepNeeded = true;
														?><div class="<?=($arResult["POST"]["CLOSED"] == "Y" ? "sonet-group-create-popup-checkbox-active" : "")?>"><input type="checkbox" onclick="BX.toggleClass(this.parentNode, 'sonet-group-create-popup-checkbox-active')" class="sonet-group-create-popup-checkbox" id="GROUP_CLOSED" value="Y" name="GROUP_CLOSED"<?= ($arResult["POST"]["CLOSED"] == "Y") ? " checked" : ""?>> <label for="GROUP_CLOSED"><?= GetMessage("SONET_GCE_T_PARAMS_CLOSED") ?></label></div><?
													else:
														?><input type="hidden" value="N" name="GROUP_CLOSED"><?
													endif;

												?></div><?

											endif;

											if (CModule::IncludeModule('extranet') && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0):
												if (!CExtranet::IsExtranetSite()):
													if ($bIsSepNeeded):
														?><div class="sonet-group-create-popup-sep"></div><?
													endif;
													$bIsSepNeeded = true;
													?><div id="IS_EXTRANET_GROUP_block" class="<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? " sonet-group-create-popup-checkbox-active" : "")?>"><input type="checkbox" class="sonet-group-create-popup-checkbox" id="IS_EXTRANET_GROUP" value="Y" name="IS_EXTRANET_GROUP"<?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? " checked" : "")?> onclick="BXSwitchExtranet(this.checked)"><label for="IS_EXTRANET_GROUP"><?= GetMessage("SONET_GCE_T_IS_EXTRANET_GROUP") ?></label></div><?
												else:
													?><input type="hidden" value="Y" name="IS_EXTRANET_GROUP"><?
												endif;
											endif;

											if (count($arResult["Subjects"]) == 1):
												$arKeysTmp = array_keys($arResult["Subjects"]);
												?><input type="hidden" name="GROUP_SUBJECT_ID" value="<?=$arKeysTmp[0]?>"><?
											else:
												if ($bIsSepNeeded):
													?><div class="sonet-group-create-popup-sep"></div><?
												endif;
												?><div class="sonet-group-create-tabs-select-wrap">
													<label for="GROUP_SUBJECT_ID"><?= GetMessage("SONET_GCE_T_SUBJECT") ?></label>
													<span class="<?=(in_array("GROUP_SUBJECT_ID", $arResult["ErrorFields"]) ? "sonet-group-create-tabs-select-error" : "")?>"><select name="GROUP_SUBJECT_ID" id="GROUP_SUBJECT_ID" class="sonet-group-create-popup-select">
														<option value=""><?= GetMessage("SONET_GCE_T_TO_SELECT") ?></option>
														<?foreach ($arResult["Subjects"] as $key => $value):?>
															<option value="<?= $key ?>"<?= ($key == $arResult["POST"]["SUBJECT_ID"]) ? " selected" : "" ?>><?= $value ?></option>
														<?endforeach;?>
													</select></span>
												</div><?
											endif;

											?><div class="sonet-group-create-tabs-image-block" id="sonet_group_create_tabs_image_block">
												<input type="hidden" name="GROUP_IMAGE_ID_DEL" id="GROUP_IMAGE_ID_DEL" value=""/>
												<span class="sonet-group-create-tabs-image-wrap"><?
													if (strlen($arResult["POST"]["IMAGE_ID_IMG"]) > 0):?>
														<?=$arResult["POST"]["IMAGE_ID_IMG"];?><br /><?
													endif;
													?><a class="sonet-group-create-popup-del" id="sonet_group_create_popup_del" href="javascript:void(0);" onclick="BXDeleteImage();"></a>
												</span>
											</div>
										</div>
									</div>
									<div class="sonet-group-create-popup-field-corners-bottom">
										<div class="sonet-group-create-popup-form-left-corner"></div>
										<div class="sonet-group-create-popup-form-right-corner"></div>
									</div>
								</div>
							</div>
						</div><?
					}

					if (
						!array_key_exists("TAB", $arResult) 
						|| $arResult["TAB"] == "edit"
					)
					{
						?><div id="sonet_group_create_tabs_features" style="display: none;">
							<div class="sonet-group-create-popup-features-title"><?=GetMessage("SONET_GCE_T_FEATURES_TAB_TITLE");?></div>
							<div style="overflow: hidden; padding: 0 25px;" id="sonet_group_create_popup_features">
								<div class="sonet-group-create-popup-features-leftcol"><?
									$i = 1;

									foreach ($arResult["POST"]["FEATURES"] as $feature => $arFeature):

										if ($i > ceil(count($arResult["POST"]["FEATURES"])/2)):
											?></div>
											<div class="sonet-group-create-popup-features-rightcol"><?
											$i = 1;
										endif;

										?><a href="javascript:void(0);" class="sonet-group-create-popup-feature<?= ($arFeature["Active"] ? " sonet-group-create-popup-feature-active" : "") ?>"><span class="sonet-group-create-popup-feature-img"></span><?= (array_key_exists("title", $arResult["arSocNetFeaturesSettings"][$feature]) && strlen($arResult["arSocNetFeaturesSettings"][$feature]["title"]) > 0 ? $arResult["arSocNetFeaturesSettings"][$feature]["title"] : GetMessage("SONET_FEATURES_".$feature))?></a><?
										?><input class="sonet-group-create-popup-feature-hidden" type="hidden" id="<?= $feature ?>_active_id" name="<?= $feature ?>_active" value="<?= ($arFeature["Active"] ? "Y" : "") ?>"><?
										$i++;
									endforeach;
								?></div>
							</div>
						</div><?
					}

					if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "invite")
					{
						if ($arResult["TAB"] == "invite")
						{
							$strSubmitButtonTitle = GetMessage("SONET_GCE_T_DO_INVITE");
						}

						?><div id="sonet_group_create_tabs_invite"<?=($arResult["TAB"] != "invite" ? ' style="display: none;"' : '')?>><?

							$selectorName = randString(6);

							?><div class="sonet-group-create-popup-users-title"><?=GetMessage("SONET_GCE_T_DEST_TITLE_".(IsModuleInstalled("intranet") ? "EMPLOYEE" : "USER"))?></div><?
								?><div class="feed-add-post-destination-wrap<?=(in_array("USERS", $arResult["ErrorFields"]) ? " sonet-group-create-tabs-text-error" : "")?>" id="sonet_group_create_popup_users_container_post_<?=$selectorName?>">
									<span id="sonet_group_create_popup_users_item_post_<?=$selectorName?>"></span>
									<span class="feed-add-destination-input-box" id="sonet_group_create_popup_users_input_box_post_<?=$selectorName?>">
										<input type="text" value="" class="feed-add-destination-inp" id="sonet_group_create_popup_users_input_post_<?=$selectorName?>">
									</span>
									<a href="#" class="feed-add-destination-link" id="sonet_group_create_popup_users_tag_post_<?=$selectorName?>"><?=GetMessage(IsModuleInstalled("intranet") ? "SONET_GCE_T_ADD_EMPLOYEE" : "SONET_GCE_T_ADD_USER")?></a><?

									$arValue = ($arResult["POST"]["USER_CODES"] ? $arResult["POST"]["USER_CODES"] : array());
									$arStructure = (IsModuleInstalled('intranet') ? CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true, "DEPARTMENT_ID" => (isset($arResult["siteDepartmentID"]) && intval($arResult["siteDepartmentID"]) > 0 ? intval($arResult["siteDepartmentID"]) : false))) : false);

									?><script type="text/javascript">

										var department = <?=($arStructure && !empty($arStructure['department']) ? CUtil::PhpToJSObject($arStructure['department']) : '{}')?>;
										var lastUsers = <?=(empty($arResult["DEST_USERS_LAST"])? '{}': CUtil::PhpToJSObject($arResult["DEST_USERS_LAST"]))?>;

										<?
										if (!$arStructure || empty($arStructure['department_relation']))
										{
											?>
											var relation = {};
											for(var iid in department)
											{
												var p = department[iid]['parent'];
												if (!relation[p])
													relation[p] = [];
												relation[p][relation[p].length] = iid;
											}
											function makeDepartmentTree(id, relation)
											{
												var arRelations = {};

												if (relation[id])
												{
													for (var x in relation[id])
													{
														var relId = relation[id][x];
														var arItems = [];
														if (relation[relId] && relation[relId].length > 0)
															arItems = makeDepartmentTree(relId, relation);

														arRelations[relId] = {
															id: relId,
															type: 'category',
															items: arItems
														};
													}
												}

												return arRelations;
											}
											var departmentRelation = makeDepartmentTree(<?=(isset($arResult["siteDepartmentID"]) && intval($arResult["siteDepartmentID"]) ? "department['DR".intval($arResult["siteDepartmentID"])."'].parent" : "'DR0'")?>, relation);
											<?
										}
										else
										{
											?>var departmentRelation = <?=CUtil::PhpToJSObject($arStructure['department_relation'])?>;<?
										}
										?>

										BX.ready(function() {
											BX.SocNetLogDestination.init({
												'name' : '<?=$selectorName?>',
												'searchInput' : BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'),
												'departmentSelectDisable' : true,
												'userSearchArea' : <?=($arResult["bExtranetInstalled"] ? "'I'" : "false")?>,
												'extranetUser' :  false, // ??
												'allowAddSocNetGroup': false,
												'siteDepartmentID' : <?=(isset($arResult["siteDepartmentID"]) && intval($arResult["siteDepartmentID"]) > 0 ? intval($arResult["siteDepartmentID"]) : "false")?>,
												'bindMainPopup' : {
													'node' : BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'),
													'offsetTop' : '5px',
													'offsetLeft': '15px'
												},
												'bindSearchPopup' : {
													'node' : BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'),
													'offsetTop' : '5px',
													'offsetLeft': '15px'
												},
												'callback' : {
													'select' : BX.BXGCE.selectCallback,
													'unSelect' : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
														formName: '<?=$selectorName?>',
														inputContainerName: 'sonet_group_create_popup_users_item_post_<?=$selectorName?>',
														inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
														tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>',
														tagLink1: BX.message('SONET_GCE_T_DEST_LINK_1'),
														tagLink2: BX.message('SONET_GCE_T_DEST_LINK_2')
													}),
													'openDialog' : BX.delegate(BX.BXGCE.openDialogCallback, {
														inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
														inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
														tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
													}),
													'closeDialog' : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
														inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
														inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
														tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
													}),
													'openSearch' : BX.delegate(BX.BXGCE.openDialogCallback, {
														inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
														inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
														tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
													}),
													'closeSearch' : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
														inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
														inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
														tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
													})
												},
												'items' : {
													'users' : <?=(
														$arResult["bExtranetInstalled"] 
														&& strlen(COption::GetOptionString("extranet", "extranet_site")) > 0
															? (is_array($arResult["POST"]["USERS_FOR_JS_I"]) && !empty($arResult["POST"]["USERS_FOR_JS_I"]) ? CUtil::PhpToJSObject($arResult["POST"]["USERS_FOR_JS_I"]) : '{}')
															: (is_array($arResult["POST"]["USERS_FOR_JS"]) && !empty($arResult["POST"]["USERS_FOR_JS"]) ? CUtil::PhpToJSObject($arResult["POST"]["USERS_FOR_JS"]) : '{}')
													)?>,
													'groups' : {},
													'sonetgroups' : {},
													'department' : department,
													'departmentRelation' : departmentRelation
												},
												'itemsLast' : {
													'users' : lastUsers,
													'sonetgroups' : {},
													'department' : {},
													'groups' : {}
												},
												'itemsSelected' : <?=(empty($arValue)? '{}': CUtil::PhpToJSObject($arValue))?>
											});
											BX.BXGCE.arUserSelector.push('<?=$selectorName?>');
											BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
												formName: '<?=$selectorName?>',
												inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
												tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
											}));
											BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
												formName: '<?=$selectorName?>',
												inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>'
											}));
											BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'click', function(e) {
												BX.BXGCE.setSelector('<?=$selectorName?>');
												BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
												BX.PreventDefault(e);
											});
											BX.bind(BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'), 'click', function(e) {
												BX.BXGCE.setSelector('<?=$selectorName?>');
												BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
												BX.PreventDefault(e);
											});
										});
									</script><?
								?></div><?

								if (
									$arResult["bExtranetInstalled"] 
									&& CModule::IncludeModule("intranet")
									&& strlen(COption::GetOptionString("extranet", "extranet_site")) > 0
								)
								{
									$selectorName = randString(6);

									?><div id="INVITE_EXTRANET_block" style="display: <?=($arResult["POST"]["IS_EXTRANET_GROUP"] == "Y" ? "block" : "none")?>;"><?
										?><div class="invite-dialog-inv-form" style="margin-top: 10px;"><?
											?><div class="sonet-group-create-popup-users-title"><?=GetMessage("SONET_GCE_T_DEST_TITLE_EXTRANET")?></div><?
											?><div class="feed-add-post-destination-wrap" id="sonet_group_create_popup_users_container_post_<?=$selectorName?>">
												<span id="sonet_group_create_popup_users_item_post_<?=$selectorName?>"></span>
												<span class="feed-add-destination-input-box" id="sonet_group_create_popup_users_input_box_post_<?=$selectorName?>">
													<input type="text" value="" class="feed-add-destination-inp" id="sonet_group_create_popup_users_input_post_<?=$selectorName?>">
												</span>
												<a href="#" class="feed-add-destination-link" id="sonet_group_create_popup_users_tag_post_<?=$selectorName?>"><?=GetMessage("SONET_GCE_T_ADD_EXTRANET")?></a><?								
												
												?><script type="text/javascript"><?
													$arStructure = array(
														'department' => array(
															'EX' => array(
																'id' => 'EX',
																'entityId' => 'EX',
																'name' => GetMessage('SONET_GCE_T_DEST_EXTRANET'),
																'parent' => 'DR0'
															)
														),
														'department_relation' => array(
															'EX' => array(
																'id' => 'EX',
																'items' => array(),
																'type' => 'category'
															)
														)
													);
													?>
													var departmentExtranet = <?=CUtil::PhpToJSObject($arStructure['department'])?>;
													var departmentRelationExtranet = <?=CUtil::PhpToJSObject($arStructure['department_relation'])?>;

													BX.ready(function() {
														BX.SocNetLogDestination.init({
															'name' : '<?=$selectorName?>',
															'searchInput' : BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'),
															'departmentSelectDisable' : true,
															'userSearchArea' : 'E',
															'extranetUser' :  false, // ??
															'allowAddSocNetGroup': false,
															'bindMainPopup' : {
																'node' : BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'),
																'offsetTop' : '5px',
																'offsetLeft': '15px'
															},
															'bindSearchPopup' : {
																'node' : BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'),
																'offsetTop' : '5px',
																'offsetLeft': '15px'
															},
															'callback' : {
																'select' : BX.BXGCE.selectCallback,
																'unSelect' : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
																	formName: '<?=$selectorName?>',
																	inputContainerName: 'sonet_group_create_popup_users_item_post_<?=$selectorName?>',
																	inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
																	tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>',
																	tagLink1: BX.message('SONET_GCE_T_DEST_LINK_1'),
																	tagLink2: BX.message('SONET_GCE_T_DEST_LINK_2')
																}),
																'openDialog' : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
																	inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
																	inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
																	tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
																}),
																'closeDialog' : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
																	inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
																	inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
																	tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
																}),
																'openSearch' : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
																	inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
																	inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
																	tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
																}),
																'closeSearch' : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
																	inputBoxName: 'sonet_group_create_popup_users_input_box_post_<?=$selectorName?>',
																	inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
																	tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
																})
															},
															'items' : {
																'users' : <?=(is_array($arResult["POST"]["USERS_FOR_JS_E"]) && !empty($arResult["POST"]["USERS_FOR_JS_E"]) ? CUtil::PhpToJSObject($arResult["POST"]["USERS_FOR_JS_E"]) : '{}')?>,
																'groups' : {},
																'sonetgroups' : {},
																'department' : departmentExtranet,
																'departmentRelation' : departmentRelationExtranet
															},
															'itemsLast' : {
																'users' : lastUsers,
																'sonetgroups' : {},
																'department' : {},
																'groups' : {}
															},
															'itemsSelected' : <?=(empty($arValue)? '{}': CUtil::PhpToJSObject($arValue))?>
														});
														BX.BXGCE.arUserSelector.push('<?=$selectorName?>');
														BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
															formName: '<?=$selectorName?>',
															inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>',
															tagInputName: 'sonet_group_create_popup_users_tag_post_<?=$selectorName?>'
														}));
														BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
															formName: '<?=$selectorName?>',
															inputName: 'sonet_group_create_popup_users_input_post_<?=$selectorName?>'
														}));
														BX.bind(BX('sonet_group_create_popup_users_input_post_<?=$selectorName?>'), 'click', function(e) {
															BX.BXGCE.setSelector('<?=$selectorName?>');
															BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
															BX.PreventDefault(e);
														});
														BX.bind(BX('sonet_group_create_popup_users_container_post_<?=$selectorName?>'), 'click', function(e) {
															BX.BXGCE.setSelector('<?=$selectorName?>');
															BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
															BX.PreventDefault(e);
														});
													});
												</script>
											</div><?
											?><div id="sonet_group_create_popup_action_title" class="invite-dialog-inv-block"><?=GetMessage(
												'SONET_GCE_T_DEST_EXTRANET_SELECTOR',
												array(
													'#ACTION#' => '<a href="javascript:void(0);" id="sonet_group_create_popup_action_title_link" class="invite-dialog-inv-link" data-action="invite">'.GetMessage('SONET_GCE_T_DEST_EXTRANET_SELECTOR_INVITE').'</a>'
												)
											)?></div><?
											?><div id="sonet_group_create_popup_action_block_invite" style="display: <?=(isset($arResult["POST"]["EXTRANET_INVITE_ACTION"]) && $arResult["POST"]["EXTRANET_INVITE_ACTION"] == "add" ? "none" : "block")?>;"><?
												if(strlen($arResult["WarningMessage"]) > 0)
												{
													?><div class='errortext'><?=$arResult["WarningMessage"]?></div><?
												}
												?><table class="invite-dialog-inv-form-table">
													<tr>
														<td class="invite-dialog-inv-form-l" style="vertical-align: top;">
															<label for="EMAILS"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_EMAIL_SHORT")?></label>
														</td>
														<td class="invite-dialog-inv-form-r">
															<textarea 
																rows="5" 
																type="text" 
																name="EMAILS" 
																id="EMAILS" 
																class="invite-dialog-inv-form-textarea"
																onblur="if(this.value == ''){BX.removeClass(this, 'invite-dialog-inv-form-textarea-active'); this.value = this.value.replace(new RegExp(/^$/), '<?=GetMessage("SONET_GCE_T_EMAILS_DESCR")?>')}" 
																onfocus="BX.addClass(this, 'invite-dialog-inv-form-textarea-active'); this.value = this.value.replace('<?=GetMessage("SONET_GCE_T_EMAILS_DESCR")?>', '')"
															><?=(strlen($arResult["POST"]["EMAILS"]) > 0 ? htmlspecialcharsbx($arResult["POST"]["EMAILS"]) : GetMessage("SONET_GCE_T_EMAILS_DESCR"));?></textarea>
														</td>
													</tr>
												</table><?
											?></div><?
											?><div id="sonet_group_create_popup_action_block_add" style="display: <?=(isset($arResult["POST"]["EXTRANET_INVITE_ACTION"]) && $arResult["POST"]["EXTRANET_INVITE_ACTION"] == "add" ? "block" : "none")?>;"><?
											
												?><table class="invite-dialog-inv-form-table">
													<tr>
														<td class="invite-dialog-inv-form-l">
															<label for="ADD_EMAIL"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_ADD_EMAIL_TITLE")?></label>
														</td>
														<td class="invite-dialog-inv-form-r">
															<input type="text" name="ADD_EMAIL" id="ADD_EMAIL" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_EMAIL"])?>">
														</td>
													</tr>
													<tr>
														<td class="invite-dialog-inv-form-l">
															<label for="ADD_NAME"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_ADD_NAME_TITLE")?></label>
														</td>
														<td class="invite-dialog-inv-form-r">
															<input type="text" name="ADD_NAME" id="ADD_NAME" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_NAME"])?>">
														</td>
													</tr>
													<tr>
														<td class="invite-dialog-inv-form-l">
															<label for="ADD_LAST_NAME"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_ADD_LAST_NAME_TITLE")?></label>
														</td>
														<td class="invite-dialog-inv-form-r">
															<input type="text" name="ADD_LAST_NAME" id="ADD_LAST_NAME" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_LAST_NAME"])?>">
														</td>
													</tr>
													<tr class="invite-dialog-inv-form-footer">
														<td class="invite-dialog-inv-form-l">&nbsp;</td>
														<td class="invite-dialog-inv-form-r">
															<div class="invite-dialog-inv-form-checkbox-wrap">
																<input type="checkbox" name="ADD_SEND_PASSWORD" id="ADD_SEND_PASSWORD" value="Y" class="invite-dialog-inv-form-checkbox"><label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_ADD_SEND_PASSWORD_TITLE")?></label>
															</div>
														</td>
													</tr>
												</table><?

											?></div><?
											?><script>
													BX.ready(function() {
														BX.BXGCE.bindActionLink(BX("sonet_group_create_popup_action_title_link"));
													});
											</script><?
										?></div><?

										?><div id="sonet_group_create_popup_action_block_invite_2" style="display: block;"><?
											?><div class="invite-dialog-inv-text-bold"><label for="MESSAGE_TEXT"><?echo GetMessage("SONET_GCE_T_DEST_EXTRANET_INVITE_MESSAGE_TITLE")?></label></div>
											<textarea rows="5" type="text" name="MESSAGE_TEXT" id="MESSAGE_TEXT" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active"><?
												if (isset($_POST["MESSAGE_TEXT"]))
												{
													echo htmlspecialcharsbx($_POST["MESSAGE_TEXT"]);
												}
												elseif ($userMessage = CUserOptions::GetOption("bitrix24", "invite_message_text"))
												{
													echo $userMessage;
												}
												else
												{
													echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT");
												}
											?></textarea><?
										?></div><?

										?><input type="hidden" id="EXTRANET_INVITE_ACTION" name="EXTRANET_INVITE_ACTION" value="invite"><?
									?></div><?
								}

							?><input type="hidden" name="NEW_INVITE_FORM" value="Y"><?
						?></div><?
					}

					if (!array_key_exists("TAB", $arResult) || $arResult["TAB"] == "edit")
					{
						?><div id="sonet_group_create_tabs_additional" style="<?=($active_tab == "additional" ? "" : "display: none;")?>">
							<div class="sonet-group-create-popup-form-add">
								<div class="sonet-group-create-popup-form-corners-top">
									<div class="sonet-group-create-popup-form-left-corner"></div>
									<div class="sonet-group-create-popup-form-right-corner"></div>
								</div>
								<div class="sonet-group-create-popup-field-content"><?

									if ($arResult["POST"]["CLOSED"] != "Y"):
										?><div class="sonet-group-create-popup-form-add-title"><?= GetMessage("SONET_GCE_T_INVITE") ?></div>
										<div class="sonet-group-create-popup-form-add-select"><select name="GROUP_INITIATE_PERMS" id="GROUP_INITIATE_PERMS" class="sonet-group-create-popup-select<?=(in_array("GROUP_INITIATE_PERMS", $arResult["ErrorFields"]) ? " sonet-group-create-tabs-select-error" : "")?>">
											<option value=""><?= GetMessage("SONET_GCE_T_TO_SELECT") ?>-</option><?
											foreach ($arResult["InitiatePerms"] as $key => $value):
												?><option id="GROUP_INITIATE_PERMS_OPTION_<?=$key?>" value="<?= $key ?>"<?= ($key == $arResult["POST"]["INITIATE_PERMS"]) ? " selected" : "" ?>><?= $value ?></option><?
											endforeach;
										?></select></div><?
									else:
										?><input type="hidden" value="<?=$arResult["POST"]["INITIATE_PERMS"]?>" name="GROUP_INITIATE_PERMS"><?
									endif;

									if (
										$arResult["POST"]["CLOSED"] != "Y"
										&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
										&& !IsModuleInstalled("im")
									):
										?><div class="sonet-group-create-popup-form-add-title"><?= GetMessage("SONET_GCE_T_SPAM_PERMS") ?></div>
										<div class="sonet-group-create-popup-form-add-select"><select name="GROUP_SPAM_PERMS" class="sonet-group-create-popup-select-perms<?=(in_array("GROUP_SPAM_PERMS", $arResult["ErrorFields"]) ? " sonet-group-create-tabs-select-error" : "")?>">
											<option value=""><?= GetMessage("SONET_GCE_T_TO_SELECT") ?>-</option><?
											foreach ($arResult["SpamPerms"] as $key => $value):
												?><option value="<?= $key ?>"<?= ($key == $arResult["POST"]["SPAM_PERMS"]) ? " selected" : "" ?>><?= $value ?></option><?
											endforeach;
										?></select></div><?
									else:
										?><input type="hidden" value="<?=$arResult["POST"]["SPAM_PERMS"]?>" name="GROUP_SPAM_PERMS"><?
									endif;

									if ($arParams["USE_KEYWORDS"] == "Y"):
										?><div class="sonet-group-create-popup-form-add-title"><?= GetMessage("SONET_GCE_T_KEYWORDS") ?></div><div class="sonet-group-create-popup-form-add-select"><?
										if (IsModuleInstalled("search")):?><?
											$APPLICATION->IncludeComponent(
												"bitrix:search.tags.input",
												".default",
												Array(
													"NAME" => "GROUP_KEYWORDS",
													"ID" => "GROUP_KEYWORDS",
													"VALUE" => $arResult["POST"]["KEYWORDS"],
													"arrFILTER" => "socialnetwork",
													"PAGE_ELEMENTS" => "10",
													"SORT_BY_CNT" => "Y",
												)
											);
											?><?
										else:
											?><input type="text" name="GROUP_KEYWORDS" style="width:98%" value="<?= $arResult["POST"]["KEYWORDS"]; ?>"><?
										endif;
										?></div><?
									endif;
									//user fields
									if (is_array($arResult["GROUP_PROPERTIES"]) && count($arResult["GROUP_PROPERTIES"]) > 0)
									{
										?>
										<div class="sonet-group-create-uf-header"><?=GetMessage("SONET_GCE_UF_HEADER")?></div>
										<div class="sonet-group-create-uf-content">
										<?
										foreach ($arResult["GROUP_PROPERTIES"] as $FIELD_NAME => $arUserField):
											?><div class="sonet-group-create-tabs-select-wrap<?=(in_array($FIELD_NAME, $arResult["ErrorFields"]) ? " sonet-group-create-tabs-uf-error" : "")?>">
												<div class="sonet-group-create-popup-form-add-title"><label><?= $arUserField["EDIT_FORM_LABEL"] ?><?= ($arUserField["MANDATORY"] == "Y") ? '<span class="sonet-group-create-uf-required">&nbsp;*</span>' : ''?></label></div>
												<div class="sonet-group-create-popup-form-add-select-uf"><?
												$APPLICATION->IncludeComponent(
													"bitrix:system.field.edit",
													$arUserField["USER_TYPE"]["USER_TYPE_ID"],
													array("bVarsFromForm" => $arResult["bVarsFromForm"], "arUserField" => $arUserField),
													null,
													array("HIDE_ICONS"=>"Y")
												);
												?></div>
											</div><?
										endforeach;
									}

									?>
									</div>
								</div>
								<div class="sonet-group-create-popup-form-corners-bottom">
									<div class="sonet-group-create-popup-form-left-corner"></div>
									<div class="sonet-group-create-popup-form-right-corner"></div>
								</div>
							</div>
						</div><?
					}
				?></div>

				<div class="sonet-group-create-tabs-footer">
					<input type="hidden" name="SONET_USER_ID" value="<?= $GLOBALS["USER"]->GetID() ?>">
					<input type="hidden" name="SONET_GROUP_ID" value="<?=intval($arResult["GROUP_ID"])?>">
					<input type="hidden" name="TAB" value="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult["TAB"]))?>">
					<div class="popup-window-buttons"><?
						?><span class="popup-window-button popup-window-button-accept" id="sonet_group_create_popup_form_button_submit"><?
							?><span class="popup-window-button-left"></span><?
							?><span class="popup-window-button-text"><?=$strSubmitButtonTitle?></span><?
							?><span class="popup-window-button-right"></span><?
						?></span><?
						?><span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" onclick="onCancelClick(event);"><?
							?><span class="popup-window-button-link-text"><?= GetMessage("SONET_GCE_T_T_CANCEL") ?></span><?
						?></span><?
					?></div><?
				?></div>

			</div>
		</form>
		<?
	}
	else
	{
		if ($arParams["GROUP_ID"] > 0):
			?><?= GetMessage("SONET_GCE_T_SUCCESS_EDIT")?><?
		else:
			?><?= GetMessage("SONET_GCE_T_SUCCESS_CREATE")?><?
		endif;
		?><br><br>
		<a href="<?= $arResult["Urls"]["NewGroup"] ?>"><?= $arResult["POST"]["NAME"]; ?></a><?
	}
}
?>