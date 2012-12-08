<?php
namespace core {

	class Log extends \core\Object {

		public static function getColumns() {
			return array(
				'action'			=> array('type' => 'selection', 'selection' => array('R_CREATE' => R_CREATE, 'R_READ' => R_READ, 'R_WRITE' => R_WRITE, 'R_DELETE' => R_DELETE)),
				'object_class'		=> array('type' => 'string'),
				'object_id'			=> array('type' => 'integer'),
				'object_field'		=> array('type' => 'short_text'),
			);
		}
	}
}