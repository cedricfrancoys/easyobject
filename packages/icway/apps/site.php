<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

include('parser.inc.php');

// force silent mode
set_silent(true);

check_params(array('page_id'));
$params = get_params(array('page_id'=>null));

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
				$html .= "<li><a href=\"index.php?show=icway_site&page_id={$section_values['page_id']}\">{$section_values['title']}</a></li>";
			}
			$html .= "</ul>";			
			break;
		case 'title':
			$html = $values[$params['page_id']]['title'];
			break;
		case 'content':
			$html = $values[$params['page_id']]['content'];
			break;
		case 'tips':
			$tips_values = &browse('icway\Tip', $values[$params['page_id']]['tips_ids']);
			foreach($tips_values as $tip_values) {
				$html .= "<div>{$tip_values['content']}</div>";
			}
			break;
	}
	return $html;
};

// output html
if(!is_null($params['age_id']) && file_exists('packages/icway/html/template_site.html')) print(decorate_template(file_get_contents('packages/icway/html/template_site.html'), $get_html));