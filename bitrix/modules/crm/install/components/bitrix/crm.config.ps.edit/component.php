<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/*
 * PATH_TO_PS_LIST
 * PATH_TO_PS_EDIT
 * PS_ID
 * PS_ID_PAR_NAME
 */

$arParams['PATH_TO_PS_LIST'] = CrmCheckPath('PATH_TO_PS_LIST', $arParams['PATH_TO_PS_LIST'], '');
$arParams['PATH_TO_PS_EDIT'] = CrmCheckPath('PATH_TO_PS_EDIT', $arParams['PATH_TO_PS_EDIT'], '?ps_id=#ps_id#&edit');

$psID = isset($arParams['PS_ID']) ? intval($arParams['PS_ID']) : 0;

if($psID <= 0)
{
	$psIDParName = isset($arParams['PS_ID_PAR_NAME']) ? strval($arParams['PS_ID_PAR_NAME']) : '';

	if(strlen($psIDParName) == 0)
		$psIDParName = 'ps_id';

	$psID = isset($_REQUEST[$psIDParName]) ? intval($_REQUEST[$psIDParName]) : 0;
}

$arPaySys = array();
$actionID = 0;

if($psID > 0)
{
	if(!($arPaySys = CSalePaySystem::GetByID($psID)))
	{
		ShowError(GetMessage('CRM_PS_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}

	$dbPSAction = CSalePaySystemAction::GetList(
		array(),
		array("PAY_SYSTEM_ID" => $psID)
	);
	if ($arPSAction = $dbPSAction->Fetch())
	{
		$actionID = IntVal($arPSAction["ID"]);
		$arPaySys['ACTION'] = $arPSAction;
	}
}

$arResult['PS_ID'] = $psID;
$arResult['PAY_SYSTEM'] = $arPaySys;

$arResult['FORM_ID'] = 'CRM_PS_EDIT_FORM';
$arResult['GRID_ID'] = 'CRM_PS_EDIT_GRID';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_PS_LIST'],
	array()
);

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$psID = isset($_POST['ps_id']) ? intval($_POST['ps_id']) : 0;
		$arPersonTypeId = isset($_POST['PERSON_TYPE_ID']) ? intval($_POST['PERSON_TYPE_ID']) : 0;
		$arFields = array();
		$errorMessage = '';

		if( $psID <= 0 && isset($_POST['ID']))
			$psID = intval(trim($_POST['ID']));

		if(isset($_POST['NAME']))
			$arFields['NAME'] = trim($_POST['NAME']);

		if (strlen($arFields['NAME']) <= 0 || !isset($_POST['NAME']))
			$errorMessage .= GetMessage('CRM_PS_ERROR_NO_NAME').'<br>';

		$arFields['ACTIVE'] = (isset($_POST['ACTIVE']) && ($_POST['ACTIVE'] == "Y") ? "Y" : "N");
		$arFields['SORT'] = (isset($_POST['SORT']) && (IntVal($_POST['SORT']) > 0) ? IntVal($_POST['SORT']) : 100);

		if(isset($_POST['DESCRIPTION']))
			$arFields['DESCRIPTION'] = $_POST['DESCRIPTION'];

		if (strlen($errorMessage) <= 0)
		{
			if ($psID > 0)
			{
				if (!CSalePaySystem::Update($psID, $arFields))
				{
					if ($ex = $APPLICATION->GetException())
						$errorMessage .= $ex->GetString().".<br>";
					else
						$errorMessage .= GetMessage("CRM_PS_UPDATE_UNKNOWN_ERROR").".<br>";
				}
			}
			else
			{
				$psID = CSalePaySystem::Add($arFields);
				if ($psID <= 0)
				{
					if ($ex = $APPLICATION->GetException())
						$errorMessage .= $ex->GetString().".<br>";
					else
						$errorMessage .= GetMessage("CRM_PS_ADD_UNKNOWN_ERROR").".<br>";
				}
			}
		}

		/*************** Actions edit ***********/
		if (strlen($errorMessage) <= 0)
		{

			if (isset($_POST["ACTION_FILE"]) && strlen(trim($_POST["ACTION_FILE"])) > 0)
				$actionFile = CCrmPaySystem::getActionPath($_POST["ACTION_FILE"]);
			else
				$errorMessage .= GetMessage("CRM_PS_EMPTY_SCRIP").".<br>";

			if (strlen($actionFile) > 0)
			{
				$actionFile = str_replace("\\", "/", $actionFile);
				while (substr($actionFile, strlen($actionFile) - 1, 1) == "/")
					$actionFile = substr($actionFile, 0, strlen($actionFile) - 1);

				$pathToAction = $_SERVER["DOCUMENT_ROOT"].$actionFile;
				if (!file_exists($pathToAction))
					$errorMessage .= GetMessage("CRM_PS_NO_SCRIPT").".<br>";
			}

			if (strlen($errorMessage) <= 0)
			{
				$arActParams = array();

				if (isset($_POST['PS_ACTION_FIELDS_LIST']) && strlen($_POST['PS_ACTION_FIELDS_LIST']) > 0)
				{
					$arActFields = explode(",", $_POST['PS_ACTION_FIELDS_LIST']);

					$arPsActFields = CCrmPaySystem::getPSCorrespondence(
						CBXVirtualIo::GetInstance()->ExtractNameFromPath($pathToAction ? $pathToAction : 'bill')
					);
					$arPSActionParams = CSalePaySystemAction::UnSerializeParams($arPaySys['ACTION']['PARAMS']);

					foreach ($arActFields as $val)
					{
						$val = Trim($val);

						if (empty($arPsActFields[$val]))
							continue;

						$typeTmp = $_POST["TYPE_".$val];
						$valueTmp = $_POST["VALUE1_".$val];

						if (strlen($typeTmp) <= 0)
							$valueTmp = $_POST["VALUE2_".$val];

						if ($arPsActFields[$val]['TYPE'] == 'FILE' && $typeTmp != 'FILE')
							continue;

						if ($typeTmp == 'FILE')
						{
							$valueTmp = array();
							if (array_key_exists("VALUE1_".$val, $_FILES))
							{
								if ($_FILES["VALUE1_".$val]["error"] == 0)
								{
									$imageFileError = CFile::CheckImageFile($_FILES["VALUE1_".$val]);

									if (is_null($imageFileError))
										$valueTmp = $_FILES["VALUE1_".$val];
									else
										$errorMessage .= $imageFileError . ".<br>";
								}
							}

							if (trim($_POST[$val."_del"]) == 'Y')
							{
								if (intval($arPSActionParams[$val]['VALUE']) == 0)
									continue;

								$valueTmp['old_file'] = $arPSActionParams[$val]['VALUE'];
								$valueTmp['del'] = trim($_POST[$val."_del"]);
							}

							if (empty($valueTmp))
							{
								$typeTmp  = $arPSActionParams[$val]['TYPE'];
								$valueTmp = $arPSActionParams[$val]['VALUE'];
							}
						}

						$arActParams[$val] = array(
							"TYPE" => $typeTmp,
							"VALUE" => $valueTmp
						);

						if ($arActParams[$val]['TYPE'] == 'FILE' && is_array($arActParams[$val]['VALUE']))
						{
							$arActParams[$val]['VALUE']['MODULE_ID'] = 'sale';
							CFile::SaveForDB($arActParams[$val], 'VALUE', 'sale/paysystem/field');
						}
					}
				}

				//add logotip
				$arPicture = array();
				if (array_key_exists("LOGOTIP", $_FILES) && $_FILES["LOGOTIP"]["error"] == 0)
					$arPicture = $_FILES["LOGOTIP"];
				elseif ($actionID <= 0)
				{
					$logo = "";

					if (file_exists($_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.png"))
						$logo = $_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.png";
					elseif (file_exists($_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.jpg"))
						$logo = $_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.jpg";
					elseif (file_exists($_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.gif"))
						$logo = $_SERVER["DOCUMENT_ROOT"].$actionFile."/logo.gif";

					$arPicture = CFile::MakeFileArray($logo);
				}

				$arPicture["old_file"] = $arPaySys['ACTION']["LOGOTIP"];
				$arPicture["del"] = trim($_POST["LOGOTIP_del"]);

				$arActFields = array(
						"PAY_SYSTEM_ID" => $psID,
						"PERSON_TYPE_ID" => $arPersonTypeId,
						"NAME" => $arFields['NAME'],
						"ACTION_FILE" => $actionFile,
						"NEW_WINDOW" => (($_POST['NEW_WINDOW'] == "Y") ? "Y" : "N" ),
						"PARAMS" => CSalePaySystemAction::SerializeParams($arActParams),
						"HAVE_PREPAY" => "N",
						"HAVE_RESULT" => "N",
						"HAVE_ACTION" => "N",
						"HAVE_PAYMENT" => "N",
						"HAVE_RESULT_RECEIVE" => "N",
						"ENCODING" => trim($_POST['ENCODING']),
						"LOGOTIP" => $arPicture
					);

				$pathToAction = $_SERVER["DOCUMENT_ROOT"].$actionFile;
				$pathToAction = str_replace("\\", "/", $pathToAction);
				while (substr($pathToAction, strlen($pathToAction) - 1, 1) == "/")
					$pathToAction = substr($pathToAction, 0, strlen($pathToAction) - 1);

				if (file_exists($pathToAction))
				{
					if (is_dir($pathToAction))
					{
						if (file_exists($pathToAction."/pre_payment.php"))
							$arActFields["HAVE_PREPAY"] = "Y";
						if (file_exists($pathToAction."/result.php"))
							$arActFields["HAVE_RESULT"] = "Y";
						if (file_exists($pathToAction."/action.php"))
							$arActFields["HAVE_ACTION"] = "Y";
						if (file_exists($pathToAction."/payment.php"))
							$arActFields["HAVE_PAYMENT"] = "Y";
						if (file_exists($pathToAction."/result_rec.php"))
							$arActFields["HAVE_RESULT_RECEIVE"] = "Y";
					}
					else
					{
						$arActFields["HAVE_PAYMENT"] = "Y";
					}
				}

				if (strlen($errorMessage) <= 0)
				{
					if ($actionID > 0)
					{
						if (!CSalePaySystemAction::Update($actionID, $arActFields))
						{
							if ($ex = $APPLICATION->GetException())
								$errorMessage .= $ex->GetString().".<br>";
							else
								$errorMessage .= GetMessage("CRM_PS_ERROR_UPDATE").".<br>";
						}
					}
					else
					{
						if (!CSalePaySystemAction::Add($arActFields))
						{
							if ($ex = $APPLICATION->GetException())
								$errorMessage .= $ex->GetString().".<br>";
							else
								$errorMessage .= GetMessage("CRM_PS_ERROR_ADD").".<br>";
						}
					}
				}
			}
		}

		if (strlen($errorMessage) <= 0)
		{
			LocalRedirect(
				isset($_POST['apply'])
					? CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PS_EDIT'],
					array('ps_id' => $psID)
				)
					: CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PS_LIST'],
					array('ps_id' => $psID)
				)
			);
		}
		else
		{
			ShowError($errorMessage);
			$arPaySys = $arFields;
			$arPaySys['ACTION'] = $arActFields;
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$psID = isset($arParams['PS_ID']) ? intval($arParams['PS_ID']) : 0;

		if(!CSalePaySystem::Delete($psID))
			ShowError(GetMessage('CRM_PS_DELETE_UNKNOWN_ERROR'));

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PS_LIST'],
				array()
			)
		);
	}
}

$arResult['FIELDS'] = array();

if(strlen($arParams['PS_ID']) > 0)
{
	$arResult['FIELDS']['tab_props'][] = array(
		'id' => 'ID',
		'name' => 'ID',
		'value' => $psID,
		'type' =>  'label'
	);
}

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'NAME',
	'name' =>  GetMessage('CRM_PS_FIELD_NAME'),
	'value' => htmlspecialcharsbx($arPaySys['NAME']),
	'type' =>  'text'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'ACTIVE',
	'name' =>  GetMessage('CRM_PS_FIELD_ACTIVE'),
	'value' => $arPaySys['ACTIVE'],
	'type' =>  'checkbox'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'SORT',
	'name' =>  GetMessage('CRM_PS_FIELD_SORT'),
	'value' => $arPaySys['SORT'],
	'type' =>  'text'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'DESCRIPTION',
	'name' =>  GetMessage('CRM_PS_FIELD_DESCRIPTION'),
	'value' => $arPaySys['DESCRIPTION'],
	'type' =>  'textarea'
);

$ptList = CCrmPaySystem::getPersonTypesList();
$ptID = $arPaySys['ACTION']['PERSON_TYPE_ID'] ? $arPaySys['ACTION']['PERSON_TYPE_ID'] : key($ptList);
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'PERSON_TYPE_ID',
	'name' => GetMessage('CRM_PS_FIELD_PERSON_TYPE_ID'),
	'type' => 'list',
	'items' => $ptList,
	'value' => $ptID
);

/*
$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'NEW_WINDOW',
	'name' =>  GetMessage('CRM_PS_FIELD_NEW_WINDOW'),
	'value' => $arPaySys['ACTION']['NEW_WINDOW'],
	'type' =>  'checkbox'
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'ENCODING',
	'name' => GetMessage('CRM_PS_FIELD_ENCODING'),
	'type' => 'list',
	'items' => array(
					"" => "",
					"windows-1251" => "windows-1251",
					"utf-8" => "utf-8",
					"iso-8859-1" => "iso-8859-1"
				),
	'value' => $arPaySys['ACTION']['ENCODING']
);

$logoHtml = '<div><input type="file" name="LOGOTIP"></div>';

if($arPaySys['ACTION']['LOGOTIP'] > 0)
{
	$logoHtml .= '<br>';
	$arLogotip = CFile::GetFileArray($arPaySys['ACTION']['LOGOTIP']);
	$logoHtml .= CFile::ShowImage($arLogotip, 150, 150, "border=0", "", false);
	$logoHtml .='	<div style="margin-top:10px;">
					<input type="checkbox" name="LOGOTIP_del" value="Y" id="LOGOTIP_del" >
					<label for="LOGOTIP_del">'.GetMessage("CRM_PS_LOGOTIP_DEL").'</label>
					</div>';
}

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'LOGOTIP',
	'name' => GetMessage('CRM_PS_FIELD_LOGOTIP'),
	'type' => 'custom',
	'value' => $logoHtml
);
*/

$io = CBXVirtualIo::GetInstance();
$arPaySys['ACTION']['ACTION_FILE'] = $io -> ExtractNameFromPath($arPaySys['ACTION']['ACTION_FILE'] ? $arPaySys['ACTION']['ACTION_FILE'] : 'bill');

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'ACTION_FILE',
	'name' => GetMessage("CRM_PS_FIELD_ACTION_FILE"),
	'type' => 'list',
	'items' => CCrmPaySystem::getActionsList(),
	'value' => $arPaySys['ACTION']['ACTION_FILE']
);

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'DETAILS',
	'name' => GetMessage("CRM_PS_ACT_PROPERTIES"),
	'type' => 'section'
);

$arResult['ACTION_FILE'] = $arPaySys['ACTION']['ACTION_FILE'];

$arResult['PS_ACT_FIELDS'] = CCrmPaySystem::getPSCorrespondence($arPaySys['ACTION']['ACTION_FILE']);
$arResult['ACTION_FIELDS_LIST'] =  implode(',', array_keys($arResult['PS_ACT_FIELDS']));
$arResult['SIMPLE_MODE'] = CCrmPaySystem::isFormSimple();

$arResult['USER_FIELDS'] = array();
$quoteUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmQuote::$sUFEntityID, 0, LANGUAGE_ID);
foreach($quoteUserFields as $quoteUserFieldName => &$quoteUserField)
{
	$arResult['USER_FIELDS']['quote'][$quoteUserFieldName] = $quoteUserField['EDIT_FORM_LABEL'];
}
unset($quoteUserField);

$arPaySys['ACTION']['PARAMS'] = unserialize($arPaySys['ACTION']['PARAMS']);

foreach ($arResult['PS_ACT_FIELDS'] as $idCorr => $arCorr)
{
	if ($arCorr['TYPE'] == 'SELECT')
		$arCorr['OPTIONS'] = $arCorr['VALUE'];

	if(isset($arPaySys['ACTION']['PARAMS'][$idCorr])
		&& isset($arPaySys['ACTION']['PARAMS'][$idCorr]['TYPE'])
		&& isset($arPaySys['ACTION']['PARAMS'][$idCorr]['VALUE'])
		)
	{
		if ($arCorr['TYPE'] == 'FILE')
		{
			$arCorr['VALUE'] = CFile::ShowImage(
				$arPaySys['ACTION']['PARAMS'][$idCorr]['VALUE'],
				150, 150,
				'id="' . $idCorr . '_preview_img"'
			);
		}
		elseif ($arCorr['TYPE'] == 'SELECT')
		{
			$arCorr['VALUE']   = $arPaySys['ACTION']['PARAMS'][$idCorr]['VALUE'];
		}
		else
		{
			$arCorr['TYPE'] = $arPaySys['ACTION']['PARAMS'][$idCorr]['TYPE'];
			$arCorr['VALUE'] = $arPaySys['ACTION']['PARAMS'][$idCorr]['VALUE'];
		}
	}
	$res  = ' ' . CCrmPaySystem::getActionSelector($idCorr, $arCorr);
	$res .= ' ' . CCrmPaySystem::getActionValueSelector($idCorr, $arCorr, $ptID, $arResult['ACTION_FILE'], $arResult['USER_FIELDS']);

	$arResult['FIELDS']['tab_props'][] = array(
		'id' => $idCorr,
		'name' => $arCorr['NAME'],
		'title' => $arCorr['DESCR'],
		'type' => 'custom',
		'value' => $res
	);
}

$arResult['FIELDS']['tab_props'][] = array(
	'id' => 'SIMPLE_MODE',
	'colspan' => true,
	'type' => 'custom',
	'value' => "<a onclick='BX.crmPaySys.switchMode();'".
				" class='crm-ps-mode-switcher'".
				" href='javascript:void(0);'".
				" id='MODE_SWITCHER'>".
				($arResult['SIMPLE_MODE'] ? GetMessage("CRM_PS_SHOW_FIELDS") : GetMessage("CRM_PS_HIDE_FIELDS")).
				"</a>"
);
$this->IncludeComponentTemplate();
?>