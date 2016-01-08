<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPCWG_GROUP_NAME") ?>:</span></td>
	<td width="60%">
		<input type="text" name="group_name" id="id_group_name" value="<?= htmlspecialcharsbx($arCurrentValues["group_name"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_group_name', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPCWG_OWNER") ?>:</span></td>
	<td width="60%">
		<input type="text" name="owner_id" id="id_owner_id" value="<?= htmlspecialcharsbx($arCurrentValues["owner_id"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_owner_id', 'user');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?= GetMessage("BPCWG_USERS") ?>:</span></td>
	<td width="60%">
		<input type="text" name="users" id="id_users" value="<?= htmlspecialcharsbx($arCurrentValues["users"]) ?>" size="50">
		<input type="button" value="..." onclick="BPAShowSelector('id_users', 'user');">
	</td>
</tr>