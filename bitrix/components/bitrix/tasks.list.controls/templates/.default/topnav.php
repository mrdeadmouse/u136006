<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (
	($arParams['SHOW_SECTIONS_BAR'] === 'Y')
	|| ($arParams['SHOW_FILTER_BAR'] === 'Y')
	|| ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
)
{
	CUtil::InitJSCore(array('popup', 'tooltip'));

	?>
	<div class="task-main-wrap" id="task-main-wrap">
		<?php

		if ($arParams['SHOW_SECTIONS_BAR'] === 'Y')
		{
			?>
			<div class="tasks-top-menu-wrap">
				<div class="tasks-top-menu" id="task-menu-block">
					<?php
					if (isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON_ALT']) && $arParams['SECTION_URL_PREFIX'])
					{
						?><span class="tasks-top-item-wrap" id="tasks-menu-block-btn-back">
							<a class="tasks-top-item" 
								href="<?php echo $arParams['SECTION_URL_PREFIX']; ?>?F_CANCEL=N"
								onclick="
									return (function(){
										var prevUrl = document.referrer;
										var tasksListStr = '<?php echo CUtil::JSEscape($arParams['SECTION_URL_PREFIX']); ?>';

										this.blur();

										if (prevUrl)
										{
											var baseUrl = prevUrl.substring(0, prevUrl.indexOf('?'));

											if (baseUrl.indexOf(tasksListStr, baseUrl.length - tasksListStr.length) !== -1)
											{
												window.history.back();
												return false;
											}
										}
									})();
							">
								<span class="tasks-top-item-text"><span style="font-size: 19px;">&larr;</span> <?php echo GetMessage('TASKS_PANEL_TAB_BACK_TO_LIST'); ?></span>
						</a></span><?php
					}

					foreach ($arResult['VIEW_STATE']['ROLES'] as $roleCodename => $arRoleData)
					{
						$cls = '';
						$clsActive = '';
						switch ($roleCodename)
						{
							case 'VIEW_ROLE_RESPONSIBLE':
								$counterId = 'tasks-main-top-counter-my';
								$cls = 'tasks-icon-do';
							break;

							case 'VIEW_ROLE_ACCOMPLICE':
								$counterId = 'tasks-main-top-counter-accomplice';
								$cls = 'tasks-icon-help';
							break;

							case 'VIEW_ROLE_ORIGINATOR':
								$counterId = 'tasks-main-top-counter-originator';
								$cls = 'tasks-icon-delegate';
							break;

							case 'VIEW_ROLE_AUDITOR':
								$counterId = 'tasks-main-top-counter-auditor';
								$cls = 'tasks-icon-watch';
							break;
						}

						if (
							($arParams['MARK_ACTIVE_ROLE'] === 'Y')
							&& ($arRoleData['SELECTED'] === 'Y')
						)
						{
							if ($arResult['SELECTED_SECTION_NAME'] === 'VIEW_SECTION_ROLES')
								$clsActive = ' tasks-top-item-wrap-active';
						}

						$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES'][$roleCodename];
						
						if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
							$counter = $arResult['VIEW_COUNTERS']['ROLES'][$roleCodename]['TOTAL']['COUNTER'];
						else
							$counter = '';

						if ($counter < 0)
							$counter = 0;

						?><span class="tasks-top-item-wrap <?php echo $clsActive; ?>">
							<a class="tasks-top-item" href="<?php echo $href; ?>" onclick="this.blur();">
								<span class="tasks-top-item-icon <?php echo $cls; ?>"></span>
								<span class="tasks-top-item-text"><?php echo $arRoleData['TITLE']; ?></span>
								<?php
								if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
								{
									?><span
										id="<?php echo $counterId; ?>"
										class="tasks-top-item-counter"
										<?php
										if ($counter == 0)
											echo ' style="display:none;" '
										?>
										><?php echo $counter; ?></span><?php
								}
								?>
						</a></span><?php
					}

					?><span class="tasks-top-item-wrap <?php if ($arResult['MARK_SECTION_ALL'] === 'Y') echo ' tasks-top-item-wrap-active'; ?> tasks-top-item-wrap-all">
						<a class="tasks-top-item" href="<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_SECTION_ADVANCED_FILTER_HREF']; ?>">
							<span class="tasks-top-item-icon tasks-icon-all"></span>
							<span class="tasks-top-item-text"><?php
								echo GetMessage('TASKS_PANEL_TAB_ALL');
							?></span>
						</a></span><?php

					if ($arResult['SHOW_SECTION_PROJECTS'] == 'Y')
					{
						?><span class="tasks-top-item-wrap <?php if ($arParams['MARK_SECTION_PROJECTS'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>">
						<a class="tasks-top-item" 
							href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PROJECTS"], array());?>">
							<span class="tasks-top-item-icon tasks-icon-projects"></span>
							<span class="tasks-top-item-text"><?php
								echo GetMessage('TASKS_PANEL_TAB_PROJECTS');
							?></span>
						</a></span><?php
					}

					if ($arResult['SHOW_SECTION_MANAGE'] == 'Y')
					{
						?><span class="tasks-top-item-wrap <?php if ($arParams['MARK_SECTION_MANAGE'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>">
						<a class="tasks-top-item" 
							href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DEPARTMENTS"], array());?>"
							>
							<span class="tasks-top-item-icon tasks-icon-lead"></span>
							<span class="tasks-top-item-text"><?php
								echo GetMessage('TASKS_PANEL_TAB_MANAGE');
							?></span>
							<span 
								id="tasks-main-top-counter-manage"
								class="tasks-top-item-counter tasks-top-item-orange-counter"
								<?php if ($arResult['SECTION_MANAGE_COUNTER'] <= 0) echo ' style="display:none;" '; ?>
								><?php
									echo $arResult['SECTION_MANAGE_COUNTER'];
							?></span>
							<?php
							// <span class="tasks-top-item-counter">22</span>
							?>
						</a></span><?php
					}

					?><span class="tasks-top-item-wrap <?php if ($arParams['MARK_SECTION_REPORTS'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>">
						<a class="tasks-top-item" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>">
							<span class="tasks-top-item-icon tasks-icon-reports"></span>
							<span class="tasks-top-item-text"><?php echo GetMessage('TASKS_PANEL_TAB_REPORTS'); ?></span>
						</a></span>
				</div>
			</div>
			<script type="text/javascript">
				var more_btn_toggle = {
					state_btn:'hidden',
					menu_items_list:[],
					item_width:[],
					button_width:0,
					num_hidden_items:0,
					popup:null,
					params:{
						block:null,
						popup_items:[],
						popup_params:{},
						menu_item_class:'',
						button:null,
						events:null
					},

					init:function(params){

						this.params = params;

						this.set_menu_item_list();

						var my_style = this.params.button.currentStyle || window.getComputedStyle(this.params.button);

						var mLeft = parseInt(my_style.marginLeft) || 0;
						var mRight = parseInt(my_style.marginLeft) || 0;

						this.params.block.appendChild(this.params.button);

						this.button_width = this.params.button.offsetWidth + mLeft + mRight;

						this.params.block.appendChild(this.params.button);
						this.params.button.style.position = 'absolute';
						this.params.button.style.top = '-500px';

						var _this = this;

						BX.bind(window, 'resize', function(){

							_this.toggle_btn();

							if(_this.popup){
								_this.popup.popupWindow.close();
								_this.popup.popupWindow.destroy();
								_this.popup = null;
							}
						});

						BX.bind(this.params.button, 'click', function(){

							_this.show_popup();
						});

						setTimeout(function(){_this.toggle_btn();},0);
					},

					set_menu_item_list: function()
					{
						var style;
						this.menu_items_list = BX.findChildren(this.params.block, {className:this.params.menu_item_class}, false);

						for(var i = 0; i < this.menu_items_list.length; i++ )
						{
							style = this.menu_items_list[i].currentStyle || window.getComputedStyle(this.menu_items_list[i]);
							this.item_width.push(this.menu_items_list[i].offsetWidth + parseInt(style.marginLeft) + parseInt(style.marginRight));
						}
					},

					show_popup:function()
					{
						var popup_items = [];

						if(this.popup) {
							this.popup.popupWindow.destroy();
							this.popup = null;
						}

						for(var i=0; i<this.params.popup_items.length; i++){
							popup_items.push(this.params.popup_items[i])
						}

						popup_items.splice(0, (this.params.popup_items.length - this.num_hidden_items));

						var _this = this;

						if(!this.params.popup_params.events) {
							this.params.popup_params.events = {
								onPopupClose: function() {
									if(_this.params.button.classList)
										_this.params.button.classList.remove('bx-menu-btn-more-active');
									else BX.removeClass(_this.params.button, 'bx-menu-btn-more-active')
								}
							}
						}

						this.popup =  BX.PopupMenu.create(
								(this.params.popup_id + Math.random()),
								this.params.button,
								popup_items,
								this.params.popup_params
						);

						this.popup.popupWindow.show();

						BX.addClass(this.params.button, 'bx-menu-btn-more-active')
					},

					toggle_btn:function()
					{
						var item_top,
							last_visible_item,
							empty_space,
							coord_block,
							first_item_top,
							last_item_top,
							num_first_hidden = 0,
							coord_last_visible,
							num_hidden_items = 0,
							block_pad_right,
							last_visible_padding;

						if(this.state_btn == 'show')
						{
							this.params.block.removeChild(this.params.button);
							this.state_btn = 'hidden';
						}

						first_item_top = parseInt(this.menu_items_list[0].offsetTop);

						last_item_top = parseInt(this.menu_items_list[this.menu_items_list.length-1].offsetTop);

						if(first_item_top != last_item_top)
						{

							for(var i=this.menu_items_list.length-1; i>=0; i--)
							{
								item_top = parseInt(this.menu_items_list[i].offsetTop);

								last_visible_item = this.menu_items_list[i];

								if(first_item_top != item_top){

									num_hidden_items++;
								}
								else if(first_item_top == item_top){
									break
								}
							}

							coord_last_visible = last_visible_item.getBoundingClientRect();

							coord_block = this.params.block.getBoundingClientRect();

							block_pad_right = parseInt(BX.style(this.params.block, 'paddingRight')) || 0;

							last_visible_padding = parseInt(BX.style(last_visible_item, 'marginRight')) || 0;

							empty_space = Math.ceil((coord_block.right - block_pad_right) - (coord_last_visible.right + last_visible_padding));

							num_first_hidden = this.menu_items_list.length-1 - (num_hidden_items-1);

							if(empty_space < Math.ceil(this.button_width)){
								num_first_hidden--;
								num_hidden_items++
							}

							this.num_hidden_items = num_hidden_items;

							this.params.button.style.top = '';
							this.params.button.style.position = 'static';
							this.params.button.style.display = 'inline-block';
							this.params.block.insertBefore(this.params.button, this.menu_items_list[num_first_hidden]);
							this.state_btn = 'show';
						}
					}
				};

				more_btn_toggle.init({
					block:BX('task-menu-block'),
					menu_item_class:'tasks-top-item-wrap',
					button:BX.create('span', {
						props:{
							className:'tasks-top-more-wrap'
						},
						html:'<a href="#" class="tasks-top-item-more">\
								<span class="tasks-top-item-icon tasks-icon-more"></span>\
								<span class="tasks-top-item-text"><?php echo GetMessageJs('TASKS_PANEL_BTN_MORE'); ?></span>\
								<span class="tasks-top-item-arrow"></span>\
							</a>'
					}),
					popup_items:[
						{
							text : '<?php echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_RESPONSIBLE']['TITLE']; ?>', 
							className : "tasks-top-popup-item tasks-top-popup-do", 
							href : "<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_RESPONSIBLE']; ?>"
						},
						{
							text : '<?php echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_ACCOMPLICE']['TITLE']; ?>', 
							className : "tasks-top-popup-item tasks-top-popup-help", 
							href : "<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_ACCOMPLICE']; ?>"
						},
						{
							text : '<?php echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_ORIGINATOR']['TITLE']; ?>', 
							className : "tasks-top-popup-item tasks-top-popup-delegate", 
							href : "<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_ORIGINATOR']; ?>"
						},
						{
							text : '<?php echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_AUDITOR']['TITLE']; ?>', 
							className : "tasks-top-popup-item tasks-top-popup-watch", 
							href : "<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_AUDITOR']; ?>"
						},
						{
							text : '<?php echo GetMessageJs('TASKS_PANEL_TAB_ALL'); ?>', 
							className : "tasks-top-popup-item tasks-top-popup-all", 
							href : "<?php echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_SECTION_ADVANCED_FILTER_HREF']; ?>"
						},
						<?php
						if ($arResult['SHOW_SECTION_PROJECTS'] == 'Y')
						{
							?>
							{
								text : '<?php echo GetMessageJs('TASKS_PANEL_TAB_PROJECTS'); ?>', 
								className : "tasks-top-popup-item tasks-top-popup-projects", 
								href : "<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PROJECTS"], array());?>"
							},
							<?php
						}

						if ($arResult['SHOW_SECTION_MANAGE'] == 'Y')
						{
							?>
							{
								text : '<?php echo GetMessageJs('TASKS_PANEL_TAB_MANAGE'); ?>', 
								className : "tasks-top-popup-item tasks-top-popup-lead", 
								href : "<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DEPARTMENTS"], array());?>"
							},
							<?php
						}
						?>
						{
							text : '<?php echo GetMessageJs('TASK_TOOLBAR_FILTER_REPORTS'); ?>', 
							className : "tasks-top-popup-item tasks-top-popup-reports", 
							href : "<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>"
						}
					],
					popup_id:'tasks-top-more-popup',
					popup_params:{
						offsetTop:-2,
						offsetLeft:8,
						angle:{
							offset:17
						}
					}
				});


				BX.addCustomEvent("onPullEvent-main", BX.delegate(function(command, params){
					if (command == 'user_counter'
						&& params[BX.message('SITE_ID')]
					)
					{
						if (params[BX.message('SITE_ID')]['tasks_my'])
						{
							var node = BX('tasks-main-top-counter-my');
							var value = params[BX.message('SITE_ID')]['tasks_my'];

							if (value == 0)
								node.style.display = 'none';
							else
							{
								node.style.display = '';
								node.innerHTML = value;
							}
						}

						if (params[BX.message('SITE_ID')]['tasks_acc'])
						{
							var node = BX('tasks-main-top-counter-accomplice');
							var value = params[BX.message('SITE_ID')]['tasks_acc'];

							if (value == 0)
								node.style.display = 'none';
							else
							{
								node.style.display = '';
								node.innerHTML = value;
							}
						}

						if (params[BX.message('SITE_ID')]['tasks_au'])
						{
							var node = BX('tasks-main-top-counter-auditor');
							var value = params[BX.message('SITE_ID')]['tasks_au'];

							if (value == 0)
								node.style.display = 'none';
							else
							{
								node.style.display = '';
								node.innerHTML = value;
							}
						}

						if (params[BX.message('SITE_ID')]['tasks_orig'])
						{
							var node = BX('tasks-main-top-counter-originator');
							var value = params[BX.message('SITE_ID')]['tasks_orig'];

							if (value == 0)
								node.style.display = 'none';
							else
							{
								node.style.display = '';
								node.innerHTML = value;
							}
						}
					}
				}, this));
			</script>
			<?php
		}

		if ($arParams['SHOW_FILTER_BAR'] === 'Y')
		{
			?>
			<div class="task-top-panel-two">
				<?php
				ob_start();
				$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];

				if (LANGUAGE_ID != 'de')
					echo strtolower($arResult['VIEW_STATE']['ROLES'][$selectedRoleCodename]['TITLE_ALT']);
				else
					echo $arResult['VIEW_STATE']['ROLES'][$selectedRoleCodename]['TITLE_ALT'];

				$role = ob_get_clean();

				if ($arResult['SELECTED_SECTION_NAME'] === 'VIEW_SECTION_ROLES')
				{
					ob_start();
					?> 
					<div id="task-top-panel-task-category-selector" class="task-top-panel-select"><?php
							$selectedCategoryCodename = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];
							echo $arResult['VIEW_STATE']['TASK_CATEGORIES'][$selectedCategoryCodename]['TITLE'];
						?> <span class="tasks_li_bottom_arrow"></span>
					</div>
					<?php
					$status = ob_get_clean();

					if ($selectedRoleCodename === 'VIEW_ROLE_RESPONSIBLE')
					{
						ob_start();
						?> 
						<div id="task-top-panel-task-originator-selector" class="task-top-panel-select"><?php
							if ($arResult['F_CREATED_BY'])
							{
								if ($arResult['F_CREATED_BY'] == $USER->getId())
									echo GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME');
								else
									echo htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_CREATED_BY']]);
							}
							else
								echo GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR');
							?> <span class="tasks_li_bottom_arrow"></span>
						</div>
						<?php
						$originator = ob_get_clean();

						echo str_replace(
							array('#ROLE#', '#STATUS#', '#ORIGINATOR#'),
							array($role, $status, $originator),
							GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_MATRIX_FOR_RESPONSIBLE')
						);
					}
					elseif ($selectedRoleCodename === 'VIEW_ROLE_ORIGINATOR')
					{
						ob_start();
						?> 
						<div id="task-top-panel-task-responsible-selector" class="task-top-panel-select"><?php
							if ($arResult['F_RESPONSIBLE_ID'])
								echo htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_RESPONSIBLE_ID']]);
							else
								echo GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE');
							?> <span class="tasks_li_bottom_arrow"></span>
						</div>
						<?php
						$responsible = ob_get_clean();

						echo str_replace(
							array('#ROLE#', '#STATUS#', '#RESPONSIBLE#'),
							array($role, $status, $responsible),
							GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_MATRIX_FOR_ORIGINATOR')
						);
					}
					else
					{
						echo str_replace(
							array('#ROLE#', '#STATUS#'),
							array($role, $status),
							GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_MATRIX')
						);
					}
				}
				elseif ($arResult['MARK_SECTION_ALL'] === 'Y')
				{
					?><div class="task-main-top-menu-advanced-filter">&nbsp;<?php

						$filterName = '';
						if (strlen($arParams['SELECTED_PRESET_NAME']))
							$filterName .= ': ' . $arParams['SELECTED_PRESET_NAME'];

						if ($arParams["VIEW_TYPE"] == "gantt")
						{
							?><span class="webform-small-button task-list-toolbar-filter" onclick="showGanttFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php
								echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
							?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
						}
						else
						{
							?><span class="webform-small-button task-list-toolbar-filter" onclick="showTaskListFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php
								echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
							?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
						}
					?></div><?php
				}
				?>
				<div class="task-top-panel-two-inright"><?php
					echo GetMessage('TASKS_PANEL_TAB_VIEW_SELECTOR_TITLE');
					?>
					<div id="task-top-panel-view-mode-selector" class="task-top-panel-select"
						><?php
							$selectedViewCodename = $arResult['VIEW_STATE']['VIEW_SELECTED']['CODENAME'];
							echo $arResult['VIEW_STATE']['VIEWS'][$selectedViewCodename]['TITLE'];
						?><span class="tasks_li_bottom_arrow"></span>
					</div>
				</div>
				<script>
				(function(){
					BX.ready(function(){
						BX.Tasks.ListControlsNS.menu.create('views_menu');
						<?php
						foreach ($arResult['VIEW_STATE']['VIEWS'] as $viewCodeName => $viewData)
						{
							$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['VIEWS'][$viewCodeName];
							?>BX.Tasks.ListControlsNS.menu.addItem(
								'views_menu',
								'<?php echo CUtil::JSEscape($viewData['TITLE']); ?>',
								'menu-popup-no-icon',
								'<?php echo $href; ?>'
							);
							<?php
						}
						?>

						BX.Tasks.ListControlsNS.menu.addDelimiter('views_menu');

						<?php
						foreach ($arResult['VIEW_STATE']['SUBMODES'] as $submodeCodeName => $submodeData)
						{
							$cls = (($submodeData['SELECTED'] === 'Y') ? 'menu-popup-item-accept' : 'task-noclass');
							$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['SUBMODES'][$submodeCodeName];

							if ($submodeCodeName === 'VIEW_SUBMODE_WITH_GROUPS')
							{
								continue; // inactive checkbox should not be displayed

								$cls .= ' task-top-panel-disabled-menu-item';
								$href = "javascript:void(0);";
							}

							?>BX.Tasks.ListControlsNS.menu.addItem(
								'views_menu',
								'<?php echo CUtil::JSEscape($submodeData['TITLE']); ?>',
								'<?php echo $cls; ?>',
								'<?php echo $href; ?>'
							);
							<?php
						}
						?>

						BX.Tasks.ListControlsNS.menu.create('categories_menu');
						<?php
						foreach ($arResult['VIEW_STATE']['TASK_CATEGORIES'] as $categoryCodeName => $categoryData)
						{
							$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName];
							
							?>BX.Tasks.ListControlsNS.menu.addItem(
								'categories_menu',
								'<?php echo CUtil::JSEscape($categoryData['TITLE']); ?>',
								'menu-popup-no-icon',
								'<?php echo $href; ?>'
							);
							<?php

							// add delimiter after completed tasks
							if ($categoryCodeName === 'VIEW_TASK_CATEGORY_COMPLETED')
								break;
						}
						?>

						BX.Tasks.ListControlsNS.init();

						BX.bind(
							BX('task-top-panel-view-mode-selector'),
							'click',
							function(){ BX.Tasks.ListControlsNS.menu.show('views_menu', BX('task-top-panel-view-mode-selector')); }
						);

						BX.bind(
							BX('task-top-panel-task-category-selector'),
							'click',
							function(){ BX.Tasks.ListControlsNS.menu.show('categories_menu', BX('task-top-panel-task-category-selector')); }
						);


						var userInputs = [];

						if (BX('task-top-panel-task-originator-selector'))
						{
							userInputs.push({
								inputNode        : BX('task-top-panel-task-originator-selector'),
								menuId           : 'originators_menu',
								pathPrefix       : '<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID'))); ?>',
								strAnyOriginator : '<?php echo GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR'); ?>',
								operation        : 'tasks.list::getOriginators()',
								urlParamName     : 'F_CREATED_BY'
							});
						}

						if (BX('task-top-panel-task-responsible-selector'))
						{
							userInputs.push({
								inputNode        : BX('task-top-panel-task-responsible-selector'),
								menuId           : 'responsibles_menu',
								pathPrefix       : '<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID'))); ?>',
								strAnyOriginator : '<?php echo GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE'); ?>',
								operation        : 'tasks.list::getResponsibles()',
								urlParamName     : 'F_RESPONSIBLE_ID'
							});
						}

						userInputs.forEach(function(userInput){
							BX.Tasks.ListControlsNS.menu.create(userInput.menuId);
							BX.Tasks.ListControlsNS.menu.addItem(
								userInput.menuId,
								userInput.strAnyOriginator,
								'menu-popup-no-icon',
								'<?php echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID'))); ?>'
							);

							BX.bind(
								userInput.inputNode,
								'click',
								(function(userInput){
									var menuInited = false;

									return function(){
										if (menuInited)
										{
											BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode);
											return;
										}

										menuInited = true;

										BX.CJSTask.batchOperations(
											[{
												operation : userInput.operation,
												userId    : <?php echo (int) $arParams['USER_ID']; ?>,
												groupId   : <?php echo (int) $arParams['GROUP_ID']; ?>,
												rawState  : '<?php echo CUtil::JSEscape($arResult['VIEW_STATE_RAW']); ?>'
											}],
											{
												callbackOnSuccess : (function(){
													return function(reply){
														var loggedInUserId = BX.message('USER_ID');
														var menuItems = [];

														reply['rawReply']['data'][0]['returnValue'].forEach(function(item){
															var menuItem = null;
															var name = '';

															if (item['USER_ID'] == loggedInUserId)
																name = '<?php echo GetMessageJS("TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME"); ?>';
															else
															{
																<?// NAME_FORMATTED may vary, but we want "LAST_NAME NAME" format?>
																var name = [];
																if(typeof item['LAST_NAME'] != 'undefined')
																	name.push(item['LAST_NAME']);
																if(typeof item['NAME'] != 'undefined')
																	name.push(item['NAME']);

																name = name.join(' ');
															}

															menuItem = {
																title : name + ' (' + item['TASKS_CNT'] + ')',
																path  : userInput.pathPrefix 
																	+ ((userInput.pathPrefix.indexOf('?') !== -1) ? '&' : '?') 
																	+ userInput.urlParamName 
																	+ '=' 
																	+ item['USER_ID']
															};

															if (item['USER_ID'] == loggedInUserId)
																menuItems.unshift(menuItem);
															else
																menuItems.push(menuItem);
														});

														if (menuItems.length)
															BX.Tasks.ListControlsNS.menu.addDelimiter(userInput.menuId);

														menuItems.forEach(function(item){
															BX.Tasks.ListControlsNS.menu.addItem(
																userInput.menuId,
																item.title,
																'menu-popup-no-icon',
																item.path
															);
														});

														BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode);
													};
												})()
											}
										);
									};
								})(userInput)
							);
						});
					});
				})();

					function showGanttFilter(bindElement)
					{
						BX.toggleClass(bindElement, "webform-small-button-active");
						TaskGanttFilterPopup.show(bindElement);
					};

					function showTaskListFilter(bindElement)
					{
						BX.toggleClass(bindElement, "webform-small-button-active");
						TaskListFilterPopup.show(bindElement);
					};
				</script>
			</div>
			<?php
		}

		if ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
		{
			$arStrings = array();

			if (
				isset($arResult['TASKS_NEW_COUNTER']['VALUE'])
				&& $arResult['TASKS_NEW_COUNTER']['VALUE']
			)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_NEW'];
				$arStrings[] = '<a href="' . $href . '" class="task-green-text">'
					. $arResult['TASKS_NEW_COUNTER']['VALUE'] 
					. ' '
					. GetMessage(
						'TASKS_PANEL_EXPLANATION_NEW_TASKS_SUFFIX_PLURAL_' 
						. $arResult['TASKS_NEW_COUNTER']['PLURAL']
					)
					. '</a>';
			}

			if (
				isset($arResult['TASKS_EXPIRED_COUNTER']['VALUE'])
				&& $arResult['TASKS_EXPIRED_COUNTER']['VALUE']
			)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED'];
				$arStrings[] = '<a href="' . $href . '" class="task-red-text">'
					. $arResult['TASKS_EXPIRED_COUNTER']['VALUE'] 
					. ' '
					. GetMessage(
						'TASKS_PANEL_EXPLANATION_EXPIRED_TASKS_SUFFIX_PLURAL_' 
						. $arResult['TASKS_EXPIRED_COUNTER']['PLURAL']
					)
					. '</a>';
			}

			if (
				isset($arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE'])
				&& $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']
			)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'];
				$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
					. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE'] 
					. ' '
					. GetMessage(
						'TASKS_PANEL_EXPLANATION_EXPIRED_SOON_TASKS_SUFFIX_PLURAL_'
						. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['PLURAL']
					)
					. '</a>';
			}

			if (
				isset($arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE'])
				&& $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']
			)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WAIT_CTRL'];
				$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
					. $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE'] 
					. ' '
					. GetMessage(
						'TASKS_PANEL_EXPLANATION_WAIT_CTRL_TASKS_SUFFIX_PLURAL_'
						. $arResult['TASKS_WAIT_CTRL_COUNTER']['PLURAL']
					)
					. '</a>';
			}

			if (
				isset($arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE'])
				&& $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']
			)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WO_DEADLINE'];
				$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
					. $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE'] 
					. ' '
					. GetMessage(
						'TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_SUFFIX_PLURAL_'
						. $arResult['TASKS_WO_DEADLINE_COUNTER']['PLURAL']
					)
					. '</a>';
			}

			$stringsCount = count($arStrings);
			if ($stringsCount)
			{
				?>
				<div class="task-top-panel-tre">
					<div class="task-main-notification-icon-counter"><?php
						echo $arResult['SELECTED_ROLE_COUNTER']['VALUE'];
					?></div>
					<span><?php echo GetMessage('TASKS_PANEL_EXPLANATION_PREFIX'); ?></span>
					<?php
					$stringsPrinted = 0;
					foreach ($arStrings as $string)
					{
						echo $string;
						$stringsPrinted++;

						$stringsRemain = $stringsCount - $stringsPrinted;

						if ($stringsRemain == 1)
							echo ' ' . GetMessage('TASKS_PANEL_EXPLANATION_AND_WORD') . ' ';
						elseif ($stringsRemain >= 2)
							echo ', ';
					}
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php
}