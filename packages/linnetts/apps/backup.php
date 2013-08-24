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
* file: packages/linnetts/apps/backup.php
*
* Backups the whole database.
*
*/

// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// remember to set $_SESSION[' PMA_token '] = 1 in phpmyadmin/libraries/session.inc.php (after line 90)

$params = array(
	'token'								=> '1',
	'db' 								=> 'linnetts',
	'export_type'						=> 'database',
	'what'								=> 'sql',
	'table_select[0]' 					=> 'core_group',
	'table_select[1]'					=> 'core_permission',
	'table_select[2]'					=> 'core_rel_group_user',
	'table_select[3]'					=> 'core_urlresolver',
	'table_select[4]'					=> 'core_user',
	'table_select[5]'					=> 'core_version',
	'table_select[6]'					=> 'linnetts_customer',
	'table_select[7]'					=> 'linnetts_invoice',
	'table_select[8]'					=> 'linnetts_job',
	'table_select[9]'					=> 'linnetts_rel_workday_job',
	'table_select[10]'					=> 'linnetts_task',
	'table_select[11]'					=> 'linnetts_workday',
	'codegen_data'						=>	'',
	'codegen_format'					=>	'0',
	'csv_separator'						=>	';',
	'csv_enclosed'						=>	'"',
	'csv_escaped'						=>	'\\',
	'csv_terminated'					=>	'AUTO',
	'csv_null'							=>	'NULL',
	'csv_data'							=>	'',
	'excel_null'						=>	'NULL',
	'excel_edition'						=>	'win',
	'excel_data'						=>	'',
	'htmlexcel_null'					=>	'NULL',
	'htmlexcel_data'					=>	'',
	'htmlword_structure'				=>	'something',
	'htmlword_data'						=>	'something',
	'htmlword_null'						=>	'NULL',
	'latex_caption'						=>	'something',
	'latex_structure'					=>	'something',
	'latex_structure_caption'			=>	'__TABLE__ structure',
	'latex_structure_continued_caption'	=>	'__TABLE__ structure (next)',
	'latex_structure_label'				=>	'tab:__TABLE__-structure',
	'latex_comments'					=>	'something',
	'latex_data'						=>	'something',
	'latex_columns'						=>	'something',
	'latex_data_caption'				=>	'__TABLE__ content',
	'latex_data_continued_caption'		=>	'__TABLE__ content (next)',
	'latex_data_label'					=>	'tab:__TABLE__-data',
	'latex_null'						=>	'\textit{NULL}',
	'ods_null'							=>	'NULL',
	'ods_data'							=>	'',
	'odt_structure'						=>	'something',
	'odt_comments'						=>	'something',
	'odt_data'							=>	'something',
	'odt_columns'						=>	'something',
	'odt_null'							=>	'NULL',
	'pdf_report_title'					=>	'',
	'pdf_data'							=>	'1',
	'sql_header_comment'				=>	'',
	'sql_include_comments'				=>	'something',
	'sql_compatibility'					=>	'NONE',
	'sql_if_not_exists'					=>	'something',
	'sql_auto_increment'				=>	'something',
	'sql_backquotes'					=>	'something',
	'sql_data'							=>	'something',
	'sql_columns'						=>	'something',
	'sql_extended'						=>	'something',
	'sql_max_query_size'				=>	'50000',
	'sql_hex_for_blob'					=>	'something',
	'sql_type'							=>	'INSERT',
	'texytext_structure'				=>	'something',
	'texytext_data'						=>	'something',
	'texytext_null'						=>	'NULL',
	'xml_data'							=>	'',
	'yaml_data'							=>	'',
	'asfile'							=>	'sendit',
	'filename_template'					=>	'__DB__',
	'remember_template'					=>	'on',
	'compression'						=>	'zip'
);

$html = '<html><body>';
$html .= "<script>window.onload=function(){document.backform.submit();}</script>";
$html .= "<form name ='backform' action='phpmyadmin/export.php' method='post'>";
foreach($params as $key => $value) $html .= "<input type='hidden' name='$key' value='$value' />";
$html .= "</form>";
$html .= '</html></body>';
echo $html;