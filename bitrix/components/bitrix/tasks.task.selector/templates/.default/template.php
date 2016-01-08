<?
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<script type="text/javascript">
	TasksTask.ajaxUrl = '<?php echo $this->__component->GetPath()."/ajax.php?lang=".LANGUAGE_ID."&SITE_ID=".$arParams["SITE_ID"]?>';
	TasksTask.lastTasks = <?php echo CUtil::PhpToJSObject($arResult["LAST_TASKS_IDS"])?>;
	TasksTask.filter = <?php echo CUtil::PhpToJSObject($arParams["FILTER"])?>

	var O_<?php echo $arResult["NAME"]?> = new TasksTask("<?php echo $arResult["NAME"]?>", <?php echo $arParams["MULTIPLE"] == "Y" ? "true" : "false"?>);

	O_<?php echo $arResult["NAME"]?>.filter = <?php echo CUtil::PhpToJSObject($arParams["FILTER"])?>;

	<?php foreach($arResult["CURRENT_TASKS"] as $task):?>
		O_<?php echo $arResult["NAME"]?>.arSelected[<?php echo $task["ID"]?>] = {id : <?php echo CUtil::JSEscape($task["ID"])?>, name : "<?php echo CUtil::JSEscape($task["TITLE"])?>", status : <?php echo $task["STATUS"]?>};
		TasksTask.arTasksData[<?php echo $task["ID"]?>] = {id : <?php echo CUtil::JSEscape($task["ID"])?>, name : "<?php echo CUtil::JSEscape($task["TITLE"])?>", status : <?php echo $task["STATUS"]?>};
	<?php endforeach?>

	<?php foreach($arResult["LAST_TASKS"] as $task):?>
		TasksTask.arTasksData[<?php echo $task["ID"]?>] = {id : <?php echo CUtil::JSEscape($task["ID"])?>, name : "<?php echo CUtil::JSEscape($task["TITLE"])?>", status : <?php echo $task["STATUS"]?>};
	<?php endforeach?>

	BX.ready(function() {
		<?php if (strlen($arParams["FORM_NAME"]) > 0 && strlen($arParams["INPUT_NAME"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = document.forms["<?php echo CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
		<?php elseif(strlen($arParams["INPUT_NAME"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>");
		<?php else:?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo $arResult["NAME"]?>_task_input");
		<?php endif?>

		<?php if (strlen($arParams["ON_CHANGE"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.onChange = <?php echo CUtil::JSEscape($arParams["ON_CHANGE"])?>;
		<?php endif?>

		<?php if (strlen($arParams["ON_SELECT"]) > 0):?>
			O_<?php echo $arResult["NAME"]?>.onSelect= <?php echo CUtil::JSEscape($arParams["ON_SELECT"])?>;
		<?php endif?>

		BX.bind(O_<?php echo $arResult["NAME"]?>.searchInput, "keyup", BX.proxy(O_<?php echo $arResult["NAME"]?>.search, O_<?php echo $arResult["NAME"]?>));
	});
</script>

<div id="<?php echo $arParams["NAME"]?>_selector_content" class="finder-box<?php if ($arParams["MULTIPLE"] == "Y"):?> finder-box-multiple<?php endif?>"<?php echo $arParams["POPUP"] == "Y" ? " style=\"display: none;\"" : ""?>>
	<table class="finder-box-layout" cellspacing="0">
		<tr>
			<td class="finder-box-left-column">
				<?php if (!isset($arParams["INPUT_NAME"]) || strlen($arParams["INPUT_NAME"]) == 0):?>
				<div class="finder-box-search"><input name="<?php echo $arResult["NAME"]?>_task_input" id="<?php echo $arResult["NAME"]?>_task_input" class="finder-box-search-textbox" /></div>
				<?php endif?>

				<div class="finder-box-tabs">
					<span class="finder-box-tab finder-box-tab-selected" id="<?php echo $arResult["NAME"]?>_tab_last" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('last');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_LAST_SELECTED")?></span><span class="finder-box-tab-right"></span></span><span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_search" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('search');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_TASK_SEARCH")?></span><span class="finder-box-tab-right"></span></span>
				</div>

				<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>

				<div class="finder-box-tabs-content">
					<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?php echo $arResult["NAME"]?>_last">
						<table class="finder-box-tab-columns" cellspacing="0">
							<tr>
								<td>
									<?php foreach($arResult["LAST_TASKS"] as $key=>$task):?>
										<div class="finder-box-item<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " finder-box-item-selected" : "")?>" id="<?php echo $arResult["NAME"]?>_last_task_<?php echo $task["ID"]?>" onclick="O_<?php echo $arResult["NAME"]?>.select(event)">
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
											<div class="finder-box-item-text"><?php echo $task["TITLE"]?></div>
											<div class="finder-box-item-icon"
												<?php if ($arParams['HIDE_ADD_REMOVE_CONTROLS'] === 'Y') echo ' style="display:none;" '; ?>
												></div>
										</div>
									<?php endforeach?>
									<?php foreach($arResult["CURRENT_TASKS"] as $key=>$task):?>
										<?php if (!in_array($task, $arResult["LAST_TASKS"])):?>
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $task["ID"]?>"<?php echo (in_array($task["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
										<?php endif?>
									<?php endforeach?>
								</td>
							</tr>
						</table>
					</div>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_search"></div>
				</div>
			</td>
			<?php if ($arParams["MULTIPLE"] == "Y"):?>
			<td class="finder-box-right-column" id="<?php echo $arResult["NAME"]?>_selected_tasks">
				<div class="finder-box-selected-title"><?php echo GetMessage("TASKS_TASKS_CURRENT_COUNT")?> (<span id="<?php echo $arResult["NAME"]?>_current_count"><?php echo sizeof($arResult["CURRENT_TASKS"])?></span>)</div>
				<div class="finder-box-selected-items">
					<?php foreach($arResult["CURRENT_TASKS"] as $task):?>
						<div class="finder-box-selected-item" id="<?php echo $arResult["NAME"]?>_task_selected_<?php echo $task["ID"]?>"><div class="finder-box-selected-item-icon" <?php if ($arParams['HIDE_ADD_REMOVE_CONTROLS'] === 'Y') echo ' style="display:none;" '; ?> onclick="O_<?php echo $arResult["NAME"]?>.unselect(<?php echo $task["ID"]?>, this);" id="task-unselect-<?php echo $task["ID"]?>"></div><a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"))?>" target="_blank" class="finder-box-selected-item-text"><?php echo $task["TITLE"]?></a></div>
					<?php endforeach?>
				</div>
			</td>
			<?php endif?>
		</tr>
	</table>
</div>