<?php

include_once 'config.php';
__p::load_plugin('sql');

class pQuerySqlTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		__sql::set_login_data('localhost', 'root', '', 'pquery_test');
	}
	
	function tearDown() {
		__sql::disconnect();
		__sql::$login_data = array();
	}
	
	function test_set_login_data() {
		$this->tearDown();
		__sql::set_login_data('a', 'b', 'c', 'd');
		
		$this->assertEquals(__sql::$login_data['host'], 'a');
		$this->assertEquals(__sql::$login_data['username'], 'b');
		$this->assertEquals(__sql::$login_data['password'], 'c');
		$this->assertEquals(__sql::$login_data['dbname'], 'd');
	}
	
	/**
	 * @expectedException pQueryException
	 */
	function test_no_login_data() {
		$this->tearDown();
		__sql::assert_login_data_exist();
	}
	
	function test_query_getter() {
		$sql = _sql('foobar');
		$this->assertEquals($sql->variable, 'foobar');
		$this->assertEquals($sql->query, 'foobar');
	}
	
	function test_variable_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => 'test1'));
		$this->assertEquals($sql->query, "select id from foo where bar = 'test1'");
	}
	
	function test_unescaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set_unescaped(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertEquals($sql->query, "select id from foo where bar = 'select id from foo where bar = 'test1''");
	}
	
	function test_escaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertNotEquals($sql->query, "select id from foo where bar = 'select id from foo where bar = 'test1''");
	}
	
	function test_constructor_simple() {
		$sql = _sql("select id from foo where bar = '[0]'", 'test1');
		$this->assertEquals($sql->query, "select id from foo where bar = 'test1'");
	}
	
	function test_constructor_advanced() {
		$sql = _sql("[0] [bar] [foo] [2]", '1', array('bar' => '2', 'foo' => '3'), '4');
		$this->assertEquals($sql->query, "1 2 3 4");
	}
	
	function test_num_rows() {
		$sql = _sql("select bar from foo where id in (1, 2)");
		$this->assertEquals($sql->num_rows(), 2);
	}
	
	function test_escape_column_simple() {
		$this->assertEquals(__sql::escape_column('foo'), '`foo`');
	}
	
	function test_escape_column_escaped() {
		$this->assertEquals(__sql::escape_column('`foo`'), '`foo`');
	}
	
	function test_escape_column_table() {
		$this->assertEquals(__sql::escape_column('foo.bar'), '`foo`.`bar`');
	}
	
	function test_escape_column_aggregate() {
		$this->assertEquals(__sql::escape_column('count(foo)'), 'COUNT(`foo`)');
	}
	
	function test_escape_column_aggregate_escaped() {
		$this->assertEquals(__sql::escape_column('count(`foo`)'), 'COUNT(`foo`)');
	}
	
	function test_parse_columns_star() {
		$sql = __sql::select('foo', '*', '', false);
		$this->assertEquals($sql->query, "SELECT * FROM `foo` WHERE 1;");
	}
	
	function test_parse_columns_simple() {
		$sql = __sql::select('foo', array('id', 'bar'), '', false);
		$this->assertEquals($sql->query, "SELECT `id`, `bar` FROM `foo` WHERE 1;");
	}
	
	function test_parse_columns_as() {
		$sql = __sql::select('foo', array('id' => 'foo_id'), '', false);
		$this->assertEquals($sql->query, "SELECT `id` AS `foo_id` FROM `foo` WHERE 1;");
	}
	
	function test_parse_constraints_empty() {
		$this->assertSame(__sql::parse_constraints(null, false), "1");
	}
	
	function test_parse_constraints_string() {
		$constraints = "foo LIKE '%bar%'";
		$this->assertEquals(__sql::parse_constraints($constraints, false), $constraints);
	}
	
	function test_parse_constraints_simple() {
		$this->assertEquals(__sql::parse_constraints(
			array('id' => 1, 'bar' => 'test1'), false),
			"`id` = '1' AND `bar` = 'test1'");
	}
	
	function test_parse_constraints_value_list() {
		$this->assertEquals(__sql::parse_constraints(
			array('id' => range(1, 3)), false),
			"`id` IN ('1', '2', '3')");
	}
	
	function test_select_query() {
		$sql = __sql::select('foo', '*', array('bar' => 'test1'), false);
		$this->assertEquals($sql->query, "SELECT * FROM `foo` WHERE `bar` = 'test1';");
	}
	
	function test_update_query() {
		$sql = __sql::update('foo', array('bar' => 'test4'), array('id' => 1), false);
		$this->assertEquals($sql->query, "UPDATE `foo` SET `bar` = 'test4' WHERE `id` = '1';");
	}
	
	function test_insert_query() {
		$sql = __sql::insert_row('foo', array('bar' => 'test3'), false);
		$this->assertEquals($sql->query, "INSERT INTO `foo`(`bar`) VALUES('test3');");
	}
	
	function test_delete_query() {
		$sql = __sql::delete('foo', array('bar' => 'test3'), false);
		$this->assertEquals($sql->query, "DELETE FROM `foo` WHERE `bar` = 'test3';");
	}
	
	/**
	 * @depends test_select_query
	 */
	function test_select() {
		$sql = _sql("select bar from foo where id = 1");
		$result = $sql->fetch('object');
		$this->assertEquals('test1', $result->bar);
		$this->assertSame($sql->fetch(), false);
	}
	
	/**
	 * @depends test_update_query
	 */
	function test_update() {
		$update = __sql::update('foo', array('bar' => 'test1'),
			array('id' => 1), false)->execute();
		
		// Do not continue unless the value has been updated
		$this->assertSame($update->result, true);
	}
	
	/**
	 * @depends test_insert_query
	 */
	function test_insert() {
		$insert = __sql::insert_row('foo', array('bar' => 'test3'))->execute();
		$this->assertSame($insert->result, true);
	}
	
	/**
	 * @depends test_delete_query
	 * @depends test_insert
	 */
	function test_delete() {
		$delete = __sql::delete('foo', array('bar' => 'test3'))->execute();
		$this->assertSame($delete->result, true);
	}
}

?>