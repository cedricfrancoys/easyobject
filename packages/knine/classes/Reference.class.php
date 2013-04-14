<?php
/**
 * KNINE php library
 *
 * Reference class
 *
 */

namespace knine {

	class Reference extends \core\Object {

		public static function getColumns() {
			return array(
				'notes'				=> array('type' => 'text'),
				'publication_id'	=> array('type' => 'many2one', 'foreign_object' => 'knine\Publication'),
				'article_id'		=> array('type' => 'many2one', 'foreign_object' => 'knine\Article'),
				'notes_short'		=> array('type' => 'function', 'result_type' => 'string', 'function' => 'knine\Reference::callable_getNotesShort'),
			);
		}

		public static function callable_getNotesShort($om, $uid, $oid, $lang) {
			$res = $om->browse($uid, 'knine\Reference', array($oid), array('notes'), $lang);
			return substr($res[$oid]['notes'], 0, 50);
		}

	}
}