<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include_once('common.inc.php');


$template_file = 'packages/icway/html/template_site.html';
$values = &browse('icway\Page', array($params['page_id']), array('id', 'mnemonic', 'title', 'content', 'tips_ids'), $params['lang']);

/**
* Extend renderer array with functions specific to this app
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array_merge($renderer, array(
	'styles'		=>	function ($params) use ($values) {
							return '';
						},
	'scripts'		=>	function ($params) use ($values) {
							return '';
						},
	'inline_script'	=>	function ($params) use ($values) {
							return '';
						},
	'title'			=>	function ($params) use ($values) {
							return $values[$params['page_id']]['title'].' - icway.be';
						},
	'content'		=>	function ($params) use ($values) {
							return $values[$params['page_id']]['content'];
						},
	'left_column'	=>	function ($params) {
							// find level-2 section of current page
							$sections_ids = search('icway\Section', array(array(array('page_id', '=', $params['page_id']))));
							$selected_id = null;
							// find root section
							while(count($sections_ids)) {
								$sections_values = &browse('icway\Section', $sections_ids, array('parent_id'), $params['lang']);
								foreach($sections_values as $section_id => $section_values) {
									if($section_values['parent_id'] == 0) break 2;
									$selected_id = $section_id;
									$sections_ids = array($section_values['parent_id']);
								}
							}
							// if no match found, display default section
							if(is_null($selected_id)) $selected_id = 1;
							$sections_values = &browse('icway\Section', array($selected_id), array('page_id', 'title', 'sections_ids'), $params['lang']);
							// note: this is a loop but we only have one item
							foreach($sections_values as $section_id => $section_values) {
								$html = '<h1 style="cursor: pointer;" onclick="window.location.href=\'index.php?show=icway_site&page_id='.$section_values['page_id'].'&lang='.$params['lang'].'\';">'.$section_values['title'].'</h1>';
								$html .= '<ul>';
								$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('page_id', 'title'), $params['lang']);
								$pages_ids = array_reduce($subsections_values, function($a, $b) { $a[] = $b['page_id']; return $a;}, array());
								$pages_values = &browse('icway\Page', $pages_ids, array('title', 'url_resolver_id'), $params['lang']);
								foreach($pages_values as $id => $page) {
// todo: to remove when project page will be defined
if($id == 3) continue;
									if($id == $params['page_id']) $html .= '<li class="current">';
									else $html .= '<li>';
								
									if($page['url_resolver_id'] > 0) {
										$url_values = &browse('core\UrlResolver', array($page['url_resolver_id']), array('human_readable_url'));
										$url = BASE_DIR.ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');										
									}
									else $url = "index.php?show=icway_site&page_id={$id}&lang={$params['lang']}";

									$html .= '<a href="'.$url.'">'.$page['title'].'</a>';
									$html .= '</li>';
								}
								$html .= '</ul>';
							}
							return $html;
						}
));

/**
* For special pages (i.e. pages that require a dynamic content),
* we may replace renderer entries with other methods (defined in separated .php files)
*/
$page_file = 'pages/'.$values[$params['page_id']]['mnemonic'].'.php';
// we try to include the file (without testing if it exists), preventing warning/error output
@include($page_file);

// output html
if(!is_null($params['page_id']) && file_exists($template_file)) {
	$template = new SiteTemplate(file_get_contents($template_file), $renderer, $params);	
	print($template->getHtml());
}