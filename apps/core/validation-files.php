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

$om = &ObjectManager::getInstance();
$db = &DBConnection::getInstance();


if(empty($params['package'])) die('no package specified');
else $params['package'] = strtolower($params['package']);

$classes_list = get_classes($params['package']);
$errors = array();


// 1) vérifier la cohérence des descriptions dans les fichiers de définition de classe
// 2) vérifier la présence des fichiers .class.php référencés dans les définitions
// 3) vérifier la présence des fichiers form.default.html et list.default.html
// 4) vérifier la présence des fichiers .json
// 5) vérifier la cohérence des fichiers view
// 6) vérifier la cohérence des fichiers de traduction


foreach($classes_list as $class) {
	// get the full class name
	$class_name = $params['package'].'\\'.$class;
	// get a static instance of the class
	$object = &$om->getStatic($class_name);
	$schema = $object->getSchema();

	// 1) check fields descriptions consistency
	foreach($schema as $field => $description) {
		if(!isset($description['type'])) {
			$errors[] = "Class $class: Missing 'type' attribute for field $field";
			continue;
		}
		$mandatory_attributes = array('type');
		$allowed_attributes = array('label', 'help');
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
				$allowed_attributes = array_merge($allowed_attributes, array('search', ));
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object'));
				break;
			case 'one2many':
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'foreign_field'));
				break;
			case 'many2many':
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'foreign_field', 'rel_table', 'rel_local_key', 'rel_foreign_key'));
				break;
			case 'related':
				$mandatory_attributes = array_merge($mandatory_attributes, array('foreign_object', 'result_type', 'path'));
				break;
			case 'function':
				$mandatory_attributes = array_merge($mandatory_attributes, array('result_type', 'function'));
				break;
			default :
				$errors[] = "Class $class: Unknown type '{$description['type']}' for field '$field'";
				continue;
		}
		if(count(array_intersect($mandatory_attributes, array_keys($schema[$field]))) < count($mandatory_attributes)) {
			$errors[] = "Class $class: Missing at least one mandatory attribute for field '$field' ({$description['type']}) - mandatory attributes are : ".implode(', ', $mandatory_attributes);
		}
		$attributes = array_merge($allowed_attributes, $mandatory_attributes);
		foreach($description as $attribute => $value) {
			if(!in_array($attribute, $attributes)) {
				$errors[] = "Class $class: Unknown attribute '$attribute' for field '$field' ({$description['type']}) - Possible attributes are : ".implode(', ', $attributes);
			}
			if(in_array($attribute, array('multilang', 'search')) && $value !== true && $value !== false) {
				$errors[] = "Class $class: Incompatible value for attribute $attribute in field $field of type {$description['type']} (possible attributes are : true, false)";
			}
		}

	}

	// 2) check existence of class definition files to which some field may refer to
// todo
    // 3) check if default views are present (form.default.html et list.default.html)
    if(!is_file(getcwd()."/library/classes/objects/{$params['package']}/views/$class.form.default.html"))
		$errors[] = "Class $class: missing default form view (/views/$class.form.default.html)";
    if(!is_file(getcwd()."/library/classes/objects/{$params['package']}/views/$class.list.default.html"))
		$errors[] = "Class $class: missing default list view (/views/$class.list.default.html)";

    // 4) check if translation file are present
    // pour chaque langue définie
    // comment déterminer quelles sont les langues définies ?
        //if(!is_file(getcwd()."/library/classes/objects/{$params['package']}/i18n/$language/$class.json"))

}

if(!count($errors)) echo "No errors found.";
else
	foreach($errors as $error) {
		print($error."<br/>\n");
	}