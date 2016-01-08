<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var array $arParams */
/** @var array $arResult */

IncludeModuleLangFile(__FILE__);

$priceTypeId = intval(CCrmProduct::getSelectedPriceTypeId());

$props = array();
if (is_array($arResult['PROPS']))
{
	foreach ($arResult['PROPS'] as $propIndex => $prop)
	{
		if ((!isset($prop['USER_TYPE'])
				|| empty($prop['USER_TYPE'])
				|| (is_array($prop['PROPERTY_USER_TYPE'])
					&& array_key_exists('GetPublicViewHTML', $prop['PROPERTY_USER_TYPE']))
			)
			&& $prop['PROPERTY_TYPE'] !== 'G')
		{
			$props[intval($prop['~ID'])] = &$arResult['PROPS'][$propIndex];
		}
	}
}

$arResult['PUBLIC_PROPS'] = &$props;

function isPublicHeaderItem($headerId, $priceTypeId, &$propsInfo)
{
	$headerId = trim(strval($headerId));
	$priceTypeId = intval($priceTypeId);
	if ($headerId === '')
		return false;

	if (in_array($headerId, array('BALANCE', 'CODE', 'EXTERNAL_ID', 'SHOW_COUNTER', 'SHOW_COUNTER_START', 'EXPAND',
		'PREVIEW_TEXT', 'QUANTITY', 'ACTION'), true))
	{
		return false;
	}

	$matches = array();
	if (preg_match('/^PRICE(\d+)$/', $headerId, $matches))
	{
		if ($priceTypeId !== intval($matches[1]))
			return false;
	}

	if (is_array($propsInfo) && count($propsInfo) > 0)
	{
		$matches = array();
		if (preg_match('/^PROPERTY_(\d+)$/', $headerId, $matches))
		{
			$propIndex = intval($matches[1]);
			if (!isset($propsInfo[$propIndex]))
				return false;
		}
	}

	return true;
}

if (is_array($arResult['HEADERS']))
{
	$newHeaders = array();

	foreach ($arResult['HEADERS'] as $header)
	{
		if (!isPublicHeaderItem($header['id'], $priceTypeId, $props))
			continue;

		$newHeader = array();
		if (isset($header['id']))
			$newHeader['id'] = $header['id'];
		if (isset($header['content']))
		{
			$matches = array();
			if (preg_match('/^PRICE(\d+)$/', $header['id'], $matches))
			{
				$newHeader['name'] = GetMessage('CRM_COLUMN_PRODUCT_PRICE');
			}
			else
			{
				$newHeader['name'] = $header['content'];
			}
		}
		if (isset($header['sort']))
			$newHeader['sort'] = $header['sort'];
		if (isset($header['default']))
			$newHeader['default'] = $header['default'];
		if (isset($header['align']))
			$newHeader['align'] = $header['align'];
		$newHeaders[] = $newHeader;
	}

	$arResult['HEADERS'] = $newHeaders;
}



// Properties values
$arArrays = array();
$arElements = array();
$arSections = array();

$arProducts = is_array($arResult['PRODUCTS']) ? $arResult['PRODUCTS'] : array();
foreach ($arProducts as $productID => $arItems)
{
	if (is_array($arItems['PRICES']) && isset($arItems['PRICES'][$priceTypeId]))
	{
		if (is_array($arItems['PRICES'][$priceTypeId])
			&& isset($arItems['PRICES'][$priceTypeId]['PRICE']))
		{
			$price = $arItems['PRICES'][$priceTypeId]['PRICE'];
			if (isset($arItems['PRICES'][$priceTypeId]['CURRENCY']))
			{
				$currencyId = $arItems['PRICES'][$priceTypeId]['CURRENCY'];
				$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = CCrmCurrency::MoneyToString($price, $currencyId);
			}
			else
			{
				$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = number_format($price, 2, '.', '');
			}
		}
		else
		{
			$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = $arItems['PRICES'][$priceTypeId];
		}
	}

	if (is_array($arItems['PROPERTIES']))
	{
		foreach ($arItems['PROPERTIES'] as $propID => $propValue)
		{
			if (isset($props[$propID]))
			{
				$arProp = $props[$propID];

				if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
					&& is_array($arProp['PROPERTY_USER_TYPE'])
					&& array_key_exists('GetPublicViewHTML', $arProp['PROPERTY_USER_TYPE']))
				{
					if (is_array($propValue))
					{
						foreach ($propValue as $valueKey => $value)
						{
							$propValue[$valueKey] = call_user_func_array($arProp['PROPERTY_USER_TYPE']['GetPublicViewHTML'], array(
								$arProp,
								array('VALUE' => $value),
								array(),
							));
						}
					}
				}
				else if ($arProp['PROPERTY_TYPE'] == 'F')
				{
					if (is_array($propValue))
					{
						$res = CFileInput::Show(
							'NO_FIELDS[' . $propID . ']',
							$propValue,
							array(
								'IMAGE' => 'Y',
								'PATH' => false,
								'FILE_SIZE' => false,
								'DIMENSIONS' => false,
								'IMAGE_POPUP' => false,
								'MAX_SIZE' => array('W' => 50, 'H' => 50),
								'MIN_SIZE' => array('W' => 1, 'H' => 1),
							),
							array(
								'upload' => false,
								'medialib' => false,
								'file_dialog' => false,
								'cloud' => false,
								'del' => false,
								'description' => false,
							)
						);
						$propValue = preg_replace('!<script[^>]*>.*</script>!isU','', $res);
					}
				}
				else if ($arProp['PROPERTY_TYPE'] == 'E')
				{
					if (is_array($propValue))
					{
						foreach ($propValue as $valueKey => $id)
						{
							if ($id > 0)
								$arElements[] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID][$valueKey];
						}
						$arArrays[$productID.'_'.$propID] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID];
					}
					else if ($propValue > 0)
					{
						$arElements[] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID];
					}
					continue;
				}
				else if ($arProp['PROPERTY_TYPE'] == 'G')
				{
					if (is_array($propValue))
					{
						foreach ($propValue as $valueKey => $id)
						{
							if ($id > 0)
								$arSections[] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID][$valueKey];
						}
						$arArrays[$productID.'_'.$propID] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID];
					}
					else if ($propValue > 0)
					{
						$arSections[] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID];
					}
					continue;
				}

				$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID] = $propValue;

				if (is_array($propValue))
				{
					if (count($propValue) > 1)
						$arArrays[$productID . '_' . $propID] = &$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID];
					else
						$arResult['PRODUCTS'][$productID]['PROPERTIES'][$propID] = reset($propValue);
				}
			}
		}
	}
}

if (count($arElements))
{
	$rsElements = CIBlockElement::GetList(array(), array('=ID' => $arElements), false, false, array('ID', 'NAME', 'DETAIL_PAGE_URL'));
	$arr = array();
	while($ar = $rsElements->GetNext())
		$arr[$ar['ID']] = $ar['NAME'];

	foreach ($arElements as $i => $el)
		if (isset($arr[$el]))
			$arElements[$i] = $arr[$el];
}

if (count($arSections))
{
	$rsSections = CIBlockSection::GetList(array(), array('=ID' => $arSections));
	$arr = array();
	while($ar = $rsSections->GetNext())
		$arr[$ar['ID']] = $ar['NAME'];

	foreach ($arSections as $i => $el)
		if (isset($arr[$el]))
			$arSections[$i] = $arr[$el];
}

foreach ($arArrays as $i => $ar)
	$arArrays[$i] = implode('&nbsp;/<br>', $ar);
