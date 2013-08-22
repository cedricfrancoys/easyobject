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
* Database parameters
*/
	// this part is the most likely to be modified
	define('DB_DBMS',		'MYSQL');		// only MySQL is supported so far
	define('DB_HOST',		'localhost');   // the full qualified domain name (ex.: www.example.com)
	define('DB_PORT',		'3306');		// this is the default port for MySQL
	define('DB_USER',		'root');        // this should be changed for security reasons
	define('DB_PASSWORD',	'');			// this should be changed for security reasons
	define('DB_NAME', 		'easyobject');	// specify the name of the DB that you have created or you plan to use
	define('DB_CHARSET',	'UTF8');		// unless you are really sure of what you're doing, leave this constant to 'UTF8'

/**
* Language parameters
*/

	// UI lang is set in SESSION_LANG_UI (in the dispatcher), via method user_lang(), that uses DEFAULT_LANG for GUEST_USER_ID and 'lang' field of core\User object for other users
	// thus, the UI language can only be changed by modifying user's preferences
	// the language in which the content of the objects is to be displayed is set in SESSION_LANG (in the dispatcher), if no value is given in the URL (as 'lang' parameter) DEFAULT_LANG is given as default value
    define('DEFAULT_LANG', 'en');


/**
* Locale parameters
*/
	date_default_timezone_set('Europe/Brussels');


/**
* Session parameters
*/
	// use session identification by COOKIE only
	ini_set('session.use_trans_sid', '0');
	ini_set('url_rewriter.tags', '');

/**
* File transfer parameters
*/
	// maximum authorized size for file upload (in octet)
	// 256ko by default
	// define('UPLOAD_MAX_FILE_SIZE', 256000);
	define('UPLOAD_MAX_FILE_SIZE', 1000000);

/**
* Debugging
*/

	ini_set('display_errors', true);
	error_reporting(E_ALL);

	define('DEBUG_PHP',	1);
	define('DEBUG_SQL',	2);
	define('DEBUG_ORM',	4);
	//define('DEBUG_',	8);

	// define('DEBUG_MODE', 0);
	define('DEBUG_MODE', DEBUG_PHP | DEBUG_ORM | DEBUG_SQL);

    $SILENT_MODE = false;

	/**
	* Allows to force the script to be verbose or to mute it (no output).
	*
	* @param boolean $silent
	*/
	function set_silent($silent) {
		global $SILENT_MODE;
		$SILENT_MODE = $silent;
		if($SILENT_MODE) {
			ini_set('display_errors', false);
			error_reporting(0);
		}
		else {
			ini_set('display_errors', true);
			error_reporting(E_ALL);
		}
	}

	/**
	* Returns the resulting debug mode (taking $SILENT_MODE under account)
	*
	*/
	function debug_mode() {
		global $SILENT_MODE;
		if($SILENT_MODE) return 0;
		return DEBUG_MODE;
	}

/**
* Error codes
*/

    define('UNKNOWN_ERROR',		 0);	// something went wrong (that requires to check the logs)
    define('INVALID_PARAM',		 1);	// one or more parameters have invalid or incompatible value
    define('SQL_ERROR',			 2);	// error while building SQL query or processing it
    define('UNKNOWN_OBJECT',	 4);	// unknown class or object
    define('NOT_ALLOWED',		 8);	// action violates some rule or user don't have required permissions

/**
* Cache parameters
*/
    // ObjectManager and IdentificationManager use a cache mechanism to minimize database access
	define('STORE_INTERVAL', 10);		// time (in minutes) during which data are cached (objects changes & users permissions)



/**
* Users, groups and permissions
*/

	// permissions that can be granted to a user or a group
	define('R_CREATE',	1);
	define('R_READ',	2);
	define('R_WRITE',	4);
	define('R_DELETE',	8);
	define('R_MANAGE',	16);

	// built-in users
	// note : make sure that the ids in DB are matching these (and are not used for other users)
	define('SYSTEM_USER_ID', 0);	// this user is used when the modifier id must be kept to 0
	define('GUEST_USER_ID', 1);
	define('ROOT_USER_ID', 2);

	// built-in default group
	// note : make sure that an identical group is set in DB
	define('DEFAULT_GROUP_ID', 1);

	// built-in default ACL
	// if no ACL is defined (which is the case by default) for an object nor for its class, any user will be granted the permissions set below
	// by default, we allow anyone to see any content (you may change it if necessary)
	// note: in order to allow a user to fully create objects, he must be granted R_CREATE and R_WRITE permissions
	//define('DEFAULT_RIGHTS', R_READ);

	// tip : to set several rights at once, you may use the OR binary operator
	define('DEFAULT_RIGHTS', R_CREATE | R_READ | R_WRITE | R_DELETE | R_MANAGE);

	// level of authorization control
	// By default, the control is done at the class level. It means that a user will be granted the same rights for every objects of a given class.
	// However, sometimes we must take the object id under account (for instance, if pages of a web site can have their own permissions)
	define('CONTROL_LEVEL', 'class');	// allowed values are 'class' or ' object'


/**
* Logging
*/
	// note : keep in mind that enabling logging makes I/O operations a little bit longer
	//define('LOGGING_MODE', false);
	define('LOGGING_MODE', R_CREATE | R_WRITE | R_DELETE);


/**
* Operation mode
*/

	define('OPERATION_MODE', 'standalone');
	//define('OPERATION_MODE', 'client-server');
	// note : in addition to OPERATION_MODE, a constant OPERATION_SIDE must be defined in every main entry-point script

	// Tests have shown that client-server mode can be 20 to 30% faster.
	// However, it requires the possibility to use the php CLI on the server.
	// examples of commands to run the server :
	// windows : C:\wamp\bin\php\php5.3.0\php.exe "C:\wamp\www\easyobject\rpc_server.php"

    // RPC parameters for client-server mode
	define('RPC_HOST', 'localhost');
	define('RPC_PORT', 63252);
	define('RPC_PACKET_SIZE', 1500);


/**
* Draft & Versioning
*/

	// draft validity in days
	// define('DRAFT_VALIDITY', 10);
	define('DRAFT_VALIDITY', 0);

/**
* Date formatting
*/

	define('DATE_FORMAT', 'd/m/Y');


/**
* Email sending
*/
	define('SMTP_HOST', 'smtp.gmail.com');
	define('SMTP_ACCOUNT_USERNAME', 'username');
	define('SMTP_ACCOUNT_PASSWORD', 'mypassword');
	define('SMTP_ACCOUNT_EMAIL', 'username@example.com');

	
/**
* Default App
*/
	
	// Application to redirect to if nothing is specified in the url (typically while accessing root folder)
	define('DEFAULT_APP', 'linnetts_home');