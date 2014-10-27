<?php
// page 'blog'
if(isset($params['cat_id'])) {
	$renderer['content'] = function($params) {
		$result = browse('icway\Category', array($params['cat_id']), array('name', 'posts_ids'), $params['lang']);
		$html = '<h1>'.$result[$params['cat_id']]['name'].'</h1>';
		$posts_ids = search('icway\Post', array(array(array('id', 'in', $result[$params['cat_id']]['posts_ids']), array('language', '=', $params['lang']))), 'created', 'desc');
		$posts_values = &browse('icway\Post', $posts_ids, array('id', 'title', 'created', 'url_resolver_id'), $params['lang']);
		// obtain related posts
		foreach($posts_values as $id => $values) {
			$dateFormatter = new DateFormatter($values['created'], DATE_TIME_SQL);
			$date = ucfirst(strftime("%B&nbsp;%Y", $dateFormatter->getTimestamp()));
			mb_detect_order(array('UTF-8', 'ISO-8859-1'));
			if(mb_detect_encoding($date) != 'UTF-8') $date = mb_convert_encoding($date, 'UTF-8');					
			if($values['url_resolver_id'] > 0) {
				$url_values = &browse('core\UrlResolver', array($values['url_resolver_id']), array('human_readable_url'));
				$url = ltrim($url_values[$values['url_resolver_id']]['human_readable_url'], '/');
			}
			else $url = "index.php?show=icway_blog&post_id={$id}&lang={$params['lang']}";					
			$html .= '<div class="blog_entry"><a href="'.$url.'">'.$values['title'].'</a>'.'&nbsp;-&nbsp;<span class="details">'.$date.'</span></div>';
		}
		return $html;
	};
	$renderer['left_column'] = function($params) {
		// list of categories
		$html = '';
		$categories_ids = search('icway\Category');
		$categories_values = &browse('icway\Category', $categories_ids, array('id', 'name', 'posts_ids'), $params['lang']);
		$html = '<h1>'.get_translation('categories', $params['lang']).'</h1>';
		$html .= '<ul>';
		foreach($categories_values as $category_values) {
			if(count($category_values['posts_ids']) > 0) {
				if($category_values['id'] == $params['cat_id']) $html .= '<li itemprop="keywords" class="current">';
				else $html .= '<li>';
				$html .= '<a href="'.BASE_DIR.'index.php?show=icway_site&page_id=5&cat_id='.$category_values['id'].'&lang='.$params['lang'].'">'.$category_values['name'].'</a>';
				$html .= '</li>';
			}
		}
		$html .= '</ul>';
		return $html;
	};
}
else {
	// if no cat_id is specified
	switch($params['lang']) {
		case 'en':	$params['post_id'] = 1;
			break;
		case 'es':	$params['post_id'] = 2;
			break;
		case 'fr':	$params['post_id'] = 3;
			break;
	}
	// we request the blog app
	header('Location: '.BASE_DIR.'index.php?show=icway_blog&'.http_build_query($params));
	exit();
}