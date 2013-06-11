<?php

namespace knine {

	class Author extends \core\Object {

		public static function getColumns() {
			return array(
				'firstname'			=> array('type' => 'string'),
				'lastname'			=> array('type' => 'string'),
				'publications_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'knine\Publication',
											'foreign_field'		=> 'authors_ids',
											'rel_table'			=> 'knine_rel_publication_author',
											'rel_foreign_key'	=> 'publication_id',
											'rel_local_key'		=> 'author_id'),
			);
		}

	}
}