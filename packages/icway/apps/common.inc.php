<?php

load_class('orm/I18n');
load_class('utils/DateFormatter');

// we're dealing with vars from the global scope
global $params, $renderer;

// set 'fr' as default language
isset($_SESSION['icway_lang']) or $_SESSION['icway_lang'] = 'fr';

/**
* These are the parameters we might receive in the URL
* (for any application using the current file)
*/
$params = get_params(array('page_id'=>1, 'post_id'=>1, 'lang'=>null, 'label_id'=>null));

// lang param was not in the URL: use previously chosen or default
if(is_null($params['lang'])) $params['lang'] = $_SESSION['LANG'] = $_SESSION['icway_lang'];
else $_SESSION['icway_lang'] = $params['lang'];

// set associated locale
switch($params['lang']) {
	case 'en':
		setlocale(LC_ALL, 'en', 'en_EN', 'en_EN.UTF-8');
		break;
	case 'es':
		setlocale(LC_ALL, 'es', 'es_ES', 'es_ES.UTF-8');
		break;
	case 'fr':	
		setlocale(LC_ALL, 'fr', 'fr_FR', 'fr_FR.UTF-8');
		break;	
}
/**
* This function returns the translation value (if defined) of the specified term
* *
* @param string $term
* @param string $lang
*/
function get_translation($term, $lang) {
	$i18n = I18n::getInstance();
	return $i18n->getClassTranslationValue($lang, array(
												'object_class'	=> 'icway\Page',
												'object_part'	=> 'view',
												'object_field'	=> $term,
												'field_attr'	=> 'label')
											);
}
/**
* This function returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
function get_html($attributes) {
	global $params, $renderer;
	if(isset($renderer[$attributes['id']])) return $renderer[$attributes['id']]($params);
	else {	
		if(!isset($attributes['translate']) || !in_array($attributes['translate'], array('yes', 'on', 'true', '1'))) $html = '';
		else $html = get_translation($attributes['id'], $params['lang']);
		return $html;
	}
};

/**
* This array holds the methods to use for rendering the page
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array(
	'page_url'		=>	function ($params) {
							$url = FClib::get_url();
							$url = str_replace("&lang=".$params['lang'], '', $url);
							return $url;
						},
	'page_id'		=>	function ($params) {
							return $params['page_id'];
						},
	'post_id'		=>	function ($params) use ($values) {
							return $params['post_id'];
						},						
	'top_menu'		=>	function ($params) {
							$html = "<ul>";
							$ids = search('icway\Section', array(array(array('parent_id', '=', '1'), array('in_menu', '=', '1'))), 'sequence', 'desc');
							if(!count($ids)) break;
							$sections_values = &browse('icway\Section', $ids, array('title', 'page_id'), $params['lang']);
							$pages_ids = array_reduce($sections_values, function($a, $b) { $a[] = $b['page_id']; return $a;}, array());
							$pages_values = &browse('icway\Page', $pages_ids, array('title', 'url_resolver_id'), $params['lang']);
							foreach($pages_values as $id => $page) {
								$title = mb_strtoupper($page['title'], 'UTF-8');
								if($page['url_resolver_id'] > 0) {
									$url_values = &browse('core\UrlResolver', array($page['url_resolver_id']), array('human_readable_url'));
									$human_url = ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
									$html .= "<li><a href=\"$human_url\">".$title."</a></li>";
								}
								else $html .= "<li><a href=\"index.php?show=icway_site&page_id={$id}&lang={$params['lang']}\">$title</a></li>";
							}
							$html .= "</ul>";
							return $html;
						},
	'localizator'	=>	function ($params) {
							$path = array();
							// recurse to the root section
							$sections_ids = search('icway\Section', array(array(array('page_id', '=', $params['page_id']))));
							while(count($sections_ids)) {
								$sections_values = &browse('icway\Section', $sections_ids, array('parent_id', 'title', 'page_id'), $params['lang']);
								foreach($sections_values as $section_id => $section_values) {
									array_unshift($path, array($section_values['page_id'] => $section_values['title']));
									$sections_ids = array($section_values['parent_id']);
									if($section_values['parent_id'] == 0) break 2;
								}
							}
							$html = '<ul>';
							for($i = 0, $j = count($path); $i < $j; $i++) {
								foreach($path[$i] as $page_id => $page_title) {
									$html .= '<li><a href="index.php?show=icway_site&page_id='.$page_id.'&lang='.$params['lang'].'">'.$page_title.'</a></li>';
								}
							}
							$html .= '</ul>';
							return $html;
						},
	'latest_docs'	=>	function ($params) {
							$html = "<ul>";
							// sort resources by title (inside the current category)
							$resources_ids = search('icway\Resource', array(array(array())), 'modified', 'desc', 0, 3);
							$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'description', 'size', 'type'), $params['lang']);
							foreach($resources_values as $resource_values) {
								$dateFormatter = new DateFormatter($resource_values['modified'], DATE_TIME_SQL);
								// we use Google doc viewer for other stuff than images
								list($mode, $type) = explode('/', $resource_values['type']);
								$html .= '<li>';
								$html .= '  <a href="index.php?show=icway_site&page_id=7#'.$resource_values['id'].'">'.$resource_values['title'].'</a>';
								$html .= '  <span class="details">'.$dateFormatter->getDate(DATE_SQL).'&nbsp;&nbsp;|&nbsp;&nbsp;'.$type.'&nbsp;&nbsp;|&nbsp;&nbsp;'.floor($resource_values['size']/1000).'ko</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;
						},
	'latest_posts'	=>	function () use($params) {
							$html = "<ul>";
							$posts_ids = search('icway\Post', array(array(array())), 'modified', 'desc', 0, 3);
							$posts_values = &browse('icway\Post', $posts_ids, array('id', 'modified', 'title'), $params['lang']);
							foreach($posts_values as $post_values) {
								$dateFormatter = new DateFormatter($post_values['modified'], DATE_TIME_SQL);								
								$date = mb_convert_encoding(ucfirst(strftime("%B&nbsp;%Y", $dateFormatter->getTimestamp())), "UTF-8");
								$html .= '<li>';
								$html .= '  <a href="index.php?show=icway_blog&post_id='.$post_values['id'].'">'.$post_values['title'].'</a>';
								$html .= '  <span class="details">'.$date.'</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;	
						}
);