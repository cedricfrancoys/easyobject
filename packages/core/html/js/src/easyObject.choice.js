/**
 * easyObject.choice.js : selection plugin intended to be used as widget for many2one elements
 * 
 * Author	: Cedric Francoys
 * Launch	: September 2012
 * Version	: 1.0
 *
 * Licensed under GPLv3
 * http://www.opensource.org/licenses/gpl-3.0.html
 *
 */

// require jquery-1.7.1.js (or later)

(function($) {
	$.fn.choice = function(arg) { 
		var default_conf = {
			data_type: 'json',
			choose: {
				func: function($list, selection) {},
				text: 'choose',
				icon: 'ui-icon-folder-open'
			}
		};
	
		var methods = {
			browse: function(conf, callback) {				
				$.ajax({
					type: 'GET',
					url: conf.url,
					async: true,
					dataType: 'json',
					data: {
						object_class: conf.class_name,
						rp: conf.rp,
						page: conf.page,
						sortname: conf.sortname,
						sortorder: conf.sortorder,
						domain: conf.domain,
						fields: conf.fields						
					},
					contentType: 'application/json; charset=utf-8',
					success: function(data){callback(data);},
					error: function(e){alert('Unable to fetch data : this may be caused by several issues such as server fault or wrong URL syntax.');}
				});
			},
			layout: function($list, conf) {		
				$list
				.append(			
					$('<table/>').css({'width': '100%'})
					.append($('<tr/>')
						.append($('<td/>').css({'width': '100%'})
							.append($('<input/>').addClass('choice_input').css({'width': '98%'}))
						)
						.append($('<td/>')
							.append($('<button/>').button({icons:{primary:conf.choose.icon}, text: false}).attr('title', conf.choose.text).css({'margin-top': '-2px'})
										.click(function() {
											if(typeof(conf.choose.func) == 'function') conf.choose.func($list, conf);
										})
							)
						)
					)
				);
/*
				$list
				.append(
					$('<div/>').css({'display': 'table', 'width': '100%'})
					.append($('<div/>').cass('display': 'table-cell', 'width': '100%', 'padding': '0px 5px').append($('<input/>').addClass('choice_input').css({'width': '100%'})))
					.append(
						$('<button/>').button({icons:{primary:conf.choose.icon}, text: false}).attr('title', conf.choose.text).css({'display': 'table-cell', 'margin':'0', 'padding': '3px'})
						.click(function() {
							if(typeof(conf.choose.func) == 'function') conf.choose.func($list, conf);
						})
					)
				);
*/				
			},
			feed: function($choice, conf) {
				this.browse(conf, function(json) {
					$input = $('.choice_input', $choice);					
					$.each(json.rows, function(i, row) {
						var value = '';
						$.each(row.cell, function(i, cell) {
							if(value.length) value += ', ';
							value += cell;
						});
						$input.data('val', row.id);
						$input.val(value);
						$choice.trigger('change');
						$('#'+conf.name, $choice).val(row.id);
					});
				});				
			},
			selection: function($choice) {
				return $('.choice_input', $choice).data('val');			
			}
		};

		// argument is either an object containing the configuration 
		// or a string containing a property name
		if(typeof(arg) == 'object') {
			return this.each(function() {
				return (function ($choice, conf) {
					methods.layout($choice, conf);
					methods.feed($choice, conf);
					$choice.data('conf', conf);
					$choice.on('reload', function(event){
						methods.feed($choice, conf);
					});
				})($(this), $.extend(true, default_conf, arg));
			});				
		}
		else {
			return (function ($choice, property_name) {
				switch(property_name) {
					case 'selection' :
						return methods.selection($choice);
				}
			})($(this), arg);
		}			
	};
})(jQuery);