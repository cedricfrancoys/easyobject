<?php
load_class('orm/I18n');
load_class('utils/DateFormatter');
load_class('utils/HtmlTemplate');

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


// We override the HtmlTemplate class to customize its decorator behavior
class SiteTemplate extends HtmlTemplate {
	/**
	* This function returns html part specified by $attributes (from a 'var' tag) and associated with current post id
	* (here come the calls to easyObject API)
	*
	* @param array $attributes
	*/
	protected function decorator($attributes) {
		if(isset($this->renderer[$attributes['id']])) return $this->renderer[$attributes['id']]($this->params);
		else {
			if(!isset($attributes['translate']) || !in_array($attributes['translate'], array('yes', 'on', 'true', '1'))) $html = '';
			else $html = get_translation($attributes['id'], $this->params['lang']);
			return $html;
		}
	}
}


// we're dealing with vars from the global scope
global $params, $renderer;

// set 'fr' as default language
isset($_SESSION['icway_lang']) or $_SESSION['icway_lang'] = 'fr';

/**
* These are the parameters we might receive in the URL
* (for every application using the current file)
*/
$params = announce(	
	array(	
		'description'	=>	"Returns the values of the specified fields for the given objects ids.",
		'params' 		=>	array(
								'page_id'	=> array(
													'description' => 'The page to display.',
													'type' => 'string', 
													'default' => 1
													),
								'post_id'		=> array(
													'description' => 'The post to display in case we request a blog entry.',
													'type' => 'integer', 
													'default' => 1
													),
								'cat_id'		=> array(
													'description' => 'Identifier of the category associated to the post.',
													'type' => 'integer', 
													'default' => null
													),													
								'lang'			=> array(
													'description '=> 'Language in which to display content.',
													'type' => 'string', 
													'default' => null
													)
							)
	)
);

// lang param was not in the URL: use previously chosen or default
if(is_null($params['lang'])) $params['lang'] = $_SESSION['LANG'] = $_SESSION['icway_lang'];
else $_SESSION['icway_lang'] = $params['lang'];

// set order for auto-detect string fomat (for windows/linux compatibilty)
mb_detect_order(array('UTF-8', 'ISO-8859-1'));

// set associated locale
switch($params['lang']) {
	case 'en':
		setlocale(LC_ALL, 'en', 'en_EN', 'en_EN.utf8');
		break;
	case 'es':
		setlocale(LC_ALL, 'es', 'es_ES', 'es_ES.utf8');
		break;
	case 'fr':
		setlocale(LC_ALL, 'fr', 'fr_FR', 'fr_FR.utf8');
		break;
}


/**
* This array holds the functions to use for rendering the page
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array(
	'page_url'		=>	function ($params) {
							return config\FClib::get_url();
						},
	'to_fr'		=>	function ($params) {
							$url_ids = search('core\UrlResolver', array(array(array('complete_url', 'ilike', "/index.php?show=icway_site&page_id={$params['page_id']}&lang=fr"))));
							if(count($url_ids)) {
								$values = &browse('core\UrlResolver', $url_ids, array('human_readable_url'));
								$url = BASE_DIR.ltrim($values[$url_ids[0]]['human_readable_url'], '/');
							}
							else $url = BASE_DIR."index.php?show=icway_site&page_id={$params['page_id']}&cat_id={$params['cat_id']}&lang=fr";
							return $url;
						},
	'to_en'		=>	function ($params) {
							$url_ids = search('core\UrlResolver', array(array(array('complete_url', 'ilike', "/index.php?show=icway_site&page_id={$params['page_id']}&lang=en"))));
							if(count($url_ids)) {
								$values = &browse('core\UrlResolver', $url_ids, array('human_readable_url'));
								$url = BASE_DIR.ltrim($values[$url_ids[0]]['human_readable_url'], '/');
							}
							else $url = BASE_DIR."index.php?show=icway_site&page_id={$params['page_id']}&cat_id={$params['cat_id']}&lang=en";
							return $url;
						},
	'to_es'		=>	function ($params) {
							$url_ids = search('core\UrlResolver', array(array(array('complete_url', 'ilike', "/index.php?show=icway_site&page_id={$params['page_id']}&lang=es"))));
							if(count($url_ids)) {
								$values = &browse('core\UrlResolver', $url_ids, array('human_readable_url'));
								$url = BASE_DIR.ltrim($values[$url_ids[0]]['human_readable_url'], '/');
							}
							else $url = BASE_DIR."index.php?show=icway_site&page_id={$params['page_id']}&cat_id={$params['cat_id']}&lang=es";
							return $url;
						},
	'page_id'		=>	function ($params) {
							return $params['page_id'];
						},
	'post_id'		=>	function ($params) {
							return $params['post_id'];
						},
	'public_code'	=>	function ($params) {
							return SESSION_ID;
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
									$human_url = BASE_DIR.ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
									$html .= "<li><a href=\"$human_url\">".$title."</a></li>";
								}
								else $html .= '<li><a href="'.BASE_DIR.'index.php?show=icway_site&page_id='.$id.'&lang='.$params['lang'].'">'.$title.'</a></li>';
							}
							$html .= "</ul>";
							return $html;
						},
	'localizator'	=>	function ($params) {
							$path = array();
							// recurse to the root section
							$sections_ids = search('icway\Section', array(array(array('page_id', '=', $params['page_id']))));
							$pages_ids = array();
							while(count($sections_ids)) {
								$sections_values = &browse('icway\Section', $sections_ids, array('parent_id', 'title', 'page_id'), $params['lang']);
								foreach($sections_values as $section_id => $section_values) {
									array_unshift($path, array($section_values['page_id'] => $section_values['title']));
									$sections_ids = array($section_values['parent_id']);
									$pages_ids[] = $section_values['page_id'];
									if($section_values['parent_id'] == 0) break 2;
								}
							}
							$html = '<ul>';
							$pages_values = &browse('icway\Page', $pages_ids, array('title', 'url_resolver_id'), $params['lang']);
							for($i = 0, $j = count($path); $i < $j; $i++) {
								foreach($path[$i] as $page_id => $page_title) {
									if($pages_values[$page_id]['url_resolver_id'] > 0) {
										$url_values = &browse('core\UrlResolver', array($pages_values[$page_id]['url_resolver_id']), array('human_readable_url'));
										$human_url = BASE_DIR.ltrim($url_values[$pages_values[$page_id]['url_resolver_id']]['human_readable_url'], '/');
										$html .= "<li><a href=\"$human_url\">".$page_title."</a></li>";
									}
									else $html .= '<li><a href="'.BASE_DIR.'index.php?show=icway_site&page_id='.$page_id.'&lang='.$params['lang'].'">'.$page_title.'</a></li>';
								}
							}							
							$html .= '</ul>';
							return $html;
						},
	'latest_posts'	=>	function () use($params) {
							$html = "<ul>";
							$posts_ids = search('icway\Post', array(array(array('language','=', $params['lang']))), 'created', 'desc', 0, 3);
							$posts_values = &browse('icway\Post', $posts_ids, array('id', 'created', 'title'), $params['lang']);
							foreach($posts_values as $post_values) {
								$dateFormatter = new DateFormatter($post_values['created'], DATE_TIME_SQL);
								$date = ucfirst(strftime("%B&nbsp;%Y", $dateFormatter->getTimestamp()));
								if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');
								// $date = mb_convert_encoding(ucfirst(strftime("%B&nbsp;%Y", $dateFormatter->getTimestamp())), "UTF-8");
								$html .= '<li>';
								$html .= '  <a href="'.BASE_DIR.'index.php?show=icway_blog&post_id='.$post_values['id'].'">'.$post_values['title'].'</a>';
								$html .= '  <span class="details">'.$date.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;
						},
	'latest_docs'	=>	function ($params) {
							$html = "<ul>";
							// sort resources by title (inside the current category)
							$resources_ids = search('icway\Resource', array(array(array('language','=', $params['lang']))), 'created', 'desc', 0, 3);
							$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'created', 'title', 'description', 'size', 'type'), $params['lang']);
							foreach($resources_values as $resource_values) {
								$dateFormatter = new DateFormatter($resource_values['created'], DATE_TIME_SQL);
								list($mode, $type) = explode('/', $resource_values['type']);
								$html .= '<li>';
								$html .= '  <a href="'.BASE_DIR.'index.php?show=icway_site&page_id=7#'.$resource_values['id'].'">'.$resource_values['title'].'</a>';
								$html .= '  <span class="details">'.$dateFormatter->getDate(DATE_SQL).'&nbsp;&nbsp;|&nbsp;&nbsp;'.$type.'&nbsp;&nbsp;|&nbsp;&nbsp;'.floor($resource_values['size']/1000).'ko&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;
						}						
);