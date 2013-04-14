<?php

namespace fivecents {

	class Post extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string', 'onchange' => 'alternet\Association::onchange_parent_id'),
				'content'			=> array('type' => 'text'),
				'author' 			=> array('type' => 'function', 'result_type' => 'string', 'store' => true, 'function' => 'fivecents\Post::callable_getAuthor'),
				'url_resolver_id' 	=> array('type' => 'many2one', 'foreign_object' => 'core\UrlResolver'),
				'comments_ids'		=> array('type' => 'one2many', 'foreign_object' => 'fivecents\Comment', 'foreign_field' => 'post_id'),
				'labels_ids'		=> array('type' => 'many2many', 'foreign_object' => 'fivecents\Label', 'foreign_field' => 'posts_ids', 'rel_table' => 'fivecents_rel_post_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'post_id'),
				'related_posts_ids'	=> array('type' => 'many2many', 'foreign_object' => 'fivecents\Post', 'foreign_field' => 'related_posts_ids', 'rel_table' => 'fivecents_rel_post_post', 'rel_foreign_key' => 'related_id', 'rel_local_key' => 'post_id'),
			);
		}

		public static function callable_getAuthor($om, $uid, $oid, $lang) {
			$author = '';
			$res = $om->browse($uid, 'fivecents\Post', array($oid), array('creator'), $lang);
			if(is_array($res)) {
				$user_id = $res[$oid]['creator'];
				$res = $om->browse($uid, 'core\User', array($res[$oid]['creator']), array('firstname', 'lastname'), $lang);
			}
			if(is_array($res)) $author = $res[$user_id]['firstname'].' '.$res[$user_id]['lastname'];
			return $author;
		}


	}
}