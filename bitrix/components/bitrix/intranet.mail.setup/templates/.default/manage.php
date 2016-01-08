<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$customDomains = array();
foreach ($arParams['SERVICES'] as $service)
{
	if (in_array($service['type'], array('domain', 'crdomain')))
		$customDomains[] = $service['server'];
}

?>

<? if (IsModuleInstalled('bitrix24') || !empty($customDomains) || in_array(LANGUAGE_ID, array('ru', 'ua'))) { ?>
<?=GetMessage('INTR_MAIL_MANAGE_HINT'); ?><br/><br/>
<? } ?>

<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-tab-title" style="width: 100%; ">
	<tr>
		<td class="bx-form-title">
			<? if (!empty($customDomains) || in_array(LANGUAGE_ID, array('ru', 'ua'))) { ?>
			<span style="float: right; font-weight: normal; ">
				<a href="?page=domain"><?=GetMessage(empty($customDomains) ? 'INTR_MAIL_MANAGE_DOMAIN_ADD' : 'INTR_MAIL_MANAGE_DOMAIN_EDIT'); ?></a>
			</span>
			<? } ?>
			<? if (empty($customDomains)) { ?>
			<?=GetMessage('INTR_MAIL_MANAGE_SEARCH_TITLE'); ?>
			<? } else { ?>
			<span style="text-transform: uppercase; "><?=join(', ', $customDomains); ?></span>
			<? } ?>
		</td>
	</tr>
</table>

<form id="search_form" name="search_form" action="<?=POST_FORM_ACTION_URI; ?>" method="GET" style="padding: 20px 15px; ">
	<input type="hidden" name="page" value="manage">
	<input type="hidden" name="act" value="search">
	<? if (!empty($arParams['SERVICES'])) { ?>
	<a class="webform-field-action-link" onclick="mb.create(); return false; "><?=GetMessage('INTR_MAIL_MANAGE_ADD_MAILBOX'); ?></a>
	<br><br>
	<? } ?>
	<span class="filter-field">
		<input
			id="mail_search_input" name="FILTER" type="text"
			value="<? if (empty($arResult['FILTER'])) { echo GetMessage('INTR_MAIL_MANAGE_SEARCH_PROMPT'); } else { echo htmlspecialcharsbx($arResult['FILTER']); } ?>"
			class="filter-textbox" style="height: 21px; width: 260px; padding: 2px 5px; <? if (empty($arResult['FILTER'])) { ?>color: #a9a9a9; <? } ?>">
		&nbsp;
		<a id="search_btn" href="#" class="webform-small-button">
			<span class="webform-small-button-left"></span>
			<span class="webform-small-button-text"><?=GetMessage('INTR_MAIL_MANAGE_SEARCH_BTN'); ?></span>
			<span class="webform-small-button-right"></span>
		</a>
		<? if (!empty($arResult['FILTER'])) { ?>
		<a id="clear_btn" href="#" class="webform-small-button">
			<span class="webform-small-button-left"></span>
			<span class="webform-small-button-text"><?=GetMessage('INTR_MAIL_MANAGE_SEARCH_CANCEL'); ?></span>
			<span class="webform-small-button-right"></span>
		</a>
		<? } ?>
	</span>

	<input type="submit" style="visibility: hidden; ">
</form>

<script type="text/javascript">

	var searchInput = BX('mail_search_input');
	BX.bind(searchInput, 'focus', function() {
		if (searchInput.value == '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_SEARCH_PROMPT')); ?>')
		{
			searchInput.style.color = '';
			searchInput.value = '';
		}
	});
	BX.bind(searchInput, 'blur', function() {
		if (searchInput.value == '')
		{
			searchInput.style.color = '#a9a9a9';
			searchInput.value = '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_SEARCH_PROMPT')); ?>';
		}
	});

	BX.bind(BX('search_btn'), 'click', function(e) {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		if (searchInput.value == '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_SEARCH_PROMPT')); ?>')
		{
			searchInput.style.color = '';
			searchInput.value = '';
			BX.adjust(searchInput, {attrs: {disabled: 'disabled'}});
		}
		BX('search_form').submit();
		return false;
	});
	BX.bind(BX('clear_btn'), 'click', function(e) {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;

		searchInput.style.color = '';
		searchInput.value = '';
		BX.adjust(searchInput, {attrs: {disabled: 'disabled'}});

		BX('search_form').submit();
		return false;
	});

</script>

<?



$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(

		'GRID_ID' => $arResult['GRID_ID'],

		'HEADERS' => array(
			array('id' => 'NAME', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_NAME'), 'sort' => 'name', 'default' => true, 'editable' => false),
			array('id' => 'EMAIL', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_EMAIL'), 'default' => true, 'editable' => false),
			array('id' => 'ADD', 'name' => '', 'default' => true, 'editable' => false),
			array('id' => 'DELETE', 'name' => '', 'default' => true, 'editable' => false),
		),

		'ROWS' => $arResult['ROWS'],

		'NAV_OBJECT' => $arResult['NAV_OBJECT'],

	)
);

?>

<script type="text/javascript">

	var domains = {};
	var services = {};
	var domainUsers = {};

	<? foreach ($arParams['SERVICES'] as $service)
	{
		if ($service['type'] == 'controller')
		{
			?>services['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject(array_values($service['domains'])); ?>;<?
			?>domainUsers['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject($service['users']); ?>;<?
		}
		if (in_array($service['type'], array('domain', 'crdomain')))
		{
			?>domains['<?=$service['id']; ?>'] = ['<?=$service['server']; ?>'];<?
			?>services['<?=$service['id']; ?>'] = ['<?=$service['server']; ?>'];<?
			?>domainUsers['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject($service['users']); ?>;<?
		}
	} ?>

	var mb = {
		'dialog': (function() {
			var dlg = new BX.CDialog({
				'content': '',
				'title': '',
				'width': 420,
				'resizable': false
			});
			dlg.PARTS.CONTENT_DATA.style.height = 'auto';
			return dlg;
		})(),
		'toggleSubform': function(el) {

			var opposite = BX.findChild(el.parentNode, {
				attr: {'name': el.getAttribute('name') == 'select_subform' ? 'create_subform' : 'select_subform'}
			});

			BX.style(BX.findChild(opposite, {attr: {'name': 'fader'}}), 'opacity', 0.5);
			BX.style(BX.findChild(el, {attr: {'name': 'fader'}}), 'opacity', 1);
			BX.findChild(el, {'tag': 'input', attr: {'name': 'create'}}, true, false).checked = true;
		},
		'toggleService': function(el, iname) {
			var sid = el.options[el.selectedIndex].getAttribute('data-sid');
			BX.findChild(el.parentNode, {attr: {'name': iname}}, true, false).value = sid;

			if (iname == 'sservice')
			{
				var domain = el.value;
				var select = BX.findChild(BX.findParent(el, {attr: {'name': 'select_subform'}}), {attr: {'name': 'suser'}}, true, false);

				while (select.options.length > 1)
					select.remove(1);

				for (var i in domainUsers[sid][domain])
				{
					var option = document.createElement('option');

					option.value = domainUsers[sid][domain][i];
					option.text  = domainUsers[sid][domain][i];

					select.add(option);
				}
			}
		},
		'getCreateForm': function(id) {

			var createForm = '<form>';
			createForm += '<?=bitrix_sessid_post(); ?>';

			if (id)
			{
				createForm += BX('user_'+id).parentNode.innerHTML;
				createForm += '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>';

				if (BX('email_'+id).innerHTML)
				{
					createForm += BX('email_'+id).parentNode.innerHTML
					createForm += '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>'
				}

				var selectDomains = [];
				var selectUsers   = [];
				for (var sid in domainUsers)
				{
					for (var domain in domainUsers[sid])
					{
						if (domainUsers[sid][domain].length > 0)
						{
							selectDomains.push([sid, domain]);
							if (selectUsers.length == 0)
							{
								for (var i in domainUsers[sid][domain])
									selectUsers.push(domainUsers[sid][domain][i]);
							}
						}
					}
				}

				if (selectDomains.length > 0)
				{
					var selectSubform = '<div name="select_subform" onmousedown="mb.toggleSubform(this); ">';
					selectSubform += '<div style="margin: 10px 0px; height: 1px; "></div>';
					selectSubform += '<label><input type="radio" name="create" value="0"> <?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_SELECT_SUBFORM')); ?></label>';

					selectSubform += '<div name="fader" style="opacity: 0.5;">';

					var selectUser = '<select name="suser" style="width: 232px; "><option></option>';
					for (var i in selectUsers)
						selectUser += '<option value="'+selectUsers[i]+'">'+selectUsers[i]+'</option>';
					selectUser += '</select>';

					var selectDomain = '<input type="hidden" name="sservice" value="'+selectDomains[0][0]+'">';
					if (selectDomains.length > 1)
					{
						selectDomain += '<select name="sdomain" style="width: 100%; " onchange="mb.toggleService(this, \'sservice\'); ">';

						for (var i in selectDomains)
							selectDomain += '<option value="'+selectDomains[i][1]+'" data-sid="'+selectDomains[i][0]+'">@'+selectDomains[i][1]+'</option>';

						selectDomain += '</select>';
					}
					else
					{
						selectDomain += '<input type="hidden" name="sdomain" value="'+selectDomains[0][1]+'">';
						selectDomain += '@'+selectDomains[0][1];
					}

					selectSubform += '<table style="width: 100%; ">'
					selectSubform += '<tr><td>'+selectUser+'</td><td style="width: 50%; ">'+selectDomain+'</td></tr>'
					selectSubform += '</table>'

					selectSubform += '</div></div>';
				}
			}

			var createSubform = '<div name="create_subform" onmousedown="mb.toggleSubform(this); ">';

			createSubform += typeof selectSubform == 'undefined'
				? '<input type="hidden" name="create" value="1">'
				: '<label><input type="radio" name="create" value="1" checked> <?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_CREATE_SUBFORM')); ?></label>';

			createSubform += '<div name="fader">';

			var selectDomains = [];
			for (var sid in services)
			{
				for (i in services[sid])
					selectDomains.push([sid, services[sid][i]]);
			}

			var selectDomain = '<input type="hidden" name="cservice" value="'+selectDomains[0][0]+'">';
			if (selectDomains.length > 1)
			{
				selectDomain += '<select name="cdomain" style="width: 100%; " onchange="mb.toggleService(this, \'cservice\'); ">';

				for (var i in selectDomains)
					selectDomain += '<option value="'+selectDomains[i][1]+'" data-sid="'+selectDomains[i][0]+'">@'+selectDomains[i][1]+'</option>';

				selectDomain += '</select>';
			}
			else
			{
				selectDomain += '<input type="hidden" name="cdomain" value="'+selectDomains[0][1]+'">';
				selectDomain += '@'+selectDomains[0][1];
			}

			createSubform += '<table style="width: 100%; ">'
			createSubform += '<tr><td><input type="text" name="cuser" style="width: 220px; "></td><td style="width: 50%; ">'+selectDomain+'</td></tr>'
			createSubform += '</table>'

			createSubform += '<table style="width: 100%; ">'
			createSubform += '<tr><td style="width: 50%; "><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_INP_PASSWORD')); ?>:</td><td><input type="password" name="password" style="width: 220px; "></td></tr>'
			createSubform += '<tr><td><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_INP_PASSWORD2')); ?>:</td><td><input type="password" name="password2" style="width: 220px; "></td></tr>'
			createSubform += '</table>'

			createSubform += '</div></div>';

			createForm += createSubform;
			if (typeof selectSubform != 'undefined')
				createForm += selectSubform;

			createForm += '</form>';

			return createForm;
		},
		'create': function(id) {

			mb.dialog.hideNotify();
			mb.dialog.ClearButtons();
			mb.dialog.SetTitle('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_CREATE_TITLE')); ?>');
			mb.dialog.SetContent(mb.getCreateForm(id));
			mb.dialog.SetButtons([
				{
					title: BX.CDialog.btnSave.title,
					id: BX.CDialog.btnSave.id,
					name: BX.CDialog.btnSave.name,
					className: BX.CDialog.btnSave.className,
					action: function () {
						var btn = this;

						mb.dialog.hideNotify();
						btn.disable();

						BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->GetPath().'/ajax.php' ; ?>?page=manage&act=create'+(id ? '&USER_ID='+id : ''),
							data: mb.dialog.GetParameters(),
							dataType: 'json',
							onsuccess: function(json)
							{
								if (json.users)
								{
									for (var sid in json.users.vacant)
									{
										if (typeof domainUsers[sid] == 'undefined')
											continue;

										for (var domain in json.users.vacant[sid])
										{
											if (typeof domainUsers[sid][domain] == 'undefined')
												continue;

											for (var i in json.users.vacant[sid][domain])
											{
												var key = BX.util.array_search(json.users.vacant[sid][domain][i], domainUsers[sid][domain]);
												if (key < 0)
													domainUsers[sid][domain].unshift(json.users.vacant[sid][domain][i]);
											}
										}
									}

									for (var sid in json.users.occupied)
									{
										if (typeof domainUsers[sid] == 'undefined')
											continue;

										for (var domain in json.users.occupied[sid])
										{
											if (typeof domainUsers[sid][domain] == 'undefined')
												continue;

											for (var i in json.users.occupied[sid][domain])
											{
												var key = BX.util.array_search(json.users.occupied[sid][domain][i], domainUsers[sid][domain]);
												if (key >= 0)
													domainUsers[sid][domain].splice(key, 1);
											}
										}
									}
								}

								if (json.result == 'error')
								{
									mb.dialog.ShowError(json.error);
								}
								else
								{
									if (id)
									{
										BX.adjust(BX('email_'+id), {html: json.email});
										BX.adjust(BX('create_'+id), {html: json.create});
										if (typeof json['delete'] != 'undefined' && json['delete'])
											BX.adjust(BX('delete_'+id), {html: json['delete']});
										else
											BX.cleanNode(BX('delete_'+id), false);
									}

									mb.dialog.Close();
								}
							},
							onfailure: function()
							{
								mb.dialog.ShowError('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_ERR_AJAX')); ?>');
							}
						});
					}
				},
				BX.CDialog.btnCancel
			]);

			mb.dialog.Show();
		},
		'changePassword': function(id) {

			var content = '<form>'
				+ '<?=bitrix_sessid_post(); ?>'
				+ BX('user_'+id).parentNode.innerHTML
				+ '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>'
				+ BX('email_'+id).parentNode.innerHTML
				+ '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>'
				+ '<table style="width: 100%; ">'
				+ '<tr><td style="width: 50%; "><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_INP_NEW_PASSWORD')); ?>:</td><td><input type="password" name="password" style="width: 220px; "></td></tr>'
				+ '<tr><td><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_INP_PASSWORD2')); ?>:</td><td><input type="password" name="password2" style="width: 220px; "></td></tr>'
				+ '</table>'
				+ '</form>';

			mb.dialog.hideNotify();
			mb.dialog.ClearButtons();
			mb.dialog.SetTitle('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_PASSWORD_TITLE')); ?>');
			mb.dialog.SetContent(content);
			mb.dialog.SetButtons([
				{
					title: BX.CDialog.btnSave.title,
					id: BX.CDialog.btnSave.id,
					name: BX.CDialog.btnSave.name,
					className: BX.CDialog.btnSave.className,
					action: function () {
						var btn = this;

						mb.dialog.hideNotify();
						btn.disable();

						BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->GetPath().'/ajax.php' ; ?>?page=manage&act=password&USER_ID='+id,
							data: mb.dialog.GetParameters(),
							dataType: 'json',
							onsuccess: function(json)
							{
								if (json.result == 'error')
									mb.dialog.ShowError(json.error);
								else
									mb.dialog.Close();
							},
							onfailure: function()
							{
								mb.dialog.ShowError('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_ERR_AJAX')); ?>');
							}
						});
					}
				},
				BX.CDialog.btnCancel
			]);

			mb.dialog.Show();
		},
		'remove': function(id) {

			var content = '<form>'
				+ '<?=bitrix_sessid_post(); ?>'
				+ BX('user_'+id).parentNode.innerHTML
				+ '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>'
				+ BX('email_'+id).parentNode.innerHTML
				+ '<div style="margin: 10px 0px; border-top: 1px solid #dce7ed; "></div>'
				+ '<div style="margin: 10px 0px; color: #c91d24; font-weight: bold; "><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_DELETE_WT')); ?></div>'
				+ '<div style="margin: 10px 0px; "><?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_DELETE_WARNING')); ?></div>'
				+ '<div style="color: #808080; ">'
				+ '<label>'
				+ '<input type="checkbox" onclick="if (this.checked) BX(\'deletebtn\').disabled = false; else BX(\'deletebtn\').disabled = true; " style="margin: 0px; vertical-align: middle; ">&nbsp;'
				+ '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_DELETE_CONFIRM')); ?>'
				+ '</label>'
				+ '</div>'
				+ '</form>';

			mb.dialog.hideNotify();
			mb.dialog.ClearButtons();
			mb.dialog.SetTitle('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_DELETE_TITLE')); ?>');
			mb.dialog.SetContent(content);
			mb.dialog.SetButtons([
				{
					title: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_DELETE')); ?>',
					id: 'deletebtn',
					name: 'deletebtn',
					action: function () {
						var btn = this;

						mb.dialog.hideNotify();
						btn.disable();

						BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->GetPath().'/ajax.php' ; ?>?page=manage&act=delete&USER_ID='+id,
							data: mb.dialog.GetParameters(),
							dataType: 'json',
							onsuccess: function(json)
							{
								if (json.result == 'error')
								{
									mb.dialog.ShowError(json.error);
								}
								else
								{
									BX.cleanNode(BX('email_'+id), false);
									BX.adjust(BX('create_'+id), {html: json.create});
									BX.cleanNode(BX('delete_'+id), false);

									mb.dialog.Close();
								}
							},
							onfailure: function()
							{
								mb.dialog.ShowError('<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MANAGE_ERR_AJAX')); ?>');
							}
						});
					}
				},
				BX.CDialog.btnCancel
			]);
			BX('deletebtn').disabled = true;

			mb.dialog.Show();
		}
	};

</script>
