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
* file: actions/core/user/logout.php
*
* Logs a user out.
*
*/

// easyObject index.php is in charge of setting the context
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

$response = array('result'=>false);

if(isset($_SESSION['user_id'])) {
	unset($_SESSION['user_id']);
	$_SESSION['login_key'] = rand(1000, 9999);
	$response['result'] = true;
}

echo json_encode($response);