<?php

// force silent mode
set_silent(true);

load_class('utils/HtmlWrapper');

$html = new HtmlWrapper();

$user_id = user_id();

$start_app = DEFAULT_APP;
$start_filename = '';
$firstname = 'guest';
$lastname = 'user';

if($user_id > GUEST_USER_ID) {
	$res = browse('core\User', array($user_id), array('firstname', 'lastname', 'start'));
	$firstname = $res[$user_id]['firstname'];
	$lastname = $res[$user_id]['lastname'];	
	if(strlen($res[$user_id]['start'])) $start_app = $res[$user_id]['start'];
}

if(strlen($start_app)) {
	$parts = explode('_', $start_app);
	$start_filename = 'packages/'.array_shift($parts).'/apps/'.implode('/', $parts).'.php';
}

if(strlen($start_filename) && is_file($start_filename)) {
		include($start_filename);	
}
else {
	$html->add("Welcome $firstname $lastname");
	print($html);
}