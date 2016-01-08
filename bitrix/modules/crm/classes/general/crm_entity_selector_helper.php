<?php
IncludeModuleLangFile(__FILE__);

class CCrmEntitySelectorHelper
{
	public static function PrepareEntityInfo($entityTypeName, $entityID, $options = array())
	{
		$entityTypeName = strtoupper(strval($entityTypeName));
		$entityID = intval($entityID);
		if(!is_array($options))
		{
			$options = array();
		}

		$result = array(
			'TITLE' => "{$entityTypeName}_{$entityID}",
			'URL' => ''
		);

		if($entityTypeName === '' || $entityID <= 0)
		{
			return $result;
		}

		if($entityTypeName === 'CONTACT')
		{
			$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');
			$obRes = CCrmContact::GetList(array(), array('=ID'=> $entityID), array('NAME', 'SECOND_NAME', 'LAST_NAME', 'TYPE_ID'));
			if($arRes = $obRes->Fetch())
			{
				$nameTemplate = isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : '';
				if($nameTemplate === '')
				{
					$nameTemplate = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
				}
				$result['TITLE'] = CUser::FormatName(
					$nameTemplate,
					array(
						'LOGIN' => '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : ''
					),
					false,
					false
				);

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $entityID
					)
				);

				// advanced info
				$advancedInfo = array();
				if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
				{
					$advancedInfo['CONTACT_TYPE'] = array(
						'ID' => $arRes['TYPE_ID'],
						'NAME' => $contactTypes[$arRes['TYPE_ID']]
					);
				}
				if (!empty($advancedInfo))
					$result['ADVANCED_INFO'] = $advancedInfo;

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $entityID));
				while($arRes = $obRes->Fetch())
				{
					if ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
					{
						if (!is_array($result['ADVANCED_INFO']))
							$result['ADVANCED_INFO'] = array();
						if (!is_array($result['ADVANCED_INFO']['MULTY_FIELDS']))
							$result['ADVANCED_INFO']['MULTY_FIELDS'] = array();
						$result['ADVANCED_INFO']['MULTY_FIELDS'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
					}
				}
			}
		}
		elseif($entityTypeName === 'COMPANY')
		{
			$obRes = CCrmCompany::GetList(array(), array('=ID'=> $entityID), array('TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = $arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $entityID
					)
				);

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $entityID));
				while($arRes = $obRes->Fetch())
				{
					if ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
					{
						if (!is_array($result['ADVANCED_INFO']))
							$result['ADVANCED_INFO'] = array();
						if (!is_array($result['ADVANCED_INFO']['MULTY_FIELDS']))
							$result['ADVANCED_INFO']['MULTY_FIELDS'] = array();
						$result['ADVANCED_INFO']['MULTY_FIELDS'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
					}
				}
			}
		}
		elseif($entityTypeName === 'LEAD')
		{
			$obRes = CCrmLead::GetList(array(), array('=ID'=> $entityID), array('TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = $arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array(
						'lead_id' => $entityID
					)
				);

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $entityID));
				while($arRes = $obRes->Fetch())
				{
					if ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
					{
						if (!is_array($result['ADVANCED_INFO']))
							$result['ADVANCED_INFO'] = array();
						if (!is_array($result['ADVANCED_INFO']['MULTY_FIELDS']))
							$result['ADVANCED_INFO']['MULTY_FIELDS'] = array();
						$result['ADVANCED_INFO']['MULTY_FIELDS'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
					}
				}
			}
		}
		elseif($entityTypeName === 'DEAL')
		{
			$obRes = CCrmDeal::GetList(array(), array('=ID'=> $entityID), array('TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = $arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array(
						'deal_id' => $entityID
					)
				);
			}
		}
		elseif($entityTypeName === 'QUOTE')
		{
			$obRes = CCrmQuote::GetList(array(), array('=ID'=> $entityID), false, false, array('QUOTE_NUMBER', 'TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array(
						'quote_id' => $entityID
					)
				);
			}
		}

		return $result;
	}

	public static function PreparePopupItems($entityTypeNames, $addPrefix = true, $nameFormat = '', $count = 50)
	{
		if(!is_array($entityTypeNames))
		{
			$entityTypeNames = array(strval($entityTypeNames));

		}

		$addPrefix =  (bool)$addPrefix;
		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		}
		$count = intval($count);
		if($count <= 0)
		{
			$count = 50;
		}

		$arItems = array();
		$i = 0;
		foreach($entityTypeNames as $typeName)
		{
			$typeName = strtoupper(strval($typeName));

			if($typeName === 'CONTACT')
			{
				$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');
				$contactIndex = array();

				$obRes = CCrmContact::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arImg = array();
					if (!empty($arRes['PHOTO']) && !isset($arFiles[$arRes['PHOTO']]))
					{
						if(intval($arRes['PHOTO']) > 0)
						{
							$arImg = CFile::ResizeImageGet($arRes['PHOTO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
						}
					}

					$arRes['SID'] = $addPrefix ? 'C_'.$arRes['ID']: $arRes['ID'];

					// advanced info
					$advancedInfo = array();
					if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
					{
						$advancedInfo['contactType'] = array(
							'id' => $arRes['TYPE_ID'],
							'name' => $contactTypes[$arRes['TYPE_ID']]
						);
					}

					$arItems[$i] = array(
						'title' => CUser::FormatName(
							$nameFormat,
							array(
								'LOGIN' => '',
								'NAME' => $arRes['NAME'],
								'SECOND_NAME' => $arRes['SECOND_NAME'],
								'LAST_NAME' => $arRes['LAST_NAME']
							),
							false,
							false
						),
						'desc'  => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
							array(
								'contact_id' => $arRes['ID']
							)
						),
						'image' => $arImg['src'],
						'type'  => 'contact',
						'selected' => 'N'
					);
					if (!empty($advancedInfo))
						$arItems[$i]['advancedInfo'] = $advancedInfo;
					unset($advancedInfo);
					$contactIndex[$arRes['ID']] = &$arItems[$i];
					$i++;
				}

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => array_keys($contactIndex)));
				while($arRes = $obRes->Fetch())
				{
					if (isset($contactIndex[$arRes['ELEMENT_ID']])
						&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
					{
						$item = &$contactIndex[$arRes['ELEMENT_ID']];
						if (!is_array($item['advancedInfo']))
							$item['advancedInfo'] = array();
						if (!is_array($item['advancedInfo']['multiFields']))
							$item['advancedInfo']['multiFields'] = array();
						$item['advancedInfo']['multiFields'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
						unset($item);
					}
				}
				unset($contactIndex);
			}
			elseif($typeName === 'COMPANY')
			{
				$companyIndex = array();
				$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
				$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
				$obRes = CCrmCompany::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
				);

				$arFiles = array();
				while ($arRes = $obRes->Fetch())
				{
					$arImg = array();
					if (!empty($arRes['LOGO']) && !isset($arFiles[$arRes['LOGO']]))
					{
						if(intval($arRes['LOGO']) > 0)
							$arImg = CFile::ResizeImageGet($arRes['LOGO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);

						$arFiles[$arRes['LOGO']] = $arImg['src'];
					}

					$arRes['SID'] = $addPrefix ? 'CO_'.$arRes['ID']: $arRes['ID'];

					$arDesc = Array();
					if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
						$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
					if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
						$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];


					$arItems[$i] = array(
						'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
						'desc' => implode(', ', $arDesc),
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
							array(
								'company_id' => $arRes['ID']
							)
						),
						'image' => $arImg['src'],
						'type'  => 'company',
						'selected' => 'N'
					);
					$companyIndex[$arRes['ID']] = &$arItems[$i];
					$i++;
				}

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => array_keys($companyIndex)));
				while($arRes = $obRes->Fetch())
				{
					if (isset($companyIndex[$arRes['ELEMENT_ID']])
						&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
					{
						$item = &$companyIndex[$arRes['ELEMENT_ID']];
						if (!is_array($item['advancedInfo']))
							$item['advancedInfo'] = array();
						if (!is_array($item['advancedInfo']['multiFields']))
							$item['advancedInfo']['multiFields'] = array();
						$item['advancedInfo']['multiFields'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
						unset($item);
					}
				}
				unset($companyIndex);
			}
			elseif($typeName === 'LEAD')
			{
				$leadIndex = array();
				$obRes = CCrmLead::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? 'L_'.$arRes['ID']: $arRes['ID'];

					$arItems[$i] = array(
						'title' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'desc' => CUser::FormatName(
							$nameFormat,
							array(
								'LOGIN' => '',
								'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
								'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
								'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
							),
							false,
							false
						),
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
							array(
								'lead_id' => $arRes['ID']
							)
						),
						'type'  => 'lead',
						'selected' => 'N'
					);
					$leadIndex[$arRes['ID']] = &$arItems[$i];
					$i++;
				}

				// advanced info - phone number, e-mail
				$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => array_keys($leadIndex)));
				while($arRes = $obRes->Fetch())
				{
					if (isset($leadIndex[$arRes['ELEMENT_ID']])
						&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
					{
						$item = &$leadIndex[$arRes['ELEMENT_ID']];
						if (!is_array($item['advancedInfo']))
							$item['advancedInfo'] = array();
						if (!is_array($item['advancedInfo']['multiFields']))
							$item['advancedInfo']['multiFields'] = array();
						$item['advancedInfo']['multiFields'][] = array(
							'ID' => $arRes['ID'],
							'TYPE_ID' => $arRes['TYPE_ID'],
							'VALUE_TYPE' => $arRes['VALUE_TYPE'],
							'VALUE' => $arRes['VALUE']
						);
						unset($item);
					}
				}
				unset($leadIndex);
			}
			elseif($typeName === 'DEAL')
			{
				$obRes = CCrmDeal::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? 'D_'.$arRes['ID']: $arRes['ID'];

					$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
					$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

					$arItems[] = array(
						'title' => isset($arRes['TITLE']) ? str_replace(array(';', ','), ' ', $arRes['TITLE']) : '',
						'desc' => $clientTitle,
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
							array(
								'deal_id' => $arRes['ID']
							)
						),
						'type'  => 'deal',
						'selected' => 'N'
					);
				}
			}
			elseif($typeName === 'QUOTE')
			{
				$obRes = CCrmQuote::GetList(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'];

					$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
					$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

					$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

					$arItems[] = array(
						'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
						'desc' => $clientTitle,
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
							array(
								'quote_id' => $arRes['ID']
							)
						),
						'type'  => 'quote',
						'selected' => 'N'
					);
				}
			}
		}
		unset($typeName);

		return $arItems;
	}

	public static function PrepareListItems($arSource)
	{
		$result = array();
		if(is_array($arSource))
		{
			foreach($arSource as $k => &$v)
			{
				$result[] = array('value' => $k, 'text' => $v);
			}
			unset($v);
		}
		return $result;
	}

	public static function PrepareCommonMessages()
	{
		return array(
			'lead'=> GetMessage('CRM_FF_LEAD'),
			'contact' => GetMessage('CRM_FF_CONTACT'),
			'company' => GetMessage('CRM_FF_COMPANY'),
			'deal'=> GetMessage('CRM_FF_DEAL'),
			'quote'=> GetMessage('CRM_FF_QUOTE'),
			'ok' => GetMessage('CRM_FF_OK'),
			'cancel' => GetMessage('CRM_FF_CANCEL'),
			'close' => GetMessage('CRM_FF_CLOSE'),
			'wait' => GetMessage('CRM_FF_WAIT'),
			'noresult' => GetMessage('CRM_FF_NO_RESULT'),
			'add' => GetMessage('CRM_FF_CHOISE'),
			'edit' => GetMessage('CRM_FF_CHANGE'),
			'search' => GetMessage('CRM_FF_SEARCH'),
			'last' => GetMessage('CRM_FF_LAST')
		);
	}

	public static function PrepareEntityAdvancedInfoHTML($entityTypeName, $entityInfo = array(), $options = array())
	{
		$result = '';
		$contactType = isset($entityInfo['ADVANCED_INFO']['CONTACT_TYPE']['NAME']) ?
			trim(strval($entityInfo['ADVANCED_INFO']['CONTACT_TYPE']['NAME'])) : '';

		// multifields
		$arPhone = array();
		$arEmail = array();
		$arMultiFields = is_array($entityInfo['ADVANCED_INFO']['MULTY_FIELDS']) ? $entityInfo['ADVANCED_INFO']['MULTY_FIELDS'] : array();
		foreach ($arMultiFields as $mf)
		{
			if (isset($mf['TYPE_ID']) && $mf['TYPE_ID'] === 'PHONE')
			{
				$arPhone[] = array('VALUE' => trim(strval($mf['VALUE'])));
			}
			if (isset($mf['TYPE_ID']) && $mf['TYPE_ID'] === 'EMAIL')
			{
				$arEmail[] = array('VALUE' => trim(strval($mf['VALUE'])));
			}
		}
		unset($arMultiFields);

		$containerID = isset($options['CONTAINER_ID']) ? $options['CONTAINER_ID'] : '';

		$result .= '<div'.($containerID != '' ? ' id="'.htmlspecialcharsbx($containerID).'"' : '').
			' class="crm-offer-info-description">';

		switch (ToUpper($entityTypeName))
		{
			case 'CONTACT':
				if (!empty($arPhone))
				{
					$result .=
						"\t" .
						'<span class="crm-offer-info-descrip-tem crm-offer-info-descrip-tel">'.
						GetMessage('CRM_ENT_SEL_HLP_PREF_PHONE').': '.htmlspecialcharsbx($arPhone[0]['VALUE']).
						'<a href="callto:'.htmlspecialcharsbx($arPhone[0]['VALUE']).'" class="crm-offer-info-descrip-icon"></a>'.
						'</span><br/>';
				}
				if (!empty($arEmail))
				{
					$result .=
						"\t" .
						'<span class="crm-offer-info-descrip-tem crm-offer-info-descrip-imail">'.
						GetMessage('CRM_ENT_SEL_HLP_PREF_EMAIL').': '.htmlspecialcharsbx($arEmail[0]['VALUE']).
						'<a href="mailto:'.htmlspecialcharsbx($arEmail[0]['VALUE']).'" class="crm-offer-info-descrip-icon"></a>'.
						'</span><br/>';
				}
				if ($contactType != '')
				{
					$result .=
						"\t" .
						'<span class="crm-offer-info-descrip-tem crm-offer-info-descrip-type">'.
						GetMessage('CRM_ENT_SEL_HLP_PREF_CONTACT_TYPE').': '.htmlspecialcharsbx($contactType).
						'</span><br/>';
				}
				break;
			case 'COMPANY':
			case 'LEAD':
				if (!empty($arPhone))
				{
					$result .=
						"\t" .
						'<span class="crm-offer-info-descrip-tem crm-offer-info-descrip-tel">'.
						GetMessage('CRM_ENT_SEL_HLP_PREF_PHONE').': '.htmlspecialcharsbx($arPhone[0]['VALUE']).
						'<a href="callto:'.htmlspecialcharsbx($arPhone[0]['VALUE']).
						'" class="crm-offer-info-descrip-icon"></a>'.
						'</span><br/>';
				}
				if (!empty($arEmail))
				{
					$result .=
						"\t" .
						'<span class="crm-offer-info-descrip-tem crm-offer-info-descrip-imail">'.
						GetMessage('CRM_ENT_SEL_HLP_PREF_EMAIL').': '.htmlspecialcharsbx($arEmail[0]['VALUE']).
						'<a href="mailto:'.htmlspecialcharsbx($arEmail[0]['VALUE']).
						'" class="crm-offer-info-descrip-icon"></a>'.
						'</span><br/>';
				}
				break;
		}

		$result .= '</div>';

		return $result;
	}
}
