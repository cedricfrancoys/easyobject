<?php

namespace icway {

	class Page extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string'),
				'url_resolver_id' 	=> array('type' => 'many2one', 'foreign_object' => 'core\UrlResolver'),
				'content'			=> array('type' => 'text'),
				'script'			=> array('type' => 'text'),				
				'tips_ids'			=> array('type' => 'one2many', 'foreign_object' => 'icway\Tip', 'foreign_field' => 'page_id'),
				'html' 				=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'icway\Page::callable_getHtml'),				
			);
		}



	}
}