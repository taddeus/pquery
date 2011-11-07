<?php

include_once '../../simpletest/autorun.php';
include_once '../pquery.config.php';
include_once PQUERY_ROOT.'pquery.php';

function is_test_file($filename) {
	return preg_match('/^test_\w+\.php$/', $filename);
}

foreach( array_filter(scandir('.'), 'is_test_file') as $file )
	include_once $file;

//include_once '../pquery.php';
//__p::require_plugins('array', 'sql', 'template');

// SQL test
/*include_once '../../debug.php';
$sql = _sql("select * from posts where slug = '[slug]'")
		->set(array('slug' => 'contact'));
$results = $sql->fetch_all('object');
$results = _arr($results);

debug($results);*/

?>