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

class EventListener {

	private static $errors_stack;

	public function __construct() {
	    self::$errors_stack = array();
		set_error_handler("EventListener::ErrorHandler");
		set_exception_handler("EventListener::UncaughtExceptionHandler");
	}

	public static function getErrorsStack() {
		return self::$errors_stack;
	}

    public static function UncaughtExceptionHandler($exception) {
    	trigger_error('Fatal error : Uncaught exception raised in : '.$exception->getFile().'@'.$exception->getLine().', '.$exception->getMessage(), E_USER_ERROR);
	}

    public static function ExceptionHandler($exception, $exception_thrower) {
    	trigger_error('Error raised by '.$exception_thrower.' : '.$exception->getFile().'@'.$exception->getLine().', '.$exception->getMessage(), E_USER_WARNING);
	}

	/**
	* We use PHP constant E_USER_ERROR for critical errors that need an immediate stop (fatal error)
	*
	* @param mixed $errno
	* @param mixed $errmsg
	* @param mixed $filename
	* @param mixed $linenum
	* @param mixed $vars
	*/
	public static function ErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
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
		$last_error = error_get_last();

	    if (in_array($errno, $user_errors)) $err = "$dt, $errmsg";
	    else $err = "$dt, {$error_types[$errno]} ($filename@$linenum), {$last_error['message']} : $errmsg";

	    self::$errors_stack[] = $err;

	    // fatal error
	    if ($errno == E_USER_ERROR) {
	    	// if we can output messages, display the whole stack
	    	if(function_exists('debug_mode') && debug_mode()) {
	    		print("<pre>A fatal error has been raised. Flushing errors stack:\n");
	    		foreach(self::$errors_stack as $line) print($line."\n");
			}
	     	die();
		}
		// other error
		// if we are in debug mode, then output errors as they come
	    elseif(function_exists('debug_mode') && (debug_mode() & DEBUG_PHP)) print($err);
	}
}