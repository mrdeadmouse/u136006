<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("blog") || !CModule::IncludeModule("idea"))
	return;

$SocNetGroupID = false;
$IdeaBlogGroupName = "[".WIZARD_SITE_ID."] ".GetMessage("IDEA_DEMO_BLOG_GROUP_NAME");
$IdeaBlogUrl = "idea_".WIZARD_SITE_ID;

$db_blog_group = CBlogGroup::GetList(array("ID" => "ASC"), array("SITE_ID" => WIZARD_SITE_ID, "NAME" => $IdeaBlogGroupName));
if ($res_blog_group = $db_blog_group->Fetch())
{
	 $SocNetGroupID = $res_blog_group["ID"];
	/* if (WIZARD_INSTALL_DEMO_DATA)
	 {
		$db_blog = CBlog::GetList(array(), array("GROUP_ID" => $res_blog_group["ID"]), false, false, array("ID"));
		if ($res_blog = $db_blog->Fetch())
		{
			do 
			{
				CBlog::Delete($res_blog["ID"]); 
			} while ($res_blog = $db_blog->Fetch()); 
		}
		BXClearCache(True, "/".WIZARD_SITE_ID."/blog/");
		BXClearCache(True, "/".WIZARD_SITE_ID."/idea/");
	 } */
}

if (!$SocNetGroupID)
	$SocNetGroupID = CBlogGroup::Add(
            array(
				"SITE_ID" => WIZARD_SITE_ID, 
				"NAME" => $IdeaBlogGroupName
            )
        );
//Blog
$blogID = CBlog::Add(
        Array(
                "NAME" =>  GetMessage("IDEA_DEMO_BLOG_NAME"),
                "DESCRIPTION" => "",
                "GROUP_ID" => $SocNetGroupID,
                "ENABLE_IMG_VERIF" => 'Y',
                "EMAIL_NOTIFY" => 'Y',
                "ENABLE_RSS" => "Y",
                "ALLOW_HTML" => "Y",
                "URL" => $IdeaBlogUrl,
                "ACTIVE" => "Y",
                "=DATE_CREATE" => $DB->GetNowFunction(),
                "=DATE_UPDATE" => $DB->GetNowFunction(),
                "SOCNET_GROUP_ID" => 1,
                "PERMS_POST" => Array("1" => BLOG_PERMS_READ, "2" => BLOG_PERMS_WRITE), 
                "PERMS_COMMENT" => array("1" => BLOG_PERMS_WRITE , "2" => BLOG_PERMS_WRITE),
                "PATH" => '/services/idea/',
        )
);

//UF
$arUFIdByName = array();
$arStatusList = CIdeaManagment::getInstance()->GetStatusList();
foreach($arStatusList as $UF)
    $arUFIdByName[$UF["XML_ID"]] = $UF["ID"];

//Categories
$categoryID = array();
$categoryID[0][] = CBlogCategory::Add(Array("BLOG_ID" => $blogID, "NAME" => GetMessage("IDEA_BLOG_DEMO_CATEGORY_1")));
$categoryID[0][] = CBlogCategory::Add(Array("BLOG_ID" => $blogID, "NAME" => GetMessage("IDEA_BLOG_DEMO_CATEGORY_2")));
$categoryID[1][] = CBlogCategory::Add(Array("BLOG_ID" => $blogID, "NAME" => GetMessage("IDEA_BLOG_DEMO_CATEGORY_3")));

//Post messages
$arBlogPostFields = array();
$arBlogPostFields[] = Array(
        "TITLE" => GetMessage("IDEA_BLOG_DEMO_MESSAGE_TITLE_1"),
        "DETAIL_TEXT" => GetMessage("IDEA_BLOG_DEMO_MESSAGE_BODY_1"),
        "DETAIL_TEXT_TYPE" => "text",
        "BLOG_ID" => $blogID,
        "AUTHOR_ID" => 1,
        "=DATE_CREATE" => $DB->GetNowFunction(),
        "=DATE_PUBLISH" => $DB->GetNowFunction(),
        "PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
        "ENABLE_TRACKBACK" => 'N',
        "ENABLE_COMMENTS" => 'Y',
        "CATEGORY_ID" =>  implode(",", $categoryID[0]),
        "UF_CATEGORY_CODE" => ToUpper(GetMessage("IDEA_UF_CATEGORY_CODE_1")),
        "UF_STATUS" => $arUFIdByName["COMPLETED"],
        "PERMS_POST" => Array(1 => BLOG_PERMS_READ, 2 => BLOG_PERMS_READ),
        "PERMS_COMMENT" => Array(1 => BLOG_PERMS_WRITE, 2 => BLOG_PERMS_WRITE),
        "PATH" => '/services/idea/#post_id#/',
        "CODE" => "company_new_year_2012",
);
$arBlogPostFields[] = Array(
        "TITLE" => GetMessage("IDEA_BLOG_DEMO_MESSAGE_TITLE_2"),
        "DETAIL_TEXT" => GetMessage("IDEA_BLOG_DEMO_MESSAGE_BODY_2"),
        "DETAIL_TEXT_TYPE" => "text",
        "BLOG_ID" => $blogID,
        "AUTHOR_ID" => 1,
        "=DATE_CREATE" => $DB->GetNowFunction(),
        "=DATE_PUBLISH" => $DB->GetNowFunction(),
        "PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
        "ENABLE_TRACKBACK" => 'N',
        "ENABLE_COMMENTS" => 'Y',
        "CATEGORY_ID" =>  implode(",", $categoryID[1]),
        "UF_CATEGORY_CODE" => ToUpper(GetMessage("IDEA_UF_CATEGORY_CODE_2")),
        "UF_STATUS" => $arUFIdByName["NEW"],
        "PERMS_POST" => Array(1 => BLOG_PERMS_READ, 2 => BLOG_PERMS_READ),
        "PERMS_COMMENT" => Array(1 => BLOG_PERMS_WRITE, 2 => BLOG_PERMS_WRITE),
        "PATH" => '/services/idea/#post_id#/',
        "CODE" => "new_workers",
);

$arBlogPostId = array();
foreach($arBlogPostFields as $BlogPostFields)
    $arBlogPostId[] = CBlogPost::Add($BlogPostFields);

foreach($arBlogPostId as $key=>$BlogPostId)
{
    if(!is_array($categoryID[$key])) continue;
    
    foreach($categoryID[$key] as $v)
	CBlogPostCategory::Add(Array("BLOG_ID" => $blogID, "POST_ID" => $BlogPostId, "CATEGORY_ID" => $v));
}
//Post Comments
$arBlogCommentFields = array();
$arBlogCommentFields[] = Array(
        "TITLE" => '',
        "POST_TEXT" => GetMessage("IDEA_BLOG_DEMO_COMMENT_BODY_1"),
        "BLOG_ID" => $blogID,
        "POST_ID" => $arBlogPostId[0],
        "PARENT_ID" => 0,
        "AUTHOR_ID" => 1,
        "DATE_CREATE" => ConvertTimeStamp(false, "FULL"), 
        "AUTHOR_IP" => "192.168.0.108",
        "PATH" => "/services/idea/#post_id#/?commentId=#comment_id###comment_id#"
);
$arBlogCommentFields[] = Array(
        "TITLE" => '',
        "POST_TEXT" => GetMessage("IDEA_BLOG_DEMO_COMMENT_BODY_2"),
        "BLOG_ID" => $blogID,
        "POST_ID" => $arBlogPostId[0],
        "PARENT_ID" => 0,
        "AUTHOR_ID" => 1,
        "DATE_CREATE" => ConvertTimeStamp(false, "FULL"), 
        "AUTHOR_IP" => "192.168.0.108",
        "PATH" => "/services/idea/#post_id#/?commentId=#comment_id###comment_id#"
);

$arCommentId = array();
foreach($arBlogCommentFields as $BlogCommentFields)
    $arCommentId[] = CBlogComment::Add($BlogCommentFields);

if($arBlogPost = CBlogPost::GetList(array(), array("BLOG_ID" => $blogID, "ID" => $arBlogPostId[0]), false, false, array("ID", CIdeaManagment::UFAnswerIdField, CIdeaManagment::UFStatusField))->Fetch())
{
    //if Empty value make an array
    if(!is_array($arBlogPost[CIdeaManagment::UFAnswerIdField]))
        $arBlogPost[CIdeaManagment::UFAnswerIdField] = array();

    $arBlogPost[CIdeaManagment::UFAnswerIdField][] = $arCommentId[1];
    unset($arBlogPost["ID"]);
    CBlogPost::Update($arBlogPostId[0], $arBlogPost);
}
	
//Replace in file
CWizardUtil::ReplaceMacros(
    WIZARD_SITE_PATH."/services/idea/index.php", 
    Array(
        "IDEA_BLOG_CODE" => $IdeaBlogUrl,
        "IDEA_BIND_STATUS_DEFAULT" => $arUFIdByName["NEW"],
    )
);
?>