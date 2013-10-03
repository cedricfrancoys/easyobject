<?php

namespace icway {

	class Category extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'multilang' => true),
				'resources_ids'		=> array('type' => 'one2many', 'foreign_object'	=> 'icway\Resource', 'foreign_field' => 'category_id')
			);
		}

	}
}