<?php

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

check_params(array('res_id'));
$params = get_params(array('res_id'=>null, 'mode'=>'view', 'lang'=>'fr'));


function sanitize_file_name($filename) {
    $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
	// remove accentuated chars
	$filename = htmlentities($filename, ENT_QUOTES, 'UTF-8');
    $filename = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $filename);
    $filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');
	// remove special chars
    $filename = str_replace($special_chars, '', $filename);
	// replace spaces with dash
    $filename = preg_replace('/[\s-]+/', '-', $filename);
	// trim the end of the string
    $filename = trim($filename, '.-_');
    return $filename;
}

$resource_values = &browse('icway\Resource', array($params['res_id']), array('title', 'filename', 'type', 'size', 'content'));

// disable compression whatever default option is
ini_set('zlib.output_compression','0');

if ($params['mode']=='download') {
	// tell browser to download resource
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=".sanitize_file_name($resource_values[$params['res_id']]['filename']).";");
    header("Content-Transfer-Encoding: binary");
}
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: ".$resource_values[$params['res_id']]['type']);
// ? header('Content-Type: application/x-download');
header("Content-Length: ".$resource_values[$params['res_id']]['size']);

print($resource_values[$params['res_id']]['content']);