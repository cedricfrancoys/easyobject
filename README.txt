easyObject 1.0
==============

Copyright (c) 2012 Cedric Francoys  
http://easyobject.cedricfrancoys.be - See LICENSE.txt for license information.

easyObject is a simple though robust tool, easy to get started with and which goal 
is to allow to ensure common tasks of most modern web applications very easily.
In concrete terms, it is a web-oriented ORM (Object-Relational Mapping) that allows 
handling and persistence of objects by associating classes to the tables of a database. 


## Documentation

A complete documentation is available online at the following address:
http://easyobject.cedricfrancoys.be/#help


## Installation

 0. **Prepare**:
	make sure your server configuration meeting the requirements
		- PHP 5.3 + (default configuration with MySQL support)
		- Apache 1.3 + (default configuration with PHP support)
		- MySQL 5.1+ 

 1. **Download** the latest version from the easyObject website:
    http://easyobject.cedricfrancoys.be/#download
    
 2. **Extract** the downloaded archive to the root directory of your website.


## Configuration

 1. Edit the file configuration file (library/files/config.ini.php)
		In most cases, you'll just have to modify the lines regarding the database access:

		define('DB_HOST',		'localhost');   // the full qualified domain name (ex.: www.example.com)
		define('DB_PORT',		'3306');	// this is the default port for MySQL 
		define('DB_USER',		'root');        // this should be changed for security reasons
		define('DB_PASSWORD',		'');		// this should be changed for security reasons
		define('DB_NAME', 		'easyobject');	// specify the name of the DB that you have created or you plan to use		
	
 2. Test your installation
		http://<your site>/index.php?show=core_setup


## Enjoy

Quick start 
If you're new to easyObject, maybe you'll like to try the beginner guide : 
" Learn to build a web application in 10 minutes".