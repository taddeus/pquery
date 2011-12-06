<?php

__p::load_plugin('array');

class pQueryArrayTest extends UnitTestCase {
	var $variable;
	var $arr;
	
	function __construct() {
		parent::__construct('pQuery array plugin');
	}
	
	function setUp() {
		$this->variable = array('test');
		$this->arr = _arr($this->variable);
	}
	
	function test_constructor() {
		$this->assertIsA($this->arr, 'pQueryArray', 'constructor does not return pQueryArray object.');
		$this->assertEqual($this->arr->variable, $this->variable, 'variable is not set correctly.');
	}
	
	function test_get_simple() {
		$this->assertEqual($this->arr->get(0), $this->variable[0]);
	}
	
	function test_get_non_existent() {
		$this->assertNull($this->arr->get(count($this->variable)), 'non-existent index should yield NULL.');
	}
	
	function test_is_empty_empty() {
		$this->assertTrue(_arr(array())->is_empty());
	}
	
	function test_is_empty_non_empty() {
		$this->assertFalse($this->arr->is_empty());
	}
	
	function test_reverse() {
		$orginal = range(1, 4);
		$reverse = range(4, 1, -1);
		$arr = _arr($orginal);
		$this->assertEqual($arr->reverse()->variable, $reverse, 'reverse is not really reverse...');
	}
	
	function test_call_count() {
		$this->assertEqual($this->arr->count(), count($this->variable));
	}
	
	function test_call_sort() {
		$arr = range(1, 8);
		shuffle($arr);
		$this->assertEqual(_arr($arr)->sort()->variable, range(1, 8));
	}
}

?>