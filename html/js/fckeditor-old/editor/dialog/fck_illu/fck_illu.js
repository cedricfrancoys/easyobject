/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 *
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 *
 * For further information visit:
 * 		http://www.fckeditor.net/
 *
 * "Support Open Source software. What about a donation today?"
 *
 * File Name: fck_ref.js
 * 	Scripts related to the Ref dialog window (see fck_ref.html).
 *
 * File Authors:
 * 		cedric francoys
 */


/**
*	Dialog Window opening
*
*/
function openWindow(url, width, height, offsetX, offsetY) {
  var top  = ((screen.height-height)/2) + offsetX;
  var left = ((screen.width-width)/2) + offsetY;
  window.open(url,'','top='+top+',left='+left+',width='+width+',height='+height+',scrollbars=no,menubar=no,resizable=yes,toolbar=no,location=no,status=no');
}

var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;

var article_id = 0;

//#### Dialog Tabs

// Set the dialog tabs.
window.parent.AddTab( 'Info', FCKLang.DlgLnkInfoTab ) ;



//#### Initialization Code

// oLink: The actual selected link in the editor.
var oLink = FCK.Selection.MoveToAncestorNode( 'A' ) ;
if ( oLink ) FCK.Selection.SelectNode( oLink ) ;

window.onload = function() {
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	// Show the initial dialog content.
	GetE('divInfo').style.display = '' ;

	// Load references list for the current article
	// var original_parent_url = window.parent.opener.parent.location.href;
	var window_url = window.location + "";
	window_url = window_url.split('?');
	var original_parent_url = window_url[1] + '?' + window_url[2];

	var params = original_parent_url.split('?');
	var args = params[1].split('&');
	for(i = 0; i < args.length; ++i) {
		row = args[i].split('=');
		key = row[0];
		value = row[1];
		if(key == 'id') {
			article_id = value;
			// make ajax call to reference_get_content for populating 'cmbRefId'
			$.getJSON("../../../../?data=illustration_get_content&article_id=" + value,
				function(data){
					var counter = 0;
					var max_length = 70;
					if(data) {
						$.each(data.items, function(i,item){
							var illu_value = item.title ;
							if(illu_value.length > max_length) illu_value = illu_value.substring(0, max_length);
							else {
								if(item.comment.length > 0) illu_value = illu_value + ' - ' + item.comment;
								if(illu_value.length > max_length) illu_value = illu_value.substring(0, max_length);
							}
							var selection_elem = GetE('cmbRefId');
							selection_elem.options[selection_elem.length] = new Option((++counter) + '. ' + illu_value, item.id);
						});
						// Load the selected ref (if any).
						LoadSelection();
					}
				}
			);
			break;
		}
	}

	// Activate the "OK" button.
	window.parent.SetOkButton( true ) ;
}

function LoadSelection() {
	if(oLink) {
		ref_id_str = GetAttribute(oLink, 'id', null);
		ref_id_array = ref_id_str.split('_');
		if(ref_id_array.length > 1) {
			GetE('cmbRefId').value = ref_id_array[1];
		}
	}
}


//#### The OK button was hit.
function Ok() {
	var sUri;

	// No link selected, so try to create one.
	if ( !oLink ) oLink = oEditor.FCK.CreateElement('a');

	oLink.innerHTML = '<sup><i>illu</i></sup>';

	oLinkId = 'illu_'+ GetE('cmbRefId').value + '_' + (new Date().getTime());
	sUri = 'javascript:openIlluPopup(' + GetE('cmbRefId').value + ',\'' + oLinkId + '\');';

	oEditor.FCKUndo.SaveUndoStep();

	oLink.href = sUri ;
	SetAttribute( oLink, '_fcksavedurl', sUri ) ;

	SetAttribute(oLink, 'id', oLinkId);

	// Select the link
	oEditor.FCKSelection.SelectNode(oLink);

	return true ;
}

function UpdatePreview()
{
}

function CreateIllustration() {
	openWindow('../../../../?action=illustration_edit&id=0&article_id='+article_id,525, 485, 0, 0);
}

function EditIllustration() {
	var illustration_id = GetE('cmbRefId').value;
	openWindow('../../../../?action=illustration_edit&id='+illustration_id+'&article_id='+article_id,525, 485, 0, 0);
}