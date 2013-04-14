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
* file: views/core/user/login.php
*
* Displays the logon screen.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode
//set_silent(true);


check_params(array('article_id'));
$params = get_params(array('article_id'=>null, 'level'=>0));

load_class('orm/I18n');
load_class('utils/HtmlWrapper');
load_class('utils/DateFormatter');


$html = new HtmlWrapper();
$html->addCSSFile('packages/knine/html/css/article.css');
$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/src/easyobject.api.js');

$i18n = I18n::getInstance();

$lang_details = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
$lang_summary = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));


$html->add($content = new HtmlBlock('content', 'div'));
$content->add($outer = new HtmlBlock('outer_frame', 'div'));
$outer->add($frame = new HtmlBlock('frame', 'div'));

/**
* Returns the html code corresponding to the specified article at the specified level of detail
*
* @param integer $article_id
* @param string $level
* @param integer $depth
* @param integer $max_depth
*/
function get_article_html($article_id, $level='', $depth=0, $max_depth=0) {
	global $lang_details, $lang_summary;
	$html = '';
	$objectsManager = &ObjectManager::getInstance();

	$values = browse('knine\Article', array($article_id), array('created', 'creator', 'title', 'is_root', 'children_ids', 'summary', 'content'));
	$article = $values[$article_id];

	if(strlen($level)) $article['title'] =  $level.'.&nbsp;'.$article['title'];

	$values = browse('knine\User', array($article['creator']), array('firstname', 'lastname'));
	$creator_name	= $values[$article['creator']]['firstname'].' '.$values[$article['creator']]['lastname'];

	$edit_link = '';
	$class_name = 'title';
	$title_details = '';

	if($article['is_root']) {
		$class_name .= ' main';
		$articleDate = new DateFormatter();
		$articleDate->setDate($article['created'], DATE_TIME_SQL);
		$article_date = $articleDate->getDate(DATE_STRING);
		$title_details	= '<br />&nbsp;&nbsp;<span style="font-size: 12px;">par '.$creator_name.'&nbsp;('.$article_date.')</span>';
	}

	if($depth >= $max_depth) {
		$html .= '<div class="article" id="'.$article_id.'">';
		$html .= '	<div class="level"></div>';
		$html .= '	<div class="'.$class_name.'">'.$article['title'].$title_details.'&nbsp&nbsp;'.$edit_link.'</div>';
		$html .= '	<div class="summary">'.$article['summary'].'</div>';
		$html .= '	<div class="content control_hidden"></div>';
		$html .= '  <div class="display_button"><a class="display_link" href="javascript:void(null);">'.$lang_details.'</a></div>';
		$html .= '</div>';
	}
	else {
		$html .= '<div class="article loaded" id="'.$article_id.'">';
		$html .= '	<div class="level"></div>';
		$html .= '	<div class="'.$class_name.'">'.$article['title'].$title_details.'&nbsp&nbsp;'.$edit_link.'</div>';
		$html .= '	<div class="summary control_hidden">'.$article['summary'].'</div>';
		$html .= '	<div class="content">';
		if(count($article['children_ids']) > 0) {
			$i = 1;
			foreach($article['children_ids'] as $sub_article_id) {
	            $next_level = $i;
				if(strlen($level)) $next_level = '.'.$next_level;
				$html .= get_article_html($sub_article_id, $level.$next_level, $depth+1, $max_depth);
				++$i;
			}
		}
		else {
			$html .= $article['content'];
		}
		$html .= '	</div>';
		$html .= '  <div class="display_button"><a class="collapse_link" href="javascript:void(null);">'.$lang_summary.'</a></div>';
		$html .= '</div>';
	}
	return $html;
}

// content could be dynamically loaded (i.e. in JS) but then it would not be ideal for search engines content indexers
$frame->add(get_article_html($params['article_id'], '', 0, $params['level']));


$html->addScript("
var LANG_ARTICLE_SUMMARY = '{$lang_summary}', LANG_ARTICLE_DETAIL = '{$lang_details}';

$(document).ready(function() {
	$('a.display_link').toggle(expand_click, collapse_click);
	$('a.collapse_link').toggle(collapse_click, expand_click);
});

function expand_click(eventObject) {
	var elem_article = $(this).parent().parent();
	var parent_article_id = elem_article.attr('id');
	var parent_article_content = elem_article.find('div.content');
	var parent_article_summary = elem_article.find('div.summary');
	var parent_level = elem_article.find('div.level').text();

	if(!elem_article.hasClass('loaded')) {
		ids = search('knine\\\\Article', [[['parent_id', '=', parent_article_id]]], 'id', 'asc', 0, 30, 'en');
		items = browse('knine\\\\Article', ids, ['content', 'summary'], 'en');
		var counter = 1;
		if(items.length == 0) {
			// get the content
			values = browse('knine\\\\Article', [parent_article_id], ['content', 'summary'], 'en');
			parent_article_content[0].innerHTML = values[parent_article_id]['content'];
		}
		else {
			$.each(items, function(i,item){
				var level = parent_level + counter + '.';
				var sub_article_level = $('<div/>').addClass('level').text(level);
				var sub_article_title = $('<div/>').addClass('title').text(level + ' ' + item.title).append('&nbsp;&nbsp;');
				var sub_article_summary = $('<div/>').addClass('summary').html(item.summary);
				var sub_article_content = $('<div/>').addClass('content').addClass('control_hidden');
				var sub_article_display_button = $('<div/>').addClass('display_button').append($('<a/>').attr('href','javascript:void(null);').addClass('display_link').text(LANG_ARTICLE_DETAIL).toggle(expand_click, collapse_click));
				var sub_article = $('<div/>')
									.addClass('article')
									.attr('id', item.id)
									.append(sub_article_level)
									.append(sub_article_title)
									.append(sub_article_summary)
									.append(sub_article_content)
									.append(sub_article_display_button);
				parent_article_content.append(sub_article);
				++counter;
			});
		}
		elem_article.addClass('loaded');
	}
	parent_article_summary.toggleClass('control_hidden');
	parent_article_content.toggleClass('control_hidden');
	$(this).text(LANG_ARTICLE_SUMMARY);
}

function collapse_click(eventObject) {
	$(this).parent().parent().find('div.summary').toggleClass('control_hidden');
	$(this).parent().parent().find('div.content').toggleClass('control_hidden');
	$(this).text(LANG_ARTICLE_DETAIL);
}

");

print($html);