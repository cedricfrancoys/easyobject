<?php
	include_once("fckeditor.php");

	if (isset($_POST)) {
		$postArray = &$_POST ;

		foreach ( $postArray as $sForm => $value ) {
			$postedValue = htmlspecialchars( stripslashes( $value ) ) ;

			echo $sForm." = ".$postedValue."<br />\n";
		}
	}


		$oFCKeditor = new FCKeditor('text', '300', '500') ;
		//$oFCKeditor->BasePath = '';
		//$oFCKeditor->ToolbarSet	= 'ecms_Advanced' ;
		$oFCKeditor->ToolbarSet	= 'ecms_Simple' ;
		$oFCKeditor->Width = '500';
		$oFCKeditor->Height = '500';

		$oFCKeditor->Value = 'Default text in editor';
		$html = $oFCKeditor->CreateHtml();

		echo "<html><body><form action=\"\" method=\"post\">";
		echo $html;
		echo "<input type=\"submit\" value=\"submit\"></form>";
		echo "<script>text.FCK.FCKFitWindow();</script>";
		echo "</body></html>";
?>