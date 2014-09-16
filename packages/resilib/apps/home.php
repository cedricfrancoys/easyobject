<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

load_class('utils/HtmlTemplate');

// this is french web-app (so far)
setlocale(LC_ALL, 'fr', 'fr_FR', 'fr_FR.utf8');

$params = get_params(array('lang'=>'en'));

// 1) generate menu and list categories

$html_select_categories = '';
$array_categories = array();
$array_languages = array('fr'=>'Français', 'es'=>'Español', 'en'=>'English');
function build_menu($categories_ids, $root=false) {
	global $html_select_categories, $array_categories;
	if($root) $html = '<ul id="menu">';
	else $html = '<ul>';
	$categories = browse('resilib\Category', $categories_ids, array('id', 'name', 'children_ids', 'path'));
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
$cat_ids = search('resilib\Category', array(array(array('parent_id', '=', '0'))), 'name');
// build menu by recursion
$html_menu = build_menu($cat_ids, true);


// 2) generate default result list (not mandatory but better for search engines and end-user)

// by default we request all documents (we could improve this with a multi-page widget)
$documents_ids = search('resilib\Document');
$documents = browse('resilib\Document', $documents_ids, array('title', 'author', 'categories_ids', 'language', 'last_update'));
// get template for result entries
$template_result = file_get_contents('packages/resilib/html/template_result.html');
// describe how vars from result-template must be interpreted
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


// 3) generate final html

// describe how vars from main template must be interpreted
$renderer = array(
	'inline_script'				=>	function ($params) {
										// vars to be added to javascript
										$js_categories = json_encode($array_categories, JSON_FORCE_OBJECT);
										$js_languages = json_encode($array_languages, JSON_FORCE_OBJECT);
										$html = "
											// global vars
											var \$template_result, \$template_details;
											var \$details_dialog;
											var categories = {$js_categories};
											var languages = {$js_languages};
										";
										// add main script (inline)
										$html .= file_get_contents('packages/resilib/html/js/resilib.js');	
										return $html;
								},
	'menu'						=>	function ($params) use ($html_menu) {
										return $html_menu;
								},
	'select_categories'			=>	function ($params) use ($html_select_categories){
										return $html_select_categories;
								},								
	'result'					=>	function ($params) use ($html_result){
										return $html_result;
								},
	'presentation'				=>	function ($params) {
										return file_get_contents('packages/resilib/html/presentation.html');
								},
	'contribute'				=>	function ($params) {
										return file_get_contents('packages/resilib/html/contribute.html');
								},
	'date'						=>	function ($params) {
										$date = date("F Y");
										if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');
										return $date;
								},
	'count_docs'				=>	function ($params) use ($documents_ids){
										return count($documents_ids);
								},
	'count_cats'				=>	function ($params) use ($array_categories) {
										return count($array_categories);
								}								
);
// output html
$template = new HtmlTemplate(file_get_contents('packages/resilib/html/template.html'), $renderer);	
print($template->getHtml());