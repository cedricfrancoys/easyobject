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

// we'll need to format some dates
load_class('utils/DateFormatter');
// our blog is in french
setlocale(LC_ALL, 'fr_FR', 'fr', 'FRA');

check_params(array('post_id'));
$params = get_params(array('post_id'=>null));

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
		case 'post_id':
			$html = $params['post_id'];
			break;
		case 'recent_posts':
			$ids = search('fivecents\Post', array(array(array())), 'created', 'desc', 0, 5);
			$recent_values = &browse('fivecents\Post', $ids, array('id', 'title'));
			foreach($recent_values as $values) {
				$title = $values['title'];
				$id = $values['id'];
				$html .= "<li><a href=\"index.php?show=fivecents_display&post_id={$id}\">$title</a></li>";
			}
			break;
		case 'content':
			$title = $post_values[$params['post_id']]['title'];
			$content = $post_values[$params['post_id']]['content'];
			$dateFormatter = new DateFormatter();
			$dateFormatter->setDate($post_values[$params['post_id']]['created'], DATE_TIME_SQL);
			$date = ucfirst(strftime("%A %d %B %Y", $dateFormatter->getTimestamp()));
			$html = "
				<h2 class=\"title\">$title</h2>
				<div class=\"meta\"><p>$date</p></div>
				<div class=\"entry\">$content</div>
			";			
			break;
		case 'related_posts':
			$related_posts_values = &browse('fivecents\Post', $post_values[$params['post_id']]['related_posts_ids'], array('id', 'title'));
			foreach($related_posts_values as $values) {
				$title = $values['title'];
				$id = $values['id'];
				$html .= "<li><a href=\"index.php?show=fivecents_display&post_id={$id}\">$title</a></li>";
			}
			break;
		case 'comments':
			$comments_values = &browse('fivecents\Comment', $post_values[$params['post_id']]['comments_ids']);
			$dateFormatter = new DateFormatter();
			foreach($comments_values as $comment) {
				$author = $comment['author'];
				$content = $comment['content'];
				$dateFormatter->setDate($comment['created'], DATE_TIME_SQL);
				$date = $dateFormatter->getDate(DATE_STRING);
				$html .= "<li><span>par $author ($date):</span><p>$content</p></li>";
			}
			break;
	}
	return $html;
};

// output html
if(!is_null($params['post_id']) && file_exists('packages/fivecents/html/template.html')) print(decorate_template(file_get_contents('packages/fivecents/html/template.html'), $get_html));