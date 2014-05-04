<?php

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/FSManipulator');

// force silent mode
set_silent(true);

check_params(array('res_id'));
$params = get_params(array('res_id'=>null, 'mode'=>'view', 'lang'=>'fr'));

$resource_values = &browse('icway\Resource', array($params['res_id']), array('title', 'filename', 'type', 'size', 'content'));

// disable compression whatever default option is
ini_set('zlib.output_compression','0');

if ($params['mode']=='download') {
	// tell browser to download resource
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=".FSManipulator::getSanitizedName($resource_values[$params['res_id']]['filename']).";");
    header("Content-Transfer-Encoding: binary");
}
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: ".$resource_values[$params['res_id']]['type']);
// todo: ? header('Content-Type: application/x-download');
header("Content-Length: ".$resource_values[$params['res_id']]['size']);

$resource_content = '';

if(BINARY_STORAGE_MODE == 'DB') $resource_content = $resource_values[$params['res_id']]['content'];
else if(BINARY_STORAGE_MODE == 'FS') {
	$resource_content = file_get_contents($resource_values[$params['res_id']]['content']);
}
print($resource_content);