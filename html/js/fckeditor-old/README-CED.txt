fichier editor/filemanager/browser/default/connectors/php/config.php
--------------------------------------------------------------------
// répertoire racine du site (si pas root)
$Config['UserFilesPath'] = '/tests/UserFiles/'; 

// path du répertoire sur le serveur
$Config['UserFilesAbsolutePath'] = 'D:\\websites_temp\\tests\\UserFiles' ;

$Config['Enabled'] = true ;


fichier /editor/filemanager/upload/php/config.php
--------------------------------------------------------------------
// répertoire racine du site (si pas root)
$Config['UserFilesPath'] = '/tests/UserFiles/';

$Config['Enabled'] = true ;


fichier editor/filemanager/browser/default/connectors/php/connector.php
-----------------------------------------------------------------------
// remplacer la ligne 
$GLOBALS["UserFilesDirectory"] = GetRootPath() . $GLOBALS["UserFilesPath"] ;
// par
$GLOBALS['UserFilesDirectory'] = $GLOBALS['UserFilesPath'];


fichier fckconfig.cfg
---------------------
FCKConfig.ImageBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php' ;

FCKConfig.LinkBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=connectors/php/connector.php' ;



fichier fckconfig.js
--------------------
Add these lines :


/* ------------------------ */
/* lines added for easy-CMS */

FCKConfig.ToolbarSets["ecms_Advanced"] = [
	['Bold','Italic','Underline','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Cut','Copy','Paste','PasteText','PasteWord'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	'/',
	['TextColor','FontSize','StrikeThrough','-','Subscript','Superscript'],
	['OrderedList','UnorderedList','-','Outdent','Indent','-','Image','Link','Unlink','Anchor'],
	'/',
	['Source','-','FitWindow']
];

FCKConfig.ToolbarSets["ecms_Simple"] = [
	['Bold','Italic','Underline','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Cut','Copy','Paste','-','Undo','Redo'],
	'/',
	['TextColor','FontSize','-','OrderedList','UnorderedList','-','Outdent','Indent']
];

/* end of ecms added lines */
/* ----------------------- */





fichier editor/dialog/fck_link/fck_link.js
------------------------------------------

Dans la fonction "BrowseServer()"

// remplacer la ligne 
OpenFileBrowser( FCKConfig.LinkBrowserURL, FCKConfig.LinkBrowserWindowWidth, FCKConfig.LinkBrowserWindowHeight ) ;
// par (exemple)
win = window.open('test.html','mywin','left=20,top=20,width=500,height=500,toolbar=1,resizable=0');


	exemple test.html
	-----------------


	<script>
	function my_submit() {
		window.opener.document.getElementById('txtUrl').value = 'index.php?pid=213';
		window.close();
	}
	</script>


	<body>
		<input type="button" onclick="javascript:my_submit();" value="ok">
	</body>


