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
	
	function test_num_rows() {
		self::set_login_data();
		$sql = _sql("select bar from foo where id in (1, 2)");
		$this->assertEqual($sql->num_rows(), 2);
	}
	
	function test_parse_constraints_empty() {
		$this->assertIdentical(__sql::parse_constraints(null, false), "1");
	}
	
	function test_parse_constraints_string() {
		$constraints = "foo LIKE '%bar%'";
		$this->assertEqual(__sql::parse_constraints($constraints, false), $constraints);
	}
	
	function test_parse_constraints_simple() {
		$this->assertEqual(__sql::parse_constraints(
			array('id' => 1, 'bar' => 'test1'), false),
			"`id` = '1' AND `bar` = 'test1'");
	}
	
	function test_parse_constraints_value_list() {
		$this->assertEqual(__sql::parse_constraints(
			array('id' => range(1, 3)), false),
			"`id` IN ('1', '2', '3')");
	}
	
	function test_insert_query() {
		$sql = __sql::insert_row('foo', array('bar' => 'test3'), false);
		$this->assertEqual($sql->query, "INSERT INTO `foo`(`bar`) VALUES('test3');");
	}
	
	function test_delete_query() {
		$sql = __sql::delete('foo', array('bar' => 'test3'), false);
		$this->assertEqual($sql->query, "DELETE FROM `foo` WHERE `bar` = 'test3';");
	}
	
	function test_insert_delete() {
		self::set_login_data();
		$insert = __sql::insert_row('foo', array('bar' => 'test3'))->execute();
		
		// Do not continue unless the value has been inserted
		if( !$this->assertIdentical($insert->result, true) )
			return false;
		
		// Delete the record that was just inserted
		$delete = __sql::delete('foo', array('bar' => 'test3'))->execute();
		$this->assertIdentical($delete->result, true);
	}
	
	static function set_login_data() {
		__sql::set_login_data('localhost', 'root', '', 'pquery_test');
	}
}

?>