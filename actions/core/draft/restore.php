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
*
*	You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
* Restore an object from the values its draft.
*
* @param string $object_class
* @param integer $object_id
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('object_class', 'id'));

// assign values with the received parameters
$params = get_params(array('object_class'=>null, 'id'=>null));

// look for a draft of the specified object
$ids = search('core\version', array(array(array('object_class', '=', $params['object_class']), array('object_id', '=', $params['id']), array('state', '=', 'draft'))), 'id', 'asc', 0, 1);

if(count($ids)) {
	$values = &browse('core\version', $ids, array('serialized_value'));
	// set the object with the values of its draft
	update($params['object_class'], array($params['id']), unserialize(base64_decode($values[$ids[0]]['serialized_value'])));
	// we no longer need the draft, so remove it (permanently)
	remove('core\version', $ids, true);
}