function init_mutichoice_widgets() {
	// remove settings if already instanciated
	$('.chosen-container').remove();
	$('.chosen-select').removeData();
	// init chosen plugin
	$('.chosen-select').chosen({'width':'200px', 'search_contains':true, 'allow_single_deselect':true});
	// enrich the chosen plugin to handle the 'drop-with' attribute
	$('.chosen-select').each(function(index, elem){
		if($(elem).attr('drop-width') != undefined) {
			var width = $(elem).attr('drop-width');
			$(elem).parent().find('.chosen-drop').css({'width':width});
		}
	});
}

function search_category(categories_txt) {
	var categories_ids = eval(categories_txt);
	// reset search form
	$('#search_form').find('input:reset').trigger('click');
	// remove any previously selected option
	$('#widget_categories').find('option').each(function(index, elem){
		$(elem).removeAttr('selected');
	});
	// pre-select option related to specified categories
	$.each(categories_ids, function (i, category_id){
		$('#widget_categories').find('option[value=\''+category_id+'\']').attr('selected', 'selected');
	})
	// reinit multichoice widgets
	init_mutichoice_widgets();
	// submit form (launch search request)
	$('#search_form').trigger('submit');
}

function search_author(author) {
	// reset search form
	$('#search_form').find('input:reset').trigger('click');
	// remove any previously selected option
	$('#widget_categories').find('option').each(function(index, elem){
		$(elem).removeAttr('selected');
	});
	// reinit multichoice widgets
	init_mutichoice_widgets();
	// set author widget
	$('#widget_author').val(author);
	// submit form (launch search request)
	$('#search_form').trigger('submit');
}

function display_details(document_id) {
	// clone template object
	var $details_dialog = $('#details_dialog');
	var $loader = $details_dialog.find('.loader');

	// remove previous details DIV
	$details_dialog.find('.details').remove();
	// show loader
	$loader.show();
	$details_dialog.dialog('open');

	var $details = $template_details.clone();
	var documents = browse('resilib\\Document', [document_id], ['title', 'author', 'categories_ids', 'language', 'last_update', 'licence', 'description', 'type', 'size_txt', 'original_url', 'resilink']);

	if(typeof documents != 'object') return false;
	$details.find('var').each(function (index, elem) {
		var $elem = $(elem);
		var id = $elem.attr('id');
		switch(id) {
			case 'language':
				$elem.after(languages[documents[document_id]['language']]);
				break;
			case 'categories':
				var cat_txt = '';
				$.each(documents[document_id]['categories_ids'], function (i, category) {
					if(cat_txt.length > 0) cat_txt += ', ';
					cat_txt += categories[category]['name'];
				});
				$elem.after(cat_txt);
				break;
			default:
				if(typeof documents[document_id][id] != 'undefined')
					$elem.after(documents[document_id][id]);
		};
		$elem.remove();
	});

	$loader.hide();
	$details_dialog.append($details.addClass('details'));

	// adjust dialog vertical position
	var window_height = $(window).height();
	var dialog_height = $details_dialog.height() + 50;
	var position = $details_dialog.dialog('option', 'position');
	$details_dialog.dialog('option', 'position', [position[0], (window_height-dialog_height)/3]);
}

function display_results(documents) {
	var $result_table = $('#result');
	$result_table.hide();
	// remove previous results
	$result_table.empty();
	// remove any remaining tooltip
	$('.tooltip').remove();
	$('#loader').show();

	$.each(documents, function(document_id, document) {
		var $result = $template_result.clone();

		$result.find('var').each(function (index, elem) {
			var $elem = $(elem);
			var id = $elem.attr('id');
			switch(id) {
				case 'title':
					$elem.after(
						$('<a />')
							.addClass('display-details')
							.attr('name', document_id)
							.text(document['title'])
					);
					break;
				case 'categories':
					$.each(document['categories_ids'], function (j, category_id) {
						if(j > 0) $elem.before(', ');
						$elem.before(
							$('<a />')
								.addClass('search-category')
								.attr('href', '#')
								.attr('name', '['+category_id+']')
								.text(categories[category_id]['name'])
								.simpletip({
									baseClass: 'tooltip',
									position: 'right',
									content: categories[category_id]['path'],
									showEffect: 'none',
									hideEffect: 'none'
								})
						);
					});
					break;
				case 'search_same_author':
					$elem.after(
						$('<a />')
							.addClass('search-author')
							.attr('href', '#')
							.attr('name', document['author'])
							.text('Toutes les publications de cet auteur')
					);
					break;
				case 'search_same_categories':
					var sub_categories = ''
					$.each(document['categories_ids'], function(j, category_id) {
						if(j > 0) sub_categories += ',';
						sub_categories += category_id;
					})
					$elem.after(
						$('<a />')
							.addClass('search-category')
							.attr('href', '#')
							.attr('name', '['+sub_categories+']')
							.text('Autres publications dans ces catégories')
					);
					break;
				default:
					if(typeof document[id] != 'undefined')
						$elem.after(document[id]);
			};
			$elem.remove();
		});
		//
		$result.find('a.search-author').on('click', function() { search_author(this.name);});
		$result.find('a.search-category').on('click', function() { search_category(this.name);});
		$result.find('a.display-details').on('click', function() { display_details(this.name);});

		$result_table.append($result);
	});
	$('#loader').hide();
	$result_table.show();
}

/**
*	We add some initilizations on document ready event handler
*
*/
$(document).ready(function() {
	// initliaze simple tabs (in-page navigation)
	$('.simpleTabs').each(function() {
		var tab_id= '#';
		var items = $(this).find('ul.simpleTabsNavigation').find('li');
		items.find('a').on('click', function(event){ event.preventDefault(); });
		items.on('click', function() {
			$(this).parent().find('li.current').removeClass('current');	
			$(this).addClass('current');
			$(tab_id).toggle();
			tab_id = $(this).find('a').attr('href');
			$(tab_id).toggle();
		});
	});
	// select page 1
	$("a[href='#page-1']").trigger('click');

	// load templates
	$.ajax({
		type: 'GET',
		url: 'packages/resilib/html/templates/details.html',
		async: false,
		dataType: 'html',
		contentType: 'application/html; charset=utf-8',
		success: function(data){ $template_details = $('<div/>').html(data); },
		error: function(e){ alert('a template file is missing'); }
	});

	$.ajax({
		type: 'GET',
		url: 'packages/resilib/html/templates/result.html',
		async: false,
		dataType: 'html',
		contentType: 'application/html; charset=utf-8',
		success: function(data){ $template_result = $('<div/>').html(data).children().first(); },
		error: function(e){ alert('a template file is missing'); }
	});
	
	// initialize left menu and customize click behavior
	$('#menu').menu();

	// initilize tabs in left-pane
	$('#tabs').tabs();

	// initialize multichoice widgets (under 'search' tab)
	init_mutichoice_widgets();

	// make links in the result table alive
	var $result_table = $('#result');
	$result_table.find('a.search-author').on('click', function() { search_author(this.name);});
	$result_table.find('a.search-category').on('click', function() { search_category(this.name);})
	.each(function() {
		var category = $(this).attr('alt');
		if(category != undefined) $(this).simpletip({
			baseClass: 'tooltip',
			position: 'right',
			content: category,
			showEffect: 'none',
			hideEffect: 'none'
		});
	});
	$result_table.find('a.display-details').on('click', function() { display_details(this.name);});

// show app
	$('#loader').hide();
	$('#inner').show();

// do some non-urgent additional tasks...

	// make menu items clickable
	$('#menu').find('a').each(function() {
		// clicking a category is equivalent to a search request with specified category id as lone criteria
		$(this).on('click', function(event) {
			// force menu to close
			$('#menu').menu('collapseAll', event, true);
			// trigger search
			search_category(this.name);
			// prevent default behavior
			return false;
		});
	});

	// initialize details popup dialog
	var $details_dialog = $('<div/>')
	.attr('id', 'details_dialog')
	.append($('<div/>').addClass('loader').text('Chargement en cours...'))
	.dialog({
		autoOpen: false,
		modal: true,
		width: 700,
		height: 'auto',
		x_offset: 0,
		y_offset: 0,
		buttons: { 'fermer': function() { $(this).dialog('close'); } }
	});

	// handle search form submission
	$('#search_form').on('submit', function(e) {
		e.preventDefault();
		$(window).scrollTop(0);
		$.ajax({
			type: 'GET',
			url: 'index.php?get=resilib_lookup&'+$(this).serialize(),
			async: false,
			dataType: 'json',
			contentType: 'application/html; charset=utf-8',
			success: function(json){
				display_results(json);
			},
			error: function(e){ alert('unable to get data from server'); }
		});
	});

	// make the tabs menu sticky
	var tabs_abs_top = $('#tabs').offset().top - parseFloat($('#tabs').css('marginTop').replace(/auto/, 0));
	var tabs_rel_top = $('#tabs').position().top;		
	$(window).scroll(function (event) {
		if (($(this).scrollTop()+tabs_rel_top) >= tabs_abs_top)
			$('#tabs').addClass('fixed').css('top', tabs_rel_top+'px');
		else
		  $('#tabs').removeClass('fixed').css('top', tabs_rel_top+'px');
	});

	// make quick-search menu animated
	$('.quick-search-dock img')
	.hover(function() {	
		$(this).css('zIndex', 200).addClass('transition');
	}, function() {
		$(this).css('zIndex', 100).removeClass('transition');
	})
	.on('click', function() {
		$(this).css('zIndex', 100).removeClass('transition');
		search_category(this.name);
	});
	
});