<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
require_once("scripts/utils.php");

define("EDITION", "E");
define("NON_INTRANET_EDITION", false);

class CSelectSiteWizardStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_site");
		$this->SetTitle(GetMessage("SELECT_SITE_TITLE"));
		$this->SetSubTitle(GetMessage("SELECT_SITE_SUBTITLE"));
		$this->SetNextStep("select_template");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsNextButtonClick())
		{
			$siteID = $wizard->GetVar("siteID");
			$siteFolder = str_replace(array("\\", "///", "//"), "/", "/".$wizard->GetVar("siteFolder")."/");
			$siteNewID = $wizard->GetVar("siteNewID");
			$createSite = $wizard->GetVar("createSite");

			if ($createSite == "Y")
			{
				if (strlen($siteNewID) != 2)
				{
					$this->SetError(GetMessage("wiz_site_id_error"));
					return;
				}
				$rsSites = CSite::GetList($by="sort", $order="desc", array());
				while($arSite = $rsSites->Fetch())
				{
					if (trim($arSite["DIR"], "/") == trim($siteFolder, "/"))
					{
						$this->SetError(GetMessage("wiz_site_folder_already_exists"));
						$bError = true;
					}

					if ($arSite["ID"] == trim($siteNewID))
					{
						$this->SetError(GetMessage("wiz_site_id_already_exists"));
						$bError = true;
					}
				}
				if ($bError)
					return;
				$wizard->SetVar("siteID", $siteNewID);
				$wizard->SetVar("siteCreate", "Y");
				$wizard->SetVar("siteFolder", $siteFolder);
			}
			elseif (strlen($siteID) > 0)
			{
				$db_res = CSite::GetList($by="sort", $order="desc", array("LID" => $siteID));
				if (!($db_res && $res = $db_res->Fetch()))
					$this->SetError(GetMessage("wiz_site_id_not_exists_error"));
				return;
			}
			else
			{
				$siteID = WizardServices::GetCurrentSiteID();
				$wizard->SetVar("siteID", $siteID);
			}
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arSites = array();
		$arSitesSelect = array();
		$db_res = CSite::GetList($by="sort", $order="desc", array());
		if ($db_res && $res = $db_res->GetNext())
		{
			do
			{
				$arSites[$res["ID"]] = $res;
				$arSitesSelect[$res["ID"]] = '['.$res["ID"].'] '.$res["NAME"];
			} while ($res = $db_res->GetNext());
		}

		$createSite = $wizard->GetVar("createSite");
		$createSite = ($createSite == "Y" ? "Y" : "N");


$this->content =
'<script type="text/javascript">
function SelectCreateSite(element, solutionId)
{
	var container = document.getElementById("solutions-container");
	var nodes = container.childNodes;
	for (var i = 0; i < nodes.length; i++)
	{
		if (!nodes[i].className)
			continue;
		nodes[i].className = "solution-item";
	}
	element.className = "solution-item solution-item-selected";
	var check = document.getElementById("createSite" + solutionId);
	if (check)
		check.checked = true;
}
</script>';
		$this->content .= '<div id="solutions-container">';
			$this->content .= "<div onclick=\"SelectCreateSite(this, 'N');\" ";
				$this->content .= 'class="solution-item'.($createSite != "Y" ? " solution-item-selected" : "").'">';
				$this->content .= '<b class="r3"></b><b class="r1"></b><b class="r1"></b>';
				$this->content .= '<div class="solution-inner-item">';
					$this->content .= $this->ShowRadioField("createSite", "N", (array("id" => "createSiteN", "class" => "solution-radio") +
						($createSite != "Y" ? array("checked" => "checked") : array())));
					$this->content .= '<h4>'.GetMessage("wiz_site_existing").'</h4>';
				if (count($arSites) < 2)
					$this->content .= '<p>'.GetMessage("wiz_site_existing_title").' '.implode("", $arSitesSelect).'</p>';
				else
				{
					$this->content .= '<p>'.GetMessage("wiz_site_existing_title");
					$this->content .= "<br />". $this->ShowSelectField("siteID", $arSitesSelect)."</p>";
				}
				$this->content .= '</div>';
				$this->content .= '<b class="r1"></b><b class="r1"></b><b class="r3"></b>';
			$this->content .= '</div>';
		if (count($arSites) < COption::GetOptionInt("main", "PARAM_MAX_SITES", 100) || COption::GetOptionInt("main", "PARAM_MAX_SITES", 100) <= 0)
		{
			$this->content .= "<div onclick=\"SelectCreateSite(this, 'Y');\" ";
				$this->content .= 'class="solution-item'.($createSite == "Y" ? " solution-item-selected" : "").'">';
				$this->content .= '<b class="r3"></b><b class="r1"></b><b class="r1"></b>';
				$this->content .= '<div class="solution-inner-item">';
					$this->content .= $this->ShowRadioField("createSite", "Y", (array("id" => "createSiteY", "class" => "solution-radio") +
						($createSite == "Y" ? array("checked" => "checked") : array())));
					$this->content .= '<h4>'.GetMessage("wiz_site_new").'</h4>';
					$this->content .= '<p>';
						$this->content .= str_replace(
							array(
								"#SITE_ID#",
								"#SITE_DIR#"),
							array(
								$this->ShowInputField("text", "siteNewID", array("size" => 2, "maxlength" => 2, "id" => "siteNewID")),
								$this->ShowInputField("text", "siteFolder", array("id" => "siteFolder"))),
							GetMessage("wiz_site_new_title"));
					$this->content .= '</p>';
				$this->content .= '</div>';
				$this->content .= '<b class="r1"></b><b class="r1"></b><b class="r3"></b>';
			$this->content .= '</div>';
		}
		$this->content .= '</div>';
	}
}

class WelcomeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetTitle(GetMessage("WELCOME_STEP_TITLE"));
		$this->SetStepID("welcome_step");

		if (!defined("WIZARD_DEFAULT_SITE_ID"))
		{
			$this->SetPrevStep("select_site");
			$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

			$wizard =& $this->GetWizard();
			$wizard->SetVar("siteID", 's1');
			$wizard->SetVar("siteFolder", '/');
		}
		else
		{
			$wizard =& $this->GetWizard();
			$wizard->SetVar("siteID", WIZARD_DEFAULT_SITE_ID);
			if(WIZARD_DEFAULT_SITE_ID == 's1') $siteFolder = '';
			else $siteFolder = '/' . WIZARD_DEFAULT_SITE_ID;
			$wizard->SetVar("siteFolder", $siteFolder);

		}
// redirect from Extranet site to s1
		$wizard =& $this->GetWizard();
		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));

		if (CModule::IncludeModule("extranet") && $siteID == CExtranet::GetExtranetSiteID() || !(CModule::IncludeModule("extranet")) && COption::GetOptionString("extranet", "extranet_site") == $siteID)
		{
			global $APPLICATION;
			$page = $APPLICATION->GetCurPageParam("wizardSiteID=s1", array("wizardSiteID"));
			LocalRedirect($page);
		}

		$this->SetNextStep("select_template");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
//b24 to cp		
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu.php"))
		{
			$arB24Features = array(
				"StaffAbsence",
				"CommonDocuments",
				"Wiki",
				"Vote",
				"PersonalFiles",
				"PersonalBlog",
				"PersonalPhoto",
				"Blog",
				"DAV",
				"WebMessenger",
				"Tasks",
				"Calendar",
				"Workgroups",
				"Extranet",
				"timeman",
				"Meeting",
				"crm"
			);
			if (IsModuleInstalled("extranet"))
				$arB24Features[] = "extranet";
			if (IsModuleInstalled("meeting"))
				$arB24Features[] = "meeting";
			if (IsModuleInstalled("timeman"))
				$arB24Features[] = "timeman";
			
			foreach($arB24Features as $featureID)
			{
				if (CBXFeatures::IsFeatureEnabled($featureID))
					CBXFeatures::SetFeatureEnabled($featureID, true);
			}
			
		}
	}

	function ShowStep()
	{
		//wizard customization file
		$bxProductConfig = array();
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php");

		if(isset($bxProductConfig["intranet_wizard"]["welcome_text"]))
			$this->content .= $bxProductConfig["intranet_wizard"]["welcome_text"];
		else
			$this->content .= GetMessage("WELCOME_TEXT");

		if(file_exists($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/ldap/include.php'))
		{
			if (!function_exists("ldap_connect"))
			{
				$this->content .= '<br /><br /><span style="color:red;">'.GetMessage("wiz_ldap_warn").'</span>';
			}
			elseif (!IsModuleInstalled("ldap"))
			{
				$this->content .= '<br /><br /><span style="color:red;">'.GetMessage("wiz_ldap_require", array('#LANGUAGE_ID#'=>LANGUAGE_ID)).'</span>';
			}
		}
	}
}

class SelectTemplateStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_template");
		$this->SetTitle(GetMessage("SELECT_TEMPLATE_TITLE"));
		$this->SetSubTitle(GetMessage("SELECT_TEMPLATE_SUBTITLE"));
		$this->SetPrevStep("welcome_step");
		$this->SetNextStep("select_theme");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsNextButtonClick())
		{
			$templatesPath = WizardServices::GetTemplatesPath("/bitrix/modules/intranet/install");
			$arTemplates = WizardServices::GetTemplates($templatesPath, $wizard->GetPath());

			$templateID = $wizard->GetVar("templateID");

			if (!array_key_exists($templateID, $arTemplates))
				$this->SetError(GetMessage("wiz_template"));

			if ($templateID === "bitrix24")
			{
				$siteDef = 'N';
				$siteID = $wizard->GetVar("siteID");
				$rsSites = CSite::GetByID($siteID);
				if ($arSite = $rsSites->Fetch())
					$siteDef = $arSite["DEF"];
				$wizard->SetCurrentStep(($_SERVER["PHP_SELF"] == "/index.php" || NON_INTRANET_EDITION || $siteDef == 'N') ? "site_settings" : "portal_features_settings");
			}
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$templatesPath = WizardServices::GetTemplatesPath("/bitrix/modules/intranet/install");
		$arTemplates = WizardServices::GetTemplates($templatesPath, $wizard->GetPath());

		if (empty($arTemplates))
			return;

		$arTemplateOrder = array("bitrix24", "light", "bright", "modern", "classic");

		$defaultTemplateID = COption::GetOptionString("main", "wizard_template_id", "", $wizard->GetVar("siteID"));
		if (strlen($defaultTemplateID) > 0 && array_key_exists($defaultTemplateID, $arTemplates))
			$wizard->SetDefaultVar("templateID", $defaultTemplateID);
		else
			$defaultTemplateID = "";
		global $SHOWIMAGEFIRST;
		$SHOWIMAGEFIRST = true;
		$this->content .= '<table width="100%" cellspacing="4" cellpadding="8">';

		foreach ($arTemplateOrder as $templateID)
		{
			$arTemplate = $arTemplates[$templateID];

			if (!$arTemplate)
				continue;

			if ($defaultTemplateID == "")
			{
				$defaultTemplateID = $templateID;
				$wizard->SetDefaultVar("templateID", $defaultTemplateID);
			}

			$this->content .= "<tr>";
			$this->content .= '<td width="25">'.$this->ShowRadioField("templateID", $templateID, Array("id" => $templateID))."</td>";
			global $SHOWIMAGEFIRST;
			$SHOWIMAGEFIRST = true;
			if ($arTemplate["SCREENSHOT"] && $arTemplate["PREVIEW"])
				$this->content .= '<td width="160" valign="top">'.CFile::Show2Images($arTemplate["PREVIEW"], $arTemplate["SCREENSHOT"], 150, 150, ' border="0"')."</td>";
			else
				$this->content .= '<td width="160" valign="top">'.CFile::ShowImage($arTemplate["SCREENSHOT"], 150, 150, ' border="0"', "", true)."</td>";

			$this->content .= '<td valign="top"><label for="'.$templateID.'"><b>'.$arTemplate["NAME"]."</b><p>".$arTemplate["DESCRIPTION"]."</p></label></td>";

			$this->content .= "</tr>";
			$this->content .= "<tr><td><br /></td></tr>";
		}

		$this->content .= "</table>";
		
		$this->content .= '<script>
			function ImgShw(ID, width, height, alt)
			{
				var scroll = "no";
				var top=0, left=0;
				if(width > screen.width-10 || height > screen.height-28) scroll = "yes";
				if(height < screen.height-28) top = Math.floor((screen.height - height)/2-14);
				if(width < screen.width-10) left = Math.floor((screen.width - width)/2-5);
				width = Math.min(width, screen.width-10);
				height = Math.min(height, screen.height-28);
				var wnd = window.open("","","scrollbars="+scroll+",resizable=yes,width="+width+",height="+height+",left="+left+",top="+top);
				wnd.document.write(
					"<html><head>"+
						"<"+"script type=\"text/javascript\">"+
						"function KeyPress()"+
						"{"+
						"	if(window.event.keyCode == 27) "+
						"		window.close();"+
						"}"+
						"</"+"script>"+
						"<title></title></head>"+
						"<body topmargin=\"0\" leftmargin=\"0\" marginwidth=\"0\" marginheight=\"0\" onKeyPress=\"KeyPress()\">"+
						"<img src=\""+ID+"\" border=\"0\" alt=\""+alt+"\" />"+
						"</body></html>"
				);
				wnd.document.close();
			}
		</script>';
	}
}


class SelectThemeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_theme");
		$this->SetTitle(GetMessage("SELECT_THEME_TITLE"));
		$this->SetSubTitle(GetMessage("SELECT_THEME_SUBTITLE"));
		$this->SetPrevStep("select_template");

		$wizard =& $this->GetWizard();

		$siteDef = 'N';
		$siteID = $wizard->GetVar("siteID");
		$rsSites = CSite::GetByID($siteID);
		if ($arSite = $rsSites->Fetch())
			$siteDef = $arSite["DEF"];

		$this->SetNextStep(($_SERVER["PHP_SELF"] == "/index.php" || NON_INTRANET_EDITION || $siteDef == 'N') ? "site_settings" : "portal_features_settings");

		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsNextButtonClick())
		{
			$templateID = $wizard->GetVar("templateID");
			$themeVarName = $templateID."_themeID";
			$themeID = $wizard->GetVar($themeVarName);

			$templatesPath = WizardServices::GetTemplatesPath("/bitrix/modules/intranet/install");
			$arThemes = WizardServices::GetThemes($templatesPath."/".$templateID."/themes", $wizard->GetPath(), $templateID);

			if (!array_key_exists($themeID, $arThemes))
				$this->SetError(GetMessage("wiz_template_color"));
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();
		$templateID = $wizard->GetVar("templateID");

		$templatesPath = WizardServices::GetTemplatesPath("/bitrix/modules/intranet/install");
		$arThemes = WizardServices::GetThemes($templatesPath."/".$templateID."/themes", $wizard->GetPath(), $templateID);

		if (empty($arThemes))
			return;

		$themeVarName = $templateID."_themeID";

		$defaultThemeID = COption::GetOptionString("main", "wizard_".$templateID."_theme_id", "", $wizard->GetVar("siteID"));
		if (strlen($defaultThemeID) > 0 && array_key_exists($defaultThemeID, $arThemes))
			$wizard->SetDefaultVar($themeVarName, $defaultThemeID);
		else
			$defaultThemeID = "";
		global $SHOWIMAGEFIRST;
		$SHOWIMAGEFIRST = true;
		$this->content .= '<table width="100%" cellspacing="4" cellpadding="8">';

		foreach ($arThemes as $themeID => $arTheme)
		{
			if ($defaultThemeID == "")
			{
				$defaultThemeID = $themeID;
				$wizard->SetDefaultVar($themeVarName, $defaultThemeID);
			}

			$this->content .= "<tr>";

			$this->content .= "<td width=\"25\">".$this->ShowRadioField($themeVarName, $themeID, Array("id" => $themeVarName."_".$themeID))."</td>";
			global $SHOWIMAGEFIRST;
			$SHOWIMAGEFIRST = true;
			if ($arTheme["SCREENSHOT"] && $arTheme["PREVIEW"])
				$this->content .= '<td valign="top" width="160">'.CFile::Show2Images($arTheme["PREVIEW"], $arTheme["SCREENSHOT"], 150, 150, ' border="0"')."</td>";
			else
				$this->content .= '<td valign="top" width="160">'.CFile::ShowImage($arTheme["SCREENSHOT"], 150, 150, ' border="0"', "", true)."</td>";

			$this->content .= '<td valign="top"><label for="'.$themeVarName."_".$themeID.'"><b>'.$arTheme["NAME"]."</b><p>".$arTheme["DESCRIPTION"]."</p></label></td>";

			$this->content .= "</tr>";
			$this->content .= "<tr><td><br /></td></tr>";
		}

		$this->content .= "</table>";

		$this->content .= '<script>
			function ImgShw(ID, width, height, alt)
			{
				var scroll = "no";
				var top=0, left=0;
				if(width > screen.width-10 || height > screen.height-28) scroll = "yes";
				if(height < screen.height-28) top = Math.floor((screen.height - height)/2-14);
				if(width < screen.width-10) left = Math.floor((screen.width - width)/2-5);
				width = Math.min(width, screen.width-10);
				height = Math.min(height, screen.height-28);
				var wnd = window.open("","","scrollbars="+scroll+",resizable=yes,width="+width+",height="+height+",left="+left+",top="+top);
				wnd.document.write(
					"<html><head>"+
						"<"+"script type=\"text/javascript\">"+
						"function KeyPress()"+
						"{"+
						"	if(window.event.keyCode == 27) "+
						"		window.close();"+
						"}"+
						"</"+"script>"+
						"<title></title></head>"+
						"<body topmargin=\"0\" leftmargin=\"0\" marginwidth=\"0\" marginheight=\"0\" onKeyPress=\"KeyPress()\">"+
						"<img src=\""+ID+"\" border=\"0\" alt=\""+alt+"\" />"+
						"</body></html>"
				);
				wnd.document.close();
			}
		</script>';
	}
}

class PortalFeaturesSettingsStep extends CWizardStep
{
	var $arBXFeaturesPrintable = array(
		"Portal" => array(
			"company" => array(
				"CompanyCalendar",
				"CompanyPhoto",
				"CompanyVideo",
				"CompanyCareer",
				"Gallery",
			),
			"staff" => array(
				"StaffChanges",
				"StaffAbsence",
			),
			"CommonDocuments",
			"services" => array(
				"MeetingRoomBookingSystem",
				"Wiki",
				"Learning",
				"Vote",
				"WebLink",
				"Subscribe",
				"Board",
			),
		),
		"Social" => array(
			//"socnet" => array(
				"Friends",
				"PersonalFiles",
				"PersonalPhoto",
				"PersonalForum",
		//	),
			"WebMessenger",
		),
		"Communications" => array(
			"Meeting",
			"Tasks",
			"Idea",
			"Calendar",
			"Workgroups",
			"Jabber",
			"VideoConference",
			"Extranet",
			"SMTP",
			"Requests",
			"DAV",
			"intranet_sharepoint",
			"timeman",
			"EventList",
			"Salary",
			"XDImport",
		),
		"Enterprise" => array(
			"BizProc",
			"Lists",
			"Support",
			"Analytics",
			"crm",
			"Controller",
		),
		"Holding" => array(
			"Cluster",
			"MultiSites",
		),
	);

	var $editionId;

	function ExtractFeatures($arFeatures)
	{
		$arResult = array();
		foreach ($arFeatures as $featureKey => $featureValue)
		{
			if (is_array($featureValue))
				$arResult = array_merge($arResult, $this->ExtractFeatures($featureValue));
			else
				$arResult[] = $featureValue;
		}
		return $arResult;
	}

	function InitStep()
	{
		$this->editionId = "Portal";
		$wizard =& $this->GetWizard();

		$this->SetStepID("portal_features_settings");
		$this->SetTitle(GetMessage("IFS_E_Portal"));
		$this->SetSubTitle(GetMessage("IFS_E_Portal"));
		$this->SetNextStep("social_features_settings");

		if ($wizard->GetVar("templateID") === "bitrix24")
			$this->SetPrevStep("select_template");
		else
			$this->SetPrevStep("select_theme");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		$arFeatures = array();
		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Portal"]);
		foreach ($arAllFeatures as $f)
		{
			if (isset($_POST["feature"]))
				$arFeatures[$f] = in_array($f, $_POST["feature"]);
			else
				$arFeatures[$f] = false;
		}

/*		if ($_POST["turn_social_on"] != "Y")
		{
			$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Social"]);
			foreach ($arAllFeatures as $f)
				$arFeatures[$f] = false;
		}*/

		CBXFeatures::ModifyFeaturesSettings(array("Portal" => array("F")), $arFeatures);

/*		if ($_POST["turn_social_on"] == "Y")
			$wizard->SetCurrentStep("social_features_settings");*/
	}

	function TestEnabledFeatures($arFeatures)
	{
		foreach ($arFeatures as $featureKey => $featureValue)
		{
			if (is_array($featureValue))
			{
				if ($this->TestEnabledFeatures($featureValue))
					return true;
			}
			else
			{
				if (CBXFeatures::IsFeatureEnabled($featureValue))
					return true;
			}
		}
		return false;
	}

	function PrintFeatures($arFeature, $level = 0)
	{
		$r = "";
		$level++;
		foreach ($arFeature as $featureKey => $featureValue)
		{
			if (is_array($featureValue))
			{
				$r .= '<tr><td>';

				if (!in_array($featureKey, Array("company", "messaging")))
					$r .= '<br>';

				$r .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $level);
				$r .= '<input type="checkbox" name="feature_tmp" value="Y" checked disabled>';

				if (in_array($featureKey, Array("messaging")))
					$r .= GetMessage('IFS_EF_'.$featureKey).'</td>';
				else
					$r .= '<b>'.GetMessage('IFS_EF_'.$featureKey).'</b></td>';

				$r .= '</tr>';

				$r .= $this->PrintFeatures($featureValue, $level);
			}
			else
			{
				if (in_array($featureValue, Array("Salary")) && LANGUAGE_ID != "ru")
				{
					$r .= '<tr><td><input style="display:none" type="checkbox" name="feature[]" id="id_f_'.$featureValue.'" value="'.$featureValue.'"></td></tr>';
					continue;
				}

				$r .= '<tr><td>';

				if (in_array($featureValue, Array("CommonDocuments", "WebMessenger")))
					$r .= '<br>';
				if (!in_array($featureValue, Array("WebMessenger")))
					$r .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $level);
				$r .= '<input type="checkbox" name="feature[]" id="id_f_'.$featureValue.'" value="'.$featureValue.'"'.(CBXFeatures::IsFeatureInstalled($featureValue) ? " checked" : "").'>';
				if (in_array($featureValue, Array("CommonDocuments", "WebMessenger")))
					$r .= '<label for="id_f_'.$featureValue.'"><b>'.GetMessage('IFS_EF_'.$featureValue).'</b></label></td>';
				else
					$r .= '<label for="id_f_'.$featureValue.'">'.GetMessage('IFS_EF_'.$featureValue).'</label></td>';

				$r .= '</tr>';
			}
		}
		return $r;
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$this->content .= '<table width="100%" cellspacing="2" cellpadding="0">';

		foreach($this->arBXFeaturesPrintable["Portal"] as $key=>$arAllFeatures)
		{
			$isEnabled = $this->TestEnabledFeatures(is_array($arAllFeatures) ? $arAllFeatures : array($arAllFeatures));

			if (is_array($arAllFeatures))
			{
				$this->content .= '<tr>';
				$this->content .= '<td><br>';
				$this->content .= '<input type="checkbox" name="turn_portal_on" id="id_turn_'.$key.'" value="Y"';
				if ($isEnabled)
					$this->content .= ' checked';
				$this->content .= ' onclick="TurnOnChange'.$key.'(this.checked)">';
				$this->content .= '<label for="id_turn_'.$key.'"><b>'.GetMessage('IFS_EF_'.$key).'</b></label></td>';
				$this->content .= '</tr>';
				$this->content .= $this->PrintFeatures($arAllFeatures);

				$this->content .= '<script>
					function TurnOnChange'.$key.'(val)
					{';
					foreach ($arAllFeatures as $f)
					{
						$this->content .= 'document.getElementById("id_f_'.$f.'").checked = val;';
					}
				$this->content .= '
					}</script>';
			}
			else
			{
				$this->content .= $this->PrintFeatures(array(0=>$arAllFeatures), -1);
			}
		}
		$this->content .= '</table>';
	}
}

class SocialFeaturesSettingsStep extends PortalFeaturesSettingsStep
{
	function InitStep()
	{
		$this->editionId = "Portal";

		$this->SetStepID("social_features_settings");
		$this->SetTitle(GetMessage("IFS_E_Social"));
		$this->SetSubTitle(GetMessage("IFS_E_Social"));
		$this->SetNextStep("communications_features_settings");
		$this->SetPrevStep("portal_features_settings");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		$arFeatures = array();
		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Social"]);
		foreach ($arAllFeatures as $f)
		{
			if (isset($_POST["feature"]))
				$arFeatures[$f] = in_array($f, $_POST["feature"]);
			else
				$arFeatures[$f] = false;
		}
		CBXFeatures::ModifyFeaturesSettings(array("Portal" => array("F")), $arFeatures);

//Ratings
		if (isset($_POST["Rating"]))
			COption::SetOptionString("main", "rating_vote_show", "Y");
		else
			COption::SetOptionString("main", "rating_vote_show", "N");

		if (isset($_POST["RatingTypeLike"]))
			COption::SetOptionString("main", "rating_vote_type", "like");
		else
			COption::SetOptionString("main", "rating_vote_type", "standart");

		$sRatingVoteType = COption::GetOptionString("main", "rating_vote_type", "standart");
		if ($sRatingVoteType == 'like')
		{
			if (isset($_POST["rating_text_like_y"]))
				COption::SetOptionString("main", "rating_text_like_y", htmlspecialcharsbx($_POST["rating_text_like_y"]));
			if (isset($_POST["rating_text_like_n"]))
				COption::SetOptionString("main", "rating_text_like_n", htmlspecialcharsbx($_POST["rating_text_like_n"]));
			if (isset($_POST["rating_text_like_d"]))
				COption::SetOptionString("main", "rating_text_like_d", htmlspecialcharsbx($_POST["rating_text_like_d"]));
			if (isset($_POST['like_style']))
				COption::SetOptionString("main", "rating_vote_template", $_POST['like_style']);
		}
		else
		{
			if (isset($_POST['dislike_style']))
				COption::SetOptionString("main", "rating_vote_template", $_POST['dislike_style']);
		}

		if (isset($_POST["SocialSearch"]))
			COption::SetOptionString("search", "use_social_rating", "Y");
		else
			COption::SetOptionString("search", "use_social_rating", "N");
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$isEnabled = $this->TestEnabledFeatures($this->arBXFeaturesPrintable["Social"]);

		$this->content .= '<table width="100%" cellspacing="2" cellpadding="0">';
		$this->content .= '<tr>';
		$this->content .= '<td>';
		$this->content .= '<input type="checkbox" name="turn_social_on" id="id_turn_social_on" value="Y"';
		if ($isEnabled)
			$this->content .= ' checked';
		$this->content .= ' onclick="TurnOnChange(this.checked)">';
		$this->content .= '<label for="id_turn_social_on"><b>'.GetMessage('IFS_E_Social').'</b></label></td>';
		$this->content .= '</tr>';
		$this->content .= $this->PrintFeatures($this->arBXFeaturesPrintable["Social"]);

//Ratings
		$RatingVoteShow = COption::GetOptionString("main", "rating_vote_show") == "" ? "Y" : COption::GetOptionString("main", "rating_vote_show");
		$RatinvVoteType = COption::GetOptionString("main", "rating_vote_type", "like");

		$this->content .= '<tr><td><br>';
	//	$this->content .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 1);
		$this->content .= '<input type="checkbox" name="Rating" id="Rating"';
		if ($RatingVoteShow == "Y")
			$this->content .= 'checked';
		$this->content .= ' onclick="TurnOnRating(this.checked)">';
		$this->content .= '<label for="Rating"><b>'.GetMessage("wiz_rating").'</b></label></td>';
		$this->content .= '<tr><td>';
		$this->content .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 1);
	//	$this->content .= '<input type="checkbox" name="RatingType" id="RatingType" checked disabled> ';
		$this->content .= '<label for="RatingType">'.GetMessage("wiz_rating_type").'</label></td>';
//TypeLike
		$arRatingTextLike = array(
			"RatingTextLikeY" => array(
				"name" => "rating_text_like_y",
				"value" => (COption::GetOptionString("main", "rating_text_like_y")=="") ? GetMessage("wiz_rating_text_likeY") : htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_y")),
				"label" => GetMessage("wiz_rating_label_likeY")
			),
			"RatingTextLikeN" => array(
				"name" => "rating_text_like_n",
				"value" => (COption::GetOptionString("main", "rating_text_like_n")=="") ? GetMessage("wiz_rating_text_likeN") : htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_n")),
				"label" => GetMessage("wiz_rating_label_likeN")
			),
			"RatingTextLikeD" => array(
				"name" => "rating_text_like_d",
				"value" => (COption::GetOptionString("main", "rating_text_like_d")=="") ? GetMessage("wiz_rating_text_likeD") : htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_d")),
				"label" => GetMessage("wiz_rating_label_likeD")
			),
		);

		$this->content .= '<tr><td style="padding:5px 0 0 50px">';
		//$this->content .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 2);
		$this->content .= '<span style="float:left"><input style="margin:0 3px 0 0; vertical-align:middle" type="radio" name="RatingTypeLike" id="RatingTypeLike"';
		if ($RatinvVoteType == "like" && $RatingVoteShow == "Y")
			$this->content .= "checked";
		$this->content .='>';
		$this->content .= '<label style="vertical-align:middle" for="RatingTypeLike">'.GetMessage('wiz_rating_type_like').'</label></span>';
		$this->content .= ' <span style="float:right; margin-top:3px; line-height:13px"><a href="javascript: LikeSettings();">'.GetMessage('wiz_rating_customize').'</a></span><div style="clear:both; height:0"></div></td></tr>';
		$likeCheck = COption::GetOptionString("main", "rating_vote_template") == "like" ? "checked" : "";
		$like_graphicCheck = COption::GetOptionString("main", "rating_vote_template") == "like_graphic" ? "checked" : "";
		if ($likeCheck == "" && $like_graphicCheck == "") $likeCheck = "checked";
		$this->content .= '
				<tr><td>
				<table id="likeTemplate" style="padding:7px 0 0 0; color:#666;display:none">
				<tr> <td style="padding-left:72px">'.GetMessage("wiz_rating_template").'</td></tr>
				<tr><td style="padding:5px 0 0 72px">
					<input type="radio" name="like_style" value="like" '.$likeCheck.'><img src ="'.$wizard->GetPath().'/images/'.LANGUAGE_ID.'/like.png">'.str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 1).'
					<input type="radio" name="like_style" value="like_graphic" '.$like_graphicCheck.'><img src ="'.$wizard->GetPath().'/images/'.LANGUAGE_ID.'/like_graphic.png">
				</td></tr> ';
		foreach($arRatingTextLike as $key=>$val)
			$this->content .= '
				<tr><td style="padding:7px 0 0 72px">'.$val['label'].'</td></tr>
				<tr><td style="padding:2px 0 7px 72px"><input type="text" name="'.$val['name'].'" value="'.$val['value'].'" size="22"></td></tr>';
		$this->content .= '</table></td></tr>';
//TypeDislike
		$this->content .= '<tr><td style="padding:5px 0 0 50px">';
	//	$this->content .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 2);
		$this->content .= '<span style="float:left"><input style="margin:0 3px 0 0; vertical-align:middle" type="radio" name="RatingTypeDislike" id="RatingTypeDislike"';
		if ($RatinvVoteType == "standart" && $RatingVoteShow == "Y")
			$this->content .= "checked";
		$this->content .='>';
		$this->content .= '<label style="vertical-align:middle" for="RatingTypeDislike">'.GetMessage('wiz_rating_type_dislike').'</label></span>';
		$this->content .= ' <span style="float:right; margin-top:3px; line-height:13px"><a href="javascript: DislikeSettings();">'.GetMessage('wiz_rating_customize').'</a></span><div style="clear:both; height:0"></div></td></tr>';
		$standartCheck = COption::GetOptionString("main", "rating_vote_template") == "standart" ? "checked" : "";
		$standart_textCheck = COption::GetOptionString("main", "rating_vote_template") == "standart_text" ? "checked" : "";
		if ($standartCheck == "" && $standart_textCheck == "") $standart_textCheck = "checked";

		$this->content .= '
				<tr><td>
				<table id="dislikeTemplate" style="padding:7px 0 0 0; color:#666; display:none">
				<tr><td style="padding:0 0 0 72px">'.GetMessage("wiz_rating_template").'</td> </tr>
				<tr><td style="padding:5px 0 0 72px">
					<input type="radio" name="dislike_style" value="standart_text"'.$standart_textCheck.'><img src ="'.$wizard->GetPath().'/images/'.LANGUAGE_ID.'/standart_text.png">'.str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 1).'
					<input type="radio" name="dislike_style" value="standart" '.$standartCheck.'><img src ="'.$wizard->GetPath().'/images/'.LANGUAGE_ID.'/standart.png">
				</td></tr>
				</table>
				</td></tr>';
		$SocialSearch = COption::GetOptionString("search", "use_social_rating", "Y");
		$this->content .= '<tr><td>';
		$this->content .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", 1);
		$this->content .= '<input type="checkbox" name="SocialSearch" id="SocialSearch"';
		if ($SocialSearch == "Y" && $RatingVoteShow == "Y")
			$this->content .= "checked";
		$this->content .= '>';
		$this->content .= '<label for="SocialSearch">'.GetMessage("wiz_social_search_label").'</label></td></tr>';
//RatingsEnd
		$this->content .= '</table>';

		$this->content .= '
			<script type="text/javascript">';

//For ratings
		$this->content .= '
			document.getElementById("RatingTypeLike").onclick=function(){
				document.getElementById("RatingTypeDislike").checked = this.checked ? false : true;
			}
			document.getElementById("RatingTypeDislike").onclick=function(){
				document.getElementById("RatingTypeLike").checked = this.checked ? false : true;
			}
			function LikeSettings()
			{
				document.getElementById("likeTemplate").style.display = (document.getElementById("likeTemplate").style.display == "none") ? "block" : "none";
			}
			function DislikeSettings()
			{
				document.getElementById("dislikeTemplate").style.display = (document.getElementById("dislikeTemplate").style.display == "none") ? "block" : "none";
			}';

		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Social"]);
		if (!$isEnabled)
		{
			foreach ($arAllFeatures as $f)
				$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = true;';
		}
		$this->content .= '
			function TurnOnChange(val)
			{';
		foreach ($arAllFeatures as $f)
		{
			if ($f != "WebMessenger")
			{
				//$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = !val;';
				$this->content .= 'document.getElementById("id_f_'.$f.'").checked = val;';
			}
		}
		$this->content .= '
			}
			function TurnOnRating(val)
			{';
			$this->content .= 'document.getElementById("RatingTypeLike").disabled = !val;';
			$this->content .= 'document.getElementById("RatingTypeLike").checked = val;';
			$this->content .= 'document.getElementById("RatingTypeDislike").disabled = !val;';
			$this->content .= 'if (!val) document.getElementById("RatingTypeDislike").checked = val;';
			$this->content .= 'document.getElementById("SocialSearch").checked = val;';
			$this->content .= '
			}
			</script>';
	}
}

class CommunicationsFeaturesSettingsStep extends PortalFeaturesSettingsStep
{
	function InitStep()
	{
		$this->editionId = "Communications";

		$this->SetStepID("communications_features_settings");
		$this->SetTitle(GetMessage("IFS_E_Communications"));
		$this->SetSubTitle(GetMessage("IFS_E_Communications"));
		$this->SetNextStep("enterprise_features_settings");
		$this->SetPrevStep("social_features_settings");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Communications"];

		if ($arFeatureSys["TYPE"] == "F" || $arFeatureSys["TYPE"] == "D" || $arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
		{
			$arFeatures = array();
			$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Communications"]);
			foreach ($arAllFeatures as $f)
			{
				if (isset($_POST["feature"]))
					$arFeatures[$f] = in_array($f, $_POST["feature"]);
				else
					$arFeatures[$f] = false;
			}
			$arEditions = array();
			if ($arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
				$arEditions["Communications"] = array("D", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

			COption::SetOptionString("main", "wizard_install_extranet", (!CBXFeatures::IsFeatureEnabled("Extranet") && $arFeatures["Extranet"] === true ? "Y" : "N"));

			CBXFeatures::ModifyFeaturesSettings($arEditions, $arFeatures);
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Communications"];

		if (!IsModuleInstalled("video"))
		{
			$key = array_search('VideoConference', $this->arBXFeaturesPrintable["Communications"]);
			if ($key !== false)
				unset($this->arBXFeaturesPrintable["Communications"][$key]);
		}

		$isEnabled = $this->TestEnabledFeatures($this->arBXFeaturesPrintable["Communications"]);

		$this->content .= '<table width="100%" cellspacing="2" cellpadding="0">';

		if ($arFeatureSys["TYPE"] != "F")
		{
			$this->content .= '<tr><td>';
			if ($arFeatureSys["TYPE"] != "D")
				$this->content .= '<input type="button" name="test_feature" id="id_test_feature" value="'.GetMessage("IFS_BUTTON_TEST").'" onclick="EditionChange()">';
			else
				$this->content .= '<input type="button" name="test_feature" value="'.($arFeatureSys["EXPIRED"] ? GetMessage("IFS_DEMO_MESSAGE1") : str_replace("#TIME#", $arFeatureSys["TRY_DAYS_COUNT"], GetMessage("IFS_DEMO_MESSAGE2"))).'" disabled>';
			$this->content .= '<input type="hidden" id="id_test_feature_on" name="test_feature_on" value="N">';
			$this->content .= ' <input type="button" name="buy_feature" value="'.GetMessage("IFS_BUTTON_BUY").'" onclick="window.open(\''.GetMessage("IFS_BUTTON_BUY_URL").'\', \'BUYINFO\')">';
			$this->content .= '</td></tr>';
			$this->content .= '<tr><td><br></td></tr>';
		}

		$this->content .= '<tr>';
		$this->content .= '<td>';
		$this->content .= '<input type="checkbox" name="turn_communications_on" id="id_turn_communications_on" value="Y"';
		if ($isEnabled)
			$this->content .= ' checked';
		$this->content .= ' onclick="TurnOnChange(this.checked)">';
		$this->content .= '<label for="id_turn_communications_on"><b>'.GetMessage('IFS_E_Communications').'</b></label></td>';
		$this->content .= '</tr>';

		$this->content .= $this->PrintFeatures($this->arBXFeaturesPrintable["Communications"]);
		$this->content .= '</table>';

		$this->content .= '
			<script type="text/javascript">';
		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Communications"]);
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"] || !$isEnabled)
		{
			foreach ($arAllFeatures as $f)
				$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = true;';
		}
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"])
			$this->content .= 'document.getElementById("id_turn_communications_on").disabled = true;';

		$this->content .= '
			function TurnOnChange(val)
			{';
		foreach ($arAllFeatures as $f)
		{
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = !val;';
			$this->content .= 'document.getElementById("id_f_'.$f.'").checked = val;';
		}
		$this->content .= '
			}';

		$this->content .= '
			function EditionChange()
			{';
		foreach ($arAllFeatures as $f)
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = false;';
		$this->content .= 'document.getElementById("id_test_feature").disabled = true;
				document.getElementById("id_test_feature_on").value = "Y";
				document.getElementById("id_turn_communications_on").disabled = false;
				document.getElementById("id_turn_communications_on").value = "Y";
			}
			</script>';
	}
}

class EnterpriseFeaturesSettingsStep extends PortalFeaturesSettingsStep
{
	function InitStep()
	{
		$this->editionId = "Enterprise";

		$this->SetStepID("enterprise_features_settings");
		$this->SetTitle(GetMessage("IFS_E_Enterprise"));
		$this->SetSubTitle(GetMessage("IFS_E_Enterprise"));
		$this->SetNextStep("holding_features_settings");
		$this->SetPrevStep("communications_features_settings");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Enterprise"];

		if ($arFeatureSys["TYPE"] == "F" || $arFeatureSys["TYPE"] == "D" || $arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
		{
			$arFeatures = array();
			$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Enterprise"]);
			foreach ($arAllFeatures as $f)
			{
				if (isset($_POST["feature"]))
					$arFeatures[$f] = in_array($f, $_POST["feature"]);
				else
					$arFeatures[$f] = false;
			}
			$arEditions = array();
			if ($arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
			{
				$arEditions["Enterprise"] = array("D", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				if ($arFeaturesSys["Communications"]["TYPE"] != "F" && $arFeaturesSys["Communications"]["TYPE"] != "D")
				{
					$arEditions["Communications"] = array("D", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
					$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Communications"]);
					foreach ($arAllFeatures as $f)
						$arFeatures[$f] = false;
				}
			}

			CBXFeatures::ModifyFeaturesSettings($arEditions, $arFeatures);
		}

		$wizard->SetVar("install_acct_list", ($_POST["install_acct_list"] && ($_POST["install_acct_list"] == "Y")) ? "Y" : "N");
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Enterprise"];

		$isEnabled = $this->TestEnabledFeatures($this->arBXFeaturesPrintable["Enterprise"]);

		$this->content .= '<table width="100%" cellspacing="2" cellpadding="0">';

		if ($arFeatureSys["TYPE"] != "F")
		{
			$this->content .= '<tr><td>';
			if ($arFeatureSys["TYPE"] != "D")
				$this->content .= '<input type="button" name="test_feature" id="id_test_feature" value="'.GetMessage("IFS_BUTTON_TEST").'" onclick="EditionChange()">';
			else
				$this->content .= '<input type="button" name="test_feature" value="'.($arFeatureSys["EXPIRED"] ? GetMessage("IFS_DEMO_MESSAGE1") : str_replace("#TIME#", $arFeatureSys["TRY_DAYS_COUNT"], GetMessage("IFS_DEMO_MESSAGE2"))).'" disabled>';
			$this->content .= '<input type="hidden" name="test_feature_on" id="id_test_feature_on" value="N">';
			$this->content .= ' <input type="button" name="buy_feature" value="'.GetMessage("IFS_BUTTON_BUY").'" onclick="window.open(\''.GetMessage("IFS_BUTTON_BUY_URL").'\', \'BUYINFO\')">';
			$this->content .= '</td></tr>';
			$this->content .= '<tr><td><br></td></tr>';
		}

		$this->content .= '<tr>';
		$this->content .= '<td>';
		$this->content .= '<input type="checkbox" name="turn_enterprise_on" id="id_turn_enterprise_on" value="Y"';
		if ($isEnabled)
			$this->content .= ' checked';
		$this->content .= ' onclick="TurnOnChange(this.checked)">';
		$this->content .= '<label for="id_turn_enterprise_on"><b>'.GetMessage('IFS_E_Enterprise').'</b></label></td>';
		$this->content .= '</tr>';
		$this->content .= $this->PrintFeatures($this->arBXFeaturesPrintable["Enterprise"]);

		$this->content .= '<tr><td><br>';
		$this->content .= '<input type="checkbox" name="install_acct_list" id="id_install_acct_list" value="Y">';
		$this->content .= '<label for="id_install_acct_list">'.GetMessage("wiz_install_acct_list").'</label></td>';

		$this->content .= '</table>';

		$this->content .= '
			<script type="text/javascript">';
		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Enterprise"]);
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"] || !$isEnabled)
		{
			foreach ($arAllFeatures as $f)
				$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = true;';
		}
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"])
			$this->content .= 'document.getElementById("id_turn_enterprise_on").disabled = true;';
		$this->content .= '
			function TurnOnChange(val)
			{';
		foreach ($arAllFeatures as $f)
		{
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = !val;';
			if (!in_array($f, array("Analytics", "Controller")))
				$this->content .= 'document.getElementById("id_f_'.$f.'").checked = val;';
		}
		$this->content .= '
			}';
		$this->content .= '
			function EditionChange()
			{';
		foreach ($arAllFeatures as $f)
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = false;';
		$this->content .= 'document.getElementById("id_test_feature").disabled = true;
				document.getElementById("id_test_feature_on").value = "Y";
				document.getElementById("id_turn_enterprise_on").disabled = false;
				document.getElementById("id_turn_enterprise_on").value = "Y";
			}
			</script>';
	}
}

class HoldingFeaturesSettingsStep extends PortalFeaturesSettingsStep
{
	function InitStep()
	{
		$this->editionId = "Holding";

		$this->SetStepID("holding_features_settings");
		$this->SetTitle(GetMessage("IFS_E_Holding"));
		$this->SetSubTitle(GetMessage("IFS_E_Holding"));
		$this->SetNextStep("site_settings");
		$this->SetPrevStep("enterprise_features_settings");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Holding"];

		if ($arFeatureSys["TYPE"] == "F" || $arFeatureSys["TYPE"] == "D" || $arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
		{
			$arFeatures = array();
			$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Holding"]);
			foreach ($arAllFeatures as $f)
			{
				if (isset($_POST["feature"]))
					$arFeatures[$f] = in_array($f, $_POST["feature"]);
				else
					$arFeatures[$f] = false;
			}
			$arEditions = array();
			if ($arFeatureSys["TYPE"] != "F" && $arFeatureSys["TYPE"] != "D" && $_POST["test_feature_on"] == "Y")
			{
				$arEditions["Holding"] = array("D", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
				if ($arFeaturesSys["Communications"]["TYPE"] != "F" && $arFeaturesSys["Communications"]["TYPE"] != "D")
				{
					$arEditions["Communications"] = array("D", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
					$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Communications"]);
					foreach ($arAllFeatures as $f)
						$arFeatures[$f] = false;
				}
			}

			CBXFeatures::ModifyFeaturesSettings($arEditions, $arFeatures);
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arFeaturesSys = CBXFeatures::GetFeaturesList();
		$arFeatureSys = $arFeaturesSys["Holding"];

		$isEnabled = $this->TestEnabledFeatures($this->arBXFeaturesPrintable["Holding"]);

		$this->content .= '<table width="100%" cellspacing="2" cellpadding="0">';

		if ($arFeatureSys["TYPE"] != "F")
		{
			$this->content .= '<tr><td>';
			if ($arFeatureSys["TYPE"] != "D")
				$this->content .= '<input type="button" name="test_feature" id="id_test_feature" value="'.GetMessage("IFS_BUTTON_TEST").'" onclick="EditionChange()">';
			else
				$this->content .= '<input type="button" name="test_feature" value="'.($arFeatureSys["EXPIRED"] ? GetMessage("IFS_DEMO_MESSAGE1") : str_replace("#TIME#", $arFeatureSys["TRY_DAYS_COUNT"], GetMessage("IFS_DEMO_MESSAGE2"))).'" disabled>';
			$this->content .= '<input type="hidden" name="test_feature_on" id="id_test_feature_on" value="N">';
			$this->content .= ' <input type="button" name="buy_feature" value="'.GetMessage("IFS_BUTTON_BUY").'" onclick="window.open(\''.GetMessage("IFS_BUTTON_BUY_URL").'\', \'BUYINFO\')">';
			$this->content .= '</td></tr>';
			$this->content .= '<tr><td><br></td></tr>';
		}

		$this->content .= '<tr>';
		$this->content .= '<td>';
		$this->content .= '<input type="checkbox" name="turn_holding_on" id="id_turn_holding_on" value="Y"';
		if ($isEnabled)
			$this->content .= ' checked';
		$this->content .= ' onclick="TurnOnChange(this.checked)">';
		$this->content .= '<label for="id_turn_holding_on"><b>'.GetMessage('IFS_E_Holding').'</b></label></td>';
		$this->content .= '</tr>';
		$this->content .= $this->PrintFeatures($this->arBXFeaturesPrintable["Holding"]);
		$this->content .= '</table>';

		$this->content .= '
			<script type="text/javascript">';
		$arAllFeatures = $this->ExtractFeatures($this->arBXFeaturesPrintable["Holding"]);
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"] || !$isEnabled)
		{
			foreach ($arAllFeatures as $f)
				$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = true;';
		}
		if ($arFeatureSys["TYPE"] != "D" && $arFeatureSys["TYPE"] != "F" || $arFeatureSys["TYPE"] == "D" && $arFeatureSys["EXPIRED"])
			$this->content .= 'document.getElementById("id_turn_holding_on").disabled = true;';
		$this->content .= '
			function TurnOnChange(val)
			{';
		foreach ($arAllFeatures as $f)
		{
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = !val;';
			if (!in_array($f, array("Analytics", "Controller")))
				$this->content .= 'document.getElementById("id_f_'.$f.'").checked = val;';
		}
		$this->content .= '
			}';
		$this->content .= '
			function EditionChange()
			{';
		foreach ($arAllFeatures as $f)
			$this->content .= 'document.getElementById("id_f_'.$f.'").disabled = false;';
		$this->content .= 'document.getElementById("id_test_feature").disabled = true;
				document.getElementById("id_test_feature_on").value = "Y";
				document.getElementById("id_turn_holding_on").disabled = false;
				document.getElementById("id_turn_holding_on").value = "Y";
			}
			</script>';
	}
}

class SiteSettingsStep extends CWizardStep
{
	function GetFileContentImgSrc($filename, $default_value)
	{
		if (file_exists($filename) && ($siteLogo = file_get_contents($filename)) !== false && strlen($siteLogo) > 0)
		{
			if (strpos($siteLogo, "default_logo") !== false)
				$siteLogo = $default_value;
			else if(preg_match("/src\s*=\s*(\S+)[ \t\r\n\/>]*/i", $siteLogo, $reg))
				$siteLogo = "/".trim($reg[1], "\"' />");
			else
				$siteLogo = $default_value;
		}
		else
			$siteLogo = $default_value;
		return $siteLogo;
	}

	function LDAPServerExists()
	{
		if (!function_exists("ldap_connect") || !CModule::IncludeModule("ldap"))
			return false;

		$rsData = CLdapServer::GetList(Array(), Array("ACTIVE" => "Y"));
		return ($rsData->Fetch());
	}

	function InitStep()
	{
		$this->SetStepID("site_settings");
		$this->SetTitle(GetMessage("wiz_settings"));
		$this->SetSubTitle(GetMessage("wiz_settings"));
		$this->SetNextStep("data_install");

		$wizard =& $this->GetWizard();
		$siteDef = 'N';
		$siteID = $wizard->GetVar("siteID");
		$rsSites = CSite::GetByID($siteID);
		if ($arSite = $rsSites->Fetch())
			$siteDef = $arSite["DEF"];

		if ($_SERVER["PHP_SELF"] == "/index.php" || NON_INTRANET_EDITION || $siteDef == 'N')
		{
			if ($wizard->GetVar("templateID") === "bitrix24")
				$prevStep = "select_template";
			else
				$prevStep = "select_theme";
		}
		else
			$prevStep = "holding_features_settings";

		$this->SetPrevStep($prevStep);
		$this->SetNextCaption(GetMessage("wiz_install"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		if($wizard->GetVar("templateID")=="light")
		{
			$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));

			$rsSites = CSite::GetByID($siteID);
			if ($arSite = $rsSites->Fetch())
				$siteDir = $arSite["DIR"] ? $arSite["DIR"] : "/site_".$siteID."/";
			else
				$siteDir = "/";

			$themeID = $wizard->GetVar("light_themeID");
			$siteLogo = $this->GetFileContentImgSrc($_SERVER['DOCUMENT_ROOT'].$siteDir."include/company_name.php", "/bitrix/wizards/bitrix/portal/images/templates/light/themes/".$themeID."/images/".LANGUAGE_ID."/logo.jpg");
		}
		elseif ($wizard->GetVar("templateID")=="bitrix24")
		{
			$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));

			$rsSites = CSite::GetByID($siteID);
			if ($arSite = $rsSites->Fetch())
				$siteDir = $arSite["DIR"] ? $arSite["DIR"] : "/site_".$siteID."/";
			else
				$siteDir = "/";

			$siteLogo = $this->GetFileContentImgSrc($_SERVER['DOCUMENT_ROOT'].$siteDir."include/company_name.php", "");
		}
		else
			$siteLogo = COption::GetOptionString("main", "wizard_site_logo", "", $wizard->GetVar("siteID"));

		$wizard->SetDefaultVars(
			Array(
				"siteLogo" => $siteLogo,
				"siteName" => COption::GetOptionString("main", "site_name", GetMessage("wiz_slogan"), $wizard->GetVar("siteID")),
				"siteFolderDep" =>  COption::GetOptionString("main", "wizard_site_folder", $siteDir, $wizard->GetVar("siteID")),
				"allowGuests" => COption::GetOptionString("main", "wizard_allow_guests", "N", $wizard->GetVar("siteID")),
				"allowGroup" => COption::GetOptionString("main", "wizard_allow_group", "N", $wizard->GetVar("siteID")),
				"allowRegistration" => COption::GetOptionString("main", "new_user_registration", "N", $wizard->GetVar("siteID")),
				"installDemoData" => COption::GetOptionString("main", "wizard_demo_data", "Y", $wizard->GetVar("siteID")),
				"useSiteLogo" => COption::GetOptionString("main", "wizard_use_site_logo", "Y", $wizard->GetVar("siteID"))
			)
		);

		if ($_SERVER["PHP_SELF"] == "/index.php" && !NON_INTRANET_EDITION)
			$wizard->SetDefaultVar("allowSocial", "N");

		if (function_exists("ldap_connect") && IsModuleInstalled("ldap") && $_SERVER["PHP_SELF"] == "/index.php")
			$wizard->SetDefaultVar("allowLDAP", "N");
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();
		$site_id = $wizard->GetVar("siteID");

		if($wizard->GetVar("templateID")=="light")
		{
			$res = $this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 72, "max_width" => 285, "make_preview" => "Y"));
		}
		elseif($wizard->GetVar("templateID")=="bitrix24")
		{
			$this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 55, "max_width" => 222, "make_preview" => "Y"));
		}
		else
		{
			$this->SaveFile("siteLogo", Array("extensions" => "gif,jpg,jpeg,png", "max_height" => 80, "max_width" => 90, "make_preview" => "Y"));
			COption::SetOptionString("main", "wizard_site_logo", "", false, $site_id);
		}

		if ($wizard->IsNextButtonClick())
		{
			$rsSites = CSite::GetByID($site_id);
			if ($arSite = $rsSites->Fetch())
				$siteDir = $arSite["DIR"];

			$firstStep = COption::GetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), false, $wizard->GetVar("siteID"));
			if (!$firstStep && $site_id != "s1")
			{
				$siteFolder = $wizard->GetVar("siteFolderDep");

				if (strlen(trim($siteFolder, " /")) == 0 || !preg_match('#^/(\w+|_)/$#', $siteFolder))
				{
					$this->SetError(GetMessage("wiz_site_folder_error"));
					return;
				}
				else
				{
					$rsSites = CSite::GetList($by="sort", $order="desc", array());
					while($arSite = $rsSites->Fetch())
					{
						if ($arSite["ID"] == $site_id)
							continue;

						if (trim($arSite["DIR"], "/") == trim($siteFolder, "/"))
						{
							$this->SetError(GetMessage("wiz_site_folder_already_exists"));
							return;
						}
					}
					COption::SetOptionString("main", "wizard_site_folder", $siteFolder, false, $site_id);
				}
			}
			else
			{
				COption::SetOptionString("main", "wizard_site_folder", $siteDir, false, $site_id);
			}

			COption::SetOptionString("main", "site_name", $wizard->GetVar("siteName"), false, $site_id);

			$allowGuests = $wizard->GetVar("allowGuests");

			$allowGroup = $wizard->GetVar("allowGroup");

			COption::SetOptionString("main", "wizard_allow_group", $allowGroup == "Y" ? "Y" : "N", false, $site_id);

			COption::SetOptionString("main", "wizard_demo_data", "N", false, $site_id);

			$site_id = $wizard->GetVar("siteID");
			if($site_id == 's1')
			{
				WizardServices::SetFilePermission(Array(SITE_ID, "/" ), Array("2" => ($allowGuests == "Y" ? "R" : "D")));
				COption::SetOptionString("main", "wizard_allow_guests", $allowGuests == "Y" ? "Y" : "N", false, $site_id);

				$allowLDAP = $wizard->GetVar("allowLDAP");
				if ($allowLDAP == "Y" && function_exists("ldap_connect") && IsModuleInstalled("ldap"))
					$wizard->SetCurrentStep("ldap_settings");

				$allowRegistration = $wizard->GetVar("allowRegistration");
				COption::SetOptionString("main", "new_user_registration", $allowRegistration == "Y" ? "Y" : "N", false, "");
			}


			if($wizard->GetVar("templateID")=="light" || $wizard->GetVar("templateID")=="bitrix24")
			{
				$useSiteLogo = $wizard->GetVar("useSiteLogo");
				COption::SetOptionString("main", "wizard_use_site_logo", $useSiteLogo == "Y" ? "Y" : "N", false, $site_id);
			}
		}
	}

	function TestEnabledFeatures($arFeatures)
	{
		foreach ($arFeatures as $featureKey => $featureValue)
		{
			if (is_array($featureValue))
			{
				if ($this->TestEnabledFeatures($featureValue, $arBXFeatures))
					return true;
			}
			else
			{
				if (CBXFeatures::IsFeatureInstalled($featureValue))
					return true;
			}
		}
		return false;
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$this->content .= '<table width="100%" cellspacing="0" cellpadding="0">';

		$this->content .= '<tr><td>';
		$this->content .= '<label for="site-name">'.GetMessage("wiz_company_name").'</label><br />';
		$this->content .= $this->ShowInputField("text", "siteName", Array("id" => "site-name", "style" => "width:90%"));
		$this->content .= '</tr></td>';

		$this->content .= '<tr><td><br /></td></tr>';

		$siteLogo = $wizard->GetVar("siteLogo", true);

		if($wizard->GetVar("templateID")=="light" || $wizard->GetVar("templateID")=="bitrix24")
		{
			$this->content .= '<tr><td>';

			$this->content .= <<<JS
				<script type="text/javascript">
						function OnSiteLogoClick(checked)
						{
								var siteLogoImage = document.getElementById("site-logo-image");
								var siteLogoUpload = document.getElementById("site-logo-upload");

								siteLogoUpload.disabled = !checked;

								if (siteLogoImage)
									siteLogoImage.className = checked ? "" : "disabled";
						}
				</script>
JS;

			$this->content .= $this->ShowCheckboxField("useSiteLogo", "Y", Array("id" => "use-site-logo", "onclick" => "OnSiteLogoClick(this.checked)"));
			$this->content .= ' <label for="use-site-logo">'.GetMessage("wiz_company_logo").'</label>';

			if (strlen($siteLogo) > 0)
				$this->content .= "<div style='margin: 5px 0 5px 0;'>".CFile::ShowImage($siteLogo, 0, 0, "border=0 id=\"site-logo-image\"".($wizard->GetVar("useSiteLogo", true) != "Y" ? " class=\"disabled\"" : ""), "", true)."</div>";
			else
				$this->content .= "<br />";

			$arParams = Array("id" => "site-name", "style" => "width:90%", "id" => "site-logo-upload", "show_file_info" => "N");
			if ($wizard->GetVar("useSiteLogo", true) != "Y")
				$arParams["disabled"] = "disabled";

			$this->content .= $this->ShowFileField("siteLogo", $arParams);
			$this->content .= '</td></tr>';
		}
		else
		{
			$this->content .= '<tr><td>';
			$this->content .= '<label for="site-logo">'.GetMessage("wiz_company_logo").'</label><br />';
			$this->content .= $this->ShowFileField("siteLogo", Array("show_file_info" => "N", "id" => "site-logo"));
			$this->content .= "<br />".CFile::ShowImage($siteLogo, 200, 200, "border=0", "", true);
			$this->content .= '</td></tr>';
		}

		$this->content .= '<tr><td><div class="line"></div></td></tr>';

		$firstStep = COption::GetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), false, $wizard->GetVar("siteID"));
//b24 to cp		
		if (file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu.php"))
			$firstStep = "Y";

		define("WIZARD_FIRST_INSTAL", $firstStep);       	
		if(WIZARD_FIRST_INSTAL == 'Y' || ($_SERVER["PHP_SELF"] != "/index.php" && !COption::GetOptionString("main", "wizard_new_2011", false) &&  $wizard->GetVar("siteID") == 's1'))
		{
			//$this->content .= '<tr><td style="padding-bottom:3px;">';
			$wizard->SetDefaultVar("installDemoData", "N");
			/*$this->content .= $this->ShowCheckboxField("installDemoData", "Y", Array("id" => "install-demo-data", "onclick" => "OnClickDemoData(this)"));
			$this->content .= '<label for="install-demo-data"><b>'.GetMessage("wiz_structure_data").'</b></label><br />';
			$this->content .= '</td></tr>';
			$this->content .= '<tr><td style="padding-bottom:3px;">';
			$wizard->SetDefaultVar("installMobile", "Y");
			$this->content .= $this->ShowCheckboxField("installMobile", "Y", Array("id" => "install-mobile"));
			$this->content .= '<label for="install-mobile">'.GetMessage("wiz_use_mobile").'</label><br />';
			$this->content .= '</td></tr>'; */

		}
		else
		{
			if($_SERVER["PHP_SELF"] == "/index.php")
			{
				$this->content .= '<tr><td style="padding-bottom:3px;">';
				$wizard->SetDefaultVar("installStructureData", "Y");
				$this->content .= $this->ShowCheckboxField("installStructureData", "Y", Array("id" => "install-structure-data", "onclick" => "OnClickDemoData(this)"));
				$this->content .= '<label for="install-structure-data"><b>'.GetMessage("wiz_demo_structure").'</b></label><br />';
				$this->content .= '</td></tr>';

				/*$this->content .= '<tr><td style="padding-bottom:3px;">';
				$wizard->SetDefaultVar("installMobile", "Y");
				$this->content .= $this->ShowCheckboxField("installMobile", "Y", Array("id" => "install-mobile"));
				$this->content .= '<label for="install-mobile">'.GetMessage("wiz_install_mobile").'</label><br />';
				$this->content .= '</td></tr>'; */
			}

			$this->content .= $this->ShowHiddenField("installDemoData","Y");

			if (!NON_INTRANET_EDITION)
			{
				if ($_SERVER["PHP_SELF"] == "/index.php")
				{
					$this->content .= '<tr><td style="padding-bottom:3px;">';
					$this->content .= $this->ShowCheckboxField("allowSocial", "Y", Array("id" => "allow_social"));
					$this->content .= '<label for="allow_social">'.GetMessage("wiz_allow_social").'</label><br />';
					$this->content .= '</td></tr>';
				}
				else
				{
					$TestEnabledFeatures = $this->TestEnabledFeatures(
						array(
							"socnet" => array(
								"Friends",
								"PersonalFiles",
								//"PersonalBlog",
								"PersonalPhoto",
								"PersonalForum",
							),
							/*"talk" => array(
								"Blog",
								"Forum",
								"Gallery",
								"Board",
							),   */
						)
					);
					if($TestEnabledFeatures)
						$this->content .= $this->ShowHiddenField("allow_social","Y");
					else
						$this->content .= $this->ShowHiddenField("allow_social","N");
				}
			}
		}

		$site_id = $wizard->GetVar("siteID");

		if ($_SERVER["PHP_SELF"] != "/index.php" && WIZARD_FIRST_INSTAL !== "Y" && $site_id != 's1')
		{
			$this->content .= '<tr><td><br /></td></tr>';
			$this->content .= '<tr><td>';
			$this->content .= '<label for="site-folder">'.GetMessage("wiz_site_folder").'</label><br />';
			$this->content .= $this->ShowInputField("text", "siteFolderDep", Array("id" => "site-folder", "style" => "width:90%"));
			$this->content .= '</tr></td>';

			$arFilter = Array('GLOBAL_ACTIVE' => 'Y', 'ACTIVE '=> 'Y', 'IBLOCK_CODE' => 'departments');
			$db_list = CIBlockSection::GetList(array("left_margin"=>"asc"), $arFilter);
			while ($ar_result = $db_list->GetNext())
				$arDepartament[] = $ar_result;

			if (count($arDepartament) > 0)
			{
				$this->content .= '<tr><td>&nbsp;</td></tr>';
				$this->content .= '<tr><td style="padding-bottom:3px;">';
				$this->content .= '<b>'.GetMessage("wiz_select_departament").'</b>';
				$this->content .= '</td></tr>';
				$this->content .= '<tr><td style="padding-bottom:3px;">';
				$this->content .= '<select size="15" name="__wiz_departamentID">';
				foreach ($arDepartament as $arSection)
				{
					$CURRENT_DEPTH = $arSection["DEPTH_LEVEL"];

					$this->content .= '<option value="'.$arSection["ID"].'">';
					$this->content .=  str_repeat("&nbsp;.&nbsp;", $CURRENT_DEPTH);
					$this->content .=  $arSection["NAME"];
					$this->content .= '</option>';

				}
				$this->content .= '</select>';
				$this->content .= '</td></tr>';

			}
		}

		/*if ($_SERVER["PHP_SELF"] != "/index.php" && $site_id != 's1')
		{
			$this->content .= '<tr><td style="padding-bottom:3px;">';
			$this->content .= $this->ShowCheckboxField("allowGroup", "Y", Array("id" => "allow-group"));
			$this->content .= '<label for="allow-group">'.GetMessage("wiz_allow_group").'</label><br />';
			$this->content .= '</td></tr>';
		}  */

		if ($site_id == 's1')
		{
			$this->content .= '<tr><td>&nbsp;</td></tr>';
			$this->content .= '<tr><td style="padding-bottom:5px;"><b>'.GetMessage("wiz_demo_access").'</b></td></tr>';
			$this->content .= '<tr><td style="padding-bottom:3px;">';
			$this->content .= $this->ShowCheckboxField("allowGuests", "Y", Array("id" => "allow-guests", "onclick" => "OnClickAllowGuests(this)"));
			$this->content .= '<label for="allow-guests">'.GetMessage("wiz_allow_anonym").'</label><br />';
			$this->content .= '</td></tr>';

			$this->content .= '<tr><td style="padding-bottom:3px;">';
			$this->content .= $this->ShowCheckboxField("allowRegistration", "Y", Array("id" => "allow-registration", "onclick" => "OnClickAllowRegister(this)"));
			$this->content .= '<label for="allow-registration">'.GetMessage("wiz_allow_register").'</label><br />';
			$this->content .= '</td></tr>';


			$this->content .= '<tr><td style="padding-bottom:3px;">';

			if (file_exists($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/ldap/include.php'))
			{
				if (!function_exists("ldap_connect"))
				{
					$this->content .= $this->ShowCheckboxField("allowLDAP", "Y", Array("id" => "allow-ldap", "disabled" => "disabled"));
					$this->content .= '<label style="opacity:0.4" disabled="disabled" for="allow-ldap">'.GetMessage("wiz_allow_ldap").'</label><br />';
					$this->content .= '(<span style="color:red">'.GetMessage("wiz_allow_ldap_warn").'</span>)';
				}
				elseif (!IsModuleInstalled("ldap"))
				{
					$this->content .= $this->ShowCheckboxField("allowLDAP", "Y", Array("id" => "allow-ldap", "disabled" => "disabled"));
					$this->content .= '<label style="opacity:0.4" disabled="disabled" for="allow-ldap">'.GetMessage("wiz_allow_ldap").'</label><br />';
					$this->content .= '(<span style="color:red">'.GetMessage("wiz_ldap_require1", array("#LANGUAGE_ID#"=>LANGUAGE_ID)).'</span>)';
				}
				elseif ($this->LDAPServerExists())
				{
					$this->content .= "&nbsp;";
				}
				else
				{
					$this->content .= $this->ShowCheckboxField("allowLDAP", "Y", Array("id" => "allow-ldap", "onclick" => "OnClickAllowLdap(this)"));
					$this->content .= '<label for="allow-ldap">'.GetMessage("wiz_allow_ldap1").'</label>';
				}
			}
		}

		$this->content .= '</td></tr>';
		$this->content .= '</table>';


		$formName = $wizard->GetFormName();
		$installCaption = $this->GetNextCaption();
		$nextCaption = GetMessage("NEXT_BUTTON");

		if(WIZARD_IS_RERUN===true)
		{
			$this->content .= '
				<script type="text/javascript">
				function OnClickDemoData(checkbox)
				{
					if (checkbox.checked)
						checkbox.checked=confirm("'.CUtil::JSEscape(GetMessage("wiz_galochka_structure")).'");
				}';
		}
		else
		{
			$this->content .= '
				<script type="text/javascript">
				function OnClickDemoData(checkbox)
				{
					if (!checkbox.checked)
					{
						alert("'.CUtil::JSEscape(GetMessage("wiz_galochka")).'");';
						if(WIZARD_FIRST_INSTAL == 'Y' || ($_SERVER["PHP_SELF"] != "/index.php" && !COption::GetOptionString("main", "wizard_new_2011", false) &&  $wizard->GetVar("siteID") == 's1'))
						{
							$this->content .= 'if(document.getElementById("install-mobile"))
							{
								document.getElementById("install-mobile").checked = false;
								document.getElementById("install-mobile").disabled = true;
							}';
						}
					$this->content .= '	}';
					if(WIZARD_FIRST_INSTAL == 'Y' || ($_SERVER["PHP_SELF"] != "/index.php" && !COption::GetOptionString("main", "wizard_new_2011", false) &&  $wizard->GetVar("siteID") == 's1'))
					{
						$this->content .= 'else
						{
							if(document.getElementById("install-mobile"))
							{
								document.getElementById("install-mobile").checked = true;
								document.getElementById("install-mobile").disabled = false;
							}
						}';
					}
				$this->content .= '}';
		}

		$this->content .= '
			function OnClickAllowGuests(checkbox)
			{
				if(checkbox.checked)
					alert("'.CUtil::JSEscape(GetMessage("wiz_demo_allow_anon_alert")).'");
			}

			function OnClickAllowRegister(checkbox)
			{
				if(checkbox.checked)
					alert("'.CUtil::JSEscape(GetMessage("wiz_demo_allow_reg_alert")).'");
			}

			function OnClickAllowLdap(checkbox)
			{
				if (!checkbox)
					return;

				var button = document.getElementById("next-button-caption");
				if (button && !checkbox.disabled)
					button.innerHTML = (checkbox.checked ? "'.$nextCaption.'" : "'.$installCaption.'");
			}

			setTimeout(function() {OnClickAllowLdap(document.getElementById("allow-ldap"))}, 0);
			</script>
		';
	}
}


class LDAPSettingsStep extends CWizardStep
{
	var $ldp = false;
	var $connSuccessString = false;

	function InitStep()
	{
		$this->SetStepID("ldap_settings");
		$this->SetTitle(GetMessage("wiz_ldap_settings"));
		$this->SetPrevStep("site_settings");
		$this->SetNextStep("ldap_groups");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		$wizard =& $this->GetWizard();
		$wizard->SetDefaultVars(
			Array(
				"ldapPort" => "389"
			)
		);

		if (!function_exists("ldap_connect") || !CModule::IncludeModule("ldap"))
			return;

		$this->ldp = new CLDAP();
		$this->ldp->arFields = Array(
			"SERVER"		=>	$wizard->GetVar("ldapServer"),
			"PORT"			=>	$wizard->GetVar("ldapPort"),
			"BASE_DN"		=>	$wizard->GetVar("ldapBaseDN"),
			"ADMIN_LOGIN"	=>	$wizard->GetVar("ldapLogin"),
			"ADMIN_PASSWORD"=>	$wizard->GetVar("ldapPassword"),
			"CONVERT_UTF8"	=>	"Y",
			"GROUP_FILTER"	=>	"(objectCategory=group)",
			"GROUP_ID_ATTR"	=>	"dn",
			"GROUP_NAME_ATTR"=>	"sAMAccountName"
		);

		$success = $this->ldp->Connect();
		if (!$success)
			$this->ldp = false;

	}


	function IsServerCheck()
	{
		return (isset($_REQUEST["checkServer"]) && strlen($_REQUEST["checkServer"]) > 0);
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		if(!$this->ldp)
		{
			$this->SetError(GetMessage("wiz_ldap_error"), "ldapServer");
			return;
		}
		elseif(!$this->ldp->BindAdmin())
		{
			$this->SetError(GetMessage("wiz_ldap_error1"), "ldapLogin");
			return;
		}

		if ($this->IsServerCheck())
		{
			$this->connSuccessString = GetMessage("wiz_ldap_success");
			$wizard->SetCurrentStep("ldap_settings");
		}
		elseif ($this->ldp)
		{
			$dbGroup = $this->ldp->GetGroupList();
			if (!$dbGroup->Fetch())
			{
				$this->SetError(GetMessage("wiz_ldap_error_root"), "ldapBaseDN");
				return;
			}
		}

		if($wizard->GetVar('ldapNTLM')=='Y' && strlen($wizard->GetVar('ldapNTLMDomain'))<=0)
		{
			$this->SetError(GetMessage('wiz_ldap_error_domain'), "ldapNTLMDomain");
			return;
		}


		$wizardPath = $wizard->GetPath();
		$servicePath = $_SERVER["DOCUMENT_ROOT"].$wizardPath."/site/services/main/groups.php";
		if (file_exists($servicePath))
		{
			define("WIZARD_IS_RERUN", true);
			define("WIZARD_SERVICE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$wizardPath."/site/services/main");
			WizardServices::IncludeServiceLang("groups.php");
			include_once($servicePath);
		}
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		if ($this->connSuccessString !== false)
			$this->content .= '<span style="color:green;">'.$this->connSuccessString.'</span><br /><br />';

		$this->content .= '<table width="100%" cellspacing="5" cellpadding="0" class="data-table">';

		$this->content .= '<tr><td align="right" width="50%" class="header" style="white-space:normal !important"><span class="required">*</span>'.GetMessage("wiz_ldap_server").'</td><td>';
		$this->content .= $this->ShowInputField("text", "ldapServer", Array("style" => "width:200px"));
		$this->content .= ":";
		$this->content .= $this->ShowInputField("text", "ldapPort", Array("style" => "width:40px;"));
		$this->content .= '</td></tr>';

		$this->content .= '<tr><td align="right" class="header" style="white-space:normal !important"><span class="required">*</span>'.GetMessage("wiz_ldap_login").'<br><span style="font-weight: normal; font-size: 85%; color: #444444;">'.GetMessage('wiz_ldap_login_help').'</span></td><td>';
		$this->content .= $this->ShowInputField("text", "ldapLogin", Array("style" => "width:240px"));
		$this->content .= '</td></tr>';


		$this->content .= '<tr><td align="right" class="header" style="white-space:normal !important" valign="top"><span class="required">*</span>'.GetMessage("wiz_ldap_pass").'</td><td>';
		$this->content .= $this->ShowInputField("password", "ldapPassword", Array("style" => "width:240px"));
		$this->content .= '<br /><br /><input type="submit" value="'.GetMessage("wiz_ldap_check").'" name="checkServer" /></td></tr>';

		$this->content .= '<tr><td align="right" class="header" style="white-space:normal !important"><span class="required">*</span>'.GetMessage("wiz_ldap_root").'<br><span style="font-weight: normal; font-size: 85%; color: #444444;">'.GetMessage('wiz_ldap_root_help').'</span></td><td>';

		if($this->ldp)
		{
			$arRootDSE = $this->ldp->RootDSE();
			if(count($arRootDSE)>0)
			{
				$this->content .= '<select style="width:200px;" name="" onchange="document.getElementById(\'ldapBaseDN\').value = this.value">';
				$this->content .= '<option value="">'.GetMessage("wiz_ldap_select").'</option>';
				foreach($arRootDSE as $rootDSE)
					$this->content .= '<option value="'.htmlspecialcharsbx($rootDSE).'">'.htmlspecialcharsbx($rootDSE).'</option>';
				$this->content .= '</select> ';
			}
		}

		$this->content .= $this->ShowInputField("text", "ldapBaseDN", Array("id" => "ldapBaseDN", "style" => "width:240px"));
		$this->content .= '</td></tr>';

		if(strpos($_SERVER['SERVER_SOFTWARE'], 'mod_auth_sspi')!==false)
		{
			$this->content .= '<tr><td align="right" valign="top" class="header" style="white-space:normal !important">'.GetMessage('wiz_ldap_use_ntlm').'</td><td>';
			$this->content .= '<script>
			function __WCHNTLMDomen(f)
			{
				document.getElementById("ldapNTLMDomainSpan").disabled=f;
				document.getElementById("ldapNTLMDomain").disabled=f;
			}
			</script>';
			$this->content .= $this->ShowCheckboxField("ldapNTLM", "Y", Array("id" => "usentlm", "onclick"=>"__WCHNTLMDomen(!this.checked)"));
			$tDis = ($wizard->GetVar('ldapNTLM')=='Y'? '_' : '');
			$this->content .= '<br><br><span id="ldapNTLMDomainSpan" disabled'.$tDis.'="Y"><span class="required">*</span>'.GetMessage('wiz_ldap_use_ntlm_domain');
			$this->content .= $this->ShowInputField("text", "ldapNTLMDomain", Array('disabled'.$tDis=>'Y', 'id'=>'ldapNTLMDomain'));
			$this->content .= '</span></td></tr>';
		}

		$this->content .= '</table>';

	}

}

class LDAPGroupsStep extends CWizardStep
{
	var $ldp = false;

	function InitStep()
	{
		$this->SetStepID("ldap_groups");
		$this->SetTitle(GetMessage("wiz_ldap_groups"));
		$this->SetPrevStep("ldap_settings");
		$this->SetNextStep("data_install");
		$this->SetNextCaption(GetMessage("wiz_install"));
		$this->SetPrevCaption(GetMessage("PREVIOUS_BUTTON"));

		if (!function_exists("ldap_connect") || !CModule::IncludeModule("ldap"))
			return;

		$wizard =& $this->GetWizard();
		$this->ldp = new CLDAP();
		$this->ldp->arFields = Array(
			"SERVER"		=>	$wizard->GetVar("ldapServer"),
			"PORT"			=>	$wizard->GetVar("ldapPort"),
			"BASE_DN"		=>	$wizard->GetVar("ldapBaseDN"),
			"ADMIN_LOGIN"	=>	$wizard->GetVar("ldapLogin"),
			"ADMIN_PASSWORD"=>	$wizard->GetVar("ldapPassword"),
			"CONVERT_UTF8"	=>	"Y",
			"GROUP_FILTER"	=>	"(objectCategory=group)",
			"GROUP_ID_ATTR"	=>	"dn",
			"GROUP_NAME_ATTR"=>	"sAMAccountName"
		);

		$success = $this->ldp->Connect();
		if (!$success)
			$this->ldp = false;
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		if(!$this->ldp)
		{
			$wizard->SetCurrentStep("ldap_settings");
			$this->SetError(GetMessage("wiz_ldap_error"), "ldapServer");
			return;
		}
		elseif(!$this->ldp->BindAdmin())
		{
			$wizard->SetCurrentStep("ldap_settings");
			$this->SetError(GetMessage("wiz_ldap_error1"), "ldapLogin");
			return;
		}
		elseif ($this->ldp)
		{
			$dbGroup = $this->ldp->GetGroupList();
			if (!$dbGroup->Fetch())
			{
				$wizard->SetCurrentStep("ldap_settings");
				$this->SetError(GetMessage("wiz_ldap_error_root"), "ldapBaseDN");
				return;
			}
		}

		$arUserFieldMap = Array(
			"ACTIVE" => "UserAccountControl&2",
			"EMAIL"=>"email",
			"NAME" =>"givenName",
			"LAST_NAME" =>"sn",
			"PERSONAL_WWW" =>"wWWHomePage",
			"PERSONAL_PHONE" =>"homePhone",
			"PERSONAL_MOBILE" =>"mobile",
			"PERSONAL_STREET" =>"streetAddress",
			"PERSONAL_MAILBOX" =>"postOfficeBox",
			"PERSONAL_CITY" =>"l",
			"PERSONAL_STATE" =>"st",
			"PERSONAL_ZIP" => "postalCode",
			"PERSONAL_COUNTRY" =>"c",
			"WORK_COMPANY" =>"company",
			"WORK_DEPARTMENT" =>"department",
			"WORK_POSITION" =>"title",
			"WORK_PHONE" =>"telephoneNumber",
			"WORK_FAX" =>"facsimileTelephoneNumber",
			"ADMIN_NOTES" =>"description",
		);

		$arFields = Array(
			"NAME"			=> GetMessage("wiz_ldap_server1"),
			"DESCRIPTION"	=> "",
			"CODE"			=> ($wizard->GetVar('ldapNTLMDomain')?$wizard->GetVar('ldapNTLMDomain'):''),
			"SERVER"		=> $wizard->GetVar("ldapServer"),
			"PORT"			=> $wizard->GetVar("ldapPort"),
			"CONVERT_UTF8"	=> "Y",
			"ADMIN_LOGIN"	=> $wizard->GetVar("ldapLogin"),
			"ACTIVE"		=> "Y",
			"ADMIN_PASSWORD"=> $wizard->GetVar("ldapPassword"),
			"BASE_DN"		=> $wizard->GetVar("ldapBaseDN"),
			"GROUP_FILTER"	=> "(objectCategory=group)",
			"GROUP_ID_ATTR"	=> "dn",
			"GROUP_NAME_ATTR"=> "sAMAccountName",
			"USER_FILTER"	=> "(&(objectClass=user)(objectCategory=PERSON))",
			"USER_ID_ATTR"	=> "samaccountname",
			"USER_NAME_ATTR"=> "givenName",
			"USER_LAST_NAME_ATTR"=> "sn",
			"USER_EMAIL_ATTR"=>	"mail",
			"USER_GROUP_ATTR"=>	"memberof",
			"SYNC_PERIOD"	=> 	"5",
			"SYNC"			=> 	"N",
			"SYNC_ATTR"		=> 	"whenChanged",
			"FIELD_MAP"		=>  $arUserFieldMap,
			);

		$ldapGroup = $wizard->GetVar("ldapGroup");
		if (is_array($ldapGroup) && !empty($ldapGroup))
		{
			$arGroups = Array();
			foreach ($ldapGroup as $groupID => $ldapGroupID)
				$arGroups[] = Array("GROUP_ID" => $groupID, "LDAP_GROUP_ID" => $ldapGroupID);
			$arFields["GROUPS"] = $arGroups;
		}

		$ID = CLdapServer::Add($arFields);

		if ($ID < 1)
			$this->SetError(GetMessage("wiz_ldap_server_err").($exception = $GLOBALS["APPLICATION"]->GetException() ? $exception->GetString() : ""));
		elseif($wizard->GetVar('ldapNTLM')=='Y' && strlen($wizard->GetVar('ldapNTLMDomain'))>0)
		{
			COption::SetOptionString("ldap", "use_ntlm", "Y");
			COption::SetOptionString("ldap", "ntlm_default_server", $ID);
			RegisterModuleDependences('main', 'OnBeforeProlog', 'ldap', 'CLDAP', 'NTLMAuth', 40);

			$fhtaccess = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
			$f = fopen($fhtaccess, "rb");
			$fcontent = fread($f, filesize($fhtaccess));
			fclose($f);

			$fcontent = preg_replace('/AuthType .+SSPIOfferBasic On[\r\n\t #]Require valid-user/is', '', $fcontent);

			$fcontent = $fcontent."\r\n".
					"AuthName \"My Intranet\"\r\n".
					"AuthType SSPI\r\n".
					"SSPIAuth On\r\n".
					"SSPIPackage NTLM\r\n".
					"SSPIDomain ".$wizard->GetVar('ldapNTLMDomain')."\r\n".
					"SSPIPerRequestAuth On\r\n".
					"SSPIAuthoritative On\r\n".
					"SSPIOfferBasic On\r\n".
					"Require valid-user\r\n";

			$f = fopen($fhtaccess, "wb+");
			fwrite($f, $fcontent);
			fclose($f);
		}
	}

	function ShowStep()
	{
		$this->content .= '<table width="100%" cellspacing="5" cellpadding="0" class="data-table">';
		$this->content .= '<tr><td class="header">'.GetMessage("wiz_ldap_group_portal").'</td><td class="header">'.GetMessage("wiz_ldap_group_ldap").'</td></tr>';

		$arLDAPGroups = Array("" => "");
		if ($this->ldp && ($gr_res = $this->ldp->GetGroupList()))
		{
			while($ar_group = $gr_res->GetNext())
				$arLDAPGroups[$ar_group['ID']] = (is_set($ar_group, 'NAME') ? $ar_group['NAME'] : $ar_group['ID']);

			uasort($arLDAPGroups, create_function('$a, $b', '$a=ToUpper($a);$b=ToUpper($b); if($a==$b) return 0; return $a>$b?1:-1;'));
		}

		$dbGroups = CGroup::GetList($by = "c_sort", $order="asc", Array());
		while($arGroup = $dbGroups->GetNext())
		{
			if ($arGroup["ID"] == 2)
				continue;

			$this->content .= '<tr><td align="right">'.$arGroup["NAME"].':</td><td>';
			$this->content .= $this->ShowSelectField("ldapGroup[".$arGroup["ID"]."]", $arLDAPGroups, Array("style" => "width:200px"));
			$this->content .= '</td></tr>';
		}

		$this->content .= '</table>';
	}
}

class DataInstallStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("data_install");
		$this->SetTitle(GetMessage("wiz_install_data"));
		$this->SetSubTitle(GetMessage("wiz_install_data"));
	}

	function OnPostForm()
	{
		$wizard =& $this->GetWizard();
		$serviceID = $wizard->GetVar("nextStep");
		$serviceStage = $wizard->GetVar("nextStepStage");

		if ($serviceID == "finish")
		{
			$wizard->SetCurrentStep("finish");
			return;
		}

		$defSiteName = GetMessage("MAIN_DEFAULT_SITE_NAME");
		$wizard->GetVar("siteName");
		if($wizard->GetVar("siteName") != "")
			$defSiteName = $wizard->GetVar("siteName");

		$res = false;
		$site_id = $wizard->GetVar("siteID");
		if($site_id!="")
		{
			$db_res = CSite::GetList($by="sort", $order="desc", array("LID" => $site_id));
			if($db_res)
				$res = $db_res->Fetch();
		}

		if($res)
		{
			$obSite = new CSite;
			$result = $obSite->Update($site_id, Array("NAME"=>$defSiteName, "SITE_NAME"=>$defSiteName));
		}
		elseif($res && $res["NAME"] == GetMessage("MAIN_DEFAULT_SITE_NAME"))
		{
			$SiteNAME = $defSiteName . " (" . GetMessage("MAIN_DEFAULT_SITE_NAME") . ")";

			$obSite = new CSite;
			$result = $obSite->Update($site_id, Array("NAME"=>$SiteNAME, "SITE_NAME"=>$defSiteName));
		}

		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");

		if ($_SERVER["PHP_SELF"] == "/index.php" && !NON_INTRANET_EDITION)
		{
			$arEditions = array("Portal");
			if (EDITION == "E" || EDITION == "C" || EDITION == "H")
				$arEditions[] = "Communications";
			if (EDITION == "E" || EDITION == "H")
				$arEditions[] = "Enterprise";
			if (EDITION == "H")
				$arEditions[] = "Holding";
			CBXFeatures::InitiateEditionsSettings($arEditions);

			if ($wizard->GetVar("allowSocial") != "Y")
			{
				$ar = array("Friends", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery");
				foreach ($ar as $f)
					CBXFeatures::SetFeatureEnabled($f, false);
			}
			CBXFeatures::SetFeatureEnabled("Analytics", false);
		}

		//define("WIZARD_IS_RERUN", $_SERVER["PHP_SELF"] != "/index.php");
		if($_SERVER["PHP_SELF"] != "/index.php")
		{
			unset($arServices["users"]);
			unset($arServices["iblock_demo_data"]);
			unset($arServices["medialibrary"]);

			if($wizard->GetVar("installDemoData")!="Y")
			{
				$s = Array();
				foreach($arServices["main"]["STAGES"] as $v)
					if(!in_array($v, array("property.php", "options.php")))
						$s[] = $v;
				$arServices["main"]["STAGES"] = $s;

				//unset($arServices["forum"]);
				//unset($arServices["search"]);
				//unset($arServices["files"]);
				//unset($arServices["iblock"]);
				//unset($arServices["advertising"]);
				//unset($arServices["vote"]);
				//unset($arServices["learning"]);
				//unset($arServices["form"]);
				//unset($arServices["subscribe"]);
				//unset($arServices["blog"]);
				//unset($arServices["socialnetwork"]);
				//unset($arServices["intranet"]);
				//unset($arServices["support"]);
				//unset($arServices["workflow"]);
				//unset($arServices["fileman"]);
				//unset($arServices["statistic"]);
			}
		}

		if ($serviceStage == "skip")
			$success = true;
		else
			$success = $this->InstallService($serviceID, $serviceStage);

		list($nextService, $nextServiceStage, $stepsComplete, $status) = $this->GetNextStep($arServices, $serviceID, $serviceStage);

		if ($nextService == "finish")
		{
			if (LANGUAGE_ID != "ru")
				CBXFeatures::SetFeatureEnabled("Salary", false);
			$formName = $wizard->GetFormName();
			$response = "window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('100'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$status."');";
		}
		else
		{
			$arServiceID = array_keys($arServices);
			$lastService = array_pop($arServiceID);
			$stepsCount = $arServices[$lastService]["POSITION"];
			if (array_key_exists("STAGES", $arServices[$lastService]) && is_array($arServices[$lastService]))
				$stepsCount += count($arServices[$lastService]["STAGES"])-1;

			$percent = round($stepsComplete/$stepsCount * 100);
			$response = "window.ajaxForm.SetStatus('".$percent."'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$status."');";
		}

		die("[response]".$response."[/response]");
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arServices = WizardServices::GetServices($_SERVER["DOCUMENT_ROOT"].$wizard->GetPath(), "/site/services/");

		list($firstService, $stage, $status) = $this->GetFirstStep($arServices);

		/*$this->content .= '
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr>
					<td colspan="2"><div id="status"></div></td>
				</tr>
				<tr>
					<td width="90%" height="10">
						<div style="border:1px solid #B9CBDF; width:100%;"><div id="indicator" style="height:10px; width:0%; background-color:#B9CBDF"></div></div>
					</td>
					<td width="10%">&nbsp;<span id="percent">0%</span></td>
				</tr>
			</table>
			<div id="wait" align=center>
			<br />
			<table width=200 cellspacing=0 cellpadding=0 border=0 style="border:1px solid #EFCB69" bgcolor="#FFF7D7">
				<tr>
					<td height=50 width="50" valign="middle" align=center><img src="'.$wizard->GetPath().'/images/wait.gif"></td>
					<td height=50 width=150>'.GetMessage("WIZARD_WAIT_WINDOW_TEXT").'</td>
				</tr>
			</table>
		</div><br />
			<br />
			<div id="error_container" style="display:none">
				<div id="error_notice"><span style="color:red;">'.GetMessage("INST_ERROR_OCCURED").'<br />'.GetMessage("INST_TEXT_ERROR").':</span></div>
				<div id="error_text"></div>
				<div><span style="color:red;">'.GetMessage("INST_ERROR_NOTICE").'</span></div>
				<div id="error_buttons" align="center">
				<br /><input type="button" value="'.GetMessage("INST_RETRY_BUTTON").'" id="error_retry_button" onclick="" />&nbsp;<input type="button" id="error_skip_button" value="'.GetMessage("INST_SKIP_BUTTON").'" onclick="" />&nbsp;</div>
			</div>

		'.$this->ShowHiddenField("nextStep", $firstService).'
		'.$this->ShowHiddenField("nextStepStage", $stage).'
		<iframe style="display:none;" id="iframe-post-form" name="iframe-post-form" src="javascript:\'\'"></iframe>';*/
		$this->content .= '
		<div class="instal-load-block" id="result">
			<div class="instal-load-label" id="status"></div>
			<div class="instal-progress-bar-outer" style="width: 670px;">
				<div class="instal-progress-bar-alignment">
					<div class="instal-progress-bar-inner" id="indicator">
						<div class="instal-progress-bar-inner-text" style="width: 670px;" id="percent"></div>
					</div>
					<span id="percent2">0%</span>
				</div>
			</div>
		</div>
		<div id="error_container" style="display:none">
			<div id="error_notice">
				<div class="inst-note-block inst-note-block-red">
					<div class="inst-note-block-icon"></div>
					<div class="inst-note-block-label">'.GetMessage("INST_ERROR_OCCURED").'</div><br />
					<div class="inst-note-block-text">'.GetMessage("INST_ERROR_NOTICE").'<div id="error_text"></div></div>
				</div>
			</div>

			<div id="error_buttons" align="center">
			<br /><input type="button" value="'.GetMessage("INST_RETRY_BUTTON").'" id="error_retry_button" onclick="" class="instal-btn instal-btn-inp" />&nbsp;<input type="button" id="error_skip_button" value="'.GetMessage("INST_SKIP_BUTTON").'" onclick="" class="instal-btn instal-btn-inp" />&nbsp;</div>
		</div>

		'.$this->ShowHiddenField("nextStep", "main").'
		'.$this->ShowHiddenField("nextStepStage", "database").'
		<iframe style="display:none;" id="iframe-post-form" name="iframe-post-form" src="javascript:\'\'"></iframe>
		';

		$wizard =& $this->GetWizard();

		$formName = $wizard->GetFormName();
		$NextStepVarName = $wizard->GetRealName("nextStep");

		$this->content .= '
		<script type="text/javascript">
			var ajaxForm = new CAjaxForm("'.$formName.'", "iframe-post-form", "'.$NextStepVarName.'");
			ajaxForm.Post("'.$firstService.'", "'.$stage.'", "'.$status.'");
		</script>';
	}

	function InstallService($serviceID, $serviceStage)
	{
		$wizard =& $this->GetWizard();

		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));
		define("WIZARD_SITE_ID", $siteID);
		define("WIZARD_SITE_ROOT_PATH", $_SERVER["DOCUMENT_ROOT"]);

		$siteFolder = COption::GetOptionString("main", "wizard_site_folder", "", WIZARD_SITE_ID);
		if ($siteFolder)
		{
			define("WIZARD_SITE_DIR", $siteFolder);
		}
		else
		{
			$rsSites = CSite::GetByID($siteID);
			if ($arSite = $rsSites->Fetch())
				define("WIZARD_SITE_DIR", $arSite["DIR"]);
			else
				define("WIZARD_SITE_DIR", "/");
		}

		define("WIZARD_SITE_PATH", str_replace("//", "/", WIZARD_SITE_ROOT_PATH."/".WIZARD_SITE_DIR."/"));

		$wizardPath = $wizard->GetPath();
		define("WIZARD_RELATIVE_PATH", $wizardPath);
		define("WIZARD_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$wizardPath);

		$templatesPath = WizardServices::GetTemplatesPath("/bitrix/modules/intranet/install");
		//$arTemplates = WizardServices::GetTemplates($templatesPath, , $wizard->GetPath());
		$templateID = $wizard->GetVar("templateID");
		define("WIZARD_TEMPLATE_ID", $templateID);
		define("WIZARD_TEMPLATE_RELATIVE_PATH", $templatesPath."/".WIZARD_TEMPLATE_ID);
		define("WIZARD_TEMPLATE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_TEMPLATE_RELATIVE_PATH);

		$themeID = $wizard->GetVar($templateID."_themeID");
		//$arThemes = WizardServices::GetThemes(WIZARD_TEMPLATE_RELATIVE_PATH."/themes");
		define("WIZARD_THEME_ID", $themeID);
		define("WIZARD_THEME_RELATIVE_PATH", WIZARD_TEMPLATE_RELATIVE_PATH."/themes/".WIZARD_THEME_ID);
		define("WIZARD_THEME_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].WIZARD_THEME_RELATIVE_PATH);

		$servicePath = WIZARD_RELATIVE_PATH."/site/services/".$serviceID;
		define("WIZARD_SERVICE_RELATIVE_PATH", $servicePath);
		define("WIZARD_SERVICE_ABSOLUTE_PATH", $_SERVER["DOCUMENT_ROOT"].$servicePath);
		define("WIZARD_IS_RERUN", $_SERVER["PHP_SELF"] != "/index.php");
		
		$b24ToCp = file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu.php") ? true : false;
		define("WIZARD_B24_TO_CP", $b24ToCp);
//b24 to cp  		
		$firstStep = COption::GetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), false, $wizard->GetVar("siteID"));
		if ($b24ToCp)
			$firstStep = "Y";
		define("WIZARD_FIRST_INSTAL", $firstStep);
	
		define("WIZARD_USE_SITE_LOGO",  $wizard->GetVar("useSiteLogo") == "Y");
		define("WIZARD_SITE_NAME", $wizard->GetVar("siteName"));
		define("WIZARD_SITE_DEPARTAMENT", $wizard->GetVar("departamentID"));
		COption::SetOptionString("main", "wizard_departament", $wizard->GetVar("departamentID"), "", $siteID);
		define("WIZARD_SITE_LOGO", intval($wizard->GetVar("siteLogo")));

		define("WIZARD_INSTALL_DEMO_DATA", $wizard->GetVar("installDemoData") == "Y");

		define("WIZARD_INSTALL_DEMO_STRUCTURE", $wizard->GetVar("installStructureData") == "Y");
		define("WIZARD_INSTALL_MOBILE", $wizard->GetVar("installMobile") == "Y");

		if($firstStep == "N" || $wizard->GetVar("installDemoData") == "Y")
		{
			COption::SetOptionString("main", "wizard_clear_exec", "N", "", $siteID);
		}

		if( $_SERVER["PHP_SELF"] == "/index.php")
		{
			COption::SetOptionString("main", "wizard_new_2011", "Y");
		}
		define("WIZARD_NEW_2011", COption::GetOptionString("main", "wizard_new_2011", false));

		$dbGroupUsers = CGroup::GetList($by="id", $order="asc", Array("ACTIVE" => "Y"));
		$arGroupsId = Array("ADMIN_SECTION", "SUPPORT", "CREATE_GROUPS", "PERSONNEL_DEPARTMENT", "DIRECTION", "MARKETING_AND_SALES", "RATING_VOTE", "RATING_VOTE_AUTHORITY");

		while ($arGroupUser = $dbGroupUsers->Fetch())
		{
			if(in_array($arGroupUser["STRING_ID"], $arGroupsId))
			{
				define("WIZARD_".$arGroupUser["STRING_ID"]."_GROUP", $arGroupUser["ID"]);
			}
			else
			{
				if(substr($arGroupUser["STRING_ID"], -2) == $wizard->GetVar("siteID"))
				{
					define("WIZARD_".substr($arGroupUser["STRING_ID"], 0, -3)."_GROUP", $arGroupUser["ID"]);
				}
			}
		}

		if (!file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/".$serviceStage))
			return false;

		if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
		{
			if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage))
				__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/en/".$serviceStage);
		}

		if (file_exists(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage))
			__IncludeLang(WIZARD_SERVICE_ABSOLUTE_PATH."/lang/".LANGUAGE_ID."/".$serviceStage);

		@set_time_limit(3600);
		global $DB, $DBType, $APPLICATION, $USER, $CACHE_MANAGER;
		include(WIZARD_SERVICE_ABSOLUTE_PATH."/".$serviceStage);
	}

	function GetNextStep(&$arServices, &$currentService, &$currentStage)
	{
		$nextService = "finish";
		$nextServiceStage = "finish";
		$status = GetMessage("INSTALL_SERVICE_FINISH_STATUS");

		if (!array_key_exists($currentService, $arServices))
			return Array($nextService, $nextServiceStage, 0, $status); //Finish

		if ($currentStage != "skip" && array_key_exists("STAGES", $arServices[$currentService]) && is_array($arServices[$currentService]["STAGES"]))
		{
			$stageIndex = array_search($currentStage, $arServices[$currentService]["STAGES"]);
			if ($stageIndex !== false && isset($arServices[$currentService]["STAGES"][$stageIndex+1]))
				return Array(
					$currentService,
					$arServices[$currentService]["STAGES"][$stageIndex+1],
					$arServices[$currentService]["POSITION"]+ $stageIndex,
					$arServices[$currentService]["NAME"]
				); //Current step, next stage
		}

		$arServiceID = array_keys($arServices);
		$serviceIndex = array_search($currentService, $arServiceID);

		if (!isset($arServiceID[$serviceIndex+1]))
			return Array($nextService, $nextServiceStage, 0, $status); //Finish

		$nextServiceID = $arServiceID[$serviceIndex+1];
		$nextServiceStage = "index.php";
		if (array_key_exists("STAGES", $arServices[$nextServiceID]) && is_array($arServices[$nextServiceID]["STAGES"]) && isset($arServices[$nextServiceID]["STAGES"][0]))
			$nextServiceStage = $arServices[$nextServiceID]["STAGES"][0];

		return Array($nextServiceID, $nextServiceStage, $arServices[$nextServiceID]["POSITION"]-1, $arServices[$nextServiceID]["NAME"]); //Next service
	}

	function GetFirstStep(&$arServices)
	{
		foreach ($arServices as $serviceID => $arService)
		{
			$stage = "index.php";
			if (array_key_exists("STAGES", $arService) && is_array($arService["STAGES"]) && isset($arService["STAGES"][0]))
				$stage = $arService["STAGES"][0];
			return Array($serviceID, $stage, $arService["NAME"]);
		}

		return Array("service_not_found", "finish", GetMessage("INSTALL_SERVICE_FINISH_STATUS"));
	}
}

class FinishStep extends CWizardStep
{
	var $extranet_button = null;

	function InitStep()
	{
		$this->SetStepID("finish");
		$this->SetNextStep("finish");
		$this->SetTitle(GetMessage("FINISH_STEP_TITLE"));
		$this->SetNextCaption(GetMessage("wiz_go"));
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));
		$rsSites = CSite::GetByID($siteID);
		$siteDir = "/";
		if ($arSite = $rsSites->Fetch())
			$siteDir = $arSite["DIR"];

		$wizard->SetFormActionScript(str_replace("//", "/", $siteDir."/?finish"));

		$this->CreateNewIndex();
		$this->content .= GetMessage("FINISH_STEP_CONTENT");

		if(($_SERVER["PHP_SELF"] == "/index.php" && CBXFeatures::IsFeatureEnabled("Extranet")) || COption::GetOptionString("main", "wizard_install_extranet", "N", "") == "Y")
		{
			$this->content .= '<style type="text/css">.instal-btn-wrap {border-top: none !important; margin-top:0; }</style>';
			$this->content .=  "<br clear=\"all\"><div style='text-align: right; margin-top: 40px; padding-top: 18px; border-top: 1px solid #e5e5e5;'>
				<input style='padding: 0 21px 0 11px' class='wizard-next-button' value='".GetMessage("wizard_extranet_config")."' onclick='document.location.href=\"/bitrix/admin/wizard_install.php?".bitrix_sessid_get()."&lang=".LANGUAGE_ID."&wizardName=bitrix:extranet\"'/>
			</div>";//$this->extranet_button = GetMessage("wizard_extranet_config");
		}
	}

	function CreateNewIndex()
	{
		$wizard =& $this->GetWizard();
		$siteID = WizardServices::GetCurrentSiteID($wizard->GetVar("siteID"));

		define("WIZARD_SITE_ID", $siteID);
		define("WIZARD_SITE_ROOT_PATH", $_SERVER["DOCUMENT_ROOT"]);

		$rsSites = CSite::GetByID($siteID);
		if ($arSite = $rsSites->Fetch())
			define("WIZARD_SITE_DIR", $arSite["DIR"]);
		else
			define("WIZARD_SITE_DIR", "/");

		define("WIZARD_SITE_PATH", str_replace("//", "/", WIZARD_SITE_ROOT_PATH."/".WIZARD_SITE_DIR."/"));
//b24 to cp
		$b24ToCp = file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu.php") ? true : false;
		if ( file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu.php"))
		{
			DeleteDirFilesEx(WIZARD_SITE_DIR.".superleft.menu.php");
		}
		if ( file_exists($_SERVER["DOCUMENT_ROOT"]."/.superleft.menu_ext.php"))
		{
			DeleteDirFilesEx(WIZARD_SITE_DIR.".superleft.menu_ext.php");
		}
		
		$firstStep = COption::GetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), false, $wizard->GetVar("siteID"));
		if (IsModuleInstalled("bitrix24"))
			$firstStep = "Y";
		define("WIZARD_FIRST_INSTAL", $firstStep);
		//Copy index page
		if (WIZARD_FIRST_INSTAL !== "Y" && $wizard->GetVar("templateID") === "bitrix24"  || $b24ToCp && $wizard->GetVar("templateID") === "bitrix24")
		{
			CopyDirFiles(
				WIZARD_SITE_PATH."_index.php",
				WIZARD_SITE_PATH."index_old.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);

			CopyDirFiles(
				WIZARD_SITE_PATH."index_b24.php",
				WIZARD_SITE_PATH."index.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);
		}
		else
			CopyDirFiles(
				WIZARD_SITE_PATH."/_index.php",
				WIZARD_SITE_PATH."/index.php",
				$rewrite = true,
				$recursive = true,
				$delete_after_copy = true
			);
		COption::SetOptionString("main", "wizard_first" . substr($wizard->GetID(), 7)  . "_" . $wizard->GetVar("siteID"), "Y", false, $siteID);
	}
}
?>