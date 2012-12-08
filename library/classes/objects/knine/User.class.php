<?php
namespace knine {
	// example of class inheritance
	class User extends \core\User {

		public function getTable() { return 'core_user'; }

		public static function getColumns() {
			// inheritance syntax
			return array_merge(parent::getColumns(), array(
				'articles_ids'		=> array('type' => 'many2many', 'foreign_object' => 'knine\Article', 'foreign_field' => 'authors_ids', 'rel_table' => 'knine_rel_article_user', 'rel_foreign_key' => 'article_id', 'rel_local_key' => 'user_id'),
			));
		}

	}
}