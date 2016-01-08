
; /* Start:"a:4:{s:4:"full";s:93:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/script.min.js?145227747833999";s:6:"source";s:73:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/script.js";s:3:"min";s:77:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/script.min.js";s:3:"map";s:77:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/script.map.js";}"*/
if(!BX.Tasks)BX.Tasks={};if(!BX.Tasks.componentIframe)BX.Tasks.componentIframe={};if(!BX.Tasks.componentIframe.objTemplate){BX.Tasks.componentIframe.objTemplate=function(e){var s=BX.Tasks.lwPopup.createForm;var t="task-responsible-employee";var a=null;var l=null;var o=null;var r=null;var n=null;var p=null;var c=null;var d=false;var u=false;this.buttonsLocked=false;this.initialTaskData=null;this.html=null;var k=function(e,s){var i="";var p="";var d=1;var k="";var m=[];var f=0;var b="...";var v=false;var _=[];var w="Y";var B="N";var h=0;var T=0;var X=0;if(BX.message("TASKS_META_OPTION_TASK_CONTROL")==="Y")w="Y";else w="N";if(BX.message("TASKS_META_OPTION_TIME_TRACKING")==="Y")B="Y";else B="N";if(e){c=e;if(e.TITLE)i=e.TITLE;if(e.DESCRIPTION)p=e.DESCRIPTION;if(e.PRIORITY)d=e.PRIORITY;if(e.DEADLINE)k=e.DEADLINE;if(e.ACCOMPLICES)m=e.ACCOMPLICES;if(e.GROUP_ID){v=true;f=e.GROUP_ID;if(e["META:GROUP_NAME"]){b=e["META:GROUP_NAME"];v=false}}if(e.UF_CRM_TASK){_=e.UF_CRM_TASK;u=true}if(e.ALLOW_TIME_TRACKING)B=e.ALLOW_TIME_TRACKING;if(e.TASK_CONTROL)w=e.TASK_CONTROL;if(e.TIME_ESTIMATE){h=e.TIME_ESTIMATE;T=Math.floor(h/3600);X=Math.round((h-T*3600)/60)}}BX.cleanNode(BX("webform-field-upload-list"));BX("task-upload").files=[];var S=e.RESPONSIBLE_ID;var E=false;BX("lwPopup-task-title").value=i;BX("lwPopup-task-responsible-id").value=S;BX("lwPopup-task-accomplices-id").value=m.join(",");BX.cleanNode(BX("task-accomplices-list"));if(S==BX.Tasks.lwPopup.loggedInUserId){w="N";BX("lwPopup-task-control").checked=false;BX.addClass(BX("lwPopup-task-control").parentNode,"webform-field-checkbox-option-disabled");BX("lwPopup-task-control").disabled=true}else{BX.removeClass(BX("lwPopup-task-control").parentNode,"webform-field-checkbox-option-disabled");BX("lwPopup-task-control").disabled=false;if(w==="Y")BX("lwPopup-task-control").checked=true;else BX("lwPopup-task-control").checked=false}if(B==="Y"){BX.addClass(BX("lwPopup-task-time-tracking-container"),"task-edit-allowed-time-tracking");BX("lwPopup-task-allow-time-tracking").checked=true}else{BX.removeClass(BX("lwPopup-task-time-tracking-container"),"task-edit-allowed-time-tracking");BX("lwPopup-task-allow-time-tracking").checked=false}if(T==0&&X==0){BX("lwPopup-task-time-tracking-hours").value="";BX("lwPopup-task-time-tracking-minutes").value=""}else{BX("lwPopup-task-time-tracking-hours").value=T;if(X>=10)BX("lwPopup-task-time-tracking-minutes").value=X;else BX("lwPopup-task-time-tracking-minutes").value="0"+X.toString()}r.setContent(p);n.setValue(_);this._togglePriority(d);this._setDeadline(k);o.setSelected({id:f,title:b});this._onGroupSelect([{id:f,title:b}]);if(v){BX.CJSTask.getGroupsData([f],{callback:function(e,s){return function(t){var a=t[e]["NAME"];o.setSelected({id:e,title:a});s._onGroupSelect([{id:e,title:a}])}}(f,this)})}var P=false;if(e["META:RESPONSIBLE_FORMATTED_NAME"])E=e["META:RESPONSIBLE_FORMATTED_NAME"];else if(e.RESPONSIBLE_LAST_NAME&&e.RESPONSIBLE_NAME)E=e.RESPONSIBLE_NAME+" "+e.RESPONSIBLE_LAST_NAME;else{var P=true;E="..."}BX(t).value=E;a.setSelectedUsers([{id:S,name:E}]);var g=[];for(var A=0;A<m.length;A++){g.push({id:m[A],name:"..."})}l.setSelectedUsers(g);this._onAccomplicesSelect(g);if(P||m.length>0){var N=[];N.push.apply(N,m);if(P)N.push(S);BX.CJSTask.formatUsersNames(N,{callback:function(e,s,i,o){return function(r){if(e){BX(t).value=r["u"+s];a.setSelectedUsers([{id:s,name:r["u"+s]}])}var n=[];for(var p=0;p<i.length;p++){n.push({id:i[p],name:r["u"+i[p]]})}l.setSelectedUsers(n);o._onAccomplicesSelect(n)}}(P,S,m.slice(),this)})}};s.callbacks.onAfterPopupCreated=function(e){var s="Shift+Enter";var i="Ctrl+Enter";var c=[];if(e.hasOwnProperty("UF_CRM_TASK"))c.push.apply(c,e.UF_CRM_TASK);var d=[];if(e.hasOwnProperty("UF_TASK_WEBDAV_FILES"))d.push.apply(d,e.UF_TASK_WEBDAV_FILES);var u=[];if(e.hasOwnProperty("ACCOMPLICES"))u.push.apply(u,e.ACCOMPLICES);var k=BX.Tasks.lwPopup.__initSelectors([{requestedObject:"intranet.user.selector.new",selectedUsersIds:[e.RESPONSIBLE_ID],anchorId:t,bindClickTo:BX(t).parentNode,userInputId:t,multiple:"N",callbackOnSelect:function(e){return function(s){e._onResponsibleSelect(s)}}(this)},{requestedObject:"intranet.user.selector.new",selectedUsersIds:u,anchorId:"task-accomplices-link",multiple:"Y",btnSelectText:BX.message("TASKS_BTN_SELECT"),btnCancelText:BX.message("TASKS_BTN_CANCEL"),callbackOnSelect:function(e){return function(s){e._onAccomplicesSelect(s)}}(this)},{requestedObject:"socialnetwork.group.selector",bindElement:"task-sonet-group-selector",callbackOnSelect:function(e){return function(s,t){e._onGroupSelect(s,t)}}(this)},{requestedObject:"LHEditor",attachTo:"lwPopup-task-description-area"},{requestedObject:"system.field.edit::CRM",userFieldName:"UF_CRM_TASK",taskId:0,value:c,callbackOnRedraw:function(e){return function(s,t){e.__onCrmFieldRedraw(s,t)}}(this)},{requestedObject:"system.field.edit::WEBDAV",userFieldName:"UF_TASK_WEBDAV_FILES",taskId:0,value:d,callbackOnRedraw:function(e){return function(s,t){e.__onWebdavFieldRedraw(s,t)}}(this)}]);a=k[0];l=k[1];o=k[2];r=k[3];n=k[4];p=k[5];if(BX.message("TASKS_META_OPTION_OPENED_DESCRIPTION")==="Y"){BX.removeClass("lwPopup-task-description-label-icon","task-description-label-icon-right");BX.addClass("lwPopup-task-description-label-icon","task-description-label-icon-bottom");BX("lwPopup-task-description-area-container").style.display="block"}else{BX.removeClass("lwPopup-task-description-label-icon","task-description-label-icon-bottom");BX.addClass("lwPopup-task-description-label-icon","task-description-label-icon-right");BX("lwPopup-task-description-area-container").style.display="none"}if(BX.browser.IsMac()){var m=document.createElement("div");m.innerHTML="&#8984;";var f=m.childNodes.length===0?"":m.childNodes[0].nodeValue;i=f+"+Enter"}BX("task-submit-button-text").innerHTML=BX.message("TASKS_BTN_CREATE_TASK")+" ("+i+")";BX("task-submit-and-create-new-when-back-to-form-button-text").innerHTML=BX.message("TASKS_BTN_CREATE_TASK_AND_ONCE_MORE")+" ("+s+")";BX("task-cancel-button-text").innerHTML=BX.message("TASKS_BTN_CANCEL")};s.callbacks.onBeforePopupShow=function(e,s){var t=[];var a=false;s=s||{};if(s.hasOwnProperty(a))a=s.isPopupJustCreated;this.initialTaskData=JSON.parse(JSON.stringify(e));k.call(this,e,a);this.__checkWarnings();if(!a)this.__cleanErrorsArea();if(BX.browser.IsSafari())BX("task-upload").multiple=false;if(p)t=p.getValue();if(t.length>0&&(!e.hasOwnProperty("UF_TASK_WEBDAV_FILES")||typeof e.UF_TASK_WEBDAV_FILES!=="object"||!e.UF_TASK_WEBDAV_FILES.hasOwnProperty("length")||e.UF_TASK_WEBDAV_FILES.length==0)){p.setValue([0])}};s.callbacks.onAfterPopupShow=function(){if(d){if((BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE())&&typeof r!="undefined"&&typeof r["editor"]!="undefined"){r["editor"].Focus(false)}BX("lwPopup-task-title").focus()}BX.bind(document,"keydown",BX.Tasks.lwPopup.createForm.objTemplate._processKeyDown)};s.callbacks.onPopupClose=function(){BX.unbind(document,"keydown",BX.Tasks.lwPopup.createForm.objTemplate._processKeyDown)};s.callbacks.onAfterEditorInited=function(){if((BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE())&&typeof r!="undefined"&&typeof r["editor"]!="undefined"&&"Focus"in r["editor"]){r["editor"].Focus(false)}BX("lwPopup-task-title").focus();d=true};this.prepareTitleBar=function(){var e='<span class="task-detail-popup-title">'+BX.message("TASKS_TIT_CREATE_TASK_2")+"</span>"+'<span class="task-detail-popup-btn" onclick="BX.Tasks.lwPopup.createForm.objTemplate._runFullEditForm();">'+BX.message("TASKS_LINK_SHOW_FULL_CREATE_FORM")+"</span>";return{content:BX.create("span",{html:e})}};this._processKeyDown=function(e){if(e.keyCode==27){bClose=true;var t=s.objTemplate.gatherTaskDataFromForm();if(t.hasOwnProperty("TITLE")&&t.TITLE.length||t.hasOwnProperty("DESCRIPTION")&&t.DESCRIPTION.length){bClose=confirm(BX.message("TASKS_CONFIRM_CLOSE_CREATE_DIALOG"))}if(bClose)s.objPopup.close()}var a=e.keyCode==10||e.keyCode==13;if(!a)return;if(e.ctrlKey||e.metaKey)s.objTemplate._submitAndClosePopup();else if(e.shiftKey)s.objTemplate._submitAndCreateOnceMore()};this._runFullEditForm=function(){var e=BX.Tasks.lwPopup.createForm.objTemplate.gatherTaskDataFromForm();if(e.hasOwnProperty("ACCOMPLICES")){e.ACCOMPLICES_IDS=e.ACCOMPLICES.slice();delete e.ACCOMPLICES}if(e.hasOwnProperty("RESPONSIBLE_SECOND_NAME"))delete e.RESPONSIBLE_SECOND_NAME;if(e.hasOwnProperty("RESPONSIBLE_LAST_NAME"))delete e.RESPONSIBLE_LAST_NAME;if(e.hasOwnProperty("RESPONSIBLE_NAME"))delete e.RESPONSIBLE_NAME;if(e.hasOwnProperty("META:RESPONSIBLE_FORMATTED_NAME"))delete e["META:RESPONSIBLE_FORMATTED_NAME"];if(e.hasOwnProperty("META:GROUP_NAME"))delete e["META:GROUP_NAME"];taskIFramePopup.add(e);BX.Tasks.lwPopup.createForm.objPopup.close()};this.prepareContent=function(){if(this.html==null){this.html='<div class="webform task-webform">					<div id="lwPopup-task-errorsArea" class="webform-round-corners webform-error-block" style="display: none;">						<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>						<div class="webform-content">							<ul id="lwPopup-task-errorsArea-list" class="webform-error-list">							</ul>						</div>						<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>					</div>					<div class="webform-round-corners webform-main-fields task-main-fields">						<div class="webform-corners-top">							<div class="webform-left-corner"></div>							<div class="webform-right-corner"></div>						</div>						<div class="webform-content">							<div class="webform-row task-title-row">								<div class="webform-field-label"><label for="task-title">									'+BX.message("TASKS_TITLE")+'								</label></div>								<div class="webform-field webform-field-textbox-double task-title">									<div class="webform-field-textbox-inner"										><input type="text" name="TITLE" id="lwPopup-task-title" 											placeholder="'+BX.message("TASKS_TITLE_PLACEHOLDER")+'"											style="height:23px;" class="webform-field-textbox"											value=""									/></div>								</div>							</div>														<div class="webform-row task-quick-responsible-employee-row">								<table cellspacing="0" class="task-responsible-employee-layout">									<tr>										<td class="task-responsible-employee-layout-left">											<div class="webform-field-label"												><label for="task-responsible-employee" 													id="task-responsible-employee-label">														'+BX.message("TASKS_RESPONSIBLE")+'												</label></div>																						<div class="webform-field webform-field-combobox task-responsible-employee" 												id="task-responsible-employee-block">												<div class="webform-field-combobox-inner">													<input type="text" autocomplete="off" id="task-responsible-employee" 														class="webform-field-combobox" value="" 													/><a href="javascript:void(0);" class="webform-field-combobox-arrow">&nbsp;</a>													<input type="hidden" id="lwPopup-task-responsible-id" value="" />												</div>											</div>																						<div class="webform-field task-quick-assistants" id="task-accomplices-block">												<div class="task-assistants-label"													><a href="javascript:void(0);" class="task-quick-assistants-link" 														id="task-accomplices-link"														onclick="BX.Tasks.lwPopup.createForm.objTemplate._showAccomplicesSelector(event);"														>														'+BX.message("TASKS_TASK_ACCOMPLICES")+'												</a></div>												<div class="task-assistants-list" id="task-accomplices-list">												</div>												<input type="hidden" id="lwPopup-task-accomplices-id" value="" />											</div>										</td>										<td class="task-responsible-employee-layout-right">											<div class="webform-field task-priority" id="task-priority">												<label>'+BX.message("TASKS_PRIORITY")+':</label>													<a href="javascript:void(0);" class="task-priority-low"														id="lwPopup-task-priority-0" 														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(0);"														><i></i><span>														'+BX.message("TASKS_PRIORITY_LOW")+'													</span><b></b></a>													<a href="javascript:void(0);" class="task-priority-middle"														id="lwPopup-task-priority-1" 														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(1);"														><i></i><span>														'+BX.message("TASKS_PRIORITY_NORMAL")+'													</span><b></b></a>													<a href="javascript:void(0);" class="task-priority-high"														id="lwPopup-task-priority-2" 														onclick="BX.Tasks.lwPopup.createForm.objTemplate._togglePriority(2);"														><i></i><span>														'+BX.message("TASKS_PRIORITY_HIGH")+'													</span><b></b></a>												<input type="hidden" id="lwPopup-task-priority" value="" />											</div>										</td>									</tr>								</table>							</div>														<div class="webform-row task-quick-dates-row">								<div class="webform-field">									<div class="webform-field task-quick-deadline-settings"										><label for="task-deadline-date">											'+BX.message("TASKS_DEADLINE")+':</label										>&nbsp;&nbsp;<span style="display:inline; line-height:20px;" id="task-quick-detail-deadline"											onclick="												BX.Tasks.lwPopup._showCalendar(													BX(\'task-quick-detail-deadline\'),													BX(\'lwPopup-task-deadline\'),													{														callback_after : function(value) {															BX.Tasks.lwPopup.createForm.objTemplate._setDeadline(BX(\'lwPopup-task-deadline\').value);														}													}												);												"											class="webform-field-action-link">												'+BX.message("TASKS_THERE_IS_NO_DEADLINE")+'											</span									><input type="text" value="" id="lwPopup-task-deadline" data-default-time="'+BX.message("TASKS_COMPANY_WORKTIME")+'" 										style="display:none;"><span id="task-detail-deadline-remove"										onclick="BX.Tasks.lwPopup.createForm.objTemplate._clearDeadline();" 										class="task-deadline-delete"										style="display:none;"></span>									</div>								</div>							</div>							<div class="webform-row task-options-row">								<div class="webform-field webform-field-checkbox-options task-options">									<div class="webform-field-checkbox-option" style="margin-top: 13px;"><input type="checkbox" value="Y" 										onclick="											if (this.checked)												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'Y\');											else												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'N\');"										onchange="											if (this.checked)												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'Y\');											else												BX.userOptions.save(\'tasks\', \'popup_options\', \'task_control\', \'N\');"										id="lwPopup-task-control" class="webform-field-checkbox"><label for="lwPopup-task-control">'+BX.message("TASKS_CONTROL_CHECKBOX")+"</label></div>									<div id=\"lwPopup-task-time-tracking-container\" class=\"webform-field-checkbox-option\" 										><input type=\"checkbox\" value=\"Y\" id=\"lwPopup-task-allow-time-tracking\" 											onclick=\"												if (this.checked)												{													BX.addClass(this.parentNode, 'task-edit-allowed-time-tracking');													BX.userOptions.save('tasks', 'popup_options', 'time_tracking', 'Y');												}												else												{													BX.removeClass(this.parentNode, 'task-edit-allowed-time-tracking');													BX.userOptions.save('tasks', 'popup_options', 'time_tracking', 'N');												}\"											onchange=\"												if (this.checked)												{													BX.addClass(this.parentNode, 'task-edit-allowed-time-tracking');													BX.userOptions.save('tasks', 'popup_options', 'time_tracking', 'Y');												}												else												{													BX.removeClass(this.parentNode, 'task-edit-allowed-time-tracking');													BX.userOptions.save('tasks', 'popup_options', 'time_tracking', 'N');												}\"											class=\"webform-field-checkbox\" /><label for=\"lwPopup-task-allow-time-tracking\" class=\"task-edit-allowed-time-tracking-hide\">"+BX.message("TASKS_TASK_ALLOW_TIME_TRACKING")+'</label><label for="lwPopup-task-allow-time-tracking" class="task-edit-allowed-time-tracking-show">'+BX.message("TASKS_TASK_ALLOW_TIME_TRACKING_DETAILS")+'</label>											<span style="display:inline;"><span style="display:inline-block;"><span style="height:18px; display:inline-block; border:1px;"></span></span></span>											<span class="task-edit-allowed-time-tracking-show">												<span class="webform-field webform-field-textbox task-time-tracking-hours"><span class="webform-field-textbox-inner"><input type="text" id="lwPopup-task-time-tracking-hours" value="" maxlength="4" class="webform-field-textbox task-time-tracking-hours-input"></span></span><span>'+BX.message("TASKS_TASK_TIME_TRACKING_HOURS")+'</span><span class="webform-field webform-field-textbox task-time-tracking-minutes"><span class="webform-field-textbox-inner"><input type="text" id="lwPopup-task-time-tracking-minutes" value="" maxlength="2" class="webform-field-textbox task-time-tracking-minutes-input"></span></span><span>'+BX.message("TASKS_TASK_TIME_TRACKING_MINUTES")+"</span><span class=\"task-deadline-delete\" style=\"\" onclick=\"BX('lwPopup-task-time-tracking-hours').value = ''; BX('lwPopup-task-time-tracking-minutes').value = ''; \"></span>											</span>									</div>								</div>							</div>						</div>					</div>										<div class=\"webform-round-corners webform-additional-fields\">						<div id=\"lwPopup-task-grey-area\" class=\"webform-content\">							<div class=\"webform-row task-description-row\">								<div class=\"webform-field-label task-description-label-container\"									><a href=\"javascript:void(0);\" class=\"task-description-label\"										onclick=\"										this.blur();										if (BX('lwPopup-task-description-area-container').style.display === 'none')										{											BX.removeClass('lwPopup-task-description-label-icon', 'task-description-label-icon-right');											BX.addClass('lwPopup-task-description-label-icon', 'task-description-label-icon-bottom');											BX('lwPopup-task-description-area-container').style.display = 'block';											BX.userOptions.save('tasks', 'popup_options', 'opened_description', 'Y');										}										else										{											BX.removeClass('lwPopup-task-description-label-icon', 'task-description-label-icon-bottom');											BX.addClass('lwPopup-task-description-label-icon', 'task-description-label-icon-right');											BX('lwPopup-task-description-area-container').style.display = 'none';											BX.userOptions.save('tasks', 'popup_options', 'opened_description', 'N');										}										\"										>"+BX.message("TASKS_DESCRIPTION")+'</a><span 									id="lwPopup-task-description-label-icon"									class="task-description-label-icon task-description-label-icon-right">&nbsp;</span></div>								<div class="webform-field webform-field-textarea task-description-textarea" id="lwPopup-task-description-area-container" style="display:none;">									<div class="webform-field-textarea-inner" id="lwPopup-task-description-area">										<textarea readonly="readonly"></textarea>									</div>								</div>							</div>														<div class="webform-row task-group-row">								<a href="javascript:void(0);" id="task-sonet-group-selector"									class="task-quick-popup-group-selector-link"									>'+BX.message("TASKS_GROUP")+'</a>							</div>														<div class="webform-row task-attachments-row" style="display:none;">								<div class="webform-field webform-field-attachments">									<ol class="webform-field-upload-list" id="webform-field-upload-list"></ol>									<div class="webform-field-upload">										<span class="webform-button webform-button-upload"											><span class="webform-button-left"></span											><span class="webform-button-text">'+BX.message("TASKS_UPLOAD_FILES")+'</span											><span class="webform-button-right"></span										></span>										<input type="file" name="task-attachments[]" size="1" 											multiple="multiple" id="task-upload"											onChange="BX.Tasks.lwPopup.createForm.objTemplate._onFilesChange.call(this, event);" />									</div>								</div>							</div>						</div>												<div class="webform-corners-bottom">							<div class="webform-left-corner"></div>							<div class="webform-right-corner"></div>						</div>					</div>										<div id="task-edit-warnings-area"						class="webform-round-corners webform-warning-block"						style="display: none; margin:10px 0;">						<div class="webform-corners-top">							<div class="webform-left-corner"></div>							<div class="webform-right-corner"></div>						</div>						<div class="webform-content">							<div id="task-edit-warnings-area-message"></div>						</div>						<div class="webform-corners-bottom">							<div class="webform-left-corner"></div>							<div class="webform-right-corner"></div>						</div>					</div>										<div class="webform-buttons task-buttons">						<a id="task-submit-button" class="webform-button webform-button-create" 							onclick="BX.Tasks.lwPopup.createForm.objTemplate._submitAndClosePopup();" 							href="javascript: void(0);"><span class="webform-button-left"></span							><span class="webform-button-text" 								id="task-submit-button-text"></span							><span class="webform-button-right"></span></a>						<a id="task-submit-and-create-new-when-back-to-form-button-text" 							href="javascript: void(0);"							class="webform-button-link task-button-create-link" 							onclick="BX.Tasks.lwPopup.createForm.objTemplate._submitAndCreateOnceMore();" 						></a>						<a class="webform-button-link webform-button-link-cancel" 							href="javascript:void(0);" 							id="task-cancel-button-text" 							onclick="BX.Tasks.lwPopup.createForm.objPopup.close();" 						></a>					</div>				</div>'}return BX.create("div",{props:{className:"task-quick-create-popup"},html:this.html})};this.gatherTaskDataFromForm=function(){var e=c;var s=[];var t=document.getElementsByName("FILES[]");var a=[];if(t){var i=t.length;for(var l=0;l<i;l++)a.push(t[l].value)}if(BX("lwPopup-task-accomplices-id").value.length>0)s=BX("lwPopup-task-accomplices-id").value.split(",");var o=0;if(BX("lwPopup-task-group-id"))o=BX("lwPopup-task-group-id").value;var d="N";var u=0;if(BX("lwPopup-task-allow-time-tracking").checked){d="Y";if(BX("lwPopup-task-time-tracking-hours")&&BX("lwPopup-task-time-tracking-minutes")){var k=parseInt(BX("lwPopup-task-time-tracking-hours").value);var m=parseInt(BX("lwPopup-task-time-tracking-minutes").value);if(isNaN(k))k=0;if(isNaN(m))m=0;u=k*3600+m*60}}if(BX("lwPopup-task-control").checked)taskControl="Y";else taskControl="N";e.TITLE=BX("lwPopup-task-title").value;e.DESCRIPTION=r.getContent();e.DEADLINE=BX("lwPopup-task-deadline").value;e.PRIORITY=BX("lwPopup-task-priority").value;e.RESPONSIBLE_ID=BX("lwPopup-task-responsible-id").value;e.ACCOMPLICES=s;e.FILES=a;e.GROUP_ID=o;e.TASK_CONTROL=taskControl;e.UF_CRM_TASK=n.getValue();e.UF_TASK_WEBDAV_FILES=p.getValue();e.ALLOW_TIME_TRACKING=d;var f=BX("diskuf-edit-rigths-doc");if(BX.type.isElementNode(f)&&"value"in f){e.DISK_ATTACHED_OBJECT_ALLOW_EDIT=f.value;e.TASKS_TASK_DISK_ATTACHED_OBJECT_ALLOW_EDIT=f.value}if(d==="Y")e.TIME_ESTIMATE=u;e["META:GROUP_NAME"]=null;e["META:RESPONSIBLE_FORMATTED_NAME"]=null;e.RESPONSIBLE_NAME=null;e.RESPONSIBLE_LAST_NAME=null;e.RESPONSIBLE_SECOND_NAME=null;if(!e.hasOwnProperty("ALLOW_CHANGE_DEADLINE"))e["ALLOW_CHANGE_DEADLINE"]="Y";return e};this.__lockButtons=function(){this.buttonsLocked=true};this.__releaseButtons=function(){this.buttonsLocked=false};this._submitAndClosePopup=function(){var e=null;if(this.buttonsLocked)return;this.__lockButtons();if(typeof tasksListNS!=="undefined"&&tasksListNS.getColumnsOrder)e=tasksListNS.getColumnsOrder();this.__cleanErrorsArea();var t=s.objTemplate.gatherTaskDataFromForm();BX.Tasks.lwPopup._createTask({taskData:t,onceMore:false,columnsIds:e,callbackOnSuccess:function(e){return function(){s.objPopup.close();e.__releaseButtons()}}(this),callbackOnFailure:function(e){return function(s){e.__fillErrorsArea(s.errMessages);e.__releaseButtons()}}(this)})};this._submitAndCreateOnceMore=function(){var e=null;if(this.buttonsLocked)return;this.__lockButtons();if(typeof tasksListNS!=="undefined"&&tasksListNS.getColumnsOrder)e=tasksListNS.getColumnsOrder();this.__cleanErrorsArea();var t=s.objTemplate.gatherTaskDataFromForm();BX.Tasks.lwPopup._createTask({taskData:t,onceMore:true,columnsIds:e,callbackOnSuccess:function(e){return function(){s.objPopup.close();e.__releaseButtons();e.initialTaskData.TITLE="";e.initialTaskData.DESCRIPTION="";e.initialTaskData.ACCOMPLICES=[];BX.Tasks.lwPopup.showCreateForm(e.initialTaskData)}}(this),callbackOnFailure:function(e){return function(s){e.__fillErrorsArea(s.errMessages);e.__releaseButtons()}}(this)})};this.prepareButtons=function(){return[]};this._togglePriority=function(e){BX.removeClass("lwPopup-task-priority-0","selected");BX.removeClass("lwPopup-task-priority-1","selected");BX.removeClass("lwPopup-task-priority-2","selected");BX("lwPopup-task-priority").value=e;BX.addClass("lwPopup-task-priority-"+e,"selected")};this._clearDeadline=function(){BX("task-detail-deadline-remove").style.display="none";BX("lwPopup-task-deadline").value="";var e=BX("task-quick-detail-deadline");BX.cleanNode(e);var s=document.createElement("span");s.innerHTML=BX.message("TASKS_THERE_IS_NO_DEADLINE");e.appendChild(s);e.className="webform-field-action-link"};this._setDeadline=function(e){if(e===null||e===false||e===""){this._clearDeadline();return}BX("lwPopup-task-deadline").value=e;var s=BX("task-quick-detail-deadline");s.innerHTML=e;s.className="task-detail-deadline webform-field-action-link";BX("task-detail-deadline-remove").style.display=""};this._onGroupSelect=function(e,s){if(!e[0])return;if(e[0]["id"]==0){this._clearGroup();return}BX.adjust(BX("task-sonet-group-selector"),{text:BX.message("TASKS_GROUP")+": "+e[0].title});var t=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"span",className:"task-group-delete"});if(t){BX.adjust(t,{events:{click:function(s){if(!s)s=window.event;BX.Tasks.lwPopup.createForm.objTemplate._clearGroup(e[0].id)}}})}else{BX("task-sonet-group-selector").parentNode.appendChild(BX.create("span",{props:{className:"task-group-delete"},events:{click:function(s){if(!s)s=window.event;BX.Tasks.lwPopup.createForm.objTemplate._clearGroup(e[0].id)}}}))}var a=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"input",className:"tasks-notclass-GROUP_ID"});if(a){BX.adjust(a,{props:{value:e[0].id}});var i=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"input",className:"tasks-notclass-GROUP_NAME"});BX.adjust(i,{props:{value:e[0].title}})}else{BX("task-sonet-group-selector").parentNode.appendChild(BX.create("input",{props:{id:"lwPopup-task-group-id",name:"GROUP_ID",className:"tasks-notclass-GROUP_ID",type:"hidden",value:e[0].id}}));BX("task-sonet-group-selector").parentNode.appendChild(BX.create("input",{props:{name:"GROUP_NAME",className:"tasks-notclass-GROUP_NAME",type:"hidden",value:e[0].title}}))}this.__checkWarnings()};this._clearGroup=function(e){this.__checkWarnings();BX.adjust(BX("task-sonet-group-selector"),{text:BX.message("TASKS_GROUP")});var s=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"span",className:"task-group-delete"});if(s){BX.cleanNode(s,true)}var t=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"input",className:"tasks-notclass-GROUP_ID"});if(t){t.value=0}var t=BX.findNextSibling(BX("task-sonet-group-selector"),{tag:"input",className:"tasks-notclass-GROUP_NAME"});if(t){t.value=""}if(e)o.deselect(e)};this._showAccomplicesSelector=function(e){l.showUserSelector(e)};this._onAccomplicesSelect=function(e){var s=[];BX.cleanNode(BX("task-accomplices-list"));var t=BX("task-accomplices-link");var a=e.length;for(i=0;i<a;i++){BX("task-accomplices-list").appendChild(BX.create("div",{props:{className:"task-assistant-item"},children:[BX.create("span",{props:{className:"task-assistant-link",href:BX.Tasks.lwPopup.pathToUser.replace("#user_id#",e[i].id),target:"_blank",title:e[i].name},text:e[i].name})]}));s.push(e[i].id)}if(s.length>0){if(t.innerHTML.substr(t.innerHTML.length-1)!=":")t.innerHTML=t.innerHTML+":"}else{if(t.innerHTML.substr(t.innerHTML.length-1)==":")t.innerHTML=t.innerHTML.substr(0,t.innerHTML.length-1)}BX("lwPopup-task-accomplices-id").value=s.join(",")};this._onResponsibleSelect=function(e){BX("lwPopup-task-responsible-id").value=e.id;if(e.id==BX.Tasks.lwPopup.loggedInUserId){BX("lwPopup-task-control").checked=false;BX.addClass(BX("lwPopup-task-control").parentNode,"webform-field-checkbox-option-disabled");BX("lwPopup-task-control").disabled=true}else{BX("lwPopup-task-control").disabled=false;BX.removeClass(BX("lwPopup-task-control").parentNode,"webform-field-checkbox-option-disabled");if(BX.message("TASKS_META_OPTION_TASK_CONTROL")==="Y")BX("lwPopup-task-control").checked=true;else BX("lwPopup-task-control").checked=false}this.__checkWarnings()};this._onFilesUploaded=function(e,s){for(i=0;i<e.length;i++){var t=BX("file-"+i+"-"+s);if(e[i].fileID){BX.removeClass(t,"uploading");BX.adjust(t.firstChild,{props:{href:e[i].fileULR}});BX.unbindAll(t.firstChild);BX.unbindAll(t.lastChild);BX.bind(t.lastChild,"click",BX.Tasks.lwPopup.createForm.objTemplate._deleteFile);t.appendChild(BX.create("input",{props:{type:"hidden",name:"FILES[]",value:e[i].fileID}}))}else{BX.cleanNode(t,true)}}BX.cleanNode(BX("iframe-"+s),true)};this._deleteFile=function(e){if(!e)e=window.event;if(confirm(BX.message("TASKS_DELETE_CONFIRM"))){if(!BX.hasClass(this.parentNode,"saved")){var s={fileID:this.nextSibling.value,sessid:BX.message("bitrix_sessid"),mode:"delete"};var t="/bitrix/components/bitrix/tasks.task.edit/upload.php";BX.ajax.post(t,s)}BX.remove(this.parentNode)}BX.PreventDefault(e)};this._onFilesChange=function(){var e=[];if(this.files&&this.files.length>0){e=this.files}else{var s=this.value;var t=s.replace(/.*\\(.*)/,"$1");t=t.replace(/.*\/(.*)/,"$1");e=[{fileName:t}]}var a;do{a=Math.floor(Math.random()*99999)}while(BX("iframe-"+a));var i=BX("webform-field-upload-list");var l=[];var o="";for(var r=0;r<e.length;r++){if(!e[r].fileName&&e[r].name){e[r].fileName=e[r].name}o=e[r].fileName;if(o.length>=95)o=o.substr(0,91)+"...";var n=BX.create("li",{props:{className:"uploading",id:"file-"+r+"-"+a},children:[BX.create("a",{props:{href:"",target:"_blank",className:"upload-file-name",title:e[r].fileName},text:o,events:{click:function(e){BX.PreventDefault(e)}}}),BX.create("i",{}),BX.create("a",{props:{href:"",className:"delete-file"},events:{click:function(e){BX.PreventDefault(e)}}})]});i.appendChild(n);l.push(n)}var p="iframe-"+a;var c=BX.create("iframe",{props:{name:p,id:p},style:{display:"none"}});document.body.appendChild(c);var d=this.parentNode;var u=BX.create("form",{props:{method:"post",action:"/bitrix/components/bitrix/tasks.task.edit/upload.php",enctype:"multipart/form-data",encoding:"multipart/form-data",target:p},style:{display:"none"},children:[this,BX.create("input",{props:{type:"hidden",name:"sessid",value:BX.message("bitrix_sessid")}}),BX.create("input",{props:{type:"hidden",name:"callbackFunctionName",value:"window.parent.window.BX.Tasks.lwPopup.createForm.objTemplate._onFilesUploaded"}}),BX.create("input",{props:{type:"hidden",name:"uniqueID",value:a}}),BX.create("input",{props:{type:"hidden",name:"mode",value:"upload"}})]});document.body.appendChild(u);BX.submit(u);window.setTimeout(BX.delegate(function(){d.appendChild(this);BX.cleanNode(u,true)},this),15)};this.__onWebdavFieldRedraw=function(e,s){var t=true;var a=true;this.__redrawUserField(e,s,t,a);

};this.__onCrmFieldRedraw=function(e,s){var t=u;var a=false;this.__redrawUserField(e,s,t,a)};this.__redrawUserField=function(e,s,t,a){var i=null;var l=null;var o="lwPopup-task-UF_USER_FIELDS"+s;if(BX(o))BX.remove(BX(o));var r=[];if(!a){r.push(BX.create("td",{props:{className:"task-property-name"},html:BX.util.htmlspecialchars(e)}))}r.push(i=BX.create("td",{props:{className:"task-property-value"},html:""}));BX("lwPopup-task-grey-area").appendChild(l=BX.create("div",{props:{id:o,className:"webform-row task-additional-properties-row"},children:[BX.create("div",{html:"&nbsp;"}),BX.create("table",{attrs:{cellspacing:"0"},style:{width:"100%"},children:[BX.create("tr",{children:r})]})]}));if(!t)l.style.display="none";else l.style.display="block";i.appendChild(BX(s))};this.__cleanErrorsArea=function(){BX("lwPopup-task-errorsArea").style.display="none";BX("lwPopup-task-errorsArea-list").innerHTML=""};this.__fillErrorsArea=function(e){var s=0;var t=0;BX("lwPopup-task-errorsArea-list").innerHTML="";s=e.length;for(t=0;t<s;t++){BX("lwPopup-task-errorsArea-list").appendChild(BX.create("li",{html:BX.util.htmlspecialchars(e[t])}))}BX("lwPopup-task-errorsArea").style.display="block"};this.__checkWarnings=function(){var e=this;if(this.checkWarningsId)window.clearTimeout(this.checkWarningsId);this.checkWarningsId=window.setTimeout(function(){if(!BX("task-edit-warnings-area"))return;var e=s.objTemplate.gatherTaskDataFromForm();var t={sessid:BX.message("bitrix_sessid"),TASK:{RESPONSIBLE_ID:e.RESPONSIBLE_ID,GROUP_ID:e.GROUP_ID},action:"getWarnings"};var a=BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.task.edit/ajax.php",data:t,async:false});if(a.responseText.length){BX("task-edit-warnings-area-message").innerHTML=a.responseText;BX("task-edit-warnings-area").style.display="block"}else{BX("task-edit-warnings-area").style.display="none";BX("task-edit-warnings-area-message").innerHTML=""}},250)}}}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:67:"/bitrix/components/bitrix/search.title/script.min.js?14522774686196";s:6:"source";s:48:"/bitrix/components/bitrix/search.title/script.js";s:3:"min";s:52:"/bitrix/components/bitrix/search.title/script.min.js";s:3:"map";s:52:"/bitrix/components/bitrix/search.title/script.map.js";}"*/
function JCTitleSearch(t){var e=this;this.arParams={AJAX_PAGE:t.AJAX_PAGE,CONTAINER_ID:t.CONTAINER_ID,INPUT_ID:t.INPUT_ID,MIN_QUERY_LEN:parseInt(t.MIN_QUERY_LEN)};if(t.WAIT_IMAGE)this.arParams.WAIT_IMAGE=t.WAIT_IMAGE;if(t.MIN_QUERY_LEN<=0)t.MIN_QUERY_LEN=1;this.cache=[];this.cache_key=null;this.startText="";this.running=false;this.currentRow=-1;this.RESULT=null;this.CONTAINER=null;this.INPUT=null;this.WAIT=null;this.ShowResult=function(t){if(BX.type.isString(t)){e.RESULT.innerHTML=t}e.RESULT.style.display=e.RESULT.innerHTML!==""?"block":"none";var s=e.adjustResultNode();var i;var r;var n=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(n){r=BX.findChild(n,{tag:"th"},true)}if(r){var a=BX.pos(n);a.width=a.right-a.left;var l=BX.pos(r);l.width=l.right-l.left;r.style.width=l.width+"px";e.RESULT.style.width=s.width+l.width+"px";e.RESULT.style.left=s.left-l.width-1+"px";if(a.width-l.width>s.width)e.RESULT.style.width=s.width+l.width-1+"px";a=BX.pos(n);i=BX.pos(e.RESULT);if(i.right>a.right){e.RESULT.style.width=a.right-a.left+"px"}}var o;if(n)o=BX.findChild(e.RESULT,{"class":"title-search-fader"},true);if(o&&r){i=BX.pos(e.RESULT);o.style.left=i.right-i.left-18+"px";o.style.width=18+"px";o.style.top=0+"px";o.style.height=i.bottom-i.top+"px";o.style.display="block"}};this.onKeyPress=function(t){var s=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(!s)return false;var i;var r=s.rows.length;switch(t){case 27:e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll();return true;case 40:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var n=-1;for(i=0;i<r;i++){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){if(n==-1)n=i;if(e.currentRow<i){e.currentRow=i;break}else if(s.rows[i].className=="title-search-selected"){s.rows[i].className=""}}}if(i==r&&e.currentRow!=i)e.currentRow=n;s.rows[e.currentRow].className="title-search-selected";return true;case 38:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var a=-1;for(i=r-1;i>=0;i--){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){if(a==-1)a=i;if(e.currentRow>i){e.currentRow=i;break}else if(s.rows[i].className=="title-search-selected"){s.rows[i].className=""}}}if(i<0&&e.currentRow!=i)e.currentRow=a;s.rows[e.currentRow].className="title-search-selected";return true;case 13:if(e.RESULT.style.display=="block"){for(i=0;i<r;i++){if(e.currentRow==i){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){var l=BX.findChild(s.rows[i],{tag:"a"},true);if(l){window.location=l.href;return true}}}}}return false}return false};this.onTimeout=function(){e.onChange(function(){setTimeout(e.onTimeout,500)})};this.onChange=function(t){if(e.running)return;e.running=true;if(e.INPUT.value!=e.oldValue&&e.INPUT.value!=e.startText){e.oldValue=e.INPUT.value;if(e.INPUT.value.length>=e.arParams.MIN_QUERY_LEN){e.cache_key=e.arParams.INPUT_ID+"|"+e.INPUT.value;if(e.cache[e.cache_key]==null){if(e.WAIT){var s=BX.pos(e.INPUT);var i=s.bottom-s.top-2;e.WAIT.style.top=s.top+1+"px";e.WAIT.style.height=i+"px";e.WAIT.style.width=i+"px";e.WAIT.style.left=s.right-i+2+"px";e.WAIT.style.display="block"}BX.ajax.post(e.arParams.AJAX_PAGE,{ajax_call:"y",INPUT_ID:e.arParams.INPUT_ID,q:e.INPUT.value,l:e.arParams.MIN_QUERY_LEN},function(s){e.cache[e.cache_key]=s;e.ShowResult(s);e.currentRow=-1;e.EnableMouseEvents();if(e.WAIT)e.WAIT.style.display="none";if(!!t)t();e.running=false});return}else{e.ShowResult(e.cache[e.cache_key]);e.currentRow=-1;e.EnableMouseEvents()}}else{e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll()}}if(!!t)t();e.running=false};this.UnSelectAll=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var s=t.rows.length;for(var i=0;i<s;i++)t.rows[i].className=""}};this.EnableMouseEvents=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var s=t.rows.length;for(var i=0;i<s;i++)if(!BX.findChild(t.rows[i],{"class":"title-search-separator"},true)){t.rows[i].id="row_"+i;t.rows[i].onmouseover=function(t){if(e.currentRow!=this.id.substr(4)){e.UnSelectAll();this.className="title-search-selected";e.currentRow=this.id.substr(4)}};t.rows[i].onmouseout=function(t){this.className="";e.currentRow=-1}}}};this.onFocusLost=function(t){setTimeout(function(){e.RESULT.style.display="none"},250)};this.onFocusGain=function(){if(e.RESULT.innerHTML.length)e.ShowResult()};this.onKeyDown=function(t){if(!t)t=window.event;if(e.RESULT.style.display=="block"){if(e.onKeyPress(t.keyCode))return BX.PreventDefault(t)}};this.adjustResultNode=function(){var t;var s=BX.findParent(e.CONTAINER,BX.is_fixed);if(!!s){e.RESULT.style.position="fixed";e.RESULT.style.zIndex=BX.style(s,"z-index")+2;t=BX.pos(e.CONTAINER,true)}else{e.RESULT.style.position="absolute";t=BX.pos(e.CONTAINER)}t.width=t.right-t.left;e.RESULT.style.top=t.bottom+2+"px";e.RESULT.style.left=t.left+"px";e.RESULT.style.width=t.width+"px";return t};this._onContainerLayoutChange=function(){if(e.RESULT.style.display!=="none"&&e.RESULT.innerHTML!==""){e.adjustResultNode()}};this.Init=function(){this.CONTAINER=document.getElementById(this.arParams.CONTAINER_ID);BX.addCustomEvent(this.CONTAINER,"OnNodeLayoutChange",this._onContainerLayoutChange);this.RESULT=document.body.appendChild(document.createElement("DIV"));this.RESULT.className="title-search-result";this.INPUT=document.getElementById(this.arParams.INPUT_ID);this.startText=this.oldValue=this.INPUT.value;BX.bind(this.INPUT,"focus",function(){e.onFocusGain()});BX.bind(this.INPUT,"blur",function(){e.onFocusLost()});if(BX.browser.IsSafari()||BX.browser.IsIE())this.INPUT.onkeydown=this.onKeyDown;else this.INPUT.onkeypress=this.onKeyDown;if(this.arParams.WAIT_IMAGE){this.WAIT=document.body.appendChild(document.createElement("DIV"));this.WAIT.style.backgroundImage="url('"+this.arParams.WAIT_IMAGE+"')";if(!BX.browser.IsIE())this.WAIT.style.backgroundRepeat="none";this.WAIT.style.display="none";this.WAIT.style.position="absolute";this.WAIT.style.zIndex="1100"}BX.bind(this.INPUT,"bxchange",function(){e.onChange()})};BX.ready(function(){e.Init(t)})}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:99:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js?14522775326483";s:6:"source";s:80:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.js";s:3:"min";s:84:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js";s:3:"map";s:84:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.map.js";}"*/
BX.namespace("BX.Bitrix24.Helper");BX.Bitrix24.Helper={frameOpenUrl:"",frameCloseUrl:"",isOpen:false,frameNode:null,popupNodeWrap:null,curtainNode:null,popupNode:null,closeBtn:null,openBtn:null,popupLoader:null,topBar:null,topBarHtml:null,header:null,langId:null,reloadPath:null,init:function(e){this.frameOpenUrl=e.frameOpenUrl||"";this.frameCloseUrl=e.frameCloseUrl||"";this.helpUpBtnText=e.helpUpBtnText||"";this.langId=e.langId||"";this.openBtn=e.helpBtn;this.reloadPath=e.reloadPath||"";this.popupLoader=BX.create("div",{attrs:{className:"bx-help-popup-loader"},children:[BX.create("div",{attrs:{className:"bx-help-popup-loader-text"},text:BX.message("B24_HELP_LOADER")})]});this.topBarHtml='<div class="bx-help-menu-title" onclick="BX.Bitrix24.Helper.reloadFrame(\''+this.reloadPath+"')\">"+BX.message("B24_HELP_TITLE")+'<span class="bx-help-blue">24</span></div>';this.topBar=BX.create("div",{attrs:{className:"bx-help-nav-wrap"},html:this.topBarHtml});this.header=document.body.querySelector(".bx-layout-header");this.createFrame();this.createCloseBtn();this.createPopup();BX.bind(this.openBtn,"click",BX.proxy(this.show,this));BX.bind(window,"message",BX.proxy(function(e){e=e||window.event;if(e.data.height&&this.isOpen)this.frameNode.style.height=e.data.height+"px";this.insertTopBar(typeof e.data=="object"?e.data.title:e.data);this._showContent()},this))},createFrame:function(){this.frameNode=BX.create("iframe",{attrs:{className:"bx-help-frame",frameborder:0,name:"help",id:"help-frame"}});BX.bind(this.frameNode,"load",BX.proxy(function(){this.popupNode.scrollTop=0},this))},_showContent:function(){this.frameNode.style.opacity=1;if(this.topBar.classList){this.topBar.classList.add("bx-help-nav-fixed");this.topBar.classList.add("bx-help-nav-show")}else{BX.addClass(this.topBar,"bx-help-nav-fixed");BX.addClass(this.topBar,"bx-help-nav-show")}this.popupLoader.classList.remove("bx-help-popup-loader-show")},_setPosFixed:function(){document.body.style.width=document.body.offsetWidth+"px";document.body.style.overflow="hidden"},_clearPosFixed:function(){document.body.style.width="auto";document.body.style.overflow=""},createCloseBtn:function(){this.closeBtn=BX.create("div",{attrs:{className:"bx-help-close"},children:[BX.create("div",{attrs:{className:"bx-help-close-inner"}})]});BX.bind(this.closeBtn,"click",BX.proxy(this.closePopup,this))},insertTopBar:function(e){this.topBar.innerHTML=this.topBarHtml+e},createPopup:function(){this.curtainNode=BX.create("div",{attrs:{className:"bx-help-curtain"}});this.popupNode=BX.create("div",{children:[this.frameNode,this.topBar,this.popupLoader],attrs:{className:"bx-help-main"}});document.body.appendChild(this.curtainNode);document.body.appendChild(this.popupNode);document.body.appendChild(this.closeBtn)},closePopup:function(){clearTimeout(this.shadowTimer);clearTimeout(this.helpTimer);BX.unbind(this.popupNode,"transitionend",BX.proxy(this.loadFrame,this));BX.unbind(document,"keydown",BX.proxy(this._close,this));BX.unbind(document,"click",BX.proxy(this._close,this));if(this.popupNode.style.transition!==undefined)BX.bind(this.popupNode,"transitionend",BX.proxy(this._clearPosFixed,this));else this._clearPosFixed();this.popupNode.style.width=0;this.topBar.style.width=0;if(this.topBar.classList){this.topBar.classList.remove("bx-help-nav-fixed");this.closeBtn.classList.remove("bx-help-close-anim")}else{BX.removeClass(this.topBar,"bx-help-nav-fixed");BX.removeClass(this.closeBtn,"bx-help-close-anim")}this.topBar.style.top=this.getTopCord().top+"px";this.helpTimer=setTimeout(BX.proxy(function(){this.curtainNode.style.opacity=0;this.closeBtn.style.display="none";if(this.openBtn.classList)this.openBtn.classList.remove("help-block-active")},this),500);this.shadowTimer=setTimeout(BX.proxy(function(){this.frameNode.src=this.frameCloseUrl;this.popupNode.style.display="none";this.curtainNode.style.display="none";this.frameNode.style.opacity=0;this.frameNode.style.height=0;this.popupLoader.classList.remove("bx-help-popup-loader-show");BX.unbind(this.popupNode,"transitionend",BX.proxy(this._clearPosFixed,this));if(this.topBar.classList)this.topBar.classList.remove("bx-help-nav-show");else BX.removeClass(this.topBar,"bx-help-nav-show");this.isOpen=false},this),800)},show:function(e){if(typeof B24==="object")B24.goUp();if(typeof e==="string"){this.frameOpenUrl=this.frameOpenUrl+"&"+e}var t=this.getTopCord().top;var s=this.getTopCord().right;clearTimeout(this.shadowTimer);clearTimeout(this.helpTimer);this._setPosFixed();this.curtainNode.style.top=t+"px";this.curtainNode.style.width=this.getTopCord().right+"px";this.curtainNode.style.display="block";this.popupNode.style.display="block";this.popupNode.style.paddingTop=t+"px";this.topBar.style.top=t+"px";this.closeBtn.style.top=t-63+"px";this.closeBtn.style.left=s-63+"px";this.closeBtn.style.display="block";this.popupLoader.style.top=t+"px";if(this.openBtn.classList)this.openBtn.classList.add("help-block-active");if(this.popupNode.style.transition!==undefined){BX.bind(this.popupNode,"transitionend",BX.proxy(this.loadFrame,this))}else{this.loadFrame(null)}this.shadowTimer=setTimeout(BX.proxy(function(){this.curtainNode.style.opacity=1;if(this.closeBtn.classList)this.closeBtn.classList.add("bx-help-close-anim");else BX.addClass(this.closeBtn,"bx-help-close-anim")},this),25);this.helpTimer=setTimeout(BX.proxy(function(){this.popupNode.style.width=860+"px";this.topBar.style.width=860+"px";this.popupLoader.classList.add("bx-help-popup-loader-show");BX.bind(document,"keydown",BX.proxy(this._close,this));BX.bind(document,"click",BX.proxy(this._close,this));this.isOpen=true},this),300)},_close:function(e){e=e||window.event;var t=e.target||e.srcElement;if(e.type=="click"){BX.PreventDefault(e)}if(e.keyCode==27){this.closePopup()}while(t!=document.documentElement){if(t==this.popupNode||t==this.closeBtn||t==this.topBar){break}else if(t==document.body&&!e.keyCode){this.closePopup();break}t=t.parentNode}},loadFrame:function(e){if(e!==null){e=e||window.event;var t=e.target||e.srcElement;if(t==this.popupNode)this.frameNode.src=this.frameOpenUrl}else{this.frameNode.src=this.frameOpenUrl}},reloadFrame:function(e){this.frameNode.style.opacity=0;this.frameNode.src=e;if(this.topBar.classList)this.topBar.classList.remove("bx-help-nav-show");else BX.removeClass(this.topBar,"bx-help-nav-show");this.popupNode.scrollTop=0},getTopCord:function(){var e=BX.pos(this.header);return{top:e.bottom,right:e.right}}};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:102:"/bitrix/components/bitrix/socialnetwork.group_create.popup/templates/.default/script.js?14522774743527";s:6:"source";s:87:"/bitrix/components/bitrix/socialnetwork.group_create.popup/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(){

if (!!BX.SGCP)
{
	return;
}

BX.SGCP =
{
	bInit: {},
	popup: null,
	params: {},
	pathToCreate: {},
	pathToEdit: {},
	pathToInvite: {}
}

BX.SGCP.Init = function(obParams)
{
	if (obParams)
	{
		if (
			!obParams.NAME
			|| obParams.NAME.length <= 0
		)
		{
			return;
		}

		if (BX.SGCP.bInit[obParams.NAME])
		{
			return;
		}

		BX.SGCP.params[obParams.NAME] = obParams;

		BX.SGCP.pathToCreate[obParams.NAME] = (obParams.pathToCreate ? obParams.pathToCreate + (obParams.pathToCreate.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");
		BX.SGCP.pathToEdit[obParams.NAME] = (obParams.pathToEdit ? obParams.pathToEdit + (obParams.pathToEdit.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");
		BX.SGCP.pathToInvite[obParams.NAME] = (obParams.pathToInvite ? obParams.pathToInvite + (obParams.pathToInvite.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");

		BX.message(obParams['MESS']);

		BX.SGCP.bInit[obParams.NAME] = true;

		BX.addCustomEvent('onSonetIframeCancelClick', function() {
			BX.SGCP.destroyPopup();
		});

		BX.addCustomEvent('onSonetIframeSuccess', function() {
			BX.SGCP.destroyPopup();
		});
	}

	return;
}

BX.SGCP.ShowForm = function(action, popupName, event)
{
	if (
		typeof popupName === 'undefined'
		|| popupName.length <= 0
	)
	{
		return BX.PreventDefault(event);
	}

	if (BX.SGCP.popup)
	{
		BX.SGCP.popup.destroy();
	}

	var actionURL = null;
	var popupTitle = '';

	switch (action)
	{
		case 'create':
			actionURL = BX.SGCP.pathToCreate[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_CREATE_' + popupName);
			break;
		case 'edit':
			actionURL = BX.SGCP.pathToEdit[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_EDIT_' + popupName);
			break;
		case 'invite':
			actionURL = BX.SGCP.pathToInvite[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_INVITE_' + popupName);
			break;
		default:
			actionURL = null;
	}

	if (
		actionURL 
		&& actionURL.length > 0
	)
	{
		BX.SGCP.popup = new BX.PopupWindow("BXSGCP", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay: true,
			lightShadow: true,
			draggable: {
				restrict:true
			},
			closeByEsc: true,
			titleBar: {
				content: BX.create("span", {
					html: popupTitle
				})
			},
			closeIcon: { 
				right : "12px", 
				top : "10px"
			},
			buttons: [],
			content: '<div style="width:450px;height:230px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent('<div style="width:450px;height:230px">' + BX.message('SONET_SGCP_LOADING_' + popupName) + '</div>');

					BX.ajax.post(
						actionURL,
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: BX.SGCP.params[popupName]
						},
						BX.delegate(function(result)
							{
								this.setContent(result);
							},
							this)
					);
				},
				onPopupClose: function()
				{
					BX.SGCP.onPopupClose();
				}
			}
		});
			
		BX.SGCP.popup.params.zIndex = (BX.WindowManager? BX.WindowManager.GetZIndex() : 0);
		BX.SGCP.popup.show();
	}

	BX.PreventDefault(event);
};

BX.SGCP.onPopupClose = function()
{
	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
	}

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
	}
}

BX.SGCP.destroyPopup = function()
{
	BX.SGCP.onPopupClose();

	if (BX.SGCP.popup != null)
	{
		BX.SGCP.popup.destroy();
	}
}

})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:95:"/bitrix/templates/bitrix24/components/bitrix/menu/vertical_multilevel/script.js?145227753226932";s:6:"source";s:79:"/bitrix/templates/bitrix24/components/bitrix/menu/vertical_multilevel/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
BX.namespace("BX.Bitrix24");

BX.Bitrix24.MenuClass = (function()
{
	var MenuClass = function(params)
	{
		params = typeof params === "object" ? params : {};

		this.arFavouriteAll = params.arFavouriteAll || {};
		this.arFavouriteShowAll = params.arFavouriteShowAll || {};
		this.arTitles = params.arTitles || [];
		this.ajaxPath = params.ajaxPath || null;
		this.isAdmin =  params.isAdmin === "Y";
		this.hiddenCounters = params.hiddenCounters || {};
		this.allCounters = params.allCounters || {};
		this.isBitrix24 = params.isBitrix24 === "Y";
		this.siteId = params.siteId || null;
		this.isCompositeMode = params.isCompositeMode === true;

		this.activeItemsId = [];

		//show hidden items, if they are selected
		if (params.arHiddenItemsSelected)
		{
			for (var key in params.arHiddenItemsSelected)
			{
				this.showHideMoreItems(BX("more_btn_" + params.arHiddenItemsSelected[key]), params.arHiddenItemsSelected[key]);
			}
		}

		for (var i = 0, l = this.arTitles.length; i < l; i++)
		{
			var itemId = this.arTitles[i];
			var item = BX(itemId);
			if (!item || BX.hasClass(item, "menu-favorites"))
			{
				continue;
			}

			BX.bind(item, "click", BX.proxy(this.showHideMenuSection2, {element: item, self:this}));
			BX.bind(item.lastChild, "click", BX.proxy(this.showHideMenuSection, {element: item.lastChild, self:this } ));
		}
	};

	MenuClass.prototype.showHideMenuSection = function(event)
	{
		if (this.self.isCompositeMode)
		{
			this.self.clearCompositeCache();
		}

		event = event || window.event;
		BX.eventCancelBubble(event);
		B24.toggleMenu(this.element.parentNode, BX.message("menu_show"), BX.message("menu_hide"));
	};

	MenuClass.prototype.showHideMenuSection2 = function()
	{
		if (!this.self.isEditMode())
		{
			if (this.self.isCompositeMode)
			{
				this.self.clearCompositeCache();
			}

			B24.toggleMenu(this.element, BX.message("menu_show"), BX.message("menu_hide"));
		}
	};

	MenuClass.prototype.isEditMode = function()
	{
		return BX.hasClass(BX("div_menu-favorites"), 'menu-favorites-editable');
	};

	MenuClass.prototype.applyEditMode = function()
	{
		var isEditMode = this.isEditMode();

		var allTitleBlocks = BX.findChildren(BX("bx_b24_menu"), {className:"menu-items-block"}, true);
		for (var obj in allTitleBlocks)
		{
			if (isEditMode)
				BX.removeClass(allTitleBlocks[obj], "menu-favorites-editable");
			else
				BX.addClass(allTitleBlocks[obj], "menu-favorites-editable");
		}

		if (!isEditMode)
		{
			BX.addClass(BX("menu_favorites_settings"), 'menu-favorites-btn-active');

			var allActiveItems = BX.findChildren(BX("bx_b24_menu"), {className:"menu-item-active"}, true);
			for (obj in allActiveItems)
			{
				if (!isEditMode)
				{
					BX.removeClass(allActiveItems[obj], 'menu-item-active');
					this.activeItemsId.push(allActiveItems[obj].id);
				}
			}
		}
		else
		{
			BX.removeClass(BX("menu_favorites_settings"), 'menu-favorites-btn-active');
			for (var key in this.activeItemsId)
			{
				BX.addClass(BX(this.activeItemsId[key]), 'menu-item-active');
			}
			this.activeItemsId = [];
		}

		var moveItems = [];
		for (var j=0; j<this.arTitles.length; j++)
		{
			if (this.arTitles[j] != "menu-favorites")
			{
				BX(this.arTitles[j]).onbxdragstart = BX.proxy(this.sectionDragStart, this);
				BX(this.arTitles[j]).onbxdrag = BX.proxy(this.sectionDragMove, this);
				BX(this.arTitles[j]).onbxdragstop = BX.proxy(this.sectionDragStop, this);
				BX(this.arTitles[j]).onbxdraghover = BX.proxy(this.sectionDragHover, this);
				jsDD.registerObject(BX(this.arTitles[j]));
			}
			jsDD.registerDest(BX(this.arTitles[j]).parentNode, 200);

			//drag&drop
			if (!isEditMode)
			{
				jsDD.Enable();
				var liObj = BX.findChildren(BX("ul_"+this.arTitles[j]), {tagName:"li"}, true);
				for (var i=0; i<liObj.length; i++)
				{
					if (liObj[i].id == "separator_"+this.arTitles[j])
						break;

					if (((this.isBitrix24 && this.arTitles[j] == "menu-favorites") || this.arTitles[j] == "menu-groups") && liObj[i].id == "empty_li_"+this.arTitles[j])
						continue;

					if ((this.isBitrix24 && liObj[i].id == "menu_live_feed") || liObj[i].id == "menu_all_groups")
					{
						jsDD.registerDest(liObj[i]);
						continue;
					}
					moveItems.push(liObj[i].id);

					liObj[i].onbxdragstart = BX.proxy(this.menuItemDragStart, this);
					liObj[i].onbxdrag =  BX.proxy(this.menuItemDragMove, this);
					liObj[i].onbxdragstop =  BX.proxy(this.menuItemDragStop, this);
					liObj[i].onbxdraghover =  BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(liObj[i], 100);
					jsDD.registerObject(liObj[i]);
				}
			}
			//--drag&drop

			var liObj = BX.findChildren(BX("hidden_items_ul_"+this.arTitles[j]), {tagName:"li"}, true);
			if (liObj.length > 0)
				BX("separator_"+this.arTitles[j]).style.display = (isEditMode)  ? "none" : "block";
			else
				BX("separator_"+this.arTitles[j]).style.display = "none";
		}
	};

	MenuClass.prototype.showHideMoreItems = function(element, titleItemId)
	{
		BX.toggleClass(BX('hidden_items_li_'+titleItemId), 'menu-item-favorites-more-open');
		BX.toggleClass(element, 'menu-favorites-more-btn-open');
		if (titleItemId == "menu-favorites")
			BX.toggleClass(BX('menu-hidden-counter'), 'menu-hidden-counter');
		BX.firstChild(element).innerHTML = (BX.firstChild(element).innerHTML == BX.message('more_items_hide')) ? BX.message('more_items_show') : BX.message('more_items_hide');
	};

	MenuClass.prototype.openMenuPopup = function(bindElement, menuItemId)
	{
		var menuItems = [];
		var self = this;

		var itemIsFavourite = false;
		for(var i = 0, l = this.arFavouriteAll.length; i < l; i++)
		{
			if (this.arFavouriteAll[i] == menuItemId)
				itemIsFavourite = true;
		}

		var can_delete_from_favorite = BX(menuItemId).getAttribute("data-can-delete-from-favorite");
		var title_item = BX(menuItemId).getAttribute("data-title-item");

		//add to favorite
		if (!itemIsFavourite && can_delete_from_favorite == "Y")
			menuItems.push({text : BX.message("add_to_favorite"), className : "menu-popup-no-icon", onclick :  function() {this.popupWindow.close(); self.addFavouriteItem(menuItemId, title_item, "N"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//delete from favorite
		if (itemIsFavourite && can_delete_from_favorite == "Y")
			menuItems.push({text : BX.message("delete_from_favorite"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.deleteFavouriteItem(menuItemId, title_item, "N"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//hide item
		if (BX(menuItemId).getAttribute("data-status") == "show" /*&& !(itemIsFavourite && can_delete_from_favorite == "Y")*/)
			menuItems.push({text : BX.message("hide_item"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.hideItem(menuItemId, title_item); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		//show item
		if (BX(menuItemId).getAttribute("data-status") == "hide"/* && !(itemIsFavourite && can_delete_from_favorite == "Y")*/)
			menuItems.push({text : BX.message("show_item"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.showItem(menuItemId, title_item); BX.PopupMenu.destroy("popup_"+menuItemId);}});

		if (this.isAdmin)
		{
			//add to favorite all
			if (!itemIsFavourite)
				menuItems.push({text : BX.message("add_to_favorite_all"), className : "menu-popup-no-icon", onclick : function() {self.addFavouriteItem(menuItemId, title_item, "Y"); BX.PopupMenu.destroy("popup_"+menuItemId);}});
			//delete from favorite all
			if (itemIsFavourite && can_delete_from_favorite == "A")
				menuItems.push({text : BX.message("delete_from_favorite_all"), className : "menu-popup-no-icon", onclick : function() {self.deleteFavouriteItem(menuItemId, title_item, "Y"); BX.PopupMenu.destroy("popup_"+menuItemId);}});

			//set rights for apps
			if (BX(menuItemId).getAttribute("data-app-id"))
				menuItems.push({text : BX.message("set_rights"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.setRights(menuItemId); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		}

		var MenuPopup = BX.PopupMenu.show("popup_"+menuItemId, bindElement, menuItems,
			{
				offsetTop:0,
				offsetLeft : 12,
				angle :true,
				events : {
					onPopupClose : function() {
						BX.removeClass(bindElement, 'menu-favorites-btn-active');
					}
				}
			});
		BX.addClass(bindElement, 'menu-favorites-btn-active');
	};

	MenuClass.prototype.showError = function(bindElement)
	{
		var errorPopup = BX.PopupWindowManager.create("menu-error", bindElement, {
			content: BX.message('edit_error'),
			angle: {offset : 10 },
			offsetTop:0,
			events : { onPopupClose: function() { BX.removeClass(this.bindElement, "filter-but-act")}},
			autoHide:true
		});
		errorPopup.setBindElement(bindElement);
		errorPopup.show();
	};

	MenuClass.prototype.setRights =  function(menuItemId)
	{
		var ajaxPath = this.ajaxPath;
		BX.Access.Init({
			other: {
				disabled: false,
				disabled_g2: true,
				disabled_cr: true
			},
			groups: { disabled: true },
			socnetgroups: { disabled: true }
		});

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "get_app_rigths",
				app_id : BX(menuItemId).getAttribute("data-app-id")
			},
			onsuccess: BX.proxy(function(json)
			{
				BX.Access.SetSelected(json.rights, "bind");

				BX.Access.ShowForm({
					bind: "bind",
					showSelected: true,
					callback: BX.proxy(function(arRights)
					{
						BX.ajax.post(
							ajaxPath,
							{
								sessid : BX.bitrix_sessid(),
								site_id : this.siteId,
								action : "set_app_rights",
								menu_item_id : menuItemId,
								app_id : BX(menuItemId).getAttribute("data-app-id"),
								rights : arRights
							},
							function(result)
							{
							}
						);
					}, this)
				});
			}, this)
		});
	};

	MenuClass.prototype.addFavouriteItem =  function(menuItemId, titleItem, forAll)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : (forAll == "Y") ? "add_favorite_admin" : "add_favorite",
				menu_item_id : menuItemId,
				title_item_id : titleItem,
				all_show_items : this.arFavouriteShowAll
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX.removeClass(BX.firstChild(BX(menuItemId)), 'menu-favorites-btn-active');
					var cloneObj = BX.clone(BX(menuItemId));
					BX(menuItemId).id = "hidden_"+menuItemId;
					BX("hidden_"+menuItemId).style.display = "none";
					BX("ul_menu-favorites").insertBefore(cloneObj, BX("separator_menu-favorites"));
					BX(menuItemId).setAttribute("data-title-item", "menu-favorites");
					BX(menuItemId).setAttribute("data-status", "show");
					if (forAll == "Y")
						BX(menuItemId).setAttribute("data-can-delete-from-favorite", "A");

					BX(menuItemId).onbxdragstart =  BX.proxy(this.menuItemDragStart, this);
					BX(menuItemId).onbxdrag =  BX.proxy(this.menuItemDragMove, this);
					BX(menuItemId).onbxdragstop =  BX.proxy(this.menuItemDragStop, this);
					BX(menuItemId).onbxdraghover =  BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(BX(menuItemId));
					jsDD.registerObject(BX(menuItemId));

					var otherItems = BX.findChildren(BX("ul_"+titleItem), {tagName:"li"}, true);
					var otherItemsExist = false;
					for (var i=0; i<otherItems.length; i++)
					{
						if (
							otherItems[i].id != "hidden_"+menuItemId
								&& otherItems[i].style.display != "none"
								&& otherItems[i].id != "separator_"+titleItem
								&& otherItems[i].id != "empty_li_"+titleItem
								&& otherItems[i].id != "hidden_items_li_"+titleItem
							)
						{
							otherItemsExist = true;
							break;
						}
					}
					if (!otherItemsExist)
						BX("div_"+titleItem).style.display = "none";

					this.arFavouriteShowAll.push(menuItemId);
					this.arFavouriteAll.push(menuItemId);

					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+titleItem), {tagName:"li"}, true);
					var otherHiddenExist = false;
					for (var i=0; i<otherHiddenItems.length; i++)
					{
						if (otherHiddenItems[i].id != "hidden_"+menuItemId
							&& otherHiddenItems[i].style.display != "none"
							)
						{
							otherHiddenExist = true;
							break;
						}
					}
					if (!otherHiddenExist)
					{
						BX("more_btn_"+titleItem).style.display = "none";
						BX("separator_"+titleItem).style.display = "none";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.deleteFavouriteItem = function(menuItemId, oldTitleItem, forAll)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : (forAll == "Y") ? "delete_favorite_admin" : "delete_favorite",
				menu_item_id : menuItemId,
				title_item_id : oldTitleItem
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX.remove(BX(menuItemId));
					BX("hidden_"+menuItemId).id = menuItemId;
					BX(menuItemId).style.display = "block";
					if (forAll == "Y")
						BX(menuItemId).setAttribute("data-can-delete-from-favorite", "Y");
					var cur_title_item = BX(menuItemId).getAttribute("data-title-item");
					BX("div_"+cur_title_item).style.display = "block";

					for(var i = 0, l = this.arFavouriteAll.length; i < l; i++)
					{
						if (this.arFavouriteAll[i] == menuItemId)
						{
							this.arFavouriteAll.splice(i,1);
						}
					}
					for(i = 0, l = this.arFavouriteShowAll.length; i < l; i++)
					{
						if (this.arFavouriteShowAll[i] == menuItemId)
						{
							this.arFavouriteShowAll.splice(i,1);
						}
					}

					var curOtherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+cur_title_item), {tagName:"li"}, true);
					var otherHiddenExist = false;
					for (i=0; i<curOtherHiddenItems.length; i++)
					{
						if (curOtherHiddenItems[i].style.display != "none")
						{
							otherHiddenExist = true;
							break;
						}
					}
					if (otherHiddenExist)
					{
						BX("more_btn_"+cur_title_item).style.display = "block";
						BX("separator_"+cur_title_item).style.display = "block";
					}
					//favorite block
					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+oldTitleItem), {tagName:"li"}, true);
					if (otherHiddenItems.length <= 0)
					{
						BX("more_btn_"+oldTitleItem).style.display = "none";
						BX("separator_"+oldTitleItem).style.display = "none";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.hideItem = function(menuItemId, titleItem)
	{
		for(var i = 0, l = this.arFavouriteShowAll.length; i < l; i++)
		{
			if (this.arFavouriteShowAll[i] == menuItemId)
			{
				this.arFavouriteShowAll.splice(i,1);
			}
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "hide",
				menu_item_id : menuItemId,
				title_item_id : titleItem,
				all_show_items : this.arFavouriteShowAll
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					BX("separator_"+titleItem).style.display = "block";
					BX(menuItemId).setAttribute("data-status", "hide");
					var cloneObj = BX.clone(BX(menuItemId));
					BX.remove(BX(menuItemId));
					BX("hidden_items_ul_"+titleItem).appendChild(cloneObj);
					BX("more_btn_"+titleItem).style.display = "block";

					if (BX(menuItemId).getAttribute("data-counter-id"))
					{
						this.hiddenCounters.push(BX(menuItemId).getAttribute("data-counter-id"));
						var curSumCounters = 0;
						for (var i=0; i<this.hiddenCounters.length; i++)
						{
							curSumCounters+= +(this.allCounters[this.hiddenCounters[i]]);
						}

						BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
						if (curSumCounters > 0)
							BX("menu-hidden-counter").style.display = "inline-block";
					}
				}
			}, this)
		});
	};

	MenuClass.prototype.showItem = function(menuItemId, titleItem)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "show",
				menu_item_id : menuItemId,
				title_item_id : titleItem
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(BX(menuItemId));
				}
				else
				{
					this.arFavouriteShowAll.push(menuItemId);
					BX(menuItemId).setAttribute("data-status", "show");
					BX("ul_"+titleItem).insertBefore(BX(menuItemId), BX("separator_"+titleItem));
					BX(menuItemId).onbxdragstart = BX.proxy(this.menuItemDragStart, this);
					BX(menuItemId).onbxdrag = BX.proxy(this.menuItemDragMove, this);
					BX(menuItemId).onbxdragstop = BX.proxy(this.menuItemDragStop, this);
					BX(menuItemId).onbxdraghover = BX.proxy(this.menuItemDragHover, this);
					jsDD.registerDest(BX(menuItemId));
					jsDD.registerObject(BX(menuItemId));

					var otherHiddenItems = BX.findChildren(BX("hidden_items_ul_"+titleItem), {tagName:"li"}, true);
					if (otherHiddenItems.length <= 0)
					{
						BX("more_btn_"+titleItem).style.display = "none";
						BX("separator_"+titleItem).style.display = "none";
					}

					if (BX(menuItemId).getAttribute("data-counter-id"))
					{
						for(var i = 0, l = this.hiddenCounters.length; i < l; i++)
						{
							if (this.hiddenCounters[i] == BX(menuItemId).getAttribute("data-counter-id"))
								this.hiddenCounters.splice(i,1);
						}

						var curSumCounters = 0;
						for (i=0; i<this.hiddenCounters.length; i++)
						{
							curSumCounters+= +(this.allCounters[this.hiddenCounters[i]]);
						}

						BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
						if (curSumCounters <= 0)
							BX("menu-hidden-counter").style.display = "none";

					}
				}
			}, this)
		});
	};

	MenuClass.prototype.sortItems = function(arTitleItems, titleItem)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "sort_items",
				title_item_id : titleItem,
				all_title_items : arTitleItems
			},
			onsuccess: BX.proxy(function(json)
			{
			}, this)
		});
	};

	MenuClass.prototype.sortSections = function(arSections)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "sort_sections",
				all_sections : arSections
			},
			onsuccess: BX.proxy(function(json)
			{
			}, this)
		});
	};

	//drag&drop
	MenuClass.prototype.menuItemDragStart = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		this.bxparent = dragElement.parentNode;
		this.objHeight = 36;//dragElement.offsetHeight;

		BX.addClass(dragElement, "menu-item-draggable");

		this.bxblank = this.bxparent.insertBefore(BX.create('DIV', {style: {height: '0px'}}), dragElement);
		this.bxblank1 = BX.create('DIV', {style: {height: this.objHeight+'px'}}); //empty div
		jsDD.disableDest(this.bxparent);

		this.bxcp = BX.create('DIV', {             //div to move
			attrs:{className: "menu-draggable-wrap"},
			children: [dragElement]
		});
		this.bxpos = BX.pos(this.bxparent);

		var liObj = BX.findChildren(this.bxparent, {tagName:"li"}, true);

		var countObj = 0;
		var isHiddenSection = false;
		for (var i=0; i<liObj.length; i++)
		{
			if (liObj[i].id == "separator_"+dragElement.getAttribute("data-title-item"))
				break;
			if (liObj[i].id == "empty_li_"+dragElement.getAttribute("data-title-item"))
				continue;
			if (liObj[i].style.display == "none")
				continue;
			countObj++;
		}
		this.countObj = countObj > 0 ? countObj : 0;

		this.bxparent.style.position = 'relative';
		this.bxparent.appendChild(this.bxcp);
	};

	MenuClass.prototype.menuItemDragMove = function(x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		y -= this.bxpos.top;

		if (this.isBitrix24 && (dragElement.getAttribute("data-title-item") == "menu-favorites" || dragElement.getAttribute("data-title-item") == "menu-groups") && y<this.objHeight)
			y = this.objHeight;
		else if (y < 0)
			y = 0;
		if (y > this.countObj*this.objHeight)
			y = this.countObj*this.objHeight;

		this.bxcp.style.top = y + 'px';
	};

	MenuClass.prototype.menuItemDragHover = function(dest, x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (
			BX.hasClass(dragElement, "menu-items-title")
				|| BX.hasClass(dest, "menu-items-title")
				|| BX.hasClass(dest, "menu-items-block")
			)
			return;

		y -= this.bxpos.top;

		if (dest == dragElement)
		{
			this.bxparent.insertBefore(this.bxblank1, this.bxblank);
		}
		else if (dest.parentNode == this.bxparent)
		{

			if (this.bxparent.parentNode.id == dest.parentNode.parentNode.id)  //li is hovered
			{
				if (dest.nextSibling)
					this.bxparent.insertBefore(this.bxblank1, dest.nextSibling);
				else
					this.bxparent.appendChild(this.bxblank1);
			}
		}
	};

	MenuClass.prototype.menuItemDragStop = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		BX.removeClass(dragElement, "menu-item-draggable");
		if (this.bxblank1 && this.bxblank1.parentNode == this.bxparent)
		{
			this.bxparent.replaceChild(dragElement, this.bxblank1);

			var arTitleItems = [];
			var liObj = BX.findChildren(dragElement.parentNode, {tagName:"li"}, true);
			for (var i=0; i<liObj.length; i++)
			{
				if (liObj[i].id == "empty_li_"+dragElement.getAttribute("data-title-item"))
					continue;

				if (liObj[i].id == "separator_"+dragElement.getAttribute("data-title-item"))
					break;

				arTitleItems.push(liObj[i].id);
			}

			this.sortItems(arTitleItems, dragElement.getAttribute("data-title-item"));
		}
		else
		{
			this.bxparent.replaceChild(dragElement, this.bxblank);
		}
		BX.remove(this.bxcp);
		BX.remove(this.bxblank);
		BX.remove(this.bxblank1);

		jsDD.enableDest(dragElement);
		this.bxparent.style.position = 'static';

		this.bxcp = null;
		this.bxpos = null;
		this.bxparent = null;
		this.bxblank = null;
		this.bxblank1 = null;
		jsDD.refreshDestArea();
	};

	//sections drag&drop
	MenuClass.prototype.sectionDragStart = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		this.bxSectParent = dragElement.parentNode.parentNode;
		this.bxSectParentHeight = dragElement.parentNode.parentNode.offsetHeight;
		this.objSectHeight = dragElement.parentNode.offsetHeight;

		this.bxSectBlank = this.bxSectParent.insertBefore(BX.create('DIV', {style: {height: '0px'}}), dragElement.parentNode);
		this.bxSectBlank1 = BX.create('DIV', {style: {height: this.objSectHeight+"px"}}); //empty div
		jsDD.disableDest(this.bxSectParent);

		this.bxSectBlock = BX.create('DIV', {             //div to move
			style: {
				position: 'absolute',
				zIndex: '100',
				height:this.objSectHeight-14+"px",
				width:dragElement.parentNode.offsetWidth+"px",
				paddingTop: "10px",
				borderRadius:"3px",
				backgroundColor: 'rgba(206, 218, 220, .9)'
			},
			children: [dragElement.parentNode]
		});

		this.bxSectPos = BX.pos(this.bxSectParent);

		this.bxSectParent.style.position = 'relative';
		this.bxSectParent.appendChild(this.bxSectBlock);
	};

	MenuClass.prototype.sectionDragMove = function(x, y)
	{
		if (!this.isEditMode())
			return;

		y -= this.bxSectPos.top;

		if (y < 0)
			y = 0;

		if (y > this.bxSectParentHeight)
			y = this.bxSectParentHeight;

		this.bxSectBlock.style.top = y + 'px';
	};

	MenuClass.prototype.sectionDragHover = function(dest, x, y)
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (
			BX.hasClass(dragElement, "menu-item-block")
				|| BX.hasClass(dest, "menu-item-block")
				|| BX.hasClass(dest, "menu-items-empty-li")
			)
			return;

		if (dest == dragElement.parentNode)
		{
			this.bxSectParent.insertBefore(this.bxSectBlank1, this.bxSectBlank);
		}
		else
		{
			if (dest.nextSibling)
				this.bxSectParent.insertBefore(this.bxSectBlank1, dest.nextSibling);
			else
				this.bxSectParent.appendChild(this.bxSectBlank1);
		}
	};

	MenuClass.prototype.sectionDragStop = function()
	{
		if (!this.isEditMode())
			return;

		var dragElement = BX.proxy_context;

		if (this.bxSectBlank1 && this.bxSectBlank1.parentNode == this.bxSectParent)
		{
			this.bxSectParent.replaceChild(dragElement.parentNode, this.bxSectBlank1);

			var arSectionItems = [];
			var sectionsObj = BX.findChildren(dragElement.parentNode.parentNode, {className:"menu-items-title"}, true);
			for (var i=0; i<sectionsObj.length; i++)
			{
				arSectionItems.push(sectionsObj[i].id);
			}
			this.sortSections(arSectionItems, dragElement.getAttribute("data-title-item"));
		}
		else
		{
			this.bxSectParent.replaceChild(dragElement.parentNode, this.bxSectBlank);
		}
		BX.remove(this.bxSectBlock);
		BX.remove(this.bxSectBlank);
		BX.remove(this.bxSectBlank1);

		jsDD.enableDest(dragElement);

		this.bxSectBlock = null; this.bxSectBlank = null; this.bxSectBlank1 = null; this.bxSectParent = null;
		jsDD.refreshDestArea();
	};

	MenuClass.prototype.clearCompositeCache = function()
	{
		BX.ajax.post(
			this.ajaxPath,
			{
				sessid : BX.bitrix_sessid(),
				action : "clear"
			},
			function(result) {

			}
		);
	};

	MenuClass.highlight = function(url)
	{
		var menu = BX("bx_b24_menu");
		if (!BX.type.isNotEmptyString(url) || !menu)
		{
			return false;
		}

		var items = menu.getElementsByTagName("a");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedUrl = null;
		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemUrl = items[i].getAttribute("href");
			if (!BX.type.isNotEmptyString(itemUrl))
			{
				continue;
			}

			if (url.indexOf(itemUrl) === 0)
			{
				var newLength = itemUrl.length;
				if (newLength > curSelectedLen)
				{
					curSelectedItem = i;
					curSelectedUrl = itemUrl;
					curSelectedLen = newLength;
				}
			}
		}

		var li = items[curSelectedItem].parentNode;
		if (curSelectedUrl == "/" && curSelectedUrl == url)
		{
			BX.addClass(li, "menu-item-active");
		}
		else if (curSelectedUrl !== null && curSelectedUrl != "/")
		{
			BX.addClass(li, "menu-item-active");
		}

		//Show hidden item
		var moreItem = li.parentNode.parentNode;
		if (BX.hasClass(moreItem, "menu-item-favorites-more") &&
			!BX.hasClass(moreItem, "menu-item-favorites-more-open"))
		{
			var id = BX.firstChild(moreItem.parentNode.parentNode).getAttribute("id");
			MenuClass.prototype.showHideMoreItems(BX("more_btn_" + id), id);
		}

		return true;
	};

	return MenuClass;

})();






/* End */
;
; /* Start:"a:4:{s:4:"full";s:58:"/bitrix/templates/bitrix24/bitrix24.min.js?145227753336565";s:6:"source";s:38:"/bitrix/templates/bitrix24/bitrix24.js";s:3:"min";s:42:"/bitrix/templates/bitrix24/bitrix24.min.js";s:3:"map";s:42:"/bitrix/templates/bitrix24/bitrix24.map.js";}"*/
(function(){BX.addCustomEvent("onFrameDataRequestFail",function(t){top.location="/auth/?backurl="+B24.getBackUrl()});BX.addCustomEvent("onAjaxFailure",function(t){var e="/auth/?backurl="+B24.getBackUrl();if(t=="auth"){top.location=e}});BX.addCustomEvent("onPopupWindowInit",function(t,e,n){if(t=="bx_log_filter_popup"){n.lightShadow=true;n.className=""}else if(t=="task-legend-popup"){n.lightShadow=true;n.offsetTop=-15;n.offsetLeft=-670;n.angle={offset:740}}else if(t=="task-gantt-filter"||t=="task-list-filter"){n.lightShadow=true;n.className=""}else if(t.indexOf("sonet_iframe_popup_")>-1){n.lightShadow=true}});BX.addCustomEvent("onJCClockInit",function(t){JCClock.setOptions({centerXInline:83,centerX:83,centerYInline:67,centerY:79,minuteLength:31,hourLength:26,popupHeight:229,inaccuracy:15,cancelCheckClick:true})});BX.PopupWindow.setOptions({angleMinTop:35,angleMinRight:10,angleMinBottom:35,angleMinLeft:10,angleTopOffset:5,angleLeftOffset:45,offsetLeft:0,offsetTop:2,positionTopXOffset:-11});BX.addCustomEvent("onPullEvent-main",function(t,e){if(t=="user_counter"&&e[BX.message("SITE_ID")]){var n=BX.clone(e[BX.message("SITE_ID")]);B24.updateCounters(n)}});BX.addCustomEvent(window,"onImUpdateCounter",function(t){if(!t)return;B24.updateCounters(BX.clone(t))});BX.addCustomEvent("onCounterDecrement",function(t){var e=BX("menu-counter-live-feed",true);if(!e)return;t=parseInt(t);var n=parseInt(e.innerHTML);var i=n-t;if(i>0)e.innerHTML=i;else BX.removeClass(e.parentNode.parentNode.parentNode,"menu-item-with-index")});BX.addCustomEvent("onImUpdateCounterNotify",function(t){B24.updateInformer(BX("im-informer-events",true),t)});BX.addCustomEvent("onImUpdateCounterMessage",function(t){B24.updateInformer(BX("im-informer-messages",true),t)});BX.addCustomEvent("onImUpdateCounterNetwork",function(t){B24.updateInformer(BX("b24network-informer-events",true),t)});BX.addCustomEvent("onPullError",BX.delegate(function(t,e){if(t=="AUTHORIZE_ERROR"){B24.connectionStatus("offline")}else if(t=="RECONNECT"&&(e==1008||e==1006)){B24.connectionStatus("connecting")}},this));BX.addCustomEvent("onImError",BX.delegate(function(t,e){if(t=="AUTHORIZE_ERROR"||t=="SEND_ERROR"&&e=="AUTHORIZE_ERROR"){B24.connectionStatus("offline")}else if(t=="CONNECT_ERROR"){B24.connectionStatus("offline")}},this));BX.addCustomEvent("onPullStatus",BX.delegate(function(t){if(t=="offline")B24.connectionStatus("offline");else B24.connectionStatus("online")},this));BX.bind(window,"online",BX.delegate(function(){B24.connectionStatus("online")},this));BX.bind(window,"offline",BX.delegate(function(){B24.connectionStatus("offline")},this));if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",function(t){if(t.key.substring(0,4)=="lmc-"){var e={};e[t.key.substring(4)]=t.value;B24.updateCounters(e,false)}})}BX.ready(function(){var t=BX("feed-up-btn-wrap",true);var e=BX("menu",true);BX.bind(window,"scroll",B24.onScroll);if(e&&t){BX.bind(window,"resize",B24.onScroll)}})})();var B24={upButtonScrollLock:false,b24ConnectionStatusState:"online",b24ConnectionStatus:null,b24ConnectionStatusText:null,b24ConnectionStatusTimeout:null,formateDate:function(t){return BX.util.str_pad(t.getHours(),2,"0","left")+":"+BX.util.str_pad(t.getMinutes(),2,"0","left")},HelpPopupWindow:{legend:null,show:function(t,e,n){if(this.popup==null)this.legend=new B24.HelpPopup(t,e,{selectedClass:"b24-help-popup-page-selected"});this.legend.popup.show()}},VideoPopupWindow:{legend:null,currentStepId:null,steps:[],params:{},init:function(t,e){if(BX.type.isArray(t)){this.steps=t}this.params=e||{}},create:function(){if(this.legend==null){var t={video:true,selectedClass:"b24-video-popup-menu-item-selected"};for(var e in this.params){if(this.params.hasOwnProperty(e)){t[e]=this.params[e]}}this.legend=new B24.HelpPopup(this.steps,null,t)}},show:function(t){if(this.legend==null){this.create()}if(BX.type.isNotEmptyString(t)){this.setCurrentStep(t)}var e=this.legend.getStepPositionById(this.currentStepId);if(e>=0){this.legend.showStepByNumber(e)}if(!this.legend.popup.isShown()){this.legend.popup.show()}else{this.legend.scrollToCurrent()}},close:function(){if(this.legend){this.legend.popup.close()}},setCurrentStep:function(t){this.currentStepId=t},existsStep:function(t){if(!this.legend)return false;return this.legend.getStepPositionById(t)>=0}},openLanguagePopup:function(t){var e=JSON.parse(t.getAttribute("data-langs"));var n=[];for(var i in e){n.push({text:e[i],className:i,onclick:function(t,e){B24.changeLanguage(e.className)}})}BX.PopupMenu.show("language-popup",t,n,{offsetTop:10,offsetLeft:0})},changeLanguage:function(t){window.location.href="/auth/?user_lang="+t+"&backurl="+B24.getBackUrl()},getBackUrl:function(){var t=window.location.pathname;var e=B24.getQueryString(["logout","login","back_url_pub","user_lang"]);return t+(e.length>0?"?"+e:"")},getQueryString:function(t){var e=window.location.search.substring(1);if(!BX.type.isNotEmptyString(e)){return""}var n=e.split("&");t=BX.type.isArray(t)?t:[];var i="";for(var s=0;s<n.length;s++){var o=n[s].split("=");var a=n[s].indexOf("=");var r=o[0];var l=BX.type.isNotEmptyString(o[1])?o[1]:false;if(!BX.util.in_array(r,t)){if(i!==""){i+="&"}i+=r+(a!==-1?"=":"")+(l!==false?l:"")}}return i},updateInformer:function(t,e){if(e>0){t.innerHTML=e;BX.addClass(t,"header-informer-act")}else{t.innerHTML="";BX.removeClass(t,"header-informer-act")}},updateCounters:function(t,e){e=e==false?false:true;var n=false;for(var i in t){if(window.B24menuItemsObj)window.B24menuItemsObj.allCounters[i]=t[i];if(i=="**"){oCounter={iCommentsMenuRead:0};BX.onCustomEvent(window,"onMenuUpdateCounter",[oCounter]);t[i]-=oCounter.iCommentsMenuRead}if(i=="CRM_**"){n=true;if(BX("menu-counter-crm_cur_act")){BX("menu-counter-crm_cur_act").setAttribute("data-counter-crmstream",t[i])}}else if(i=="crm_cur_act"){n=true;if(BX("menu-counter-crm_cur_act")){BX("menu-counter-crm_cur_act").setAttribute("data-counter-crmact",t[i])}}var s=BX(i=="**"?"menu-counter-live-feed":"menu-counter-"+i.toLowerCase(),true);if(s){if(t[i]>0){s.innerHTML=i=="mail_unseen"?t[i]>99?"99+":t[i]:t[i]>50?"50+":t[i];BX.addClass(s.parentNode.parentNode.parentNode,"menu-item-with-index")}else{BX.removeClass(s.parentNode.parentNode.parentNode,"menu-item-with-index");if(t[i]<0){var o=BX("menu-counter-warning-"+i.toLowerCase());if(o)o.style.display="inline-block"}}if(e)BX.localStorage.set("lmc-"+i,t[i],5)}else if(n&&BX("menu-counter-crm_cur_act")){var a=parseInt(BX("menu-counter-crm_cur_act").getAttribute("data-counter-crmact"))+parseInt(BX("menu-counter-crm_cur_act").getAttribute("data-counter-crmstream"));if(BX.type.isNumber(a)){BX("menu-counter-crm_cur_act").innerHTML=a>50?"50+":a;n=false}}}if(window.B24menuItemsObj){var r=0;for(var l=0,u=window.B24menuItemsObj.hiddenCounters.length;l<u;l++){if(window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[l]]){r+=+window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[l]]}}BX("menu-hidden-counter").style.display=r>0?"inline-block":"none";BX("menu-hidden-counter").innerHTML=r>50?"50+":r}},showNotifyPopup:function(t){if(BX.hasClass(t,"header-informer-press")){BX.removeClass(t,"header-informer-press");BXIM.closeNotify()}else{BXIM.openNotify()}},showMessagePopup:function(t){if(typeof BXIM=="undefined")return false;if(BXIM.isOpenMessenger()){BXIM.closeMessenger()}else{BXIM.openMessenger()}},closeBanner:function(t){BX.userOptions.save("bitrix24","banners",t,"Y");var e=BX("sidebar-banner-"+t);if(e){e.style.minHeight="auto";e.style.overflow="hidden";e.style.border="none";new BX.easing({duration:500,start:{height:e.offsetHeight,opacity:100},finish:{height:0,opacity:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){if(t.height>=0){e.style.height=t.height+"px";e.style.opacity=t.opacity/100}if(t.height<=17){e.style.marginBottom=t.height+"px"}},complete:function(){e.style.display="none"}}).animate()}},showLoading:function(t){t=t||500;function e(){var t=BX("b24-loader");if(t){BX.addClass(t,"b24-loader-show");return true}return false}setTimeout(function(){if(!e()&&!BX.isReady){BX.ready(e)}},t)}};B24.onScroll=function(){var t=BX.GetWindowScrollPos();if(B24.b24ConnectionStatus){if(B24.b24ConnectionStatus.getAttribute("data-float")=="true"){if(t.scrollTop<60){BX.removeClass(B24.b24ConnectionStatus,"bx24-connection-status-float");B24.b24ConnectionStatus.setAttribute("data-float","false")}}else{if(t.scrollTop>60){BX.addClass(B24.b24ConnectionStatus,"bx24-connection-status-float");B24.b24ConnectionStatus.setAttribute("data-float","true")}}}if(B24.upButtonScrollLock)return;B24.upButtonScrollLock=true;setTimeout(function(){B24.upButtonScrollLock=false},150);var e=BX("menu",true);if(!e)return;var n=BX.GetWindowInnerSize();var i=BX.pos(e);var s=BX("feed-up-btn-wrap",true);s.style.left="-"+t.scrollLeft+"px";if(t.scrollTop+parseInt(n.innerHeight*.33)>i.bottom)B24.showUpButton(true,s);else B24.showUpButton(false,s)};B24.showUpButton=function(t,e){if(!e)return;if(!!t)BX.addClass(e,"feed-up-btn-wrap-anim");else BX.removeClass(e,"feed-up-btn-wrap-anim")};B24.goUp=function(){var t=BX("feed-up-btn-wrap",true);if(t){t.style.display="none";BX.removeClass(t,"feed-up-btn-wrap-anim")}var e=BX.GetWindowScrollPos();new BX.easing({duration:500,start:{scroll:e.scrollTop},finish:{scroll:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){window.scrollTo(0,t.scroll)},complete:function(){if(t)t.style.display="block";BX.onCustomEvent(window,"onGoUp")}}).animate()};B24.SearchTitle=function(t){var e=this;this.arParams={AJAX_PAGE:t.AJAX_PAGE,CONTAINER_ID:t.CONTAINER_ID,INPUT_ID:t.INPUT_ID,MIN_QUERY_LEN:parseInt(t.MIN_QUERY_LEN)};if(t.MIN_QUERY_LEN<=0)t.MIN_QUERY_LEN=1;this.cache=[];this.cache_key=null;this.startText="";this.currentRow=-1;this.RESULT=null;this.CONTAINER=null;this.INPUT=null;this.timeout=null;this.CreateResultWrap=function(){if(e.RESULT==null){this.RESULT=document.body.appendChild(document.createElement("DIV"));this.RESULT.className="title-search-result title-search-result-header"}};this.ShowResult=function(t){e.CreateResultWrap();var n=0;var i=0;var s=0;if(BX.browser.IsIE()){n=0;i=1;s=-1;if(/MSIE 7/i.test(navigator.userAgent)){n=-1;i=-1;s=-2}}var o=BX.pos(e.CONTAINER);o.width=o.right-o.left;e.RESULT.style.position="absolute";e.RESULT.style.top=o.bottom+n-1+"px";e.RESULT.style.left=o.left+i+"px";e.RESULT.style.width=o.width+s-2+"px";if(t!=null)e.RESULT.innerHTML=t;if(e.RESULT.innerHTML.length>0)e.RESULT.style.display="block";else e.RESULT.style.display="none"};this.onKeyPress=function(t){e.CreateResultWrap();var n=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(!n)return false;var i=n.rows.length;switch(t){case 27:e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll();return true;case 40:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var s=-1;for(var o=0;o<i;o++){if(!BX.findChild(n.rows[o],{"class":"title-search-separator"},true)){if(s==-1)s=o;if(e.currentRow<o){e.currentRow=o;break}else if(n.rows[o].className=="title-search-selected"){n.rows[o].className=""}}}if(o==i&&e.currentRow!=o)e.currentRow=s;n.rows[e.currentRow].className="title-search-selected";return true;case 38:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var a=-1;for(var o=i-1;o>=0;o--){if(!BX.findChild(n.rows[o],{"class":"title-search-separator"},true)){if(a==-1)a=o;if(e.currentRow>o){e.currentRow=o;break}else if(n.rows[o].className=="title-search-selected"){n.rows[o].className=""}}}if(o<0&&e.currentRow!=o)e.currentRow=a;n.rows[e.currentRow].className="title-search-selected";return true;case 13:if(e.RESULT.style.display=="block"){for(var o=0;o<i;o++){if(e.currentRow==o){if(!BX.findChild(n.rows[o],{"class":"title-search-separator"},true)){var r=BX.findChild(n.rows[o],{tag:"a"},true);if(r){window.location=r.href;return true}}}}}return false}return false};this.onTimeout=function(){if(e.INPUT.value==e.oldValue||e.INPUT.value==e.startText){return}if(e.INPUT.value.length>=e.arParams.MIN_QUERY_LEN){e.oldValue=e.INPUT.value;e.cache_key=e.arParams.INPUT_ID+"|"+e.INPUT.value;if(e.cache[e.cache_key]==null){if(e.timeout)clearInterval(e.timeout);BX.ajax.post(e.arParams.AJAX_PAGE,{ajax_call:"y",INPUT_ID:e.arParams.INPUT_ID,q:e.INPUT.value},function(t){e.cache[e.cache_key]=t;e.ShowResult(t);e.currentRow=-1;e.EnableMouseEvents();e.timeout=setInterval(e.onTimeout,500)})}else{e.ShowResult(e.cache[e.cache_key]);e.currentRow=-1;e.EnableMouseEvents()}}else{e.currentRow=-1;e.UnSelectAll()}};this.UnSelectAll=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var n=t.rows.length;for(var i=0;i<n;i++)t.rows[i].className=""}};this.EnableMouseEvents=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var n=t.rows.length;for(var i=0;i<n;i++)if(!BX.findChild(t.rows[i],{"class":"title-search-separator"},true)){t.rows[i].id="row_"+i;t.rows[i].onmouseover=function(t){if(e.currentRow!=this.id.substr(4)){e.UnSelectAll();this.className="title-search-selected";e.currentRow=this.id.substr(4)}};t.rows[i].onmouseout=function(t){this.className="";e.currentRow=-1}}}};this.onFocusLost=function(t){if(e.RESULT!=null){setTimeout(function(){e.RESULT.style.display="none"},250)}if(e.timeout)clearInterval(e.timeout)};this.onFocusGain=function(){e.CreateResultWrap();if(e.RESULT&&e.RESULT.innerHTML.length)e.ShowResult();this.timeout=setInterval(this.onTimeout,500)};this.onWindowResize=function(){if(e.RESULT!=null){e.ShowResult()}};this.onKeyDown=function(t){t=t||window.event;if(e.RESULT&&e.RESULT.style.display=="block"){if(e.onKeyPress(t.keyCode))return BX.PreventDefault(t)}};this.Init=function(){this.CONTAINER=BX(this.arParams.CONTAINER_ID);this.INPUT=BX(this.arParams.INPUT_ID);this.startText=this.oldValue=this.INPUT.value;BX.bind(this.INPUT,"focus",BX.proxy(this.onFocusGain,this));BX.bind(window,"resize",BX.proxy(this.onWindowResize,this));BX.bind(this.INPUT,"blur",BX.proxy(this.onFocusLost));if(BX.browser.IsSafari()||BX.browser.IsIE())this.INPUT.onkeydown=this.onKeyDown;else this.INPUT.onkeypress=this.onKeyDown};BX.ready(function(){e.Init(t)})};B24.toggleMenu=function(t,e,n){var i=BX.findChild(t.parentNode,{tagName:"ul"},false,false);var s=BX.findChildren(i,{tagName:"li"},false);if(!s)return;var o=BX.findChild(t,{className:"menu-toggle-text"},true,false);if(!o)return;if(BX.hasClass(i,"menu-items-close")){i.style.height="0px";BX.removeClass(i,"menu-items-close");BX.removeClass(BX.nextSibling(BX.nextSibling(t)),"menu-items-close");i.style.opacity=0;a(true,i,i.scrollHeight);o.innerHTML=n;BX.userOptions.save("bitrix24",t.id,"hide","N")}else{a(false,i,i.offsetHeight);o.innerHTML=e;BX.userOptions.save("bitrix24",t.id,"hide","Y")}function a(e,n,i){n.style.overflow="hidden";new BX.easing({duration:200,start:{opacity:e?0:100,height:e?0:i},finish:{opacity:e?100:0,height:e?i:0},transition:BX.easing.transitions.linear,step:function(t){n.style.opacity=t.opacity/100;n.style.height=t.height+"px"},complete:function(){if(!e){BX.addClass(n,"menu-items-close");BX.addClass(BX.nextSibling(BX.nextSibling(t)),"menu-items-close")}n.style.cssText=""}}).animate()}};B24.HelpPopup=function(t,e,n){this.currentStep=null;this.layout={title:null,paging:null,previousButton:null,nextButton:null,link:null,banner:null};this.selectedClass=BX.type.isNotEmptyString(n.selectedClass)?n.selectedClass:"b24-popup-selected";this.steps=[];this.settings=n||{};if(n&&n.video){this.createVideoLayout(t)}else{this.createHelpLayout(t)}var i=BX.type.isNumber(n.defaultStep)?n.defaultStep:0;if(n&&BX.type.isNotEmptyString(n.context)){t:for(var s=0;s<this.steps.length;s++){if(!BX.type.isArray(this.steps[s].patterns)){continue}for(var o=0;o<this.steps[s].patterns.length;o++){if(this.steps[s].patterns[o].test(n.context)){i=s;break t}}}}this.showStepByNumber(i)};B24.HelpPopup.prototype.createHelpLayout=function(t){var e=[];var n=[];if(BX.type.isArray(t)){for(var i=0;i<t.length;i++){var s=t[i];if(!BX.type.isNotEmptyString(s.title)||!BX.type.isNotEmptyString(s.content))continue;var o=BX.create("div",{props:{className:"b24-help-popup-step"},children:[BX.create("div",{props:{className:"b24-help-popup-title"},html:s.title}),BX.create("div",{props:{className:"b24-help-popup-content"},html:s.content})]});var a=BX.create("span",{props:{className:"b24-help-popup-page"},html:i+1,events:{click:BX.proxy(this.onPageClick,this)}});this.steps.push({content:o,page:a});e.push(o);n.push(a)}}this.popup=BX.PopupWindowManager.create("b24-help-popup",null,{closeIcon:{top:"10px",right:"15px"},offsetTop:1,overlay:{opacity:20},lightShadow:true,draggable:{restrict:true},closeByEsc:true,titleBar:{content:BX.create("span",{html:BX.message["B24_HELP_TITLE"]?BX.message("B24_HELP_TITLE"):"Help"})},content:BX.create("div",{props:{className:"b24-help-popup"},children:[BX.create("div",{props:{className:"b24-help-popup-contents"},children:e}),BX.create("div",{props:{className:"b24-help-popup-navigation"},children:[this.layout.paging=BX.create("div",{props:{className:"b24-help-popup-paging"},children:n}),BX.create("div",{props:{className:"b24-help-popup-buttons"},children:[this.layout.previousButton=BX.create("span",{props:{className:"popup-window-button"},events:{click:BX.proxy(this.showPrevStep,this)},children:[BX.create("span",{props:{className:"popup-window-button-left"}}),BX.create("span",{props:{className:"popup-window-button-text"},html:BX.message["B24_HELP_PREV"]?BX.message("B24_HELP_PREV"):"&larr; Back"}),BX.create("span",{props:{className:"popup-window-button-right"}})]}),this.layout.nextButton=BX.create("span",{props:{className:"popup-window-button"},events:{click:BX.proxy(this.showNextStep,this)},children:[BX.create("span",{props:{className:"popup-window-button-left"}}),BX.create("span",{props:{className:"popup-window-button-text"},html:BX.message["B24_HELP_NEXT"]?BX.message("B24_HELP_NEXT"):"Next &rarr;"}),BX.create("span",{props:{className:"popup-window-button-right"}})]})]})]})]})})};B24.HelpPopup.prototype.createVideoLayout=function(t){BX.addCustomEvent(this,"onShowStep",BX.proxy(this.onShowVideoStep,this));var e=[];var n=[];if(BX.type.isArray(t)){t.push({id:"other",patterns:[],learning_path:"",title:this.settings.learning_title,title_full:this.settings.learning_title_full,content:'<div class="b24-video-popup-player"><a class="b24-video-popup-learning-banner" href="http://dev.1c-bitrix.ru/learning/bitrix24/" target="_blank"><img src="/bitrix/templates/bitrix24/images/video-help-bg.png" width="480" height="270"><span class="b24-video-popup-learn-question">'+this.settings.learning_question+'</span><span class="b24-video-popup-learn-answer">'+this.settings.learning_answer+"</span></a></div>"});for(var i=0;i<t.length;i++){var s=t[i];if(BX.type.isNotEmptyString(s.youtube)&&!BX.type.isNotEmptyString(s.content)){s.content='<iframe width="480" height="270" src="https://www.youtube.com/embed/'+s.youtube+'?rel=0&fs=1" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'}if(!BX.type.isNotEmptyString(s.title)||!BX.type.isNotEmptyString(s.content))continue;var o=null;if(s.content.indexOf("iframe")!==-1){o=BX.create("div",{props:{className:"b24-video-popup-step"},children:[BX.create("div",{props:{className:"b24-video-popup-player"},html:s.content})]});var a=o.getElementsByTagName("iframe");if(a.length>0){var r=a[0].getAttribute("src");a[0].setAttribute("data-src",r);a[0].setAttribute("src","")}}else{o=BX.create("div",{props:{className:"b24-video-popup-step"},html:s.content})}var l=BX.create("div",{props:{className:"b24-video-popup-menu-item"},events:{click:BX.proxy(this.onPageClick,this)},children:[BX.create("div",{props:{className:"b24-video-popup-menu-index"},html:i+1+"."}),BX.create("div",{props:{className:"b24-video-popup-menu-title"},html:s.title})]});this.steps.push({title:s.title,title_full:BX.type.isNotEmptyString(s.title_full)?s.title_full:s.title,content:o,learning_path:s.learning_path,page:l,id:BX.type.isNotEmptyString(s.id)?s.id:null,patterns:BX.type.isArray(s.patterns)?s.patterns:[]});e.push(o);n.push(l)}}this.popup=BX.PopupWindowManager.create("b24-video-popup",null,{closeIcon:{top:"20px",right:"20px"},offsetTop:1,overlay:{opacity:20},lightShadow:true,draggable:{restrict:true},closeByEsc:true,events:{onPopupClose:BX.proxy(function(t){this.unsetFrameSrc(this.currentStep);var e=BX.browser.isPropertySupported("transform");var n=BX("help-block");if(!e||!n)return;BX.addClass(t.popupContainer,"b24-help-popup-animation");var i=5;var s={height:t.popupContainer.offsetHeight,scale:100};var o={height:0,scale:i};var a=BX.pos(n);var r=BX.pos(t.popupContainer);s.left=r.left;s.top=r.top;o.left=a.left-(r.width-r.width*(i/100))/2;o.top=a.top-(r.height-r.height*(i/100))/2;new BX.easing({duration:500,start:s,finish:o,transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:BX.proxy(function(n){t.popupContainer.style[e]="scale("+n.scale/100+")";t.popupContainer.style.left=n.left+"px";t.popupContainer.style.top=n.top+"px"},t),complete:BX.proxy(function(){t.popupContainer.style[e]="none";BX.removeClass(t.popupContainer,"b24-help-popup-animation");t.adjustPosition()},t)}).animate()},this),onPopupShow:BX.proxy(function(t){this.setFrameSrc(this.currentStep);var e=BX.browser.isPropertySupported("transform");if(e){t.popupContainer.style.opacity=0;var n=5;new BX.easing({duration:500,start:{opacity:0,scale:n},finish:{opacity:100,scale:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:BX.proxy(function(n){t.popupContainer.style[e]="scale("+n.scale/100+")";t.popupContainer.style.opacity=n.opacity/100},t),complete:BX.proxy(function(){t.popupContainer.style[e]="none";t.adjustPosition();this.scrollToCurrent()},this)}).animate()}},this)},content:BX.create("div",{props:{className:"b24-video-popup"},children:[this.layout.title=BX.create("div",{props:{className:"b24-video-popup-title"}}),BX.create("div",{props:{className:"b24-video-popup-contents"},children:[BX.create("div",{props:{className:"b24-video-popup-menu"},children:n}),BX.create("div",{props:{className:"b24-video-popup-steps "},children:e})]}),BX.create("div",{props:{className:"b24-video-popup-learning"},children:[BX.create("span",{text:this.settings.learning_question+" "}),this.layout.link=BX.create("a",{props:{href:""},text:this.settings.learning_answer,attrs:{target:"_blank"}})]})]})});var u=this.getStepPositionById("other");if(u>=0){this.layout.banner=BX.findChild(this.steps[u].content,{className:"b24-video-popup-learning-banner"},true)}};B24.HelpPopup.prototype.onShowVideoStep=function(t,e){this.setLearningLink(e);if(!t){return}this.setFrameSrc(e);this.unsetFrameSrc(t)};B24.HelpPopup.prototype.setFrameSrc=function(t){if(!t){return}var e=t.content.getElementsByTagName("iframe");if(e.length>0&&e[0].getAttribute("data-src")!=e[0].getAttribute("src")){e[0].setAttribute("src",e[0].getAttribute("data-src"))}};B24.HelpPopup.prototype.unsetFrameSrc=function(t){if(!t){return}var e=t.content.getElementsByTagName("iframe");if(e.length>0){e[0].setAttribute("src","")}};B24.HelpPopup.prototype.setLearningLink=function(t){var e=BX.type.isNotEmptyString(t.learning_path)&&this.settings.currentStepId!=t.id?t.learning_path:window.location.pathname=="/"?"/start/":window.location.pathname;if(BX.type.isNotEmptyString(this.settings.site_dir)&&this.settings.site_dir!="/"){e=e.replace(this.settings.site_dir,"/")}e=e.replace(/\d+/gi,"");e=e.replace("/contacts/","/company/");e=e.replace(/^\/company\/personal\/user\/\/files\/(.*)/,"/docs/");e=e.replace(/^\/workgroups\/group\/\/files\/(.*)/,"/docs/");e=e.replace(/^\/docs\/(.*)/,"/docs/");var n=this.settings.learning_url+"?path="+encodeURIComponent(e);this.layout.link.href=n;if(this.layout.banner){this.layout.banner.href=n}};B24.HelpPopup.prototype.scrollToCurrent=function(){var t=this.currentStep.page.offsetParent;var e=t.offsetHeight;var n=this.currentStep.page;var i=n.offsetTop;var s=n.offsetHeight;if(i+s>e){t.scrollTop=i-e+s}else{t.scrollTop=0}};B24.HelpPopup.prototype.showStepByNumber=function(t){if(!this.steps[t]||this.currentStep==this.steps[t])return;if(this.currentStep!=null){this.currentStep.content.style.display="none";BX.removeClass(this.currentStep.page,this.selectedClass)}this.steps[t].content.style.display="block";BX.addClass(this.steps[t].page,this.selectedClass);if(this.layout.title){this.layout.title.innerHTML=this.steps[t].title_full}BX.onCustomEvent(this,"onShowStep",[this.currentStep,this.steps[t]]);this.currentStep=this.steps[t]};B24.HelpPopup.prototype.onPageClick=function(t){for(var e=0;e<this.steps.length;e++){if(this.steps[e].page==BX.proxy_context){this.showStepByNumber(e);break}}};B24.HelpPopup.prototype.showNextStep=function(){var t=this.getStepPosition(this.currentStep);if(t+1>this.steps.length-1)this.showStepByNumber(0);else this.showStepByNumber(t+1)};B24.HelpPopup.prototype.showPrevStep=function(){var t=this.getStepPosition(this.currentStep);if(t>0)this.showStepByNumber(t-1);else this.showStepByNumber(this.steps.length-1)};B24.HelpPopup.prototype.getStepPosition=function(t){for(var e=0;e<this.steps.length;e++){if(this.steps[e]==t)return e}return-1};B24.HelpPopup.prototype.getStepPositionById=function(t){for(var e=0;e<this.steps.length;e++){if(this.steps[e].id==t)return e}return-1};function showPartnerForm(t){BX=window.BX;BX.Bitrix24PartnerForm={bInit:false,popup:null,arParams:{}};BX.Bitrix24PartnerForm.arParams=t;BX.message(t["MESS"]);BX.Bitrix24PartnerForm.popup=BX.PopupWindowManager.create("BXPartner",null,{autoHide:false,zIndex:0,offsetLeft:0,offsetTop:0,overlay:true,draggable:{restrict:true},closeByEsc:true,titleBar:{content:BX.create("span",{html:BX.message("BX24_PARTNER_TITLE")})},closeIcon:{right:"12px",top:"10px"},buttons:[new BX.PopupWindowButtonLink({text:BX.message("BX24_CLOSE_BUTTON"),className:"popup-window-button-link-cancel",events:{click:function(){this.popupWindow.close()}}})],content:'<div style="width:450px;height:230px"></div>',events:{onAfterPopupShow:function(){this.setContent('<div style="width:450px;height:230px">'+BX.message("BX24_LOADING")+"</div>");BX.ajax.post("/bitrix/tools/b24_site_partner.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",arParams:BX.Bitrix24PartnerForm.arParams},BX.delegate(function(t){this.setContent(t)},this))}}});BX.Bitrix24PartnerForm.popup.show()}B24.Timemanager={inited:false,layout:{block:null,timer:null,info:null,event:null,tasks:null,status:null},data:null,timer:null,clock:null,formatTime:function(t,e){return BX.util.str_pad(parseInt(t/3600),2,"0","left")+":"+BX.util.str_pad(parseInt(t%3600/60),2,"0","left")+(!!e?":"+BX.util.str_pad(t%60,2,"0","left"):"")},formatWorkTime:function(t,e,n){return'<span class="tm-popup-notice-time-hours"><span class="tm-popup-notice-time-number">'+t+'</span></span><span class="tm-popup-notice-time-minutes"><span class="tm-popup-notice-time-number">'+BX.util.str_pad(e,2,"0","left")+'</span></span><span class="tm-popup-notice-time-seconds"><span class="tm-popup-notice-time-number">'+BX.util.str_pad(n,2,"0","left")+"</span></span>"},formatCurrentTime:function(t,e,n){var i="";if(BX.isAmPmMode()){i="AM";if(t>12){t=t-12;i="PM"}else if(t==0){t=12;i="AM"}else if(t==12){i="PM"}i='<span class="time-am-pm">'+i+"</span>"}else t=BX.util.str_pad(t,2,"0","left");return'<span class="time-hours">'+t+"</span>"+'<span class="time-semicolon">:</span>'+'<span class="time-minutes">'+BX.util.str_pad(e,2,"0","left")+"</span>"+i},init:function(t){BX.addCustomEvent("onTimeManDataRecieved",BX.proxy(this.onDataRecieved,this));BX.addCustomEvent("onTimeManNeedRebuild",BX.proxy(this.onDataRecieved,this));BX.addCustomEvent("onPlannerDataRecieved",BX.proxy(this.onPlannerDataRecieved,this));BX.addCustomEvent("onPlannerQueryResult",BX.proxy(this.onPlannerQueryResult,this));BX.addCustomEvent("onTaskTimerChange",BX.proxy(this.onTaskTimerChange,this));BX.timer.registerFormat("worktime_notice_timeman",BX.proxy(this.formatWorkTime,this));BX.timer.registerFormat("bitrix24_time",BX.proxy(this.formatCurrentTime,this));BX.addCustomEvent(window,"onTimemanInit",BX.proxy(function(){this.inited=true;this.layout.block=BX("timeman-block");this.layout.timer=BX("timeman-timer");this.layout.info=BX("timeman-info");this.layout.event=BX("timeman-event");this.layout.tasks=BX("timeman-tasks");this.layout.status=BX("timeman-status");this.layout.statusBlock=BX("timeman-status-block");this.layout.taskTime=BX("timeman-task-time");this.layout.taskTimer=BX("timeman-task-timer");window.BXTIMEMAN.ShowFormWeekly(t);BX.bind(this.layout.block,"click",BX.proxy(this.onTimemanClick,this));BXTIMEMAN.setBindOptions({node:this.layout.block,mode:"popup",popupOptions:{angle:{position:"top",offset:130},offsetTop:10,autoHide:true,offsetLeft:-60,zIndex:-1,events:{onPopupClose:BX.proxy(function(){BX.removeClass(this.layout.block,"timeman-block-active")},this)}}});this.redraw()},this))},onTimemanClick:function(){BX.addClass(this.layout.block,"timeman-block-active");BXTIMEMAN.Open()},onTaskTimerChange:function(t){if(t.action==="refresh_daemon_event"){if(!!this.taskTimerSwitch){this.layout.taskTime.style.display="";if(this.layout.info.style.display!="none"){this.layout.statusBlock.style.display="none"}this.taskTimerSwitch=false}var e="";e+=this.formatTime(parseInt(t.data.TIMER.RUN_TIME||0)+parseInt(t.data.TASK.TIME_SPENT_IN_LOGS||0),true);if(!!t.data.TASK.TIME_ESTIMATE&&t.data.TASK.TIME_ESTIMATE>0){e+=" / "+this.formatTime(parseInt(t.data.TASK.TIME_ESTIMATE))}this.layout.taskTimer.innerHTML=e}else if(t.action==="start_timer"){this.taskTimerSwitch=true}else if(t.action==="stop_timer"){this.layout.taskTime.style.display="none";this.layout.statusBlock.style.display=""}},setTimer:function(){if(this.timer){this.timer.setFrom(new Date(this.data.INFO.DATE_START*1e3));this.timer.dt=-this.data.INFO.TIME_LEAKS*1e3}else{this.timer=BX.timer(this.layout.timer,{from:new Date(this.data.INFO.DATE_START*1e3),dt:-this.data.INFO.TIME_LEAKS*1e3,display:"simple"})}},stopTimer:function(){if(this.timer!=null){BX.timer.stop(this.timer);this.timer=null}},redraw_planner:function(t){if(!!t.TASKS_ENABLED){t.TASKS_COUNT=!t.TASKS_COUNT?0:t.TASKS_COUNT;this.layout.tasks.innerHTML=t.TASKS_COUNT;this.layout.tasks.style.display=t.TASKS_COUNT==0?"none":"inline-block"}if(!!t.CALENDAR_ENABLED){this.layout.event.innerHTML=t.EVENT_TIME;this.layout.event.style.display=t.EVENT_TIME==""?"none":"inline-block"}this.layout.info.style.display=BX.style(this.layout.tasks,"display")=="none"&&BX.style(this.layout.event,"display")=="none"?"none":"block"},redraw:function(){this.redraw_planner(this.data.PLANNER);if(this.data.STATE=="CLOSED"&&(this.data.CAN_OPEN=="REOPEN"||!this.data.CAN_OPEN))this.layout.status.innerHTML=this.getStatusName("COMPLETED");else this.layout.status.innerHTML=this.getStatusName(this.data.STATE);if(!this.timer)this.timer=BX.timer({container:this.layout.timer,display:"bitrix24_time"});var t="";if(this.data.STATE=="CLOSED"){if(this.data.CAN_OPEN=="REOPEN"||!this.data.CAN_OPEN)t="timeman-completed";else t="timeman-start"}else if(this.data.STATE=="PAUSED")t="timeman-paused";else if(this.data.STATE=="EXPIRED")t="timeman-expired";BX.removeClass(this.layout.block,"timeman-completed timeman-start timeman-paused timeman-expired");BX.addClass(this.layout.block,t);if(t=="timeman-start"||t=="timeman-paused"){this.startAnimation()}else{this.endAnimation()}},getStatusName:function(t){return BX.message("TM_STATUS_"+t)},onDataRecieved:function(t){t.OPEN_NOW=false;this.data=t;if(this.inited)this.redraw()},onPlannerQueryResult:function(t,e){if(this.inited)this.redraw_planner(t)},onPlannerDataRecieved:function(t,e){if(this.inited)this.redraw_planner(e)},animation:null,animationTimeout:3e4,blinkAnimation:null,blinkLimit:10,blinkTimeout:750,startAnimation:function(){if(this.animation!==null){this.endAnimation()}this.startBlink();this.animation=setInterval(BX.proxy(this.startBlink,this),this.animationTimeout)},endAnimation:function(){this.endBlink();if(this.animation){clearInterval(this.animation);

}this.animation=null},startBlink:function(){if(this.blinkAnimation!==null){this.endBlink()}var t=0;this.blinkAnimation=setInterval(BX.proxy(function(){if(++t>=this.blinkLimit){clearInterval(this.blinkAnimation);BX.show(BX("timeman-background",true))}else{BX.toggle(BX("timeman-background",true))}},this),this.blinkTimeout)},endBlink:function(){if(this.blinkAnimation){clearInterval(this.blinkAnimation)}BX("timeman-background",true).style.cssText="";this.blinkAnimation=null}};B24.Bitrix24InviteDialog={bInit:false,popup:null,arParams:{}};B24.Bitrix24InviteDialog.Init=function(t){if(t)B24.Bitrix24InviteDialog.arParams=t;if(B24.Bitrix24InviteDialog.bInit)return;BX.message(t["MESS"]);B24.Bitrix24InviteDialog.bInit=true;BX.ready(BX.delegate(function(){B24.Bitrix24InviteDialog.popup=BX.PopupWindowManager.create("B24InviteDialog",null,{autoHide:false,zIndex:0,offsetLeft:0,offsetTop:0,overlay:true,draggable:{restrict:true},closeByEsc:true,titleBar:{content:BX.create("span",{html:BX.message("BX24_INVITE_TITLE_INVITE")})},closeIcon:{right:"12px",top:"10px"},buttons:[],content:'<div style="width:450px;height:230px"></div>',events:{onAfterPopupShow:function(){this.setContent('<div style="width:450px;height:230px">'+BX.message("BX24_LOADING")+"</div>");BX.ajax.post("/bitrix/tools/intranet_invite_dialog.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",arParams:B24.Bitrix24InviteDialog.arParams},BX.delegate(function(t){this.setContent(t)},this))},onPopupClose:function(){BX.InviteDialog.onInviteDialogClose()}}})},this))};B24.Bitrix24InviteDialog.ShowForm=function(t){B24.Bitrix24InviteDialog.Init(t);B24.Bitrix24InviteDialog.popup.params.zIndex=BX.WindowManager?BX.WindowManager.GetZIndex():0;B24.Bitrix24InviteDialog.popup.show()};B24.Bitrix24InviteDialog.ReInvite=function(t){BX.ajax.post("/bitrix/tools/intranet_invite_dialog.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",reinvite:t,sessid:BX.bitrix_sessid()},BX.delegate(function(t){},this))};B24.connectionStatus=function(t){if(!(t=="online"||t=="connecting"||t=="offline"))return false;if(this.b24ConnectionStatusState==t)return false;this.b24ConnectionStatusState=t;var e="";if(t=="offline"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_OFFLINE");e="bx24-connection-status-offline"}else if(t=="connecting"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_CONNECTING");e="bx24-connection-status-connecting"}else if(t=="online"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_ONLINE");e="bx24-connection-status-online"}clearTimeout(this.b24ConnectionStatusTimeout);var n=document.querySelector('[data-role="b24-connection-status"]');if(!n){var i=BX.GetWindowScrollPos();var s=i.scrollTop>60;this.b24ConnectionStatus=BX.create("div",{attrs:{className:"bx24-connection-status "+(this.b24ConnectionStatusState=="online"?"bx24-connection-status-hide":"bx24-connection-status-show bx24-connection-status-"+this.b24ConnectionStatusState)+(s?" bx24-connection-status-float":""),"data-role":"b24-connection-status","data-float":s?"true":"false"},children:[BX.create("div",{props:{className:"bx24-connection-status-wrap"},children:[this.b24ConnectionStatusText=BX.create("span",{props:{className:"bx24-connection-status-text"},html:b24ConnectionStatusStateText}),BX.create("span",{props:{className:"bx24-connection-status-text-reload"},children:[BX.create("span",{props:{className:"bx24-connection-status-text-reload-title"},html:BX.message("BITRIX24_CS_RELOAD")}),BX.create("span",{props:{className:"bx24-connection-status-text-reload-hotkey"},html:BX.browser.IsMac()?"&#8984;+R":"Ctrl+R"})],events:{click:function(){location.reload()}}})]})]})}else{this.b24ConnectionStatus=n}if(!this.b24ConnectionStatus)return false;if(t=="online"){clearTimeout(this.b24ConnectionStatusTimeout);this.b24ConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.b24ConnectionStatus,"bx24-connection-status-show");this.b24ConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.b24ConnectionStatus,"bx24-connection-status-hide")},this),1e3)},this),4e3)}this.b24ConnectionStatus.className="bx24-connection-status bx24-connection-status-show "+e+" "+(this.b24ConnectionStatus.getAttribute("data-float")=="true"?"bx24-connection-status-float":"");this.b24ConnectionStatusText.innerHTML=b24ConnectionStatusStateText;if(!n){var o=BX.findChild(document.body,{className:"bx-layout-inner-table"},true,false);o.parentNode.insertBefore(this.b24ConnectionStatus,o)}return true};
/* End */
;; /* /bitrix/components/bitrix/tasks.iframe.popup/templates/.default/script.min.js?145227747833999*/
; /* /bitrix/components/bitrix/search.title/script.min.js?14522774686196*/
; /* /bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js?14522775326483*/
; /* /bitrix/components/bitrix/socialnetwork.group_create.popup/templates/.default/script.js?14522774743527*/
; /* /bitrix/templates/bitrix24/components/bitrix/menu/vertical_multilevel/script.js?145227753226932*/
; /* /bitrix/templates/bitrix24/bitrix24.min.js?145227753336565*/

//# sourceMappingURL=template_bx24.map.js