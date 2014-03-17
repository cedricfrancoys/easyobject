<?php

namespace icway {

	class Page extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string', 'multilang' => true),
				'mnemonic'			=> array('type' => 'string'),
				'url_resolver_id' 	=> array('type' => 'many2one', 'foreign_object' => 'core\UrlResolver'),
				'content'			=> array('type' => 'text', 'multilang' => true),
				'script'			=> array('type' => 'text'),				
				'html' 				=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'icway\Page::getHtml'),				
			);
		}

		public static function getHtml($om, $uid, $oid, $lang) {
			return '';
		}

	}
}