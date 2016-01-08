if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

BX.VoxImplant.config = function() {};
BX.VoxImplant.config.sip = function() {};

BX.VoxImplant.config.sip.initStatus = function(regId)
{
	BX.VoxImplant.config.sip.regId = regId;
	BX.VoxImplant.config.sip.regStatus = BX('vi_sip_reg_status');
	BX.VoxImplant.config.sip.regStatusText = BX('vi_sip_reg_status_desc');
	BX.VoxImplant.config.sip.regNeedUpdate = BX('vi_sip_reg_need_update');

	BX.VoxImplant.config.sip.checkStatus();
};

BX.VoxImplant.config.sip.checkStatus = function()
{
	BX.showWait();
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.edit/ajax.php?VI_SIP_CHECK',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_SIP_CHECK': 'Y', 'REG_ID': BX.VoxImplant.config.sip.regId, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			if (data.ERROR == '')
			{
				BX.closeWait();

				if (data.REG_STATUS == 'in_progress')
				{
					setTimeout(function(){
						BX.VoxImplant.config.sip.checkStatus();
					}, 30000)
				}

				BX.VoxImplant.config.sip.regStatus.title = data.REG_LAST_UPDATED? BX.message('VI_CONFIG_SIP_LAST_UPDATED').replace('#DATE#', data.REG_LAST_UPDATED): '';
				BX.VoxImplant.config.sip.regStatus.className = 'tel-set-sip-reg-status-result tel-set-sip-reg-status-result-'+data.REG_STATUS;
				BX.VoxImplant.config.sip.regStatus.innerHTML = BX.message('VI_CONFIG_SIP_C_STATUS_'+data.REG_STATUS.toUpperCase());
				BX.VoxImplant.config.sip.regStatusText.innerHTML = BX.message('VI_CONFIG_SIP_C_STATUS_'+data.REG_STATUS.toUpperCase()+'_DESC');

				if (data.REG_STATUS == 'error')
				{
					BX.VoxImplant.config.sip.regStatus.title = data.REG_LAST_UPDATED? BX.message('VI_CONFIG_SIP_ERROR').replace('#DATE#', data.REG_LAST_UPDATED).replace('#CODE#', data.REG_CODE).replace('#MESSAGE#', data.REG_ERROR_MESSAGE): '';
					BX.VoxImplant.config.sip.regNeedUpdate.value = 'Y';
				}
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.config.sip.regStatus.title = '';
			BX.VoxImplant.config.sip.regStatus.className = 'tel-set-sip-reg-status-result tel-set-sip-reg-status-result-error';
			BX.VoxImplant.config.sip.regStatus.innerHTML = BX.message('VI_CONFIG_SIP_C_STATUS_ERROR');
			BX.VoxImplant.config.sip.regStatusText.innerHTML = BX.message('VI_CONFIG_SIP_C_STATUS_ERROR_DESC');
			BX.VoxImplant.config.sip.regNeedUpdate.value = 'Y';
		}
	});
};

