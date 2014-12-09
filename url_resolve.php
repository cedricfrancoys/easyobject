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

// include main libraries (this script is an entry point)
defined('__FC_LIB') or include_once('fc.lib.php');
include_once('config.inc.php');
defined('__EASYOBJECT_LIB') or include_file('easyobject.api.php');

// disable output
set_silent(true);

session_start() or die(__FILE__.', line '.__LINE__.", unable to start session.");
define('SESSION_ID', session_id());


// Here are the server environment vars we use and some examples of typical values
// $_SERVER['SCRIPT_NAME']		ex.: /easyobject/url_resolve.php
// $_SERVER['REQUEST_URI']		ex.: /easyobject/en/presentation/project, /easyobject/en/presentation/index.php?show=icway_site&page_id=6&lang=en
// $_SERVER['HTTP_REFERER']		ex.: http://localhost/easyobject/en/presentation/project


// get the base directory of the current script (easyObject directory being considered as root for URL redirection)
$base = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/')+1);
// note: in our example, $base should now contain '/easyobject/'

/**
* Get a clean version of the request URI
*/
$request_uri = $_SERVER['REQUEST_URI'];
// remove everything after question mark, if any
if(($pos = strpos($request_uri, '?')) !== false) $request_uri = substr($request_uri, 0, $pos);
// remove 'index.php', if explicit
$request_uri = str_replace('/index.php', '/', $request_uri);

/**
* Check for redirection
*/
// if the main entry point (index.php) is requested (inside some virtual subfolder), then we redirect to the root
// example: 
// '/easyobject/presentation/index.php?show=icway_site&page_id=6&lang=fr'
// must redirect to 
// '/easyobject/index.php?show=icway_site&page_id=6&lang=fr'
// therefore '/easyobject/presentation/' must redirect to '/easyobject/'
if(substr($request_uri, -1) == '/') {
	$request_uri = str_replace($request_uri, $base, $_SERVER['REQUEST_URI']);
	header('HTTP/1.0 200 OK');
	header('Status: 200 OK');
	header("Location: ".$request_uri);
	exit();
}

/**
* Check for content type
*/
// if the resource being requested differs from a script (name containing a dot and thus suggesting a file, like 'style.css' or 'ui.js'),
// then we try to find out its location (assuming referer's url might involve virtual folders)
$parts = explode('.', $request_uri);
if(count($parts) > 1) {
	// get everything after the last dot
	$extension = strtolower($parts[count($parts)-1]);
	// if resource is among accepted extensions
	if(in_array($extension, array('htm', 'html', 'css', 'js', 'png', 'gif', 'jpg', 'jpeg'))) {
		// get path from referer's URL (current URL must have that part in common)
		$referer_url = config\get_script_path($_SERVER['HTTP_REFERER']).'/';
		// keep only the part following referer's url
		$request_uri = substr(config\get_url(), strlen($referer_url));
		header('HTTP/1.0 200 OK');
		header('Status: 200 OK');
		header("Location: ".$base.$request_uri);
		exit();
	}
}

/**
* Get related UrlResolver object
*/
// if we reached this part, it means we are looking for a script pointed by an object of class 'core\UrlResolver'

// first, look for exact match
$request_uri = str_replace($base, '/', $_SERVER['REQUEST_URI']);

$ids = search('core\UrlResolver', array(array(array('human_readable_url', 'like', $request_uri))));
// if no match, look for a resolver having same URL base location
if(count($ids) <= 0) {
	if(($pos = strrpos($request_uri, '?')) !== false) $request_uri = substr($request_uri, 0, $pos);
	$ids = search('core\UrlResolver', array(array(array('human_readable_url', 'like', $request_uri))));
	$additional_params = extract_params($_SERVER['REQUEST_URI']);
}
else $additional_params = array();

// page not found
if(count($ids) <= 0) {
	// set the header to HTTP 404 and exit
	header('HTTP/1.0 404 Not Found');
	header('Status: 404 Not Found');
	include_once('html/page_not_found.html');
}
// URL found in DB
else {
	// get the complete URL (we should get only one result)
	$values = browse('core\UrlResolver', $ids, array('complete_url', 'human_readable_url'));
	$additional_params = array_merge($additional_params, extract_params($values[$ids[0]]['complete_url']));		
	// set the global var '$_REQUEST' (if a param is already set, its value is overwritten)
	foreach($additional_params as $key => $value) $_REQUEST[$key] = $value;
	// set the header to HTTP 200 and relay processing to index.php
	header('HTTP/1.0 200 OK');
	header('Status: 200 OK');
	// continue as usual
	include_once('index.php');
}