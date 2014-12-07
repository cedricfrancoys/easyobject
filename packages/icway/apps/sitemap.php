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
* file: packages/icway/apps/sitemap.php
*
* Returns a 'sitemap according to sitemaps.org schema : http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
*
*/

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);


function get_pages_list($section_id) {
	$pages_ids = array();
	$sections_values = &browse('icway\Section', array($section_id), array('sections_ids', 'page_id', 'title'));
	foreach($sections_values as $section_id => $section_values) {
		$pages_ids[] = $section_values['page_id'];
		$subsections_values = &browse('icway\Section', $section_values['sections_ids'], array('id', 'sections_ids', 'page_id', 'title'));
		foreach($subsections_values as $subsection_values) {			
			if(!empty($subsection_values['sections_ids'])) $pages_ids = array_merge($pages_ids, get_pages_list($subsection_values['id']));
			else $pages_ids[] = $subsection_values['page_id'];
		}
	}
	return $pages_ids;
}

// output xml data
echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
';	
echo "<url>\n";
echo "\t<loc>".config\FClib::get_url(true, false)."</loc>\n";
echo "</url>\n";


$root_url = config\FClib::get_url(true, false);
$pages_ids = get_pages_list(1);

foreach(array('fr', 'en', 'es') as $lang) {
	$pages_values = &browse('icway\Page', $pages_ids, array('url_resolver_id'), $lang);			
	$url_ids = array_map(function($a){return $a['url_resolver_id'];}, $pages_values);
	$url_values = &browse('core\UrlResolver', $url_ids, array('human_readable_url'));
	foreach($pages_values as $page_id => $page_values) {
		// we build the sitemap only with pages having a nice URL
		if($page_values['url_resolver_id'] > 0) {
			echo "<url>\n";
			echo "\t<loc>".$root_url.ltrim($url_values[$page_values['url_resolver_id']]['human_readable_url'], '/')."</loc>\n";
			echo "</url>\n";
		}			
	}
}

echo '</urlset>';