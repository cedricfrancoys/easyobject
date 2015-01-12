<?php
/**
	This script allows to pick a page among the sections of the current website.
*/

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


check_params(array('CKEditorFuncNum'));
$params = get_params(array('CKEditorFuncNum'=>null, 'lang'=>$_SESSION['LANG']));

// force silent mode
set_silent(true);
load_class('utils/HtmlWrapper');


$html = new HtmlWrapper();
$html->addJSFile('packages/core/html/js/jquery-1.7.1.min.js');
$html->addStyle("
	a {
		display: block;
		color: #005596 !important;
		font-size: 9pt;
		font-family: trebuchet ms;
	}
	a:link, a:visited {
		text-decoration: none;
	}
	a:hover {
		text-decoration: underline;
	}  
");

$html->addScript("
	function select_page(URL) {
		var CKEditorFuncNum = {$params['CKEditorFuncNum']};
		var dialog = window.opener.CKEDITOR.dialog.getCurrent();
        dialog.setValueOf('info','url',URL);  
        dialog.setValueOf('info','protocol','');
		self.close();
	}
");



function get_page_url($page_id, $lang) {
	$url = '';
	$pages_values = &browse('icway\Page', array($page_id), array('url_resolver_id'));
	foreach($pages_values as $id => $page) {
		$url = "index.php?show=icway_site&page_id={$id}&lang={$lang}";
/*
// note: UrlResolver could be change and therefore make the link invalid	
		if($page['url_resolver_id'] > 0) {
			$url_values = &browse('core\UrlResolver', array($page['url_resolver_id']), array('human_readable_url'));
			$url = ltrim($url_values[$page['url_resolver_id']]['human_readable_url'], '/');
		}
		else $url = "index.php?show=icway_site&page_id={$id}";
*/		
	}
	return $url;
}

function get_pages_list($section_id) {
	global $params;
	$html = '';
	$sections_values = &browse('icway\Section', array($section_id), array('sections_ids', 'page_id', 'title'), $params['lang']);
	foreach($sections_values as $section_id => $section_values) {
		$url = get_page_url($section_values['page_id']);
		$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$section_values['title'].'</a>';
		$html .= '<ul>';
		$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('id', 'sections_ids', 'page_id', 'title'), $params['lang']);
		foreach($subsections_values as $subsection_values) {
			$html .= '<li>';
			if(!empty($subsection_values['sections_ids'])) $html .= get_pages_list($subsection_values['id']);
			else { 
				$url = get_page_url($subsection_values['page_id'], $params['lang']);
				$html .= '<a href="#" onclick="javascript:select_page(\''.$url.'\');">'.$subsection_values['title'].'</a>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
	}
	return $html;
}

 
$html->add(get_pages_list(1));

print($html);