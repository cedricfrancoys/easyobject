/**
 * easyObject.grid : A simple jquery grid plugin intended to be used as widget for form element
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
	 * Grid widget plugin implementation
	 */
	$.fn.grid = function(arg) { 
		var default_conf = {
			data_type: 'json',										// format of the received data (plugin only supports 'json' for now)
			rp: 20,													// number of results per page
			rp_choices: [5, 10, 25, 50, 100, 250, 500, 1000],		// allowed per-page values 			
			page: 1,												// default page to display
			sortname: 'id',											// default field on which perform sort
			sortorder: 'asc',										// order for sorting
			edit: {
				func: function($grid, selection) {},				// function to call for editing an item
				text: 'edit',										// alternate text for the edit button
				icon: 'ui-icon-pencil'								// icon for the edition button
			},
			del: {
				func: function($grid, selection) {},				// function to call for removing an item
				text: 'delete',										// alternate text for the delete button
				icon: 'ui-icon-trash'								// icon for the delete button
			},
			add: {
				func: function($grid, conf) {},						// function to call for adding an item
				text: 'create new',									// alternate text for the add button
				icon: 'ui-icon-document'							// icon for the add button
			},			
			more: [],												// ids to include to the domain			
			less: [],												// ids to exclude from the domain
			domain: [],												// domain (i.e. clauses to limit the results)
			lang: easyObject.conf.content_lang,						// language in which request the content to server 
			ui: easyObject.conf.user_lang							// language in which display UI items			
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
					async: false,
					dataType: 'json',
					data: {
						object_class: conf.class_name,
						rp: conf.rp,
						page: conf.page,
						sortname: conf.sortname,
						sortorder: conf.sortorder,
						domain: domain,
						fields: conf.fields,
						records: conf.records
					},
					contentType: 'application/json; charset=utf-8',
					success: function(data){callback(data);},
					error: function(e){alert('Unable to fetch data : this may be caused by several issues such as server fault or wrong URL syntax.');}
				});
			},
			layout: function($grid, conf) {
				var self = this;
				// create table
				var $table = $('<table/>').attr('id', 'grid_table').attr('cellspacing', '0');
				var $thead = $('<thead/>').attr('id', 'grid_table_head');
				var $tbody = $('<tbody/>').attr('id', 'grid_table_body');

				// instanciate header row and the first column which contains the 'select-all' checkbox
				var $hrow = $('<tr/>')
				.append($('<th/>').css({'width': '30px'})
					.append($('<div/>')
						.append(
							$('<input type="checkbox" />').addClass('checkbox')
							.click(function() {
								var checked = this.checked;
								$("input:checkbox", $tbody).each(function(i, elem) {
									if(checked) elem.checked = true;
									else elem.checked = false;
								});
							})
						)
					)
				);
				
				// create other columns, based on the col_model given in the configuration
				$.each(conf.col_model, function(i, col) {
//					$cell = $('<th/>').attr('id', col.name).addClass('column').css('width', col.width).append($('<div/>').text(col.display))
					$cell = $('<th/>').attr('name', col.name).addClass('column').css('width', col.width).append($('<div/>').append($('<label/>').attr('for', col.display)))
						.hover(
							/** The div style attr 'asc' or 'desc' is for the display of the arrow
							  * the th style attr 'asc' or 'desc' is to memorize the current order
							  * so, when present, both attributes should always be inverted 
							  */
							function() {
								// set hover and sort order indicator
								$this = $(this);
								$this.addClass('thOver');
								$div = $('div', $this);								
								$sorted = $thead.find('.sorted');
								if($sorted.attr('name') == $this.attr('name') && conf.sortorder == 'asc') $div.removeClass('asc').addClass('desc');
								else $div.removeClass('desc').addClass('asc');
							}, 
							function() {
								// unset hover and sort order indicator
								$this = $(this);
								$div = $('div', $this);
								$sorted = $thead.find('.sorted');
								$this.removeClass('thOver');
								$div.removeClass('asc').removeClass('desc');						
								if($sorted.attr('name') == $this.attr('name')) {
									if($this.hasClass('asc')) $div.addClass('asc');
									else $div.addClass('desc');
								}
							})
						.click(
							function() {
								// change sortname and/or sortorder
								$this = $(this);
								$sorted = $thead.find('.sorted');
								$div = $('div', $this);
								if($sorted.attr('name') == $this.attr('name')) {
									if($div.hasClass('asc')) {
										$div.removeClass('asc').addClass('desc');
										$this.removeClass('desc').addClass('asc');
										conf.sortorder = 'asc';
									}								
									else {
										$div.removeClass('desc').addClass('asc');
										$this.removeClass('asc').addClass('desc');
										conf.sortorder = 'desc';									
									}
								}
								else {
									$this.addClass('sorted').addClass('asc');
									$div.removeClass('asc').addClass('desc');
									$sorted.removeClass('sorted').removeClass('asc').removeClass('desc');
									$('div', $sorted).removeClass('asc').removeClass('desc');
									conf.sortorder = 'asc';
								}
								conf.sortname = $this.attr('name');
								// uncheck selection box
								$("input:checkbox", $thead)[0].checked = false;
								self.feed($grid, conf);
							}
						);
					if(col.name == conf.sortname) {
						$cell.addClass('sorted').addClass(conf.sortorder);
						$('div', $cell).addClass(conf.sortorder);
					}
					$hrow.append($cell);
				});				

				$grid.append($table.append($thead.append($hrow)).append($tbody));
			},
			pager: function($grid, conf) {
				var self = this;
					
				// create pager
				return $('<div/>').attr('id', 'grid_pager').addClass('pager')

					// 1) action buttons
					.append($('<div/>').css('left', '5px')
						// edit button
						.append($('<span/>').button({icons:{primary:conf.edit.icon}}).attr('title', conf.edit.text)
							.click(function() {
								var ids = self.selection($grid);
								if(!ids.length) alert('No item selected.');
//								else if(ids.length > 1) alert('Cannot edit more than one item at once.');
								else if(typeof(conf.edit.func) == 'function') conf.edit.func($grid, ids);
							})
						)
						// delete button
						.append($('<span/>').button({icons:{primary:conf.del.icon}}).attr('title', conf.del.text)
							.click(function() {
								var ids = self.selection($grid);
								if(!ids.length) alert('No item selected.');
								else if(confirm('Confirm deletion of ' + ids.length + ' selected item(s) ?', 'Deletion')) {						
									if(typeof(conf.del.func) == 'function') conf.del.func($grid, ids);
								}
							})
						)
						// add button
						.append($('<span/>').button({icons:{primary:conf.add.icon}}).attr('title', conf.add.text)
							.click(function() {
								if(typeof(conf.add.func) == 'function') conf.add.func($grid, conf);
							})
						)								
					)
					// 2) results & info
					.append($('<div/>').css('right', '10px')
						// current view info
						.append($('<span/>').css('padding', '2px').append(function(index, html) {
							var start = (conf.page-1) * conf.rp;
							if(start > 0) start++;
							var end = Math.min(start + parseInt(conf.rp) - 1, conf.records);
							return 'Results ' + start + ' - ' + end + ' of ' + conf.records;
						}))
						
						// separator
						.append($('<span/>').addClass('separator').text(' | '))					

						// result text
						.append($('<span/>').append('Show ').append(
							// number of results selection box
							$('<select />').css('width', '45px').attr('name', 'rp').attr('form', 'not_inside_a_form').change(function () {
									conf.rp = this.value;
									conf.page = 1;
									self.feed($grid, conf);
								}).append(function(index, html) {
									var options = '';
									$.each(conf.rp_choices, function(i, val) {
										options += "<option value='" + val + "'" + ((conf.rp == val)?' selected="selected" ':'') + ">" + val + "</option>";
									});				
									return options;
								})
							)
						)

					)

					// 3) page navigator
					.append($('<div/>').css('left', '50%').css('margin-left',  '-190px')
						// first page button
						.append($('<span/>').button({icons:{primary:'ui-icon-seek-start'}}).attr('title', 'first')
							.click(function() {
								var first = 1;
								if(conf.page != first) {
									$('#grid_table_body', $grid).empty();
									conf.page = first;
									self.feed($grid, conf);
								}
							}))
						// previous page button
						.append($('<span/>').button({icons:{primary:'ui-icon-seek-prev'}}).attr('title', 'prev')
							.click(function() {
								var previous = Math.max(parseInt(conf.page)-1, 1);
								if(conf.page != previous) {
									$('#grid_table_body', $grid).empty();								
									conf.page = previous;
									self.feed($grid, conf);
								}
							}))						
						// separator
						.append($('<span/>').addClass('separator').text(' | '))
						// current page among total number of result pages
						.append($('<span/>').append('Page ' + conf.page + ' of '+ conf.total))
						/*
						// this is for letting the user choose the page he wants to go to
						.append($('<span/>').append('Page ')
							.append($('<input type="text"/>').css('width', '26px').val(conf.page)
								.change(function() {
									var page = $(this).val();
									if(page >= 1 && page <= conf.total) {
										conf.page = page;
										self.feed($grid, conf);
									}
								})
							)
							.append(' of '+ conf.total)
						)
						*/
						// separator
						.append($('<span/>').addClass('separator').text(' | '))
						// next page button
						.append($('<span/>').button({icons:{primary:'ui-icon-seek-next'}}).attr('title', 'next')
							.click(function() {
								var next = Math.min(parseInt(conf.page)+1, conf.total);
								if(conf.page != next) {
									$('#grid_table_body', $grid).empty();								
									conf.page = next;
									self.feed($grid, conf);
								}
							}))						
						// last page button
						.append($('<span/>').button({icons:{primary:'ui-icon-seek-end'}}).attr('title', 'last')
							.click(function() {
								var last = conf.total
								if(conf.page != last) {
									$('#grid_table_body', $grid).empty();								
									conf.page = last;
									self.feed($grid, conf);
								}
							}))
					);
			},
			footer: function($grid, conf) {
				var self = this;
				var params = {
					show: 'core_objects_view',
					view: conf.view_name,
					object_class: conf.class_name,
					domain: conf.domain,
					rp: conf.rp,
					page: conf.page,
					sortname: conf.sortname,
					sortorder: conf.sortorder,
					fields: conf.fields
				};

				// create extra widgets at the bottom of the grid
				return $('<div/>').attr('id', 'grid_footer').addClass('bottom')
					.append($('<div/>').css('margin-left',  '7px')
						.append($('<span/>').text('Export:'))
						.append($('<a/>').css({'margin': '0px 5px'}).attr('href', '?index.php&'+$.param($.extend(params, {output: 'pdf'}))).attr('target', '_blank').append('pdf'))
						.append($('<span/>').text('|'))			
						.append($('<a/>').css({'margin': '0px 5px'}).attr('href', '?index.php&'+$.param($.extend(params, {output: 'xls'}))).attr('target', '_blank').append('xls'))
						.append($('<span/>').text('|'))						
						.append($('<a/>').css({'margin': '0px 5px'}).attr('href', '?index.php&'+$.param($.extend(params, {output: 'csv'}))).attr('target', '_blank').append('csv'))						
					);
			},
			feed: function($grid, conf) {
				var self = this;
				// get body, empty it and display the loader
				$tbody = $('#grid_table_body', $grid);
				if($tbody.children().length == 0) $tbody.append($('<tr/>').append($('<th/>').append($('<div/>').addClass('loader').append('loading...'))));
							
				self.browse(conf, function(json) {
					$tbody.empty();
					$('#grid_pager', $grid).remove();
					$('#grid_footer', $grid).remove();					
					$.each(json.rows, function(i, row) {
						$row = $('<tr/>').attr('id', row.id).append($('<td/>').append($('<div/>').append(
								$('<input type="checkbox" />')
								.addClass('checkbox')
								.on('dblclick', function() {conf.edit.func($grid, [row.id]);})
						)));
						if(i%2) $row.addClass('erow');
						$.each(row.cell, function(i, cell) {
							$row.append($('<td/>').append($('<div/>').text(cell)));
						});	
						$tbody.append($row);
					});
					conf.page = Math.max(1, json.page);
					conf.records = json.records;
					conf.total = Math.max(1, json.total);
					// add pager at the top and bottom of the grid
					$grid.prepend(self.pager($grid, conf));
					$grid.append(self.pager($grid, conf));
					$grid.append(self.footer($grid, conf));	
				});

			},

			/**
			* translate terms of the form
			* into the lang specified in the configuration object
			*/
			translate: function($grid, conf) {
				// by default, display language elements as defined in schema
				var object_name = easyObject.getObjectName(conf.class_name);
				var package_name = easyObject.getObjectPackageName(conf.class_name);
				var langObj = easyObject.get_lang(package_name, object_name, conf.ui);
				var schemaObj = easyObject.get_schema(conf.class_name);

				// field labels (not necesarily related to the object being edited : may also be of a subitem)
				$grid.find('label').each(function() {
					var value;
					var field = $(this).attr('for');
					if(typeof(field) != 'undefined') {
						// check field format (a full format means that the field is probabily refering to another object)

						if(langObj && typeof(langObj['model'][field]) != 'undefined' && typeof(langObj['model'][field]['label']) != 'undefined') {
							$(this).text(langObj['model'][field]['label']);
							if(typeof(langObj['model'][field]['help']) != 'undefined') {
								$(this).append($('<sup/>').addClass('help').text('?'));
								$(this).simpletip({
									content: langObj['model'][field]['help'].replace('\n','<br />'),
									showEffect: 'none',
									hideEffect: 'none'
								});
							}
						}
						else if(typeof(schemaObj[field]) != 'undefined') {
							if(typeof(schemaObj[field]['label']) != 'undefined') $(this).text(schemaObj[field]['label']);
							else $(this).text(ucfirst(field));
						}
						$(this).removeAttr('for');
					}
				});
				// stand-alone labels, legends, buttons (refering to the current view)
				$grid.find('label,legend,button').each(function() {
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
			
			selection: function($grid) {
				var selection = [];
				// for extended result
				var result = {};				
				var col_model = [];				
				$('#grid_table_head', $grid).find('.column').each(function() {
					col_model.push($(this).attr('id'));
				});
				
				$("input:checkbox", $('#grid_table_body', $grid)).each(function(i, elem) {
					if(elem.checked) {
						var id = $(elem).parent().parent().parent().attr('id');
						selection.push(id);
						/*						
						// extended result object with columns contents
						result[id] = {};						
						$sibling = $(elem).parent().parent().next();
						$.each(col_model, function(i, col_name) {
							result[id][col_name] = $sibling.children().text();
							$sibling = $sibling.next();
						});
						*/
					}
					
				});
				return selection;			
			}
		};

		// argument is either an object containing the configuration 
		// or a string containing a property name
		if(typeof(arg) == 'object') {
			return this.each(function() {
				return (function ($grid, conf) {
					$grid.addClass('ui-grid').addClass('ui-widget').noSelect();
					methods.layout($grid, conf);
					methods.translate($grid, conf);					
					$grid.data('conf', conf);
					$grid.on('reload', function(event){
						conf.page = 1;
						methods.feed($grid, conf);
					});
					$grid.trigger('reload');
				})($(this), $.extend(true, default_conf, arg));
			});				
		}
		else {
			return (function ($grid, property_name) {
				switch(property_name) {
					case 'selection' :
						return methods.selection($grid);
				}
			})($(this), arg);
		}			
	};
})(jQuery);