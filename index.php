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
* note : we use the functions defined in easyobject.api.php to call object manager methods so we don't have to deal with the mode (either "standalone" or "client-server" )
*
* @version $Id: index.php 2012-06-01 $
* @package	easyObject
*/

/**
* Include dependencies
*/

defined('__FC_LIB') or include_once('fc.lib.php');
// files are stored in the library/files folder
defined('__EASYOBJECT_LIB') or include_file('easyobject.api.php');


/**
* Define the context
*/
// prevent vars initialization from generating output
set_silent(true);

// set current entry-point script as client
define('OPERATION_SIDE', 'client');

// try to start or resume the session
if(!strlen(session_id())) session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");

// store current session_id into a constant (required as default parameter of several functions defined in easyobject library)
if(!defined('SESSION_ID')) define('SESSION_ID', session_id());

// set the languages in which UI and content must be displayed

// UI items : UI language is the one defined in the user's settings (core/User object)
define('SESSION_LANG_UI', user_lang());

// Content items :
//	- for unidentified users, language is DEFAULT_LANG
//	- for identified users language is the one defined in the user's settings
//	- if a parameter lang is defined in the HTTP request, it overrides user's language
$params = get_params(array('lang'=>DEFAULT_LANG));
define('SESSION_LANG', $params['lang']);

// from now on, we let the script decide whether or not to output error messages if any
set_silent(false);

// add keys from $_FILES to the superglobal $_REQUEST array (we do this in order to let the manager know when binary fields are present)
$_REQUEST = array_merge($_REQUEST, array_fill_keys(array_keys($_FILES), ''));

/**
* Dispatching : include the requested script
*/

$accepted_requests = array(
							'do'	=> array('type' => 'action', 'dir' => 'actions'),		// do something server-side
							'get'	=> array('type' => 'data provider', 'dir' => 'data'), 	// return some data (json)
							'show'	=> array('type' => 'application', 'dir' => 'apps')		// output rendering information (html/js)
					);

$request_found = false;
foreach($accepted_requests as $request_key => $request_conf) {
	if(isset($_REQUEST[$request_key])) {
		$parts = explode('_', $_REQUEST[$request_key]);
		$filename = 'packages/'.array_shift($parts).'/'.$request_conf['dir'].'/'.implode('/', $parts).'.php';
		is_file($filename) or die ("'{$_REQUEST[$request_key]}' is not a valid {$request_conf['type']}.");
		include($filename);
		$request_found = true;		
		break;
	}
}

if(!$request_found && defined('DEFAULT_APP')) {
	$parts = explode('_', DEFAULT_APP);
	$filename = 'packages/'.array_shift($parts).'/apps/'.implode('/', $parts).'.php';
	is_file($filename) or die ("'{$_REQUEST[$request_key]}' is not a valid {$request_conf['type']}.");
	include($filename);
}