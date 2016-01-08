if(typeof(BX.CrmWidget) === "undefined")
{
	BX.CrmWidget = function()
	{
		this._id = "";
		this._settings = null;
		this._typeName = "";
		this._prefix = "";
		this._heightInPixel = 0;
		this._widthInPercent = 0;
		this._layout = "";
		this._data = {};
		this._container = null;
		this._wrapper = null;
		this._headerWrapper = null;
		this._contentWrapper = null;
		this._settingButton = null;
		this._editButton = null;
		this._hasLayout = false;
		this._isChartsReady = false;
	};

	BX.CrmWidget.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._typeName = this.getSetting("typeName", "");
			if(!this._typeName)
			{
				throw "CrmWidget: The type name is not found.";
			}

			this._data = this.getSetting("data", {});
			this._prefix = this.getSetting("prefix", "");

			var containerId = this.getSetting("containerId", "");
			this._container = BX(containerId !== "" ? containerId : (this._prefix + "_" + "container"));
			if(!this._container)
			{
				throw "CrmWidget: Could not find container.";
			}

			this._heightInPixel = parseInt(this.getSetting("heightInPixel", 0));
			if(this._heightInPixel <= 0)
			{
				this._heightInPixel = 360;
			}

			this._widthInPercent = parseInt(this.getSetting("widthInPercent", 0));
			if(this._widthInPercent <= 0)
			{
				this._widthInPercent = 100;
			}

			this._layout = this.getSetting("layout", "");
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getTitle: function()
		{
			if(this._typeName === "number" && this._data.length > 0 && BX.type.isNotEmptyString(this._data[0]["title"]))
			{
				return this._data[0]["title"];
			}

			return this.getSetting("title", this._id);
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidget.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		prepareGraphSettings: function(config)
		{
			var result =
				{
					"title": config["title"],
					"valueField": config["selectField"],
					"balloonText": "[[title]]: [[value]]"
				};

			var displayParams = typeof(config["display"]) !== "undefined" ? config["display"] : {};
			var graphParams = typeof(displayParams["graph"]) !== "undefined" ? displayParams["graph"] : {};

			var graphType = this._typeName;
			if(BX.type.isNotEmptyString(graphParams["type"]))
			{
				graphType = config["display"]["type"];
			}

			if(BX.type.isNotEmptyString(graphParams["clustered"]))
			{
				result["clustered"] = graphParams["clustered"] === 'Y';
			}

			if(BX.type.isNotEmptyString(graphParams["lineColor"]))
			{
				result["lineColor"] = graphParams["lineColor"];
			}

			if(graphType === "bar")
			{
				result["type"] = "column";
				result["fillAlphas"] = 1;
			}
			else
			{
				result["type"] = "smoothedLine";
				result["lineThickness"] = 2;
				result["bullet"] = "round";
				result["bulletSize"] = 7;
				result["bulletBorderAlpha"] = 1;
			}

			return result;
		},
		renderHeader: function()
		{
			this._headerWrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });
			this._wrapper.appendChild(this._headerWrapper);

			this._settingButton = BX.create("SPAN",  { attrs: { className: "crm-widget-settings" } });
			this._editButton = BX.create("SPAN",  { attrs: { className: "crm-widget-title-edit" } });

			this._headerWrapper.appendChild(this._settingButton);
			this._headerWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-title-container" },
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-widget-title-inner" },
									children:
									[
										BX.create("SPAN",
											{ attrs: { className: "crm-widget-title" }, text: this.getTitle() }
										),
										this._editButton
									]
								}
							)
						]
					}
				)
			);
		},
		renderContent: function()
		{
			this._contentWrapper = BX.create("DIV",
				{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
			);
			this._wrapper.appendChild(this._contentWrapper);

			if(this._typeName === "funnel")
			{
				this.renderFunnel();
			}
			else if(this._typeName === "graph" || this._typeName === "bar")
			{
				this.renderGraph();
			}
			else if(this._typeName === "number")
			{
				if(this._layout === "tiled")
				{
					this.renderTiledNumber();
				}
				else
				{
					this.renderNumber();
				}
			}
			else if(this._typeName === "rating")
			{
				this.renderRating();
			}
		},
		renderFunnel: function()
		{
			if(!this._isChartsReady)
			{
				AmCharts.ready(BX.delegate(this.renderFunnel, this));
				this._isChartsReady = true;
				return;
			}

			this._contentWrapper.style.height = this._heightInPixel + "px";

			AmCharts.makeChart(this._contentWrapper.id,
				{
					"type": "funnel",
					"theme": "none",
					"titleField":  this.getSetting("titleField", "title"),
					"valueField": this.getSetting("valueField", "value"),
					"dataProvider": this._data,
					"labelPosition": "right",
					"depth3D": 160,
					"angle": 16,
					"outlineAlpha": 2,
					"outlineColor": "#FFFFFF",
					"outlineThickness": 2,
					"startY": -400,
					"marginRight": 240,
					"marginLeft": 10,
					"balloon": { "fixedPosition": true }
				}
			);
		},
		renderGraph: function()
		{
			if(!this._isChartsReady)
			{
				AmCharts.ready(BX.delegate(this.renderGraph, this));
				this._isChartsReady = true;
				return;
			}

			this._contentWrapper.style.height = this._heightInPixel + "px";

			var item = this._data[0];
			var graphs = [];
			var graphConfigs = BX.type.isArray(item["graphs"]) ? item["graphs"] : null;
			if(graphConfigs)
			{
				for(var i = 0; i < graphConfigs.length; i++)
				{
					graphs.push(this.prepareGraphSettings(graphConfigs[i]));
				}
			}
			else
			{
				graphs.push(this.prepareGraphSettings(item));
			}

			var values = BX.type.isArray(item["values"]) ? item["values"] : [];
			var groupField = BX.type.isNotEmptyString(item["groupField"]) ? item["groupField"] : "";
			var chartConfig =
			{
				type: "serial",
				theme: "none",
				marginLeft: 20,
				dataProvider: values,
				graphs: graphs,
				dataDateFormat: this.getSetting("dateFormat", "YYYY-MM-DD"),
				categoryField: groupField,
				legend: { useGraphSettings: true, position: "bottom" }
			};

			if(this._typeName === "bar" && graphs.length > 0)
			{
				chartConfig["valueAxes"] = [{ stackType: "regular", axisAlpha: 0.3, gridAlpha: 0 }];
				//chartConfig["depth3D"] = 20;
				//chartConfig["angle"] = 30;
			}

			chartConfig["chartCursor"] = { oneBalloonOnly: true };
			if(groupField === "DATE")
			{
				chartConfig["chartCursor"]["categoryBalloonDateFormat"] = "DD MMM";

				var monthNames = [];
				var shortMonthNames = [];
				for(var m = 1; m <= 12; m++)
				{
					monthNames.push(BX.message["MONTH_" + m.toString()]);
					shortMonthNames.push(BX.message["MON_" + m.toString()]);
				}
				AmCharts.monthNames = monthNames;
				AmCharts.shortMonthNames = shortMonthNames;

				var format = BX.message("FORMAT_DATE");
				var dateFormat = format.indexOf("D") < format.indexOf("M") ? "DD MMM" : "MMM DD";
				chartConfig["categoryAxis"] =
					{
						parseDates: true,
						minPeriod: "DD",
						dateFormats:
							[
								{ period: "DD", format: dateFormat },
								{ period: "WW", format: dateFormat },
								{ period: "MM", format: "MMM" },
								{ period: "YYYY", format: "YYYY" }
							]
					};
			}

			AmCharts.makeChart(this._contentWrapper.id, chartConfig);
		},
		renderNumber: function()
		{
			BX.addClass(this._wrapper, "crm-widget-number");

			for(var i = 0; i < this._data.length; i++)
			{
				var item = this._data[i];
				var text = item["value"];
				if(BX.type.isNotEmptyString(item["text"]))
				{
					text = item["text"];
				}

				var displayParams = typeof(item["display"]) !== "undefined" ? item["display"] : {};
				var styleParams = typeof(displayParams["style"]) !== "undefined" ? displayParams["style"] : {};

				if(BX.type.isNotEmptyString(styleParams["backgroundColor"]))
				{
					this._contentWrapper.style.backgroundColor = styleParams["backgroundColor"];
				}

				var content = BX.create("A",
					{
						props: { href: "#" },
						text: text,
						events: { click: BX.eventReturnFalse }
					}
				);
				if(BX.type.isNotEmptyString(styleParams["color"]))
				{
					content.style.color = styleParams["color"];
				}

				var contentNode = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content-amt" },
						children: [ content ]
					}
				);


				if(i === 0)
				{
					this._contentWrapper.appendChild(contentNode);
				}
				else
				{
					var headerNodeWrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });
					this._wrapper.appendChild(headerNodeWrapper);
					headerNodeWrapper.appendChild(
						BX.create("SPAN",
							{
								attrs: { className: "crm-widget-title-container" },
								children:
								[
									BX.create("SPAN",
										{
											attrs: { className: "crm-widget-title-inner" },
											children:
											[
												BX.create("SPAN",
													{ attrs: { className: "crm-widget-title" }, text: item["title"] }
												)
											]
										}
									)
								]
							}
						)
					);

					var contentNodeWrapper = BX.create("DIV", { attrs: { className: "crm-widget-content" } });
					this._wrapper.appendChild(contentNodeWrapper);
					contentNodeWrapper.appendChild(contentNode);
				}
			}
		},
		renderTiledNumber: function()
		{
			BX.addClass(this._wrapper, "crm-widget-total");

			for(var i = 0; i < this._data.length; i++)
			{
				var item = this._data[i];
				var text = item["value"];
				if(BX.type.isNotEmptyString(item["text"]))
				{
					text = item["text"];
				}

				var contentNode = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content-amt" },
						children:
						[
							BX.create("A",
								{
									props: { href: "#" },
									text: text,
									events: { click: BX.eventReturnFalse }
								}
							)
						]
					}
				);

				if(i === 0)
				{
					this._contentWrapper.appendChild(contentNode);
				}
				else
				{
					var contentNodeWrapper = BX.create("DIV", { attrs: { className: "crm-widget crm-widget-5" } });
					this._wrapper.appendChild(contentNodeWrapper);

					contentNodeWrapper.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "crm-widget-head" },
								children:
								[
									BX.create("SPAN",
										{
											attrs: { className: "crm-widget-title-container" },
											children:
											[
												BX.create("SPAN",
													{
														attrs: { className: "crm-widget-title-inner" },
														children:
														[
															BX.create("SPAN",
																{
																	attrs: { className: "crm-widget-title" },
																	text: item["title"]
																}
															)
														]
													}
												)
											]
										}
									)
								]
							}
						)
					);

					var contentNodeInnerWrapper = BX.create("DIV", { attrs: { className: "crm-widget-content" } });
					contentNodeWrapper.appendChild(contentNodeInnerWrapper);
					contentNodeInnerWrapper.appendChild(contentNode);
				}
			}
		},
		renderRating: function()
		{
			BX.addClass(this._wrapper, "crm-widget-rating");
			var wrapper = BX.create("DIV", { attrs: { className: "crm-widget-content-rating" } });
			this._contentWrapper.appendChild(wrapper);

			var item = this._data[0];
			var nomineeId = parseInt(item["nomineeId"]);
			var positions = BX.type.isArray(item["positions"]) ? item["positions"] : [];
			var i;
			var nomineeIndex = -1;
			for(i = 0; i < positions.length; i++)
			{
				if(parseInt(positions[i]["id"]) === nomineeId)
				{
					nomineeIndex = i;
					break;
				}
			}

			if(nomineeIndex >= 0)
			{
				var nominee = positions[nomineeIndex];
				wrapper.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-widget-rating-position" },
							children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-widget-rating-position-inner" },
										children:
										[
											BX.create("SPAN",
												{
													attrs: { className: "crm-widget-rating-pl" },
													text: this.getMessage("nomineeRatingPosition").replace("#POSITION#", nominee["value"])
												}
											),
											BX.create("SPAN",
												{
													attrs: { className: "crm-widget-rating-result" },
													children:
													[
														BX.create("SPAN",
															{
																text: this.getMessage("legend").replace("#LEGEND#", nominee["legend"])
															}
														)
													]
												}
											)
										]
									}
								)
							]
						}
					)
				);
			}

			var neighbours = BX.create("DIV", { attrs: { className: "crm-widget-rating-positions" } });
			wrapper.appendChild(neighbours);

			for(i = 0; i < positions.length; i++)
			{
				var pos = positions[i];
				var posId = parseInt(pos["id"]);
				if(posId === nomineeId)
				{
					continue;
				}

				neighbours.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-widget-rating-position" },
							children:
							[
								this.getMessage("ratingPosition").replace("#POSITION#", pos["value"]),
								BX.create("SPAN",
									{
										attrs: { className: "crm-widget-rating-result" },
										children:
										[
											BX.create("SPAN",
												{
													text: this.getMessage("legend").replace("#LEGEND#", pos["legend"])
												}
											)
										]
									}
								)
							]
						}
					)
				);
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var className = "crm-widget-10";
			if(this._widthInPercent === 50)
			{
				className = "crm-widget-5"
			}
			else if(this._widthInPercent === 70)
			{
				className = "crm-widget-7"
			}
			else if(this._widthInPercent === 30)
			{
				className = "crm-widget-3"
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-widget " + className } });
			this._container.appendChild(this._wrapper);

			this.renderHeader();
			this.renderContent();

			this._hasLayout = true;
		}
	};

	if(typeof(BX.CrmWidget.messages) === "undefined")
	{
		BX.CrmWidget.messages = {};
	}

	BX.CrmWidget.create = function(id, settings)
	{
		var self = new BX.CrmWidget();
		self.initialize(id, settings);
		return self;
	};
}
