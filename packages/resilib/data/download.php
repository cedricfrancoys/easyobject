<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

/*
	This script checks if the specified document is available from its original location (URL).
	If so, it then redirects to the original resource.
	Otherwise, it returns a local copy of the document.
*/

// force silent mode
set_silent(true);

// document id is a mandatory parameter
check_params(array('id'));
$params = get_params(array('id'=>null));

//$documents_ids = search('resilib\Document');
//$documents = browse('resilib\Document', $documents_ids, array('original_url', 'type'));

$documents = browse('resilib\Document', array($params['id']), array('original_url', 'type'));
$document = $documents[$params['id']];

// check that a document by the specified id does exist

// pseudo infinite loop
while(true) {
	$document_url = $document['original_url'];
	$document_type = $document['type'];

	// 1) check the given URL syntax with regex
	// we skip this step since we assume URL syntax has been checked previously (validation rule in Document.class.php)
	
	// 2) get HTTP headers
	if(!($headers = get_headers($document_url, 1))) {
		// print('unable to get remote content (non-existent website)');
		break;
	}

	$response_code = substr($headers[0], 9, 3);
	if($response_code != 200) {
		//print("server error ($response_code)");
		break;
	}

	// deal with multiple content-types (in case of a 3xx redirection)
	if(is_array($headers['Content-Type']))
		$content_type = $headers['Content-Type'][0];
	else $content_type = $headers['Content-Type'];
	// deal with multi-parts content-types
	if(strpos($content_type, ';') !== false) {
		$content_type = explode(';',$content_type);
		$content_type = $content_type[0];
	}
	
	if($content_type != $document_type) {
		//print("mismatch document type ($content_type)");
		break;
	}

	// if we reach this code, everything went well
	// redirect to the original URL 
	header("Location: $document_url");
	exit;	
}


// we were unable to get the content at its original plase, so we use the local version (resilink)
load_class('utils/FSManipulator');
$documents = browse('resilib\Document', array($params['id']), array('size', 'type', 'filename', 'content'));



$resource_content = false;

if(BINARY_STORAGE_MODE == 'DB') $resource_content = $documents[$params['id']]['content'];
else if(BINARY_STORAGE_MODE == 'FS') {
	$resource_content = file_get_contents($documents[$params['id']]['content']);
}

if($resource_content === false) die('unable to provide content');

// disable compression whatever the default option 
ini_set('zlib.output_compression','0');

// update/send the header
header("Content-Description: File Transfer");	// force download
header("Content-Disposition: attachment; filename=".FSManipulator::getSanitizedName($documents[$params['id']]['filename']).";");
header("Content-Transfer-Encoding: binary");
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: ".$documents[$params['id']]['type']);
header("Content-Length: ".$documents[$params['id']]['size']);
// output file content
print($resource_content);