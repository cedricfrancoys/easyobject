<?php

// Dispatcher (index.php) is in charge of setting the context and should include easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// $values = update('core\User', array(0), array('firstname' => 'cedric'));
// $id = $values[0];

//$ids = update('core\User', array(0));
//$ids = update('core\User', array(0, 0, 0), array('firstname' => 'test'));

//$values = browse('core\User', $ids);
//print_r($values);

$a = 3;
$b = 1;
$c = 5;
echo ($a | $b | $c);