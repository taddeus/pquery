<?php

include_once '../../simpletest/autorun.php';
include_once '../pquery.config.php';
include_once PQUERY_ROOT.'pquery.php';

function is_test_file($filename) {
	return preg_match('/^test_\w+\.php$/', $filename);
}

foreach( array_filter(scandir('.'), 'is_test_file') as $file )
	include_once $file;

/*include_once '../../debug.php';
include_once '../pquery.php';*/
//__p::require_plugins('array', 'sql', 'template');

// Array test
/*$a = _p(range(0, 10));

while( !$a->is_empty() ) {
	debug($a->pop(), $a->reverse()->pop());
}*/

// SQL test
/*$sql = _sql("select * from posts where slug = '[slug]'")
		->set(array('slug' => 'contact'));
$results = $sql->fetch_all('object');
$results = _arr($results);

debug($results);

__tpl::set_root('templates', false);
$tpl = _tpl('test.tpl');

$test1 = $tpl->data->add('test1', array('var' => 'some-variable'));
$tpl->data->add('test1', array('var' => 'some-other-variable'));
$test1->add('test2');
$tpl->data->add('test3');
debug($tpl->parse());*/

?>