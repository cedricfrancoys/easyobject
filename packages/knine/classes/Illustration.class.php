<?php
/**
 * KNINE php library
 *
 * Illustration class
 *
 */

namespace knine {

	class Illustration extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string'),
				'comment'			=> array('type' => 'short_text'),
				'file_id'			=> array('type' => 'many2one', 'foreign_object' => 'knine\File'),
				'article_id'		=> array('type' => 'many2one', 'foreign_object' => 'knine\Article'),
			);
		}

	}
}