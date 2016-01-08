<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="menu">
	<!-- navigation start -->
	<div id="navigation">
		<?if (!empty($arResult)):?>
		<ul class="sf-menu">
			<?foreach($arResult as $key => $arItem):?>
			<?if($arItem['DEPTH_LEVEL'] != 1) {
				continue;
			}
			?>
			<?if($arItem['DEPTH_LEVEL'] == 1):?>
				<li <?if($arItem["SELECTED"]):?>class="current"><?endif;?>>
				<a href = "<?=$arItem["LINK"];?>" class = "applyfont"><?=$arItem["TEXT"];?></a>
				<ul>
			<?endif;?>
			<?foreach($arResult as $keyInner => $arItemInner):?>
				<?if($keyInner <= $key) {
					continue;
				}
				?>
				<?if($arItemInner['DEPTH_LEVEL'] == 2):?>
					<li><a href = "<?=$arItemInner["LINK"];?>"><?=$arItemInner["TEXT"];?></a></li>
				<?endif;?>
				<?if($arItemInner['DEPTH_LEVEL'] != 2){
						break;
				}
				?>
			<?endforeach;?>
			<?if($arItem['DEPTH_LEVEL'] == 1):?>
		        </ul>
			<?endif;?>
			<?unset($arResult[$key]);?>
		</li>
		<?endforeach;?>
		<?endif?>
	</div>
	<!-- navigation end -->
</div>
<div class="clear"></div>