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
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* file: actions/core/objects/browse.php
*
* Returns the fields values of the object's given ids.
*
* @param string $class
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('package'));

// assign values with the received parameters
$params = get_params(array('package'=>null));


$classes_list = get_classes($params['package']);


$om = &ObjectManager::getInstance();
$db = &DBConnection::getInstance();

$errors = array();

$allowed_associations = array(
	'boolean' 		=> array('bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint'),
	'integer' 		=> array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'),
	'string' 		=> array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'),
	'short_text' 	=> array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'),
	'text' 			=> array('tinytext', 'text', 'mediumtext', 'longtext'),
	'date' 			=> array('date', 'datetime'),
	'time' 			=> array('time'),
	'datetime' 		=> array('datetime'),
	'timestamp' 	=> array('timestamp'),
	'selection' 	=> array('char', 'varchar'),
	'binary' 		=> array('mediumblob', 'blob', 'longblob'),
	'many2one' 		=> array('int')
);

// load database tables
$tables = array();
$res = $db->sendQuery("show tables;");
while($row = $db->fetchRow($res)) {
	$tables[$row[0]] = true;
}


foreach($classes_list as $class) {
	// get the full class name
	$class_name = $params['package'].'\\'.$class;
	// get a static instance of the class
	$object = &$om->getStatic($class_name);
	$schema = $object->getSchema();

	// 1) verify that the DB table exists
	if(!isset($tables[$object->getTable()])) {
		$errors[] = "Class $class: Associated table ({$object->getTable()}) does not exist in database";
		continue;
	}

	// load DB schema
	$db_schema = array();
	$res = $db->sendQuery("show full columns from `{$object->getTable()}`;");
	while($row = $db->fetchArray($res)) {
		// we dont need the length, if present
		$db_type = explode('(', $row['Type']);
		$db_schema[$row['Field']] = array('type'=>$db_type[0]);
	}

	$simple_fields = array();
	$m2m_fields = array();
	foreach($schema as $field => $description) {
		if(in_array($description['type'], $om->simple_types)) $simple_fields[] = $field;
		// handle the 'store' attrbute
		else if(in_array($description['type'], array('function', 'related'))) {
			if(isset($description['store']) && $description['store']) $simple_fields[] = $field;
		}
		else if($description['type'] == 'many2many') $m2m_fields[] = $field;
	}
	// a) check that every declared simple field is present in the associated DB table
	foreach($simple_fields as $field) {
		// 2) verify that the fields exists in DB
		if(!isset($db_schema[$field])) $errors[] = "Class $class: Field $field ({$schema[$field]['type']}) does not exist in table {$object->getTable()}";
		else {
		// 3) verify types compatibility
			if(!in_array($db_schema[$field]['type'], $allowed_associations[$schema[$field]['type']])) {
				$errors[] = "Class $class: Non compatible type in database ({$db_schema[$field]['type']}) for field $field ({schema[$field]['type']})";
			}
		}
	}
	// b) check that relational tables, if any, are present as well
	foreach($m2m_fields as $field) {
		// 4) verify that the DB table exists
		$table_name = $schema[$field]['rel_table'];

		if(!isset($tables[$table_name])) {
			$errors[] = "Class $class: Relational table ($table_name) specified by field {$field} does not exist in database";
		}
	}
}

if(!count($errors)) echo "No errors found.";
else
	foreach($errors as $error) {
		print($error."<br/>\n");
	}