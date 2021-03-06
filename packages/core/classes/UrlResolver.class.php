<?php
namespace core {

	class UrlResolver extends \core\Object {

		public static function getColumns() {
			return array(
				'human_readable_url'	=> array('type' => 'string', 'unique' => true),
				'complete_url'			=> array('type' => 'string'),
			);
		}
	}
}