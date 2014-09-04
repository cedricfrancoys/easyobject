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

/*
* file: actions/core/user/login.php
*
* Logs a user in.
*
* @param string $login
* @param string $password (locked MD5 value)
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('login', 'password'));
// assign values with the received parameters
$params = get_params(array('login' =>null, 'password'=>null));

//check constraints
$error_message_ids = array();
$validation = validate('core\User', $params);

// if something went wrong during the validation, abort the log in process
if($validation === false) $result = UNKNOWN_ERROR;
else {
	if(count($validation)) {
		// one or more fields have invalid value
		$error_message_ids = array_values($validation);
		$result = INVALID_PARAM;
	}
	else {
		// values are valid : try to log in
		$result = (bool) login($params['login'], $params['password']);
	}
}

// send json result
echo json_encode(array('result' => $result, 'url' => 'index.php?show=core_user_start', 'error_message_ids' => $error_message_ids));