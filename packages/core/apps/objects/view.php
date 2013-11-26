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
* file: packages/core/apps/objects/view.php
*
* Displays an object applying the specified view and output mode (html, pdf, xsl, csv, img, swf, ...).
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode (debug output would corrupt json data)
set_silent(true);

check_params(array('object_class', 'view'));

// assign values with the received parameters
$params = get_params(array(
							'object_class'		=> null,
							'id'				=> 0,
							'ids'				=> null,
							'view'				=> 'form.default',
							'output'			=> 'html',
							'fields'			=> null,
							'domain'			=> array(array()),
							'page'				=> 1,
							'rp'				=> 10,
							'sortname'			=> 'id',
							'sortorder'			=> 'asc',
							'records'			=> null,
							'lang'				=> DEFAULT_LANG,
							'ui'				=> $_SESSION['LANG_UI']
					));

// convert the fields parameter if we received a comma-separated list instead of an array
if(!is_null($params['fields']) && !is_array($params['fields'])) $params['fields'] = explode(',', $params['fields']);

if(is_null($params['ids'])) $params['ids'] = array($params['id']);

/**
*	HTML output
*
*/
if($params['output'] == 'html') {
	// this is the easiest: we let the UI (html/jQuery) deal with the parameters
	load_class('utils/HtmlWrapper');
	$html = new HtmlWrapper();
	$html->addCSSFile('html/css/easyobject/base.css');
	$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
	$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');
	$html->addJSFile('html/js/jquery-1.7.1.min.js');
	$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');

// remove if not necessary
	$html->addJSFile('html/js/ckeditor/ckeditor.js');
	$html->addJSFile('html/js/ace/src-min/ace.js');

//		$html->addJSFile('html/js/easyObject.min.js');
	$html->addJSFile('html/js/easyObject.loader.js');

	list($view, $name) = explode(".", $params['view']);
	$params['object_class'] = addslashes($params['object_class']);

	switch($view) {
		case 'form':
			!empty($params['id']) or die('no object specified');

			$html->addScript("
				$(document).ready(function() {
					$('body').append(easyObject.UI.form({
							class_name: '{$params['object_class']}',
							object_id: {$params['id']},
							view_name: '{$params['view']}',
							lang: '{$params['lang']}',
							ui: '{$params['ui']}'
					}));
				});
			");
			break;
		case 'list':
			$html->addScript("
				$(document).ready(function() {
					$('body').append(easyObject.UI.list({
							class_name: '{$params['object_class']}',
							view_name: '{$params['view']}',
							lang: '{$params['lang']}',
							ui: '{$params['ui']}'
					}));
				});
			");
			break;
	}
	print($html);	
}
else {
	// now, let's deal with other types of output
	
	/**
	*
	* A function that retrieves some data (json formatted) by including a file (using a different scope)
	* (used to retrieve output generated by some other script)
	*/
	function get_include_contents($filename) {
		if(!file_exists($filename)) return '';
		ob_start();
		include $filename;
		return ob_get_clean();
	}
	
	list($view, $name) = explode(".", $params['view']);	
	
	switch($view) {
		case 'list':
			// retrieve necessary data for displaying a list
			$list_data =	array(
								'fields'	=>	array(),	// fields as defined in view file
								'rows'		=>	array(),	// fields values following order of view file
								'widths'	=>	array(),	// fields widths as defined in view file
								'labels'	=>	array()		// fields labels as defined in i18n file
							);

			// 1) get fields list from the specified view and their widths
			// get html from specified view
			$view = get_include_contents('packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/views/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.'.$params['view'].'.html');
			if(empty($view)) return false;
			// use regular expression to locate all 'li' tags in the view
			$tags = array();
			preg_match_all("/<li([^>]*)>.*<\/li>/iU", $view, $matches, PREG_OFFSET_CAPTURE);
			for($i = 0, $j = count($matches[1]); $i < $j; ++$i) {
				// get tag attributes
				$tag = array();
				$args = explode(' ', ltrim($matches[1][$i][0]));
				foreach($args as $arg) {
					if(!strlen($arg)) continue;
					list($attribute, $value) = explode('=', $arg);
					$tag[$attribute] = str_replace('"', '', $value);
				}
				$tags[] = $tag;
			}
			// assign fields and widths
			foreach($tags as $attributes) {
				$list_data['fields'][] = $attributes['id'];
				$list_data['widths'][] = (int) str_replace('%', '', $attributes['width']);
			}
			
			// 2) get JSON data from the core/data/objects/list.php script output
			$_REQUEST['fields'] = $list_data['fields'];	
			$json = json_decode(get_include_contents('packages/core/data/objects/list.php'), true);
			// check JSON validity
			!is_null($json) or die('invalid json returned');
			// get the right sub dataset
			foreach($json['rows'] as $row) {	
				$data = array();
				for($i = 0, $j = count($row['cell']); $i < $j; ++$i) {				
					$data[] = $row['cell'][$i];
				}
				$list_data['rows'][] = $data;
			}

			// 3) retrieve fields labels (if any)
			// by default, set fieldnames as labels
			$list_data['labels'] = $list_data['fields'];
			// check if there is a matching i18n file
			$json_file = 'packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/i18n/'.$params['ui'].'/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.json';
			file_exists($json_file) or die("No translation file for class: {$params['object_class']}");
			$json = json_decode(get_include_contents($json_file), true);
			if(!is_null($json)) {
				foreach($list_data['fields'] as $key => $field ) {
					if(isset($json['model'][$field]) && isset($json['model'][$field]['label'])) $list_data['labels'][$key] = $json['model'][$field]['label'];
				}
			}

			// 4) ouput following the requested method
			switch($params['output']) {
				case 'csv':			
				case 'xls':				
					// create a worksheet and fill it with data
					load_class('utils/PHPExcel');		
					$xls = new PHPExcel();

					// set cells alignment
					$xls->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
					// set columns widths
					for($i = 0, $j = count($list_data['fields']), $k = 'A';$i < $j; ++$i, ++$k) {
						$xls->getActiveSheet()->getColumnDimension($k)->setWidth(90 * $list_data['widths'][$i]/100);
					}

					// set labels (columns headers)
					$xls->getActiveSheet()->fromArray($list_data['labels'],NULL,'A1');
					// Loop through the result set
					$line = 2;
					foreach($list_data['rows'] as $row) {
					   $col = 'A';
					   foreach($row as $cell) {
						  $xls->getActiveSheet()->setCellValue($col.$line,$cell);
						  $col++;
					   }
					   $line++;
					}				
					if($params['output'] == 'xls') {
						// save as an Excel BIFF (xls) file
						header('Content-Type: application/vnd.ms-excel');
						header('Content-Disposition: attachment;filename="export.xls"');
						header('Cache-Control: max-age=0');

						$fileWriter = PHPExcel_IOFactory::createWriter($xls, 'Excel5')
						->save('php://output');					
					}
					elseif($params['output'] == 'csv') {
						// save as a CSV file
						header('Content-Type: text/csv');
						header('Content-Disposition: attachment;filename="export.csv"');
						header('Cache-Control: max-age=0');

						$fileWriter = PHPExcel_IOFactory::createWriter($xls, 'CSV')
						->setDelimiter(',')
						->setEnclosure('"')
						->setLineEnding("\r\n")
						->setSheetIndex(0)
						->save('php://output');					
					}
					break;				
				case 'pdf':
					load_class('utils/phpQuery');
					$doc = phpQuery::newDocument('<table/>');
				
					$table = $doc['table'];
					$header = pq('<tr/>');				
					for($i = 0, $j = count($list_data['fields']);$i < $j; ++$i) {
						$header->append(pq('<td/>')->attr('width', $list_data['widths'][$i].'%')->html($list_data['labels'][$i]));
					}
					$table->append($header);
					foreach($list_data['rows'] as $cell) {
						$row = pq('<tr/>');
						foreach($cell as $value) $row->append(pq('<td/>')->html($value));
						$table->append($row);							
					}					
					
					require_once("classes/utils/dompdf/dompdf_config.inc.php");					
					$styles = "
						<style>
						body {
							font-family:sans-serif;
							font-size: 0.7em;
							color: #000000;
						}
						table {
							table-layout: fixed;
							border: 0;
						}
						table, tr {
							width: 100%;
						}
						table, tr, td {
							border-spacing:0;
							border-collapse:collapse;
						}
						td {
							border: solid 1px #000000;
							vertical-align: top;
						}
						</style>
					";
					$dompdf = new DOMPDF();
					$dompdf->load_html($styles.$doc);
					$dompdf->set_paper("letter", "landscape");
					$dompdf->render();
					$dompdf->stream("export.pdf", array("Attachment" => false));				
					break;					
			}
			break;
		case 'form':
			$package = ObjectManager::getObjectPackageName($params['object_class']);
			$class_name = ObjectManager::getObjectName($params['object_class']);

			// get html content
			$view_file = "packages/{$package}/views/{$class_name}.{$params['view']}.html";
			file_exists($view_file) or die("No file for view: {$params['view']}");
			$html = file_get_contents($view_file);			

			// transforms html document
			load_class('utils/phpQuery');
			$doc = phpQuery::newDocumentHTML($html, 'UTF-8');

			$orientation = pq('form')->attr('orientation');
			// if(!empty(pq('form')->attr('orientation'))) 
			if(empty($orientation)) $orientation  = 'portrait';
			
			// parser function allowing recursive calls
			function parse($elem, $object_class, $ids, $lang) {
				$html = '';
				
				$package = ObjectManager::getObjectPackageName($object_class);
				$class_name = ObjectManager::getObjectName($object_class);

				// 1) retrieve necessary fields (from var tags of specified view)				
				$fields = array();
				foreach($elem['var'] as $node) {
					$var = pq($node);
					if($var->parents()->filter('var')->length()==0) $fields[] = $var->attr('id');
				}
				
				// 2) get labels
				// check if there is a matching i18n file
				// set fields names as default value
				$labels = $fields;
				$json_file = 'packages/'.strtolower($package).'/i18n/'.$lang.'/'.ucfirst($class_name).'.json';
				file_exists($json_file) or die("No translation file for class: {$object_class}");
				$labels = json_decode(get_include_contents($json_file), true);						

				// 3) get schema
				$om = &ObjectManager::getInstance();
				$schema = $om->getObjectSchema($object_class);
				
				// 4) get values
				// we need to browse all required fields (defined in the view)
				$values = &browse($object_class, $ids, $fields);

				// 5) fill the template in
				// remove button tags
				pq('button')->remove();			
				// replace label tags with their associated value ($labels array)				
				foreach($elem['label)'] as $node) {
					$label = pq($node);
					if($label->parents()->filter('var')->length()==0) {
						if(!is_null($label->attr('for'))) {
							if(isset($labels['model'][$label->attr('for')])) $label->replaceWith($labels['model'][$label->attr('for')]['label'].':');
						}
						elseif(!is_null($label->attr('name'))) {
							if(isset($labels['view'][$label->attr('name')])) $label->replaceWith($labels['view'][$label->attr('name')]['label'].':');
						}
					}
				}
				// replace var tags with their associated value ($values array)
				foreach($ids as $id) {
					// we duplicate the current document
					$new_elem = $elem->clone();
					foreach($new_elem['var'] as $node) {
						$var = pq($node);

						if($var->parents()->filter('var')->length()==0) {					
							if(($field = $var->attr('id')) == null) continue;
							$type = isset($schema[$field]['result_type'])?$schema[$field]['result_type']:$schema[$field]['type'];						
							$parent = $var->parent();
							if(in_array($type, array('one2many', 'many2one', 'many2many'))) {
								// we have to recurse
								if(!is_array($values[$id][$field])) $values[$id][$field] = array($values[$id][$field]);
								$parent->append(parse(pq('<div />')->append($var->children()), $schema[$field]['foreign_object'], $values[$id][$field], $lang));
							}
							else {
								if(isset($values[$id][$field])) {
									switch($type) {
											case 'string':
											case 'short_text':
											case 'text':
												$parent->append(str_replace(array("\n", "\r\n"), "<br />", $values[$id][$field]));
												break;
											case 'float':
												$parent->append(sprintf("%.2f", $values[$id][$field]));	
												break;
											default:
												$parent->append($values[$id][$field]);
												break;											
									}									
								}
							}
							$var->remove();
						}
					}
					$html .= $new_elem->children();
				}
				return $html;					
			};
			// retrieve resulting html inside the form tag (root of the view) 			
			$html = parse($doc, $params['object_class'], $params['ids'], $params['ui']);

			// output accordingly to the specified method
			switch($params['output']) {
				case 'pdf':
// todo: clean this (there should not be additional styles here...)				
					$styles = "
						<style>
						body {
							font-family:sans-serif;
							font-size: 0.7em !important;
							color: #000000 !important;
						}
						table {
							table-layout: fixed;
						}
						table, tr {
							width: 100%;
						}
						table, tr, td {
							border-spacing:0;
							border-collapse:collapse;
						}
						td.label {
							width: 5%;
							padding-right: 5px;
							padding-bottom: 5px;
							text-align: left;
							font-weight: bold;
							white-space: nowrap;
							overflow: hidden;
							vertical-align: top;
						}
						td.field {
							width: 95%;
							text-align: left;
							padding-bottom: 5px;
							overflow: overflow;
							white-space: normal;
							word-wrap: break-word;
							vertical-align: top;
						}
						</style>
					";
					require_once("classes/utils/dompdf/dompdf_config.inc.php");
					
					$dompdf = new DOMPDF();
					$dompdf->load_html($styles.$html, 'UTF-8');
					$dompdf->set_paper("letter", $orientation);
					$dompdf->render();

					// add footer on all pages
					$canvas = $dompdf->get_canvas();
					$y = $canvas->get_height() - 2 * $text_height - 35;
					$font = Font_Metrics::get_font("helvetica", "bold");
					$canvas->page_text(529, $y, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0,0,0));
					
					$dompdf->stream("export.pdf", array("Attachment" => false));
					break;
			}
			break;
	}
}