<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
class WelcomeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetTitle(GetMessage("WELCOME_STEP_TITLE"));
		$this->SetStepID("welcome_step");
		$this->SetNextStep("delete_step");
		$this->SetNextCaption(GetMessage("NEXT_BUTTON"));
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();
		$wizard->SetVar("siteID",$_REQUEST['wizardSiteID']);
		$this->content .= GetMessage("WELCOME_TEXT");
	}

}

class DeleteStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("delete_step");
		$this->SetTitle(GetMessage("DELETE_STEP_TITLE"));
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

		$arServices = DeleteStep::GetServices();

		$success = $this->InstallService($serviceID, $serviceStage);

		$arStep = $this->GetNextStep($arServices, $serviceID, $serviceStage);
		$nextService = $arStep[0];
		$nextServiceStage = $arStep[1];
		$stepsComplete = $arStep[2];

		if ($nextService == "finish")
		{
			$formName = $wizard->GetFormName();
			$response = "window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('100'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','');";
		}
		else
		{
			$response = "window.ajaxForm.SetStatus('".$stepsComplete."'); window.ajaxForm.Post('".$nextService."', '".$nextServiceStage."','".$stepsComplete."');";
		}

		die("[response]".$response."[/response]");
	}

	function ShowStep()
	{
		$wizard =& $this->GetWizard();

		$arServices = DeleteStep::GetServices();

		list($firstService, $stage, $status) = $this->GetFirstStep($arServices);

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
			ajaxForm.Post("'.$firstService.'", "'.$stage.'", 0);
		</script>';
	}

	function InstallService($serviceID, $serviceStage)
	{
		$wizard =& $this->GetWizard();
		if(CModule::IncludeModule("iblock"))
		{
			$type = substr($serviceID, 0, -1);
			if($type == "iblockElement")
			{
				$dbItem = CIBlockElement::GetList(Array(), Array("=IBLOCK_CODE" => $serviceStage), false, false, Array("ID"));
				while($arItem = $dbItem->Fetch())
				{
					CIBlockElement::Delete($arItem["ID"]);
				}
			}
			elseif($type == "iblockSectionElement")
			{
				$dbItem = CIBlockElement::GetList(Array(), Array("=IBLOCK_CODE" => $serviceStage), false, false, Array("ID"));
				while($arItem = $dbItem->Fetch())
				{
					CIBlockElement::Delete($arItem["ID"]);
				}

				$dbItem = CIBlockSection::GetList(Array(), Array("=IBLOCK_CODE" => $serviceStage), false, Array("ID"));
				while($arItem = $dbItem->Fetch())
				{
					CIBlockSection::Delete($arItem["ID"]);
				}
			}
			elseif($serviceID == "iblockDepartmentsElement")
			{
				$dbItem = CIBlockSection::GetList(Array(), Array("=IBLOCK_CODE" => "departments"));
				while($arItem = $dbItem->Fetch())
				{
					if($arItem["DEPTH_LEVEL"] > 2)
					{
						CIBlockSection::Delete($arItem["ID"]);
					}
				}

			}
			elseif($serviceID == "tasks")
			{
				if (CModule::IncludeModule("tasks"))
				{
					$dbItem = CTasks::GetList(array(), array("SITE_ID" => $wizard->GetVar("siteID")));
					while($arItem = $dbItem->Fetch())
					{
						CTasks::Delete($arItem["ID"]);
					}
				}
			}
			elseif($serviceID == "calendar")
			{
				if (CModule::IncludeModule("calendar"))
				{
					$dbItem = CCalendarEvent::GetList();
					foreach($dbItem as $arItem)
					{
						CCalendarEvent::Delete(array("id"=>$arItem["ID"]));
					}
				}
			}
			elseif($serviceID == "meeting")
			{
				if (CModule::IncludeModule("meeting"))
				{
					$dbItem = CMeeting::GetList(Array(), Array());
					while($arItem = $dbItem->Fetch())
					{
						if($arItem["ID"])
						{
							CMeeting::Delete($arItem["ID"]);
						}
					}
				}
			}
			elseif($serviceID == "user")
			{
				if($serviceStage == "user")
				{
					$dbUser = CUser::GetList($by = "ID", $order = "DESC", Array(">ID" => 1));
					while($arUser = $dbUser->Fetch())
					{
						CUser::Delete($arUser["ID"]);
					}
				}
			}
			elseif($serviceID == "crm")
			{
				if($serviceStage == "crm")
				{
					if(CModule::IncludeModule('crm')) 
					{
						$CCrmLead = new CCrmLead();
						$resLead = CCrmLead::GetList(array(), array(), Array(), false);
						while ($rowLead = $resLead->Fetch())
							$CCrmLead->Delete($rowLead["ID"]); 

						$CCrmContact = new CCrmContact();
						$resContact = CCrmContact::GetList(array(), array(), Array(), false);
						while ($rowContact = $resContact->Fetch())
							$CCrmContact->Delete($rowContact["ID"]); 
							
						$CCrmCompany = new CCrmCompany();	
						$resCompany = CCrmCompany::GetList(array(), array(), Array(), false);
						while ($rowCompany = $resCompany->Fetch())
							$CCrmCompany->Delete($rowCompany["ID"]); 
							
						$CCrmDeal = new CCrmDeal();
						$resDeal = CCrmDeal::GetList(array(), array(), Array(), false);
						while ($rowDeal = $resDeal->Fetch())
							$CCrmDeal->Delete($rowDeal["ID"]); 
					}
				}
			}
			elseif($serviceID == "cache")
			{
					require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_html.php");
					if($serviceStage == "cache1")
						BXClearCache(true);
					elseif($serviceStage == "cache2")
						$GLOBALS["CACHE_MANAGER"]->CleanAll();
					elseif($serviceStage == "cache3")
						$GLOBALS["stackCacheManager"]->CleanAll();
					elseif($serviceStage == "cache4")
						CHTMLPagesCache::CleanAll();
					
					COption::SetOptionString("main", "wizard_clear_exec", "Y", false, $wizard->GetVar("siteID"));
			}			
		}
	}

	function GetNextStep(&$arServices, &$currentService, &$currentStage)
	{
		$nextService = "finish";
		$nextServiceStage = "finish";
		$status = GetMessage("INSTALL_SERVICE_FINISH_STATUS");

		if (!array_key_exists($currentService, $arServices))
			return Array($nextService, $nextServiceStage, 100); //Finish
		
		$i = 0;
		foreach($arServices as $k => $v)
		{
			$i++;
			if($currentService == $k)
				$serviceIndex = $i*6;
		}

		$stageIndex = array_search($currentStage, $arServices[$currentService]);
		if ($stageIndex !== false && isset($arServices[$currentService][$stageIndex+1]))
		{
			return Array(
				$currentService,
				$arServices[$currentService][$stageIndex+1],
				$serviceIndex
			); 
			//Current step, next stage
		}
		else
		{
			$bNext = false;
			$i = 0;
			foreach($arServices as $k => $v)
			{
				$i++;
				if($bNext)
				{
					return Array($k, $v[0], $i*6);
					//Next service
				}
				if($k == $currentService)
					$bNext = true;
			}

			return array($nextService, $nextServiceStage, 100);
		}
	}

	function GetFirstStep(&$arServices)
	{
		foreach ($arServices as $serviceID => $arService)
		{
			return Array($serviceID, $arService[0], 10);
		}
		return Array("service_not_found", "finish", GetMessage("INSTALL_SERVICE_FINISH_STATUS"));
	}
	
	function GetServices()
	{
		$wizard =& $this->GetWizard();
		$siteID = $wizard->GetVar("siteID");
		
		$arServices = Array(
				"iblockElement2" => Array(
						"board_".$siteID,
						//"faq",
						"links_".$siteID,
					),
				"iblockElement3" => Array(
						"official_news_".$siteID,
						"our_life_".$siteID,
					),
				"iblockElement4" => Array(
						"calendar_company_".$siteID,
						"meeting_rooms_".$siteID,
						"video-meeting_".$siteID,
						"vacancy_".$siteID,
					),
				"iblockSectionElement2" => Array(
						"shared_files_".$siteID,
						"sales_files_".$siteID,
						"photo_company_".$siteID,
					),
				"iblockSectionElement3" => Array(
						"directors_files_".$siteID,
					),
				"tasks" => Array(
					"tasks"
				),
				"cache" => Array( "cache1", "cache2", "cache3", "cache4")
					
			);
		if($siteID == 's1')
		{
			$arServices["crm"] = Array("crm");
			$arServices["iblockElement1"] = Array("adsence", "state_history","honour");
			$arServices["iblockDepartmentsElement"] = Array("departments");
			$arServices["meeting"] = Array("meeting");
			$arServices["user"] = Array("user");
			$arServices["iblockSectionElement1"] = Array("intranet_tasks", "calendar_employees");
			$arServices["iblockSectionElement5"] = Array("user_photogallery");
			$arServices["calendar"] = Array("calendar");			
		}
		return $arServices;
	}	
}

class FinishStep extends CWizardStep
{
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

		$siteID = $wizard->GetVar("siteID");
		$rsSites = CSite::GetByID($siteID);
		$siteDir = "/"; 
		if ($arSite = $rsSites->Fetch())
			$siteDir = $arSite["DIR"];

		$wizard->SetFormActionScript(str_replace("//", "/", $siteDir."/?finish"));

		$this->content .= GetMessage("FINISH_STEP_CONTENT");
	}

}
?>
