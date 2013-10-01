<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

include('parser.inc.php');
include('common.inc.php');

$template = 'packages/icway/html/template_blog.html';
$values = &browse('icway\Post', array($params['post_id']), array('id', 'title', 'content', 'tips_ids', 'comments_ids'));

/**
* Extend renderer array with functions specific to this app
* (i.e. translate the 'var' tags from the template)
*/
$renderer = array_merge($renderer, array(
	'title'			=>	function ($params) use ($values) {
							return $values[$params['post_id']]['title'];
						},
	'content'		=>	function ($params) use ($values) {
							return '<h1>'.$values[$params['post_id']]['title'].'</h1>'.$values[$params['post_id']]['content'];
						},
	'tips'			=>	function ($params) use ($values) {
							$html = '';
							$tips_values = &browse('icway\Tip', $values[$params['post_id']]['tips_ids'], array('content'));
							foreach($tips_values as $tip_values) {
								$html .= "<div>{$tip_values['content']}</div>";
							}
							return $html;
						},
	'comments'		=>	function ($params) use ($values) {
							$html = '';	
							$comments_values = &browse('icway\Comment', $values[$params['post_id']]['comments_ids']);
							$dateFormatter = new DateFormatter();
							foreach($comments_values as $comment) {
								$author = $comment['author'];
								$content = str_replace("\n", "<br />", $comment['content']);
								$dateFormatter->setDate($comment['created'], DATE_TIME_SQL);
								$date = $dateFormatter->getDate(DATE_STRING);
								$html .= "<li><span>par $author ($date):</span><p>$content</p></li>";
							}
							return $html;
						},
	'localizator'	=>	function ($params) {
// todo : translate
							$path = array(1 => 'Accueil', 5 => 'Blog');
							$html = '<ul>';
							foreach($path as $page_id => $page_title) {
								$html .= '<li><a href="?show=icway_site&page_id='.$page_id.'">'.$page_title.'</a></li>';
							}
							$html .= '</ul>';
							return $html;
						},
	'left_column'	=>	function ($params) {
							// display posts categories (labels)
							$html = '';
							$labels_ids = search('icway\Label', array(array(array())));
							$labels_values = &browse('icway\Label', $labels_ids, array('id', 'name'));
// todo : translate							
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
));

// output html
if(!is_null($params['page_id']) && file_exists($template)) print(decorate_template(file_get_contents($template), get_html));