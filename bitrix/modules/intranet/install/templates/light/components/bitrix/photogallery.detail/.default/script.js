function EditPhoto(url)
{
	var oEditDialog = new BX.CDialog({
		title : BXPH_MESS.EditPhotoTitle,
		content_url: url + (url.indexOf('?') !== -1 ? "&" : "?") + "AJAX_CALL=Y",
		buttons: [BX.CDialog.btnSave, BX.CDialog.btnCancel],
		width: 600
	});
	oEditDialog.Show();

	BX.addCustomEvent(oEditDialog, "onWindowRegister", function(){
		oEditDialog.adjustSizeEx();
		BX.focus(BX('bxph_title'));
	});

	oEditDialog.ClearButtons();
	oEditDialog.SetButtons([
		new BX.CWindowButton(
		{
			title: BX.message('JS_CORE_WINDOW_SAVE'),
			id: 'savebtn',
			action: function()
			{
				CheckForm(document.forms['form_photo']);
			}
		}),
		oEditDialog.btnCancel
	]);

	window.oPhotoEditDialog = oEditDialog;
}

function CheckForm(form)
{
	if (typeof form != "object")
		return false;

	oData = {"AJAX_CALL" : "Y"};
	for (var ii = 0; ii < form.elements.length; ii++)
	{
		if (form.elements[ii] && form.elements[ii].name)
		{
			if (form.elements[ii].type && form.elements[ii].type == "checkbox")
			{
				if (form.elements[ii].checked == true)
					oData[form.elements[ii].name] = form.elements[ii].value;
			}
			else
			{
				oData[form.elements[ii].name] = form.elements[ii].value;
			}
		}
	}

	BX.showWait('photo_window_edit');
	window.oPhotoEditDialogError = false;

	BX.ajax.post(
		form.action,
		oData,
		function(data)
		{
			setTimeout(function(){
				BX.closeWait('photo_window_edit');
				result = {};

				if (window.oPhotoEditDialogError !== false)
				{
					var errorTr = BX("bxph_error_row");
					errorTr.style.display = "";
					errorTr.cells[0].innerHTML = window.oPhotoEditDialogError;
					window.oPhotoEditDialog.adjustSizeEx();
				}
				else
				{
					try
					{
						eval("result = " + data + ";");

						if (result['url'] && result['url'].length > 0)
							BX.reload(result['url']);
						else
						{
							if (BX("photo_title"))
								BX("photo_title").innerHTML = result['TITLE'];
							if (BX("photo_date"))
								BX("photo_date").innerHTML = result['DATE'];
							if (BX("photo_tags"))
							{
								if (!result['TAGS'] || result['TAGS'].length <= 0)
								{
									BX("photo_tags").innerHTML = '';
									BX("photo_tags").parentNode.style.display = 'none';
								}
								else
								{
									BX("photo_tags").innerHTML = result['TAGS'];
									BX("photo_tags").parentNode.style.display = 'block';
								}
							}
							if (BX("photo_description"))
								BX("photo_description").innerHTML = result['DESCRIPTION'];
						}
						window.oPhotoEditDialog.Close();
					}
					catch(e)
					{
						var errorTr = BX("bxph_error_row");
						errorTr.style.display = "";
						errorTr.cells[0].innerHTML = BXPH_MESS.UnknownError;
						window.oPhotoEditDialog.adjustSizeEx();
					}
				}
			}, 200);
		});
}

function ShowOriginal(src, title)
{
	var SrcWidth = screen.availWidth;
	var SrcHeight = screen.availHeight;
	var sizer = false;
	var text = '';
	if (!title)
		title = "";
	if (document.all)
	{
		 sizer = window.open("","","height=SrcHeight,width=SrcWidth,top=0,left=0,scrollbars=yes,fullscreen=yes");
	}
	else
	{
		sizer = window.open('',src,'width=SrcWidth,height=SrcHeight,menubar=no,status=no,location=no,scrollbars=yes,fullscreen=yes,directories=no,resizable=yes');
	}
	text += '<html><head>';
	text += '\n<script language="JavaScript" type="text/javascript">';
	text += '\nfunction SetBackGround(div)';
	text += '\n{';
	text += '\n		if (!div){return false;}';
	text += '\n		document.body.style.backgroundColor = div.style.backgroundColor;';
	text += '\n}';
	text += '\n</script>';
	text += '\n</head>';
	text += '\n<title>';
	text += ('\n' + title);
	text += '\n</title>';
	text += '\n<body bgcolor="#999999">';
	text += '\n';
	text += '\n<table width="100%" height="96%" border=0 cellpadding=0 cellspacing=0>';
	text += '\n<tr><td align=right>';
	text += '\n<table align=center cellpadding=0 cellspacing=2 border=0>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#FFFFFF;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#E5E5E5;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#CCCCCC;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#B3B3B3;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#999999;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#808080;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#666666;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#4D4D4D;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#333333;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#1A1A1A;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px; background-color:#000000;" onmouseover="SetBackGround(this);"></div></td></tr>';
	text += '\n<tr><td><div style="width:18px; height:18px;"></div></td>';
	text += '\n</table></td>';
	text += '\n<td align=center><img alt="" border=0 src="' + src + '" onClick="window.close();" style="cursor:pointer; cursor:hand;" /></td></tr>';
	text += '\n</table></body></html>';
	sizer.document.write(text);

	return true;
}
bPhotoUtilsLoad = true;