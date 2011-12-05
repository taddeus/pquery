<?php

include_once '../../simpletest/autorun.php';
include_once 'config.php';
include_once '../pquery.php';

function is_test_file($filename) {
	return preg_match('/^test_\w+\.php$/', $filename);
}

foreach( array_filter(scandir('.'), 'is_test_file') as $file )
	include_once $file;

?>