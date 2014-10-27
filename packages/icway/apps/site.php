<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

$params = announce(	
	array(	
		'description'	=>	"Returns the values of the specified fields for the given objects ids.",
		'params' 		=>	array(
								'page_id'	=> array(
													'description' => 'The page to display.',
													'type' => 'string', 
													'default' => 1
													),
								'post_id'		=> array(
													'description' => 'The post to display in case we request a blog entry.',
													'type' => 'integer', 
													'default' => 1
													),
								'cat_id'		=> array(
													'description' => 'Identifier of the category associated to the post.',
													'type' => 'integer', 
													'default' => null
													),													
								'lang'			=> array(
													'description '=> 'Language in which to display content.',
													'type' => 'string', 
													'default' => DEFAULT_LANG
													)
							)
	)
);

// lang param was not in the URL: use previously chosen or default
isset($_SESSION['icway_lang']) or $_SESSION['icway_lang'] = 'fr';
if(is_null($params['lang'])) $params['lang'] = $_SESSION['LANG'] = $_SESSION['icway_lang'];
else $_SESSION['icway_lang'] = $params['lang'];

//we use the cached version of the content
if($params['page_id'] != 5) {
	$values = &browse('icway\Page', array($params['page_id']), array('html'), $params['lang']);
	print($values[$params['page_id']]['html']);
}
// blog page is dynamic (might change based on cat_id)
else {
	include('packages/icway/data/page-html.php');
}