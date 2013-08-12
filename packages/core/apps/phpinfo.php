<?php
// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

$params = get_params(array(
							'object_class'		=> null, 
							'id'				=> 0, 
							'view'				=> 'form.default',
							'output'			=> 'html',
							'fields'			=> null, 
							'domain'			=> array(array()), 
							'page'				=> 1, 
							'rp'				=> 10, 
							'sortname'			=> 'id', 
							'sortorder'			=> 'asc', 
							'records'			=> null, 
							'mode'				=> null, 
							'lang'				=> DEFAULT_LANG,
							'ui'				=> SESSION_LANG_UI		
					));

$filename =  'packages/'.strtolower(ObjectManager::getObjectPackageName($params['object_class'])).'/views/'.ucfirst(ObjectManager::getObjectName($params['object_class'])).'.'.$params['view'].'.html';
include($filename);

// echo phpinfo();