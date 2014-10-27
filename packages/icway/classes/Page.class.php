<?php

namespace icway {

	class Page extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string', 'multilang' => true, 'onchange' => 'icway\Page::onchangePage'),
				'mnemonic'			=> array('type' => 'string'),
				'url_resolver_id' 	=> array('type' => 'many2one', 'foreign_object' => 'core\UrlResolver', 'multilang' => true),
				'content'			=> array('type' => 'text', 'multilang' => true, 'onchange' => 'icway\Page::onchangePage'),
				'script'			=> array('type' => 'text', 'onchange' => 'icway\Page::onchangePage'),
				'html' 				=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'icway\Page::getHtml', 'multilang' => true),
			);
		}
		
		public static function onchangePage($om, $uid, $oid, $lang) {
			// since almost every page is somehow related to the others, at each change, we update the entire website
			$pages_ids = $om->search($uid, 'icway\Page');		
			$om->update($uid, 'icway\Page', $pages_ids, 
						array(
								'html' => NULL
						), $lang);
		}

		public static function getHtml($om, $uid, $oid, $lang) {
			// we call the page-html data provider to obtain html of the current page in the specified language
			$get_include_contents = function ($filename) {
				ob_start();
				include($filename); 
				return ob_get_clean();
			};
			return $get_include_contents('packages/icway/data/page-html.php');
		}

	}
}