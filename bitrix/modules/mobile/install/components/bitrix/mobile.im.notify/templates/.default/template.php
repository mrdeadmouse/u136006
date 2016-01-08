<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/im_mobile.js").'"></script>');

$frame = \Bitrix\Main\Page\Frame::getInstance();
$frame->setEnable();
$frame->setUseAppCache();
$frame->startDynamicWithID("im_notify_legacy_".$USER->GetId());
if(empty($arResult)):?>
	<div class="notif-block-empty"><?=GetMessage('NM_EMPTY');?></div>
<?else:?>
	<div class="notif-block-wrap" id="notif-block-wrap">
	<?
	$jsIds = "";
	$maxId = 0;
	$newFlag = false;
	$firstNewFlag = true;
	foreach ($arResult as $data):
		$avatarId = "notif-avatar-".randString(5);
		$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';

		$arFormat = Array(
			"tommorow" => "tommorow, ".GetMessage('NM_FORMAT_TIME'),
			"today" => "today, ".GetMessage('NM_FORMAT_TIME'),
			"yesterday" => "yesterday, ".GetMessage('NM_FORMAT_TIME'),
			"" => GetMessage('NM_FORMAT_DATE')
		);
		$maxId = $data['id'] > $maxId? $data['id']: $maxId;
		$data['date'] = FormatDate($arFormat, $data['date']);
		$data['text'] = preg_replace("/<img.*?data-code=\"([^\"]*)\".*?>/i", "$1", $data['text']);
		$data['text'] = strip_tags($data['text'], '<br>');
		$data['link'] = createLink($data['original_tag']);

		if ($data['read'] == 'N' && !$newFlag || $data['read'] == 'Y' && $newFlag):
			$newFlag = $newFlag? false: true;
			if (!$firstNewFlag):
				?><div class="notif-new"></div><?
			endif;
		endif;
		$firstNewFlag = false;
	?>
		<div id="notify<?=$data['id']?>" class="notif-block" <?=($data['link'] && $data['type'] != 1? ' onclick="'.$data['link'].'"':'')?>>
			<div class="notif-avatar ml-avatar"><div class="ml-avatar-sub" id="<?=$avatarId?>" data-src="<?=$data['userAvatar']?>" style="background-size:cover;"></div></div>
			<div class="notif-cont">
				<div class="notif-title"><?=$data['userName']?></div>
				<div class="notif-text"><?=$data['text']?></div>
				<?if(isset($data['buttons'])):?>
				<div class="notif-buttons">
					<?foreach ($data['buttons'] as $button):?>
						<div data-notifyId="<?=$data['id']?>"  data-notifyValue="<?=$button['VALUE']?>" class="notif-button notif-button-<?=$button['TYPE']?>" onclick="_confirmRequest(this)"><?=$button['TITLE']?></div>
					<?endforeach;?>
				</div>
				<?endif;?>
				<div class="notif-options">
					<?=($data['link']? '<div class="notif-counter" onclick="'.$data['link'].'">'.GetMessage('NM_MORE').'</div>': '')?>
					<div class="notif-time"><?=$data['date']?></div>
				</div>
			</div>
		</div>
	<?endforeach;?>
	</div>
	<script type="text/javascript">
		BX.ImLegacy.notifyLastId = <?=$maxId?>;
		BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);
		app.checkOpenStatus({'callback' : function(data){
			if (data && data.status == 'visible')
			{
				BX.ImLegacy.notifyViewed();
			}
		}});
		app.onCustomEvent('onNotificationsLastId', BX.ImLegacy.notifyLastId);
	</script>
<?$frame->finishDynamicWithID("im_notify_legacy_".$USER->GetId(), $stub = "", $containerId = null, $useBrowserStorage = true);?>
	<script type="text/javascript">
		BX.addCustomEvent("onNotificationsOpen", function(){
			BitrixMobile.LazyLoad.showImages();
			if (BX('notif-block-wrap'))
			{
				var elements = BX.findChildren(BX('notif-block-wrap'), {className : "notif-block-unread"}, false);
				for (var i = 0; i < elements.length; i++)
					BX.removeClass(elements[i], 'notif-block-unread');
			}
		});
		newNotifyReload = null;
		BX.addCustomEvent("onPull-im", function(data) {
			if (data.command == 'confirmNotify')
			{
				var notifyId = parseInt(data.params.id);
				if (BX('notify'+notifyId))
				{
					var elements = BX.findChildren(BX('notify'+notifyId), {className : "notif-buttons"}, true);
					for (var i = 0; i < elements.length; i++)
						BX.remove(elements[i]);
				}
			}
		});

		function _confirmRequest(el)
		{
			BX.remove(el.parentNode);
			BX.ImLegacy.confirmRequest({
				notifyId: el.getAttribute('data-notifyId'),
				notifyValue: el.getAttribute('data-notifyValue')
			})
		}
	</script>
<?endif;?>
<script type="text/javascript">

	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TopBar.title.setText("<?=GetMessage("NM_TITLE")?>");
		BXMobileApp.UI.Page.TopBar.title.show();
	}
	app.pullDown({
		'enable': true,
		'pulltext': '<?=GetMessage('NM_PULLTEXT')?>',
		'downtext': '<?=GetMessage('NM_DOWNTEXT')?>',
		'loadtext': '<?=GetMessage('NM_LOADTEXT')?>',
		'callback': function(){
			app.BasicAuth({
				success: function() {
					location.reload();
				},
				failture: function() {
					app.pullDownLoadingStop();
				}
			});
		}
	});
	
	BX.addCustomEvent("onFrameDataReceived", function(data){
		BitrixMobile.LazyLoad.showImages();
	});
</script>
<?
function createLink($tag)
{
	$link = SITE_DIR.'mobile/log/?ACTION=CONVERT';
	$result = false;
	$unique = false;
	$uniqueParams = "{}";

	if (
		substr($tag, 0, 10) == 'BLOG|POST|'
		|| substr($tag, 0, 13) == 'BLOG|COMMENT|'
		|| substr($tag, 0, 18) == 'BLOG|POST_MENTION|'
		|| substr($tag, 0, 21) == 'BLOG|COMMENT_MENTION|'
		|| substr($tag, 0, 17) == 'BLOG|SHARE2USERS|'
	)
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=".$params[2];
	}
	else if (substr($tag, 0, 28) == 'RATING_MENTION|BLOG_COMMENT|')
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=BLOG_COMMENT&ENTITY_ID=".$params[2];
	}
	else if (substr($tag, 0, 10) == 'RATING|IM|')
	{
		$params = explode("|", $tag);
		$result = SITE_DIR.'mobile/im/dialog.php';
		$uniqueParams = "{dialogId: '".($params[2] == 'P'? $params[3]: 'chat'.$params[3])."'}";
		$unique = true;
	}
	else if (substr($tag, 0, 10) == 'RATING|DL|')
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=".$params[2]."&ENTITY_ID=".$params[3];
	}
	else if (substr($tag, 0, 7) == 'RATING|')
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=".$params[1]."&ENTITY_ID=".$params[2];
	}
	else if (substr($tag, 0, 15) == 'CALENDAR|INVITE')
	{
		$params = explode("|", $tag);
		if (count($params) >= 5 && $params[4] == 'cancel')
			$result = false;
		else
			$result = SITE_DIR.'mobile/calendar/view_event.php?event_id='.$params[2];
	}
	else if (
		substr($tag, 0, 13) == 'FORUM|COMMENT'
		|| substr($tag, 0, 26) == 'RATING_MENTION|FORUM_POST|'
	)
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=FORUM_POST&ENTITY_ID=".$params[2];
	}
	else if (substr($tag, 0, 7) == 'VOTING|')
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=VOTING&ENTITY_ID=".$params[1];
	}
	else if (
		substr($tag, 0, 13) == 'PHOTO|COMMENT'
		|| substr($tag, 0, 12) == 'WIKI|COMMENT'
	)
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=IBLOCK_ELEMENT&ENTITY_ID=".$params[2];
	}
	else if (
		substr($tag, 0, 34) == 'INTRANET_NEW_USER|COMMENT_MENTION|'
		|| substr($tag, 0, 27) == 'RATING_MENTION|LOG_COMMENT|'
	)
	{
		$params = explode("|", $tag);
		$result = $link."&ENTITY_TYPE_ID=LOG_COMMENT&ENTITY_ID=".$params[2];
	}

	if ($result)
	{
		if ($unique)
		{
			$result = "BXMobileApp.PageManager.loadPageUnique({'url' : '".$result."','bx24ModernStyle' : true, 'data': '".$uniqueParams."'});";
		}
		else
		{
			$result = "app.loadPageBlank({url: '".$result."', 'unique': ".($unique? 'true': 'false').", 'bx24ModernStyle': true})";
		}
	}

	return $result;
}
?>
