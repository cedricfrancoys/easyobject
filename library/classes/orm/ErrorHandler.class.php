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

class ErrorHandler {

	private static $errors_stack;

	public function __construct() {
	    self::$errors_stack = array();
		set_error_handler("ErrorHandler::ErrorHandling");
		set_exception_handler("ErrorHandler::UncaughtExceptionHandling");
	}

	public static function getErrorsStack() {
		return self::$errors_stack;
	}

    public static function UncaughtExceptionHandling($exception) {
    	trigger_error('Fatal error : Uncaught exception raised by '.$exception_thrower.' : '.$exception->getFile().'@'.$exception->getLine().', '.$exception->getMessage(), E_USER_ERROR);
	}

    public static function ExceptionHandling($exception, $exception_thrower) {
    	trigger_error('Error raised by '.$exception_thrower.' : '.$exception->getFile().'@'.$exception->getLine().', '.$exception->getMessage(), E_USER_WARNING);
	}

	public static function ErrorHandling($errno, $errmsg, $filename, $linenum, $vars) {
	    $error_types = array (
		                E_ERROR				=> 'Error',
		                E_USER_ERROR		=> 'Error',
		                E_WARNING			=> 'Warning',
		                E_USER_WARNING		=> 'Warning',
		                E_PARSE				=> 'Parsing Error',
		                E_NOTICE			=> 'Notice',
		                E_USER_NOTICE		=> 'Notice',
		                E_CORE_ERROR		=> 'Core Error',
		                E_CORE_WARNING		=> 'Core Warning',
		                E_COMPILE_ERROR		=> 'Compile Error',
		                E_COMPILE_WARNING	=> 'Compile Warning',
		                E_STRICT			=> 'Runtime Notice',
		                E_RECOVERABLE_ERROR	=> 'Catchable Fatal Error'
	                );
	    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
	    $dt = date("Y-m-d H:i:s (T)");
		$error = error_get_last();
		$error_message = $error['message'];

	    if (in_array($errno, $user_errors)) $err = "$dt, $errmsg.\n";
	    else $err = "$dt, {$error_types[$errno]} ($filename@$linenum), $error_message : $errmsg.\n";

	    if ($errno == E_USER_ERROR) die($err);
	    elseif(function_exists('debug_mode') && (debug_mode() & DEBUG_PHP)) print($err);

	    self::$errors_stack[] = $errmsg;
	}
}