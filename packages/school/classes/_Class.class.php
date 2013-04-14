<?php
namespace school {
	// we use '_Class' (with underscore) as object name since 'class' is a PHP reserved keyword
	class _Class extends \core\Object {

		// we override the Object::getTable method in order to customize the name of the SQL table
		public function getTable() { return 'school_class'; }

		public static function getColumns() {
			return array(
					'label'			=> array('type' => 'string'),
					'lessons_ids'	=> array('type' => 'one2many', 'foreign_object' => 'school\Lesson', 'foreign_field' => 'class_id'),
					'course_id'		=> array('type' => 'many2one', 'foreign_object' => 'school\Course', 'foreign_field' => 'classes_ids'),
					'teacher_id'	=> array('type' => 'many2one', 'foreign_object' => 'school\Teacher', 'foreign_field' => 'classes_ids'),
					'students_ids'	=> array('type' => 'many2many', 'foreign_object' => 'school\Student', 'foreign_field' => 'classes_ids', 'rel_table' => 'school_rel_class_student', 'rel_foreign_key' => 'student_id', 'rel_local_key' => 'class_id'),
			);
		}
	}
}