<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$id = $arResult['ID'];
?>
<div class="calendar-ev-block-wrapp">
	<div class="post-item-calendar-event">
		<div class="calendar-ev-cont-top">
			<div class="calendar-ev-date-icon">
				<div class="calendar-ev-date-top"><?= $arResult['EVENT']['FROM_WEEK_DAY']?></div>
				<div class="calendar-ev-date-num"><?= $arResult['EVENT']['FROM_MONTH_DAY']?></div>
			</div>
			<div class="calendar-event-title"><?= GetMessage('ECLFV_EVENT_NAME')?>:</div>
			<div class="calendar-event-title-name"><?= htmlspecialcharsex($arResult['EVENT']['NAME'])?></div>
		</div>
		<div class="calendar-event-item">
			<div class="calendar-event-label"><?= GetMessage('ECLFV_EVENT_START')?>:</div>
			<span class="calendar-event-text" id="feed-event-view-from-<?=$id?>"></span>
		</div>
		<?if ($arResult['EVENT']['RRULE'] !== ''):?>
		<?
		$RRULE = CCalendarEvent::ParseRRULE($arResult['EVENT']['RRULE']);
		switch ($RRULE['FREQ'])
		{
			case 'DAILY':
				if ($RRULE['INTERVAL'] == 1)
					$repeatHTML = GetMessage('EC_RRULE_EVERY_DAY');
				else
					$repeatHTML = GetMessage('EC_RRULE_EVERY_DAY_1', array('#DAY#' => $RRULE['INTERVAL']));
				break;
			case 'WEEKLY':

				$daysList = array();
				foreach ($RRULE['BYDAY'] as $day)
					$daysList[] = GetMessage('EC_'.$day);
				$daysList = implode(', ', $daysList);
				if ($RRULE['INTERVAL'] == 1)
					$repeatHTML = GetMessage('EC_RRULE_EVERY_WEEK', array('#DAYS_LIST#' => $daysList));
				else
					$repeatHTML = GetMessage('EC_RRULE_EVERY_WEEK_1', array('#WEEK#' => $RRULE['INTERVAL'], '#DAYS_LIST#' => $daysList));
				break;
			case 'MONTHLY':
				if ($RRULE['INTERVAL'] == 1)
					$repeatHTML = GetMessage('EC_RRULE_EVERY_MONTH');
				else
					$repeatHTML = GetMessage('EC_RRULE_EVERY_MONTH_1', array('#MONTH#' => $RRULE['INTERVAL']));
				break;
			case 'YEARLY':
				if ($RRULE['INTERVAL'] == 1)
					$repeatHTML = GetMessage('EC_RRULE_EVERY_YEAR', array('#DAY#' => 0, '#MONTH#' => 0));
				else
					$repeatHTML = GetMessage('EC_RRULE_EVERY_YEAR_1', array('#YEAR#' => $RRULE['INTERVAL'], '#DAY#' => 0, '#MONTH#' => 0));
				break;
		}

		if ($RRULE['UNTIL'] != '' && date('dmY', $RRULE['UNTIL']) != '01012038')
			$repeatHTML .= '<br>'.GetMessage('EC_RRULE_UNTIL', array('#UNTIL_DATE#' => FormatDate(CCalendar::DFormat(false), $RRULE['UNTIL'])));
		?>
		<div class="calendar-event-item">
			<div class="calendar-event-label"><?=GetMessage('EC_T_REPEAT')?>:</div>
			<span class="calendar-event-text"><?= $repeatHTML?></span>
		</div>
		<?endif;/*RRULE*/?>
		<?if (!empty($arResult['EVENT']['LOCATION'])):?>
		<div class="calendar-event-item">
			<div class="calendar-event-label"><?= GetMessage('ECLFV_EVENT_LOCATION')?>:</div>
			<span class="calendar-event-text"><?= htmlspecialcharsex($arResult['EVENT']['LOCATION'])?></span>
		</div>
		<?endif;?>

		<?
		$bAcc = count($arResult['EVENT']['ACCEPTED_ATTENDEES']) > 0;
		$bDec = count($arResult['EVENT']['DECLINED_ATTENDEES']) > 0;
		?>
		<div id="feed-event-attendees-cont-<?=$id?>" class="calendar-event-item" style="display:<?= (($bAcc || $bDec) ? "block" : "none")?>;">
			<div class="calendar-event-label"><?= GetMessage('ECLFV_INVITE_ATTENDEES')?>:</div>
			<span id="feed-event-attendees-wrap-<?=$id?>">
				<? if ($bAcc):?>
				<span class="calendar-event-text"><?= GetMessage('ECLFV_INVITE_ATTENDEES_ACC', array('#ATTENDEES_NUM#' => CCalendar::GetAttendeesMessage(count($arResult['EVENT']['ACCEPTED_ATTENDEES']))))?></span><?if ($bDec){echo ', ';}?>
				<?endif;?>
				<? if ($bDec):?>
					<span  class="calendar-event-text"><?= GetMessage('ECLFV_INVITE_ATTENDEES_DEC', array('#ATTENDEES_NUM#' => CCalendar::GetAttendeesMessage(count($arResult['EVENT']['DECLINED_ATTENDEES']))))?></span>
				<?endif;?>
			</span>
		</div>

		<div class="calendar-invite-cont" id="feed-event-invite-controls-<?=$id?>">
			<div class="calendar-event-buttons-block">
				<div class="calendar-event-but-part">
					<a href="#" id="feed-event-accept-<?=$id?>" class="calendar-event-accept" ontouchstart="this.classList.toggle('calendar-event-accept-active')" ontouchend="this.classList.toggle('calendar-event-accept-active')"><?= GetMessage('EC_ACCEPT_MEETING')?></a>
				</div><div class="calendar-event-but-part">
					<a href="#" id="feed-event-decline-<?=$id?>" class="calendar-event-refuse" ontouchstart="this.classList.toggle('calendar-event-refuse-active')" ontouchend="this.classList.toggle('calendar-event-refuse-active')"><?= GetMessage('EC_DEL_ENCOUNTER')?></a>
				</div>
			</div>

			<div class="calendar-status-accepted calendar-event-text" id="feed-event-stat-link-y-<?= $id?>">
				<?= GetMessage('ECLFV_EVENT_ACCEPTED')?>
			</div>

			<div class="calendar-status-declined calendar-event-text" id="feed-event-stat-link-n-<?= $id?>">
				<?= GetMessage('ECLFV_EVENT_DECLINED')?>
			</div>
		</div>
	</div>
</div>

<script>
	if (typeof(oMSL) == "object")
	{
		oMSL.registerScripts('<?=CUtil::JSEscape($this->GetFolder()."/script.js")?>');
	}

	setTimeout(function() { // wait for oMSL.loadScripts
		if (!window.oViewEventManager)
			window.oViewEventManager = {};
		window.oViewEventManager[('<?= $arResult['EVENT']['ID']?>' || 0)] = new window.ViewEventManager(<?=CUtil::PhpToJSObject(
		array(
			"id" => $id,
			"eventId" => $arResult['EVENT']['ID'],
			"EVENT" => array(
				"IS_MEETING" => $arResult['EVENT']['IS_MEETING'],
				"DT_FROM_TS" => $arResult['EVENT']['DT_FROM_TS'],
				"DT_TO_TS" => $arResult['EVENT']['DT_TO_TS'],
				"DT_SKIP_TIME" => $arResult['EVENT']['DT_SKIP_TIME'],
				"DT_LENGTH" => $arResult['EVENT']['DT_LENGTH']
			),
			"attendees" => $arResult['ATTENDEES_INDEX'],
			"actionUrl" => "/mobile/ajax.php",
			"viewEventUrlTemplate" => $arParams['EVENT_TEMPLATE_URL'],
			"EC_JS_DEL_EVENT_CONFIRM" => GetMessageJS('EC_JS_DEL_EVENT_CONFIRM'),
			"ECLFV_INVITE_ATTENDEES_ACC" => GetMessageJS('ECLFV_INVITE_ATTENDEES_ACC'),
			"ECLFV_INVITE_ATTENDEES_DEC" => GetMessageJS('ECLFV_INVITE_ATTENDEES_DEC'),
			"AJAX_PARAMS" => array(
				"MOBILE" => "Y"
			)
		));?>
		);
	}, 1000);

</script>

<?/* Don't delete or change html comments below. It used to display results */?>
<!--#BX_FEED_EVENT_FOOTER_MESSAGE#-->
<?if ($arResult['UF_WEBDAV_CAL_EVENT']):?>
<div id="bx-feed-cal-view-files-<?=$id?>" class="feed-cal-view-uf-block">
<?$APPLICATION->IncludeComponent(
	"bitrix:system.field.view",
	$arResult['UF_WEBDAV_CAL_EVENT']["USER_TYPE"]["USER_TYPE_ID"],
	array(
		"arUserField" => $arResult['UF_WEBDAV_CAL_EVENT'],
		"MOBILE" => "Y"
	),
	null,
	array("HIDE_ICONS"=>"Y")
);
?>
</div>
<?endif;?>
<!--#BX_FEED_EVENT_FOOTER_MESSAGE_END#-->