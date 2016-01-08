<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\PhaseSemantics;

abstract class DealDataSource extends DataSource
{
	/** @var string */
	protected $permissionSql;
	protected static $userNames = array();
	protected static $entityListPath = null;

	/**
	* @return string|boolean
	*/
	protected function preparePermissionSql()
	{
		if($this->permissionSql !== null)
		{
			return $this->permissionSql;
		}

		if(\CCrmPerms::IsAdmin($this->userID))
		{
			$this->permissionSql = '';
		}
		else
		{
			$this->permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::DealName,
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetUserPermissions($this->userID))
			);
		}
		return $this->permissionSql;
	}
	protected static function prepareUserNames(array $userIDs)
	{
		if(empty($userIDs))
		{
			return array();
		}

		$results = array();
		foreach($userIDs as $k => $v)
		{
			if(isset(self::$userNames[$v]))
			{
				$results[$v] = self::$userNames[$v];
				unset($userIDs[$v]);
			}
		}

		if(!empty($userIDs))
		{
			$dbResult = \CUser::GetList(
				($by = 'ID'),
				($order = 'ASC'),
				array('ID' => implode('||', $userIDs)),
				array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'))
			);

			$format = \CSite::GetNameFormat(false);
			while($user = $dbResult->Fetch())
			{
				$userID = (int)$user['ID'];
				$results[$userID] = \CUser::FormatName($format, $user, true, false);
			}
		}

		return $results;
	}
	/**
	* @return array
	*/
	protected static function externalizeFilter(Filter $filter)
	{
		$filterParams = $filter->getParams();
		$params = Filter::externalizeParams($filterParams);

		$semanticID = $filter->getExtraParam('semanticID', PhaseSemantics::UNDEFINED);
		if($semanticID !== PhaseSemantics::UNDEFINED)
		{
			$params['SEMANTIC_ID'] = $semanticID;
		}
		return $params;
	}
	/**
	* @return Filter
	*/
	protected static function internalizeFilter(array $params)
	{
		$filterParams = Filter::internalizeParams($params);
		if(isset($params['SEMANTIC_ID']))
		{
			if(!isset($filterParams['extras']))
			{
				$filterParams['extras'] = array();
			}
			$filterParams['extras']['semanticID'] = $params['SEMANTIC_ID'];
		}
		return new Filter($filterParams);
	}
	/**
	 * @return string
	 */
	public function getDetailsPageUrl(array $params)
	{
		$urlParams = array('WG' => 'Y', 'DS' => $this->getTypeName());

		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
		{
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");
		}

		$params = self::externalizeFilter($filter);
		foreach($params as $k => $v)
		{
			if(!is_array($v))
			{
				$urlParams[$k] = $v;
			}
			else
			{
				$qty = count($v);
				for($i = 0; $i < $qty; $i++)
				{
					$urlParams["{$k}[{$i}]"] = $v[$i];
				}
			}
		}

		return \CHTTP::urlAddParams(self::getEntityListPath(), $urlParams);
	}
	/**
	 * @return string
	 */
	protected static function getEntityListPath()
	{
		if(self::$entityListPath === null)
		{
			self::$entityListPath = \CComponentEngine::MakePathFromTemplate(
				Main\Config\Option::get('crm', 'path_to_deal_list', '/crm/deal/list/', false),
				array()
			);
		}
		return self::$entityListPath;
	}
}