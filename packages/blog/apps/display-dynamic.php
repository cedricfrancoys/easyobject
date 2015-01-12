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
* file: apps/blog/display-dynamic.php
*
* Displays a post entry according to the template design.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/HtmlWrapper');

check_params(array('post_id'));
$params = get_params(array('post_id'=>null));

$html = new HtmlWrapper();
$html->addJSFile('packages/core/html/js/jquery-1.7.1.min.js');
$html->addJSFile('packages/core/html/js/src/easyObject.api.js');
$html->addCSSFile('packages/blog/html/css/style.css');
$html->addScript("
var post_id = {$params['post_id']};
$(document).ready(function() {
	var html = '';
	$.ajax({
		type: 'GET',
		url: 'packages/blog/html/template.html',
		async: false,
		dataType: 'html',
		contentType: 'application/html; charset=utf-8',
		success: function(data){
			html = data;
		},
		error: function(e){}
	});
	var template = $('<div/>').append(html).find('#body');	
	// replace var tags with their associated contents
	template.find('var').each(function() {
		var id = $(this).attr('id');
		switch(id) {
			case 'content':
				var post_values = browse('blog\\\\Post', [post_id], ['id', 'created', 'title', 'content']);			
				if(!$.isEmptyObject(post_values)) {
					var title = post_values[post_id]['title'];
					var content = post_values[post_id]['content'];					
					var date = new Date(post_values[post_id]['created'].substr(0,4), parseInt(post_values[post_id]['created'].substr(5,2))-1, post_values[post_id]['created'].substr(8,2), 0, 0, 0, 0);
					$(this)
					.before($('<h2/>').addClass('title').html(title))
					.before($('<div/>').addClass('meta').append($('<p/>').text(date.toDateString())))
					.before($('<div/>').addClass('entry').html(content));		
				}
				break;
			case 'recent_posts':
				var ids = search('blog\\\\Post', [[[]]], 'created', 'desc', 0, 5);
				if(!$.isEmptyObject(ids)) {				
					var recent_values = browse('blog\\\\Post', ids, ['id', 'title']);
					var self = $(this);
					$.each(recent_values, function(i, values){
						var title = values['title'];
						var id = values['id'];					
						self.before($('<li/>').append($('<a/>').attr('href', 'index.php?show=blog_display&post_id='+id).html(title)));		
					});			
				}
				break;
		};		
	});
	$('body').append(template.children());
});
");

print($html);