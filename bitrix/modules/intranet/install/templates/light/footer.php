<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

				<?if($APPLICATION->GetCurPage(true) != SITE_DIR."index.php" && $APPLICATION->GetCurPage() != SITE_DIR."desktop.php" && $APPLICATION->GetProperty("HIDE_SIDEBAR", "N") != "Y"):?>
						</td>
						<td id="sidebar">
							<?$GLOBALS['INTRANET_TOOLBAR']->Enable();?>
							<?$GLOBALS['INTRANET_TOOLBAR']->Show();?>
							<?$APPLICATION->ShowViewContent("sidebar")?>
							<?$APPLICATION->ShowViewContent("sidebar_tools_1")?>
							<?$APPLICATION->ShowViewContent("sidebar_tools_2")?>
							<div id="bx_left_menu">
							<?$APPLICATION->IncludeComponent("bitrix:menu", "right", Array(
								"ROOT_MENU_TYPE" => "left",
								"MAX_LEVEL" => "2",
								"CHILD_MENU_TYPE" => "left",
								"USE_EXT" => "Y",
								"MENU_CACHE_TYPE" => "A",
								"MENU_CACHE_TIME" => "604800",
								"MENU_CACHE_USE_GROUPS" => "Y",
								"MENU_CACHE_GET_VARS" => Array()
							)
							);?>
							</div>
						</td>
					</tr>
				</table>
				<? endif ?>
			</div>
		</div>
	</div>
	<div id="space-for-footer"></div>
</div>

<div id="footer-wrapper">
	<div id="footer">
		<div id="copyright"><?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => SITE_DIR."include/copyright.php"), false);?></div>
		<div id="footer-links"><?$APPLICATION->IncludeComponent("bitrix:menu", "bottom", array("ROOT_MENU_TYPE" => "bottom", "MAX_LEVEL" => "1", "MENU_CACHE_TYPE" => "A", "MENU_CACHE_TIME" => "604800"), false);?></div>
	</div>
</div>
<? $APPLICATION->IncludeComponent('bitrix:intranet.mail.check', '', array(), false, array()); ?>
<?
$profile_link = (CModule::IncludeModule("extranet") && SITE_ID == CExtranet::GetExtranetSiteID()) ? SITE_DIR."contacts/personal" : SITE_DIR."company/personal";
$APPLICATION->IncludeComponent("bitrix:intranet.otp.info", "", array(
	"PATH_TO_PROFILE" => $profile_link."/user/#user_id#/",
	"PATH_TO_PROFILE_SECURITY" => $profile_link."/user/#user_id#/security/",
));
?>
</body>
</html>