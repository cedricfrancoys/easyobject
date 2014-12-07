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

*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('orm/ValidationTests') or die(__FILE__.' unable to load mandatory class : orm/ValidationTests');

$validationTests = new ValidationTests;
$validation_tests = $validationTests();

$test_keys = array_keys($validation_tests);
$total_tests = count($test_keys);
$first_test_id = $test_keys[0];
$last_test_id = $test_keys[($total_tests-1)];
if(isset($_REQUEST['auto'])) $_SESSION['auto'] = $_REQUEST['auto'];
if(isset($_REQUEST['test_id'])) $test_id = $_REQUEST['test_id'];
else {
	$test_id = $first_test_id;
	$_SESSION['results_array'] = array();
}
$test_no = 1;
$prev_test_id = $test_id;
foreach($test_keys as $key) {
	if($key == $test_id) break;
	$prev_test_id = $key;
	++$test_no;
}
$next_test_id = current($test_keys);


if(isset($_REQUEST['previous_result'])) $_SESSION['results_array'][$prev_test_id] = $_REQUEST['previous_result'];

if($test_no > $total_tests || !isset($validation_tests[$test_id])) {
	if(!isset($_SESSION['results_array'])) echo "unexisting test id";
	else {
		// show result grid
		echo "<table border='1'><tr><td width='50'>Test id</td><td width='600'>description</td><td>expected</td><td>returned</td><td>result</td></tr>";
		foreach($validation_tests as $test_id => $test) {
			$expected = sprintf("%s", $test['expected_result']);
			$returned = $_SESSION['results_array'][$test_id];
			$result = ($expected == $returned)?'<span style="color: green;">OK</span>':'<span style="color: red;">FAIL</span>';
			echo "<tr><td>".$test_id."</td><td>".$test['description']."</td><td>".$expected."</td><td>".$returned."</td><td>".$result."</td></tr>";
		}
	}
	exit();
}


echo "<html>\n<head>\n  <script type='text/javascript' src='html/js/jquery-1.7.1.min.js'></script>\n";

if(isset($_SESSION['auto']) && $_SESSION['auto']) {
	print("
	<script>
		$(document).ready(function() {
			var next_id = $('#test_id_next').val();
			var result = $('#result_value').val();
			window.location='?show=core_validate&test_id='+next_id+'&previous_result='+result;
		});
	</script>");
}

$url = config\FCLib::get_url();
echo "</head>
<body>
<form action='$url' method='get'>
	<input type='hidden' id='result_value' name='result_value' value='0' />
	<input type='hidden' id='show' name='show' value='core_validate' />
	<button id='test_id_first' name='test_id' type='submit' value='$first_test_id'>&lt;&lt; first</button>
	<button id='test_id_prev' name='test_id' type='submit' value='$prev_test_id'>&lt; prev</button>
	$test_no / $total_tests
	<button id='test_id_next' name='test_id' type='submit' value='$next_test_id'>next &gt;</button>
	<button id='test_id_last' name='test_id' type='submit' value='$last_test_id'>last &gt;&gt;</button>
</form>
<br />\n";

print("Test id : $test_id. ".$validation_tests[$test_id]['description']." (expected result: {$validation_tests[$test_id]['expected_result']})<br /><br />\n");

echo '<pre>';
$result = call_user_func($validation_tests[$test_id]['test']);
print('Result ('.count($result)." values fetched):<br />\n");
var_dump($result);
echo '</pre>';

print("<script>$('#result_value').val('{$result}');</script>");