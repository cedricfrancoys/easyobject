<?php

namespace fivecents {

	class Comment extends \core\Object {

		public static function getColumns() {
			return array(
				'author'	=> array('type' => 'string'),
				'email'		=> array('type' => 'string'),
				'content'	=> array('type' => 'short_text'),
				'post_id'	=> array('type' => 'many2one', 'foreign_object' => 'fivecents\Post'),
			);
		}

	}
}