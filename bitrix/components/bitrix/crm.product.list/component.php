<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $DB, $APPLICATION;

if (!CCrmSecurityHelper::IsAuthorized())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/** @var $CrmPerms CCrmPerms */
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAccessEnabled($CrmPerms) && $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $arResult['CAN_ADD_SECTION'] = $arResult['CAN_EDIT_SECTION'] =
	$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], $APPLICATION->GetCurPage().'?section_id=#section_id#');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);

// prepare URI template
$curParam = $APPLICATION->GetCurParam();
$curParam = preg_replace('/(^|[^\w])bxajaxid=[\d\w]*([^\d\w]|$)/', '', $curParam);
$curParam = preg_replace('/(?<!\w)list_section_id=\d*(?=([^\d]|$))/', 'list_section_id=#section_id#', $curParam);
$curParam = preg_replace('/(^|&)tree=\w*(?=(&|$))/', '', $curParam);
$arResult['PAGE_URI_TEMPLATE'] = $arParams['PATH_TO_PRODUCT_LIST'].(strlen($curParam) > 0 ? '?'.$curParam.'&tree=Y' : '?tree=Y');
unset($curParam);

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$bVatMode = $arResult['VAT_MODE'] = CCrmTax::isVatMode();

$arResult['VAT_RATE_LIST_ITEMS'] = array();
if ($bVatMode)
	$arResult['VAT_RATE_LIST_ITEMS'] = CCrmVat::GetVatRatesListItems();

// measure list items
$arResult['MEASURE_LIST_ITEMS'] = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
$measures = \Bitrix\Crm\Measure::getMeasures(100);
if (is_array($measures))
{
	foreach ($measures as $measure)
		$arResult['MEASURE_LIST_ITEMS'][$measure['ID']] = $measure['SYMBOL'];
	unset($measure);
}
unset($measures);

if (isset($arResult['PRODUCT_ID']))
{
	unset($arResult['PRODUCT_ID']);
}

if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
{
	$bInternal = true;
}

$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? $this->GetParent()->GetName() : '';
	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
	$arSort = $arParams['INTERNAL_SORT'];
}

if (!isset($arParams['PRODUCT_COUNT']))
{
	$arParams['PRODUCT_COUNT'] = 20;
}

$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST'.($bInternal ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['FILTER'] = $arResult['FILTER2LOGIC'] = $arResult['FILTER_PRESETS'] = array();

$catalogID = isset($arParams['~CATALOG_ID']) ? intval($arParams['~CATALOG_ID']) : 0;
if ($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}

$arResult['SECTION_LIST'] = array();

//$arCatalogs = array();
//$arCatalogs[''] = GetMessage('CRM_NOT_SELECTED');
//$obRes = CCrmCatalog::GetList(array('NAME'), array(), false, false, array('ID', 'NAME'));
//while($arCatalog = $obRes->GetNext())
//{
//	$arCatalogs[$arCatalog['ID']] = $arCatalog['NAME'];
//}

$arSections = array();
$arSections[''] = GetMessage('CRM_PRODUCT_LIST_FILTER_SECTION_ALL');
$arSections['0'] = GetMessage('CRM_PRODUCT_LIST_FILTER_SECTION_ROOT');
$rsSections = CIBlockSection::GetList(
	array('left_margin' => 'asc'),
	array(
		'IBLOCK_ID' => $catalogID,
		'GLOBAL_ACTIVE' => 'Y',
		'CHECK_PERMISSIONS' => 'N'
	)
);

while($arSection = $rsSections->GetNext())
{
	$arResult['SECTION_LIST'][$arSection['ID']] =
		array(
			'ID' => $arSection['ID'],
			'NAME' => $arSection['~NAME'],
			'LIST_URL' => str_replace(
				'#section_id#',
				$arSection['ID'],
				$arResult['PAGE_URI_TEMPLATE']
			)
		);

	$arSections[$arSection['ID']] = str_repeat(' . ', $arSection['DEPTH_LEVEL']).$arSection['~NAME'];
}

$arResult['FILTER'] =
	array(
		array(
			'id' => 'ID',
			'name' => GetMessage('CRM_COLUMN_ID'),
			'type' => 'string',
			'default' => true
		),
		array(
			'id' => 'NAME',
			'name' => GetMessage('CRM_COLUMN_NAME'),
			'type' => 'string',
			'default' => true
		),
// Catalog ID is not supported - section list can not be changed
//		array(
//			'id' => 'CATALOG_ID',
//			'name' => GetMessage('CRM_COLUMN_CATALOG_ID'),
//			'type' => 'list',
//			'items' => $arCatalogs
//		),
		array(
			'id' => 'LIST_SECTION_ID',
			'name' => GetMessage('CRM_COLUMN_SECTION'),
			'type' => 'list',
			'default' => true,
			'items' => $arSections,
			'value' => '0'/*,
			'filtered' => $sectionID > 0*/
		),
		array(
			'id' => 'ACTIVE',
			'name' => GetMessage('CRM_COLUMN_ACTIVE'),
			'type' => 'list',
			'items' => array(
				'' => GetMessage('CRM_NOT_SELECTED'),
				'Y' => GetMessage('MAIN_YES'),
				'N' => GetMessage('MAIN_NO')
			)
		),
		array(
			'id' => 'DESCRIPTION',
			'name' => GetMessage('CRM_COLUMN_DESCRIPTION')
		)
	);
	$arResult['FILTER_PRESETS'] = array();
//}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => true, 'editable' => true, 'params' => array('size' => 45)),
	array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'),/* 'sort' => 'price',*/ 'default' => true, 'editable' => true),
	array('id' => 'MEASURE', 'name' => GetMessage('CRM_COLUMN_MEASURE'),/* 'sort' => 'price',*/ 'default' => true, 'editable' => array('items' => $arResult['MEASURE_LIST_ITEMS']), 'type' => 'list')
);
if ($bVatMode)
{
	$arResult['HEADERS'][] = array('id' => 'VAT_ID', 'name' => GetMessage('CRM_COLUMN_VAT_ID'),/* 'sort' => 'price',*/ 'default' => true, 'editable' => array('items' => $arResult['VAT_RATE_LIST_ITEMS'], 'type' => 'list'));
	$arResult['HEADERS'][] = array('id' => 'VAT_INCLUDED', 'name' => GetMessage('CRM_COLUMN_VAT_INCLUDED'),/* 'sort' => 'price',*/ 'default' => true, 'editable' => true, 'type' => 'checkbox');
}
$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'SECTION_ID', 'name' => GetMessage('CRM_COLUMN_SECTION'), 'default' => true, 'editable' => array('items'=> CCrmProductHelper::PrepareSectionListItems($catalogID, true)), 'type' => 'list'),
		array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'sort', 'default' => false, 'editable' => true),
		array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'active', 'default' => false, 'editable' => true, 'type' => 'checkbox'),
		array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_DESCRIPTION'), 'sort' => 'description', 'default' => true, 'editable' => true),
		array('id' => 'PREVIEW_PICTURE', 'name' => GetMessage('CRM_PRODUCT_FIELD_PREVIEW_PICTURE'), 'sort' => 'preview_picture', 'default' => false, 'editable' => false),
		array('id' => 'DETAIL_PICTURE', 'name' => GetMessage('CRM_PRODUCT_FIELD_DETAIL_PICTURE'), 'sort' => 'detail_picture', 'default' => false, 'editable' => false),
	)
);
// <-- Headers initialization

// Product properties
// <editor-fold defaultstate="collapsed" desc="Product properties">
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, array('view', 'filter'));
$arResult['PROP_USER_TYPES'] = $arPropUserTypeList;
$arProps = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList);
$arResult['PROPS'] = $arProps;
$arFilterable = array();
$arCustomFilter = array();
$arDateFilter = array();
CCrmProductPropsHelper::ListAddFilterFields($arPropUserTypeList, $arProps, $arResult['GRID_ID'],
	$arResult['FILTER'], $arFilterable, $arCustomFilter, $arDateFilter);
CCrmProductPropsHelper::ListAddHeades($arPropUserTypeList, $arProps, $arResult['HEADERS']);
// </editor-fold>

$bTree = false;
// check hit from section tree
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_REQUEST['tree']))
{
	$bTree = ($_REQUEST['tree'] === 'Y');
	unset($_GET['tree'], $_REQUEST['tree']);
}

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if (check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	if ($actionData['METHOD'] == 'POST')
	{
		if (isset($_POST[$postAction]))
		{
			$actionData['ACTIVE'] = true;

			$actionData['NAME'] = $_POST[$postAction];
			unset($_POST[$postAction], $_REQUEST[$postAction]);

			$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
			$actionData['ALL_ROWS'] = false;
			if (isset($_POST[$allRows]))
			{
				$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
				unset($_POST[$allRows], $_REQUEST[$allRows]);
			}

			if (isset($_POST['ID']))
			{
				$actionData['ID'] = $_POST['ID'];
				unset($_POST['ID'], $_REQUEST['ID']);
			}

			if (isset($_POST['FIELDS']))
			{
				$actionData['FIELDS'] = $_POST['FIELDS'];
				unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
			}

			$actionData['AJAX_CALL'] = false;
			if (isset($_POST['AJAX_CALL']))
			{
				$actionData['AJAX_CALL']  = true;
				// Must be transfered to main.interface.grid
				//unset($_POST['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
			}
		}
		else if (isset($_POST['action']))
		{
			$actionData['ACTIVE'] = true;
			$actionData['NAME'] = $_POST['action'];
			unset($_POST['action'], $_REQUEST['action']);
			
			if ($actionData['NAME'] === 'ADD_SECTION')
			{
				$actionData['SECTION_NAME'] = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
				unset($_POST['sectionName'], $_REQUEST['sectionName']);
			}
			else if ($actionData['NAME'] === 'RENAME_SECTION')
			{
				$actionData['RENAMED_SECTION_ID'] = isset($_POST['sectionID']) ? intval($_POST['sectionID']) : 0;
				$actionData['NEW_SECTION_NAME'] = trim(isset($_POST['sectionName']) ? $_POST['sectionName'] : '', " \n\r\t");
				unset($_POST['sectionID'], $_REQUEST['sectionID'], $_POST['sectionName'], $_REQUEST['sectionName']);
			}
		}
	}
	else if ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if (isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if (isset($_GET['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_GET['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
}
// <-- Try to extract user action data

$arNavParams = array(
	'nPageSize' => $arParams['PRODUCT_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arParams['PRODUCT_COUNT']);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter = $gridFilter = $CGridOptions->GetFilter($arResult['FILTER']);
$arFilter['CATALOG_ID'] = $catalogID;

$sectionID = isset($arParams['~SECTION_ID']) ? intval($arParams['~SECTION_ID']) : 0;

$bFilterSection = (
	$bTree
	|| !isset($arFilter['GRID_FILTER_APPLIED'])
	|| !$arFilter['GRID_FILTER_APPLIED']
	|| (isset($arFilter['LIST_SECTION_ID']) && $arFilter['LIST_SECTION_ID'] !== '')
);
if ($bFilterSection)
{
	if (!$bTree
		&& isset($arFilter['GRID_FILTER_APPLIED'])
		&& $arFilter['GRID_FILTER_APPLIED']
		&& isset($arFilter['LIST_SECTION_ID']))
	{
		$sectionID = intval($arFilter['LIST_SECTION_ID']);
	}
	$arFilter['SECTION_ID'] = $sectionID;
}
// reset section filter HACK
if (!is_array($_SESSION['main.interface.grid']))
	$_SESSION['main.interface.grid'] = array();
if (!is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]))
	$_SESSION['main.interface.grid'][$arResult['GRID_ID']] = array();
if (!is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']))
	$_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter'] = array();
if (is_array($_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']))
	$_SESSION['main.interface.grid'][$arResult['GRID_ID']]['filter']['LIST_SECTION_ID'] = $bFilterSection ? strval($sectionID) : '';
if (!isset($arFilter['GRID_FILTER_APPLIED']) || !$arFilter['GRID_FILTER_APPLIED'])
	$_REQUEST['LIST_SECTION_ID'] = $_GET['LIST_SECTION_ID'] = $bFilterSection ? strval($sectionID) : '';

$arImmutableFilters = array('ID', 'SECTION_ID', 'LIST_SECTION_ID', 'CATALOG_ID', 'ACTIVE', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID');
foreach ($arFilter as $k => $v)
{
	if (in_array($k, $arImmutableFilters, true) || preg_match('/^PROPERTY_\d+(_from|_to)*$/', $k))
	{
		continue;
	}

	if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if ($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k != 'LOGIC')
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}
foreach($gridFilter as $key => $value)
{
	if (substr($key, -5) == "_from")
	{
		$op = ">=";
		$new_key = substr($key, 0, -5);
	}
	else if (substr($key, -3) == "_to")
	{
		$op = "<=";
		$new_key = substr($key, 0, -3);
		if (array_key_exists($new_key, $arDateFilter))
		{
			if (!preg_match("/\\d\\d:\\d\\d:\\d\\d\$/", $value))
				$value .= " 23:59:59";
		}
	}
	else
	{
		$op = "";
		$new_key = $key;
	}

	if (array_key_exists($new_key, $arFilterable))
	{
		if ($op == "")
			$op = $arFilterable[$new_key];
		$arFilter[$op.$new_key] = $value;
		if ($op.$new_key !== $key)
			unset($arFilter[$key]);
	}
}
unset($gridFilter);
$arFilter['~REAL_PRICE'] = true;

foreach($arCustomFilter as $propID => $arCallback)
{
	$filtered = false;
	call_user_func_array($arCallback["callback"], array(
		$arProps[$propID],
		array(
			"VALUE" => $propID,
			"GRID_ID" => $arResult["GRID_ID"],
		),
		&$arFilter,
		&$filtered,
	));
}

//Show error message if required
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = strtolower($_GET['error']);
	if (preg_match('/^crm_err_/', $errorID) === 1)
	{
		if (!isset($_SESSION[$errorID]))
		{
			LocalRedirect(CHTTP::urlDeleteParams($APPLICATION->GetCurPage(), array('error')));
		}

		$errorMessage = strval($_SESSION[$errorID]);
		unset($_SESSION[$errorID]);
		if ($errorMessage !== '')
		{
			ShowError(htmlspecialcharsbx($errorMessage));
		}
	}
}

// FILTERED SECTIONS
$arSectionFilter = array (
	'CHECK_PERMISSIONS' => 'N'
);
$arFiltrableFieldMap = array(
	'ID' => 'ID',
	'CATALOG_ID' => 'IBLOCK_ID',
	'SECTION_ID' => 'SECTION_ID',
	'NAME' => 'NAME',
	'XML_ID' => 'EXTERNAL_ID'
);
$arFiltrableField = array_keys($arFiltrableFieldMap);
$arIgnoreFilters = array('LIST_SECTION_ID', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID', '~REAL_PRICE');
$bSkipSections = false;
foreach($arFilter as $k => $v)
{
	$matches = array();
	if (preg_match('/^([!><=%?][><=%]?[<]?|)(\w+)$/', $k, $matches))
	{
		if (isset($matches[2]) && strlen($matches[2]) > 0)
		{
			if (in_array($matches[2], $arFiltrableField, true))
				$arSectionFilter[$matches[1].$arFiltrableFieldMap[$matches[2]]] = $v;
			else if (!in_array($matches[2], $arIgnoreFilters, true))
				$bSkipSections = true;
		}
	}
}
unset($arIgnoreFilters, $arFiltrableFieldMap, $arFiltrableField, $fieldSection, $fieldIblock, $k, $v, $matches);

// POST & GET actions processing -->
if ($actionData['ACTIVE'])
{
	$errorMessage = '';
	if ($actionData['METHOD'] == 'POST')
	{
		if ($actionData['NAME'] == 'delete' && $arResult['CAN_DELETE'])
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDelSection = array();
				$arFilterDelProduct = array();
				if (!$actionData['ALL_ROWS'])
				{
					// split by type
					$arSectionId = $arProductId = array();
					foreach ($actionData['ID'] as $sId)
					{
						if (is_string($sId) && strlen($sId) > 2)
						{
							if ($sId[0] === 'P')
								$arProductId[] = intval(substr($sId, 1));
							else if ($sId[0] === 'S')
								$arSectionId[] = intval(substr($sId, 1));
						}
					}
					if (!empty($arSectionId))
						$arFilterDelSection = array('ID' => $arSectionId, 'CHECK_PERMISSIONS' => 'N');
					if (!empty($arProductId))
						$arFilterDelProduct = array('ID' => $arProductId);
					unset($arSectionId, $arProductId, $sId);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDelSection = $arSectionFilter;
					$arFilterDelProduct = $arFilter;
				}

				// DELETE SECTIONS -->
				if (!empty($arFilterDelSection))
				{
					$dbSection = CIBlockSection::GetList(
						array(),
						$arFilterDelSection,
						false,
						array('ID')
					);
					while($arSection = $dbSection->Fetch())
					{
						if (CCrmProductSection::Delete($arSection['ID'])
							|| CCrmProductSection::GetLastErrorCode() === CCrmProductSection::ERR_SECTION_NOT_FOUND)
						{
							continue;
						}

						if ($errorMessage !== '')
						{
							$errorMessage .= '<br />';
						}

						$errorMessage .= CCrmProductSection::GetLastError();
						break;
					}
					unset($dbSection);
				}
				// DELETE SECTIONS <--

				// DELETE PRODUCTS -->
				if (!empty($arFilterDelProduct))
				{
					$obRes = CCrmProduct::GetList(array(), $arFilterDelProduct, array('ID'));
					//$isInTransaction = false;
					while($arProduct = $obRes->Fetch())
					{
						/*if (!$isInTransaction)
						{
							$DB->StartTransaction();
							$isInTransaction = true;
						}*/

						if (CCrmProduct::Delete($arProduct['ID']))
						{
							continue;
						}

						if ($errorMessage !== '')
						{
							$errorMessage .= '<br />';
						}

						$errorMessage .= CCrmProduct::GetLastError();
						break;
					}
				}
				// DELETE PRODUCTS <--

				/*if ($isInTransaction)
				{
					if ($errorMessage === '')
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}*/
			}
		}
		else if ($actionData['NAME'] == 'edit' && $arResult['CAN_EDIT'])
		{
			if (isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$type = substr($ID, 0, 1);
					$ID = intval(substr($ID, 1));
					if ($type === 'S')
					{
						$arUpdateData = array();
						reset($arResult['HEADERS']);
						foreach ($arResult['HEADERS'] as $arHead)
						{
							if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
							{
								$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
							}
						}
						if (!empty($arUpdateData))
						{
							$DB->StartTransaction();
							if (CCrmProductSection::Update($ID, $arUpdateData))
							{
								$DB->Commit();
							}
							else
							{
								if ($errorMessage !== '')
								{
									$errorMessage.= '<br />';
								}
								$errorMessage .= CCrmProduct::GetLastError();
							}
						}
					}
					else
					{
						$arUpdateData = array();
						reset($arResult['HEADERS']);
						foreach ($arResult['HEADERS'] as $arHead)
						{
							if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
							{
								$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
							}
						}
						if (!empty($arUpdateData))
						{
							$DB->StartTransaction();
							if (CCrmProduct::Update($ID, $arUpdateData))
							{
								$DB->Commit();
							}
							else
							{
								if ($errorMessage !== '')
								{
									$errorMessage.= '<br />';
								}
								$errorMessage .= CCrmProduct::GetLastError();
							}
						}
					}
				}
			}
		}

		if (strlen($errorMessage) > 0)
		{
			if (!$actionData['AJAX_CALL'])
			{
				$errorID = uniqid('crm_err_');
				$_SESSION[$errorID] = $errorMessage;
				LocalRedirect(CHTTP::urlAddParams($APPLICATION->GetCurPage(), array('error' => $errorID)));
			}
			else
			{
				ShowError(htmlspecialcharsbx($errorMessage));
			}
		}
		else if($actionData['NAME'] === 'ADD_SECTION' && $arResult['CAN_ADD_SECTION'])
		{
			$sectionName = $actionData['SECTION_NAME'];
			if(isset($sectionName[0]))
			{
				$section = new CIBlockSection();
				$section->Add(
					array(
						'IBLOCK_ID' => $catalogID,
						'NAME' => $sectionName,
						'IBLOCK_SECTION_ID' => $sectionID,
						'CHECK_PERMISSIONS' => 'N',
					)
				);
			}
		}
		elseif($actionData['NAME'] === 'RENAME_SECTION' && $arResult['CAN_EDIT_SECTION'])
		{
			$renamedSectionID = $actionData['RENAMED_SECTION_ID'];
			$newSectionName = $actionData['NEW_SECTION_NAME'];
			if($renamedSectionID > 0 && isset($newSectionName[0]))
			{
				$rsSections = CIBlockSection::GetList(
					array(),
					array(
						'IBLOCK_ID' => $catalogID,
						'ID' => $renamedSectionID,
						'GLOBAL_ACTIVE' => 'Y',
						'CHECK_PERMISSIONS' => 'N'
					)
				);
				if($rsSections->Fetch())
				{
					$section = new CIBlockSection();
					$section->Update(
						$renamedSectionID,
						array(
							'IBLOCK_ID' => $catalogID,
							'NAME' => $newSectionName,
						)
					);
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($APPLICATION->GetCurPage());
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		$errorMessage = '';
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']) && $arResult['CAN_DELETE'])
		{

			$sId = $actionData['ID'];
			$elementType = '';
			if (is_string($sId) && strlen($sId) > 2)
			{
				$elementType = $sId[0];
				$ID = intval(substr($sId, 1));

				$DB->StartTransaction();
				$result = true;
				if ($elementType === 'P')
					$result = CCrmProduct::Delete($ID);
				else if ($elementType === 'S')
					$result = (CCrmProductSection::Delete($ID)
						|| CCrmProductSection::GetLastErrorCode() === CCrmProductSection::ERR_SECTION_NOT_FOUND);
				if ($result)
				{
					$DB->Commit();
				}
				else
				{
					if ($errorMessage !== '')
					{
						$errorMessage.= '<br />';
					}

					if ($elementType === 'P')
						$errorMessage .= CCrmProduct::GetLastError();
					else if ($elementType === 'S')
						$errorMessage .= CCrmProductSection::GetLastError();

					$DB->Rollback();
				}
			}
		}

		if (strlen($errorMessage) > 0)
		{
			$errorID = uniqid('crm_err_');
			$_SESSION[$errorID] = $errorMessage;
			LocalRedirect(CHTTP::urlAddParams($APPLICATION->GetCurPage(), array('error' => $errorID)));
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect(
				$bInternal
					? ('?'.$arParams['FORM_ID'].'_active_tab=tab_product')
					: CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_PRODUCT_LIST'], array('section_id' => $sectionID)
					)
			);
		}
	}
}
// <-- POST & GET actions processing

$_arSort = $CGridOptions->GetSorting(
	array(
		'sort' => array('name' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arSelect = $arProperties = array();
$arGridSelect = $CGridOptions->GetVisibleColumns();
if (empty($arGridSelect))
{
	$arGridSelect = array();
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arGridSelect[] = $arHeader['id'];
		}
	}
}
foreach ($arGridSelect as $fieldName)
{
	if (preg_match('/^PROPERTY_\d+$/', $fieldName))
		$arProperties[] = $fieldName;
	else
		$arSelect[] = $fieldName;
}
unset($arGridSelect);

// ID must present in select
if (!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

//SECTION_ID must present in select
if (!in_array('SECTION_ID', $arSelect))
{
	$arSelect[] = 'SECTION_ID';
}

//PREVIEW_PICTURE must present in select
if (!in_array('PREVIEW_PICTURE', $arSelect))
{
	$arSelect[] = 'PREVIEW_PICTURE';
}

// Force select currency ID if price selected
if (in_array('PRICE', $arSelect) && !in_array('CURRENCY_ID', $arSelect))
{
	$arSelect[] = 'CURRENCY_ID';
}

$arResult['SELECTED_HEADERS'] = $arSelect;

// SECTIONS -->
$arResultData = array();
if (!$bSkipSections)
{
	$obSection = new CIBlockSection;
	$rsSection = $obSection->GetList($arResult['SORT'], $arSectionFilter, false, $arSelect);
	while($arSectionRow = $rsSection->Fetch())
	{
		$arSectionRow['TYPE'] = 'S';
		$arResultData[] = $arSectionRow;
	}
	unset($obSection, $rsSection, $arSectionRow);
}
unset($arSectionFilter);
// SECTIONS <--

// PRODUCTS -->
$arPricesSelect = $arVatsSelect = array();
$arSelect = CCrmProduct::DistributeProductSelect($arSelect, $arPricesSelect, $arVatsSelect);
$rsProduct = CCrmProduct::GetList($arResult['SORT'], $arFilter, $arSelect);
while($arProductRow = $rsProduct->Fetch())
{
	$arProductRow['TYPE'] = 'P';
	$arResultData[] = $arProductRow;
}
unset($rsProduct, $arProductRow);
//$obRes = CCrmProduct::GetList($arResult['SORT'], $arFilter, $arSelect, $arNavParams);
$obRes = new CDBResult;
$obRes->InitFromArray($arResultData);
$obRes->NavStart($arNavParams);
$arResult['PRODUCTS'] = array();
//$arResult['PRODUCT_ID_ARY'] = array();
$arResult['PERMS']['ADD']    = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE');
$arProductId = array();
$arPropertyValues = array();
while($arElement = $obRes->GetNext())
{
	if ($arElement['TYPE'] === 'S')
	{
		$arElement['DELETE'] = $arElement['EDIT'] = true;
		$arResult['SECTIONS'][$arElement['ID']] = $arElement;
	}
	else if ($arElement['TYPE'] === 'P')
	{
		//$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'WRITE', $arContactAttr[$arElement['ID']])
		//$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'DELETE', $arContactAttr[$arElement['ID']])

		$arElement['DELETE'] = $arElement['EDIT'] = true;

		$arElement['PATH_TO_PRODUCT_SHOW'] =
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_SHOW'],
				array('product_id' => $arElement['ID'])
			);

		$arElement['PATH_TO_PRODUCT_EDIT'] =
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_EDIT'],
				array('product_id' => $arElement['ID'])
			);

		$arElement['PATH_TO_PRODUCT_DELETE'] =
			CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					//array('section_id' => isset($arElement['SECTION_ID']) ? $arElement['SECTION_ID'] : '0')
					array('section_id' => $sectionID)
				),
				array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arElement['TYPE'].$arElement['ID'], 'sessid' => bitrix_sessid())
			);

		foreach ($arPricesSelect as $fieldName)
			$arElement['~'.$fieldName] = $arElement[$fieldName] = null;
		foreach ($arVatsSelect as $fieldName)
			$arElement['~'.$fieldName] = $arElement[$fieldName] = null;
		$arProductId[] = $arElement['ID'];

		$arResult['PRODUCTS'][$arElement['ID']] = $arElement;
		//$arResult['PRODUCT_ID_ARY'][$arElement['ID']] = $arElement['ID'];

		// Product properties
		$rsProperties = CIBlockElement::GetProperty(
			$catalogID,
			$arElement['ID'],
			array(
				'sort' => 'asc',
				'id' => 'asc',
				'enum_sort' => 'asc',
				'value_id' => 'asc',
			),
			array(
				'ACTIVE' => 'Y',
				'EMPTY' => 'N',
				'CHECK_PERMISSIONS' => 'N'
			)
		);
		while ($arProperty = $rsProperties->Fetch())
		{
			if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
				&& !array_key_exists($arProperty['USER_TYPE'], $arPropUserTypeList))
				continue;

			$propID = 'PROPERTY_' . $arProperty['ID'];
			if (!isset($arPropertyValues[$arElement['ID']][$propID]))
				$arPropertyValues[$arElement['ID']][$propID] = array();

			if (isset($arProperty['USER_TYPE']) && !empty($arProperty['USER_TYPE'])
				&& is_array($arPropUserTypeList[$arProperty['USER_TYPE']])
				&& array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProperty['USER_TYPE']]))
			{
				$arPropertyValues[$arElement['ID']][$propID][] = call_user_func_array($arPropUserTypeList[$arProperty['USER_TYPE']]["GetPublicViewHTML"], array(
					$arProps[$propID],
					array("VALUE" => $arProperty["VALUE"]),
					array(),
				));
			}
			else if ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arPropertyValues[$arElement['ID']][$propID][] = htmlspecialcharsex($arProperty["VALUE_ENUM"]);
			}
			else
			{
				$arPropertyValues[$arElement['ID']][$propID][] = htmlspecialcharsex($arProperty["VALUE"]);
			}
		}
		unset($rsProperties, $arProperty, $propID);
	}
}
$arResult['PROPERTY_VALUES'] = $arPropertyValues;
unset($arPropertyValues);
CCrmProduct::ObtainPricesVats($arResult['PRODUCTS'], $arProductId, $arPricesSelect, $arVatsSelect,
	(isset($arFilter['~REAL_PRICE']) && $arFilter['~REAL_PRICE'] === true));
$productMeasureInfos = \Bitrix\Crm\Measure::getProductMeasures($arProductId);
if (!is_array($productMeasureInfos))
	$productMeasureInfos = array();
$arResult['PRODUCT_MEASURE_INFOS'] = $productMeasureInfos;
// <-- PRODUCTS
$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['NAV_OBJECT'] = $obRes;
$arResult['BACK_URL_SECTION_ID'] = $bFilterSection ? $sectionID : '';

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');

$result = array(
	'ROWS_COUNT' => $arResult['ROWS_COUNT'],
);
if ($bFilterSection)
{
	$result['SECTION_ID'] = $sectionID;
}

return $result;
