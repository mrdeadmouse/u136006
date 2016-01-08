BX.namespace("BX.Intranet");

BX.Intranet.Banner24 = (function() {

	var url = null;

	return {

		init : function(options)
		{
			options = options || {};
			if (BX.type.isNotEmptyString(options.url))
			{
				url = options.url;
			}
		},

		close : function(bannerId)
		{
			if (url !== null)
			{
				BX.ajax.get(url, { banner: bannerId, sessid: BX.bitrix_sessid() });
			}

			var banner = BX("sidebar-banner-" + bannerId);
			if (banner)
			{
				banner.style.minHeight = "auto";
				banner.style.overflow = "hidden";
				banner.style.border = "none";
				(new BX.easing({
					duration : 500,
					start : { height : banner.offsetHeight, opacity : 100 },
					finish : { height : 0, opacity: 0 },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						if (state.height >= 0)
						{
							banner.style.height = state.height + "px";
							banner.style.opacity = state.opacity/100;
						}

						if (state.height <= 17)
						{
							banner.style.marginBottom = state.height + "px";
						}
					},
					complete : function() {
						banner.style.display = "none";
					}
				})).animate();
			}
		}
	}
})();