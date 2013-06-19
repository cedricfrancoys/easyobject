<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('orm/I18n');
include('parser.inc.php');

// force silent mode
set_silent(true);

check_params(array('page_id'));
$params = get_params(array('page_id'=>1, 'lang'=>'fr'));

$i18n = I18n::getInstance();
$values = &browse('icway\Page', array($params['page_id']), array('id', 'title', 'content', 'tips_ids'));

	
/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
	global $params, $values, $i18n;
	$html = '';
	switch($attributes['id']) {
		case 'page_id':
			$html = $params['page_id'];
			break;
		case 'top_menu':
			$html .= "<ul>";	
			$ids = search('icway\Section', array(array(array('parent_id', '=', '1'))), 'sequence', 'desc', 0, 10);
			if(!count($ids)) break;
			$sections_values = &browse('icway\Section', $ids, array('title', 'page_id'));
			foreach($sections_values as $section_values) {
				$title = mb_strtoupper($section_values['title'], 'UTF-8');
				$html .= "<li><a href=\"index.php?show=icway_site&page_id={$section_values['page_id']}\">$title</a></li>";
			}
			$html .= "</ul>";			
			break;
		case 'title':
			$html = $values[$params['page_id']]['title'];
			break;
		case 'content':
			$html = $values[$params['page_id']]['content'];
			break;
		case 'localizator':
			$path = array();
			// recurse to the root section
			$sections_ids = search('icway\Section', array(array(array('page_id', '=', $params['page_id']))));	
			while(count($sections_ids)) {
				$sections_values = &browse('icway\Section', $sections_ids, array('parent_id', 'title', 'page_id'));
				foreach($sections_values as $section_id => $section_values) {
					array_unshift($path, array($section_values['page_id'] => $section_values['title']));
					$sections_ids = array($section_values['parent_id']);
					if($section_values['parent_id'] == 0) break 2;					
 				}
 			} 			
			$html = '<ul>';
			for($i = 0, $j = count($path); $i < $j; $i++) {
				foreach($path[$i] as $page_id => $page_title) {
					$html .= '<li><a href="?show=icway_site&page_id='.$page_id.'">'.$page_title.'</a></li>';
				}
			}
			$html .= '</ul>';					
			break;
		case 'left_column':
			// find level-2 section of current page 
			$sections_ids = search('icway\Section', array(array(array('page_id', '=', $params['page_id']))));			
			$selected_id = null;			
			while(count($sections_ids)) {
				$sections_values = &browse('icway\Section', $sections_ids, array('parent_id'));
				foreach($sections_values as $section_id => $section_values) {
					if($section_values['parent_id'] == 0) break 2;
					$selected_id = $section_id;
					$sections_ids = array($section_values['parent_id']);
 				}
 			} 			
			// no match found
			if(is_null($selected_id)) $selected_id = 1;
			$sections_values = &browse('icway\Section', array($selected_id), array('title', 'sections_ids'));				
			foreach($sections_values as $section_id => $section_values) {
				$html = '<h1>'.$section_values['title'].'</h1>';
				$html .= '<ul>';
				$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('page_id', 'title'));
				foreach($subsections_values as $subsection_values) {
					if($subsection_values['page_id'] == $params['page_id']) $html .= '<li class="current">';
					else $html .= '<li>';
					$html .= '<a href="?show=icway_site&page_id='.$subsection_values['page_id'].'">'.$subsection_values['title'].'</a>';
					$html .= '</li>';
				}
				$html .= '</ul>';					
			}
			break;			
		case 'tips':
			$tips_values = &browse('icway\Tip', $values[$params['page_id']]['tips_ids'], array('content'));
			foreach($tips_values as $tip_values) {
				$html .= "<div>{$tip_values['content']}</div>";
			}
			break;
		default:
			if(isset($attributes['translate']) && in_array($attributes['translate'], array('on', 'yes', 'true'))) {
				$html = $i18n->getClassTranslationValue($params['lang'], array(
													'object_class'	=> 'icway\Page',
													'object_part'	=> 'view',
													'object_field'	=> $attributes['id'],
													'field_attr'	=> 'label')
												);
			}
			break;
	}
	return $html;
};

// output html
if(!is_null($params['page_id']) && file_exists('packages/icway/html/template_site.html')) print(decorate_template(file_get_contents('packages/icway/html/template_site.html'), $get_html));