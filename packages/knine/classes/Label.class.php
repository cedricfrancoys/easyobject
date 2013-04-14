<?php

namespace knine {

	class Label extends \core\Object {

		public static function getColumns() {
			return array(
				'name'			=> array('type' => 'string'),
				'articles_ids'	=> array('type' => 'many2many',
											'foreign_object'	=> 'knine\Article',
											'foreign_field'		=> 'labels_ids',
											'rel_table'			=> 'knine_rel_article_label',
											'rel_foreign_key'	=> 'article_id',
											'rel_local_key'		=> 'label_id'),
			);
		}

	}
}