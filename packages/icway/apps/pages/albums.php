<?php

// page 'albums'

$renderer['styles'] = function($params) {
	$html = '';		
	$styles = array('packages/icway/html/css/picasagallery.css', 'packages/icway/html/css/fancybox.css');
	foreach($styles as $style) {
		$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />'."\n";
	}
	return $html;
};						
$renderer['scripts'] = function($params) {
	$html = '';		
	$scripts = array('packages/icway/html/js/jquery.fancybox.min.js', 'packages/icway/html/js/jquery.fancybox.thumbs.js');
	foreach($scripts as $script) {
		$html .= '<script type="text/javascript" src="'.$script.'"></script>'."\n";
	}
	return $html;
};								
$renderer['inline_script'] = function ($params) {
	return "
		$(document).ready(function(){
			$.getScript('packages/icway/html/js/jquery.picasagallery.js')
			.done(function() {
				$('<div />').picasagallery({
					username:'cedricfrancoys',
					title: 'Liste des albums',
					thumbnail_width: '160',
					inline: false,
					link_to_picasa: false,
					auto_open: false,
					hide_albums: ['Photos du profil','Scrapbook','Présentation projet','2014-02-06']
				}).appendTo($('#article-content'));
			})
			.fail(function(jqxhr, settings, exception) {
				console.log(exception);
			});
		});
	";
};
