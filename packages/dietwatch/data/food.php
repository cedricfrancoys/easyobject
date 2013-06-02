<?php

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

check_params(array('content'));
$params = get_params(array('content'=>null,'lang'=>'en'));

$result = array();

if(isset($params['content'])) {
	$ids = search("dietwatch\\food", array(array(array('Long_Desc', 'ilike', "%{$params['content']}%"))), 'Long_Desc', 'asc', 0, '', $params['lang']);
	$list = &browse("dietwatch\\food", $ids, array('Long_Desc'), $params['lang']);
	foreach($list as $id => $object_fields) { 
		$result[] = array('label' => preg_replace('/'.$params["content"].'/iu', '<b>'.$params["content"].'</b>', $object_fields['Long_Desc']), 'value' => $id);
	}	
}

header('Content-type: text/html; charset=UTF-8');
echo json_encode($result);