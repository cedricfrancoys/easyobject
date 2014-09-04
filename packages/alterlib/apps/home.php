<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

load_class('utils/HtmlWrapper');
load_class('utils/HtmlTemplate');

$params = get_params(array('lang'=>'en'));

$html = new HtmlWrapper();

$html->addCSSFile('packages/alterlib/html/ui/1.10.4/themes/smoothness/jquery-ui.css');
$html->addCSSFile('packages/alterlib/html/jquery.chosen/chosen.css');
$html->addCSSFile('packages/alterlib/html/css/main.css');
$html->addCSSFile('packages/alterlib/html/css/details.css');
$html->addCSSFile('packages/alterlib/html/css/results.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/easyobject.api.min.js');
$html->addJSFile('html/js/src/jquery.simpletip-1.3.1.js');

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
		$array_categories[$category['id']] = array('name'=>$category['name'], 'path'=>$category['path']);
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


// 2) generate default result list (not mandatory but better for search engines and end-user)
$template_result = file_get_contents('packages/alterlib/html/template_result.html');
// by default we request all documents (we could improve this with a multi-page widget)
$documents_ids = search('alterlib\Document');
$documents = browse('alterlib\Document', $documents_ids, array('title', 'author', 'categories_ids', 'language', 'last_update'));
// we describe how vars from result-template must be interpreted
$renderer = array(
	'title'						=>	function ($params) use ($documents) {
									return '<a class="display-details" name="'.$params['document_id'].'">'.$documents[$params['document_id']]['title'].'</a>';
								},
	'author'					=>	function ($params) use ($documents) {
									return $documents[$params['document_id']]['author'];
								},
	'last_update'				=>	function ($params) use ($documents) {
									return $documents[$params['document_id']]['last_update'];
								},
	'categories'				=>	function ($params) use ($documents, $array_categories) {
									$html = '';
									for($i = 0, $j = count($documents[$params['document_id']]['categories_ids']); $i < $j; ++$i) {
										if($i) $html .= ', ';
										$html .= '<a class="search-category" 
										href="#" 
										name="['.$documents[$params['document_id']]['categories_ids'][$i].']"
										alt="'.$array_categories[$documents[$params['document_id']]['categories_ids'][$i]]['path'].'"
										>'
										.$array_categories[$documents[$params['document_id']]['categories_ids'][$i]]['name']
										.'</a>';
									}
									return $html;
								},
	'search_same_author'		=>	function ($params) use ($documents) {
									return '<a class="search-author" href="#" name="'.$documents[$params['document_id']]['author'].'">Toutes les publications de cet auteur</a>';
								},
	'search_same_categories'	=>	function ($params) use ($documents) {
									return 	'<a class="search-category" href="#" name="['.implode(',',$documents[$params['document_id']]['categories_ids']).']">Autres publications dans ces catégories</a>';
								},
);
// add documents list to resulting html 
$html_result = '';
foreach($documents as $document_id => $document) {
	$template = new HtmlTemplate($template_result, $renderer, array('document_id'=>$document_id));	
	$html_result .= $template->getHtml();
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
// add main script (inline)
$html->addScript(file_get_contents('packages/alterlib/html/js/resilib.js'));


// 4) add static html
$page_2 = file_get_contents('packages/alterlib/html/presentation.html');
$page_3 = file_get_contents('packages/alterlib/html/participer.html');
$html->add('
<div class="simpleTabs">
	<ul class="simpleTabsNavigation">
		<li><a href="#page-1">Recherche</a></li>
		<li><a href="#page-2">Présentation</a></li>
		<li><a href="#page-3">Participer</a></li>		
	</ul>
	<div id="page-1" class="simpleTabsContent">
		<div style="position: relative; display: table; margin: 0; padding: 0; height: 100px; width: 100%;" >	
			<div class="quick-search-dock">
				<fieldset>
					<legend>Catégories phares</legend>
					<img name="[]" src="packages/alterlib/html/img/compost.png" />
					<img name="[3]" src="packages/alterlib/html/img/construction_habitation.png" />
					<img name="[54]" src="packages/alterlib/html/img/construction_presse_a_briques.png" />
					<img name="[41]" src="packages/alterlib/html/img/eau_filtre.png" />
					<!-- <img name="[22]" src="packages/alterlib/html/img/eau_pompe.png" /> -->
					<!-- <img name="[]" src="packages/alterlib/html/img/eau_pompe_belier.png" /> -->
					<img name="[22]" src="packages/alterlib/html/img/eau_pompe_manuelle.png" /> 
					<img name="[25]" src="packages/alterlib/html/img/elec_eclairage.png" />
					<img name="[29]" src="packages/alterlib/html/img/elec_eolien.png" />
					<img name="[]" src="packages/alterlib/html/img/elec_hydraulique.png" />
					<img name="[16]" src="packages/alterlib/html/img/elec_solaire.png" />
					<img name="[46]" src="packages/alterlib/html/img/nourriture_pain.png" />
					<img name="[47]" src="packages/alterlib/html/img/therm_solaire.png" alt="chauffe-eau" />
				</fieldset>
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
	</div>
	<div id="page-2" class="simpleTabsContent">
	'.$page_2.'	
	</div>
	<div id="page-3" class="simpleTabsContent">
	'.$page_3.'	
	</div>	
</div>
');

$html->add(count($documents_ids).' documents référencés');
print($html);