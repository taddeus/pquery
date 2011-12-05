<?php

__p::load_plugin('sql');
include '../../debug.php';

class pQuerySqlTest extends UnitTestCase {
	function __construct() {
		parent::__construct('pQuery MySQL plugin');
	}
	
	function setUp() {
		__sql::set_login_data('localhost', 'root', '', 'pquery_test');
	}
	
	function test_set_login_data() {
		__sql::set_login_data('a', 'b', 'c', 'd');
		
		$this->assertEqual(__sql::$login_data['host'], 'a');
		$this->assertEqual(__sql::$login_data['username'], 'b');
		$this->assertEqual(__sql::$login_data['password'], 'c');
		$this->assertEqual(__sql::$login_data['dbname'], 'd');
	}
	
	function test_no_login_data() {
		$this->expectException('pQueryException');
		__sql::$login_data = array();
		__sql::assert_login_data_exist();
	}
	
	function test_query_getter() {
		$sql = _sql('foobar');
		$this->assertEqual($sql->variable, 'foobar');
		$this->assertEqual($sql->query, 'foobar');
	}
	
	function test_variable_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => 'test1'));
		$this->assertEqual($sql->query, "select id from foo where bar = 'test1'");
	}
	
	function test_unescaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set_unescaped(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertEqual($sql->query, "select id from foo where bar = 'select id from foo where bar = 'test1''");
	}
	
	function test_escaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertNotEqual($sql->query, "select id from foo where bar = 'select id from foo where bar = 'test1''");
	}
	
	function test_select_simple() {
		$sql = _sql("select bar from foo where id = 1");
		$results = $sql->fetch_all('object');
		$this->assertEqual($results[0]->bar, 'test1');
	}
}

?>