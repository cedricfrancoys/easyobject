<?php

// page 'sitemap'

$renderer['content'] = function($params) {
	function get_pages_list($section_id) {
		global $params;
		$html = '';
		$get_page_url = function ($page_id) {
			$url = '';
			$pages_values = &browse('icway\Page', array($page_id), array('url_resolver_id'));
			foreach($pages_values as $id => $page) {
				if($page['url_resolver_id'] > 0) {
					$url_values = &browse('core\UrlResolver', array($page['url_resolver_id']), array('human_readable_url'));
					$url = ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
				}
				else $url = "index.php?show=icway_site&page_id={$id}";
			}
			return $url;
		};
		$sections_values = &browse('icway\Section', array($section_id), array('sections_ids', 'page_id', 'title'), $params['lang']);
		foreach($sections_values as $section_id => $section_values) {
			$url = $get_page_url($section_values['page_id']);
			$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$section_values['title'].'</a>';
			$html .= '<ul>';
			$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('id', 'sections_ids', 'page_id', 'title'), $params['lang']);
			foreach($subsections_values as $subsection_values) {
				$html .= '<li>';
				if(!empty($subsection_values['sections_ids'])) $html .= get_pages_list($subsection_values['id']);
				else {
					$url = $get_page_url($subsection_values['page_id']);
					$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$subsection_values['title'].'</a>';
				}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
		return $html;
	};
	$html = '<h1>'.get_translation('sitemap', $params['lang']).'</h1>';
	$html .= get_pages_list(1);
	return $html;
};
