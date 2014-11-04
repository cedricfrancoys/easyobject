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
* file: packages/knine/data/article.php
*
* Returns html code corresponding to the specified article at the specified level of detail.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode
set_silent(true);


$params = announce(	
	array(	
		'description'	=>	"Returns knine-compatible html for the specified article.",
		'params' 		=>	array(
								'article_id'	=> array(
													'description' => 'Id of the article to display..',
													'type' => 'string', 
													'required'=> true
													),
								'level'			=> array(
													'description' => 'Recursion depth.',
													'type' => 'integer', 
													'default' => 0
													),
								'autonum'		=> array(
													'description' => 'Auto-numbering chapters.',
													'type' => 'bool', 
													'default' => true
													),
								'lang'			=> array(
													'description '=> 'Specific language for multilang field.',
													'type' => 'string', 
													'default' => DEFAULT_LANG
													)
							)
	)
);

load_class('orm/I18n');
load_class('utils/DateFormatter');

$i18n = I18n::getInstance();

$lang_details = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
$lang_summary = $i18n->getClassTranslationValue('fr', array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));

/**
* Returns the html code corresponding to the specified article at the specified level of detail
*
* @param integer $article_id
* @param string $level
* @param integer $depth
* @param integer $max_depth
*/
function get_article_html($article_id, $level='', $depth=0, $max_depth=0) {
	global $params, $lang_details, $lang_summary;
	$html = '';
	$objectsManager = &ObjectManager::getInstance();

	$values = browse('knine\Article', array($article_id), array('created', 'authors_ids', 'title', 'is_root', 'children_ids', 'summary', 'content'));
	$article = $values[$article_id];

	if(strlen($level) && $params['autonum']) {echo 'ici'; $article['title'] = $level.'.&nbsp;'.$article['title'];}

	$values = browse('knine\User', $article['authors_ids'], array('firstname', 'lastname'));
	$authors = '';
	
	for($i=0, $j=count($values); $i < $j; ++$i) {
		if(strlen($authors)) {
			if($i == $j-1) $authors .= ' et ';
			else $authors .= ', ';
		}
		$user_values = array_shift($values);
		$authors .= $user_values['firstname'].' '.$user_values['lastname'];
	}

	$class_name = 'title';
	$title_details = '';

	if($article['is_root']) {
		$class_name .= ' main';
		$articleDate = new DateFormatter();
		$articleDate->setDate($article['created'], DATE_TIME_SQL);
		$article_date = $articleDate->getDate(DATE_STRING);
		$title_details	= '<br /><span style="font-size: 12px;">'.$authors.'&nbsp;('.$article_date.')</span>';
	}

	if($depth >= $max_depth) {
		$html .= '<div class="article" id="'.$article_id.'">';
		$html .= '	<div class="level">'.$level.'</div>';
		$html .= '	<div class="'.$class_name.'">'.$article['title'].$title_details.'</div>';
		$html .= '	<div class="summary">'.$article['summary'].'</div>';
		$html .= '	<div class="content" style="display: none;"></div>';
		$html .= '  <div class="display_button"><a class="summary_link" style="display: none;">'.$lang_summary.'</a><a class="details_link">'.$lang_details.'</a></div>';
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
				if(strlen($level) && $params['autonum']) $next_level = '.'.$next_level;
				$html .= get_article_html($sub_article_id, $level.$next_level, $depth+1, $max_depth);
				++$i;
			}
		}
		else {
			$html .= $article['content'];
		}
		$html .= '	</div>';
		
		if($depth > 0 && count($article['children_ids']) == 0 && strlen($article['content']) == 0) $depth = 0;
		
		if( ($depth == 0 && (count($article['children_ids']) > 0 || strlen($article['content']) > 0))
			||
			($depth > 0 && strlen($article['summary']) > 0) )			
			$html .= '  <div class="display_button"><a class="summary_link">'.$lang_summary.'</a><a class="details_link" style="display: none;">'.$lang_details.'</a></div>';
		$html .= '</div>';
	}
	return $html;
}

// We display the content of the specified article (at specified level).
print(get_article_html($params['article_id'], '', 0, $params['level']));