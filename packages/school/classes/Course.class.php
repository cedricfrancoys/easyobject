<?php
namespace school {

	class Course extends \core\Object {

		public static function getColumns() {
			return array(
				'label'			=> array('type' => 'string'),
				'school_id'		=> array('type' => 'many2one', 'foreign_object' => 'school\School'),
				'teachers_ids'	=> array('type' => 'many2many', 'foreign_object' => 'school\Teacher', 'foreign_field' => 'courses_ids', 'rel_table' => 'school_rel_course_teacher', 'rel_foreign_key' => 'teacher_id', 'rel_local_key' => 'course_id'),
				'classes_ids'	=> array('type' => 'one2many', 'foreign_object' => 'school\_Class', 'foreign_field' => 'course_id'),
			);
		}
	}
}