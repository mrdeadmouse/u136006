<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CAjax::Init();

$this->SetViewTarget("sidebar")?>
<div class="sidebar-block event-list-filter">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="filter-block-title"><?=GetMessage("EVENT_LIST_FILTER_TITLE")?></div>
		<div class="filter-block">
			<form method="GET" name="log_filter" >
				<script type="text/javascript">
					var arFltFeaturesID = new Array();
				</script>
				<?if (!$arResult["NO_ACTIVE_FEATURES"]):?>
				<div class="log-filter-title"><?=GetMessage("EVENT_LIST_FILTER_FEATURES_TITLE")?></div>
				<table cellspacing="0" border="0">
					<?  
					$bCheckedAll = true;
					foreach ($arResult["ActiveFeatures"] as $featureID => $featureName):
						?>
						<tr>
							<td>
								<script type="text/javascript">
									arFltFeaturesID.push('<?=$featureID?>');
								</script>
								<?																
								if (array_key_exists("flt_event_id", $_REQUEST) && in_array($featureID, $_REQUEST["flt_event_id"]) || empty($arParams["EVENT_ID"]) || (!empty($arParams["EVENT_ID"]) && in_array($featureID, $arParams["EVENT_ID"])))
									$bChecked = true;
								else
								{
									$bChecked = false;
									$bCheckedAll = false;
								}
								?>
								<div class="event-list-filter-checkbox"><nobr><input type="checkbox" id="flt_event_id_<?=$featureID?>" name="flt_event_id[]" value="<?=$featureID?>" <?=($bChecked ? "checked" : "")?> onclick="__logFilterClick('<?=$featureID?>')"> <label for="flt_event_id_<?=$featureID?>"><?=$featureName?></label></nobr></div>                                    
							</td>
						</tr>
						<?
					endforeach;
					?>
				</table>
				<div class="event-list-filter-line"></div>
				<?endif;?>
				<div style="width: 200px;">
					<div class="event-list-filter-createdby-title"><?=GetMessage("EVENT_LIST_FILTER_CREATED_BY");?></div>
					<? 
					if (IsModuleInstalled("intranet")):
						$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:intranet.user.selector', '', array(
							'INPUT_NAME' => "flt_created_by_id",
							'INPUT_NAME_STRING' => "flt_created_by_string",
							'INPUT_NAME_SUSPICIOUS' => "flt_created_by_suspicious",
							'INPUT_VALUE_STRING' => htmlspecialcharsback($_REQUEST["flt_created_by_string"]),
							'EXTERNAL' => 'A',
							'MULTIPLE' => 'N',
							),
							false,
							array("HIDE_ICONS" => "Y")
						);		                
					else:
						$APPLICATION->IncludeComponent("bitrix:socialnetwork.user_search_input", ".default", array(
								"TEXT" => 'size="20"',
								"EXTRANET" => "I",
								"HIDE_ICONS" => "Y",
								"NAME" => "flt_created_by_id",
								"VALUE" => $_REQUEST["flt_created_by_id"],
							)
						);								
					endif;
					?>
				</div>
				<div class="event-list-filter-line"></div>
				<div class="filter-field filter-field-date-combobox">
					<label for="flt-date-datesel" class="filter-field-title"><?=GetMessage("EVENT_LIST_FILTER_DATE");?></label>
					<select name="flt_date_datesel" onchange="__logOnDateChange(this)" class="filter-dropdown" id="flt-date-datesel">
					<?
					foreach($arResult["DATE_FILTER"] as $k=>$v):
						?>
						<option value="<?=$k?>"<?if($_REQUEST["flt_date_datesel"] == $k) echo ' selected="selected"'?>><?=$v?></option>					
						<?
					endforeach;
					?>
					</select>
				</div>
				<span class="filter-field filter-day-interval" style="display:none" id="flt_date_day_span">
					<input type="text" name="flt_date_days" value="<?=htmlspecialcharsbx($_REQUEST["flt_date_days"])?>" class="filter-date-days" size="2" /> <?echo GetMessage("EVENT_LIST_DATE_FILTER_DAYS")?>
				</span>
				<span class="filter-date-interval filter-date-interval-after filter-date-interval-before">
					<span class="filter-field filter-date-interval-from" style="display:none" id="flt_date_from_span">
						<input type="text" name="flt_date_from" value="<?=(array_key_exists("LOG_DATE_FROM", $arParams) ? htmlspecialcharsbx($arParams["LOG_DATE_FROM"]) : "")?>" class="filter-date-interval-from" /><?
						$APPLICATION->IncludeComponent(
							"bitrix:main.calendar",
							"",
							array(
								"SHOW_INPUT"	=> "N",
								"INPUT_NAME"	=> "flt_date_from",
								"INPUT_VALUE"	=> (array_key_exists("LOG_DATE_FROM", $arParams) ? htmlspecialcharsbx($arParams["LOG_DATE_FROM"]) : ""),
								"FORM_NAME"		=> "log_filter",
							),
							$component,
							array("HIDE_ICONS"	=> true)
						);?></span>
					<span class="filter-date-interval-hellip" style="display:none" id="flt_date_hellip_span">&hellip;</span>
					<span class="filter-field filter-date-interval-to" style="display:none" id="flt_date_to_span">
						<input type="text" name="flt_date_to" value="<?=(array_key_exists("LOG_DATE_TO", $arParams) ? htmlspecialcharsbx($arParams["LOG_DATE_TO"]) : "")?>" class="filter-date-interval-to" /><?
						$APPLICATION->IncludeComponent(
							"bitrix:main.calendar",
							"",
							array(
								"SHOW_INPUT"	=> "N",
								"INPUT_NAME"	=> "flt_date_to",
								"INPUT_VALUE"	=> (array_key_exists("LOG_DATE_TO", $arParams) ? htmlspecialcharsbx($arParams["LOG_DATE_TO"]) : ""),
								"FORM_NAME"		=> "log_filter",
							),
							$component,
							array("HIDE_ICONS"	=> true)
						);?></span>
				</span>
				<script type="text/javascript">
					BX.ready(function(){__logOnDateChange(document.forms['log_filter'].flt_date_datesel)});
				</script>
				<div class="event-list-filter-line"></div>
				<?
				if (array_key_exists("flt_show_hidden", $_REQUEST) && $_REQUEST["flt_show_hidden"] == "Y")
					$bChecked = true;
				else
					$bChecked = false;
				?>
				<input type="hidden" id="flt_event_id_all" name="flt_event_id_all" value="<?=($bCheckedAll ? "Y" : "")?>">
				<div class="event-list-filter-submit"><input type="submit" name="log_filter_submit" value="<?=GetMessage("EVENT_LIST_SUBMIT")?>"></div>
				<?echo bitrix_sessid_post();?>
			</form>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>                    
<?$this->EndViewTarget("sidebar");

if ($arResult["NO_ACTIVE_FEATURES"])
{
	ShowError(GetMessage("EVENT_LIST_NO_ACTIVE_FEATURES_ERROR"));
	return;
}

if ($arResult["EVENT"])
{
	foreach ($arResult["EVENT"] as $date => $arEvents)
	{	
	?>
		<span class="event-list-header-center" nowrap><span class="event-list-header-day"><nobr><?=$date?></nobr></span></span>
		<div class="event-list-date-line"></div>	
	<?
		foreach ($arEvents as $arEvent)
		{
		?>
			<div class="event-list-item">
				<span class="event-list-item-avatar" <?
				if (
					array_key_exists("avatar", $arEvent["user"])
					&& strlen($arEvent["user"]["avatar"]) > 0
				)
				{
					?>style="background: url('<?=$arEvent["user"]["avatar"]?>') no-repeat 0 0;"<?
				}?>>
				</span>   
				<span class="event-list-item-body">
					<div class="event-list-item-header">
						<span class="event-list-item-createdby">
							<a href="<?=CComponentEngine::MakePathFromTemplate($arParams["USER_PATH"], array("user_id" => $arEvent["user"]["id"], "SITE_ID" => SITE_DIR))?>"><?=$arEvent["user"]["name"]?></a>
						</span>
						<span class="event-list-item-title"><?=$arEvent["pageURL"]?></span>
					</div>
					<div class="event-list-item-content">												
						<div class=" event-list-message-show">
							<?
								if ($arEvent["eventURL"] != "")
									$eventName = "<a href =".$arEvent['eventURL'].">\"".htmlspecialcharsbx($arEvent["eventName"])."\"</a>";
								else
									$eventName = "\"".htmlspecialcharsbx($arEvent["eventName"])."\"";
								echo str_replace("#NAME#", $eventName, $arEvent["eventType"]);
							?>                               												
						</div>
						<div class="event-list-clear"></div>
					</div>
					<div class="event-list-item-footer"><span class="event-list-item-time"><?=$arEvent["time"]?></span><span class="event-list-item-transport"></span></div>
				</span>   
			</div>
		<?
		}
	}
	$arResult["NAV"]->NavPrint(GetMessage("EVENT_LIST_PAGE_NAV"));
}
else
{
	echo GetMessage("EVENT_LIST_NO_UPDATES");
}
?>

