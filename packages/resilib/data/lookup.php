<?php
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

// force silent mode
set_silent(true);

$params = get_params(array('language'=>null, 'categories_ids'=>null, 'author'=>null, 'title'=>null));

// 1) find all involved (sub)categories
$sum_categories_ids = array();
$categories_ids = (array) $params['categories_ids'];
while(!empty($categories_ids)) {
	$sum_categories_ids = array_merge($sum_categories_ids, $categories_ids);
	$categories_ids = search('resilib\Category', array(array(array('parent_id', 'in', $categories_ids))));	
}

// 2) get all related documents (inclusive search: we fetch docs belonging to, at least, one of the specified categories)
$documents_ids = search('resilib\Document', array(array(array('categories_ids', 'contains', $sum_categories_ids))));

// if there is no match, then add a non-existent document identifier
if(empty($documents_ids)) $documents_ids[] = 0;

// 3) build domain (simple serie of conjunctions)
$domain = array();
if(isset($params['language']))
	$domain[] = array('language', '=', $params['language']);
if(isset($params['author']))
	$domain[] = array('author', 'ilike', '%'.$params['author'].'%');
if(isset($params['title']))
	$domain[] = array('title', 'ilike', '%'.$params['title'].'%');
if(!empty($sum_categories_ids))
	$domain[] = array('id', 'in', $documents_ids);

// 4) request the related documents ids (sorted by title)
$result_ids = search('resilib\Document', array($domain), 'title');
$documents = browse('resilib\Document', $result_ids, array('title', 'author', 'categories_ids', 'language', 'last_update'));

// 5) output json result
header('Content-type: text/html; charset=UTF-8');
echo json_encode($documents);