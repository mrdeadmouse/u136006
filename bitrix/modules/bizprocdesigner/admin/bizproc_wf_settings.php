<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/include.php");
IncludeModuleLangFile(__FILE__);

CUtil::DecodeUriComponent($_POST);

foreach (array('arWorkflowParameters', 'arWorkflowParameters', 'arWorkflowConstants') as $field)
{
	if (isset($_POST[$field]) && is_array($_POST[$field]))
	{
		if (LANG_CHARSET != "UTF-8")
		{
			foreach ($_POST[$field] as $name => $param)
			{
				if (is_array($_POST[$field][$name]["Options"]))
				{
					$newarr = array();
					foreach ($_POST[$field][$name]["Options"] as $k => $v)
						$newarr[$GLOBALS["APPLICATION"]->ConvertCharset($k, "UTF-8", LANG_CHARSET)] = $v;
					$_POST[$field][$name]["Options"] = $newarr;
				}
			}
		}
	}
	else
		$_POST[$field] = array();
}


$arWorkflowParameters = $_POST['arWorkflowParameters'];
$arWorkflowVariables = $_POST['arWorkflowVariables'];
$arWorkflowConstants = $_POST['arWorkflowConstants'];

try
{
	$canWrite = CBPDocument::CanUserOperateDocumentType(
		CBPCanUserOperateOperation::CreateWorkflow,
		$GLOBALS["USER"]->GetID(),
		array(MODULE_ID, ENTITY, $_POST['document_type'])
	);
}
catch (Exception $e)
{
	$canWrite = false;
}

if (!$canWrite || !check_bitrix_sessid())
{
	ShowError(GetMessage("ACCESS_DENIED"));
	die();
}

if ($_POST["save"] == "Y")
{
	$perms = array();
	if (!isset($arErrors))
		$arErrors = array();
	if (is_array($_POST['perm']))
	{
		foreach ($_POST['perm'] as $t => $v)
			$perms[$t] = CBPHelper::UsersStringToArray($v, array(MODULE_ID, ENTITY, $_POST['document_type']), $arErrors);
	}

	echo CUtil::PhpToJSObject($perms, false);
	die();
}

$APPLICATION->ShowTitle(GetMessage("BIZPROC_WFS_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$runtime = CBPRuntime::GetRuntime();
$runtime->StartRuntime();

$documentService = $runtime->GetService("DocumentService");
echo $documentService->GetJSFunctionsForFields(array(MODULE_ID, ENTITY, $_POST['document_type']), "objFields");

$arAllowableOperations = $documentService->GetAllowableOperations(array(MODULE_ID, ENTITY, $_POST['document_type']));

if(!is_array($arWorkflowParameters))
	$arWorkflowParameters = array();

$arWorkflowParameterTypesTmp = $documentService->GetDocumentFieldTypes(array(MODULE_ID, ENTITY, $_POST['document_type']));
$arWorkflowParameterTypes = array();
foreach ($arWorkflowParameterTypesTmp as $key => $value)
	$arWorkflowParameterTypes[$key] = $value["Name"];

CBPDocument::AddShowParameterInit(MODULE_ID, "only_users", $_POST['document_type'], ENTITY);
?>
<script type="text/javascript">
BX.WindowManager.Get().SetTitle('<?= GetMessageJS("BIZPROC_WFS_TITLE") ?>');

var WFSAllData = {};
WFSAllData['P'] = <?=(is_array($arWorkflowParameters)?CUtil::PhpToJSObject($arWorkflowParameters):'{}')?>;
WFSAllData['V'] = <?=(is_array($arWorkflowVariables)?CUtil::PhpToJSObject($arWorkflowVariables):'{}')?>;
WFSAllData['C'] = <?=(is_array($arWorkflowConstants)?CUtil::PhpToJSObject($arWorkflowConstants):'{}')?>;

function WFSStart()
{
	var type, id;

	for (type in WFSAllData)
	{
		if (!WFSAllData.hasOwnProperty(type))
			continue;
		var typeData = WFSAllData[type];
		for (id in typeData)
		{
			if (!typeData.hasOwnProperty(id))
				continue;
			WFSParamAddParam(id, typeData[id], type);
		}
	}
	document.getElementById('WFStemplate_name').value = workflowTemplateName;
	document.getElementById('WFStemplate_description').value = workflowTemplateDescription;

	document.getElementById('WFStemplate_autostart1').checked = workflowTemplateAutostart & 1;
	document.getElementById('WFStemplate_autostart2').checked = workflowTemplateAutostart & 2;
}

function WFSFSave()
{
	var perm = 'save=Y&document_type=<?= urlencode($_POST['document_type']) ?>&<?= bitrix_sessid_get() ?>';
	<?foreach ($arAllowableOperations as $op_id => $op_name):?>
		perm += '&perm[<?= $op_id ?>]='+encodeURIComponent(document.getElementById('id_P<?= $op_id ?>').value);
	<?endforeach;?>

	BX.showWait();
	BX.ajax({
		'url': '/bitrix/admin/<?= MODULE_ID ?>_bizproc_wf_settings.php?lang=<?= LANGUAGE_ID ?>&entity=<?= ENTITY ?>',
		'method': 'POST',
		'data': perm,
		'dataType': 'json',
		'timeout': 10,
		'async': false,
		'start': true,
		'onsuccess': WFSSaveOK, 
		'onfailure': WFSSaveN
	});
}

function WFSSaveN(o)
{
	BX.closeWait();
	alert('<?=GetMessageJS("BP_WF_SAVEERR")?>');
}

function WFSSaveOK(permissions)
{
	BX.closeWait();

	arWorkflowParameters = {};
	var i, t = document.getElementById('WFSListP');
	for (i = 1; i < t.rows.length; i++)
		arWorkflowParameters[t.rows[i].paramId] = WFSAllData['P'][t.rows[i].paramId];

	arWorkflowConstants = {};
	var i, t = document.getElementById('WFSListC');
	for (i = 1; i < t.rows.length; i++)
		arWorkflowConstants[t.rows[i].paramId] = WFSAllData['C'][t.rows[i].paramId];

	arWorkflowVariables = WFSAllData['V'];
	workflowTemplateName = document.getElementById('WFStemplate_name').value;
	workflowTemplateDescription = document.getElementById('WFStemplate_description').value;
	workflowTemplateAutostart = ((document.getElementById('WFStemplate_autostart1').checked ? 1 : 0) | (document.getElementById('WFStemplate_autostart2').checked ? 2 : 0));

	rootActivity['Properties']['Permission'] = permissions;

	BX.WindowManager.Get().CloseDialog();
}

function WFSParamEditForm(b, Type)
{
	var f = document.getElementById('dparamform'+Type);
	var l = document.getElementById('dparamlist'+Type);
	document.getElementById('dpsavebutton').disabled = b;
	document.getElementById('dpcancelbutton').disabled = b;
	if (b)
	{
		l.style.display = 'none';
		f.style.display = 'block';
	}
	else
	{
		f.style.display = 'none';
		l.style.display = 'block';
	}
}

function WFSParamSetType(type, pvMode, value)
{
	BX.showWait();

	var f1 = document.getElementById("WFSFormType"+pvMode);
	if (f1)
	{
		for (var i = 0; i < f1.options.length; i++)
		{
			if (f1.options[i].value == type['Type'])
			{
				f1.selectedIndex = i;
				break;
			}
		}
	}

	if (typeof value == "undefined")
		value = "";

	if (objFields.arFieldTypes[type['Type']]['Complex'] == "Y")
	{
		objFields.GetFieldInputControl4Type(
			type,
			value,
			{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
			"WFSSwitchSubTypeControl" + pvMode,
			function(v, newPromt)
			{
				if (v == undefined)
				{
					document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";
					document.getElementById('WFSFormOptionsRow'+pvMode).style.display = 'none';
				}
				else
				{
					document.getElementById('WFSFormOptionsRow'+pvMode).style.display = '';
					document.getElementById('tdWFSFormOptions'+pvMode).innerHTML = v;
				}

				if (newPromt.length <= 0)
					newPromt = '<?= GetMessageJS("BIZPROC_WFS_PARAMLIST") ?>';
				document.getElementById('tdWFSFormOptionsPromt'+pvMode).innerHTML = newPromt + ":";

				objFields.GetFieldInputControl4Subtype(
					type,
					value,
					{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
					function(v1)
					{
						if (v1 == undefined)
							document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";
						else
							document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = v1;

						BX.closeWait();
					}
				);

			}
		);
	}
	else
	{
		document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";
		document.getElementById('WFSFormOptionsRow'+pvMode).style.display = 'none';

		objFields.GetFieldInputControl4Subtype(
			type,
			value,
			{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
			function(v)
			{
				if (v == undefined)
					document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";
				else
					document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = v;

				BX.closeWait();
			}
		);
	}
}

function WFSParamSetSubtype(type, pvMode, value)
{
	BX.showWait();

	if (typeof value == "undefined")
		value = "";

	objFields.GetFieldInputControl4Subtype(
		type,
		value,
		{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
		function(v)
		{
			if (v == undefined)
				document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";
			else
				document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = v;

			BX.closeWait();
		}
	);
}

function WFSSwitchSubTypeControl(newSubtype, pvMode)
{
	BX.showWait();
	document.getElementById('dpsavebuttonform' + pvMode).disabled = true;
	document.getElementById('dpcancelbuttonform' + pvMode).disabled = true;

	objFields.GetFieldInputValue(
		window.currentType,
		{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
		function(v)
		{
			window.currentType['Options'] = newSubtype;

			if (typeof v == "object")
				v = v[0];

			BX.closeWait();
			document.getElementById('dpsavebuttonform' + pvMode).disabled = false;
			document.getElementById('dpcancelbuttonform' + pvMode).disabled = false;

			WFSParamSetSubtype(window.currentType, pvMode, v);
		}
	);
}

function WFSSwitchTypeControl(newType, pvMode)
{
	BX.showWait();

	objFields.GetFieldInputValue(
		window.currentType,
		{'Field':'WFSFormDefault'+pvMode, 'Form':'bizprocform'},
		function(v)
		{
			window.currentType['Type'] = newType;

			if (typeof v == "object")
				v = v[0];

			BX.closeWait();

			WFSParamSetType(window.currentType, pvMode, v);
		}
	);
}

function WFSSwitchSubTypeControlP(newSubtype)
{
	WFSSwitchSubTypeControl(newSubtype, 'P');
}

function WFSSwitchSubTypeControlV(newSubtype)
{
	WFSSwitchSubTypeControl(newSubtype, 'V');
}

function WFSSwitchSubTypeControlC(newSubtype)
{
	WFSSwitchSubTypeControl(newSubtype, 'C');
}

function WFSParamDeleteParam(ob, Type)
{
	var id = ob.parentNode.parentNode.paramId;
	delete WFSAllData[Type][id];

	var i, t = document.getElementById('WFSList'+Type);
	for (i = 1; i < t.rows.length; i++)
	{
		if (t.rows[i].paramId == id)
		{
			t.deleteRow(i);
			return;
		}
	}
}

var lastEd = false;
var currentType = null;

function WFSParamEditParam(ob, pvMode)
{
	WFSParamEditForm(true, pvMode);

	window.lastEd = ob.parentNode.parentNode.paramId;

	var s = WFSAllData[pvMode][ob.parentNode.parentNode.paramId];

	window.currentType = {'Type' : s['Type'], 'Options' : s['Options'], 'Required' : s['Required'], 'Multiple' : s['Multiple']};

	document.getElementById("WFSFormId"+pvMode).value = lastEd;
	document.getElementById("WFSFormId"+pvMode).readOnly = true;

	document.getElementById("WFSFormName"+pvMode).value = s['Name'];
	document.getElementById("WFSFormDesc"+pvMode).value = s['Description'] || '';

	document.getElementById("WFSFormReq"+pvMode).checked = (s['Required'] == 1);
	document.getElementById("WFSFormMult"+pvMode).checked = (s['Multiple'] == 1);

	document.getElementById('tdWFSFormDefault'+pvMode).innerHTML = "";

	WFSParamSetType(
		window.currentType,
		pvMode,
		s['Default']
	);

	document.getElementById("WFSFormName"+pvMode).focus();
}

function WFSParamSaveForm(Type)
{
	if (document.getElementById("WFSFormName"+Type).value.replace(/^\s+|\s+$/g, '').length <= 0)
	{
		alert('<?=GetMessageJS("BIZPROC_WFS_PARAM_REQ")?>');
		document.getElementById("WFSFormName"+Type).focus();
		return;
	}

	if (document.getElementById("WFSFormId"+Type).value.replace(/^\s+|\s+$/g, '').length <= 0)
	{
		alert('<?=GetMessageJS("BIZPROC_WFS_PARAM_ID")?>');
		document.getElementById("WFSFormId"+Type).focus();
		return;
	}

	if (!document.getElementById("WFSFormId"+Type).value.match(/^[A-Za-z_][A-Za-z0-9_]*$/g))
	{
		alert('<?=GetMessageJS("BIZPROC_WFS_PARAM_ID1")?>');
		document.getElementById("WFSFormId"+Type).focus();
		return;
	}

	var WFSData = WFSAllData[Type];
	if (lastEd && typeof WFSData[lastEd] === 'undefined')
		lastEd = false;
	var N = lastEd;

	if (!lastEd)
	{
		var varId = document.getElementById("WFSFormId"+Type).value.replace(/^\s+|\s+$/g, '');
		if (typeof(WFSData[varId]) !== 'undefined')
		{
			alert('<?=GetMessageJS("BIZPROC_WFS_PARAM_ID_EXISTS")?>'.replace('#ID#', varId));
			document.getElementById("WFSFormId"+Type).focus();
			return;
		}
		lastEd = varId;
		WFSData[lastEd] = {};
	}
	BX.showWait();

	WFSData[lastEd]['Name'] = document.getElementById("WFSFormName"+Type).value.replace(/^\s+|\s+$/g, '');
	WFSData[lastEd]['Description'] = document.getElementById("WFSFormDesc"+Type).value;
	WFSData[lastEd]['Type'] = document.getElementById("WFSFormType"+Type).value;
	WFSData[lastEd]['Required'] = document.getElementById("WFSFormReq"+Type).checked ? 1 : 0;
	WFSData[lastEd]['Multiple'] = document.getElementById("WFSFormMult"+Type).checked ? 1 : 0;

	WFSData[lastEd]['Options'] = null;
	if (objFields.arFieldTypes[WFSData[lastEd]['Type']]['Complex'] == "Y")
		WFSData[lastEd]['Options'] = window.currentType['Options'];

	objFields.GetFieldInputValue(
		WFSData[lastEd],
		{'Field':'WFSFormDefault'+Type, 'Form':'bizprocform'},
		function(v){
			if (typeof v == "object")
			{
				WFSData[lastEd]['Default_printable'] = v[1];
				v = v[0];
			}
			else
			{
				WFSData[lastEd]['Default_printable'] = v;
			}

			WFSData[lastEd]['Default'] = v;

			if (N === false)
				WFSParamAddParam(lastEd, WFSData[lastEd], Type);
			else
				WFSParamFillParam(lastEd, WFSData[lastEd], Type);

			WFSParamEditForm(false, Type);
			BX.closeWait();
		}
	);
}

function WFSParamFillParam(id, p, pvMode)
{
	var i, t = document.getElementById('WFSList'+pvMode);
	for (i = 1; i < t.rows.length; i++)
	{
		if (t.rows[i].paramId == id)
		{
			var r = t.rows[i].cells;

			r[0].innerHTML = '<a href="javascript:void(0);" onclick="WFSParamEditParam(this, \''+pvMode+'\');">'+HTMLEncode(p['Name'])+"</a>";
			r[1].innerHTML = id;
			r[2].innerHTML = (objFields.arFieldTypes[p['Type']] ? objFields.arFieldTypes[p['Type']]['Name'] : p['Type'] );
			r[3].innerHTML = (p['Required']==1?'<?=GetMessageJS("BIZPROC_WFS_YES")?>':'<?=GetMessageJS("BIZPROC_WFS_NO")?>');
			r[4].innerHTML = (p['Multiple']==1?'<?=GetMessageJS("BIZPROC_WFS_YES")?>':'<?=GetMessageJS("BIZPROC_WFS_NO")?>');

			return true;
		}
	}
}

function WFSParamNewParam(pvMode)
{
	lastEd = false;
	WFSParamEditForm(true, pvMode);

	var i;
	var prefix = {P: 'Parameter', V: 'Variable', C: 'Constant'}[pvMode];
	for (i=1; i<10000; i++)
	{
		if (!WFSAllData[pvMode][prefix+i])
			break;
	}

	for (var t in objFields.arFieldTypes)
		break;

	window.currentType = {'Type' : t, 'Options' : null, 'Required' : 'N', 'Multiple' : 'N'};

	document.getElementById("WFSFormId"+pvMode).value = prefix+i;
	document.getElementById("WFSFormId"+pvMode).readOnly = false;

	document.getElementById("WFSFormType"+pvMode).selectedIndex = 0;
	WFSParamSetType(window.currentType, pvMode);

	document.getElementById("WFSFormName"+pvMode).value = '';
	document.getElementById("WFSFormDesc"+pvMode).value = '';

	document.getElementById("WFSFormReq"+pvMode).checked = false;
	document.getElementById("WFSFormMult"+pvMode).checked = false;

	document.getElementById("WFSFormName"+pvMode).focus();
}

function WFSParamAddParam(id, p, pvMode)
{
	var t = document.getElementById('WFSList'+pvMode);
	var r = t.insertRow(-1);
	r.paramId = id;
	var c = r.insertCell(-1);
	c = r.insertCell(-1);
	c = r.insertCell(-1);
	c = r.insertCell(-1);
	c.align="center";
	c = r.insertCell(-1);
	c.align="center";
	c = r.insertCell(-1);
	c.innerHTML = ((pvMode == "P") ? '<a href="javascript:void(0);" onclick="moveRowUp(this); return false;"><?= GetMessageJS("BP_WF_UP") ?></a> | <a href="javascript:void(0);" onclick="moveRowDown(this); return false;"><?= GetMessageJS("BP_WF_DOWN") ?></a> | ' : '') + '<a href="javascript:void(0);" onclick="WFSParamEditParam(this, \''+pvMode+'\');"><?=GetMessageJS("BIZPROC_WFS_CHANGE_PARAM")?></a> | <a href="javascript:void(0);" onclick="WFSParamDeleteParam(this, \''+pvMode+'\');"><?=GetMessageJS("BIZPROC_WFS_DEL_PARAM")?></a>';
	WFSParamFillParam(id, p, pvMode);
}

function moveRowUp(a)
{
	var row = a.parentNode.parentNode;
	if (row.previousSibling.previousSibling)
		row.parentNode.insertBefore(row, row.previousSibling);
}

function moveRowDown(a)
{
	var row = a.parentNode.parentNode;
	if (row.nextSibling)
	{
		if (row.nextSibling.nextSibling)
			row.parentNode.insertBefore(row, row.nextSibling.nextSibling);
		else
			row.parentNode.appendChild(row);
	}
}

setTimeout(WFSStart, 0);
</script>

<form id="bizprocform" name="bizprocform" method="post">
<?=bitrix_sessid_post()?>
<?
$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("BIZPROC_WFS_TAB_MAIN"), "ICON" => "group_edit", "TITLE" => GetMessage("BIZPROC_WFS_TAB_MAIN_TITLE")),
	array("DIV" => "edit2", "TAB" => GetMessage("BIZPROC_WFS_TAB_PARAM"), "ICON" => "group_edit", "TITLE" => GetMessage("BIZPROC_WFS_TAB_PARAM_TITLE")),
	array("DIV" => "edit3", "TAB" => GetMessage("BP_WF_TAB_VARS"), "ICON" => "group_edit", "TITLE" => GetMessage("BP_WF_TAB_VARS_TITLE")),
	array("DIV" => "edit5", "TAB" => GetMessage("BP_WF_TAB_CONSTANTS"), "ICON" => "group_edit", "TITLE" => GetMessage("BP_WF_TAB_CONSTANTS_TITLE")),
	array("DIV" => "edit4", "TAB" => GetMessage("BP_WF_TAB_PERM"), "ICON" => "group_edit", "TITLE" => GetMessage("BP_WF_TAB_PERM_TITLE")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->Begin();

$tabControl->BeginNextTab();
?>
<tr>
	<td><span style="color: #FF0000">*</span><?echo GetMessage("BIZPROC_WFS_PAR_NAME")?></td>
	<td><input type="text" id="WFStemplate_name" value="<?=htmlspecialcharsbx($_POST['workflowTemplateName'])?>" size="40"></td>
</tr>
<tr>
	<td valign="top"><?echo GetMessage("BIZPROC_WFS_PAR_DESC")?></td>
	<td><textarea cols="35" rows="5"  id="WFStemplate_description"><?=htmlspecialcharsbx($_POST['workflowTemplateDescription'])?></textarea></td>
</tr>
<tr>
	<td valign="top"><?echo GetMessage("BIZPROC_WFS_PAR_AUTO")?></td>
	<td>
		<input type="checkbox" id="WFStemplate_autostart1" value="Y"><label for="WFStemplate_autostart1"><?echo GetMessage("BIZPROC_WFS_PAR_AUTO_ADD")?></label><br>
		<input type="checkbox" id="WFStemplate_autostart2" value="Y"><label for="WFStemplate_autostart2"><?echo GetMessage("BIZPROC_WFS_PAR_AUTO_UPD")?></label>
	</td>
</tr>

<?
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2">
		<div id="dparamlistP">
			<table width="100%" class="internal" id="WFSListP">
				<tr class="heading">
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_NAME")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAMID")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_TYPE")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_MULT")?></td>
					<!--td><?echo GetMessage("BIZPROC_WFS_PARAM_DEF")?></td-->
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_ACT")?></td>
				</tr>
			</table>
			<br>
			<span style="padding: 10px;"><a href="javascript:void(0);" onclick="WFSParamNewParam('P')"><?echo GetMessage("BIZPROC_WFS_ADD_PARAM")?></a></span>
		</div>
		<div id="dparamformP" style="display: none">
			<table class="internal">
				<tr>
					<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAMID")?>:</td>
					<td><input type="text" size="20" id="WFSFormIdP" readonly=readonly></td>
				</tr>
				<tr>
					<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAM_NAME")?>:</td>
					<td><input type="text" size="30" id="WFSFormNameP"></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAMDESC")?>:</td>
					<td><textarea id="WFSFormDescP" rows="2" cols="30"></textarea></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_TYPE")?>:</td>
					<td>
						<select id="WFSFormTypeP" onchange="WFSSwitchTypeControl(this.value, 'P');">
							<?foreach ($arWorkflowParameterTypes as $k => $v):?>
								<option value="<?= $k ?>"><?= htmlspecialcharsbx($v) ?></option>
							<?endforeach;?>
						</select><br />
						<span id="WFSAdditionalTypeInfoP"></span>
					</td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?>:</td>
					<td><input type="checkbox" id="WFSFormReqP" value="Y"></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_MULT")?>:</td>
					<td><input type="checkbox" id="WFSFormMultP" value="Y"></td>
				</tr>
				<tr id="WFSFormOptionsRowP" style="display: none;">
					<td id="tdWFSFormOptionsPromtP"><?echo GetMessage("BIZPROC_WFS_PARAMLIST")?>:</td>
					<td id="tdWFSFormOptionsP"><textarea id="WFSFormOptionsP" rows="5" cols="30"></textarea></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAMDEF")?>:</td>
					<td id="tdWFSFormDefaultP">
						<input id="id_WFSFormDefaultP">
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="button" id="dpsavebuttonformP" value="OK" onclick="WFSParamSaveForm('P')"><input type="button" id="dpcancelbuttonformP" onclick="WFSParamEditForm(false, 'P')" value="<?echo GetMessage("BIZPROC_WFS_BUTTON_CANCEL")?>"></td>
				</tr>
			</table>
		</div>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
?>
<tr>
	<td colspan="2">
		<div id="dparamlistV">
			<table width="100%" class="internal" id="WFSListV">
				<tr class="heading">
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_NAME")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAMID")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_TYPE")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?></td>
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_MULT")?></td>
					<!--td><?echo GetMessage("BIZPROC_WFS_PARAM_DEF")?></td-->
					<td><?echo GetMessage("BIZPROC_WFS_PARAM_ACT")?></td>
				</tr>
			</table>
			<br>
			<span style="padding: 10px;"><a href="javascript:void(0);" onclick="WFSParamNewParam('V')"><?echo GetMessage("BP_WF_VAR_ADD")?></a></span>
		</div>
		<div id="dparamformV" style="display: none">
			<table class="internal">
				<tr>
					<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAMID")?>:</td>
					<td><input type="text" size="20" id="WFSFormIdV" readonly=readonly></td>
				</tr>
				<tr>
					<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAM_NAME")?>:</td>
					<td><input type="text" size="30" id="WFSFormNameV"></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAMDESC")?>:</td>
					<td><textarea id="WFSFormDescV" rows="2" cols="30"></textarea></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_TYPE")?>:</td>
					<td>
						<select id="WFSFormTypeV" onchange="WFSSwitchTypeControl(this.value, 'V');">
							<?foreach ($arWorkflowParameterTypes as $k => $v):?>
								<option value="<?= $k ?>"><?= htmlspecialcharsbx($v) ?></option>
							<?endforeach;?>
						</select><br />
						<span id="WFSAdditionalTypeInfoV"></span>
					</td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?>:</td>
					<td><input type="checkbox" id="WFSFormReqV" value="Y"></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAM_MULT")?>:</td>
					<td><input type="checkbox" id="WFSFormMultV" value="Y"></td>
				</tr>
				<tr id="WFSFormOptionsRowV" style="display: none;">
					<td id="tdWFSFormOptionsPromtV"><?echo GetMessage("BIZPROC_WFS_PARAMLIST")?>:</td>
					<td id="tdWFSFormOptionsV"><textarea id="WFSFormOptionsV" rows="5" cols="30"></textarea></td>
				</tr>
				<tr>
					<td><?=GetMessage("BIZPROC_WFS_PARAMDEF")?>:</td>
					<td id="tdWFSFormDefaultV">
						<input id="id_WFSFormDefaultV">
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="button" id="dpsavebuttonformV" value="OK" onclick="WFSParamSaveForm('V')"><input type="button" id="dpcancelbuttonformV" onclick="WFSParamEditForm(false, 'V')" value="<?echo GetMessage("BIZPROC_WFS_BUTTON_CANCEL")?>"></td>
				</tr>
			</table>
		</div>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td colspan="2">
			<div id="dparamlistC">
				<table width="100%" class="internal" id="WFSListC">
					<tr class="heading">
						<td><?echo GetMessage("BIZPROC_WFS_PARAM_NAME")?></td>
						<td><?echo GetMessage("BIZPROC_WFS_PARAMID")?></td>
						<td><?echo GetMessage("BIZPROC_WFS_PARAM_TYPE")?></td>
						<td><?echo GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?></td>
						<td><?echo GetMessage("BIZPROC_WFS_PARAM_MULT")?></td>
						<!--td><?echo GetMessage("BIZPROC_WFS_PARAM_DEF")?></td-->
						<td><?echo GetMessage("BIZPROC_WFS_PARAM_ACT")?></td>
					</tr>
				</table>
				<br>
				<span style="padding: 10px;"><a href="javascript:void(0);" onclick="WFSParamNewParam('C')"><?echo GetMessage("BP_WF_CONSTANT_ADD")?></a></span>
			</div>
			<div id="dparamformC" style="display: none">
				<table class="internal">
					<tr>
						<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAMID")?>:</td>
						<td><input type="text" size="20" id="WFSFormIdC" readonly=readonly></td>
					</tr>
					<tr>
						<td><span style="color: #FF0000">*</span><?=GetMessage("BIZPROC_WFS_PARAM_NAME")?>:</td>
						<td><input type="text" size="30" id="WFSFormNameC"></td>
					</tr>
					<tr>
						<td><?=GetMessage("BIZPROC_WFS_PARAMDESC")?>:</td>
						<td><textarea id="WFSFormDescC" rows="2" cols="30"></textarea></td>
					</tr>
					<tr>
						<td><?=GetMessage("BIZPROC_WFS_PARAM_TYPE")?>:</td>
						<td>
							<select id="WFSFormTypeC" onchange="WFSSwitchTypeControl(this.value, 'C');">
								<?foreach ($arWorkflowParameterTypes as $k => $v):?>
									<option value="<?= $k ?>"><?= htmlspecialcharsbx($v) ?></option>
								<?endforeach;?>
							</select><br />
							<span id="WFSAdditionalTypeInfoC"></span>
						</td>
					</tr>
					<tr>
						<td><?=GetMessage("BIZPROC_WFS_PARAM_REQUIRED")?>:</td>
						<td><input type="checkbox" id="WFSFormReqC" value="Y"></td>
					</tr>
					<tr>
						<td><?=GetMessage("BIZPROC_WFS_PARAM_MULT")?>:</td>
						<td><input type="checkbox" id="WFSFormMultC" value="Y"></td>
					</tr>
					<tr id="WFSFormOptionsRowC" style="display: none;">
						<td id="tdWFSFormOptionsPromtC"><?echo GetMessage("BIZPROC_WFS_PARAMLIST")?>:</td>
						<td id="tdWFSFormOptionsC"><textarea id="WFSFormOptionsC" rows="5" cols="30"></textarea></td>
					</tr>
					<tr>
						<td><?=GetMessage("BIZPROC_WFS_CONSTANTDEF")?>:</td>
						<td id="tdWFSFormDefaultC">
							<input id="id_WFSFormDefaultC">
						</td>
					</tr>
					<tr>
						<td colspan="2" align="center"><input type="button" id="dpsavebuttonformC" value="OK" onclick="WFSParamSaveForm('C')"><input type="button" id="dpcancelbuttonformC" onclick="WFSParamEditForm(false, 'C')" value="<?echo GetMessage("BIZPROC_WFS_BUTTON_CANCEL")?>"></td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	<?
$tabControl->BeginNextTab();
$permissions = $_POST['arWorkflowTemplate'][0]['Properties']['Permission'];
foreach($arAllowableOperations as $op_id=>$op_name):
	$parameterKeyExt = 'P'.$op_id;
?>
<tr>
	<td valign="top"><?=htmlspecialcharsbx($op_name)?>:</td>
	<td valign="top"><?
			$usersP = htmlspecialcharsbx(CBPHelper::UsersArrayToString(
						$permissions[$op_id],
						$_POST['arWorkflowTemplate'],
						array(MODULE_ID, ENTITY, $_POST['document_type'])
					));
	?>
	<textarea name="<?= $parameterKeyExt ?>" id="id_<?= $parameterKeyExt ?>" rows="4" cols="50"><?= $usersP ?></textarea>
	<input type="button" value="..." onclick="BPAShowSelector('id_<?= $parameterKeyExt ?>', 'user', 'all', {'arWorkflowParameters': WFSAllData['P'], 'arWorkflowVariables': WFSAllData['V'], 'arWorkflowConstants': WFSAllData['C']});" style="vertical-align: top; margin-left: 2px"/>
	</td>
</tr>
<?
endforeach;

$tabControl->EndTab();

$tabControl->Buttons(array("buttons"=>Array(
	Array("name"=>GetMessage("BIZPROC_WFS_BUTTON_SAVE"), "onclick"=>"WFSFSave();", "title"=>"", "id"=>"dpsavebutton"),
	Array("name"=>GetMessage("BIZPROC_WFS_BUTTON_CANCEL"), "onclick"=>"BX.WindowManager.Get().CloseDialog();", "title"=>"", "id"=>"dpcancelbutton")
	)));
$tabControl->End();

?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>