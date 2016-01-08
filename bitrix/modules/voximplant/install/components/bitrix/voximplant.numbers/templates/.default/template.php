<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $arResult['NAV_OBJECT'] CAllDBResult
 * @var $APPLICATION CMain
 * @var $USER CUser
 */
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
$numbersC = CVoxImplantConfig::GetPortalNumbers();
$portalNumber = CVoxImplantConfig::GetPortalNumber();
$numbers = array('' => GetMessage("VI_NUMBERS_DEFAULT")) + $numbersC;
?>
<div class="bx-vi-block bx-vi-filter">
	<form id="search_form" action="<?=POST_FORM_ACTION_URI?>" method="GET">
		<input type="hidden" name="act" value="search">
		<span class="filter-field">
			<input name="FILTER" type="text" value="<?=htmlspecialcharsbx($arResult['FILTER'])?>" class="tel-set-inp" placeholder="<?=GetMessage('VI_NUMBERS_SEARCH')?>" />
			&nbsp;
			<a id="search_btn" href="#" class="webform-small-button">
				<span class="webform-small-button-left"></span>
				<span class="webform-small-button-text"><?=GetMessage('VI_NUMBERS_SEARCH_BTN'); ?></span>
				<span class="webform-small-button-right"></span>
			</a>
			<? if (!empty($arResult['FILTER'])): ?>
			<a id="clear_btn" href="#" class="webform-small-button">
				<span class="webform-small-button-left"></span>
				<span class="webform-small-button-text"><?=GetMessage('VI_NUMBERS_SEARCH_CANCEL'); ?></span>
				<span class="webform-small-button-right"></span>
			</a>
			<? endif; ?>
		</span>
		<input type="submit" style="visibility: hidden;" />
	</form>
</div>
<?

CJSCore::Init(array('admin_interface'));
$arResult['ROWS_COUNT'] = $arResult['NAV_OBJECT']->selectedRowsCount();
$arRows = array();
foreach ($arResult["USERS"] as $user)
{
	$arCols = array(
		'NAME' => '<table id="user_'.$user['ID'].'" style="border-collapse: collapse; border: none; ">
			<tr>
				<td style="border: none !important; padding: 0px !important; ">
					<div style="width: 32px; height: 32px; margin-top:4px; border-radius: 50%; overflow: hidden;">
						<a href="'.$user['DETAIL_URL'].'">'.$user['PHOTO_THUMB'].'</a>
					</div>
				</td>
				<td style="border: none !important; padding: 0px 0px 0px 7px !important; vertical-align: middle; ">
					<a href="'.$user['DETAIL_URL'].'"><b>'.CUser::formatName(CSite::getNameFormat(), $user, true, true).'</b></a><br>
					'.htmlspecialcharsbx($user['WORK_POSITION']).'
				</td>
			</tr>
		</table>',
		'UF_PHONE_INNER' => '<span id="innerphone_'.$user['ID'].'">'.$user["UF_PHONE_INNER"].'</span>',
		'UF_VI_BACKPHONE' => '<span id="backphone_'.$user['ID'].'">'.(
				array_key_exists($user["UF_VI_BACKPHONE"], $numbers) ? $numbers[$user["UF_VI_BACKPHONE"]] : GetMessage('VI_NUMBERS_DEFAULT')).'</span>'.
				'<span id="backphone_'.$user['ID'].'_value" style="display:none;">'.$user["UF_VI_BACKPHONE"].'</span>',
	);

	$arCols['UF_VI_PHONE'] = '<span id="vi_phone_'.$user['ID'].'"'.($user["UF_VI_PHONE"] == "Y" ? ' class="bx-vi-phone-enable"' : '').'>'.($user["UF_VI_PHONE"] == "Y" ? GetMessage('VI_NUMBERS_PHONE_DEVICE_ENABLE') : GetMessage('VI_NUMBERS_PHONE_DEVICE_DISABLE')).'</span>'.
		'<span style="display:none" id="vi_phone_enable_'.$user['ID'].'">'.($user["UF_VI_PHONE"] == "Y" ? 'Y' : 'N').'</span>';

	$arCols['EDIT'] = '<span id="create_'.$user['ID'].'">'.
			'<a href="#" onclick="BX.Voximplant.Numbers.edit('.$user['ID'].'); return false; ">'.GetMessage('VI_NUMBERS_EDIT').'</a>'.
		'</span>';

	$arRows[$user['ID']] = array('data' => $user, 'columns' => $arCols);
}
$arResult['ROWS'] = $arRows;

$arHeaders = array(
	array('id' => 'NAME', 'name' => GetMessage('VI_NUMBERS_GRID_NAME'), 'sort' => 'name', 'default' => true, 'editable' => false),
	array('id' => 'UF_PHONE_INNER', 'name' => GetMessage('VI_NUMBERS_GRID_CODE'), 'default' => true, 'editable' => false),
	array('id' => 'UF_VI_BACKPHONE', 'name' => GetMessage('VI_NUMBERS_GRID_PHONE'), 'default' => true, 'editable' => false),
);
$arHeaders[] = array('id' => 'UF_VI_PHONE', 'name' => GetMessage('VI_NUMBERS_GRID_PHONE_DEVICE'), 'default' => true, 'editable' => false);
$arHeaders[] = array('id' => 'EDIT', 'name' => '', 'default' => true, 'editable' => false);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arHeaders,
		'ROWS' => $arResult['ROWS'],
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
	)
);
?>
<div style="padding-top: 30px;">
	<div class="tel-set-item-alert">
		<?=GetMessage('VI_CONFIG_NOTICE_2', Array('#LINK#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'configs.php">'.GetMessage('VI_CONFIG_PAGE_CONFIGS').'</a>'))?>
	</div>
</div>
<script type="text/javascript">
BX.message({
	VI_NUMBERS_CREATE_TITLE : '<?=GetMessageJS("VI_NUMBERS_CREATE_TITLE")?>',
	VI_NUMBERS_ERR_AJAX : '<?=GetMessageJS("VI_NUMBERS_ERR_AJAX")?>',
	VI_NUMBERS_GRID_CODE : '<?=GetMessageJS("VI_NUMBERS_GRID_CODE")?>',
	VI_NUMBERS_GRID_PHONE : '<?=GetMessageJS("VI_NUMBERS_GRID_PHONE")?>',
	VI_NUMBERS_PHONE_DEVICE_ENABLE : '<?=GetMessageJS("VI_NUMBERS_PHONE_DEVICE_ENABLE")?>',
	VI_NUMBERS_PHONE_DEVICE_DISABLE : '<?=GetMessageJS("VI_NUMBERS_PHONE_DEVICE_DISABLE")?>',
	VI_NUMBERS_PHONE_CONNECT : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT")?>',
	VI_NUMBERS_PHONE_CONNECT_ON : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_ON")?>',
	VI_NUMBERS_PHONE_CONNECT_OFF : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_OFF")?>',
	VI_NUMBERS_PHONE_CONNECT_INFO : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_INFO")?>',
	VI_NUMBERS_PHONE_CONNECT_SERVER : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_SERVER")?>',
	VI_NUMBERS_PHONE_CONNECT_LOGIN : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_LOGIN")?>',
	VI_NUMBERS_PHONE_CONNECT_PASSWORD : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_PASSWORD")?>',
	VI_NUMBERS_SAVE : '<?=GetMessageJS("VI_NUMBERS_SAVE")?>',
	VI_NUMBERS_CANCEL : '<?=GetMessageJS("VI_NUMBERS_CANCEL")?>',
	VI_NUMBERS_URL : '<?=$this->__component->GetPath()?>/ajax.php?act='
});
BX.Voximplant.Numbers.init(<?=CUtil::PhpToJSObject($numbers)?>);
</script>
