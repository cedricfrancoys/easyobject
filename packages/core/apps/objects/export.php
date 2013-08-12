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
* file: packages/core/apps/objects/export.php
*
* Displays an edition form matching the specified view and class.
*
*/

// todo: this is very similar to the packages/core/data/objects/list.php script ... maybe we could merge something?


// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/FPDF');

// force silent mode (debug output would corrupt json data)
set_silent(true);

// ensure required parameters have been transmitted
check_params(array('object_class'));

// 1) get parameters values
$params = get_params(array('object_class'=>null, 'fields'=>null, 'domain'=>null, 'page'=>1, 'rp'=>10, 'sortname'=>'id', 'sortorder'=>'asc', 'records'=>null, 'mode'=>null, 'lang'=>DEFAULT_LANG));
$object_class = $params['object_class'];
$page 	= $params['page']; 		// the requested page
$limit	= $params['rp']; 		// how many rows we want to have into the grid
$order	= $params['sortname'];	// index column - i.e. user click to sort
$sort	= $params['sortorder'];	// the direction  - i.e. 'asc' or 'desc'
$domain = $params['domain'];
$lang	= $params['lang'];

if($params['fields'] && !is_array($params['fields'])) $fields = explode(',', $params['fields']);
else $fields = $params['fields'];

// todo: in this case we NEED the names of the fields so, if non given, we should retrieve them from the object schema (only simple fields)

if(is_null($domain)) $domain = array(array());

$start = ($page-1) * $limit;
if($start < 0) $start = 0;


// 2) search and browse
if(empty($params['records'])) {
	// This way we search all possible results : that might result in a quite long process if the tables are big
	// but it is the only way to determine the number of results,
	// so we do it only when the number of results is unknown
	if(OPERATION_MODE == 'standalone') {
		// if we are in standalone mode, however, the DBMS might offer some options to do this quicker (for example with MySQL FOUND_ROWS())
		// first ensure objectManger is instanciated
		$om = &ObjectManager::getInstance();
		// get an instance of the DBMS manipulator
		$db = &DBConnection::getInstance();
		$ids = search($object_class, $domain, $order, $sort, $start, $limit, $lang);
		// use the getAffectedRows method to get the total number of reords
		$count_ids = $db->getAffectedRows();
		$list = &browse($object_class, $ids, $fields, $lang);
	}
	else {
		$ids = search($object_class, $domain, $order, $sort, 0, '', $lang);
		$list = &browse($object_class, array_slice($ids, $start , $limit, true), $fields, $lang);
		$count_ids = count($ids);
	}
}
else {
	// This is a faster way to do the search but it requires the number of total results
	$ids = search($object_class, $domain, $order, $sort, $start, $limit, $lang);
	$list = &browse($object_class, $ids, $fields, $lang);
	$count_ids = $params['records'];
}


// 3) generate output
class PDF extends FPDF {
	function GenerateTable($header, $data)	{
		$this->SetFillColor(255,0,0);
		$this->SetTextColor(255);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');
		// header
		$w = array(40, 35, 45, 40);
		for($i = 0, $j = count($header);$i < $j; ++$i) $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
		$this->Ln();
		// restore colors and font
		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont('');
		// add data
		$fill = false;
		foreach($data as $row) {
			for($i = 0, $j = count($header); $i < $j; ++$i) $this->Cell($w[$i],6,$row[$i],'LR',0,'L',$fill);
			$this->Ln();
			$fill = !$fill;
		}
		// Bottom line
		$this->Cell(array_sum($w),0,'','T');
	}
}



$data = array();
foreach($list as $id => $object_fields) {
	$data[] = array_values($object_fields);
}
define('FPDF_FONTPATH', 'files/font');
$pdf = new PDF();

$pdf->SetLeftMargin(22.0);

$pdf->SetFont('Arial','',14);
$pdf->AddPage();
$pdf->GenerateTable($fields,$data);
$pdf->Output('report.pdf', 'I');