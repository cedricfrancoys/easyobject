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
* file: packages/icway/apps/sitemap.php
*
* Returns a 'sitemap according to sitemaps.org schema : http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
*
*/

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

load_class('utils/HtmlTemplate');

// get content of the html template
$template_file = 'packages/icway/html/template_plaquette.html';

$renderer = array(
	'menu'		=>	function ($params) {
							$html = '';
							$chapters_values = &browse('knine\Article', array(41), array('children_ids'));
							$chapters_titles = &browse('knine\Article', $chapters_values[41]['children_ids'], array('title', 'children_ids'));
							foreach($chapters_titles as $id => $item) {
								$sub_values = &browse('knine\Article', array($id), array('children_ids'));
								$sub_titles = &browse('knine\Article', $sub_values[$id]['children_ids'], array('title'));
								$html .= '<li><a article_id="'.$id.'">'.$item['title'].'</a>';
								if(count($sub_titles) > 0) {
									$html .= '<ul class="chapters">';
									foreach($sub_titles as $sub_id => $sub_item) {
										$html .= '<li><a article_id="'.$id.'" chapter_id="'.$sub_id.'">'.$sub_item['title'].'</a></li>';
									}
									$html .= '</ul>';
								}
								$html .= '</li>';
							}	
							return $html;
						},
	'script'		=>	function ($params) {
							return "
							$(document).ready(function(){
								ddsmoothmenu.init({
									mainmenuid: 'menu', 
									orientation: 'v', 
									classname: 'menu-v', 
									contentsource: 'markup'
								});
							
								$('li a').on('click', function() {
									var \$link = \$main_link = $(this);
									if(\$link.is('[article_id]')) {									
										if(\$link.is('[chapter_id]')) \$main_link = \$link.parent().parent().parent().find('a').first();
										$('.chapters').animate({height:'hide', opacity:'hide'}, 1);										
										if($('#menu li a.active').get(0) != \$main_link.get(0)) {
											$('#menu li a.active').removeClass('active');
																			
											$('#content').empty().append($('<div />').attr('id', 'loader').addClass('loader').text('Chargement ...'));

											$('<div />').attr('id', 'content_knine')
											.knine({
												article_id: \$link.attr('article_id'),
												depth: 1,
												lang_summary: 'Résumer',
												lang_details: 'En savoir plus',										
												autonum: false
											})
											.on('ready', function() {
												$('#loader').remove();
												$('#content').append($(this));
												if(\$link.is('[chapter_id]')) $('html, body').animate({scrollTop: $('#'+\$link.attr('chapter_id')).offset().top}, 2000);
											});										
										}
										else {
											if(\$link.is('[chapter_id]')) $('html, body').animate({scrollTop: $('#'+\$link.attr('chapter_id')).offset().top}, 2000);
										}
										\$main_link.addClass('active');
									}
								});
							});
							";
						}
);

if(file_exists($template_file)) {
	$template = new HtmlTemplate(file_get_contents($template_file), $renderer);	
	print($template->getHtml());
}