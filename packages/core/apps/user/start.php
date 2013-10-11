<?php

load_class('utils/HtmlWrapper');

// force silent mode 
set_silent(true);

$html = new HtmlWrapper();

$user_id = user_id();

// todo : go to home URL if defined


if($user_id > GUEST_USER_ID) {
	$res = browse('core\user', array($user_id), array('firstname', 'lastname'));
	$html->add("Welcome {$res[$user_id]['firstname']} {$res[$user_id]['lastname']}");
}
else $html->add("Welcome guest user");

$html->add(" ({$user_id})");

print($html);