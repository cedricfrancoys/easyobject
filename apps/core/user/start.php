<?php


load_class('utils/HtmlWrapper');

include_file('lang/'.user_lang().'.lang.php');

$html = new HtmlWrapper();

$user_id = user_id();
if($user_id > GUEST_USER_ID) {
	$res = browse('core\user', array($user_id), array('firstname', 'lastname'));
	$html->setBody("Welcome {$res[$user_id]['firstname']} {$res[$user_id]['lastname']}");
}
else $html->setBody("Welcome guest user");

print($html);