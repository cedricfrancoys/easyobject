/**
 * easyObject.form : A jquery form plugin intended to be used with easyObject
 * 
 * Author	: Cedric Francoys
 * Launch	: July 2012
 * Version	: 1.0
 *
 * Licensed under GPLv3
 * http://www.opensource.org/licenses/gpl-3.0.html
 *
 */

// require jquery-1.7.1.js (or later)

(function($) {
	/**
	* jQuery Form plugin :
	* generates an edition form for the specified object with specified view
	*
	*/
	$.fn.form = function(conf) {
		var default_conf = {
// todo : standardize this (object_name or class_name)
			class_name: '',							// class of the object to edit
			object_id: 0,							// id of the object to edit
			newinstance: false,						// are we editing a new object
			view_name: 'form.default',				// view to use for the object edition
			lang: easyObject.conf.content_lang,		// language in which request the content to server
			ui: easyObject.conf.user_lang,			// language in which display UI texts
			modified: false,						// state of the form
			autosave: true,							// do we autosave drafts of the object being edited
			success_handler: null,					// bypass the standard action listener and execute some function in case of success
			predefined: {}							// assign predefined values to some fields or insert hidden controls when those fields are not present in selected view
		};

		var methods = {
			/**
			* Loads object's view, transform the html
			* and put it into the specified form container
			*/
			layout : function($form, conf) {
				var view_html = easyObject.get_view(conf.class_name, conf.view_name);

				//we use tables for easier fields ordering
				var convert_to_table = function($item) {
					var html = $item.html();
					// labels
					html = html.replace(/(<label[^>]*>[^<]*<\/label>)/gi, '<td class="label">$1</td>');
					// vars
					html = html.replace(/(<var[^>]*>[^<]*<\/var>)/gi, '<td class="field">$1</td>');
					// table wrap + newlines
					html = '<table><tr>' + html.replace(/<br[^\/>]*>/gi, '</tr><tr>') + '</tr></table>';
					$item.html(html);
					// colspan check
					$item.find('label,var').each(function() {
						var colspan = $item.attr('colspan');
						if(typeof(colspan) != 'undefined') $item.parent().attr('colspan', colspan);
					});
					return $item;
				};

				// we use a recusive function to enrich html templates
				var transform_html = function($elem) {
					var $result = $('<div/>').css('display', 'none');

					// 1) process the sections of the current node
					var tabs_already_added = false;
					var $tabs_list = $('<ul/>');
					var $tabs_pane = $('<div/>').attr('id', $form.attr('id')+'_tabs')

					var $sections = $elem.children('section').each(function() {
						var name = $(this).attr('name');
						// we put a label inside the tab for later translation
						var $new_item = $('<li/>').append($('<a/>').attr('href','#'+name+'_tab').append($('<label/>').attr('name', name)));
						var $inner_item = transform_html($(this));
						var $new_tab = $('<div/>').attr('id', name+'_tab');
						$new_tab.append($('<table/>').append($('<tr/>').append($('<td/>').addClass('field').append($inner_item))));
						$tabs_list.append($new_item);
						$tabs_pane.append($new_tab);
					});
					$tabs_pane.prepend($tabs_list);
					// enable jQuery UI tabs
					$tabs_pane.tabs();

					// 2) process other elements
					$elem.children().each(function () {
						switch($(this).prop('nodeName').toLowerCase()) {
							case 'var':
							case 'label':
								$result.append($(this));
								break;
							case 'button':
								// enable jQuery UI buttons
								$(this).button();
								$result.append($(this));
								break;
							case 'fieldset':
								var $new_fieldset = $('<fieldset/>');
								var title = $(this).attr('title');
								if(title != undefined) $new_fieldset.append($('<legend/>').attr('name', title));
								$result.append($new_fieldset.append(transform_html($(this))));
								break;
							case 'span':
								var $new_div = $('<div/>');
								var width = $(this).attr('width');
								var align = $(this).attr('align');
								width = (width === undefined)?100:parseInt(width);
								align = (align === undefined)?'left':align;
								$new_div.css('float', 'left').css('text-align', align).css('width', (width-2) + '%').css('padding-left', '1%').css('padding-right', '1%');
								$result.append($new_div.append(convert_to_table($(this))));
								break;
							case 'div':
								var $new_div = $('<div/>');
								var width = $(this).attr('width');
								var align = $(this).attr('align');
								var id = $(this).attr('id');
								width = (width === undefined)?100:parseInt(width);
								align = (align === undefined)?'left':align;
								if(id != undefined) $new_div.attr('id', id);
								$new_div.css('float', 'left').css('text-align', align).css('width', width + '%');
								$result.append($new_div.append(transform_html($(this))));
								break;
							case 'section':
								if(tabs_already_added) break;
								$result.append($tabs_pane);
								tabs_already_added = true;
								break;
							default:
								$result.append($(this));
								break;
						}
					});
					return $result.children();
				};

				// create a jquery object by appending raw html to a temporary div
				$view = $('<div/>').append(view_html).children().first();
				// get the action (defined in the root item of the view)
				// if 'action' attribute is set, we add 'save', 'cancel' and 'apply' buttons to the form
				var action = $view.attr('action');
				if(action != undefined) {
					// append buttons to the view
					$view.append($('<div/>').attr('align', 'right').attr('width', '100%')
						.append($('<button type="button" />').attr('name', 'save').attr('action', 'save').attr('default', 'true'))
						.append($('<button type="button" />').attr('name', 'cancel').attr('action', 'cancel'))
						.append($('<button type="button" />').attr('name', 'apply').attr('action', 'apply')));
					if(conf.autosave)	
						$view.append($('<button type="button" />').css('display', 'none').attr('name', 'autosave').attr('action', 'draft'));
				}
				$form.append(transform_html($view));
			},

			/**
			* Inserts edition widgets into 'var' tags
			* and populate fields with their current values
			*/
			populate: function($form, conf) {
				var schemaObj = easyObject.get_schema(conf.class_name);

				var fields = easyObject.get_fields(conf.class_name, conf.view_name);
				// obtain object fields values
				//var object_values = (easyObject.browse(conf.class_name, [conf.object_id], fields, conf.lang))[conf.object_id];
				// we only need values of simple fields
				var object_values = (easyObject.browse(conf.class_name, [conf.object_id], null, conf.lang))[conf.object_id];
				
				// set pedefined values if any
				if(typeof conf.predefined == 'object') $.extend(object_values, conf.predefined);

				// initialize form callbacks handler
				$form.data('conf').onSubmitCallbacks = $.Callbacks();
				$form.data('conf').onSubmitResult = true;

				// insert controls and fields values
				$form.find('var').each(function() {
					var $item = $(this);
					var field = $item.attr('id');
					// if a field mentioned in the wiew do not match the object schema, ignore it
					if(typeof schemaObj[field] != 'undefined') {
						var attr_type = $item.attr('type');
						var attr_readonly = $item.attr('readonly');
						var attr_required = $item.attr('required');
						var attr_onchange = $item.attr('onchange');
						var attr_onsubmit = $item.attr('onsubmit');
						var attr_view = $item.attr('view');
						var attr_domain = $item.attr('domain');
						var attr_widget = $item.attr('widget');	

						// remove attributes that might cause undesired effects
						$item.removeAttr('onsubmit');
						$item.removeAttr('onchange');
						$item.removeAttr('id');
						
						
						var config = {
							name: field,
							parent_class: conf.class_name,
							parent_id: conf.object_id,
							lang: conf.lang,
							value: object_values[field],
							type: schemaObj[field]['type'],
							readonly: (attr_readonly != undefined),
							required: (attr_required != undefined),
							onchange: 	function() {
											if(!conf.modified) {
												conf.modified = true;
												easyObject.log('some change have been made to an object being edited ('+conf.class_name+', '+conf.object_id+')');
											}
											if(attr_onchange != undefined) {
												eval(attr_onchange);
											}
										}							
						};

						// set the proper type of the widget					
						if(config.type == 'related' || config.type == 'function') config.type = schemaObj[field]['result_type'];
						if(attr_type == 'password') config.type = 'password';

// todo: to validate (this is still experimental)						
						if(attr_widget != undefined) config.type = attr_widget;

						// set additional config params for special fields
						switch(config.type) {						
							case 'textarea':						
								// find the related label (if any), and set the label to the top of the cell
								$("label[for='"+field+"']").parent().css({'vertical-align': 'top'});
								break;
							case 'function':
							case 'related':
								break;
							case 'selection':
								$.extend(config, {
										selection: schemaObj[field]['selection']
								});
								break;
							case 'many2one':
								var class_name = schemaObj[field]['foreign_object'];
								// we use the get_grid_config although we'll generate a 'choice' widget
								$.extend(config, easyObject.get_grid_config({
										class_name: class_name,
										view_name: (attr_view != undefined)?attr_view:'list.default',
										domain: [[[ 'id', '=', object_values[field] ]]]
								}));

								$.extend(config, {
									choose: {
										func: function($choice, id) {
											$list = easyObject.UI.list({class_name: class_name, view_name: 'list.default', lang: conf.lang});
											$dia = easyObject.UI.dialog({
													content: $list,
													title: 'Choose item'});
											$dia.dialog({
												buttons: {
													"Ok": function() {
														var conf = $choice.data('conf');
														var $sub_grid = $list.data('grid');
														$.each($sub_grid.grid('selection'), function(i, id){
															conf.domain = [[[ 'id', '=', id ]]];
														});
														// closing the dialog will trigger the widget reload
														$(this).dialog("close").dialog("destroy");
													}
												},
												close: function(event, ui) {
													// force to refresh input content
													$choice.trigger('reload');
												}
											});
										}
									}
								});
								break;
							case 'one2many':
								var class_name = schemaObj[field]['foreign_object'];

								if(config.parent_class == class_name) {
									// recursive tree
									// obtain listiew for target object and generate grid config (col_model & url)
									$.extend(config, easyObject.get_grid_config({
											class_name: class_name,
											view_name: (attr_view != undefined)?attr_view:'list.default',
											domain: [[[ schemaObj[field]['foreign_field'], '=', conf.object_id ]]]
									}));
									$.extend(config, {
										del: {
											func: function($ddlist) {
												var conf = $ddlist.data('conf');
												var id = $ddlist.dropdownlist('selection');
												conf.less = add_value(conf.less, id);
												conf.more = remove_value(conf.more, id);
												// force grid to refresh its content
												$ddlist.trigger('reload');
												// update the value of the widget
												$ddlist.trigger('change');
											}
										},
										add: {
											func: function($ddlist) {
												$list = easyObject.UI.list({class_name: class_name, view_name: 'list.default', lang: conf.lang});
												$dia = easyObject.UI.dialog({
														content: $list,
														title: 'Add item'});
												$dia.dialog({
													buttons: {
														"Ok": function() {
															var conf = $ddlist.data('conf');
															var $sub_grid = $list.data('grid');
															$.each($sub_grid.grid('selection'), function(i, id){
																conf.more = add_value(conf.more, id);
																conf.less = remove_value(conf.less, id);
															});
															// closing the dialog will trigger the list reload
															$(this).dialog("close").dialog("destroy");
														}
													},
													close: function(event, ui) {
														// force grid to refresh its content
														$ddlist.trigger('reload');
														// update the value of the widget
														$ddlist.trigger('change');
													}
												});
											}
										}
									});

								}
								else {
									// obtain listiew for target object and generate grid config (col_model & url)
									$.extend(config, easyObject.get_grid_config({
											class_name: class_name,
											view_name: (attr_view != undefined)?attr_view:'list.default',
											domain: [[[ schemaObj[field]['foreign_field'], '=', conf.object_id]]]
									}));
// todo : use attr_domain : conf.domain.push(...)
// todo : make this part compatible for all widgets
									$.extend(config, {
										del: {
											func: function($ddlist) {
												var conf = $ddlist.data('conf');
												var id = $ddlist.dropdownlist('selection');
												conf.less = add_value(conf.less, id);
												conf.more = remove_value(conf.more, id);
												// force grid to refresh its content
												$ddlist.trigger('reload');
												// update the value of the widget
												$ddlist.trigger('change');
											}
										},
										add: {
											func: function($ddlist) {
												// we pass pre-defined value in the edition form (that will be stored whether field is displayed or not)
												var predefined = {};
												predefined[schemaObj[field]['foreign_field']] = conf.object_id;
												$dia = easyObject.UI.dialog({
															content: easyObject.UI.form({
																			class_name: class_name,
																			object_id: 0, 
																			lang: conf.lang,
																			predefined: predefined
																	}),
															title: 'New object - '+class_name
															});

												$dia.dialog({close: function(event, ui) {
																// force grid to refresh its content
																$ddlist.trigger('reload');
																// update the value of the widget
																$ddlist.trigger('change');
																$(this).dialog('destroy');
															}
												});
											}
										},
										edit: {
											func: function($ddlist) {		
												var id = $ddlist.dropdownlist('selection');
												$subform = easyObject.UI.form({class_name: class_name, object_id: id, view_name: 'form.default'});
												$dia = easyObject.UI.dialog({
														content: $subform,
														title: 'Object edition - '+class_name
												});
												$dia.dialog({close: function(event, ui) { $ddlist.trigger('reload'); $subform.trigger('destroy'); $(this).dialog('destroy');}});

											}
										}										
									});
								}
								break;
							case 'many2many':
								var class_name = schemaObj[field]['foreign_object'];

// todo : use attr_domain (not easy: can be related to a sublist...)

								// obtain listiew for target object and generate grid config (col_model & url)
								var domain = [[[schemaObj[field]['foreign_field'], 'contains', [conf.object_id]]]];
// tod: deal with this 								
								// if(attr_domain != undefined) domain[0].push(eval(attr_domain));

								$.extend(config, easyObject.get_grid_config({
										class_name: class_name,
										view_name: (attr_view != undefined)?attr_view:'list.default',
										domain: domain
								}));

								$.extend(config, {
									edit: {
										func: function($grid, ids) {
											$subform = easyObject.UI.form({class_name: class_name, object_id: ids[0], view_name: 'form.default'});
											$dia = easyObject.UI.dialog({
													content: $subform,
													title: 'Object edition - '+ class_name
											});
											$dia.dialog({close: function(event, ui) { $grid.trigger('reload'); $subform.trigger('destroy'); $(this).dialog('destroy');}});
										}
									},
									del: {
										func: function($grid, ids) {
											var conf = $grid.data('conf');
											$.each($grid.grid('selection'), function(i, id){
												conf.less = add_value(conf.less, id);
												conf.more = remove_value(conf.more, id);
											});
											// force grid to refresh its content
											$grid.trigger('reload');
											// update the value of the widget
											$grid.trigger('change');
										}
									},
									add: {
										func: function($grid) {
// todo : display only items not already present in relation
// todo: attr_domain is here too											
											var config = {class_name: class_name, view_name: 'list.default', lang: conf.lang};
											if(attr_domain != undefined) {
												var domain = [[]];											
												domain[0].push(eval(attr_domain));
												config = $.extend(true, config, {domain: domain});
											}

											$list = easyObject.UI.list(config);
											$dia = easyObject.UI.dialog({
													content: $list,
													title: 'Add relation'});
											$dia.dialog({
												buttons: {
													"Ok": function() {
														var conf = $grid.data('conf');
														var $sub_grid = $list.data('grid');
														$.each($sub_grid.grid('selection'), function(i, id){
															conf.more = add_value(conf.more, id);
															conf.less = remove_value(conf.less, id);
														});

														// closing the dialog will trigger the grid reload
														$(this).dialog("close").dialog("destroy");
													}
												},
												close: function(event, ui) {
													// force grid to refresh its content
													$grid.trigger('reload');
													// update the value of the widget
													$grid.trigger('change');
												}
											});
										}
									}
								});
								break;
						}

						// add widget to the form
						$item.editable(config);

						// add onSubmit callback to the form, if any
						if(attr_onsubmit != undefined) {
							$form.data('conf').onSubmitCallbacks.add(function() {
								// we don't use $.globalEval because we need access to the current context
								eval(attr_onsubmit);
							});
						}
					
						// empty binary fields would result in erasing already existing data!
						if(schemaObj[field]['type'] == 'binary') {
							$form.data('conf').onSubmitCallbacks.add(function() {
								if($item.data('widget').val().length == 0) $item.remove();
							});							
						}
						
						
						// setting content validation by adding onSubmit callback to the form
// todo : also check compatibilty (to prevent unnecessary server-side checks) for each field depending on its type (regexps)
// html = html.replace(/(<label[^>]*>[^<]*<\/label>)/gi, '<td class="label">$1</td>');
// var pattern = /[^0-9]/i;
// result = !pattern.test(string);
						if(attr_required != undefined) {
							// set field as required
							$item.editable('set', 'required');
// todo : there may be a confusion between empty required field and field with incompatible value, since no feedback is given to the user
							$form.data('conf').onSubmitCallbacks.add(function() {
								if($('#'+field, $form).val().length <= 0) {
									// if a required field is empty at submission, mark it as invalid
									$item.editable('set', 'invalid');
									$form.data('conf').onSubmitResult = false;
								}
								else $item.editable('unset', 'invalid');
							});
						}

					}
					
				});

				// insert some hidden controls : predefined fields not present in the specified view
				if(typeof conf.predefined == 'object') {
					$.each(conf.predefined, function(field, value){					
						if($.inArray(field, fields) < 0) {
							if(typeof(value) == 'object') field += '[]';
							$form.append($('<input type="hidden"/>').attr({id: field, name: field, value: value}));
						}
					});
				}

				// buttons
				$form.find('button').each(function() {
					var $this = $(this);
					var action_attr = $this.attr('action');
					var show_attr = $this.attr('show');
					var view_attr = $this.attr('view');					
					var output_attr = $this.attr('output');

					if(action_attr != undefined) {
						$this.click(function () {
							//execute action
							$form.trigger('submit', action_attr);
						});					
					}

					if(show_attr != undefined) {
						$this.click(function () {
							if(view_attr == undefined) view_attr = 'form.default';
							if(output_attr == undefined) output_attr = 'html';							
							// open new window and transmit the current context
							window.open('index.php?show='+show_attr+'&'+$.param({
									view: view_attr,
									id: conf.object_id,
									object_class: conf.class_name,
									output: output_attr
								})
							);
						});
					}

					// if we need to auto-save drafts, set the timeout handle				
					if($this.attr('name') == 'autosave') {
						var autosaving = function(){
							if(conf.modified) {
								// we simulate a click on the button
								$this.trigger('click');
								// reset the modification flag
								conf.modified = false;
							}
							conf.timer_id = setTimeout(autosaving, easyObject.conf.auto_save_delay * 60000);
						}
						// init timer
						conf.timer_id = setTimeout(autosaving, easyObject.conf.auto_save_delay * 60000);
					}

					if($this.attr('default') == 'true') {
						$this.focus();
// note: this causes conflict when more than one form is displayed at a time
// in addition it makes muli-lines inputs lose the focus when going to a newline
/*						
						$(document).bind('keyup', function(e) {
							 if(e.keyCode == 13) {
								$this.focus();
								$this.click();
							 }
							 return false;
						});
*/						

					}
				});

			},

			/**
			* Translates terms of the form (UI)
			* into the lang specified in the configuration object
			*/
			translate: function($form, conf) {
				// by default, display language elements as defined in schema
				var object_name = easyObject.getObjectName(conf.class_name);
				var package_name = easyObject.getObjectPackageName(conf.class_name);
				var langObj = easyObject.get_lang(package_name, object_name, conf.ui);
				var schemaObj = easyObject.get_schema(conf.class_name);
				// field labels 
// todo : not necesarily related to the object being edited : may also be of a subitem (what if parent and child have a field of the same name ?)
				$form.find('label').each(function() {
					var value;
					var field = $(this).attr('for');

					if(field != undefined) {
						if(!$.isEmptyObject(langObj) && typeof(langObj['model'][field]) != 'undefined' && typeof(langObj['model'][field]['label']) != 'undefined') {
							$(this).text(langObj['model'][field]['label']);
							if(typeof(langObj['model'][field]['help']) != 'undefined' && langObj['model'][field]['help']) {
								$(this).append($('<sup/>').addClass('help').text('?'));
								$(this).simpletip({
									content: langObj['model'][field]['help'].replace(/\n/g,'<br />'),
									showEffect: 'none',
									hideEffect: 'none'
								});
							}
						}
						else if(typeof(schemaObj[field]) != 'undefined') {
							if(typeof(schemaObj[field]['label']) != 'undefined') $(this).text(schemaObj[field]['label']);
							else $(this).text(ucfirst(field));
							if(typeof(schemaObj[field]['help']) != 'undefined' && schemaObj[field]['help'] ) {
								$(this).append($('<sup/>').addClass('help').text('?'));
								$(this).simpletip({
									content: schemaObj[field]['help'].replace(/\n/g,'<br />'),
									showEffect: 'none',
									hideEffect: 'none'
								});							
							}
						}
						// why to supress this attr ?
						// $(this).removeAttr('for');
					}
				});
				// stand-alone labels, legends, buttons (refering to the current view)
				$form.find('label,legend,button').each(function() {
					var name = $(this).attr('name');
					if(typeof(name) != 'undefined') {
						if(langObj && typeof(langObj['view'][name]) != 'undefined') {
							var value = langObj['view'][name]['label'];
							$(this).text(value);
						}
						else if(typeof(schemaObj[name]) != 'undefined' && typeof(schemaObj[name]['label']) != 'undefined')
							$(this).text(schemaObj[name]['label']);
						else $(this).text(ucfirst(name));
					}
				});
			},

			/**
			* Action execution handler
			* action is set in attribute 'onClick' of buttons defined in form's view
			* and is either a server action (/actions) or a special keyword ('cancel', ...)
			*/
			listen: function($form, conf) {
				// we hijack the default submit event to handle actions and to be able to submit files ('binary' type) by posting multipart/form-data content
				$form.bind('submit', function(event, action){
					var no_redirect = false;

					var close = function(type, msg) {
						if($form.parent().parent().hasClass('ui-dialog')) { // form is inside a dialog
							if(type == 'cancel'){
								$form.parent().dialog('destroy');	// we don't trigger the 'close' event
								$form.remove();
							}
							if(type == 'ok') {
								$form.parent().dialog('close').dialog('destroy');
								$form.remove();
							}
							// go to top of page
							$('html, body').animate({ scrollTop: 0 }, 0);
						}
		//				else if(typeof(msg) != 'undefined') alert(msg);
						return false;
					};

					switch(action) {
						case '':
								alert('No action is attached to this button.');
								return false;
						case 'cancel':
								return close('cancel');
						case 'apply':
								no_redirect = true;
						case 'save':
								action = 'core_objects_update';
								break;
						case 'draft':
								no_redirect = true;
								action = 'core_draft_save';
								break;
					}

					// 1) check submission callbacks (tasks that must be processed before the form submission)
					// onSubmit callbacks are used to :
					// - check fields validity
					// - execute user defined functions (set in views, using 'onSubmit' attribute) that could modify some data
					conf.onSubmitResult = true;
					conf.onSubmitCallbacks.fire();					
					if(!conf.onSubmitResult) return false; // something went wrong : stop the form submission

					// 2) POST the form data
					// we use an iframe to be able to post multipart/form-data content (that ajax does not allow)
					var iframe_name = ("uploader" + (new Date()).getTime());
					var $frame = $('<iframe name="' + iframe_name + '" src="about:blank" />').css('display', 'none')
					.appendTo('body')
					.load(function(){
						var response = window.frames[ iframe_name ].document.getElementsByTagName("body")[0].innerHTML;
						// we must check the received data to ensure it matches JSON format
						// this test is not completely safe, but good enough for now
						if(response.charAt(0) == '{') {
							var json_data = eval("(" + response + ")");

							// ensure evaluated code is an object
							if(typeof json_data == 'object') {
								if(typeof conf.success_handler == 'function') conf.success_handler(json_data);
								else {
									// process the returned data
									if(typeof json_data.result != 'object' && !json_data.result) {
										// json_data.result is an error_code
										//var message = 'Execution error(s):' + "\n";
	// todo : to check
										// get an array of messages for the current language
										var langObj = easyObject.get_lang(easyObject.getObjectPackageName(conf.class_name), easyObject.getObjectName(conf.class_name), conf.lang);

										var message = ucfirst(easyObject.error_codes[json_data.result]) + "\n";
										$.each(json_data.error_message_ids, function (index, item) {
											 if(typeof langObj['view'][item] != 'undefined') message += langObj['view'][item]['label'] + "\n";
											else message += item + "\n";
										});
										alert(message);
									}
									else {
										if(no_redirect) json_data.url = '';
										// if action require a redirection, go to the new location
										if(typeof(json_data.url) != 'undefined' && json_data.url.length > 0) window.location.href = json_data.url;
										else {
		// temporary
		/*
											if(no_redirect) alert('Action ' + action + ' successfuly executed');
											else return close('ok', 'Action ' + action + ' successfuly executed');
		*/
											return close('ok', 'Action ' + action + ' successfuly executed');
										}
									}
								}
							}
						}
						// when finished, remove the iframe
						setTimeout(function(){$(this).remove();}, 100);
					});

					// define url, method, content-type and target window (to be able to post binary data)
					$form
					.attr('action', 'index.php?do='+action)
					.attr('method', "post")
					.attr('enctype', "multipart/form-data")
					.attr('encoding', "multipart/form-data")
					.attr('target', iframe_name);
					// from now on, submission process continues with specified target (i.e. the temporary iframe)
				});
			}
		};

		return this.each(function() {
			(function ($form, conf) {
				// 1) display a loading spinner and return the result immediately
				$loader = $('<div/>').addClass('loading').append('loading...').appendTo($form);
				$container = $('<div/>').css('display', 'none').appendTo($form);
				// 2) do the other tasks asynchronously
				setTimeout(function(){
					// loading method
					var loadForm = function() {
						// attach configuration objet to $form for further use
						$form.data('conf', conf);
						methods.layout($container, conf);
						methods.populate($form, conf);
						methods.translate($form, conf);
						methods.listen($form, conf);
						$loader.remove();
						$container.toggle();
						// if form is inside a dialog, adjust vertical position of parent dialog
						if($form.parent().parent().hasClass('ui-dialog')) { 
							$dia = $form.parent();
							var dialog_height = $dia.height() + 50;
							var window_height = $(window).height();
							var position = $dia.dialog('option', 'position');
							$dia.dialog('option', 'position', [position[0], (window_height-dialog_height)/3]);								
						}
						// register a 'destroy' event for removing the form and its content and killing any attached timer
						$form.bind('destroy', function() {
							$form.empty().remove();
							if(typeof conf.timer_id != 'undefined') clearTimeout(conf.timer_id);
						});
					};

					// we check if there is a draft pending
					var ids = search('core\\Version', [[['object_class', '=', conf.class_name], ['object_id', '=', conf.object_id], ['state', '=', 'draft']]], '', 'asc', 0, 1, conf.lang);
					if(!$.isEmptyObject(ids)) {
						var result = browse('core\\Version', [ids[0]], ['created'], conf.lang);
						var timestamp = result[ids[0]]['created'];
						// display dialog
						var content = "<br /><br />Non-saved changes have been made since the last record of this element. <br />Do you want to recover from auto-saved draft ("+ timestamp +") ?<br /><br /><br />";
						// important : ignoring draft may result in loss of previous non-saved changes
						$dia = easyObject.UI.dialog({
							content: $('<div/>').append($('<div/>').css('padding', '10px').html(content))
							.append($('<div/>').css('text-align', 'center')
								.append($('<button type="button"/>').css('margin-right', '8px').text('Yes (restore)').button()
									.click(function () {
										easyObject.restore(conf.class_name, conf.object_id);
										$dia.dialog('destroy');
										loadForm();
									}))
								.append($('<button type="button"/>').text('No (ignore draft)').button()
									.click(function () {
										$dia.dialog('destroy');
										loadForm();
									}))
							),
							title: 'Draft pending',
							height: 250
						});
					}
					else loadForm();
				}, 100);
			})($(this), $.extend(default_conf, conf));
		});
   };
})(jQuery);