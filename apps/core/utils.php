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
* file: apps/core/user/utils.php
*
* App for using utility plugins
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/HtmlWrapper');


$html = new HtmlWrapper();
$html->addCSSFile('html/css/easyobject/base.css');
$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');

//$html->addJSFile('html/js/easyObject.min.js');
$html->addJSFile('html/js/easyObject.loader.js');


$js_packages = function () {
	$packages_directory = getcwd().'/library/classes/objects';
	$packages_list = array();
	if(is_dir($packages_directory) && ($list = scandir($packages_directory))) {
		foreach($list as $node) if (!in_array($node, array('.', '..')) && is_dir($packages_directory.'/'.$node) && $node{0} != '.') $packages_list[] = "'$node'";
	}
	return '['.implode(',', $packages_list).']';
};

$js_plugins = function () {
	$plugins_directory = getcwd().'/data/utils';
	$plugins_list = array();
	if(is_dir($plugins_directory) && ($list = scandir($plugins_directory))) {
		foreach($list as $node) {
			if (in_array($node, array('.', '..')) || !is_file($plugins_directory.'/'.$node)) continue;
			$parts = explode('.', $node);
			if(!count($parts)) continue;
			$ext = strtolower($parts[count($parts)-1]);
			if($ext != "php") continue;
			$plugins_list[] = "'{$parts[0]}'";
		}
	}
	return '['.implode(',', $plugins_list).']';
};

$html->addScript("
$(document).ready(function() {
	// vars
	var packages = {$js_packages()};
	var plugins = {$js_plugins()};
    var package = 'core';

	// layout
	$('body')
	.append($('<div/>').attr('id', 'menu').css({'height': $(window).height()+'px', 'float':'left', 'width':'200px'})
			.append($('<label/>').css({'margin': '4px', 'font-weight': 'bold', 'display': 'block'}).html('Package: '))
			.append($('<select/>').attr('id', 'package').css({'margin': '4px'}))
			.append($('<label/>').css({'margin': '4px', 'font-weight': 'bold', 'display': 'block'}).html('Plugin: '))
    		.append($('<select/>').attr('id', 'plugin').css({'margin': '4px'}))
    		.append($('<button type=\"button\"/>').attr({'id': 'submit'}).html('ok'))
	)
    .append($('<div/>').attr('id', 'main').css({'display': 'table', 'background-color': 'white', 'height': $(window).height()+'px', 'float':'left', 'width': ($(window).width()-240)+'px', 'padding': '10px'}));

	// feed
	$.each(packages, function(i,item){
		$('#package').append($('<option/>').val(item).html(item));
	});
	$.each(plugins, function(i,item){
		$('#plugin').append($('<option/>').val(item).html(item));
	});

	// events
	$('#submit').click(function() {
		$.getJSON('index.php?get=utils_'+$('#plugin').val()+'&package='+$('#package').val(), function (json_data) {
				$('#main').empty();
				$('#main').append($('<div/>').css({'font-weight': 'bold', 'margin-bottom': '20px'}).append($('#plugin').val() + ' plugin results for package ' +  $('#package').val()));
				$('#main').append($('<textarea/>').attr('id', 'result').css({'width': '100%', 'height': '400px'}));
				if(typeof json_data.result != 'object') {
					// error
				}
				else {
					$.each(json_data.result, function(i, item){
						$('#result').append(item+'\\r\\n');
					});
				}
		});
	});

	// init

});
");

print($html);