if ( ! BX.Tasks )
	BX.Tasks = {};

if ( ! BX.Tasks.componentIframe )
	BX.Tasks.componentIframe = {};

if ( ! BX.Tasks.componentIframe.objTemplate )
{
	BX.Tasks.componentIframe.objTemplate = function(html) {
		var createForm           =  BX.Tasks.lwPopup.createForm;
		var responsibleInputId   = 'task-responsible-employee';
		var oResponsibleSelector =  null;
		var oAccomplicesSelector =  null;
		var oGroupSelector       =  null;
		var oLHEditor            =  null;
		var oCrmUserField        =  null;
		var oWebdavUserField     =  null;
		var originalTaskData     =  null;
		var editorInited         =  false;
		var showCrmField         =  false;


		this.buttonsLocked = false;
		this.initialTaskData = null;
		this.html = null;


		var fillEditForm = function(pTaskData, isPopupJustCreated)
		{
			var title       = '';
			var description = '';
			var priority    = 1;
			var deadline    = '';
			var accomplices = [];
			var groupId     = 0;
			var groupName   = '...';
			var bGroupNameAbsent    = false;
			var crmFieldData        = [];
			var taskControl         = 'Y';
			var allowTimeTracking   = 'N';
			var timeEstimate        = 0;
			var timeEstimateHours   = 0;
			var timeEstimateMinutes = 0;

			// Default for taskControl and allowTimeTracking
			if (BX.message('TASKS_META_OPTION_TASK_CONTROL') === 'Y')
				taskControl = 'Y';
			else
				taskControl = 'N';

			if (BX.message('TASKS_META_OPTION_TIME_TRACKING') === 'Y')
				allowTimeTracking = 'Y';
			else
				allowTimeTracking = 'N';

			if (pTaskData)
			{
				originalTaskData = pTaskData;

				if (pTaskData.TITLE)
					title = pTaskData.TITLE;

				if (pTaskData.DESCRIPTION)
					description = pTaskData.DESCRIPTION;

				if (pTaskData.PRIORITY)
					priority = pTaskData.PRIORITY;

				if (pTaskData.DEADLINE)
					deadline = pTaskData.DEADLINE;

				if (pTaskData.ACCOMPLICES)
					accomplices = pTaskData.ACCOMPLICES;

				if (pTaskData.GROUP_ID)
				{
					bGroupNameAbsent = true;
					groupId = pTaskData.GROUP_ID;

					if (pTaskData['META:GROUP_NAME'])
					{
						groupName = pTaskData['META:GROUP_NAME'];
						bGroupNameAbsent = false;
					}
				}

				if (pTaskData.UF_CRM_TASK)
				{
					crmFieldData = pTaskData.UF_CRM_TASK;
					showCrmField = true;
				}

				if (pTaskData.ALLOW_TIME_TRACKING)
					allowTimeTracking = pTaskData.ALLOW_TIME_TRACKING;

				if (pTaskData.TASK_CONTROL)
					taskControl = pTaskData.TASK_CONTROL;

				if (pTaskData.TIME_ESTIMATE)
				{
					timeEstimate        = pTaskData.TIME_ESTIMATE;
					timeEstimateHours   = Math.floor(timeEstimate / 3600);
					timeEstimateMinutes = Math.round((timeEstimate - timeEstimateHours * 3600) / 60);
				}
			}

			// Cleanup files list
			BX.cleanNode(BX('webform-field-upload-list'));
			BX('task-upload').files = [];

			var responsibleId   = pTaskData.RESPONSIBLE_ID;
			var responsibleName = false;

			BX('lwPopup-task-title').value           = title;
			BX('lwPopup-task-responsible-id').value  = responsibleId;
			BX('lwPopup-task-accomplices-id').value  = accomplices.join(',');
			BX.cleanNode(BX('task-accomplices-list'));

			if (responsibleId == BX.Tasks.lwPopup.loggedInUserId)
			{
				taskControl = 'N';
				BX('lwPopup-task-control').checked = false;
				BX.addClass(BX('lwPopup-task-control').parentNode, 'webform-field-checkbox-option-disabled');
				BX('lwPopup-task-control').disabled = true;
			}
			else
			{
				BX.removeClass(BX('lwPopup-task-control').parentNode, 'webform-field-checkbox-option-disabled');
				BX('lwPopup-task-control').disabled = false;

				if (taskControl === 'Y')
					BX('lwPopup-task-control').checked = true;
				else
					BX('lwPopup-task-control').checked = false;
			}

			if (allowTimeTracking === 'Y')
			{
				BX.addClass(BX('lwPopup-task-time-tracking-container'), 'task-edit-allowed-time-tracking');
				BX('lwPopup-task-allow-time-tracking').checked = true;
			}
			else
			{
				BX.removeClass(BX('lwPopup-task-time-tracking-container'), 'task-edit-allowed-time-tracking');
				BX('lwPopup-task-allow-time-tracking').checked = false;
			}

			if ((timeEstimateHours == 0) && (timeEstimateMinutes == 0))
			{
				BX('lwPopup-task-time-tracking-hours').value   = '';
				BX('lwPopup-task-time-tracking-minutes').value = '';
			}
			else
			{
				BX('lwPopup-task-time-tracking-hours').value   = timeEstimateHours;

				if (timeEstimateMinutes >= 10)
					BX('lwPopup-task-time-tracking-minutes').value = timeEstimateMinutes;
				else
					BX('lwPopup-task-time-tracking-minutes').value = '0' + timeEstimateMinutes.toString();
			}

			oLHEditor.setContent(description);

			oCrmUserField.setValue(crmFieldData);

			this._togglePriority(priority);
			this._setDeadline(deadline);

			oGroupSelector.setSelected({id: groupId, title: groupName});
			this._onGroupSelect([{id: groupId, title: groupName}]);

			if (bGroupNameAbsent)
			{
				BX.CJSTask.getGroupsData(
					[groupId], {
						callback: (function(groupId, selfObj){
							return function(arGroups) {
								var groupName = arGroups[groupId]['NAME'];

								oGroupSelector.setSelected({
									id    : groupId,
									title : groupName
								});

								selfObj._onGroupSelect([{
									id    : groupId,
									title : groupName
								}]);
							}
						})(groupId, this)
					}
				)
			}

			var bResponsibleNameAbsent = false;

			if (pTaskData['META:RESPONSIBLE_FORMATTED_NAME'])
				responsibleName = pTaskData['META:RESPONSIBLE_FORMATTED_NAME'];
			else if (pTaskData.RESPONSIBLE_LAST_NAME && pTaskData.RESPONSIBLE_NAME)
				responsibleName = pTaskData.RESPONSIBLE_NAME + ' ' + pTaskData.RESPONSIBLE_LAST_NAME;
			else
			{
				var bResponsibleNameAbsent = true;
				responsibleName = '...';			// name is unknown yet
			}

			BX(responsibleInputId).value = responsibleName;
			oResponsibleSelector.setSelectedUsers([{
				id   : responsibleId,
				name : responsibleName
			}]);

			var arUsersInAnotherFormat = [];

			for (var i = 0; i < accomplices.length; i++)
			{
				arUsersInAnotherFormat.push({
					id   : accomplices[i],
					name : '...'			// names are unknown yet
				});
			}

			oAccomplicesSelector.setSelectedUsers(arUsersInAnotherFormat);
			this._onAccomplicesSelect(arUsersInAnotherFormat);

			// Delayed user names formatting
			if (bResponsibleNameAbsent || (accomplices.length > 0))
			{
				var usersIds = [];

				usersIds.push.apply(usersIds, accomplices);

				if (bResponsibleNameAbsent)
					usersIds.push(responsibleId);

				BX.CJSTask.formatUsersNames(
					usersIds, {
						callback: (function(bResponsibleNameAbsent, responsibleId, accomplices, selfObj){
							return function(arUsers) {
								if (bResponsibleNameAbsent)
								{
									BX(responsibleInputId).value = arUsers['u' + responsibleId];

									oResponsibleSelector.setSelectedUsers([{
										id   : responsibleId,
										name : arUsers['u' + responsibleId]
									}]);
								}

								var arUsersInAnotherFormat = [];

								for (var i = 0; i < accomplices.length; i++)
								{
									arUsersInAnotherFormat.push(
										{
											id   : accomplices[i],
											name : arUsers['u' + accomplices[i]]
										}
									);
								}

								oAccomplicesSelector.setSelectedUsers(arUsersInAnotherFormat);

								selfObj._onAccomplicesSelect(arUsersInAnotherFormat);
							}
						})(bResponsibleNameAbsent, responsibleId, accomplices.slice(), this)
					}
				);
			}
		};


		createForm.callbacks.onAfterPopupCreated = function(pTaskData)
		{
			var btnHintCreateMultiple = 'Shift+Enter';
			var btnHintCreateOnce = 'Ctrl+Enter';

			var crmFieldData = [];
			if (pTaskData.hasOwnProperty('UF_CRM_TASK'))
				crmFieldData.push.apply(crmFieldData, pTaskData.UF_CRM_TASK);

			var webdavFieldData = [];
			if (pTaskData.hasOwnProperty('UF_TASK_WEBDAV_FILES'))
				webdavFieldData.push.apply(webdavFieldData, pTaskData.UF_TASK_WEBDAV_FILES);

			var accomplices = [];
			if (pTaskData.hasOwnProperty('ACCOMPLICES'))
				accomplices.push.apply(accomplices, pTaskData.ACCOMPLICES);

			var selectors = BX.Tasks.lwPopup.__initSelectors([
				{
					requestedObject  : 'intranet.user.selector.new',
					selectedUsersIds :  [pTaskData.RESPONSIBLE_ID],
					anchorId         :  responsibleInputId,
					bindClickTo      :  BX(responsibleInputId).parentNode,
					userInputId      :  responsibleInputId,
					multiple         : 'N',
					callbackOnSelect :  (function(obj) {
						return function (arUsers)
						{
							obj._onResponsibleSelect(arUsers);
						}
					})(this)
				},
				{
					requestedObject  : 'intranet.user.selector.new',
					selectedUsersIds :  accomplices,
					anchorId         : 'task-accomplices-link',
					multiple         : 'Y',
					btnSelectText    :  BX.message('TASKS_BTN_SELECT'),
					btnCancelText    :  BX.message('TASKS_BTN_CANCEL'),
					callbackOnSelect :  (function(obj) {
						return function (arUsers)
						{
							obj._onAccomplicesSelect(arUsers);
						}
					})(this)
				},
				{
					requestedObject  : 'socialnetwork.group.selector',
					bindElement      : 'task-sonet-group-selector',
					callbackOnSelect : (function(obj) {
						return function (arGroups, params)
						{
							obj._onGroupSelect(arGroups, params);
						}
					})(this)
				},
				{
					requestedObject : 'LHEditor',
					attachTo        : 'lwPopup-task-description-area'
				},
				{
					requestedObject  : 'system.field.edit::CRM',
					userFieldName    : 'UF_CRM_TASK',
					taskId           :  0,
					value            :  crmFieldData,
					callbackOnRedraw :  (function(obj){
						return function(fieldLabel, containerId){
							obj.__onCrmFieldRedraw(fieldLabel, containerId);
						}
					})(this)
				},
				{
					requestedObject  : 'system.field.edit::WEBDAV',
					userFieldName    : 'UF_TASK_WEBDAV_FILES',
					taskId           :  0,
					value            :  webdavFieldData,
					callbackOnRedraw :  (function(obj){
						return function(fieldLabel, containerId){
							obj.__onWebdavFieldRedraw(fieldLabel, containerId);
						}
					})(this)
				}
			]);

			oResponsibleSelector = selectors[0];
			oAccomplicesSelector = selectors[1];
			oGroupSelector       = selectors[2];
			oLHEditor            = selectors[3];
			oCrmUserField        = selectors[4];
			oWebdavUserField     = selectors[5];

			if (BX.message('TASKS_META_OPTION_OPENED_DESCRIPTION') === 'Y')
			{
				BX.removeClass('lwPopup-task-description-label-icon', 'task-description-label-icon-right');
				BX.addClass('lwPopup-task-description-label-icon', 'task-description-label-icon-bottom');
				BX('lwPopup-task-description-area-container').style.display = 'block';
			}
			else
			{
				BX.removeClass('lwPopup-task-description-label-icon', 'task-description-label-icon-bottom');
				BX.addClass('lwPopup-task-description-label-icon', 'task-description-label-icon-right');
				BX('lwPopup-task-description-area-container').style.display = 'none';
			}

			if (BX.browser.IsMac())
			{
				var e = document.createElement('div');
				e.innerHTML = "&#8984;";
				var cmdSymbol = e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
				btnHintCreateOnce = cmdSymbol + "+Enter";
			}

			BX('task-submit-button-text').innerHTML = BX.message('TASKS_BTN_CREATE_TASK') + ' (' + btnHintCreateOnce + ')';
			BX('task-submit-and-create-new-when-back-to-form-button-text').innerHTML = BX.message('TASKS_BTN_CREATE_TASK_AND_ONCE_MORE') + ' (' + btnHintCreateMultiple + ')';
			BX('task-cancel-button-text').innerHTML = BX.message('TASKS_BTN_CANCEL');
		};


		createForm.callbacks.onBeforePopupShow = function(pTaskData, params)
		{
			var webdavFieldData = [];
			var isPopupJustCreated = false;

			params = params || {};

			if (params.hasOwnProperty(isPopupJustCreated))
				isPopupJustCreated = params.isPopupJustCreated;

			this.initialTaskData = JSON.parse(JSON.stringify(pTaskData));

			fillEditForm.call(this, pTaskData, isPopupJustCreated);
			this.__checkWarnings();

			if ( ! isPopupJustCreated )
				this.__cleanErrorsArea();

			// due to http://jabber.bx/view.php?id=30480
			if (BX.browser.IsSafari())
				BX("task-upload").multiple = false;

			if (oWebdavUserField)
				webdavFieldData = oWebdavUserField.getValue();

			if (
				(webdavFieldData.length > 0)
				&& (
					! pTaskData.hasOwnProperty('UF_TASK_WEBDAV_FILES')
					|| (typeof pTaskData.UF_TASK_WEBDAV_FILES !== 'object')
					|| ! pTaskData.UF_TASK_WEBDAV_FILES.hasOwnProperty('length')
					|| pTaskData.UF_TASK_WEBDAV_FILES.length == 0
				)
			)
			{
				oWebdavUserField.setValue([0]);
			}
		};


		createForm.callbacks.onAfterPopupShow = function()
		{
			if (editorInited)
			{
				if((BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE()) && typeof oLHEditor != 'undefined' && typeof oLHEditor['editor'] != 'undefined')
				{
					oLHEditor['editor'].Focus(false);
				}

				BX('lwPopup-task-title').focus();
			}

			BX.bind(
				document,
				'keydown',
				BX.Tasks.lwPopup.createForm.objTemplate._processKeyDown
			);
		};


		createForm.callbacks.onPopupClose = function()
		{
			BX.unbind(
				document,
				'keydown',
				BX.Tasks.lwPopup.createForm.objTemplate._processKeyDown
			);
		};


		createForm.callbacks.onAfterEditorInited = function()
		{
			if((BX.browser.IsChrome() || BX.browser.IsIE11() || BX.browser.IsIE()) && typeof oLHEditor != 'undefined' && typeof oLHEditor['editor'] != 'undefined' && ('Focus' in oLHEditor['editor']))
			{
				oLHEditor['editor'].Focus(false);
			}

			BX('lwPopup-task-title').focus();
			editorInited = true;
		};


		this.prepareTitleBar = function()
		{
			var html = '<span class="task-detail-popup-title">' + BX.message('TASKS_TIT_CREATE_TASK_2') + '</span>'
				+ '<span class="task-detail-popup-btn" onclick="BX.Tasks.lwPopup.createForm.objTemplate._runFullEditForm();">'
				+ BX.message('TASKS_LINK_SHOW_FULL_CREATE_FORM')
				+ '</span>';

			return ({
				content: BX.create(
					'span',
					{
						html : html
					}
				)
			});
		};


		this._processKeyDown = function(e)
		{
			if (e.keyCode == 27)
			{
				bClose = true;

				// Escape key pressed
				var taskData = createForm.objTemplate.gatherTaskDataFromForm();
				if (
					(
						taskData.hasOwnProperty('TITLE')
						&& taskData.TITLE.length
					)
					||
					(
						taskData.hasOwnProperty('DESCRIPTION')
						&& taskData.DESCRIPTION.length
					)
				)
				{
					bClose = confirm(BX.message('TASKS_CONFIRM_CLOSE_CREATE_DIALOG'));
				}

				if (bClose)
					createForm.objPopup.close();
			}

			var bEnterPressed = (e.keyCode == 0xA) || (e.keyCode == 0xD);

			if ( ! bEnterPressed )
				return;

			if (e.ctrlKey || e.metaKey)
				createForm.objTemplate._submitAndClosePopup();
			else if (e.shiftKey)
				createForm.objTemplate._submitAndCreateOnceMore();
		};


		this._runFullEditForm = function()
		{
			var taskData = BX.Tasks.lwPopup.createForm.objTemplate.gatherTaskDataFromForm();
			if (taskData.hasOwnProperty('ACCOMPLICES'))
			{
				taskData.ACCOMPLICES_IDS = taskData.ACCOMPLICES.slice();
				delete taskData.ACCOMPLICES;
			}

			if (taskData.hasOwnProperty('RESPONSIBLE_SECOND_NAME'))
				delete taskData.RESPONSIBLE_SECOND_NAME;

			if (taskData.hasOwnProperty('RESPONSIBLE_LAST_NAME'))
				delete taskData.RESPONSIBLE_LAST_NAME;

			if (taskData.hasOwnProperty('RESPONSIBLE_NAME'))
				delete taskData.RESPONSIBLE_NAME;

			if (taskData.hasOwnProperty('META:RESPONSIBLE_FORMATTED_NAME'))
				delete taskData['META:RESPONSIBLE_FORMATTED_NAME'];

			if (taskData.hasOwnProperty('META:GROUP_NAME'))
				delete taskData['META:GROUP_NAME'];

			taskIFramePopup.add(taskData);

			BX.Tasks.lwPopup.createForm.objPopup.close();
		}


		this.prepareContent = function()
		{
			if (this.html == null)
			{
				this.html = '<div class="webform task-webform">\
					<div id="lwPopup-task-errorsArea" class="webform-round-corners webform-error-block" style="display: none;">\
						<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
						<div class="webform-content">\
							<ul id="lwPopup-task-errorsArea-list" class="webform-error-list">\
							</ul>\
						</div>\
						<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
					</div>\
					<div class="webform-round-corners webform-main-fields task-main-fields">\
						<div class="webform-corners-top">\
							<div class="webform-left-corner"></div>\
							<div class="webform-right-corner"></div>\
						</div>\
						<div class="webform-content">\
							<div class="webform-row task-title-row">\
								<div class="webform-field-label"><label for="task-title">\
									' + BX.message('TASKS_TITLE') + '\
								</label></div>\
								<div class="webform-field webform-field-textbox-double task-title">\
									<div class="webform-field-textbox-inner"\
										><input type="text" name="TITLE" id="lwPopup-task-title" \
											placeholder="' + BX.message('TASKS_TITLE_PLACEHOLDER') + '"\
											style="height:23px;" class="webform-field-textbox"\
											value=""\
									/></div>\
								</div>\
							</div>\
							\
							<div class="webform-row task-quick-responsible-employee-row">\
								<table cellspacing="0" class="task-responsible-employee-layout">\
									<tr>\
										<td class="task-responsible-employee-layout-left">\
											<div class="webform-field-label"\
												><label for="task-responsible-employee" \
													id="task-responsible-employee-label">\
														' + BX.message('TASKS_RESPONSIBLE') + '\
												</label></div>\
											\
											<div class="webform-field webform-field-combobox task-responsible-employee" \
												id="task-responsible-employee-block">\
												<div class="webform-field-combobox-inner">\
													<input type="text" autocomplete="off" id="task-responsible-employee" \
														class="webform-field-combobox" value="" \
													/><a href="javascript:void(0);" class="webform-field-combobox-arrow">&nbsp;</a>\
													<input type="hidden" id="lwPopup-task-responsible-id" value="" />\
												</div>\
											</div>\
											\
											<div class="webform-field task-quick-assistants" id="task-accomplices-block">\
												<div class="task-assistants-label"\
													><a href="javascript:void(0);" class="task-quick-assistants-link" \
														id="task-accomplices-link"\
														onclick="BX.Tasks.lwPopup.createForm.objTemplate._showAccomplicesSelector(event);"\
														>\
														' + BX.message('TASKS_TASK_ACCOMPLICES') + '\
												</a></div>\
												<div class="task-assistants-list" id="task-accomplices-list">\
												</div>\
												<input type="hidden" id="lwPopup-task-accomplices-id" value="" />\
											</div>\
										</td>\
										<td class="task-responsible-employee-layout-right">\
											<div class="webform-field task-priority" id="task-priority">\
												<label>' + BX.message('TASKS_PRIORITY') + ':</label>\
													<a href="javascript:void(0);" class="task-priority-low"\
														id="lwPopup-task-priority-0" \
														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(0);"\
														><i></i><span>\
														' + BX.message('TASKS_PRIORITY_LOW') + '\
													</span><b></b></a>\
													<a href="javascript:void(0);" class="task-priority-middle"\
														id="lwPopup-task-priority-1" \
														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(1);"\
														><i></i><span>\
														' + BX.message('TASKS_PRIORITY_NORMAL') + '\
													</span><b></b></a>\
													<a href="javascript:void(0);" class="task-priority-high"\
														id="lwPopup-task-priority-2" \
														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(2);"\
														><i></i><span>\
														' + BX.message('TASKS_PRIORITY_HIGH') + '\
													</span><b></b></a>\
												<input type="hidden" id="lwPopup-task-priority" value="" />\
											</div>\
										</td>\
									</tr>\
								</table>\
							</div>\
							\
							<div class="webform-row task-quick-dates-row">\
								<div class="webform-field">\
									<div class="webform-field task-quick-deadline-settings"\
										><label for="task-deadline-date">\
											' + BX.message('TASKS_DEADLINE') + ':</label\
										>&nbsp;&nbsp;<span style="display:inline; line-height:20px;" id="task-quick-detail-deadline"\
											onclick="\
												BX.Tasks.lwPopup._showCalendar(\
													BX(\'task-quick-detail-deadline\'),\
													BX(\'lwPopup-task-deadline\'),\
													{\
														callback_after : function(value) {\
															BX.Tasks.lwPopup.createForm.objTemplate._setDeadline(BX(\'lwPopup-task-deadline\').value);\
														}\
													}\
												);\
												"\
											class="webform-field-action-link">\
												' + BX.message('TASKS_THERE_IS_NO_DEADLINE') + '\
											</span\
									><input type="text" value="" id="lwPopup-task-deadline" data-default-time="' + BX.message('TASKS_COMPANY_WORKTIME') + '" \
										style="display:none;"><span id="task-detail-deadline-remove"\
										onclick="BX.Tasks.lwPopup.createForm.objTemplate._clearDeadline();" \
										class="task-deadline-delete"\
										style="display:none;"></span>\
									</div>\
								</div>\
							</div>\
							<div class="webform-row task-options-row">\
								<div class="webform-field webform-field-checkbox-options task-options">\
									<div class="webform-field-checkbox-option" style="margin-top: 13px;"><input type="checkbox" value="Y" \
										onclick="\
											if (this.checked)\
												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'Y\');\
											else\
												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'N\');"\
										onchange="\
											if (this.checked)\
												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'Y\');\
											else\
												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'N\');"\
										id="lwPopup-task-control" class="webform-field-checkbox"><label for="lwPopup-task-control">'
											+ BX.message('TASKS_CONTROL_CHECKBOX') 
									+ '</label></div>\
									<div id="lwPopup-task-time-tracking-container" class="webform-field-checkbox-option" \
										><input type="checkbox" value="Y" id="lwPopup-task-allow-time-tracking" \
											onclick="\
												if (this.checked)\
												{\
													BX.addClass(this.parentNode, \'task-edit-allowed-time-tracking\');\
													BX.userOptions.save(\'tasks\', \'popup_options\', \'time_tracking\', \'Y\');\
												}\
												else\
												{\
													BX.removeClass(this.parentNode, \'task-edit-allowed-time-tracking\');\
													BX.userOptions.save(\'tasks\', \'popup_options\', \'time_tracking\', \'N\');\
												}"\
											onchange="\
												if (this.checked)\
												{\
													BX.addClass(this.parentNode, \'task-edit-allowed-time-tracking\');\
													BX.userOptions.save(\'tasks\', \'popup_options\', \'time_tracking\', \'Y\');\
												}\
												else\
												{\
													BX.removeClass(this.parentNode, \'task-edit-allowed-time-tracking\');\
													BX.userOptions.save(\'tasks\', \'popup_options\', \'time_tracking\', \'N\');\
												}"\
											class="webform-field-checkbox" /><label for="lwPopup-task-allow-time-tracking" class="task-edit-allowed-time-tracking-hide">'
												+ BX.message('TASKS_TASK_ALLOW_TIME_TRACKING') +
											'</label><label for="lwPopup-task-allow-time-tracking" class="task-edit-allowed-time-tracking-show">'
												+ BX.message('TASKS_TASK_ALLOW_TIME_TRACKING_DETAILS') +
											'</label>\
											<span style="display:inline;"><span style="display:inline-block;"><span style="height:18px; display:inline-block; border:1px;"></span></span></span>\
											<span class="task-edit-allowed-time-tracking-show">\
												<span class="webform-field webform-field-textbox task-time-tracking-hours"><span class="webform-field-textbox-inner"><input type="text" id="lwPopup-task-time-tracking-hours" value="" maxlength="4" class="webform-field-textbox task-time-tracking-hours-input"></span></span><span>'
													+ BX.message('TASKS_TASK_TIME_TRACKING_HOURS') +
												'</span><span class="webform-field webform-field-textbox task-time-tracking-minutes"><span class="webform-field-textbox-inner"><input type="text" id="lwPopup-task-time-tracking-minutes" value="" maxlength="2" class="webform-field-textbox task-time-tracking-minutes-input"></span></span><span>'
													+ BX.message('TASKS_TASK_TIME_TRACKING_MINUTES') +
												'</span><span class="task-deadline-delete" style="" onclick="BX(\'lwPopup-task-time-tracking-hours\').value = \'\'; BX(\'lwPopup-task-time-tracking-minutes\').value = \'\'; "></span>\
											</span>\
									</div>\
								</div>\
							</div>\
						</div>\
					</div>\
					\
					<div class="webform-round-corners webform-additional-fields">\
						<div id="lwPopup-task-grey-area" class="webform-content">\
							<div class="webform-row task-description-row">\
								<div class="webform-field-label task-description-label-container"\
									><a href="javascript:void(0);" class="task-description-label"\
										onclick="\
										this.blur();\
										if (BX(\'lwPopup-task-description-area-container\').style.display === \'none\')\
										{\
											BX.removeClass(\'lwPopup-task-description-label-icon\', \'task-description-label-icon-right\');\
											BX.addClass(\'lwPopup-task-description-label-icon\', \'task-description-label-icon-bottom\');\
											BX(\'lwPopup-task-description-area-container\').style.display = \'block\';\
											BX.userOptions.save(\'tasks\', \'popup_options\', \'opened_description\', \'Y\');\
										}\
										else\
										{\
											BX.removeClass(\'lwPopup-task-description-label-icon\', \'task-description-label-icon-bottom\');\
											BX.addClass(\'lwPopup-task-description-label-icon\', \'task-description-label-icon-right\');\
											BX(\'lwPopup-task-description-area-container\').style.display = \'none\';\
											BX.userOptions.save(\'tasks\', \'popup_options\', \'opened_description\', \'N\');\
										}\
										"\
										>' + BX.message('TASKS_DESCRIPTION') + '</a><span \
									id="lwPopup-task-description-label-icon"\
									class="task-description-label-icon task-description-label-icon-right">&nbsp;</span></div>\
								<div class="webform-field webform-field-textarea task-description-textarea" id="lwPopup-task-description-area-container" style="display:none;">\
									<div class="webform-field-textarea-inner" id="lwPopup-task-description-area">\
										<textarea readonly="readonly"></textarea>\
									</div>\
								</div>\
							</div>\
							\
							<div class="webform-row task-group-row">\
								<a href="javascript:void(0);" id="task-sonet-group-selector"\
									class="task-quick-popup-group-selector-link"\
									>' + BX.message('TASKS_GROUP') + '</a>\
							</div>\
							\
							<div class="webform-row task-attachments-row" style="display:none;">\
								<div class="webform-field webform-field-attachments">\
									<ol class="webform-field-upload-list" id="webform-field-upload-list"></ol>\
									<div class="webform-field-upload">\
										<span class="webform-button webform-button-upload"\
											><span class="webform-button-left"></span\
											><span class="webform-button-text">' + BX.message('TASKS_UPLOAD_FILES') + '</span\
											><span class="webform-button-right"></span\
										></span>\
										<input type="file" name="task-attachments[]" size="1" \
											multiple="multiple" id="task-upload"\
											onChange="BX.Tasks.lwPopup.createForm.objTemplate._onFilesChange.call(this, event);" />\
									</div>\
								</div>\
							</div>\
						</div>\
						\
						<div class="webform-corners-bottom">\
							<div class="webform-left-corner"></div>\
							<div class="webform-right-corner"></div>\
						</div>\
					</div>\
					\
					<div id="task-edit-warnings-area"\
						class="webform-round-corners webform-warning-block"\
						style="display: none; margin:10px 0;">\
						<div class="webform-corners-top">\
							<div class="webform-left-corner"></div>\
							<div class="webform-right-corner"></div>\
						</div>\
						<div class="webform-content">\
							<div id="task-edit-warnings-area-message"></div>\
						</div>\
						<div class="webform-corners-bottom">\
							<div class="webform-left-corner"></div>\
							<div class="webform-right-corner"></div>\
						</div>\
					</div>\
					\
					<div class="webform-buttons task-buttons">\
						<a id="task-submit-button" class="webform-button webform-button-create" \
							onclick="BX.Tasks.lwPopup.createForm.objTemplate._submitAndClosePopup();" \
							href="javascript: void(0);"><span class="webform-button-left"></span\
							><span class="webform-button-text" \
								id="task-submit-button-text"></span\
							><span class="webform-button-right"></span></a>\
						<a id="task-submit-and-create-new-when-back-to-form-button-text" \
							href="javascript: void(0);"\
							class="webform-button-link task-button-create-link" \
							onclick="BX.Tasks.lwPopup.createForm.objTemplate._submitAndCreateOnceMore();" \
						></a>\
						<a class="webform-button-link webform-button-link-cancel" \
							href="javascript:void(0);" \
							id="task-cancel-button-text" \
							onclick="BX.Tasks.lwPopup.createForm.objPopup.close();" \
						></a>\
					</div>\
				</div>';

			}
			return(BX.create(
				'div',
				{
					props : { className : 'task-quick-create-popup' },
					html: this.html
				}
			));
		};


		this.gatherTaskDataFromForm = function()
		{
			var taskData = originalTaskData;

			var accomplices = [];
			var arFiles = document.getElementsByName('FILES[]');

			var filesIds = [];
			if (arFiles)
			{
				var cnt = arFiles.length;

				for (var i=0; i<cnt; i++)
					filesIds.push(arFiles[i].value);
			}

			if (BX('lwPopup-task-accomplices-id').value.length > 0)
				accomplices = BX('lwPopup-task-accomplices-id').value.split(',');

			var groupId = 0;

			if (BX('lwPopup-task-group-id'))
				groupId = BX('lwPopup-task-group-id').value;

			var allowTimeTracking = 'N';
			var timeEstimate      = 0;

			if (BX('lwPopup-task-allow-time-tracking').checked)
			{
				allowTimeTracking = 'Y';

				if (BX('lwPopup-task-time-tracking-hours') && BX('lwPopup-task-time-tracking-minutes'))
				{
					var timeEstimateHours   = parseInt(BX('lwPopup-task-time-tracking-hours').value);
					var timeEstimateMinutes = parseInt(BX('lwPopup-task-time-tracking-minutes').value);

					if (isNaN(timeEstimateHours))
						timeEstimateHours = 0;

					if (isNaN(timeEstimateMinutes))
						timeEstimateMinutes = 0;

					timeEstimate = timeEstimateHours * 3600 + timeEstimateMinutes * 60;
				}
			}

			if (BX('lwPopup-task-control').checked)
				taskControl = 'Y';
			else
				taskControl = 'N';

			taskData.TITLE                = BX('lwPopup-task-title').value;
			taskData.DESCRIPTION          = oLHEditor.getContent();
			taskData.DEADLINE             = BX('lwPopup-task-deadline').value;
			taskData.PRIORITY             = BX('lwPopup-task-priority').value;
			taskData.RESPONSIBLE_ID       = BX('lwPopup-task-responsible-id').value;
			taskData.ACCOMPLICES          = accomplices;
			taskData.FILES                = filesIds;
			taskData.GROUP_ID             = groupId;
			taskData.TASK_CONTROL         = taskControl;
			taskData.UF_CRM_TASK          = oCrmUserField.getValue();
			taskData.UF_TASK_WEBDAV_FILES = oWebdavUserField.getValue();
			taskData.ALLOW_TIME_TRACKING  = allowTimeTracking;

			var formField = BX('diskuf-edit-rigths-doc');
			if(BX.type.isElementNode(formField) && ('value' in formField))
			{
				taskData.DISK_ATTACHED_OBJECT_ALLOW_EDIT = formField.value;
				taskData.TASKS_TASK_DISK_ATTACHED_OBJECT_ALLOW_EDIT = formField.value;
			}

			if (allowTimeTracking === 'Y')
				taskData.TIME_ESTIMATE = timeEstimate;

			taskData['META:GROUP_NAME'] = null;
			taskData['META:RESPONSIBLE_FORMATTED_NAME'] = null;
			taskData.RESPONSIBLE_NAME = null;
			taskData.RESPONSIBLE_LAST_NAME = null;
			taskData.RESPONSIBLE_SECOND_NAME = null;

			if ( ! taskData.hasOwnProperty('ALLOW_CHANGE_DEADLINE') )
				taskData['ALLOW_CHANGE_DEADLINE'] = 'Y';

			return (taskData);
		};


		this.__lockButtons = function()
		{
			this.buttonsLocked = true;
		};


		this.__releaseButtons = function()
		{
			this.buttonsLocked = false;
		};


		this._submitAndClosePopup = function()
		{
			var columnsIds = null;

			if (this.buttonsLocked)
				return;

			this.__lockButtons();

			if ((typeof tasksListNS !== 'undefined') && tasksListNS.getColumnsOrder)
				columnsIds = tasksListNS.getColumnsOrder();

			this.__cleanErrorsArea();
			var taskData = createForm.objTemplate.gatherTaskDataFromForm();
			BX.Tasks.lwPopup._createTask({
				taskData : taskData,
				onceMore : false,
				columnsIds : columnsIds,
				callbackOnSuccess : (function(objSelf){
					return function(){
						createForm.objPopup.close();
						objSelf.__releaseButtons();
					};
				})(this),
				callbackOnFailure : (function(objSelf){
					return function(data) {
						objSelf.__fillErrorsArea(data.errMessages);
						objSelf.__releaseButtons();
					};
				})(this)
			});
		};


		this._submitAndCreateOnceMore = function()
		{
			var columnsIds = null;

			if (this.buttonsLocked)
				return;

			this.__lockButtons();

			if ((typeof tasksListNS !== 'undefined') && tasksListNS.getColumnsOrder)
				columnsIds = tasksListNS.getColumnsOrder();

			this.__cleanErrorsArea();
			var taskData = createForm.objTemplate.gatherTaskDataFromForm();
			BX.Tasks.lwPopup._createTask({
				taskData : taskData,
				onceMore : true,
				columnsIds : columnsIds,
				callbackOnSuccess : (function(objSelf){
					return function(){
						createForm.objPopup.close();
						objSelf.__releaseButtons();
						objSelf.initialTaskData.TITLE = '';
						objSelf.initialTaskData.DESCRIPTION = '';
						objSelf.initialTaskData.ACCOMPLICES = [];
						BX.Tasks.lwPopup.showCreateForm(objSelf.initialTaskData);
					};
				})(this),
				callbackOnFailure : (function(objSelf){
					return function(data) {
						objSelf.__fillErrorsArea(data.errMessages);
						objSelf.__releaseButtons();
					};
				})(this)
			});			
		};


		this.prepareButtons = function()
		{
			return([]);
		};


		this._togglePriority = function(newPriority)
		{
			BX.removeClass('lwPopup-task-priority-0', 'selected');
			BX.removeClass('lwPopup-task-priority-1', 'selected');
			BX.removeClass('lwPopup-task-priority-2', 'selected');

			BX('lwPopup-task-priority').value = newPriority;
			BX.addClass('lwPopup-task-priority-' + newPriority, 'selected');
		};


		this._clearDeadline = function()
		{
			BX('task-detail-deadline-remove').style.display = 'none';
			BX('lwPopup-task-deadline').value = '';
			var dateSpan = BX('task-quick-detail-deadline');
			BX.cleanNode (dateSpan);
			var newsubcont = document.createElement('span');
			newsubcont.innerHTML = BX.message('TASKS_THERE_IS_NO_DEADLINE');
			dateSpan.appendChild(newsubcont);
			dateSpan.className = 'webform-field-action-link';
		};


		this._setDeadline = function(newValue)
		{
			if ((newValue === null) || (newValue === false) || (newValue === ''))
			{
				this._clearDeadline();
				return;
			}

			BX('lwPopup-task-deadline').value = newValue;
			var dateSpan = BX('task-quick-detail-deadline');
			dateSpan.innerHTML = newValue;
			dateSpan.className = 'task-detail-deadline webform-field-action-link';
			BX('task-detail-deadline-remove').style.display = '';
		};


		this._onGroupSelect = function(groups, params)
		{
			// try
			// {
			// 	if (
			// 		(typeof(params) === 'object')
			// 		&& (typeof(params.onInit) !== 'undefined')
			// 		&& (params.onInit === true)
			// 	)
			// 	{
			// 		return;
			// 	}
			// }
			// catch (e)
			// {
			// }

			if (!groups[0])
				return;

			if (groups[0]['id'] == 0)
			{
				this._clearGroup();
				return;
			}

			BX.adjust(BX("task-sonet-group-selector"), {
				text: BX.message('TASKS_GROUP') + ": " + groups[0].title
			});
			var deleteIcon = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "span", className: "task-group-delete"});
			if (deleteIcon)
			{
				BX.adjust(deleteIcon, {
					events: {
						click: function(e) {
							if (!e) e = window.event;
							BX.Tasks.lwPopup.createForm.objTemplate._clearGroup(groups[0].id);
						}
					}
				})
			}
			else
			{
				BX("task-sonet-group-selector").parentNode.appendChild(
					BX.create("span", {
						props: {className: "task-group-delete"},
						events: {
							click: function(e)
							{
								if (!e) e = window.event;
								BX.Tasks.lwPopup.createForm.objTemplate._clearGroup(groups[0].id);
							}
						}
					})
				);
			}
			var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_ID"});
			if (input)
			{
				BX.adjust(input, {props: {value: groups[0].id}})

				var inputWithGroupName = BX.findNextSibling(
					BX("task-sonet-group-selector"),
					{tag: "input", className: "tasks-notclass-GROUP_NAME"}
				);

				BX.adjust(
					inputWithGroupName, 
					{props: {value: groups[0].title}}
				);
			}
			else
			{
				BX("task-sonet-group-selector").parentNode.appendChild(
					BX.create("input", {
						props: {
							id   : 'lwPopup-task-group-id',
							name: "GROUP_ID",
							className: 'tasks-notclass-GROUP_ID',
							type: "hidden",
							value: groups[0].id
						}
					})
				);

				BX("task-sonet-group-selector").parentNode.appendChild(
					BX.create("input", {
						props: {
							name: "GROUP_NAME",
							className: 'tasks-notclass-GROUP_NAME',
							type: 'hidden',
							value: groups[0].title
						}
					})
				);
			}

			// Show warning if responsible person not in selected group
			this.__checkWarnings();
		};


		this._clearGroup = function(groupId)
		{
			// Show warning if responsible person not in selected group
			this.__checkWarnings();

			BX.adjust(BX("task-sonet-group-selector"), {
				text: BX.message('TASKS_GROUP')
			});
			var deleteIcon = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "span", className: "task-group-delete"});
			if (deleteIcon)
			{
				BX.cleanNode(deleteIcon, true);
			}
			var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_ID"});
			if (input)
			{
				input.value = 0;
			}
			var input = BX.findNextSibling(BX("task-sonet-group-selector"), {tag: "input", className: "tasks-notclass-GROUP_NAME"});
			if (input)
			{
				input.value = '';
			}

			if (groupId)
				oGroupSelector.deselect(groupId);
		};


		this._showAccomplicesSelector = function(e)
		{
			oAccomplicesSelector.showUserSelector(e);
		};


		this._onAccomplicesSelect = function(arUsers)
		{
			var empIDs = [];
			BX.cleanNode(BX("task-accomplices-list"));
			var bindLink = BX("task-accomplices-link");

			var arUsersCount = arUsers.length;
			for (i = 0; i < arUsersCount; i++)
			{
				BX("task-accomplices-list").appendChild(BX.create("div", {
					props : {
						className : "task-assistant-item"
					},
					children : [
						BX.create("span", {
							props : {
								className : "task-assistant-link",
								href : BX.Tasks.lwPopup.pathToUser.replace("#user_id#", arUsers[i].id),
								target : "_blank",
								title : arUsers[i].name
							},
							text : arUsers[i].name
						})
					]
				}));

				empIDs.push(arUsers[i].id);
			}

			if (empIDs.length > 0)
			{
				if(bindLink.innerHTML.substr(bindLink.innerHTML.length - 1) != ":")
					bindLink.innerHTML = bindLink.innerHTML + ":";
			}
			else
			{
				if(bindLink.innerHTML.substr(bindLink.innerHTML.length - 1) == ":")
					bindLink.innerHTML = bindLink.innerHTML.substr(0, bindLink.innerHTML.length - 1);
			}

			BX('lwPopup-task-accomplices-id').value = empIDs.join(',');
		};


		this._onResponsibleSelect = function(arUser)
		{
			BX('lwPopup-task-responsible-id').value = arUser.id;

			if (arUser.id == BX.Tasks.lwPopup.loggedInUserId)
			{
				BX('lwPopup-task-control').checked = false;
				BX.addClass(BX('lwPopup-task-control').parentNode, 'webform-field-checkbox-option-disabled');
				BX('lwPopup-task-control').disabled = true;
			}
			else
			{
				BX('lwPopup-task-control').disabled = false;
				BX.removeClass(BX('lwPopup-task-control').parentNode, 'webform-field-checkbox-option-disabled');

				// use default value
				if (BX.message('TASKS_META_OPTION_TASK_CONTROL') === 'Y')
					BX('lwPopup-task-control').checked = true;
				else
					BX('lwPopup-task-control').checked = false;
			}

			// Show warning if responsible person not in selected group or is absence
			this.__checkWarnings();
		};


		this._onFilesUploaded = function(files, uniqueID)
		{
			for(i = 0; i < files.length; i++)
			{
				var elem = BX("file-" + i + '-' + uniqueID);
				if (files[i].fileID)
				{
					BX.removeClass(elem, "uploading");
					BX.adjust(elem.firstChild, {props : {href : files[i].fileULR}});
					BX.unbindAll(elem.firstChild);
					BX.unbindAll(elem.lastChild);
					BX.bind(elem.lastChild, "click", BX.Tasks.lwPopup.createForm.objTemplate._deleteFile);
					elem.appendChild(BX.create("input", {
						props : {
							type : "hidden",
							name : "FILES[]",
							value : files[i].fileID
						}
					}));
				}
				else
				{
					BX.cleanNode(elem, true);
				}
			}
			BX.cleanNode(BX("iframe-" + uniqueID), true);
		};


		this._deleteFile = function (e)
		{
			if(!e) e = window.event;
			
			if (confirm(BX.message("TASKS_DELETE_CONFIRM"))) {
				if (!BX.hasClass(this.parentNode, "saved"))
				{
					var data = {
						fileID : this.nextSibling.value,
						sessid : BX.message("bitrix_sessid"),
						mode : "delete"
					}
					var url = "/bitrix/components/bitrix/tasks.task.edit/upload.php";
					BX.ajax.post(url, data);
				}
				BX.remove(this.parentNode);
			}

			BX.PreventDefault(e);
		};


		this._onFilesChange = function()
		{
			var files = [];

			if (this.files && this.files.length > 0) {
				files = this.files;
			} else {
				var filePath = this.value;
				var fileTitle = filePath.replace(/.*\\(.*)/, "$1");
				fileTitle = fileTitle.replace(/.*\/(.*)/, "$1");
				files = [
					{fileName : fileTitle}
				];
			}
			
			var uniqueID;
			
			do
			{
				uniqueID = Math.floor(Math.random() * 99999);
			}
			while(BX("iframe-" + uniqueID));
			
			var list = BX("webform-field-upload-list");
			var items = [];
			var filenameShort = '';
			for (var i = 0; i < files.length; i++) {
				if (!files[i].fileName && files[i].name) {
					files[i].fileName = files[i].name;
				}

				filenameShort = files[i].fileName;

				if (filenameShort.length >= 95)
					filenameShort = filenameShort.substr(0, 91) + '...';

				var li = BX.create("li", {
					props : {className : "uploading",  id : "file-" + i + '-' + uniqueID},
					children : [
						BX.create("a", { 
							props : {href : "", target : "_blank", className : "upload-file-name", title: files[i].fileName},
							text : filenameShort,
							events : {click : function(e) {
								BX.PreventDefault(e);
							}}
						}),
						BX.create("i", { }),
						BX.create("a", {
							props : {href : "", className : "delete-file"},
							events : {click : function(e) {
								BX.PreventDefault(e);
							}}
						})
					]
				});
				
				list.appendChild(li);
				items.push(li);
			}
			
			var iframeName = "iframe-" + uniqueID;
			var iframe = BX.create("iframe", {
				props : {name : iframeName, id : iframeName},
				style : {display : "none"}
			});
			document.body.appendChild(iframe);

			var originalParent = this.parentNode;
			var form = BX.create("form", {
				props : {
					method : "post",
					action : "/bitrix/components/bitrix/tasks.task.edit/upload.php",
					enctype : "multipart/form-data",
					encoding : "multipart/form-data",
					target : iframeName
				},
				style : {display : "none"},
				children : [
					this,
					BX.create("input", {
						props : {
							type : "hidden",
							name : "sessid",
							value : BX.message("bitrix_sessid")
						}
					}),
					BX.create('input', {
						props : {
							type  : 'hidden',
							name  : 'callbackFunctionName',
							value : 'window.parent.window.BX.Tasks.lwPopup.createForm.objTemplate._onFilesUploaded'
						}
					}),
					BX.create("input", {
						props : {
							type : "hidden",
							name : "uniqueID",
							value : uniqueID
						}
					}),
					BX.create("input", {
						props : {
							type : "hidden",
							name : "mode",
							value : "upload"
						}
					})
				]
			});
			document.body.appendChild(form);
			BX.submit(form);

			// This is workaround due to changes in main//core.js since main 11.5.9
			// http://jabber.bx/view.php?id=29990
			window.setTimeout(
				BX.delegate(
					function()
					{
						originalParent.appendChild(this);
						BX.cleanNode(form, true);
					}, 
					this
				),
				15
			);
		};


		this.__onWebdavFieldRedraw = function(fieldLabel, containerId)
		{
			var bShow = true;
			var bHideTitle = true;

			this.__redrawUserField(fieldLabel, containerId, bShow, bHideTitle);
		};
		

		this.__onCrmFieldRedraw = function(fieldLabel, containerId)
		{
			var bShow = showCrmField;
			var bHideTitle = false;

			this.__redrawUserField(fieldLabel, containerId, bShow, bHideTitle);
		};


		this.__redrawUserField = function(fieldLabel, containerId, bShow, bHideTitle)
		{
			var targetNode    = null;
			var userFieldNode = null;

			var nodeId = 'lwPopup-task-UF_USER_FIELDS' + containerId;

			if (BX(nodeId))
				BX.remove(BX(nodeId));

			var workFields = [];
			if ( ! bHideTitle )
			{
				workFields.push(BX.create(
					'td',
					{
						props : { className : 'task-property-name' },
						html  : BX.util.htmlspecialchars(fieldLabel)
					}
				));
			}

			workFields.push(targetNode = BX.create(
				'td',
				{
					props : { className : 'task-property-value' },
					html  : ''
				}
			));

			BX('lwPopup-task-grey-area').appendChild(
				userFieldNode = BX.create(
					'div',
					{
						props : {
							id        :  nodeId,
							className : 'webform-row task-additional-properties-row'
						},
						children: [
							BX.create(
								'div',
								{
									html : '&nbsp;'
								}
							),
							BX.create(
								'table',
								{
									attrs : { cellspacing : '0' },
									style : { width: "100%" },
									children : [
										BX.create(
											'tr',
											{
												children: workFields
											}
										)
									]
								}
							)
						]
					}
				)
			);

			if ( ! bShow )
				userFieldNode.style.display = 'none';
			else
				userFieldNode.style.display = 'block';

			targetNode.appendChild(BX(containerId));
		};


		this.__cleanErrorsArea = function()
		{
			BX('lwPopup-task-errorsArea').style.display = 'none';
			BX('lwPopup-task-errorsArea-list').innerHTML = '';
		};


		this.__fillErrorsArea = function(errorMessages)
		{
			var errsCount = 0;
			var i = 0;

			BX('lwPopup-task-errorsArea-list').innerHTML = '';

			errsCount = errorMessages.length;
			
			for (i = 0; i < errsCount; i++)
			{
				BX('lwPopup-task-errorsArea-list').appendChild(
					BX.create(
						'li',
						{
							html : BX.util.htmlspecialchars(errorMessages[i])
						}
					)
				);
			}

			BX('lwPopup-task-errorsArea').style.display = 'block';
		};


		this.__checkWarnings = function()
		{
			var oSelf = this;

			if (this.checkWarningsId)
				window.clearTimeout(this.checkWarningsId);

			this.checkWarningsId = window.setTimeout(
				function()
				{
					if ( ! BX('task-edit-warnings-area') )
						return;

					var taskData = createForm.objTemplate.gatherTaskDataFromForm();

					var data = {
						sessid : BX.message('bitrix_sessid'),
						TASK   : {
							RESPONSIBLE_ID : taskData.RESPONSIBLE_ID,
							GROUP_ID       : taskData.GROUP_ID
						},
						action : 'getWarnings'
					};

					var oAjaxReply = BX.ajax({
						method   : 'POST',
						dataType : 'html',
						url      : '/bitrix/components/bitrix/tasks.task.edit/ajax.php',
						data     :  data,
						async    :  false
					});

					
					if (oAjaxReply.responseText.length)
					{
						BX('task-edit-warnings-area-message').innerHTML = oAjaxReply.responseText;
						BX('task-edit-warnings-area').style.display = 'block';
					}
					else
					{
						// clear warnings
						BX('task-edit-warnings-area').style.display = 'none';
						BX('task-edit-warnings-area-message').innerHTML = '';
					}
				}
			, 250);
		};
	};
};
