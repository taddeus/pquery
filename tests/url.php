<?php

include_once 'config.php';
__p::require_plugins('url');

class pQueryUrlTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		$this->url = _url('foo/bar');
	}
	
	function tearDown() {
		__url::$handlers = array();
	}
	
	function test_constructor() {
		$this->assertInstanceOf('pQueryUrl', $this->url, 'constructor does not return pQueryUrl object');
	}
	
	function test_parse_url() {
		$this->assertEquals('foo/bar', _url('/foo/bar')->url);
		$this->assertEquals('foo/bar', _url('foo/bar/')->url);
	}
	
	function test_add_handler_callable() {
		$handler = create_function('$a', 'return true;');
		__url::add_handler('foo/.*', $handler);
		$this->assertArrayHasKey('%foo/.*%', __url::$handlers);
		$this->assertEquals($handler, __url::$handlers['%foo/.*%']);
	}
	
	/**
	 * @expectedException pQueryException
	 */
	function test_add_handler_not_callable() {
		__url::add_handler('foo/.*', 'bar');
	}
	
	/**
	 * @depends test_add_handler_callable
	 */
	function test_add_handlers() {
		$handler1 = create_function('$a', 'return true;');
		$handler2 = create_function('$a', 'return false;');
		__url::add_handlers(array(
			'foo/.*' => $handler1,
			'bar/.*' => $handler2
		));
		$this->assertArrayHasKey('%foo/.*%', __url::$handlers);
		$this->assertArrayHasKey('%bar/.*%', __url::$handlers);
		$this->assertEquals($handler1, __url::$handlers['%foo/.*%']);
		$this->assertEquals($handler2, __url::$handlers['%bar/.*%']);
	}
	
	/**
	 * @depends test_add_handler_callable
	 */
	function test_handler() {
		$handler = create_function('$a', 'return $a;');
		__url::add_handler('foo/(.*)', $handler);
		$result = $this->url->handler();
		$this->assertEquals('bar', $result);
	}
	
	/**
	 * @depends test_handler
	 * @expectedException pQueryException
	 */
	function test_handler_error() {
		$this->url->handler();
	}
}

?>