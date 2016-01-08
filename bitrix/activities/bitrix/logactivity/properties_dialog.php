<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCAL_PD_TEXT") ?>:</span></td>
	<td width="60%">
		<textarea name="text" id="id_text" rows="3" cols="40"><?= htmlspecialcharsbx($arCurrentValues["text"]) ?></textarea>
		<input style="vertical-align: top" type="button" value="..." onclick="BPAShowSelector('id_text', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCAL_PD_SET_VAR") ?>:</td>
	<td width="60%">
		<input type="checkbox" name="set_variable" value="Y"<?= ($arCurrentValues["set_variable"] == "Y") ? " checked" : "" ?>>
	</td>
</tr>