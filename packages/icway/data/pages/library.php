<?php

// page 'resources'


$renderer['styles'] = function($params) {
	$html = '';		
	$styles = array('packages/icway/html/css/file_cabinet.css');
	foreach($styles as $style) {
		$html .= '<link media="all" rel="stylesheet" type="text/css" href="'.$style.'" />';
	}
	return $html;
};				

$renderer['content'] = function($params) {
// todo: complete array if necessary
	$types_associations = array('text/plain'=>'txt', 'text/html'=>'html', 'application/pdf'=>'pdf', 'application/msword'=>'doc', 'application/rtf'=>'rtf', 'application/zip'=>'zip', 'application/vnd.ms-excel'=>'xls','image/jpeg'=>'jpeg','image/png'=>'png');	
	$html = '<h1>'.get_translation('resources', $params['lang']).'</h1>';
	$html .= '<div class="file_cabinet">';
	$categories_ids = search('icway\Category');
	$categories_values = &browse('icway\Category', $categories_ids, array('name'), $params['lang']);
	foreach($categories_ids as $category_id) {
		// sort resources by title (inside the current category)
		$resources_ids = search('icway\Resource', array(array(array('category_id','=',$category_id), array('language','=', $params['lang']))), 'title');
		if(!count($resources_ids)) continue;
		$resources_values = &browse('icway\Resource', $resources_ids, array('id', 'modified', 'title', 'author', 'description', 'size', 'pages', 'type'), $params['lang']);
		$html .= '<div class="header"><a name="cat_'.$category_id.'"></a>'.$categories_values[$category_id]['name'].'</div>';
		foreach($resources_values as $resource_values) {
			$dateFormatter = new DateFormatter($resource_values['modified'], DATE_TIME_SQL);
			// we use Google doc viewer for other stuff than images
			if($resource_values['type'] == 'application/pdf') $view_url = 'http://docs.google.com/viewer?url='.urlencode('http://'.$_SERVER["SERVER_NAME"].$_SERVER['PHP_SELF'].'?get=icway_resource&mode=download&res_id='.$resource_values['id']);
			else $view_url = 'index.php?get=icway_resource&mode=view&res_id='.$resource_values['id'];				
			$html .= '<div class="row">';
			$html .= '  <div class="name">';
			$html .= '    <a name="'.$resource_values['id'].'">'.$resource_values['title'].'</a><br />';
			$html .= '    <span style="word-spacing: 5px;"><a href="'.$view_url.'" target="_blank">Afficher</a>&nbsp;<a href="index.php?get=icway_resource&mode=download&res_id='.$resource_values['id'].'">Télécharger</a></span>';
			$html .= '  </div>';
			$html .= '  <div class="desc">'.$resource_values['description'].'</div>';
			$html .= '  <div class="type">'.$types_associations[$resource_values['type']].'</div>';
			$html .= '  <div class="pages">'.$resource_values['pages'].' p.</div>';					
			$html .= '  <div class="size">'.floor($resource_values['size']/1000).' Ko</div>';					
			// $html .= '  <div class="modif">'.$dateFormatter->getDate(DATE_LITTLE_ENDIAN).'</div>';
			$html .= '  <div class="author">'.$resource_values['author'].'</div>';					
			$html .= '</div>';
		}
	}
	$html .= '</div>';
	return $html;
};

$renderer['inline_script'] = function($params) {
	return "
		$(document).ready(function(){
			var href = window.location.href;
			var pos = href.indexOf('#');
			if(pos > 0) {
				var res_id = href.slice(pos+1);
				$(\"a[name='\"+res_id+\"']\").css({'background-color': '#FCFE7C'});
			}
		});
	";
};
