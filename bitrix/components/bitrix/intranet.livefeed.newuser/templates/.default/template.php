<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$id = 'intranet_lfn_'.$arParams['USER']['ID'].'_'.RandString(5);
?>
<span class="feed-workday-left-side">
	<div<?if($arParams['AVATAR_SRC']):?> style="background: url('<?=$arParams['AVATAR_SRC']?>') no-repeat center center;"<?endif?> class="feed-user-avatar"></div>
	<span class="feed-user-name-wrap">
		<a class="feed-workday-user-name" href="<?=$arParams['USER_URL']?>" id="<?=$id?>"><?=CUser::FormatName(
			$arParams['PARAMS']['NAME_TEMPLATE'],
			is_array($arParams['USER']) ? $arParams['USER'] : array()
		); ?></a>
		<span class="feed-workday-user-position"><?=htmlspecialcharsbx($arParams['USER']['WORK_POSITION'])?></span>
	</span>
</span>
<script type="text/javascript">BX.tooltip('<?=$arParams['USER']['ID']?>', '<?=$id?>', '<?=$APPLICATION->GetCurPageParam("", array("bxajaxid", "logout"));?>');</script>
