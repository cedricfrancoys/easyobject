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
* Use the config namespace and its related functions.
* Constants defined in this file can be modified/re-defined in customs config.inc.php (i.e.: packages/[package_name]/config.inc.php)
* Note: instead, we could use the global namespace and explicitely call the config\define function
* Note: parameters defined with config\define are automatically exported as global constants in main entry point (index.php)
*/
namespace config;

/**
* File transfer parameters
*/
// maximum authorized size for file upload (in octet)
// keep in mind that this parameter does not override the PHP 'upload_max_filesize' directive
// so it can be more restrictive but cannot be higher!
// 	note: 'upload_max_filesize' is a PHP_INI_PERDIR directive and therefore must be defined in php.ini

define('UPLOAD_MAX_FILE_SIZE', 30000000);		// set upload limit to 30Mo


/**
* Locale parameters
*/
date_default_timezone_set('Europe/Brussels');



/**
* Email sending
*/
define('SMTP_HOST',				'smtp.gmail.com');
define('SMTP_ACCOUNT_USERNAME',	'example');
define('SMTP_ACCOUNT_PASSWORD',	'password');
define('SMTP_ACCOUNT_EMAIL',	'example@gmail.com');


/**
* Default App
*/
// Application that will be invoked if URL does not specify any app after the package name
define('DEFAULT_APP', 'site');