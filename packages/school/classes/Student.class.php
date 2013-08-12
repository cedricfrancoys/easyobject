<?php
namespace school {

	class Student extends \core\Object {

		public static function getColumns() {
			return array(
					'firstname'		=> array('type' => 'string', 'label' => 'Firstname', 'help' => 'Student\'s firstname'),
					'lastname'		=> array('type' => 'string', 'label' => 'Lastname', 'help' => 'Student\'s lastname'),
					'birthdate'		=> array('type' => 'date', 'label' => 'Birthdate', 'help' => 'Accepted formats : YYY-mm-dd or dd-mm-YYY as a string or inside an array having \'day\', \'month\' and \'year\' indexes set'),
					'subscription'	=> array('type' => 'date'),
					'classes_ids'	=> array('type' => 'many2many', 'label' => 'Classes', 'foreign_object' => 'school\_Class', 'foreign_field' => 'students_ids', 'rel_table' => 'school_rel_class_student', 'rel_foreign_key' => 'class_id', 'rel_local_key' => 'student_id'),
					'lessons_ids'	=> array('type' => 'related', 'result_type' => 'one2many', 'foreign_object' => 'school\Lesson', 'path' => array('classes_ids', 'lessons_ids')),
			);
		}

		public static function getDefaults() {
			return array(
					'subscription'	=> 'school\Student::default_subscription',
					'birthdate'		=> function() { return '2000-01-01'; },
			);
		}

		public static function default_subscription() {
			return date("Y-m-d");
		}

		public static function getInheritance() {
			return array('classes_ids');
		}

	}
}