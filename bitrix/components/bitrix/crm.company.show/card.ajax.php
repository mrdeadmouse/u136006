<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if (!$USER->IsAuthorized() || $CCrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	return ;

$arResult = array();
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iCompanyId = (int) $_GET['USER_ID'];
if ($iCompanyId > 0)
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
	$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
	$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');

	$obRes = CCrmCompany::GetList(array(), array('ID' => $iCompanyId));
	$arCompany = $obRes->Fetch();
	if ($arCompany == false)
		return ;
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $iCompanyId));
	while($ar = $res->Fetch())
		if (empty($arCompany[$ar['COMPLEX_ID']]))
			$arCompany[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arCompany['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $iCompanyId
		)
	);
	$arCompany['PATH_TO_COMPANY_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
		array(
			'company_id' => $iCompanyId
		)
	);

	$strCard = '
<div class="bx-user-info-data-cont-video  bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name ">
		<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'">'.htmlspecialcharsbx($arCompany['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arCompany['COMPANY_TYPE']))
	{
		$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_COMPANY_TYPE').'</span>:
		<span class="fields enumeration">'.$arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']].'</span>
		<br />';
	}
	if (!empty($arCompany['EMPLOYEES']))
	{
		$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_EMPLOYEES').'</span>:
		<span class="fields enumeration">'.$arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']].'</span>
		<br />';
	}
	$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arCompany['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />
	</div>
	<div class="bx-user-info-data-name bx-user-info-seporator">
		<nobr>'.GetMessage('CRM_SECTION_COMPANY_INFO').'</nobr>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arCompany['PHONE_WORK']))
	{
		$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>:
		'.$arCompany['PHONE_WORK'].'
		<br />';
	}
	if (!empty($arCompany['EMAIL_WORK']))
	{
		$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>:
		'.$arCompany['EMAIL_WORK'].'
		<br />';
	}
	if (!empty($arCompany['WEB_WORK']))
	{
		$strCard .= '
		<span class="field-name">'.GetMessage('CRM_COLUMN_WEB').'</span>:
		'.$arCompany['WEB_WORK'].'
		<br />';
	}
	$strCard .= '</div>
</div>';

	if (!empty($arCompany['LOGO']))
	{
		$imageFile = CFile::GetFileArray($arCompany['LOGO']);
		if ($imageFile !== false)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array('width' => 102, 'height' => 104),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$imageImg = CFile::ShowImage($arFileTmp['src'], 102, 104, "border='0'", '');
		}
		if (strlen($imageImg)>0)
			$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-user-info-data-photo">'.$imageImg.'</a>';
		else
			$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';
	}
	else
		$strPhoto = '<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon-show">
		<a href="'.$arCompany['PATH_TO_COMPANY_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arCompany['PATH_TO_COMPANY_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Card' => $strCard,
		'Photo' => $strPhoto,
		'Photsdfdo' => $arCompany
	);
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject(array('RESULT' => $arResult));
if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}
include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
die();

?>
