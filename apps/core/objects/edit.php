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
* file: apps/core/objects/edit.php
*
* Displays an edition form matching the specified view and class.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/HtmlWrapper');

check_params(array('id', 'class', 'view'));
// assign values with the received parameters
$params = get_params(array('class'=>'', 'id'=>0, 'view'=>'', 'lang'=>DEFAULT_LANG, 'ui'=>SESSION_LANG_UI));
$object_id = $params['id'];
$object_class = addslashes($params['class']);
$object_view = $params['view'];

$html = new HtmlWrapper();
$html->addCSSFile('html/css/easyobject/base.css');
$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');
$html->addJSFile('html/js/fckeditor/fckeditor.js');

//$html->addJSFile('html/js/easyObject.min.js');
//$html->addJSFile('html/js/easyObject.loader.js');

$html->addJSFile('html/js/src/md5.js');
$html->addJSFile('html/js/src/jquery.simpletip-1.3.1.js');
$html->addJSFile('html/js/src/jquery.noselect-1.1.js');
$html->addJSFile('html/js/src/jquery-ui.timepicker-1.0.1.js');
$html->addJSFile('html/js/src/easyObject.utils.js');
$html->addJSFile('html/js/src/easyObject.grid.js');
$html->addJSFile('html/js/src/easyObject.tree.js');
$html->addJSFile('html/js/src/easyObject.dropdownlist.js');
$html->addJSFile('html/js/src/easyObject.choice.js');
$html->addJSFile('html/js/src/easyObject.editable.js');
$html->addJSFile('html/js/src/easyObject.form.js');
$html->addJSFile('html/js/src/easyObject.api.js');



$html->addScript("

$(document).ready(function() {
	easyObject.init({
		user_lang: '{$params['ui']}'
	});

	edit('$object_class', $object_id, '$object_view', '{$params['lang']}');
});

");

print($html);