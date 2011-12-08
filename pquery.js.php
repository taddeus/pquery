<?php
/**
 * pQuery plugin for parsing templates.
 * 
 * @package pQuery
 */

__p::require_plugins('cache');
__p::load_utils('jshrink');

/**
 * @todo Documentation
 */
class pQueryJs extends pQueryCache {
	static $accepts = array('array' => 'add_extensions', 'string' => 'make_array');
	
	/**
	 * Make a single file into an array.
	 * 
	 * @param string $file The file to put in an array.
	 */
	function make_array($file) {
		return $this->add_extensions(array($file));
	}
	
	/**
	 * 
	 * 
	 * @param array $files 
	 */
	function add_extensions($files) {
		foreach( $files as $i => $file )
			if( !preg_match('/\.js$/', $file) )
				$files[$i] = $file.'.js';
		
		return $this->get_modification_dates($files);
	}
	
	/**
	 * 
	 */
	function minify() {
		$this->content = trim(JShrink::minify($this->content, array('flaggedComments' => false)));
		
		return $this;
	}
	
	/**
	 * 
	 */
	function set_headers() {
		header('Content-Type: application/javascript');
		
		return $this;
	}
}

/**
 * Shortcut constructor for {@link pQueryJs}.
 * 
 * @param array|string $scripts 
 * @returns pQueryJs A new script cache instance.
 */
function _js($scripts) {
	return pQuery::create('js', $scripts);
}

/*
 * Add plugin to pQuery
 */
__p::extend('pQueryJs', 'js');

?>