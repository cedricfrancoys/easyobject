<?php
/**
 * KNINE php library
 *
 * DBManipulator class
 *
 */
class DBManipulator {

	/**
	 * DB server hostname
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $host_name;

	/**
	 * DB server connection port
	 *
	 * @var		integer
	 * @access	protected
	 */
	protected $port;

	/**
	 * DB name
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $db_name;


	/**
	 * DB user
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $user_name;


	/**
	 * DB password
	 *
	 * @var		string
	 * @access	protected
	 */
	protected $password;


	/**
	 * Latest error id
	 *
	 * @var		integer
	 * @access	protected
	 */
	protected $last_id;


	/**
	 * Number of rows affected by last query
	 *
	 * @var		integer
	 * @access	protected
	 */
	protected $affected_rows;

	protected $last_query;

	protected $mysql_handler;


	/**
	 * Class constructor
	 *
	 * Initialize the DBMS data for SQL transactions
	 *
	 * @access   public
 	 * @param    string    DB server hostname
	 * @param    string    DB name
	 * @param    string    Username to use for the connection
	 * @param    string    Password to use for the connection
 	 * @return   void
	 */
	public final function __construct($host, $port, $db, $user, $pass) {
		$this->host_name = $host;
		$this->port = $port;
		$this->db_name   = $db;
		$this->user_name = $user;
		$this->password = $pass;
	}


	public final function __destruct() {
		$this->disconnect();
	}

	/**
	 * Open the DBMS connection
	 *
	 * @return   integer   The status of the connect function call
	 * @access   public
	 */
	public function connect() {
	}

	/**
	* Close the DBMS connection
	*
	* @return   integer   Status of the close function call
	* @access   public
	*/
	public function disconnect() {
	}


	public final function isEmpty($value) {
		return (empty($value) || $value == '0000-00-00' || !strcasecmp($value, 'false') || !strcasecmp($value, 'null'));
	}

	public function getLastId() {
		return $this->last_id;
	}


	public function getAffectedRows() {
		return $this->affected_rows;
	}


	protected function setLastId($id) {
		$this->last_id = $id;
	}


	protected function setAffectedRows($affected_rows) {
		$this->affected_rows = $affected_rows;
	}

}