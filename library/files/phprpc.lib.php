<?php
/**
*	This file is part of the easyObject project.
*	http://www.cedricfrancoys.be/easyobject
*
*	Copyright (C) 2012  Cedric Francoys
*
*	This program is free software: you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation, either version 3 of the License, or
*	(at your option) any later version.
*
*	This program is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*	GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define('__PHPRPC_LIB', true) or die('unable to define or already defined constant __PHPRPC_LIB');


class PHPRPC {
	protected $socket;

	public function __destruct() {
		socket_close($this->socket);
	}

	protected function init_server($host, $port) {
		($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) or die('error creating socket');
		socket_bind($this->socket, $host, $port) or die('error while binding socket');
		socket_listen($this->socket) or die('error while starting to listen to socket');
	}

	protected function init_client($host, $port) {
		($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) or die('error creating socket');
		if(socket_connect($this->socket, $host, $port) === false) trigger_error('phprpc.lib.php, unable to connect to RPC server : check configuration file', E_USER_ERROR);
	}

	protected function receive_data($socket) {
		$result_data = '';
		while(true) {
			$data = socket_read($socket, RPC_PACKET_SIZE);
			$result_data .= $data;
			if(strlen($data) < RPC_PACKET_SIZE) break;
		}
		return $result_data;
	}

	protected function send_data($socket, $data) {
		while(strlen($data)) {
			$nb = socket_write($socket, $data, strlen($data));
			$data = substr($data, $nb);
		}
	}
}

class PHPRPC_Server extends PHPRPC {

	protected function call($request) {
		$query = unserialize(base64_decode($request));
		if(function_exists('debug_mode') && (debug_mode() & DEBUG_ORM)) print("  call to '{$query[0]}', ".serialize($query[1])."\n");
		if(!is_callable($query[0])) throw new Exception("remote client is trying to call unexisting method '{$query[0]}'");
		$result = call_user_func_array($query[0], $query[1]);
		if(function_exists('debug_mode') && (debug_mode() & DEBUG_ORM)) print("  result : ".serialize($result)."\n");
		return base64_encode(serialize($result));
	}

	public function __construct($host, $port) {
		$this->init_server($host, $port);
	}

	public function start() {
		echo "server is up and listening\n";
		while(true) {
			$cli_sock = socket_accept($this->socket);
			echo "handling request\n";
			$this->send_data($cli_sock, $this->call($this->receive_data($cli_sock)));
		}
	}
}

class PHPRPC_Client extends PHPRPC {

	public function __construct($host, $port) {
		$this->init_client($host, $port);
	}

	public function call($request) {
		$this->send_data($this->socket, base64_encode(serialize($request)));
		$response = unserialize(base64_decode($this->receive_data($this->socket)));
		if($response === false) throw new Exception('RPC server reported an error, check the call syntax');
		return $response;
	}
}