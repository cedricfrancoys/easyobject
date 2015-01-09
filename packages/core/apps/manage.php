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

$params = get_params(array('lang'=>'en'));

$html = new HtmlWrapper();
$html->addCSSFile('html/css/easyobject/base.css');
$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');
$html->addCSSFile('html/css/jquery.ui.daterangepicker.css');
$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');
// todo : include these in the loader
$html->addJSFile('html/js/ckeditor/ckeditor.js');
$html->addJSFile('html/js/ace/src-min/ace.js');


$html->addJSFile('html/js/easyObject.min.js');

//$html->addJSFile('html/js/easyObject.loader.js');

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

print($html);