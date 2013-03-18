<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.

*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

Class ValidationTests {

	public function __invoke() {
			return array(
			//1xxx : calls related to the ObjectManger instance
			'1000' => array(
							'description'		=> "Trying to get an instance of the object Manager",
							'expected_result'	=> true,
							'test'				=> function (){
														$om = &ObjectManager::getInstance();
														return (is_object($om));
													},
							),
			'1100' => array(
							'description'		=> "Verifying that the ObjectManager instance is unique",
							'expected_result'	=> true,
							'test'				=> function (){
														$om1 = &ObjectManager::getInstance();
														$om2 = &ObjectManager::getInstance();
														return ($om1 === $om2);
													},
							),
			//2xxx : calls related to the browse method
			'2100' => array(
							'description'		=> "Trying to get some User object",
							'expected_result'	=> array(),
							'test'				=> function (){
														return browse('core\User', array('1'), array('language','firstname','lastname'));
													},
							),
			'2110' => array(
							'description'		=> "Trying to get all User objects",
							'expected_result'	=> array(),
							'test'				=> function (){
														return browse('core\User');
													},
							),
			'2200' => array(
							'description'		=> "Trying to browse some unexisting object",
							'expected_result'	=> UNKNOWN_OBJECT,
							'test'				=> function (){
														return browse('core\Foo', array('1'), array('language','firstname','lastname'));
													},
							),
			'2300' => array(
							'description'		=> "Calling browse method with wrong value for \$ids parameter : not an array",
							'expected_result'	=> INVALID_PARAM,
							'test'				=> function (){
														return browse('core\User', 1, array('language','firstname','lastname'));
													},
							),
			'2310' => array(
							'description'		=> "Calling browse method with empty value for \$ids parameter : empty array",
							'expected_result'	=> array(),
							'test'				=> function (){
														return browse('core\User', array(), array('language','firstname','lastname'));
													},
							),
			'2320' => array(
							'description'		=> "Calling browse method with empty value for \$ids parameter : null",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = &browse('core\User', null, array('language','firstname','lastname'));
														return $values;
													},
							),
			'2330' => array(
							'description'		=> "Calling browse method with wrong value for \$fields parameter : not an array",
							'expected_result'	=> INVALID_PARAM,
							'test'				=> function (){
														$values = &browse('core\User', array('1'), 'firstname');
														return $values;
													},
							),
			'2340' => array(
							'description'		=> "Calling browse method with wrong \$fields value : unexisting field name",
							'expected_result'	=> UNKNOWN_ERROR,
							'test'				=> function (){
														$values = &browse('core\User', array('1'), array('foo'));
														return $values;
												},
							),
			'2510' => array(
							'description'		=> "Calling browse method on related \$fields : one2many, 1 step path",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = &browse('school\Lesson', array(1), array('students_ids'));
														return $values;
													},
							),
			'2520' => array(
							'description'		=> "Calling browse method on related \$fields : one2many, 2 steps path",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = &browse('school\Lesson', array(1), array('teacher_courses_ids'));
														return $values;
													},
							),
			'2530' => array(
							'description'		=> "Calling browse method on related \$fields : many2one, 1 step path",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = &browse('school\Lesson', array(1), array('teacher_id'));
														return $values;
													},
							),
			'2540' => array(
							'description'		=> "Calling browse method on related \$fields : many2one, 2 steps path",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = &browse('school\Lesson', array(2), array('school_id'));
														return $values;
													},
							),

			//3xxx : calls related to the search method
			'3000' => array(
							'description'		=> "Trying to search for some object : clause 'ilike'",
							'expected_result'	=> true,
							'test'				=> function (){
														$values = search('knine\Article', array(array('parent_id', '=', '1'), array('id', 'in', array(1, 3, 4, 8, 15)), array('summary', 'ilike', '%Belgique%')));
														return $values;
													},
							),
			'3100' => array(
							'description'		=> "Trying to search for some object : clause 'contains' on one2many field",
							'expected_result'	=> true,
							'test'				=> function (){
														$values = search('knine\Article', array(array('attributes_ids', 'contains', array(1, 2))));
														return $values;
													},
							),
			'3110' => array(
							'description'		=> "Trying to search for some object : clause 'contains' on one2many field (using a foreign key different from 'id')",
							'expected_result'	=> true,
							'test'				=> function (){
		                                                $values = search('knine\Article', array(array('attributes_types', 'contains', array('author', 'editor'))));
														return $values;
													},
							),
			'3120' => array(
							'description'		=> "Trying to search for some object : clause 'contains' on many2one field",
							'expected_result'	=> true,
							'test'				=> function (){
														$values = search('knine\Article', array(array('parent_id', '=', '1')));
														return $values;
													},
							),
			'3120' => array(
							'description'		=> "Trying to search for some object : clause contain on many2many field",
							'expected_result'	=> true,
							'test'				=> function (){
														$values = search('knine\Article', array(array('labels_ids', 'contains', array(1, 2, 3))));
														return $values;
												}	,
							),
			'9999' => array(
							'description'		=> "tests",
							'expected_result'	=> array(),
							'test'				=> function (){
														$values = browse('core\User', array('0'));
														$user_id = $values[0]['id'];
														$values = browse('core\User', array($user_id));
														//$values = &$om->browse('school\Lesson', array(1), array('students_ids'));
														//$values = &$om->browse('school\Lesson', array(1), array('teacher_courses_ids'));
														//$values = &$om->browse('school\Lesson', array(1), array('teacher_id'));
														//$values = &$om->browse('school\Student', array(2), array('lessons_ids'));

														return $values;
													},
							),

		);
	}
}