<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('email'));

// set 'fr' as default language
isset($_SESSION['icway_lang']) or $_SESSION['icway_lang'] = 'fr';

$params = get_params(array('email'=>null, 'lang'=>$_SESSION['icway_lang']));

load_class('Zend_Mail');
load_class('Zend_Mail_Transport_Smtp');

//SMTP server configuration
// note: requires openssl module installed on the server
$transport = new \Zend_Mail_Transport_Smtp(SMTP_HOST, array(
														'auth' => 'login',
														'ssl' => 'ssl',
														'port' => '465',
														'username' => SMTP_ACCOUNT_USERNAME,
														'password' => SMTP_ACCOUNT_PASSWORD
													)
);

//Create email
$mail = new \Zend_Mail();
$mail->setFrom(SMTP_ACCOUNT_EMAIL, 'icway');
$mail->addTo('isaced@gmail.com', 'isaced');
$mail->setSubject("ICway - Demande d'inscription a la newsletter");
$mail->setBodyText('e-mail: '.$params['email']."\n".'langue: '.$params['lang']);
// Send email
$mail->send($transport);