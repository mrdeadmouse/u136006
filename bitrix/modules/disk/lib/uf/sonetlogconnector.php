<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Main\Loader;

final class SonetLogConnector extends StubConnector implements ISupportForeignConnector
{
	private $canRead = null;
	private $logEntryData;

	public function getDataToShow()
	{
		if(!($log = $this->loadLogEntryData()))
		{
			return null;
		}

		$data = array();

		if (
			strpos($log["EVENT_ID"], "crm_") === 0
			&& Loader::includeModule('crm')
		)
		{
			if (strpos($log["EVENT_ID"], "_message") > 0)
			{
				$connector = new CrmMessageConnector($log["ID"], $this->attachedObject);
				$subData = $connector->getDataToShow();
				$data = array_merge($data, $subData);
			}

			$connector = null;
			if ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Deal)
			{
				$connector = new CrmDealConnector($log["ENTITY_ID"], $this->attachedObject);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Lead)
			{
				$connector = new CrmLeadConnector($log["ENTITY_ID"], $this->attachedObject);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Company)
			{
				$connector = new CrmCompanyConnector($log["ENTITY_ID"], $this->attachedObject);
			}
			elseif ($log["ENTITY_TYPE"] == \CCrmLiveFeedEntity::Contact)
			{
				$connector = new CrmContactConnector($log["ENTITY_ID"], $this->attachedObject);
			}

			if ($connector)
			{
				$subData = $connector->getDataToShow();
				$data = array_merge($data, $subData);
			}

			return $data;
		}
		else
		{
			return array();
		}
	}

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		if (\CSocNetUser::isCurrentUserModuleAdmin())
		{
			$this->canRead = true;

			return $this->canRead;
		}

		if ($log = $this->loadLogEntryData())
		{
			if (strpos($log["EVENT_ID"], "crm_") === 0 && Loader::includeModule('crm'))
			{
				$userPermissions = \CCrmPerms::getUserPermissions($userId);
				if ($log["ENTITY_TYPE"] == "CRMACTIVITY")
				{
					$bindings = \CCRMActivity::getBindings($log["ENTITY_ID"]);
					foreach($bindings as $binding)
					{
						if (\CCrmAuthorizationHelper::checkReadPermission(\CCrmOwnerType::resolveName($binding["OWNER_TYPE_ID"]), $binding["OWNER_ID"], $userPermissions))
						{
							$this->canRead = true;

							return $this->canRead;
						}
					}
				}
				else
				{
					if (\CCrmAuthorizationHelper::checkReadPermission(\CCrmLiveFeedEntity::resolveEntityTypeID($log["ENTITY_TYPE"]), $log["ENTITY_ID"], $userPermissions))
					{
						$this->canRead = true;

						return $this->canRead;
					}
					elseif (\CSocNetLogRights::checkForUser($log["ID"], $userId))
					{
						$this->canRead = true;

						return $this->canRead;
					}
				}
			}
			elseif (\CSocNetLogRights::checkForUser($log["ID"], $userId))
			{
				$this->canRead = true;

				return $this->canRead;
			}
		}

		$this->canRead = false;

		return $this->canRead;
	}

	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

	protected function loadLogEntryData()
	{
		$queryLog = \CSocNetLog::getList(
			array(),
			array(
				"ID" => $this->entityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "SOURCE_ID")
		);
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */

		return ($this->logEntryData = $queryLog->fetch());
	}
}
