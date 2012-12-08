<?php
namespace core {

	class Permission extends \core\Object {

		public static function getColumns() {
			return array(
				'class_name'		=> array('type' => 'string'),
				'group_id'			=> array('type' => 'many2one', 'foreign_object' => 'core\Group', 'foreign_field' => 'permissions_ids'),
				'rights'			=> array('type' 	=> 'integer',
					                         'onchange' => 'core\Permission::onchange_Rights',
											 'help' 	=> "Permissions are set using a binary mask, bits meaning:\nbit 0 (2^0 = 1): create\nbit 1 (2^1 = 2): read\nbit 2 (2^2 = 4): write\nbit 3 (2^3 = 8): delete\nbit 4 (2^4 = 16): manage\n\nEx.: To grant read and write permissions, we need to set bits 1 and 2,\n so field value would be 6 (2^1+2^2)."),
				// virtual fields, used in list views
				'group_name'		=> array('type' => 'related', 'result_type' => 'string', 'foreign_object' => 'core\Group', 'path' => array('group_id','name')),
				'rights_txt'		=> array('type' => 'function', 'store' => true, 'result_type' => 'string', 'function' => 'core\Permission::callable_getRightsTxt'),
			);
		}

		public static function onchange_Rights($om, $uid, $oid, $lang) {
			// note : we are in the core namespace, so we don't need to specify it when referring to this class
			$om->update($uid, 'core\Permission', array($oid), array('rights_txt' => Permission::callable_getRightsTxt($om, $uid, $oid, $lang)), $lang);
		}

		public static function callable_getRightsTxt($om, $uid, $oid, $lang) {
			$rights_txt = array();
			$res = $om->browse($uid, 'core\Permission', array($oid), array('rights'), $lang);
			$rights = $res[$oid]['rights'];
			if($rights & R_CREATE)	$rights_txt[] = 'create';
			if($rights & R_READ)	$rights_txt[] = 'read';
			if($rights & R_WRITE)	$rights_txt[] = 'write';
			if($rights & R_DELETE)	$rights_txt[] = 'delete';
			if($rights & R_MANAGE)	$rights_txt[] = 'manage';
			return implode(',', $rights_txt);
		}

	}
}