<?php

include_once __DIR__.'/../config.php';
__p::load_utils('block');

class BlockTest extends PHPUnit_Framework_TestCase {
	var $block;
	
	function setUp() {
		$this->block = new Block('foo');
	}
	
	function test_constructor() {
		$count = Block::$count;
		$block = new Block('foo');
		$this->assertEquals('foo', $block->name);
		$this->assertEquals($count + 1, Block::$count);
	}
	
	function test_set_single() {
		$block = $this->block->set('bar', 'baz');
		$this->assertEquals('baz', $this->block->vars['bar']);
		$this->assertSame($this->block, $block);
	}
	
	function test_set_multiple() {
		$data = array('bar' => 'baz', 'bar2' => 'baz2');
		$block = $this->block->set($data);
		$this->assertEquals($data, $this->block->vars);
		$this->assertSame($this->block, $block);
	}
	
	/**
	 * @depends test_set_single
	 */
	function test_get_simple() {
		$this->block->set('bar', 'baz');
		$this->assertEquals('baz', $this->block->get('bar'));
	}
	
	function test_get_null() {
		$this->assertNull($this->block->get('bar'));
	}
	
	/**
	 * @depends test_get_simple
	 */
	function test_getter() {
		$this->block->set('bar', 'baz');
		$this->assertEquals('baz', $this->block->bar);
	}
	
	/**
	 * @depends test_set_multiple
	 */
	function test_add_empty() {
		$block = $this->block->add('bar');
		$this->assertInstanceOf('Block', $block);
		$this->assertEquals('bar', $block->name);
	}
	
	/**
	 * @depends test_get_simple
	 * @depends test_add_empty
	 */
	function test_add_data() {
		$block = $this->block->add('bar', array('baz' => 'foo'));
		$this->assertEquals('foo', $block->get('baz'));
	}
	
	/**
	 * @depends test_add_empty
	 * @depends test_get_simple
	 */
	function test_get_parent() {
		$block = $this->block->set('bar', 'baz')->add('new-foo');
		$this->assertEquals('baz', $block->get('bar'));
	}
	
	/**
	 * @depends test_add_empty
	 */
	function test_find_single() {
		$block = $this->block->add('bar');
		$this->block->add('baz');
		$this->assertSame(array($block), $this->block->find('bar'));
	}
	
	/**
	 * @depends test_add_empty
	 */
	function test_find_multiple() {
		$block0 = $this->block->add('bar');
		$block1 = $this->block->add('bar');
		$this->block->add('baz');
		$this->assertSame(array($block0, $block1), $this->block->find('bar'));
	}
	
	/**
	 * @depends test_add_empty
	 */
	function test_find_none() {
		$this->block->add('bar');
		$this->block->add('baz');
		$this->assertSame(array(), $this->block->find('foo'));
	}
	
	/**
	 * @depends test_add_empty
	 */
	function test_remove_child() {
		$block0 = $this->block->add('bar');
		$block1 = $this->block->add('baz');
		$ret = $this->block->remove_child($block0);
		$this->assertSame(array($block1), $this->block->children);
		$this->assertSame($this->block, $ret);
	}
	
	/**
	 * @depends test_remove_child
	 */
	function test_remove() {
		$block0 = $this->block->add('bar');
		$block1 = $this->block->add('baz');
		$ret = $block0->remove();
		$this->assertSame(array($block1), $this->block->children);
		$this->assertSame($block0, $ret);
	}
}

?>