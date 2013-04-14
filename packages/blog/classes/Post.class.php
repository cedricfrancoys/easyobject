<?php

namespace blog {

	class Post extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string', 'onchange' => 'alternet\Association::onchange_parent_id'),
				'content'			=> array('type' => 'text'),
				'author' 			=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'blog\Post::callable_getAuthor'),
			);
		}

		public static function callable_getAuthor($om, $uid, $oid, $lang) {
			$author = '';
			$res = $om->browse($uid, 'blog\Post', array($oid), array('creator'), $lang);
			if(is_array($res)) {
				$user_id = $res[$oid]['creator'];
				$res = $om->browse($uid, 'core\User', array($res[$oid]['creator']), array('firstname', 'lastname'), $lang);
			}
			if(is_array($res)) $author = $res[$user_id]['firstname'].' '.$res[$user_id]['lastname'];
			return $author;
		}

	}
}