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


define('__FC_LIB', true) or die('either __FC_LIB is already defined or it cannot be defined');


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
		return 'classes/'.$sub_path.FClib::get_class_name($class_path).'.class.php';
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
		$library_folder = FClib::get_script_path().'/library';
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
			if($_REQUEST[$param] == 'null' ) $_REQUEST[$param] = null;
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
			 $result = Zend_Loader::loadClass($class_path);
			// Zend framework 2
			/*
			require_once 'Zend/Loader/StandardAutoloader.php';
			$loader = new Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
			$result = $loader->autoload($class_path);
			*/
		}
		else {
			if($class_name == '') $class_name = FClib::get_class_name($class_path);
			if(class_exists($class_name)) $result = true;
			else {
				$file_path = FClib::get_class_path($class_path);
				if(FClib::path_contains($file_path)) $result = include $file_path;
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
			if(FClib::path_contains($file_path)) $GLOBALS['FCLib_included_files'][$file_name] = include $file_path;
			else $GLOBALS['FCLib_included_files'][$file_name] = false;
		}
		return $GLOBALS['FCLib_included_files'][$file_name];
	}
}


/**
* Stand-alone functions defintions (we export those methods to the global scope in order to relieve the user from the scope resolution operator notation)
*
*/

function load_class($class_path, $class_name='') {
	return FClib::load_class($class_path, $class_name);
}

function include_file($file_name) {
	return FClib::include_file($file_name);
}

// deprecated : use announce method instead
function check_params($params) {
	FClib::check_params($params);
}

// deprecated : use announce method instead
function get_params($params) {
	return FClib::get_params($params);
}

function announce($announcement) {
	return FClib::announce($announcement);
}

function extract_params($url) {
	return FClib::extract_params($url);
}

//Initialize the FClib class for further 'load_class' and 'include_file' calls
FClib::init();