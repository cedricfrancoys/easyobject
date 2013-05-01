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
* file: data/core/objects/list.php
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

// todo : add lang parameter


// 1) get parameters values
$params = get_params(array('object_class'=>null, 'fields'=>null, 'domain'=>null, 'page'=>1, 'rp'=>10, 'sortname'=>'id', 'sortorder'=>'asc', 'records'=>null, 'mode'=>null));
$object_class = $params['object_class'];
$page 	= $params['page']; 		// the requested page
$limit	= $params['rp']; 		// how many rows we want to have into the grid
$order	= $params['sortname'];	// index column - i.e. user click to sort
$sort	= $params['sortorder'];	// the direction  - i.e. 'asc' or 'desc'
$domain = $params['domain'];

if($params['fields'] && !is_array($params['fields'])) $fields = explode(',', $params['fields']);
else $fields = $params['fields'];

if(is_null($domain)) $domain = array(array());

$start = ($page-1) * $limit;
if($start < 0) $start = 0;

// 2) check for special option 'mode' (that allows to limit result to deleted objects)
if($params['mode'] == 'recycle') {
	// add the (deleted = 1) clause to every condition
	for($i = 0, $j = count($domain); $i < $j; ++$i)
		$domain[$i] = array_merge($domain[$i], array(array('deleted', '=', '1')));
}

// 3) search and browse
$ids = search($object_class, $domain, $order, $sort);
$list = &browse($object_class, array_slice($ids, $start , $limit, true), $fields);
$count_ids = count($ids);



// 4) generate json result
$html = '{'."\n";
$html .= '	"page": "'.$page.'",'."\n";
$html .= '	"total": "'.ceil($count_ids/$limit).'",'."\n";
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