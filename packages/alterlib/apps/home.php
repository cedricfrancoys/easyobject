<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include_once('parser.inc.php');
load_class('utils/HtmlWrapper');

$params = get_params(array('lang'=>'en'));

$html = new HtmlWrapper();


$html->addCSSFile('packages/alterlib/html/ui/1.10.4/themes/smoothness/jquery-ui.css');
$html->addCSSFile('packages/alterlib/html/jquery.chosen/chosen.css');
$html->addCSSFile('packages/alterlib/html/css/main.css');
$html->addCSSFile('packages/alterlib/html/css/details.css');
$html->addCSSFile('packages/alterlib/html/css/results.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/easyobject.api.min.js');

$html->addJSFile('packages/alterlib/html/ui/1.10.4/jquery-ui.js');
$html->addJSFile('packages/alterlib/html/jquery.chosen/chosen.jquery.js');

// 1) generate menu and list categories
$html_select_categories = '';
$array_categories = array();
$array_languages = array('fr'=>'Français', 'es'=>'Español', 'en'=>'English');
function build_menu($categories_ids, $root=false) {
	global $html_select_categories, $array_categories;
	if($root) $html = '<ul id="menu">';
	else $html = '<ul>';
	$categories = browse('alterlib\Category', $categories_ids, array('id', 'name', 'children_ids', 'path'));
	foreach($categories as $category) {
		$array_categories[$category['id']] = $category['name'];
		// we use attribute 'name' to store the category identifier
		$html .= '<li><a name="['.$category['id'].']" href="#">'.$category['name'].'</a>';
		$html_select_categories .= "<option value='{$category['id']}'>{$category['path']}</option>";
		if(!empty($category['children_ids'])) $html .= build_menu($category['children_ids']);
		$html .= '</li>';
	}
	$html .= '</ul>';
	return $html;
}
// get root categories
$cat_ids = search('alterlib\Category', array(array(array('parent_id', '=', '0'))), 'name');
// build menu by recursion
$html_menu = build_menu($cat_ids, true);


// 2) generate default result list (not mandatory but best for search engines and end-user)
$template_result = file_get_contents('packages/alterlib/html/template_result.html');
$documents_ids = search('alterlib\Document');
$documents = browse('alterlib\Document', $documents_ids, array('title', 'author', 'categories_ids', 'language', 'last_update'));

$renderer = array(
	'title'						=>	function ($document_id) use ($documents) {
									return '<a class="display-details" name="'.$document_id.'">'.$documents[$document_id]['title'].'</a>';
								},
	'author'					=>	function ($document_id) use ($documents) {
									return $documents[$document_id]['author'];
								},
	'last_update'				=>	function ($document_id) use ($documents) {
									return $documents[$document_id]['last_update'];
								},
	'categories'				=>	function ($document_id) use ($documents, $array_categories) {
									$html = '';
									for($i = 0, $j = count($documents[$document_id]['categories_ids']); $i < $j; ++$i) {
										if($i) $html .= ', ';
										$html .= '<a class="search-category" href="#" name="['.$documents[$document_id]['categories_ids'][$i].']">'.$array_categories[$documents[$document_id]['categories_ids'][$i]].'</a>';
									}
									return $html;
								},
	'search_same_author'		=>	function ($document_id) use ($documents) {
									return '<a class="search-author" href="#" name="'.$documents[$document_id]['author'].'">Toutes les publications de cet auteur</a>';
								},
	'search_same_categories'	=>	function ($document_id) use ($documents) {
									return 	'<a class="search-category" href="#" name="['.implode(',',$documents[$document_id]['categories_ids']).']">Autres publications dans ces catégories</a>';
								},
);

$html_result = '';
foreach($documents as $document_id => $document) {
	$html_result .= decorate_template($template_result, function ($attributes) use ($document_id, $renderer, $params) {
		if(isset($renderer[$attributes['id']])) return $renderer[$attributes['id']]($document_id);
	});
}


// 3) add JS scripts
// vars to be added to javascript
$js_categories = json_encode($array_categories, JSON_FORCE_OBJECT);
$js_languages = json_encode($array_languages, JSON_FORCE_OBJECT);
$html->addScript("
	// global vars
	var \$template_result, \$template_details;
	var \$details_dialog;
	var categories = {$js_categories};
	var languages = {$js_languages};
");
$html->addScript(<<<'EOD'
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

		var documents = browse('alterlib\\Document', [document_id], ['title', 'author', 'categories_ids', 'language', 'last_update', 'licence', 'description', 'type', 'size_txt', 'original_url', 'resilink']);

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
						cat_txt += categories[category];
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

	function display_results(documents_ids) {
		var $result_table = $('#result');
		$result_table.hide();
		// remove previous results
		$result_table.empty();
		$('#loader').show();
		var documents = browse('alterlib\\Document', documents_ids, ['title', 'author', 'categories_ids', 'language', 'last_update']);

		$.each(documents_ids, function(i, document_id) {
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
								.text(documents[document_id]['title'])
						);
						break;
					case 'categories':
						$.each(documents[document_id]['categories_ids'], function (j, category_id) {
							if(j > 0) $elem.before(', ');
							$elem.before(
								$('<a />')
									.addClass('search-category')
									.attr('href', '#')
									.attr('name', '['+category_id+']')
									.text(categories[category_id])
							);
						});
						break;
					case 'search_same_author':
						$elem.after(
							$('<a />')
								.addClass('search-author')
								.attr('href', '#')
								.attr('name', documents[document_id]['author'])
								.text('Toutes les publications de cet auteur')
						);
						break;
					case 'search_same_categories':
						var sub_categories = ''
						$.each(documents[document_id]['categories_ids'], function(j, category_id) {
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
						if(typeof documents[document_id][id] != 'undefined')
							$elem.after(documents[document_id][id]);
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

	$(document).ready(function() {

		// load templates
		$.ajax({
			type: 'GET',
			url: 'packages/alterlib/html/template_details.html',
			async: false,
			dataType: 'html',
			contentType: 'application/html; charset=utf-8',
			success: function(data){ $template_details = $('<div/>').html(data); },
			error: function(e){ alert('a template file is missing'); }
		});

		$.ajax({
			type: 'GET',
			url: 'packages/alterlib/html/template_result.html',
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
		$result_table.find('a.search-category').on('click', function() { search_category(this.name);});
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
				url: 'index.php?get=alterlib_lookup&'+$(this).serialize(),
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
EOD
);


// 4) add static html
$html->add('

<div style="position: relative; display: table; margin: 0; padding: 0; height: 150px; width: 100%;" >
	<div style="position: absolute; top: 0; left: 0;font-family: Verdana,Arial,sans-serif; font-size: 80%;">		
	</div>
	<div class="quick-search-dock">
		<img name="[]" src="packages/alterlib/html/img/compost.png" />
		<img name="[3]" src="packages/alterlib/html/img/construction_habitation.png" />
		<img name="[]" src="packages/alterlib/html/img/construction_presse_a_briques.png" />
		<img name="[]" src="packages/alterlib/html/img/eau_filtre.png" />
		<img name="[]" src="packages/alterlib/html/img/eau_pompe.png" />
		<img name="[]" src="packages/alterlib/html/img/eau_pompe_belier.png" />
		<img name="[]" src="packages/alterlib/html/img/eau_pompe_manuelle.png" />
		<img name="[25]" src="packages/alterlib/html/img/elec_eclairage.png" />
		<img name="[29]" src="packages/alterlib/html/img/elec_eolien.png" />
		<img name="[]" src="packages/alterlib/html/img/elec_hydraulique.png" />
		<img name="[16]" src="packages/alterlib/html/img/elec_solaire.png" />
		<img name="[]" src="packages/alterlib/html/img/nourriture_pain.png" />
		<img name="[14]" src="packages/alterlib/html/img/therm_solaire.png" />
	</div>
</div>
<div id="container">
 	<div id="loader" class="loader">Chargement en cours...</div>
 	<table id="inner">
	<tr>
 		<td id="left_pane">
			<div id="tabs">
			  <ul>
				<li><a href="#tabs-1">Categories</a></li>
				<li><a href="#tabs-2">Rechercher</a></li>
			  </ul>
			  <div id="tabs-1">
				'.$html_menu.'
			  </div>
			  <div id="tabs-2">
			    <form id="search_form">
				<div style="font-weight: bold; height: 25px;">Langue</div>
				<div style="width: 200px; display: block;">
				  <select id="widget_lang" name="language" data-placeholder="Sélectionner la langue..." class="chosen-select">
					<option value="" default></option>
					<option value="fr">Français</option>
					<option value="en">Anglais</option>
					<option value="es">Espagnol</option>
				  </select>
				</div>
				<br />
				<div style="font-weight: bold; height: 25px;">Catégorie(s)</div>
				<div style="width: 200px; display: block;">
				  <select id="widget_categories" name="categories_ids[]" drop-width="500px" data-placeholder="Dans une ou plusieurs catégories..." class="chosen-select" multiple>
					'.$html_select_categories.'
				  </select>
				</div>
				<br />
				<div style="font-weight: bold; height: 25px;">Auteur</div>
				<div><input id="widget_author" style="width: 198px;" name="author" type="text" value="" placeholder="Dans le nom de l\'auteur..."></div>
				<br />
				<div style="font-weight: bold; height: 25px;">Titre</div>
				<div><input id="widget_title" style="width: 198px;" name="title" type="text" value="" placeholder="Dans le titre..."></div>
				<br />
				<div style="width:100%; text-align: right;"><input type="submit" value="Ok" class="button" /></div>
				<input style="display: none;" type="reset" />
				</form>
			  </div>
			</div>
		</td>

		<td id="main">
			<table id="result">'.$html_result.'</table>
		</td>
	</tr>
	</table>
</div>
');

print($html);