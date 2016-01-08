<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $INTRANET_TOOLBAR;
$INTRANET_TOOLBAR->Show();
?><?
ob_start();

$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "sections", $arParams, $component);
$current_section = intval($arFilterValues[$arParams['FILTER_NAME'].'_UF_DEPARTMENT']);

$sFilter = ob_get_contents();
ob_end_clean();

if ($arResult['USER_CAN_SET_HEAD'] && $current_section):
	$arParams['LIST_OBJECT'] = 'arEmployees';
	?>
	<script>
		var arEmployees = [];
		var bx_head_menu = new PopupMenu('bx_head_menu', 300);
		function ShowHeadMenu(el)
		{
			if (arEmployees.length > 0)
			{
				var items = [];

				for (var i = 0; i < arEmployees.length; i++)
				{
					items[i] = {
						ICONCLASS: !arEmployees[i].CURRENT ? '' : 'checked',
						TEXT: arEmployees[i].NAME,
						ONCLICK: 'SetHead(' + arEmployees[i].ID + ')'
					}
				}
				bx_head_menu.ShowMenu(el, items);
			}
		}

		function SetHead(id)
		{
			var url = '/bitrix/tools/intranet_set_head.php?SECTION_ID=<?echo $current_section?>&USER_ID=' + parseInt(id) + '&<?echo bitrix_sessid_get()?>';
			jsUtils.LoadPageToDiv(url, 'blank')
		}

		window.onload = function() {if (arEmployees.length > 0) document.getElementById('bx_head_link').style.display = 'block';}
	</script>
	<?
endif;

$this->SetViewTarget("sidebar_tools_2", 100);
?>
<div class="sidebar-links">
	<?
	if ($arResult['USER_CAN_SET_HEAD'] && $current_section):
		?><span id="blank"></span>
		<a href="javascript:void(0)" id="bx_head_link" onclick="ShowHeadMenu(this);" style="display: none;"><i class="sidebar-action-head"></i><b><?echo GetMessage('INTR_IS_TPL_HEAD')?><span></span></b></a>
		<?
	endif;
	?>
	<a href="javascript:<?echo htmlspecialcharsbx(CIntranetUtils::GetStsSyncURL(
			array(
				'LINK_URL' => $APPLICATION->GetCurPage()
			), 'contacts'
		))?>" title="<?=GetMessage('INTR_IS_TPL_OUTLOOK_TITLE')?>"><i class="sidebar-action-outlook"></i><b><?echo GetMessage('INTR_IS_TPL_OUTLOOK')?></b></a>
</div>
<?
$this->EndViewTarget();
?>
<?
echo $sFilter;
?>
<br />
<?
if ($arFilterValues[$arParams['FILTER_NAME'].'_UF_DEPARTMENT']):
	$APPLICATION->IncludeComponent("bitrix:intranet.structure.list", "list", $arParams, $component);
endif;
?>