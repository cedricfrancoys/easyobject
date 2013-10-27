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
* file: packages/core/actions/objects/update.php
*
* Update an object.
*
* @param string $class_anme
* @param array $ids
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('object_class', 'ids'));

// assign values with the received parameters
$params = get_params(array('object_class'=>null, 'ids'=>null, 'lang'=>DEFAULT_LANG, 'public_code'=>null));

// additional check for public objects (i.e. that can be creatd or modified by guest users)
if(in_array($params['object_class'], unserialize(PUBLIC_OBJECTS))) {
	// this is a public object, so we check if public_code is given and if it matches the current session code
	if(is_null($params['public_code']) || $params['public_code'] != SESSION_ID) die();
}

// first we check the validation of the posted content
$error_message_ids = array();
$validation = validate($params['object_class'], $_REQUEST);

// if something went wrong during the validation, abort the log in process
if($validation === false) $result = UNKNOWN_ERROR;
else {
	if(count($validation)) {
		// one or more fields have invalid value
		$error_message_ids = array_values($validation);
		$result = INVALID_PARAM;
	}
	else {
// note : keep in mind that if we are requesting a new object
// and if that object has one field whose name is in the $_REQUEST
// then we don't prevent from setting values (which would result in the creation AND modification, so object would no longer be a draft)
		// values are valid : update object and get json result
		$result = update($params['object_class'], $params['ids'], $_REQUEST, $params['lang']);
		// look for deprecated draft
		$ids = search('core\Version', array(array(array('object_class', '=', $params['object_class']), array('object_id', '=', $params['ids']), array('state', '=', 'draft'))));
		// if update went well, remove (permanently) pending draft, if any
		if(is_array($result) && !empty($ids)) remove('core\Version', $ids, true);
	}
}

// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result' => $result, 'url' => '', 'error_message_ids' => $error_message_ids));