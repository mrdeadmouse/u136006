BX.namespace("BX.Bitrix24.Helper");

BX.Bitrix24.Helper =
{
	frameOpenUrl : '',
	frameCloseUrl : '',
	isOpen : false,
	frameNode : null,
	popupNodeWrap : null,
	curtainNode : null,
	popupNode : null,
	closeBtn : null,
	openBtn : null,
	popupLoader : null,
	topBar : null,
	topBarHtml : null,
	header : null,
	langId: null,
	reloadPath: null,

	init : function (params)
	{
		this.frameOpenUrl = params.frameOpenUrl || '';
		this.frameCloseUrl = params.frameCloseUrl || '';
		this.helpUpBtnText = params.helpUpBtnText || '';
		this.langId = params.langId || '';
		this.openBtn = params.helpBtn;
		this.reloadPath = params.reloadPath || '';

		this.popupLoader = BX.create('div',{
			attrs:{className:'bx-help-popup-loader'},
			children : [BX.create('div', {
				attrs:{className:'bx-help-popup-loader-text'},
				text : BX.message("B24_HELP_LOADER")
			})]
		});

		this.topBarHtml = '<div class="bx-help-menu-title" onclick="BX.Bitrix24.Helper.reloadFrame(\'' + this.reloadPath + '\')">'+BX.message("B24_HELP_TITLE")+'<span class="bx-help-blue">24</span></div>';

		this.topBar = BX.create('div',{
			attrs:{className:'bx-help-nav-wrap'},
			html : this.topBarHtml
		})

		this.header = document.body.querySelector('.bx-layout-header');

		this.createFrame();
		this.createCloseBtn();
		this.createPopup();

		BX.bind(this.openBtn, 'click', BX.proxy(this.show, this));

		BX.bind(window, 'message', BX.proxy(function(event)
		{
			event = event || window.event;

			if(event.data.height && this.isOpen)
				this.frameNode.style.height = event.data.height + 'px';
			this.insertTopBar(typeof(event.data) == 'object' ? event.data.title : event.data);
			this._showContent();

		}, this));
	},

	createFrame : function ()
	{
		this.frameNode = BX.create('iframe', {
			attrs: {
				className: 'bx-help-frame',
				frameborder: 0,
				name: 'help',
				id: 'help-frame'
			}
		});

		BX.bind(this.frameNode, 'load',BX.proxy(function(){
			this.popupNode.scrollTop = 0;
		}, this))
	},

	_showContent : function()
	{
		this.frameNode.style.opacity = 1;

		if(this.topBar.classList)
		{
			this.topBar.classList.add('bx-help-nav-fixed');
			this.topBar.classList.add('bx-help-nav-show');
		}
		else {
			BX.addClass(this.topBar,'bx-help-nav-fixed');
			BX.addClass(this.topBar, 'bx-help-nav-show');
		}

		this.popupLoader.classList.remove('bx-help-popup-loader-show');
	},

	_setPosFixed : function ()
	{
		document.body.style.width = document.body.offsetWidth + 'px';
		document.body.style.overflow = 'hidden';
	},

	_clearPosFixed : function()
	{
		document.body.style.width = 'auto';
		document.body.style.overflow = '';
	},

	createCloseBtn : function()
	{
		this.closeBtn = BX.create('div', {
			attrs: {
				className: 'bx-help-close'
			},
			children : [BX.create('div', {attrs: {className: 'bx-help-close-inner'}})]
		});

		BX.bind(this.closeBtn, 'click', BX.proxy(this.closePopup, this))
	},

	insertTopBar : function(node)
	{
		this.topBar.innerHTML= this.topBarHtml + node;
	},

	createPopup : function()
	{
		this.curtainNode = BX.create('div', {
			attrs: {
				"className": 'bx-help-curtain'
			}
		});

		this.popupNode = BX.create('div', {
			children: [
				this.frameNode,
				this.topBar,
				this.popupLoader
			],
			attrs: {
				className: 'bx-help-main'
			}
		});

		document.body.appendChild(this.curtainNode);
		document.body.appendChild(this.popupNode);
		document.body.appendChild(this.closeBtn);
	},

	closePopup : function ()
	{
		clearTimeout(this.shadowTimer); 
		clearTimeout(this.helpTimer); 
		BX.unbind(this.popupNode, 'transitionend', BX.proxy(this.loadFrame, this));

		BX.unbind(document, 'keydown', BX.proxy(this._close, this));
		BX.unbind(document, 'click', BX.proxy(this._close, this));

		if(this.popupNode.style.transition !== undefined)
			BX.bind(this.popupNode, 'transitionend', BX.proxy(this._clearPosFixed, this));
		else
			this._clearPosFixed();


		this.popupNode.style.width = 0;
		this.topBar.style.width = 0;

		if(this.topBar.classList){
			this.topBar.classList.remove('bx-help-nav-fixed');
			this.closeBtn.classList.remove('bx-help-close-anim');
		}
		else{
			BX.removeClass(this.topBar, 'bx-help-nav-fixed');
			BX.removeClass(this.closeBtn, 'bx-help-close-anim');
		}

		this.topBar.style.top = this.getTopCord().top + 'px';

		this.helpTimer = setTimeout(BX.proxy(function(){
			this.curtainNode.style.opacity = 0;
			this.closeBtn.style.display = 'none';

			if(this.openBtn.classList)
				this.openBtn.classList.remove('help-block-active');

		}, this),500)

		this.shadowTimer = setTimeout(BX.proxy(function(){
			this.frameNode.src = this.frameCloseUrl;
			this.popupNode.style.display = 'none';
			this.curtainNode.style.display = 'none';
			this.frameNode.style.opacity = 0;
			this.frameNode.style.height = 0;
			this.popupLoader.classList.remove('bx-help-popup-loader-show');
			BX.unbind(this.popupNode, 'transitionend', BX.proxy(this._clearPosFixed, this));

			if(this.topBar.classList)
				this.topBar.classList.remove('bx-help-nav-show');
			else
				BX.removeClass(this.topBar, 'bx-help-nav-show');
			this.isOpen = false;

		},this),800);

		
	},

	show : function(additionalParam)
	{
		if (typeof B24 === "object")
			B24.goUp();
		
		if (typeof additionalParam === "string")
		{
			this.frameOpenUrl = this.frameOpenUrl + "&" + additionalParam;
		}

		var top = this.getTopCord().top;
		var right = this.getTopCord().right;
		clearTimeout(this.shadowTimer); 
		clearTimeout(this.helpTimer); 

		this._setPosFixed();

		this.curtainNode.style.top = top +'px';
		this.curtainNode.style.width = this.getTopCord().right + 'px';
		this.curtainNode.style.display = 'block';
		this.popupNode.style.display = 'block';
		this.popupNode.style.paddingTop = top + 'px';
		this.topBar.style.top = top + 'px';
		this.closeBtn.style.top = (top - 63) + 'px';
		this.closeBtn.style.left = (right - 63) + 'px';
		this.closeBtn.style.display = 'block';
		this.popupLoader.style.top = top + 'px';

		if(this.openBtn.classList)
			this.openBtn.classList.add('help-block-active');

		if(this.popupNode.style.transition !== undefined){
			BX.bind(this.popupNode, 'transitionend', BX.proxy(this.loadFrame, this));
		}else {
			this.loadFrame(null);
		}

		this.shadowTimer = setTimeout(BX.proxy(function(){
			this.curtainNode.style.opacity = 1;

			if(this.closeBtn.classList)
				this.closeBtn.classList.add('bx-help-close-anim');
			else
				BX.addClass(this.closeBtn, 'bx-help-close-anim');
		}, this),25);

		this.helpTimer = setTimeout(BX.proxy(function(){
			this.popupNode.style.width = 860 + 'px';
			this.topBar.style.width = 860 + 'px';
			this.popupLoader.classList.add('bx-help-popup-loader-show');

			BX.bind(document, 'keydown', BX.proxy(this._close, this));
			BX.bind(document, 'click', BX.proxy(this._close, this));
			this.isOpen = true;

		}, this),300);

		
	},

	_close : function(event)
	{
		event = event || window.event;
		var target = event.target || event.srcElement;

		if(event.type == 'click'){
			BX.PreventDefault(event);
		}

		if(event.keyCode == 27){
			this.closePopup();
		}

		while(target != document.documentElement)
		{
			if (target == this.popupNode || target == this.closeBtn || target == this.topBar)
			{
				break;
			}
			else if(target == document.body && !event.keyCode){
				this.closePopup();
				break;
			}
			target = target.parentNode;
		}
	},

	loadFrame : function(event)
	{
		if(event !== null){
			event = event || window.event;
			var target = event.target || event.srcElement;

			if(target == this.popupNode)
				this.frameNode.src = this.frameOpenUrl;
		}else {
			this.frameNode.src = this.frameOpenUrl;
		}
	},

	reloadFrame : function(url)
	{
		this.frameNode.style.opacity = 0;
		this.frameNode.src = url;

		if(this.topBar.classList)
			this.topBar.classList.remove('bx-help-nav-show');
		else
			BX.removeClass(this.topBar, 'bx-help-nav-show');

		this.popupNode.scrollTop = 0;
	},
	getTopCord : function()
	{
		var pos = BX.pos(this.header);
		return {
			top:pos.bottom,
			right : pos.right
		}
	}
}
