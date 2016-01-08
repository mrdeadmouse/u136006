<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_GET["RELOAD"]) && $_GET["RELOAD"] == "Y")
{
	return; //Live Feed Ajax
}
else if (strpos($_SERVER["REQUEST_URI"], "/historyget/") > 0)
{
	return;
}
else if (isset($_GET["IFRAME"]) && $_GET["IFRAME"] == "Y" && !isset($_GET["SONET"]))
{
	//For the task iframe popup
	$APPLICATION->SetPageProperty("BodyClass", "task-iframe-popup");
	$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH."/interface.css", true);
	$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/bitrix24.js", true);
	return;
}

$APPLICATION->GroupModuleJS("im","pull");
$APPLICATION->GroupModuleJS("timeman","pull");
$APPLICATION->GroupModuleJS("webrtc","pull");
$APPLICATION->GroupModuleCSS("im","pull");
$APPLICATION->GroupModuleCSS("timeman","pull");
$APPLICATION->GroupModuleCSS("webrtc","pull");
$APPLICATION->MoveJSToBody("pull");
$APPLICATION->MoveJSToBody("timeman");
$APPLICATION->SetUniqueJS('bx24', 'template');
$APPLICATION->SetUniqueCSS('bx24', 'template');

$isCompositeMode = defined("USE_HTML_STATIC_CACHE");
$isIndexPage = $APPLICATION->GetCurPage(true) == SITE_DIR."index.php";

if ($isCompositeMode && $isIndexPage)
{
	define("BITRIX24_INDEX_COMPOSITE", true);
}

if ($isCompositeMode)
{
	$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
}

function showJsTitle()
{
	$GLOBALS["APPLICATION"]->AddBufferContent("getJsTitle");
}

function getJsTitle()
{
	$title = $GLOBALS["APPLICATION"]->GetTitle("title", true);
	$title = html_entity_decode($title, ENT_QUOTES, SITE_CHARSET);
	$title = CUtil::JSEscape($title);
	return $title;
}

$isDiskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?IncludeTemplateLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".SITE_TEMPLATE_ID."/header.php");?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<?if (IsModuleInstalled("bitrix24")):?>
<meta name="apple-itunes-app" content="app-id=561683423" />
<link rel="apple-touch-icon-precomposed" href="/images/iphone/57x57.png" />
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/images/iphone/72x72.png" />
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/images/iphone/114x114.png" />
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="/images/iphone/144x144.png" />
<?endif;

$APPLICATION->ShowHead();
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH."/interface.css", true);
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/bitrix24.js", true);
?><title><? if (!$isCompositeMode || $isIndexPage) $APPLICATION->ShowTitle()?></title>
</head>

<body class="template-bitrix24">
<?
if ($isCompositeMode && !$isIndexPage)
{
	$frame = new \Bitrix\Main\Page\FrameStatic("title");
	$frame->startDynamicArea();
	?><script type="text/javascript">document.title = "<?showJsTitle()?>";</script><?
	$frame->finishDynamicArea();
}

$profile_link = (CModule::IncludeModule("extranet") && SITE_ID == CExtranet::GetExtranetSiteID()) ? SITE_DIR."contacts/personal" : SITE_DIR."company/personal";
?>
<table class="bx-layout-table">
	<tr>
		<td class="bx-layout-header">
			<?
			if ((!IsModuleInstalled("bitrix24") || $USER->IsAdmin()) && !defined("SKIP_SHOW_PANEL"))
				$APPLICATION->ShowPanel();
			?>
			<div id="header">
				<div id="header-inner">
					<?
					//This component is used for menu-create-but.
					//We have to include the component before bitrix:timeman for composite mode.
					if (CModule::IncludeModule('tasks') && CBXFeatures::IsFeatureEnabled('Tasks')):
						$APPLICATION->IncludeComponent(
							"bitrix:tasks.iframe.popup",
							".default",
							array(
								"ON_TASK_ADDED" => "#SHOW_ADDED_TASK_DETAIL#",
								"ON_TASK_CHANGED" => "BX.DoNothing",
								"ON_TASK_DELETED" => "BX.DoNothing"
							),
							null,
							array("HIDE_ICONS" => "Y")
						);
					endif;

					if(
						!CModule::IncludeModule("extranet")
						|| CExtranet::GetExtranetSiteID() != SITE_ID
					)
					{
						if(
							!IsModuleInstalled("timeman")
							|| !$APPLICATION->IncludeComponent('bitrix:timeman', 'bitrix24', array(), false, array("HIDE_ICONS" => "Y" ))
						)
						{
							$APPLICATION->IncludeComponent('bitrix:planner', 'bitrix24', array(), false, array("HIDE_ICONS" => "Y" ));
						}
					}
					else
					{
						CJSCore::Init("timer");?>
						<div class="timeman-wrap">
							<span id="timeman-block" class="timeman-block">
								<span class="bx-time" id="timeman-timer"><script type="text/javascript">document.write(B24.Timemanager.formatCurrentTime(new Date().getHours(), new Date().getMinutes()))</script></span>
							</span>
						</div>
						<script type="text/javascript">BX.ready(function() {
							BX.timer.registerFormat("bitrix24_time", B24.Timemanager.formatCurrentTime);
							BX.timer({
								container: BX("timeman-timer"),
								display : "bitrix24_time"
							});
						});</script>
					<?
					}
					?>

					<div class="header-logo-block">
						<span class="header-logo-block-util"></span>
						<?if (IsModuleInstalled("bitrix24")):?>
							<a id="logo_24_a" href="<?=SITE_DIR?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="logo"><?
								$clientLogo = COption::GetOptionInt("bitrix24", "client_logo", "");?>
								<span id="logo_24_text" <?if ($clientLogo):?>style="display:none"<?endif?>>
									<span class="logo-text"><?=htmlspecialcharsbx(COption::GetOptionString("bitrix24", "site_title", ""))?></span><?
									if(COption::GetOptionString("bitrix24", "logo24show", "Y") !=="N"):?><span class="logo-color">24</span><?endif?>
								</span>
								<img id="logo_24_img" src="<?if ($clientLogo) echo CFile::GetPath($clientLogo)?>" <?if (!$clientLogo):?>style="display:none;"<?endif?>/>
							</a>
						<?else:?>
							<?
							$logoID = COption::GetOptionString("main", "wizard_site_logo", "", SITE_ID);
							?>
							<a id="logo_24_a" href="<?=SITE_DIR?>" title="<?=GetMessage("BITRIX24_LOGO_TOOLTIP")?>" class="logo">
								<?if ($logoID):?>
									<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/company_name.php"), false);?>
								<?else:?>
									<span id="logo_24_text">
										<span class="logo-text"><?=htmlspecialcharsbx(COption::GetOptionString("main", "site_name", ""));?></span>
										<span class="logo-color">24</span>
									</span>
								<?endif?>
							</a>
						<?endif?>
						<?
						$GLOBALS["LEFT_MENU_COUNTERS"] = array();
						if (CModule::IncludeModule("im") && CBXFeatures::IsFeatureEnabled('WebMessenger'))
						{
							$APPLICATION->IncludeComponent("bitrix:im.messenger", "", Array(
								"PATH_TO_SONET_EXTMAIL" => SITE_DIR."company/personal/mail/"
							), false, Array("HIDE_ICONS" => "Y"));
						} ?>
					</div>

					<?$APPLICATION->IncludeComponent("bitrix:search.title", ".default", Array(
						"NUM_CATEGORIES" => "5",
						"TOP_COUNT" => "5",
						"CHECK_DATES" => "N",
						"SHOW_OTHERS" => "Y",
						"PAGE" => "#SITE_DIR#search/index.php",
						"CATEGORY_0_TITLE" => GetMessage("BITRIX24_SEARCH_EMPLOYEE"),
						"CATEGORY_0" => array(
							0 => "intranet",
						),
						"CATEGORY_1_TITLE" => GetMessage("BITRIX24_SEARCH_DOCUMENT"),
						"CATEGORY_1" => array(
							0 => "iblock_library",
						),
						"CATEGORY_1_iblock_library" => array(
							0 => "all",
						),
						"CATEGORY_2_TITLE" => GetMessage("BITRIX24_SEARCH_GROUP"),
						"CATEGORY_2" => array(
							0 => "socialnetwork",
						),
						"CATEGORY_2_socialnetwork" => array(
							0 => "all",
						),
						"CATEGORY_3_TITLE" => GetMessage("BITRIX24_SEARCH_MICROBLOG"),
						"CATEGORY_3" => array(
							0 => "microblog", 1 => "blog",
						),
						"CATEGORY_4_TITLE" => "CRM",
						"CATEGORY_4" => array(
							0 => "crm",
						),
						"CATEGORY_OTHERS_TITLE" => GetMessage("BITRIX24_SEARCH_OTHER"),
						"SHOW_INPUT" => "N",
						"INPUT_ID" => "search-textbox-input",
						"CONTAINER_ID" => "search",
						"USE_LANGUAGE_GUESS" => (LANGUAGE_ID == "ru") ? "Y" : "N"
						),
						false
					);?>

					<?$APPLICATION->IncludeComponent("bitrix:system.auth.form", "", array(
						"PATH_TO_SONET_PROFILE" => $profile_link."/user/#user_id#/",
						"PATH_TO_SONET_PROFILE_EDIT" => $profile_link."/user/#user_id#/edit/",
						"PATH_TO_SONET_EXTMAIL_SETUP" => $profile_link."/mail/?page=home",
						"PATH_TO_SONET_EXTMAIL_MANAGE" => $profile_link."/mail/?page=manage"
						),
						false
					);?>
				</div>
			</div>
		</td>
	</tr>
	<tr>
		<td class="bx-layout-cont">
			<table class="bx-layout-inner-table">
				<colgroup>
					<col class="bx-layout-inner-left" />
					<col class="bx-layout-inner-center" />
				</colgroup>
				<tr class="bx-layout-inner-top-row">
					<td class="bx-layout-inner-left">
						<div id="menu">
							<?
							if (!(
								CModule::IncludeModule('extranet')
								&& SITE_ID === CExtranet::GetExtranetSiteID()
							))
							{
								$APPLICATION->IncludeComponent(
									"bitrix:socialnetwork.group.iframe.popup",
									".default",
									array(
										"PATH_TO_GROUP" => SITE_DIR."workgroups/group/#group_id#/",
										"PATH_TO_GROUP_CREATE" => SITE_DIR."company/personal/user/".$USER->GetID()."/groups/create/",
										"IFRAME_POPUP_VAR_NAME" => "groupCreatePopup",
										"ON_GROUP_ADDED" => "BX.DoNothing",
										"ON_GROUP_CHANGED" => "BX.DoNothing",
										"ON_GROUP_DELETED" => "BX.DoNothing"
									),
									null,
									array("HIDE_ICONS" => "Y")
								);

								$popupName = "create-group-popup";
								$APPLICATION->IncludeComponent(
									"bitrix:socialnetwork.group_create.popup",
									".default",
									array(
										"NAME" => $popupName,
										"PATH_TO_GROUP_EDIT" => SITE_DIR."company/personal/user/".$USER->GetID()."/groups/create/",
									),
									null,
									array("HIDE_ICONS" => "Y")
								);
							}
							?>

							<?if (
								$USER->IsAuthorized()
								&& (
									CBXFeatures::IsFeatureEnabled('Calendar')
									|| CBXFeatures::IsFeatureEnabled('Workgroups')
									|| CBXFeatures::IsFeatureEnabled('PersonalFiles')
									|| CBXFeatures::IsFeatureEnabled('PersonalPhoto')
								)
							):?>
							<div class="menu-create-but" onclick="BX.addClass(this, 'menu-create-but-active');BX.PopupMenu.show('create-menu', this, [
								<?if((CModule::IncludeModule('bitrix24') && $USER->CanDoOperation('bitrix24_invite') || !IsModuleInstalled("bitrix24") && $USER->CanDoOperation('edit_all_users'))&& CModule::IncludeModule('intranet')):?>
								{ text : '<?=GetMessage("BITRIX24_INVITE")?>', className : 'invite-employee', onclick : function() { this.popupWindow.close(); <?=CIntranetInviteDialog::ShowInviteDialogLink()?>} },
								<?endif?>
								<?if(CBXFeatures::IsFeatureEnabled('Tasks')):?>
								{ text : '<?=GetMessage("BITRIX24_TASK_CREATE")?>', className : 'create-task', onclick : function() { this.popupWindow.close(); BX.Tasks.lwPopup.showCreateForm(); }},
								<?endif?>
								<?if (!(CModule::IncludeModule('extranet') && SITE_ID === CExtranet::GetExtranetSiteID())):?>
									<?if (CBXFeatures::IsFeatureEnabled('Calendar')):?>
								{ text : '<?=GetMessage("BITRIX24_EVENT_CREATE")?>', className : 'create-event', href : '<?=SITE_DIR?>company/personal/user/<?=$USER->GetID()?>/calendar/?EVENT_ID=NEW'},
									<?endif?>
								{ text : '<?=GetMessage("BITRIX24_BLOG_CREATE")?>', className : 'create-write-blog', href : '<?=SITE_DIR?>company/personal/user/<?=$USER->GetID()?>/blog/edit/new/'},
									<?if (CBXFeatures::IsFeatureEnabled('Workgroups') && CModule::IncludeModule('socialnetwork') && (CSocNetUser::IsCurrentUserModuleAdmin() || $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork", false, "Y", "Y", array(SITE_ID, false)) >= "K")):?>
								{ text : '<?=GetMessage("BITRIX24_GROUP_CREATE")?>', className : 'create-group', onclick : function() {this.popupWindow.close(); if (BX.SGCP) { BX.SGCP.ShowForm('create', '<?=$popupName?>', event); } else { return false; } } },
									<?endif?>
									<?if (CBXFeatures::IsFeatureEnabled('PersonalFiles')):
										$newFileLink = $isDiskEnabled ? SITE_DIR.'company/personal/user/'.$USER->GetID().'/blog/edit/new/?POST_MESSAGE=&changePostFormTab=file' : SITE_DIR."company/personal/user/".$USER->GetID()."/files/lib/?file_upload=Y";
									?>
								{ text : '<?=GetMessage("BITRIX24_FILE_CREATE")?>', className : 'create-download-files', href : '<?=$newFileLink?>' },
									<?endif?>
									<?if (CBXFeatures::IsFeatureEnabled('PersonalPhoto')):?>
								{ text : '<?=GetMessage("BITRIX24_PHOTO_CREATE")?>', className : 'create-download-photo', href : '<?=SITE_DIR?>company/personal/user/<?=$USER->GetID()?>/photo/photo/0/action/upload/'}
									<?endif?>
								<?else:?>
								{ text : '<?=GetMessage("BITRIX24_BLOG_CREATE")?>', className : 'create-write-blog', href : '<?=SITE_DIR?>contacts/personal/user/<?=$USER->GetID()?>/blog/edit/new/'},
									<?if (CBXFeatures::IsFeatureEnabled('PersonalFiles')):
										$newFileLink = $isDiskEnabled ? SITE_DIR.'contacts/personal/user/'.$USER->GetID().'/blog/edit/new/?POST_MESSAGE=&changePostFormTab=file' : SITE_DIR."contacts/personal/user/".$USER->GetID()."/files/lib/?file_upload=Y";
									?>
								{ text : '<?=GetMessage("BITRIX24_FILE_CREATE")?>', className : 'create-download-files', href : '<?=$newFileLink?>' },
									<?endif?>
								<?endif;?>
								],
								{
									offsetLeft: 47,
									offsetTop: 10,
									angle : true,

									events : {
										onPopupClose : function(popupWindow)
										{
											BX.removeClass(this.bindElement, 'menu-create-but-active');
										}
									}
								})"><?=GetMessage("BITRIX24_CREATE")?></div>
								<?endif;?>

								<?if (IsModuleInstalled("bitrix24")) :?>
									<?$APPLICATION->IncludeComponent("bitrix:menu", "vertical_multilevel", array(
											"ROOT_MENU_TYPE" => "superleft",
											"MENU_CACHE_TYPE" => "Y",
											"MENU_CACHE_TIME" => "604800",
											"MENU_CACHE_USE_GROUPS" => "N",
											"MENU_CACHE_USE_USERS" => "Y",
											"CACHE_SELECTED_ITEMS" => "N",
											"MENU_CACHE_GET_VARS" => array(),
											"MAX_LEVEL" => "1",
											"CHILD_MENU_TYPE" => "superleft",
											"USE_EXT" => "Y",
											"DELAY" => "N",
											"ALLOW_MULTI_SELECT" => "N"
										),
										false
									);?>
								<?else:?>
									<?$APPLICATION->IncludeComponent("bitrix:menu", "vertical_multilevel", array(
											"ROOT_MENU_TYPE" => "top",
											"MENU_CACHE_TYPE" => "Y",
											"MENU_CACHE_TIME" => "604800",
											"MENU_CACHE_USE_GROUPS" => "N",
											"MENU_CACHE_USE_USERS" => "Y",
											"CACHE_SELECTED_ITEMS" => "N",
											"MENU_CACHE_GET_VARS" => array(),
											"MAX_LEVEL" => "2",
											"CHILD_MENU_TYPE" => "left",
											"USE_EXT" => "Y",
											"DELAY" => "N",
											"ALLOW_MULTI_SELECT" => "N"
										),
										false
									);?>
								<?endif;?>
						</div>
					</td>
					<td class="bx-layout-inner-center" id="content-table">
					<?
					if ($isCompositeMode && !$isIndexPage)
					{
						$dynamicArea = new \Bitrix\Main\Page\FrameStatic("workarea");
						$dynamicArea->setAssetMode(\Bitrix\Main\Page\AssetMode::STANDARD);
						$dynamicArea->setContainerId("content-table");
						$dynamicArea->setStub('
							<table class="bx-layout-inner-inner-table">
								<colgroup>
									<col class="bx-layout-inner-inner-cont">
									<col class="bx-layout-inner-inner-right">
								</colgroup>
								<tr>
									<td class="bx-layout-inner-inner-cont"><div class="pagetitle-wrap"></div>
									<div class="b24-loader" id="b24-loader"><div class="b24-loader-curtain"></div></div></td>
									<td class="bx-layout-inner-inner-right"></td>
								</tr>
							</table>
							<script>B24.showLoading();</script>'
						);
						$dynamicArea->startDynamicArea();
					}
					?>

						<table class="bx-layout-inner-inner-table <?$APPLICATION->ShowProperty("BodyClass");?>">
							<colgroup>
								<col class="bx-layout-inner-inner-cont">
								<col class="bx-layout-inner-inner-right">
							</colgroup>
							<tr>
								<td class="bx-layout-inner-inner-cont">
									<?$APPLICATION->ShowViewContent("above_pagetitle")?>
									<div class="pagetitle-wrap">
										<div class="pagetitle-menu" id="pagetitle-menu"><?$APPLICATION->ShowViewContent("pagetitle")?></div>
										<h1 class="pagetitle" id="pagetitle"><span class="pagetitle-inner"><?$APPLICATION->ShowTitle(false);?></span></h1>
										<div class="pagetitle-content-topEnd">
											<div class="pagetitle-content-topEnd-corn"></div>
										</div>
									</div>
									<div id="workarea">
										<?if(!$isIndexPage && $APPLICATION->GetProperty("HIDE_SIDEBAR", "N") != "Y"):
											?><div id="sidebar"><?
											if (IsModuleInstalled("bitrix24")):
												$GLOBALS['INTRANET_TOOLBAR']->Disable();
											else:
												$GLOBALS['INTRANET_TOOLBAR']->Enable();
												$GLOBALS['INTRANET_TOOLBAR']->Show();
											endif;

											$APPLICATION->ShowViewContent("sidebar");
											$APPLICATION->ShowViewContent("sidebar_tools_1");
											$APPLICATION->ShowViewContent("sidebar_tools_2");
											?></div>
										<?endif?>
										<div id="workarea-content">
										<?$APPLICATION->ShowViewContent("topblock")?>
										<?CPageOption::SetOptionString("main.interface", "use_themes", "N"); //For grids?>
