<?php

include_once '../debug.php';
include_once 'pquery.php';
__p::require_plugins('array', 'sql');

// Array test
/*$a = _p(range(0, 10));

while( !$a->is_empty() ) {
	debug($a->pop(), $a->reverse()->pop());
}*/



echo '<br><br>';

// SQL test
$sql = _sql("select * from posts where slug = '[slug]'")
		->set(array('slug' => 'contact'));
$results = $sql->fetch_all('object');
$results = _arr($results);

debug($results);

?>