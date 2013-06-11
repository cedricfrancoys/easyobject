<?php
namespace school {

	class Teacher extends \core\Object {

		public static function getColumns() {
			return array(
				'firstname'		=> array('type' => 'string'),
				'lastname'		=> array('type' => 'string'),
				'courses_ids'	=> array('type' => 'many2many', 'foreign_object' => 'school\Course', 'foreign_field' => 'teachers_ids', 'rel_table' => 'school_rel_course_teacher', 'rel_foreign_key' => 'course_id', 'rel_local_key' => 'teacher_id'),
				'classes_ids'	=> array('type' => 'one2many', 'foreign_object' => 'school\_Class', 'foreign_field' => 'teacher_id'),
			);
		}
	}
}