<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);

if ( ! $arResult['OPTIMIZE_REPEATED_RUN'] )
{
	CUtil::InitJSCore(array('popup','tooltip', 'taskQuickPopups', 'viewer'));

	$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");
	$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

	$loggedInUserId = (int) $GLOBALS['USER']->GetID();

	$loggedInUserFormattedName = CUser::FormatName(
		CSite::GetNameFormat(false),
		array(
			'NAME'        => $USER->GetFirstName(),
			'LAST_NAME'   => $USER->GetLastName(),
			'SECOND_NAME' => $USER->GetSecondName(),
			'LOGIN'       => $USER->GetLogin()
		),
		$bUseLogin = true,
		$bHtmlSpecialChars = false
	);

	$pathToUserProfile = '/company/personal/user/#user_id#/';
	if (isset($arParams["PATH_TO_USER_PROFILE"]))
		$pathToUserProfile = $arParams["PATH_TO_USER_PROFILE"];

	$arPopupOptions = CTasksTools::getPopupOptions();

	?><script type="text/javascript">
	BX.message({
		TASKS_TITLE             : '<?php echo GetMessageJS("TASKS_TITLE")?>',
		TASKS_TITLE_PLACEHOLDER : '<?php echo GetMessageJS("TASKS_TITLE_PLACEHOLDER")?>',
		TASKS_RESPONSIBLE       : '<?php echo GetMessageJS("TASKS_RESPONSIBLE")?>',
		TASKS_TASK_ACCOMPLICES  : '<?php echo GetMessageJS("TASKS_TASK_ACCOMPLICES")?>',
		TASKS_PRIORITY          : '<?php echo GetMessageJS("TASKS_PRIORITY")?>',
		TASKS_PRIORITY_LOW      : '<?php echo GetMessageJS("TASKS_PRIORITY_LOW")?>',
		TASKS_PRIORITY_NORMAL   : '<?php echo GetMessageJS("TASKS_PRIORITY_NORMAL")?>',
		TASKS_PRIORITY_HIGH     : '<?php echo GetMessageJS("TASKS_PRIORITY_HIGH")?>',
		TASKS_DEADLINE          : '<?php echo GetMessageJS("TASKS_DEADLINE")?>',
		TASKS_DESCRIPTION       : '<?php echo GetMessageJS("TASKS_DESCRIPTION")?>',
		TASKS_GROUP             : '<?php echo GetMessageJS("TASKS_GROUP")?>',
		TASKS_UPLOAD_FILES      : '<?php echo GetMessageJS("TASKS_UPLOAD_FILES")?>',
		TASKS_TASK_NUM          : '<?php echo GetMessageJS("TASKS_TASK_NUM")?>',
		TASKS_TITLE_EDIT_TASK   : '<?php echo GetMessageJS("TASKS_TITLE_EDIT_TASK")?>',
		TASKS_TITLE_CREATE_TASK : '<?php echo GetMessageJS("TASKS_TITLE_CREATE_TASK")?>',
		TASKS_META_OPTION_OPENED_DESCRIPTION   : '<?php echo CUtil::JSEscape($arPopupOptions['opened_description']); ?>',
		TASKS_META_OPTION_TASK_CONTROL         : '<?php echo CUtil::JSEscape($arPopupOptions['task_control']); ?>',
		TASKS_META_OPTION_TIME_TRACKING        : '<?php echo CUtil::JSEscape($arPopupOptions['time_tracking']); ?>',
		TASKS_TASK_ALLOW_TIME_TRACKING_DETAILS : '<?php echo GetMessageJS("TASKS_TASK_ALLOW_TIME_TRACKING_DETAILS")?>',
		TASKS_TASK_ALLOW_TIME_TRACKING         : '<?php echo GetMessageJS("TASKS_TASK_ALLOW_TIME_TRACKING")?>',
		TASKS_TASK_TIME_TRACKING_HOURS         : '<?php echo GetMessageJS("TASKS_TASK_TIME_TRACKING_HOURS")?>',
		TASKS_TASK_TIME_TRACKING_MINUTES       : '<?php echo GetMessageJS("TASKS_TASK_TIME_TRACKING_MINUTES")?>',
		TASKS_CONTROL_CHECKBOX                 : '<?php echo GetMessageJS("TASKS_CONTROL_CHECKBOX")?>',

		TASKS_BTN_CREATE_TASK   : '<?php echo GetMessageJS('TASKS_BTN_CREATE_TASK'); ?>',
		TASKS_BTN_CANCEL        : '<?php echo GetMessageJS('TASKS_BTN_CANCEL'); ?>',
		TASKS_BTN_CONTINUE      : '<?php echo GetMessageJS('TASKS_BTN_CONTINUE'); ?>',
		TASKS_BTN_SELECT        : '<?php echo GetMessageJS('TASKS_BTN_SELECT'); ?>',
		TASKS_TIT_CREATE_TASK_2 : '<?php echo GetMessageJS('TASKS_TIT_CREATE_TASK_2'); ?>',
		TASKS_DELETE_CONFIRM    : '<?php echo GetMessageJS('TASKS_DELETE_CONFIRM'); ?>',
		TASKS_GROUP             : '<?php echo GetMessageJS('TASKS_GROUP'); ?>',

		TASKS_LINK_SHOW_FULL_CREATE_FORM    : '<?php echo GetMessageJS('TASKS_LINK_SHOW_FULL_CREATE_FORM'); ?>',
		TASKS_BTN_CREATE_TASK_AND_ONCE_MORE : '<?php echo GetMessageJS('TASKS_BTN_CREATE_TASK_AND_ONCE_MORE'); ?>',
		TASKS_THERE_IS_NO_DEADLINE          : '<?php echo GetMessageJS('TASKS_THERE_IS_NO_DEADLINE'); ?>',
		TASKS_CONFIRM_CLOSE_CREATE_DIALOG   : '<?php echo GetMessageJS('TASKS_CONFIRM_CLOSE_CREATE_DIALOG'); ?>',
		TASKS_TASK_CONFIRM_START_TIMER      : '<?php echo GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER'); ?>',
		TASKS_TASK_CONFIRM_START_TIMER_TITLE : '<?php echo GetMessageJS('TASKS_TASK_CONFIRM_START_TIMER_TITLE'); ?>',

		TASKS_COMPANY_WORKTIME : '<?=intval($arResult['COMPANY_WORKTIME']['END']['H'])?>:<?=intval($arResult['COMPANY_WORKTIME']['END']['M'])?>:0'
	});

	</script><?php
}

?><script type="text/javascript">

var taskIFramePopup;
BX.ready(function() {
	<?php
	if ( ! $arResult['OPTIMIZE_REPEATED_RUN'] )
	{
		?>
		BX.Tasks.lwPopup.ajaxUrl    = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID; ?>";
		BX.Tasks.lwPopup.loggedInUserId = <?php echo $loggedInUserId; ?>;
		BX.Tasks.lwPopup.loggedInUserFormattedName = '<?php echo CUtil::JSEscape($loggedInUserFormattedName); ?>';

		BX.Tasks.lwPopup.pathToUser = "<?php echo CUtil::JSEscape($pathToUserProfile); ?>";
		<?php
	}
	?>

	BX.Tasks.lwPopup.pathToView = "<?php echo CUtil::JSEscape(str_replace("#action#", "view", $arParams["PATH_TO_TASKS"]))?>";
	BX.Tasks.lwPopup.pathToEdit = "<?php echo CUtil::JSEscape(str_replace("#action#", "edit", $arParams["PATH_TO_TASKS"]))?>";

	<?php

	// Replace '#SHOW_ADDED_TASK_DETAIL#' to js function that will show task
	if ($arParams["ON_TASK_ADDED"] === '#SHOW_ADDED_TASK_DETAIL#')
	{
		ob_start();
		?>
		function(task, action, params)
		{
			var skipShow = false;

			if (
				params
				&& (params.multipleTasksAdded === true)
				&& (params.firstTask === false)
			)
			{
				skipShow = true;
			}

			if ( ! skipShow )
				taskIFramePopup.view(task.id);
		}
		<?php
		$arParams['ON_TASK_ADDED'] = ob_get_clean();
	}

	// Ugly hack
	if (strtolower($arParams["ON_TASK_ADDED"]) !== 'bx.donothing')
	{
		?>BX.Tasks.lwPopup.onTaskAdded = <?php echo $arParams["ON_TASK_ADDED"]; ?>;<?php
	}

	if (isset($arParams['ON_TASK_ADDED_MULTIPLE']))
	{
		?>
		BX.Tasks.lwPopup.onTaskAddedMultiple = <?php echo $arParams['ON_TASK_ADDED_MULTIPLE']; ?>;<?php
	}
	?>

	taskIFramePopup	= BX.TasksIFramePopup.create({
		pathToView: "<?php echo CUtil::JSEscape(str_replace("#action#", "view", $arParams["PATH_TO_TASKS"]))?>",
		pathToEdit: "<?php echo CUtil::JSEscape(str_replace("#action#", "edit", $arParams["PATH_TO_TASKS"]))?>",
		events: {
			<?php if (strlen($arParams["ON_BEFORE_SHOW"])):?>
				onBeforeShow: <?php echo CUtil::JSEscape($arParams["ON_BEFORE_SHOW"])?>,
			<?php endif?>
			<?php if (strlen($arParams["ON_AFTER_SHOW"])):?>
				onAfterShow: <?php echo CUtil::JSEscape($arParams["ON_AFTER_SHOW"])?>,
			<?php endif?>
			<?php if (strlen($arParams["ON_BEFORE_HIDE"])):?>
				onBeforeHide: <?php echo CUtil::JSEscape($arParams["ON_BEFORE_HIDE"])?>,
			<?php endif?>
			<?php if (strlen($arParams["ON_AFTER_HIDE"])):?>
				onAfterHide: <?php echo CUtil::JSEscape($arParams["ON_AFTER_HIDE"])?>,
			<?php endif?>
			onTaskAdded: <?php echo $arParams["ON_TASK_ADDED"]?>,
			onTaskChanged: <?php echo CUtil::JSEscape($arParams["ON_TASK_CHANGED"])?>,
			onTaskDeleted: <?php echo CUtil::JSEscape($arParams["ON_TASK_DELETED"])?>
		}
		<?php if ($arParams["TASKS_LIST"] && is_array($arParams["TASKS_LIST"])):?>
			,
			tasksList: <?php echo CUtil::PhpToJSObject($arParams["TASKS_LIST"])?>
		<?php endif?>
	});

	<?php
	if ( ! $arResult['OPTIMIZE_REPEATED_RUN'] )
	{
		?>
		if (!BX.Tasks.lwPopup.createForm.objTemplate)
			BX.Tasks.lwPopup.createForm.objTemplate = new BX.Tasks.componentIframe.objTemplate();
		<?php
	}
	?>
});
</script><?php
