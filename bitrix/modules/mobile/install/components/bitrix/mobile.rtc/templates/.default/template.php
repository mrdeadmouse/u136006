<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<script type="text/javascript">
	/* PULL EVENTS */
	BX.addCustomEvent("onPullExtendWatch", function (data)
	{
		BX.PULL.extendWatch(data.id);
	});

	BX.addCustomEvent("thisPageWillDie", function (data)
	{
		BX.PULL.clearWatch(data.page_id);
	});

	BX.addCustomEvent("onPullEvent", function (module_id, command, params)
	{
		BXMobileApp.onCustomEvent('onPull-'+module_id, {'command': command, 'params': params});
		BXMobileApp.onCustomEvent('onPull', {'module_id': module_id, 'command': command, 'params': params});
	});

	BX.addCustomEvent("onPullOnlineEvent", function (command, params)
	{
		BXMobileApp.onCustomEvent('onPullOnline', {'command': command, 'params': params});
	});

	BX.PULL.authTimeout = null;
	BX.addCustomEvent("onPullError", function (error)
	{
		if (error == 'AUTHORIZE_ERROR')
		{
			clearTimeout(BX.PULL.authTimeout);
			BX.PULL.authTimeout = setTimeout(function(){
				app.BasicAuth({
					success:function ()
					{
						BX.PULL.setPrivateVar('_pullTryConnect', true);
						BX.PULL.updateState('13', true);
					}
				});
			}, 500);
		}
	});

	/* WEBRTC */
	BX.addCustomEvent("onCallInvite", function (data)
	{
		if (data.userId)
			mwebrtc.callInvite(data.userId, (data.video != "NO"));
	});

	/* IM EVENTS */
	ReadyDevice(function(){
		BXIM = new BX.ImMobile({
			'mobileAction': 'INIT',
			'userId': '<?=$USER->GetId()?>'
		});
	});
</script>