<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (WIZARD_INSTALL_DEMO_DATA === false)
	return; 
	
CModule::IncludeModule('iblock');

$dbRes = CIBlockSection::GetTreeList(array('ACTIVE' => 'Y', 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure', 0)));
$arSections = array();
$arSectionsMap = array();
$i = 0;
while ($arRes = $dbRes->Fetch())
{
	$arSectionsMap[$arRes['ID']] = $i++;
	$arSections[] = $arRes;
}

if (count($arSections) > 0)
{
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/csv_data.php"); 
	
	$csvFile = WIZARD_SERVICE_ABSOLUTE_PATH.'/head_'.LANGUAGE_ID.'.csv';
	
	if (!file_exists($csvFile))
		return;
	
	$csvData = new CCSVData('R', true);
	$csvData->LoadFile($csvFile);

	$arResult = array();
	while ($arRes = $csvData->Fetch())
	{
		if (strlen($arRes[1]) <= 0)
		{
			$current_section = $arSections[0]['ID'];
		}
		else
		{
			$left_margin = 0;
			$right_margin = $arSections[count($arSections)-1]['RIGHT_MARGIN']+1;
			$current_section = 0;
			
			$i = 1;
			foreach ($arSections as $key => $value)
			{
				if ($arSections[$key]['NAME'] == $arRes[$i])
				{
					$left_margin = $arSections[$key]['LEFT_MARGIN'];
					$right_margin = $arSections[$key]['RIGHT_MARGIN'];
					$current_section = $arSections[$key]['ID'];
					
					$i++;
				}
				elseif ($arSections[$key]['LEFT_MARGIN'] > $right_margin)
				{
					$current_section = 0;
					break;
				}
				
				if (strlen($arRes[$i]) <= 0)
					break;
			}
		}

		$arResult[$current_section] = $arRes[0];
	}

	if (count($arResult) > 0)
	{
		$dbRes = CUser::GetList($by = 'ID', $order = 'ASC', array('ACTIVE' => 'Y', 'LOGIN_EQUAL' => implode('|', $arResult)));
		$arUsers[] = array();
		while ($arRes = $dbRes->Fetch())
		{
			$arUsers[$arRes['LOGIN']] = $arRes['ID'];
		}

		if (count($arUsers) > 0)
		{
			$obS = new CIBlockSection();
			foreach ($arResult as $SECTION_ID => $LOGIN)
			{
				if ($arUsers[$LOGIN])
					$obS->Update($SECTION_ID, array('UF_HEAD' => $arUsers[$LOGIN]), false, false);
			}
		}
	}
	
	// rating update
	$RatSubID = COption::GetOptionString("intranet", "ratingSubordinateId", false);	
	if ($RatSubID)
	{
		CRatingRule::Apply($RatSubID);
		COption::RemoveOption("intranet", "ratingSubordinateId");
		// recount ratings
		$rsData = CRatings::GetList(array('ID'=>'ASC'), array());
		while($arRes = $rsData->Fetch())
		{
			CRatings::Calculate($arRes['ID'], true);
		}
	}
}
?>