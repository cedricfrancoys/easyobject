<?php

namespace resilib {

	class Category extends \core\Object {

		public static function getColumns() {
			return array(
				'name'				=> array('type' => 'string', 'onchange' => 'resilib\Category::onchangeName'),
				'mnemonic'			=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'resilib\Category::getMnemonic'),
				'parent_id'			=> array('type' => 'many2one', 'foreign_object' => 'resilib\Category', 'onchange' => 'resilib\Category::onchangeParentId'),
				'path'				=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'resilib\Category::getPath'),
				
				'children_ids'		=> array('type' => 'one2many', 'foreign_object' => 'resilib\Category', 'foreign_field' => 'parent_id', 'order'  => 'name'),
				'documents_ids'		=> array('type' => 'many2many', 'label' => 'Documents', 'foreign_object' => 'resilib\Document', 'foreign_field' => 'categories_ids', 'rel_table' => 'resilib_rel_category_document', 'rel_foreign_key' => 'document_id', 'rel_local_key' => 'category_id'),				
			);
		}

		public static function onchangeName($om, $uid, $oid, $lang) {
			// force re-compute mnemonic and path
			$om->update($uid, 'resilib\Category', array($oid), 
						array(
								'mnemonic' => NULL,
								'path' => NULL,								
						), $lang);
			// find all subcategories and force to re-compute path
			$categories_ids = $om->search($uid, 'resilib\Category', array(array(array('parent_id', '=', $oid))));
			foreach($categories_ids as $category_id)
				Category::onchangeName($om, $uid, $category_id, $lang);
/*
			$res = $om->browse($uid, 'resilib\Category', array($oid), array('children_ids'), $lang);						
			foreach($res[$oid]['children_ids'] as $category_id)
				Category::onchangeName($om, $uid, $category_id, $lang);
*/				
		}
		
		public static function onchangeParentId($om, $uid, $oid, $lang) {
			$om->update($uid, 'resilib\Category', array($oid), 
						array(
								'path' => NULL,
						), $lang);
			// force recompute path for all subcategories
			$categories_ids = $om->search($uid, 'resilib\Category', array(array(array('parent_id', '=', $oid))));
			foreach($categories_ids as $category_id)
				Category::onchangeParentId($om, $uid, $category_id, $lang);
			
/*			
			$res = $om->browse($uid, 'resilib\Category', array($oid), array('children_ids'), $lang);						
			foreach($res[$oid]['children_ids'] as $category_id)
				Category::onchangeParentId($om, $uid, $category_id, $lang);
*/				
		}

		public static function getMnemonic($om, $uid, $oid, $lang) {
			load_class('utils/FSManipulator');
			$res = $om->browse($uid, 'resilib\Category', array($oid), array('name'), $lang);			
			return \FSManipulator::getSanitizedName($res[$oid]['name']);
		}

		public static function getPath($om, $uid, $oid, $lang) {
			$path_array = array();			
			do {
				$res = $om->browse($uid, 'resilib\Category', array($oid), array('name', 'parent_id'), $lang);
				array_push($path_array, $res[$oid]['name']);
				$oid = $res[$oid]['parent_id'];
			} while($oid > 0);
			return implode('/', array_reverse($path_array));			
		}

	}
}