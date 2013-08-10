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
* file: apps/fivecents/category.php
*
* Displays a page with a list of articles from the specified category.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

include('common.inc.php');

// force silent mode
set_silent(true);


check_params(array('category_id'));
$params = get_params(array('category_id'=>null));


	
/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
	global $params;
	$html = '';
	switch($attributes['id']) {
		case 'scripts':
			$html = "
				$(document).ready(function() {
					$('p.comments').toggle();
					$('ul.commentlist').toggle();			
					$('li.related').toggle();			
				});
			";
			break;
		case 'content':
			// obtain the list of the articles of the category
			$category_values = &browse('fivecents\Label', array($params['category_id']), array('id', 'name', 'posts_ids'));
			$posts_values = &browse('fivecents\Post', $category_values[$params['category_id']]['posts_ids'], array('id', 'title', 'url_resolver_id'));
			$html = "<h3>Articles dans <i>{$category_values[$params['category_id']]['name']}</i></h3><br />";
			foreach($posts_values as $id => $post) {
				if($post['url_resolver_id'] > 0) {
					$url_values = &browse('core\UrlResolver', array($post['url_resolver_id']), array('human_readable_url'));
					$human_url = ltrim($url_values[$post['url_resolver_id']]['human_readable_url'], '/');
					$html .= "<a href=\"$human_url\">".$post['title']."</a><br />";
				}
				else $html .= "<a href=\"index.php?show=fivecents_display&post_id={$id}\">".$post['title']."</a><br />";
			}
			break;
		case 'recent_posts':
			$ids = search('fivecents\Post', array(array(array())), 'created', 'desc', 0, 5);
			$posts_values = &browse('fivecents\Post', $ids, array('id', 'title', 'url_resolver_id'));
			foreach($posts_values as $id => $post) {
				if($post['url_resolver_id'] > 0) {
					$url_values = &browse('core\UrlResolver', array($post['url_resolver_id']), array('human_readable_url'));
					$human_url = ltrim($url_values[$post['url_resolver_id']]['human_readable_url'], '/');
					$html .= "<li><a href=\"$human_url\">".$post['title']."</a></li>";
				}
				else $html .= "<li><a href=\"index.php?show=fivecents_display&post_id={$id}\">".$post['title']."</a></li>";
			}
			break;
	}
	return $html;
};


// output html
if(!is_null($params['category_id']) && file_exists('packages/fivecents/html/template.html')) print(decorate_template(file_get_contents('packages/fivecents/html/template.html'), $get_html));