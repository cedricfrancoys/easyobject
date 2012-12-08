<?php
defined('__FC_LIB') or die(__FILE__.' requires fc.lib.php');

load_class('orm/ObjectManager') or die(__FILE__.' unable to load mandatory class : orm/ObjectManager.');


class IdentificationManager {

	private $usersTable;
	private $permissionsTable;

	private function __construct() {
		$this->usersTable = array();
		$this->permissionsTable = array();
	}

	private static function is_valid_login($login) {
		// login must be a valid email address
		return (bool) (preg_match('/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/', $login, $matches));
	}

	private static function is_valid_password($password) {
		// password must be a valid MD5 value
		return (bool) (preg_match('/^[0-9|a-z]{32}$/', $password, $matches));
	}

	private static function unlock($key, $value) {
		if (IdentificationManager::is_valid_password($value)) {
			$hex_next = function ($val) {
				$next = hexdec($val) + 1;
				if($next == 16) $next = 0;
				return dechex($next);
			};
			for($i = 0; $i < 4; ++$i) {
				$pos = (int) substr($key, $i, 1);
				$hex_val = substr($value, $pos, 1);
				$value[$pos] = $hex_next($hex_val);
			}
		}
		return $value;
	}

	private function initializeSession($session_id) {
		$this->usersTable[$session_id] = array('user_id' => GUEST_USER_ID, 'lang' => DEFAULT_LANG, 'login_key' => rand(1000, 9999));
	}

	private static function resolveUserId($login, $password) {
		$om = &ObjectManager::getInstance();
		$ids = $om->search(ROOT_USER_ID, 'core\user', array(array(array('validated','=',true), array('login','=',$login), array('password','=',$password))));
		if(count($ids)) return $ids[0];
		else return 0;
	}

    private function getUserPermissions($user_id, $object_class, $object_id) {
		$user_rights = 0;
		if(isset($this->permissionsTable[$user_id][$object_class])) $user_rights = $this->permissionsTable[$user_id][$object_class];
		else {
			try {
				// root user always has full rights
				if($user_id == ROOT_USER_ID) $user_rights = R_CREATE | R_READ | R_WRITE | R_DELETE | R_MANAGE;
				else {
					// we have to fetch data directly from database otherwise access would results in infinite loops of permissions check
					$db = &DBConnection::getInstance();
					// get the user groups
					$groups_ids = array();
					$result = $db->getRecords(array('core_rel_group_user'), array('group_id'), null, array(array(array('user_id', '=', $user_id))));
					if($db->getAffectedRows()) {
						while($row = $db->fetchArray($result)) $groups_ids[] = $row['group_id'];
						// check if permissions are defined for the current object class
						$result = $db->getRecords(array('core_permission'), array('id', 'rights'), null, array(array(array('class_name', '=', $object_class), array('group_id', 'in', $groups_ids), array('deleted', '=', 0), array('modifier', '>', 0))));
						// get the user permissions
						if($db->getAffectedRows()) while($row = $db->fetchArray($result)) $user_rights |= $row['rights'];
						else $user_rights |= DEFAULT_RIGHTS;
					}
					// use the default permission (DEFAULT_RIGHTS)
					else $user_rights |= DEFAULT_RIGHTS;
					if(isset($this->permissionsTable[$user_id])) $this->permissionsTable[$user_id] = array();
					$this->permissionsTable[$user_id][$object_class] = $user_rights;
				}
			}
			catch(Exception $e) {
				ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
				throw new Exception('unable to check user rights', UNKNOWN_ERROR);
			}
			// user always has read permission on its own object
			if(strcasecmp($object_class, 'core\user') == 0 && $object_id == $user_id) $user_rights = R_READ;
		}
		return $user_rights;
	}

	public static function &getInstance()	{
		if (!isset($GLOBALS['IdentificationManager_instance'])) {
			if(isset($_SESSION['IdentificationManager_instance'])) $GLOBALS['IdentificationManager_instance'] = unserialize($_SESSION['IdentificationManager_instance']);
			else $GLOBALS['IdentificationManager_instance'] = new IdentificationManager();
		}
		return $GLOBALS['IdentificationManager_instance'];
	}

	public function __destruct() {
		// to keep track of users data, we store them in the SESSION global array
		$_SESSION['IdentificationManager_instance'] = serialize($this);
	}

	public function __sleep() {
		return array('usersTable');
	}

	public function resetPermissionsCache() {
		$this->permissionsTable = array();
	}

	public function user_key($session_id) {
		if(!isset($this->usersTable[$session_id])) $this->initializeSession($session_id);
		return $this->usersTable[$session_id]['login_key'];
	}

	public function user_id($session_id) {
		if(!isset($this->usersTable[$session_id])) $this->initializeSession($session_id);
    	return $this->usersTable[$session_id]['user_id'];
	}

	public function user_lang($session_id) {
		$user_id = $this->user_id($session_id);
		$lang = DEFAULT_LANG;
		if($user_id != GUEST_USER_ID) {
			$om = &ObjectManager::getInstance();
			$values = &$om->browse(ROOT_USER_ID, 'core\user', array($user_id), array('language'));
			if(!empty($values[$user_id]['lang'])) $lang = $values[$user_id]['lang'];
		}
		return $lang;
	}

	// We don't use https, however we offer minimum privacy (even if not completely bullet-proof):
	// 1) Only MD5 values of the password are sent from client to server. So user's password stays unknown from the developper.
	// 2) The value that is sent is always different (i.e. : MD5 value is only valid for current session). So, one cannot grab user's password by capturing http packet.
	public function login($session_id, $login, $password) {
        $user_id = 0;
		if(IdentificationManager::is_valid_login($login) && IdentificationManager::is_valid_password($password)) {
			$password = IdentificationManager::unlock($this->user_key($session_id), $password);
			if(($user_id = IdentificationManager::resolveUserId($login, $password)) > 0) {
				$this->usersTable[$session_id]['user_id'] = $user_id;
			}
		}
		return $user_id;
	}

	/**
	*
	*	methods related to rights management
	*	************************************
	*/

	public static function hasRight($user_id, $object_class, $object_id, $right_flags) {
 		$im = &IdentificationManager::getInstance();
 		$user_rights = $im->getUserPermissions($user_id, $object_class, $object_id);
		return (bool) ($user_rights & $right_flags);
	}
}