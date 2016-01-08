<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

abstract class DataSourceFactory
{
	public static function create(array $settings, $userID = 0, $enablePermissionCheck = true)
	{
		$name = isset($settings['name']) ? strtoupper($settings['name']) : '';
		if($name === DealSumStatistics::TYPE_NAME)
		{
			return new DealSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInvoiceStatistics::TYPE_NAME)
		{
			return new DealInvoiceStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealActivityStatistics::TYPE_NAME)
		{
			return new DealActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealStageHistory::TYPE_NAME)
		{
			return new DealStageHistory($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInWork::TYPE_NAME)
		{
			return new DealInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealIdle::TYPE_NAME)
		{
			return new DealIdle($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === ExpressionDataSource::TYPE_NAME)
		{
			return new ExpressionDataSource($settings, $userID);
		}
		else
		{
			throw new Main\NotSupportedException("The data source '{$name}' is not supported in current context.");
		}
	}

	public static function getPresets()
	{
		return array_merge(
			DealSumStatistics::getPresets(),
			DealInWork::getPresets(),
			DealIdle::getPresets(),
			DealActivityStatistics::getPresets(),
			DealInvoiceStatistics::getPresets()
		);
	}
}