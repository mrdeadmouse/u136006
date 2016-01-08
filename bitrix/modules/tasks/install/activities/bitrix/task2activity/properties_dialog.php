<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>
<tr>
	<td align="right" width="40%" valign="top"><?= GetMessage("BPCGHLP_HOLD_TO_CLOSE") ?>:</td>
	<td width="60%" valign="top">
		<select name="HOLD_TO_CLOSE">
			<option value="Y"<?= ("Y" == $arCurrentValues["HOLD_TO_CLOSE"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
			<option value="N"<?= ("N" == $arCurrentValues["HOLD_TO_CLOSE"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
		</select>
	</td>
</tr>
<?php
foreach ($arDocumentFields as $fieldKey => $fieldValue)
{
	if (
		($fieldValue['UserField']['USER_TYPE']['USER_TYPE_ID'] === 'crm')
		&& ($fieldValue['UserField']['USER_TYPE']['CLASS_NAME'] === 'CUserTypeCrm')
		&& CModule::IncludeModule('crm')
	)
	{
		?>
		<tr>
			<td align="right" width="40%" valign="top"><?= GetMessage("TASKS_BP_AUTO_LINK_TO_CRM_ENTITY") ?>:</td>
			<td width="60%" valign="top">
				<select name="AUTO_LINK_TO_CRM_ENTITY">
					<option value="Y"<?= ("Y" == $arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
					<option value="N"<?= ("N" == $arCurrentValues["AUTO_LINK_TO_CRM_ENTITY"] ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
				</select>
			</td>
		</tr>
		<?php
	}
	?>
	<tr>
		<td align="right" width="40%" valign="top"><?= ($fieldValue["Required"]) ? "<span class=\"adm-required-field\">".htmlspecialcharsbx($fieldValue["Name"])."</span>:" : htmlspecialcharsbx($fieldValue["Name"]) .":" ?></td>
		<td width="60%" id="td_<?= htmlspecialcharsbx($fieldKey) ?>" valign="top">
			<?
			if ($fieldValue["UserField"])
			{
				if ($arCurrentValues[$fieldKey])
				{
					if ($fieldValue["UserField"]["USER_TYPE_ID"] == "boolean")
					{
						$fieldValue["UserField"]["VALUE"] = ($arCurrentValues[$fieldKey] == "Y" ? 1 : 0);
					}
					else
					{
						$fieldValue["UserField"]["VALUE"] = $arCurrentValues[$fieldKey];
					}
					$fieldValue["UserField"]["ENTITY_VALUE_ID"] = 1; //hack to not empty value
				}
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:system.field.edit",
					$fieldValue["UserField"]["USER_TYPE"]["USER_TYPE_ID"],
					array(
						"bVarsFromForm" => false,
						"arUserField" => $fieldValue["UserField"],
						"form_name" => $formName
					), null, array("HIDE_ICONS" => "Y")
				);
			}
			else
			{
				$fieldValueTmp = $arCurrentValues[$fieldKey];

				$fieldValueTextTmp = '';
				if (isset($arCurrentValues[$fieldKey . '_text']))
					$fieldValueTextTmp = $arCurrentValues[$fieldKey . '_text'];

				switch ($fieldValue["Type"])
				{
					case "S:UserID":
						?><input type="text" size="40" id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>" value="<?= htmlspecialcharsbx($fieldValueTmp) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>', 'user');"><?
						break;
					case "S:DateTime":
						$v1 = $fieldValueTmp;
						$v2 = "";
						if (preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($v1)) || (substr(trim($v1), 0, 1) == "="))
						{
							$v1 = "";
							$v2 = $fieldValueTmp;
						}
						?>
						<input type="text" name="<?= $fieldKey ?>" value="<?= $v1 ?>" size="20"><?
						$GLOBALS['APPLICATION']->IncludeComponent(
							"bitrix:main.calendar",
							"",
							array(
								"SHOW_INPUT" => "N",
								"FORM_NAME" => $formName,
								"INPUT_NAME" => $fieldKey),
							null,
							array("HIDE_ICONS" => "Y"));
						?>
						<input type="text" id="id_<?= $fieldKey ?>_text" name="<?= $fieldKey ?>_text" value="<?= htmlspecialcharsbx($v2) ?>">
						<input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>_text', 'datetime');"><?
						break;
					case "L":
						?>
						<select id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>">
							<?
							foreach ($fieldValue["Options"] as $k => $v)
							{
								echo '<option value="'.htmlspecialcharsbx($k).'"'.($k."!" === $fieldValueTmp."!" ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
								if ($k."!" === $fieldValueTmp."!")
									$fieldValueTmp = "";
							}
							?>
						</select>
						<br /><input type="text" id="id_<?= $fieldKey ?>_text" name="<?= $fieldKey ?>_text" value="<?= $fieldValueTextTmp ?>">
						<input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>_text', 'select');">
						<?
						break;
					case "B":
						?>
						<select id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>">
							<option value="Y"<?= ("Y" == $fieldValueTmp ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
							<option value="N"<?= ("N" == $fieldValueTmp ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
						</select>
						<?
						if (in_array($fieldValueTmp, array("Y", "N")))
							$fieldValueTmp = "";
						?>
						<br /><input type="text" id="id_<?= $fieldKey ?>_text" name="<?= $fieldKey ?>_text" value="<?= $fieldValueTmp ?>">
						<input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>_text', 'bool');">
						<?
						break;
					case "T":
						?><textarea rows="5" cols="40" id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>"><?= htmlspecialcharsbx($fieldValueTmp) ?></textarea>
						<br /><input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>', 'string');"><?
						break;
					default:
						?><input type="text" size="40" id="id_<?= $fieldKey ?>" name="<?= $fieldKey ?>" value="<?= htmlspecialcharsbx($fieldValueTmp) ?>">
						<input type="button" value="..." onclick="BPAShowSelector('id_<?= $fieldKey ?>', 'string');"><?
						break;
				}
			}
			?>
		</td>
	</tr>
	<?php
}
?>
<?php echo $GLOBALS["APPLICATION"]->GetCSS();?>