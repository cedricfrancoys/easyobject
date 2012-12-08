<?php
// under windows, require following extension in php.ini:
// extension=php_sockets.dll

// startup command line examples :
// 		linux : php rpc_server.php  (make sure the file has execution flag set)
// 		windows : c:\wamp\bin\php\php5.3.0\php.exe rpc_server.php

defined('__FC_LIB') or include_once('fc.lib.php');
defined('__EASYOBJECT_LIB') or include_file('easyobject.lib.php');


// server calls are equivalent to standalone-mode calls
// i.e. (OPERATION_MODE == 'client-server' && OPERATION_SIDE == 'server') is equivalent to (OPERATION_MODE == 'standalone')
define('OPERATION_SIDE', 'server');
// constant SESSION_ID is required by easyobject library but won't be used server-side
define('SESSION_ID', null);

class Timer {
	private $start_time;
	private $last_time;
	private $timeout;

	const TIME_IS_UP = true;

	public function __construct($timeout) {
		$this->timeout = $timeout;
	}

	public function start() {
		$this->start_time = time();
   	}

	public function iterate() {
		$this->last_time = time();
		if($this->last_time - $this->start_time >= $this->timeout) {
			$this->start_time = $this->last_time;
			return Timer::TIME_IS_UP;
		}
		return false;
	}
}

class easyObjectServer extends PHPRPC_Server {
	private $objectManager;
	private $IdentificationManager;
	private $timer;
	private $msg_count;

	public function __construct($host, $port) {
		parent::__construct($host, $port);
		$this->objectManager = &ObjectManager::getInstance();
		$this->IdentificationManager = &IdentificationManager::getInstance();

		$this->timer = new Timer(STORE_INTERVAL*60);
		$this->msg_count = 0;
	}

	public function start() {
		echo "server is up and listening\n";
		$this->timer->start();
		$null_array = null;
		while(true) {
			$array_read = array($this->socket);
			if(socket_select($array_read, $null_array, $null_array, 30)) {
				if(($cli_sock = socket_accept($this->socket)) !== false) {
					++$this->msg_count;
					if(defined('DEBUG_MODE') && (DEBUG_MODE & DEBUG_ORM)) print("#{$this->msg_count} handling request\n");
					$this->send_data($cli_sock, $this->call($this->receive_data($cli_sock)));
				}
			}
			if($this->timer->iterate() == Timer::TIME_IS_UP) {
				echo "storing changes to database\n";
				$this->objectManager->store();
				$this->IdentificationManager->resetPermissionsCache();
			}
		}
	}

	protected function call($request) {
    	try {
			$result = parent::call($request);
		}
		catch(Exception $e) {
			ErrorHandler::ExceptionHandling($e, __FILE__.', '.__METHOD__);
			$result = base64_encode(serialize(false));
		}
		return $result;
	}

}

// instanciate and start the server
$server = new easyObjectServer(RPC_HOST, RPC_PORT);
$server->start();