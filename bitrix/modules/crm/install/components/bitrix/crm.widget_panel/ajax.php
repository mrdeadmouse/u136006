<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

global $DB, $APPLICATION;

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	echo CUtil::PhpToJSObject(array('ERROR' => 'Access denied.'));
	die();
}

CUtil::JSPostUnescape();

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	echo CUtil::PhpToJSObject(array('ERROR' => 'Invalid request. The "Action" parameter is not found.'));
	die();
}

if($action == 'PREPARE_DATA')
{
	$params = isset($_POST['PARAMS'])  ? $_POST['PARAMS'] : null;
	$GUID = isset($params['GUID'])  ? $params['GUID'] : '';
	$control = isset($params['CONTROL'])  ? $params['CONTROL'] : null;
	if(!is_array($control) || empty($control))
	{
		echo CUtil::PhpToJSObject(array('ERROR' => 'Invalid request. The "Control" parameter is not found.'));
		die();
	}

	$filterConfig = isset($params['FILTER'])  ? $params['FILTER'] : null;
	if(!is_array($filterConfig) || empty($filterConfig))
	{
		echo CUtil::PhpToJSObject(array('ERROR' => 'Invalid request. The "Filter" parameter is not found.'));
		die();
	}

	$filter = new Bitrix\Crm\Widget\Filter($filterConfig);
	if($filter->isEmpty())
	{
		$filter->setPeriodTypeID(Bitrix\Crm\Widget\FilterPeriodType::LAST_DAYS_30);
	}

	if(isset($control['filter']) && is_array($control['filter']))
	{
		$curFilter = new Bitrix\Crm\Widget\Filter($control['filter']);
		if($curFilter->isEmpty())
		{
			$curFilter = $filter;
		}
	}
	else
	{
		$curFilter = $filter;
	}

	$widget = Bitrix\Crm\Widget\WidgetFactory::create($control, $curFilter);
	echo CUtil::PhpToJSObject(
		array('RESULT' => array('GUID' => $GUID, 'DATA' => $widget->prepareData()))
	);
	die();
}