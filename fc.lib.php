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
namespace {
	/**
	* Add some global system-related constants
	*/

	define('__FC_LIB', true) or die('either __FC_LIB is already defined or it cannot be defined');
	
	/**
	* Error codes
	*/
	define('UNKNOWN_ERROR',		 0);	// something went wrong (that requires to check the logs)
	define('INVALID_PARAM',		 1);	// one or more parameters have invalid or incompatible value
	define('SQL_ERROR',			 2);	// error while building SQL query or processing it (check that object class matches DB schema)
	define('UNKNOWN_OBJECT',	 4);	// unknown class or object
	define('NOT_ALLOWED',		 8);	// action violates some rule (including max size for binary fields) or user don't have required permissions

	/**
	* Debugging modes
	*/	
	define('DEBUG_PHP',			1);
	define('DEBUG_SQL',			2);
	define('DEBUG_ORM',			4);

	/**
	* Users & Groups permissions masks
	*/
	define('R_CREATE',	 		1);
	define('R_READ',	 		2);
	define('R_WRITE',	 		4);
	define('R_DELETE',	 		8);
	define('R_MANAGE',			16);

	/**
	* Built-in Users and Groups
	*
	* Note : make sure that the ids in DB are set and matching these
	*/
	define('SYSTEM_USER_ID',	0);	// this user is used when the modifier id must be kept to 0
	define('GUEST_USER_ID',		1);
	define('ROOT_USER_ID',		2);	
	define('DEFAULT_GROUP_ID',	1);	// default group (all users are members of the default group)

	/** 
	* Add some config-utility functions to the global namespace
	*/

	/**
	* Returns a configuraiton parameter.
	*/
	function config($name, $default=null) {
		return (isset($GLOBALS['CONFIG_ARRAY'][$name]))?$GLOBALS['CONFIG_ARRAY'][$name]:$default;
	}

	/**
	* Force the script to be either silent (no output) or verbose (according to DEBUG_MODE).
	* @param boolean $silent
	*/
	function set_silent($silent) {
		$GLOBALS['SILENT_MODE'] = $silent;
		ini_set('display_errors', !$silent);
		if($silent) error_reporting(0);
		else error_reporting(E_ALL);
	}

	/**
	* Returns the resulting debug mode (taking $SILENT_MODE under account)
	*/
	function debug_mode() { return ($GLOBALS['SILENT_MODE'])?0:DEBUG_MODE; }	

	// Set script as verbose by default (and ensure $GLOBALS['SILENT_MODE'] is set)
	set_silent(false);
}
namespace config {
	/**
	* Add some config-utility functions to the config namespace
	*/

	/**
	* Adds a parameter to the configuration array
	*/	
	function define($name, $value) {
		$GLOBALS['CONFIG_ARRAY'][$name] = $value;
	}

	/**
	* Checks if a parameter has already been defined
	*/	
	function defined($name) {
		return isset($GLOBALS['CONFIG_ARRAY'][$name]);
	}

	/**
	* Export parameters declared with config\define function, as constants (i.e.: accessible through global scope)
	*/
	function export_config() {
		if(!isset($GLOBALS['CONFIG_ARRAY'])) $GLOBALS['CONFIG_ARRAY'] = array();
		foreach($GLOBALS['CONFIG_ARRAY'] as $name => $value) {
			\defined($name) or \define($name, $value);
		}
	}
	
	/**
	*	FC library defines a set of functions whose purpose is to ease php scripting for :
	*	- classes and files inclusion (especially for cascading inclusions)
	*		load_class($class_path)
	*		include_file($file_name) : file that defines functions or constants (in order to use variables, one must use the global keyword)
	*	- extracting HTTP data (GET / POST/ COOKIE)
	*		extract_params($url)
	*	- script description and the parameters it should receive
	*
	*	Classes folder (either Zend or user-defined framework, or both) needs to be placed in a directory named 'library' located in the same folder as the current fc.lib.php file
	*	User-defined classes naming convention : ClassName.class.php
	*
	*	Expected tree structure :
	*		library
	*		library/classes
	*		library/files
	*		library/Zend
	*
	*	This file should be included only once (for example in the index.php file)
	*		ex. : include_once('fc.lib.php');
	*
	*	Any file requiring functions defined in this library must check its presence :
	*		ex. : defined('__FC_LIB') or die(__FILE__.' requires fc.lib.php');
	*/
	class FClib {

		/*
		* private methods
		*/

		/**
		* Gets the name of a class given the full path of the file containing its definition.
		*
		* @static
		* @param	string	$class_path
		* @return	string
		*/
		private static function get_class_name($class_path) {
			$parts = explode('/', $class_path);
			return end($parts);
		}

		/**
		* Gets the relative path of a file containing a class, given its full path.
		*
		* @static
		* @param	string	$class_path
		* @return	string
		*/
		private static function get_class_path($class_path) {
			$sub_path = substr($class_path, 0, strrpos($class_path, '/'));
			if(strlen($sub_path) > 0) {
				$sub_path .= '/';
			}
			return 'classes/'.$sub_path.self::get_class_name($class_path).'.class.php';
		}

		/**
		* Checks if the path contains the specified file , given its relative path.
		*
		* @static
		* @param	string	$filename
		* @return	bool
		*/
		private static function path_contains($filename) {
			$include_path = get_include_path();
			if(strpos($include_path, PATH_SEPARATOR)) {
				if(($temp = explode(PATH_SEPARATOR, $include_path))) {
					for($n = 0; $n < count($temp); ++$n) {
						if(file_exists($temp[$n].'/'.$filename)) return true;
					}
				}
			}
			return false;
		}

		/*
		* public methods
		*/

		/**
		* Gets the location of the given script, by default the current file (fc.lib.php)
		*
		* @static
		* @return	string
		*/
		public static function get_script_path($script=__FILE__) {
			$file_path = str_replace('\\', '/', $script);
			if(($pos = strrpos($file_path, '/')) !== false) {
				$file_path = substr($file_path, 0, $pos);
			}
			return $file_path;
		}

		/**
		* Adds the library folder to the include path (library folder should contain the Zend framework if required)
		*
		* @static
		*/
		public static function init() {
			$library_folder = self::get_script_path().'/library';
			if(is_dir($library_folder))	set_include_path($library_folder.PATH_SEPARATOR.get_include_path());
		}

		/**
		* Gets the complete URL (uniform resource locator)
		*
		* @static
		* @param	boolean $server_port	display server port (required when differs from 80)
		* @param	boolean	$query_string	display query_string (i.e.: script.php?...&...&...)
		* @return	string
		*/
		public static function get_url($server_port=true, $query_string=true) {
			$url = 'http://'.$_SERVER['SERVER_NAME'];
			if($server_port && $_SERVER['SERVER_PORT'] != '80')  $url .= ':'.$_SERVER['SERVER_PORT'];
			// add full request URI if required
			if($query_string) $url .= $_SERVER['REQUEST_URI'];	
			// otherwise get the base directory of the current script (assuming this script is located in the root installation dir)
			else $url .= substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')+1);
			return $url;
		}

		/**
		* Checks if all parameters have been received in the HTTP request. If not, the script is terminated.
		*
		* @deprecated : use announce method instead
		*
		* @static
		*/
		public static function check_params($mandatory_params) {
			if(count(array_intersect($mandatory_params, array_keys($_REQUEST))) != count($mandatory_params)) {
				// alternate output: send json data telling which params are expected
				echo json_encode(array('expected_params' => $mandatory_params), JSON_FORCE_OBJECT);
				// terminate script
				die();
			}
		}
		
		/**
		* Gets the value of a list of parameters received in the HTTP request. If a parameter is not defined, its default value is returned.
		*
		* @deprecated : use announce method instead
		*
		* @static
		* @param	array	$params
		* @param	array	$default_values
		* @return	array
		*/
		public static function get_params($params) {
			$result = array();
			foreach($params as $param => $default) {
				if(isset($_REQUEST[$param]) && !empty($_REQUEST[$param])) $result[$param] = $_REQUEST[$param];
				else $result[$param] = $default;
			}
			return $result;
		}

		/**
		* This method desribes the current script and its parameters. It also ensures that required parameters have been transmitted.
		* And, if necessary, sets default values for missing optional params.
		*
		* Accepted types for parameters types are : int, bool, float, string, array
		*	
		* @static
		* @param	array	$announcement	array holding the description of the script and its parameters
		* @return	array	parameters and their final values
		*/
		public static function announce($announcement) {		
			$result = array();
			// 1) check presence of all mandatory parameters
			// build mandatory fields array
			$mandatory_params = array();
			foreach($announcement['params'] as $param => $description) {
				if(isset($description['required']) && $description['required']) $mandatory_params[] = $param;
			}
			// if at least one mandatory param is missing
			if(count(array_intersect($mandatory_params, array_keys($_REQUEST))) != count($mandatory_params)) {
				// output json data telling what is expected
				echo json_encode(array('result'=>INVALID_PARAM,'announcement'=>$announcement), JSON_FORCE_OBJECT);
				// terminate script
				die();
			}
			// 2) find any missing parameters
			$allowed_params = array_keys($announcement['params']);
			$missing_params = array_diff($allowed_params, array_intersect($allowed_params, array_keys($_REQUEST)));
			// 3) build result array and set default values for optional missing parameters
			foreach($announcement['params'] as $param => $description) {
				if(in_array($param, $missing_params) || empty($_REQUEST[$param])) {
					if(!isset($announcement['params'][$param]['default'])) $_REQUEST[$param] = null;
					else $_REQUEST[$param] = $announcement['params'][$param]['default'];
				}
				// prevent some js/php misunderstanding
				if(in_array($_REQUEST[$param], array('NULL', 'null'))) $_REQUEST[$param] = null;
				if($announcement['params'][$param]['type'] == 'bool') {
					if(in_array($_REQUEST[$param], array('TRUE', 'true', '1', 1))) $_REQUEST[$param] = true;						
					if(in_array($_REQUEST[$param], array('FALSE', 'false', '0', 0))) $_REQUEST[$param] = false;			
				}
				if($announcement['params'][$param]['type'] == 'array' && !is_array($_REQUEST[$param])) $_REQUEST[$param] = explode(',', $_REQUEST[$param]);
				$result[$param] = $_REQUEST[$param];
			}
			return $result;
		}
		
		/**
		* Extracts paramters from an URL : returns an associative array with the params and their values
		*
		* @static
		* @param	string	$url
		* @return	array
		*/
		public static function extract_params($url) {
			preg_match_all('/([^?&=#]+)=([^&#]*)/', $url, $matches);
			return array_combine(
						array_map(function($arg){return htmlspecialchars(urldecode($arg));}, $matches[1]),
						array_map(function($arg){return htmlspecialchars(urldecode($arg));}, $matches[2])
					);
		}

		/**
		* Loads a class file from its class name (compatible with Zend classes)
		*
		* @static
		* @example	load_class('db/DBConnection');
		* @param	string	$class_path
		* @param	string	$class_name	in case the actual name of the class differs from the class file name (which may be the case when using namespaces)
		* @return	bool
		*/
		public static function load_class($class_path, $class_name='') {
			$result = false;
			if(strpos($class_path, 'Zend_') === 0) {
				// Zend framework 1
				 require_once 'Zend/Loader.php';
				 $result = \Zend_Loader::loadClass($class_path);
				// Zend framework 2
				/*
				require_once 'Zend/Loader/StandardAutoloader.php';
				$loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
				$result = $loader->autoload($class_path);
				*/
			}
			else {
				if($class_name == '') $class_name = self::get_class_name($class_path);
				if(class_exists($class_name, false) || isset($GLOBALS['FCLib_loading_classes'][$class_name])) $result = true;
				else {
					$GLOBALS['FCLib_loading_classes'][$class_name] = true;
					$file_path = self::get_class_path($class_path);
					if(self::path_contains($file_path)) $result = include $file_path;
					unset($GLOBALS['FCLib_loading_classes']);
				}
			}
			return $result;
		}

		/**
		* Loads a class file from its class name (compatible with Zend classes)
		* Note : We don't use include_once because doing so would result in a systematic call to path_contains() function.
		*
		* @static
		* @param string $file_name
		*/
		public static function include_file($file_name) {
			if(!isset($GLOBALS['FCLib_included_files'])) $GLOBALS['FCLib_included_files'] = array();
			if(!isset($GLOBALS['FCLib_included_files'][$file_name])) {
				$file_path = 'files/'.$file_name;
				if(self::path_contains($file_path)) $GLOBALS['FCLib_included_files'][$file_name] = include $file_path;
				else $GLOBALS['FCLib_included_files'][$file_name] = false;
			}
			return $GLOBALS['FCLib_included_files'][$file_name];
		}
	}

	/**
	* We add some standalone functions to relieve the user from the scope resolution notation.
	*/
	function get_url($server_port=true, $query_string=true) {
		return FClib::get_url($server_port, $query_string);
	}
	
	function get_script_path($script=__FILE__) {
		return FClib::get_script_path($script);	
	}
	
	//Initialize the FClib class for further 'load_class' and 'include_file' calls
	FClib::init();	
}
namespace {
	/**
	* Stand-alone functions defintions 
	* Note: We export those methods to the global scope in order to relieve the user from the scope resolution and namespace notation.
	*/
	function load_class($class_path, $class_name='') {
		return config\FClib::load_class($class_path, $class_name);
	}

	function include_file($file_name) {
		return config\FClib::include_file($file_name);
	}

	function announce($announcement) {
		return config\FClib::announce($announcement);
	}

	function extract_params($url) {
		return config\FClib::extract_params($url);
	}

	// deprecated : use announce method instead
	function check_params($params) {
		config\FClib::check_params($params);
	}

	// deprecated : use announce method instead
	function get_params($params) {
		return config\FClib::get_params($params);
	}
}