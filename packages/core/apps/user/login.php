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
* file: packages/core/apps/user/login.php
*
* Displays a logon form.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/HtmlWrapper');

$params = get_params(array('ui'=>user_lang()));

$user_key = user_key();

$html = new HtmlWrapper();
$html->addCSSFile('packages/core/html/css/login.css');
$html->addJSFile('packages/core/html/js/jquery-1.7.1.min.js');
$html->addJSFile('packages/core/html/js/src/md5.js');
$html->addJSFile('packages/core/html/js/src/easyObject.utils.js');
$html->addJSFile('packages/core/html/js/easyObject.api.min.js');

$html->addScript("
$(document).ready(function() {
	easyObject.init({
		user_key: '$user_key',
		user_lang: '{$params['ui']}'
	});

	// customize checkbox
    $('.login-form span').addClass('checked').children('input').attr('checked', true);
    $('.login-form span').on('click', function() {
 
        if ($(this).children('input').attr('checked')) {
            $(this).children('input').attr('checked', false);
            $(this).removeClass('checked');
        }
 
        else {
            $(this).children('input').attr('checked', true);
            $(this).addClass('checked');
        }
    });
	$('#submit').on('click', function() {
		$('form').trigger('submit');		
	});

	$('form').bind('submit', function(event){
		$('#password').val(lock('$user_key', $('#password_input').val()));
		$.ajax({
			type: 'GET',
			url: 'index.php?do=core_user_login',
			async: false,
			dataType: 'json',
			data: $(this).serialize(),
			contentType: 'application/json; charset=utf-8',
			success: function(json_data){
				if(json_data.result != true) {
					alert('invalid password or username');
				}
				else window.location.href = json_data.url;
			},
			error: function(e){
			}
		});
		return false;
	});
});
");
 
$html->add(new HtmlBlock(0, 'body', array('background'=>'none repeat scroll 0 0 #646264')));

$src = <<<'EOT'
<div class="login-form">
    <h1>Identification</h1>
    <form> 
        <input type="text" name="login" placeholder="username">
        <input type="password" id="password_input" placeholder="password"> 
		<input type="hidden" id="password" name="password"> 
        <span>
            <input type="checkbox" name="checkbox">
            <label for="checkbox">remember</label>
        </span>
        <button id="submit" type="button">log in</button>
    </form>
</div>
EOT;

$html->add($src);
print($html);