<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!is_array($arResult['COMPANY']) || !($USERS_CNT = count($arResult['COMPANY'])))
{
	echo(GetMessage('ERROR_COMPANY_IS_EMPTY'));
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}

	// Display headers
	foreach($arResult['SELECTED_HEADERS'] as $headerID)
	{
		$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
		if($arHead)
		{
			echo '"', str_replace('"', '""', $arHead['name']),'";';
		}
	}
	echo "\n";

	// Display data
	foreach ($arResult['COMPANY'] as $i => &$arCompany)
	{
		foreach($arResult['SELECTED_HEADERS'] as $headerID)
		{
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if(!$arHead)
			{
				continue;
			}

			$headerID = $arHead['id'];
			$result = '';

			switch($headerID)
			{
				case 'COMPANY_TYPE':
				{
					$result = $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']];
					break;
				}
				case 'EMPLOYEES':
				{
					$result = $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']];
					break;
				}
				case 'INDUSTRY':
				{
					$result = $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']];
					break;
				}
				case 'CURRENCY_ID':
				{
					$result = CCrmCurrency::GetCurrencyName($arCompany['CURRENCY_ID']);
					break;
				}
				case 'CREATED_BY':
				{
					$result = $arCompany['CREATED_BY_FORMATTED_NAME'];
					break;
				}
				case 'MODIFY_BY':
				{
					$result = $arCompany['MODIFY_BY_FORMATTED_NAME'];
					break;
				}
				default:
				{
					if(isset($arResult['COMPANY_UF'][$i]) && isset($arResult['COMPANY_UF'][$i][$headerID]))
					{
						$result = $arResult['COMPANY_UF'][$i][$headerID];
					}
					elseif (is_array($arCompany[$headerID]))
					{
						$result = implode(', ', $arCompany[$headerID]);
					}
					else
					{
						$result = strval($arCompany[$headerID]);
					}
				}
			}

			echo '"', str_replace('"', '""', htmlspecialcharsback($result)), '";';
		}
		echo "\n";
	}
}