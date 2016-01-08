<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if($_GET['RELOAD'] != 'Y' && $_GET['IFRAME'] != 'Y'):
CUtil::InitJSCore();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?IncludeTemplateLangFile(__FILE__);?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" /><?
	$APPLICATION->ShowHead();
	$asset = \Bitrix\Main\Page\Asset::getInstance();
	$asset->addString('<link rel="stylesheet" type="text/css" media="print" href="'.SITE_TEMPLATE_PATH.'/print.css" />', false, \Bitrix\Main\Page\AssetLocation::AFTER_CSS);
	$asset->addString('<link rel="alternate stylesheet" type="text/css" media="screen,projection" href="'.SITE_TEMPLATE_PATH.'/print.css" title="print" />', false, \Bitrix\Main\Page\AssetLocation::AFTER_CSS);
	$asset->addString('<link rel="stylesheet" type="text/css" href="'.SITE_TEMPLATE_PATH.'/colors.css"/>', false, \Bitrix\Main\Page\AssetLocation::AFTER_CSS);
	$asset->addJs(SITE_TEMPLATE_PATH.'/script.js');
	?><title><?$APPLICATION->ShowTitle()?></title>
</head>
<body class="<?$APPLICATION->ShowProperty("BodyClass");?>">
<?if (IsModuleInstalled("im") && CBXFeatures::IsFeatureEnabled('WebMessenger')) $APPLICATION->IncludeComponent("bitrix:im.messenger", "", Array(
	"PATH_TO_SONET_EXTMAIL" => SITE_DIR."company/personal/mail/"
));?>
<div id="page-wrapper">
	<div id="panel"><?$APPLICATION->ShowPanel();?></div>

	<div id="page-inner">
		<div id="site-selector">
			<div id="site-selector-inner">

				<div id="site-selector-menus">

				<span class="site-selector-wrapper"><?
					$APPLICATION->IncludeComponent("bitrix:menu", "top_links",
						array(
							"ROOT_MENU_TYPE" => "top_links",
							"MENU_CACHE_TYPE" => "A",
							"MENU_CACHE_TIME" => 86400,
							"MAX_LEVEL" => "1",
						),
						false
					);
				?></span><span class="site-selector-wrapper"><?
					$APPLICATION->IncludeComponent("bitrix:menu", "top_links",
						array(
							"ROOT_MENU_TYPE" => "department",
							"MENU_CACHE_TYPE" => "A",
							"MENU_CACHE_TIME" => "86400",
							"MENU_CACHE_USE_GROUPS" => "Y",
							"MENU_CACHE_GET_VARS" => array(
							),
							"MAX_LEVEL" => "1",
							"CHILD_MENU_TYPE" => "left",
							"USE_EXT" => "Y",
							"DELAY" => "N",
							"ALLOW_MULTI_SELECT" => "N"
							),
							false,
							Array("HIDE_ICONS" => "Y")
					);
				?></span></div>

				<div id="site-selector-items"><?$APPLICATION->ShowViewContent("user-indicators")?><?
					if(
						!CModule::IncludeModule("extranet")
						|| CExtranet::GetExtranetSiteID() != SITE_ID
					)
					{
						if(
							!IsModuleInstalled("timeman")
							|| !$APPLICATION->IncludeComponent('bitrix:timeman', '', array(), false, array("HIDE_ICONS" => "Y" ))
						)
						{
							$APPLICATION->IncludeComponent('bitrix:planner', '', array(), false, array("HIDE_ICONS" => "Y" ));
						}
					}
?></div>

			</div>
		</div>
		<div id="page">
			<div id="header">

				<div id="header-inner">
					<?
					$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false);
					$diskPath = ($diskEnabled == "Y") ? SITE_DIR."company/personal/user/#user_id#/disk/path/" : SITE_DIR."company/personal/user/#user_id#/files/lib/";
					?>
					<?$APPLICATION->IncludeComponent("bitrix:system.auth.form", "auth", array(
							"REGISTER_URL" => SITE_DIR."auth/",
							"PATH_TO_MYPORTAL" => SITE_DIR."desktop.php",
							"PATH_TO_SONET_PROFILE" => SITE_DIR."company/personal/user/#user_id#/",
							"PATH_TO_SONET_MESSAGES" => SITE_DIR."company/personal/messages/",
							"PATH_TO_SONET_EXTMAIL" => SITE_DIR."company/personal/mail/",
							"PATH_TO_SONET_LOG" => SITE_DIR."company/personal/log/",
							"PATH_TO_SONET_GROUPS" => SITE_DIR."company/personal/user/#user_id#/groups/",
							"PATH_TO_CALENDAR" => SITE_DIR."company/personal/user/#user_id#/calendar/",
							"PATH_TO_TASKS" => SITE_DIR."company/personal/user/#user_id#/tasks/",
							"PATH_TO_PHOTO" => SITE_DIR."company/personal/user/#user_id#/photo/",
							"PATH_TO_BLOG" => SITE_DIR."company/personal/user/#user_id#/blog/",
							"PATH_TO_MICROBLOG" => SITE_DIR."company/personal/user/#user_id#/microblog/",
							"PATH_TO_FILES" => $diskPath,
						),
						false
					);?>
					<div id="navigation-block">
						<table id="logo-search" cellspacing="0">
							<tr>
								<td id="logo-image"><a href="<?=SITE_DIR?>" title="<?=GetMessage("LIGHT_HEADER_MAIN_LINK")?>"><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/company_name.php"), false);?></a></td>
								<td id="search-form">
									<?$APPLICATION->IncludeComponent("bitrix:search.title", ".default", Array(
										"NUM_CATEGORIES" => "5",
										"TOP_COUNT" => "5",
										"CHECK_DATES" => "N",
										"SHOW_OTHERS" => "Y",
										"PAGE" => "#SITE_DIR#search/index.php",
										"CATEGORY_0_TITLE" => GetMessage("LIGHT_HEADER_SEARCH_EMPLOYEE"),
										"CATEGORY_0" => array(
											0 => "intranet",
										),
										"CATEGORY_1_TITLE" => GetMessage("LIGHT_HEADER_SEARCH_DOCS"),
										"CATEGORY_1" => array(
											0 => "iblock_library",
										),
										"CATEGORY_1_iblock_library" => array(
											0 => "all",
										),
										"CATEGORY_2_TITLE" => GetMessage("LIGHT_HEADER_SEARCH_GROUPS"),
										"CATEGORY_2" => array(
											0 => "socialnetwork",
										),
										"CATEGORY_2_socialnetwork" => array(
											0 => "all",
										),
										"CATEGORY_3_TITLE" => GetMessage("LIGHT_HEADER_SEARCH_MICROBLOG"),
										"CATEGORY_3" => array(
											0 => "microblog",
										),
										"CATEGORY_4_TITLE" => "CRM",
										"CATEGORY_4" => array(
											0 => "crm",
										),
										"CATEGORY_OTHERS_TITLE" => GetMessage("LIGHT_HEADER_SEARCH_OTHER"),
										"SHOW_INPUT" => "N",
										"INPUT_ID" => "search-textbox-input",
										"CONTAINER_ID" => "search",
										),
										false
									);?>
								</td>
							</tr>
						</table>


						<?$APPLICATION->IncludeComponent("bitrix:menu", "horizontal_multilevel", array(
							"ROOT_MENU_TYPE" => "top",
							"MENU_CACHE_TYPE" => "N",
							"MAX_LEVEL" => "3",
							"CHILD_MENU_TYPE" => "left",
							"USE_EXT" => "Y",
							"DELAY" => "N",
							"ALLOW_MULTI_SELECT" => "N"
							),
							false
						);?>
						<?if($APPLICATION->GetCurPage(true) != SITE_DIR."index.php" && $APPLICATION->GetCurPage() != SITE_DIR."desktop.php"):?>
						<div id="breadcrumb"><?$APPLICATION->IncludeComponent("bitrix:breadcrumb", ".default", array("SITE_ID" => SITE_ID), false);?></div>
						<?endif?>
					</div>
					<div class="clear"></div>
				</div>
			</div>
			<?$APPLICATION->ShowViewContent("topblock")?>
			<div id="content">
				<?if($APPLICATION->GetCurPage(true) != SITE_DIR."index.php" && $APPLICATION->GetCurPage() != SITE_DIR."desktop.php" && $APPLICATION->GetProperty("HIDE_SIDEBAR", "N") != "Y"):?>
				<table id="content-table" cellspacing="0">
					<tr>
						<td id="workarea">
							<h1 id="pagetitle"><?$APPLICATION->ShowTitle(false);?><?$APPLICATION->ShowViewContent("pagetitle")?></h1>
							<div id="pagetitle-underline"></div>
							<? $GLOBALS['INTRANET_TOOLBAR']->Disable();?>
				<? else: ?>
					<?if($APPLICATION->GetCurPage(true) != SITE_DIR."index.php"):?>
					<h1 id="pagetitle"><?$APPLICATION->ShowTitle(false);?><?$APPLICATION->ShowViewContent("pagetitle")?></h1>
					<?endif?>
				<? endif ?>
<?endif;?>