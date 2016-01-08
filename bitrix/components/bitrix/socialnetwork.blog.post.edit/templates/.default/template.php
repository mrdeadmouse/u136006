<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
$arParams["FORM_ID"] = "blogPostForm";
$jsObjName = "oPostFormLHE_".$arParams["FORM_ID"];
$id = "idPostFormLHE_".$arParams["FORM_ID"];

$bCalendar = $arParams["B_CALENDAR"];
$bLists = $arResult["BLOG_POST_LISTS"];

if (
	!empty($arResult["Post"])
	|| isset($arParams["DISPLAY"])
	|| (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()) // Disable calendar feature for extranet users
)
{
	$bCalendar = false;
	$bLists = false;
}

$bFile = true;
if (
	!empty($arResult["Post"])
	|| !array_key_exists("UF_BLOG_POST_FILE", $arResult["POST_PROPERTIES"]["DATA"])
)
{
	$bFile = false;
}

if($arResult["delete_blog_post"] == "Y")
{
	$APPLICATION->RestartBuffer();
	if (!empty($arResult["ERROR_MESSAGE"]))
	{
		?>
		<script bxrunfirst="yes">
			top.deletePostEr = 'Y';
		</script>
		<div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div>
		<?
	}
	if(!empty($arResult["OK_MESSAGE"]))
	{
		?><div class="feed-add-successfully">
			<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["OK_MESSAGE"]?></span>
		</div><?
	}
	die();
}

if(!empty($arResult["FATAL_MESSAGE"]))
{
	?><div class="feed-add-error">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["FATAL_MESSAGE"]?></span>
	</div><?

	return false;
}

?><div class="feed-wrap">
	<div id="feed-add-post-block<?=$arParams["FORM_ID"]?>" class="feed-add-post-block blog-post-edit"><?
if (!empty($arResult["OK_MESSAGE"]) || !empty($arResult["ERROR_MESSAGE"]))
{
	?><div id="feed-add-post-form-notice-block<?=$arParams["FORM_ID"]?>" class="feed-notice-block" style="display:none;"><?
	if(!empty($arResult["OK_MESSAGE"]))
	{
		?><div class="feed-add-successfully">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["OK_MESSAGE"]?></span>
		</div><?
	}
	if(!empty($arResult["ERROR_MESSAGE"]))
	{
		?><div class="feed-add-error">
			<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["ERROR_MESSAGE"]?></span>
		</div><?
	}
	?></div><?
}
if(!empty($arResult["UTIL_MESSAGE"]))
{
	?>
	<div class="feed-add-successfully">
		<span class="feed-add-info-icon"></span><span class="feed-add-info-text"><?=$arResult["UTIL_MESSAGE"]?></span>
	</div>
	<?
}
else if($arResult["imageUploadFrame"] == "Y") // Frame with file input to ajax uploading in WYSIWYG editor dialog
{
?>
<script type="text/javascript">
<?if(!empty($arResult["Image"]))
{
	?>
	var imgTable = top.BX('blog-post-image');
	if (imgTable)
	{
		imgTable.innerHTML += '<span class="feed-add-photo-block"><span class="feed-add-img-wrap"><?=$arResult["ImageModified"]?></span><span class="feed-add-img-title"><?=$arResult["Image"]["fileName"]?></span><span class="feed-add-post-del-but" onclick="DeleteImage(\'<?=$arResult["Image"]["ID"]?>\', this)"></span><input type="hidden" id="blgimg-<?=$arResult["Image"]["ID"]?>" value="<?=$arResult["Image"]["source"]["src"]?>"></span>';
		imgTable.parentNode.parentNode.style.display = 'block';
	}

	top.bxPostFileId = '<?=$arResult["Image"]["ID"]?>';
	top.bxPostFileIdSrc = '<?=CUtil::JSEscape($arResult["Image"]["source"]["src"])?>';
	top.bxPostFileIdWidth = '<?=CUtil::JSEscape($arResult["Image"]["source"]["width"])?>';
	<?
}
elseif(strlen($arResult["ERROR_MESSAGE"]) > 0)
{
	?>
	window.bxPostFileError = top.bxPostFileError = '<?=CUtil::JSEscape($arResult["ERROR_MESSAGE"])?>';
	<?
}?>
</script>
<?
die();
}
else
{
	$bVarsFromForm = (array_key_exists("POST_MESSAGE", $_REQUEST) || strlen($arResult["ERROR_MESSAGE"]) > 0 || $arResult["needShow"]);
	$activeTab = ($bVarsFromForm ? $_REQUEST["changePostFormTab"] : "message");
	$arSmiles = array();
	$userOption = CUserOptions::GetOption("socialnetwork", "postEdit");
	$bShowTitle = (($arResult["PostToShow"]["MICRO"] != "Y" && !empty($arResult["PostToShow"]["TITLE"])) ||
			(isset($userOption["showTitle"]) && $userOption["showTitle"] == "Y" && $arResult["PostToShow"]["MICRO"] != "Y"));
	if(!empty($arResult["Smiles"]))
	{
		foreach($arResult["Smiles"] as $arSmile)
		{
			$arSmiles[] = array(
				'name' => $arSmile["~LANG_NAME"],
				'path' => "/bitrix/images/blog/smile/".$arSmile["IMAGE"],
				'code' => str_replace("\\\\","\\",$arSmile["TYPE"]),
				'width' => $arSmile["IMAGE_WIDTH"],
				'height' => $arSmile["IMAGE_HEIGHT"],
			);
		}
	}

	if (array_key_exists("UF_BLOG_POST_VOTE", $arResult["POST_PROPERTIES"]["DATA"]))
	{
		$bVote = true;

		if (!$bVarsFromForm && !!$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"]["VALUE"])
			$activeTab = "vote";
	}
	if (
		IsModuleInstalled("intranet")
		&& array_key_exists("GRATS", $arResult["PostToShow"])
		&& is_array($arResult["PostToShow"]["GRATS"])
		&& count($arResult["PostToShow"]["GRATS"]) > 0
	)
	{
		$bGrat = true;
		if (
			!$bVarsFromForm
			&& (
				!empty($arResult["PostToShow"]["GRAT_CURRENT"]["ID"])
				|| !empty($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
			)
		)
		{
			$activeTab = "grat";
		}
	}
	if (array_key_exists("UF_BLOG_POST_IMPRTNT", $arResult["POST_PROPERTIES"]["DATA"]))
	{
		if (!$bVarsFromForm && !!$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"])
			$activeTab = "important";
	}

	ob_start();

	$arTabs = array(
		array(
			"ID" => "message",
			"NAME" => GetMessage("BLOG_TAB_POST")
		)
	);

	if ($bFile)
	{
		$arTabs[] = array(
			"ID" => "file",
			"NAME" => GetMessage("BLOG_TAB_FILE")
		);
	}

	if ($bCalendar)
	{
		$arTabs[] = array(
			"ID" => "calendar",
			"NAME" => GetMessage("SBPE_CALENDAR_EVENT"),
			"ONCLICK" => "BX.onCustomEvent('onCalendarLiveFeedShown');"
		);
	}

	if($bLists)
	{
		$arTabs[] = array(
			"ID" => "lists",
			"NAME" => GetMessage("BLOG_TAB_LISTS"),
			"ICON" => "feed-add-post-form-polls-link-icon"
		);
	}

	if ($bVote)
	{
		$arTabs[] = array(
			"ID" => "vote",
			"NAME" => GetMessage("BLOG_TAB_VOTE"),
			"ICON" => "feed-add-post-form-polls-link-icon"
		);
	}

	if ($bGrat)
	{
		$arTabs[] = array(
			"ID" => "grat",
			"NAME" => GetMessage("BLOG_TAB_GRAT")
		);
	}

	$arTabs[] = array(
		"ID" => "important",
		"NAME" => GetMessage("SBPE_IMPORTANT_MESSAGE")
	);

	if($bLists)
		$maxTabs = 4;
	else
		$maxTabs = 3;

	$tabsCnt = count($arTabs);
	for ($i = 0; $i < $maxTabs; $i++)
	{
		$arTab = $arTabs[$i];
		$moreClass = ($activeTab == $arTab["ID"] ? " feed-add-post-form-link-active" : "");
		if($arTab["ID"] == "lists")
		{
			?><span class="feed-add-post-form-link<?=$moreClass?>" <?
			?>id="feed-add-post-form-tab-<?=$arTab["ID"]?>" <?
			?>onclick="window.SBPETabs.getInstance().getLists();"><?
			?><span class="feed-add-post-form-lists-link-icon"></span><?
			?><span id="feed-add-post-form-tab-lists" class="feed-add-post-form-link-text"><?=$arTab["NAME"]?></span><?
			?><span class="feed-add-post-more-icon-lists"></span></span><?
		}
		else
		{
			?><span class="feed-add-post-form-link<?=$moreClass?>" <?
			?>id="feed-add-post-form-tab-<?=$arTab["ID"]?>" <?
			?>onclick="window.SBPETabs.changePostFormTab('<?=$arTab["ID"]?>');<?=(isset($arTab["ONCLICK"]) ? $arTab["ONCLICK"] : "")?>"><?
			?><span class="<?=(isset($arTab["ICON"]) ? $arTab["ICON"] : "feed-add-post-form-".$arTab["ID"]."-link-icon" )?>"></span><?
			?><span><?=$arTab["NAME"]?></span><?
			?></span><?
		}
	}

	if ($tabsCnt > $maxTabs)
	{
		$moreCaption = GetMessage("SBPE_MORE");
		$moreClass = "";
		$pseudoTabs = "";

		for ($i = $maxTabs; $i < $tabsCnt; $i++)
		{
			$arTab = $arTabs[$i];
			$pseudoTabs .= '<span class="feed-add-post-form-link" data-onclick="'.(isset($arTab["ONCLICK"]) ? $arTab["ONCLICK"] : "").'" data-name="'.$arTab["NAME"].'" id="feed-add-post-form-tab-'.$arTab["ID"].'" style="display:none;"></span>';
			if ($activeTab == $arTab["ID"] && $maxTabs > 0)
			{
				$moreCaption = $arTab["NAME"];
				$moreClass = " feed-add-post-form-".$arTab["ID"]."-link";
			}
		}

		?><span id="feed-add-post-form-link-more" <?
				?>class="feed-add-post-form-link feed-add-post-form-link-more<?=$moreClass?>" <?
				?>onclick="window.SBPETabs.getInstance().showMoreMenu();"><?
			?><span class="feed-add-post-form-link-icon"></span><?
			?><span id="feed-add-post-form-link-text" class="feed-add-post-form-link-text"><?=$moreCaption?></span><?
			?><span class="feed-add-post-more-icon"></span><?=$pseudoTabs?><?
		?></span><?
	}

	$strGratVote = ob_get_contents();
	ob_end_clean();

	if ($arParams["TOP_TABS_VISIBLE"] == "Y")
	{
		?><div class="microblog-top-tabs-visible"><?
			?><div class="feed-add-post-form-variants" id="feed-add-post-form-tab"><?
				echo $strGratVote;
				$APPLICATION->ShowViewContent("sonet_blog_form");
				?><div id="feed-add-post-form-tab-arrow" class="feed-add-post-form-arrow" style="left: 31px;"></div><?
			?></div><?
		?></div><?
	}
	$htmlAfterTextarea = "";
	if (!empty($arResult["Images"]))
	{
		$arFile = reset($arResult["Images"]);
		$arJSFiles = array();
		while ($arFile)
		{
			$arJSFiles[strVal($arFile["ID"])] = array(
				"element_id" => $arFile["ID"],
				"element_name" => $arFile["FILE_NAME"],
				"element_size" => $arFile["FILE_SIZE"],
				"element_url" => $arFile["URL"],
				"element_content_type" => $arFile["CONTENT_TYPE"],
				"element_thumbnail" => $arFile["SRC"],
				"element_image" => $arFile["THUMBNAIL"],
				"isImage" => (substr($arFile["CONTENT_TYPE"], 0, 6) == "image/"),
				"del_url" => $arFile["DEL_URL"]
			);
			$title = GetMessage("MPF_INSERT_FILE");
			$arFile["DEL_URL"] = CUtil::JSEscape($arFile["DEL_URL"]);
$htmlAfterTextarea .= <<<HTML
<span class="feed-add-photo-block" id="wd-doc{$arFile["ID"]}">
	<span class="feed-add-img-wrap" title="{$title}">
		<img src="{$arFile["THUMBNAIL"]}" border="0" width="90" height="90" />
	</span>
	<span class="feed-add-img-title" title="{$title}">{$arFile["NAME"]}</span>
	<span class="feed-add-post-del-but"></span>
</span>
HTML;
			$arFile = next($arResult["Images"]);
		}
		if ($htmlAfterTextarea !== "")
		{
			$arJSFiles = CUtil::PhpToJSObject($arJSFiles);
$htmlAfterTextarea .= <<<HTML
<script>window['{$id}Files']={$arJSFiles};</script>
HTML;
		}
	}
?>
<div id="microblog-form">
<form action="<?=(isset($arParams["POST_FORM_ACTION_URI"]) ? htmlspecialcharsbx($arParams["POST_FORM_ACTION_URI"]) : POST_FORM_ACTION_URI)?>" id="blogPostForm" name="blogPostForm" method="POST" enctype="multipart/form-data" target="_self">
	<input type="hidden" name="show_title" id="show_title" value="<?=($bShowTitle ? "Y" : "N")?>">
	<?=bitrix_sessid_post();?>
	<div class="feed-add-post-form-wrap"><?
		if ($arParams["TOP_TABS_VISIBLE"] != "Y")
		{
			?><div class="feed-add-post-form-variants" id="feed-add-post-form-tab"><?
				echo $strGratVote;
				$APPLICATION->ShowViewContent("sonet_blog_form");
				?><div id="feed-add-post-form-tab-arrow" class="feed-add-post-form-arrow" style="left: 31px;"></div><?
			?></div><?
		}

		?><div id="feed-add-post-content-message">
			<div class="feed-add-post-title" id="blog-title" style="display: none;">
				<input id="POST_TITLE" name="POST_TITLE" class="feed-add-post-inp feed-add-post-inp-active" <?
				?>type="text" value="<?=$arResult["PostToShow"]["TITLE"]?>" placeholder="<?=GetMessage("BLOG_TITLE")?>" />
				<div class="feed-add-close-icon" onclick="showPanelTitle_<?=$arParams["FORM_ID"]?>(false);"></div>
			</div>
			<?$APPLICATION->IncludeComponent(
				"bitrix:main.post.form",
				"",
				($formParams = Array(
					"FORM_ID" => "blogPostForm",
					"SHOW_MORE" => "Y",
					"PARSER" => Array("Bold", "Italic", "Underline", "Strike", "ForeColor",
						"FontList", "FontSizeList", "RemoveFormat", "Quote", "Code",
						(($arParams["USE_CUT"] == "Y") ? "InsertCut" : ""),
						"CreateLink",
						"Image",
						"Table",
						"Justify",
						"InsertOrderedList",
						"InsertUnorderedList",
						"SmileList",
						"Source",
						"UploadImage",
						(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
						"MentionUser",
					),
					"BUTTONS" => Array(
						"UploadImage",
						"UploadFile",
						"CreateLink",
						(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
						"Quote",
						"MentionUser",
						"InputTag"
					),
					"ADDITIONAL" => array(
						"<span title=\"".GetMessage("BLOG_TITLE")."\" ".
							"onclick=\"showPanelTitle_".$arParams["FORM_ID"]."(this);\" ".
							"class=\"feed-add-post-form-title-btn".($bShowTitle ? " feed-add-post-form-btn-active" : "")."\" ".
							"id=\"lhe_button_title_".$arParams["FORM_ID"]."\" ".
							"></span>"
					),

					"TEXT" => Array(
						"NAME" => "POST_MESSAGE",
						"VALUE" => htmlspecialcharsBack($arResult["PostToShow"]["~DETAIL_TEXT"]),
						"HEIGHT" => "120px"),

					"PROPERTIES" => array(
						array_key_exists("UF_BLOG_POST_FILE", $arResult["POST_PROPERTIES"]["DATA"]) ?
							array_merge(
								(is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) ? $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"] : array()),
								($bVarsFromForm && is_array($_POST["UF_BLOG_POST_FILE"]) ? array("VALUE" => $_POST["UF_BLOG_POST_FILE"]) : array()))
							:
							array_merge(
								(is_array($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"]) ? $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"] : array()),
								($bVarsFromForm && is_array($_POST["UF_BLOG_POST_DOC"]) ? array("VALUE" => $_POST["UF_BLOG_POST_DOC"]) : array()),
								array("POSTFIX" => "file"))),
					"UPLOAD_FILE_PARAMS" => array('width' => $arParams["IMAGE_MAX_WIDTH"], 'height' => $arParams["IMAGE_MAX_HEIGHT"]),

					"DESTINATION" => array(
						"VALUE" => $arResult["PostToShow"]["FEED_DESTINATION"],
						"SHOW" => "Y"
					),

					"TAGS" => Array(
						"ID" => "TAGS",
						"NAME" => "TAGS",
						"VALUE" => explode(",", trim($arResult["PostToShow"]["CategoryText"])),
						"USE_SEARCH" => "Y",
						"FILTER" => "blog",
					),
					"SMILES" => array("VALUE" => $arSmiles),
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"AT_THE_END_HTML" => $htmlAfterTextarea,
					"LHE" => array(
						"id" => $id,
						"documentCSS" => "body {color:#434343;}",
						"ctrlEnterHandler" => "submitBlogPostForm",
						"jsObjName" => $jsObjName,
						"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
						"fontSize" => "14px",
						"bInitByJS" => (!$bVarsFromForm && $arParams["TOP_TABS_VISIBLE"] == "Y")
					),
					"USE_CLIENT_DATABASE" => "Y"
				)),
				false,
				Array("HIDE_ICONS" => "Y")
			);?>
		</div><?
	?></div><? //feed-add-post-form-wrap
	?><div id="feed-add-post-content-message-add-ins"><?
	if ($bVote)
	{
		?><div id="feed-add-post-content-vote" style="display: none;"><?
		if (IsModuleInstalled("vote"))
		{
			$APPLICATION->IncludeComponent(
				"bitrix:system.field.edit",
				"vote",
				array(
					"bVarsFromForm" => $bVarsFromForm,
					"arUserField" => $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"]),
				null,
				array("HIDE_ICONS" => "Y")
			);
		}
		?></div><?
	}
	?><div id="feed-add-post-content-important" style="display: none;"><?
		?><span style="display: none;"><?
		$APPLICATION->IncludeComponent(
			"bitrix:system.field.edit",
			"integer",
			array(
				"bVarsFromForm" => $bVarsFromForm,
				"arUserField" => $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]),
			null,
			array("HIDE_ICONS" => "Y")
		);
		?></span><?
	?></div><?
	if ($bGrat)
	{
		?><div id="feed-add-post-content-grat" style="display: <?=($activeTab == "grat" ? "block" : "none")?>;"><?
			if (
				array_key_exists("GRAT_CURRENT", $arResult["PostToShow"])
				&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
			)
			{
				$arGratCurrentUsers = array();
				foreach($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"] as $grat_user_id)
				{
					$arGratCurrentUsers["U".$grat_user_id] = 'users';
				}
			}
			?><div class="feed-add-grat-block feed-add-grat-star"><?

				$grat_type = ""; $title_default = "";

				if (
					is_array($arResult["PostToShow"]["GRAT_CURRENT"])
					&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"])
				)
				{
					$grat_type = htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"]);
					$class_default = "feed-add-grat-medal-".htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"]);
					$title_default = htmlspecialcharsbx($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["VALUE_ENUM"]);
				}
				elseif (is_array($arResult["PostToShow"]["GRATS_DEF"]))
				{
					$grat_type = htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["XML_ID"]);
					$class_default = "feed-add-grat-medal-".htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["XML_ID"]);
					$title_default = htmlspecialcharsbx($arResult["PostToShow"]["GRATS_DEF"]["VALUE"]);
				}

				?><div id="feed-add-post-grat-type-selected" class="feed-add-grat-medal"<?=($title_default ? ' title="'.$title_default.'"' : '')?>>
					<span class="feed-add-grat-box<?=($class_default ? " ".$class_default : "")?>"></span>
					<div id="feed-add-post-grat-others" class="feed-add-grat-medal-other"><?=GetMessage("BLOG_TITLE_GRAT_OTHER")?></div>
					<div class="feed-add-grat-medal-arrow"></div>
				</div>
				<input type="hidden" name="GRAT_TYPE" value="<?=htmlspecialcharsbx($grat_type)?>" id="feed-add-post-grat-type-input">
				<script type="text/javascript">

					var arGrats = [];
					var	BXSocNetLogGratFormName = '<?=$this->randString(6)?>';
					<?
					if (is_array($arResult["PostToShow"]["GRATS"]))
					{
						foreach($arResult["PostToShow"]["GRATS"] as $i => $arGrat)
						{
							?>
							arGrats[<?=CUtil::JSEscape($i)?>] = {
								'title': '<?=CUtil::JSEscape($arGrat["VALUE"])?>',
								'code': '<?=CUtil::JSEscape($arGrat["XML_ID"])?>',
								'style': 'feed-add-grat-medal-<?=CUtil::JSEscape($arGrat["XML_ID"])?>'
							};
							<?
						}
					}
					?>

					BX.SocNetGratSelector.init({
						'name' : BXSocNetLogGratFormName,
						'itemSelectedImageItem' : BX('feed-add-post-grat-type-selected'),
						'itemSelectedInput' : BX('feed-add-post-grat-type-input')
					});
					BX.bind(BX('feed-add-post-grat-type-selected'), 'click', function(e){BX.SocNetGratSelector.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
				</script>
				<div class="feed-add-grat-right">
					<div class="feed-add-grat-label"><?=GetMessage("BLOG_TITLE_GRAT")?></div>
					<div class="feed-add-grat-form">
						<div class="feed-add-post-grat-wrap feed-add-post-destination-wrap" id="feed-add-post-grat-container">
							<span id="feed-add-post-grat-item"></span>
							<span class="feed-add-grat-input-box" id="feed-add-post-grat-input-box">
								<input type="text" value="" class="feed-add-grat-inp" id="feed-add-post-grat-input">
							</span>
							<a href="#" class="feed-add-grat-link" id="bx-grat-tag"><?
							if (
								!is_array($arResult["PostToShow"]["GRAT_CURRENT"])
								|| count($arResult["PostToShow"]["GRAT_CURRENT"]) <= 0
							)
								echo GetMessage("BLOG_GRATMEDAL_1");
							?></a>
							<script type="text/javascript">
							var department = <?=(empty($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT']))?>;
							<?if(empty($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION']))
							{
								?>
								var relation = {};
								for(var iid in department)
								{
									var p = department[iid]['parent'];
									if (!relation[p])
										relation[p] = [];
									relation[p][relation[p].length] = iid;
								}
								function makeDepartmentTree(id, relation)
								{
									var arRelations = {};
									if (relation[id])
									{
										for (var x in relation[id])
										{
											var relId = relation[id][x];
											var arItems = [];
											if (relation[relId] && relation[relId].length > 0)
												arItems = makeDepartmentTree(relId, relation);

											arRelations[relId] = {
												id: relId,
												type: 'category',
												items: arItems
											};
										}
									}

									return arRelations;
								}
								var departmentRelation = makeDepartmentTree('DR0', relation);
								<?
							}
							else
							{
								?>var departmentRelation = <?=CUtil::PhpToJSObject($arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION'])?>;<?
							}
							?>


								BX.message({
									'BX_FPGRATMEDAL_LINK_1': '<?=GetMessageJS("BLOG_GRATMEDAL_1")?>',
									'BX_FPGRATMEDAL_LINK_2': '<?=GetMessageJS("BLOG_GRATMEDAL_2")?>',
									'BLOG_GRAT_POPUP_TITLE': '<?=GetMessageJS("BLOG_GRAT_POPUP_TITLE")?>'
								});

								BX.SocNetLogDestination.init({
									'name' : BXSocNetLogGratFormName,
									'searchInput' : BX('feed-add-post-grat-input'),
									'pathToAjax' : '/bitrix/components/bitrix/socialnetwork.blog.post.edit/post.ajax.php',
									'extranetUser' : false,
									'bindMainPopup' : { 'node' : BX('feed-add-post-grat-container'), 'offsetTop' : '-5px', 'offsetLeft': '15px'},
									'bindSearchPopup' : { 'node' : BX('feed-add-post-grat-container'), 'offsetTop' : '-5px', 'offsetLeft': '15px'},
									'departmentSelectDisable' : true,
									'lastTabDisable' : true,
									'callback' : {
										'select' : BXfpGratSelectCallback,
										'unSelect' : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
											formName: window['BXSocNetLogGratFormName'],
											inputContainerName: 'feed-add-post-grat-item',
											inputName: 'feed-add-post-grat-input',
											tagInputName: 'bx-grat-tag',
											tagLink1: BX.message('BX_FPGRATMEDAL_LINK_1'),
											tagLink2: BX.message('BX_FPGRATMEDAL_LINK_2')
										}),
										'openDialog' : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
											inputBoxName: 'feed-add-post-grat-input-box',
											inputName: 'feed-add-post-grat-input',
											tagInputName: 'bx-grat-tag'
										}),
										'closeDialog' : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
											inputBoxName: 'feed-add-post-grat-input-box',
											inputName: 'feed-add-post-grat-input',
											tagInputName: 'bx-grat-tag'
										}),
										'openSearch' : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
											inputBoxName: 'feed-add-post-grat-input-box',
											inputName: 'feed-add-post-grat-input',
											tagInputName: 'bx-grat-tag'
										}),
										'closeSearch' : BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback, {
											inputBoxName: 'feed-add-post-grat-input-box',
											inputName: 'feed-add-post-grat-input',
											tagInputName: 'bx-grat-tag'
										})
									},
									'items' : {
										'users' : <?=((array_key_exists("GRAT_CURRENT", $arResult["PostToShow"]) && is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"])) ? CUtil::PhpToJSObject($arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"]) : '{}')?>,
										'groups' : {},
										'sonetgroups' : {},
										'department' : department,
										'departmentRelation' : departmentRelation
									},
									'itemsLast' : {
										'users' : {},
										'sonetgroups' : {},
										'department' : {},
										'groups' : {}
									},
									'itemsSelected' : <?=(($arGratCurrentUsers && is_array($arGratCurrentUsers)) ? CUtil::PhpToJSObject($arGratCurrentUsers) : '{}')?>,
									'LHEObjName' : '<?=CUtil::JSEscape($jsObjName)?>'
								});
								BX.bind(BX('feed-add-post-grat-input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
									formName: window["BXSocNetLogGratFormName"],
									inputName: 'feed-add-post-grat-input',
									tagInputName: 'bx-grat-tag'
								}));
								BX.bind(BX('feed-add-post-grat-input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
									formName: window["BXSocNetLogGratFormName"],
									inputName: 'feed-add-post-grat-input'
								}));
								BX.bind(BX('bx-grat-tag'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
								BX.bind(BX('feed-add-post-grat-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogGratFormName); BX.PreventDefault(e); });
							</script>
						</div>
					</div>
				</div>
			</div><?
		?></div><?
	}
	foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
	{
		if(in_array($FIELD_NAME, $arParams["POST_PROPERTY_SOURCE"]))
		{
			?>
			<div id="blog-post-user-fields-<?=$FIELD_NAME?>"><?=$arPostField["EDIT_FORM_LABEL"].":"?>
				<?$APPLICATION->IncludeComponent(
					"bitrix:system.field.edit",
					$arPostField["USER_TYPE"]["USER_TYPE_ID"],
					array("arUserField" => $arPostField), null, array("HIDE_ICONS"=>"Y"));?>
			</div>
			<div class="blog-clear-float"></div>
		<?
		}
	}

	if ($bCalendar)
	{
		?>
	<div id="feed-add-post-content-calendar" style="display: none;">
		<?
		$APPLICATION->IncludeComponent("bitrix:calendar.livefeed.edit", '',
			array(
				"EVENT_ID" => '',
				"UPLOAD_FILE" => (!empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]) ? false : $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"]),
				"UPLOAD_WEBDAV_ELEMENT" => $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"],
				"UPLOAD_FILE_PARAMS" => array('width' => $arParams["IMAGE_MAX_WIDTH"], 'height' => $arParams["IMAGE_MAX_HEIGHT"]),
				"FILES" => Array(
					"VALUE" => $arResult["Images"],
					"POSTFIX" => "file",
				),
				"DESTINATION" => array(
					"VALUE" => $arResult["PostToShow"]["FEED_DESTINATION"],
					"SHOW" => "Y"
				),
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]

			), null, array("HIDE_ICONS"=>"Y"));
		?>
	</div>
		<?
	}

	if($bLists)
	{
		?>
		<div id="feed-add-post-content-lists" style="display: none;">
			<?
			$APPLICATION->IncludeComponent("bitrix:lists.live.feed", "",
				array(
					"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
					"DESTINATION" => $arResult["PostToShow"],
					"IBLOCK_ID" => isset($_GET['bp_setting']) ? $_GET['bp_setting'] : 0
				), null, array("HIDE_ICONS" => "Y")
			);
			?>
		</div>
		<?
	}

?></div>
<script type="text/javascript">
BX.message({
	'BLOG_TITLE' : '<?=GetMessageJS("BLOG_TITLE")?>',
	'SBPE_MORE' : '<?=GetMessageJS("SBPE_MORE")?>',
	'BLOG_TAB_GRAT': '<?=GetMessageJS("BLOG_TAB_GRAT")?>',
	'BLOG_TAB_VOTE': '<?=GetMessageJS("BLOG_TAB_VOTE")?>',
	'SBPE_IMPORTANT_MESSAGE': '<?=GetMessageJS("SBPE_IMPORTANT_MESSAGE")?>',
	'BLOG_POST_AUTOSAVE':'<?=GetMessageJS("BLOG_POST_AUTOSAVE")?>',
	'BLOG_POST_AUTOSAVE2' : '<?=GetMessageJS("BLOG_POST_AUTOSAVE2")?>',
	'SBPE_CALENDAR_EVENT': '<?=GetMessageJS("SBPE_CALENDAR_EVENT")?>',
	'LISTS_CATALOG_PROCESSES_ACCESS_DENIED' : '<?=GetMessageJS("LISTS_CATALOG_PROCESSES_ACCESS_DENIED")?>'
});
BX.SocnetBlogPostInit('<?=$arParams["FORM_ID"]?>', {
	editorID : '<?=$id?>',
	showTitle : '<?=$bShowTitle?>',
	autoSave : '<?=(COption::GetOptionString("blog", "use_autosave", "Y") == "Y" ? ($arParams["ID"] > 0 ? "onDemand" : "Y") : 'N')?>',
	activeTab : '<?=($bVarsFromForm || $arParams["ID"] > 0 ? $activeTab : '')?>',
	text : '<?=CUtil::JSEscape($formParams["TEXT"]["VALUE"])?>'
});
</script>
<?
if(COption::GetOptionString("blog", "use_autosave", "Y") == "Y")
{
	$dynamicArea = new \Bitrix\Main\Page\FrameStatic("post-autosave");
	$dynamicArea->startDynamicArea();
	$as = new CAutoSave();
	$as->Init(false);
	$dynamicArea->finishDynamicArea();
}
	$arButtons = Array(
		Array(
			"NAME" => "save",
			"TEXT" => GetMessage("BLOG_BUTTON_SEND"),
			"CLICK" => "submitBlogPostForm();",
		),
	);
	if($arParams["MICROBLOG"] != "Y")
	{
		$arButtons[] = Array(
			"NAME" => "draft",
			"TEXT" => GetMessage("BLOG_BUTTON_DRAFT")
		);
	}
	else
	{
		$arButtons[] = Array(
			"NAME" => "cancel",
			"TEXT" => GetMessage("BLOG_BUTTON_CANCEL"),
			"CLICK" => "window.SBPETabs.getInstance().collapse()",
			"CLEAR_CANCEL" => "Y",
		);
	}

	?><div class="feed-buttons-block" id="feed-add-buttons-block<?=$arParams["FORM_ID"]?>" style="display:none;"><?
	$scriptFunc = array();
	foreach($arButtons as $val)
	{
		$onclick = $val["CLICK"];
		if(strlen($onclick) <= 0)
			$onclick = "submitBlogPostForm('".$val["NAME"]."'); ";
		$scriptFunc[$val["NAME"]] = $onclick;
		if($val["CLEAR_CANCEL"] == "Y")
		{
			?><a href="javascript:void(0)" id="blog-submit-button-<?=$val["NAME"]?>" class="feed-cancel-com"><?=$val["TEXT"]?></a><?
		}
		else
		{
			?><a href="javascript:void(0)" id="blog-submit-button-<?=$val["NAME"]?>" class="feed-add-button<?=" ".$val["ADIT_STYLES"]?>"><?=$val["TEXT"]?></a><?
		}
	}
	if (!empty($scriptFunc))
	{
?><script>BX.ready(function(){<?
		foreach ($scriptFunc as $id => $handler)
		{
?>BX.bind(BX("blog-submit-button-<?=$id?>"), "click", function(e){<?=$handler?>;return BX.PreventDefault(e);});<?
		}
?>});
</script><?
	}
	?></div>
	<input type="hidden" name="blog_upload_cid" id="upload-cid" value="">
</form><?
}
	?></div>
</div>
</div>
