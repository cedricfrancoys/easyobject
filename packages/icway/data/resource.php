<?php

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

check_params(array('res_id'));
$params = get_params(array('res_id'=>null, 'mode'=>'view', 'lang'=>'fr'));


$resource_values = &browse('icway\Section', array($params['res_id']), array('title', 'filename', 'type', 'size', 'content'));

// disable compression whatever default option is
ini_set('zlib.output_compression','0');

if ($params['mode']=='download') {
	// tell browser to download resource
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=".$resource_values[$params['res_id']]['filename'].";");
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