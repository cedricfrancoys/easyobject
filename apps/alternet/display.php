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

/*
* file: views/core/user/login.php
*
* Displays the logon screen.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


// force silent mode
set_silent(true);

load_class('utils/HtmlWrapper');


$html = new HtmlWrapper();
$html->addCSSFile('html/css/easyobject/base.css');
$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');
$html->addJSFile('html/js/fckeditor/fckeditor.js');

//$html->addJSFile('html/js/easyObject.min.js');
$html->addJSFile('html/js/easyObject.loader.js');


$content = '';

$ids = search('alternet\Association', null, 'name');
$values = browse('alternet\Association', $ids);

foreach($values as $association) {
	$content .= "
	<div style='margin-bottom: 20px;'>
		<strong>{$association['name']} {$association['type']}</strong><br />
		<div style='width: 500px; text-align: justify;'>
			{$association['description']}<br />
		</div>
	</div>";
}

$html->setBody($content);

print($html);