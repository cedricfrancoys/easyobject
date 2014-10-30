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
* file: packages/knine/apps/display.php
*
* Displays an article based on the given depth.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode
set_silent(true);


check_params(array('article_id'));
$params = get_params(array('article_id'=>null, 'level'=>0));

load_class('orm/I18n');
load_class('utils/HtmlWrapper');
load_class('utils/DateFormatter');


$html = new HtmlWrapper();
$html->addCSSFile('packages/knine/html/css/article.css');
$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/src/easyobject.api.js');
$html->addJSFile('packages/knine/html/js/knine.js');

$i18n = I18n::getInstance();

$lang_details = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
$lang_summary = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));

$html->add($content = new HtmlBlock('content', 'div'));
$content->add($outer = new HtmlBlock('outer_frame', 'div'));
$outer->add($frame = new HtmlBlock('frame', 'div', null, array('class'=>'loaded')));


function get_article() {
	ob_start();	
	include('packages/knine/data/article.php');
	$data = ob_get_contents();
	ob_end_clean();
	return $data;
}

// We display the content of the specified article (at specified level).
// This could be done client-side (JS) but then content would not be indexed by search engines
$frame->add(get_article());


$html->addScript("
$(document).ready(function() {
	$('#frame').knine({
		article_id: {$params['article_id']},
		depth: {$params['level']},
		lang_summary: '{$lang_summary}',
		lang_details: '{$lang_details}',
		autonum: true
	});
});
");

print($html);