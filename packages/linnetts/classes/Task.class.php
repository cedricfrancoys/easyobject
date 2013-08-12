<?php

namespace linnetts {

	class Task extends \core\Object {

		public static function getColumns() {
			return array(
				'start_time'	=> array('type' => 'time'),
				'end_time' 		=> array('type' => 'time'),				
				'job_id' 		=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Job'),
				'workday_id' 	=> array('type' => 'many2one', 'foreign_object' => 'linnetts\Workday'),				
				'job_title'		=> array('type' => 'related', 'result_type' => 'string', 'foreign_object' => 'linnetts\Job', 'path' => array('job_id', 'title'))
			);
		}
	}
}