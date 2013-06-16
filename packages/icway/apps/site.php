<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

include('parser.inc.php');

// force silent mode
set_silent(true);

check_params(array('page_id'));
$params = get_params(array('page_id'=>1));


// todo : param 'lang'
$values = &browse('icway\Page', array($params['page_id']), array('id', 'title', 'content', 'tips_ids'));

	
/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
	global $params, $values;
	$html = '';
	switch($attributes['id']) {
		case 'page_id':
			$html = $params['page_id'];
			break;
		case 'top_menu':
			$html .= "<ul>";	
			$ids = search('icway\Section', array(array(array('parent_id', '=', '1'))), 'index', 'desc', 0, 10);
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
		case 'left_columns':
			$current_page_id = $params['page_id'];
			// find sectiuon of current page (should be only one!)
			$sections_values = &browse('icway\Section', array($params['page_id']), array('parent_id'));				
			foreach($sections_values as $section_id => $section_values) {
				$parent_values = &browse('icway\Section', array($section_values['parent_id']), array('sections_ids', 'title'));				
				$html = '<h1>'.$parent_values[$section_values['parent_id']]['title'].'</h1>';
				$html .= '<ul>';
				$subsections_values = &browse('icway\Section', $parent_values[$section_values['parent_id']]['sections_ids'], array('page_id', 'title'));
				foreach($subsections_values as $subsection_values) {
					if($subsection_id == $params['page_id']) $html .= '<li class="current">';
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
	}
	return $html;
};

// output html
if(!is_null($params['page_id']) && file_exists('packages/icway/html/template_site.html')) print(decorate_template(file_get_contents('packages/icway/html/template_site.html'), $get_html));