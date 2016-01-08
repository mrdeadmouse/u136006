(function() {
var BX = window.BX;
if(BX.SocNetLogDestination)
	return;

BX.SocNetLogDestination =
{
	popupWindow: null,
	popupSearchWindow: null,
	createSocNetGroupWindow: null,
	sendEvent: true,
	extranetUser: false,

	obSearchFirstElement: null,
	obSearchCurrentElement: null,
	obSearchResult: null,
	obSearchPosition: null,

	searchTimeout: null,
	createSonetGroupTimeout: null,

	obAllowAddSocNetGroup: {},
	obNewSocNetGroupCnt: {},

	obDepartmentEnable: {},
	obSonetgroupsEnable: {},
	obLastEnable: {},

	obWindowClass: {},
	obWindowCloseIcon: {},
	obPathToAjax: {},
	obDepartmentLoad: {},
	obDepartmentSelectDisable: {},
	obUserSearchArea: {},
	obItems: {},
	obItemsLast: {},
	obItemsSelected: {},
	obCallback: {},
	obElementSearchInput: {},
	obElementBindMainPopup: {},
	obElementBindSearchPopup: {},

	obSiteDepartmentID: {},

	obCrmFeed: {},

	bFinderInited: false,
	obClientDb: null,
	obClientDbData: {},
	obClientDbDataSearchIndex: {},

	oDbUserSearchResult: {},
	oAjaxUserSearchResult: {},
	oSearchWaiterEnabled: {},
	oSearchWaiterContentHeight: 0,

	bSearchResultMoved: false,
	oXHR: null
};

BX.SocNetLogDestination.init = function(arParams)
{
	if(!arParams.name)
		arParams.name = 'lm';

	BX.SocNetLogDestination.obPathToAjax[arParams.name] = (!arParams.pathToAjax ? '/bitrix/components/bitrix/main.post.form/post.ajax.php' : arParams.pathToAjax);

	BX.SocNetLogDestination.obCallback[arParams.name] = arParams.callback;
	BX.SocNetLogDestination.obElementBindMainPopup[arParams.name] = arParams.bindMainPopup;
	BX.SocNetLogDestination.obElementBindSearchPopup[arParams.name] = arParams.bindSearchPopup;
	BX.SocNetLogDestination.obElementSearchInput[arParams.name] = arParams.searchInput;
	BX.SocNetLogDestination.obDepartmentSelectDisable[arParams.name] = (arParams.departmentSelectDisable == true ? true : false);
	BX.SocNetLogDestination.obUserSearchArea[arParams.name] = (BX.util.in_array(arParams.userSearchArea, ['I', 'E']) ? arParams.userSearchArea : false);
	BX.SocNetLogDestination.obDepartmentLoad[arParams.name] = {};
	BX.SocNetLogDestination.obWindowClass[arParams.name] = (!arParams.obWindowClass ? 'bx-lm-socnet-log-destination' : arParams.obWindowClass);
	BX.SocNetLogDestination.obWindowCloseIcon[arParams.name] = (typeof (arParams.obWindowCloseIcon) == 'undefined' ? true : arParams.obWindowCloseIcon);
	BX.SocNetLogDestination.extranetUser = arParams.extranetUser;

	BX.SocNetLogDestination.obCrmFeed[arParams.name] = arParams.isCrmFeed;
	BX.SocNetLogDestination.obAllowAddSocNetGroup[arParams.name] = (arParams.allowAddSocNetGroup === true ? true : false);
	BX.SocNetLogDestination.obSiteDepartmentID[arParams.name] = (typeof (arParams.siteDepartmentID) != 'undefined' && parseInt(arParams.siteDepartmentID) > 0 ? parseInt(arParams.siteDepartmentID) : false);

	BX.SocNetLogDestination.obNewSocNetGroupCnt[arParams.name] = 0;

	BX.SocNetLogDestination.obLastEnable[arParams.name] = (arParams.lastTabDisable == true ? false : true);
	BX.SocNetLogDestination.obDepartmentEnable[arParams.name] = false;

	BX.SocNetLogDestination.oDbUserSearchResult[arParams.name] = {};

	if (arParams.items.department)
	{
		for(var i in arParams.items.department)
		{
			BX.SocNetLogDestination.obDepartmentEnable[arParams.name] = true;
			break;
		}
	}

	BX.SocNetLogDestination.obSonetgroupsEnable[arParams.name] = false;
	if (arParams.items.sonetgroups)
	{
		for(var i in arParams.items.sonetgroups)
		{
			BX.SocNetLogDestination.obSonetgroupsEnable[arParams.name] = true;
			break;
		}
	}

	BX.SocNetLogDestination.obItems[arParams.name] = BX.clone(arParams.items);
	BX.SocNetLogDestination.obItemsLast[arParams.name] = BX.clone(arParams.itemsLast);
	BX.SocNetLogDestination.obItemsSelected[arParams.name] = BX.clone(arParams.itemsSelected);

	for (var itemId in BX.SocNetLogDestination.obItemsSelected[arParams.name])
	{
		var type = BX.SocNetLogDestination.obItemsSelected[arParams.name][itemId];
		BX.SocNetLogDestination.runSelectCallback(itemId, type, arParams.name);
	}

	if (!BX.SocNetLogDestination.bFinderInited)
	{
		BX.Finder(false, 'destination', [], {});
		BX.onCustomEvent('initFinderDb', [ BX.SocNetLogDestination ]);
		BX.SocNetLogDestination.bFinderInited = true;
	}

	if (
		typeof (arParams.LHEObjName) != 'undefined'
		&& BX('div' + arParams.LHEObjName)
	)
	{
		BX.addCustomEvent(BX('div' + arParams.LHEObjName), 'OnShowLHE', function(show) {
			if (!show)
			{
				if (BX.SocNetLogDestination.isOpenDialog())
				{
					BX.SocNetLogDestination.closeDialog();
				}
				BX.SocNetLogDestination.closeSearch();
			}
		});
	}
};

BX.SocNetLogDestination.reInit = function(name)
{
	for (var itemId in BX.SocNetLogDestination.obItemsSelected[name])
	{
		var type = BX.SocNetLogDestination.obItemsSelected[name][itemId];
		BX.SocNetLogDestination.runSelectCallback(itemId, type, name);
	}
};

BX.SocNetLogDestination.openDialog = function(name, params)
{
	if(!name)
		name = 'lm';

	if (!params)
		params = {};

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
	}

	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
		return false;
	}

	BX.SocNetLogDestination.popupWindow = new BX.PopupWindow('BXSocNetLogDestination', params.bindNode || BX.SocNetLogDestination.obElementBindMainPopup[name].node, {
		autoHide: true,
		zIndex: 100,
		offsetLeft: parseInt(BX.SocNetLogDestination.obElementBindMainPopup[name].offsetLeft),
		offsetTop: parseInt(BX.SocNetLogDestination.obElementBindMainPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		closeByEsc: true,
		closeIcon: BX.SocNetLogDestination.obWindowCloseIcon[name] ? {'top': '12px', 'right': '15px'} : false,
		lightShadow: true,
		events: {
			onPopupShow : function() {
				if (
					BX.SocNetLogDestination.sendEvent
					&& BX.SocNetLogDestination.obCallback[name]
					&& BX.SocNetLogDestination.obCallback[name].openDialog
				)
				{
					BX.SocNetLogDestination.obCallback[name].openDialog(name);
				}
			},
			onPopupClose : function() {
				this.destroy();
			},
			onPopupDestroy : BX.proxy(function() {
				BX.SocNetLogDestination.popupWindow = null;
				if (
					BX.SocNetLogDestination.sendEvent
					&& BX.SocNetLogDestination.obCallback[name]
					&& BX.SocNetLogDestination.obCallback[name].closeDialog
				)
				{
					BX.SocNetLogDestination.obCallback[name].closeDialog(name);
				}
			}, this)
		},
		content:
		'<div class="bx-finder-box bx-lm-box '+BX.SocNetLogDestination.obWindowClass[name] +'" style="min-width: 450px; padding-bottom: 8px;">'+
			(!BX.SocNetLogDestination.obLastEnable[name] && !BX.SocNetLogDestination.obSonetgroupsEnable[name] && !BX.SocNetLogDestination.obDepartmentEnable[name]? '':
			'<div class="bx-finder-box-tabs">'+
				(BX.SocNetLogDestination.obLastEnable[name] ? '<a hidefocus="true" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'last\')" class="bx-finder-box-tab bx-lm-tab-last bx-finder-box-tab-selected" href="#switchTab">'+BX.message('LM_POPUP_TAB_LAST')+'</a>':'')+
				(BX.SocNetLogDestination.obSonetgroupsEnable[name] ? '<a hidefocus="true" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'group\')" class="bx-finder-box-tab bx-lm-tab-sonetgroup" href="#switchTab">'+BX.message('LM_POPUP_TAB_SG')+'</a>':'')+
				(BX.SocNetLogDestination.obDepartmentEnable[name] ? '<a hidefocus="true" id="destDepartmentTab_'+name+'" onclick="return BX.SocNetLogDestination.SwitchTab(\''+name+'\', this, \'department\')" class="bx-finder-box-tab bx-lm-tab-department" href="#switchTab">'+(BX.SocNetLogDestination.obUserSearchArea[name] == 'E' ? BX.message('LM_POPUP_TAB_STRUCTURE_EXTRANET') : BX.message('LM_POPUP_TAB_STRUCTURE'))+'</a>':'')+
			'</div><div class="popup-window-hr popup-window-buttons-hr"><i></i></div>')+
			'<div class="bx-finder-box-tabs-content bx-finder-box-tabs-content-window">'+
				'<table class="bx-finder-box-tabs-content-table">'+
					'<tr>'+
						'<td class="bx-finder-box-tabs-content-cell">'+
							(BX.SocNetLogDestination.obLastEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-last' + (BX.SocNetLogDestination.obLastEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '">'
								+BX.SocNetLogDestination.getItemLastHtml(false, false, name)+
							'</div>' : '') +
							(BX.SocNetLogDestination.obSonetgroupsEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-sonetgroup' + (!BX.SocNetLogDestination.obLastEnable[name] && BX.SocNetLogDestination.obSonetgroupsEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '"></div>' : '') +
							(BX.SocNetLogDestination.obDepartmentEnable[name] ? '<div class="bx-finder-box-tab-content bx-lm-box-tab-content-department' + (!BX.SocNetLogDestination.obLastEnable[name] && !BX.SocNetLogDestination.obSonetgroupsEnable[name] && BX.SocNetLogDestination.obDepartmentEnable[name] ? ' bx-finder-box-tab-content-selected' : '') + '"></div>' : '') +
						'</td>'+
					'</tr>'+
				'</table>'+
			'</div>'+
		'</div>'
	});
	BX.SocNetLogDestination.popupWindow.setAngle({});
	BX.SocNetLogDestination.popupWindow.show();

	if (
		!BX.SocNetLogDestination.obLastEnable[name]
		&& !BX.SocNetLogDestination.obSonetgroupsEnable[name]
		&& BX.SocNetLogDestination.obDepartmentEnable[name]
		&& BX('destDepartmentTab_'+name)
	)
	{
		BX.SocNetLogDestination.SwitchTab(name, BX('destDepartmentTab_'+name), 'department');
	}
};

BX.SocNetLogDestination.search = function(text, sendAjax, name, nameTemplate, params)
{
	if(!name)
		name = 'lm';

	if (!params)
		params = {};

	sendAjax = sendAjax == false? false: true;

	if (BX.SocNetLogDestination.extranetUser)
	{
		sendAjax = false;
	}

	BX.SocNetLogDestination.obSearchFirstElement = null;
	BX.SocNetLogDestination.obSearchCurrentElement = null;
	BX.SocNetLogDestination.obSearchResult = [];
	BX.SocNetLogDestination.obSearchPosition = {
		group: 0,
		row: 0,
		column: 0
	};

	if (text.length <= 0)
	{
		clearTimeout(BX.SocNetLogDestination.searchTimeout);
		if(BX.SocNetLogDestination.popupSearchWindow != null)
		{
			BX.SocNetLogDestination.popupSearchWindow.close();
		}
		return false;
	}
	else
	{
		var items = {
			'groups': {}, 'users': {}, 'sonetgroups': {}, 'department': {},
			'contacts': {}, 'companies': {}, 'leads': {}, 'deals': {}
		};
		var count = 0;

		var resultGroupIndex = 0;
		var resultRowIndex = 0;
		var resultColumnIndex = 0;
		var bNewGroup = null;
		var storedItem = false;
		var bSkip = false;

		var partsItem = [];
		var bFound = false;
		var bPartFound = false;
		var partsSearchText = text.toLowerCase().split(" ");

		if (sendAjax) // before AJAX request
		{
			var obSearch = { searchString: text };
			BX.onCustomEvent('findEntityByName', [
				BX.SocNetLogDestination,
				obSearch,
				{ },
				BX.SocNetLogDestination.oDbUserSearchResult[name]
			]);
			text = obSearch.searchString;
			BX.SocNetLogDestination.bSearchResultMoved = false;
		}
		else // from AJAX results
		{
			if (
				typeof BX.SocNetLogDestination.oDbUserSearchResult[name][text] != 'undefined'
				&& BX.SocNetLogDestination.oDbUserSearchResult[name][text].length > 0
				&& BX.SocNetLogDestination.oAjaxUserSearchResult[name][text].length < 20
			)
			{
				/* sync minus */
				BX.onCustomEvent('syncClientDb', [
					BX.SocNetLogDestination,
					name,
					BX.SocNetLogDestination.oDbUserSearchResult[name][text],
					(
						typeof BX.SocNetLogDestination.oAjaxUserSearchResult[name][text] != 'undefined'
							? BX.SocNetLogDestination.oAjaxUserSearchResult[name][text]
							: {}
					)
				]);
			}
		}

		for (var group in items)
		{
			bNewGroup = true;

			if (
				BX.SocNetLogDestination.obDepartmentSelectDisable[name]
				&& group == 'department'
			)
			{
				continue;
			}

			if (
				group == 'users'
				&& sendAjax
				&& typeof BX.SocNetLogDestination.oDbUserSearchResult[name][text] != 'undefined'
				&& BX.SocNetLogDestination.oDbUserSearchResult[name][text].length > 0 // results from local DB
			)
			{
				for (var i in BX.SocNetLogDestination.oDbUserSearchResult[name][text])
				{
					BX.SocNetLogDestination.obItems[name][group][BX.SocNetLogDestination.oDbUserSearchResult[name][text][i]] = BX.SocNetLogDestination.obClientDbData.users[BX.SocNetLogDestination.oDbUserSearchResult[name][text][i]];
				}
			}

			for (var i in BX.SocNetLogDestination.obItems[name][group])
			{
				if (BX.SocNetLogDestination.obItemsSelected[name][i]) // if already in selected
				{
					continue;
				}

				if (partsSearchText.length <= 1)
				{
					if (BX.SocNetLogDestination.obItems[name][group][i].name.toLowerCase().indexOf(text.toLowerCase()) < 0)
					{
						continue;
					}
					else
					{
						bFound = true;
					}
				}
				else
				{
					partsItem = BX.SocNetLogDestination.obItems[name][group][i].name.toLowerCase().split(" ");
					bFound = true;

					for (var j in partsSearchText)
					{
						bPartFound = false;
						for (var k in partsItem)
						{
							if (partsItem[k].indexOf(partsSearchText[j]) === 0)
							{
								bPartFound = true;
								break;
							}
						}

						if (!bPartFound)
						{
							bFound = false;
							break;
						}
					}

					if (!bFound)
					{
						continue;
					}
				}

				if (bNewGroup)
				{
					if (typeof BX.SocNetLogDestination.obSearchResult[resultGroupIndex] != 'undefined')
					{
						resultGroupIndex++;
					}
					bNewGroup = false;
				}

				items[group][i] = true;

				bSkip = false;
				if (BX.SocNetLogDestination.obItems[name][group][i]['id'] == 'UA')
				{
					bSkip = true;
				}
				else
				{
					if (typeof BX.SocNetLogDestination.obSearchResult[resultGroupIndex] == 'undefined')
					{
						BX.SocNetLogDestination.obSearchResult[resultGroupIndex] = [];
						resultRowIndex = 0;
						resultColumnIndex = 0;
					}

					if (resultColumnIndex == 2)
					{
						resultRowIndex++;
						resultColumnIndex = 0;
					}

					if (typeof BX.SocNetLogDestination.obSearchResult[resultGroupIndex][resultRowIndex] == 'undefined')
					{
						BX.SocNetLogDestination.obSearchResult[resultGroupIndex][resultRowIndex] = [];
						resultColumnIndex = 0;
					}
				}

				var item = BX.clone(BX.SocNetLogDestination.obItems[name][group][i]);

				if (bSkip)
				{
					storedItem = item;
				}

				item.type = group;
				if (!bSkip)
				{
					if (storedItem)
					{
						BX.SocNetLogDestination.obSearchResult[resultGroupIndex][resultRowIndex][resultColumnIndex] = storedItem;
						storedItem = false;
						resultColumnIndex++;
					}

					BX.SocNetLogDestination.obSearchResult[resultGroupIndex][resultRowIndex][resultColumnIndex] = item;
				}

				if (count <= 0)
				{
					BX.SocNetLogDestination.obSearchFirstElement = item;
					BX.SocNetLogDestination.obSearchCurrentElement = item;
				}
				count++;

				resultColumnIndex++;
			}

		}

		if (sendAjax)
		{
			if (BX.SocNetLogDestination.popupSearchWindow != null)
			{
				BX.SocNetLogDestination.popupSearchWindowContent.innerHTML = BX.SocNetLogDestination.getItemLastHtml(items, true, name);
			}
			else
			{
				if (count > 0)
				{
					BX.SocNetLogDestination.openSearch(items, name, params);
				}
			}
		}
		else
		{
			if (count <= 0)
			{
				if (BX.SocNetLogDestination.popupSearchWindow != null)
				{
					BX.SocNetLogDestination.popupSearchWindow.destroy();
				}

				if (BX.SocNetLogDestination.obAllowAddSocNetGroup[name])
				{
					BX.SocNetLogDestination.createSonetGroupTimeout = setTimeout(function(){

						if (BX.SocNetLogDestination.createSocNetGroupWindow === null)
						{
							BX.SocNetLogDestination.createSocNetGroupWindow = new BX.PopupWindow("invite-dialog-creategroup-popup", BX.SocNetLogDestination.obElementBindSearchPopup[name].node, {
								offsetTop : 1,
								autoHide : true,
								content : BX.SocNetLogDestination.createSocNetGroupContent(text),
								zIndex : 1200,
								buttons : BX.SocNetLogDestination.createSocNetGroupButtons(text, name)
							});
						}
						else
						{
							BX.SocNetLogDestination.createSocNetGroupWindow.setContent(BX.SocNetLogDestination.createSocNetGroupContent(text));
							BX.SocNetLogDestination.createSocNetGroupWindow.setButtons(BX.SocNetLogDestination.createSocNetGroupButtons(text, name));
						}

						if (BX.SocNetLogDestination.createSocNetGroupWindow.popupContainer.style.display != "block")
						{
							BX.SocNetLogDestination.createSocNetGroupWindow.show();
						}

					}, 1000);
				}
			}
			else
			{
				if (BX.SocNetLogDestination.popupSearchWindow != null)
				{
					BX.SocNetLogDestination.popupSearchWindowContent.innerHTML = BX.SocNetLogDestination.getItemLastHtml(items, true, name);
				}
				else
				{
					BX.SocNetLogDestination.openSearch(items, name, params);
				}
			}
		}

		clearTimeout(BX.SocNetLogDestination.searchTimeout);

		if (sendAjax && text.toLowerCase() != '')
		{
			BX.SocNetLogDestination.showSearchWaiter(name);

			BX.SocNetLogDestination.searchTimeout = setTimeout(function()
			{
				BX.SocNetLogDestination.oXHR = BX.ajax({
					url: BX.SocNetLogDestination.obPathToAjax[name],
					method: 'POST',
					dataType: 'json',
					data: {
						'LD_SEARCH' : 'Y',
						'CRM_SEARCH' : BX.SocNetLogDestination.obCrmFeed[name] ? 'Y' : 'N',
						'EXTRANET_SEARCH' : BX.util.in_array(BX.SocNetLogDestination.obUserSearchArea[name], ['I', 'E']) ? BX.SocNetLogDestination.obUserSearchArea[name] : 'N',
						'SEARCH' : text.toLowerCase(),
						'SEARCH_CONVERTED' : (
							BX.message('LANGUAGE_ID') == 'ru'
							&& BX.correctText
								? BX.correctText(text.toLowerCase())
								: ''
						),
						'sessid': BX.bitrix_sessid(),
						'nt': nameTemplate,
						'DEPARTMENT_ID': (parseInt(BX.SocNetLogDestination.obSiteDepartmentID[name]) > 0 ? parseInt(BX.SocNetLogDestination.obSiteDepartmentID[name]) : 0)
					},
					onsuccess: function(data)
					{
						BX.SocNetLogDestination.hideSearchWaiter(name);

						/* sync plus */
						if (typeof data.SEARCH != 'undefined')
						{
							text = data.SEARCH;
						}

						BX.onCustomEvent('onFinderAjaxSuccess', [ data, BX.SocNetLogDestination ]);

						if (!BX.SocNetLogDestination.bSearchResultMoved)
						{
							BX.SocNetLogDestination.oAjaxUserSearchResult[name] = {};
							BX.SocNetLogDestination.oAjaxUserSearchResult[name][text.toLowerCase()] = [];

							for (var i in data.USERS)
							{
								bFound = true;
								BX.SocNetLogDestination.oAjaxUserSearchResult[name][text.toLowerCase()].push(i);

								if (!BX.SocNetLogDestination.obItems[name].users[i])
								{
									BX.SocNetLogDestination.obItems[name].users[i] = data.USERS[i];
								}
							}

							if (BX.SocNetLogDestination.obCrmFeed[name])
							{
								var types = {'contacts': 'CONTACTS', 'companies': 'COMPANIES', 'leads': 'LEADS', 'deals': 'DEALS'};
								for (type in types)
								{
									for (var i in data[types[type]])
									{
										bFound = true;
										if (!BX.SocNetLogDestination.obItems[name][type][i])
										{
											BX.SocNetLogDestination.obItems[name][type][i] = data[types[type]][i];
										}
									}
								}
							}

							BX.SocNetLogDestination.search(text, false, name, nameTemplate);
						}
					},
					onfailure: function(data)
					{
						BX.SocNetLogDestination.hideSearchWaiter(name);
					}
				});
			}, 1000);
		}
	}
};

BX.SocNetLogDestination.openSearch = function(items, name, params)
{
	if (!name)
	{
		name = 'lm';
	}

	if (!params)
	{
		params = {};
	}

	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
	}

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
		return false;
	}

	BX.SocNetLogDestination.popupSearchWindow = new BX.PopupWindow('BXSocNetLogDestinationSearch', params.bindNode || BX.SocNetLogDestination.obElementBindSearchPopup[name].node, {
		autoHide: true,
		zIndex: 100,
		offsetLeft: parseInt(BX.SocNetLogDestination.obElementBindSearchPopup[name].offsetLeft),
		offsetTop: parseInt(BX.SocNetLogDestination.obElementBindSearchPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		closeByEsc: true,
		closeIcon: BX.SocNetLogDestination.obWindowCloseIcon[name] ? {'top': '12px', 'right': '15px'} : false,
		lightShadow: true,
		events: {
			onPopupShow : function() {
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].openSearch)
					BX.SocNetLogDestination.obCallback[name].openSearch(name);
			},
			onPopupClose : function() {
				this.destroy();
				if(BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].closeSearch)
					BX.SocNetLogDestination.obCallback[name].closeSearch(name);
			},
			onPopupDestroy : BX.proxy(function() {
				BX.SocNetLogDestination.popupSearchWindow = null;
				BX.SocNetLogDestination.popupSearchWindowContent = null;
			}, this)
		},
		content: BX.create('DIV', {
			props: {
				className: 'bx-finder-box bx-lm-box ' + BX.SocNetLogDestination.obWindowClass[name]
			},
			style: {
				minWidth: '450px',
				paddingBottom: '8px'
			},
			children: [
				BX.create('DIV', {
					attrs : {
						id : 'bx-lm-box-search-tabs-content'
					},
					props: {
						className: 'bx-finder-box-tabs-content'
					},
					children: [
						BX.create('TABLE', {
							props: {
								className: 'bx-finder-box-tabs-content-table'
							},
							children: [
								BX.create('TR', {
									children: [
										BX.create('TD', {
											props: {
												className: 'bx-finder-box-tabs-content-cell'
											},
											children: [
												BX.create('DIV', {
													attrs : {
														id : 'bx-lm-box-search-content'
													},
													props: {
														className: 'bx-finder-box-tab-content bx-finder-box-tab-content-selected'
													},
													html: BX.SocNetLogDestination.getItemLastHtml(items, true, name)
												})
											]
										})
									]
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					attrs : {
						id : 'bx-lm-box-search-waiter'
					},
					props: {
						className: 'bx-finder-box-search-waiter'
					},
					style: {
						height: '0px'
					},
					children: [
						BX.create('IMG', {
							props: {
								className: 'bx-finder-box-search-waiter-background'
							},
							attrs: {
								src: '/bitrix/js/main/core/images/waiter-white.gif'
							}
						}),
						BX.create('DIV', {
							props: {
								className: 'bx-finder-box-search-waiter-text'
							},
							text: BX.message('LM_POPUP_WAITER_TEXT')
						})
					]
				})
			]
		})
	});
	BX.SocNetLogDestination.popupSearchWindow.setAngle({});
	BX.SocNetLogDestination.popupSearchWindow.show();
	BX.SocNetLogDestination.popupSearchWindowContent = BX('bx-lm-box-search-content');

	BX.SocNetLogDestination.oSearchWaiterContentHeight = BX.pos(BX('bx-lm-box-search-tabs-content')).height;
};

/* vizualize lastItems - search result */
BX.SocNetLogDestination.getItemLastHtml = function(lastItems, search, name)
{
	if(!name)
		name = 'lm';

	if (!lastItems)
	{
		lastItems = BX.SocNetLogDestination.obItemsLast[name];
	}

	var html = '';
	var count = 0;

	if (BX.SocNetLogDestination.obCrmFeed[name])
	{
		itemsHtml = '';
		for (var i in lastItems.contacts)
		{
			if (!BX.SocNetLogDestination.obItems[name].contacts[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
				name, BX.SocNetLogDestination.obItems[name].contacts[i],
				{className: 'bx-lm-element-contacts', itemType: 'contacts', 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
			);
			count++;
		}
		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_CONTACTS')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}

		itemsHtml = '';
		for (var i in lastItems.companies)
		{
			if (!BX.SocNetLogDestination.obItems[name].companies[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
				name, BX.SocNetLogDestination.obItems[name].companies[i],
				{className: 'bx-lm-element-companies', itemType: 'companies', 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
			);
			count++;
		}
		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_COMPANIES')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}

		itemsHtml = '';
		for (var i in lastItems.leads)
		{
			if (!BX.SocNetLogDestination.obItems[name].leads[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
				name, BX.SocNetLogDestination.obItems[name].leads[i],
				{className: 'bx-lm-element-leads', itemType: 'leads', avatarLessMode: true, 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
			);
			count++;
		}
		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_LEADS')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}

		itemsHtml = '';
		for (var i in lastItems.deals)
		{
			if (!BX.SocNetLogDestination.obItems[name].deals[i])
				continue;
			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
				name, BX.SocNetLogDestination.obItems[name].deals[i],
				{className: 'bx-lm-element-deals', itemType: 'deals', avatarLessMode: true, 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
			);
			count++;
		}
		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_DEALS')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}
	}

	if (
		search
		|| !BX.SocNetLogDestination.obCrmFeed[name]
	)
	{
		var itemsHtml = '';
		for (var i in lastItems.groups)
		{
			if (!BX.SocNetLogDestination.obItems[name].groups[i])
			{
				continue;
			}
			itemsHtml = BX.SocNetLogDestination.getHtmlByTemplate7(
				name, BX.SocNetLogDestination.obItems[name].groups[i],
				{className: 'bx-lm-element-groups', descLessMode : true, itemType: 'groups', 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
			);
			count++;
		}

		for (var i in lastItems.users) // users
		{
			if (!BX.SocNetLogDestination.obItems[name].users[i]) // check if in available items
			{
				continue;
			}

			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
				name,
				BX.SocNetLogDestination.obItems[name].users[i],
				{
					'className': 'bx-lm-element-user',
					'descLessMode' : true,
					'itemType': 'users',
					'search': search,
					'itemHover': (search && count <= 0)
				}
			);
			count++;
		}

		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-last">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_USERS')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}

		itemsHtml = '';
		for (var i in lastItems.sonetgroups)
		{
			if (!BX.SocNetLogDestination.obItems[name].sonetgroups[i])
			{
				continue;
			}

			itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(name, BX.SocNetLogDestination.obItems[name].sonetgroups[i],	{
				className: 'bx-lm-element-sonetgroup' + (typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(BX.SocNetLogDestination.obItems[name].sonetgroups[i].entityId, window['arExtranetGroupID']) ? ' bx-lm-element-extranet' : ''),
				descLessMode : true,
				itemType: 'sonetgroups',
				'search': search,
				'itemHover': (search && count <= 0 ? true : false)
			});
			count++;
		}
		if (itemsHtml != '')
		{
			html += '<span class="bx-finder-groupbox bx-lm-groupbox-sonetgroup">'+
				'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_SG')+':</span>'+
				'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
			'</span>';
		}

		if (BX.SocNetLogDestination.obDepartmentEnable[name])
		{
			itemsHtml = '';
			for (var i in lastItems.department)
			{
				if (!BX.SocNetLogDestination.obItems[name].department[i])
					continue;
				itemsHtml += BX.SocNetLogDestination.getHtmlByTemplate7(
					name, BX.SocNetLogDestination.obItems[name].department[i],
					{className: 'bx-lm-element-department', descLessMode : true, itemType: 'department', 'search': search, 'itemHover': (search && count <= 0 ? true : false)}
				);
				count++;
			}
			if (itemsHtml != '')
			{
				html += '<span class="bx-finder-groupbox bx-lm-groupbox-department">'+
					'<span class="bx-finder-groupbox-name">'+BX.message('LM_POPUP_TAB_LAST_STRUCTURE')+':</span>'+
					'<span class="bx-finder-groupbox-content">'+itemsHtml+'</span>'+
				'</span>';
			}
		}
	}

	if (html.length <= 0)
	{
		html = '<span class="bx-finder-groupbox bx-lm-groupbox-search">'+
			'<span class="bx-finder-groupbox-content">'+BX.message(search ? 'LM_SEARCH_PLEASE_WAIT' : 'LM_EMPTY_LIST')+'</span>'+
		'</span>';
	}

	return html;
};

BX.SocNetLogDestination.getItemGroupHtml = function(name)
{
	if(!name)
		name = 'lm';

	var html = '';
	for (var i in BX.SocNetLogDestination.obItems[name].sonetgroups)
	{
		html += BX.SocNetLogDestination.getHtmlByTemplate7(name, BX.SocNetLogDestination.obItems[name].sonetgroups[i], {
			className: "bx-lm-element-sonetgroup" + (typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(BX.SocNetLogDestination.obItems[name].sonetgroups[i].entityId, window['arExtranetGroupID']) ? ' bx-lm-element-extranet' : ''),
			descLessMode : true,
			itemType: 'sonetgroups'
		});
	}

	return html;
};

BX.SocNetLogDestination.getItemDepartmentHtml = function(name, relation, categoryId, categoryOpened)
{
	if(!name)
		name = 'lm';

	categoryId = categoryId ? categoryId: false;
	categoryOpened = categoryOpened ? true: false;

	var bFirstRelation = false;
	if (
		typeof relation == 'undefined'
		|| !relation
	)
	{
		relation = BX.SocNetLogDestination.obItems[name].departmentRelation;
		bFirstRelation = true;
	}

	var html = '';
	for (var i in relation)
	{
		if (relation[i].type == 'category')
		{
			var category = BX.SocNetLogDestination.obItems[name].department[relation[i].id];
			var activeClass = BX.SocNetLogDestination.obItemsSelected[name][relation[i].id]? 'bx-finder-company-department-check-checked': '';
			html += '<div class="bx-finder-company-department'+(bFirstRelation? ' bx-finder-company-department-opened': '')+'"><a href="#'+category.id+'" class="bx-finder-company-department-inner" onclick="return BX.SocNetLogDestination.OpenCompanyDepartment(\''+name+'\', this.parentNode, \''+category.entityId+'\')" hidefocus="true"><div class="bx-finder-company-department-arrow"></div><div class="bx-finder-company-department-text">'+category.name+'</div></a></div>';
			html += '<div class="bx-finder-company-department-children'+(bFirstRelation? ' bx-finder-company-department-children-opened': '')+'">';
			if(
				!BX.SocNetLogDestination.obDepartmentSelectDisable[name]
				&& !bFirstRelation
			)
			{
				html += '<a class="bx-finder-company-department-check '+activeClass+' bx-finder-element" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department\', \''+relation[i].id+'\', \'department\')" rel="'+relation[i].id+'" href="#'+relation[i].id+'">';
				html += '<span class="bx-finder-company-department-check-inner">\
						<div class="bx-finder-company-department-check-arrow"></div>\
						<div class="bx-finder-company-department-check-text" rel="'+category.name+': '+BX.message("LM_POPUP_CHECK_STRUCTURE")+'">'+BX.message("LM_POPUP_CHECK_STRUCTURE")+'</div>\
					</span>\
				</a>';
			}
			html += BX.SocNetLogDestination.getItemDepartmentHtml(name, relation[i].items, category.entityId, bFirstRelation);
			html += '</div>';
		}
	}

	if (categoryId)
	{
		html += '<div class="bx-finder-company-department-employees" id="bx-lm-category-relation-'+categoryId+'">';
		userCount = 0;
		for (var i in relation)
		{
			if (relation[i].type == 'user')
			{
				var user = BX.SocNetLogDestination.obItems[name].users[relation[i].id];
				if (user == null)
					continue;

				var activeClass = BX.SocNetLogDestination.obItemsSelected[name][relation[i].id]? 'bx-finder-company-department-employee-selected': '';
				html += '<a href="#'+user.id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+user.id+'" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+user.id+'\', \'users\')" hidefocus="true">\
					<div class="bx-finder-company-department-employee-info">\
						<div class="bx-finder-company-department-employee-name">'+user.name+'</div>\
						<div class="bx-finder-company-department-employee-position">'+user.desc+'</div>\
					</div>\
					<div style="'+(user.avatar? 'background:url(\''+user.avatar+'\') no-repeat center center': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
				</a>';
				userCount++;
			}
		}
		if (userCount <=0)
		{
			if (!BX.SocNetLogDestination.obDepartmentLoad[name][categoryId])
				html += '<div class="bx-finder-company-department-employees-loading">'+BX.message('LM_PLEASE_WAIT')+'</div>';

			if (categoryOpened)
				BX.SocNetLogDestination.getDepartmentRelation(name, categoryId);
		}
		html += '</div>';
	}

	return html;
};

BX.SocNetLogDestination.getDepartmentRelation = function(name, departmentId)
{
	if (BX.SocNetLogDestination.obDepartmentLoad[name][departmentId])
		return false;

	BX.ajax({
		url: BX.SocNetLogDestination.obPathToAjax[name],
		method: 'POST',
		dataType: 'json',
		data: {'LD_DEPARTMENT_RELATION' : 'Y', 'DEPARTMENT_ID' : departmentId, 'sessid': BX.bitrix_sessid()},
		onsuccess: function(data){
			BX.SocNetLogDestination.obDepartmentLoad[name][departmentId] = true;
			var departmentItem = BX.util.object_search_key((departmentId == 'EX' ? departmentId : 'DR'+departmentId), BX.SocNetLogDestination.obItems[name].departmentRelation);

			html = '';
			for(var i in data.USERS)
			{
				if (!BX.SocNetLogDestination.obItems[name].users[i])
				{
					BX.SocNetLogDestination.obItems[name].users[i]	= data.USERS[i];
				}

				if (!departmentItem.items[i])
				{
					departmentItem.items[i] = {'id': i,	'type': 'user'};
					var activeClass = BX.SocNetLogDestination.obItemsSelected[name][data.USERS[i].id]? 'bx-finder-company-department-employee-selected': '';
					html += '<a href="#'+data.USERS[i].id+'" class="bx-finder-company-department-employee '+activeClass+' bx-finder-element" rel="'+data.USERS[i].id+'" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, \'department-user\', \''+data.USERS[i].id+'\', \'users\')" hidefocus="true">\
						<div class="bx-finder-company-department-employee-info">\
							<div class="bx-finder-company-department-employee-name">'+data.USERS[i].name+'</div>\
							<div class="bx-finder-company-department-employee-position">'+data.USERS[i].desc+'</div>\
						</div>\
						<div style="'+(data.USERS[i].avatar? 'background:url(\''+data.USERS[i].avatar+'\') no-repeat center center': '')+'" class="bx-finder-company-department-employee-avatar"></div>\
					</a>';
				}
			}
			BX('bx-lm-category-relation-'+departmentId).innerHTML = html;

		},
		onfailure: function(data)	{}
	});
};

BX.SocNetLogDestination.getHtmlByTemplate1 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-hover': '';
	var html = '<a id="' + name + '_' + item.id + '" class="bx-finder-box-item '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 1, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-text">'+item.name+'</div>\
	</a>';
	return html;
};

BX.SocNetLogDestination.getHtmlByTemplate2 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t2-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t2-hover': '';
	var html = '<a id="' + name + '_' + item.id + '" class="bx-finder-box-item-t2 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 2, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" href="#'+item.id+'">\
		<div class="bx-finder-box-item-t2-text">'+item.name+'</div>\
	</a>';
	return html;
};

BX.SocNetLogDestination.getHtmlByTemplate3 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t3-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t3-hover': '';
	var html = '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 3, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t3 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t3-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t3-info">'+
			'<div class="bx-finder-box-item-t3-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t3-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
};

BX.SocNetLogDestination.getHtmlByTemplate5 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t5-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t5-hover': '';
	var html = '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 5, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t5 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t5-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t5-info">'+
			'<div class="bx-finder-box-item-t5-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t5-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
};

BX.SocNetLogDestination.getHtmlByTemplate6 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var activeClass = BX.SocNetLogDestination.obItemsSelected[name][item.id]? ' bx-finder-box-item-t6-selected': '';
	var hoverClass = params.itemHover? 'bx-finder-box-item-t6-hover': '';
	var html = '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 6, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="bx-finder-box-item-t6 '+activeClass+' '+hoverClass+' bx-finder-element'+(params.className? ' '+params.className: '')+'" href="#'+item.id+'">'+
		'<div class="bx-finder-box-item-t6-avatar" '+(item.avatar? 'style="background:url(\''+item.avatar+'\') no-repeat center center"':'')+'></div>'+
		'<div class="bx-finder-box-item-t6-info">'+
			'<div class="bx-finder-box-item-t6-name">'+item.name+'</div>'+
			(item.desc? '<div class="bx-finder-box-item-t6-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'<div class="bx-clear"></div>'+
	'</a>';
	return html;
};

BX.SocNetLogDestination.getHtmlByTemplate7 = function(name, item, params)
{
	if(!name)
		name = 'lm';
	if(!params)
		params = {};

	var showDesc = BX.type.isNotEmptyString(item.desc);
	showDesc = params.descLessMode && params.descLessMode == true ? false : showDesc;

	var itemClass = "bx-finder-box-item-t7 bx-finder-element";
	itemClass += BX.SocNetLogDestination.obItemsSelected[name][item.id] ? ' bx-finder-box-item-t7-selected': '';
	itemClass += params.itemHover ? ' bx-finder-box-item-t7-hover': '';
	itemClass += showDesc ? ' bx-finder-box-item-t7-desc-mode': '';
	itemClass += params.className ? ' ' + params.className: '';
	itemClass += params.avatarLessMode && params.avatarLessMode == true ? ' bx-finder-box-item-t7-avatarless' : '';
	itemClass += typeof (item.isExtranet != 'undefined') && item.isExtranet == 'Y' ? ' bx-lm-element-extranet' : '';

	var html = '<a id="' + name + '_' + item.id + '" hidefocus="true" onclick="return BX.SocNetLogDestination.selectItem(\''+name+'\', this, 7, \''+item.id+'\', \''+(params.itemType? params.itemType: 'item')+'\', '+(params.search? true: false)+')" rel="'+item.id+'" class="' + itemClass + '" href="#'+item.id+'">'+
		(
			item.avatar
				? '<div class="bx-finder-box-item-t7-avatar"><img bx-lm-item-id="' + item.id + '" bx-lm-item-type="' + params.itemType + '" class="bx-finder-box-item-t7-avatar-img" src="' + item.avatar + '" onerror="BX.onCustomEvent(\'removeClientDbObject\', [BX.SocNetLogDestination, this.getAttribute(\'bx-lm-item-id\'), this.getAttribute(\'bx-lm-item-type\')]); BX.cleanNode(this, true);"></div>'
				: '<div class="bx-finder-box-item-t7-avatar"></div>'
		) +
		'<div class="bx-finder-box-item-t7-space"></div>' +
		'<div class="bx-finder-box-item-t7-info">'+
		'<div class="bx-finder-box-item-t7-name">'+item.name+'</div>'+
		(showDesc? '<div class="bx-finder-box-item-t7-desc">'+item.desc+'</div>': '')+
		'</div>'+
		'</a>';
	return html;
};


BX.SocNetLogDestination.SwitchTab = function(name, currentTab, type)
{
	var tabsContent = BX.findChildren(
		BX.findChild(
			currentTab.parentNode.parentNode,
			{ tagName : "td", className : "bx-finder-box-tabs-content-cell"},
			true
		),
		{ tagName : "div" }
	);

	if (!tabsContent)
	{
		return false;
	}

	var tabIndex = 0;
	var tabs = BX.findChildren(currentTab.parentNode, { tagName : "a" });
	for (var i = 0; i < tabs.length; i++)
	{
		if (tabs[i] === currentTab)
		{
			BX.addClass(tabs[i], "bx-finder-box-tab-selected");
			tabIndex = i;
		}
		else
			BX.removeClass(tabs[i], "bx-finder-box-tab-selected");
	}

	for (i = 0; i < tabsContent.length; i++)
	{
		if (tabIndex === i)
		{
			if (type == 'last')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemLastHtml(false, false, name);
			else if (type == 'group')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemGroupHtml(name);
			else if (type == 'department')
				tabsContent[i].innerHTML = BX.SocNetLogDestination.getItemDepartmentHtml(name);
			BX.addClass(tabsContent[i], "bx-finder-box-tab-content-selected");
		}
		else
			BX.removeClass(tabsContent[i], "bx-finder-box-tab-content-selected");
	}
	BX.focus(BX.SocNetLogDestination.obElementSearchInput[name]);
	return false;
}

BX.SocNetLogDestination.OpenCompanyDepartment = function(name, department, categoryId)
{
	if(!name)
		name = 'lm';

	BX.toggleClass(department, "bx-finder-company-department-opened");

	var nextDiv = BX.findNextSibling(department, { tagName : "div"} );
	if (BX.hasClass(nextDiv, "bx-finder-company-department-children"))
		BX.toggleClass(nextDiv, "bx-finder-company-department-children-opened");

	BX.SocNetLogDestination.getDepartmentRelation(name, categoryId);

	return false;
}

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

BX.SocNetLogDestination.selectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
		name = 'lm';

	BX.focus(BX.SocNetLogDestination.obElementSearchInput[name]);

	if (BX.SocNetLogDestination.obItemsSelected[name][itemId])
		return BX.SocNetLogDestination.unSelectItem(name, element, template, itemId, type, search);

	BX.SocNetLogDestination.obItemsSelected[name][itemId] = type;
	BX.SocNetLogDestination.obItemsLast[name][type][itemId] = itemId;

	if (!(element == null || template == null))
	{
		if (template == 1)
			BX.addClass(element, 'bx-finder-box-item-selected');
		else if (template == 2)
			BX.addClass(element, 'bx-finder-box-item-t2-selected');
		else if (template == 3)
			BX.addClass(element, 'bx-finder-box-item-t3-selected');
		else if (template == 4)
			BX.addClass(element, 'bx-finder-box-item-t3-selected');
		else if (template == 5)
			BX.addClass(element, 'bx-finder-box-item-t5-selected');
		else if (template == 6)
			BX.addClass(element, 'bx-finder-box-item-t6-selected');
		else if (template == 7)
			BX.addClass(element, 'bx-finder-box-item-t7-selected');
		else if (template == 'department-user')
			BX.addClass(element, 'bx-finder-company-department-employee-selected');
		else if (template == 'department')
			BX.addClass(element, 'bx-finder-company-department-check-checked');
	}

	BX.SocNetLogDestination.runSelectCallback(itemId, type, name, search);

	if (search === true)
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.close();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.close();
	}
	else
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.adjustPosition();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition();
	}


	var objSize = Object.size(BX.SocNetLogDestination.obItemsLast[name][type]);

	if(objSize > 5)
	{
		var destLast = {};
		var ii = 0;
		var jj = objSize-5;

		for(var i in BX.SocNetLogDestination.obItemsLast[name][type])
		{
			if(ii >= jj)
				destLast[BX.SocNetLogDestination.obItemsLast[name][type][i]] = BX.SocNetLogDestination.obItemsLast[name][type][i];
			ii++;
		}
	}
	else
	{
		var destLast = BX.SocNetLogDestination.obItemsLast[name][type];
	}

 	BX.userOptions.save('socialnetwork', 'log_destination', type, JSON.stringify(destLast));

	if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']) && BX.SocNetLogDestination.obCrmFeed[name])
	{
		var lastCrmItems = [itemId];
		for (var i = 0; i < BX.SocNetLogDestination.obItemsLast[name].crm.length && lastCrmItems.length < 20; i++)
		{
			if (BX.SocNetLogDestination.obItemsLast[name].crm[i] != itemId)
				lastCrmItems.push(BX.SocNetLogDestination.obItemsLast[name].crm[i]);
		}

		BX.SocNetLogDestination.obItemsLast[name].crm = lastCrmItems;

		BX.userOptions.save('crm', 'log_destination', 'items', lastCrmItems);
	}

	return false;
};

BX.SocNetLogDestination.unSelectItem = function(name, element, template, itemId, type, search)
{
	if(!name)
		name = 'lm';

	if (!BX.SocNetLogDestination.obItemsSelected[name][itemId])
		return false;

	if (template == 1)
		BX.removeClass(element, 'bx-finder-box-item-selected');
	else if (template == 2)
		BX.removeClass(element, 'bx-finder-box-item-t2-selected');
	else if (template == 3)
		BX.removeClass(element, 'bx-finder-box-item-t3-selected');
	else if (template == 4)
		BX.removeClass(element, 'bx-finder-box-item-t3-selected');
	else if (template == 5)
		BX.removeClass(element, 'bx-finder-box-item-t5-selected');
	else if (template == 6)
		BX.removeClass(element, 'bx-finder-box-item-t6-selected');
	else if (template == 7)
		BX.removeClass(element, 'bx-finder-box-item-t7-selected');
	else if (template == 'department-user')
		BX.removeClass(element, 'bx-finder-company-department-employee-selected');
	else if (template == 'department')
		BX.removeClass(element, 'bx-finder-company-department-check-checked');

	BX.SocNetLogDestination.runUnSelectCallback(itemId, type, name, search);

	if (search === true)
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.close();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.close();
	}
	else
	{
		if (BX.SocNetLogDestination.popupWindow != null)
			BX.SocNetLogDestination.popupWindow.adjustPosition();
		if (BX.SocNetLogDestination.popupSearchWindow != null)
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition();
	}

	return false;
};

BX.SocNetLogDestination.runSelectCallback = function(itemId, type, name, search)
{
	if(!name)
	{
		name = 'lm';
	}

	if(!search)
	{
		search = false;
	}

	if(
		BX.SocNetLogDestination.obCallback[name]
		&& BX.SocNetLogDestination.obCallback[name].select
		&& BX.SocNetLogDestination.obItems[name][type]
		&& BX.SocNetLogDestination.obItems[name][type][itemId]
	)
	{
		BX.SocNetLogDestination.obCallback[name].select(BX.SocNetLogDestination.obItems[name][type][itemId], type, search, false, name);
	}
};

BX.SocNetLogDestination.runUnSelectCallback = function(itemId, type, name, search)
{
	if(!name)
		name = 'lm';

	if(!search)
		search = false;

	delete BX.SocNetLogDestination.obItemsSelected[name][itemId];
	if(BX.SocNetLogDestination.obCallback[name] && BX.SocNetLogDestination.obCallback[name].unSelect && BX.SocNetLogDestination.obItems[name][type] && BX.SocNetLogDestination.obItems[name][type][itemId])
		BX.SocNetLogDestination.obCallback[name].unSelect(BX.SocNetLogDestination.obItems[name][type][itemId], type, search, name);
};

/* public function */
BX.SocNetLogDestination.deleteItem = function(itemId, type, name)
{
	if(!name)
		name = 'lm';

	BX.SocNetLogDestination.runUnSelectCallback(itemId, type, name);
};

BX.SocNetLogDestination.deleteLastItem = function(name)
{
	if(!name)
		name = 'lm';

	var lastId = false;
	for (var itemId in BX.SocNetLogDestination.obItemsSelected[name])
		lastId = itemId;

	if (lastId)
	{
		var type = BX.SocNetLogDestination.obItemsSelected[name][lastId];
		BX.SocNetLogDestination.runUnSelectCallback(lastId, type, name);
	}
};

BX.SocNetLogDestination.selectFirstSearchItem = function(name)
{
	if(!name)
		name = 'lm';
	var item = BX.SocNetLogDestination.obSearchFirstElement;
	if (item != null)
	{
		BX.SocNetLogDestination.selectItem(name, null, null, item.id, item.type, true);
		BX.SocNetLogDestination.obSearchFirstElement = null;
	}
};

BX.SocNetLogDestination.selectCurrentSearchItem = function(name)
{
	if(!name)
	{
		name = 'lm';
	}

	var item = BX.SocNetLogDestination.obSearchCurrentElement;
	if (item != null)
	{
		BX.SocNetLogDestination.selectItem(name, null, null, item.id, item.type, true);
		BX.SocNetLogDestination.obSearchCurrentElement = null;
	}
};

BX.SocNetLogDestination.moveCurrentSearchItem = function(name, direction)
{
	BX.SocNetLogDestination.bSearchResultMoved = true;

	if (BX.SocNetLogDestination.oXHR)
	{
		BX.SocNetLogDestination.oXHR.abort();
		BX.SocNetLogDestination.hideSearchWaiter(name);
	}

	if (!BX.SocNetLogDestination.obSearchPosition)
	{
		BX.SocNetLogDestination.obSearchPosition = {
			group: 0,
			row: 0,
			column: 0
		};
	}

	var bMoved = false;
	var oldId = BX.SocNetLogDestination.obSearchCurrentElement.id;

	switch (direction)
	{
		case 'left':
			if (BX.SocNetLogDestination.obSearchPosition.column == 1)
			{
				if (typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row][BX.SocNetLogDestination.obSearchPosition.column - 1] != 'undefined')
				{
					BX.SocNetLogDestination.obSearchPosition.column--;
					bMoved = true;
				}
			}
			break;
		case 'right':
			if (BX.SocNetLogDestination.obSearchPosition.column == 0)
			{
				if (typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row][BX.SocNetLogDestination.obSearchPosition.column + 1] != 'undefined')
				{
					BX.SocNetLogDestination.obSearchPosition.column++;
					bMoved = true;
				}
			}
			break;
		case 'up':
			if (
				BX.SocNetLogDestination.obSearchPosition.row > 0
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row - 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row - 1][BX.SocNetLogDestination.obSearchPosition.column] != 'undefined'
			)
			{
				BX.SocNetLogDestination.obSearchPosition.row--;
				bMoved = true;
			}
			else if (
				BX.SocNetLogDestination.obSearchPosition.row == 0
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1][BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1].length - 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1][BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1].length - 1][0] != 'undefined'
			)
			{
				BX.SocNetLogDestination.obSearchPosition.row = BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group - 1].length - 1;
				BX.SocNetLogDestination.obSearchPosition.column = 0;
				BX.SocNetLogDestination.obSearchPosition.group--;
				bMoved = true;
			}
			break;
		case 'down':
			if (
				typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row + 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row + 1][BX.SocNetLogDestination.obSearchPosition.column] != 'undefined'
			)
			{
				BX.SocNetLogDestination.obSearchPosition.row++;
				bMoved = true;
			}
			else if (
				typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row + 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row + 1][0] != 'undefined'
			)
			{
				BX.SocNetLogDestination.obSearchPosition.column = 0;
				BX.SocNetLogDestination.obSearchPosition.row++;
				bMoved = true;
			}
			else if (
				BX.SocNetLogDestination.obSearchPosition.row == (BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group].length - 1)
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group + 1] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group + 1][0] != 'undefined'
				&& typeof BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group + 1][0][0] != 'undefined'
			)
			{
				BX.SocNetLogDestination.obSearchPosition.group++;
				BX.SocNetLogDestination.obSearchPosition.row = 0;
				BX.SocNetLogDestination.obSearchPosition.column = 0;
				bMoved = true;
			}
			break;
		default:
	}

	if (bMoved)
	{
		BX.SocNetLogDestination.obSearchCurrentElement = BX.SocNetLogDestination.obSearchResult[BX.SocNetLogDestination.obSearchPosition.group][BX.SocNetLogDestination.obSearchPosition.row][BX.SocNetLogDestination.obSearchPosition.column];

		if (BX(name + '_' + oldId))
		{
			BX.SocNetLogDestination.unhoverSearchItem(BX(name + '_' + oldId));
		}

		var hoveredNode = BX(name + '_' + BX.SocNetLogDestination.obSearchCurrentElement.id);
		var containerNode = BX('bx-lm-box-search-tabs-content');
		if (
			hoveredNode
			&& containerNode
		)
		{
			var arPosContainer = BX.pos(containerNode);
			var arPosNode = BX.pos(hoveredNode);
			if (
				arPosNode.bottom > arPosContainer.bottom
				|| arPosNode.top < arPosContainer.top
			)
			{
				containerNode.scrollTop += (
					arPosNode.bottom > arPosContainer.bottom
						? (arPosNode.bottom - arPosContainer.bottom)
						: (arPosNode.top - arPosContainer.top)
				);
			}

			BX.SocNetLogDestination.hoverSearchItem(hoveredNode);
		}
	}
};

BX.SocNetLogDestination.getSearchItemHoverClassName = function(node)
{
	if (!node)
	{
		return false;
	}

	if (node.classList.contains('bx-finder-box-item-t1'))
	{
		return 'bx-finder-box-item-t1-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t2'))
	{
		return 'bx-finder-box-item-t2-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t3'))
	{
		return 'bx-finder-box-item-t3-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t4'))
	{
		return 'bx-finder-box-item-t4-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t5'))
	{
		return 'bx-finder-box-item-t5-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t6'))
	{
		return 'bx-finder-box-item-t6-hover';
	}
	else if (node.classList.contains('bx-finder-box-item-t7'))
	{
		return 'bx-finder-box-item-t7-hover';
	}

	return  false;
}

BX.SocNetLogDestination.hoverSearchItem = function(node)
{
	var hoverClassName = BX.SocNetLogDestination.getSearchItemHoverClassName(node);

	if (hoverClassName)
	{
		BX.addClass(
			node,
			hoverClassName
		);
	}
}

BX.SocNetLogDestination.unhoverSearchItem = function(node)
{
	var hoverClassName = BX.SocNetLogDestination.getSearchItemHoverClassName(node);

	if (hoverClassName) {
		BX.removeClass(
			node,
			hoverClassName
		);
	}
}

BX.SocNetLogDestination.getSelectedCount = function(name)
{
	if(!name)
		name = 'lm';

	var count = 0;
	for (var i in BX.SocNetLogDestination.obItemsSelected[name])
		count++;

	return count;
};

BX.SocNetLogDestination.getSelected = function(name)
{
	if(!name)
		name = 'lm';
	return BX.SocNetLogDestination.obItemsSelected[name];
};

BX.SocNetLogDestination.isOpenDialog = function()
{
	return BX.SocNetLogDestination.popupWindow != null? true: false;
};

BX.SocNetLogDestination.isOpenSearch = function()
{
	return BX.SocNetLogDestination.popupSearchWindow != null? true: false;
};

BX.SocNetLogDestination.closeDialog = function(silent)
{
	silent = silent === true? true: false;
	if (BX.SocNetLogDestination.popupWindow != null)
		if (silent)
			BX.SocNetLogDestination.popupWindow.destroy();
		else
			BX.SocNetLogDestination.popupWindow.close();
	return true;
};

BX.SocNetLogDestination.closeSearch = function()
{
	if (BX.SocNetLogDestination.popupSearchWindow != null)
		BX.SocNetLogDestination.popupSearchWindow.close();
	return true;
};

BX.SocNetLogDestination.createSocNetGroupContent = function(text)
{
	return BX.create('div', {
		children: [
			BX.create('div', {
				text: BX.message('LM_CREATE_SONETGROUP_TITLE').replace("#TITLE#", text)
			})
		]
	});
};

BX.SocNetLogDestination.createSocNetGroupButtons = function(text, name)
{
	var strReturn = [
		new BX.PopupWindowButton({
			text : BX.message("LM_CREATE_SONETGROUP_BUTTON_CREATE"),
			events : {
				click : function() {
					var groupCode = 'SGN'+ BX.SocNetLogDestination.obNewSocNetGroupCnt[name] + '';
					BX.SocNetLogDestination.obItems[name]['sonetgroups'][groupCode] = {
						id: groupCode,
						entityId: BX.SocNetLogDestination.obNewSocNetGroupCnt[name],
						name: text,
						desc: ''
					};

					var itemsNew = {
						'sonetgroups': {
						}
					};
					itemsNew['sonetgroups'][groupCode] = true;

					if (BX.SocNetLogDestination.popupSearchWindow != null)
					{
						BX.SocNetLogDestination.popupSearchWindowContent.innerHTML = BX.SocNetLogDestination.getItemLastHtml(itemsNew, true, name);
					}
					else
					{
						BX.SocNetLogDestination.openSearch(itemsNew, name);
					}

					BX.SocNetLogDestination.obNewSocNetGroupCnt[name]++;
					BX.SocNetLogDestination.createSocNetGroupWindow.close();
				}
			}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message("LM_CREATE_SONETGROUP_BUTTON_CANCEL"),
			className : "popup-window-button-link-cancel",
			events : {
				click : function() {
					BX.SocNetLogDestination.createSocNetGroupWindow.close();
				}
			}
		})
	];

	return strReturn;
};

BX.SocNetLogDestination.showSearchWaiter = function(name)
{
	if (
		typeof BX.SocNetLogDestination.oSearchWaiterEnabled[name] == 'undefined'
		|| !BX.SocNetLogDestination.oSearchWaiterEnabled[name]
	)
	{
		if (BX.SocNetLogDestination.oSearchWaiterContentHeight > 0)
		{
			BX.SocNetLogDestination.oSearchWaiterEnabled[name] = true;
			var startHeight = 0;
			var finishHeight = 40;

			BX.SocNetLogDestination.animateSearchWaiter(startHeight, finishHeight);
		}
	}
}

BX.SocNetLogDestination.hideSearchWaiter = function(name)
{
	if (
		typeof BX.SocNetLogDestination.oSearchWaiterEnabled[name] != 'undefined'
		&& BX.SocNetLogDestination.oSearchWaiterEnabled[name]
	)
	{
		BX.SocNetLogDestination.oSearchWaiterEnabled[name] = false;

		var startHeight = 40;
		var finishHeight = 0;
		BX.SocNetLogDestination.animateSearchWaiter(startHeight, finishHeight);
	}
}

BX.SocNetLogDestination.animateSearchWaiter = function(startHeight, finishHeight)
{
	if (
		BX('bx-lm-box-search-waiter')
		&& BX('bx-lm-box-search-tabs-content')
	)
	{
		(new BX.fx({
			time: 0.5,
			step: 0.05,
			type: 'linear',
			start: startHeight,
			finish: finishHeight,
			callback: BX.delegate(function(height)
			{
				if (this)
				{
					this.waiterBlock.style.height = height + 'px';
					this.contentBlock.style.height = (BX.SocNetLogDestination.oSearchWaiterContentHeight) - height + 'px';
				}
			},
			{
				waiterBlock: BX('bx-lm-box-search-waiter'),
				contentBlock: BX('bx-lm-box-search-tabs-content')
			}),
			callback_complete: function()
			{
			}
		})).start();
	}
}

BX.SocNetLogDestination.BXfpSetLinkName = function(ob)
{
	BX(ob.tagInputName).innerHTML = (
		BX.SocNetLogDestination.getSelectedCount(ob.formName) <= 0
			? ob.tagLink1
			: ob.tagLink2
	);
};

BX.SocNetLogDestination.BXfpUnSelectCallback = function(item)
{
	var elements = BX.findChildren(BX(this.inputContainerName), {attribute: {'data-id': '' + item.id + ''}}, true);
	if (elements !== null)
	{
		for (var j = 0; j < elements.length; j++)
		{
			if (
				typeof (this.undeleteClassName) == 'undefined'
				|| !BX.hasClass(elements[j], this.undeleteClassName)
			)
			{
				BX.remove(elements[j]);
			}
		}
	}
	BX(this.inputName).value = '';
	BX.SocNetLogDestination.BXfpSetLinkName(this);
};

BX.SocNetLogDestination.BXfpSearch = function(event)
{
	if (
		event.keyCode == 16
		|| event.keyCode == 17
		|| event.keyCode == 18
		|| event.keyCode == 20
		|| event.keyCode == 244
		|| event.keyCode == 224
		|| event.keyCode == 91
	)
	{
		return false;
	}

	if (event.keyCode == 37)
	{
		BX.SocNetLogDestination.moveCurrentSearchItem(this.formName, 'left');
		BX.PreventDefault(event);
		return false;
	}
	else if (event.keyCode == 38)
	{
		BX.SocNetLogDestination.moveCurrentSearchItem(this.formName, 'up');
		BX.PreventDefault(event);
		return false;
	}
	else if (event.keyCode == 39)
	{
		BX.SocNetLogDestination.moveCurrentSearchItem(this.formName, 'right');
		BX.PreventDefault(event);
		return false;
	}
	else if (event.keyCode == 40)
	{
		BX.SocNetLogDestination.moveCurrentSearchItem(this.formName, 'down');
		BX.PreventDefault(event);
		return false;
	}

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectCurrentSearchItem(this.formName);
		return true;
	}
	if (event.keyCode == 27)
	{
		BX(this.inputName).value = '';
		BX.style(BX(this.tagInputName), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(
			BX(this.inputName).value,
			true,
			this.formName
		);
	}

	if (
		!BX.SocNetLogDestination.isOpenDialog()
		&& BX(this.inputName).value.length <= 0
	)
	{
		BX.SocNetLogDestination.openDialog(this.formName);
	}
	else if (
		BX.SocNetLogDestination.sendEvent
		&& BX.SocNetLogDestination.isOpenDialog()
	)
	{
		BX.SocNetLogDestination.closeDialog();
	}

	if (event.keyCode == 8)
	{
		BX.SocNetLogDestination.sendEvent = true;
	}
	return true;
}

BX.SocNetLogDestination.BXfpSearchBefore = function(event)
{
	if (
		event.keyCode == 8
		&& BX(this.inputName).value.length <= 0
	)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(this.formName);
	}

	return true;
}

BX.SocNetLogDestination.BXfpOpenDialogCallback = function()
{
	BX.style(BX(this.inputBoxName), 'display', 'inline-block');
	BX.style(BX(this.tagInputName), 'display', 'none');
	BX.focus(BX(this.inputName));
};

BX.SocNetLogDestination.BXfpCloseDialogCallback = function()
{
	if (
		!BX.SocNetLogDestination.isOpenSearch()
		&& BX(this.inputName).value.length <= 0
	)
	{
		BX.style(BX(this.inputBoxName), 'display', 'none');
		BX.style(BX(this.tagInputName), 'display', 'inline-block');
		BX.SocNetLogDestination.BXfpDisableBackspace();
	}
};

BX.SocNetLogDestination.BXfpCloseSearchCallback = function()
{
	if (
		!BX.SocNetLogDestination.isOpenSearch()
		&& BX(this.inputName).value.length > 0
	)
	{
		BX.style(BX(this.inputBoxName), 'display', 'none');
		BX.style(BX(this.tagInputName), 'display', 'inline-block');
		BX(this.inputName).value = '';
		BX.SocNetLogDestination.BXfpDisableBackspace();
	}
}

BX.SocNetLogDestination.BXfpDisableBackspace = function(event)
{
	if (
		BX.SocNetLogDestination.backspaceDisable
		|| BX.SocNetLogDestination.backspaceDisable !== null
	)
	{
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
	}

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event)
	{
		if (event.keyCode == 8)
		{
			BX.PreventDefault(event);
			return false;
		}
	});

	setTimeout(function()
	{
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
		BX.SocNetLogDestination.backspaceDisable = null;
	}, 5000);
}

})();