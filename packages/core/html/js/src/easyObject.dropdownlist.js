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
				func: function($list) {},
				text: 'delete',
				icon: 'ui-icon-minus'
			},
			add: {
				func: function($list) {},
				text: 'add',
				icon: 'ui-icon-plus'
			},
			edit: {
				func: function($list) {},
				text: 'edit',
				icon: 'ui-icon-pencil'
			},			
			// ids to include to the domain
			more: [],
			// ids to exclude from the domain
			less: []			
		};
	
		var methods = {
			browse: function(conf, callback) {
				// create a temporary domain with the config domain and, if necessary, do some changes to it
				var domain = conf.domain;
				// add an inclusive OR clause
				if(conf.more.length) domain.push([['id','in', conf.more]]);
				// add an exclusive AND clause 
				if(conf.less.length) domain[0].push(['id','not in', conf.less]);

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
						domain: domain,
						fields: conf.fields
					},
					contentType: 'application/json; charset=utf-8',
					success: function(data){callback(data);},
					error: function(e){alert('Unable to fetch data : this may be caused by several issues such as server fault or wrong URL syntax.');}
				});
			},
			layout: function($list, conf) {		
				$list
				.append($('<select/>').addClass('list_select').css({'width': '85%', 'vertical-align': 'top', 'margin-right': '3px'}))
				.append(
					$('<button type="button"/>').button({icons:{primary:conf.edit.icon}, text: false}).attr('title', conf.edit.text).css({'margin': '1px', 'padding': '3px 0px 3px 0px', 'width': '4%'})
					.click(function() {
						if(typeof(conf.edit.func) == 'function') conf.edit.func($list);
					})
				)
				.append(
					$('<button type="button"/>').button({icons:{primary:conf.add.icon}, text: false}).attr('title', conf.add.text).css({'margin': '1px', 'padding': '3px 0px 3px 0px', 'width': '4%'})
					.click(function() {
						if(typeof(conf.add.func) == 'function') conf.add.func($list);
					})
				)
				.append(
					$('<button tpe="button"/>').button({icons:{primary:conf.del.icon}, text: false}).attr('title', conf.del.text).css({'margin': '1px', 'padding': '3px 0px 3px 0px', 'width': '4%'})
					.click(function() {
						if(typeof(conf.del.func) == 'function') conf.del.func($list);
					})					
				);
			},
			feed: function($list, conf) {
// todo : check if conf contains values or url
				this.browse(conf, function(json) {
					// get list and empty it
					$select = $('.list_select', $list).empty();				
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
				return $('.list_select', $list).val();			
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