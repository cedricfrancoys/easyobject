<?php
namespace school {

	class School extends \core\Object {

		public static function getColumns() {
			return array(
				'name'			=> array('type' => 'string'),
				'courses_ids'	=> array('type' => 'one2many', 'foreign_object' => 'school\Course', 'foreign_field' => 'school_id')
			);
		}
	}
}