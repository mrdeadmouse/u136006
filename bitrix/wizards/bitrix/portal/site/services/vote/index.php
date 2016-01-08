<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
if (!CModule::IncludeModule("vote"))
	return;

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/install/index.php");
$vote = new vote();
$vote->InstallUserFields();
		
$arAnswerColors = Array(
	"blue" => "#81a8ab");
$answerColor = (array_key_exists(WIZARD_THEME_ID, $arAnswerColors) ? $arAnswerColors[WIZARD_THEME_ID] : "#969696");

if (!is_object($DB))
	global $DB;
global $CACHE_MANAGER;	

$CACHE_MANAGER->CleanDir("b_vote_channel");
$CACHE_MANAGER->Clean("b_vote_channel_2_site");

$symbolycName = 'COMPANY_' . WIZARD_SITE_ID;

if (!WIZARD_NEW_2011 && WIZARD_SITE_ID == 's1')
	$symbolycName = 'COMPANY';

$arChannelFields = array(
	"TIMESTAMP_X"		=> $DB->GetNowFunction(),
	"C_SORT"			=> "'1'",
	"FIRST_SITE_ID"		=> "'".WIZARD_SITE_ID."'",
	"ACTIVE"			=> "'Y'",
	"TITLE"				=> "'".$DB->ForSql(GetMessage('VOTE_CHANNEL_COMPANY'))."'",
	"SYMBOLIC_NAME"		=> "'".$symbolycName."'");

$rsVoteChan = CVoteChannel::GetList($by, $order, Array("SYMBOLIC_NAME" => $symbolycName, 'SYMBOLIC_NAME_EXACT_MATCH' => 'Y'), $is_filtered);
if (!$rsVoteChan->Fetch())
{
	$ID = (int)$DB->Insert("b_vote_channel", $arChannelFields);
	if ($ID < 1)
		return;
	
	$CACHE_MANAGER->CleanDir("b_vote_perm_".$ID);
	
	//site
	$DB->Query("DELETE FROM b_vote_channel_2_site WHERE CHANNEL_ID='".$ID."'", false);
	$DB->Query("INSERT INTO b_vote_channel_2_site (CHANNEL_ID, SITE_ID) VALUES ($ID, '".WIZARD_SITE_ID."')", false);
	
	//groups
	$DB->Query("DELETE FROM b_vote_channel_2_group WHERE CHANNEL_ID='$ID'", false);
	
	$rsGroups = CGroup::GetList($by, $order, array());
	while ($arGroup = $rsGroups->Fetch())
	{
		$arFieldsPerm = array(
			"CHANNEL_ID"	=> "'".intval($ID)."'",
			"GROUP_ID"		=> "'".intval($arGroup["ID"])."'",
			"PERMISSION"	=> "'2'"
		);
		$DB->Insert("b_vote_channel_2_group", $arFieldsPerm);
	}
	
	$arFieldsVote = array(
		"CHANNEL_ID"		=> "'".$ID."'",
		"C_SORT"			=> "'1'",
		"ACTIVE"			=> "'Y'",
		"TIMESTAMP_X"		=> $DB->GetNowFunction(),
		"DATE_START"		=> $DB->CharToDateFunction(GetTime(time(),"FULL")),
		"DATE_END"			=> $DB->CharToDateFunction(GetTime(time()+31567028,"FULL")),
		"TITLE"				=> "'".$DB->ForSql(GetMessage('VOTE_DO_YOU_LIKE_PORTAL_NAME'))."'",
		"DESCRIPTION"		=> "NULL",
		"DESCRIPTION_TYPE"	=> "'html'",
		"EVENT1"			=> "'vote'",
		"EVENT2"			=> "'NULL'",
		"EVENT3"			=> "NULL",
		"UNIQUE_TYPE"		=> "'6'",
		"KEEP_IP_SEC"		=> "'0'",
		"DELAY"				=> "'0'",
		"DELAY_TYPE"		=> "NULL",
		"TEMPLATE"			=> "'default.php'",
		"RESULT_TEMPLATE"	=> "'default.php'",
		"NOTIFY"			=> "'N'"
		);
	
	$VOTE_ID = $DB->Insert("b_vote", $arFieldsVote);
	
	$arFieldsQuestion = array(
		"TIMESTAMP_X"		=> $DB->GetNowFunction(),
		"C_SORT"			=> "'1'",
		"ACTIVE"			=> "'Y'",
		'QUESTION_TYPE'		=> "'text'",
		'DIAGRAM'			=> "'Y'",
		'DIAGRAM_TYPE'		=> "'histogram'",
		'VOTE_ID'			=> "'$VOTE_ID'",
		'QUESTION'			=> "'".$DB->ForSql(GetMessage('VOTE_DO_YOU_LIKE_PORTAL_NAME'))."'",
		'COUNTER'			=> "'0'",
	);
	
	$Q_ID = $DB->Insert("b_vote_question", $arFieldsQuestion);
	
	$arAnswers = array(
		array(
			'C_SORT' => "'100'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_DO_YOU_LIKE_PORTAL_ANSWER1'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
		array(
			'C_SORT' => "'200'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_DO_YOU_LIKE_PORTAL_ANSWER2'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
		array(
			'C_SORT' => "'300'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_DO_YOU_LIKE_PORTAL_ANSWER3'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
	);
	
	foreach ($arAnswers as $answer)
		$DB->Insert("b_vote_answer", $answer);
	
	$arFieldsVote = array(
		"CHANNEL_ID"		=> "'".$ID."'",
		"C_SORT"			=> "'1'",
		"ACTIVE"			=> "'Y'",
		"TIMESTAMP_X"		=> $DB->GetNowFunction(),
		"DATE_START"		=> $DB->CharToDateFunction(GetTime(time(),"FULL")),
		"DATE_END"			=> $DB->CharToDateFunction(GetTime(time()+31567028,"FULL")),
		"TITLE"				=> "'".$DB->ForSql(GetMessage('VOTE_ABOUT_LEARNING_NAME'))."'",
		"DESCRIPTION"		=> "NULL",
		"DESCRIPTION_TYPE"	=> "'html'",
		"EVENT1"			=> "'vote'",
		"EVENT2"			=> "'NULL'",
		"EVENT3"			=> "NULL",
		"UNIQUE_TYPE"		=> "'6'",
		"KEEP_IP_SEC"		=> "'0'",
		"DELAY"				=> "'0'",
		"DELAY_TYPE"		=> "NULL",
		"TEMPLATE"			=> "'default.php'",
		"RESULT_TEMPLATE"	=> "'default.php'",
		"NOTIFY"			=> "'N'"
		);
	
	$VOTE_ID = $DB->Insert("b_vote", $arFieldsVote);
	
	$arFieldsQuestion = array(
		"TIMESTAMP_X"		=> $DB->GetNowFunction(),
		"C_SORT"			=> "'1'",
		"ACTIVE"			=> "'Y'",
		'QUESTION_TYPE'		=> "'text'",
		'DIAGRAM'			=> "'Y'",
		'DIAGRAM_TYPE'		=> "'histogram'",
		'VOTE_ID'			=> "'$VOTE_ID'",
		'QUESTION'			=> "'".$DB->ForSql(GetMessage('VOTE_ABOUT_LEARNING_NAME'))."'",
		'COUNTER'			=> "'0'",
	);
	
	$Q_ID = $DB->Insert("b_vote_question", $arFieldsQuestion);
	
	$arAnswers = array(
		array(
			'C_SORT' => "'100'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_ABOUT_LEARNING_ANSWER1'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
		array(
			'C_SORT' => "'200'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_ABOUT_LEARNING_ANSWER2'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
		array(
			'C_SORT' => "'300'",
			'MESSAGE' => "'".$DB->ForSql(GetMessage('VOTE_ABOUT_LEARNING_ANSWER3'))."'",
			'FIELD_TYPE' => "'0'",
			'COLOR' => "'".$DB->ForSql($answerColor)."'",
			'QUESTION_ID' => "'$Q_ID'",
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"ACTIVE" => "'Y'",
			'FIELD_WIDTH' => "'0'",
			'FIELD_HEIGHT' => "'0'",
		),
	);
	
	foreach ($arAnswers as $answer)
		$DB->Insert("b_vote_answer", $answer);
	
	
	
	$APPLICATION->SetGroupRight("vote", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
	$APPLICATION->SetGroupRight("vote", WIZARD_PERSONNEL_DEPARTMENT_GROUP, "W");
}

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", Array("SYMBOLIC_NAME_VOTE" => $symbolycName));

?>
