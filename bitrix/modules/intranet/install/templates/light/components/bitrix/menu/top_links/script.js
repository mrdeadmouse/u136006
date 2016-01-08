function ShowSiteSelectorPopup(bindElement, number)
{
	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			 ie7 = true;
		/*@end
	@*/
	var offsetLeft = 0;
	var offsetTop =  -17;
	if (ie7 || (document.documentMode && document.documentMode <= 7))
	{
		offsetLeft = -2;
		offsetTop = -19;
	}
	var popup = BX.PopupWindowManager.create("site-selector-popup-" + number, bindElement, {
		content : BX("site-selector-popup-" + number),
		autoHide: true,
		offsetLeft : offsetLeft,
		offsetTop : offsetTop,
		lightShadow: true,
		events : {
			onPopupShow : function()
			{

			}
		}
	});

	popup.show();
	BX.bind(popup.popupContainer, "mouseover", BX.proxy(function() {
		if (this.params._timeoutId)
		{
			clearTimeout(this.params._timeoutId);
			this.params._timeoutId = undefined;
		}

		this.show();
	}, popup));

	BX.bind(popup.popupContainer, "mouseout", BX.proxy(OnOutSiteSelectorPopup, popup));
}

function OnOutSiteSelectorPopup(event)
{
	if (!this.params._timeoutId)
		this.params._timeoutId = setTimeout(BX.proxy(function() { this.close()}, this), 300);
}