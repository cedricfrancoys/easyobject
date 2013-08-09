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
* file: packages/core/data/objects/list.php
*
* Search and browse matching objects.
*
* input: 'object_class'
*
* example
* full URL : http://localhost/easyobject/index.php?get=core_objects_list&class=school\Student&fields=id,firstname,lastname,birthdate,subscription&rp=20&page=1&sortname=id&sortorder=asc&domain=[[]]
* output result :
* {
*	"page": "1",
*	"total": "1",
*	"records": "5",
*	"rows": [
*		{"id":"1","cell":["1","Bart","Simpson","1976-03-04","2012-06-01"]},
*		{"id":"2","cell":["2","Parker","Lewis","2000-01-01","2012-08-25"]},
*		{"id":"3","cell":["3","Kevin","McCallister","2000-01-01","2012-08-25"]},
*		{"id":"4","cell":["4","Joey","Jeremiah","2000-01-01","2012-08-25"]},
*		{"id":"5","cell":["5","Christine","Nelson","2000-01-01","2012-08-25"]}
*		]
* }
*
*/

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('object_class'));

// 1) get parameters values
$params = get_params(array(

							'object_class'		=> null, 
							'fields'			=> null, 
							'domain'			=> array(array()), 
							'page'				=> 1, 
							'rp'				=> 10,					// number of rows we want to have into the grid
							'sortname'			=> 'id',				// index column (i.e. user click to sort)
							'sortorder'			=> 'asc',				// the direction  (i.e. 'asc' or 'desc')
							'records'			=> null, 
							'mode'				=> null, 
							'lang'				=> DEFAULT_LANG
					));

if($params['fields'] && !is_array($params['fields'])) $params['fields'] = explode(',', $params['fields']);

$start = ($params['page']-1) * $params['rp'];
if($start < 0) $start = 0;

// 2) check for special option 'mode' (that allows to limit result to deleted objects)
if($params['mode'] == 'recycle') {
	// add the (deleted = 1) clause to every condition
	for($i = 0, $j = count($params['domain']); $i < $j; ++$i)
		$params['domain'][$i] = array_merge($params['domain'][$i], array(array('deleted', '=', '1')));
}

// 3) search and browse
if(empty($params['records'])) {
	// This way we search all possible results : that might result in a quite long process if the tables are big
	// but it is the only way to determine the number of results,
	// so we do it only when the number of results is unknown
	if(OPERATION_MODE == 'standalone') {
		// if we are in standalone mode, however, the DBMS might offer some options to do this quicker (for example with MySQL FOUND_ROWS())
		// first ensure objectManger is instanciated
		$om = &ObjectManager::getInstance();
		// get an instance of the DBMS manipulator
		$db = &DBConnection::getInstance();
		$ids = search($params['object_class'], $params['domain'], $params['sortname'], $params['sortorder'], $start, $params['rp'], $params['lang']);
		// use the getAffectedRows method to get the total number of reords
		$count_ids = $db->getAffectedRows();
		$list = &browse($params['object_class'], $ids, $params['fields'], $params['lang']);
	}
	else {
		$ids = search($params['object_class'], $params['domain'], $params['sortname'], $params['sortorder'], 0, '', $params['lang']);
		$list = &browse($params['object_class'], array_slice($ids, $start , $params['rp'], true), $params['fields'], $params['lang']);
		$count_ids = count($ids);
	}
}
else {
	// This is a faster way to do the search but it requires the number of total results
	$ids = search($params['object_class'], $params['domain'], $params['sortname'], $params['sortorder'], $start, $params['rp'], $params['lang']);
	$list = &browse($params['object_class'], $ids, $params['fields'], $params['lang']);
	$count_ids = $params['records'];
}

// 4) generate json result
$html = '{'."\n";
$html .= '	"page": "'.$params['page'].'",'."\n";
$html .= '	"total": "'.ceil($count_ids/$params['rp']).'",'."\n";
$html .= '	"records": "'.$count_ids.'",'."\n";
$html .= '	"rows": ['."\n";

foreach($list as $id => $object_fields) {
	$html .= '		{"id":"'.$id.'","cell":[';
	foreach($object_fields as $field_name => $field_value) {
		$html .= json_encode($object_fields[$field_name]).',';
	}
	$html = rtrim($html, ' ,').']},'."\n";
}
$html = rtrim($html, "\n ,");

$html .= "\n".'	]'."\n";
$html .= '}';

header('Content-type: text/html; charset=UTF-8');
print($html);