<?php
namespace school {

	class Lesson extends \core\Object {

		public static function getColumns() {
			return array(
				'day_of_week'			=> array('type' => 'integer'),
				'start_time'			=> array('type' => 'time'),
				'end_time'				=> array('type' => 'time'),
				'class_id'				=> array('type' => 'many2one', 'foreign_object' => 'school\_Class'),
				'students_ids'			=> array('type' => 'related', 'result_type' => 'one2many', 'foreign_object' => 'school\Student', 'path' => array('class_id', 'students_ids')),
				'teacher_courses_ids'	=> array('type' => 'related', 'result_type' => 'one2many', 'foreign_object' => 'school\Course', 'path' => array('class_id', 'teacher_id', 'courses_ids')),
				'teacher_id'			=> array('type' => 'related', 'result_type' => 'many2one', 'foreign_object' => 'school\Teacher', 'path' => array('class_id', 'teacher_id')),
				'school_id'				=> array('type' => 'related', 'result_type' => 'many2one', 'foreign_object' => 'school\School', 'path' => array('class_id', 'course_id', 'school_id')),
			);
		}
	}
}