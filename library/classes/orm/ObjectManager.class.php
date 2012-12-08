<?php
/**
*	This file is part of the easyObject project.
*	http://www.cedricfrancoys.be/easyobject
*
*	Copyright (C) 2012  Cedric Francoys
*
*	This program is free software: you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation, either version 3 of the License, or
*	(at your option) any later version.
*
*	This program is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*	GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined('__FC_LIB') or die(__FILE__.' requires fc.lib.php');

include_file('config.inc.php')  or die('unable to load mandatory file config.inc.php');

load_class('db/DBConnection') or die('unable to load mandatory class DBConnection');
load_class('orm/IdentificationManager') or die('unable to load mandatory class IdentificationManager');
load_class('orm/ErrorHandler') or die('unable to load mandatory class ErrorHandler');
load_class('orm/Object') or die('unable to load mandatory class Object');



/**
* @name		ObjectManager class
* @version	1.0
* @package	core
* @author	Cedric Francoys
*/
class ObjectManager {
/**
*
* Private vars
*
*/
	private $objectsArray;
	private $dbConnection;

	public $simple_types	= array('boolean', 'integer', 'string', 'short_text', 'text', 'date', 'time', 'datetime', 'timestamp', 'selection', 'binary', 'many2one');
	public $complex_types	= array('one2many', 'many2many', 'related', 'function');

/**
*
* Private methods
*
*/
	private function __construct() {
		// initialize the objects array
		$this->objectsArray = array();
		// initialize error handler
		new ErrorHandler();
		// open DB connection
		$this->dbConnection = &DBConnection::getInstance(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_DBMS);
	}


	/**
	*
	*	 methods related to objects instances
	*	 ************************************
	*/

	/**
	* Returns a DB id for a new instance
	* checks in the DB for the first available id (i.e. one that has not been modified since creation and whose validity has expired)
	* Some of the exiting records might not being use : this methods helps to recycle the ids that can be reused
	*
	* @param string $object_class
	*/
	private function getNewObjectId($user_id, $object_class) {
		$object_id = 0;
		$object_table = $this->getObjectTableName($object_class);
		// list ids of records having no modifier set (i.e. : records created but not stored since then)
		$ids = $this->search($user_id, $object_class, array(array(array('modifier', '=', 0),array('created', '<', date("Y-m-d H:i:s", time()-(3600*24*DRAFT_VALIDITY))))), 'id', 'asc');
		if(count($ids)) $object_id = $ids[0];
		$creation_array = array('created' => date("Y-m-d H:i:s"), 'creator' => $user_id);
		if($object_id  > 0) {
			// store the id to reuse and delete the associated record
			$creation_array['id'] = $object_id;
			$this->dbConnection->deleteRecords($object_table, array($object_id));
		}
		// create a new record with the found walue, or let the autoincrement do the job
		$this->dbConnection->addRecords($object_table, array_keys($creation_array), array(array_values($creation_array)));
		if($object_id <= 0) $object_id = $this->dbConnection->getLastId();
		return $object_id;
	}

	/**
	* Returns a static instance for the specified object class (does not create a new object)
	*
	* @param string $object_class
	*/
	private function &getObjectStaticInstance($object_class) {
		try {
			// if it hasn't already be done, load the file containing the class declaration of the requested object
			if(!class_exists($object_class)) {
				// first, read the file content to see if the class extends from another, which could not be loaded yet
				// (we do so because we cannot use __autoload mechanism since the script may be run in CLI SAPI)
				$file_content = file_get_contents('classes/objects/'.$this->getObjectClassFileName($object_class).'.class.php', FILE_USE_INCLUDE_PATH);
				preg_match('/\bextends\b(.*)\{/iU', $file_content, $matches);
				if(!isset($matches[1])) throw new Exception("malformed class file for object '$object_class' : parent class name not found", INVALID_PARAM);
				else $parent_class = trim($matches[1]);
				// caution : no mutual inclusion check is done, so this call might result in an infinite loop
				if($parent_class != '\core\Object') $this->getObjectStaticInstance($parent_class);
				if(!load_class('objects/'.$this->getObjectClassFileName($object_class), $object_class)) throw new Exception("unknown object class : '$object_class'", UNKNOWN_OBJECT);
			}
			if(!isset($this->objectsArray[$object_class])) {
				$this->objectsArray[$object_class] = array();
				// "zero" indexes are used to store static instances with object default values
				$this->objectsArray[$object_class][0] = new $object_class();
			}
			return $this->objectsArray[$object_class][0];
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to get static instance');
		}
	}

	/**
	* Gets the instance of an object according to its id and class name.
	* If the specified class does not exist or cannot be loaded, an exception is thrown.
	* Note : Inside the ObjectManager class, every access to an object instance is done using this function.
	*
	* @param string $object_class the class of the object to return
	* @param integer $object_id	id of the object to return
	* @return object
	*/
	private function &getObjectInstance($user_id, $object_class, $object_id = 0) {
		try {
			$is_new_object = false;
			// ensure that $object_id has a valid value
			if(!is_numeric($object_id) || $object_id < 0) throw new Exception("incompatible value for object identifier : '$object_id'", INVALID_PARAM);
			// new instance, request for a new id
			if($object_id == 0) {
				$is_new_object = true;
				// check user's permission for creation
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_CREATE)) throw new Exception("user($user_id) does not have permission to create object($object_class)", NOT_ALLOWED);
				$object_id = $this->getNewObjectId($user_id, $object_class);
			}
			// check user's permission for reading
			else if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_READ)) throw new Exception("user($user_id) does not have read permission on the object($object_class, $object_id)", NOT_ALLOWED);
			// check if object is already loaded
			if(isset($this->objectsArray[$object_class]) && isset($this->objectsArray[$object_class][$object_id])) $object = &$this->objectsArray[$object_class][$object_id];
	        else {
				// new instances are clones of static instances (so we don't need to re-compute default values for each new instance)
				$static_instance = &$this->getObjectStaticInstance($object_class);
				$object = clone $static_instance;
				// set the proper id
				$object->setValues($user_id, array('id'=>$object_id));
				// for existing objects, values that matter are the ones in DB (and not defaults)
				if(!$is_new_object) {
					// we make sure we won't overwrite fields with default values
					$object->resetLoadedFields();
					$object->resetModifiedFields();
				}
				$this->objectsArray[$object_class][$object_id] = &$object;
			}
			return $object;
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to get object instance', $e->getCode());
		}
	}

	/**
	* Gets the name of the table associated to the specified class (required to convert namespace notation).
	*
	* @param string $object_class
	* @return string
	*/
	public function getObjectTableName($object_class) {
		try {
			$object = &$this->getObjectStaticInstance($object_class);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			return $e->getCode();
		}
		return $object->getTable();
	}

	/**
	* Gets the filename containing the class definition of an object, including a part of its path (required to convert namespace notation).
	*
	* @param string $object_class
	* @return string
	*/
	public static function getObjectClassFileName($object_class) {
		return str_replace('\\', '/', $object_class);
	}

	/**
	* Gets the package in which is defined the class of an object (required to convert namespace notation).
	*
	* @param string $object_class
	* @return string
	*/
	public static function getObjectPackageName($object_class) {
		return substr($object_class, 0, strrpos($object_class, '\\'));
	}

   	/**
	* Gets the name of the object (equivalent to its class without namespace / package).
	*
	* @param string $object_class
	* @return string
	*/
	public static function getObjectName($object_class) {
		return substr($object_class, strpos($object_class, '\\')+1);
	}

	/**
	* Gets the complete schema of an object (including special fields).
	*
	* @param string $object_class
	* @return string
	*/
	public function getObjectSchema($object_class) {
		$object = &$this->getObjectStaticInstance($object_class);
		return $object->getSchema();
	}

	/**
	* Checks if all the given attributes are defined in the specified schema for the given field.
	*
	* @param array $attributes
	* @param array $schema
	* @param string $field
	* @return bool
	*/
	private function checkFieldAttributes($attributes, $schema, $field) {
		if (!isset($schema) || !isset($schema[$field])) throw new Exception("empty schema or unknown field name '$field'");
		return !(count(array_intersect($attributes, array_keys($schema[$field]))) < count($attributes));
	}

	/**
	* Checks if the given array contains valid values for related fields.
	* This is done using the class validation method.
	* Returns an associative array containing invalid fields with their associated error_message_id (thus an empty array means no invalid fields).
	*
	* @param string $object_class
	* @param array $values associative array containing a list of field:value
	* @return array
	*/
   	private function checkFieldsValidity($object_class, $values) {
		$static_instance = &$this->getObjectStaticInstance($object_class);
		$res = array();
		foreach($values as $field => $field_value) {
 			if(method_exists($static_instance, 'getConstraints')) {
  				$constraints = $static_instance->getConstraints();
 				if(isset($constraints[$field]) && isset($constraints[$field]['function']) && isset($constraints[$field]['error_message_id'])) {
					$validation_func = $constraints[$field]['function'];
					if(is_callable($validation_func) && !call_user_func($validation_func, $field_value)) $res[$field] = $constraints[$field]['error_message_id'];
				}
			}
		}
		return $res;
	}

	/**
	* Loads a bunch of fields values form DB.
	* The function can load any type of field, however complex fields are loaded one by one, while simple fields are loaded all at once.
	*
	* @param integer $user_id
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields
	*/
	private function loadObjectFields($user_id, $object_class, $object_id, $object_fields, $lang) {
		try {
			// array to store the values of the loaded fields
			$value_array = array();
			// get the name of the DB table associated to the object
			$table_name = $this->getObjectTableName($object_class);
			// string to store which simple fields need to be loaded (this is done at the end of the function)
			$simple_fields = array();
			$simple_fields_multilang = array();
			// get the object instance (if the current user has R_READ permission for the specified object)
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			// get the complete schema of the object (including special fields)
			$schema = $object->getSchema();
			// first pass :  load complex fields one by one, and list names of the simple fields that must be loaded
			foreach($object_fields as $field) {
				if(!isset($schema[$field]) || ! isset($schema[$field]['type'])) throw new Exception("unknown field or missing mandatory data for field '$field' of class '$object_class'");
				if($lang != DEFAULT_LANG && isset($schema[$field]['multilang']) && $schema[$field]['multilang']) $simple_fields_multilang[] = $field;
				else if(in_array($schema[$field]['type'], $this->simple_types)) $simple_fields[] = $field;
				else {
					switch($schema[$field]['type']) {
						case 'related':
							if(!$this->checkFieldAttributes(array('result_type', 'foreign_object', 'path'), $schema, $field)) throw new Exception("missing at least one mandatory attribute for function field '$field' of class '$object_class'");
							// class of the object at the current position (of the 'path' variable), start with the $object_class given as parameter
							$path_object_class = $object_class;
							// list of the parent objects ids, start with the $object_id given as parameter
							$path_prev_ids = array($object_id);
							// schema of the object at the current position, start with the schema of the $object_class given as parameter
							$path_schema = $schema;
							// walk through the path variable (containing the fields hierarchy)
							foreach($schema[$field]['path'] as $path_field) {
								// list of selected ids for the object at the current position of the 'path' variable (i.e. $path_object_class)
								$path_objects_ids = array();
								// fetch all ids for every parent object (whose ids are stored in $path_prev_ids)
								foreach($path_prev_ids as $path_object_id) {
									$path_values = $this->getFields($user_id, $path_object_class, $path_object_id, array($path_field), $lang);
									// type of returned values may vary (integer or array) depending on the type of the field (i.e. many2many, one2many or many2one)
									if(!is_array($path_values[$path_field])) $path_values[$path_field] = array($path_values[$path_field]);
									$path_objects_ids = array_merge($path_objects_ids, $path_values[$path_field]);
								}
								// obtain the next object name (given the name of the current field, $path_field, and the schema of the current object, $path_schema)
								if(isset($path_schema[$path_field]['foreign_object']))
									$path_object_class = $path_schema[$path_field]['foreign_object'];
								$path_prev_ids = $path_objects_ids;
								$path_schema = $this->getObjectSchema($path_object_class);
							}
							if(in_array($schema[$field]['result_type'], $this->simple_types)) $value_array[$field] = $path_objects_ids[0];
							else $value_array[$field] = $path_objects_ids;
							break;
						case 'function':
							if(!$this->checkFieldAttributes(array('function', 'result_type'), $schema, $field)) throw new Exception("missing at least one mandatory attribute for function field '$field' of class '$object_class'");
							// handle the 'store' attribute
                        	// if set and equals to true, value should be in database
							if(isset($schema[$field]['store']) && $schema[$field]['store']) $simple_fields[] = $field;
							// otherwise, we have to compute its value
							else {
								if(!is_callable($schema[$field]['function'])) throw new Exception("error in schema parameter for function field '$field' of class '$object_class' : function cannot be called");
								$value_array[$field] = call_user_func($schema[$field]['function'], $this, $user_id, $object_id, $lang);
							}
							break;
						case 'one2many':
						case 'many2many':
							if(!$this->checkFieldAttributes(array('foreign_object','foreign_field'), $schema, $field)) throw new Exception("missing at least one mandatory attribute for one2many field '$field' of class '$object_class'");
							// obtain the ids by searching among objects having symmetrical field ('foreign_field') set to $object_id
		                    $value_array[$field] = $this->search($user_id, $schema[$field]['foreign_object'], array(array(array($schema[$field]['foreign_field'], ($schema[$field]['type']=='many2many')?'contains':'in', $object_id))));
							break;
						default :
							throw new Exception("unknown type '{$schema[$field]['type']}' for field '$field' of class '$object_class'");
							break;
					}
				}
			}
			// second pass : a) load all simple fields at once, if any
			if(count($simple_fields)) {
				$result = $this->dbConnection->getRecords(array($table_name), $simple_fields, array($object_id));
				if($row = $this->dbConnection->fetchArray($result))
					// check each value to ensure not to erase default value for empty fields
					foreach($row as $column => $value) if(!$this->dbConnection->isEmpty($value)) $value_array[$column] = $value;
			}
			// second pass : b) load multilang fields, if any
			if(count($simple_fields_multilang)) {
				$result = $this->dbConnection->getRecords(array('core_translation'), array('object_field', 'value'), array($object_id), array(array(array('lang','=',$lang), array('object_class','=',$object_class), array('object_field','in',$simple_fields_multilang))), 'object_id');
				while($row = $this->dbConnection->fetchArray($result)) {
					// check value to ensure not to erase default value for empty field
					if(!$this->dbConnection->isEmpty($row['value'])) $value_array[$row['object_field']] = $row['value'];
				}
			}
			// set the object values according to the loaded fields (do not mark as modified)
			$object->setValues($user_id, $value_array, $lang, false);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to load object fields');
		}
	}


	/**
	* Stores the values of specified fields to database.
	* If no fields are specified, stores all simple fields of the object.
	*
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields array of fields to store
	*/
	private function storeObjectFields($user_id, $object_class, $object_id, $object_fields=null, $lang=DEFAULT_LANG) {
		try {
			if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_WRITE)) throw new Exception("user $user_id does not have write permission on object $object_id of class $object_class");
			if($object_id <= 0) throw new Exception("unable to store non-existing object : create a new instance first");
			$object =  &$this->getObjectInstance($user_id, $object_class, $object_id);

			// get the columns of the object (schema)
			$columns = $object->getSchema();
			// get the name of the DB table associated to the object
			$table_name = $this->getObjectTableName($object_class);

// todo : check if we can change that since every class has to be in a package
			$table_prefix = (strlen($prefix = $this->getObjectPackageName($object_class)))?$prefix.'_':'';

			// array to handle names of the fields that must be stored
			$simple_fields = array(DEFAULT_LANG=>array());
			if($lang != DEFAULT_LANG) $simple_fields[$lang] = array();

			// if no fields have been specified, we store every simple fields of the object
			if(empty($object_fields)) $object_fields = $object->getFieldsNames($this->simple_types);

			// first pass : list all the names of the simple fields that must be stored
			foreach($object_fields as $field) {
				if(in_array($columns[$field]['type'], $this->simple_types)) {
					// if a field is not multilang, we set it to be stored in the default language, even if a different language is specified
					if(isset($columns[$field]['multilang']) && $columns[$field]['multilang']) $simple_fields[$lang][] = $field;
					else $simple_fields[DEFAULT_LANG][] = $field;
				}
			}

			// second pass : store complex fields one by one
			foreach($object_fields as $field) {
				$fields_values = $object->getValues(array($field));
				$field_value = $fields_values[$field];
				switch($columns[$field]['type']) {
					case 'one2many':
						if(!is_array($field_value)) throw new Exception("wrong value for field '$field' of class '$object_class', should be an array");
						$mandatory_keys = array('foreign_object', 'foreign_field');
						if(!$this->checkFieldAttributes($mandatory_keys, $columns, $field)) throw new Exception("missing at least one mandatory parameter for field '$field' of class '$object_class', mandatory parameters are ".implode(', ', $mandatory_keys));
						$ids_to_remove = array();
						$ids_to_add = array();
						foreach($field_value as $id_value) {
							$id_value = intval($id_value);
							if($id_value < 0) $ids_to_remove[] = abs($id_value);
							if($id_value > 0) $ids_to_add[] = $id_value;
						}
						$foreign_table = $this->getObjectTableName($columns[$field]['foreign_object']);
						// remove relation by setting pointing id to 0
						if(count($ids_to_remove)) $this->dbConnection->setRecords($foreign_table, $ids_to_remove, array($columns[$field]['foreign_field']=>0));
						// add relation by setting the pointing id (overwrite previous value if any)
                        if(count($ids_to_add)) $this->dbConnection->setRecords($foreign_table, $ids_to_add, array($columns[$field]['foreign_field']=>$object_id));
						break;
					case 'many2many':
						if(!is_array($field_value)) throw new Exception("wrong value for field '$field' of class '$object_class', should be an array");
						$mandatory_keys = array('rel_table', 'rel_foreign_key', 'rel_local_key', 'foreign_object', 'foreign_field');
						if(!$this->checkFieldAttributes($mandatory_keys, $columns, $field)) throw new Exception("missing at least one mandatory parameter for field '$field' of class '$object_class', mandatory parameters are ".implode(', ', $mandatory_keys));
						$ids_to_remove = array();
						$values_array = array();
						foreach($field_value as $id_value) {
							$id_value = intval($id_value);
							if($id_value < 0) $ids_to_remove[] = abs($id_value);
							if($id_value > 0) $values_array[] = array($object_id, $id_value);
						}
						// delete relations of ids having a '-' prefix
						if(count($ids_to_remove)) $this->dbConnection->deleteRecords($columns[$field]['rel_table'], array($object_id), array(array(array($columns[$field]['rel_foreign_key'], 'in', $ids_to_remove))), $columns[$field]['rel_local_key']);
						// create relations for other ids
// todo: trying to add an already existing relation could raise an exception, so we should either do some check or make it fail-safe (this may occur when using the UI to remove and re-add during the same edition process)
						$this->dbConnection->addRecords($columns[$field]['rel_table'], array($columns[$field]['rel_local_key'], $columns[$field]['rel_foreign_key']), $values_array);
						break;
					case 'related':
					case 'function':
						// if the 'store' attribute is set to true, save it as a simple field
						if(isset($columns[$field]['store']) && $columns[$field]['store']) $simple_fields[DEFAULT_LANG][] = $field;
						break;
				}
			}

			// third pass : store all simple fields at once, if any
			foreach($simple_fields as $lang => $simple_fields_lang) {
				if(empty($simple_fields_lang)) continue;
				$fields_array = &$object->getValues($simple_fields_lang, $lang);
				if($lang == DEFAULT_LANG) $this->dbConnection->setRecords($this->getObjectTableName($object_class), array($object_id), $fields_array);
				else {
					// save translation in 'core_translation' table
					$fields_list = array();
					$values_list = array();
					foreach($fields_array as $field => $value) {
						$fields_list[] = $field;
						$values_list[] = array($lang, $object_class, $field, $object_id, $value);
					}
					$this->dbConnection->deleteRecords('core_translation', array($object_id), array(array(array('lang', '=', $lang), array('object_class', '=', $object_class), array('object_field', 'in', $fields_list), array('object_id', '=', $object_id))));
					$this->dbConnection->addRecords('core_translation', array('lang', 'object_class', 'object_field', 'object_id', 'value'), $values_list);
				}
			}
		}
		catch (Exception $e) {
			ErrorHandler::ExceptionHandling($e, __METHOD__);
		}
	}

	/**
	*
	*	methods related to retreiving objects or fields values
	*	******************************************************
	*/

	/**
	* Gets the value of the required fields for the specified object.
	*
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields
	* @return array
	*/
	private function &getFields($user_id, $object_class, $object_id, $object_fields=null, $lang=DEFAULT_LANG) {
		try {
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			// if no fields have been defined, then we will return every simple fields of the object
 			if(empty($object_fields)) $object_fields = $object->getFieldsNames($this->simple_types);
 			else $object_fields = array_unique($object_fields);
			// first, determine which fields have not yet been loaded
			$missing_fields = array_diff($object_fields, array_keys($object->getLoadedFields($lang)));
			// then load the missing fields
			$this->loadObjectFields($user_id, $object_class, $object_id, $missing_fields, $lang);
			return $object->getValues($object_fields, $lang);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to get object fields');
		}
	}

	/**
	* Sets the values of specified fields and creates a new object (if specified $object_id is 0).
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $object_id
	* @param array $object_fields
	*
	*/
	private function setFields($user_id, $object_class, $object_id, &$object_fields, $lang) {
		try {
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			$columns = $object->getColumns();
			if($object_id == 0) $object_id = $object->getId();

			$fields_values = array();

			foreach($object_fields as $field => $value) {
				// 1) check if the given value match one of the object's fields
				if(!isset($columns[$field])) continue;
				// 2) handle the onchange events
				if(isset($columns[$field]['onchange']) && is_callable($columns[$field]['onchange'])) {
					call_user_func($columns[$field]['onchange'], $this, $user_id, $object_id, $lang);
				}
				// 3) some fields require to be adapted in some ways
				switch($columns[$field]['type']) {
					case 'text':
						load_class('utils/HtmlCleaner');
						$fields_values[$field] = str_replace('&amp;', '&', HtmlCleaner::clean($value));
						break;
					case 'date':
						load_class('utils/DateFormatter');
						$dateFormatter = new DateFormatter();
						if(is_array($value)) $dateFormatter->setDate($value, DATE_ARRAY);
						else $dateFormatter->setDate($value, DATE_STRING);
						$fields_values[$field] = $dateFormatter->getDate(DATE_SQL);
						break;
					case 'binary':
						if(isset($_FILES[$field]) && isset($_FILES[$field]['tmp_name'])) {
							if(isset($_FILES[$field]['error']) && $_FILES[$field]['error'] == 2 || isset($_FILES[$field]['size']) && $_FILES[$field]['size'] > UPLOAD_MAX_FILE_SIZE)
								throw new Exception("file exceed maximum allowed size (".floor(UPLOAD_MAX_FILE_SIZE/1024)." ko)");
							$fields_values[$field] = file_get_contents($_FILES[$field]['tmp_name'], FILE_BINARY, null, -1, UPLOAD_MAX_FILE_SIZE);
						}
						break;
					case 'one2many':
					case 'many2many':
						if(strlen($value)) $fields_values[$field] = explode(',', $value);
						break;
					default :
						$fields_values[$field] = $value;
						break;
				}
			}
			// update object (mark fields as modified)
			$object->setValues($user_id, $fields_values, $lang);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to set object fields', $e->getCode());
		}
		return $object_id;
	}

	/**
	*
	*	methods related to logs
	*	***********************
	*/

	/**
	* Adds a log to database.
	* Everytime a change to an object occurs, one record is created for each modified field (this allows to quickly retrieve the modification time for a specific field, useful for stored functional or related fields).
	*
	* @param integer $user_id
	* @param integer $action
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields
	*/
	private function logAction($user_id, $action, $object_class, $object_id, $object_fields=null, $lang=DEFAULT_LANG) {
		if(!defined('LOGGING_MODE') || !(LOGGING_MODE & $action) ) return;
		// prevent from infintite loop
		if($object_class == 'core\Log') return;

		//$values = array('created' => date("Y-m-d H:i:s"), 'creator' => $user_id, 'action' => $action, 'object_class' => $object_class, 'object_id' => $object_id, 'lang' => $lang);
		$values = array('action' => $action, 'object_class' => $object_class, 'object_id' => $object_id, 'lang' => $lang);

		// creation, deletion or management
		if(empty($object_fields)) {
			if(!in_array($action, array(R_CREATE, R_DELETE, R_MANAGE))) return;
			$this->update($user_id, 'core\Log', array(0), $values);
		}
		// reading, writing
		else {
			if(!in_array($action, array(R_READ, R_WRITE))) return;
			foreach($object_fields as $object_field) {
				if(in_array($object_field, array('created', 'modified', 'creator', 'modifier'))) return;
				$values['object_field'] = $object_field;
				$this->update($user_id, 'core\Log', array(0), $values);
			}
		}
	}

/**
*
* Public methods
*
*/
	/**
	* Gets the instance of the Manager.
	* The instance is stored in the $GLOBALS array and is created at first call to this method.
	*
	* @return object
	*/
	public static function &getInstance()	{
		if (!isset($GLOBALS['ObjectManager_instance'])) $GLOBALS['ObjectManager_instance'] = new ObjectManager();
		return $GLOBALS['ObjectManager_instance'];
	}

	public function __destruct() {
		// store changes before script terminates
		$this->store();
	}

	public function __toString() {
		return "ObjectManager instance";
	}

	/**
	* Stores changes made to objects and reset modification flags
	* (we do asynchronous writes : changes are stored in DB only when the store method is called)
	*
	*/
	public function store() {
		// flush the output buffer (to prevent browser from waiting until script ends)
		flush();
		foreach($this->objectsArray as $object_class => $objects_list) {
			foreach($objects_list as $object_id => $object) {
				// (zero ids are static instances)
				if($object_id <= 0) continue;
				// get used languages
				$langs = $object->getUsedLangs();
				foreach($langs as $lang) {
					// check for modifications
					$modified_fields = array_keys($object->getModifiedFields($lang));
					if(count($modified_fields)) {
						// we need to obtain the id of the user that made the change
                        $info = $object->getValues(array('modifier'), $lang);
						// store changes to database
						$this->storeObjectFields($info['modifier'], $object_class, $object_id, $modified_fields, $lang);
					}
				}
				// reset the modification flag
				$object->resetModifiedFields();
			}
		}
	}

	/**
	*
	*	Main final methods (validate, get, browse, search, update, remove)
	*	******************************************************************
	*/

	/**
	* Checks whether the values of given object fields are valid or not.
	* The returned value is either FALSE or an array associating, for each invalid field, field name and error message id.
	*
	* @param string $object_class object class
	* @param array $values
	* @return mixed (boolean or array)
	*/
	public function validate($object_class, $values) {
		try {
			$object = &$this->getObjectStaticInstance($object_class);
			// keep only values which key is matching one of the object's fields
			$result = $this->checkFieldsValidity($object_class, array_intersect_key($values, $object->getColumns()));
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = false;
		}
		return $result;
	}

	/**
	* Returns the instance of the specified object(after having checked if user has right on it).
	*
	* @param integer $user_id
	* @param string $object_class object class
	* @param integer $object_id
	* @return object
	*/
	public function &get($user_id, $object_class, $object_id) {
		try {
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$object = false;
		}
		return $object;
	}

	// experimental (we need this for validation tests)
	public function &getStatic($object_class) {
		try {
			$object = &$this->getObjectStaticInstance($object_class);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$object = false;
		}
		return $object;
	}


	/**
	* Returns objects of requested ids for the specified class.
	* The returned structure is an associative array containing, for every object id, a sub array maping each field to its value.
	*
	* @param integer $user_id
	* @param string $object_class object class
	* @param array $ids ids of the objects to return
	* @param array $fields required field
	* @param string $lang
	* @return mixed (integer or array)
	*/
	public function &browse($user_id, $object_class, $ids=null, $fields=null, $lang=DEFAULT_LANG) {
		try {
			$result = array();
			if(!empty($ids) && !is_array($ids)) throw new Exception("argument is not an array of objects identifiers : '$ids'", INVALID_PARAM);
			if(!empty($fields) && !is_array($fields)) throw new Exception("argument is not an array of objects fields : '$fields'", INVALID_PARAM);
			if(is_null($ids)) $ids = $this->search($user_id, $object_class, null, '', 'asc', 0, '', $lang);
			foreach($ids as $object_id) {
				if($object_id == 0) {
					// we cannot use getFields here, since it would result in the creation of a new object (which is a behaviour reserved to the 'update' method)
					// so in order to get the default values we use the "static instance"
					$object = &$this->getObjectStaticInstance($object_class);
					$result[$object_id] = $object->getValues($fields, $lang);
				}
				else {
					if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_READ)) throw new Exception("user '$user_id' does not have permission to read object '$object_id' of class '$object_class'", NOT_ALLOWED);
					$result[$object_id] = $this->getFields($user_id, $object_class, $object_id, $fields, $lang);
					$this->logAction($user_id, R_READ, $object_class, $object_id, $fields, $lang);
				}
			}
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = $e->getCode();
		}
		return $result;
	}

	/**
	* Search for the objects corresponding to the domain criteria.
	* This method essentially generates an SQL query and returns an array of matching objects ids.
	*
	* 	The domain syntax is : array( array( array(operand, operator, operand)[, array(operand, operator, operand) [, ...]]) [, array( array(operand, operator, operand)[, array(operand, operator, operand) [, ...]])])
	* 	Array of several series of clauses joined by logical ANDs themselves joined by logical ORs : disjunctions of conjunctions
	* 	i.e.: (clause[, AND clause [, AND ...]]) [ OR (clause[, AND clause [, AND ...]]) [ OR ...]]
	*
	* 	accepted operators are : '=', '<', '>',' <=', '>=', '<>', 'like', 'ilike', 'in', 'contains'
	* 	example : array( array('title', 'like', '%foo%'), array('id', 'in', array(1,2,18)) )
	*
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $domain
	* @param string $order
	* @param string $sort ('asc' or 'desc')
	* @param integer $start
	* @param string $limit
	* @return mixed (integer or array)
	*/
	public function search($user_id, $object_class, $domain=null, $order='', $sort='asc', $start=0, $limit='', $lang=DEFAULT_LANG) {
		try {
			if(!IdentificationManager::hasRight($user_id, $object_class, 0, R_READ)) throw new Exception("user($user_id) does not have permission to read objects of class ($object_class)", NOT_ALLOWED);
			$res_list = array();
			$valid_operators = array(
								'boolean'		=> array('=', '<>'),
								'integer'		=> array('in', 'not in', '=', '<>', '<', '>', '<=', '>='),
								'string'		=> array('like', 'ilike', '=', '<>'),
								'short_text'	=> array('like', 'ilike','='),
								'text'			=> array('like', 'ilike','='),
								'date'			=> array('=', '<>', '<', '>', '<=', '>=', 'like'),
								'time'			=> array('=', '<>', '<', '>', '<=', '>='),
								'datetime'		=> array('=', '<>', '<', '>', '<=', '>='),
								'timestamp'		=> array('=', '<>', '<', '>', '<=', '>='),
								'selection'		=> array('in', '=', '<>'),
								'many2one'		=> array('in', '='),
								'one2many'		=> array('contains'),
								'many2many'		=> array('contains'),
								'related'		=> array('contains', '=', '<>', '<', '>', '<=', '>='),
							);

			$conditions = array(array());
			$tables = array();
			// we use a nested closure to define a function (whose use is limited to this method) that stores original table names and returns corresponding aliases
			$add_table = function ($table_name) use (&$tables) {
				$table_alias = 't'.count($tables);
				$tables[$table_alias] = $table_name;
				return $table_alias;
			};

			$table_alias = $add_table($this->getObjectTableName($object_class));

			if(!empty($domain)) {
				$schema = $this->getObjectSchema($object_class);
				for($j = 0, $max_j = count($domain); $j < $max_j; ++$j) {
					// first pass : build conditions and the tables names arrays
					for($i = 0, $max_i = count($domain[$j]); $i < $max_i; ++$i) {
						if(!isset($domain[$j][$i]) || !is_array($domain[$j][$i])) throw new Exception("malformed domain", INVALID_PARAM);
						if(!isset($domain[$j][$i][0]) || !isset($domain[$j][$i][1]) || !isset($domain[$j][$i][2])) throw new Exception("invalid domain, a mandatory attribute is missing", INVALID_PARAM);
						$field		= $domain[$j][$i][0];
						$operator	= strtolower($domain[$j][$i][1]);
						$value		= $domain[$j][$i][2];

						if(!in_array($field, array_keys($schema))) throw new Exception("invalid domain, unexisting field '$field' for object '$object_class'", INVALID_PARAM);
						if(!in_array($operator, $valid_operators[$schema[$field]['type']])) throw new Exception("invalid operator '$operator' for field '$field' of type '{$schema[$field]['type']}' in object '$object_class'", INVALID_PARAM);

						// todo : test user permissions (on foreign objects)
						switch($schema[$field]['type']) {
							case 'one2many':
								if(!$this->checkFieldAttributes(array('foreign_object','foreign_field'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for one2many field '$field' of class '$object_class'", INVALID_PARAM);
								// add foreign table to sql query
								$foreign_table_alias =  $add_table($this->getObjectTableName($schema[$field]['foreign_object']));
								// add the join condition
								$conditions[$j][] = array($foreign_table_alias.'.'.$schema[$field]['foreign_field'], '=', '`'.$table_alias.'`.`id`');
								// as comparison field, use foreign table's 'foreign_key' if any, 'id' otherwise
								if(isset($schema[$field]['foreign_key'])) $field = $foreign_table_alias.'.'.$schema[$field]['foreign_key'];
								else $field = $foreign_table_alias.'.id';
								// use operator 'in' instead of 'contains' (which is not sql standard)
								$operator = 'in';
								break;
							case 'many2many':
								if(!$this->checkFieldAttributes(array('rel_table','rel_local_key','rel_foreign_key'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for many2many field '$field' of class '$object_class'", INVALID_PARAM);
								// add related table to sql query
								$table_prefix = (strlen($prefix = $this->getObjectPackageName($object_class)))?$prefix.'_':'';
								$rel_table_alias =  $add_table($schema[$field]['rel_table']);
								// add the join condition
								$conditions[$j][] = array($table_alias.'.id', '=', '`'.$rel_table_alias.'`.`'.$schema[$field]['rel_local_key'].'`');
								// use rel table's 'rel_foreign_key' as comparison field
								$field = $rel_table_alias.'.'.$schema[$field]['rel_foreign_key'];
								// use operator 'in' instead of 'contains' (which is not sql standard)
								$operator = 'in';
								break;
							case 'related':
								// todo : handle when 'store' attribute is set
								throw new Exception("no search allowed for 'related' field '$field' of class '$object_class'", NOT_ALLOWED);
								break;
							case 'function':
								// todo : handle when 'store' attribute is set
								throw new Exception("no search allowed for 'function' field '$field' of class '$object_class'", NOT_ALLOWED);
								break;
							default:
								// simple fields always match table fields
								$field = $table_alias.'.'.$field;
								break;
						}
						$conditions[$j][] = array($field, $operator, $value);
					}
					// search only among non-deleted records
					$conditions[$j][] = array($table_alias.'.deleted', '=', '0');
					$conditions[$j][] = array($table_alias.'.modifier', '>', '0');
				}
			}
			else {
				// search only among non-deleted records
				$conditions[0][] = array($table_alias.'.deleted', '=', '0');
				$conditions[0][] = array($table_alias.'.modifier', '>', '0');
			}

			// second pass : fetch the ids of matching objects
			$res = $this->dbConnection->getRecords($tables, array($table_alias.'.id'), null, $conditions, $table_alias.'.id', $order, $sort, $start, $limit);
			while ($row = $this->dbConnection->fetchArray($res)) $res_list[] = $row['id'];
			$res_list = array_unique($res_list);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$res_list = $e->getCode();
		}
		return $res_list;
	}

	/**
	*
	*	 methods related to modifying objects
	*	 ************************************
	*/


	/**
	* Sets the values of an instance and creates a new object (if specified $object_id is 0).
	* This method expects an array with modified values of an object.
	* In case new objects are created, an array is returned containing associated new ids.
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $ids
	* @param array $values
	* @return mixed (integer or array containing ids of newly created objects - if none, array is empty)
	*/
	public function update($user_id, $object_class, $ids, $values=null, $lang=DEFAULT_LANG) {
		try {
			$result = array();
			$object = &$this->getObjectStaticInstance($object_class);

			if(empty($ids) || (!empty($ids) && !is_array($ids))) throw new Exception("argument is not an array of objects identifiers : '$ids'", INVALID_PARAM);
			if(!is_null($values) && !is_array($values)) throw new Exception("argument is not an array of fields values", INVALID_PARAM);
			if(is_null($values)) $values = array();
			// prevent from modifying special fields
			$values = array_diff_key($values, \core\Object::getSpecialFields());
			// keep only values which key is matching one of the object's fields
			$values = array_intersect_key($values, $object->getColumns());
			foreach($ids as $object_id) {
				// checking for permissions
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_WRITE)) throw new Exception("user '$user_id' does not have permission to write object '$object_id' of class '$object_class'", NOT_ALLOWED);
				$id = $this->setFields($user_id, $object_class, $object_id, $values, $lang);
				if($object_id == 0) {
					$result[] = $object_id = $id;
					// log R_CREATE
					$log_action = R_CREATE;
				}
				// log R_WRITE
				else $log_action = R_WRITE;
				$this->logAction($user_id, $log_action, $object_class, $object_id, array_keys($values), $lang);
			}
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = $e->getCode();
		}
		return $result;
	}

	/**
	* Deletes an object permanently or puts it in the "trash bin" (ie setting the 'deleted' flag to 1).
	* The returned structure is an associative array containing ids of the objects actually deleted.
	*
	* @param integer $user_id
	* @param string $object_class object class
	* @param array $ids ids of the objects to remove
	* @param boolean $permanent
	* @return mixed (integer or array)
	*/
	public function remove($user_id, $object_class, $ids, $permanent=false) {
		try {
			$result = $ids;
			if(empty($ids) || (!empty($ids) && !is_array($ids))) throw new Exception("argument is not an array of objects identifiers : '$ids'", INVALID_PARAM);
			foreach($ids as $object_id) {
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_DELETE)) throw new Exception("user($user_id) does not have permission to remove object($object_class)", NOT_ALLOWED);
			}
			$table_name = $this->getObjectTableName($object_class);
			if ($permanent) {
				$this->dbConnection->deleteRecords($table_name, $ids);
				$log_action = R_DELETE;
				$log_fields = null;
			}
			else {
				$this->dbConnection->setRecords($table_name, $ids, array('deleted'=>1));
				$log_action = R_WRITE;
				$log_fields = array('deleted');
			}
			foreach($ids as $object_id) $this->logAction($user_id, $log_action, $object_class, $object_id, $log_fields);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = $e->getCode();
		}
		return $result;
	}
}