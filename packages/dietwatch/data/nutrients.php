<?php

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

check_params(array('food_id'));
$params = get_params(array('food_id'=>null));

$result = '';

if(isset($params['food_id'])) {
	$ids = search("dietwatch\\value", array(array(array('NDB_No', '=', $params['food_id']))));	
	$list = &browse("dietwatch\\value", $ids, array('Nutr_No', 'Nutr_Val', 'Units'));
	foreach($list as $id => $object_fields) $result .= '"'.$object_fields['Nutr_No'].'":["'.$object_fields['Nutr_Val'].'","'.$object_fields['Units'].'"],';
	$result = rtrim($result, ',');
}

header('Content-type: text/html; charset=UTF-8');
echo '{'.$result.'}';