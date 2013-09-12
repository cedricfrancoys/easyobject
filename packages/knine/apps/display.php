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

	if(strlen($level)) $article['title'] = $level.'.&nbsp;'.$article['title'];

	$values = browse('knine\User', array($article['creator']), array('firstname', 'lastname'));
	$creator_name	= $values[$article['creator']]['firstname'].' '.$values[$article['creator']]['lastname'];

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
		$html .= '	<div class="level">'.$level.'</div>';
		$html .= '	<div class="'.$class_name.'">'.$article['title'].$title_details.'</div>';
		$html .= '	<div class="summary">'.$article['summary'].'</div>';
		$html .= '	<div class="content" style="display: none;"></div>';
		$html .= '  <div class="display_button"><a class="summary_link" style="display: none;" href="javascript:void(null);">'.$lang_summary.'</a><a class="details_link" href="javascript:void(null);">'.$lang_details.'</a></div>';
		$html .= '</div>';
	}
	else {
		$html .= '<div class="article" id="'.$article_id.'">';
		$html .= '	<div class="level">'.$level.'</div>';
		$html .= '	<div class="'.$class_name.'">'.$article['title'].$title_details.'</div>';
		$html .= '	<div class="summary" style="display: none;">'.$article['summary'].'</div>';
		$html .= '	<div class="content loaded">';
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
		$html .= '  <div class="display_button"><a class="summary_link" href="javascript:void(null);">'.$lang_summary.'</a><a class="details_link" style="display: none;" href="javascript:void(null);">'.$lang_details.'</a></div>';
		$html .= '</div>';
	}
	return $html;
}

// We display the content of the specified article (at specified level).
// This could be done client-side (JS) but then content would not be indexed by search engines
$frame->add(get_article_html($params['article_id'], '', 0, $params['level']));


$html->addScript("
$(document).ready(function() {
	$('#frame').knine({
		article_id: {$params['article_id']},
		depth: {$params['level']},
		lang_summary: '{$lang_summary}', 
		lang_details: '{$lang_details}'
	});
});
");

print($html);