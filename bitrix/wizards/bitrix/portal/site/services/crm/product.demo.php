<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CCrmProductDemo
{
	public static function Create($catalogID = 0, $currencyID = '')
	{
		IncludeModuleLangFile(__FILE__);

		$catalogID = intval($catalogID);
		if($catalogID <= 0)
		{
			$catalogID =  CCrmCatalog::EnsureDefaultExists();
		}

		$currencyID = strval($currencyID);
		if($currencyID === '')
		{
			$currencyID = CCrmCurrency::GetBaseCurrencyID();
		}

		$sectionID =  self::EnsureProductSection(
			'CRM_DEMO_SECTION_PRODUCTS',
			$catalogID,
			array('NAME' => GetMessage('CRM_DEMO_SECTION_PRODUCTS'))
		);

		self::EnsureProduct(
			'CRM_DEMO_PRODUCT_BX_CMS',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_PRODUCT_BX_CMS'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(10000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 100
			)
		);

		self::EnsureProduct(
			'CRM_DEMO_PRODUCT_BX_CP',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_PRODUCT_BX_CP'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(25000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 110
			)
		);

		self::EnsureProduct(
			'CRM_DEMO_PRODUCT_BX_TEAM',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_PRODUCT_BX_TEAM'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(5000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 120
			)
		);

		$sectionID =  self::EnsureProductSection(
			'CRM_DEMO_SECTION_SERVICES',
			$catalogID,
			array('NAME' => GetMessage('CRM_DEMO_SECTION_SERVICES'))
		);

		self::EnsureProduct(
			'CRM_DEMO_SERVICE_SITE_DISIGN',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_SERVICE_SITE_DISIGN'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(15000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 130
			)
		);

		self::EnsureProduct(
			'CRM_DEMO_SERVICE_SITE_TUNING',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_SERVICE_SITE_TUNING'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(20000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 140
			)
		);

		self::EnsureProduct(
			'CRM_DEMO_SERVICE_MANAGER_TRAINING',
			$catalogID,
			array(
				'CATALOG_ID' => $catalogID,
				'SECTION_ID' => $sectionID,
				'NAME' => GetMessage('CRM_DEMO_SERVICE_MANAGER_TRAINING'),
				'CURRENCY_ID' => $currencyID,
				'PRICE' => self::ConvertMoney(5000, 'RUB', $currencyID),
				'ACTIVE' => 'Y',
				'SORT' => 150
			)
		);
	}

	private static function EnsureProductSection($externalID, $catalogID, $arFields)
	{
		$section = new CIBlockSection();
		$rsSections = $section->GetList(array(), array('XML_ID' => $externalID, 'IBLOCK_ID'=> $catalogID), false, array('ID'));
		$arSection = $rsSections->Fetch();
		if(is_array($arSection))
		{
			return intval($arSection['ID']);
		}

		$sectionID = $section->Add(
			array(
				'IBLOCK_ID' => $catalogID,
				'ACTIVE' => 'Y',
				'NAME' => $arFields['NAME'],
				'IBLOCK_SECTION_ID' => 0,
				'CHECK_PERMISSIONS' => 'N',
				'XML_ID' => $externalID
			)
		);

		return $sectionID;
	}

	private static function EnsureProduct($externalID, $catalogID, $arFields)
	{
		$rsProducts = CCrmProduct::GetList(array(), array('CATALOG_ID' => $catalogID, 'ORIGIN_ID' => $externalID), array('ID'));
		$arProduct = $rsProducts ? $rsProducts->Fetch() : false;
		if(is_array($arProduct))
		{
			return intval($arProduct['ID']);
		}

		$arFields['CATALOG_ID'] = $catalogID;
		$arFields['ORIGIN_ID'] = $externalID;
		return CCrmProduct::Add($arFields);
	}

	private static function ConvertMoney($sum, $srcCurrencyID, $dstCurrencyID)
	{
		if (CModule::IncludeModule('currency'))
		{
			return CCrmCurrency::ConvertMoney($sum, $srcCurrencyID, $dstCurrencyID);
		}

		$exchRate = 1.0;
		// Using hardcoded exchange rates for Rub
		if($srcCurrencyID === 'RUB')
		{
			if($dstCurrencyID === 'EUR')
			{
				$exchRate = 39.4;
			}
			elseif($dstCurrencyID === 'USD')
			{
				$exchRate = 31.0;
			}
			elseif($dstCurrencyID === 'UAH')
			{
				$exchRate = 3.9;
			}
		}

		return round($sum / $exchRate, 2);
	}
}
