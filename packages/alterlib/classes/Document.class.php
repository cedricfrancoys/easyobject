<?php

namespace alterlib {

	class Document extends \core\Object {

		public static function getColumns() {
			return array(
				'title'				=> array('type' => 'string'),
				'author'			=> array('type' => 'string'),
				'language'			=> array('type' => 'selection', 'selection' => array('fr' => 'Français', 'en' => 'English', 'es' => 'Español')),
				'last_update'		=> array('type' => 'date'),				
				'description'		=> array('type' => 'short_text'),
				'pages'				=> array('type' => 'integer'),
								
				'licence'			=> array('type' => 'string'),

				'content'			=> array('type' => 'binary', 'onchange' => 'alterlib\Document::onchange_content'),

				'filename'			=> array('type' => 'string'),
				'type'				=> array('type' => 'string'),
				'size'				=> array('type' => 'string', 'onchange' => 'alterlib\Document::onchange_size'),				
				'size_txt'			=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'alterlib\Document::getSizeTxt'),
				
				'original_url'		=> array('type' => 'string'),
				'resilink'			=> array('type' => 'function', 'result_type' => 'string', 'function' => 'alterlib\Document::getResilink'),

				'categories_ids'	=> array('type' => 'many2many', 'label' => 'Categories', 'foreign_object' => 'alterlib\Category', 'foreign_field' => 'documents_ids', 'rel_table' => 'alterlib_rel_category_document', 'rel_foreign_key' => 'category_id', 'rel_local_key' => 'document_id'),
			);
		}

		public static function onchange_content($om, $uid, $oid, $lang) {
			// note : this won't work in client-server mode (since in that case $_FILES array is only available on client-side)
			if(isset($_FILES['content'])) {
				$om->update($uid, 'alterlib\Document', array($oid), 
					array(
							'filename'	=> $_FILES['content']['name'], 
							'size'		=> $_FILES['content']['size'], 
							'type'		=> $_FILES['content']['type']
						), 
					$lang);
			}
		}

		public static function onchange_size($om, $uid, $oid, $lang) {
			$om->update($uid, 'alterlib\Document', array($oid), 
						array(
								'size_txt' => NULL
						), $lang);
		}
		
// todo		
		public static function getResilink($om, $uid, $oid, $lang) {			
			return '<a href="#" target="_blank">Lien resilink</a>';
		}

		public static function getSizeTxt($om, $uid, $oid, $lang) {
			$txt = '';
			$res = $om->browse($uid, 'alterlib\Document', array($oid), array('name'), $lang);				
			$txt .= floor($res[$oid]['size']/1000).' Ko';
			if($res[$oid]['pages'] > 0) $txt .= ' ('.$res[$oid]['pages'].' p.)';
			return $txt;
		}		
		
	}
}