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

load_class('utils/HtmlWrapper');

$params = get_params(array('ui'=>user_lang()));

$user_key = user_key();

$html = new HtmlWrapper();

$html->addCSSFile('html/css/easyobject/base.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');

//$html->addJSFile('html/js/easyObject.min.js');
$html->addJSFile('html/js/easyObject.loader.js');


$html->addScript("
$(document).ready(function() {
	easyObject.init({
		user_key: '$user_key',
		user_lang: '{$params['ui']}'
	});

	easyObject.UI.dialog({
		content:
			$('<form/>').attr('id', 'login_form').form({
				class_name: 'core\\\\User',
				view_name: 'form.login',
				autosave: false,
				success_handler: function(json_data) {
					// if logon was successful get the new user_id
					if(json_data.result) {
						easyObject.conf.user_id = 0;
						user_id();
						$('#login_form').parent().dialog('close').dialog('destroy');
					}
				}
		}),
		title: 'Logon',
		width: 600,
		height: 'auto'
	});
});
");

print($html);