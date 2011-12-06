<?php

include_once 'config.php';
__p::load_plugin('template');

class pQueryTemplateTest extends PHPUnit_Framework_TestCase {
	const TEMPLATES_FOLDER = 'templates/';
	var $templates_folder;
	var $file;
	var $tpl;
	
	function setUp() {
		// Set root to tests/templates
		$this->templates_folder = PQUERY_ROOT.'test/'.self::TEMPLATES_FOLDER;
		__tpl::set_root($this->templates_folder, false);
		
		// Load the test template
		$this->file = 'test.tpl';
		$this->tpl = _tpl($this->file);
	}
	
	function test_add_root_relative() {
		$folder = PQUERY_ROOT.'test/';
		$folder_relative = 'test/';
		__tpl::add_root($folder_relative);
		$this->assertTrue(in_array($folder, __tpl::$include_path), 'folder was not added to include path');
	}
	
	function test_add_root_absolute() {
		$folder = PQUERY_ROOT.'test/';
		__tpl::add_root($folder, false);
		$this->assertTrue(in_array($folder, __tpl::$include_path), 'folder was not added to include path');
	}
	
	/**
	 * @expectedException pQueryException
	 */
	function test_add_root_failure() {
		__tpl::add_root('non_existing_folder');
	}
	
	function test_set_root_relative() {
		$folder = PQUERY_ROOT.'test/';
		$folder_relative = 'test/';
		__tpl::set_root($folder_relative);
		$this->assertEquals(array($folder), __tpl::$include_path, 'folder was not set as only include path');
	}
	
	function test_set_root_absolute() {
		$folder = PQUERY_ROOT.'test/';
		__tpl::set_root($folder, false);
		$this->assertEquals(array($folder), __tpl::$include_path, 'folder was not set as only include path');
	}
	
	function test_constructor() {
		$this->assertTrue($this->tpl instanceof pQueryTemplate, 'constructor does not return pQueryTemplate object');
	}
	
	function test_open_template_file() {
		$path = $this->templates_folder.$this->file;
		$content = file_get_contents($path);
		$this->assertEquals($this->tpl->content, $content, 'template content is not set correctly');
	}
	
	/**
	 * @expectedException pQueryException
	 */
	function test_non_existent_file() {
		_tpl('non_existent_file.tpl');
	}
	
	function test_parse() {
		// Add some blocks with test variables
		$this->tpl->data->set('variable', '-variable value-');
		$object = new StdClass;
		$object->property = '-object property-';
		$this->tpl->data->set('object', $object);
		$this->tpl->data->set('assoc', array('index' => '-assoc index-'));
		
		$test1 = $this->tpl->data->add('test1', array('var' => 'some-variable'));
		$this->tpl->data->add('test1', array('var' => 'some-other-variable'));
		$test1->add('test2');
		$this->tpl->data->add('test3');
		
		// Expected content is defined in a text file
		$expected_content = file_get_contents($this->templates_folder.'expect_parse.html');
		
		$this->assertEquals($this->tpl->parse(), $expected_content);
	}
}

?>