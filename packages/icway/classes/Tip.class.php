<?php

namespace icway {

	class Tip extends \core\Object {

		public static function getColumns() {
			return array(
				'memo'		=> array('type' => 'string'),
				'content'	=> array('type' => 'text'),
				'page_id'	=> array('type' => 'many2one', 'foreign_object' => 'icway\Page'),
			);
		}

	}
}