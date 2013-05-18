// todo : feedback sur les erreurs (browse, update return 8 : not allowed & incorrect output [si set_silent n'est pas appel�])
// views are mandatory (stop if not found)
// translation files are optional


(function($) {
	/**
	* Error handler for remote calls related functions (ajax)
	* todo: use a DEBUG global var (conf)
	*/
	$(document).ajaxError(function(event, request, ajaxOptions, thrownError){
	  if (request.readyState == 4) {
		$console = $('#easyobject_console');
		if($console.length == 0) $console = $('<div/>').attr('id', 'easyobject_console').css('display', 'none').appendTo('body');
		$console.append(ajaxOptions['url'] + ' did not return valid json data: ' + thrownError + "<br/>");
		//alert(ajaxOptions['url'] + ' did not return valid json data:' + "\n" + thrownError);
	  }
	  else {
		alert('Error: some data could not be retrieved from server'+ "\n" + thrownError);
	  }
	});

	/**
	* Keyboard handler for console mechanism (dialog shows up on 'ctrl + shift')	
	* 
	*/
	$(document).bind('keydown', function(event) {
		if(event.ctrlKey && event.shiftKey && event.altKey) {
// todo : add a menu for common tasks		
			var user_id = easyObject.user_id();
			var user_values = (easyObject.browse('core\\User', [user_id], ['firstname', 'lastname'], easyObject.conf.lang))[user_id];
			var $dia = $('<div/>')
			.append($('<div/>').html('Current user: '+user_values['firstname']+' '+user_values['lastname']+' ('+user_id+')')
			.append($('<button type="button" />').css({'margin-left':'20px'}).html("logon").on('click', function() {logon_dialog();})))
			.append($('<div/>').css({'font-size': '11px', 'height': '200px', 'overflow': 'scroll', 'border': 'solid 1px grey'}).append($('#easyobject_console').html()))
			.appendTo($('body'));
			$dia.dialog({
				modal: true,
				title: 'easyobject console',
				width: 700,
				height: 'auto',
				x_offset: 0,
				y_offset: 0
			}).dialog('open');			
		}
	});
	
})(jQuery);	
	

/**
* singleton implementation for easyObject 
*
*/
var easyObject = {
		/* configuration data */
		conf: {
				user_id: 0,
				user_key: 0,
				// user_lang is the language in which the UI is displayed (set once and for all)
				user_lang: 'en',
				// content_lang is the language in which multilang fields values are displayed (on demand)
				content_lang: 'en',
				auto_save_delay: 5				// auto-save delay in minutes
		},
		/* objects data handlers */
		schemas: [],
		i18n: [],
		views: [],
		fields: [],		
		error_codes: {0: "unknown error(s)", 1: "invalid parameter(s) or wrong value(s)", 2: "SQL error(s)", 4: "unknown class or object", 8: "action not allowed : action violates some rule or you don't have permission to execute it"},
		simple_types: ['boolean', 'integer', 'string', 'short_text', 'text', 'date', 'time', 'datetime', 'timestamp', 'selection', 'binary', 'many2one'],
	
		init: function(conf) {
				$.extend(this.conf, conf);
		},
		
		/**
		* ObjectManager methods
		*/
		getObjectPackageName: function (class_name) {
				return class_name.substr(0, class_name.indexOf('\\'));
		},
		getObjectName: function(class_name) {
				return class_name.substr(class_name.indexOf('\\')+1);
		},
		log: function(txt) {
			$console = $('#easyobject_console');
			if($console.length == 0) $console = $('<div/>').attr('id', 'easyobject_console').css('display', 'none').appendTo('body');
			$console.append(txt + "<br/>");
		},
		browse: function(class_name, ids, fields, lang) {
				var result = [];
				$.ajax({
					type: 'GET',
					url: 'index.php?get=core_objects_browse',
					async: false,
					dataType: 'json',
// todo : rather set fields param to null in calls that require it and leave this to whatever the user asks for
					// we don't want to request complex fields, so we don't use the fields parameter					
					data: {
//						fields: fields,
						object_class: class_name,
						ids: ids,
						lang: lang
					},
					contentType: 'application/json; charset=utf-8',
					success: function(json_data){
							if(!json_data) alert("Unable to browse object : check fields names in DB/schema/related view and user's permissions");
							else result = json_data;
					},
					error: function(e){
					}
				});
				return result;
		},
		search: function(class_name, domain, order, sort, start, limit, lang) {
				var result = [];
				var values = {
					object_class: class_name,
					domain: domain,
					lang: lang
				};

				if(typeof order != undefined)	values.order = order;
				if(typeof sort != undefined)	values.sort = sort;
				if(typeof start != undefined)	values.start = start;
				if(typeof limit != undefined)	values.limit = limit;				
				
				$.ajax({
					type: 'GET',
					url: 'index.php?get=core_objects_search',
					async: false,
					dataType: 'json',
					data: values,
					contentType: 'application/x-www-form-urlencoded; charset=utf-8',
					success: function(json_data){
						result = json_data;
					},
					error: function(e){
					}
				});
				return result;
		
		},
		update: function(class_name, ids, values, lang) {
			/*
				function serializeForm($form) {
					var params = {};
					$.each($form.serializeArray(), function(index, value) {
						params[value.name] = value.value;
					});
					return params;
				}
			*/
				var result = [];
				$.ajax({
					type: 'POST',
					url: 'index.php?do=core_objects_update',
					async: false,
					dataType: 'json',
					data: $.extend({
						object_class: class_name,
						ids: ids,
						lang: lang
					}, values),
					// note : this MIME content-type does not allow binary data (FILE elements)
					contentType: 'application/x-www-form-urlencoded; charset=utf-8',
					success: function(json_data){
						result = json_data.result;
					},
					error: function(e){
					}
				});
				return result;
		},		
		remove: function(class_name, ids, permanent) {
				var result = [];
				$.ajax({
					type: 'GET',
					url: 'index.php?do=core_objects_remove',
					async: false,
					dataType: 'json',
					data: {
						object_class: class_name,
						ids: ids,
						permanent: Number(new Boolean(permanent))
					},					
					contentType: 'application/json; charset=utf-8',
					success: function(json_data){
							if(!json_data) alert("Unable to remove object : check user's permissions");
							else result = json_data;
					},
					error: function(e){
					}
				});
				return result;
		},
// todo : undelete (force deleted field to 0)		
		restore: function(class_name, id) {
				var result = [];
				$.ajax({
					type: 'GET',
					url: 'index.php?do=core_draft_restore&object_class='+class_name+'&id='+id,
					async: false,
					dataType: 'json',
					contentType: 'application/json; charset=utf-8',
					success: function(json_data){
					},
					error: function(e){
					}
				});
				return result;
		},

		/**
		* IdentificationManager methods
		*/
		lock: function (key, value) {
				if(typeof(value) == 'number') value = value.toString();
				if(typeof(key) == 'number') key = key.toString();	
				if(value.length == 32) {
					var hex_prev = function (val) {
						var hex_tab = '0123456789abcdef';
						var prev = parseInt(val, 16) - 1;
						if(prev < 0) prev = 15;
						return hex_tab.charAt(prev);
					}
					for(i = 0; i < key.length; ++i) {
						pos =  parseInt(key.charAt(i));
						hex_val = hex_prev(value.charAt(pos));
						value = value.substring(0,pos) + hex_val + value.substring(pos+1);
					}
				}
				return value;
		},
		login: function() {
		},
		user_id: function () {
				if(!easyObject.conf.user_id) {
					$.ajax({
						type: 'GET',
						url: 'index.php?get=core_user_id',
						async: false,
						dataType: 'json',
						contentType: 'application/json; charset=utf-8',
						success: function(json_data){
								easyObject.conf.user_id = json_data.result;
						},
						error: function(e){
						}
					});
				}
				return easyObject.conf.user_id;
		},
		user_key: function () {
				if(!easyObject.conf.user_key) {
					$.ajax({
						type: 'GET',
						url: 'index.php?get=core_user_key',
						async: false,
						dataType: 'json',
						contentType: 'application/json; charset=utf-8',
						success: function(json_data){
								easyObject.conf.user_key = json_data.result;
						},
						error: function(e){
						}
					});
				}
				return easyObject.conf.user_key;
		},
		user_lang: function () {
				if(!easyObject.conf.user_lang) {
					$.ajax({
						type: 'GET',
						url: 'index.php?get=core_user_lang',
						async: false,
						dataType: 'json',
						contentType: 'application/json; charset=utf-8',
						success: function(json_data){
								easyObject.conf.user_key = json_data.result;
						},
						error: function(e){
						}
					});
				}
				return easyObject.conf.user_lang;
		},
			
		/**
		* schemas methods
		*/			
		load_schema: function(package_name, class_name) {
				$.ajax({
					type: 'GET',
					url: 'index.php?get=core_objects_schema&object_class='+class_name,
					async: false,
					dataType: 'json',
					contentType: 'application/json; charset=utf-8',
					success: function(json_data){
							if(typeof(easyObject.schemas[package_name]) == 'undefined') easyObject.schemas[package_name] = new Array();
							easyObject.schemas[package_name][class_name] = json_data;
					},
					error: function(e){
					}
				});
		},
		get_schema: function(class_name) {
				var package_name = this.getObjectPackageName(class_name);
				if(typeof(easyObject.schemas[package_name]) == 'undefined' || typeof(easyObject.schemas[package_name][class_name]) == 'undefined') this.load_schema(package_name, class_name);
				return easyObject.schemas[package_name][class_name];
		},


		/**
		* i18n methods
		*/
		load_lang: function(package_name, object_name, lang) {
				$.ajax({
					type: 'GET',
					//url: 'index.php?get=core_i18n_lang&package='+package_name+'&lang='+lang,
					//url: 'library/classes/objects/'+package_name+'/i18n/'+lang+'/'+object_name+'.json',
					url: 'packages/'+package_name+'/i18n/'+lang+'/'+object_name+'.json',
					async: false,
					dataType: 'json',
					contentType: 'application/json; charset=utf-8',
					success: function(json_data){					
						easyObject.i18n[package_name][object_name] = json_data;
					},
					error: function(e){
					}	
				});
		},
		get_lang: function(package_name, object_name, lang) {
				if(typeof(easyObject.i18n[package_name]) == 'undefined' || typeof(easyObject.i18n[package_name][object_name]) == 'undefined') {
					if(typeof(easyObject.i18n[package_name]) == 'undefined') easyObject.i18n[package_name] = new Array();		
					this.load_lang(package_name, object_name, lang);
				}
				if(typeof(easyObject.i18n[package_name][object_name]) == 'undefined') return null;
				return easyObject.i18n[package_name][object_name];
		},

		/**
		* views methods
		*/
		load_view: function(package_name, object_name, view_name) {
				$.ajax({
					type: 'GET',
					//url: 'library/classes/objects/'+package_name+'/views/'+object_name+'.'+view_name+'.html',
					url: 'packages/'+package_name+'/views/'+object_name+'.'+view_name+'.html',
					async: false,
					dataType: 'html',
					contentType: 'application/html; charset=utf-8',
					success: function(json_data){
							if(typeof(easyObject.views[package_name]) == 'undefined') easyObject.views[package_name] = new Array();
							if(typeof(easyObject.views[package_name][object_name]) == 'undefined') easyObject.views[package_name][object_name] = new Array();			
							easyObject.views[package_name][object_name][view_name] = json_data;
					},
					error: function(e){
					}
				});
		},
		get_view: function(class_name, view_name) {
				var package_name = this.getObjectPackageName(class_name);
				var object_name = this.getObjectName(class_name);	
				if(typeof(easyObject.views[package_name]) == 'undefined' || 
				typeof(easyObject.views[package_name][object_name]) == 'undefined' || 
				typeof(easyObject.views[package_name][object_name][view_name]) == 'undefined') this.load_view(package_name, object_name, view_name);
				return easyObject.views[package_name][object_name][view_name];
		},
		get_fields: function(class_name, view_name) {
				var package_name = this.getObjectPackageName(class_name);
				var object_name = this.getObjectName(class_name);			
				if(typeof(easyObject.fields[package_name]) == 'undefined') easyObject.fields[package_name] = [];				
				if(typeof(easyObject.fields[package_name][object_name]) == 'undefined') easyObject.fields[package_name][object_name] = [];
				if(typeof(easyObject.fields[package_name][object_name][view_name]) == 'undefined') {
					easyObject.fields[package_name][object_name][view_name] = [];
					var item_type;
					switch(view_name.substr(0,4)) {
						case 'form' :
							item_type = 'var';
							break;
						case 'list' :
							item_type = 'li';
							break;
					}
					$('<div/>').append(easyObject.get_view(class_name, view_name)).find(item_type).each(function() {
						easyObject.fields[package_name][object_name][view_name].push($(this).attr('id'));
					});
				}
				return easyObject.fields[package_name][object_name][view_name];
		},
		get_grid_config: function(conf) {
			// in : class_name, view_name, domain
			// out : class_name, view_name, domain, url, col_model, fields
			var result = {
				url: '',
				col_model: [],
				fields: []
			};
			var default_conf = {
				class_name: '',
				view_name: 'list.default',
				domain: [[]],
				ui: easyObject.conf.user_lang,
				permanent_deletion: false
			};
			return (function(conf){
				var view_html = easyObject.get_view(conf.class_name, conf.view_name);
				// merge data from configuration
				$.extend(result, conf);
				$view = $('<div/>').append(view_html).children().first();
				// check if we need to apply a condition to the elements to be displayed in the view
				var domain_str = $view.attr('domain');
				if(domain_str != undefined) {
// todo : check syntax validity using reg exp
					var domain = eval(domain_str);
					result.domain.push(domain[0]);
				}
				// create a jquery object by appending raw html to a temporary div
				$view.find('li').each(function() {
					// extract the fields from the view and generate the columns model
					var name = $(this).attr('id');
					result.col_model.push({display: name, name: name, width: $(this).attr('width')});
					result.fields.push(name);
				});			
				if(result.url.length == 0) result.url = 'index.php?get=core_objects_list';
				return result;
			})($.extend(default_conf, conf));
		},
		
		/**
		* UI elements
		*/
		UI: {
				dialog: function(conf) {
					var default_conf = {
						content: $('<div/>'),
						modal: true,
						title: '',
						width: 650,
						height: 'auto',
						minHeight: 100,
						x_offset: 0,
						y_offset: 0
					};			
					conf = $.extend(default_conf, conf);
					var $dia = $('<div/>').attr('title', conf.title).appendTo($('body'));
					var $temp = $('<div/>').css({'position': 'absolute', 'left': '-10000px'}).append(conf.content).appendTo($('body'));
					var dialog_height = $temp.height() + 50;
					var dialog_width = conf.width;
					var window_height = $(window).height();
					var window_width = $(window).width();
					conf.content.detach();				
					$temp.remove();
					conf.minWidth = conf.width;
					conf.position = [(window_width-dialog_width)/2+conf.x_offset, (window_height-dialog_height)/3+conf.y_offset];
					$dia.append(conf.content).dialog(conf).dialog('open');
					return $dia;
				},
				alert: function(content, title) {
					easyObject.UI.dialog({
						content: $('<div/>').append($('<div/>').css('padding', '10px').text(content)).append($('<div/>').css('text-align', 'right').append($('<button/>').text('Ok').button()
						.click(function () {
							$dia.dialog('destroy');
						}))), 
						title: 'Alert', 
						height: 400
					});
				},
				confirm: function(text, title, actions) {
					easyObject.UI.dialog({
						content: $('<div/>').append($('<div/>').css('padding', '10px').text(content)).append(($('<div/>').css('text-align', 'right')
							.append($('<input type="button" />').css('margin-right', '8px').text('Yes').button().click(function () {
													$dia.dialog('destroy');
													if(typeof actions.yes_func != undefined) actions.yes_func();
												}))
							.append($('<input type="button"/>').text('No').button().click(function () {
													$dia.dialog('destroy');
													if(typeof actions.no_func != undefined) actions.no_func();
												}))
							)),
						title: 'Confirm', 
						height: 400});
				},
				loading: function() {
					var spinner = $('<img/>').attr('src', 'html/css/jquery/base/images/spinner.gif').css('margin-right', '3px');		
					return $('<div/>').attr('id', 'load').text('Loading ...').css('font-size', '16px').css('height','16px').css('line-height','16px').css('text-align','center').prepend(spinner);
				},
				form: function(conf) {		
					// the display of some relational fields require the actual existence of the objet before editing it
					if(typeof conf.object_id == 'undefined' || conf.object_id == 0) {
						// obtain a new id by creating a new empty object (as no values are specified, the modifier field wont be set)
						conf.object_id = (update(conf.class_name, [0], {}, conf.lang))[0];
						conf.autosave = false;
					}
					return $('<form/>').attr('id', 'edit').form($.extend(true, {
																		// for the object id : some methods require an array (ids) and other a single integer (id), so we put both
																		predefined: {
																			object_class: conf.class_name,
																			id: conf.object_id,																			
																			ids: [conf.object_id],
																			lang: conf.lang
																		}
																}, conf));
				},
				grid: function(conf) {
					return $('<div/>')
					.grid($.extend(true, {
						edit: {
							func: function($grid, ids) {
								$form = easyObject.UI.form({class_name: conf.class_name, object_id: ids[0], view_name: 'form.default', lang: conf.lang});
								$dia = easyObject.UI.dialog({
										content: $form,
										title: 'Object edition - ' + conf.class_name, 
										width: 650, 
										height: 'auto'
								});
								$dia.dialog({close: function(event, ui) { $grid.trigger('reload'); $form.trigger('destroy'); $(this).dialog('destroy');}});	
							}
						},
						del: {
							func: function($grid, ids) {
								remove(conf.class_name, ids, conf.permanent_deletion);
								$grid.trigger('reload');
							}
						},
						add: {
							func: function($grid) {							
								$dia = easyObject.UI.dialog({content: easyObject.UI.form({class_name: conf.class_name, lang: conf.lang}), title: 'New object - '+conf.class_name, width: 650, height: 'auto'});
								$dia.dialog({close: function(event, ui) { $grid.trigger('reload'); $(this).dialog('destroy');}});	
							}
						}
					}, conf));
				},
				list: function(conf) {
					// create inputs for critereas (simple_fields only)
					// (we make it very basic for now)
					var $search_criterea = $('<div/>').css('width', '100%');
					var fields = easyObject.get_fields(conf.class_name, conf.view_name);
					var schemaObj = easyObject.get_schema(conf.class_name);
					$.each(fields, function(i, field){
						if($.inArray(schemaObj[field]['type'], easyObject.simple_types) > -1  || (schemaObj[field]['type'] == 'function' && $.inArray(schemaObj[field]['result_type'], easyObject.simple_types) > -1)) {
							$search_criterea.append($('<div/>').css({'float': 'left', 'margin-bottom': '2px'}).append($('<div/>').append($('<label/>').css('margin-right', '4px').append(field)).append($('<input type="text"/>').attr('name', field).css('margin-right', '10px'))));
						}						
					});					
					// create the grid
					$grid = easyObject.UI.grid(easyObject.get_grid_config(conf));
					// remember the original domain
					var grid_domain_orig = $.extend(true, {}, $grid.data('conf').domain);
					
					// create the search button and the associated action when clicking 
					$search = $('<div/>').append($('<table/>').append($('<tr/>').append($('<td>').attr('width', '90%').append($search_criterea)).append($('<td>').append($('<button type="button"/>').button()
						.click(function(){
							// 1) generate the new domain (array of conditions)
							var grid_conf = $grid.data('conf');
							// reset the domain to its original state
							grid_conf.domain = $.extend(true, {}, grid_domain_orig);
							$search.find('input').each(function(){
								var $item = $(this);
								var field = $item.attr('name');
								var value = $item.val();
								if(value.length) {
									// create the new domain to filter the results of the grid
									type = schemaObj[field]['type'];
									if(schemaObj[field]['type'] == 'function' || schemaObj[field]['type'] == 'related') type = schemaObj[field]['result_type'];
									switch(type) {
										case 'boolean':
										case 'integer':
										case 'many2one':
										case 'selection':
										case 'date':
										case 'time':
										case 'datetime':
										case 'timestamp':
											grid_conf.domain[0].push([ field, '=', value]);
											break;
										case 'string':
										case 'short_text':								
										case 'text':
											grid_conf.domain[0].push([ field, 'like', '%' + value + '%']);
											break;
										case 'binary':
											// no filter allowed on this kind of field
											break;
									}
								}
							});
							// 2) force grid to refresh						
							$grid.trigger('reload');
						}
					).css('margin-bottom', '2px').text('search')))));	
					return $('<div/>').append($search).append($grid).data('grid', $grid);
				}
		}
};

/**
* easyObject standard API functions set
* 
*/

function user_id() {
	return easyObject.user_id();
}

function user_key() {
	return easyObject.user_key();
}

function user_lang() {
	return easyObject.user_lang();
}

function lock(key, value) {
	value = rtrim(value);
	if(value.length == 0) return;
	return easyObject.lock(key, hex_md5(value));
}

function browse(class_name, ids, fields, lang) {
	return easyObject.browse(class_name, ids, fields, lang);
}

function search(class_name, domain, order, sort, start, limit, lang) {
	return easyObject.search(class_name, domain, order, sort, start, limit, lang);
}

function update(class_name, ids, values, lang) {
	return easyObject.update(class_name, ids, values, lang);
}

function remove(class_name, ids, permanent) {
	return easyObject.remove(class_name, ids, permanent);
}

/**
*	UI methods
*/

// todo : distinction entre formulaire d'�dition et formulaire d'encodage ? (login form)
// dans le second cas, on n'a pas besoin de cr�er un nouvel objet
function edit(object_class, object_id, object_view, lang) {
	$('body').append(easyObject.UI.form({
			class_name: object_class,
			object_id: object_id,
			view_name: object_view,
			lang: lang,
			ui: easyObject.conf.user_lang			
	}));
}


function logon_dialog() {
	easyObject.UI.dialog({
		content:
			$('<form/>').attr('id', 'login_form').form({
				class_name: 'core\\User',
				view_name: 'form.login',
				autosave: false,				
				success_handler: function(json_data) {
					// if logon was successful get the new user_id
					if(json_data.result) {
						easyObject.conf.user_id = 0;					
						user_id();
						$('#login_form').parent().dialog('close').dialog('destroy');						
					}
				}
		}),
		title: 'Logon',
		width: 600,
		height: 'auto'
	});
}


/*
function popup(object_class, object_id, object_view, lang) {
	easyObject.UI.dialog({
			content: easyObject.UI.form({
				class_name: object_class, 
				object_id: object_id,
				view_name: object_view,
				lang: lang
			}), 
			title: 'Edit object', 
			width: 650, 
			height: 'auto'
	});
}
*/