<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$id = $arResult['ID'];
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");

CJSCore::Init(array('popup', 'date'));

\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/classes/general/calendar.php");

$addWidthStyle = IsAmPmMode() ? ' ampm-width' : '';
?>
<div class="feed-event" id="feed-cal-cont<?=$id?>">
<!-- Event Name-->
<div class="feed-event-inp-cont">
	<input type="text" placeholder="<?= GetMessage('ECLF_EVENT_NAME')?>" value="" class="feed-event-inp feed-event-inp-active" name="EVENT_NAME" id="feed-cal-event-name<?=$id?>">
</div>

<!-- Event From/To, Reminder, Location-->
<div class="feed-event-grey-cont">
	<div class="feed-event-from-to-reminder" id="feed-cal-from-to-cont<?=$id?>">
		<input id="event-from-ts<?=$id?>" type="hidden" value="" name="EVENT_FROM_TS"/>
		<input id="event-to-ts<?=$id?>" type="hidden" value="" name="EVENT_TO_TS"/>
		<div class="feed-event-from-to-reminder-inner">
			<span class="feed-event-date">
				<label class="feed-event-date-label" for="<?=$id?>edev-from"><?=GetMessage('ECLF_EVENT_FROM_DATE_TIME')?></label>
				<label class="feed-event-date-label-full-day" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_FROM')?></label>
				<input id="feed-cal-event-from<?=$id?>" type="text" class="calendar-inp calendar-inp-cal"/>
			</span>
			<span class="feed-event-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => 'feed_cal_event_from_time'.$id, 'inputTitle' => GetMessage('ECLF_TIME_FROM'), 'showIcon' => false));?></span>
			<span class="feed-event-mdash">&mdash;</span>
			<span class="feed-event-date">
				<label class="feed-event-date-label" for="<?=$id?>edev-from"><?=GetMessage('ECLF_EVENT_TO_DATE_TIME')?></label>
				<label class="feed-event-date-label-full-day" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_TO')?></label>
				<input id="feed-cal-event-to<?=$id?>" type="text" class="calendar-inp calendar-inp-cal"/>
			</span>
			<span class="feed-event-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => 'feed_cal_event_to_time'.$id, 'inputTitle' => GetMessage('ECLF_TIME_TO'), 'showIcon' => false));?></span>
		</div>

		<div  class="feed-event-full-day">
			<input type="checkbox" id="event-full-day<?=$id?>" value="Y" name="EVENT_FULL_DAY"/>
			<label style="display: inline-block;" for="event-full-day<?=$id?>"><?= GetMessage('ECLF_EVENT_ALL_DAY')?></label>
		</div>

		<div class="feed-event-reminder-collapsed" id="feed-cal-reminder-cont<?=$id?>">
			<input class="feed-event-rem-ch" type="checkbox" id="event-reminder<?=$id?>" value="Y" name="EVENT_REMIND"/>
			<label class="feed-event-rem-lbl" for="event-reminder<?=$id?>"><?= GetMessage('ECLF_EVENT_REMIND')?></label>
			<label class="feed-event-rem-lbl-for" for="event-reminder<?=$id?>"><?= GetMessage('ECLF_EVENT_REMIND_FOR')?></label>

			<span class="feed-event-rem-value">
				<input class="calendar-inp" id="event-remind_count<?=$id?>" type="text" style="width: 30px" size="2" name="EVENT_REMIND_COUNT">
				<select id="event-remind_type<?=$id?>" class="calendar-select" name="EVENT_REMIND_TYPE">
					<option value="min" selected="true"><?=GetMessage('ECLF_REM_MIN')?></option>
					<option value="hour"><?=GetMessage('ECLF_REM_HOUR')?></option>
					<option value="day"><?=GetMessage('ECLF_REM_DAY')?></option>
				</select>
				<?=GetMessage('ECLF_REM_DE_VORHER')?>
			</span>
		</div>
	</div>
	<div  class="feed-event-location">
		<label style="display: inline-block;" for="event-location<?=$id?>"><?= GetMessage('ECLF_EVENT_LOCATION')?></label>
		<input type="text" id="event-location<?=$id?>" value="" class="calendar-inp" name="EVENT_LOCATION"/>
	</div>
</div>

<!-- Description + files -->
<?
$APPLICATION->IncludeComponent(
	"bitrix:main.post.form",
	"",
	array(
		"FORM_ID" => $arParams["FORM_ID"].'_calendar',
		"SHOW_MORE" => "Y",
		"PARSER" => Array(
			"Bold", "Italic", "Underline", "Strike", "ForeColor",
			"FontList", "FontSizeList", "RemoveFormat", "Quote",
			"Code", "CreateLink",
			"Image", "UploadFile",
			"InputVideo",
			"Table", "Justify", "InsertOrderedList",
			"InsertUnorderedList",
			"Source", "MentionUser", "Spoiler"
		),
		"BUTTONS" => Array(
			"UploadFile",
			"CreateLink",
			"InputVideo",
			"Quote",
			//"MentionUser"
		),
		"TEXT" => Array(
			"ID" => "EVENT_DESCRIPTION",
			"NAME" => "EVENT_DESCRIPTION",
			"VALUE" => "",
			"HEIGHT" => "200px"
		),
		//"UPLOAD_FILE" => (array_key_exists("UF_SONET_COM_DOC", $arResult["COMMENT_PROPERTIES"]["DATA"]) ? false : $arResult["COMMENT_PROPERTIES"]["DATA"]["UF_SONET_COM_FILE"]),
		"UPLOAD_WEBDAV_ELEMENT" => $arParams["UPLOAD_WEBDAV_ELEMENT"],
		"UPLOAD_FILE_PARAMS" => array("width" => 400, "height" => 400),
		"FILES" => Array(
			"VALUE" => array(),
			"DEL_LINK" => $arResult["urlToDelImage"],
			"SHOW" => "N"
		),
		"SMILES" => Array("VALUE" => array()),
		"LHE" => array(
			"id" => $arParams["JS_OBJECT_NAME"],
			"documentCSS" => "body {color:#434343;}",
			"jsObjName" => $arParams["JS_OBJECT_NAME"],
			"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
			"fontSize" => "12px",
			"lazyLoad" => true,
			"setFocusAfterShow" => false
		)
	),
	false,
	array(
		"HIDE_ICONS" => "Y"
	)
);
?>

<!-- Destination - "Attendees" -->
<div class="feed-event-destination-block">
	<div class="feed-event-destination-title"><?=GetMessage("ECLF_DESTINATION")?>:</div>
	<div class="feed-event-destination-wrap" id="feed-event-dest-cont">
		<span id="feed-event-dest-item"></span>
	<span class="feed-add-destination-input-box" id="feed-event-dest-input-box">
		<input type="text" value="" class="feed-add-destination-inp" id="feed-event-dest-input">
	</span>
		<a href="#" class="feed-add-destination-link" id="feed-event-dest-add-link"></a>
		<script type="text/javascript">
			destinationFormName = 'cal<?=$this->randString(6)?>';
			BXSocNetLogDestinationDisableBackspace = null;
			BX.SocNetLogDestination.init({
				name : destinationFormName,
				searchInput : BX('feed-event-dest-input'),
				extranetUser :  false,
				bindMainPopup : { 'node' : BX('feed-event-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				bindSearchPopup : { 'node' : BX('feed-event-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
				callback : {
					select : BXEvDestSelectCallback,
					unSelect : BXEvDestUnSelectCallback,
					openDialog : BXEvDestOpenDialogCallback,
					closeDialog : BXEvDestCloseDialogCallback,
					openSearch : BXEvDestOpenDialogCallback,
					closeSearch : BXEvDestCloseSearchCallback
				},
				items : {
					users : <?=(empty($arParams["DESTINATION"]['USERS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['USERS']))?>,
					groups : <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? '{}': "{'UA' : {'id':'UA','name': '".(!empty($arParams["DESTINATION"]['DEPARTMENT']) ? GetMessageJS("MPF_DESTINATION_3"): GetMessageJS("MPF_DESTINATION_4"))."'}}")?>,
					sonetgroups : <?=(empty($arParams["DESTINATION"]['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['SONETGROUPS']))?>,
					department : <?=(empty($arParams["DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['DEPARTMENT']))?>,
					departmentRelation : departmentRelation
				},
				itemsLast : {
					users : <?=(empty($arParams["DESTINATION"]['LAST']['USERS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['USERS']))?>,
					sonetgroups : <?=(empty($arParams["DESTINATION"]['LAST']['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['SONETGROUPS']))?>,
					department : <?=(empty($arParams["DESTINATION"]['LAST']['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['LAST']['DEPARTMENT']))?>,
					groups : <?=($arParams["DESTINATION"]["EXTRANET_USER"] == 'Y'? '{}': "{'UA':true}")?>
				},
				itemsSelected : <?=(empty($arParams["DESTINATION"]['SELECTED'])? '{}': CUtil::PhpToJSObject($arParams["DESTINATION"]['SELECTED']))?>
			});
			BX.bind(BX('feed-event-dest-input'), 'keyup', BXEvDestSearch);
			BX.bind(BX('feed-event-dest-input'), 'keydown', BXEvDestSearchBefore);
			BX.bind(BX('feed-event-dest-add-link'), 'click', function(e){BX.SocNetLogDestination.openDialog(destinationFormName); BX.PreventDefault(e); });
			BX.bind(BX('feed-event-dest-cont'), 'click', function(e){BX.SocNetLogDestination.openDialog(destinationFormName); BX.PreventDefault(e);});
			BXEvDestSetLinkName(destinationFormName);

		</script>
	</div>
</div>

<!-- Additional params -->
<div class="feed-event-additional feed-event-additional-hidden" id="feed-cal-additional">
	<span id="feed-cal-additional-show" class="feed-event-more-link"><span class="feed-event-more-link-text"><?= GetMessage('ECLF_SHOW_ADD_SECT')?></span><span class="feed-event-more-link-icon"></span></span>
	<div class="feed-event-grey-cont">
		<div id="feed-cal-additional-inner" class="feed-event-additional-inner">
			<table>
				<tr>
					<td class="feed-cal-addit-left-c">
						<label for="event-accessibility<?=$id?>"><?= GetMessage('EC_ACCESSIBILITY_S')?></label>
					</td>
					<td class="feed-cal-addit-right-c">
						<select name="EVENT_ACCESSIBILITY" class="calendar-select" id="event-accessibility<?=$id?>" style="width: 250px;">
							<option value="busy" ><?=GetMessage('EC_ACCESSIBILITY_B')?></option>
							<option value="quest"><?=GetMessage('EC_ACCESSIBILITY_Q')?></option>
							<option value="free"><?=GetMessage('EC_ACCESSIBILITY_F')?></option>
							<option value="absent"><?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)</option>
						</select>
					</td>
				</tr>

				<tr>
					<td class="feed-cal-addit-left-c">
						<label for="event-repeat<?=$id?>"><?= GetMessage('EC_T_REPEAT')?></label>
					</td>
					<td class="feed-cal-addit-right-c">
						<select name="EVENT_RRULE[FREQ]" class="calendar-select" id="event-repeat<?=$id?>" style="width:
						250px;">
							<option value="NONE"><?=GetMessage('EC_T_REPEAT_NONE')?></option>
							<option value="DAILY"><?=GetMessage('EC_T_REPEAT_DAILY')?></option>
							<option value="WEEKLY"><?=GetMessage('EC_T_REPEAT_WEEKLY')?></option>
							<option value="MONTHLY"><?=GetMessage('EC_T_REPEAT_MONTHLY')?></option>
							<option value="YEARLY"><?=GetMessage('EC_T_REPEAT_YEARLY')?></option>
						</select>
					</td>
				</tr>
				<tr class="feed-cal-repeat-details" id="event-repeat-details<?=$id?>">
					<td class="feed-cal-addit-left-c"></td>
					<td class="feed-cal-addit-right-c">
						<div class="feed-cal-repeat-details-inner">
							<div>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-daily"><?= GetMessage('EC_JS_EVERY_M')?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-weekly"><?= GetMessage('EC_JS_EVERY_F')
									?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-monthly"><?= GetMessage('EC_JS_EVERY_M')
									?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-yearly"><?= GetMessage('EC_JS_EVERY_M')
									?></span>
								<select id="<?=$id?>_edit_ed_rep_count" class="calendar-select" name="EVENT_RRULE[INTERVAL]">
									<?for ($i = 1; $i < 36; $i++):?>
										<option value="<?=$i?>"><?=$i?></option>
									<?endfor;?>
								</select>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-daily"><?= GetMessage('EC_JS_DAY_P')?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-weekly"><?= GetMessage('EC_JS_WEEK_P')
									?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-monthly"><?= GetMessage('EC_JS_MONTH_P')
									?></span>
								<span class="event-feed-rep-phrase event-feed-rep-phrase-yearly"><?= GetMessage('EC_JS_YEAR_P')
									?></span>

								<span style="display: inline-block; padding-top: 5px;">
									<label for="<?=$id_?>edit-ev-rep-diap-to" class="event-feed-rep-phrase-to"><?=GetMessage('EC_T_DIALOG_STOP_REPEAT')?>:</label>
									<input name="EVENT_RRULE[UNTIL]" class="calendar-inp calendar-inp-cal" id="event-repeat-to-value<?=$id?>" type="hidden"/>
									<input class="calendar-inp calendar-inp-cal" id="event-repeat-to<?=$id?>" type="text" style="width: 100px; font-size: 13px;"/>
								</span>
							</div>
							<div class="feed-cal-week-days-cont">
								<?
								$week_days = CCalendarSceleton::GetWeekDays();
								for($i = 0; $i < 7; $i++):
									$id_ = $id.'bxec_week_day_'.$i;?>
									<input id="<?=$id_?>" type="checkbox" value="<?= $week_days[$i][2]?>" name="EVENT_RRULE[BYDAY][]">
									<label for="<?=$id_?>" title="<?=$week_days[$i][0]?>"><?=$week_days[$i][1]?></label>
								<?endfor;?>
							</div>
						</div>
					</td>
				</tr>

				<tr>
					<td class="feed-cal-addit-left-c">
						<label for="event-section<?=$id?>"><?= GetMessage('EC_T_CALENDAR')?></label>
					</td>
					<td class="feed-cal-addit-right-c">
						<select name="EVENT_SECTION" class="calendar-select" id="event-section<?=$id?>" style="width:250px;">
							<?foreach ($arParams['SECTIONS'] as $section):?>
								<option value="<?= $section['ID']?>"><?= htmlspecialcharsbx($section['NAME'])?></option>
							<?endforeach;?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="feed-cal-addit-left-c">
						<label for="event-importance<?=$id?>"><?= GetMessage('EC_IMPORTANCE_TITLE')?></label>
					</td>
					<td class="feed-cal-addit-right-c">
						<select name="EVENT_IMPORTANCE" class="calendar-select" id="event-importance<?=$id?>" style="width: 250px;">
							<option value="high"><?=GetMessage('EC_IMPORTANCE_H')?></option>
							<option value="normal" selected="true"><?=GetMessage('EC_IMPORTANCE_N')?></option>
							<option value="low"><?=GetMessage('EC_IMPORTANCE_L')?></option>
						</select>
					</td>
				</tr>

				<? if (false && isset($arResult['USER_FIELDS']['UF_CRM_CAL_EVENT'])): ?>
					<?$crmUF = $arResult['USER_FIELDS']['UF_CRM_CAL_EVENT'];?>
				<tr>
					<td class="feed-cal-addit-left-c">
						<label for="event-crm<?=$id?>"><?= htmlspecialcharsbx($crmUF["EDIT_FORM_LABEL"])?></label>
					</td>
					<td class="feed-cal-addit-right-c">
						<?$APPLICATION->IncludeComponent(
							"bitrix:system.field.edit",
							$crmUF["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"bVarsFromForm" => false,
								"arUserField" => $crmUF,
								"form_name" => $arParams["FORM_ID"]
							), null, array("HIDE_ICONS" => "Y")
						);?>
					</td>
				</tr>
				<?endif;?>
			</table>
		</div>
		<span id="feed-cal-additional-hide" class="feed-event-more-link-open"><span class="feed-event-more-link-text"><?= GetMessage('ECLF_HIDE_ADD_SECT')?></span><span class="feed-event-more-link-icon"></span></span>
	</div>
</div>

<script>
window.oEditEventManager = new window.EditEventManager(<?=CUtil::PhpToJSObject(
	array(
		'id' => $id,
		'editorId' => $arParams["JS_OBJECT_NAME"],
		'arEvent' => $arParams['EVENT'],
		'bAMPM' => IsAmPmMode(),
		'message' => array(
			'NoLimits' => GetMessageJS('EC_T_DIALOG_NEVER')
		)
	));?>
);
</script>

</div>