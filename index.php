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
* note : we use the functions defined in easyobject.lib.php to call easyObject's manager methods so we don't have to deal with the mode ("standalone" or "client-server" )
*
* @version $Id: index.php 2012-06-01 $
* @package	easyObject
*/

/**
* Include dependencies
*/

defined('__FC_LIB') or include_once('fc.lib.php');
defined('__EASYOBJECT_LIB') or include_file('easyobject.api.php');


/**
* Define the context
*/

// set current entry-point script as client
define('OPERATION_SIDE', 'client');
// try to start or resume the session
session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");
// store current session_id into a constant (required as default parameter of several functions defined in easyobject library)
define('SESSION_ID', session_id());
// store the languages in which UI and content must be displayed
// content items :
//	- for unidentified users, language is DEFAULT_LANG
//	- for identified users language is the one defined in the user's settings
//	- if a parameter lang is defined in the HTTP request, it overrides user's language
// UI language is always the one defined in the user's settings


set_silent(true); // prevent vars initialization from generating output

$params = get_params(array('lang'=>DEFAULT_LANG));
define('SESSION_LANG_UI', user_lang());
define('SESSION_LANG', $params['lang']);

set_silent(false);


/**
* Dispatching : redirect to the requested script
*/

// just do something
if(isset($_REQUEST['do'])) {
	$filename = 'actions/'.str_replace('_', '/', $_REQUEST['do']).'.php';
	is_file($filename) or die ("'{$_REQUEST['do']}' is not a valid action.");
	include($filename);
}
// return some data
elseif(isset($_REQUEST['get'])) {
	$filename = 'data/'.str_replace('_', '/', $_REQUEST['get']).'.php';
	is_file($filename) or die ("'{$_REQUEST['get']}' is not a valid data provider.");
	include($filename);
}
// display html stuff
elseif(isset($_REQUEST['show'])) {
	$filename = 'apps/'.str_replace('_', '/', $_REQUEST['show']).'.php';
	is_file($filename) or die ("'{$_REQUEST['show']}' is not a valid application.");
	include($filename);
}