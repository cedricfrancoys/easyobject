<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include_once('parser.inc.php');
include_once('common.inc.php');

$template = 'packages/icway/html/template_site.html';
$values = &browse('icway\Page', array($params['page_id']), array('id', 'title', 'content', 'tips_ids'), $params['lang']);

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
										$url = ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
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
* we replace renderer entries with other methods
*/
switch($params['page_id']) {
	case 5:
		// page 'blog'
		if(isset($params['cat_id'])) {
			$renderer['content'] = function($params) {
				$result = browse('icway\Category', array($params['cat_id']), array('name', 'posts_ids'), $params['lang']);
				$html = '<h1>'.$result[$params['cat_id']]['name'].'</h1>';
				$posts_ids = search('icway\Post', array(array(array('id', 'in', $result[$params['cat_id']]['posts_ids']), array('language', '=', $params['lang']))), 'created', 'desc');
				$posts_values = &browse('icway\Post', $posts_ids, array('id', 'title', 'created', 'url_resolver_id'), $params['lang']);
				// obtain related posts
				foreach($posts_values as $id => $values) {
					$dateFormatter = new DateFormatter($values['created'], DATE_TIME_SQL);
					$date = ucfirst(strftime("%B&nbsp;%Y", $dateFormatter->getTimestamp()));
					mb_detect_order(array('UTF-8', 'ISO-8859-1'));
					if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');					
					if($values['url_resolver_id'] > 0) {
						$url_values = &browse('core\UrlResolver', array($values['url_resolver_id']), array('human_readable_url'));
						$url = ltrim($url_values[$values['url_resolver_id']]['human_readable_url'], '/');
					}
					else $url = "index.php?show=icway_blog&post_id={$id}&lang={$params['lang']}";					
					$html .= '<div class="blog_entry"><a href="'.$url.'">'.$values['title'].'</a>'.'&nbsp;-&nbsp;<span class="details">'.$date.'</span></div>';
				}
				return $html;
			};
			$renderer['left_column'] = function($params) {
				// list of categories
				$html = '';
				$categories_ids = search('icway\Category', array(array(array())));
				$categories_values = &browse('icway\Category', $categories_ids, array('id', 'name', 'posts_ids'), $params['lang']);
				$html = '<h1>'.get_translation('categories', $params['lang']).'</h1>';
				$html .= '<ul>';
				foreach($categories_values as $category_values) {
					if(count($category_values['posts_ids']) > 0) {
						if($category_values['id'] == $params['cat_id']) $html .= '<li class="current">';
						else $html .= '<li>';
						$html .= '<a href="index.php?show=icway_site&page_id=5&cat_id='.$category_values['id'].'" id="'.$category_values['id'].'">'.$category_values['name'].'</a>';
						$html .= '</li>';
					}
				}
				$html .= '</ul>';
				return $html;
			};

		}
		else {
			switch($params['lang']) {
				case 'en':	$params['post_id'] = 1;
					break;
				case 'es':	$params['post_id'] = 2;
					break;
				case 'fr':	$params['post_id'] = 3;
					break;
			}
			include('blog.php');
			die();
		}
		break;
	case 7:
		// page 'resources'
		$renderer['styles'] = function($params) {
			$html = '';		
			$styles = array('packages/icway/html/css/file_cabinet.css');
			foreach($styles as $style) {
				$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />';
			}
			return $html;
		};				
		$renderer['content'] = function($params) {
			$html = '<h1>'.get_translation('resources', $params['lang']).'</h1>';
			$html .= '<div class="file_cabinet">';
			$categories_ids = search('icway\Category');
			$categories_values = &browse('icway\Category', $categories_ids, array('name'), $params['lang']);
			foreach($categories_ids as $category_id) {
				// sort resources by title (inside the current category)
				$resources_ids = search('icway\Resource', array(array(array('category_id','=',$category_id), array('language','=', $params['lang']))), 'title');
				if(!count($resources_ids)) continue;
				$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'description', 'size', 'type'), $params['lang']);
				$html .= '<div class="header"><a name="cat_'.$category_id.'"></a>'.$categories_values[$category_id]['name'].'</div>';
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
		$renderer['inline_script'] = function($params) {
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
		$renderer['styles'] = function($params) {
			$html = '';		
			$styles = array('packages/icway/html/css/article.css');
			foreach($styles as $style) {
				$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />'."\n";
			}
			return $html;
		};		
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
		$renderer['inline_script'] = function ($params) {
			$i18n = I18n::getInstance();
			$lang_details = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
			$lang_summary = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));
			$lang_back = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'icway\Page', 'object_part' => 'view', 'object_field' => 'go_back', 'field_attr' => 'label'));
			$script = '';
			$script .= "var LANG_DETAILS = '{$lang_details}';\n";
			$script .= "var LANG_SUMMARY = '{$lang_summary}';\n";
			$script .= "var LANG_BACK = '{$lang_back}';\n";
			$script .= "
				$(document).ready(function(){
					$.getScript('html/js/src/easyObject.api.js')
					.done(function() {
						$.getScript('packages/knine/html/js/knine.js')
						.done(function() {
							$('h2.knine').on('click', function() {
								var \$content = $('#article-content').children().detach();
								var \$loader = $('<div />').addClass('loader').text('Loading ...');
								$('#article-content').append(\$loader).append($('<div />').attr('id', 'content_knine'));
								$('#content_knine').knine({
									article_id: $(this).attr('id'),
									depth: 1,
									lang_summary: LANG_SUMMARY,
									lang_details: LANG_DETAILS,
									autonum: false
								});
								$('#article-content').prepend($('<a href=\"#\" />').css({'display': 'block', 'padding': '10px'}).text(LANG_BACK)
								.on('click', function() {
										$('#article-content').empty().append(\$content);
								}));
								\$loader.toggle();
							});
						});
					})
					.fail(function(jqxhr, settings, exception) {
						console.log(exception);
					});
				});
			";
			return $script;
		};
		break;
	case 13:
		// contact us
		$renderer['inline_script'] = function ($params) {
			$confirm_txt = str_replace("\n", '\n', addslashes(get_translation('subscribe_confirm', $params['lang'])));
			return "
				function submit_form() {
					var response = $.post('index.php?do=icway_add-subscriber', $('#submit_form').serialize(), function () {});
					setTimeout(function(){
						alert('{$confirm_txt}');
					}, 500);
				}
			";
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
			$html = '<h1>'.get_translation('sitemap', $params['lang']).'</h1>';
			$html .= get_pages_list(1);
			return $html;
		};
		break;
	case 17:
		// search page
		$renderer['inline_script'] = function ($params) {
			return "
				$(document).ready(function(){
					// google custom search
					$.getScript('http://www.google.com/cse/cse.js?cx='+ '004967614553816060821:aqmxxfj88ue')
					.done(function() {})
					.fail(function() {});
				});
			";
		};
		break;
	case 19:
		// albums gallery
		$renderer['styles'] = function($params) {
			$html = '';		
			$styles = array('packages/icway/html/css/picasagallery.css', 'packages/icway/html/css/fancybox.css');
			foreach($styles as $style) {
				$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />'."\n";
			}
			return $html;
		};						
		$renderer['scripts'] = function($params) {
			$html = '';		
			$scripts = array('packages/icway/html/js/jquery.fancybox.min.js', 'packages/icway/html/js/jquery.fancybox.thumbs.js');
			foreach($scripts as $script) {
				$html .= '<script type="text/javascript" src="'.$script.'"></script>'."\n";
			}
			return $html;
		};								
		$renderer['inline_script'] = function ($params) {
			return "
				$(document).ready(function(){
					$.getScript('packages/icway/html/js/jquery.picasagallery.js')
					.done(function() {
						$('<div />').picasagallery({
							username:'cedricfrancoys',
							title: 'Liste des albums',
							thumbnail_width: '160',
							inline: false,
							link_to_picasa: false,
							auto_open: false,
							hide_albums: ['Photos du profil', 'Scrapbook', 'Présentation projet']
						}).appendTo($('#article-content'));
					})
					.fail(function(jqxhr, settings, exception) {
						console.log(exception);
					});
				});
			";
		};
		break;
	case 20:
		$renderer['content'] = function ($params) {
			$values = &browse('icway\Page', array(15), array('content'), $params['lang']);
			return $values[15]['content'];
		};
		break;
}


// output html
if(!is_null($params['page_id']) && file_exists($template)) print(decorate_template(file_get_contents($template), get_html));