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
* Dispatcher's role is to set up the context and handle the client calls
* note : we use the functions defined in easyobject.api.php to call object manager methods so user doesn't have to deal with the mode (either "standalone" or "client-server" )
*
*/


/**
* Include dependencies
*/
// FC library allows to include required files and classes
defined('__FC_LIB') or include_once('fc.lib.php');
// load configuration data
include_once('config.inc.php');
// files are stored in the library/files folder
defined('__EASYOBJECT_LIB') or include_file('easyobject.api.php');


/**
* Define context
*/
// prevent vars initialization from generating output
set_silent(true);

// set current entry-point script as client
define('OPERATION_SIDE', 'client');

// get the base directory of the current script (easyObject installation directory being considered as root for URL redirection)
define('BASE_DIR', substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')+1));

// try to start or resume the session
if(!strlen(session_id())) session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");

// store current session_id into a constant (required as default parameter of several functions defined in easyobject library)
defined('SESSION_ID') or define('SESSION_ID', session_id());

// set the languages in which UI and content must be displayed

// UI items : UI language is the one defined in the user's settings (core/User object)
isset($_SESSION['LANG_UI']) or $_SESSION['LANG_UI'] = user_lang();

// Content items :
//		- for unidentified users, language is DEFAULT_LANG
//		- for identified users language is the one defined in the user's settings
//		- if a parameter lang is defined in the HTTP request, it overrides user's language
isset($_SESSION['LANG']) or $_SESSION['LANG'] = $_SESSION['LANG_UI'];
$params = get_params(array('lang'=>$_SESSION['LANG']));
$_SESSION['LANG'] = $params['lang'];

// from now on, we let the script decide whether or not to output error messages if any
set_silent(false);

// we need to prevent double escaping (especially for class names)
if (get_magic_quotes_gpc()) {
		function stripslashes_deep($value) {
			return is_array($value) ?  array_map('stripslashes_deep', $value) : stripslashes($value);
		}
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}
// add keys from $_FILES to the superglobal $_REQUEST array (in order to let the manager know when binary fields are present)
$_REQUEST = array_merge($_REQUEST, array_fill_keys(array_keys($_FILES), ''));


/**
* Dispatching : include the requested script
*/
$accepted_requests = array(
						'do'	=> array('type' => 'action',		'dir' => 'actions'), 	// do something server-side
						'get'	=> array('type' => 'data provider',	'dir' => 'data'),		// return some data (json)
						'show'	=> array('type' => 'application',	'dir' => 'apps')		// output rendering information (html/js)
					);

// load default config values
include_once('packages/core/config.inc.php');

// check current request for package specification
foreach($accepted_requests as $request_key => $request_conf) {
	if(isset($_REQUEST[$request_key])) {
		$parts = explode('_', $_REQUEST[$request_key]);
		$package = array_shift($parts);
		config\define('DEFAULT_PACKAGE', $package);	
		break;
	}
}
// if no package is pecified in the URL, check for DEFAULT_PACKAGE constant (defined in root config.inc.php)
if(!config\defined('DEFAULT_PACKAGE') && defined('DEFAULT_PACKAGE')) config\define('DEFAULT_PACKAGE', DEFAULT_PACKAGE);

if(config\defined('DEFAULT_PACKAGE')) {
	// if package has a custom configuration file, load it
	if(is_file('packages/'.config('DEFAULT_PACKAGE').'/config.inc.php')) include('packages/'.config('DEFAULT_PACKAGE').'/config.inc.php');
}

// if no request is specified, set DEFAULT_PACKAGE/DEFAULT_APP as requested script
if(count(array_intersect_key($accepted_requests, $_REQUEST)) == 0) {
	if(config\defined('DEFAULT_PACKAGE') && config\defined('DEFAULT_APP')) $_REQUEST['show'] = config('DEFAULT_PACKAGE').'_'.config('DEFAULT_APP');
}

// try to include requested script
foreach($accepted_requests as $request_key => $request_conf) {
	if(isset($_REQUEST[$request_key])) {
		$parts = explode('_', $_REQUEST[$request_key]);
		$package = array_shift($parts);
		// if no app is specified, use the default app (if any)
		if(empty($parts) && config\defined('DEFAULT_APP')) $parts[] = config('DEFAULT_APP');
		$filename = 'packages/'.$package.'/'.$request_conf['dir'].'/'.implode('/', $parts).'.php';
		is_file($filename) or die ("'{$_REQUEST[$request_key]}' is not a valid {$request_conf['type']}.");
		// export as constants all parameters declared with config\define() (i.e.: to make them accessible through global scope)
		config\export_config();
		include($filename);
		break;
	}
}