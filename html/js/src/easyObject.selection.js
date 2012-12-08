/**
 * easyObject.dropdownlist.js : selection plugin intended to be used as widget for one2many elements
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
	$.fn.dropdownlist = function(arg) { 
		var default_conf = {
			data_type: 'json',
			sortname: 'id',
			sortorder: 'asc',
			del: {
				func: function($list, selection) {},
				text: 'delete',
				icon: 'ui-icon-minus'
			},
			add: {
				func: function($list, conf) {},
				text: 'add',
				icon: 'ui-icon-plus'
			},
			// ids to include to the domain
			more: [],
			// ids to exclude from the domain
			less: []			
		};
	
		var methods = {
			browse: function(conf, callback) {
				// embed the domain in an array and, if necessary, do some changes to it
				var domain = [];
				// add an inclusive OR clause
				if(conf.more.length) domain.push([['id','in', conf.more]]);
				// add an exclusive AND clause 
				if(conf.less.length) conf.domain.push(['id','not in', conf.less]);
				domain.push(conf.domain);
				
				var url = conf.url+'&domain='+domain_to_string(domain);
				$.ajax({
					type: 'GET',
					url: url,
					async: true,
					dataType: 'json',
					contentType: 'application/json; charset=utf-8',
					success: function(data){callback(data);},
					error: function(e){alert('Unable to fetch data : this may be caused by several issues such as server fault or wrong URL syntax.');}
				});
			},
			layout: function($list, conf) {		
				$list
				.append($('<select/>').attr('id', 'list_select').css({'width': '88%', 'vertical-align': 'top', 'margin-right': '3px'}))
				.append(
					$('<button/>').button({icons:{primary:conf.add.icon}, text: false}).attr('title', conf.add.text).css({'margin': '1px', 'padding': '3px 0px 3px 0px', 'width': '4%'})
					.click(function() {
						if(typeof(conf.add.func) == 'function') conf.add.func($list, conf);
					})
				)
				.append(
					$('<button/>').button({icons:{primary:conf.del.icon}, text: false}).attr('title', conf.del.text).css({'margin': '1px', 'padding': '3px 0px 3px 0px', 'width': '4%'})
					.click(function() {
						if(typeof(conf.del.func) == 'function') conf.del.func($list, conf);
					})					
				);
			},
			feed: function($list, conf) {
				var self = this;
				// get list and empty it
				$select = $('#list_select', $list).empty();
				self.browse(conf, function(json) {
					$.each(json.rows, function(i, row) {
						var value = '';
						$.each(row.cell, function(i, cell) {
							if(value.length) value += ', ';
							value += cell;
						});
						$select.append($('<option/>').attr('value', row.id).html(value));
					});
				});				
			},
			selection: function($list) {
				return $('#list_select', $list).val();			
			}
		};

		// argument is either an object containing the configuration 
		// or a string containing a property name
		if(typeof(arg) == 'object') {
			return this.each(function() {
				return (function ($list, conf) {
					methods.layout($list, conf);
					methods.feed($list, conf);
					$list.data('conf', conf);
					$list.on("reload", function(event){
						methods.feed($list, conf);
					});
				})($(this), $.extend(true, default_conf, arg));
			});				
		}
		else {
			return (function ($list, property_name) {
				switch(property_name) {
					case 'selection' :
						return methods.selection($list);
				}
			})($(this), arg);
		}			
	};
})(jQuery);