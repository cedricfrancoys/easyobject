<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('orm/I18n');
load_class('utils/DateFormatter');

include('parser.inc.php');


// force silent mode
set_silent(true);

$params = get_params(array('page_id'=>1, 'lang'=>'fr'));


$values = &browse('icway\Page', array($params['page_id']), array('id', 'title', 'content', 'script', 'tips_ids'));

/**
* This array holds the methods to use for rendering the page
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array(
	'page_id'		=>	function () use ($params) {
							return $params['page_id'];
						},
	'title'			=>	function () use ($params, $values) {
							return $values[$params['page_id']]['title'];
						},
	'content'		=>	function () use ($params, $values) {
							return $values[$params['page_id']]['content'];
						},
	'script'		=>	function () use ($params, $values) {
							return $values[$params['page_id']]['script'];
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
									$html .= '<li><a href="index.php?show=icway_site&page_id='.$page_id.'">'.$page_title.'</a></li>';
								}
							}
							$html .= '</ul>';
							return $html;
						},
	'left_column'	=>	function () use ($params){
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
							// note: this is a loop but we only have one item
							foreach($sections_values as $section_id => $section_values) {
								$html = '<h1>'.$section_values['title'].'</h1>';
								$html .= '<ul>';
								$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('page_id', 'title'));
								foreach($subsections_values as $subsection_values) {
									if($subsection_values['page_id'] == $params['page_id']) $html .= '<li class="current">';
									else $html .= '<li>';
									$html .= '<a href="index.php?show=icway_site&page_id='.$subsection_values['page_id'].'">'.$subsection_values['title'].'</a>';
									$html .= '</li>';
								}
								$html .= '</ul>';
							}
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
								$html .= '  <a href="ressources#'.$resource_values['id'].'">'.$resource_values['title'].'</a>';
								$html .= '  <span class="details">&nbsp;&nbsp'.$dateFormatter->getDate(DATE_SQL).'&nbsp;&nbsp;|&nbsp;&nbsp;'.$type.'&nbsp;&nbsp;|&nbsp;&nbsp;'.floor($resource_values['size']/1000).'ko</span>';
								$html .= '</li>';
							}
							$html .= "</ul>";
							return $html;
						},
	'latest_posts'	=>	function () {
	
						}
);




// For special pages (i.e. pages that require a dynamic content),
// we replace renderer entries with other methods
switch($params['page_id']) {
	case 5:
		// page 'blog'
		$renderer['script'] = function() use ($params){
			return "
				$(document).ready(function(){
					$.getScript('html/js/easyObject.api.min.js')
					.done(function() {
						$('.select_label').on('click', function() {
							var label_id = $(this).attr('id');
							var posts_ids = (browse('icway\\\\Label', [label_id], ['posts_ids'], '{$params['lang']}'))[label_id]['posts_ids'];
							var posts_values = browse('icway\\\\Post', posts_ids, ['id', 'title', 'modified'], '{$params['lang']}');
							// obtain related posts 
							var title = $('#article-content > h1').detach();
							$('#article-content').empty().append(title);
							$.each(posts_values, function(id, values) {
								$('#article-content').append($('<div />').append($('<a />').attr('href', 'index.php?show=icway_blog&post_id='+id).append(values['title'])));
							});							
						});
					})
					.fail(function(jqxhr, settings, exception) {
						console.log(exception);
					});
				});
			";
		};
	
		$renderer['left_column'] = function() {
			// list of categories
			$html = '';
			$labels_ids = search('icway\Label', array(array(array())));
			$labels_values = &browse('icway\Label', $labels_ids, array('id', 'name'));
			$html = '<h1>'.'Cat&eacute;gories'.'</h1>';							
			$html .= '<ul>';							
			foreach($labels_values as $label_values) {
				$html .= '<li>';
				$html .= '<a href="#" class="select_label" id="'.$label_values['id'].'">'.$label_values['name'].'</a>';
				$html .= '</li>';
			}
			$html .= '</ul>';														
			return $html;			
		};
		
// todo: check if we received a albel_id param (in which case we have to display the associated posts in the content pane)
		break;
	case 7:
		// page 'resources'
		$renderer['content'] = function() {
			$html = '<h1>'.'Ressources'.'</h1>';
			$html .= '<div class="file_cabinet">';

			$categories_ids = search('icway\Category');
			$categories_values = &browse('icway\Category', $categories_ids, array('name'));

			foreach($categories_ids as $category_id) {
				// sort resources by title (inside the current category)
				$resources_ids = search('icway\Resource', array(array(array('category_id','=',$category_id))), 'title');
				$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'description', 'size', 'type'));
				$html .= '<div class="header">'.$categories_values[$category_id]['name'].'</div>';
				foreach($resources_values as $resource_values) {
					$dateFormatter = new DateFormatter($resource_values['modified'], DATE_TIME_SQL);
					// we use Google doc viewer for other stuff than images
					if($resource_values['type'] == 'application/pdf') $view_url = 'http://docs.google.com/viewer?url='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER['PHP_SELF'].'?get=icway_resource&mode=download&res_id='.$resource_values['id']);
					else $view_url = 'index.php?get=icway_resource&mode=view&res_id='.$resource_values['id'];

					$html .= '<div class="row">';
					$html .= '  <div class="name">';
					$html .= '    <a name="'.$resource_values['id'].'">'.$resource_values['title'].'</a><br />';
					$html .= '    <span style="word-spacing: 5px;"><a href="'.$view_url.'" target="_blank">Afficher</a>&nbsp;<a href="index.php?get=icway_resource&mode=download&res_id='.$resource_values['id'].'">Télécharger</a></span>';
					$html .= '  </div>';
					$html .= '  <div class="desc">'.$resource_values['description'].'</div>';
					$html .= '  <div class="type">'.$resource_values['type'].'</div>';
					$html .= '  <div class="size">'.floor($resource_values['size']/1000).' Ko</div>';
					$html .= '  <div class="modif">'.$dateFormatter->getDate(DATE_LITTLE_ENDIAN).'</div>';
					$html .= '</div>';
				}
			}
			$html .= '</div>';
			return $html;
		};
		break;
	case 14:
		// page sitemap
		break;
}

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
if(!is_null($params['page_id']) && file_exists('packages/icway/html/template_site.html')) print(decorate_template(file_get_contents('packages/icway/html/template_site.html'), $get_html));