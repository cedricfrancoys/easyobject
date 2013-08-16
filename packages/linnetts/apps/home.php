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


$html = new HtmlWrapper();

$html->addCSSFile('html/css/styles.min.css');

$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');

// $html->addJSFile('html/js/ckeditor/ckeditor.js');
// $html->addJSFile('html/js/ace/src-min/ace.js');
// $html->addJSFile('html/js/easyObject.min.js');

$html->addJSFile('html/js/src/jquery.simpletip-1.3.1.js');
$html->addJSFile('html/js/src/jquery.noselect-1.1.js');
$html->addJSFile('html/js/src/jquery-ui.timepicker-1.0.1.js');
$html->addJSFile('html/js/src/jquery-ui.panel.js');
$html->addJSFile('html/js/src/date.js');
$html->addJSFile('html/js/src/jquery-ui.daterangepicker.js');
$html->addJSFile('html/js/src/easyObject.utils.js');
$html->addJSFile('html/js/src/easyObject.grid.js');
$html->addJSFile('html/js/src/easyObject.tree.js');
$html->addJSFile('html/js/src/easyObject.dropdownlist.js');
$html->addJSFile('html/js/src/easyObject.choice.js');
$html->addJSFile('html/js/src/jquery.inputmask.bundle.js');
$html->addJSFile('html/js/src/easyObject.editable.js');
$html->addJSFile('html/js/src/easyObject.form.js');

// changes to this one
$html->addJSFile('html/js/src/easyObject.api.js');

$html->addScript(
<<<'EOT'
$(document).ready(function() {
	easyObject.init({
		dialog_width: 900
	});

	$('#panel').panel({
		width: '100%',
		linkWidth: 160,
		height: 1440,
		afterClick: function(ev){
			var $this = $(ev.target);
			var $pane = $('#'+$this.attr('id')+'-content');
			var loaded = $pane.attr('loaded');
			if($pane.attr('loaded') == 'false') {
				$pane.prepend(easyObject.UI.list({
									class_name: 'linnetts'+'\\'+$this.attr('id'),
									view_name: 'list.default'
							})
				);
				$pane.find('.loader').remove();
				$pane.attr('loaded', 'true');				
		   }
		}
	});
	$('#panel').toggle();
	$('#Workday').trigger('click');
});
EOT
);

$html->add(
<<<EOT
<div id="panel" style="display: none;">
	<ul>
		<li id="Workday">Workday</li>
		<li id="Job">Jobs</li>
		<li id="Invoice">Ivoices</li>
		<li id="Customer">Customers</li>		
	</ul>
	<div id="Workday-content" loaded="false">
		<div class="loader" style="text-align: center; font-size: 17px; background: url(&quot;html/css/jquery/base/images/spinner.gif&quot;) no-repeat scroll 0% 0% transparent; margin-left: 40%; margin-top: 15%; height: 20px; line-height: 14px; width: 140px;">Loading ...</div>	
	</div>
	<div id="Job-content" loaded="false">
		<div class="loader" style="text-align: center; font-size: 17px; background: url(&quot;html/css/jquery/base/images/spinner.gif&quot;) no-repeat scroll 0% 0% transparent; margin-left: 40%; margin-top: 15%; height: 20px; line-height: 14px; width: 140px;">Loading ...</div>	
	</div>
	<div id="Invoice-content" loaded="false">
		<div class="loader" style="text-align: center; font-size: 17px; background: url(&quot;html/css/jquery/base/images/spinner.gif&quot;) no-repeat scroll 0% 0% transparent; margin-left: 40%; margin-top: 15%; height: 20px; line-height: 14px; width: 140px;">Loading ...</div>	
	</div>
	<div id="Customer-content" loaded="false">
		<div class="loader" style="text-align: center; font-size: 17px; background: url(&quot;html/css/jquery/base/images/spinner.gif&quot;) no-repeat scroll 0% 0% transparent; margin-left: 40%; margin-top: 15%; height: 20px; line-height: 14px; width: 140px;">Loading ...</div>
	</div>	
</div>
EOT
);
print($html);