<?php

namespace icway {

	class Label extends \core\Object {

		public static function getColumns() {
			return array(
				'name'		=> array('type' => 'string'),
				'posts_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'icway\Post',
											'foreign_field'		=> 'labels_ids',
											'rel_table'			=> 'icway_rel_post_label',
											'rel_foreign_key'	=> 'post_id',
											'rel_local_key'		=> 'label_id'),
			);
		}

	}
}