<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CUtil::InitJSCore(array('tags'));

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_tags.css");
?>
<script type="text/javascript">
BX.message({
	TAGS_BUTTON_OK : "<?php echo GetMessage("TASKS_TAGS_OK")?>",
	TAGS_BUTTON_CANCEL : "<?php echo GetMessage("TASKS_TAGS_CANCEL")?>",
	TAGS_BUTTON_SAVE : "<?php echo GetMessage("TASKS_TAGS_SAVE")?>",
	TAGS_BUTTON_DISCARD : "<?php echo GetMessage("TASKS_TAGS_DISCARD")?>",
	TAGS_BUTTON_ADD : "<?php echo GetMessage("TASKS_TAGS_ADD")?>",
	TAGS_BUTTON_CHANGE : "<?php echo GetMessage("TASKS_TAGS_CHANGE")?>"
});

var tags = [
	<?php foreach($arResult["~VALUE"] as $tag):?>
		{ name : "<?php echo CUtil::JSEscape($tag)?>", selected : true },
	<?php endforeach?>
	<?php foreach($arResult["~USER_TAGS"] as $tag):?>
		<?php if (!in_array($tag, $arResult["~VALUE"])):?>
			{ name : "<?php echo CUtil::JSEscape($tag)?>" },
		<?php endif?>
	<?php endforeach?>
	{ name : "" }/**/
];

function TasksShowTagsPopup(e) {
	if(!e) e = window.event;

	tasksTagsPopUp.popupWindow.setBindElement(this.parentNode);

	tasksTagsPopUp.showPopup();
	BX.PreventDefault(e);
}

var tasksTagsPopUp = null;

BX.ready(function() {
	tasksTagsPopUp = new BX.TagsWindow.create("task-tags-popup", null, tags, {
		events : {
			"onSaveButtonClick" : function(tagsWindow) {
				var data = {
					sessid: BX.message("bitrix_sessid"),
					deleted: [],
					oldNames: [],
					newNames: []
				}
				for(var i = 0; i < tagsWindow.windowArea.deletedTags.length; i++)
				{
					data.deleted.push(tagsWindow.windowArea.deletedTags[i].name);
				}
				for(var i in tagsWindow.windowArea.renamedTags)
				{
					data.oldNames.push(i);
					data.newNames.push(tagsWindow.windowArea.renamedTags[i]);
				}
				var url = "/bitrix/components/bitrix/tasks.tags.selector/ajax.php";
				BX.ajax.post(url, data);
			}<?php if ($arParams["SILENT"] != "Y"):?>,
			"onSelectButtonClick" : function(tagsWindow) {
				var tags = this.windowArea.getSelectedTags();
				var tagsString = "";
				for (var i = 0, length = tags.length; i < length; i++)
				{
					if (i > 0)
						tagsString += ", ";
					tagsString += tags[i].name
				}
				BX("task-tags-input").value = tagsString;
				<?php if (strlen($arParams["ON_SELECT"])):?><?php echo $arParams["ON_SELECT"]?>(tags)<?php endif?>
			},
			"onUpdateTagLine" : function(tagsWindow) {

				var tags = this.windowArea.getSelectedTags();
				var tagsString = "";
				for (var i = 0, length = tags.length; i < length; i++)
				{
					if (i > 0)
						tagsString += ", ";
					tagsString += tags[i].name
				}
				var tagLine = BX("task-tags-line");
				BX.cleanNode(tagLine);
				BX.adjust(tagLine, { text : tagsString } );
				if (tagsString.length > 0)
				{
					tagLine.innerHTML += "&nbsp;&nbsp;";
					BX("task-tags-link").innerHTML = BX.message("TAGS_BUTTON_CHANGE");
				}
				else
				{
					BX("task-tags-link").innerHTML = BX.message("TAGS_BUTTON_ADD");
				}
				<?php if (strlen($arParams["ON_UPDATE"])):?><?php echo $arParams["ON_UPDATE"]?>(tags)<?php endif?>
			}<?php elseif($arParams["ON_UPDATE"]):?>,
			"onUpdateTagLine" : <?php echo $arParams["ON_UPDATE"]?>
			<?php endif?>
		}
	});

	BX.bind(
		BX('task-tags-popup'),
		'click',
		function(e)
		{
			if (!e) e = window.event;

			if (e.stopPropagation)
				e.stopPropagation();
			else
				e.cancelBubble = true;
		}
	)

	<?php if ($arParams["SILENT"] != "Y"):?>BX.bind(BX("task-tags-link"), "click", TasksShowTagsPopup);<?php endif?>
});
</script>
<?php if (!isset($arParams["SILENT"]) || $arParams["SILENT"] != "Y"):?>
	<?php if (sizeof($arResult["VALUE"]) > 0):?>
	<span id="task-tags-line"><?php echo implode(", ", $arResult["VALUE"])?>&nbsp;&nbsp;</span><a href="javascript: void(0);" class="webform-field-action-link" id="task-tags-link"><?php echo GetMessage("TASKS_TAGS_CHANGE")?></a>
	<?php else:?>
	<span id="task-tags-line"></span><a href="" class="webform-field-action-link" id="task-tags-link"><?php echo GetMessage("TASKS_TAGS_ADD")?></a>
	<?php endif?>
	<input type="hidden" name="<?php echo $arResult["NAME"]?>" value="<?php echo sizeof($arResult["VALUE"]) > 0 ? implode(", ", $arResult["VALUE"]) : ""?>" id="task-tags-input" />
<?php endif?>