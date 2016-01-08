<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$composite = \Bitrix\Main\Page\Frame::getInstance();
$composite->setEnable();
$composite->setUseAppCache();
?>
<div id="im-dialog-wrap"></div>
<div id="im-dialog-form"></div>
<?
$frame = $this->createFrame("im_component_dialog_v4_".$USER->GetId())->begin();
$frame->setBrowserStorage(true);
?>
<script type="text/javascript">
	<?=CIMMessenger::GetMobileTemplateJS(Array(), $arResult)?>
</script>
<?
$frame->beginStub();
?>
<div class="bx-messenger-cl-item-load"><?=GetMessage('IM_DIALOG_LOADING')?></div>
<?
$frame->end();
?>
