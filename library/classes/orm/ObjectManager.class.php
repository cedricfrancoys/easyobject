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

// Private vars

	private $objectsArray;
	private $dbConnection;

	public static $simple_types	= array('boolean', 'integer', 'float', 'string', 'short_text', 'text', 'date', 'time', 'datetime', 'timestamp', 'selection', 'binary', 'many2one');
	public static $complex_types = array('one2many', 'many2many', 'related', 'function');

	public static $valid_attributes = array(
			'boolean'		=> array('type', 'label', 'help', 'onchange', 'search'),
			'integer'		=> array('type', 'label', 'help', 'onchange', 'search'),
			'float'			=> array('type', 'label', 'help', 'onchange', 'search'),
			'string'		=> array('type', 'label', 'help', 'onchange', 'search', 'multilang'),
			'short_text'	=> array('type', 'label', 'help', 'onchange', 'search', 'multilang'),
			'text'			=> array('type', 'label', 'help', 'onchange', 'search', 'multilang'),
			'date'			=> array('type', 'label', 'help', 'onchange', 'search'),
			'time'			=> array('type', 'label', 'help', 'onchange', 'search'),
			'datetime'		=> array('type', 'label', 'help', 'onchange', 'search'),
			'timestamp'		=> array('type', 'label', 'help', 'onchange', 'search'),
			'selection'		=> array('type', 'label', 'help', 'onchange', 'selection'),
			'binary'		=> array('type', 'label', 'help', 'onchange', 'search', 'multilang'),
			'many2one'		=> array('type', 'foreign_object', 'label', 'help', 'onchange', 'search', 'multilang'),
			'one2many'		=> array('type', 'foreign_object', 'foreign_field', 'label', 'help', 'onchange', 'order'),
			'many2many'		=> array('type', 'foreign_object', 'foreign_field', 'rel_table', 'rel_local_key', 'rel_foreign_key', 'label', 'help', 'onchange'),
			'related'		=> array('type', 'foreign_object', 'result_type', 'path', 'label', 'help', 'onchange', 'store'),
			'function'		=> array('type', 'result_type', 'function', 'label', 'help', 'onchange', 'store')
	);

	public static $mandatory_attributes = array(
			'boolean'		=> array('type'),
			'integer'		=> array('type'),
			'float'			=> array('type'),
			'string'		=> array('type'),
			'short_text'	=> array('type'),
			'text'			=> array('type'),
			'date'			=> array('type'),
			'time'			=> array('type'),
			'datetime'		=> array('type'),
			'timestamp'		=> array('type'),
			'selection'		=> array('type', 'selection'),
			'binary'		=> array('type'),
			'many2one'		=> array('type', 'foreign_object'),
			'one2many'		=> array('type', 'foreign_object', 'foreign_field'),
			'many2many'		=> array('type', 'foreign_object', 'foreign_field', 'rel_table', 'rel_local_key', 'rel_foreign_key'),
			'related'		=> array('type', 'foreign_object', 'result_type', 'path'),
			'function'		=> array('type', 'result_type', 'function')
	);

// Private methods


	private function __construct() {
		// initialize the objects array
		$this->objectsArray = array();
		// initialize error handler
		new ErrorHandler();
		// open DB connection
		$this->dbConnection = &DBConnection::getInstance(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, DB_DBMS);
		if($this->dbConnection->connect() === false) trigger_error('ObjectManager::ObjectManager, unable to establish connection to database: check connection parameters (possibles reasons: non-supported DBMS, unknown database name, incorrect username or password, ...)', E_USER_ERROR);
	}


//  Methods related to objects instances


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
		$ids = $this->search($user_id, $object_class, array(array(array('modifier', '=', '0'),array('created', '<', date("Y-m-d H:i:s", time()-(3600*24*DRAFT_VALIDITY))))), 'id', 'asc');
		if(count($ids)) $object_id = $ids[0];
		$creation_array = array('created' => date("Y-m-d H:i:s"), 'creator' => $user_id);
		if($object_id  > 0) {
			// store the id to reuse
			$creation_array['id'] = $object_id;
			// and delete the associated record
			$this->dbConnection->deleteRecords($object_table, array($object_id));
		}
		// create a new record with the found value, or let the autoincrement do the job
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
				// first, read the file content to see if the class extends from another (which could not be loaded yet)
				// we do so because we cannot use __autoload mechanism since the script might be run in CLI SAPI
				$filename = realpath($_SERVER['DOCUMENT_ROOT']).dirname($_SERVER['PHP_SELF']).'/packages/'.$this->getObjectPackageName($object_class).'/classes/'.$this->getObjectName($object_class).'.class.php';
				if(!is_file($filename)) throw new Exception("unknown object class : '$object_class'", UNKNOWN_OBJECT);
				preg_match('/\bextends\b(.*)\{/iU', file_get_contents($filename, FILE_USE_INCLUDE_PATH), $matches);
				if(!isset($matches[1])) throw new Exception("malformed class file for object '$object_class' : parent class name not found", INVALID_PARAM);
				else $parent_class = trim($matches[1]);
				// caution : no mutual inclusion check is done, so this call might result in an infinite loop
				if($parent_class != '\core\Object') $this->getObjectStaticInstance($parent_class);
				if(!(include $filename)) throw new Exception("unknown object class : '$object_class'", UNKNOWN_OBJECT);
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
			throw new Exception('unable to get static instance', $e->getCode());
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
		return str_replace('\\', '', substr($object_class, 0, strrpos($object_class, '\\')));
	}

   	/**
	* Gets the name of the object (equivalent to its class without namespace / package).
	*
	* @param string $object_class
	* @return string
	*/
	public static function getObjectName($object_class) {
		return substr($object_class, strrpos($object_class, '\\')+1);
	}

	/**
	* Gets the complete schema of an object (including special fields).
	* note: this method is not set as static since we need to load class file in order to retrieve the schema
	* (and this is only done in the getObjectStaticInstance method)
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
	* @param array $check_array
	* @param array $schema
	* @param string $field
	* @return bool
	*/
	public static function checkFieldAttributes($check_array, $schema, $field) {
		if (!isset($schema) || !isset($schema[$field])) throw new Exception("empty schema or unknown field name '$field'");
		$attributes = $check_array[$schema[$field]['type']];
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
// todo: check that syntax matches field type (use regexp)

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

// todo : see if we could put this method inside loadObjectFields
	private function loadRelatedField($user_id, $object_class, $object_id, $field, $lang) {
		try {
			$result = null;
			// get the object instance (if the current user has R_READ permission for the specified object)
			$object = &$this->getObjectStaticInstance($object_class);
			// get the complete schema of the object (including special fields)
			$schema = $object->getSchema();

			// 1) init vars used in the loop
			// class of the object at the current position (of the 'path' variable), start with the $object_class given as parameter
			$path_object_class = $object_class;
			// list of the parent objects ids, start with the $object_id given as parameter
			$path_prev_ids = array($object_id);
			// schema of the object at the current position, start with the schema of the $object_class given as parameter
			$path_schema = $schema;
			// 2) walk through the path variable (containing the fields hierarchy)
			foreach($schema[$field]['path'] as $path_field) {
				// list of selected ids for the object at the current position of the 'path' variable (i.e. $path_object_class)
				$path_objects_ids = array();
				// fetch all ids for every parent object (whose ids are stored in $path_prev_ids)
				foreach($path_prev_ids as $path_object_id) {
					// get the pointed field value (there might be a recursion here)
					$path_values = $this->getFields($user_id, $path_object_class, $path_object_id, array($path_field), $lang);
					if(is_null($path_values[$path_field])) break 2;
					// type of returned values may vary (integer or array) depending on the type of the field (i.e. many2many, one2many or many2one)
					if(!is_array($path_values[$path_field])) $path_values[$path_field] = array($path_values[$path_field]);
					$path_objects_ids = array_merge($path_objects_ids, $path_values[$path_field]);
				}
				// obtain the next object name (given the name of the current field, $path_field, and the schema of the current object, $path_schema)
				if(isset($path_schema[$path_field]['foreign_object'])) $path_object_class = $path_schema[$path_field]['foreign_object'];
				$path_prev_ids = $path_objects_ids;
				$path_schema = $this->getObjectSchema($path_object_class);
			}
			// 3) retrieve result
			if(in_array($schema[$field]['result_type'], self::$simple_types)) $result = $path_objects_ids[0];
			else $result = $path_objects_ids;
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __METHOD__);
		}
		return $result;
	}

	/**
	* Loads a bunch of fields values form DB.
	* The function can load any type of field. However, complex fields are loaded one by one while simple fields are loaded all at once.
	* We assume that if we have to query the DB, it's best to do so for an entire objects set (rather than one object at a time)
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $ids
	* @param array $object_fields
	* @param string $lang
	*/
	private function loadObjectFields($user_id, $object_class, $ids, $object_fields, $lang) {
		try {
			if(is_null($object_fields)) throw new Exception("missing object_fields attribute for method loadObjectFields", INVALID_PARAM);

			// array to store the values of the loaded fields, for each object
			$values_array = array();
			// get the name of the DB table associated to the object
			$table_name = $this->getObjectTableName($object_class);
			// array to store which simple fields need to be loaded (this is done at the end of the function)
			$simple_fields = array();
			// we also may need additional query for translations of some fields
			$simple_fields_multilang = array();
			// get the object instance (if the current user has R_READ permission for the specified object)
			$object = &$this->getObjectStaticInstance($object_class);
			// get the complete schema of the object (including special fields)
			$schema = $object->getSchema();

			// first pass :  load complex fields one by one, and list names of the simple fields that must be loaded
			foreach($object_fields as $field) {
				if(!isset($schema[$field]) || ! isset($schema[$field]['type'])) throw new Exception("unknown field or missing mandatory data for field '$field' of class '$object_class'", INVALID_PARAM);
				// remember simple fields for delayed loading
				if(in_array($schema[$field]['type'], self::$simple_types)) {
					// multilang fields must be loaded from the translation table
					if($lang != DEFAULT_LANG && isset($schema[$field]['multilang']) && $schema[$field]['multilang']) $simple_fields_multilang[] = $field;
					// remember simple fields (that are not multilang)
					else $simple_fields[] = $field;
				}
				// complex fields are loaded at once (for each object specified by the $ids array)
				else {
					// check schema validity
					if(!self::checkFieldAttributes(self::$mandatory_attributes, $schema, $field)) throw new Exception("missing at least one mandatory attribute for field '$field' of class '$object_class'", INVALID_PARAM);
					// check if we need to fetch something from DB for functional fields having store attribute set to true
                	if(in_array($schema[$field]['type'], array('related', 'function')) && isset($schema[$field]['store']) && $schema[$field]['store']) {
                		if(in_array($schema[$field]['result_type'], self::$simple_types)) $simple_fields[] = $field;
// todo: following code has not been tested yet (for now, only simple fields are stored)
// we should check if object is already loaded or not
                		else {
							$result = $this->dbConnection->getRecords(array($table_name), array($field), $ids);
							while($row = $this->dbConnection->fetchArray($result)) {
							    $object = &$this->getObjectInstance($user_id, $object_class, $row['id']);
	        					$object->setValues($user_id, array($field => $row[$field]), $lang, false);
							}
						}
					}
					else {
						foreach($ids as $object_id) {
// todo: find a way to load all data at once (this could be achieved by using SQL queries instead of calling 'search' method)						
							$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
							// if field is already loaded and/or has been modified, there is nothing to do
				   			if(in_array($field, array_keys($object->getLoadedFields($lang)))) continue;
							switch($schema[$field]['type']) {
								case 'related':
                                	$values_array[$object_id][$field] = $this->loadRelatedField($user_id, $object_class, $object_id, $field, $lang);
									break;
								case 'function':
									if(!is_callable($schema[$field]['function'])) throw new Exception("error in schema parameter for function field '$field' of class '$object_class' : function cannot be called");
									$values_array[$object_id][$field] = call_user_func($schema[$field]['function'], $this, $user_id, $object_id, $lang);
									break;
								case 'one2many':
									if(isset($schema[$field]['order'])) $order = $schema[$field]['order'];
									else $order = 'id';
									// obtain the ids by searching among objects having symetrical field ('foreign_field') set to $object_id
				                    $values_array[$object_id][$field] = $this->search($user_id, $schema[$field]['foreign_object'], array(array(array($schema[$field]['foreign_field'], '=', $object_id), array('deleted', '=', '0'))), $order);
									break;
								case 'many2many':
									// obtain the ids by searching among objects having symetrical field ('foreign_field') set to $object_id
				                    $values_array[$object_id][$field] = $this->search($user_id, $schema[$field]['foreign_object'], array(array(array($schema[$field]['foreign_field'], 'contains', $object_id))));
									break;
								default :
									throw new Exception("unknown type '{$schema[$field]['type']}' for field '$field' of class '$object_class'");
									break;
							}
						}
					}
				}
			}
			// second pass : a) load all simple fields at once, if any
			if(count($simple_fields)) {
				// we'll need the value of the id column below
				array_push($simple_fields, 'id', 'modifier');
				// get all records at once
				$result = $this->dbConnection->getRecords(array($table_name), $simple_fields, $ids);
				while($row = $this->dbConnection->fetchArray($result)) {
				    $object_id = $row['id'];
				    $object = &$this->getObjectInstance($user_id, $object_class, $object_id);
				   	$loaded_fields = array_keys($object->getLoadedFields($lang));
					// check each value to ensure not to erase fields already loaded or default value for empty fields
					foreach($row as $column => $value) {
						// we need to check column type as simple_fields may contain complex fields with result_type set to a simple type
						// for functional fields : if no value is specified in DB, then compute the value by calling the associated method
						if(in_array($schema[$column]['type'], array('related', 'function')) && isset($schema[$column]['store']) && $schema[$column]['store']) {
							// note : we use is_null() rather than empty() because an empty value could be the result of the calculated field (this implies that the DB schema has DDEFAULT NULL for the associated column)
							if(is_null($row[$column])) {
								if($schema[$column]['type'] == 'related')
									$computed_value = $this->loadRelatedField($user_id, $object_class, $object_id, $column, $lang);
								if($schema[$column]['type'] == 'function') {
									if(!is_callable($schema[$column]['function'])) throw new Exception("error in schema parameter for function field '$column' of class '$object_class' : function cannot be called");
									$computed_value = call_user_func($schema[$column]['function'], $this, $user_id, $object_id, $lang);
								}
								// we need to store the computed value (to avoid computing it at each object load)
								// if the object is still a dradt, we don't want to mark the object as modified(so we use the SYSTEM_USER_ID - which is equal to 0)
// todo : doesn't work in case of rights restriction (or we have to add user 0 to all groups)
								if(empty($row['modifier'])) $object->setValues(SYSTEM_USER_ID, array($column => $computed_value), $lang);
								// otherwise we update the object (even if we were actually loading it)
								else $object->setValues($user_id, array($column => $computed_value), $lang);
							}
							else $values_array[$object_id][$column] = $value;
						}
						else {
							if(in_array($column, $loaded_fields)) continue;
							// note : sometimes, we need the value, even if it is empty (and it is important that it overwrites the default value, if any)
							// line below means that, for existing objects, default value might be overwritten by an empty value
							$values_array[$object_id][$column] = $value;
						}
					}
				}
			}
			// second pass : b) load multilang fields, if any
			if(count($simple_fields_multilang)) {
				$result = $this->dbConnection->getRecords(
					array('core_translation'),
					array('object_id', 'object_field', 'value'),
					$ids,
					array(array(
							array('language','=',$lang),
							array('object_class','=',$object_class),
							array('object_field','in',$simple_fields_multilang)
						 )
					),
					'object_id');
 				// fill in the object values array (use 'value' instead of field name)
				while($row = $this->dbConnection->fetchArray($result)) {
					// check value to ensure not to erase default value for empty field
					// note : sometimes, we need the value, even if it is empty (and it is important that it overwrites the default value, if any)
					// it means that, for existing objects, default value may be overwritten by an empty value
					// if(!$this->dbConnection->isEmpty($row['value'])) $values_array[$row['object_field']] = $row['value'];
					$values_array[$row['object_id']][$row['object_field']] = $row['value'];
				}
			}
			// set objects values according to the loaded fields (do not mark as modified)
			foreach($ids as $object_id) {
				if(isset($values_array[$object_id])) {
					$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
					$schema = $object->getSchema();
					// do some pre-treatment if necessary (this step is symetric to the one in setFields method)
					foreach($values_array[$object_id] as $field => $value) {
						switch($schema[$field]['type']){
							case 'date':
								if($value == '0000-00-00') $value = '';
								else {
									load_class('utils/DateFormatter');
									$dateFormatter = new DateFormatter($value, DATE_SQL);
									// DATE_FORMAT constant is defined in config.inc.php
									$value = $dateFormatter->getDate(DATE_FORMAT);
								}
								$values_array[$object_id][$field] = $value;
								break;
							default:
								break;
						}
					}
					$object->setValues($user_id, $values_array[$object_id], $lang, false);
				}
			}
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to load object fields', $e->getCode());
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
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);

			// get the columns of the object (schema)
			$columns = $object->getSchema();
			// get the name of the DB table associated to the object
			$table_name = $this->getObjectTableName($object_class);

			// array to handle names of the fields that must be stored
			$simple_fields = array(DEFAULT_LANG=>array());
			if($lang != DEFAULT_LANG) $simple_fields[$lang] = array();

			// if no fields have been specified, we store every simple fields of the object
			if(empty($object_fields)) $object_fields = $object->getFieldsNames(self::$simple_types);

			// first pass : list all the names of the simple fields that must be stored
			foreach($object_fields as $field) {
				if(in_array($columns[$field]['type'], self::$simple_types)) $simple_fields[$lang][] = $field;
			}

			// second pass : store complex fields one by one
			foreach($object_fields as $field) {
				if(!self::checkFieldAttributes(self::$mandatory_attributes, $columns, $field)) throw new Exception("missing at least one mandatory parameter for field '$field' of class '$object_class'", INVALID_PARAM);
				$fields_values = $object->getValues(array($field));
				$field_value = $fields_values[$field];
				switch($columns[$field]['type']) {
					case 'one2many':
						if(!is_array($field_value)) throw new Exception("wrong value for field '$field' of class '$object_class', should be an array");
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
					$this->dbConnection->deleteRecords('core_translation', array($object_id), array(array(array('language', '=', $lang), array('object_class', '=', $object_class), array('object_field', 'in', $fields_list))), 'object_id');
					$this->dbConnection->addRecords('core_translation', array('language', 'object_class', 'object_field', 'object_id', 'value'), $values_list);
				}
			}
		}
		catch (Exception $e) {
			ErrorHandler::ExceptionHandling($e, __METHOD__);
		}
	}


// Methods related to retreiving objects or fields values


	/**
	* Gets the value of the required fields for the specified object.
	*
	* @param string $object_class
	* @param integer $object_id
	* @param array $object_fields
	* @return array
	*/
	private function &getFields($user_id, $object_class, $object_id, $object_fields=null, $lang=DEFAULT_LANG) {
		try {
 			// get the associated instance
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			// if no fields have been defined, then we will return every simple fields of the object
 			if(empty($object_fields)) $object_fields = $object->getFieldsNames(self::$simple_types);
 			// otherwise, we ensure that there is no duplicate
 			else $object_fields = array_unique($object_fields);
			// first, determine which fields (among the requested ones) have not yet been loaded (or modified)
			$missing_fields = array_diff($object_fields, array_keys($object->getLoadedFields($lang)));
			// then load the missing fields
			$this->loadObjectFields($user_id, $object_class, array($object_id), $missing_fields, $lang);
			return $object->getValues($object_fields, $lang);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to get object fields', $e->getCode());
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
			// if a new instance has been created, assign $object_id to the actual object identifier
			if($object_id == 0) $object_id = $object->getId();

			$fields_values = array();
			$onchange_fields = array();

	        // first pass : update values
			foreach($object_fields as $field => $value) {
				// 1) check if the given value match one of the object's fields
				if(!isset($columns[$field])) continue;
				// 2) check if the modification does trigger an onchange event
				if(isset($columns[$field]['onchange'])) $onchange_fields[] = $field;
				// 3) some fields require to be adapted in some ways
				switch($columns[$field]['type']) {
					case 'text':
						load_class('utils/HtmlCleaner');
						// standard cleaning: remove non-standard tags and 'class' attributes
						$fields_values[$field] = str_replace('&amp;', '&', HtmlCleaner::clean($value, null, array('class')));
						break;
					case 'date':
						if(empty($value)) $value = '0000-00-00';
						else {
							load_class('utils/DateFormatter');
							$dateFormatter = new DateFormatter($value, DATE_FORMAT);
	//						if(is_array($value)) $dateFormatter->setDate($value, DATE_ARRAY);
	//						else $dateFormatter->setDate($value, DATE_STRING);
							$value = $dateFormatter->getDate(DATE_SQL);
						}
						$fields_values[$field] = $value;
						break;
					case 'binary':
						// note : this won't work in client-server mode (since in that case $_FILES array is only available on client-side)
						if(isset($_FILES[$field]) && isset($_FILES[$field]['tmp_name'])) {
							if(isset($_FILES[$field]['error']) && $_FILES[$field]['error'] == 2 || isset($_FILES[$field]['size']) && $_FILES[$field]['size'] > UPLOAD_MAX_FILE_SIZE)
								throw new Exception("file exceed maximum allowed size (".floor(UPLOAD_MAX_FILE_SIZE/1024)." ko)", NOT_ALLOWED);
						
							if(BINARY_STORAGE_MODE == 'DB') {
								// store file content in database
								$fields_values[$field] = file_get_contents($_FILES[$field]['tmp_name'], FILE_BINARY, null, -1, UPLOAD_MAX_FILE_SIZE);
							}
							else if(BINARY_STORAGE_MODE == 'FS') {
								// 1) move temporary file
								load_class('utils/FSManipulator');
								$storage_location = BINARY_STORAGE_DIR.'/'.FSManipulator::getSanitizedName($_FILES[$field]['name']);								
								// note : if a file by that name already exists it will be overwritten
								move_uploaded_file($_FILES[$field]['tmp_name'], $storage_location);
								// 2) store file location in database
								$fields_values[$field] = $storage_location;
							}
						}
						else throw new Exception("binary data has not been received or cannot be retrieved", UNKNOWN_ERROR);
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

			// second pass : handle onchange events, if any (must be called afer modifications otherwise object values might be outdated)
			// before handling onchange events, we store fields having the onchange attribute set (we need to do so because o2m and m2m fields can be only partially loaded)
			if(count($onchange_fields)) {
				// force a storage of the modified values to DB
				$this->storeObjectFields($user_id, $object_class, $object_id, $onchange_fields, $lang);
				// reset the state flags (so value will be reloaded from DB when needed)
				$object->resetModifiedFields(array($lang => $onchange_fields));
				$object->resetLoadedFields(array($lang => $onchange_fields));
				// call methods associated with onchange events of related fields
				foreach($onchange_fields as $field)
					if(is_callable($columns[$field]['onchange'])) call_user_func($columns[$field]['onchange'], $this, $user_id, $object_id, $lang);
			}
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to set object fields', $e->getCode());
		}
		return $object_id;
	}


// Methods related to logs


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
	private function setLog($user_id, $action, $object_class, $object_id, $object_fields=null, $lang=DEFAULT_LANG) {
		if(!defined('LOGGING_MODE') || !(LOGGING_MODE & $action) ) return;
		// allow only valid actions
		if(!in_array($action, array(R_CREATE, R_READ, R_WRITE, R_DELETE, R_MANAGE))) return;

		// prevent from infintite loop
		if($object_class == 'core\Log') return;

		$values = array('action' => $action, 'object_class' => $object_class, 'object_id' => $object_id, 'lang' => $lang);

		if(!empty($object_fields)) {
			// remove special fields from list, if any
			$object_fields = array_diff($object_fields, array('created', 'modified', 'creator', 'modifier'));
			$values['object_fields'] = implode(',', $object_fields);
		}
		// logs are system objects, so the permissions are not related to the user generating log
		$this->update(ROOT_USER_ID, 'core\Log', array(0), $values);
	}


// Public methods


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
				// reset the state flags
				$object->resetModifiedFields();
				$object->resetLoadedFields();
			}
		}
	}


// Main final methods (validate, get, browse, search, update, remove)


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
	public function &get($user_id, $object_class, $object_id, $lang=DEFAULT_LANG) {
		try {
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
            // we browse the specified object requesting all fields in order to load those that would have not yet been loaded
			$this->browse($user_id, $object_class, array($object_id), $object->getFieldsNames(), $lang);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$object = false;
		}
		return $object;
	}

	/**
	* Returns the static instance for the specified class. Static instances or zero id instances are instances with no id that serve as template for new object.
	* Note : we don't need a database access to instanciate those instances.
	*
	* 	experimental (we need this for validation tests)
	*
	* @param string $object_class
	*/
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

			// not sure we should do this as it could take a while (instead, first use search method with no domain)
			// if(is_null($ids)) $ids = $this->search($user_id, $object_class, null, 'id', 'asc', 0, '', $lang);
        	if(is_null($ids)) throw new Exception("argument is not an array of objects identifiers : '$ids'", INVALID_PARAM);

			$object = &$this->getObjectStaticInstance($object_class);
			$schema = $object->getSchema();

			// if no fields have been defined, then we will return every simple fields of the object
			// (we also add functional fields having store attribute set to true)
			if(empty($fields)) {
				$fields = array();
				foreach($schema as $field => $def) {
//					if(in_array($def['type'], self::$simple_types) || (isset($def['store']) && $def['store'] && in_array($def['result_type'], self::$simple_types)))
// todo: to validate (this could slow the process when listing lots of objects with computed fields, since they must be computed each time)
					if(in_array($def['type'], self::$simple_types) || ($def['type'] == 'function' && isset($def['result_type']) && in_array($def['result_type'], self::$simple_types)))
						$fields[] = $field;
				}
			}
			else {
				// let's be kind and remove unexisting fields
				// values from fields array must be in keys from schema
				$allowed_fields = array_keys($schema);
				for($i = 0, $j = count($fields); $i < $j; ++$i) {
					if(!in_array($fields[$i], $allowed_fields)) unset($fields[$i]);
				}
			}
			// if the script is running in standalone mode
			if(OPERATION_MODE == 'standalone') {
				// first we request all fields of all objects at once to generate a bulk query
				// in order to minimize the number of SQL queries
				// (if some multilang field are required they'll be loaded all at once from core_translation)
				// note : an sql query will be generated for:
				//  - all simple field (even the ones already loaded!)
				//  - complex fields not yet loaded
// todo : we could maybe improve this by removing objects already fully loaded from the ids list
				$this->loadObjectFields($user_id, $object_class, $ids, $fields, $lang);
			}

			foreach($ids as $object_id) {
				if($object_id == 0) {
					// we cannot use getFields here, since it would result in the creation of a new object (which is a behaviour reserved to the 'update' method)
					// so in order to get the default values we use the "static instance"
					$result[$object_id] = $object->getValues($fields, $lang);
				}
				else {
					if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_READ)) throw new Exception("user '$user_id' does not have permission to read object '$object_id' of class '$object_class'", NOT_ALLOWED);
					$result[$object_id] = $this->getFields($user_id, $object_class, $object_id, $fields, $lang);
					$this->setLog($user_id, R_READ, $object_class, $object_id, $fields, $lang);
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
	* 	accepted operators are : '=', '<', '>',' <=', '>=', '<>', 'like' (case-sensitive), 'ilike' (case-insensitive), 'in', 'contains'
	* 	example : array( array( array('title', 'like', '%foo%'), array('id', 'in', array(1,2,18)) ) )
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
	public function search($user_id, $object_class, $domain=null, $order='id', $sort='asc', $start='0', $limit='0', $lang=DEFAULT_LANG) {
// todo : if no order field is specifield, use fields returned by optional method 'getOrder', if any
		try {
			if(!IdentificationManager::hasRight($user_id, $object_class, 0, R_READ)) throw new Exception("user($user_id) does not have permission to read objects of class ($object_class)", NOT_ALLOWED);
			if(empty($order)) throw new Exception("sort field cannot be empty", INVALID_PARAM);
			$res_list = array();
			$res_assoc_db = array();
			$valid_operators = array(
								'boolean'		=> array('=', '<>', '<', '>'),
								'integer'		=> array('in', 'not in', '=', '<>', '<', '>', '<=', '>='),
								'float'			=> array('=', '<>', '<', '>', '<=', '>='),
								'string'		=> array('like', 'ilike', '=', '<>'),
								'short_text'	=> array('like', 'ilike','='),
								'text'			=> array('like', 'ilike','='),
								'date'			=> array('=', '<>', '<', '>', '<=', '>=', 'like'),
								'time'			=> array('=', '<>', '<', '>', '<=', '>='),
								'datetime'		=> array('=', '<>', '<', '>', '<=', '>='),
								'timestamp'		=> array('=', '<>', '<', '>', '<=', '>='),
								'selection'		=> array('in', '=', '<>'),
								'binary'		=> array('like', 'ilike', '='),
								// contains is allowed for many2one field (for compatibilty reasons)
								// note: 'contains' operator means 'list contains at least one of the following ids'
								'many2one'		=> array('is', 'in', '=', 'contains'),
								'one2many'		=> array('contains'),
								'many2many'		=> array('contains'),
							);

			$conditions = array(array());
			$tables = array();

			// we use a nested closure to define a function that stores original table names and returns corresponding aliases
			$add_table = function ($table_name) use (&$tables) {
// todo : following condition has not been tested yet (should be ok though)
				if(in_array($table_name, $tables)) return array_search($table_name, $tables);
				$table_alias = 't'.count($tables);
				$tables[$table_alias] = $table_name;
				return $table_alias;
			};

			$schema = $this->getObjectSchema($object_class);
			$table_alias = $add_table($this->getObjectTableName($object_class));

			// first pass : build conditions and the tables names arrays
			if(!empty($domain) && !empty($domain[0]) && !empty($domain[0][0])) { // domain structure is correct and contains at least one condition

				// we check, for each clause, if it's about a "special field"
				$special_fields = \core\Object::getSpecialFields();

				for($j = 0, $max_j = count($domain); $j < $max_j; ++$j) {
					for($i = 0, $max_i = count($domain[$j]); $i < $max_i; ++$i) {
						if(!isset($domain[$j][$i]) || !is_array($domain[$j][$i])) throw new Exception("malformed domain", INVALID_PARAM);
						if(!isset($domain[$j][$i][0]) || !isset($domain[$j][$i][1]) || !isset($domain[$j][$i][2])) throw new Exception("invalid domain, a mandatory attribute is missing", INVALID_PARAM);
						$field		= $domain[$j][$i][0];
						$operator	= strtolower($domain[$j][$i][1]);
						$value		= $domain[$j][$i][2];
						$type 		= $schema[$field]['type'];

						if(!self::checkFieldAttributes(self::$mandatory_attributes, $schema, $field)) throw new Exception("missing at least one mandatory parameter for field '$field' of class '$object_class'", INVALID_PARAM);

						if(in_array($type, array('function', 'related'))) $type = $schema[$field]['result_type'];

						// check the validity of the field name and the operator
						if(!in_array($field, array_keys($schema))) throw new Exception("invalid domain, unexisting field '$field' for object '$object_class'", INVALID_PARAM);
						if(!in_array($operator, $valid_operators[$type])) throw new Exception("invalid operator '$operator' for field '$field' of type '{$schema[$field]['type']}' (result type: $type) in object '$object_class'", INVALID_PARAM);
						// remember special fields involved in the domain (by removing them from the special_fields list)
						if(isset($special_fields[$field])) unset($special_fields[$field]);

						// note: we don't test user permissions on foreign objects here
						switch($type) {
							case 'many2one':
								// use operator '=' instead of 'contains' (which is not sql standard)
								if($operator == 'contains') $operator = '=';
								break;
							case 'one2many':
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
								// add related table to sql query
								$rel_table_alias = $add_table($schema[$field]['rel_table']);
								// if the relation points out to objects of the same class
								if($schema[$field]['foreign_object'] == $object_class) {
									// add the join condition on 'rel_foreign_key'
									$conditions[$j][] = array($table_alias.'.id', '=', '`'.$rel_table_alias.'`.`'.$schema[$field]['rel_foreign_key'].'`');
									// use 'rel_local_key' column as comparison field
									$field = $rel_table_alias.'.'.$schema[$field]['rel_local_key'];
								}
								else {
									// add the join condition on 'rel_local_key'
									$conditions[$j][] = array($table_alias.'.id', '=', '`'.$rel_table_alias.'`.`'.$schema[$field]['rel_local_key'].'`');
									// use 'rel_foreign_key' column as comparison field
									$field = $rel_table_alias.'.'.$schema[$field]['rel_foreign_key'];
								}
								// use operator 'in' instead of 'contains' (which is not sql standard)
								$operator = 'in';
								break;
							default:
								// add some conditions if field is multilang (and the search is made on another language than the default one)
								if($lang != DEFAULT_LANG && isset($schema[$field]['multilang']) && $schema[$field]['multilang']) {
// todo : validate this code
									$translation_table_alias = $add_table('core_translation');
									// add joint conditions
									$conditions[$j][] = array($table_alias.'.id', '=', '`'.$translation_table_alias.'.object_id`');
									$conditions[$j][] = array($translation_table_alias.'.object_class', '=', $object_class);
									$conditions[$j][] = array($translation_table_alias.'.object_field', '=', $field);
									$field = $translation_table_alias.'.value';
								}
								// simple fields always match table fields
								else $field = $table_alias.'.'.$field;
								break;
						}
						$conditions[$j][] = array($field, $operator, $value);
					}
					// search only among non-draft and non-deleted records
					// (unless at least one clause was related to those fields - and consequently corresponding key in array $special_fields has been unset in the code above)
					if(isset($special_fields['modifier']))	$conditions[$j][] = array($table_alias.'.modifier', '>', '0');
					if(isset($special_fields['deleted']))	$conditions[$j][] = array($table_alias.'.deleted', '=', '0');
				}
			}
			else { // no domain is specified
				// search only among non-draft and non-deleted records
				$conditions[0][] = array($table_alias.'.modifier', '>', '0');
				$conditions[0][] = array($table_alias.'.deleted', '=', '0');
			}

			// second pass : fetch the ids of matching objects
			$select_fields = array($table_alias.'.id');
			$order_table_alias = $table_alias;
			$order_field = $order;
			// we might need to request more than the id field (for example, for sorting purpose)
			if(isset($schema[$order]['multilang']) && $schema[$order]['multilang']) {
// todo : validate this code (we should probabily add joint conditions in some cases)
				$translation_table_alias = $add_table('core_translation');
				$select_fields[] = $translation_table_alias.'.value';
				$order_table_alias = $translation_table_alias;
				$order_field = 'value';
			}
			elseif($order != 'id') $select_fields[] = $table_alias.'.'.$order;
			// get the matching records by generating the resulting SQL query
			$res = $this->dbConnection->getRecords($tables, $select_fields, null, $conditions, $table_alias.'.id', $order_table_alias.'.'.$order_field, $sort, $start, $limit);
			while ($row = $this->dbConnection->fetchArray($res)) {
				// if we are in standalone mode, we take advantage of the SQL sort
				$res_list[] = $row['id'];
				// if we are in client-server mode, we could need further sort
				$res_assoc_db[$row['id']] = $row[$order_field];
			}

// todo : validate this code
			// if we are running in client-server mode, some objects may have been modified and changes not yet been stored in database
			if(OPERATION_MODE == 'client-server' && isset($this->objectsArray[$object_class])) {
				// note: if we are here, we know that OPERATION_SIDE is 'server'
				$res_assoc_mem = array();
				// get all ids of (partialy) loaded objects from the specified class that have not been selected by the SQL query
				$ids = array_diff(array_keys($this->objectsArray[$object_class]), $res_list);
				for($i = 0, $j = count($ids); $i < $j; ++$i) {
					$object = &$this->objectsArray[$object_class][$ids[$i]];
					// check if the object does match the domain criterias
					foreach($domain as $conditions_list) {
						// each group of disjunctions may be a match
						$match = true;
						foreach($conditions_list as $condition) {
								//$condition[0] is the field name ; $condition[1] is the operator; $condition[2] is the value to which the field has to be compared to
								$values = $object->getValues(array($condition[0]), $lang);
								$value = $values[$condition[0]];
								switch($condition[1]) {
									case '=':			$match = ($value == $condition[2]);
										break;
									case '<':			$match = ($value < $condition[2]);
										break;
									case '>':			$match = ($value > $condition[2]);
										break;
									case '<>':			$match = ($value != $condition[2]);
										break;
									case '<=':			$match = ($value <= $condition[2]);
										break;
									case '>=':			$match = ($value >= $condition[2]);
										break;
									case 'in':			$match = in_array($value, $condition[2]);
										break;
									case 'not in':  	$match = !(in_array($value, $condition[2]));
										break;
									case 'contains':    $match = in_array($condition[2], $value);
										break;
									case 'like':
										$condition[2] = str_replace('%', '', $condition[2]);
										$match = strpos($value, $condition[2]);
										break;
									case 'ilike':
										$condition[2] = str_replace('%', '', $condition[2]);
										$match = stripos($value, $condition[2]);
										break;
								}
								// if one of the conditions is not met, we can stop and try the next disjunction
								if($match === false) break;
						}
						if($match !== false) {
							// if any of the disjunctions is a match the whole domain is validated :
							// we add the object to the result list and we go to the next object
							// we keep the value of the 'order' field for further sort
							$values = $object->getValues(array($order), $lang);
							$res_assoc_mem[$ids[$i]] = $values[$order];
							break;
						}
					}
				}
				// if we found at least one more object matching the domain
				if(count($res_assoc_mem)) {
					// we merge both associative arrays (from DB and from memory), we sort the result and keep only the keys (which hold matching objects ids)
					$res_list = array_keys(asort(array_merge($res_assoc_db, $res_assoc_mem)));
				}
			}
			else $res_list = array_unique($res_list);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$res_list = $e->getCode();
		}
		return $res_list;
	}


// Methods related to modifying objects


	/**
	* Sets the values of one or more instance(s) or creates new object(s) (if specified $object_id is 0).
	* This method expects an array with new values for specified objects.
	* In case new objects are created, an array is returned containing associated new ids.
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $ids
	* @param array $values
	* @return mixed (integer - error code if something went wrong - or array containing ids of newly created objects - if none, array is empty)
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
				if($object_id == 0) $action = R_CREATE;
				else $action = R_WRITE;
				// checking for permissions
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, $action)) throw new Exception("user '$user_id' does not have permission to write object '$object_id' of class '$object_class'", NOT_ALLOWED);
				$id = $this->setFields($user_id, $object_class, $object_id, $values, $lang);
				// log the current action
				$this->setLog($user_id, $action, $object_class, $id, array_keys($values), $lang);
				// add object identifier to the result array
				$result[] = $id;
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
			// 1) check rights and object schema
			$object = &$this->getObjectStaticInstance($object_class);
			$schema = $object->getSchema();

			foreach($ids as $object_id) {
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_DELETE)) throw new Exception("user($user_id) does not have permission to remove object($object_class)", NOT_ALLOWED);
				foreach($schema as $field => $def) {
// todo : handle other relation types
					if($def['type'] == 'one2many') {
						$res = $this->browse($user_id, $object_class, array($object_id), array($field));
						$this->update($user_id, $def['foreign_object'], $res[$object_id][$field], array($def['foreign_field'] => '0'));
					}
				}
			}

			// 2) remove object from DB
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
			foreach($ids as $object_id) $this->setLog($user_id, $log_action, $object_class, $object_id, $log_fields);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = $e->getCode();
		}
		return $result;
	}
}