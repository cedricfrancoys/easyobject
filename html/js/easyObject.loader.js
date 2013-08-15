(function($) {
	/**
	* Dependencies loader
	*/
	(function(files) {
		for(i = 0, j = files.length; i < j; ++i) {
			if($.browser.safari) $.ajax({url:files[i], dataType:'script', async:false, cache: true});
			else if ($.browser.msie) document.write('<script charset="utf-8" type="text/javascript" language="javascript" src="'+files[i]+'"></script>');
			else $('head').append($('<script />').attr('type', 'text/javascript').attr('language', 'javascript').attr('src', files[i]));		
		}
	})([
	'html/js/src/md5.js',
	'html/js/src/jquery.simpletip-1.3.1.js',
	'html/js/src/jquery.noselect-1.1.js',	
	'html/js/src/jquery-ui.timepicker-1.0.1.js',	

	'html/js/date.js',	
	'html/js/jquery.daterangepicker.js',
	
	'html/js/src/easyObject.utils.js',
	'html/js/src/easyObject.grid.js',
	'html/js/src/easyObject.tree.js',	
	'html/js/src/easyObject.dropdownlist.js',
	'html/js/src/easyObject.choice.js',	
	'html/js/src/jquery.inputmask.bundle.min.js',	
	'html/js/src/easyObject.editable.js',
	'html/js/src/easyObject.form.js',	
	'html/js/src/easyObject.api.js'	
	]);
})(jQuery);