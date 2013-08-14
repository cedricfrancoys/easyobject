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
// set_silent(true);


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


/**
*
* Get some data by including files (using a different scope)
* (used to retrieve output from another script)
*/
function get_include_contents($filename) {
	if(!file_exists($filename)) return '';
	ob_start();
	include $filename;
	return ob_get_clean();
}

/**
*
* Retrieves all require information for displaying a list
*/
function get_list_data() {
	global $params;
	
	$result =	array(
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
		$index = array_search($attributes['id'], $params['fields']);
		// ensure specified field is in the fields list
		if($index !== false) {
			$result['fields'][] = $attributes['id'];
			$result['widths'][] = (int) str_replace('%', '', $attributes['width']);
		}
	}
	
	// 2) retrieve data (rows)
	// retieve and store the fields order (it may differs from the $params['fields'] order)
	$indexes = array();
	for($i = 0, $j = count($params['fields']); $i < $j; ++$i) {
		$indexes[] = array_search($params['fields'][$i], $result['fields']);
	}
	// get JSON data from the core/data/objects/list.php script output
	$json = json_decode(get_include_contents('packages/core/data/objects/list.php'), true);	
	// check JSON validity
	if(is_null($json)) return false;
	// get the right sub dataset
	foreach($json['rows'] as $row) {
		$data = array();
		for($i = 0, $j = count($row['cell']); $i < $j; ++$i) {
			$data[$indexes[$i]] = $row['cell'][$i];
		}		
		$result['rows'][] = $data;
	}
	
	// 3) retrieve fields labels (if any)
	// by default, set fieldnames as labels
	$result['labels'] = $result['fields'];
	// check if there is a matching i18n file	
	$json = json_decode(get_include_contents('packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/i18n/'.$params['ui'].'/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.json'), true);
	if(!is_null($json)) {
		foreach($result['fields'] as $key => $field ) {
			if(isset($json['model'][$field]) && isset($json['model'][$field]['label'])) $result['labels'][$key] = $json['model'][$field]['label'];
		}				
	}

	return $result;
}




// output result
switch($params['output']) {
/**
*	HTML output
*
*/
	case 'html':
		load_class('utils/HtmlWrapper');	
		$html = new HtmlWrapper();
		$html->addCSSFile('html/css/easyobject/base.css');
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
	case 'csv':
		// UTF-8 BOM	
/*		
		$csv = chr(239).chr(187).chr(191).$csv;
		$len = strlen($csv);

		//Begin writing headers
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Type: application/vnd.ms-excel");

		//Force the download
		$filename = "";
		$header = "Content-Disposition: attachment; filename=export.csv;";
		header($header);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".$len);
		print($csv);	
*/		
		break;
	case 'xls':
// todo : continue	
		load_class('utils/PHPExcel');

/*		
		// Create a new PHPExcel object
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getActiveSheet()->setTitle('List of Cities');

		// Loop through the result set
		$rowNumber = 1;
		while ($row = mysql_fetch_row($result)) {
		   $col = 'A';
		   foreach($row as $cell) {
			  $objPHPExcel->getActiveSheet()->setCellValue($col.$rowNumber,$cell);
			  $col++;
		   }
		   $rowNumber++;
		}
		// Save as an Excel BIFF (xls) file
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="myFile.xls"');
		header('Cache-Control: max-age=0');

		$objWriter->save('php://output');	
*/		
		break;
	case 'pdf':
/**
*	PDF output
*
*/
		list($view, $name) = explode(".", $params['view']);
		
		switch($view) {
			case 'list':
				$list_data = get_list_data();
				
				if(is_array($list_data)) {			
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
					$pdf->SetFont('Arial','B',14);
					$pdf->AddPage();
					$pdf->SetFillColor(100,150,250);$pdf->SetTextColor(255);$pdf->SetDrawColor(128,0,0);
					$pdf->SetLineWidth(.3);
					
					// adjust widths to occupy all available space
					$page_width = $pdf->w - $pdf->lMargin - $pdf->rMargin;
					for($i = 0, $j = count($list_data['fields']);$i < $j; ++$i) $list_data['widths'][$i] = $page_width * $list_data['widths'][$i]/100;

					// header of table					
					for($i = 0, $j = count($list_data['fields']);$i < $j; ++$i) $pdf->Cell($list_data['widths'][$i],7,$list_data['labels'][$i],1,0,'C',true);
					$pdf->Ln();
					// restore colors and font
					$pdf->SetFillColor(224,235,255);$pdf->SetTextColor(0);$pdf->SetFont('');
					// data
					$fill = false;
					foreach($list_data['rows'] as $row) {
						for($i = 0, $j = count($list_data['fields']); $i < $j; ++$i) $pdf->Cell($list_data['widths'][$i],6,$row[$i],'LR',0,'L',$fill);
						$pdf->Ln();
						$fill = !$fill;
					}
					// bottom of table
					$pdf->Cell(array_sum($list_data['widths']),0,'','T');
					$pdf->Output('export.pdf', 'I');			
				break;
			case 'form':
				break;
		}

			
		}
		break;
}