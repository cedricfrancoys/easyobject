<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include_once('common.inc.php');

$template_file = 'packages/icway/html/template_blog.html';
$values = &browse('icway\Post', array($params['post_id']), array('id', 'title', 'created', 'content', 'category_id', 'tips_ids', 'comments_ids'), $params['lang']);

$params['cat_id'] = $values[$params['post_id']]['category_id'];

/**
* Extend renderer array with functions specific to this app
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array_merge($renderer, array(
	'title'			=>	function ($params) use ($values) {
							return $values[$params['post_id']]['title'];
						},
	'content'		=>	function ($params) use ($values) {
							$html = '<h1>'.$values[$params['post_id']]['title'].'</h1>';
							$dateFormatter = new DateFormatter($values[$params['post_id']]['created'], DATE_TIME_SQL);
							$date = ucfirst(strftime("%d %B %Y", $dateFormatter->getTimestamp()));
							if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');
							if(in_array($params['post_id'], array(1,2,3))) $date = '&nbsp;';
							$html .= '<h2>'.$date.'</h2>';
							$html .= $values[$params['post_id']]['content'];
							return $html;
						},
	'tips'			=>	function ($params) use ($values) {
							$html = '';
							// display tips, if any
							$tips_values = &browse('icway\Tip', $values[$params['post_id']]['tips_ids'], array('content'), $params['lang']);
							foreach($tips_values as $tip_values) {
								$html .= "<div>{$tip_values['content']}</div>";
							}
							// publications history
							$posts_ids = search('icway\Post', array(array(array('language', '=', $params['lang']))), 'created', 'desc', 0, 25);
							$posts_values = &browse('icway\Post', $posts_ids, array('id', 'created', 'title', 'url_resolver_id'), $params['lang']);							
							$current_month ='';
							foreach($posts_values as $id => $post_values){
								$dateFormatter = new DateFormatter($post_values['created'], DATE_TIME_SQL);
								$post_month = $dateFormatter->getDate('Ym');
								if($post_month != $current_month) {
									if(strlen($current_month) > 0) $html .= '</span>';
									$date = ucfirst(strftime("%B %Y", $dateFormatter->getTimestamp()));
									if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');
									$html .= '<span><b>'.$date.'</b><br />';
									$current_month = $post_month;									
								}
								if($post_values['url_resolver_id'] > 0) {
									$url_values = &browse('core\UrlResolver', array($post_values['url_resolver_id']), array('human_readable_url'));
									$url = ltrim($url_values[$post_values['url_resolver_id']]['human_readable_url'], '/');
								}
								else $url = "index.php?show=icway_blog&post_id={$id}&lang={$params['lang']}";								
								$html .= '<a class="tip" href="'.$url.'">'.$post_values['title'].'</a><br />';
							}
							return $html;
						},
	'comments'		=>	function ($params) use ($values) {
							$html = '';
							$comments_values = &browse('icway\Comment', $values[$params['post_id']]['comments_ids'], array('author', 'created', 'content'), $params['lang']);
							$dateFormatter = new DateFormatter();
							foreach($comments_values as $comment) {
								$author = $comment['author'];
								$content = str_replace("\n", "<br />", $comment['content']);
								$dateFormatter->setDate($comment['created'], DATE_TIME_SQL);
								$date = $dateFormatter->getDate(DATE_STRING);
								$html .= "<li><span style='font-weight: bold;'>par $author ($date):</span><p>$content</p></li>";
							}
							return $html;
						},
	'localizator'	=>	function ($params) {
							$path = array(1 => get_translation('home', $params['lang']), 5 => 'Blog');
							$html = '<ul>';
							foreach($path as $page_id => $page_title) {
								$html .= '<li><a href="index.php?show=icway_site&page_id='.$page_id.'">'.$page_title.'</a></li>';
							}
							$html .= '</ul>';
							return $html;
						},
	'left_column'	=>	function ($params) {
							// display categories
							$html = '';
							$categories_ids = search('icway\Category', array(array(array())));
							$categories_values = &browse('icway\Category', $categories_ids, array('id', 'name', 'posts_ids'), $params['lang']);
							$html = '<h1>'.get_translation('categories', $params['lang']).'</h1>';
							$html .= '<ul>';
							foreach($categories_values as $category_values) {
								if(count($category_values['posts_ids']) > 0) {
									if($category_values['id'] == $params['cat_id']) $html .= '<li class="current">';
									else $html .= '<li>';
									$html .= '<a href="index.php?show=icway_site&page_id=5&cat_id='.$category_values['id'].'">'.$category_values['name'].'</a>';
									$html .= '</li>';
								}
							}
							$html .= '</ul>';
							return $html;
						},
));

// output html
if(!is_null($params['page_id']) && file_exists($template_file)) {
	$template = new SiteTemplate(file_get_contents($template_file), $renderer, $params);	
	print($template->getHtml());
}