<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if (!$USER->IsAuthorized() || $CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	return ;

$arResult = array();
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iDealId = (int) $_GET['USER_ID'];
if ($iDealId > 0)
{

	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
	$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
	$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
	$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');

	$arResult['STAGE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STAGE');

	$obRes = CCrmDeal::GetList(array(), array('ID' => $iDealId));
	$arDeal = $obRes->Fetch();
	if ($arDeal == false)
		return ;
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'DEAL', 'ELEMENT_ID' => $iDealId));
	while($ar = $res->Fetch())
		if (empty($arDeal[$ar['COMPLEX_ID']]))
			$arDeal[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arDeal['PATH_TO_DEAL_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
		array(
			'deal_id' => $iDealId
		)
	);
	$arDeal['PATH_TO_DEAL_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
		array(
			'deal_id' => $iDealId
		)
	);
	$arDeal['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
		array(
			'contact_id' => $arDeal['CONTACT_ID']
		)
	);
	$arDeal['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $arDeal['COMPANY_ID']
		)
	);

	$arDeal['CONTACT_NAME'] = CUser::FormatName(
		\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
		array(
			'NAME' => $arDeal['NAME'],
			'LAST_NAME' => $arDeal['LAST_NAME'],
			'SECOND_NAME' => $arDeal['SECOND_NAME']
		),
		true, false
	);

	$strCard = '
<div class="bx-user-info-data-cont-video bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name">
		<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'">'.htmlspecialcharsbx($arDeal['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arDeal['STAGE_ID']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STAGE_ID').'</span>:
		<span class="fields enumeration">'.$arResult['STAGE_LIST'][$arDeal['STAGE_ID']].'</span>
		<br />';
	}

	$arProductRows = CCrmDeal::LoadProductRows($arDeal['ID']);
	if(count($arProductRows) > 0)
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>:<span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span><br />';
	}

	if (!empty($arDeal['OPPORTUNITY']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_OPPORTUNITY').'</span>:
		<span class="fields enumeration"><nobr>'.number_format($arDeal['OPPORTUNITY'], 2, ',', ' ').' '.htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID'])).'</nobr></span>
		<br />';
	}
	if (!empty($arDeal['PROBABILITY']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PROBABILITY').'</span>:
		<span class="fields enumeration">'.intval($arDeal['PROBABILITY']).'%</span>
		<br />';
	}
	$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arDeal['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />';
	if (!empty($arDeal['COMPANY_TITLE']))
	{
		$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_COMPANY_TITLE')).'</span>:
		<a href="'.$arDeal['PATH_TO_COMPANY_SHOW'].'">'.$arDeal['COMPANY_TITLE'].'</a>
		<br />';
	}
	if (!empty($arDeal['CONTACT_FULL_NAME']))
	{
		$strCard .= '<span class="field-name">'.htmlspecialcharsbx(GetMessage('CRM_COLUMN_CONTACT_FULL_NAME')).'</span>:
		<a href="'.$arDeal['PATH_TO_CONTACT_SHOW'].'">'.$arDeal['CONTACT_FULL_NAME'].'</a>
		<br />';
	}
	$strCard .= '</div>
</div>';
	$strPhoto = '<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon-show">
		<a href="'.$arDeal['PATH_TO_DEAL_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arDeal['PATH_TO_DEAL_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Card' => $strCard,
		'Photo' => $strPhoto
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
