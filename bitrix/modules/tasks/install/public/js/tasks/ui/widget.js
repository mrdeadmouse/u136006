BX.namespace('BX.Tasks.ui');

//////////////////////////////
// base widget
//////////////////////////////

BX.Tasks.ui.widget = function(opts){

	BX.merge(this, {
		opts: {
			scope:						false, // it should be either native dom object, or string that represents node id
			useSpawn:					false, // if set to true, you can do .spawn() on this object

			messages:					{}, // language-dependent messages to display
			controls:					{}, // known links to controls
			bindEvents:					{}, // event pre-binding (when use this, keep in mind that the resulting instance could not be fully formed yet)

			removeTemplates:			true, // remove script nodes after search

			initializeByGlobalEvent: 	false, // if equals to a not-empty string, initialization will be performed only by event with that name, being fired on document
			globalEventScope:			'document' // initializeByGlobalEvent scope (could be 'document' or 'window')
		},
		vars: {}, // significant variables
		ctrls: {}, // links to controls
		tmpls: {}, // templates
		sys: {
			stack:				{init:[]},
			code:				'widget', // only [a-z0-9_-] allowed
			initialized:		false
		}
	});

	this.pushFuncStack('init', BX.Tasks.ui.widget);

	this.isuiWidget = true; // prevent BX.merge() from going deeper on a widget instance
}
// the following functions can be overrided with inheritance
BX.merge(BX.Tasks.ui.widget.prototype, {

	////////////////////////////
	/// about initialization

	// only basic things here
	preInit: function(){
		var ctx = this,
			so = this.opts,
			sc = this.ctrls,
			code = this.sys.code;

		sc.scope = null;

		if(!('querySelector' in document))
			throw new Error('Your browser does not support querySelector');

		if(!code.match(/^[a-zA-Z0-9-_]+$/))
			throw new Error('Only letters, digitis, "-" and "_" allowed in code');
	},

	// member of stack of initializers, must be defined even if do nothing
	init: function(){

		var ctx = this,
			sc = this.ctrls,
			so = this.opts,
			code = this.sys.code,
			k;

		if(so.scope !== false){ // some widgets may have no scope

			sc.scope = BX.type.isNotEmptyString(so.scope) ? BX(so.scope) : so.scope;
			if(!BX.type.isElementNode(sc.scope))
				throw new Error('Invalid node passed');

			if(so.useSpawn && sc.scope)
				ctx.tmpls['scope'] = sc.scope.outerHTML;

			// templates
			var templates = sc.scope.querySelectorAll('script[type="text/html"]');
			for(k = 0; k < templates.length; k++){
				var id = BX.data(templates[k], 'template-id');

				if(typeof id == 'string' && id.length > 0 && id.search('bx-tasks-ui-'+code) == 0){

					id = id.replace('bx-tasks-ui-'+code+'-', '');
					ctx.tmpls[id] = templates[k].innerHTML;

					if(this.opts.removeTemplates)
						BX.remove(templates[k]);
				}
			}
		}

		// events
		if(typeof so.bindEvents == 'object'){
			for(k in so.bindEvents){
				if(BX.type.isFunction(so.bindEvents[k]))
					this.bindEvent(k, so.bindEvents[k]);
			}
		}
		so.bindEvents = null;
	},

	remove: function(){
		// drop scope
		if(BX.type.isDomNode(this.ctrls.scope))
			this.ctrls.scope.innerHTML = '';

		// ubind custom events
		BX.unbindAll(this);

		// here should be a mechanism of "remove stack", just equal to "init stack", but works in reversed manner

		/*
		this.opts = null;
		this.vars = null;
		this.ctrls = null;
		this.tmpls = null;
		this.sys = null;
		*/

		// later unregister in global dispatcher, if ID is set
	},

	////////////////////////////
	/// about system

	getControlClassName: function(id){
		return 'bx-tasks-ui-'+this.sys.code+'-'+id;
	},

	getControl: function(id, notRequired, scope, getAll){

		var node;

		if(!BX.type.isNotEmptyString(id))
			return null;

		if(BX.type.isElementNode(this.opts.controls[id]))
			return this.opts.controls[id];

		if(!this.ctrls.scope)
			return null;

		var sScope = this.ctrls.scope;
		if(BX.type.isElementNode(scope))
			sScope = scope;

		var checkFound = function(result){
			return (!getAll && result !== null) || (getAll && result.length > 0);
		};

		try{

			// it might be in a special data attribute
			node = sScope[getAll ? 'querySelectorAll' : 'querySelector']('[data-bx-tasks-ui-id="'+this.sys.code+'-'+id+'"]');
			if(checkFound(node))
				return node;

		}catch(e){}

		try{

			// it might be control class
			node = sScope[getAll ? 'querySelectorAll' : 'querySelector']('.'+this.getControlClassName(id));
			if(checkFound(node))
				return node;

		}catch(e){}

		try{

			// it might be some other class
			node = sScope[getAll ? 'querySelectorAll' : 'querySelector']('.'+id);
			if(checkFound(node))
				return node;

		}catch(e){}

		try{

			// last chance - it might be a specified selector
			node = sScope[getAll ? 'querySelectorAll' : 'querySelector'](id);
			if(checkFound(node))
				return node;

		}catch(e){}

		if(node === null && !notRequired)
			throw new Error('Requested control node can not be found ('+id+')');

		return node;
	},

	setOption: function(name, value){
		this.opts[name] = value;
	},

	getOption: function(name){
		return this.opts[name];
	},

	getSysCode: function(){
		return this.sys.code;
	},

	////////////////////////////
	/// about templating

	getHTMLByTemplate: function(templateId, replacements){

		var html = this.tmpls[templateId];

		if(!BX.type.isNotEmptyString(html))
			return '';

		for(var k in replacements){
			if(typeof replacements[k] != 'undefined' && replacements.hasOwnProperty(k)){

				var replaceWith = '';
				if(k.toString().indexOf('=') == 0){ // leading '=' stands for an unsafe replace - no escaping
					replaceWith = replacements[k].toString();
					k = k.toString().substr(1);
				}else
					replaceWith = BX.util.htmlspecialchars(replacements[k]).toString();

				var placeHolder = '{{'+k.toString().toLowerCase()+'}}';

				if(replaceWith.search(placeHolder) >= 0) // you must be joking
					replaceWith = '';

				while(html.search(placeHolder) >= 0) // new RegExp('', 'g') on user-controlled data seems not so harmless
					html = html.replace(placeHolder, replaceWith);
			}
		}

		return html;
	},

	createNodesByTemplate: function(templateId, replacements, onlyTags){

		//var template = this.tmpls[templateId].trim(); // not working in IE8
		var template = this.tmpls[templateId];

		if(!BX.type.isNotEmptyString(template))
			return null;

		template = template.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
		var html = this.getHTMLByTemplate(templateId, replacements);

		// table makeup behaves not so well when being parsed by a browser, so a little hack is on route:

		var isTableRow = false;
		var isTableCell = false;

		if(template.search(/^<\s*(tr|th)[^<]*>/) >= 0)
			isTableRow = true;
		else if(template.search(/^<\s*td[^<]*>/) >= 0)
			isTableCell = true;

		var keeper = document.createElement('div');

		if(isTableRow || isTableCell){

			if(isTableRow){
				keeper.innerHTML = '<table><tbody>'+html+'</tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0];
			}else{
				keeper.innerHTML = '<table><tbody><tr>'+html+'</tr></tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0].childNodes[0];
			}
		}else
			keeper.innerHTML = html;

		if(onlyTags){

			var children = keeper.childNodes;
			var result = [];

			// we need only non-text nodes
			for(var k = 0; k < children.length; k++)
				if(BX.type.isElementNode(children[k]))
					result.push(children[k]);

			return result;
		}else
			return Array.prototype.slice.call(keeper.childNodes);
	},

	replaceTemplate: function(templateId, html){
		this.tmpls[templateId] = html;
	},

	////////////////////////////
	/// about inheritance

	parentConstruct: function(owner, opts){
		var c = owner.superclass;
		if(typeof c == 'object')
			c.constructor.apply(this, [opts, true]);
	},

	handleInitStack: function(nf, owner, opts){

		this.pushFuncStack('init', owner);

		if(!nf){
			BX.merge(this.opts, opts);

			BX.Tasks.ui.widget.prototype.preInit.call(this);

			var init = function(){

				if(this.sys.initialized) // already initialized once
					return;

				this.resolveFuncStack('init'); // resove init stacks

				for(var i in this.sys.stack){
					if(i != 'init')
						this.resolveFuncStack(i, true); // resolve all other stacks
				}

				this.sys.initialized = true;
				this.fireEvent('init', [this]);
			}

			if(BX.type.isString(this.opts.initializeByGlobalEvent) && this.opts.initializeByGlobalEvent.length > 0){
				var scope = this.opts.globalEventScope == 'window' ? window : document;
				BX.addCustomEvent(scope, this.opts.initializeByGlobalEvent, BX.proxy(init, this));
			}else
				init.call(this);
		}
	},

	// when you add fName to the stack, function with the corresponding name must exist, at least equal to BX.DoNothing()
	pushFuncStack: function(fName, owner){
		if(BX.type.isFunction(owner.prototype[fName])){

			if(typeof this.sys.stack[fName] == 'undefined')
				this.sys.stack[fName] = [];

			this.sys.stack[fName].push({owner: owner, f: owner.prototype[fName]});
		}
	},

	disableInFuncStack: function(fName, owner){

		var stack = this.sys.stack[fName];

		if(typeof stack == 'undefined')
			return;

		for(var k = 0; k < stack.length; k++){
			if(stack[k].owner == owner)
				stack[k].f = BX.DoNothing;
		}
	},

	resolveFuncStack: function(fName, fire){

		var stack = this.sys.stack[fName];

		if(typeof stack == 'undefined')
			return;

		for(var k = 0; k < stack.length; k++){
			stack[k].f.call(this);
		}

		if(fire)
			this.fireEvent(fName, [this], document);

		this.sys.stack[fName] = null;
	},

	////////////////////////////
	/// about events

	fireEvent: function(eventName, args, scope){
		scope = scope || this;
		args = args || [];
		BX.onCustomEvent(scope, 'bx-tasks-ui-'+this.sys.code+'-'+eventName, args);
	},

	bindEvent: function(eventName, callback){
		BX.addCustomEvent(this, 'bx-tasks-ui-'+this.sys.code+'-'+eventName, callback);
	},

	////////////////////////////
	/// about css states

	setCSSState: function(statName, scope)
	{
		this.changeCSSState(statName, scope, true);
	},

	dropCSSState: function(statName, scope)
	{
		this.changeCSSState(statName, scope, false);
	},

	changeCSSState: function(statName, scope, way)
	{
		scope = scope || this.ctrls.scope;
		if(typeof statName != 'string' || statName.length == 0)
			return;

		BX[way ? 'addClass' : 'removeClass'](scope, 'bx-tasks-ui-state-'+statName);
	},

	////////////////////////////
	/// about miscellaneous

	spawn: function(node, onSpawn){

		// if spawning was enabled, you can spawn widget on auto-duplicated scope
		// otherwise, you should prepare scope by yourself

		if(this.opts.useSpawn)
			BX.html(node, this.tmpls.scope);

		var opts = BX.clone(this.opts);
		opts.scope = node;

		if(BX.type.isFunction(onSpawn))
			onSpawn.apply(this, [opts, node]);

		return new this.constructor(opts);
	},

	getRandom: function(){
		// only letters, digits, - and _ allowed to return
		return 	'bx'+this.sys.code+
				Math.floor((Math.random() * 1000) + 1)+
				Math.floor((Math.random() * 1000) + 1);
	}

});

//////////////////////////////
// widget with networking
//////////////////////////////

BX.Tasks.ui.networkIOWidget = function(opts, nf){

	this.parentConstruct(BX.Tasks.ui.networkIOWidget, opts);

	BX.merge(this, {
		opts: { // default options
			source:						'/somewhere.php',
			pageSize:					5, // amount of variants to show
			paginatedRequest:			true // if true, parameters for server-side paginator will be sent in the request
		},
		vars: { // significant variables
			lastPage: 0,
			loader: {show: BX.DoNothing, hide: BX.DoNothing}
		},
		ctrls: { // links to controls
		},
		sys: {
			code: 'network-io-widget'
		}
	});

	this.handleInitStack(nf, BX.Tasks.ui.networkIOWidget, opts);
};
BX.extend(BX.Tasks.ui.networkIOWidget, BX.Tasks.ui.widget);

// the following functions can be overrided with inheritance
BX.merge(BX.Tasks.ui.networkIOWidget.prototype, {

	// member of stack of initializers, must be defined even if do nothing
	init: function(){
	},

	downloadBundle: function(parameters){

		var so = this.opts,
			sv = this.vars,
			sc = this.ctrls,
			ctx = this;

		sv.loader.show(parameters.options);

		BX.ajax({

			url: so.source,
			method: 'post',
			dataType: 'json',
			async: true,
			processData: true,
			emulateOnload: true,
			start: true,
			data: BX.merge(ctx.refineRequest(parameters.request, parameters.options), ctx.getNavParams(parameters.options)),
			//cache: true,
			onsuccess: function(result){

				sv.loader.hide(parameters.options);
				if(result.result){
					result.data = ctx.refineResponce(result.data, parameters.request, parameters.options);

					if(typeof result.data == 'undefined')
						result.data = [];

					if(BX.type.isFunction(parameters.callbacks.onLoad))
						parameters.callbacks.onLoad.apply(ctx, [result.data]);

				}else
					ctx.showError({errors: result.errors, type: 'server-logic', options: parameters.options});

				if(BX.type.isFunction(parameters.callbacks.onComplete))
					parameters.callbacks.onComplete.call(ctx);
			},
			onfailure: function(type, e){

				sv.loader.hide(parameters.options);

				ctx.showError({errors: [e.message], type: type, options: parameters.options, exception: e});

				if(BX.type.isFunction(parameters.callbacks.onComplete))
					parameters.callbacks.onComplete.call(ctx);

				if(BX.type.isFunction(parameters.callbacks.onError))
					parameters.callbacks.onError.apply(ctx, [type, e]);
			}

		});

	},

	getNavParams: function(options){
		return this.opts.paginatedRequest ? {
			PAGE_SIZE: this.opts.pageSize,
			PAGE: this.vars.lastPage
		} : {};
	},

	// show internal or ajax call errors, but not logic errors (like "not found or smth")
	showError: function(parameters){
		BX.debug(parameters);
	},

	// this function is called just before request send, i.e. it`s look like a 'query proxy'
	refineRequest: function(query, options){
		return query;
	},

	// responce 'proxy-back'
	refineResponce: function(responce, request, options){
		return responce;
	}

});