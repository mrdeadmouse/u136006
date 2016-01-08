<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskBreadcrumbsComponent extends \Bitrix\Disk\Internals\BaseComponent
{
	protected function prepareParams()
	{
		parent::prepareParams();
		if(isset($this->arParams['BREADCRUMBS_ID']) && $this->arParams['BREADCRUMBS_ID'] !== '')
		{
			$this->arParams['BREADCRUMBS_ID'] = preg_replace('/[^a-z0-9_]/i', '', $this->arParams['BREADCRUMBS_ID']);
		}
		else
		{
			$this->arParams['BREADCRUMBS_ID'] = 'breadcrumbs_' . (strtolower(randString(5)));
		}
		if(!isset($this->arParams['SHOW_ONLY_DELETED']))
		{
			$this->arParams['SHOW_ONLY_DELETED'] = false;
		}
		if(!isset($this->arParams['BREADCRUMBS']))
		{
			$this->arParams['BREADCRUMBS'] = array();
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult = array(
			'BREADCRUMBS_ID' => $this->arParams['BREADCRUMBS_ID'],
			'BREADCRUMBS_ROOT' => array(
				'ID' => $this->arParams['BREADCRUMBS_ROOT']['ID'],
				'NAME' => $this->arParams['BREADCRUMBS_ROOT']['NAME'],
				'LINK' => $this->arParams['BREADCRUMBS_ROOT']['LINK'],
			),
			'BREADCRUMBS' => $this->arParams['BREADCRUMBS'],
			'SHOW_ONLY_DELETED' => $this->arParams['SHOW_ONLY_DELETED'],
		);

		$this->includeComponentTemplate();
	}
}