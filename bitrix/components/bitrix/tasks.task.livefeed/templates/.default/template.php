<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$anchorId = 'tasks-user-tooltip-' . uniqid();
ob_start();
?>
<div class="feed-task-info-block">
	<div class="feed-task-info-label"><?=GetMessage("TASKS_SONET_LOG_LABEL_TITLE")?><div class="feed-task-info-label-icon"></div></div>
	<div class="feed-task-info-text">
		<div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?
				echo $arParams['~MESSAGE_24_1'];
			?></span>
		</div><?php

		if ($arParams["TYPE"] !== 'comment')
		{
			if ($arParams["TYPE"] == "status")
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams['MESSAGE_24_2']?></span>
				</div><?
			}
			elseif (strlen($arParams["MESSAGE_24_2"]) > 0 && strlen($arParams["CHANGES_24"]) > 0)
			{
				?><div class="feed-task-info-text-item">
					<span class="feed-task-info-text-title"><?=$arParams["MESSAGE_24_2"]?>:</span><span class="feed-task-info-text-cont"><?=$arParams["CHANGES_24"]?></span>
				</div><?php
			}
		}
		?>
		<div class="feed-task-info-text-item">
			<span class="feed-task-info-text-title"><?=GetMessage("TASKS_SONET_LOG_RESPONSIBLE_ID")?>:</span><span class="feed-task-info-text-cont"><a id="<?php echo $anchorId; ?>" href="<?=$arResult["PATH_TO_USER"];?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"]);?></a></span>
		</div>
	</div>
	<script>
		BX.tooltip(<?php echo (int) $arResult['USER']['ID']; ?>, '<?php echo $anchorId; ?>', '');
	</script>
</div>
<?php

// This is because socialnetwork do htmlspecialcharsback();
echo htmlspecialcharsbx(ob_get_clean());
