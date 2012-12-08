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


// todo : finish comments


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

	private $simple_types	= array('boolean', 'integer', 'string', 'short_text', 'text', 'date', 'time', 'datetime', 'timestamp', 'selection', 'binary', 'many2one');
	private $complex_types	= array('one2many', 'many2many', 'related', 'function');

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
	* Returns a static instance for the specified object class
	*
	* @param string $object_class
	*/
	private function &getObjectStaticInstance($object_class) {
		try {
			// if it hasn't already be done, load the file containing the class declaration of the requested object
			if(!class_exists($object_class)) {
				// first, read the file content to see if the class extends from another, which could not be loaded yet
				// (we do so because we cannot use __autoload mechanism since the script may be run in CLI SAPI)
				$file_content = file_get_contents('library/classes/objects/'.$this->getObjectClassFileName($object_class).'.class.php', true);
				preg_match('/\bextends\b(.*)\{/iU', $file_content, $matches);
				if(!isset($matches[1])) throw new Exception("malformed class file for object '$object_class' : parent class name not found");
				else $parent_class = trim($matches[1]);
				// caution : no mutual inclusion check is done, so this call may result in an infinite loop
				if($parent_class != '\core\Object') $this->getObjectStaticInstance($parent_class);
				if(!load_class('objects/'.$this->getObjectClassFileName($object_class), $object_class)) throw new Exception("unknown object class : '$object_class'");
			}
			if(!isset($this->objectsArray[$object_class])) {
				$this->objectsArray[$object_class] = array();
				// "zero" indexes are used to store static instances with object default values
				$this->objectsArray[$object_class][0] = new $object_class(0);
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
			// ensure that $object_id has a valid value
			if(!is_numeric($object_id) || $object_id < 0) throw new Exception("incompatible value for object identifier : '$object_id'");
			// new instance, request for a new id
			if($object_id == 0) {
				// check user's permission for creation
				if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_CREATE)) throw new Exception("user($user_id) does not have permission to create object($object_class)");
				$this->dbConnection->addRecord($this->getObjectTableName($object_class), array('created' => date("Y-m-d H:i:s"), 'creator' => $user_id));
				$object_id = $this->dbConnection->getLastId();
				$this->logAction($user_id, 'R_CREATE', $object_class, array($object_id), null);
			}
			// check user's permission for reading
			elseif(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_READ)) throw new Exception("user($user_id) does not have read permission on the object($object_class, $object_id)");
			// check if object is already loaded
			if(isset($this->objectsArray[$object_class]) && isset($this->objectsArray[$object_class][$object_id])) $object = &$this->objectsArray[$object_class][$object_id];
	        else {
				// new instances are clones of static instances (so we don't need to re-compute default values for each new instance)
				$static_instance = &$this->getObjectStaticInstance($object_class);
				$object = clone $static_instance;
				// set the proper id
				$object->setId($object_id);
				$this->objectsArray[$object_class][$object_id] = &$object;
			}
			return $object;
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			throw new Exception('unable to get object instance');
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
			return false;
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
	* Loads a bunch of fields value form DB.
	* The function can load any type of field, however complex fields are loaded one by one, while simple fields are loaded all at once.
	*
	* @param integer $user_id
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields
	*/
	private function loadObjectFields($user_id, $object_class, $object_id, $object_fields, $lang) {
		try {
			// checks if the current user has R_READ permission for the specified object
			if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_READ)) throw new Exception("user does not have view permission on this object");
			// array to store the values of the loaded fields
			$value_array = array();
			// get the name of the DB table associated to the object
			$table_name = $this->getObjectTableName($object_class);
			// string to store which simple fields need to be loaded (this is done at the end of the function)
			$simple_fields = array();
			$simple_fields_multilang = array();
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
							if(!$this->checkFieldAttributes(array('result_type', 'foreign_object', 'path'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for function field '$field' of class '$object_class'");
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
								$path_object_class = $path_schema[$path_field]['foreign_object'];
								$path_prev_ids = $path_objects_ids;
								$path_schema = $this->getObjectSchema($path_object_class);
							}
							$value_array[$field] = $path_objects_ids;
							break;
						case 'function':
							if(!$this->checkFieldAttributes(array('function', 'result_type'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for function field '$field' of class '$object_class'");
							if(!is_callable($schema[$field]['function'])) throw new Exception("error in schema parameter for function field '$field' of class '$object_class' : function cannot be called");
							$value_array[$field] = call_user_func($schema[$field]['function'], $this, $user_id, $object_id, $lang);
							break;
						case 'one2many':
						case 'many2many':
							if(!$this->checkFieldAttributes(array('foreign_object','foreign_field'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for one2many field '$field' of class '$object_class'");
							// obtain the ids by searching among objects having symmetrical field ('foreign_field') set to $object_id
		                    $value_array[$field] = $this->search($user_id, $schema[$field]['foreign_object'], array(array($schema[$field]['foreign_field'], ($schema[$field]['type']=='many2many')?'contains':'in', $object_id)));
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
				$result = $this->dbConnection->getRecords(array('core_translation'), array('object_field', 'value'), array($object_id), array(array('lang','=',$lang), array('object_class','=',$object_class), array('object_field','in',$simple_fields_multilang)), 'object_id');
				while($row = $this->dbConnection->fetchArray($result)) {
					// check value to ensure not to erase default value for empty field
					if(!$this->dbConnection->isEmpty($row['value'])) $value_array[$row['object_field']] = $row['value'];
				}
			}
			// set the object values according to the loaded fields (do not mark as modified)
			$object->setValues($value_array, $lang, false);
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
	* @param array $object_fields
	*/
// todo (!multilang fields)
	private function storeObject($object_class, $object_id, $object_fields=null) {
		if($object_id <= 0) throw new Exception("unable to store non-existing object : create a new instance first");
		$object =  &$this->getObjectInstance($user_id, $object_class, $object_id);
		// get the columns of the object (i.e. schema without special fields)
		$columns = $object->getColumns();
		$table_name = $this->getObjectTableName($object_class);
		$table_prefix = (strlen($prefix = $this->getObjectPackageName($object_class)))?$prefix.'_':'';
		// array to handle names of the fields that must be stored
		$simple_fields = array();
		// id of the current logged in user
		$current_user_id = IdentificationManager::getCurrentUserId();
		// if no fields have been specified, store every simple fields of the object
		if(empty($object_fields)) $object_fields = $object->getFieldsNames($this->simple_types);
		// first pass :  list all the names of the simple fields that must be stored
		foreach($object_fields as $field) {
			if(!isset($columns[$field]) || !isset($columns[$field]['type'])) throw new Exception("unknown field or missing mandatory data for field '$field' of class '$object_class'");
			if(in_array($columns[$field]['type'], $this->simple_types)) $simple_fields[] = $field;
		}
		// second pass :  store all simple fields at once, if any
		if(!empty($simple_fields)) {
			$fields_array = &$object->getValues($simple_fields);
			try {
				$fields_array['modified'] = date("Y-m-d H:i:s");
				$fields_array['modifier'] = $current_user_id;
				$this->dbConnection->setRecord($this->getObjectTableName($object_class), array($object_id), $fields_array);
				$this->logAction('R_WRITE', $object_class, array($object_id), array_keys($fields_array));
			}
			catch (Exception $e) {
				ErrorHandler::ExceptionHandling($e, __METHOD__);
			}
		}
		// third pass : store complex fields one by one
		foreach($object_fields as $field) {
			if(!isset($columns[$field]) || !isset($columns[$field]['type'])) throw new Exception("unknown field or missing mandatory data for field '$field' of class '$object_class'");
			$field_value = $object->getValues(array($field));
			switch($columns[$field]['type']) {
				//nothing to do for 'one2many' fields as thses relations are managed on the many2one side
				case 'many2many':
					$mandatory_keys = array('rel_table', 'rel_foreign_key', 'rel_local_key', 'foreign_object', 'foreign_field');
					if(!$this->checkFieldAttributes($mandatory_keys, $columns, $field)) throw new Exception("missing at least one mandatory parameter for field '$field' of class '$object_class', mandatory parameters are ".implode(', ', $mandatory_keys));
					try {
						$fields_array = array();
						$ids = array('add'=>array(), 'del'=>array());
// the field must be an array
						foreach($field_value as $id_value) {
							if(strstr($id_value, '-') == false) $ids['del'][] = str_replace('-', '', $id_value);
							else $ids['add'][] = str_replace('+', '', $id_value);
						}

// todo : en cours - fonctionnement à définir
						// delete relations for ids having a '-' prefix
						$this->dbConnection->sendQuery("delete from `{$table_prefix}{$columns[$field]['rel_table']}` where `{$columns[$field]['local_key']}` = $object_id and `{$table_prefix}{$columns[$field]['rel_table']}` in (".implode(',', $ids['del']).");");

						// create relations for ids having a '+' prefix
						$this->dbConnection->addRecord($table_prefix.$columns[$field]['rel_table'], $fields_array);
// to continue

						// if object already exists then delete existing relation
						if(!$is_new_object) $this->dbConnection->sendQuery("delete from `{$table_prefix}{$columns[$field]['rel_table']}` where `{$columns[$field]['local_key']}` = $object_id;");
						// test default values for specific fields
						$res = $this->dbConnection->sendQuery("show full columns from `{$table_prefix}{$columns[$field]['rel_table']}`;");
						while($row = $this->dbConnection->fetchArray($res)) {
							 switch($row['Field']) {
								case 'sequence' :
									if($field == 'sequence') break;
									$res2 = $this->dbConnection->sendQuery("select max(`sequence`) as `index` from `{$table_prefix}{$columns[$field]['rel_table']}` where `{$columns[$field]['local_key']}` = '$object_id';");
									if($row2 = $this->dbConnection->fetchArray($res2)) $fields_array['sequence'] = $row2['index'] + 1;
									else $fields_array['sequence'] = 1;
									break;
							 }
						}
						$fields_array[$columns[$field]['local_key']] = $object_id;
						foreach($field_value as $key => $id_value) {
							$fields_array[$columns[$field]['rel_key']] = $id_value;
							$this->dbConnection->addRecord($table_prefix.$columns[$field]['rel_table'], $fields_array);
							if(isset($fields_array['sequence'])) ++$fields_array['sequence'];
						}
					}
					catch(Exception $e) {
						ErrorHandler::ExceptionHandling($e, __METHOD__);
					}
					break;
				case 'related':
					// todo : in case the 'store' attribute is set
					break;
				case 'function':
					// todo : in case the 'store' attribute is set
					break;
			}
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
	private function &getFields($user_id, $object_class, $object_id, $object_fields=null, $lang) {
		try {
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			// if no fields have been defined, then we will return every simple fields of the object
 			if(empty($object_fields)) $object_fields = $object->getFieldsNames($this->simple_types);
 			else $object_fields = array_unique($object_fields);
			// first, determine which fields have not yet been loaded
			$missing_fields = array_diff($object_fields, $object->getLoadedFields());
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
	*
	*	methods related to logs
	*	***********************
	*/

	/**
	* Adds a log to database.
	* Everytime a change to an object occurs, one record is created for each modified field (this allows to quickly retrieve when a specifiec field was modified, useful for stored functional fields that are related to another field).
	* Note : Here, we cannot call the update method because as there is a call to logAction in the storeObject method, it would result in an infinite loop.
	*
	* @param string $action
	* @param string $object_class
	* @param mixed $object_id
	* @param array $object_fields
	*/
	private function logAction($user_id, $action, $object_class, $ids, $fields) {
		if(defined('DISABLE_LOGGING') || DISABLE_LOGGING) return;
		foreach($ids as $object_id) {
			$fields_array = array('created'			=> date("Y-m-d H:i:s"),
								  'creator'			=> $user_id,
								  'action'			=> $action,
								  'object_class'	=> $object_class,
								  'object_id'		=> $object_id
							);
			foreach($fields as $field) {
				if(in_array($field, array('id', 'created', 'modified', 'creator', 'modifier'))) continue;
				try {
					$fields_array['object_field'] = $field;
					$this->dbConnection->addRecord($this->getObjectTableName('core\Log'), $fields_array);
				}
				catch (Exception $e) {
					ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
				}
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
	*
	*/
	public function store() {
/*
todo : change technique
utiliser des objets log en mémoire pour toutes les modifications
sauvegarder les nouvelles valeurs des objets en utilisant les logs (modifier et modified)
*/
		foreach($this->objectsArray as $object_class => $objects_list) {
			foreach($objects_list as $object_id => $object) {
				// (zero ids are static instances)
				if($object_id > 0) {
					// check for modifications
					$modified_fields = $object->getModifiedFields();
					if(count($modified_fields)) {
						// store changes to database
						$this->storeObject($object_class, $object_id, $modified_fields);
						// reset the modification flag
						$object->resetModifiedFields();
					}
				}
			}
		}
	}

	/**
	*
	*	Main final methods (validate, get, browse, search, update, remove)
	*	******************************************************************
	*/

	/**
	* Checks whether the values of some object fields are valid or not.
	* The returned value is either FALSE or an array associating, for each invalid field, field name and error message id.
	*
	* @param string $object_class object class
	* @param array $values
	* @return mixed (boolean or array)
	*/
	public function validate($object_class, $values) {
		try {
			$result = $this->checkFieldsValidity($object_class, $values);
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

	/**
	* Returns objects of requested ids for the specified class.
	* The returned structure is an associative array containing, for every object id, a sub array maping each field to its value.
	*
	* @param integer $user_id
	* @param string $object_class object class
	* @param array $ids ids of the objects to return
	* @param array $fields required field
	* @param string $lang
	* @return array
	*/
	public function &browse($user_id, $object_class, $ids=null, $fields=null, $lang=DEFAULT_LANG) {
		try {
			$result = array();
			if(!empty($ids) && !is_array($ids)) throw new Exception("argument is not an array of objects identifiers : '$ids'");
			if(!empty($fields) && !is_array($fields)) throw new Exception("argument is not an array of objects fields : '$fields'");
			if(is_null($ids)) $ids = $this->search($user_id, $object_class, null, '', 'asc', 0, '', $lang);
			foreach($ids as $id) $result[$id] = $this->getFields($user_id, $object_class, $id, $fields, $lang);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = false;
		}
		return $result;
	}

	/**
	* Search for the objects corresponding to the domain criteria.
	* This method essentially generates an SQL query and returns an array of matching objects ids.
	*
	* 	The domain syntax is : array( array(field, operator, value) [,array(field, operator, value), ...])
	* 	accepted operators are : '=', '<', '>',' <=', '>=', '<>', 'like', 'ilike', 'in', 'contains'
	* 	example : array( array('title', 'like', '%foo%'), array('id', 'in', array(1,2,18)) )
	* 	note : conditions of the domain are restrictive (AND operator)
	*
	* @param integer $user_id
	* @param string $object_class
	* @param array $domain
	* @param string $order
	* @param string $sort ('asc' or 'desc')
	* @param integer $start
	* @param string $limit
	* @return array
	*/
	public function search($user_id, $object_class, $domain=null, $order='', $sort='asc', $start=0, $limit='', $lang=DEFAULT_LANG) {
		try {
			if(!IdentificationManager::hasRight($user_id, $object_class, 0, R_READ)) throw new Exception("user($user_id) does not have permission to read objects of class ($object_class)");
			$res_list = array();
			$valid_operators = array(
								'boolean'		=> array('=', '<>'),
								'integer'		=> array('in', '=', '<>', '<', '>', '<=', '>='),
								'string'		=> array('like', 'ilike', '=', '<>'),
								'short_text'	=> array('like', 'ilike','='),
								'text'			=> array('like', 'ilike','='),
								'date'			=> array('=', '<>', '<', '>', '<=', '>='),
								'time'			=> array('=', '<>', '<', '>', '<=', '>='),
								'datetime'		=> array('=', '<>', '<', '>', '<=', '>='),
								'timestamp'		=> array('=', '<>', '<', '>', '<=', '>='),
								'selection'		=> array('in', '=', '<>'),
								'many2one'		=> array('in', '='),
								'one2many'		=> array('contains'),
								'many2many'		=> array('contains'),
								'related'		=> array('contains', '=', '<>', '<', '>', '<=', '>='),
							);

			$conditions = array();
			$tables = array();
			// we use a nested closure to define a function (whose use is limited to this method) that stores original table names and returns corresponding aliases
			$add_table = function ($table_name) use (&$tables) {
				$table_alias = 't'.count($tables);
				$tables[$table_alias] = $table_name;
				return $table_alias;
			};

			$table_alias = $add_table($this->getObjectTableName($object_class));

			if(!empty($domain)) {
				$count = count($domain);
				$schema = $this->getObjectSchema($object_class);
				// first pass : build conditions and the tables names arrays
				for($i = 0; $i < $count; ++$i) {
					if(!isset($domain[$i]) || !is_array($domain[$i])) throw new Exception("malformed domain");
					if(!isset($domain[$i][0]) || !isset($domain[$i][1]) || !isset($domain[$i][2])) throw new Exception("invalid domain, a mandatory attribute is missing");
					$field		= $domain[$i][0];
					$operator	= $domain[$i][1];
					$value		= $domain[$i][2];

					if(!in_array($field, array_keys($schema))) throw new Exception("invalid domain, unexisting field '$field' for object '$object_class'");
					if(!in_array($operator, $valid_operators[$schema[$field]['type']])) throw new Exception("invalid operator '$operator' for field '$field' of type '{$schema[$field]['type']}' in object '$object_class'");
					// todo : test user permissions (on foreign objects)

					switch($schema[$field]['type']) {
						case 'one2many':
							if(!$this->checkFieldAttributes(array('foreign_object','foreign_field'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for one2many field '$field' of class '$object_class'");
							// add foreign table to sql query
							$foreign_table_alias =  $add_table($this->getObjectTableName($schema[$field]['foreign_object']));
							// add the join condition
							$conditions[] = array($foreign_table_alias.'.'.$schema[$field]['foreign_field'], '=', '`'.$table_alias.'`.`id`');
							// as comparison field, use foreign table's 'foreign_key' if any, 'id' otherwise
							if(isset($schema[$field]['foreign_key'])) $field = $foreign_table_alias.'.'.$schema[$field]['foreign_key'];
							else $field = $foreign_table_alias.'.id';
							// use operator 'in' instead of 'contains' (which is not sql standard)
							$operator = 'in';
							break;
						case 'many2many':
							if(!$this->checkFieldAttributes(array('rel_table','rel_local_key','rel_foreign_key'), $schema, $field)) throw new Exception("missing at least one mandatory parameter for many2many field '$field' of class '$object_class'");
							// add related table to sql query
							$table_prefix = (strlen($prefix = $this->getObjectPackageName($object_class)))?$prefix.'_':'';
							$rel_table_alias =  $add_table($table_prefix.$schema[$field]['rel_table']);
							// add the join condition
							$conditions[] = array($table_alias.'.id', '=', '`'.$rel_table_alias.'`.`'.$schema[$field]['rel_local_key'].'`');
							// use rel table's 'rel_foreign_key' as comparison field
							$field = $rel_table_alias.'.'.$schema[$field]['rel_foreign_key'];
							// use operator 'in' instead of 'contains' (which is not sql standard)
							$operator = 'in';
							break;
						case 'related':
							// todo - improvement : add possibilty to use a 'store' attribute
							throw new Exception("no search allowed for 'related' field '$field' of class '$object_class'");
							break;
						case 'function':
							// todo - improvement : add possibilty to use a 'store' attribute
							throw new Exception("no search allowed for 'function' field '$field' of class '$object_class'");
							break;
						default:
							// simple fields always match table fields
							$field = $table_alias.'.'.$field;
							break;
					}
					$conditions[] = array($field, $operator, $value);
				}
			}

			// search only among non-deleted records
			$conditions[] = array($table_alias.'.deleted', '=', '0');

			// second pass : fetch the ids of matching objects
			$res = $this->dbConnection->getRecords($tables, array($table_alias.'.id'), null, $conditions, $table_alias.'.id', $order, $sort, $start, $limit);
			while ($row = $this->dbConnection->fetchArray($res)) $res_list[] = $row['id'];
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$res_list = false;
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
	*
	* @param integer $user_id
	* @param string $object_class
	* @param integer $object_id
	* @param array $values
	*
	* object_id may be equal to 0, in that case, a new object is created
	*/
	public function update($user_id, $object_class, $object_id, &$values) {
		try {
			if(!is_array($values)) throw new Exception("invalid request or missing mandatory value");
			if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_WRITE)) throw new Exception("user($user_id) does not have permission to modify object($object_class)");
			$object = &$this->getObjectInstance($user_id, $object_class, $object_id);
			$columns = $object->getColumns();

			$fields_values = array();
			foreach($values as $field => $value) {
			  if(!isset($columns[$field])) continue;
			  switch($columns[$field]['type']) {
				case 'short_text':
					  $fields_values[$field] = str_replace(array("\r\n", "\n","\r"), '<br />', $value);
					  break;
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
					default :
						$fields_values[$field] = $value;
						break;
			  }
			}
			// update object and mark fields as modified
			$object->setValues($fields_values);
			return true;
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE.', '.__METHOD__);
			return false;
		}
	}

	/**
	* Removes objects of requested ids.
	* Soft deletion (when $permanent flag is set to false) is similar to a trash in which it is possible to retrieve the deleted objet(s).
	* On the other hand, permanent deletion cannot be undone.
	*
	* @param integer $user_id
	* @param string $object_class object class
	* @param array $ids ids of the objects to return
	* @param boolean $permanent indicates if we want a soft or permanent deletion
	* @return boolean
	*/
	public function remove($user_id, $object_class, $ids, $permanent=false) {
		try {
			if(!IdentificationManager::hasRight($user_id, $object_class, $object_id, R_DELETE)) throw new Exception("user($user_id) does not have permission to remove object($object_class)");
			$table_name = $this->getObjectTableName($object_class);
			if ($permanent) {
				$this->dbConnection->deleteRecords($table_name, $ids);
				$this->logAction('R_DELETE', $object_class, $ids, null);
			}
			else {
				$this->dbConnection->setRecords($table_name, $ids, array('deleted'=>1));
				$this->logAction('R_WRITE', $object_class, $ids, array('deleted'));
			}
			return true;
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			return false;
		}
	}
}
