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
* file: packages/dietwatch/apps/compose.php
*
* Allows to make the composition of a meal and see what nutrients it contains.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
//set_silent(true);


load_class('utils/HtmlWrapper');

$html = new HtmlWrapper();
$html->addJSFile('html/js/jquery-1.7.1.min.js');
$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');
$html->addJSFile('packages/dietwatch/html/js/config.js');
$html->addJSFile('packages/dietwatch/html/js/diet.js');
$html->addCSSFile('packages/dietwatch/html/css/style.css');
$html->addCSSFile('html/css/jquery/base/jquery.ui.all.css');
$html->addStyle("#main {visibility: hidden;}");
$html->add('
	<div id="loader"><div class="layer"></div><div class="loading">Loading, please wait...</div></div>
	<div id="main" class="ui-tabs">
		 <ul>
			 <li><a href="#compose"><label for="compose">Composition</label></a></li>
			 <li><a href="#view"><label for="result">Résultat</label></a></li>
		 </ul>
		<div id="compose" class="ui-tabs-hide">
			<fieldset id="list"><legend><label for="composition">Composition du repas</label></legend></fieldset>
			<button id="add" type="button"><label for="add_food_item">Ajouter un aliment</label></button>		
			<button id="export" type="button"><label for="save_list">Sauvegarder la liste</label></button>
			<button id="import" type="button"><label for="load_list">Importer une liste</label></button>
		</div>		 
		<div id="view" class="ui-tabs-hide">
			<fieldset><legend><label for="content">Contenance en nutriments du repas</label></legend>
				<div id="result" class="details" style="border: 0;"></div>
			</fieldset>		
		</div>		 		
	</div>
');
print($html);