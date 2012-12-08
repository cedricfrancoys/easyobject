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

// require jquery-1.7.1.js (or later), fckeditor.js, jquery-ui.timepicker.js, easyObject.grid.js, easyObject.dropdownlist.js, easyObject.choice.js
 
(function($){
	/**
	 * Alternate FCK editor constructor that allows to set all main options at once
	 * and extends the created object so we can call a .val() method to obtain the editor's value (without having to use 'FCKeditorAPI')
	 */
	var FCKEditor = function(options) {
		var defaults = {
			name: 'editor',
			height: 250,
			toolbarSet: 'Basic',
			basePath: 'fckeditor/',
			CheckBrowser: true,
			DisplayErrors: true
		}
		return (function (options) {
			var fckEditor = new FCKeditor(options.name, 0, options.height, options.toolbarSet);
			$.extend(fckEditor, {
				BasePath: options.basePath,
				CheckBrowser: options.CheckBrowser,
				DisplayErrors: options.DisplayErrors,
				val: function() {
					return FCKeditorAPI.GetInstance(options.name).GetHTML();
				}			
			});			
			return fckEditor;
		})($.extend({}, defaults, options));	
	};
 
	/** 
	 * We use jQuery valHooks on textareas to modify the behaviour of the $.val method (that is used by $.serialize) in order to:
	 *  - use wysiwyg editor value when one is attached to the textarea
	 *  - preserve carriage returns
	 */
	$.valHooks.textarea = {
		get: function( elem ) {
		console.log('valhooks');
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
			onChange: function() {}
		}

		if(typeof(arg) == 'object') {
			return this.each(function() {
				return (function ($this, conf) {
					switch(conf.type) {
						case 'boolean':
							$this.data('widget', $('<input type="checkbox"/>').attr({id: conf.name, name: conf.name, checked: (conf.value)?'checked':''}).val((conf.value)?1:0).change(function () {this.value = (int)(this.checked);}).appendTo($this));
							break;
						case 'integer':
						case 'string': 
							$this.data('widget', $('<input type="text"/>').attr({id: conf.name, name: conf.name}).val(conf.value).appendTo($this));
							break;
						case 'password':
							// as password may be changed on submission (i.e. locked), we hide the associated input
							$this.append($('<input type="hidden"/>').attr({id: conf.name, name: conf.name}));
							$this.data('widget', $('<input type="password"/>').change(function () {$('#'+conf.name).val($(this).val());}).appendTo($this)); 
							break;
						case 'short_text':
							$this.data('widget', $('<textarea />').attr({id: conf.name, name: conf.name}).css('height', '150px').html(conf.value).appendTo($this));
							break;
						case 'text': 
							// we use our alternate constructor
							var editor = new FCKEditor({
								name: conf.name,
								height: 250, 
								toolbarSet: 'knine_Simple',
								basePath: 'html/js/fckeditor/'
							});
							$textarea = $('<textarea />').css('display', 'none').attr({id: conf.name, name: conf.name }).html(conf.value).data('value', editor.val).appendTo($this);
							//we replace the ReplaceTextarea() method, since it tries to get the textarea from the document DOM object (what doesn't work here for the textarea is not yet in the document)
							$textarea.before(editor._GetConfigHtml()).before(editor._GetIFrameHtml());
							break;
						case 'date':
							$this.data('widget', $('<input />').attr({id: conf.name, name: conf.name}).val(conf.value).datepicker({ dateFormat: 'yy-mm-dd' }).appendTo($this));
							break;
						case 'time': 
							$this.data('widget', $('<input />').attr({id: conf.name, name: conf.name}).val(conf.value).timepicker({timeFormat: 'hh:mm:ss'}).appendTo($this));
							break;
						case 'datetime':
							$this.data('widget', $('<input />').attr({id: conf.name, name: conf.name}).val(conf.value).datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' }).appendTo($this));
							break;
						case 'timestamp':
	//todo										
							break;
						case 'selection': 
							$select = $('<select />').attr({id: conf.name, name: conf.name}).appendTo($this);
							$this.data('widget', $select);
							$.each(conf.selection, function(value, display) {
								$option = $('<option />').attr('value', value).text(display);
								if(value == conf.value) $option.attr('selected', 'selected');
								$select.append($option);
							});
							break;
						case 'binary':
	//todo					
							break;
						case 'many2one':
							$this.append($('<input type="hidden"/>').attr({id: conf.name, name: conf.name}));
							$this.choice(conf)
							.change(function () {$('#'+conf.name).val($this.choice('selection'));});
							$this.data('widget', $('#choice_input', $this));
							break;
						case 'one2many':
							// display a tree if recursion is found (i.e. : destination class is the same that the one of the current object), use dropdown list otherwise 
							if(conf.parent_class == conf.class_name) {
								$this.tree(conf);
							}
							else {
								$this.append($('<input type="hidden"/>').attr({id: conf.name, name: conf.name}));
								$this.dropdownlist(conf)
								.change(function () {
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
							$this.append($('<input type="hidden"/>').attr({id: conf.name, name: conf.name}));
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
							.change(function () {
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