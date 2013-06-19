<?php
/**
	This script allows to browse among public albums of a given Picasa user.
	When picking a picture, it tries to tranfer its URL to the parent window (which suposely holds a CKeditor instance).
	
	Note : you can use HTTP instead of HTTPS by modifying the following lines in the file Zend/GData/Photos.php
	(which is useful when you don't have SSL installed on your server)
	
    const PICASA_BASE_URI = 'https://picasaweb.google.com/data';	
    ->	const PICASA_BASE_URI = 'http://picasaweb.google.com/data';	
    
	const PICASA_BASE_FEED_URI = 'https://picasaweb.google.com/data/feed';
	->	const PICASA_BASE_FEED_URI = 'http://picasaweb.google.com/data/feed';	
*/

defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');


check_params(array('CKEditorFuncNum'));
$params = get_params(array('CKEditorFuncNum'=>null, 'album'=>null));

load_class('utils/HtmlWrapper');
load_class('Zend_Gdata_Photos');
load_class('Zend_Gdata_Photos_UserQuery');

$username = 'cedricfrancoys@gmail.com';
$page = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?show=utils_picasa&CKEditorFuncNum=".$_GET['CKEditorFuncNum'];

$html = new HtmlWrapper();
$html->addStyle("
	img {
		margin: 3px 4px 5px;
		border-width: 0px;
	}
	a {
		display: block;
		color: #005596 !important;
		font-size: 9pt;
		font-family: trebuchet ms;
	}
	a:link, a:visited {
		text-decoration: none;
	}
	a:hover {
		text-decoration: underline;
	}  
	.sh1 {
		-moz-box-shadow: 2px 2px 4px rgba(0, 0, 0, .75);
		-webkit-box-shadow: 2px 2px 4px rgba(0, 0, 0, .75);
		-goog-ms-box-shadow: 2px 2px 4px rgba(0, 0, 0, .75);
		box-shadow: 2px 2px 4px rgba(0, 0, 0, .75);
		filter:progid:DXImageTransform.Microsoft.Shadow(color='#000000', Direction=120, Strength=5);
	}
	h1 {
		color: #9B0044 !important;
		font-size: 1.8em;
		font-family: trebuchet ms !important;
	}
");

$html->addScript("
	function select_image(imagePath) {
		var CKEditorFuncNum = {$_GET['CKEditorFuncNum']};
		window.parent.opener.CKEDITOR.tools.callFunction( CKEditorFuncNum, imagePath, '' );
		// self.close();
	}
");



$service = new Zend_Gdata_Photos();

if(!isset($_GET['album'])) {
	try {
		$query = new Zend_Gdata_Photos_UserQuery();
		$query->setUser($username);
		$query->setAccess("public");	
		$userFeed = $service->getUserFeed(null, $query);
		foreach ($userFeed as $entry) {
			if ($entry instanceof Zend_Gdata_Photos_AlbumEntry){
				$thumbnails = $entry->getMediaGroup()->getThumbNail();
				$thumb = $thumbnails[0]->getUrl();			
				$title = $entry->getTitle()->getText();				
				$album_id = $entry->getGphotoId();
				$html->add("<div style='float: left; width: 170px; height: 235px;'> 
				<a href='{$page}&album={$album_id}'>
				<img width='160' height='160' class='sh1' src='{$thumb}' border='0' />{$title}
				</a></div>");
			}
		}
	} 
	catch (Zend_Gdata_App_HttpException $e) {
		echo "Error: ".$e->getMessage()."<br />\n";
		if ($e->getResponse() != null) echo "Body: <br />\n".$e->getResponse()->getBody()."<br />\n"; 
	} 
	catch (Zend_Gdata_App_Exception $e) {
		echo "Error: ".$e->getMessage()."<br />\n"; 
	}
}
else {
	try {
		$query = $service->newAlbumQuery();
		$query->setUser($username);
		$query->setAlbumId($_GET['album']);
		$query->setImgMax("d");
		$query->setThumbsize(160);
		$albumFeed = $service->getAlbumFeed($query);
		foreach ($albumFeed as $entry) {
			if ($entry instanceof Zend_Gdata_Photos_PhotoEntry){
				$mediaGroup = $entry->getMediaGroup();
				$thumbnails = $mediaGroup->getThumbNail();
				$contents = $mediaGroup->getContent();
				$thumb_url = $thumbnails[0]->getUrl();
				$photo_url = $contents[0]->getUrl();
				$html->add("<div style='float: left; width: 170px; height: 170px;'> 
				<a href='#' onclick='javascript:select_image(\"{$photo_url}\");'>
				<img width='160' height='160' class='sh1' src='{$thumb_url}' border='0' />
				</a></div>");
			}
		}
	}
	catch (Zend_Gdata_App_HttpException $e) {
		echo "Error: " . $e->getMessage() . "<br />\n";
		if ($e->getResponse() != null) {
			echo "Body: <br />\n" . $e->getResponse()->getBody() . 
				 "<br />\n"; 
		}
	} 
	catch (Zend_Gdata_App_Exception $e) {
		echo "Error: " . $e->getMessage() . "<br />\n"; 
	}	
}

print($html);