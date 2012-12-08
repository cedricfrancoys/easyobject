<?php
/*
 * KNINE php library
 *
 * DBConnection class
 *
 */
function_exists('load_class') or die(__FILE__.' requires fc.lib.php');
load_class('db/DBManipulatorMySQL');
// ... : add other DBMS manipulator class files here

class DBConnection {

	private $dbConnection;

	private function __construct($host, $port, $name, $user, $password, $dbms) {
		switch($dbms) {
			case 'MYSQL' :
				$this->dbConnection = new DBManipulatorMySQL($host, $port, $name, $user, $password);
				break;
			default:
				trigger_error('DBConnection::DBConnection, unknown DBMS : check configuration file', E_USER_ERROR);
		}
		if($this->dbConnection->connect() === false) {
			trigger_error('DBConnection::DBConnection, unable to establish connection : check connection parameters', E_USER_ERROR);
		}
	}

	public static function &getInstance($host='', $port=0, $name='', $user='', $password='', $dbms = 'MYSQL')	{
		if (!isset($GLOBALS['DBConnection_instance'])) $GLOBALS['DBConnection_instance'] = new DBConnection($host, $port, $name, $user, $password, $dbms);
		return $GLOBALS['DBConnection_instance']->dbConnection;
	}
}