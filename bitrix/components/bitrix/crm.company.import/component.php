<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

use Bitrix\Crm\EntityAddress;

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'IMPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(!function_exists('__CrmImportPrepareFieldBindingTab'))
{
	function __CrmImportPrepareFieldBindingTab(&$arResult, &$arRequireFields)
	{
		$resultMessages = array();
		$arFields = Array(''=>'');
		$arFieldsUpper = Array();
		foreach($arResult['HEADERS'] as $arField)
		{
			//echo '"'.$arField['name'].'";';
			$arFields[$arField['id']] = $arField['name'];
			$arFieldsUpper[$arField['id']] = strtoupper($arField['name']);
			if ($arField['mandatory'] == 'Y')
				$arRequireFields[$arField['id']] = $arField['name'];
		}

		require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');
		$csvFile = new CCSVData();
		$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
		$csvFile->SetFieldsType('R');
		$csvFile->SetFirstHeader(false);
		$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

		$iRow = 1;
		$arHeader = Array();
		$arRows = Array();
		while($arData = $csvFile->Fetch())
		{
			if ($iRow == 1)
			{
				foreach($arData as $key => $value)
				{
					if ($_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] && empty($value))
						continue;
					if ($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
						$arHeader[$key] = empty($value)? GetMessage('CRM_COLUMN_HEADER').' '.($key+1): trim($value);
					else
						$arHeader[$key] = GetMessage('CRM_COLUMN_HEADER').' '.($key+1);
				}
				if (!$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
					foreach($arHeader as $key => $value)
						$arRows[$iRow][$key] = $arData[$key];
			}
			else
				foreach($arHeader as $key => $value)
					$arRows[$iRow][$key] = $arData[$key];

			if ($iRow > 5)
				break;

			$iRow++;
		}
		$_SESSION['CRM_IMPORT_FILE_HEADERS'] = $arHeader;
		$_SESSION['CRM_IMPORT_FILE_FLIPPED_HEADERS'] = array_flip($arHeader);

		if(count($arHeader) === 1)
		{
			$resultMessages[] = GetMessage('CRM_CSV_CUSTOM_SINGLE_HEADER_ERROR');
		}

		$arResult['FIELDS']['tab_2'] = array();
		if (count($arRequireFields) > 0)
		{
			ob_start();
			?>
			<div class="crm_import_require_fields">
				<?=GetMessage('CRM_REQUIRE_FIELDS')?>: <b><?=implode('</b>, <b>', $arRequireFields)?></b>.
			</div>
			<?
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_2'][] = array(
				'id' => 'IMPORT_REQUIRE_FIELDS',
				'name' => '',
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
		}

		foreach ($arHeader as $key => $value)
		{
			$arResult['FIELDS']['tab_2'][] = array(
				'id' => 'IMPORT_FILE_FIELD_'.$key,
				'name' => $value,
				'items' => $arFields,
				'type' => 'list',
				'value' => isset($arFields[strtoupper($value)])? strtoupper($value): array_search(strtoupper($value), $arFieldsUpper),
			);
		}

		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_ASSOC_EXAMPLE',
			'name' => GetMessage('CRM_SECTION_IMPORT_ASSOC_EXAMPLE'),
			'type' => 'section'
		);
		ob_start();
		?>
		<div id="crm_import_example" class="crm_import_example">
			<table cellspacing="0" cellpadding="0" class="crm_import_example_table">
				<tr>
					<?foreach ($arHeader as $key => $value):?>
						<th><?=htmlspecialcharsbx($value)?></th>
					<?endforeach;?>
				</tr>
				<?foreach ($arRows as $arRow):?>
					<tr>
					<?foreach ($arRow as $row):?>
						<td><?=htmlspecialcharsbx($row)?></td>
					<?endforeach;?>
					</tr>
				<?endforeach;?>
			</table>
		</div>
		<script type="text/javascript">
			windowSizes = BX.GetWindowSize(document);
			if (windowSizes.innerWidth > 1024)
				BX('crm_import_example').style.width = '870px';
			if (windowSizes.innerWidth > 1280)
				BX('crm_import_example').style.width = '1065px';
		</script>
		<?
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_2'][] = array(
			'id' => 'IMPORT_ASSOC_EXAMPLE_TABLE',
			'name' => "",
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
		return implode("\n", $resultMessages);
	}
}
if(!function_exists('__CrmImportPrepareDupControlTab'))
{
	function __CrmImportPrepareDupControlTab(&$arResult)
	{
		$arResult['FIELDS']['tab_3'] = array();
		$arResult['DUP_CONTROL_TYPES'] = array(
			'NO_CONTROL' => GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_DESCR'),
			'REPLACE' => GetMessage('CRM_FIELD_DUP_CONTROL_REPLACE_DESCR'),
			'MERGE' => GetMessage('CRM_FIELD_DUP_CONTROL_MERGE_DESCR'),
			'SKIP' => GetMessage('CRM_FIELD_DUP_CONTROL_SKIP_DESCR')
		);

		$dupCtrlPrefix = $arResult['DUP_CONTROL_PREFIX'] = 'dup_ctrl_';
		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_TYPE',
			'type' => 'custom',
			'value' =>
				'<div class="crm-dup-control-type-radio-title">'.GetMessage('CRM_FIELD_DUP_CONTROL_TITLE').':</div>'.
				'<div class="crm-dup-control-type-radio-wrap">'.
				'<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'no_control" name="IMPORT_DUP_CONTROL_TYPE" value="NO_CONTROL" checked="checked" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_CAPTION').'</label>'.
				'<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'replace" name="IMPORT_DUP_CONTROL_TYPE" value="REPLACE" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_REPLACE_CAPTION').'</label>'.
				'<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'merge" name="IMPORT_DUP_CONTROL_TYPE" value="MERGE" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_MERGE_CAPTION').'</label>'.
				'<input type="radio" class="crm-dup-control-type-radio" id="'.$dupCtrlPrefix.'skip" name="IMPORT_DUP_CONTROL_TYPE" value="SKIP" /><label class="crm-dup-control-type-label">'.GetMessage('CRM_FIELD_DUP_CONTROL_SKIP_CAPTION').'</label>'.
				'</div>',
			'colspan' => true
		);

		$dupControlTypeDescrId = $arResult['DUP_CONTROL_TYPE_DESCR_ID'] = 'dup_ctrl_type_descr';
		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_TYPE_DESCR',
			'type' => 'custom',
			'value' => '<div class="crm-dup-control-type-info" id="'.$dupControlTypeDescrId.'">'.GetMessage('CRM_FIELD_DUP_CONTROL_NO_CONTROL_DESCR').'</div>',
			'colspan' => true
		);

		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_CRITERION',
			'name' => GetMessage('CRM_GROUP_DUP_CONTROL_CRITERION'),
			'type' => 'section'
		);

		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_ORGANIZATION'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_PHONE',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_PHONE'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		$arResult['FIELDS']['tab_3'][] = array(
			'id' => 'IMPORT_DUP_CONTROL_ENABLE_EMAIL',
			'name' => GetMessage('CRM_FIELD_DUP_CONTROL_ENABLE_EMAIL'),
			'type' => 'checkbox',
			'value' => 'Y'
		);

		return '';
	}
}
if(!function_exists('__CrmImportWriteDataToFile'))
{
	function __CrmImportWriteDataToFile($filePath, $headers, $data)
	{
		$file = fopen($filePath, 'ab');
		$fileSize = filesize($filePath);
		if(is_resource($file))
		{
			if($fileSize > 0)
			{
				fwrite($file, "\n");
			}
			else
			{
				if(is_array($headers))
				{
					foreach($headers as $header)
					{
						fwrite($file, '"');
						fwrite($file, str_replace('"', '""', $header));
						fwrite($file, '";');
					}
					fwrite($file, "\n");
				}
			}

			foreach($data as $datum)
			{
				fwrite($file, '"');
				fwrite($file, str_replace('"', '""', $datum));
				fwrite($file, '";');
			}
			fclose($file);
			unset($file);
		}
	}
}
global $USER_FIELD_MANAGER;
$CCrmFieldMulti = new CCrmFieldMulti();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$addressLabels = EntityAddress::getShortLabels();
$regAddressLabels = EntityAddress::getShortLabels(EntityAddress::Registered);
$arResult['HEADERS'] = array(
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE')),
	array('id' => 'LOGO', 'name' => GetMessage('CRM_COLUMN_LOGO')),
	array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE')),
	array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_INDUSTRY')),
	array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_EMPLOYEES')),
	array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE')),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID')),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
	array('id' => 'ASSIGNED_BY_ID', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY_ID')),
	array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel()),
	array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS']),
	array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2']),
	array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY']),
	array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION']),
	array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE']),
	array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE']),
	array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY']),
	array('id' => 'FULL_REG_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(EntityAddress::Registered)),
	array('id' => 'REG_ADDRESS', 'name' => $regAddressLabels['ADDRESS']),
	array('id' => 'REG_ADDRESS_2', 'name' => $regAddressLabels['ADDRESS_2']),
	array('id' => 'REG_ADDRESS_CITY', 'name' => $regAddressLabels['CITY']),
	array('id' => 'REG_ADDRESS_REGION', 'name' => $regAddressLabels['REGION']),
	array('id' => 'REG_ADDRESS_PROVINCE', 'name' => $regAddressLabels['PROVINCE']),
	array('id' => 'REG_ADDRESS_POSTAL_CODE', 'name' => $regAddressLabels['POSTAL_CODE']),
	array('id' => 'REG_ADDRESS_COUNTRY', 'name' => $regAddressLabels['COUNTRY'])
);

$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS')),
		array('id' => 'OPENED', 'name' => GetMessage('CRM_COLUMN_OPENED'))
	)
);

$CCrmUserType->ListAddHeaders($arResult['HEADERS'], true);

$arRequireFields = Array();
$arRequireFields['TITLE'] = GetMessage('CRM_COLUMN_TITLE');

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_IMPORT'] = CrmCheckPath('PATH_TO_COMPANY_IMPORT', $arParams['PATH_TO_COMPANY_IMPORT'], $APPLICATION->GetCurPage().'?import');
$userNameFormats = \Bitrix\Crm\Format\PersonNameFormatter::getAllDescriptions();
//Download sample
if(isset($_REQUEST['getSample']) && $_REQUEST['getSample'] == 'csv')
{
	$APPLICATION->RestartBuffer();

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	Header("Content-Disposition: attachment;filename=company.csv");
	Header("Content-Transfer-Encoding: binary");

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$typeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$industryList = CCrmStatus::GetStatusListEx('INDUSTRY');
	$employeeList = CCrmStatus::GetStatusListEx('EMPLOYEES');

	$arDemo = array(
		'TITLE' => GetMessage('CRM_SAMPLE_TITLE'),
		'COMPANY_TYPE' => $typeList['CUSTOMER'],
		'INDUSTRY' => $industryList['IT'],
		'EMPLOYEES' => $employeeList['EMPLOYEES_1'],
		'REVENUE' => GetMessage('CRM_SAMPLE_REVENUE'),
		'CURRENCY_ID' => GetMessage('CRM_SAMPLE_CURRENCY_ID'),
		'PHONE_WORK' => GetMessage('CRM_SAMPLE_PHONE'),
		'EMAIL_WORK' => GetMessage('CRM_SAMPLE_EMAIL'),
		'OPENED' => GetMessage('MAIN_YES')
	);

	foreach($arResult['HEADERS'] as $arField):
		echo '"', str_replace('"', '""', $arField['name']),'";';
	endforeach;
	echo "\n";
	foreach($arResult['HEADERS'] as $arField):
		echo isset($arDemo[$arField['id']])? '"'.str_replace('"', '""', $arDemo[$arField['id']]).'";': '"";';
	endforeach;
	echo "\n";
	die();
}
else if (isset($_REQUEST['import']) && isset($_SESSION['CRM_IMPORT_FILE']))
{
	$APPLICATION->RestartBuffer();

	global 	$USER_FIELD_MANAGER;
	$CCrmFieldMulti = new CCrmFieldMulti();
	$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);

	require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

	$arStatus['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arStatus['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');
	$arStatus['INDUSTRY_LIST'] = CCrmStatus::GetStatusListEx('INDUSTRY');
	$arStatus['OPENED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));

	$csvFile = new CCSVData();
	$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
	$csvFile->SetFieldsType('R');
	$csvFile->SetPos($_SESSION['CRM_IMPORT_FILE_POS']);
	$csvFile->SetFirstHeader($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER']);
	$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPORATOR']);

	$arResult = Array();
	$arResult['import'] = 0;
	$arResult['duplicate'] = 0;
	$arResult['duplicate_url'] = '';
	$arResult['error'] = 0;
	$arResult['error_data'] = array();
	$arResult['errata_url'] = '';
	$CCrmCompany = new CCrmCompany();

	$usersByID = array();
	$usersByName = array();
	$defaultUserID =  isset($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) ? intval($_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID']) : 0;
	$userNameFormat = isset($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
		&& \Bitrix\Crm\Format\PersonNameFormatter::isDefined($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
			? intval($_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'])
			: \Bitrix\Crm\Format\PersonNameFormatter::FirstLast;

	$dupCtrlType = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] : '';
	if(!in_array($dupCtrlType, array('REPLACE', 'MERGE', 'SKIP'), true))
	{
		$dupCtrlType = 'NO_CONTROL';
	}

	$enableDupCtrlByOrganization = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION'] : false;
	$enableDupCtrlByPhone = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE'] : false;
	$enableDupCtrlByEmail = isset($_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL']) ? $_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL'] : false;

	$mappedFields = isset($_SESSION['CRM_IMPORT_MAPPED_FIELDS']) ? $_SESSION['CRM_IMPORT_MAPPED_FIELDS'] : array();
	$mappedMultiFields = isset($_SESSION['CRM_IMPORT_MAPPED_MULTI_FIELDS']) ? $_SESSION['CRM_IMPORT_MAPPED_MULTI_FIELDS'] : array();

	$dupChecker = new \Bitrix\Crm\Integrity\CompanyDuplicateChecker();
	$processedQty = 0;

	$tempDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
	if($tempDir === '')
	{
		$tempDir = $_SESSION['CRM_IMPORT_TEMP_DIR'] = CTempFile::GetDirectoryName(1, array('crm', uniqid('company_import_')));
		CheckDirPath($tempDir);
	}
	$errataFilePath = "{$tempDir}errata.csv";

	$enableDupFile = $dupCtrlType === 'SKIP';
	if($enableDupFile)
	{
		$duplicateFilePath = "{$tempDir}duplicate.csv";
	}

	while($arData = $csvFile->Fetch())
	{
		$arResult['column'] = count($arData);
		$arCompany = Array();
		foreach ($arData as $key => $data)
		{
			if (isset($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]) && !empty($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]))
			{
				$currentKey = strtoupper($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]);
				if ($currentKey == 'ID' || strpos($currentKey, '~') === 0)
				{
					continue;
				}

				$data = trim(htmlspecialcharsback($data));

				if ($currentKey == 'COMPANY_TYPE')
				{
					$arCompany[$currentKey] = isset($arStatus['COMPANY_TYPE_LIST'][$data])? $data: array_search($data, $arStatus['COMPANY_TYPE_LIST']);
				}
				elseif ($currentKey == 'EMPLOYEES')
				{
					$arCompany[$currentKey] = isset($arStatus['EMPLOYEES_LIST'][$data])? $data: array_search($data, $arStatus['EMPLOYEES_LIST']);
				}
				elseif ($currentKey  == 'INDUSTRY')
				{
					$arCompany[$currentKey] = isset($arStatus['INDUSTRY_LIST'][$data])? $data: array_search($data, $arStatus['INDUSTRY_LIST']);
				}
				elseif ($currentKey  == 'CURRENCY_ID')
				{
					$currency = CCrmCurrency::GetByName($data);
					if(!$currency)
					{
						$currency = CCrmCurrency::GetByID($data);
					}

					$arCompany[$currentKey] = $currency ? $currency['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
				}
				elseif ($currentKey  == 'OPENED')
				{
					$arCompany[$currentKey] = isset($arStatus[$currentKey.'_LIST'][$data])? $data: array_search($data, $arStatus[$currentKey.'_LIST']);
					if ($arCompany[$currentKey] === false)
						unset($arCompany[$currentKey]);
				}
				elseif ($currentKey == 'LOGO')
				{
					if(CCrmUrlUtil::HasScheme($data) && CCrmUrlUtil::IsSecureUrl($data))
					{
						$data = CFile::MakeFileArray($data);
						if (is_array($data) && strlen(CFile::CheckImageFile($data)) === 0)
						{
							$arCompany[$currentKey] = array_merge($data, array('MODULE_ID' => 'crm'));
						}
					}
				}
				elseif ($currentKey == 'ASSIGNED_BY_ID')
				{
					$userID = 0;
					if(is_numeric($data))
					{
						// 1. Try to interpret value as user ID
						$userID = is_int($data) ? $data : intval($data);
						if($userID > 0 && !isset($usersByID[$userID]))
						{
							$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', array('ID'=> $userID), array('FIELDS' => array('ID')));
							$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
							if(is_array($user))
							{
								$usersByID[$userID] = $user;
							}
							else
							{
								//Reset user
								$userID = 0;
							}
						}
					}
					else
					{
						if(preg_match('/^.+\[\s*(\d+)\s*]$/', $data, $m) === 1)
						{
							// 2. Try to interpret value as user name with ID
							$userID = intval($m[1]);
							if($userID > 0 && !isset($usersByID[$userID]))
							{
								$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', array('ID'=> $userID), array('FIELDS' => array('ID')));
								$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
								if(is_array($user))
								{
									$usersByID[$userID] = $user;
								}
								else
								{
									//Reset user
									$userID = 0;
								}
							}
						}
						else
						{
							// 3. Try to interpret value as user name (#NAME# #LAST_NAME#)
							if(isset($usersByName[$data]))
							{
								$userID = intval($usersByName[$data]['ID']);
							}
							else
							{
								$nameParts = array();
								if(\Bitrix\Crm\Format\PersonNameFormatter::tryParseName($data, $userNameFormat, $nameParts))
								{
									$userFilter = array();
									if(isset($nameParts['NAME']))
									{
										$userFilter['NAME'] = $nameParts['NAME'];
									}
									if(isset($nameParts['SECOND_NAME']))
									{
										$userFilter['SECOND_NAME'] = $nameParts['SECOND_NAME'];
									}
									if(isset($nameParts['LAST_NAME']))
									{
										$userFilter['LAST_NAME'] = $nameParts['LAST_NAME'];
									}

									$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', $userFilter, array('FIELDS' => array('ID')));
									$user = is_object($dbUsers) ? $dbUsers->Fetch() : null;
									if(is_array($user))
									{
										$userID = $user['ID'] = intval($user['ID']);
										$usersByName[$data] = $user;
									}
								}
							}
						}
					}
					if($userID > 0)
					{
						$arCompany['ASSIGNED_BY_ID'] = $userID;
					}
					elseif($defaultUserID > 0)
					{
						$arCompany['ASSIGNED_BY_ID'] = $defaultUserID;
					}
				}
				else
				{
					// Finaly try to internalize user type values
					$arCompany[$currentKey] = $CCrmUserType->Internalize($currentKey, $data, ',');
				}
			}
		}

		if (!isset($arCompany['ASSIGNED_BY_ID']) && $defaultUserID > 0)
		{
			$arCompany['ASSIGNED_BY_ID'] = $defaultUserID;
		}

		//Try to map full address to first address line
		if(isset($arCompany['FULL_ADDRESS']) && !isset($arCompany['ADDRESS']))
		{
			$arCompany['ADDRESS'] = $arCompany['FULL_ADDRESS'];
			unset($arCompany['FULL_ADDRESS']);
		}
		if(isset($arCompany['FULL_REG_ADDRESS']) && !isset($arCompany['REG_ADDRESS']))
		{
			$arCompany['REG_ADDRESS'] = $arCompany['FULL_REG_ADDRESS'];
			unset($arCompany['FULL_REG_ADDRESS']);
		}

		CCrmFieldMulti::PrepareFields($arCompany);

		$isDuplicate = false;
		if($dupCtrlType !== 'NO_CONTROL'
			&& ($enableDupCtrlByOrganization || $enableDupCtrlByPhone || $enableDupCtrlByEmail))
		{
			$fieldNames = array();
			if($enableDupCtrlByOrganization)
			{
				$fieldNames[] = 'TITLE';
			}
			if($enableDupCtrlByPhone)
			{
				$fieldNames[] = 'FM.PHONE';
			}
			if($enableDupCtrlByEmail)
			{
				$fieldNames[] = 'FM.EMAIL';
			}

			$adapter = \Bitrix\Crm\EntityAdapterFactory::create($arCompany, CCrmOwnerType::Company);
			$dups = $dupChecker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams($fieldNames));

			$dupIDs = array();
			foreach($dups as &$dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				if(empty($dupIDs))
				{
					$dupIDs = $dup->getEntityIDsByType(CCrmOwnerType::Company);
				}
				else
				{
					$dupIDs = array_intersect($dupIDs, $dup->getEntityIDsByType(CCrmOwnerType::Company));
				}
			}
			unset($dup);

			if(!empty($dupIDs))
			{
				$isDuplicate = true;

				if($dupCtrlType !== 'SKIP')
				{
					$dupItems = array();
					$dbResult = CCrmCompany::GetListEx(array(), array('@ID' => $dupIDs, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('*', 'UF_*'));

					$loadMultiFields = !empty($mappedMultiFields);

					if(is_object($dbResult))
					{
						while($fields = $dbResult->Fetch())
						{
							if($loadMultiFields)
							{
								$fields['FM'] = array();
								$dbMultiFields = CCrmFieldMulti::GetList(
									array('ID' => 'asc'),
									array('ENTITY_ID' => CCrmOwnerType::CompanyName, 'ELEMENT_ID' => $fields['ID'])
								);
								while($multiFields = $dbMultiFields->Fetch())
								{
									$fields['FM'][$multiFields['TYPE_ID']][$multiFields['ID']] =
										array(
											'VALUE' => $multiFields['VALUE'],
											'VALUE_TYPE' => $multiFields['VALUE_TYPE']
										);
								}
							}
							$dupItems[] = &$fields;
							unset($fields);
						}
					}

					//Preparing multifieds
					$multiFieldValues = array();
					$multiFields = isset($arCompany['FM']) ? $arCompany['FM'] : array();
					if(!empty($multiFields))
					{
						foreach($mappedMultiFields as $type => &$valueTypes)
						{
							if(!isset($multiFields[$type]))
							{
								continue;
							}

							$multiFieldData = $multiFields[$type];
							if(empty($multiFieldData))
							{
								continue;
							}

							foreach($valueTypes as $valueType)
							{
								foreach($multiFieldData as $multiFieldItem)
								{
									$itemValueType = isset($multiFieldItem['VALUE_TYPE']) ? $multiFieldItem['VALUE_TYPE'] : '';
									$itemValue = isset($multiFieldItem['VALUE']) ? $multiFieldItem['VALUE'] : '';
									if($itemValueType === $valueType && $itemValue !== '')
									{
										if(!isset($multiFieldValues[$type]))
										{
											$multiFieldValues[$type] = array();
										}
										if(!isset($multiFieldValues[$type][$valueType]))
										{
											$multiFieldValues[$type][$valueType] = array();
										}
										$multiFieldValues[$type][$valueType][] = array(
											'VALUE' => $itemValue,
											'CODE' => \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::prepareCode($type, $itemValue)
										);
									}
								}
							}
						}
						unset($valueTypes);
					}

					$isRewrite = $dupCtrlType === 'REPLACE';
					foreach($dupItems as &$item)
					{
						foreach($mappedFields as $fieldName)
						{
							if($isRewrite)
							{
								if(isset($arCompany[$fieldName]))
								{
									$item[$fieldName] = $arCompany[$fieldName];
								}
							}
							else
							{
								if(isset($arCompany[$fieldName]) && !empty($arCompany[$fieldName]))
								{
									if($fieldName === 'COMMENTS')
									{
										// HACK: Ignore line break tags in HTML
										$comments = isset($item[$fieldName]) ? $item[$fieldName] : '';
										if($comments !== '')
										{
											$comments = trim(preg_replace('/<br[\/]?>/i', '', $comments));
										}
										if($comments === '')
										{
											$item['COMMENTS'] = $arCompany['COMMENTS'];
										}
									}
									elseif((!isset($item[$fieldName]) || empty($item[$fieldName])))
									{
										$item[$fieldName] = $arCompany[$fieldName];
									}
								}
							}
						}

						foreach($mappedMultiFields as $type => &$valueTypes)
						{
							if(!isset($multiFieldValues[$type]))
							{
								continue;
							}

							foreach($valueTypes as $valueType)
							{
								if(!isset($multiFieldValues[$type][$valueType]))
								{
									continue;
								}

								$values = $multiFieldValues[$type][$valueType];
								$valueCount = count($values);
								if($valueCount > 0)
								{
									if($isRewrite)
									{
										if(isset($item['FM'][$type]))
										{
											foreach($item['FM'][$type] as $k => $v)
											{
												if($v['VALUE_TYPE'] === $valueType)
												{
													//Mark item for delete
													unset($item['FM'][$type][$k]['VALUE']);
												}
											}
										}

										if(!isset($item['FM'][$type]))
										{
											$item['FM'][$type] = array();
										}

										for($i = 0; $i < $valueCount; $i++)
										{
											$item['FM'][$type]['n'.strval($i + 1)] = array(
												'VALUE_TYPE' => $valueType,
												'VALUE' => $values[$i]['VALUE']
											);
										}
									}
									else
									{
										if(isset($item['FM'][$type]) && !empty($item['FM'][$type]))
										{
											$valuesToAdd = array();
											foreach($values as &$value)
											{
												$code = $value['CODE'];
												$isFound = false;
												foreach($item['FM'][$type] as $k => &$v)
												{
													if($v['VALUE_TYPE'] === $valueType)
													{
														if($code === \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::prepareCode($type, $v['VALUE']))
														{
															$isFound = true;
															break;
														}
													}
												}
												unset($v);

												if(!$isFound)
												{
													$valuesToAdd[] = $value['VALUE'];
												}
											}
											unset($value);

											$valueToAddCount = count($valuesToAdd);
											if($valueToAddCount > 0)
											{
												for($i = 0; $i < $valueToAddCount; $i++)
												{
													$item['FM'][$type]['n'.strval($i + 1)] = array(
														'VALUE_TYPE' => $valueType,
														'VALUE' => $valuesToAdd[$i]
													);
												}
											}
										}
										else
										{
											if(!isset($item['FM'][$type]))
											{
												$item['FM'][$type] = array();
											}

											for($i = 0; $i < $valueCount; $i++)
											{
												$item['FM'][$type]['n'.strval($i + 1)] = array(
													'VALUE_TYPE' => $valueType,
													'VALUE' => $values[$i]['VALUE']
												);
											}
										}
									}
								}
							}
						}
						unset($valueTypes);
						$CCrmCompany->Update($item['ID'], $item);
					}
					unset($item);
				}
			}
		}

		if($isDuplicate)
		{
			$arResult['duplicate']++;
			if($enableDupFile)
			{
				__CrmImportWriteDataToFile(
					$duplicateFilePath,
					isset($_SESSION['CRM_IMPORT_FILE_HEADERS']) ? $_SESSION['CRM_IMPORT_FILE_HEADERS'] : null,
					$arData
				);
			}
		}
		else
		{
			$arCompany['PERMISSION'] = 'IMPORT';
			if (!$CCrmCompany->Add($arCompany))
			{
				$arResult['error']++;
				$arResult['error_data'][] = Array(
					'message' => $arCompany['RESULT_MESSAGE'],
					'data' => $arData
				);

				__CrmImportWriteDataToFile(
					$errataFilePath,
					isset($_SESSION['CRM_IMPORT_FILE_HEADERS']) ? $_SESSION['CRM_IMPORT_FILE_HEADERS'] : null,
					$arData
				);
			}
			else if (!empty($arCompany))
				$arResult['import']++;
		}

		$processedQty++;
		if($processedQty == 20)
		{
			break;
		}
	}
	$_SESSION['CRM_IMPORT_FILE_POS'] = $csvFile->GetPos();
	$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = false;
	$csvFile->CloseFile();

	if($arResult['error'] > 0)
	{
		$arResult['errata_url'] = SITE_DIR.'bitrix/components/bitrix/crm.company.import/show_file.php?name=errata';
	}

	if($enableDupFile && $arResult['duplicate'] > 0)
	{
		$arResult['duplicate_url'] = SITE_DIR.'bitrix/components/bitrix/crm.company.import/show_file.php?name=duplicate';
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}

$strError = '';
$arResult['STEP'] = isset($_POST['step'])? intval($_POST['step']): 1;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if (isset($_POST['next']))
	{
		if ($arResult['STEP'] == 1)
		{
			if ($_FILES['IMPORT_FILE']['error'] > 0)
				ShowError(GetMessage('CRM_CSV_NF_ERROR'));
			else
			{
				$error = CFile::CheckFile($_FILES['IMPORT_FILE'], 0, 0, 'csv,txt');
				if($error !== '')
				{
					ShowError($error);
				}
				else
				{
					if (isset($_SESSION['CRM_IMPORT_FILE']))
						unset($_SESSION['CRM_IMPORT_FILE']);

					$sTmpFilePath = CTempFile::GetDirectoryName(12, 'crm');
					CheckDirPath($sTmpFilePath);
					$_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] = isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'Y'? true: false;
					$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'Y'? true: false;
					$_SESSION['CRM_IMPORT_FILE'] = $sTmpFilePath.md5($_FILES['IMPORT_FILE']['tmp_name']).'.tmp';
					$_SESSION['CRM_IMPORT_FILE_POS'] = 0;
					move_uploaded_file($_FILES['IMPORT_FILE']['tmp_name'], $_SESSION['CRM_IMPORT_FILE']);
					@chmod($_SESSION['CRM_IMPORT_FILE'], BX_FILE_PERMISSIONS);

					if (isset($_POST['IMPORT_FILE_ENCODING']))
					{
						$fileEncoding = $_POST['IMPORT_FILE_ENCODING'];
						if($fileEncoding !== '' && $fileEncoding !== '_' && $fileEncoding !== strtolower(SITE_CHARSET))
						{
							$convertCharsetErrorMsg = '';
							$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'rb');
							$fileContents = fread($fileHandle, filesize($_SESSION['CRM_IMPORT_FILE']));
							fclose($fileHandle);

							//HACK: Remove UTF-8 BOM
							if($fileEncoding === 'utf-8' && substr($fileContents, 0, 3) === "\xEF\xBB\xBF")
							{
								$fileContents = substr($fileContents, 3);
							}

							$fileContents = CharsetConverter::ConvertCharset($fileContents, $fileEncoding, SITE_CHARSET, $convertCharsetErrorMsg);

							$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'wb');
							fwrite($fileHandle, $fileContents);
							fclose($fileHandle);
						}
					}

					$_SESSION['CRM_IMPORT_DEFAULT_RESPONSIBLE_ID'] = isset($_POST['IMPORT_DEFAULT_RESPONSIBLE_ID']) ? $_POST['IMPORT_DEFAULT_RESPONSIBLE_ID'] : '';
					$_SESSION['CRM_IMPORT_IMPORT_NAME_FORMAT'] = isset($_POST['IMPORT_NAME_FORMAT'])
						&& \Bitrix\Crm\Format\PersonNameFormatter::isDefined($_POST['IMPORT_NAME_FORMAT'])
							? intval($_POST['IMPORT_NAME_FORMAT'])
							: \Bitrix\Crm\Format\PersonNameFormatter::FirstLast;

					if ($_POST['IMPORT_FILE_SEPORATOR'] == 'semicolon')
						$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ';';
					elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'comma')
						$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ',';
					elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'tab')
						$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = "\t";
					elseif ($_POST['IMPORT_FILE_SEPORATOR'] == 'space')
						$_SESSION['CRM_IMPORT_FILE_SEPORATOR'] = ' ';

					$error = __CrmImportPrepareFieldBindingTab($arResult, $arRequireFields);
					if($error !== '')
					{
						ShowError($error);
					}
				}
				$arResult['STEP'] = 2;
			}
		}
		else if ($arResult['STEP'] == 2)
		{
			$mappedFields = array();
			$mappedMultiFields = array();

			foreach ($_POST as $key => $value)
			{
				if($value === null || $value === '' || strpos($key, 'IMPORT_FILE_FIELD_') === false)
				{
					continue;
				}

				$_SESSION['CRM_'.$key] = $value;

				$multiFieldName = CCrmFieldMulti::ParseComplexName($value, true);
				if(empty($multiFieldName))
				{
					$mappedFields[] = $value;
				}
				else
				{
					$multiFieldType = $multiFieldName['TYPE'];
					if(!isset($mappedMultiFields[$multiFieldType]))
					{
						$mappedMultiFields[$multiFieldType] = array();
					}
					$multiFieldValueType = $multiFieldName['VALUE_TYPE'];
					if(!in_array($multiFieldValueType, $mappedMultiFields[$multiFieldType], true))
					{
						$mappedMultiFields[$multiFieldType][] = $multiFieldValueType;
					}
				}
			}
			$_SESSION['CRM_IMPORT_MAPPED_FIELDS'] = $mappedFields;
			$_SESSION['CRM_IMPORT_MAPPED_MULTI_FIELDS'] = $mappedMultiFields;

			$error = __CrmImportPrepareDupControlTab($arResult);
			if($error !== '')
			{
				ShowError($error);
			}

			$arResult['STEP'] = 3;
		}
		else if ($arResult['STEP'] == 3)
		{
			$_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] = isset($_POST['IMPORT_DUP_CONTROL_TYPE']) ? $_POST['IMPORT_DUP_CONTROL_TYPE'] : '';
			if($_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] === '')
			{
				$_SESSION['CRM_IMPORT_DUP_CONTROL_TYPE'] = 'NO_CONTROL';
			}

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_ORGANIZATION'] == 'Y' ? true: false;

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_PHONE'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_PHONE'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_PHONE'] == 'Y' ? true: false;

			$_SESSION['CRM_IMPORT_DUP_CONTROL_ENABLE_EMAIL'] =
				isset($_POST['IMPORT_DUP_CONTROL_ENABLE_EMAIL'])
				&& $_POST['IMPORT_DUP_CONTROL_ENABLE_EMAIL'] == 'Y' ? true: false;

			//CLEAR ERRATA BEFORE IMPORT START -->
			$tempDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
			if($tempDir !== '')
			{
				@unlink("{$tempDir}errata.csv");
				@unlink("{$tempDir}duplicate.csv");
				@rmdir($tempDir);
				unset($_SESSION['CRM_IMPORT_TEMP_DIR']);
			}
			//<-- CLEAR ERRATA BEFORE IMPORT START

			$arResult['FIELDS']['tab_4'] = array();

			ob_start();
			?>
				<div class="crm_import_entity"><?=GetMessage('CRM_IMPORT_FINISH')?>: <span id="crm_import_entity">0</span> <span id="crm_import_entity_progress"><img src="/bitrix/components/bitrix/crm.company.import/templates/.default/images/wait.gif" align="absmiddle"></span></div>
				<div id="crm_import_duplicate" class="crm_import_entity"><?=GetMessage('CRM_PROCESSED_DUPLICATES')?>: <span id="crm_import_entity_duplicate">0</span></div>
				<div id="crm_import_error" class="crm_import_error"><?=GetMessage('CRM_IMPORT_ERROR')?>: <span id="crm_import_entity_error">0</span></div>
				<div id="crm_import_errata" class="crm_import_error"><a id="crm_import_entity_errata" href="#"><?=GetMessage('CRM_IMPORT_ERRATA')?></a></div>
				<div id="crm_import_duplicate_file_wrapper" class="crm_import_duplicate_file"><a id="crm_import_duplicate_file_url" href="#"><?=GetMessage('CRM_IMPORT_DUPLICATE_URL')?></a></div>
				<div id="crm_import_example" class="crm_import_example" style="display:none">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table" id="crm_import_example_table">
						<tbody id="crm_import_example_table_body">
						<tr>
							<?foreach ($_SESSION['CRM_IMPORT_FILE_HEADERS'] as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						</tbody>
					</table>
				</div>
				<script type="text/javascript">
					windowSizes = BX.GetWindowSize(document);
					BX('crm_import_example').style.height = "44px";
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
					crmImportAjax('<?=$APPLICATION->GetCurPage()?>?import');
				</script>
			<?
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_4'][] = array(
				'id' => 'IMPORT_FINISH',
				'name' => "",
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
			$arResult['STEP'] = 4;
		}
		else if ($arResult['STEP'] == 4)
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
				if(strpos($key, 'CRM_IMPORT_FILE') !== false)
					unset($_SESSION[$key]);

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array()));
		}
		else
			$arResult['STEP'] = 1;
	}
	else if (isset($_POST['previous']))
	{
		if ($arResult['STEP'] === 3)
		{
			$error = __CrmImportPrepareFieldBindingTab($arResult, $arRequireFields);
			if($error !== '')
			{
				ShowError($error);
			}

			$arResult['STEP'] = 2;
		}
		else
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
				if(strpos($key, 'CRM_IMPORT_FILE') !== false)
					unset($_SESSION[$key]);

			$arResult['STEP'] = 1;
		}
	}
	else if (isset($_POST['cancel']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array()));
	}
}

$arResult['FORM_ID'] = 'CRM_COMPANY_IMPORT';

$arResult['FIELDS']['tab_1'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE'),
	'params' => array(),
	'type' => 'file',
	'required' => true
);

$encodings = array(
	'_' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_FILE_ENCODING'),
	'ascii' => 'ASCII',
	'UTF-8' => 'UTF-8',
	'UTF-16' => 'UTF-16',
	'windows-1251' => 'Windows-1251',
	'Windows-1252' => 'Windows-1252',
	'iso-8859-1' => 'ISO-8859-1',
	'iso-8859-2' => 'ISO-8859-2',
	'iso-8859-3' => 'ISO-8859-3',
	'iso-8859-4' => 'ISO-8859-4',
	'iso-8859-5' => 'ISO-8859-5',
	'iso-8859-6' => 'ISO-8859-6',
	'iso-8859-7' => 'ISO-8859-7',
	'iso-8859-8' => 'ISO-8859-8',
	'iso-8859-9' => 'ISO-8859-9',
	'iso-8859-10' => 'ISO-8859-10',
	'iso-8859-13' => 'ISO-8859-13',
	'iso-8859-14' => 'ISO-8859-14',
	'iso-8859-15' => 'ISO-8859-15',
	'koi8-r' => 'KOI8-R'
);

$siteEncoding = strtolower(SITE_CHARSET);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_ENCODING',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_ENCODING'),
	'items' => $encodings,
	'type' => 'list',
	'value' => isset($encodings[$siteEncoding]) ? $siteEncoding : '_'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_DEFAULT_RESPONSIBLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_DEFAULT_RESPONSIBLE'),
	'type' => 'intranet_user_search',
	'componentParams' => array(
		'NAME' => 'crm_company_import_responsible',
		'INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_ID',
		'SEARCH_INPUT_NAME' => 'IMPORT_DEFAULT_RESPONSIBLE_NAME'
	),
	'value' => CCrmPerms::GetCurrentUserID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_NAME_FORMAT',
	'name' => GetMessage('CRM_FIELD_NAME_FORMAT'),
	'items' => $userNameFormats,
	'type' => 'list',
	'value' => \Bitrix\Crm\Format\PersonNameFormatter::FirstLast
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_EXAMPLE',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_EXAMPLE'),
	'params' => array(),
	'type' => 'label',
	'value' => '<a href="?getSample=csv&ncc=1">'.GetMessage('CRM_DOWNLOAD').'</a>'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FORMAT',
	'name' => GetMessage('CRM_SECTION_IMPORT_FILE_FORMAT'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SEPORATOR',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR'),
	'items' => Array(
		'semicolon' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SEMICOLON'),
		'comma' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_COMMA'),
		'tab' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_TAB'),
		'space' => GetMessage('CRM_FIELD_IMPORT_FILE_SEPORATOR_SPACE'),
	),
	'type' => 'list',
	'value' => isset($_POST['IMPORT_FILE_SEPORATOR'])? $_POST['IMPORT_FILE_SEPORATOR']: 'semicolon'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FIRST_HEADER',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_FIRST_HEADER'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'N'? 'N': 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SKIP_EMPTY',
	'name' => GetMessage('CRM_FIELD_IMPORT_FILE_SKIP_EMPTY'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'N'? 'N': 'Y'
);

for ($i = 1; $i <= 4; $i++):
	if ($arResult['STEP'] != $i)
		$arResult['FIELDS']['tab_'.$i] = array();
endfor;

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

?>
