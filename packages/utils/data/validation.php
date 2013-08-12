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
* file: packages/utils/data/validation.php
*
* Returns the errors found in the specified package.
*
* @param string $package
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('package'));

// assign values with the received parameters
$params = get_params(array('package'=>null));

if(empty($params['package'])) die('no package specified');
else $params['package'] = strtolower($params['package']);


// get singletons instances
$om = &ObjectManager::getInstance();
$db = &DBConnection::getInstance();


// result of the tests : array containing errors (if no errors are found, array is empty)
$result = array();

/**
* TESTING FILES
*
*/

$classes_list = get_classes($params['package']);


// 1) vérifier la cohérence des descriptions dans les fichiers de définition de classe
// 2) vérifier la présence des fichiers .class.php référencés dans les définitions
// 3) vérifier la présence des fichiers view par défaut (form.default.html et list.default.html)
// 4) vérifier la présence des fichiers de traduction (.json)
// 5) vérifier la cohérence des fichiers view (.html)
// 6) vérifier la cohérence des fichiers de traduction (.lson)



foreach($classes_list as $class) {
// todo :
	// get filename containing class
	// check PHP syntax
	// check match between namespace and package
	// check match between classname and filename
	
	// get the full class name
	$class_name = $params['package'].'\\'.$class;
	// get a static instance of the class
	$object = &$om->getStatic($class_name);
	$schema = $object->getSchema();

	// 1) check fields descriptions consistency
	foreach($schema as $field => $description) {
		if(!isset($description['type'])) {
			$result[] = "Class $class: Missing 'type' attribute for field $field";
			continue;
		}
		$mandatory_attributes = array('type');
		$allowed_attributes = array('label', 'help', 'onchange');
		switch($description['type']) {
			case 'string':
			case 'short_text':
			case 'text':
				$allowed_attributes = array_merge($allowed_attributes, array('multilang', 'search'));
				break;
			case 'binary':
				$allowed_attributes = array_merge($allowed_attributes, array('multilang'));
				break;
			case 'boolean':
			case 'integer':			
			case 'float':
			case 'date':
			case 'time':
			case 'datetime':
			case 'timestamp':
				$allowed_attributes = array_merge($allowed_attributes, array('search'));
				break;
			case 'selection':
				$allowed_attributes = array_merge($allowed_attributes, array('multilang', 'search'));
				$mandatory_attributes = array_merge($mandatory_attributes, array('selection'));
				break;
			case 'many2one':
				$allowed_attributes = array_merge($allowed_attributes, array('search'));
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object'));
				break;
			case 'one2many':
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'foreign_field'));
				break;
			case 'many2many':
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'foreign_field', 'rel_table', 'rel_local_key', 'rel_foreign_key'));
				break;
			case 'related':
				$allowed_attributes = array_merge($allowed_attributes, array('store'));
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'result_type', 'path'));
				break;
			case 'function':
				$allowed_attributes = array_merge($allowed_attributes, array('store'));
				$mandatory_attributes = array_merge($mandatory_attributes, array('result_type', 'function'));
				break;
			default :
				$result[] = "Class $class: Unknown type '{$description['type']}' for field '$field'";
				continue;
		}
		if(count(array_intersect($mandatory_attributes, array_keys($schema[$field]))) < count($mandatory_attributes)) {
			$result[] = "Class $class: Missing at least one mandatory attribute for field '$field' ({$description['type']}) - mandatory attributes are : ".implode(', ', $mandatory_attributes);
		}
		$attributes = array_merge($allowed_attributes, $mandatory_attributes);
		foreach($description as $attribute => $value) {
			if(!in_array($attribute, $attributes)) {
				$result[] = "Class $class: Unknown attribute '$attribute' for field '$field' ({$description['type']}) - Possible attributes are : ".implode(', ', $attributes);
			}
			if(in_array($attribute, array('multilang', 'search')) && $value !== true && $value !== false) {
				$result[] = "Class $class: Incompatible value for attribute $attribute in field $field of type {$description['type']} (possible attributes are : true, false)";
			}
		}

	}

// todo : 2) check presence of class definition files to which some field may be related to

    // 3) check if default views are present (form.default.html and list.default.html)
    if(!is_file("packages/{$params['package']}/views/$class.form.default.html"))
		$result[] = "Class $class: missing default form view (/views/$class.form.default.html)";
    if(!is_file("packages/{$params['package']}/views/$class.list.default.html"))
		$result[] = "Class $class: missing default list view (/views/$class.list.default.html)";

// todo : 4) check if translation file are present
    // for each defined language
    // how to determine which languages are defined ?
        //if(!is_file("packages/{$params['package']}/i18n/$language/$class.json"))

}



/**
* TESTING DATABASE
*
*/

// 1) verify that the DB table exists
// 2) verify that the fields exists in DB
// 3) verify types compatibility
// 4) verify that the DB table exists

	// a) check that every declared simple field is present in the associated DB table
	// b) check that relational tables, if any, are present as well


$allowed_associations = array(
	'boolean' 		=> array('bool', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint'),
	'integer' 		=> array('tinyint', 'smallint', 'mediumint', 'int', 'bigint'),
	'string' 		=> array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'blob', 'mediumblob'),
	'short_text' 	=> array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'blob', 'mediumblob'),
	'text' 			=> array('tinytext', 'text', 'mediumtext', 'longtext', 'blob'),
	'date' 			=> array('date', 'datetime'),
	'time' 			=> array('time'),
	'datetime' 		=> array('datetime'),
	'timestamp' 	=> array('timestamp'),
	'selection' 	=> array('char', 'varchar'),
	'binary' 		=> array('blob', 'mediumblob', 'longblob'),
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
		$result[] = "Class $class: Associated table ({$object->getTable()}) does not exist in database";
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
		if(!isset($db_schema[$field])) $result[] = "Class $class: Field $field ({$schema[$field]['type']}) does not exist in table {$object->getTable()}";
		else {
		// 3) verify types compatibility
			$type = $schema[$field]['type'];
			if(in_array($type, array('function', 'related'))) $type = $schema[$field]['result_type'];
			if(!in_array($db_schema[$field]['type'], $allowed_associations[$type])) {
				$result[] = "Class $class: Non compatible type in database ({$db_schema[$field]['type']}) for field $field ({$schema[$field]['type']})";
			}
		}
	}
	// b) check that relational tables, if any, are present as well
	foreach($m2m_fields as $field) {
		// 4) verify that the DB table exists
		$table_name = $schema[$field]['rel_table'];

		if(!isset($tables[$table_name])) {
			$result[] = "Class $class: Relational table ($table_name) specified by field {$field} does not exist in database";
		}
	}
}


if(!count($result)) $result[] = "No errors found.";


// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result' => $result, 'url' => '', 'error_message_ids' => ''));