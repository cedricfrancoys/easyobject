<?php

namespace resilib {

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

				'content'			=> array('type' => 'binary', 'onchange' => 'resilib\Document::onchange_content'),

				'filename'			=> array('type' => 'string'),
				'type'				=> array('type' => 'string'),
				'size'				=> array('type' => 'string', 'onchange' => 'resilib\Document::onchange_size'),				
				'size_txt'			=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'resilib\Document::getSizeTxt'),
				
				'original_url'		=> array('type' => 'string'),
				'resilink'			=> array('type' => 'function', 'result_type' => 'string', 'function' => 'resilib\Document::getResilink'),

				'categories_ids'	=> array('type' => 'many2many', 'label' => 'Categories', 'foreign_object' => 'resilib\Category', 'foreign_field' => 'documents_ids', 'rel_table' => 'resilib_rel_category_document', 'rel_foreign_key' => 'category_id', 'rel_local_key' => 'document_id'),
			);
		}


		public static function getConstraints() {
			return array(
				'original_url'		=> array(
										'error_message_id' => 'invalid_url',
										'function' => function ($url) {
											// Diego Perini posted this version as a gist (https://gist.github.com/729294) :
											$url_regex = '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS';
											return (bool) (preg_match($url_regex, $url));
										}
									),
			);
		}
		
		public static function onchange_content($om, $uid, $oid, $lang) {
			// note : this won't work in client-server mode (since in that case $_FILES array is only available on client-side)
			if(isset($_FILES['content'])) {
				$om->update($uid, 'resilib\Document', array($oid), 
					array(
							'filename'	=> $_FILES['content']['name'], 
							'size'		=> $_FILES['content']['size'], 
							'type'		=> $_FILES['content']['type']
						), 
					$lang);
			}
		}

		public static function onchange_size($om, $uid, $oid, $lang) {
			$om->update($uid, 'resilib\Document', array($oid), 
						array(
								'size_txt' => NULL
						), $lang);
		}
		
		public static function getResilink($om, $uid, $oid, $lang) {			
			return '<a href="index.php?get=resilib_download&id='.$oid.'" target="_blank">Lien resilink</a>';
		}

		public static function getSizeTxt($om, $uid, $oid, $lang) {
			$txt = '';
			$res = $om->browse($uid, 'resilib\Document', array($oid), array('name'), $lang);				
			$txt .= floor($res[$oid]['size']/1000).' Ko';
			if($res[$oid]['pages'] > 0) $txt .= ' ('.$res[$oid]['pages'].' p.)';
			return $txt;
		}		
		
	}
}