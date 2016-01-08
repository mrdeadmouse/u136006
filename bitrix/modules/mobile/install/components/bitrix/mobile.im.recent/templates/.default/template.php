<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$composite = \Bitrix\Main\Page\Frame::getInstance();
$composite->setEnable();
$composite->setUseAppCache();
?>
<div id="im-contact-list-search"></div>
<div id="im-contact-list-wrap"></div>
<?
$frame = $this->createFrame("im_component_recent_v2_".$USER->GetId())->begin();
$frame->setBrowserStorage(true);
?>
<script type="text/javascript">
	<?=CIMMessenger::GetMobileTemplateJS(Array(), $arResult)?>
</script>
<?
$frame->beginStub();
?>
<div class="bx-messenger-cl-item-load"><?=GetMessage('IM_RESENT_LOADING')?></div>
<?
$frame->end();
?>
