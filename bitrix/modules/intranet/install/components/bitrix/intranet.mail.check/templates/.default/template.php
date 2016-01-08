<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<? $frame = $this->createFrame()->begin(''); ?>
<? if ($arParams['SETTED_UP'] !== false) { ?>
<script type="text/javascript">

	var checkExternalMail = function()
	{
		BX.ajax({
			url: "/bitrix/tools/check_mail.php?SITE_ID=<?=SITE_ID; ?>",
			dataType: "json",
			onsuccess: function(json)
			{
				if (typeof json != "object")
					return;

				if (typeof BXIM == "object" && typeof BXIM.notify == "object" && typeof BXIM.notify.counters == "object")
					BXIM.notify.counters.mail_unseen = json.unseen;

				if (BX("menu_external_mail"))
				{
					var link = BX.findChild(BX("menu_external_mail"), {"class": "menu-item-link"}, true);
					if (typeof link == "object")
						BX.adjust(link, {props: {target: json.last_check >= 0 ? "_blank" : "_self"}});

					if (typeof B24 == "object" && typeof B24.updateCounters == "function")
						B24.updateCounters({mail_unseen: json.unseen});
				}
				else if (BX("menu_extmail_counter"))
				{
					var link    = BX("menu_extmail_counter");
					var counter = BX.findChild(link, {"class": "user-indicator-text"}, true);
					var warning = BX("menu_extmail_warning");

					if (typeof counter == "object")
						BX.adjust(counter, {text: json.unseen});

					if (typeof link == "object")
						BX.adjust(link, {style: {display: json.result == "ok" ? "inline-block" : "none"}});
					if (typeof warning == "object")
						BX.adjust(warning, {style: {display: json.result == "ok" ? "none" : "inline-block"}});
				}

				if (typeof BXIM == "object" && typeof BXIM.notify == "object" && typeof BXIM.notify.updateNotifyMailCount == "function")
					BXIM.notify.updateNotifyMailCount(json.unseen);
			}
		});
	};

</script>
<? if (isset($arParams['LAST_MAIL_CHECK']) && intval($arParams['LAST_MAIL_CHECK']) >= 0) { ?>
<script type="text/javascript">

	BX.ready(function()
	{
		if (BX("menu_external_mail"))
			var link = BX.findChild(BX("menu_external_mail"), {"class": "menu-item-link"}, true);
		else if (BX("menu_extmail_counter"))
			var link = BX("menu_extmail_counter");

		if (typeof link == "object")
			BX.adjust(link, {props: {target: "_blank"}});

		BX.bind(link, "click", function()
		{
			window.onfocus = function()
			{
				window.onfocus = null;
				checkExternalMail();
			};
			return true;
		});
	});

</script>
<? } ?>
<? if ($arParams['IS_TIME_TO_MAIL_CHECK']) { ?>
<script type="text/javascript">

	BX.ready(function() {
		checkExternalMail();
	});

</script>
<? } ?>
<? } ?>
<? $frame->end(); ?>