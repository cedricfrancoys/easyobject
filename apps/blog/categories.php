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
* file: apps/blog/categories.php
*
* Displays a page with a list of existing categories (each item is a link to the categogy.php app).
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

include('common.inc.php');

// force silent mode
set_silent(true);


/**
* Returns html part specified by $attributes (from a 'var' tag) and associated with current post id
* (here come the calls to easyObject API)
*
* @param array $attributes
*/
$get_html = function ($attributes) {
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
			$html = '<h3>Cat&eacute;gories</h3><br />';
			// obtain the list of all categories
			$categories_ids = search('blog\Label');
			$categories_values = &browse('blog\Label', $categories_ids, array('id', 'name', 'posts_ids'));
			foreach($categories_values as $id => $category) {
				$count = count($category['posts_ids']);
				$html .= "<a href=\"index.php?show=blog_category&category_id={$id}\">".$category['name'].' ('.$count.')'."</a><br />";
			}
			$html .= '<br />';
			break;
		case 'recent_posts':
			$ids = search('blog\Post', array(array(array())), 'created', 'desc', 0, 5);
			$recent_values = &browse('blog\Post', $ids, array('id', 'title'));
			foreach($recent_values as $values) {
				$title = $values['title'];
				$id = $values['id'];
				$html .= "<li><a href=\"index.php?show=blog_display&post_id={$id}\">$title</a></li>";
			}
			break;
	}
	return $html;

};


// output html
if(file_exists('html/blog/template.html')) print(decorate_template(file_get_contents('html/blog/template.html'), $get_html));