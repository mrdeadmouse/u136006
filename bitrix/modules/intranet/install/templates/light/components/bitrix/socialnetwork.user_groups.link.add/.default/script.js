function onPopupGroupChanged(group) {

}

function onPopupGroupAdded(group) {

}

function onPopupGroupDeleted(groupId) {

}

window.subjectFilterMenuPopup = false;

function ShowSubjectMenuPopup(bindElement)
{
	if (subjectFilterMenuPopup)
		BX.PopupMenu.show('subject', bindElement, subjectFilterMenuPopup, {});

	return false;
} 

function __onSubjectMenuItemClick()
{
	return false;
}

var sonetUGXmlHttpGet = new XMLHttpRequest();

function ShowTagsPopup(bindElement)
{
	var popup = BX.PopupWindowManager.create(
		'bx_user_groups_tags_popup', 
		bindElement,
		{
			closeIcon : true,
			offsetTop: 2,
			autoHide: true
		}
	);

	var params = BX.message('sonetUGAjaxPath') + "?" + BX.message('sonetUGSessid')
		+ "&mode=tags"
		+ "&fmax=" + BX.util.urlencode(BX.message('sonetUGFontMax'))
		+ "&fmin=" + BX.util.urlencode(BX.message('sonetUGFontMin'))
		+ "&cnew=" + BX.util.urlencode(BX.message('sonetUGColorNew'))
		+ "&cold=" + BX.util.urlencode(BX.message('sonetUGColorOld'))
		+ "&pgs=" + BX.util.urlencode(BX.message('sonetUGPathToGroupSearch'))
		+ "&site=" + BX.util.urlencode(BX.message('sonetUGSiteId'));

	sonetUGXmlHttpGet.open(
		"get",
		params
	);
	sonetUGXmlHttpGet.send(null);

	sonetUGXmlHttpGet.onreadystatechange = function()
	{
		if (sonetUGXmlHttpGet.readyState == 4 && sonetUGXmlHttpGet.status == 200)
		{
			var content = sonetUGXmlHttpGet.responseText;
			popup.setContent(content);
			popup.show();
		}
	}
}
