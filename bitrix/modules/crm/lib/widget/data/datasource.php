<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

abstract class DataSource
{
	/** @var array */
	protected $settings = null;
	/** @var int */
	protected $userID = 0;
	/** @var boolean */
	protected $enablePermissionCheck = true;

	public function __construct(array $settings, $userID = 0, $enablePermissionCheck = true)
	{
		$this->settings = $settings;

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}
		$this->userID = $userID;
		if(!is_bool($enablePermissionCheck))
		{
			$enablePermissionCheck = (bool)$enablePermissionCheck;
		}
		$this->enablePermissionCheck = $enablePermissionCheck;
	}

	/**
	* @return int
	*/
	public function getUserID()
	{
		return $this->userID;
	}
	/**
	* @return boolean
	*/
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	/**
	* @return string
	*/
	abstract function getTypeName();
	/**
	 * @return array
	 */
	abstract public function getList(array $params);
	/**
	 * @return array
	 */
	public function getFirst(array $params)
	{
		$l = $this->getList($params);
		return !empty($l) ? $l[0] : null;
	}
	/**
	 * @return string
	 */
	public function getFirstValue(array $params, $fieldName, $defaultValue = '')
	{
		$l = $this->getList($params);
		return !empty($l) && isset($l[0][$fieldName]) ? $l[0][$fieldName] : $defaultValue;
	}
	/**
	 * @return string
	 */
	public function getDetailsPageUrl()
	{
		return '';
	}
	/** @return array */
	public function prepareEntityListFilter(array $filterParams)
	{
		return null;
	}
	/**
	 * @return string|boolean
	 */
	protected abstract function preparePermissionSql();
}