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
* Displays an object applying the specified view and output mode (html, pdf, img, swf, ...).
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
							'ui'				=> SESSION_LANG_UI		
					));
			
if($params['fields'] && !is_array($params['fields'])) $params['fields'] = explode(',', $params['fields']);

// todo: if fields is null, get simple_fields from schema

// output result
switch($params['output']) {
/**
*	HTML output
*
*/
	case 'html':
		load_class('utils/HtmlWrapper');	
		$html = new HtmlWrapper();
		$html->addCSSFile('packages/linnetts/html/css/styles.css');
		$html->addCSSFile('html/css/jquery.ui.grid/jquery.ui.grid.css');
		$html->addCSSFile('html/css/jquery/base/jquery.ui.easyobject.css');
		$html->addJSFile('html/js/jquery-1.7.1.min.js');
		$html->addJSFile('html/js/jquery-ui-1.8.20.custom.min.js');
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
		break;
	case 'pdf':
/**
*	PDF output
*
*/
	
// IMPORTANT: right now, this only works  with list views (with HTML having li tags)
// it seems there is no point of outputing a form in PDF though...

		// we will need to get some data by including files (by it with a function, we use a different scope for vars in the included file)
		function get_include_contents($filename) {
			if(!file_exists($filename)) return '';
			ob_start();
			include $filename;
			return ob_get_clean();
		}
		
		// get JSON data from the core/data/objects/list.php script output
		$result = json_decode(get_include_contents('packages/core/data/objects/list.php'), true);
		
		// check JSON validity
		if(!is_null($result)) {
			// get specified view	
			$filename = 'packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/views/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.'.$params['view'].'.html';
			$view = get_include_contents($filename);
			
			// get the right sub dataset
			$data = array();
			foreach($result['rows'] as $row) $data[] = $row['cell'];

			// use regular expression to locate all 'li' tags in the view
			$tags = array();
			preg_match_all("/<li([^>]*)>.*<\/li>/iU", $view, $matches, PREG_OFFSET_CAPTURE);
			for($i = 0, $j = count($matches[1]); $i < $j; ++$i) {
				// get tag attributes
				$attributes = array();
				$args = explode(' ', ltrim($matches[1][$i][0]));
				foreach($args as $arg) {
					if(!strlen($arg)) continue;
					list($attribute, $value) = explode('=', $arg);
					$attributes[$attribute] = str_replace('"', '', $value);
				}
				$tags[] = $attributes;
			}
			
			load_class('utils/FPDF');	
			define('FPDF_FONTPATH', 'files/font');
			
			class PDF extends FPDF {			
				function Footer() {
					$this->SetY(-15);
					$this->SetFont('Arial','I',8);
					$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
				}
			}
			
			$pdf = new PDF();
			$pdf->AliasNbPages();
			// $pdf->SetLeftMargin(22.0);
			$pdf->SetFont('Arial','B',14);
			$pdf->AddPage();
			$pdf->SetFillColor(100,150,250);$pdf->SetTextColor(255);$pdf->SetDrawColor(128,0,0);
			$pdf->SetLineWidth(.3);

			$widths = array();			
			// compute elements width
			foreach($tags as $attributes) {				
				$field = $attributes['id'];
				$sheet_width = $pdf->w - $pdf->lMargin - $pdf->rMargin;
				$width = (((int) str_replace('%', '', $attributes['width']))/100) * $sheet_width;
				$index = array_search($field, $params['fields']);
				$widths[$index] = $width;				
			}
				
			// check if there is a matching i18n file
			$filename = 'packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/i18n/'.$params['ui'].'/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.json';
			$result = json_decode(get_include_contents($filename), true);
			if(!is_null($result)) {
				foreach($params['fields'] as $key => $field ) {
					if(isset($result['model'][$field])) $params['fields'][$key] = $result['model'][$field]['label'];
				}				
			}

			// header of table					
			for($i = 0, $j = count($params['fields']);$i < $j; ++$i) $pdf->Cell($widths[$i],7,$params['fields'][$i],1,0,'C',true);
			$pdf->Ln();
			// restore colors and font
			$pdf->SetFillColor(224,235,255);$pdf->SetTextColor(0);$pdf->SetFont('');
			// data
			$fill = false;
			foreach($data as $row) {
				for($i = 0, $j = count($params['fields']); $i < $j; ++$i) $pdf->Cell($widths[$i],6,$row[$i],'LR',0,'L',$fill);
				$pdf->Ln();
				$fill = !$fill;
			}
			// bottom of table
			$pdf->Cell(array_sum($widths),0,'','T');
			$pdf->Output('report.pdf', 'I');			
		}
		break;
}