<?php
namespace core {

	class Version extends \core\Object {

		public static function getColumns() {
			return array(
				'state'				=> array('type' => 'selection', 'selection' => array('draft', 'version')),
				'object_class'		=> array('type' => 'string'),
				'object_id'			=> array('type' => 'integer'),
				'serialized_value'	=> array('type' => 'text'),
			);
		}
	}

}