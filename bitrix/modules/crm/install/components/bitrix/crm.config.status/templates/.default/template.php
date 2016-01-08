<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

if($arResult['NEED_FOR_FIX_STATUSES']):
	?><div id="fixStatusesMsg" class="crm-view-message">
		<?=GetMessage('CRM_STATUS_FIX_STATUSES', array('#ID#' => 'fixStatusesLink', '#URL#' => '#'))?>
	</div><?
endif;
?>
<form name="crmStatusForm" method="POST">
<input type="hidden" name="ACTION" value="save" id="ACTION">
<input type="hidden" name="ACTIVE_TAB" value="<?=htmlspecialcharsbx($arResult['ACTIVE_TAB'])?>" id="ACTIVE_TAB">
<?=bitrix_sessid_post()?>
<table cellspacing="0" cellpadding="0" class="status">
<tr>
	<td id="status_box" class="status_tabs">
	<?
	$tabActive = 'status_tab_active';
	foreach($arResult['HEADERS'] as $headerId => $headerName)
	{
		if ('status_tab_'.$headerId == $arResult['ACTIVE_TAB'])
			$tabActive = 'status_tab_active';
		else
			$tabActive = '';
		?>
		<a href="#<?=$headerId?>" id="status_tab_<?=$headerId?>" class="status_tab <?=$tabActive?>" onclick="changeTab(this)"><?=$headerName?></a>
		<?	
	}
	?>
	</td>
	<td class="status_box">
	<?
		
		foreach($arResult['HEADERS'] as $headerId => $headerName)
		{	
			$headerId = addslashes($headerId);
			$maxSort = 0;
			if ('status_tab_'.$headerId == $arResult['ACTIVE_TAB'])
				$tabActive = 'style="display:block"';
			else
				$tabActive = '';
			?>
			<div id="status_tab_<?=$headerId?>_body" class="status_tab_body" <?=$tabActive?>>
				<table id="tblLIST<?=$headerId?>">
				<?foreach($arResult["ROWS"][$headerId] as $arStatus):
					$maxSort = $maxSort < $arStatus['SORT']? $arStatus['SORT']: $maxSort;
					$arStatus['ID'] = addslashes($arStatus['ID']);		
					$arStatus['STATUS_ID'] = addslashes($arStatus['STATUS_ID']);		
				?>
				<tr>
					<td class="sort-arrow"><div class="sort-up" onclick="sort_up(this);" title="<?=GetMessage('CRM_STATUS_LIST_UP')?>"></div></td>
					<td class="sort-arrow"><div class="sort-down" onclick="sort_down(this);" title="<?=GetMessage('CRM_STATUS_LIST_DOWN')?>"></div></td>
					<td>
						<input type="hidden" name="LIST[<?=$headerId?>][<?=$arStatus['ID']?>][SORT]" value="<?=$arStatus['SORT']?>" class="sort-input">
						<input type="text" size="35" name="LIST[<?=$headerId?>][<?=$arStatus['ID']?>][VALUE]" id="field-<?=$headerId?>-<?=$arStatus['STATUS_ID']?>" value="<?=htmlspecialcharsbx($arStatus['NAME'])?>" class="value-input">
					</td>
					<?if ($arStatus['SYSTEM'] == 'N'):?>
						<td><div class="delete-action" onclick="delete_item(this);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
					<?else:?>
						<td><div class="recovery-action" onclick="recovery_name('field-<?=$headerId?>-<?=$arStatus['STATUS_ID']?>', '<?=addslashes($arStatus['NAME_INIT'])?>');" title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"></div></td>
					<?endif;?>
				</tr>
				<?endforeach;?>
				<tr>
					<td class="sort-arrow"><div class="sort-arrow sort-up" onclick="sort_up(this);" title="<?=GetMessage('CRM_STATUS_LIST_UP')?>"></div></td>
					<td class="sort-arrow"><div class="sort-arrow sort-down" onclick="sort_down(this);" title="<?=GetMessage('CRM_STATUS_LIST_DOWN')?>"></div></td>
					<td>
						<input type="hidden" name="LIST[<?=$headerId?>][n1][SORT]" value="<?=($maxSort+10)?>" class="sort-input">
						<input type="text" size="35" name="LIST[<?=$headerId?>][n1][VALUE]" value="" class="value-input">
					</td>
					<td><div class="delete-action" onclick="delete_item(this);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
				</tr>
				</table>
				<div class="status-field-add"><a href="#add" onclick="addNewTableRow('<?=$headerId?>', /LIST\[<?=$headerId?>\]\[(n)([0-9]*)\]/g, 2)"><?=GetMessage('CRM_STATUS_LIST_ADD')?></a></div>
				<table id="tblSAMPLE<?=$headerId?>" style="display:none">
				<tr>
					<td class="sort-arrow"><div class="sort-arrow sort-up" onclick="sort_up(this);" title="<?=GetMessage('CRM_STATUS_LIST_UP')?>"></div></td>
					<td class="sort-arrow"><div class="sort-arrow sort-down" onclick="sort_down(this);" title="<?=GetMessage('CRM_STATUS_LIST_DOWN')?>"></div></td>
					<td>
						<input type="hidden" name="LIST[<?=$headerId?>][n0][SORT]" value="<?=($maxSort+10)?>" class="sort-input">
						<input type="text" size="35" name="LIST[<?=$headerId?>][n0][VALUE]" value="" class="value-input">
					</td>
					<td><div class="delete-action" onclick="delete_item(this);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
				</tr>
				</table>
			</div>
			<?
			if (!empty($tabActive))
				$tabActive = '';
		}
	?>
	<div class="status_buttons">
		<input type="submit" value="<?=GetMessage('CRM_STATUS_BUTTONS_SAVE');?>">
		<input type="button" value="<?=GetMessage('CRM_STATUS_BUTTONS_CANCEL');?>" onclick="statusReset()">
	</div>
	</td>
</tr>
</table>
</form>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var mgr = BX.CrmStatusManager.create(
				"crm_status_mgr",
				{
					serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.config.status/ajax.php?&<?=bitrix_sessid_get()?>"
				}
			);
			<?if($arResult['NEED_FOR_FIX_STATUSES']):?>
			BX.addCustomEvent(
				mgr,
				'ON_STATUS_FIXING_COMPLETE',
				function()
				{
					window.location.reload(true);
				}
			);

			var link = BX("fixStatusesLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						mgr.fixStatuses();
						return BX.PreventDefault(e);
					}
				);
			}
			<?endif;?>
		}
	);
</script>
