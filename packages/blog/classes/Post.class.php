<?php

namespace blog {

	class Post extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string'),
				'content'			=> array('type' => 'text'),
				'author' 			=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'blog\Post::callable_getAuthor'),
			);
		}

		/**
		* Returns the author's name of specified post
		* checks in the DB for the first available id (i.e. one that has not been modified since creation and whose validity has expired)
		* Some of the exiting records might not being use : this methods helps to recycle the ids that can be reused
		*
		* @param string $object_class
		* @return string
		*/
		public static function callable_getAuthor($om, $uid, $oid, $lang) {
			$author = '';
			// we need the creator id of the specified post
			$res = $om->browse($uid, 'blog\Post', array($oid), array('creator'), $lang);
			if(is_array($res)) {
				// the user_id we're insterested in is the creator field
				$user_id = $res[$oid]['creator'];
				// we request firstname and lastname for this user_id
				$res = $om->browse($uid, 'core\User', array($res[$oid]['creator']), array('firstname', 'lastname'), $lang);
				if(is_array($res)) $author = $res[$user_id]['firstname'].' '.$res[$user_id]['lastname'];
			}
			return $author;
		}

		// alternate experimental method
		// returns an array having keys matching objects ids and values the related author's name
		public static function callable_getAuthor2($om, $uid, $oid, $lang) {
			if(!is_array($oid) && is_integer($oid)) $oid = array($oid);
			// init the resulting array
			$result = array_fill_keys($oid, '');
			// request at once all creators ids for specified posts
			$res = $om->browse($uid, 'blog\Post', $oid, array('creator'), $lang);
			if(is_array($res)) {
				$users_ids = array();
				// build the users_ids array
				foreach($res as $oid => $values) $users_ids[] = $values['creator'];
				// we request firstname and lastname for these users_ids
				$res2 = $om->browse($uid, 'core\User', array_unique($users_ids), array('firstname', 'lastname'), $lang);
				if(is_array($res2)) {
				    foreach($res as $oid => $values) $result[$oid] = $res2[$values['creator']]['firstname'].' '.$res2[$values['creator']]['lastname'];
				}
			}
			return $result;
		}


	}
}