<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/workgroups/.left.menu_ext.php");

global $APPLICATION;

// You can change this url template
$strGroupSubjectLinkTemplate = COption::GetOptionString("socialnetwork", "subject_path_template", SITE_DIR."workgroups/group/search/#subject_id#/", SITE_ID);
$strGroupLinkTemplate = COption::GetOptionString("socialnetwork", "group_path_template", SITE_DIR."workgroups/group/#group_id#/", SITE_ID);

if (SITE_TEMPLATE_ID === "bitrix24")
{
	if (CBXFeatures::IsFeatureEnabled('Workgroups')):
		GLOBAL $USER;
		$USER_ID = $USER->GetID();
		
		$arSGGroup = array();

		if (CModule::IncludeModule("socialnetwork"))
		{
			$arSGGroup[] = array(
				GetMessage("WORKGROUPS_MENU_ALL_GROUPS"),
				SITE_DIR."workgroups/index.php",
				Array(),
				Array("menu_item_id"=>"menu_all_groups"),
				"CBXFeatures::IsFeatureEnabled('Workgroups')"
			);

			$extGroupID = array();

			if (IsModuleInstalled("extranet"))
			{
				/*$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
				$cache_id = 'bx_user_inexmenu_'.$USER_ID;
				$obCache = new CPHPCache;
				$cache_dir = '/bx/user_inexmenu'; */

				//if($obCache->InitCache($ttl, $cache_id, $cache_dir))
				//	$extGroupID = $obCache->GetVars();
				//else
				//{
					if(defined("BX_COMP_MANAGED_CACHE"))
					{	
						global $CACHE_MANAGER;
					}
				//	$CACHE_MANAGER->StartTagCache($cache_dir);

					if (CModule::IncludeModule("extranet"))
					{
						$arGroupFilterMy = array(
							"USER_ID" => $USER_ID,
							"<=ROLE" => SONET_ROLES_USER,
							"GROUP_ACTIVE" => "Y",
							"!GROUP_CLOSED" => "Y",
							"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID()
						);

						$dbGroups = CSocNetUserToGroup::GetList(
							array(),
							$arGroupFilterMy,
							false,
							false,
							array('ID')
						);

						while ($arGroups = $dbGroups->GetNext())
							$extGroupID[] = $arGroups["ID"];
					}
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->RegisterTag('sonet_group');
					$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$USER_ID);
				}
				//	$CACHE_MANAGER->EndTagCache();

				//	if($obCache->StartDataCache())
				//		$obCache->EndDataCache($extGroupID);
				//}
				//unset($obCache);
			}

			$arGroupFilterMy = array(
				"USER_ID" => $USER_ID,
				"<=ROLE" => SONET_ROLES_USER,
				"GROUP_ACTIVE" => "Y",
				"!GROUP_CLOSED" => "Y",
				"GROUP_SITE_ID" => SITE_ID
			);

			// Socialnetwork

			$dbGroups = CSocNetUserToGroup::GetList(
					array("GROUP_NAME" => "ASC"),
					$arGroupFilterMy,
					false,
					false,
					array('ID', 'GROUP_ID', 'GROUP_NAME')
				);

			while ($arGroups = $dbGroups->GetNext())
			{
				if(in_array($arGroups['ID'], $extGroupID))
					continue;

				$arSGGroup[] = array(
					$arGroups["GROUP_NAME"],
					str_replace("#group_id#", $arGroups["GROUP_ID"], $strGroupLinkTemplate),
					array(),
					array(/*"counter_id" => "SG".$arGroups["GROUP_ID"]*/),
					""
				);
				//$CACHE_MANAGER->RegisterTag('sonet_group_'.$arGroups["ID"]);
			}
		}
		$aMenuLinks = $arSGGroup;
	endif;
}	
else
{
	if (CModule::IncludeModule("socialnetwork"))
	{
		if (!function_exists("__CheckPath4Template"))
		{
			function __CheckPath4Template($pageTemplate, $currentPageUrl, &$arVariables)
			{
				$pageTemplateReg = preg_replace("'#[^#]+?#'", "([^/]+?)", $pageTemplate);
	//			if (substr($pageTemplateReg, -1, 1) == "/")
	//				$pageTemplateReg .= "index\\.php";

				$arValues = array();
				if (preg_match("'^".$pageTemplateReg."'", $currentPageUrl, $arValues))
				{
					$arMatches = array();
					if (preg_match_all("'#([^#]+?)#'", $pageTemplate, $arMatches))
					{
						for ($i = 0, $cnt = count($arMatches[1]); $i < $cnt; $i++)
							$arVariables[$arMatches[1][$i]] = $arValues[$i + 1];
					}
					return True;
				}

				return False;
			}
		}

		$arGroup = false;
		$arVariables = array();
		$componentPage = __CheckPath4Template($strGroupLinkTemplate, $_SERVER["REQUEST_URI"], $arVariables);
		if ($componentPage && IntVal($arVariables["group_id"]) > 0)
			$arGroup = CSocNetGroup::GetByID(IntVal($arVariables["group_id"]));

		$dbGroupSubjects = CSocNetGroupSubject::GetList(
			array("SORT" => "ASC", "NAME" => "ASC"),
			array("SITE_ID" => SITE_ID),
			false,
			false,
			array("ID", "NAME")
		);

		$aMenuLinksAdd = array();
		while ($arGroupSubject = $dbGroupSubjects->GetNext())
		{
			$arLinks = array();
			if ($arGroup && $arGroup["SUBJECT_ID"] == $arGroupSubject["ID"])
				$arLinks = array($_SERVER["REQUEST_URI"]);

			$aMenuLinksAdd[] = array(
				$arGroupSubject["NAME"],
				str_replace("#subject_id#", $arGroupSubject["ID"], $strGroupSubjectLinkTemplate),
				$arLinks,
				array(),
				""
			);
		}

		$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksAdd);

		$aMenuLinks[] = array(GetMessage("WORKGROUPS_MENU_ARCHIVE"), str_replace("#subject_id#", -1, $strGroupSubjectLinkTemplate), array(), array(), "");
	}
}
?>