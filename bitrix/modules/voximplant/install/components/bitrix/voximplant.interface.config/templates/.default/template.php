<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?

foreach($arResult['INTERFACE_CHAT_OPTIONS'] as $action)
{
	$arResult['INTERFACE_CHAT_OPTIONS_FINAL'][$action] = GetMessage('VI_INTERFACE_CHAT_'.$action);
}

?>
<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-tab-title" style="width: 100%; ">
	<tr>
		<td class="bx-form-title">
			<?=GetMessage('VI_INTERFACE_TITLE')?>
		</td>
	</tr>
</table>
<div class="tel-set-item bx-vi-options" style="margin-top:20px; margin-bottom: 10px;">
	<form id="interface_chat_option_form">
		<input type="hidden" name="act" value="save">
		<dl>
			<dt><?=GetMessage("VI_INTERFACE_CHAT_TITLE")?></dt>
			<dd>
				<select name="chatAction" class="tel-set-inp" style="width: 380px;">
					<?foreach ($arResult['INTERFACE_CHAT_OPTIONS_FINAL'] as $k => $v): ?>
					<option value="<?=$k?>" <? if ($arResult['INTERFACE_CHAT_ACTION'] == $k): ?> selected <? endif; ?>><?=$v?></option>
					<?endforeach;?>
				</select>
			</dd>
		</dl>
		<a id="interface_chat_option_btn" href="javascript:void(0);" class="webform-small-button webform-small-button-accept">
			<span class="webform-small-button-left"></span>
			<span class="webform-small-button-text"><?=GetMessage('VI_INTERFACE_SAVE'); ?></span>
			<span class="webform-small-button-right"></span>
		</a>
	</form>
</div>
<script type="text/javascript">
BX.message({
	VI_INTERFACE_URL : '<?=$this->__component->GetPath()?>/ajax.php'
});
BX.Voximplant.Interface.init();
</script>
