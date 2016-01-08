<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
CUtil::InitJSCore(array("amcharts", "amcharts_funnel", "amcharts_serial"));

$quid = $arResult['GUID'];
$prefix = strtolower($quid);
$settings = $arResult['SETTINGS'];

$height = $arResult['HEIGHT'];
$layout = $arResult['LAYOUT'];
$containerID = "{$prefix}_container";
?>
<div class="crm-widget-container" id="<?=htmlspecialcharsbx($containerID)?>"></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmWidget.messages =
			{
				"legend" : "<?=GetMessageJS("CRM_WGT_RATING_LEGEND")?>",
				"nomineeRatingPosition" : "<?=GetMessageJS("CRM_WGT_RATING_NOMINEE_POSITION")?>",
				"ratingPosition" : "<?=GetMessageJS("CRM_WGT_RATING_POSITION")?>"
			};<?
			$width = 100;
			if($layout === '70/30')
				$width = 70;
			elseif($layout === '50/50')
				$width = 50;
			elseif($layout === '30/70')
				$width = 30;
			$qty = count($settings);
			for($i = 0; $i < $qty; $i++):
				$setting = $settings[$i];
				$id = "{$quid}_{$i}";

				$setting['containerId'] = $containerID;
				$setting['prefix'] = "{$prefix}_{$i}";
				$setting['heightInPixel'] = $height;
				$setting['widthInPercent'] = $width;
				//Preparation of next widget's width
				if(($width % 50) !== 0)
					$width = 100 - $width;
			?>BX.CrmWidget.create("<?=CUtil::JSEscape($id)?>", <?=CUtil::PhpToJSObject($setting)?>).layout();<?
			endfor;
		?>}
	);
</script>