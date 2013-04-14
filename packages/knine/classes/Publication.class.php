<?php
/**
 * KNINE php library
 *
 * Illustration class
 *
 */

namespace knine {

	class Publication extends \core\Object {

		public static function getColumns() {
			return array(
				'type'				=> array('type' => 'selection', 'selection' => array( 'Article' => 'article', 'Book' => 'book')),
				'title'				=> array('type' => 'string', 'help' => 'Title of the original work'),
				'publisher'			=> array('type' => 'string', 'help' => 'Publishing company for a book; name of the periodic for an article; name of the association or website for an electronic publication'),
				'place'				=> array('type' => 'string', 'help' => 'City of publication or complete URL for an electronic publication'),
				'year'				=> array('type' => 'integer', 'help' => 'Year of the publication'),
				'number'			=> array('type' => 'integer', 'help' => 'The periodic number, if any'),
				'pages'				=> array('type' => 'string', 'help'  => 'The number of pages for a book, the related pages of the peridodic for an article'),

				'authors_ids'		=> array('type' => 'many2many', 'foreign_object' => 'knine\Author', 'foreign_field' => 'publications_ids', 'rel_table' => 'knine_rel_publication_author', 'rel_foreign_key' => 'author_id', 'rel_local_key' => 'publication_id'),
				'articles_ids'		=> array('type' => 'many2many', 'foreign_object' => 'knine\Article', 'foreign_field' => 'publications_ids', 'rel_table' => 'knine_rel_article_publication', 'rel_foreign_key' => 'article_id', 'rel_local_key' => 'publication_id'),
			);
		}

	}
}