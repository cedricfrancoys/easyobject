<?php

// page 'search'

$renderer['inline_script'] = function ($params) {
	return "
		$(document).ready(function(){
			// google custom search
			$.getScript('http://www.google.com/cse/cse.js?cx='+ '004967614553816060821:aqmxxfj88ue')
			.done(function() {})
			.fail(function() {});
		});
	";
};