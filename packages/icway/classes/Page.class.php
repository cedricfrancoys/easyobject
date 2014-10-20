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
			$om->update($uid, 'icway\Page', array($oid), 
						array(
								'html' => NULL
						), $lang);
		}

		public static function getHtml($om, $uid, $oid, $lang) {
			$get_include_contents = function ($filename) {
				ob_start();
				include($filename); 
				return ob_get_clean();
			};
			return $get_include_contents('packages/icway/data/page-html.php');
		}

	}
}