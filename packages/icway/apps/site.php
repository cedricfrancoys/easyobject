<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include('parser.inc.php');
include('common.inc.php');

$template = 'packages/icway/html/template_site.html';
$values = &browse('icway\Page', array($params['page_id']), array('id', 'title', 'content', 'script', 'tips_ids'), $params['lang']);

/**
* Extend renderer array with functions specific to this app
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array_merge($renderer, array(
	'script'		=>	function ($params) use ($values) {
							return $values[$params['page_id']]['script'];
						},
	'title'			=>	function ($params) use ($values) {
							return $values[$params['page_id']]['title'];
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
							// no match found
							if(is_null($selected_id)) $selected_id = 1;
							$sections_values = &browse('icway\Section', array($selected_id), array('page_id', 'title', 'sections_ids'), $params['lang']);
							// note: this is a loop but we only have one item
							foreach($sections_values as $section_id => $section_values) {
								$html = '<h1 style="cursor: pointer;" onclick="window.location.href=\'index.php?show=icway_site&page_id='.$section_values['page_id'].'\';">'.$section_values['title'].'</h1>';
								$html .= '<ul>';
								$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('page_id', 'title'), $params['lang']);
								foreach($subsections_values as $subsection_values) {
									if($subsection_values['page_id'] == $params['page_id']) $html .= '<li class="current">';
									else $html .= '<li>';
									$html .= '<a href="index.php?show=icway_site&page_id='.$subsection_values['page_id'].'">'.$subsection_values['title'].'</a>';
									$html .= '</li>';
								}
								$html .= '</ul>';
							}
							return $html;
						}
));

/**
* For special pages (i.e. pages that require a dynamic content),
* we replace renderer entries with other methods
*/
switch($params['page_id']) {
	case 5:
		// page 'blog'		
		if(isset($params['label_id'])) {
			$renderer['content'] = function($params) {
				$html = '<h1>'.'Bienvenue sur notre blog'.'</h1>';
				$result = browse('icway\Label', array($params['label_id']), array('posts_ids'), $params['lang']);
				$posts_ids = $result[$params['label_id']]['posts_ids'];
				$posts_values = browse('icway\Post', $posts_ids, array('id', 'title', 'modified'), $params['lang']);
				// obtain related posts 
				foreach($posts_values as $id => $values) {
					$html .= '<div>'.'<a href="index.php?show=icway_blog&post_id='.$id.'">'.$values['title'].'</a>'.'</div>';
				}				
				return $html;
			};
		}
		else {
			$renderer['script'] = function($params) {
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
		}
		
		$renderer['left_column'] = function($params) {
			// list of categories
			$html = '';
			$labels_ids = search('icway\Label', array(array(array())));
			$labels_values = &browse('icway\Label', $labels_ids, array('id', 'name'), $params['lang']);
// todo: translate
			$html = '<h1>'.'Cat&eacute;gories'.'</h1>';							
			$html .= '<ul>';							
			foreach($labels_values as $label_values) {				
				if($label_values['id'] == $params['label_id']) $html .= '<li class="current">';
				else $html .= '<li>';
				$html .= '<a href="index.php?show=icway_site&page_id=5&label_id='.$label_values['id'].'" class="select_label" id="'.$label_values['id'].'">'.$label_values['name'].'</a>';
				$html .= '</li>';
			}
			$html .= '</ul>';														
			return $html;			
		};
		break;
	case 7:
		// page 'resources'
		$renderer['content'] = function($params) {
// todo: translate
			$html = '<h1>'.'Ressources'.'</h1>';
			$html .= '<div class="file_cabinet">';

			$categories_ids = search('icway\Category');
			$categories_values = &browse('icway\Category', $categories_ids, array('name'), $params['lang']);

			foreach($categories_ids as $category_id) {
				// sort resources by title (inside the current category)
				$resources_ids = search('icway\Resource', array(array(array('category_id','=',$category_id))), 'title');
				$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'description', 'size', 'type'), $params['lang']);
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
		$renderer['script'] = function($params) {
			return "
				$(document).ready(function(){
					var href = window.location.href;
					var pos = href.indexOf('#');
					if(pos > 0) {
						var res_id = href.slice(pos+1);
						$(\"a[name='\"+res_id+\"']\").css({'background-color': '#FCFE7C'});
					}
				});
			";
		};
		break;
	case 11:
		// page our convictions
		$renderer['content'] = function($params) {
			$html = '';
			// get children_ids from article 21
			$articles_values = &browse('knine\Article', array(21), array('title', 'summary', 'children_ids'), DEFAULT_LANG);
			$html .= '<h1>'.$articles_values[21]['title'].'</h1>';
			$html .= '<div>'.$articles_values[21]['summary'].'</div>';
			$articles_values = &browse('knine\Article', $articles_values[21]['children_ids'], array('title'), DEFAULT_LANG);
			foreach($articles_values as $id => $values) {
				$html .= '<h2 class="knine" id="'.$id.'"><a href="#">'.$values['title'].'</a></h2>';
			}
			return $html;
		};
		$renderer['script'] = function ($params) use ($params, $values) {
			$i18n = I18n::getInstance();
			$lang_details = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
			$lang_summary = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));
			$lang_back = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'icway\Page', 'object_part' => 'view', 'object_field' => 'go_back', 'field_attr' => 'label'));
	
			$script = '';
			$script .= "var LANG_DETAILS = '{$lang_details}';\n";
			$script .= "var LANG_SUMMARY = '{$lang_summary}';\n";	
			$script .= "var LANG_BACK = '{$lang_back}';\n";				
			$script .= $values[$params['page_id']]['script'];
			return $script;
		};
		break;		
	case 14:
		// page sitemap
		$renderer['content'] = function($params) {
			function get_pages_list($section_id) {
				global $params;
				$html = '';
				$get_page_url = function ($page_id) {
					$url = '';
					$pages_values = &browse('icway\Page', array($page_id), array('url_resolver_id'));
					foreach($pages_values as $id => $page) {
						if($page['url_resolver_id'] > 0) {
							$url_values = &browse('core\UrlResolver', array($page['url_resolver_id']), array('human_readable_url'));
							$url = ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
						}
						else $url = "index.php?show=icway_site&page_id={$id}";
					}
					return $url;
				};				
				$sections_values = &browse('icway\Section', array($section_id), array('sections_ids', 'page_id', 'title'), $params['lang']);
				foreach($sections_values as $section_id => $section_values) {
					$url = $get_page_url($section_values['page_id']);
					$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$section_values['title'].'</a>';
					$html .= '<ul>';
					$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('id', 'sections_ids', 'page_id', 'title'), $params['lang']);
					foreach($subsections_values as $subsection_values) {
						$html .= '<li>';
						if(!empty($subsection_values['sections_ids'])) $html .= get_pages_list($subsection_values['id']);
						else { 
							$url = $get_page_url($subsection_values['page_id']);
							$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$subsection_values['title'].'</a>';
						}
						$html .= '</li>';
					}
					$html .= '</ul>';
				}
				return $html;
			};
//todo: translate			
			$html = '<h1>'.'Plan du site'.'</h1>';	
			$html .= get_pages_list(1);
			return $html;
		};
		break;
}


// output html
if(!is_null($params['page_id']) && file_exists($template)) print(decorate_template(file_get_contents($template), get_html));