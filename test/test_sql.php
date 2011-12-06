<?php

__p::load_plugin('sql');

class pQuerySqlTest extends UnitTestCase {
	function __construct() {
		parent::__construct('pQuery MySQL plugin');
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
	
	function test_num_rows() {
		self::set_login_data();
		$sql = _sql("select bar from foo where id in (1, 2)");
		$this->assertEqual($sql->num_rows(), 2);
	}
	
	function test_escape_column_simple() {
		$this->assertEqual(__sql::escape_column('foo'), '`foo`');
	}
	
	function test_escape_column_escaped() {
		$this->assertEqual(__sql::escape_column('`foo`'), '`foo`');
	}
	
	function test_escape_column_table() {
		$this->assertEqual(__sql::escape_column('foo.bar'), '`foo`.`bar`');
	}
	
	function test_escape_column_aggregate() {
		$this->assertEqual(__sql::escape_column('count(foo)'), 'COUNT(`foo`)');
	}
	
	function test_escape_column_aggregate_escaped() {
		$this->assertEqual(__sql::escape_column('count(`foo`)'), 'COUNT(`foo`)');
	}
	
	function test_parse_columns_star() {
		$sql = __sql::select('foo', '*', '', false);
		$this->assertEqual($sql->query, "SELECT * FROM `foo` WHERE 1;");
	}
	
	function test_parse_columns_simple() {
		$sql = __sql::select('foo', array('id', 'bar'), '', false);
		$this->assertEqual($sql->query, "SELECT `id`, `bar` FROM `foo` WHERE 1;");
	}
	
	function test_parse_columns_as() {
		$sql = __sql::select('foo', array('id' => 'foo_id'), '', false);
		$this->assertEqual($sql->query, "SELECT `id` AS `foo_id` FROM `foo` WHERE 1;");
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
	
	function test_select_query() {
		$sql = __sql::select('foo', '*', array('bar' => 'test1'), false);
		$this->assertEqual($sql->query, "SELECT * FROM `foo` WHERE `bar` = 'test1';");
	}
	
	function test_update_query() {
		$sql = __sql::update('foo', array('bar' => 'test4'), array('id' => 1), false);
		$this->assertEqual($sql->query, "UPDATE `foo` SET `bar` = 'test4' WHERE `id` = '1';");
	}
	
	function test_insert_query() {
		$sql = __sql::insert_row('foo', array('bar' => 'test3'), false);
		$this->assertEqual($sql->query, "INSERT INTO `foo`(`bar`) VALUES('test3');");
	}
	
	function test_delete_query() {
		$sql = __sql::delete('foo', array('bar' => 'test3'), false);
		$this->assertEqual($sql->query, "DELETE FROM `foo` WHERE `bar` = 'test3';");
	}
	
	function test_select() {
		self::set_login_data();
		$sql = _sql("select bar from foo where id = 1");
		$result = $sql->fetch('object');
		$this->assertEqual($result->bar, 'test1');
		$this->assertIdentical($sql->fetch(), false);
	}
	
	function test_update() {
		self::set_login_data();
		$update = __sql::update('foo', array('bar' => 'test4'),
			array('id' => 1), false)->execute();
		
		// Do not continue unless the value has been updated
		if( !$this->assertIdentical($update->result, true) )
			return false;
		
		// Chachge the updated record back to its original state
		$update = __sql::update('foo', array('bar' => 'test1'),
			array('id' => 1), false)->execute();
		$this->assertIdentical($update->result, true);
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