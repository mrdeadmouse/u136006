<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
/**
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponent $this
 */
$i = 0;

if(!empty($arResult["SIP_CONFIG"]))
{
	if($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
	{
		CJSCore::RegisterExt('voximplant_config_edit', array(
			'js' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/template.js',
			'lang' => '/bitrix/components/bitrix/voximplant.config.edit/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
		));
		CJSCore::Init(array('voximplant_config_edit'));
	}
}

$tableTitle = htmlspecialcharsbx($arResult["ITEM"]["PHONE_NAME"]);
if (strlen($tableTitle) <= 0 && !empty($arResult["SIP_CONFIG"]))
{
	$tableTitle = $arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD? GetMessage('VI_CONFIG_SIP_CLOUD_DEF'): GetMessage('VI_CONFIG_SIP_OFFICE_DEF');
	$tableTitle = str_replace('#ID#', $arResult['ITEM']['ID'], $tableTitle);
}

$defaultM = $arResult["DEFAULT_MELODIES"];
?>
<form action="<?=POST_FORM_ACTION_URI?>" method="POST" id="voximplantform">
<?=bitrix_sessid_post()?>
<input type="hidden" name="action" value="save" />
<div class="tel-set-main-wrap">
	<div class="tel-set-top-title"><?=$tableTitle?></div>
	<div class="tel-set-inner-wrap">
		<div class="tel-set-cont-block">
			<?if(strlen($arResult["ERROR"])>0):?>
				<div class="tel-set-cont-error"><?=$arResult['ERROR']?></div>
			<?endif;?>
			<?if(!empty($arResult["SIP_CONFIG"])):?>
				<?if($arResult["SIP_CONFIG"]['TYPE'] == CVoxImplantSip::TYPE_CLOUD):?>
					<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_SIP_CLOUD_TITLE")?></div>
					<div class="tel-set-sip-blocks">
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_OUT_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_C_CONFIG')?>
							</div>
							<input type="hidden" name="SIP[NEED_UPDATE]" value="N" id="vi_sip_reg_need_update" />
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_C_NUMBER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PHONE_NAME]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PHONE_NAME'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_SERVER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[SERVER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['SERVER'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_LOGIN')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[LOGIN]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['LOGIN'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_PASS')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PASSWORD]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PASSWORD'])?>" class="tel-set-inp tel-set-inp-sip"/></td>
								</tr>
							</table>
						</div>
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_IN_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_C_IN')?>
							</div>
							<div class="tel-set-sip-reg-status">
								<?=GetMessage('VI_CONFIG_SIP_C_STATUS');?>: <span id="vi_sip_reg_status" class="tel-set-sip-reg-status-result tel-set-sip-reg-status-result-<?=$arResult['SIP_CONFIG']['REG_STATUS']?>"><?=GetMessage('VI_CONFIG_SIP_C_STATUS_'.strtoupper($arResult['SIP_CONFIG']['REG_STATUS']))?></span>.
							</div>
							<div class="tel-set-sip-reg-status-desc" id="vi_sip_reg_status_desc">
								<?=GetMessage('VI_CONFIG_SIP_C_STATUS_'.strtoupper($arResult['SIP_CONFIG']['REG_STATUS']).'_DESC')?>
							</div>
							<div class="tel-set-sip-block-notice">
								<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
							</div>
							<script type="text/javascript">
								BX.VoxImplant.config.sip.initStatus(<?=$arResult['SIP_CONFIG']['REG_ID']?>);
							</script>
						</div>
					</div>
				<?else:?>
					<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_SIP_OFFICE_TITLE")?></div>
					<div class="tel-set-sip-blocks">
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_OUT_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_OUT')?>
							</div>
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_C_NUMBER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PHONE_NAME]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PHONE_NAME'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_SERVER')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[SERVER]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['SERVER'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_LOGIN')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[LOGIN]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['LOGIN'])?>" class="tel-set-inp tel-set-inp-sip" /></td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_PASS')?></td>
									<td class="tel-set-sip-td-r"><input type="text" name="SIP[PASSWORD]" value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['PASSWORD'])?>" class="tel-set-inp tel-set-inp-sip"/></td>
								</tr>
							</table>
						</div>
						<div class="tel-set-sip-block">
							<div class="tel-set-sip-block-title">
								<b><?=GetMessage('VI_CONFIG_SIP_IN_TITLE')?></b><br>
								<?=GetMessage('VI_CONFIG_SIP_IN')?>
							</div>
							<table class="tel-set-sip-table" cellpadding="0" cellspacing="0">
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_SERVER')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_SERVER'])?>"/>
									</td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_LOGIN')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_LOGIN'])?>"/>
									</td>
								</tr>
								<tr>
									<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_T_INC_PASS')?></td>
									<td class="tel-set-sip-td-r">
										<input type="text" class="tel-set-inp tel-set-inp-sip-inc" readonly value="<?=htmlspecialcharsbx($arResult['SIP_CONFIG']['INCOMING_PASSWORD'])?>"/>
									</td>
								</tr>
							</table>
							<div class="tel-set-sip-block-notice">
								<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
							</div>
						</div>
					</div>
				<?endif;?>
			<?endif;?>
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_CALLS_ROUTING")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="DIRECT_CODE" type="hidden" value="N" />
					<input id="id<?=(++$i)?>" name="DIRECT_CODE" <? if ($arResult["ITEM"]["DIRECT_CODE"] == "Y") { ?>checked<? } ?> type="checkbox" value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=$i?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="id<?=$i?>" class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING")?></label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-text"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_TIP")?></div>
						<div class="tel-set-item-select-block">
							<span class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_EXT_NUM_PROCESSING_OMITTED_CALL")?></span>
							<select class="tel-set-inp tel-set-item-select" name="DIRECT_CODE_RULE">
								<option value="<?=CVoxImplantIncoming::RULE_QUEUE?>"<?=(CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["DIRECT_CODE_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1")?></option>
								<option value="<?=CVoxImplantIncoming::RULE_PSTN?>"<?=(CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["DIRECT_CODE_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_2")?></option>
								<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["DIRECT_CODE_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2")?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="CRM" type="hidden" value="N" />
					<input type="checkbox" id="id<?=(++$i)?>" name="CRM" <? if ($arResult["ITEM"]["CRM"] == "Y") { ?>checked<? } ?> value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=$i?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="id<?=$i?>" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_EDIT_CRM_CHECKING")?>
					</label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-select-block">
							<input id="vi_crm_forward" type="checkbox" name="CRM_FORWARD" <?if($arResult["ITEM"]["CRM_FORWARD"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_FORWARD")?></div>
						</div>
						<script type="text/javascript">
							BX.bind(BX('vi_crm_forward'), 'change', function(e){
								if (BX('vi_crm_forward').checked)
								{
									BX('vi_crm_rule').style.height = '40px';
								}
								else
								{
									BX('vi_crm_rule').style.height = '0';
								}
							});
						</script>
						<div id="vi_crm_rule" class="tel-set-item-select-block tel-set-item-crm-rule" style="<?=($arResult["ITEM"]["CRM_FORWARD"] == "Y"? 'height: 40px': '')?>">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_CHECKING_OMITTED_CALL")?></div>
							<select class="tel-set-inp tel-set-item-select" name="CRM_RULE">
								<option value="<?=CVoxImplantIncoming::RULE_QUEUE?>"<?=(CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_1")?></option>
								<option value="<?=CVoxImplantIncoming::RULE_PSTN?>"<?=(CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_3_3")?></option>
								<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["CRM_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_DEALING_WITH_OMITTED_CALL_2")?></option>
							</select>
						</div>
						<div class="tel-set-item-select-block" style="background-color: #fff; padding-top: 10px;">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE")?></div>
							<select class="tel-set-inp tel-set-item-select" name="CRM_CREATE">
								<?foreach (array("1" => CVoxImplantConfig::CRM_CREATE_NONE, "2" => CVoxImplantConfig::CRM_CREATE_LEAD) as $ii => $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["CRM_CREATE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_CRM_CREATE_".$ii)?></option>
								<?endforeach;?>
							</select>
						</div>
					</div>
				</div>
			</div>
<?
if (IsModuleInstalled("socialnetwork"))
{
	CUtil::InitJSCore(array("socnetlogdest"));
?>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<span class="tel-set-item-num-text"><?=(++$i)?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_EDIT_QUEUE")?></label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-text">
							<?=GetMessage("VI_CONFIG_EDIT_QUEUE_TIP")?>
						</div>
						<div class="tel-set-destination-container" id="users_for_queue"></div>
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_QUEUE_AMOUNT_OF_BEEPS_BEFORE_REDIRECT")?></div>
							<select class="tel-set-inp tel-set-item-select" name="QUEUE_TIME">
								<?foreach (array("3", "4", "5", "6", "7") as $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["QUEUE_TIME"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_QUEUE_AMOUNT_OF_BEEPS_BEFORE_REDIRECT_".$k)?></option>
								<?endforeach;?>
							</select>
						</div>
						<div class="tel-set-item-select-block">
							<input id="vi_timeman" type="checkbox" name="TIMEMAN" <?if($arResult["ITEM"]["TIMEMAN"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TIMEMAN_SUPPORT")?></div>
						</div>
						<?if (!IsModuleInstalled("timeman")):?>
						<script type="text/javascript">
							BX.bind(BX('vi_timeman'), 'change', function(e){
								BX('vi_timeman').checked = false;
								alert('<?=GetMessage(IsModuleInstalled("bitrix24")? "VI_CONFIG_EDIT_TIMEMAN_SUPPORT_B24": "VI_CONFIG_EDIT_TIMEMAN_SUPPORT_CP")?>');
							});
						</script>
						<?endif;?>
					</div>
				</div>
			</div>
<script type="text/javascript">
	BX.ready(function(){
		BX.message({LM_ADD1 : '<?=GetMessageJS("LM_ADD1")?>', LM_ADD2 : '<?=GetMessageJS("LM_ADD2")?>'});
		BX.VoxImplantConfigEdit.initDestination(BX('users_for_queue'), 'QUEUE', <?=CUtil::PhpToJSObject($arParams["DESTINATION"])?>);
	});
</script>
<?
}
?>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<span class="tel-set-item-num-text"><?=++$i?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label class="tel-set-cont-item-title" for="id<?=$i?>"><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_2")?></label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-text">
							<?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_TIP_2")?>
						</div>
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION")?></div>
							<select class="tel-set-inp tel-set-item-select" name="NO_ANSWER_RULE" id="vi_no_answer_rule">
									<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_2")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_PSTN?>"<?=(CVoxImplantIncoming::RULE_PSTN == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_3_2")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>"<?=(CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_5")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_QUEUE?>"<?=(CVoxImplantIncoming::RULE_QUEUE == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_6")?></option>
									<option value="<?=CVoxImplantIncoming::RULE_HUNGUP?>"<?=(CVoxImplantIncoming::RULE_HUNGUP == $arResult["ITEM"]["NO_ANSWER_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_4")?></option>
							</select>
						</div>
						<div class="tel-set-item-select-block tel-set-item-forward-number" id="vi_forward_number" style="<?=(CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["NO_ANSWER_RULE"]? 'height: 55px': '')?>">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_FORWARD_NUMBER")?></div>
							<input class="tel-set-inp" type="text" name="FORWARD_NUMBER" value="<?=htmlspecialcharsbx($arResult["ITEM"]["FORWARD_NUMBER"])?>">
						</div>
						<script type="text/javascript">
							BX.bind(BX('vi_no_answer_rule'), 'change', function(e){
								if (this.options[this.selectedIndex].value == '<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>')
								{
									BX('vi_forward_number').style.height = '55px';
								}
								else
								{
									BX('vi_forward_number').style.height = '0';
								}
							});
						</script>
					</div>
				</div>
			</div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input name="RECORDING" type="hidden" value="N" />
					<input type="checkbox" id="id<?=(++$i)?>" name="RECORDING" <?if($arResult["ITEM"]["RECORDING"] == "Y") { ?>checked<? }?> value="Y" class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text"><?=$i?>.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="id<?=$i?>" class="tel-set-cont-item-title">
						<?=GetMessage("VI_CONFIG_EDIT_RECORD")?>
						<span class="tel-set-cont-item-title-description"><?=GetMessage("VI_CONFIG_EDIT_RECORD_TIP")?></span>
					</label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-alert">
							<?=GetMessage("VI_CONFIG_EDIT_RECORD_TIP2")?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- work time-->
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">

					&nbsp;<input type="checkbox" name="WORKTIME_ENABLE" id="WORKTIME_ENABLE" class="tel-set-checkbox" value="Y" <?if ($arResult["ITEM"]["WORKTIME_ENABLE"] === "Y"):?>checked="checked"<?endif?>/>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="WORKTIME_ENABLE" class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_ENABLE")?></label>
					<div class="tel-set-item-cont tel-set-item-crm-rule" id="vi_worktime" <?if ($arResult["ITEM"]["WORKTIME_ENABLE"] == "Y"):?>style="height: auto"<?endif?>>
						<table>
							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_TIMEZONE")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_TIMEZONE" class="tel-set-inp tel-set-item-select">
										<?if (is_array($arResult["TIME_ZONE_LIST"]) && !empty($arResult["TIME_ZONE_LIST"])):?>
											<?foreach($arResult["TIME_ZONE_LIST"] as $tz=>$tz_name):?>
												<option value="<?=htmlspecialcharsbx($tz)?>"<?=($arResult["ITEM"]["WORKTIME_TIMEZONE"] == $tz? ' selected="selected"' : '')?>><?=htmlspecialcharsbx($tz_name)?></option>
											<?endforeach?>
										<?endif?>
									</select>
								</td>
							</tr>
							<?if (!empty($arResult["WORKTIME_LIST_FROM"]) && !empty($arResult["WORKTIME_LIST_TO"])):?>
							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_TIME")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_FROM" class="tel-set-inp tel-set-item-select" style="min-width: 70px">
										<?foreach($arResult["WORKTIME_LIST_FROM"] as $key => $val):?>
											<option value="<?= $key?>" <?if ($arResult["ITEM"]["WORKTIME_FROM"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
										<?endforeach;?>
									</select>
									&nbsp; &mdash; &nbsp;
									<select name="WORKTIME_TO" class="tel-set-inp tel-set-item-select" style="min-width: 70px">
										<?foreach($arResult["WORKTIME_LIST_TO"] as $key => $val):?>
											<option value="<?= $key?>" <?if ($arResult["ITEM"]["WORKTIME_TO"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
										<?endforeach;?>
									</select>
								</td>
							</tr>
							<?endif?>

							<tr>
								<td>
									<div class="tel-set-item-select-text" style="vertical-align: top"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select size="7" multiple=true name="WORKTIME_DAYOFF[]" class="tel-set-inp tel-set-item-select-multiple ">
										<?foreach($arResult["WEEK_DAYS"] as $day):?>
											<option value="<?=$day?>" <?=(is_array($arResult["ITEM"]["WORKTIME_DAYOFF"]) && in_array($day, $arResult["ITEM"]["WORKTIME_DAYOFF"]) ? ' selected="selected"' : '')?>><?= GetMessage('VI_CONFIG_WEEK_'.$day)?></option>
										<?endforeach;?>
									</select>
								</td>
							</tr>

							<tr>
								<td style="vertical-align: top; padding-top: 12px;">
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS")?></div>
								</td>
								<td style="vertical-align: top; padding-top: 12px;">&nbsp; &mdash; &nbsp;</td>
								<td>
									<input type="text" name="WORKTIME_HOLIDAYS" class="tel-set-inp" value="<?=htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_HOLIDAYS"])?>"/>
									<div class="tel-set-item-text" style="margin-top: 5px">(<?=GetMessage("VI_CONFIG_EDIT_WORKTIME_HOLIDAYS_EXAMPLE")?>)</div>
								</td>
							</tr>

							<tr>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_RULE")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<select name="WORKTIME_DAYOFF_RULE" id="WORKTIME_DAYOFF_RULE" class="tel-set-inp tel-set-item-select">
										<option value="<?=CVoxImplantIncoming::RULE_VOICEMAIL?>"<?=(CVoxImplantIncoming::RULE_VOICEMAIL == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_2")?></option>
										<option value="<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>"<?=(CVoxImplantIncoming::RULE_PSTN_SPECIFIC == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_5")?></option>
										<option value="<?=CVoxImplantIncoming::RULE_HUNGUP?>"<?=(CVoxImplantIncoming::RULE_HUNGUP == $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_NO_ANSWER_ACTION_4")?></option>
									</select>
								</td>
							</tr>

							<tr id="vi_dayoff_number" <?if (CVoxImplantIncoming::RULE_PSTN_SPECIFIC != $arResult["ITEM"]["WORKTIME_DAYOFF_RULE"]):?>style="display: none"<?endif?>>
								<td>
									<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_NUMBER")?></div>
								</td>
								<td>&nbsp; &mdash; &nbsp;</td>
								<td>
									<input type="text" name="WORKTIME_DAYOFF_NUMBER" class="tel-set-inp" value="<?=htmlspecialcharsbx($arResult["ITEM"]["WORKTIME_DAYOFF_NUMBER"])?>"/>
								</td>
							</tr>

							<?
							$dayOffMelody = array(
								"MELODY" => (array_key_exists("~WORKTIME_DAYOFF_MELODY", $arResult["ITEM"]) ? $arResult["ITEM"]["~WORKTIME_DAYOFF_MELODY"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOICEMAIL"])),
								"MELODY_ID" => $arResult["ITEM"]["WORKTIME_DAYOFF_MELODY"],
								"DEFAULT_MELODY" => $defaultM["MELODY_VOICEMAIL"],
								"INPUT_NAME" => "WORKTIME_DAYOFF_MELODY"
							);
							$id = "voximplant_dayoff";
							?>
							<tr>
								<td colspan="3" style="padding-top: 25px;">
									<label class="tel-set-cont-item-title"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_MELODY")?></label>
									<div class="tel-set-item-cont">
										<div class="tel-set-item-text"><?=GetMessage("VI_CONFIG_EDIT_WORKTIME_DAYOFF_MELODY_TEXT")?></div>
										<div class="tel-set-melody-block">
											<span class="tel-set-player-wrap">
												<?$APPLICATION->IncludeComponent(
													"bitrix:player",
													"",
													Array(
														"PLAYER_ID" => $id."player",
														"PLAYER_TYPE" => "flv",
														"USE_PLAYLIST" => "N",
														"PATH" => $dayOffMelody["MELODY"],
														"PROVIDER" => "sound",
														"STREAMER" => "",
														"WIDTH" => "217",
														"HEIGHT" => "24",
														"PREVIEW" => "",
														"FILE_TITLE" => "",
														"FILE_DURATION" => "",
														"FILE_AUTHOR" => "",
														"FILE_DATE" => "",
														"FILE_DESCRIPTION" => "",
														"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
														"SKIN" => "",
														"CONTROLBAR" => "bottom",
														"WMODE" => "opaque",
														"LOGO" => "",
														"LOGO_LINK" => "",
														"LOGO_POSITION" => "none",
														"PLUGINS" => array(),
														"ADDITIONAL_FLASHVARS" => "",
														"AUTOSTART" => "N",
														"REPEAT" => "none",
														"VOLUME" => "90",
														"MUTE" => "N",
														"ADVANCED_MODE_SETTINGS" => "Y",
														"BUFFER_LENGTH" => "2",
														"ALLOW_SWF" => "N"
													),
													null,
													Array(
														'HIDE_ICONS' => 'Y'
													)
												);?>
											</span>
											<span style="display: none;"><?
												$APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
													array(
														'INPUT_CAPTION' => GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE"),
														'INPUT_NAME' => $dayOffMelody["INPUT_NAME"],
														'INPUT_NAME_UNSAVED' => $dayOffMelody["INPUT_NAME"]."_TMP",
														'INPUT_VALUE' => array($dayOffMelody["MELODY_ID"]),
														'MAX_FILE_SIZE' => 2097152,
														'MODULE_ID' => 'voximplant',
														'CONTROL_ID' => $id,
														'MULTIPLE' => 'N',
														'ALLOW_UPLOAD' => 'F',
														'ALLOW_UPLOAD_EXT' => 'mp3'
													),
													$this->component,
													array("HIDE_ICONS" => true)
												);?></span>
											<span class="tel-set-melody-item">
												<span class="tel-set-item-melody-link tel-set-item-melody-link-active" id="<?=$id?>span"><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE")?></span>
												<span class="tel-set-melody-description" id="<?=$id?>notice" ><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP")?></span>
											</span>
											<span class="tel-set-melody-item" id="<?=$id?>default" <?if ($dayOffMelody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
												<span class="tel-set-item-melody-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
											</span>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		<script>
			BX.ready(function(){
				window.BX.VoxImplantConfigEdit.loadMelody('<?=CUtil::JSEscape($id)?>', <?=CUtil::PhpToJSObject($dayOffMelody)?>);

				BX.bind(BX('WORKTIME_ENABLE'), 'change', function(e){
					if (BX('WORKTIME_ENABLE').checked)
					{
						BX('vi_worktime').style.height = '464px';
						setTimeout(function(){BX('vi_worktime').style.height = 'auto';}, 500);
					}
					else
					{
						BX('vi_worktime').style.height = '464px';
						setTimeout(function(){BX('vi_worktime').style.height = '0';}, 100);
					}
				});

				BX.bind(BX('WORKTIME_DAYOFF_RULE'), 'change', function(e){
					if (this.options[this.selectedIndex].value == '<?=CVoxImplantIncoming::RULE_PSTN_SPECIFIC?>')
					{
						BX('vi_dayoff_number').style.display = '';
					}
					else
					{
						BX('vi_dayoff_number').style.display = 'none';
					}
				});
			});
		</script>
		<!-- //work time-->
		<!-- melody -->
		<div class="tel-set-cont-block">
			<div class="tel-set-cont-title"><?=GetMessage("VI_CONFIG_EDIT_TUNES")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num"></div>
				<div class="tel-set-item-cont-block">
					<div class="tel-set-item-cont">
						<div class="tel-set-item-select-block">
							<div class="tel-set-item-select-text"><?=GetMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE")?></div>
							<select class="tel-set-inp tel-set-item-select" name="MELODY_LANG">
								<?foreach (array("RU", "EN", "DE") as $k):?>
									<option value="<?=$k?>"<?=($k == $arResult["ITEM"]["MELODY_LANG"] ? " selected" : "")?>><?=GetMessage("VI_CONFIG_EDIT_TUNES_LANGUAGE_".$k)?></option>
								<?endforeach;?>
							</select>
						</div>
					</div>
				</div>
			</div>
<?
$melodies = array(
	array(
		"TITLE" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_WELCOMING_TUNE_TIP"),
		"MELODY" => (array_key_exists("~MELODY_WELCOME", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WELCOME"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_WELCOME"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_WELCOME"],
		"DEFAULT_MELODY" => $defaultM["MELODY_WELCOME"],
		"CHECKBOX" => "MELODY_WELCOME_ENABLE",
		"INPUT_NAME" => "MELODY_WELCOME"
	),
	array(
		"TITLE" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_WAITING_TUNE_TIP"),
		"MELODY" => (array_key_exists("~MELODY_WAIT", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_WAIT"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_WAIT"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_WAIT"],
		"DEFAULT_MELODY" => $defaultM["MELODY_WAIT"],
		"INPUT_NAME" => "MELODY_WAIT"
	),
	array(
		"TITLE" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_HOLDING_TUNE_TIP"),
		"MELODY" => (array_key_exists("~MELODY_HOLD", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_HOLD"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_HOLD"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_HOLD"],
		"DEFAULT_MELODY" => $defaultM["MELODY_HOLD"],
		"INPUT_NAME" => "MELODY_HOLD"
	),
	array(
		"TITLE" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE"),
		"TIP" => GetMessage("VI_CONFIG_EDIT_AUTO_ANSWERING_TUNE_TIP"),
		"MELODY" => (array_key_exists("~MELODY_VOICEMAIL", $arResult["ITEM"]) ? $arResult["ITEM"]["~MELODY_VOICEMAIL"]["SRC"] : str_replace("#LANG_ID#", $arResult["ITEM"]["MELODY_LANG"], $defaultM["MELODY_VOICEMAIL"])),
		"MELODY_ID" => $arResult["ITEM"]["MELODY_VOICEMAIL"],
		"DEFAULT_MELODY" => $defaultM["MELODY_VOICEMAIL"],
		"INPUT_NAME" => "MELODY_VOICEMAIL"
	)
);

foreach ($melodies as $i => $melody)
{
	$id = 'voximplant'.$i;
	CHTTP::URN2URI($APPLICATION->GetCurPageParam("mfi_mode=down&fileID=".$fileID."&cid=".$cid."&".bitrix_sessid_get(), array("mfi_mode", "fileID", "cid")))
?>
			<div class="tel-set-item tel-set-item-border">
				<div class="tel-set-item-num">
					<?if (array_key_exists("CHECKBOX", $melody)):?>
					<input name="<?=$melody["CHECKBOX"]?>" type="hidden" value="N" />
					<input type="checkbox" id="checkbox<?=$melody["CHECKBOX"]?>" name="<?=$melody["CHECKBOX"]?>" class="tel-set-checkbox" value="Y" <? if ($arResult["ITEM"][$melody["CHECKBOX"]] == "Y"): ?> checked <? endif; ?> style="margin-top: 3px;" />
					<?endif;?>
				</div>
				<div class="tel-set-item-cont-block">
					<label class="tel-set-cont-item-title" for="checkbox<?=$melody["CHECKBOX"]?>"><?=$melody["TITLE"]?></label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-text"><?=$melody["TIP"]?></div>
						<div class="tel-set-melody-block">
							<span class="tel-set-player-wrap">
								<?

								$APPLICATION->IncludeComponent(
									"bitrix:player",
									"",
									Array(
										"PLAYER_ID" => $id."player",
										"PLAYER_TYPE" => "flv",
										"USE_PLAYLIST" => "N",
										"PATH" => $melody["MELODY"],
										"PROVIDER" => "sound",
										"STREAMER" => "",
										"WIDTH" => "217",
										"HEIGHT" => "24",
										"PREVIEW" => "",
										"FILE_TITLE" => "",
										"FILE_DURATION" => "",
										"FILE_AUTHOR" => "",
										"FILE_DATE" => "",
										"FILE_DESCRIPTION" => "",
										"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
										"SKIN" => "",
										"CONTROLBAR" => "bottom",
										"WMODE" => "opaque",
										"LOGO" => "",
										"LOGO_LINK" => "",
										"LOGO_POSITION" => "none",
										"PLUGINS" => array(),
										"ADDITIONAL_FLASHVARS" => "",
										"AUTOSTART" => "N",
										"REPEAT" => "none",
										"VOLUME" => "90",
										"MUTE" => "N",
										"ADVANCED_MODE_SETTINGS" => "Y",
										"BUFFER_LENGTH" => "2",
										"ALLOW_SWF" => "N"
									),
								null,
								Array(
									'HIDE_ICONS' => 'Y'
								)
								);?>
							</span>
							<span style="display: none;"><?
							$APPLICATION->IncludeComponent('bitrix:main.file.input', '.default',
								array(
									'INPUT_CAPTION' => GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE"),
									'INPUT_NAME' => $melody["INPUT_NAME"],
									'INPUT_NAME_UNSAVED' => $melody["INPUT_NAME"]."_TMP",
									'INPUT_VALUE' => array($melody["MELODY_ID"]),
									'MAX_FILE_SIZE' => 2097152,
									'MODULE_ID' => 'voximplant',
									'CONTROL_ID' => $id,
									'MULTIPLE' => 'N',
									'ALLOW_UPLOAD' => 'F',
									'ALLOW_UPLOAD_EXT' => 'mp3'
								),
								$this->component,
								array("HIDE_ICONS" => true)
							);?></span><?

							?>
							<span class="tel-set-melody-item">
								<span class="tel-set-item-melody-link tel-set-item-melody-link-active" id="<?=$id?>span"><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE")?></span>
								<span class="tel-set-melody-description" id="<?=$id?>notice" ><?=GetMessage("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP")?></span>
							</span>
							<span class="tel-set-melody-item" id="<?=$id?>default" <?if ($melody["MELODY_ID"] <= 0) { ?> style="display:none;" <? } ?>>
								<span class="tel-set-item-melody-link"><?=GetMessage("VI_CONFIG_EDIT_SET_DEFAULT_TUNE")?></span>
							</span>
						</div>
					</div>
				</div>
			</div>
<script type="application/javascript">
BX.ready(function(){
	window.BX.VoxImplantConfigEdit.loadMelody('<?=CUtil::JSEscape($id)?>', <?=CUtil::PhpToJSObject($melody)?>);
});
</script><?
}
?>
<script type="application/javascript">
	BX.message({
		VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP : '<?=GetMessageJS('VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP')?>',
		VI_CONFIG_EDIT_UPLOAD_SUCCESS : '<?=GetMessageJS("VI_CONFIG_EDIT_UPLOAD_SUCCESS")?>'});
</script>
			<div class="tel-set-item tel-set-item-border">
				<div class="tel-set-item-cont-block">
					<div class="tel-set-item-alert">
						<?=GetMessage("VI_CONFIG_EDIT_TUNES_TIP")?>
					</div>
				</div>
			</div>
		</div>
<!-- //melody -->
		<div class="tel-set-footer-btn">
			<span class="webform-button webform-button-accept" onclick="BX.submit(BX('voximplantform'))">
				<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage("VI_CONFIG_EDIT_SAVE")?></span><span class="webform-button-right"></span>
			</span>
			<a href="<?=CVoxImplantMain::GetPublicFolder()?>lines.php?MODE=<?=$arResult["ITEM"]["PORTAL_MODE"]?>" class="webform-small-button-link"><?=GetMessage("VI_CONFIG_EDIT_BACK")?></a>
		</div>
	</div>
</div>


</form>