<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
?>
<div class="tel-set-wrap">
	<div class="tel-top-title-block">
		<div class="tel-top-title-icon"></div>
		<?=GetMessage('VI_CONFIG_WELCOME_MESSAGE')?>
	</div>
	<table class="tel-block-table" id="tel-block-table">
		<tr class="tel-block-top">
			<td class="tel-block tel-block-own-num <?=($arResult['MODE_LINK']? 'tel-block-active': '')?>"  data-block="tel-set-first">
				<div class="tel-block-title">
					<span class="tel-block-title-icon"></span>
					<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_LINK')?></span>
				</div>
				<div class="tel-block-text">
					<?=GetMessage('VI_CONFIG_LINK_DESC')?>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<td class="tel-block tel-block-rent-num <?=($arResult['MODE_RENT']? 'tel-block-active': '')?>"  data-block="tel-set-second">
				<div class="tel-block-title">
					<span class="tel-block-title-icon"></span>
					<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_RENT')?></span>
				</div>
				<div class="tel-block-text">
					<?=GetMessage('VI_CONFIG_RENT_DESC')?>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<td class="tel-block tel-block-connect-atc <?=($arResult['MODE_SIP']? 'tel-block-active': '')?>" data-block="tel-set-third">
				<div class="tel-block-title">
					<span class="tel-block-title-icon"></span>
					<span class="tel-block-title-text"><?=GetMessage('VI_CONFIG_SIP')?></span>
				</div>
				<div class="tel-block-text">
					<?=GetMessage('VI_CONFIG_SIP_DESC')?>
				</div>
			</td>
		</tr>
		<tr class="tel-block-bottom">
			<td class="tel-block <?=($arResult['MODE_LINK']? 'tel-block-active': '')?>"  data-block="tel-set-first">
				<div class="tel-block-footer">
					<span class="tel-block-btn" id="tel-set-first-btn"><?=GetMessage($arResult['MODE_LINK']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<td class="tel-block <?=($arResult['MODE_RENT']? 'tel-block-active': '')?>"  data-block="tel-set-second">
				<div class="tel-block-footer">
					<span class="tel-block-btn" id="tel-set-second-btn"><?=GetMessage($arResult['MODE_RENT']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
				</div>
			</td>
			<td class="tel-block-space"></td>
			<td class="tel-block <?=($arResult['MODE_SIP']? 'tel-block-active': '')?>" data-block="tel-set-third">
				<div class="tel-block-footer">
					<span class="tel-block-btn" id="tel-set-third-btn"><?=GetMessage($arResult['MODE_SIP']? 'VI_CONFIG_SET_ACTIVE': 'VI_CONFIG_SET')?></span>
				</div>
			</td>
		</tr>
	</table>
	<div class="tel-set-block-wrap" id="tel-set-block-wrap">
		<div class="tel-set-block tel-set-block-active" id="tel-set-block">
			<div id="tel-set-first" class="tel-set-block-inner-wrap" <?=($arResult['MODE_LINK']? 'style="display: block;"': '')?>>
				<div class="tel-set-inner">
					<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.link", "", array());?>
				</div>
			</div>
			<div id="tel-set-second" class="tel-set-block-inner-wrap" <?=($arResult['MODE_RENT']? 'style="display: block;"': '')?>>
				<div class="tel-set-inner">
					<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.rent", "", array());?>
				</div>
			</div>
			<div id="tel-set-third" class="tel-set-block-inner-wrap" <?=($arResult['MODE_SIP']? 'style="display: block;"': '')?>>
				<div class="tel-set-inner">
					<?$APPLICATION->IncludeComponent("bitrix:voximplant.config.sip", "", array());?>
				</div>
			</div>
			<div id="tel-set-corner" class="tel-set-corner"></div>
		</div>
	</div>
</div>

<div class="tel-set-phone-numbers">
	<div class="tel-set-item-alert">
		<?=GetMessage('VI_CONFIG_NOTICE_NEW', Array(
			'#LINK_CONFIG#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'configs.php">'.GetMessage('VI_CONFIG_PAGE_CONFIG').'</a>',
			'#LINK_USERS#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'users.php">'.GetMessage('VI_CONFIG_PAGE_CONFIG_USERS').'</a>'
		))?>
	</div>
</div>

<script type="text/javascript">
	var setPost = {
		corner : BX('tel-set-corner'),
		anim_block : null,
		btn : null,
		wrap_block : BX('tel-set-block'),
		block_list : null,
		table : BX('tel-block-table'),
		active_cell_num : null,
		over_cell_num : null,

		show : function(ev)
		{
			var event = ev || window.event;
			var target = event.target || event.srcElement;
			var active_cell,
				btn;

			while(target != this)
			{
				if (target.tagName == 'TD')
				{
					active_cell = target;
					break;
				}
				target = target.parentNode;
			}

			if(!active_cell.hasAttribute('data-block')) return;

			if(event.type == 'mouseover'){
				setPost.block_hover(active_cell);
			}
			else if (event.type == 'mouseout'){
				setPost.block_out();
			}
			else if(event.type == 'click')
			{
				var blockID = active_cell.getAttribute('data-block');

				if(blockID == 'tel-set-first'){
					btn = BX('tel-set-first-btn')
				}else if(blockID == 'tel-set-second'){
					btn = BX('tel-set-second-btn')
				}else if(blockID == 'tel-set-third'){
					btn = BX('tel-set-third-btn')
				}
				setPost.anim(blockID, btn)
			}
		},

		anim : function(blockID, btn)
		{

			this.block_list = this.wrap_block.childNodes;

			this.anim_block = BX(blockID);
			this.btn = btn;

			//this.wrap_block.style.height = this.wrap_block.offsetHeight + 'px';

			for(var i = this.block_list.length-1; i>=0; i--){
				if(this.block_list[i].tagName == 'DIV' && this.block_list[i] != this.corner){
					this.block_list[i].style.display = 'none';
				}
			}

			this.anim_block.style.display = 'block';

			var corner_offset =  ((this.btn.offsetWidth/2) + BX.pos(this.btn).left) - ((this.corner.offsetWidth/2) + BX.pos(this.corner).left);
			this.corner.style.left = parseInt(BX.style(this.corner, 'left')) + corner_offset + 'px';

			for(var i = this.table.rows.length-1; i >=0; i--){
				for(var b = this.table.rows[i].cells.length-1; b>=0; b--)
				{
					BX.removeClass(this.table.rows[i].cells[b], 'tel-block-active');

					if(this.btn.parentNode.parentNode == this.table.rows[i].cells[b]){
						this.active_cell_num = b;
					}
				}
			}

			BX.addClass(this.table.rows[0].cells[this.active_cell_num], 'tel-block-active');
			BX.addClass(this.table.rows[1].cells[this.active_cell_num], 'tel-block-active')

		},

		anim_easing : function(params){
			var _this = this;
			var easing = new BX.easing({
				duration:300,
				start : params.start,
				finish : params.finish,
				transition : BX.easing.makeEaseOut(BX.easing.transitions.linear),
				step:function(state){
					_this.wrap_block.style.height = state.height +'px';
				},
				complete:function(){}
			});

			easing.animate()
		},

		block_hover : function(cell)
		{
			var tr;
			tr = cell.parentNode;

			for(var i = tr.cells.length-1; i>=0; i--){
				if(tr.cells[i] == cell){
					this.over_cell_num = i;
				}
			}

			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.addClass(this.table.rows[i].cells[this.over_cell_num] ,'tel-block-hover')
			}
		},

		block_out : function()
		{
			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.removeClass( this.table.rows[i].cells[this.over_cell_num] ,'tel-block-hover')
			}
		}
	};

	BX.bind(BX('tel-block-table'), 'mouseover', setPost.show);
	BX.bind(BX('tel-block-table'), 'mouseout', setPost.show);
	BX.bind(BX('tel-block-table'), 'click', setPost.show);

	<?
		if ($arResult['MODE_ACTIVE'] == 'SIP' || $arResult['MODE_ACTIVE'] == '' && $arResult['MODE_SIP'])
		{?>
			var blockID = 'tel-set-third';
			var btn = BX('tel-set-third-btn');
		<?}
		else if ($arResult['MODE_ACTIVE'] == 'RENT' || $arResult['MODE_ACTIVE'] == '' && $arResult['MODE_RENT'])
		{?>
			var blockID = 'tel-set-second';
			var btn = BX('tel-set-second-btn');
		<?}
		else if ($arResult['MODE_ACTIVE'] == 'LINK' || $arResult['MODE_ACTIVE'] == '' && $arResult['MODE_LINK'])
		{?>
			var blockID = 'tel-set-first';
			var btn = BX('tel-set-first-btn');
		<?}
	?>
	setPost.anim(blockID, btn);
</script>