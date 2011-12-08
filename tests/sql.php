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
		
		$this->assertEquals('a', __sql::$login_data['host']);
		$this->assertEquals('b', __sql::$login_data['username']);
		$this->assertEquals('c', __sql::$login_data['password']);
		$this->assertEquals('d', __sql::$login_data['dbname']);
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
		$this->assertEquals('foobar', $sql->variable);
		$this->assertEquals('foobar', $sql->query);
	}
	
	function test_variable_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => 'test1'));
		$this->assertEquals("select id from foo where bar = 'test1'", $sql->query);
	}
	
	function test_unescaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set_unescaped(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertEquals("select id from foo where bar = 'select id from foo where bar = 'test1''", $sql->query);
	}
	
	function test_escaped_query() {
		$sql = _sql("select id from foo where bar = '[bar]'")
					->set(array('bar' => "select id from foo where bar = 'test1'"));
		$this->assertNotEquals("select id from foo where bar = 'select id from foo where bar = 'test1''", $sql->query);
	}
	
	function test_constructor() {
		$this->assertInstanceOf('pQuerySql', _sql("foo"), 'constructor does not return pQuerySql object');
	}
	
	function test_constructor_simple() {
		$sql = _sql("select id from foo where bar = '[0]'", 'test1');
		$this->assertEquals("select id from foo where bar = 'test1'", $sql->query);
	}
	
	function test_constructor_advanced() {
		$sql = _sql("[0] [bar] [foo] [2]", '1', array('bar' => '2', 'foo' => '3'), '4');
		$this->assertEquals("1 2 3 4", $sql->query);
	}
	
	function test_num_rows() {
		$sql = _sql("select bar from foo where id in (1, 2)");
		$this->assertEquals(2, $sql->num_rows());
	}
	
	function test_escape_column_simple() {
		$this->assertEquals('`foo`', __sql::escape_column('foo'));
	}
	
	function test_escape_column_escaped() {
		$this->assertEquals('`foo`', __sql::escape_column('`foo`'));
	}
	
	function test_escape_column_table() {
		$this->assertEquals('`foo`.`bar`', __sql::escape_column('foo.bar'));
	}
	
	function test_escape_column_aggregate() {
		$this->assertEquals('COUNT(`foo`)', __sql::escape_column('count(foo)'));
	}
	
	function test_escape_column_aggregate_escaped() {
		$this->assertEquals('COUNT(`foo`)', __sql::escape_column('count(`foo`)'));
	}
	
	function test_escape_value() {
		$this->assertEquals("'foo'", __sql::escape_value("foo"));
	}
	
	function test_escape_value_escaped() {
		$this->assertEquals("'foo'", __sql::escape_value("'foo'"));
	}
	
	function test_parse_columns_star() {
		$sql = __sql::select('foo', '*', '', false);
		$this->assertEquals("SELECT * FROM `foo` WHERE 1;", $sql->query);
	}
	
	function test_parse_columns_simple() {
		$sql = __sql::select('foo', array('id', 'bar'), '', false);
		$this->assertEquals("SELECT `id`, `bar` FROM `foo` WHERE 1;", $sql->query);
	}
	
	function test_parse_columns_as() {
		$sql = __sql::select('foo', array('id' => 'foo_id'), '', false);
		$this->assertEquals("SELECT `id` AS `foo_id` FROM `foo` WHERE 1;", $sql->query);
	}
	
	function test_parse_constraints_empty() {
		$this->assertSame("1", __sql::parse_constraints(null, false));
	}
	
	function test_parse_constraints_string() {
		$constraints = "foo LIKE '%bar%'";
		$this->assertEquals($constraints, __sql::parse_constraints($constraints, false));
	}
	
	function test_parse_constraints_simple() {
		$this->assertEquals("`id` = '1' AND `bar` = 'test1'",
			__sql::parse_constraints(array('id' => 1, 'bar' => 'test1'), false));
	}
	
	function test_parse_constraints_value_list() {
		$this->assertEquals("`id` IN ('1', '2', '3')",
			__sql::parse_constraints(array('id' => range(1, 3)), false));
	}
	
	function test_select_query() {
		$sql = __sql::select('foo', '*', array('bar' => 'test1'), false);
		$this->assertEquals("SELECT * FROM `foo` WHERE `bar` = 'test1';", $sql->query);
	}
	
	function test_update_query() {
		$sql = __sql::update('foo', array('bar' => 'test4'), array('id' => 1), false);
		$this->assertEquals("UPDATE `foo` SET `bar` = 'test4' WHERE `id` = '1';", $sql->query);
	}
	
	function test_insert_query() {
		$sql = __sql::insert_row('foo', array('bar' => 'test3'), false);
		$this->assertEquals("INSERT INTO `foo`(`bar`) VALUES('test3');", $sql->query);
	}
	
	function test_delete_query() {
		$sql = __sql::delete('foo', array('bar' => 'test3'), false);
		$this->assertEquals("DELETE FROM `foo` WHERE `bar` = 'test3';", $sql->query);
	}
	
	/**
	 * @depends test_select_query
	 */
	function test_select() {
		$sql = _sql("select bar from foo where id = 1");
		$result = $sql->fetch('object');
		$this->assertEquals('test1', $result->bar);
		$this->assertFalse($sql->fetch());
	}
	
	/**
	 * @depends test_update_query
	 */
	function test_update() {
		$update = __sql::update('foo', array('bar' => 'test1'),
			array('id' => 1), false)->execute();
		$this->assertTrue($update->result);
	}
	
	/**
	 * @depends test_insert_query
	 */
	function test_insert() {
		$insert = __sql::insert_row('foo', array('bar' => 'test3'))->execute();
		$this->assertTrue($insert->result);
	}
	
	/**
	 * @depends test_delete_query
	 * @depends test_insert
	 */
	function test_delete() {
		$delete = __sql::delete('foo', array('bar' => 'test3'))->execute();
		$this->assertTrue($delete->result);
	}
}

?>