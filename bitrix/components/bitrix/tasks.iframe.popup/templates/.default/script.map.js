{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","Tasks","componentIframe","objTemplate","html","createForm","lwPopup","responsibleInputId","oResponsibleSelector","oAccomplicesSelector","oGroupSelector","oLHEditor","oCrmUserField","oWebdavUserField","originalTaskData","editorInited","showCrmField","this","buttonsLocked","initialTaskData","fillEditForm","pTaskData","isPopupJustCreated","title","description","priority","deadline","accomplices","groupId","groupName","bGroupNameAbsent","crmFieldData","taskControl","allowTimeTracking","timeEstimate","timeEstimateHours","timeEstimateMinutes","message","TITLE","DESCRIPTION","PRIORITY","DEADLINE","ACCOMPLICES","GROUP_ID","UF_CRM_TASK","ALLOW_TIME_TRACKING","TASK_CONTROL","TIME_ESTIMATE","Math","floor","round","cleanNode","files","responsibleId","RESPONSIBLE_ID","responsibleName","value","join","loggedInUserId","checked","addClass","parentNode","disabled","removeClass","toString","setContent","setValue","_togglePriority","_setDeadline","setSelected","id","_onGroupSelect","CJSTask","getGroupsData","callback","selfObj","arGroups","bResponsibleNameAbsent","RESPONSIBLE_LAST_NAME","RESPONSIBLE_NAME","setSelectedUsers","name","arUsersInAnotherFormat","i","length","push","_onAccomplicesSelect","usersIds","apply","formatUsersNames","arUsers","slice","callbacks","onAfterPopupCreated","btnHintCreateMultiple","btnHintCreateOnce","hasOwnProperty","webdavFieldData","UF_TASK_WEBDAV_FILES","selectors","__initSelectors","requestedObject","selectedUsersIds","anchorId","bindClickTo","userInputId","multiple","callbackOnSelect","obj","_onResponsibleSelect","btnSelectText","btnCancelText","bindElement","params","attachTo","userFieldName","taskId","callbackOnRedraw","fieldLabel","containerId","__onCrmFieldRedraw","__onWebdavFieldRedraw","style","display","browser","IsMac","e","document","createElement","innerHTML","cmdSymbol","childNodes","nodeValue","onBeforePopupShow","JSON","parse","stringify","call","__checkWarnings","__cleanErrorsArea","IsSafari","getValue","onAfterPopupShow","IsChrome","IsIE11","IsIE","Focus","focus","bind","_processKeyDown","onPopupClose","unbind","onAfterEditorInited","prepareTitleBar","content","create","keyCode","bClose","taskData","gatherTaskDataFromForm","confirm","objPopup","close","bEnterPressed","ctrlKey","metaKey","_submitAndClosePopup","shiftKey","_submitAndCreateOnceMore","_runFullEditForm","ACCOMPLICES_IDS","RESPONSIBLE_SECOND_NAME","taskIFramePopup","add","prepareContent","props","className","arFiles","getElementsByName","filesIds","cnt","split","parseInt","isNaN","getContent","FILES","formField","type","isElementNode","DISK_ATTACHED_OBJECT_ALLOW_EDIT","TASKS_TASK_DISK_ATTACHED_OBJECT_ALLOW_EDIT","__lockButtons","__releaseButtons","columnsIds","tasksListNS","getColumnsOrder","_createTask","onceMore","callbackOnSuccess","objSelf","callbackOnFailure","data","__fillErrorsArea","errMessages","showCreateForm","prepareButtons","newPriority","_clearDeadline","dateSpan","newsubcont","appendChild","newValue","groups","_clearGroup","adjust","text","deleteIcon","findNextSibling","tag","events","click","window","event","input","inputWithGroupName","deselect","_showAccomplicesSelector","showUserSelector","empIDs","bindLink","arUsersCount","children","href","pathToUser","replace","target","substr","arUser","_onFilesUploaded","uniqueID","elem","fileID","firstChild","fileULR","unbindAll","lastChild","_deleteFile","hasClass","nextSibling","sessid","mode","url","ajax","post","remove","PreventDefault","_onFilesChange","filePath","fileTitle","fileName","random","list","items","filenameShort","li","iframeName","iframe","body","originalParent","form","method","action","enctype","encoding","submit","setTimeout","delegate","bShow","bHideTitle","__redrawUserField","targetNode","userFieldNode","nodeId","workFields","util","htmlspecialchars","attrs","cellspacing","width","errorMessages","errsCount","oSelf","checkWarningsId","clearTimeout","TASK","oAjaxReply","dataType","async","responseText"],"mappings":"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"}