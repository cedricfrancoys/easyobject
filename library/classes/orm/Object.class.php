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
namespace core;

class Object {

	/**
	 * Object fields values
	 * $fields_values[lang][field]
	 *
	 * @var array
	 * @access private
	 */
	private $fields_values;

	private $langs;

	/**
	* Associative array which keys are names of modified fields
	*
	* @var array
	* @access private
	*/
	private $modified_fields;

	/**
	* Associative array which keys are names of loaded fields
	* This is required in order to make the distinction between loaded field and those assigned with default value
	*
	* @var array
	* @access private
	*/
	private $loaded_fields;

	/**
	 * Complete object schema, containing all columns (including special ones as object id)
	 *
	 * @var array
	 * @access private
	 */
	private $schema;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param  integer $id
	 */
	public final function __construct() {
		$this->langs = array(DEFAULT_LANG);
		// As a convention, the id field is always set for (and only for) the default language (as it never can be  multilang)
		$this->fields_values	= array(DEFAULT_LANG=>array('id'=>0));
		$this->loaded_fields	= array(DEFAULT_LANG=>array());
		$this->modified_fields	= array(DEFAULT_LANG=>array());
		$this->schema = array_merge($this->getSpecialFields(), $this->getColumns());
		$this->setDefaults();
	}

	private final function setDefaults() {
		if(method_exists($this, 'getDefaults')) {
			$defaults = $this->getDefaults();
			// get default values, set fields for default language, and mark fields as modified
			foreach($defaults as $field => $default_value) if(isset($this->schema[$field]) && is_callable($default_value)) $fields_values[$field] = call_user_func($default_value);
			// we use the SYSTEM_USER_ID (=0) so that the modifier field is left to 0
			// (which is necessary to make the distinction between objects being created and objects actually created)
			$this->setValues(SYSTEM_USER_ID, $fields_values);
    	}
	}

	public final static function getSpecialFields() {
		return array(
			'id'		=> array('type' => 'integer'),
			'created'	=> array('type' => 'datetime'),
			'modified'	=> array('type' => 'datetime'),
			'creator'	=> array('type' => 'integer'),
			'modifier'	=> array('type' => 'integer'),
			'published'	=> array('type' => 'boolean'),
			'deleted'	=> array('type' => 'boolean')
		);
	}

	/**
	 * Gets object schema
	 *
	 * @access public
	 * @return array
	 */
	public final function getSchema() {
		return $this->schema;
	}

	/**
	* This method must be overridden by children classes
	*
	* @access public
	*/
	public static function getColumns() {
		return array();
	}

	public function getTable() {
		return strtolower(str_replace('\\', '_', get_class($this)));
	}

	/**
	* Gets object id
	*
	* @access public
	* @return integer The unique identifier of the current object (unicity scope is the object class)
	*/
	public final function getId() {
		return (isset($this->fields_values[DEFAULT_LANG]['id']))?$this->fields_values[DEFAULT_LANG]['id']:0;
	}

    public final function getUsedLangs() {
   		return $this->langs;
	}

	public final function getLoadedFields($lang=DEFAULT_LANG)  {
		if(!isset($this->loaded_fields[$lang])) return array();
		return $this->loaded_fields[$lang];
	}

	public final function resetLoadedFields($fields_list=null) {
		if($fields_list == null) $fields_list = $this->loaded_fields;
		foreach($fields_list as $lang => $fields) {
			foreach($fields as $field => $value) unset($this->loaded_fields[$lang][$field]);
		}
	}

	public final function getModifiedFields($lang=DEFAULT_LANG)  {
		if(!isset($this->modified_fields[$lang])) return array();
		return $this->modified_fields[$lang];
	}

	public final function resetModifiedFields($fields_list=null) {
		if($fields_list == null) $fields_list = $this->modified_fields;
		foreach($fields_list as $lang => $fields) {
			foreach($fields as $field => $value) unset($this->modified_fields [$lang][$field]);
		}
	}

	/**
	* Returns the fields names of the specified types
	*
	* @param array $types_list allows to restrict the result to specified types (the method willl only return fields from which type is present in the list)
	*/
	public final function getFieldsNames($types_list=null) {
		$result_array = array();
		if(!is_array($types_list) || is_null($types_list))	$result_array = array_keys($this->schema);
		else {
			foreach($this->schema as $field_name => $field_description) {
				if(in_array($field_description['type'], $types_list)) $result_array[] = $field_name;
			}
		}
		return $result_array;
	}

	/**
	 * Gets Object fields
	 * Returns the value, in the specified language, of each specified field
	 *
	 * @access public
	 * @param array $fields
	 * @param string $lang
	 * @return array
	 */
	public final function &getValues($fields=null, $lang=DEFAULT_LANG) {
		$result_array = array();
		if(is_null($fields)) $fields = array_keys($this->schema);
		foreach($fields as $field) {
			if(isset($this->fields_values[$lang]) && isset($this->fields_values[$lang][$field])) $result_array[$field] = $this->fields_values[$lang][$field];
			else $result_array[$field] = null;
		}
		return $result_array;
	}

	/**
	 * Sets Object fields
	 *
	 * @access public
	 * @param  array $values
	 * @param string $lang
	 * @param boolean $mark_as_modified
	 * @return boolean false if no array was given, true otherwise
	 */
	public final function setValues($user_id, $values, $lang=DEFAULT_LANG, $mark_as_modified = true) {
		if(!is_array($values)) return;
		$schema	= array_keys($this->schema);
		$keys	= array_keys($values);
		if(!in_array($lang, $this->langs)) {
				$this->langs[] = $lang;
				$this->fields_values[$lang]		= array();
				$this->modified_fields[$lang]	= array();
				$this->loaded_fields[$lang]		= array();
		}
		foreach($keys as $field) {
			if(in_array($field, $schema)) {
				// if it has not yet been modified, set the field to its new value
				if(!isset($this->modified_fields[$lang][$field])) {
					$this->fields_values[$lang][$field] = $values[$field];
					if($mark_as_modified) {
						// id is always set in DB (even for new objects)
						if($field != 'id') {
							$this->modified_fields[$lang][$field] = true;
							// modifier is the id of the last user who have made changes to the object
							$this->fields_values[$lang]['modified'] = date("Y-m-d H:i:s");
							$this->fields_values[$lang]['modifier'] = $user_id;
							$this->modified_fields[$lang]['modified'] = $this->loaded_fields[$lang]['modified'] = true;
							$this->modified_fields[$lang]['modifier'] = $this->loaded_fields[$lang]['modifier'] = true;
						}
					}
				}
				// mark field as loaded
				// (if field has been modified, this allows to make a check to prevent it from being overwritten with further loading operations)
				$this->loaded_fields[$lang][$field] = true;
			}
		}
	}

	/**
	* Magic method for handling dynamic getters and setters
	*
	*	Note : This mechanism only works under standalone mode
	*
	* @param string $name
	* @param array $arguments
	*/
	public function __call ($name, $arguments) {
		// get the parts of the virtual method invoked
		$method	= strtolower(substr($name, 0, 3));
		$field	= strtolower(substr($name, 3));
		// check that the specified field does exist
		if(in_array($field, array_keys(array_change_key_case($this->schema, CASE_LOWER)))) {
			switch($method) {
				case 'get':
					$values = $this->getValues(array($field));
					return $values[$field];
					break;
				case 'set':
					// we use the global method 'update' in order to retrieve the user id associated with the current session
					// thus, this method can only be used in standalone mode
					update(get_class($this), array($this->getId()), array($field=>$arguments[0]));
					break;
			}
		}
	}

}