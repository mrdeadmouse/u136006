; /* /bitrix/js/intranet/core_planner.js?14522774573206*/
; /* /bitrix/js/intranet/outlook.js?14522774572408*/

; /* Start:"a:4:{s:4:"full";s:50:"/bitrix/js/intranet/core_planner.js?14522774573206";s:6:"source";s:35:"/bitrix/js/intranet/core_planner.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(){

if(!!window.BX.CPlanner)
	return;

var BX = window.BX,
	planner_access_point = '/bitrix/tools/intranet_planner.php';

BX.planner_query = function(url, action, data, callback, bForce)
{
	if (BX.type.isFunction(data))
	{
		callback = data;data = {};
	}

	var query_data = {
		'method': 'POST',
		'dataType': 'json',
		'url': (url||planner_access_point) + '?action=' + action + '&site_id=' + BX.message('SITE_ID') + '&sessid=' + BX.bitrix_sessid(),
		'data':  BX.ajax.prepareData(data),
		'onsuccess': function(data) {
			if(!!callback)
			{
				callback(data, action)
			}

			BX.onCustomEvent('onPlannerQueryResult', [data, action]);
		},
		'onfailure': function(type, e) {
			if (e && e.type == 'json_failure')
			{
				(new BX.PopupWindow('planner_failure_' + Math.random(), null, {
					content: BX.create('DIV', {
						style: {width: '300px'},
						html: BX.message('JS_CORE_PL_ERROR') + '<br /><br /><small>' + BX.util.strip_tags(e.data) + '</small>'
					}),
					buttons: [
						new BX.PopupWindowButton({
							text : BX.message('JS_CORE_WINDOW_CLOSE'),
							className : "popup-window-button-decline",
							events : {
								click : function() {this.popupWindow.close()}
							}
						})
					]
				})).show();
			}
		}
	};

	return BX.ajax(query_data);
};

BX.CPlanner = function(DATA)
{
	this.DATA = DATA;
	this.DIV = null;
	this.DIV_ADDITIONAL = null;
	this.WND = null;

	BX.addCustomEvent('onGlobalPlannerDataRecieved', BX.delegate(this.onPlannerBroadcastRecieved, this));
	BX.onCustomEvent('onPlannerInit', [this, this.DATA]);
};

BX.CPlanner.prototype.onPlannerBroadcastRecieved = function(DATA)
{
	this.DATA = DATA;
	BX.onCustomEvent(this, 'onPlannerDataRecieved', [this, this.DATA]);
}

BX.CPlanner.prototype.draw = function()
{
	if(!this.DIV)
	{
		this.DIV = BX.create('DIV', {props: {className: 'bx-planner-content'}});
	}

	BX.onGlobalCustomEvent('onGlobalPlannerDataRecieved', [this.DATA]);

	return this.DIV;
};

BX.CPlanner.prototype.drawAdditional = function()
{
	if(!this.DIV_ADDITIONAL)
	{
		this.DIV_ADDITIONAL = BX.create('DIV', {style: {minHeight: 0}});
	}

	return this.DIV_ADDITIONAL;
};

BX.CPlanner.prototype.addBlock = function(block, sort)
{
	if(!block||!BX.type.isElementNode(block))
	{
		return;
	}

	block.bxsort = parseInt(sort)||100;

	if(!!block.parentNode)
	{
		block.parentNode.removeChild(block);
	}

	var el = this.DIV.firstChild;
	while(el)
	{
		if(el == block)
			break;

		if(!!el.bxsort&&el.bxsort>block.bxsort)
		{
			this.DIV.insertBefore(block, el);
			break;
		}
		el = el.nextSibling;
	}

	if(!block.parentNode||!BX.type.isElementNode(block.parentNode)) // 2nd case is for IE8
	{
		this.DIV.appendChild(block);
	}
};

BX.CPlanner.prototype.addAdditional = function(block)
{
	this.drawAdditional().appendChild(block);
};

BX.CPlanner.prototype.update = function(data)
{
	if(!!data)
	{
		this.DATA = data;
		this.draw();
	}
	else
	{
		this.query('update');
	}
};

BX.CPlanner.prototype.query = function(action, data)
{
	return BX.planner_query(planner_access_point, action, data, BX.proxy(this.update, this));
};

BX.CPlanner.query = function(action, data)
{
	return BX.planner_query(planner_access_point, action, data);
};

})();

/* End */
;
; /* Start:"a:4:{s:4:"full";s:45:"/bitrix/js/intranet/outlook.js?14522774572408";s:6:"source";s:30:"/bitrix/js/intranet/outlook.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
var jsOutlookUtils = {
	encode: function(str)
	{
		var
			i, len = str.length, cur_chr, cur_chr_code,
			out = "",
			bUnicode = false,
			symb_escape = "&\\[]|";
		for (i = 0; i < len; i++)
		{
			cur_chr = str.charAt(i);
			cur_chr_code = cur_chr.charCodeAt(0);

			if (bUnicode && cur_chr_code <= 0x7F) { out += "]"; bUnicode = false; }
			if (!bUnicode && cur_chr_code > 0x7F) { out += "["; bUnicode = true; }

			if (symb_escape.indexOf(cur_chr) >= 0)
				out += "|";

			if (
				(cur_chr_code >= 0x61 && cur_chr_code <= 0x7A)
				||
				(cur_chr_code >= 0x41 && cur_chr_code <= 0x5A)
				||
				(cur_chr_code >= 0x30 && cur_chr_code <= 0x39)
			)
				out += cur_chr;
			else if (cur_chr_code <= 0x0F)
				out += "%0" + cur_chr_code.toString(16).toUpperCase();
			else if (cur_chr_code <= 0x7F)
				out += "%" + cur_chr_code.toString(16).toUpperCase();
			else if (cur_chr_code <= 0x00FF)
				out += "00" + cur_chr_code.toString(16).toUpperCase();
			else if (cur_chr_code <= 0x0FFF)
				out += "0" + cur_chr_code.toString(16).toUpperCase();
			else
				out += cur_chr_code.toString(16).toUpperCase();
		}

		if (bUnicode)
			out += "]";

		return out;
	},

	Sync: function(type, base_url, list_url, list_prefix, list_name, guid, port, alert)
	{
		var
			maxLinkLen = 500,
			maxNameLen = 20,
			host = window.location.host;

		if(!!port)
		{
			host = host.replace(/:\d+/, '') + ':' + port;
		}

		base_url = window.location.protocol + "//" + host + base_url;
		guid = guid.replace(/{/g, '%7B').replace(/}/g, '%7D').replace(/-/g, '%2D');

		var link = "stssync://sts/?ver=1.1"
			+ "&type=" + type
			+ "&cmd=add-folder"
			+ "&base-url=" + jsOutlookUtils.encode(base_url)
			+ "&list-url=" + jsOutlookUtils.encode(list_url)
			+ "&guid=" + guid;

		var names = "&site-name=" + jsOutlookUtils.encode(list_prefix) + "&list-name=" + jsOutlookUtils.encode(list_name);

		if (
			link.length + names.length > maxLinkLen
			&&
			(list_prefix.length > maxNameLen || list_name.length > maxNameLen)
		)
		{
			if (list_prefix.length > maxNameLen)
				list_prefix = list_prefix.substring(0, maxNameLen-1) + "...";
			if (list_name.length > maxNameLen)
				list_name = list_name.substring(0, maxNameLen-1) + "...";

			names = "&site-name=" + jsOutlookUtils.encode(list_prefix) + "&list-name=" + jsOutlookUtils.encode(list_name);
		}

		link += names;

		try {window.location.href = link;}
		catch (e) {}
	}
};
/* End */
;