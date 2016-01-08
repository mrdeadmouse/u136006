;(function(window){
	if (!!window.BX.VoxImplantConfigEdit)
		return;
	var destination = function(params, type) {
		this.p = (!!params ? params : {});
		if (!!params["SELECTED"])
		{
			var res = {}, tp, j;
			for (tp in params["SELECTED"])
			{
				if (params["SELECTED"].hasOwnProperty(tp) && typeof params["SELECTED"][tp] == "object")
				{
					for (j in params["SELECTED"][tp])
					{
						if (params["SELECTED"][tp].hasOwnProperty(j))
						{
							if (tp == 'USERS')
								res['U' + params["SELECTED"][tp][j]] = 'users';
							else if (tp == 'SG')
								res['SG' + params["SELECTED"][tp][j]] = 'sonetgroups';
							else if (tp == 'DR')
								res['DR' + params["SELECTED"][tp][j]] = 'department';
						}
					}
				}
			}
			this.p["SELECTED"] = res;
		}

		this.nodes = {};
		var makeDepartmentTree = function(id, relation)
		{
			var arRelations = {}, relId, arItems, x;
			if (relation[id])
			{
				for (x in relation[id])
				{
					if (relation[id].hasOwnProperty(x))
					{
						relId = relation[id][x];
						arItems = [];
						if (relation[relId] && relation[relId].length > 0)
							arItems = makeDepartmentTree(relId, relation);
						arRelations[relId] = {
							id: relId,
							type: 'category',
							items: arItems
						};
					}
				}
			}
			return arRelations;
		},
		buildDepartmentRelation = function(department)
		{
			var relation = {}, p;
			for(var iid in department)
			{
				if (department.hasOwnProperty(iid))
				{
					p = department[iid]['parent'];
					if (!relation[p])
						relation[p] = [];
					relation[p][relation[p].length] = iid;
				}
			}
			return makeDepartmentTree('DR0', relation);
		};
		if (true || type == 'users')
		{
			this.params = {
				'name' : null,
				'searchInput' : null,
				'extranetUser' :  (this.p['EXTRANET_USER'] == "Y"),
				'bindMainPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				departmentSelectDisable : true,
				'callback' : {
					'select' : BX.delegate(this.select, this),
					'unSelect' : BX.delegate(this.unSelect, this),
					'openDialog' : BX.delegate(this.openDialog, this),
					'closeDialog' : BX.delegate(this.closeDialog, this),
					'openSearch' : BX.delegate(this.openDialog, this),
					'closeSearch' : BX.delegate(this.closeSearch, this)
				},
				items : {
					users : (!!this.p['USERS'] ? this.p['USERS'] : {}),
					groups : {},
					sonetgroups : {},
					department : (!!this.p['DEPARTMENT'] ? this.p['DEPARTMENT'] : {}),
					departmentRelation : (!!this.p['DEPARTMENT'] ? buildDepartmentRelation(this.p['DEPARTMENT']) : {}),
					contacts : {},
					companies : {},
					leads : {},
					deals : {}
				},
				itemsLast : {
					users : (!!this.p['LAST'] && !!this.p['LAST']['USERS'] ? this.p['LAST']['USERS'] : {}),
					sonetgroups : {},
					department : {},
					groups : {},
					contacts : {},
					companies : {},
					leads : {},
					deals : {},
					crm : []
				},
				itemsSelected : (!!this.p['SELECTED'] ? BX.clone(this.p['SELECTED']) : {}),
				isCrmFeed : false
			}
		}
		// TODO Other types for searching
	}, destinationInstance = null;
	destination.prototype = {
		setInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new destInput(id, node, inputName);
				this.nodes[id] = node;
				BX.defer_proxy(function(){
					this.params.name = res.id;
					this.params.searchInput = res.nodes.input;
					this.params.bindMainPopup.node = res.nodes.container;
					this.params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(this.params);
				}, this)();
			}
		},
		select : function(item, type, search, bUndeleted, id)
		{
			var type1 = type, prefix = 'S';

			if (type == 'groups')
			{
				type1 = 'all-users';
			}
			else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
			{
				type1 = 'crm';
			}

			if (type == 'sonetgroups')
			{
				prefix = 'SG';
			}
			else if (type == 'groups')
			{
				prefix = 'UA';
			}
			else if (type == 'users')
			{
				prefix = 'U';
			}
			else if (type == 'department')
			{
				prefix = 'DR';
			}
			else if (type == 'contacts')
			{
				prefix = 'CRMCONTACT';
			}
			else if (type == 'companies')
			{
				prefix = 'CRMCOMPANY';
			}
			else if (type == 'leads')
			{
				prefix = 'CRMLEAD';
			}
			else if (type == 'deals')
			{
				prefix = 'CRMDEAL';
			}

			var stl = (bUndeleted ? ' bx-destination-undelete' : '');
			stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' bx-destination-extranet' : '');

			var el = BX.create("span", {
				attrs : {
					'data-id' : item.id
				},
				props : {
					className : "bx-destination bx-destination-"+type1+stl
				},
				children: [
					BX.create("span", {
						props : {
							'className' : "bx-destination-text"
						},
						html : item.name
					})
				]
			});

			if(!bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "bx-destination-del-but"
					},
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, type, id);
							BX.PreventDefault(e)
						},
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'bx-destination-hover');
						},
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'bx-destination-hover');
						}
					}
				}));
			}
			BX.onCustomEvent(this.nodes[id], 'select', [item, el, prefix]);
		},
		unSelect : function(item, type, search, id)
		{
			BX.onCustomEvent(this.nodes[id], 'unSelect', [item]);
		},
		openDialog : function(id)
		{
			BX.onCustomEvent(this.nodes[id], 'openDialog', []);
		},
		closeDialog : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeDialog', []);
				this.disableBackspace();
			}
		},
		closeSearch : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeSearch', []);
				this.disableBackspace();
			}
		},
		disableBackspace : function()
		{
			if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

			BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
				if (event.keyCode == 8)
				{
					BX.PreventDefault(event);
					return false;
				}
				return true;
			});
			setTimeout(function(){
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
				BX.SocNetLogDestination.backspaceDisable = null;
			}, 5000);
		}
	};
	var destInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.node.appendChild(BX.create('SPAN', {
			props : { className : "bx-destination-wrap" },
			html : [
				'<span id="', this.id, '-container"></span>',
				'<span class="bx-destination-input-box" id="', this.id, '-input-box">',
					'<input type="text" value="" class="bx-destination-input" id="', this.id, '-input">',
				'</span>',
				'<a href="#" class="bx-destination-add" id="', this.id, '-add-button"></a>'
			].join('')}));
		BX.defer_proxy(this.bind, this)();
	};
	destInput.prototype = {
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			BX.bind(this.nodes.container, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function(item, el, prefix)
		{
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
						type : "hidden",
						name : (this.inputName + '[' + prefix + '][]'),
						value : item.id
					}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function(item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		openDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			BX.style(this.nodes.button, 'display', 'none');
			BX.focus(this.nodes.input);
		},
		closeDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		closeSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		searchBefore : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		search : function(event)
		{
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
				return false;

			if (event.keyCode == 13)
			{
				BX.SocNetLogDestination.selectFirstSearchItem(this.id);
				return true;
			}
			if (event.keyCode == 27)
			{
				this.nodes.input.value = '';
				BX.style(this.nodes.button, 'display', 'inline');
			}
			else
			{
				BX.SocNetLogDestination.search(this.nodes.input.value, true, this.id);
			}

			if (!BX.SocNetLogDestination.isOpenDialog() && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
			else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			if (event.keyCode == 8)
			{
				BX.SocNetLogDestination.sendEvent = true;
			}
			return true;
		}
	};

	window.BX.VoxImplantConfigEdit = {
		initDestination : function(node, inputName, params)
		{
			if (destinationInstance === null)
				destinationInstance = new destination(params);
			destinationInstance.setInput(BX(node), inputName);

		},
		loadMelody : function(curId, params)
		{
			if (typeof params !== "object")
				return;

			var inputName = params.INPUT_NAME || "";
			var defaultMelody = params.DEFAULT_MELODY || "";

			BX.bind(BX("voximplantform").elements["MELODY_LANG"], "change", function() {
				if (!(!!BX("voximplantform").elements[inputName] && !!BX("voximplantform").elements[inputName]))
					window.jwplayer(curId+"player_div").load( [ { file : defaultMelody.replace("#LANG_ID#", this.value) } ] );
			});
			BX(curId+'span').appendChild(BX('file_input_'+curId));
			BX.bind(BX(curId+'default'), "click", function() {
				window["FILE_INPUT_"+curId]._deleteFile(BX('voximplantform').elements[inputName]);
			});
			BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onSubmit', function() {
				BX(curId+'span').appendChild(
					BX.create('SPAN', {attrs: {id : curId+'waiter'}, props : {className : "webform-field-upload-list"}, html : '<i></i>'})
				);
			});
			BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onFileUploaderChange', function() {
				window["FILE_INPUT_"+curId].INPUT.disabled = false;
			});
			BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onDeleteFile', function(id) {
				BX.hide(BX(curId+'default'));
				BX(curId+'notice').innerHTML = BX.message("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP");
				window.jwplayer(curId+"player_div").load( [ { file : defaultMelody.replace("#LANG_ID#", BX("voximplantform").elements["MELODY_LANG"].value) } ] );
				window["FILE_INPUT_"+curId].INPUT.disabled = false;
			});

			BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onDone', function(files, id, err) {
				BX.remove(BX(curId+'waiter'));
				if (!!files && files.length > 0)
				{
					var n = BX(curId+'notice');
					if (err === false && !!files[0])
					{
						if (id != 'init')
						{
							n.innerHTML = BX.message('VI_CONFIG_EDIT_UPLOAD_SUCCESS');
							if (!!window["jwplayer"])
							{
								window.jwplayer(curId+"player_div").load( [ { file : files[0]["fileURL"] } ] );
							}
							BX(curId+'default').style.display = '';
						}
					}
					else if (!!files[0] && files[0]["error"])
					{
						n.innerHTML = files[0]["error"];
					}
				}
			});
		}
	};
})(window);