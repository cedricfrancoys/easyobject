<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* file: apps/fivecents/display.php
*
* Displays a post entry according to the template design.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

include('common.inc.php');

// force silent mode
set_silent(true);

// we'll need to format some dates (for posts and comments)
load_class('utils/DateFormatter');
// our blog is in french
setlocale(LC_ALL, 'fr_FR', 'fr_FR.UTF-8');

// check_params(array('post_id'));
$params = get_params(array('post_id'=>1));

$post_values = &browse('fivecents\Post', array($params['post_id']), array('id', 'created', 'title', 'content', 'comments_ids', 'related_posts_ids'));

/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
	global $params, $post_values;
	$html = '';
	switch($attributes['id']) {
		case 'social_meta':
			load_class('utils/HtmlToText');
			$htmlText = new HtmlToText($post_values[$params['post_id']]['content']);
			$description = substr($htmlText->get_text(), 0, 300).'...';
			$html = '
			<meta property="og:locale" content="fr_FR"/>
			<meta property="og:type" content="article"/>
			<meta property="og:title" content="'.$post_values[$params['post_id']]['title'].'"/>
			<meta property="og:description" content="'.$description.'"/>
			<meta property="og:url" content="'.config\FClib::get_url().'"/>
			<meta property="og:site_name" content="fivecents.org"/>
			<meta property="article:author" content="http://www.facebook.com/cedric.francoys"/>
			<meta name="twitter:card" content="summary"/>
			<meta name="twitter:site" content="@cedricfrancoys"/>
			<meta name="twitter:domain" content="fivecents.org"/>
			<meta name="twitter:creator" content="@cedricfrancoys"/>
			';
			break;
		case 'post_id':
			$html = $params['post_id'];
			break;
		case 'recent_posts':	
			$ids = search('fivecents\Post', array(array(array())), 'created', 'desc', 0, 10);
			if(count($ids) > 0) {
				$html .= '<h2 class="widgettitle">Derniers articles</h2>';
				$html .= '<ul>';			
				$posts_values = &browse('fivecents\Post', $ids, array('id', 'title', 'url_resolver_id'));
				foreach($posts_values as $id => $post) {
					if($post['url_resolver_id'] > 0) {
						$url_values = &browse('core\UrlResolver', array($post['url_resolver_id']), array('human_readable_url'));
						$human_url = ltrim($url_values[$post['url_resolver_id']]['human_readable_url'], '/');
						$html .= "<li><a href=\"$human_url\">".$post['title']."</a></li>";
					}
					else $html .= "<li><a href=\"index.php?show=fivecents_display&post_id={$id}\">".$post['title']."</a></li>";
				}
				$html .= '</ul>';
			}			
			break;
		case 'content':
			$title = $post_values[$params['post_id']]['title'];
			$content = $post_values[$params['post_id']]['content'];
			$dateFormatter = new DateFormatter();
			$dateFormatter->setDate($post_values[$params['post_id']]['created'], DATE_TIME_SQL);
			// $date = ucfirst(strftime("%A %d %B %Y", $dateFormatter->getTimestamp()));
			$date = ucfirst(strftime("%A %d %B", $dateFormatter->getTimestamp()));
			$html = "
				<h2 class=\"title\">$title</h2>
				<div class=\"meta\"><p>$date</p></div>
				<div class=\"entry\">$content</div>
			";
			break;
		case 'related_posts':
			if(count($post_values[$params['post_id']]['related_posts_ids']) > 0) {
				$html .= '<h2 class="widgettitle">Articles associés</h2>';
				$html .= '<ul>';					
				$posts_values = &browse('fivecents\Post', $post_values[$params['post_id']]['related_posts_ids'], array('id', 'title', 'url_resolver_id'));			
				foreach($posts_values as $id => $post) {
					if($post['url_resolver_id'] > 0) {
						$url_values = &browse('core\UrlResolver', array($post['url_resolver_id']), array('human_readable_url'));
						$human_url = ltrim($url_values[$post['url_resolver_id']]['human_readable_url'], '/');
						$html .= "<li><a href=\"$human_url\">".$post['title']."</a></li>";
					}
					else $html .= "<li><a href=\"index.php?show=fivecents_display&post_id={$id}\">".$post['title']."</a></li>";
				}
				$html .= '</ul>';							
			}
			break;
		case 'comments':
			$comments_values = &browse('fivecents\Comment', $post_values[$params['post_id']]['comments_ids']);
			$dateFormatter = new DateFormatter();
			foreach($comments_values as $comment) {
				$author = $comment['author'];
				$content = str_replace("\n", "<br />", $comment['content']);
				$dateFormatter->setDate($comment['created'], DATE_TIME_SQL);
				$date = $dateFormatter->getDate(DATE_STRING);
				$html .= "<li><span style='font-weight: bold;'>par $author ($date):</span><p>$content</p></li>";
			}
			break;
	}
	return $html;
};

// output html
if(!is_null($params['post_id']) && file_exists('packages/fivecents/html/template.html')) print(decorate_template(file_get_contents('packages/fivecents/html/template.html'), $get_html));