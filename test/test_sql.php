<?php

__p::load_plugin('sql');
include '../../debug.php';

class pQuerySqlTest extends UnitTestCase {
	function __construct() {
		parent::__construct('pQuery MySQL plugin');
	}
	
	function setUp() {
		
	}
	
	function tearDown() {
		__sql::disconnect();
		__sql::$login_data = array();
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
		__sql::assert_login_data_exist();
	}
	
	function test_query_getter() {
		$sql = _sql('foobar');
		$this->assertEqual($sql->variable, 'foobar');
		$this->assertEqual($sql->query, 'foobar');
	}
	
	function test_variable_query() {
		self::set_login_data();
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
		self::set_login_data();
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertNotEqual($sql->query, "select id from foo where bar = 'select id from foo where bar = 'test1''");
	}
	
	function test_constructor_simple() {
		self::set_login_data();
		$sql = _sql("select id from foo where bar = '[0]'", 'test1');
		$this->assertEqual($sql->query, "select id from foo where bar = 'test1'");
	}
	
	function test_constructor_advanced() {
		self::set_login_data();
		$sql = _sql("[0] [bar] [foo] [2]", '1', array('bar' => '2', 'foo' => '3'), '4');
		$this->assertEqual($sql->query, "1 2 3 4");
	}
	
	function test_select_simple() {
		self::set_login_data();
		$sql = _sql("select bar from foo where id = 1");
		$result = $sql->fetch('object');
		$this->assertEqual($result->bar, 'test1');
		$this->assertIdentical($sql->fetch(), false);
	}
	
	function test_result_count() {
		__sql::set_login_data('localhost', 'root', '', 'pquery_test');
		$sql = _sql("select bar from foo where id in (1, 2)");
		$this->assertEqual($sql->result_count(), 2);
	}
	
	static function set_login_data() {
		__sql::set_login_data('localhost', 'root', '', 'pquery_test');
	}
}

?>