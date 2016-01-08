<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
return array(
	'DISK_READ' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_READ'),
	),
	'DISK_EDIT' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_EDIT'),
	),
	'DISK_SETTINGS' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_SETTINGS'),
	),
	'DISK_DELETE' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_DELETE'),
	),
	'DISK_RIGHTS' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_RIGHTS'),
	),
	'DISK_SHARING' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_SHARING'),
	),
	'DISK_START_BP' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_START_BP'),
	),
	'DISK_CREATE_WF' => array(
		'title' => Loc::getMessage('OP_NAME_DISK_CREATE_WF'),
	),
);
