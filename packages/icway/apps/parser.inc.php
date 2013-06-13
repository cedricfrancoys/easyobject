<?php

/*
* A small html parser that replaces 'var' tags with their associated content.
*
*/
function decorate_template($template, $decorator) {
	$previous_pos = 0;
	$html = '';
	// use regular expression to locate all 'var' tags in the template
	preg_match_all("/<var([^>]*)>.*<\/var>/iU", $template, $matches, PREG_OFFSET_CAPTURE);
	// replace 'var' tags with their associated content
	for($i = 0, $j = count($matches[1]); $i < $j; ++$i) {
		// 1) get tag attributes
		$attributes = array();
		$args = explode(' ', ltrim($matches[1][$i][0]));
		foreach($args as $arg) {
			if(!strlen($arg)) continue;
			list($attribute, $value) = explode('=', $arg);
			$attributes[$attribute] = str_replace('"', '', $value);
		}
		// 2) get content pointed by var tag, replace tag with content and build resulting html
		$pos = $matches[0][$i][1];
		$len = strlen($matches[0][$i][0]);
		$html .= substr($template, $previous_pos, ($pos-$previous_pos)).$decorator($attributes);
		$previous_pos = $pos + $len;
	}
	// add tailer
	$html .= substr($template, $previous_pos);
	return $html;
}