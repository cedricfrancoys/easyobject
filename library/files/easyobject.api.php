<?php
/**
*    This file is part of the easyObject project.
*    http://www.cedricfrancoys.be/easyobject
*
*    Copyright (C) 2012  Cedric Francoys
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define('__EASYOBJECT_LIB', true) or die('unable to define or already defined constant __EASYOBJECT_LIB');
defined('__FC_LIB') or die(__FILE__.' requires fc.lib.php');

load_class('orm/ObjectManager');
load_class('orm/AccessController');

if(OPERATION_MODE == 'client-server') {
	defined('__PHPRPC_LIB') or include_file('phprpc.lib.php');
}

/**
* Set of stand-alone functions allowing to :
* 	- relieve the user from object notations and repetitive multi-steps calls,
* 	- ease remote calls in client-server mode
*   - ensure that OPERATION_MODE is properly set (unknown values results in fatal error)
*/

function user_id($session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$ac = &AccessController::getInstance();
		return $ac->user_id($session_id);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = 0;
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('user_id', array($session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to obtain user id : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function user_key($session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$ac = &AccessController::getInstance();
		return $ac->user_key($session_id);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = 0;
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('user_key', array($session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to obtain user key : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function user_lang($session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$ac = &AccessController::getInstance();
		return $ac->user_lang($session_id);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = DEFAULT_LANG;
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('user_lang', array($session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to obtain user lang : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function login($login, $password, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$ac = &AccessController::getInstance();
		return $ac->login($session_id, $login, $password);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = 0;
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('login', array($login, $password, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to obtain user id : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function validate($object_class, &$values) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->validate($object_class, $values);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = false;
		try {
			load_class('objects/'.ObjectManager::getObjectClassFileName($object_class), $object_class);
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('validate', array($object_class, $values)));
		}
		catch(Exception $e) {
			trigger_error('unable to validate object : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

/**
* Returns an instance of the specified class holding data associated with specified identifier.
*
* 	Because handling the object instance required the class to be declared this method only works in PHP
* 	(In order to use it in other languages, it would be necessary to declare the classes in each programming language and overload the setters and getters)
*
* @param string $object_class
* @param integer $object_id
* @param string $session_id
*/
function &get($object_class, $object_id, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->get(user_id($session_id), $object_class, $object_id);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$object = null;
		try {
			load_class('objects/'.ObjectManager::getObjectClassFileName($object_class), $object_class);
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$object = $rpc_client->call(array('get', array($object_class, $object_id, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to get object : '.$e->getMessage(), E_USER_WARNING);
		}
		return $object;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function &browse($object_class, $ids=null, $fields=null, $lang=DEFAULT_LANG, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->browse(user_id($session_id), $object_class, $ids, $fields, $lang);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = array();
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('browse', array($object_class, $ids, $fields, $lang, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to browse data : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function search($object_class, $domain=null, $order='id', $sort='asc', $start=0, $limit='', $lang=DEFAULT_LANG, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->search(user_id($session_id), $object_class, $domain, $order, $sort, $start, $limit, $lang);
	}
	elseif(OPERATION_MODE == 'client-server') {
		$result = array();
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('search', array($object_class, $domain, $order, $sort, $start, $limit, $lang, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to search for data : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function update($object_class, $ids, $values=null, $lang=DEFAULT_LANG, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->update(user_id($session_id), $object_class, $ids, $values, $lang);
	}
	elseif(OPERATION_MODE == 'client-server') {
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('update', array($object_class, $ids, $values, $lang, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to update data : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}

function remove($object_class, $ids, $permanent=false, $session_id=SESSION_ID) {
	if(OPERATION_MODE == 'standalone' || (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server')) {
		$om = &ObjectManager::getInstance();
		return $om->remove(user_id($session_id), $object_class, $ids, $permanent);
	}
	elseif(OPERATION_MODE == 'client-server') {
		try {
			$rpc_client = new PHPRPC_Client(RPC_HOST, RPC_PORT);
			$result = $rpc_client->call(array('remove', array($object_class, $ids, $permanent, $session_id)));
		}
		catch(Exception $e) {
			trigger_error('unable to remove data : '.$e->getMessage(), E_USER_WARNING);
		}
		return $result;
	}
	else trigger_error('easyobject.api.php, unknown operation mode: check configuration file', E_USER_ERROR);
}


/* additional utility functions */
function get_packages() {
	$packages_list = array();
	$package_directory = getcwd().'/packages';
	if(is_dir($package_directory) && ($list = scandir($package_directory))) {
		foreach($list as $node) if(is_dir($package_directory.'/'.$node) && !in_array($node, array('.', '..'))) $packages_list[] = $node;
	}
	return $packages_list;
}

function get_classes($package_name) {
	$classes_list = array();
	$package_directory = getcwd().'/packages/'.$package_name.'/classes';
	if(is_dir($package_directory) && ($list = scandir($package_directory))) {
		foreach($list as $node) if (stristr($node, '.class.php') && is_file($package_directory.'/'.$node)) $classes_list[] = substr($node, 0, -10);
	}
	return $classes_list;
}