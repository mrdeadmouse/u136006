<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?= GetMessage("BPCA_PD_PHP") ?>:</span></td>
	<td width="60%">
		<textarea name="execute_code" id="id_execute_code" rows="10" cols="70"><?= htmlspecialcharsbx($arCurrentValues["execute_code"]) ?></textarea>
		<input style="vertical-align: top" type="button" value="..." onclick="BPAShowSelector('id_execute_code', 'string');">
	</td>
</tr>