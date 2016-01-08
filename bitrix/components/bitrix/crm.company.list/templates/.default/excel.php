<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!is_array($arResult['COMPANY']) || !($USERS_CNT = count($arResult['COMPANY']))):
	echo(GetMessage('ERROR_COMPANY_IS_EMPTY'));
else:
?><meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
<table border="1">
<thead>
	<tr><?
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
		$arHeaders[$arHead['id']] = $arHead;

	// Display headers
	foreach($arResult['SELECTED_HEADERS'] as $headerID):
		$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
		if($arHead):
		?><th><?=$arHead['name']?></th><?
		endif;
	endforeach;
	?></tr>
</thead>
<tbody><?
	foreach ($arResult['COMPANY'] as $i => &$arCompany):
	?><tr><?
		foreach($arResult['SELECTED_HEADERS'] as $headerID):
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if(!$arHead)
				continue;

			$headerID = $arHead['id'];
			$result = '';

			switch($headerID):
				case 'COMPANY_TYPE':
					$result = isset($arCompany['COMPANY_TYPE']) ? $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']] : '';
					break;
				case 'EMPLOYEES':
					$result = isset($arCompany['EMPLOYEES']) ? $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']] : '';
					break;
				case 'INDUSTRY':
					$result = isset($arCompany['INDUSTRY']) ? $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']] : '';
					break;
				case 'CURRENCY_ID':
					$result = isset($arCompany['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($arCompany['CURRENCY_ID']) : '';
					break;
				case 'CREATED_BY':
					$result = isset($arCompany['CREATED_BY_FORMATTED_NAME']) ? $arCompany['CREATED_BY_FORMATTED_NAME'] : '';
					break;
				case 'MODIFY_BY':
					$result = isset($arCompany['MODIFY_BY_FORMATTED_NAME']) ? $arCompany['MODIFY_BY_FORMATTED_NAME'] : '';
					break;
				default:
					if(isset($arResult['COMPANY_UF'][$i]) && isset($arResult['COMPANY_UF'][$i][$headerID])):
						$result = $arResult['COMPANY_UF'][$i][$headerID];
					elseif (is_array($arResult['COMPANY'][$i][$headerID])):
						$result = implode(', ', $arCompany[$headerID]);
					else:
						$result = $arCompany[$headerID];
					endif;
			endswitch;
		?><td><?=$result?></td><?
		endforeach;
	?></tr><?
	endforeach;
?></tbody>
</table><?
endif;