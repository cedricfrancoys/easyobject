<?php

namespace linnetts {

	class Workday extends \core\Object {

		public static function getColumns() {
			return array(
				'day'		=> array('type' => 'date'),
				'tasks_ids'	=> array('type' => 'one2many', 'foreign_object'	=> 'linnetts\Task', 'foreign_field' => 'workday_id')
			);
		}

	}
}