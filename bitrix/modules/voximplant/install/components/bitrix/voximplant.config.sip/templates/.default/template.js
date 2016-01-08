if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

if (!BX.VoxImplant.sip)
	BX.VoxImplant.sip = function() {};

BX.VoxImplant.sip.init = function(params)
{
	BX.VoxImplant.sip.publicFolder = params.publicFolder;

	BX.VoxImplant.sip.cloudTitle = BX('vi_sip_cloud_title');
	BX.VoxImplant.sip.cloudServer = BX('vi_sip_cloud_server');
	BX.VoxImplant.sip.cloudLogin = BX('vi_sip_cloud_login');
	BX.VoxImplant.sip.cloudPassword = BX('vi_sip_cloud_password');
	BX.VoxImplant.sip.cloudButton = BX('vi_sip_cloud_add');

	BX.VoxImplant.sip.officeTitle = BX('vi_sip_office_title');
	BX.VoxImplant.sip.officeServer = BX('vi_sip_office_server');
	BX.VoxImplant.sip.officeLogin = BX('vi_sip_office_login');
	BX.VoxImplant.sip.officePassword = BX('vi_sip_office_password');
	BX.VoxImplant.sip.officeButton = BX('vi_sip_office_add');

	BX.bind(BX.VoxImplant.sip.cloudButton, 'click', BX.VoxImplant.sip.attachCloudPbx);
	BX.bind(BX.VoxImplant.sip.officeButton, 'click', BX.VoxImplant.sip.attachOfficePbx);

	BX.ready(function(){
		BX.bind(BX('vi_sip_cloud_options'), 'click', function(e)
		{
			BX.addClass(BX('vi_sip_office_options'), 'webform-button-create');
			BX.removeClass(BX('vi_sip_office_options_div'), 'tel-connect-pbx-animate');
			BX('vi_sip_office_options_div').style.display = 'none';

			if (BX('vi_sip_cloud_options_div').style.display == 'none')
			{
				BX.removeClass(BX('vi_sip_cloud_options'), 'webform-button-create');
				BX.addClass(BX('vi_sip_cloud_options_div'), 'tel-connect-pbx-animate');
				BX('vi_sip_cloud_options_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX('vi_sip_cloud_options'), 'webform-button-create');
				BX.removeClass(BX('vi_sip_cloud_options_div'), 'tel-connect-pbx-animate');
				BX('vi_sip_cloud_options_div').style.display = 'none';
			}

			BX.PreventDefault(e);
		});
		BX.bind(BX('vi_sip_office_options'), 'click', function(e){
			BX.addClass(BX('vi_sip_cloud_options'), 'webform-button-create');
			BX.removeClass(BX('vi_sip_cloud_options_div'), 'tel-connect-pbx-animate');
			BX('vi_sip_cloud_options_div').style.display = 'none';

			if (BX('vi_sip_office_options_div').style.display == 'none')
			{
				BX.removeClass(BX('vi_sip_office_options'), 'webform-button-create');
				BX.addClass(BX('vi_sip_office_options_div'), 'tel-connect-pbx-animate');
				BX('vi_sip_office_options_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX('vi_sip_office_options'), 'webform-button-create');
				BX.removeClass(BX('vi_sip_office_options_div'), 'tel-connect-pbx-animate');
				BX('vi_sip_office_options_div').style.display = 'none';
			}
			BX.PreventDefault(e);
		});
	});
};


BX.VoxImplant.sip.attachCloudPbx = function()
{
	if (BX.VoxImplant.sip.blockAjax)
		return true;
	BX.removeClass(BX.VoxImplant.sip.cloudButton, 'webform-button-create');

	BX.showWait();
	BX.VoxImplant.sip.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.sip/ajax.php?VI_SIP_CLOUD_ATTACH',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_ADD': 'Y', 'TYPE': 'cloud', 'TITLE': BX.VoxImplant.sip.cloudTitle.value, 'SERVER': BX.VoxImplant.sip.cloudServer.value, 'LOGIN': BX.VoxImplant.sip.cloudLogin.value, 'PASSWORD': BX.VoxImplant.sip.cloudPassword.value,  'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			if (data.ERROR == '')
			{
				location.href = BX.VoxImplant.sip.publicFolder+'edit.php?ID='+data.RESULT;
			}
			else
			{
				BX.closeWait();
				BX.VoxImplant.sip.blockAjax = false;
				BX.addClass(BX.VoxImplant.sip.cloudButton, 'webform-button-create');
				alert(data.ERROR.split("<br> ").join("\n"));
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.addClass(BX.VoxImplant.sip.cloudButton, 'webform-button-create');
			BX.VoxImplant.sip.blockAjax = false;
		}
	});
};

BX.VoxImplant.sip.attachOfficePbx = function()
{
	if (BX.VoxImplant.sip.blockAjax)
		return true;
	BX.removeClass(BX.VoxImplant.sip.officeButton, 'webform-button-create');

	BX.showWait();
	BX.VoxImplant.sip.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.sip/ajax.php?VI_SIP_OFFICE_ATTACH',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_ADD': 'Y', 'TYPE': 'office', 'TITLE': BX.VoxImplant.sip.officeTitle.value, 'SERVER': BX.VoxImplant.sip.officeServer.value, 'LOGIN': BX.VoxImplant.sip.officeLogin.value, 'PASSWORD': BX.VoxImplant.sip.officePassword.value,  'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			if (data.ERROR == '')
			{
				location.href = BX.VoxImplant.sip.publicFolder+'edit.php?ID='+data.RESULT;
			}
			else
			{
				BX.closeWait();
				BX.VoxImplant.sip.blockAjax = false;
				BX.addClass(BX.VoxImplant.sip.officeButton, 'webform-button-create');
				alert(data.ERROR.split("<br> ").join("\n"));
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.addClass(BX.VoxImplant.sip.officeButton, 'webform-button-create');
			BX.VoxImplant.sip.blockAjax = false;
		}
	});
};

BX.VoxImplant.sip.connectModule = function(url)
{
	if (confirm(BX.message('VI_CONFIG_SIP_CONNECT_NOTICE_2').replace('<br>', "\n")))
	{
		location.href = url;
	}
};

BX.VoxImplant.sip.unlinkPhone = function(id)
{
	if (BX.VoxImplant.sip.blockAjax)
		return true;

	if (!confirm(BX.message('VI_CONFIG_SIP_DELETE_CONFIRM_2')))
	{
		return false;
	}
	BX.showWait();

	BX.VoxImplant.sip.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.sip/ajax.php?VI_SIP_DELETE',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_DELETE': 'Y', 'CONFIG_ID': id, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait();
			BX.VoxImplant.sip.blockAjax = false;
			if (data.ERROR == '')
			{
				var elements = BX.findChildren(BX('phone-confing-sip-wrap'), {className : "tel-set-num-sip-block"}, false);
				if (elements.length == 1)
				{
					location.reload();
				}
				else
				{
					BX.remove(BX('phone-confing-'+id));
				}
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.sip.blockAjax = false;
		}
	});
};