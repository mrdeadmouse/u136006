<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH."/im_mobile.js").'"></script>');
?>
<div class="chat-profile-wrap">
	<div class="chat-profile-top">
		<div class="chat-profile-avatar"></div>
		<div class="chat-profile-title" id="chat-profile-title">
			<?=$arResult['CHAT'][$arResult['CHAT_ID']]['name']?>
		</div>
	</div>
	<div class="chat-profile-users-list-block">
		<div class="chat-profile-users-list-title"><?=GetMessage('USERS')?>:</div>
		<div class="chat-profile-users-list" id="chat-profile-users-list" >
			<?
			$jsIds = "";
			foreach ($arResult['USERS'] as $user):
				$avatarId = "chat-avatar-".$user['id'];
				$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
				?><a id="chat-profile-user-<?=$user['id']?>" class="chat-profile-user" href="#" onclick="app.loadPageBlank({url: '<?=SITE_DIR?>mobile/users/?user_id=<?=$user['id']?>', bx24ModernStyle: true}); return false;"><span class="ml-avatar"><span class="ml-avatar-sub" style="background-size:cover" id="<?=$avatarId?>" data-src="<?=$user['avatar']?>"></span></span><span class="chat-profile-user-name"><?=$user['name']?></span></a>
			<?endforeach;?>
			<script type="text/javascript">
				BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);
			</script>
		</div>
	</div>
</div>
<script type="text/javascript">
	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TopBar.title.setText("<?=GetMessage("CHAT_TITLE")?>");
		BXMobileApp.UI.Page.TopBar.title.show();
	}
	closeDialog = false;
	app.pullDown({
		enable: true,
		action: "RELOAD"
	});
	BX.addCustomEvent("onPull-im", function(data)
	{
		if (data.command == 'messageChat')
		{
			if (data.params.MESSAGE.recipientId == 'chat'+<?=$arResult['CHAT_ID']?>)
			{
				closeDialog = false;
			}
		}
		else if (data.command == 'chatRename')
		{
			if (<?=$arResult['CHAT_ID']?> == data.params.chatId)
				BX('chat-profile-title').innerHTML = data.params.chatTitle;
		}
		else if (data.command == 'chatUserAdd')
		{
			for (var i = 0; i < data.params.newUsers; i++)
			{
				var user = data.params.users[data.params.newUsers[i]];
				BX('chat-profile-users-list').appendChild(BX.create("a", {
					props : { className : "chat-profile-user"},
					attrs : { id: 'chat-profile-user-'+user.id, href : '#', onclick : 'app.loadPageBlank({url:\'<?=SITE_DIR?>mobile/users/?user_id='+user.id+'\', bx24ModernStyle: true}); return false;'},
					children : [
						BX.create("span", { props : { className : "avatar"}, children: [
							BX.create("span", { props : { className : "avatar_sub"}, attrs : {style : 'background:url('+user.avatar+') center no-repeat; background-size:29px'}})
						]}),
						BX.create("span", { props : { className : "chat-profile-user-name"}, html: user.name})
					]
				}));
			}
		}
		else if (data.command == 'chatUserLeave')
		{
			if (data.params.userId == BX.message('USER_ID'))
			{
				app.checkOpenStatus({
					'callback' : function(data)
					{
						if (data && data.status == 'visible')
						{
							app.closeController();
						}
						else
						{
							closeDialog = true;
						}
					}
				});
			}
			else
			{
				BX.remove(BX('chat-profile-user-'+data.params.userId));
			}
		}
	});
	BX.addCustomEvent("onOpenPageAfter", function(){
		if (closeDialog)
		{
			closeDialog = false;
			app.closeController({'drop': true});
		}
	});
</script>