<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!CModule::IncludeModule("learning"))
	return;

// paths templates for correct indexing
$urls = array(
	array(WIZARD_SITE_DIR.'services/learning/course.php?COURSE_ID=#COURSE_ID#&INDEX=Y', 'C'),
	array(WIZARD_SITE_DIR.'services/learning/course.php?COURSE_ID=#COURSE_ID#&CHAPTER_ID=#CHAPTER_ID#', 'H'),
	array(WIZARD_SITE_DIR.'services/learning/course.php?COURSE_ID=#COURSE_ID#&LESSON_ID=#LESSON_ID#', 'L')
);

// If method not exists => new data model used
if ( ! method_exists('CCourse', 'SetPermission') )
	$urls[] = array(WIZARD_SITE_DIR.'services/learning/course.php?LESSON_PATH=#LESSON_PATH#', 'U');


$dbPath = CSitePath::GetList(Array(), Array("SITE_ID" => WIZARD_SITE_ID));
while($arPath = $dbPath -> Fetch())
{
	unset($GLOBALS["LEARNING_SITE_PATH"]["LEARNING_SITE_PATH1_CACHE_".$arPath["SITE_ID"]]);
	unset($GLOBALS["LEARNING_SITE_PATH"]["LEARNING_SITE_PATH_CACHE_".$arPath["ID"]]);
	$DB->Query("DELETE FROM b_learn_site_path WHERE ID = '".$arPath["ID"]."'", true);
}

foreach($urls as $url)
{
	CSitePath::Add(
		array(
			"SITE_ID" => WIZARD_SITE_ID,
			"PATH" => $url[0],
			"TYPE" => $url[1]
		)
	);
}

$arCourses = Array("new_employee", "portal");
$arCoursesSite = Array("new_employee_".WIZARD_SITE_ID, "portal_".WIZARD_SITE_ID);

if(!WIZARD_NEW_2011 && WIZARD_SITE_ID == 's1')
{
	$arCoursesSite = Array("new_employee", "portal");
}

foreach ($arCourses as $key => $courseCode)
{
	try
	{
		$dbResult = CCourse::GetList(Array(), Array("CODE" => $arCoursesSite[$key]));
		$pathToService = WIZARD_SERVICE_ABSOLUTE_PATH;
		if (!$arCourse = $dbResult->Fetch())
		{
			$pathToCourse = WIZARD_SERVICE_RELATIVE_PATH."/".LANGUAGE_ID."/".$courseCode."/";
			$package = new CCourseImport($pathToCourse, Array(WIZARD_SITE_ID));

			if (strlen($package->LAST_ERROR) > 0)
				return;

			$success = $package->ImportPackage();

			if ($success)
			{
				$dbResult = CCourse::GetList(Array(), Array("CODE" => $courseCode));
				$arCourse = $dbResult->Fetch();
				$obCCourse = new CCourse();
				$obCCourse->Update($arCourse["ID"], array("CODE" => $arCoursesSite[$key]));
			}
		}

		/*
		it's new rights model now, rights mudt be set for every lesson and/or for module at all
		if (isset($arCourse["ID"])){
			CCourse::SetPermission($arCourse["ID"], Array("2"=>"R"));

		}
		*/

		CopyDirFiles(
			$pathToService."/".LANGUAGE_ID."/images/".$courseCode."/",
			WIZARD_SITE_PATH,
			$rewrite = false,
			$recursive = true
		);
	}
	catch (LearnException $e)
	{
	}
}

if (!WIZARD_IS_RERUN || WIZARD_FIRST_INSTAL !== "Y")
{
	$obSite = CSite::GetByID(WIZARD_SITE_ID);
	if (!$arSite = $obSite->Fetch())
		return;

	$arTemplates = Array();
	$obTemplate = CSite::GetTemplateList(WIZARD_SITE_ID);
	while($arTemplate = $obTemplate->Fetch())
		$arTemplates[]= $arTemplate;

	$urlTemlates = WIZARD_SITE_DIR."services/learning/course";
	$arTemplates[]= Array("CONDITION" => "CSite::InDir('".$urlTemlates."')", "SORT" => 150, "TEMPLATE" => "learning");

	$obSite = new CSite();
	$obSite->Update(WIZARD_SITE_ID, Array("TEMPLATE" => $arTemplates, "NAME" => $arSite["NAME"]));

	$APPLICATION->SetGroupRight("learning", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
}
?>