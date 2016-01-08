<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $arResult['NAV_OBJECT'] CAllDBResult
 * @var $APPLICATION CMain
 * @var $USER CUser
 */
$numbersC = CVoxImplantConfig::GetPortalNumbers();
$portalNumber = CVoxImplantConfig::GetPortalNumber();
$numbers = array('' => GetMessage("VI_NUMBERS_DEFAULT")) + $numbersC;
?>

<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-tab-title" style="width: 100%; ">
	<tr>
		<td class="bx-form-title">
			<?=GetMessage('VI_NUMBERS_TITLE_2')?>
		</td>
	</tr>
</table>
<div class="tel-set-item bx-vi-options" style="margin-top:20px; margin-bottom: 10px;">
	<form id="option_form">
		<input type="hidden" name="act" value="save">
		<dl>
			<dt><?=GetMessage("VI_NUMBERS_CONFIG_BACKPHONE")?></dt>
			<dd>
				<select name="portalNumber" class="tel-set-inp">
					<?foreach ($numbersC as $k => $v): ?>
					<option value="<?=$k?>" <? if ($portalNumber == $k): ?> selected <? endif; ?>><?=$v?></option>
					<?endforeach;?>
				</select>
				<span><?=GetMessage("VI_NUMBERS_CONFIG_BACKPHONE_TITLE")?></span>
			</dd>
		</dl>
		<a id="option_btn" href="javascript:void(0);" class="webform-small-button webform-small-button-accept">
			<span class="webform-small-button-left"></span>
			<span class="webform-small-button-text"><?=GetMessage('VI_NUMBERS_SAVE'); ?></span>
			<span class="webform-small-button-right"></span>
		</a>
	</form>
</div>
<script type="text/javascript">
BX.message({
	VI_NUMBERS_URL : '<?=$this->__component->GetPath()?>/ajax.php?act='
});
BX.Voximplant.Numbers.init(<?=CUtil::PhpToJSObject($numbers)?>);
</script>
