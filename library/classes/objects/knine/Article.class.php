<?php
/**
 * KNINE php library
 *
 * Article class
 *
 */

namespace knine {

	class Article extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string', 'multilang' => true),
				'summary'			=> array('type' => 'text', 'multilang' => true),
				'content'			=> array('type' => 'text', 'multilang' => true),

				'type'				=> array('type'			=> 'selection',
											 'selection'	=> array( 'Original' => 'original', 'Summary' => 'summary', 'Review' => 'review', 'Analysis' => 'analysis', 'Comments' => 'comments'),
                                        	 'help'			=> "The type allows to specify if the article is a work by its own (original) \nor if it is related to another work (summary, review, analysis, comments)."
										),
				'publications_ids'	=> array('type' => 'many2many', 'foreign_object' => 'knine\Publication', 'foreign_field' => 'articles_ids', 'rel_table' => 'knine_rel_article_publication', 'rel_foreign_key' => 'publication_id', 'rel_local_key' => 'article_id'),

				'parent_id' 		=> array('type' => 'many2one', 'onchange' => 'alternet\Association::onchange_parent_id', 'foreign_object' => 'knine\Article'),
				'children_ids'		=> array('type' => 'one2many', 'foreign_object' => 'knine\Article', 'foreign_field' => 'parent_id'),
				'sequence' 			=> array('type' => 'integer'),

				'references_ids'	=> array('type' => 'one2many', 'foreign_object' => 'knine\Reference', 'foreign_field' => 'article_id'),
				'illustrations_ids'	=> array('type' => 'one2many', 'foreign_object' => 'knine\Illustration', 'foreign_field' => 'article_id'),

				'authors_ids'		=> array('type' => 'many2many', 'foreign_object' => 'knine\User', 'foreign_field' => 'articles_ids', 'rel_table' => 'knine_rel_article_user', 'rel_foreign_key' => 'user_id', 'rel_local_key' => 'article_id'),
				'labels_ids'		=> array('type' => 'many2many', 'foreign_object' => 'knine\Label', 'foreign_field' => 'articles_ids', 'rel_table' => 'knine_rel_article_label', 'rel_foreign_key' => 'label_id', 'rel_local_key' => 'article_id'),

				'root_id' 			=> array('type' => 'function', 'result_type' => 'integer', 'function' => 'knine\Article::callable_getRootId'),
				'is_root' 			=> array('type' => 'function', 'result_type' => 'boolean', 'store' => true, 'function' => 'knine\Article::callable_isRoot'),
			);
		}

		public static function getDefaults() {
			return array(
					'title'		=> function() { return 'new article'; },
					'is_root'	=> function() { return true; }
			);
		}

		public static function callable_getRootId($om, $uid, $oid, $lang) {
			while(true) {
				$root_id = $oid;
				$res = $om->browse($uid, 'knine\Article', array($oid), array('parent_id'), $lang);
				if(empty($res[$oid]['parent_id'])) break;
				$oid = $res[$oid]['parent_id'];
			}
			return $root_id;
		}

 		public static function callable_isRoot($om, $uid, $oid, $lang) {
			$res = $om->browse($uid, 'knine\Article', array($oid), array('parent_id'), $lang);
			return empty($res[$oid]['parent_id']);
		}

		public static function onchange_parent_id($om, $uid, $oid, $lang) {
			$om->update($uid, 'alternet\Association', array($oid), array('is_root' => Association::callable_isRoot($om, $uid, $oid, $lang)), $lang);
		}

	}
}