<?php
// page 'convictions'

$renderer['styles'] = function($params) {
	$html = '';		
	$styles = array('packages/icway/html/css/article.css');
	foreach($styles as $style) {
		$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />'."\n";
	}
	return $html;
};		
$renderer['content'] = function($params) {
	$html = '';
	// get children_ids from article 21
	$articles_values = &browse('knine\Article', array(21), array('title', 'summary', 'children_ids'), DEFAULT_LANG);
	$html .= '<h1>'.$articles_values[21]['title'].'</h1>';
	$html .= '<div>'.$articles_values[21]['summary'].'</div>';
	$articles_values = &browse('knine\Article', $articles_values[21]['children_ids'], array('title'), DEFAULT_LANG);
	foreach($articles_values as $id => $values) {
		$html .= '<h2 class="knine" id="'.$id.'"><a href="#">'.$values['title'].'</a></h2>';
	}
	return $html;
};
$renderer['inline_script'] = function ($params) {
	$i18n = I18n::getInstance();
	$lang_details = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'more', 'field_attr' => 'label'));
	$lang_summary = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'knine\Article', 'object_part' => 'view', 'object_field' => 'less', 'field_attr' => 'label'));
	$lang_back = $i18n->getClassTranslationValue($params['lang'], array('object_class' => 'icway\Page', 'object_part' => 'view', 'object_field' => 'go_back', 'field_attr' => 'label'));
	$script = '';
	$script .= "var LANG_DETAILS = '{$lang_details}';\n";
	$script .= "var LANG_SUMMARY = '{$lang_summary}';\n";
	$script .= "var LANG_BACK = '{$lang_back}';\n";
	$script .= "
		$(document).ready(function(){
			$.getScript('html/js/src/easyObject.api.js')
			.done(function() {
				$.getScript('packages/knine/html/js/knine.js')
				.done(function() {
					$('h2.knine').on('click', function() {
						var \$content = $('#article-content').children().detach();
						var \$loader = $('<div />').addClass('loader').text('Loading ...');
						$('#article-content').append(\$loader).append($('<div />').attr('id', 'content_knine'));
						$('#content_knine').knine({
							article_id: $(this).attr('id'),
							depth: 1,
							lang_summary: LANG_SUMMARY,
							lang_details: LANG_DETAILS,
							autonum: false
						});
						$('#article-content').prepend($('<a href=\"#\" />').css({'display': 'block', 'padding': '10px'}).text(LANG_BACK)
						.on('click', function() {
								$('#article-content').empty().append(\$content);
						}));
						\$loader.toggle();
					});
				});
			})
			.fail(function(jqxhr, settings, exception) {
				console.log(exception);
			});
		});
	";
	return $script;
};