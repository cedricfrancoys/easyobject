<?php

// page 'evolution'


$renderer['content'] = function ($params) {
	$values = &browse('icway\Page', array(15), array('content'), $params['lang']);
	return $values[15]['content'];
};
