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
* Update specified object(s) or creates new object(s) (when given id is 0).
°
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode (debug output would corrupt json data)
set_silent(true);

// announce script and fetch parameters values
$params = announce(	
	array(	
		'description'	=>	"Update specified object(s) or creates new object(s) (when given id is 0).",
		'params' 		=>	array(
								'object_class'	=> array(
													'description' => 'Class to look into.',
													'type' => 'string', 
													'required'=> true
													),
								'ids'			=> array(
													'description' => 'List of ids of the objects to browse.',
													'type' => 'array', 
													'required'=> true
													),
								'public_code'	=> array(
													'description' => 'Code for updating public objects (should be equal to session_id).',
													'type' => 'string',
													'default' => null
													),
								'lang'			=> array(
													'description '=> 'Specific language for multilang field.',
													'type' => 'string', 
													'default' => DEFAULT_LANG
													)
							)
	)
);


// additional check for public objects (i.e. that can be creatd or modified by guest users)
if(in_array($params['object_class'], unserialize(PUBLIC_OBJECTS))) {
	// this is a public object, so we check if public_code is given and if it matches the current session code
	if(is_null($params['public_code']) || $params['public_code'] != SESSION_ID) die();
}

// first we try to validate the submitted content
$error_message_ids = array();
$validation = validate($params['object_class'], $_REQUEST);

// if something went wrong during the validation, abort the process
if($validation === false) $result = UNKNOWN_ERROR;
else {
	if(count($validation)) {
		// one or more fields have invalid value
		$error_message_ids = array_values($validation);
		$result = INVALID_PARAM;
	}
	else {
// note : keep in mind that if we are requesting a new object
// and if that object has one (or more) field whose name is in the $_REQUEST array
// then operation will result in the creation AND modification of the object (which then would no longer be a draft)
		// values are valid : update object and get json result
		$result = update($params['object_class'], $params['ids'], $_REQUEST, $params['lang']);
		// look for deprecated draft
		$ids = search('core\Version', array(array(array('object_class', '=', $params['object_class']), array('object_id', '=', $params['ids']), array('state', '=', 'draft'))));
		// if update went well, remove (permanently) pending draft, if any
		if(is_array($result) && !empty($ids)) remove('core\Version', $ids, true);
	}
}

// note: in case of success, $result will contain an array (with ids of newly created objects, if any)
// otherwise it will hold an error code (integer)

// send json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode(array('result' => $result, 'url' => '', 'error_message_ids' => $error_message_ids));