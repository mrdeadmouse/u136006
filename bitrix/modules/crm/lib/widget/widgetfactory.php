<?php
namespace Bitrix\Crm\Widget;
use Bitrix\Main;

abstract class WidgetFactory
{
	const FUNNEL = 'FUNNEL';
	const GRAPH = 'GRAPH';
	const BAR = 'BAR';
	const NUMBER = 'NUMBER';
	const RATING = 'RATING';

	/**
	* @return Widget
	*/
	public static function create(array $settings, Filter $filter)
	{
		$typeName = isset($settings['typeName']) ? strtoupper($settings['typeName']) : '';
		if($typeName === self::FUNNEL)
		{
			return new DealFunnelWidget($settings, $filter);
		}
		elseif($typeName === self::GRAPH || $typeName === self::BAR)
		{
			return new DealGraphWidget($settings, $filter);
		}
		elseif($typeName === self::NUMBER)
		{
			return new DealNumericWidget($settings, $filter);
		}
		elseif($typeName === self::RATING)
		{
			return new DealRatingWidget($settings, $filter);
		}
		else
		{
			throw new Main\NotSupportedException("The widget type '{$typeName}' is not supported in current context.");
		}
	}
}