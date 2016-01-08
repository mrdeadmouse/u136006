<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
$i = 0;
if (!$arParams["AJAX_MODE"]):
	?>
	<div class="sidebar-buttons" id="bx_intranet_toolbar">
	<?
else:
	?>
	<div class="sidebar-buttons" id="bx_intranet_toolbar_tmp" style="display: none;">
	<?
endif;

foreach ($arParams["OBJECT"]->arButtons as $arButton):
	$arAttributes = array();
	if ($arButton['HREF'])
		$arAttributes[] = 'href="'.htmlspecialcharsbx($arButton['HREF']).'"';
	else
		$arAttributes[] = 'href="javascript:void(0)"';
		
	if ($arButton['ONCLICK'])
		$arAttributes[] = 'onclick="'.htmlspecialcharsbx($arButton['ONCLICK']).'"';

	if ($arButton['ID'])
		$arAttributes[] = 'id="'.htmlspecialcharsbx($arButton['ID']).'"';
		
	if ($arButton['ICON'] == 'add')
		$arButton['ICON'] = 'create';

	if ($arButton['ICON'] == 'import-users')
		$arButton['ICON'] = 'import';
			
	if ($arButton['TITLE'])
		$arAttributes[] = 'title="'.htmlspecialcharsbx($arButton['TITLE']).'"';
//	<td class="bx-intranet-button-container bx-intranet-".htmlspecialcharsbx($arButton['ICON'])">
	?>
		<a <?echo implode(' ', $arAttributes)?> class="sidebar-button">
				<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
				<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-<?=htmlspecialcharsbx($arButton['ICON'])?>"></i><b><?echo htmlspecialcharsbx($arButton['TEXT'])?></b></span></span>
				<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a>

	<?
endforeach;
?>
</div>
<?
if ($arParams["AJAX_MODE"]):
	?>
	<script type="text/javascript">
	setTimeout(function() {
		var obToolbar = document.getElementById('bx_intranet_toolbar');
		var obToolbarTmp = document.getElementById('bx_intranet_toolbar_tmp');

		if (null == obToolbar)
		{
			obToolbarTmp.id = 'bx_intranet_toolbar';
			obToolbarTmp.style.display = 'block';
		}
		else
		{
			obToolbar.innerHTML = obToolbarTmp.innerHTML;
			obToolbarTmp.parentNode.removeChild(obToolbarTmp);
			obToolbarTmp = null;
		}
	}, 200);
	</script>
	<?
endif;
?>