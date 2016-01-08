<?php
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	return;
}

Loc::loadMessages(__FILE__);

class CDiskBitrix24DiskComponent extends BaseComponent
{
	protected function processActionDefault()
	{
		$pathToAjax = isset($this->arParams['AJAX_PATH'])? $this->arParams['AJAX_PATH'] : '/bitrix/components/bitrix/disk.bitrix24disk/ajax.php';

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$quota = CDiskQuota::getDiskQuota();
		$this->arResult['showDiskQuota'] = false; //$quota !== true; //now without quota
		$this->arResult['diskSpace'] = (float)COption::getOptionInt('main', 'disk_space')*1024*1024;
		$this->arResult['quota'] = $quota;
		$this->arResult['ajaxIndex'] = $pathToAjax;
		$this->arResult['ajaxStorageIndex'] = '/desktop_app/storage.php';
		$this->arResult['isInstalledDisk'] = \Bitrix\Disk\Desktop::isDesktopDiskInstall();
		$this->arResult['personalLibIndex'] = '/company/personal/user/' . $this->getUser()->getId() . '/disk/path/';

		$this->arResult['isInstalledPull'] = (bool)isModuleInstalled('pull');
		$this->arResult['currentUser'] = array(
			'id' => $this->getUser()->getId(),
			'formattedName' => $this->getUser()->getFormattedName(),
		);
		Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.bitrix24disk/disk.js');

		$this->includeComponentTemplate();
	}
}
