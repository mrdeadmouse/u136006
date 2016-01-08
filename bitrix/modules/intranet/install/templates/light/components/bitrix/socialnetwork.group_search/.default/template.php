<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$this->SetViewTarget("sidebar", 100);
?>
<form method="get" action="<?= $arResult["Urls"]["GroupSearch"] ?>" style="margin:0;padding:0;">
<input type="hidden" name="<?= $arParams["PAGE_VAR"] ?>" value="group_search">
	
<div class="sidebar-block">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="sidebar-block-title"><?= GetMessage("SONET_C24_T_SEARCH_TITLE") ?></div>
		<div class="filter-block">
			<div class="filter-field filter-field-user-fio">
				<label class="filter-field-title" for="group-q"><span class="required-field">*</span><?= GetMessage("SONET_C24_T_SEARCH") ?></label>
				<input class="filter-textbox" type="text" id="group-q" name="q" value="<?= $arResult["q"] ?>" />
			</div>
			<div class="filter-field filter-field-user-fio">
				<label class="filter-field-title" for="group-subject"><?= GetMessage("SONET_C24_T_SUBJECT") ?></label>
				<select name="subject" id="group-subject" class="filter-dropdown">
					<option value=""><?= GetMessage("SONET_C24_T_ANY") ?></option>
					<?foreach ($arResult["Subjects"] as $k => $v):?>
						<option value="<?= $k ?>"<?= ($k == $arResult["subject"]) ? " selected" : "" ?>><?= $v ?></option>
					<?endforeach;?>
				</select>
			</div>			
			<div class="filter-field-buttons">
				<input type="submit" value="<?= GetMessage("SONET_C24_T_DO_SEARCH") ?>" class="filter-submit" />
				<input type="button" value="<?= GetMessage("SONET_C24_T_DO_CANCEL") ?>" onclick="window.location='<?= $arResult["Urls"]["GroupSearch"] ?>'">				
			</div>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
<?if ($arResult["how"] == "d"):?>
	<input type="hidden" name="how" value="d">
<?endif;?>
</form>
<?
$this->EndViewTarget();
?>
<?if ($arResult["ALLOW_CREATE_GROUP"]):

	$GLOBALS["INTRANET_TOOLBAR"]->AddButton(array(
		'HREF' => $arResult["Urls"]["GroupCreate"],
		"TEXT" => GetMessage('SONET_C24_T_CREATE_GROUP'),
		'ICON' => 'create',
		"SORT" => 1000,
	));	

endif;?>
<?if (strlen($arResult["ERROR_MESSAGE"]) <= 0):?>
	<?if (count($arResult["SEARCH_RESULT"]) > 0):?>
		<br /><?foreach ($arResult["SEARCH_RESULT"] as $v):?>
		<table width="100%" class="sonet-user-profile-friends data-table">
			
				<tr>
					<td width="105" nowrap valign="top" align="center">
						<?= $v["IMAGE_IMG"] ?>
					</td>
					<td valign="top">
						<a href="<?= $v["URL"] ?>"><b><?= $v["TITLE_FORMATED"] ?></b></a><br />
						<?
						if ($v["ARCHIVE"] == "Y")
						{
							?>
							<br />
							<b><?= GetMessage("SONET_C39_ARCHIVE_GROUP") ?></b>
							<?
						}
						if (strlen($v["BODY_FORMATED"]) > 0)
						{
							?>
							<br />
							<?= $v["BODY_FORMATED"] ?>
							<?
						}
						if (strlen($v["SUBJECT_NAME"]) > 0)
						{
							?>
							<br />
							<?= GetMessage("SONET_C24_T_SUBJ") ?>: <?= $v["SUBJECT_NAME"] ?>
							<?
						}
						if (IntVal($v["NUMBER_OF_MEMBERS"]) > 0)
						{
							?>
							<br />
							<?= GetMessage("SONET_C24_T_MEMBERS") ?>: <?= $v["NUMBER_OF_MEMBERS"] ?>
							<?
						}
						?>
						<br />
						<?= GetMessage("SONET_C24_T_ACTIVITY") ?>: <?= $v["FULL_DATE_CHANGE_FORMATED"]; ?>
					</td>
				</tr>
			
		</table>
		<br />
		<?endforeach;?>

		<?if (strlen($arResult["NAV_STRING"]) > 0):?>
			<p><?=$arResult["NAV_STRING"]?></p>
		<?endif;?>
			
		<?if (strlen($arResult["ORDER_LINK"]) > 0):?>
			<?if ($arResult["how"] == "d"):?>
				<p><a href="<?= $arResult["ORDER_LINK"] ?>"><?= GetMessage("SONET_C24_T_ORDER_REL") ?></a>&nbsp;|&nbsp;<b><?= GetMessage("SONET_C24_T_ORDER_DATE") ?></b></p>
			<?else:?>
				<p><b><?= GetMessage("SONET_C24_T_ORDER_REL") ?></b>&nbsp;|&nbsp;<a href="<?=$arResult["ORDER_LINK"]?>"><?= GetMessage("SONET_C24_T_ORDER_DATE") ?></a></p>
			<?endif;?>
		<?endif;?>
	<?endif;?>
<?else:?>
	<?= ShowError($arResult["ERROR_MESSAGE"]); ?>
<?endif;?>