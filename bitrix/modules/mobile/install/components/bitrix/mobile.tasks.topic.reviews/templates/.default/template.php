<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/rating.vote/templates/mobile_like/script_attached.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/rating.vote/templates/mobile_comment_like/script_attached.js");

$bBulkMode = false;
if (isset($arParams['JUST_SHOW_BULK_TEMPLATE']) && ($arParams['JUST_SHOW_BULK_TEMPLATE'] === 'Y'))
	$bBulkMode = true;

if ( ! $bBulkMode )
{
	?>
	<script>
		BX.addCustomEvent(
			'onTasksDataChange', 
			function(data)
			{
				// ignore incorrect events
				if ( ! (
					(data.module_id) 
					&& (data.module_id === 'tasks')
					&& (data.emitter)
					&& (data.emitter !== 'tasks component mobile.tasks.topic.reviews')
				))
					return;

				// actualize data on page
				app.reload();
			}
		);

	</script>
	<?php
}
ob_start();
?>
<div class="post-comments-wrap" id="post-comments-wrap">
	<?php
		$currentUserId = false;
		if (is_object($GLOBALS['USER']))
			$currentUserId = (int) $GLOBALS['USER']->GetID();

		$gotMessagesCount = count($arResult['MESSAGES']);

		if ( ($gotMessagesCount > 0)
			&& (intval($arResult['MESSAGES_COUNT']) > $gotMessagesCount)
		)
		{
			?><div id="post-comment-more" 
				class="post-comments-button" 
				ontouchstart="BX.toggleClass(this, 'post-comments-button-press');"
				ontouchend="BX.toggleClass(this, 'post-comments-button-press');"><?php
					echo str_replace(
						'#COMMENTS_COUNT#', 
						(int) $arResult['MESSAGES_COUNT'], 
						GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_SHOW_ALL_COMMENTS')
					);
			?></div>
			<script>
				var ajaxUrl = "<?echo $arParams["PATH_TO_SNM_ROUTER_AJAX"]?>";
			BX.bind(BX('post-comment-more'), 'click', function(e)
			{
				var moreButton = BX('post-comment-more');
				if (moreButton)
					BX.addClass(moreButton, 'post-comments-button-waiter');

				var postData = {
					'sessid': '<?php echo bitrix_sessid(); ?>',
					'site': '<?php echo CUtil::JSEscape(SITE_ID); ?>',
					'lang': '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>',
					'AVA_WIDTH': <?php echo (int) $arParams['AVATAR_SIZE']['WIDTH']; ?>,
					'AVA_HEIGHT': <?php echo (int) $arParams['AVATAR_SIZE']['HEIGHT']; ?>,
					'DEFAULT_MESSAGES_COUNT': <?php echo (int) $arParams['DEFAULT_MESSAGES_COUNT']; ?>,
					'PATH_TO_FORUM_SMILE': '<?php echo CUtil::JSEscape($arParams['PATH_TO_SMILE']); ?>',
					'FORUM_ID': <?php echo (int) $arParams['FORUM_ID']; ?>,
					'task_id': <?php echo (int) $arParams['TASK_ID']; ?>,
					'user_id': <?php echo (int) $arParams['USER_ID']; ?>,
					'SHOW_RATING': '<?php echo CUtil::JSEscape($arParams['SHOW_RATING']); ?>',
					'RATING_TYPE': '<?php echo CUtil::JSEscape($arParams['RATING_TYPE']); ?>',
					'PATH_TEMPLATE_TO_USER_PROFILE': '<?php echo CUtil::JSEscape($arParams['URL_TEMPLATES_PROFILE_VIEW']); ?>',
					'NAME_TEMPLATE': '<?php echo CUtil::JSEscape($arParams['NAME_TEMPLATE']); ?>',
					'TASK_LAST_VIEWED_DATE': '<?php echo CUtil::JSEscape($arParams['TASK_LAST_VIEWED_DATE']); ?>',
					'DATE_TIME_FORMAT': '<?php echo CUtil::JSEscape($arParams['DATE_TIME_FORMAT']); ?>',
					'subject': 'COMMENTS',
					'action':  'get_task_data',
					'comments_already_loaded': document.getElementById('tasks-already-loaded-on-page').value
				};

				var BMAjaxWrapper = new MobileAjaxWrapper;
				BMAjaxWrapper.Wrap({
					'type': 'json',
					'method': 'POST',
					'url': ajaxUrl,
					'data': postData,
					'callback': function(get_response_data) 
					{
						if (moreButton)
							BX.removeClass(moreButton, 'post-comments-button-waiter');

						if (get_response_data["commentsData"] != 'undefined')
							__MB_TASKS_TASK_TOPIC_REVIEWS_ShowComments(get_response_data["commentsData"]);
					},
					'callback_failure': function()
					{
						if (moreButton)
							BX.removeClass(moreButton, 'post-comments-button-waiter');
					}
				});
			});
			</script>
			<div id="post-comment-hidden" style="display:none; overflow:hidden;"></div><?
		}

		foreach ($arResult['MESSAGES'] as $arComment)
		{
			$strCreatedBy = $arComment['META:FORMATTED_DATA']['AUTHOR_NAME'];

			$bUnread = false;

			if ($arComment['AUTHOR_ID'] != $currentUserId)
				$bUnread = $arComment['META:UNREAD'];

			?><div class="post-comment-block<?=($bUnread ? " post-comment-new" : "")?>"><?
				?><div class="post-user-wrap"><?
					?><div class="avatar" <?=(strlen($arComment['AUTHOR_PHOTO']) > 0 ? " style=\"background:url('".$arComment['AUTHOR_PHOTO']."') no-repeat; background-size: 29px 29px;\"" : "")?>></div><?
					?><div class="post-comment-cont">
						<a href="<?php echo $arComment['META:FORMATTED_DATA']['AUTHOR_URL']; ?>"
							class="post-comment-author"><?php echo $strCreatedBy; 
						?></a><?
						?><div class="post-comment-time"><?php 
							echo $arComment['META:FORMATTED_DATA']['DATETIME_SEXY']; 
						?></div><?php
					?></div><?
				?></div><?
				?><div class="post-comment-text"><?
					if ( ! empty($arComment['POST_MESSAGE_TEXT']) )
					{
						echo $arComment['POST_MESSAGE_TEXT'];
					}
					else
					{
						echo '&nbsp;';
					}
				?></div><?

				if ($arParams['SHOW_RATING'] === 'Y')
				{
					$arRatingParams = $arComment['RATING'];

					$GLOBALS['APPLICATION']->IncludeComponent(
						'bitrix:rating.vote', 
						'mobile_comment_like',	// should be "mobile_comment_".$arParams["RATING_TYPE"],
						$arRatingParams, 
						$component, 
						array('HIDE_ICONS' => 'Y')
					);
				}
				else
				{
					echo '&nbsp;';
				}
			?></div><?
		}
	?><span id="post-comment-last-after"></span>
	<form style="display:none;">
		<input type="hidden" id="tasks-already-loaded-on-page" value="<?php
			echo htmlspecialcharsbx(implode('|', array_keys($arResult['MESSAGES'])));
		?>">
	</form>
</div>
<?php
$arResult['HTML']['COMMENTS'] = ob_get_clean();
?>

<!-- </div>
</div>
-->

<?php
ob_start();
?>
	<form class="tasks-send-message-block" id="comment_send_form">
		<input type="hidden" 
			id="comment_send_form_logid" 
			name="sonet_log_comment_logid" 
			value="<?=$arParams["LOG_ID"]?>">
		<textarea 
			id="comment_send_form_comment" 
			class="tasks-send-message-input" 
			placeholder="<?php echo GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_EDIT_COMMENT_PLACEHOLDER'); ?>"
		></textarea>
		<input 
			type="button" 
			id="comment_send_button" 
			class="tasks-send-message-button" 
			value="<?php echo GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_SEND_COMMENT_BUTTON'); ?>" 
			ontouchstart="BX.toggleClass(this, 'tasks-send-message-button-press');"
			ontouchend="BX.toggleClass(this, 'tasks-send-message-button-press');"
		>
	</form>
	<div style="height:30px; background-color:gray;"></div>
	<div style="display: none;" id="comment_send_button_waiter" class="tasks-send-message-button-waiter"></div>
<script>
	document.addEventListener("DOMContentLoaded", function() {
		BitrixMobile.Utils.autoResizeForm(
				document.getElementById("comment_send_form_comment"),
				document.getElementById("tasks-detail-card-container")
		);
	}, false);

	BX.bind(BX('comment_send_button'), 'click', function(e)
	{
		function disableSubmitButton(status)
		{
			var button = BX('comment_send_button');
			var waiter = BX('comment_send_button_waiter');

			if (button)
			{
				button.disabled = status;

				if (status)
				{
					BX.addClass(button, 'tasks-send-message-button-disabled');
					if (waiter)
					{
						var arPos = BX.pos(button);
						var arPosWaiter = BX.pos(waiter);
						waiter.style.top = (arPos.top + parseInt(arPos.height/2) - 10) + 'px';
						waiter.style.left = (arPos.left + parseInt(arPos.width/2) - 10) + 'px';
						waiter.style.zIndex = 10000;
						waiter.style.display = "block";
					}
				}
				else
				{
					if (waiter)
						waiter.style.display = "none";
					BX.removeClass(button, 'tasks-send-message-button-disabled');
				}
			}
		}

		if (BX('comment_send_form_comment').value.length > 0)
		{
			disableSubmitButton(true);

			var postData = {
				'sessid': '<?php echo bitrix_sessid(); ?>',
				'site': '<?php echo CUtil::JSEscape(SITE_ID); ?>',
				'lang': '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>',
				'AVA_WIDTH': <?php echo (int) $arParams['AVATAR_SIZE']['WIDTH']; ?>,
				'AVA_HEIGHT': <?php echo (int) $arParams['AVATAR_SIZE']['HEIGHT']; ?>,
				'DEFAULT_MESSAGES_COUNT': <?php echo (int) $arParams['DEFAULT_MESSAGES_COUNT']; ?>,
				'PATH_TO_FORUM_SMILE': '<?php echo CUtil::JSEscape($arParams['PATH_TO_SMILE']); ?>',
				'FORUM_ID': <?php echo (int) $arParams['FORUM_ID']; ?>,
				'task_id': <?php echo (int) $arParams['TASK_ID']; ?>,
				'user_id': <?php echo (int) $arParams['USER_ID']; ?>,
				'SHOW_RATING': '<?php echo CUtil::JSEscape($arParams['SHOW_RATING']); ?>',
				'RATING_TYPE': '<?php echo CUtil::JSEscape($arParams['RATING_TYPE']); ?>',
				'PATH_TEMPLATE_TO_USER_PROFILE': '<?php echo CUtil::JSEscape($arParams['URL_TEMPLATES_PROFILE_VIEW']); ?>',
				'NAME_TEMPLATE': '<?php echo CUtil::JSEscape($arParams['NAME_TEMPLATE']); ?>',
				'TASK_LAST_VIEWED_DATE': '<?php echo CUtil::JSEscape($arParams['TASK_LAST_VIEWED_DATE']); ?>',
				'DATE_TIME_FORMAT': '<?php echo CUtil::JSEscape($arParams['DATE_TIME_FORMAT']); ?>',
				'NEW_COMMENT_TEXT': BX('comment_send_form_comment').value,
				subject: 'COMMENTS',
				action:  'perfom_action',
				action_name: 'add_comment'
			};

			var BMAjaxWrapper = new MobileAjaxWrapper;
			BMAjaxWrapper.Wrap({
				'type': 'json',
				'method': 'POST',
				'url': ajaxUrl,
				'data': postData,
				'callback': function(post_response_data) 
				{
					disableSubmitButton(false);

					if (post_response_data["commentsData"] != 'undefined')
					{
						for (var indx in post_response_data["commentsData"])
						{
							if ( ! post_response_data["commentsData"].hasOwnProperty(indx) )
								break;

							arComment = post_response_data["commentsData"][indx];

							var newCommentNode = __MB_TASKS_TASK_TOPIC_REVIEWS_RenderComment(
								arComment,
								function(comNode, ratingNode, vote_id, ratingTypeId, eventEntityId, allowRatingVote, commentId)
								{
									if (comNode)
									{
										BX('post-comment-last-after').parentNode.insertBefore(
											comNode, 
											BX('post-comment-last-after')
										);

										var alreadyLoadedComments = document.getElementById('tasks-already-loaded-on-page').value;
										if (alreadyLoadedComments.length > 0)
											alreadyLoadedComments = alreadyLoadedComments + '|';
										document.getElementById('tasks-already-loaded-on-page').value = alreadyLoadedComments + commentId;
									}
									
									BX('comment_send_form_comment').value = '';

									if (ratingNode)
									{
										if (!window.RatingLikeComments && top.RatingLikeComments)
											RatingLikeComments = top.RatingLikeComments;

										RatingLikeComments.Set(
											vote_id,
											ratingTypeId,
											eventEntityId,
											allowRatingVote
										);

									}

									if (__MB_TASKS_TASK_DETAIL_scrollPageBottom)
										__MB_TASKS_TASK_DETAIL_scrollPageBottom();
								}
							);

							break;
						}

						BitrixMobile.Utils.resetAutoResize(
							BX("comment_send_form_comment"), 
							BX("tasks-detail-card-container")
						);
					}
					else
					{
						disableSubmitButton(false);
					}
				},
				'callback_failure': function()
				{
					disableSubmitButton(false);
				}
			});
		}
	});
</script>
<?php
$arResult['HTML']['SEND_BTN'] = ob_get_clean();
?>
</div>