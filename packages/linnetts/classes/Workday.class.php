<?php

namespace linnetts {

	class Workday extends \core\Object {

		public static function getColumns() {
			return array(
				'day'		=> array('type' => 'date'),
				// 'tasks_ids'	=> array('type' => 'one2many', 'foreign_object'	=> 'linnetts\Task', 'foreign_field' => 'workday_id'),
				'jobs_ids' 	=> array('type' => 'many2many', 'foreign_object' => 'linnetts\Job', 'foreign_field' => 'workdays_ids', 'rel_table' => 'linnetts_rel_workday_job', 'rel_foreign_key' => 'job_id', 'rel_local_key' => 'workday_id')
			);
		}

	}
}