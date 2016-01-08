
; /* Start:"a:4:{s:4:"full";s:95:"/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.min.js?145227747423971";s:6:"source";s:75:"/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.js";s:3:"min";s:79:"/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.min.js";s:3:"map";s:79:"/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.map.js";}"*/
BX.CLBlock=function(e){this.arData=new Array;this.arData["Subscription"]=new Array;this.UTPopup=null;this.entity_type=null;this.entity_id=null;this.event_id=null;this.event_id_fullset=false;this.cb_id=null;this.t_val=null;this.ind=null;this.type=null};BX.CLBlock.prototype.DataParser=function(str){str=str.replace(/^\s+|\s+$/g,"");while(str.length>0&&str.charCodeAt(0)==65279)str=str.substring(1);if(str.length<=0)return false;if(str.substring(0,1)!="{"&&str.substring(0,1)!="["&&str.substring(0,1)!="*")str='"*"';eval("arData = "+str);return arData};function __logFilterShow(){if(BX("bx_sl_filter").style.display=="none"){BX("bx_sl_filter").style.display="block";BX("bx_sl_filter_hidden").style.display="none"}else{BX("bx_sl_filter").style.display="none";BX("bx_sl_filter_hidden").style.display="block"}}if(!window.XMLHttpRequest){var XMLHttpRequest=function(){try{return new ActiveXObject("MSXML3.XMLHTTP")}catch(e){}try{return new ActiveXObject("MSXML2.XMLHTTP.3.0")}catch(e){}try{return new ActiveXObject("MSXML2.XMLHTTP")}catch(e){}try{return new ActiveXObject("Microsoft.XMLHTTP")}catch(e){}}}var sonetLXmlHttpGet=new XMLHttpRequest;var sonetLXmlHttpSet=new XMLHttpRequest;var LBlock=new BX.CLBlock;function __logOnAjaxInsertToNode(e){var t=false;if(BX("sonet_log_more_container")){nodeTmp1=BX.findChild(BX("sonet_log_more_container"),{tag:"span",className:"feed-new-message-inf-text"},false);nodeTmp2=BX.findChild(BX("sonet_log_more_container"),{tag:"span",className:"feed-new-message-inf-text-waiting"},false);if(nodeTmp1&&nodeTmp2){nodeTmp1.style.display="none";nodeTmp2.style.display="inline"}t=BX.pos(BX("sonet_log_more_container"));nodeTmp1Cap=document.body.appendChild(BX.create("div",{style:{position:"absolute",width:t.width+"px",height:t.height+"px",top:t.top+"px",left:t.left+"px",zIndex:1e3}}))}if(BX("sonet_log_counter_2_container")){nodeTmp1=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text"},false);nodeTmp2=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text-waiting"},false);if(nodeTmp1&&nodeTmp2){nodeTmp1.style.display="none";nodeTmp2.style.display="inline"}t=BX.pos(BX("sonet_log_more_container"));nodeTmp2Cap=document.body.appendChild(BX.create("div",{style:{position:"absolute",width:t.width+"px",height:t.height+"px",top:t.top+"px",left:t.left+"px",zIndex:1e3}}))}BX.unbind(BX("sonet_log_counter_2_container"),"click",__logOnAjaxInsertToNode)}function sonetLClearContainerExternalNew(){logAjaxMode="new"}function sonetLClearContainerExternalMore(){logAjaxMode="more"}function _sonetLClearContainerExternal(e){if(BX("sonet_log_more_container")){nodeTmp1=BX.findChild(BX("sonet_log_more_container"),{tag:"span",className:"feed-new-message-inf-text"},false);nodeTmp2=BX.findChild(BX("sonet_log_more_container"),{tag:"span",className:"feed-new-message-inf-text-waiting"},false);if(nodeTmp1&&nodeTmp2){nodeTmp1.style.display="inline";nodeTmp2.style.display="none"}}if(BX("sonet_log_counter_2_wrap")){BX.removeClass(BX("sonet_log_counter_2_wrap"),"feed-new-message-informer-anim");BX("sonet_log_counter_2_wrap").style.visibility="hidden"}if(BX("sonet_log_counter_2_container")){nodeTmp1=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text"},false);nodeTmp2=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text-waiting"},false);nodeTmp3=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text-reload"},false);if(nodeTmp1&&nodeTmp2&&nodeTmp3){nodeTmp1.style.display="inline";nodeTmp2.style.display="none";nodeTmp3.style.display="none"}}if(nodeTmp1Cap&&nodeTmp1Cap.parentNode){nodeTmp1Cap.parentNode.removeChild(nodeTmp1Cap)}if(nodeTmp2Cap&&nodeTmp2Cap.parentNode){nodeTmp2Cap.parentNode.removeChild(nodeTmp2Cap)}if(BX("sonet_log_counter_preset")&&logAjaxMode=="new"){BX("sonet_log_counter_preset").style.display="none"}}function __logChangeCounter(e){var t=parseInt(e)<=0;oCounter={iCommentsRead:0};BX.onCustomEvent(window,"onSonetLogChangeCounter",[oCounter]);e-=oCounter.iCommentsRead;__logChangeCounterAnimate(parseInt(e)>0,e,t)}function __logDecrementCounter(e){if(BX("sonet_log_counter_2")){e=parseInt(e);var t=parseInt(BX("sonet_log_counter_2").innerHTML);var n=t-e;if(n>0)BX("sonet_log_counter_2").innerHTML=n;else __logChangeCounterAnimate(false,0)}}function __logChangeCounterAnimate(e,t,n){n=!!n;if(!!window.bLockCounterAnimate){setTimeout(function(){__logChangeCounterAnimate(e,t)},200);return false}e=!!e;if(e){if(BX("sonet_log_counter_2"))BX("sonet_log_counter_2").innerHTML=t;if(BX("sonet_log_counter_2_wrap")){BX("sonet_log_counter_2_wrap").style.visibility="visible";BX.addClass(BX("sonet_log_counter_2_wrap"),"feed-new-message-informer-anim")}}else if(BX("sonet_log_counter_2_wrap")){if(n&&BX.hasClass(BX("sonet_log_counter_2_wrap"),"feed-new-message-informer-anim")){if(BX("sonet_log_counter_2_container")){nodeTmp1=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text"},false);nodeTmp2=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text-waiting"},false);nodeTmp3=BX.findChild(BX("sonet_log_counter_2_container"),{tag:"span",className:"feed-new-message-inf-text-reload"},false);if(nodeTmp1&&nodeTmp2&&nodeTmp3){nodeTmp1.style.display="none";nodeTmp2.style.display="none";nodeTmp3.style.display="inline"}}}else setTimeout(function(){BX.removeClass(BX("sonet_log_counter_2_wrap"),"feed-new-message-informer-anim");BX("sonet_log_counter_2_wrap").style.visibility="hidden"},400)}}function __logChangeCounterArray(e){if(typeof e[BX.message("sonetLCounterType")]!="undefined")__logChangeCounter(e[BX.message("sonetLCounterType")])}function __logShowPostMenu(e,t,n,o,s,i,a,r,l,d){BX.PopupMenu.destroy("post-menu-"+t);var f=null;if(BX.message("sonetLbUseFavorites")!="N"){f={text:l?BX.message("sonetLMenuFavoritesTitleY"):BX.message("sonetLMenuFavoritesTitleN"),className:"menu-popup-no-icon",onclick:function(e){__logChangeFavorites(r,"log_entry_favorites_"+r,l?"N":"Y",true);return BX.PreventDefault(e)}}}var m=[e.getAttribute("data-log-entry-url").length>0?{text:'<span id="post-menu-'+t+'-href-text">'+BX.message("sonetLMenuHref")+"</span>",className:"menu-popup-no-icon feed-entry-popup-menu feed-entry-popup-menu-href",href:e.getAttribute("data-log-entry-url")}:null,e.getAttribute("data-log-entry-url").length>0?{text:'<span id="post-menu-'+t+'-link-text">'+BX.message("sonetLMenuLink")+"</span>",className:"menu-popup-no-icon feed-entry-popup-menu feed-entry-popup-menu-link",onclick:function(){id="post-menu-"+t+"-link",it=BX.proxy_context,height=parseInt(!!it.getAttribute("bx-height")?it.getAttribute("bx-height"):it.offsetHeight);if(it.getAttribute("bx-status")!="shown"){it.setAttribute("bx-status","shown");if(!BX(id)&&!!BX(id+"-text")){var n=BX(id+"-text"),o=BX.pos(n),s=BX.pos(n.parentNode);pos3=BX.pos(BX.findParent(n,{className:"menu-popup-item"},true));o["height"]=s["height"]-1;BX.adjust(it,{attrs:{"bx-height":it.offsetHeight},style:{overflow:"hidden",display:"block"},children:[BX.create("BR"),BX.create("DIV",{attrs:{id:id},children:[BX.create("SPAN",{attrs:{className:"menu-popup-item-left"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-icon"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-text"},children:[BX.create("INPUT",{attrs:{id:id+"-input",type:"text",value:e.getAttribute("data-log-entry-url")},style:{height:o["height"]+"px",width:pos3["width"]-21+"px"},events:{click:function(e){this.select();BX.PreventDefault(e)}}})]})]}),BX.create("SPAN",{className:"menu-popup-item-right"})]})}new BX.fx({time:.2,step:.05,type:"linear",start:height,finish:height*2,callback:BX.delegate(function(e){this.style.height=e+"px"},it)}).start();BX.fx.show(BX(id),.2);BX(id+"-input").select()}else{it.setAttribute("bx-status","hidden");new BX.fx({time:.2,step:.05,type:"linear",start:it.offsetHeight,finish:height,callback:BX.delegate(function(e){this.style.height=e+"px"},it)}).start();BX.fx.hide(BX(id),.2)}}}:null,f,BX.message("sonetLCanDelete")=="Y"?{text:BX.message("sonetLMenuDelete"),className:"menu-popup-no-icon",onclick:function(e){if(confirm(BX.message("sonetLMenuDeleteConfirm"))){__logDelete(r,"log-entry-"+r,t)}return BX.PreventDefault(e)}}:null];if(!!d&&BX.type.isArray(d)){for(var p=0;p<d.length;p++)if(typeof d[p].className=="undefined")d[p].className="menu-popup-no-icon";m=BX.util.array_merge(m,d)}var c={offsetLeft:-14,offsetTop:4,lightShadow:false,angle:{position:"top",offset:50},events:{onPopupShow:function(e){if(BX("log_entry_favorites_"+r)){var n=BX.findChildren(e.contentContainer,{className:"menu-popup-item-text"},true);if(n!=null){for(var o=0;o<n.length;o++){if(n[o].innerHTML==BX.message("sonetLMenuFavoritesTitleY")||n[o].innerHTML==BX.message("sonetLMenuFavoritesTitleN")){var s=n[o];break}}}if(s!=undefined){if(BX.hasClass(BX("log_entry_favorites_"+r),"feed-post-important-switch-active"))BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleY");else BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleN")}}if(BX("post-menu-"+t+"-link")){var i=BX.findChild(e.popupContainer,{className:"feed-entry-popup-menu-link"},true,false);if(i){var a=parseInt(!!i.getAttribute("bx-height")?i.getAttribute("bx-height"):0);if(a>0){BX("post-menu-"+t+"-link").style.display="none";i.setAttribute("bx-status","hidden");i.style.height=a+"px"}}}}}};BX.PopupMenu.show("post-menu-"+t,e,m,c)}function __logCommentFormAutogrow(e){var t=0;if(e&&BX.type.isDomNode(e))var n=e;else{var n=BX.proxy_context;var o=e||window.event;if((o.keyCode==13||o.keyCode==10)&&o.ctrlKey)__logCommentAdd()}var s=BX.findParent(n,{className:"sonet-log-comment-form-place"});if(BX(s))t=BX(s).offsetHeight;var i=0;var a=n.value.split("\n");for(var r=a.length-1;r>=0;--r)i+=Math.floor(a[r].length/CommentFormColsDefault+1);if(i>=CommentFormRowsDefault)n.rows=i+1;else n.rows=CommentFormRowsDefault}function __logGetNextPage(e,t,n){if(oLF.bLoadStarted){return false}oLF.bLoadStarted=true;window.bLockCounterAnimate=true;t=!!t;if(!t&&BX("feed-new-message-inf-wrap")){BX("feed-new-message-inf-wrap").classList.toggle("feed-new-message-anim")}var o={method:"GET",url:e};BX.onCustomEvent("SonetLogBeforeGetNextPage",[o]);if(BX.type.isNotEmptyString(o.url)){e=o.url}BX.ajax({url:e,method:"GET",dataType:"html",data:{},onsuccess:function(e){oLF.bLoadStarted=false;if(!t&&typeof n!="undefined"&&n&&n.parentNode){BX.cleanNode(n.parentNode,true)}window.bLockCounterAnimate=false;if(e.length>0){var o="content_block_"+Math.floor(Math.random()*1e3);BX("log_internal_container").appendChild(BX.create("DIV",{props:{id:o,className:"feed-wrap"},style:{display:t?"none":"block"},html:e}));_sonetLClearContainerExternal(false);if(t){BX("feed-new-message-inf-wrap-first").style.display="block";var s=function(){if(BX(o)){BX(o).style.display="block"}BX.unbind(BX("sonet_log_more_container_first"),"click",s);BX("feed-new-message-inf-wrap-first").style.display="none";__logRecalcMoreButton()};BX.bind(BX("sonet_log_more_container_first"),"click",s)}else{setTimeout(function(){__logRecalcMoreButton()},1e3)}}},onfailure:function(e){oLF.bLoadStarted=false;if(!t&&BX("feed-new-message-inf-wrap")){BX("feed-new-message-inf-wrap").classList.toggle("feed-new-message-anim")}window.bLockCounterAnimate=false;_sonetLClearContainerExternal(false)}});return false}function __logGetNextPageLinkEntities(e,t){if(!!window.__logGetNextPageFormName&&!!e&&!!t&&!!window["UC"]&&!!window["UC"][window.__logGetNextPageFormName]&&!!window["UC"][window.__logGetNextPageFormName].linkEntity){window["UC"][window.__logGetNextPageFormName].linkEntity(e);for(var n in t){if(!!n&&!!t[n])window["UC"][window.__logGetNextPageFormName]["entitiesCorrespondence"][n]=t[n]}}}function __logRefresh(e){if(oLF.bLoadStarted){return}var t=BX("sonet_log_counter_2_wrap",true);oLF.bLoadStarted=true;if(t){var n=BX.findChild(t,{tag:"span",className:"feed-new-message-inf-text-reload"},true);if(n){n.style.display="none"}}window.bLockCounterAnimate=true;BX.ajax({url:e,method:"GET",dataType:"json",data:{},onsuccess:function(e){oLF.bLoadStarted=false;if(typeof e!="undefined"&&typeof e.TEXT!="undefined"&&e.TEXT.length>0){window.bLockCounterAnimate=false;BX.cleanNode("log_internal_container",false);BX("log_internal_container").appendChild(BX.create("DIV",{props:{className:"feed-wrap"},html:e.TEXT}));var n=BX.processHTML(e.TEXT,true);var o=n.SCRIPT;setTimeout(function(){BX.ajax.processScripts(o,true)},500);_sonetLClearContainerExternal(false);window.bStopTrackNextPage=false;if(BX("feed-new-message-inf-wrap-first")){BX("feed-new-message-inf-wrap-first").style.display="none"}if(typeof arCommentsMoreButtonID!="undefined"){arCommentsMoreButtonID=[]}if(t&&BX.hasClass(t,"feed-new-message-informer-fixed")){var s=BX("feed-up-btn-wrap",true);if(s){s.style.display="none";BX.removeClass(s,"feed-up-btn-wrap-anim")}var i=BX.GetWindowScrollPos();new BX.easing({duration:500,start:{scroll:i.scrollTop},finish:{scroll:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){window.scrollTo(0,e.scroll)},complete:function(){if(s)s.style.display="block";BX.onCustomEvent(window,"onGoUp")}}).animate()}}else{oLF.showRefreshError()}},onfailure:function(e){oLF.bLoadStarted=false;oLF.showRefreshError()}});return false}function __logChangeFavorites(e,t,n,o){if(!e||!BX(t)){return}if(!!o){var s=BX.proxy_context;if(!BX.hasClass(BX(s),"menu-popup-item-text")){s=BX.findChild(BX(s),{className:"menu-popup-item-text"},true)}}var i=BX.hasClass(BX(t),"feed-post-important-switch")?BX(t):BX.findChild(BX(t),{className:"feed-post-important-switch"});if(n!=undefined){if(n=="Y"){BX.addClass(BX(i),"feed-post-important-switch-active");BX(i).title=BX.message("sonetLMenuFavoritesTitleY");if(typeof s!="undefined"){BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleY")}}else{BX.removeClass(BX(i),"feed-post-important-switch-active");BX(i).title=BX.message("sonetLMenuFavoritesTitleN");if(typeof s!="undefined"){BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleN")}}}var a=new XMLHttpRequest;a.open("POST",BX.message("sonetLESetPath"),true);a.setRequestHeader("Content-Type","application/x-www-form-urlencoded");a.onreadystatechange=function(){if(a.readyState==4){if(a.status==200){var e=LBlock.DataParser(a.responseText);if(typeof e=="object"){if(e[0]=="*"){if(sonetLErrorDiv!=null){sonetLErrorDiv.style.display="block";sonetLErrorDiv.innerHTML=a.responseText}return}a.abort();var t="";if(e["bResult"]!=undefined&&(e["bResult"]=="Y"||e["bResult"]=="N")){if(e["bResult"]=="Y"){BX.addClass(BX(i),"feed-post-important-switch-active");BX(i).title=BX.message("sonetLMenuFavoritesTitleY");if(s!=undefined)BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleY")}else{BX.removeClass(BX(i),"feed-post-important-switch-active");BX(i).title=BX.message("sonetLMenuFavoritesTitleN");if(s!=undefined)BX(s).innerHTML=BX.message("sonetLMenuFavoritesTitleN")}}}}else{}}};a.send("r="+Math.floor(Math.random()*1e3)+"&"+BX.message("sonetLSessid")+"&site="+BX.util.urlencode(BX.message("sonetLSiteId"))+"&log_id="+encodeURIComponent(e)+"&action=change_favorites")}function __logDelete(e,t,n){if(!e){return}if(!BX(t)){return}BX.ajax({url:BX.message("sonetLESetPath"),method:"POST",dataType:"json",data:{sessid:BX.bitrix_sessid(),site:BX.message("sonetLSiteId"),log_id:e,action:"delete"},onsuccess:function(e){if(e.bResult!=undefined&&e.bResult=="Y"){if(typeof n!="undefined"){BX.PopupMenu.destroy("post-menu-"+n)}__logDeleteSuccess(BX(t))}else{__logDeleteFailure(BX(t))}},onfailure:function(e){__logDeleteFailure(BX(t))}})}function __logDeleteSuccess(e){if(typeof e=="undefined"||!e||!BX(e)){return}new BX.fx({time:.5,step:.05,type:"linear",start:BX(e).offsetHeight,finish:60,callback:BX.delegate(function(e){this.style.height=e+"px"},BX(e)),callback_start:BX.delegate(function(){this.style.overflow="hidden";this.style.minHeight=0},BX(e)),callback_complete:BX.delegate(function(){this.style.marginBottom=0;BX.cleanNode(this);this.appendChild(BX.create("DIV",{props:{className:"feed-add-successfully"},style:{marginLeft:"17px",marginRight:"17px",marginTop:"10px"},children:[BX.create("span",{props:{className:"feed-add-info-text"},children:[BX.create("span",{props:{className:"feed-add-info-icon"}}),BX.create("span",{html:BX.message("sonetLMenuDeleteSuccess")})]})]}))},BX(e))}).start()}function __logDeleteFailure(e){if(typeof e=="undefined"||!e||!BX(e)){return}e.insertBefore(BX.create("DIV",{props:{className:"feed-add-error"},style:{marginLeft:"84px",marginRight:"37px",marginTop:"18px",marginBottom:"4px"},children:[BX.create("span",{props:{className:"feed-add-info-text"},children:[BX.create("span",{props:{className:"feed-add-info-icon"}}),BX.create("span",{html:BX.message("sonetLMenuDeleteFailure")})]})]}),e.firstChild)}function __logOnFeedScroll(){if(window.feedScrollLock==undefined||window.feedScrollLock===false){window.feedScrollLock=true;setTimeout(function(){window.feedScrollLock=false},100);var e=BX.GetWindowSize();if(window.bStopTrackNextPage===undefined||window.bStopTrackNextPage==false){var t=parseInt(e.scrollHeight/2);if(e.scrollHeight-e.innerHeight<t){t=1}if(e.scrollTop>=t&&next_url){window.bStopTrackNextPage=true;__logGetNextPage(next_url,true)}}}var n=BX("sonet_log_counter_2_wrap",true);if(n){var o=n.parentNode.getBoundingClientRect().top;if(o<=0){BX.addClass(n,"feed-new-message-informer-fixed");setTimeout(function(){if(BX.hasClass(n,"feed-new-message-informer-fixed")){BX.addClass(n,"feed-new-message-informer-fix-anim")}},100)}else{BX.removeClass(n,"feed-new-message-informer-fixed feed-new-message-informer-fix-anim")}}}function __logScrollInit(e){if(!!e){BX.unbind(window,"scroll",__logOnFeedScroll);BX.bind(window,"scroll",__logOnFeedScroll)}else{BX.unbind(window,"scroll",__logOnFeedScroll)}}function __logRecalcMoreButton(){if(typeof arMoreButtonID!="undefined"){var e=false;var t=false;var n=false;var o=false;for(var s=0;s<arMoreButtonID.length;s++){e=BX.pos(BX(arMoreButtonID[s].bodyBlockID));if(e.height<280){BX(arMoreButtonID[s].moreButtonBlockID).style.display="none"}if(typeof arMoreButtonID[s].outerBlockID!="undefined"){n=BX(arMoreButtonID[s].outerBlockID);if(n){t=BX.pos(n);if(t.width<e.width){o=BX.findChild(n,{tag:"div",className:"feed-post-text-block-inner"},false);o.style.overflowX="scroll"}}}}}if(typeof arCommentsMoreButtonID!="undefined"){var e=false;for(var s=0;s<arCommentsMoreButtonID.length;s++){e=BX.pos(BX(arCommentsMoreButtonID[s].bodyBlockID));if(e.height<202){BX(arCommentsMoreButtonID[s].moreButtonBlockID).style.display="none"}}}}window.__socOnUCFormClear=function(e){LHEPostForm.reinitDataBefore(e.editorId)};window.__socOnUCFormAfterShow=function(e,t,n){n=!!n?n:{};var o=e.entitiesCorrespondence[e.id.join("-")][0],s=e.entitiesCorrespondence[e.id.join("-")][1];BX.show(BX("feed_comments_block_"+o));BX.onCustomEvent(window,"OnBeforeSocialnetworkCommentShowedUp",["socialnetwork"]);e.form.action=e.url.replace(/\#eId\#/,o).replace(/\#id\#/,s);var i={ENTITY_XML_ID:e.id[0],ENTITY_TYPE:e.entitiesId[e.id[0]][0],ENTITY_ID:e.entitiesId[e.id[0]][1],parentId:e.id[1],comment_post_id:e.entitiesId[e.id[0]][1],edit_id:e.id[1],act:e.id[1]>0?"edit":"add",logId:e.entitiesId[e.id[0]][2]};for(var a in i){if(!e.form[a]){e.form.appendChild(BX.create("INPUT",{attrs:{name:a,type:"hidden"}}))}e.form[a].value=i[a]}__socOnLightEditorShow(t,n)};window.__socOnUCFormSubmit=function(e,t){t["r"]=Math.floor(Math.random()*1e3);t["sessid"]=BX.bitrix_sessid();t["log_id"]=e.entitiesCorrespondence[e.id.join("-")][0];t["p_smile"]=BX.message("sonetLPathToSmile");t["p_ubp"]=BX.message("sonetLPathToUserBlogPost");t["p_gbp"]=BX.message("sonetLPathToGroupBlogPost");t["p_umbp"]=BX.message("sonetLPathToUserMicroblogPost");t["p_gmbp"]=BX.message("sonetLPathToGroupMicroblogPost");t["p_user"]=BX.message("sonetLPathToUser");t["f_id"]=BX.message("sonetLForumID");t["bapc"]=BX.message("sonetLBlogAllowPostCode");t["site"]=BX.message("sonetLSiteId");t["lang"]=BX.message("sonetLLangId");t["nt"]=BX.message("sonetLNameTemplate");t["sl"]=BX.message("sonetLShowLogin");t["as"]=BX.message("sonetLAvatarSizeComment");t["dtf"]=BX.message("sonetLDateTimeFormat");t["message"]=t["REVIEW_TEXT"];t["action"]="add_comment";t["RATING_TYPE"]=BX.message("sonetRatingType");t["pull"]="Y";t["crm"]=BX.message("sonetLIsCRM");e.form["bx-action"]=e.form.action;e.form.action=BX.message("sonetLESetPath")};window.__socOnUCFormResponse=function(e,t){e.form.action=e.form["bx-action"];var n={errorMessage:t},o=e.entitiesCorrespondence[e.id.join("-")][0],s={};if(!(!!t&&typeof t=="object")){}else if(t[0]=="*"){n={errorMessage:BX.message("sonetLErrorSessid")}}else{if(!(t["commentID"]>0)||!!t["strMessage"]){n["errorMessage"]=t["strMessage"]}else{var i=t["arCommentFormatted"],a=t["arComment"],r=!!window["__logBuildRating"]?window["__logBuildRating"](t["arComment"],t["arCommentFormatted"]):null,l=!!a["SOURCE_ID"]?a["SOURCE_ID"]:a["ID"],s={ID:l,ENTITY_XML_ID:e.id[0],FULL_ID:[e.id[0],l],NEW:"N",APPROVED:"Y",POST_TIMESTAMP:t["timestamp"]-BX.message("USER_TZ_OFFSET"),POST_TIME:i["LOG_TIME_FORMAT"],POST_DATE:i["LOG_TIME_FORMAT"],"~POST_MESSAGE_TEXT":i["MESSAGE"],POST_MESSAGE_TEXT:i["MESSAGE_FORMAT"],PANELS:{MODERATE:false},URL:{LINK:a["URL"].length>0?a["URL"]:BX.message("sonetLEPath").replace("#log_id#",a["LOG_ID"])+"?commentId="+a["ID"]+"#com"+(parseInt(a["SOURCE_ID"])>0?a["SOURCE_ID"]:a["ID"])},AUTHOR:{ID:i["USER_ID"],NAME:i["CREATED_BY"]["FORMATTED"],URL:i["CREATED_BY"]["URL"],AVATAR:i["AVATAR_SRC"]},BEFORE_ACTIONS:!!r?r:"",AFTER:i["UF"]};if(typeof t["hasEditCallback"]!="undefined"&&t["hasEditCallback"]=="Y"){s["PANELS"]["EDIT"]="Y";s["URL"]["EDIT"]="__logEditComment('"+e.id[0]+"', '"+a["ID"]+"', '"+a["LOG_ID"]+"');"}if(typeof t["hasDeleteCallback"]!="undefined"&&t["hasDeleteCallback"]=="Y"){s["PANELS"]["DELETE"]="Y";s["URL"]["DELETE"]=BX.message("sonetLESetPath")+"?lang="+BX.message("sonetLLangId")+"&action=delete_comment&delete_comment_id="+a["ID"]+"&post_id="+a["LOG_ID"]+"&site="+BX.message("sonetLSiteId")}n={errorMessage:"",okMessage:"",status:true,message:"",messageCode:i["MESSAGE"],messageId:[e.id[0],l],"~message":"",messageFields:s}}var d=BX("log_entry_follow_"+o,true),f=!!d?d.getAttribute("data-follow")=="Y"?"Y":"N":false;if(f=="N"){BX.findChild(d,{tagName:"a"}).innerHTML=BX.message("sonetLFollowY");d.setAttribute("data-follow","Y")}var d=BX("feed-comments-all-cnt-"+o,true),m=!!d?d.innerHTML.length>0?parseInt(d.innerHTML):0:false;if(m!==false)d.innerHTML=m+1}e.OnUCFormResponseData=n};window.__socOnLightEditorShow=function(e,t){var n={};if(t["arFiles"]){var o={},s,i;for(var a=0;a<t["arFiles"].length;a++){s=BX.findChild(BX("wdif-doc-"+t["arFiles"][a]),{className:"feed-com-file-name"},true);i=BX.findChild(BX("wdif-doc-"+t["arFiles"][a]),{className:"feed-con-file-size"},true);o["F"+a]={FILE_ID:t["arFiles"][a],FILE_NAME:s?s.innerHTML:"noname",FILE_SIZE:i?i.innerHTML:"unknown",CONTENT_TYPE:"notimage/xyz"}}n["UF_SONET_COM_DOC"]={USER_TYPE_ID:"file",FIELD_NAME:"UF_SONET_COM_FILE[]",VALUE:o}}if(t["arDocs"])n["UF_SONET_COM_FILE"]={USER_TYPE_ID:"webdav_element",FIELD_NAME:"UF_SONET_COM_DOC[]",VALUE:BX.clone(t["arDocs"])};if(t["arDFiles"])n["UF_SONET_COM_FILE"]={USER_TYPE_ID:"disk_file",FIELD_NAME:"UF_SONET_COM_DOC[]",VALUE:BX.clone(t["arDFiles"])};LHEPostForm.reinitData(SLEC.editorId,e,n)};BitrixLF=function(){this.bLoadStarted=false};BitrixLF.prototype.showRefreshError=function(){window.bLockCounterAnimate=false;_sonetLClearContainerExternal(false)};BitrixLF.prototype.LazyLoadCheckVisibility=function(e){var t=e.node;var n="comment";var o=BX.findParent(t,{className:"feed-com-text"});if(!o){n="post";o=BX.findParent(t,{className:"feed-post-text-block"})}if(o){var s=BX.findChild(o,{tag:"div",className:"feed-post-text-more"},false);if(s&&s.style.display!="none"){return t.parentNode.parentNode.offsetTop<(n=="comment"?220:270)}}return true};oLF=new BitrixLF;window.oLF=oLF;
/* End */
;
; /* Start:"a:4:{s:4:"full";s:99:"/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.min.js?145227747215269";s:6:"source";s:79:"/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.js";s:3:"min";s:83:"/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.min.js";s:3:"map";s:83:"/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.map.js";}"*/
window["__logCommentsListRedefine"]=function(e,t,s){if(!!window["UC"]&&!!window["UC"][e]){window["UC"][e].send=function(){this.logID=this.nav.getAttribute("bx-sonet-nav-event-id");this.commentID=this.nav.getAttribute("bx-sonet-nav-comment-id");this.commentTS=this.nav.getAttribute("bx-sonet-nav-comment-ts");this.entityType=this.nav.getAttribute("bx-sonet-nav-entity-type");this.ts=this.nav.getAttribute("bx-sonet-nav-ts");this.bFollow=this.nav.getAttribute("bx-sonet-nav-follow");this.status="busy";this.pageNumber=this.nav.getAttribute("bx-sonet-nav-page-number");BX.addClass(this.nav,"feed-com-all-hover");BX.ajax({method:"GET",url:BX.message("sonetLEGetPath")+"?"+BX.ajax.prepareData({sessid:BX.bitrix_sessid(),r:Math.floor(Math.random()*1e3),action:"get_comments",lang:BX.message("sonetLLangId"),site:BX.message("sonetLSiteId"),stid:BX.message("sonetLSiteTemplateId"),nt:BX.message("sonetLNameTemplate"),sl:BX.message("sonetLShowLogin"),dtf:BX.message("sonetLDateTimeFormat"),as:BX.message("sonetLAvatarSizeComment"),p_user:BX.message("sonetLPathToUser"),p_group:BX.message("sonetLPathToGroup"),p_dep:BX.message("sonetLPathToDepartment"),p_le:BX.message("sonetLEPath"),p_smile:BX.message("sonetLPathToSmile"),logid:this.logID,commentID:this.commentID,commentTS:this.commentTS,et:this.entityType,exmlid:e,PAGEN_1:this.pageNumber}),dataType:"json",onsuccess:BX.proxy(function(t){if(!(typeof t=="object"&&!!t)||t[0]=="*"){BX.debug(t)}else{var s=t["arComments"],n="";for(var a in s){var i=Math.floor(Math.random()*1e5)+1,o=s[a]["EVENT_FORMATTED"],r=s[a],l=!!window["__logBuildRating"]?window["__logBuildRating"](r["EVENT"],o,i):null,d=!!r["EVENT"]["SOURCE_ID"]?r["EVENT"]["SOURCE_ID"]:r["EVENT"]["ID"],p={ID:d,ENTITY_XML_ID:this.ENTITY_XML_ID,FULL_ID:[this.ENTITY_XML_ID,d],NEW:this.bFollow&&parseInt(r["LOG_DATE_TS"])>this.ts&&r["EVENT"]["USER_ID"]!=BX.message("sonetLCurrentUserID")?"Y":"N",APPROVED:"Y",POST_TIMESTAMP:r["LOG_DATE_TS"],POST_TIME:r["LOG_TIME_FORMAT"],POST_DATE:r["LOG_DATETIME_FORMAT"],"~POST_MESSAGE_TEXT":o["MESSAGE"],POST_MESSAGE_TEXT:o["FULL_MESSAGE_CUT"],URL:{LINK:r["URL"],EDIT:r["URL_EDIT"],DELETE:BX.message("sonetLESetPath")+"?lang="+BX.message("sonetLLangId")+"&action=delete_comment&delete_comment_id="+r["EVENT"]["ID"]+"&post_id="+this.logID+"&site="+BX.message("sonetLSiteId")},AUTHOR:{ID:r["EVENT"]["USER_ID"],NAME:r["CREATED_BY"]["FORMATTED"],URL:r["CREATED_BY"]["URL"],AVATAR:r["AVATAR_SRC"]},BEFORE_ACTIONS:!!l?l:"",AFTER:o["UF"],PANELS:r["PANELS"]};n+='<div id="record-'+this.ENTITY_XML_ID+"-"+d+'-cover" class="feed-com-block-cover">'+fcParseTemplate({messageFields:p})+"</div>";BX.onCustomEvent(window,"OnUCAddEntitiesCorrespondence",[e+"-"+r["EVENT"]["SOURCE_ID"],[this.logID,r["EVENT"]["ID"]]])}}this.build({status:true,navigation:typeof t["navigation"]!=="undefined"&&t["navigation"].length>0?t["navigation"]:"",navigationNextPageNum:typeof t["navigationNextPageNum"]!=="undefined"&&parseInt(t["navigationNextPageNum"])>0?parseInt(t["navigationNextPageNum"]):null,navigationCounter:typeof t["navigationCounter"]!=="undefined"&&parseInt(t["navigationCounter"])>0?parseInt(t["navigationCounter"]):null,messageList:n})},this),onfailure:BX.delegate(function(e){this.status="ready";BX.debug(e)},this)});return false};window["UC"][e].build=function(e){this.status="ready";this.wait("hide");BX.removeClass(this.nav,"feed-com-all-hover");if(!!e&&e["status"]==true){if(!!e["navigationNextPageNum"]&&!!e["navigationCounter"]&&parseInt(e["navigationNextPageNum"])>0&&parseInt(e["navigationCounter"])>0){this.nav.setAttribute("bx-sonet-nav-page-number",parseInt(e["navigationNextPageNum"]));BX.findChild(this.nav,{className:"feed-com-all-cnt"},true,false).innerHTML=parseInt(e["navigationCounter"])}else{BX.adjust(this.nav,{attrs:{href:"javascript:void(0);","bx-visibility-status":"visible"},html:BX.message("BLOG_C_HIDE")});this.status="done"}var t=!!e["navigation"]?BX.create("DIV",{html:e["navigation"]}):null,s=BX.processHTML(e["messageList"],false);var n=this.container.offsetHeight;if(this.order=="ASC")this.container.innerHTML=this.container.innerHTML+s.HTML;else this.container.innerHTML=s.HTML+this.container.innerHTML;BX.onCustomEvent(window,"OnUCFeedChanged",[[this.ENTITY_XML_ID,this.mid]]);this.display("show",n);BX.defer(function(){BX.ajax.processScripts(s.SCRIPT)})()}}}if(!!window.mplCheckForQuote)BX.bind(BX(t),"mouseup",function(t){mplCheckForQuote(t,this,e,s)})};window["__logBuildRating"]=function(e,t,s){var n="";s=!!s?s:Math.floor(Math.random()*1e5)+1;if(BX.message("sonetLShowRating")=="Y"&&!!e["RATING_TYPE_ID"]>0&&e["RATING_ENTITY_ID"]>0&&(BX.message("sonetLRatingType")=="like"&&!!window["RatingLike"]||BX.message("sonetLRatingType")=="standart_text"&&!!window["Rating"])){if(BX.message("sonetLRatingType")=="like"){var a=e["RATING_USER_VOTE_VALUE"]>0?" bx-you-like":"",i=e["RATING_USER_VOTE_VALUE"]>0?BX.message("sonetLTextLikeN"):BX.message("sonetLTextLikeY"),o=null;if(!!t["ALLOW_VOTE"]&&!!t["ALLOW_VOTE"]["RESULT"])o=BX.create("span",{props:{className:"bx-ilike-text"},html:i});n=BX.create("span",{attrs:{id:"sonet-rating-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s},props:{className:"sonet-log-comment-like rating_vote_text"},children:[BX.create("span",{props:{className:"ilike-light"},children:[BX.create("span",{props:{id:"bx-ilike-button-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s,className:"bx-ilike-button"},children:[BX.create("span",{props:{className:"bx-ilike-right-wrap"+a},children:[BX.create("span",{props:{className:"bx-ilike-right"},html:e["RATING_TOTAL_POSITIVE_VOTES"]})]}),BX.create("span",{props:{className:"bx-ilike-left-wrap"},children:[o]})]}),BX.create("span",{props:{id:"bx-ilike-popup-cont-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s,className:"bx-ilike-wrap-block"},style:{display:"none"},children:[BX.create("span",{props:{className:"bx-ilike-popup"},children:[BX.create("span",{props:{className:"bx-ilike-wait"}})]})]})]})]})}else if(BX.message("sonetLRatingType")=="standart_text"){n=BX.create("span",{attrs:{id:"sonet-rating-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s},props:{className:"sonet-log-comment-like rating_vote_text"},children:[BX.create("span",{props:{className:"bx-rating"+(!t["ALLOW_VOTE"]["RESULT"]?" bx-rating-disabled":"")+(e["RATING_USER_VOTE_VALUE"]!=0?" bx-rating-active":""),id:"bx-rating-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s,title:!t["ALLOW_VOTE"]["RESULT"]?t["ERROR_MSG"]:""},children:[BX.create("span",{props:{className:"bx-rating-absolute"},children:[BX.create("span",{props:{className:"bx-rating-question"},html:!t["ALLOW_VOTE"]["RESULT"]?BX.message("sonetLTextDenied"):BX.message("sonetLTextAvailable")}),BX.create("span",{props:{className:"bx-rating-yes "+(e["RATING_USER_VOTE_VALUE"]>0?"  bx-rating-yes-active":""),title:e["RATING_USER_VOTE_VALUE"]>0?BX.message("sonetLTextCancel"):BX.message("sonetLTextPlus")},children:[BX.create("a",{props:{className:"bx-rating-yes-count",href:"#like"},html:""+parseInt(e["RATING_TOTAL_POSITIVE_VOTES"])}),BX.create("a",{props:{className:"bx-rating-yes-text",href:"#like"},html:BX.message("sonetLTextRatingY")})]}),BX.create("span",{props:{className:"bx-rating-separator"},html:"/"}),BX.create("span",{props:{className:"bx-rating-no "+(e["RATING_USER_VOTE_VALUE"]<0?"  bx-rating-no-active":""),title:e["RATING_USER_VOTE_VALUE"]<0?BX.message("sonetLTextCancel"):BX.message("sonetLTextMinus")},children:[BX.create("a",{props:{className:"bx-rating-no-count",href:"#dislike"},html:""+parseInt(e["RATING_TOTAL_NEGATIVE_VOTES"])}),BX.create("a",{props:{className:"bx-rating-no-text",href:"#dislike"},html:BX.message("sonetLTextRatingN")})]})]})]}),BX.create("span",{props:{id:"bx-rating-popup-cont-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s+"-plus"},style:{display:"none"},children:[BX.create("span",{props:{className:"bx-ilike-popup  bx-rating-popup"},children:[BX.create("span",{props:{className:"bx-ilike-wait"}})]})]}),BX.create("span",{props:{id:"bx-rating-popup-cont-"+e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s+"-minus"},style:{display:"none"},children:[BX.create("span",{props:{className:"bx-ilike-popup  bx-rating-popup"},children:[BX.create("span",{props:{className:"bx-ilike-wait"}})]})]})]})}}if(!!n){n=BX.create("span",{children:[n]});n=n.innerHTML+'<script>window["#OBJ#"].Set("#ID#", "#RATING_TYPE_ID#", #RATING_ENTITY_ID#, "#ALLOW_VOTE#", BX.message("sonetLCurrentUserID"), #TEMPLATE#, "light", BX.message("sonetLPathToUser"));</script>'.replace("#OBJ#",BX.message("sonetLRatingType")=="like"?"RatingLike":"Rating").replace("#ID#",e["RATING_TYPE_ID"]+"-"+e["RATING_ENTITY_ID"]+"-"+s).replace("#RATING_TYPE_ID#",e["RATING_TYPE_ID"]).replace("#RATING_ENTITY_ID#",e["RATING_ENTITY_ID"]).replace("#ALLOW_VOTE#",!!t["ALLOW_VOTE"]&&!!t["ALLOW_VOTE"]["RESULT"]?"Y":"N").replace("#TEMPLATE#",BX.message("sonetLRatingType")=="like"?'{LIKE_Y:BX.message("sonetLTextLikeN"),LIKE_N:BX.message("sonetLTextLikeY"),LIKE_D:BX.message("sonetLTextLikeD")}':'{PLUS:BX.message("sonetLTextPlus"),MINUS:BX.message("sonetLTextMinus"),CANCEL:BX.message("sonetLTextCancel")}')}return n};window["__logShowCommentForm"]=function(e){if(!!window["UC"][e])window["UC"][e].reply()};var waitTimeout=null;var waitDiv=null;var waitPopup=null;var waitTime=500;function __logEventExpand(e){if(BX(e)){BX(e).style.display="none";var t=BX.findParent(BX(e),{tag:"div",className:"feed-post-text-block"});if(t){var s=BX.findChild(t,{tag:"div",className:"feed-post-text-block-inner"},true);var n=BX.findChild(t,{tag:"div",className:"feed-post-text-block-inner-inner"},true);if(s&&n){fxStart=300;fxFinish=n.offsetHeight;new BX.fx({time:1*(n.offsetHeight-fxStart)/(1200-fxStart),step:.05,type:"linear",start:fxStart,finish:fxFinish,callback:BX.delegate(__logEventExpandSetHeight,s),callback_complete:BX.delegate(function(){s.style.maxHeight="none";BX.LazyLoad.showImages(true)})}).start()}}}}function __logCommentExpand(e){if(!BX.type.isDomNode(e))e=BX.proxy_context;if(BX(e)){var t=BX.findParent(BX(e),{tag:"div",className:"feed-com-text"});if(t){BX.remove(e);var s=BX.findChild(t,{tag:"div",className:"feed-com-text-inner"},true);var n=BX.findChild(t,{tag:"div",className:"feed-com-text-inner-inner"},true);if(n&&s){fxStart=200;fxFinish=n.offsetHeight;var a=1*(fxFinish-fxStart)/(2e3-fxStart);if(a<.3)a=.3;if(a>.8)a=.8;new BX.fx({time:a,step:.05,type:"linear",start:fxStart,finish:fxFinish,callback:BX.delegate(__logEventExpandSetHeight,s),callback_complete:BX.delegate(function(){s.style.maxHeight="none"})}).start()}}}}function __logEventExpandSetHeight(e){this.style.maxHeight=e+"px"}function __logShowHiddenDestination(e,t,s){var n=new XMLHttpRequest;n.open("POST",BX.message("sonetLESetPath"),true);n.setRequestHeader("Content-Type","application/x-www-form-urlencoded");n.onreadystatechange=function(){if(n.readyState==4){if(n.status==200){var e=LBlock.DataParser(n.responseText);if(typeof e=="object"){if(e[0]=="*"){if(sonetLErrorDiv!=null){sonetLErrorDiv.style.display="block";sonetLErrorDiv.innerHTML=n.responseText}return}n.abort();var t=e["arDestinations"];if(typeof t=="object"){if(BX(s)){var a=s.parentNode;a.removeChild(s);var i="";for(var o=0;o<t.length;o++){if(typeof t[o]["TITLE"]!="undefined"&&t[o]["TITLE"].length>0){a.appendChild(BX.create("SPAN",{html:",&nbsp;"}));if(typeof t[o]["CRM_PREFIX"]!="undefined"&&t[o]["CRM_PREFIX"].length>0){a.appendChild(BX.create("SPAN",{props:{className:"feed-add-post-destination-prefix"},html:t[o]["CRM_PREFIX"]+":&nbsp;"}))}if(typeof t[o]["URL"]!="undefined"&&t[o]["URL"].length>0){a.appendChild(BX.create("A",{props:{className:"feed-add-post-destination-new"+(typeof t[o]["IS_EXTRANET"]!="undefined"&&t[o]["IS_EXTRANET"]=="Y"?" feed-post-user-name-extranet":""),href:t[o]["URL"]},html:t[o]["TITLE"]}))}else{a.appendChild(BX.create("SPAN",{props:{className:"feed-add-post-destination-new"+(typeof t[o]["IS_EXTRANET"]!="undefined"&&t[o]["IS_EXTRANET"]=="Y"?" feed-post-user-name-extranet":"")},html:t[o]["TITLE"]}))}}}if(e["iDestinationsHidden"]!="undefined"&&parseInt(e["iDestinationsHidden"])>0){e["iDestinationsHidden"]=parseInt(e["iDestinationsHidden"]);if(e["iDestinationsHidden"]%100>10&&e["iDestinationsHidden"]%100<20)var r=5;else var r=e["iDestinationsHidden"]%10;a.appendChild(BX.create("SPAN",{html:"&nbsp;"+BX.message("sonetLDestinationHidden"+r).replace("#COUNT#",e["iDestinationsHidden"])}))}}}}}else{}}};n.send("r="+Math.floor(Math.random()*1e3)+"&"+BX.message("sonetLSessid")+"&site="+BX.util.urlencode(BX.message("SITE_ID"))+"&nt="+BX.util.urlencode(BX.message("sonetLNameTemplate"))+"&log_id="+encodeURIComponent(e)+(t?"&created_by_id="+encodeURIComponent(t):"")+"&p_user="+BX.util.urlencode(BX.message("sonetLPathToUser"))+"&p_group="+BX.util.urlencode(BX.message("sonetLPathToGroup"))+"&p_dep="+BX.util.urlencode(BX.message("sonetLPathToDepartment"))+"&dlim="+BX.util.urlencode(BX.message("sonetLDestinationLimit"))+"&action=get_more_destination")}function __logSetFollow(e){var t=BX("log_entry_follow_"+e,true).getAttribute("data-follow")=="Y"?"Y":"N";var s=t=="Y"?"N":"Y";if(BX("log_entry_follow_"+e,true)){BX.findChild(BX("log_entry_follow_"+e,true),{tagName:"a"}).innerHTML=BX.message("sonetLFollow"+s);BX("log_entry_follow_"+e,true).setAttribute("data-follow",s)}BX.ajax({url:BX.message("sonetLSetPath"),method:"POST",dataType:"json",data:{log_id:e,action:"change_follow",follow:s,sessid:BX.bitrix_sessid(),site:BX.message("sonetLSiteId")},onsuccess:function(s){if(s["SUCCESS"]!="Y"&&BX("log_entry_follow_"+e,true)){BX.findChild(BX("log_entry_follow_"+e,true),{tagName:"a"}).innerHTML=BX.message("sonetLFollow"+t);BX("log_entry_follow_"+e,true).setAttribute("data-follow",t)}},onfailure:function(s){if(BX("log_entry_follow_"+e,true)){BX.findChild(BX("log_entry_follow_"+e,true),{tagName:"a"}).innerHTML=BX.message("sonetLFollow"+t);BX("log_entry_follow_"+e,true).setAttribute("data-follow",t)}}});return false}function __logRefreshEntry(e){var t=e.node!==undefined?BX(e.node):false;var s=e.logId!==undefined?parseInt(e.logId):0;if(!t||s<=0||BX.message("sonetLEPath")===undefined){return}BX.ajax({url:BX.message("sonetLEPath").replace("#log_id#",s),method:"POST",dataType:"json",data:{log_id:s,action:"get_entry"},onsuccess:function(e){if(e["ENTRY_HTML"]!==undefined){BX.cleanNode(t);t.innerHTML=e["ENTRY_HTML"];var s=BX.processHTML(t.innerHTML,true);var n=s.SCRIPT;BX.ajax.processScripts(n,true)}},onfailure:function(e){}});return false}window.__logEditComment=function(e,t,s){BX.ajax({url:BX.message("sonetLESetPath"),method:"POST",dataType:"json",data:{comment_id:t,post_id:s,site:BX.message("sonetLSiteId"),action:"get_comment_src",sessid:BX.bitrix_sessid()},onsuccess:function(s){if(typeof s.message!="undefined"&&typeof s.sourceId!="undefined"){var n={messageBBCode:s.message,messageFields:{arFiles:typeof top["arLogComFiles"+t]!="undefined"?top["arLogComFiles"+t]:[]}};if(top["arLogComDocsType"+t]=="webdav_element"&&typeof top["arLogComDocs"+t]!="undefined")n["messageFields"]["arDocs"]=top["arLogComDocs"+t];if(top["arLogComDocsType"+t]=="disk_file"&&typeof top["arLogComDocs"+t]!="undefined")n["messageFields"]["arDFiles"]=top["arLogComDocs"+t];BX.onCustomEvent(window,"OnUCAfterRecordEdit",[e,s.sourceId,n,"EDIT"])}},onfailure:function(e){}})};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:103:"/bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/script.min.js?145227747223587";s:6:"source";s:83:"/bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/script.js";s:3:"min";s:87:"/bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/script.min.js";s:3:"map";s:87:"/bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/script.map.js";}"*/
(function(){if(window["SBPETabs"])return;window.SBPETabs=function(){if(window.SBPETabs.instance!=null){throw"SBPETabs is a singleton. Use SBPETabs.getInstance to get an object."}this.tabs={};this.bodies={};this.active=null;this.animation=null;this.animationStartHeight=0;this.menu=null;this.menuItems=[];if(this.inited!==true)this.init();window.SBPETabs.instance=this};window.SBPETabs.instance=null;window.SBPETabs.getInstance=function(){if(window.SBPETabs.instance==null){window.SBPETabs.instance=new SBPETabs}return window.SBPETabs.instance};window.SBPETabs.changePostFormTab=function(e,t){var o=window.SBPETabs.getInstance();return o.setActive(e,t)};window.SBPETabs.prototype={_createOnclick:function(e,t,o){return function(){var i=BX("feed-add-post-form-link-more",true);var s=BX("feed-add-post-form-link-text",true);s.innerHTML=t;i.className="feed-add-post-form-link feed-add-post-form-link-more feed-add-post-form-link-active feed-add-post-form-"+e+"-link";window.SBPETabs.changePostFormTab(e);if(BX.type.isNotEmptyString(o)){BX.evalGlobal(o)}this.popupWindow.close()}},init:function(){this.tabContainer=BX("feed-add-post-form-tab");var e=BX.findChildren(this.tabContainer,{tag:"span",className:"feed-add-post-form-link"},true);this.arrow=BX("feed-add-post-form-tab-arrow");this.tabs={};this.bodies={};for(var t=0;t<e.length;t++){var o=e[t].getAttribute("id").replace("feed-add-post-form-tab-","");this.tabs[o]=e[t];if(this.tabs[o].style.display=="none"){this.menuItems.push({tabId:o,text:e[t].getAttribute("data-name"),className:"feed-add-post-form-"+o,onclick:this._createOnclick(o,e[t].getAttribute("data-name"),e[t].getAttribute("data-onclick"))});this.tabs[o]=this.tabs[o].parentNode}this.bodies[o]=BX("feed-add-post-content-"+o)}if(!!this.tabs["file"])this.bodies["file"]=[this.bodies["message"]];if(!!this.tabs["calendar"])this.bodies["calendar"]=[this.bodies["calendar"]];if(!!this.tabs["vote"])this.bodies["vote"]=[this.bodies["message"],this.bodies["vote"]];if(!!this.tabs["more"])this.bodies["more"]=null;if(!!this.tabs["important"])this.bodies["important"]=[this.bodies["message"],this.bodies["important"]];if(!!this.tabs["grat"])this.bodies["grat"]=[this.bodies["message"],this.bodies["grat"]];if(!!this.tabs["lists"])this.bodies["lists"]=[this.bodies["lists"]];for(var i in this.bodies){if(this.bodies.hasOwnProperty(i)&&BX.type.isDomNode(this.bodies[i]))this.bodies[i]=[this.bodies[i]]}this.inited=true;this.previousTab=false;BX("bx-b-uploadfile-blogPostForm").setAttribute("bx-press","pressOut");BX.bind(BX("bx-b-uploadfile-blogPostForm"),"mousedown",BX.delegate(function(){BX("bx-b-uploadfile-blogPostForm").setAttribute("bx-press",BX("bx-b-uploadfile-blogPostForm").getAttribute("bx-press")=="pressOut"?"pressOn":"pressOut")},this));BX.onCustomEvent(this.tabContainer,"onObjectInit",[this]);var s=BX("blogPostForm");if(s){if(!s.changePostFormTab){s.appendChild(BX.create("INPUT",{props:{type:"hidden",name:"changePostFormTab",value:""}}))}BX.addCustomEvent(window,"changePostFormTab",function(e){if(e!="more"){s.changePostFormTab.value=e}});if(s["UF_BLOG_POST_IMPRTNT"]){BX.addCustomEvent(window,"changePostFormTab",function(e){if(e!="more"){s["UF_BLOG_POST_IMPRTNT"].value=e=="important"?1:0}})}}},setActive:function(e,t){if(e==null||this.active==e&&e!="lists")return this.active;else if(!this.tabs[e])return false;var o,i;this.startAnimation();for(o in this.tabs){if(this.tabs.hasOwnProperty(o)&&o!=e){BX.removeClass(this.tabs[o],"feed-add-post-form-link-active");if(this.bodies[o]==null||this.bodies[e]==null)continue;for(i=0;i<this.bodies[o].length;i++){if(this.bodies[e][i]!=this.bodies[o][i])BX.adjust(this.bodies[o][i],{style:{display:"none"}})}}}if(!!this.tabs[e]){this.active=e;BX.addClass(this.tabs[e],"feed-add-post-form-link-active");var s=BX.pos(this.tabs[e],true);this.arrow.style.left=s.left+25+"px";if(this.previousTab=="file"||e=="file"){var a=null,n=null,d=false,l=false,r=BX("divoPostFormLHE_blogPostForm");if(!!r.childNodes&&r.childNodes.length>0){for(o in r.childNodes){if(r.childNodes.hasOwnProperty(o)&&r.childNodes[o].className=="file-selectdialog"){a=r.childNodes[o];var c=BX.findChild(a,{className:"file-placeholder-tbody"},true),f=BX.findChildren(a,{className:"feed-add-photo-block"},true);if(c.rows>0||!!f&&f.length>1)d=true}else if(BX.type.isNotEmptyString(r.childNodes[o].className)&&(r.childNodes[o].className.indexOf("wduf-selectdialog")>=0||r.childNodes[o].className.indexOf("diskuf-selectdialog")>=0)){n=r.childNodes[o];var u=BX.findChildren(n,{className:"wd-inline-file"},true);l=!!u&&u.length>0}else if(BX.type.isElementNode(r.childNodes[o])){BX.adjust(r.childNodes[o],{style:{display:e=="file"?"none":""}})}}if(e=="file"){if(!!window["PlEditorblogPostForm"]){if(!window["PlEditorblogPostForm"]["SBPEBinded"]){window["PlEditorblogPostForm"].SBPEBinded=true;BX.addCustomEvent(window["PlEditorblogPostForm"].eventNode,"onUploadsHasBeenChanged",function(e){if(e.dialogName=="AttachFileDialog"&&e.urlUpload.indexOf("&dropped=Y")<0){e.urlUpload=e.agent.uploadFileUrl=e.urlUpload.replace("&random_folder=Y","&dropped=Y")}BX("bx-b-uploadfile-blogPostForm").setAttribute("bx-press","pressOn");window.SBPETabs.changePostFormTab("message")})}window["PlEditorblogPostForm"].controllerInit("show")}window["PlEditorblogPostForm"].controllerInit("show");BX.addClass(r,"feed-add-post-form");BX.addClass(r,"feed-add-post-edit-form");BX.addClass(r,"feed-add-post-edit-form-file")}else{BX.removeClass(r,"feed-add-post-form");BX.removeClass(r,"feed-add-post-edit-form");BX.removeClass(r,"feed-add-post-edit-form-file");if(!d&&!l&&BX("bx-b-uploadfile-blogPostForm").getAttribute("bx-press")=="pressOut"&&!!window["PlEditorblogPostForm"]){window["PlEditorblogPostForm"].controllerInit("hide")}}}}if(BX("divoPostFormLHE_blogPostForm").style.display=="none"){BX.onCustomEvent(BX("divoPostFormLHE_blogPostForm"),"OnShowLHE",["justShow"])}if(e=="lists"){BX.onCustomEvent("onDisplayClaimLiveFeed",[t])}this.previousTab=e;if(!!this.bodies[e]){for(i=0;i<this.bodies[e].length;i++){BX.adjust(this.bodies[e][i],{style:{display:"block"}})}}}this.endAnimation();if(e!="lists")this.restoreMoreMenu();BX.onCustomEvent(window,"changePostFormTab",[e]);return this.active},startAnimation:function(){if(this.animation)this.animation.stop();var e=BX("microblog-form",true);this.animationStartHeight=e.parentNode.offsetHeight;e.parentNode.style.height=this.animationStartHeight+"px";e.parentNode.style.overflowY="hidden";e.parentNode.style.position="relative";e.style.opacity=0},endAnimation:function(){var e=BX("microblog-form",true);this.animation=new BX["easing"]({duration:500,start:{height:this.animationStartHeight,opacity:0},finish:{height:e.offsetHeight+e.offsetTop,opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){e.parentNode.style.height=t.height+"px";e.style.opacity=t.opacity/100},complete:BX.proxy(function(){e.style.cssText="";e.parentNode.style.cssText="";this.animation=null},this)});this.animation.animate()},collapse:function(){window.SBPETabs.changePostFormTab("message");this.startAnimation();BX.onCustomEvent(BX("divoPostFormLHE_blogPostForm"),"OnShowLHE",[false]);this.endAnimation();this.active=null},showMoreMenu:function(){if(!this.menu){this.menu=BX.PopupMenu.create("feed-add-post-form-popup",BX("feed-add-post-form-link-text"),this.menuItems,{closeByEsc:true,offsetTop:5,offsetLeft:3,angle:true})}this.menu.popupWindow.show()},restoreMoreMenu:function(){var e=this.menuItems.length;if(e<1){return}for(var t=0;t<e;t++){if(this.active==this.menuItems[t]["tabId"]){return}}var o=BX("feed-add-post-form-link-more",true);var i=BX("feed-add-post-form-link-text",true);o.className="feed-add-post-form-link feed-add-post-form-link-more";i.innerHTML=BX.message("SBPE_MORE")},getLists:function(){var e=BX("feed-add-post-form-tab-lists"),t=BX.findChildren(e,{tag:"span",className:"feed-add-post-form-link-lists"},true),o=BX.findChildren(e,{tag:"span",className:"feed-add-post-form-link-lists-default"},true),i=[],s=[];if(t.length){s=this.getMenuItems(t,this.createOnclickLists);i=this.getMenuItemsDefault(o);s=s.concat(i);this.showMoreMenuLists(s)}else{var a=this.showMoreMenuLists,n=this.getMenuItems,d=this.getMenuItemsDefault,l=this.createOnclickLists,r=null;if(BX("bx-lists-select-site-id")){r=BX("bx-lists-select-site-id").value}BX.ajax({method:"POST",dataType:"json",url:"/bitrix/components/bitrix/socialnetwork.blog.post.edit/post.ajax.php",data:{bitrix_processes:1,siteId:r,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(r){if(r.success){for(var c in r.lists){e.appendChild(BX.create("span",{attrs:{"data-name":r.lists[c].NAME,"data-picture":r.lists[c].PICTURE,"data-description":r.lists[c].DESCRIPTION,"data-picture-small":r.lists[c].PICTURE_SMALL,"data-code":r.lists[c].CODE,iblockId:r.lists[c].ID},props:{className:"feed-add-post-form-link-lists",id:"feed-add-post-form-tab-lists"},style:{display:"none"}}))}t=BX.findChildren(e,{tag:"span",className:"feed-add-post-form-link-lists"},true);s=n(t,l);if(!o.length){for(var c in r.permissions){var f;if(c=="new"){f='document.location.href = "'+BX("bx-lists-lists-page").value+'0/edit/"'}else if(c=="market"){if(r.admin&&BX("bx-lists-lists-page")){f='document.location.href = "'+BX("bx-lists-lists-page").value+'?bp_catalog=y"'}else{if(BX("bx-lists-random-string")){f='BX["LiveFeedClass_'+BX("bx-lists-random-string").value+'"].errorPopup("'+BX.message("LISTS_CATALOG_PROCESSES_ACCESS_DENIED")+'");'}}}else if(c=="settings"){f='document.location.href = "'+BX("bx-lists-lists-page").value+'"'}e.appendChild(BX.create("span",{attrs:{"data-name":r.permissions[c],"data-picture-small":"","data-key":c,"data-onclick":f},props:{className:"feed-add-post-form-link-lists-default",id:"feed-add-post-form-tab-lists"},style:{display:"none"}}))}o=BX.findChildren(e,{tag:"span",className:"feed-add-post-form-link-lists-default"},true)}i=d(o);s=s.concat(i);a(s)}else{e.appendChild(BX.create("span",{attrs:{"data-name":r.error,"data-picture-small":""},props:{className:"feed-add-post-form-link-lists-default",id:"feed-add-post-form-tab-lists"},style:{display:"none"}}));t=BX.findChildren(e,{tag:"span",className:"feed-add-post-form-link-lists-default"},true);s=n(t,0);a(s)}})})}},getMenuItems:function(e,t){var o=[];for(var i=0;i<e.length;i++){var s=e[i].getAttribute("id").replace("feed-add-post-form-tab-","");if(t){o.push({tabId:s,text:BX.util.htmlspecialchars(e[i].getAttribute("data-name")),className:"feed-add-post-form-"+s,onclick:t(s,[e[i].getAttribute("iblockId"),e[i].getAttribute("data-name"),e[i].getAttribute("data-description"),e[i].getAttribute("data-picture"),e[i].getAttribute("data-code")])})}else{o.push({tabId:s,text:e[i].getAttribute("data-name"),className:"feed-add-post-form-"+s,onclick:""})}}return o},getMenuItemsDefault:function(e){var t=[];for(var o=0;o<e.length;o++){t.push({text:BX.util.htmlspecialchars(e[o].getAttribute("data-name")),className:"feed-add-post-form-lists-default-"+e[o].getAttribute("data-key"),onclick:e[o].getAttribute("data-onclick")})}return t},showMoreMenuLists:function(e){var t=BX.PopupMenu.create("lists",BX("feed-add-post-form-tab-lists"),e,{closeByEsc:true,offsetTop:5,offsetLeft:12,angle:true});var o=BX.findChildren(BX("popup-window-content-menu-popup-lists"),{tag:"span",className:"menu-popup-item-icon"},true),i=BX.findChildren(BX("feed-add-post-form-tab-lists"),{tag:"span",className:"feed-add-post-form-link-lists"},true),s=BX.findChildren(BX("feed-add-post-form-tab-lists"),{tag:"span",className:"feed-add-post-form-link-lists-default"},true);i=i.concat(s);for(var a=0;a<o.length;a++){if(i[a].getAttribute("data-picture-small")){o[a].innerHTML=i[a].getAttribute("data-picture-small")}}t.popupWindow.show()},createOnclickLists:function(e,t){return function(){window.SBPETabs.changePostFormTab(e,t);this.popupWindow.close()}}};window.BXfpGratSelectCallback=function(e){BXfpGratMedalSelectCallback(e,"grat")};window.BXfpMedalSelectCallback=function(e){BXfpGratMedalSelectCallback(e,"medal")};window.BXfpGratMedalSelectCallback=function(e,t){if(t!="grat")t="medal";var o="U";BX("feed-add-post-"+t+"-item").appendChild(BX.create("span",{attrs:{"data-id":e.id},props:{className:"feed-add-post-"+t+" feed-add-post-destination-users"},children:[BX.create("input",{attrs:{type:"hidden",name:(t=="grat"?"GRAT":"MEDAL")+"["+o+"][]",value:e.id}}),BX.create("span",{props:{className:"feed-add-post-"+t+"-text"},html:e.name}),BX.create("span",{props:{className:"feed-add-post-del-but"},events:{click:function(t){BX.SocNetLogDestination.deleteItem(e.id,"users",window["BXSocNetLogGratFormName"]);BX.PreventDefault(t)},mouseover:function(){BX.addClass(this.parentNode,"feed-add-post-"+t+"-hover")},mouseout:function(){BX.removeClass(this.parentNode,"feed-add-post-"+t+"-hover")}}})]}));BX("feed-add-post-"+t+"-input").value="";BX.SocNetLogDestination.BXfpSetLinkName({formName:t=="grat"?window["BXSocNetLogGratFormName"]:window["BXSocNetLogMedalFormName"],tagInputName:"bx-"+t+"-tag",tagLink1:BX.message("BX_FPGRATMEDAL_LINK_1"),tagLink2:BX.message("BX_FPGRATMEDAL_LINK_2")})};if(!!BX.SocNetGratSelector)return;BX.SocNetGratSelector={popupWindow:null,obWindowCloseIcon:{},sendEvent:true,obCallback:{},gratsContentElement:null,itemSelectedImageItem:{},itemSelectedInput:{},searchTimeout:null,obDepartmentEnable:{},obSonetgroupsEnable:{},obLastEnable:{},obWindowClass:{},obPathToAjax:{},obDepartmentLoad:{},obDepartmentSelectDisable:{},obItems:{},obItemsLast:{},obItemsSelected:{},obElementSearchInput:{},obElementBindMainPopup:{},obElementBindSearchPopup:{}};BX.SocNetGratSelector.init=function(e){if(!e.name)e.name="lm";BX.SocNetGratSelector.obCallback[e.name]=e.callback;BX.SocNetGratSelector.obWindowCloseIcon[e.name]=typeof e.obWindowCloseIcon=="undefined"?true:e.obWindowCloseIcon;BX.SocNetGratSelector.itemSelectedImageItem[e.name]=e.itemSelectedImageItem;BX.SocNetGratSelector.itemSelectedInput[e.name]=e.itemSelectedInput};BX.SocNetGratSelector.openDialog=function(e){if(!e)e="lm";if(BX.SocNetGratSelector.popupWindow!=null){BX.SocNetGratSelector.popupWindow.close();return false}var t=[];for(var o=0;o<arGrats.length;o++){t[t.length]=BX.create("span",{props:{className:"feed-add-grat-box "+arGrats[o].style},attrs:{title:arGrats[o].title},events:{click:BX.delegate(function(t){BX.SocNetGratSelector.selectItem(e,this.code,this.style,this.title);BX.PreventDefault(t)},arGrats[o])}})}var i=[];var s=1;for(o=0;o<t.length;o++){if(o>=t.length/2)s=2;if(i[s]==null||i[s]=="undefined")i[s]=BX.create("div",{props:{className:"feed-add-grat-list-row"}});i[s].appendChild(t[o])}BX.SocNetGratSelector.gratsContentElement=BX.create("div",{children:[BX.create("div",{props:{className:"feed-add-grat-list-title"},html:BX.message("BLOG_GRAT_POPUP_TITLE")}),BX.create("div",{props:{className:"feed-add-grat-list"},children:i})]});BX.SocNetGratSelector.popupWindow=new BX.PopupWindow("BXSocNetGratSelector",BX("feed-add-post-grat-type-selected"),{autoHide:true,offsetLeft:25,bindOptions:{forceBindPosition:true},closeByEsc:true,closeIcon:BX.SocNetGratSelector.obWindowCloseIcon[e]?{top:"5px",right:"10px"}:false,events:{onPopupShow:function(){if(BX.SocNetGratSelector.sendEvent&&BX.SocNetGratSelector.obCallback[e]&&BX.SocNetGratSelector.obCallback[e].openDialog)BX.SocNetGratSelector.obCallback[e].openDialog()},onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){BX.SocNetGratSelector.popupWindow=null;if(BX.SocNetGratSelector.sendEvent&&BX.SocNetGratSelector.obCallback[e]&&BX.SocNetGratSelector.obCallback[e].closeDialog)BX.SocNetGratSelector.obCallback[e].closeDialog()},this)},content:BX.SocNetGratSelector.gratsContentElement,angle:{position:"bottom",offset:20},lightShadow:true});BX.SocNetGratSelector.popupWindow.setAngle({});BX.SocNetGratSelector.popupWindow.show();return true};BX.SocNetGratSelector.selectItem=function(e,t,o,i){var s=BX.findChild(BX.SocNetGratSelector.itemSelectedImageItem[e],{tag:"span"},false,false);if(typeof s!="undefined"&&s){s.className="feed-add-grat-box "+o}BX.SocNetGratSelector.itemSelectedImageItem[e].title=i;BX.SocNetGratSelector.itemSelectedInput[e].value=t;BX.SocNetGratSelector.popupWindow.close()};var e=function(e){var t="blogPostForm",o=BX(t),i="POST_TITLE",s=BX(i),a=BX(t).TAGS,n=function(e){BX.bind(s,"keydown",BX.proxy(e.Init,e));BX.bind(a,"keydown",BX.proxy(e.Init,e))};if(!o)return;BX.addCustomEvent(o,"onAutoSavePrepare",function(e){e.DISABLE_STANDARD_NOTIFY=true;var t=e;setTimeout(function(){n(t)},100)});BX.addCustomEvent(o,"onAutoSave",function(e,o){o["TAGS"]=BX(t).TAGS.value;delete o["POST_MESSAGE"]});if(e=="Y"){BX.addCustomEvent(o,"onAutoSaveRestoreFound",function(e,o){var s=BX.util.trim(o["text"+t])||"",a=BX.util.trim(o[i])||"";if(s.length<1&&a.length<1)return;e.Restore()})}else{BX.addCustomEvent(o,"onAutoSaveRestoreFound",BX.delegate(function(e,o){var s=BX.util.trim(o["text"+t])||"",a=BX.util.trim(o[i])||"";if(s.length<1&&a.length<1)return;var n=BX("microoPostFormLHE_blogPostForm"),d=BX.create("DIV",{attrs:{className:"feed-add-successfully"},children:[BX.create("SPAN",{attrs:{className:"feed-add-info-icon"}}),BX.create("A",{attrs:{className:"feed-add-info-text",href:"#"},events:{click:function(){e.Restore();d.parentNode.removeChild(d);return false}},text:BX.message("BLOG_POST_AUTOSAVE2")})]});if(n){n.parentNode.insertBefore(d,n)}},this))}BX.addCustomEvent(o,"onAutoSaveRestore",function(e,o){BX(i).value=o[i];if(o[i].length>0&&o[i]!=BX(i).getAttribute("placeholder")){if(BX("divoPostFormLHE_blogPostForm").style.display!="none")window["showPanelTitle_"+t](true);else window["bShowTitle"]=true;if(!!BX(i).__onchange)BX(i).__onchange()}var s=window["BXPostFormTags_"+t];if(o["TAGS"].length>0&&s){var a=s.addTag(o["TAGS"]);if(a.length>0){BX.show(s.tagsArea)}}if(BX.SocNetLogDestination){var d;if(o["SPERM[DR][]"]){for(d=0;d<o["SPERM[DR][]"].length;d++){BX.SocNetLogDestination.selectItem(BXSocNetLogDestinationFormName,"",3,o["SPERM[DR][]"][d],"department",false)}}if(o["SPERM[SG][]"]){for(d=0;d<o["SPERM[SG][]"].length;d++){BX.SocNetLogDestination.selectItem(BXSocNetLogDestinationFormName,"",3,o["SPERM[SG][]"][d],"sonetgroups",false)}}if(o["SPERM[U][]"]){for(d=0;d<o["SPERM[U][]"].length;d++){BX.SocNetLogDestination.selectItem(BXSocNetLogDestinationFormName,"",3,o["SPERM[U][]"][d],"users",false)}}if(!o["SPERM[UA][]"]){BX.SocNetLogDestination.deleteItem("UA","groups",BXSocNetLogDestinationFormName)}}n(e)})},t={},o=function(e){if(t[e]&&t[e]["editorID"]){if(t[e]["editor"])t[e]["editor"](t[e]["text"]);else setTimeout(function(){o(e)},50)}};BX.SocnetBlogPostInit=function(i,s){t[i]={editorID:s["editorID"],showTitle:!!s["showTitle"],submitted:false,text:s["text"],autoSave:s["autoSave"],handler:LHEPostForm&&LHEPostForm.getHandler(s["editorID"]),editor:LHEPostForm&&LHEPostForm.getEditor(s["editorID"])};window["showPanelTitle_"+i]=function(e,o){e=e===true||e===false?e:BX("blog-title").style.display=="none";o=o!==false;var s=t[i]["showTitle"],a=BX("lhe_button_title_"+i),n=BX("feed-add-post-block"+i),d=BX("show_title")||{};if(e){BX.show(BX("blog-title"));BX.focus(BX("POST_TITLE"));t[i]["showTitle"]=true;d.value="Y";if(a){BX.addClass(a,"feed-add-post-form-btn-active")}if(n){BX.addClass(n,"blog-post-edit-open")}}else{BX.hide(BX("blog-title"));t[i]["showTitle"]=false;d.value="N";if(a)BX.removeClass(a,"feed-add-post-form-btn-active")}if(o)BX.userOptions.save("socialnetwork","postEdit","showTitle",t[i]["showTitle"]?"Y":"N");else t[i]["showTitle"]=s};window["submitBlogPostForm"]=function(e,o){if(typeof e!="object"){o=e;e=LHEPostForm.getEditor(t[i]["editorID"])}if(e&&e.id==t[i]["editorID"]){if(t[i]["submitted"])return false;e.SaveContent();if(!o)o="save";if(BX("blog-title").style.display=="none")BX("POST_TITLE").value="";if(BX("blog-submit-button-save")){BX.addClass(BX("blog-submit-button-save"),"feed-add-button-load")}BX.submit(BX(i),o);t[i]["submitted"]=true}};var a=function(e,o){if(o==i){t[i]["handler"]=e;BX.addCustomEvent(e.eventNode,"OnControlClick",function(){window.SBPETabs.changePostFormTab("message")});var s=function(){var e=[BX("feed-add-post-form-notice-blockblogPostForm"),BX("feed-add-buttons-blockblogPostForm"),BX("feed-add-post-content-message-add-ins")];for(var o=0;o<e.length;o++){if(!!e[o]){BX.adjust(e[o],{style:{display:"block",height:"auto",opacity:1}})}}if(t[i]["showTitle"])window["showPanelTitle_"+i](true,false)},a=function(){var e,o=[BX("feed-add-post-form-notice-blockblogPostForm"),BX("feed-add-buttons-blockblogPostForm"),BX("feed-add-post-content-message-add-ins")];for(e=0;e<o.length;e++){if(!!o[e]){BX.adjust(o[e],{style:{display:"block",height:"0px",opacity:0}})}}if(t[i]["showTitle"])window["showPanelTitle_"+i](false,false)};BX.addCustomEvent(e.eventNode,"OnAfterShowLHE",s);BX.addCustomEvent(e.eventNode,"OnAfterHideLHE",a);if(e.eventNode.style.display=="none")a();else s()}},n=function(o){if(o.id==t[i]["editorID"]){t[i]["editor"]=o;if(t[i]["autoSave"]!="N")new e(t[i]["autoSave"]);var s=window[o.id+"Files"],a=LHEPostForm.getHandler(o.id),n,d,l,r=[],c=null;for(d in a["controllers"]){if(a["controllers"].hasOwnProperty(d)){if(a["controllers"][d]["parser"]&&a["controllers"][d]["parser"]["bxTag"]=="postimage"){c=a["controllers"][d];break}}}var f=function(e,t){return function(){e.insertFile(t)}},u=function(e,t,o){return function(){if(c){c.deleteFile(t,{});BX.remove(BX("wd-doc"+t));BX.ajax({method:"GET",url:o})}else{e.deleteFile(t,o,e,{controlID:"common"})}}};for(n in s){if(s.hasOwnProperty(n)){if(c){c.addFile(s[n])}else{d=a.checkFile(n,"common",s[n]);r.push(n);if(!!d&&BX("wd-doc"+n)&&!BX("wd-doc"+n).hasOwnProperty("bx-bound")){BX("wd-doc"+n).setAttribute("bx-bound","Y");if((l=BX.findChild(BX("wd-doc"+n),{className:"feed-add-img-wrap"},true,false))&&l){BX.bind(l,"click",f(a,d));l.style.cursor="pointer"}if((l=BX.findChild(BX("wd-doc"+n),{className:"feed-add-img-title"},true,false))&&l){BX.bind(l,"click",f(a,d));l.style.cursor="pointer"}if((l=BX.findChild(BX("wd-doc"+n),{className:"feed-add-post-del-but"},true,false))&&l){BX.bind(l,"click",u(a,n,s[n]["del_url"]));l.style.cursor="pointer"}}}if((l=BX.findChild(BX("wd-doc"+n),{className:"feed-add-post-del-but"},true,false))&&l){BX.bind(l,"click",u(a,n,s[n]["del_url"]));l.style.cursor="pointer"}}}if(r.length>0){o.SaveContent();var m=o.GetContent();m=m.replace(new RegExp("\\&\\#91\\;IMG ID=("+r.join("|")+")([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;","gim"),"[IMG ID=$1$2]");o.SetContent(m);o.Focus()}}};BX.addCustomEvent(window,"onInitialized",a);if(t[i]["handler"])a(t[i]["handler"],i);BX.addCustomEvent(window,"OnEditorInitedAfter",n);if(t[i]["editor"])n(t[i]["editor"]);BX.addCustomEvent(window,"onSocNetLogMoveBody",function(e){if(e=="sonet_log_microblog_container"){o(i)}});BX.ready(function(){if(BX.browser.IsIE()&&BX("POST_TITLE")){var e=function(e){if(!this.value||this.value==this.getAttribute("placeholder")){this.value=this.getAttribute("placeholder");BX.removeClass(this,"feed-add-post-inp-active")}};BX.bind(BX("POST_TITLE"),"blur",e);e.apply(BX("POST_TITLE"));BX("POST_TITLE").__onchange=BX.delegate(function(e){if(this.value==this.getAttribute("placeholder")){this.value=""}if(this.className.indexOf("feed-add-post-inp-active")<0){BX.addClass(this,"feed-add-post-inp-active")}},BX("POST_TITLE"));BX.bind(BX("POST_TITLE"),"click",BX("POST_TITLE").__onchange);BX.bind(BX("POST_TITLE"),"keydown",BX("POST_TITLE").__onchange);BX.bind(BX("POST_TITLE").form,"submit",function(){if(BX("POST_TITLE").value==BX("POST_TITLE").getAttribute("placeholder")){BX("POST_TITLE").value=""}})}if(s["activeTab"]!=="")window.SBPETabs.changePostFormTab(s["activeTab"])})}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:89:"/bitrix/components/bitrix/main.post.form/templates/.default/script.min.js?145227743152683";s:6:"source";s:69:"/bitrix/components/bitrix/main.post.form/templates/.default/script.js";s:3:"min";s:73:"/bitrix/components/bitrix/main.post.form/templates/.default/script.min.js";s:3:"map";s:73:"/bitrix/components/bitrix/main.post.form/templates/.default/script.map.js";}"*/
(function(e){if(e.LHEPostForm)return;var t={controller:{},handler:{}};BX.addCustomEvent(e,"BFileDLoadFormControllerWasBound",function(e){t.controller[e.id]=true});BX.addCustomEvent(e,"WDLoadFormControllerInit",function(e){t.controller[e.CID]=e});BX.addCustomEvent(e,"WDLoadFormControllerWasBound",function(e){t.controller[e.CID]=true});BX.addCustomEvent(e,"DiskDLoadFormControllerInit",function(e){t.controller[e.CID]=e});BX.addCustomEvent(e,"DiskLoadFormControllerWasBound",function(e){t.controller[e.CID]=true});BX.addCustomEvent(e,"OnEditorInitedBefore",function(i){if(t.handler[i.id]){i.__lhe_flags=["OnEditorInitedBefore"];if(t.handler[i.id]["params"]&&t.handler[i.id]["params"]["LHEJsObjName"])e[t.handler[i.id].params["LHEJsObjName"]]=i;t.handler[i.id].OnEditorInitedBefore(i)}});var i=function(e){if(t.handler[e.id]&&t.handler[e.id].editorIsLoaded!=true){t.handler[e.id].editorIsLoaded=true;t.handler[e.id].exec();BX.onCustomEvent(t.handler[e.id],"OnEditorIsLoaded",[t.handler[e.id],e])}};BX.addCustomEvent(e,"OnCreateIframeAfter",i);BX.addCustomEvent(e,"OnEditorInitedAfter",function(e,n){if(t.handler[e.id]){e.__lhe_flags.push("OnEditorInitedAfter");t.handler[e.id].OnEditorInitedAfter(e);if(t.handler[e.id].editorIsLoaded!=true&&n&&e.sandbox&&e.sandbox.inited)i.apply(e,[e])}});BX.util.object_search=function(e,t){for(var i in t){if(t.hasOwnProperty(i)){if(t[i]==e)return true;else if(typeof t[i]=="object"){var n=BX.util.object_search_key(e,t[i]);if(n!==false)return n}}}return false};var n=function(e,t,i){i=i&&i.length>0?i:[];if(typeof t=="object"&&t.length>0){var n;while((n=t.pop())&&n&&t.length>0){i.push(n)}t=n}i.push(t);this.exist=true;this.bxTag=e;this.tag=t;this.tags=i;this.regexp=new RegExp("\\[("+i.join("|")+")=((?:\\s|\\S)*?)(?:\\s*?WIDTH=(\\d+)\\s*?HEIGHT=(\\d+))?\\]","ig");this.code="["+t+"=#ID##ADDITIONAL#]";this.wysiwyg='<span style="color: #2067B0; border-bottom: 1px dashed #2067B0; margin:0 2px;" id="#ID#"#ADDITIONAL#>#NAME#</span>'},o=function(e,i,n){this.CID=this.id=i;this.parser=e.parser["disk_file"]||null;this.params=n;this.node=BX("diskuf-selectdialog-"+i);this.handler=t.controller[i];this.manager=e;this.eventNode=this.manager.eventNode;this.parserName="disk_file";this.prefixNode="disk-edit-attach";this.prefixHTMLNode="disk-attach-";this.props={valueEditClassName:"wd-inline-file",securityCID:"disk-upload-cid"};this.storage="disk";this.fileToAttach={};this.xmlToAttach={};this.events={onInit:"DiskDLoadFormControllerInit",onShow:"DiskLoadFormController",onBound:"DiskLoadFormControllerWasBound"}};o.prototype={parser:false,eventNode:null,values:{},initialized:false,functionsToExec:[],exec:function(e,t){if(typeof e=="function")this.functionsToExec.push([e,t]);if(this.handler&&this.handler!==true){var i;while((i=this.functionsToExec.shift())&&i)i[0].apply(this,i[1])}},init:function(){if(this.initialized!==true){this.values={};this.functionsToExec=[];this.initialized=true;this.bindMainEvents(this.manager);if(this.parser!==null){this.bindEvents(this.manager);return this.initValues()}}return false},initValues:function(){var e=BX.findChildren(this.node,{className:this.props.valueEditClassName},true);if(e&&e.length>0){this.exec(this.runCheckText);return true}return false},bindMainEvents:function(i){var n=null;BX.addCustomEvent(i.eventNode,"onReinitializeBefore",BX.proxy(this.clean,this));BX.addCustomEvent(i.eventNode,"onShowControllers",BX.proxy(function(e){n=e;BX.onCustomEvent(i.eventNode,this.events.onShow,[e])},this));if(!t.controller[this.id]){var o=BX.delegate(function(t){if(t["UID"]==this.id||t["id"]==this.id){if(n==="show"||n==="hide"){BX.onCustomEvent(i.eventNode,this.events.onShow,[n]);n=null}BX.removeCustomEvent(e,this.events.onBound,o)}},this);BX.addCustomEvent(e,this.events.onBound,o)}},bindEvents:function(e){this._catchHandler=BX.delegate(function(t){BX.removeCustomEvent(this.eventNode,this.events.onInit,this._catchHandler);this.handler=t;var i=BX.findChild(BX(e.formID),{attr:{id:this.props.securityCID}},true,false);if(i)i.value=this.handler.CID;this.exec();var n=BX.delegate(function(){BX.onCustomEvent(e.eventNode,"onUploadsHasBeenChanged",arguments)},this);BX.addCustomEvent(this.handler.agent,"onFileIsInited",n);BX.addCustomEvent(this.handler.agent,"ChangeFileInput",n);BX.onCustomEvent(e.eventNode,"onControllerInitialized",[this.id,t])},this);if(this.handler!="object")BX.addCustomEvent(e.eventNode,this.events.onInit,this._catchHandler);else this._catchHandler(this.handler);BX.addCustomEvent(e.eventNode,"OnFileUploadSuccess",BX.delegate(function(e,t){if(this.id==t.CID||this.id==t.id){this.addFile(e,{usePostfix:true})}},this));BX.addCustomEvent(e.eventNode,"OnFileUploadRemove",BX.delegate(function(e,t){if(this.id==t.CID||this.id==t.id){this.deleteFile(e,{usePostfix:true})}},this));BX.addCustomEvent(this,"onFileIsInText",BX.proxy(function(e,t){this.adjustFile(this.checkFile(e),t)},this))},addFile:function(e,t){var i=this.checkFile(e.element_id,e,t);if(i){setTimeout(BX.proxy(function(){this.bindFile(i);this.adjustFile(i,false)},this),100);BX.onCustomEvent(this.eventNode,"onFileIsAdded",[i,this])}return true},checkFile:function(e,t){e=""+(typeof e=="object"?e.id:e);if(typeof t=="object"&&t!==null&&e&&t.element_name&&BX(t.place)){var i={id:e,name:t.element_name,url:t.element_url,type:"isnotimage/xyz",isImage:false,place:BX(t.place,true),xmlID:BX(t.place,true).getAttribute("bx-attach-xml-id"),fileID:BX(t.place,true).getAttribute("bx-attach-file-id")},n;if(/(\.png|\.jpg|\.jpeg|\.gif|\.bmp)$/i.test(t.element_name)&&(n=BX.findChild(i.place,{className:"files-preview",tagName:"IMG"},true,false))&&n){i.type="image/xyz";i.lowsrc=n.src;i.element_url=i.src=n.src.replace(/\Wwidth=(\d+)/,"").replace(/\Wheight=(\d+)/,"");i.isImage=true;i.width=parseInt(n.getAttribute("data-bx-full-width"));i.height=parseInt(n.getAttribute("data-bx-full-height"))}if(i.xmlID)this.xmlToAttach[i.xmlID+""]=e;if(i.fileID)this.fileToAttach[i.fileID+""]=e;this.values[e]=i}return this.values[e]||false},bindFile:function(e){var t=e.place;if(typeof e=="object"&&t&&!t.hasAttribute("bx-file-is-bound")){var i=BX.findChild(t,{className:"f-wrap"},true,false),n=BX.findChild(t,{className:"files-preview"},true,false);if(i){BX.bind(i,"click",BX.delegate(function(){this.insertFile(e.id)},this));i.style.cursor="pointer";i.title=BX.message("MPF_FILE")}if(n){BX.bind(n,"click",BX.delegate(function(){this.insertFile(e.id)},this))}}},adjustFile:function(e,t){var i=e.place;if(t===true||t===false){if(!e.info)e.info=BX.findChild(e.place,{className:"files-info"},true,false);i=e.info;if(BX.type.isDomNode(i)){var n="check-in-text-"+e.id,o=BX(n),s=t===false?{attrs:{"bx-file-is-in-text":"N"},props:{className:"insert-btn"},html:'<span class="insert-btn-text">'+BX.message("MPF_FILE_INSERT_IN_TEXT")+"</span>"}:{attrs:{"bx-file-is-in-text":"Y"},props:{className:"insert-text"},html:'<span class="insert-btn-text">'+BX.message("MPF_FILE_IN_TEXT")+"</span>"};if(!o){s.attrs.id=n;s.events={click:BX.proxy(function(){this.insertFile(e.id)},this)};i.appendChild(BX.create("SPAN",s))}else{BX.adjust(o,s)}}}},insertFile:function(e){BX.onCustomEvent(this.eventNode,"onFileIsInserted",[this.checkFile(e),this])},deleteFile:function(e,t){e=this.checkFile(e,t);if(e){BX.onCustomEvent(this.eventNode,"onFileIsDeleted",[e,this]);this.values[e.id].place=null;delete this.values[e.id].place;this.values[e.id]=null;delete this.values[e.id];e=null;return true}return false},reinitValues:function(e,t){var i,n,o={};while((i=t.pop())&&i){n=BX(this.prefixHTMLNode+i);n=n?n.tagName=="A"?n:BX.findChild(n,{tagName:"IMG"},true):null;if(n){o["E"+i]={type:"file",id:i,name:n.getAttribute("data-bx-title"),size:n.getAttribute("data-bx-size"),sizeInt:n.getAttribute("data-bx-size"),storage:"disk",previewUrl:n.tagName=="A"?"":n.getAttribute("data-bx-src"),fileId:n.getAttribute("bx-attach-file-id")};if(n.hasAttribute("bx-attach-xml-id"))o["E"+i]["xmlId"]=n.getAttribute("bx-attach-xml-id")}}this.handler.selectFile({},{},o);this.runCheckText()},runCheckText:function(){if(!this._checkText)this._checkText=BX.delegate(this.checkText,this);this.manager.exec(this._checkText)},checkText:function(){var e,t=this.manager.getContent(),i=[],n,o;if(t!=""){e=t;for(o in this.xmlToAttach){if(this.xmlToAttach.hasOwnProperty(o)){t=t.replace(new RegExp("\\&\\#91\\;DOCUMENT ID=("+o+")([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;","gim"),"["+this.parser["tag"]+"="+this.xmlToAttach[o]+"$2]").replace(new RegExp("\\[DOCUMENT ID=("+o+")([WIDTHHEIGHT=0-9 ]*)\\]","gim"),"["+this.parser["tag"]+"="+this.xmlToAttach[o]+"$2]")}}for(o in this.fileToAttach){if(this.fileToAttach.hasOwnProperty(o)){t=t.replace(new RegExp("\\&\\#91\\;"+this.parser["tag"]+"=("+o+")([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;","gim"),"["+this.parser["tag"]+"="+this.fileToAttach[o]+"$2]").replace(new RegExp("\\["+this.parser["tag"]+"=("+o+")([WIDTHHEIGHT=0-9 ]*)\\]","gim"),"["+this.parser["tag"]+"="+this.fileToAttach[o]+"$2]")}}n=new RegExp("(?:\\&\\#91\\;)("+this.parser["tags"].join("|")+")=([a-z=0-9 ]+)(?:\\&\\#93\\;)","gim");if(n.test(t)){for(o in this.values){if(this.values.hasOwnProperty(o)){i.push(o)}}if(i.length>0){n=new RegExp("(?:\\&\\#91\\;|\\[)("+this.parser["tags"].join("|")+")=("+i.join("|")+")([WIDTHHEIGHT=0-9 ]*)(?:\\&\\#93\\;|\\])","gim");if(n.test(t))t=t.replace(n,BX.delegate(function(e,t,i,n){return"["+t+"="+i+n+"]"},this))}}if(e!=t)BX.onCustomEvent(this.eventNode,"onFileIsDetected",[t,this])}return t},clean:function(){if(this.handler&&this.handler.values){var e,t,i,n=BX(this.manager.formID);while((e=this.handler.values.pop())&&e){BX.remove(e)}if(this.handler.params&&this.handler.params.controlName){t=BX.findChildren(n,{tagName:"INPUT",attribute:{name:this.handler.params.controlName}},true)}if(t){for(i=0;i<t.length;i++){BX.remove(t[i])}}}},reinit:function(e,t){var i=[],n,o;for(n in t){if(t.hasOwnProperty(n)){if(t[n]["USER_TYPE_ID"]==this.parserName&&t[n]["VALUE"]){for(o in t[n]["VALUE"]){if(t[n]["VALUE"].hasOwnProperty(o)){i.push(t[n]["VALUE"][o])}}}}}if(i.length>0){this.exec(this.reinitValues,[e,i]);return true}return false}};var s=function(e,t,i){s.superclass.constructor.apply(this,arguments);this.parser=e.parser["webdav_element"]||null;this.node=BX("wduf-selectdialog-"+t);this.manager=e;this.parserName="webdav_element";this.prefixNode="wd-doc";this.prefixHTMLNode="wdif-doc-";this.storage="webdav";this.events={onInit:"WDLoadFormControllerInit",onShow:"WDLoadFormController",onBound:"WDLoadFormControllerWasBound"}};BX.extend(s,o);s.prototype.reinitValues=function(e,t){var i,n,o={};this.waitAnswerFromServer=[];while((i=t.pop())&&i){n=BX(this.prefixHTMLNode+i);n=n?n.tagName=="A"?n:BX.findChild(n,{tagName:"IMG"},true):null;if(n){o["E"+i]={type:"file",id:i,name:n.getAttribute("alt"),storage:"webdav",size:n.getAttribute("data-bx-size"),sizeInt:1,ext:"",link:n.getAttribute("data-bx-document")};if(n.hasAttribute("bx-attach-xml-id"))o["E"+i]["xmlId"]=n.getAttribute("bx-attach-xml-id");this.waitAnswerFromServer.push(i)}}if(this.waitAnswerFromServer.length>0){if(!this._defferCheckText)this._defferCheckText=BX.delegate(this.defferCheckText,this);BX.addCustomEvent(this.eventNode,"OnFileUploadSuccess",this._defferCheckText);this.handler.WDFD_SelectFile({},{},o)}};s.prototype.defferCheckText=function(e){var t=BX.util.array_search(e.element_id,this.waitAnswerFromServer);if(t>=0){this.runCheckText();this.waitAnswerFromServer=BX.util.deleteFromArray(this.waitAnswerFromServer,t)}if(this.waitAnswerFromServer.length<=0)BX.removeCustomEvent(this.eventNode,"OnFileUploadSuccess",this._defferCheckText)};var a=function(e,t,i){a.superclass.constructor.apply(this,arguments);this.parser=e.parser["file"]?e.parser["file"]:e.parser["postimage"]["exist"]?e.parser["postimage"]:null;this.postfix=i["postfix"]||"";this.node=BX("file-selectdialog-"+t);this.parserName="file";this.prefixNode="wd-doc";this.prefixHTMLNode="file-doc-";this.props={valueEditClassName:"file-inline-file",securityCID:"upload-cid"};this.storage="bfile";this.events={onInit:"BFileDLoadFormControllerInit",onShow:"BFileDLoadFormController",onBound:"BFileDLoadFormControllerWasBound"}};BX.extend(a,o);a.prototype.initValues=function(e){var t;if(e!==true){t=BX.findChildren(this.node,{className:this.props.valueEditClassName},true);if(t&&t.length>1){this.exec(this.initValues,[true]);return true}return false}t=this.handler.agent.values||[];var i,n,o,s,a={},r="/bitrix/components/bitrix/main.file.input/file.php?mfi_mode=down&cid="+this.handler.CID+"&sessid="+BX.bitrix_sessid();for(var l=0;l<t.length;l++){s=parseInt(t[l].getAttribute("id").replace(this.prefixNode,""));if(a["id"+s])continue;a["id"+s]="Y";if(s>0){n=BX.findChild(t[l],{className:"f-wrap"},true,false);if(!n)continue;o={element_id:s,element_name:n.innerHTML,parser:this.parser.bxTag,storage:"bfile",element_url:r+"&fileID="+s};i=this.addFile(o,{usePostfix:true,hasPreview:false})}}this.runCheckText();return true};a.prototype.checkFile=function(e,t,i){e=""+(typeof e=="object"?e.id:e);e=e+(i&&i["usePostfix"]===true?this.postfix:"");if(typeof t=="object"&&t!==null&&e&&t.element_name&&BX(this.prefixNode+t.element_id,true)){var n={id:e,name:t.element_name,url:t.element_url,type:"isnotimage/xyz",isImage:false,place:BX(this.prefixNode+t.element_id,true)},o;if((t["element_type"]&&t["element_type"].indexOf("image/")===0||/(\.png|\.jpg|\.jpeg|\.gif|\.bmp)$/i.test(t.element_name))&&((o=BX.findChild(n.place,{tagName:"IMG"},true,false))&&o||i&&i["hasPreview"]===false)){n.type="image/xyz";n.src=t["element_thumbnail"]||t["element_url"];n.isImage=true;n.hasPreview=false;n.lowsrc="";n.width="";n.height="";if(BX(o)){n.hasPreview=true;n.lowsrc=t["element_thumbnail"]||o["src"];n.width=parseInt(o.getAttribute("data-bx-full-width"));n.height=parseInt(o.getAttribute("data-bx-full-height"))}}else if(this.parser.bxTag=="postimage"){return false}this.values[e]=n}return this.values[e]||false};a.prototype.bindFile=function(e){var t=e&&e["place"]?e["place"]:null;if(typeof e=="object"&&t&&!t.hasAttribute("bx-file-is-bound")){if(e.isImage&&e.hasPreview){var i=BX.findChild(t,{className:"feed-add-img-title"},true,false),n=BX.findChild(t,{className:"feed-add-img-wrap"},true,false);if(n){BX.bind(n,"click",BX.proxy(function(){this.insertFile(e)},this));n.style.cursor="pointer";n.title=BX.message("MPF_IMAGE")}if(i){BX.bind(i,"click",BX.delegate(function(){this.insertFile(e)},this));i.style.cursor="pointer";i.title=BX.message("MPF_IMAGE")}}else a.superclass.bindFile.apply(this,arguments)}};a.prototype.clean=function(){a.superclass.clean.apply(this,arguments);if(this["handler"]&&this.handler["agent"]&&this.handler.agent["inputName"]){var e,t,i,n=BX(this.manager.formID);t=BX.findChildren(n,{tagName:"INPUT",attribute:{name:this.handler.agent.inputName+"[]"}},true);if(t){for(i=0;i<t.length;i++){BX.remove(t[i])}}}};e.LHEPostForm=function(e,i){this.params=i;this.formID=e;this.oEditorId=i["LHEJsObjId"];this.__divId=i["LHEJsObjName"]||i["LHEJsObjId"];t.handler[this.oEditorId]=this;this.oEditor=LHEPostForm.getEditor(this.oEditorId);this.eventNode=BX("div"+this.__divId);BX.addCustomEvent(this.eventNode,"OnShowLHE",BX.delegate(this.OnShowLHE,this));BX.addCustomEvent(this.eventNode,"OnButtonClick",BX.delegate(this.OnButtonClick,this));BX.addCustomEvent(this.eventNode,"OnAfterShowLHE",function(e,t){if(t.oEditor&&t.oEditor["AllowBeforeUnloadHandler"])t.oEditor.AllowBeforeUnloadHandler();if(t.monitoringWakeUp===true)t.monitoringStart()});BX.addCustomEvent(this.eventNode,"OnAfterHideLHE",function(e,t){t.monitoringWakeUp=t.monitoringStop();if(t.oEditor&&t.oEditor["DenyBeforeUnloadHandler"])t.oEditor.DenyBeforeUnloadHandler()});this.initParsers(i);this.initFiles(e,i);BX.ready(BX.delegate(function(){if(BX("lhe_button_submit_"+e,true)){BX.bind(BX("lhe_button_submit_"+e,true),"mousedown",function(){BX.addClass(this,"feed-add-button-press")});BX.bind(BX("lhe_button_submit_"+e,true),"mouseup",function(){BX.removeClass(this,"feed-add-button-press")});BX.bind(BX("lhe_button_submit_"+e,true),"click",BX.proxy(function(e){BX.onCustomEvent(this.eventNode,"OnButtonClick",["submit"]);return BX.PreventDefault(e)},this))}if(BX("lhe_button_cancel_"+e,true)){BX.bind(BX("lhe_button_cancel_"+e,true),"mousedown",function(){BX.addClass(this,"feed-add-button-press")});BX.bind(BX("lhe_button_cancel_"+e,true),"mouseup",function(){BX.removeClass(this,"feed-add-button-press")});BX.bind(BX("lhe_button_cancel_"+e,true),"click",BX.proxy(function(e){BX.onCustomEvent(this.eventNode,"OnButtonClick",["cancel"]);return BX.PreventDefault(e)},this))}},this));this.inited=true;BX.addCustomEvent(BX(this.formID),"onAutoSavePrepare",function(e){e.FORM.setAttribute("bx-lhe-autosave-prepared","Y")});BX.onCustomEvent(this,"onInitialized",[this,e,i,this.parsers]);BX.onCustomEvent(this.eventNode,"onInitialized",[this,e,i,this.parsers]);if(this.oEditor&&this.oEditor.inited&&!this.oEditor["__lhe_flags"]){BX.onCustomEvent(this.oEditor,"OnEditorInitedBefore",[this.oEditor]);BX.onCustomEvent(this.oEditor,"OnEditorInitedAfter",[this.oEditor,true])}};e.LHEPostForm.prototype={editorIsLoaded:false,arFiles:{},parser:{},controllers:{},exec:function(e,t){this.functionsToExec=this.functionsToExec||[];if(typeof e=="function")this.functionsToExec.push([e,t]);if(this.editorIsLoaded==true){var i;while((i=this.functionsToExec.shift())&&i)i[0].apply(this,i[1])}},initParsers:function(e){this.parser={postimage:{exist:false,bxTag:"postimage",tag:"IMG ID",tags:["IMG ID"],regexp:/\[(IMG ID)=((?:\s|\S)*?)(?:\s*?WIDTH=(\d+)\s*?HEIGHT=(\d+))?\]/gi,code:"[IMG ID=#ID##ADDITIONAL#]",wysiwyg:'<img id="#ID#" src="'+'#SRC#" lowsrc="'+'#LOWSRC#" title=""#ADDITIONAL# />'}};var t=e["parsers"]?e["parsers"]:{};for(var i in t){if(t.hasOwnProperty(i)&&/[a-z]/gi.test(i+"")){this.parser[i]=new n(i,t[i])}}if(BX.util.object_search("UploadImage",t)){this.parser["postimage"]["exist"]=true}if(typeof e["arSize"]=="object"){var o="";if(e["arSize"]["width"])o+="max-width:"+e["arSize"]["width"]+"px;";if(e["arSize"]["height"])o+="max-height:"+e["arSize"]["height"]+"px;";if(o!=="")this.parser["postimage"]["wysiwyg"]=this.parser["postimage"]["wysiwyg"].replace("#ADDITIONAL#",'style="'+o+'" #ADDITIONAL#')}},initFiles:function(t,i){this.arFiles={};this.controllers={common:{postfix:"",storage:"bfile",parser:"postimage",node:e,obj:null,init:false}};if(!i["CID"]||typeof i["CID"]!=="object")return;BX.addCustomEvent(this.eventNode,"onFileIsAdded",BX.delegate(this.OnFileUploadSuccess,this));BX.addCustomEvent(this.eventNode,"onFileIsDeleted",BX.delegate(this.OnFileUploadRemove,this));BX.addCustomEvent(this.eventNode,"onFileIsDetected",BX.delegate(this.setContent,this));BX.addCustomEvent(this.eventNode,"onFileIsInserted",BX.delegate(this.insertFile,this));var n,r,l;for(r in i["CID"]){if(i["CID"].hasOwnProperty(r)){n=i["CID"][r]["parser"];if(n=="disk_file")this.controllers[r]=new o(this,r,i["CID"][r]);else if(n=="webdav_element")this.controllers[r]=new s(this,r,i["CID"][r]);else if(n=="file")this.controllers[r]=new a(this,r,i["CID"][r]);if(this.controllers[r]&&this.controllers[r].init()&&!l)l=true}}BX.ready(BX.delegate(function(){BX.bind(BX("bx-b-uploadfile-"+t),"click",BX.proxy(this.controllerInit,this));if(l)this.controllerInit("show")},this))},controllerInit:function(e){this.controllerInitStatus=e=="show"||e=="hide"?e:this.controllerInitStatus=="show"?"hide":"show";BX.onCustomEvent(this.eventNode,"onShowControllers",[this.controllerInitStatus])},getContent:function(){return this.oEditor?this.oEditor.GetContent():""},setContent:function(e){if(this.oEditor)this.oEditor.SetContent(e)},OnFileUploadSuccess:function(e,t,i){if(this.controllers[t.id]){var n=t.parser.bxTag+e.id;this.arFiles[n]=this.arFiles[n]||[];this.arFiles[n].push(t.id);if(i===true&&e.isImage&&this.insertImageAfterUpload){if(!this._insertFile)this._insertFile=BX.delegate(this.insertFile,this);this.exec(this._insertFile,arguments)}}},OnFileUploadRemove:function(e,t){if(this.controllers[t.id]){var i=t.parser.bxTag+e.id;if(this.arFiles[i]){var n=BX.util.array_search(t.id,this.arFiles[i]);this.arFiles[i]=BX.util.deleteFromArray(this.arFiles[i],n);if(!this.arFiles[i]||this.arFiles[i].length<=0){this.arFiles[i]=null;delete this.arFiles[i];if(!this._deleteFile)this._deleteFile=BX.delegate(this.deleteFile,this);this.exec(this._deleteFile,arguments)}}}},showPanelEditor:function(e,t){if(e==undefined)e=!this.oEditor.toolbar.IsShown();this.params.showPanelEditor=e;var i=BX("lhe_button_editor_"+this.formID),n=BX("panel-close"+this.__divId);if(n){this.oEditor.dom.cont.appendChild(n)}if(e){this.oEditor.dom.toolbarCont.style.opacity="inherit";this.oEditor.toolbar.Show();if(i)BX.addClass(i,"feed-add-post-form-btn-active");if(n)n.style.display=""}else{this.oEditor.toolbar.Hide();if(i)BX.removeClass(i,"feed-add-post-form-btn-active");if(n)n.style.display="none"}if(t!==false)BX.userOptions.save("main.post.form","postEdit","showBBCode",e?"Y":"N")},monitoring:{interval:null,text:"",savedText:"",files:[],savedFiles:[]},monitoringStart:function(){if(this.monitoring.interval===null){if(!this._monitoringStart){this._monitoringStart=BX.delegate(this.checkFilesInText,this);BX.addCustomEvent(this.oEditor,"OnContentChanged",BX.proxy(function(e){this.monitoring.text=e},this))}this.monitoring.interval=setInterval(this._monitoringStart,1e3)}},monitoringStop:function(){var e=this.monitoring.interval!==null;if(this.monitoring.interval!==null)clearInterval(this.monitoring.interval);this.monitoring.interval=null;return e},monitoringSetStatus:function(e,t,i){if(this.arFiles[e+t]){var n;for(var o=0;o<this.arFiles[e+t].length;o++){this.monitoring.files.push(e+t);n=this.arFiles[e+t][o];BX.onCustomEvent(this.controllers[n],"onFileIsInText",[t,i])}}},checkFilesInText:function(){if(this.monitoring.text!==this.monitoring.savedText){this.monitoring.savedText=this.monitoring.text;this.monitoring.files=[];var e=this.monitoring.savedText,t,i=function(e,t){return function(i,n,o){e.monitoring.files.push([t,o].join("/"))}};for(t in this.parser){if(this.parser.hasOwnProperty(t)){if(!this.parser[t]["checkFilesInText"]){this.parser[t]["checkFilesInText"]=i(this,t)}e.replace(this.parser[t]["regexp"],this.parser[t]["checkFilesInText"])}}if(this.monitoring.savedFiles.join(",")!=this.monitoring.files.join(",")){var n=this.monitoring.files.join("|")+"|",o;for(t=0;t<this.monitoring.savedFiles.length;t++){o=this.monitoring.savedFiles[t];if(n.indexOf(o+"|")>=0)n=n.replace(o+"|","");else{o=o.split("/");this.monitoringSetStatus(o[0],o[1],false)}}n=n.substring(0,n.length-1).split("|");for(t=0;t<n.length;t++){o=o=n[t].split("/");this.monitoringSetStatus(o[0],o[1],true)}}this.monitoring.savedFiles=this.monitoring.files;if(this.monitoring.savedFiles.length<=0)this.monitoringStop()}},checkFile:function(e,t){var i=false;if(typeof e=="string"){var n=typeof t=="string"?t:t.parser;if(!!this.arFiles[n+e]){var o=this.arFiles[n+e][0];t=this.controllers[o];i={file:t.values[e],controller:t}}}else if(this.controllers[t.id]){i={file:e,controller:t}}return i},insertFile:function(e,t){var i=this.oEditor;if(i&&e){var n=e["id"],o="",s=t.parser,a=i.GetViewMode(),r=this.parser[s.bxTag][a];if(e["isImage"]){r=a=="wysiwyg"?this.parser["postimage"][a]:r;if(e.width>0&&e.height>0&&i.sEditorMode=="html"){o=' style="width:'+e.width+"px;height:"+e.height+'px;" onload="this.style=\' \'"'}}if(a=="wysiwyg"){i.InsertHtml(r.replace("#ID#",i.SetBxTag(false,{tag:s.bxTag,params:{value:n}})).replace("#SRC#",e.src).replace("#URL#",e.url).replace("#LOWSRC#",e.lowsrc||"").replace("#NAME#",e.name).replace("#ADDITIONAL#",o)+"<span>&nbsp;</span>");setTimeout(BX.delegate(i.AutoResizeSceleton,i),500);setTimeout(BX.delegate(i.AutoResizeSceleton,i),1e3)}else if(a=="code"&&i.bbCode){i.textareaView.Focus();i.textareaView.WrapWith(false,false,r.replace("#ID#",n).replace("#ADDITIONAL#",""))}this.monitoringSetStatus(s.bxTag,e.id,true);this.monitoringStart()}},deleteFile:function(e,t){var i=this.oEditor,n=t.parser,o=e.id,s=i.GetContent();if(n&&s.indexOf("="+o)>=0){if(i.GetViewMode()=="wysiwyg"){var a=i.GetIframeDoc(),r,l;for(r in i["bxTags"]){if(i["bxTags"].hasOwnProperty(r)){if(typeof i.bxTags[r]=="object"&&i.bxTags[r]["params"]&&i.bxTags[r]["params"]["value"]==e.id){l=a.getElementById(r);if(l)l.parentNode.removeChild(l)}}}i.SaveContent()}else{s=s.replace(n.regexp,function(t,i,n){return n==e.id?"":t});i.SetContent(s);i.Focus()}this.monitoringSetStatus(n.bxTag,e.id,false)}},reinit:function(e,t){BX.onCustomEvent(this.eventNode,"onReinitializeBefore",[this,e,t]);this.arFiles={};delete this.monitoringWakeUp;this.monitoringStop();this.oEditor.CheckAndReInit(e||"");BX.onCustomEvent(this.eventNode,"onReinitialize",[this,e,t]);var i,n=false;for(i in this.controllers){if(this.controllers.hasOwnProperty(i)){if(this.controllers[i]["init"]&&this.controllers[i].reinit(e,t))n=true}}this.controllerInit(n?"show":"hide");if(this.params["~height"]){this.oEditor.SetConfigHeight(this.params["~height"]);this.oEditor.ResizeSceleton()}},Parse:function(e,t,i){var n=this.parser[e],o=this;if(n){t=t.replace(n.regexp,function(t,s,a,r,l){var d=o.checkFile(a,e);if(d&&(d=d.file)&&d){var h="",f=d.isImage?o.parser.postimage.wysiwyg:n.wysiwyg;o.monitoringStart();if(d.isImage){r=parseInt(r);l=parseInt(l);h=r&&l?' width="'+r+'" height="'+l+'"':"";if(h===""&&d["width"]>0&&d["height"]>0){h=' style="width:'+d["width"]+"px;height:"+d["height"]+'px;" onload="this.style=\' \'"'}}return f.replace("#ID#",i.SetBxTag(false,{tag:e,params:{value:a}})).replace("#NAME#",d.name).replace("#SRC#",d.src).replace("#LOWSRC#",d.lowsrc).replace("#ADDITIONAL#",h).replace("#WIDTH#",parseInt(r)).replace("#HEIGHT#",parseInt(l))}return t})}return t},Unparse:function(e,t){var i="",n=e.tag;if(this.parser[n]){var o=parseInt(t.node.hasAttribute("width")?t.node.getAttribute("width"):0),s=parseInt(t.node.hasAttribute("height")?t.node.getAttribute("height"):0),a="";if(o>0&&s>0){a=" WIDTH="+o+" HEIGHT="+s}i=this.parser[n]["code"].replace("#ID#",e.params.value).replace("#ADDITIONAL#",a).replace("#WIDTH#",o).replace("#HEIGHT#",s)}return i},OnShowLHE:function(e,t,i){var n=this.__divId;e=e===false?false:e==="hide"?"hide":e==="justShow"?"justShow":true;this.oEditor=this.oEditor||LHEPostForm.getEditor(this.oEditorId);if(!this.oEditor)return;this.oEditor.Init();var o=BX("micro"+n),s=this.eventNode;if(o){o.style.display=e===true||e==="justShow"?"none":"block"}if(e=="hide"){BX.onCustomEvent(this.eventNode,"OnBeforeHideLHE",[e,this]);if(this.eventNode.style.display=="none"){BX.onCustomEvent(this.eventNode,"OnAfterHideLHE",[e,this])}else{new BX["easing"]({duration:200,start:{opacity:100,height:this.eventNode.scrollHeight},finish:{opacity:0,height:20},transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:function(e){s.style.height=e.height+"px";s.style.opacity=e.opacity/100},complete:BX.proxy(function(){this.eventNode.style.cssText="";this.eventNode.style.display="none";BX.onCustomEvent(s,"OnAfterHideLHE",[e,this])},this)}).animate()}}else if(e){BX.onCustomEvent(this.eventNode,"OnBeforeShowLHE",[e,this]);if(e=="justShow"){this.eventNode.style.display="block";BX.onCustomEvent(this.eventNode,"OnAfterShowLHE",[e,this]);if(i!==false)this.oEditor.Focus()}else if(this.eventNode.style.display=="block"){BX.onCustomEvent(this.eventNode,"OnAfterShowLHE",[e,this]);if(i!==false)this.oEditor.Focus()}else{BX.adjust(this.eventNode,{style:{display:"block",overflow:"hidden",height:"20px",opacity:.1}});new BX["easing"]({duration:200,start:{opacity:10,height:20},finish:{opacity:100,height:s.scrollHeight},transition:BX["easing"].makeEaseOut(BX.easing.transitions.quad),step:function(e){s.style.height=e.height+"px";s.style.opacity=e.opacity/100},complete:BX.proxy(function(){BX.onCustomEvent(s,"OnAfterShowLHE",[e,this]);this.oEditor.Focus();this.eventNode.style.cssText=""},this)}).animate()}}else{BX.onCustomEvent(this.eventNode,"OnBeforeHideLHE",[e,this]);this.eventNode.style.display="none";BX.onCustomEvent(this.eventNode,"OnAfterHideLHE",[e,this])}},OnButtonClick:function(e){if(e!="cancel"){BX.onCustomEvent(this.eventNode,"OnClickSubmit",[this])}else{BX.onCustomEvent(this.eventNode,"OnClickCancel",[this]);BX.onCustomEvent(this.eventNode,"OnShowLHE",["hide"])}},OnEditorInitedBefore:function(t){var i=this;this.oEditor=t;t.formID=this.formID;if(this.params)this.params["~height"]=t.config["height"];if(this.params&&this.params["ctrlEnterHandler"]){BX.addCustomEvent(t,"OnCtrlEnter",function(){t.SaveContent();if(typeof e[i.params["ctrlEnterHandler"]]=="function")e[i.params["ctrlEnterHandler"]]();else BX.submit(BX(i.formID))})}var n=this.params.parsers?this.params.parsers:[];if(BX.util.object_search("Spoiler",n)){t.AddButton({id:"spoiler",name:BX.message("spoilerText"),iconClassName:"spoiler",disabledForTextarea:false,src:BX.message("MPF_TEMPLATE_FOLDER")+"/images/lhespoiler.png",toolbarSort:205,handler:function(){var e=this,t=false;if(!e.editor.bbCode||!e.editor.synchro.IsFocusedOnTextarea()){t=e.editor.action.actions.formatBlock.exec("formatBlock","blockquote","bx-spoiler",false,{bxTagParams:{tag:"spoiler"}})}else{t=e.editor.action.actions.formatBbCode.exec("quote",{tag:"SPOILER"})}return t}});t.AddParser({name:"spoiler",obj:{Parse:function(e,t,i){if(/\[(cut|spoiler)(([^\]])*)\]/gi.test(t)){t=t.replace(/[\001-\006]/gi,"").replace(/\[cut(((?:=)[^\]]*)|)\]/gi,"$1").replace(/\[\/cut]/gi,"").replace(/\[spoiler([^\]]*)\]/gi,"$1").replace(/\[\/spoiler]/gi,"");var n=/(?:\001([^\001]*)\001)([^\001-\004]+)\002/gi,o=/(?:\003([^\003]*)\003)([^\001-\004]+)\004/gi,s=function(e,t){e=e.replace(/^(="|='|=)/gi,"").replace(/("|')?$/gi,"");return'<blockquote class="bx-spoiler" id="'+i.SetBxTag(false,{tag:"spoiler"})+'" title="'+e+'">'+t+"</blockquote>"},a=function(e,t,i){return s(t,i)};while(t.match(n)||t.match(o)){t=t.replace(n,a).replace(o,a)}}t=t.replace(/\001([^\001]*)\001/gi,"[cut$1]").replace(/\003([^\003]*)\003/gi,"[spoiler$1]").replace(/\002/gi,"[/cut]").replace(/\004/gi,"[/spoiler]");return t},UnParse:function(e,i){if(e.tag=="spoiler"){var n="",o;for(o=0;o<i.node.childNodes.length;o++){n+=t.bbParser.GetNodeHtml(i.node.childNodes[o])}n=BX.util.trim(n);if(n!="")return"[SPOILER"+(i.node.hasAttribute("title")?"="+i.node.getAttribute("title"):"")+"]"+n+"[/SPOILER]"}return""}}})}if(BX.util.object_search("MentionUser",n)){t.AddParser({name:"postuser",obj:{Parse:function(e,i){i=i.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/gi,function(e,i,n){n=BX.util.trim(n);if(n=="")return"";return'<span id="'+t.SetBxTag(false,{tag:"postuser",params:{value:parseInt(i)}})+'" class="bxhtmled-metion">'+n+"</span>"});return i},UnParse:function(e,i){if(e.tag=="postuser"){var n="",o;for(o=0;o<i.node.childNodes.length;o++){n+=t.bbParser.GetNodeHtml(i.node.childNodes[o])}n=BX.util.trim(n);if(n!="")return"[USER="+e.params.value+"]"+n+"[/USER]"}return""}}})}var o=function(e,n){return i.Parse(e,n,t)},s=function(e,t){return i.Unparse(e,t)};for(var a in this.parser){if(this.parser.hasOwnProperty(a)){t.AddParser({name:a,obj:{Parse:o,UnParse:s}})}}},OnEditorInitedAfter:function(t){BX.addCustomEvent(t,"OnIframeDrop",BX.proxy(function(){BX.onCustomEvent(this.eventNode,"OnIframeDrop",arguments)},this));BX.addCustomEvent(t,"OnIframeDragOver",BX.proxy(function(){BX.onCustomEvent(this.eventNode,"OnIframeDragOver",arguments)},this));BX.addCustomEvent(t,"OnIframeDragLeave",BX.proxy(function(){BX.onCustomEvent(this.eventNode,"OnIframeDragLeave",arguments)},this));t.contextMenu.items["postimage"]=t.contextMenu.items["postdocument"]=t.contextMenu.items["postfile"]=[{TEXT:BX.message("BXEdDelFromText"),bbMode:true,ACTION:function(){var e=t.contextMenu.GetTargetItem("postimage");if(!e)e=t.contextMenu.GetTargetItem("postdocument");if(!e)e=t.contextMenu.GetTargetItem("postfile");if(e&&e.element){t.selection.RemoveNode(e.element)}t.contextMenu.Hide()}}];if(!this.params["lazyLoad"]){BX.onCustomEvent(this.eventNode,"OnShowLHE",["justShow",t,false])}if(t.toolbar.controls&&t.toolbar.controls.FontSelector){t.toolbar.controls.FontSelector.SetWidth(45)}BX.addCustomEvent(BX(this.formID),"onAutoSavePrepare",function(e){var i=e;setTimeout(function(){
BX.addCustomEvent(t,"OnContentChanged",BX.proxy(function(e){this["mpfTextContent"]=e;this.Init()},i))},1500)});BX.addCustomEvent(BX(this.formID),"onAutoSave",BX.proxy(function(e,t){if(BX.type.isNotEmptyString(e["mpfTextContent"]))t["text"+this.formID]=e["mpfTextContent"]},this));BX.addCustomEvent(BX(this.formID),"onAutoSaveRestore",BX.proxy(function(e,i){if(i["text"+this.formID]&&/[^\s]+/gi.test(i["text"+this.formID])){t.CheckAndReInit(i["text"+this.formID])}},this));if(BX(this.formID)&&BX(this.formID).hasAttribute("bx-lhe-autosave-prepared")&&BX(this.formID).BXAUTOSAVE){BX(this.formID).removeAttribute("bx-lhe-autosave-prepared");setTimeout(BX.proxy(function(){BX(this.formID).BXAUTOSAVE.Prepare()},this),100)}var i=this.formID,n=this.params;this.showPanelEditor(n.showPanelEditor,false);if(!t.mainPostFormCustomized){t.mainPostFormCustomized=true;BX.addCustomEvent(t,"OnIframeKeydown",function(n){if(e.onKeyDownHandler){e.onKeyDownHandler(n,t,i)}});BX.addCustomEvent(t,"OnIframeKeyup",function(n){if(e.onKeyUpHandler){e.onKeyUpHandler(n,t,i)}});if(e["BXfpdStopMent"+i]){BX.addCustomEvent(t,"OnIframeClick",function(){e["BXfpdStopMent"+i]()})}if(t&&t.textareaView.GetCursorPosition){BX.addCustomEvent(t,"OnTextareaKeyup",function(n){if(e.onTextareaKeyUpHandler){e.onTextareaKeyUpHandler(n,t,i)}});BX.addCustomEvent(t,"OnTextareaKeydown",function(n){if(e.onTextareaKeyDownHandler){e.onTextareaKeyDownHandler(n,t,i)}})}}}};e.LHEPostForm.getEditor=function(t){return e["BXHtmlEditor"]?e["BXHtmlEditor"].Get(typeof t=="object"?t.id:t):null};e.LHEPostForm.getHandler=function(e){return t.handler[typeof e=="object"?e.id:e]};e.LHEPostForm.unsetHandler=function(e){var i=typeof e=="object"?e.id:e;if(!t.handler[i])return;if(t.handler[i].oEditor)t.handler[i].oEditor.Destroy();t.handler[i]=null};e.LHEPostForm.reinitData=function(e,t,i){var n=LHEPostForm.getHandler(e);if(n)n.exec(n.reinit,[t,i]);return false};e.LHEPostForm.reinitDataBefore=function(e){var t=LHEPostForm.getHandler(e);if(t&&t["eventNode"])BX.onCustomEvent(t.eventNode,"onReinitializeBefore",[t])};e.BXPostFormTags=function(e,t){this.popup=null;this.formID=e;this.buttonID=t;this.sharpButton=null;this.addNewLink=null;this.tagsArea=null;this.hiddenField=null;this.popupContent=null;BX.ready(BX.proxy(this.init,this))};e.BXPostFormTags.prototype.init=function(){this.sharpButton=BX(this.buttonID);this.addNewLink=BX("post-tags-add-new-"+this.formID);this.tagsArea=BX("post-tags-block-"+this.formID);this.tagsContainer=BX("post-tags-container-"+this.formID);this.hiddenField=BX("post-tags-hidden-"+this.formID);this.popupContent=BX("post-tags-popup-content-"+this.formID);this.popupInput=BX.findChild(this.popupContent,{tag:"input"});var e=BX.findChildren(this.tagsContainer,{className:"feed-add-post-del-but"},true);for(var t=0,i=e.length;t<i;t++){BX.bind(e[t],"click",BX.proxy(this.onTagDelete,{obj:this,tagBox:e[t].parentNode,tagValue:e[t].parentNode.getAttribute("data-tag")}))}BX.bind(this.sharpButton,"click",BX.proxy(this.onButtonClick,this));BX.bind(this.addNewLink,"click",BX.proxy(this.onAddNewClick,this))};e.BXPostFormTags.prototype.onTagDelete=function(){BX.remove(this.tagBox);this.obj.hiddenField.value=this.obj.hiddenField.value.replace(this.tagValue+",","").replace("  "," ")};e.BXPostFormTags.prototype.show=function(){if(this.popup===null){this.popup=new BX.PopupWindow("bx-post-tag-popup",this.addNewLink,{content:this.popupContent,lightShadow:false,offsetTop:8,offsetLeft:10,autoHide:true,angle:true,closeByEsc:true,zIndex:-910,buttons:[new BX.PopupWindowButton({text:BX.message("TAG_ADD"),events:{click:BX.proxy(this.onTagAdd,this)}})]});BX.bind(this.popupInput,"keydown",BX.proxy(this.onKeyPress,this));BX.bind(this.popupInput,"keyup",BX.proxy(this.onKeyPress,this))}this.popup.show();BX.focus(this.popupInput)};e.BXPostFormTags.prototype.addTag=function(e){var t=BX.type.isNotEmptyString(e)?e.split(","):this.popupInput.value.split(",");var i=[];for(var n=0;n<t.length;n++){var o=BX.util.trim(t[n]);if(o.length>0){var s=this.hiddenField.value.split(",");if(!BX.util.in_array(o,s)){var a;var r=BX.create("span",{children:[a=BX.create("span",{attrs:{"class":"feed-add-post-del-but"}})],attrs:{"class":"feed-add-post-tags"}});r.insertBefore(document.createTextNode(o),a);this.tagsContainer.insertBefore(r,this.addNewLink);BX.bind(a,"click",BX.proxy(this.onTagDelete,{obj:this,tagBox:r,tagValue:o}));this.hiddenField.value+=o+",";i.push(o)}}}return i};e.BXPostFormTags.prototype.onTagAdd=function(e){this.addTag();this.popupInput.value="";this.popup.close()};e.BXPostFormTags.prototype.onAddNewClick=function(t){t=t||e.event;this.show();BX.PreventDefault(t)};e.BXPostFormTags.prototype.onButtonClick=function(t){t=t||e.event;BX.show(this.tagsArea);this.show();BX.PreventDefault(t)};e.BXPostFormTags.prototype.onKeyPress=function(t){t=t||e.event;var i=t.keyCode?t.keyCode:t.which?t.which:null;if(i==13){setTimeout(BX.proxy(this.onTagAdd,this),0)}};var r=null;e.MPFbuttonShowWait=function(e){if(e&&!BX.type.isElementNode(e))e=null;e=e||this;e=e?e.tagName=="A"?e:e.parentNode:e;if(e){BX.addClass(e,"feed-add-button-load");r=e;BX.defer(function(){e.disabled=true})()}};e.MPFbuttonCloseWait=function(e){if(e&&!BX.type.isElementNode(e))e=null;e=e||r||this;if(e){e.disabled=false;BX.removeClass(e,"feed-add-button-load");r=null}};e.__mpf_wd_getinfofromnode=function(e,t){var i=BX.findChild(BX((e["prefixNode"]||"wd-doc")+e.element_id),{className:"files-preview",tagName:"IMG"},true,false);if(i){e.lowsrc=i.src;e.element_url=i.src.replace(/\Wwidth\=(\d+)/,"").replace(/\Wheight\=(\d+)/,"");e.width=parseInt(i.getAttribute("data-bx-full-width"));e.height=parseInt(i.getAttribute("data-bx-full-height"))}else if(t.urlGet){e.element_url=t.urlGet.replace("#element_id#",e.element_id).replace("#ELEMENT_ID#",e.element_id).replace("#element_name#",e.element_name).replace("#ELEMENT_NAME#",e.element_name)}};var l={listen:false,plus:false,text:""};e.BXfpdSelectCallback=function(t,i,n,o){if(!BX.findChild(BX("feed-add-post-destination-item"),{attr:{"data-id":t.id}},false,false)){var s=i;var a="S";if(i=="groups"){s="all-users"}else if(BX.util.in_array(i,["contacts","companies","leads","deals"])){s="crm"}if(i=="sonetgroups"){a="SG"}else if(i=="groups"){a="UA"}else if(i=="users"){a="U"}else if(i=="department"){a="DR"}else if(i=="contacts"){a="CRMCONTACT"}else if(i=="companies"){a="CRMCOMPANY"}else if(i=="leads"){a="CRMLEAD"}else if(i=="deals"){a="CRMDEAL"}var r=o?" feed-add-post-destination-undelete":"";r+=i=="sonetgroups"&&typeof e["arExtranetGroupID"]!="undefined"&&BX.util.in_array(t.entityId,e["arExtranetGroupID"])?" feed-add-post-destination-extranet":"";var l=BX.create("span",{attrs:{"data-id":t.id},props:{className:"feed-add-post-destination feed-add-post-destination-"+s+r},children:[BX.create("input",{attrs:{type:"hidden",name:"SPERM["+a+"][]",value:t.id}}),BX.create("span",{props:{className:"feed-add-post-destination-text"},html:t.name})]});if(!o){l.appendChild(BX.create("span",{props:{className:"feed-add-post-del-but"},events:{click:function(n){BX.SocNetLogDestination.deleteItem(t.id,i,e.BXSocNetLogDestinationFormName);BX.PreventDefault(n)},mouseover:function(){BX.addClass(this.parentNode,"feed-add-post-destination-hover")},mouseout:function(){BX.removeClass(this.parentNode,"feed-add-post-destination-hover")}}}))}BX("feed-add-post-destination-item").appendChild(l)}BX("feed-add-post-destination-input").value="";BX.SocNetLogDestination.BXfpSetLinkName({formName:e.BXSocNetLogDestinationFormName,tagInputName:"bx-destination-tag",tagLink1:BX.message("BX_FPD_LINK_1"),tagLink2:BX.message("BX_FPD_LINK_2")})};e.onKeyDownHandler=function(t,i,n){var o=t.keyCode;if(!e["BXfpdStopMent"+n])return true;if(o==107||(t.shiftKey||t.modifiers>3)&&BX.util.in_array(o,[187,50,107,43,61])){setTimeout(function(){var t=i.selection.GetRange(),o=i.GetIframeDoc(),s=t?t.endContainer.textContent:"",a=s?s.slice(t.endOffset-1,t.endOffset):"",r=s?s.slice(t.endOffset-2,t.endOffset-1):"";if((a=="@"||a=="+")&&(!r||BX.util.in_array(r,["+","@",",","("])||r.length==1&&BX.util.trim(r)==="")){l.listen=true;l.text="";l.leaveContent=true;t.setStart(t.endContainer,t.endOffset-1);t.setEnd(t.endContainer,t.endOffset);i.selection.SetSelection(t);var d=BX.create("SPAN",{props:{id:"bx-mention-node"}},o);i.selection.Surround(d,t);t.setStart(d,1);t.setEnd(d,1);i.selection.SetSelection(t);if(!BX.SocNetLogDestination.isOpenDialog()){BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+n],{bindNode:getMentionNodePosition(d,i)})}}},10)}if(l.listen){if(o==i.KEY_CODES["enter"]){BX.SocNetLogDestination.selectCurrentSearchItem(e["BXSocNetLogDestinationFormNameMent"+n]);i.iframeKeyDownPreventDefault=true;BX.PreventDefault(t)}else if(o==i.KEY_CODES["left"]){BX.SocNetLogDestination.moveCurrentSearchItem(e["BXSocNetLogDestinationFormNameMent"+n],"left");i.iframeKeyDownPreventDefault=true;BX.PreventDefault(t)}else if(o==i.KEY_CODES["right"]){BX.SocNetLogDestination.moveCurrentSearchItem(e["BXSocNetLogDestinationFormNameMent"+n],"right");i.iframeKeyDownPreventDefault=true;BX.PreventDefault(t)}else if(o==i.KEY_CODES["up"]){BX.SocNetLogDestination.moveCurrentSearchItem(e["BXSocNetLogDestinationFormNameMent"+n],"up");i.iframeKeyDownPreventDefault=true;BX.PreventDefault(t)}else if(o==i.KEY_CODES["down"]){BX.SocNetLogDestination.moveCurrentSearchItem(e["BXSocNetLogDestinationFormNameMent"+n],"down");i.iframeKeyDownPreventDefault=true;BX.PreventDefault(t)}}if(!l.listen&&o===i.KEY_CODES["enter"]){var s=i.selection.GetRange();if(s.collapsed){var a=s.endContainer,r=i.GetIframeDoc();if(a){if(a.className!=="bxhtmled-metion"){a=BX.findParent(a,function(e){return e.className=="bxhtmled-metion"},r.body)}if(a&&a.className=="bxhtmled-metion"){i.selection.SetAfter(a)}}}}};e.onKeyUpHandler=function(t,i,n){var o=t.keyCode,s,a;if(!e["BXfpdStopMent"+n])return true;if(l.listen===true){if(o==i.KEY_CODES["escape"]){e["BXfpdStopMent"+n]()}else if(o!==i.KEY_CODES["enter"]&&o!==i.KEY_CODES["left"]&&o!==i.KEY_CODES["right"]&&o!==i.KEY_CODES["up"]&&o!==i.KEY_CODES["down"]){s=i.GetIframeDoc();var r=s.getElementById("bx-mention-node");if(r){var d=BX.util.trim(i.util.GetTextContent(r)),h=d;d=d.replace(/^[\+@]*/,"");BX.SocNetLogDestination.search(d,true,e["BXSocNetLogDestinationFormNameMent"+n],BX.message("MPF_NAME_TEMPLATE"),{bindNode:getMentionNodePosition(r,i)});if(l.leaveContent&&l._lastText&&h===""){e["BXfpdStopMent"+n]()}else if(l.leaveContent&&l.lastText&&h!==""&&d===""){e["BXfpdStopMent"+n]();BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+n],{bindNode:getMentionNodePosition(r,i)})}l.lastText=d;l._lastText=h}else{e["BXfpdStopMent"+n]()}}}else{if(!t.shiftKey&&(o===i.KEY_CODES["space"]||o===i.KEY_CODES["escape"]||o===188||o===190)){a=i.selection.GetRange();if(a.collapsed){var f=a.endContainer;s=i.GetIframeDoc();if(f){if(f.className!=="bxhtmled-metion"){f=BX.findParent(f,function(e){return e.className=="bxhtmled-metion"},s.body)}if(f&&f.className=="bxhtmled-metion"){d=i.util.GetTextContent(f);var u=d.match(/[\s\.\,]$/);if(u||o===i.KEY_CODES["escape"]){f.innerHTML=d.replace(/[\s\.\,]$/,"");var c=BX.create("SPAN",{html:u||i.INVISIBLE_SPACE},s);i.util.InsertAfter(c,f);i.selection.SetAfter(c)}}}}}}};e.onTextareaKeyDownHandler=function(t,i,n){var o=t.keyCode;if(l.listen&&o==i.KEY_CODES["enter"]){BX.SocNetLogDestination.selectFirstSearchItem(e["BXSocNetLogDestinationFormNameMent"+n]);i.textareaKeyDownPreventDefault=true;BX.PreventDefault(t)}};e.onTextareaKeyUpHandler=function(t,i,n){var o,s,a=t.keyCode;if(l.listen===true){if(a==27){e["BXfpdStopMent"+n]()}else if(a!==13){s=i.textareaView.GetValue(false);o=i.textareaView.GetCursorPosition();if(s.indexOf("+")!==-1||s.indexOf("@")!==-1){var r=s.substr(0,o),d=Math.max(r.lastIndexOf("+"),r.lastIndexOf("@"));if(d>=0){var h=r.substr(d),f=h;h=h.replace(/^[\+@]*/,"");if(!BX.SocNetLogDestination.isOpenDialog()){BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+n])}BX.SocNetLogDestination.search(h,true,e["BXSocNetLogDestinationFormNameMent"+n],BX.message("MPF_NAME_TEMPLATE"));if(l.leaveContent&&l._lastText&&f===""){e["BXfpdStopMent"+n]()}else if(l.leaveContent&&l.lastText&&f!==""&&h===""){e["BXfpdStopMent"+n]();BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+n])}l.lastText=h;l._lastText=f}}}}else{if(a==16){var u=this;this.shiftPressed=true;if(this.shiftTimeout)this.shiftTimeout=clearTimeout(this.shiftTimeout);this.shiftTimeout=setTimeout(function(){u.shiftPressed=false},100)}if(a==107||(t.shiftKey||t.modifiers>3||this.shiftPressed)&&BX.util.in_array(a,[187,50,107,43,61])){o=i.textareaView.element.selectionStart;if(o>0){s=i.textareaView.element.value;var c=s.substr(o-1,1);if(c&&(c==="+"||c==="@")){l.listen=true;l.text="";l.textarea=true;if(!BX.SocNetLogDestination.isOpenDialog()){BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+n])}}}}}};e.getMentionNodePosition=function(e,t){var i=BX.pos(e),n=BX.pos(t.dom.areaCont),o=BX.GetWindowScrollPos(t.GetIframeDoc()),s=n.top+i.bottom-o.scrollTop+2,a=n.left+i.right-o.scrollLeft;return{top:s,left:a}};e.BxInsertMention=function(t){var i=t.item,n=t.type,o=t.formID,s=t.editorId,a=t.bNeedComa,r=LHEPostForm.getEditor(s);if(n=="users"&&i&&i.entityId>0&&r){if(r.GetViewMode()=="wysiwyg"){var d=r.GetIframeDoc(),h=r.selection.GetRange(),f=d.getElementById("bx-mention-node"),u=BX.create("SPAN",{props:{className:"bxhtmled-metion"},text:BX.util.htmlspecialcharsback(i.name)},d),c=BX.create("SPAN",{html:a?",&nbsp;":"&nbsp;"},d);r.SetBxTag(u,{tag:"postuser",params:{value:i.entityId}});if(f){r.util.ReplaceNode(f,u)}else{r.selection.InsertNode(u,h)}if(u&&u.parentNode){var p=BX.findParent(u,{className:"bxhtmled-metion"},d.body);if(p){r.util.InsertAfter(u,p)}}if(u&&u.parentNode){r.util.InsertAfter(c,u);r.selection.SetAfter(c)}}else if(r.GetViewMode()=="code"&&r.bbCode){r.textareaView.Focus();var m=r.textareaView.GetValue(false),g=r.textareaView.GetCursorPosition(),B=m.substr(0,g),X=Math.max(B.lastIndexOf("+"),B.lastIndexOf("@"));if(X>=0&&g>X){r.textareaView.SetValue(m.substr(0,X)+m.substr(g));r.textareaView.element.setSelectionRange(X,X)}r.textareaView.WrapWith(false,false,"[USER="+i.entityId+"]"+i.name+"[/USER]"+(a?", ":" "))}delete BX.SocNetLogDestination.obItemsSelected[e["BXSocNetLogDestinationFormNameMent"+o]][i.id];e["BXfpdStopMent"+o]();l["text"]="";if(r.GetViewMode()=="wysiwyg"){r.Focus();r.selection.SetAfter(c)}}};e.buildDepartmentRelation=function(e){var t={};for(var i in e){var n=e[i]["parent"];if(!t[n])t[n]=[];t[n][t[n].length]=i}function o(e,t){var i={};if(t[e]){for(var n in t[e]){var s=t[e][n];var a=[];if(t[s]&&t[s].length>0)a=o(s,t);i[s]={id:s,type:"category",items:a}}}return i}return o("DR0",t)};e.MPFMentionInit=function(t,i){if(!i["items"]["departmentRelation"])i["items"]["departmentRelation"]=e.buildDepartmentRelation(i["items"]["department"]);e["departmentRelation"]=i["items"]["departmentRelation"];if(i["initDestination"]===true){e.BXSocNetLogDestinationFormName="destination"+(""+(new Date).getTime()).substr(6);e.BXSocNetLogDestinationDisableBackspace=null;BX.SocNetLogDestination.init({name:e.BXSocNetLogDestinationFormName,searchInput:BX("feed-add-post-destination-input"),extranetUser:i["extranetUser"],bindMainPopup:{node:BX("feed-add-post-destination-container"),offsetTop:"5px",offsetLeft:"15px"},bindSearchPopup:{node:BX("feed-add-post-destination-container"),offsetTop:"5px",offsetLeft:"15px"},callback:{select:e["BXfpdSelectCallback"],unSelect:BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback,{formName:e.BXSocNetLogDestinationFormName,inputContainerName:"feed-add-post-destination-item",inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag",tagLink1:BX.message("BX_FPD_LINK_1"),tagLink2:BX.message("BX_FPD_LINK_2")}),openDialog:BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback,{inputBoxName:"feed-add-post-destination-input-box",inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag"}),closeDialog:BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback,{inputBoxName:"feed-add-post-destination-input-box",inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag"}),openSearch:BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback,{inputBoxName:"feed-add-post-destination-input-box",inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag"}),closeSearch:BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback,{inputBoxName:"feed-add-post-destination-input-box",inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag"})},items:i["items"],itemsLast:i["itemsLast"],itemsSelected:i["itemsSelected"],isCrmFeed:i["isCrmFeed"],useClientDatabase:!!i["useClientDatabase"]});BX.bind(BX("feed-add-post-destination-input"),"keyup",BX.delegate(BX.SocNetLogDestination.BXfpSearch,{formName:e.BXSocNetLogDestinationFormName,inputName:"feed-add-post-destination-input",tagInputName:"bx-destination-tag"}));BX.bind(BX("feed-add-post-destination-input"),"keydown",BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore,{formName:e.BXSocNetLogDestinationFormName,inputName:"feed-add-post-destination-input"}));BX.bind(BX("bx-destination-tag"),"click",function(t){BX.SocNetLogDestination.openDialog(e.BXSocNetLogDestinationFormName);BX.PreventDefault(t)});BX.bind(BX("feed-add-post-destination-container"),"click",function(t){BX.SocNetLogDestination.openDialog(e.BXSocNetLogDestinationFormName);BX.PreventDefault(t)});if(i["itemsHidden"]){for(var n in i["itemsHidden"]){e.BXfpdSelectCallback({id:"SG"+i["itemsHidden"][n]["ID"],name:i["itemsHidden"][n]["NAME"]},"sonetgroups","",true)}}BX.SocNetLogDestination.BXfpSetLinkName({formName:e.BXSocNetLogDestinationFormName,tagInputName:"bx-destination-tag",tagLink1:BX.message("BX_FPD_LINK_1"),tagLink2:BX.message("BX_FPD_LINK_2")})}e["BXfpdSelectCallbackMent"+t]=function(n,o,s){e.BxInsertMention({item:n,type:o,formID:t,editorId:i["editorId"]})};e["BXfpdStopMent"+t]=function(){BX.SocNetLogDestination.closeDialog();BX.SocNetLogDestination.closeSearch();clearTimeout(BX.SocNetLogDestination.searchTimeout);BX.SocNetLogDestination.searchOnSuccessHandle=false};e["BXfpdOnDialogOpen"+t]=function(){l.listen=true};e["BXfpdOnDialogClose"+t]=function(){l.listen=false;setTimeout(function(){if(!l.listen){var e=LHEPostForm.getEditor(i.editorId);if(e){var t=e.GetIframeDoc(),n=t.getElementById("bx-mention-node");if(n){e.selection.SetAfter(n);if(l.leaveContent){e.util.ReplaceWithOwnChildren(n)}else{BX.remove(n)}}e.Focus()}}},100)};e["BXSocNetLogDestinationFormNameMent"+t]="mention"+(""+(new Date).getTime()).substr(5);e["BXSocNetLogDestinationDisableBackspace"]=null;var o=BX("bx-b-mention-"+t);BX.SocNetLogDestination.init({name:e["BXSocNetLogDestinationFormNameMent"+t],searchInput:o,extranetUser:i["extranetUser"],bindMainPopup:{node:o,offsetTop:"1px",offsetLeft:"12px"},bindSearchPopup:{node:o,offsetTop:"1px",offsetLeft:"12px"},callback:{select:e["BXfpdSelectCallbackMent"+t],openDialog:e["BXfpdOnDialogOpen"+t],closeDialog:e["BXfpdOnDialogClose"+t],openSearch:e["BXfpdOnDialogOpen"+t],closeSearch:e["BXfpdOnDialogClose"+t]},items:{users:i["items"]["users"],groups:{},sonetgroups:{},department:i["items"]["department"],departmentRelation:i["items"]["departmentRelation"]},itemsLast:{users:e["lastUsers"],sonetgroups:{},department:{},groups:{}},itemsSelected:i["itemsSelected"],departmentSelectDisable:true,obWindowClass:"bx-lm-mention",obWindowCloseIcon:false});BX.ready(function(){var n=BX("bx-b-mention-"+t);if(BX.browser.IsIE()&&!BX.browser.IsIE9()){n.style.width="1px";n.style.marginRight="0"}BX.bind(n,"mousedown",function(o){if(l.listen!==true){var s=LHEPostForm.getEditor(i.editorId),a=s.GetIframeDoc();if(s.GetViewMode()=="wysiwyg"&&a){l.listen=true;l.text="";l.leaveContent=false;var r=s.selection.GetRange(),d=a.getElementById("bx-mention-node");if(d){BX.remove(d)}s.InsertHtml('<span id="bx-mention-node">'+s.INVISIBLE_SPACE+"</span>",r);setTimeout(function(){if(!BX.SocNetLogDestination.isOpenDialog()){BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+t],{bindNode:n})}var i=a.getElementById("bx-mention-node");if(i){r.setStart(i,0);if(i.firstChild&&i.firstChild.nodeType==3&&i.firstChild.nodeValue.length>0){r.setEnd(i,1)}else{r.setEnd(i,0)}s.selection.SetSelection(r)}s.Focus()},100)}else if(s.GetViewMode()=="code"){l.listen=true;l.text="";l.leaveContent=false;setTimeout(function(){if(!BX.SocNetLogDestination.isOpenDialog()){BX.SocNetLogDestination.openDialog(e["BXSocNetLogDestinationFormNameMent"+t],{bindNode:n})}},100)}BX.onCustomEvent(n,"mentionClick")}})})}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:71:"/bitrix/components/bitrix/system.field.edit/script.min.js?1452277431462";s:6:"source";s:53:"/bitrix/components/bitrix/system.field.edit/script.js";s:3:"min";s:57:"/bitrix/components/bitrix/system.field.edit/script.min.js";s:3:"map";s:57:"/bitrix/components/bitrix/system.field.edit/script.map.js";}"*/
function addElement(e,n){if(document.getElementById("main_"+e)){var t=document.getElementById("main_"+e).getElementsByTagName("div");if(t&&t.length>0&&t[0]){var d=t[0].parentNode;d.appendChild(t[t.length-1].cloneNode(true))}}return}function addElementFile(e,n){var t=document.getElementById("main_"+e);var d=document.getElementById("main_add_"+e);if(t&&d){d=d.cloneNode(true);d.id="";d.style.display="";t.appendChild(d)}return}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:87:"/bitrix/components/bitrix/disk.uf.file/templates/.default/script.min.js?145227744627707";s:6:"source";s:67:"/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js";s:3:"min";s:71:"/bitrix/components/bitrix/disk.uf.file/templates/.default/script.min.js";s:3:"map";s:71:"/bitrix/components/bitrix/disk.uf.file/templates/.default/script.map.js";}"*/
(function(e){var t=false,i=false,s=0,o=null,n=null,a={};BX.namespace("BX.Disk");if(BX.Disk.UF)return;BX.Disk.UF=function(){var t=function(t){this.dialogName="DiskFileDialog";this.params=t;this.CID=t["UID"];this.controller=t.controller;this.values=t.values||[];this.prefix="diskuf-doc";BX.addCustomEvent(e,"onUploaderIsAlmostInited",BX.proxy(this.onUploaderIsAlmostInited,this));this.agent=BX.Uploader.getInstance({id:t["UID"],streams:3,allowUpload:"A",uploadFormData:"N",uploadMethod:"immediate",uploadFileUrl:t["urlUpload"],showImage:false,input:t["input"],dropZone:t["dropZone"],placeHolder:t["placeHolder"],queueFields:{thumb:{tagName:"TR",className:"wd-inline-file"}},fields:{thumb:{tagName:"",template:BX.message("DISK_TMPLT_THUMB")}}});this.urlSelect=!!t["urlSelect"]?t["urlSelect"]:null;this.urlRenameFile=!!t["urlRenameFile"]?t["urlRenameFile"]:null;this.urlSelect=this._addUrlParam(this.urlSelect,"dialog2=Y&ACTION=SELECT&MULTI=Y");this.urlUpload=!!t["urlUpload"]?t["urlUpload"]:null;this.urlShow=!!t["urlShow"]?t["urlShow"]:null;this.params.controlName=this.params.controlName||"FILES[]";this.init();return this};t.prototype={_addUrlParam:function(e,t){if(!e)return null;if(e.indexOf(t)==-1)e+=(e.indexOf("?")==-1?"?":"&")+t;return e},_camelToSNAKE:function(e){var t={},i,s;for(i in e){s=i.replace(/(.)([A-Z])/g,"$1_$2").toUpperCase();t[s]=e[i];t[i]=e[i]}return t},onUploaderIsAlmostInited:function(e,t){var i=BX.findChild(this.controller,{className:"diskuf-simple"},true),s=BX.findChild(this.controller,{className:"diskuf-extended"},true);if(e=="BX.UploaderSimple"){BX.remove(s);BX.show(i)}else{BX.remove(i);BX.show(s)}t.input=BX.findChild(this.controller,{className:"diskuf-fileUploader"},true);t.dropZone=BX.findChild(this.controller,{className:"diskuf-extended"},false);this.params.placeHolder=t["placeHolder"]=BX.findChild(this.controller,{className:"diskuf-placeholder-tbody"},true)},init:function(){this._onItemIsAdded=BX.delegate(this.onItemIsAdded,this);this._onFileIsAppended=BX.delegate(this.onFileIsAppended,this);this._onFileIsAttached=BX.delegate(this.onFileIsAttached,this);this._onFileIsBound=BX.delegate(this.onFileIsBound,this);this._onFileIsInited=BX.delegate(this.onFileIsInited,this);this._onError=BX.delegate(this.onError,this);BX.addCustomEvent(this.agent,"onItemIsAdded",this._onItemIsAdded);BX.addCustomEvent(this.agent,"onFileIsInited",this._onFileIsInited);BX.addCustomEvent(this.agent,"onError",this._onError);this._onUploadProgress=BX.delegate(this.onUploadProgress,this);this._onUploadDone=BX.delegate(this.onUploadDone,this);this._onUploadError=BX.delegate(this.onUploadError,this);this._onUploadRestore=BX.delegate(this.onUploadRestore,this);BX.onCustomEvent(BX(this.controller.parentNode),"DiskDLoadFormControllerInit",[this]);var e=[],t=[],i;for(var s=0;s<this.values.length;s++){i=BX.findChild(this.values[s],{className:"f-wrap"},true);if(!!i){e.push({name:i.innerHTML});t.push(this.values[s])}}this.values=[];this.agent.onAttach(e,t);var o=BX.findChild(this.controller,{className:"diskuf-selector-link"},true);if(!!o){BX.bind(o,"click",BX.proxy(this.showSelectDialog,this))}var n=BX.findChildren(this.controller,{className:"diskuf-selector-link-cloud"},true);if(!!n){for(var a in n){if(!n.hasOwnProperty(a))continue;BX.bind(n[a],"click",BX.proxy(this.showSelectDialogCloudImport,this))}}return false},onItemIsAdded:function(){BX.removeCustomEvent(this.agent,"onItemIsAdded",this._onItemIsAdded);BX.show(BX.findParent(this.params.placeHolder,{className:"diskuf-files-block"}))},onFileIsInited:function(e,t){BX.addCustomEvent(t,"onFileIsAttached",this._onFileIsAttached);BX.addCustomEvent(t,"onFileIsAfterCreated",function(e,t,i,s){if(t&&!t.sizeInt){e.size=" "}});BX.addCustomEvent(t,"onFileIsAppended",this._onFileIsAppended);BX.addCustomEvent(t,"onFileIsBound",this._onFileIsBound);BX.addCustomEvent(t,"onUploadProgress",this._onUploadProgress);BX.addCustomEvent(t,"onUploadDone",this._onUploadDone);BX.addCustomEvent(t,"onUploadError",this._onUploadError)},onFileIs:function(e,t,i){this.bindEventsHandlers(e,t,i);var s={element_id:i.element_id,element_name:i.element_name||t.name,element_url:i.element_name||i.previewUrl||i.preview_url,place:e,storage:"disk"};this.values.push(e);BX.onCustomEvent(this.params.controller.parentNode,"OnFileUploadSuccess",[s,this]);t.__disk_element_id=i.element_id;BX.onCustomEvent(t,"OnFileUploadSuccess",[s,this])},onFileIsAppended:function(e,t){var i=this.agent.getItem(e),s=i.node;this.bindEventsHandlers(s,t,{})},onFileIsBound:function(e,t){var i=this.agent.getItem(e),s=i.node,o=s.getAttribute("id").replace("disk-edit-attach","");this.onFileIs(s,t,{element_id:o})},onFileIsAttached:function(e,t,i,s){if(!!s["sizeFormatted"])s["size"]=s["sizeFormatted"];if(!s.hasOwnProperty("service")){this.onUploadDone(t,{file:s});return}BX.Disk.ExternalLoader.startLoad({file:{id:s.id,name:s.name,service:s.service},onFinish:BX.delegate(function(e){var i=this.agent.getItem(t.id).node;BX.hide(i);var s={id:e.ufId,name:e.name,storage:e.storage,ext:t.ext,size:e.sizeFormatted,previewUrl:e.previewUrl,preview_url:e.previewUrl,element_url:e.previewUrl,size_int:parseInt(e.size,10)};var o;var n=BX.message("DISK_TMPLT_THUMB2").replace("#control_name#",this.params.controlName).replace("#CONTROL_NAME#",this.params.controlName);for(var a in s){o=s[a];if(s.hasOwnProperty(a)){if(a.toLowerCase()=="size"){if(s.hasOwnProperty("size_int")){if(parseInt(s["size_int"],10)==0||isNaN(s["size_int"])){o=""}}if(s.hasOwnProperty("SIZE_INT")||isNaN(s["SIZE_INT"])){if(parseInt(s["SIZE_INT"],10)==0){o=""}}}n=n.replace(new RegExp("#"+a.toLowerCase()+"#","gi"),o).replace(new RegExp("#"+a.toUpperCase()+"#","gi"),o)}}var r={id:"disk-edit-attach"+s.id,"bx-agentFileId":t.id},l;l=BX.create("TR",{attrs:r,props:{className:"wd-inline-file"},html:n});var d=BX.findChild(l,{className:"files-name-edit-btn"},true);if(d){BX.remove(d)}i.parentNode.insertBefore(l,i);s.element_id=s.id;this.agent.onAttach([s],[l]);t.deleteFile()},this),onProgress:BX.delegate(function(e){this.onUploadProgress(t,e)},this)}).start()},onUploadProgress:function(e,t){t=Math.min(t,98);var i=e.id;if(!e.__progressBarWidth)e.__progressBarWidth=5;if(t>e.__progressBarWidth){e.__progressBarWidth=Math.ceil(t);e.__progressBarWidth=e.__progressBarWidth>100?100:e.__progressBarWidth;if(BX("wdu"+i+"Progressbar"))BX.adjust(BX("wdu"+i+"Progressbar"),{style:{width:e.__progressBarWidth+"%"}});if(BX("wdu"+i+"ProgressbarText"))BX.adjust(BX("wdu"+i+"ProgressbarText"),{text:e.__progressBarWidth+"%"})}},onUploadDone:function(e,t){var i=this.agent.getItem(e.id).node,s=this._camelToSNAKE(t["file"]);if(BX(i)){var o=BX.message("DISK_TMPLT_THUMB2").replace("#control_name#",this.params.controlName).replace("#CONTROL_NAME#",this.params.controlName);for(var n in s){var a=s[n];if(s.hasOwnProperty(n)){if(n.toLowerCase()=="size"){if(s.hasOwnProperty("size_int")){if(parseInt(s["size_int"],10)==0){a=""}}if(s.hasOwnProperty("SIZE_INT")){if(parseInt(s["SIZE_INT"],10)==0){a=""}}}o=o.replace(new RegExp("#"+n.toLowerCase()+"#","gi"),a).replace(new RegExp("#"+n.toUpperCase()+"#","gi"),a)}}var r={id:"disk-edit-attach"+s.id,"bx-agentFileId":e.id},l;if(s["XML_ID"])r["bx-attach-xml-id"]=s["XML_ID"];if(s["FILE_ID"])r["bx-attach-file-id"]="n"+s["FILE_ID"];l=BX.create("TR",{attrs:r,props:{className:"wd-inline-file"},html:o});if(!e.file.canChangeName){var d=BX.findChild(l,{className:"files-name-edit-btn"},true);if(d){BX.remove(d)}}i.parentNode.replaceChild(l,i);s.element_id=s.id;this.onFileIs(l,e,s)}else{this.onUploadError(e,t,this.agent)}},onUploadError:function(e,t,i){var s=i.getItem(e.id);if(!!s&&(s=s.node)&&!!s){BX.adjust(s,{props:{className:"error-load"}});var o=t&&t["error"]?t["error"]:"Uploading error";s.cells[1].innerHTML="";s.cells[2].innerHTML='<span class="info-icon"></span><span class="error-text">'+o+"</span>";BX.onCustomEvent(e,"OnFileUploadFailed",[s,this])}},onError:function(e,t,i){var s="Uploading error.",o,n;if(i){if(i["error"]&&typeof i["error"]=="string")s=i["error"];else if(BX.type.isArray(i["errors"])&&i["errors"].length>0){s=[];for(var a=0;a<i["errors"].length;a++){if(typeof i["errors"][a]=="object"&&i["errors"][a]["message"])s.push(i["errors"][a]["message"])}if(s.length<=0)s.push("Uploading error.");s=s.join(" ")}}e.files=e.files||{};for(n in e.files){if(e.files.hasOwnProperty(n)){o=this.agent.queue.items.getItem(n);this.onUploadError(o,{error:s},this.agent)}}},onBlurRenameInput:function(e){var t=e.target||e.srcElement;var i=BX.findChild(t.parentNode,{className:"files-name-edit-btn"},true);if(!!i)BX.fireEvent(i,"click")},bindEventsHandlers:function(t,i,s){var o=s.element_id,n="file-action-control",a=BX.findChild(t,{className:"files-path"},true),l=BX.findChild(t,{className:"files-name-edit-btn"},true),d=BX.findChild(t,{className:"f-wrap"},true);if(!!a&&!!d&&!a.getAttribute(n)){a.setAttribute(n,"enabled");BX.bind(a,"click",BX.delegate(function(){this.move(o,d.innerHTML,t)},this))}if(!!l){BX.bind(l,"click",BX.delegate(function(){BX.toggleClass(l.parentNode,"files-name-editable");this.rename(o,t)},this));var h=BX.findChild(t,{className:"files-name-edit-inp"},true);if(h){BX.bind(h,"keydown",BX.delegate(function(i){var s=(i||e.event).keyCode||(i||e.event).charCode;if(s==13){BX.unbind(h,"blur",BX.proxy(this.onBlurRenameInput,this));BX.toggleClass(l.parentNode,"files-name-editable");this.rename(o,t);return BX.PreventDefault(i)}if(s==27){BX.unbind(h,"blur",BX.proxy(this.onBlurRenameInput,this));BX.toggleClass(l.parentNode,"files-name-editable");this.revertRename(o,t);return BX.PreventDefault(i)}},this))}}var u=BX.findChild(t,{className:"files-preview-wrap",tagName:"SPAN"},true);if(!!u)BX.bind(u,"mouseover",BX.delegate(function(){new r(this)},u));var c=BX.findChild(t,{className:"feed-add-post-loading"},true),m=BX.delegate(function(){this.deleteFile(t,i)},this),p=BX.create("SPAN",{props:{className:"del-but"},events:{click:m}});if(!!c){var f=BX.findChild(c,{className:"del-but"},true);if(!!f)BX.bind(f,"click",m);else c.appendChild(p.cloneNode(true))}if(!BX.findChild(t,{className:"files-info"},true)){t.appendChild(BX.create("TD",{props:{className:"files-info"}}))}if(!BX.findChild(t,{className:"files-del-btn"},true)){t.appendChild(BX.create("TD",{props:{className:"files-del-btn"},children:[p]}))}if(!p.hasAttribute("bx-bound")){p.setAttribute("bx-bound","Y");BX.bind(p,"click",m);BX.onCustomEvent(t,"OnMkClose",[t])}},deleteFile:function(e,t){var i=BX.findChild(e,{className:"file-delete"},true),s=BX.proxy_context;if(!!t.__disk_element_id){BX.onCustomEvent(this.controller.parentNode,"OnFileUploadRemove",[t.__disk_element_id,this])}BX.removeCustomEvent(t,"onFileIsAttached",this._onFileIsAttached);BX.removeCustomEvent(t,"onFileIsAppended",this._onFileIsAppended);BX.removeCustomEvent(t,"onFileIsBound",this._onFileIsBound);BX.removeCustomEvent(t,"onUploadProgress",this._onUploadProgress);BX.removeCustomEvent(t,"onUploadDone",this._onUploadDone);BX.removeCustomEvent(t,"onUploadError",this._onUploadError);delete t.hash;t.deleteFile("deleteFile");BX.remove(e);if(!!i&&i.getAttribute("href").length>0)BX.ajax.post(i.href,{sessid:BX.bitrix_sessid()})},move:function(e,t,i){var s=this._addUrlParam(this.urlSelect.replace("ACTION=SELECT","").replace("MULTI=Y",""),"ID=E"+e+"&NAME="+t+"&ACTION=FAKEMOVE&IFRAME=Y");while(s.indexOf("&&")>=0)s=s.replace("&&","&");if(!this._checkFileName){this._checkFileName=BX.proxy(this.checkFileName,this);this._selectFolder=BX.proxy(this.selectFolder,this);this._openSection=BX.proxy(this.openSection,this);this._selectItem=BX.proxy(this.selectItem,this);this._unSelectItem=BX.proxy(this.unSelectItem,this)}var o=i||BX("disk-edit-attach"+e),n=BX.findChild(o,{className:"f-wrap"},true);BX.DiskFileDialog.arParams={};BX.DiskFileDialog.arParams[this.dialogName]={element_id:e,element_name:n.innerHTML};BX.addCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection);BX.addCustomEvent(BX.DiskFileDialog,"loadItemsDone",this._checkFileName);BX.addCustomEvent(BX.DiskFileDialog,"selectItem",this._selectItem);BX.addCustomEvent(BX.DiskFileDialog,"unSelectItem",this._unSelectItem);return BX.ajax.get(s,"dialogName="+this.dialogName,BX.delegate(function(){setTimeout(BX.delegate(function(){BX.DiskFileDialog.obCallback[this.dialogName]={saveButton:this._selectFolder};BX.DiskFileDialog.openDialog(this.dialogName);this._checkFileName()},this),100)},this))},rename:function(e,t){if(!this.urlRenameFile){return}var i=BX.findChild(t,{className:"files-name-editable"},true);var s=BX.findChild(t,{className:"files-name-edit-inp"},true);var o=BX.findChild(t,{className:"f-wrap"},true);var n=o.textContent||o.innerText;var a=n.split(".").pop();var r=s.value+"."+a;if(!!i){BX.focus(s);BX.bind(s,"blur",BX.proxy(this.onBlurRenameInput,this))}else if(s.value&&r!==n){BX.adjust(o,{text:r});var l=t.getAttribute("bx-agentFileId");var d=l?this.agent.getItem(l):null;if(!!d){d.item.file.name=r;d.item.name=r}var h=this.manager?this.manager.checkFile("disk_file"+e):null;if(!!h){h.name=r}BX.ajax({url:this.urlRenameFile,method:"POST",dataType:"json",data:{newName:r,attachedId:e,sessid:BX.bitrix_sessid()},onsuccess:function(e){}})}},revertRename:function(e,t){if(!this.urlRenameFile){return}var i=BX.findChild(t,{className:"files-name-editable"},true);var s=BX.findChild(t,{className:"files-name-edit-inp"},true);var o=BX.findChild(t,{className:"f-wrap"},true);var n=o.textContent||o.innerText;var a=n.split(".");var r=a.pop();s.value=a.join(".")},showMovedFile:function(e,t,i){if(!e)return false;var s,o=e,n=BX("disk-edit-attach"+o),a=BX.findChild(n,{className:"files-path"},true);BX.cleanNode(a);i=i.split("/").join(" / ");a.innerHTML=i;var r=parseInt(a.offsetWidth);var l=parseInt(a.parentNode.offsetWidth)-150,d;s=l/(r/i.length);if(r>l){d=Math.floor(s/2)+1;i=i.substr(0,d)+" ... "+i.substr(i.length-d);a.innerHTML=i}var h=BX("diskuf-doc"+o);if(!!h){var u=this._fileUnserialize(h.value);u.section=t["sectionID"];u.iblock=t["iblockID"];h.value=this._fileSerialize(u)}return true},_fileUnserialize:function(e){if(!BX.type.isString(e))return false;var t=e.split("|");return{id:t[0]||0,section:t[1]||0,iblock:t[2]||0}},_fileSerialize:function(e){var t=[e.id,e.section,e.iblock];return t.join("|")},selectFolder:function(e,t,i,s){var o=false,n=false,a,r,l;if(BX.DiskFileDialog.arParams&&BX.DiskFileDialog.arParams[this.dialogName]&&BX.DiskFileDialog.arParams[this.dialogName]["element_id"])o=BX.DiskFileDialog.arParams[this.dialogName]["element_id"];for(a in i){if(i.hasOwnProperty(a)&&a.substr(0,1)=="S"){r=e.name+i[a].path;l={sectionID:a.substr(1),iblockID:e.iblock_id};this.showMovedFile(o,l,r);n=true}}if(!n){r=e.name;l={sectionID:e.section_id,iblockID:e.iblock_id};if(!!s&&!!s.path&&s.path!="/"){r+=s.path;l.sectionID=s.id.substr(1)}this.showMovedFile(o,l,r)}BX.removeCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection);BX.removeCustomEvent(BX.DiskFileDialog,"loadItemsDone",this._checkFileName);BX.removeCustomEvent(BX.DiskFileDialog,"selectItem",this._selectItem);BX.removeCustomEvent(BX.DiskFileDialog,"unSelectItem",this._unSelectItem)},checkFileName:function(e){if(this.noticeTimeout){clearTimeout(this.noticeTimeout);this.noticeTimeout=null}if(!!e&&e!=this.dialogName)return;var t=BX.DiskFileDialog.arParams[this.dialogName]["element_name"];var i=false,s;for(s in BX.DiskFileDialog.obItems[this.dialogName]){if(BX.DiskFileDialog.obItems[this.dialogName].hasOwnProperty(s)){if(BX.DiskFileDialog.obItems[this.dialogName][s]["name"]==t)i=true;if(i)break}}if(i)BX.DiskFileDialog.showNotice(BX.message("DISK_FILE_EXISTS"),this.dialogName);else BX.DiskFileDialog.closeNotice(this.dialogName)},selectItem:function(e,t,i){if(i!=this.dialogName)return;var s=t.substr(1);var o=BX.DiskFileDialog.obCurrentTab[i].link;o=o.replace("/index.php","")+"/element/upload/"+s+"/?use_light_view=Y&AJAX_CALL=Y&SIMPLE_UPLOAD=Y&IFRAME=Y&sessid="+BX.bitrix_sessid()+"&SECTION_ID="+s+"&CHECK_NAME="+BX.DiskFileDialog.arParams[this.dialogName]["element_name"];o=o.replace("/files/lib/","/files/");BX.ajax.loadJSON(o,BX.delegate(function(e){var t=e.permission===true&&e["okmsg"]!="";if(this.noticeTimeout){clearTimeout(this.noticeTimeout);this.noticeTimeout=null}this.noticeTimeout=setTimeout(BX.delegate(function(){if(t){BX.DiskFileDialog.showNotice(this.msg.file_exists,this.dialogName)}else{BX.DiskFileDialog.closeNotice(this.dialogName)}},this),200)},this))},unSelectItem:function(){if(this.noticeTimeout){clearTimeout(this.noticeTimeout);this.noticeTimeout=null}this.noticeTimeout=setTimeout(BX.delegate(function(){this.checkFileName()},this),200)},openSection:function(e,t){if(t==this.dialogName){BX.DiskFileDialog.target[t]=this._addUrlParam(e,"dialog2=Y")}},openSectionCloud:function(e,t){if(t==this.dialogName){BX.DiskFileDialog.target[t]=this._addUrlParam(e,"dialog2=Y");BX.DiskFileDialog.target[t]=this._addUrlParam(BX.DiskFileDialog.target[t],"cloudImport=1")}},selectFile:function(e,t,i){var s=[],o;for(o in i){if(i.hasOwnProperty(o)){if(i[o].type=="file"&&!BX(this.prefix+o)){i[o].sizeFormatted=i[o]["size"];i[o].size=i[o]["sizeInt"];if(!i[o]["ext"])i[o]["ext"]=i[o]["name"].split(".").pop();if(!i[o]["storage"])i[o]["storage"]="";s.push(i[o])}}}this.agent.onAttach(s,s);BX.removeCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection)},selectCloudFile:function(e,t,i){var s=[],o;for(o in i){if(i.hasOwnProperty(o)){if(i[o].type=="file"&&!BX(this.prefix+o)){if(i[o].hasOwnProperty("provider")){i[o].service=i[o].provider}else{i[o].service=n}i[o].sizeFormatted=i[o]["size"];i[o].size=i[o]["sizeInt"];if(!i[o]["ext"])i[o]["ext"]=i[o]["name"].split(".").pop();if(!i[o]["storage"])i[o]["storage"]="";s.push(i[o])}}}this.agent.onAttach(s,s);BX.removeCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection)},showSelectDialog:function(){this._openSection=BX.proxy(this.openSection,this);this._selectFile=BX.proxy(this.selectFile,this);BX.addCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection);BX.ajax.get(this.urlSelect,"dialogName="+this.dialogName,BX.delegate(function(){setTimeout(BX.delegate(function(){BX.DiskFileDialog.obCallback[this.dialogName]={saveButton:this._selectFile};BX.DiskFileDialog.openDialog(this.dialogName)},this),10)},this))},showSelectDialogCloudImport:function(e){var t=e.target||e.srcElement;if(!BX.hasClass(t,"diskuf-selector-link-cloud")){t=BX.findParent(t,{className:"diskuf-selector-link-cloud"})}if(!t||!t.getAttribute("data-bx-doc-handler"))return;n=t.getAttribute("data-bx-doc-handler");this._openSection=BX.proxy(this.openSectionCloud,this);this._selectFile=BX.proxy(this.selectFile,this);this._selectCloudFile=BX.proxy(this.selectCloudFile,this);BX.addCustomEvent(BX.DiskFileDialog,"loadItems",this._openSection);BX.ajax.get(this.urlSelect,"&cloudImport=1&service="+n+"&dialogName="+this.dialogName,BX.delegate(function(){setTimeout(BX.delegate(function(){BX.DiskFileDialog.obCallback[this.dialogName]={saveButton:this._selectCloudFile};BX.DiskFileDialog.openDialog(this.dialogName)},this),10)},this))}};return t}();BX.Disk.UF.dndCatcher={};BX.Disk.UF.add=function(e){e["controller"]=BX("diskuf-selectdialog-"+e["UID"]);e["values"]=BX.findChildren(e["controller"],{className:"wd-inline-file"},true);var t=BX(e["controller"]).parentNode;if(!BX(e["controller"]).hasAttribute("bx-disk-load-is-bound")){BX(e["controller"]).setAttribute("bx-disk-load-is-bound","Y");BX.addCustomEvent(t,"DiskLoadFormController",function(t){BX.Disk.UF.initialize(t,e)})}BX.onCustomEvent(e["controller"],"DiskLoadFormControllerWasBound",[e,"DiskLoadFormControllerWasBound"]);if(!!BX.DD){var i=e["controller"].parentNode,s=i.getAttribute("id");if(BX.type.isElementNode(i)){BX.addCustomEvent(i,"OnIframeDrop",BX.delegate(function(e){if(e["dataTransfer"]&&e["dataTransfer"]["files"]){BX.PreventDefault(e);if(BX.Disk.UF.dndCatcher[s]["uploader"]===null){BX.Disk.UF.dndCatcher[s].drop(e["dataTransfer"]["files"])}else{BX.onCustomEvent(BX(s),"DiskLoadFormController",["show"]);BX.Disk.UF.dndCatcher[s]["uploader"].agent.onChange(e["dataTransfer"]["files"])}return false}return true},this));BX.Disk.UF.dndCatcher[s]={files:[],uploader:null,dropZone:null,initdrag:BX.delegate(function(){var e=BX(s);BX.Disk.UF.dndCatcher[s].dropZone=new BX.DD.dropFiles(e);BX.addCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dropFiles",BX.Disk.UF.dndCatcher[s]["drop"]);BX.addCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dragEnter",BX.Disk.UF.dndCatcher[s]["dragover"]);BX.addCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dragLeave",BX.Disk.UF.dndCatcher[s]["dragleave"]);BX.unbind(document,"dragover",BX.Disk.UF.dndCatcher[s]["initdrag"])},this),dragover:BX.delegate(function(e){BX.addClass(BX(s),"bxu-file-input-over");BX.onCustomEvent(BX(s),"DiskLoadFormController",["show"])},this),dragleave:BX.delegate(function(e){BX.removeClass(BX(s),"bxu-file-input-over")},this),drop:BX.delegate(function(e){if(e&&e.length>0){BX.Disk.UF.dndCatcher[s].files=e;BX.onCustomEvent(BX(s),"DiskLoadFormController",["show"]);BX.removeClass(BX(s),"bxu-file-input-over")}},this)};BX.bind(document,"dragover",BX.Disk.UF.dndCatcher[s]["initdrag"]);this.__initCatcher=BX.delegate(function(e){var t=BX(s);e.agent.initDropZone(t);if(BX.Disk.UF.dndCatcher[s].files.length>0){e.agent.onChange(BX.Disk.UF.dndCatcher[s].files);BX.Disk.UF.dndCatcher[s].files=[]}BX.removeCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dropFiles",BX.Disk.UF.dndCatcher[s].drop);BX.removeCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dragEnter",BX.Disk.UF.dndCatcher[s].dragover);BX.removeCustomEvent(BX.Disk.UF.dndCatcher[s].dropZone,"dragLeave",BX.Disk.UF.dndCatcher[s].dragleave);BX.removeCustomEvent(t,"DiskDLoadFormControllerInit",this.__initCatcher);BX.Disk.UF.dndCatcher[s]["uploader"]=e;this.__initCatcher=null},this);BX.addCustomEvent(BX(i),"DiskDLoadFormControllerInit",this.__initCatcher)}}if(!!e["values"]&&e["values"].length>0)BX.onCustomEvent(e["controller"].parentNode,"DiskLoadFormController",["show"])};BX.Disk.UF.initialize=function(e,t){e=e==="show"||e==="hide"?e:t["controller"].style.display!="none"?"hide":"show";if(!t["controller"].loaded){t["controller"].loaded=true;a[t["UID"]]=new BX.Disk.UF(t)}if(e=="show"){if(t["controller"].style.display!="block"){BX.fx.show(t["controller"],"fade",{time:.2});if(t["switcher"]&&t["switcher"].style.display!="none")BX.fx.hide(t["switcher"],"fade",{time:.1});BX.onCustomEvent(t["controller"],"onControllerIsShown",[t["controller"],a[t["UID"]]]);o=a[t["UID"]]}}else if(t["controller"].style.display!="none"){o=null;BX.fx.hide(t["controller"],"fade",{time:.2});BX.onCustomEvent(t["controller"],"onControllerIsHidden",[t["controller"],a[t["UID"]]])}return a[t["UID"]]};var r=function(e){if(!BX(e)||e.hasAttribute("bx-is-bound"))return;e.setAttribute("bx-is-bound","Y");this.img=e;this.node=e.parentNode.parentNode.parentNode;BX.unbindAll(e);BX.unbindAll(this.node);BX.show(this.node);BX.remove(this.node.nextSibling);this.id="wufdp_"+Math.random();BX.bind(this.node,"mouseover",BX.delegate(function(){this.turnOn()},this));BX.bind(this.node,"mouseout",BX.delegate(function(){this.turnOff()},this))};r.prototype={turnOn:function(){this.timeout=setTimeout(BX.delegate(function(){this.show()},this),500)},turnOff:function(){clearTimeout(this.timeout);this.timeout=null;this.hide()},show:function(){if(this.popup!=null)this.popup.close();if(this.popup==null){this.popup=new BX.PopupWindow("bx-wufd-preview-img-"+this.id,this.img,{lightShadow:true,offsetTop:-7,offsetLeft:7,autoHide:true,closeByEsc:true,bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){this.popup=null},this)},content:BX.create("DIV",{attrs:{width:this.img.getAttribute("width"),height:this.img.getAttribute("height")},children:[BX.create("IMG",{attrs:{width:this.img.getAttribute("width"),height:this.img.getAttribute("height"),src:this.img.src}})]})});this.popup.show()}this.popup.setAngle({position:"bottom"});this.popup.bindOptions.forceBindPosition=true;this.popup.adjustPosition();this.popup.bindOptions.forceBindPosition=false},hide:function(){if(this.popup!=null)this.popup.close()}};BX.addCustomEvent("onDiskPreviewIsReady",function(e){new r(e)});BX.Disk.UF.runImport=function(e){BX.Disk.showActionModal({text:BX.message("DISK_UF_FILE_STATUS_PROCESS_LOADING"),showLoaderIcon:true,autoHide:false});BX.Disk.ExternalLoader.reloadLoadAttachedObject({attachedObject:{id:e.id,name:e.name,service:e.service},onFinish:BX.delegate(function(e){if(e.hasOwnProperty("hasNewVersion")&&!e.hasNewVersion){BX.Disk.showActionModal({text:BX.message("DISK_UF_FILE_STATUS_HAS_LAST_VERSION"),showSuccessIcon:true,autoHide:true})}else if(e.status==="success"){BX.Disk.showActionModal({text:BX.message("DISK_UF_FILE_STATUS_SUCCESS_LOADING"),showSuccessIcon:true,autoHide:true})}else{BX.Disk.showActionModal({text:BX.message("DISK_UF_FILE_STATUS_FAIL_LOADING"),autoHide:true})}},this),onProgress:BX.delegate(function(e){},this)}).start()};e.DiskCreateDocument=function(e){if(!o){return false}if(!e){return false}var t=new BX.CViewer({createDoc:true});var i=t.createBlankElementByParams({docType:e,editUrl:BX.message("DISK_CREATE_BLANK_URL"),renameUrl:BX.message("DISK_RENAME_FILE_URL")});t.setCurrent(i);i.afterSuccessCreate=function(e){var t={};var i=e.name.split(".");i.pop();t["E"+e.objectId]={type:"file",id:"n"+e.objectId,name:e.name,label:i.join("."),storage:e.folderName,size:e.size,sizeInt:e.sizeInt,ext:e.extension,canChangeName:true,link:e.link};o.selectFile({},{},t)};t.runActionByCurrentElement("create",{obElementViewer:t});try{BX.PreventDefault()}catch(s){}return false};e.DiskOpenMenuCreateService=function(e){var t=new BX.CViewer({});t.openMenu("disk_open_menu_with_services",BX(e),[{text:BX.message("DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT"),className:"bx-viewer-popup-item item-b24",href:"#",onclick:BX.delegate(function(t){if(BX.CViewer.isEnableLocalEditInDesktop()){this.setEditService("l");BX.adjust(e,{text:BX.message("DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT")});BX.PopupMenu.destroy("disk_open_menu_with_services")}else{this.helpDiskDialog()}return BX.PreventDefault(t)},t)},{text:t.getNameEditService("google"),className:"bx-viewer-popup-item item-gdocs",href:"#",onclick:BX.delegate(function(t){this.setEditService("google");BX.adjust(e,{text:this.getNameEditService("google")});BX.PopupMenu.destroy("disk_open_menu_with_services");return BX.PreventDefault(t)},t)},{text:t.getNameEditService("skydrive"),className:"bx-viewer-popup-item item-office",href:"#",onclick:BX.delegate(function(t){this.setEditService("skydrive");BX.adjust(e,{text:this.getNameEditService("skydrive")});BX.PopupMenu.destroy("disk_open_menu_with_services");return BX.PreventDefault(t)},t)}],{offsetTop:0,offsetLeft:25})};e.DiskActionFileMenu=function(e,t,i){s++;BX.PopupMenu.show("bx-viewer-wd-popup"+s+"_"+e,BX(t),i,{angle:{position:"top",offset:25},autoHide:true});return false};e.WDInlineElementClickDispatcher=function(e,t){var i=BX(t);if(i){BX.fireEvent(i,"click")}return false};e.showWebdavHistoryPopup=function(e,s,o){o=o||null;if(t){t.show();return}if(i==s){return}i=s;t=new BX.PopupWindow("bx_webdav_history_popup",o,{closeIcon:true,offsetTop:5,autoHide:true,zIndex:-100,content:BX.create("div",{children:[BX.create("div",{style:{display:"table",width:"665px",height:"225px"},children:[BX.create("div",{style:{display:"table-cell",verticalAlign:"middle",textAlign:"center"},children:[BX.create("div",{props:{className:"bx-viewer-wrap-loading-modal"}}),BX.create("span",{text:""})]})]})]}),closeByEsc:true,draggable:true,titleBar:{content:BX.create("span",{text:BX.message("WDUF_FILE_TITLE_REV_HISTORY")})},events:{onPopupClose:function(){t.destroy();t=i=false}}});t.show();BX.ajax.get(e,function(e){t.setContent(BX.create("DIV",{html:e}))})}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:88:"/bitrix/components/bitrix/search.tags.input/templates/.default/script.js?145227746813020";s:6:"source";s:72:"/bitrix/components/bitrix/search.tags.input/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
var Errors = {
	"result_unval" : "Error in result",
	"result_empty" : "Empty result"
};

function JsTc(oHandler, sParams, sParser) // TC = TagCloud
{
	var t = this;

	t.oObj = typeof oHandler == 'object' ? oHandler : document.getElementById("TAGS");
	t.sParams = sParams;
	// Arrays for data
	if (sParser)
	{
		t.sExp = new RegExp("["+sParser+"]+", "i");
	}
	else
	{
		t.sExp = new RegExp(",");
	}
	t.oLast = {"str":false, "arr":false};
	t.oThis = {"str":false, "arr":false};
	t.oEl = {"start":false, "end":false};
	t.oUnfinedWords = {};
	// Flags
	t.bReady = true;
	t.eFocus = true;
	// Array with results & it`s showing
	t.aDiv = null;
	t.oDiv = null;
	// Pointers
	t.oActive = null;
	t.oPointer = [];
	t.oPointer_default = [];
	t.oPointer_this = 'input_field';

	t.oObj.onblur = function()
	{
		t.eFocus = false;
	};

	t.oObj.onfocus = function()
	{
		if (!t.eFocus)
		{
			t.eFocus = true;
			setTimeout(function(){t.CheckModif('focus')}, 500);
		}
	};

	t.oLast["arr"] = t.oObj.value.split(t.sExp);
	t.oLast["str"] = t.oLast["arr"].join(":");

	setTimeout(function(){t.CheckModif('this')}, 500);

	this.CheckModif = function(__data)
	{
		var
			sThis = false, tmp = 0,
			bUnfined = false, word = "",
			cursor = {};

		if (!t.eFocus)
			return;

		if (t.bReady && t.oObj.value.length > 0)
		{
			// Preparing input data
			t.oThis["arr"] = t.oObj.value.split(t.sExp);
			t.oThis["str"] = t.oThis["arr"].join(":");

			// Getting modificated element
			if (t.oThis["str"] && (t.oThis["str"] != t.oLast["str"]))
			{
				cursor['position'] = TCJsUtils.getCursorPosition(t.oObj);
				if (cursor['position']['end'] > 0 && !t.sExp.test(t.oObj.value.substr(cursor['position']['end']-1, 1)))
				{
					cursor['arr'] = t.oObj.value.substr(0, cursor['position']['end']).split(t.sExp);
					sThis = t.oThis["arr"][cursor['arr'].length - 1];

					t.oEl['start'] = cursor['position']['end'] - cursor['arr'][cursor['arr'].length - 1].length;
					t.oEl['end'] = t.oEl['start'] + sThis.length;
					t.oEl['content'] = sThis;

					t.oLast["arr"] = t.oThis["arr"];
					t.oLast["str"] = t.oThis["str"];
				}
			}
			if (sThis)
			{
				// Checking for UnfinedWords
				for (tmp = 2; tmp <= sThis.length; tmp++)
				{
					word = sThis.substr(0, tmp);
					if (t.oUnfinedWords[word] == '!fined')
					{
						bUnfined = true;
						break;
					}
				}
				if (!bUnfined)
					t.Send(sThis);
			}
		}
		setTimeout(function(){t.CheckModif('this')}, 500);
	};

	t.Send = function(sSearch)
	{
		if (!sSearch)
			return false;

		var oError = [];
		t.bReady = false;
		if (BX('wait_container'))
		{
			BX('wait_container').innerHTML = BX.message('JS_CORE_LOADING');
			BX.show(BX('wait_container'));
		}
		BX.ajax.post(
			'/bitrix/components/bitrix/search.tags.input/search.php',
			{"search":sSearch, "params":t.sParams},
			function(data)
			{
				var result = {};
				t.bReady = true;

				try
				{
					eval("result = " + data + ";");
				}
				catch(e)
				{
					oError['result_unval'] = e;
				}

				if (TCJsUtils.empty(result))
					oError['result_empty'] = Errors['result_empty'];

				try
				{
					if (TCJsUtils.empty(oError) && (typeof result == 'object'))
					{
						if (!(result.length == 1 && result[0]['NAME'] == t.oEl['content']))
						{
							t.Show(result);
							return;
						}
					}
					else
					{
						t.oUnfinedWords[t.oEl['content']] = '!fined';
					}
				}
				catch(e)
				{
					oError['unknown_error'] = e;
				}

				if(BX('wait_container'))
					BX.hide(BX('wait_container'));
			}
		);
	};

	t.Show = function(result)
	{
		t.Destroy();
		t.oDiv = document.body.appendChild(document.createElement("DIV"));
		t.oDiv.id = t.oObj.id+'_div';

		t.oDiv.className = "search-popup";
		t.oDiv.style.position = 'absolute';

		t.aDiv = t.Print(result);
		var pos = TCJsUtils.GetRealPos(t.oObj);
		t.oDiv.style.width = parseInt(pos["width"]) + "px";
		TCJsUtils.show(t.oDiv, pos["left"], pos["bottom"]);
		TCJsUtils.addEvent(document, "click", t.CheckMouse);
		TCJsUtils.addEvent(document, "keydown", t.CheckKeyword);
	};

	t.Print = function(aArr)
	{
		var aEl = null;
		var aResult = [];
		var aRes = [];
		var iCnt = 0;
		var oDiv = null;
		var oSpan = null;
		var sPrefix = t.oDiv.id;

		for (var tmp_ in aArr)
		{
			// Math
			if (aArr.hasOwnProperty(tmp_))
			{
				aEl = aArr[tmp_];
				aRes = [];
				aRes['ID'] = (aEl['ID'] && aEl['ID'].length > 0) ? aEl['ID'] : iCnt++;
				aRes['GID'] = sPrefix + '_' + aRes['ID'];
				aRes['NAME'] = TCJsUtils.htmlspecialcharsEx(aEl['NAME']);
				aRes['~NAME'] = aEl['NAME'];
				aRes['CNT'] = aEl['CNT'];
				aResult[aRes['GID']] = aRes;
				t.oPointer.push(aRes['GID']);
				// Graph
				oDiv = t.oDiv.appendChild(document.createElement("DIV"));
				oDiv.id = aRes['GID'];
				oDiv.name = sPrefix + '_div';

				oDiv.className = 'search-popup-row';

				oDiv.onmouseover = function(){t.Init(); this.className='search-popup-row-active';};
				oDiv.onmouseout = function(){t.Init(); this.className='search-popup-row';};
				oDiv.onclick = function(e){
						t.oActive = this.id;
						t.Replace();
						t.Destroy();
						BX.PreventDefault(e);
					};

				oSpan = oDiv.appendChild(document.createElement("DIV"));
				oSpan.id = oDiv.id + '_NAME';
				oSpan.className = "search-popup-el search-popup-el-cnt";
				oSpan.innerHTML = aRes['CNT'];

				oSpan = oDiv.appendChild(document.createElement("DIV"));
				oSpan.id = oDiv.id + '_NAME';
				oSpan.className = "search-popup-el search-popup-el-name";
				oSpan.innerHTML = aRes['NAME'];
			}
		}
		t.oPointer.push('input_field');
		t.oPointer_default = t.oPointer;
		return aResult;
	};

	t.Destroy = function()
	{
		try
		{
			TCJsUtils.hide(t.oDiv);
			t.oDiv.parentNode.removeChild(t.oDiv);
		}
		catch(e)
		{}
		t.aDiv = [];
		t.oPointer = [];
		t.oPointer_default = [];
		t.oPointer_this = 'input_field';
		t.bReady = true;
		t.eFocus = true;
		t.oActive = null;

		TCJsUtils.removeEvent(document, "click", t.CheckMouse);
		TCJsUtils.removeEvent(document, "keydown", t.CheckKeyword);
	};

	t.Replace = function()
	{
		if (typeof t.oActive == 'string')
		{
			var tmp = t.aDiv[t.oActive];
			var tmp1 = '';
			if (typeof tmp == 'object')
			{
				var elEntities = document.createElement("textarea");
				elEntities.innerHTML = tmp['~NAME'];
				tmp1 = elEntities.value;
			}
			//this preserves leading spaces
			var start = t.oEl['start'];
			while(start < t.oObj.value.length && t.oObj.value.substring(start, start+1) == " ")
				start++;

			t.oObj.value = t.oObj.value.substring(0, start) + tmp1 + t.oObj.value.substr(t.oEl['end']);
			TCJsUtils.setCursorPosition(t.oObj, start + tmp1.length);
		}
	};

	t.Init = function()
	{
		t.oActive = false;
		t.oPointer = t.oPointer_default;
		t.Clear();
		t.oPointer_this = 'input_pointer';
	};

	t.Clear = function()
	{
		var oEl = t.oDiv.getElementsByTagName("div");
		if (oEl.length > 0 && typeof oEl == 'object')
		{
			for (var ii in oEl)
			{
				if (oEl.hasOwnProperty(ii))
				{
					var oE = oEl[ii];
					if (oE && (typeof oE == 'object') && (oE.name == t.oDiv.id + '_div'))
					{
						oE.className = "search-popup-row";
					}
				}
			}
		}
	};

	t.CheckMouse = function()
	{
		t.Replace();
		t.Destroy();
	};

	t.CheckKeyword = function(e)
	{
		if (!e)
			e = window.event;
		var oP = null;
		var oEl = null;
		if ((37 < e.keyCode && e.keyCode <41) || (e.keyCode == 13))
		{
			t.Clear();

			switch (e.keyCode)
			{
				case 38:
					oP = t.oPointer.pop();
					if (t.oPointer_this == oP)
					{
						t.oPointer.unshift(oP);
						oP = t.oPointer.pop();
					}

					if (oP != 'input_field')
					{
						t.oActive = oP;
						oEl = document.getElementById(oP);
						if (typeof oEl == 'object')
						{
							oEl.className = "search-popup-row-active";
						}
					}
					t.oPointer.unshift(oP);
					break;
				case 40:
					oP = t.oPointer.shift();
					if (t.oPointer_this == oP)
					{
						t.oPointer.push(oP);
						oP = t.oPointer.shift();
					}
					if (oP != 'input_field')
					{
						t.oActive = oP;
						oEl = document.getElementById(oP);
						if (typeof oEl == 'object')
						{
							oEl.className = "search-popup-row-active";
						}
					}
					t.oPointer.push(oP);
					break;
				case 39:
					t.Replace();
					t.Destroy();
					break;
				case 13:
					t.Replace();
					t.Destroy();
					if (TCJsUtils.IsIE())
					{
						e.returnValue = false;
						e.cancelBubble = true;
					}
					else
					{
						e.preventDefault();
						e.stopPropagation();
					}
					break;
			}
			t.oPointer_this	= oP;
		}
		else
		{
			t.Destroy();
		}
	}
}

var TCJsUtils =
{
	arEvents:  [],

	addEvent: function(el, evname, func)
	{
		if(el.attachEvent) // IE
			el.attachEvent("on" + evname, func);
		else if(el.addEventListener) // Gecko / W3C
			el.addEventListener(evname, func, false);
		else
			el["on" + evname] = func;
		this.arEvents[this.arEvents.length] = {'element': el, 'event': evname, 'fn': func};
	},

	removeEvent: function(el, evname, func)
	{
		if(el.detachEvent) // IE
			el.detachEvent("on" + evname, func);
		else if(el.removeEventListener) // Gecko / W3C
			el.removeEventListener(evname, func, false);
		else
			el["on" + evname] = null;
	},

	getCursorPosition: function(oObj)
	{
		var result = {'start': 0, 'end': 0};
		if (!oObj || (typeof oObj != 'object'))
			return result;
		try
		{
			if (document.selection != null && oObj.selectionStart == null)
			{
				oObj.focus();
				var oRange = document.selection.createRange();
				var oParent = oRange.parentElement();
				var sBookmark = oRange.getBookmark();
				var sContents_ = oObj.value;
				var sContents = sContents_;
				var sMarker = '__' + Math.random() + '__';

				while(sContents.indexOf(sMarker) != -1)
				{
					sMarker = '__' + Math.random() + '__';
				}

				if (!oParent || oParent == null || (oParent.type != "textarea" && oParent.type != "text"))
				{
					return result;
				}

				oRange.text = sMarker + oRange.text + sMarker;
				sContents = oObj.value;
				result['start'] = sContents.indexOf(sMarker);
				sContents = sContents.replace(sMarker, "");
				result['end'] = sContents.indexOf(sMarker);
				oObj.value = sContents_;
				oRange.moveToBookmark(sBookmark);
				oRange.select();
				return result;
			}
			else
			{
				return {
					'start': oObj.selectionStart,
					'end': oObj.selectionEnd
				};
			}
		}
		catch(e){}
		return result;
	},

	setCursorPosition: function(oObj, iPosition)
	{
		if (typeof oObj != 'object')
			return false;

		oObj.focus();

		try
		{
			if (document.selection != null && oObj.selectionStart == null)
			{
				var oRange = document.selection.createRange();
				oRange.select();
			}
			else
			{
				oObj.selectionStart = iPosition;
				oObj.selectionEnd = iPosition;
			}
			return true;
		}
		catch(e)
		{
			return false;
		}
	},

	printArray: function (oObj, sParser, iLevel)
	{
		try
		{
			var result = '';
			var space = '';

			if (iLevel==undefined)
				iLevel = 0;
			if (!sParser)
				sParser = "\n";

			for (var j=0; j<=iLevel; j++)
				space += '  ';

			for (var i in oObj)
			{
				if (oObj.hasOwnProperty(i))
				{
					if (typeof oObj[i] == 'object')
						result += space+i + " = {"+ sParser + TCJsUtils.printArray(oObj[i], sParser, iLevel+1) + ", " + sParser + "}" + sParser;
					else
						result += space+i + " = " + oObj[i] + "; " + sParser;
				}
			}
			return result;
		}
		catch(e)
		{
		}
	},

	empty: function(oObj)
	{
		if (oObj)
		{
			for (var i in oObj)
			{
				if (oObj.hasOwnProperty(i))
				{
					return false;
				}
			}
		}
		return true;
	},

	show: function(oDiv, iLeft, iTop)
	{
		if (typeof oDiv != 'object')
			return;
		var zIndex = parseInt(oDiv.style.zIndex);
		if(zIndex <= 0 || isNaN(zIndex))
			zIndex = 2200;
		oDiv.style.zIndex = zIndex;
		oDiv.style.left = iLeft + "px";
		oDiv.style.top = iTop + "px";
		return oDiv;
	},

	hide: function(oDiv)
	{
		if (oDiv)
			oDiv.style.display = 'none';
	},

	GetRealPos: function(el)
	{
		if(!el || !el.offsetParent)
			return false;

		var res = {};
		var objParent = el.offsetParent;
		res["left"] = el.offsetLeft;
		res["top"] = el.offsetTop;
		while(objParent && objParent.tagName != "BODY")
		{
			res["left"] += objParent.offsetLeft;
			res["top"] += objParent.offsetTop;
			objParent = objParent.offsetParent;
		}
		res["right"]=res["left"] + el.offsetWidth;
		res["bottom"]=res["top"] + el.offsetHeight;
		res["width"]=el.offsetWidth;
		res["height"]=el.offsetHeight;

		return res;
	},

	IsIE: function()
	{
		return (document.attachEvent && !TCJsUtils.IsOpera());
	},

	IsOpera: function()
	{
		return (navigator.userAgent.toLowerCase().indexOf('opera') != -1);
	},

	htmlspecialcharsEx: function(str)
	{
		return str.replace(/&amp;/g, '&amp;amp;').replace(/&lt;/g, '&amp;lt;').replace(/&gt;/g, '&amp;gt;').replace(/&quot;/g, '&amp;quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	},

	htmlspecialcharsback: function(str)
	{
		return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;;/g, '"').replace(/&amp;/g, '&');
	}
};

/* End */
;
; /* Start:"a:4:{s:4:"full";s:86:"/bitrix/components/bitrix/voting.vote.edit/templates/.default/script.js?14522774806119";s:6:"source";s:71:"/bitrix/components/bitrix/voting.vote.edit/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function() {
if (window.BVoteConstructor)
	return;
// uploader section
top.BVoteConstructor = window.BVoteConstructor = function(Params)
{
	this.controller = Params.controller;
	this.maxQ = parseInt(Params['maxQ']);
	this.maxA = parseInt(Params['maxA']);
	this.q = {num : 0, cnt : 0};
	this.a = [{num : 0, cnt : 0}];
	this.InitVoteForm();
};

window.BVoteConstructor.prototype.checkAnswerAdding = function(qId) {
	if (this.a[qId].list) {
		var nodeQuestion = BX('question_' + qId);
		if (this.a[qId].list.firstChild) {
			BX.unbindAll(nodeQuestion);
			var node = this.a[qId].list.firstChild;
			do {
				if (node == null)
					break;
				BX.unbind(node.firstChild, "focus", BX.proxy(this._do, this));
			} while (node = node.nextSibling);
		}
	}

	if (this.maxA > 0 && this.a[qId].cnt >= this.maxA) {
		if (this.a[qId].node) { BX.hide(this.a[qId].node); }
		return false;
	}
	if (this.a[qId].node) { BX.show(this.a[qId].node); }
	else if (this.a[qId].list) {
		if (this.a[qId].list.lastChild) {
			BX.bind(this.a[qId].list.lastChild.firstChild, "focus", BX.proxy(this._do, this));
		} else {
			BX.bind(nodeQuestion, "focus", BX.proxy(this._do, this));
		}
	}
	return true;
};

window.BVoteConstructor.prototype.checkQuestionAdding = function() {
	if (this.maxQ > 0 && this.q.cnt >= this.maxQ)
	{
		if (this.q.node)
			this.q.node.style.display = "none";
		return false;
	}
	if (this.q.node)
		this.q.node.style.display = "";
	return true;
};

window.BVoteConstructor.prototype.InitVoteForm = function() {
	var
		vOl = BX.findChild(this.controller, {"tagName" : "OL", "className" : "vote-questions"}, true),
		vLi = vOl.childNodes,
		regexp = /question_(\d+)/ig,
		num = !!vLi ? regexp.exec(vOl.lastChild.firstChild.firstChild.id) : [0, 0, 0];
	this.q.cnt = vLi.length;
	this.q.num = parseInt(num[1]);
	this.q.node = BX.findChild(this.controller, {"tagName" : "A", "className" : "addq"}, true);
	var
		aOl,
		aLi,
		regexpa,
		ii;
	for (ii in vLi)
	{
		if (vLi.hasOwnProperty(ii) && vLi[ii]["tagName"] == "LI")
		{
			aOl = BX.findChild(vLi[ii], {"tagName" : "OL"}, true);
			aLi = BX.findChildren(aOl, {"tagName" : "LI"}, false);
			regexpa = /answer_(\d+)__(\d+)_/gi;
			num = [0, 0, 0];
			if (aOl.lastChild)
			{
				num = regexpa.exec(aOl.lastChild.firstChild.id);
			}
			else
			{
				num = regexp.exec(vLi[ii].firstChild.firstChild.id);
				num[2] = 0;
			}
			this.a[num[1]] = {
				cnt : aLi.length,
				num : parseInt(num[2]),
				node: false,
				"list": aOl};
			this.checkAnswerAdding(num[1]);
		}
	}
	this.checkQuestionAdding();

	var nodeTags = ["LABEL", "A"],
		a;
	for (var nodeTag in nodeTags)
	{
		if (nodeTags.hasOwnProperty(nodeTag))
		{
			a = BX.findChildren(vOl.parentNode, {"tagName" : nodeTags[nodeTag]}, true);
			for (ii in a)
			{
				if (a.hasOwnProperty(ii))
					BX.bind(a[ii], "click", BX.delegate(this._do, this));
			}
		}
	}
};

window.BVoteConstructor.prototype._do = function()
{
	var
		reg = /(add|del)\w/,
		node = BX.proxy_context,
		className = reg.exec(BX.proxy_context.className),
		res,
		ii,
		a,
		q,
		aOl,
		qOl,
		regexp;
	if (!!className)
	{
		switch (className[0])
		{
			case "adda" :
				var qLi = BX.findParent(node, {"className" : "vote-question", "tagName" : "li"});
				aOl = BX.findChild(qLi, {"tagName" : "OL"}, true);
				regexp = /answer_(\d+)__(\d+)_/i;
				q = regexp.exec(node.getAttribute("id"));
				if (!q)
				{
					regexp = /question_(\d+)/i;
					q = regexp.exec(node.getAttribute("id"));
				}
				q = (!!q ? q[1] : null);
				if (q != null && this.checkAnswerAdding(q))
				{
					this.a[q].num++; this.a[q].cnt++;
					res = BX.create('DIV', {'html' : BX.message('VOTE_TEMPLATE_ANSWER').
							replace(/#Q#/gi, q).replace(/#A#/gi, this.a[q].num).
							replace(/#A_VALUE#/gi, "").replace(/#A_PH#/gi, (this.a[q].num + 1))});
					a = BX.findChildren(res.firstChild, {"tagName" : "LABEL"}, true);
					for (ii in a)
					{
						if (a.hasOwnProperty(ii))
						{
							BX.bind(a[ii], "click", BX.delegate(this._do, this));
						}
					}
					aOl.appendChild(res.firstChild);
					this.checkAnswerAdding(q);
				}
				break;
			case "dela" :
				regexp = /answer_(\d+)__(\d+)_/i;
				q = regexp.exec(node.getAttribute("for"));
				q = (!!q ? q[1] : null);
				var aLi = BX.findParent(node, {"tagName" : "li"});
				aOl = BX.findParent(aLi, {"tagName" : "OL"});
				node = BX(node.getAttribute("for"));
				if (node.value != '' && !confirm(BX.message("VVE_ANS_DELETE")))
					return false;
				aOl.removeChild(aLi);
				this.a[q].cnt--;
				this.checkAnswerAdding(q);
				break;
			case "addq" :
				if (this.checkQuestionAdding())
				{
					qOl = BX.findChild(node.parentNode, {"tag" : "OL"}, false);
					this.q.num++; this.q.cnt++;
					res = BX.message('VOTE_TEMPLATE_ANSWER').replace(/#A#/gi, 0).replace(/#A_PH#/gi, 1).replace(/#A_VALUE#/gi, "") +
						BX.message('VOTE_TEMPLATE_ANSWER').replace(/#A#/gi, 1).replace(/#A_PH#/gi, 2).replace(/#A_VALUE#/gi, "");
					res = BX.create("DIV", {html : BX.message('VOTE_TEMPLATE_QUESTION').
						replace(/#ANSWERS#/gi, res).replace(/#Q#/gi, this.q.num).
						replace(/#Q_VALUE#/gi, "").replace(/#Q_MULTY#/gi, "")});
					a = BX.findChildren(res.firstChild, {"tagName" : "LABEL"}, true);
					for (ii in a)
					{
						if (a.hasOwnProperty(ii))
						{
							BX.bind(a[ii], "click", BX.delegate(this._do, this));
						}
					}

					this.a[this.q.num] = {
						num : 1,
						cnt : 2,
						node: false,
						"list": BX.findChild(res, {"tag" : "OL"}, true, false)};

					qOl.appendChild(res.firstChild);
					BX('question_' + this.q.num).focus();
					this.checkQuestionAdding();
					this.checkAnswerAdding(this.q.num);
				}
				break;
			case "delq" :
				q = node.getAttribute("for");
				var question = node.previousSibling;
				qOl = BX.findParent(question, {"tagName" : "OL"});
				q = parseInt(q.replace(/question_/gi, ""));
				if (question.value != '' && !confirm(BX.message("VVE_QUESTION_DELETE")))
					return false;
				qOl.removeChild(BX.findParent(question, {"tagName" : "LI"}));
				this.q.cnt--;
				this.checkQuestionAdding();
				break;
		}
	}
	return true;
};
})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:93:"/bitrix/components/bitrix/calendar.livefeed.edit/templates/.default/script.js?145227743719690";s:6:"source";s:77:"/bitrix/components/bitrix/calendar.livefeed.edit/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(window){

	//
	window.EditEventManager = function(config)
	{
		this.config = config;
		this.id = this.config.id;
		this.bAMPM = this.config.bAMPM;
		//this.bPanelShowed = true;
		this.bFullDay = false;
		this.bReminder = false;
		this.bAdditional = false;

		var _this = this;

		BX.addCustomEvent('onCalendarLiveFeedShown', function()
		{
			_this.Init();

			_this.defaultValues = {
				remind: {count: 15, type: 'min'}
			};

			_this.config.arEvent = _this.HandleEvent(_this.config.arEvent);
			_this.ShowFormData(_this.config.arEvent);
		});
	};

	window.EditEventManager.prototype = {
		Init: function()
		{
			var _this = this;
			// From-to
			this.pFromToCont = BX('feed-cal-from-to-cont' + this.id);
			this.pFromDate = BX('feed-cal-event-from' + this.id);
			this.pToDate = BX('feed-cal-event-to' + this.id);
			this.pFromTime = BX('feed_cal_event_from_time' + this.id);
			this.pToTime = BX('feed_cal_event_to_time' + this.id);
			this.pFullDay = BX('event-full-day' + this.id);
			this.pFromTs = BX('event-from-ts' + this.id);
			this.pToTs = BX('event-to-ts' + this.id);
			//Reminder
			this.pReminderCont = BX('feed-cal-reminder-cont' + this.id);
			this.pReminder = BX('event-reminder' + this.id);

			this.pEventName = BX('feed-cal-event-name' + this.id);
			this.pForm = this.pEventName.form;
			this.pLocation = BX('event-location' + this.id);
			this.pImportance = BX('event-importance' + this.id);
			this.pAccessibility = BX('event-accessibility' + this.id);
			this.pSection = BX('event-section' + this.id);
			this.pRemCount = BX('event-remind_count' + this.id);
			this.pRemType = BX('event-remind_type' + this.id);

			// Control events
			this.pFullDay.onclick = BX.proxy(this.FullDay, this);
			this.pReminder.onclick = BX.proxy(this.Reminder, this);

			BX.bind(this.pForm, 'submit', BX.proxy(this.OnSubmit, this));
			// *************** Init events ***************

			BX("feed-cal-additional-show").onclick = BX("feed-cal-additional-hide").onclick = BX.proxy(this.ShowAdditionalParams, this);

			this.InitDateTimeControls();

			var oEditor = window["BXHtmlEditor"].Get(this.config.editorId);
			if (oEditor && oEditor.IsShown())
			{
				this.CustomizeHtmlEditor(oEditor);
			}
			else
			{
				BX.addCustomEvent(window["BXHtmlEditor"], 'OnEditorCreated', function(editor)
				{
					if (editor.id == _this.config.editorId)
					{
						_this.CustomizeHtmlEditor(editor);
					}
				});
			}

			// repeat
			this.pRepeat = BX('event-repeat' + this.id);
			this.pRepeatDetails = BX('event-repeat-details' + this.id);
			this.RepeatDiapTo = BX('event-repeat-to' + this.id);
			this.RepeatDiapToValue = BX('event-repeat-to-value' + this.id);

			this.pRepeat.onchange = function()
			{
				var value = this.value;
				_this.pRepeatDetails.className = "feed-cal-repeat-details feed-cal-repeat-details-" + value.toLowerCase();
			};
			this.pRepeat.onchange();

			this.RepeatDiapTo.onclick = function(){
				BX.calendar({node: this, field: this, bTime: false});
				BX.focus(this);
			};
			this.RepeatDiapTo.onfocus = function()
			{
				if (!this.value || this.value == _this.config.message.NoLimits)
					this.title = this.value = '';
				this.style.color = '#000000';
			};
			this.RepeatDiapTo.onblur = this.RepeatDiapTo.onchange = function()
			{
				if (this.value && this.value != _this.config.message.NoLimits)
				{
					var until = BX.parseDate(this.value);
					if (until && until.getTime)
						_this.RepeatDiapToValue.value = BX.date.getServerTimestamp(until.getTime());
					this.style.color = '#000000';
					this.title = '';
					return;
				}
				this.title = this.value = _this.config.message.NoLimits;
				this.style.color = '#C0C0C0';
			};
			this.RepeatDiapTo.onchange();

			this.eventNode = BX('div' + this.config.editorId);
			if (this.eventNode)
			{
				BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['justShow']);
			}
		},

		CustomizeHtmlEditor: function(editor)
		{
			if (editor.toolbar.controls && editor.toolbar.controls.spoiler)
			{
				BX.remove(editor.toolbar.controls.spoiler.pCont);
			}
		},

		InitDateTimeControls: function()
		{
			var _this = this;
			// Date
			this.pFromDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};
			this.pToDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};

			this.pFromDate.onchange = function()
			{
				if(_this._FromDateValue)
				{
					var
						prevF = BX.parseDate(_this._FromDateValue),
						F = BX.parseDate(_this.pFromDate.value),
						T = BX.parseDate(_this.pToDate.value);

					if (F)
					{
						var duration = T.getTime() - prevF.getTime();
						T = new Date(F.getTime() + duration);
						_this.pToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
					}
				}
				_this._FromDateValue = _this.pFromDate.value;
			};

			// Time
			this.pFromTime.parentNode.onclick = this.pFromTime.onclick = window['bxShowClock_' + 'feed_cal_event_from_time' + this.id];
			this.pToTime.parentNode.onclick = this.pToTime.onclick = window['bxShowClock_' + 'feed_cal_event_to_time' + this.id];

			this.pFromTime.onchange = function()
			{
				var fromTime, toTime;
				if (_this.pToTime.value == "")
				{
					if(BX.util.trim(_this.pFromDate.value) == BX.util.trim(_this.pToDate.value) && BX.util.trim(_this.pToDate.value) != '')
					{
						fromTime = _this.ParseTime(this.value);
						if (fromTime.h >= 23)
						{
							_this.pToTime.value = formatTimeByNum(0, fromTime.m, _this.bAMPM);
							var date = BX.parseDate(_this.pFromDate.value);
							if (date)
							{
								date.setDate(date.getDate() + 1);
								_this.pToDate.value = bxFormatDate(date.getDate(), date.getMonth() + 1, date.getFullYear());
							}
						}
						else
						{
							_this.pToTime.value = formatTimeByNum(parseInt(fromTime.h, 10) + 1, fromTime.m, _this.bAMPM);
						}
					}
					else
					{
						_this.pToTime.value = _this.pFromTime.value;
					}
				}
				else if (_this.pToDate.value == '' || _this.pToDate.value == _this.pFromDate.value)
				{
					if (_this.pToDate.value == '')
						_this.pToDate.value = _this.pFromDate.value;

					// 1. We need prev. duration
					if(_this._FromTimeValue)
					{
						var
							F = BX.parseDate(_this.pFromDate.value),
							T = BX.parseDate(_this.pToDate.value),
							prevFromTime = _this.ParseTime(_this._FromTimeValue);

						fromTime = _this.ParseTime(_this.pFromTime.value);
						toTime = _this.ParseTime(_this.pToTime.value);

						F.setHours(prevFromTime.h);
						F.setMinutes(prevFromTime.m);
						T.setHours(toTime.h);
						T.setMinutes(toTime.m);

						var duration = T.getTime() - F.getTime();
						if (duration != 0)
						{
							F.setHours(fromTime.h);
							F.setMinutes(fromTime.m);

							T = new Date(F.getTime() + duration);
							_this.pToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
							_this.pToTime.value = formatTimeByNum(T.getHours(), T.getMinutes(), _this.bAMPM);
						}
					}
				}

				_this._FromTimeValue = _this.pFromTime.value;
			};
		},

		OnSubmit: function()
		{

			// Datetime limits
			var fd = BX.parseDate(this.pFromDate.value);
			var td = BX.parseDate(this.pToDate.value);

			if (!fd)
				fd = getUsableDateTime(new Date().getTime()).oDate;

			if (this.pFromTime.value == '' && this.pToTime.value == '')
				this.pFullDay.checked = true;

			if (this.pFullDay.checked)
				this.pFromTime.value = this.pToTime.value = '';

			var fromTime = this.ParseTime(this.pFromTime.value);
			fd.setHours(fromTime.h);
			fd.setMinutes(fromTime.m);
			var
				to,
				from = BX.date.getServerTimestamp(fd.getTime());

			if (td)
			{
				var toTime = this.ParseTime(this.pToTime.value);
				td.setHours(toTime.h);
				td.setMinutes(toTime.m);
				to = BX.date.getServerTimestamp(td.getTime());

				if (from == to && toTime.h == 0 && toTime.m == 0)
				{
					fd.setHours(0);
					fd.setMinutes(0);
					td.setHours(0);
					td.setMinutes(0);

					from = BX.date.getServerTimestamp(fd.getTime());
					to = BX.date.getServerTimestamp(td.getTime());
				}
			}

			this.pFromTs.value = from;
			this.pToTs.value = to;
		},

		HandleEvent: function(oEvent)
		{
			if(oEvent)
			{
				oEvent.DT_FROM_TS = BX.date.getBrowserTimestamp(oEvent.DT_FROM_TS);
				oEvent.DT_TO_TS = BX.date.getBrowserTimestamp(oEvent.DT_TO_TS);

				if (oEvent.DT_FROM_TS > oEvent.DT_TO_TS)
					oEvent.DT_FROM_TS = oEvent.DT_TO_TS;

				if ((oEvent.RRULE && oEvent.RRULE.FREQ && oEvent.RRULE.FREQ != 'NONE'))
				{
					oEvent['~DT_FROM_TS'] = BX.date.getBrowserTimestamp(oEvent['~DT_FROM_TS']);
					oEvent['~DT_TO_TS'] = BX.date.getBrowserTimestamp(oEvent['~DT_TO_TS']);

					if (oEvent.RRULE && oEvent.RRULE.UNTIL)
						oEvent.RRULE.UNTIL = BX.date.getBrowserTimestamp(oEvent.RRULE.UNTIL);
				}
			}
			return oEvent;
		},

		ShowFormData: function(oEvent)
		{
			var bNew = false;
			if (!oEvent || !oEvent.ID)
			{
				bNew = true;
				oEvent = {};
			}

			// Name
			this.pEventName.value = oEvent.NAME || '';

			// From / To
			var fd, td;
			if (oEvent.DT_FROM_TS || oEvent.DT_TO_TS)
			{
				if (!(oEvent.RRULE && oEvent.RRULE.FREQ && oEvent.RRULE.FREQ != 'NONE'))
				{
					fd = bxGetDateFromTS(oEvent.DT_FROM_TS);
					td = bxGetDateFromTS(oEvent.DT_TO_TS);
				}
				else
				{
					fd = bxGetDateFromTS(oEvent['~DT_FROM_TS']);
					td = bxGetDateFromTS(oEvent['~DT_TO_TS']);
				}
			}
			else
			{
				fd = getUsableDateTime(new Date().getTime());
				td = getUsableDateTime(new Date().getTime() + 3600000 /* one hour*/);
			}

			if (fd)
			{
				this._FromDateValue = this.pFromDate.value = bxFormatDate(fd.date, fd.month, fd.year);
				this._FromTimeValue = this.pFromTime.value = fd.bTime ? formatTimeByNum(fd.hour, fd.min, this.bAMPM) : '';
			}
			else
			{
				this._FromDateValue = this._FromTimeValue = this.pFromDate.value = this.pFromTime.value = '';
			}

			if (td)
			{
				this.pToDate.value = bxFormatDate(td.date, td.month, td.year);
				this.pToTime.value = td.bTime ? formatTimeByNum(td.hour, td.min, this.bAMPM) : '';
			}
			else
			{
				this.pToDate.value = this.pToTime.value = '';
			}

			this.pFullDay.checked = oEvent.DT_SKIP_TIME == "Y";
			this.FullDay(false, oEvent.DT_SKIP_TIME !== "Y");

			if (bNew)
			{
				this.pLocation.value = '';
				this.pImportance.value = 'normal';
				this.pAccessibility.value = 'busy';
				if (this.pSection.options && this.pSection.options.length > 0)
					this.pSection.value = this.pSection.options[0].value;

				this.pReminder.checked = !!this.defaultValues.remind;
				this.pRemCount.value = (this.defaultValues.remind && this.defaultValues.remind.count) || '15';
				this.pRemType.value = (this.defaultValues.remind && this.defaultValues.remind.type) || 'min';
			}
			else
			{
				this.pLocation.value = oEvent.LOCATION;
				this.pImportance.value = oEvent.IMPORTANCE;
				this.pAccessibility.value = oEvent.ACCESSIBILITY;
				this.pSection.value = oEvent.SECT_ID;


				// Remind
				this.pReminder.checked = oEvent.REMIND && oEvent.REMIND[0];
				this.pRemCount.value = oEvent.REMIND[0].count;
				this.pRemType.value = oEvent.REMIND[0].type;
			}
			this.Reminder(false, true);

			var _this = this;
			setTimeout(function()
			{
				BX.focus(_this.pEventName);
			}, 100);
		},

		FullDay: function(bSaveOption, value)
		{
			if (value == undefined)
				value = !this.bFullDay;

			if (value)
				BX.removeClass(this.pFromToCont, 'feed-cal-full-day');
			else
				BX.addClass(this.pFromToCont, 'feed-cal-full-day');
			this.bFullDay = value;
		},

		Reminder: function(bSaveOption, value)
		{
			if (value == undefined)
				value = !this.bReminder;

			this.pReminderCont.className = value ? 'feed-event-reminder' : 'feed-event-reminder-collapsed';

			this.bReminder = value;
		},

		ShowAdditionalParams: function()
		{
			var value = !this.bAdditional;
			if (!this.pAdditionalCont)
				this.pAdditionalCont = BX("feed-cal-additional");

			if (value)
				BX.removeClass(this.pAdditionalCont, 'feed-event-additional-hidden');
			else
				BX.addClass(this.pAdditionalCont, 'feed-event-additional-hidden');

			this.bAdditional = value;
		},

		ParseTime: function(str)
		{
			var h, m, arTime;
			str = BX.util.trim(str);
			str = str.toLowerCase();

			if (this.bAMPM)
			{
				var ampm = 'pm';
				if (str.indexOf('am') != -1)
					ampm = 'am';

				str = str.replace(/[^\d:]/ig, '');
				arTime = str.split(':');
				h = parseInt(arTime[0] || 0, 10);
				m = parseInt(arTime[1] || 0, 10);

				if (h == 12)
				{
					if (ampm == 'am')
						h = 0;
					else
						h = 12;
				}
				else if (h != 0)
				{
					if (ampm == 'pm' && h < 12)
					{
						h += 12;
					}
				}
			}
			else
			{
				arTime = str.split(':');
				h = arTime[0] || 0;
				m = arTime[1] || 0;

				if (h.toString().length > 2)
					h = parseInt(h.toString().substr(0, 2));
				m = parseInt(m);
			}

			if (isNaN(h) || h > 24)
				h = 0;
			if (isNaN(m) || m > 60)
				m = 0;

			return {h: h, m: m};
		}
	};

	// Calbacks for destination
	window.BXEvDestSetLinkName = function(name)
	{
		if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
			BX('feed-event-dest-add-link').innerHTML = BX.message("BX_FPD_LINK_1");
		else
			BX('feed-event-dest-add-link').innerHTML = BX.message("BX_FPD_LINK_2");
	};

	window.BXEvDestSelectCallback = function(item, type, search)
	{
		var
			type1 = type,
			prefix = 'S';

		if (type == 'sonetgroups')
			prefix = 'SG';
		else if (type == 'groups')
		{
			prefix = 'UA';
			type1 = 'all-users';
		}
		else if (type == 'users')
			prefix = 'U';
		else if (type == 'department')
			prefix = 'DR';

		BX('feed-event-dest-item').appendChild(
			BX.create("span", { attrs : { 'data-id' : item.id }, props : { className : "feed-event-destination feed-event-destination-"+type1 }, children: [
				BX.create("input", { attrs : { 'type' : 'hidden', 'name' : 'EVENT_PERM[' + prefix + '][]', 'value' : item.id }}),
				BX.create("span", { props : { 'className' : "feed-event-destination-text" }, html : item.name}),
				BX.create("span", { props : { 'className' : "feed-event-del-but"}, events : {'click' : function(e){BX.SocNetLogDestination.deleteItem(item.id, type, destinationFormName);BX.PreventDefault(e)}, 'mouseover' : function(){BX.addClass(this.parentNode, 'feed-event-destination-hover')}, 'mouseout' : function(){BX.removeClass(this.parentNode, 'feed-event-destination-hover')}}})
			]})
		);

		BX('feed-event-dest-input').value = '';
		BXEvDestSetLinkName(destinationFormName);
	};

	// remove block
	window.BXEvDestUnSelectCallback = function(item, type, search)
	{
		var elements = BX.findChildren(BX('feed-event-dest-item'), {attribute: {'data-id': ''+item.id+''}}, true);
		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
				BX.remove(elements[j]);
		}
		BX('feed-event-dest-input').value = '';
		BXEvDestSetLinkName(destinationFormName);
	};
	window.BXEvDestOpenDialogCallback = function()
	{
		BX.style(BX('feed-event-dest-input-box'), 'display', 'inline-block');
		BX.style(BX('feed-event-dest-add-link'), 'display', 'none');
		BX.focus(BX('feed-event-dest-input'));
	};

	window.BXEvDestCloseDialogCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.style(BX('feed-event-dest-input-box'), 'display', 'none');
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline-block');
			BXEvDestDisableBackspace();
		}
	};

	window.BXEvDestCloseSearchCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-event-dest-input').value.length > 0)
		{
			BX.style(BX('feed-event-dest-input-box'), 'display', 'none');
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline-block');
			BX('feed-event-dest-input').value = '';
			BXEvDestDisableBackspace();
		}

	};
	window.BXEvDestDisableBackspace = function()
	{
		if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable != null)
			BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

		BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(e)
		{
			if (e.keyCode == 8)
			{
				BX.PreventDefault(e);
				return false;
			}
		});
		setTimeout(function()
		{
			BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
			BX.SocNetLogDestination.backspaceDisable = null;
		}, 5000);
	};

	window.BXEvDestSearchBefore = function(event)
	{
		if (event.keyCode == 8 && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(destinationFormName);
		}

		return true;
	};
	window.BXEvDestSearch = function(event)
	{
		if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
			return false;

		if (event.keyCode == 13)
		{
			BX.SocNetLogDestination.selectFirstSearchItem(destinationFormName);
			return true;
		}
		if (event.keyCode == 27)
		{
			BX('feed-event-dest-input').value = '';
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline');
		}
		else
		{
			BX.SocNetLogDestination.search(BX('feed-event-dest-input').value, true, destinationFormName);
		}

		if (!BX.SocNetLogDestination.isOpenDialog() && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.SocNetLogDestination.openDialog(destinationFormName);
		}
		else
		{
			if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
				BX.SocNetLogDestination.closeDialog();
		}
		if (event.keyCode == 8)
		{
			BX.SocNetLogDestination.sendEvent = true;
		}
		return true;
	};

	function bxFormatDate(d, m, y)
	{
		var str = BX.message("FORMAT_DATE");

		str = str.replace(/YY(YY)?/ig, y);
		str = str.replace(/MMMM/ig, BX.message('MONTH_' + this.Number(m)));
		str = str.replace(/MM/ig, zeroInt(m));
		str = str.replace(/M/ig, BX.message('MON_' + this.Number(m)));
		str = str.replace(/DD/ig, zeroInt(d));

		return str;
	}

	function zeroInt(x)
	{
		x = parseInt(x, 10);
		if (isNaN(x))
			x = 0;
		return x < 10 ? '0' + x.toString() : x.toString();
	}

	function bxGetDateFromTS(ts, getObject)
	{
		var oDate = new Date(ts);
		if (!getObject)
		{
			var
				ho = oDate.getHours() || 0,
				mi = oDate.getMinutes() || 0;

			oDate = {
				date: oDate.getDate(),
				month: oDate.getMonth() + 1,
				year: oDate.getFullYear(),
				bTime: !!(ho || mi),
				oDate: oDate
			};

			if (oDate.bTime)
			{
				oDate.hour = ho;
				oDate.min = mi;
			}
		}

		return oDate;
	}

	function getUsableDateTime(timestamp, roundMin)
	{
		var date = bxGetDateFromTS(timestamp);
		if (!roundMin)
			roundMin = 10;

		date.min = Math.ceil(date.min / roundMin) * roundMin;

		if (date.min == 60)
		{
			if (date.hour == 23)
				date.bTime = false;
			else
				date.hour++;
			date.min = 0;
		}

		date.oDate.setHours(date.hour);
		date.oDate.setMinutes(date.min);
		return date;
	}

	function formatTimeByNum(h, m, bAMPM)
	{
		var res = '';
		if (m == undefined)
			m = '00';
		else
		{
			m = parseInt(m, 10);
			if (isNaN(m))
				m = '00';
			else
			{
				if (m > 59)
					m = 59;
				m = (m < 10) ? '0' + m.toString() : m.toString();
			}
		}

		h = parseInt(h, 10);
		if (h > 24)
			h = 24;
		if (isNaN(h))
			h = 0;

		if (bAMPM)
		{
			var ampm = 'am';

			if (h == 0)
			{
				h = 12;
			}
			else if (h == 12)
			{
				ampm = 'pm';
			}
			else if (h > 12)
			{
				ampm = 'pm';
				h -= 12;
			}

			res = h.toString() + ':' + m.toString() + ' ' + ampm;
		}
		else
		{
			res = ((h < 10) ? '0' : '') + h.toString() + ':' + m.toString();
		}
		return res;
	}

})(window);



/* End */
;
; /* Start:"a:4:{s:4:"full";s:90:"/bitrix/components/bitrix/lists.live.feed/templates/.default/script.min.js?145227745921419";s:6:"source";s:70:"/bitrix/components/bitrix/lists.live.feed/templates/.default/script.js";s:3:"min";s:74:"/bitrix/components/bitrix/lists.live.feed/templates/.default/script.min.js";s:3:"map";s:74:"/bitrix/components/bitrix/lists.live.feed/templates/.default/script.map.js";}"*/
BX.LiveFeedClass=function(){var t=function(t){this.ajaxUrl="/bitrix/components/bitrix/lists.live.feed/ajax.php";this.socnetGroupId=t.socnetGroupId;this.randomString=t.randomString;this.listData=t.listData;var e=this;BX.addCustomEvent("onDisplayClaimLiveFeed",function(t){e.init(t)});if(this.listData){var s=[this.listData.ID,this.listData.NAME,this.listData.DESCRIPTION,this.listData.PICTURE,this.listData.CODE];window.SBPETabs.changePostFormTab("lists",s)}};t.prototype.init=function(t){if(t instanceof Array){var e=t[0],s=t[1],i=t[2],o=t[3],a=t[4];this.setPicture(o);this.setTitle(s);this.getList(e,i,a);this.isConstantsTuned(e)}};t.prototype.isConstantsTuned=function(t){BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","isConstantsTuned"),data:{iblockId:t,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){BX("bx-lists-template-id").value=t.templateId;if(t.admin===true){this.setResponsible()}else if(t.admin===false){this.notifyAdmin();BX("bx-lists-cjeck-notify-admin").value=1}}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}},this)})};t.prototype.setPicture=function(t){BX("bx-lists-table-td-title-img").innerHTML=t};t.prototype.setTitle=function(t){BX("bx-lists-table-td-title").innerHTML=BX.util.htmlspecialchars(t);BX("bx-lists-title-notify-admin-popup").value=BX.util.htmlspecialchars(t)};t.prototype.getList=function(t,e,s){var i=BX.findChildrenByClassName(BX("bx-lists-store-lists"),"bx-lists-input-list");for(var o=0;o<i.length;o++){if(i[o].value==t){this.show(BX("bx-lists-div-list-"+i[o].value))}else{this.hide(BX("bx-lists-div-list-"+i[o].value))}}BX("bx-lists-selected-list").value=t;if(BX("bx-lists-input-list-"+t)){return}BX.ajax({url:this.addToLinkParam(this.ajaxUrl,"action","getList"),method:"POST",dataType:"html",data:{iblockId:t,iblockDescription:e,iblockCode:s,socnetGroupId:this.socnetGroupId,randomString:this.randomString,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){BX("bx-lists-store-lists").appendChild(BX.create("input",{props:{id:"bx-lists-input-list-"+t,className:"bx-lists-input-list"},attrs:{type:"hidden",value:t}}));BX("bx-lists-total-div-id").appendChild(BX.create("div",{props:{id:"bx-lists-div-list-"+t,className:"bx-lists-div-list"},attrs:{style:"display: block;"}}));BX.adjust(BX("bx-lists-div-list-"+t),{html:e})},this)});BX.unbindAll(BX("blog-submit-button-save"));BX("blog-submit-button-save").setAttribute("onclick",'BX["LiveFeedClass_'+this.randomString+'"].submitForm();')};t.prototype.removeElement=function(t){return t.parentNode?t.parentNode.removeChild(t):t};t.prototype.addToLinkParam=function(t,e,s){if(!t.length){return"?"+e+"="+s}t=BX.util.remove_url_param(t,e);if(t.indexOf("?")!=-1){return t+"&"+e+"="+s}return t+"?"+e+"="+s};t.prototype.showModalWithStatusAction=function(t,e){t=t||{};if(!t.message){if(t.status=="success"){t.message=BX.message("LISTS_JS_STATUS_ACTION_SUCCESS")}else{t.message=BX.message("LISTS_JS_STATUS_ACTION_ERROR")+". "+this.getFirstErrorFromResponse(t)}}var s=BX.create("div",{props:{className:"bx-lists-alert"},children:[BX.create("span",{props:{className:"bx-lists-aligner"}}),BX.create("span",{props:{className:"bx-lists-alert-text"},text:t.message}),BX.create("div",{props:{className:"bx-lists-alert-footer"}})]});var i=BX.PopupWindowManager.getCurrentPopup();if(i){i.destroy()}var o=setTimeout(function(){var t=BX.PopupWindowManager.getCurrentPopup();if(!t||t.uniquePopupId!="bx-lists-status-action"){return}t.close();t.destroy()},3500);var a=BX.PopupWindowManager.create("bx-lists-status-action",null,{content:s,onPopupClose:function(){this.destroy();clearTimeout(o)},autoHide:true,zIndex:2e3,className:"bx-lists-alert-popup"});a.show();BX("bx-lists-status-action").onmouseover=function(t){clearTimeout(o)};BX("bx-lists-status-action").onmouseout=function(t){o=setTimeout(function(){var t=BX.PopupWindowManager.getCurrentPopup();if(!t||t.uniquePopupId!="bx-lists-status-action"){return}t.close();t.destroy()},3500)}};t.prototype.addNewTableRow=function(t,e,s,i){var o=document.getElementById(t);var a=o.rows.length;var n=o.insertRow(a);for(var r=0;r<e;r++){var l=n.insertCell(r);var d=o.rows[a-1].cells[r].innerHTML;l.innerHTML=d.replace(s,function(t){return t.replace("[n"+arguments[i]+"]","[n"+(1+parseInt(arguments[i]))+"]")})}};t.prototype.getNameInputFile=function(){var t=document.getElementsByClassName("bx-lists-input-file");for(var e=0;e<t.length;e++){var s=t[e].getElementsByTagName("input");for(var i=0;i<s.length;i++){s[i].onchange=getName}}};t.prototype.createAdditionalHtmlEditor=function(t,e,s){var i=document.getElementById(t);var o=i.rows.length;var a=i.insertRow(o);var n=a.insertCell(0);var r=i.rows[o-1].cells[0].innerHTML;var l=0;while(true){var d=r.indexOf("[n",l);if(d<0)break;var p=r.indexOf("]",d);if(p<0)break;var u=parseInt(r.substr(d+2,p-d));r=r.substr(0,d)+"[n"+ ++u+"]"+r.substr(p+1);l=d+1}var l=0;while(true){var d=r.indexOf("__n",l);if(d<0)break;var p=r.indexOf("_",d+2);if(p<0)break;var u=parseInt(r.substr(d+3,p-d));r=r.substr(0,d)+"__n"+ ++u+"_"+r.substr(p+1);l=p+1}n.innerHTML=r;var c="id_"+e+"__n"+o+"_";var m=e+"[n"+o+"][VALUE]";window.BXHtmlEditor.Show({id:c,inputName:m,name:m,content:"",width:"100%",height:"200",allowPhp:false,limitPhpAccess:false,templates:[],templateId:"",templateParams:[],componentFilter:"",snippets:[],placeholder:"Text here...",actionUrl:"/bitrix/tools/html_editor_action.php",cssIframePath:"/bitrix/js/fileman/html_editor/iframe-style.css?1412693817",bodyClass:"",bodyId:"",spellcheck_path:"/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817",usePspell:"N",useCustomSpell:"Y",bbCode:false,askBeforeUnloadPage:false,settingsKey:"user_settings_1",showComponents:true,showSnippets:true,view:"wysiwyg",splitVertical:false,splitRatio:"1",taskbarShown:false,taskbarWidth:"250",lastSpecialchars:false,cleanEmptySpans:true,lazyLoad:false,showTaskbars:false,showNodeNavi:false,controlsMap:[{id:"Bold",compact:true,sort:"80"},{id:"Italic",compact:true,sort:"90"},{id:"Underline",compact:true,sort:"100"},{id:"Strikeout",compact:true,sort:"110"},{id:"RemoveFormat",compact:true,sort:"120"},{id:"Color",compact:true,sort:"130"},{id:"FontSelector",compact:false,sort:"135"},{id:"FontSize",compact:false,sort:"140"},{separator:true,compact:false,sort:"145"},{id:"OrderedList",compact:true,sort:"150"},{id:"UnorderedList",compact:true,sort:"160"},{id:"AlignList",compact:false,sort:"190"},{separator:true,compact:false,sort:"200"},{id:"InsertLink",compact:true,sort:"210",wrap:"bx-htmleditor-"+s},{id:"InsertImage",compact:false,sort:"220"},{id:"InsertVideo",compact:true,sort:"230",wrap:"bx-htmleditor-"+s},{id:"InsertTable",compact:false,sort:"250"},{id:"Code",compact:true,sort:"260"},{id:"Quote",compact:true,sort:"270",wrap:"bx-htmleditor-"+s},{id:"Smile",compact:false,sort:"280"},{separator:true,compact:false,sort:"290"},{id:"Fullscreen",compact:false,sort:"310"},{id:"BbCode",compact:true,sort:"340"},{id:"More",compact:true,sort:"400"}],autoResize:true,autoResizeOffset:"40",minBodyWidth:"350",normalBodyWidth:"555"});var b=BX.findChildrenByClassName(BX(t),"bx-html-editor");for(var h in b){var f=b[h].getAttribute("id");var B=BX.findChildrenByClassName(BX(f),"bx-editor-iframe");if(B.length>1){for(var X=0;X<B.length-1;X++){B[X].parentNode.removeChild(B[X])}}}};t.prototype.createSettingsDropdown=function(t){BX.PreventDefault(t);BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","createSettingsDropdown"),data:{iblockId:BX("bx-lists-selected-list").value,randomString:this.randomString,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){var e=BX.PopupMenu.getMenuById("settings-lists");if(e&&e.popupWindow){if(e.popupWindow.isShown()){BX.PopupMenu.destroy("settings-lists");return}}BX.PopupMenu.show("settings-lists",BX("bx-lists-settings-btn"),t.settingsDropdown,{autoHide:true,offsetTop:0,offsetLeft:0,angle:{offset:15},events:{onPopupClose:function(){}}})}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}},this)})};t.prototype.setDelegateResponsible=function(){if(BX.PopupWindowManager.getCurrentPopup()){BX.PopupWindowManager.getCurrentPopup().close()}var t=this.hide,e=this.addToLinkParam,s=this.showModalWithStatusAction,i=this.ajaxUrl;BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","checkDelegateResponsible"),data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(o){if(o.status=="success"){this.show(BX("feed-add-lists-right"));this.modalWindow({modalId:"bx-lists-popup",title:BX.message("LISTS_SELECT_STAFF_SET_RIGHT"),overlay:false,autoHide:true,contentStyle:{width:"600px",paddingTop:"10px",paddingBottom:"10px"},content:[BX("feed-add-lists-right")],events:{onPopupClose:function(){t(BX("feed-add-lists-right"));BX("bx-lists-total-div-id").appendChild(BX("feed-add-lists-right"));this.destroy()},onAfterPopupShow:function(t){var e=BX.findChild(t.contentContainer,{"class":"bx-lists-popup-title"},true);if(e){e.style.cursor="move";BX.bind(e,"mousedown",BX.proxy(t._startDrag,t))}BX.PopupMenu.destroy("settings-lists")}},buttons:[BX.create("a",{text:BX.message("LISTS_SAVE_BUTTON_SET_RIGHT"),props:{className:"webform-small-button webform-small-button-accept"},events:{click:BX.delegate(function(t){var o=BX.findChildrenByClassName(BX("feed-add-post-lists-item"),"feed-add-post-lists"),a=[];for(var n=0;n<o.length;n++){a.push(o[n].getAttribute("data-id"))}BX.ajax({method:"POST",dataType:"json",url:e(i,"action","setDelegateResponsible"),data:{iblockId:BX("bx-lists-selected-list").value,selectUsers:a,sessid:BX.bitrix_sessid()},onsuccess:function(t){if(t.status=="success"){BX.PopupWindowManager.getCurrentPopup().close();s({status:"success",message:t.message})}else{BX.PopupWindowManager.getCurrentPopup().close();t.errors=t.errors||[{}];s({status:"error",message:t.errors.pop().message})}}})},this)}}),BX.create("a",{text:BX.message("LISTS_CANCEL_BUTTON_SET_RIGHT"),props:{className:"webform-small-button webform-button-cancel"},events:{click:BX.delegate(function(t){BX.PopupWindowManager.getCurrentPopup().close()},this)}})]});for(var a in o.listUser){var n=BX.findChildrenByClassName(BX("feed-add-post-lists-item"),"feed-add-post-lists");for(var r in n){if(o.listUser[a].id==n[r].getAttribute("data-id")){delete o.listUser[a]}}BXfpListsSelectCallback(o.listUser[a])}}else{o.errors=o.errors||[{}];this.showModalWithStatusAction({status:"error",message:o.errors.pop().message})}},this)})};t.prototype.jumpSettingProcess=function(){BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","checkPermissions"),data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){document.location.href=BX("bx-lists-lists-page").value+BX("bx-lists-selected-list").value+"/edit/"}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}},this)})};t.prototype.jumpProcessDesigner=function(){BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","getBizprocTemplateId"),data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){document.location.href=BX("bx-lists-lists-page").value+BX("bx-lists-selected-list").value+"/bp_edit/"+t.templateId+"/"}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}},this)})};t.prototype.notify=function(t){BX("bx-lists-notify-button-"+t).setAttribute("onclick","");var e="/",s=null;if(BX("bx-lists-select-site-dir")){e=BX("bx-lists-select-site-dir").value}if(BX("bx-lists-select-site-id")){s=BX("bx-lists-select-site-id").value}BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","notifyAdmin"),data:{iblockId:BX("bx-lists-selected-list").value,iblockName:BX("bx-lists-title-notify-admin-popup").value,userId:t,siteDir:e,siteId:s,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.status=="success"){this.removeElement(BX("bx-lists-notify-button-"+t));BX("bx-lists-notify-success-"+t).innerHTML=e.message}else{BX("bx-lists-notify-button-"+t).setAttribute("onclick",'BX["LiveFeedClass_'+this.randomString+'"].notify('+t+")");e.errors=e.errors||[{}];this.showModalWithStatusAction({status:"error",message:e.errors.pop().message})}},this)})};t.prototype.notifyAdmin=function(){BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","getListAdmin"),data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){var e='<span class="bp-question"><span>!</span>'+BX.message("LISTS_NOTIFY_ADMIN_TITLE_WHY").replace("#NAME_PROCESSES#",BX("bx-lists-title-notify-admin-popup").value)+"</span>";e+="<p>"+BX.message("LISTS_NOTIFY_ADMIN_TEXT_ONE").replace("#NAME_PROCESSES#",BX("bx-lists-title-notify-admin-popup").value)+"</p>";e+="<p>"+BX.message("LISTS_NOTIFY_ADMIN_TEXT_TWO").replace("#NAME_PROCESSES#",BX("bx-lists-title-notify-admin-popup").value)+"</p>";e+='<span class="bp-question-title">'+BX.message("LISTS_NOTIFY_ADMIN_MESSAGE")+"</span>";for(var s in t.listAdmin){var i="";if(t.listAdmin[s].img){i='<img src="'+t.listAdmin[s].img+'" alt="">'}e+='<div class="bp-question-item"><a href="#" class="bp-question-item-avatar"><span class="bp-question-item-avatar-inner">'+i+'</span></a><span class="bp-question-item-info"><span>'+t.listAdmin[s].name+"</span></span>"+'<span id="bx-lists-notify-success-'+t.listAdmin[s].id+'" class="bx-lists-notify-success"></span>'+'<a id="bx-lists-notify-button-'+t.listAdmin[s].id+'" href="#" onclick=\'BX["LiveFeedClass_'+this.randomString+'"].notify('+t.listAdmin[s].id+');\' class="webform-small-button bp-small-button webform-small-button-blue">'+""+BX.message("LISTS_NOTIFY_ADMIN_MESSAGE_BUTTON")+"</a></div>"}BX("bx-lists-notify-admin-popup-content").innerHTML=e;this.modalWindow({modalId:"bx-lists-popup",title:BX("bx-lists-title-notify-admin-popup").value,overlay:false,contentStyle:{width:"600px",paddingTop:"10px",paddingBottom:"10px"},content:[BX("bx-lists-notify-admin-popup-content")],events:{onPopupClose:function(){BX("bx-lists-notify-admin-popup").appendChild(BX("bx-lists-notify-admin-popup-content"));this.destroy()},onAfterPopupShow:function(t){var e=BX.findChild(t.contentContainer,{"class":"bx-lists-popup-title"},true);if(e){e.style.cursor="move";BX.bind(e,"mousedown",BX.proxy(t._startDrag,t))}BX.PopupMenu.destroy("settings-lists")}},buttons:[BX.create("a",{text:BX.message("LISTS_CANCEL_BUTTON_CLOSE"),props:{className:"webform-small-button webform-button-cancel"},events:{click:BX.delegate(function(t){BX.PopupWindowManager.getCurrentPopup().close()},this)}})]})}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}},this)})};t.prototype.setResponsible=function(){if(BX.PopupWindowManager.getCurrentPopup()){BX.PopupWindowManager.getCurrentPopup().close()}BX.ajax({method:"POST",dataType:"json",url:this.addToLinkParam(this.ajaxUrl,"action","checkPermissions"),data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){if(t.status=="success"){BX.ajax({url:this.addToLinkParam(this.ajaxUrl,"action","setResponsible"),method:"POST",dataType:"html",data:{iblockId:BX("bx-lists-selected-list").value,sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){BX.adjust(BX("bx-lists-set-responsible-content"),{html:t})},this)});this.modalWindow({modalId:"bx-lists-popup",title:BX.message("LISTS_SELECT_STAFF_SET_RESPONSIBLE"),overlay:false,contentStyle:{width:"600px",paddingTop:"10px",paddingBottom:"10px"},content:[BX("bx-lists-set-responsible-content")],events:{onPopupClose:function(){BX("bx-lists-set-responsible").appendChild(BX("bx-lists-set-responsible-content"));this.destroy()},onAfterPopupShow:function(t){var e=BX.findChild(t.contentContainer,{"class":"bx-lists-popup-title"},true);if(e){e.style.cursor="move";BX.bind(e,"mousedown",BX.proxy(t._startDrag,t))}BX.PopupMenu.destroy("settings-lists")}},buttons:[BX.create("a",{text:BX.message("LISTS_SAVE_BUTTON_SET_RIGHT"),props:{className:"webform-small-button webform-small-button-accept"},events:{click:BX.delegate(function(t){var e=BX.findChild(BX("bx-lists-set-responsible-content"),{tag:"FORM"},true);if(e)e.onsubmit(e,t)})}}),BX.create("a",{text:BX.message("LISTS_CANCEL_BUTTON_SET_RIGHT"),props:{className:"webform-small-button webform-button-cancel"},events:{click:BX.delegate(function(t){BX.PopupWindowManager.getCurrentPopup().close()},this)}})]})}else{if(BX("bx-lists-cjeck-notify-admin").value){this.notifyAdmin()}else{t.errors=t.errors||[{}];this.showModalWithStatusAction({status:"error",message:t.errors.pop().message})}}},this)})};t.prototype.show=function(t){if(this.getRealDisplay(t)!="none")return;var e=t.getAttribute("displayOld");t.style.display=e||"";if(this.getRealDisplay(t)==="none"){var s=t.nodeName,i=document.body,o;if(displayCache[s]){o=displayCache[s]}else{var a=document.createElement(s);i.appendChild(a);o=this.getRealDisplay(a);if(o==="none"){o="block"}i.removeChild(a);displayCache[s]=o}t.setAttribute("displayOld",o);t.style.display=o}};t.prototype.hide=function(t){if(!t.getAttribute("displayOld")){t.setAttribute("displayOld",t.style.display)}t.style.display="none"};t.prototype.getRealDisplay=function(t){if(t.currentStyle){return t.currentStyle.display}else if(window.getComputedStyle){var e=window.getComputedStyle(t,null);return e.getPropertyValue("display")}};t.prototype.modalWindow=function(t){t=t||{};t.title=t.title||false;t.bindElement=t.bindElement||null;t.overlay=typeof t.overlay=="undefined"?true:t.overlay;t.autoHide=t.autoHide||false;t.closeIcon=typeof t.closeIcon=="undefined"?{right:"20px",top:"10px"}:t.closeIcon;t.modalId=t.modalId||"lists_modal_window_"+(Math.random()*(2e5-100)+100);t.withoutContentWrap=typeof t.withoutContentWrap=="undefined"?false:t.withoutContentWrap;t.contentClassName=t.contentClassName||"";t.contentStyle=t.contentStyle||{};t.content=t.content||[];t.buttons=t.buttons||false;t.events=t.events||{};t.withoutWindowManager=!!t.withoutWindowManager||false;var e=[];if(t.title){e.push(BX.create("div",{props:{className:"bx-lists-popup-title"},text:t.title}))}if(t.withoutContentWrap){e=e.concat(t.content)}else{e.push(BX.create("div",{props:{className:"bx-lists-popup-content "+t.contentClassName},style:t.contentStyle,children:t.content}))}var s=[];if(t.buttons){for(var i in t.buttons){if(!t.buttons.hasOwnProperty(i)){continue}if(i>0){s.push(BX.create("SPAN",{html:"&nbsp;"}))}s.push(t.buttons[i])}e.push(BX.create("div",{props:{className:"bx-lists-popup-buttons"},children:s}))}var o=BX.create("div",{props:{className:"bx-lists-popup-container"},children:e});t.events.onPopupShow=BX.delegate(function(){if(s.length){firstButtonInModalWindow=s[0];BX.bind(document,"keydown",BX.proxy(this._keyPress,this))}if(t.events.onPopupShow)BX.delegate(t.events.onPopupShow,BX.proxy_context)},this);var a=t.events.onPopupClose;t.events.onPopupClose=BX.delegate(function(){firstButtonInModalWindow=null;try{BX.unbind(document,"keydown",BX.proxy(this._keypress,this))}catch(e){}if(a){BX.delegate(a,BX.proxy_context)()}if(t.withoutWindowManager){delete windowsWithoutManager[t.modalId]}BX.proxy_context.destroy()},this);var n;if(t.withoutWindowManager){if(!!windowsWithoutManager[t.modalId]){return windowsWithoutManager[t.modalId]}n=new BX.PopupWindow(t.modalId,t.bindElement,{content:o,closeByEsc:true,closeIcon:t.closeIcon,autoHide:t.autoHide,overlay:t.overlay,events:t.events,buttons:[],zIndex:isNaN(t["zIndex"])?0:t.zIndex});windowsWithoutManager[t.modalId]=n}else{n=BX.PopupWindowManager.create(t.modalId,t.bindElement,{content:o,closeByEsc:true,closeIcon:t.closeIcon,autoHide:t.autoHide,overlay:t.overlay,events:t.events,buttons:[],zIndex:isNaN(t["zIndex"])?0:t.zIndex})}n.show();return n};t.prototype.submitForm=function(){if(this.getRealDisplay(BX("feed-add-post-content-lists"))=="none")BX.bind(BX("blog-submit-button-save"),"click",submitBlogPostForm());BX("blog-submit-button-save").setAttribute("onclick","");BX.addClass(BX("blog-submit-button-save"),"feed-add-button-load");var t=BX.findChildrenByClassName(BX("bx-lists-store-lists"),"bx-lists-input-list");for(var e=0;e<t.length;e++){if(t[e].value!=BX("bx-lists-selected-list").value){this.removeElement(BX("bx-lists-div-list-"+t[e].value));this.removeElement(BX("bx-lists-input-list-"+t[e].value))}}BX.ajax.submitAjax(BX("blogPostForm"),{method:"POST",url:this.addToLinkParam(this.ajaxUrl,"action","checkDataElementCreation"),processData:true,onsuccess:BX.delegate(function(t){t=BX.parseJSON(t,{});if(t.status=="success"){BX.bind(BX("blog-submit-button-save"),"click",submitBlogPostForm())}else{BX.removeClass(BX("blog-submit-button-save"),"feed-add-button-load");BX("bx-lists-block-errors").innerHTML=t.errors.pop().message;this.show(BX("bx-lists-block-errors"));BX("blog-submit-button-save").setAttribute("onclick",'BX["LiveFeedClass_'+this.randomString+'"].submitForm();')}},this)})};t.prototype.errorPopup=function(t){this.showModalWithStatusAction({status:"error",message:t})};return t}();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:88:"/bitrix/components/bitrix/lists.live.feed/templates/.default/right.min.js?14522774593499";s:6:"source";s:69:"/bitrix/components/bitrix/lists.live.feed/templates/.default/right.js";s:3:"min";s:73:"/bitrix/components/bitrix/lists.live.feed/templates/.default/right.min.js";s:3:"map";s:73:"/bitrix/components/bitrix/lists.live.feed/templates/.default/right.map.js";}"*/
(function(){window.BXfpListsSelectCallback=function(e){BXfpListsMedalSelectCallback(e,"lists")};window.BXfpListsMedalLinkName=function(e,t){if(t!="lists")t="medal";if(BX.SocNetLogDestination.getSelectedCount(e)<=0)BX("bx-"+t+"-tag").innerHTML=BX.message("LISTS_ADD_STAFF");else BX("bx-"+t+"-tag").innerHTML=BX.message("LISTS_ADD_STAFF_MORE")};window.BXfpListsMedalSelectCallback=function(e,t){if(t!="lists")t="medal";var s="U";BX("feed-add-post-"+t+"-item").appendChild(BX.create("span",{attrs:{"data-id":e.id},props:{className:"feed-add-post-"+t+" feed-add-post-destination-users"},children:[BX.create("input",{attrs:{type:"hidden",name:"LISTS"+"["+s+"][]",value:e.id}}),BX.create("span",{props:{className:"feed-add-post-"+t+"-text"},html:e.name}),BX.create("span",{props:{className:"feed-add-post-del-but"},events:{click:function(t){BX.SocNetLogDestination.deleteItem(e.id,"users",window["BXSocNetLogListsFormName"]);BXfpListsUnSelectCallback(e);BX.PreventDefault(t)},mouseover:function(){BX.addClass(this.parentNode,"feed-add-post-"+t+"-hover")},mouseout:function(){BX.removeClass(this.parentNode,"feed-add-post-"+t+"-hover")}}})]}));BX("feed-add-post-"+t+"-input").value="";BXfpListsMedalLinkName(window["BXSocNetLogListsFormName"],t)};window.BXfpListsUnSelectCallback=function(e){BXfpListsMedalUnSelectCallback(e,"lists")};window.BXfpListsMedalUnSelectCallback=function(e,t){var s=BX.findChildren(BX("feed-add-post-"+t+"-item"),{attribute:{"data-id":""+e.id+""}},true);if(s!=null){for(var i=0;i<s.length;i++)BX.remove(s[i])}BX("feed-add-post-"+t+"-input").value="";BXfpListsMedalLinkName(window["BXSocNetLogListsFormName"],t)};window.BXfpListsOpenDialogCallback=function(){BX.style(BX("feed-add-post-lists-input-box"),"display","inline-block");BX.style(BX("bx-lists-tag"),"display","none");BX.focus(BX("feed-add-post-lists-input"))};window.BXfpListsCloseDialogCallback=function(){if(!BX.SocNetLogDestination.isOpenSearch()&&BX("feed-add-post-lists-input").value.length<=0){BX.style(BX("feed-add-post-lists-input-box"),"display","none");BX.style(BX("bx-lists-tag"),"display","inline-block");BXfpdDisableBackspace()}};window.BXfpListsCloseSearchCallback=function(){if(!BX.SocNetLogDestination.isOpenSearch()&&BX("feed-add-post-lists-input").value.length>0){BX.style(BX("feed-add-post-lists-input-box"),"display","none");BX.style(BX("bx-lists-tag"),"display","inline-block");BX("feed-add-post-lists-input").value="";BXfpdDisableBackspace()}};window.BXfpListsSearch=function(e){if(e.keyCode==16||e.keyCode==17||e.keyCode==18)return false;if(e.keyCode==13){BX.SocNetLogDestination.selectFirstSearchItem(window["BXSocNetLogListsFormName"]);return true}if(e.keyCode==27){BX("feed-add-post-lists-input").value="";BX.style(BX("bx-lists-tag"),"display","inline")}else{BX.SocNetLogDestination.search(BX("feed-add-post-lists-input").value,true,window["BXSocNetLogListsFormName"])}if(!BX.SocNetLogDestination.isOpenDialog()&&BX("feed-add-post-lists-input").value.length<=0){BX.SocNetLogDestination.openDialog(window["BXSocNetLogListsFormName"])}else{if(BX.SocNetLogDestination.sendEvent&&BX.SocNetLogDestination.isOpenDialog())BX.SocNetLogDestination.closeDialog()}if(e.keyCode==8){BX.SocNetLogDestination.sendEvent=true}return true};window.BXfpListsSearchBefore=function(e){if(e.keyCode==8&&BX("feed-add-post-lists-input").value.length<=0){BX.SocNetLogDestination.sendEvent=false;BX.SocNetLogDestination.deleteLastItem(window["BXSocNetLogListsFormName"])}return true}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:103:"/bitrix/templates/bitrix24/components/bitrix/socialnetwork.log.filter/.default/script.js?14522775327951";s:6:"source";s:88:"/bitrix/templates/bitrix24/components/bitrix/socialnetwork.log.filter/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
__logOnDateChange = function(sel)
{
	var bShowFrom=false, bShowTo=false, bShowHellip=false, bShowDays=false, bShowBr=false;

	if(sel.value == 'interval')
		bShowBr = bShowFrom = bShowTo = bShowHellip = true;
	else if(sel.value == 'before')
		bShowTo = true;
	else if(sel.value == 'after' || sel.value == 'exact')
		bShowFrom = true;
	else if(sel.value == 'days')
		bShowDays = true;

	BX('flt_date_from_span').style.display = (bShowFrom? '':'none');
	BX('flt_date_to_span').style.display = (bShowTo? '':'none');
	BX('flt_date_hellip_span').style.display = (bShowHellip? '':'none');
	BX('flt_date_day_span').style.display = (bShowDays? 'inline':'none');
}

function onFilterGroupSelect(arGroups)
{
	if (arGroups[0])
	{
		BX('filter-field-user').value = '';
		document.forms["log_filter"]["flt_to_user_id"].value = 0;
		document.forms["log_filter"]["flt_group_id"].value = arGroups[0].id;
		BX.removeClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
	}
}

function onFilterCreatedBySelect(arUser)
{
	if (arUser.id)
	{
		document.forms["log_filter"]["flt_created_by_id"].value = arUser.id;
		document.forms["log_filter"]["filter-field-created-by"].value = arUser.name;
		BX.removeClass(BX("filter-field-created-by").parentNode.parentNode, "webform-field-textbox-empty");
		if (BX("flt_comments_cont"))
		{
			BX("flt_comments_cont").style.display = "block";
		}
	}
	else if (BX("flt_comments_cont"))
	{
		BX("flt_comments_cont").style.display = "none";
	}

	filterCreatedByPopup.close();
}

function onFilterUserSelect(arUser)
{
	if (arUser.id)
	{
		BX('filter-field-group').value = '';
		document.forms["log_filter"]["flt_group_id"].value = 0;
		document.forms["log_filter"]["flt_to_user_id"].value = arUser.id;
		document.forms["log_filter"]["filter-field-user"].value = arUser.name;
		BX.removeClass(BX("filter-field-user").parentNode.parentNode, "webform-field-textbox-empty");
	}

	filterUserPopup.close();
}

function onFilterDestChangeTab(type)
{
	var type_hide;
	if (type != 'group')
	{
		type = 'user';
		type_hide = 'group';
		if (
			filterGroupsPopup !== undefined 
			&& filterGroupsPopup.popupWindow !== undefined
		)
		{
			filterGroupsPopup.popupWindow.close();
		}
	}
	else
	{
		type_hide = 'user';
		if (
			filterUserPopup !== undefined
		)
		{
			filterUserPopup.close();
		}
	}

	BX.removeClass(BX('filter-dest-' + type + '-tab'), 'webform-field-action-link');
	BX.addClass(BX('filter-dest-' + type_hide + '-tab'), 'webform-field-action-link');

	BX('filter-dest-' + type + '-block').style.display = 'inline-block';
	BX('filter-dest-' + type_hide + '-block').style.display = 'none';

	if (type != 'group')
	{
		BX("filter-field-user").focus();
		__SLFShowUseropup(BX("filter-field-user"));
	}
	else
	{
		BX("filter-field-group").focus();
		__SLFShowGroupsPopup();
	}
}

var filterPopup = false;

function ShowFilterPopup(bindElement)
{
	if (!filterPopup)
	{
		//BX.showWait(bindElement);
		BX.ajax.get(BX.message('sonetLFAjaxPath'), function(data) 
		{
			BX.closeWait(bindElement);

			filterPopup = new BX.PopupWindow(
				'bx_log_filter_popup',
				bindElement,
				{
					closeIcon : false,
					offsetTop: 5,
					autoHide: true,
					zIndex : -100,
					//angle : { offset : 59},
					className : 'sonet-log-filter-popup-window',
					events : {
						onPopupClose: function() {
							if (!BX.hasClass(this.bindElement, "pagetitle-menu-filter-set"))
								BX.removeClass(this.bindElement, "pagetitle-menu-filter-selected")
						},
						onPopupShow: function() { BX.addClass(this.bindElement, "pagetitle-menu-filter-selected")}
					}
				}
			);

			var filter_block = BX.create('DIV', {html: data});
			filterPopup.setContent(filter_block.firstChild);
			filterPopup.show();
			
			BX.bind(BX("filter-field-created-by"), "click", function(e) {
				if(!e) e = window.event;

				__SLFShowCreatedByPopup(this);
				return BX.PreventDefault(e);
			});

			BX.bind(BX.findNextSibling(BX("filter-field-created-by"), {tagName : "a"}), "click", function(e){
				if(!e) e = window.event;

				BX("filter-field-created-by").value = "";
				BX("filter_field_createdby_hidden").value = "0";
				BX.addClass(BX("filter-field-created-by").parentNode.parentNode, "webform-field-textbox-empty");
				if (BX("flt_comments_cont"))
				{
					BX("flt_comments_cont").style.display = "none";
				}
				return BX.PreventDefault(e);
			});

			if (BX("filter-field-group"))
			{
				BX.bind(BX("filter-field-group"), "click", function(e) {
					if(!e) e = window.event;

					__SLFShowGroupsPopup();
					return BX.PreventDefault(e);
				});

				BX.bind(BX.findNextSibling(BX("filter-field-group"), {tagName : "a"}), "click", function(e){
					if(!e) e = window.event;

					filterGroupsPopup.deselect(BX("filter_field_group_hidden").value.value);
					BX("filter_field_group_hidden").value = "0";
					BX.addClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
					return BX.PreventDefault(e);
				});
			}

			if (BX("filter-field-user"))
			{
				BX.bind(BX("filter-field-user"), "click", function(e) {
					if(!e) e = window.event;

					__SLFShowUseropup(this);
					return BX.PreventDefault(e);
				});

				BX.bind(BX.findNextSibling(BX("filter-field-user"), {tagName : "a"}), "click", function(e){
					if(!e) e = window.event;
					BX("filter-field-user").value = "";
					BX("filter_field_user_hidden").value = "0";
					BX.addClass(BX("filter-field-user").parentNode.parentNode, "webform-field-textbox-empty");
					return BX.PreventDefault(e);
				});
			}
		});
	}
	else
	{
		filterPopup.show();	
	}

}

function __SLFShowCreatedByPopup(obj)
{
	filterCreatedByPopup = BX.PopupWindowManager.create("filter-created-by-popup", obj.parentNode, {
		offsetTop : 1,
		autoHide : true,
		content : BX("FILTER_CREATEDBY_selector_content"),
		zIndex : 1200,
		buttons : [
			new BX.PopupWindowButton({
				text : BX.message("sonetLDialogClose"),
				className : "popup-window-button-accept",
				events : {
					click : function() {
						this.popupWindow.close();
					}
				}
			})
		]
	});

	if (filterCreatedByPopup.popupContainer.style.display != "block")
	{
		filterCreatedByPopup.show();
	}
}

function __SLFShowGroupsPopup()
{
	BX('filter-field-user').value = '';
	BX('filter_field_user_hidden').value = "0";

	filterGroupsPopup.show();
}

function __SLFShowUseropup(obj)
{
	filterUserPopup = BX.PopupWindowManager.create("filter-user-popup", obj.parentNode, {
		offsetTop : 1,
		autoHide : true,
		content : BX("FILTER_USER_selector_content"),
		zIndex : 1200,
		buttons : [
			new BX.PopupWindowButton({
				text : BX.message("sonetLDialogClose"),
				className : "popup-window-button-accept",
				events : {
					click : function() {
						this.popupWindow.close();
					}
				}
			})
		]
	});

	if (filterUserPopup.popupContainer.style.display != "block")
	{
		filterUserPopup.show();
	}
}

function __logOnReload(log_counter)
{
	if (BX("menu-popup-lenta-sort-popup"))
	{
		var arMenuItems = BX.findChildren(BX("menu-popup-lenta-sort-popup"), { className: 'lenta-sort-item' }, true);
		
		if (!BX.hasClass(arMenuItems[0], 'lenta-sort-item-selected'))
		{
			for (var i = 0; i < arMenuItems.length; i++)
			{
				if (i == 0)
					BX.addClass(arMenuItems[i], 'lenta-sort-item-selected');
				else if (i != (arMenuItems.length-1))
					BX.removeClass(arMenuItems[i], 'lenta-sort-item-selected');
			}
		}
	}

	if (BX("lenta-sort-button"))
	{
		var menuButtonText = BX.findChild(BX("lenta-sort-button"), { className: 'lenta-sort-button-text-internal' }, true, false);
		if (menuButtonText)
			menuButtonText.innerHTML = BX.message('sonetLFAllMessages');
	}

	var counter_cont = BX("sonet_log_counter_preset", true);
	if (counter_cont)
	{
		if (parseInt(log_counter) > 0)
		{
			counter_cont.style.display = "inline-block";
			counter_cont.innerHTML = log_counter;
		}
		else
		{
			counter_cont.innerHTML = '';
			counter_cont.style.display = "none";
		}
	}
}

/* End */
;
; /* Start:"a:4:{s:4:"full";s:96:"/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js?145227745720460";s:6:"source";s:80:"/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(){

if(window.IntranetUsers)
	return;
	
window.IntranetUsers = function(name, multiple, bSubordinateOnly) {
	this.name = name;
	this.multiple = multiple;
	this.arSelected = [];
	this.arFixed = [];
	this.bSubordinateOnly = bSubordinateOnly;
	this.ajaxUrl = '';
	this.lastSearchTime = 0;
}

IntranetUsers.arStructure = {};
IntranetUsers.bSectionsOnly = false;
IntranetUsers.arEmployees = { 'group' : {} };
IntranetUsers.arEmployeesData = {};
IntranetUsers.ajaxUrl = '';

IntranetUsers.prototype.loadGroup = function(groupId)
{
	var obSection = BX(this.name + '_group_section_' + groupId);
	function __onLoadEmployees(data)
	{
		IntranetUsers.arEmployees['group'][groupId] = data;
		this.show(groupId, data, 'g');
	}

	groupId = parseInt(groupId);
	if (IntranetUsers.arEmployees['group'][groupId] != null)
	{
		this.show(groupId, IntranetUsers.arEmployees['group'][groupId], 'g');
	}
	else
	{
		var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&GROUP_ID=' + groupId;
		BX.ajax.loadJSON(url, BX.proxy(__onLoadEmployees, this));
	}

	BX.toggleClass(obSection, "company-department-opened");
	BX.toggleClass(BX(this.name + '_gchildren_' + groupId), "company-department-children-opened");
}

IntranetUsers.prototype.load = function(sectionID, bShowOnly, bScrollToSection, bSectionsOnly)
{
	this.bSectionsOnly = bSectionsOnly;

	function __onLoadEmployees(data)
	{
		IntranetUsers.arStructure[sectionID] = data.STRUCTURE;
		IntranetUsers.arEmployees[sectionID] = data.USERS;
		this.show(sectionID, false, '', this.bSectionsOnly);
	}

	if (null == bShowOnly) bShowOnly = false;
	if (null == bScrollToSection) bScrollToSection = false;
	if (null == bSectionsOnly) bSectionsOnly = false;

	if (sectionID != 'extranet') sectionID = parseInt(sectionID);

	var obSection = BX(this.name + '_employee_section_' + sectionID);
	if (!obSection.BX_LOADED)
	{
		if (IntranetUsers.arEmployees[sectionID] != null)
		{
			this.show(sectionID);
		}
		else
		{
			var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&SECTION_ID=' + sectionID;
			BX.ajax.loadJSON(url,  BX.proxy(__onLoadEmployees, this));
		}
	}

	if (bScrollToSection)
	{
		BX(this.name + '_employee_search_layout').scrollTop = obSection.offsetTop - 40;
	}

	BX.toggleClass(obSection, "company-department-opened");
	BX.toggleClass(BX(this.name + '_children_' + sectionID), "company-department-children-opened");
}

IntranetUsers.prototype.show = function (sectionID, usersData, sectionPrefixName, bSelectSection)
{
	bSelectSection = !!bSelectSection;
	sectionPrefixName = sectionPrefixName || '';
	var obSection = BX(this.name + '_' + sectionPrefixName + 'employee_section_' + sectionID);
	var arEmployees = usersData || IntranetUsers.arEmployees[sectionID];

	if(obSection !== null)
	{
		obSection.BX_LOADED = true;
	}

	var obSectionDiv = BX(this.name + '_' + sectionPrefixName + 'employees_' + sectionID);
	if (obSectionDiv)
	{
		if (IntranetUsers.arStructure[sectionID] != null && !sectionPrefixName)
		{
			var arStructure = IntranetUsers.arStructure[sectionID];

			var obSectionCh = BX(this.name + '_' + sectionPrefixName + 'children_' + sectionID);
			if (obSectionCh)
			{
				for (var i = 0; i < arStructure.length; i++)
				{
					obSectionRow1 = BX.create('div', {
						props: {className: 'company-department'},
						children: [
							(bSelectSection
								? BX.create('span', {
									props: {
										className: 'company-department-inner',
										id: this.name+'_employee_section_'+arStructure[i].ID
									},
									children: [
										BX.create('div', {
											props: {className: 'company-department-arrow'},
											attrs: {
												onclick: 'O_'+this.name+'.load('+arStructure[i].ID+', false, false, true)'
											}
										}),
										BX.create('div', {
											props: {className: 'company-department-text'},
											attrs: {
												'data-section-id' : arStructure[i].ID,
												onclick: 'O_'+this.name+'.selectSection('+this.name+'_employee_section_'+arStructure[i].ID+')'
											},
											text: arStructure[i].NAME
										})
									]
								})
								: BX.create('span', {
									props: {
										className: 'company-department-inner',
										id: this.name+'_employee_section_'+arStructure[i].ID
									},
									attrs: {
										onclick: 'O_'+this.name+'.load('+arStructure[i].ID+')'
									},
									children: [
										BX.create('div', {props: {className: 'company-department-arrow'}}),
										BX.create('div', {
											props: {className: 'company-department-text'},
											text: arStructure[i].NAME
										})
									]
								})
							)
						]
					});

					obSectionRow2 = BX.create('div', {
						props: {
							className: 'company-department-children',
							id: this.name+'_children_'+arStructure[i].ID
						},
						children: [
							BX.create('div', {
								props: {
									className: 'company-department-employees',
									id: this.name+'_employees_'+arStructure[i].ID
								},
								children: [
									BX.create('span', {
										props: {className: 'company-department-employees-loading'},
										text: BX.message('INTRANET_EMP_WAIT')
									})
								]
							})
						]
					});

					obSectionCh.appendChild(obSectionRow1);
					obSectionCh.appendChild(obSectionRow2);
				}

				obSectionCh.appendChild(obSectionDiv);
			}
		}

		obSectionDiv.innerHTML = '';

		for (var i = 0; i < arEmployees.length; i++)
		{

			var obUserRow;
			var bSelected = false;

			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			}

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}

			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}

			obInput.value = arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "company-department-employee" + (bSelected ? " company-department-employee-selected" : "")
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "company-department-employee-avatar"
						},
						style : {
							background : arEmployees[i].PHOTO ? "url('" + arEmployees[i].PHOTO + "') no-repeat center center" : ""
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-icon"
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-info"
						},
						children : [
							BX.create("div", {
								props : {
									className : "company-department-employee-name"
								},
								text : arEmployees[i].NAME
							}),
							BX.create("div", {
								props : {
									className : "company-department-employee-position"
								},
								html : !arEmployees[i].HEAD && !arEmployees[i].WORK_POSITION ? "&nbsp;" : (BX.util.htmlspecialchars(arEmployees[i].WORK_POSITION) + (arEmployees[i].HEAD && arEmployees[i].WORK_POSITION ? ', ' : '') + (arEmployees[i].HEAD ? BX.message('INTRANET_EMP_HEAD') : ''))
							})
						]
					})
				]
			})

			obSectionDiv.appendChild(obUserRow);
		}
	}
}

IntranetUsers.prototype.select = function(e)
{
	var obCurrentTarget;
	var i = 0;

	var target = e.target || e.srcElement;

	if (e.currentTarget)
	{
		obCurrentTarget = e.currentTarget;
	}
	else // because IE does not support currentTarget
	{
		obCurrentTarget = target;

		while(!BX.hasClass(obCurrentTarget, "finder-box-item") && !BX.hasClass(obCurrentTarget, "company-department-employee"))
		{
			obCurrentTarget = obCurrentTarget.parentNode;
		}
	}

	var obInput = BX.findChild(obCurrentTarget, {tag: "input"});

	if (!this.multiple)
	{
		var arInputs = document.getElementsByName(this.name);
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value != obInput.value)
			{
				BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
			else
			{
				BX.addClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
		}
		obInput.checked = true;
		BX.addClass(obCurrentTarget, BX.hasClass(obCurrentTarget, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");

		this.searchInput.value = IntranetUsers.arEmployeesData[obInput.value].name;

		this.arSelected = [];
		this.arSelected[obInput.value] = {
			id : obInput.value,
			name : IntranetUsers.arEmployeesData[obInput.value].name,
			sub : IntranetUsers.arEmployeesData[obInput.value].sub,
			sup : IntranetUsers.arEmployeesData[obInput.value].sup,
			position : IntranetUsers.arEmployeesData[obInput.value].position,
			photo : IntranetUsers.arEmployeesData[obInput.value].photo
		};
	}
	else
	{
		var arInputs = document.getElementsByName(this.name + "[]");
		if (!BX.util.in_array(obInput, arInputs) && !BX.util.in_array(obInput.value, this.arFixed)) { // IE7
			obInput.checked = false;
			BX.toggleClass(obInput.parentNode, BX.hasClass(obInput.parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
		}
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value == obInput.value && !BX.util.in_array(obInput.value, this.arFixed))
			{
				arInputs[i].checked = false;
				BX.toggleClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}

		if (BX.hasClass(obInput.parentNode, "finder-box-item-selected") || BX.hasClass(obInput.parentNode, "company-department-employee-selected"))
		{
			obInput.checked = true;
		}

		if (obInput.checked)
		{
			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});

			if (!BX(this.name + "_employee_selected_" + obInput.value))
			{
				var obUserRow = BX.create('DIV');
				obUserRow.id = this.name + '_employee_selected_' + obInput.value;
				obUserRow.className = 'finder-box-selected-item';

				var obNameDiv = BX.findChild(obCurrentTarget, {tag: "DIV", className: "finder-box-item-text"}, true) || BX.findChild(obCurrentTarget, {tag: "DIV", className: "company-department-employee-name"}, true);

				obUserRow.innerHTML =  "<div class=\"finder-box-selected-item-icon\" id=\"user-selector-unselect-" + obInput.value + "\" onclick=\"O_" + this.name + ".unselect(" + obInput.value + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + obNameDiv.innerHTML + "</span>";
				obSelected.appendChild(obUserRow);

				var countSpan = BX(this.name + "_current_count");
				countSpan.innerHTML = parseInt(countSpan.innerHTML) + 1;

				this.arSelected[obInput.value] = {
					id : obInput.value,
					name : IntranetUsers.arEmployeesData[obInput.value].name,
					sub : IntranetUsers.arEmployeesData[obInput.value].sub,
					sup : IntranetUsers.arEmployeesData[obInput.value].sup,
					position : IntranetUsers.arEmployeesData[obInput.value].position,
					photo : IntranetUsers.arEmployeesData[obInput.value].photo
				};
			}
		}
		else
		{
			BX.remove(BX(this.name + '_employee_selected_' + obInput.value));

			var countSpan = BX(this.name + "_current_count");
			countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;

			this.arSelected[obInput.value] = null;
		}
	}

	if (!BX.util.in_array(obInput.value, IntranetUsers.lastUsers))
	{
		IntranetUsers.lastUsers.unshift(obInput.value);
		BX.userOptions.save('intranet', 'user_search', 'last_selected', IntranetUsers.lastUsers.slice(0, 10));
	}

	if (this.onSelect)
	{
		var emp = this.arSelected.pop();
		this.arSelected.push(emp);
		this.onSelect(emp);
	}

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.selectSection = function(block_id)
{
	var obSectionBlock = BX(block_id);
	if (!obSectionBlock)
	{
		return false;
	}
	else
	{
		var obSectionTitleBlock = BX.findChild(obSectionBlock, {tag: "div", className: "company-department-text"});
		if (obSectionTitleBlock)
		{
			if (this.onSectionSelect)
			{
				this.onSectionSelect({
					id : obSectionTitleBlock.getAttribute('data-section-id'),
					name : obSectionTitleBlock.innerHTML
				});
			}
		}
	}
}

IntranetUsers.prototype.unselect = function(employeeID)
{
	var link = BX("user-selector-unselect-" + employeeID);
	var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
	for(var i = 0; i < arInputs.length; i++)
	{
		if (arInputs[i].value == employeeID)
		{
			arInputs[i].checked = false;
			BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
		}
	}
	if (this.multiple)
	{
		if (link)
		{
			BX.remove(link.parentNode);
		}
		var countSpan = BX(this.name + "_current_count");
		countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;
	}

	this.arSelected[employeeID] = null;

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.setSelected = function(arEmployees)
{
	for(var i = 0, count = this.arSelected.length; i < count; i++)
	{
		if (this.arSelected[i] && this.arSelected[i].id)
			this.unselect(this.arSelected[i].id);
	}

	if (!this.multiple)
	{
		arEmployees = [arEmployees[0]];
	}
	this.arSelected = [];
	for(var i = 0, count = arEmployees.length; i < count; i++)
	{
		this.arSelected[arEmployees[i].id] = arEmployees[i];

		var hiddenInput = BX.create("input", {
			props: {
				className: "intranet-hidden-input",
				value: arEmployees[i].id,
				checked: "checked",
				name: this.name + (this.multiple ? "[]" : "")
			}
		});

		BX(this.name + "_last").appendChild(hiddenInput);

		if (this.multiple)
		{
			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});
			var obUserRow = BX.create("div", {
				props: {
					className: "finder-box-selected-item",
					id: this.name + '_employee_selected_' + arEmployees[i].id
				},
				html: "<div class=\"finder-box-selected-item-icon\" id=\"user-selector-unselect-" + arEmployees[i].id + "\" onclick=\"O_" + this.name + ".unselect(" + arEmployees[i].id + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + BX.util.htmlspecialchars(arEmployees[i].name) + "</span>"
			});
			obSelected.appendChild(obUserRow);
		}

		var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
		for(var j = 0; j < arInputs.length; j++)
		{
			if (arInputs[j].value == arEmployees[i].id)
			{
				BX.toggleClass(arInputs[j].parentNode, BX.hasClass(arInputs[j].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}
	}

	if (this.multiple)
	{
		BX.adjust(BX(this.name + "_current_count"), {text: arEmployees.length});
	}
}

IntranetUsers.prototype.setFixed = function(arEmployees)
{
	if (typeof arEmployees != 'object')
		arEmployees = [];

	this.arFixed = arEmployees;

	var obSelected = BX.findChildren(BX(this.name + '_selected_users'), {className: 'finder-box-selected-item-icon'}, true);

	for (i = 0; i < obSelected.length; i++)
	{
		var userId = obSelected[i].id.replace('user-selector-unselect-', '');

		BX.adjust(obSelected[i], {style: {
			visibility: BX.util.in_array(userId, this.arFixed) ? 'hidden' : 'visible'
		}});
	}
}

IntranetUsers.prototype.search = function(e)
{
	this.searchRqstTmt = clearTimeout(this.searchRqstTmt);
	if (typeof this.searchRqst == 'object')
	{
		this.searchRqst.abort();
		this.searchRqst = false;
	}

	if (!e) e = window.event;

	if (this.searchInput.value.length > 0)
	{
		this.displayTab("search");

		var url = this.getAjaxUrl() + '&MODE=SEARCH&SEARCH_STRING=' + encodeURIComponent(this.searchInput.value);
		if (this.bSubordinateOnly)
			url += "&S_ONLY=Y";
		var _this = this;
		this.searchRqstTmt = setTimeout(function() {
			var startTime = (new Date()).getTime();
			_this.lastSearchTime = startTime;
			_this.searchRqst = BX.ajax.loadJSON(url, BX.proxy(function(data) {
				if (_this.lastSearchTime == startTime)
					_this.showResults(data);
			}, _this));
		}, 400);
	}
}

IntranetUsers.prototype.showResults = function(data)
{
	var arEmployees = data;
	var obSectionDiv = BX(this.name + '_search');

	var arInputs = obSectionDiv.getElementsByTagName("input");
	for(var i = 0, count = arInputs.length; i < count; i++)
	{
		if (arInputs[i].checked)
		{
			BX(this.name + '_last').appendChild(arInputs[i]);
		}
	}

	if (obSectionDiv)
	{
		obSectionDiv.innerHTML = '';

		var table = BX.create("table", {
			props : {
				className : "finder-box-tab-columns",
				cellspacing : "0"
			},
			children : [
				 BX.create("tbody")
			]
		});

		var tr = BX.create("tr");
		table.firstChild.appendChild(tr);

		var td = BX.create("td");
		tr.appendChild(td);

		obSectionDiv.appendChild(table);

		for (var i = 0; i < arEmployees.length; i++)
		{
			var obUserRow;
			var bSelected = false;
			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			}

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}

			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}

			obInput.value = arEmployees[i].ID;

			var text = arEmployees[i].NAME;
			/*
			TODO: good look and feel
			if (arEmployees[i].WORK_POSITION.length > 0)
				text = text + ', ' + arEmployees[i].WORK_POSITION;*/

			var anchor_user_id = "finded_anchor_user_id_" + arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "finder-box-item" + (bSelected ? " finder-box-item-selected" : ""),
					id: anchor_user_id
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "finder-box-item-text"
						},
						text : text
					}),
					BX.create("div", {
						props : {
							className : "finder-box-item-icon"
						}
					})
				]
			})

			td.appendChild(obUserRow);

			if (i == Math.ceil(arEmployees.length / 2) - 1)
			{
				td = BX.create("td");
				table.firstChild.appendChild(td);
			}

			BX.tooltip(arEmployees[i].ID, anchor_user_id, "");
		}
	}
}

IntranetUsers.prototype.displayTab = function(tab)
{
	BX.removeClass(BX(this.name + "_last"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_search"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_structure"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_groups"), "finder-box-tab-content-selected");
	BX.addClass(BX(this.name + "_" + tab), "finder-box-tab-content-selected");

	BX.removeClass(BX(this.name + "_tab_last"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_search"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_structure"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_groups"), "finder-box-tab-selected");
	BX.addClass(BX(this.name + "_tab_" + tab), "finder-box-tab-selected");
}

IntranetUsers.prototype._onFocus = function()
{
	this.searchInput.value = "";
}

IntranetUsers.prototype.getAjaxUrl = function()
{
    return this.ajaxUrl || IntranetUsers.ajaxUrl;
}


})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:102:"/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.min.js?14522774746107";s:6:"source";s:83:"/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.js";s:3:"min";s:87:"/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.min.js";s:3:"map";s:87:"/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.map.js";}"*/
(function(t){var e={};BX.GroupsPopup={create:function(t,i,a){if(!e[t])e[t]=new s(t,i,a);return e[t]}};var s=function(e,s,i){this.tabs=[];this.items2Objects=[];this.selected=[];this.lastGroups=[];this.myGroups=[];this.featuresPerms=null;var a=[];if(i){if(i.lastGroups){this.lastGroups=i.lastGroups}if(i.myGroups){this.myGroups=i.myGroups}if(i.featuresPerms){this.featuresPerms=i.featuresPerms}if(i.events){for(var o in i.events)BX.addCustomEvent(this,o,i.events[o])}if(i.selected&&i.selected.length){this.selected=i.selected;BX.onCustomEvent(this,"onGroupSelect",[this.selected,{onInit:true}])}if(i.searchInput){this.searchInput=i.searchInput}else{this.searchInput=BX.create("input",{props:{className:"bx-finder-box-search-textbox"}});a.push(BX.create("div",{props:{className:"bx-finder-box-search"},style:{},children:[this.searchInput]}))}}BX.adjust(this.searchInput,{events:{keyup:BX.proxy(function(e){if(!e)e=t.event;this.search((e.target||e.srcElement).value)},this),focus:function(){this.value=""},blur:BX.proxy(function(){setTimeout(BX.proxy(function(){if(this.selected[0]){this.searchInput.value=this.selected[0].title}},this),150)},this)}});this.ajaxURL="/bitrix/components/bitrix/socialnetwork.group.selector/ajax.php";if(this.lastGroups.length>0){this.addTab("last",this.lastGroups)}if(this.myGroups.length>0){this.addTab("my",this.myGroups)}this.addTab("search");this.tabsOuter=BX.create("div",{props:{className:"bx-finder-box-tabs"}});this.tabsContentOuter=BX.create("td",{props:{className:"bx-finder-box-tabs-content-cell"}});a.splice(a.length,0,this.tabsOuter,BX.create("div",{props:{className:"popup-window-hr popup-window-buttons-hr"},html:"<i></i>"}),BX.create("div",{props:{className:"bx-finder-box-tabs-content"},children:[BX.create("table",{props:{className:"bx-finder-box-tabs-content-table"},children:[BX.create("tr",{children:[this.tabsContentOuter]})]})]}));this.content=BX.create("div",{props:{className:"bx-finder-box bx-lm-box sonet-groups-finder-box"},style:{padding:"2px 6px 6px 6px",minWidth:"500px"},children:a});this.popupWindow=BX.PopupWindowManager.create(e,s,{content:"",autoHide:true,events:{onPopupFirstShow:BX.proxy(function(t){t.setContent(this.content)},this),onPopupShow:BX.proxy(function(t){this.__render()},this)},buttons:[new BX.PopupWindowButton({text:BX.message("SONET_GROUP_BUTTON_CLOSE"),className:"popup-window-button-accept task-edit-popup-close-but",events:{click:function(){this.popupWindow.close()}}})]})};s.prototype.show=function(){this.popupWindow.show();this.searchInput.focus()};s.prototype.selectTab=function(t){for(var e in this.tabs){BX.removeClass(this.tabs[e].tab,"bx-finder-box-tab-selected");BX.adjust(this.tabs[e].content,{style:{display:"none"}})}BX.addClass(t.tab,"bx-finder-box-tab-selected");BX.adjust(t.content,{style:{display:"block"}})};s.prototype.addTab=function(t,e,s){var i=BX.create("div",{props:{className:"bx-finder-box-tab-content bx-lm-box-tab-content-sonetgroup"}});if(s){BX.adjust(i,{style:{display:"block"}})}var a=BX.create("span",{props:{className:"bx-finder-box-tab"+(s?" bx-finder-box-tab-selected":"")},text:BX.message("SONET_GROUP_TABS_"+t.toUpperCase())});this.tabs[t]={tab:a,content:i};BX.adjust(this.tabs[t].tab,{events:{click:BX.proxy(function(){this.selectTab(this.tabs[t])},this)}});if(e){this.setItems(this.tabs[t],e)}};s.prototype.setItems=function(t,e){BX.cleanNode(t.content);for(var s=0,i=e.length;s<i;s++){t.content.appendChild(this.__renderItem(e[s]))}};s.prototype.select=function(t){this.selected=[t];if(this.items2Objects[t.id]){for(var e=0,s=this.items2Objects[t.id].length;e<s;e++){BX.addClass(this.items2Objects[t.id][e],"bx-finder-box-item-t7-selected")}}BX.onCustomEvent(this,"onGroupSelect",[this.selected,{onInit:false}]);var i=[t.id];for(var e=0,s=this.lastGroups.length;e<s;e++){if(!BX.util.in_array(this.lastGroups[e].id,i)){i.push(this.lastGroups[e].id)}}BX.userOptions.save("socialnetwork","groups_popup","last_selected",i.slice(0,10));this.popupWindow.close()};s.prototype.deselect=function(t){this.selected=[];if(t&&this.items2Objects[t]){for(var e=0,s=this.items2Objects[t].length;e<s;e++){BX.removeClass(this.items2Objects[t][e],"bx-finder-box-item-t7-selected")}}this.searchInput.value=""};s.prototype.search=function(t){if(t.length>0){this.selectTab(this.tabs["search"]);var e=this.ajaxURL+"?mode=search&SITE_ID="+__bx_group_site_id+"&query="+encodeURIComponent(t);if(this.featuresPerms){e+="&features_perms[0]="+encodeURIComponent(this.featuresPerms[0]);e+="&features_perms[1]="+encodeURIComponent(this.featuresPerms[1])}BX.ajax.loadJSON(e,BX.proxy(function(t){this.setItems(this.tabs["search"],t)},this))}};s.prototype.__render=function(){var t=false;BX.cleanNode(this.tabsOuter);BX.cleanNode(this.tabsContentOuter);for(var e in this.tabs){if(!t){t=BX.hasClass(this.tabs[e].tab,"bx-finder-box-tab-selected")}this.tabsOuter.appendChild(this.tabs[e].tab);this.tabsContentOuter.appendChild(this.tabs[e].content)}if(!t){this.selectTab(this.tabs["last"]||this.tabs["my"]||this.tabs["search"])}};s.prototype.__renderItem=function(t){var e=BX.create("div",{props:{className:"bx-finder-box-item-t7-avatar bx-finder-box-item-t7-group-avatar"}});if(t.image){BX.adjust(e,{style:{background:"url('"+t.image+"') no-repeat center center",backgroundSize:"24px 24px"}})}var s=false;for(var i=0;i<this.selected.length;i++){if(this.selected[i].id==t.id){s=true;break}}var a=BX.create("div",{props:{className:"bx-finder-box-item-t7 bx-finder-element bx-lm-element-sonetgroup"+(typeof t.IS_EXTRANET!="undefined"&&t.IS_EXTRANET=="Y"?" bx-lm-element-extranet":"")+(s?" bx-finder-box-item-t7-selected":"")},children:[e,BX.create("div",{props:{className:"bx-finder-box-item-t7-space"}}),BX.create("div",{props:{className:"bx-finder-box-item-t7-info"},children:[BX.create("div",{text:t.title,props:{className:"bx-finder-box-item-t7-name"}})]})],events:{click:BX.proxy(function(){this.select(t)},this)}});if(!this.items2Objects[t.id]){this.items2Objects[t.id]=[a]}else if(!BX.util.in_array(a,this.items2Objects[t.id])){this.items2Objects[t.id].push(a)}return a}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:98:"/bitrix/components/bitrix/socialnetwork.blog.post/templates/.default/script.min.js?145227747317963";s:6:"source";s:78:"/bitrix/components/bitrix/socialnetwork.blog.post/templates/.default/script.js";s:3:"min";s:82:"/bitrix/components/bitrix/socialnetwork.blog.post/templates/.default/script.min.js";s:3:"map";s:82:"/bitrix/components/bitrix/socialnetwork.blog.post/templates/.default/script.map.js";}"*/
function showHiddenDestination(t,e){BX.hide(e);BX("blog-destination-hidden-"+t).style.display="inline"}function showMenuLinkInput(t,e){id="post-menu-"+t+"-link",it=BX.proxy_context,height=parseInt(!!it.getAttribute("bx-height")?it.getAttribute("bx-height"):it.offsetHeight);if(it.getAttribute("bx-status")!="shown"){it.setAttribute("bx-status","shown");if(!BX(id)&&!!BX(id+"-text")){var i=BX(id+"-text"),o=BX.pos(i),s=BX.pos(i.parentNode);pos3=BX.pos(BX.findParent(i,{className:"menu-popup-item"},true));o["height"]=s["height"]-1;BX.adjust(it,{attrs:{"bx-height":it.offsetHeight},style:{overflow:"hidden",display:"block"},children:[BX.create("BR"),BX.create("DIV",{attrs:{id:id},children:[BX.create("SPAN",{attrs:{className:"menu-popup-item-left"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-icon"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-text"},children:[BX.create("INPUT",{attrs:{id:id+"-input",type:"text",value:e},style:{height:o["height"]+"px",width:pos3["width"]-21+"px"},events:{click:function(t){this.select();BX.PreventDefault(t)}}})]})]}),BX.create("SPAN",{className:"menu-popup-item-right"})]})}new BX.fx({time:.2,step:.05,type:"linear",start:height,finish:height*2,callback:BX.delegate(function(t){this.style.height=t+"px"},it)}).start();BX.fx.show(BX(id),.2);BX(id+"-input").select()}else{it.setAttribute("bx-status","hidden");new BX.fx({time:.2,step:.05,type:"linear",start:it.offsetHeight,finish:height,callback:BX.delegate(function(t){this.style.height=t+"px"},it)}).start();BX.fx.hide(BX(id),.2)}}function showBlogPost(t,e){var i=BX.findChild(BX("blg-post-"+t),{className:"feed-post-text-block-inner"},true,false);el2=BX.findChild(BX("blg-post-"+t),{className:"feed-post-text-block-inner-inner"},true,false);BX.remove(e);if(i){var o=300;var s=el2.offsetHeight;new BX.fx({time:1*(s-o)/(1200-o),step:.05,type:"linear",start:o,finish:s,callback:BX.delegate(__blogExpandSetHeight,i),callback_complete:BX.delegate(function(){this.style.maxHeight="none";BX.LazyLoad.showImages(true)},i)}).start()}}function __blogExpandSetHeight(t){this.style.maxHeight=t+"px"}function deleteBlogPost(t){url=BX.message("sonetBPDeletePath");url1=url.replace("#del_post_id#",t);if(BX.findChild(BX("blg-post-"+t),{attr:{id:"form_c_del"}},true,false)){BX.hide(BX("form_c_del"));BX(BX("blg-post-"+t).parentNode.parentNode).appendChild(BX("form_c_del"))}BX.ajax.get(url1,function(e){if(window.deletePostEr&&window.deletePostEr=="Y"){var i=BX("blg-post-"+t);BX.findChild(i,{className:"feed-post-cont-wrap"},true,false).insertBefore(BX.create("SPAN",{html:e}),BX.findChild(i,{className:"feed-user-avatar"},true,false))}else{BX("blg-post-"+t).parentNode.innerHTML=e}});return false}var waitPopupBlogImage=null;function blogShowImagePopup(t){if(!waitPopupBlogImage){waitPopupBlogImage=new BX.PopupWindow("blogwaitPopupBlogImage",window,{autoHide:true,lightShadow:false,zIndex:2,content:BX.create("IMG",{props:{src:t,id:"blgimgppp"}}),closeByEsc:true,closeIcon:true})}else{BX("blgimgppp").src="/bitrix/images/1.gif";BX("blgimgppp").src=t}waitPopupBlogImage.setOffset({offsetTop:0,offsetLeft:0});setTimeout(function(){waitPopupBlogImage.adjustPosition()},100);waitPopupBlogImage.show()}function __blogPostSetFollow(t){var e=BX("log_entry_follow_"+t,true).getAttribute("data-follow")=="Y"?"Y":"N";var i=e=="Y"?"N":"Y";if(BX("log_entry_follow_"+t,true)){BX.findChild(BX("log_entry_follow_"+t,true),{tagName:"a"}).innerHTML=BX.message("sonetBPFollow"+i);BX("log_entry_follow_"+t,true).setAttribute("data-follow",i)}BX.ajax({url:BX.message("sonetBPSetPath"),method:"POST",dataType:"json",data:{log_id:t,action:"change_follow",follow:i,sessid:BX.bitrix_sessid(),site:BX.message("sonetBPSiteId")},onsuccess:function(i){if(i["SUCCESS"]!="Y"&&BX("log_entry_follow_"+t,true)){BX.findChild(BX("log_entry_follow_"+t,true),{tagName:"a"}).innerHTML=BX.message("sonetBPFollow"+e);BX("log_entry_follow_"+t,true).setAttribute("data-follow",e)}},onfailure:function(i){if(BX("log_entry_follow_"+t,true)){BX.findChild(BX("log_entry_follow_"+t,true),{tagName:"a"}).innerHTML=BX.message("sonetBPFollow"+e);BX("log_entry_follow_"+t,true).setAttribute("data-follow",e)}}});return false}(function(){if(!!window.SBPImpPost)return false;window.SBPImpPost=function(t){if(t.getAttribute("sbpimppost")=="Y")return false;this.CID="sbpimppost"+(new Date).getTime();this.busy=false;this.node=t;this.btn=t.parentNode;this.block=t.parentNode.parentNode;this.postId=t.getAttribute("bx-blog-post-id");t.setAttribute("sbpimppost","Y");BX.onCustomEvent(this.node,"onInit",[this]);if(this.postId>0)this.onclick();return false};window.SBPImpPost.prototype.onclick=function(){this.sendData()};window.SBPImpPost.prototype.showClick=function(){var t=this.btn.offsetWidth,e=BX.message("BLOG_ALREADY_READ"),i=BX.create("span",{props:{className:"have-read-text-block"},html:"<i></i>"+e+'<span class="feed-imp-post-footer-comma">,</span>'});this.block.style.minWidth=this.btn.offsetWidth-27+"px";var o=new BX.easing({duration:250,start:{width:t},finish:{width:1},transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:BX.delegate(function(t){this.btn.style.width=t.width+"px"},this),complete:BX.delegate(function(){this.btn.innerHTML="";this.btn.appendChild(i);var t=i.offsetWidth,e=new BX.easing({duration:300,start:{width_2:0},finish:{width_2:t},transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:BX.delegate(function(t){this.btn.style.width=t.width_2+"px"},this)});e.animate()},this)});o.animate()};window.SBPImpPost.prototype.wait=function(t){t=t=="show"?"show":"hide";if(t=="show"){this.node.disabled=true;BX.adjust(this.node,{style:{position:"relative"},children:[BX.create("DIV",{attrs:{className:"mpf-load-img","mpf-load-img":"Y"},style:{position:"absolute",top:0,left:0,width:"100%"}})]})}else{if(!!this.node.lastChild&&this.node.lastChild.hasAttribute("mpf-load-img")){BX.remove(this.node.lastChild)}}};window.SBPImpPost.prototype.sendData=function(){if(this.busy)return false;this.busy=true;window["node"]=this.node;window["obj"]=this;this.wait("show");var t={options:[{post_id:this.postId,name:"BLOG_POST_IMPRTNT",value:"Y"}],sessid:BX.bitrix_sessid()},e=this.node.href;BX.onCustomEvent(this.node,"onSend",[t]);t=BX.ajax.prepareData(t);if(t){e+=(e.indexOf("?")!==-1?"&":"?")+t;t=""}BX.ajax({method:"GET",url:e,dataType:"json",onsuccess:BX.delegate(function(t){this.busy=false;this.wait("hide");this.showClick();BX.onCustomEvent(this.node,"onUserVote",[t]);BX.onCustomEvent("onImportantPostRead",[this.postId,this.CID])},this),onfailure:BX.delegate(function(t){this.busy=false;this.wait("hide")},this)})};top.SBPImpPostCounter=function(t,e,i){this.parentNode=t;this.node=BX.findChild(t,{tagName:"A"});if(!this.node)return false;BX.addCustomEvent(this.node,"onUserVote",BX.delegate(function(t){this.change(t)},this));this.parentNode.SBPImpPostCounter=this;this.node.setAttribute("status","ready");this.node.setAttribute("inumpage",0);this.postId=e;this.popup=null;this.data=[];BX.bind(t,"click",BX.proxy(function(){this.get()},this));BX.bind(t,"mouseover",BX.proxy(function(t){this.init(t)},this));BX.bind(t,"mouseout",BX.proxy(function(t){this.init(t)},this));this.pathToUser=i["pathToUser"];this.nameTemplate=i["nameTemplate"];this.onPullEvent=BX.delegate(function(t,e){if(t=="read"&&!!e&&e["POST_ID"]==this.postId){if(!!e["data"]){this.change(e["data"])}}},this);BX.addCustomEvent("onPullEvent-socialnetwork",this.onPullEvent)};top.SBPImpPostCounter.prototype.click=function(t){t.uController=this;BX.addCustomEvent(t.node,"onUserVote",BX.proxy(this.change,this));BX.addCustomEvent(t.node,"onSend",BX.proxy(function(t){t["PATH_TO_USER"]=this.pathToUser;t["NAME_TEMPLATE"]=this.nameTemplate;t["iNumPage"]=0;t["ID"]=this.postId;t["post_id"]=this.postId;t["name"]="BLOG_POST_IMPRTNT";t["value"]="Y";t["return"]="users"},this));this.btnObj=t};top.SBPImpPostCounter.prototype.change=function(t){if(!!t&&!!t.items){var e=false;this.data=[];for(var i in t.items){this.data.push(t.items[i])}if(t["StatusPage"]=="done")this.node.setAttribute("inumpage","done");else this.node.setAttribute("inumpage",1);BX.adjust(this.parentNode,{style:{display:"inline-block"}})}else{this.node.setAttribute("inumpage","done");BX.hide(this.parentNode)}this.node.firstChild.innerHTML=t["RecordCount"]};top.SBPImpPostCounter.prototype.init=function(t){if(!!this.node.timeoutOver){clearTimeout(this.node.timeoutOver);this.node.timeoutOver=false}if(t.type=="mouseover"){if(!this.node.mouseoverFunc){this.node.mouseoverFunc=BX.delegate(function(){this.get();if(this.popup){BX.bind(this.popup.popupContainer,"mouseout",BX.proxy(function(){this.popup.timeoutOut=setTimeout(BX.proxy(function(){if(!!this.popup){this.popup.close()}},this),400)},this));BX.bind(this.popup.popupContainer,"mouseover",BX.proxy(function(){if(this.popup.timeoutOut)clearTimeout(this.popup.timeoutOut)},this))}},this)}this.node.timeoutOver=setTimeout(this.node.mouseoverFunc,400)}};top.SBPImpPostCounter.prototype.get=function(){if(this.node.getAttribute("inumpage")!="done")this.node.setAttribute("inumpage",parseInt(this.node.getAttribute("inumpage"))+1);this.show();if(this.data.length>0){this.make(this.node.getAttribute("inumpage")!="done")}if(this.node.getAttribute("inumpage")!="done"){this.node.setAttribute("status","busy");BX.ajax({url:"/bitrix/components/bitrix/socialnetwork.blog.blog/users.php",method:"POST",dataType:"json",data:{ID:this.postId,post_id:this.postId,name:"BLOG_POST_IMPRTNT",value:"Y",iNumPage:this.node.getAttribute("inumpage"),PATH_TO_USER:this.pathToUser,NAME_TEMPLATE:this.nameTemplate,sessid:BX.bitrix_sessid()},onsuccess:BX.proxy(function(t){if(!!t&&!!t.items){var e=false;for(var i in t.items){this.data.push(t.items[i])}if(t.StatusPage=="done")this.node.setAttribute("inumpage","done");this.make(this.node.getAttribute("inumpage")!="done")}else{this.node.setAttribute("inumpage","done")}this.node.firstChild.innerHTML=t["RecordCount"];this.node.setAttribute("status","ready")},this),onfailure:BX.proxy(function(t){this.node.setAttribute("status","ready")},this)})}};top.SBPImpPostCounter.prototype.show=function(){if(this.popup!=null)this.popup.close();if(this.popup==null){this.popup=new BX.PopupWindow("bx-vote-popup-cont-"+this.postId,this.node,{lightShadow:true,offsetTop:-2,offsetLeft:3,autoHide:true,closeByEsc:true,bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){this.popup=null},this)},content:BX.create("SPAN",{props:{className:"bx-ilike-wait"}})});this.popup.isNew=true;this.popup.show()}this.popup.setAngle({position:"bottom"});this.popup.bindOptions.forceBindPosition=true;this.popup.adjustPosition();this.popup.bindOptions.forceBindPosition=false};top.SBPImpPostCounter.prototype.make=function(t){if(!this.popup)return true;t=t===false?false:true;var e=this.popup&&this.popup.contentContainer?this.popup.contentContainer:BX("popup-window-content-bx-vote-popup-cont-"+this.postId),i=false,o=false,s=this.data;if(this.popup.isNew){var i=BX.create("SPAN",{props:{className:"bx-ilike-popup"},children:[BX.create("SPAN",{props:{className:"bx-ilike-bottom_scroll"}})]}),o=BX.create("SPAN",{props:{className:"bx-ilike-wrap-block"},children:[i]})}else{i=BX.findChild(this.popup.contentContainer,{className:"bx-ilike-popup"},true)}if(!!i){for(var n in s){if(!BX.findChild(i,{tag:"A",attr:{id:"u"+s[n]["ID"]}},true)){i.appendChild(BX.create("A",{attrs:{id:"u"+s[n]["ID"]},props:{href:s[n]["URL"],target:"_blank",className:"bx-ilike-popup-img"},text:"",children:[BX.create("SPAN",{props:{className:"bx-ilike-popup-avatar"},html:s[n]["PHOTO"]}),BX.create("SPAN",{props:{className:"bx-ilike-popup-name"},html:s[n]["FULL_NAME"]})]}))}}if(t){BX.bind(i,"scroll",BX.delegate(this.popupScrollCheck,this))}}if(this.popup.isNew){this.popup.isNew=false;if(!!e){try{e.removeChild(e.firstChild)}catch(a){}e.appendChild(o)}}if(this.popup!=null){this.popup.bindOptions.forceBindPosition=true;this.popup.adjustPosition();this.popup.bindOptions.forceBindPosition=false}};top.SBPImpPostCounter.prototype.popupScrollCheck=function(){var t=BX.proxy_context;if(t.scrollTop>(t.scrollHeight-t.offsetHeight)/1.5){BX.unbind(t,"scroll",BX.delegate(this.popupScrollCheck,this));this.get()}}})(window);window.BXfpdPostSelectCallback=function(t,e,i){if(!BX.findChild(BX("feed-add-post-destination-item-post"),{attr:{"data-id":t.id}},false,false)){var o=e;prefix="S";if(e=="sonetgroups")prefix="SG";else if(e=="groups"){prefix="UA";o="all-users"}else if(e=="users")prefix="U";else if(e=="department")prefix="DR";var s=e=="sonetgroups"&&typeof window["arExtranetGroupID"]!="undefined"&&BX.util.in_array(t.entityId,window["arExtranetGroupID"])?" feed-add-post-destination-extranet":"";BX("feed-add-post-destination-item-post").appendChild(BX.create("span",{attrs:{"data-id":t.id},props:{className:"feed-add-post-destination feed-add-post-destination-"+o+s},children:[BX.create("input",{attrs:{type:"hidden",name:"SPERM["+prefix+"][]",value:t.id}}),BX.create("span",{props:{className:"feed-add-post-destination-text"},html:t.name}),BX.create("span",{props:{className:"feed-add-post-del-but"},events:{click:function(i){BX.SocNetLogDestination.deleteItem(t.id,e,BXSocNetLogDestinationFormNamePost);BX.PreventDefault(i)},mouseover:function(){BX.addClass(this.parentNode,"feed-add-post-destination-hover")},mouseout:function(){BX.removeClass(this.parentNode,"feed-add-post-destination-hover")}}})]}))}BX("feed-add-post-destination-input-post").value="";BX.SocNetLogDestination.BXfpSetLinkName({formName:window.BXSocNetLogDestinationFormNamePost,tagInputName:"bx-destination-tag-post",tagLink1:BX.message("BX_FPD_LINK_1"),tagLink2:BX.message("BX_FPD_LINK_2")})};window.BXfpdPostClear=function(){var t=BX.findChildren(BX("feed-add-post-destination-item-post"),{className:"feed-add-post-destination"},true);if(t!=null){for(var e=0;e<t.length;e++){BX.remove(t[e])}}BX("feed-add-post-destination-input-post").value="";BX.SocNetLogDestination.BXfpSetLinkName({formName:window.BXSocNetLogDestinationFormNamePost,tagInputName:"bx-destination-tag-post",tagLink1:BX.message("BX_FPD_LINK_1"),tagLink2:BX.message("BX_FPD_LINK_2")})};window.showSharing=function(t,e){BXfpdPostClear();BX("sharePostId").value=t;BX("shareUserId").value=e;BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost]={};if(window["postDest"+t]){for(var i=0;i<window["postDest"+t].length;i++){if(BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost]){BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost][window["postDest"+t][i].id]=window["postDest"+t][i].type}if(!BX.SocNetLogDestination.obItems[BXSocNetLogDestinationFormNamePost][window["postDest"+t][i].type][window["postDest"+t][i].id]){BX.SocNetLogDestination.obItems[BXSocNetLogDestinationFormNamePost][window["postDest"+t][i].type][window["postDest"+t][i].id]={avatar:"",entityId:window["postDest"+t][i].entityId,id:window["postDest"+t][i].id,name:window["postDest"+t][i].name}}}if(BXSocNetLogDestinationFormNamePost)BX.SocNetLogDestination.reInit(BXSocNetLogDestinationFormNamePost);var o=BX.findChildren(BX("feed-add-post-destination-item-post"),{className:"feed-add-post-destination"},true);if(o!=null){for(var s=0;s<o.length;s++){BX.addClass(o[s],"feed-add-post-destination-undelete");BX.remove(o[s].lastChild)}}var n=BX("destination-sharing");if(BX("blg-post-destcont-"+t)){BX("blg-post-destcont-"+t).appendChild(n)}n.style.height=0;n.style.opacity=0;n.style.overflow="hidden";n.style.display="inline-block";new BX.easing({duration:500,start:{opacity:0,height:0},finish:{opacity:100,height:n.scrollHeight-40},transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:function(t){n.style.height=t.height+"px";n.style.opacity=t.opacity/100},complete:function(){n.style.cssText=""}}).animate()}};window.closeSharing=function(){var t=BX("destination-sharing");if(BX("sharePostSubmitButton")){BX.removeClass(BX("sharePostSubmitButton"),"feed-add-button-load")}new BX.easing({duration:500,start:{opacity:100,height:t.scrollHeight-40},finish:{opacity:0,height:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quad),step:function(e){t.style.height=e.height+"px";t.style.opacity=e.opacity/100},complete:function(){BX.hide(t)}}).animate()};window.sharingPost=function(){var t=BX("sharePostId").value;var e=BX("shareUserId").value;var i=BX("blogShare");var o=socBPDest.shareUrl.replace(/#post_id#/,t).replace(/#user_id#/,e);if(BX("sharePostSubmitButton")){BX.addClass(BX("sharePostSubmitButton"),"feed-add-button-load")}var s=BX.findChildren(BX("feed-add-post-destination-item-post"),{className:"feed-add-post-destination"},true);if(s!=null){var n=BX("blog-destination-hidden-"+t);if(!n){var a=BX.findChildren(BX("blg-post-img-"+t),{className:"feed-add-post-destination-new"},true);var p=a[a.length-1]}for(var d=0;d<s.length;d++){if(!BX.hasClass(s[d],"feed-add-post-destination-undelete")){var r=BX.findChild(s[d],{className:"feed-add-post-destination-text"},false,false).innerHTML;var l=BX.findChild(s[d],{tag:"input"},false,false);var u=l.value;var h;if(l.name=="SPERM[SG][]")h="sonetgroups";else if(l.name=="SPERM[DR][]")h="department";else if(l.name=="SPERM[G][]")h="groups";else if(l.name=="SPERM[U][]")h="users";else if(l.name=="SPERM[UA][]")h="groups";if(h.length>0){window["postDest"+t].push({id:u,name:r,type:h});var f=BX.create("span",{children:[BX.create("span",{html:", "}),BX.create("a",{props:{className:"feed-add-post-destination-new"},href:"",html:r})]});if(n){n.appendChild(f)}else if(p){BX(p.parentNode).insertBefore(f,p.nextSibling)}}}}}i.action=o;i.target="";var c,B="";var m=i.elements.length;var g="";for(c=0;c<m;c++){if(B!="")g="&";var a=i.elements[c];if(a.disabled)continue;switch(a.type.toLowerCase()){case"text":case"hidden":B+=g+a.name+"="+BX.util.urlencode(a.value);break;default:break}}B+="&save=Y";BX.ajax({method:"POST",dataType:"html",url:o,data:B,async:true,processData:false});closeSharing()};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:101:"/bitrix/components/bitrix/socialnetwork.blog.post.comment/templates/.default/script.js?14522774735252";s:6:"source";s:86:"/bitrix/components/bitrix/socialnetwork.blog.post.comment/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function() {
	if (!!window.__blogEditComment)
		return;
window.checkForQuote = function(e, node, ENTITY_XML_ID, author_id) {
	if (window.mplCheckForQuote)
		mplCheckForQuote(e, node, ENTITY_XML_ID, author_id)
};

window.__blogLinkEntity = function(entities, formId) {
	if (!!window["UC"] && !!window["UC"]["f" + formId])
	{
		window["UC"]["f" + formId].linkEntity(entities);
		for (var ii in entities)
		{
			if (entities.hasOwnProperty(ii))
			{
				BX.bind(BX('blog-post-addc-add-' + entities[ii][1]), "click", function(){ window['UC'][ii].reply(); });
				BX.addCustomEvent(window["UC"]["f" + formId].eventNode, 'OnUCFormBeforeShow', function(obj) {
					if (!!obj && !!obj.id && obj.id[0] == ii)
					{
						BX.show(BX('blg-comment-' + entities[ii][1]));
					}
				});
				BX.addCustomEvent(window["UC"]["f" + formId].eventNode, 'OnUCFormAfterHide', function(obj) {
					if (!!obj && !!obj.id && obj.id[0] == ii)
					{
						var nodesNew = BX('record-' + obj.id[0] + '-new'),
							nodes = BX.findChildren(BX('blg-comment-' + entities[ii][1]), {"className" : "feed-com-block-cover" }, false);
						nodesNew = (!!nodesNew ? nodesNew.childNodes : []);
						if (!(!!nodesNew && nodesNew.length > 0) && !(!!nodes && nodes.length > 0))
						{
							BX.hide(BX('blg-comment-' + entities[ii][1]));
						}
					}
				});
			}
		}
	}
};

window.__blogEditComment = function(key, postId){
	var data = {
		messageBBCode : top["text"+key],
		messageFields : {
			arImages : top["arComFiles"+key],
			arDocs : top["arComDocs"+key],
			arFiles : top["arComFilesUf"+key],
			arDFiles : top["arComDFiles"+key]}
	};
	BX.onCustomEvent(window, 'OnUCAfterRecordEdit', ['BLOG_' + postId, key, data, 'EDIT']);
};
window.__blogOnUCFormClear = function(obj) {
	LHEPostForm.reinitDataBefore(obj.editorId);
};

window.__blogOnUCFormAfterShow = function(obj, text, data){
	data = (!!data ? data : {});
	BX.onCustomEvent(window, "OnBeforeSocialnetworkCommentShowedUp", ['socialnetwork_blog']);
	var
		post_data = {
			ENTITY_XML_ID : obj.id[0],
			ENTITY_TYPE : obj.entitiesId[obj.id[0]][0],
			ENTITY_ID : obj.entitiesId[obj.id[0]][1],
			parentId : obj.id[1],
			comment_post_id : obj.entitiesId[obj.id[0]][1],
			edit_id : obj.id[1],
			act : (obj.id[1] > 0 ? 'edit' : 'add'),
			logId : obj.entitiesId[obj.id[0]][2]
		};
	for (var ii in post_data)
	{
		if (!obj.form[ii])
			obj.form.appendChild(BX.create('INPUT', {attrs : {name : ii, type: "hidden"}}));
		obj.form[ii].value = post_data[ii];
	}
	obj.form.action = SBPC.actionUrl.replace(/#source_post_id#/, post_data['comment_post_id']);

	var im = BX('captcha');
	if (!!im) {
		BX('captcha_del').appendChild(im);
		im.style.display = "block";
	}
	onLightEditorShow(text, data);
};

window.__blogOnUCFormSubmit =  function(obj, post_data) {
	post_data["decode"] = "Y";
};

window.__blogOnUCAfterRecordAdd = function(ENTITY_XML_ID, response) {
	if (response.errorMessage.length > 0)
		return;

	if (BX('blg-post-inform-' + ENTITY_XML_ID.substr(5)))
	{
		var followNode = BX.findChild(BX('blg-post-inform-' + ENTITY_XML_ID.substr(5)), {'tag':'span', 'className': 'feed-inform-follow'}, true);
		if (followNode)
		{
			var strFollowOld = (followNode.getAttribute("data-follow") == "Y" ? "Y" : "N");
			if (strFollowOld == "N")
			{
				BX.findChild(followNode, { tagName: 'a' }).innerHTML = BX.message('sonetBPFollowY');
				followNode.setAttribute("data-follow", "Y");
			}
		}
	}
};

window.onLightEditorShow = function(content, data){
	var res = {};
	if (data["arFiles"])
	{
		var tmp2 = {}, name, size;
		for (var ij = 0; ij < data["arFiles"].length; ij++)
		{
			name = BX.findChild(BX('wdif-doc-' + data["arFiles"][ij]), {className : "feed-com-file-name"}, true);
			size = BX.findChild(BX('wdif-doc-' + data["arFiles"][ij]), {className : "feed-con-file-size"}, true);

			tmp2['F' + ij] = {
				FILE_ID : data["arFiles"][ij],
				FILE_NAME : (name ? name.innerHTML : "noname"),
				FILE_SIZE : (size ? size.innerHTML : "unknown"),
				CONTENT_TYPE : "notimage/xyz"};
		}
		res["UF_BLOG_COMMENT_DOC"] = {
			USER_TYPE_ID : "file",
			FIELD_NAME : "UF_BLOG_COMMENT_DOC[]",
			VALUE : tmp2};
	}
	if (data["arDocs"])
		res["UF_BLOG_COMMENT_FILE"] = {
			USER_TYPE_ID : "webdav_element",
			FIELD_NAME : "UF_BLOG_COMMENT_FILE[]",
			VALUE : BX.clone(data["arDocs"])};
	if (data["arDFiles"])
		res["UF_BLOG_COMMENT_FILE"] = {
			USER_TYPE_ID : "disk_file",
			FIELD_NAME : "UF_BLOG_COMMENT_FILE[]",
			VALUE : BX.clone(data["arDFiles"])};
	LHEPostForm.reinitData(SBPC.editorId, content, res);
	if (data["arImages"])
	{
		var tmp, handler = LHEPostForm.getHandler(SBPC.editorId), controllerId = '';
		for (var ii in data["arImages"])
		{
			if (data["arImages"].hasOwnProperty(ii))
			{
				tmp = {
					id : data["arImages"][ii]["id"],
					element_id : data["arImages"][ii]["id"],
					element_name : data["arImages"][ii]["name"],
					element_size : 0,
					element_content_type: data["arImages"][ii]["type"],
					element_url: data["arImages"][ii]["src"],
					element_thumbnail: data["arImages"][ii]["thumbnail"],
					element_image: data["arImages"][ii]["src"],
					parser: 'postimage',
					storage : 'bfile'
				};
				var ret = handler.checkFile(tmp.id, 'common', tmp, true);
			}
		}
	}
}
})(window);


/* End */
;
; /* Start:"a:4:{s:4:"full";s:89:"/bitrix/components/bitrix/main.post.list/templates/.default/script.min.js?145227743127737";s:6:"source";s:69:"/bitrix/components/bitrix/main.post.list/templates/.default/script.js";s:3:"min";s:73:"/bitrix/components/bitrix/main.post.list/templates/.default/script.min.js";s:3:"map";s:73:"/bitrix/components/bitrix/main.post.list/templates/.default/script.map.js";}"*/
(function(window){window["UC"]=!!window["UC"]?window["UC"]:{};if(!!window["FCList"])return;var safeEditing=true,safeEditingCurrentObj=null,quoteData=null;window.FCList=function(e,t){this.CID=e["CID"];this.ENTITY_XML_ID=e["ENTITY_XML_ID"];this.container=e["container"];this.nav=e["nav"];this.mid=e["mid"];this.order=e["order"];this.status="ready";this.msg=!!this.nav?this.nav.innerHTML:"";this.params=!!t?t:{};this.pullNewRecords={};this.rights=e["rights"];if(!!e["params"]["NOTIFY_TAG"]&&!!e["params"]["NOTIFY_TEXT"]&&!!window["UC"]["Informer"]){BX.addCustomEvent(window,"OnUCCommentWasPulled",BX.delegate(function(t,i){if(this.ENTITY_XML_ID==t[0]){window["UC"]["Informer"].check(t,i,e["params"]["NOTIFY_TAG"],e["params"]["NOTIFY_TEXT"])}},this));window["UC"]["InformerTags"][e["params"]["NOTIFY_TAG"]]=!!window["UC"]["InformerTags"][e["params"]["NOTIFY_TAG"]]?window["UC"]["InformerTags"][e["params"]["NOTIFY_TAG"]]:[]}BX.bind(this.nav,"click",BX.proxy(function(e){BX.PreventDefault(e);this.get();return false},this));BX.addCustomEvent(window,"OnUCUserIsWriting",BX.delegate(function(t,i){if(this.ENTITY_XML_ID==t){BX.ajax({url:"/bitrix/components/bitrix/main.post.list/templates/.default/activity.php",method:"POST",dataType:"json",data:{AJAX_POST:"Y",ENTITY_XML_ID:this.ENTITY_XML_ID,MODE:"PUSH&PULL",sessid:BX.bitrix_sessid(),PATH_TO_USER:e["params"]["PATH_TO_USER"],AVATAR_SIZE:e["params"]["AVATAR_SIZE"],NAME_TEMPLATE:e["params"]["NAME_TEMPLATE"],SHOW_LOGIN:e["params"]["SHOW_LOGIN"]}})}},this));BX.addCustomEvent(window,"OnUCAfterRecordAdd",BX.delegate(function(e,t){if(this.ENTITY_XML_ID==e){this.add(t["messageId"],t,true,"simple")}},this));BX.addCustomEvent(window,"OnUCFormSubmit",BX.delegate(function(e,t,i,s){if(this.ENTITY_XML_ID==e){this.pullNewRecords[e+"-0"]="busy"}},this));BX.addCustomEvent(window,"OnUCFormResponse",BX.delegate(function(e,t,i,s){if(this.ENTITY_XML_ID==e){this.pullNewRecords[e+"-0"]="ready";this.pullNewRecords[e+"-"+t]="done"}},this));BX.addCustomEvent(window,"OnUCUserQuote",BX.delegate(function(e){if(this.ENTITY_XML_ID==e&&this.quote&&this.quote.popup){this.quote.popup.hide()}},this));if(location.hash&&parseInt(location.hash.replace("#com",""))>0)this.checkHash(parseInt(location.hash.replace("#com","")));if(e["params"]["SHOW_FORM"]=="Y"){this.quote.show=BX.delegate(function(e,t){setTimeout(BX.delegate(function(){this.quoteShow(e,t)},this),50)},this);var i=BX("record-"+this.ENTITY_XML_ID+"-new"),s=BX.findChildren(i.parentNode,{tagName:"DIV",className:"feed-com-block-cover"},false);s=!!s?s:[];s.push(i);if(!!this.container)s.push(this.container);for(var o=0;o<s.length;o++){BX.bind(s[o],"mouseup",this.quote.show)}}BX.addCustomEvent(window,"onQuote"+this.ENTITY_XML_ID,this.quote.show)};window.FCList.prototype={quote:{show:BX.DoNothing(),popup:null},quoteCheck:function(){var e="",t,i=null;if(window.getSelection){t=window.getSelection();e=t.toString()}else if(document.selection){t=document.selection;e=t.createRange().text}if(e!=""){var s=BX("record-"+this.ENTITY_XML_ID+"-new"),o=BX.findParent(t.focusNode,{tagName:"DIV",className:"feed-com-block-cover"},s.parentNode),a=BX.findParent(t.anchorNode,{tagName:"DIV",className:"feed-com-block-cover"},s.parentNode);if(o!=a||BX(o)&&!o.hasAttribute("id")){e=""}else if(BX(o)){var n=BX(o.getAttribute("id").replace(/\-cover$/,"-actions-reply"));if(n){i={id:parseInt(n.getAttribute("bx-mpl-author-id")),name:n.getAttribute("bx-mpl-author-name")}}}}if(e==""){if(!!this.quote.popup)this.quote.popup.hide();return false}return{text:e,author:i}},quoteShow:function(e,t){t=!!t?t:this.quoteCheck();if(!t||!t["text"]){quoteData=null;return false}quoteData=t;if(this.quote.popup==null){this.quote.popup=new MPLQuote({id:this.ENTITY_XML_ID,closeByEsc:true,autoHide:true,autoHideTimeout:2500,events:{click:BX.delegate(function(e){BX.PreventDefault(e);safeEditingCurrentObj=safeEditing;BX.onCustomEvent(window,"OnUCUserQuote",[this.ENTITY_XML_ID,t["author"],t["text"],safeEditingCurrentObj]);this.quote.popup.hide()},this)},classEvents:{onQuoteHide:BX.proxy(function(){quoteData=null;this.quote.popup=null},this)}})}this.quote.popup.show(e)},display:function(e,t){var i=0,s=0,o=0,a=this.container;e=e=="hide"?"hide":"show";if(e=="hide"){i=this.container.offsetHeight;o=1*i/2e3;o=o<.3?.3:o>.5?.5:o;a.style.overflow="hidden";new BX.easing({duration:o*1e3,start:{height:i,opacity:100},finish:{height:s,opacity:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){a.style.maxHeight=e.height+"px";a.style.opacity=e.opacity/100},complete:function(){a.style.cssText="";a.style.display="none"}}).animate()}else{i=!!t?t:20;a.style.display="block";a.style.overflow="hidden";a.style.maxHeight=i;s=this.container.offsetHeight;o=1*(s-i)/(2e3-i);o=o<.3?.3:o>.8?.8:o;new BX.easing({duration:o*1e3,start:{height:i,opacity:i>0?100:0},finish:{height:s,opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){a.style.maxHeight=e.height+"px";a.style.opacity=e.opacity/100},complete:function(){a.style.cssText="";a.style.maxHeight="none"}}).animate()}},get:function(){if(this.status=="done"){if(this.nav.getAttribute("bx-visibility-status")=="visible"){this.display("hide");BX.adjust(this.nav,{attrs:{"bx-visibility-status":"none"},html:this.msg})}else{this.display("show");BX.adjust(this.nav,{attrs:{"bx-visibility-status":"visible"},html:BX.message("BLOG_C_HIDE")})}}else if(this.status=="ready"){this.send()}return false},send:function(){this.status="busy";BX.addClass(this.nav,"feed-com-all-hover");var e=BX.ajax.prepareData({AJAX_POST:"Y",ENTITY_XML_ID:this.ENTITY_XML_ID,MODE:"LIST",FILTER:this.order=="ASC"?{">ID":this.mid}:{"<ID":this.mid},sessid:BX.bitrix_sessid()}),t=BX.util.htmlspecialcharsback(this.nav.getAttribute("href"));t=t.indexOf("#")!==-1?t.substr(0,t.indexOf("#")):t;BX.ajax({url:t+(t.indexOf("?")!==-1?"&":"?")+e,method:"GET",dataType:"json",data:"",onsuccess:BX.proxy(this.build,this),onfailure:BX.proxy(function(){this.status="done";this.wait("hide")},this)})},build:function(e){this.status="ready";this.wait("hide");BX.removeClass(this.nav,"feed-com-all-hover");if(!!e&&e["status"]==true){var t=!!e["navigation"]?BX.create("DIV",{html:e["navigation"]}):null,i=BX.processHTML(e["messageList"],false);var s=this.container.offsetHeight;if(this.order=="ASC")this.container.innerHTML=this.container.innerHTML+i.HTML;else this.container.innerHTML=i.HTML+this.container.innerHTML;BX.onCustomEvent(window,"OnUCFeedChanged",[[this.ENTITY_XML_ID,this.mid]]);this.display("show",s);if(!!t)t=t.firstChild;if(!!t)BX.adjust(this.nav,{attrs:{href:t.getAttribute("href")},html:t.innerHTML});else{BX.adjust(this.nav,{attrs:{href:"javascript:void(0);","bx-visibility-status":"visible"},html:BX.message("BLOG_C_HIDE")});this.status="done"}var o=0,a=BX.delegate(function(){o++;if(o<100){if(this.container.childNodes.length>0)BX.ajax.processScripts(i.SCRIPT);else BX.defer(a)()}},this);BX.defer(a)()}},wait:function(e){e=e=="show"?"show":"hide"},reply:function(e){safeEditingCurrentObj=safeEditing;if(!!e)BX.onCustomEvent(window,"OnUCUserReply",[this.ENTITY_XML_ID,e.getAttribute("bx-mpl-author-id"),e.getAttribute("bx-mpl-author-name"),safeEditingCurrentObj]);else BX.onCustomEvent(window,"OnUCUserReply",[this.ENTITY_XML_ID,undefined,undefined,safeEditingCurrentObj])},add:function(e,t,i,s){if(!(!!t&&!!e&&parseInt(e[1])>0))return false;var o=BX("record-"+e.join("-")+"-cover"),a=!!t["message"]?t["message"]:fcParseTemplate({messageFields:t["messageFields"]},{DATA_TIME_FORMAT:this.DATA_TIME_FORMAT,RIGHTS:this.rights}),n=BX.processHTML(a,false),r;if(!!o){if(!!i){o.parentNode.insertBefore(BX.create("DIV",{attrs:{id:"record-"+e.join("-")+"-cover",className:"feed-com-block-cover"},html:n.HTML}),o);BX.remove(o)}}else{o=BX("record-"+e[0]+"-new");var d=["MODERATE","EDIT","DELETE"],l=false;for(var c in d){if(this.rights[d[c]]=="OWNLAST"){l=true;break}}if(l){r=!!o.lastChild&&o.lastChild.className=="feed-com-block-cover"?[o.lastChild]:[];if(this.addCheckPreviousNodes!==true){r=BX.findChildren(o.parentNode,{tagName:"DIV",className:"feed-com-block-cover"},false);var u=BX.findChildren(o,{tagName:"DIV",className:"feed-com-block-cover"},false),h,p;r=!!r?r:[];u=!!u?u:[];while(u.length>0&&(h=u.pop())&&!!h)r.push(h);this.addCheckPreviousNodes=true}while(h=r.pop()){p=BX(h.id.replace("-cover","-actions"));if(!!p){if(this.rights["EDIT"]=="OWNLAST")p.setAttribute("bx-mpl-edit-show","N");if(this.rights["MODERATE"]=="OWNLAST")p.setAttribute("bx-mpl-moderate-show","N");if(this.rights["DELETE"]=="OWNLAST")p.setAttribute("bx-mpl-delete-show","N")}}}o.appendChild(BX.create("DIV",{attrs:{id:"record-"+e.join("-")+"-cover",className:"feed-com-block-cover"},style:{opacity:0,height:0,overflow:"hidden"},html:n.HTML}));var E=BX("record-"+e.join("-")+"-cover");if(!!E){if(s!=="simple"){var T=BX.pos(E),f=BX.GetWindowScrollPos(),m=BX.GetWindowInnerSize();new BX.easing({duration:1e3,start:{opacity:0,height:0},finish:{opacity:100,height:E.scrollHeight},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){E.style.height=e.height+"px";E.style.opacity=e.opacity/100;if(f.scrollTop>0&&T.top<f.scrollTop+m.innerHeight)window.scrollTo(0,f.scrollTop+e.height)},complete:function(){E.style.cssText=""}}).animate()}else{new BX.easing({duration:500,start:{height:0,opacity:0},finish:{height:E.scrollHeight,opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.cubic),step:function(e){E.style.height=e.height+"px";E.style.opacity=e.opacity/100},complete:function(){E.style.cssText=""}}).animate()}}}var X=0,B=function(){X++;if(X<100){var t=BX("record-"+e.join("-")+"-cover");if(t&&t.childNodes.length>0)BX.ajax.processScripts(n.SCRIPT);else BX.defer(B)()}};BX.defer(B)();BX.onCustomEvent(window,"OnUCRecordHaveDrawn",[this.ENTITY_XML_ID,t]);BX.onCustomEvent(window,"OnUCFeedChanged",[e]);return true},pullNewAuthor:function(e,t,i){BX.onCustomEvent(window,"OnUCUsersAreWriting",[this.ENTITY_XML_ID,e,t,i])},pullNewRecord:function(e){var t=[this.ENTITY_XML_ID,parseInt(e["ID"])];if(!!BX("record-"+t.join("-")+"-cover"))return true;else if(!!this.pullNewRecords[t.join("-")]&&this.pullNewRecords[t.join("-")]=="busy")return true;else if(!!this.pullNewRecords[t[0]+"-0"]&&this.pullNewRecords[t[0]+"-0"]=="busy")return setTimeout(BX.proxy(function(){this.pullNewRecord(e)},this),100);BX.onCustomEvent(window,"OnUCBeforeCommentWillBePulled",[t,e]);var i=!!e["URL"]&&!!e["URL"]["LINK"];if(i&&!!this.rights){i=false;if(e["APPROVED"]!="Y"){if(this.rights["MODERATE"]=="Y")i=true;else return false}}if(e["NEED_REQUEST"]=="Y")i=true;if(i){if(e["URL"]["LINK"].indexOf("#GROUPS_PATH#")>=0&&!!BX.message("MPL_WORKGROUPS_PATH"))e["URL"]["LINK"]=e["URL"]["LINK"].replace("#GROUPS_PATH#",BX.message("MPL_WORKGROUPS_PATH"));this.pullNewRecords[t.join("-")]="busy";var s=BX.ajax.prepareData({AJAX_POST:"Y",ENTITY_XML_ID:this.ENTITY_XML_ID,MODE:"RECORD",FILTER:{ID:e["ID"]},sessid:BX.bitrix_sessid()}),o=e["URL"]["LINK"];o=o.indexOf("#")!==-1?o.substr(0,o.indexOf("#")):o;BX.ajax({url:o+(o.indexOf("?")!==-1?"&":"?")+s,method:"GET",dataType:"json",data:"",onsuccess:BX.delegate(function(i){if(!!BX("record-"+t.join("-")+"-cover"))return;this.add([this.ENTITY_XML_ID,parseInt(e["ID"])],i);var s=BX("record-"+t.join("-")+"-cover"),o=BX.findChild(s,{className:"feed-com-block"},true,false);BX.addClass(s,"comment-new-answer");BX.addClass(o,"feed-com-block-pointer-to-new feed-com-block-new");this.pullNewRecords[t.join("-")]="done";if(BX("record-"+t[0]+"-corner")){BX.addClass(BX("record-"+t[0]+"-corner"),BX.hasClass(o,"feed-com-block-new")?"feed-post-block-yellow-corner":"");BX("record-"+t[0]+"-corner").removeAttribute("id")}BX.onCustomEvent(window,"OnUCCommentWasPulled",[t,i])},this)})}else{if(e&&!(e["AUTHOR"]&&e["AUTHOR"]["ID"]+""==BX.message("USER_ID")+""))e["NEW"]="Y";this.add(t,{messageFields:e});var a=BX("record-"+t.join("-")+"-cover"),n=BX.findChild(a,{className:"feed-com-block"},true,false);if(BX("record-"+t[0]+"-corner")){BX.addClass(BX("record-"+t[0]+"-corner"),e["NEW"]=="Y"?"feed-post-block-yellow-corner":"");BX("record-"+t[0]+"-corner").removeAttribute("id")}BX.addClass(a,"comment-new-answer");if(e["NEW"]=="Y"){BX.addClass(n,"feed-com-block-pointer-to-new feed-com-block-new")}this.pullNewRecords[t.join("-")]="done";BX.onCustomEvent(window,"OnUCCommentWasPulled",[t,{messageFields:e}])}return true},act:function(url,id,act){if(url.substr(0,1)!="/"){try{eval(url);return false}catch(e){}if(BX.type.isFunction(url)){url(this,id,act);return false}}fcShowWait(BX("record-"+this.ENTITY_XML_ID+"-"+id+"-actions"));act=act==="EDIT"?"EDIT":act==="DELETE"?"DELETE":"MODERATE";id=parseInt(id);var data=BX.ajax.prepareData({sessid:BX.bitrix_sessid(),MODE:"RECORD",NOREDIRECT:"Y",AJAX_POST:"Y",FILTER:{ID:id},ENTITY_XML_ID:this.ENTITY_XML_ID});url=url.indexOf("#")!==-1?url.substr(0,url.indexOf("#")):url;BX.ajax({method:"GET",url:url+(url.indexOf("?")!==-1?"&":"?")+data,data:"",dataType:"json",onsuccess:BX.proxy(function(e){fcCloseWait(BX("record-"+this.ENTITY_XML_ID+"-"+id).firstChild);if(!!e&&typeof e=="object"&&e["status"]){if(act!=="EDIT"){var t=BX("record-"+this.ENTITY_XML_ID+"-"+id+"-cover");if(!!e["message"]&&!!t){var i=BX.processHTML(e["message"],false);t.innerHTML=i.HTML;var s=0,o=function(){s++;if(s<100){if(t.childNodes.length>0)BX.ajax.processScripts(i.SCRIPT);else BX.defer(o)()}};BX.defer(o)();e["okMessage"]=""}else if(act=="DELETE"&&!!e["okMessage"]){BX.hide(BX("record-"+this.ENTITY_XML_ID+"-"+id))}}BX.onCustomEvent(window,"OnUCAfterRecordEdit",[this.ENTITY_XML_ID,id,e,act]);BX.onCustomEvent(window,"OnUCFeedChanged",[id])}this.busy=false},this),onfailure:BX.delegate(function(){fcCloseWait()},this)});return false},checkHash:function(e){var t=[this.ENTITY_XML_ID,e],i=BX("record-"+t.join("-")+"-cover");if(!!i){var s=BX.pos(i);window.scrollTo(0,s["top"]);i=BX.findChild(i,{className:"feed-com-block"},true,false);BX.removeClass(i,"feed-com-block-pointer-to-new feed-com-block-new");BX.addClass(i,"feed-com-block-pointer")}}};window.FCList.getQuoteData=function(){return quoteData};window["fcExpandComment"]=function(e,t){if(!BX("record-"+e+"-text")){return false}var i=BX("record-"+e+"-text"),s=i.parentNode,o=200,a=i.offsetHeight,n={height:o},r={height:a};if(!!t)BX.remove(t);var d=1*(a-o)/(2e3-o);d=d<.3?.3:d>.8?.8:d;s.style.maxHeight=n.height+"px";s.style.overflow="hidden";new BX.easing({duration:d*1e3,start:n,finish:r,transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){s.style.maxHeight=e.height+"px";s.style.opacity=e.opacity/100},complete:function(){s.style.cssText="";s.style.maxHeight="none";BX.LazyLoad.showImages(true)}}).animate();BX.onCustomEvent(window,"OnUCFeedChanged",[e.split("-")])};var lastWaitElement=null;window["fcShowWait"]=function(e){if(e&&!BX.type.isElementNode(e))e=null;e=e||this;if(BX.type.isElementNode(e)){BX.defer(function(){e.disabled=true})();var t=BX.findParent(e,BX.is_relative);e.bxwaiter=(t||document.body).appendChild(BX.create("DIV",{props:{className:"feed-com-loader"},style:{position:"absolute"}}));lastWaitElement=e;return e.bxwaiter}return true};window["fcCloseWait"]=function(e){if(e&&!BX.type.isElementNode(e))e=null;e=e||lastWaitElement||this;if(BX.type.isElementNode(e)){if(e.bxwaiter&&e.bxwaiter.parentNode){e.bxwaiter.parentNode.removeChild(e.bxwaiter);e.bxwaiter=null}e.disabled=false;if(lastWaitElement==e)lastWaitElement=null}};window["fcShowActions"]=function(e,t,i){var s=[];if(i.getAttribute("bx-mpl-view-show")=="Y"){s.push({text:BX.message("MPL_MES_HREF"),href:i.getAttribute("bx-mpl-view-url").replace(/\#(.+)$/gi,"")+"#com"+t});s.push({text:'<span id="record-popup-'+e+"-"+t+'-link-text">'+BX.message("B_B_MS_LINK")+"</span>",onclick:function(){var s="record-popup-"+e+"-"+t+"-link",o=BX.proxy_context,a=parseInt(!!o.getAttribute("bx-height")?o.getAttribute("bx-height"):o.offsetHeight);if(o.getAttribute("bx-status")!="shown"){o.setAttribute("bx-status","shown");if(!BX(s)&&!!BX(s+"-text")){var n=BX(s+"-text"),r=BX.pos(n),d=BX.pos(n.parentNode),l=BX.findChildren(n.parentNode.parentNode.parentNode,{className:"menu-popup-item-text"},true),c=i.getAttribute("bx-mpl-view-url").replace(/\#(.+)$/gi,"")+"#com"+t;r["height"]=d["height"]-1;if(l){var u=0,h;for(var p=0;p<l.length;p++){h=BX.pos(l[p]);u=Math.max(u,h["width"])}d["width"]=u}BX.adjust(o,{attrs:{"bx-height":o.offsetHeight},style:{overflow:"hidden",display:"block"},children:[BX.create("BR"),BX.create("DIV",{attrs:{id:s},children:[BX.create("SPAN",{attrs:{className:"menu-popup-item-left"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-icon"}}),BX.create("SPAN",{attrs:{className:"menu-popup-item-text"},children:[BX.create("INPUT",{attrs:{id:s+"-input",type:"text",value:(c.indexOf("http")<0?location.protocol+"//"+location.host:"")+c},style:{height:d["height"]+"px",width:d["width"]+"px"},events:{click:function(e){this.select();BX.PreventDefault(e)}}})]})]}),BX.create("SPAN",{className:"menu-popup-item-right"})]})}new BX.fx({time:.2,step:.05,type:"linear",start:a,finish:a*2,callback:BX.delegate(function(e){this.style.height=e+"px"},o)}).start();BX.fx.show(BX(s),.2);BX(s+"-input").select()}else{o.setAttribute("bx-status","hidden");new BX.fx({time:.2,step:.05,type:"linear",start:o.offsetHeight,finish:a,callback:BX.delegate(function(e){this.style.height=e+"px"},o)}).start();BX.fx.hide(BX(s),.2)}}})}if(i.getAttribute("bx-mpl-edit-show")=="Y")s.push({text:BX.message("BPC_MES_EDIT"),onclick:function(){window["UC"][e].act(i.getAttribute("bx-mpl-edit-url"),t,"EDIT");this.popupWindow.close();return false}});if(i.getAttribute("bx-mpl-moderate-show")=="Y")s.push({text:i.getAttribute("bx-mpl-moderate-approved")=="hidden"?BX.message("BPC_MES_SHOW"):BX.message("BPC_MES_HIDE"),onclick:function(){window["UC"][e].act(i.getAttribute("bx-mpl-moderate-url"),t,"MODERATE");this.popupWindow.close()}});if(i.getAttribute("bx-mpl-delete-show")=="Y")s.push({text:BX.message("BPC_MES_DELETE"),onclick:function(){if(confirm(BX.message("BPC_MES_DELETE_POST_CONFIRM")))window["UC"][e].act(i.getAttribute("bx-mpl-delete-url"),t,"DELETE");this.popupWindow.close();return false}});if(s.length>0){for(var o in s)s[o]["className"]="blog-comment-popup-menu";BX.PopupMenu.show("action-"+e+"-"+t,i,s,{offsetLeft:-18,offsetTop:2,lightShadow:false,angle:{position:"top",offset:50},events:{onPopupClose:function(i){this.destroy();BX.PopupMenu.Data["action-"+e+"-"+t]=null}}})}};window["fcParseTemplate"]=function(data,params){params=!!params?params:{};params["DATE_TIME_FORMAT"]=!!params["DATE_TIME_FORMAT"]?params["DATE_TIME_FORMAT"]:"d F Y G:i";params["TIME_FORMAT"]=!!params["DATE_TIME_FORMAT"]&&params["DATE_TIME_FORMAT"].indexOf("a")>=0?"g:i a":"G:i";var ii=0,res=!!data&&!!data["messageFields"]?data["messageFields"]:data,replacement={ID:"",FULL_ID:"",ENTITY_XML_ID:"",NEW:"old",APPROVED:"Y",DATE:"",TEXT:"",CLASSNAME:"",VIEW_URL:"",VIEW_SHOW:"N",EDIT_URL:"",EDIT_SHOW:"N",MODERATE_URL:"",MODERATE_SHOW:"N",DELETE_URL:"",DELETE_SHOW:"N",BEFORE_HEADER:"",BEFORE_ACTIONS:"",AFTER_ACTIONS:"",AFTER_HEADER:"",BEFORE:"",AFTER:"",AUTHOR_ID:0,AUTHOR_AVATAR_IS:"N",AUTHOR_AVATAR:"",AUTHOR_URL:"",AUTHOR_NAME:"",BEFORE_RECORD:"",AFTER_RECORD:"",AUTHOR_EXTRANET_STYLE:""},txt=BX.message("MPL_RECORD_TEMPLATE");if(!!res&&!!data["messageFields"]){res["URL"]=!!res["URL"]?res["URL"]:{};res["AUTHOR"]=!!res["AUTHOR"]?res["AUTHOR"]:{};res["PANELS"]=!!res["PANELS"]?res["PANELS"]:{};replacement={ID:res["ID"],FULL_ID:res["FULL_ID"].join("-"),ENTITY_XML_ID:res["ENTITY_XML_ID"],NEW:res["NEW"]=="Y"?"new":"old",APPROVED:res["APPROVED"]!="Y"?"hidden":"approved",DATE:res["POST_DATE"],TEXT:res["POST_MESSAGE_TEXT"],CLASSNAME:res["CLASSNAME"]?" "+res["CLASSNAME"]:"",VIEW_URL:res["URL"]["LINK"],VIEW_SHOW:!!res["URL"]["LINK"]?"Y":"N",EDIT_URL:res["URL"]["EDIT"],EDIT_SHOW:!!res["PANELS"]["EDIT"]&&!!res["URL"]["EDIT"]?res["PANELS"]["EDIT"]:"N",MODERATE_URL:res["URL"]["MODERATE"],MODERATE_SHOW:!!res["PANELS"]["MODERATE"]&&!!res["URL"]["MODERATE"]?res["PANELS"]["MODERATE"]:"N",DELETE_URL:res["URL"]["DELETE"],DELETE_SHOW:!!res["PANELS"]["DELETE"]&&!!res["URL"]["DELETE"]?res["PANELS"]["DELETE"]:"N",BEFORE_HEADER:res["BEFORE_HEADER"],BEFORE_ACTIONS:res["BEFORE_ACTIONS"],AFTER_ACTIONS:res["AFTER_ACTIONS"],AFTER_HEADER:res["AFTER_HEADER"],BEFORE:res["BEFORE"],AFTER:res["AFTER"],BEFORE_RECORD:res["BEFORE_RECORD"],AFTER_RECORD:res["AFTER_RECORD"],AUTHOR_ID:res["AUTHOR"]["ID"],AUTHOR_AVATAR_IS:!!res["AUTHOR"]["AVATAR"]?"Y":"N",AUTHOR_AVATAR:!!res["AUTHOR"]["AVATAR"]?res["AUTHOR"]["AVATAR"]:"/bitrix/images/1.gif",AUTHOR_URL:window.mplReplaceUserPath(res["AUTHOR"]["URL"]),AUTHOR_NAME:res["AUTHOR"]["NAME"],AUTHOR_EXTRANET_STYLE:!!res["AUTHOR"]["IS_EXTRANET"]?" feed-com-name-extranet":""};if(!!res["POST_TIMESTAMP"]){res["POST_TIMESTAMP"]=parseInt(res["POST_TIMESTAMP"])+parseInt(BX.message("USER_TZ_OFFSET"))+parseInt(BX.message("SERVER_TZ_OFFSET"));if(BX.date.format("d F Y",res["POST_TIMESTAMP"])==BX.date.format("d F Y"))replacement["DATE"]=BX.date.format(params["TIME_FORMAT"],res["POST_TIMESTAMP"],false,true);else replacement["DATE"]=BX.date.format(params["DATE_TIME_FORMAT"],res["POST_TIMESTAMP"],false,true)}if(!!params["RIGHTS"]){var acts=["MODERATE","EDIT","DELETE"],act="";for(ii in acts){act=acts[ii];if(!!params["RIGHTS"][act]&&params["RIGHTS"][act]!="N"&&!!replacement[act+"_URL"]){if((params["RIGHTS"][act]=="OWN"||params["RIGHTS"][act]=="OWNLAST")&&parseInt(BX.message("USER_ID"))>0&&BX.message("USER_ID")==res["AUTHOR"]["ID"]||params["RIGHTS"][act]=="ALL"||params["RIGHTS"][act]=="Y"){replacement[act+"_SHOW"]="Y"}}}}}else{for(ii in replacement){replacement[ii]=!!data[ii]?data[ii]:replacement[ii]}}for(ii in replacement){replacement[ii]=!!replacement[ii]?replacement[ii]:""}replacement["SHOW_POST_FORM"]=!!BX("record-"+replacement["ENTITY_XML_ID"]+"-0-placeholder")?"Y":"N";for(var ij in replacement){eval("txt = txt.replace(/#"+ij+"#/g, replacement[ij])")}return txt};window["fcPull"]=function(e,t){BX.ajax({url:"/bitrix/components/bitrix/main.post.list/templates/.default/component_epilog.php",method:"POST",data:{AJAX_POST:"Y",ENTITY_XML_ID:e,MODE:"PUSH&PULL",sessid:BX.bitrix_sessid(),DATA:t}})};BX.addCustomEvent(window,"OnUCCommentWasPulled",function(e){window["UC"]["PULLED"]=!!window["UC"]["PULLED"]?window["UC"]["PULLED"]:[];window["UC"]["PULLED"].push(e);if(!window["UC"]["PULLEDScreenData"]){var t=BX.GetWindowScrollPos();window["UC"]["PULLEDScreenData"]={scrollTop:t.scrollTop,time:(new Date).getTime()}}window["UC"]["PULLEDScreenData"]["checked"]=false;window["UC"]["PULLEDTimeout"]=!!window["UC"]["PULLEDTimeout"]?window["UC"]["PULLEDTimeout"]:0;if(window["UC"]["PULLEDTimeout"]<=0)window["UC"]["PULLEDTimeout"]=setTimeout(markReadComments,1e3)});window.markReadComments=function(){var e=BX.GetWindowScrollPos();if(e.scrollTop!=window["UC"]["PULLEDScreenData"]["scrollTop"]){window["UC"]["PULLEDScreenData"]["time"]=(new Date).getTime();window["UC"]["PULLEDScreenData"]["scrollTop"]=e.scrollTop;window["UC"]["PULLEDScreenData"]["checked"]=false}else if(!window["UC"]["PULLEDScreenData"]["checked"]&&(new Date).getTime()-window["UC"]["PULLEDScreenData"]["time"]>3e3){window["UC"]["PULLEDScreenData"]["time"]=(new Date).getTime();window["UC"]["PULLEDScreenData"]["checked"]=true;var t=0,i=BX.GetWindowInnerSize(),s=[],o,a,n;for(var r=0;r<window["UC"]["PULLED"].length;r++){o=BX("record-"+window["UC"]["PULLED"][r].join("-")+"-cover");a=BX.pos(o);if(a.top>=e.scrollTop&&a.top<=e.scrollTop+i.innerHeight-20){BX.removeClass(o,"comment-new-answer");n=BX.findChild(o,{className:"feed-com-block"},true,false);BX.removeClass(n,"feed-com-block-pointer-to-new feed-com-block-new");BX.addClass(n,"feed-com-block-read");t++}else{s.push(window["UC"]["PULLED"][r])}}window["UC"]["PULLED"]=s;if(t>0)BX.onCustomEvent(window,"onCounterDecrement",[t])}if(window["UC"]["PULLED"].length>0)window["UC"]["PULLEDTimeout"]=setTimeout(markReadComments,1e3);else window["UC"]["PULLEDTimeout"]=0};var MPLQuote=function(e){this.params=e;this.id=e["id"];this.closeByEsc=!!e["closeByEsc"];this.autoHide=!!e["autoHide"];this.autoHideTimeout=!!e["autoHideTimeout"]?parseInt(e["autoHideTimeout"]):0;if(this.params.classEvents){for(var t in this.params.classEvents)if(this.params.classEvents.hasOwnProperty(t))BX.addCustomEvent(this,t,this.params.classEvents[t])}this.node=document.createElement("A");BX.adjust(this.node,{props:{id:this.id},style:{zIndex:BX.PopupWindow.getOption("popupZindex")+this.params.zIndex,position:"absolute",display:"none",top:"0px",left:"0px"},attrs:{className:"mpl-quote-block",href:"javascript:void(0);"},events:this.params.events});document.body.appendChild(this.node)};MPLQuote.prototype={show:function(e){var t=this.getPosition(this.node,e);BX.adjust(this.node,{style:{top:t.y+"px",left:t.x+"px",display:"block"}});BX.addClass(this.node,"mpl-quote-block-show");if(this.closeByEsc&&!this.isCloseByEscBinded){this.isCloseByEscBinded=BX.delegate(this._onKeyUp,this);BX.bind(document,"keyup",this.isCloseByEscBinded)}if(this.params.autoHide&&!this.isAutoHideBinded){setTimeout(BX.proxy(function(){BX.bind(this.node,"click",this.cancelBubble);this.isAutoHideBinded=BX.delegate(this.hide,this);BX.bind(document,"click",this.isAutoHideBinded)},this),0)}if(this.autoHideTimeout>0&&this.autoHideTimeoutInt<=0){if(!this.autoHideTimeoutBinded)this.autoHideTimeoutBinded=BX.delegate(this.hide,this);this.autoHideTimeoutInt=setTimeout(this.autoHideTimeoutBinded,this.autoHideTimeout)}},hide:function(e){if(!this.isShown())return;if(e&&!(BX.getEventButton(e)&BX.MSLEFT))return true;this.node.style.display="none";if(this.isCloseByEscBinded){BX.unbind(document,"keyup",this.isCloseByEscBinded);this.isCloseByEscBinded=false}if(this.autoHideTimeout>0){clearTimeout(this.autoHideTimeoutInt);this.autoHideTimeoutInt=0}setTimeout(BX.proxy(this._hide,this),0)},_hide:function(){BX.onCustomEvent(this,"onQuoteHide",[this]);if(this.params.autoHide&&this.isAutoHideBinded){BX.unbind(this.node,"click",this.cancelBubble);BX.unbind(document,"click",this.isAutoHideBinded);this.isAutoHideBinded=false}BX.remove(this.node)},getPosition:function(e,t){var i;if(t.pageX==null){var s=document.documentElement,o=document.body;var a=t.clientX+(s&&s.scrollLeft||o&&o.scrollLeft||0)-(s.clientLeft||0);var n=t.clientY+(s&&s.scrollTop||o&&o.scrollTop||0)-(s.clientTop||0);i={x:a,y:n}}else{i={x:t.pageX,y:t.pageY}}return{x:i.x+5,y:i.y-16}},isShown:function(){return this.node.style.display=="block"},cancelBubble:function(e){if(!e)e=window.event;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true},_onKeyUp:function(e){e=e||window.event;if(e.keyCode==27)this.hide(e)}};window.mplCheckForQuote=function(e,t,i,s){e=document.all?window.event:e;var o="",a,n=null;if(window.getSelection){a=window.getSelection();o=a.toString()}else if(document.selection){a=document.selection;o=a.createRange().text}if(o!=""){var r=BX.findParent(a.focusNode,{tagName:t.tagName,className:t.className},t),d=BX.findParent(a.anchorNode,{tagName:t.tagName,className:t.className},t);if(r!=d||r!=t){o=""}else{if(!!s&&BX(s,true)){var l=BX(s,true);if(!!l&&l.hasAttribute("bx-post-author-id")){n={id:parseInt(l.getAttribute("bx-post-author-id")),name:l.innerHTML}}}}}if(o!=""){BX.onCustomEvent(window,"onQuote"+i,[e,{text:o,author:n}]);return true}return false};window.mplReplaceUserPath=function(e){if(typeof e!="string"||e.length<=0){return""}if(BX("MPL_IS_EXTRANET_SITE")=="Y"){e=e.replace("/company/personal/user/","/extranet/contacts/personal/user/")}else{e=e.replace("/extranet/contacts/personal/user/","/company/personal/user/")}e=e.replace(new RegExp("[\\w/]*/mobile/users/\\?user_id=(\\d+)","igm"),BX("MPL_IS_EXTRANET_SITE")=="Y"?"/extranet/contacts/personal/user/$1/":"/company/personal/user/$1/");return e}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:99:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_form.min.js?145227743113600";s:6:"source";s:79:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_form.js";s:3:"min";s:83:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_form.min.js";s:3:"map";s:83:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_form.map.js";}"*/
(function(e){e["UC"]=!!e["UC"]?e["UC"]:{};if(!!e["FCForm"])return;e.FCForm=function(t){this.url="";this.lhe="";this.entitiesId={};this.form=BX(t["formId"]);this.handler=LHEPostForm.getHandler(t["editorId"]);this.editorName=t["editorName"];this.editorId=t["editorId"];this.windowEvents={OnUCUnlinkForm:BX.delegate(function(t){if(!!t&&!!this.entitiesId[t]){var i={},s=true;for(var n in this.entitiesId){if(this.entitiesId.hasOwnProperty(n)&&n!=t){s=false;i[n]=this.entitiesId[n]}}this.entitiesId=i;if(s&&!!this.windowEvents){for(n in this.windowEvents){if(this.windowEvents.hasOwnProperty(n)&&n)BX.removeCustomEvent(e,n,this.windowEvents[n])}this.windowEventsSet=false}}},this),OnUCUserQuote:BX.delegate(function(e,t,i,s,n){if(this.entitiesId[e]){if(!this._checkTextSafety([e,0],s))return;this.show([e,0]);if(n!==true){this.handler.exec(this.windowEvents.OnUCUserQuote,[e,t,i,s,true]);return}else if(!this.handler.oEditor.toolbar.controls.Quote){BX.DoNothing()}else if(!t&&!i){this.handler.oEditor.action.Exec("quote")}else{i=BX.util.htmlspecialchars(i);if(this.handler.oEditor.GetViewMode()=="wysiwyg"){i=i.replace(/\n/g,"<br/>");if(t){if(t.id>0){t='<span id="'+this.handler.oEditor.SetBxTag(false,{tag:"postuser",params:{value:t.id}})+'" class="bxhtmled-metion">'+t.name.replace(/</gi,"&lt;").replace(/>/gi,"&gt;")+"</span>"}else{t="<span>"+t.name.replace(/</gi,"&lt;").replace(/>/gi,"&gt;")+"</span>"}t=t!==""?t+BX.message("MPL_HAVE_WRITTEN")+"<br/>":"";i=t+i}}else if(this.handler.oEditor.bbCode){if(t){if(t.id>0){t="[USER="+t.id+"]"+t.name+"[/USER]"}else{t=t.name}t=t!==""?t+BX.message("MPL_HAVE_WRITTEN")+"\n":"";i=t+i}}this.handler.oEditor.action.actions.quote.setExternalSelection(i);this.handler.oEditor.action.Exec("quote")}}},this),OnUCUserReply:BX.delegate(function(t,i,s,n){if(!this._checkTextSafety([t,0],n))return;if(this.entitiesId[t]){this.show([t,0]);if(i>0){this.handler.exec(e.BxInsertMention,[{item:{entityId:i,name:s},type:"users",formID:this.form.id,editorId:this.editorId,bNeedComa:true,insertHtml:true}])}}},this),OnUCAfterRecordEdit:BX.delegate(function(e,t,i,s){if(!!this.entitiesId[e]){if(s==="EDIT"){this.show([e,t],i["messageBBCode"],i["messageFields"]);this.editing=true}else{this.hide(true);if(!!i["errorMessage"]){this.id=[e,t];this.showError(i["errorMessage"])}else if(!!i["okMessage"]){this.id=[e,t];this.showNote(i["okMessage"]);this.id=null}}}},this),OnUCUsersAreWriting:BX.delegate(function(e,t,i,s,n){if(!!this.entitiesId[e]){this.showAnswering([e,0],t,i,s,n)}},this),OnUCRecordHaveDrawn:BX.delegate(function(e,t){if(!!this.entitiesId[e]){var i=parseInt(!!t&&!!t["messageFields"]&&!!t["messageFields"]["AUTHOR"]&&!!t["messageFields"]["AUTHOR"]["ID"]?t["messageFields"]["AUTHOR"]["ID"]:0);if(i>0)this.hideAnswering([e,0],i)}},this)};this.linkEntity(t["entitiesId"]);BX.remove(BX("micro"+t["editorName"]));BX.remove(BX("micro"+t["editorId"]));this.eventNode=this.handler.eventNode;if(this.eventNode){BX.addCustomEvent(this.eventNode,"OnBeforeHideLHE",BX.delegate(function(){if(!!this.id&&!!BX("uc-writing-"+this.form.id+"-"+this.id[0]))BX.hide(BX("uc-writing-"+this.form.id+"-"+this.id[0]))},this));BX.addCustomEvent(this.eventNode,"OnAfterHideLHE",BX.delegate(function(){var t=this._getPlacehoder();if(t){BX.hide(t)}t=this._getSwitcher();if(t){BX.show(t);BX.focus(t.firstChild)}this.__content_length=0;if(!!this.id){BX.onCustomEvent(this.eventNode,"OnUCFormAfterHide",[this]);this.showAnswering(this.id)}clearTimeout(this._checkWriteTimeout);this._checkWriteTimeout=0;this.clear();BX.onCustomEvent(e,"OnUCFeedChanged",[this.id])},this));BX.addCustomEvent(this.eventNode,"OnBeforeShowLHE",BX.delegate(function(){var e=this._getPlacehoder();if(e){BX.show(e)}e=this._getSwitcher();if(e){BX.hide(e)}if(!!this.id&&!!BX("uc-writing-"+this.form.id+"-"+this.id[0]))BX.hide(BX("uc-writing-"+this.form.id+"-"+this.id[0]))},this));BX.addCustomEvent(this.eventNode,"OnAfterShowLHE",BX.delegate(function(t,i){this._checkWrite(t,i);if(!!this.id)this.showAnswering(this.id);BX.onCustomEvent(e,"OnUCFeedChanged",[this.id])},this));BX.addCustomEvent(this.eventNode,"OnClickSubmit",BX.delegate(this.submit,this));BX.addCustomEvent(this.eventNode,"OnClickCancel",BX.delegate(this.cancel,this));BX.onCustomEvent(this.eventNode,"OnUCFormInit",[this])}this.id=null};e.FCForm.prototype={linkEntity:function(t){if(!!t){for(var i in t){if(t.hasOwnProperty(i)){BX.onCustomEvent(e,"OnUCUnlinkForm",[i]);this.entitiesId[i]=t[i]}}}if(!this.windowEventsSet&&!!this.entitiesId){BX.addCustomEvent(e,"OnUCUnlinkForm",this.windowEvents.OnUCUnlinkForm);BX.addCustomEvent(e,"OnUCUserReply",this.windowEvents.OnUCUserReply);BX.addCustomEvent(e,"OnUCUserQuote",this.windowEvents.OnUCUserQuote);BX.addCustomEvent(e,"OnUCAfterRecordEdit",this.windowEvents.OnUCAfterRecordEdit);BX.addCustomEvent(e,"OnUCUsersAreWriting",this.windowEvents.OnUCUsersAreWriting);BX.addCustomEvent(e,"OnUCRecordHaveDrawn",this.windowEvents.OnUCRecordHaveDrawn);this.windowEventsSet=true}},_checkTextSafety:function(e,t){if(t===true){t=e;if(this.id&&this.id.join("-")!=e.join("-")&&this.handler.editorIsLoaded&&this.handler.oEditor.IsContentChanged())return confirm(BX.message("MPL_SAFE_EDIT"));return true}return t===false},_checkWrite:function(t,i){if(this.handler.editorIsLoaded&&this._checkWriteTimeout!==false){this.__content_length=this.__content_length>0?this.__content_length:0;var s=this.handler.oEditor.GetContent(),n=BX.delegate(function(){this._checkWrite(t,i)},this),o=2e3;if(s.length>=4&&this.__content_length!=s.length&&!!this.id){BX.onCustomEvent(e,"OnUCUserIsWriting",[this.id[0],this.id[1]]);o=3e4}this._checkWriteTimeout=setTimeout(n,o);this.__content_length=s.length}},_getPlacehoder:function(e){e=!!e?e:this.id;return!!e?BX("record-"+e.join("-")+"-placeholder"):null},_getSwitcher:function(e){e=!!e?e:this.id;return!!e?BX("record-"+e[0]+"-switcher"):null},hide:function(e){if(this.eventNode.style.display!="none"){BX.onCustomEvent(this.eventNode,"OnShowLHE",[e===true?false:"hide"])}},clear:function(){this.editing=false;var e=this._getPlacehoder();if(!!e)BX.hide(e);var t=BX.findChildren(e,{tagName:"DIV",className:"feed-add-error"},true);if(!!t){e=t.pop();do{BX.remove(e)}while((e=t.pop())&&e)}BX.onCustomEvent(this.eventNode,"OnUCFormClear",[this]);var i=BX.findChild(this.form,{className:"wduf-placeholder-tbody"},true,false);if(i!==null&&typeof i!="undefined")BX.cleanNode(i,false);i=BX.findChild(this.form,{className:"wduf-selectdialog"},true,false);if(i!==null&&typeof i!="undefined")BX.hide(i);i=BX.findChild(this.form,{className:"file-placeholder-tbody"},true,false);if(i!==null&&typeof i!="undefined")BX.cleanNode(i,false);this.id=null},show:function(e,t,i){if(this.id&&!!e&&this.id.join("-")==e.join("-"))return true;else this.hide(true);this.id=e;var s=this._getPlacehoder();s.appendChild(this.form);BX.onCustomEvent(this.eventNode,"OnUCFormBeforeShow",[this,t,i]);BX.onCustomEvent(this.eventNode,"OnShowLHE",["show"]);BX.onCustomEvent(this.eventNode,"OnUCFormAfterShow",[this,t,i]);return true},submit:function(){if(this.busy===true)return"busy";var t=this.handler.editorIsLoaded?this.handler.oEditor.GetContent():"";if(!t){this.showError(BX.message("JERROR_NO_MESSAGE"));return false}this.showWait();this.busy=true;var i={};e.convertFormToArray(this.form,i);i["REVIEW_TEXT"]=t;i["NOREDIRECT"]="Y";i["MODE"]="RECORD";i["AJAX_POST"]="Y";i["id"]=this.id;if(this.editing===true){i["REVIEW_ACTION"]="EDIT";i["FILTER"]={ID:this.id[1]}}BX.onCustomEvent(this.eventNode,"OnUCFormSubmit",[this,i]);BX.onCustomEvent(e,"OnUCFormSubmit",[this.id[0],this.id[1],this,i]);BX.ajax({method:"POST",url:this.form.action,data:i,dataType:"json",onsuccess:BX.proxy(function(t){this.closeWait();var i=t,s=this.id[0];BX.onCustomEvent(this.eventNode,"OnUCFormResponse",[this,t]);if(!!this.OnUCFormResponseData)t=this.OnUCFormResponseData;if(!!t){if(!!t["errorMessage"]){this.showError(t["errorMessage"])}else{BX.onCustomEvent(e,"OnUCAfterRecordAdd",[this.id[0],t,i]);this.hide(true)}}this.busy=false;BX.onCustomEvent(e,"OnUCFormResponse",[s,t["messageId"],this,t])},this),onfailure:BX.delegate(function(){this.closeWait();this.busy=false;BX.onCustomEvent(e,"OnUCFormResponse",[this.id[0],this.id[1],this,[]])},this)})},cancel:function(){},showError:function(e){if(!e)return;var t=this._getPlacehoder(),i=BX.findChildren(t,{tagName:"DIV",className:"feed-add-error"},true);if(!!i){var s=i.pop();do{BX.remove(s);BX.remove(s)}while((s=i.pop())&&!!s)}t.insertBefore(BX.create("div",{attrs:{"class":"feed-add-error"},html:'<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>'+"<b>"+BX.message("FC_ERROR")+"</b><br />"+e+"</span>"}),t.firstChild);BX.show(t)},showNote:function(e){if(!e)return;var t=this._getPlacehoder(),i=BX.findChildren(t,{tagName:"DIV",className:"feed-add-successfully"},true),s=null;if(!!i){while((s=i.pop())&&!!s){BX.remove(s)}}t.insertBefore(BX.create("div",{attrs:{"class":"feed-add-successfully"},html:'<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>'+e+"</span>"}),t.firstChild);BX.show(t)},showWait:function(){var e=BX("lhe_button_submit_"+this.form.id);if(!!e){BX.addClass(e,"feed-add-button-load");BX.addClass(e,"feed-add-button-press");BX.defer(function(){e.disabled=true})()}},closeWait:function(){var e=BX("lhe_button_submit_"+this.form.id);if(!!e){e.disabled=false;BX.removeClass(e,"feed-add-button-press");BX.removeClass(e,"feed-add-button-load")}},objAnswering:null,showAnswering:function(e,t,i,s,n){if(!(t>0))return false;var o="uc-writing-"+this.form.id+"-"+e[0],r=BX(o+"-area"),a=this._getSwitcher(e),d=BX.localStorage.get("ucAnsweringStorage");d=!!d?d:{};if(!r&&a){r=BX.create("DIV",{attrs:{id:o+"-area",className:"feed-com-writers"},html:'<div id="'+o+'-users" class="feed-com-writers-wrap"></div><div class="feed-com-writers-pen"></div>'});a.appendChild(r)}if(!!r){if(t>0){if(!n){d["userId"+t]={id:e[0],userId:t,name:i,avatar:s,time:new Date};BX.localStorage.set("ucAnsweringStorage",d,3e3)}if(!BX(o+"-user-"+t)){BX.adjust(BX(o+"-users"),{children:[BX.create("DIV",{attrs:{className:"feed-com-avatar",id:o+"-user-"+t,title:i},children:[BX.create("IMG",{attrs:{src:s&&s.length>0?s:"/bitrix/images/1.gif"}})]})]})}}if(BX(o+"-users").childNodes.length>0){if(BX(r.parentNode).style.display=="none"){var h=BX("lhe_buttons_"+this.form.id);if(!h||h.style.display=="none")h=this.form;h.appendChild(r)}else if(r.parentNode!=a)a.appendChild(r);if(this.objAnswering&&this.objAnswering.name!="show")this.objAnswering.stop();if(!this.objAnswering||this.objAnswering.name!="show"){r.style.display="inline-block";this.objAnswering=new BX["easing"]({duration:500,start:{opacity:0},finish:{opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){r.style.opacity=e.opacity/100}});this.objAnswering.name="show";this.objAnswering.animate()}var l=setTimeout(BX.delegate(function(){this.hideAnswering(e,t)},this),!!n?n:40500);if(BX(o+"-user-"+t)){clearTimeout(BX(o+"-user-"+t).getAttribute("bx-check-timeout"));BX(o+"-user-"+t).setAttribute("bx-check-timeout",l+"")}}}},hideAnswering:function(e,t){var i="uc-writing-"+this.form.id+"-"+e[0],s=BX(i+"-area"),n=BX(i+"-user-"+t,false);if(n&&s){if(BX(i+"-users").childNodes.length>1){new BX["easing"]({duration:500,start:{opacity:100},finish:{opacity:0},transition:BX["easing"].makeEaseOut(BX["easing"].transitions.quart),step:function(e){n.style.opacity=e.opacity/100},complete:function(){if(!!n&&!!n.parentNode)n.parentNode.removeChild(n)}}).animate()}else{if(this.objAnswering&&this.objAnswering.name!="hide")this.objAnswering.stop();if(!this.objAnswering||this.objAnswering.name!="hide"){this.objAnswering=new BX["easing"]({duration:500,start:{opacity:100},finish:{opacity:0},transition:BX["easing"].makeEaseOut(BX.easing.transitions.quart),step:function(e){s.style.opacity=e.opacity/100},complete:function(){s.style.display="none";if(!!n&&!!n.parentNode)n.parentNode.removeChild(n)}});this.objAnswering.name="hide";this.objAnswering.animate()}}}}};e.convertFormToArray=function(e,t){t=!!t?t:[];if(!!e){var i,s=[],n=e.elements.length;for(i=0;i<n;i++){var o=e.elements[i];if(o.disabled)continue;switch(o.type.toLowerCase()){case"text":case"textarea":case"password":case"hidden":case"select-one":s.push({name:o.name,value:o.value});break;case"radio":case"checkbox":if(o.checked)s.push({name:o.name,value:o.value});break;case"select-multiple":for(var r=0;r<o.options.length;r++){if(o.options[r].selected)s.push({name:o.name,value:o.options[r].value})}break;default:break}}var a=t;i=0;while(i<s.length){var d=s[i].name.indexOf("[");if(d==-1){a[s[i].name]=s[i].value;a=t;i++}else{var h=s[i].name.substring(0,d);var l=s[i].name.substring(d+1);if(!a[h])a[h]=[];var f=l.indexOf("]");if(f==-1){a=t;i++}else if(f===0){a=a[h];s[i].name=""+a.length}else{a=a[h];s[i].name=l.substring(0,f)+l.substring(f+1)}}}}return t};e.FCForm.onUCUsersAreWriting=function(){BX.ready(function(){var t=null,i=null,s=BX.localStorage.get("ucAnsweringStorage");if(!!s){for(var n in s){if(s.hasOwnProperty(n)){t=s[n];if(!!t&&t.userId>0){i=new Date-t.time;if(i<3e4){BX.onCustomEvent(e,"OnUCUsersAreWriting",[t.id,t.userId,t.name,t.avatar,i])}}}}}})};e["fRefreshCaptcha"]=function(e){var t=null,i=BX.findChild(e,{attr:{name:"captcha_code"}},true),s=BX.findChild(e,{attr:{name:"captcha_word"}},true),n=BX.findChild(e,{className:"comments-reply-field-captcha-image"},true);if(n)t=BX.findChild(n,{tag:"img"});if(i&&s&&t){s.value="";BX.ajax.getCaptcha(function(e){i.value=e["captcha_sid"];t.src="/bitrix/tools/captcha.php?captcha_code="+e["captcha_sid"]})}}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:96:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_im.min.js?14522774318184";s:6:"source";s:77:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_im.js";s:3:"min";s:81:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_im.min.js";s:3:"map";s:81:"/bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_im.map.js";}"*/
(function(t){t["UC"]=!!t["UC"]?t["UC"]:{};if(!!t["UC"]["Informer"])return;t.SPC=function(){this.stack=[];this.stackTimeout=null;this.stackPopup={};this.stackPopupTimeout={};this.stackPopupTimeout2={};this.stackPopupId=0;this.stackOverflow=false;this.notifyShow=0;this.notifyHideTime=5e3;this.notifyHeightCurrent=10;this.notifyHeightMax=0;this.notifyGarbageTimeout=null;this.notifyAutoHide=true;this.notifyAutoHideTimeout=null};SPC.prototype.add=function(t){if(typeof t!="object"||!t.html)return false;if(BX.type.isDomNode(t.html))t.html=t.html.outerHTML;this.stack.push(t);if(!this.stackOverflow)this.setShowTimer(300);return true};SPC.prototype.remove=function(t){delete this.stack[t]};SPC.prototype.show=function(){this.notifyHeightMax=document.body.offsetHeight;var t=BX.GetWindowInnerSize();for(var e=0;e<this.stack.length;e++){if(typeof this.stack[e]=="undefined")continue;var i=new BX.PopupWindow("bx-sbpc-notify-flash-"+this.stackPopupId,{top:0,left:0},{lightShadow:true,zIndex:200,events:{onPopupClose:BX.delegate(function(){BX.proxy_context.popupContainer.style.opacity=0;this.notifyShow--;this.notifyHeightCurrent-=BX.proxy_context.popupContainer.offsetHeight+10;this.stackOverflow=false;setTimeout(BX.delegate(function(){this.destroy()},BX.proxy_context),1500)},this),onPopupDestroy:BX.delegate(function(){BX.unbindAll(BX.findChild(BX.proxy_context.popupContainer,{className:"bx-spbc-notifier-item-delete"},true));BX.unbindAll(BX.proxy_context.popupContainer);delete this.stackPopup[BX.proxy_context.uniquePopupId];delete this.stackPopupTimeout[BX.proxy_context.uniquePopupId];delete this.stackPopupTimeout2[BX.proxy_context.uniquePopupId]},this)},bindOnResize:false,content:BX.create("div",{props:{className:"bx-notifyManager-item-sbpc"},html:this.stack[e].html})});i.notifyParams=this.stack[e];i.notifyParams.id=e;i.show();BX.removeClass(i.popupContainer.firstChild,"popup-window");if(BX("workarea")){var o=BX.pos(BX("workarea"));i.popupContainer.style.left=o.left-223+"px"}else i.popupContainer.style.left=10+"px";i.popupContainer.style.opacity=0;if(this.notifyHeightMax<this.notifyHeightCurrent+i.popupContainer.offsetHeight+10){if(this.notifyShow>0){i.destroy();this.stackOverflow=true;break}}BX.addClass(i.popupContainer,"bx-notifyManager-animation-spbc");new BX.easing({duration:500,start:{opacity:0},finish:{opacity:100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){i.popupContainer.style.opacity=t.opacity/100}}).animate();i.popupContainer.style.top=t.innerHeight-this.notifyHeightCurrent-i.popupContainer.offsetHeight-10+"px";this.notifyHeightCurrent=this.notifyHeightCurrent+i.popupContainer.offsetHeight+10;this.stackPopupId++;this.notifyShow++;this.remove(e);this.stackPopupTimeout[i.uniquePopupId]=null;BX.bind(i.popupContainer,"mouseover",BX.delegate(function(){this.clearAutoHide()},this));BX.bind(i.popupContainer,"mouseout",BX.delegate(function(){this.setAutoHide(this.notifyHideTime/2)},this));BX.bind(i.popupContainer,"contextmenu",BX.delegate(function(t){if(this.stackPopup[BX.proxy_context.id].notifyParams.tag)this.closeByTag(this.stackPopup[BX.proxy_context.id].notifyParams.tag);else this.stackPopup[BX.proxy_context.id].close();return BX.PreventDefault(t)},this));var s=BX.findChildren(i.popupContainer,{tagName:"a"},true);for(var n=0;n<s.length;n++){if(s[n].href!="#")s[n].target="_blank"}BX.bind(BX.findChild(i.popupContainer,{className:"bx-spbc-notifier-item-delete"},true),"click",BX.delegate(function(t){var e=BX.proxy_context.parentNode.parentNode.parentNode.parentNode.id.replace("popup-window-content-","");if(this.stackPopup[e].notifyParams.close)this.stackPopup[e].notifyParams.close(this.stackPopup[e]);this.stackPopup[e].close();if(this.notifyAutoHide==false){this.clearAutoHide();this.setAutoHide(this.notifyHideTime/2)}return BX.PreventDefault(t)},this));if(i.notifyParams.click){i.popupContainer.style.cursor="pointer";BX.bind(i.popupContainer,"click",BX.delegate(function(t){this.notifyParams.click(this);return BX.PreventDefault(t)},i))}this.stackPopup[i.uniquePopupId]=i}if(this.stack.length>0){this.clearAutoHide(true);this.setAutoHide(this.notifyHideTime)}this.garbage()};SPC.prototype.closeByTag=function(t){for(var e=0;e<this.stack.length;e++){if(typeof this.stack[e]!="undefined"&&this.stack[e].tag==t){delete this.stack[e]}}for(e in this.stackPopup){if(this.stackPopup.hasOwnProperty(e))if(this.stackPopup[e].notifyParams.tag==t)this.stackPopup[e].close()}};SPC.prototype.setShowTimer=function(t){clearTimeout(this.stackTimeout);this.stackTimeout=setTimeout(BX.delegate(this.show,this),t)};SPC.prototype.setAutoHide=function(t){this.notifyAutoHide=true;clearTimeout(this.notifyAutoHideTimeout);this.notifyAutoHideTimeout=setTimeout(BX.delegate(function(){for(var e in this.stackPopupTimeout){if(this.stackPopupTimeout.hasOwnProperty(e)){this.stackPopupTimeout[e]=setTimeout(BX.delegate(function(){this.close()},this.stackPopup[e]),t-1e3);this.stackPopupTimeout2[e]=setTimeout(BX.delegate(function(){this.setShowTimer(300)},this),t-700)}}},this),1e3)};SPC.prototype.clearAutoHide=function(t){clearTimeout(this.notifyGarbageTimeout);this.notifyAutoHide=false;t=t==true;var e;if(t){clearTimeout(this.stackTimeout);for(e in this.stackPopupTimeout){if(this.stackPopupTimeout.hasOwnProperty(e)){clearTimeout(this.stackPopupTimeout[e]);clearTimeout(this.stackPopupTimeout2[e])}}}else{clearTimeout(this.notifyAutoHideTimeout);this.notifyAutoHideTimeout=setTimeout(BX.delegate(function(){clearTimeout(this.stackTimeout);for(e in this.stackPopupTimeout){if(this.stackPopupTimeout.hasOwnProperty(e)){clearTimeout(this.stackPopupTimeout[e]);clearTimeout(this.stackPopupTimeout2[e])}}},this),300)}};SPC.prototype.garbage=function(){clearTimeout(this.notifyGarbageTimeout);this.notifyGarbageTimeout=setTimeout(BX.delegate(function(){var t=[];for(var e=0;e<this.stack.length;e++){if(typeof this.stack[e]!="undefined")t.push(this.stack[e])}this.stack=t},this),1e4)};SPC.prototype.check=function(e,i,o,s){if(e[1]<=0||!t["UC"]["Informer"])return;var n=/(\d+)/g.exec(e[0]),a=BX("record-"+e.join("-")+"-cover");n=!!n?parseInt(n):0;if(n<=0||!a)return false;else if(BX.util.in_array(n,t["UC"]["InformerTags"][o]))return true;t["UC"]["InformerTags"][o].push(n);var r=!!i&&!!i["messageFields"]?i["messageFields"]:false;if(!r)return;var p=BX.pos(a),u=BX.GetWindowScrollPos(),c=BX.GetWindowInnerSize();if(p.top<u.scrollTop||p.top>u.scrollTop+c.innerHeight-20){setTimeout(function(){if(parseInt(r["AUTHOR"]["ID"])!=parseInt(BX.message("USER_ID"))){var i=BX.create("div",{props:{className:"bx-spbc-notifier-item"},children:[BX.create("span",{props:{className:"bx-spbc-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-spbc-notifier-item-avatar"},children:[!!r["AUTHOR"]["AVATAR"]?BX.create("img",{props:{className:"bx-spbc-notifier-item-avatar-img"},attrs:{src:r["AUTHOR"]["AVATAR"]}}):""]}),BX.create("a",{attrs:{href:"#"},props:{className:"bx-spbc-notifier-item-delete"}}),BX.create("span",{props:{className:"bx-spbc-notifier-item-name"},html:r["AUTHOR"]["NAME"]}),BX.create("span",{props:{className:"bx-spbc-notifier-item-time"},html:r["POST_TIME"]}),BX.create("span",{props:{className:"bx-spbc-notifier-item-text"}}),BX.create("span",{props:{className:"bx-spbc-notifier-item-text2"},html:'"'+s+'"'})]})]}),o=BX.GetWindowScrollPos();t["UC"]["Informer"].add({html:i,tag:"im-record-"+e.join("-"),click:BX.delegate(function(){var i=BX.pos(BX("record-"+e.join("-")));new BX.easing({duration:500,start:{scroll:o.scrollTop},finish:{scroll:i.top-100},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(e){t.scrollTo(0,e.scroll)}}).animate()},this)})}},50)}};SPC.NativeNotify=function(){return t.webkitNotifications&&t.webkitNotifications.checkPermission()==0};t["UC"]["Informer"]=new SPC;t["UC"]["InformerTags"]={};SPC.notifyManagerShow=function(){BX.ready(function(){BX.addCustomEvent("onNotifyManagerShow",function(e){if(e.originalTag){var i=e.originalTag.lastIndexOf("|"),o=e.originalTag.substr(0,i);if(!!t["UC"]["InformerTags"][o]){var s=parseInt(e.originalTag.substr(i+1));t["UC"]["InformerTags"][o].push(s)}}})})}})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:94:"/bitrix/components/bitrix/intranet.bitrix24.banner/templates/.default/script.js?14522774561166";s:6:"source";s:79:"/bitrix/components/bitrix/intranet.bitrix24.banner/templates/.default/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
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
/* End */
;
; /* Start:"a:4:{s:4:"full";s:104:"/bitrix/templates/bitrix24/components/bitrix/socialnetwork.blog.blog/important/script.js?145227753218825";s:6:"source";s:88:"/bitrix/templates/bitrix24/components/bitrix/socialnetwork.blog.blog/important/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(window){
if (top.BSBBW)
	return true;

	function animation(message, main_block){
		if(!BX.browser.isPropertySupported('transform'))
			return false;

		function vendor(props){
			if(BX.browser.isPropertySupported(props))
				return BX.browser.isPropertySupported(props);
			else
				return false
		}

		function getPrefix() {
			var vendorPrefixes = ['moz','webkit', 'o', 'ms'],
				len = vendorPrefixes.length,
				vendor = '';

			while (len--)
				if ('transform' in document.body.style ){
					return vendor
				}else if((vendorPrefixes[len] + 'Transform') in document.body.style){
					vendor='-'+vendorPrefixes[len].toLowerCase()+'-';
				}
			return vendor;
		}

		var corner_gradient = BX.create('div',{
			props:{
				className:'anim-corner-gradient'
			}
		});

		var corner = BX.create('div', { props : { className:'anim-corner' }, children : [ corner_gradient ]}),
			corner_wrap = BX.create('div',{ props:{className:'anim-corner-wrap'}, children:[corner] }),
			distort_shadow = BX.create('div',{ props:{className:'block-distort-shadow-wrap'},
				children:[ BX.create('div',{ props:{className:'block-distort-shadow'} }) ] }),
			distort = BX.create('div', { props:{ className:'block-distort' }, children:[message,corner_wrap] }),
			main_wrap = BX.create('div',{ props:{className:'main-mes-wrap'}, children:[distort, distort_shadow] });

		main_block.appendChild(main_wrap);


		distort.style [vendor('transformOrigin')] = '180px 130px';

		distort.style[vendor('transform')] = 'rotate(42deg)';

		message.style[vendor('transformOrigin')] = '50% 100%';

		message.style[vendor('transformOrigin')] = '50% 100%';
		message.style[vendor('transform')] = 'rotate(-42deg)';

		corner_wrap.style[vendor('transform')] = 'rotate(-42deg)';


		var easing = new BX.easing({
			duration:100,
			start:{
				height:475,
				bottom:-182,
				left:-124,
				shadow_height:0,
				shadow_bottom:-74,
				gradient_height:0,
				gradient_width:0
			},
			finish:{
				height:342,
				bottom:-50,
				left:-72,
				shadow_height:130,
				shadow_bottom:-52,
				gradient_height:172,
				gradient_width:197
			},
			transition : BX.easing.transitions.linear(),
			step:function(state){
				distort.style.height = state.height + 'px';
				corner_wrap.style.left = state.left + 'px';
				corner_wrap.style.bottom = state.bottom + 'px';
				distort_shadow.style.height = state.shadow_height + 'px';
				distort_shadow.style.bottom = state.shadow_bottom + 'px';
				corner_gradient.style.height = state.gradient_height + 'px';
				corner_gradient.style.width = state.gradient_width + 'px';

			},
			complete:function(){

				var gradient_rotate;

				corner_wrap.style[vendor('transformOrigin')] = '62px 0';
				corner_wrap.style.left = -17 + 'px';
				corner_wrap.style.bottom = -183 + 'px';

				distort_shadow.style[vendor('transformOrigin')] = '28px 0';
				distort_shadow.style.left = '-28px';
				distort_shadow.style.bottom = '46px';

				distort.style[vendor('transformOrigin')] = '47px 100%';
				distort.style.top = -195+'px';
				distort.style.left = -46+'px';

				message.style[vendor('transformOrigin')] = '0 0';
				message.style.top = 337 + 'px';
				message.style.left = 41 + 'px';


				var easing_2 = new BX.easing({
					duration:200,
					start:{
						distort_rotate:42,
						shadow_rotate:42,
						shadow_skew:0,
						corner_rotate:-42,
						corner_height:180,
						corner_bottom:-183,
						message_rotate: -42,
						gradient_rotate:42
					},
					finish:{
						distort_rotate:34,
						shadow_rotate:34,
						shadow_skew:11,
						corner_rotate:-50,
						corner_height:251,
						corner_bottom:-248,
						message_rotate: -34,
						gradient_rotate:48
					},
					transition : BX.easing.transitions.linear(),

					step:function(state){

						distort.style[vendor('transform')] = 'rotate('+ state.distort_rotate + 'deg)';

						corner_wrap.style[vendor('transform')] = 'rotate('+ state.corner_rotate + 'deg)';
						corner_wrap.style.bottom = state.corner_bottom + 'px';

						corner.style.height = state.corner_height + 'px';

						distort_shadow.style[vendor('transform')] = 'rotate('+ state.shadow_rotate + 'deg)';

						message.style[vendor('transform')] = 'rotate('+ state.message_rotate + 'deg)';

						corner_gradient.style.height = state.corner_height + 'px';

						corner_gradient.style.backgroundImage = getPrefix()+'linear-gradient('+state.gradient_rotate+'deg, #ece297 42%, #e5d38e 57%, #f6e9a3 78%)';

					},
					complete:function(){

						corner.style[vendor('transformOrigin')] = '100% 0';
						corner.style.boxShadow = 'none';

						if(getPrefix() == '-webkit-') gradient_rotate = 24;
						else gradient_rotate = 67;

						var easing_3 = new BX.easing({
							duration:200,
							start:{
								distort_rotate:34,
								corner_rotate:-50,
								corner_width:260,
								corner_height:251,
								corner_skew:0,
								message_rotate:-34,
								shadow_rotate:34,
								shadow_skew:0,
								shadow_width:340,
								opacity:10,
								gradient_rotate:48,
								gradient_percent:57
							},
							finish:{
								distort_rotate:16,
								corner_rotate:-60,
								corner_width:236,
								corner_height:256,
								corner_skew:8,
								message_rotate:-16,
								shadow_rotate:16,
								shadow_skew:15,
								shadow_width:301,
								opacity:0,
								gradient_rotate:gradient_rotate,
								gradient_percent:50
							},
							transition:BX.easing.transitions.linear(),
							step:function(state){

								distort.style[vendor('transform')] = 'rotate('+ state.distort_rotate + 'deg)';
								distort.style.opacity = (state.opacity/10);

								corner_wrap.style[vendor('transform')] = 'rotate('+ state.corner_rotate + 'deg)';

								corner.style[vendor('transform')] = 'skew('+ state.corner_skew +'deg, 0deg)';
								corner.style.width = state.corner_width + 'px';
								corner.style.height = state.corner_height + 'px';

								corner_gradient.style.height = state.corner_height + 'px';

								message.style[vendor('transform')] = 'rotate('+ state.message_rotate + 'deg)';

								distort_shadow.style[vendor('transform')] = 'rotate('+ state.shadow_rotate + 'deg) skew('+ state.shadow_skew +'deg, 0)';
								distort_shadow.style.width = state.shadow_width + 'px';
								distort_shadow.style.opacity = (state.opacity/10);

								corner_gradient.style.backgroundImage = getPrefix()+'linear-gradient('+state.gradient_rotate+'deg, #ece297 42%, #e5d38e '+state.gradient_percent+'%, #f6e9a3 78%)';
							},
							complete:function(){
								main_wrap.style.display = 'none';
							}
						});
						easing_3.animate()
					}
				});
				easing_2.animate();
			}
		});
		easing.animate();
	}

top.BSBBW = function(params) {
	this.CID = params["CID"];
	this.controller = params["controller"];

	this.nodes = params["nodes"];
	this.tMessage = this.nodes['template'].innerHTML;

	this.url = params["url"];

	this.options = params["options"];
	this.post_info = params["post_info"];
	this.post_info['AJAX_POST'] = "Y";

	this.sended = false;
	this.active = false;
	this.inited = false;
	this.busy = false;
	this.userCounter = 0;

	this.inited = this.init(params);
	this.show();

	BX.addCustomEvent(this.controller, "onDataAppeared", BX.delegate(this.onDataAppeared, this));
	BX.addCustomEvent(this.controller, "onDataRanOut", BX.delegate(this.onDataRanOut, this));
	BX.addCustomEvent(this.controller, "onReachedLimit", BX.delegate(this.onReachedLimit, this));
	BX.addCustomEvent(this.controller, "onRequestSend", BX.delegate(this.showWait, this));
	BX.addCustomEvent(this.controller, "onResponseCame", BX.delegate(this.hideWait, this));
	BX.addCustomEvent(this.controller, "onResponseFailed", BX.delegate(this.hideWait, this));
	BX.addCustomEvent(window, "onImUpdateCounter", BX.delegate(this.onImUpdateCounter, this));
	BX.addCustomEvent("onPullEvent-main", BX.delegate(function(command,params){
		if (command == 'user_counter'
				&& params[BX.message('SITE_ID')]
				&& params[BX.message('SITE_ID')]["BLOG_POST_IMPORTANT"]
			)
		{
			this.onImUpdateCounter(params[BX.message('SITE_ID')]);
		}
	}, this));
	BX.addCustomEvent(window, 'onSonetLogCounterClear', BX.delegate(function(){this.onImUpdateCounter({"BLOG_POST_IMPORTANT" : 0});}, this));
	BX.addCustomEvent(window, 'onImportantPostRead', BX.delegate(this.onImportantPostRead, this));
}

top.BSBBW.prototype = {
	init : function(params) {
		this.page_settings = params["page_settings"];
		this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);

		this.limit = (this.page_settings["NavPageCount"] > 1 ? 3 : 0);
		this.current = 0;

		if (this.active)
			clearTimeout(this.active);
		this.active = false;

		this.data_id = {};
		this.data = params["data"];
		for (var ii in this.data)
			this.data_id['id' + this.data[ii]["id"]] = 'normal';

		if (this.data.length <= 0)
			BX.onCustomEvent(this.controller, "onDataRanOut");
		else
			BX.onCustomEvent(this.controller, "onDataAppeared");

		if (!this.inited)
		{
			BX.bind(this.nodes["right"], "click", BX.delegate(function(){this.onShiftPage("right")}, this));
			BX.bind(this.nodes["left"], "click", BX.delegate(function(){this.onShiftPage("left")}, this));
			BX.adjust(this.nodes["btn"], {attrs : {url : this.url}, events: {click : BX.delegate(this.onClickToRead, this)}});
		}
		return true;
	},
	show : function() {
		var
			message = this.tMessage,
			data = this.data[this.current];
		if (!data)
			return;
		for (var ii in data)
			message = message.replace("__" + ii + "__", data[ii]);
		this.nodes["leaf"].innerHTML = message;
		this.nodes["text"].innerHTML = message;
		this.nodes["counter"].innerHTML = (this.current + 1);
		this.nodes["total"].innerHTML = this.page_settings["NavRecordCount"];
		var btn = BX.findChild(this.nodes["text"], {"className" : "sidebar-imp-mess-text"}, true),
			avatar = BX.findChild(this.nodes["text"], {attribute : {"data-bx-author-avatar" : true}}, true);
		if (!!btn)
			BX.adjust(btn, {attrs : {url : this.url}, events: {click : BX.delegate(this.onClickToRead, this)}});
		if (data["author_avatar_style"] !== "" && !!avatar)
			BX.adjust(avatar, { style : {backgroundImage : data["author_avatar_style"], backgroundRepeat : "no-repeat", backgroundPosition : "center"}});
		var btn = BX.findChild(this.nodes["leaf"], {"className" : "sidebar-imp-mess-text"}, true),
			avatar = BX.findChild(this.nodes["leaf"], {attribute : {"data-bx-author-avatar" : true}}, true);
		if (data["author_avatar_style"] !== "" && !!avatar)
			BX.adjust(avatar, { style : {backgroundImage : data["author_avatar_style"], backgroundRepeat : "no-repeat", backgroundPosition : "center"}});
	},
	showWait : function() { /* showWait */ },
	hideWait : function() { /* hideWait */ },
	onImUpdateCounter : function(arCount)
	{
		var counter = parseInt(arCount['BLOG_POST_IMPORTANT']);
		if (this.userCounter != counter)
		{
			this.userCounter = counter;
			if (this.userCounter > 0)
			{
				this.startCheck();
			}
		}
	},
	startCheck : function()
	{
		if (this.busy !== true)
		{
			var request = this.post_info;
			request['sessid'] = BX.bitrix_sessid();
			request['page_settings'] = this.page_settings;
			request['page_settings']['iNumPage'] = null;
			BX.ajax({
				'method': 'POST',
				'processData': false,
				'url': this.url,
				'data': request,
				'onsuccess': BX.delegate(function(data){this.busy = false; this.parseResponse(data, true);}, this),
				'onfailure': BX.delegate(function(data){this.busy = false; this.onResponseFailed(data);}, this)
			});
		}
	},
	parseResponse : function(response, fromCheck)
	{
		var data = false, result = false;
		try{eval("result="+ response + ";");} catch(e) {}
		if (!result || !result.data || result.data.length <= 0)
			data = false;
		else if (fromCheck === true)
		{
			var dataNew = [], data = result.data;
			for (var ii in data )
			{
				if (typeof data[ii] == "object" && !this.data_id['id' + data[ii]["id"]])
				{
					dataNew.push(data[ii]);
				}
			}
			result.page_settings["NavRecordCount"] = parseInt(result.page_settings["NavRecordCount"]);
			this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);
			if (this.data.length > 0 &&
				dataNew.length == (result.page_settings["NavRecordCount"] - this.page_settings["NavRecordCount"]))
			{
				var d = dataNew.pop();
				while(!!d)
				{
					this.data_id['id' + d["id"]] = 'normal';
					this.data.unshift(d);
					this.current++;
					d = dataNew.pop();
				}
				this.page_settings["NavPageCount"] = result.page_settings["NavPageCount"];
				this.page_settings["NavRecordCount"] = result.page_settings["NavRecordCount"];
				this.show();
			}
			else
			{
				var current = 0, res = this.data[this.current];
				if (this.data.length > 0 && !!res)
				{
					for (var ii = 0; ii < data.length; ii++)
					{
						if (typeof data[ii] == "object" && data[ii]["id"] == res["id"])
						{
							current = ii;
							break;
						}
					}
				}
				this.init(result);
				this.current = current;
				this.show();
			}
		}
		else
		{
			this.page_settings["NavPageNomer"] = result.page_settings["NavPageNomer"];
			data = result.data;
			for (var ii in data )
			{
				if (typeof data[ii] == "object" && !this.data_id['id' + data[ii]["id"]])
				{
					this.data_id['id' + data[ii]["id"]] = 'normal';
					this.data.push(data[ii]);
				}
			}
			if (this.data.length > 0)
				BX.onCustomEvent(this.controller, "onDataAppeared");
		}
		return true;
	},
	onClickToRead : function(send)
	{
		var
			data = this.data[this.current], options = [], ii;
		for (ii in this.options)
			options.push({post_id : data["id"], name : this.options[ii]['name'], value:this.options[ii]['value']});
		var
			request = this.post_info;
		request['options'] = options;
		request['page_settings'] = this.page_settings;
		request['sessid'] = BX.bitrix_sessid();
		send = (send === false ? false : true);

		if (send)
		{
			BX.ajax({
				'method': 'POST',
				'processData': false,
				'url': this.url,
				'data': request,
				'onsuccess': BX.delegate(this.onAfterClickToRead, this),
				'onfailure': function(data){}
			});
		}
		this.onShiftPage('drop');
		animation(this.nodes["leaf"], this.nodes["block"]);
	},
	onAfterClickToRead : function ()
	{
	},
	onShiftPage : function(status)
	{
		if (this.active)
			clearTimeout(this.active);
		this.active = setTimeout(BX.delegate(function(){this.active=false;}, this), 120000);

		if (status == 'drop')
		{
			this.page_settings["NavRecordCount"]--;
			this.data_id['id' + this.data[this.current]["id"]] = 'readed';
			this.data = BX.util.deleteFromArray(this.data, this.current);
			if (!!this.data && this.data.length > 0)
			{
				this.current = this.current - 1;
				status = 'left';
			}
			else
			{
				BX.onCustomEvent(this.controller, "onDataRanOut");
				return;
			}
		}

		if (status == 'right')
		{
			if (this.current <= 0)
			{
				this.page_settings["NavRecordCount"] = parseInt(this.page_settings["NavRecordCount"]);
				if (this.data.length < this.page_settings["NavRecordCount"])
					this.current = 1;
				else
					this.current = this.data.length;
			}
			this.current = this.current - 1;
		}
		else
		{
			if (this.current >= (this.data.length - 1))
				this.current = 0;
			else
				this.current = this.current + 1;
		}
		if (this.limit > 0 && this.current >= (this.data.length - 1 - this.limit))
			BX.onCustomEvent(this.controller, "onReachedLimit");

		this.show();
	},
	onDataRanOut: function()
	{
		if ((!this.data || this.data.length <= 0) && this.controller.style.display != "none")
		{
			this.bodyAnimationheight = this.controller.offsetHeight;
			(this.bodyAnimation = new BX.easing({
				duration : 200,
				start : { height : this.controller.offsetHeight, opacity : 100},
				finish : { height : 0, opacity : 0},
				transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
				step : BX.delegate(function(state){
					BX.adjust(this.controller, {style:{height : state.height + 'px', opacity : (state.opacity/100)}});
				}, this),
				complete : BX.delegate(function(){
					this.controller.style.display = "none";
				}, this)
			})).animate();
		}
	},
	onDataAppeared: function()
	{
		if (!!this.data && this.data.length > 0 && this.controller.style.display == "none")
		{
			var height = (!!this.bodyAnimationheight ? this.bodyAnimationheight : 200);
			this.controller.style.display = "block";
			(this.bodyAnimation = new BX.easing({
				duration : 200,
				start : { height : 0, opacity : 0},
				finish : { height : height, opacity : 100},
				transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
				step : BX.delegate(function(state){
					BX.adjust(this.controller, {style:{height : state.height + 'px', opacity : (state.opacity/100)}});
				}, this),
				complete : BX.delegate(function(){
					BX.adjust(this.controller, {style:{display : "block", height : "auto", opacity : "auto"}});
				}, this)
			})).animate();
		}
	},
	onReachedLimit : function()
	{
		if (this.sended === true)
			return;

		var
			request = this.post_info,
			needToUnbind = false;

		this.page_settings["NavPageNomer"] = parseInt(this.page_settings["NavPageNomer"]);
		this.page_settings["NavPageCount"] = parseInt(this.page_settings["NavPageCount"]);

		if (this.page_settings["NavPageCount"] <= 1)
			needToUnbind = true;
		else if (this.page_settings["bDescPageNumbering"] == true)
		{
			if (this.page_settings["NavPageNomer"] > 1)
				this.page_settings["iNumPage"] = parseInt(this.page_settings["NavPageNomer"]) - 1;
			else
				needToUnbind = true;
		}
		else if (this.page_settings["NavPageNomer"] < this.page_settings["NavPageCount"])
			this.page_settings["iNumPage"] = parseInt(this.page_settings["NavPageNomer"]) + 1;
		else
			needToUnbind = true;
		if (needToUnbind === true)
		{
			BX.removeCustomEvent(this.controller, "onReachedLimit", BX.delegate(this.onReachedLimit, this));
			return true;
		}
		BX.onCustomEvent(this.controller, "onRequestSend");
		this.sended = true;
		request['page_settings'] = this.page_settings;
		request['sessid'] = BX.bitrix_sessid();
		BX.ajax({
			'method': 'POST',
			'processData': false,
			'url': this.url,
			'data': request,
			'onsuccess': BX.delegate(this.onResponseCame, this),
			'onfailure': BX.delegate(this.onResponseFailed, this)
		});
	},
	onResponseCame : function(data)
	{
		this.sended = false;
		BX.onCustomEvent(this.controller, "onResponseCame");
		this.parseResponse(data);
	},
	onResponseFailed : function(data)
	{
		this.sended = false;
		BX.onCustomEvent(this.controller, "onResponseFailed");
	},
	onImportantPostRead : function(postId, CID)
	{
		if (postId > 0)
		{
			for (var ii in this.data)
			{
				if (this.data[ii]["id"] == postId)
				{
					this.current = ii;
					this.onClickToRead((CID == this.CID));
					break;
				}
			}
		}
	}
}
})(window);
/* End */
;; /* /bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.min.js?145227747423971*/
; /* /bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.min.js?145227747215269*/
; /* /bitrix/components/bitrix/socialnetwork.blog.post.edit/templates/.default/script.min.js?145227747223587*/
; /* /bitrix/components/bitrix/main.post.form/templates/.default/script.min.js?145227743152683*/
; /* /bitrix/components/bitrix/system.field.edit/script.min.js?1452277431462*/
; /* /bitrix/components/bitrix/disk.uf.file/templates/.default/script.min.js?145227744627707*/
; /* /bitrix/components/bitrix/search.tags.input/templates/.default/script.js?145227746813020*/
; /* /bitrix/components/bitrix/voting.vote.edit/templates/.default/script.js?14522774806119*/
; /* /bitrix/components/bitrix/calendar.livefeed.edit/templates/.default/script.js?145227743719690*/
; /* /bitrix/components/bitrix/lists.live.feed/templates/.default/script.min.js?145227745921419*/
; /* /bitrix/components/bitrix/lists.live.feed/templates/.default/right.min.js?14522774593499*/
; /* /bitrix/templates/bitrix24/components/bitrix/socialnetwork.log.filter/.default/script.js?14522775327951*/
; /* /bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js?145227745720460*/
; /* /bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.min.js?14522774746107*/
; /* /bitrix/components/bitrix/socialnetwork.blog.post/templates/.default/script.min.js?145227747317963*/
; /* /bitrix/components/bitrix/socialnetwork.blog.post.comment/templates/.default/script.js?14522774735252*/
; /* /bitrix/components/bitrix/main.post.list/templates/.default/script.min.js?145227743127737*/
; /* /bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_form.min.js?145227743113600*/
; /* /bitrix/components/bitrix/main.post.list/templates/.default/scripts_for_im.min.js?14522774318184*/
; /* /bitrix/components/bitrix/intranet.bitrix24.banner/templates/.default/script.js?14522774561166*/
; /* /bitrix/templates/bitrix24/components/bitrix/socialnetwork.blog.blog/important/script.js?145227753218825*/

//# sourceMappingURL=page_live_feed_v2.map.js