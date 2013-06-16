/**
 * easyObject.tree : A simple jquery tree plugin intended to be used as widget for form element
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

	/**
	 * Tree widget plugin implementation
	 */

	 $.fn.tree = function(arg) { 
		var default_conf = {
			edit: {
				func: function($grid, selection) {},
				text: 'edit',
				icon: 'ui-icon-pencil'
			},
			del: {
				func: function($grid, selection) {},
				text: 'delete',
				icon: 'ui-icon-trash'
			},
			add: {
				func: function($grid, conf) {},
				text: 'create new',
				icon: 'ui-icon-document'
			},
			lang: easyObject.conf.content_lang, 
			ui: easyObject.conf.user_lang			
		};
	
		var methods = {
			browse: function(conf, callback) {
				$.ajax({
					type: 'GET',
					url: conf.url,
					async: false,
					dataType: 'json',
					data: {
						object_class: conf.class_name,
						rp: 100,
						page: 1,
						// note : we could use a variable sequence field 
						sortname: 'sequence',
						sortorder: 'asc',
						domain: conf.domain,
						fields: conf.fields.concat(['sequence'])
					},
					contentType: 'application/json; charset=utf-8',
					success: function(data){callback(data);},
					error: function(e){alert('Unable to fetch data : this may be caused by several issues such as server fault or wrong URL syntax.');}
				});
			},
			
			layout: function($tree, conf) {
				var self = this;
				// create list
				$list = $('<ul/>').addClass('tree_list').addClass('ui-selectable')
					.click(function(event) {
						if($(event.target).is('span')) {
							$this = $(event.target);
							// select item and unselect previously selected item if any 
							if(typeof conf.selection != 'undefined') conf.selection.children().first().removeClass('ui-selected');									
							$this.addClass('ui-selected');
							$li = $this.parent();
							conf.selection = $li;
						}
						else if($(event.target).is('li')) {
							$li = $(event.target);
							// fold or unfold item
							$li.trigger('folding');
							// select item and unselect previously selected item if any 
							if(typeof conf.selection != 'undefined') conf.selection.children().first().removeClass('ui-selected');									
							$li.children().first().addClass('ui-selected');
							conf.selection = $li;							
						}
						// we don't want changes to the parent UL (if any)
						return false;
					});

				// if displaying the 'root tree', add frame and buttons
				if(typeof conf.init == 'undefined') {
					conf.init = true;
					conf.selection = undefined;
					$table = $('<table/>').append($('<tr/>')
						.append($('<td/>').css({'width': '96%', 'border': 'solid 1px grey', 'v-align': 'top'})
							.append($list)
						)
						.append($('<td/>').css({'width': '4%', 'vertical-align': 'top'})
							.append(
								// move up
								$('<button type="button" />').button({icons:{primary:'ui-icon-arrowthick-1-n'}, text: false}).css({'display': 'block', 'margin': '4px', 'padding': '3px 0px 3px 0px'})
								.click(function() {
									// find the UL parent of selected item
									var $selected = conf.selection;
									for($ul = $selected; !$ul.is('ul'); $ul = $ul.parent());
									// swap selected item with previous item (if it is not the first of the list)
									if($ul.children().first().attr('id') != $selected.attr('id')) {
										$selected.prev().before($selected.detach());
										// get parent id
										var parent_id =  ($ul.parent().is('li'))?$li.parent().attr('id'):conf.parent_id;
										var sequence = $selected.attr('seq');
										// swap sequences
										$selected.attr('seq', $selected.prev().attr('seq'));
										$selected.next().attr('seq', sequence);
										// update items
										update(conf.parent_class, [$selected.next().attr('id')], {parent_id: parent_id, sequence: $selected.next().attr('seq')}, conf.lang);
										update(conf.parent_class, [$selected.attr('id')], {parent_id: parent_id, sequence: $selected.attr('seq')}, conf.lang);										
									}
								})
							)
							.append(
								// move down
								$('<button type="button"/>').button({icons:{primary:'ui-icon-arrowthick-1-s'}, text: false}).css({'display': 'block', 'margin': '4px', 'padding': '3px 0px 3px 0px'})
								.click(function() {
									// find the UL parent of selected item
									var $selected = conf.selection;
									for($ul = $selected; !$ul.is('ul'); $ul = $ul.parent());
									// swap selected item with next item (if it is not the last of the list)
									if($ul.children().last().attr('id') != $selected.attr('id')) {
										$selected.next().after($selected.detach());
										// get parent id
										var parent_id =  $ul.parent().attr('id');
										var sequence = $selected.attr('seq');
										// swap sequences
										$selected.attr('seq', $selected.prev().attr('seq'));
										$selected.prev().attr('seq', sequence);
										// update items
										update(conf.parent_class, [$selected.prev().attr('id')], {parent_id: parent_id, sequence: $selected.prev().attr('seq')}, conf.lang);
										update(conf.parent_class, [$selected.attr('id')], {parent_id: parent_id, sequence: $selected.attr('seq')}, conf.lang);
									}
								})
							)
							.append(
								// step down 
								$('<button type="button"/>').button({icons:{primary:'ui-icon-arrowthick-1-e'}, text: false}).css({'display': 'block', 'margin': '4px', 'padding': '3px 0px 3px 0px'})
								.click(function() {
									var $selected = conf.selection;
									for($ul = $selected; !$ul.is('ul'); $ul = $ul.parent());
									// if there is at leat one item before the selected one
									if($ul.children().first().attr('id') != $selected.attr('id')) {
										$previous = $selected.prev();
										var step_down = function() {
											$ul = $previous.children().last();
											if($ul.children().length) $ul.children().last().after($selected.detach());
											else $ul.append($selected.detach());
											// get parent id and update items that require it
											var parent_id =  $ul.parent().attr('id');
											$ul.children().each(function(i, item) {												
												// if required, update sequence and item
												if($(this).attr('seq') != i+1) {
													$(this).attr('seq', i+1);
													update(conf.parent_class, [$(this).attr('id')], {parent_id: parent_id, sequence: i+1}, conf.lang);
												}
											});											
										}
										// if necessary, trigger the folding handler
										if(!$previous.hasClass('unfolded')) $previous.trigger('folding', step_down);
										else step_down();
									}
								})
							)
							.append(
								// step up
								$('<button type="button"/>').button({icons:{primary:'ui-icon-arrowthick-1-w'}, text: false}).css({'display': 'block', 'margin': '4px', 'padding': '3px 0px 3px 0px'})
								.click(function() {
									// find the UL parent of selected item
									var $selected = conf.selection;
									for($ul = $selected; !$ul.is('ul'); $ul = $ul.parent());
									$li = $ul.parent();
									// if we can go left
									if($li.is('li')) {
										$li.after($selected.detach());
										// get parent id and update items that require it
										var parent_id =  ($li.parent().parent().is('li'))?$li.parent().parent().attr('id'):conf.parent_id;
										$li.parent().children().each(function(i, item) {
											// if required, update sequence and item
											if($(this).attr('seq') != i+1) {
												$(this).attr('seq', i+1);
												update(conf.parent_class, [$(this).attr('id')], {parent_id: parent_id, sequence: i+1}, conf.lang);
											}
										});									
									}
								})
							)
						)
					)
					$tree.append($table);
				}
				else $tree.append($list);
			},
			
			feed: function($tree, conf) {
				var self = this;
				// get body, empty it and display the loader
				$list = $('.tree_list', $tree).empty().append($('<div/>').addClass('loader').append('loading...'));
				self.browse(conf, function(json) {
					// empty the body again (to make the loader disappear)
					$list = $('.tree_list', $tree).empty();
					$.each(json.rows, function(i, row) {
						$list.append(
							$('<li/>')
								.attr('id', row.id)
								// sequence field is added in the feed method and is thus the last field
								.attr('seq', row.cell[conf.fields.length-1])						
								// to improve							
								// for now, we consider that the first column is the id and the second one holds some data that allow user to identify the object
								.append($('<span/>').text(row.cell[1]))
								
								.bind('folding', function(event, callback) {
									// folding is only fot current LI (not its parents)
									event.stopPropagation();								
									$li = $(this);
									// fold or unfold							
									if(!$li.hasClass('folded')) {
										// first load : unfold
										if(!$li.hasClass('unfolded')) {
											$li.addClass('unfolded');
											conf.domain[0][0][2] = $li.attr('id');
											// recursive call
											$li.tree(conf);									
										}
										// fold
										else {
											$li.removeClass('unfolded').addClass('folded');															
											$li.find('ul').first().toggle();
										}
									}
									// unfold
									else {
										$li.removeClass('folded').addClass('unfolded');															
										$li.find('ul').first().toggle();
									}
									if(typeof callback == 'function') callback();
								})
								.noSelect()
						);
					});
				});
			},
		
			selection: function($tree) {
				return $tree.data('selection').attr('id');
			}
		};

		// argument is either an object containing the configuration 
		// or a string containing a property name
		if(typeof(arg) == 'object') {
			return this.each(function() {
				// as this plugin may be recursively called, the arg may be the parent configuration (thus passed by reference)
				// so we don't make any change to it since it would also modify the parent
				methods.layout($(this), arg);
				$(this).data('conf', arg);
				methods.feed($(this), arg);				
			});
		}
		else {
			switch(arg) {
				case 'selection' :
					return methods.selection($(this));
			}
		}			
	};
})(jQuery);