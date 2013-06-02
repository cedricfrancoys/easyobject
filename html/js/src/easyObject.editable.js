/**
 * easyObject.editable : A plugin generating edition widgets for forms elements
 *
 * Author	: Cedric Francoys
 * Launch	: July 2012
 * Version	: 1.0
 *
 * Licensed under GPL Version 3 license
 * http://www.opensource.org/licenses/gpl-3.0.html
 *
 */

// require jquery-1.7.1.js (or later), ckeditor.js, jquery-ui.timepicker.js, easyObject.grid.js, easyObject.dropdownlist.js, easyObject.choice.js
 
(function($){
	/** 
	 * We use jQuery valHooks on textareas to modify the behaviour of the $.val method (that is used by $.serialize) in order to:
	 *  - use wysiwyg editor value when one is attached to the textarea
	 *  - preserve carriage returns
	 */
	$.valHooks.textarea = {
		get: function( elem ) {
			if(typeof $(elem).data('value') == 'function') return $(elem).data('value')();
			else return elem.value.replace(/\r?\n/g, "\r\n");
		}
	};					

	$.fn.editable = function(arg, val){
		var default_conf = {
			name: '',
			value: '',
			type: 'string', //boolean, integer, string, password, short_text, text, date, time, datetime, timestamp, selection, binary, one2many, many2one, many2many
			readonly: false,
			required: false,
			onchange: function() {}
		}

		if(typeof(arg) == 'object') {
			return this.each(function() {
				return (function ($this, conf) {
					switch(conf.type) {
						case 'boolean':
							$this.data('widget', $('<input type="checkbox"/>')
												.attr({id: conf.name, name: conf.name, checked: (conf.value)?'checked':''})
												.val((conf.value)?1:0)
												.on('change', function () {
																this.value = (int)(this.checked);
																conf.onchange();
															})
												.appendTo($this)
										);
							break;
						case 'integer':
						case 'string': 
							$this.data('widget', $('<input type="text"/>')
												.attr({id: conf.name, name: conf.name})
												.val(conf.value)
												.on('change', conf.onchange)
												.appendTo($this)
										);
							break;
						case 'password':
							// as password may be changed on submission, we hide the associated input
							$this.append($('<input type="hidden"/>').attr({id: conf.name, name: conf.name}));
							$this.data('widget', $('<input type="password"/>')
												.on('change', function () {
																$('#'+conf.name).val($(this).val());
																conf.onchange();
															}
													)
												.appendTo($this)
										); 
							break;
						case 'short_text':
							$this.data('widget', $('<textarea />')
												.attr({id: conf.name, name: conf.name})
												.css('height', '150px')
												.html(conf.value)
												.on('change', conf.onchange)
												.appendTo($this)
										);
							break;
						case 'text': 
							$textarea = $('<textarea />')
										.css('display', 'none')
										.attr({id: conf.name, name: conf.name })
										.html(conf.value)
										.appendTo($this);
							CKEDITOR.replace($textarea[0], {
									filebrowserImageBrowseUrl : 'picasaBrowser.php',
									height: 250,
									toolbar: [
										['Maximize'],['Undo','Redo'],['Cut','Copy','Paste','PasteText','PasteFromWord'],['Bold','Italic','Underline','Strike','-','Subscript','Superscript', '-', 'RemoveFormat', '-', 'TextColor'],
										'/',
										['Source'],['NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote'],['Link','Image','Table','SpecialChar']	
									],
									enterMode: CKEDITOR.ENTER_BR,
									// change detection : we use the instanceReady event to catch the instance of the editor being created
									on: {
										instanceReady: function(event) {
											event.editor.on('blur', function() { if (this.checkDirty()) conf.onchange(); });
										}
									}
							});
							// this is the method that will be called by $.valHooks
							$textarea.data('value', function() {
								return CKEDITOR.instances[conf.name].getData();
							});
							break;
						case 'date':
							$this.data('widget', $('<input />')
												.attr({id: conf.name, name: conf.name})
												.val(conf.value)
												.on('change', conf.onchange)												
												.datepicker({ dateFormat: 'yy-mm-dd' })
												.appendTo($this)
										);
							break;
						case 'time': 
							$this.data('widget', $('<input />')
												.attr({id: conf.name, name: conf.name})
												.val(conf.value)
												.on('change', conf.onchange)												
												.timepicker({timeFormat: 'hh:mm:ss'})
												.appendTo($this)
										);
							break;
						case 'datetime':
							$this.data('widget', $('<input />')
												.attr({id: conf.name, name: conf.name})
												.val(conf.value)
												.on('change', conf.onchange)												
												.datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' })
												.appendTo($this)
										);
							break;
						case 'timestamp':
	//todo										
							break;
						case 'selection': 
							$select =	$('<select />')
										.attr({id: conf.name, name: conf.name})
										.on('change', conf.onchange)
										.appendTo($this);
							$this.data('widget', $select);
							$.each(conf.selection, function(value, display) {
								$option = $('<option />').attr('value', value).text(display);
								if(value == conf.value) $option.attr('selected', 'selected');
								$select.append($option);
							});
							break;
						case 'binary':
							$this.data('widget', $('<input type="file" />')
												.attr({id: conf.name, name: conf.name})
												.on('change', conf.onchange)
												.appendTo($this)
										);
							//$('<input type="hidden" />').attr('name', 'MAX_FILE_SIZE').val('256000')
							break;
						case 'many2one':
							$this.append($('<input type="hidden"/>')
											.attr({id: conf.name, name: conf.name})
											.on('change', conf.onchange)
										);
							$this
							.choice(conf)
							.on('change', function () {
											$('#'+conf.name).val($this.choice('selection'));
										}
									);
							// an element with id 'choice_input' is generated by the method .choice()
							$this.data('widget', $('#choice_input', $this));
							break;
						case 'one2many':
							// display a tree if recursion is found (i.e. : destination class is the same that the one of the current object), use dropdown list otherwise 
							if(conf.parent_class == conf.class_name) {
								$this.tree(conf);
							}
							else {
								$this.append($('<input type="hidden"/>')
												.attr({id: conf.name, name: conf.name})
												.on('change', conf.onchange)
											);
								$this.dropdownlist(conf)
								.on('change', function () {
									var conf = $this.data('conf');
									var value = conf.more.toString();
									for(i in conf.less) {
										if(value.length > 0) value += ',';
										value += '-'+conf.less[i];
									}
									$('#'+conf.name).val(value);
								});						
							}
							break;
						case 'many2many':
							$this.append($('<input type="hidden"/>')
											.attr({id: conf.name, name: conf.name})
											.on('change', conf.onchange)
										);
							$this.grid($.extend(true, {
								del: {
									text: 'remove',
									icon: 'ui-icon-minus'
								},
								add: {
									text: 'add',
									icon: 'ui-icon-plus'
								}
							}, conf))
							.on('change', function () {
								// note : doing so we might add already existing relations
								var conf = $this.data('conf');
								var value = conf.more.toString();
								for(i in conf.less) {
									if(value.length > 0) value += ',';
									value += '-'+conf.less[i];
								}
								$('#'+conf.name).val(value);
							});
							break;
					}
				})($(this).empty(), $.extend(true, default_conf, arg));
			});				
		}
		else {
			return (function ($this, property_name, value) {
				switch(property_name) {
					case 'set' :
						// value is either 'invalid' or 'required'
						$widget = $this.data('widget');
						if(typeof $widget == 'object') $widget.addClass(value);
						break;
					case 'unset' :
						$widget = $this.data('widget');
						if(typeof $widget == 'object') $widget.removeClass(value);					
						break;
				}
			})($(this), arg, val);
		}
	}
})(jQuery);