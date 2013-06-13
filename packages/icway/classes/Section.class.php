<?php

namespace icway {

	class Section extends \core\Object {

		public static function getColumns() {
			return array(
				'index'			=> array('type' => 'integer'),
				'page_id'		=> array('type' => 'many2one', 'foreign_object' => 'icway\Page'),
				'parent_id'		=> array('type' => 'many2one', 'foreign_object' => 'icway\Section'),				
				'sections_ids'	=> array('type' => 'one2many', 'foreign_object' => 'icway\Section', 'foreign_field' => 'parent_id'),
				'title'			=> array('type' => 'related', 'result_type' => 'string',
									'foreign_object' => 'icway\Page',
									'path'	=> array('page_id','title')
								)
				
			);
		}

	}
}