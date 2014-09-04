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
* file: packages/core/data/objects/browse.php
*
* Returns the fields values of the object's given ids.
*
* @param string $class
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('object_class', 'ids'));

// assign values with the received parameters
$params = get_params(array('object_class'=>null, 'ids'=>null, 'fields'=>null, ''=>false, 'lang'=>DEFAULT_LANG));

if($params['fields'] == 'null' ) $params['fields'] = null;

// json result
$result = &browse($params['object_class'], $params['ids'], $params['fields'], $lang = $params['lang']);

header('Content-type: text/html; charset=UTF-8');
echo json_encode($result, JSON_FORCE_OBJECT);