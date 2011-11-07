<?php

__p::load_plugin('template');

class pQueryTemplateTest extends UnitTestCase {
	const TEMPLATES_FOLDER = 'templates/';
	var $templates_folder;
	var $file;
	var $tpl;
	
	function __construct() {
		parent::__construct('pQuery template plugin');
	}
	
	function setUp() {
		$this->templates_folder = PQUERY_ROOT.'test/'.self::TEMPLATES_FOLDER;
		__tpl::set_root($this->templates_folder, false);
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
	
	function test_add_root_failure() {
		$this->expectException('pQueryException');
		__tpl::add_root('non_existing_folder');
	}
	
	function test_set_root_relative() {
		$folder = PQUERY_ROOT.'test/';
		$folder_relative = 'test/';
		__tpl::set_root($folder_relative);
		$this->assertEqual(array($folder), __tpl::$include_path, 'folder was not set as only include path');
	}
	
	function test_set_root_absolute() {
		$folder = PQUERY_ROOT.'test/';
		__tpl::set_root($folder, false);
		$this->assertEqual(array($folder), __tpl::$include_path, 'folder was not set as only include path');
	}
	
	function test_constructor() {
		$this->assertIsA($this->tpl, 'pQueryTemplate', 'constructor does not return pQueryTemplate object');
	}
	
	function test_open_template_file() {
		$path = $this->templates_folder.$this->file;
		$content = file_get_contents($path);
		$this->assertEqual($this->tpl->content, $content, 'template content is not set correctly');
	}
	
	function test_non_existent_file() {
		$this->expectException('pQueryException');
		_tpl('non_existent_file.tpl');
	}
	
	function test_parse() {
		$expected_content = file_get_contents($this->templates_folder.'expect_parse.txt');
		$test1 = $this->tpl->data->add('test1', array('var' => 'some-variable'));
		$this->tpl->data->add('test1', array('var' => 'some-other-variable'));
		$test1->add('test2');
		$this->tpl->data->add('test3');
		$this->assertEqual($this->tpl->parse(), $expected_content, 'parsed templated does not match expected content');
	}
}

?>