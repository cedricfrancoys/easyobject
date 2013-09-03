<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('orm/I18n');
load_class('utils/DateFormatter');

include('parser.inc.php');


// force silent mode
set_silent(true);

$params = get_params(array('post_id'=>1, 'lang'=>'fr'));


$values = &browse('icway\Post', array($params['post_id']), array('id', 'title', 'content', 'tips_ids'));

/**
* This array holds the methods to use for rendering the page
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array(
	'post_id'		=>	function () use ($params) {
							return $params['post_id'];
						},
	'title'			=>	function () use ($params, $values) {
							return $values[$params['post_id']]['title'];
						},
	'content'		=>	function () use ($params, $values) {
							return '<h1>'.$values[$params['post_id']]['title'].'</h1>'.$values[$params['post_id']]['content'];
						},
	'tips'			=>	function () use ($params, $values) {
							$html = '';
							$tips_values = &browse('icway\Tip', $values[$params['post_id']]['tips_ids'], array('content'));
							foreach($tips_values as $tip_values) {
								$html .= "<div>{$tip_values['content']}</div>";
							}
							return $html;
						},
	'top_menu'		=>	function () {
							$html = "<ul>";
							$ids = search('icway\Section', array(array(array('parent_id', '=', '1'), array('in_menu', '=', '1'))), 'sequence', 'desc');
							if(!count($ids)) break;
							$sections_values = &browse('icway\Section', $ids, array('title', 'page_id'));
							foreach($sections_values as $section_values) {
								$title = mb_strtoupper($section_values['title'], 'UTF-8');
								$html .= "<li><a href=\"index.php?show=icway_site&page_id={$section_values['page_id']}\">$title</a></li>";
							}
							$html .= "</ul>";
							return $html;
						},
	'localizator'	=>	function () use ($params){
// todo : manage translations here
							$path = array(1 => 'Accueil', 5 => 'Blog');
							$html = '<ul>';
							foreach($path as $page_id => $page_title) {
								$html .= '<li><a href="?show=icway_site&page_id='.$page_id.'">'.$page_title.'</a></li>';
							}
							$html .= '</ul>';
							return $html;
						},
	'left_column'	=>	function () use ($params){
							// display posts categories (labels)
							$html = '';
							$labels_ids = search('icway\Label', array(array(array())));
							$labels_values = &browse('icway\Label', $labels_ids, array('id', 'name'));
							$html = '<h1>'.'Cat&eacute;gories'.'</h1>';							
							$html .= '<ul>';							
							foreach($labels_values as $label_values) {
								$html .= '<li>';
								$html .= '<a href="index.php?show=icway_site&page_id=5&label_id='.$label_values['id'].'">'.$label_values['name'].'</a>';
								$html .= '</li>';
							}
							$html .= '</ul>';														
							return $html;
						},
	'latest_docs'	=>	function () {
							$html = "<ul>";
							// sort resources by title (inside the current category)
							$resources_ids = search('icway\Resource', array(array(array())), 'modified', 'desc', 0, 3);
							$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'description', 'size', 'type'));
							foreach($resources_values as $resource_values) {
								$dateFormatter = new DateFormatter($resource_values['modified'], DATE_TIME_SQL);
								// we use Google doc viewer for other stuff than images
								list($mode, $type) = explode('/', $resource_values['type']);
								$html .= '<li>';
								$html .= '  <a href="">'.$resource_values['title'].'</a>';
								$html .= '  <span class="details">&nbsp;&nbsp'.$dateFormatter->getDate(DATE_SQL).'&nbsp;&nbsp;|&nbsp;&nbsp;'.$type.'&nbsp;&nbsp;|&nbsp;&nbsp;'.floor($resource_values['size']/1000).'ko</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;
						}
);



/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) use ($renderer, $params) {

		if(isset($renderer[$attributes['id']])) return $renderer[$attributes['id']]();
		else {
			$html = '';
			if(isset($attributes['translate']) && in_array($attributes['translate'], array('yes', 'on', 'true', '1'))) {
				$i18n = I18n::getInstance();
				$html = $i18n->getClassTranslationValue($params['lang'], array(
													'object_class'	=> 'icway\Page',
													'object_part'	=> 'view',
													'object_field'	=> $attributes['id'],
													'field_attr'	=> 'label')
												);
			}
			return $html;
		}
};

// output html
if(!is_null($params['post_id']) && file_exists('packages/icway/html/template_blog.html')) print(decorate_template(file_get_contents('packages/icway/html/template_blog.html'), $get_html));