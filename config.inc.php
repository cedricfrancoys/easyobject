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

*	You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/** 
* Add stuff in the global namespace.
* Constants defined in this file cannot be modified in customs config.inc.php
*/

/**
* Current version of easyObject
*/
define('VERSION', '0.9.3');


/**
* Database parameters
*/
define('DB_DBMS',		'MYSQL');		// only MySQL is supported so far
define('DB_HOST',		'localhost');   // the full qualified domain name (ex.: www.example.com)
define('DB_PORT',		'3306');		// this is the default port for MySQL
define('DB_USER',		'root');        // this should be changed for security reasons
define('DB_PASSWORD',	'');			// this should be changed for security reasons
define('DB_NAME', 		'easyobject');	// specify the name of the DB that you have created or you plan to use
define('DB_CHARSET',	'UTF8');		// unless you are really sure of what you're doing, leave this constant to 'UTF8'


/**
* Operation mode
*
* Possible values are: 'standalone' and 'client-server'
* Note : an additional OPERATION_SIDE constant must be defined in every entry-point script (index.php, url_resolve.php, rpc_server.php, ...)
*/
// Tests have shown that client-server mode can be 20 to 30% faster.
// However, it requires the possibility to use PHP CLI on the server.
// Examples of commands to run the server :
// windows : C:\wamp\bin\php\php5.3.0\php.exe "C:\wamp\www\easyobject\rpc_server.php"
define('OPERATION_MODE', 'standalone');

// RPC parameters for client-server mode
define('RPC_HOST', 'localhost');
define('RPC_PORT', 63252);
define('RPC_PACKET_SIZE', 1500);


/**
* Cache parameters
*/
// This applies only when OPERATION_MODE is set to client-server
// ObjectManager and IdentificationManager use a cache mechanism to minimize database access
define('STORE_INTERVAL', 10);		// time (in minutes) during which data is cached (objects changes & users permissions)


/**
* Session parameters
*/
// Use session identification by COOKIE only
ini_set('session.use_trans_sid', '0');
ini_set('url_rewriter.tags', '');


/**
* Binaries storage directory
*/
// Note: ensure http service has read/write permissions on this directory
define('BINARY_STORAGE_DIR', './bin');


/**
* Binary type storage mode
*
* Possible values are: 'DB' (database) and 'FS' (filesystem)
*/
define('BINARY_STORAGE_MODE', 'FS');


/**
* Default ACL
*
* If no ACL is defined (which is the case by default) for an object nor for its class, any user will be granted the permissions set below
*/
// Note: in order to allow a user to fully create objects, he must be granted R_CREATE and R_WRITE permissions
// Note: to set several rights at once, use the OR binary operator	
define('DEFAULT_RIGHTS', R_CREATE | R_READ | R_WRITE | R_DELETE | R_MANAGE);

/**
* Access control level
*/
// By default, the control is done at the class level. It means that a user will be granted the same rights for every objects of a given class.
// However, sometimes we must take the object id under account (for instance, if pages of a web site can have their own permissions)
define('CONTROL_LEVEL', 'class');	// allowed values are 'class' or ' object'


/**
* Debugging
*/	
define('DEBUG_MODE', DEBUG_PHP | DEBUG_ORM | DEBUG_SQL);


/**
* List of public objects 
*/
// array of classes involved in right management and SPAM protection mechanism
define ("PUBLIC_OBJECTS", serialize (array ('icway\Comment')));


/**
* Default App
*/
// Application that will be invoked if nothing is specified in the url (typically while accessing root folder)
define('DEFAULT_APP', 'core_manage');


/**
* Language parameters
*/
// UI lang is set in SESSION_LANG_UI (in the dispatcher), via method user_lang(), that uses GUEST_USER_LANG for GUEST_USER_ID and 'language' field of core\User object for other users
// thus, the UI language can only be changed by modifying user's preferences
// the language in which the content of the objects is to be displayed is set in SESSION_LANG (in the dispatcher), if no value is given in the URL (as 'lang' parameter) DEFAULT_LANG is given as default value
define('DEFAULT_LANG', 'en');
define('GUEST_USER_LANG', 'en');
