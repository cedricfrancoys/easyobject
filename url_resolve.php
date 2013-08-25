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

*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// include main libraries
defined('__FC_LIB') or include_once('fc.lib.php');
defined('__EASYOBJECT_LIB') or include_file('easyobject.api.php');

// prevent undesired output
set_silent(true);

session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");
define('SESSION_ID', session_id());

$additional_params = array();
$page_found = true;

// get the base directory of the current script (easyObject directory being considered as root for URL redirection)
$base = '/';
$path = explode('/', $_SERVER['SCRIPT_NAME']);
if(($len = count($path)) > 2) for($i = 1, $j = $len-1; $i < $j; ++$i) $base .= $path[$i].'/';

// first look for exact match
$ids = search('core\UrlResolver', array(array(array('human_readable_url', 'like', str_replace($base, '/', $_SERVER['REQUEST_URI'])))));
// if no match, look for a resolver having same URL base location
if(count($ids) <= 0) {
	$ids = search('core\UrlResolver', array(array(array('human_readable_url', 'like', str_replace($base, '/', $_SERVER['REDIRECT_URL'])))));
	// page not found
	if(count($ids) <= 0) $page_found = false;
	else $additional_params = extract_params($_SERVER['REQUEST_URI']);
}

if(!$page_found) {
	// set the header and exit
	header('HTTP/1.0 404 Not Found');
	header('Status: 404 Not Found');
	include_once('html/page_not_found.html');
	exit();
}
else {
	// set the header
	header('HTTP/1.0 200 OK');
	header('Status: 200 OK');
	// get the complete URL
	$values = browse('core\UrlResolver', $ids, array('complete_url'));
	$additional_params = array_merge($additional_params, extract_params($values[$ids[0]]['complete_url']));
 	// set the global var '$_REQUEST' : if a param is already set, its value is overwritten
	foreach($additional_params as $key => $value) $_REQUEST[$key] = $value;
 	// continue as usual
	include_once('index.php');
}