<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetPageProperty("BodyClass", "newpost-page");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/main.post.form/mobile/script_attached.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");

if (
	isset($arResult["Post"])
	&& isset($arResult["Post"]["ID"])
	&& intval($arResult["Post"]["ID"]) > 0
)
{
	$post_id = intval($arResult["Post"]["ID"]);
}

if (
	is_array($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iFiles = count($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}
elseif (
	is_array($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iDocs = count($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}

if (
	is_array($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iFiles += count($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}

$bFilesUploaded = ($iFiles || $iDocs);

?><div class="newpost-panel-top"><?
	?><div class="attach-file-button" id="feed-add-post-image"></div><?
	?><div class="attach-dog-button" id="feed-add-post-mention"></div><?
?></div><?

?><form action="<?=$arParams["FORM_ACTION_URL"]?>" id="<?=$arParams["FORM_ID"]?>" name="<?=$arParams["FORM_ID"]?>" method="POST" enctype="multipart/form-data"<?if(strlen($arParams["FORM_TARGET"]) > 0) echo " target=\"".$arParams["FORM_TARGET"]."\""?>>
	<input type="hidden" id="<?=$arParams["FORM_ID"]?>_is_sent" name="is_sent" value="Y" /><?
	if(!empty($arParams["HIDDENS"]))
	{
		foreach($arParams["HIDDENS"] as $val)
		{
			?><input type="hidden" name="<?=$val["NAME"]?>" id="<?=$val["ID"]?>" value="<?=$val["VALUE"]?>" /><?
		}
	}
	?><?=bitrix_sessid_post();?><?
	?><textarea 
		name="POST_MESSAGE" 
		class="newpost-textarea" 
		id="POST_MESSAGE" 
		cols="30" 
		rows="10" 
		placeholder="<?=GetMessage("MFP_MENTION_TEXTAREA_TITLE")?>"
	><?=(isset($arResult["Post"]) ? $arResult["Post"]["DETAIL_TEXT"] : "")?></textarea><?
	if (isset($arResult["Post"]))
	{
		if (
			isset($arResult["Post"]["ID"])
			&& intval($arResult["Post"]["ID"]) > 0
		)
		{
			?><input type="hidden" name="post_id" id="post_id" value="<?=intval($arResult["Post"]["ID"])?>" /><?
			?><input type="hidden" name="post_user_id" id="post_user_id" value="<?=intval($arResult["Post"]["AUTHOR_ID"])?>" /><?
		}

		if (
			isset($arResult["Post"]["LogID"])
			&& intval($arResult["Post"]["LogID"]) > 0
		)
		{
			?><input type="hidden" name="log_id" id="log_id" value="<?=intval($arResult["Post"]["LogID"])?>" /><?
		}		
	}
	?>
	<input type="hidden" name="newpost_photo_counter" id="newpost_photo_counter" value="<?
		?><?=($bFilesUploaded ? ($iFiles ? $iFiles : $iDocs) : 0)?><?
	?>" />
</form><?

?><div class="newpost-controls" id="newpost-controls"><?
	?><div class="newpost-recipient newpost-recipient-shadow"><?
		?><div class="newpost-keyboard-text"><?=GetMessage("BLOG_P_DESTINATION")?></div><?
		?><div 
			id="feed-add-post-destination-button" 
			class="newpost-button newpost-button-destination" 
			ontouchstart="BX.toggleClass(this, 'newpost-button-press');" 
			ontouchend="BX.toggleClass(this, 'newpost-button-press');"
		><?=GetMessage("BLOG_P_DESTINATION_2")?></div><?
	?></div><?
	?><span id="feed-add-post-destination-hidden-container"></span><?
	?><div class="newpost-recipient-list" id="feed-add-post-destination-container"></div><?

	?><script type="text/javascript">

		BX.bind(BX('feed-add-post-image'), 'click', function(e)
		{
			if (app.enableInVersion(10))
			{
				var action = new BXMobileApp.UI.ActionSheet({
					buttons: [
						{
							title: '<?=GetMessageJS("MPF_PHOTO_CAMERA")?>',
							callback: function()
							{
								oMPF.takePhoto({type: 'camera'});
							}
						},
						{
							title: '<?=GetMessageJS("MPF_PHOTO_GALLERY")?>',
							callback: function()
							{
								oMPF.takePhoto({type: 'gallery'});
							}
						}
					]
					},
					"imageSheet"
				);
				action.show();
			}
			else
			{
				oMPF.takePhoto({type: 'gallery'});
			}
		});

		BX.addCustomEvent('onAfterMFLDeleteFile', __MPFonAfterMFLDeleteFile);
		BX.addCustomEvent('onAfterMFLDeleteElement', __MPFonAfterMFLDeleteElement);
	</script><?
?></div><?
?><div class="newpost-panel" id="newpost-panel"><div class="newpost-keyboard newpost-grey-button" id="newpost-keyboard" style="display: none;"></div><?
?><div id="newpost_progressbar_cont" class="newpost-progress" style="display: none;"><?
	?><div id="newpost_progressbar_label" class="newpost-progress-label"></div><?
	?><div id="newpost_progressbar_ind" class="newpost-progress-indicator"></div><?
?></div><?
?><div onclick="app.loadPageBlank({url: '<?=SITE_DIR?>mobile/log/new_post_images.php<?=(isset($arResult["Post"]) && isset($arResult["Post"]["ID"]) && intval($arResult["Post"]["ID"]) > 0 ? "?post_id=".intval($arResult["Post"]["ID"]) : "")?>', cache: false });" style="display: <?=($bFilesUploaded ? "block" : "none")?>;" class="newpost-info newpost-grey-button" id="newpost_photo_counter_title" ontouchstart="BX.toggleClass(this, 'newpost-info-pressed');" ontouchend="BX.toggleClass(this, 'newpost-info-pressed');"><?
	?><span><?=($bFilesUploaded ? ($iFiles ? $iFiles : $iDocs) : 0)?></span><?
	?><span>&nbsp;<?=GetMessage("MPF_PHOTO")?></span><?
?></div><?

if($arParams["USER_FIELDS"]["SHOW"] == "Y")
{

	if (
		!$arResult["diskEnabled"]
		&& !IsModuleInstalled("webdav")
	)
	{
		$eventHandlerID = false;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/mobile_app/components/bitrix/main.post.form/mobile/result_modifier.php");
		$eventHandlerID = AddEventHandler('main', 'system.field.edit.file', '__blogUFfileEditMobile');
	}

	foreach($arParams["USER_FIELDS"]["VALUE"] as $FIELD_NAME => $arPostField)
	{
		if (
			(
				!$arResult["diskEnabled"]
				&& IsModuleInstalled("webdav") 
				&& $arPostField["USER_TYPE"]["USER_TYPE_ID"] != "webdav_element"
			)
			|| (
				$arResult["diskEnabled"]
				&& IsModuleInstalled("disk") 
				&& $arPostField["USER_TYPE"]["USER_TYPE_ID"] != "disk_file"
			)
			|| (
				!IsModuleInstalled("webdav") 
				&& !IsModuleInstalled("disk") 
				&& $arPostField["USER_TYPE"]["USER_TYPE_ID"] != "files"
			)
		)
		{
			continue;
		}

		$APPLICATION->IncludeComponent(
			"bitrix:system.field.edit", $arPostField["USER_TYPE"]["USER_TYPE_ID"],
			array(
				"arUserField" => $arPostField,
				"POST_ID" => (isset($arResult["Post"]) && isset($arResult["Post"]["ID"]) && intval($arResult["Post"]["ID"]) > 0 ? intval($arResult["Post"]["ID"]) : 0)
			), 
			null, 
			array("HIDE_ICONS"=>"Y")
		);
	}

	if (
		!$arResult["diskEnabled"]
		&& !IsModuleInstalled("webdav")
		&& $eventHandlerID !== false 
		&& intval($eventHandlerID) > 0
	)
	{
		RemoveEventHandler('main', 'system.field.edit.file', $eventHandlerID);
	}

}

?></div>
<script type="text/javascript">

	BX.message({
		'DENY_TOALL': '<?=($arResult["DENY_TOALL"] ? 'Y' : 'N')?>',
		'DEFAULT_TOALL': '<?=($arResult["DEFAULT_TOALL"] ? 'Y' : 'N')?>',
		'MPF_TABLE_OK': '<?=GetMessageJS("MPF_TABLE_OK")?>',
		'MPF_TABLE_CANCEL': '<?=GetMessageJS("MPF_TABLE_CANCEL")?>',
		'MPF_SEND': '<?=GetMessageJS("MPF_SEND")?>',
		'MPF_CANCEL': '<?=GetMessageJS("MPF_CANCEL")?>'
	});

	document.addEventListener("deviceready", function()
	{
		oMPF.Init({
			uri: '<?=CUtil::JSEscape((CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"].$APPLICATION->GetCurPageParam("", array("bxajaxid", "logout")))?>',
			uriSession: '<?=CUtil::JSEscape((CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"].$APPLICATION->GetCurPageParam(bitrix_sessid_get(), array("bxajaxid", "logout")))?>',
			destinationUri: '<?=SITE_DIR?>mobile/index.php?mobile_action=<?=(isset($arParams["IS_EXTRANET"]) && $arParams["IS_EXTRANET"] == "Y" ? 'get_group_list' : 'get_usergroup_list')?>',
			mentionUri: '<?=SITE_DIR?>mobile/index.php?mobile_action=get_user_list',
			liveFeedId: '<?=CUtil::JSEscape($_REQUEST["feed_id"])?>',
			formId: '<?=CUtil::JSEscape($arParams["FORM_ID"])?>',
			postId: <?=(isset($post_id) ? intval($post_id) : 0)?>
		});

		<?
		if (
			!isset($arResult["Post"])
			|| empty($arResult["Post"])
		)
		{
			?>
			BX.MSL.DBLoad({
				onLoad: function(obResult) {

					var arItemsSelected = [];

					if (
						typeof obResult['SPERM[UA]'] != 'undefined'
						&& obResult['SPERM[UA]'] != null
						&& BX.message('DENY_TOALL') != 'Y'
					)
					{
						arItemsSelected[arItemsSelected.length] = {
							type: 'groups',
							item: {
								id: 'UA',
								name: '<?=GetMessageJS("MFP_DEST_UA")?>'
							}
						};
					}

					if (
						typeof obResult['SPERM[U]'] != 'undefined'
						&& obResult['SPERM[U]'] != null
					)
					{
						for (var i = 0; i < obResult['SPERM[U]'].length; i++)				
						{
							arItemsSelected[arItemsSelected.length] = {
								type: 'users',
								item: {
									id: obResult['SPERM[U]'][i],
									name: (obResult['SPERM_NAME[U]'][i] != undefined && obResult['SPERM_NAME[U]'][i] != null
										? obResult['SPERM_NAME[U]'][i]
										: ''
									)
								}
							};
						}
					}

					if (obResult['SPERM[SG]'] != undefined && obResult['SPERM[SG]'] != null)
					{
						for (var i = 0; i < obResult['SPERM[SG]'].length; i++)
						{
							arItemsSelected[arItemsSelected.length] = {
								type: 'sonetgroups',
								item: {
									id: obResult['SPERM[SG]'][i],
									name: (obResult['SPERM_NAME[SG]'][i] != undefined  && obResult['SPERM_NAME[SG]'][i] != null
										? obResult['SPERM_NAME[SG]'][i]
										: ''
									)
								}
							};
						}
					}

					oMPF.initDestinationEx(arItemsSelected);

					if (
						typeof obResult.POST_MESSAGE != 'undefined'
						&& obResult.POST_MESSAGE != null
					)
					{
						BX('POST_MESSAGE').value = oMPF.unParseMentions(obResult.POST_MESSAGE);
					}

					<?
					if (intval($arParams["SOCNET_GROUP_ID"]) > 0)
					{
						?>
						if (arItemsSelected === undefined || arItemsSelected.length <= 0)
						{
							oMPF.initDestination({
								id: 'SG<?=intval($arParams["SOCNET_GROUP_ID"])?>', 
								name: '<?=CUtil::JSEscape($arResult["SONET_GROUP_NAME"])?>' 
							}, 'sonetgroups');
						}
						<?
					}
					else
					{
						?>
						if (
							(
								arItemsSelected === undefined 
								|| arItemsSelected.length <= 0
							)
							&& BX.message('DEFAULT_TOALL') == 'Y'
						)
							oMPF.initDestination({
								id: 'UA',
								name: '<?=CUtil::JSEscape(GetMessage("MFP_DEST_UA"))?>'
							}, 'groups');
						<?
					}
					?>
				},
				onEmpty: function(obResult) {
					<?
					if (intval($arParams["SOCNET_GROUP_ID"]) > 0)
					{
						?>
						oMPF.initDestination({
							id: 'SG<?=intval($arParams["SOCNET_GROUP_ID"])?>', 
							name: '<?=CUtil::JSEscape($arResult["SONET_GROUP_NAME"])?>'
						}, 'sonetgroups');
						<?
					}
					else
					{
						?>
						if (BX.message('DEFAULT_TOALL') == 'Y')
						{
							oMPF.initDestination({
								id: 'UA',
								name: '<?=CUtil::JSEscape(GetMessage("MFP_DEST_UA"))?>'
							}, 'groups');
						}
						<?
					}
					?>
				}
			}, <?=(intval($arParams["SOCNET_GROUP_ID"]) > 0 ? $arParams["SOCNET_GROUP_ID"] : 'false')?>);
			<?
		}
		elseif (
			isset($arResult["Post"]["arSonetPerms"])
			&& is_array($arResult["Post"]["arSonetPerms"])
		)
		{
			?>
			var arItemsSelected = [];
			var arItemsSelectedHidden = [];
			<?
			foreach($arResult["Post"]["arSonetPerms"] as $arSonetPerm)
			{
				?>
				arItemsSelected[arItemsSelected.length] = {
					type: '<?=$arSonetPerm["type"]?>',
					item: {
						id: '<?=$arSonetPerm["item"]["id"]?>',
						name: '<?=($arSonetPerm["item"]["id"] == "UA" ? GetMessageJS("MFP_DEST_UA") : CUtil::JSEscape($arSonetPerm["item"]["name"]))?>'
					}
				};
				<?
			}

			foreach($arResult["Post"]["arSonetPermsHidden"] as $arSonetPermHidden)
			{
				?>
				arItemsSelectedHidden[arItemsSelectedHidden.length] = {
					type: '<?=$arSonetPermHidden["type"]?>',
					item: {
						id: '<?=$arSonetPermHidden["item"]["id"]?>',
						name: ''
					}
				};
				<?
			}
			?>
			oMPF.initDestinationEx(arItemsSelected, arItemsSelectedHidden);
			<?
		}
		?>
	}, false);
</script>
