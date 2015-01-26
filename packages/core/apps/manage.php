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
* file: packages/core/apps/manage.php
*
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

set_silent(true);

load_class('utils/HtmlWrapper');

$params = announce(	
	array(	
		'description'	=>	"Allows to manage (view/update/remove) objects from classes available for each packages.",
		'params' 		=>	array(
								'lang'			=> array(
													'description '=> 'Specific language for multilang field.',
													'type' => 'string', 
													'default' => DEFAULT_LANG
													)
							)
	)
);


$html = new HtmlWrapper();
$html->addCSSFile('packages/core/html/css/easyobject/base.css');
$html->addCSSFile('packages/core/html/css/jquery.ui.grid/jquery.ui.grid.css');
$html->addCSSFile('packages/core/html/css/jquery/base/jquery.ui.easyobject.css');
$html->addCSSFile('packages/core/html/css/jquery.ui.daterangepicker.css');
$html->addCSSFile('packages/core/html/css/sliding_login/sliding_login_eo.css');

$html->addJSFile('packages/core/html/js/jquery-1.7.1.min.js');
$html->addJSFile('packages/core/html/js/jquery-ui-1.8.20.custom.min.js');
$html->addJSFile('packages/core/html/js/ckeditor/ckeditor.js');
$html->addJSFile('packages/core/html/js/ace/src-min/ace.js');

$html->addJSFile('packages/core/html/js/easyObject.min.js');

//$html->addJSFile('packages/core/html/js/easyObject.loader.js');


$user_id = user_id();
$user_data = browse('core\User', $user_id, array('firstname', 'lastname'));
$user_name = $user_data[$user_id]['firstname'].' '.$user_data[$user_id]['lastname'];



$js_packages = function () {
	$packages_directory = 'packages';
	$packages_list = array();
	if(is_dir($packages_directory) && ($list = scandir($packages_directory))) {
		foreach($list as $node) if (!in_array($node, array('.', '..')) && is_dir($packages_directory.'/'.$node) && $node{0} != '.') $packages_list[] = "'$node'";
	}
	return '['.implode(',', $packages_list).']';
};

$html->addScript("
$(document).ready(function() {
	// init
	easyObject.init({
		dialog_width: 900,
		user_lang: '{$params['lang']}'
	});
	
	// vars
	var packages = {$js_packages()};
	var languages = ['en', 'fr', 'es'];	
    var selection = $('body');

	
	// sliding login panel
	$('#login-panel-open').click(function(){
		$('#sliding-pane').slideDown('slow');
	});
	$('#login-panel-close').click(function(){
		$('#sliding-pane').slideUp('slow');		
	});	
	
	
	
	// layout
	$('body')
	.append($('<div/>').attr('id', 'menu').css({'height': $(window).height()+'px', 'float':'left', 'width':'200px'})
			.append($('<div/>')
				.append($('<label/>').css({'margin': '4px', 'float': 'left', 'width': '80px', 'font-weight': 'bold'}).html('Lang: '))	
				.append($('<select/>').attr('id', 'lang').css({'margin': '4px'}))	
			)
			.append($('<div/>')
				.append($('<label/>').css({'margin': '4px', 'float': 'left', 'width': '80px', 'font-weight': 'bold'}).html('Package: '))
				.append($('<select/>').attr('id', 'package').css({'margin': '4px'}))
			)
			.append($('<div/>')
				.append($('<label/>').css({'margin': '4px', 'float': 'left', 'width': '80px', 'font-weight': 'bold'}).html('Recylce bin: '))
				.append($('<input type=\"checkbox\"/>').attr('id', 'recycle').css({'margin': '4px'}))
			)
			.append($('<label/>').css({'margin': '4px', 'font-weight': 'bold', 'display': 'block'}).html('Classes: '))
    		.append($('<div/>').attr('id', 'classes').css({'margin': '4px', 'height': ($(window).height()-100)+'px', 'width': '200px', 'overflow': 'auto'}))
	)
    .append($('<div/>').attr('id', 'main').css({'display': 'table', 'background-color': 'white', 'height': $(window).height()+'px', 'float':'left', 'width': ($(window).width()-240)+'px', 'padding': '10px'}));

	// feed
	$.each(languages, function(i,item){
		$('#lang').append($('<option/>').val(item).html(item));
	});
	$.each(packages, function(i,item){
		$('#package').append($('<option/>').val(item).html(item));
	});	

	// events
	$('#package').on('change', function() {
		$.getJSON('index.php?get=core_packages_listing&package='+$(this).val(), function (json_data) {
				var sel = selection.attr('id');
				$('#classes').empty();
				$('#main').empty();
				selection = $('body');
				$.each(json_data, function(i, item){
					$('#classes').append($('<span/>').attr('id', item).css({'display': 'block', 'cursor': 'pointer'}).append(item)
						.click(function() {
							selection.removeClass('selected');
							selection = $(this);
							selection.addClass('selected');				
							$('#recycle').unbind('change').on('change', function() {
								$('#main')
								.empty()
								.append(easyObject.UI.list({
															class_name: $('#package').val()+'\\\\'+selection.html(),
															view_name: 'list.default',
															url: ($('#recycle')[0].checked)?'index.php?get=core_objects_list&mode=recycle':'',
															permanent_deletion: ($('#recycle')[0].checked)?true:false
														   }
														));
							});
							$('#recycle').trigger('change');
						})
					);
				});
				if(sel != undefined) $('span#'+sel).trigger('click');
		});
	});	
	$('#lang').on('change', function() {	
		easyObject.init({
			content_lang: $('#lang').val()
		});		
		$('#package').trigger('change');	
	});
	
	// init
	$('#package').trigger('change');
});
");

$html->add('
<div id="login-panel">
	<div id="sliding-pane">
		<div class="content clearfix">
			<div class="left">
				<h1>Welcome easyForge</h1>
				<h2>easy web applications</h2>
				<p class="grey">framework for making web applications development fast and easy.</p>
				<h2>Current application</h2>
				<p class="grey">Current application is <b>{}</b><br />
				See all available apps <a href="" title="Download" target=_blank">here &raquo;</a></p>
			</div>
			<div class="left">
				<form class="clearfix" action="#" method="post">
					<h1>User Login</h1>
					<label class="grey" for="log">Username:</label>
					<input class="field" type="text" name="log" id="log" value="" size="23" />
					<label class="grey" for="pwd">Password:</label>
					<input class="field" type="password" name="pwd" id="pwd" size="23" />
	            	<label><input name="rememberme" id="rememberme" type="checkbox" checked="checked" value="forever" /> &nbsp;Remember me</label>
        			<div class="clear"></div>
					<input type="submit" name="submit" value="Login" class="bt_login" />
					<a class="lost-pwd" href="#">Lost your password?</a>
				</form>
			</div>
			<div class="left right">			
				<form action="#" method="post">
					<h1>Not registered yet? Sign Up!</h1>				
					<label class="grey" for="signup">Username:</label>
					<input class="field" type="text" name="signup" id="signup" value="" size="23" />
					<label class="grey" for="email">Email:</label>
					<input class="field" type="text" name="email" id="email" size="23" />
					<label>A password will be e-mailed to you.</label>
					<input type="submit" name="submit" value="Register" class="bt_register" />
				</form>
			</div>
		</div>
		<div class="close-tab">
			<a id="login-panel-close" class="close" href="#">Close Panel</a>			
		</div>
	</div>	
	<div class="login-menu">
		<ul class="login">
			<li>Welcome '.$user_name.'</li>
			<li class="sep">|</li>
			<li><a id="login-panel-open" class="open" href="#">Log In | Register</a></li>
		</ul> 
	</div>	
</div>
');

print($html);