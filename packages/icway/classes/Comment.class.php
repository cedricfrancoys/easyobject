<?php

namespace icway {

	class Comment extends \core\Object {

		public static function getColumns() {
			return array(
				'author'	=> array('type' => 'string'),
				'email'		=> array('type' => 'string'),
				'content'	=> array('type' => 'short_text', 'onchange' => 'icway\Comment::onchange_content'),
				'post_id'	=> array('type' => 'many2one', 'foreign_object' => 'icway\Post'),
			);
		}

		public static function onchange_content($om, $uid, $oid, $lang) {
			load_class('Zend_Mail');
			load_class('Zend_Mail_Transport_Smtp');
			
			$res = $om->browse($uid, 'icway\Comment', array($oid), array('author', 'post_id', 'content'), $lang);	
			
			//SMTP server configuration
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
			$mail->addTo(SMTP_ACCOUNT_EMAIL, SMTP_ACCOUNT_USERNAME);
			$mail->setSubject('Commentaire sur le post '.$res[$oid]['post_id']);
			$mail->setBodyText($res[$oid]['content']);

			$mail->send($transport);
		}

	}
}