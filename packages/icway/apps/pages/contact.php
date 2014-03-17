<?php

// page 'contact'

$renderer['inline_script'] = function ($params) {
	$confirm_txt = str_replace("\n", '\n', addslashes(get_translation('subscribe_confirm', $params['lang'])));
	return "
		function submit_form() {
			var response = $.post('index.php?do=icway_add-subscriber', $('#submit_form').serialize(), function () {});
			setTimeout(function(){
				alert('{$confirm_txt}');
			}, 500);
		}
	";
};
