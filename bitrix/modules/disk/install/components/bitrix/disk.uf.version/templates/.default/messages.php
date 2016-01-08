<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//attention! Necessarily in one line (without line break)!
?><script type="application/javascript">BX.message({disk_revision_api: '<?= (int)\Bitrix\Disk\Configuration::getRevisionApi() ?>',disk_document_service: '<?= (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode() ?>',wd_desktop_disk_is_installed: '<?= (bool)\Bitrix\Disk\Desktop::isDesktopDiskInstall() ?>',disk_render_uf: true});</script><?