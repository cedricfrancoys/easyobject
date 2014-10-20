<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

$params = get_params(array('page_id'=>1, 'post_id'=>1, 'lang'=>null, 'cat_id'=>null));

// lang param was not in the URL: use previously chosen or default
isset($_SESSION['icway_lang']) or $_SESSION['icway_lang'] = 'fr';
if(is_null($params['lang'])) $params['lang'] = $_SESSION['LANG'] = $_SESSION['icway_lang'];
else $_SESSION['icway_lang'] = $params['lang'];


$values = &browse('icway\Page', array($params['page_id']), array('html'), $params['lang']);

print($values[$params['page_id']]['html']);